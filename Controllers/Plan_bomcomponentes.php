<?php
class Plan_bomcomponentes extends Controllers
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
		getPermisos(MPBOMCOMPONENTES);
	}

	// --------------------------------------------------------------------
	// VISTA PRINCIPAL
	// --------------------------------------------------------------------

	public function Plan_bomcomponentes()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "BOM";
		$data['page_title'] = "BOM";
		$data['page_name'] = "bom";
		$data['page_functions_js'] = "functions_plan_bomcomponentes.js";
		$this->views->getView($this, "plan_bomcomponentes", $data);
	}


	// --------------------------------------------------------------------
	// FUNCIÓN PARA SELECCIONAR LOS PRODUCTOS DE INVENTARIO
	// --------------------------------------------------------------------
	public function getSelectProductos()
	{

		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionProductos();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				// if ($arrData[$i]['estado'] == 2) {
				$htmlOptions .= '<option value="' . $arrData[$i]['idinventario'] . '">' . $arrData[$i]['cve_art'] . '</option>';
				// }
			}
		}
		echo $htmlOptions;
		die();
	}


	// --------------------------------------------------------------------
	// FUNCIÓN PARA VER EL DETALLE DE INVENTARIO POR REGIASTRO
	// --------------------------------------------------------------------
	public function getSelectInventario($idinventario)
	{
		$arrData = $this->model->selectInventario($idinventario);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();
	}

	// --------------------------------------------------------------------
	// FUNCIÓN PARA OBTENER EL CATALOGO DE LINEAS DE PRODUCTO DEL WMS
	// --------------------------------------------------------------------

	public function getSelectLineasProductos()
	{

		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionLineasProductos();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				// if ($arrData[$i]['estado'] == 2) {
				$htmlOptions .= '<option value="' . $arrData[$i]['idlinea'] . '">' . $arrData[$i]['cve_linea'] . ' - ' . $arrData[$i]['descripcion'] . '</option>';
				// }
			}
		}
		echo $htmlOptions;
		die();
	}


	// --------------------------------------------------------------------
	// FUNCIÓN PARA EL GUARDADO DE COMPONENTES
	// --------------------------------------------------------------------


	public function setComponente()
	{
		if ($_POST) {
			if (
				empty($_POST['listProductos'])
				|| empty($_POST['txtDescripcion'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
			} else {

				$intIdcomponente = intval($_POST['idcomponente']);
				$inventarioid = intval($_POST['listProductos']);
				$lineaproductoid = intval($_POST['listLineasProductos']);
				$descripcion = strClean($_POST['txtDescripcion']);

				if ($intIdcomponente == 0) {

					$claveUnica = $this->model->generarClave();
					$fecha_creacion = date('Y-m-d H:i:s');
					$estado = 2;

					//Crear 
					// if ($_SESSION['permisosMod']['w']) {
						$request_componente = $this->model->inserComponente($claveUnica, $inventarioid, $lineaproductoid, $descripcion, $fecha_creacion, $estado);
						$option = 1;
					// }

				}
				else {
				    //Actualizar
				    // if ($_SESSION['permisosMod']['u']) {
				        $request_componente = $this->model->updateComponente($intIdcomponente, $nombre_planta, $direccion, $estado);
				        $option = 2;
				    // }
				}
				if ($request_componente > 0) {
					if ($option == 1) {
						$arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert', 'idcomponente' => $request_componente);

					}
					// else{ 
					// 	$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
					// }
				} else if ($request_componente == 'exist') {
					$arrResponse = array('status' => false, 'msg' => '¡Atención! La categoría ya existe.');
				} else {
					$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
				}

				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
	}



	// --------------------------------------------------------------------
	// FUNCIÓN PARA VER TODO EL LISTADO DE COMPONENTES
	// --------------------------------------------------------------------


	public function getComponentes()
	{
		if ($_SESSION['permisosMod']['r']) {
			$arrData = $this->model->selectComponentes();
			for ($i = 0; $i < count($arrData); $i++) {
				$btnView = '';
				$btnEdit = '';
				$btnDelete = '';

				if ($arrData[$i]['estado'] == 2) {
					$arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
				} else if ($arrData[$i]['estado'] == 1) {
					$arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
				}

				// if ($_SESSION['permisosMod']['r']) {

				// 	$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver planta" onClick="fntViewPlanta(' . $arrData[$i]['idcomponente'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

				// }
				// if ($_SESSION['permisosMod']['u']) {

				// 	$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar planta" onClick="fntEditInfo(' . $arrData[$i]['idcomponente'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

				// }
				// if ($_SESSION['permisosMod']['d']) {
				// 	$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar planta" onClick="fntDelInfo(' . $arrData[$i]['idcomponente'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';

				// }
				$btnViewPendiente = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver Componente" ><i class="ri-eye-fill align-bottom text-muted"></i></button>';

				$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Componente" onClick="fntEditComponente(' . $arrData[$i]['idcomponente'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';



				// $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
				$arrData[$i]['options'] = '<div class="text-center">' . $btnViewPendiente .' '. $btnEdit . '</div>';
			}
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	// --------------------------------------------------------------------
	// FUNCIÓN PARA ALMACENAR LA DOCUMENTACIÓN DE LOS COMPONENTES
	// --------------------------------------------------------------------


	public function setDocumentacion()
	{

		if ($_POST) {
			if (
				empty($_POST['idcomponentedoc'])
				|| empty($_POST['txtDescripcionDocumento'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
			} else {
				$intIdComponente = intval($_POST['idcomponentedoc']);
				$strDescripcion = strClean($_POST['txtDescripcionDocumento']);

				if (isset($_FILES['txtFile']) && $_FILES['txtFile']['error'] == 0) {
					$code = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5);
					$fechaHora = date('Ymd_His');
					$fecha_creacion = date('Y-m-d H:i:s');
					$extensionArchivo = pathinfo($_FILES['txtFile']['name'], PATHINFO_EXTENSION);

					$nombreDocumento = 'documento_' . $fechaHora . '_' . $code . '.' . $extensionArchivo;

					$directorio = 'Assets/uploads/doc_componentes/';
					if (!file_exists($directorio)) {
						mkdir($directorio, 0777, true);
					}

					$rutaDestino = $directorio . $nombreDocumento;
					$tmpArchivo = $_FILES['txtFile']['tmp_name'];

					if (!move_uploaded_file($tmpArchivo, $rutaDestino)) {
						$arrResponse = array("status" => false, "msg" => "Error al mover el documento.");
						echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
						return;
					}

					$insert = $this->model->insertDocumento($intIdComponente, $strDescripcion, $nombreDocumento, $fecha_creacion);

					if ($insert > 0) {
						$arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert', 'idcomponente' => $intIdComponente);
					}
				} else {
					$arrResponse = array("status" => false, "msg" => "No se recibió ningún archivo.");
				}

			}

			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

		}
	}

		// --------------------------------------------------------------------
	// FUNCIÓN PARA OBTENER LOS DOCUMENTOS POR COMPONENTE
	// --------------------------------------------------------------------

	public function getDocumentos()
	{
		
		$componenteid= intval($_POST['idcomponentedoc']);
		$arrData = $this->model->selectDocumentosByComponente($componenteid);

		for ($i = 0; $i < count($arrData); $i++) {

			$arrData[$i]['documento'] = '<a href="' . media() . '/uploads/doc_componentes/' . $arrData[$i]['ruta'] . '" class="btn btn-sm btn-soft-success btn-sm" title="Ver documento" target="_blank"><i class="bx bxs-file-pdfalign-bottom"></i></a>';

			$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar planta" onClick="fntDelDocumento(' . $arrData[$i]['iddocumento'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';


			$arrData[$i]['options'] = '<div class="text-center">' . $btnDelete . '</div>';

		}

		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();

	} 

		// --------------------------------------------------------------------
	// FUNCIÓN PARA ELIMINAR LOS DOCUMENTOS
	// --------------------------------------------------------------------

			public function delDocumento()
		{
			if($_POST){

					$intIddocumento = intval($_POST['iddocumento']);
					$requestDelete = $this->model->deleteDocumento($intIddocumento);
					if($requestDelete == 'ok')
					{
						$arrResponse = array('status' => true, 'msg' => 'El documento ha sido eliminado correctamente.');
					}else if($requestDelete == 'exist'){
						$arrResponse = array('status' => false, 'msg' => 'No es posible almacenar el documento.');
					}else{
						$arrResponse = array('status' => false, 'msg' => 'Error al eliminar la categoría.');
					}
					echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
				
			}
			die();
		}


		
		// --------------------------------------------------------------------
	// FUNCIÓN PARA EDITAR LOS COMPONENTES
	// --------------------------------------------------------------------

				public function getComponente($idcomponente)
		{
			// if($_SESSION['permisosMod']['r']){
				$intidcomponente = intval($idcomponente);
				if($intidcomponente > 0)
				{
					$arrData = $this->model->selectComponente($intidcomponente);
					if(empty($arrData))
					{
						$arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
					}else{
						$arrResponse = array('status' => true, 'data' => $arrData);
					}
					echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
				}
			// }
			die(); 
		}



}


?>