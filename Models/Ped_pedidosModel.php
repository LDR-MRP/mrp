<?php

require_once __DIR__ . '/../Services/PedPedidosZonaHoraria.php';
class Ped_pedidosModel extends Mysql
{
    use PedPedidosZonaHoraria;


    public function __construct()
    {
        parent::__construct();
        $this->configurarZonaHorariaPedidos();
    }


      public function selectPedidos(array $filtros = []) {

        /*
         * ========================================================
         * FILTROS
         * ========================================================
         */

        $condiciones =$this->construirFiltrosPedidos($filtros);
        $where =$condiciones['where'];
        $parametros =$condiciones['parametros'];

        /*
         * ========================================================
         * PAGINACIÓN
         * ========================================================
         */

        $limite = intval($filtros['limite'] ?? 10);
        $offset =intval($filtros['offset'] ?? 0);
        if ($limite <= 0) {
            $limite = 10;
        }
        if ($offset < 0 ) {
            $offset = 0;
        }

        /*
         * ========================================================
         * CONSULTA
         * ========================================================
         */

        $sql = "SELECT

                p.idpedido,
                p.idcliente,
                p.idsede,
                p.idusuario_acceso,

                p.folio_pedido,
                p.clave,

                p.fecha_pedido,
                p.fecha_requerida,
                p.mes_facturacion_deseado,

                p.prioridad,

                p.subtotal,
                p.descuento,
                p.iva,
                p.total,

                p.observaciones,

                p.estatus,
                p.estatus_revision_comercial,
                p.estado,

                p.fecha_creacion,
                p.fecha_actualizacion,

                p.version,
                p.ultima_modificacion_por,
                p.fecha_ultima_modificacion,


                /* ================================================
                 * CLIENTE / DISTRIBUIDOR
                 * ================================================ */

                c.codigo_cliente,
                c.clave_distribuidor,

                c.razon_social,
                c.nombre_comercial,

                c.telefono AS telefono_cliente,
                c.celular AS celular_cliente,
                c.correo AS correo_cliente,


                /* ================================================
                 * USUARIO DEL PORTAL
                 * ================================================ */

                ua.nombre AS nombre_usuario,
                ua.apellido AS apellido_usuario,

                ua.correo AS correo_usuario,
                ua.telefono AS telefono_usuario,


                /* ================================================
                 * RESUMEN DEL PEDIDO
                 * ================================================ */

                COALESCE(resumen.total_modelos,0) AS total_modelos,
                COALESCE(resumen.total_unidades,0) AS total_unidades,
                COALESCE(resumen.total_autorizadas,0) AS total_autorizadas,
                COALESCE(resumen.total_facturadas,0) AS total_facturadas,
                COALESCE(resumen.total_pendientes,0) AS total_pendientes,
                COALESCE(backorder_resumen.importe_backorder,0) AS importe_backorder,
                /* Importe mostrado: en BACK_ORDER se utiliza el compromiso vigente,
                   sin alterar el total original ni inventar importes si no hay Backorders. */
                CASE
                    WHEN p.estatus = 'BACK_ORDER'
                         AND backorder_resumen.idpedido IS NOT NULL
                    THEN COALESCE(backorder_resumen.importe_backorder, 0)
                    ELSE p.total
                END AS total_mostrado

            FROM ped_pedidos AS p

            INNER JOIN cli_clientes AS c
                ON c.idcliente = p.idcliente

            LEFT JOIN cli_usuarios_acceso AS ua
                ON ua.idusuario_acceso = p.idusuario_acceso

            LEFT JOIN (

                SELECT
                    d.idpedido,
                    COUNT(DISTINCT d.idunidad) AS total_modelos,
                    SUM(d.cantidad_solicitada) AS total_unidades,
                    SUM(d.cantidad_autorizada) AS total_autorizadas,
                    SUM(d.cantidad_facturada) AS total_facturadas,
                    SUM(d.cantidad_pendiente) AS total_pendientes
                FROM ped_pedidos_detalle AS d
                WHERE d.estado = 2
                GROUP BY d.idpedido

            ) AS resumen
                ON resumen.idpedido = p.idpedido

            LEFT JOIN (
                SELECT idpedido, SUM(total) AS importe_backorder
                FROM ped_backorder
                WHERE estatus <> 'CANCELADO'
                GROUP BY idpedido
            ) AS backorder_resumen ON backorder_resumen.idpedido = p.idpedido
            WHERE
                {$where}
            ORDER BY p.fecha_creacion DESC, p.idpedido DESC
            LIMIT {$limite}
            OFFSET {$offset}
        ";

        /*
         * ========================================================
         * EJECUTAR
         * ========================================================
         */

        $request = $this->select_all($sql,$parametros);

        return
            is_array(
                $request
            )
                ? $request
                : [];

    }


    /* ============================================================
     * CONTAR PEDIDOS
     * ============================================================
     *
     * Esta consulta es importante para la paginación.
     * ============================================================ */

    public function countPedidos(array $filtros = []): int {
        /*
         * ========================================================
         * FILTROS
         * ========================================================
         */

        $condiciones =$this->construirFiltrosPedidos($filtros);
        $where =$condiciones['where'];
        $parametros = $condiciones['parametros'];

        /*
         * ========================================================
         * CONSULTA
         * ========================================================
         */

        $sql = "SELECT

                COUNT(
                    p.idpedido
                ) AS total

            FROM ped_pedidos AS p

            INNER JOIN cli_clientes AS c
                ON c.idcliente = p.idcliente

            LEFT JOIN cli_usuarios_acceso AS ua
                ON ua.idusuario_acceso = p.idusuario_acceso

            LEFT JOIN (
                SELECT idpedido, SUM(total) AS importe
                FROM ped_backorder
                WHERE estatus <> 'CANCELADO'
                GROUP BY idpedido
            ) bres ON bres.idpedido = p.idpedido
            WHERE
                {$where}
        ";

        $request =$this->select($sql,$parametros);
        return intval(
            $request['total']
            ?? 0
        );

    }


    /* ============================================================
     * INDICADORES
     * ============================================================
     *  
     * */

