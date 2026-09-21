<?php
class Inv_movcargamasiva extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		// Reutiliza el permiso del módulo Movimientos Inventario: esta
		// pantalla es una extensión de Inv_movimientosinventario, no un
		// módulo con menú propio.
		getPermisos(MIMOVIMIENTOS);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_movcargamasiva()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Carga Masiva de Movimientos";
		$data['page_title'] = "Carga Masiva de Movimientos";
		$data['page_name'] = "movcargamasiva";
		$data['page_functions_js'] = "functions_inv_movcargamasiva.js";
		$this->views->getView($this, "inv_movcargamasiva", $data);
	}

	/* ===============================
	   PLANTILLA DE LLENADO (XLSX)
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$conceptos = $this->model->selectConceptosProveedor();
		$almacenes = $this->model->selectAlmacenesActivos();
		$proveedores = $this->model->selectProveedoresActivos();

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()
			->setCreator('LDR Solutions · MRP')
			->setTitle('Plantilla Carga Masiva Movimientos')
			->setDescription('Plantilla para alta masiva de movimientos de inventario (conceptos tipo Proveedor)');

		/* ---------- HOJA 1: INSTRUCCIONES ---------- */
		$hoja1 = $ss->getActiveSheet();
		$hoja1->setTitle('Instrucciones');

		$hoja1->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA · MOVIMIENTOS DE INVENTARIO');
		$hoja1->mergeCells('A1:C1');
		$hoja1->getStyle('A1')->getFont()->setBold(true)->setSize(14);

		$hoja1->setCellValue('A3', '1. Llena la hoja "Movimientos" sin modificar los encabezados de la fila 1.');
		$hoja1->setCellValue('A4', '2. Borra la fila de ejemplo (fila 2) antes de subir el archivo.');
		$hoja1->setCellValue('A5', '3. Esta carga solo admite conceptos que requieren Proveedor (ej. Compras). En CONCEPTO, ALMACEN y PROVEEDOR escribe el texto tal como aparece en sus hojas de referencia (tienen lista desplegable para elegirlo sin errores).');
		$hoja1->setCellValue('A6', '4. Cada fila del archivo genera un movimiento INDEPENDIENTE (con su propio número de movimiento). No se agrupan varias filas en un mismo movimiento.');
		$hoja1->setCellValue('A7', '5. CLAVE_ARTICULO debe existir y estar activa en el catálogo de inventario. CANTIDAD debe ser mayor a 0.');
		$hoja1->setCellValue('A8', '6. COSTO_UNITARIO, REFERENCIA y LOTE son opcionales; si se dejan vacíos, el costo se guarda como 0 y referencia/lote quedan vacíos.');
		$hoja1->setCellValue('A9', '7. Puedes agregar o quitar columnas si no las necesitas: la lectura del archivo se hace por el NOMBRE del encabezado, no por su posición.');
		$hoja1->setCellValue('A10', '8. Después de procesar el archivo puedes descargar un log en Excel con las filas que no se procesaron y el motivo.');

		foreach ([3, 4, 5, 6, 7, 8, 9, 10] as $fRow) {
			$hoja1->mergeCells('A' . $fRow . ':F' . $fRow);
		}

		$hoja1->setCellValue('A12', 'CAMPO');
		$hoja1->setCellValue('B12', 'OBLIGATORIO');
		$hoja1->setCellValue('C12', 'DESCRIPCIÓN / VALORES VÁLIDOS');
		$hoja1->getStyle('A12:C12')->getFont()->setBold(true);
		$hoja1->getStyle('A12:C12')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');

		$campos = [
			['CONCEPTO', 'Sí', 'Nombre exacto del concepto de movimiento (elige de la lista desplegable, ver hoja "Conceptos"). Solo se listan conceptos tipo Proveedor.'],
			['ALMACEN', 'Sí', 'Nombre exacto del almacén (elige de la lista desplegable, ver hoja "Almacenes").'],
			['PROVEEDOR', 'Sí', 'Nombre exacto del proveedor (elige de la lista desplegable, ver hoja "Proveedores").'],
			['CLAVE_ARTICULO', 'Sí', 'Clave del producto (cve_articulo) tal como está en el catálogo de inventario. Debe existir y estar activo.'],
			['CANTIDAD', 'Sí', 'Numérico mayor a 0.'],
			['COSTO_UNITARIO', 'No', 'Numérico. Si se deja vacío se usa 0.'],
			['REFERENCIA', 'No', 'Texto libre. Si se deja vacío queda en blanco.'],
			['LOTE', 'No', 'Texto libre del lote del movimiento. Si se deja vacío queda en blanco.'],
		];

		$fila = 13;
		foreach ($campos as $c) {
			$hoja1->setCellValue('A' . $fila, $c[0]);
			$hoja1->setCellValue('B' . $fila, $c[1]);
			$hoja1->setCellValue('C' . $fila, $c[2]);
			$hoja1->mergeCells('C' . $fila . ':F' . $fila);
			$fila++;
		}

		foreach (['A', 'B'] as $col) {
			$hoja1->getColumnDimension($col)->setAutoSize(true);
		}
		$hoja1->getColumnDimension('C')->setWidth(70);
		$hoja1->getStyle('C13:C' . ($fila - 1))->getAlignment()->setWrapText(true);

		/* ---------- HOJA 2: CONCEPTOS (referencia + fuente del dropdown) ---------- */
		$hojaConceptos = $ss->createSheet();
		$hojaConceptos->setTitle('Conceptos');
		$hojaConceptos->fromArray(['ID', 'CONCEPTO'], null, 'A1');
		$hojaConceptos->getStyle('A1:B1')->getFont()->setBold(true);
		$hojaConceptos->getStyle('A1:B1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');

		$totalConceptos = count($conceptos);
		$fc = 2;
		foreach ($conceptos as $c) {
			$hojaConceptos->setCellValue('A' . $fc, $c['idconcepmov']);
			$hojaConceptos->setCellValue('B' . $fc, $c['descripcion']);
			$fc++;
		}
		$hojaConceptos->getColumnDimension('A')->setWidth(10);
		$hojaConceptos->getColumnDimension('B')->setWidth(35);
		if ($totalConceptos === 0) {
			$hojaConceptos->setCellValue('A2', '(No hay conceptos tipo Proveedor activos registrados todavía)');
			$hojaConceptos->mergeCells('A2:B2');
		}

		/* ---------- HOJA 3: ALMACENES (referencia + fuente del dropdown) ---------- */
		$hojaAlmacenes = $ss->createSheet();
		$hojaAlmacenes->setTitle('Almacenes');
		$hojaAlmacenes->fromArray(['ID', 'ALMACEN'], null, 'A1');
		$hojaAlmacenes->getStyle('A1:B1')->getFont()->setBold(true);
		$hojaAlmacenes->getStyle('A1:B1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');

		$totalAlmacenes = count($almacenes);
		$fa = 2;
		foreach ($almacenes as $a) {
			$hojaAlmacenes->setCellValue('A' . $fa, $a['idalmacen']);
			$hojaAlmacenes->setCellValue('B' . $fa, $a['descripcion']);
			$fa++;
		}
		$hojaAlmacenes->getColumnDimension('A')->setWidth(10);
		$hojaAlmacenes->getColumnDimension('B')->setWidth(35);
		if ($totalAlmacenes === 0) {
			$hojaAlmacenes->setCellValue('A2', '(No hay almacenes activos registrados todavía)');
			$hojaAlmacenes->mergeCells('A2:B2');
		}

		/* ---------- HOJA 4: PROVEEDORES (referencia + fuente del dropdown) ---------- */
		$hojaProveedores = $ss->createSheet();
		$hojaProveedores->setTitle('Proveedores');
		$hojaProveedores->fromArray(['ID', 'PROVEEDOR'], null, 'A1');
		$hojaProveedores->getStyle('A1:B1')->getFont()->setBold(true);
		$hojaProveedores->getStyle('A1:B1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');

		$totalProveedores = count($proveedores);
		$fp = 2;
		foreach ($proveedores as $p) {
			$hojaProveedores->setCellValue('A' . $fp, $p['id_proveedor']);
			$hojaProveedores->setCellValue('B' . $fp, $p['nombre']);
			$fp++;
		}
		$hojaProveedores->getColumnDimension('A')->setWidth(10);
		$hojaProveedores->getColumnDimension('B')->setWidth(35);
		if ($totalProveedores === 0) {
			$hojaProveedores->setCellValue('A2', '(No hay proveedores activos registrados todavía)');
			$hojaProveedores->mergeCells('A2:B2');
		}

		/* ---------- HOJA 5: MOVIMIENTOS ---------- */
		$hojaMov = $ss->createSheet();
		$hojaMov->setTitle('Movimientos');

		$encabezados = [
			'CONCEPTO',
			'ALMACEN',
			'PROVEEDOR',
			'CLAVE_ARTICULO',
			'CANTIDAD',
			'COSTO_UNITARIO',
			'REFERENCIA',
			'LOTE',
		];

		$hojaMov->fromArray($encabezados, null, 'A1');
		$hojaMov->getStyle('A1:H1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$hojaMov->getStyle('A1:H1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FF2E7D32');
		$hojaMov->freezePane('A2');
		$hojaMov->setAutoFilter('A1:H1');

		$ejemplo = [
			$totalConceptos > 0 ? $conceptos[0]['descripcion'] : '',
			$totalAlmacenes > 0 ? $almacenes[0]['descripcion'] : '',
			$totalProveedores > 0 ? $proveedores[0]['nombre'] : '',
			'EJ-0001',
			10,
			100,
			'Ejemplo referencia',
			'Ejemplo lote',
		];
		$hojaMov->fromArray($ejemplo, null, 'A2');
		$hojaMov->getStyle('A2:H2')->getFont()->setItalic(true)->getColor()->setARGB('FF999999');

		// CLAVE_ARTICULO como TEXTO: evita que Excel convierta claves numéricas
		// largas a notación científica (ej. 7502225310467 -> 7.50223E+12).
		$hojaMov->getStyle('D2:D500')->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

		if ($totalConceptos > 0) {
			$rangoConceptos = 'Conceptos!$B$2:$B$' . (1 + $totalConceptos);
			$this->agregarValidacionLista($hojaMov, 'A', 3, 500, $rangoConceptos);
		}
		if ($totalAlmacenes > 0) {
			$rangoAlmacenes = 'Almacenes!$B$2:$B$' . (1 + $totalAlmacenes);
			$this->agregarValidacionLista($hojaMov, 'B', 3, 500, $rangoAlmacenes);
		}
		if ($totalProveedores > 0) {
			$rangoProveedores = 'Proveedores!$B$2:$B$' . (1 + $totalProveedores);
			$this->agregarValidacionLista($hojaMov, 'C', 3, 500, $rangoProveedores);
		}

		foreach (range('A', 'H') as $col) {
			$hojaMov->getColumnDimension($col)->setWidth(22);
		}

		$ss->setActiveSheetIndex(4);

		$filename = 'Plantilla_CargaMasiva_Movimientos.xlsx';

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

	private function agregarValidacionLista($hoja, string $columna, int $desde, int $hasta, string $formula): void
	{
		for ($fila = $desde; $fila <= $hasta; $fila++) {
			$dv = $hoja->getCell($columna . $fila)->getDataValidation();
			$dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
			$dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
			$dv->setAllowBlank(true);
			$dv->setShowInputMessage(true);
			$dv->setShowErrorMessage(true);
			// OJO: en PhpSpreadsheet, showDropDown=true SÍ muestra la flecha del
			// desplegable en Excel (el atributo interno del XML está invertido).
			$dv->setShowDropDown(true);
			$dv->setFormula1($formula);
		}
	}

	/* ===============================
	   PROCESAR CARGA (CADA FILA = UN MOVIMIENTO INDEPENDIENTE)
	=============================== */
	public function procesarCarga()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['w'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para registrar movimientos.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}

		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene movimientos para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$conceptosMap = $this->mapaPorNombre($this->model->selectConceptosProveedor(), 'descripcion', 'idconcepmov');
		$almacenesMap = $this->mapaPorNombre($this->model->selectAlmacenesActivos(), 'descripcion', 'idalmacen');
		$proveedoresMap = $this->mapaPorNombre($this->model->selectProveedoresActivos(), 'nombre', 'id_proveedor');

		$todasLasClaves = array_values(array_unique(array_filter(array_map(function ($r) {
			return trim((string) ($r['cve_articulo'] ?? ''));
		}, $rows))));
		$inventarioMap = $this->model->mapInventarioPorClaves($todasLasClaves);

		require_once 'Models/Inv_movimientosinventarioModel.php';
		$movModel = new Inv_movimientosinventarioModel();

		$insertados = 0;
		$log = [];

		foreach ($rows as $r) {
			$fila = $r['_fila'];
			$conceptoTexto = trim((string) ($r['concepto'] ?? ''));
			$almacenTexto = trim((string) ($r['almacen'] ?? ''));
			$proveedorTexto = trim((string) ($r['proveedor'] ?? ''));
			$clave = trim((string) ($r['cve_articulo'] ?? ''));
			$cantidadTexto = trim((string) ($r['cantidad'] ?? ''));
			$costoTexto = trim((string) ($r['costo_unitario'] ?? ''));
			$referencia = trim((string) ($r['referencia'] ?? ''));
			$lote = trim((string) ($r['lote'] ?? ''));

			if ($clave === '') {
				$log[] = ['fila' => $fila, 'clave' => '', 'concepto' => $conceptoTexto, 'motivo' => 'Fila sin clave de artículo'];
				continue;
			}

			if ($conceptoTexto === '' || !isset($conceptosMap[$this->normalizarTexto($conceptoTexto)])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => $conceptoTexto === ''
					? 'Falta el concepto de movimiento (columna CONCEPTO)'
					: "El concepto \"{$conceptoTexto}\" no existe en el catálogo (hoja Conceptos) o no es de tipo Proveedor"];
				continue;
			}
			$concepmovid = $conceptosMap[$this->normalizarTexto($conceptoTexto)];

			if ($almacenTexto === '' || !isset($almacenesMap[$this->normalizarTexto($almacenTexto)])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => $almacenTexto === ''
					? 'Falta el almacén (columna ALMACEN)'
					: "El almacén \"{$almacenTexto}\" no existe en el catálogo (hoja Almacenes)"];
				continue;
			}
			$almacenid = $almacenesMap[$this->normalizarTexto($almacenTexto)];

			if ($proveedorTexto === '' || !isset($proveedoresMap[$this->normalizarTexto($proveedorTexto)])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => $proveedorTexto === ''
					? 'Falta el proveedor (columna PROVEEDOR); esta carga solo admite conceptos que requieren proveedor'
					: "El proveedor \"{$proveedorTexto}\" no existe en el catálogo (hoja Proveedores)"];
				continue;
			}
			$id_proveedor = $proveedoresMap[$this->normalizarTexto($proveedorTexto)];

			if (!isset($inventarioMap[$clave])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => 'La clave de artículo no existe en el catálogo o está inactiva'];
				continue;
			}
			$inventarioid = $inventarioMap[$clave];

			if ($cantidadTexto === '' || !is_numeric($cantidadTexto) || (float) $cantidadTexto <= 0) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => 'CANTIDAD inválida o vacía (debe ser numérica y mayor a 0)'];
				continue;
			}
			$cantidad = (float) $cantidadTexto;

			$costo = (is_numeric($costoTexto) ? (float) $costoTexto : 0.0);

			try {
				$resultado = $movModel->insertMovimientoMasivo(
					$almacenid,
					$concepmovid,
					$referencia,
					[$inventarioid],
					[$cantidad],
					[$costo],
					$id_proveedor,
					$lote !== '' ? $lote : null
				);

				if (is_array($resultado)) {
					$insertados++;
				} else {
					$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => (string) $resultado];
				}
			} catch (\Throwable $e) {
				error_log('Inv_movcargamasiva::procesarCarga insertMovimientoMasivo fila ' . $fila . ': ' . $e->getMessage());
				$log[] = ['fila' => $fila, 'clave' => $clave, 'concepto' => $conceptoTexto, 'motivo' => 'Error al guardar (revisa el formato de los datos)'];
			}
		}

		$_SESSION['cargaMasivaMovLog']['movimientos'] = $log;

		echo json_encode([
			'status' => true,
			'insertados' => $insertados,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$insertados} movimiento(s) registrado(s), " . count($log) . " omitido(s) de " . count($rows) . " fila(s).",
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	/* ===============================
	   LOG DE FILAS NO PROCESADAS (XLSX)
	=============================== */
	public function exportarLog()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$log = $_SESSION['cargaMasivaMovLog']['movimientos'] ?? [];

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $ss->getActiveSheet();
		$sheet->setTitle('Log');

		$sheet->setCellValue('A1', 'Log de movimientos NO registrados (Carga masiva)');
		$sheet->mergeCells('A1:D1');
		$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sheet->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sheet->mergeCells('A2:D2');

		$sheet->fromArray(['FILA_ARCHIVO', 'CLAVE_ARTICULO', 'CONCEPTO', 'MOTIVO'], null, 'A4');
		$sheet->getStyle('A4:D4')->getFont()->setBold(true);
		$sheet->getStyle('A4:D4')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');
		$sheet->freezePane('A5');

		// CLAVE_ARTICULO, CONCEPTO y MOTIVO como TEXTO explícito: evita que
		// Excel muestre claves numéricas largas en notación científica.
		$sheet->getStyle('B5:B5000')->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

		$r = 5;
		foreach ($log as $item) {
			$sheet->setCellValue('A' . $r, $item['fila'] ?? '');
			$sheet->setCellValueExplicit('B' . $r, (string) ($item['clave'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('C' . $r, (string) ($item['concepto'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('D' . $r, (string) ($item['motivo'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$r++;
		}

		foreach (['A', 'B', 'C', 'D'] as $col) {
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}
		$sheet->getColumnDimension('D')->setWidth(60);

		$filename = 'Log_CargaMasiva_Movimientos_' . date('Ymd_His') . '.xlsx';

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
	   HELPERS PRIVADOS
	=============================== */

	private function obtenerFilasArchivo(): ?array
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
			$rows = $this->leerArchivo($_FILES['archivo']['tmp_name']);
		} catch (\Throwable $e) {
			error_log('Inv_movcargamasiva::leerArchivo: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible leer el archivo. Verifica que uses la plantilla proporcionada.'], JSON_UNESCAPED_UNICODE);
			return null;
		}

		return $rows;
	}

	/**
	 * Construye un mapa [texto_normalizado => id] a partir de un arreglo de
	 * filas de catálogo (conceptos, almacenes o proveedores).
	 */
	private function mapaPorNombre(array $filas, string $campoNombre, string $campoId): array
	{
		$map = [];
		foreach ($filas as $f) {
			$map[$this->normalizarTexto((string) $f[$campoNombre])] = (int) $f[$campoId];
		}
		return $map;
	}

	private function leerArchivo(string $tmpName): array
	{
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);

		$sheet = $spreadsheet->sheetNameExists('Movimientos')
			? $spreadsheet->getSheetByName('Movimientos')
			: $spreadsheet->getSheet(0);

		$highestRow = $sheet->getHighestDataRow();
		$highestCol = $sheet->getHighestDataColumn();
		$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

		$mapaEncabezados = [
			'CONCEPTO' => 'concepto',
			'ALMACEN' => 'almacen',
			'PROVEEDOR' => 'proveedor',
			'CLAVE_ARTICULO' => 'cve_articulo',
			'CANTIDAD' => 'cantidad',
			'COSTO_UNITARIO' => 'costo_unitario',
			'REFERENCIA' => 'referencia',
			'LOTE' => 'lote',
		];

		$columnas = [];
		for ($col = 1; $col <= $highestColIndex; $col++) {
			$texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
			$clave = strtoupper($this->quitarAcentos($texto));
			$clave = preg_replace('/\s+/', '_', $clave);
			if (isset($mapaEncabezados[$clave])) {
				$columnas[$col] = $mapaEncabezados[$clave];
			}
		}

		if (empty($columnas) || !in_array('cve_articulo', $columnas, true)) {
			throw new \RuntimeException('No se encontraron los encabezados esperados. Usa la plantilla proporcionada.');
		}

		$rows = [];
		for ($fila = 2; $fila <= $highestRow; $fila++) {
			$registro = [];
			$vacio = true;

			foreach ($columnas as $col => $campo) {
				$valorCrudo = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
				$registro[$campo] = $this->valorComoTexto($valorCrudo);
				if ($registro[$campo] !== '') {
					$vacio = false;
				}
			}

			if ($vacio) {
				continue;
			}

			$registro['_fila'] = $fila;
			$rows[] = $registro;
		}

		return $rows;
	}

	private function quitarAcentos(string $texto): string
	{
		$busca = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'];
		$reemplaza = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'];
		return str_replace($busca, $reemplaza, $texto);
	}

	private function normalizarTexto(string $texto): string
	{
		return strtoupper(trim($this->quitarAcentos($texto)));
	}

	/**
	 * Convierte el valor crudo de una celda a texto sin perder precisión ni
	 * caer en notación científica (importante para claves numéricas largas).
	 */
	private function valorComoTexto($valor): string
	{
		if ($valor === null) {
			return '';
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
