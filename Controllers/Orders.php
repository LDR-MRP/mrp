<?php

class Orders extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    }

    public function home()
    {
        $data['page_tag'] = 'Portal de Pedidos - LDR Solutions';
        $data['page_name'] = 'home';

        $this->views->getView(
            $this,
            '../Orders/home',
            $data
        );
    }

    public function restablecer($token = '')
    {
        $token = trim(urldecode($token));

        $data['page_tag'] = 'Restablecer contraseña - LDR Solutions';
        $data['page_name'] = 'restablecer_password';
        $data['page_functions_js'] = [
            'orders/restablecer.js'
        ];

        $data['token'] = '';
        $data['token_valido'] = false;
        $data['mensaje_token'] = '';

        if (empty($token)) {
            $data['mensaje_token'] =
                'La liga de recuperación no es válida.';

            $this->views->getView(
                $this,
                '../Orders/restablecer',
                $data
            );

            return;
        }

        try {
            /*
             * En la base de datos se almacena SHA-256,
             * no el token plano recibido en la URL.
             */
            $tokenHash = hash('sha256', $token);

            $usuario = $this->model
                ->selectUsuarioPorTokenRecuperacion(
                    $tokenHash
                );

            if (empty($usuario)) {
                $data['mensaje_token'] =
                    'La liga de recuperación es inválida, caducó o ya fue utilizada.';

                $this->views->getView(
                    $this,
                    '../Orders/restablecer',
                    $data
                );

                return;
            }

            /*
             * Tus estados:
             * 2 = activo
             * 1 = inactivo
             * 0 = eliminado
             */
            if (intval($usuario['estado']) !== 2) {
                $data['mensaje_token'] =
                    'La cuenta no se encuentra activa. Contacta al administrador.';

                $this->views->getView(
                    $this,
                    '../Orders/restablecer',
                    $data
                );

                return;
            }

            $data['token'] = $token;
            $data['token_valido'] = true;

            $this->views->getView(
                $this,
                '../Orders/restablecer',
                $data
            );

        } catch (Throwable $e) {
            $data['mensaje_token'] =
                'Ocurrió un error al validar la liga de recuperación.';

            /*
             * Durante desarrollo puedes revisar:
             * error_log($e->getMessage());
             */

            $this->views->getView(
                $this,
                '../Orders/restablecer',
                $data
            );
        }
    }

    public function login()
    {
        /*
         * Si ya existe sesión completa, no mostrar login.
         */
        if (!empty($_SESSION['portal_autenticado'])) {
            header(
                'Location:' . base_url() . '/orders/micuenta'
            );
            die();
        }

        $data['page_tag'] = 'Iniciar sesión - LDR Solutions';
        $data['page_name'] = 'login';
        $data['page_functions_js'] = [
            'orders/login.js'
        ];

        $this->views->getView(
            $this,
            '../Orders/login',
            $data
        );
    }

    public function micuenta()
    {
        $this->validarSesionPortal();

        /*
         * Protección adicional:
         * no dejar entrar al portal si todavía debe cambiar contraseña.
         */
        if (
            !empty($_SESSION['portal_requiere_cambio_password'])
        ) {
            header(
                'Location:' . base_url() . '/orders/login'
            );
            die();
        }

        $data['page_tag'] = 'Mi cuenta - LDR Solutions';
        $data['page_name'] = 'micuenta';
        $data['page_functions_js'] = [
            'orders/micuenta.js'
        ];

        $this->views->getView(
            $this,
            '../Orders/micuenta',
            $data
        );
    }

    public function carrito()
    {
        $this->validarSesionPortal();

        $data['page_tag'] = 'Carrito - LDR Solutions';
        $data['page_name'] = 'carrito';
        $data['page_functions_js'] = [
            'orders/carrito.js'
        ];

        $this->views->getView(
            $this,
            '../Orders/carrito',
            $data
        );
    }

  public function autenticar()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        /*
         * Esto solo inicia la sesión si todavía no fue iniciada.
         * El nombre personalizado de sesión debe configurarse
         * globalmente antes del primer session_start().
         */
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->soloPost();

        $correo = strtolower(
            trim($_POST['correo'] ?? '')
        );

        $password = $_POST['password'] ?? '';

        if (empty($correo) || empty($password)) {
            $this->responseJson(
                false,
                'El correo y la contraseña son obligatorios.'
            );
        }

        if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->responseJson(
                false,
                'El correo no tiene un formato válido.'
            );
        }

        $usuario = $this->model->selectUsuarioPorCorreo(
            $correo
        );

        if (empty($usuario)) {
            $this->registrarLogPortal(
                null,
                null,
                'LOGIN_FALLIDO',
                'FALLIDO',
                $correo,
                'Correo o contraseña incorrectos.'
            );

            $this->responseJson(
                false,
                'El correo o la contraseña no son correctos.'
            );
        }

        /*
         * Estados del sistema:
         * 2 = activo
         * 1 = inactivo
         * 0 = eliminado
         */
        if (intval($usuario['estado']) !== 2) {
            $estadoUsuario = intval(
                $usuario['estado']
            );

            $motivo = $estadoUsuario === 0
                ? 'La cuenta se encuentra eliminada.'
                : 'La cuenta se encuentra inactiva.';

            $this->registrarLogPortal(
                intval($usuario['idusuario_acceso']),
                intval($usuario['idcliente']),
                'LOGIN_FALLIDO',
                'BLOQUEADO',
                $correo,
                $motivo
            );

            $mensaje = $estadoUsuario === 0
                ? 'La cuenta ya no se encuentra disponible. Contacta al administrador.'
                : 'La cuenta se encuentra inactiva. Contacta al administrador.';

            $this->responseJson(
                false,
                $mensaje
            );
        }

        if (
            !empty($usuario['estado_cliente'])
            && intval($usuario['estado_cliente']) !== 2
        ) {
            $this->registrarLogPortal(
                intval($usuario['idusuario_acceso']),
                intval($usuario['idcliente']),
                'LOGIN_FALLIDO',
                'BLOQUEADO',
                $correo,
                'El cliente se encuentra inactivo o eliminado.'
            );

            $this->responseJson(
                false,
                'El cliente se encuentra inactivo. Contacta al administrador.'
            );
        }

        if (
            !empty($usuario['bloqueado_hasta'])
            && strtotime($usuario['bloqueado_hasta']) > time()
        ) {
            $this->registrarLogPortal(
                intval($usuario['idusuario_acceso']),
                intval($usuario['idcliente']),
                'CUENTA_BLOQUEADA',
                'BLOQUEADO',
                $correo,
                'Intento de acceso durante el bloqueo temporal.'
            );

            $this->responseJson(
                false,
                'La cuenta está temporalmente bloqueada. Intenta más tarde.'
            );
        }

        if (
            !password_verify(
                $password,
                $usuario['password_hash']
            )
        ) {
            $intentos = intval(
                $usuario['intentos_fallidos'] ?? 0
            ) + 1;

            $bloqueadoHasta = null;

            if ($intentos >= 5) {
                $bloqueadoHasta = date(
                    'Y-m-d H:i:s',
                    strtotime('+15 minutes')
                );
            }

            $this->model->updateIntentosFallidos(
                intval($usuario['idusuario_acceso']),
                $intentos,
                $bloqueadoHasta
            );

            $this->registrarLogPortal(
                intval($usuario['idusuario_acceso']),
                intval($usuario['idcliente']),
                $bloqueadoHasta
                    ? 'CUENTA_BLOQUEADA'
                    : 'LOGIN_FALLIDO',
                $bloqueadoHasta
                    ? 'BLOQUEADO'
                    : 'FALLIDO',
                $correo,
                $bloqueadoHasta
                    ? 'Cuenta bloqueada durante 15 minutos por múltiples intentos fallidos.'
                    : 'Contraseña incorrecta.'
            );

            $this->responseJson(
                false,
                $bloqueadoHasta
                    ? 'La cuenta fue bloqueada temporalmente por múltiples intentos fallidos.'
                    : 'El correo o la contraseña no son correctos.'
            );
        }

        /*
         * Regenerar solo después de validar las credenciales.
         */
        session_regenerate_id(true);

        $_SESSION['portal_login_pendiente'] = [
            'idusuario_acceso' => intval(
                $usuario['idusuario_acceso']
            ),
            'idcliente' => intval(
                $usuario['idcliente']
            ),
            'correo' => $usuario['correo'],
            'requiere_cambio_password' => intval(
                $usuario['requiere_cambio_password'] ?? 0
            ),
            'doble_autenticacion' => intval(
                $usuario['doble_autenticacion'] ?? 0
            )
        ];

        $this->model->updateIntentosFallidos(
            intval($usuario['idusuario_acceso']),
            0,
            null
        );

        $this->registrarLogPortal(
            intval($usuario['idusuario_acceso']),
            intval($usuario['idcliente']),
            'CREDENCIALES_VALIDAS',
            'INFORMATIVO',
            $correo,
            'Correo y contraseña validados correctamente.'
        );

        /*
         * Usuario con doble autenticación.
         */
        if (
            intval($usuario['doble_autenticacion'] ?? 0) === 1
        ) {
            $resultadoPin = $this->generarYEnviarPin(
                $usuario
            );

            if (
                empty($resultadoPin['status'])
                || empty($resultadoPin['challenge'])
            ) {
                $this->responseJson(
                    false,
                    $resultadoPin['message']
                        ?? 'No fue posible generar el PIN.'
                );
            }

            /*
             * Guardar explícitamente el challenge en sesión.
             */
            $_SESSION['portal_pin_challenge'] =
                $resultadoPin['challenge'];

            /*
             * Forzar la escritura de las variables en producción.
             */
            session_write_close();

            $this->responseJson(
                true,
                'Se envió un PIN de seguridad a tu correo.',
                [
                    'requiere_pin' => true,
                    'challenge' => $resultadoPin['challenge'],
                    'expira_en_segundos' => 180
                ]
            );
        }

        /*
         * Usuario sin doble autenticación.
         */
        $this->completarSesionPortal($usuario);

        if (
            intval(
                $usuario['requiere_cambio_password'] ?? 0
            ) === 1
        ) {
            $_SESSION[
                'portal_requiere_cambio_password'
            ] = true;

            session_write_close();

            $this->responseJson(
                true,
                'Debes cambiar tu contraseña temporal.',
                [
                    'requiere_pin' => false,
                    'requiere_cambio_password' => true
                ]
            );
        }

        $this->registrarLoginExitoso($usuario);

        session_write_close();

        $this->responseJson(
            true,
            'Inicio de sesión correcto.',
            [
                'requiere_pin' => false,
                'requiere_cambio_password' => false,
                'redirect' =>
                    base_url() . '/orders/micuenta'
            ]
        );

    } catch (Throwable $e) {
        http_response_code(500);

        echo json_encode([
            'status' => false,
            'message' =>
                'Ocurrió un error al procesar el inicio de sesión.',
            'debug' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'linea' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);

        die();
    }
}

   public function validarPin()
{
    header('Content-Type: application/json; charset=utf-8');

    try {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->soloPost();

        if (
            empty(
                $_SESSION['portal_login_pendiente']
            )
        ) {
            /*
             * Este bloque de debug es temporal.
             * Elimínalo cuando confirmes que la sesión funciona.
             */
            echo json_encode([
                'status' => false,
                'message' =>
                    'La sesión de validación ya no es válida. Inicia sesión nuevamente.',
                'debug' => [
                    'session_name' =>
                        session_name(),

                    'session_id' =>
                        session_id(),

                    'cookie_session' =>
                        $_COOKIE[session_name()] ?? null,

                    'session_keys' =>
                        array_keys($_SESSION),

                    'host' =>
                        $_SERVER['HTTP_HOST'] ?? null,

                    'request_uri' =>
                        $_SERVER['REQUEST_URI'] ?? null,

                    'save_path' =>
                        session_save_path()
                ]
            ], JSON_UNESCAPED_UNICODE);

            die();
        }

        $pin = trim(
            $_POST['pin'] ?? ''
        );

        $challenge = trim(
            $_POST['challenge'] ?? ''
        );

        if (!preg_match('/^\d{6}$/', $pin)) {
            $this->responseJson(
                false,
                'El PIN debe contener seis dígitos.'
            );
        }

        if (empty($challenge)) {
            $this->responseJson(
                false,
                'No se recibió el identificador de validación.'
            );
        }

        $pendiente =
            $_SESSION['portal_login_pendiente'];

        $idusuarioAcceso = intval(
            $pendiente['idusuario_acceso'] ?? 0
        );

        if ($idusuarioAcceso <= 0) {
            $this->responseJson(
                false,
                'La sesión no contiene un usuario válido.'
            );
        }

        if (
            empty($_SESSION['portal_pin_challenge'])
            || !hash_equals(
                (string) $_SESSION['portal_pin_challenge'],
                (string) $challenge
            )
        ) {
            $this->responseJson(
                false,
                'La solicitud de validación no es válida.'
            );
        }

        $pinActivo =
            $this->model->selectPinActivoPorChallenge(
                $idusuarioAcceso,
                $challenge
            );

        if (empty($pinActivo)) {
            $this->registrarLogPortal(
                $idusuarioAcceso,
                intval($pendiente['idcliente']),
                'PIN_CADUCADO',
                'FALLIDO',
                $pendiente['correo'],
                'El PIN no existe, expiró o ya fue utilizado.'
            );

            $this->responseJson(
                false,
                'El PIN caducó o ya fue utilizado. Solicita uno nuevo.'
            );
        }

        if (
            empty($pinActivo['fecha_expiracion'])
            || strtotime(
                $pinActivo['fecha_expiracion']
            ) < time()
        ) {
            $this->model->invalidarPin(
                intval($pinActivo['idpin'])
            );

            $this->registrarLogPortal(
                $idusuarioAcceso,
                intval($pendiente['idcliente']),
                'PIN_CADUCADO',
                'FALLIDO',
                $pendiente['correo'],
                'El PIN superó los tres minutos de vigencia.'
            );

            $this->responseJson(
                false,
                'El PIN caducó. Solicita uno nuevo.'
            );
        }

        if (
            intval($pinActivo['intentos'])
            >= intval($pinActivo['max_intentos'])
        ) {
            $this->model->invalidarPin(
                intval($pinActivo['idpin'])
            );

            $this->registrarLogPortal(
                $idusuarioAcceso,
                intval($pendiente['idcliente']),
                'PIN_BLOQUEADO',
                'BLOQUEADO',
                $pendiente['correo'],
                'Se alcanzó el número máximo de intentos del PIN.'
            );

            $this->responseJson(
                false,
                'Se alcanzó el máximo de intentos. Solicita un PIN nuevo.'
            );
        }

        if (
            !password_verify(
                $pin,
                $pinActivo['codigo_hash']
            )
        ) {
            $intentos = intval(
                $pinActivo['intentos']
            ) + 1;

            $this->model->updateIntentoPin(
                intval($pinActivo['idpin']),
                $intentos
            );

            $this->registrarLogPortal(
                $idusuarioAcceso,
                intval($pendiente['idcliente']),
                'PIN_FALLIDO',
                'FALLIDO',
                $pendiente['correo'],
                'PIN de doble autenticación incorrecto.'
            );

            $this->responseJson(
                false,
                'El PIN no es correcto.'
            );
        }

        $this->model->validarPinCorrecto(
            intval($pinActivo['idpin'])
        );

        $usuario =
            $this->model->selectUsuarioAccesoPorId(
                $idusuarioAcceso
            );

        if (empty($usuario)) {
            $this->responseJson(
                false,
                'No se encontró el usuario.'
            );
        }

        if (intval($usuario['estado']) !== 2) {
            $this->responseJson(
                false,
                'La cuenta ya no se encuentra activa.'
            );
        }

        $this->registrarLogPortal(
            $idusuarioAcceso,
            intval($usuario['idcliente']),
            'PIN_VALIDADO',
            'EXITOSO',
            $usuario['correo'],
            'PIN validado correctamente.'
        );

        /*
         * Ya no necesitamos el challenge anterior.
         */
        unset($_SESSION['portal_pin_challenge']);

        /*
         * Esta función debe crear la sesión completa
         * y eliminar portal_login_pendiente.
         */
        $this->completarSesionPortal($usuario);

        if (
            intval(
                $usuario['requiere_cambio_password'] ?? 0
            ) === 1
        ) {
            $_SESSION[
                'portal_requiere_cambio_password'
            ] = true;

            session_write_close();

            $this->responseJson(
                true,
                'PIN validado. Debes cambiar tu contraseña temporal.',
                [
                    'requiere_pin' => false,
                    'requiere_cambio_password' => true
                ]
            );
        }

        $this->registrarLoginExitoso($usuario);

        session_write_close();

        $this->responseJson(
            true,
            'PIN validado correctamente.',
            [
                'requiere_pin' => false,
                'requiere_cambio_password' => false,
                'redirect' =>
                    base_url() . '/orders/micuenta'
            ]
        );

    } catch (Throwable $e) {
        http_response_code(500);

        echo json_encode([
            'status' => false,
            'message' =>
                'Ocurrió un error al validar el PIN.',
            'debug' => $e->getMessage(),
            'archivo' => $e->getFile(),
            'linea' => $e->getLine()
        ], JSON_UNESCAPED_UNICODE);

        die();
    }
}

    public function reenviarPin()
    {
        $this->soloPost();

        if (
            empty(
            $_SESSION['portal_login_pendiente']
        )
        ) {
            $this->responseJson(
                false,
                'La sesión de validación ya no es válida.'
            );
        }

        $pendiente =
            $_SESSION['portal_login_pendiente'];

        $usuario =
            $this->model->selectUsuarioAccesoPorId(
                intval(
                    $pendiente['idusuario_acceso']
                )
            );

        if (empty($usuario)) {
            $this->responseJson(
                false,
                'No se encontró la cuenta.'
            );
        }

        $resultado =
            $this->generarYEnviarPin($usuario);

        if (!$resultado['status']) {
            $this->responseJson(
                false,
                $resultado['message']
            );
        }

        $this->responseJson(
            true,
            'Se envió un nuevo PIN. Tiene una vigencia de tres minutos.',
            [
                'challenge' =>
                    $resultado['challenge'],
                'expira_en_segundos' => 180
            ]
        );
    }

    public function cambiarPasswordInicial()
    {

        try {
            $this->soloPost();

            if (
                empty(
                $_SESSION['portal_idusuario_acceso']
            )
                || empty(
                $_SESSION[
                    'portal_requiere_cambio_password'
                ]
            )
            ) {
                $this->responseJson(
                    false,
                    'No existe un cambio de contraseña pendiente.'
                );
            }

            $passwordNueva =
                $_POST['password_nueva'] ?? '';

            $confirmacion =
                $_POST['password_confirmacion'] ?? '';

            if ($passwordNueva !== $confirmacion) {
                $this->responseJson(
                    false,
                    'Las contraseñas no coinciden.'
                );
            }

            if (
                !$this->validarPasswordSegura(
                    $passwordNueva
                )
            ) {
                $this->responseJson(
                    false,
                    'La contraseña no cumple con los requisitos de seguridad.'
                );
            }

            $idusuarioAcceso =
                intval(
                    $_SESSION[
                        'portal_idusuario_acceso'
                    ]
                );

            $usuario =
                $this->model->selectUsuarioAccesoPorId(
                    $idusuarioAcceso
                );

            if (empty($usuario)) {
                $this->responseJson(
                    false,
                    'No se encontró el usuario.'
                );
            }

            /*
             * Evitar reutilizar la contraseña temporal actual.
             */
            if (
                password_verify(
                    $passwordNueva,
                    $usuario['password_hash']
                )
            ) {
                $this->responseJson(
                    false,
                    'La nueva contraseña debe ser diferente de la contraseña temporal.'
                );
            }

            $passwordHash = password_hash(
                $passwordNueva,
                PASSWORD_DEFAULT
            );

            $actualizado =
                $this->model->updatePasswordDefinitiva(
                    $idusuarioAcceso,
                    $passwordHash
                );

            if (!$actualizado) {
                $this->responseJson(
                    false,
                    'No fue posible actualizar la contraseña.'
                );
            }

            unset(
                $_SESSION[
                    'portal_requiere_cambio_password'
                ]
            );

            $this->registrarLogPortal(
                $idusuarioAcceso,
                intval($usuario['idcliente']),
                'PASSWORD_CAMBIADA',
                'EXITOSO',
                $usuario['correo'],
                'El distribuidor cambió su contraseña temporal.'
            );

            $this->registrarLoginExitoso($usuario);

            $this->responseJson(
                true,
                'La contraseña fue actualizada correctamente.',
                [
                    'redirect' =>
                        base_url() . '/orders/micuenta'
                ]
            );


        } catch (Throwable $e) {
            http_response_code(500);

            echo json_encode([
                'status' => false,
                'message' => 'Ocurrió un error interno al cambiar la contraseña.',
                'debug' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ], JSON_UNESCAPED_UNICODE);

            die();
        }
    }

    public function solicitarRecuperacion()
    {
        $this->soloPost();

        $correo = strtolower(trim(
            $_POST['correo'] ?? ''
        ));

        if (
            !filter_var(
                $correo,
                FILTER_VALIDATE_EMAIL
            )
        ) {
            $this->responseJson(
                false,
                'El correo no tiene un formato válido.'
            );
        }

        $usuario =
            $this->model->selectUsuarioPorCorreo(
                $correo
            );

        /*
         * Siempre respondemos de forma genérica.
         */
        $mensajeGenerico =
            'Si el correo está registrado, recibirás las instrucciones para restablecer tu contraseña.';

        if (empty($usuario)) {
            $this->registrarLogPortal(
                null,
                null,
                'RECUPERACION_SOLICITADA',
                'INFORMATIVO',
                $correo,
                'Solicitud de recuperación para un correo no registrado.'
            );

            $this->responseJson(
                true,
                $mensajeGenerico
            );
        }

        $tokenPlano = bin2hex(
            random_bytes(32)
        );

        $tokenHash = hash(
            'sha256',
            $tokenPlano
        );

        $fechaExpiracion = date(
            'Y-m-d H:i:s',
            strtotime('+30 minutes')
        );

        $guardado =
            $this->model->updateTokenRecuperacion(
                intval(
                    $usuario['idusuario_acceso']
                ),
                $tokenHash,
                $fechaExpiracion
            );

        if (!$guardado) {
            $this->responseJson(
                false,
                'No fue posible procesar la solicitud.'
            );
        }

        $ligaRecuperacion =
            base_url()
            . '/orders/restablecer/'
            . urlencode($tokenPlano);

        $datosCorreo = [
            'email' => $usuario['correo'],
            'nombre_destinatario' =>
                trim(
                    $usuario['nombre']
                    . ' '
                    . $usuario['apellido']
                ),

            'asunto' =>
                'Recuperación de acceso al Portal de Pedidos',

            'nombre' =>
                trim(
                    $usuario['nombre']
                    . ' '
                    . $usuario['apellido']
                ),

            'liga_recuperacion' =>
                $ligaRecuperacion,

            'vigencia_minutos' => 30,
            'fecha_notificacion' =>
                date('d/m/Y H:i')
        ];
        $cc = 'carlos.cruz@ldrsolutions.com.mx';
        $correoEnviado = sendMailLocalCron(
            $datosCorreo,
            'email_recuperar_acceso_pedidos',
            $cc
        );

        $this->registrarLogPortal(
            intval($usuario['idusuario_acceso']),
            intval($usuario['idcliente']),
            'RECUPERACION_SOLICITADA',
            $correoEnviado
            ? 'EXITOSO'
            : 'FALLIDO',
            $correo,
            $correoEnviado
            ? 'Se envió el enlace de recuperación.'
            : 'No fue posible enviar el correo de recuperación.'
        );

        $this->responseJson(
            true,
            $mensajeGenerico
        );
    }

    // public function restablecer($token = '')
    // {
    //     $token = trim($token);

    //     if (empty($token)) {
    //         header(
    //             'Location:' . base_url() . '/orders/login'
    //         );
    //         die();
    //     }

    //     $tokenHash = hash(
    //         'sha256',
    //         $token
    //     );

    //     $usuario =
    //         $this->model->selectUsuarioPorTokenRecuperacion(
    //             $tokenHash
    //         );

    //     if (empty($usuario)) {
    //         $data['token_valido'] = false;
    //     } else {
    //         $data['token_valido'] = true;
    //         $data['token'] = $token;
    //     }

    //     $data['page_tag'] =
    //         'Restablecer contraseña - LDR Solutions';

    //     $data['page_name'] =
    //         'restablecer_password';

    //     $data['page_functions_js'] = [
    //         'orders/restablecer.js'
    //     ];

    //     $this->views->getView(
    //         $this,
    //         '../Orders/restablecer',
    //         $data
    //     );
    // }

    public function guardarPasswordRecuperacion()
    {
        $this->soloPost();

        $token = trim(
            $_POST['token'] ?? ''
        );

        $passwordNueva =
            $_POST['password_nueva'] ?? '';

        $confirmacion =
            $_POST['password_confirmacion'] ?? '';

        if (
            empty($token)
            || $passwordNueva !== $confirmacion
        ) {
            $this->responseJson(
                false,
                'La información proporcionada no es válida.'
            );
        }

        if (
            !$this->validarPasswordSegura(
                $passwordNueva
            )
        ) {
            $this->responseJson(
                false,
                'La contraseña no cumple con los requisitos de seguridad.'
            );
        }

        $tokenHash = hash(
            'sha256',
            $token
        );

        $usuario =
            $this->model->selectUsuarioPorTokenRecuperacion(
                $tokenHash
            );

        if (empty($usuario)) {
            $this->responseJson(
                false,
                'El enlace de recuperación caducó o ya fue utilizado.'
            );
        }

        $passwordHash = password_hash(
            $passwordNueva,
            PASSWORD_DEFAULT
        );

        $actualizado =
            $this->model->updatePasswordRecuperacion(
                intval(
                    $usuario['idusuario_acceso']
                ),
                $passwordHash
            );

        if (!$actualizado) {
            $this->responseJson(
                false,
                'No fue posible restablecer la contraseña.'
            );
        }

        $this->registrarLogPortal(
            intval($usuario['idusuario_acceso']),
            intval($usuario['idcliente']),
            'PASSWORD_RECUPERADA',
            'EXITOSO',
            $usuario['correo'],
            'La contraseña se restableció mediante recuperación.'
        );

        $this->responseJson(
            true,
            'La contraseña fue restablecida correctamente.',
            [
                'redirect' =>
                    base_url() . '/orders/login'
            ]
        );
    }

    public function logout()
    {
        if (
            !empty(
            $_SESSION[
                'portal_idusuario_acceso'
            ]
        )
        ) {
            $usuario =
                $this->model->selectUsuarioAccesoPorId(
                    intval(
                        $_SESSION[
                            'portal_idusuario_acceso'
                        ]
                    )
                );

            if (!empty($usuario)) {
                $this->registrarLogPortal(
                    intval(
                        $usuario[
                            'idusuario_acceso'
                        ]
                    ),
                    intval(
                        $usuario['idcliente']
                    ),
                    'LOGOUT',
                    'EXITOSO',
                    $usuario['correo'],
                    'El distribuidor cerró sesión.'
                );
            }
        }

        unset(
            $_SESSION['portal_autenticado'],
            $_SESSION['portal_idusuario_acceso'],
            $_SESSION['portal_idcliente'],
            $_SESSION['portal_correo'],
            $_SESSION['portal_login_pendiente'],
            $_SESSION['portal_pin_challenge'],
            $_SESSION[
                'portal_requiere_cambio_password'
            ]
        );

        session_regenerate_id(true);

        header(
            'Location:' . base_url() . '/orders/login'
        );

        die();
    }

    public function generarYEnviarPin(
        array $usuario
    ) {
        $idusuarioAcceso =
            intval(
                $usuario['idusuario_acceso']
            );

        /*
         * Invalidar PIN anterior.
         */
        $this->model->invalidarPinesAnteriores(
            $idusuarioAcceso
        );

        $pin = str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );

        $codigoHash = password_hash(
            $pin,
            PASSWORD_DEFAULT
        );

        $challenge = bin2hex(
            random_bytes(24)
        );

        $fechaExpiracion = date(
            'Y-m-d H:i:s',
            strtotime('+3 minutes')
        );

        $idpin =
            $this->model->insertPinDobleAutenticacion(
                $idusuarioAcceso,
                $codigoHash,
                $challenge,
                $fechaExpiracion,
                $this->getClientIp(),
                session_id()
            );

        if (!$idpin) {
            return [
                'status' => false,
                'message' =>
                    'No fue posible generar el PIN.'
            ];
        }

        $datosCorreo = [
            'email' => $usuario['correo'],

            'nombre_destinatario' => trim(
                ($usuario['nombre'] ?? '')
                . ' '
                . ($usuario['apellido'] ?? '')
            ),

            'asunto' =>
                'Código de seguridad para ingresar al Portal de Pedidos',

            'nombre' => trim(
                ($usuario['nombre'] ?? '')
                . ' '
                . ($usuario['apellido'] ?? '')
            ),

            'pin' => $pin,

            'vigencia_minutos' => 3,

            'fecha_notificacion' =>
                date('d/m/Y H:i'),

            'liga_acceso' =>
                base_url() . '/orders/login',

            'logo_url' =>
                'https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png',

            'anio' => date('Y')
        ];

        $cc = 'carlos.cruz@ldrsolutions.com.mx';
        $correoEnviado = sendMailLocalCron(
            $datosCorreo,
            'email_pin_portal_pedidos',
            $cc
        );

        $this->registrarLogPortal(
            $idusuarioAcceso,
            intval($usuario['idcliente']),
            'PIN_ENVIADO',
            $correoEnviado
            ? 'EXITOSO'
            : 'FALLIDO',
            $usuario['correo'],
            $correoEnviado
            ? 'PIN enviado correctamente. Vigencia: 3 minutos.'
            : 'No fue posible enviar el PIN.'
        );

        if (!$correoEnviado) {
            $this->model->invalidarPin(
                intval($idpin)
            );

            return [
                'status' => false,
                'message' =>
                    'No fue posible enviar el PIN al correo.'
            ];
        }

        $_SESSION['portal_pin_challenge'] =
            $challenge;

        return [
            'status' => true,
            'challenge' => $challenge
        ];
    }

    public function completarSesionPortal(
        array $usuario
    ) {
        session_regenerate_id(true);

        $_SESSION['portal_autenticado'] = true;

        $_SESSION['portal_idusuario_acceso'] =
            intval(
                $usuario['idusuario_acceso']
            );

        $_SESSION['portal_idcliente'] =
            intval(
                $usuario['idcliente']
            );

        $_SESSION['portal_correo'] =
            $usuario['correo'];

        $_SESSION[
            'portal_requiere_cambio_password'
        ] =
            intval(
                $usuario[
                    'requiere_cambio_password'
                ]
            ) === 1;

        unset(
            $_SESSION['portal_login_pendiente'],
            $_SESSION['portal_pin_challenge']
        );
    }

    public function registrarLoginExitoso(
        array $usuario
    ) {
        $this->model->updateUltimoLogin(
            intval(
                $usuario['idusuario_acceso']
            )
        );

        $this->registrarLogPortal(
            intval(
                $usuario['idusuario_acceso']
            ),
            intval(
                $usuario['idcliente']
            ),
            'LOGIN_EXITOSO',
            'EXITOSO',
            $usuario['correo'],
            'Inicio de sesión completado correctamente.'
        );
    }

    public function registrarLogPortal(
        ?int $idusuarioAcceso,
        ?int $idcliente,
        string $tipoEvento,
        string $resultado,
        ?string $correo,
        ?string $motivo
    ) {
        $userAgent =
            $_SERVER['HTTP_USER_AGENT']
            ?? '';

        $infoDispositivo =
            $this->detectarDispositivo(
                $userAgent
            );

        $this->model->insertLogAcceso(
            $idusuarioAcceso,
            $idcliente,
            $tipoEvento,
            $resultado,
            $correo,
            $this->getClientIp(),
            $infoDispositivo['dispositivo'],
            $infoDispositivo['tipo'],
            $infoDispositivo['navegador'],
            $infoDispositivo['version'],
            $infoDispositivo['sistema'],
            null,
            session_id(),
            $userAgent,
            $motivo
        );
    }

    public function detectarDispositivo(
        string $userAgent
    ) {
        $tipo = 'Escritorio';
        $dispositivo = 'Computadora';

        if (
            preg_match(
                '/Mobile|Android|iPhone/i',
                $userAgent
            )
        ) {
            $tipo = 'Móvil';
            $dispositivo = 'Teléfono móvil';
        } elseif (
            preg_match(
                '/iPad|Tablet/i',
                $userAgent
            )
        ) {
            $tipo = 'Tablet';
            $dispositivo = 'Tablet';
        }

        $navegador = 'No identificado';
        $version = '';

        if (
            preg_match(
                '/Edg\/([0-9.]+)/',
                $userAgent,
                $match
            )
        ) {
            $navegador = 'Microsoft Edge';
            $version = $match[1];
        } elseif (
            preg_match(
                '/Chrome\/([0-9.]+)/',
                $userAgent,
                $match
            )
        ) {
            $navegador = 'Google Chrome';
            $version = $match[1];
        } elseif (
            preg_match(
                '/Firefox\/([0-9.]+)/',
                $userAgent,
                $match
            )
        ) {
            $navegador = 'Mozilla Firefox';
            $version = $match[1];
        } elseif (
            preg_match(
                '/Version\/([0-9.]+).*Safari/',
                $userAgent,
                $match
            )
        ) {
            $navegador = 'Safari';
            $version = $match[1];
        }

        $sistema = 'No identificado';

        if (stripos($userAgent, 'Windows') !== false) {
            $sistema = 'Windows';
        } elseif (
            stripos($userAgent, 'Android') !== false
        ) {
            $sistema = 'Android';
        } elseif (
            stripos($userAgent, 'iPhone') !== false
            || stripos($userAgent, 'iPad') !== false
        ) {
            $sistema = 'iOS';
        } elseif (
            stripos($userAgent, 'Mac OS') !== false
        ) {
            $sistema = 'macOS';
        } elseif (
            stripos($userAgent, 'Linux') !== false
        ) {
            $sistema = 'Linux';
        }

        return [
            'dispositivo' => $dispositivo,
            'tipo' => $tipo,
            'navegador' => $navegador,
            'version' => $version,
            'sistema' => $sistema
        ];
    }

    public function validarPasswordSegura(
        string $password
    ) {
        return strlen($password) >= 10
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password)
            && preg_match(
                '/[^A-Za-z0-9]/',
                $password
            );
    }

    public function validarSesionPortal()
    {
        if (
            empty(
            $_SESSION['portal_autenticado']
        )
            || empty(
            $_SESSION[
                'portal_idusuario_acceso'
            ]
        )
        ) {
            header(
                'Location:' . base_url() . '/orders/login'
            );

            die();
        }
    }

    public function getClientIp(): string
    {
        $ip =
            $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';

        /*
         * X-Forwarded-For puede contener varias IP.
         */
        if (str_contains($ip, ',')) {
            $ip = trim(
                explode(',', $ip)[0]
            );
        }

        return substr($ip, 0, 45);
    }

    public function soloPost()
    {
        if (
            $_SERVER['REQUEST_METHOD']
            !== 'POST'
        ) {
            $this->responseJson(
                false,
                'Método no permitido.'
            );
        }
    }

    public function responseJson(
        bool $status,
        string $message,
        $data = null
    ) {
        header(
            'Content-Type: application/json; charset=utf-8'
        );

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
}