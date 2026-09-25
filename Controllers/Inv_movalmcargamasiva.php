<?php
class Inv_movalmcargamasiva extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		// Reutiliza el permiso del módulo Traspaso entre almacenes: esta
		// pantalla es una extensión de Inv_movimientosalmacenes, no un
		// módulo con menú propio.
		getPermisos(MIMOVIMIENTOSALMACEN);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_movalmcargamasiva()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Carga Masiva de Traspasos";
		$data['page_title'] = "Carga Masiva de Traspasos entre Almacenes";
		$data['page_name'] = "movalmcargamasiva";
		$data['page_functions_js'] = "functions_inv_movalmcargamasiva.js";
		$this->views->getView($this, "inv_movalmcargamasiva", $data);
	}

	/* ===============================
	   PLANTILLA DE LLENADO (XLSX)
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$almacenes = $this->model->selectAlmacenesActivos();

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()
			->setCreator('LDR Solutions · MRP')
			->setTitle('Plantilla Carga Masiva Traspasos')
			->setDescription('Plantilla para alta masiva de traspasos entre almacenes');

		/* ---------- HOJA 1: INSTRUCCIONES ---------- */
		$hoja1 = $ss->getActiveSheet();
		$hoja1->setTitle('Instrucciones');

		$hoja1->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA · TRASPASO ENTRE ALMACENES');
		$hoja1->mergeCells('A1:C1');
		$hoja1->getStyle('A1')->getFont()->setBold(true)->setSize(14);

		$hoja1->setCellValue('A3', '1. Llena la hoja "Traspasos" sin modificar los encabezados de la fila 1.');
		$hoja1->setCellValue('A4', '2. Borra la fila de ejemplo (fila 2) antes de subir el archivo.');
		$hoja1->setCellValue('A5', '3. En ALMACEN_ORIGEN y ALMACEN_DESTINO escribe el texto tal como aparece en la hoja "Almacenes" (tiene lista desplegable para elegirlo sin errores). No pueden ser el mismo almacén.');
		$hoja1->setCellValue('A6', '4. Las filas que compartan el mismo ALMACEN_ORIGEN y ALMACEN_DESTINO se agrupan en un mismo traspaso (un solo folio TRF- con varias partidas). La REFERENCIA y el LOTE del traspaso se toman de la primera fila de cada grupo. Si cambias el almacén origen o destino, se genera un traspaso nuevo.');
		$hoja1->setCellValue('A7', '5. CLAVE_ARTICULO debe existir y estar activa en el catálogo de inventario. CANTIDAD debe ser mayor a 0 y debe haber suficiente existencia en el almacén origen.');
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
			['ALMACEN_ORIGEN', 'Sí', 'Nombre exacto del almacén de origen (elige de la lista desplegable, ver hoja "Almacenes").'],
			['ALMACEN_DESTINO', 'Sí', 'Nombre exacto del almacén de destino (elige de la lista desplegable, ver hoja "Almacenes"). Debe ser distinto al de origen.'],
			['CLAVE_ARTICULO', 'Sí', 'Clave del producto (cve_articulo) tal como está en el catálogo de inventario. Debe existir y estar activo.'],
			['CANTIDAD', 'Sí', 'Numérico mayor a 0.'],
			['COSTO_UNITARIO', 'No', 'Numérico. Si se deja vacío se usa 0.'],
			['REFERENCIA', 'No', 'Texto libre. Si se deja vacío queda en blanco.'],
			['LOTE', 'No', 'Texto libre del lote del traspaso. Si se deja vacío queda en blanco.'],
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

		/* ---------- HOJA 2: ALMACENES (referencia + fuente del dropdown) ---------- */
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

		/* ---------- HOJA 3: TRASPASOS ---------- */
		$hojaMov = $ss->createSheet();
		$hojaMov->setTitle('Traspasos');

		$encabezados = [
			'ALMACEN_ORIGEN',
			'ALMACEN_DESTINO',
			'CLAVE_ARTICULO',
			'CANTIDAD',
			'COSTO_UNITARIO',
			'REFERENCIA',
			'LOTE',
		];

		$hojaMov->fromArray($encabezados, null, 'A1');
		$hojaMov->getStyle('A1:G1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$hojaMov->getStyle('A1:G1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FF2E7D32');
		$hojaMov->freezePane('A2');
		$hojaMov->setAutoFilter('A1:G1');

		$origenEjemplo = $totalAlmacenes > 0 ? $almacenes[0]['descripcion'] : '';
		$destinoEjemplo = $totalAlmacenes > 1 ? $almacenes[1]['descripcion'] : '';

		$ejemplo = [
			$origenEjemplo,
			$destinoEjemplo,
			'EJ-0001',
			10,
			100,
			'Ejemplo referencia',
			'Ejemplo lote',
		];
		$hojaMov->fromArray($ejemplo, null, 'A2');
		$hojaMov->getStyle('A2:G2')->getFont()->setItalic(true)->getColor()->setARGB('FF999999');

		// CLAVE_ARTICULO como TEXTO: evita que Excel convierta claves numéricas
		// largas a notación científica (ej. 7502225310467 -> 7.50223E+12).
		$hojaMov->getStyle('C2:C500')->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

		if ($totalAlmacenes > 0) {
			$rangoAlmacenes = 'Almacenes!$B$2:$B$' . (1 + $totalAlmacenes);
			$this->agregarValidacionLista($hojaMov, 'A', 3, 500, $rangoAlmacenes);
			$this->agregarValidacionLista($hojaMov, 'B', 3, 500, $rangoAlmacenes);
		}

		foreach (range('A', 'G') as $col) {
			$hojaMov->getColumnDimension($col)->setWidth(22);
		}

		$ss->setActiveSheetIndex(2);

		$filename = 'Plantilla_CargaMasiva_Traspasos.xlsx';

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
	   PROCESAR CARGA (AGRUPA POR ALMACEN ORIGEN + ALMACEN DESTINO)
	=============================== */
	public function procesarCarga()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['w'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para registrar traspasos.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}

		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene traspasos para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$almacenesMap = $this->mapaPorNombre($this->model->selectAlmacenesActivos(), 'descripcion', 'idalmacen');

		$todasLasClaves = array_values(array_unique(array_filter(array_map(function ($r) {
			return trim((string) ($r['cve_articulo'] ?? ''));
		}, $rows))));
		$inventarioMap = $this->model->mapInventarioPorClaves($todasLasClaves);

		require_once 'Models/Inv_movimientosalmacenesModel.php';
		$trasladoModel = new Inv_movimientosalmacenesModel();

		$log = [];
		// Filas válidas agrupadas por ALMACEN_ORIGEN + ALMACEN_DESTINO: cada
		// grupo se registra con un solo folio TRF- (varias partidas), para que
		// el detalle del traspaso las muestre juntas en vez de una por fila.
		$grupos = [];

		foreach ($rows as $r) {
			$fila = $r['_fila'];
			$origenTexto = trim((string) ($r['almacen_origen'] ?? ''));
			$destinoTexto = trim((string) ($r['almacen_destino'] ?? ''));
			$clave = trim((string) ($r['cve_articulo'] ?? ''));
			$cantidadTexto = trim((string) ($r['cantidad'] ?? ''));
			$costoTexto = trim((string) ($r['costo_unitario'] ?? ''));
			$referencia = trim((string) ($r['referencia'] ?? ''));
			$lote = trim((string) ($r['lote'] ?? ''));

			if ($clave === '') {
				$log[] = ['fila' => $fila, 'clave' => '', 'almacenes' => '', 'motivo' => 'Fila sin clave de artículo'];
				continue;
			}

			if ($origenTexto === '' || !isset($almacenesMap[$this->normalizarTexto($origenTexto)])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'almacenes' => "{$origenTexto} -> {$destinoTexto}", 'motivo' => $origenTexto === ''
					? 'Falta el almacén origen (columna ALMACEN_ORIGEN)'
					: "El almacén origen \"{$origenTexto}\" no existe en el catálogo (hoja Almacenes)"];
				continue;
			}
			$almacen_origenid = $almacenesMap[$this->normalizarTexto($origenTexto)];

			if ($destinoTexto === '' || !isset($almacenesMap[$this->normalizarTexto($destinoTexto)])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'almacenes' => "{$origenTexto} -> {$destinoTexto}", 'motivo' => $destinoTexto === ''
					? 'Falta el almacén destino (columna ALMACEN_DESTINO)'
					: "El almacén destino \"{$destinoTexto}\" no existe en el catálogo (hoja Almacenes)"];
				continue;
			}
			$almacen_destinoid = $almacenesMap[$this->normalizarTexto($destinoTexto)];

			if ($almacen_origenid === $almacen_destinoid) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'almacenes' => "{$origenTexto} -> {$destinoTexto}", 'motivo' => 'El almacén origen y destino no pueden ser el mismo'];
				continue;
			}

			if (!isset($inventarioMap[$clave])) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'almacenes' => "{$origenTexto} -> {$destinoTexto}", 'motivo' => 'La clave de artículo no existe en el catálogo o está inactiva'];
				continue;
			}
			$inventarioid = $inventarioMap[$clave];

			if ($cantidadTexto === '' || !is_numeric($cantidadTexto) || (float) $cantidadTexto <= 0) {
				$log[] = ['fila' => $fila, 'clave' => $clave, 'almacenes' => "{$origenTexto} -> {$destinoTexto}", 'motivo' => 'CANTIDAD inválida o vacía (debe ser numérica y mayor a 0)'];
				continue;
			}
			$cantidad = (float) $cantidadTexto;

			$costo = (is_numeric($costoTexto) ? (float) $costoTexto : 0.0);

			$claveGrupo = $almacen_origenid . '|' . $almacen_destinoid;

			if (!isset($grupos[$claveGrupo])) {
				$grupos[$claveGrupo] = [
					'almacen_origenid' => $almacen_origenid,
					'almacen_destinoid' => $almacen_destinoid,
					'origenTexto' => $origenTexto,
					'destinoTexto' => $destinoTexto,
					// La referencia y el lote del traspaso se toman de la primera
					// fila del grupo (todas las filas del grupo comparten un solo
					// folio, y esos dos campos son a nivel traspaso).
					'referencia' => $referencia,
					'lote' => $lote,
					'filas' => [],
				];
			}

			$grupos[$claveGrupo]['filas'][] = [
				'fila' => $fila,
				'clave' => $clave,
				'inventarioid' => $inventarioid,
				'cantidad' => $cantidad,
				'costo' => $costo,
			];
		}

		$filasInsertadas = 0;
		$traspasosCreados = 0;

		foreach ($grupos as $grupo) {
			$inventarios = array_column($grupo['filas'], 'inventarioid');
			$cantidades = array_column($grupo['filas'], 'cantidad');
			$costos = array_column($grupo['filas'], 'costo');

			try {
				$resultado = $trasladoModel->insertTransferencia(
					$grupo['almacen_origenid'],
					$grupo['almacen_destinoid'],
					$grupo['referencia'],
					$inventarios,
					$cantidades,
					$costos,
					$grupo['lote'] !== '' ? $grupo['lote'] : null
				);

				if (is_string($resultado) && str_starts_with($resultado, 'TRF-')) {
					$filasInsertadas += count($grupo['filas']);
					$traspasosCreados++;
				} else {
					foreach ($grupo['filas'] as $f) {
						$log[] = ['fila' => $f['fila'], 'clave' => $f['clave'], 'almacenes' => "{$grupo['origenTexto']} -> {$grupo['destinoTexto']}", 'motivo' => (string) $resultado];
					}
				}
			} catch (\Throwable $e) {
				error_log('Inv_movalmcargamasiva::procesarCarga insertTransferencia grupo: ' . $e->getMessage());
				foreach ($grupo['filas'] as $f) {
					$log[] = ['fila' => $f['fila'], 'clave' => $f['clave'], 'almacenes' => "{$grupo['origenTexto']} -> {$grupo['destinoTexto']}", 'motivo' => 'Error al guardar (revisa el formato de los datos)'];
				}
			}
		}

		// Ordena el log por número de fila para que se lea en el mismo orden que el archivo.
		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});

		$_SESSION['cargaMasivaMovAlmLog']['traspasos'] = $log;

		echo json_encode([
			'status' => true,
			'insertados' => $filasInsertadas,
			'traspasos' => $traspasosCreados,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$filasInsertadas} fila(s) registrada(s) en {$traspasosCreados} traspaso(s), " . count($log) . " omitido(s) de " . count($rows) . " fila(s).",
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

		$log = $_SESSION['cargaMasivaMovAlmLog']['traspasos'] ?? [];

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $ss->getActiveSheet();
		$sheet->setTitle('Log');

		$sheet->setCellValue('A1', 'Log de traspasos NO registrados (Carga masiva)');
		$sheet->mergeCells('A1:D1');
		$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sheet->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sheet->mergeCells('A2:D2');

		$sheet->fromArray(['FILA_ARCHIVO', 'CLAVE_ARTICULO', 'ORIGEN -> DESTINO', 'MOTIVO'], null, 'A4');
		$sheet->getStyle('A4:D4')->getFont()->setBold(true);
		$sheet->getStyle('A4:D4')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');
		$sheet->freezePane('A5');

		// CLAVE_ARTICULO, ORIGEN->DESTINO y MOTIVO como TEXTO explícito: evita
		// que Excel muestre claves numéricas largas en notación científica.
		$sheet->getStyle('B5:B5000')->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

		$r = 5;
		foreach ($log as $item) {
			$sheet->setCellValue('A' . $r, $item['fila'] ?? '');
			$sheet->setCellValueExplicit('B' . $r, (string) ($item['clave'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('C' . $r, (string) ($item['almacenes'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('D' . $r, (string) ($item['motivo'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$r++;
		}

		foreach (['A', 'B', 'C', 'D'] as $col) {
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}
		$sheet->getColumnDimension('D')->setWidth(60);

		$filename = 'Log_CargaMasiva_Traspasos_' . date('Ymd_His') . '.xlsx';

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
			error_log('Inv_movalmcargamasiva::leerArchivo: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible leer el archivo. Verifica que uses la plantilla proporcionada.'], JSON_UNESCAPED_UNICODE);
			return null;
		}

		return $rows;
	}

	/**
	 * Construye un mapa [texto_normalizado => id] a partir de un arreglo de
	 * filas de catálogo (almacenes).
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

		$sheet = $spreadsheet->sheetNameExists('Traspasos')
			? $spreadsheet->getSheetByName('Traspasos')
			: $spreadsheet->getSheet(0);

		$highestRow = $sheet->getHighestDataRow();
		$highestCol = $sheet->getHighestDataColumn();
		$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

		$mapaEncabezados = [
			'ALMACEN_ORIGEN' => 'almacen_origen',
			'ALMACEN_DESTINO' => 'almacen_destino',
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