    public function selectIndicadoresPedidos(array $filtros = []): array {

        /*
         * Quitamos únicamente el filtro de estatus
         * para conservar la visión general.
         */

        $filtrosIndicadores =$filtros;
        $filtrosIndicadores['estatus'] ='';
        $filtrosIndicadores['situacion_comercial'] = '';

        /*
         * ========================================================
         * FILTROS
         * ========================================================
         */
        $condiciones =$this->construirFiltrosPedidos($filtrosIndicadores);
        $where =$condiciones['where'];
        $parametros =$condiciones['parametros'];
        /*
         * ========================================================
         * CONSULTA
         * ========================================================
         */

        $sql = "SELECT

                COUNT(
                    p.idpedido
                ) AS total_pedidos,


                SUM(
                    CASE

                        WHEN p.estatus = 'PENDIENTE'
                            THEN 1

                        ELSE 0

                    END
                ) AS pendientes,


                SUM(
                    CASE

                        WHEN p.estatus = 'EN_REVISION'
                            THEN 1

                        ELSE 0

                    END
                ) AS en_revision,


                SUM(
                    CASE

                        WHEN p.estatus = 'AUTORIZADO'
                            THEN 1

                        ELSE 0

                    END
                ) AS autorizados,


                SUM(
                    CASE

                        WHEN p.estatus = 'RECHAZADO'
                            THEN 1

                        ELSE 0

                    END
                ) AS rechazados,


                SUM(
                    CASE

                        WHEN p.estatus = 'CANCELADO'
                            THEN 1

                        ELSE 0

                    END
                ) AS cancelados,


                SUM(
                    CASE

                        WHEN p.estatus = 'FINALIZADO'
                            THEN 1

                        ELSE 0

                    END
                ) AS finalizados,


                COALESCE(
                    SUM(
                        CASE

                            WHEN p.estatus <> 'CANCELADO'
                                THEN p.total

                            ELSE 0

                        END
                    ),
                    0
                ) AS importe_total,
                COALESCE(SUM(COALESCE(bres.importe,0)),0) AS importe_backorders


            FROM ped_pedidos AS p


            INNER JOIN cli_clientes AS c
                ON c.idcliente = p.idcliente


            LEFT JOIN cli_usuarios_acceso AS ua
                ON ua.idusuario_acceso = p.idusuario_acceso

            /* Importe vigente por pedido; evita duplicar importes al agrupar. */
            LEFT JOIN (
                SELECT idpedido, SUM(total) AS importe
                FROM ped_backorder
                WHERE estatus <> 'CANCELADO'
                GROUP BY idpedido
            ) AS bres ON bres.idpedido = p.idpedido

            WHERE
                {$where}
        ";


        $request =$this->select($sql,$parametros);


        if (empty($request)) {

            return [
                'total_pedidos' =>0,
                'pendientes' =>0,
                'en_revision' => 0,
                'autorizados' =>0,
                'rechazados' =>0,
                'cancelados' =>0,
                'finalizados' =>0,
                'importe_total' =>0,
                'importe_backorders' => 0
            ];

        }

        return [
            'total_pedidos' =>intval($request['total_pedidos'] ?? 0),
            'pendientes' =>intval($request['pendientes'] ?? 0),
            'en_revision' =>intval($request['en_revision'] ?? 0),
            'autorizados' =>intval($request['autorizados'] ?? 0),
            'rechazados' =>intval($request['rechazados'] ?? 0),
            'cancelados' =>intval($request['cancelados'] ?? 0),
            'finalizados' =>intval($request['finalizados'] ?? 0),
            'importe_total' =>floatval($request['importe_total'] ?? 0),
            'importe_backorders' => floatval($request['importe_backorders'] ?? 0)
        ];

    }


    /* ============================================================
     * DISTRIBUIDORES
     * ============================================================
     *
     * Solamente devuelve clientes que ya tengan pedidos.
     *
     * ============================================================ */

    public function selectDistribuidoresPedidos(): array
    {

        $sql = "SELECT DISTINCT

                c.idcliente,

                c.codigo_cliente,
                c.clave_distribuidor,

                c.razon_social,
                c.nombre_comercial

            FROM cli_clientes AS c

            INNER JOIN ped_pedidos AS p
                ON p.idcliente = c.idcliente

            WHERE

                c.estado = 2

                AND p.estado = 2

            ORDER BY

                COALESCE(
                    NULLIF(
                        c.nombre_comercial,
                        ''
                    ),
                    c.razon_social
                ) ASC
        ";

        $request = $this->select_all($sql);

        return
            is_array($request)
                ? $request
                : [];

    }


    /* ============================================================
     * DETALLE ADMINISTRATIVO DEL PEDIDO
     * ============================================================ */

    public function selectPedidoDetalleAdmin(string $clave) {

        $clave =trim($clave);

        if ($clave === '') {
            return [];
        }

        $sql = "SELECT

                p.idpedido,
                p.idcliente,
                p.idsede,
                p.idusuario_acceso,

                p.folio_pedido,
                p.clave,

                p.fecha_pedido,
                p.fecha_requerida,
                p.mes_facturacion_deseado,

                p.prioridad,

                p.subtotal,
                p.descuento,
                p.iva,
                p.total,

                p.observaciones,

                p.estatus,
                p.estado,

                p.version,

                p.ultima_modificacion_por,
                p.fecha_ultima_modificacion,

                p.fecha_creacion,
                p.fecha_actualizacion,


                /* ================================================
                 * CLIENTE
                 * ================================================ */

                c.codigo_cliente,

                c.clave_distribuidor,

                c.razon_social,
                c.nombre_comercial,

                c.telefono AS telefono_cliente,
                c.celular AS celular_cliente,

                c.correo AS correo_cliente,

                c.tipo_persona,


                /* ================================================
                 * USUARIO PORTAL
                 * ================================================ */

                ua.nombre AS nombre_usuario,
                ua.apellido AS apellido_usuario,

                ua.correo AS correo_usuario,

                ua.telefono AS telefono_usuario,


                /* ================================================
                 * RESUMEN
                 * ================================================ */

                COALESCE(
                    resumen.total_modelos,
                    0
                ) AS total_modelos,

                COALESCE(
                    resumen.total_unidades,
                    0
                ) AS total_unidades,

                COALESCE(
                    resumen.total_autorizadas,
                    0
                ) AS total_autorizadas,

                COALESCE(
                    resumen.total_facturadas,
                    0
                ) AS total_facturadas,

                COALESCE(
                    resumen.total_pendientes,
                    0
                ) AS total_pendientes


            FROM ped_pedidos AS p


            INNER JOIN cli_clientes AS c
                ON c.idcliente = p.idcliente


            LEFT JOIN cli_usuarios_acceso AS ua
                ON ua.idusuario_acceso = p.idusuario_acceso


            LEFT JOIN (

                SELECT
                    d.idpedido,
                    COUNT(DISTINCT d.idunidad) AS total_modelos,
                    SUM(d.cantidad_solicitada) AS total_unidades,
                    SUM(d.cantidad_autorizada) AS total_autorizadas,
                    SUM(d.cantidad_facturada) AS total_facturadas,
                    SUM(d.cantidad_pendiente) AS total_pendientes
                FROM ped_pedidos_detalle AS d
                WHERE d.estado = 2
                GROUP BY d.idpedido

            ) AS resumen
                ON resumen.idpedido = p.idpedido

            WHERE
                p.clave = ?
                AND p.estado = 2
            LIMIT 1
        ";

        $request =$this->select($sql,[$clave]);

        return
            !empty(
                $request
            )
                ? $request
                : [];

    }


