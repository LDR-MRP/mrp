<?php
class Inv_cargamasiva extends Controllers
{
	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		// Reutiliza el permiso del módulo Inventario (18): esta pantalla
		// es una extensión de Inv_inventario, no un módulo con menú propio.
		getPermisos(MIINVENTARIO);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_cargamasiva()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Cargas Masivas";
		$data['page_title'] = "Cargas Masivas de Inventario";
		$data['page_name'] = "cargamasiva";
		$data['page_functions_js'] = "functions_inv_cargamasiva.js";
		$this->views->getView($this, "inv_cargamasiva", $data);
	}

	/* ===============================
	   PLANTILLA DE LLENADO (XLSX)
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$marcas = $this->model->selectMarcasActivas();

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()
			->setCreator('LDR Solutions · MRP')
			->setTitle('Plantilla Carga Masiva Inventario')
			->setDescription('Plantilla para alta y actualización masiva de productos');

		/* ---------- HOJA 1: INSTRUCCIONES ---------- */
		$hoja1 = $ss->getActiveSheet();
		$hoja1->setTitle('Instrucciones');

		$hoja1->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA · INVENTARIO');
		$hoja1->mergeCells('A1:C1');
		$hoja1->getStyle('A1')->getFont()->setBold(true)->setSize(14);

		$hoja1->setCellValue('A3', '1. Llena la hoja "Productos" sin modificar los encabezados de la fila 1.');
		$hoja1->setCellValue('A4', '2. Borra la fila de ejemplo (fila 2) antes de subir el archivo.');
		$hoja1->setCellValue('A5', '3. En la columna MARCA escribe el nombre tal como aparece en la hoja "Marcas" (tiene una lista desplegable para elegirlo sin errores). Déjala vacía si el producto no tiene marca.');
		$hoja1->setCellValue('A6', '4. En "Alta masiva" se insertan solo los productos NUEVOS. Si la CLAVE_ARTICULO ya existe, la fila se omite y se reporta en el log.');
		$hoja1->setCellValue('A7', '5. En "Actualización masiva" se busca cada CLAVE_ARTICULO en el sistema y se actualiza con lo que subas. Si dejas una celda vacía, se conserva el valor actual del producto. Si la clave no existe, se reporta en el log.');
		$hoja1->setCellValue('A8', '6. Puedes agregar o quitar columnas si no las necesitas: la lectura del archivo se hace por el NOMBRE del encabezado, no por su posición. Solo CLAVE_ARTICULO es indispensable en todas las filas.');
		$hoja1->setCellValue('A9', '7. Después de cada proceso puedes descargar un log en Excel con las filas que no se procesaron y el motivo.');

		foreach ([3, 4, 5, 6, 7, 8, 9] as $fRow) {
			$hoja1->mergeCells('A' . $fRow . ':F' . $fRow);
		}

		$hoja1->setCellValue('A11', 'CAMPO');
		$hoja1->setCellValue('B11', 'OBLIGATORIO');
		$hoja1->setCellValue('C11', 'DESCRIPCIÓN / VALORES VÁLIDOS');
		$hoja1->getStyle('A11:C11')->getFont()->setBold(true);
		$hoja1->getStyle('A11:C11')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');

		$campos = [
			['CLAVE_ARTICULO', 'Sí', 'Clave única del producto (cve_articulo). No puede repetirse.'],
			['DESCRIPCION', 'Sí', 'Descripción del producto.'],
			['TIPO_ELEMENTO', 'Sí (en alta)', 'Una letra: P=Producto, S=Servicio, K=Kit, C=Componente, H=Herramienta, R=Refacción.'],
			['UNIDAD_ENTRADA', 'Sí (en alta)', 'Unidad de compra/entrada, ej. PZA, KG, LT.'],
			['UNIDAD_SALIDA', 'No', 'Unidad de venta/salida.'],
			['UNIDAD_EMPAQUE', 'No', 'Numérico. Si se deja vacío se usa 1.'],
			['FACTOR_UNIDADES', 'No', 'Numérico. Si se deja vacío se usa 1.'],
			['ULTIMO_COSTO', 'No', 'Numérico. Si se deja vacío se usa 0.'],
			['UBICACION', 'No', 'Ubicación física del producto.'],
			['MARCA', 'No', 'Nombre exacto de la marca (elige de la lista desplegable, ver hoja "Marcas"). Vacío = sin marca.'],
			['TIEMPO_SURTIDO', 'No', 'Días de tiempo de surtido (entero). Vacío = 0.'],
			['MANEJA_SERIE', 'No', 'S o N. Vacío = N.'],
			['MANEJA_LOTE', 'No', 'S o N. Vacío = N.'],
			['MANEJA_PEDIMENTO', 'No', 'S o N. Vacío = N.'],
			['PESO', 'No', 'Numérico. Vacío = 0.'],
			['VOLUMEN', 'No', 'Numérico. Vacío = 0.'],
			['STOCK_MINIMO', 'No', 'Numérico. Vacío = sin definir.'],
			['STOCK_MAXIMO', 'No', 'Numérico. Vacío = sin definir.'],
			['NOTAS', 'No', 'Notas libres del producto.'],
			['ESTADO', 'No', 'Solo aplica en actualización: 1=Inactivo, 2=Activo. Vacío = conserva el actual. En alta siempre entra Activo.'],
		];

		$fila = 12;
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
		$hoja1->getStyle('C12:C' . ($fila - 1))->getAlignment()->setWrapText(true);

		/* ---------- HOJA 2: MARCAS (referencia + fuente del dropdown) ---------- */
		$hojaMarcas = $ss->createSheet();
		$hojaMarcas->setTitle('Marcas');
		$hojaMarcas->fromArray(['ID', 'NOMBRE_MARCA'], null, 'A1');
		$hojaMarcas->getStyle('A1:B1')->getFont()->setBold(true);
		$hojaMarcas->getStyle('A1:B1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');

		$totalMarcas = count($marcas);
		$fm = 2;
		foreach ($marcas as $m) {
			$hojaMarcas->setCellValue('A' . $fm, $m['id']);
			$hojaMarcas->setCellValue('B' . $fm, $m['nombre']);
			$fm++;
		}
		$hojaMarcas->getColumnDimension('A')->setWidth(10);
		$hojaMarcas->getColumnDimension('B')->setWidth(35);
		if ($totalMarcas === 0) {
			$hojaMarcas->setCellValue('A2', '(No hay marcas activas registradas todavía)');
			$hojaMarcas->mergeCells('A2:B2');
		}

		/* ---------- HOJA 3: PRODUCTOS ---------- */
		$hoja2 = $ss->createSheet();
		$hoja2->setTitle('Productos');

		$encabezados = [
			'CLAVE_ARTICULO',
			'DESCRIPCION',
			'TIPO_ELEMENTO',
			'UNIDAD_ENTRADA',
			'UNIDAD_SALIDA',
			'UNIDAD_EMPAQUE',
			'FACTOR_UNIDADES',
			'ULTIMO_COSTO',
			'UBICACION',
			'MARCA',
			'TIEMPO_SURTIDO',
			'MANEJA_SERIE',
			'MANEJA_LOTE',
			'MANEJA_PEDIMENTO',
			'PESO',
			'VOLUMEN',
			'STOCK_MINIMO',
			'STOCK_MAXIMO',
			'NOTAS',
			'ESTADO',
		];

		$hoja2->fromArray($encabezados, null, 'A1');
		$hoja2->getStyle('A1:T1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$hoja2->getStyle('A1:T1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FF2E7D32');
		$hoja2->freezePane('A2');
		$hoja2->setAutoFilter('A1:T1');

		$ejemplo = ['EJ-0001', 'Producto de ejemplo', 'P', 'PIEZA', 'PIEZA', 1, 1, 100, 'A-01-01', '', 0, 'N', 'N', 'N', 0, 0, '', '', 'Ejemplo', 2];
		$hoja2->fromArray($ejemplo, null, 'A2');
		$hoja2->getStyle('A2:T2')->getFont()->setItalic(true)->getColor()->setARGB('FF999999');

		$this->agregarValidacionLista($hoja2, 'C', 3, 300, '"P,S,K,C,H,R"');
		$this->agregarValidacionLista($hoja2, 'L', 3, 300, '"S,N"');
		$this->agregarValidacionLista($hoja2, 'M', 3, 300, '"S,N"');
		$this->agregarValidacionLista($hoja2, 'N', 3, 300, '"S,N"');
		$this->agregarValidacionLista($hoja2, 'T', 3, 300, '"1,2"');

		if ($totalMarcas > 0) {
			$rangoMarcas = 'Marcas!$B$2:$B$' . (1 + $totalMarcas);
			$this->agregarValidacionLista($hoja2, 'J', 3, 300, $rangoMarcas);
		}

		foreach (range('A', 'T') as $col) {
			$hoja2->getColumnDimension($col)->setWidth(18);
		}
		$hoja2->getColumnDimension('B')->setWidth(35);

		$ss->setActiveSheetIndex(2);

		$filename = 'Plantilla_CargaMasiva_Inventario.xlsx';

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
	   ALTA MASIVA (INSERTAR SOLO NUEVOS)
	=============================== */
	public function procesarAltas()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['w'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para dar de alta productos.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}

		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene productos para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$todasLasClaves = array_values(array_unique(array_filter(array_map(function ($r) {
			return trim((string) ($r['cve_articulo'] ?? ''));
		}, $rows))));

		$existentes = $this->model->mapExistentesPorClaves($todasLasClaves);
		$marcasMap = $this->mapaMarcasPorNombre();

		$clavesArchivo = [];
		$insertados = 0;
		$log = [];

		foreach ($rows as $r) {
			$fila = $r['_fila'];
			$cve = trim((string) ($r['cve_articulo'] ?? ''));
			$desc = trim((string) ($r['descripcion'] ?? ''));

			if ($cve === '') {
				$log[] = ['fila' => $fila, 'clave' => '', 'descripcion' => $desc, 'motivo' => 'Fila sin clave de artículo'];
				continue;
			}

			$faltantes = [];
			if ($desc === '') $faltantes[] = 'DESCRIPCION';
			if (trim((string) ($r['tipo_elemento'] ?? '')) === '') $faltantes[] = 'TIPO_ELEMENTO';
			if (trim((string) ($r['unidad_entrada'] ?? '')) === '') $faltantes[] = 'UNIDAD_ENTRADA';

			if (!empty($faltantes)) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'Datos obligatorios incompletos: ' . implode(', ', $faltantes)];
				continue;
			}

			[$idMarca, $marcaInvalida, $marcaTexto] = $this->resolverMarca($r['marca'] ?? null, $marcasMap);
			if ($marcaInvalida) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => "La marca \"{$marcaTexto}\" no existe en el catálogo (hoja Marcas). Corrígela o deja la celda vacía."];
				continue;
			}
			$r['idmarca'] = $idMarca;

			if (isset($existentes[$cve])) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'Ya existe en el sistema'];
				continue;
			}

			if (isset($clavesArchivo[$cve])) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'Clave duplicada dentro del archivo (ya procesada en la fila ' . $clavesArchivo[$cve] . ')'];
				continue;
			}

			$data = $this->normalizarFilaAlta($r);

			try {
				$idNuevo = $this->model->insertProducto($data);
				if ($idNuevo > 0) {
					$clavesArchivo[$cve] = $fila;
					$insertados++;
				} else {
					$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'No fue posible guardar el registro'];
				}
			} catch (\Throwable $e) {
				error_log('Inv_cargamasiva::procesarAltas insertProducto fila ' . $fila . ': ' . $e->getMessage());
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'Error al guardar (revisa el formato de los datos)'];
			}
		}

		$_SESSION['cargaMasivaLog']['altas'] = $log;

		echo json_encode([
			'status' => true,
			'insertados' => $insertados,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$insertados} producto(s) insertado(s), " . count($log) . " omitido(s) de " . count($rows) . " fila(s).",
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	/* ===============================
	   ACTUALIZACIÓN MASIVA (SOLO EXISTENTES)
	=============================== */
	public function procesarActualizacion()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['u'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para actualizar productos.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}

		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene productos para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$todasLasClaves = array_values(array_unique(array_filter(array_map(function ($r) {
			return trim((string) ($r['cve_articulo'] ?? ''));
		}, $rows))));

		$existentes = $this->model->mapExistentesPorClaves($todasLasClaves);
		$marcasMap = $this->mapaMarcasPorNombre();

		$actualizados = 0;
		$log = [];

		foreach ($rows as $r) {
			$fila = $r['_fila'];
			$cve = trim((string) ($r['cve_articulo'] ?? ''));
			$desc = trim((string) ($r['descripcion'] ?? ''));

			if ($cve === '') {
				$log[] = ['fila' => $fila, 'clave' => '', 'descripcion' => $desc, 'motivo' => 'Fila sin clave de artículo'];
				continue;
			}

			if (!isset($existentes[$cve])) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'La clave no está registrada en el sistema'];
				continue;
			}

			[$idMarca, $marcaInvalida, $marcaTexto] = $this->resolverMarca($r['marca'] ?? null, $marcasMap);
			if ($marcaInvalida) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => "La marca \"{$marcaTexto}\" no existe en el catálogo (hoja Marcas). Corrígela o deja la celda vacía."];
				continue;
			}
			if ($idMarca !== null) {
				$r['idmarca'] = $idMarca;
			} else {
				unset($r['idmarca']); // celda vacía: que conserve la marca actual del producto
			}

			$idinventario = $existentes[$cve];
			$actual = $this->model->selectProducto($idinventario);

			if (empty($actual)) {
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'No fue posible localizar el registro actual'];
				continue;
			}

			$data = $this->normalizarFilaActualizacion($r, $actual);

			try {
				$ok = $this->model->updateProducto($idinventario, $data);
				if ($ok) {
					$actualizados++;
				} else {
					$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'No fue posible actualizar el registro'];
				}
			} catch (\Throwable $e) {
				error_log('Inv_cargamasiva::procesarActualizacion updateProducto fila ' . $fila . ': ' . $e->getMessage());
				$log[] = ['fila' => $fila, 'clave' => $cve, 'descripcion' => $desc, 'motivo' => 'Error al actualizar (revisa el formato de los datos)'];
			}
		}

		$_SESSION['cargaMasivaLog']['actualizacion'] = $log;

		echo json_encode([
			'status' => true,
			'actualizados' => $actualizados,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$actualizados} producto(s) actualizado(s), " . count($log) . " omitido(s) de " . count($rows) . " fila(s).",
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	/* ===============================
	   LOG DE FILAS NO PROCESADAS (XLSX)
	=============================== */
	public function exportarLog($tipo = 'altas')
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$tipo = in_array($tipo, ['altas', 'actualizacion'], true) ? $tipo : 'altas';
		$log = $_SESSION['cargaMasivaLog'][$tipo] ?? [];

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $ss->getActiveSheet();
		$sheet->setTitle('Log');

		$titulo = $tipo === 'altas'
			? 'Log de productos NO insertados (Alta masiva)'
			: 'Log de productos NO actualizados (Actualización masiva)';

		$sheet->setCellValue('A1', $titulo);
		$sheet->mergeCells('A1:D1');
		$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sheet->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sheet->mergeCells('A2:D2');

		$sheet->fromArray(['FILA_ARCHIVO', 'CLAVE_ARTICULO', 'DESCRIPCION', 'MOTIVO'], null, 'A4');
		$sheet->getStyle('A4:D4')->getFont()->setBold(true);
		$sheet->getStyle('A4:D4')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');
		$sheet->freezePane('A5');

		$r = 5;
		foreach ($log as $item) {
			$sheet->setCellValue('A' . $r, $item['fila'] ?? '');
			$sheet->setCellValue('B' . $r, $item['clave'] ?? '');
			$sheet->setCellValue('C' . $r, $item['descripcion'] ?? '');
			$sheet->setCellValue('D' . $r, $item['motivo'] ?? '');
			$r++;
		}

		foreach (['A', 'B', 'C', 'D'] as $col) {
			$sheet->getColumnDimension($col)->setAutoSize(true);
		}
		$sheet->getColumnDimension('D')->setWidth(60);

		$filename = 'Log_CargaMasiva_' . ($tipo === 'altas' ? 'Altas' : 'Actualizacion') . '_' . date('Ymd_His') . '.xlsx';

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
			error_log('Inv_cargamasiva::leerArchivo: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible leer el archivo. Verifica que uses la plantilla proporcionada.'], JSON_UNESCAPED_UNICODE);
			return null;
		}

		return $rows;
	}

	private function mapaMarcasPorNombre(): array
	{
		$map = [];
		foreach ($this->model->selectMarcasActivas() as $m) {
			$map[$this->normalizarTexto($m['nombre'])] = (int) $m['id'];
		}
		return $map;
	}

	/**
	 * Resuelve el texto de la columna MARCA contra el catálogo.
	 * Regresa [idmarca|null, invalida(bool), textoOriginal].
	 */
	private function resolverMarca($valor, array $marcasMap): array
	{
		$texto = trim((string) ($valor ?? ''));
		if ($texto === '') {
			return [null, false, ''];
		}
		$clave = $this->normalizarTexto($texto);
		if (isset($marcasMap[$clave])) {
			return [$marcasMap[$clave], false, $texto];
		}
		return [null, true, $texto];
	}

	private function leerArchivo(string $tmpName): array
	{
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);

		$sheet = $spreadsheet->sheetNameExists('Productos')
			? $spreadsheet->getSheetByName('Productos')
			: $spreadsheet->getSheet(0);

		$highestRow = $sheet->getHighestDataRow();
		$highestCol = $sheet->getHighestDataColumn();
		$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

		$mapaEncabezados = [
			'CLAVE_ARTICULO' => 'cve_articulo',
			'DESCRIPCION' => 'descripcion',
			'TIPO_ELEMENTO' => 'tipo_elemento',
			'UNIDAD_ENTRADA' => 'unidad_entrada',
			'UNIDAD_SALIDA' => 'unidad_salida',
			'UNIDAD_EMPAQUE' => 'unidad_empaque',
			'FACTOR_UNIDADES' => 'factor_unidades',
			'ULTIMO_COSTO' => 'ultimo_costo',
			'UBICACION' => 'ubicacion',
			'MARCA' => 'marca',
			'TIEMPO_SURTIDO' => 'tiempo_surtido',
			'MANEJA_SERIE' => 'serie',
			'MANEJA_LOTE' => 'lote',
			'MANEJA_PEDIMENTO' => 'pedimiento',
			'PESO' => 'peso',
			'VOLUMEN' => 'volumen',
			'STOCK_MINIMO' => 'stock_minimo',
			'STOCK_MAXIMO' => 'stock_maximo',
			'NOTAS' => 'notas',
			'ESTADO' => 'estado',
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
				$valor = $sheet->getCellByColumnAndRow($col, $fila)->getValue();
				$registro[$campo] = is_string($valor) ? trim($valor) : $valor;
				if ($valor !== null && $valor !== '') {
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

	private function normalizarFilaAlta(array $r): array
	{
		$texto = trim((string) ($r['descripcion'] ?? ''));

		return [
			'cve_articulo'    => trim((string) ($r['cve_articulo'] ?? '')),
			'descripcion'     => $texto,
			'notas'           => trim((string) ($r['notas'] ?? '')),
			'serie'           => $this->flagSN($r['serie'] ?? null),
			'unidad_salida'   => trim((string) ($r['unidad_salida'] ?? '')),
			'unidad_empaque'  => $this->numOr($r['unidad_empaque'] ?? null, 1),
			'ubicacion'       => trim((string) ($r['ubicacion'] ?? '')),
			'idmarca'         => $this->idOpcional($r['idmarca'] ?? null),
			'tiempo_surtido'  => $this->entOr($r['tiempo_surtido'] ?? null, 0),
			'ultimo_costo'    => $this->numOr($r['ultimo_costo'] ?? null, 0),
			'tipo_elemento'   => strtoupper(trim((string) ($r['tipo_elemento'] ?? ''))),
			'unidad_entrada'  => trim((string) ($r['unidad_entrada'] ?? '')),
			'factor_unidades' => $this->numOr($r['factor_unidades'] ?? null, 1),
			'lote'            => $this->flagSN($r['lote'] ?? null),
			'pedimiento'      => $this->flagSN($r['pedimiento'] ?? null),
			'peso'            => $this->numOr($r['peso'] ?? null, 0),
			'volumen'         => $this->numOr($r['volumen'] ?? null, 0),
			'stock_minimo'    => $this->numOrNull($r['stock_minimo'] ?? null),
			'stock_maximo'    => $this->numOrNull($r['stock_maximo'] ?? null),
			'estado'          => 2,
		];
	}

	private function normalizarFilaActualizacion(array $r, array $actual): array
	{
		$vacio = function ($v) {
			return $v === null || trim((string) $v) === '';
		};

		return [
			'descripcion'     => !$vacio($r['descripcion'] ?? null) ? trim((string) $r['descripcion']) : $actual['descripcion'],
			'notas'           => !$vacio($r['notas'] ?? null) ? trim((string) $r['notas']) : $actual['notas'],
			'serie'           => !$vacio($r['serie'] ?? null) ? $this->flagSN($r['serie']) : $actual['serie'],
			'unidad_salida'   => !$vacio($r['unidad_salida'] ?? null) ? trim((string) $r['unidad_salida']) : $actual['unidad_salida'],
			'unidad_empaque'  => !$vacio($r['unidad_empaque'] ?? null) ? $this->numOr($r['unidad_empaque'], 1) : $actual['unidad_empaque'],
			'ubicacion'       => !$vacio($r['ubicacion'] ?? null) ? trim((string) $r['ubicacion']) : $actual['ubicacion'],
			'idmarca'         => !$vacio($r['idmarca'] ?? null) ? $this->idOpcional($r['idmarca']) : $actual['idmarca'],
			'tiempo_surtido'  => !$vacio($r['tiempo_surtido'] ?? null) ? $this->entOr($r['tiempo_surtido'], 0) : $actual['tiempo_surtido'],
			'ultimo_costo'    => !$vacio($r['ultimo_costo'] ?? null) ? $this->numOr($r['ultimo_costo'], 0) : $actual['ultimo_costo'],
			'tipo_elemento'   => !$vacio($r['tipo_elemento'] ?? null) ? strtoupper(trim((string) $r['tipo_elemento'])) : $actual['tipo_elemento'],
			'unidad_entrada'  => !$vacio($r['unidad_entrada'] ?? null) ? trim((string) $r['unidad_entrada']) : $actual['unidad_entrada'],
			'factor_unidades' => !$vacio($r['factor_unidades'] ?? null) ? $this->numOr($r['factor_unidades'], 1) : $actual['factor_unidades'],
			'lote'            => !$vacio($r['lote'] ?? null) ? $this->flagSN($r['lote']) : $actual['lote'],
			'pedimiento'      => !$vacio($r['pedimiento'] ?? null) ? $this->flagSN($r['pedimiento']) : $actual['pedimiento'],
			'peso'            => !$vacio($r['peso'] ?? null) ? $this->numOr($r['peso'], 0) : $actual['peso'],
			'volumen'         => !$vacio($r['volumen'] ?? null) ? $this->numOr($r['volumen'], 0) : $actual['volumen'],
			'stock_minimo'    => !$vacio($r['stock_minimo'] ?? null) ? $this->numOr($r['stock_minimo'], 0) : $actual['stock_minimo'],
			'stock_maximo'    => !$vacio($r['stock_maximo'] ?? null) ? $this->numOr($r['stock_maximo'], 0) : $actual['stock_maximo'],
			'estado'          => (!$vacio($r['estado'] ?? null) && in_array(trim((string) $r['estado']), ['1', '2'], true))
				? (int) trim((string) $r['estado'])
				: (int) $actual['estado'],
		];
	}

	private function idOpcional($v): ?int
	{
		return (is_numeric($v) && (int) $v > 0) ? (int) $v : null;
	}

	private function numOr($v, float $default): float
	{
		return (is_numeric($v)) ? (float) $v : $default;
	}

	private function numOrNull($v): ?float
	{
		return (is_numeric($v)) ? (float) $v : null;
	}

	private function entOr($v, int $default): int
	{
		return (is_numeric($v)) ? (int) $v : $default;
	}

	private function flagSN($v): string
	{
		$v = strtoupper(trim((string) ($v ?? '')));
		return in_array($v, ['S', 'N'], true) ? $v : 'N';
	}
}
