<?php

class OrdersModel extends Mysql
{
    /**
     * Inicializa el modelo de acceso del portal de pedidos
     * y ejecuta el constructor de la clase principal Mysql.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtiene la información de acceso de un usuario mediante
     * su correo electrónico, siempre que el cliente esté activo.
     *
     * @param string $correo Correo electrónico del usuario.
     *
     * @return mixed
     */
    public function selectUsuarioPorCorreo(
        string $correo
    ) {
        $sql = "SELECT
                ua.idusuario_acceso,
                ua.idcliente,
                ua.nombre,
                ua.apellido,
                ua.correo,
                ua.password_hash,
                ua.telefono,
                ua.ultimo_login,
                ua.doble_autenticacion,
                ua.requiere_cambio_password,
                ua.fecha_cambio_password,
                ua.intentos_fallidos,
                ua.bloqueado_hasta,
                ua.estado
            FROM cli_usuarios_acceso ua
            INNER JOIN cli_clientes c
                ON c.idcliente = ua.idcliente
            WHERE LOWER(ua.correo) = LOWER('$correo')
              AND c.estado = 2
            LIMIT 1
        ";

        return $this->select(
            $sql
        );
    }

    /**
     * Obtiene la información completa de acceso de un usuario
     * mediante su identificador.
     *
     * @param int $idusuarioAcceso Identificador del usuario de acceso.
     *
     * @return mixed
     */
    public function selectUsuarioAccesoPorId(
        int $idusuarioAcceso
    ) {
        $sql = "SELECT
                idusuario_acceso,
                idcliente,
                nombre,
                apellido,
                correo,
                password_hash,
                telefono,
                ultimo_login,
                doble_autenticacion,
                requiere_cambio_password,
                fecha_cambio_password,
                intentos_fallidos,
                bloqueado_hasta,
                estado
            FROM cli_usuarios_acceso
            WHERE idusuario_acceso = ?
            LIMIT 1
        ";

        return $this->select(
            $sql,
            [$idusuarioAcceso]
        );
    }

    /**
     * Actualiza el número de intentos fallidos de inicio de sesión
     * y establece, cuando corresponda, la fecha de bloqueo de la cuenta.
     *
     * @param int         $idusuarioAcceso Identificador del usuario.
     * @param int         $intentos Número de intentos fallidos.
     * @param string|null $bloqueadoHasta Fecha límite del bloqueo.
     *
     * @return mixed
     */
    public function updateIntentosFallidos(
        int $idusuarioAcceso,
        int $intentos,
        ?string $bloqueadoHasta
    ) {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                intentos_fallidos = ?,
                bloqueado_hasta = ?,
                fecha_actualizacion = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            WHERE idusuario_acceso = $idusuarioAcceso
        ";

        return $this->update(
            $sql,
            [
                $intentos,
                $bloqueadoHasta
            ]
        );
    }

    /**
     * Registra la fecha y hora del último inicio de sesión exitoso,
     * reinicia los intentos fallidos y elimina cualquier bloqueo activo.
     *
     * @param int $idusuarioAcceso Identificador del usuario.
     *
     * @return mixed
     */
    public function updateUltimoLogin(
        int $idusuarioAcceso
    ) {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                ultimo_login = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                ),
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                fecha_actualizacion = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            WHERE idusuario_acceso = ?
        ";