    /* ============================================================
     * DETALLES / UNIDADES DEL PEDIDO
     * ============================================================ */

    public function selectDetallesPedidoAdmin(int $idpedido): array {

        if ($idpedido <= 0 ) {
            return [];
        }

        $sql = "SELECT

                d.idpedido_detalle,
                d.idpedido,
                d.idunidad,

                d.tipo_entrega,
                d.idsucursal_entrega,
                d.direccion_entrega,

                d.cantidad_solicitada,
                d.cantidad_autorizada,
                d.cantidad_facturada,
                d.cantidad_pendiente,

                d.precio_unitario,
                d.descuento,

                d.subtotal,
                d.iva,
                d.total,

                d.estatus,
                d.estado,

                d.fecha_creacion,
                d.fecha_actualizacion,


                /* ================================================
                 * UNIDAD
                 * ================================================ */

                u.modelo,
                u.clave_modelo,

                u.nombre,
                u.version,

                u.descripcion,

                u.anio,
                (SELECT sp_m.cve_sublinea_producto FROM wms_sublinea_producto sp_m WHERE sp_m.idsublineaproducto = u.marca) AS marca,
                u.motor,

                u.stock,

                u.precio_estimado,

                u.imagen_caratula


            FROM ped_pedidos_detalle AS d

            INNER JOIN web_unidades AS u
                ON u.idunidad = d.idunidad

            WHERE
                d.idpedido = ?
                AND d.estado = 2
            ORDER BY
                d.idpedido_detalle ASC
        ";

        $request =$this->select_all($sql,[$idpedido]);

        return
            is_array(
                $request
            )
                ? $request
                : [];

    }


    /* ============================================================
     * INICIAR GESTIÓN DEL PEDIDO
     * ============================================================
     *
     *
     * ============================================================ */

    public function iniciarGestionPedidoModel(int $idpedido,int $idusuario) {

        if ($idpedido <= 0 || $idusuario <= 0) {
            return false;
        }

        $sql = "UPDATE ped_pedidos

            SET
                estatus = 'EN_REVISION',
                ultima_modificacion_por = ?,
                fecha_ultima_modificacion = NOW(),
                fecha_actualizacion = NOW(),
                version = version + 1

            WHERE
                idpedido = ?
                AND estado = 2
                AND estatus = 'PENDIENTE'
        ";

        $arrData = [
            $idusuario,
            $idpedido
        ];

        $request =
            $this->update(
                $sql,
                $arrData
            );

        return $request;
    }

    /* ============================================================
     * INSERTAR BITÁCORA
     * ============================================================
     *
     * ============================================================ */

    public function insertBitacoraEvento(array $data): int {

        /*
         * ========================================================
         * VALIDAR
         * ========================================================
         */

        $idpedido =intval($data['idpedido'] ?? 0);
        $usuarioRegistro =intval($data['usuario_registro'] ?? 0);
        if ($idpedido <= 0 || $usuarioRegistro <= 0) {
            return 0;
        }

        /*
         * ========================================================
         * DATOS
         * ========================================================
         */

        $tipoEvento =trim((string)($data['tipo_evento'] ?? ''));
        $descripcion =trim((string)($data['descripcion'] ?? ''));
        $estatusAnterior =trim((string)($data['estatus_anterior'] ?? ''));
        $estatusNuevo =trim((string)($data['estatus_nuevo'] ?? ''));
        $origen =trim((string)($data['origen'] ?? 'ADMIN'));

        /*
         * ========================================================
         * INSERT
         * ========================================================
         */

        $sql = "INSERT INTO ped_bitacora_eventos (

                idpedido,
                tipo_evento,
                descripcion,
                estatus_anterior,
                estatus_nuevo,
                usuario_registro,
                origen,
                fecha_creacion,
                fecha_actualizacion
            )

            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ";


        $arrData = [
            $idpedido,
            $tipoEvento,
            $descripcion,
            $estatusAnterior,
            $estatusNuevo,
            $usuarioRegistro,
            $origen
        ];


