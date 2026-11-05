<?php
/**
 * RETAIL DE DISTRIBUIDORES.
 *
 * Almacena el retail que captura el área en una tabla propia, SIN relación
 * con unidades, VINs del MRP ni el catálogo de clientes.
 *
 * - SOLO se acepta la plantilla del módulo (descargarPlantilla): hoja "Retail"
 *   (captura libre, sin listas ni validaciones) + hoja oculta "Catalogos" con la marca
 *   MARCA_PLANTILLA en A1. exportarRetail genera el mismo formato.
 *
 * - Cada archivo subido es una CARGA (historial con folio RTD-000001).
 * - El archivo es acumulado: al subirlo se REEMPLAZAN los meses (MES RETAIL)
 *   que trae; los meses que no vienen en el archivo se conservan.
 * - Obligatorios por renglón: DISTRIBUIDOR, VIN y MES RETAIL. Lo demás es opcional.
 *
 * Permiso propio: módulo 85 (MIINVDISTRIBUIDORES).
 * Tablas: wms_dist_retail_cargas / wms_dist_retail.
 */
class Inv_invdistribuidores extends Controllers
{
	private const HOJA = 'Retail';
	private const HOJA_CATALOGOS = 'Catalogos';
	/** Marca en Catalogos!A1: solo se aceptan archivos hechos desde la plantilla del módulo. */
	private const MARCA_PLANTILLA = 'PLANTILLA_RETAIL_DISTRIBUIDORES_V1';
	/** Renglones con formato de texto/fecha en la plantilla. */
	private const FILAS_PLANTILLA = 20000;

	/** Encabezado del archivo (normalizado: sin acentos ni signos) => campo. */
	private const MAPA_ENCABEZADOS = [
		'DISTRIBUIDOR' => 'distribuidor',
		'VIN' => 'vin',
		'NIV' => 'vin',
		'MODELO' => 'modelo',
		'ANOMODELO' => 'anio_modelo',
		'ANO' => 'anio_modelo',
		'TIPO' => 'tipo',
		'ESTATUS' => 'estatus',
		'OBSERVACIONES' => 'observaciones',
		'FECHADEFACTURACION' => 'fecha_facturacion',
		'FECHAFACTURACION' => 'fecha_facturacion',
		'FOLIOFISCAL' => 'folio_fiscal',
		'NOMBREORAZONSOCIAL' => 'razon_social',
		'RAZONSOCIAL' => 'razon_social',
		'RFC' => 'rfc',
		'DIRECCION' => 'direccion',
		'CODIGOPOSTAL' => 'codigo_postal',
		'CP' => 'codigo_postal',
		'TELEFONO' => 'telefono',
		'EMAIL' => 'email',
		'CORREO' => 'email',
		'TIPODEPAGO' => 'tipo_pago',
		'INSTITUCION' => 'institucion',
		'ENGANCHE' => 'enganche',
		'PLAZO' => 'plazo',
		'NOMBREDELVENDEDOR' => 'vendedor',
		'VENDEDOR' => 'vendedor',
		'MODELOGEN' => 'modelo_gen',
		'MODELOGENERICO' => 'modelo_gen',
		'MOTOR' => 'motor',
		'MESRETAIL' => 'mes_retail',
	];

	/** Encabezados de la plantilla / exportación (mismo orden que el archivo original). */
	private const ENCABEZADOS = [
		'DISTRIBUIDOR' => 'distribuidor',
		'VIN' => 'vin',
		'MODELO' => 'modelo',
		'AÑO modelo' => 'anio_modelo',
		'TIPO' => 'tipo',
		'ESTATUS' => 'estatus',
		'OBSERVACIONES' => 'observaciones',
		'FECHA DE FACTURACIÓN' => 'fecha_facturacion',
		'FOLIO FISCAL' => 'folio_fiscal',
		'NOMBRE O RAZÓN SOCIAL' => 'razon_social',
		'R.F.C.' => 'rfc',
		'DIRECCIÓN' => 'direccion',
		'CODIGO POSTAL' => 'codigo_postal',
		'TELEFONO' => 'telefono',
		'E-MAIL' => 'email',
		'TIPO DE PAGO' => 'tipo_pago',
		'INSTITUCIÓN' => 'institucion',
		'ENGANCHE' => 'enganche',
		'PLAZO' => 'plazo',
		'NOMBRE DEL VENDEDOR' => 'vendedor',
		'MODELO GEN' => 'modelo_gen',
		'MOTOR' => 'motor',
		'MES RETAIL' => 'mes_retail',
	];

