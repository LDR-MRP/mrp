<?php

class Cli_clientesModel extends Mysql
{
    public $intIddistribuidor;

    public function __construct()
    {
        parent::__construct();
    }

    public function selectDistribuidores()
    {
        $sql = "SELECT 
                c.id,
                p.nombre AS nombre_grupo,
                c.nombre_comercial,
                c.razon_social,
                c.rfc,
                c.repve,
                c.plaza,
                c.tipo_negocio,
                c.telefono,
                c.telefono_alt,
                c.fecha_registro,
                c.estado,
                r.nombre AS region
            FROM cli_distribuidores c
            INNER JOIN cli_grupos p ON c.grupo_id = p.id
            LEFT JOIN cli_distribuidor_direcciones d 
                ON d.distribuidor_id = c.id
            LEFT JOIN cli_estados e 
                ON e.id = d.estado_id
            LEFT JOIN cli_regiones r 
                ON r.id = e.region_id
            WHERE c.estado != 0";

        return $this->select_all($sql);
    }

    public function selectDistribuidor(int $iddistribuidor)
    {
        $this->intIddistribuidor = $iddistribuidor;

        // DATOS DEL DISTRIBUIDOR
        $sqlDistribuidor = "SELECT 
            c.id,
            c.grupo_id,
            g.nombre AS nombre_grupo,
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
            c.telefono,
            c.telefono_alt,
            c.fecha_registro,
            c.estado
        FROM cli_distribuidores c
        INNER JOIN cli_grupos g ON c.grupo_id = g.id
        WHERE c.id = {$this->intIddistribuidor}
        AND c.estado != 0";

        $distribuidor = $this->select($sqlDistribuidor);

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

        $sqlRegional = "SELECT
            r.id AS regional_id,
            r.nombre,
            r.apellido_paterno,
            r.apellido_materno
        FROM cli_regional_distribuidor crd
        INNER JOIN cli_regionales r ON r.id = crd.regional_id
        WHERE crd.distribuidor_id = {$this->intIddistribuidor}
        LIMIT 1";

        $regional = $this->select($sqlRegional);

        $distribuidor['regional'] = $regional ?: null;

        return $distribuidor;
    }

    public function insertDistribuidor(
        int $grupo_id,
        string $tipo_persona,
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
        string $telefono,
        string $telefono_alt
    ) {
        $sql = "INSERT INTO cli_distribuidores 
        (grupo_id, tipo_persona, nombre_fisica, apellido_paterno, apellido_materno, fecha_nacimiento, correo, curp, razon_social, representante_legal, domicilio_fiscal, rfc , nombre_comercial, repve, plaza, clasificacion, estatus, tipo_negocio, telefono, telefono_alt)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $arrData = [
            $grupo_id,
            $tipo_persona,
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
        string $tipo_persona,
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
        string $telefono,
        string $telefono_alt
    ) {
        $sql = "UPDATE cli_distribuidores SET
        grupo_id = ?,
        tipo_persona = ?,
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
        telefono = ?,
        telefono_alt = ?
        WHERE id = ?";

        $arrData = [
            $grupo_id,
            $tipo_persona,
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
        return $this->insert($sql, [$regional_id, $distribuidor_id]);
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

    public function selectOptionGrupos()
    {
        $sql = "SELECT * FROM  cli_grupos 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
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
