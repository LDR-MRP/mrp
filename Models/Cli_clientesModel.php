<?php

class Cli_clientesModel extends Mysql
{
    public $intIddistribuidor;

    public function __construct()
    {
        parent::__construct();
    }


    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS CLIENTES
    |--------------------------------------------------------------------------
    */
    public function selectTodos()
    {
        $sql = "SELECT * FROM cli_clientes";

        return $this->select_all($sql);
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIÓN PARA OBTENER TODOS LOS DISTRIBUIDORES
    |--------------------------------------------------------------------------
    */

    public function selectDistribuidores()
    {
        $sql = "SELECT * FROM cli_clientes WHERE idtipo_cliente = 1";

        return $this->select_all($sql);
    }

    /*
|--------------------------------------------------------------------------
| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES INTERNOS
|--------------------------------------------------------------------------
*/

    public function selectInternos()
    {
        $sql = "SELECT * FROM cli_clientes WHERE idtipo_cliente = 2";

        return $this->select_all($sql);
    }

    /*
|--------------------------------------------------------------------------
| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES EXTERNOS
|--------------------------------------------------------------------------
*/

    public function selectExternos()
    {
        $sql = "SELECT * FROM cli_clientes WHERE idtipo_cliente = 3";

        return $this->select_all($sql);
    }

    /*
|--------------------------------------------------------------------------
| FUNCIÓN PARA OBTENER TODOS LOS CLIENTES GUBERNAMENTALES
|--------------------------------------------------------------------------
*/

    public function selectGubernamentales()
    {
        $sql = "SELECT * FROM cli_clientes WHERE idtipo_cliente = 4";

        return $this->select_all($sql);
    }

    ////////////////////////////////////////////////////////////////////


    public function selectClienteAcceso(int $idcliente)
    {
        $sql = "SELECT
                c.idcliente,
                c.idtipo_cliente,
                tc.nombre AS tipo_cliente,
                c.codigo_cliente,
                c.razon_social,
                c.nombre_comercial,
                c.correo,
                c.telefono,
                c.estado
            FROM cli_clientes c
            INNER JOIN cli_tipos_cliente tc
                ON tc.id = c.idtipo_cliente
            WHERE c.idcliente = $idcliente
            LIMIT 1
        ";

        return $this->select($sql);
    }

    public function selectUsuarioAccesoPorCliente(int $idcliente)
    {
        $sql = "SELECT
                ua.idusuario_acceso,
                ua.idcliente,
                ua.nombre_usuario,
                ua.nombre,
                ua.apellido,
                ua.correo,
                ua.telefono,
                ua.url_portal,
                ua.ultimo_login,
                ua.ultimo_envio_accesos,
                ua.doble_autenticacion,
                ua.requiere_cambio_password,
                ua.fecha_cambio_password,
                ua.intentos_fallidos,
                ua.bloqueado_hasta,
                ua.estado,
                ua.fecha_creacion,
                ua.fecha_actualizacion
            FROM cli_usuarios_acceso ua
            WHERE ua.idcliente = $idcliente
            LIMIT 1
        ";

        return $this->select($sql);
    }

    public function selectUsuarioAccesoPorId(int $idusuarioAcceso)
    {
        $sql = "SELECT
                idusuario_acceso,
                idcliente,
                nombre_usuario,
                nombre,
                apellido,
                correo,
                password_hash,
                telefono,
                url_portal,
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

        return $this->select($sql, [$idusuarioAcceso]);
    }

    public function selectUsuarioPorCorreo(string $correo)
    {
        $sql = "SELECT
                idusuario_acceso,
                idcliente,
                nombre_usuario,
                nombre,
                apellido,
                correo,
                password_hash,
                url_portal,
                ultimo_login,
                doble_autenticacion,
                requiere_cambio_password,
                fecha_cambio_password,
                intentos_fallidos,
                bloqueado_hasta,
                estado
            FROM cli_usuarios_acceso
            WHERE correo = ?
            LIMIT 1
        ";

        return $this->select($sql, [$correo]);
    }

    public function insertUsuarioAcceso(
        int $idcliente,
        string $nombreUsuario,
        string $nombre,
        string $apellido,
        string $correo,
        string $passwordHash,
        string $telefono,
        string $urlPortal,
        int $dobleAutenticacion,
        int $usuarioRegistro
    ) {
        $sql = "INSERT INTO cli_usuarios_acceso
        (
            idcliente,
            nombre_usuario,
            nombre,
            apellido,
            correo,
            password_hash,
            telefono,
            url_portal,
            doble_autenticacion,
            requiere_cambio_password,
            fecha_cambio_password,
            intentos_fallidos,
            estado,
            created_by,
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
            ?,
            ?,
            ?,
            1,
            NULL,
            0,
            2,
            ?,
            CONVERT_TZ(
                UTC_TIMESTAMP(),
                '+00:00',
                '-06:00'
            ),
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
                $idcliente,
                $nombreUsuario,
                $nombre,
                $apellido,
                $correo,
                $passwordHash,
                $telefono,
                $urlPortal,
                $dobleAutenticacion,
                $usuarioRegistro
            ]
        );
    }

    public function updateUsuarioAcceso(
        int $idusuarioAcceso,
        int $idcliente,
        string $nombreUsuario,
        string $correo,
        string $passwordHash,
        string $urlPortal,
        int $dobleAutenticacion,
        int $usuarioActualiza
    ) {
        $sql = "UPDATE cli_usuarios_acceso
        SET
            nombre_usuario = ?,
            correo = ?,
            password_hash = ?,
            url_portal = ?,
            doble_autenticacion = ?,
            requiere_cambio_password = 1,
            fecha_cambio_password = NULL,
            intentos_fallidos = 0,
            bloqueado_hasta = NULL,
            estado = 2,
            updated_by = ?,
            fecha_actualizacion = CONVERT_TZ(
                UTC_TIMESTAMP(),
                '+00:00',
                '-06:00'
            )
        WHERE idusuario_acceso = ?
          AND idcliente = ?
    ";

        return $this->update(
            $sql,
            [
                $nombreUsuario,
                $correo,
                $passwordHash,
                $urlPortal,
                $dobleAutenticacion,
                $usuarioActualiza,
                $idusuarioAcceso,
                $idcliente
            ]
        );
    }

    public function updateFechaEnvioAccesos(int $idusuarioAcceso)
    {
        $sql = "UPDATE cli_usuarios_acceso
        SET
            ultimo_envio_accesos = CONVERT_TZ(
                UTC_TIMESTAMP(),
                '+00:00',
                '-06:00'
            ),
            fecha_actualizacion = CONVERT_TZ(
                UTC_TIMESTAMP(),
                '+00:00',
                '-06:00'
            )
        WHERE idusuario_acceso = ?
    ";

        return $this->update($sql, [$idusuarioAcceso]);
    }

    public function updateUltimoLogin(int $idusuarioAcceso)
    {
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

        return $this->update($sql, [$idusuarioAcceso]);
    }

    public function updateIntentosFallidos(
        int $idusuarioAcceso,
        int $intentos,
        ?string $bloqueadoHasta = null
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
        WHERE idusuario_acceso = ?
    ";

        return $this->update(
            $sql,
            [
                $intentos,
                $bloqueadoHasta,
                $idusuarioAcceso
            ]
        );
    }

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
        $sql = "
        INSERT INTO cli_usuarios_acceso_logs
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
            ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
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

