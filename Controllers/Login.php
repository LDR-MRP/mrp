<?php

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class Login extends Controllers
{
    public function __construct()
    {
        session_start();
        parent::__construct();
    }


    /**
     * ==========================================================
     * LOGIN PRINCIPAL
     * ==========================================================
     *
     * Al entar a /login:
     *
     * 1. Si ya existe sesión MRP -> Dashboard
     * 2. Si existe cookie token de RRHH -> intenta SSO
     * 3. Si SSO es correcto -> Dashboard
     * 4. Si no existe token -> Login tradicional
     * 5. Si token es inválido/expiró -> Login tradicional
     */
    public function login()
    {
        // ======================================================
        // 1. YA EXISTE SESIÓN MRP
        // ======================================================

        if (!empty($_SESSION['login']) &&!empty($_SESSION['idUser'])) {
            header('Location: ' . base_url() . '/dashboard');
            exit;
        }


        // ======================================================
        // 2. INTENTAR SSO AUTOMÁTICO
        // ======================================================

        if (!empty($_COOKIE['token'])) {

            $ssoResult = $this->procesarLoginSso();

            if ($ssoResult === true) {

                header('Location: ' . base_url() . '/dashboard');
                exit;
            }
        }


        // ======================================================
        // 3. LOGIN TRADICIONAL
        // ======================================================

        $data['page_tag'] = "Login - MRP";
        $data['page_title'] = "MRP";
        $data['page_name'] = "login";
        $data['page_functions_js'] = "functions_login.js";


        if (!empty($_SESSION['error_sso'])) {

            $data['error_sso'] = $_SESSION['error_sso'];

            unset($_SESSION['error_sso']);
        }


        $this->views->getView($this,"login",$data);
    }


    /**
     * ==========================================================
     * PROCESAR LOGIN SSO
     * ==========================================================
     *
     * Lee la cookie "token" de RRHH.
     *
     * El númeo de colaborador NO viene por GET.
     * Se obtiene exclusivamente del JWT validado.
     */
    public function procesarLoginSso()
    {
        try {

            // ==================================================
            //  OBTENER JWT DE RRHH
            // ==================================================

            $jwt = trim((string)($_COOKIE['token'] ?? ''));

			


            if ($jwt === '') {
                return false;
            }


            // ==================================================
            //  VALIDAR CONFIGURACIÓN
            // ==================================================

            if (!defined('RH_JWT_SECRET') ||trim((string)RH_JWT_SECRET) === '') {

                error_log(
                    '[SSO MRP] RH_JWT_SECRET no está configurado.'
                );

                return false;
            }


            // ==================================================
            //  VALIDAR JWT
            // ==================================================
            //
            // Aquí se valida:
            // - firma
            // - formato
            // - expiración (exp)
            //
            // ==================================================

            $decoded = JWT::decode($jwt,
                new Key(
                    RH_JWT_SECRET,
                    'HS256'
                )
            );


            // ==================================================
            //  VALIDACIONES DEL EMISOR
            // ==================================================

            if (!isset($decoded->iss) ||$decoded->iss !== 'ldrhsys.ldrhumanresources.com') {

                error_log(
                    '[SSO MRP] Emisor JWT no permitido.'
                );

                return false;
            }


            if (!isset($decoded->aud) || $decoded->aud !== 'ldrhsys.ldrhumanresources.com') {

                error_log(
                    '[SSO MRP] Audience JWT no permitida.'
                );

                return false;
            }


            // ==================================================
            //  EXTRAER INFORMACIÓN DEL COLABORADOR
            // ==================================================

            $numeroColaborador = trim((string)($decoded->numero_colaborador ?? ''));


            $nombres = trim((string)($decoded->nombres ?? ''));


            $apellidoPaterno = trim((string)($decoded->apellido_paterno ?? ''));


            $apellidoMaterno = trim((string)($decoded->apellido_materno ?? ''));


            $correo = strtolower(trim((string)($decoded->correo ?? '')));


            // ==================================================
            //  VALIDAR DATOS OBLIGATORIOS
            // ==================================================

            if ($numeroColaborador === '') {

                error_log(
                    '[SSO MRP] JWT válido pero sin numero_colaborador.'
                );

                $_SESSION['error_sso'] =
                    'Tu sesión corporativa no contiene un número de colaborador válido.';

                return false;
            }


            // Como actualmente tus números son numéricos.
            if (!ctype_digit($numeroColaborador)) {

                error_log(
                    '[SSO MRP] numero_colaborador inválido.'
                );

                return false;
            }


            // ==================================================
            // CONSTRUIR APELLIDOS
            // ==================================================

            $apellidos = trim($apellidoPaterno . ' ' . $apellidoMaterno);


            // ==================================================
            // BUSCAR USUARIO EN MRP
            // ==================================================

            $usuario =$this->model->getUserByNumColaborador($numeroColaborador);


            // ==================================================
            // SI NO EXISTE -> CREAR USUARIO MRP
            // ==================================================

            if (empty($usuario)) {

                $idUsuario =$this->model->crearUsuarioSso($numeroColaborador,$nombres,$apellidos,$correo);


                if (empty($idUsuario)) {

                    error_log(
                        '[SSO MRP] No fue posible crear usuario SSO. '
                        . 'Colaborador: '
                        . $numeroColaborador
                    );


                    $_SESSION['error_sso'] =
                        'No fue posible crear tu usuario en MRP.';

                    return false;
                }


                // ==============================================
                // VOLVER A CONSULTAR EL USUARIO CREADO
                // ==============================================

                $usuario =$this->model->getUserByNumColaborador($numeroColaborador);


                if (empty($usuario)) {

                    error_log(
                        '[SSO MRP] Usuario creado pero no recuperado. '
                        . 'Colaborador: '
                        . $numeroColaborador
                    );

                    return false;
                }
            }


            // ==================================================
            // VALIDAR ESTATUS DEL USUARIO MRP
            // ==================================================

            if ((int)$usuario['status'] !== 1) {

                $_SESSION['error_sso'] =
                    'Tu usuario de MRP se encuentra inactivo. Contacta al administrador.';

                return false;
            }


            // ==================================================
            // CREAR SESIÓN MRP
            // ==================================================

            $this->crearSesionUsuario(
                $usuario,
                'Inicio de Sesión (SSO RRHH)'
            );


            return true;


        } catch (ExpiredException $e) {

            // ==================================================
            // TOKEN EXPIRADO
            // ==================================================

            error_log(
                '[SSO MRP] Token RRHH expirado: '
                . $e->getMessage()
            );


    

            return false;


        } catch (SignatureInvalidException $e) {

            // ==================================================
            // FIRMA INVÁLIDA
            // ==================================================

            error_log(
                '[SSO MRP] Firma JWT inválida: '
                . $e->getMessage()
            );


            return false;


        } catch (\Throwable $e) {

            // ==================================================
            // CUALQUIER OTRO ERROR
            // ==================================================

            error_log(
                '[SSO MRP] Error SSO: '
                . get_class($e)
                . ' - '
                . $e->getMessage()
            );




            return false;
        }
    }


    /**
     * ==========================================================
     * CREAR SESIÓN MRP
     * ==========================================================
     *
     * Esta función es compartida por:
     *
     * - Login tradicional
     * - Login SSO
     *
     */
    public function crearSesionUsuario(
        array $usuario,
        string $evento
    ) {

        // ======================================================
        // EVITAR SESSION FIXATION
        // ======================================================

        session_regenerate_id(true);


        // ======================================================
        // SESIÓN PRINCIPAL
        // ======================================================

        $_SESSION['idUser'] =(int)$usuario['idusuario'];


        $_SESSION['login'] = true;


        $_SESSION['avatar_file'] =$usuario['avatar_file'] ?? null;


        $_SESSION['rolid'] =(int)$usuario['rolid'];


        $_SESSION['plantaid'] =
            isset($usuario['plantaid'])
                ? (int)$usuario['plantaid']
                : null;


        // ======================================================
        // CARGAR DATOS COMPLETOS DEL USUARIO
        // ======================================================

        $datosUsuario =$this->model->sessionLogin($_SESSION['idUser']);


 
        sessionUser($_SESSION['idUser']);


        // ======================================================
        // REGISTRAR ACCESO
        // ======================================================

        $ip =$_SERVER['REMOTE_ADDR']?? '';


        $userAgent =$_SERVER['HTTP_USER_AGENT']?? '';


        $this->model->registrarAcceso(
            $_SESSION['idUser'],
            $evento,
            $ip,
            $userAgent,
            date('Y-m-d H:i:s')
        );


        // ======================================================
        // GENERAR TOKEN PROPIO DEL MRP
        // ======================================================

        if (!empty($datosUsuario)) {

            $this->generarTokenMrp($datosUsuario);
        }
    }


    /**
     * ==========================================================
     * GENERAR TOKEN PROPIO DEL MRP
     * ==========================================================
     
     * token     = JWT recibido desde RRHH
     * mrp_token = JWT generado por MRP
     *
     */
    public function generarTokenMrp(array $usuario)
    {
        if (
            !class_exists('\Firebase\JWT\JWT') ||
            !defined('JWT_SECRET')
        ) {
            return;
        }


        $now = time();


        $tokenPayload = [

            'iat' => $now,

            'exp' => $now + (60 * 60 * 10),

            'data' => [

                'id' =>
                    $usuario['idusuario'],

                'nombre' =>
                    trim(
                        ($usuario['nombres'] ?? '')
                        . ' '
                        . ($usuario['apellidos'] ?? '')
                    ),

                'rolid' =>
                    $usuario['rolid'] ?? null,

                'plantaid' =>
                    $usuario['plantaid'] ?? 1,

                'rol' =>
                    $usuario['nombrerol']
                    ?? $usuario['rol_nombre']
                    ?? '',

                'avatar' =>
                    $usuario['avatar_file']
                    ?? 'avatar_default.svg',

                'is_vendor' =>
                    false
            ]
        ];


        $jwt = JWT::encode(
            $tokenPayload,
            JWT_SECRET,
            'HS256'
        );


        setcookie(
            'mrp_token',
            $jwt,
            [
                'expires' =>
                    time() + 36000,

                'path' =>
                    '/',

                'domain' =>
                    COOKIE_DOMAIN,

                'secure' =>
                    COOKIE_SECURE,

                'httponly' =>
                    false,

                'samesite' =>
                    'Lax'
            ]
        );
    }


    /**
     * ==========================================================
     * LOGIN TRADICIONAL
     * ==========================================================
     *
     * SE CONSERVA.
     *
     * El usuario puede seguir entrando mediante:
     *
     * email + password
     */
public function loginUser()
{
    header('Content-Type: application/json; charset=utf-8');

    try {

        if (!$_POST) {

            echo json_encode([
                'status' => false,
                'msg' => 'No se recibieron datos POST.'
            ], JSON_UNESCAPED_UNICODE);

            die();
        }


        /**
         * ==========================================
         * VALIDAR CAMPOS
         * ==========================================
         */
        if (
            empty($_POST['txtEmail']) ||
            empty($_POST['txtPassword'])
        ) {

            echo json_encode([
                'status' => false,
                'msg' => 'Error de datos. Ingrese usuario y contraseña.'
            ], JSON_UNESCAPED_UNICODE);

            die();
        }


        /**
         * ==========================================
         * DATOS
         * ==========================================
         */
        $strUsuario = strtolower(
            strClean($_POST['txtEmail'])
        );


        $strPassword = hash(
            'SHA256',
            $_POST['txtPassword']
        );


        /**
         * ==========================================
         * BUSCAR USUARIO
         * ==========================================
         */
        $userCheck = $this->model->getUserEmail($strUsuario);


        if (empty($userCheck)) {

            echo json_encode([
                'status' => false,
                'msg' => 'El usuario no se encuentra registrado.'
            ], JSON_UNESCAPED_UNICODE);

            die();
        }


        /**
         * ==========================================
         * PASSWORD
         * ==========================================
         */
        if (
            strtolower($userCheck['password'])
            !==
            strtolower($strPassword)
        ) {

            echo json_encode([
                'status' => false,
                'msg' => 'La contraseña ingresada es incorrecta.'
            ], JSON_UNESCAPED_UNICODE);

            die();
        }


        /**
         * ==========================================
         * STATUS
         * ==========================================
         */
        if ((int)$userCheck['status'] !== 1) {

            echo json_encode([
                'status' => false,
                'msg' => 'El usuario se encuentra inactivo.'
            ], JSON_UNESCAPED_UNICODE);

            die();
        }


        /**
         * ==========================================
         * LOGIN MODEL
         * ==========================================
         */
        $requestUser = $this->model->loginUser($strUsuario,$strPassword);


        if (empty($requestUser)) {

            echo json_encode([
                'status' => false,
                'msg' => 'El modelo no regresó información del usuario.'
            ], JSON_UNESCAPED_UNICODE);

            die();
        }


        /**
         * ==========================================
         * CREAR SESIÓN
         * ==========================================
         */
        $this->crearSesionUsuario($requestUser,'Inicio de Sesión');


        /**
         * ==========================================
         * TODO CORRECTO
         * ==========================================
         */
        echo json_encode([
            'status' => true,
            'msg' => 'ok'
        ], JSON_UNESCAPED_UNICODE);

        die();


    } catch (\Throwable $e) {

        /**
         * ==========================================
         * DEBUG TEMPORAL
         * ==========================================
         *
         */
        error_log(
            '[LOGIN MRP ERROR] '
            . get_class($e)
            . ' | '
            . $e->getMessage()
            . ' | '
            . $e->getFile()
            . ':'
            . $e->getLine()
        );


        echo json_encode([
            'status' => false,

            'msg' => 'ERROR PHP: '
                . $e->getMessage(),

            'debug' => [
                'tipo' => get_class($e),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine()
            ]

        ], JSON_UNESCAPED_UNICODE);

        die();
    }
}


    /**
     * ==========================================================
     * SSO MANUALS
     * ==========================================================
     *
     */
    public function sso_login()
    {
        if (
            !empty($_SESSION['login']) &&
            !empty($_SESSION['idUser'])
        ) {

            header(
                'Location: '
                . base_url()
                . '/dashboard'
            );

            exit;
        }


        $result = $this->procesarLoginSso();


        if ($result === true) {

            header(
                'Location: '
                . base_url()
                . '/dashboard'
            );

            exit;
        }


        $_SESSION['error_sso'] =
            'No se encontró una sesión válida del Portal de RRHH.';


        header(
            'Location: '
            . base_url()
            . '/login'
        );

        exit;
    }


    /**
     * ==========================================================
     * RECUPERAR CONTRASEÑA
     * ==========================================================
     */
    public function resetPass()
    {
        if ($_POST) {

            error_reporting(0);

            $correos_copias =
                "carlos.cruz@ldrsolutions.com.mx";


            if (empty($_POST['txtEmailReset'])) {

                $arrResponse = [
                    'status' => false,
                    'msg' =>
                        'Error de datos'
                ];

            } else {

                $token =token();


                $strEmail =
                    strtolower(
                        strClean(
                            $_POST['txtEmailReset']
                        )
                    );


                $arrData =$this->model->getUserEmail($strEmail);


                if (empty($arrData)) {

                    $arrResponse = [
                        'status' => false,
                        'msg' =>
                            'Usuario no existente.'
                    ];

                } else {

                    $idusuario =$arrData['idusuario'];


                    $nombreUsuario =$arrData['nombres']. ' '. $arrData['apellidos'];


                    $url_recovery =base_url(). '/login/confirmUser/'. $strEmail. '/'. $token;


                    $requestUpdate =$this->model->setTokenUser($idusuario,$token);


                    $dataUsuario = [

                        'nombreUsuario' =>
                            $nombreUsuario,

                        'email' =>
                            $strEmail,

                        'asunto' =>
                            'Recuperar cuenta - '
                            . NOMBRE_REMITENTE,

                        'url_recovery' =>
                            $url_recovery
                    ];


                    if ($requestUpdate) {

                        $sendEmail = sendMailLocal(
                                $dataUsuario,
                                'email_cambioPassword',
                                $correos_copias
                            );


                        if ($sendEmail) {

                            $arrResponse = [
                                'status' => true,
                                'msg' =>
                                    'Se ha enviado un email a tu cuenta de correo para cambiar tu contraseña.'
                            ];

                        } else {

                            $arrResponse = [
                                'status' => false,
                                'msg' =>
                                    'No es posible realizar el proceso, intenta más tarde.'
                            ];
                        }

                    } else {

                        $arrResponse = [
                            'status' => false,
                            'msg' =>
                                'No es posible realizar el proceso, intenta más tarde.'
                        ];
                    }
                }
            }


            echo json_encode(
                $arrResponse,
                JSON_UNESCAPED_UNICODE
            );
        }

        die();
    }


    /**
     * ==========================================================
     * CONFIRMAR USUARIO
     * ==========================================================
     */
    public function confirmUser(string $params)
    {
        if (empty($params)) {

            header(
                'Location: '
                . base_url()
            );

            exit;
        }


        $arrParams =
            explode(
                ',',
                $params
            );


        $strEmail =
            strClean(
                $arrParams[0] ?? ''
            );


        $strToken =
            strClean(
                $arrParams[1] ?? ''
            );


        $arrResponse = $this->model->getUsuario( $strEmail,$strToken);


        if (empty($arrResponse)) {

            header(
                "Location: "
                . base_url()
            );

            exit;
        }


        $data['page_tag'] =
            "Cambiar contraseña";

        $data['page_name'] =
            "cambiar_contrasenia";

        $data['page_title'] =
            "Cambiar Contraseña";

        $data['email'] =
            $strEmail;

        $data['token'] =
            $strToken;

        $data['idusuario'] =
            $arrResponse['idusuario'];

        $data['page_functions_js'] =
            "functions_login.js";


        $this->views->getView(
            $this,
            "cambiar_password",
            $data
        );

        die();
    }


    /**
     * ==========================================================
     * CAMBIAR CONTRASEÑA
     * ==========================================================
     */
    public function setPassword()
    {
        if (
            empty($_POST['idUsuario']) ||
            empty($_POST['txtEmail']) ||
            empty($_POST['txtToken']) ||
            empty($_POST['txtPassword']) ||
            empty($_POST['txtPasswordConfirm'])
        ) {

            $arrResponse = [
                'status' => false,
                'msg' =>
                    'Error de datos'
            ];

        } else {

            $intIdusuario =
                intval(
                    $_POST['idUsuario']
                );


            $strPassword =
                $_POST['txtPassword'];


            $strPasswordConfirm =
                $_POST['txtPasswordConfirm'];


            $strEmail =
                strClean(
                    $_POST['txtEmail']
                );


            $strToken =
                strClean(
                    $_POST['txtToken']
                );


            if (
                $strPassword
                !=
                $strPasswordConfirm
            ) {

                $arrResponse = [
                    'status' => false,
                    'msg' =>
                        'Las contraseñas no son iguales.'
                ];

            } else {

                $arrResponseUser = $this->model->getUsuario($strEmail,$strToken);


                if (empty($arrResponseUser)) {

                    $arrResponse = [
                        'status' => false,
                        'msg' =>
                            'Error de datos.'
                    ];

                } else {

                    $strPassword =
                        hash(
                            "SHA256",
                            $strPassword
                        );


                    $requestPass =
                        $this->model->insertPassword($intIdusuario,$strPassword);


                    if ($requestPass) {

                        $arrResponse = [
                            'status' => true,
                            'msg' =>
                                'Contraseña actualizada con éxito.'
                        ];

                    } else {

                        $arrResponse = [
                            'status' => false,
                            'msg' =>
                                'No es posible realizar el proceso, intente más tarde.'
                        ];
                    }
                }
            }
        }


        echo json_encode(
            $arrResponse,
            JSON_UNESCAPED_UNICODE
        );

        die();
    }
}