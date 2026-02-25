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
			$lineaproductoid  = intval($_POST['lineaproductoid'] ?? 0);
			$tipo_elemento    = strClean($_POST['tipo_elemento']); // P S K
			$unidad_entrada   = strClean($_POST['unidad_entrada'] ?? '');
			$unidad_salida    = strClean($_POST['unidad_salida'] ?? '');
			$ubicacion = strClean($_POST['ubicacion'] ?? 'GENERAL');
			$unidad_empaque = strClean($_POST['unidad_empaque'] ?? '');
			$ultimo_costo = floatval($_POST['ultimo_costo'] ?? 0);
			$factor_unidades  = floatval($_POST['factor_unidades'] ?? 1);
			$tiempo_surtido = intval($_POST['tiempo_surtido'] ?? 0);
			$serie            = strClean($_POST['serie'] ?? 'N');
			$lote             = strClean($_POST['lote'] ?? 'N');
			$pedimiento       = strClean($_POST['pedimiento'] ?? 'N');
			$peso             = floatval($_POST['peso'] ?? 0);
			$volumen          = floatval($_POST['volumen'] ?? 0);
			$clave_alterna   = strClean($_POST['clave_alterna'] ?? '');
			$tipo_asignacion = strClean($_POST['tipo_asignacion'] ?? '');
			$almacenid        = intval($_POST['almacenid'] ?? 0);
			$cantidadInicial  = floatval($_POST['cantidad_inicial'] ?? 0);
			$costoUnitario    = floatval($_POST['costo'] ?? 0);
			$precioUnitario   = floatval($_POST['precio'] ?? 0);
			$idimpuesto = intval($_POST['idimpuesto'] ?? 1);






			if ($lineaproductoid <= 0) {
				$arrResponse = [
					'status' => false,
					'msg' => 'Debes seleccionar una línea de producto válida'
				];
				header('Content-Type: application/json; charset=utf-8');
				echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
				die();
			}

			// =========================
			// INSERT / UPDATE
			// =========================
			if ($idinventario == 0) {

				if ($_SESSION['permisosMod']['w']) {

					$request = $this->model->insertInventario(
						$cve_articulo,
						$descripcion,
						$unidad_entrada,
						$unidad_salida,
						$unidad_empaque,
						$ultimo_costo,
						$lineaproductoid,
						$tipo_elemento,
						$factor_unidades,
						$ubicacion,
						$tiempo_surtido,
						$peso,
						$volumen,
						$serie,
						$lote,
						$pedimiento
					);
					$option = 1;

					// =========================
					// INSERTAR IMPUESTO
					// =========================

					if ($request > 0) {
						$this->model->insertInventarioImpuesto($request, $idimpuesto);
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

					$estado = 2; // activo por defecto

					$request = $this->model->updateInventario(
						$idinventario,
						$cve_articulo,
						$descripcion,
						$unidad_entrada,
						$unidad_salida,
						$unidad_empaque,
						$ultimo_costo,
						$lineaproductoid,
						$tipo_elemento,
						$factor_unidades,
						$ubicacion,
						$tiempo_surtido,
						$peso,
						$volumen,
						$serie,
						$lote,
						$pedimiento,
						$estado           // ✅ PARAMETRO QUE FALTABA
					);
					$option = 2;
				}
			}

			// =========================
			// INICIALIZAR MULTIALMACÉN CON EXISTENCIA INICIAL
			// =========================
			if (
				$request > 0 &&
				$option == 1 && // SOLO ALTA NUEVA
				in_array($tipo_elemento, ['P', 'C', 'H']) &&
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
			} elseif ($request > 0) {

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
				if (in_array($tipoRaw, ['P', 'C', 'H'])) {
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
					$principal = $arrData[0];
					$principal['claves'] = $arrData;

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

			// 🔹 INSERT HEADER
			$kitConfigId = $this->model->insertKitConfig(
				$inventarioid,
				$precio,
				$descripcion
			);

			if ($kitConfigId <= 0) {
				echo json_encode([
					'status' => false,
					'msg' => 'No se pudo guardar la configuración del kit'
				]);
				die();
			}

			// 🔹 INSERT DETALLE
			foreach ($componentes as $item) {

				$productoId = intval($item['idinventario'] ?? 0);
				$cantidad   = floatval($item['cantidad'] ?? 0);
				$porcentaje = floatval($item['porcentaje'] ?? 0);

				if ($productoId <= 0 || $cantidad <= 0) {
					continue;
				}

				$this->model->insertKitDetalle(
					$kitConfigId,
					$productoId,
					$cantidad,
					$porcentaje
				);
			}

			echo json_encode([
				'status' => true,
				'msg' => 'Configuración del kit guardada correctamente'
			]);
		}
		die();
	}

	//----------------------------------------------------------------------IMPUESTOS
	public function getSelectImpuestos()
	{
		$data = $this->model->selectImpuestos();

		$html = '<option value="">Seleccione impuesto</option>';

		foreach ($data as $row) {
			$selected = ($row['idimpuesto'] == 1) ? 'selected' : '';
			$html .= '<option value="' . $row['idimpuesto'] . '" ' . $selected . '>'
				. $row['cve_impuesto'] . ' - ' . $row['descripcion'] .
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

			if ($request > 0) {
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

			if (empty($_POST['inventarioid']) || empty($_POST['idprecio'])) {
				echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
				die();
			}

			$inventarioid = intval($_POST['inventarioid']);
			$idprecio = intval($_POST['idprecio']);
			$fecha = date('Y-m-d H:i:s');
			$estado = 2;

			$request = $this->model->insertInventarioPrecio(
				$inventarioid,
				$idprecio,
				$fecha,
				$estado
			);

			if ($request > 0) {
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

			if (empty($_POST['inventarioid']) || empty($_POST['idlineaproducto'])) {
				echo json_encode(['status' => false, 'msg' => 'Datos obligatorios']);
				die();
			}

			$inventarioid = intval($_POST['inventarioid']);
			$idlineaproducto = intval($_POST['idlineaproducto']);
			$estado = 2;
			$fecha = date('Y-m-d H:i:s');

			$request = $this->model->insertInventarioLinea(
				$inventarioid,
				$idlineaproducto,
				$fecha,
				$estado
			);

			if ($request === "exist") {

				$arrResponse = [
					'status' => false,
					'msg' => 'Este producto ya tiene una línea asignada'
				];
			} elseif ($request > 0) {

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
			$idlineaproducto = intval($_POST['idlineaproducto']);

			$request = $this->model->updateInventarioLinea(
				$id_inv_linea,
				$idlineaproducto
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

	public function index()
	{
		return $this->apiResponse($this->inventarioService->items(sanitizeGet()));
	}
}
