<?php
class Cli_clientes extends Controllers
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
        getPermisos(MCLIENTES_CLIENTES);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA REDIRIGIR A LA VISTA PRINCIPAL INDEX.PHP INLCUYENDO EL ARCHIVO JS 
    |--------------------------------------------------------------------------
    */

    public function Cli_clientes()
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }
        $data['page_tag'] = "Clientes";
        $data['page_title'] = "Clientes";
        $data['page_functions_js'] = "/clientes/index.js";
        $data['tipos_cliente'] = $this->model->selectOptionTipoClientes();
        $this->views->getView($this, "index", $data);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS CLIENTES
    |--------------------------------------------------------------------------
    */

    public function getLogoReporte()
    {
        $contexto = stream_context_create(['http' => ['timeout' => 5, 'follow_location' => 0]]);
        $logo = @file_get_contents(
            'https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png',
            false,
            $contexto,
            0,
            2 * 1024 * 1024
        );
        if ($logo === false || substr($logo, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            http_response_code(502);
            exit;
        }

        header('Content-Type: image/png');
        header('Cache-Control: private, max-age=3600');
        echo $logo;
        exit;
    }

    public function getFichaCliente()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->json(false, 'No tiene permisos para consultar la ficha del cliente.', [], 403);
        }

        $codigo = trim((string) ($_GET['codigo'] ?? ''));
        if ($codigo === '' || strlen($codigo) > 50) {
            $this->json(false, 'Código de cliente inválido.', [], 400);
        }

        header('Cache-Control: no-store');
        try {
            $general = $this->model->selectGeneralClientePorCodigo($codigo);
            if (!$general) {
                $this->json(false, 'No se encontró el cliente.', [], 404);
            }

            $idcliente = (int) $general['idcliente'];
            $fiscal = $this->model->selectFiscalCliente($idcliente);
            $comercial = $this->model->selectComercialCliente($idcliente);
            if ($comercial && !empty($comercial['precioid'])) {
                $comercial['lista_precio_nombre'] = $this->model->selectNombreListaPrecio((string) $comercial['precioid'])
                    ?? $comercial['precioid'];
            }

            $domicilio = [
                'calle' => 'Calle', 'numero_exterior' => 'Número exterior', 'numero_interior' => 'Número interior',
                'colonia' => 'Colonia', 'codigo_postal' => 'Código postal', 'municipio' => 'Municipio',
                'estado_republica' => 'Estado', 'pais' => 'País'
            ];
            $fechas = ['estado' => 'Estatus', 'fecha_creacion' => 'Fecha de registro', 'fecha_actualizacion' => 'Última actualización'];
            $secciones = [
                ['Información general', [$general], [
                    'codigo_cliente' => 'Código', 'nombre_tipo_cliente' => 'Tipo de cliente', 'tipo_persona' => 'Tipo de persona',
                    'razon_social' => 'Razón social', 'nombre_comercial' => 'Nombre comercial', 'correo' => 'Correo',
                    'telefono' => 'Teléfono', 'celular' => 'Celular', 'sitio_web' => 'Sitio web', 'fecha_alta' => 'Fecha de alta',
                    'clave_distribuidor' => 'Clave del distribuidor', 'zona_comercial' => 'Zona comercial', 'territorio' => 'Territorio',
                    'responsable_comercial' => 'Responsable comercial', 'requiere_acceso_portal' => 'Requiere acceso al portal',
                    'correo_acceso' => 'Correo de acceso', 'numero_empleado' => 'Número de empleado', 'departamento' => 'Departamento',
                    'centro_costos' => 'Centro de costos', 'jefe_inmediato' => 'Jefe inmediato', 'correo_corporativo' => 'Correo corporativo',
                    'origen_cliente' => 'Origen del cliente', 'ejecutivo_asignado' => 'Ejecutivo asignado', 'segmento_mercado' => 'Segmento de mercado',
                    'dependencia' => 'Dependencia', 'unidad_administrativa' => 'Unidad administrativa', 'nivel_gobierno' => 'Nivel de gobierno',
                    'partida_presupuestal' => 'Partida presupuestal', 'tipo_contratacion' => 'Tipo de contratación'
                ] + $fechas],
                ['Información fiscal', $fiscal ? [$fiscal] : [], [
                    'rfc' => 'RFC', 'curp' => 'CURP', 'regimen_fiscal' => 'Régimen fiscal', 'uso_cfdi' => 'Uso de CFDI',
                    'codigo_postal_fiscal' => 'Código postal fiscal', 'correo_facturacion' => 'Correo de facturación',
                    'requiere_factura' => 'Requiere factura'
                ] + $fechas],
                ['Contactos', $this->model->selectContactosCliente($idcliente), [
                    'nombre' => 'Nombre', 'puesto' => 'Puesto', 'tipo' => 'Tipo de contacto', 'correo' => 'Correo',
                    'telefono' => 'Teléfono', 'notificar' => 'Recibe notificaciones',
                    'doble_autenticacion' => 'Doble autenticación', 'fecha_envio_accesos' => 'Último envío de accesos'
                ] + $fechas],
                ['Sucursales', $this->model->selectSucursalesCliente($idcliente), [
                    'nombre_sucursal' => 'Sucursal', 'responsable' => 'Responsable', 'correo' => 'Correo', 'telefono' => 'Teléfono'
                ] + $domicilio + $fechas],
                ['Direcciones', $this->model->selectDireccionesCliente($idcliente), [
                    'tipo_direccion' => 'Tipo de dirección'
                ] + $domicilio + ['referencias' => 'Referencias'] + $fechas],
                ['Información comercial', $comercial ? [$comercial] : [], [
                    'lista_precio_nombre' => 'Lista de precios', 'moneda' => 'Moneda', 'forma_pago' => 'Forma de pago',
                    'limite_credito' => 'Límite de crédito', 'dias_credito' => 'Días de crédito',
                    'descuento_autorizado' => 'Descuento autorizado', 'ejecutivo_cuenta' => 'Ejecutivo de cuenta',
                    'canal_venta' => 'Canal de venta', 'clasificacion_comercial' => 'Clasificación',
                    'observaciones_comerciales' => 'Observaciones'
                ]],
                ['Bancos', $this->model->selectBancosCliente($idcliente), [
                    'banco' => 'Banco', 'titular_cuenta' => 'Titular de la cuenta', 'numero_cuenta' => 'Número de cuenta',
                    'clabe' => 'CLABE', 'moneda_cuenta' => 'Moneda', 'referencia_bancaria' => 'Referencia bancaria'
                ] + $fechas]
            ];

            $ficha = [];
            foreach ($secciones as [$titulo, $registros, $campos]) {
                $ficha[] = [
                    'titulo' => $titulo,
                    'registros' => array_map(
                        fn (array $registro) => $this->camposFichaCliente($registro, $campos),
                        $registros ?: []
                    )
                ];
            }

            $this->json(true, 'Ficha consultada.', [
                'codigo' => $general['codigo_cliente'],
                'nombre' => $general['razon_social'],
                'secciones' => $ficha
            ]);
        } catch (Throwable $error) {
            error_log('getFichaCliente: ' . $error->getMessage());
            $this->json(false, 'No fue posible consultar la ficha del cliente.', [], 500);
        }
    }

    private function camposFichaCliente(array $registro, array $campos): array
    {
        $resultado = [];
        $booleanos = ['requiere_acceso_portal', 'requiere_factura', 'notificar', 'doble_autenticacion'];
        foreach ($campos as $campo => $etiqueta) {
            $valor = trim((string) ($registro[$campo] ?? ''));
            if ($valor === '') {
                continue;
            }
            if (in_array($campo, $booleanos, true)) {
                $valor = $valor === '1' ? 'Sí' : 'No';
            } elseif ($campo === 'estado') {
                $valor = [1 => 'Inactivo', 2 => 'Activo', 3 => 'Suspendido'][(int) $valor] ?? $valor;
            } elseif ($campo === 'limite_credito') {
                $valor = number_format((float) $valor, 2, '.', ',') . ' ' . ($registro['moneda'] ?? '');
            } elseif ($campo === 'descuento_autorizado') {
                $valor .= ' %';
            }
            $resultado[] = [$etiqueta, $valor];
        }
        return $resultado;
    }

    public function getTodos()
    { 
        header('Content-Type: application/json; charset=utf-8');

        try {

            $arrData = $this->model->selectTodos(max(0, (int) ($_GET['tipo'] ?? 0)));

            if (!is_array($arrData)) {
                $arrData = [];
            }

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        } catch (Throwable $error) {

            http_response_code(500);

            echo json_encode([
                'status' => false,
                'message' => 'Error al consultar los clientes.',
                'error' => $error->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS DISTRIBUIDORES
    |--------------------------------------------------------------------------
    */
    public function getDistribuidores()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {

            $arrData = $this->model->selectDistribuidores();

            if (!is_array($arrData)) {
                $arrData = [];
            }

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        } catch (Throwable $error) {

            http_response_code(500);

            echo json_encode([
                'status' => false,
                'message' => 'Error al consultar los clientes.',
                'error' => $error->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS CLIENTES INTERNOS
    |--------------------------------------------------------------------------
    */
    public function getInternos()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {

            $arrData = $this->model->selectInternos();

            if (!is_array($arrData)) {
                $arrData = [];
            }

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        } catch (Throwable $error) {

            http_response_code(500);

            echo json_encode([
                'status' => false,
                'message' => 'Error al consultar los clientes.',
                'error' => $error->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS CLIENTES EXTERNOS
    |--------------------------------------------------------------------------
    */
    public function getExternos()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {

            $arrData = $this->model->selectExternos();

            if (!is_array($arrData)) {
                $arrData = [];
            }

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        } catch (Throwable $error) {

            http_response_code(500);

            echo json_encode([
                'status' => false,
                'message' => 'Error al consultar los clientes.',
                'error' => $error->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS CLIENTES GUBERNAMENTALES
    |--------------------------------------------------------------------------
    */
    public function getGubernamentales()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {

            $arrData = $this->model->selectGubernamentales();

            if (!is_array($arrData)) {
                $arrData = [];
            }

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

        } catch (Throwable $error) {

            http_response_code(500);

            echo json_encode([
                'status' => false,
                'message' => 'Error al consultar los clientes.',
                'error' => $error->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA REDIRIGIR A LA VISTA DE CREAR NUEVO CLIENTE INCLUYENDO EL ARCHIVO JS
    |--------------------------------------------------------------------------
    */
    public function create()
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }

        // dep($_SESSION);
        $data['page_tag'] = "Clientes";
        $data['page_title'] = "Clientes";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "/clientes/create.js";
        $data['tipos_cliente'] = $this->model->selectOptionTipoClientes();
        $data['listas_precio'] = $this->model->selectListasPrecioActivas();
        $this->views->getView($this, "create", $data);
    }


    public function editar($idcliente)
{
    // if (empty($_SESSION['permisosMod']['u'])) {
    //     header("Location: " . base_url() . '/dashboard');
    //     exit;
    // }

    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {
        header("Location: " . base_url() . '/cli_clientes');
        exit;
    }

    /*
     * Verificamos que el cliente exista.
     */
    $cliente = $this->model->selectClienteById($idcliente);


    if (empty($cliente)) {
        header("Location: " . base_url() . '/cli_clientes');
        exit;
    } 

    $data['page_tag'] = "Editar cliente";
    $data['page_title'] = "Editar cliente";
    $data['page_name'] = "editar_cliente";

    /*
     * Reutilizamos la misma vista create.
     */
    $data['idcliente'] = $idcliente;
    $data['cliente'] = $cliente;
    $data['tipos_cliente'] = $this->model->selectOptionTipoClientes((int) $cliente['idtipo_cliente']);

    // $data['page_functions_js'] = [
    //     "/clientes/cli_clientes.js"
    // ];

    $data['page_functions_js'] = "/clientes/create.js";
    $data['listas_precio'] = $this->model->selectListasPrecioActivas();

    $this->views->getView(
        $this,
        "create",
        $data
    );
}

    /*
|--------------------------------------------------------------------------
| FUNCIÓN PARA REDIRIGIR A LA VISTA DE ACCESOS A CLIENTES INCLUYENDO SU ARCHIVO JS
|--------------------------------------------------------------------------
*/
    public function accesos($idcliente)
    {

        // if (empty($_SESSION['permisosMod']['r'])) {
        //     header("Location:" . base_url() . '/dashboard');
        // }
        $data['page_tag'] = "Clientes";
        $data['page_title'] = "Clientes";
        $data['page_functions_js'] = "/clientes/accesos.js";

        // Enviar el id del cliente a la vista
        $data['idcliente'] = $idcliente;
        $this->views->getView($this, "accesos", $data);
    }




    /*
|--------------------------------------------------------------------------
| FUNCIÓN PARA VER EL HISTORICO DE ACCESOS DE CLIENTES AL PORTAL DE DISTRIBUIDORES 
|--------------------------------------------------------------------------
*/
    public function getAccesoCliente($idcliente)
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     $this->responseJson(false, 'No tiene permisos para consultar.');
        // }

        $idcliente = intval($idcliente);

        if ($idcliente <= 0) {
            $this->responseJson(false, 'El cliente no es válido.');
        }

        $cliente = $this->model->selectClienteAcceso($idcliente);

        if (empty($cliente)) {
            $this->responseJson(false, 'No se encontró el cliente.');
        }

        if (empty($cliente['correo'])) {
            $this->responseJson(
                false,
                'El cliente no tiene un correo registrado.'
            );
        }

        $acceso = $this->model->selectUsuarioAccesoPorCliente($idcliente);

        $correo = strtolower(trim($cliente['correo']));

        $usuarioSugerido = explode('@', $correo)[0];

        $data = [
            'idcliente' => $idcliente,
            'tipo_cliente' => $cliente['tipo_cliente'],
            'nombre_cliente' => $cliente['nombre_comercial']
                ?: $cliente['razon_social'],

            'idusuario_acceso' => intval(
                $acceso['idusuario_acceso'] ?? 0
            ),

            'usuario_acceso' => $acceso['nombre_usuario']
                ?? $usuarioSugerido,

            'correo_acceso' => $correo,

            'liga_acceso' => $acceso['url_portal']
                ?? base_url() . '/orders/login',

            'doble_autenticacion' => intval(
                $acceso['doble_autenticacion'] ?? 0
            ),

            'requiere_cambio_password' => intval(
                $acceso['requiere_cambio_password'] ?? 1
            ),

            'fecha_cambio_password' =>
                $acceso['fecha_cambio_password'] ?? null,

            'ultimo_login' =>
                $acceso['ultimo_login'] ?? null,

            'ultimo_envio_accesos' =>
                $acceso['ultimo_envio_accesos'] ?? null,

            'estado_acceso' => intval(
                $acceso['estado'] ?? 0
            )
        ];

        $this->responseJson(
            true,
            'Información obtenida correctamente.',
            $data
        );
    }

    public function responseJson(
        bool $status,
        string $message,
        $data = null
    ) {
        header('Content-Type: application/json; charset=utf-8');

        $response = [
            'status' => $status,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        echo json_encode(
            $response,
            JSON_UNESCAPED_UNICODE
        );

        die();
    }



    public function setAccesoCliente()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->json(false, 'Método no permitido.', [], 405);
        }

        if (empty($_SESSION['permisosMod']['w'])) {
            $this->responseJson(
                false,
                'No tiene permisos para guardar accesos.'
            );
        }

        $idcliente = intval($_POST['idcliente'] ?? 0);
        $idusuarioAcceso = intval(
            $_POST['idusuario_acceso'] ?? 0
        );

        $usuarioAcceso = trim(
            $_POST['usuario_acceso'] ?? ''
        );

        $passwordTemporal = trim(
            $_POST['password_temporal'] ?? ''
        );

        $ligaAcceso = trim(
            $_POST['liga_acceso'] ?? ''
        );

        $dobleAutenticacion = intval(
            $_POST['doble_autenticacion'] ?? 0
        );

        $cliente = $this->model->selectClienteAcceso($idcliente);
        if (!$cliente) {
            $this->responseJson(false, 'No se encontró el cliente.');
        }

        $idcontacto = (int) ($_POST['idcontacto'] ?? 0);
        $contacto = null;
        $correoAcceso = strtolower(trim((string) $cliente['correo']));

        if ($idcontacto > 0) {
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
                $this->json(false, 'Solicitud inválida.', [], 400);
            }
            if ((int) $cliente['idtipo_cliente'] !== 1 || (int) $cliente['estado'] !== 2) {
                $this->responseJson(false, 'Los accesos por contacto requieren un distribuidor activo.');
            }

            $contacto = $this->model->selectContactoAcceso($idcliente, $idcontacto);
            if (!$contacto) {
                $this->responseJson(false, 'No se encontró un contacto vigente para el distribuidor.');
            }

            $correoAcceso = strtolower(trim((string) $contacto['correo']));
            $acceso = $this->model->selectUsuarioContacto($idcliente, $idcontacto);

            // Un contacto con el mismo correo del cliente puede vincular la cuenta general existente.
            if (!$acceso && strcasecmp($correoAcceso, (string) $cliente['correo']) === 0) {
                $accesoGeneral = $this->model->selectUsuarioAccesoPorCliente($idcliente);
                if (!empty($accesoGeneral) && empty($accesoGeneral['idcontacto'])
                    && strcasecmp($correoAcceso, (string) $accesoGeneral['correo']) === 0) {
                    $acceso = $accesoGeneral;
                }
            }

            $idusuarioAcceso = (int) ($acceso['idusuario_acceso'] ?? 0);
            $usuarioAcceso = $acceso['nombre_usuario'] ?? $correoAcceso;
            $ligaAcceso = $acceso['url_portal'] ?? base_url() . '/orders/login';
            $dobleAutenticacion = (int) ((string) ($_POST['doble_autenticacion']
                ?? $acceso['doble_autenticacion'] ?? '0') === '1');
            $alfabeto = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%&*+-_?';
            $caracteres = str_split('Aa1!');
            for ($i = 0; $i < 11; $i++) {
                $caracteres[] = $alfabeto[random_int(0, strlen($alfabeto) - 1)];
            }
            for ($i = count($caracteres) - 1; $i > 0; $i--) {
                $j = random_int(0, $i);
                [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
            }
            $passwordTemporal = implode('', $caracteres);
        } elseif ($idusuarioAcceso > 0) {
            $acceso = $this->model->selectUsuarioAccesoPorCliente($idcliente);
            if ((int) ($acceso['idusuario_acceso'] ?? 0) !== $idusuarioAcceso) {
                $this->responseJson(false, 'La cuenta general no corresponde a este cliente.');
            }
        }

        if (!$usuarioAcceso || !$ligaAcceso || !$this->validarPasswordTemporal($passwordTemporal)) {
            $this->responseJson(false, 'Completa el usuario, liga y contraseña temporal de 15 caracteres.');
        }
        if (!filter_var($correoAcceso, FILTER_VALIDATE_EMAIL) || strlen($correoAcceso) > 50) {
            $this->responseJson(false, 'El correo registrado debe ser válido y tener hasta 50 caracteres.');
        }

        $esNuevoAcceso = $idusuarioAcceso <= 0;
        $passwordHash = password_hash(
            $passwordTemporal,
            PASSWORD_DEFAULT
        );

        $usuarioAdmin = intval(
            $_SESSION['idUser'] ?? 0
        );

        try {
            if ($idusuarioAcceso > 0) {
                $guardado = $this->model->updateUsuarioAcceso(
                    $idusuarioAcceso,
                    $idcliente,
                    $usuarioAcceso,
                    $correoAcceso,
                    $passwordHash,
                    $ligaAcceso,
                    $dobleAutenticacion,
                    $usuarioAdmin,
                    $idcontacto ?: null
                );
            } else {
                $guardado = $this->model->insertUsuarioAcceso(
                    $idcliente,
                    $usuarioAcceso,
                    $contacto['nombre'] ?? ($cliente['nombre_comercial'] ?: $cliente['razon_social']),
                    '',
                    $correoAcceso,
                    $passwordHash,
                    $contacto['telefono'] ?? $cliente['telefono'] ?? '',
                    $ligaAcceso,
                    $dobleAutenticacion,
                    $usuarioAdmin,
                    $idcontacto ?: null
                );

                $idusuarioAcceso = intval($guardado);
            }
        } catch (Throwable $error) {
            $this->responseJson(false, 'No se pudo guardar el acceso. Verifica si el correo o usuario ya está registrado.');
        }

        if (!$guardado) {
            $this->responseJson(
                false,
                'No fue posible guardar las credenciales.'
            );
        }

        $nombreCliente = !empty($cliente['nombre_comercial'])
            ? $cliente['nombre_comercial']
            : $cliente['razon_social'];

        $datosCorreo = [
            /*
             * Datos utilizados por tu función de correo.
             * Ajusta estas claves si sendMailLocalCron usa otros nombres.
             */
            'email' => $correoAcceso,
            'nombre_destinatario' => $contacto['nombre'] ?? $nombreCliente,
            'asunto' => $esNuevoAcceso
                ? 'Accesos al Portal de Pedidos'
                : 'Actualización de accesos al Portal de Pedidos',

            /*
             * Datos visuales de la plantilla.
             */
            'nombre_cliente' => $nombreCliente,
            'codigo_cliente' => $cliente['codigo_cliente'] ?? '',
            'usuario_acceso' => $correoAcceso,
            'password_temporal' => $passwordTemporal,
            'liga_acceso' => $ligaAcceso,
            'doble_autenticacion' => $dobleAutenticacion,
            'fecha_notificacion' => date('d/m/Y H:i'),
            'anio' => date('Y'),

            /*
             * Puedes cambiar esta liga por la ubicación real de tu logotipo.
             */
            'logo_url' => 'https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png'
        ];

        $cc = 'carlos.cruz@ldrsolutions.com.mx';

        try {
            $correoEnviado = sendMailLocalCron($datosCorreo, 'email_clientes_accesos', $cc);
        } catch (Throwable $error) {
            $correoEnviado = false;
            error_log('Error al enviar accesos al portal para la cuenta ' . $idusuarioAcceso);
        }

        $this->model->insertEnvioAcceso(
            $idusuarioAcceso,
            $idcliente,
            $correoAcceso,
            $esNuevoAcceso
            ? 'CREDENCIALES'
            : 'REENVIO_CREDENCIALES',
            $datosCorreo['asunto'],
            $correoEnviado ? 'ENVIADO' : 'FALLIDO',
            $correoEnviado
            ? 'Credenciales enviadas correctamente.'
            : 'Las credenciales se guardaron, pero no fue posible enviar el correo.',
            $usuarioAdmin
        );

        $this->model->insertLogAcceso(
            $idusuarioAcceso,
            $idcliente,
            'CREDENCIALES_GENERADAS',
            $correoEnviado ? 'EXITOSO' : 'FALLIDO',
            $correoAcceso,
            $this->getClientIp(),
            'Panel administrativo',
            'Escritorio',
            null,
            null,
            null,
            null,
            session_id(),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $correoEnviado
            ? 'Se generó y envió una contraseña temporal.'
            : 'Se guardó la contraseña, pero el correo no pudo enviarse.'
        );

        if (!$correoEnviado) {
            $this->responseJson(
                false,
                'Las credenciales se guardaron, pero no fue posible enviar el correo.',
                [
                    'idusuario_acceso' => $idusuarioAcceso
                ]
            );
        }

        $this->model->updateFechaEnvioAccesos(
            $idusuarioAcceso
        );

        $this->responseJson(
            true,
            'Las credenciales fueron guardadas y enviadas correctamente.',
            [
                'idusuario_acceso' => $idusuarioAcceso,
                'fecha_envio_accesos' => $this->model->selectUsuarioAccesoPorId($idusuarioAcceso)['ultimo_envio_accesos'] ?? null
            ]
        );
    }



    public function validarPasswordTemporal(string $password)
    {
        if (strlen($password) !== 15) {
            return false;
        }

        return preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match('/[!@#$%&*+\-_?]/', $password);
    }


    public function getClientIp()
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }



    public function getEnviosCredenciales($idcliente)
    {
        if (empty($_SESSION['permisosMod']['r']) && empty($_SESSION['permisosMod']['w'])) {
            $this->json(false, 'No tiene permisos para consultar los envíos.', [], 403);
        }

        $idcliente = (int) $idcliente;
        $this->validarCliente($idcliente);
        $envios = $this->model->selectEnviosCredenciales($idcliente);
        $this->responseJson(true, 'Envíos consultados correctamente.', $envios ?: []);
    }

    public function getLogsAcceso($idcliente)
    {
        // if (empty($_SESSION['permisosMod']['r'])) {
        //     $this->responseJson(
        //         false,
        //         'No tiene permisos para consultar el histórico.'
        //     );
        // }

        $idcliente = intval($idcliente);

        if ($idcliente <= 0) {
            $this->responseJson(
                false,
                'El cliente no es válido.'
            );
        }

        $logs = $this->model->selectLogsAcceso(
            $idcliente
        );

        $this->responseJson(
            true,
            'Histórico obtenido correctamente.',
            $logs
        );
    }



    public function cambiarPasswordPortal()
    {
        if (empty($_SESSION['portal_idusuario_acceso'])) {
            $this->responseJson(
                false,
                'La sesión no es válida.'
            );
        }

        $idusuarioAcceso = intval(
            $_SESSION['portal_idusuario_acceso']
        );

        $passwordActual = trim(
            $_POST['password_actual'] ?? ''
        );

        $passwordNueva = trim(
            $_POST['password_nueva'] ?? ''
        );

        $usuario = $this->model->selectUsuarioAccesoPorId(
            $idusuarioAcceso
        );

        if (empty($usuario)) {
            $this->responseJson(
                false,
                'No se encontró el usuario.'
            );
        }

        if (
            !password_verify(
                $passwordActual,
                $usuario['password_hash']
            )
        ) {
            $this->responseJson(
                false,
                'La contraseña actual no es correcta.'
            );
        }

        if (
            strlen($passwordNueva) < 10
            || !preg_match('/[A-Z]/', $passwordNueva)
            || !preg_match('/[a-z]/', $passwordNueva)
            || !preg_match('/[0-9]/', $passwordNueva)
            || !preg_match('/[^A-Za-z0-9]/', $passwordNueva)
        ) {
            $this->responseJson(
                false,
                'La nueva contraseña no cumple con los requisitos de seguridad.'
            );
        }

        $nuevoHash = password_hash(
            $passwordNueva,
            PASSWORD_DEFAULT
        );

        $actualizado = $this->model->updatePasswordDefinitiva(
            $idusuarioAcceso,
            $nuevoHash
        );

        if (!$actualizado) {
            $this->responseJson(
                false,
                'No fue posible cambiar la contraseña.'
            );
        }

        $this->model->insertLogAcceso(
            $idusuarioAcceso,
            intval($usuario['idcliente']),
            'PASSWORD_CAMBIADA',
            'EXITOSO',
            $usuario['correo'],
            $this->getClientIp(),
            null,
            null,
            null,
            null,
            null,
            null,
            session_id(),
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            'El distribuidor cambió su contraseña temporal.'
        );

        $this->responseJson(
            true,
            'La contraseña fue actualizada correctamente.'
        );
    }







    public function getSelectRegimenFiscal($tipoPersona = null)
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionRegimenFiscal($tipoPersona);

        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['id'] . '">' .
                $row['c_regimen_fiscal'] . ' - ' . $row['descripcion'] .
                '</option>';
        }

        echo $htmlOptions;
        die();
    }



    public function getSelectPaises()
    {
        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionPaises();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['id'] . '">' . $arrData[$i]['nombre'] . '</option>';
                }
            }
        }
        echo $htmlOptions;
        die();
    }

    public function getSelectEstados($pais_id)
    {
        $htmlOptions = '<option value="">--Seleccione estado--</option>';
        $arrData = $this->model->selectEstadosByPais(intval($pais_id));

        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
        }

        echo $htmlOptions;
        die();
    }

    public function getSelectMunicipios($estado_id)
    {
        $htmlOptions = '<option value="">--Seleccione municipio--</option>';
        $arrData = $this->model->selectMunicipiosByEstado(intval($estado_id));

        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['id'] . '">' . $row['nombre'] . '</option>';
        }

        echo $htmlOptions;
        die();
    }

    public function getRegionByEstado($estado_id)
    {
        $estado_id = intval($estado_id);

        $arrData = $this->model->selectRegionByEstado($estado_id);

        if (!empty($arrData)) {
            echo json_encode([
                "status" => true,
                "data" => $arrData
            ]);
        } else {
            echo json_encode([
                "status" => false
            ]);
        }
        die();
    }



    /**
     * Endpoint encargado de generar y devolver el próximo código
     * disponible para un tipo de cliente.
     *
     * Respuesta exitosa:
     * {
     *     "status": true,
     *     "message": "Código generado correctamente.",
     *     "data": {
     *         "codigo_cliente": "CLI-DIS-0001",
     *         "consecutivo": 1,
     *         "prefijo": "CLI-DIS-",
     *         "tipo_cliente": "Distribuidor"
     *     }
     * }
     *
     * @param mixed $idtipoCliente ID del tipo de cliente recibido desde la URL.
     * @return void
     */
    public function getCodigoCliente($idtipoCliente)
    {
        try {
            /*
             * Validamos que el usuario tenga permiso de lectura
             * sobre el módulo actual.
             */
            // if (empty($_SESSION['permisosMod']['r'])) {
            //     $this->responderJson(
            //         false,
            //         'No tiene permisos para consultar esta información.',
            //         [],
            //         403
            //     );
            // }

            /*
             * Convertimos el parámetro recibido a un número entero.
             * Esto evita recibir cadenas, valores decimales o contenido inválido.
             */
            $idtipoCliente = intval($idtipoCliente);

            /*
             * El ID debe ser mayor a cero para considerarse válido.
             */
            if ($idtipoCliente <= 0) {
                $this->responderJson(
                    false,
                    'El tipo de cliente no es válido.',
                    [],
                    400
                );
            }

            /*
             * Solicitamos la generación del código correspondiente
             * al tipo de cliente seleccionado.
             */
            $resultado = $this->generarCodigoCliente($idtipoCliente);

            /*
             * Si la función generadora devuelve un error,
             * enviamos el mensaje correspondiente al frontend.
             */
            if (empty($resultado['status'])) {
                $this->responderJson(
                    false,
                    $resultado['message'] ?? 'No fue posible generar el código.',
                    [],
                    400
                );
            }

            /*
             * Si todo es correcto, devolvemos el código generado,
             * su consecutivo, prefijo y nombre del tipo de cliente.
             */
            $this->responderJson(
                true,
                'Código generado correctamente.',
                [
                    'codigo_cliente' => $resultado['codigo_cliente'],
                    'consecutivo' => $resultado['consecutivo'],
                    'prefijo' => $resultado['prefijo'],
                    'tipo_cliente' => $resultado['tipo_cliente']
                ],
                200
            );
        } catch (Throwable $e) {
            /*
             * Registramos el error real en el log del servidor.
             * Es recomendable no mostrar archivos, líneas o mensajes
             * internos directamente al usuario en producción.
             */
            error_log(
                'Error en getCodigoCliente: '
                . $e->getMessage()
                . ' | Archivo: '
                . $e->getFile()
                . ' | Línea: '
                . $e->getLine()
            );

            /*
             * Devolvemos un mensaje genérico al frontend.
             */
            $this->responderJson(
                false,
                'Ocurrió un error al generar el código del cliente.',
                [],
                500
            );
        }
    }


    /**
     * Genera el siguiente código disponible para un tipo de cliente.
     *
     * Ejemplos:
     *
     * Distribuidor:
     * CLI-DIS-0001
     *
     * Cliente interno:
     * CLI-INT-0001
     *
     * Cliente externo:
     * CLI-EXT-0001
     *
     * Cliente gubernamental:
     * CLI-GUB-0001
     *
     * @param int $idtipoCliente ID del tipo de cliente.
     * @return array Resultado de la generación del código.
     */
    private function generarCodigoCliente(int $idtipoCliente)
    {
        /*
         * Consultamos en la base de datos la información
         * correspondiente al tipo de cliente seleccionado.
         */
        $tipoCliente = $this->model->selectTipoCliente($idtipoCliente);

        /*
         * Si la consulta no devuelve información,
         * significa que el tipo de cliente no existe.
         */
        if (empty($tipoCliente)) {
            return [
                'status' => false,
                'message' => 'No se encontró el tipo de cliente.'
            ];
        }

        /*
         * Estados utilizados en el sistema:
         *
         * 2 = Activo
         * 1 = Inactivo
         * 0 = Eliminado
         *
         * Solamente se permite generar códigos para tipos activos.
         */
        if (intval($tipoCliente['estado']) !== 2) {
            return [
                'status' => false,
                'message' => 'El tipo de cliente no se encuentra activo.'
            ];
        }

        /*
         * Normalizamos el nombre para evitar diferencias por:
         *
         * - Mayúsculas y minúsculas.
         * - Espacios al inicio o al final.
         * - Caracteres con acentos.
         *
         * Ejemplo:
         * "Cliente gubernamental" se convierte en
         * "CLIENTE GUBERNAMENTAL".
         */
        $nombreTipo = $this->normalizarNombreTipoCliente(
            (string) $tipoCliente['nombre']
        );

        /*
         * Configuración de prefijos.
         *
         * Se incluyen diferentes nombres posibles para un mismo tipo,
         * por ejemplo "INTERNO" y "CLIENTE INTERNO".
         */
        $prefijos = [
            'DISTRIBUIDOR' => 'CLI-DIS-',
            'CLIENTE DIRECTO' => 'CLI-DIR-',
            'CLIENTE DE INTERCAMBIO' => 'CLI-CAN-',

            'CLIENTE INTERNO' => 'CLI-INT-',
            'INTERNO' => 'CLI-INT-',

            'CLIENTE EXTERNO' => 'CLI-EXT-',
            'EXTERNO' => 'CLI-EXT-',

            'CLIENTE GUBERNAMENTAL' => 'CLI-GUB-',
            'GUBERNAMENTAL' => 'CLI-GUB-'
        ];

        /*
         * Validamos que el tipo de cliente tenga un prefijo configurado.
         */
        if (!isset($prefijos[$nombreTipo])) {
            return [
                'status' => false,
                'message' => 'El tipo de cliente no tiene una nomenclatura configurada.'
            ];
        }

        /*
         * Obtenemos el prefijo correspondiente.
         *
         * Ejemplo:
         * DISTRIBUIDOR = CLI-DIS-
         */
        $prefijo = $prefijos[$nombreTipo];

        /*
         * Consultamos el último consecutivo utilizado
         * para ese tipo de cliente y prefijo.
         *
         * Por ejemplo, si el último código es CLI-DIS-0015,
         * el modelo deberá devolver 15.
         */
        $ultimoConsecutivo = $this->model->selectUltimoConsecutivoCliente(
            $prefijo
        );

        /*
         * Convertimos el resultado a entero para evitar errores
         * cuando el modelo devuelve null, false o una cadena numérica.
         */
        $ultimoConsecutivo = intval($ultimoConsecutivo);

        /*
         * Incrementamos el consecutivo en uno.
         */
        $nuevoConsecutivo = $ultimoConsecutivo + 1;

        /*
         * Construimos el código final.
         *
         * str_pad completa el consecutivo con ceros a la izquierda
         * hasta alcanzar cuatro posiciones.
         *
         * Ejemplos:
         *
         * 1   = 0001
         * 15  = 0015
         * 150 = 0150
         */
        $codigoCliente = $prefijo . str_pad(
            (string) $nuevoConsecutivo,
            4,
            '0',
            STR_PAD_LEFT
        );

        /*
         * Devolvemos la información generada.
         */
        return [
            'status' => true,
            'codigo_cliente' => $codigoCliente,
            'consecutivo' => $nuevoConsecutivo,
            'prefijo' => $prefijo,
            'tipo_cliente' => $tipoCliente['nombre']
        ];
    }


    /**
     * Normaliza el nombre de un tipo de cliente.
     *
     * La función:
     *
     * 1. Elimina espacios al inicio y al final.
     * 2. Convierte el texto a mayúsculas.
     * 3. Sustituye caracteres acentuados.
     * 4. Reduce espacios repetidos.
     *
     * Ejemplo:
     *
     * "  Cliente gubernamental  "
     *
     * Resultado:
     *
     * "CLIENTE GUBERNAMENTAL"
     *
     * @param string $nombre Nombre que será normalizado.
     * @return string Nombre normalizado.
     */
    private function normalizarNombreTipoCliente(string $nombre)
    {
        /*
         * Eliminamos espacios al inicio y al final
         * y convertimos el texto a mayúsculas respetando UTF-8.
         */
        $nombre = trim(
            mb_strtoupper(
                $nombre,
                'UTF-8'
            )
        );

        /*
         * Caracteres que se buscarán dentro del texto.
         */
        $buscar = [
            'Á',
            'É',
            'Í',
            'Ó',
            'Ú',
            'Ü',
            'Ñ'
        ];

        /*
         * Caracteres por los que serán reemplazados.
         */
        $reemplazar = [
            'A',
            'E',
            'I',
            'O',
            'U',
            'U',
            'N'
        ];

        /*
         * Reemplazamos los caracteres especiales.
         */
        $nombre = str_replace(
            $buscar,
            $reemplazar,
            $nombre
        );

        /*
         * Sustituimos dos o más espacios consecutivos
         * por un solo espacio.
         */
        $nombre = preg_replace(
            '/\s+/',
            ' ',
            $nombre
        );

        /*
         * Devolvemos el nombre normalizado.
         */
        return $nombre;
    }


    /**
     * Devuelve una respuesta JSON estándar y finaliza la ejecución.
     *
     * Esta función permite evitar repetir en todos los métodos:
     *
     * - header()
     * - http_response_code()
     * - json_encode()
     * - die()
     *
     * @param bool   $status     Indica si la operación fue exitosa.
     * @param string $message    Mensaje que se enviará al frontend.
     * @param array  $data       Información adicional de la respuesta.
     * @param int    $statusCode Código HTTP de la respuesta.
     * @return void
     */
    private function responderJson(
        bool $status,
        string $message,
        array $data = [],
        int $statusCode = 200
    ) {
        /*
         * Indicamos que la respuesta será JSON con codificación UTF-8.
         */
        header('Content-Type: application/json; charset=utf-8');

        /*
         * Establecemos el código HTTP correspondiente.
         *
         * Algunos ejemplos:
         *
         * 200 = Solicitud correcta.
         * 400 = Datos enviados incorrectos.
         * 403 = Usuario sin permisos.
         * 500 = Error interno del servidor.
         */
        http_response_code($statusCode);

        /*
         * Construimos la respuesta base.
         */
        $respuesta = [
            'status' => $status,
            'message' => $message
        ];

        /*
         * Solamente agregamos la propiedad data
         * cuando contiene información.
         */
        if (!empty($data)) {
            $respuesta['data'] = $data;
        }

        /*
         * Convertimos el arreglo a JSON.
         *
         * JSON_UNESCAPED_UNICODE evita que las letras con acentos
         * se conviertan en códigos Unicode.
         *
         * JSON_UNESCAPED_SLASHES evita escapar innecesariamente
         * las diagonales de rutas y URLs.
         */
        echo json_encode(
            $respuesta,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        /*
         * Finalizamos la ejecución para evitar que el controlador
         * imprima contenido adicional y dañe la respuesta JSON.
         */
        die();
    }






    /** Obtiene un valor POST limpio. */
    private function post(string $name, string $default = ''): string
    {
        return trim((string) ($_POST[$name] ?? $default));
    }


    /** Devuelve una respuesta JSON estándar y detiene la ejecución. */
    private function json(bool $status, string $message, array $data = [], int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        $response = ['status' => $status, 'message' => $message];
        if ($data !== [])
            $response['data'] = $data;
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    private function syncLogisticaCliente(int $idcliente): void
    {
        if ($idcliente <= 0) {
            return;
        }
        try {
            (new Lgs_clientesSyncService())->sincronizarCliente($idcliente);
        } catch (Throwable $e) {
            error_log('Cli_clientes::syncLogisticaCliente(' . $idcliente . '): ' . $e->getMessage());
        }
    }


    /*
     * FUNCION PARA IMPLEMENTAR EL REGISTRO DE LA INFORMACIÓN GENERAL
     */


    public function setGeneral()
    {
        try {
            $idcliente = (int) $this->post('idcliente', '0');
            $tipoEnviado = $this->post('idtipo_cliente');
            $tipoPersona = strtoupper($this->post('tipo_persona'));
            $razonSocial = mb_strtoupper($this->post('razon_social'), 'UTF-8');
            $nombreComercial = mb_strtoupper($this->post('nombre_comercial'), 'UTF-8');
            $correo = strtolower($this->post('correo'));

            if (!ctype_digit($tipoEnviado) || (int) $tipoEnviado <= 0 || !in_array($tipoPersona, ['FISICA', 'MORAL'], true) || $razonSocial === '' || $nombreComercial === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->json(false, 'Completa correctamente los campos obligatorios de Información general.', [], 400);
            }

            $tipoId = (int) $tipoEnviado;
            $clienteActual = $idcliente > 0 ? $this->model->selectClienteById($idcliente) : null;
            if ($idcliente > 0 && !$clienteActual) {
                $this->json(false, 'No se encontró el cliente.', [], 404);
            }

            // El formulario no es fuente del código, aunque se altere la petición.
            $codigo = $clienteActual['codigo_cliente'] ?? '';
            if ((int) ($clienteActual['idtipo_cliente'] ?? 0) !== $tipoId) {
                $generado = $this->generarCodigoCliente($tipoId);
                if (!$generado['status']) {
                    $this->json(false, $generado['message'], [], 400);
                }
                $codigo = $generado['codigo_cliente'];
            }

            $data = [
                'idtipo_cliente' => $tipoId,
                'tipo_persona' => $tipoPersona,
                'codigo_cliente' => $codigo,
                'razon_social' => $razonSocial,
                'nombre_comercial' => $nombreComercial,
                'telefono' => $this->post('telefono'),
                'celular' => $this->post('celular'),
                'correo' => $correo,
                'sitio_web' => $this->post('sitio_web'),
                'fecha_alta' => $this->post('fecha_alta', date('Y-m-d')),
                'estado' => (int) $this->post('estado', (string) ($clienteActual['estado'] ?? 2)),
                'clave_distribuidor' => $tipoId === 1 && $this->post('clave_distribuidor_automatica') === '1'
                    ? $codigo
                    : $this->post('clave_distribuidor'),
                'zona_comercial' => mb_strtoupper($this->post('zona_comercial'), 'UTF-8'),
                'territorio' => mb_strtoupper($this->post('territorio'), 'UTF-8'),
                'responsable_comercial' => mb_strtoupper($this->post('responsable_comercial'), 'UTF-8'),
                'requiere_acceso_portal' => (int) $this->post('requiere_acceso_portal', '0'),
                'correo_acceso' => strtolower($this->post('correo_acceso')),
                'numero_empleado' => $this->post('numero_empleado'),
                'departamento' => $this->post('departamento'),
                'centro_costos' => $this->post('centro_costos'),
                'jefe_inmediato' => $this->post('jefe_inmediato'),
                'correo_corporativo' => strtolower($this->post('correo_corporativo')),
                'origen_cliente' => $this->post('origen_cliente'),
                'ejecutivo_asignado' => mb_strtoupper($this->post('ejecutivo_asignado'), 'UTF-8'),
                'segmento_mercado' => mb_strtoupper($this->post('segmento_mercado'), 'UTF-8'),
                'dependencia' => $this->post('dependencia'),
                'unidad_administrativa' => $this->post('unidad_administrativa'),
                'nivel_gobierno' => $this->post('nivel_gobierno'),
                'partida_presupuestal' => $this->post('partida_presupuestal'),
                'tipo_contratacion' => $this->post('tipo_contratacion'),
                'usuarioid' => (int) ($_SESSION['idUser'] ?? 0)
            ]; 
 
            $resultado = $idcliente > 0
                ? $this->model->updateGeneral($idcliente, $data)
                : $this->model->insertGeneral($data);

            if (!$resultado)
                $this->json(false, 'No fue posible guardar la información general.', [], 500);
            if ($idcliente <= 0)
                $idcliente = (int) $resultado;

            $this->syncLogisticaCliente($idcliente);

            $this->json(true, 'La información general se guardó correctamente.', [
                'idcliente' => $idcliente,
                'codigo_cliente' => $codigo,
                'clave_distribuidor' => $data['clave_distribuidor']
            ]);
        } catch (Throwable $e) {
            error_log('setGeneral: ' . $e->getMessage());
            $this->json(false, 'Ocurrió un error al guardar la información general.', [], 500);
        }
    }



        /** Guarda o actualiza la información fiscal del cliente. */
    public function setFiscal()
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            $cliente = $this->validarCliente($idcliente);

            $rfc = strtoupper($this->post('rfc'));
            $regimen = $this->post('regimen_fiscal');
            $usoCfdi = $this->post('uso_cfdi');
            $cp = $this->post('codigo_postal_fiscal');
            // $patron = $cliente['tipo_persona'] === 'FISICA'
            //     ? '/^[A-ZÑ&]{4}\d{6}[A-Z0-9]{3}$/'
            //     : '/^[A-ZÑ&]{3}\d{6}[A-Z0-9]{3}$/';

            // if (!preg_match($patron, $rfc) || $regimen === '' || $usoCfdi === '' || !preg_match('/^\d{5}$/', $cp)) {
            //     $this->json(false, 'La información fiscal contiene datos inválidos.', [], 400);
            // }
            // if ($this->model->existeRfc($rfc, $idcliente)) {
            //     $this->json(false, 'El RFC ya se encuentra registrado en otro cliente.', [], 409);
            // }

            $data = [
                'rfc' => $rfc,
                'curp' => strtoupper($this->post('curp')),
                'regimen_fiscal' => $regimen,
                'uso_cfdi' => $usoCfdi,
                'codigo_postal_fiscal' => $cp,
                'correo_facturacion' => strtolower($this->post('correo_facturacion')),
                'requiere_factura' => (int)$this->post('requiere_factura', '1'),
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ];

            // dep($data);

            $ok = $this->model->upsertFiscal($idcliente, $data);
            if (!$ok) $this->json(false, 'No fue posible guardar la información fiscal.', [], 500);
            $this->json(true, 'La información fiscal se guardó correctamente.', ['idcliente' => $idcliente]);
        } catch (Throwable $e) {
            error_log('setFiscal: ' . $e->getMessage());
            $this->json(false, 'Ocurrió un error al guardar la información fiscal.', [], 500);
        }
    }



        /** Valida que exista un cliente y que no esté eliminado. */
    private function validarCliente(int $idcliente): array
    {
        if ($idcliente <= 0) $this->json(false, 'El cliente no es válido.', [], 400);
        $cliente = $this->model->selectClienteBasico($idcliente);
        if (empty($cliente)) $this->json(false, 'No se encontró el cliente.', [], 404);
        return $cliente;
    }



        /** Guarda o actualiza un contacto. */
    public function setContacto(): void
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            $idcontacto = (int)$this->post('idcontacto', '0');
          
            $correo = strtolower($this->post('correo'));
            if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->json(false, 'El nombre y correo del contacto son obligatorios.', [], 400);
            }

            $data = [
                'nombre' => $this->post('nombre'),
                'puesto' => $this->post('puesto'),
                'correo' => $correo,
                'telefono' => $this->post('telefono'),
                'tipo' => $this->post('tipo_contacto', 'ADMINISTRATIVO'),
                'notificar' => (int)$this->post('notificar', '1'),
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ];

            $resultado = $idcontacto > 0
                ? $this->model->updateContacto($idcontacto, $idcliente, $data)
                : $this->model->insertContacto($idcliente, $data);
            if (!$resultado) $this->json(false, 'No fue posible guardar el contacto.', [], 500);
            if ($idcontacto <= 0) $idcontacto = (int)$resultado;
            $this->json(true, 'El contacto se guardó correctamente.', ['idcontacto' => $idcontacto]);
        } catch (Throwable $e) {
            error_log('setContacto: ' . $e->getMessage());
            $this->json(false, 'Ocurrió un error al guardar el contacto.', [], 500);
        }
    }

    public function delContacto(): void
    {
        try {
            $idcliente = (int)$this->post('idcliente', '0');
            $idcontacto = (int)$this->post('idcontacto', '0');
            if (!$this->model->deleteContacto($idcontacto, $idcliente, (int)($_SESSION['idUser'] ?? 0))) {
                $this->json(false, 'No fue posible eliminar el contacto.', [], 500);
            }
            $this->json(true, 'Contacto eliminado correctamente.');
        } catch (Throwable $e) {
            $this->json(false, 'Ocurrió un error al eliminar el contacto.', [], 500);
        }
    }




        /** Guarda o actualiza una sucursal. */
    public function setSucursal(): void
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            $idsucursal = (int)$this->post('idsucursal', '0');
            if ($this->post('nombre_sucursal') === '' || $this->post('calle') === '' || $this->post('numero_exterior') === '') {
                $this->json(false, 'Completa los campos obligatorios de la sucursal.', [], 400);
            }

            $data = [
                'nombre_sucursal' => $this->post('nombre_sucursal'),
                'responsable' => $this->post('responsable'),
                'correo' => strtolower($this->post('correo')),
                'telefono' => $this->post('telefono'),
                'calle' => $this->post('calle'),
                'numero_exterior' => $this->post('numero_exterior'),
                'numero_interior' => $this->post('numero_interior'),
                'colonia' => $this->post('colonia'),
                'codigo_postal' => $this->post('codigo_postal'),
                'municipio' => $this->post('municipio'),
                'estado_republica' => $this->post('estado'),
                'pais' => $this->post('pais', 'México'),
                'estado' => (int)$this->post('estatus', '2'),
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ];

            $resultado = $idsucursal > 0
                ? $this->model->updateSucursal($idsucursal, $idcliente, $data)
                : $this->model->insertSucursal($idcliente, $data);
            if (!$resultado) $this->json(false, 'No fue posible guardar la sucursal.', [], 500);
            if ($idsucursal <= 0) $idsucursal = (int)$resultado;
            $this->syncLogisticaCliente($idcliente);
            $this->json(true, 'La sucursal se guardó correctamente.', ['idsucursal' => $idsucursal]);
        } catch (Throwable $e) {
            error_log('setSucursal: ' . $e->getMessage());
            $this->json(false, 'Ocurrió un error al guardar la sucursal.', [], 500);
        }
    }

    public function delSucursal(): void
    {
        try {
            // $this->validarPermiso('d');
            $idcliente = (int)$this->post('idcliente', '0');
            $idsucursal = (int)$this->post('idsucursal', '0');
            // $this->validarCliente($idcliente);
            if (!$this->model->deleteSucursal($idsucursal, $idcliente, (int)($_SESSION['idUser'] ?? 0))) {
                $this->json(false, 'No fue posible eliminar la sucursal.', [], 500);
            }
            $this->syncLogisticaCliente($idcliente);
            $this->json(true, 'Sucursal eliminada correctamente.');
        } catch (Throwable $e) {
            $this->json(false, 'Ocurrió un error al eliminar la sucursal.', [], 500);
        }
    }


 public function setDireccion()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status' => false,
            'message' => 'Método no permitido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    if (empty($_SESSION['permisosMod']['w'])) {
        echo json_encode([
            'status' => false,
            'message' => 'No tiene permisos para guardar direcciones.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $idcliente = intval(
        $_POST['idcliente'] ?? 0
    );

    $iddireccion = intval(
        $_POST['iddireccion'] ?? 0
    );

    $tipoDireccion = strClean(
        $_POST['tipo_direccion'] ?? ''
    );

    $calle = strClean(
        $_POST['calle'] ?? ''
    );

    $numeroExterior = strClean(
        $_POST['numero_exterior'] ?? ''
    );

    $numeroInterior = strClean(
        $_POST['numero_interior'] ?? ''
    );

    $colonia = strClean(
        $_POST['colonia'] ?? ''
    );

    $codigoPostal = strClean(
        $_POST['codigo_postal'] ?? ''
    );

    $municipio = strClean(
        $_POST['municipio'] ?? ''
    );

    $estadoRepublica = strClean(
        $_POST['estado_republica'] ?? ''
    );

    $pais = strClean(
        $_POST['pais'] ?? 'México'
    );

    $referencias = strClean(
        $_POST['referencias'] ?? ''
    );

    $usuarioid = intval(
        $_SESSION['idUser'] ?? 0
    );

    if ($idcliente <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'El cliente no es válido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    if (
        empty($tipoDireccion) ||
        empty($calle) ||
        empty($numeroExterior) ||
        empty($colonia) ||
        empty($codigoPostal) ||
        empty($municipio) ||
        empty($estadoRepublica) ||
        empty($pais)
    ) {
        echo json_encode([
            'status' => false,
            'message' => 'Complete los campos obligatorios de la dirección.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    if (!preg_match('/^[0-9]{5}$/', $codigoPostal)) {
        echo json_encode([
            'status' => false,
            'message' => 'El código postal debe contener exactamente 5 números.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $datos = [
        'tipo_direccion' => $tipoDireccion,
        'calle' => $calle,
        'numero_exterior' => $numeroExterior,
        'numero_interior' => $numeroInterior,
        'colonia' => $colonia,
        'codigo_postal' => $codigoPostal,
        'municipio' => $municipio,
        'estado_republica' => $estadoRepublica,
        'pais' => $pais,
        'referencias' => $referencias,
        'usuarioid' => $usuarioid
    ];

    /*
     * ACTUALIZAR
     */
    if ($iddireccion > 0) {
        $direccionActual =
            $this->model->selectDireccionPorId(
                $iddireccion,
                $idcliente
            );

        if (empty($direccionActual)) {
            echo json_encode([
                'status' => false,
                'message' => 'La dirección que intenta actualizar no existe.',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);

            die();
        }

        $request =
            $this->model->updateDireccion(
                $iddireccion,
                $idcliente,
                $datos
            );

        if (!$request) {
            echo json_encode([
                'status' => false,
                'message' => 'No fue posible actualizar la dirección.',
                'data' => null
            ], JSON_UNESCAPED_UNICODE);

            die();
        }

        $this->syncLogisticaCliente($idcliente);

        echo json_encode([
            'status' => true,
            'message' => 'La dirección se actualizó correctamente.',
            'data' => [
                'iddireccion' => $iddireccion,
                'accion' => 'actualizar'
            ]
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    /*
     * INSERTAR
     */
    $request =
        $this->model->insertDireccion(
            $idcliente,
            $datos
        );

    $nuevaDireccion = intval($request);

    if ($nuevaDireccion <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'No fue posible registrar la dirección.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $this->syncLogisticaCliente($idcliente);

    echo json_encode([
        'status' => true,
        'message' => 'La dirección se registró correctamente.',
        'data' => [
            'iddireccion' => $nuevaDireccion,
            'accion' => 'insertar'
        ]
    ], JSON_UNESCAPED_UNICODE);

    die();
}

public function delDireccion()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'status' => false,
            'message' => 'Método no permitido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    if (empty($_SESSION['permisosMod']['d'])) {
        echo json_encode([
            'status' => false,
            'message' => 'No tiene permisos para eliminar direcciones.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $idcliente = intval(
        $_POST['idcliente'] ?? 0
    );

    $iddireccion = intval(
        $_POST['iddireccion'] ?? 0
    );

    $usuarioid = intval(
        $_SESSION['idUser'] ?? 0
    );

    if (
        $idcliente <= 0 ||
        $iddireccion <= 0
    ) {
        echo json_encode([
            'status' => false,
            'message' => 'Los datos de la dirección no son válidos.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $direccion =
        $this->model->selectDireccionPorId(
            $iddireccion,
            $idcliente
        );

    if (empty($direccion)) {
        echo json_encode([
            'status' => false,
            'message' => 'La dirección no existe o ya fue eliminada.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $request =
        $this->model->deleteDireccion(
            $iddireccion,
            $idcliente,
            $usuarioid
        );

    if (!$request) {
        echo json_encode([
            'status' => false,
            'message' => 'No fue posible eliminar la dirección.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $this->syncLogisticaCliente($idcliente);

    echo json_encode([
        'status' => true,
        'message' => 'La dirección se eliminó correctamente.',
        'data' => [
            'iddireccion' => $iddireccion
        ]
    ], JSON_UNESCAPED_UNICODE);

    die();
}

    
    /** Guarda las condiciones comerciales; solo existe un registro por cliente. */
    public function setComercial(): void
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            $precioId = $this->post('precioid', '8');
            if (!ctype_digit($precioId) || !$this->model->existeListaPrecioActiva($precioId)) {
                $comercialActual = $this->model->selectComercialCliente($idcliente);
                if ($precioId === '' || $precioId !== (string) ($comercialActual['precioid'] ?? '')) {
                    $this->json(false, 'Selecciona una lista de precios activa.', [], 400);
                }
            }

            $data = [
                'precioid' => $precioId,
                'moneda' => $this->post('moneda', 'MXN'),
                'forma_pago' => $this->post('forma_pago'),
                'limite_credito' => max(0, (float)$this->post('limite_credito', '0')),
                'dias_credito' => max(0, (int)$this->post('dias_credito', '0')),
                'descuento_autorizado' => min(100, max(0, (float)$this->post('descuento_autorizado', '0'))),
                'ejecutivo_asignado' => $this->post('ejecutivo_asignado'),
                'canal_venta' => $this->post('canal_venta'),
                'clasificacion_comercial' => $this->post('clasificacion_comercial'),
                'observaciones_comerciales' => $this->post('observaciones_comerciales'),
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ];
            if (!$this->model->upsertComercial($idcliente, $data)) {
                $this->json(false, 'No fue posible guardar la información comercial.', [], 500);
            }
            $this->json(true, 'La información comercial se guardó correctamente.');
        } catch (Throwable $e) {
            $this->json(false, 'Ocurrió un error al guardar la información comercial.', [], 500);
        }
    }

/**
 * Guarda o actualiza una cuenta bancaria.
 */
/**
 * Guarda o actualiza una cuenta bancaria.
 */
public function setBanco()
{
    try {

        $idcliente = (int) $this->post(
            'idcliente',
            '0'
        );

        $idbanco = (int) $this->post(
            'idbanco',
            '0'
        );

        if ($idcliente <= 0) {

            return $this->json(
                false,
                'El cliente no es válido.',
                [],
                400
            );
        }

        $banco = trim(
            $this->post(
                'banco',
                ''
            )
        );

        $titularCuenta = trim(
            $this->post(
                'titular_cuenta',
                ''
            )
        );

        /*
         * Sin espacios después de numero_cuenta.
         */
        $numeroCuenta = preg_replace(
            '/\D+/',
            '',
            $this->post(
                'numero_cuenta',
                ''
            )
        );

        $clabe = preg_replace(
            '/\D+/',
            '',
            $this->post(
                'clabe',
                ''
            )
        );

        $monedaCuenta = trim(
            $this->post(
                'moneda_cuenta',
                'MXN'
            )
        );

        $referenciaBancaria = trim(
            $this->post(
                'referencia_bancaria',
                ''
            )
        );

        $estado = (int) $this->post(
            'estado',
            '2'
        );

        $usuarioid = (int) (
            $_SESSION['idUser'] ?? 0
        );

        if ($banco === '') {

            return $this->json(
                false,
                'Capture el nombre del banco.',
                [],
                400
            );
        }

        if ($titularCuenta === '') {

            return $this->json(
                false,
                'Capture el titular de la cuenta.',
                [],
                400
            );
        }

        if (
            $numeroCuenta === '' &&
            $clabe === ''
        ) {

            return $this->json(
                false,
                'Capture el número de cuenta o la CLABE bancaria.',
                [],
                400
            );
        }

        if (
            $numeroCuenta !== '' &&
            strlen($numeroCuenta) < 6
        ) {

            return $this->json(
                false,
                'El número de cuenta debe contener al menos 6 dígitos.',
                [],
                400
            );
        }

        if (
            $clabe !== '' &&
            strlen($clabe) !== 18
        ) {

            return $this->json(
                false,
                'La CLABE debe contener exactamente 18 dígitos.',
                [],
                400
            );
        }

        if (
            !in_array(
                $estado,
                [1, 2],
                true
            )
        ) {
            $estado = 2;
        }

        $data = [
            'banco' => $banco,
            'titular_cuenta' => $titularCuenta,
            'numero_cuenta' => $numeroCuenta,
            'clabe' => $clabe,
            'moneda_cuenta' => (
                $monedaCuenta !== ''
                    ? $monedaCuenta
                    : 'MXN'
            ),
            'referencia_bancaria' => $referenciaBancaria,
            'estado' => $estado,
            'usuarioid' => $usuarioid
        ];

        /*
         * ACTUALIZAR
         */
        if ($idbanco > 0) {

            $bancoActual = $this->model->selectBancoPorId(
                $idbanco,
                $idcliente
            );

            if (empty($bancoActual)) {

                return $this->json(
                    false,
                    'La cuenta bancaria que intenta actualizar no existe.',
                    [],
                    404
                );
            }

            $requestBanco = $this->model->updateBanco(
                $idbanco,
                $idcliente,
                $data
            );

            if (!$requestBanco) {

                return $this->json(
                    false,
                    'No fue posible actualizar la cuenta bancaria.',
                    [],
                    500
                );
            }

            return $this->json(
                true,
                'La cuenta bancaria se actualizó correctamente.',
                [
                    'idbanco' => $idbanco,
                    'accion' => 'actualizar'
                ]
            );
        }

        /*
         * INSERTAR
         */
        $requestBanco = $this->model->insertBanco(
            $idcliente,
            $data
        );

        $idbancoNuevo = intval(
            $requestBanco
        );

        if ($idbancoNuevo <= 0) {

            return $this->json(
                false,
                'No fue posible registrar la cuenta bancaria.',
                [],
                500
            );
        }

        return $this->json(
            true,
            'La cuenta bancaria se registró correctamente.',
            [
                'idbanco' => $idbancoNuevo,
                'accion' => 'insertar'
            ]
        );

    } catch (Throwable $e) {

        error_log(
            'Error en setBanco: ' .
            $e->getMessage() .
            ' | Archivo: ' .
            $e->getFile() .
            ' | Línea: ' .
            $e->getLine()
        );

        return $this->json(
            false,
            'Ocurrió un error al guardar la información bancaria.',
            [],
            500
        );
    }
}

/**
 * Elimina lógicamente una cuenta bancaria.
 */
public function delBanco()
{
    try {

        $idcliente = (int) $this->post(
            'idcliente',
            '0'
        );

        $idbanco = (int) $this->post(
            'idbanco',
            '0'
        );

        $usuarioid = (int) (
            $_SESSION['idUser'] ?? 0
        );

        if (
            $idcliente <= 0 ||
            $idbanco <= 0
        ) {
            $this->json(
                false,
                'Los datos de la cuenta bancaria no son válidos.',
                [],
                400
            );
        }

        $bancoActual =
            $this->model
                ->selectBancoPorId(
                    $idbanco,
                    $idcliente
                );

        if (empty($bancoActual)) {
            $this->json(
                false,
                'La cuenta bancaria no existe o ya fue eliminada.',
                [],
                404
            );
        }

        $request =
            $this->model
                ->deleteBanco(
                    $idbanco,
                    $idcliente,
                    $usuarioid
                );

        if (!$request) {
            $this->json(
                false,
                'No fue posible eliminar la cuenta bancaria.',
                [],
                500
            );
        }

        $this->json(
            true,
            'La cuenta bancaria se eliminó correctamente.',
            [
                'idbanco' =>
                    $idbanco
            ]
        );

    } catch (Throwable $e) {

        $this->json(
            false,
            'Ocurrió un error al eliminar la cuenta bancaria.',
            [],
            500
        );
    }
}

    /** Valida, mueve y registra un documento del cliente. */
    public function setDocumento(): void
    {
        try {
            // $this->validarPermiso('w');
          
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            $tipo = $this->post('tipo_documento');
            if ($tipo === '' || empty($_FILES['archivo'])) $this->json(false, 'Selecciona un tipo y un archivo.', [], 400);

            $file = $_FILES['archivo'];
            if ($file['error'] !== UPLOAD_ERR_OK) $this->json(false, 'El archivo no se cargó correctamente.', [], 400);
            if ($file['size'] > 10 * 1024 * 1024) $this->json(false, 'El archivo supera el límite de 10 MB.', [], 400);

            $permitidos = [
                'application/pdf' => 'pdf',
                'application/xml' => 'xml',
                'text/xml' => 'xml',
                'image/jpeg' => 'jpg',
                'image/png' => 'png'
            ];
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            if (!isset($permitidos[$mime])) $this->json(false, 'El tipo de archivo no está permitido.', [], 400);

            $directorio = 'Assets/Uploads/clientes/' . $idcliente . '/';
            $rutaFisica = dirname(__DIR__) . '/' . $directorio;
            if (!is_dir($rutaFisica) && !mkdir($rutaFisica, 0775, true) && !is_dir($rutaFisica)) {
                $this->json(false, 'No fue posible crear el directorio de documentos.', [], 500);
            }

            $nombreSeguro = $tipo . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $permitidos[$mime];
            if (!move_uploaded_file($file['tmp_name'], $rutaFisica . $nombreSeguro)) {
                $this->json(false, 'No fue posible guardar el archivo.', [], 500);
            }

            $iddocumento = $this->model->insertDocumento($idcliente, [
                'tipo_documento' => $tipo,
                'nombre_original' => $file['name'],
                'nombre_archivo' => $nombreSeguro,
                'ruta_archivo' => $directorio . $nombreSeguro,
                'mime_type' => $mime,
                'tamano_bytes' => (int)$file['size'],
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ]);

            if (!$iddocumento) {
                @unlink($rutaFisica . $nombreSeguro);
                $this->json(false, 'No fue posible registrar el documento.', [], 500);
            }
            $this->json(true, 'El documento se guardó correctamente.', ['iddocumento' => (int)$iddocumento]);
        } catch (Throwable $e) {
            error_log('setDocumento: ' . $e->getMessage());
            $this->json(false, 'Ocurrió un error al guardar el documento.', [], 500);
        }
    }



    public function getGeneral($idcliente = 0)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        echo json_encode([
            'status' => false,
            'message' => 'Método no permitido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $idcliente = intval($idcliente);



    if ($idcliente <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    } 

    $arrData = $this->model->selectGeneralCliente($idcliente);

    if (empty($arrData)) {
        echo json_encode([
            'status' => false,
            'message' => 'No se encontró la información general del cliente.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    echo json_encode([
        'status' => true,
        'message' => 'Información general obtenida correctamente.',
        'data' => $arrData
    ], JSON_UNESCAPED_UNICODE);

    die();
}


public function getFiscal($idcliente = 0)
{
    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {

        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $arrData = $this->model->selectFiscalCliente($idcliente);

    /*
     * Fiscal puede no existir todavía.
     * Eso NO debe considerarse un error.
     */
    if (empty($arrData)) {

        $arrData = [];

    }

    echo json_encode([
        'status' => true,
        'message' => 'Información fiscal obtenida correctamente.',
        'data' => $arrData
    ], JSON_UNESCAPED_UNICODE);

    die();
}



public function getContactos($idcliente = 0)
{
    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {

        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $arrData = $this->model->selectContactosCliente($idcliente);

    echo json_encode([
        'status' => true,
        'message' => 'Contactos obtenidos correctamente.',
        'data' => $arrData ?: []
    ], JSON_UNESCAPED_UNICODE);

    die();
}

public function getSucursales($idcliente = 0)
{
    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {

        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $arrData = $this->model->selectSucursalesCliente($idcliente);

    echo json_encode([
        'status' => true,
        'message' => 'Sucursales obtenidas correctamente.',
        'data' => $arrData ?: []
    ], JSON_UNESCAPED_UNICODE);

    die();
}

public function getDirecciones($idcliente = 0)
{
    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {

        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $arrData = $this->model->selectDireccionesCliente($idcliente);

    echo json_encode([
        'status' => true,
        'message' => 'Direcciones obtenidas correctamente.',
        'data' => $arrData ?: []
    ], JSON_UNESCAPED_UNICODE);

    die();
}

public function getComercial($idcliente = 0)
{
    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {

        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => null
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $arrData = $this->model->selectComercialCliente($idcliente);

    if (empty($arrData)) {
        $arrData = [];
    }

    echo json_encode([
        'status' => true,
        'message' => 'Información comercial obtenida correctamente.',
        'data' => $arrData
    ], JSON_UNESCAPED_UNICODE);

    die();
}


public function getBancos(
    $idcliente = 0
) {
    try {

        $idcliente =
            (int) $idcliente;

        if ($idcliente <= 0) {
            $this->json(
                false,
                'El cliente no es válido.',
                [],
                400
            );
        }

        $bancos =
            $this->model
                ->selectBancosCliente(
                    $idcliente
                );

        $this->json(
            true,
            'Cuentas bancarias obtenidas correctamente.',
            $bancos ?: []
        );

    } catch (Throwable $e) {

        $this->json(
            false,
            'Ocurrió un error al consultar las cuentas bancarias.',
            [],
            500
        );
    }
}


public function getDocumentos($idcliente = 0)
{
    $idcliente = intval($idcliente);

    if ($idcliente <= 0) {

        echo json_encode([
            'status' => false,
            'message' => 'El ID del cliente no es válido.',
            'data' => []
        ], JSON_UNESCAPED_UNICODE);

        die();
    }

    $arrData = $this->model->selectDocumentosCliente($idcliente);

    /*
     * Podemos construir aquí una URL pública si la necesita el JS.
     */
    foreach ($arrData as &$documento) {

        $documento['url'] = '';

        if (!empty($documento['ruta_archivo'])) {

            $documento['url'] =
                base_url() . '/' .
                ltrim(
                    $documento['ruta_archivo'],
                    '/'
                );

        }

    }

    unset($documento);

    echo json_encode([
        'status' => true,
        'message' => 'Documentos obtenidos correctamente.',
        'data' => $arrData ?: []
    ], JSON_UNESCAPED_UNICODE);

    die();
}



}