	/** Largo máximo por columna (igual que la tabla). */
	private const LARGOS = [
		'distribuidor' => 150, 'vin' => 30, 'modelo' => 120, 'tipo' => 60, 'estatus' => 80,
		'observaciones' => 255, 'fecha_facturacion_txt' => 60, 'folio_fiscal' => 80, 'razon_social' => 200,
		'rfc' => 120, 'direccion' => 255, 'codigo_postal' => 30, 'telefono' => 40, 'email' => 120,
		'tipo_pago' => 60, 'institucion' => 150, 'enganche' => 50, 'plazo' => 30, 'vendedor' => 100,
		'modelo_gen' => 40, 'motor' => 40,
	];

	/** Orden permitido del listado (índice de columna DataTables => columna SQL). */
	private const ORDEN = [
		0 => 'r.mes_retail', 1 => 'r.distribuidor', 2 => 'r.vin', 3 => 'r.modelo', 4 => 'r.modelo_gen',
		5 => 'r.anio_modelo', 6 => 'r.estatus', 7 => 'r.fecha_facturacion', 8 => 'r.razon_social',
		9 => 'r.tipo_pago', 10 => 'r.vendedor',
	];

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

		$data['page_tag'] = "Retail distribuidores";
		$data['page_title'] = "Retail de distribuidores";
		$data['page_name'] = "invdistribuidores";
		$data['page_functions_js'] = "functions_inv_invdistribuidores.js";
		$data['tablas_listas'] = $this->model->tablasListas();
		$this->views->getView($this, "inv_invdistribuidores", $data);
	}

	/* ===============================
	   CONSULTAS (JSON)
	=============================== */
	public function getFiltros()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$out = $this->model->selectCatalogosFiltro();
		$out['anios'] = $this->model->selectAniosDisponibles();
		$this->json($out);
	}

	/** Listado (DataTables serverSide). */
	public function getRetail()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$req = $_POST + $_GET;
		$f = $this->filtrosRequest($req);
		$f['search'] = mb_substr(trim((string) ($req['search']['value'] ?? '')), 0, 100);
		$idx = (int) ($req['order'][0]['column'] ?? 0);
		$f['order_col'] = self::ORDEN[$idx] ?? 'r.mes_retail';
		$f['order_dir'] = strtolower((string) ($req['order'][0]['dir'] ?? 'desc')) === 'asc' ? 'ASC' : 'DESC';
		$f['start'] = (int) ($req['start'] ?? 0);
		$f['length'] = (int) ($req['length'] ?? 25);

		$res = $this->model->selectRetailPaginado($f);
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
		$this->json($this->model->selectKpis($this->filtrosRequest($_GET)));
	}

	/** Matriz por mes de un año (agrupar=distribuidor|modelo_gen). */
	public function getResumen()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$anio = (int) ($_GET['anio'] ?? date('Y'));
		if ($anio < 2000 || $anio > 2100) {
			$anio = (int) date('Y');
		}
		$agrupar = ($_GET['agrupar'] ?? '') === 'modelo_gen' ? 'modelo_gen' : 'distribuidor';
		$f = $this->filtrosRequest($_GET);
		$this->json($this->model->selectResumenAnual($anio, $agrupar, $f));
	}

	public function getCargas()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		$this->json($this->model->selectCargas());
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
			error_log('Inv_invdistribuidores::eliminarCarga: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'No fue posible eliminar la carga.']);
		}
		$this->json([
			'status' => true,
			'msg' => "Carga {$carga['folio']} eliminada: se borraron {$borrados} renglón(es) de retail que seguían vigentes.",
		]);
	}

	/* ===============================
	   CARGA DEL ARCHIVO
	=============================== */
	public function procesarCarga()
	{
		if (empty($_SESSION['permisosMod']['w'])) {
			$this->json(['status' => false, 'msg' => 'No tienes permisos para subir el retail.']);
		}
		if (!$this->model->tablasListas()) {
			$this->json(['status' => false, 'msg' => 'Faltan las tablas del módulo. Corre el script migracion_retail_distribuidores_20261104.sql.']);
		}
		@set_time_limit(900);
		@ini_set('memory_limit', '1024M');

		$observaciones = mb_substr(trim(strip_tags((string) ($_POST['observaciones'] ?? ''))), 0, 255);
		[$hoja, $rows] = $this->obtenerFilasArchivo();

		$log = [];
		$filas = [];
		$meses = [];
		$vistos = [];   // "mes|vin" => fila

		foreach ($rows as $row) {
			$fila = $row['_fila'];
			$vin = strtoupper(preg_replace('/\s+/', '', (string) ($row['vin'] ?? '')));
			$dist = preg_replace('/\s+/', ' ', trim((string) ($row['distribuidor'] ?? '')));
			$base = ['fila' => $fila, 'distribuidor' => $dist, 'vin' => $vin];

			if ($vin === '') {
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => 'Falta el VIN'];
				continue;
			}
			if ($dist === '') {
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => 'Falta el DISTRIBUIDOR'];
				continue;
			}
			$mes = $this->parseMes($row['mes_retail_raw'] ?? null);
			if ($mes === null) {
				$val = $this->valorComoTexto($row['mes_retail_raw'] ?? '');
				$log[] = $base + ['tipo' => 'No cargada', 'motivo' => $val === '' ? 'Falta el MES RETAIL' : "MES RETAIL \"{$val}\" no es una fecha válida"];
				continue;
			}

			$clave = $mes . '|' . $vin;
			if (isset($vistos[$clave])) {
				$log[] = $base + ['tipo' => 'Aviso', 'motivo' => 'VIN repetido en el mismo MES RETAIL (también en la fila ' . $vistos[$clave] . '); se cargaron ambos'];
			} else {
				$vistos[$clave] = $fila;
			}

			$fecha = $this->parseFecha($row['fecha_facturacion_raw'] ?? null);
			if ($fecha['txt'] !== null) {
				$log[] = $base + ['tipo' => 'Aviso', 'motivo' => "FECHA DE FACTURACIÓN \"{$fecha['txt']}\" no se reconoció como fecha; se guardó como texto"];
			}

			$anio = (string) ($row['anio_modelo'] ?? '');
			$anio = ctype_digit($anio) && (int) $anio >= 1980 && (int) $anio <= 2100 ? (int) $anio : null;

			$reg = [
				'mes_retail' => $mes,
				'distribuidor' => $dist,
				'vin' => $vin,
				'anio_modelo' => $anio,
				'fecha_facturacion' => $fecha['date'],
				'fecha_facturacion_txt' => $fecha['txt'],
				'fila_archivo' => $fila,
			];
			foreach (['modelo', 'tipo', 'estatus', 'observaciones', 'folio_fiscal', 'razon_social', 'rfc', 'direccion',
				'codigo_postal', 'telefono', 'email', 'tipo_pago', 'institucion', 'enganche', 'plazo', 'vendedor',
				'modelo_gen', 'motor'] as $c) {
				$v = trim((string) ($row[$c] ?? ''));
				$reg[$c] = $v === '' ? null : $v;
			}
			foreach (self::LARGOS as $c => $max) {
				if ($reg[$c] !== null && mb_strlen($reg[$c]) > $max) {
					$reg[$c] = mb_substr($reg[$c], 0, $max);
				}
			}

			$filas[] = $reg;
			$meses[$mes] = true;
		}

		$omitidas = count(array_filter($log, function ($l) { return $l['tipo'] === 'No cargada'; }));
		$avisos = count($log) - $omitidas;
		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});
		$_SESSION['cargaRetailDistLog'] = $log;

		if (empty($filas)) {
			$this->json([
				'status' => true, 'cargados' => 0, 'omitidos' => $omitidas, 'avisos' => $avisos,
				'msg' => 'No se cargó nada: ninguna fila tiene DISTRIBUIDOR, VIN y MES RETAIL válidos. Revisa el log.',
			]);
		}

		try {
			$res = $this->model->insertCarga([
				'archivo' => mb_substr(basename((string) $_FILES['archivo']['name']), 0, 255),
				'hoja' => $hoja,
				'total_filas' => count($rows),
				'total_omitidas' => $omitidas,
				'observaciones' => $observaciones !== '' ? $observaciones : null,
				'usuarioid' => !empty($_SESSION['idUser']) ? (int) $_SESSION['idUser'] : null,
			], $filas, array_keys($meses));
		} catch (\Throwable $e) {
			error_log('Inv_invdistribuidores::procesarCarga: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'Error al guardar la carga; no se modificó nada. Detalle en el log del servidor.']);
		}

		$listaMeses = array_keys($meses);
		sort($listaMeses);
		$rango = substr(reset($listaMeses), 0, 7) . (count($listaMeses) > 1 ? ' a ' . substr(end($listaMeses), 0, 7) : '');

		$msg = "Carga {$res['folio']}: " . number_format(count($filas)) . ' renglón(es) de retail guardados ('
			. count($listaMeses) . " mes(es): {$rango}).";
		if ($res['reemplazadas'] > 0) {
			$msg .= ' Se reemplazaron ' . number_format($res['reemplazadas']) . ' renglón(es) que ya existían de esos meses.';
		}
		if ($omitidas > 0) {
			$msg .= " {$omitidas} fila(s) no se cargaron.";
		}
		if ($avisos > 0) {
			$msg .= " {$avisos} aviso(s).";
		}

		$this->json([
			'status' => true,
			'folio' => $res['folio'],
			'cargados' => count($filas),
			'reemplazados' => $res['reemplazadas'],
			'omitidos' => $omitidas,
			'avisos' => $avisos,
			'totalFilas' => count($rows),
			'msg' => $msg,
		]);
	}

	/* ===============================
	   EXCEL: PLANTILLA / EXPORTAR / LOG
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		@ini_set('memory_limit', '1024M');
		$ss = $this->construirPlantilla([]);
		$this->enviarXlsx($ss, 'Plantilla_Retail_Distribuidores_' . date('Ymd') . '.xlsx');
	}

	/**
	 * Exporta lo filtrado EN FORMATO PLANTILLA (con la marca),
	 * así se puede corregir y volver a subir.
	 */
	public function exportarRetail()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}
		@set_time_limit(600);
		@ini_set('memory_limit', '1024M');
		$f = $this->filtrosRequest($_GET);
		$f['search'] = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
		$ss = $this->construirPlantilla($this->model->selectRetailExport($f));
		$this->enviarXlsx($ss, 'Retail_Distribuidores_' . date('Ymd_His') . '.xlsx');
	}

	/**
	 * Arma la plantilla oficial: hoja "Retail" (captura libre, sin validaciones),
	 * hoja "Instrucciones" y hoja oculta "Catalogos" (solo la marca de plantilla).
	 * Si $rows trae datos, la hoja Retail sale prellenada.
	 */
	private function construirPlantilla(array $rows)
	{
		$NF = \PhpOffice\PhpSpreadsheet\Style\NumberFormat::class;
		$T = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
		$col = function (string $campo): string {
			$i = array_search($campo, array_values(self::ENCABEZADOS), true);
			return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
		};
		$hasta = max(self::FILAS_PLANTILLA, count($rows) + 1000);

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()->setCreator('LDR Solutions · MRP')->setTitle('Plantilla Retail de distribuidores');

		/* ---------- Hoja oculta con la marca de plantilla ---------- */
		$hc = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($ss, self::HOJA_CATALOGOS);
		$ss->addSheet($hc);
		$hc->setCellValue('A1', self::MARCA_PLANTILLA);
		$hc->setCellValue('B1', 'No modificar ni borrar esta hoja');
		$hc->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

		/* ---------- Retail (captura) ---------- */
		$sh = $ss->getSheet(0);
		$sh->setTitle(self::HOJA);
		$this->escribirEncabezados($sh);

		// Formatos por columna
		foreach (['vin', 'folio_fiscal', 'rfc', 'codigo_postal', 'telefono', 'motor', 'enganche', 'plazo'] as $c) {
			$sh->getStyle($col($c) . '2:' . $col($c) . $hasta)->getNumberFormat()->setFormatCode($NF::FORMAT_TEXT);
		}
		$sh->getStyle($col('fecha_facturacion') . '2:' . $col('fecha_facturacion') . $hasta)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
		$sh->getStyle($col('mes_retail') . '2:' . $col('mes_retail') . $hasta)->getNumberFormat()->setFormatCode('mmm-yyyy');

		// Datos (exportación)
		$campos = array_values(self::ENCABEZADOS);
		$r = 2;
		foreach ($rows as $row) {
			foreach ($campos as $i => $campo) {
				$v = $row[$campo] ?? null;
				if ($v === null || $v === '') {
					continue;
				}
				$cell = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . $r;
				if ($campo === 'mes_retail' || ($campo === 'fecha_facturacion' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v))) {
					$fecha = $campo === 'mes_retail' ? $v . '-01' : $v;
					$sh->setCellValue($cell, \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel(new \DateTime($fecha)));
				} elseif ($campo === 'anio_modelo') {
					$sh->setCellValue($cell, (int) $v);
				} else {
					$sh->setCellValueExplicit($cell, (string) $v, $T);
				}
			}
			$r++;
		}
		$ult = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($campos));
		$sh->setAutoFilter('A1:' . $ult . max(2, $r - 1));

		/* ---------- Instrucciones ---------- */
		$ins = new \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet($ss, 'Instrucciones');
		$ss->addSheet($ins, 1);
		$lineas = [
			'PLANTILLA DE RETAIL DE DISTRIBUIDORES',
			'',
			'1. Solo se acepta ESTA plantilla (descargada del módulo). No cambies el nombre de la hoja "Retail" ni sus encabezados.',
			'2. Obligatorias por fila (encabezado verde): DISTRIBUIDOR, VIN y MES RETAIL. Las demás columnas son opcionales.',
			'3. MES RETAIL: el mes de la venta (p. ej. 01/09/2026). FECHA DE FACTURACIÓN: de preferencia dd/mm/aaaa.',
			'4. Las celdas no tienen listas ni restricciones: captura o pega los datos como los manejan.',
			'5. No borres la hoja oculta "Catalogos": es la que identifica a la plantilla.',
			'6. El archivo es acumulado: al subirlo se REEMPLAZAN los meses que trae; los meses que no vengan se conservan.',
			'7. Las filas que no se puedan cargar y los avisos quedan en un log descargable.',
			'8. "Exportar" del módulo descarga los datos en este mismo formato: puedes corregirlos y volver a subir el archivo.',
		];
		foreach ($lineas as $i => $txt) {
			$ins->setCellValue('A' . ($i + 1), $txt);
		}
		$ins->getStyle('A1')->getFont()->setBold(true)->setSize(14);
		$ins->getColumnDimension('A')->setWidth(130);

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
		$sh->setCellValue('A1', 'Log de carga de Retail de distribuidores');
		$sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sh->fromArray(['FILA_ARCHIVO', 'TIPO', 'DISTRIBUIDOR', 'VIN', 'MOTIVO'], null, 'A4');
		$sh->getStyle('A4:E4')->getFont()->setBold(true);
		$sh->freezePane('A5');
		$r = 5;
		foreach ($_SESSION['cargaRetailDistLog'] ?? [] as $it) {
			$sh->setCellValue('A' . $r, $it['fila'] ?? '');
			$sh->setCellValueExplicit('B' . $r, (string) ($it['tipo'] ?? ''), $T);
			$sh->setCellValueExplicit('C' . $r, (string) ($it['distribuidor'] ?? ''), $T);
			$sh->setCellValueExplicit('D' . $r, (string) ($it['vin'] ?? ''), $T);
			$sh->setCellValueExplicit('E' . $r, (string) ($it['motivo'] ?? ''), $T);
			$r++;
		}
		foreach (['A' => 14, 'B' => 12, 'C' => 40, 'D' => 22, 'E' => 100] as $c => $w) {
			$sh->getColumnDimension($c)->setWidth($w);
		}
		$this->enviarXlsx($ss, 'Log_Retail_Distribuidores_' . date('Ymd_His') . '.xlsx');
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

	/** Filtros comunes (distribuidor, modelo_gen, estatus, tipo_pago, mes_desde, mes_hasta). */
	private function filtrosRequest(array $req): array
	{
		$f = [];
		foreach (['distribuidor', 'modelo_gen', 'estatus', 'tipo_pago'] as $c) {
			$f[$c] = mb_substr(trim((string) ($req['f_' . $c] ?? '')), 0, 200);
		}
		foreach (['mes_desde', 'mes_hasta'] as $c) {
			$v = trim((string) ($req['f_' . $c] ?? ''));
			$f[$c] = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $v) ? $v : '';
		}
		return $f;
	}

	private function escribirEncabezados($sh): void
	{
		$FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
		$enc = array_keys(self::ENCABEZADOS);
		$ult = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($enc));
		$sh->fromArray($enc, null, 'A1');
		$sh->getStyle("A1:{$ult}1")->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$sh->getStyle("A1:{$ult}1")->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF1F4E78');
		// Obligatorias en verde
		foreach (['A1', 'B1', 'W1'] as $c) {
			$sh->getStyle($c)->getFill()->setFillType($FILL)->getStartColor()->setARGB('FF198754');
		}
		$sh->freezePane('A2');
		$anchos = [40, 22, 30, 11, 18, 26, 30, 14, 26, 40, 16, 50, 12, 14, 28, 20, 26, 12, 10, 30, 16, 16, 12];
		foreach ($anchos as $i => $w) {
			$sh->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1))->setWidth($w);
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

	/** Lee el Excel subido; si algo falla responde JSON y termina. Regresa [hoja, filas]. */
	private function obtenerFilasArchivo(): array
	{
		if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
			$this->json(['status' => false, 'msg' => 'Selecciona un archivo válido (.xlsx). Si el archivo es grande, revisa upload_max_filesize del servidor.']);
		}
		$ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
		if (!in_array($ext, ['xlsx', 'xls'], true)) {
			$this->json(['status' => false, 'msg' => 'El archivo debe ser un Excel (.xlsx).']);
		}
		if ($_FILES['archivo']['size'] > 30 * 1024 * 1024) {
			$this->json(['status' => false, 'msg' => 'El archivo supera el límite de 30 MB.']);
		}

		try {
			$res = $this->leerArchivo($_FILES['archivo']['tmp_name']);
		} catch (\Throwable $e) {
			error_log('Inv_invdistribuidores::leerArchivo: ' . $e->getMessage());
			$this->json(['status' => false, 'msg' => 'No fue posible leer el archivo: ' . $e->getMessage()]);
		}
		if (empty($res[1])) {
			$this->json(['status' => false, 'msg' => 'La hoja "' . $res[0] . '" no contiene filas para procesar.']);
		}
		return $res;
	}

	/**
	 * Valida que sea la plantilla del módulo (marca en Catalogos!A1), lee la hoja "Retail"
	 * y mapea columnas por encabezado.
	 * Las fechas se dejan en crudo (*_raw) para interpretarlas después.
	 */
	private function leerArchivo(string $tmpName): array
	{
		$reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmpName);
		$reader->setReadDataOnly(true);
		$nombres = $reader->listWorksheetNames($tmpName);
		$noEsPlantilla = new \RuntimeException('el archivo no es la plantilla del módulo. Descarga la plantilla, pega tus datos en la hoja "Retail", y súbela.');
		if (!in_array(self::HOJA, $nombres, true) || !in_array(self::HOJA_CATALOGOS, $nombres, true)) {
			throw $noEsPlantilla;
		}
		$hoja = self::HOJA;
		$reader->setLoadSheetsOnly([$hoja, self::HOJA_CATALOGOS]);
		$spreadsheet = $reader->load($tmpName);
		if (trim((string) $spreadsheet->getSheetByName(self::HOJA_CATALOGOS)->getCell('A1')->getValue()) !== self::MARCA_PLANTILLA) {
			throw $noEsPlantilla;
		}
		$sheet = $spreadsheet->getSheetByName($hoja);

		$highestRow = $sheet->getHighestDataRow();
		$highestColStr = $sheet->getHighestDataColumn();
		$datos = $sheet->rangeToArray('A1:' . $highestColStr . $highestRow, null, false, false, false);
		$spreadsheet->disconnectWorksheets();
		unset($spreadsheet);

		// Encabezado: primera de las 5 primeras filas que tenga VIN y DISTRIBUIDOR
		$filaEnc = null;
		$columnas = [];
		for ($i = 0; $i < min(5, count($datos)); $i++) {
			$cols = [];
			foreach ($datos[$i] as $col => $texto) {
				$clave = preg_replace('/[^A-Z0-9]/', '', strtoupper($this->quitarAcentos(trim((string) $texto))));
				if (isset(self::MAPA_ENCABEZADOS[$clave]) && !in_array(self::MAPA_ENCABEZADOS[$clave], $cols, true)) {
					$cols[$col] = self::MAPA_ENCABEZADOS[$clave];
				}
			}
			if (in_array('vin', $cols, true) && in_array('distribuidor', $cols, true)) {
				$filaEnc = $i;
				$columnas = $cols;
				break;
			}
		}
		if ($filaEnc === null) {
			throw new \RuntimeException('en la hoja "' . $hoja . '" no se encontraron los encabezados DISTRIBUIDOR y VIN.');
		}
		if (!in_array('mes_retail', $columnas, true)) {
			throw new \RuntimeException('en la hoja "' . $hoja . '" falta la columna MES RETAIL.');
		}

		$rows = [];
		for ($i = $filaEnc + 1, $n = count($datos); $i < $n; $i++) {
			$reg = [];
			$vacio = true;
			foreach ($columnas as $col => $campo) {
				$raw = $datos[$i][$col] ?? null;
				if (in_array($campo, ['mes_retail', 'fecha_facturacion'], true)) {
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
			$rows[] = $reg;
		}
		return [$hoja, $rows];
	}

	/** MES RETAIL => 'YYYY-MM-01' o null. */
	private function parseMes($raw): ?string
	{
		$f = $this->parseFecha($raw);
		return $f['date'] !== null ? substr($f['date'], 0, 7) . '-01' : (
			($f['txt'] !== null && preg_match('/^(\d{4})[-\/](\d{1,2})$/', $f['txt'], $m) && (int) $m[2] >= 1 && (int) $m[2] <= 12)
				? sprintf('%04d-%02d-01', $m[1], $m[2])
				: (($f['txt'] !== null && preg_match('/^(\d{1,2})[-\/](\d{4})$/', $f['txt'], $m) && (int) $m[1] >= 1 && (int) $m[1] <= 12)
					? sprintf('%04d-%02d-01', $m[2], $m[1]) : null)
		);
	}

	/**
	 * Fecha de Excel (serial o texto) => ['date' => 'Y-m-d'|null, 'txt' => original|null].
	 * Acepta serial de Excel, Y-m-d, d/m/Y y d-m-Y. Lo que no se reconoce se regresa en txt.
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
			// 1990-01-01 .. 2100-12-31 en serial de Excel
			if ($num >= 32874 && $num <= 73415) {
				$dt = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($num);
				return ['date' => $dt->format('Y-m-d'), 'txt' => null];
			}
			return ['date' => null, 'txt' => mb_substr($this->valorComoTexto($raw), 0, 60)];
		}
		$s = trim((string) $raw);
		// Quita hora y basura al final ("02/05/2025  .", "1/7/2025 1", "2025-01-03 10:22")
		$s1 = preg_replace('/\s+\d{1,2}:\d{2}(:\d{2})?$/', '', $s);
		$s1 = trim(preg_replace('/^(\S+)\s+\S{1,2}$/u', '$1', $s1));
		if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $s1, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
			return ['date' => sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]), 'txt' => null];
		}
		if (preg_match('/^(\d{1,2})[\/\-.](\d{1,2})[\/\-.](\d{2}|\d{4})$/', $s1, $m)) {
			$a = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];
			if (checkdate((int) $m[2], (int) $m[1], $a)) {          // d/m/Y (formato MX)
				return ['date' => sprintf('%04d-%02d-%02d', $a, $m[2], $m[1]), 'txt' => null];
			}
			if (checkdate((int) $m[1], (int) $m[2], $a)) {          // m/d/Y (cuando el día > 12)
				return ['date' => sprintf('%04d-%02d-%02d', $a, $m[1], $m[2]), 'txt' => null];
			}
		}
		// "01 JULIO 25", "08 ago 25", "11 SEPT 2025", "5-ene-2026", "3 de marzo de 2026"
		$mesesTxt = ['ENE' => 1, 'FEB' => 2, 'MAR' => 3, 'ABR' => 4, 'MAY' => 5, 'JUN' => 6, 'JUL' => 7,
			'AGO' => 8, 'SEP' => 9, 'OCT' => 10, 'NOV' => 11, 'DIC' => 12];
		$up = strtoupper($this->quitarAcentos($s1));
		if (preg_match('/^(\d{1,2})[\s\-\/.]*(?:DE\s+)?([A-Z]{3,10})\.?[\s\-\/.]*(?:DEL?\s+)?(\d{2}|\d{4})$/', $up, $m)
			&& isset($mesesTxt[substr($m[2], 0, 3)])) {
			$a = strlen($m[3]) === 2 ? 2000 + (int) $m[3] : (int) $m[3];
			$mesN = $mesesTxt[substr($m[2], 0, 3)];
			if (checkdate($mesN, (int) $m[1], $a)) {
				return ['date' => sprintf('%04d-%02d-%02d', $a, $mesN, $m[1]), 'txt' => null];
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
