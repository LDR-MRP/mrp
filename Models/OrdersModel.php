<?php

class OrdersModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function selectUsuarioPorCorreo(string $correo) {
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
    public function selectUsuarioAccesoPorId(int $idusuarioAcceso) {
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
            WHERE idusuario_acceso = $idusuarioAcceso
            LIMIT 1
        ";

        return $this->select($sql);
    }

    public function updateIntentosFallidos(int $idusuarioAcceso,int $intentos,?string $bloqueadoHasta) {
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
     * @param int $idusuarioAcceso Identificador del usuario.
     *
     * @return mixed
     */
    public function updateUltimoLogin(int $idusuarioAcceso) {
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
     * Actualizms la contraseña definitiva del usuario, elimina los datos
     * de recuperación y restablece los intentos fallidos y bloqueos.
     *
     * @param int    $idusuarioAcceso Identificador del usuario.
     * @param string $passwordHash Contraseña cifrada del usuario.
     *
     * @return mixed
     */
    public function updatePasswordDefinitiva(int $idusuarioAcceso,string $passwordHash) {
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
     * Registramoss un PIN de doble autenticación con su código cifrado,
     * challenge, expiración e informacin de la sesión.
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
     * Obtienemos el PIN activo más reciente relacionado con un usuario
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
    public function updateIntentoPin(int $idpin,int $intentos) {
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
    public function validarPinCorrecto(int $idpin) {
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
     * Registramos en la bitácor los eventos relacionados con el acceso
     * de los usuaridos, incluyendo el resultado, dispositivo, navegador,
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
     * Obtienemos un usuario mediante un token de recuperación y valida
     * que el token exista, no haya expirado y que tanto el usuario
     * como el cliente se encuentren activos.d
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
     * Actualizar la contraseña mediante el proceso de recuperación,
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


/**
 * Obtiene las unidades activas publicadas
 * en el portal de distribuidores.
 */
public function selectUnidadesWeb()
{
    $sql = "SELECT
                idunidad,
                modelo,
                clave_modelo,
                nombre,
                version,
                descripcion,
                anio,
                marca,
                motor,
                stock,
                precio_estimado,
                imagen_caratula
            FROM web_unidades
            WHERE estado = 2
            ORDER BY
                marca ASC,
                modelo ASC,
                nombre ASC";

    return $this->select_all($sql);
}


/**
 * Obtiene la información completa de una unidad activa.
 */
public function selectUnidadDetalle(int $idunidad)
{
    $idunidad = intval($idunidad);

    $sql = "SELECT
                idunidad,
                modelo,
                clave_modelo,
                nombre,
                version,
                descripcion,
                anio,
                marca,
                motor,
                stock,
                precio_estimado,
                imagen_caratula,
                estado,
                fecha_creacion,
                fecha_actualizacion
            FROM web_unidades
            WHERE idunidad = {$idunidad}
              AND estado = 2
            LIMIT 1";

    return $this->select($sql);
}


/**
 * Obtiene las imágenes activas de la unidad.
 *
 * La imagen principal aparece primero y después
 * se respeta el campo orden.
 */
public function selectImagenesUnidad(int $idunidad)
{
    $idunidad = intval($idunidad);

    $sql = "SELECT
                idimagen,
                idunidad,
                nombre_original,
                nombre_archivo,
                ruta_archivo,
                orden,
                es_principal,
                estado
            FROM web_unidades_imagenes
            WHERE idunidad = {$idunidad}
              AND estado = 2
            ORDER BY
                es_principal DESC,
                orden ASC,
                idimagen ASC";

    return $this->select_all($sql);
}


    /**
     * Obtiene las sucursales activas pertenecientes
     * exclusivamente al distribuidor autenticado.
     */
    public function selectSucursalesCliente(int $idcliente)
    {
        $sql = "SELECT
                    idsucursal,
                    idcliente,
                    nombre_sucursal,
                    responsable,
                    correo,
                    telefono,
                    calle,
                    numero_exterior,
                    numero_interior,
                    colonia,
                    codigo_postal,
                    municipio,
                    estado_republica,
                    pais
                FROM cli_clientes_sucursales
                WHERE idcliente = $idcliente
                  AND estado = 2
                ORDER BY
                    nombre_sucursal ASC,
                    idsucursal ASC";

        $request = $this->select_all($sql);

        return is_array($request)
            ? $request
            : [];
    }



    /**
     * Asignamos el folio definitivo después de conocer
     * el identificador del pedido.
     */
    public function updateFolioPedido(
        int $idpedido,
        string $folio
    ): bool {
        $sql = "UPDATE ped_pedidos
                SET
                    folio_pedido = ?,
                    fecha_actualizacion = NOW()
                WHERE idpedido = ?
                  AND estado <> 0";

        return (bool) $this->update(
            $sql,
            [
                $folio,
                $idpedido
            ]
        );
    }


public function generarFolioPedido(): string
{
    $fechaActual = date('Y-m-d');

    /* ============================================================
     *  CREAR EL REGISTRO DEL DÍA SI NO EXISTE
     * ============================================================ */

    $sql = "INSERT INTO ped_folios_diarios (
                fecha_folio,
                ultimo_consecutivo,
                fecha_creacion,
                fecha_actualizacion
            ) VALUES (
                ?,
                0,
                NOW(),
                NOW()
            )
            ON DUPLICATE KEY UPDATE
                fecha_actualizacion = fecha_actualizacion";

    $arrData = [
        $fechaActual
    ];

    $this->insert(
        $sql,
        $arrData
    );

    /* ============================================================
     * OBTENER Y BLOQUEAR EL CONSECUTIVO ACTUAL
     * ============================================================ */

    $sql = "SELECT
                fecha_folio,
                ultimo_consecutivo
            FROM ped_folios_diarios
            WHERE fecha_folio = ?
            LIMIT 1
            FOR UPDATE";

    $arrData = [
        $fechaActual
    ];

    $registro = $this->select(
        $sql,
        $arrData
    );

    /*
     * Algunos métodos select() regresan:
     *
     * [
     *     'ultimo_consecutivo' => 0
     * ]
     *
     * Otros regresan:
     *
     * [
     *     0 => [
     *         'ultimo_consecutivo' => 0
     *     ]
     * ]
     *
     * Aquí soportamos ambos formatos.
     */
    if (
        is_array($registro)
        && isset($registro[0])
        && is_array($registro[0])
    ) {
        $registro = $registro[0];
    }

    if (is_object($registro)) {
        $registro = (array) $registro;
    }

    if (
        !is_array($registro)
        || !array_key_exists(
            'ultimo_consecutivo',
            $registro
        )
    ) {
        error_log(
            'Resultado generarFolioPedido: '
            . print_r(
                $registro,
                true
            )
        );

        throw new RuntimeException(
            'No fue posible obtener el consecutivo del pedido.'
        );
    }

    $consecutivoActual = (int) (
        $registro['ultimo_consecutivo']
        ?? 0
    );

    $nuevoConsecutivo =
        $consecutivoActual + 1;

    /* ============================================================
     * ACTUALIZAR EL CONSECUTIVO
     * ============================================================ */

    $sql = "UPDATE ped_folios_diarios
            SET
                ultimo_consecutivo = ?,
                fecha_actualizacion = NOW()
            WHERE fecha_folio = ?";

    $arrData = [
        $nuevoConsecutivo,
        $fechaActual
    ];

    $requestUpdate = $this->update(
        $sql,
        $arrData
    );

    /*
     * Dependiendo de tu método update(), puede devolver:
     * true, 1 o la cantidad de registros afectado
     */
    if (
        $requestUpdate === false
        || $requestUpdate === null
    ) {
        throw new RuntimeException(
            'No fue posible actualizar el consecutivo del pedido.'
        );
    }

    /* ============================================================
     * CREAR EL FOLIO
     * ============================================================ */

    return sprintf(
        'PED-%s-%04d',
        date('Ymd'),
        $nuevoConsecutivo
    );
}



 /* ============================================================
     * VALIDACIONES
     * ============================================================ */

    /**
     * Verifica que la sucursal pertenezca al distribuidor.
     */
    public function selectSucursalCliente(int $idsucursal,int $idcliente){
        $sql = "SELECT
                idsucursal,
                idcliente,
                nombre_sucursal,
                calle,
                numero_exterior,
                numero_interior,
                colonia,
                codigo_postal,
                municipio,
                estado_republica,
                pais
            FROM cli_clientes_sucursales
            WHERE idsucursal = $idsucursal
              AND idcliente = $idcliente
              AND estado = 2
            LIMIT 1
        ";

        $request = $this->select($sql);

        return is_array($request) ? $request: [];
    }

    /**
     * Obtiene la unidad y su precio desde la base de datos.
     */
    public function selectUnidadPedido(
        int $idunidad
    ){
        $sql = "SELECT
                idunidad,
                modelo,
                clave_modelo,
                nombre,
                version,
                descripcion,
                anio,
                marca,
                motor,
                stock,
                precio_estimado,
                estado
            FROM web_unidades
            WHERE idunidad = $idunidad
              AND estado = 2
            LIMIT 1
        ";

        $request = $this->select($sql);

        return is_array($request) ? $request : [];
    }

    /* ============================================================
     * INSERTAR PEDIDO
     * ============================================================ */

    public function insertPedido(array $data)
    {
        $sql = "INSERT INTO ped_pedidos (
                idcliente,
                idsede,
                idusuario_acceso,
                folio_pedido,
                clave,
                fecha_pedido,
                fecha_requerida,
                mes_facturacion_deseado,
                prioridad,
                subtotal,
                descuento,
                iva,
                total,
                observaciones,
                estatus,
                estado,
                fecha_creacion,
                fecha_actualizacion
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW(),
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                2,
                NOW(),
                NOW()
            )
        ";

        $arrData = [
            $data['idcliente'],
            $data['idsede'],
            $data['idusuario_acceso'],
            $data['folio_pedido'],
            $data['clave'],
            $data['fecha_requerida'],
            $data['mes_facturacion_deseado'],
            $data['prioridad'],
            $data['subtotal'],
            $data['descuento'],
            $data['iva'],
            $data['total'],
            $data['observaciones'],
            $data['estatus']
        ];

        $request = $this->insert(
            $sql,
            $arrData
        );

        return (int) $request;
    }

    /* ============================================================
     * INSERTAR DETALLE
     * ============================================================ */

public function insertPedidoDetalle(array $data) {
    $sql = "INSERT INTO ped_pedidos_detalle (
            idpedido,
            idunidad,
            tipo_entrega,
            idsucursal_entrega,
            direccion_entrega,
            cantidad_solicitada,
            cantidad_autorizada,
            cantidad_facturada,
            cantidad_pendiente,
            precio_unitario,
            descuento,
            subtotal,
            iva,
            total,
            estatus,
            estado,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            0,
            0,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            2,
            NOW(),
            NOW()
        )
    ";

    $arrData = [

        intval(
            $data[
                'idpedido'
            ]
        ),

        intval(
            $data[
                'idunidad'
            ]
        ),

        $data[
            'tipo_entrega'
        ],

        $data[
            'idsucursal_entrega'
        ],

        $data[
            'direccion_entrega'
        ],

        intval(
            $data[
                'cantidad_solicitada'
            ]
        ),

        intval(
            $data[
                'cantidad_pendiente'
            ]
        ),

        floatval(
            $data[
                'precio_unitario'
            ]
        ),

        floatval(
            $data[
                'descuento'
            ]
        ),

        floatval(
            $data[
                'subtotal'
            ]
        ),

        floatval(
            $data[
                'iva'
            ]
        ),

        floatval(
            $data[
                'total'
            ]
        ),

        $data[
            'estatus'
        ]
        ?? 'PENDIENTE'
    ];

    $request =
        $this->insert(
            $sql,
            $arrData
        );

    return intval(
        $request
    );
}

    /* ============================================================
     * BITÁCORA
     * ============================================================ */

    public function insertBitacoraEvento(
        array $data
    ) {
        $sql = "INSERT INTO ped_bitacora_eventos (
                idpedido,
                tipo_evento,
                descripcion,
                estatus_anterior,
                estatus_nuevo,
                usuario_registro,
                origen,
                fecha_creacion,
                fecha_actualizacion
            ) VALUES (
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                NOW(),
                NOW()
            )
        ";

        $arrData = [
            $data['idpedido'],
            $data['tipo_evento'],
            $data['descripcion'],
            $data['estatus_anterior'],
            $data['estatus_nuevo'],
            $data['usuario_registro'],
            $data['origen']
        ];

        $request = $this->insert(
            $sql,
            $arrData
        );

        return (int) $request;
    }


    public function existeClavePublicaPedido(string $clavePublica)
{
    $sql = "SELECT idpedido
        FROM ped_pedidos
        WHERE clave = '{$clavePublica}'
        LIMIT 1
    ";

    $request = $this->select($sql);

    return !empty($request);
}



/**
 * Obtienemoa la información principal del distribuidor
 * autenticado en el portal.
 */
public function selectClientePortal(int $idcliente)
{
    $sql = "SELECT
            c.idcliente,
            c.idtipo_cliente,
            c.tipo_persona,
            c.codigo_cliente,
            c.razon_social,
            c.nombre_comercial,
            c.telefono,
            c.celular,
            c.correo,
            c.correo_acceso,
            c.sitio_web,
            c.fecha_alta,
            c.estado,
            c.clave_distribuidor,
            c.zona_comercial,
            c.territorio,
            c.responsable_comercial,
            c.segmento_mercado
        FROM cli_clientes c
        WHERE c.idcliente = $idcliente
          AND c.estado = 2
        LIMIT 1
    ";

    return $this->select($sql);
}


/**
 * Obtiener todos los pedidos activos pertenecientes
 * al distribuidor autenticado.
 */
public function selectPedidosCliente(int $idcliente)
{
    $sql = "SELECT
            p.idpedido,
            p.idcliente,
            p.idsede,
            p.idusuario_acceso,
            p.folio_pedido,
            p.clave,
            p.fecha_pedido,
            p.fecha_requerida,
            p.mes_facturacion_deseado,
            p.prioridad,
            p.subtotal,
            p.descuento,
            p.iva,
            p.total,
            p.observaciones,
            p.estatus,
            p.estado,
            p.fecha_creacion,
            p.fecha_actualizacion,

            ua.nombre_usuario,

            ua.nombre AS usuario_nombre,

            ua.apellido AS usuario_apellido,

            ua.correo AS usuario_correo,

            TRIM(
                CONCAT(
                    COALESCE(ua.nombre, ''),
                    ' ',
                    COALESCE(ua.apellido, '')
                )
            ) AS registrado_por,

            s.nombre_sucursal,

            COALESCE(
                detalle.total_unidades,
                0
            ) AS total_unidades,

            COALESCE(
                detalle.total_modelos,
                0
            ) AS total_modelos

        FROM ped_pedidos p

        INNER JOIN cli_usuarios_acceso ua
            ON ua.idusuario_acceso =
               p.idusuario_acceso

        LEFT JOIN cli_clientes_sucursales s
            ON s.idsucursal = p.idsede
           AND s.idcliente = p.idcliente
           AND s.estado = 2

        LEFT JOIN
        (
            SELECT
                pd.idpedido,

                SUM(
                    pd.cantidad_solicitada
                ) AS total_unidades,

                COUNT(
                    DISTINCT pd.idunidad
                ) AS total_modelos

            FROM ped_pedidos_detalle pd

            GROUP BY
                pd.idpedido

        ) AS detalle
            ON detalle.idpedido =
               p.idpedido

        WHERE p.idcliente =  $idcliente
          AND p.estado = 2

        ORDER BY
            p.fecha_pedido DESC,
            p.idpedido DESC
    ";

    return $this->select_all($sql);
}

/**
 * Obtiene las métricas generales del distribuidor.
 */
public function selectMetricasPedidosCliente(int $idcliente)
{
    $sql = "SELECT

            (
                SELECT
                    COUNT(*)

                FROM ped_pedidos p

                WHERE p.idcliente = $idcliente
                  AND p.estado = 2

            ) AS pedidos,

            (
                SELECT
                    COALESCE(
                        SUM(
                            pd.cantidad_solicitada
                        ),
                        0
                    )

                FROM ped_pedidos_detalle pd

                INNER JOIN ped_pedidos p
                    ON p.idpedido =
                       pd.idpedido

                WHERE p.idcliente = $idcliente
                  AND p.estado = 2

            ) AS unidades,

            (
                SELECT
                    COUNT(
                        DISTINCT pd.idunidad
                    )

                FROM ped_pedidos_detalle pd

                INNER JOIN ped_pedidos p
                    ON p.idpedido =
                       pd.idpedido

                WHERE p.idcliente = $idcliente
                  AND p.estado = 2

            ) AS modelos,

            (
                SELECT
                    COALESCE(
                        SUM(p.total),
                        0
                    )

                FROM ped_pedidos p

                WHERE p.idcliente = $idcliente
                  AND p.estado = 2

            ) AS total
    ";

    return $this->select($sql);
}



/**
 * Obtenemos cuántos pedidos existen por estatus.
 */
public function selectConteoEstatusPedidosCliente(
    int $idcliente
)
{
    $sql = "SELECT
            estatus,
            COUNT(*) AS total
        FROM ped_pedidos
        WHERE idcliente = $idcliente
          AND estado = 2
        GROUP BY
            estatus
    ";

    return $this->select_all($sql);
}

///////////////////// FUNCIONES PARA EDITAR LOS EPDIDOS



public function selectPedidoEditable(string $clave, int $idcliente) {
    $sql = "SELECT
            p.idpedido,
            p.idcliente,
            p.idsede,
            p.idusuario_acceso,
            p.folio_pedido,
            p.clave,
            p.fecha_pedido,
            p.fecha_requerida,
            p.mes_facturacion_deseado,
            p.prioridad,
            p.subtotal,
            p.descuento,
            p.iva,
            p.total,
            p.observaciones,
            p.estatus,
            p.version,
            p.ultima_modificacion_por,
            p.fecha_ultima_modificacion,
            p.estado,
            p.fecha_creacion,
            p.fecha_actualizacion
        FROM ped_pedidos p
        WHERE p.clave = '{$clave}'
          AND p.idcliente = $idcliente
          AND p.estado = 2
          AND p.estatus = 'PENDIENTE'
        LIMIT 1
    ";

    return $this->select($sql);
}

public function selectDetallesPedidoEditar(int $idpedido) {
    $sql = "SELECT
            pd.idpedido_detalle,
            pd.idpedido,
            pd.idunidad,
            pd.tipo_entrega,
            pd.idsucursal_entrega,
            pd.direccion_entrega,
            pd.cantidad_solicitada,
            pd.cantidad_autorizada,
            pd.cantidad_facturada,
            pd.cantidad_pendiente,
            pd.precio_unitario,
            pd.descuento,
            pd.subtotal,
            pd.iva,
            pd.total,
            pd.estatus,
            pd.estado,
            pd.fecha_creacion,
            pd.fecha_actualizacion,

            wu.modelo,
            wu.clave_modelo,
            wu.nombre,
            wu.version AS version_unidad,
            wu.descripcion,
            wu.anio,
            wu.marca,
            wu.motor,
            wu.stock,
            wu.precio_estimado,
            wu.imagen_caratula

        FROM ped_pedidos_detalle pd

        INNER JOIN web_unidades wu
            ON wu.idunidad = pd.idunidad
           AND wu.estado = 2

        WHERE pd.idpedido = $idpedido
          AND pd.estado = 2

        ORDER BY
            pd.idpedido_detalle ASC
    ";

    return $this->select_all($sql);
}



public function validarPedidoEditable(string $clave,int $idcliente) {
    $sql = "SELECT
            idpedido,
            idcliente,
            folio_pedido,
            clave,
            estatus,
            version,
            estado
        FROM ped_pedidos
        WHERE clave = '{$clave}'
          AND idcliente = $idcliente
          AND estado = 2
          AND estatus = 'PENDIENTE'
        LIMIT 1
    ";

    return $this->select($sql);
}

// public function selectSucursalesCliente(
//     int $idcliente
// ) {
//     $sql = "
//         SELECT
//             idsucursal,
//             idcliente,
//             nombre_sucursal,
//             codigo_sucursal,
//             telefono,
//             correo,
//             estado
//         FROM cli_clientes_sucursales
//         WHERE idcliente = ?
//           AND estado = 2
//         ORDER BY
//             nombre_sucursal ASC
//     ";

//     return $this->select_all(
//         $sql,
//         [
//             $idcliente
//         ]
//     );
// }


// public function selectUnidadPedido(
//     int $idunidad
// ) {
//     $sql = "
//         SELECT
//             idunidad,
//             modelo,
//             clave_modelo,
//             nombre,
//             version,
//             descripcion,
//             anio,
//             marca,
//             motor,
//             stock,
//             precio_estimado,
//             imagen_caratula
//         FROM web_unidades
//         WHERE idunidad = ?
//           AND estado = 2
//         LIMIT 1
//     ";

//     return $this->select(
//         $sql,
//         [
//             $idunidad
//         ]
//     );
// }



public function updatePedidoCabecera(int $idpedido,array $data) {
    $sql = "UPDATE ped_pedidos
        SET
            fecha_requerida = ?,
            mes_facturacion_deseado = ?,
            prioridad = ?,
            subtotal = ?,
            descuento = ?,
            iva = ?,
            total = ?,
            observaciones = ?,
            version = version + 1,
            ultima_modificacion_por = ?,
            fecha_ultima_modificacion = NOW(),
            fecha_actualizacion = NOW()
        WHERE idpedido = $idpedido
          AND estado = 2
          AND estatus = 'PENDIENTE'
    ";

    $arrData = [
        $data['fecha_requerida'],
        $data['mes_facturacion_deseado'],
        $data['prioridad'],
        $data['subtotal'],
        $data['descuento'],
        $data['iva'],
        $data['total'],
        $data['observaciones'],
        $data['idusuario_modificacion']
    ];

    return $this->update($sql,$arrData);
}


public function updatePedidoDetalle(int $idpedidoDetalle,int $idpedido,array $data) {
    $sql = "UPDATE ped_pedidos_detalle
        SET
            tipo_entrega = ?,
            idsucursal_entrega = ?,
            direccion_entrega = ?,
            cantidad_solicitada = ?,
            cantidad_pendiente = ?,
            precio_unitario = ?,
            descuento = ?,
            subtotal = ?,
            iva = ?,
            total = ?,
            fecha_actualizacion = NOW()
        WHERE idpedido_detalle = $idpedidoDetalle
          AND idpedido =  $idpedido
          AND estado = 2
          AND estatus = 'PENDIENTE'
    ";

    $arrData = [
        $data['tipo_entrega'],
        $data['idsucursal_entrega'],
        $data['direccion_entrega'],
        $data['cantidad_solicitada'],
        $data['cantidad_pendiente'],
        $data['precio_unitario'],
        $data['descuento'],
        $data['subtotal'],
        $data['iva'],
        $data['total'] 
    ];

    return $this->update($sql,$arrData);
}


public function desactivarPedidoDetalle(int $idpedidoDetalle,int $idpedido) {
    $sql = " UPDATE ped_pedidos_detalle
        SET
            estado = ?,
            fecha_actualizacion = NOW()
        WHERE idpedido_detalle = $idpedidoDetalle
          AND idpedido = $idpedido
          AND estado = 2
          AND estatus = 'PENDIENTE'
    ";

    return $this->update(
        $sql,
        [0]
    );
}


public function selectPedidoDetallePorId(int $idpedidoDetalle,int $idpedido
) {
    $sql = "SELECT
            pd.*,
            wu.nombre,
            wu.modelo,
            wu.version AS version_unidad
        FROM ped_pedidos_detalle pd

        INNER JOIN web_unidades wu
            ON wu.idunidad = pd.idunidad

        WHERE pd.idpedido_detalle = $idpedidoDetalle
          AND pd.idpedido = $idpedido
          AND pd.estado = 2
        LIMIT 1
    ";

    return $this->select($sql);
}


// public function insertPedidoDetalle(array $data)
// {
//     $sql = "INSERT INTO ped_pedidos_detalle
//         (
//             idpedido,
//             idunidad,
//             tipo_entrega,
//             idsucursal_entrega,
//             direccion_entrega,
//             cantidad_solicitada,
//             cantidad_autorizada,
//             cantidad_facturada,
//             cantidad_pendiente,
//             precio_unitario,
//             descuento,
//             subtotal,
//             iva,
//             total,
//             estatus,
//             estado,
//             fecha_creacion,
//             fecha_actualizacion
//         )
//         VALUES
//         (
//             ?, ?, ?, ?, ?,
//             ?, ?, ?, ?, ?,
//             ?, ?, ?, ?, ?,
//             2,
//             NOW(),
//             NOW()
//         )
//     ";

//     $arrData = [
//         $data['idpedido'],
//         $data['idunidad'],
//         $data['tipo_entrega'],
//         $data['idsucursal_entrega'],
//         $data['direccion_entrega'],
//         $data['cantidad_solicitada'],
//         0,
//         0,
//         $data['cantidad_pendiente'],
//         $data['precio_unitario'],
//         $data['descuento'],
//         $data['subtotal'],
//         $data['iva'],
//         $data['total'],
//         'PENDIENTE'
//     ];

//     return $this->insert(
//         $sql,
//         $arrData
//     );
// }

public function selectDatosCorreoPedido(int $idpedido) {
    $sql = "SELECT
            p.idpedido,
            p.folio_pedido,
            p.clave,
            p.fecha_pedido,
            p.fecha_requerida,
            p.mes_facturacion_deseado,
            p.prioridad,
            p.subtotal,
            p.descuento,
            p.iva,
            p.total,
            p.observaciones,
            p.estatus,

            c.idcliente,
            c.codigo_cliente,
            c.clave_distribuidor,
            c.razon_social,
            c.nombre_comercial,
            c.telefono AS telefono_cliente,
            c.celular AS celular_cliente,
            c.correo AS correo_cliente,

            ua.idusuario_acceso,
            ua.nombre,
            ua.apellido,
            ua.correo AS correo_usuario,
            ua.telefono AS telefono_usuario

        FROM ped_pedidos AS p

        INNER JOIN cli_clientes AS c
            ON c.idcliente = p.idcliente

        INNER JOIN cli_usuarios_acceso AS ua
            ON ua.idusuario_acceso = p.idusuario_acceso

        WHERE p.idpedido = $idpedido
          AND p.estado = 2

        LIMIT 1
    ";

    return $this->select($sql);
}



public function selectPedidoDetalle(string $clave) {

    $sql = "SELECT

            /* ============================================
             * PEDIDO
             * ============================================ */

            p.idpedido,
            p.idcliente,
            p.idsede,
            p.idusuario_acceso,

            p.folio_pedido,
            p.clave,

            p.fecha_pedido,
            p.fecha_requerida,
            p.mes_facturacion_deseado,

            p.prioridad,

            p.subtotal,
            p.descuento,
            p.iva,
            p.total,

            p.observaciones,

            p.estatus,
            p.estado,

            p.fecha_creacion,
            p.fecha_actualizacion,

            p.version,
            p.fecha_ultima_modificacion,


            /* ============================================
             * CLIENTE / DISTRIBUIDOR
             * ============================================ */

            c.codigo_cliente,
            c.clave_distribuidor,

            c.razon_social,
            c.nombre_comercial,

            c.telefono AS telefono_cliente,
            c.celular AS celular_cliente,
            c.correo AS correo_cliente,


            /* ============================================
             * USUARIO PORTAL
             * ============================================ */

            ua.nombre AS nombre_usuario,
            ua.apellido AS apellido_usuario,
            ua.correo AS correo_usuario,
            ua.telefono AS telefono_usuario


        FROM ped_pedidos AS p


        INNER JOIN cli_clientes AS c
            ON c.idcliente = p.idcliente


        LEFT JOIN cli_usuarios_acceso AS ua
            ON ua.idusuario_acceso = p.idusuario_acceso


        WHERE p.clave = '{$clave}'
        --   AND p.idcliente = $idcliente
          AND p.estado = 2

        LIMIT 1
    ";


    return $this->select($sql);
}




public function selectDetallesPedido(int $idpedido) {

    $sql = "SELECT

            /* ============================================
             * DETALLE
             * ============================================ */

            d.idpedido_detalle,
            d.idpedido,
            d.idunidad,

            d.tipo_entrega,
            d.idsucursal_entrega,
            d.direccion_entrega,

            d.cantidad_solicitada,
            d.cantidad_autorizada,
            d.cantidad_facturada,
            d.cantidad_pendiente,

            d.precio_unitario,
            d.descuento,
            d.subtotal,
            d.iva,
            d.total,

            d.estatus,

            d.fecha_creacion,
            d.fecha_actualizacion,


            /* ============================================
             * UNIDAD
             * ============================================ */

            u.modelo,
            u.clave_modelo,
            u.nombre,
            u.version,
            u.descripcion,
            u.anio,
            u.marca,
            u.motor,
            u.imagen_caratula,


            /* ============================================
             * SUCURSAL
             * ============================================ */

            s.nombre_sucursal


        FROM ped_pedidos_detalle AS d


        INNER JOIN web_unidades AS u
            ON u.idunidad = d.idunidad


        LEFT JOIN cli_clientes_sucursales AS s
            ON s.idsucursal = d.idsucursal_entrega


        WHERE d.idpedido = $idpedido
          AND d.estado = 2

        ORDER BY
            d.idpedido_detalle ASC
    ";


    return $this->select_all($sql);
}





public function selectPedidoParaCancelar(string $clave,int $idcliente) {

    $sql = "SELECT
            idpedido,
            idcliente,
            idusuario_acceso,
            folio_pedido,
            clave,
            estatus,
            estado
        FROM ped_pedidos
        WHERE clave = '{$clave}'
          AND idcliente = $idcliente
          AND estado = 2
        LIMIT 1
    ";
    return $this->select($sql);

}


public function cancelarPedidoModel(
    int $idpedido,
    int $idcliente,
    int $idusuarioAcceso
) {

    $sql = "UPDATE ped_pedidos

        SET
            estatus = 'CANCELADO',
            ultima_modificacion_por = ?,
            fecha_ultima_modificacion = NOW(),
            fecha_actualizacion = NOW(),
            version = version + 1
        WHERE idpedido = $idpedido
          AND idcliente = $idcliente
          AND estado = 2
          AND estatus = 'PENDIENTE'
    ";

    $arrData = [$idusuarioAcceso];
    $request =
        $this->update(
            $sql,
            $arrData
        );

    return $request;

}









}