<?php
class Inv_inventario extends Controllers
{
	use ApiResponser;

	protected $inventarioService;

	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MIINVENTARIO);

		$this->inventarioService = new Inv_inventarioService;

		$this->inventarioService->model = $this->model;
	}

	public function Inv_inventario()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Inventario";
		$data['page_title'] = "Inventario";
		$data['page_name'] = "inventario";
		$data['page_functions_js'] = "functions_inv_inventario.js";
		$this->views->getView($this, "inv_inventario", $data);
	}


	public function setInventario()
	{
		if ($_POST) {

			// =========================
			// VALIDACIÓN BÁSICA
			// =========================
			if (
				empty($_POST['cve_articulo']) ||
				empty($_POST['descripcion']) ||
				empty($_POST['tipo_elemento']) ||
				empty($_POST['tipo_elemento'])
			) {
				$arrResponse = [
					'status' => false,
					'msg' => 'Datos obligatorios incompletos'
				];
				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
				die();
			}

			// =========================
			// LIMPIEZA DE DATOS
			// =========================
			$idinventario     = intval($_POST['idinventario'] ?? 0);
			$cve_articulo     = strClean($_POST['cve_articulo']);
			$descripcion      = strClean($_POST['descripcion']);
			$notas      	  = strClean($_POST['notas']);
			$tipo_elemento    = strClean($_POST['tipo_elemento']); // P S K
			$unidad_entrada   = strClean($_POST['unidad_entrada'] ?? '');
			$unidad_salida    = strClean($_POST['unidad_salida'] ?? '');
			$unidad_empaque   = strClean($_POST['unidad_empaque'] ?? '');
			$ultimo_costo     = floatval($_POST['ultimo_costo'] ?? 0);
			$ubicacion     	  = strClean($_POST['ubicacion']);
			$factor_unidades  = floatval($_POST['factor_unidades'] ?? 1);
			$tiempo_surtido   = intval($_POST['tiempo_surtido'] ?? 0);
			$serie            = strClean($_POST['serie'] ?? 'N');
			$lote             = strClean($_POST['lote'] ?? 'N');
			$pedimiento       = strClean($_POST['pedimiento'] ?? 'N');
			$peso             = floatval($_POST['peso'] ?? 0);
			$volumen          = floatval($_POST['volumen'] ?? 0);
			$clave_alterna    = strClean($_POST['clave_alterna'] ?? '');
			$tipo_asignacion  = strClean($_POST['tipo_asignacion'] ?? '');
			$almacenid        = intval($_POST['almacenid'] ?? 0);
			$cantidadInicial  = floatval($_POST['cantidad_inicial'] ?? 0);
			$costoUnitario    = floatval($_POST['costo'] ?? 0);
			$precioUnitario   = floatval($_POST['precio'] ?? 0);
			$idimpuesto       = intval($_POST['idimpuesto'] ?? 1);
			$idmarca = !empty($_POST['idmarca'])
				? intval($_POST['idmarca'])
				: null;

			// =========================
			// INSERT / UPDATE
			// =========================
			if ($idinventario == 0) {

				if ($_SESSION['permisosMod']['w']) {

					$request = $this->model->insertInventario(
						$cve_articulo,
						$descripcion,
						$notas,
						$unidad_entrada,
						$unidad_salida,
						$unidad_empaque,
						$ultimo_costo,
						$ubicacion,
						$idmarca,
						$tipo_elemento,
						$factor_unidades,
						$tiempo_surtido,
						$peso,
						$volumen,
						$serie,
						$lote,
						$pedimiento
					);
					$option = 1;

					if ($request === "exist") {
						echo json_encode([
							'status' => false,
							'msg' => 'La clave del artículo ya existe'
						], JSON_UNESCAPED_UNICODE);
						die();
					}

					// =========================
					// INSERTAR IMPUESTO
					// =========================

					if (is_numeric($request) && $request > 0) {
						$this->model->insertInventarioImpuestoform($request, $idimpuesto, 2);
					}


					// =========================
					// INSERTAR CLAVE ALTERNA
					// =========================
					if (
						$request > 0 &&                      // ID del inventario
						!empty($clave_alterna) &&
						!empty($tipo_asignacion)
					) {
						$this->model->insertClaveAlterna(
							$request,            // inventarioid
							$clave_alterna,
							$tipo_asignacion
						);
					}
				}
			} else {

				if ($_SESSION['permisosMod']['u']) {

					$estado = 2;

					$request = $this->model->updateInventario(
						$idinventario,
						$cve_articulo,
						$descripcion,
						$notas,
						$unidad_entrada,
						$unidad_salida,
						$unidad_empaque,
						$ultimo_costo,
						$ubicacion,
						$idmarca,
						$tipo_elemento,
						$factor_unidades,
						$tiempo_surtido,
						$peso,
						$volumen,
						$serie,
						$lote,
						$pedimiento,
						$estado
					);

					// AQUI VA
					if ($request && !empty($clave_alterna) && !empty($tipo_asignacion)) {

						$this->model->upsertClaveAlterna(
							$idinventario,
							$clave_alterna,
							$tipo_asignacion
						);
					}

					$option = 2;
				}
			}

			// =========================
			// 🔥 AQUÍ VA LO DE IMÁGENES
			// =========================
			$idFinal = ($option == 1) ? $request : $idinventario;

			if ($idFinal > 0) {

				if (!empty($_FILES['imagenes']['name'][0])) {

					$ruta = __DIR__ . "/../Assets/uploads/inventario_imagenes/";

					// if (!file_exists($ruta)) {
					// 	mkdir($ruta, 0777, true);
					// }

					// [DevSecOps] Permisos 0750: Solo el owner (www-data) y el grupo pueden leer/escribir.
					if (!is_dir($ruta) && !mkdir($ruta, 0750, true) && !is_dir($ruta)) {
						// Falla silenciosa o manejo de error a nivel de OS si no se puede crear el directorio
						return;
					}

					$clave = preg_replace('/[^A-Za-z0-9_\-]/', '', $cve_articulo);

					foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp) {

						if ($_FILES['imagenes']['error'][$key] === 0) {

							$ext = pathinfo($_FILES['imagenes']['name'][$key], PATHINFO_EXTENSION);
							$fecha = date("Ymd_His") . "_" . substr(microtime(), 2, 3);
							$nombre = $clave . "_" . $fecha . "." . $ext;

							$destino = $ruta . $nombre;

							if (move_uploaded_file($tmp, $destino)) {

								// 🔥 AQUI EL CAMBIO IMPORTANTE
								$this->model->insertImagenInventario($idFinal, $nombre);
							} else {
								error_log("❌ Error al mover archivo: " . $tmp);
							}
						}
					}
				}
			}


			// =========================
			// INICIALIZAR MULTIALMACÉN CON EXISTENCIA INICIAL
			// =========================
			if (
				$request > 0 &&
				$option == 1 && // SOLO ALTA NUEVA
				in_array($tipo_elemento, ['P', 'C', 'H', 'R']) &&
				$almacenid > 0 &&
				$cantidadInicial > 0 &&
				$costoUnitario > 0
			) {
				require_once 'Models/Inv_movimientosinventarioModel.php';

				$movModel = new Inv_movimientosinventarioModel();

				$movModel->insertMovimiento(
					(int)$request,          // inventarioid
					(int)$almacenid,        // almacenid
					1,                      // concepmovid = INVENTARIO INICIAL
					'Inventario inicial',
					(float)$cantidadInicial,
					(float)$costoUnitario
				);
			}



			// =========================
			// RESPUESTA
			// =========================
			if ($request === "exist") {

				$arrResponse = [
					'status' => false,
					'msg' => 'La clave del artículo ya existe'
				];
			} elseif (
				($option == 1 && is_numeric($request) && $request > 0) ||  // INSERT
				($option == 2 && $request)                                 // UPDATE
			) {

				$arrResponse = [
					'status' => true,
					'msg' => ($option == 1)
						? 'Inventario registrado correctamente'
						: 'Inventario actualizado correctamente',
					'tipo' => $tipo_elemento,
					'id'   => ($option == 1) ? $request : $idinventario
				];
			} else {

				$arrResponse = [
					'status' => false,
					'msg' => 'No fue posible guardar la información'
				];
			}

			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}
		die();
	}


	public function getInventarios()
	{
		if ($_SESSION['permisosMod']['r']) {

			$arrData = $this->model->selectInventarios();

			for ($i = 0; $i < count($arrData); $i++) {

				// Estado
				$arrData[$i]['estado'] = ($arrData[$i]['estado'] == 2)
					? '<span class="badge bg-success">Activo</span>'
					: '<span class="badge bg-danger">Inactivo</span>';

				$tipoRaw = $arrData[$i]['tipo_elemento'];

				// Tipo
				if ($arrData[$i]['tipo_elemento'] == 'P') $arrData[$i]['tipo_elemento'] = 'Producto';
				if ($arrData[$i]['tipo_elemento'] == 'S') $arrData[$i]['tipo_elemento'] = 'Servicio';
				if ($arrData[$i]['tipo_elemento'] == 'K') $arrData[$i]['tipo_elemento'] = 'Kit';
				if ($arrData[$i]['tipo_elemento'] == 'C') $arrData[$i]['tipo_elemento'] = 'Componente';
				if ($arrData[$i]['tipo_elemento'] == 'H') $arrData[$i]['tipo_elemento'] = 'Herramienta';
				if ($arrData[$i]['tipo_elemento'] == 'R') $arrData[$i]['tipo_elemento'] = 'Refacción';


				// Botones
				$btnView = '';
				$btnEdit = '';
				$btnDelete = '';
				$btnConfig = '';

				if ($_SESSION['permisosMod']['r']) {
					$btnView = '<button class="btn btn-sm btn-soft-info" onClick="fntViewInventario(' . $arrData[$i]['idinventario'] . ')">
                                <i class="ri-eye-fill"></i>
                            </button>';
				}

				if ($_SESSION['permisosMod']['u']) {
					$btnEdit = '<button class="btn btn-sm btn-soft-warning" onClick="fntEditInventario(' . $arrData[$i]['idinventario'] . ')">
                                <i class="ri-pencil-fill"></i>
                            </button>';
				}

				if ($_SESSION['permisosMod']['d']) {
					$btnDelete = '<button class="btn btn-sm btn-soft-danger" onClick="fntDelInventario(' . $arrData[$i]['idinventario'] . ')">
                                <i class="ri-delete-bin-5-fill"></i>
                            </button>';
				}
				if (in_array($tipoRaw, ['P', 'C', 'H', 'K', 'R'])) {
					$btnConfig = '<button class="btn btn-sm btn-soft-primary" title="Configurar" onClick="fntConfigInventario(' . $arrData[$i]['idinventario'] . ')"><i class="ri-settings-3-fill"></i></button>';
				}



				$arrData[$i]['options'] = '<div class="text-center">'
					. $btnView . ' '
					. $btnEdit . ' '
					. $btnConfig .
					'</div>';
			}

			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
	}



	public function getInventario($idinventario)
	{
		if ($_SESSION['permisosMod']['r']) {

			$intidalmacen = intval($idinventario);

			if ($intidalmacen > 0) {

				$arrData = $this->model->selectInventario($intidalmacen);

				if (empty($arrData)) {

					$arrResponse = [
						'status' => false,
						'msg' => 'Datos no encontrados.'
					];
				} else {

					$principal = $arrData;

					// 🔥 NUEVO: TRAER IMÁGENES
					$imagenes = $this->model->selectImagenesInventario($intidalmacen);
					$principal['imagenes'] = $imagenes;

					$arrResponse = [
						'status' => true,
						'data' => $principal
					];
				}

				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			}
		}
		die();
	}


	public function delInventario()
	{
		if ($_POST) {
			if ($_SESSION['permisosMod']['d']) {
				$intidalmacen = intval($_POST['idinventario']);
				$requestDelete = $this->model->deleteInventario($intidalmacen);
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

	public function deleteImagen()
{
    header('Content-Type: application/json; charset=utf-8');

    try {

        $id = intval($_POST['idfotoinventario'] ?? 0);

        if ($id <= 0) {
            echo json_encode([
                'status' => false,
                'msg' => 'ID de imagen inválido'
            ]);
            die();
        }

        // ==========================================
        // BUSCAR IMAGEN
        // ==========================================

        $imagen = $this->model->selectImagenInventario($id);

        if (!$imagen) {

            echo json_encode([
                'status' => false,
                'msg' => 'No se encontró la imagen'
            ]);
            die();
        }

        $nombreArchivo = $imagen['foto'];

        // ==========================================
        // RUTA FÍSICA DE LA IMAGEN
        // ==========================================

        $ruta = $_SERVER['DOCUMENT_ROOT']
              . "/mrp-ldr/Assets/uploads/inventario_imagenes/"
              . $nombreArchivo;

        // ==========================================
        // ELIMINAR ARCHIVO FÍSICO
        // ==========================================

        if (file_exists($ruta)) {

            if (!unlink($ruta)) {

                echo json_encode([
                    'status' => false,
                    'msg' => 'No se pudo eliminar el archivo físico'
                ]);
                die();
            }
        }

        // ==========================================
        // ELIMINAR REGISTRO BD
        // ==========================================

        $eliminado = $this->model->deleteImagenInventario($id);

        if (!$eliminado) {

            echo json_encode([
                'status' => false,
                'msg' => 'No se pudo eliminar el registro de la base de datos'
            ]);
            die();
        }

        // ==========================================
        // RESPUESTA
        // ==========================================

        echo json_encode([
            'status' => true,
            'msg' => 'Imagen eliminada correctamente'
        ]);

    } catch (Throwable $e) {

        http_response_code(500);

        echo json_encode([
            'status' => false,
            'msg' => $e->getMessage(),
            'line' => $e->getLine()
        ]);
    }

    die();
}

	public function buscarProductoKit()
	{
		// Blindaje total para AJAX
		ob_start();

		if (!isset($_SESSION['permisosMod']['r']) || !$_SESSION['permisosMod']['r']) {
			ob_clean();
			header('Content-Type: application/json');
			echo json_encode([]);
			exit;
		}

		$term = strClean($_GET['term'] ?? '');

		// ✅ SIEMPRE llamar al MODELO
		$arrData = $this->model->buscarProductoKit($term);

		ob_clean();
		header('Content-Type: application/json');
		echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		exit;
	}




	public function getSelectInventarios($idprecio)
	{
		$htmlOptions = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectOptionAlmacenes($idprecio);
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


	public function setKitDetalle()
	{
		if ($_POST) {

			$kitid = intval($_POST['kitid'] ?? 0);
			$componentes = $_POST['componentes'] ?? [];

			if ($kitid <= 0 || empty($componentes)) {
				echo json_encode([
					'status' => false,
					'msg' => 'Datos del kit incompletos'
				]);
				die();
			}

			foreach ($componentes as $item) {

				$productoId = intval($item['idinventario'] ?? 0);
				$cantidad   = floatval($item['cantidad'] ?? 0);
				$porcentaje = floatval($item['porcentaje'] ?? 0);

				if ($productoId <= 0 || $cantidad <= 0) {
					continue; // saltar inválidos
				}

				$this->model->insertKitDetalle(
					$kitid,
					$productoId,
					$cantidad,
					$porcentaje
				);
			}

			echo json_encode([
				'status' => true,
				'msg' => 'Componentes del kit guardados correctamente'
			]);
		}
		die();
	}

	public function setKitConfig()
	{
		if ($_POST) {

			$inventarioid = intval($_POST['inventarioid'] ?? 0);
			$kitid = intval($_POST['kitid'] ?? 0);
			$precio = floatval($_POST['precio'] ?? 0);
			$descripcion = strClean($_POST['descripcion'] ?? '');
			$componentes = $_POST['componentes'] ?? [];

			if ($inventarioid <= 0 || empty($componentes)) {
				echo json_encode([
					'status' => false,
					'msg' => 'Datos del kit incompletos'
				]);
				die();
			}

			// 🔍 BUSCAR SI YA EXISTE CONFIG
			$existing = $this->model->selectKitConfigByInventario($inventarioid);

			if (!empty($existing)) {

				// 🔥 YA EXISTE → USAR ESE ID
				$kitid = $existing['idkitconfig'];

				$this->model->updateKitConfig($kitid, $precio, $descripcion);
			} else {

				// 🔥 NO EXISTE → CREAR NUEVO
				$kitid = $this->model->insertKitConfig(
					$inventarioid,
					$precio,
					$descripcion
				);
			}

			$ids = [];

			foreach ($componentes as $item) {
				$ids[] = intval($item['idinventario']);
			}

			$this->model->deleteKitDetalleExcepto($kitid, $ids);

			// 🔥 INSERTAR NUEVO DETALLE
			foreach ($componentes as $item) {

				$productoId = intval($item['idinventario'] ?? 0);
				$cantidad   = floatval($item['cantidad'] ?? 0);
				$porcentaje = floatval($item['porcentaje'] ?? 0);

				if ($productoId <= 0 || $cantidad <= 0) continue;

				$this->model->insertKitDetalle(
					$kitid,
					$productoId,
					$cantidad,
					$porcentaje
				);
			}

			echo json_encode([
				'status' => true,
				'msg' => 'Kit actualizado correctamente'
			]);
		}
		die();
	}

	public function getKitCompleto($idinventario)
	{
		if ($_SESSION['permisosMod']['r']) {

			$data = $this->model->selectKitCompleto((int)$idinventario);

			if (empty($data)) {
				echo json_encode([
					"status" => false,
					"msg" => "Kit sin configuración"
				]);
				die();
			}

			echo json_encode([
				"status" => true,
				"data" => $data
			], JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	//----------------------------------------------------------------------IMPUESTOS
	public function getSelectImpuestos()
	{
		$data = $this->model->selectImpuestosCfg();

		$html = '<option value="">Seleccione impuesto</option>';

		foreach ($data as $row) {
			$selected = ($row['idimpuesto'] == 1) ? 'selected' : '';

			$html .= '<option value="' . $row['idimpuesto'] . '" ' . $selected . '>'
				. $row['descripcion'] .
				'</option>';
		}

		echo $html;
		die();
	}

	//----------------------------------------------------------------------MARCAS
	public function getSelectMarcas()
	{
		$data = $this->model->selectMarcas();

		$html = '<option value="">Seleccione una marca</option>';

		foreach ($data as $row) {

			$html .= '<option value="' . $row['id'] . '" ' . $selected . '>'
				. $row['nombre'] .
				'</option>';
		}

		echo $html;
		die();
	}

	//----------------------------------------------------------------------MONEDAS
	public function getSelectMonedas()
	{
		$html = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectMonedas();

		foreach ($arrData as $row) {
			if ($row['estado'] == 2) {
				$html .= '<option value="' . $row['idmoneda'] . '">' . $row['descripcion'] . '</option>';
			}
		}

		echo $html;
		die();
	}

	public function getSelectLineas()
	{
		$html = '<option value="">--Seleccione--</option>';
		$arrData = $this->model->selectLineas();

		foreach ($arrData as $row) {
			if ($row['estado'] == 2) {
				$html .= '<option value="' . $row['idlinea'] . '">' . $row['descripcion'] . '</option>';
			}
		}

		echo $html;
		die();
	}

	//----------------------------------------------------------------------MONEDAS INVENTARIO
	public function setMoneda()
	{
		if (!$_SESSION['permisosMod']['w']) {
			echo json_encode(['status' => false, 'msg' => 'Sin permisos']);
			die();
		}

		if ($_POST) {

			if (empty($_POST['inventarioid']) || empty($_POST['idmoneda'])) {
				echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
				die();
			}

			$inventarioid = intval($_POST['inventarioid']);
			$idmoneda = intval($_POST['idmoneda']);
			$tipo_cambio = $_POST['tipo_cambio'] ?? null;
			$estado = 2;
			$fecha = date('Y-m-d H:i:s');

			$request = $this->model->insertInventarioMoneda(
				$inventarioid,
				$idmoneda,
				$tipo_cambio,
				$fecha,
				$estado
			);

			if (is_numeric($request) && $request > 0) {
				$arrResponse = ['status' => true, 'msg' => 'Moneda asignada correctamente'];
			} else {
				$arrResponse = ['status' => false, 'msg' => 'Error al guardar moneda'];
			}

			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}

		die();
	}
	//MONEDAS ASIGNADAS
	public function getMonedasAsignadas($idinventario)
	{
		$monedas = $this->model->getMonedasAsignadas($idinventario);

		echo json_encode([
			'status' => true,
			'data' => $monedas
		], JSON_UNESCAPED_UNICODE);

		die();
	}

	//---------------------------------------------------------------------- PRECIOS
	// GUARDAR PRECIO
	public function setPrecioInventario()
	{
		if (!$_SESSION['permisosMod']['w']) {
			echo json_encode(['status' => false, 'msg' => 'Sin permisos']);
			die();
		}

		if ($_POST) {

			if (empty($_POST['inventarioid']) || empty($_POST['idprecio']) || empty($_POST['precio'])) {
				echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
				die();
			}

			$inventarioid = intval($_POST['inventarioid']);
			$idprecio = intval($_POST['idprecio']);
			$precio = floatval($_POST['precio']);
			$fecha = date('Y-m-d H:i:s');
			$estado = 2;

			$request = $this->model->insertInventarioPrecio(
				$inventarioid,
				$idprecio,
				$precio,
				$fecha,
				$estado
			);

			if (is_numeric($request) && $request > 0) {
				$arrResponse = ['status' => true, 'msg' => 'Precio asignado correctamente'];
			} else {
				$arrResponse = ['status' => false, 'msg' => 'Error al guardar precio'];
			}

			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}

		die();
	}
	//PRECIOS ASIGNADOS
	public function getPreciosAsignados($idinventario)
	{
		$monedas = $this->model->getPreciosAsignados($idinventario);

		echo json_encode([
			'status' => true,
			'data' => $monedas
		], JSON_UNESCAPED_UNICODE);

		die();
	}



	//---------------------------------------------------------------------- LINEAS INVENTARIO
	public function setLinea()
	{
		if (!$_SESSION['permisosMod']['w']) {
			echo json_encode(['status' => false, 'msg' => 'Sin permisos']);
			die();
		}

		if ($_POST) {

			if (empty($_POST['inventarioid']) || empty($_POST['sublineaproductoid'])) {
				echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
				die();
			}

			$inventarioid = intval($_POST['inventarioid']);
			$sublinea = intval($_POST['sublineaproductoid']);
			$estado = 2;
			$fecha = date('Y-m-d H:i:s');

			$request = $this->model->insertInventarioLinea(
				$inventarioid,
				$sublinea,
				$fecha,
				$estado
			);

			if ($request === "exist") {

				$arrResponse = [
					'status' => false,
					'msg' => 'Este producto ya tiene una línea asignada'
				];
			} elseif (is_numeric($request) && $request > 0) {

				$arrResponse = [
					'status' => true,
					'msg' => 'Línea asignada correctamente'
				];
			} else {

				$arrResponse = [
					'status' => false,
					'msg' => 'Error al guardar línea'
				];
			}
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}

		die();
	}

	public function updateLinea()
	{
		if ($_POST) {

			$id_inv_linea = intval($_POST['id_inv_linea']);
			$sublinea = intval($_POST['sublineaproductoid']);

			$request = $this->model->updateInventarioLinea(
				$id_inv_linea,
				$sublinea
			);

			if ($request) {
				$arrResponse = [
					'status' => true,
					'msg' => 'Línea actualizada correctamente'
				];
			} else {
				$arrResponse = [
					'status' => false,
					'msg' => 'Error al actualizar'
				];
			}

			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
			die();
		}
	}

	public function getSelectSublineas()
	{
		$arrData = $this->model->selectSublineas();

		$html = '<option value="">Seleccione</option>';

		foreach ($arrData as $row) {
			$html .= '<option value="' . $row['idsublineaproducto'] . '">'
				. $row['linea'] . ' - ' . $row['sublinea'] .
				'</option>';
		}

		echo $html;
		die();
	}


	public function getLineasAsignadas($idinventario)
	{
		// Usar el modelo principal del controlador
		$lineas = $this->model->getLineasAsignadas($idinventario);

		echo json_encode([
			'status' => true,
			'data' => $lineas
		]);
	}

	//----------------------------------------------------Datos fiscales SAT
	//**************************** CLAVE SAT ****************************/
	public function searchSAT()
	{
		$term = strClean($_GET['term'] ?? '');
		if (strlen($term) < 2) {
			echo json_encode([]);
			die();
		}
		$path = realpath(__DIR__ . '/../Assets/sat_catalogos/CAT_PROD_SERV.xml');
		if (!file_exists($path)) {
			echo json_encode([]);
			die();
		}
		$xml = simplexml_load_file($path);
		$rows = $xml->xpath('//row');
		$term = mb_strtolower($term);
		$grupos = [];
		foreach ($rows as $row) {
			$a = $row->attributes();
			$clave = (string)$a['c_ClaveProdServ'];
			$desc  = (string)$a['Descripcion'];
			$nivel = (string)$a['Nivel'];
			$agr   = (string)$a['Agrupador'];
			$hay = mb_strtolower($clave . ' ' . $desc . ' ' . $agr);
			if (strpos($hay, $term) !== false) {
				if (!isset($grupos[$agr])) {
					$grupos[$agr] = [
						'clase' => $agr,
						'items' => []
					];
				}
				if ($nivel == 'Subclase') {
					$grupos[$agr]['items'][] = [
						'clave' => $clave,
						'descripcion' => $desc
					];
				}
			}
		}
		echo json_encode(array_values($grupos), JSON_UNESCAPED_UNICODE);
		die();
	}

	//**************************** CLAVE UNIDADSAT ****************************/
	public function searchUNIDADSAT()
	{
		$term = strClean($_GET['term'] ?? '');

		if (strlen($term) < 2) {
			echo json_encode([]);
			die();
		}

		$path = realpath(__DIR__ . '/../Assets/sat_catalogos/CAT_CLAVE_UNI.xml');


		if (!file_exists($path)) {
			echo json_encode([]);
			die();
		}

		$xml = simplexml_load_file($path);
		$rows = $xml->xpath('//row');

		$term = mb_strtolower($term);
		$res = [];

		foreach ($rows as $row) {

			$a = $row->attributes();

			$clave = (string)$a['c_ClaveUnidad'];
			$nombre = (string)$a['Nombre'];
			$desc  = (string)$a['Descripcion'];

			$hay = mb_strtolower($clave . ' ' . $nombre . ' ' . $desc);

			if (strpos($hay, $term) !== false) {
				$res[] = [
					'clave' => $clave,
					'descripcion' => $nombre . ' - ' . $desc
				];
			}

			if (count($res) >= 30) break;
		}

		echo json_encode($res, JSON_UNESCAPED_UNICODE);
		die();
	}

	//**************************** GRACCION ARANCELARIA ****************************/
	public function searchFRACCIONSAT()
	{
		$term = strClean($_GET['term'] ?? '');

		if (strlen($term) < 2) {
			echo json_encode([]);
			die();
		}

		$path = realpath(__DIR__ . '/../Assets/sat_catalogos/CAT_FRACC_ARANC.xml');


		if (!file_exists($path)) {
			echo json_encode([]);
			die();
		}

		$xml = simplexml_load_file($path);
		$rows = $xml->xpath('//row');

		$term = mb_strtolower($term);
		$res = [];

		foreach ($rows as $row) {

			$a = $row->attributes();

			$clave = (string)$a['Clave'];
			$desc  = (string)$a['Descripcion'];

			$hay = mb_strtolower($clave . ' ' .  $desc);

			if (strpos($hay, $term) !== false) {
				$res[] = [
					'clave' => $clave,
					'descripcion' => $desc
				];
			}

			if (count($res) >= 30) break;
		}

		echo json_encode($res, JSON_UNESCAPED_UNICODE);
		die();
	}

	//**************************** UNIDAD ADUANA SAT ****************************/
	public function searchADUANASAT()
	{
		$term = strClean($_GET['term'] ?? '');

		if (strlen($term) < 2) {
			echo json_encode([]);
			die();
		}

		$path = realpath(__DIR__ . '/../Assets/sat_catalogos/CAT_ADUANA.xml');


		if (!file_exists($path)) {
			echo json_encode([]);
			die();
		}

		$xml = simplexml_load_file($path);
		$rows = $xml->xpath('//row');

		$term = mb_strtolower($term);
		$res = [];

		foreach ($rows as $row) {

			$a = $row->attributes();

			$clave = (string)$a['c_Aduana'];
			$desc  = (string)$a['Descripcion'];

			$hay = mb_strtolower($clave . ' ' . $desc);

			if (strpos($hay, $term) !== false) {
				$res[] = [
					'clave' => $clave,
					'descripcion' => $desc
				];
			}

			if (count($res) >= 30) break;
		}

		echo json_encode($res, JSON_UNESCAPED_UNICODE);
		die();
	}

	//**************************** guardar datos fiscales del producto ****************************/
	public function setFiscal()
	{
		$inventarioid = intval($_POST['inventarioid'] ?? 0);
		$grupo = $_POST['grupo'] ?? '';



		if ($inventarioid <= 0) {
			echo json_encode(['status' => false, 'msg' => 'Inventario inválido']);
			die();
		}

		$data = [
			'inventarioid'        => $inventarioid,
			'clave_sat'          => strClean($_POST['clave_sat'] ?? ''),
			'desc_sat'           => strClean($_POST['desc_sat'] ?? ''),
			'clave_unidad_sat'   => strClean($_POST['clave_unidad_sat'] ?? ''),
			'desc_unidad_sat'    => strClean($_POST['desc_clave_unidad_sat'] ?? ''),
			'clave_fraccion_sat' => strClean($_POST['clave_fraccion_sat'] ?? ''),
			'desc_fraccion_sat'  => strClean($_POST['desc_clave_fraccion_sat'] ?? ''),
			'clave_aduana_sat'   => strClean($_POST['clave_aduana_sat'] ?? ''),
			'desc_aduana_sat'    => strClean($_POST['desc_clave_aduana_sat'] ?? '')
		];

		$update = [];

		if ($grupo == 'sat') {
			$update['clave_sat'] = strClean($_POST['clave_sat'] ?? '');
			$update['desc_sat']  = strClean($_POST['desc_sat'] ?? '');
		}

		if ($grupo == 'unidad') {
			$update['clave_unidad_sat'] = strClean($_POST['clave_unidad_sat'] ?? '');
			$update['desc_unidad_sat']  = strClean($_POST['desc_clave_unidad_sat'] ?? '');
		}

		if ($grupo == 'fraccion') {
			$update['clave_fraccion_sat'] = strClean($_POST['clave_fraccion_sat'] ?? '');
			$update['desc_fraccion_sat']  = strClean($_POST['desc_clave_fraccion_sat'] ?? '');
		}

		if ($grupo == 'aduana') {
			$update['clave_aduana_sat'] = strClean($_POST['clave_aduana_sat'] ?? '');
			$update['desc_aduana_sat']  = strClean($_POST['desc_clave_aduana_sat'] ?? '');
		}


		$existe = $this->model->getFiscalByInventario($inventarioid);

		if (empty($existe)) {
			$resp = $this->model->insertFiscal($data);
		} else {
			$resp = $this->model->updateFiscalParcial($existe['idfiscal'], $update);
		}

		if (!empty($existe) && empty($update)) {
			echo json_encode(['status' => false, 'msg' => 'Nada que actualizar']);
			die();
		}


		echo json_encode([
			'status' => $resp ? true : false,
			'msg'    => $resp ? 'Fiscal guardado' : 'Error al guardar fiscal'
		]);

		die();
	}

	public function getFiscalByInventario($idinventario)
	{
		$data = $this->model->getFiscalByInventario((int)$idinventario);

		if (empty($data)) {
			echo json_encode(['status' => false]);
		} else {
			echo json_encode(['status' => true, 'data' => $data]);
		}
		die();
	}


	// ================= IMPUESTOS =================

	public function getSelectImpuestosCfg()
	{
		$data = $this->model->selectImpuestosCfg();

		$html = '<option value="">Seleccione un impuesto</option>';
		foreach ($data as $row) {
			$html .= '<option value="' . $row['idimpuesto'] . '">' . $row['descripcion'] . '</option>';
		}

		echo $html;
		die();
	}

	public function setImpuesto()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_POST['inventarioid']) || empty($_POST['idimpuesto'])) {
			echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
			die();
		}

		$inventarioid = intval($_POST['inventarioid']);
		$idimpuesto   = intval($_POST['idimpuesto']);

		$resp = $this->model->insertInventarioImpuestoform($inventarioid, $idimpuesto, 2);

		if ($resp === "exist") {
			echo json_encode([
				'status' => false,
				'msg' => 'Este impuesto ya está asignado al producto'
			]);
		} elseif ($resp > 0) {
			echo json_encode([
				'status' => true,
				'msg' => 'Impuesto asignado correctamente'
			]);
		} else {
			echo json_encode([
				'status' => false,
				'msg' => 'Error al asignar impuesto'
			]);
		}

		die();
	}


	public function getImpuestosAsignados($idinventario)
	{
		$data = $this->model->getImpuestosAsignados($idinventario);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'status' => true,
			'data' => $data
		]);
		die();
	}

	// ================= ubicaciones  =================
	//----------------------------- SELECT
	public function getSelectUbicaciones()
	{
		$data = $this->model->selectUbicacionesFull();

		$html = '<option value="">Seleccione ubicación</option>';

		foreach ($data as $row) {
			$html .= '<option value="' . $row['idubicaciones'] . '">'
				. $row['nombre'] . '</option>';
		}

		echo $html;
		die();
	}


	//----------------------------- INSERT
	public function setUbicacion()
	{
		header('Content-Type: application/json');

		if (empty($_POST['inventarioid']) || empty($_POST['ubicacionid'])) {
			echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
			die();
		}

		$inventarioid = intval($_POST['inventarioid']);
		$ubicacionid = intval($_POST['ubicacionid']);
		$cantidad = intval($_POST['cantidad']);
		$fecha = date('Y-m-d H:i:s');

		$resp = $this->model->insertInventarioUbicacion(
			$inventarioid,
			$ubicacionid,
			$cantidad,
			$fecha
		);

		if ($resp === "exist") {
			echo json_encode([
				'status' => false,
				'msg' => 'Esta ubicación ya está asignada'
			]);
		} elseif ($resp > 0) {
			echo json_encode([
				'status' => true,
				'msg' => 'Ubicación asignada correctamente'
			]);
		} elseif ($resp === "ocupada") {
			echo json_encode([
				'status' => false,
				'msg' => 'La ubicación ya está ocupada'
			]);
		} else {
			echo json_encode([
				'status' => false,
				'msg' => 'Error al guardar'
			]);
		}

		die();
	}


	//----------------------------- TABLA
	public function getUbicacionesAsignadas($idinventario)
	{
		$data = $this->model->getUbicacionesAsignadas($idinventario);

		echo json_encode([
			'status' => true,
			'data' => $data
		]);

		die();
	}

	public function index()
	{
		return $this->apiResponse($this->inventarioService->items(sanitizeGet()));
	}


	// ================= PROVEEDORES =================

	public function getSelectProveedoresCfg()
	{
		$data = $this->model->selectProveedoresCfg();

		$html = '<option value="">Seleccione un proveedor</option>';
		foreach ($data as $row) {
			$html .= '<option value="' . $row['id_proveedor'] . '">' . $row['nombre_comercial'] . '</option>';
		}

		echo $html;
		die();
	}

	public function setProveedor()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_POST['inventarioid']) || empty($_POST['id_proveedor'])) {
			echo json_encode(['status' => false, 'msg' => 'Datos incompletos']);
			die();
		}

		$inventarioid = intval($_POST['inventarioid']);
		$id_proveedor   = intval($_POST['id_proveedor']);

		$resp = $this->model->insertInventarioProveedorform($inventarioid, $id_proveedor, 2);

		if ($resp === "exist") {
			echo json_encode([
				'status' => false,
				'msg' => 'Este proveedor ya está asignado al producto'
			]);
		} elseif ($resp > 0) {
			echo json_encode([
				'status' => true,
				'msg' => 'Proveedor asignado correctamente'
			]);
		} else {
			echo json_encode([
				'status' => false,
				'msg' => 'Error al asignar proveedor'
			]);
		}

		die();
	}


	public function getProveedoresAsignados($idinventario)
	{
		$data = $this->model->getProveedoresAsignados($idinventario);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'status' => true,
			'data' => $data
		]);
		die();
	}

	// ================= PORTAL WEB =================
	// Dos modos segun tipo_elemento de wms_inventario. Solo 'P' y 'R' tienen
	// pestaña Portal Web (Herramienta, Componente, Kit y Servicio no la ven):
	//  - 'P' (Producto = unidad ensamblada) -> tablas del desarrollador
	//    del portal (web_unidades / web_unidades_imagenes). web_unidades tiene
	//    su propia columna inventarioid (agregada con permiso del desarrollador).
	//  - 'R' (Refaccion) -> tablas propias (wms_refacciones_portalweb /
	//    wms_refacciones_portalweb_imagenes), mismas columnas que 'P'.

	private function tipoPortalWeb($inventarioid)
	{
		$producto = $this->model->selectInventario($inventarioid);
		$tipo = $producto['tipo_elemento'] ?? null;

		if ($tipo === 'P') {
			return 'unidad';
		}

		if ($tipo === 'R') {
			return 'refaccion';
		}

		return null;
	}

	public function getPortalWeb($idinventario)
	{
		header('Content-Type: application/json; charset=utf-8');

		$inventarioid = intval($idinventario);

		if ($inventarioid <= 0) {
			echo json_encode(['status' => false, 'msg' => 'Inventario inválido']);
			die();
		}

		$modo = $this->tipoPortalWeb($inventarioid);

		if ($modo === 'unidad') {
			$unidad = $this->model->getWebUnidadByInventario($inventarioid);
			$imagenes = [];

			if (!empty($unidad)) {
				$imagenes = $this->model->selectImagenesWebUnidad((int) $unidad['idunidad']);
			}

			// Sugerencias automáticas para cuando aún no existe el registro en web_unidades
			$sugerencias = [
				'clave_modelo' => '',
				'nombre' => '',
				'marca' => $this->model->getMarcaAutoPorLinea($inventarioid),
				'precio_estimado' => $this->model->getPrecioPublicoAuto($inventarioid),
			];

			if (empty($unidad)) {
				$producto = $this->model->selectInventario($inventarioid);
				$sugerencias['clave_modelo'] = $producto['cve_articulo'] ?? '';
				$sugerencias['nombre'] = $producto['descripcion'] ?? '';
			}

			echo json_encode([
				'status' => true,
				'data' => [
					'modo' => 'unidad',
					'unidad' => $unidad ?: null,
					'imagenes' => $imagenes,
					'stock_actual' => $this->model->getStockTotalAuto($inventarioid),
					'sugerencias' => $sugerencias,
				]
			], JSON_UNESCAPED_UNICODE);
			die();
		}

		if ($modo === 'refaccion') {
			$config = $this->model->getPortalWebRefaccionByInventario($inventarioid);
			$imagenes = [];

			if (!empty($config)) {
				$imagenes = $this->model->selectImagenesPortalWebRefaccion((int) $config['idportalweb']);
			}

			// Sugerencias automáticas para cuando aún no existe el registro en wms_refacciones_portalweb
			$sugerencias = [
				'clave_modelo' => '',
				'nombre' => '',
				'marca' => $this->model->getMarcaAutoPorLinea($inventarioid),
				'precio_estimado' => $this->model->getPrecioPublicoAuto($inventarioid),
			];

			if (empty($config)) {
				$producto = $this->model->selectInventario($inventarioid);
				$sugerencias['clave_modelo'] = $producto['cve_articulo'] ?? '';
				$sugerencias['nombre'] = $producto['descripcion'] ?? '';
			}

			echo json_encode([
				'status' => true,
				'data' => [
					'modo' => 'refaccion',
					'unidad' => $config ?: null,
					'imagenes' => $imagenes,
					'stock_actual' => $this->model->getStockTotalAuto($inventarioid),
					'sugerencias' => $sugerencias,
				]
			], JSON_UNESCAPED_UNICODE);
			die();
		}

		echo json_encode(['status' => false, 'msg' => 'Este tipo de elemento no tiene Portal Web']);
		die();
	}

	public function setPortalWeb()
	{
		header('Content-Type: application/json; charset=utf-8');

		$inventarioid = intval($_POST['inventarioid'] ?? 0);

		if ($inventarioid <= 0) {
			echo json_encode(['status' => false, 'msg' => 'Inventario inválido']);
			die();
		}

		$modo = $this->tipoPortalWeb($inventarioid);

		if ($modo === 'unidad' || $modo === 'refaccion') {
			$claveModelo = strClean($_POST['clave_modelo'] ?? '');

			if (empty($claveModelo)) {
				echo json_encode(['status' => false, 'msg' => 'Falta el SKU / clave del modelo']);
				die();
			}

			$anio = intval($_POST['anio'] ?? 0);
			if ($anio <= 0) {
				$anio = (int) date('Y');
			}

			$nombre = strClean($_POST['nombre'] ?? '');

			$publicar = !empty($_POST['web_distribuidores']);
			$estatusRadio = $_POST['estatus'] ?? '2';

			if (!$publicar) {
				$estadoFinal = 0;
			} elseif ($estatusRadio == '2') {
				$estadoFinal = 2;
			} else {
				$estadoFinal = 1;
			}

			// Imagen principal (caratula) - opcional, se sube junto con el formulario
			// principal. Es distinta de las imagenes adicionales (evidencias) que se
			// suben aparte en la galeria (web_unidades_imagenes / wms_refacciones_portalweb_imagenes).
			$rutaCaratulaNueva = null;
			$carpetaCaratulas = ($modo === 'unidad') ? 'unidades_web' : 'refacciones_web';

			if (!empty($_FILES['imagen_caratula']['tmp_name']) && $_FILES['imagen_caratula']['error'] === 0) {
				$rutaCaratulas = __DIR__ . "/../Assets/uploads/{$carpetaCaratulas}/img_caratulas/";

				// [DevSecOps] Permisos 0750: Solo el owner (www-data) y el grupo pueden leer/escribir.
				if (!is_dir($rutaCaratulas) && !mkdir($rutaCaratulas, 0750, true) && !is_dir($rutaCaratulas)) {
					echo json_encode(['status' => false, 'msg' => 'No se pudo crear el directorio de destino para la imagen principal']);
					die();
				}

				$extCaratula = pathinfo($_FILES['imagen_caratula']['name'], PATHINFO_EXTENSION);
				$fechaCaratula = date("Ymd_His") . "_" . substr(microtime(), 2, 3);
				$nombreArchivoCaratula = "caratula_" . $inventarioid . "_" . $fechaCaratula . "." . $extCaratula;
				$destinoCaratula = $rutaCaratulas . $nombreArchivoCaratula;

				if (move_uploaded_file($_FILES['imagen_caratula']['tmp_name'], $destinoCaratula)) {
					$rutaCaratulaNueva = "Assets/uploads/{$carpetaCaratulas}/img_caratulas/" . $nombreArchivoCaratula;
				} else {
					error_log("❌ Error al mover archivo (imagen_caratula): " . $_FILES['imagen_caratula']['tmp_name']);
				}
			}

			$data = [
				'inventarioid' => $inventarioid,
				'modelo' => $nombre,
				'clave_modelo' => $claveModelo,
				'nombre' => $nombre,
				'descripcion' => strClean($_POST['descripcion'] ?? ''),
				'marca' => strClean($_POST['marca'] ?? ''),
				'stock' => $this->model->getStockTotalAuto($inventarioid),
				'precio_estimado' => (float) ($_POST['precio_estimado'] ?? 0),
				'estado' => $estadoFinal,
			];

			// Version, motor y año solo aplican a Producto (unidad ensamblada);
			// Refaccion no los usa.
			if ($modo === 'unidad') {
				$data['version'] = strClean($_POST['version'] ?? '');
				$data['motor'] = strClean($_POST['motor'] ?? '');
				$data['anio'] = $anio;
			}

			if ($modo === 'unidad') {
				$unidad = $this->model->getWebUnidadByInventario($inventarioid);

				if (!empty($unidad)) {
					$idunidad = (int) $unidad['idunidad'];
					$resp = $this->model->updateWebUnidad($idunidad, $data);

					if ($resp && $rutaCaratulaNueva !== null) {
						$rutaCaratulaAnterior = $unidad['imagen_caratula'] ?? '';

						if (!empty($rutaCaratulaAnterior) && $rutaCaratulaAnterior !== $rutaCaratulaNueva) {
							$rutaRelativaAnterior = (strpos($rutaCaratulaAnterior, 'Assets/') === 0)
								? substr($rutaCaratulaAnterior, strlen('Assets/'))
								: $rutaCaratulaAnterior;
							$rutaCaratulaAnteriorAbsoluta = __DIR__ . "/../Assets/" . $rutaRelativaAnterior;

							if (file_exists($rutaCaratulaAnteriorAbsoluta)) {
								@unlink($rutaCaratulaAnteriorAbsoluta);
							}
						}

						$this->model->updateImagenCaratulaWebUnidad($idunidad, $rutaCaratulaNueva);
					}
				} else {
					$data['imagen_caratula'] = $rutaCaratulaNueva ?? '';
					$idunidad = $this->model->insertWebUnidad($data);
					$resp = $idunidad;
				}

				echo json_encode([
					'status' => $resp ? true : false,
					'msg' => $resp ? 'Configuración de portal web guardada' : 'Error al guardar la configuración de portal web',
					'idunidad' => $idunidad,
				]);
				die();
			}

			// Modo refaccion
			$config = $this->model->getPortalWebRefaccionByInventario($inventarioid);

			if (!empty($config)) {
				$idportalweb = (int) $config['idportalweb'];
				$resp = $this->model->updatePortalWebRefaccion($idportalweb, $data);

				if ($resp && $rutaCaratulaNueva !== null) {
					$rutaCaratulaAnterior = $config['imagen_caratula'] ?? '';

					if (!empty($rutaCaratulaAnterior) && $rutaCaratulaAnterior !== $rutaCaratulaNueva) {
						$rutaRelativaAnterior = (strpos($rutaCaratulaAnterior, 'Assets/') === 0)
							? substr($rutaCaratulaAnterior, strlen('Assets/'))
							: $rutaCaratulaAnterior;
						$rutaCaratulaAnteriorAbsoluta = __DIR__ . "/../Assets/" . $rutaRelativaAnterior;

						if (file_exists($rutaCaratulaAnteriorAbsoluta)) {
							@unlink($rutaCaratulaAnteriorAbsoluta);
						}
					}

					$this->model->updateImagenCaratulaPortalWebRefaccion($idportalweb, $rutaCaratulaNueva);
				}
			} else {
				$data['imagen_caratula'] = $rutaCaratulaNueva ?? '';
				$idportalweb = $this->model->insertPortalWebRefaccion($data);
				$resp = $idportalweb;
			}

			echo json_encode([
				'status' => $resp ? true : false,
				'msg' => $resp ? 'Configuración de portal web guardada' : 'Error al guardar la configuración de portal web',
				'idportalweb' => $idportalweb,
			]);
			die();
		}

		echo json_encode(['status' => false, 'msg' => 'Este tipo de elemento no tiene Portal Web']);
		die();
	}

	public function subirImagenPortalWeb()
	{
		header('Content-Type: application/json; charset=utf-8');

		$inventarioid = intval($_POST['inventarioid'] ?? 0);

		if ($inventarioid <= 0) {
			echo json_encode(['status' => false, 'msg' => 'Inventario inválido']);
			die();
		}

		if (empty($_FILES['imagenes']['name'][0])) {
			echo json_encode(['status' => false, 'msg' => 'No se recibió ninguna imagen']);
			die();
		}

		$maxImagenes = 5;
		$modo = $this->tipoPortalWeb($inventarioid);

		if ($modo === 'unidad') {
			$unidad = $this->model->getWebUnidadByInventario($inventarioid);

			if (empty($unidad)) {
				echo json_encode(['status' => false, 'msg' => 'Primero guarda la configuración de portal web antes de subir imágenes']);
				die();
			}

			$idunidad = (int) $unidad['idunidad'];
			$actuales = $this->model->countImagenesWebUnidad($idunidad);
			$nuevas = count(array_filter($_FILES['imagenes']['name']));

			if ($actuales + $nuevas > $maxImagenes) {
				echo json_encode(['status' => false, 'msg' => "Solo puedes tener máximo {$maxImagenes} evidencias por unidad"]);
				die();
			}

			$ruta = __DIR__ . "/../Assets/uploads/web_unidades/";

			// [DevSecOps] Permisos 0750: Solo el owner (www-data) y el grupo pueden leer/escribir.
			if (!is_dir($ruta) && !mkdir($ruta, 0750, true) && !is_dir($ruta)) {
				echo json_encode(['status' => false, 'msg' => 'No se pudo crear el directorio de destino']);
				die();
			}

			$subidas = 0;
			$orden = $actuales;
			$yaTienePrincipal = false;

			foreach ($this->model->selectImagenesWebUnidad($idunidad) as $img) {
				if ((int) $img['es_principal'] === 1) {
					$yaTienePrincipal = true;
				}
			}

			foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp) {

				if ($_FILES['imagenes']['error'][$key] === 0) {

					$nombreOriginal = $_FILES['imagenes']['name'][$key];
					$ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
					$fecha = date("Ymd_His") . "_" . substr(microtime(), 2, 3);
					$nombreArchivo = "unidad_" . $idunidad . "_" . $fecha . "." . $ext;

					$destino = $ruta . $nombreArchivo;
					$rutaRelativa = "uploads/web_unidades/" . $nombreArchivo;

					if (move_uploaded_file($tmp, $destino)) {
						$orden++;
						$esPrincipal = (!$yaTienePrincipal) ? 1 : 0;

						$this->model->insertImagenWebUnidad($idunidad, $nombreOriginal, $nombreArchivo, $rutaRelativa, $orden, $esPrincipal);

						if ($esPrincipal) {
							$yaTienePrincipal = true;
						}

						$subidas++;
					} else {
						error_log("❌ Error al mover archivo (web_unidades): " . $tmp);
					}
				}
			}

			echo json_encode([
				'status' => $subidas > 0,
				'msg' => $subidas > 0 ? "Se subieron {$subidas} imagen(es)" : 'No se pudo subir ninguna imagen',
				'imagenes' => $this->model->selectImagenesWebUnidad($idunidad)
			], JSON_UNESCAPED_UNICODE);
			die();
		}

		if ($modo === 'refaccion') {
			$config = $this->model->getPortalWebRefaccionByInventario($inventarioid);

			if (empty($config)) {
				echo json_encode(['status' => false, 'msg' => 'Primero guarda la configuración de portal web antes de subir imágenes']);
				die();
			}

			$idportalweb = (int) $config['idportalweb'];
			$actuales = $this->model->countImagenesPortalWebRefaccion($idportalweb);
			$nuevas = count(array_filter($_FILES['imagenes']['name']));

			if ($actuales + $nuevas > $maxImagenes) {
				echo json_encode(['status' => false, 'msg' => "Solo puedes tener máximo {$maxImagenes} evidencias"]);
				die();
			}

			$ruta = __DIR__ . "/../Assets/uploads/portalweb_imagenes/";

			// [DevSecOps] Permisos 0750: Solo el owner (www-data) y el grupo pueden leer/escribir.
			if (!is_dir($ruta) && !mkdir($ruta, 0750, true) && !is_dir($ruta)) {
				echo json_encode(['status' => false, 'msg' => 'No se pudo crear el directorio de destino']);
				die();
			}

			$subidas = 0;
			$orden = $actuales;
			$yaTienePrincipal = false;

			foreach ($this->model->selectImagenesPortalWebRefaccion($idportalweb) as $img) {
				if ((int) $img['es_principal'] === 1) {
					$yaTienePrincipal = true;
				}
			}

			foreach ($_FILES['imagenes']['tmp_name'] as $key => $tmp) {

				if ($_FILES['imagenes']['error'][$key] === 0) {

					$nombreOriginal = $_FILES['imagenes']['name'][$key];
					$ext = pathinfo($nombreOriginal, PATHINFO_EXTENSION);
					$fecha = date("Ymd_His") . "_" . substr(microtime(), 2, 3);
					$nombreArchivo = "refaccion_" . $idportalweb . "_" . $fecha . "." . $ext;

					$destino = $ruta . $nombreArchivo;
					$rutaRelativa = "uploads/portalweb_imagenes/" . $nombreArchivo;

					if (move_uploaded_file($tmp, $destino)) {
						$orden++;
						$esPrincipal = (!$yaTienePrincipal) ? 1 : 0;

						$this->model->insertImagenPortalWebRefaccion($idportalweb, $nombreOriginal, $nombreArchivo, $rutaRelativa, $orden, $esPrincipal);

						if ($esPrincipal) {
							$yaTienePrincipal = true;
						}

						$subidas++;
					} else {
						error_log("❌ Error al mover archivo (portalweb refaccion): " . $tmp);
					}
				}
			}

			echo json_encode([
				'status' => $subidas > 0,
				'msg' => $subidas > 0 ? "Se subieron {$subidas} imagen(es)" : 'No se pudo subir ninguna imagen',
				'imagenes' => $this->model->selectImagenesPortalWebRefaccion($idportalweb)
			], JSON_UNESCAPED_UNICODE);
			die();
		}

		echo json_encode(['status' => false, 'msg' => 'Este tipo de elemento no tiene Portal Web']);
		die();
	}

	public function eliminarCaratulaPortalWeb()
	{
		header('Content-Type: application/json; charset=utf-8');

		$inventarioid = intval($_POST['inventarioid'] ?? 0);

		if ($inventarioid <= 0) {
			echo json_encode(['status' => false, 'msg' => 'Inventario inválido']);
			die();
		}

		$modo = $this->tipoPortalWeb($inventarioid);

		if ($modo === 'unidad') {
			$unidad = $this->model->getWebUnidadByInventario($inventarioid);

			if (empty($unidad)) {
				echo json_encode(['status' => false, 'msg' => 'No se encontró la unidad']);
				die();
			}

			$idRegistro = (int) $unidad['idunidad'];
			$rutaCaratula = $unidad['imagen_caratula'] ?? '';
		} elseif ($modo === 'refaccion') {
			$config = $this->model->getPortalWebRefaccionByInventario($inventarioid);

			if (empty($config)) {
				echo json_encode(['status' => false, 'msg' => 'No se encontró la refacción']);
				die();
			}

			$idRegistro = (int) $config['idportalweb'];
			$rutaCaratula = $config['imagen_caratula'] ?? '';
		} else {
			echo json_encode(['status' => false, 'msg' => 'Este tipo de elemento no tiene Portal Web']);
			die();
		}

		if (empty($rutaCaratula)) {
			echo json_encode(['status' => false, 'msg' => 'Este registro no tiene imagen principal']);
			die();
		}

		$rutaRelativa = (strpos($rutaCaratula, 'Assets/') === 0)
			? substr($rutaCaratula, strlen('Assets/'))
			: $rutaCaratula;
		$rutaAbsoluta = __DIR__ . "/../Assets/" . $rutaRelativa;

		if (file_exists($rutaAbsoluta)) {
			@unlink($rutaAbsoluta);
		}

		if ($modo === 'unidad') {
			$resp = $this->model->updateImagenCaratulaWebUnidad($idRegistro, '');
		} else {
			$resp = $this->model->updateImagenCaratulaPortalWebRefaccion($idRegistro, '');
		}

		echo json_encode([
			'status' => $resp ? true : false,
			'msg' => $resp ? 'Imagen principal eliminada' : 'Error al eliminar la imagen principal',
		]);
		die();
	}

	public function deleteImagenPortalWeb()
	{
		header('Content-Type: application/json; charset=utf-8');

		// El modo se distingue por el nombre del campo recibido, para no
		// mezclar los ids de dos tablas independientes (web_unidades_imagenes
		// vs wms_refacciones_portalweb_imagenes, cada una con su propio autoincrement).
		if (isset($_POST['idfotoportalweb'])) {
			$id = intval($_POST['idfotoportalweb']);

			if ($id <= 0) {
				echo json_encode(['status' => false, 'msg' => 'ID de imagen inválido']);
				die();
			}

			$imagen = $this->model->selectImagenPortalWebRefaccion($id);

			if (!$imagen) {
				echo json_encode(['status' => false, 'msg' => 'No se encontró la imagen']);
				die();
			}

			$idportalweb = (int) $imagen['idportalweb'];
			$eraPrincipal = (int) $imagen['es_principal'] === 1;
			$ruta = __DIR__ . "/../Assets/" . $imagen['ruta_archivo'];

			if (file_exists($ruta)) {
				@unlink($ruta);
			}

			$resp = $this->model->deleteImagenPortalWebRefaccion($id);

			if ($resp && $eraPrincipal) {
				$restantes = $this->model->selectImagenesPortalWebRefaccion($idportalweb);

				if (!empty($restantes)) {
					$nuevaPrincipal = $restantes[0];
					$this->model->marcarPrincipalImagenPortalWebRefaccion((int) $nuevaPrincipal['idfotoportalweb'], $idportalweb);
				}
			}

			echo json_encode([
				'status' => $resp ? true : false,
				'msg' => $resp ? 'Imagen eliminada' : 'Error al eliminar la imagen',
				'imagenes' => $this->model->selectImagenesPortalWebRefaccion($idportalweb)
			], JSON_UNESCAPED_UNICODE);
			die();
		}

		$id = intval($_POST['idimagen'] ?? 0);

		if ($id <= 0) {
			echo json_encode(['status' => false, 'msg' => 'ID de imagen inválido']);
			die();
		}

		$imagen = $this->model->selectImagenWebUnidad($id);

		if (!$imagen) {
			echo json_encode(['status' => false, 'msg' => 'No se encontró la imagen']);
			die();
		}

		$idunidad = (int) $imagen['idunidad'];
		$eraPrincipal = (int) $imagen['es_principal'] === 1;

		$ruta = __DIR__ . "/../Assets/" . $imagen['ruta_archivo'];

		if (file_exists($ruta)) {
			@unlink($ruta);
		}

		$resp = $this->model->deleteImagenWebUnidad($id);

		if ($resp && $eraPrincipal) {
			$restantes = $this->model->selectImagenesWebUnidad($idunidad);

			if (!empty($restantes)) {
				$nuevaPrincipal = $restantes[0];
				$this->model->marcarPrincipalImagenWebUnidad((int) $nuevaPrincipal['idimagen'], $idunidad);
			}
		}

		echo json_encode([
			'status' => $resp ? true : false,
			'msg' => $resp ? 'Imagen eliminada' : 'Error al eliminar la imagen',
			'imagenes' => $this->model->selectImagenesWebUnidad($idunidad)
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	public function marcarImagenPrincipalPortalWeb()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (isset($_POST['idfotoportalweb'])) {
			$id = intval($_POST['idfotoportalweb']);

			if ($id <= 0) {
				echo json_encode(['status' => false, 'msg' => 'ID de imagen inválido']);
				die();
			}

			$imagen = $this->model->selectImagenPortalWebRefaccion($id);

			if (!$imagen) {
				echo json_encode(['status' => false, 'msg' => 'No se encontró la imagen']);
				die();
			}

			$idportalweb = (int) $imagen['idportalweb'];

			$this->model->marcarPrincipalImagenPortalWebRefaccion($id, $idportalweb);

			echo json_encode([
				'status' => true,
				'msg' => 'Imagen marcada como principal',
				'imagenes' => $this->model->selectImagenesPortalWebRefaccion($idportalweb)
			], JSON_UNESCAPED_UNICODE);
			die();
		}

		$idimagen = intval($_POST['idimagen'] ?? 0);

		if ($idimagen <= 0) {
			echo json_encode(['status' => false, 'msg' => 'ID de imagen inválido']);
			die();
		}

		$imagen = $this->model->selectImagenWebUnidad($idimagen);

		if (!$imagen) {
			echo json_encode(['status' => false, 'msg' => 'No se encontró la imagen']);
			die();
		}

		$idunidad = (int) $imagen['idunidad'];

		$this->model->marcarPrincipalImagenWebUnidad($idimagen, $idunidad);

		echo json_encode([
			'status' => true,
			'msg' => 'Imagen marcada como principal',
			'imagenes' => $this->model->selectImagenesWebUnidad($idunidad)
		], JSON_UNESCAPED_UNICODE);
		die();
	}


	// ================= CANTIDADES =================

	public function getCantidadesProducto()
	{
		if ($_POST) {

			$inventarioid = intval($_POST['inventarioid']);

			$arrData = $this->model->selectCantidadesProducto($inventarioid);

			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
			die();
		}
	}

	public function getAlmacenesProducto()
	{
		if ($_POST) {

			$inventarioid = intval($_POST['inventarioid']);

			$arrData = $this->model->selectAlmacenesProducto($inventarioid);

			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
			die();
		}
	}
}
