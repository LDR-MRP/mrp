<?php





class Ped_cotizacionesModel extends Mysql

{



    public function __construct()

    {

        parent::__construct();

    }




    /** Listado agrupado por propuesta, sin duplicar importes por cada cotización. */
    public function selectPropuestasComerciales(array $filtros = []): array
    {
        $sql = "SELECT
                    pc.idpropuesta, pc.folio_propuesta, pc.clave,
                    pc.idcliente, pc.idpedido_origen, pc.tipo_propuesta,
                    pc.estatus, pc.fecha_creacion,
                    cli.codigo_cliente, cli.razon_social, cli.nombre_comercial,
                    p.folio_pedido,
                    COALESCE(resumen.total_cotizaciones, 0) AS total_cotizaciones,
                    COALESCE(resumen.cotizaciones_autorizadas, 0) AS cotizaciones_autorizadas,
                    COALESCE(resumen.importe_total, 0) AS importe_total
                FROM ped_propuestas_comerciales pc
                INNER JOIN cli_clientes cli ON cli.idcliente = pc.idcliente
                LEFT JOIN ped_pedidos p ON p.idpedido = pc.idpedido_origen
                LEFT JOIN (
                    SELECT idpropuesta,
                           COUNT(*) AS total_cotizaciones,
                           SUM(CASE WHEN estatus IN (
                               'AUTORIZADA','PENDIENTE_RESPUESTA',
                               'EN_AJUSTE_CLIENTE','ACEPTADA'
                           ) THEN 1 ELSE 0 END) AS cotizaciones_autorizadas,
                           SUM(total) AS importe_total
                    FROM ped_cotizaciones
                    WHERE estado = 2 AND idpropuesta IS NOT NULL
                    GROUP BY idpropuesta
                ) resumen ON resumen.idpropuesta = pc.idpropuesta
                WHERE pc.estado = 2";
        $params = [];
        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $sql .= " AND (pc.folio_propuesta LIKE ? OR pc.clave LIKE ?
                        OR cli.codigo_cliente LIKE ? OR cli.razon_social LIKE ?
                        OR cli.nombre_comercial LIKE ? OR p.folio_pedido LIKE ?)";
            for ($i = 0; $i < 6; $i++) $params[] = '%' . $buscar . '%';
        }
        $desde = trim((string) ($filtros['desde'] ?? ''));
        $hasta = trim((string) ($filtros['hasta'] ?? ''));
        foreach (['desde' => $desde, 'hasta' => $hasta] as $campo => $fecha) {
            if ($fecha === '') continue;
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
            if (!$date || $date->format('Y-m-d') !== $fecha) {
                throw new InvalidArgumentException('Fecha de filtro inválida.');
            }
            $sql .= $campo === 'desde' ? ' AND pc.fecha_creacion >= ?' : ' AND pc.fecha_creacion < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $fecha;
        }
        $tipo = strtoupper(trim((string) ($filtros['tipo'] ?? '')));
        if ($tipo !== '') {
            if (!in_array($tipo, ['DIRECTA','PEDIDO'], true)) throw new InvalidArgumentException('Origen inválido.');
            $sql .= ' AND pc.tipo_propuesta = ?';
            $params[] = $tipo;
        }
        $estatus = strtoupper(trim((string) ($filtros['estatus'] ?? '')));
        $permitidos = ['BORRADOR','EN_PROCESO','PARCIALMENTE_AUTORIZADA','AUTORIZADA',
                       'PARCIALMENTE_ENVIADA','ENVIADA','PARCIALMENTE_ACEPTADA','ACEPTADA','CANCELADA'];
        if ($estatus !== '') {
            if (!in_array($estatus, $permitidos, true)) throw new InvalidArgumentException('Estatus inválido.');
            $sql .= ' AND pc.estatus = ?';
            $params[] = $estatus;
        }
        $sql .= ' ORDER BY pc.fecha_creacion DESC, pc.idpropuesta DESC';
        return $this->select_all($sql, $params);
    }

    /** Indicadores por propuesta, no por cotización. */
    public function selectDashboardPropuestas(): array
    {
        $sql = "SELECT COUNT(*) AS total_propuestas,
                    COALESCE(SUM(estatus = 'BORRADOR'),0) AS borradores,
                    COALESCE(SUM(estatus IN ('EN_PROCESO','PARCIALMENTE_AUTORIZADA')),0) AS en_proceso,
                    COALESCE(SUM(estatus = 'AUTORIZADA'),0) AS autorizadas,
                    COALESCE(SUM(estatus IN ('ENVIADA','PARCIALMENTE_ENVIADA')),0) AS enviadas,
                    COALESCE(SUM(estatus = 'ACEPTADA'),0) AS aceptadas,
                    COALESCE(SUM(estatus = 'CANCELADA'),0) AS canceladas
                FROM ped_propuestas_comerciales WHERE estado = 2";
        $resumen = $this->select($sql) ?: [];
        $montos = $this->select("SELECT
                    COALESCE(SUM(CASE WHEN pc.estatus <> 'CANCELADA' THEN c.total ELSE 0 END),0) AS valor_propuesto,
                    COALESCE(SUM(CASE WHEN c.estatus = 'ACEPTADA' THEN c.total ELSE 0 END),0) AS valor_aceptado
                FROM ped_cotizaciones c
                INNER JOIN ped_propuestas_comerciales pc ON pc.idpropuesta = c.idpropuesta
                WHERE c.estado = 2 AND pc.estado = 2") ?: [];
        foreach (['total_propuestas','borradores','en_proceso','autorizadas','enviadas','aceptadas','canceladas'] as $campo) {
            $resumen[$campo] = (int) ($resumen[$campo] ?? 0);
        }
        $resumen['valor_propuesto'] = (float) ($montos['valor_propuesto'] ?? 0);
        $resumen['valor_aceptado'] = (float) ($montos['valor_aceptado'] ?? 0);
        return $resumen;
    }

    /* ============================================================

       LISTADO DE COTIZACIONES

    ============================================================ */



    public function selectCotizaciones(

        array $filtros = []

    ) {



        $buscar =trim($filtros['buscar'] ?? "");

        $desde =trim($filtros['desde'] ?? "");

        $hasta =trim($filtros['hasta'] ?? "");

        $tipo =strtoupper(trim($filtros['tipo'] ?? ""));

        $estatus =strtoupper(trim($filtros['estatus'] ?? ""));



        $sql = "SELECT

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



            $sql .= "AND (

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



            $arrData[] =$termino;

            $arrData[] =$termino;

            $arrData[] =$termino;

            $arrData[] =$termino;

            $arrData[] =$termino;

            $arrData[] =$termino;

        }





        /* ========================================================

           FECHA DESDE

        ======================================================== */



        if (

            $desde !== ""

        ) {



            $sql .= "AND DATE(

                    c.fecha_cotizacion

                ) >= ?

            ";



            $arrData[] =$desde;

        }





        /* ========================================================

           FECHA HASTA

        ======================================================== */



        if (

            $hasta !== ""

        ) {



            $sql .= "AND DATE(

                    c.fecha_cotizacion

                ) <= ?

            ";

            $arrData[] =$hasta;

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

            $sql .= "AND c.tipo_cotizacion = ?";

            $arrData[] =$tipo;

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
              'PENDIENTE_AUTORIZACION',
              'EN_AJUSTE_INTERNO',
              'AUTORIZADA',
              'EN_AJUSTE_CLIENTE',
              'RECHAZADA_CLIENTE',

            'CANCELADA'



        ];



        if (in_array($estatus,$estatusPermitidos,true)) {



            $sql .= "AND c.estatus = ?";

            $arrData[] =$estatus; }





        /* ========================================================

           ORDEN

        ======================================================== */



        $sql .= "

            ORDER BY

                c.fecha_cotizacion DESC,

                c.idcotizacion DESC

        ";



        return $this->select_all($sql,$arrData);

    }





    /* ============================================================

       DASHBOARD

    ============================================================ */



    public function selectDashboardCotizaciones()

    {

        $sql = "SELECT

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



        $request =$this->select($sql);



        if (empty($request)) {



            return [

                'total_cotizaciones' =>0,

                'pendientes' =>0,

                'en_ajuste' =>0,

                'aceptadas' =>0,

                'rechazadas' =>0,

                'valor_cotizado' =>0,

                'valor_aceptado' =>0

            ];

        }



        return [



            'total_cotizaciones' =>(int) ($request['total_cotizaciones'] ?? 0),

            'pendientes' =>(int) ($request['pendientes'] ?? 0),

            'en_ajuste' =>(int) ($request['en_ajuste']?? 0),

            'aceptadas' =>(int) ($request['aceptadas'] ?? 0),

            'rechazadas' =>(int) ($request['rechazadas'] ?? 0),

            'valor_cotizado' =>(float) ($request['valor_cotizado'] ?? 0),

            'valor_aceptado' =>(float) ($request['valor_aceptado'] ?? 0)

        ];

    }





    /* ============================================================

       DISTRIBUIDORES

    ============================================================ */



    public function selectDistribuidoresCotizacion()

    {

        $sql = "SELECT

                c.idcliente,

                c.codigo_cliente,

                c.clave_distribuidor,

                c.razon_social,

                c.nombre_comercial,

                c.correo,

                c.telefono,

                c.celular,

                c.requiere_acceso_portal,



                (

                    SELECT ua.idusuario_acceso

                    FROM cli_usuarios_acceso ua

                    WHERE

                        ua.idcliente = c.idcliente

                        AND ua.estado = 2

                    ORDER BY

                        ua.idusuario_acceso ASC

                    LIMIT 1

                ) AS idusuario_acceso



            FROM cli_clientes c

            WHERE

                c.estado = 2

            ORDER BY

                COALESCE(

                    NULLIF(

                        c.nombre_comercial,

                        ''

                    ),

                    c.razon_social

                ) ASC

        ";



        return $this->select_all(

            $sql,

            []

        );

    }



    /* ============================================================

       CLIENTE

    ============================================================ */



    public function selectClienteCotizacion(int $idcliente) {



        $sql = "SELECT

                idcliente,

                codigo_cliente,

                clave_distribuidor,

                razon_social,

                nombre_comercial,

                correo,

                telefono,

                celular,

                requiere_acceso_portal



            FROM cli_clientes

            WHERE

                idcliente = ?

                AND estado = 2



            LIMIT 1

        ";

        return $this->select($sql,[$idcliente]);

    }



    /* ============================================================

       PEDIDOS DISPONIBLES

    ============================================================ */



    public function selectPedidosCotizables(): array

    {

        $sql = "SELECT

                p.idpedido,

                p.idcliente,

                p.idusuario_acceso,

                p.folio_pedido,

                p.clave,

                p.fecha_pedido,

                p.fecha_requerida,

                p.subtotal,

                p.iva,

                p.total,

                p.estatus,



                c.codigo_cliente,

                c.clave_distribuidor,

                c.razon_social,

                c.nombre_comercial



            FROM ped_pedidos p



            INNER JOIN cli_clientes c

                ON c.idcliente =

                   p.idcliente



            WHERE

                p.estado IN (1, 2)



                AND p.estatus IN (

                    'PENDIENTE',

                    'EN_REVISION'

                )



                AND NOT EXISTS (

                    SELECT 1

                    FROM ped_cotizaciones cot

                    WHERE

                        cot.idpedido_origen =

                            p.idpedido

                        AND cot.estado = 2



                        AND cot.estatus NOT IN (

                            'CANCELADA',

                            'RECHAZADA'

                        )

                )

            ORDER BY

                p.fecha_pedido DESC,

                p.idpedido DESC

        ";





        return $this->select_all(

            $sql,

            []

        );

    }



    /* ============================================================

       PEDIDO

    ============================================================ */



    public function selectPedidoCotizacion(

        int $idpedido

    ) {



        $sql = "SELECT

                p.idpedido,

                p.idcliente,

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



                c.codigo_cliente,

                c.clave_distribuidor,

                c.razon_social,

                c.nombre_comercial,

                c.correo AS correo_cliente,



                ua.nombre,

                ua.apellido,

                ua.correo AS correo_usuario



            FROM ped_pedidos p



            INNER JOIN cli_clientes c

                ON c.idcliente =

                   p.idcliente



            LEFT JOIN cli_usuarios_acceso ua

                ON ua.idusuario_acceso =

                   p.idusuario_acceso

            WHERE

                p.idpedido = ?

                AND p.estado IN (1, 2)



            LIMIT 1

        ";



        return $this->select($sql,[$idpedido]);

    }



    /* ============================================================

       DETALLE PEDIDO

    ============================================================ */



    public function selectDetallesPedidoCotizacion(int $idpedido): array {



        $sql = "SELECT

                d.idpedido_detalle,

                d.idpedido,

                d.idunidad,

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



                u.modelo,

                u.clave_modelo,

                u.nombre,

                u.version,

                u.descripcion,

                u.anio,

                u.marca,

                u.motor,

                u.stock,

                u.precio_estimado



            FROM ped_pedidos_detalle d

            INNER JOIN web_unidades u

                ON u.idunidad =

                   d.idunidad

            WHERE

                d.idpedido = ?

                AND d.estado = 2

            ORDER BY

                d.idpedido_detalle ASC

        ";





        return $this->select_all(

            $sql,

            [

                $idpedido

            ]

        );

    }



    /* ============================================================

       VALIDAR DETALLE PEDIDO

    ============================================================ */



    public function selectPedidoDetalleCotizacion(int $idpedidoDetalle,int $idpedido) {



        $sql = "SELECT

                idpedido_detalle,

                idpedido,

                idunidad,

                cantidad_solicitada,

                precio_unitario,

                estatus



            FROM ped_pedidos_detalle



            WHERE

                idpedido_detalle = ?

                AND idpedido = ?

                AND estado = 2



            LIMIT 1

        ";

        return $this->select($sql,[$idpedidoDetalle,$idpedido]);

    }



    /* ============================================================

       CATÁLOGO UNIDADES

    ============================================================ */



    public function selectUnidadesCotizacion(): array

    {

        $sql = "SELECT

                idunidad,

                modelo,

                clave_modelo,

                nombre,

                version,

                descripcion,

                anio,

                marca,

                motor,

                stock,

                precio_estimado,
                precio_publico,
                precio_dealer

            FROM web_unidades

            WHERE

                estado = 2

            ORDER BY

                modelo ASC,

                nombre ASC,

                version ASC

        ";



        return $this->select_all($sql,[]);

    }



    /* ============================================================

       UNA UNIDAD

    ============================================================ */



    public function selectUnidadCotizacion(int $idunidad) {



        $sql = "SELECT

                idunidad,

                modelo,

                clave_modelo,

                nombre,

                version,

                descripcion,

                anio,

                marca,

                motor,

                stock,

                precio_estimado,
                precio_publico,
                precio_dealer

            FROM web_unidades

            WHERE

                idunidad = ?

                AND estado = 2



            LIMIT 1

        ";



        return $this->select($sql,[$idunidad]);

    }



    /* ============================================================

       GENEAR FOLIO

    ============================================================ */



    public function generarFolioCotizacion(): string

    {

        $fecha =date('Ymd');

        $prefijo ="COT-{$fecha}-";



        $sql = "SELECT

                folio_cotizacion

            FROM ped_cotizaciones

            WHERE

                folio_cotizacion LIKE ?

            ORDER BY

                idcotizacion DESC

            LIMIT 1

        ";

        $ultimo =$this->select($sql,[$prefijo . '%']);



        $consecutivo =1;



        if (!empty($ultimo['folio_cotizacion'])) {



            $partes =explode('-',$ultimo['folio_cotizacion']);

            $numero =intval(end($partes));

            $consecutivo =$numero + 1;

        }



        return

            $prefijo

            . str_pad(

                $consecutivo,

                4,

                '0',

                STR_PAD_LEFT

            );

    }



    /* ============================================================

       INSERT CABECERA

    ============================================================ */



    public function insertCotizacion(array $data) {



        $sql = "INSERT INTO ped_cotizaciones (

                idcliente,

                idusuario_acceso,

                idpedido_origen,

                idpedido_generado,

                folio_cotizacion,

                clave,

                tipo_cotizacion,

                version_actual,

                fecha_cotizacion,

                fecha_vigencia,

                moneda,

                subtotal,

                descuento,

                iva,

                total,

                condiciones_pago,

                condiciones_entrega,

                tiempo_entrega,

                observaciones,

                estatus,

                usuario_creacion,

                estado,

                fecha_creacion,

                fecha_actualizacion

            )

            VALUES (

                ?, ?, ?, NULL,

                ?, ?, ?, 1,

                NOW(), ?, ?,

                ?, ?, ?, ?,

                ?, ?, ?, ?,

                'BORRADOR',

                ?, 2,

                NOW(), NOW()

            )

        ";

        return intval(

            $this->insert(

                $sql,

                [

                    intval($data['idcliente']),

                    $data['idusuario_acceso'],

                    $data['idpedido_origen'],

                    $data['folio_cotizacion'],

                    $data['clave'],

                    $data['tipo_cotizacion'],

                    $data['fecha_vigencia'],

                    $data['moneda'],

                    floatval($data['subtotal']),

                    floatval($data['descuento']),

                    floatval($data['iva']),

                    floatval($data['total']),

                    $data['condiciones_pago'],

                    $data['condiciones_entrega'],

                    $data['tiempo_entrega'],

                    $data['observaciones'],

                    intval($data['usuario_creacion'])

                ]

            )

        );

    }



    /* ============================================================

       INSERT VERSION

    ============================================================ */



    public function insertCotizacionVersion(array $data){



        $sql = "INSERT INTO ped_cotizaciones_versiones (

                idcotizacion,

                numero_version,

                subtotal,

                descuento,

                iva,

                total,

                fecha_vigencia,

                condiciones_pago,

                condiciones_entrega,

                tiempo_entrega,

                observaciones,

                motivo_version,

                estatus,

                fecha_envio,

                usuario_creacion,

                estado,

                fecha_creacion,

                fecha_actualizacion

            )

            VALUES (

                ?, ?, ?, ?, ?, ?,

                ?, ?, ?, ?, ?, ?,

                'BORRADOR',

                NULL,

                ?,

                2,

                NOW(),

                NOW()

            )

        ";



        return intval(

            $this->insert(

                $sql,

                [

                    intval($data['idcotizacion']),

                    intval($data['numero_version']),

                    floatval($data['subtotal']),

                    floatval($data['descuento']),

                    floatval($data['iva']),

                    floatval($data['total']),

                    $data['fecha_vigencia'],

                    $data['condiciones_pago'],

                    $data['condiciones_entrega'],

                    $data['tiempo_entrega'],

                    $data['observaciones'],

                    $data['motivo_version'],

                    intval($data['usuario_creacion'])

                ]

            )

        );

    }



    /* ============================================================

       INSERT DETALLE VERSION

    ============================================================ */



    public function insertCotizacionVersionDetalle(array $data){



        $sql = "INSERT INTO ped_cotizaciones_versiones_detalle (

                idversion,

                idpedido_detalle,

                idunidad,

                cantidad,

                precio_lista,

                precio_unitario,

                descuento_porcentaje,

                descuento,

                subtotal,

                iva,

                total,

                observaciones,

                estado,

                fecha_creacion,

                fecha_actualizacion

            )

            VALUES (

                ?, ?, ?, ?, ?, ?,

                ?, ?, ?, ?, ?, ?,

                2,

                NOW(),

                NOW()

            )

        ";



        return intval(

            $this->insert(

                $sql,

                [

                    intval($data['idversion']),

                    $data['idpedido_detalle'],

                    intval($data['idunidad']),

                    intval($data['cantidad']),

                    floatval($data['precio_lista']),

                    floatval($data['precio_unitario']),

                    floatval($data['descuento_porcentaje']),

                    floatval( $data['descuento']),

                    floatval($data['subtotal']),

                    floatval($data['iva']),

                    floatval($data['total']),

                    $data['observaciones']

                ]

            )

        );

    }



    /* ============================================================

       BITÁCORA

    ============================================================ */



    public function insertCotizacionBitacora(array $data) {



        $sql = "INSERT INTO ped_cotizaciones_bitacora (

                idcotizacion,

                idversion,

                tipo_evento,

                descripcion,

                estatus_anterior,

                estatus_nuevo,

                usuario_registro,

                idusuario_acceso,

                origen,

                ip,

                fecha_creacion

            )

            VALUES (

                ?, ?, ?, ?, ?, ?,

                ?, ?, ?, ?,

                NOW()

            )

        ";



        return intval(

            $this->insert(

                $sql,

                [

                    intval($data['idcotizacion']),

                    $data['idversion'],

                    $data['tipo_evento'],

                    $data['descripcion'],

                    $data['estatus_anterior'],

                    $data['estatus_nuevo'],

                    $data['usuario_registro'],

                    $data['idusuario_acceso'],

                    $data['origen'],

                    $data['ip']

                ]

            )

        );

    }























