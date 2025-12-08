<?php
class Plan_confproductos extends Controllers
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
		getPermisos(MPCONFPRODUCTOS);
	}

	// --------------------------------------------------------------------
	// VISTA PRINCIPAL
	// --------------------------------------------------------------------

	public function Plan_confproductos()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
		}
		$data['page_tag'] = "Configuración de productos";
		$data['page_title'] = "Configuración de productos";
		// $data['page_name'] = "";
		$data['page_functions_js'] = "functions_plan_confproductos.js";
		$this->views->getView($this, "plan_confproductos", $data);
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
	// FUNCIÓN PARA EL GUARDADO DE PRODUCTOS
	// --------------------------------------------------------------------


	public function setProducto()
	{
		if ($_POST) {
			if (
				empty($_POST['listProductos'])
				|| empty($_POST['txtDescripcion'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos .');
			} else {

				// --------------------------------------------------------------------
				//  Datos de auditoría
				// --------------------------------------------------------------------
				$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');

				$intIdproducto = intval($_POST['idproducto']);
				$inventarioid = intval($_POST['listProductos']);
				$lineaproductoid = intval($_POST['listLineasProductos']);
				$descripcion = strClean($_POST['txtDescripcion']);
				$estado = intval($_POST['intEstado']);

				if ($intIdproducto == 0) {

					$claveUnica = $this->model->generarClave();
					$fecha_creacion = date('Y-m-d H:i:s');
					// $estado = 2;

					//Crear 
					// if ($_SESSION['permisosMod']['w']) {
					$request_producto = $this->model->inserProducto($claveUnica, $inventarioid, $lineaproductoid, $descripcion, $fecha_creacion, $estado);
					$option = 1;
					// }

				} else {
					//Actualizar
					// if ($_SESSION['permisosMod']['u']) {
					$request_producto = $this->model->updateProducto($intIdproducto, $inventarioid, $lineaproductoid, $descripcion, $estado);
					$option = 2;
					// }
				}
				if ($request_producto > 0) {
					if ($option == 1) {
						$arrResponse = array('status' => true, 'msg' => '¡La información se ha registrado exitosamente!', 'tipo' => 'insert', 'idproducto' => $request_producto);
						$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							1,
							$idusuario,
							'mrp_productos',
							$request_producto,
							$fechaEvento,
							$ip,
							$detalle
						);
					} else {
						$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update', 'idproducto' => $request_producto);
						$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							2,
							$idusuario,
							'mrp_productos',
							$request_producto,
							$fechaEvento,
							$ip,
							$detalle
						);
					}
				} else if ($request_producto == 'exist') {
					$arrResponse = array('status' => false, 'msg' => '¡Atención! La categoría ya existe.');
				} else {
					$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
				}

				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
	}



	// --------------------------------------------------------------------
	// FUNCIÓN PARA VER TODO EL LISTADO DE PRODUCTOS
	// --------------------------------------------------------------------


	public function getProductos()
	{
		if ($_SESSION['permisosMod']['r']) {
			$arrData = $this->model->selectProductos();
			for ($i = 0; $i < count($arrData); $i++) {
				$btnView = '';
				$btnEdit = '';
				$btnDelete = '';

				if ($arrData[$i]['estado_producto'] == 2) {
					$arrData[$i]['estado_producto'] = '<span class="badge bg-success">Activo</span>';
				} else if ($arrData[$i]['estado_producto'] == 1) {
					$arrData[$i]['estado_producto'] = '<span class="badge bg-danger">Inactivo</span>';
				}

				// if ($_SESSION['permisosMod']['r']) {

				// 	$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver planta" onClick="fntViewPlanta(' . $arrData[$i]['idproducto'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

				// }
				// if ($_SESSION['permisosMod']['u']) {

				// 	$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar planta" onClick="fntEditInfo(' . $arrData[$i]['idproducto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

				// }
				// if ($_SESSION['permisosMod']['d']) {
				// 	$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar planta" onClick="fntDelInfo(' . $arrData[$i]['idproducto'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';

				// }
				$btnViewPendiente = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver Producto" ><i class="ri-eye-fill align-bottom text-muted"></i></button>';

				$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idproducto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';



				// $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
				$arrData[$i]['options'] = '<div class="text-center">' . $btnViewPendiente . ' ' . $btnEdit . '</div>';
			}
			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	// --------------------------------------------------------------------
	// FUNCIÓN PARA ALMACENAR LA DOCUMENTACIÓN DE LOS PRODUCTOS
	// --------------------------------------------------------------------


	public function setDocumentacion()
	{

		if ($_POST) {
			if (
				empty($_POST['idproducto_documentacion'])
				|| empty($_POST['txtDescripcionDocumento'])
			) {
				$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
			} else {

				// --------------------------------------------------------------------
				//  Datos de auditoría
				// --------------------------------------------------------------------
				$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');


				$intIdProducto = intval($_POST['idproducto_documentacion']);
				$strDescripcion = strClean($_POST['txtDescripcionDocumento']);

				$strTipoDocumento = strClean($_POST['tipoDocumento']);

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

					$insert = $this->model->insertDocumento($intIdProducto, $strTipoDocumento, $strDescripcion, $nombreDocumento, $fecha_creacion);

					if ($insert > 0) {
						$arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente.', 'tipo' => 'insert', 'idproducto' => $intIdProducto);
						$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							1,
							$idusuario,
							'mrp_productos_documentos',
							$insert,
							$fechaEvento,
							$ip,
							$detalle
						);

					}
				} else {
					$arrResponse = array("status" => false, "msg" => "No se recibió ningún archivo.");
				}

			}

			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

		}
	}

	// --------------------------------------------------------------------
	// FUNCIÓN PARA OBTENER LOS DOCUMENTOS POR PRODUCTO
	// --------------------------------------------------------------------

	public function getDocumentos()
	{

		$productoid = intval($_POST['idproducto_documentacion']);
		$arrData = $this->model->selectDocumentosByProducto($productoid);

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
		if ($_POST) {

							// --------------------------------------------------------------------
				//  Datos de auditoría
				// --------------------------------------------------------------------
				$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');

			$intIddocumento = intval($_POST['iddocumento']);
			$requestDelete = $this->model->deleteDocumento($intIddocumento);
			if ($requestDelete == 'ok') {
				$arrResponse = array('status' => true, 'msg' => 'El documento ha sido eliminado correctamente.');
							$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							3,
							$idusuario,
							'mrp_productos_documentos',
							$intIddocumento,
							$fechaEvento,
							$ip,
							$detalle
						);
			} else if ($requestDelete == 'exist') {
				$arrResponse = array('status' => false, 'msg' => 'No es posible almacenar el documento.');
			} else {
				$arrResponse = array('status' => false, 'msg' => 'Error al eliminar la categoría.');
			}
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);

		}
		die();
	}



	// --------------------------------------------------------------------
	// FUNCIÓN PARA EDITAR LOS PRODUCTOS
	// --------------------------------------------------------------------

	public function getProducto($idproducto)
	{
		// if($_SESSION['permisosMod']['r']){
		$intidproducto = intval($idproducto);
		if ($intidproducto > 0) {
			$arrData = $this->model->selectProducto($intidproducto);
			if (empty($arrData)) {
				$arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
			} else {
				$arrResponse = array('status' => true, 'data' => $arrData);
			}
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}
		// }
		die();
	}

	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------
	// FUNCIONES PARA EL MODULO DE DESCRIPTIVA TÉCNICA
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------


	public function setDescriptiva()
	{


		if ($_POST) {

				// --------------------------------------------------------------------
				//  Datos de auditoría
				// --------------------------------------------------------------------
				$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');



				$intIdDescriptiva = intval($_POST['iddescriptiva']);
                $intProducto = intval($_POST['idproducto_descriptiva']);
				$marca = strClean($_POST['txtMarca']);
				$modelo = strClean($_POST['txtModelo']);
				$largo_total = strClean($_POST['txtLargoTotal']);
				$distancia_ejes = strClean($_POST['txtDistanciaEjes']);
				$peso_bruto_vehicular = strClean($_POST['txtPesoBruto']);
				$motor = strClean($_POST['txtMotor']);
				$cilindros = strClean($_POST['txtDesplazamientoCilindros']);
				$desplazamiento_c = strClean($_POST['txtDesplazamiento']);
				$tipo_combustible = strClean($_POST['txtTipoCombustible']);
				$potencia = strClean($_POST['txtPotencia']);
				$torque = strClean($_POST['txtTorque']);
				$transmision = strClean($_POST['txtTransmision']);
				$eje_delantero = strClean($_POST['txtEjeDelantero']);
				$suspension_delantera = strClean($_POST['txtSuspensionDelantera']);
				$eje_trasero = strClean($_POST['txtEjeTrasero']);
				$suspension_trasera = strClean($_POST['txtSuspensionTrasera']);
				$llantas = strClean($_POST['txtLlantas']);
				$sistema_frenos = strClean($_POST['txtSistemaFrenos']);
				$asistencias = strClean($_POST['txtAsistencias']);
				$sistema_electrico = strClean($_POST['txtSistemaElectrico']);
				$capacidad_combustible = strClean($_POST['txtCapacidadCombustible']);
				$direccion = strClean($_POST['txtDireccion']);
				$equipamiento = strClean($_POST['txtEquipamiento']);


				if ($intIdDescriptiva == 0) {

					$fecha_creacion = date('Y-m-d H:i:s');
					// $estado = 2;

					//Crear 
					// if ($_SESSION['permisosMod']['w']) {
					$request_descriptiva = $this->model->insertDescriptiva(
						$intProducto,
						$marca,
						$modelo,
						$largo_total,
						$distancia_ejes,
						$peso_bruto_vehicular,
						$motor,
						$cilindros,
						$desplazamiento_c,
						$tipo_combustible,
						$potencia,
						$torque,
						$transmision,
						$eje_delantero,
						$suspension_delantera,
						$eje_trasero,
						$suspension_trasera,
						$llantas,
						$sistema_frenos,
						$asistencias,
						$sistema_electrico,
						$capacidad_combustible,
						$direccion,
						$equipamiento,
						$fecha_creacion
					);
					$option = 1;

					// }

				} else {
					//Actualizar
					// if ($_SESSION['permisosMod']['u']) {
					 $request_descriptiva = $this->model->updateDescriptiva(
						$intIdDescriptiva,
						$marca,
						$modelo,
						$largo_total,
						$distancia_ejes,
						$peso_bruto_vehicular,
						$motor,
						$cilindros,
						$desplazamiento_c,
						$tipo_combustible,
						$potencia,
						$torque,
						$transmision,
						$eje_delantero,
						$suspension_delantera,
						$eje_trasero,
						$suspension_trasera,
						$llantas,
						$sistema_frenos,
						$asistencias,
						$sistema_electrico,
						$capacidad_combustible,
						$direccion,
						$equipamiento,
					 );

				$option = 2;
					// }
				}
				if ($request_descriptiva > 0) {
					if ($option == 1) {
						$arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente.', 'tipo' => 'insert', 'iddescriptiva' => $request_descriptiva);
						$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							1,
							$idusuario,
							'mrp_productos_descriptiva',
							$request_descriptiva,
							$fechaEvento,
							$ip,
							$detalle
						);
						} else {
							$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update', 'iddescriptiva' => $request_descriptiva);
						}
					} else {
						$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
					}

					echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
				
			}
		}
	
		

		// --------------------------------------------------------------------
	// FUNCIÓN PARA OBTENER LOS DOCUMENTOS POR PRODUCTO
	// --------------------------------------------------------------------

	public function getDescriptiva($productoid)
	{
		$productoid = intval($productoid);
		$arrData = $this->model->selectDescriptivaByProducto($productoid);
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();

	}


	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------
	// FUNCIONES PARA EL MODULO DE PROCESOS
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------


	public function getSelectEstaciones($idlinea)
	{
		// $htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionEstacionesByLinea($idlinea);
		// if (count($arrData) > 0) {
		//     for ($i = 0; $i < count($arrData); $i++) {
		//         if ($arrData[$i]['estado'] == 2) {
		//             $htmlOptions .= '<option value="' . $arrData[$i]['idlinea'] . '">' . $arrData[$i]['cve_linea'] . ' - ' . $arrData[$i]['nombre_linea'] . '</option>';
		//         }
		//     }
		// }
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();
	}



}


?>