<?php

class Inv_esquemaimpuestosModel extends Mysql
{
	public $intidimpuesto;
	public $strClave;
	public $strCveImpuesto;
	public $strFecha;
	public $intEstatus;
	public $strDescripcion;
	public $intImpuesto;


	public function __construct()
	{
		parent::__construct();
	}





	public function inserImpuesto(
    string $cve_impuesto,
    string $descripcion,
    array $impuestos,
    array $aplica,
    string $fecha_creacion,
    int $status
){
    $sql = "INSERT INTO wms_impuestos (
        cve_impuesto, descripcion,
        impuesto1, imp1_aplica,
        impuesto2, imp2_aplica,
        impuesto3, imp3_aplica,
        impuesto4, imp4_aplica,
        impuesto5, imp5_aplica,
        impuesto6, imp6_aplica,
        impuesto7, imp7_aplica,
        impuesto8, imp8_aplica,
        fecha_creacion,
        estado
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $arrData = [
        $cve_impuesto,
        $descripcion,
        $impuestos[1] ?? 0, $aplica[1] ?? 6,
        $impuestos[2] ?? 0, $aplica[2] ?? 6,
        $impuestos[3] ?? 0, $aplica[3] ?? 6,
        $impuestos[4] ?? 0, $aplica[4] ?? 6,
        $impuestos[5] ?? 0, $aplica[5] ?? 6,
        $impuestos[6] ?? 0, $aplica[6] ?? 6,
        $impuestos[7] ?? 0, $aplica[7] ?? 6,
        $impuestos[8] ?? 0, $aplica[8] ?? 6,
		$fecha_creacion,
        $status
    ];

    return $this->insert($sql, $arrData);
}




	public function selectImpuestos()
	{
		$sql = "SELECT * FROM  wms_impuestos 
					WHERE estado != 0 ";
		$request = $this->select_all($sql);
		return $request;
	}

	public function selectOptionPrecios()
	{
		$sql = "SELECT * FROM  wms_impuestos 
					WHERE estado = 2";
		$request = $this->select_all($sql);
		return $request;
	}

	public function selectImpuesto(int $idimpuesto)
	{
		$this->intidimpuesto = $idimpuesto;
		$sql = "SELECT * FROM wms_impuestos
					WHERE idimpuesto = $this->intidimpuesto";
		$request = $this->select($sql);
		return $request;
	}

	public function deleteImpuesto(int $idimpuesto)
	{
		$this->intidimpuesto = $idimpuesto;
		$sql = "UPDATE wms_impuestos SET estado = ? WHERE idimpuesto = $this->intidimpuesto ";
		$arrData = array(0);
		$request = $this->update($sql, $arrData);
		return $request;
	}


	public function updateImpuesto($id, $cve, $desc, $imp, $aplica, $status)
{
    $sql = "UPDATE wms_impuestos SET
        cve_impuesto = ?, descripcion = ?,
        impuesto1=?, imp1_aplica=?,
        impuesto2=?, imp2_aplica=?,
        impuesto3=?, imp3_aplica=?,
        impuesto4=?, imp4_aplica=?,
        impuesto5=?, imp5_aplica=?,
        impuesto6=?, imp6_aplica=?,
        impuesto7=?, imp7_aplica=?,
        impuesto8=?, imp8_aplica=?,
        estado=?
        WHERE idimpuesto = ?";

    $arrData = [
        $cve, $desc,
        $imp[1], $aplica[1],
        $imp[2], $aplica[2],
        $imp[3], $aplica[3],
        $imp[4], $aplica[4],
        $imp[5], $aplica[5],
        $imp[6], $aplica[6],
        $imp[7], $aplica[7],
        $imp[8], $aplica[8],
        $status,
        $id
    ];

    return $this->update($sql, $arrData);
}
}