public function crearPropuestaComercial(array $datos, int $idUsuario): array

{

    $pdo = $this->getConexion();



    $idCliente = (int) ($datos['idcliente'] ?? 0);

    $tipo = strtoupper(trim((string) ($datos['tipo_propuesta'] ?? '')));

    $idPedido = !empty($datos['idpedido_origen'])

        ? (int) $datos['idpedido_origen']

        : null;



    $modelos = $datos['modelos'] ?? [];



    if ($idUsuario <= 0 || $idCliente <= 0) {

        throw new InvalidArgumentException('Usuario o cliente inválido.');

    }



    if (!in_array($tipo, ['DIRECTA', 'PEDIDO'], true)) {

        throw new InvalidArgumentException('Tipo de propuesta inválido.');

    }



    if (!is_array($modelos) || count($modelos) === 0) {

        throw new InvalidArgumentException('Selecciona al menos un modelo.');

    }



    if ($tipo === 'PEDIDO' && !$idPedido) {

        throw new InvalidArgumentException('Selecciona un pedido de origen.');

    }



    if ($tipo === 'DIRECTA' && $idPedido !== null) {

        throw new InvalidArgumentException('Una propuesta directa no debe tener pedido de origen.');

    }



    // Validaciones de catálogo.

    $cliente = $this->select(

        "SELECT idcliente

         FROM cli_clientes

         WHERE idcliente = ? AND estado = 2",

        [$idCliente]

    );



    if (!$cliente) {

        throw new InvalidArgumentException('El cliente no está activo.');

    }



    if ($tipo === 'PEDIDO') {

        $pedido = $this->select(

            "SELECT idpedido

             FROM ped_pedidos

             WHERE idpedido = ?

               AND idcliente = ?

               AND estado = 2

               AND estatus NOT IN ('CANCELADO', 'FACTURADO', 'ENTREGADO')",

            [$idPedido, $idCliente]

        );



        if (!$pedido) {

            throw new InvalidArgumentException('Pedido no válido para este cliente.');

        }

    }



    // Preparar y validar modelos antes de escribir.

    $items = [];

    $lineasUsadas = [];
    $unidadesUsadas = [];



    foreach ($modelos as $modelo) {

        $idUnidad = (int) ($modelo['idunidad'] ?? 0);

        $cantidad = filter_var(

            $modelo['cantidad'] ?? null,

            FILTER_VALIDATE_INT

        );



        if ($idUnidad <= 0 || $cantidad === false || $cantidad <= 0) {

            throw new InvalidArgumentException('Modelo o cantidad inválidos.');

        }



        if (isset($unidadesUsadas[$idUnidad])) {
            throw new InvalidArgumentException('El mismo modelo no puede repetirse en la propuesta.');
        }
        $unidadesUsadas[$idUnidad] = true;

        $unidad = $this->select(

            "SELECT idunidad, precio_publico, precio_dealer

             FROM web_unidades

             WHERE idunidad = ? AND estado = 2",

            [$idUnidad]

        );



        if (!$unidad) {

            throw new InvalidArgumentException('El modelo no está disponible.');

        }



        if ($unidad['precio_publico'] === null ||

            $unidad['precio_dealer'] === null) {

            throw new InvalidArgumentException(

                'El modelo ' . $idUnidad . ' no tiene precios configurados.'

            );

        }



        $precioPublico = (float) $unidad['precio_publico'];

        $precioDealer = (float) $unidad['precio_dealer'];



        if ($precioPublico < 0 || $precioDealer < 0) {

            throw new InvalidArgumentException('Se encontraron precios negativos.');

        }



        $idPedidoDetalle = null;



        if ($tipo === 'PEDIDO') {

            $idPedidoDetalle = (int) ($modelo['idpedido_detalle'] ?? 0);



            if ($idPedidoDetalle <= 0 ||

                isset($lineasUsadas[$idPedidoDetalle])) {

                throw new InvalidArgumentException(

                    'Línea de pedido inválida o duplicada.'

                );

            }



            $linea = $this->select(

                "SELECT idpedido_detalle, cantidad_solicitada

                 FROM ped_pedidos_detalle

                 WHERE idpedido_detalle = ?

                   AND idpedido = ?

                   AND idunidad = ?

                   AND estado = 2",

                [$idPedidoDetalle, $idPedido, $idUnidad]

            );



            if (!$linea || $cantidad > (int) $linea['cantidad_solicitada']) {

                throw new InvalidArgumentException(

                    'La cantidad no corresponde a la línea del pedido.'

                );

            }



            $lineasUsadas[$idPedidoDetalle] = true;

        }



        // La propuesta inicia sin descuento.

        $subtotal = round($precioDealer * $cantidad, 2);

        $iva = round($subtotal * 0.16, 2);

        $total = round($subtotal + $iva, 2);



        $items[] = [

            'idunidad' => $idUnidad,

            'idpedido_detalle' => $idPedidoDetalle,

            'cantidad' => $cantidad,

            'precio_publico' => $precioPublico,

            'precio_dealer' => $precioDealer,

            'subtotal' => $subtotal,

            'iva' => $iva,

            'total' => $total

        ];

    }



    if ($pdo->inTransaction()) {

        throw new RuntimeException(

            'Ya existe una transacción activa en esta conexión.'

        );

    }



    $pdo->beginTransaction();



    try {

        // Folio provisional único, sustituido con el ID generado.

        $temporal = 'TMP-' . bin2hex(random_bytes(12));

        $clave = bin2hex(random_bytes(20));



        $idPropuesta = (int) $this->insert(

            "INSERT INTO ped_propuestas_comerciales

             (folio_propuesta, clave, idcliente, idpedido_origen,

              tipo_propuesta, estatus, usuario_creacion, estado)

             VALUES (?, ?, ?, ?, ?, 'BORRADOR', ?, 2)",

            [

                $temporal,

                $clave,

                $idCliente,

                $idPedido,

                $tipo,

                $idUsuario

            ]

        );



        if ($idPropuesta <= 0) {

            throw new RuntimeException('No se pudo crear la propuesta.');

        }



        $folioPropuesta = 'PCO-' . str_pad(

            (string) $idPropuesta, 8, '0', STR_PAD_LEFT

        );



        $this->update(

            "UPDATE ped_propuestas_comerciales

             SET folio_propuesta = ?

             WHERE idpropuesta = ?",

            [$folioPropuesta, $idPropuesta]

        );



        $cotizaciones = [];



        foreach ($items as $item) {

            $temporalCot = 'TMP-' . bin2hex(random_bytes(12));

            $claveCot = bin2hex(random_bytes(20));



            $idCotizacion = (int) $this->insert(

                "INSERT INTO ped_cotizaciones

                 (idpropuesta, idunidad, idcliente, idpedido_origen,

                  folio_cotizacion, clave, tipo_cotizacion,

                  version_actual, fecha_cotizacion, moneda,

                  subtotal, descuento, iva, total,

                  estatus, usuario_creacion, estado)

                 VALUES

                 (?, ?, ?, ?, ?, ?, ?, 1, NOW(), 'MXN',

                  ?, 0, ?, ?, 'BORRADOR', ?, 2)",

                [

                    $idPropuesta,

                    $item['idunidad'],

                    $idCliente,

                    $idPedido,

                    $temporalCot,

                    $claveCot,

                    $tipo,

                    $item['subtotal'],

                    $item['iva'],

                    $item['total'],

                    $idUsuario

                ]

            );



            if ($idCotizacion <= 0) {

                throw new RuntimeException('No se pudo crear una cotización.');

            }



            $folioCotizacion = 'COT-' . str_pad(

                (string) $idCotizacion, 8, '0', STR_PAD_LEFT

            );



            $this->update(

                "UPDATE ped_cotizaciones

                 SET folio_cotizacion = ?

                 WHERE idcotizacion = ?",

                [$folioCotizacion, $idCotizacion]

            );



            $idVersion = (int) $this->insert(

                "INSERT INTO ped_cotizaciones_versiones

                 (idcotizacion, numero_version,

                  subtotal, descuento, iva, total,

                  estatus, usuario_creacion, estado)

                 VALUES

                 (?, 1, ?, 0, ?, ?, 'BORRADOR', ?, 2)",

                [

                    $idCotizacion,

                    $item['subtotal'],

                    $item['iva'],

                    $item['total'],

                    $idUsuario

                ]

            );



            if ($idVersion <= 0) {

                throw new RuntimeException('No se pudo crear la versión.');

            }



            $idDetalle = (int) $this->insert(

                "INSERT INTO ped_cotizaciones_versiones_detalle

                 (idversion, idpedido_detalle, idunidad,

                  precio_publico, precio_dealer,

                  tipo_descuento, descuento_unitario,

                  precio_cotizado, cantidad,

                  precio_lista, precio_unitario,

                  descuento_porcentaje, descuento,

                  subtotal, iva, total, estado)

                 VALUES

                 (?, ?, ?, ?, ?,

                  'MONTO', 0, ?, ?,

                  ?, ?, 0, 0,

                  ?, ?, ?, 2)",

                [

                    $idVersion,

                    $item['idpedido_detalle'],

                    $item['idunidad'],

                    $item['precio_publico'],

                    $item['precio_dealer'],

                    $item['precio_dealer'],

                    $item['cantidad'],

                    $item['precio_publico'],

                    $item['precio_dealer'],

                    $item['subtotal'],

                    $item['iva'],

                    $item['total']

                ]

            );



            if ($idDetalle <= 0) {

                throw new RuntimeException('No se pudo crear el detalle.');

            }



            $idBitacora = $this->insertCotizacionBitacora([
                'idcotizacion' => $idCotizacion,
                'idversion' => $idVersion,
                'tipo_evento' => 'COTIZACION_CREADA',
                'descripcion' => 'Cotización creada en borrador desde la propuesta ' . $folioPropuesta,
                'estatus_anterior' => null,
                'estatus_nuevo' => 'BORRADOR',
                'usuario_registro' => $idUsuario,
                'idusuario_acceso' => null,
                'origen' => 'ADMIN',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            if ($idBitacora <= 0) {
                throw new RuntimeException('No se pudo registrar la bitácora.');
            }

            $cotizaciones[] = [

                'idcotizacion' => $idCotizacion,

                'folio' => $folioCotizacion,

                'idversion' => $idVersion,

                'idunidad' => $item['idunidad']

            ];

        }



        $pdo->commit();



        return [

            'idpropuesta' => $idPropuesta,

            'folio_propuesta' => $folioPropuesta,
            'clave' => $clave,

            'estatus' => 'BORRADOR',

            'cotizaciones' => $cotizaciones

        ];



    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {

            $pdo->rollBack();

        }



        throw $e;

    }

}































    // NEUVAS FUYNCIONES


    public function selectPropuestaPorClave(string $clave)
    {
        return $this->select(
            "SELECT pc.idpropuesta, pc.folio_propuesta, pc.clave, pc.tipo_propuesta,
                    pc.estatus, pc.fecha_creacion, pc.idpedido_origen, pc.usuario_creacion,
                    cli.codigo_cliente, cli.razon_social, cli.nombre_comercial,
                    cli.correo AS correo_distribuidor, cli.telefono AS telefono_distribuidor,
                    p.folio_pedido
             FROM ped_propuestas_comerciales pc
             INNER JOIN cli_clientes cli ON cli.idcliente = pc.idcliente
             LEFT JOIN ped_pedidos p ON p.idpedido = pc.idpedido_origen
             WHERE pc.clave = ? AND pc.estado = 2 LIMIT 1",
            [$clave]
        );
    }

    public function selectCotizacionesPorPropuesta(int $idpropuesta): array
    {
        $cotizaciones = $this->select_all(
            "SELECT c.idcotizacion, c.clave, c.folio_cotizacion, c.estatus,
                    c.version_actual, c.moneda, c.fecha_vigencia,
                    c.condiciones_pago, c.condiciones_entrega,
                    c.tiempo_entrega, c.observaciones,
                    c.subtotal, c.descuento, c.iva, c.total,
                    u.modelo, u.nombre, u.version AS version_modelo, u.clave_modelo
             FROM ped_cotizaciones c
             INNER JOIN web_unidades u ON u.idunidad = c.idunidad
             WHERE c.idpropuesta = ? AND c.estado = 2
             ORDER BY c.idcotizacion ASC",
            [$idpropuesta]
        );
        foreach ($cotizaciones as &$cot) {
            $cot['detalle'] = $this->select(
                "SELECT d.idversion_detalle, d.cantidad, d.nombre_cliente_final,
                        d.nombre_competidor, d.modelo_competidor, d.precio_competidor,
                        d.precio_publico, d.precio_dealer, d.precio_cotizado,
                        d.tipo_descuento, d.descuento_unitario, d.descuento_porcentaje,
                        d.precio_lista, d.precio_unitario, d.descuento,
                        d.subtotal, d.iva, d.total, d.observaciones
                 FROM ped_cotizaciones_versiones v
                 INNER JOIN ped_cotizaciones_versiones_detalle d ON d.idversion = v.idversion
                 WHERE v.idcotizacion = ? AND v.numero_version = ?
                   AND v.estado = 2 AND d.estado = 2 LIMIT 1",
                [(int)$cot['idcotizacion'], (int)$cot['version_actual']]
            ) ?: [];
            unset($cot['idcotizacion']);
        }
        unset($cot);
        return $cotizaciones;
    }

    /** Edita únicamente la versión vigente de una cotización en borrador. */
    public function actualizarBorradorCotizacion(array $input, int $usuario): array
    {
        $clavePropuesta = trim((string)($input['clave_propuesta'] ?? ''));
        $claveCot = trim((string)($input['clave_cotizacion'] ?? ''));
        if (!preg_match('/^[a-f0-9]{40}$/i', $clavePropuesta) ||
            !preg_match('/^[a-f0-9]{40}$/i', $claveCot)) {
            throw new InvalidArgumentException('Claves de propuesta o cotización inválidas.');
        }

        $cantidad = filter_var($input['cantidad'] ?? null, FILTER_VALIDATE_INT);
        if ($cantidad === false || $cantidad < 1 || $cantidad > 100000) {
            throw new InvalidArgumentException('La cantidad debe ser un entero entre 1 y 100000.');
        }
        $tipo = strtoupper(trim((string)($input['tipo_descuento'] ?? '')));
        if (!in_array($tipo, ['MONTO', 'PORCENTAJE'], true)) {
            throw new InvalidArgumentException('Selecciona descuento por monto o porcentaje.');
        }
        $valor = $this->validarImporteComercial($input['valor_descuento'] ?? null, 'Descuento', 4);
        if ($tipo === 'PORCENTAJE' && $valor > 100) {
            throw new InvalidArgumentException('El descuento porcentual no puede superar el 100%.');
        }
        $precioCompetidor = null;
        if (isset($input['precio_competidor']) && $input['precio_competidor'] !== '') {
            $precioCompetidor = $this->validarImporteComercial($input['precio_competidor'], 'Precio del competidor', 2);
        }
        $campos = [];
        foreach ([
            'nombre_cliente_final' => 200, 'nombre_competidor' => 200,
            'modelo_competidor' => 200, 'observaciones_detalle' => 500,
            'condiciones_pago' => 1000, 'condiciones_entrega' => 1000,
            'tiempo_entrega' => 255, 'observaciones' => 3000
        ] as $nombre => $max) {
            $texto = trim((string)($input[$nombre] ?? ''));
            if (mb_strlen($texto, 'UTF-8') > $max) {
                throw new InvalidArgumentException('El campo ' . $nombre . ' excede el límite de caracteres.');
            }
            $campos[$nombre] = $texto !== '' ? $texto : null;
        }
        $vigencia = trim((string)($input['fecha_vigencia'] ?? ''));
        if ($vigencia !== '') {
            $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $vigencia);
            if (!$fecha || $fecha->format('Y-m-d') !== $vigencia) {
                throw new InvalidArgumentException('Fecha de vigencia inválida.');
            }
        }
        $pdo = $this->getConexion();
        if ($pdo->inTransaction()) throw new RuntimeException('Ya existe una transacción activa.');
        $pdo->beginTransaction();
        try {
            $cot = $this->select(
                "SELECT c.idcotizacion, c.version_actual, c.estatus,
                        p.estatus AS estatus_propuesta, p.usuario_creacion AS creador_propuesta
                 FROM ped_cotizaciones c
                 INNER JOIN ped_propuestas_comerciales p ON p.idpropuesta = c.idpropuesta
                 WHERE c.clave = ? AND p.clave = ? AND c.estado = 2 AND p.estado = 2
                 FOR UPDATE",
                [$claveCot, $clavePropuesta]
            );
            if (!$cot || (int)$cot['creador_propuesta'] !== $usuario || $cot['estatus'] !== 'BORRADOR' || $cot['estatus_propuesta'] !== 'BORRADOR') {
                throw new InvalidArgumentException('La cotización o propuesta ya no está en borrador.');
            }
            $version = $this->select(
                "SELECT idversion, estatus FROM ped_cotizaciones_versiones
                 WHERE idcotizacion = ? AND numero_version = ? AND estado = 2 FOR UPDATE",
                [(int)$cot['idcotizacion'], (int)$cot['version_actual']]
            );
            if (!$version || $version['estatus'] !== 'BORRADOR') {
                throw new InvalidArgumentException('La versión vigente no está disponible para edición.');
            }
            $det = $this->select(
                "SELECT idversion_detalle, precio_dealer FROM ped_cotizaciones_versiones_detalle
                 WHERE idversion = ? AND estado = 2 LIMIT 1 FOR UPDATE",
                [(int)$version['idversion']]
            );
            if (!$det || $det['precio_dealer'] === null) {
                throw new InvalidArgumentException('No existe un precio dealer histórico válido.');
            }
            $dealer = (float)$det['precio_dealer'];
            if (!is_finite($dealer) || $dealer < 0) {
                throw new InvalidArgumentException('El precio dealer histórico no es válido.');
            }
            $descuentoUnitario = $tipo === 'PORCENTAJE'
                ? round($dealer * $valor / 100, 2)
                : round($valor, 2);
            if ($descuentoUnitario > $dealer) {
                throw new InvalidArgumentException('El descuento no puede superar el precio dealer.');
            }
            $precioFinal = round($dealer - $descuentoUnitario, 2);
            $descuentoTotal = round($descuentoUnitario * $cantidad, 2);
            $subtotal = round($precioFinal * $cantidad, 2);
            $iva = round($subtotal * 0.16, 2);
            $total = round($subtotal + $iva, 2);
            foreach ([$descuentoTotal, $subtotal, $iva, $total] as $importe) {
                if (!is_finite($importe) || $importe > 9999999999999.99) {
                    throw new InvalidArgumentException('Los importes superan el límite permitido.');
                }
            }
            $porcentaje = $tipo === 'PORCENTAJE'
                ? $valor
                : ($dealer > 0 ? round($descuentoUnitario / $dealer * 100, 4) : 0);
            $this->update(
                "UPDATE ped_cotizaciones_versiones_detalle SET
                    nombre_cliente_final = ?, nombre_competidor = ?, modelo_competidor = ?,
                    precio_competidor = ?, cantidad = ?, tipo_descuento = ?,
                    descuento_unitario = ?, descuento_porcentaje = ?,
                    precio_cotizado = ?, precio_lista = ?, precio_unitario = ?,
                    descuento = ?, subtotal = ?, iva = ?, total = ?,
                    observaciones = ?, fecha_actualizacion = NOW()
                 WHERE idversion_detalle = ? AND idversion = ? AND estado = 2",
                [
                    $campos['nombre_cliente_final'], $campos['nombre_competidor'],
                    $campos['modelo_competidor'], $precioCompetidor, $cantidad, $tipo,
                    $descuentoUnitario, $porcentaje, $precioFinal, $dealer, $precioFinal,
                    $descuentoTotal, $subtotal, $iva, $total,
                    $campos['observaciones_detalle'], (int)$det['idversion_detalle'], (int)$version['idversion']
                ]
            );
            $comunes = [
                $subtotal, $descuentoTotal, $iva, $total, $vigencia ?: null,
                $campos['condiciones_pago'], $campos['condiciones_entrega'],
                $campos['tiempo_entrega'], $campos['observaciones']
            ];
            $this->update(
                "UPDATE ped_cotizaciones_versiones SET
                    subtotal = ?, descuento = ?, iva = ?, total = ?, fecha_vigencia = ?,
                    condiciones_pago = ?, condiciones_entrega = ?, tiempo_entrega = ?,
                    observaciones = ?, fecha_actualizacion = NOW()
                 WHERE idversion = ? AND estado = 2",
                array_merge($comunes, [(int)$version['idversion']])
            );
            $this->update(
                "UPDATE ped_cotizaciones SET
                    subtotal = ?, descuento = ?, iva = ?, total = ?, fecha_vigencia = ?,
                    condiciones_pago = ?, condiciones_entrega = ?, tiempo_entrega = ?,
                    observaciones = ?, fecha_actualizacion = NOW()
                 WHERE idcotizacion = ? AND estado = 2",
                array_merge($comunes, [(int)$cot['idcotizacion']])
            );
            $registro = $this->insertCotizacionBitacora([
                'idcotizacion' => (int)$cot['idcotizacion'],
                'idversion' => (int)$version['idversion'],
                'tipo_evento' => 'BORRADOR_ACTUALIZADO',
                'descripcion' => 'Actualización de información de competencia y descuento comercial.',
                'estatus_anterior' => 'BORRADOR', 'estatus_nuevo' => 'BORRADOR',
                'usuario_registro' => $usuario, 'idusuario_acceso' => null,
                'origen' => 'ADMIN', 'ip' => $_SERVER['REMOTE_ADDR'] ?? null
            ]);
            if ((int)$registro <= 0) throw new RuntimeException('No se pudo registrar la bitácora.');
            $pdo->commit();
            return [
                'clave_cotizacion' => $claveCot, 'precio_cotizado' => $precioFinal,
                'descuento_unitario' => $descuentoUnitario, 'descuento' => $descuentoTotal,
                'subtotal' => $subtotal, 'iva' => $iva, 'total' => $total
            ];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    private function validarImporteComercial($valor, string $campo, int $decimales): float
    {
        if (!is_scalar($valor) || !preg_match('/^\d{1,13}(?:\.\d{1,' . $decimales . '})?$/', trim((string)$valor))) {
            throw new InvalidArgumentException($campo . ' debe ser un número positivo válido.');
        }
        $numero = (float)$valor;
        if (!is_finite($numero)) throw new InvalidArgumentException($campo . ' inválido.');
        return $numero;
    }


    /** Permisos de dirección obtenidos de usuarios.rolid y la configuración activa. */
    public function nivelesAutorizador(int $usuario): array
    {
        $rows = $this->select_all("SELECT ar.nivel FROM usuarios u
            INNER JOIN ped_cotizaciones_autorizaciones_roles ar ON ar.idrol = u.rolid AND ar.estado = 2
            WHERE u.idusuario = ?", [$usuario]);
        return array_map('intval', array_column($rows ?: [], 'nivel'));
    }

    /** Acceso: autor de la propuesta o usuario configurado como autorizador. */
    public function puedeConsultarPropuesta(string $clave, int $usuario): bool
    {
        $row = $this->select("SELECT usuario_creacion FROM ped_propuestas_comerciales
            WHERE clave = ? AND estado = 2", [$clave]);
        return $row && ((int)$row['usuario_creacion'] === $usuario || count($this->nivelesAutorizador($usuario)) > 0);
    }

    public function seguimientoAutorizaciones(int $idpropuesta, int $usuario): array
    {
        $niveles = $this->nivelesAutorizador($usuario);
        $rows = $this->select_all("SELECT c.clave, c.estatus, c.version_actual,
                a.nivel, a.tipo_autorizacion, a.estatus AS estatus_autorizacion,
                a.comentario, a.fecha_solicitud, a.fecha_respuesta, a.idusuario_autorizador
            FROM ped_cotizaciones c
            LEFT JOIN ped_cotizaciones_versiones v ON v.idcotizacion = c.idcotizacion
                AND v.numero_version = c.version_actual AND v.estado = 2
            LEFT JOIN ped_cotizaciones_autorizaciones a ON a.idversion = v.idversion AND a.estado = 2
            WHERE c.idpropuesta = ? AND c.estado = 2
            ORDER BY c.idcotizacion, a.nivel", [$idpropuesta]);
        $out = [];
        foreach ($rows ?: [] as $r) {
            $k = $r['clave'];
            if (!isset($out[$k])) $out[$k] = ['aprobadas'=>0,'porcentaje'=>0,'etapas'=>[], 'puede_autorizar'=>false];
            if ($r['nivel'] !== null) {
                $n=(int)$r['nivel'];
                $out[$k]['etapas'][] = [
                    'nivel'=>$n,'tipo'=>$r['tipo_autorizacion'], 'estatus'=>$r['estatus_autorizacion'],
                    'comentario'=>$r['comentario'],'fecha_solicitud'=>$r['fecha_solicitud'],
                    'fecha_respuesta'=>$r['fecha_respuesta'],
                    'autorizador'=>$r['idusuario_autorizador']
                ];
                if ($r['estatus_autorizacion']==='APROBADA') $out[$k]['aprobadas']++;
                if ($r['estatus_autorizacion']==='PENDIENTE' && in_array($n,$niveles,true)) $out[$k]['puede_autorizar']=true;
            }
        }
        foreach ($out as &$x) $x['porcentaje'] = (int)round($x['aprobadas']*100/3);
        unset($x);
        return $out;
    }

    /** Enviar uno o todos los modelos en BORRADOR de una propuesta. */
    public function enviarCotizacionesRevision(string $clavePropuesta, ?string $claveCotizacion, int $usuario): array
    {
        $pdo=$this->getConexion();
        if ($pdo->inTransaction()) throw new RuntimeException('Ya existe una transacción activa.');
        $pdo->beginTransaction();
        try {
            $prop=$this->select("SELECT idpropuesta, usuario_creacion FROM ped_propuestas_comerciales
                WHERE clave=? AND estado=2 FOR UPDATE",[$clavePropuesta]);
            if (!$prop || (int)$prop['usuario_creacion']!==$usuario) throw new InvalidArgumentException('No tienes permiso para enviar esta propuesta.');
            $sql="SELECT c.idcotizacion, c.clave, c.version_actual, c.estatus,
                    v.idversion, v.estatus AS version_estatus, d.idversion_detalle, d.precio_dealer, d.cantidad, d.precio_cotizado, d.total
                FROM ped_cotizaciones c
                INNER JOIN ped_cotizaciones_versiones v ON v.idcotizacion=c.idcotizacion AND v.numero_version=c.version_actual AND v.estado=2
                INNER JOIN ped_cotizaciones_versiones_detalle d ON d.idversion=v.idversion AND d.estado=2
                WHERE c.idpropuesta=? AND c.estado=2";
            $params=[(int)$prop['idpropuesta']];
            if ($claveCotizacion!==null) { $sql.=" AND c.clave=?"; $params[]=$claveCotizacion; }
            $sql.=" ORDER BY c.idcotizacion FOR UPDATE";
            $rows=$this->select_all($sql,$params);
            if (!$rows) throw new InvalidArgumentException('No se encontraron modelos para revisar.');
            $enviadas=[];
            foreach ($rows as $r) {
                if ($r['estatus']!=='BORRADOR' || $r['version_estatus']!=='BORRADOR') {
                    if ($claveCotizacion!==null) throw new InvalidArgumentException('El modelo seleccionado no está en borrador.');
                    continue;
                }
                if ($r['precio_dealer']===null) throw new InvalidArgumentException('Falta precio dealer en una cotización.');
                if ((int)$r['cantidad'] < 1 || $r['precio_cotizado'] === null || (float)$r['precio_cotizado'] < 0 || $r['total'] === null || (float)$r['total'] <= 0) {
                    throw new InvalidArgumentException('El modelo seleccionado no tiene una cotización completa guardada. Captura y guarda sus datos antes de solicitar autorización.');
                }
                $existe=$this->select("SELECT idautorizacion FROM ped_cotizaciones_autorizaciones WHERE idversion=? AND nivel=1",[(int)$r['idversion']]);
                if ($existe) throw new InvalidArgumentException('Ya existe una revisión para esta versión.');
                $this->insert("INSERT INTO ped_cotizaciones_autorizaciones
                    (idcotizacion,idversion,nivel,tipo_autorizacion,estatus,usuario_registro,estado)
                    VALUES (?,?,1,'DIRECCION_GENERAL','PENDIENTE',?,2)",[(int)$r['idcotizacion'],(int)$r['idversion'],$usuario]);
                $this->update("UPDATE ped_cotizaciones SET estatus='PENDIENTE_AUTORIZACION', usuario_actualizacion=? WHERE idcotizacion=?",[$usuario,(int)$r['idcotizacion']]);
                $this->update("UPDATE ped_cotizaciones_versiones SET estatus='PENDIENTE_AUTORIZACION' WHERE idversion=?",[(int)$r['idversion']]);
                $enviadas[]=$r['clave'];
            }
            if (!$enviadas) throw new InvalidArgumentException('No hay cotizaciones en borrador para enviar.');
            $this->sincronizarEstadoPropuesta((int)$prop['idpropuesta']);
            $pdo->commit();
            return ['enviadas'=>count($enviadas),'claves'=>$enviadas];
        } catch (Throwable $e) {if ($pdo->inTransaction()) $pdo->rollBack();throw $e;}
    }

    /** Resuelve exclusivamente la etapa pendiente correspondiente al rol autenticado. */
    public function resolverAutorizacion(string $claveCot, string $decision, string $comentario, int $usuario): array
    {
        if (!in_array($decision,['APROBADA','RECHAZADA','DEVUELTA_AJUSTE'],true)) throw new InvalidArgumentException('Decisión inválida.');
        if ($decision!=='APROBADA' && trim($comentario)==='') throw new InvalidArgumentException('Indica el motivo de la decisión.');
        if (mb_strlen($comentario,'UTF-8')>3000) throw new InvalidArgumentException('Comentario demasiado largo.');
        $niveles=$this->nivelesAutorizador($usuario);
        if (!$niveles) throw new InvalidArgumentException('Tu usuario no tiene un rol autorizador.');
        $pdo=$this->getConexion();
        if ($pdo->inTransaction()) throw new RuntimeException('Ya existe una transacción activa.');
        $pdo->beginTransaction();
        try {
            $c=$this->select("SELECT c.idcotizacion,c.idpropuesta,c.version_actual,c.estatus,v.idversion,v.estatus AS version_estatus
                FROM ped_cotizaciones c INNER JOIN ped_cotizaciones_versiones v
                ON v.idcotizacion=c.idcotizacion AND v.numero_version=c.version_actual AND v.estado=2
                WHERE c.clave=? AND c.estado=2 FOR UPDATE",[$claveCot]);
            if (!$c || $c['estatus']!=='PENDIENTE_AUTORIZACION' || $c['version_estatus']!=='PENDIENTE_AUTORIZACION') throw new InvalidArgumentException('La cotización no está pendiente de autorización.');
            $a=$this->select("SELECT idautorizacion,nivel FROM ped_cotizaciones_autorizaciones
                WHERE idversion=? AND estatus='PENDIENTE' AND estado=2 FOR UPDATE",[(int)$c['idversion']]);
            if (!$a) throw new InvalidArgumentException('No existe una autorización pendiente.');
            $nivel=(int)$a['nivel'];
            if (!in_array($nivel,$niveles,true)) throw new InvalidArgumentException('No tienes permiso para autorizar esta etapa.');
            $this->update("UPDATE ped_cotizaciones_autorizaciones
                SET estatus=?, comentario=?, idusuario_autorizador=?, fecha_respuesta=NOW()
                WHERE idautorizacion=? AND estatus='PENDIENTE'",[$decision,$comentario?:null,$usuario,(int)$a['idautorizacion']]);
            $estadoNuevo='PENDIENTE_AUTORIZACION';
            if ($decision==='APROBADA') {
                if ($nivel<3) {
                    $tipos=[2=>'DIRECCION_COMERCIAL',3=>'DIRECCION_FINANZAS'];
                    $this->insert("INSERT INTO ped_cotizaciones_autorizaciones
                        (idcotizacion,idversion,nivel,tipo_autorizacion,estatus,usuario_registro,estado)
                        VALUES (?,?,?,?,'PENDIENTE',?,2)",[(int)$c['idcotizacion'],(int)$c['idversion'],$nivel+1,$tipos[$nivel+1],$usuario]);
                } else $estadoNuevo='AUTORIZADA';
            } elseif ($decision==='RECHAZADA') $estadoNuevo='RECHAZADA';
            else $estadoNuevo='EN_AJUSTE_INTERNO';
            if ($estadoNuevo!=='PENDIENTE_AUTORIZACION') {
                $this->update("UPDATE ped_cotizaciones SET estatus=?,usuario_actualizacion=? WHERE idcotizacion=?",[$estadoNuevo,$usuario,(int)$c['idcotizacion']]);
                $versionEstado=$estadoNuevo==='RECHAZADA'?'RECHAZADA':$estadoNuevo;
                $this->update("UPDATE ped_cotizaciones_versiones SET estatus=? WHERE idversion=?",[$versionEstado,(int)$c['idversion']]);
            }
            $this->sincronizarEstadoPropuesta((int)$c['idpropuesta']);
            $pdo->commit();
            return ['clave_cotizacion'=>$claveCot,'nivel'=>$nivel,'decision'=>$decision,'estatus'=>$estadoNuevo];
        } catch (Throwable $e) {if ($pdo->inTransaction()) $pdo->rollBack();throw $e;}
    }

    /** Crea nueva versión editable, preservando intacta la versión devuelta. */
    public function prepararAjusteCotizacion(string $claveCot, int $usuario): array
    {
        $pdo=$this->getConexion();
        if ($pdo->inTransaction()) throw new RuntimeException('Ya existe una transacción activa.');
        $pdo->beginTransaction();
        try {
            $c=$this->select("SELECT c.idcotizacion,c.idpropuesta,c.version_actual,c.estatus,p.usuario_creacion
                FROM ped_cotizaciones c INNER JOIN ped_propuestas_comerciales p ON p.idpropuesta=c.idpropuesta
                WHERE c.clave=? AND c.estado=2 AND p.estado=2 FOR UPDATE",[$claveCot]);
            if (!$c || $c['estatus']!=='EN_AJUSTE_INTERNO' || (int)$c['usuario_creacion']!==$usuario) throw new InvalidArgumentException('No puedes preparar ajustes para este modelo.');
            $anterior=$this->select("SELECT * FROM ped_cotizaciones_versiones WHERE idcotizacion=? AND numero_version=? AND estado=2 FOR UPDATE",[(int)$c['idcotizacion'],(int)$c['version_actual']]);
            if (!$anterior || $anterior['estatus']!=='EN_AJUSTE_INTERNO') throw new InvalidArgumentException('Versión anterior inválida.');
            $detalle=$this->select("SELECT * FROM ped_cotizaciones_versiones_detalle WHERE idversion=? AND estado=2 LIMIT 1",[(int)$anterior['idversion']]);
            if (!$detalle) throw new InvalidArgumentException('No existe detalle para duplicar.');
            $n=(int)$c['version_actual']+1;
            $cols=['idcotizacion','numero_version','subtotal','descuento','iva','total','fecha_vigencia','condiciones_pago','condiciones_entrega','tiempo_entrega','observaciones','motivo_version','estatus','usuario_creacion','estado'];
            $vals=[(int)$c['idcotizacion'],$n,$anterior['subtotal'],$anterior['descuento'],$anterior['iva'],$anterior['total'],$anterior['fecha_vigencia'],$anterior['condiciones_pago'],$anterior['condiciones_entrega'],$anterior['tiempo_entrega'],$anterior['observaciones'],'Ajustes solicitados por dirección','BORRADOR',$usuario,2];
            $idVersion=(int)$this->insert('INSERT INTO ped_cotizaciones_versiones ('.implode(',',$cols).') VALUES ('.implode(',',array_fill(0,count($cols),'?')).')',$vals);
            if ($idVersion<=0) throw new RuntimeException('No se pudo crear la versión de ajuste.');
            $colsDet=['idversion','idpedido_detalle','idunidad','nombre_cliente_final','nombre_competidor','modelo_competidor','precio_competidor','precio_publico','precio_dealer','tipo_descuento','descuento_unitario','precio_cotizado','cantidad','precio_lista','precio_unitario','descuento_porcentaje','descuento','subtotal','iva','total','observaciones','estado'];
            $valsDet=array_map(fn($col)=>$col==='idversion'?$idVersion:$detalle[$col],$colsDet);
            $this->insert('INSERT INTO ped_cotizaciones_versiones_detalle ('.implode(',',$colsDet).') VALUES ('.implode(',',array_fill(0,count($colsDet),'?')).')',$valsDet);
            $this->update("UPDATE ped_cotizaciones SET version_actual=?,estatus='BORRADOR',usuario_actualizacion=? WHERE idcotizacion=?",[$n,$usuario,(int)$c['idcotizacion']]);
            $this->sincronizarEstadoPropuesta((int)$c['idpropuesta']);
            $pdo->commit();
            return ['clave_cotizacion'=>$claveCot,'version_actual'=>$n];
        } catch (Throwable $e) {if ($pdo->inTransaction()) $pdo->rollBack();throw $e;}
    }

    /** Recalcula el estado agregado; se invoca dentro de la misma transacción. */
    private function sincronizarEstadoPropuesta(int $idpropuesta): void
    {
        $r=$this->select("SELECT COUNT(*) total,
            SUM(estatus='BORRADOR') borradores,
            SUM(estatus IN ('AUTORIZADA','PENDIENTE_RESPUESTA','EN_AJUSTE_CLIENTE','ACEPTADA')) autorizadas,
            SUM(estatus IN ('PENDIENTE_RESPUESTA','EN_AJUSTE_CLIENTE','ACEPTADA')) enviadas,
            SUM(estatus='ACEPTADA') aceptadas
            FROM ped_cotizaciones WHERE idpropuesta=? AND estado=2",[$idpropuesta]);
        $total=(int)($r['total']??0);
        if ($total===0) return;
        $b=(int)$r['borradores']; $a=(int)$r['autorizadas'];
        $e=(int)$r['enviadas']; $ok=(int)$r['aceptadas'];
        if ($ok===$total) $estado='ACEPTADA';
        elseif ($ok>0) $estado='PARCIALMENTE_ACEPTADA';
        elseif ($e===$total) $estado='ENVIADA';
        elseif ($e>0) $estado='PARCIALMENTE_ENVIADA';
        elseif ($a===$total) $estado='AUTORIZADA';
        elseif ($a>0) $estado='PARCIALMENTE_AUTORIZADA';
        elseif ($b===$total) $estado='BORRADOR';
        else $estado='EN_PROCESO';
        $this->update("UPDATE ped_propuestas_comerciales SET estatus=? WHERE idpropuesta=? AND estado=2 AND estatus<>'CANCELADA'",[$estado,$idpropuesta]);
    }

    /** Solicitudes vigentes pendientes del rol del usuario conectado. */
    public function bandejaAutorizaciones(int $usuario): array
    {
        return $this->select_all("SELECT p.clave AS clave_propuesta,p.folio_propuesta,
            c.clave AS clave_cotizacion,c.folio_cotizacion,c.total,
            c.version_actual,a.nivel,a.tipo_autorizacion,a.fecha_solicitud,
            COALESCE(cli.nombre_comercial,cli.razon_social) AS distribuidor
            FROM usuarios u
            INNER JOIN ped_cotizaciones_autorizaciones_roles ar ON ar.idrol=u.rolid AND ar.estado=2
            INNER JOIN ped_cotizaciones_autorizaciones a ON a.nivel=ar.nivel AND a.estatus='PENDIENTE' AND a.estado=2
            INNER JOIN ped_cotizaciones_versiones ver ON ver.idversion=a.idversion AND ver.estado=2
            INNER JOIN ped_cotizaciones c ON c.idcotizacion=a.idcotizacion AND c.version_actual=ver.numero_version
                AND c.estado=2 AND c.estatus='PENDIENTE_AUTORIZACION'
            INNER JOIN ped_propuestas_comerciales p ON p.idpropuesta=c.idpropuesta AND p.estado=2
            INNER JOIN cli_clientes cli ON cli.idcliente=p.idcliente
            WHERE u.idusuario=? ORDER BY a.fecha_solicitud ASC",[$usuario]) ?: [];
    }

    /** Datos del correo obtenidos del servidor, nunca del POST. */
    public function correosAutorizacion(string $claveCotizacion, int $nivel): array
    {
        $datos=$this->select("SELECT p.clave AS clave_propuesta,p.folio_propuesta,
            c.folio_cotizacion,c.version_actual,c.total,
            COALESCE(cli.nombre_comercial,cli.razon_social) AS distribuidor
            FROM ped_cotizaciones c
            INNER JOIN ped_propuestas_comerciales p ON p.idpropuesta=c.idpropuesta
            INNER JOIN cli_clientes cli ON cli.idcliente=p.idcliente
            WHERE c.clave=? AND c.estado=2",[$claveCotizacion]);
        if (!$datos) return [];
        $usuarios=$this->select_all("SELECT DISTINCT u.idusuario,u.email_user
            FROM usuarios u INNER JOIN ped_cotizaciones_autorizaciones_roles r ON r.idrol=u.rolid
            WHERE r.nivel=? AND r.estado=2 AND u.email_user IS NOT NULL AND u.email_user<>''",[$nivel]);
        return ['datos'=>$datos,'destinatarios'=>$usuarios?:[]];
    }

}
