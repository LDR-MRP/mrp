<?php

class Orders extends Controllers
{
    public function __construct()
    {
        parent::__construct();
    }


    public function home()
    {

        $this->validarSesionPortal();
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

            $usuario = $this->model->selectUsuarioPorTokenRecuperacion($tokenHash);

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
        if (!empty($_SESSION['portal_requiere_cambio_password'])) {
            header(
                'Location:' . base_url() . '/orders/login'
            );
            die();
        }

            $data['page_styles'] = [
        'Orders/micuenta.css'
            ];

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

            if (!empty($usuario['estado_cliente'])&& intval($usuario['estado_cliente']) !== 2) {
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

            if (!empty($usuario['bloqueado_hasta'])&& strtotime($usuario['bloqueado_hasta']) > time()) {
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

            if (!password_verify($password,$usuario['password_hash'])) {
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

            $this->model->updateIntentosFallidos(intval($usuario['idusuario_acceso']),0,null);

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
            if (intval($usuario['doble_autenticacion'] ?? 0) === 1) {
                $resultadoPin = $this->generarYEnviarPin(
                    $usuario
                );

                if (empty($resultadoPin['status'])|| empty($resultadoPin['challenge'])) {
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

            if (intval( $usuario['requiere_cambio_password'] ?? 0) === 1) {
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

            if (empty($_SESSION['portal_pin_challenge'])|| !hash_equals((string) $_SESSION['portal_pin_challenge'],(string) $challenge)
            ) {
                $this->responseJson(
                    false,
                    'La solicitud de validación no es válida.'
                );
            }

            $pinActivo =
                $this->model->selectPinActivoPorChallenge($idusuarioAcceso,$challenge);

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

            if (empty($pinActivo['fecha_expiracion'])|| strtotime($pinActivo['fecha_expiracion']) < time()) {
                $this->model->invalidarPin(intval($pinActivo['idpin']));

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

            if (intval($pinActivo['intentos'])>= intval($pinActivo['max_intentos'])) {
                $this->model->invalidarPin(intval($pinActivo['idpin']));

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
            preg_match('/Mobile|Android|iPhone/i', $userAgent)
        ) {
            $tipo = 'Móvil';
            $dispositivo = 'Teléfono móvil';
        } elseif (
            preg_match('/iPad|Tablet/i', $userAgent)
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


    public function getUnidades()
    {
        header(
            'Content-Type: application/json; charset=utf-8'
        );

        try {

            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

                echo json_encode([
                    'status' => false,
                    'message' => 'Método no permitido.',
                    'data' => []
                ]);

                exit;
            }

            $unidades =
                $this->model->selectUnidadesWeb();

            echo json_encode([
                'status' => true,
                'message' =>
                    'Unidades obtenidas correctamente.',
                'data' =>
                    is_array($unidades)
                    ? $unidades
                    : []
            ]);

            exit;

        } catch (Throwable $e) {

            error_log(
                'Orders::getUnidades - '
                . $e->getMessage()
            );

            echo json_encode([
                'status' => false,
                'message' =>
                    'No fue posible obtener las unidades.',
                'data' => []
            ]);

            exit;
        }
    }



    public function detalle($unidad = 0)
    {
        $this->validarSesionPortal();
        try {
            $idunidad = intval($unidad);

            if ($idunidad <= 0) {
                header(
                    'Location: '
                    . base_url()
                    . '/orders/home#catalogo'
                );

                die();
            }

            $unidadDetalle =
                $this->model->selectUnidadDetalle(
                    $idunidad
                );

            if (empty($unidadDetalle)) {
                $data['page_tag'] =
                    'Unidad no encontrada - LDR Solutions';

                $data['page_name'] =
                    'detalle_unidad';

                $data['page_functions_js'] = [];

                $data['unidad'] = null;

                $data['imagenes'] = [];

                $data['mensaje_error'] =
                    'La unidad solicitada no existe o ya no se encuentra disponible.';

                $this->views->getView(
                    $this,
                    '../Orders/detalle',
                    $data
                );

                return;
            }

            $imagenes =
                $this->model->selectImagenesUnidad(
                    $idunidad
                );

            /*
             * Si no existen imágenes en la tabla relacionada,
             * utilizamos imagen_caratula como respaldo.
             */
            if (
                empty($imagenes)
                && !empty($unidadDetalle['imagen_caratula'])
            ) {
                $imagenes = [
                    [
                        'idimagen' => 0,
                        'idunidad' => $idunidad,
                        'nombre_original' =>
                            $unidadDetalle['nombre'],
                        'nombre_archivo' => '',
                        'ruta_archivo' =>
                            $unidadDetalle['imagen_caratula'],
                        'orden' => 1,
                        'es_principal' => 1,
                        'estado' => 2
                    ]
                ];
            }

            $data['page_tag'] =
                $unidadDetalle['nombre']
                . ' - Portal de Pedidos';

            $data['page_name'] =
                'detalle_unidad';

            $data['page_functions_js'] = [
                'orders/detalle.js'
            ];

            $data['unidad'] =
                $unidadDetalle;

            $data['imagenes'] =
                is_array($imagenes)
                ? $imagenes
                : [];

            $data['mensaje_error'] = '';

            $this->views->getView(
                $this,
                '../Orders/detalle',
                $data
            );
        } catch (Throwable $e) {
            error_log(
                'Orders::detalle - '
                . $e->getMessage()
            );

            $data['page_tag'] =
                'Detalle de unidad - LDR Solutions';

            $data['page_name'] =
                'detalle_unidad';

            $data['page_functions_js'] = [];

            $data['unidad'] = null;

            $data['imagenes'] = [];

            $data['mensaje_error'] =
                'No fue posible cargar la información de la unidad.';

            $this->views->getView(
                $this,
                '../Orders/detalle',
                $data
            );
        }
    }





    /**
     * Indica si el distribuidor cuenta con una sesión completa.
     */
    public function portalAutenticado()
    {
        return !empty($_SESSION['portal_autenticado'])
            && !empty($_SESSION['portal_idusuario_acceso'])
            && !empty($_SESSION['portal_idcliente']);
    }

    /**
     * Protege endpoints AJAX.
     */
    public function validarSesionPortalJson()
    {
        if ($this->portalAutenticado()) {
            return;
        }

        $this->respuestaJson(
            false,
            'Tu sesión ha finalizado. Inicia sesión nuevamente.',
            [
                'redirect' => base_url() . '/orders/login'
            ],
            401
        );
    }

    /**
     * Envía una respuesta JSON y finaliza la ejecución.
     */
    public function respuestaJson(bool $status, string $message, array $data = [], int $httpCode = 200)
    {
        http_response_code($httpCode);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode(
            [
                'status' => $status,
                'message' => $message,
                'data' => $data
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
        );

        exit;
    }

    /**
     * Obtiene el contenido JSON enviado mediante fetch.
     */
    public function obtenerJsonEntrada()
    {
        $contenido = file_get_contents('php://input');

        if ($contenido === false || trim($contenido) === '') {
            return [];
        }

        $data = json_decode(
            $contenido,
            true
        );

        return is_array($data)
            ? $data
            : [];
    }


    public function validarSesionPedidoJson()
    {
        $autenticado = !empty(
            $_SESSION['portal_autenticado']
        );

        $idcliente = (int) (
            $_SESSION['portal_idcliente']
            ?? 0
        );

        $idusuarioAcceso = (int) (
            $_SESSION['portal_idusuario_acceso']
            ?? 0
        );

        if (
            !$autenticado
            || $idcliente <= 0
            || $idusuarioAcceso <= 0
        ) {
            $this->respuestaJson(
                false,
                'Tu sesión ha finalizado. Inicia sesión nuevamente.',
                [
                    'redirect' =>
                        base_url()
                        . '/orders/login'
                ],
                401
            );
        }
    }

    /**
     * Limpia textos enviados por el cliente.
     */
    public function limpiarTexto(mixed $valor, int $longitudMaxima = 500)
    {
        $texto = trim((string) $valor);

        $texto = preg_replace(
            '/\s+/u',
            ' ',
            $texto
        );

        return mb_substr(
            $texto ?? '',
            0,
            $longitudMaxima
        );
    }



    public function validarFechaPedido(
        mixed $valor
    ) {
        $fecha = trim(
            (string) $valor
        );

        if ($fecha === '') {
            return null;
        }

        $objeto = DateTime::createFromFormat(
            'Y-m-d',
            $fecha
        );

        if (
            !$objeto
            || $objeto->format('Y-m-d') !== $fecha
        ) {
            return null;
        }

        return $fecha;
    }


    /**
     * Convierte YYYY-MM a una fecha utilizando
     * el primer día del mes.
     */
    public function convertirMesFacturacion(
        mixed $valor
    ) {
        $mes = trim(
            (string) $valor
        );

        if ($mes === '') {
            return null;
        }

        if (
            !preg_match(
                '/^\d{4}-\d{2}$/',
                $mes
            )
        ) {
            return null;
        }

        $fecha = DateTime::createFromFormat(
            'Y-m-d',
            $mes . '-01'
        );

        if (!$fecha) {
            return null;
        }

        return $fecha->format(
            'Y-m-d'
        );
    }

    /**
     * Construye el folio definitivo.
     *
     * Ejemplo:
     * PED260728-000123
     */
    public function generarFolioPedido(int $idpedido)
    {
        return 'PED'
            . date('ymd')
            . '-'
            . str_pad(
                (string) $idpedido,
                6,
                '0',
                STR_PAD_LEFT
            );
    }



    /**
     * Regresa únicamente las sucursales del distribuidor
     * que actualmente inició sesión.
     */
    public function getSucursalesEntrega()
    {
        $this->validarSesionPortalJson();

        try {
            $idcliente = (int) (
                $_SESSION['portal_idcliente']
                ?? 0
            );

            if ($idcliente <= 0) {
                $this->respuestaJson(
                    false,
                    'No fue posible identificar al distribuidor.',
                    [],
                    400
                );
            }

            $sucursales = $this->model->selectSucursalesCliente($idcliente);

            $dataSucursales = [];

            foreach ($sucursales as $sucursal) {
                $direccion = implode(
                    ', ',
                    array_filter(
                        [
                            trim(
                                ($sucursal['calle'] ?? '')
                                . ' '
                                . ($sucursal['numero_exterior'] ?? '')
                                . (
                                    !empty($sucursal['numero_interior'])
                                    ? ' Int. '
                                    . $sucursal['numero_interior']
                                    : ''
                                )
                            ),
                            $sucursal['colonia'] ?? '',
                            $sucursal['municipio'] ?? '',
                            $sucursal['estado_republica'] ?? '',
                            !empty($sucursal['codigo_postal'])
                            ? 'C.P. '
                            . $sucursal['codigo_postal']
                            : '',
                            $sucursal['pais'] ?? ''
                        ],
                        static fn($valor) =>
                        trim((string) $valor) !== ''
                    )
                );

                $dataSucursales[] = [
                    'idsucursal' => (int) $sucursal['idsucursal'],
                    'nombre_sucursal' => (string) $sucursal['nombre_sucursal'],
                    'responsable' => (string) ($sucursal['responsable'] ?? ''),
                    'direccion' => $direccion
                ];
            }

            $this->respuestaJson(
                true,
                'Sucursales obtenidas correctamente.',
                [
                    'sucursales' => $dataSucursales
                ]
            );
        } catch (Throwable $e) {
            error_log(
                'Orders::getSucursalesEntrega: '
                . $e->getMessage()
            );

            $this->respuestaJson(
                false,
                'No fue posible cargar las sucursales de entrega.',
                [],
                500
            );
        }
    }





    public function generarSolicitudPedido()
    {
        $this->validarSesionPedidoJson();

        try {
            $idcliente = (int) (
                $_SESSION['portal_idcliente']
                ?? 0
            );

            $idusuarioAcceso = (int) (
                $_SESSION['portal_idusuario_acceso']
                ?? 0
            );

            $entrada =
                $this->obtenerJsonEntrada();

            $productos =
                $entrada['productos']
                ?? [];

            if (
                !is_array($productos)
                || count($productos) === 0
            ) {
                $this->respuestaJson(
                    false,
                    'El carrito no contiene unidades.',
                    [],
                    400
                );
            }

            if (count($productos) > 100) {
                $this->respuestaJson(
                    false,
                    'No puedes enviar más de 100 modelos en una sola solicitud.',
                    [],
                    400
                );
            }

            $fechaRequerida =
                $this->validarFechaPedido(
                    $entrada['fecha_requerida']
                    ?? null
                );

            $mesFacturacion =
                $this->convertirMesFacturacion(
                    $entrada['mes_facturacion_deseado']
                    ?? null
                );

            $prioridad = strtoupper(
                $this->limpiarTexto(
                    $entrada['prioridad']
                    ?? 'NORMAL',
                    20
                )
            );

            if (
                !in_array(
                    $prioridad,
                    [
                        'NORMAL',
                        'ALTA',
                        'URGENTE'
                    ],
                    true
                )
            ) {
                $prioridad = 'NORMAL';
            }

            $observaciones =
                $this->limpiarTexto(
                    $entrada['observaciones']
                    ?? '',
                    1000
                );

            /*
             * El precio y los importes se recalculan
             * completamente en el servidor.
             */
            $tasaIva = 0.16;

            $subtotalPedido = 0.00;
            $descuentoPedido = 0.00;
            $ivaPedido = 0.00;
            $totalPedido = 0.00;

            $detallesProcesados = [];
            $sucursalesSeleccionadas = [];
            $todosConSucursal = true;

            foreach (
                $productos as $productoEntrada
            ) {
                if (!is_array($productoEntrada)) {
                    throw new RuntimeException(
                        'Uno de los productos tiene un formato incorrecto.'
                    );
                }

                $idproducto = (int) (
                    $productoEntrada['idproducto']
                    ?? 0
                );

                $cantidad = (int) (
                    $productoEntrada['cantidad']
                    ?? 0
                );

                if ($idproducto <= 0) {
                    throw new RuntimeException(
                        'Existe una unidad sin identificador válido.'
                    );
                }

                if (
                    $cantidad <= 0
                    || $cantidad > 1000
                ) {
                    throw new RuntimeException(
                        'La cantidad solicitada para la unidad '
                        . $idproducto
                        . ' no es válida.'
                    );
                }

                $unidad =
                    $this->model->selectUnidadPedido($idproducto);

                if (empty($unidad)) {
                    throw new RuntimeException(
                        'La unidad '
                        . $idproducto
                        . ' no existe o no está activa.'
                    );
                }

                $tipoEntrega = strtoupper(
                    $this->limpiarTexto(
                        $productoEntrada['tipo_entrega']
                        ?? '',
                        30
                    )
                );

                $idsucursalEntrega = null;
                $direccionEntrega = null;

                if (
                    $tipoEntrega === 'SUCURSAL'
                ) {
                    $idsucursalEntrega = (int) (
                        $productoEntrada['idsucursal_entrega']
                        ?? 0
                    );

                    if (
                        $idsucursalEntrega <= 0
                    ) {
                        throw new RuntimeException(
                            'Selecciona una sucursal para '
                            . $unidad['nombre']
                            . '.'
                        );
                    }

                    $sucursal =
                        $this->model->selectSucursalCliente(
                            $idsucursalEntrega,
                            $idcliente
                        );

                    if (empty($sucursal)) {
                        throw new RuntimeException(
                            'La sucursal seleccionada para '
                            . $unidad['nombre']
                            . ' no pertenece al distribuidor autenticado.'
                        );
                    }

                    $sucursalesSeleccionadas[] =
                        $idsucursalEntrega;
                } elseif (
                    $tipoEntrega
                    === 'OTRA_DIRECCION'
                ) {
                    $todosConSucursal = false;

                    $direccionEntrega =
                        $this->limpiarTexto(
                            $productoEntrada['direccion_entrega']
                            ?? '',
                            500
                        );

                    if (
                        mb_strlen(
                            $direccionEntrega
                        ) < 10
                    ) {
                        throw new RuntimeException(
                            'Escribe una dirección válida para '
                            . $unidad['nombre']
                            . '.'
                        );
                    }
                } else {
                    throw new RuntimeException(
                        'Selecciona el destino de entrega para '
                        . $unidad['nombre']
                        . '.'
                    );
                }

                /*
                 * Nunca se confía en el precio enviado por JavaScript.
                 */
                $precioUnitario = round(
                    (float) (
                        $unidad['precio_estimado']
                        ?? 0
                    ),
                    2
                );

                if ($precioUnitario < 0) {
                    throw new RuntimeException(
                        'La unidad '
                        . $unidad['nombre']
                        . ' no cuenta con un precio válido.'
                    );
                }

                $descuentoDetalle = 0.00;

                $subtotalDetalle = round(
                    $precioUnitario
                    * $cantidad,
                    2
                );

                $baseIva = round(
                    $subtotalDetalle
                    - $descuentoDetalle,
                    2
                );

                $ivaDetalle = round(
                    $baseIva
                    * $tasaIva,
                    2
                );

                $totalDetalle = round(
                    $baseIva
                    + $ivaDetalle,
                    2
                );

                $subtotalPedido +=
                    $subtotalDetalle;

                $descuentoPedido +=
                    $descuentoDetalle;

                $ivaPedido +=
                    $ivaDetalle;

                $totalPedido +=
                    $totalDetalle;

                $detallesProcesados[] = [
                    'idproducto' =>
                        $idproducto,

                    'nombre_producto' =>
                        (string) $unidad['nombre'],

                    'tipo_entrega' =>
                        $tipoEntrega,

                    'idsucursal_entrega' =>
                        $idsucursalEntrega,

                    'direccion_entrega' =>
                        $direccionEntrega,

                    'cantidad_solicitada' =>
                        $cantidad,

                    'cantidad_pendiente' =>
                        $cantidad,

                    'precio_unitario' =>
                        $precioUnitario,

                    'descuento' =>
                        $descuentoDetalle,

                    'subtotal' =>
                        $subtotalDetalle,

                    'iva' =>
                        $ivaDetalle,

                    'total' =>
                        $totalDetalle,

                    'estatus' =>
                        'PENDIENTE'
                ];
            }

            $subtotalPedido = round(
                $subtotalPedido,
                2
            );

            $descuentoPedido = round(
                $descuentoPedido,
                2
            );

            $ivaPedido = round(
                $ivaPedido,
                2
            );

            $totalPedido = round(
                $totalPedido,
                2
            );

            /*
             * Solo se guarda idsede en la cabecera cuando
             * todos los detalles utilizan la misma sucursal.
             */
            $sucursalesUnicas =
                array_values(
                    array_unique(
                        $sucursalesSeleccionadas
                    )
                );

            $idsedePedido = (
                $todosConSucursal
                && count($sucursalesUnicas) === 1
            )
                ? (int) $sucursalesUnicas[0]
                : null;



            $folioPedido = $this->model->generarFolioPedido();

            /*
             * Insertar cabecera.
             */
            $idpedido = $this->model->insertPedido(
                [
                    'idcliente' =>
                        $idcliente,

                    'idsede' =>
                        $idsedePedido,

                    'idusuario_acceso' =>
                        $idusuarioAcceso,

                    'folio_pedido' =>
                        $folioPedido,

                    'fecha_requerida' =>
                        $fechaRequerida,

                    'mes_facturacion_deseado' =>
                        $mesFacturacion,

                    'prioridad' =>
                        $prioridad,

                    'subtotal' =>
                        $subtotalPedido,

                    'descuento' =>
                        $descuentoPedido,

                    'iva' =>
                        $ivaPedido,

                    'total' =>
                        $totalPedido,

                    'observaciones' =>
                        $observaciones,

                    'estatus' =>
                        'PENDIENTE'
                ]
            );

            if ($idpedido <= 0) {
                throw new RuntimeException(
                    'No fue posible registrar el pedido.'
                );
            }

            /*
             * Evento inicial de creación.
             */
            $idEventoCreacion = $this->model->insertBitacoraEvento(
                [
                    'idpedido' =>
                        $idpedido,

                    'tipo_evento' =>
                        'PEDIDO_CREADO',

                    'descripcion' =>
                        'El distribuidor generó la solicitud '
                        . $folioPedido
                        . ' desde el portal de pedidos.',

                    'estatus_anterior' =>
                        null,

                    'estatus_nuevo' =>
                        'PENDIENTE',

                    'usuario_registro' =>
                        $idusuarioAcceso,

                    'origen' =>
                        'PORTAL_DISTRIBUIDOR'
                ]
            );

            if ($idEventoCreacion <= 0) {
                throw new RuntimeException(
                    'No fue posible registrar el evento de creación del pedido.'
                );
            }

            /*
             * Insertar detalles y registrar un evento por cada uno.
             */
            foreach (
                $detallesProcesados
                as $detalle
            ) {
                $detalle['idpedido'] =
                    $idpedido;

                $idpedidoDetalle = $this->model->insertPedidoDetalle($detalle);

                if ($idpedidoDetalle <= 0) {
                    throw new RuntimeException(
                        'No fue posible registrar el detalle de '
                        . $detalle['nombre_producto']
                        . '.'
                    );
                }

                $descripcionDestino = (
                    $detalle['tipo_entrega']
                    === 'SUCURSAL'
                )
                    ? 'Sucursal ID '
                    . $detalle['idsucursal_entrega']
                    : 'Otra dirección: '
                    . $detalle['direccion_entrega'];

                $idEventoDetalle =
                    $this->model->insertBitacoraEvento(
                        [
                            'idpedido' =>
                                $idpedido,

                            'tipo_evento' =>
                                'DETALLE_AGREGADO',

                            'descripcion' =>
                                'Se agregó '
                                . $detalle['nombre_producto']
                                . ' con cantidad '
                                . $detalle['cantidad_solicitada']
                                . '. Destino: '
                                . $descripcionDestino
                                . '.',

                            'estatus_anterior' =>
                                null,

                            'estatus_nuevo' =>
                                'PENDIENTE',

                            'usuario_registro' =>
                                $idusuarioAcceso,

                            'origen' =>
                                'PORTAL_DISTRIBUIDOR'
                        ]
                    );

                if ($idEventoDetalle <= 0) {
                    throw new RuntimeException(
                        'No fue posible registrar la bitácora del detalle.'
                    );
                }
            }

            /*
             * Evento final del registro completo.
             */
            $idEventoConfirmacion =$this->model->insertBitacoraEvento(
                    [
                        'idpedido' =>
                            $idpedido,

                        'tipo_evento' =>
                            'SOLICITUD_REGISTRADA',

                        'descripcion' =>
                            'La solicitud '
                            . $folioPedido
                            . ' fue registrada correctamente con '
                            . count($detallesProcesados)
                            . ' modelo(s) y un total estimado de $'
                            . number_format(
                                $totalPedido,
                                2,
                                '.',
                                ','
                            )
                            . ' MXN.',

                        'estatus_anterior' =>
                            null,

                        'estatus_nuevo' =>
                            'PENDIENTE',

                        'usuario_registro' =>
                            $idusuarioAcceso,

                        'origen' =>
                            'PORTAL_DISTRIBUIDOR'
                    ]
                );

            if ($idEventoConfirmacion <= 0) {
                throw new RuntimeException(
                    'No fue posible registrar la confirmación del pedido.'
                );
            }


            $this->respuestaJson(
                true,
                'La solicitud de pedido fue generada correctamente.',
                [
                    'idpedido' =>
                        $idpedido,

                    'folio_pedido' =>
                        $folioPedido,

                    'subtotal' =>
                        $subtotalPedido,

                    'descuento' =>
                        $descuentoPedido,

                    'iva' =>
                        $ivaPedido,

                    'total' =>
                        $totalPedido,

                    'estatus' =>
                        'PENDIENTE',

                    'redirect' =>
                        base_url()
                        . '/orders/micuenta'
                ],
                201
            );
        } catch (Throwable $e) {


            error_log(
                'Orders::generarSolicitudPedido: '
                . $e->getMessage()
            );

            $mensaje = (
                $e instanceof RuntimeException
            )
                ? $e->getMessage()
                : 'Ocurrió un error interno al generar el pedido.';

            $codigoHttp = (
                $e instanceof RuntimeException
            )
                ? 400
                : 500;

            $this->respuestaJson(
                false,
                $mensaje,
                [],
                $codigoHttp
            );
        }
    }




}