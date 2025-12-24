<?php
class Inv_inventario extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MIINVENTARIO);
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
				empty($_POST['estado'])
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
			$ubicacion        = strClean($_POST['ubicacion'] ?? '');
			$factor_unidades  = floatval($_POST['factor_unidades'] ?? 1);
			$tiempo_surtido = intval($_POST['tiempo_surtido'] ?? 0);
			$serie            = strClean($_POST['serie'] ?? 'N');
			$lote             = strClean($_POST['lote'] ?? 'N');
			$pedimiento       = strClean($_POST['pedimiento'] ?? 'N');
			$peso             = floatval($_POST['peso'] ?? 0);
			$volumen          = floatval($_POST['volumen'] ?? 0);
			$estado           = intval($_POST['estado']);
			$clave_alterna   = strClean($_POST['clave_alterna'] ?? '');
			$tipo_asignacion = strClean($_POST['tipo_asignacion'] ?? '');
			$almacenid        = intval($_POST['almacenid'] ?? 0);
			$cantidadInicial  = floatval($_POST['cantidad_inicial'] ?? 0);
			$costoUnitario    = floatval($_POST['costo'] ?? 0);
			$precioUnitario   = floatval($_POST['precio'] ?? 0);



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
						$estado
					);
					$option = 1;

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

					if (
						$request > 0 &&
						$tipo_elemento === 'P' &&
						$almacenid > 0 &&
						$cantidadInicial > 0
					) {

						$signo = 1;
						$existencia = $cantidadInicial;
						$costoCantidad = $cantidadInicial * $costoUnitario;

						$this->model->insertMovimientoInventario([
							'inventarioid'     => $request,
							'almacenid'        => $almacenid,
							'concepmovid'      => 3, //  Entrada de fabrica
							'referencia'       => 'Alta de producto',
							'cantidad'         => $cantidadInicial,
							'costo_cantidad'   => $costoCantidad,
							'precio'           => $precioUnitario,
							'costo'            => $costoUnitario,
							'existencia'       => $existencia,
							'signo'            => $signo,
							'estado'           => 2
						]);
					}
				}
			} else {

				if ($_SESSION['permisosMod']['u']) {

					$request = $this->model->updateInventario(
						$idinventario,
						$cve_articulo,
						$descripcion,
						$lineaproductoid,
						$tipo_elemento,
						$unidad_entrada,
						$unidad_salida,
						$ubicacion,
						$factor_unidades,
						$tiempo_surtido,
						$serie,
						$lote,
						$pedimiento,
						$peso,
						$volumen,
						$estado
					);
					$option = 2;
				}
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

				// Tipo
				if ($arrData[$i]['tipo_elemento'] == 'P') $arrData[$i]['tipo_elemento'] = 'Producto';
				if ($arrData[$i]['tipo_elemento'] == 'S') $arrData[$i]['tipo_elemento'] = 'Servicio';
				if ($arrData[$i]['tipo_elemento'] == 'K') $arrData[$i]['tipo_elemento'] = 'Kit';

				// Botones
				$btnView = '';
				$btnEdit = '';
				$btnDelete = '';

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

				$arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnDelete . '</div>';
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
					$arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
				} else {

					$arrResponse = array('status' => true, 'data' => $arrData);
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
		if ($_SESSION['permisosMod']['r']) {

			$term = strClean($_GET['term'] ?? '');

			$arrData = $this->model->buscarProductoKit($term);

			echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
		}
		die();
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
}
