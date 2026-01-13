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
                c.estado
            FROM cli_distribuidores c
            INNER JOIN cli_grupos p 
                ON c.grupo_id = p.id
            WHERE c.estado != 0";

        $request = $this->select_all($sql);
        return $request;
    }

    public function selectDistribuidor(int $iddistribuidor)
    {
        $this->intIddistribuidor = $iddistribuidor;

        // DATOS DEL DISTRIBUIDOR
        $sqlDistribuidor = "SELECT 
            c.id,
            c.grupo_id,
            g.nombre AS nombre_grupo,
            c.nombre_comercial,
            c.razon_social,
            c.rfc,
            c.repve,
            c.plaza,
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
            d.municipio_id,
            m.nombre AS municipio,
            d.latitud,
            d.longitud
        FROM cli_distribuidor_direcciones d
        LEFT JOIN cli_paises p ON p.id = d.pais_id
        LEFT JOIN cli_estados e ON e.id = d.estado_id
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

        return $distribuidor;
    }

    public function insertDistribuidor(
        int $grupo_id,
        string $nombre_comercial,
        string $razon_social,
        string $rfc,
        string $repve,
        string $plaza,
        string $estatus,
        string $tipo_negocio,
        string $telefono,
        string $telefono_alt
    ) {
        $sql = "INSERT INTO cli_distribuidores 
        (grupo_id, nombre_comercial, razon_social, rfc, repve, plaza, estatus, tipo_negocio, telefono, telefono_alt)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";

        $arrData = [
            $grupo_id,
            $nombre_comercial,
            $razon_social,
            $rfc,
            $repve,
            $plaza,
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
        string $nombre_comercial,
        string $razon_social,
        string $rfc,
        string $repve,
        string $plaza,
        string $estatus,
        string $tipo_negocio,
        string $telefono,
        string $telefono_alt
    ) {
        $sql = "UPDATE cli_distribuidores SET
        grupo_id = ?,
        nombre_comercial = ?,
        razon_social = ?,
        rfc = ?,
        repve = ?,
        plaza = ?,
        estatus = ?,
        tipo_negocio = ?,
        telefono = ?,
        telefono_alt = ?
        WHERE id = ?";

        $arrData = [
            $grupo_id,
            $nombre_comercial,
            $razon_social,
            $rfc,
            $repve,
            $plaza,
            $estatus,
            $tipo_negocio,
            $telefono,
            $telefono_alt,
            $id
        ];

        return $this->update($sql, $arrData);
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
}
