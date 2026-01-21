<?php
class Inv_moneda extends Controllers{
    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIMONEDAS);
    }

    public function Inv_moneda()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
            $data['page_tag'] = "Monedas";
			$data['page_title'] = "Monedas";
			$data['page_name'] = "monedas";
			$data['page_functions_js'] = "functions_inv_moneda.js";
			$this->views->getView($this,"inv_moneda",$data);
    }

    //CAPTURAR UNA NUEVA MONEDA 
    public function setMoneda()
    {
        if ($_POST) {
            if (
                empty($_POST['clave-moneda-input'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intidmoneda = intval($_POST['idmoneda']);
                $cve_moneda = strClean($_POST['clave-moneda-input']);
                $cambio_moneda = strClean($_POST['cambio-moneda-input']);
                $simbolo = strClean($_POST['simbolo-moneda-input']);
				$descripcion = strClean($_POST['descripcion-moneda-textarea']);
                $estado = intval($_POST['estado-select']); 

                if ($intidmoneda == 0) {
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_moneda = $this->model->inserMoneda($cve_moneda, $descripcion, $simbolo, $cambio_moneda, $fecha_creacion, $estado);
                        $option = 1;
                    }

                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_moneda = $this->model->updateMoneda($intidmoneda, $cve_moneda, $descripcion, $simbolo, $cambio_moneda, $estado);
                        $option = 2;
                    }
                }
                if ($request_moneda > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');

                    }
                    else{
                    	$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_moneda == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    } 
 
    public function getMonedas()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectMonedas();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnView = '';
                $btnEdit = '';
                $btnDelete = '';

                if ($arrData[$i]['estado'] == 2) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
                } else if($arrData[$i]['estado'] == 1) {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
                }

                if ($_SESSION['permisosMod']['r']) {

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver moneda" onClick="fntViewMoneda(' . $arrData[$i]['idmoneda'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar moneda" onClick="fntEditMoneda(' . $arrData[$i]['idmoneda'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar moneda" onClick="fntDelInfo(' . $arrData[$i]['idmoneda'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';

                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getMoneda($idmoneda)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intidmoneda = intval($idmoneda);
            if ($intidmoneda > 0) {
                $arrData = $this->model->selectMoneda($intidmoneda);
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

    public function delMoneda()
{
    if($_POST){
        if ($_SESSION['permisosMod']['d']) {
        $idmoneda = intval($_POST['idmoneda']);
        $request = $this->model->deleteMoneda($idmoneda);

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



    		public function getSelectMonedas(){
		$htmlOptions = '<option value="" selected>--Seleccione--</option>';
			$arrData = $this->model->selectOptionPrecios();
			if(count($arrData) > 0 ){ 
				for ($i=0; $i < count($arrData); $i++) { 
					if($arrData[$i]['estado'] == 2 ){
					$htmlOptions .= '<option value="'.$arrData[$i]['idmoneda'].'">'.$arrData[$i]['cve_moneda'].'</option>';
					}
				}
			}
			echo $htmlOptions;
			die();	
		}


}


?>