        $request =$this->insert($sql,$arrData);
        return intval(
            $request
        );

    }


    /* ============================================================
     * CONSTRUIR FILTROS
     * ============================================================
     *
     * ============================================================ */

    private function construirFiltrosPedidos(array $filtros): array {

        /*
         * ========================================================
         * CONDICIONES BASE
         * ========================================================
         */

        $condiciones = [
            "p.estado = 2"
        ];

        $parametros = [];

        /*
         * ========================================================
         * BÚSQUEDA GENERAL
         * ========================================================
         */

        $busqueda =trim((string)($filtros['busqueda'] ?? ''));


        if ($busqueda !== '') {

            $textoBusqueda ='%' . $busqueda . '%';


            $condiciones[] = "
                (
                    p.folio_pedido LIKE ?
                    OR p.clave LIKE ?
                    OR c.codigo_cliente LIKE ?
                    OR c.clave_distribuidor LIKE ?
                    OR c.razon_social LIKE ?
                    OR c.nombre_comercial LIKE ?
                    OR ua.nombre LIKE ?
                    OR ua.apellido LIKE ?
                    OR ua.correo LIKE ?
                )
            ";


            $parametros[] =$textoBusqueda;
            $parametros[] = $textoBusqueda;
            $parametros[] =$textoBusqueda;
            $parametros[] =$textoBusqueda;
            $parametros[] = $textoBusqueda;
            $parametros[] =$textoBusqueda;
            $parametros[] =$textoBusqueda;
            $parametros[] =$textoBusqueda;
            $parametros[] =$textoBusqueda;
        }


        /*
         * ========================================================
         * ESTATUS
         * ========================================================
         */

        $estatus =strtoupper(trim((string)($filtros['estatus'] ?? '')));


        if ($estatus !== '') {

            $estatusPermitidos = [
                'PENDIENTE',
                'EN_REVISION',
                'AUTORIZADO',
                'RECHAZADO',
                'CANCELADO',
                'FINALIZADO'
            ];


            if (in_array($estatus,$estatusPermitidos,true)) {

                $condiciones[] ='p.estatus = ?';
                $parametros[] =$estatus;
            }

        }

        // Situación de la revisión comercial: filtro independiente del estado del pedido.
        $situacion = strtoupper(trim((string)($filtros['situacion_comercial'] ?? '')));
        if (in_array($situacion, ['PENDIENTE_ACEPTACION','AJUSTE_SOLICITADO','ACEPTADA','RECHAZADA'], true)) {
            $condiciones[] = 'p.estatus_revision_comercial = ?';
            $parametros[] = $situacion;
        }

        $backorder = strtoupper(trim((string)($filtros['backorder'] ?? '')));
        if ($backorder === 'CON') {
            $condiciones[] = "EXISTS (SELECT 1 FROM ped_backorder b WHERE b.idpedido=p.idpedido AND b.estatus <> 'CANCELADO')";
        } elseif ($backorder === 'SIN') {
            $condiciones[] = "NOT EXISTS (SELECT 1 FROM ped_backorder b WHERE b.idpedido=p.idpedido AND b.estatus <> 'CANCELADO')";
        }

        /*
         * ========================================================
         * PRIORIDAD
         * ========================================================
         */

        $prioridad =
            strtoupper(
                trim(
                    (string)(
                        $filtros['prioridad']
                        ?? ''
                    )
                )
            );


        if (
            $prioridad !== ''
        ) {

            $prioridadesPermitidas = [
                'BAJA',
                'MEDIA',
                'ALTA',
                'URGENTE'
            ];

            if (in_array($prioridad,$prioridadesPermitidas,true)) {
                $condiciones[] ='p.prioridad = ?';
                $parametros[] =$prioridad;
            }

        }

        /*
         * ========================================================
         * DISTRIBUIDOR
         * ========================================================
         */

        $idcliente =intval($filtros['idcliente'] ?? 0);
        if ($idcliente > 0) {
            $condiciones[] ='p.idcliente = ?';
            $parametros[] =$idcliente;
        }

        /*
         * ========================================================
         * FECHA DESDE
         * ========================================================
         */

        $desde =trim((string)($filtros['desde'] ?? ''));

        if ($desde !== '') {
            $condiciones[] ='DATE(p.fecha_pedido) >= ?';
            $parametros[] =$desde;
        }

        /*
         * ========================================================
         * FECHA HASTA
         * ========================================================
         */

        $hasta = trim((string)($filtros['hasta'] ?? ''));
        if ($hasta !== '' ) {
            $condiciones[] ='DATE(p.fecha_pedido) <= ?';
            $parametros[] = $hasta;
        }

        /*
         * ========================================================
         * FECHA REQUERIDA
         * ========================================================
         */

        $fechaRequerida =trim((string)($filtros['fecha_requerida'] ?? ''));
        if ($fechaRequerida !== '') {
            $condiciones[] ='DATE(p.fecha_requerida) = ?';
            $parametros[] =$fechaRequerida;
        }

        /*
         * ========================================================
         * MES DE FACTURACIÓN DESEADO
         * ========================================================
         *
         * ============================================================ */

        $mesFacturacion =trim((string)($filtros['mes_facturacion'] ?? ''));

        if ($mesFacturacion !== '') {
            $condiciones[] = "
            LEFT(
                    p.mes_facturacion_deseado,
                    7
                ) = ?
            ";

            $parametros[] =$mesFacturacion;
        }

        /*
         * ========================================================
         * CONSTRUIR WHERE
         * ========================================================
         */

        $where =
            implode(
                ' AND ',
                $condiciones
            );

        return [
            'where' =>$where,
            'parametros' =>$parametros

        ];

    }






    public function selectPedidoGestionAdmin(string $clave)
{
    $sql = "SELECT
            p.idpedido,
            p.idcliente,
            p.idsede,
            p.idusuario_acceso,

            p.folio_pedido,
            p.clave,

            p.fecha_pedido,
            p.fecha_requerida,
            p.mes_facturacion_deseado,

            p.prioridad,

            p.subtotal,
            p.descuento,
            p.iva,
            p.total,

            p.observaciones,

            p.estatus,
            p.estado,

            p.version,
            p.ultima_modificacion_por,
            p.fecha_ultima_modificacion,

            p.fecha_creacion,
            p.fecha_actualizacion,

            c.codigo_cliente,
            c.clave_distribuidor,
            c.razon_social,
            c.nombre_comercial,

            c.telefono AS telefono_cliente,
            c.celular AS celular_cliente,
            c.correo AS correo_cliente,

            ua.nombre AS nombre_usuario,
            ua.apellido AS apellido_usuario,
            ua.correo AS correo_usuario,
            ua.telefono AS telefono_usuario,

            COALESCE(resumen.total_modelos, 0) AS total_modelos,
            COALESCE(resumen.total_solicitadas, 0) AS total_unidades,
            COALESCE(resumen.total_autorizadas, 0) AS total_autorizadas,
            COALESCE(resumen.total_facturadas, 0) AS total_facturadas,
            COALESCE(resumen.total_pendientes, 0) AS total_pendientes,
            COALESCE(backorder_resumen.importe_backorder, 0) AS importe_backorder,
            CASE
                WHEN p.estatus = 'BACK_ORDER'
                     AND backorder_resumen.idpedido IS NOT NULL
                THEN COALESCE(backorder_resumen.importe_backorder, 0)
                ELSE p.total
            END AS total_mostrado

        FROM ped_pedidos AS p

        INNER JOIN cli_clientes AS c
            ON c.idcliente = p.idcliente

        LEFT JOIN cli_usuarios_acceso AS ua
            ON ua.idusuario_acceso = p.idusuario_acceso

        LEFT JOIN (
            SELECT
                idpedido,
                COUNT(DISTINCT idunidad) AS total_modelos,
                SUM(cantidad_solicitada) AS total_solicitadas,
                SUM(cantidad_autorizada) AS total_autorizadas,
                SUM(cantidad_facturada) AS total_facturadas,
                SUM(cantidad_pendiente) AS total_pendientes

            FROM ped_pedidos_detalle

            WHERE estado = 2

            GROUP BY idpedido
        ) AS resumen
            ON resumen.idpedido = p.idpedido

        LEFT JOIN (
            SELECT idpedido, SUM(total) AS importe_backorder
            FROM ped_backorder
            WHERE estatus <> 'CANCELADO'
            GROUP BY idpedido
        ) AS backorder_resumen
            ON backorder_resumen.idpedido = p.idpedido

        WHERE p.clave = '{$clave}'
          AND p.estado = 2

        LIMIT 1
    ";

    $request = $this->select($sql);

    return !empty($request)
        ? $request
        : [];
}




