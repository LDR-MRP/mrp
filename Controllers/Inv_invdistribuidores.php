<?php
/**
 * INVENTARIO DE DISTRIBUIDORES (conteo reportado por distribuidor).
 *
 * - Almacén lógico ALM-DISTRIBUIDORES; el distribuidor va por VIN.
 * - Cada carga genera un CORTE por distribuidor y fecha (historial). El corte
 *   vigente de cada distribuidor es su último corte activo.
 * - Solo se aceptan VINs que existen en wms_numeros_series, están Facturados (3)
 *   y cuya última venta fue a ese mismo distribuidor. Lo demás va al log.
 * - No mueve existencias (wms_multialmacen / kardex): las unidades ya salieron
 *   del MRP con su venta.
 * Permiso propio: módulo 85 (MIINVDISTRIBUIDORES).
 */
class Inv_invdistribuidores extends Controllers
{
	private const MAX_FILAS_PLANTILLA = 3000;

	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		getPermisos(MIINVDISTRIBUIDORES);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_invdistribuidores()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Inventario de distribuidores";
		$data['page_title'] = "Inventario de distribuidores";
		$data['page_name'] = "invdistribuidores";
		$data['page_functions_js'] = "functions_inv_invdistribuidores.js";
		$data['almacen'] = $this->model->selectAlmacenDistribuidores();
		$this->views->getView($this, "inv_invdistribuidores", $data);
	}

	/* ===============================
	   CONSULTAS (JSON)
	=============================== */
	public function getDistribuidores()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$out = [];
		foreach ($this->model->selectDistribuidores() as $c) {
			$out[] = ['idcliente' => (int) $c['idcliente'], 'nombre' => $this->etiquetaCliente($c)];
		}
		$this->json($out);
	}

	public function getInventario()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$this->json($this->model->selectInventarioVigente($this->clienteGet()));
	}

	/** Resumen por distribuidor: facturados vs reportados en su corte vigente. */
	public function getConciliacion()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$res = [];
		$base = function (array $r) {
			return [
				'clienteid' => (int) $r['clienteid'],
				'distribuidor' => $r['distribuidor'],
				'folio' => '',
				'fecha_corte' => '',
				'facturados' => 0,
				'reportados' => 0,
				'no_reportados' => 0,
				'en_corte' => 0,
				'disponibles' => 0,
				'facturados_dist' => 0,
				'no_conciliados' => 0,
			];
		};

		foreach ($this->model->selectFacturadosConciliacion() as $r) {
			$id = (int) $r['clienteid'];
			$res[$id] = $res[$id] ?? $base($r);
			$res[$id]['facturados']++;
			if ((int) $r['reportado'] === 1) {
				$res[$id]['reportados']++;
			} else {
				$res[$id]['no_reportados']++;
			}
		}
		foreach ($this->model->selectInventarioVigente() as $r) {
			$id = (int) $r['clienteid'];
			$res[$id] = $res[$id] ?? $base($r);
			$res[$id]['folio'] = $r['folio'];
			$res[$id]['fecha_corte'] = $r['fecha_corte'];
			$res[$id]['en_corte']++;
			if ($r['estatus_distribuidor'] === 'FACTURADO') {
				$res[$id]['facturados_dist']++;
			} else {
				$res[$id]['disponibles']++;
			}
			if ((int) $r['conciliado'] === 0) {
				$res[$id]['no_conciliados']++;
			}
		}

		$out = array_values($res);
		usort($out, function ($a, $b) {
			return strcmp($a['distribuidor'], $b['distribuidor']);
		});
		$this->json($out);
	}

	public function getCortes()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$this->json($this->model->selectCortes());
	}

	public function getDetalleCorte($id = null)
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$id = (int) $id;
		if ($id <= 0) {
			$this->json([]);
		}
		$this->json($this->model->selectDetalleCorte($id));
	}

	public function cancelarCorte()
	{
		if (empty($_SESSION['permisosMod']['d'])) {
			$this->json(['status' => false, 'msg' => 'No tienes permisos para cancelar cortes.']);
		}
		$id = (int) ($_POST['idcorte'] ?? 0);
		$corte = $id > 0 ? $this->model->selectCorte($id) : null;
		if (!$corte || (int) $corte['estado'] !== 2) {
			$this->json(['status' => false, 'msg' => 'El corte no existe o ya está cancelado.']);
		}
		$ok = $this->model->cancelarCorte($id);
		$this->json([
			'status' => $ok,
			'msg' => $ok
				? "Corte {$corte['folio']} cancelado. El inventario vigente del distribuidor pasa a su corte activo anterior."
				: 'No fue posible cancelar el corte.',
		]);
	}

	/* ===============================
	   PLANTILLA (XLSX)
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
		$distribuidores = $this->model->selectDistribuidores();

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()
			->setCreator('LDR Solutions · MRP')
			->setTitle('Plantilla Inventario de Distribuidores');

		/* ---------- Instrucciones ---------- */
		$h1 = $ss->getActiveSheet();
		$h1->setTitle('Instrucciones');
		$lineas = [
			'PLANTILLA DE INVENTARIO DE DISTRIBUIDORES',
			'',
			'1. La hoja "Inventario" ya trae las unidades que LDR vendió a cada distribuidor.',
			'2. ESTATUS de cada VIN: Disponible = sigue en el piso del distribuidor; Facturado = el distribuidor ya lo facturó a su cliente final. Vacío = Disponible.',
			'3. DISTRIBUIDOR: elígelo de la lista (hoja "Distribuidores"). Si lo dejas vacío se usa el distribuidor elegido en la pantalla al subir.',
			'4. VIN: obligatorio. Debe existir en el sistema, estar vendido por LDR y su última venta debe ser a ese mismo distribuidor. UBICACION y OBSERVACIONES son opcionales.',
			'5. Cada distribuidor del archivo genera su propio corte con la fecha de corte que elijas al subir.',
			'6. El último corte de cada distribuidor es su inventario vigente; los anteriores quedan como historial.',
			'7. Los VINs que no cumplan las reglas no se suben y quedan en un log descargable con el motivo.',
		];
		foreach ($lineas as $i => $txt) {
			$h1->setCellValue('A' . ($i + 1), $txt);
		}
		$h1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$h1->getColumnDimension('A')->setWidth(130);

		/* ---------- Distribuidores (fuente del dropdown) ---------- */
		$h2 = $ss->createSheet();
		$h2->setTitle('Distribuidores');
		$h2->setCellValue('A1', 'DISTRIBUIDOR');
		$h2->getStyle('A1')->getFont()->setBold(true);
		$r = 2;
		foreach ($distribuidores as $c) {
			$h2->setCellValueExplicit('A' . $r, $this->etiquetaCliente($c), $T);
			$r++;
		}
		$ultimaDist = max(2, $r - 1);
		$h2->getColumnDimension('A')->setWidth(60);

		/* ---------- Inventario (captura, prellenada con las ventas) ---------- */
		// Trae los VINs Facturados a distribuidores (filtro opcional ?clienteid=).
		// Al subir solo se leen DISTRIBUIDOR, VIN, ESTATUS, UBICACION y OBSERVACIONES;
		// SKU, MODELO, FACTURA y FECHA_VENTA son informativas (encabezado gris).
		// ESTATUS se prellena con el del corte vigente (o Disponible si no se ha reportado).
		$vendidos = $this->model->selectFacturadosConciliacion($this->clienteGet());
		$h3 = $ss->createSheet();
		$h3->setTitle('Inventario');
		$h3->fromArray(['DISTRIBUIDOR', 'VIN', 'ESTATUS', 'SKU', 'MODELO', 'FACTURA', 'FECHA_VENTA', 'UBICACION', 'OBSERVACIONES'], null, 'A1');
		$h3->getStyle('A1:I1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$h3->getStyle('A1:I1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF1F4E78');
		$h3->getStyle('D1:G1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF7F7F7F');
		$h3->freezePane('A2');
		$fila = 2;
		foreach ($vendidos as $v) {
			$estatus = $v['estatus_distribuidor'] === 'FACTURADO' ? 'Facturado' : 'Disponible';
			$vals = [$v['distribuidor'], $v['vin'], $estatus, $v['cve_articulo'], $v['descripcion'], $v['factura'], $v['fecha_venta']];
			foreach ($vals as $i => $val) {
				$h3->setCellValueExplicit(chr(65 + $i) . $fila, (string) $val, $T);
			}
			$fila++;
		}
		foreach (['A' => 50, 'B' => 22, 'C' => 14, 'D' => 16, 'E' => 35, 'F' => 14, 'G' => 13, 'H' => 30, 'I' => 40] as $col => $w) {
			$h3->getColumnDimension($col)->setWidth($w);
		}
		$hasta = ($fila - 1) + self::MAX_FILAS_PLANTILLA;
		$h3->getStyle('B2:B' . $hasta)
			->getNumberFormat()->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		$this->agregarValidacionLista($h3, 'A', 2, $hasta, "'Distribuidores'!\$A\$2:\$A\${$ultimaDist}");
		$this->agregarValidacionLista($h3, 'C', 2, $hasta, '"Disponible,Facturado"');

		$ss->setActiveSheetIndex(2);
		$this->enviarXlsx($ss, 'Plantilla_Inventario_Distribuidores_' . date('Ymd') . '.xlsx');
	}

	/* ===============================
	   CARGA MASIVA
	=============================== */
	public function procesarCarga()
	{
		if (empty($_SESSION['permisosMod']['w'])) {
			$this->json(['status' => false, 'msg' => 'No tienes permisos para subir inventario de distribuidores.']);
		}
		@set_time_limit(600);

		$almacen = $this->model->selectAlmacenDistribuidores();
		if (!$almacen) {
			$this->json(['status' => false, 'msg' => 'No existe el almacén ALM-DISTRIBUIDORES. Corre el script de base de datos del módulo.']);
		}

		$fechaCorte = trim((string) ($_POST['fecha_corte'] ?? ''));
		$dt = \DateTime::createFromFormat('Y-m-d', $fechaCorte);
		if (!$dt || $dt->format('Y-m-d') !== $fechaCorte) {
			$this->json(['status' => false, 'msg' => 'Selecciona una fecha de corte válida.']);
		}
		if ($fechaCorte > date('Y-m-d')) {
			$this->json(['status' => false, 'msg' => 'La fecha de corte no puede ser futura.']);
		}
		$observaciones = mb_substr(trim(strip_tags((string) ($_POST['observaciones'] ?? ''))), 0, 255);
		$clienteForm = (int) ($_POST['clienteid'] ?? 0);

		$rows = $this->obtenerFilasArchivo();

		// Catálogo de distribuidores: "CODIGO - NOMBRE", código o nombre
		$clientesMap = [];
		$clientesNombre = [];
		foreach ($this->model->selectDistribuidores() as $c) {
			$id = (int) $c['idcliente'];
			$clientesNombre[$id] = $this->etiquetaCliente($c);
			$clientesMap[$this->normalizarTexto($this->etiquetaCliente($c))] = $id;
			if (trim((string) $c['codigo_cliente']) !== '') {
				$clientesMap[$this->normalizarTexto((string) $c['codigo_cliente'])] = $id;
			}
			$clientesMap[$this->normalizarTexto((string) $c['nombre'])] = $id;
		}
		if ($clienteForm > 0 && !isset($clientesNombre[$clienteForm])) {
			$this->json(['status' => false, 'msg' => 'El distribuidor seleccionado no es un distribuidor activo.']);
		}

		$vinesBD = $this->model->mapVines(array_values(array_filter(array_map(function ($r) {
			return $this->normalizarVin($r['vin'] ?? '');
		}, $rows))));

		$log = [];
		$grupos = [];      // clienteid => [vines]
		$vinesVistos = [];

		foreach ($rows as $row) {
			$fila = $row['_fila'];
			$vin = $this->normalizarVin($row['vin'] ?? '');
			$distTxt = trim((string) ($row['distribuidor'] ?? ''));
			$base = ['fila' => $fila, 'distribuidor' => $distTxt, 'vin' => $vin];

			if ($distTxt !== '') {
				$idCliente = $clientesMap[$this->normalizarTexto($distTxt)] ?? null;
				if (!$idCliente) {
					$log[] = $base + ['motivo' => "\"{$distTxt}\" no es un distribuidor activo (elígelo de la lista)"];
					continue;
				}
			} elseif ($clienteForm > 0) {
				$idCliente = $clienteForm;
				$base['distribuidor'] = $clientesNombre[$clienteForm];
			} else {
				$log[] = $base + ['motivo' => 'Falta el DISTRIBUIDOR (captúralo en el archivo o elígelo en la pantalla)'];
				continue;
			}

			if ($vin === '') {
				$log[] = $base + ['motivo' => 'Fila sin VIN'];
				continue;
			}
			if (isset($vinesVistos[$vin])) {
				$log[] = $base + ['motivo' => "VIN repetido en el archivo (ya se cargó en la fila {$vinesVistos[$vin]})"];
				continue;
			}

			$estTxt = $this->normalizarTexto((string) ($row['estatus'] ?? ''));
			if ($estTxt === '' || $estTxt === 'DISPONIBLE') {
				$estatus = 'DISPONIBLE';
			} elseif ($estTxt === 'FACTURADO') {
				$estatus = 'FACTURADO';
			} else {
				$log[] = $base + ['motivo' => "ESTATUS \"{$row['estatus']}\" no válido (usa Disponible o Facturado)"];
				continue;
			}

			$bd = $vinesBD[$vin] ?? null;
			if (!$bd) {
				$log[] = $base + ['motivo' => 'El VIN no existe en el sistema'];
				continue;
			}
			if ((string) $bd['estado'] !== '3') {
				$est = ['1' => '1 - Disponible', '2' => '2 - Tatuado'][(string) $bd['estado']] ?? (string) $bd['estado'];
				$log[] = $base + ['motivo' => "El VIN no está Facturado (estatus actual: {$est}); no se puede reportar en un distribuidor"];
				continue;
			}
			if ((int) $bd['id_cliente'] !== (int) $idCliente) {
				$aQuien = isset($clientesNombre[(int) $bd['id_cliente']])
					? $clientesNombre[(int) $bd['id_cliente']]
					: 'otro cliente';
				$fac = $bd['factura'] !== '' ? " (factura {$bd['factura']})" : '';
				$log[] = $base + ['motivo' => "El VIN está facturado a {$aQuien}{$fac}, no a este distribuidor"];
				continue;
			}

			$vinesVistos[$vin] = $fila;
			$grupos[$idCliente][] = [
				'id_numeros_serie' => (int) $bd['id_numeros_serie'],
				'numero_serie' => $bd['numero_serie'],
				'inventarioid' => (int) $bd['inventarioid'],
				'estatus' => $estatus,
				'ubicacion' => mb_substr(trim((string) ($row['ubicacion'] ?? '')), 0, 150),
				'observaciones' => mb_substr(trim((string) ($row['observaciones'] ?? '')), 0, 255),
				'fila' => $fila,
			];
		}

		$usuarioid = !empty($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : null;
		$archivo = mb_substr(basename((string) $_FILES['archivo']['name']), 0, 255);
		$folios = [];
		$cargados = 0;

		foreach ($grupos as $idCliente => $vines) {
			try {
				$folio = $this->model->insertCorte(
					(int) $idCliente, (int) $almacen['idalmacen'], $fechaCorte,
					$observaciones !== '' ? $observaciones : null, $archivo, $usuarioid, $vines
				);
				$folios[] = $folio . ' (' . $clientesNombre[$idCliente] . ': ' . count($vines) . ')';
				$cargados += count($vines);
			} catch (\Throwable $e) {
				error_log('Inv_invdistribuidores::procesarCarga: ' . $e->getMessage());
				foreach ($vines as $v) {
					$log[] = ['fila' => $v['fila'], 'distribuidor' => $clientesNombre[$idCliente],
						'vin' => $v['numero_serie'], 'motivo' => 'Error al guardar el corte de este distribuidor'];
				}
			}
		}

		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});
		$_SESSION['cargaInvDistribuidoresLog'] = $log;

		$msg = "Proceso finalizado: {$cargados} VIN(s) cargado(s) en " . count($folios) . ' corte(s)';
		$msg .= count($log) > 0 ? ', ' . count($log) . ' fila(s) no subida(s)' : '';
		$msg .= ' de ' . count($rows) . ' fila(s).';

		$this->json([
			'status' => true,
			'cargados' => $cargados,
			'cortes' => $folios,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => $msg,
		]);
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
		$sh->setCellValue('A1', 'Log de VINs NO cargados (Inventario de distribuidores)');
		$sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sh->fromArray(['FILA_ARCHIVO', 'DISTRIBUIDOR', 'VIN', 'MOTIVO'], null, 'A4');
		$sh->getStyle('A4:D4')->getFont()->setBold(true);
		$sh->freezePane('A5');
		$r = 5;
		foreach ($_SESSION['cargaInvDistribuidoresLog'] ?? [] as $it) {
			$sh->setCellValue('A' . $r, $it['fila'] ?? '');
			$sh->setCellValueExplicit('B' . $r, (string) ($it['distribuidor'] ?? ''), $T);
			$sh->setCellValueExplicit('C' . $r, (string) ($it['vin'] ?? ''), $T);
			$sh->setCellValueExplicit('D' . $r, (string) ($it['motivo'] ?? ''), $T);
			$r++;
		}
		$sh->getColumnDimension('A')->setWidth(14);
		$sh->getColumnDimension('B')->setWidth(45);
		$sh->getColumnDimension('C')->setWidth(22);
		$sh->getColumnDimension('D')->setWidth(90);
		$this->enviarXlsx($ss, 'Log_Inventario_Distribuidores_' . date('Ymd_His') . '.xlsx');
	}

	/** Excel de conciliación: VINs facturados a distribuidores, reportados o no en su corte vigente. */
	public function exportarConciliacion()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		@set_time_limit(300);
		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
		$filas = $this->model->selectFacturadosConciliacion($this->clienteGet());

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sh = $ss->getActiveSheet();
		$sh->setTitle('Conciliacion');
		$enc = ['DISTRIBUIDOR', 'VIN', 'SKU', 'MODELO', 'NUMERO_MOTOR', 'FACTURA', 'FECHA_VENTA', 'REPORTADO', 'ESTATUS_DISTRIBUIDOR', 'CORTE_VIGENTE', 'FECHA_CORTE'];
		$sh->fromArray($enc, null, 'A1');
		$sh->getStyle('A1:K1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$sh->getStyle('A1:K1')->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF1F4E78');
		$sh->freezePane('A2');

		$r = 2;
		foreach ($filas as $f) {
			$vals = [$f['distribuidor'], $f['vin'], $f['cve_articulo'], $f['descripcion'], $f['numero_motor'],
				$f['factura'], $f['fecha_venta'], ((int) $f['reportado'] === 1 ? 'SI' : 'NO'),
				($f['estatus_distribuidor'] === 'FACTURADO' ? 'Facturado' : ($f['estatus_distribuidor'] === 'DISPONIBLE' ? 'Disponible' : '')),
				$f['folio'] ?? '', $f['fecha_corte'] ?? ''];
			foreach ($vals as $i => $v) {
				$sh->setCellValueExplicit(chr(65 + $i) . $r, (string) $v, $T);
			}
			if ((int) $f['reportado'] !== 1) {
				$sh->getStyle('H' . $r)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FFF8D7DA');
			}
			$r++;
		}
		foreach (['A' => 45, 'B' => 22, 'C' => 18, 'D' => 40, 'E' => 20, 'F' => 16, 'G' => 13, 'H' => 12, 'I' => 20, 'J' => 14, 'K' => 13] as $col => $w) {
			$sh->getColumnDimension($col)->setWidth($w);
		}
		if ($r > 2) {
			$sh->setAutoFilter('A1:K' . ($r - 1));
		}
		$this->enviarXlsx($ss, 'Conciliacion_Inventario_Distribuidores_' . date('Ymd_His') . '.xlsx');
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

	private function clienteGet(): ?int
	{
		$id = (int) ($_GET['clienteid'] ?? 0);
		return $id > 0 ? $id : null;
	}

	private function etiquetaCliente(array $c): string
	{
		$cod = trim((string) ($c['codigo_cliente'] ?? ''));
		return $cod !== '' ? $cod . ' - ' . $c['nombre'] : (string) $c['nombre'];
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

	private function agregarValidacionLista($hoja, string $columna, int $desde, int $hasta, string $formula): void
	{
		$dv = $hoja->getCell($columna . $desde)->getDataValidation();
		$dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
		$dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
		$dv->setAllowBlank(true);
		$dv->setShowInputMessage(true);
		$dv->setShowErrorMessage(true);
		// OJO: en PhpSpreadsheet, showDropDown=true SÍ muestra la flecha (atributo invertido).
		$dv->setShowDropDown(true);
		$dv->setFormula1($formula);
		$dv->setSqref($columna . $desde . ':' . $columna . $hasta);
	}

	/** Lee el Excel subido; si algo falla responde JSON y termina. */
	private function obtenerFilasArchivo(): array
	{
		if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
			$this->json(['status' => false, 'msg' => 'Selecciona un archivo válido (.xlsx).']);
		}
		$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, ['xlsx', 'xls'], true)) {
			$this->json(['status' => false, 'msg' => 'El archivo debe ser un Excel (.xlsx).']);
		}
		if ($_FILES['archivo']['size'] > 15 * 1024 * 1024) {
			$this->json(['status' => false, 'msg' => 'El archivo supera el límite de 15 MB.']);
		}

		try {
			$rows = $this->leerArchivo($_FILES['archivo']['tmp_name']);
		} catch (\Throwable $e) {
			error_log('Inv_invdistribuidores::leerArchivo: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'No fue posible leer el archivo. Usa la plantilla de esta pantalla (hoja "Inventario", columna VIN obligatoria).']);
		}
		if (empty($rows)) {
			$this->json(['status' => false, 'msg' => 'El archivo no contiene VINs para procesar.']);
		}
		return $rows;
	}

	/** Mapea columnas por texto de encabezado (no por posición). Solo VIN es obligatoria. */
	private function leerArchivo(string $tmpName): array
	{
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
		$reader->setReadDataOnly(true);
		$spreadsheet = $reader->load($tmpName);
		$sheet = $spreadsheet->sheetNameExists('Inventario')
			? $spreadsheet->getSheetByName('Inventario')
			: $spreadsheet->getSheet(0);

		$mapa = [
			'DISTRIBUIDOR' => 'distribuidor',
			'VIN' => 'vin',
			'NIV' => 'vin',
			'UBICACION' => 'ubicacion',
			'SUCURSAL' => 'ubicacion',
			'OBSERVACIONES' => 'observaciones',
			'COMENTARIOS' => 'observaciones',
			'ESTATUS' => 'estatus',
			'ESTATUS_DISTRIBUIDOR' => 'estatus',
			'COMMERCIAL_STATUS_NAME' => 'estatus',
		];

		$highestRow = $sheet->getHighestDataRow();
		$highestCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());
		$columnas = [];
		for ($col = 1; $col <= $highestCol; $col++) {
			$texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
			$clave = preg_replace('/\s+/', '_', strtoupper($this->quitarAcentos($texto)));
			if (isset($mapa[$clave]) && !in_array($mapa[$clave], $columnas, true)) {
				$columnas[$col] = $mapa[$clave];
			}
		}
		if (!in_array('vin', $columnas, true)) {
			throw new \RuntimeException('Falta la columna VIN.');
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

	/** VIN en mayúsculas y sin ningún espacio (los VIN no llevan espacios). */
	private function normalizarVin($vin): string
	{
		return strtoupper(preg_replace('/\s+/', '', (string) $vin));
	}

	private function quitarAcentos(string $texto): string
	{
		$busca = ['á', 'é', 'í', 'ó', 'ú', 'Á', 'É', 'Í', 'Ó', 'Ú', 'ñ', 'Ñ'];
		$reemplaza = ['a', 'e', 'i', 'o', 'u', 'A', 'E', 'I', 'O', 'U', 'n', 'N'];
		return str_replace($busca, $reemplaza, $texto);
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
