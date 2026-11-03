<?php
/**
 * Inventarios > Cobranza de unidades.
 *
 * Consulta de las unidades Facturadas (VIN en estado 3) y su estatus de pago:
 * 0 Pendiente, 1 Crédito, 2 Parcialmente pagada, 3 Pagada. Solo estatus, sin
 * montos. Se actualiza de forma individual (modal) o masiva (layout Excel de
 * solo actualización, con log de lo que no se aplicó).
 *
 * Permisos (módulo MICOBRANZAUNIDADES): r = consultar y descargar,
 * u = cambiar estatus (individual y carga masiva).
 */
class Inv_cobranzaunidades extends Controllers
{
	private const MAX_FILAS_VALIDACION = 5000;

	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		getPermisos(MICOBRANZAUNIDADES);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_cobranzaunidades()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Cobranza de unidades";
		$data['page_title'] = "Cobranza de unidades";
		$data['page_name'] = "cobranzaunidades";
		$data['estatus_pago'] = Inv_cobranzaunidadesModel::ESTATUS;
		$data['page_functions_js'] = "functions_inv_cobranzaunidades.js";
		$this->views->getView($this, "inv_cobranzaunidades", $data);
	}

	/* ===============================
	   CONSULTAS (JSON)
	=============================== */
	public function getResumen()
	{
		$this->json(function () {
			return ['status' => true, 'data' => $this->model->selectResumen()];
		}, 'r', 'No fue posible obtener los indicadores.');
	}

	/** Listado (filtro de fechas en servidor; distribuidor, modelo y estatus se filtran en la tabla). */
	public function getUnidades()
	{
		@set_time_limit(120);
		$this->json(function () {
			$data = [];
			foreach ($this->model->selectUnidades($this->filtrosFechas()) as $u) {
				$data[] = $this->filaPublica($u);
			}
			return ['status' => true, 'data' => $data];
		}, 'r', 'No fue posible consultar las unidades facturadas.');
	}

	public function getHistorial()
	{
		$this->json(function () {
			$id = (int) ($_GET['id'] ?? 0);
			return ['status' => true, 'data' => $id > 0 ? $this->model->selectHistorial($id) : []];
		}, 'r', 'No fue posible consultar el historial.');
	}

	/* ===============================
	   CAMBIO INDIVIDUAL
	=============================== */
	public function setEstatus()
	{
		$this->json(function () {
			$id = (int) ($_POST['id_numeros_serie'] ?? 0);
			$idmov = (int) ($_POST['idmovinventario'] ?? 0);
			$estatus = $_POST['estatus_pago'] ?? '';

			if ($id <= 0 || $estatus === '' || !isset(Inv_cobranzaunidadesModel::ESTATUS[(int) $estatus])) {
				return ['status' => false, 'msg' => 'Selecciona un estatus válido.'];
			}
			$u = $this->model->selectUnidad($id);
			if (!$u) {
				return ['status' => false, 'msg' => 'La unidad ya no está facturada (pudo haberse devuelto). Actualiza la tabla.'];
			}
			if ($idmov > 0 && (int) $u['idmovinventario'] !== $idmov) {
				return ['status' => false, 'msg' => 'La venta de esta unidad cambió (devolución y nueva venta). Actualiza la tabla.'];
			}
			$cambio = $this->model->guardarEstatus($u, (int) $estatus, $this->usuarioId(), 'INDIVIDUAL');
			$u = $this->model->selectUnidad($id);
			return [
				'status' => true,
				'msg' => $cambio ? 'Estatus actualizado.' : 'La unidad ya tenía ese estatus.',
				'data' => $u ? $this->filaPublica($u) : null,
			];
		}, 'u', 'No fue posible actualizar el estatus.');
	}

	/* ===============================
	   LAYOUT (XLSX) CON LAS UNIDADES FILTRADAS
	=============================== */
	public function descargarLayout()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		@set_time_limit(300);

		$filtros = $this->filtrosFechas();
		$filtros['distribuidor'] = trim((string) ($_GET['distribuidor'] ?? ''));
		$filtros['modelo'] = trim((string) ($_GET['modelo'] ?? ''));
		$filtros['estatus'] = trim((string) ($_GET['estatus'] ?? ''));
		$unidades = $this->model->selectUnidades($filtros);

		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
		$estatus = Inv_cobranzaunidadesModel::ESTATUS;

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()->setCreator('LDR Solutions · MRP')->setTitle('Layout Cobranza de unidades');

		/* ---------- INSTRUCCIONES ---------- */
		$h1 = $ss->getActiveSheet();
		$h1->setTitle('Instrucciones');
		$h1->setCellValue('A1', 'LAYOUT DE COBRANZA DE UNIDADES (ESTATUS DE PAGO)');
		$h1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$h1->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s') . ' · ' . count($unidades) . ' unidad(es) · ' . $this->textoFiltros($filtros));
		$txt = [
			'1. Este archivo es SOLO DE ACTUALIZACIÓN: trae las unidades FACTURADAS con los filtros que tenías en pantalla. No agregues filas.',
			'2. Solo captura la columna en VERDE: ESTATUS_PAGO, eligiendo de la lista: Pendiente, Crédito, Parcialmente pagada o Pagada. Puedes arrastrar la celda para copiar el mismo estatus a varias filas.',
			'3. Las columnas en GRIS son informativas. VIN y FOLIO_VENTA se usan para identificar la venta: no los modifiques.',
			'4. Las filas sin cambio de estatus se ignoran.',
			'5. Si un VIN se devolvió (o se volvió a vender) después de descargar este archivo, su fila no se aplica y queda en el log.',
			'6. Al terminar puedes descargar el log en Excel con las filas que no se aplicaron y el motivo.',
		];
		$f = 4;
		foreach ($txt as $t) {
			$h1->setCellValue('A' . $f, $t);
			$h1->mergeCells('A' . $f . ':H' . $f);
			$h1->getStyle('A' . $f)->getAlignment()->setWrapText(true);
			$h1->getRowDimension($f)->setRowHeight(30);
			$f++;
		}
		$h1->getColumnDimension('A')->setWidth(18);

		/* ---------- CATÁLOGO DE ESTATUS ---------- */
		$he = $ss->createSheet();
		$he->setTitle('Estatus');
		$he->setCellValue('A1', 'ESTATUS_PAGO');
		$he->getStyle('A1')->getFont()->setBold(true);
		$r = 2;
		foreach ($estatus as $k => $v) {
			$he->setCellValue('A' . $r, $v);
			$r++;
		}
		$he->getColumnDimension('A')->setWidth(26);

		/* ---------- UNIDADES ---------- */
		$hu = $ss->createSheet();
		$hu->setTitle('Unidades');
		$enc = ['FECHA_VENTA', 'VIN', 'NUMERO_MOTOR', 'SKU', 'MODELO', 'DISTRIBUIDOR', 'FACTURA', 'FOLIO_VENTA', 'ESTATUS_PAGO'];
		$hu->fromArray($enc, null, 'A1');
		$hu->getStyle('A1:I1')->getFont()->setBold(true);
		$hu->getStyle('A1:H1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFD9D9D9');
		$hu->getStyle('I1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFC6EFCE');

		$total = count($unidades);
		$ultima = max(2, $total + 1);
		foreach (['B', 'C', 'D', 'G', 'H'] as $col) {
			$hu->getStyle($col . '2:' . $col . $ultima)->getNumberFormat()
				->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		}

		$r = 2;
		foreach ($unidades as $u) {
			$hu->setCellValueExplicit('A' . $r, (string) $u['fecha_venta'], $T);
			$hu->setCellValueExplicit('B' . $r, (string) $u['numero_serie'], $T);
			$hu->setCellValueExplicit('C' . $r, (string) $u['numero_motor'], $T);
			$hu->setCellValueExplicit('D' . $r, (string) $u['cve_articulo'], $T);
			$hu->setCellValueExplicit('E' . $r, (string) $u['descripcion'], $T);
			$hu->setCellValueExplicit('F' . $r, (string) $u['distribuidor'], $T);
			$hu->setCellValueExplicit('G' . $r, (string) $u['factura'], $T);
			$hu->setCellValueExplicit('H' . $r, (string) $u['folio_venta'], $T);
			$e = (int) $u['estatus_pago'];
			$hu->setCellValueExplicit('I' . $r, $estatus[$e] ?? '', $T);
			$r++;
		}

		if ($total > 0) {
			$hu->getStyle('A2:H' . $ultima)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFF2F2F2');
			$this->agregarValidacionLista($hu, 'I', 2, min($ultima, self::MAX_FILAS_VALIDACION + 1),
				'Estatus!$A$2:$A$' . (1 + count($estatus)));
			$hu->setAutoFilter('A1:I' . $ultima);
		}
		$hu->freezePane('A2');
		$anchos = ['A' => 14, 'B' => 22, 'C' => 20, 'D' => 26, 'E' => 34, 'F' => 40, 'G' => 16, 'H' => 28, 'I' => 24];
		foreach ($anchos as $col => $w) {
			$hu->getColumnDimension($col)->setWidth($w);
		}
		$ss->setActiveSheetIndex(2);

		$this->enviarXlsx($ss, 'Layout_Cobranza_Unidades_' . date('Ymd_His') . '.xlsx');
	}

	/* ===============================
	   CARGA MASIVA (solo actualización de ESTATUS_PAGO)
	=============================== */
	public function procesarCarga()
	{
		header('Content-Type: application/json; charset=utf-8');
		if (empty($_SESSION['permisosMod']['u'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para actualizar estatus de pago.'], JSON_UNESCAPED_UNICODE);
			die();
		}
		@set_time_limit(600);

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}
		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene unidades para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$log = [];
		$addLog = function (array $row, string $motivo) use (&$log) {
			$log[] = [
				'fila' => $row['_fila'] ?? '',
				'vin' => $row['vin'] ?? '',
				'sku' => $row['sku'] ?? '',
				'estatus' => $row['estatus_pago'] ?? '',
				'motivo' => $motivo,
			];
		};

		$vines = [];
		foreach ($rows as $row) {
			$vin = strtoupper(trim($row['vin'] ?? ''));
			if ($vin !== '') {
				$vines[] = $vin;
			}
		}
		$bd = $this->model->mapUnidadesPorVin($vines);

		$usuarioid = $this->usuarioId();
		$vistos = [];
		$actualizados = 0;
		$sinCambio = 0;

		foreach ($rows as $row) {
			$vin = strtoupper(trim($row['vin'] ?? ''));
			if ($vin === '') {
				$addLog($row, 'Fila sin VIN.');
				continue;
			}
			if (isset($vistos[$vin])) {
				$addLog($row, "VIN repetido en el archivo (ya viene en la fila {$vistos[$vin]}).");
				continue;
			}
			$vistos[$vin] = $row['_fila'];

			$txtEstatus = trim($row['estatus_pago'] ?? '');
			if ($txtEstatus === '') {
				$addLog($row, 'ESTATUS_PAGO vacío.');
				continue;
			}
			$nuevo = $this->parsearEstatus($txtEstatus);
			if ($nuevo === null) {
				$addLog($row, "ESTATUS_PAGO '{$txtEstatus}' no válido. Usa Pendiente, Crédito, Parcialmente pagada o Pagada (si trae número, debe coincidir con el texto).");
				continue;
			}
			if (!isset($bd[$vin])) {
				$addLog($row, 'El VIN no existe o ya no está Facturado (pudo haberse devuelto).');
				continue;
			}
			$u = $bd[$vin];
			$folio = trim($row['folio_venta'] ?? '');
			if ($folio !== '' && strcasecmp($folio, (string) $u['folio_venta']) !== 0) {
				$addLog($row, "La venta del VIN cambió desde que se descargó el layout (folio actual {$u['folio_venta']}).");
				continue;
			}
			if ((int) $u['estatus_pago'] === $nuevo) {
				$sinCambio++;
				continue;
			}
			try {
				$this->model->guardarEstatus($u, $nuevo, $usuarioid, 'MASIVA');
				$actualizados++;
			} catch (\Throwable $e) {
				error_log('Inv_cobranzaunidades::procesarCarga ' . $vin . ': ' . $e->getMessage());
				$addLog($row, 'Error al guardar el estatus. Inténtalo de nuevo.');
			}
		}

		$_SESSION['cobranzaUnidadesLog'] = $log;
		$omitidos = count($log);
		$msg = "{$actualizados} unidad(es) actualizada(s), {$sinCambio} sin cambios";
		$msg .= $omitidos > 0 ? " y {$omitidos} fila(s) no se aplicaron (descarga el log)." : '.';

		echo json_encode([
			'status' => true,
			'msg' => $msg,
			'actualizados' => $actualizados,
			'sin_cambio' => $sinCambio,
			'omitidos' => $omitidos,
		], JSON_UNESCAPED_UNICODE);
		die();
	}

	public function exportarLog()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$log = $_SESSION['cobranzaUnidadesLog'] ?? [];
		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sh = $ss->getActiveSheet();
		$sh->setTitle('Log');
		$sh->setCellValue('A1', 'Log de unidades NO actualizadas (Cobranza de unidades)');
		$sh->mergeCells('A1:E1');
		$sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sh->fromArray(['FILA_ARCHIVO', 'VIN', 'SKU', 'ESTATUS_PAGO', 'MOTIVO'], null, 'A4');
		$sh->getStyle('A4:E4')->getFont()->setBold(true);
		$sh->getStyle('A4:E4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');
		$sh->freezePane('A5');
		$r = 5;
		foreach ($log as $it) {
			$sh->setCellValue('A' . $r, $it['fila']);
			$sh->setCellValueExplicit('B' . $r, (string) $it['vin'], $T);
			$sh->setCellValueExplicit('C' . $r, (string) $it['sku'], $T);
			$sh->setCellValueExplicit('D' . $r, (string) $it['estatus'], $T);
			$sh->setCellValueExplicit('E' . $r, (string) $it['motivo'], $T);
			$r++;
		}
		foreach (['A' => 14, 'B' => 22, 'C' => 26, 'D' => 24, 'E' => 90] as $col => $w) {
			$sh->getColumnDimension($col)->setWidth($w);
		}
		$this->enviarXlsx($ss, 'Log_Cobranza_Unidades_' . date('Ymd_His') . '.xlsx');
	}

	/* ===============================
	   HELPERS
	=============================== */

	/** Ejecuta $fn y responde JSON; valida el permiso y atrapa errores de BD. */
	private function json(callable $fn, string $permiso, string $msgError): void
	{
		header('Content-Type: application/json; charset=utf-8');
		if (empty($_SESSION['permisosMod'][$permiso])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para esta acción.', 'data' => []], JSON_UNESCAPED_UNICODE);
			die();
		}
		try {
			echo json_encode($fn(), JSON_UNESCAPED_UNICODE);
		} catch (\Throwable $e) {
			error_log('Inv_cobranzaunidades: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => $msgError, 'data' => []], JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	private function filaPublica(array $u): array
	{
		return [
			'id_numeros_serie' => (int) $u['id_numeros_serie'],
			'idmovinventario' => (int) $u['idmovinventario'],
			'fecha_venta' => (string) $u['fecha_venta'],
			'vin' => (string) $u['numero_serie'],
			'numero_motor' => (string) $u['numero_motor'],
			'sku' => (string) $u['cve_articulo'],
			'descripcion' => (string) $u['descripcion'],
			'distribuidor' => (string) $u['distribuidor'],
			'factura' => (string) $u['factura'],
			'folio_venta' => (string) $u['folio_venta'],
			'almacen_salida' => (string) $u['almacen_salida'],
			'estatus_pago' => (int) $u['estatus_pago'],
			'fecha_estatus' => (string) ($u['fecha_estatus'] ?? ''),
			'usuario_estatus' => (string) ($u['usuario_estatus'] ?? ''),
		];
	}

	private function filtrosFechas(): array
	{
		return [
			'desde' => $this->fechaValida($_GET['desde'] ?? ''),
			'hasta' => $this->fechaValida($_GET['hasta'] ?? ''),
		];
	}

	private function textoFiltros(array $f): string
	{
		$p = [];
		if (!empty($f['desde']) || !empty($f['hasta'])) {
			$p[] = 'Ventas ' . ($f['desde'] ?: 'inicio') . ' a ' . ($f['hasta'] ?: 'hoy');
		}
		if (($f['distribuidor'] ?? '') !== '') {
			$p[] = 'Distribuidor: ' . ($f['distribuidor'] === '__SIN__' ? 'Sin distribuidor' : $f['distribuidor']);
		}
		if (($f['modelo'] ?? '') !== '') {
			$p[] = 'Modelo: ' . $f['modelo'];
		}
		if (($f['estatus'] ?? '') !== '' && isset(Inv_cobranzaunidadesModel::ESTATUS[(int) $f['estatus']])) {
			$p[] = 'Estatus: ' . Inv_cobranzaunidadesModel::ESTATUS[(int) $f['estatus']];
		}
		return $p ? implode(' · ', $p) : 'Sin filtros';
	}

	private function usuarioId(): ?int
	{
		return !empty($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : null;
	}

	private function fechaValida(string $f): ?string
	{
		$f = trim($f);
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) ? $f : null;
	}

	/**
	 * Interpreta ESTATUS_PAGO. Acepta el texto ("Parcialmente pagada", "pagada",
	 * "credito"...), el número solo (0-3) o "número - texto". Si trae número Y texto
	 * deben coincidir: así una serie generada al arrastrar la celda en Excel
	 * ("2 - Parcialmente pagada" -> "3 - Parcialmente pagada") se rechaza en vez de
	 * tomarse como otro estatus.
	 */
	private function parsearEstatus(string $txt): ?int
	{
		$t = $this->normalizarTexto($txt);
		if ($t === '') {
			return null;
		}
		$num = null;
		if (preg_match('/^(\d+)\s*(?:-\s*)?(.*)$/', $t, $m)) {
			$num = (int) $m[1];
			$t = trim($m[2]);
		}
		$porTexto = $t === '' ? null : $this->estatusPorTexto($t);
		if ($num === null) {
			return $porTexto;
		}
		if (!isset(Inv_cobranzaunidadesModel::ESTATUS[$num])) {
			return null;
		}
		if ($t !== '' && $porTexto !== $num) {
			return null; // número y texto no coinciden
		}
		return $num;
	}

	private function estatusPorTexto(string $t): ?int
	{
		foreach (Inv_cobranzaunidadesModel::ESTATUS as $k => $v) {
			if ($t === $this->normalizarTexto($v)) {
				return $k;
			}
		}
		$alias = ['PARCIAL' => 2, 'PAGO PARCIAL' => 2, 'PAGADO' => 3, 'CREDITO' => 1, 'PENDIENTE' => 0];
		return $alias[$t] ?? null;
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
			// En PhpSpreadsheet, showDropDown=true SÍ muestra la flecha (atributo invertido).
			$dv->setShowDropDown(true);
			$dv->setFormula1($formula);
		}
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
			return $this->leerArchivo($_FILES['archivo']['tmp_name']);
		} catch (\Throwable $e) {
			error_log('Inv_cobranzaunidades::leerArchivo: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible leer el archivo. Usa el layout descargado de esta pantalla (hoja "Unidades").'], JSON_UNESCAPED_UNICODE);
			return null;
		}
	}

	private function leerArchivo(string $tmpName): array
	{
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
		$reader->setReadDataOnly(true);
		$spreadsheet = $reader->load($tmpName);
		$sheet = $spreadsheet->sheetNameExists('Unidades')
			? $spreadsheet->getSheetByName('Unidades')
			: $spreadsheet->getSheet(0);

		$highestRow = $sheet->getHighestDataRow();
		$highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

		$mapa = ['VIN' => 'vin', 'SKU' => 'sku', 'FOLIO_VENTA' => 'folio_venta', 'ESTATUS_PAGO' => 'estatus_pago'];
		$columnas = [];
		for ($col = 1; $col <= $highestCol; $col++) {
			$texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
			$clave = preg_replace('/\s+/', '_', strtoupper($this->quitarAcentos($texto)));
			if (isset($mapa[$clave])) {
				$columnas[$col] = $mapa[$clave];
			}
		}
		foreach (['vin', 'estatus_pago'] as $req) {
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
		return str_replace(
			['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'],
			['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'],
			$texto
		);
	}

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
		if (is_float($valor) && fmod($valor, 1.0) === 0.0 && abs($valor) < 1e15) {
			return (string) (int) $valor;
		}
		return trim((string) $valor);
	}
}
