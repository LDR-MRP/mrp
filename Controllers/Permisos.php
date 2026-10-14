<?php 

	class Permisos extends Controllers{
		public function __construct()
		{
			parent::__construct();
		}

		public function getPermisosRol(int $idrol)
		{
			$rolid = intval($idrol);
			if($rolid > 0)
			{
				$arrModulos = $this->model->selectModulos();
				$arrPermisosRol = $this->model->selectPermisosRol($rolid);
				$arrRol = $this->model->getRol($rolid);
				$arrPermisos = array('r' => 0, 'w' => 0, 'u' => 0, 'd' => 0);
				$arrPermisoRol = array('idrol' => $rolid, 'rol' => $arrRol['nombrerol']);

				// Emparejar por idmodulo (antes era por posición del arreglo)
				$mapaPermisos = array();
				foreach ($arrPermisosRol as $permisoRol) {
					$mapaPermisos[$permisoRol['moduloid']] = $permisoRol;
				}
				for ($i=0; $i < count($arrModulos); $i++) {
					$arrPermisos = array('r' => 0, 'w' => 0, 'u' => 0, 'd' => 0);
					$idModulo = $arrModulos[$i]['idmodulo'];
					if (isset($mapaPermisos[$idModulo])) {
						$arrPermisos = array('r' => $mapaPermisos[$idModulo]['r'],
											 'w' => $mapaPermisos[$idModulo]['w'],
											 'u' => $mapaPermisos[$idModulo]['u'],
											 'd' => $mapaPermisos[$idModulo]['d']
											);
					}
					$arrModulos[$i]['permisos'] = $arrPermisos;
				}
				$arrPermisoRol['modulos'] = $arrModulos;
				$html = getModal("modalPermisos",$arrPermisoRol);
			}
			die();
		}

		public function setPermisos()
		{
			if($_POST)
			{
				$intIdrol = intval($_POST['idrol']);
				$modulos = $_POST['modulos'];

				$this->model->deletePermisos($intIdrol);
				foreach ($modulos as $modulo) {
					$idModulo = $modulo['idmodulo'];
					$r = empty($modulo['r']) ? 0 : 1;
					$w = empty($modulo['w']) ? 0 : 1;
					$u = empty($modulo['u']) ? 0 : 1;
					$d = empty($modulo['d']) ? 0 : 1;
					$requestPermiso = $this->model->insertPermisos($intIdrol, $idModulo, $r, $w, $u, $d);
				}
				if($requestPermiso > 0)
				{
					$arrResponse = array('status' => true, 'msg' => 'Permisos asignados correctamente.');
				}else{
					$arrResponse = array("status" => false, "msg" => 'No es posible asignar los permisos.');
				}
				echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
			}
			die();
		}

	}
 ?>