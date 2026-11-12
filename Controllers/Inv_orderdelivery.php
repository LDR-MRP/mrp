<?php
/**
 * ORDER DELIVERY PLAN (unidades en tránsito).
 *
 * Carga mensual del archivo "Order Delivery plan" (hoja Current order) mediante
 * una plantilla propia del módulo:
 *  - Columna A "PRODUCTO": lista desplegable con los productos tipo P activos
 *    (SKU | nombre comercial de Ingeniería). Liga cada renglón con wms_inventario.
 *  - Contract No., QTY, ETA at POD y Status, tomados del archivo original.
 * - Cada renglón se registra en el almacén de tránsito (ALM-TRANSITO) por defecto.
 * - Cargas INCREMENTALES: cada archivo se suma a lo ya cargado (no reemplaza).
 *   Cada renglón guarda el MES EN CURSO al subirlo para segmentar. Una carga
 *   equivocada se deshace eliminándola completa.
 * - NO mueve inventario (sin entradas en wms_multialmacen / kardex).
 *
 * Permiso propio: módulo 88 (MIORDENDELIVERY).
 */
class Inv_orderdelivery extends Controllers
{
	private const MODULO = 88;
	private const HOJA = 'OrderDeliveryPlan';
	private const HOJA_PRODUCTOS = 'Productos';
	private const HOJA_CATALOGOS = 'Catalogos';
	private const MARCA = 'PLANTILLA_ORDER_DELIVERY_PLAN_V1';
	private const LOG = 'cargaOdpLog';
	private const FILAS_PLANTILLA = 1500;
	private const SEP = ' | ';
	private const SCRIPT = 'Docs/desarrollo/order_delivery/migracion_order_delivery_plan_20261112.sql';

	/** Encabezado de la plantilla => campo (en este orden). */
	private const ENCABEZADOS = [
		'PRODUCTO (SKU | NOMBRE COMERCIAL)' => 'producto',
		'Modelo' => 'modelo',
		'Contract No.' => 'contrato',
		'QTY' => 'qty',
		'ETA at POD' => 'eta_pod',
		'Status' => 'status',
	];
	/** Encabezados alternos aceptados al leer (normalizados). */
	private const ALIAS = [
		'PRODUCTO' => 'producto', 'SKU' => 'producto',
		'CONTRACT' => 'contrato', 'CONTRACTNO' => 'contrato', 'CONTRATO' => 'contrato',
		'ETA' => 'eta_pod', 'ETAPOD' => 'eta_pod', 'CANTIDAD' => 'qty', 'ESTATUS' => 'status', 'MODEL' => 'modelo',
	];
	private const ANCHOS = [52, 30, 20, 8, 14, 30];

	private const LARGOS = ['contrato' => 60, 'modelo' => 150, 'eta_pod_txt' => 60, 'status' => 100, 'sku' => 100, 'nombre_comercial' => 200];

