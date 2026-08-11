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

        d.vinid,
        d.vin,

        i.descripcion AS modelo

    FROM wms_traslados_unidades_detalle d

    INNER JOIN wms_inventario i
        ON i.idinventario = d.inventarioid

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


        try {


            /*
 Buscar traslado
*/

            $sql = "
SELECT *
FROM wms_traslados_unidades
WHERE folio='$folio'
";


            $traslado = $this->select($sql);


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



            return [
                "status" => true,
                "msg" => "Salida registrada"
            ];
        } catch (Exception $e) {

            return [
                "status" => false,
                "msg" => $e->getMessage()
            ];
        }
    }

    public function registrarRecepcion($folio, $usuario)
    {


        try {


            /*
        Buscar traslado
        */

            $sql = "
        SELECT *
        FROM wms_traslados_unidades
        WHERE folio='$folio'
        ";


            $traslado = $this->select($sql);



            if (!$traslado) {

                throw new Exception(
                    "Traslado no encontrado"
                );
            }



            /*
        Validar estado correcto
        */

            if ($traslado['estado'] != 2) {


                throw new Exception(
                    "El traslado no está pendiente de recepción"
                );
            }



            $idtraslado = $traslado['idtraslado'];


            $almacenDestino =
                $traslado['almacen_destinoid'];



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
            Buscar existencia destino
            */


                $sql = "
            SELECT existencia
            FROM wms_multialmacen
            WHERE inventarioid={$u['inventarioid']}
            AND almacenid=$almacenDestino
            ";


                $exist = $this->select($sql);



                if ($exist) {


                    $nuevaExistencia =
                        $exist['existencia'] + 1;



                    $this->update(
                        "
                    UPDATE wms_multialmacen
                    SET existencia = existencia + 1
                    WHERE inventarioid=?
                    AND almacenid=?
                    ",
                        [
                            $u['inventarioid'],
                            $almacenDestino
                        ]
                    );
                } else {


                    $nuevaExistencia = 1;



                    $this->insert(
                        "
                    INSERT INTO wms_multialmacen
                    (
                        inventarioid,
                        almacenid,
                        existencia
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?
                    )
                    ",
                        [
                            $u['inventarioid'],
                            $almacenDestino,
                            1
                        ]
                    );
                }




                /*
            Movimiento entrada
            */


                $movimiento =
                    "MOV-" . date("YmdHis") . "-" . rand(100, 999);


                $resultMovimiento = $this->insert(

                    "
INSERT INTO wms_movimientos_inventario
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
)
",

                    [
                        $u['inventarioid'],
                        $almacenDestino,
                        $movimiento,
                        7, // ✅ Entrada x traspaso
                        $folio,
                        1,
                        0,
                        $nuevaExistencia,
                        1,
                        2
                    ]

                );


                if (!$resultMovimiento) {

                    throw new Exception(
                        "Error registrando movimiento de entrada"
                    );
                }
            }




            /*
        Actualizar traslado
        */


            $this->update(

                "
        UPDATE wms_traslados_unidades

        SET

            estado=4,
            fecha_recepcion=NOW(),
            usuario_recepcion=?

        WHERE idtraslado=?

        ",

                [

                    $usuario,
                    $idtraslado

                ]
            );





            return [

                "status" => true,
                "msg" => "Recepción registrada correctamente"

            ];
        } catch (Exception $e) {


            return [

                "status" => false,
                "msg" => $e->getMessage()

            ];
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
