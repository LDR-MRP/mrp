<?php
class Inv_lineasdproducto extends Controllers{
    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MILPRODUCTO);
    }

    public function Inv_lineasdproducto()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Líneas de producto";
			$data['page_title'] = "Líneas de producto";
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_inv_lineasdproducto.js";
			$this->views->getView($this,"inv_lineasdproducto",$data);
    }

    //CAPTURAR UNA NUEVA linea producto 
    public function setLineaProducto()
    {
        if ($_POST) {
            if (
                empty($_POST['clave-linea-producto-input'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
            } else {

                $intIdLineaProducto = intval($_POST['idlineaproducto']);
                $cve_linea_producto = strClean($_POST['clave-linea-producto-input']);
				$descripcion = strClean($_POST['descripcion-linea-producto-textarea']);
                $estado = intval($_POST['estado-select']); 

                if ($intIdLineaProducto == 0) {
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_linea_producto = $this->model->inserLineaProducto($cve_linea_producto, $descripcion, $fecha_creacion, $estado);
                        $option = 1;
                    }

                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_linea_producto = $this->model->updateLineaProducto($intIdLineaProducto, $cve_linea_producto, $descripcion, $estado);
                        $option = 2;
                    }
                }
                if ($request_linea_producto > 0) {
                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');

                    }
                    else{
                    	$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }
                } else if ($request_linea_producto == 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! ya existe.');
                } else {
                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    } 
 
    public function getLineasProductos()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectLineasProductos();
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

                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver linea producto" onClick="fntViewLineaProducto(' . $arrData[$i]['idlineaproducto'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

                }
                if ($_SESSION['permisosMod']['u']) {

                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar linea producto" onClick="fntEditLineaProducto(' . $arrData[$i]['idlineaproducto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

                }
                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar linea producto" onClick="fntDelInfo(' . $arrData[$i]['idlineaproducto'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';

                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function getLineaProducto($idlineaproducto)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intIdLineaProducto = intval($idlineaproducto);
            if ($intIdLineaProducto > 0) {
                $arrData = $this->model->selectLineaProducto($intIdLineaProducto);
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

    public function delLineaProducto()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $intIdLineaProducto = intval($_POST['idlineaproducto']);
                $requestDelete = $this->model->deleteLineaProducto($intIdLineaProducto);
                
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


    		public function getSelectLineasProductos(){
		$htmlOptions = '<option value="" selected>--Seleccione--</option>';
			$arrData = $this->model->selectOptionLineasProductos();
			if(count($arrData) > 0 ){ 
				for ($i=0; $i < count($arrData); $i++) { 
					if($arrData[$i]['estado'] == 2 ){
					$htmlOptions .= '<option value="'.$arrData[$i]['idlineaproducto'].'">'.$arrData[$i]['cve_linea_producto'].'</option>';
					}
				}
			}
			echo $htmlOptions;
			die();	
		}


}


?>