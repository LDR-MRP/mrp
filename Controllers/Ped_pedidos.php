<?php
	class Ped_pedidos extends Controllers{
		public function __construct()
		{
			parent::__construct();
			session_start(); 
			//session_regenerate_id(true);
			if(empty($_SESSION['login']))
			{
				header('Location: '.base_url().'/login');
				die();
			} 
			getPermisos(MCPEDIDOS);
		} 

		public function Ped_pedidos()
		{
			if(empty($_SESSION['permisosMod']['r'])){   
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Pedidos";
			$data['page_title'] = "Gestión de Pedidos";
			$data['page_name'] = "Pedidos";
            $data['page_functions_js'] = "clientes_pedidos/pedidos/index.js";
			$this->views->getView($this,"index",$data);
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
                'status'  => false,
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
                'status'  => false,
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
            $pagina =isset($_GET['pagina'])? intval($_GET['pagina']): 1;
            $limite =isset($_GET['limite']) ? intval($_GET['limite']) : 10;

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


            if (!in_array($limite,$limitesPermitidos,true)) {
                $limite = 10;
            }

            /*
             * ====================================================
             * FILTROS
             * ====================================================
             */

            $busqueda =trim((string)($_GET['busqueda'] ?? ''));

            $estatus =
                strtoupper(
                    trim(
                        (string)(
                            $_GET['estatus']
                            ?? ''
                        )
                    )
                );

            $prioridad =strtoupper(trim((string)($_GET['prioridad'] ?? '')));
            $idcliente =intval($_GET['idcliente'] ?? 0);
            $desde =trim((string)($_GET['desde']?? ''));
            $hasta =trim((string)($_GET['hasta'] ?? ''));
            $fechaRequerida =trim((string)($_GET['fecha_requerida'] ?? ''));
            $mesFacturacion =trim((string)($_GET['mes_facturacion']?? ''));

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

            if (!in_array($estatus,$estatusPermitidos,true)) {
                $estatus ='';
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

            if (!in_array($prioridad,$prioridadesPermitidas,true)) {
                $prioridad ='';
            }

            /*
             * ====================================================
             * VALIDAR FECHAS
             * ====================================================
             */

            if ($desde !== '' && !$this->validarFecha($desde)) {
                $desde ='';
            }
            if ($hasta !== '' && !$this->validarFecha($hasta)) {
                $hasta ='';
            }

            if ($fechaRequerida !== '' && !$this->validarFecha($fechaRequerida)) {
                $fechaRequerida ='';
            }

            /*
             * ====================================================
             * VALIDAR MES
             * ====================================================
             */

            if ($mesFacturacion !== '' && !$this->validarMes($mesFacturacion)) {
                $mesFacturacion ='';
            }

            /*
             * ====================================================
             * VALIDAR RANGO
             * ====================================================
             */

            if ($desde !== '' && $hasta !== '' && $desde > $hasta) {

                http_response_code(400);
                echo json_encode([
                    'status'  => false,
                    'message' => 'La fecha inicial no puede ser mayor a la fecha final.'
                ]);

                return;
            }

            /*
             * ====================================================
             * OFFSET
             * ====================================================
             */

            $offset =($pagina - 1) * $limite;

            /*
             * ====================================================
             * FILTROS PARA MODELO
             * ====================================================
             */

            $filtros = [
                'busqueda' =>$busqueda,
                'estatus' =>$estatus,
                'prioridad' =>$prioridad,
                'idcliente' =>$idcliente,
                'desde' =>$desde,
                'hasta' =>$hasta,
                'fecha_requerida' =>$fechaRequerida,
                'mes_facturacion' =>$mesFacturacion,
                'limite' =>$limite,
                'offset' =>$offset
            ];

            /*
             * ====================================================
             * CONSULTAR PEDIDOS
             * ====================================================
             */

            $pedidos =$this->model->selectPedidos($filtros);

            /*
             * ====================================================
             * TOTAL REGISTROS
             * ====================================================
             */

            $totalRegistros =$this->model->countPedidos($filtros);

            /*
             * ====================================================
             * INDICADORES
             * ====================================================
             */

            $indicadores =$this->model->selectIndicadoresPedidos($filtros);

            /*
             * ====================================================
             * PAGINACIÓN
             * ====================================================
             */

            $totalPaginas =$totalRegistros > 0 ? (int)ceil($totalRegistros / $limite): 0;

            /*
             * ====================================================
             * CORREGIR PÁGINA
             * ====================================================
             */

            if ($totalPaginas > 0 && $pagina > $totalPaginas) {
                $pagina =$totalPaginas;
            }

            /*
             * ====================================================
             * RESPUESTA
             * ====================================================
             */

            echo json_encode([

                'status' =>true,
                'message' =>'Pedidos obtenidos correctamente.',
                'data' => [
                    'pedidos' =>$pedidos,
                    'indicadores' =>$indicadores,
                    'paginacion' => [
                        'pagina_actual' =>$pagina,
                        'limite' =>$limite,
                        'total_registros' =>$totalRegistros,
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
                'status' =>false,
                'message' =>'No fue posible obtener los pedidos.'
            ]);

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
                'status'  => false,
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
                'status'  => false,
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

            $distribuidores =$this->model->selectDistribuidoresPedidos();

            /*
             * ====================================================
             * RESPUESTA
             * ====================================================
             */

            echo json_encode([

                'status' =>true,
                'message' =>'Distribuidores obtenidos correctamente.',
                'data' => [
                    'distribuidores' =>$distribuidores
                ]
            ]);

        } catch (Throwable $e) {

            error_log(
                'Ped_pedidos::getDistribuidores - '
                . $e->getMessage()
            );

            http_response_code(500);
            echo json_encode([
                'status' =>false,
                'message' =>'No fue posible obtener los distribuidores.'
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

    public function detalle($clave = '') {

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

        $clave =trim((string)$clave);
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

            $pedido =$this->model->selectPedidoDetalleAdmin($clave);

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
            $data['page_tag'] ='Detalle pedido';
            $data['page_title'] =
                'Detalle del Pedido - '
                . (
                    $pedido['folio_pedido']
                    ?? ''
                );


            $data['page_name'] ='detalle_pedido';
            $data['pedido'] =$pedido;
            $data['detalles'] = $detalles;
            $data['page_functions_js'] = ['ped_pedidos/detalle.js'];

            /*
             * ====================================================
             * VISTA
             * ====================================================
             */

            $this->views->getView($this,'detalle',);

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
        header(
            'Content-Type: application/json; charset=utf-8'
        );

        /*
         * ========================================================
         * VALIDAR MÉTODO
         * ========================================================
         */

        if ($_SERVER['REQUEST_METHOD']!== 'POST' ) {
            http_response_code(405);
            echo json_encode([
                'status'  => false,
                'message' => 'Método no permitido.'
            ]);
            return;
        }

        /*
         * ========================================================
         * VALIDAR SESIÓN
         * ========================================================
         */

        if (empty($_SESSION['login'])|| empty($_SESSION['userData'])) {

            http_response_code(401);
            echo json_encode([
                'status'  => false,
                'message' => 'La sesión no es válida.'
            ]);

            return;
        }

        /*
         * ========================================================
         * OBTENER USUARIO ADMIN
         * ========================================================
         */

        $idusuario =intval($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0);


        if ($idusuario <= 0) {
            http_response_code(401);
            echo json_encode([
                'status'  => false,
                'message' => 'No fue posible identificar al usuario.'
            ]);
            return;
        }

        /*
         * ========================================================
         * OBTENER JSON
         * ========================================================
         */

        $input =
            json_decode(
                file_get_contents(
                    'php://input'
                ),
                true
            );

        if (!is_array($input)) {
            $input =$_POST;
        }

        $clave =trim((string)($input['clave'] ?? ''));
        if ($clave === '') {
            http_response_code(400);
            echo json_encode([
                'status'  => false,
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

            $pedido =$this->model->selectPedidoDetalleAdmin($clave);
            if (empty($pedido)) {

                http_response_code(404);
                echo json_encode([
                    'status'  => false,
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
                    'status'  => false,
                    'message' => 'El pedido no se encuentra activo.'
                ]);

                return;
            }

            /*
             * ====================================================
             * VALIDAR ESTATUS
             * ====================================================
             */

            $estatusActual =
                strtoupper(
                    trim(
                        (string)(
                            $pedido['estatus']
                            ?? ''
                        )
                    )
                );


            if ($estatusActual!== 'PENDIENTE') {
                http_response_code(409);
                echo json_encode([
                    'status'  => false,
                    'message' => 'El pedido ya comenzó a ser gestionado.'
                ]);
                return;
            }

            /*
             * ====================================================
             * ACTUALIZAR
             * ====================================================
             */

            $actualizado =$this->model->iniciarGestionPedidoModel(
                    intval($pedido['idpedido']),
                    $idusuario
                );

            if (!$actualizado) {
                throw new Exception(
                    'No fue posible actualizar el pedido.'
                );
            }

            /*
             * ====================================================
             * BITÁCORA
             * ====================================================
             */

            $descripcion ='Se inició la gestión del pedido '. ($pedido['folio_pedido'] ?? $clave). ' desde el módulo administrativo.';

            $idBitacora =$this->model->insertBitacoraEvento([
                    'idpedido' =>intval($pedido['idpedido'] ),
                    'tipo_evento' =>'INICIO_GESTION',
                    'descripcion' =>$descripcion,
                    'estatus_anterior' =>'PENDIENTE',
                    'estatus_nuevo' =>'EN_REVISION',
                    'usuario_registro' =>$idusuario,
                    'origen' =>'ADMIN'
                ]);

            /*
             * No detenemos el proceso si la bitácora
             * falla, pero lo dejamos registrado en log.
             */

            if (intval($idBitacora)<= 0) {
                error_log(
                    'No fue posible insertar bitácora '
                    . 'del pedido '
                    . $pedido['idpedido']
                );

            }

            /*
             * ====================================================
             * RESPUESTA
             * ====================================================
             */

            echo json_encode([

                'status' =>true,
                'message' =>'La gestión del pedido inició correctamente.',
                'data' => [
                    'idpedido' =>intval($pedido['idpedido']),
                    'clave' => $clave,
                    'folio' =>$pedido['folio_pedido'] ?? '',
                    'estatus_anterior' =>'PENDIENTE',
                    'estatus_nuevo' =>'EN_REVISION'
                ]

            ]);

        } catch (Throwable $e) {

            error_log(
                'Ped_pedidos::iniciarGestion - '
                . $e->getMessage()
            );

            http_response_code(500);
            echo json_encode([
                'status' =>false,
                'message' =>'No fue posible iniciar la gestión del pedido.'
            ]);

        }
    }


    /* ============================================================
     * VALIDAR FECHA YYYY-MM-DD
     * ============================================================ */

    private function validarFecha(
        string $fecha
    ): bool {

        $formato = DateTime::createFromFormat('Y-m-d',$fecha);

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

    private function validarMes(string $mes): bool {

        return preg_match(
            '/^\d{4}-(0[1-9]|1[0-2])$/',
            $mes
        ) === 1;

    }
}

    


 ?>