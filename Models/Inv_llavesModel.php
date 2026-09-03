<?php

/* =====================================================
   PRÉSTAMO Y DEVOLUCIÓN DE LLAVES A COLABORADORES

   Módulo "Control de Llaves". NO tiene relación con el
   flujo de traslados de unidades: es la otra cara del
   mismo inventario físico de llaves (wms_llaves_inventario /
   wms_llaves_movimientos), pero aquí solo se gestiona el
   préstamo temporal de una llave a un colaborador y su
   devolución.
===================================================== */

class Inv_llavesModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    /* =====================================================
       UNIDADES (mismo catálogo de VINs activos que usa
       Traslados, duplicado aquí para que este módulo no
       dependa de ese controlador/modelo)
    ===================================================== */

    public function selectUnidades($plantaid = null)
    {
        $params = [];

        $sql = "
            SELECT
                va.idasignacion,
                ns.numero_serie AS vin,
                ns.id_numeros_serie AS vinid,
                i.idinventario,
                i.descripcion AS unidad,
                ns.almacenid,
                a.descripcion AS almacen
            FROM mrp_vin_asignaciones va
            INNER JOIN wms_numeros_series ns
                ON ns.id_numeros_serie = va.numero_serie_id
            INNER JOIN wms_inventario i
                ON i.idinventario = ns.inventarioid
            LEFT JOIN wms_almacenes a
                ON a.idalmacen = ns.almacenid
            WHERE va.estado = 1
            AND i.tipo_elemento = 'P'
        ";

        // Si viene una planta (usuario no-administrador), solo se ven las
        // unidades cuyo almacén actual pertenece a esa planta.
        if ($plantaid !== null) {
            $sql .= " AND a.plantaid = ? ";
            $params[] = $plantaid;
        }

        return $this->select_all($sql, $params);
    }

    /* =====================================================
       RESPONSABLES (colaboradores = usuarios activos del
       sistema)
    ===================================================== */

    public function selectResponsablesActivos()
    {
        $sql = "
            SELECT
                idusuario,
                numcolaborador,
                CONCAT(nombres, ' ', apellidos) AS nombre_completo
            FROM usuarios
            WHERE status = 1
            ORDER BY nombres, apellidos
        ";

        return $this->select_all($sql);
    }

    /* =====================================================
       PRÉSTAMO
    ===================================================== */

    public function prestarLlave(
        $vinid,
        $inventarioid,
        $tipo_llave,
        $almacenid,
        $nombre_colaborador,
        $fecha_prevista_devolucion,
        $observaciones,
        $usuarioid,
        $plantaidPermitida = null,
        $entregadoPorId = null
    ) {
        $pdo = $this->getConexion();

        try {

            $pdo->beginTransaction();

            // Si el usuario está restringido a una planta, valida que el
            // almacén de la unidad realmente pertenezca a esa planta. Esto
            // evita que alguien manipule el request y preste llaves de un
            // almacén fuera de su planta aunque el select del formulario ya
            // venga filtrado.
            if ($plantaidPermitida !== null) {

                $almacen = $this->select(
                    "SELECT idalmacen FROM wms_almacenes WHERE idalmacen = ? AND plantaid = ? LIMIT 1",
                    [$almacenid, $plantaidPermitida]
                );

                if (empty($almacen)) {
                    throw new Exception("No tiene permiso para prestar llaves de este almacén");
                }
            }

            // Bloquea el renglón de esta llave (si ya existe) mientras se
            // decide si se puede prestar. Si la misma llave está siendo
            // usada en ese instante por un traslado o por otro préstamo,
            // esta consulta espera y luego respeta su estado real.
            $existente = $this->select(
                "SELECT idllave, estado FROM wms_llaves_inventario
                 WHERE vinid = ? AND tipo_llave = ? LIMIT 1 FOR UPDATE",
                [$vinid, $tipo_llave]
            );

            if (!empty($existente)) {

                if ((int)$existente['estado'] !== 1) {
                    throw new Exception(
                        "Esta llave ya está entregada/en tránsito en otro movimiento (traslado o préstamo) y no ha sido devuelta"
                    );
                }

                $llaveid = $existente['idllave'];
            } else {

                $llaveid = $this->insert(
                    "INSERT INTO wms_llaves_inventario
                     (vinid, inventarioid, tipo_llave, almacenid, estado)
                     VALUES (?,?,?,?,1)",
                    [$vinid, $inventarioid, $tipo_llave, $almacenid]
                );
            }

            // referenciaid es NOT NULL y no hay un "encabezado" externo al
            // que apuntar como en los traslados, así que el propio
            // movimiento se referencia a sí mismo: eso lo vuelve el "id de
            // préstamo" que después usa la devolución para encontrarlo.
            //
            // entregado_porid es quién FÍSICAMENTE entrega la llave (un
            // colaborador elegido en el formulario, no necesariamente el
            // usuario logeado): antes esto no se registraba y se asumía
            // que era la sesión activa, lo cual es incorrecto porque
            // cualquier sesión puede estar haciendo la captura. Se guarda
            // por idusuario (igual que usuarioid), no por nombre.
            $idmovimiento = $this->insert(
                "INSERT INTO wms_llaves_movimientos
                 (llaveid, tipo_movimiento, referenciaid, tipo_accion,
                  almacen_origenid, responsable, entregado_porid, usuarioid,
                  fecha_prevista_devolucion, observaciones)
                 VALUES (?, 'prestamo', ?, 'prestamo', ?, ?, ?, ?, ?, ?)",
                [
                    $llaveid,
                    $llaveid,
                    $almacenid,
                    $nombre_colaborador,
                    $entregadoPorId,
                    $usuarioid,
                    $fecha_prevista_devolucion ?: null,
                    $observaciones
                ]
            );

            $this->update(
                "UPDATE wms_llaves_movimientos SET referenciaid = ? WHERE idmovimiento = ?",
                [$idmovimiento, $idmovimiento]
            );

            $this->update(
                "UPDATE wms_llaves_inventario SET estado = 3 WHERE idllave = ?",
                [$llaveid]
            );

            $pdo->commit();

            return [
                "status" => true,
                "msg" => "Llave entregada correctamente a " . $nombre_colaborador
            ];
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ["status" => false, "msg" => $e->getMessage()];
        }
    }

    /* =====================================================
       DEVOLUCIÓN
    ===================================================== */

    public function devolverLlave(
        $idmovimientoPrestamo,
        $responsableRecibe,
        $observaciones,
        $usuarioid,
        $plantaidPermitida = null
    ) {
        $pdo = $this->getConexion();

        try {

            $pdo->beginTransaction();

            $prestamo = $this->select(
                "SELECT * FROM wms_llaves_movimientos
                 WHERE idmovimiento = ? AND tipo_movimiento = 'prestamo' AND tipo_accion = 'prestamo'
                 FOR UPDATE",
                [$idmovimientoPrestamo]
            );

            if (empty($prestamo)) {
                throw new Exception("No se encontró el préstamo indicado");
            }

            // Igual que en el préstamo: si el usuario está restringido a
            // una planta, no puede devolver una llave cuyo almacén de
            // origen pertenece a otra planta.
            if ($plantaidPermitida !== null) {

                $almacen = $this->select(
                    "SELECT idalmacen FROM wms_almacenes WHERE idalmacen = ? AND plantaid = ? LIMIT 1",
                    [$prestamo['almacen_origenid'], $plantaidPermitida]
                );

                if (empty($almacen)) {
                    throw new Exception("No tiene permiso para devolver llaves de este almacén");
                }
            }

            $yaDevuelto = $this->select(
                "SELECT idmovimiento FROM wms_llaves_movimientos
                 WHERE referenciaid = ? AND tipo_accion = 'devolucion' LIMIT 1",
                [$idmovimientoPrestamo]
            );

            if (!empty($yaDevuelto)) {
                throw new Exception("Este préstamo ya fue devuelto anteriormente");
            }

            $this->insert(
                "INSERT INTO wms_llaves_movimientos
                 (llaveid, tipo_movimiento, referenciaid, tipo_accion,
                  almacen_destinoid, responsable, usuarioid, observaciones)
                 VALUES (?, 'prestamo', ?, 'devolucion', ?, ?, ?, ?)",
                [
                    $prestamo['llaveid'],
                    $idmovimientoPrestamo,
                    $prestamo['almacen_origenid'],
                    $responsableRecibe,
                    $usuarioid,
                    $observaciones
                ]
            );

            $this->update(
                "UPDATE wms_llaves_inventario SET estado = 1 WHERE idllave = ?",
                [$prestamo['llaveid']]
            );

            $pdo->commit();

            return ["status" => true, "msg" => "Devolución registrada correctamente"];
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ["status" => false, "msg" => $e->getMessage()];
        }
    }

    /* =====================================================
       BITÁCORA
    ===================================================== */

    public function selectPrestamosLlaves($plantaid = null)
    {
        $params = [];

        // Solo préstamos a colaborador. Los traslados NO se mezclan aquí
        // -son "la otra cara de la moneda"- ; su propio historial vive en
        // selectHistorialTraslados() (pestaña "Historial de Traslados").
        $sql = "
            SELECT
                p.idmovimiento,
                p.llaveid,
                li.tipo_llave,
                ns.numero_serie AS vin,
                inv.descripcion AS modelo,
                alm.descripcion AS almacen,
                p.responsable,
                CONCAT(ue.nombres, ' ', ue.apellidos) AS asigno,
                p.fecha AS fecha_entrega,
                p.fecha_prevista_devolucion,
                p.observaciones,
                d.fecha AS fecha_devolucion,
                d.responsable AS responsable_devolucion
            FROM wms_llaves_movimientos p
            INNER JOIN wms_llaves_inventario li ON li.idllave = p.llaveid
            INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = li.vinid
            INNER JOIN wms_inventario inv ON inv.idinventario = li.inventarioid
            LEFT JOIN wms_almacenes alm ON alm.idalmacen = li.almacenid
            LEFT JOIN wms_llaves_movimientos d
                ON d.referenciaid = p.idmovimiento AND d.tipo_accion = 'devolucion'
            LEFT JOIN usuarios ue ON ue.idusuario = p.entregado_porid
            WHERE p.tipo_movimiento = 'prestamo' AND p.tipo_accion = 'prestamo'
        ";

        if ($plantaid !== null) {
            $sql .= " AND alm.plantaid = ? ";
            $params[] = $plantaid;
        }

        $sql .= " ORDER BY p.idmovimiento DESC ";

        $rows = $this->select_all($sql, $params);

        $hoy = date('Y-m-d');

        foreach ($rows as &$r) {

            if (!empty($r['fecha_devolucion'])) {
                $r['estatus'] = 'devuelta';
            } elseif (!empty($r['fecha_prevista_devolucion']) && $r['fecha_prevista_devolucion'] < $hoy) {
                $r['estatus'] = 'vencida';
            } else {
                $r['estatus'] = 'prestada';
            }
        }
        unset($r);

        return $rows;
    }

    /* =====================================================
       HISTORIAL DE LLAVES EN TRASLADO

       A diferencia de la Rama 2 de selectPrestamosLlaves() (que solo
       muestra las que SIGUEN en tránsito, li.estado != 1), aquí se
       muestra TODO el historial de movimientos de traslado por llave
       -en_transito, recibida o faltante-, para que se pueda rastrear
       a qué almacén se fue una llave y cuál fue su origen aunque ya
       haya sido recibida (momento en el que, por diseño, desaparece
       de la bitácora principal).

       Se filtra por planta viendo si la planta del usuario coincide
       con el ALMACÉN ORIGEN o el ALMACÉN DESTINO del movimiento (para
       que tanto quien la mandó como quien la recibió puedan verla),
       usando el almacen_origenid/almacen_destinoid que ya se guarda en
       cada wms_llaves_movimientos de tipo traslado. Administrador
       (plantaid null) ve todo.
    ===================================================== */

    public function selectHistorialTraslados($plantaid = null)
    {
        $params = [];

        $sql = "
            SELECT
                grp.llaveid,
                li.tipo_llave,
                ns.numero_serie AS vin,
                inv.descripcion AS modelo,
                grp.referenciaid AS idtraslado,
                t.folio,
                ao.descripcion AS almacen_origen,
                ad.descripcion AS almacen_destino,
                sal.fecha AS fecha_salida,
                ult.fecha AS fecha_ultimo_movimiento,
                ult.tipo_accion AS ultima_accion,
                ult.observaciones,
                CONCAT(us.nombres, ' ', us.apellidos) AS responsable_ultimo
            FROM (
                SELECT llaveid, referenciaid, MAX(idmovimiento) AS ultimo_idmovimiento
                FROM wms_llaves_movimientos
                WHERE tipo_movimiento = 'traslado'
                GROUP BY llaveid, referenciaid
            ) grp
            INNER JOIN wms_llaves_movimientos ult ON ult.idmovimiento = grp.ultimo_idmovimiento
            INNER JOIN wms_llaves_inventario li ON li.idllave = grp.llaveid
            INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = li.vinid
            INNER JOIN wms_inventario inv ON inv.idinventario = li.inventarioid
            LEFT JOIN wms_traslados_unidades t ON t.idtraslado = grp.referenciaid
            LEFT JOIN wms_almacenes ao ON ao.idalmacen = ult.almacen_origenid
            LEFT JOIN wms_almacenes ad ON ad.idalmacen = ult.almacen_destinoid
            LEFT JOIN wms_llaves_movimientos sal
                ON sal.llaveid = grp.llaveid
                AND sal.referenciaid = grp.referenciaid
                AND sal.tipo_accion = 'salida'
            LEFT JOIN usuarios us ON us.idusuario = ult.usuarioid
            WHERE 1=1
        ";

        if ($plantaid !== null) {
            $sql .= " AND (ao.plantaid = ? OR ad.plantaid = ?) ";
            $params[] = $plantaid;
            $params[] = $plantaid;
        }

        $sql .= " ORDER BY ult.idmovimiento DESC ";

        $rows = $this->select_all($sql, $params);

        $mapaEstatus = [
            'salida' => 'en_transito',
            'recepcion' => 'recibida',
            'faltante' => 'faltante',
        ];

        foreach ($rows as &$r) {
            $r['estatus'] = $mapaEstatus[$r['ultima_accion']] ?? 'en_transito';
            unset($r['ultima_accion']);
        }
        unset($r);

        return $rows;
    }

    /* =====================================================
       KPIs
    ===================================================== */

    public function getKpisLlavesGeneral($plantaid = null)
    {
        $whereAlmacen = "";
        $params = [];

        if ($plantaid !== null) {
            $whereAlmacen = " AND li.almacenid IN (SELECT idalmacen FROM wms_almacenes WHERE plantaid = ?) ";
            $params[] = $plantaid;
        }

        $totales = $this->select(
            "SELECT COUNT(*) total FROM wms_llaves_inventario li WHERE 1=1 {$whereAlmacen}",
            $params
        );
        $disponibles = $this->select(
            "SELECT COUNT(*) total FROM wms_llaves_inventario li WHERE li.estado = 1 {$whereAlmacen}",
            $params
        );

        // "estado != 1" en wms_llaves_inventario lo pone TANTO un préstamo
        // (Inv_llaves) COMO un traslado de unidad (Inv_traslados /
        // Inv_operaciones_traslados) que aún no se recibe en destino: es
        // el mismo campo compartido por los dos flujos. Para no mezclar
        // "prestada a un colaborador" con "se fue en un traslado sin
        // devolución todavía", se mira cuál fue el ÚLTIMO movimiento
        // registrado para esa llave (el que la dejó en estado != 1) y se
        // separa según su tipo_movimiento.
        $ultimoMovimiento = "
            INNER JOIN (
                SELECT llaveid, MAX(idmovimiento) AS ultimo_idmovimiento
                FROM wms_llaves_movimientos
                GROUP BY llaveid
            ) um ON um.llaveid = li.idllave
            INNER JOIN wms_llaves_movimientos m ON m.idmovimiento = um.ultimo_idmovimiento
        ";

        $prestadas = $this->select(
            "SELECT COUNT(*) total
             FROM wms_llaves_inventario li
             {$ultimoMovimiento}
             WHERE li.estado != 1
             AND m.tipo_movimiento = 'prestamo'
             {$whereAlmacen}",
            $params
        );

        $enTransitoTraslado = $this->select(
            "SELECT COUNT(*) total
             FROM wms_llaves_inventario li
             {$ultimoMovimiento}
             WHERE li.estado != 1
             AND m.tipo_movimiento = 'traslado'
             {$whereAlmacen}",
            $params
        );

        $whereAlmacenVencidas = "";
        $paramsVencidas = [];

        if ($plantaid !== null) {
            $whereAlmacenVencidas = " AND li.almacenid IN (SELECT idalmacen FROM wms_almacenes WHERE plantaid = ?) ";
            $paramsVencidas[] = $plantaid;
        }

        $vencidas = $this->select(
            "SELECT COUNT(*) total
             FROM wms_llaves_movimientos p
             INNER JOIN wms_llaves_inventario li ON li.idllave = p.llaveid
             LEFT JOIN wms_llaves_movimientos d
                ON d.referenciaid = p.idmovimiento AND d.tipo_accion = 'devolucion'
             WHERE p.tipo_movimiento = 'prestamo' AND p.tipo_accion = 'prestamo'
             AND d.idmovimiento IS NULL
             AND p.fecha_prevista_devolucion IS NOT NULL
             AND p.fecha_prevista_devolucion < CURDATE()
             {$whereAlmacenVencidas}",
            $paramsVencidas
        );

        return [
            "total" => $totales['total'],
            "disponibles" => $disponibles['total'],
            "prestadas" => $prestadas['total'],
            "en_transito_traslado" => $enTransitoTraslado['total'],
            "vencidas" => $vencidas['total']
        ];
    }
}
