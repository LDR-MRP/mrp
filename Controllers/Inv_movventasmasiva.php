<?php
/**
 * Carga masiva de VENTAS / FACTURACIÓN de unidades (VINs).
 * Extensión de Inv_movimientosinventario (reutiliza su permiso MIMOVIMIENTOS).
 *
 * Flujo:
 *  1. descargarLayout(): los VINs (1=Disponible, 2=Tatuado, 3=Facturado) que están
 *     en almacenes de Producto Terminado o Tránsito (los únicos donde se puede vender), con SKU, nombre, VIN, motor, estatus,
 *     y columnas DISTRIBUIDOR / FACTURA para capturar.
 *  2. procesarCarga(): SOLO ACTUALIZA. Cambios permitidos: 1 -> 3 y 2 -> 3 (Facturado)
 *     con distribuidor y factura. Cada VIN facturado = un movimiento de Ventas
 *     (una unidad por factura) vía Inv_movimientosinventarioModel::
 *     insertMovimientoMasivo(), que baja wms_multialmacen, marca el VIN en
 *     estado 3 y lo liga en wms_movimientos_inventario_series.
 *     Cualquier fila nueva, VIN/SKU/nombre/almacén/motor modificado o cambio
 *     de estatus no permitido se omite y va al log descargable.
 */
