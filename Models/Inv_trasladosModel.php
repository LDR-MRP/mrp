<?php

class Inv_trasladosModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }


    /* =====================================================
       ALMACENES
    ===================================================== */

    public function selectAlmacenes($plantaid = null)
    {
        $params = [];

        $sql = "
            SELECT
                idalmacen,
                descripcion
            FROM wms_almacenes
            WHERE estado = 2
        ";

        // Si viene una planta (usuario no-administrador), el almacén de
        // origen solo puede ser uno que pertenezca a su propia planta.
        if ($plantaid !== null) {
            $sql .= " AND plantaid = ? ";
            $params[] = $plantaid;
        }

        $sql .= " ORDER BY descripcion";

        return $this->select_all($sql, $params);
    }

    public function selectUnidadesPorAlmacen($idalmacen)
    {


        $idalmacen = intval($idalmacen);


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
    AND ns.almacenid = $idalmacen
    AND NOT EXISTS (
        SELECT 1
        FROM wms_traslados_unidades_detalle d
        INNER JOIN wms_traslados_unidades t
            ON t.idtraslado = d.trasladoid
        WHERE d.vinid = ns.id_numeros_serie
        AND t.estado IN (1,2,3)
    )
    ";


        return $this->select_all($sql);
    }

    /* =====================================================
       TRANSPORTISTAS
    ===================================================== */

    public function selectTransportistas()
    {

        $sql = "

        SELECT DISTINCT

            p.id_proveedor,

            p.razon_social

        FROM prv_cat_proveedores p

        INNER JOIN prv_rel_proveedores_actividades r
            ON p.id_proveedor = r.id_proveedor

        INNER JOIN prv_cat_actividades a
            ON a.id_actividad = r.id_actividad

        WHERE a.cve_actividad = 'TRASLADO_UNIDADES'

        AND p.estatus_operativo = 1

        ORDER BY p.razon_social

    ";


        return $this->select_all($sql);
    }



    /* =====================================================
       INSERTAR TRASLADO
    ===================================================== */

    public function insertTraslado(
        $almacen_origenid,
        $almacen_destinoid,
        $tipo_traslado,
        $proveedorid,
        $fecha_programada,
        $observaciones,
        $usuarioid,
        $unidades,
        $nombre_trasladista,
        $contacto_trasladista,
        $numero_licencia,
        $vigencia_licencia,
        $archivoLicencia
    ) {
        try {

            $folio = 'TRU-' . date('YmdHis') . '-' . rand(100, 999);

            $idtraslado = $this->insert(
                "INSERT INTO wms_traslados_unidades
             (folio, almacen_origenid, almacen_destinoid, tipo_traslado,
              proveedorid, fecha_programada, observaciones, usuarioid, estado)
             VALUES (?,?,?,?,?,?,?,?,1)",
                [
                    $folio,
                    $almacen_origenid,
                    $almacen_destinoid,
                    $tipo_traslado,
                    $proveedorid,
                    $fecha_programada,
                    $observaciones,
                    $usuarioid
                ]
            );

            $this->insert(
                "INSERT INTO wms_traslados_trasladistas
             (trasladoid, nombre, contacto, numero_licencia, vigencia_licencia, archivo_licencia)
             VALUES (?,?,?,?,?,?)",
                [
                    $idtraslado,
                    $nombre_trasladista,
                    $contacto_trasladista,
                    $numero_licencia,
                    $vigencia_licencia,
                    $archivoLicencia
                ]
            );

            $vins = [];

            foreach ($unidades as $unidad) {

                if ($this->validarUnidadEnTraslado($unidad['vinid'])) {
                    throw new Exception("La unidad " . $unidad['vin'] . " ya tiene un traslado pendiente");
                }

                if (in_array($unidad['vin'], $vins)) {
                    throw new Exception("El VIN " . $unidad['vin'] . " está repetido");
                }

                $vins[] = $unidad['vin'];

                $iddetalle = $this->insert(
                    "INSERT INTO wms_traslados_unidades_detalle
                 (trasladoid, vinid, inventarioid, vin)
                 VALUES (?,?,?,?)",
                    [$idtraslado, $unidad['vinid'], $unidad['inventarioid'], $unidad['vin']]
                );

                // Llave de esta unidad (opcional, viene del form)
                if (!empty($unidad['entrega_llave'])) {

                    $llaveid = $this->getOrCreateLlave(
                        $unidad['vinid'],
                        $unidad['inventarioid'],
                        $unidad['tipo_llave'],
                        $almacen_origenid
                    );

                    $this->insert(
                        "INSERT INTO wms_traslados_llaves
         (trasladoid, iddetalle, llaveid, tipo_llave, entrega_llave, estado_llave)
         VALUES (?,?,?,?,1,1)",
                        [$idtraslado, $iddetalle, $llaveid, $unidad['tipo_llave']]
                    );
                }
            }

            return true;
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    public function validarUnidadEnTraslado($vinid, $excludeTrasladoId = null)
    {


        $sql = "

SELECT

COUNT(*) total


FROM wms_traslados_unidades_detalle d


INNER JOIN wms_traslados_unidades t

ON t.idtraslado = d.trasladoid


WHERE d.vinid = $vinid


AND t.estado IN (1,2,3)

";

        $params = [];

        // Al editar un traslado, sus propias unidades ya tienen un
        // registro con estado 1 en esta misma tabla: hay que excluir el
        // propio traslado o siempre chocaría consigo mismo.
        if ($excludeTrasladoId !== null) {
            $sql .= " AND t.idtraslado != ? ";
            $params[] = $excludeTrasladoId;
        }


        $result = $this->select($sql, $params);


        return $result['total'] > 0;
    }




    /* =====================================================
       LISTADO
    ===================================================== */

    public function selectTraslados()
    {
        $sql = "

    SELECT

        t.idtraslado,
        t.folio,

        ao.descripcion AS almacen_origen,
        ad.descripcion AS almacen_destino,

        t.tipo_traslado,

        p.razon_social AS proveedor,

        COUNT(d.iddetalle) AS total_unidades,

        t.fecha_programada,

        t.estado,

        t.fecha

    FROM wms_traslados_unidades t

    INNER JOIN wms_almacenes ao
        ON ao.idalmacen = t.almacen_origenid

    INNER JOIN wms_almacenes ad
        ON ad.idalmacen = t.almacen_destinoid

    LEFT JOIN prv_cat_proveedores p
        ON p.id_proveedor = t.proveedorid

    LEFT JOIN wms_traslados_unidades_detalle d
        ON d.trasladoid = t.idtraslado

    GROUP BY t.idtraslado

    ORDER BY t.idtraslado DESC

    ";

        return $this->select_all($sql);
    }




    /* =====================================================
       DETALLE
    ===================================================== */

    public function getDetalleTraslado($id)
    {

        $sql = "

        SELECT

            d.vin,

            d.inventarioid,

            i.descripcion AS modelo,

            d.color


        FROM wms_traslados_unidades_detalle d


        INNER JOIN wms_inventario i
            ON i.idinventario = d.inventarioid


        WHERE d.trasladoid = $id

        ";


        return $this->select_all($sql);
    }

    public function selectUnidades()
    {

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


WHERE va.estado=1

AND i.tipo_elemento='P'

";


        return $this->select_all($sql);
    }

    public function validarUnidadPendiente($vinid)
    {


        $sql = "

SELECT


t.folio,

ao.descripcion AS origen,

ad.descripcion AS destino,

t.fecha_programada


FROM wms_traslados_unidades_detalle d


INNER JOIN wms_traslados_unidades t

ON t.idtraslado = d.trasladoid


INNER JOIN wms_almacenes ao

ON ao.idalmacen = t.almacen_origenid


INNER JOIN wms_almacenes ad

ON ad.idalmacen = t.almacen_destinoid


WHERE d.vinid = $vinid


AND t.estado IN (1,2,3)


LIMIT 1


";


        $data = $this->select($sql);



        if (empty($data)) {


            return [
                "pendiente" => false
            ];
        }



        return [

            "pendiente" => true,

            "folio" => $data['folio'],

            "origen" => $data['origen'],

            "destino" => $data['destino'],

            "fecha" => $data['fecha_programada']

        ];
    }


    public function getHojaTraslado($idTraslado)
    {
        $sql = "

    SELECT

        t.*,

        ao.descripcion AS almacen_origen,
        ad.descripcion AS almacen_destino,

        p.razon_social AS proveedor

    FROM wms_traslados_unidades t

    INNER JOIN wms_almacenes ao
        ON ao.idalmacen = t.almacen_origenid

    INNER JOIN wms_almacenes ad
        ON ad.idalmacen = t.almacen_destinoid

    LEFT JOIN prv_cat_proveedores p
        ON p.id_proveedor = t.proveedorid

    WHERE t.idtraslado = $idTraslado

    LIMIT 1

    ";

        $traslado = $this->select($sql);

        if (empty($traslado)) {
            return [];
        }

        $sqlDetalle = "

    SELECT

        d.vin,

        i.descripcion AS modelo

    FROM wms_traslados_unidades_detalle d

    INNER JOIN wms_inventario i
        ON i.idinventario = d.inventarioid

    WHERE d.trasladoid = $idTraslado

    ";

        $detalle = $this->select_all($sqlDetalle);

        $sqlTrasladista = "

    SELECT *

    FROM wms_traslados_trasladistas

    WHERE trasladoid = $idTraslado

    LIMIT 1

    ";

        $trasladista = $this->select($sqlTrasladista);

        return [
            "traslado" => $traslado,
            "detalle" => $detalle,
            "trasladista" => $trasladista
        ];
    }

    /* =====================================================
       EDICIÓN DE TRASLADO (solo permitida en estado 1 -
       "Solicitud", antes de confirmar la salida)
    ===================================================== */

    public function getTrasladoParaEditar($idtraslado)
    {
        $sql = "
            SELECT
                t.*,
                ao.descripcion AS almacen_origen,
                ad.descripcion AS almacen_destino
            FROM wms_traslados_unidades t
            INNER JOIN wms_almacenes ao ON ao.idalmacen = t.almacen_origenid
            INNER JOIN wms_almacenes ad ON ad.idalmacen = t.almacen_destinoid
            WHERE t.idtraslado = ?
            LIMIT 1
        ";

        $traslado = $this->select($sql, [$idtraslado]);

        if (empty($traslado)) {
            return [];
        }

        // A diferencia de getHojaTraslado() (que solo trae vin/modelo para
        // el PDF), aquí se necesitan también vinid/inventarioid/almacenid
        // y si esa unidad entrega llave, para poder rearmar cada fila de
        // la tabla "Unidades a Trasladar" tal como quedó guardada.
        $sqlDetalle = "
            SELECT
                d.vinid,
                d.vin,
                d.inventarioid,
                i.descripcion AS unidad,
                ns.almacenid,
                a.descripcion AS almacen,
                CASE WHEN tl.iddetalle IS NULL THEN 0 ELSE 1 END AS entrega_llave,
                tl.tipo_llave
            FROM wms_traslados_unidades_detalle d
            INNER JOIN wms_inventario i ON i.idinventario = d.inventarioid
            LEFT JOIN wms_numeros_series ns ON ns.id_numeros_serie = d.vinid
            LEFT JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
            LEFT JOIN wms_traslados_llaves tl ON tl.iddetalle = d.iddetalle
            WHERE d.trasladoid = ?
        ";

        $detalle = $this->select_all($sqlDetalle, [$idtraslado]);

        $sqlTrasladista = "SELECT * FROM wms_traslados_trasladistas WHERE trasladoid = ? LIMIT 1";

        $trasladista = $this->select($sqlTrasladista, [$idtraslado]);

        return [
            "traslado" => $traslado,
            "detalle" => $detalle,
            "trasladista" => $trasladista
        ];
    }

    public function updateTraslado(
        $idtraslado,
        $almacen_origenid,
        $almacen_destinoid,
        $tipo_traslado,
        $proveedorid,
        $fecha_programada,
        $observaciones,
        $usuarioid,
        $unidades,
        $nombre_trasladista,
        $contacto_trasladista,
        $numero_licencia,
        $vigencia_licencia,
        $archivoLicencia
    ) {
        $pdo = $this->getConexion();

        try {

            $pdo->beginTransaction();

            // Se bloquea el renglón mientras se decide si todavía se puede
            // editar: si alguien más confirmó la salida justo en este
            // instante, esta consulta espera y luego respeta ese estado.
            $traslado = $this->select(
                "SELECT * FROM wms_traslados_unidades WHERE idtraslado = ? FOR UPDATE",
                [$idtraslado]
            );

            if (empty($traslado)) {
                throw new Exception("El traslado no existe");
            }

            if ((int)$traslado['estado'] !== 1) {
                throw new Exception("Este traslado ya no se puede editar (la salida ya fue registrada, o está recibido/cancelado)");
            }

            $this->update(
                "UPDATE wms_traslados_unidades SET
                    almacen_origenid = ?,
                    almacen_destinoid = ?,
                    tipo_traslado = ?,
                    proveedorid = ?,
                    fecha_programada = ?,
                    observaciones = ?
                 WHERE idtraslado = ?",
                [
                    $almacen_origenid,
                    $almacen_destinoid,
                    $tipo_traslado,
                    $proveedorid,
                    $fecha_programada,
                    $observaciones,
                    $idtraslado
                ]
            );

            if (!empty($archivoLicencia)) {
                $this->update(
                    "UPDATE wms_traslados_trasladistas SET
                        nombre = ?, contacto = ?, numero_licencia = ?, vigencia_licencia = ?, archivo_licencia = ?
                     WHERE trasladoid = ?",
                    [
                        $nombre_trasladista,
                        $contacto_trasladista,
                        $numero_licencia,
                        $vigencia_licencia,
                        $archivoLicencia,
                        $idtraslado
                    ]
                );
            } else {
                $this->update(
                    "UPDATE wms_traslados_trasladistas SET
                        nombre = ?, contacto = ?, numero_licencia = ?, vigencia_licencia = ?
                     WHERE trasladoid = ?",
                    [
                        $nombre_trasladista,
                        $contacto_trasladista,
                        $numero_licencia,
                        $vigencia_licencia,
                        $idtraslado
                    ]
                );
            }

            // Unidades: como el traslado sigue en estado 1, todavía no
            // existe salida ni movimiento de llave para ninguna de ellas
            // (eso solo se genera al confirmar la salida), así que es
            // seguro reemplazar por completo el detalle guardado por el
            // que se acaba de capturar en el formulario.
            $detalleExistente = $this->select_all(
                "SELECT iddetalle FROM wms_traslados_unidades_detalle WHERE trasladoid = ?",
                [$idtraslado]
            );

            // OJO: a diferencia de select()/select_all()/update(), el delete()
            // de este framework NO acepta parámetros preparados (solo recibe
            // el query ya armado), así que aquí los IDs se interpolan ya
            // convertidos a entero con (int) — no vienen de texto libre del
            // usuario, así que es seguro.
            foreach ($detalleExistente as $d) {
                $iddetalle = (int) $d['iddetalle'];

                $this->delete(
                    "DELETE FROM wms_traslados_llaves WHERE iddetalle = $iddetalle"
                );
            }

            $idtrasladoInt = (int) $idtraslado;

            $this->delete(
                "DELETE FROM wms_traslados_unidades_detalle WHERE trasladoid = $idtrasladoInt"
            );

            $vins = [];

            foreach ($unidades as $unidad) {

                if ($this->validarUnidadEnTraslado($unidad['vinid'], $idtraslado)) {
                    throw new Exception("La unidad " . $unidad['vin'] . " ya tiene un traslado pendiente");
                }

                if (in_array($unidad['vin'], $vins)) {
                    throw new Exception("El VIN " . $unidad['vin'] . " está repetido");
                }

                $vins[] = $unidad['vin'];

                $iddetalle = $this->insert(
                    "INSERT INTO wms_traslados_unidades_detalle
                     (trasladoid, vinid, inventarioid, vin)
                     VALUES (?,?,?,?)",
                    [$idtraslado, $unidad['vinid'], $unidad['inventarioid'], $unidad['vin']]
                );

                if (!empty($unidad['entrega_llave'])) {

                    $llaveid = $this->getOrCreateLlave(
                        $unidad['vinid'],
                        $unidad['inventarioid'],
                        $unidad['tipo_llave'],
                        $almacen_origenid
                    );

                    $this->insert(
                        "INSERT INTO wms_traslados_llaves
                         (trasladoid, iddetalle, llaveid, tipo_llave, entrega_llave, estado_llave)
                         VALUES (?,?,?,?,1,1)",
                        [$idtraslado, $iddetalle, $llaveid, $unidad['tipo_llave']]
                    );
                }
            }

            $pdo->commit();

            return true;
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ["error" => $e->getMessage()];
        }
    }

    /* =====================================================
   INVENTARIO DE LLAVES
===================================================== */

    // Alta implícita: si el VIN no tiene todavía registrada esa llave, se crea
    public function getOrCreateLlave($vinid, $inventarioid, $tipo_llave, $almacenid)
    {
        $sql = "
        SELECT idllave, estado
        FROM wms_llaves_inventario
        WHERE vinid = ? AND tipo_llave = ?
        LIMIT 1
    ";

        $existe = $this->select($sql, [$vinid, $tipo_llave]);

        if (!empty($existe)) {

            // La llave ya está en tránsito/prestada en otro movimiento activo:
            // no se puede reutilizar para un traslado nuevo hasta que sea devuelta.
            if ((int)$existe['estado'] !== 1) {
                throw new Exception(
                    "La llave (" . $tipo_llave . ") de esta unidad ya se encuentra entregada/en tránsito en otro movimiento y no ha sido devuelta"
                );
            }

            return $existe['idllave'];
        }

        return $this->insert(
            "
        INSERT INTO wms_llaves_inventario
        (vinid, inventarioid, tipo_llave, almacenid, estado)
        VALUES (?,?,?,?,1)
        ",
            [$vinid, $inventarioid, $tipo_llave, $almacenid]
        );
    }

    private function registrarMovimientoLlave(
        $llaveid,
        $trasladoid,
        $tipo_accion,
        $almacen_origenid,
        $almacen_destinoid,
        $responsable,
        $usuarioid,
        $observaciones = null
    ) {
        $this->insert(
            "
        INSERT INTO wms_llaves_movimientos
        (llaveid, tipo_movimiento, referenciaid, tipo_accion,
         almacen_origenid, almacen_destinoid, responsable, usuarioid, observaciones)
        VALUES (?, 'traslado', ?, ?, ?, ?, ?, ?, ?)
        ",
            [$llaveid, $trasladoid, $tipo_accion, $almacen_origenid, $almacen_destinoid, $responsable, $usuarioid, $observaciones]
        );
    }

    /* =====================================================
   CONFIRMAR SALIDA (Solicitado -> En tránsito)
===================================================== */

    public function confirmarSalidaTraslado($idtraslado, $usuarioid)
    {
        try {

            $traslado = $this->select(
                "SELECT almacen_origenid, almacen_destinoid, estado
             FROM wms_traslados_unidades WHERE idtraslado = ?",
                [$idtraslado]
            );

            if (empty($traslado) || $traslado['estado'] != 1) {
                throw new Exception("El traslado no está en estado 'Solicitado'");
            }

            $this->update(
                "UPDATE wms_traslados_unidades SET estado = 2 WHERE idtraslado = ?",
                [$idtraslado]
            );

            $llaves = $this->select_all(
                "SELECT tl.idllave_traslado, tl.llaveid, tt.nombre AS responsable
     FROM wms_traslados_llaves tl
     INNER JOIN wms_traslados_trasladistas tt ON tt.trasladoid = tl.trasladoid
     WHERE tl.trasladoid = ? AND tl.entrega_llave = 1",
                [$idtraslado]
            );

            foreach ($llaves as $llave) {

                $this->update(
                    "UPDATE wms_traslados_llaves
                 SET estado_llave = 2, fecha_entrega = NOW()
                 WHERE idllave_traslado = ?",
                    [$llave['idllave_traslado']]
                );

                $this->update(
                    "UPDATE wms_llaves_inventario SET estado = 3 WHERE idllave = ?",
                    [$llave['llaveid']]
                );

                $this->registrarMovimientoLlave(
                    $llave['llaveid'],
                    $idtraslado,
                    'salida',
                    $traslado['almacen_origenid'],
                    $traslado['almacen_destinoid'],
                    $llave['responsable'],
                    $usuarioid
                );
            }

            return true;
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    /* =====================================================
   RECEPCIÓN (parcial por unidad)
===================================================== */

    public function getDetalleRecepcion($idtraslado)
    {
        $sql = "
        SELECT
            d.iddetalle,
            d.vin,
            d.estado_recepcion,
            i.descripcion AS modelo,
            tl.idllave_traslado,
            tl.llaveid,
            tl.tipo_llave,
            tl.entrega_llave,
            tl.estado_llave
        FROM wms_traslados_unidades_detalle d
        INNER JOIN wms_inventario i ON i.idinventario = d.inventarioid
        LEFT JOIN wms_traslados_llaves tl ON tl.iddetalle = d.iddetalle
        WHERE d.trasladoid = ?
    ";

        return $this->select_all($sql, [$idtraslado]);
    }

    public function setRecepcionUnidad(
        $idtraslado,
        $iddetalle,
        $llaveRecibida,
        $responsable,
        $usuarioid,
        $observaciones = null
    ) {
        try {

            $traslado = $this->select(
                "SELECT almacen_origenid, almacen_destinoid FROM wms_traslados_unidades WHERE idtraslado = ?",
                [$idtraslado]
            );

            $this->update(
                "UPDATE wms_traslados_unidades_detalle
             SET estado_recepcion = 2, responsable_recepcion = ?, fecha_recepcion = NOW()
             WHERE iddetalle = ?",
                [$responsable, $iddetalle]
            );

            $llave = $this->select(
                "SELECT idllave_traslado, llaveid FROM wms_traslados_llaves
             WHERE iddetalle = ? AND entrega_llave = 1",
                [$iddetalle]
            );

            if (!empty($llave) && $llaveRecibida) {

                $this->update(
                    "UPDATE wms_traslados_llaves
                 SET estado_llave = 3, responsable_recepcion = ?, fecha_recepcion = NOW(), observaciones_recepcion = ?
                 WHERE idllave_traslado = ?",
                    [$responsable, $observaciones, $llave['idllave_traslado']]
                );

                $this->update(
                    "UPDATE wms_llaves_inventario SET estado = 1, almacenid = ? WHERE idllave = ?",
                    [$traslado['almacen_destinoid'], $llave['llaveid']]
                );

                $this->registrarMovimientoLlave(
                    $llave['llaveid'],
                    $idtraslado,
                    'recepcion',
                    $traslado['almacen_origenid'],
                    $traslado['almacen_destinoid'],
                    $responsable,
                    $usuarioid,
                    $observaciones
                );
            }

            // ¿Ya se recibieron todas las unidades del traslado?
            $pendientes = $this->select(
                "SELECT COUNT(*) total FROM wms_traslados_unidades_detalle
             WHERE trasladoid = ? AND estado_recepcion = 1",
                [$idtraslado]
            );

            if ($pendientes['total'] == 0) {
                // Estado 4 = Recibido (mismo estado final que usa el flujo de
                // Inv_operaciones_traslados::registrarRecepcion), para que el
                // traslado quede consistente sin importar por qué flujo se cerró.
                $this->update(
                    "UPDATE wms_traslados_unidades SET estado = 4 WHERE idtraslado = ?",
                    [$idtraslado]
                );
            }

            return true;
        } catch (Exception $e) {
            return ["error" => $e->getMessage()];
        }
    }

    // KPIs
public function getKpisTraslados()
{
    $sql = "
        SELECT
            SUM(estado = 1) AS pendientes,
            SUM(estado IN (2,3)) AS transito,
            SUM(estado = 4) AS recibidas,
            SUM(estado = 5) AS canceladas
        FROM wms_traslados_unidades
    ";
    return $this->select($sql);
}


}
