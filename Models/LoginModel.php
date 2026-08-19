<?php

class LoginModel extends Mysql
{
    private $intIdUsuario;
    private $strUsuario;
    private $strPassword;
    private $strToken;

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * ==========================================================
     * LOGIN TRADICIONAL
     * ==========================================================
     *
     * Se conserva para:
     *
     * email + password
     */
    public function loginUser(string $usuario, string $password)
    {
        $this->strUsuario = $usuario;
        $this->strPassword = $password;

        $sql = "SELECT
                    idusuario,
                    numcolaborador,
                    nombres,
                    apellidos,
                    email_user,
                    status,
                    avatar_file,
                    rolid,
                    plantaid
                FROM usuarios
                WHERE email_user = '{$this->strUsuario}'
                  AND password = '{$this->strPassword}'
                  AND status != 0
                LIMIT 1";


        return $this->select(
            $sql
        );
    }


    /**
     * ==========================================================
     * CARGAR DATOS DE SESIÓN
     * ==========================================================
     *
     * Esta función la utilizan:
     *
     * - login normal
     * - login SSO
     */
    public function sessionLogin(int $iduser)
    {
        $this->intIdUsuario = $iduser;

        $sql = "SELECT
                    p.idusuario,
                    p.numcolaborador,
                    p.nombres,
                    p.apellidos,
                    p.telefono,
                    p.email_user,
                    p.nit,
                    p.nombrefiscal,
                    p.direccionfiscal,
                    p.status,
                    p.avatar,
                    p.avatar_file,
                    p.plantaid,
                    p.rolid,
                    r.idrol,
                    r.nombrerol
                FROM usuarios p
                INNER JOIN rol r
                    ON p.rolid = r.idrol
                WHERE p.idusuario = $this->intIdUsuario
                LIMIT 1";

        $request = $this->select(
            $sql
        );


        $_SESSION['userData'] = $request;


        return $request;
    }


    /**
     * ==========================================================
     * REGISTRAR ACCESO
     * ==========================================================
     *
     * Tabla:
     *
     * login_logs
     */
    public function registrarAcceso(
        $idusuario,
        $evento,
        $ip,
        $detalle,
        $fecha
    ) {

        $sql = "INSERT INTO login_logs
                (
                    idusuario,
                    evento,
                    ip,
                    detalle,
                    fecha
                )
                VALUES (?, ?, ?, ?, ?)";

        $arrData = [
            $idusuario,
            $evento,
            $ip,
            $detalle,
            $fecha
        ];


        return $this->insert(
            $sql,
            $arrData
        );
    }


    /**
     * ==========================================================
     * BUSCAR USUARIO POR EMAIL
     * ==========================================================
     *
     * Utilizado por login tradicional y recuperación
     * de contraseña.
     */
    public function getUserEmail(string $strEmail)
    {
        $this->strUsuario = $strEmail;

        $sql = "SELECT
                    idusuario,
                    numcolaborador,
                    nombres,
                    apellidos,
                    email_user,
                    status,
                    password,
                    avatar_file,
                    rolid,
                    plantaid
                FROM usuarios
                WHERE email_user = '{$this->strUsuario}'
                LIMIT 1";

        return $this->select($sql);
    }


    /**
     * ==========================================================
     * BUSCAR USUARIO POR NÚMERO DE COLABORADOR
     * ==========================================================
     *
     * ESTA ES LA FUNCIÓN PRINCIPAL DEL SSO.
     *
     * NO filtramos status.
     *
     * Esto es intencional.
     *
     * Necesitamos distinguir:
     *
     * usuario no existe
     *
     * vs
     *
     * usuario existe pero está inactivo
     *
     */
    public function getUserByNumColaborador(
        string $numColaborador
    ) {

        $numColaborador =
            trim($numColaborador);


        $sql = "SELECT
                    idusuario,
                    numcolaborador,
                    nombres,
                    apellidos,
                    telefono,
                    email_user,
                    password,
                    rolid,
                    plantaid,
                    status,
                    cambio_password,
                    avatar,
                    avatar_file
                FROM usuarios
                WHERE numcolaborador = $numColaborador
                LIMIT 1";


        return $this->select(
            $sql
        );
    }


