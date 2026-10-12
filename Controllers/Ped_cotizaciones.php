<?php

class Ped_cotizaciones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
    }

    public function Ped_cotizaciones()
    {
        $this->validarSesionVista();
        $data = [
            'page_tag' => 'Propuestas Comerciales',
            'page_title' => 'Gestión de Propuestas Comerciales',
            'page_name' => 'ped_cotizaciones',
            'page_functions_js' => ['/modulos/clientes_pedidos/cotizaciones/index.js']
        ];
        $this->views->getView($this, 'index', $data);
    }

    public function nueva()
    {
        $this->validarSesionVista();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $data = [
            'page_tag' => 'Nueva Propuesta Comercial',
            'page_title' => 'Nueva Propuesta Comercial',
            'page_name' => 'ped_cotizaciones_nueva',
            'page_functions_js' => ['/modulos/clientes_pedidos/cotizaciones/nueva.js']
        ];
        $this->views->getView($this, 'nueva', $data);
    }


    // Listado de propuestas comerciales (una fila por propuesta).
    public function getPropuestas()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            $filtros = [
                'buscar' => trim((string) ($_GET['buscar'] ?? '')),
                'desde' => trim((string) ($_GET['desde'] ?? '')),
                'hasta' => trim((string) ($_GET['hasta'] ?? '')),
                'tipo' => strtoupper(trim((string) ($_GET['tipo'] ?? ''))),
                'estatus' => strtoupper(trim((string) ($_GET['estatus'] ?? '')))
            ];
            return [
                'message' => 'Propuestas consultadas correctamente.',
                'data' => $this->model->selectPropuestasComerciales($filtros)
            ];
        });
    }

    public function getDashboardPropuestas()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            return [
                'message' => 'Indicadores de propuestas consultados correctamente.',
                'data' => $this->model->selectDashboardPropuestas()
            ];
        });
    }

    // Se mantienen los endpoints de lectura actuales para no romper el frontend.
    public function getBandejaAutorizaciones()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            $usuario=(int)($_SESSION['idUser']??$_SESSION['userData']['idusuario']??0);
            return ['data'=>$this->model->bandejaAutorizaciones($usuario)];
        });
    }

    /** Se ejecuta DESPUÉS del commit; una falla SMTP no revierte el cambio. */
    private function notificarRevision(array $claves, int $nivel): void
    {
        if (!function_exists('sendMailLocalV2')) {
            error_log('Propuestas: sendMailLocalV2 no disponible');
            return;
        }
        $titulos=[1=>'Dirección General',2=>'Dirección Comercial',3=>'Dirección de Finanzas'];
        foreach (array_unique($claves) as $clave) {
            try {
                $correo=$this->model->correosAutorizacion($clave,$nivel);
                if (!$correo) continue;
                $d=$correo['datos'];
                foreach ($correo['destinatarios'] as $destino) {
                    $email=trim((string)$destino['email_user']);
                    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) continue;
                    $payload=[
                        'email'=>$email,
                        'asunto'=>'Autorización pendiente | '.$d['folio_cotizacion'],
                        'folio_propuesta'=>$d['folio_propuesta'],
                        'folio_cotizacion'=>$d['folio_cotizacion'],
                        'distribuidor'=>$d['distribuidor'],
                        'version'=>$d['version_actual'],
                        'total'=>$d['total'],
                        'direccion'=>$titulos[$nivel]??'Dirección',
                        'fechaHora'=>date('d/m/Y H:i'),
                        'url_revision'=>base_url().'/ped_cotizaciones/gestion/'.$d['clave_propuesta']
                    ];
                    sendMailLocalCron($payload,'cotizacion_autorizacion_pendiente','carlos.cruz@ldrsolutions.com.mx');
                } 
            } catch (Throwable $e) {
                error_log('Correo autorización '.$clave.': '.$e->getMessage());
            }
        }
    }

    public function getCotizaciones()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            $filtros = [
                'buscar' => trim((string) ($_GET['buscar'] ?? '')),
                'desde' => trim((string) ($_GET['desde'] ?? '')),
                'hasta' => trim((string) ($_GET['hasta'] ?? '')),
                'tipo' => strtoupper(trim((string) ($_GET['tipo'] ?? ''))),
                'estatus' => strtoupper(trim((string) ($_GET['estatus'] ?? '')))
            ];
            return ['message' => 'Cotizaciones consultadas correctamente.',
                    'data' => $this->model->selectCotizaciones($filtros)];
        });
    }

    public function getDashboard()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            return ['message' => 'Indicadores consultados correctamente.',
                    'data' => $this->model->selectDashboardCotizaciones()];
        });
    }

    public function getDistribuidores()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            return ['message' => 'Distribuidores obtenidos correctamente.',
                    'data' => ['distribuidores' => $this->model->selectDistribuidoresCotizacion()]];
        });
    }

    public function getPedidosCotizables()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            return ['message' => 'Pedidos obtenidos correctamente.',
                    'data' => ['pedidos' => $this->model->selectPedidosCotizables()]];
        });
    }

    public function getPedidoCotizacion($idpedido = 0)
    {
        $this->ejecutarJson(function () use ($idpedido) {
            $this->validarSesionJson();
            $idpedido = filter_var($idpedido, FILTER_VALIDATE_INT);
            if ($idpedido === false || $idpedido <= 0) {
                throw new InvalidArgumentException('El pedido recibido no es válido.');
            }
            $pedido = $this->model->selectPedidoCotizacion($idpedido);
            if (!$pedido) {
                throw new InvalidArgumentException('El pedido no está disponible para cotización.');
            }
            return [
                'message' => 'Pedido obtenido correctamente.',
                'data' => [
                    'pedido' => $pedido,
                    'detalles' => $this->model->selectDetallesPedidoCotizacion($idpedido)
                ]
            ];
        });
    }

    public function getUnidadesCotizacion()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            return ['message' => 'Unidades obtenidas correctamente.',
                    'data' => ['unidades' => $this->model->selectUnidadesCotizacion()]];
        });
    }

    
    public function guardarPropuesta()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                throw new RuntimeException('METHOD_NOT_ALLOWED');
            }

            $idUsuario = (int) ($_SESSION['idUser'] ??
                $_SESSION['userData']['idusuario'] ?? 0);
            if ($idUsuario <= 0) {
                throw new RuntimeException('No fue posible identificar al usuario administrativo.');
            }

            // Se requiere qe el formulario y la sesión compartan el mismo token.
            $tokenSesion = (string) ($_SESSION['csrf_token'] ?? '');
            $tokenPeticion = (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if ($tokenSesion === '' || $tokenPeticion === '' ||
                !hash_equals($tokenSesion, $tokenPeticion)) {
                throw new RuntimeException('CSRF_INVALID');
            }

            $contenido = file_get_contents('php://input');
            $input = json_decode($contenido, true);
            if (!is_array($input) || json_last_error() !== JSON_ERROR_NONE) {
                throw new InvalidArgumentException('El JSON recibido no es válido.');
            }


            if (!isset($input['modelos']) && isset($input['partidas'])) {
                $input['modelos'] = $input['partidas'];
            }
            if (!isset($input['tipo_propuesta']) && isset($input['tipo_cotizacion'])) {
                $input['tipo_propuesta'] = $input['tipo_cotizacion']; 
            }

            if (!method_exists($this->model, 'crearPropuestaComercial')) {
                throw new RuntimeException('Falta integrar crearPropuestaComercial() en el modelo.');
            }

            $resultado = $this->model->crearPropuestaComercial($input, $idUsuario);
            return [
                'message' => 'Propuesta y cotizaciones creadas en borrador.',
                'data' => $resultado,
                'http_code' => 201
            ];
        });
    }

    // Mantiene la ruta antigua, pero cambia intencionalmente su contrato de respuesta.
    public function guardar()
    {
        $this->guardarPropuesta();
    }

    private function validarSesionVista()
    {
        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {
            header('Location: ' . base_url() . '/login');
            exit;
        }
    }

    private function validarSesionJson()
    {
        if (empty($_SESSION['login']) || empty($_SESSION['userData'])) {
            throw new RuntimeException('SESSION_EXPIRED');
        }
    }

    private function ejecutarJson(callable $accion)
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        try {
            $resultado = $accion();
            http_response_code((int) ($resultado['http_code'] ?? 200));
            echo json_encode([
                'status' => true,
                'message' => $resultado['message'],
                'data' => $resultado['data']
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        } catch (Throwable $e) {
            $mensaje = $e->getMessage();
            $codigo = 400;
            if ($mensaje === 'SESSION_EXPIRED') {
                $codigo = 401;
                $mensaje = 'La sesión ha expirado.';
            } elseif ($mensaje === 'CSRF_INVALID') {
                $codigo = 403;
                $mensaje = 'Token de seguridad inválido. Recarga la página.';
            } elseif ($mensaje === 'METHOD_NOT_ALLOWED') {
                $codigo = 405;
                $mensaje = 'Método no permitido.';
            } elseif (!($e instanceof InvalidArgumentException)) {
                error_log('Ped_cotizaciones: ' . $e->getMessage());
                $codigo = 500;
                $mensaje = 'Ocurrió un error al procesar la solicitud.';
            }
            http_response_code($codigo);
            echo json_encode([
                'status' => false,
                'message' => $mensaje,
                'data' => []
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        }
        exit;
    }

    public function gestion($clave = '')
    {
        $this->validarSesionVista();
        if (!preg_match('/^[a-f0-9]{40}$/i', (string)$clave)) {
            http_response_code(404);
            exit('Propuesta no encontrada.');
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $propuesta = $this->model->selectPropuestaPorClave($clave);
        if (!$propuesta || !$this->model->puedeConsultarPropuesta($clave, (int)($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0))) {
            http_response_code(404);
            exit('Propuesta no encontrada.');
        }
        $data = [
            'page_tag' => 'Gestión de propuesta ' . $propuesta['folio_propuesta'],
            'page_title' => 'Gestión de propuesta comercial',
            'page_name' => 'ped_cotizaciones_gestion',
            'page_functions_js' => ['/modulos/clientes_pedidos/cotizaciones/gestion.js'],
            'clave_propuesta' => $clave
        ];
        $this->views->getView($this, 'gestion', $data);
    }

    public function getGestionPropuesta($clave = '')
    {
        $this->ejecutarJson(function () use ($clave) {
            $this->validarSesionJson();
            if (!preg_match('/^[a-f0-9]{40}$/i', (string)$clave)) {
                throw new InvalidArgumentException('Clave de propuesta inválida.');
            }
            $propuesta = $this->model->selectPropuestaPorClave($clave);
            if (!$propuesta || !$this->model->puedeConsultarPropuesta($clave, (int)($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0))) {
                throw new InvalidArgumentException('No se encontró la propuesta o no tienes acceso.');
            }
            return [
                'message' => 'Propuesta consultada.',
                'data' => [
                    'propuesta' => $propuesta,
                    'cotizaciones' => $this->model->selectCotizacionesPorPropuesta($propuesta['idpropuesta']),
                    'seguimiento' => $this->model->seguimientoAutorizaciones((int)$propuesta['idpropuesta'],(int)($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0)),
                    'puede_enviar' => (int)$propuesta['usuario_creacion'] === (int)($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0)
                ]
            ];
        });
    }

    public function guardarBorradorCotizacion()
    {
        $this->ejecutarJson(function () {
            $this->validarSesionJson();
            if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                throw new RuntimeException('METHOD_NOT_ALLOWED');
            }
            $sesion = (string)($_SESSION['csrf_token'] ?? '');
            $enviado = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
            if (!$sesion || !$enviado || !hash_equals($sesion, $enviado)) {
                throw new RuntimeException('CSRF_INVALID');
            }
            $input = json_decode(file_get_contents('php://input'), true);
            if (!is_array($input)) {
                throw new InvalidArgumentException('Datos inválidos.');
            }
            $usuario = (int)($_SESSION['idUser'] ?? $_SESSION['userData']['idusuario'] ?? 0);
            if ($usuario <= 0) {
                throw new RuntimeException('SESSION_EXPIRED');
            }
            return [
                'message' => 'Borrador actualizado correctamente.',
                'data' => $this->model->actualizarBorradorCotizacion($input, $usuario)
            ];
        });
    }


    private function validarPostGestion(): array
    {
        $this->validarSesionJson();
        if (($_SERVER['REQUEST_METHOD']??'')!=='POST') throw new RuntimeException('METHOD_NOT_ALLOWED');
        $token=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');
        if (!$token || !hash_equals((string)($_SESSION['csrf_token']??''),$token)) throw new RuntimeException('CSRF_INVALID');
        $datos=json_decode(file_get_contents('php://input'),true);
        if (!is_array($datos)) throw new InvalidArgumentException('Solicitud inválida.');
        return $datos;
    }

    public function enviarRevisionCotizaciones()
    {
        $this->ejecutarJson(function(){
            $d=$this->validarPostGestion();
            $prop=(string)($d['clave_propuesta']??'');
            $cot=isset($d['clave_cotizacion'])?(string)$d['clave_cotizacion']:null;
            if (!preg_match('/^[a-f0-9]{40}$/i',$prop) || ($cot!==null&&!preg_match('/^[a-f0-9]{40}$/i',$cot))) throw new InvalidArgumentException('Clave inválida.');
            $usuario=(int)($_SESSION['idUser']??$_SESSION['userData']['idusuario']??0);
            $resultado=$this->model->enviarCotizacionesRevision($prop,$cot,$usuario);
            $this->notificarRevision($resultado['claves'],1);
            return ['message'=>'Cotizaciones enviadas a revisión.','data'=>$resultado];
        });
    }

    public function resolverRevisionCotizacion()
    {
        $this->ejecutarJson(function(){
            $d=$this->validarPostGestion();
            $cot=(string)($d['clave_cotizacion']??'');
            if (!preg_match('/^[a-f0-9]{40}$/i',$cot)) throw new InvalidArgumentException('Clave inválida.');
            $usuario=(int)($_SESSION['idUser']??$_SESSION['userData']['idusuario']??0);
            $resultado=$this->model->resolverAutorizacion($cot,(string)($d['decision']??''),trim((string)($d['comentario']??'')),$usuario);
            if ($resultado['decision']==='APROBADA' && (int)$resultado['nivel']<3) {
                $this->notificarRevision([$cot],(int)$resultado['nivel']+1);
            }
            return ['message'=>'Decisión registrada correctamente.','data'=>$resultado];
        });
    }

    public function prepararAjusteCotizacion()
    {
        $this->ejecutarJson(function(){
            $d=$this->validarPostGestion();
            $cot=(string)($d['clave_cotizacion']??'');
            if (!preg_match('/^[a-f0-9]{40}$/i',$cot)) throw new InvalidArgumentException('Clave inválida.');
            $usuario=(int)($_SESSION['idUser']??$_SESSION['userData']['idusuario']??0);
            return ['message'=>'Nueva versión creada para ajustes.','data'=>$this->model->prepararAjusteCotizacion($cot,$usuario)];
        });
    }

}
