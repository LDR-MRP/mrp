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

    public function selectAlmacenes()
    {
        $sql = "
            SELECT 
                idalmacen,
                descripcion
            FROM wms_almacenes
            WHERE estado = 2
            ORDER BY descripcion
        ";

        return $this->select_all($sql);
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


            $folio = 'TRU-'
                . date('YmdHis')
                . '-'
                . rand(100, 999);



            $idtraslado = $this->insert(
                "
            INSERT INTO wms_traslados_unidades
            (
                folio,
                almacen_origenid,
                almacen_destinoid,
                tipo_traslado,
                proveedorid,
                fecha_programada,
                observaciones,
                usuarioid,
                estado
            )
            VALUES
            (?,?,?,?,?,?,?,?,1)
            ",
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
                "
    INSERT INTO wms_traslados_trasladistas
    (
        trasladoid,
        nombre,
        contacto,
        numero_licencia,
        vigencia_licencia,
        archivo_licencia
    )
    VALUES
    (?,?,?,?,?,?)
    ",
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

                $enTraslado = $this->validarUnidadEnTraslado(
                    $unidad['vinid']
                );

                if ($enTraslado) {
                    throw new Exception(
                        "La unidad " . $unidad['vin'] . " ya tiene un traslado pendiente"
                    );
                }

                if (in_array($unidad['vin'], $vins)) {
                    throw new Exception(
                        "El VIN " . $unidad['vin'] . " está repetido"
                    );
                }

                $vins[] = $unidad['vin'];

                $this->insert(
                    "
        INSERT INTO wms_traslados_unidades_detalle
        (
            trasladoid,
            vinid,
            inventarioid,
            vin
        )
        VALUES
        (?,?,?,?)
        ",
                    [
                        $idtraslado,
                        $unidad['vinid'],
                        $unidad['inventarioid'],
                        $unidad['vin']
                    ]
                );
            }



            return true;
        } catch (Exception $e) {


            return [
                "error" => $e->getMessage()
            ];
        }
    }

    public function validarUnidadEnTraslado($vinid)
    {


        $sql = "

SELECT

COUNT(*) total


FROM wms_traslados_unidades_detalle d


INNER JOIN wms_traslados_unidades t

ON t.idtraslado = d.trasladoid


WHERE d.vinid = $vinid


AND t.estado IN (1,4)


";


        $result = $this->select($sql);


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


AND t.estado IN (1,4)


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
}
