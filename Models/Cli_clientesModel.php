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
                d.*, 
                g.nombre AS nombre
				
            FROM cli_distribuidores d
            LEFT JOIN cli_grupos g ON d.grupo_id = g.id
            WHERE d.estado != 0";

		return $this->select_all($sql);
	}

	public function selectCliente(int $idcliente)
	{
		$this->intIdcliente = $idcliente;
		$sql = "SELECT * FROM cli_distribuidores WHERE id = $this->intIdcliente";
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
}