    public function selectLogsAcceso(int $idcliente)
    {
        $sql = "SELECT
                l.idlog,
                DATE_FORMAT(l.fecha_evento, '%d/%m/%Y') AS fecha,
                DATE_FORMAT(l.fecha_evento, '%Y-%m-%d') AS fecha_iso,
                DATE_FORMAT(l.fecha_evento, '%H:%i:%s') AS hora,
                l.tipo_evento,
                l.resultado,
                l.correo_intento,
                l.direccion_ip AS ip,
                l.dispositivo,
                l.tipo_dispositivo,
                l.navegador,
                l.version_navegador,
                l.sistema_operativo,
                l.ubicacion_aproximada AS ubicacion,
                l.id_sesion,
                l.user_agent,
                l.motivo AS detalle
            FROM cli_usuarios_acceso_logs l
            WHERE l.idcliente = ?
            ORDER BY l.fecha_evento DESC
        ";

        return $this->select_all($sql, [$idcliente]);
    }

    public function invalidarPinesAnteriores(int $idusuarioAcceso)
    {
        $sql = "UPDATE cli_usuarios_acceso_pines
            SET utilizado = 1
            WHERE idusuario_acceso = ?
              AND utilizado = 0
        ";

        return $this->update($sql, [$idusuarioAcceso]);
    }

