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
        getPermisos(MCCLIENTES);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA REDIRIGIR A LA VISTA PRINCIPAL INDEX.PHP INLCUYENDO EL ARCHIVO JS 
    |--------------------------------------------------------------------------
    */

    public function Cli_clientes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Clientes";
        $data['page_title'] = "Clientes";
        $data['page_functions_js'] = "/clientes/index.js";
        $this->views->getView($this, "index", $data);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS CLIENTES
    |--------------------------------------------------------------------------
    */

    public function getTodos()
    {
        header('Content-Type: application/json; charset=utf-8');

        try {

            $arrData = $this->model->selectTodos();

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
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Clientes";
        $data['page_title'] = "Clientes";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "/clientes/create.js";
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

    // $data['page_functions_js'] = [
    //     "/clientes/cli_clientes.js"
    // ];

    $data['page_functions_js'] = "/clientes/create.js";

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

        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
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
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->responseJson(false, 'No tiene permisos para consultar.');
        }

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

        $correoAcceso = strtolower(trim(
            $_POST['correo_acceso'] ?? ''
        ));

        $passwordTemporal = trim(
            $_POST['password_temporal'] ?? ''
        );

        $ligaAcceso = trim(
            $_POST['liga_acceso'] ?? ''
        );

        $dobleAutenticacion = intval(
            $_POST['doble_autenticacion'] ?? 0
        );

        if (
            $idcliente <= 0
            || empty($usuarioAcceso)
            || empty($correoAcceso)
            || empty($passwordTemporal)
            || empty($ligaAcceso)
        ) {
            $this->responseJson(
                false,
                'Todos los campos son obligatorios.'
            );
        }

        if (!filter_var($correoAcceso, FILTER_VALIDATE_EMAIL)) {
            $this->responseJson(
                false,
                'El correo no tiene un formato válido.'
            );
        }

        if (!$this->validarPasswordTemporal($passwordTemporal)) {
            $this->responseJson(
                false,
                'La contraseña debe tener 15 caracteres, mayúscula, minúscula, número y símbolo.'
            );
        }

        $cliente = $this->model->selectClienteAcceso($idcliente);

        if (empty($cliente)) {
            $this->responseJson(
                false,
                'No se encontró el cliente.'
            );
        }

        /*
         * No confíes en el correo readonly del navegador.
         * Se vuelve a tomar desde la base de datos.
         */
        $correoAcceso = strtolower(trim($cliente['correo']));

        $passwordHash = password_hash(
            $passwordTemporal,
            PASSWORD_DEFAULT
        );

        $usuarioAdmin = intval(
            $_SESSION['idUser'] ?? 0
        );

        if ($idusuarioAcceso > 0) {
            $guardado = $this->model->updateUsuarioAcceso(
                $idusuarioAcceso,
                $idcliente,
                $usuarioAcceso,
                $correoAcceso,
                $passwordHash,
                $ligaAcceso,
                $dobleAutenticacion,
                $usuarioAdmin
            );
        } else {
            $guardado = $this->model->insertUsuarioAcceso(
                $idcliente,
                $usuarioAcceso,
                $cliente['nombre_comercial']
                ?: $cliente['razon_social'],
                '',
                $correoAcceso,
                $passwordHash,
                $cliente['telefono'] ?? '',
                $ligaAcceso,
                $dobleAutenticacion,
                $usuarioAdmin
            );

            $idusuarioAcceso = intval($guardado);
        }

        if (!$guardado) {
            $this->responseJson(
                false,
                'No fue posible guardar las credenciales.'
            );
        }

        $esNuevoAcceso = $idusuarioAcceso <= 0;

        $nombreCliente = !empty($cliente['nombre_comercial'])
            ? $cliente['nombre_comercial']
            : $cliente['razon_social'];

        $datosCorreo = [
            /*
             * Datos utilizados por tu función de correo.
             * Ajusta estas claves si sendMailLocalCron usa otros nombres.
             */
            'email' => $correoAcceso,
            'nombre_destinatario' => $nombreCliente,
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

        $correoEnviado = sendMailLocalCron(
            $datosCorreo,
            'email_clientes_accesos',
            $cc
        );

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
                'idusuario_acceso' => $idusuarioAcceso
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



    public function getLogsAcceso($idcliente)
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            $this->responseJson(
                false,
                'No tiene permisos para consultar el histórico.'
            );
        }

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
            if (empty($_SESSION['permisosMod']['r'])) {
                $this->responderJson(
                    false,
                    'No tiene permisos para consultar esta información.',
                    [],
                    403
                );
            }

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
            $idtipoCliente,
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


    /*
     * FUNCION PARA IMPLEMENTAR EL REGISTRO DE LA INFORMACIÓN GENERAL
     */


    public function setGeneral()
    {
        try {
            $idcliente = (int) $this->post('idcliente', '0');
            $tipoEnviado = $this->post('idtipo_cliente');
            $tipoPersona = strtoupper($this->post('tipo_persona'));
            $razonSocial = $this->post('razon_social');
            $nombreComercial = $this->post('nombre_comercial');
            $correo = strtolower($this->post('correo'));

            if ($tipoEnviado === '' || !in_array($tipoPersona, ['FISICA', 'MORAL'], true) || $razonSocial === '' || $nombreComercial === '' || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
                $this->json(false, 'Completa correctamente los campos obligatorios de Información general.', [], 400);
            }

            $data = [
                'idtipo_cliente' => $this->post('idtipo_cliente'),
                'tipo_persona' => $tipoPersona,
                'codigo_cliente' => $this->post('codigo_cliente'),
                'razon_social' => $razonSocial,
                'nombre_comercial' => $nombreComercial,
                'telefono' => $this->post('telefono'),
                'celular' => $this->post('celular'),
                'correo' => $correo,
                'sitio_web' => $this->post('sitio_web'),
                'fecha_alta' => $this->post('fecha_alta', date('Y-m-d')),
                'estado' => (int) $this->post('estado', '1'),
                'clave_distribuidor' => $this->post('clave_distribuidor'),
                'zona_comercial' => $this->post('zona_comercial'),
                'territorio' => $this->post('territorio'),
                'responsable_comercial' => $this->post('responsable_comercial'),
                'requiere_acceso_portal' => (int) $this->post('requiere_acceso_portal', '0'),
                'correo_acceso' => strtolower($this->post('correo_acceso')),
                'numero_empleado' => $this->post('numero_empleado'),
                'departamento' => $this->post('departamento'),
                'centro_costos' => $this->post('centro_costos'),
                'jefe_inmediato' => $this->post('jefe_inmediato'),
                'correo_corporativo' => strtolower($this->post('correo_corporativo')),
                'origen_cliente' => $this->post('origen_cliente'),
                'ejecutivo_asignado' => $this->post('ejecutivo_asignado'),
                'segmento_mercado' => $this->post('segmento_mercado'),
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

            $this->json(true, 'La información general se guardó correctamente.', [
                'idcliente' => $idcliente,
                'codigo_cliente' => $this->post('codigo_cliente')
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
            $this->json(true, 'Sucursal eliminada correctamente.');
        } catch (Throwable $e) {
            $this->json(false, 'Ocurrió un error al eliminar la sucursal.', [], 500);
        }
    }


        /** Registra una dirección adicional del cliente. */
    public function setDireccion(): void
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            foreach (['tipo_direccion','calle','numero_exterior','colonia','codigo_postal','municipio','estado_republica','pais'] as $campo) {
                if ($this->post($campo) === '') $this->json(false, 'Completa los campos obligatorios de la dirección.', [], 400);
            }
            $data = [
                'tipo_direccion' => $this->post('tipo_direccion'),
                'calle' => $this->post('calle'),
                'numero_exterior' => $this->post('numero_exterior'),
                'numero_interior' => $this->post('numero_interior'),
                'colonia' => $this->post('colonia'),
                'codigo_postal' => $this->post('codigo_postal'),
                'municipio' => $this->post('municipio'),
                'estado_republica' => $this->post('estado_republica'),
                'pais' => $this->post('pais'),
                'referencias' => $this->post('referencias'),
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ];
            $iddireccion = $this->model->insertDireccion($idcliente, $data);
            if (!$iddireccion) $this->json(false, 'No fue posible guardar la dirección.', [], 500);
            $this->json(true, 'La dirección se guardó correctamente.', ['iddireccion' => (int)$iddireccion]);
        } catch (Throwable $e) {
            $this->json(false, 'Ocurrió un error al guardar la dirección.', [], 500);
        }
    }

    
    /** Guarda las condiciones comerciales; solo existe un registro por cliente. */
    public function setComercial(): void
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            $data = [
                'lista_precio' => $this->post('lista_precio'),
                'moneda' => $this->post('moneda', 'MXN'),
                'forma_pago' => $this->post('forma_pago'),
                'limite_credito' => max(0, (float)$this->post('limite_credito', '0')),
                'dias_credito' => max(0, (int)$this->post('dias_credito', '0')),
                'descuento_autorizado' => min(100, max(0, (float)$this->post('descuento_autorizado', '0'))),
                'ejecutivo_cuenta' => $this->post('ejecutivo_cuenta'),
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

    /** Guarda o actualiza la cuenta bancaria principal del cliente. */
    public function setBanco(): void
    {
        try {
            // $this->validarPermiso('w');
            $idcliente = (int)$this->post('idcliente', '0');
            // $this->validarCliente($idcliente);
            $clabe = preg_replace('/\D+/', '', $this->post('clabe'));
            if ($clabe !== '' && strlen($clabe) !== 18) $this->json(false, 'La CLABE debe contener 18 dígitos.', [], 400);
            $data = [
                'banco' => $this->post('banco'),
                'titular_cuenta' => $this->post('titular_cuenta'),
                'numero_cuenta' => $this->post('numero_cuenta'),
                'clabe' => $clabe,
                'moneda_cuenta' => $this->post('moneda_cuenta', 'MXN'),
                'referencia_bancaria' => $this->post('referencia_bancaria'),
                'usuarioid' => (int)($_SESSION['idUser'] ?? 0)
            ];
            if (!$this->model->upsertBanco($idcliente, $data)) {
                $this->json(false, 'No fue posible guardar la información bancaria.', [], 500);
            }
            $this->json(true, 'La información bancaria se guardó correctamente.');
        } catch (Throwable $e) {
            $this->json(false, 'Ocurrió un error al guardar la información bancaria.', [], 500);
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




}
