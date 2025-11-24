<?php
class Cap_lineasdtrabajo extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();
		//session_regenerate_id(true);
		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MCLINEAS);
	}

	public function Cap_lineasdtrabajo()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Líneas";
		$data['page_title'] = "Líneas <small>de trabajo</small>";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "functions_cap_lineasdtrabajo.js";
		$this->views->getView($this, "cap_lineasdtrabajo", $data);
	}

    public function setLinea()
    {
        if ($_POST) {
            if (
                empty($_POST['nombre-linea-input'])
                || empty($_POST['listPlantas'])
				|| empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
            } else {

                $intIdlinea = intval($_POST['idlinea']);
                $nombre_linea = strClean($_POST['nombre-linea-input']);
				$planta = intval($_POST['listPlantas']);
                $estado = intval($_POST['estado-select']);


                if ($intIdlinea == 0) {

                    $claveUnica = $this->model->generarClave();
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_linea = $this->model->inserLinea($claveUnica, $planta, $nombre_linea, $fecha_creacion, $estado);
                        $option = 1;
                    }

                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_linea = $this->model->updateLinea($intIdlinea,$planta,$nombre_linea,$estado);
                        $option = 2;
                    }
                }
                if ($request_linea > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');

                    }
                    else{
                    	$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_linea == 'exist') {
                    $arrResponse = array('st atus' => false, 'msg' => '¡Atención! La categoría ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getLineas()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectLineas();
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

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver planta" onClick="fntViewLinea(' . $arrData[$i]['idlinea'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar planta" onClick="fntEditInfo(' . $arrData[$i]['idlinea'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar planta" onClick="fntDelInfo(' . $arrData[$i]['idlinea'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';

                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getLinea($idlinea)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intIdlinea = intval($idlinea);
            if ($intIdlinea > 0) {
                $arrData = $this->model->selectLinea($intIdlinea);
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

    public function delLinea()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $intIdlinea = intval($_POST['idlinea']);
                $requestDelete = $this->model->deleteLinea($intIdlinea);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'msg' => 'El registro fue eliminado satisfactoriamente.');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'Error al eliminar el usuario.');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }


    		public function getSelectLineas(){
			$htmlOptions = '<option value="">--Seleccione--</option>';
			$arrData = $this->model->selectOptionLineas();
			if(count($arrData) > 0 ){
				for ($i=0; $i < count($arrData); $i++) { 
					if($arrData[$i]['estado'] == 2 ){
					$htmlOptions .= '<option value="'.$arrData[$i]['idlinea'].'">'.$arrData[$i]['cve_linea']. ''.$arrData[$i]['nombre_linea'].'</option>';
					}
				}
			}
			echo $htmlOptions;
			die();	
		}





}


?>