        return $this->update(
            $sql,
            [$idusuarioAcceso]
        );
    }

    /**
     * Actualiza la contraseña definitiva del usuario, elimina los datos
     * de recuperación y restablece los intentos fallidos y bloqueos.
     *
     * @param int    $idusuarioAcceso Identificador del usuario.
     * @param string $passwordHash Contraseña cifrada del usuario.
     *
     * @return mixed
     */
    public function updatePasswordDefinitiva(
        int $idusuarioAcceso,
        string $passwordHash
    ) {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                password_hash = ?,
                requiere_cambio_password = 0,
                fecha_cambio_password = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                ),
                token_recuperacion = NULL,
                token_recuperacion_expira = NULL,
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                fecha_actualizacion = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            WHERE idusuario_acceso = ?
        ";

        return $this->update(
            $sql,
            [
                $passwordHash,
                $idusuarioAcceso
            ]
        );
    }

    /**
     * Marca como utilizados todos los PIN anteriores que aún se encuentren
     * activos para evitar que puedan reutilizarse.
     *
     * @param int $idusuarioAcceso Identificador del usuario.
     *
     * @return mixed
     */
    public function invalidarPinesAnteriores(
        int $idusuarioAcceso
    ) {
        $sql = "UPDATE cli_usuarios_acceso_pines
            SET utilizado = 1
            WHERE idusuario_acceso = ?
              AND utilizado = 0
        ";

        return $this->update(
            $sql,
            [$idusuarioAcceso]
        );
    }

    /**
     * Registra un PIN de doble autenticación con su código cifrado,
     * challenge, expiración e información de la sesión.
     *
     * @param int    $idusuarioAcceso Identificador del usuario.
     * @param string $codigoHash Código PIN cifrado.
     * @param string $challenge Identificador del desafío.
     * @param string $fechaExpiracion Fecha de expiración del PIN.
     * @param string $direccionIp Dirección IP del usuario.
     * @param string $idSesion Identificador de la sesión.
     *
     * @return mixed
     */
    public function insertPinDobleAutenticacion(
        int $idusuarioAcceso,
        string $codigoHash,
        string $challenge,
        string $fechaExpiracion,
        string $direccionIp,
        string $idSesion
    ) {
        /*
         * Para usar challenge, agrega la columna indicada abajo.
         */
        $sql = "INSERT INTO cli_usuarios_acceso_pines
            (
                idusuario_acceso,
                codigo_hash,
                challenge,
                fecha_generacion,
                fecha_expiracion,
                intentos,
                max_intentos,
                utilizado,
                direccion_ip,
                id_sesion
            )
            VALUES (
                ?,
                ?,
                ?,
                CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                ),
                ?,
                0,
                5,
                0,
                ?,
                ?
            )
        ";

        return $this->insert(
            $sql,
            [
                $idusuarioAcceso,
                $codigoHash,
                $challenge,
                $fechaExpiracion,
                $direccionIp,
                $idSesion
            ]
        );
    }

    /**
     * Obtiene el PIN activo más reciente relacionado con un usuario
     * y un challenge específico.
     *
     * @param int    $idusuarioAcceso Identificador del usuario.
     * @param string $challenge Identificador del desafío.
     *
     * @return mixed
     */
    public function selectPinActivoPorChallenge(
        int $idusuarioAcceso,
        string $challenge
    ) {
        $sql = "SELECT
                idpin,
                idusuario_acceso,
                codigo_hash,
                challenge,
                fecha_generacion,
                fecha_expiracion,
                intentos,
                max_intentos,
                utilizado
            FROM cli_usuarios_acceso_pines
            WHERE idusuario_acceso = ?
              AND challenge = ?
              AND utilizado = 0
            ORDER BY idpin DESC
            LIMIT 1
        ";

        return $this->select(
            $sql,
            [
                $idusuarioAcceso,
                $challenge
            ]
        );
    }

    /**
     * Actualiza el número de intentos realizados para validar
     * un PIN de doble autenticación.
     *
     * @param int $idpin Identificador del PIN.
     * @param int $intentos Número de intentos realizados.
     *
     * @return mixed
     */
    public function updateIntentoPin(
        int $idpin,
        int $intentos
    ) {
        $sql = "UPDATE cli_usuarios_acceso_pines
            SET intentos = ?
            WHERE idpin = ?
        ";

        return $this->update(
            $sql,
            [
                $intentos,
                $idpin
            ]
        );
    }

    /**
     * Marca un PIN como utilizado y registra la fecha y hora
     * en la que fue validado correctamente.
     *
     * @param int $idpin Identificador del PIN.
     *
     * @return mixed
     */
    public function validarPinCorrecto(
        int $idpin
    ) {
        $sql = "UPDATE cli_usuarios_acceso_pines
            SET
                utilizado = 1,
                fecha_validacion = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            WHERE idpin = ?
        ";

        return $this->update(
            $sql,
            [$idpin]
        );
    }

    /**
     * Invalida un PIN de doble autenticación marcándolo como utilizado.
     *
     * @param int $idpin Identificador del PIN.
     *
     * @return mixed
     */
    public function invalidarPin(
        int $idpin
    ) {
        $sql = "UPDATE cli_usuarios_acceso_pines
            SET utilizado = 1
            WHERE idpin = ?
        ";

        return $this->update(
            $sql,
            [$idpin]
        );
    }

    /**
     * Registra en la bitácora los eventos relacionados con el acceso
     * de los usuarios, incluyendo el resultado, dispositivo, navegador,
     * dirección IP, sesión y motivo del evento.
     *
     * @param int|null    $idusuarioAcceso Identificador del usuario.
     * @param int|null    $idcliente Identificador del cliente.
     * @param string      $tipoEvento Tipo de evento registrado.
     * @param string      $resultado Resultado del evento.
     * @param string|null $correoIntento Correo utilizado en el intento.
     * @param string|null $direccionIp Dirección IP.
     * @param string|null $dispositivo Nombre del dispositivo.
     * @param string|null $tipoDispositivo Tipo de dispositivo.
     * @param string|null $navegador Navegador utilizado.
     * @param string|null $versionNavegador Versión del navegador.
     * @param string|null $sistemaOperativo Sistema operativo.
     * @param string|null $ubicacion Ubicación aproximada.
     * @param string|null $idSesion Identificador de sesión.
     * @param string|null $userAgent Información del agente de usuario.
     * @param string|null $motivo Motivo o detalle del evento.
     *
     * @return mixed
     */
    public function insertLogAcceso(
        ?int $idusuarioAcceso,
        ?int $idcliente,
        string $tipoEvento,
        string $resultado,
        ?string $correoIntento,
        ?string $direccionIp,
        ?string $dispositivo,
        ?string $tipoDispositivo,
        ?string $navegador,
        ?string $versionNavegador,
        ?string $sistemaOperativo,
        ?string $ubicacion,
        ?string $idSesion,
        ?string $userAgent,
        ?string $motivo
    ) {
        $sql = "INSERT INTO cli_usuarios_acceso_logs
            (
                idusuario_acceso,
                idcliente,
                tipo_evento,
                resultado,
                correo_intento,
                direccion_ip,
                dispositivo,
                tipo_dispositivo,
                navegador,
                version_navegador,
                sistema_operativo,
                ubicacion_aproximada,
                id_sesion,
                user_agent,
                motivo,
                fecha_evento
            )
            VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            )
        ";

        return $this->insert(
            $sql,
            [
                $idusuarioAcceso,
                $idcliente,
                $tipoEvento,
                $resultado,
                $correoIntento,
                $direccionIp,
                $dispositivo,
                $tipoDispositivo,
                $navegador,
                $versionNavegador,
                $sistemaOperativo,
                $ubicacion,
                $idSesion,
                $userAgent,
                $motivo
            ]
        );
    }

    /**
     * Guarda el token de recuperación y su fecha de expiración,
     * siempre que la cuenta del usuario se encuentre activa.
     *
     * @param int    $idusuarioAcceso Identificador del usuario.
     * @param string $tokenHash Token de recuperación cifrado.
     * @param string $fechaExpiracion Fecha de expiración del token.
     *
     * @return mixed
     */
    public function updateTokenRecuperacion(
        int $idusuarioAcceso,
        string $tokenHash,
        string $fechaExpiracion
    ) {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                token_recuperacion = ?,
                token_recuperacion_expira = ?,
                fecha_actualizacion = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            WHERE idusuario_acceso = ?
              AND estado = 2
        ";

        return $this->update(
            $sql,
            [
                $tokenHash,
                $fechaExpiracion,
                $idusuarioAcceso
            ]
        );
    }

    /**
     * Obtiene un usuario mediante un token de recuperación y valida
     * que el token exista, no haya expirado y que tanto el usuario
     * como el cliente se encuentren activos.
     *
     * @param string $tokenHash Token de recuperación cifrado.
     *
     * @return mixed
     */
    public function selectUsuarioPorTokenRecuperacion(
        string $tokenHash
    ) {
        $sql = "SELECT
                ua.idusuario_acceso,
                ua.idcliente,
                ua.nombre_usuario,
                ua.apellido,
                ua.correo,
                ua.password_hash,
                ua.token_recuperacion,
                ua.token_recuperacion_expira,
                ua.doble_autenticacion,
                ua.requiere_cambio_password,
                ua.estado,
                c.estado AS estado_cliente
            FROM cli_usuarios_acceso ua
            INNER JOIN cli_clientes c
                ON c.idcliente = ua.idcliente
            WHERE ua.token_recuperacion = ?
              AND ua.token_recuperacion IS NOT NULL
              AND ua.token_recuperacion_expira IS NOT NULL
              AND ua.token_recuperacion_expira >= CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
              )
              AND ua.estado = 2
              AND c.estado = 2
            LIMIT 1
        ";

        return $this->select(
            $sql,
            [$tokenHash]
        );
    }

    /**
     * Actualiza la contraseña mediante el proceso de recuperación,
     * elimina el token utilizado, reinicia los intentos fallidos
     * y registra la fecha del cambio de contraseña.
     *
     * @param int    $idusuarioAcceso Identificador del usuario.
     * @param string $passwordHash Nueva contraseña cifrada.
     *
     * @return mixed
     */
    public function updatePasswordRecuperacion(
        int $idusuarioAcceso,
        string $passwordHash
    ) {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                password_hash = ?,
                requiere_cambio_password = 0,
                fecha_cambio_password = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                ),
                token_recuperacion = NULL,
                token_recuperacion_expira = NULL,
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                fecha_actualizacion = CONVERT_TZ(
                    UTC_TIMESTAMP(),
                    '+00:00',
                    '-06:00'
                )
            WHERE idusuario_acceso = ?
        ";

        return $this->update(
            $sql,
            [
                $passwordHash,
                $idusuarioAcceso
            ]
        );
    }
}