public function selectDetallesGestionPedido(int $idpedido): array
{
    if ($idpedido <= 0) {
        return [];
    }

    $sql = "SELECT
            d.idpedido_detalle,
            d.idpedido,
            d.idunidad,

            d.tipo_entrega,
            d.idsucursal_entrega,
            d.direccion_entrega,

            d.cantidad_solicitada,
            d.cantidad_autorizada,
            d.cantidad_facturada,
            d.cantidad_pendiente,

            d.precio_unitario,
            d.descuento,
            d.subtotal,
            d.iva,
            d.total,

            d.estatus,
            d.estado,

            u.modelo,
            u.clave_modelo,
            u.nombre,
            u.version,
            u.anio,
            (SELECT sp_m.cve_sublinea_producto FROM wms_sublinea_producto sp_m WHERE sp_m.idsublineaproducto = u.marca) AS marca,
            u.motor,
            u.stock,
            u.precio_estimado,
            u.imagen_caratula

        FROM ped_pedidos_detalle AS d

        INNER JOIN web_unidades AS u
            ON u.idunidad = d.idunidad

        WHERE d.idpedido = $idpedido
          AND d.estado = 2

        ORDER BY d.idpedido_detalle ASC
    ";

    $request = $this->select_all($sql);

    return is_array($request)
        ? $request
        : [];
}



