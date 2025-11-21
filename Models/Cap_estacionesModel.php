<?php 

	class Cap_estacionesModel extends Mysql
	{


		public function __construct()
		{
			parent::__construct();
		}

		
	public function generarClave()
	{
		$fecha = date('Ymd'); // 20250606
		$prefijo = '100-VG' . $fecha . '-';

		$sql = "SELECT codigo_solicitud FROM viaticos_generales 
            WHERE codigo_solicitud LIKE '$prefijo%' 
            ORDER BY codigo_solicitud DESC 
            LIMIT 1";

		$result = $this->select($sql);
		$numero = 1;

		if (!empty($result)) {
			$ultimoCodigo = $result['codigo_solicitud'];
			$ultimoNumero = (int)substr($ultimoCodigo, -4);
			$numero = $ultimoNumero + 1;
		}

		return $prefijo . str_pad($numero, 4, '0', STR_PAD_LEFT); // VIA-20250606-0004

	}




	}
 ?>