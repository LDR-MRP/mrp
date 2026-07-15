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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NULL, 0, 2, ?, NOW(), NOW())
        ";

        return $this->insert($sql, [
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
        ]);
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
                fecha_actualizacion = NOW()
            WHERE idusuario_acceso = ?
              AND idcliente = ?
        ";

        return $this->update($sql, [
            $nombreUsuario,
            $correo,
            $passwordHash,
            $urlPortal,
            $dobleAutenticacion,
            $usuarioActualiza,
            $idusuarioAcceso,
            $idcliente
        ]);
    }

    public function updateFechaEnvioAccesos(int $idusuarioAcceso)
    {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                ultimo_envio_accesos = NOW(),
                fecha_actualizacion = NOW()
            WHERE idusuario_acceso = ?
        ";

        return $this->update($sql, [$idusuarioAcceso]);
    }

    public function updateUltimoLogin(int $idusuarioAcceso)
    {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                ultimo_login = NOW(),
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                fecha_actualizacion = NOW()
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
                fecha_actualizacion = NOW()
            WHERE idusuario_acceso = ?
        ";

        return $this->update($sql, [
            $intentos,
            $bloqueadoHasta,
            $idusuarioAcceso
        ]);
    }

    public function updatePasswordDefinitiva(
        int $idusuarioAcceso,
        string $passwordHash
    ) {
        $sql = "UPDATE cli_usuarios_acceso
            SET
                password_hash = ?,
                requiere_cambio_password = 0,
                fecha_cambio_password = NOW(),
                intentos_fallidos = 0,
                bloqueado_hasta = NULL,
                fecha_actualizacion = NOW()
            WHERE idusuario_acceso = ?
        ";

        return $this->update($sql, [
            $passwordHash,
            $idusuarioAcceso
        ]);
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        return $this->insert($sql, [
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
        ]);
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
            VALUES (?, ?, NOW(), ?, 0, 5, 0, ?, ?)
        ";

        return $this->insert($sql, [
            $idusuarioAcceso,
            $codigoHash,
            $fechaExpiracion,
            $direccionIp,
            $idSesion
        ]);
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
              AND fecha_expiracion >= NOW()
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
                fecha_validacion = NOW()
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
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        return $this->insert($sql, [
            $idusuarioAcceso,
            $idcliente,
            $correo,
            $tipoEnvio,
            $asunto,
            $resultado,
            $detalle,
            $enviadoPor
        ]);
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

    public function insertDireccion(
        int $distribuidor_id,
        string $tipo,
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
        $sql = "INSERT INTO cli_distribuidor_direcciones
        (distribuidor_id, tipo, calle, numero_ext, numero_int, colonia, codigo_postal,
         pais_id, estado_id, municipio_id, latitud, longitud)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $arrData = [
            $distribuidor_id,
            $tipo,
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

    public function deleteDirecciones(int $distribuidor_id)
    {
        $distribuidor_id = intval($distribuidor_id);

        $sql = "DELETE FROM cli_distribuidor_direcciones
            WHERE distribuidor_id = $distribuidor_id";

        return $this->delete($sql);
    }

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
}
