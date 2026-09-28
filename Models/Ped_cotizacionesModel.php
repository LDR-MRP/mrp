<?php


class Ped_cotizacionesModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }


    /* ============================================================
       LISTADO DE COTIZACIONES
    ============================================================ */

    public function selectCotizaciones(
        array $filtros = []
    ) {

        $buscar =
            trim(
                $filtros['buscar']
                ?? ""
            );


        $desde =
            trim(
                $filtros['desde']
                ?? ""
            );


        $hasta =
            trim(
                $filtros['hasta']
                ?? ""
            );


        $tipo =
            strtoupper(
                trim(
                    $filtros['tipo']
                    ?? ""
                )
            );


        $estatus =
            strtoupper(
                trim(
                    $filtros['estatus']
                    ?? ""
                )
            );


        $sql = "
            SELECT

                c.idcotizacion,

                c.idcliente,

                c.idusuario_acceso,

                c.idpedido_origen,

                c.idpedido_generado,

                c.folio_cotizacion,

                c.clave,

                c.tipo_cotizacion,

                c.version_actual,

                c.fecha_cotizacion,

                c.fecha_vigencia,

                c.moneda,

                c.subtotal,

                c.descuento,

                c.iva,

                c.total,

                c.estatus,

                c.fecha_ultimo_envio,

                c.fecha_respuesta,

                c.fecha_aceptacion,

                c.fecha_rechazo,

                c.estado,

                c.fecha_creacion,


                cli.codigo_cliente,

                cli.razon_social,

                cli.nombre_comercial,

                cli.correo AS correo_cliente,


                ua.nombre_usuario,

                ua.nombre AS nombre_usuario_acceso,

                ua.apellido AS apellido_usuario_acceso,

                ua.correo AS correo_usuario_acceso,


                p.folio_pedido,

                p.clave AS clave_pedido,

                p.estatus AS estatus_pedido,


                COALESCE(
                    (
                        SELECT
                            SUM(
                                vd.cantidad
                            )

                        FROM
                            ped_cotizaciones_versiones v

                        INNER JOIN
                            ped_cotizaciones_versiones_detalle vd
                                ON vd.idversion =
                                   v.idversion

                        WHERE
                            v.idcotizacion =
                                c.idcotizacion

                            AND v.numero_version =
                                c.version_actual

                            AND v.estado = 2

                            AND vd.estado = 2
                    ),
                    0
                ) AS total_unidades


            FROM
                ped_cotizaciones c


            INNER JOIN
                cli_clientes cli
                    ON cli.idcliente =
                       c.idcliente


            LEFT JOIN
                cli_usuarios_acceso ua
                    ON ua.idusuario_acceso =
                       c.idusuario_acceso


            LEFT JOIN
                ped_pedidos p
                    ON p.idpedido =
                       c.idpedido_origen


            WHERE
                c.estado = 2
        ";


        $arrData = [];


        /* ========================================================
           BUSCADOR
        ======================================================== */

        if (
            $buscar !== ""
        ) {

            $sql .= "
                AND (
                    c.folio_cotizacion LIKE ?
                    OR c.clave LIKE ?
                    OR cli.codigo_cliente LIKE ?
                    OR cli.razon_social LIKE ?
                    OR cli.nombre_comercial LIKE ?
                    OR p.folio_pedido LIKE ?
                )
            ";


            $termino =
                "%"
                . $buscar
                . "%";


            $arrData[] =
                $termino;

            $arrData[] =
                $termino;

            $arrData[] =
                $termino;

            $arrData[] =
                $termino;

            $arrData[] =
                $termino;

            $arrData[] =
                $termino;
        }


        /* ========================================================
           FECHA DESDE
        ======================================================== */

        if (
            $desde !== ""
        ) {

            $sql .= "
                AND DATE(
                    c.fecha_cotizacion
                ) >= ?
            ";


            $arrData[] =
                $desde;
        }


        /* ========================================================
           FECHA HASTA
        ======================================================== */

        if (
            $hasta !== ""
        ) {

            $sql .= "
                AND DATE(
                    c.fecha_cotizacion
                ) <= ?
            ";


            $arrData[] =
                $hasta;
        }


        /* ========================================================
           TIPO
        ======================================================== */

        if (
            in_array(
                $tipo,
                [
                    'DIRECTA',
                    'PEDIDO'
                ],
                true
            )
        ) {

            $sql .= "
                AND c.tipo_cotizacion = ?
            ";


            $arrData[] =
                $tipo;
        }


        /* ========================================================
           ESTATUS
        ======================================================== */

        $estatusPermitidos = [

            'BORRADOR',

            'PENDIENTE_RESPUESTA',

            'EN_AJUSTE',

            'ACEPTADA',

            'RECHAZADA',

            'VENCIDA',

            'CANCELADA'

        ];


        if (
            in_array(
                $estatus,
                $estatusPermitidos,
                true
            )
        ) {

            $sql .= "
                AND c.estatus = ?
            ";


            $arrData[] =
                $estatus;
        }


        /* ========================================================
           ORDEN
        ======================================================== */

        $sql .= "
            ORDER BY
                c.fecha_cotizacion DESC,
                c.idcotizacion DESC
        ";


        return $this->select_all(
            $sql,
            $arrData
        );
    }


    /* ============================================================
       DASHBOARD
    ============================================================ */

    public function selectDashboardCotizaciones()
    {
        $sql = "
            SELECT

                COUNT(*) AS total_cotizaciones,


                SUM(
                    CASE

                        WHEN estatus =
                            'PENDIENTE_RESPUESTA'

                        THEN 1

                        ELSE 0

                    END
                ) AS pendientes,


                SUM(
                    CASE

                        WHEN estatus =
                            'EN_AJUSTE'

                        THEN 1

                        ELSE 0

                    END
                ) AS en_ajuste,


                SUM(
                    CASE

                        WHEN estatus =
                            'ACEPTADA'

                        THEN 1

                        ELSE 0

                    END
                ) AS aceptadas,


                SUM(
                    CASE

                        WHEN estatus =
                            'RECHAZADA'

                        THEN 1

                        ELSE 0

                    END
                ) AS rechazadas,


                COALESCE(
                    SUM(
                        CASE

                            WHEN estatus NOT IN (
                                'CANCELADA'
                            )

                            THEN total

                            ELSE 0

                        END
                    ),
                    0
                ) AS valor_cotizado,


                COALESCE(
                    SUM(
                        CASE

                            WHEN estatus =
                                'ACEPTADA'

                            THEN total

                            ELSE 0

                        END
                    ),
                    0
                ) AS valor_aceptado


            FROM
                ped_cotizaciones


            WHERE
                estado = 2
        ";


        $request =
            $this->select(
                $sql
            );


        if (
            empty(
                $request
            )
        ) {

            return [

                'total_cotizaciones' =>
                    0,

                'pendientes' =>
                    0,

                'en_ajuste' =>
                    0,

                'aceptadas' =>
                    0,

                'rechazadas' =>
                    0,

                'valor_cotizado' =>
                    0,

                'valor_aceptado' =>
                    0

            ];
        }


        return [

            'total_cotizaciones' =>
                (int) (
                    $request[
                        'total_cotizaciones'
                    ]
                    ?? 0
                ),

            'pendientes' =>
                (int) (
                    $request[
                        'pendientes'
                    ]
                    ?? 0
                ),

            'en_ajuste' =>
                (int) (
                    $request[
                        'en_ajuste'
                    ]
                    ?? 0
                ),

            'aceptadas' =>
                (int) (
                    $request[
                        'aceptadas'
                    ]
                    ?? 0
                ),

            'rechazadas' =>
                (int) (
                    $request[
                        'rechazadas'
                    ]
                    ?? 0
                ),

            'valor_cotizado' =>
                (float) (
                    $request[
                        'valor_cotizado'
                    ]
                    ?? 0
                ),

            'valor_aceptado' =>
                (float) (
                    $request[
                        'valor_aceptado'
                    ]
                    ?? 0
                )

        ];
    }
}