    /**
     * ==========================================================
     * CREAR USUARIO DESDE SSO
     * ==========================================================
     *
     * Solo se ejecuta cuando NO existe:
     *
     * usuarios.numcolaborador
     *
     * El rol por default es:
     *
     * rolid = 64
     *
     * status = 1
     */
    public function crearUsuarioSso(
        string $numColaborador,
        string $nombres,
        string $apellidos,
        string $correo
    ) {

        $numColaborador =
            trim($numColaborador);


        $nombres =
            trim($nombres);


        $apellidos =
            trim($apellidos);


        $correo =
            strtolower(
                trim($correo)
            );


        /**
         * ------------------------------------------------------
         * DOBLE VALIDACIÓN
         * ------------------------------------------------------
         *
         * Aunque el controlador ya consultó,
         * volvemos a revisar aquí.
         *
         * Esto evita crear duplicados si por alguna razón
         * dos peticiones llegan al mismo tiempo.
         */
        $usuarioExistente =
            $this->getUserByNumColaborador(
                $numColaborador
            );


        if (!empty($usuarioExistente)) {

            return (int)$usuarioExistente['idusuario'];
        }


        /**
         * ------------------------------------------------------
         * PASSWORD ALEATORIO
         * ------------------------------------------------------
         *
         * El usuario SSO no necesita conocer esta contraseña.
         *
         * Le asignamos una contraseña aleatoria para NO dejar
         * el campo vacío.
         */
        $passwordAleatorio =
            bin2hex(
                random_bytes(32)
            );


        /**
         * Tu proyecto actualmente utiliza SHA256.
         *
         * Lo mantenemos para ser compatible con loginUser().
         *
         * Más adelante sería recomendable migrar a
         * password_hash() / password_verify().
         */
        $passwordHash =
            hash(
                'SHA256',
                $passwordAleatorio
            );


        /**
         * ------------------------------------------------------
         * DATOS POR DEFAULT
         * ------------------------------------------------------
         */
        $rolDefault = 64;

        $statusDefault = 1;

        $cambioPassword = 0;


        /**
         * ------------------------------------------------------
         * INSERT
         * ------------------------------------------------------
         *
         * Solo insertamos los campos necesarios.
         *
         * Los demás campos quedan NULL/default según
         * configuración de tu tabla.
         */
        $sql = "INSERT INTO usuarios
                (
                    numcolaborador,
                    nombres,
                    apellidos,
                    email_user,
                    password,
                    rolid,
                    datecreated,
                    status,
                    cambio_password
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NOW(),
                    ?,
                    ?
                )";


        $arrData = [

            $numColaborador,

            $nombres,

            $apellidos,

            $correo,

            $passwordHash,

            $rolDefault,

            $statusDefault,

            $cambioPassword

        ];


        return $this->insert(
            $sql,
            $arrData
        );
    }


    /**
     * ==========================================================
     * TOKEN PARA RECUPERACIÓN DE CONTRASEÑA
     * ==========================================================
     *
     * Esto NO tiene relación con el JWT de RRHH.
     *
     * Es tu token tradicional de recuperación.
     */
    public function setTokenUser(
        int $idusuario,
        string $token
    ) {

        $this->intIdUsuario =
            $idusuario;


        $this->strToken =
            $token;


        $sql = "UPDATE usuarios
                SET token = ?
                WHERE idusuario = ?";


        $arrData = [

            $this->strToken,

            $this->intIdUsuario

        ];


        return $this->update(
            $sql,
            $arrData
        );
    }


    /**
     * ==========================================================
     * VALIDAR TOKEN DE RECUPERACIÓN
     * ==========================================================
     */
    public function getUsuario(
        string $email,
        string $token
    ) {

        $this->strUsuario =
            $email;


        $this->strToken =
            $token;


        $sql = "SELECT
                    idusuario
                FROM usuarios
                WHERE email_user = '$this->strUsuario'
                  AND token = '$this->strToken'
                  AND status = 1
                LIMIT 1";


        return $this->select(
            $sql
        );
    }


    /**
     * ==========================================================
     * CAMBIAR CONTRASEÑA
     * ==========================================================
     */
    public function insertPassword(
        int $idusuario,
        string $password
    ) {

        $this->intIdUsuario =
            $idusuario;


        $this->strPassword =
            $password;


        $sql = "UPDATE usuarios
                SET
                    password = ?,
                    token = ?
                WHERE idusuario = ?";


        $arrData = [

            $this->strPassword,

            "",

            $this->intIdUsuario

        ];


        return $this->update(
            $sql,
            $arrData
        );
    }


    /**
     * ==========================================================
     * FUNCIÓN LEGACY
     * ==========================================================
     * Para el SSO nuevo usamos:
     *
     * getUserByNumColaborador()
     */
    public function loginByNumColaborador(
        string $numcolaborador
    ) {

        $numcolaborador =
            trim($numcolaborador);


        $sql = "SELECT
                    idusuario,
                    numcolaborador,
                    nombres,
                    apellidos,
                    email_user,
                    status,
                    avatar_file,
                    rolid,
                    plantaid
                FROM usuarios
                WHERE numcolaborador = $numcolaborador
                  AND status != 0
                LIMIT 1";


        return $this->select(
            $sql
        );
    }
}
?>