    public function insertPinDobleAutenticacion(
        int $idusuarioAcceso,
        string $codigoHash,
        string $fechaExpiracion,
        ?string $direccionIp,
        ?string $idSesion
    ) {
        $sql = "INSERT INTO cli_usuarios_acceso_pines
        (
            idusuario_acceso,
            codigo_hash,
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
                $fechaExpiracion,
                $direccionIp,
                $idSesion
            ]
        );
    }

    public function selectPinActivo(int $idusuarioAcceso)
    {
        $sql = "SELECT
            idpin,
            idusuario_acceso,
            codigo_hash,
            fecha_generacion,
            fecha_expiracion,
            intentos,
            max_intentos,
            utilizado
        FROM cli_usuarios_acceso_pines
        WHERE idusuario_acceso = ?
          AND utilizado = 0
          AND fecha_expiracion >= CONVERT_TZ(
                UTC_TIMESTAMP(),
                '+00:00',
                '-06:00'
          )
        ORDER BY idpin DESC
        LIMIT 1
    ";

        return $this->select($sql, [$idusuarioAcceso]);
    }

    public function updateIntentoPin(int $idpin, int $intentos)
    {
        $sql = "UPDATE cli_usuarios_acceso_pines
            SET intentos = ?
            WHERE idpin = ?
        ";

        return $this->update($sql, [
            $intentos,
            $idpin
        ]);
    }

    public function validarPin(int $idpin)
    {
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

        return $this->update($sql, [$idpin]);
    }

    public function insertEnvioAcceso(
        int $idusuarioAcceso,
        int $idcliente,
        string $correo,
        string $tipoEnvio,
        string $asunto,
        string $resultado,
        ?string $detalle,
        ?int $enviadoPor
    ) {
        $sql = "INSERT INTO cli_usuarios_acceso_envios
        (
            idusuario_acceso,
            idcliente,
            correo_destino,
            tipo_envio,
            asunto,
            resultado,
            detalle,
            enviado_por,
            fecha_envio
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
                $correo,
                $tipoEnvio,
                $asunto,
                $resultado,
                $detalle,
                $enviadoPor
            ]
        );
    }

    ///////////////////////////////////////////////////////////////////

    public function selectDistribuidor(int $iddistribuidor)
    {
        $this->intIddistribuidor = $iddistribuidor;

        // DATOS DEL DISTRIBUIDOR
        $sqlDistribuidor = "SELECT 
            c.id,
            c.grupo_id,
            g.nombre AS nombre_grupo,
            c.regimen_fiscal_id,
            c.tipo_persona,
            c.nombre_fisica,
            c.apellido_paterno,
            c.apellido_materno,
            c.fecha_nacimiento,
            c.correo,
            c.curp,
            c.razon_social,
            c.representante_legal,
            c.domicilio_fiscal,
            c.rfc,
            c.nombre_comercial,
            c.repve,
            c.plaza,
            c.clasificacion,
			c.estatus,
            c.tipo_negocio,
            c.tipo_cliente_id,
            c.matriz_id,
            c.telefono,
            c.telefono_alt,
            c.fecha_registro,
            c.estado,

            -- DATOS DE LA MATRIZ
            m.nombre_comercial AS matriz_nombre_comercial,
            m.razon_social AS matriz_razon_social,

            -- DATOS DEL TIPO DE NEGÓCIO
            t.nombre AS nombre_tipo_negocio

        FROM cli_distribuidores c
        INNER JOIN cli_grupos g ON c.grupo_id = g.id
        LEFT JOIN cli_distribuidores m ON m.id = c.matriz_id
        LEFT JOIN cli_tipos_cliente t ON t.id = c.tipo_cliente_id
        WHERE c.id = {$this->intIddistribuidor}
        AND c.estado != 0";

        $distribuidor = $this->select($sqlDistribuidor);

        if ($distribuidor['tipo_negocio'] === 'Matriz') {
            $sqlSucursales = "SELECT
                s.id,
                s.nombre_comercial,
                s.razon_social,
                s.plaza,
                s.clasificacion,
                s.telefono,
                s.estado
            FROM cli_distribuidores s
            WHERE s.matriz_id = {$this->intIddistribuidor}
            AND s.estado != 0";
            $distribuidor['sucursales'] = $this->select_all($sqlSucursales) ?? [];
        } else {
            $distribuidor['sucursales'] = [];
        }

        if (empty($distribuidor)) {
            return null;
        }

        // DIRECCION
        $sqlDireccion = "SELECT 
            d.tipo,
            d.calle,
            d.numero_ext,
            d.numero_int,
            d.colonia,
            d.codigo_postal,
            d.pais_id,
            p.nombre AS pais,
            d.estado_id,
            e.nombre AS estado,
            r.id AS region_id,
            r.nombre AS region,
            d.municipio_id,
            m.nombre AS municipio,
            d.latitud,
            d.longitud
        FROM cli_distribuidor_direcciones d
        LEFT JOIN cli_paises p ON p.id = d.pais_id
        LEFT JOIN cli_estados e ON e.id = d.estado_id
        LEFT JOIN cli_regiones r ON r.id = e.region_id
        LEFT JOIN cli_municipios m ON m.id = d.municipio_id
        WHERE d.distribuidor_id = {$this->intIddistribuidor}";

        $distribuidor['direccion'] = $this->select($sqlDireccion) ?? [];

        // DIRECCION FISCAL
        $sqlDireccionFiscal = "SELECT 
            d.tipo,
            d.calle,
            d.numero_ext,
            d.numero_int,
            d.colonia,
            d.codigo_postal,
            d.pais_id,
            p.nombre AS pais,
            d.estado_id,
            e.nombre AS estado,
            d.municipio_id,
            m.nombre AS municipio,
            d.latitud,
            d.longitud
        FROM cli_distribuidor_direcciones_fiscales d
        LEFT JOIN cli_paises p ON p.id = d.pais_id
        LEFT JOIN cli_estados e ON e.id = d.estado_id
        LEFT JOIN cli_municipios m ON m.id = d.municipio_id
        WHERE d.distribuidor_id = {$this->intIddistribuidor}";

        $distribuidor['direccion_fiscal'] = $this->select($sqlDireccionFiscal) ?? [];

        // CONTACTOS
        if ($distribuidor['tipo_negocio'] === 'Matriz') {

            // Matriz → contactos propios + contactos de sucursales
            $sqlContactos = "SELECT
                c.id,
                c.nombre,
                c.correo,
                c.telefono,
                c.estatus,
                c.fecha_registro,
                p.id AS puesto_id,
                p.nombre AS puesto,
                d.id AS departamento_id,
                d.nombre AS departamento,
                dist.nombre_comercial AS distribuidor
            FROM cli_contactos c
            INNER JOIN cli_puestos p ON p.id = c.puesto_id
            INNER JOIN cli_departamentos d ON d.id = p.departamento_id
            INNER JOIN cli_distribuidores dist ON dist.id = c.distribuidor_id
            WHERE
                c.distribuidor_id = {$this->intIddistribuidor}
                OR dist.matriz_id = {$this->intIddistribuidor}";
        } else {
            // Sucursal → solo sus contactos
            $sqlContactos = "SELECT
                c.id,
                c.nombre,
                c.correo,
                c.telefono,
                c.estatus,
                c.fecha_registro,
                p.id AS puesto_id,
                p.nombre AS puesto,
                d.id AS departamento_id,
                d.nombre AS departamento
            FROM cli_contactos c
            INNER JOIN cli_puestos p ON p.id = c.puesto_id
            INNER JOIN cli_departamentos d ON d.id = p.departamento_id
            WHERE c.distribuidor_id = {$this->intIddistribuidor}";
        }

        $distribuidor['contactos'] = $this->select_all($sqlContactos) ?? [];

        // MODELOS
        $sqlModelos = "SELECT 
            m.idlineaproducto,
            m.cve_linea_producto,
            m.descripcion
        FROM cli_distribuidor_modelos dm
        INNER JOIN wms_linea_producto m ON m.idlineaproducto = dm.id_modelo
        WHERE dm.distribuidor_id = {$this->intIddistribuidor}";

        $distribuidor['modelos'] = $this->select_all($sqlModelos);

        // REGIONALES
        $sqlRegionales = "SELECT
            r.id,
            r.nombre,
            r.apellido_paterno,
            r.apellido_materno
        FROM cli_regional_distribuidor crd
        INNER JOIN cli_regionales r ON r.id = crd.regional_id
        WHERE crd.distribuidor_id = {$this->intIddistribuidor}";

        $distribuidor['regionales'] = $this->select_all($sqlRegionales);

        return $distribuidor;
    }

    public function insertDistribuidor(
        int $grupo_id,
        string $regimen_fiscal_id,
        string $tipo_persona,
        string $tipo_cliente_id,
        string $nombre_fisica,
        string $apellido_paterno,
        string $apellido_materno,
        string $fecha_nacimiento,
        string $correo,
        string $curp,
        string $razon_social,
        string $representante_legal,
        string $domicilio_fiscal,
        string $rfc,
        string $nombre_comercial,
        string $repve,
        string $plaza,
        string $clasificacion,
        string $estatus,
        string $tipo_negocio,
        ?int $matriz_id,
        string $telefono,
        string $telefono_alt
    ) {
        $sql = "INSERT INTO cli_distribuidores 
        (grupo_id, regimen_fiscal_id, tipo_persona, tipo_cliente_id, nombre_fisica, apellido_paterno, apellido_materno, fecha_nacimiento, correo, curp, razon_social, representante_legal, domicilio_fiscal, rfc , nombre_comercial, repve, plaza, clasificacion, estatus, tipo_negocio, matriz_id, telefono, telefono_alt)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $arrData = [
            $grupo_id,
            $regimen_fiscal_id,
            $tipo_persona,
            $tipo_cliente_id,
            $nombre_fisica,
            $apellido_paterno,
            $apellido_materno,
            $fecha_nacimiento,
            $correo,
            $curp,
            $razon_social,
            $representante_legal,
            $domicilio_fiscal,
            $rfc,
            $nombre_comercial,
            $repve,
            $plaza,
            $clasificacion,
            $estatus,
            $tipo_negocio,
            $matriz_id,
            $telefono,
            $telefono_alt
        ];

        return $this->insert($sql, $arrData);
    }

    public function deleteDistribuidor(int $iddistribuidor)
    {
        $this->intIddistribuidor = $iddistribuidor;
        $sql = "UPDATE cli_distribuidores SET estado = ? WHERE id = $this->intIddistribuidor ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function updateDistribuidor(
        int $id,
        int $grupo_id,
        string $regimen_fiscal_id,
        string $tipo_persona,
        string $tipo_cliente_id,
        string $nombre_fisica,
        string $apellido_paterno,
        string $apellido_materno,
        string $fecha_nacimiento,
        string $correo,
        string $curp,
        string $razon_social,
        string $representante_legal,
        string $domicilio_fiscal,
        string $rfc,
        string $nombre_comercial,
        string $repve,
        string $plaza,
        string $clasificacion,
        string $estatus,
        string $tipo_negocio,
        ?int $matriz_id,
        string $telefono,
        string $telefono_alt
    ) {
        $sql = "UPDATE cli_distribuidores SET
        grupo_id = ?,
        regimen_fiscal_id = ?,
        tipo_persona = ?,
        tipo_cliente_id = ?,
        nombre_fisica = ?,
        apellido_paterno = ?,
        apellido_materno = ?,
        fecha_nacimiento = ?,
        correo = ?,
        curp = ?,
        razon_social = ?,
        representante_legal = ?,
        domicilio_fiscal = ?,
        rfc = ?,
        nombre_comercial = ?,
        repve = ?,
        plaza = ?,
        clasificacion = ?,
        estatus = ?,
        tipo_negocio = ?,
        matriz_id = ?,
        telefono = ?,
        telefono_alt = ?
        WHERE id = ?";

        $arrData = [
            $grupo_id,
            $regimen_fiscal_id,
            $tipo_persona,
            $tipo_cliente_id,
            $nombre_fisica,
            $apellido_paterno,
            $apellido_materno,
            $fecha_nacimiento,
            $correo,
            $curp,
            $razon_social,
            $representante_legal,
            $domicilio_fiscal,
            $rfc,
            $nombre_comercial,
            $repve,
            $plaza,
            $clasificacion,
            $estatus,
            $tipo_negocio,
            $matriz_id,
            $telefono,
            $telefono_alt,
            $id
        ];

        return $this->update($sql, $arrData);
    }

    public function insertDistribuidorRegional(int $regional_id, int $distribuidor_id)
    {
        $sql = "INSERT INTO cli_regional_distribuidor (regional_id, distribuidor_id)
            VALUES (?, ?)";

        $arrData = [
            $regional_id,
            $distribuidor_id
        ];

        return $this->insert($sql, $arrData);
    }

    public function deleteDistribuidorRegional(int $distribuidor_id)
    {
        $distribuidor_id = intval($distribuidor_id);

        $sql = "DELETE FROM cli_regional_distribuidor 
            WHERE distribuidor_id = $distribuidor_id";

        return $this->delete($sql);
    }

    public function insertDistribuidorModelo(int $distribuidor_id, int $id_modelo)
    {
        $sql = "INSERT INTO cli_distribuidor_modelos
            (distribuidor_id, id_modelo)
            VALUES (?, ?)";

        $arrData = [
            $distribuidor_id,
            $id_modelo
        ];

        return $this->insert($sql, $arrData);
    }

    public function deleteDistribuidorModelos(int $distribuidor_id)
    {
        $distribuidor_id = intval($distribuidor_id);

        $sql = "DELETE FROM cli_distribuidor_modelos
            WHERE distribuidor_id = $distribuidor_id";

        return $this->delete($sql);
    }

    // public function insertDireccion(
    //     int $distribuidor_id,
    //     string $tipo,
    //     string $calle,
    //     string $numero_ext,
    //     string $numero_int,
    //     string $colonia,
    //     string $codigo_postal,
    //     int $pais_id,
    //     int $estado_id,
    //     int $municipio_id,
    //     float $latitud = null,
    //     float $longitud = null
    // ) {
    //     $sql = "INSERT INTO cli_distribuidor_direcciones
    //     (distribuidor_id, tipo, calle, numero_ext, numero_int, colonia, codigo_postal,
    //      pais_id, estado_id, municipio_id, latitud, longitud)
    //     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    //     $arrData = [
    //         $distribuidor_id,
    //         $tipo,
    //         $calle,
    //         $numero_ext,
    //         $numero_int,
    //         $colonia,
    //         $codigo_postal,
    //         $pais_id,
    //         $estado_id,
    //         $municipio_id,
    //         $latitud,
    //         $longitud
    //     ];

    //     return $this->insert($sql, $arrData);
    // }

    // public function deleteDirecciones(int $distribuidor_id)
    // {
    //     $distribuidor_id = intval($distribuidor_id);

    //     $sql = "DELETE FROM cli_distribuidor_direcciones
    //         WHERE distribuidor_id = $distribuidor_id";

    //     return $this->delete($sql);
    // }

    public function insertDireccionFiscal(
        int $distribuidor_id,
        string $calle,
        string $numero_ext,
        string $numero_int,
        string $colonia,
        string $codigo_postal,
        int $pais_id,
        int $estado_id,
        int $municipio_id,
        float $latitud = null,
        float $longitud = null
    ) {
        $sql = "INSERT INTO cli_distribuidor_direcciones_fiscales
        (distribuidor_id, tipo, calle, numero_ext, numero_int, colonia, codigo_postal,
         pais_id, estado_id, municipio_id, latitud, longitud)
        VALUES (?, 'Fiscal', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $arrData = [
            $distribuidor_id,
            $calle,
            $numero_ext,
            $numero_int,
            $colonia,
            $codigo_postal,
            $pais_id,
            $estado_id,
            $municipio_id,
            $latitud,
            $longitud
        ];

        return $this->insert($sql, $arrData);
    }

    public function deleteDireccionFiscal(int $distribuidor_id)
    {
        $distribuidor_id = intval($distribuidor_id);

        $sql = "DELETE FROM cli_distribuidor_direcciones_fiscales
            WHERE distribuidor_id = $distribuidor_id";

        return $this->delete($sql);
    }

    public function selectOptionRegionales()
    {
        $sql = "SELECT * FROM  cli_regionales
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionMatrizDistribuidores()
    {
        $sql = "SELECT * FROM  cli_distribuidores
                    WHERE estado = 2  AND tipo_negocio = 'Matriz' ";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionTipoClientes()
    {
        $sql = "SELECT * FROM  cli_tipos_cliente
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionGrupos()
    {
        $sql = "SELECT * FROM  cli_grupos 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionRegimenFiscal($tipoPersona = null)
    {
        $where = "estado = 2";

        if ($tipoPersona == "1") { // Física
            $where .= " AND persona_fisica = 'Sí'";
        } elseif ($tipoPersona == "2") { // Moral
            $where .= " AND persona_moral = 'Sí'";
        }

        $sql = "SELECT * FROM cli_regimenes_fiscales WHERE $where ORDER BY c_regimen_fiscal";
        return $this->select_all($sql);
    }

    public function selectOptionModelos()
    {
        $sql = "SELECT * FROM  wms_linea_producto 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionPaises()
    {
        $sql = "SELECT * FROM  cli_paises 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectEstadosByPais(int $pais_id)
    {
        $sql = "SELECT id, nombre 
            FROM cli_estados
            WHERE pais_id = $pais_id
              AND estado = 2";
        return $this->select_all($sql);
    }

    public function selectMunicipiosByEstado(int $estado_id)
    {
        $sql = "SELECT id, nombre 
            FROM cli_municipios
            WHERE estado_id = $estado_id
              AND estado = 2";
        return $this->select_all($sql);
    }

    public function selectRegionByEstado(int $estado_id)
    {
        $sql = "
        SELECT r.id, r.nombre
        FROM cli_estados e
        INNER JOIN cli_regiones r ON r.id = e.region_id
        WHERE e.id = $estado_id
          AND e.estado = 2
        LIMIT 1
    ";

        return $this->select($sql);
    }


    /**
     * Consulta un tipo de cliente mediante su ID.
     *
     * @param int $idtipoCliente ID del tipo de cliente.
     * @return array|false Información del tipo de cliente o false.
     */
    public function selectTipoCliente(int $idtipoCliente)
    {
        $sql = "SELECT
                id,
                nombre,
                descripcion,
                estado
            FROM cli_tipos_cliente
            WHERE id = ?
              AND estado != 0
            LIMIT 1";

        return $this->select($sql, [$idtipoCliente]);
    }


    /**
     * Obtiene el último consecutivo utilizado para un tipo de cliente.
     * Si el último código registrado es CLI-DIS-0015,
     * esta consulta devuelve 15.
     *
     * @param int    $idtipoCliente ID del tipo de cliente.
     * @param string $prefijo       Prefijo correspondiente al tipo.
     * @return int Último consecutivo encontrado.
     */
    public function selectUltimoConsecutivoCliente(
        int $idtipoCliente,
        string $prefijo
    ): int {
        /*
         * LENGTH(?) permite comenzar la extracción justo después
         * del prefijo.

         * Código:  CLI-DIS-0015
         * Prefijo: CLI-DIS-
         *
         * SUBSTRING devuelve 0015 y CAST lo convierte a 15.
         */
        $sql = "SELECT
                COALESCE(
                    MAX(
                        CAST(
                            SUBSTRING(
                                codigo_cliente,
                                LENGTH(?) + 1
                            ) AS UNSIGNED
                        )
                    ),
                    0
                ) AS ultimo_consecutivo
            FROM cli_clientes
            WHERE idtipo_cliente = ?
              AND codigo_cliente LIKE CONCAT(?, '%')
              AND estado != 0";

        $resultado = $this->select(
            $sql,
            [
                $prefijo,
                $idtipoCliente,
                $prefijo
            ]
        );

        /*
         * Si no existen clientes registrados para ese tipo,
         * se devuelve cero para que el primer código sea 0001.
         */
        if (empty($resultado)) {
            return 0;
        }

        return intval(
            $resultado['ultimo_consecutivo'] ?? 0
        );
    }



    public function selectClienteBasico(int $idcliente): array|false
    {
        return $this->select("SELECT idcliente, tipo_persona, codigo_cliente, estado FROM cli_clientes WHERE idcliente = ? AND estado <> 0 LIMIT 1", [$idcliente]);
    }


    public function insertGeneral(array $d)
    {

        $sql = "INSERT INTO cli_clientes (
                idtipo_cliente,
                idregimen_fiscal,
                tipo_persona,
                codigo_cliente,
                razon_social,
                nombre_comercial,
                telefono,
                celular,
                correo,
                sitio_web,
                fecha_alta,
                estado,
                clave_distribuidor,
                zona_comercial,
                territorio,
                responsable_comercial,
                requiere_acceso_portal,
                correo_acceso,
                numero_empleado,
                departamento,
                centro_costos,
                jefe_inmediato,
                correo_corporativo,
                origen_cliente,
                ejecutivo_asignado,
                segmento_mercado,
                dependencia,
                unidad_administrativa,
                nivel_gobierno,
                partida_presupuestal,
                tipo_contratacion,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES (
                ?,  -- idtipo_cliente
                1,
                ?,  -- tipo_persona
                ?,  -- codigo_cliente
                ?,  -- razon_social
                ?,  -- nombre_comercial
                ?,  -- telefono
                ?,  -- celular
                ?,  -- correo
                ?,  -- sitio_web
                ?,  -- fecha_alta
                ?,  -- estado
                ?,  -- clave_distribuidor
                ?,  -- zona_comercial
                ?,  -- territorio
                ?,  -- responsable_comercial
                ?,  -- requiere_acceso_portal
                ?,  -- correo_acceso
                ?,  -- numero_empleado
                ?,  -- departamento
                ?,  -- centro_costos
                ?,  -- jefe_inmediato
                ?,  -- correo_corporativo
                ?,  -- origen_cliente
                ?,  -- ejecutivo_asignado
                ?,  -- segmento_mercado
                ?,  -- dependencia
                ?,  -- unidad_administrativa
                ?,  -- nivel_gobierno
                ?,  -- partida_presupuestal
                ?,  -- tipo_contratacion
                ?,  -- usuarioid
                CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '-06:00'),
                CONVERT_TZ(UTC_TIMESTAMP(), '+00:00', '-06:00')
            )";

        return $this->insert($sql, [
            $d['idtipo_cliente'],
            $d['tipo_persona'],
            $d['codigo_cliente'],
            $d['razon_social'],
            $d['nombre_comercial'],
            $d['telefono'],
            $d['celular'],
            $d['correo'],
            $d['sitio_web'],
            $d['fecha_alta'],
            $d['estado'],
            $d['clave_distribuidor'],
            $d['zona_comercial'],
            $d['territorio'],
            $d['responsable_comercial'],
            $d['requiere_acceso_portal'],
            $d['correo_acceso'],
            $d['numero_empleado'],
            $d['departamento'],
            $d['centro_costos'],
            $d['jefe_inmediato'],
            $d['correo_corporativo'],
            $d['origen_cliente'],
            $d['ejecutivo_asignado'],
            $d['segmento_mercado'],
            $d['dependencia'],
            $d['unidad_administrativa'],
            $d['nivel_gobierno'],
            $d['partida_presupuestal'],
            $d['tipo_contratacion'],
            $d['usuarioid']
        ]);
    }



    /**
     * Registra o actualiza la información fiscal de un cliente.
     *
     * Si el cliente ya cuenta con un registro fiscal activo, lo actualiza.
     * Si no existe, crea un nuevo registro con estado 2.
     *
     * @param int   $idcliente ID del cliente.
     * @param array $d         Información fiscal del cliente.
     *
     * @return bool|int
     */
    public function upsertFiscal(int $idcliente, array $d)
    {
        /*
         * Consultamos si el cliente ya tiene un registro fiscal activo.
         *
         * Parámetros:
         * 1. idcliente
         */
        $actual = $this->select(
            "SELECT idfiscal
         FROM cli_clientes_fiscal
         WHERE idcliente = $idcliente
           AND estado <> 0
         LIMIT 1"
        );

        /*
         * Si el registro fiscal ya existe, actualizamos sus datos.
         */
        if (!empty($actual)) {
            $sqlUpdate = "UPDATE cli_clientes_fiscal
                      SET
                          rfc = ?,
                          curp = ?,
                          regimen_fiscal = ?,
                          uso_cfdi = ?,
                          codigo_postal_fiscal = ?,
                          correo_facturacion = ?,
                          requiere_factura = ?,
                          usuarioid = ?,
                          fecha_actualizacion = NOW()
                      WHERE idcliente = ?";

            $arrUpdate = [
                $d['rfc'],
                $d['curp'],
                $d['regimen_fiscal'],
                $d['uso_cfdi'],
                $d['codigo_postal_fiscal'],
                $d['correo_facturacion'],
                $d['requiere_factura'],
                $d['usuarioid'],
                $idcliente
            ];

            return (bool) $this->update(
                $sqlUpdate,
                $arrUpdate
            );
        }

        /*
         * Si el registro fiscal no existe, se crea uno nuevo.
         *
         * El estado se guarda directamente con valor 2,
         * por eso no se incluye en el arreglo de parámetros.
         */
        $sqlInsert = "INSERT INTO cli_clientes_fiscal (
                      idcliente,
                      rfc,
                      curp,
                      regimen_fiscal,
                      uso_cfdi,
                      codigo_postal_fiscal,
                      correo_facturacion,
                      requiere_factura,
                      estado,
                      usuarioid,
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
                      ?,
                      ?,
                      2,
                      ?,
                      NOW(),
                      NOW()
                  )";

        $arrInsert = [
            $idcliente,
            $d['rfc'],
            $d['curp'],
            $d['regimen_fiscal'],
            $d['uso_cfdi'],
            $d['codigo_postal_fiscal'],
            $d['correo_facturacion'],
            $d['requiere_factura'],
            $d['usuarioid']
        ];

        return $this->insert(
            $sqlInsert,
            $arrInsert
        );
    }




    /**
     * Inserta un nuevo contacto para un cliente.
     *
     * @param int   $idcliente ID del cliente.
     * @param array $d         Datos del contacto.
     *
     * @return int|false
     */
    public function insertContacto(
        int $idcliente,
        array $d
    ) {

        $sql = "INSERT INTO cli_clientes_contactos (
                idcliente,
                nombre,
                puesto,
                correo,
                telefono,
                tipo,
                notificar,
                usuarioid,
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
                ?,
                ?,
                2,
                NOW(),
                NOW()
            )";

        $arrData = [
            $idcliente,
            $d['nombre'],
            $d['puesto'],
            $d['correo'],
            $d['telefono'],
            $d['tipo'],
            $d['notificar'],
            $d['usuarioid']
        ];

        return $this->insert(
            $sql,
            $arrData
        );
    }


    /**
     * Actualiza la información de un contacto perteneciente a un cliente.
     *
     * @param int   $idcontacto ID del contacto.
     * @param int   $idcliente  ID del cliente.
     * @param array $d          Datos del contacto.
     *
     * @return bool
     */
    public function updateContacto(
        int $idcontacto,
        int $idcliente,
        array $d
    ): bool {
        $sql = "UPDATE cli_clientes_contactos
            SET
                nombre = ?,
                puesto = ?,
                correo = ?,
                telefono = ?,
                tipo = ?,
                notificar = ?,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE idcontacto = ?
              AND idcliente = ?
              AND estado <> 0";

        $arrData = [
            $d['nombre'],
            $d['puesto'],
            $d['correo'],
            $d['telefono'],
            $d['tipo'],
            $d['notificar'],
            $d['usuarioid'],
            $idcontacto,
            $idcliente
        ];

        return (bool) $this->update(
            $sql,
            $arrData
        );
    }

    /**
     * Realiza la eliminación lógica de un contacto.
     *
     * El contacto no se elimina físicamente de la base de datos;
     * únicamente cambia su estado a 0.
     *
     * @param int $idcontacto ID del contacto.
     * @param int $idcliente  ID del cliente.
     * @param int $usuarioid  ID del usuario que realiza la eliminación.
     *
     * @return bool
     */
    public function deleteContacto(
        int $idcontacto,
        int $idcliente,
        int $usuarioid
    ): bool {

        $sql = "UPDATE cli_clientes_contactos
            SET
                estado = ?,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE idcontacto = ?
              AND idcliente = ?
              AND estado <> 0";

        $arrData = [
            0,
            $usuarioid,
            $idcontacto,
            $idcliente
        ];

        return (bool) $this->update(
            $sql,
            $arrData
        );
    }



    /**
     * Registra una nueva sucursal relacionada con un cliente.
     *
     * @param int   $idcliente ID del cliente propietario de la sucursal.
     * @param array $d         Datos de la sucursal.
     *
     * @return int|false ID de la sucursal registrada o false si ocurrió un error.
     */
    public function insertSucursal(
        int $idcliente,
        array $d
    ) {
        /*
         * Consulta para insertar una nueva sucursal.
         *
         * fecha_creacion y fecha_actualizacion se generan
         * automáticamente con NOW().
         */
        $sql = "INSERT INTO cli_clientes_sucursales
            (
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
                pais,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES
            (
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
                NOW(),
                NOW()
            )";

        /*
         * Los valores deben respetar exactamente el orden
         * de los signos de interrogación de la consulta.
         */
        $arrData = [
            $idcliente,
            $d['nombre_sucursal'],
            $d['responsable'],
            $d['correo'],
            $d['telefono'],
            $d['calle'],
            $d['numero_exterior'],
            $d['numero_interior'],
            $d['colonia'],
            $d['codigo_postal'],
            $d['municipio'],
            $d['estado_republica'],
            $d['pais'],
            $d['estado'],
            $d['usuarioid']
        ];

        /*
         * La función insert() debe devolver el ID autoincremental
         * generado por MySQL.
         */
        return $this->insert(
            $sql,
            $arrData
        );
    }


    /**
     * Actualiza una sucursal existente.
     *
     * La condición utiliza idsucursal e idcliente para asegurar
     * que la sucursal pertenezca al cliente indicado.
     *
     * @param int   $idsucursal ID de la sucursal.
     * @param int   $idcliente  ID del cliente.
     * @param array $d          Nuevos datos de la sucursal.
     *
     * @return bool true si la consulta se ejecutó correctamente.
     */
    public function updateSucursal(
        int $idsucursal,
        int $idcliente,
        array $d
    ) {
        /*
         * No se actualizan:
         *
         * - idcliente
         * - fecha_creacion
         *
         * fecha_actualizacion se establece automáticamente.
         */
        $sql = "UPDATE cli_clientes_sucursales
            SET
                nombre_sucursal = ?,
                responsable = ?,
                correo = ?,
                telefono = ?,
                calle = ?,
                numero_exterior = ?,
                numero_interior = ?,
                colonia = ?,
                codigo_postal = ?,
                municipio = ?,
                estado_republica = ?,
                pais = ?,
                estado = ?,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE idsucursal = ?
              AND idcliente = ?
              AND estado <> 0";

        $arrData = [
            $d['nombre_sucursal'],
            $d['responsable'],
            $d['correo'],
            $d['telefono'],
            $d['calle'],
            $d['numero_exterior'],
            $d['numero_interior'],
            $d['colonia'],
            $d['codigo_postal'],
            $d['municipio'],
            $d['estado_republica'],
            $d['pais'],
            $d['estado'],
            $d['usuarioid'],
            $idsucursal,
            $idcliente
        ];

        return (bool) $this->update(
            $sql,
            $arrData
        );
    }


    /**
     * Realiza una eliminación lógica de una sucursal.
     *
     * La sucursal no se borra físicamente de la base de datos.
     * Solamente se cambia su estado a 0.
     *
     * Estados sugeridos:
     *
     * 2 = Activa
     * 1 = Inactiva
     * 0 = Eliminada
     *
     * @param int $idsucursal ID de la sucursal.
     * @param int $idcliente  ID del cliente.
     * @param int $usuarioid  Usuario que realizó la eliminación.
     *
     * @return bool true si la consulta se ejecutó correctamente.
     */
    public function deleteSucursal(
        int $idsucursal,
        int $idcliente,
        int $usuarioid
    ) {
        $sql = "UPDATE cli_clientes_sucursales
            SET
                estado = 0,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE idsucursal = ?
              AND idcliente = ?
              AND estado <> 0";

        $arrData = [
            $usuarioid,
            $idsucursal,
            $idcliente
        ];

        return (bool) $this->update(
            $sql,
            $arrData
        );
    }



    /**
     * Registra una nueva dirección para un cliente.
     *
     * La dirección puede ser:
     *
     * - FISCAL
     * - ENTREGA
     * - COBRANZA
     * - CORRESPONDENCIA
     *
     * @param int   $idcliente ID del cliente.
     * @param array $d         Datos de la dirección.
     *
     * @return int|false ID generado o false si ocurrió un error.
     */
    // public function insertDireccion(
//     int $idcliente,
//     array $d
// ): int|false {
//     $sql = "INSERT INTO cli_direcciones
//             (
//                 idcliente,
//                 tipo_direccion,
//                 calle,
//                 numero_exterior,
//                 numero_interior,
//                 colonia,
//                 codigo_postal,
//                 municipio,
//                 estado_republica,
//                 pais,
//                 referencias,
//                 estado,
//                 usuarioid,
//                 fecha_creacion,
//                 fecha_actualizacion
//             )
//             VALUES
//             (
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 2,
//                 ?,
//                 NOW(),
//                 NOW()
//             )";

    //     $arrData = [
//         $idcliente,
//         trim($d['tipo_direccion']),
//         trim($d['calle']),
//         trim($d['numero_exterior']),
//         trim($d['numero_interior'] ?? ''),
//         trim($d['colonia']),
//         trim($d['codigo_postal']),
//         trim($d['municipio']),
//         trim($d['estado_republica']),
//         trim($d['pais'] ?? 'México'),
//         trim($d['referencias'] ?? ''),
//         intval($d['usuarioid'])
//     ];

    //     return $this->insert(
//         $sql,
//         $arrData
//     );
// }



    public function selectDireccionPorId(
        int $iddireccion,
        int $idcliente
    ) {
        $sql = "SELECT
                iddireccion,
                idcliente,
                tipo_direccion,
                calle,
                numero_exterior,
                numero_interior,
                colonia,
                codigo_postal,
                municipio,
                estado_republica,
                pais,
                referencias,
                estado
            FROM cli_direcciones
            WHERE iddireccion = $iddireccion
              AND idcliente = $idcliente
              AND estado <> 0
            LIMIT 1";

        return $this->select(
            $sql
        );
    }


    public function insertDireccion(
        int $idcliente,
        array $datos
    ) {
        $sql = "INSERT INTO cli_direcciones
            (
                idcliente,
                tipo_direccion,
                calle,
                numero_exterior,
                numero_interior,
                colonia,
                codigo_postal,
                municipio,
                estado_republica,
                pais,
                referencias,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                2, ?, NOW(), NOW()
            )";

        $arrData = [
            $idcliente,
            $datos['tipo_direccion'],
            $datos['calle'],
            $datos['numero_exterior'],
            $datos['numero_interior'],
            $datos['colonia'],
            $datos['codigo_postal'],
            $datos['municipio'],
            $datos['estado_republica'],
            $datos['pais'],
            $datos['referencias'],
            $datos['usuarioid']
        ];

        return $this->insert(
            $sql,
            $arrData
        );
    }



    /**
     * Actualiza una dirección existente.
     *
     * La condición usa iddireccion e idcliente para asegurar que
     * la dirección pertenezca al cliente.
     *
     * @param int   $iddireccion ID de la dirección.
     * @param int   $idcliente   ID del cliente.
     * @param array $d           Datos nuevos.
     *
     * @return bool
     */
    public function updateDireccion(
        int $iddireccion,
        int $idcliente,
        array $datos
    ) {
        $sql = "UPDATE cli_direcciones
            SET
                tipo_direccion = ?,
                calle = ?,
                numero_exterior = ?,
                numero_interior = ?,
                colonia = ?,
                codigo_postal = ?,
                municipio = ?,
                estado_republica = ?,
                pais = ?,
                referencias = ?,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE iddireccion = ?
              AND idcliente = ?
              AND estado <> 0";

        $arrData = [
            $datos['tipo_direccion'],
            $datos['calle'],
            $datos['numero_exterior'],
            $datos['numero_interior'],
            $datos['colonia'],
            $datos['codigo_postal'],
            $datos['municipio'],
            $datos['estado_republica'],
            $datos['pais'],
            $datos['referencias'],
            $datos['usuarioid'],
            $iddireccion,
            $idcliente
        ];

        return (bool) $this->update(
            $sql,
            $arrData
        );
    }

    public function deleteDireccion(
        int $iddireccion,
        int $idcliente,
        int $usuarioid
    ) {
        $sql = "UPDATE cli_direcciones
            SET
                estado = 0,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE iddireccion = ?
              AND idcliente = ?
              AND estado <> 0";

        return (bool) $this->update(
            $sql,
            [
                $usuarioid,
                $iddireccion,
                $idcliente
            ]
        );
    }


    /**
     * Consulta las direcciones de un cliente.
     *
     * @param int $idcliente ID del cliente.
     *
     * @return array
     */
    // public function selectDireccionesCliente(
//     int $idcliente
// ): array {
//     $sql = "SELECT
//                 iddireccion,
//                 idcliente,
//                 tipo_direccion,
//                 calle,
//                 numero_exterior,
//                 numero_interior,
//                 colonia,
//                 codigo_postal,
//                 municipio,
//                 estado_republica,
//                 pais,
//                 referencias,
//                 estado,
//                 usuarioid,
//                 fecha_creacion,
//                 fecha_actualizacion
//             FROM cli_direcciones
//             WHERE idcliente = ?
//               AND estado <> 0
//             ORDER BY
//                 tipo_direccion ASC,
//                 iddireccion DESC";

    //     $resultado = $this->select_all(
//         $sql,
//         [$idcliente]
//     );

    //     return is_array($resultado)
//         ? $resultado
//         : [];
// }



    /**
     * Inserta o actualiza la información comercial de un cliente.
     *
     * Solo se conserva un registro comercial activo por cliente.
     *
     * Si ya existe:
     * - actualiza.
     *
     * Si no existe:
     * - inserta.
     *
     * @param int   $idcliente ID del cliente.
     * @param array $d         Datos comerciales.
     *
     * @return bool
     */
    public function upsertComercial(
        int $idcliente,
        array $d
    ) {
        /*
         * Primero verificamos si ya existe un registro comercial
         * activo para el cliente.
         */
        $actual = $this->select(
            "SELECT
            idcomercial
         FROM cli_clientes_comercial
         WHERE idcliente = ?
           AND estado <> 0
         LIMIT 1",
            [$idcliente]
        );

        /*
         * Valores normalizados.
         */
        $listaPrecio = trim(
            $d['lista_precio'] ?? ''
        );

        $moneda = trim(
            $d['moneda'] ?? 'MXN'
        );

        $formaPago = trim(
            $d['forma_pago'] ?? ''
        );

        $limiteCredito = floatval(
            $d['limite_credito'] ?? 0
        );

        $diasCredito = intval(
            $d['dias_credito'] ?? 0
        );

        $descuentoAutorizado = floatval(
            $d['descuento_autorizado'] ?? 0
        );

        $ejecutivoCuenta = trim(
            $d['ejecutivo_asignado'] ?? ''
        );

        $canalVenta = trim(
            $d['canal_venta'] ?? ''
        );

        $clasificacionComercial = trim(
            $d['clasificacion_comercial'] ?? ''
        );

        $observaciones = trim(
            $d['observaciones_comerciales'] ?? ''
        );

        $usuarioid = intval(
            $d['usuarioid']
        );

        /*
         * Si existe, actualizamos el registro.
         */
        if (!empty($actual)) {
            $sql = "UPDATE cli_clientes_comercial
                SET
                    lista_precio = ?,
                    moneda = ?,
                    forma_pago = ?,
                    limite_credito = ?,
                    dias_credito = ?,
                    descuento_autorizado = ?,
                    ejecutivo_cuenta = ?,
                    canal_venta = ?,
                    clasificacion_comercial = ?,
                    observaciones_comerciales = ?,
                    usuarioid = ?,
                    fecha_actualizacion = NOW()
                WHERE idcomercial = ?
                  AND idcliente = ?
                  AND estado <> 0";

            $arrData = [
                $listaPrecio,
                $moneda,
                $formaPago,
                $limiteCredito,
                $diasCredito,
                $descuentoAutorizado,
                $ejecutivoCuenta,
                $canalVenta,
                $clasificacionComercial,
                $observaciones,
                $usuarioid,
                intval($actual['idcomercial']),
                $idcliente
            ];

            return (bool) $this->update(
                $sql,
                $arrData
            );
        }

        /*
         * Si no existe, insertamos un nuevo registro comercial.
         */
        $sql = "INSERT INTO cli_clientes_comercial
            (
                idcliente,
                lista_precio,
                moneda,
                forma_pago,
                limite_credito,
                dias_credito,
                descuento_autorizado,
                ejecutivo_cuenta,
                canal_venta,
                clasificacion_comercial,
                observaciones_comerciales,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES
            (
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
                2,
                ?,
                NOW(),
                NOW()
            )";

        $arrData = [
            $idcliente,
            $listaPrecio,
            $moneda,
            $formaPago,
            $limiteCredito,
            $diasCredito,
            $descuentoAutorizado,
            $ejecutivoCuenta,
            $canalVenta,
            $clasificacionComercial,
            $observaciones,
            $usuarioid
        ];

        return (bool) $this->insert(
            $sql,
            $arrData
        );
    }







    public function selectBancoPorId(
        int $idbanco,
        int $idcliente
    ) {
        $sql = "SELECT
                idbanco,
                idcliente,
                banco,
                titular_cuenta,
                numero_cuenta,
                clabe,
                moneda_cuenta,
                referencia_bancaria,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_clientes_bancos
            WHERE idbanco = $idbanco
              AND idcliente = $idcliente
              AND estado <> 0
            LIMIT 1";

        return $this->select(
            $sql
        );
    }

    public function insertBanco(
        int $idcliente,
        array $d
    ) {
        $sql = "INSERT INTO cli_clientes_bancos
            (
                idcliente,
                banco,
                titular_cuenta,
                numero_cuenta,
                clabe,
                moneda_cuenta,
                referencia_bancaria,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            )
            VALUES
            (
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                NOW(),
                NOW()
            )";

        $arrData = [
            $idcliente,
            $d['banco'],
            $d['titular_cuenta'],
            $d['numero_cuenta'],
            $d['clabe'],
            $d['moneda_cuenta'],
            $d['referencia_bancaria'],
            $d['estado'],
            $d['usuarioid']
        ];

        // dep($d);

        return $this->insert(
            $sql,
            $arrData
        );
    }

    public function updateBanco(
        int $idbanco,
        int $idcliente,
        array $d
    ) {
        $sql = "UPDATE cli_clientes_bancos
            SET
                banco = ?,
                titular_cuenta = ?,
                numero_cuenta = ?,
                clabe = ?,
                moneda_cuenta = ?,
                referencia_bancaria = ?,
                estado = ?,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE idbanco = ?
              AND idcliente = ?
              AND estado <> 0";

        $arrData = [
            $d['banco'],
            $d['titular_cuenta'],
            $d['numero_cuenta'],
            $d['clabe'],
            $d['moneda_cuenta'],
            $d['referencia_bancaria'],
            $d['estado'],
            $d['usuarioid'],
            $idbanco,
            $idcliente
        ];

        return (bool) $this->update(
            $sql,
            $arrData
        );
    }

    public function deleteBanco(
        int $idbanco,
        int $idcliente,
        int $usuarioid
    ) {
        $sql = "UPDATE cli_clientes_bancos
            SET
                estado = 0,
                usuarioid = ?,
                fecha_actualizacion = NOW()
            WHERE idbanco = ?
              AND idcliente = ?
              AND estado <> 0";

        return (bool) $this->update(
            $sql,
            [
                $usuarioid,
                $idbanco,
                $idcliente
            ]
        );
    }


    /**
     * Registra un documento asociado a un cliente.
     *
     * @param int   $idcliente ID del cliente.
     * @param array $d         Información del archivo cargado.
     *
     * @return int|false ID del documento registrado o false si ocurre un error.
     */
    public function insertDocumento(
        int $idcliente,
        array $d
    ) {

        $sql = "INSERT INTO cli_clientes_documentos
        (
            idcliente,
            tipo_documento,
            nombre_original,
            nombre_archivo,
            ruta_archivo,
            mime_type,
            tamano_bytes,
            estado,
            usuarioid,
            fecha_creacion,
            fecha_actualizacion
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            2,
            ?,
            NOW(),
            NOW()
        )
    ";

        $arrData = [
            $idcliente,
            $d['tipo_documento'],
            $d['nombre_original'],
            $d['nombre_archivo'],
            $d['ruta_archivo'],
            $d['mime_type'],
            $d['tamano_bytes'],
            $d['usuarioid']
        ];

        return $this->insert(
            $sql,
            $arrData
        );
    }




    public function selectClienteById(int $idcliente)
    {
        $sql = "SELECT
                idcliente,
                idtipo_cliente,
                codigo_cliente,
                tipo_persona,
                razon_social,
                nombre_comercial,
                estado
            FROM cli_clientes
            WHERE idcliente = $idcliente
              AND estado <> 0
            LIMIT 1";

        return $this->select($sql);
    }



    public function selectGeneralCliente(int $idcliente)
    {
        $sql = "SELECT *
            FROM cli_clientes c
            WHERE c.idcliente = $idcliente
              AND c.estado <> 0
            LIMIT 1";

        return $this->select($sql);
    }

    public function selectFiscalCliente(int $idcliente)
    {
        $sql = "SELECT
                idfiscal,
                idcliente,
                rfc,
                curp,
                regimen_fiscal,
                uso_cfdi,
                codigo_postal_fiscal 
                AS codigo_postal_fiscal,
                correo_facturacion,
                requiere_factura,
                estado,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_clientes_fiscal
            WHERE idcliente = $idcliente
              AND estado <> 0
            LIMIT 1";

        return $this->select($sql);
    }

    public function selectContactosCliente(int $idcliente)
    {
        $sql = "SELECT
                idcontacto,
                idcliente,
                nombre,
                puesto,
                correo,
                telefono,
                tipo,
                notificar,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_clientes_contactos
            WHERE idcliente = $idcliente
              AND estado <> 0
            ORDER BY idcontacto DESC";

        return $this->select_all($sql);
    }

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
                pais,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_clientes_sucursales
            WHERE idcliente = ?
              AND estado <> 0
            ORDER BY idsucursal DESC";

        return $this->select_all($sql, [$idcliente]);
    }

    public function selectDireccionesCliente(int $idcliente)
    {
        $sql = "SELECT
                iddireccion,
                idcliente,
                tipo_direccion,
                calle,
                numero_exterior,
                numero_interior,
                colonia,
                codigo_postal,
                municipio,
                estado_republica,
                pais,
                referencias,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_direcciones
            WHERE idcliente = $idcliente
              AND estado <> 0
            ORDER BY
                tipo_direccion ASC,
                iddireccion DESC";

        return $this->select_all($sql);
    }

    public function selectComercialCliente(int $idcliente)
    {
        $sql = "SELECT
                idcliente,
                lista_precio,
                moneda,
                forma_pago,
                limite_credito,
                dias_credito,
                descuento_autorizado,
                ejecutivo_cuenta,
                canal_venta,
                clasificacion_comercial,
                observaciones_comerciales
            FROM cli_clientes_comercial
            WHERE idcliente = $idcliente
              AND estado <> 0
            LIMIT 1";

        return $this->select($sql);
    }


    public function selectBancosCliente(int $idcliente)
    {
        $sql = "SELECT
                idbanco,
                idcliente,
                banco,
                titular_cuenta,
                numero_cuenta,
                clabe,
                moneda_cuenta,
                referencia_bancaria,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_clientes_bancos
            WHERE idcliente = $idcliente
              AND estado <> 0
            ORDER BY idbanco DESC";

        return $this->select_all($sql);
    }


    public function selectDocumentosCliente(int $idcliente)
    {
        $sql = "SELECT
                iddocumento,
                idcliente,
                tipo_documento,
                nombre_original,
                nombre_archivo,
                ruta_archivo,
                mime_type,
                tamano_bytes,
                estado,
                usuarioid,
                fecha_creacion,
                fecha_actualizacion
            FROM cli_clientes_documentos
            WHERE idcliente = $idcliente
              AND estado <> 0
            ORDER BY iddocumento DESC";

        return $this->select_all($sql);
    }


}
