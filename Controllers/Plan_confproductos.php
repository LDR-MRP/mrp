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
				if ($arrData[$i]['estado'] == 2) {
				$htmlOptions .= '<option value="' . $arrData[$i]['idinventario'] . '">' . $arrData[$i]['cve_articulo'] . '</option>';
				}
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
				$htmlOptions .= '<option value="' . $arrData[$i]['idlineaproducto'] . '">' . $arrData[$i]['cve_linea_producto'] . ' - ' . $arrData[$i]['descripcion'] . '</option>';
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
						$arrResponse = array('status' => true, 'msg' => '¡La información se ha registrado exitosamente!', 'tipo' => 'insert', 'idproducto' => $request_producto, 'clave'=>$claveUnica, 'descripcion'=>$descripcion);
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
						$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update', 'idproducto' => $request_producto, 'clave'=>$claveUnica, 'descripcion'=>$descripcion);
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
				// $btnViewPendiente = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver Producto" ><i class="ri-eye-fill align-bottom text-muted"></i></button>';

				$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Producto" onClick="fntEditProducto(' . $arrData[$i]['idproducto'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
				$btnReporte = '<button class="btn btn-sm btn-soft-danger edit-file" title="Generar reporte" onClick="fntReportProducto(' . $arrData[$i]['idproducto'] . ')"><i class="ri-file-text-line me-1"></i></button>';
 

 
				// $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
				$arrData[$i]['options'] = '<div class="text-center">' . $btnReporte .' '  . $btnEdit . '</div>';
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

			$arrData[$i]['documento'] = '<a href="' . media() . '/uploads/doc_componentes/' . $arrData[$i]['ruta'] . '" class="btn btn-sm btn-soft-success btn-sm" title="Ver documento" target="_blank"><i class="ri-file-text-fill align-bottom"></i></a>';

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
	// ---------------------------------------------------- ----------------
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


public function setRutaProducto()
{
    header('Content-Type: application/json');

    // 1) valida que venga ruta
    if (!isset($_POST['ruta'])) {
        echo json_encode(['status' => false, 'msg' => 'No se recibió ruta'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $arr = json_decode($_POST['ruta'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($arr) || empty($arr)) {
        echo json_encode(['status' => false, 'msg' => 'Payload inválido'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = $arr[0];

 
    $idruta = isset($_POST['id_ruta_producto']) ? (int)$_POST['id_ruta_producto'] : 0;

    $planta  = (int)($data['listPlantasSelect'] ?? 0);
    $linea   = (int)($data['listLineasSelect'] ?? 0);
    $prod    = (int)($data['idproducto_proceso'] ?? 0);
    $detalle = $data['detalle_ruta'] ?? [];

    if (!$planta || !$linea || !$prod || !is_array($detalle)) {
        echo json_encode(['status' => false, 'msg' => 'Datos incompletos'], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $tieneActivas = false;
    // foreach ($detalle as $row) {
    //     if ((int)($row['orden'] ?? 0) > 0) { $tieneActivas = true; break; }
    // }
    // if (!$tieneActivas) {
    //     echo json_encode(['status' => false, 'msg' => 'La ruta debe tener al menos 1 estación activa.'], JSON_UNESCAPED_UNICODE);
    //     exit;
    // }

    $now = date('Y-m-d H:i:s');

	$idusuario   = $_SESSION['userData']['idusuario'] ?? 0;
    $fechaEvento = date('Y-m-d H:i:s');
    $ip          = $_SERVER['REMOTE_ADDR'] ?? '';
	$detalleAudit = $_SERVER['HTTP_USER_AGENT'] ?? '';

    try {

        if ($idruta <= 0) {

            $idruta = $this->model->insertRuta($prod, $planta, $linea, $now);
            if (!$idruta) {
                echo json_encode(['status' => false, 'msg' => 'No se pudo generar la ruta'], JSON_UNESCAPED_UNICODE);
                exit;
            }

			
$this->model->insertAuditoria(
    MPCONFPRODUCTOS,
    1, // INSERT
    $idusuario,
    'mrp_producto_ruta',
    $idruta,
    $fechaEvento,
    $ip,
    $detalleAudit
);

            foreach ($detalle as $row) {
                $idestacion = (int)($row['idestacion'] ?? 0);
                $orden      = (int)($row['orden'] ?? 0);

               
                if ($idestacion > 0 && $orden > 0) {
                  $request_detalle =   $this->model->insertRutaDetalle($idruta, $idestacion, $orden, $now);


					$this->model->insertAuditoria(
    MPCONFPRODUCTOS,
    1, // INSERT
    $idusuario,
    'mrp_producto_ruta_detalle',
    $request_detalle,
    $fechaEvento,
    $ip,
    $detalleAudit
);
                }
            }

            echo json_encode([
                'status' => true,
                'msg'    => 'Ruta registrada correctamente',
                'idruta' => $idruta,
                'tipo'   => 'insert'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // ==========================================================
        //  UPDATE (idruta>0)
        // ==========================================================

        // valida que exista y que sea del producto
        if (!$this->model->rutaExisteParaProducto($idruta, $prod)) {
            echo json_encode(['status' => false, 'msg' => 'La ruta no existe o no pertenece al producto'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // actualiza header (planta/linea)
        $this->model->updateRutaHeader($idruta, $planta, $linea);


$this->model->insertAuditoria(
    MPCONFPRODUCTOS,
    2, // UPDATE
    $idusuario,
    'mrp_producto_ruta',
    $idruta,
    $fechaEvento,
    $ip,
    $detalleAudit
);

        $idsDetalleVistos = []; 

        foreach ($detalle as $row) {

            $iddetalle  = (int)($row['iddetalle'] ?? 0);
            $idestacion = (int)($row['idestacion'] ?? 0);
            $orden      = (int)($row['orden'] ?? 0);

          
            if ($orden === 0) {
                if ($iddetalle > 0) {
                    $this->model->deleteRutaDetalleLogico($iddetalle);
                    $idsDetalleVistos[] = $iddetalle;

					   $this->model->deleteEspecificacionEstacionLogico($idestacion);
					   $this->model->deleteComponentesEstacionLogico($idestacion);
					   $this->model->deleteHerramientaEstacionLogico($idestacion);
                }
                continue;
            }

          
            if ($iddetalle > 0) {
                $this->model->updateRutaDetalle($iddetalle, $idestacion, $orden);
                $idsDetalleVistos[] = $iddetalle;
                continue;
            }

         
            if ($idestacion > 0 && $orden > 0) {
                $newId = $this->model->insertRutaDetalle($idruta, $idestacion, $orden, $now);
                if ($newId) $idsDetalleVistos[] = (int)$newId;
            }
        }

    
        $this->model->disableDetallesNoEnPayload($idruta, $idsDetalleVistos);

        $this->model->reindexOrdenRuta($idruta);

        echo json_encode([
            'status' => true,
            'msg'    => 'Ruta actualizada correctamente',
            'idruta' => $idruta,
            'tipo'   => 'update'
        ], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (\Throwable $e) {
        echo json_encode([
            'status' => false,
            'msg'    => 'Error al guardar ruta',
            'error'  => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}





	// --------------------------------------------------------------------
	// FUNCIONES PARA EL GUARDADO Y EDICION DE ESPECIFICAIONES POR ESTACION
	// --------------------------------------------------------------------

		public function setEspecificacion()
	{

	
		if ($_POST) {
			if (
				empty($_POST['idproducto_especificacion'])
				|| empty($_POST['idestacion'])
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

				$intEspecificacionid = intval($_POST['idespecificacion']);
				$intIdproducto = intval($_POST['idproducto_especificacion']);
				$intEstacionId = intval($_POST['idestacion']);
				$descripcion = strClean($_POST['txtEspecificacion']);


				if ($intEspecificacionid == 0) {

					$fecha_creacion = date('Y-m-d H:i:s');
					// $estado = 2;

					//Crear 
					// if ($_SESSION['permisosMod']['w']) {
					$request_especificacion = $this->model->insertEspecificacion($intIdproducto, $intEstacionId, $descripcion, $fecha_creacion);

					$option = 1;
					// }

				} else {
					//Actualizar
					// if ($_SESSION['permisosMod']['u']) {
					$request_especificacion = $this->model->updateEspecificacion($intEspecificacionid, $descripcion);
					$option = 2;
					// }
				}
				if ($request_especificacion > 0) {
					if ($option == 1) {
						$arrResponse = array('status' => true, 'msg' => '¡La información se ha registrado exitosamente!', 'tipo' => 'insert', 'idespecificacion' => $request_especificacion);
						$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							1,
							$idusuario,
							'mrp_estacion_especificaciones',
							$request_especificacion,
							$fechaEvento,
							$ip,
							$detalle
						);
					} else {
						$arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update', 'idespecificacion' => $intEspecificacionid);
						$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							2,
							$idusuario,
							'mrp_estacion_especificaciones',
							$request_especificacion,
							$fechaEvento,
							$ip,
							$detalle
						);
					}
				} else if ($request_especificacion == 'exist') {
					$arrResponse = array('status' => false, 'msg' => '¡Atención! La especificación ya existe.');
				} else {
					$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
				}

				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
	}



	public function getEspecificaciones($idestacion){
        // if ($_SESSION['permisosMod']['r']) {
        $intIdestacion = intval($idestacion);
        if ($intIdestacion > 0) {
            $arrData = $this->model->EspecificacionesByEstacion($intIdestacion);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {

				for ($i = 0; $i < count($arrData); $i++) {


				$btnEdit = '<button type="button" class="btn btn-sm btn-soft-warning edit-list" title="Editar especificación" onClick="fntEditEspecificacion(' . $arrData[$i]['idespecificacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

				$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar especificación" onClick="fntDelEspecificacion(' . $arrData[$i]['idespecificacion'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';



				$arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
				  }

                $arrResponse = array('status' => true, 'data' => $arrData);
            }




            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        // }
        die();

}


	public function delEspecificacion()
	{
		if ($_POST) {

							// --------------------------------------------------------------------
				//  Datos de auditoría
				// --------------------------------------------------------------------
				$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');

			$intIdEspecificacion = intval($_POST['idespecificacion']);
			$requestDelete = $this->model->deleteEspecificacion($intIdEspecificacion);
			if ($requestDelete == 'ok') {
				$arrResponse = array('status' => true, 'msg' => 'El registro ha sido eliminado correctamente.');
							$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							3,
							$idusuario,
							'mrp_estacion_especificaciones',
							$intIdEspecificacion,
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


	
		public function getEspecificacion($idespecificacion)
		{
			// if($_SESSION['permisosMod']['r']){
				$intIdespecificacion = intval($idespecificacion);
				if($intIdespecificacion > 0)
				{
					$arrData = $this->model->selectEspecificacion($intIdespecificacion);
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



			// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------
	// FUNCIONES PARA EL MODULO DE COMPONENTES
	// --------------------------------------------------------------------

	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------
	// --------------------------------------------------------------------


	public function getSelectAlmacenes()
	{

		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionAlmacenes();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idalmacen'] . '">' . $arrData[$i]['descripcion'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();

	}

		public function getSelectComponentes()
	{

		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionAlmacenes();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idalmacen'] . '">' . $arrData[$i]['descripcion'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();

	}

public function setComponentesEstacion()
{
    header('Content-Type: application/json; charset=utf-8');

					$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalleAudit = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');

    if (!isset($_POST['componentes'])) {
        echo json_encode(['status'=>false,'msg'=>'No llegó el payload componentes'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $payload = json_decode($_POST['componentes'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload) || empty($payload[0])) {
        echo json_encode(['status'=>false,'msg'=>'JSON inválido en componentes'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $d = $payload[0];

    $idAlmacen  = (int)($d['idalmacen'] ?? 0);
    $idProducto = (int)($d['idproducto'] ?? 0);
    $idEstacion = (int)($d['idestacion'] ?? 0);
    $detalle    = $d['detalle_componentes'] ?? [];

    if (!$idAlmacen || !$idProducto || !$idEstacion) {
        echo json_encode(['status'=>false,'msg'=>'Faltan datos: idalmacen/idproducto/idestacion'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($detalle)) $detalle = [];

    // normalizar inventarioids entrantes
    $incoming = [];
    foreach ($detalle as $it) {
        $inv = (int)($it['inventarioid'] ?? 0);
        $cant = (int)($it['cantidad'] ?? 0);
        if ($inv > 0 && $cant > 0) {
            $incoming[$inv] = $cant; // evita duplicados
        }
    }

    $fecha = date('Y-m-d H:i:s');


    $existentes = $this->model->selectComponentesEstacionAllEstados($idEstacion, $idProducto, $idAlmacen);
    $existMap = [];
    foreach ($existentes as $row) {
        $existMap[(int)$row['inventarioid']] = (int)$row['idcomponente'];
    }

    foreach ($incoming as $inventarioid => $cantidad) {
        if (isset($existMap[$inventarioid])) {
            $idcomponente = $existMap[$inventarioid];
            $this->model->updateComponenteEstacion($idcomponente, $cantidad, 2);
							$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							2,
							$idusuario,
							'mrp_estacion_componentes',
							$idcomponente,
							$fechaEvento,
							$ip,
							$detalleAudit
						);


        } else {
            $request_componentes= $this->model->insertComponenteEstacion($idAlmacen, $idProducto, $idEstacion, $inventarioid, $cantidad, 2, $fecha);

							$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							1,
							$idusuario,
							'mrp_estacion_componentes',
							$request_componentes,
							$fechaEvento,
							$ip,
							$detalleAudit
						);
        }
    }


    $idsIncoming = array_keys($incoming);
    $this->model->softDeleteComponentesNoIncluidos($idAlmacen, $idProducto, $idEstacion, $idsIncoming);

    echo json_encode(['status'=>true,'msg'=>'Componentes sincronizados correctamente'], JSON_UNESCAPED_UNICODE);
    exit;
}




public function getComponentesEstacion($idestacion)
{
    header('Content-Type: application/json; charset=utf-8');

    $idestacion = (int)$idestacion;
    $idproducto = isset($_GET['idproducto']) ? (int)$_GET['idproducto'] : 0;

    if ($idestacion <= 0 || $idproducto <= 0) {
        echo json_encode(['status'=>false,'msg'=>'Faltan datos: idestacion/idproducto'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rows = $this->model->selectComponentesEstacion($idestacion, $idproducto);

    if (!$rows || count($rows) === 0) {
        echo json_encode(['status'=>false,'msg'=>'Sin datos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idalmacen = (int)($rows[0]['almacenid'] ?? 0);

    echo json_encode([
        'status'   => true,
        'idalmacen'=> $idalmacen,
        'data'     => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;
}





	
	
	// --------------------------------------------------------------------
	// FUNCIÓN PARA MOSTRAR TODOS LAS HERRAMIENTAS
	// --------------------------------------------------------------------
	public function getHerramientas($idalmacen)
	{
		$idalmacen = intval($idalmacen);
		$arrData = $this->model->selectHerramientas($idalmacen);

		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();
	}


	public function getHerramientasEstacion($idestacion)
{
    header('Content-Type: application/json; charset=utf-8');

    $idestacion = (int)$idestacion;
    $idproducto = isset($_GET['idproducto']) ? (int)$_GET['idproducto'] : 0;

    if ($idestacion <= 0 || $idproducto <= 0) {
        echo json_encode(['status'=>false,'msg'=>'Faltan datos: idestacion/idproducto'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $rows = $this->model->selectHerramientasEstacion($idestacion, $idproducto);

    if (!$rows || count($rows) === 0) {
        echo json_encode(['status'=>false,'msg'=>'Sin datos'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $idalmacen = (int)($rows[0]['almacenid'] ?? 0);

    echo json_encode([
        'status'   => true,
        'idalmacen'=> $idalmacen,
        'data'     => $rows
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


public function setHerramientasEstacion()
{

    header('Content-Type: application/json; charset=utf-8');

				$idusuario = $_SESSION['userData']['idusuario'] ?? 0;
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
				$detalleHerramienta = $_SERVER['HTTP_USER_AGENT'] ?? '';
				$fechaEvento = date('Y-m-d H:i:s');

    if (!isset($_POST['herramientas'])) {
        echo json_encode(['status'=>false,'msg'=>'No llegó el payload herramientas'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $payload = json_decode($_POST['herramientas'], true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($payload) || empty($payload[0])) {
        echo json_encode(['status'=>false,'msg'=>'JSON inválido en herramientas'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $d = $payload[0];

    $idAlmacen  = (int)($d['idalmacen'] ?? 0);
    $idProducto = (int)($d['idproducto'] ?? 0);
    $idEstacion = (int)($d['idestacion'] ?? 0);
    $detalle    = $d['detalle_herramientas'] ?? [];

    if (!$idAlmacen || !$idProducto || !$idEstacion) {
        echo json_encode(['status'=>false,'msg'=>'Faltan datos: idalmacen/idproducto/idestacion'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!is_array($detalle)) $detalle = [];


    $incoming = [];
    foreach ($detalle as $it) {
        $inv = (int)($it['inventarioid'] ?? 0);
        $cant = (int)($it['cantidad'] ?? 0);
        if ($inv > 0 && $cant > 0) {
            $incoming[$inv] = $cant;
        }
    }

    $fecha = date('Y-m-d H:i:s');

 
    $existentes = $this->model->selectHerramientasEstacionAllEstados($idEstacion, $idProducto, $idAlmacen);
    $existMap = [];
    foreach ($existentes as $row) {
        $existMap[(int)$row['inventarioid']] = (int)$row['idherramienta'];
    }


    foreach ($incoming as $inventarioid => $cantidad) {
        if (isset($existMap[$inventarioid])) {
            $idcomponente = $existMap[$inventarioid];
            $this->model->updateHerramientaEstacion($idcomponente, $cantidad, 2);

							$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							2,
							$idusuario,
							'mrp_estacion_herramientas',
							$idcomponente,
							$fechaEvento,
							$ip,
							$detalleHerramienta
						);
        } else {
           $request_herramienta= $this->model->insertHerramientaEstacion($idAlmacen, $idProducto, $idEstacion, $inventarioid, $cantidad, 2, $fecha);

							$this->model->insertAuditoria(
							MPCONFPRODUCTOS,
							1,
							$idusuario,
							'mrp_estacion_herramientas',
							$request_herramienta,
							$fechaEvento,
							$ip,
							$detalleHerramienta
						);
        }
    }


    $idsIncoming = array_keys($incoming);
    $this->model->softDeleteHerramientasNoIncluidos($idAlmacen, $idProducto, $idEstacion, $idsIncoming);

    echo json_encode(['status'=>true,'msg'=>'Componentes sincronizados correctamente'], JSON_UNESCAPED_UNICODE);
    exit;
}

		// --------------------------------------------------------------------
	// FUNCIÓN PARA MOSTRAR TODOS LOS COMPONENTES
	// --------------------------------------------------------------------
	public function getComponentes($idalmacen)
	{
		$idalmacen = intval($idalmacen);
		$arrData = $this->model->selectComponentes($idalmacen);

		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		die();
	}


		public function getSelectAlmacenesHerramientas()
	{

		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionAlmacenes();
		if (count($arrData) > 0) {
			for ($i = 0; $i < count($arrData); $i++) {
				if ($arrData[$i]['estado'] == 2) {
					$htmlOptions .= '<option value="' . $arrData[$i]['idalmacen'] . '">' . $arrData[$i]['descripcion'] . '</option>';
				}
			}
		}
		echo $htmlOptions;
		die();

	}



public function getRuta($rutaid)
{
    header('Content-Type: application/json; charset=utf-8');

    $rutaid = (int)$rutaid;

    if ($rutaid <= 0) {
        echo json_encode([
            'status' => false,
            'msg'    => 'ID de ruta inválido'
        ]);
        die();
    }

    $arrData = $this->model->selectRutaByProducto($rutaid);

    if (empty($arrData)) {
        echo json_encode([
            'status' => false,
            'msg'    => 'No se encontró la ruta'
        ]);
        die();
    }
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
}



	//FUNCIÓN PARA GENERAR EL REPORTE DEL PRODUCTO PDF

	public function getProductoReporte($idproducto)
	{ 
		// if($_SESSION['permisosMod']['r']){
		$intidproducto = intval($idproducto);
		if ($intidproducto > 0) {
			$arrData = $this->model->selectProductoReporte($intidproducto);
			if (empty($arrData)) {
				$arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
			} else {
				$arrResponse = array('status' => true, 'data' => $arrData);
			}

			// dep($arrData);
			// exit;
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}
		// }
		die();
	}




}


?>

