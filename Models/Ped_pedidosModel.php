<?php

class Ped_pedidosModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
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
                COALESCE(resumen.total_pendientes,0) AS total_pendientes

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
                {$where}
            ORDER BY

                CASE p.estatus
                    WHEN 'PENDIENTE' THEN 1
                    WHEN 'EN_REVISION' THEN 2
                    WHEN 'AUTORIZADO' THEN 3
                    WHEN 'RECHAZADO' THEN 4
                    WHEN 'FINALIZADO' THEN 5
                    WHEN 'CANCELADO' THEN 6
                    ELSE 7
                END ASC,

                CASE p.prioridad
                    WHEN 'URGENTE' THEN 1
                    WHEN 'ALTA' THEN 2
                    WHEN 'MEDIA' THEN 3
                    WHEN 'BAJA' THEN 4
                    ELSE 5

                END ASC,
                p.fecha_pedido DESC,
                p.idpedido DESC
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
                ) AS importe_total


            FROM ped_pedidos AS p


            INNER JOIN cli_clientes AS c
                ON c.idcliente = p.idcliente


            LEFT JOIN cli_usuarios_acceso AS ua
                ON ua.idusuario_acceso = p.idusuario_acceso


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
                'importe_total' =>0
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
            'importe_total' =>floatval($request['importe_total'] ?? 0)
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
                u.marca,
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
     * PENDIENTE
     *      ↓
     * EN_REVISION
     *
     * Muy importante:
     *
     * El WHERE vuelve a validar PENDIENTE.
     *
     * ============================================================ */

    public function iniciarGestionPedidoModel(
        int $idpedido,
        int $idusuario
    ) {

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
     * Ejemplo:
     *
     * INICIO_GESTION
     *
     * PENDIENTE -> EN_REVISION
     *
     * ADMIN
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
     * Esta función la utilizan:
     *
     * selectPedidos()
     * countPedidos()
     * selectIndicadoresPedidos()
     *
     * Con esto evitamos tener filtros diferentes entre consultas.
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
         * Soporta que el campo sea DATE, DATETIME o VARCHAR
         * con formato YYYY-MM...
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

}
