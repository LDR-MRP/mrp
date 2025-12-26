<?php
	class Inv_concepmovinventarios extends Controllers{
		public function __construct()
		{
			parent::__construct();
			session_start();
			//session_regenerate_id(true);
			if(empty($_SESSION['login']))
			{
				header('Location: '.base_url().'/login');
				die();
			} 
			getPermisos(MICONCEPTOSMOVIMIENTOS); 
		}

		public function Inv_concepmovinventarios()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Conceptos Movimientos Inventario";
			$data['page_title'] = "Conceptos Movimientos Inventario";
			$data['page_name'] = "Conceptos Movimientos Inventario";
			$data['page_functions_js'] = "functions_inv_concepmovinventarios.js";
			$this->views->getView($this,"inv_concepmovinventarios",$data);
		}

    //CAPTURAR UNA NUEVO CONCEPTO DE MOVIMIENTO 
    public function setConceptomovimiento()
{
    if ($_POST) {

        if (
            empty($_POST['clave-concepto-movimiento-input']) ||
            empty($_POST['estado-select']) ||
            empty($_POST['tipo_mov'])
        ) {
            $arrResponse = array("status" => false, "msg" => "Datos incompletos");
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }

        $id = intval($_POST['idconcepmov']);
        $clave = strClean($_POST['clave-concepto-movimiento-input']);
        $descripcion = strClean($_POST['descripcion-concepto-textarea']);
        $cpn = $_POST['asociado-select']; // C P N
        $tipo = $_POST['tipo_mov'];       // E o S
        $estado = intval($_POST['estado-select']);
        $signo = ($tipo === 'E') ? 1 : -1;

        if ($id == 0) {
            if ($_SESSION['permisosMod']['w']) {
                $request = $this->model->insertConcepto(
                    $clave,
                    $descripcion,
                    $cpn,
                    $tipo,
                    $signo,
                    $estado
                );
            }
        } else {
            if ($_SESSION['permisosMod']['u']) {
                $request = $this->model->updateConcepto(
                    $id,
                    $clave,
                    $descripcion,
                    $cpn,
                    $tipo,
                    $signo,
                    $estado
                );
            }
        }

        if ($request > 0) {
            $arrResponse = array("status" => true, "msg" => "Registro guardado correctamente");
        } elseif ($request == "exist") {
            $arrResponse = array("status" => false, "msg" => "El concepto ya existe");
        } else {
            $arrResponse = array("status" => false, "msg" => "Error al guardar");
        }

        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        die();
    }
}

 
    public function getConceptos()
{
    if ($_SESSION['permisosMod']['r']) {
        $arrData = $this->model->selectConceptos();

        for ($i=0; $i<count($arrData); $i++) {

            $arrData[$i]['estado'] =
                ($arrData[$i]['estado']==2)
                ? '<span class="badge bg-success">Activo</span>'
                : '<span class="badge bg-danger">Inactivo</span>';

            $arrData[$i]['tipo_movimiento'] =
                ($arrData[$i]['tipo_movimiento']=='E') ? 'Entrada' : 'Salida';

            $arrData[$i]['cpn'] =
                ($arrData[$i]['cpn']=='C') ? 'Cliente' :
                (($arrData[$i]['cpn']=='P') ? 'Proveedor' : 'Ninguno');

            $btns = '';
            if ($_SESSION['permisosMod']['r']) {
                $btns .= '<button class="btn btn-soft-info btn-sm" onclick="fntViewConcepto('.$arrData[$i]['idconcepmov'].')"><i class="ri-eye-fill"></i></button> ';
            }
            if ($_SESSION['permisosMod']['u']) {
                $btns .= '<button class="btn btn-soft-warning btn-sm" onclick="fntEditConcepto('.$arrData[$i]['idconcepmov'].')"><i class="ri-pencil-fill"></i></button> ';
            }
            if ($_SESSION['permisosMod']['d']) {
                $btns .= '<button class="btn btn-soft-danger btn-sm" onclick="fntDelConcepto('.$arrData[$i]['idconcepmov'].')"><i class="ri-delete-bin-fill"></i></button>';
            }

            $arrData[$i]['options'] = '<div class="text-center">'.$btns.'</div>';
        }

        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }
}



    public function getPrecio($idconcepmov)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidconcepmov = intval($idconcepmov);
            if ($intidconcepmov > 0) {
                $arrData = $this->model->selectPrecio($intidconcepmov);
                if (empty($arrData)) {
                    $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
                } else {

                    $arrResponse = array('status' => true, 'data' => $arrData);
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function delPrecio()
{
    if($_POST){
        if ($_SESSION['permisosMod']['d']) {
        $idconcepmov = intval($_POST['idconcepmov']);
        $request = $this->model->deletePrecio($idconcepmov);

        if($request){
            $arrResponse = array('status' => true, 'msg' => 'Registro eliminado correctamente');
        }else{
            $arrResponse = array('status' => false, 'msg' => 'No se pudo eliminar');
        }
        echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
    }
}
    die();
}



    		public function getSelectPrecios(){
		$htmlOptions = '<option value="" selected>--Seleccione--</option>';
			$arrData = $this->model->selectOptionPrecios();
			if(count($arrData) > 0 ){ 
				for ($i=0; $i < count($arrData); $i++) { 
					if($arrData[$i]['estado'] == 2 ){
					$htmlOptions .= '<option value="'.$arrData[$i]['idconcepmov'].'">'.$arrData[$i]['cve_concep_mov'].'</option>';
					}
				}
			}
			echo $htmlOptions;
			die();	
		}


}


?>