class Inv_movventasmasiva extends Controllers
{
	private const ESTATUS = ['1' => 'Disponible', '2' => 'Tatuado', '3' => 'Facturado'];
	private const MAX_FILAS_VALIDACION = 5000;

	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		getPermisos(MIMOVIMIENTOS);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_movventasmasiva()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Carga Masiva de Ventas (VINs)";
		$data['page_title'] = "Carga Masiva de Ventas (VINs)";
		$data['page_name'] = "movventasmasiva";
		$data['page_functions_js'] = "functions_inv_movventasmasiva.js";
		$this->views->getView($this, "inv_movventasmasiva", $data);
	}

	/* ===============================
	   LAYOUT CON TODOS LOS VINs (XLSX)
	=============================== */
	public function descargarLayout()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		@set_time_limit(300);

		$vines = $this->model->selectVinesLayout();

		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()
			->setCreator('LDR Solutions · MRP')
			->setTitle('Layout Ventas de Unidades (VINs)')
			->setDescription('Layout de actualización de estatus de VIN y facturación');

		/* ---------- HOJA 1: INSTRUCCIONES ---------- */
		$h1 = $ss->getActiveSheet();
		$h1->setTitle('Instrucciones');
		$h1->setCellValue('A1', 'LAYOUT DE VENTAS / FACTURACIÓN DE UNIDADES (VINs)');
		$h1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$h1->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s') . ' · histórico general: ' . count($vines) . ' VIN(s)');

		$instrucciones = [
			'1. Este archivo es SOLO DE ACTUALIZACIÓN: contiene el HISTÓRICO GENERAL de VINs (Disponible, Tatuado y Facturado) de los almacenes de PRODUCTO TERMINADO y TRÁNSITO de todas las plantas. No agregues filas: cualquier VIN que no exista se rechaza.',
			'2. Solo captura las columnas en VERDE: ESTATUS, DISTRIBUIDOR y FACTURA. Las columnas en GRIS (PLANTA, ALMACEN, SKU, NOMBRE, VIN, NUMERO_MOTOR, FECHA_VENTA) son informativas: si se modifican, esa fila NO se sube y se manda al log.',
			'3. Para facturar una unidad cambia ESTATUS a "Facturado" (elígelo de la lista), ESCRIBE el nombre del DISTRIBUIDOR (campo libre) y el número de FACTURA. Se puede facturar un VIN que esté en "Disponible" o en "Tatuado". Puedes arrastrar la celda para copiar el mismo estatus a varias filas.',
			'3b. En los VINs que ya están en "Facturado" puedes CORREGIR el DISTRIBUIDOR o la FACTURA de su venta (solo si esa venta es de una sola unidad). No se puede cambiar su ESTATUS aquí: las devoluciones se hacen en la sección de Devoluciones.',
			'4. Una unidad por factura: cada VIN facturado genera su propio movimiento de Ventas (baja la existencia del almacén donde está el VIN). Una misma FACTURA no puede usarse para dos VINs, ni repetir una factura ya registrada.',
			'5. No se permiten otros cambios de estatus desde este archivo (por ejemplo 1 -> 2: el tatuado se hace en Planeación). Las filas sin cambios simplemente se ignoran.',
			'6. Solo se pueden vender unidades que estén en almacenes de PRODUCTO TERMINADO o en el almacén de TRÁNSITO (por eso el layout solo trae esos almacenes); las de otros almacenes se rechazan. También se rechaza si el VIN está en un traslado activo o el almacén no tiene existencia suficiente.',
			'7. Al terminar puedes descargar el log en Excel con las filas que no se subieron y el motivo.',
		];
		$f = 4;
		foreach ($instrucciones as $txt) {
			$h1->setCellValue('A' . $f, $txt);
			$h1->mergeCells('A' . $f . ':H' . $f);
			$h1->getStyle('A' . $f)->getAlignment()->setWrapText(true);
			$h1->getRowDimension($f)->setRowHeight(32);
			$f++;
		}
		$h1->getColumnDimension('A')->setWidth(18);

		/* ---------- HOJA 2: ESTATUS ---------- */
		$he = $ss->createSheet();
		$he->setTitle('Estatus');
		$he->setCellValue('A1', 'ESTATUS');
		$he->getStyle('A1')->getFont()->setBold(true);
		$r = 2;
		foreach (self::ESTATUS as $k => $v) {
			$he->setCellValue('A' . $r, $v);
			$r++;
		}
		$he->getColumnDimension('A')->setWidth(20);

		/* ---------- HOJA 3: VINES (captura) ---------- */
		$hv = $ss->createSheet();
		$hv->setTitle('VINes');
		$enc = ['PLANTA', 'ALMACEN', 'SKU', 'NOMBRE', 'VIN', 'NUMERO_MOTOR', 'ESTATUS', 'DISTRIBUIDOR', 'FACTURA', 'FECHA_VENTA'];
		$hv->fromArray($enc, null, 'A1');
		$hv->getStyle('A1:J1')->getFont()->setBold(true);
		$hv->getStyle('A1:F1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFD9D9D9');
		$hv->getStyle('G1:I1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFC6EFCE');
		$hv->getStyle('J1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFD9D9D9');

		$total = count($vines);
		$ultima = max(2, $total + 1);
		// SKU, VIN, motor y factura como texto (evita notación científica / ceros perdidos)
		foreach (['C', 'E', 'F', 'I'] as $col) {
			$hv->getStyle($col . '2:' . $col . $ultima)->getNumberFormat()
				->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		}

		$r = 2;
		foreach ($vines as $v) {
			$hv->setCellValueExplicit('A' . $r, (string) $v['planta'], $T);
			$hv->setCellValueExplicit('B' . $r, (string) $v['almacen'], $T);
			$hv->setCellValueExplicit('C' . $r, (string) $v['cve_articulo'], $T);
			$hv->setCellValueExplicit('D' . $r, (string) $v['descripcion'], $T);
			$hv->setCellValueExplicit('E' . $r, (string) $v['numero_serie'], $T);
			$hv->setCellValueExplicit('F' . $r, (string) $v['numero_motor'], $T);
			$hv->setCellValueExplicit('G' . $r, self::ESTATUS[$v['estado']] ?? '', $T);
			if ($v['estado'] === '3') {
				$hv->setCellValueExplicit('H' . $r, (string) $v['distribuidor'], $T);
				$hv->setCellValueExplicit('I' . $r, (string) $v['factura'], $T);
				$hv->setCellValueExplicit('J' . $r, (string) $v['fecha_venta'], $T);
			}
			$r++;
		}

		if ($total > 0) {
			$hv->getStyle('A2:F' . $ultima)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFF2F2F2');
			$hv->getStyle('J2:J' . $ultima)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFF2F2F2');
			$filasDv = min($ultima, self::MAX_FILAS_VALIDACION + 1);
			$this->agregarValidacionLista($hv, 'G', 2, $filasDv, 'Estatus!$A$2:$A$' . (1 + count(self::ESTATUS)));
			$hv->setAutoFilter('A1:J' . $ultima);
		}
		$hv->freezePane('A2');

		$anchos = ['A' => 20, 'B' => 28, 'C' => 20, 'D' => 45, 'E' => 22, 'F' => 22, 'G' => 16, 'H' => 45, 'I' => 18, 'J' => 14];
		foreach ($anchos as $col => $w) {
			$hv->getColumnDimension($col)->setWidth($w);
		}

		$ss->setActiveSheetIndex(2);

		$filename = 'Layout_Ventas_VINs_' . date('Ymd_His') . '.xlsx';
		if (ob_get_length()) {
			ob_end_clean();
		}
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		header('Pragma: public');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
		$writer->save('php://output');
		exit;
	}

	/* ===============================
	   PROCESAR CARGA (solo actualización)
	=============================== */
	public function procesarCarga()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['w'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para registrar movimientos.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		@set_time_limit(600);

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}
		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene VINs para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$concepto = $this->model->selectConceptoVentas();
		if (!$concepto) {
			echo json_encode(['status' => false, 'msg' => 'No existe un concepto de Ventas activo (tipo Cliente, salida).'], JSON_UNESCAPED_UNICODE);
			die();
		}
		$concepmovid = (int) $concepto['idconcepmov'];

		$vinesArchivo = array_values(array_filter(array_map(function ($r) {
			return strtoupper(trim((string) ($r['vin'] ?? '')));
		}, $rows)));
		$vinesBD = $this->model->mapVinesPorNumero($vinesArchivo);

		$facturasArchivo = array_values(array_filter(array_map(function ($r) {
			return strtoupper(trim((string) ($r['factura'] ?? '')));
		}, $rows)));
		$facturasBD = $this->model->facturasUsadas($concepmovid, $facturasArchivo);

		$log = [];
		$aFacturar = [];
		$aCorregir = [];
		$vinesVistos = [];
		$facturasVistas = [];
		$sinCambios = 0;

		foreach ($rows as $row) {
			$fila = $row['_fila'];
			$vin = strtoupper(trim((string) ($row['vin'] ?? '')));
			$sku = trim((string) ($row['sku'] ?? ''));
			$estTxt = trim((string) ($row['estatus'] ?? ''));
			$cliTxt = trim((string) ($row['cliente'] ?? ''));
			$factura = trim((string) ($row['factura'] ?? ''));
			$base = ['fila' => $fila, 'vin' => $vin, 'sku' => $sku, 'estatus' => $estTxt];

			if ($vin === '') {
				$log[] = $base + ['motivo' => 'Fila sin VIN: este archivo solo actualiza VINs existentes'];
				continue;
			}
			if (isset($vinesVistos[$vin])) {
				$log[] = $base + ['motivo' => "VIN repetido en el archivo (ya viene en la fila {$vinesVistos[$vin]})"];
				continue;
			}
			$vinesVistos[$vin] = $fila;

			$bd = $vinesBD[$vin] ?? null;
			if (!$bd) {
				$log[] = $base + ['motivo' => 'El VIN no existe en el sistema (no se permite dar de alta ni modificar VINs desde este archivo)'];
				continue;
			}

			// Columnas informativas: deben coincidir con lo que hay en el sistema
			$cambios = [];
			if (array_key_exists('sku', $row) && $this->normalizarTexto($sku) !== $this->normalizarTexto((string) $bd['cve_articulo'])) {
				$cambios[] = "SKU (sistema: {$bd['cve_articulo']})";
			}
			if (array_key_exists('nombre', $row) && $this->normalizarTexto((string) $row['nombre']) !== $this->normalizarTexto((string) $bd['descripcion'])) {
				$cambios[] = 'NOMBRE (sistema: ' . $bd['descripcion'] . ')';
			}
			if (array_key_exists('almacen', $row) && $this->normalizarTexto((string) $row['almacen']) !== $this->normalizarTexto((string) $bd['almacen'])) {
				$cambios[] = 'ALMACEN (sistema: ' . $bd['almacen'] . ')';
			}
			if (array_key_exists('numero_motor', $row) && $this->normalizarTexto((string) $row['numero_motor']) !== $this->normalizarTexto((string) $bd['numero_motor'])) {
				$cambios[] = 'NUMERO_MOTOR (sistema: ' . ($bd['numero_motor'] !== '' ? $bd['numero_motor'] : 'vacío') . ')';
			}
			if (!empty($cambios)) {
				$log[] = $base + ['motivo' => 'Se modificaron columnas que no se pueden cambiar: ' . implode('; ', $cambios)];
				continue;
			}

			$estadoBD = (string) $bd['estado'];
			$estadoNuevo = $this->parsearEstatus($estTxt);

			// ---- VIN ya Facturado: solo se puede corregir distribuidor / factura de su venta ----
			if ($estadoBD === '3') {
				if ($estadoNuevo !== '3') {
					$log[] = $base + ['motivo' => 'El VIN está Facturado: su ESTATUS no se cambia aquí (las devoluciones se hacen en la sección de Devoluciones)'];
					continue;
				}
				$cambiaDist = array_key_exists('cliente', $row) && $this->normalizarTexto($cliTxt) !== $this->normalizarTexto((string) $bd['distribuidor']);
				$cambiaFac = array_key_exists('factura', $row) && $this->normalizarTexto($factura) !== $this->normalizarTexto((string) $bd['factura']);
				if (!$cambiaDist && !$cambiaFac) {
					$sinCambios++;
					continue;
				}
				if (empty($bd['idmov_venta'])) {
					$log[] = $base + ['motivo' => 'No se encontró el movimiento de venta de este VIN; no se puede corregir'];
					continue;
				}
				if ((int) $bd['vines_en_venta'] > 1) {
					$log[] = $base + ['motivo' => "La venta {$bd['folio_venta']} incluye {$bd['vines_en_venta']} VINs: la corrección de distribuidor/factura solo se permite en ventas de una unidad"];
					continue;
				}
				if ($cambiaDist && $cliTxt === '') {
					$log[] = $base + ['motivo' => 'El DISTRIBUIDOR no puede quedar vacío en un VIN facturado'];
					continue;
				}
				if ($cambiaFac) {
					if ($factura === '') {
						$log[] = $base + ['motivo' => 'La FACTURA no puede quedar vacía en un VIN facturado'];
						continue;
					}
					$facKey = strtoupper($factura);
					if (isset($facturasVistas[$facKey])) {
						$log[] = $base + ['motivo' => "La factura {$factura} ya se usa para otro VIN en la fila {$facturasVistas[$facKey]} (una unidad por factura)"];
						continue;
					}
					$otras = array_diff($facturasBD[$facKey] ?? [], [(int) $bd['idmov_venta']]);
					if (!empty($otras)) {
						$log[] = $base + ['motivo' => "La factura {$factura} ya está registrada en otro movimiento de Ventas"];
						continue;
					}
					$facturasVistas[$facKey] = $fila;
				}
				$aCorregir[] = [
					'base' => $base,
					'bd' => $bd,
					'distribuidor' => $cambiaDist ? $cliTxt : null,
					'factura' => $cambiaFac ? $factura : null,
				];
				continue;
			}

			if ($estadoNuevo === null) {
				$log[] = $base + ['motivo' => "ESTATUS '{$estTxt}' no válido: usa Disponible, Tatuado o Facturado (si trae número, debe coincidir con el texto)"];
				continue;
			}

			if ($estadoNuevo === $estadoBD) {
				if ($cliTxt !== '' || $factura !== '') {
					$log[] = $base + ['motivo' => 'Se capturó distribuidor/factura pero el ESTATUS no se cambió a Facturado'];
				} else {
					$sinCambios++;
				}
				continue;
			}

			if (!(in_array($estadoBD, ['1', '2'], true) && $estadoNuevo === '3')) {
				$log[] = $base + ['motivo' => 'Cambio de estatus no permitido en este archivo: '
					. self::ESTATUS[$estadoBD] . ' -> ' . self::ESTATUS[$estadoNuevo]
					. ' (solo se permite pasar a Facturado desde Disponible o Tatuado)'];
				continue;
			}

			// ---- Facturación 1/2 -> 3 (DISTRIBUIDOR es texto libre) ----
			if ($cliTxt === '') {
				$log[] = $base + ['motivo' => 'Para facturar debes escribir el DISTRIBUIDOR'];
				continue;
			}
			if ($factura === '') {
				$log[] = $base + ['motivo' => 'Para facturar debes capturar la FACTURA'];
				continue;
			}
			$facKey = strtoupper($factura);
			if (isset($facturasVistas[$facKey])) {
				$log[] = $base + ['motivo' => "La factura {$factura} ya se usa para otro VIN en la fila {$facturasVistas[$facKey]} (una unidad por factura)"];
				continue;
			}
			if (isset($facturasBD[$facKey])) {
				$log[] = $base + ['motivo' => "La factura {$factura} ya está registrada en otro movimiento de Ventas"];
				continue;
			}
			$facturasVistas[$facKey] = $fila;

			$aFacturar[] = [
				'base' => $base,
				'bd' => $bd,
				'distribuidor' => $cliTxt,
				'factura' => $factura,
			];
		}

		// ---- Un movimiento de Ventas por VIN (todo o nada por VIN) ----
		require_once 'Models/Inv_movimientosinventarioModel.php';
		$movModel = new Inv_movimientosinventarioModel();
		$usuarioid = !empty($_SESSION['idUser']) ? intval($_SESSION['idUser']) : null;
		$facturados = 0;

		foreach ($aFacturar as $item) {
			$bd = $item['bd'];
			try {
				$res = $movModel->insertMovimientoMasivo(
					(int) $bd['almacenid'],
					$concepmovid,
					$item['factura'],
					[(int) $bd['inventarioid']],
					[1],
					[(float) $bd['ultimo_costo']],
					null,
					null,
					null,
					[[(int) $bd['id_numeros_serie']]],
					$usuarioid,
					$item['distribuidor']
				);
				if (is_array($res)) {
					$facturados++;
				} else {
					$motivo = (string) $res;
					if (stripos($motivo, 'Stock insuficiente') === 0) {
						$motivo = "Sin existencia en multialmacén para el SKU {$bd['cve_articulo']} en {$bd['almacen']} "
							. '(el VIN está en inventario pero la existencia del almacén está en 0; revisa el inventario de ese almacén)';
					}
					$log[] = $item['base'] + ['motivo' => $motivo];
				}
			} catch (\Throwable $e) {
				error_log('Inv_movventasmasiva::procesarCarga: ' . $e->getMessage());
				$log[] = $item['base'] + ['motivo' => 'Error al guardar la venta (revisa el formato de los datos)'];
			}
		}

		// ---- Correcciones de ventas ya registradas ----
		$corregidos = 0;
		foreach ($aCorregir as $item) {
			try {
				$this->model->corregirVenta($item['bd'], $item['distribuidor'], $item['factura'], $usuarioid, (int) $item['base']['fila']);
				$corregidos++;
			} catch (\Throwable $e) {
				error_log('Inv_movventasmasiva::corregirVenta: ' . $e->getMessage());
				$log[] = $item['base'] + ['motivo' => 'Error al corregir la venta (revisa el formato de los datos)'];
			}
		}

		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});
		$_SESSION['cargaMasivaVentasLog'] = $log;

		echo json_encode([
			'status' => true,
			'facturados' => $facturados,
			'corregidos' => $corregidos,
			'sinCambios' => $sinCambios,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$facturados} VIN(s) facturado(s) (un movimiento de Ventas cada uno), "
				. "{$corregidos} venta(s) corregida(s), "
				. count($log) . " fila(s) no subida(s) y {$sinCambios} sin cambios, de " . count($rows) . " fila(s).",
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	/* ===============================
	   LOG DE FILAS NO SUBIDAS (XLSX)
	=============================== */
	public function exportarLog()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$this->enviarLog($_SESSION['cargaMasivaVentasLog'] ?? [],
			'Log de VINs NO actualizados (Carga masiva de ventas)', 'Log_CargaMasiva_Ventas_');
	}

	private function enviarLog(array $log, string $titulo, string $prefijo): void
	{
		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sh = $ss->getActiveSheet();
		$sh->setTitle('Log');
		$sh->setCellValue('A1', $titulo);
		$sh->mergeCells('A1:E1');
		$sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sh->mergeCells('A2:E2');

		$sh->fromArray(['FILA_ARCHIVO', 'VIN', 'SKU', 'ESTATUS', 'MOTIVO'], null, 'A4');
		$sh->getStyle('A4:E4')->getFont()->setBold(true);
		$sh->getStyle('A4:E4')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');
		$sh->freezePane('A5');

		$r = 5;
		foreach ($log as $it) {
			$sh->setCellValue('A' . $r, $it['fila'] ?? '');
			$sh->setCellValueExplicit('B' . $r, (string) ($it['vin'] ?? ''), $T);
			$sh->setCellValueExplicit('C' . $r, (string) ($it['sku'] ?? ''), $T);
			$sh->setCellValueExplicit('D' . $r, (string) ($it['estatus'] ?? ''), $T);
			$sh->setCellValueExplicit('E' . $r, (string) ($it['motivo'] ?? ''), $T);
			$r++;
		}
		$sh->getColumnDimension('A')->setWidth(14);
		$sh->getColumnDimension('B')->setWidth(22);
		$sh->getColumnDimension('C')->setWidth(20);
		$sh->getColumnDimension('D')->setWidth(16);
		$sh->getColumnDimension('E')->setWidth(90);

		$filename = $prefijo . date('Ymd_His') . '.xlsx';
		if (ob_get_length()) {
			ob_end_clean();
		}
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		header('Pragma: public');

		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
		$writer->save('php://output');
		exit;
	}

	/* =================================================================
	   DEVOLUCIONES
	   Layout con los VINs Facturados (3) y los datos de su última venta.
	   Para devolver: ESTATUS = "DEVOLUCION". Cada VIN genera su propio
	   movimiento "Devol. de venta" en el almacén del que salió (suma 1 en
	   multialmacén) y el VIN regresa como 1 = Disponible, listo para volver
	   a venderse con otra factura.
	================================================================= */
	public function descargarLayoutDevoluciones()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		@set_time_limit(300);

		$desde = $this->fechaValida($_GET['desde'] ?? '');
		$hasta = $this->fechaValida($_GET['hasta'] ?? '');
		$vines = $this->model->selectVinesFacturados($desde, $hasta);

		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()->setCreator('LDR Solutions · MRP')->setTitle('Layout Devoluciones de Unidades (VINs)');

		/* ---------- INSTRUCCIONES ---------- */
		$h1 = $ss->getActiveSheet();
		$h1->setTitle('Instrucciones');
		$h1->setCellValue('A1', 'LAYOUT DE DEVOLUCIONES DE UNIDADES (VINs FACTURADOS)');
		$h1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$rango = ($desde || $hasta) ? ' · ventas del ' . ($desde ?: 'inicio') . ' al ' . ($hasta ?: 'hoy') : ' · todas las ventas';
		$h1->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s') . ' · ' . count($vines) . ' VIN(s) facturado(s)' . $rango);
		$inst = [
			'1. Este archivo es SOLO DE ACTUALIZACIÓN: contiene los VINs que hoy están Facturados. No agregues filas: un VIN que no esté facturado se rechaza.',
			'2. Para devolver una unidad cambia ESTATUS a "DEVOLUCION". No hay que capturar nada más.',
			'3. La unidad regresa al mismo almacén del que salió (columna ALMACEN_SALIDA).',
			'4. Cada VIN devuelto genera su propio movimiento "Devol. de venta" (suma 1 en la existencia de ese almacén) y el VIN queda como Disponible, listo para venderse otra vez con otra factura.',
			'5. Las columnas en GRIS son informativas (SKU, NOMBRE, VIN, NUMERO_MOTOR, DISTRIBUIDOR, FACTURA...): si se modifican, esa fila NO se sube y se manda al log. Única excepción: si la venta no tiene DISTRIBUIDOR registrado, puedes escribirlo.',
			'6. Las filas que se dejan en "Facturado" se ignoran. Al terminar puedes descargar el log con lo que no se subió.',
		];
		$f = 4;
		foreach ($inst as $txt) {
			$h1->setCellValue('A' . $f, $txt);
			$h1->mergeCells('A' . $f . ':H' . $f);
			$h1->getStyle('A' . $f)->getAlignment()->setWrapText(true);
			$h1->getRowDimension($f)->setRowHeight(32);
			$f++;
		}
		$h1->getColumnDimension('A')->setWidth(18);

		/* ---------- OPCIONES DE ESTATUS ---------- */
		$he = $ss->createSheet();
		$he->setTitle('Estatus');
		$he->fromArray([['ESTATUS'], ['Facturado'], ['DEVOLUCION']], null, 'A1');
		$he->getStyle('A1')->getFont()->setBold(true);

		/* ---------- DEVOLUCIONES (captura) ---------- */
		$hv = $ss->createSheet();
		$hv->setTitle('Devoluciones');
		$enc = ['ALMACEN_SALIDA', 'SKU', 'NOMBRE', 'VIN', 'NUMERO_MOTOR', 'DISTRIBUIDOR', 'FACTURA', 'FECHA_VENTA', 'ESTATUS'];
		$hv->fromArray($enc, null, 'A1');
		$hv->getStyle('A1:I1')->getFont()->setBold(true);
		$hv->getStyle('A1:H1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFD9D9D9');
		$hv->getStyle('I1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFC6EFCE');

		$total = count($vines);
		$ultima = max(2, $total + 1);
		foreach (['B', 'D', 'E', 'G'] as $col) {
			$hv->getStyle($col . '2:' . $col . $ultima)->getNumberFormat()
				->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		}
		$r = 2;
		foreach ($vines as $v) {
			$fila = [$v['almacen_salida'], $v['cve_articulo'], $v['descripcion'], $v['numero_serie'], $v['numero_motor'],
				$v['distribuidor'], $v['factura'], $v['fecha_venta'], 'Facturado'];
			foreach ($fila as $k => $val) {
				$hv->setCellValueExplicit(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($k + 1) . $r, (string) $val, $T);
			}
			$r++;
		}
		if ($total > 0) {
			$hv->getStyle('A2:H' . $ultima)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFF2F2F2');
			$filasDv = min($ultima, self::MAX_FILAS_VALIDACION + 1);
			$this->agregarValidacionLista($hv, 'I', 2, $filasDv, 'Estatus!$A$2:$A$3');
			$hv->setAutoFilter('A1:I' . $ultima);
		}
		$hv->freezePane('A2');
		$anchos = ['A' => 30, 'B' => 18, 'C' => 40, 'D' => 22, 'E' => 20, 'F' => 40, 'G' => 18, 'H' => 14, 'I' => 16];
		foreach ($anchos as $col => $w) {
			$hv->getColumnDimension($col)->setWidth($w);
		}
		$ss->setActiveSheetIndex(2);

		$filename = 'Layout_Devoluciones_VINs_' . date('Ymd_His') . '.xlsx';
		if (ob_get_length()) {
			ob_end_clean();
		}
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="' . $filename . '"');
		header('Cache-Control: max-age=0');
		header('Pragma: public');
		$writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
		$writer->save('php://output');
		exit;
	}

	public function procesarDevoluciones()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['w'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para registrar movimientos.'], JSON_UNESCAPED_UNICODE);
			die();
		}
		@set_time_limit(600);

		$rows = $this->obtenerFilasArchivo('Devoluciones', [
			'ALMACEN_SALIDA' => 'almacen_salida',
			'SKU' => 'sku',
			'NOMBRE' => 'nombre',
			'VIN' => 'vin',
			'NUMERO_MOTOR' => 'numero_motor',
			'DISTRIBUIDOR' => 'distribuidor',
			'FACTURA' => 'factura',
			'FECHA_VENTA' => 'fecha_venta',
			'ESTATUS' => 'estatus',
		], ['vin', 'sku', 'nombre', 'estatus']);
		if ($rows === null) {
			die();
		}
		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene VINs para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$concepto = $this->model->selectConceptoDevolucion();
		if (!$concepto) {
			echo json_encode(['status' => false, 'msg' => 'No existe un concepto de devolución de venta activo (tipo Cliente, entrada).'], JSON_UNESCAPED_UNICODE);
			die();
		}
		$concepmovid = (int) $concepto['idconcepmov'];

		$vinesBD = $this->model->mapVinesFacturados(array_values(array_filter(array_map(function ($r) {
			return strtoupper(trim((string) ($r['vin'] ?? '')));
		}, $rows))));

		$log = [];
		$aDevolver = [];
		$vistos = [];
		$sinCambios = 0;

		foreach ($rows as $row) {
			$fila = $row['_fila'];
			$vin = strtoupper(trim((string) ($row['vin'] ?? '')));
			$estTxt = trim((string) ($row['estatus'] ?? ''));
			$base = ['fila' => $fila, 'vin' => $vin, 'sku' => trim((string) ($row['sku'] ?? '')), 'estatus' => $estTxt];

			if ($vin === '') {
				$log[] = $base + ['motivo' => 'Fila sin VIN: este archivo solo actualiza VINs facturados'];
				continue;
			}
			if (isset($vistos[$vin])) {
				$log[] = $base + ['motivo' => "VIN repetido en el archivo (ya viene en la fila {$vistos[$vin]})"];
				continue;
			}
			$vistos[$vin] = $fila;

			$est = $this->normalizarTexto($estTxt);
			$esDevolucion = in_array($est, ['DEVOLUCION', 'DEVOLVER', 'DEVUELTO'], true);

			$bd = $vinesBD[$vin] ?? null;
			if (!$bd) {
				if ($esDevolucion) {
					$log[] = $base + ['motivo' => 'El VIN no existe o ya no está Facturado (no se puede devolver)'];
				} else {
					$sinCambios++;
				}
				continue;
			}

			$cambios = [];
			$comparar = [
				'sku' => ['SKU', $bd['cve_articulo']],
				'nombre' => ['NOMBRE', $bd['descripcion']],
				'numero_motor' => ['NUMERO_MOTOR', $bd['numero_motor']],
				'factura' => ['FACTURA', $bd['factura']],
				'distribuidor' => ['DISTRIBUIDOR', $bd['distribuidor']],
				'almacen_salida' => ['ALMACEN_SALIDA', $bd['almacen_salida']],
			];
			// La devolución lleva el mismo distribuidor de la venta (cliente del catálogo
			// y/o texto). Si la venta no tiene ninguno, se permite escribirlo en la fila.
			$distTxt = trim((string) ($row['distribuidor'] ?? ''));
			$idClienteDev = !empty($bd['id_cliente']) ? (int) $bd['id_cliente'] : null;
			$distDev = trim((string) $bd['distribuidor_texto']) !== '' ? trim((string) $bd['distribuidor_texto']) : null;
			if (!$idClienteDev && $distDev === null && $distTxt !== '') {
				unset($comparar['distribuidor']);
				$distDev = $distTxt;
			}
			foreach ($comparar as $campo => [$etq, $valBD]) {
				if (array_key_exists($campo, $row) && $this->normalizarTexto((string) $row[$campo]) !== $this->normalizarTexto((string) $valBD)) {
					$cambios[] = "{$etq} (sistema: " . ($valBD !== '' ? $valBD : 'vacío') . ')';
				}
			}
			if (!empty($cambios)) {
				$log[] = $base + ['motivo' => 'Se modificaron columnas que no se pueden cambiar: ' . implode('; ', $cambios)];
				continue;
			}

			if (!$esDevolucion) {
				if ($est === '' || $this->parsearEstatus($estTxt) === '3') {
					$sinCambios++;
				} else {
					$log[] = $base + ['motivo' => 'ESTATUS no válido: para devolver usa "DEVOLUCION" (las ventas se hacen en el layout de ventas)'];
				}
				continue;
			}

			if (!$idClienteDev && $distDev === null) {
				$log[] = $base + ['motivo' => 'La venta de este VIN no tiene distribuidor registrado: escribe el DISTRIBUIDOR en la fila'];
				continue;
			}

			// Siempre regresa al almacén del que salió
			$aDevolver[] = ['base' => $base, 'bd' => $bd, 'almacen' => (int) $bd['almacen_salidaid'],
				'id_cliente' => $idClienteDev, 'distribuidor' => $distDev];
		}

		require_once 'Models/Inv_movimientosinventarioModel.php';
		$movModel = new Inv_movimientosinventarioModel();
		$usuarioid = !empty($_SESSION['idUser']) ? intval($_SESSION['idUser']) : null;
		$devueltos = 0;

		foreach ($aDevolver as $item) {
			$bd = $item['bd'];
			try {
				$res = $movModel->insertMovimientoMasivo(
					(int) $item['almacen'],
					$concepmovid,
					(string) $bd['factura'], // misma referencia con la que salió (la de la venta)
					[(int) $bd['inventarioid']],
					[1],
					[(float) $bd['ultimo_costo']],
					null,
					null,
					$item['id_cliente'],
					[[(int) $bd['id_numeros_serie']]],
					$usuarioid,
					$item['distribuidor']
				);
				if (is_array($res)) {
					$devueltos++;
				} else {
					$log[] = $item['base'] + ['motivo' => (string) $res];
				}
			} catch (\Throwable $e) {
				error_log('Inv_movventasmasiva::procesarDevoluciones: ' . $e->getMessage());
				$log[] = $item['base'] + ['motivo' => 'Error al guardar la devolución (revisa el formato de los datos)'];
			}
		}

		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});
		$_SESSION['cargaMasivaDevolucionesLog'] = $log;

		echo json_encode([
			'status' => true,
			'devueltos' => $devueltos,
			'sinCambios' => $sinCambios,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$devueltos} VIN(s) devuelto(s) (un movimiento de devolución cada uno), "
				. count($log) . " fila(s) no subida(s) y {$sinCambios} sin cambios, de " . count($rows) . " fila(s).",
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	public function exportarLogDevoluciones()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$this->enviarLog($_SESSION['cargaMasivaDevolucionesLog'] ?? [],
			'Log de VINs NO devueltos (Carga masiva de devoluciones)', 'Log_CargaMasiva_Devoluciones_');
	}

	/* ===============================
	   UNIDADES FACTURADAS (consulta)
	=============================== */

	/** Indicadores de la pantalla: disponibles, tatuadas, facturadas y facturadas del mes. */
	public function getResumen()
	{
		header('Content-Type: application/json; charset=utf-8');
		if (empty($_SESSION['permisosMod']['r'])) {
			echo json_encode(['status' => false, 'msg' => 'Sin permisos.'], JSON_UNESCAPED_UNICODE);
			die();
		}
		try {
			echo json_encode(['status' => true, 'data' => $this->model->selectResumen()], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			error_log('Inv_movventasmasiva::getResumen ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible obtener los indicadores.'], JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	/** Listado de VINs facturados con su última venta, filtrable por fecha de venta. */
	public function getFacturadas()
	{
		header('Content-Type: application/json; charset=utf-8');
		if (empty($_SESSION['permisosMod']['r'])) {
			echo json_encode(['status' => false, 'msg' => 'Sin permisos.', 'data' => []], JSON_UNESCAPED_UNICODE);
			die();
		}
		@set_time_limit(120);

		$desde = $this->fechaValida($_GET['desde'] ?? '');
		$hasta = $this->fechaValida($_GET['hasta'] ?? '');

		try {
			$data = [];
			foreach ($this->model->selectVinesFacturados($desde, $hasta) as $v) {
				$data[] = [
					'fecha_venta'    => (string) $v['fecha_venta'],
					'vin'            => (string) $v['numero_serie'],
					'numero_motor'   => (string) $v['numero_motor'],
					'sku'            => (string) $v['cve_articulo'],
					'descripcion'    => (string) $v['descripcion'],
					'planta'         => (string) $v['planta'],
					'almacen_salida' => (string) $v['almacen_salida'],
					'distribuidor'   => (string) $v['distribuidor'],
					'factura'        => (string) $v['factura'],
					'folio_venta'    => (string) $v['folio_venta'],
				];
			}
			echo json_encode(['status' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			error_log('Inv_movventasmasiva::getFacturadas ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible consultar las unidades facturadas.', 'data' => []], JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	private function fechaValida(string $f): ?string
	{
		$f = trim($f);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) ? $f : null;
	}

	/* ===============================
	   HELPERS PRIVADOS
	=============================== */

	private function etiquetaCliente(array $c): string
	{
		$cod = trim((string) ($c['codigo_cliente'] ?? ''));
		return $cod !== '' ? $cod . ' - ' . $c['nombre'] : (string) $c['nombre'];
	}

	/** "Facturado", "3" o "3 - Facturado" (layouts anteriores) -> '3'. */
	private function parsearEstatus(string $txt): ?string
	{
		$t = $this->normalizarTexto($txt);
		if ($t === '') {
			return null;
		}
		// "número - texto": el texto debe corresponder al número. Así una serie
		// generada al arrastrar la celda en Excel ("2 - Tatuado" -> "3 - Tatuado")
		// se rechaza en vez de leerse como otro estatus (3 = Facturado).
		if (preg_match('/^(\d+)\s*(?:-\s*(.*))?$/', $t, $m)) {
			$num = $m[1];
			$resto = trim($m[2] ?? '');
			if (!isset(self::ESTATUS[$num])) {
				return null;
			}
			if ($resto !== '' && $resto !== $this->normalizarTexto(self::ESTATUS[$num])) {
				return null;
			}
			return (string) $num;
		}
		foreach (self::ESTATUS as $k => $v) {
			if ($t === $this->normalizarTexto($v)) {
				return (string) $k;
			}
		}
		return null;
	}

	private function agregarValidacionLista($hoja, string $columna, int $desde, int $hasta, string $formula): void
	{
		for ($fila = $desde; $fila <= $hasta; $fila++) {
			$dv = $hoja->getCell($columna . $fila)->getDataValidation();
			$dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
			$dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
			$dv->setAllowBlank(true);
			$dv->setShowInputMessage(true);
			$dv->setShowErrorMessage(true);
			// OJO: en PhpSpreadsheet, showDropDown=true SÍ muestra la flecha (atributo invertido).
			$dv->setShowDropDown(true);
			$dv->setFormula1($formula);
		}
	}

	private function obtenerFilasArchivo(string $hoja = 'VINes', ?array $mapa = null, array $requeridas = ['vin', 'sku', 'nombre', 'estatus']): ?array
	{
		if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
			echo json_encode(['status' => false, 'msg' => 'Selecciona un archivo válido (.xlsx).'], JSON_UNESCAPED_UNICODE);
			return null;
		}
		$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, ['xlsx', 'xls'], true)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo debe ser un Excel (.xlsx).'], JSON_UNESCAPED_UNICODE);
			return null;
		}
		if ($_FILES['archivo']['size'] > 15 * 1024 * 1024) {
			echo json_encode(['status' => false, 'msg' => 'El archivo supera el límite de 15 MB.'], JSON_UNESCAPED_UNICODE);
			return null;
		}

		try {
			return $this->leerArchivo($_FILES['archivo']['tmp_name'], $hoja, $mapa, $requeridas);
		} catch (\Throwable $e) {
			error_log('Inv_movventasmasiva::leerArchivo: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible leer el archivo. Usa el layout descargado de esta pantalla (hoja "' . $hoja . '").'], JSON_UNESCAPED_UNICODE);
			return null;
		}
	}

	private function leerArchivo(string $tmpName, string $hoja, ?array $mapaParam, array $requeridas): array
	{
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
		$reader->setReadDataOnly(true);
		$spreadsheet = $reader->load($tmpName);

		$sheet = $spreadsheet->sheetNameExists($hoja)
			? $spreadsheet->getSheetByName($hoja)
			: $spreadsheet->getSheet(0);

		$highestRow = $sheet->getHighestDataRow();
		$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

		$mapa = $mapaParam ?? [
			'PLANTA' => 'planta',
			'ALMACEN' => 'almacen',
			'SKU' => 'sku',
			'NOMBRE' => 'nombre',
			'VIN' => 'vin',
			'NUMERO_MOTOR' => 'numero_motor',
			'ESTATUS' => 'estatus',
			'DISTRIBUIDOR' => 'cliente',
			'CLIENTE' => 'cliente',
			'FECHA_VENTA' => 'fecha_venta',
			'FACTURA' => 'factura',
		];

		$columnas = [];
		for ($col = 1; $col <= $highestColIndex; $col++) {
			$texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
			$clave = preg_replace('/\s+/', '_', strtoupper($this->quitarAcentos($texto)));
			if (isset($mapa[$clave])) {
				$columnas[$col] = $mapa[$clave];
			}
		}

		foreach ($requeridas as $req) {
			if (!in_array($req, $columnas, true)) {
				throw new \RuntimeException("Falta la columna obligatoria {$req}.");
			}
		}

		$rows = [];
		for ($fila = 2; $fila <= $highestRow; $fila++) {
			$reg = [];
			$vacio = true;
			foreach ($columnas as $col => $campo) {
				$reg[$campo] = $this->valorComoTexto($sheet->getCellByColumnAndRow($col, $fila)->getValue());
				if ($reg[$campo] !== '') {
					$vacio = false;
				}
			}
			if ($vacio) {
				continue;
			}
			$reg['_fila'] = $fila;
			$rows[] = $reg;
		}
		return $rows;
	}

	private function quitarAcentos(string $texto): string
	{
		$busca = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'];
		$reemplaza = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'];
		return str_replace($busca, $reemplaza, $texto);
	}

	/** Mayúsculas, sin acentos y espacios colapsados (para comparar sin falsos cambios). */
	private function normalizarTexto(string $texto): string
	{
		return preg_replace('/\s+/', ' ', strtoupper(trim($this->quitarAcentos($texto))));
	}

	private function valorComoTexto($valor): string
	{
		if ($valor === null) {
			return '';
		}
		if ($valor instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
			return trim($valor->getPlainText());
		}
		if (is_string($valor)) {
			return trim($valor);
		}
		if (is_int($valor)) {
			return (string) $valor;
		}
		if (is_float($valor)) {
			if (fmod($valor, 1.0) === 0.0 && abs($valor) < 1e15) {
				return sprintf('%.0f', $valor);
			}
			return rtrim(rtrim(sprintf('%.10f', $valor), '0'), '.');
		}
		return trim((string) $valor);
	}
}
