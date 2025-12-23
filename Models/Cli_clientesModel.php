<?php

class Cli_clientesModel extends Mysql
{
	public $intIdcliente;

	public function __construct()
	{
		parent::__construct();
	}

	public function selectClientes()
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

	public function selectCliente(int $idcliente)
	{
		$this->intIdcliente = $idcliente;

		$sql = "SELECT 
                c.id,
                c.grupo_id,
                g.nombre AS nombre_grupo,
                c.nombre_comercial,
                c.razon_social,
                c.rfc,
                c.repve,
                c.plaza,
                c.tipo_negocio,
                c.telefono,
                c.telefono_alt,
            
				d.tipo,
				d.calle,
				d.numero_ext,
				d.numero_int,
				d.colonia,
				d.codigo_postal,
				d.pais_id,
				p.nombre AS pais,
				d.estado_id,
				e.nombre AS estado_id,
				d.municipio_id,
				m.nombre AS municipio,
				d.latitud AS latitud_direccion,
				d.longitud AS longitud_direccion,

				c.fecha_registro,
                c.estado
				FROM cli_distribuidores c
				INNER JOIN cli_grupos g 
    				ON c.grupo_id = g.id
				LEFT JOIN cli_distribuidor_direcciones d 
    				ON d.distribuidor_id = c.id
				LEFT JOIN cli_paises p 
    				ON p.id = d.pais_id
				LEFT JOIN cli_estados e 
    				ON e.id = d.estado_id
				LEFT JOIN cli_municipios m 
    				ON m.id = d.municipio_id
				WHERE c.id = {$this->intIdcliente}
  				AND c.estado != 0";

		$request = $this->select($sql);
		return $request;
	}

	public function deleteCliente(int $idcliente)
	{
		$this->intIdcliente = $idcliente;
		$sql = "UPDATE cli_distribuidores SET estado = ? WHERE id = $this->intIdcliente ";
		$arrData = array(0);
		$request = $this->update($sql, $arrData);
		return $request;
	}

	public function selectOptionGrupos()
	{
		$sql = "SELECT * FROM  cli_grupos 
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