public function selectBitacoraPedido(int $idpedido): array
{
    if ($idpedido <= 0) {
        return [];
    }

    $sql = "SELECT
            idbitacora_evento,
            idpedido,
            tipo_evento,
            descripcion,
            estatus_anterior,
            estatus_nuevo,
            usuario_registro,
            origen,
            fecha_creacion,
            fecha_actualizacion

        FROM ped_bitacora_eventos

        WHERE idpedido = $idpedido

        ORDER BY fecha_creacion DESC,
                 idbitacora_evento DESC
    ";

    $request = $this->select_all($sql);

    return is_array($request)
        ? $request
        : [];
}

    /* ============================================================
     * ASIGNACIÓN DE VINS PT (wms_almacenes.pt = 1) A PEDIDOS
     * ============================================================ */

    public function getAsignacionesPedido(int $idpedido): array
    {
        if ($idpedido <= 0) {
            return ['partidas' => [], 'asignaciones' => []];
        }

        $partidas = $this->select_all("
            SELECT
                d.idpedido_detalle,
                d.idpedido,
                d.idunidad,
                COALESCE(u.inventarioid, 0) AS inventarioid,
                u.modelo,
                u.clave_modelo,
                u.nombre AS unidad_nombre,
                d.tipo_entrega,
                d.idsucursal_entrega,
                s.nombre_sucursal AS sucursal_entrega_nombre,
                d.direccion_entrega,
                d.cantidad_solicitada,
                d.cantidad_autorizada,
                d.cantidad_facturada,
                d.cantidad_pendiente,
                d.estatus,
                bo.idbackorder,
                bo.cantidad_comprometida AS bo_comprometida,
                bo.cantidad_asignada AS bo_asignada,
                bo.cantidad_pendiente AS bo_pendiente,
                (
                    SELECT COUNT(*)
                    FROM ped_pedidos_vins pv
                    WHERE pv.idpedido_detalle = d.idpedido_detalle
                      AND pv.estatus = 'ACTIVA'
                ) AS vins_asignados
            FROM ped_pedidos_detalle d
            INNER JOIN web_unidades u ON u.idunidad = d.idunidad
            LEFT JOIN cli_clientes_sucursales s ON s.idsucursal = d.idsucursal_entrega
            LEFT JOIN ped_backorder bo ON bo.idpedido_detalle = d.idpedido_detalle
            WHERE d.idpedido = ?
              AND d.estado = 2
            ORDER BY d.idpedido_detalle ASC
        ", [$idpedido]) ?: [];

        $asignaciones = $this->select_all("
            SELECT
                pv.id AS id_asignacion,
                pv.idpedido,
                pv.idpedido_detalle,
                pv.idbackorder,
                pv.id_numeros_serie,
                pv.estatus,
                pv.fecha_asignacion,
                ns.numero_serie,
                ns.estado AS estado_vin,
                COALESCE(a.descripcion, '') AS almacen,
                COALESCE(pl.nombre_planta, '') AS planta,
                COALESCE(f.factura, '') AS factura,
                COALESCE(f.distribuidor, '') AS distribuidor,
                c.estatus_pago
            FROM ped_pedidos_vins pv
            INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = pv.id_numeros_serie
            LEFT JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
            LEFT JOIN mrp_planta pl ON pl.idplanta = a.plantaid
            LEFT JOIN wms_vin_facturacion f ON f.id_numeros_serie = ns.id_numeros_serie AND f.estatus IN (1, 2)
            LEFT JOIN vta_cobranza_unidades c ON c.id_numeros_serie = ns.id_numeros_serie
                AND (c.idfacturacion = f.idfacturacion OR f.idfacturacion IS NULL)
            WHERE pv.idpedido = ?
              AND pv.estatus = 'ACTIVA'
            ORDER BY pv.idpedido_detalle ASC, pv.id ASC
        ", [$idpedido]) ?: [];

        return [
            'partidas'     => $partidas,
            'asignaciones' => $asignaciones,
        ];
    }

    public function getVinsDisponiblesParaPedido(int $idpedidoDetalle, string $busqueda = ''): array
    {
        if ($idpedidoDetalle <= 0) {
            return [];
        }

        $det = $this->select("
            SELECT d.idpedido_detalle, d.idpedido, COALESCE(u.inventarioid, 0) AS inventarioid
            FROM ped_pedidos_detalle d
            LEFT JOIN web_unidades u ON u.idunidad = d.idunidad
            WHERE d.idpedido_detalle = ? AND d.estado = 2
            LIMIT 1
        ", [$idpedidoDetalle]);

        if (!$det) {
            return [];
        }

        $invId = (int)($det['inventarioid'] ?? 0);
        $params = [];
        $whereInv = '';
        if ($invId > 0) {
            $whereInv = " AND ns.inventarioid = ? ";
            $params[] = $invId;
        }
        $whereSearch = '';
        $busq = trim($busqueda);
        if ($busq !== '') {
            $whereSearch = " AND (ns.numero_serie LIKE ? OR va.numero_motor LIKE ? OR a.descripcion LIKE ?) ";
            $like = '%' . $busq . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = "
            SELECT
                ns.id_numeros_serie,
                ns.numero_serie,
                ns.estado AS estado_vin,
                ns.inventarioid,
                i.cve_articulo,
                i.descripcion AS modelo_wms,
                a.idalmacen,
                a.cve_almacen,
                a.descripcion AS almacen,
                COALESCE(pl.nombre_planta, '') AS planta,
                COALESCE(va.numero_motor, '') AS numero_motor,
                COALESCE(f.factura, '') AS factura,
                COALESCE(f.distribuidor, '') AS distribuidor,
                c.estatus_pago
            FROM wms_numeros_series ns
            INNER JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
            INNER JOIN wms_inventario i ON i.idinventario = ns.inventarioid
            LEFT JOIN mrp_planta pl ON pl.idplanta = a.plantaid
            LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = ns.id_numeros_serie
            LEFT JOIN wms_vin_facturacion f ON f.id_numeros_serie = ns.id_numeros_serie AND f.estatus IN (1, 2)
            LEFT JOIN vta_cobranza_unidades c ON c.id_numeros_serie = ns.id_numeros_serie
                AND (c.idfacturacion = f.idfacturacion OR f.idfacturacion IS NULL)
            WHERE (ns.estado IN ('1', '2') OR COALESCE(f.estatus, 0) = 1)
              AND a.estado = 2
              AND a.pt = 1
              {$whereInv}
              {$whereSearch}
              AND NOT EXISTS (
                  SELECT 1 FROM ped_pedidos_vins pv
                  WHERE pv.id_numeros_serie = ns.id_numeros_serie
                    AND pv.estatus = 'ACTIVA'
              )
              AND NOT EXISTS (
                  SELECT 1 FROM ped_backorder_asignaciones ba
                  WHERE ba.id_numeros_serie = ns.id_numeros_serie
                    AND ba.estatus = 'ACTIVA'
              )
            ORDER BY (f.idfacturacion IS NOT NULL) DESC, ns.estado ASC, ns.fecha DESC, ns.id_numeros_serie DESC
            LIMIT 250
        ";

        return $this->select_all($sql, $params) ?: [];
    }

    public function asignarVinPedido(int $idpedido, int $idpedidoDetalle, int $idNumerosSerie, int $idUsuario): array
    {
        if ($idpedido <= 0 || $idpedidoDetalle <= 0 || $idNumerosSerie <= 0 || $idUsuario <= 0) {
            throw new Exception('Datos incompletos para asignar el VIN al pedido.');
        }

        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $det = $this->select("
                SELECT d.idpedido_detalle, d.idpedido, d.idunidad, d.idsucursal_entrega, d.direccion_entrega,
                       d.cantidad_solicitada, d.cantidad_autorizada,
                       p.folio_pedido, p.idcliente, p.estatus AS estatus_pedido,
                       COALESCE(u.inventarioid, 0) AS inventarioid,
                       COALESCE(s.nombre_sucursal, d.direccion_entrega, '') AS destino_txt
                FROM ped_pedidos_detalle d
                INNER JOIN ped_pedidos p ON p.idpedido = d.idpedido
                LEFT JOIN web_unidades u ON u.idunidad = d.idunidad
                LEFT JOIN cli_clientes_sucursales s ON s.idsucursal = d.idsucursal_entrega
                WHERE d.idpedido_detalle = ? AND d.idpedido = ? AND d.estado = 2
                FOR UPDATE
            ", [$idpedidoDetalle, $idpedido]);

            if (!$det) {
                throw new Exception('La partida seleccionada no pertenece al pedido o está inactiva.');
            }
            if (in_array(strtoupper((string)$det['estatus_pedido']), ['CANCELADO', 'RECHAZADO'], true)) {
                throw new Exception('No es posible asignar VINs a un pedido cancelado o rechazado.');
            }

            $cupo = max((int)$det['cantidad_solicitada'], (int)$det['cantidad_autorizada']);
            $cntRow = $this->select("
                SELECT COUNT(*) AS total
                FROM ped_pedidos_vins
                WHERE idpedido_detalle = ? AND estatus = 'ACTIVA'
            ", [$idpedidoDetalle]);
            $asignadasActuales = (int)($cntRow['total'] ?? 0);
            if ($cupo > 0 && $asignadasActuales >= $cupo) {
                throw new Exception("La partida ya tiene todas sus unidades asignadas ({$asignadasActuales}/{$cupo}).");
            }

            $vin = $this->select("
                SELECT ns.id_numeros_serie, ns.numero_serie, ns.inventarioid, ns.estado,
                       a.pt, a.estado AS alm_estado
                FROM wms_numeros_series ns
                INNER JOIN wms_almacenes a ON a.idalmacen = ns.almacenid
                WHERE ns.id_numeros_serie = ?
                FOR UPDATE
            ", [$idNumerosSerie]);

            if (!$vin || !in_array((string)$vin['estado'], ['1', '2', '3'], true)) {
                throw new Exception('El VIN seleccionado no existe o está inactivo.');
            }
            if ((int)$vin['alm_estado'] !== 2 || (int)$vin['pt'] !== 1) {
                throw new Exception("El VIN {$vin['numero_serie']} no pertenece a un almacén de Producto Terminado (pt = 1).");
            }
            $invEsperado = (int)($det['inventarioid'] ?? 0);
            if ($invEsperado > 0 && (int)$vin['inventarioid'] !== $invEsperado) {
                throw new Exception("El VIN {$vin['numero_serie']} no corresponde al modelo/artículo de la partida.");
            }

            $yaPed = $this->select("
                SELECT pv.id, p.folio_pedido
                FROM ped_pedidos_vins pv
                INNER JOIN ped_pedidos p ON p.idpedido = pv.idpedido
                WHERE pv.id_numeros_serie = ? AND pv.estatus = 'ACTIVA'
                LIMIT 1
            ", [$idNumerosSerie]);
            if ($yaPed) {
                throw new Exception("El VIN {$vin['numero_serie']} ya se encuentra asignado al pedido {$yaPed['folio_pedido']}.");
            }

            $boRow = $this->select("
                SELECT idbackorder FROM ped_backorder WHERE idpedido_detalle = ? LIMIT 1 FOR UPDATE
            ", [$idpedidoDetalle]);
            $idBackorder = !empty($boRow['idbackorder']) ? (int)$boRow['idbackorder'] : null;

            $idAsig = (int)$this->insert("
                INSERT INTO ped_pedidos_vins
                    (idpedido, idpedido_detalle, idbackorder, id_numeros_serie, estatus, asignado_por)
                VALUES (?, ?, ?, ?, 'ACTIVA', ?)
            ", [$idpedido, $idpedidoDetalle, $idBackorder, $idNumerosSerie, $idUsuario]);

            if ($idBackorder) {
                $yaBo = $this->select("
                    SELECT idasignacion FROM ped_backorder_asignaciones
                    WHERE id_numeros_serie = ? AND estatus = 'ACTIVA' LIMIT 1
                ", [$idNumerosSerie]);
                if (!$yaBo) {
                    $db->prepare("
                        INSERT INTO ped_backorder_asignaciones (idbackorder, id_numeros_serie, estatus, asignado_por)
                        VALUES (?, ?, 'ACTIVA', ?)
                    ")->execute([$idBackorder, $idNumerosSerie, $idUsuario]);
                }
                $this->recalcularBackorderPorId($db, $idBackorder);
            }

            // Enlazar también en wms_vin_facturacion y lgs_unidades si existen registros para este VIN
            $db->prepare("
                UPDATE wms_vin_facturacion
                SET id_pedido = COALESCE(id_pedido, ?),
                    id_pedido_detalle = COALESCE(id_pedido_detalle, ?),
                    id_cliente = COALESCE(id_cliente, ?)
                WHERE id_numeros_serie = ? AND estatus IN (1, 2)
            ")->execute([$idpedido, $idpedidoDetalle, (int)$det['idcliente'], $idNumerosSerie]);

            $this->sincronizarLgsUnidadDesdePedido(
                $db,
                $idNumerosSerie,
                (string)$vin['numero_serie'],
                $idpedido,
                (string)$det['folio_pedido'],
                (int)$det['idcliente'],
                !empty($det['idsucursal_entrega']) ? (int)$det['idsucursal_entrega'] : null,
                (string)$det['destino_txt'],
                $idUsuario
            );

            $this->insertBitacoraEvento([
                'idpedido'          => $idpedido,
                'tipo_evento'       => 'ASIGNACION_VIN',
                'descripcion'       => "VIN {$vin['numero_serie']} asignado a la partida #{$idpedidoDetalle}.",
                'estatus_anterior'  => $det['estatus_pedido'],
                'estatus_nuevo'     => $det['estatus_pedido'],
                'usuario_registro'  => $idUsuario,
                'origen'            => 'ADMIN',
            ]);

            $db->commit();

            return [
                'id_asignacion' => $idAsig,
                'numero_serie'  => $vin['numero_serie'],
                'folio_pedido'  => $det['folio_pedido'],
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    public function liberarVinPedido(int $idAsignacion, int $idUsuario, string $motivo = ''): array
    {
        if ($idAsignacion <= 0 || $idUsuario <= 0) {
            throw new Exception('Identificador de asignación inválido.');
        }

        $db = $this->getConexion();
        $db->beginTransaction();
        try {
            $asig = $this->select("
                SELECT pv.*, p.folio_pedido, p.estatus AS estatus_pedido, ns.numero_serie
                FROM ped_pedidos_vins pv
                INNER JOIN ped_pedidos p ON p.idpedido = pv.idpedido
                INNER JOIN wms_numeros_series ns ON ns.id_numeros_serie = pv.id_numeros_serie
                WHERE pv.id = ?
                FOR UPDATE
            ", [$idAsignacion]);

            if (!$asig || strtoupper((string)$asig['estatus']) !== 'ACTIVA') {
                throw new Exception('La asignación de VIN no existe o ya fue liberada.');
            }

            // Verificar que el VIN no esté comprometido en un envío oficial EN- activo
            $enOficial = $this->select("
                SELECT e.folio
                FROM lgs_envios_vins ev
                INNER JOIN lgs_envios e ON e.id_envio = ev.id_envio
                INNER JOIN lgs_unidades_envios u ON u.id_unidad = ev.id_unidad
                WHERE e.deleted_at IS NULL
                  AND COALESCE(e.es_preenvio, 0) = 0
                  AND e.id_estado NOT IN (0, 8)
                  AND (ev.id_numeros_serie = ? OR UPPER(TRIM(u.vin)) = UPPER(TRIM(?)))
                LIMIT 1
            ", [(int)$asig['id_numeros_serie'], (string)$asig['numero_serie']]);
            if ($enOficial) {
                throw new Exception("No se puede liberar el VIN {$asig['numero_serie']} porque ya está asignado al envío oficial {$enOficial['folio']}.");
            }

            $motivoFinal = trim($motivo) !== '' ? trim($motivo) : 'Liberado desde gestión del pedido';
            $db->prepare("
                UPDATE ped_pedidos_vins
                SET estatus = 'LIBERADA',
                    liberado_por = ?,
                    fecha_liberacion = NOW(),
                    motivo_liberacion = ?
                WHERE id = ?
            ")->execute([$idUsuario, $motivoFinal, $idAsignacion]);

            if (!empty($asig['idbackorder'])) {
                $db->prepare("
                    UPDATE ped_backorder_asignaciones
                    SET estatus = 'LIBERADA',
                        liberado_por = ?,
                        fecha_liberacion = NOW(),
                        motivo_liberacion = ?
                    WHERE idbackorder = ? AND id_numeros_serie = ? AND estatus = 'ACTIVA'
                ")->execute([$idUsuario, $motivoFinal, (int)$asig['idbackorder'], (int)$asig['id_numeros_serie']]);
                $this->recalcularBackorderPorId($db, (int)$asig['idbackorder']);
            }

            $db->prepare("
                UPDATE wms_vin_facturacion
                SET id_pedido = NULL, id_pedido_detalle = NULL
                WHERE id_numeros_serie = ? AND id_pedido = ? AND estatus IN (1, 2)
            ")->execute([(int)$asig['id_numeros_serie'], (int)$asig['idpedido']]);

            $db->prepare("
                UPDATE lgs_unidades
                SET id_pedido = NULL, folio_pedido = NULL, destino_pedido = NULL, updated_by = ?
                WHERE (id_numeros_serie = ? OR id_unidad IN (SELECT id_unidad FROM lgs_unidades_envios WHERE UPPER(TRIM(vin)) = UPPER(TRIM(?))))
                  AND id_pedido = ?
            ")->execute([$idUsuario, (int)$asig['id_numeros_serie'], (string)$asig['numero_serie'], (int)$asig['idpedido']]);

            $this->insertBitacoraEvento([
                'idpedido'          => (int)$asig['idpedido'],
                'tipo_evento'       => 'LIBERACION_VIN',
                'descripcion'       => "VIN {$asig['numero_serie']} liberado de la partida #{$asig['idpedido_detalle']}. Motivo: {$motivoFinal}",
                'estatus_anterior'  => $asig['estatus_pedido'],
                'estatus_nuevo'     => $asig['estatus_pedido'],
                'usuario_registro'  => $idUsuario,
                'origen'            => 'ADMIN',
            ]);

            $db->commit();

            return [
                'id_asignacion' => $idAsignacion,
                'numero_serie'  => $asig['numero_serie'],
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function recalcularBackorderPorId(PDO $db, int $idBackorder): void
    {
        $st = $db->prepare("SELECT idbackorder, cantidad_comprometida, cantidad_atendida_inicial, estatus FROM ped_backorder WHERE idbackorder = ? LIMIT 1");
        $st->execute([$idBackorder]);
        $bo = $st->fetch(PDO::FETCH_ASSOC);
        if (!$bo || strtoupper((string)$bo['estatus']) === 'CANCELADO') {
            return;
        }

        $cntSt = $db->prepare("SELECT COUNT(*) FROM ped_backorder_asignaciones WHERE idbackorder = ? AND estatus = 'ACTIVA'");
        $cntSt->execute([$idBackorder]);
        $asignada = (int)$cntSt->fetchColumn();

        $comp = (int)$bo['cantidad_comprometida'];
        $aten = (int)$bo['cantidad_atendida_inicial'];
        $pend = max(0, $comp - $aten - $asignada);
        $estatus = $pend === 0 ? 'ASIGNADO' : ($asignada > 0 ? 'PARCIAL' : 'PENDIENTE');

        $db->prepare("
            UPDATE ped_backorder
            SET cantidad_asignada = ?, cantidad_pendiente = ?, estatus = ?
            WHERE idbackorder = ?
        ")->execute([$asignada, $pend, $estatus, $idBackorder]);
    }

    private function sincronizarLgsUnidadDesdePedido(
        PDO $db,
        int $idNumerosSerie,
        string $numeroSerie,
        int $idPedido,
        string $folioPedido,
        int $idCliente,
        ?int $idSucursalEntrega,
        string $destinoTxt,
        int $idUsuario
    ): void {
        try {
            $idUbicacionDest = null;
            if (!empty($idSucursalEntrega)) {
                $uSt = $db->prepare("SELECT id_ubicacion FROM lgs_cat_ubicaciones WHERE id_sucursal_cli = ? AND activo = 1 LIMIT 1");
                $uSt->execute([$idSucursalEntrega]);
                $uRow = $uSt->fetch(PDO::FETCH_ASSOC);
                if (!empty($uRow['id_ubicacion'])) {
                    $idUbicacionDest = (int)$uRow['id_ubicacion'];
                }
            }

            $db->prepare("
                UPDATE lgs_unidades_envios
                SET id_numeros_serie = COALESCE(id_numeros_serie, ?)
                WHERE UPPER(TRIM(vin)) = UPPER(TRIM(?))
            ")->execute([$idNumerosSerie, $numeroSerie]);

            $luSt = $db->prepare("
                SELECT lu.id_lgs_unidad
                FROM lgs_unidades lu
                LEFT JOIN lgs_unidades_envios ue ON ue.id_unidad = lu.id_unidad
                WHERE lu.id_numeros_serie = ?
                   OR UPPER(TRIM(COALESCE(ue.vin, ''))) = UPPER(TRIM(?))
                LIMIT 1
            ");
            $luSt->execute([$idNumerosSerie, $numeroSerie]);
            $lu = $luSt->fetch(PDO::FETCH_ASSOC);

            if ($lu) {
                $db->prepare("
                    UPDATE lgs_unidades
                    SET id_numeros_serie = ?,
                        id_pedido = ?,
                        folio_pedido = ?,
                        id_cliente = COALESCE(id_cliente, ?),
                        destino_pedido = COALESCE(NULLIF(?, ''), destino_pedido),
                        id_ubicacion_destino = COALESCE(id_ubicacion_destino, ?),
                        updated_by = ?
                    WHERE id_lgs_unidad = ?
                ")->execute([
                    $idNumerosSerie,
                    $idPedido,
                    $folioPedido,
                    $idCliente > 0 ? $idCliente : null,
                    trim($destinoTxt),
                    $idUbicacionDest,
                    $idUsuario,
                    (int)$lu['id_lgs_unidad']
                ]);
            }
        } catch (Throwable $e) {
            error_log('sincronizarLgsUnidadDesdePedido: ' . $e->getMessage());
        }
    }

}
