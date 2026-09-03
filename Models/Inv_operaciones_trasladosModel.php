<?php

class Inv_operaciones_trasladosModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function getTrasladoOperacion($folio)
    {
        $sql = "

    SELECT

        t.idtraslado,
        t.folio,
        t.estado,

        ao.descripcion AS origen,
        ad.descripcion AS destino,

        t.fecha_programada

    FROM wms_traslados_unidades t

    INNER JOIN wms_almacenes ao
        ON ao.idalmacen = t.almacen_origenid

    INNER JOIN wms_almacenes ad
        ON ad.idalmacen = t.almacen_destinoid

    WHERE t.folio = '$folio'

    LIMIT 1

    ";

        $traslado = $this->select($sql);

        if (empty($traslado)) {

            return [
                "status" => false,
                "msg" => "Traslado no encontrado"
            ];
        }

        $idtraslado = $traslado['idtraslado'];

        $sqlDetalle = "
    SELECT
        d.iddetalle,
        d.vinid,
        d.vin,
        i.descripcion AS modelo,
        tl.estado_llave,
        tl.fecha_entrega,
        tl.fecha_recepcion,
        tl.entrega_llave,
        tl.tipo_llave
    FROM wms_traslados_unidades_detalle d
    INNER JOIN wms_inventario i ON i.idinventario = d.inventarioid
    LEFT JOIN wms_traslados_llaves tl ON tl.iddetalle = d.iddetalle
    WHERE d.trasladoid = $idtraslado
";

        $traslado['unidades'] =
            $this->select_all($sqlDetalle);

        return [
            "status" => true,
            "data" => $traslado
        ];
    }

    public function registrarSalida($folio, $usuario)
    {

        $pdo = $this->getConexion();

        try {

            // Bloquea el renglón del traslado durante toda la operación:
            // si llega una segunda petición (doble clic / doble envío) para
            // el mismo folio, se queda esperando aquí hasta que esta
            // transacción termine, y al reintentar ya verá estado != 1.
            $pdo->beginTransaction();

            /*
 Buscar traslado
*/

            $traslado = $this->select(
                "SELECT * FROM wms_traslados_unidades WHERE folio = ? FOR UPDATE",
                [$folio]
            );


            if (!$traslado) {

                throw new Exception(
                    "Traslado no encontrado"
                );
            }


            // Validar que solo se procese una vez
            if ($traslado['estado'] != 1) {

                throw new Exception(
                    "El traslado ya fue procesado"
                );
            }


            $idtraslado = $traslado['idtraslado'];

            $almacenOrigen =
                $traslado['almacen_origenid'];



            /*
 Obtener unidades
*/

            $sql = "
SELECT *
FROM wms_traslados_unidades_detalle
WHERE trasladoid=$idtraslado
";


            $unidades = $this->select_all($sql);



            foreach ($unidades as $u) {



                /*
 Existencia actual
*/

                $sql = "
SELECT existencia
FROM wms_multialmacen
WHERE inventarioid={$u['inventarioid']}
AND almacenid=$almacenOrigen
FOR UPDATE
";


                $exist = $this->select($sql);



                if (!$exist || $exist['existencia'] <= 0) {

                    throw new Exception(
                        "Sin existencia VIN " . $u['vin']
                    );
                }


                $nuevaExistencia = $exist['existencia'] - 1;



                /*
 Restar inventario
*/

                $this->update(
                    "UPDATE wms_multialmacen
     SET existencia = existencia - 1
     WHERE inventarioid = ?
     AND almacenid = ?",
                    [
                        $u['inventarioid'],
                        $almacenOrigen
                    ]
                );

                $llave = $this->select(
                    "SELECT idllave_traslado, llaveid
     FROM wms_traslados_llaves
     WHERE iddetalle = ? AND entrega_llave = 1",
                    [$u['iddetalle']]
                );

                if (!empty($llave)) {

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

                    $this->insert(
                        "INSERT INTO wms_llaves_movimientos
         (llaveid, tipo_movimiento, referenciaid, tipo_accion,
          almacen_origenid, almacen_destinoid, usuarioid)
         VALUES (?, 'traslado', ?, 'salida', ?, ?, ?)",
                        [$llave['llaveid'], $idtraslado, $almacenOrigen, $traslado['almacen_destinoid'], $usuario]
                    );
                }



                /*
 Movimiento salida
*/

                $movimiento =
                    "MOV-" . date("YmdHis") . "-" . rand(100, 999);



                $result = $this->insert(

                    "INSERT INTO wms_movimientos_inventario
    (
        inventarioid,
        almacenid,
        numero_movimiento,
        concepmovid,
        referencia,
        cantidad,
        costo_cantidad,
        existencia,
        signo,
        fecha_movimiento,
        estado
    )

    VALUES
    (
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        ?,
        NOW(),
        ?
    )",

                    [
                        $u['inventarioid'],
                        $almacenOrigen,
                        $movimiento,
                        15,
                        $folio,
                        1,
                        0,
                        $nuevaExistencia,
                        -1,
                        2
                    ]

                );

                if (!$result) {

                    throw new Exception(
                        "Error insert movimiento: " . $sql
                    );
                }
            }



            /*
 Actualizar traslado
*/

            $this->update(
                "UPDATE wms_traslados_unidades
     SET
        estado = 2,
        fecha_salida = NOW(),
        usuario_salida = ?
     WHERE idtraslado = ?",
                [
                    $usuario,
                    $idtraslado
                ]
            );



            $pdo->commit();

            return [
                "status" => true,
                "msg" => "Salida registrada"
            ];
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                "status" => false,
                "msg" => $e->getMessage()
            ];
        }
    }

    /* =====================================================
       REGISTRO DE INGRESO (Seguridad / caseta)

       Paso intermedio entre la salida (estado 2) y la recepción
       interna (estado 4). Únicamente confirma que la unidad llegó
       físicamente al almacén destino: NO mueve inventario ni toca
       las llaves, y NO cierra el traslado. La recepción formal
       (incluyendo la confirmación de la llave) la debe hacer
       después una persona interna mediante registrarRecepcion().
    ===================================================== */
    public function registrarIngresoUnidad($folio, $usuario)
    {
        $pdo = $this->getConexion();

        try {

            $pdo->beginTransaction();

            $traslado = $this->select(
                "SELECT * FROM wms_traslados_unidades WHERE folio = ? FOR UPDATE",
                [$folio]
            );

            if (!$traslado) {
                throw new Exception("Traslado no encontrado");
            }

            if ($traslado['estado'] != 2) {
                throw new Exception(
                    $traslado['estado'] == 1
                        ? "El traslado aún no registra salida"
                        : "El traslado ya no está pendiente de ingreso (probablemente ya fue recibido, cancelado, o el ingreso ya se había registrado)"
                );
            }

            $idtraslado = $traslado['idtraslado'];

            $this->update(
                "UPDATE wms_traslados_unidades SET estado = 3 WHERE idtraslado = ?",
                [$idtraslado]
            );

            $pdo->commit();

            return [
                "status" => true,
                "msg" => "Ingreso registrado. Falta la recepción interna para completar el traslado."
            ];
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return [
                "status" => false,
                "msg" => $e->getMessage()
            ];
        }
    }

    public function registrarRecepcion($folio, $usuario, $unidadesRecibidas)
    {
        $pdo = $this->getConexion();

        try {

            // Bloquea el renglón del traslado mientras dura toda la recepción.
            // Si el botón se manda dos veces (doble clic, doble tap en el
            // celular, reintento de red), la segunda petición se queda
            // esperando aquí y, al continuar, ya encuentra estado != 3 y
            // se rechaza sin volver a mover inventario ni llaves.
            $pdo->beginTransaction();

            $traslado = $this->select(
                "SELECT * FROM wms_traslados_unidades WHERE folio = ? FOR UPDATE",
                [$folio]
            );

            if (!$traslado) {
                throw new Exception("Traslado no encontrado");
            }

            if ($traslado['estado'] != 3) {
                throw new Exception(
                    $traslado['estado'] == 2
                        ? "Falta registrar el ingreso de la unidad (caseta/seguridad) antes de la recepción interna"
                        : "El traslado no está pendiente de recepción"
                );
            }

            $idtraslado = $traslado['idtraslado'];
            $almacenDestino = $traslado['almacen_destinoid'];

            // Mapa vin -> { recibida (bool), observaciones } que llegó del cliente
            $mapaRecibidos = [];
            foreach ($unidadesRecibidas as $r) {
                $mapaRecibidos[$r['vin']] = [
                    'recibida' => !empty($r['llave_recibida']),
                    'observaciones' => trim((string)($r['observaciones_llave'] ?? '')),
                ];
            }

            $sql = "SELECT * FROM wms_traslados_unidades_detalle WHERE trasladoid=$idtraslado";
            $unidades = $this->select_all($sql);

            $faltantes = 0;
            $llavesFaltantes = 0;

            foreach ($unidades as $u) {

                $fueRecibida = array_key_exists($u['vin'], $mapaRecibidos);

                if (!$fueRecibida) {

                    $this->update(
                        "UPDATE wms_traslados_unidades_detalle
                     SET estado_recepcion = 3
                     WHERE iddetalle = ?",
                        [$u['iddetalle']]
                    );

                    // La unidad no llegó: si tenía llave entregada en la salida,
                    // no la marcamos como devuelta (sigue fuera, en poder del
                    // trasladista), pero SÍ dejamos trazabilidad de la incidencia
                    // para que no quede "perdida" sin ningún registro.
                    $llaveFaltante = $this->select(
                        "SELECT idllave_traslado, llaveid FROM wms_traslados_llaves
                     WHERE iddetalle = ? AND entrega_llave = 1",
                        [$u['iddetalle']]
                    );

                    if (!empty($llaveFaltante)) {

                        $this->update(
                            "UPDATE wms_traslados_llaves
                         SET observaciones_recepcion = ?
                         WHERE idllave_traslado = ?",
                            [
                                'Unidad no recibida en destino: llave pendiente de localizar',
                                $llaveFaltante['idllave_traslado']
                            ]
                        );

                        $this->insert(
                            "INSERT INTO wms_llaves_movimientos
                         (llaveid, tipo_movimiento, referenciaid, tipo_accion,
                          almacen_origenid, almacen_destinoid, usuarioid, observaciones)
                         VALUES (?, 'traslado', ?, 'faltante', ?, ?, ?, ?)",
                            [
                                $llaveFaltante['llaveid'],
                                $idtraslado,
                                $traslado['almacen_origenid'],
                                $almacenDestino,
                                $usuario,
                                'Unidad no recibida en destino: llave pendiente de localizar'
                            ]
                        );
                    }

                    $faltantes++;
                    continue;
                }

                /* --- Inventario (igual que antes) --- */
                $sql = "SELECT existencia FROM wms_multialmacen
                    WHERE inventarioid={$u['inventarioid']} AND almacenid=$almacenDestino";
                $exist = $this->select($sql);

                $nuevaExistencia = $exist ? $exist['existencia'] + 1 : 1;

                if ($exist) {
                    $this->update(
                        "UPDATE wms_multialmacen SET existencia = existencia + 1
                     WHERE inventarioid=? AND almacenid=?",
                        [$u['inventarioid'], $almacenDestino]
                    );
                } else {
                    $this->insert(
                        "INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia) VALUES (?,?,?)",
                        [$u['inventarioid'], $almacenDestino, 1]
                    );
                }

                $movimiento = "MOV-" . date("YmdHis") . "-" . rand(100, 999);

                $this->insert(
                    "INSERT INTO wms_movimientos_inventario
                 (inventarioid, almacenid, numero_movimiento, concepmovid, referencia,
                  cantidad, costo_cantidad, existencia, signo, fecha_movimiento, estado)
                 VALUES (?,?,?,?,?,?,?,?,?,NOW(),?)",
                    [$u['inventarioid'], $almacenDestino, $movimiento, 7, $folio, 1, 0, $nuevaExistencia, 1, 2]
                );

                $this->update(
                    "UPDATE wms_traslados_unidades_detalle
                 SET estado_recepcion = 2, responsable_recepcion = ?, fecha_recepcion = NOW()
                 WHERE iddetalle = ?",
                    [$usuario, $u['iddetalle']]
                );

                /* --- Llave --- */
                $llave = $this->select(
                    "SELECT idllave_traslado, llaveid FROM wms_traslados_llaves
                 WHERE iddetalle = ? AND entrega_llave = 1",
                    [$u['iddetalle']]
                );

                $infoLlave = $mapaRecibidos[$u['vin']] ?? ['recibida' => false, 'observaciones' => ''];

                if (!empty($llave) && $infoLlave['recibida']) {

                    $this->update(
                        "UPDATE wms_traslados_llaves
                     SET estado_llave = 3, responsable_recepcion = ?, fecha_recepcion = NOW()
                     WHERE idllave_traslado = ?",
                        [$usuario, $llave['idllave_traslado']]
                    );

                    $this->update(
                        "UPDATE wms_llaves_inventario SET estado = 1, almacenid = ? WHERE idllave = ?",
                        [$almacenDestino, $llave['llaveid']]
                    );

                    $this->insert(
                        "INSERT INTO wms_llaves_movimientos
                     (llaveid, tipo_movimiento, referenciaid, tipo_accion,
                      almacen_origenid, almacen_destinoid, usuarioid)
                     VALUES (?, 'traslado', ?, 'recepcion', ?, ?, ?)",
                        [$llave['llaveid'], $idtraslado, $traslado['almacen_origenid'], $almacenDestino, $usuario]
                    );
                } elseif (!empty($llave)) {

                    // La unidad SÍ llegó pero la llave no se confirmó recibida:
                    // antes esto se quedaba en silencio (estado_llave=2 y
                    // wms_llaves_inventario.estado=3 sin ningún registro nuevo),
                    // por lo que en la bitácora de llaves solo se veía "En
                    // Tránsito" para siempre, sin explicar por qué ni desde
                    // cuándo. Ahora se deja trazabilidad explícita, igual que
                    // en el caso de "unidad completa no recibida".
                    $observacion = $infoLlave['observaciones'] !== ''
                        ? $infoLlave['observaciones']
                        : 'Llave no recibida junto con la unidad en la recepción interna';

                    $this->update(
                        "UPDATE wms_traslados_llaves
                     SET observaciones_recepcion = ?
                     WHERE idllave_traslado = ?",
                        [$observacion, $llave['idllave_traslado']]
                    );

                    $this->insert(
                        "INSERT INTO wms_llaves_movimientos
                     (llaveid, tipo_movimiento, referenciaid, tipo_accion,
                      almacen_origenid, almacen_destinoid, usuarioid, observaciones)
                     VALUES (?, 'traslado', ?, 'faltante', ?, ?, ?, ?)",
                        [$llave['llaveid'], $idtraslado, $traslado['almacen_origenid'], $almacenDestino, $usuario, $observacion]
                    );

                    $llavesFaltantes++;
                }
                // Si no tenía llave asignada en este traslado, no hay nada que
                // registrar en wms_llaves_movimientos para esta unidad.
            }

            $this->update(
                "UPDATE wms_traslados_unidades
             SET estado = 4, fecha_recepcion = NOW(), usuario_recepcion = ?
             WHERE idtraslado = ?",
                [$usuario, $idtraslado]
            );

            $pdo->commit();

            $avisos = [];
            if ($faltantes > 0) {
                $avisos[] = "$faltantes unidad(es) faltante(s)";
            }
            if ($llavesFaltantes > 0) {
                $avisos[] = "$llavesFaltantes llave(s) no recibida(s)";
            }

            return [
                "status" => true,
                "msg" => !empty($avisos)
                    ? "Recepción registrada con " . implode(" y ", $avisos)
                    : "Recepción registrada correctamente",
                "faltantes" => $faltantes,
                "llaves_faltantes" => $llavesFaltantes
            ];
        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            return ["status" => false, "msg" => $e->getMessage()];
        }
    }

    public function registrarUnidadAnomala($folio, $vin, $usuario)
    {
        try {

            $folio = trim($folio);
            $vin   = trim($vin);

            /* Buscar traslado */
            $sql = "
            SELECT *
            FROM wms_traslados_unidades
            WHERE folio = '$folio'
            LIMIT 1
        ";

            $traslado = $this->select($sql);

            if (!$traslado) {
                throw new Exception("Traslado no encontrado");
            }

            // Solo aplica en fase de recepción (ya tuvo salida / está en tránsito)
            if (!in_array($traslado['estado'], [2, 3])) {
                throw new Exception("El traslado no está en fase de recepción");
            }

            $idtraslado     = $traslado['idtraslado'];
            $almacenDestino = $traslado['almacen_destinoid'];

            /* Verificar que el VIN NO pertenezca ya a este traslado */
            $sql = "
            SELECT COUNT(*) total
            FROM wms_traslados_unidades_detalle
            WHERE trasladoid = $idtraslado
            AND vin = '$vin'
        ";

            $pertenece = $this->select($sql);

            if ($pertenece && $pertenece['total'] > 0) {
                throw new Exception("El VIN sí pertenece a este traslado, valídelo normalmente");
            }

            /* Evitar registrar la misma anomalía dos veces */
            $sql = "
            SELECT COUNT(*) total
            FROM wms_traslados_anomalias
            WHERE trasladoid = $idtraslado
            AND vin = '$vin'
        ";

            $yaRegistrada = $this->select($sql);

            if ($yaRegistrada && $yaRegistrada['total'] > 0) {
                return [
                    "status" => true,
                    "msg" => "Esta unidad ya había sido registrada como alerta",
                    "duplicado" => true
                ];
            }

            /* Buscar datos del VIN en el sistema */
            $sql = "
            SELECT
                ns.id_numeros_serie AS vinid,
                ns.inventarioid,
                i.descripcion AS modelo
            FROM wms_numeros_series ns
            INNER JOIN wms_inventario i
                ON i.idinventario = ns.inventarioid
            WHERE ns.numero_serie = '$vin'
            LIMIT 1
        ";

            $unidad = $this->select($sql);

            if (!$unidad) {
                throw new Exception("El VIN escaneado no existe en el sistema");
            }

            $inventarioid = $unidad['inventarioid'];

            /* Sumar existencia en almacén destino */
            $sql = "
            SELECT existencia
            FROM wms_multialmacen
            WHERE inventarioid = $inventarioid
            AND almacenid = $almacenDestino
        ";

            $exist = $this->select($sql);

            if ($exist) {

                $nuevaExistencia = $exist['existencia'] + 1;

                $this->update(
                    "UPDATE wms_multialmacen
                 SET existencia = existencia + 1
                 WHERE inventarioid = ?
                 AND almacenid = ?",
                    [$inventarioid, $almacenDestino]
                );
            } else {

                $nuevaExistencia = 1;

                $this->insert(
                    "INSERT INTO wms_multialmacen
                (inventarioid, almacenid, existencia)
                VALUES (?, ?, ?)",
                    [$inventarioid, $almacenDestino, 1]
                );
            }

            /* Movimiento de entrada, marcado como anómalo en la referencia */
            $movimiento = "MOV-" . date("YmdHis") . "-" . rand(100, 999);

            $this->insert(
                "INSERT INTO wms_movimientos_inventario
            (
                inventarioid,
                almacenid,
                numero_movimiento,
                concepmovid,
                referencia,
                cantidad,
                costo_cantidad,
                existencia,
                signo,
                fecha_movimiento,
                estado
            )
            VALUES (?,?,?,?,?,?,?,?,?,NOW(),?)",
                [
                    $inventarioid,
                    $almacenDestino,
                    $movimiento,
                    7,
                    $folio . '-ALERTA',
                    1,
                    0,
                    $nuevaExistencia,
                    1,
                    2
                ]
            );

            /* Registrar anomalía para revisión */
            $this->insert(
                "INSERT INTO wms_traslados_anomalias
            (
                trasladoid,
                folio,
                vin,
                vinid,
                inventarioid,
                almacen_destinoid,
                tipo,
                usuario_id,
                fecha,
                atendido
            )
            VALUES (?,?,?,?,?,?,?,?,NOW(),0)",
                [
                    $idtraslado,
                    $folio,
                    $vin,
                    $unidad['vinid'],
                    $inventarioid,
                    $almacenDestino,
                    'VIN_NO_PERTENECE',
                    $usuario
                ]
            );

            return [
                "status" => true,
                "msg" => "Unidad registrada con alerta: no pertenece a este traslado",
                "modelo" => $unidad['modelo']
            ];
        } catch (Exception $e) {
            return [
                "status" => false,
                "msg" => $e->getMessage()
            ];
        }
    }
}
