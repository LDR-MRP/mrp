<?php
require_once __DIR__ . '/../Services/PedRevisionService.php';
class Ped_pedidos extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MCLIENTES_PEDIDOS);
    }

    public function Ped_pedidos()
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }
        $data['page_tag'] = "Pedidos";
        $data['page_title'] = "Gestión de Pedidos";
        $data['page_name'] = "Pedidos";
        // $data['page_functions_js'] = "clientes_pedidos/pedidos/index.js";
        $data['page_functions_js'] = ["modulos/clientes_pedidos/pedidos/index.js", "orders/pedido_pdf.js"];
        //  $data['page_functions_js'] =  ['orders/pedido_pdf.js','orders/micuenta.js'];
        $this->views->getView($this, "index", $data);
    }




    /* ============================================================
     * GET PEDIDOS
     * ============================================================ */

    public function getPedidos()
    {
        /*
         * ========================================================
         * RESPONSE JSON
         * ========================================================
         */
        header('Content-Type: application/json; charset=utf-8');

        /*
         * ========================================================
         * VALIDAR SESIÓN
         * ========================================================
         */

        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {

            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'La sesión no es válida.'
            ]);
            return;
        }

        /*
         * ========================================================
         * VALIDAR MÉTODO
         * ========================================================
         */

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode([
                'status' => false,
                'message' => 'Método no permitido.'
            ]);

            return;
        }

        try {
            /*
             * ====================================================
             * PAGINACIÓN
             * ====================================================
             */
            $pagina = isset($_GET['pagina']) ? intval($_GET['pagina']) : 1;
            $limite = isset($_GET['limite']) ? intval($_GET['limite']) : 10;

            if ($pagina <= 0) {
                $pagina = 1;
            }


            /*
             * ====================================================
             * LIMITAR CANTIDAD
             * ====================================================
             */

            $limitesPermitidos = [
                10,
                25,
                50,
                100
            ];


            if (!in_array($limite, $limitesPermitidos, true)) {
                $limite = 10;
            }

            /*
             * ====================================================
             * FILTROS
             * ====================================================
             */

            $busqueda = trim((string) ($_GET['busqueda'] ?? ''));

            $estatus =
                strtoupper(
                    trim(
                        (string) (
                            $_GET['estatus']
                            ?? ''
                        )
                    )
                );

            $prioridad = strtoupper(trim((string) ($_GET['prioridad'] ?? '')));
            $idcliente = intval($_GET['idcliente'] ?? 0);
            $desde = trim((string) ($_GET['desde'] ?? ''));
            $hasta = trim((string) ($_GET['hasta'] ?? ''));
            $fechaRequerida = trim((string) ($_GET['fecha_requerida'] ?? ''));
            $mesFacturacion = trim((string) ($_GET['mes_facturacion'] ?? ''));
            $situacionComercial = strtoupper(trim((string) ($_GET['situacion_comercial'] ?? '')));
            $backorder = strtoupper(trim((string) ($_GET['backorder'] ?? '')));

            /*
             * ====================================================
             * VALIDAR ESTATUS
             * ====================================================
             */

            $estatusPermitidos = [
                '',
                'PENDIENTE',
                'EN_REVISION',
                'AUTORIZADO',
                'RECHAZADO',
                'CANCELADO',
                'FINALIZADO'
            ];

            if (!in_array($estatus, $estatusPermitidos, true)) {
                $estatus = '';
            }

            /*
             * ====================================================
             * VALIDAR PRIORIDAD
             * ====================================================
             */

            $prioridadesPermitidas = [
                '',
                'BAJA',
                'MEDIA',
                'ALTA',
                'URGENTE'
            ];

            if (!in_array($prioridad, $prioridadesPermitidas, true)) {
                $prioridad = '';
            }

            /*
             * ====================================================
             * VALIDAR FECHAS
             * ====================================================
             */

            if ($desde !== '' && !$this->validarFecha($desde)) {
                $desde = '';
            }
            if ($hasta !== '' && !$this->validarFecha($hasta)) {
                $hasta = '';
            }

            if ($fechaRequerida !== '' && !$this->validarFecha($fechaRequerida)) {
                $fechaRequerida = '';
            }

            /*
             * ====================================================
             * VALIDAR MES
             * ====================================================
             */

            if ($mesFacturacion !== '' && !$this->validarMes($mesFacturacion)) {
                $mesFacturacion = '';
            }

            /*
             * ====================================================
             * VALIDAR RANGO
             * ====================================================
             */

            if ($desde !== '' && $hasta !== '' && $desde > $hasta) {

                http_response_code(400);
                echo json_encode([
                    'status' => false,
                    'message' => 'La fecha inicial no puede ser mayor a la fecha final.'
                ]);

                return;
            }

            /*
             * ====================================================
             * OFFSET
             * ====================================================
             */

            $offset = ($pagina - 1) * $limite;

            /*
             * ====================================================
             * FILTROS PARA MODELO
             * ====================================================
             */

            $filtros = [
                'busqueda' => $busqueda,
                'estatus' => $estatus,
                'prioridad' => $prioridad,
                'idcliente' => $idcliente,
                'desde' => $desde,
                'hasta' => $hasta,
                'fecha_requerida' => $fechaRequerida,
                'mes_facturacion' => $mesFacturacion,
                'situacion_comercial' => $situacionComercial,
                'backorder' => $backorder,
                'limite' => $limite,
                'offset' => $offset
            ];

            /*
             * ====================================================
             * CONSULTAR PEDIDOS
             * ====================================================
             */

            $pedidos = $this->model->selectPedidos($filtros);

            /*
             * ====================================================
             * TOTAL REGISTROS
             * ====================================================
             */

            $totalRegistros = $this->model->countPedidos($filtros);

            /*
             * ====================================================
             * INDICADORES
             * ====================================================
             */

            $indicadores = $this->model->selectIndicadoresPedidos($filtros);

            /*
             * ====================================================
             * PAGINACIÓN
             * ====================================================
             */

            $totalPaginas = $totalRegistros > 0 ? (int) ceil($totalRegistros / $limite) : 0;

            /*
             * ====================================================
             * CORREGIR PÁGINA
             * ====================================================
             */

            if ($totalPaginas > 0 && $pagina > $totalPaginas) {
                $pagina = $totalPaginas;
            }

            /*
             * ====================================================
             * RESPUESTA
             * ====================================================
             */

            echo json_encode([

                'status' => true,
                'message' => 'Pedidos obtenidos correctamente.',
                'data' => [
                    'pedidos' => $pedidos,
                    'indicadores' => $indicadores,
                    'paginacion' => [
                        'pagina_actual' => $pagina,
                        'limite' => $limite,
                        'total_registros' => $totalRegistros,
                        'total_paginas' => $totalPaginas
                    ]
                ]

            ]);

        } catch (Throwable $e) {

            /*
             * ====================================================
             * LOG
             * ====================================================
             */

            error_log(
                'Ped_pedidos::getPedidos - '
                . $e->getMessage()
            );

            /*
             * ====================================================
             * ERROR
             * ====================================================
             */

            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'No fue posible obtener los pedidos.'
            ]);

        }
    }

    /** Reporte completo filtrado; no modifica pedidos ni sus estados. */
    public function getReportePedidos()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {
            http_response_code(401);
            echo json_encode(['status'=>false,'message'=>'Sesión no válida.']);
            return;
        }
        try {
            $filtros = [
                'busqueda' => trim((string)($_GET['busqueda'] ?? '')),
                'estatus' => strtoupper(trim((string)($_GET['estatus'] ?? ''))),
                'situacion_comercial' => strtoupper(trim((string)($_GET['situacion_comercial'] ?? ''))),
                'backorder' => strtoupper(trim((string)($_GET['backorder'] ?? ''))),
                'prioridad' => strtoupper(trim((string)($_GET['prioridad'] ?? ''))),
                'idcliente' => (int)($_GET['idcliente'] ?? 0),
                'desde' => trim((string)($_GET['desde'] ?? '')),
                'hasta' => trim((string)($_GET['hasta'] ?? '')),
                'fecha_requerida' => trim((string)($_GET['fecha_requerida'] ?? '')),
                'mes_facturacion' => trim((string)($_GET['mes_facturacion'] ?? '')),
                'offset' => 0,
                'limite' => 50000
            ];
            foreach (['desde','hasta','fecha_requerida'] as $campo) {
                if ($filtros[$campo] !== '' && !$this->validarFecha($filtros[$campo])) {
                    throw new InvalidArgumentException('Fecha no válida: '.$campo);
                }
            }
            if ($filtros['desde'] !== '' && $filtros['hasta'] !== '' && $filtros['desde'] > $filtros['hasta']) {
                throw new InvalidArgumentException('Rango de fechas no válido.');
            }
            if ($filtros['mes_facturacion'] !== '' && !$this->validarMes($filtros['mes_facturacion'])) {
                throw new InvalidArgumentException('Mes de facturación no válido.');
            }
            $total = $this->model->countPedidos($filtros);
            if ($total > 50000) {
                http_response_code(422);
                echo json_encode(['status'=>false,'message'=>'El reporte supera 50,000 pedidos. Reduce el rango de filtros.']);
                return;
            }
            $pedidos = $this->model->selectPedidos($filtros);
            echo json_encode(['status'=>true,'data'=>['pedidos'=>$pedidos,'total'=>$total,'filtros'=>$filtros]],JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[PED_REPORTE] '.$e->getMessage());
            http_response_code(500);
            echo json_encode(['status'=>false,'message'=>'No fue posible generar el reporte.']);
        }
    }

    /* ============================================================
     * GET DISTRIBUIDORES
     * ============================================================ */

    public function getDistribuidores()
    {
        header('Content-Type: application/json; charset=utf-8');

        /*
         * ========================================================
         * VALIDAR SESIÓN
         * ========================================================
         */

        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {

            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'La sesión no es válida.'
            ]);

            return;
        }

        /*
         * ========================================================
         * VALIDAR MÉTODO
         * ========================================================
         */

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

            http_response_code(405);
            echo json_encode([
                'status' => false,
                'message' => 'Método no permitido.'
            ]);
            return;
        }

        try {

            /*
             * ====================================================
             * CONSULTAR
             * ====================================================
             */

            $distribuidores = $this->model->selectDistribuidoresPedidos();

            /*
             * ====================================================
             * RESPUESTA
             * ====================================================
             */

            echo json_encode([

                'status' => true,
                'message' => 'Distribuidores obtenidos correctamente.',
                'data' => [
                    'distribuidores' => $distribuidores
                ]
            ]);

        } catch (Throwable $e) {

            error_log(
                'Ped_pedidos::getDistribuidores - '
                . $e->getMessage()
            );

            http_response_code(500);
            echo json_encode([
                'status' => false,
                'message' => 'No fue posible obtener los distribuidores.'
            ]);

        }
    }


    /* ============================================================
     * DETALLE DEL PEDIDO
     * ============================================================
     *
     * Todavía no vamos a construir la vista en este paso,
     * pero te dejo preparado el método para la siguiente parte.
     * ============================================================ */

    public function detalle($clave = '')
    {

        /*
         * ========================================================
         * VALIDAR SESIÓN
         * ========================================================
         */

        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {

            header(
                'Location: '
                . base_url()
                . '/login'
            );

            exit;
        }

        /*
         * ========================================================
         * CLAVE
         * ========================================================
         */

        $clave = trim((string) $clave);
        if ($clave === '') {
            header(
                'Location: '
                . base_url()
                . '/ped_pedidos'
            );
            exit;
        }

        try {

            /*
             * ====================================================
             * CONSULTAR PEDIDO
             * ====================================================
             */

            $pedido = $this->model->selectPedidoDetalleAdmin($clave);

            if (empty($pedido)) {
                header(
                    'Location: '
                    . base_url()
                    . '/ped_pedidos'
                );
                exit;
            }

            /*
             * ====================================================
             * DETALLES
             * ====================================================
             */

            $detalles = $this->model->selectDetallesPedidoAdmin(intval($pedido['idpedido']));

            /*
             * ====================================================
             * DATA
             * ====================================================
             */

            $data = [];
            $data['page_tag'] = 'Detalle pedido';
            $data['page_title'] =
                'Detalle del Pedido - '
                . (
                    $pedido['folio_pedido']
                    ?? ''
                );

            $data['page_name'] = 'detalle_pedido';
            $data['pedido'] = $pedido;
            $data['detalles'] = $detalles;
            $data['page_functions_js'] = ['ped_pedidos/detalle.js'];

            /*
             * ====================================================
             * VISTA
             * ====================================================
             */

            $this->views->getView($this, 'detalle', );

        } catch (Throwable $e) {

            error_log(
                'Ped_pedidos::detalle - '
                . $e->getMessage()
            );

            header(
                'Location: '
                . base_url()
                . '/ped_pedidos'
            );

            exit;

        }
    }

    /* ============================================================
     * INICIAR GESTIÓN
     * ============================================================
     *
     * Lo usaremos desde la vista detalle.
     * ============================================================ */

    public function iniciarGestion()
    {
        header('Content-Type: application/json; charset=utf-8');

        /*
         * ========================================================
         * VALIDAR MÉTODO
         * ========================================================
         */

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            http_response_code(405);

            echo json_encode([
                'status' => false,
                'message' => 'Método no permitido.'
            ]);

            return;
        }


        /*
         * ========================================================
         * VALIDAR SESIÓN
         * ========================================================
         */

        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {
            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'La sesión no es válida.'
            ]);
            return;
        }

        /*
         * ========================================================
         * OBTENER USUARIO ADMINISTRATIVO
         * ========================================================
         */

        $idusuario = intval($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0);


        if ($idusuario <= 0) {
            http_response_code(401);
            echo json_encode([
                'status' => false,
                'message' => 'No fue posible identificar al usuario.'
            ]);
            return;
        }

        /*
         * ========================================================
         * OBTENER INFORMACIÓN RECIBIDA
         * ========================================================
         */

        $input = json_decode(file_get_contents('php://input'), true);

        if (!is_array($input)) {
            $input = $_POST;
        }

        $clave = trim((string) ($input['clave'] ?? ''));

        if ($clave === '') {
            http_response_code(400);
            echo json_encode([
                'status' => false,
                'message' => 'No se recibió la clave del pedido.'
            ]);
            return;
        }

        try {

            /*
             * ====================================================
             * CONSULTAR PEDIDO
             * ====================================================
             */

            $pedido = $this->model->selectPedidoDetalleAdmin($clave);

            // dep($pedido);
            // exit;


            if (empty($pedido)) {
                http_response_code(404);
                echo json_encode([
                    'status' => false,
                    'message' => 'El pedido no existe.'
                ]);
                return;
            }

            /*
             * ====================================================
             * VALIDAR ESTADO
             * ====================================================
             */

            if (intval($pedido['estado'] ?? 0) !== 2) {
                http_response_code(409);
                echo json_encode([
                    'status' => false,
                    'message' => 'El pedido no se encuentra activo.'
                ]);
                return;
            }

            /*
             * ====================================================
             * VALIDAR ESTATUS
             * ====================================================
             */

            $estatusActual = strtoupper(trim((string) ($pedido['estatus'] ?? '')));
            if ($estatusActual !== 'PENDIENTE') {
                http_response_code(409);
                echo json_encode([
                    'status' => false,
                    'message' => 'El pedido ya comenzó a ser gestionado.'
                ]);
                return;
            }

            /*
             * ====================================================
             * INICIAR GESTIÓN
             * ====================================================
             */

            $actualizado = $this->model->iniciarGestionPedidoModel(intval($pedido['idpedido']), $idusuario);
            if (!$actualizado) {
                throw new Exception(
                    'No fue posible actualizar el pedido.'
                );
            }

            /*
             * ====================================================
             * REGISTRAR BITÁCORA
             * ====================================================
             */

            $descripcion = 'Se inició la gestión del pedido ' . ($pedido['folio_pedido'] ?? $clave) . ' desde el módulo administrativo.';

            $idBitacora =
                $this->model->insertBitacoraEvento([
                    'idpedido' => intval($pedido['idpedido']),
                    'tipo_evento' => 'INICIO_GESTION',
                    'descripcion' => $descripcion,
                    'estatus_anterior' => 'PENDIENTE',
                    'estatus_nuevo' => 'EN_REVISION',
                    'usuario_registro' => $idusuario,
                    'origen' => 'ADMIN'
                ]);


            /*
             * ====================================================
             * LA BITÁCORA NO DETIENE LA OPERACIÓN
             * ====================================================
             */

            if (intval($idBitacora) <= 0) {
                error_log(
                    'No fue posible insertar la bitácora '
                    . 'del pedido ID: '
                    . intval($pedido['idpedido'])
                );
            }

            /*
             * ====================================================
             * ENVIAR NOTIFICACIÓN AL DISTRIBUIDOR
             * ====================================================
             *
             */

            $emailEnviado = false;


            try {

                $emailEnviado = $this->enviarCorreoInicioGestionPedido($pedido);

                if (!$emailEnviado) {

                    error_log(
                        'No fue posible enviar el correo de inicio '
                        . 'de gestión del pedido '
                        . ($pedido['folio_pedido'] ?? $clave)
                    );
                }


            } catch (Throwable $emailError) {

                /*
                 * NO lanzar nuevamente la excepción.
                 *
                 * La gestión ya fue iniciada correctamente.
                 */

                error_log(
                    'Error enviando correo de inicio de gestión '
                    . 'del pedido '
                    . ($pedido['folio_pedido'] ?? $clave)
                    . ': '
                    . $emailError->getMessage()
                );

                $emailEnviado = false;
            }

            /*
             * ====================================================
             * RESPUESTA
             * ====================================================
             */

            echo json_encode([

                'status' => true,
                'message' => 'La gestión del pedido inició correctamente.',
                'email_enviado' => $emailEnviado,
                'data' => [
                    'idpedido' => intval($pedido['idpedido']),
                    'clave' => $clave,
                    'folio' => $pedido['folio_pedido'] ?? '',
                    'estatus_anterior' => 'PENDIENTE',
                    'estatus_nuevo' => 'EN_REVISION'
                ]
            ]);


        } catch (Throwable $e) {
            error_log(
                'Ped_pedidos::iniciarGestion - '
                . $e->getMessage()
            );

            http_response_code(500);
            echo json_encode([

                'status' => false,

                'message' =>
                    'No fue posible iniciar la gestión del pedido.'

            ]);
        }
    }



    public function enviarCorreoInicioGestionPedido(array $pedido)
    {
        // dep($pedido);
        try {

            /*
             * ====================================================
             * OBTENER CORREO DESTINO
             * ====================================================
             *
             */

            $correoUsuario = trim((string) ($pedido['correo_usuario'] ?? ''));
            $correoCliente = trim((string) ($pedido['correo_cliente'] ?? ''));
            $correoDestino = $correoUsuario !== '' ? $correoUsuario : $correoCliente;
            if ($correoDestino === '' || !filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {

                error_log(
                    'Pedido '
                    . ($pedido['folio_pedido'] ?? '')
                    . ': no existe un correo válido '
                    . 'para notificar el inicio de gestión.'
                );

                return false;
            }

            /*
             * ====================================================
             * NOMBRE DEL DISTRIBUIDOR
             * ====================================================
             */

            $nombreDistribuidor = trim((string) ($pedido['nombre_comercial'] ?? ''));

            if ($nombreDistribuidor === '') {
                $nombreDistribuidor = trim(
                    (string) (
                        $pedido['razon_social']
                        ?? ''
                    )
                );
            }


            /*
             * ====================================================
             * NOMBRE DEL SOLICITANTE
             * ====================================================
             */

            $nombreSolicitante = trim(($pedido['nombre_usuario'] ?? '') . ' ' . ($pedido['apellido_usuario'] ?? ''));

            if ($nombreSolicitante === '') {
                $nombreSolicitante = 'Distribuidor';
            }

            /*
             * ====================================================
             * FOLIO
             * ====================================================
             */

            $folio = trim((string) ($pedido['folio_pedido'] ?? ''));

            /*
             * ====================================================
             * URL PARA CONSULTAR PEDIDO
             * ====================================================
             */

            $clave = trim((string) ($pedido['clave'] ?? ''));
            $urlPedido = base_url() . '/orders/detallepedido/' . rawurlencode($clave);
            /*
             * ====================================================
             * INFORMACIÓN PARA LA PLANTILLA
             * ====================================================
             */
            $dataUsuario = [
                /*
                 * Destinatario
                 */
                // 'email' =>$correoDestino,
                // 'email' => 'carloscc_1997@outlook.com',
                'email' => $correoDestino,
                /*
                 * Solicitante
                 */

                'nombre' => $nombreSolicitante,
                'nombre_solicitante' => $nombreSolicitante,
                /*
                 * Distribuidor
                 */
                'nombre_distribuidor' => $nombreDistribuidor,
                'razon_social' =>
                    $pedido['razon_social']
                    ?? '',

                'clave_distribuidor' =>
                    $pedido['clave_distribuidor']
                    ?? '',

                'codigo_cliente' =>
                    $pedido['codigo_cliente']
                    ?? '',

                /*
                 * Pedido
                 */

                'idpedido' =>
                    intval(
                        $pedido['idpedido']
                        ?? 0
                    ),

                'folio_pedido' => $folio,
                'num_orden'=>$folio,
                'clave' => $clave,
                'asunto' => 'Pedido en revisión',
                'prioridad' => $pedido['prioridad'] ?? '',
                'fecha_pedido' => $pedido['fecha_pedido'] ?? '',
                'fecha_requerida' => $pedido['fecha_requerida'] ?? '',
                'mes_facturacion_deseado' =>
                    $pedido['mes_facturacion_deseado']
                    ?? '',

                /*
                 * Cantidades
                 */

                'total_modelos' => intval($pedido['total_modelos'] ?? 0),
                'total_unidades' =>
                    intval(
                        $pedido['total_unidades']
                        ?? 0
                    ),

                /*
                 * Importes
                 */

                'subtotal' => floatval($pedido['subtotal'] ?? 0),
                'iva' => floatval($pedido['iva'] ?? 0),
                'total' => floatval($pedido['total'] ?? 0),

                /*
                 * Estatus
                 */

                'estatus_anterior' => 'PENDIENTE',
                'estatus' => 'EN_REVISION',
                'estatus_nuevo' => 'EN_REVISION',

                /*
                 * Observaciones
                 */

                'observaciones' =>
                    $pedido['observaciones']
                    ?? '',


                /*
                 * Liga
                 */

                'url_pedido' => $urlPedido

            ];


            /*
             * ====================================================
             * ENVIAR CORREO
             * ====================================================
             */

            // $sendEmail = sendMailLocalV2(
            //     $dataUsuario,
            //     'email_distribuidores_inicio_gestion_pedido'
            // ); 
            $correos_copias = 'carlos.cruz@ldrsolutions.com.mx';
            $sendEmail = sendMailLocalCron($dataUsuario, 'email_distribuidores_inicio_gestion_pedido', $correos_copias);


            /*
             * ====================================================
             * VALIDAR RESPUESTA
             * ====================================================
             */

            if (!$sendEmail) {
                error_log(
                    'sendMailLocalV2 devolvió false '
                    . 'para el pedido '
                    . $folio
                );

                return false;
            }

            return true;

        } catch (Throwable $e) {

            error_log(
                'Ped_pedidos::enviarCorreoInicioGestionPedido - '
                . $e->getMessage()
            );

            return false;
        }
    }


    /* ============================================================
     * VALIDAR FECHA YYYY-MM-DD
     * ============================================================ */

    private function validarFecha(string $fecha): bool
    {

        $formato = DateTime::createFromFormat('Y-m-d', $fecha);
        return (
            $formato
            && $formato->format(
                'Y-m-d'
            )
            === $fecha
        );

    }

    /* ============================================================
     * VALIDAR MES YYYY-MM
     * ============================================================ */

    private function validarMes(string $mes): bool
    {

        return preg_match(
            '/^\d{4}-(0[1-9]|1[0-2])$/',
            $mes
        ) === 1;

    }




    public function gestionar($clave = '')
    {
        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {
            header('Location:' . base_url() . '/login');
            exit;
        }

        $clave = trim((string) $clave);

        if ($clave === '') {
            header('Location:' . base_url() . '/ped_pedidos');
            exit;
        }

        try {

            // =========================================================
            // PEDIDO
            // =========================================================
            $pedido = $this->model->selectPedidoGestionAdmin($clave);



            if (empty($pedido)) {
                header('Location:' . base_url() . '/ped_pedidos');
                exit;
            }

            // =========================================================
            // DETALLES
            // =========================================================
            $detalles = $this->model->selectDetallesGestionPedido(
                intval($pedido['idpedido'])
            );

            // =========================================================
            // BITÁCORA
            // =========================================================
            $bitacora = $this->model->selectBitacoraPedido(
                intval($pedido['idpedido'])
            );

            // =========================================================
            // ÓRDENES DE VENTA
            // Más adelante alimentaremos esta consulta.
            // =========================================================
            // $ordenesVenta = $this->model->selectOrdenesVentaPedido(
            //     intval($pedido['idpedido'])
            // );

            // =========================================================
            // DATA
            // =========================================================
            $data = [];

            $data['page_tag'] = 'Gestión de Pedido';

            $data['page_title'] =
                'Gestión del Pedido - '
                . ($pedido['folio_pedido'] ?? '');

            $data['page_name'] = 'gestionar_pedido';

            $data['pedido'] = $pedido;

            $data['detalles'] = $detalles;

            $data['bitacora'] = $bitacora;
            // 2026-11-04: datos de la revisión y del backorder, sin alterar pedido original.
            $revisionService = new PedRevisionService();
            $data['revision_comercial'] = $revisionService->ultima((int)$pedido['idpedido']);
            $data['versiones_comerciales'] = $revisionService->versiones((int)$pedido['idpedido']);
            $data['lineas_revision'] = $data['revision_comercial'] ? $revisionService->lineas((int)$data['revision_comercial']['idrevision']) : [];
            $data['backorders_comerciales'] = $revisionService->backorders((int)$pedido['idpedido']);
            $data['respuestas_comerciales'] = $revisionService->respuestas((int)$pedido['idpedido']);
            $data['colores_revision'] = [];
            foreach ($data['lineas_revision'] as $linea) {
                $data['colores_revision'][(int)$linea['idunidad']] = $revisionService->colores((int)$linea['idunidad']);
            }
            if (empty($_SESSION['ped_revision_csrf'])) $_SESSION['ped_revision_csrf'] = bin2hex(random_bytes(24));


            $data['page_functions_js'] = ["modulos/clientes_pedidos/pedidos/gestionar.js", "orders/pedido_pdf.js", "modulos/clientes_pedidos/pedidos/revision_comercial.js", "orders/propuesta_comercial_pdf.js", "modulos/clientes_pedidos/pedidos/historial_reporte.js"];

            $this->views->getView($this, 'gestionar', $data);

        } catch (Throwable $e) {

            error_log(
                'Ped_pedidos::gestionar - '
                . $e->getMessage()
            );

            header('Location:' . base_url() . '/ped_pedidos');
            exit;
        }
    }







    /** 2026-11-04 | API de revisión comercial (solo sesión administrativa). */
    public function revisionComercialApi()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['status'=>false,'msg'=>'Método no permitido']); return; }
        $token = (string)($_SERVER['HTTP_X_PED_CSRF'] ?? '');
        if (!$token || !hash_equals((string)($_SESSION['ped_revision_csrf']??''),$token)) { http_response_code(403); echo json_encode(['status'=>false,'msg'=>'Token de seguridad inválido']); return; }
        $usuario=(int)($_SESSION['idUser']??$_SESSION['userData']['id_usuario']??$_SESSION['userData']['idusuario']??0);
        if ($usuario<=0) { http_response_code(403); echo json_encode(['status'=>false,'msg'=>'Sesión no válida']); return; }
        try {
            $input=json_decode(file_get_contents('php://input'),true);
            if (!is_array($input)) throw new RuntimeException('Solicitud JSON inválida.');
            $clave=trim((string)($input['clave']??''));
            if (!preg_match('/^[a-zA-Z0-9_-]{1,150}$/',$clave)) throw new RuntimeException('Clave de pedido inválida.');
            $svc=new PedRevisionService(); 
            $accion=$input['accion']??'';
            switch($accion){
                case 'crear': $result=$svc->crear($clave,$usuario);break;
                case 'guardar': $result=$svc->guardar($clave,$usuario,$input['lineas']??[],(string)($input['observaciones']??''));break;
                case 'enviar': $result=$svc->enviar($clave,$usuario);break;
                default: throw new RuntimeException('Acción no reconocida.');
            }
            if ($accion === 'enviar') {
                try {
                    require_once __DIR__ . '/../Services/PedRevisionNotificador.php';
                    (new PedRevisionNotificador())->distribuidor($clave, 'ENVIADA');
                } catch (Throwable $mailError) { error_log('Notificación propuesta enviada: '.$mailError->getMessage()); }
            }
            echo json_encode(['status'=>true,'msg'=>'Operación realizada correctamente','data'=>$result],JSON_UNESCAPED_UNICODE);
        } catch(Throwable $e) { http_response_code(422); error_log('revisionComercialApi: '.$e->getMessage()); echo json_encode(['status'=>false,'msg'=>$e->getMessage()],JSON_UNESCAPED_UNICODE); }
    }
}








?>