	/** Índice de columna del DataTable => columna SQL. */
	private const ORDEN = [
		0 => 'r.nombre_comercial', 1 => 'r.modelo', 2 => 'r.contrato', 3 => 'r.qty', 4 => 'r.eta_pod', 5 => 'r.status',
	];

	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(defined('MIORDENDELIVERY') ? MIORDENDELIVERY : self::MODULO);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_orderdelivery()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}
		$data['page_tag'] = "Order delivery plan";
		$data['page_title'] = "Order delivery plan · Unidades en tránsito";
		$data['page_name'] = "orderdelivery";
		$data['page_functions_js'] = "functions_inv_orderdelivery.js";
		$data['tablas_listas'] = $this->model->tablasListas();
		$data['almacen_transito'] = $data['tablas_listas'] ? $this->model->selectAlmacenTransito() : null;
		$data['script'] = self::SCRIPT;
		$this->views->getView($this, "inv_orderdelivery", $data);
	}

	/* ===============================
	   CONSULTAS (JSON)
	=============================== */
	/** Meses disponibles + catálogos de filtros del mes pedido (o el más reciente). */
	public function getFiltros()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		if (!$this->model->tablasListas()) {
			$this->json(['meses' => [], 'mes' => '', 'status' => [], 'productos' => []]);
		}
		$meses = $this->model->selectMeses();
		$mes = $this->mesRequest($_GET);   // '' = todos los meses
		if ($mes !== '' && !in_array($mes, $meses, true)) {
			$mes = '';
		}
		$this->json(['meses' => $meses, 'mes' => $mes] + $this->model->selectCatalogosFiltro($mes));
	}

	public function getListado()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$req = $_POST + $_GET;
		$f = $this->filtrosRequest($req);
		if ($f === null || !$this->model->tablasListas()) {
			$this->json(['draw' => (int) ($req['draw'] ?? 0), 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
		}
		$f['search'] = mb_substr(trim((string) ($req['search']['value'] ?? '')), 0, 100);
		// Sin orden elegido: el mismo orden del archivo
		$idx = isset($req['order'][0]['column']) ? (int) $req['order'][0]['column'] : -1;
		$f['order_col'] = self::ORDEN[$idx] ?? 'r.idodp';
		$f['order_dir'] = strtolower((string) ($req['order'][0]['dir'] ?? 'asc')) === 'desc' ? 'DESC' : 'ASC';
		$f['start'] = (int) ($req['start'] ?? 0);
		$f['length'] = (int) ($req['length'] ?? 25);
		$res = $this->model->selectPaginado($f);
		$this->json([
			'draw' => (int) ($req['draw'] ?? 0),
			'recordsTotal' => $res['recordsTotal'],
			'recordsFiltered' => $res['recordsFiltered'],
			'data' => $res['data'],
		]);
	}

	public function getKpis()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$f = $this->filtrosRequest($_GET);
		if ($f === null || !$this->model->tablasListas()) {
			$this->json(['renglones' => 0, 'unidades' => 0, 'contratos' => 0, 'productos' => 0, 'con_eta' => 0, 'eta_futura' => 0, 'proxima_eta' => '']);
		}
		$this->json($this->model->selectKpis($f));
	}

	public function getResumen()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$f = $this->filtrosRequest($_GET);
		if ($f === null || !$this->model->tablasListas()) {
			$this->json(['status' => [], 'filas' => []]);
		}
		$this->json($this->model->selectResumen($f));
	}

	public function getCargas()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$this->json($this->model->tablasListas() ? $this->model->selectCargas() : []);
	}

	public function eliminarCarga()
	{
		if (empty($_SESSION['permisosMod']['d'])) {
			$this->json(['status' => false, 'msg' => 'No tienes permisos para eliminar cargas.']);
		}
		$id = (int) ($_POST['idcarga'] ?? 0);
		$carga = $id > 0 ? $this->model->selectCarga($id) : null;
		if (!$carga || (int) $carga['estado'] !== 2) {
			$this->json(['status' => false, 'msg' => 'La carga no existe o ya fue eliminada.']);
		}
		try {
			$borrados = $this->model->eliminarCarga($id);
		} catch (\Throwable $e) {
			error_log('Inv_orderdelivery::eliminarCarga: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'No fue posible eliminar la carga.']);
		}
		$this->json(['status' => true, 'msg' => "Carga {$carga['folio']} eliminada: se borraron {$borrados} renglón(es) que seguían vigentes."]);
	}

	/* ===============================
	   CARGA DEL ARCHIVO
	=============================== */
	public function procesarCarga()
	{
		if (empty($_SESSION['permisosMod']['w'])) {
			$this->json(['status' => false, 'msg' => 'No tienes permisos para subir el order delivery plan.']);
		}
		if (!$this->model->tablasListas()) {
			$this->json(['status' => false, 'msg' => 'Faltan las tablas del módulo. Corre el script ' . self::SCRIPT . '.']);
		}
		$almacen = $this->model->selectAlmacenTransito();
		if (!$almacen) {
			$this->json(['status' => false, 'msg' => 'No existe el almacén de tránsito (' . Inv_orderdeliveryModel::CVE_ALMACEN_TRANSITO . ').']);
		}
		@set_time_limit(600);
		@ini_set('memory_limit', '512M');

		$observaciones = mb_substr(trim(strip_tags((string) ($_POST['observaciones'] ?? ''))), 0, 255);
		[$hoja, $rows] = $this->obtenerFilasArchivo();
		$res = $this->validarFilas($rows, $this->model->mapProductos(), (int) $almacen['idalmacen']);
		$filas = $res['filas'];
		$log = $res['log'];

		// Carga incremental: avisa (sin bloquear) de renglones iguales a otros ya cargados
		$existentes = $this->model->selectLlavesExistentes();
		foreach ($filas as $f) {
			$llave = $f['inventarioid'] . '|' . mb_strtoupper((string) $f['contrato']) . '|' . $f['qty'] . '|'
				. mb_strtoupper((string) ($f['eta_pod'] ?? $f['eta_pod_txt'] ?? ''));
			if (isset($existentes[$llave])) {
				$log[] = ['fila' => $f['fila_archivo'], 'contrato' => (string) $f['contrato'], 'producto' => $f['sku'],
					'tipo' => 'Aviso', 'motivo' => "Posible duplicado: ya hay un renglón con el mismo producto, contrato, QTY y ETA (carga {$existentes[$llave]}); se cargó de todos modos"];
			}
		}
		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});

		$omitidas = count(array_filter($log, function ($l) { return $l['tipo'] === 'No cargada'; }));
		$avisos = count($log) - $omitidas;
		$_SESSION[self::LOG] = $log;
		$mes = date('Y-m') . '-01';
		$mesTxt = $this->mesLargo($mes);

		if (empty($filas)) {
			$this->json([
				'status' => true, 'cargados' => 0, 'omitidos' => $omitidas, 'avisos' => $avisos,
				'msg' => 'No se cargó nada: ninguna fila tiene PRODUCTO y QTY válidos. Revisa el log.',
			]);
		}

		try {
			$r = $this->model->insertCarga([
				'archivo' => mb_substr(basename((string) $_FILES['archivo']['name']), 0, 255),
				'hoja' => $hoja,
				'total_filas' => count($rows),
				'total_omitidas' => $omitidas,
				'observaciones' => $observaciones !== '' ? $observaciones : null,
				'usuarioid' => !empty($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : null,
			], $filas, $mes);
		} catch (\Throwable $e) {
			error_log('Inv_orderdelivery::procesarCarga: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'Error al guardar la carga; no se modificó nada. Detalle en el log del servidor.']);
		}

		$unidades = array_sum(array_column($filas, 'qty'));
		$msg = "Carga {$r['folio']} ({$mesTxt}): " . number_format(count($filas)) . ' renglón(es), '
			. number_format($unidades) . " unidad(es) agregadas a {$almacen['cve_almacen']}.";
		if ($omitidas > 0) {
			$msg .= " {$omitidas} fila(s) no se cargaron.";
		}
		if ($avisos > 0) {
			$msg .= " {$avisos} aviso(s).";
		}
		$this->json([
			'status' => true, 'folio' => $r['folio'], 'mes' => substr($mes, 0, 7), 'cargados' => count($filas),
			'unidades' => $unidades, 'omitidos' => $omitidas,
			'avisos' => $avisos, 'msg' => $msg,
		]);
	}

	/**
	 * Valida y arma los renglones. Regresa ['filas' => [...], 'log' => [...]].
	 */
	private function validarFilas(array $rows, array $productos, int $almacenid): array
	{
		$log = [];
		$filas = [];
		$porFila = [];   // fila => ['contrato', 'producto'] ya resueltos (para las celdas combinadas)

		foreach ($rows as $row) {
			$fila = $row['_fila'];
			$prodTxt = trim((string) ($row['producto'] ?? ''));
			$contrato = trim((string) ($row['contrato'] ?? ''));
			$modelo = trim((string) ($row['modelo'] ?? ''));
			$status = trim((string) ($row['status'] ?? ''));
			$qtyTxt = $this->valorComoTexto($row['qty_raw'] ?? null);
			$etaTxt = $this->valorComoTexto($row['eta_pod_raw'] ?? null);
			$base = ['fila' => $fila, 'contrato' => $contrato, 'producto' => $prodTxt];

			// Subtotales / renglones sin contenido
			if ($prodTxt === '' && $contrato === '' && $modelo === '' && $status === '' && $etaTxt === '') {
				if ($qtyTxt !== '') {
					$log[] = $base + ['tipo' => 'Aviso', 'motivo' => "Renglón de subtotal (solo QTY {$qtyTxt}); se ignoró"];
				}
				continue;
			}

			// Contrato partido en varios renglones: la celda de Contract No. está combinada con la de
			// arriba => toma el contrato (y el PRODUCTO si va vacío) del primer renglón de la combinación.
			$heredados = [];
			$origen = $row['_contrato_de'] ?? null;
			if ($origen !== null && isset($porFila[$origen])) {
				if ($contrato === '' && $porFila[$origen]['contrato'] !== '') {
					$contrato = $porFila[$origen]['contrato'];
					$heredados[] = 'Contract No.';
				}
				if ($modelo === '' && $porFila[$origen]['modelo'] !== '') {
					$modelo = $porFila[$origen]['modelo'];
					$heredados[] = 'Modelo';
				}
				if ($prodTxt === '' && $porFila[$origen]['producto'] !== '') {
					$prodTxt = $porFila[$origen]['producto'];
					$heredados[] = 'PRODUCTO';
				}
				$base['contrato'] = $contrato;
				$base['producto'] = $prodTxt;
			}
			$porFila[$fila] = ['contrato' => $contrato, 'modelo' => $modelo, 'producto' => $prodTxt];

			if ($prodTxt === '') {
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => 'Falta el PRODUCTO (elígelo de la lista)'];
				continue;
			}
			$sku = mb_strtoupper(trim(explode(trim(self::SEP), $prodTxt, 2)[0]));
			$prod = $productos[$sku] ?? null;
			if ($prod === null) {
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => "El PRODUCTO \"{$prodTxt}\" no existe en el catálogo (productos tipo P activos)"];
				continue;
			}
			if ($qtyTxt === '') {
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => 'Falta la QTY'];
				continue;
			}
			$qtyNum = is_numeric($qtyTxt) ? (float) $qtyTxt : null;
			if ($qtyNum === null || $qtyNum <= 0 || floor($qtyNum) != $qtyNum || $qtyNum > 100000) {
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => "QTY \"{$qtyTxt}\" debe ser un número entero mayor a 0"];
				continue;
			}
			if ($heredados) {
				$log[] = $base + ['tipo' => 'Aviso', 'motivo' => 'Celdas combinadas: se tomó ' . implode(', ', $heredados) . " del renglón {$origen} (Contract No. combinado)"];
			}
			if ($contrato === '') {
				$log[] = $base + ['tipo' => 'Aviso', 'motivo' => 'Renglón sin Contract No.; se cargó sin contrato'];
			}

			$eta = $this->parseFecha($row['eta_pod_raw'] ?? null);
			$reg = [
				'inventarioid' => (int) $prod['idinventario'],
				'almacenid' => $almacenid,
				'sku' => $prod['sku'],
				'nombre_comercial' => $prod['nombre_comercial'],
				'contrato' => $contrato === '' ? null : $contrato,
				'modelo' => $modelo === '' ? null : $modelo,
				'qty' => (int) $qtyNum,
				'eta_pod' => $eta['date'],
				'eta_pod_txt' => $eta['txt'],
				'status' => $status === '' ? null : $status,
				'fila_archivo' => $fila,
			];
			foreach (self::LARGOS as $c => $max) {
				if (isset($reg[$c]) && is_string($reg[$c]) && mb_strlen($reg[$c]) > $max) {
					$reg[$c] = mb_substr($reg[$c], 0, $max);
				}
			}
			$filas[] = $reg;
		}
		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});
		return ['filas' => $filas, 'log' => $log];
	}

	/* ===============================
	   EXCEL: PLANTILLA / EXPORTAR / LOG
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$ss = $this->construirPlantilla($this->model->selectProductos(), []);
		$this->enviarXlsx($ss, 'Plantilla_Order_Delivery_Plan_' . date('Ymd') . '.xlsx');
	}

	/** Exporta lo filtrado del mes en formato plantilla (se puede corregir y volver a subir). */
	public function exportar()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		@set_time_limit(300);
		$f = $this->filtrosRequest($_GET);
		$rows = [];
		if ($f !== null && $this->model->tablasListas()) {
			$f['search'] = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
			$rows = $this->model->selectExport($f);
		}
		$ss = $this->construirPlantilla($this->model->selectProductos(), $rows);
		$this->enviarXlsx($ss, 'Order_Delivery_Plan_' . (($f['mes'] ?? '') !== '' ? str_replace('-', '', $f['mes']) . '_' : '') . date('Ymd_His') . '.xlsx');
	}

	/**
	 * Plantilla: hoja de captura (PRODUCTO con lista + columnas del Current order),
	 * "Instrucciones", "Productos" (catálogo de la lista) y "Catalogos" oculta con la marca.
	 */
	private function construirPlantilla(array $productos, array $rows)
	{
		$CO = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::class;
		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
		$campos = array_values(self::ENCABEZADOS);
		$col = function (string $campo) use ($campos, $CO): string {
			return $CO::stringFromColumnIndex(array_search($campo, $campos, true) + 1);
		};
		$hasta = max(self::FILAS_PLANTILLA, count($rows) + 300);

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()->setCreator('LDR Solutions · MRP')->setTitle('Plantilla Order Delivery Plan');

		/* ---------- Productos (fuente de la lista) ---------- */
		$hp = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($ss, self::HOJA_PRODUCTOS);
		$ss->addSheet($hp);
		$hp->fromArray(['PRODUCTO (elige este texto)', 'SKU', 'NOMBRE COMERCIAL', 'DESCRIPCIÓN INVENTARIO', 'INGENIERÍA'], null, 'A1');
		$hp->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$hp->getStyle('A1:E1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF1F4E78');
		$etiquetas = [];
		$r = 2;
		foreach ($productos as $p) {
			$etq = $this->etiquetaProducto($p['sku'], $p['nombre_comercial']);
			$etiquetas[(int) $p['idinventario']] = $etq;
			$hp->setCellValueExplicit('A' . $r, $etq, $T);
			$hp->setCellValueExplicit('B' . $r, (string) $p['sku'], $T);
			$hp->setCellValueExplicit('C' . $r, (string) $p['nombre_comercial'], $T);
			$hp->setCellValueExplicit('D' . $r, (string) $p['descripcion'], $T);
			$hp->setCellValue('E' . $r, (int) $p['con_ingenieria'] === 1 ? 'Sí' : 'No');
			$r++;
		}
		$ultProd = max(2, $r - 1);
		foreach (['A' => 60, 'B' => 28, 'C' => 42, 'D' => 50, 'E' => 12] as $c => $w) {
			$hp->getColumnDimension($c)->setWidth($w);
		}
		$hp->freezePane('A2');
		$hp->setAutoFilter('A1:E' . $ultProd);

		/* ---------- Marca de plantilla (oculta) ---------- */
		$hc = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($ss, self::HOJA_CATALOGOS);
		$ss->addSheet($hc);
		$hc->setCellValue('A1', self::MARCA);
		$hc->setCellValue('B1', 'No modificar ni borrar esta hoja');
		$hc->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

		/* ---------- Hoja de captura ---------- */
		$sh = $ss->getSheet(0);
		$sh->setTitle(self::HOJA);
		$enc = array_keys(self::ENCABEZADOS);
		$ult = $CO::stringFromColumnIndex(count($enc));
		$sh->fromArray($enc, null, 'A1');
		$sh->getStyle("A1:{$ult}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$sh->getStyle("A1:{$ult}1")->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF1F4E78');
		$sh->getStyle("A1:{$ult}1")->getAlignment()->setWrapText(true)->setVertical('center');
		foreach (['producto', 'qty'] as $c) {     // obligatorias en verde
			$sh->getStyle($col($c) . '1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF198754');
		}
		$sh->getRowDimension(1)->setRowHeight(32);
		$sh->getStyle('A2:A' . $hasta)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFE2EFDA');
		$sh->freezePane('A2');
		foreach (self::ANCHOS as $i => $w) {
			$sh->getColumnDimension($CO::stringFromColumnIndex($i + 1))->setWidth($w);
		}
		$sh->getStyle('A2:' . $ult . $hasta)->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		$sh->getStyle($col('qty') . '2:' . $col('qty') . $hasta)->getNumberFormat()->setFormatCode('0');
		$sh->getStyle($col('eta_pod') . '2:' . $col('eta_pod') . $hasta)->getNumberFormat()->setFormatCode('dd/mm/yyyy');

		// Lista desplegable de productos
		$formula = "'" . self::HOJA_PRODUCTOS . "'!\$A\$2:\$A\$" . $ultProd;
		for ($fila = 2; $fila <= $hasta; $fila++) {
			$dv = $sh->getCell('A' . $fila)->getDataValidation();
			$dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
			$dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
			$dv->setAllowBlank(true);
			$dv->setShowErrorMessage(true);
			$dv->setErrorTitle('Producto no válido');
			$dv->setError('Elige un producto de la lista (hoja Productos).');
			// En PhpSpreadsheet showDropDown=true SÍ muestra la flecha en Excel (atributo invertido).
			$dv->setShowDropDown(true);
			$dv->setFormula1($formula);
		}

		// Datos (exportación)
		$r = 2;
		foreach ($rows as $row) {
			foreach ($campos as $i => $campo) {
				$cell = $CO::stringFromColumnIndex($i + 1) . $r;
				if ($campo === 'producto') {
					$etq = $etiquetas[(int) $row['inventarioid']] ?? $this->etiquetaProducto($row['sku'], $row['nombre_comercial']);
					$sh->setCellValueExplicit($cell, $etq, $T);
					continue;
				}
				$v = $row[$campo] ?? null;
				if ($v === null || $v === '') {
					continue;
				}
				if ($campo === 'qty') {
					$sh->setCellValue($cell, (int) $v);
				} elseif ($campo === 'eta_pod' && preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $v)) {
					$sh->setCellValue($cell, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($v)));
				} else {
					$sh->setCellValueExplicit($cell, (string) $v, $T);
				}
			}
			$r++;
		}

		/* ---------- Instrucciones ---------- */
		$ins = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($ss, 'Instrucciones');
		$ss->addSheet($ins, 1);
		$lineas = [
			'PLANTILLA ORDER DELIVERY PLAN (UNIDADES EN TRÁNSITO)',
			'',
			'1. Solo se acepta ESTA plantilla (descargada del módulo). No cambies el nombre de la hoja "' . self::HOJA . '" ni sus encabezados.',
			'2. Columna A (verde): elige el PRODUCTO de la lista para cada renglón (productos tipo P activos; hoja "Productos": SKU y nombre comercial de Ingeniería).',
			'3. Columnas B a F: Modelo (texto libre), Contract No., QTY, ETA at POD y Status (copia del archivo Order Delivery plan, hoja Current order).',
			'4. Obligatorias por renglón (encabezado verde): PRODUCTO y QTY (entero mayor a 0). Modelo, Contract No., ETA at POD y Status son opcionales.',
			'5. Contrato partido en varios renglones: si pegas Contract No. con sus celdas combinadas (como viene en el Current order), los renglones de abajo toman el contrato y, si van vacíos, el Modelo y el PRODUCTO del primero. Sin celda combinada no se hereda nada.',
			'6. Los renglones de SUBTOTAL (solo traen QTY) se ignoran solos; no hace falta borrarlos.',
			'7. ETA at POD: se reconocen fechas de Excel, dd/mm/aaaa y aaaa.mm.dd. Valores como TBD se guardan como texto.',
			'8. Cada renglón queda en el almacén de tránsito (ALM-TRANSITO) con el MES EN CURSO como mes de carga. No mueve existencias.',
			'9. Las cargas SE SUMAN: cada archivo agrega sus renglones a los ya cargados. Sube solo lo nuevo; si un renglón es igual a uno ya cargado verás un aviso de posible duplicado.',
			'10. Las filas que no se puedan cargar y los avisos quedan en un log descargable.',
			'11. Para corregir una carga equivocada, elimínala en "Historial de cargas" y sube el archivo corregido. "Exportar" descarga lo filtrado en este formato (no lo vuelvas a subir completo: se duplicaría).',
			'12. No borres las hojas "Productos" ni la hoja oculta "Catalogos".',
		];
		foreach ($lineas as $i => $txt) {
			$ins->setCellValue('A' . ($i + 1), $txt);
		}
		$ins->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$ins->getColumnDimension('A')->setWidth(160);

		$ss->setActiveSheetIndex(0);
		return $ss;
	}

	public function exportarLog()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sh = $ss->getActiveSheet();
		$sh->setTitle('Log');
		$sh->setCellValue('A1', 'Log de carga del Order Delivery Plan');
		$sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sh->fromArray(['FILA_ARCHIVO', 'TIPO', 'CONTRACT NO.', 'PRODUCTO', 'MOTIVO'], null, 'A4');
		$sh->getStyle('A4:E4')->getFont()->setBold(true);
		$sh->freezePane('A5');
		$r = 5;
		foreach ($_SESSION[self::LOG] ?? [] as $it) {
			$sh->setCellValue('A' . $r, $it['fila'] ?? '');
			$sh->setCellValueExplicit('B' . $r, (string) ($it['tipo'] ?? ''), $T);
			$sh->setCellValueExplicit('C' . $r, (string) ($it['contrato'] ?? ''), $T);
			$sh->setCellValueExplicit('D' . $r, (string) ($it['producto'] ?? ''), $T);
			$sh->setCellValueExplicit('E' . $r, (string) ($it['motivo'] ?? ''), $T);
			$r++;
		}
		foreach (['A' => 14, 'B' => 12, 'C' => 20, 'D' => 50, 'E' => 100] as $c => $w) {
			$sh->getColumnDimension($c)->setWidth($w);
		}
		$this->enviarXlsx($ss, 'Log_Order_Delivery_Plan_' . date('Ymd_His') . '.xlsx');
	}

	/* ===============================
	   HELPERS PRIVADOS
	=============================== */
	private function json($data): void
	{
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($data, JSON_UNESCAPED_UNICODE);
		die();
	}

	private function etiquetaProducto($sku, $nombre): string
	{
		$sku = trim((string) $sku);
		$nombre = trim(preg_replace('/\s+/u', ' ', (string) $nombre));
		return $nombre !== '' ? $sku . self::SEP . $nombre : $sku;
	}

	private function mesRequest(array $req): string
	{
		$v = trim((string) ($req['f_mes'] ?? ''));
		return preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $v) ? $v : '';
	}

	/** Filtros del listado (mes '' = todos los meses). */
	private function filtrosRequest(array $req): ?array
	{
		return [
			'mes' => $this->mesRequest($req),
			'status' => mb_substr(trim((string) ($req['f_status'] ?? '')), 0, 100),
			'inventarioid' => (int) ($req['f_inventarioid'] ?? 0),
		];
	}

	private function mesLargo(string $ymd): string
	{
		$m = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
		return $m[(int) substr($ymd, 5, 2) - 1] . ' ' . substr($ymd, 0, 4);
	}

	private function enviarXlsx($ss, string $filename): void
	{
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

	/** Lee el Excel subido; si algo falla responde JSON y termina. Regresa [hoja, filas]. */
	private function obtenerFilasArchivo(): array
	{
		if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
			$this->json(['status' => false, 'msg' => 'Selecciona un archivo válido (.xlsx).']);
		}
		$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, ['xlsx', 'xls'], true)) {
			$this->json(['status' => false, 'msg' => 'El archivo debe ser un Excel (.xlsx).']);
		}
		if ($_FILES['archivo']['size'] > 20 * 1024 * 1024) {
			$this->json(['status' => false, 'msg' => 'El archivo supera el límite de 20 MB.']);
		}
		try {
			$res = $this->leerArchivo($_FILES['archivo']['tmp_name']);
		} catch (\Throwable $e) {
			error_log('Inv_orderdelivery::leerArchivo: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'No fue posible leer el archivo: ' . $e->getMessage()]);
		}
		if (empty($res[1])) {
			$this->json(['status' => false, 'msg' => 'La hoja "' . self::HOJA . '" no contiene renglones para procesar.']);
		}
		return $res;
	}

	/**
	 * Valida que sea la plantilla (marca en Catalogos!A1), lee la hoja de captura
	 * y mapea columnas por encabezado. QTY y fechas quedan en crudo (*_raw).
	 */
	private function leerArchivo(string $tmpName): array
	{
		$mapa = self::ALIAS;
		foreach (self::ENCABEZADOS as $enc => $campo) {
			$mapa[$this->normalizar($enc)] = $campo;
		}
		$crudos = ['qty', 'eta_pod'];

		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
		// Sin readDataOnly para conocer las celdas combinadas del Contract No.
		$reader->setReadDataOnly(false);
		$nombres = $reader->listWorksheetNames($tmpName);
		$noEsPlantilla = new \RuntimeException('el archivo no es la plantilla del módulo. Descarga la plantilla, elige el PRODUCTO de cada renglón, copia Modelo, Contract No., QTY, ETA at POD y Status, y súbela.');
		if (!in_array(self::HOJA_CATALOGOS, $nombres, true) || !in_array(self::HOJA, $nombres, true)) {
			throw $noEsPlantilla;
		}
		$reader->setLoadSheetsOnly([self::HOJA, self::HOJA_CATALOGOS]);
		$spreadsheet = $reader->load($tmpName);
		if (trim((string) $spreadsheet->getSheetByName(self::HOJA_CATALOGOS)->getCell('A1')->getValue()) !== self::MARCA) {
			throw $noEsPlantilla;
		}
		$sheet = $spreadsheet->getSheetByName(self::HOJA);
		$datos = $sheet->rangeToArray('A1:' . $sheet->getHighestDataColumn() . $sheet->getHighestDataRow(), null, false, false, false);
		$combinadas = [];   // [colIni, filaIni, colFin, filaFin] (base 0 / base 1)
		foreach (array_keys($sheet->getMergeCells()) as $rango) {
			[$a, $b] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::rangeBoundaries($rango);
			$combinadas[] = [$a[0] - 1, $a[1], $b[0] - 1, $b[1]];
		}
		// Fórmulas (p. ej. QTY =SUM(...) o =30+30 al pegar del archivo original): se usa su resultado
		foreach ($datos as $i => $fila) {
			foreach ($fila as $c => $v) {
				if (is_string($v) && strlen($v) > 1 && $v[0] === '=') {
					$cell = $sheet->getCell(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c + 1) . ($i + 1));
					try {
						$datos[$i][$c] = $cell->getCalculatedValue();
					} catch (\Throwable $e) {
						$datos[$i][$c] = $cell->getOldCalculatedValue();
					}
				}
			}
		}
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);

		$filaEnc = null;
		$columnas = [];
		for ($i = 0; $i < min(5, count($datos)); $i++) {
			$cols = [];
			foreach ($datos[$i] as $c => $texto) {
				$clave = $this->normalizar((string) $texto);
				if ($clave !== '' && isset($mapa[$clave]) && !in_array($mapa[$clave], $cols, true)) {
					$cols[$c] = $mapa[$clave];
				}
			}
			if (in_array('producto', $cols, true) && in_array('qty', $cols, true)) {
				$filaEnc = $i;
				$columnas = $cols;
				break;
			}
		}
		if ($filaEnc === null) {
			throw new \RuntimeException('en la hoja "' . self::HOJA . '" no se encontraron los encabezados PRODUCTO y QTY.');
		}

		// Renglón => renglón de arriba con el que comparte la celda combinada de Contract No.
		$colContrato = array_search('contrato', $columnas, true);
		$origenContrato = [];
		if ($colContrato !== false) {
			foreach ($combinadas as [$c1, $r1, $c2, $r2]) {
				if ($colContrato >= $c1 && $colContrato <= $c2) {
					for ($r = $r1 + 1; $r <= $r2; $r++) {
						$origenContrato[$r] = $r1;
					}
				}
			}
		}

		$rows = [];
		for ($i = $filaEnc + 1, $n = count($datos); $i < $n; $i++) {
			$reg = [];
			$vacio = true;
			foreach ($columnas as $c => $campo) {
				$raw = $datos[$i][$c] ?? null;
				if (in_array($campo, $crudos, true)) {
					$reg[$campo . '_raw'] = $raw;
				} else {
					$reg[$campo] = $this->valorComoTexto($raw);
				}
				if ($this->valorComoTexto($raw) !== '') {
					$vacio = false;
				}
			}
			if ($vacio) {
				continue;
			}
			$reg['_fila'] = $i + 1;
			$reg['_contrato_de'] = $origenContrato[$i + 1] ?? null;
			$rows[] = $reg;
		}
		return [self::HOJA, $rows];
	}

	private function normalizar(string $texto): string
	{
		return preg_replace('/[^A-Z0-9]/', '', strtoupper($this->quitarAcentos(trim($texto))));
	}

	/**
	 * Fecha de Excel (serial o texto) => ['date' => 'Y-m-d'|null, 'txt' => original|null].
	 * Acepta serial de Excel, Y-m-d, Y.m.d, Y/m/d, d/m/Y y d-m-Y. Lo demás regresa en txt (TBD, /).
	 */
	private function parseFecha($raw): array
	{
		if ($raw === null || (is_string($raw) && trim($raw) === '')) {
			return ['date' => null, 'txt' => null];
		}
		if ($raw instanceof \DateTimeInterface) {
			return ['date' => $raw->format('Y-m-d'), 'txt' => null];
		}
		if (is_int($raw) || is_float($raw) || (is_string($raw) && is_numeric(trim($raw)))) {
			$num = (float) $raw;
			if ($num >= 32874 && $num <= 73415) {   // 1990-01-01 .. 2100-12-31
				return ['date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($num)->format('Y-m-d'), 'txt' => null];
			}
			return ['date' => null, 'txt' => mb_substr($this->valorComoTexto($raw), 0, 60)];
		}
		$s = trim(preg_replace('/\s+/u', ' ', (string) $raw));
		$s1 = trim(preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?$/', '', $s));
		if (preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})$/', $s1, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
			return ['date' => sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]), 'txt' => null];
		}
		if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2}|\d{4})$/', $s1, $m)) {
			$a = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];
			if (checkdate((int) $m[2], (int) $m[1], $a)) {
				return ['date' => sprintf('%04d-%02d-%02d', $a, $m[2], $m[1]), 'txt' => null];
			}
			if (checkdate((int) $m[1], (int) $m[2], $a)) {
				return ['date' => sprintf('%04d-%02d-%02d', $a, $m[1], $m[2]), 'txt' => null];
			}
		}
		return ['date' => null, 'txt' => mb_substr($s, 0, 60)];
	}

	private function quitarAcentos(string $texto): string
	{
		$busca = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ', 'ü', 'Ü'];
		$reemplaza = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N', 'u', 'U'];
		return str_replace($busca, $reemplaza, $texto);
	}

	private function valorComoTexto($valor): string
	{
		if ($valor === null) {
			return '';
		}
		if ($valor instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) {
			return trim($valor->getPlainText());
		}
		if (is_bool($valor)) {
			return $valor ? '1' : '0';
		}
		if (is_string($valor)) {
			return trim(preg_replace('/\s+/u', ' ', $valor));
		}
		if (is_int($valor)) {
			return (string) $valor;
		}
		if (is_float($valor)) {
			if (fmod($valor, 1.0) === 0.0 && abs($valor) < 1e15) {
				return sprintf('%.0f', $valor);
			}
			return rtrim(rtrim(sprintf('%.6f', $valor), '0'), '.');
		}
		return trim((string) $valor);
	}
}
