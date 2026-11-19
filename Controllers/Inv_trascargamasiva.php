<?php

/* =====================================================
   CARGA MASIVA DE TRASLADOS DE UNIDADES (Excel)
   Extensión de Inv_traslados (sin menú propio, usa su
   permiso). Cada renglón del archivo es un VIN; los
   renglones con el mismo ALMACÉN ORIGEN + ALMACÉN DESTINO
   + FECHA PROGRAMADA se registran en un solo traslado
   (un folio TRU-). Lo que no pasa validación se manda al
   log de omitidos.
===================================================== */

class Inv_trascargamasiva extends Controllers
{
	private const TIPOS_LLAVE = ['PRINCIPAL' => 'principal', 'DUPLICADO' => 'duplicado', 'JUEGO' => 'juego'];

	public function __construct()
	{
		parent::__construct();
		session_start();

		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}

		getPermisos(MITRASLADOS);
	}

	/* ===============================
	   VISTA PRINCIPAL
	=============================== */
	public function Inv_trascargamasiva()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			header("Location:" . base_url() . '/dashboard');
			die();
		}

		$data['page_tag'] = "Carga Masiva de Traslados";
		$data['page_title'] = "Carga Masiva de Traslados de Unidades";
		$data['page_name'] = "trascargamasiva";
		$data['page_functions_js'] = "functions_inv_trascargamasiva.js";
		$this->views->getView($this, "inv_trascargamasiva", $data);
	}

	/* ===============================
	   PLANTILLA DE LLENADO (XLSX)
	=============================== */
	public function descargarPlantilla()
	{
		if (empty($_SESSION['permisosMod']['r'])) {
			die();
		}

		$almacenesOrigen = $this->model->selectAlmacenes($this->getPlantaFiltro());
		$almacenesDestino = $this->model->selectAlmacenes();
		$unidades = $this->model->selectUnidadesDisponibles(array_column($almacenesOrigen, 'idalmacen'));

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$ss->getProperties()
			->setCreator('LDR Solutions · MRP')
			->setTitle('Plantilla Carga Masiva Traslados')
			->setDescription('Plantilla para registrar traslados de unidades por VIN');

		$gris = function ($hoja, string $rango) {
			$hoja->getStyle($rango)->getFont()->setBold(true);
			$hoja->getStyle($rango)->getFill()
				->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
				->getStartColor()->setARGB('FFEFEFEF');
		};

		/* ---------- HOJA 1: INSTRUCCIONES ---------- */
		$hoja1 = $ss->getActiveSheet();
		$hoja1->setTitle('Instrucciones');

		$hoja1->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA · TRASLADO DE UNIDADES');
		$hoja1->mergeCells('A1:C1');
		$hoja1->getStyle('A1')->getFont()->setBold(true)->setSize(14);

		$instrucciones = [
			'1. Llena la hoja "Traslados" sin modificar los encabezados de la fila 1. Cada fila es UNA unidad (un VIN).',
			'2. Borra la fila de ejemplo (fila 2) antes de subir el archivo.',
			'3. ALMACEN_ORIGEN y ALMACEN_DESTINO se eligen de la lista desplegable (hoja "Almacenes"). El origen solo puede ser un almacén de tu planta. No pueden ser el mismo almacén.',
			'4. El VIN debe existir, estar tatuado, estar en el ALMACEN_ORIGEN indicado y no estar en otro traslado activo. En la hoja "Unidades disponibles" están los VIN que hoy se pueden trasladar desde tus almacenes.',
			'5. Las filas con el mismo ALMACEN_ORIGEN, ALMACEN_DESTINO y FECHA_PROGRAMADA se registran en un mismo traslado (un solo folio TRU-). Las OBSERVACIONES del traslado se toman de la primera fila de cada grupo.',
			'6. Si un traslado no se puede registrar, ninguna de sus unidades se guarda y todas aparecen en el log con el motivo.',
			'7. Después de procesar el archivo puedes descargar un log en Excel con las filas que no se procesaron y el motivo.',
		];
		$f = 3;
		foreach ($instrucciones as $txt) {
			$hoja1->setCellValue('A' . $f, $txt);
			$hoja1->mergeCells('A' . $f . ':F' . $f);
			$f++;
		}

		$f++;
		$hoja1->fromArray(['CAMPO', 'OBLIGATORIO', 'DESCRIPCIÓN / VALORES VÁLIDOS'], null, 'A' . $f);
		$gris($hoja1, 'A' . $f . ':C' . $f);
		$f++;
		$inicioCampos = $f;

		$campos = [
			['ALMACEN_ORIGEN', 'Sí', 'Almacén donde está hoy la unidad (lista desplegable). Solo almacenes de tu planta.'],
			['ALMACEN_DESTINO', 'Sí', 'Almacén al que va la unidad (lista desplegable). Distinto al de origen.'],
			['VIN', 'Sí', 'VIN de la unidad, tal cual está registrado.'],
			['ENTREGA_LLAVE', 'No', 'SI o NO. Si se deja vacío se toma como NO.'],
			['TIPO_LLAVE', 'Si ENTREGA_LLAVE = SI', 'PRINCIPAL, DUPLICADO o JUEGO. La llave no debe estar entregada en otro movimiento.'],
			['KIT', 'No', 'SI o NO. Si se deja vacío se toma como NO.'],
			['FECHA_PROGRAMADA', 'Sí', 'Fecha del traslado (formato AAAA-MM-DD o DD/MM/AAAA).'],
			['OBSERVACIONES', 'No', 'Texto libre del traslado.'],
		];
		foreach ($campos as $c) {
			$hoja1->fromArray($c, null, 'A' . $f);
			$hoja1->mergeCells('C' . $f . ':F' . $f);
			$f++;
		}
		$hoja1->getColumnDimension('A')->setAutoSize(true);
		$hoja1->getColumnDimension('B')->setAutoSize(true);
		$hoja1->getColumnDimension('C')->setWidth(70);
		$hoja1->getStyle('C' . $inicioCampos . ':C' . ($f - 1))->getAlignment()->setWrapText(true);

		/* ---------- HOJA 2: ALMACENES (fuente de las listas desplegables) ---------- */
		$hojaAlm = $ss->createSheet();
		$hojaAlm->setTitle('Almacenes');
		$hojaAlm->fromArray(['ALMACEN_ORIGEN (tu planta)', '', 'ALMACEN_DESTINO'], null, 'A1');
		$gris($hojaAlm, 'A1');
		$gris($hojaAlm, 'C1');

		$i = 2;
		foreach ($almacenesOrigen as $a) {
			$hojaAlm->setCellValue('A' . $i++, $a['descripcion']);
		}
		$i = 2;
		foreach ($almacenesDestino as $a) {
			$hojaAlm->setCellValue('C' . $i++, $a['descripcion']);
		}
		if (empty($almacenesOrigen)) {
			$hojaAlm->setCellValue('A2', '(No tienes almacenes de origen asignados a tu planta)');
		}
		$hojaAlm->getColumnDimension('A')->setWidth(45);
		$hojaAlm->getColumnDimension('B')->setWidth(3);
		$hojaAlm->getColumnDimension('C')->setWidth(45);

		/* ---------- HOJA 3: UNIDADES DISPONIBLES (referencia) ---------- */
		$hojaUni = $ss->createSheet();
		$hojaUni->setTitle('Unidades disponibles');
		$hojaUni->fromArray(['VIN', 'UNIDAD', 'ALMACEN'], null, 'A1');
		$gris($hojaUni, 'A1:C1');
		$hojaUni->freezePane('A2');
		$i = 2;
		foreach ($unidades as $u) {
			$hojaUni->setCellValueExplicit('A' . $i, (string) $u['vin'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$hojaUni->setCellValue('B' . $i, $u['unidad']);
			$hojaUni->setCellValue('C' . $i, $u['almacen']);
			$i++;
		}
		if (empty($unidades)) {
			$hojaUni->setCellValue('A2', '(No hay unidades disponibles para trasladar en tus almacenes)');
		} else {
			$hojaUni->setAutoFilter('A1:C' . ($i - 1));
		}
		$hojaUni->getColumnDimension('A')->setWidth(24);
		$hojaUni->getColumnDimension('B')->setWidth(45);
		$hojaUni->getColumnDimension('C')->setWidth(40);

		/* ---------- HOJA 4: TRASLADOS (captura) ---------- */
		$hojaTra = $ss->createSheet();
		$hojaTra->setTitle('Traslados');

		$encabezados = ['ALMACEN_ORIGEN', 'ALMACEN_DESTINO', 'VIN', 'ENTREGA_LLAVE', 'TIPO_LLAVE', 'KIT', 'FECHA_PROGRAMADA', 'OBSERVACIONES'];
		$hojaTra->fromArray($encabezados, null, 'A1');
		$hojaTra->getStyle('A1:H1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
		$hojaTra->getStyle('A1:H1')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FF2E7D32');
		$hojaTra->freezePane('A2');
		$hojaTra->setAutoFilter('A1:H1');

		// VIN y FECHA como texto: el VIN no debe convertirse a número y la
		// fecha se acepta escrita (AAAA-MM-DD o DD/MM/AAAA).
		$hojaTra->getStyle('C2:C1000')->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
		$hojaTra->getStyle('G2:G1000')->getNumberFormat()
			->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

		$origenEjemplo = $almacenesOrigen[0]['descripcion'] ?? '';
		$destinoEjemplo = '';
		foreach ($almacenesDestino as $a) {
			if ($a['descripcion'] !== $origenEjemplo) {
				$destinoEjemplo = $a['descripcion'];
				break;
			}
		}

		$ejemplo = [
			$origenEjemplo,
			$destinoEjemplo,
			$unidades[0]['vin'] ?? 'VIN DE EJEMPLO',
			'SI',
			'PRINCIPAL',
			'NO',
			date('Y-m-d', strtotime('+1 day')),
			'Ejemplo de observaciones',
		];
		foreach ($ejemplo as $idx => $valor) {
			$col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
			$hojaTra->setCellValueExplicit($col . '2', (string) $valor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
		}
		$hojaTra->getStyle('A2:H2')->getFont()->setItalic(true)->getColor()->setARGB('FF999999');

		if (!empty($almacenesOrigen)) {
			$this->agregarValidacionLista($hojaTra, 'A', 2, 1000, "'Almacenes'!\$A\$2:\$A\$" . (1 + count($almacenesOrigen)));
		}
		if (!empty($almacenesDestino)) {
			$this->agregarValidacionLista($hojaTra, 'B', 2, 1000, "'Almacenes'!\$C\$2:\$C\$" . (1 + count($almacenesDestino)));
		}
		$this->agregarValidacionLista($hojaTra, 'D', 2, 1000, '"SI,NO"');
		$this->agregarValidacionLista($hojaTra, 'E', 2, 1000, '"PRINCIPAL,DUPLICADO,JUEGO"');
		$this->agregarValidacionLista($hojaTra, 'F', 2, 1000, '"SI,NO"');

		$anchos = ['A' => 38, 'B' => 38, 'C' => 24, 'D' => 16, 'E' => 16, 'F' => 10, 'G' => 20, 'H' => 40];
		foreach ($anchos as $col => $ancho) {
			$hojaTra->getColumnDimension($col)->setWidth($ancho);
		}

		$ss->setActiveSheetIndex(3);

		$this->enviarExcel($ss, 'Plantilla_CargaMasiva_Traslados.xlsx');
	}

	private function agregarValidacionLista($hoja, string $columna, int $desde, int $hasta, string $formula): void
	{
		// Una sola validación para todo el rango (más ligera que celda por
		// celda); PhpSpreadsheet 1.29 escribe la llave del rango como sqref.
		$dv = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
		$dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
		$dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
		$dv->setAllowBlank(true);
		$dv->setShowInputMessage(true);
		$dv->setShowErrorMessage(true);
		// OJO: en PhpSpreadsheet, showDropDown=true SÍ muestra la flecha del
		// desplegable en Excel (el atributo interno del XML está invertido).
		$dv->setShowDropDown(true);
		$dv->setFormula1($formula);
		$hoja->setDataValidation($columna . $desde . ':' . $columna . $hasta, $dv);
	}

	/* ===============================
	   PROCESAR CARGA
	   (agrupa por ORIGEN + DESTINO + FECHA PROGRAMADA)
	=============================== */
	public function procesarCarga()
	{
		header('Content-Type: application/json; charset=utf-8');

		if (empty($_SESSION['permisosMod']['w'])) {
			echo json_encode(['status' => false, 'msg' => 'No tienes permisos para registrar traslados.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$rows = $this->obtenerFilasArchivo();
		if ($rows === null) {
			die();
		}

		if (empty($rows)) {
			echo json_encode(['status' => false, 'msg' => 'El archivo no contiene unidades para procesar.'], JSON_UNESCAPED_UNICODE);
			die();
		}

		$mapOrigen = $this->mapaPorNombre($this->model->selectAlmacenes($this->getPlantaFiltro()));
		$mapTodos = $this->mapaPorNombre($this->model->selectAlmacenes());

		$vinesMap = $this->model->mapVines(array_column($rows, 'vin'));
		$llavesOcupadas = $this->model->mapLlavesNoDisponibles(array_column($vinesMap, 'id_numeros_serie'));

		$log = [];
		$grupos = [];
		$vinesVistos = [];

		foreach ($rows as $r) {
			$fila = $r['_fila'];
			$origenTexto = trim((string) ($r['almacen_origen'] ?? ''));
			$destinoTexto = trim((string) ($r['almacen_destino'] ?? ''));
			$vinTexto = strtoupper(trim((string) ($r['vin'] ?? '')));
			$ruta = "{$origenTexto} -> {$destinoTexto}";

			$omitir = function (string $motivo) use (&$log, $fila, $vinTexto, $ruta) {
				$log[] = ['fila' => $fila, 'vin' => $vinTexto, 'almacenes' => $ruta, 'motivo' => $motivo];
			};

			if ($vinTexto === '') {
				$omitir('Fila sin VIN');
				continue;
			}

			// --- Almacenes ---
			if ($origenTexto === '') {
				$omitir('Falta el almacén origen (columna ALMACEN_ORIGEN)');
				continue;
			}
			$claveOrigen = $this->normalizarTexto($origenTexto);
			if (!isset($mapTodos[$claveOrigen])) {
				$omitir("El almacén origen \"{$origenTexto}\" no existe en el catálogo (hoja Almacenes)");
				continue;
			}
			if (!isset($mapOrigen[$claveOrigen])) {
				$omitir("El almacén origen \"{$origenTexto}\" no pertenece a tu planta");
				continue;
			}
			$origenid = $mapOrigen[$claveOrigen];

			if ($destinoTexto === '') {
				$omitir('Falta el almacén destino (columna ALMACEN_DESTINO)');
				continue;
			}
			$claveDestino = $this->normalizarTexto($destinoTexto);
			if (!isset($mapTodos[$claveDestino])) {
				$omitir("El almacén destino \"{$destinoTexto}\" no existe en el catálogo (hoja Almacenes)");
				continue;
			}
			$destinoid = $mapTodos[$claveDestino];

			if ($origenid === $destinoid) {
				$omitir('El almacén origen y destino no pueden ser el mismo');
				continue;
			}

			// --- Fecha programada ---
			$fecha = $this->parsearFecha((string) ($r['fecha_programada'] ?? ''));
			if ($fecha === null) {
				$omitir(trim((string) ($r['fecha_programada'] ?? '')) === ''
					? 'Falta la fecha programada (columna FECHA_PROGRAMADA)'
					: 'FECHA_PROGRAMADA inválida (usa AAAA-MM-DD o DD/MM/AAAA)');
				continue;
			}

			// --- VIN ---
			if (!isset($vinesMap[$vinTexto])) {
				$omitir('El VIN no existe');
				continue;
			}
			$vin = $vinesMap[$vinTexto];

			if (($vin['tipo_elemento'] ?? '') !== 'P') {
				$omitir('El VIN no corresponde a una unidad (producto tipo P)');
				continue;
			}
			if ((int) $vin['almacenid'] !== $origenid) {
				$omitir('El VIN no está en el almacén origen; hoy está en "' . ($vin['almacen'] ?: 'sin almacén') . '"');
				continue;
			}
			if ((int) $vin['asignaciones_activas'] === 0 || (string) $vin['estado'] !== '2') {
				$omitir('El VIN no está asignado/tatuado a ninguna unidad');
				continue;
			}
			if (!empty($vin['folio_traslado_activo'])) {
				$omitir('La unidad ya tiene un traslado activo (folio ' . $vin['folio_traslado_activo'] . ')');
				continue;
			}
			if (isset($vinesVistos[$vinTexto])) {
				$omitir('El VIN está repetido en el archivo (ya viene en la fila ' . $vinesVistos[$vinTexto] . ')');
				continue;
			}

			// --- Llave y kit ---
			$entrega = $this->parsearSiNo((string) ($r['entrega_llave'] ?? ''));
			if ($entrega === null) {
				$omitir('ENTREGA_LLAVE inválido (usa SI o NO)');
				continue;
			}

			$tipoLlave = '';
			if ($entrega === 1) {
				$tipoTexto = $this->normalizarTexto((string) ($r['tipo_llave'] ?? ''));
				if (!isset(self::TIPOS_LLAVE[$tipoTexto])) {
					$omitir($tipoTexto === ''
						? 'Entrega llave = SI pero falta TIPO_LLAVE (PRINCIPAL, DUPLICADO o JUEGO)'
						: 'TIPO_LLAVE inválido (usa PRINCIPAL, DUPLICADO o JUEGO)');
					continue;
				}
				$tipoLlave = self::TIPOS_LLAVE[$tipoTexto];

				if (isset($llavesOcupadas[(int) $vin['id_numeros_serie'] . '|' . $tipoLlave])) {
					$omitir("La llave ({$tipoTexto}) de esta unidad ya está entregada/en tránsito en otro movimiento y no ha sido devuelta");
					continue;
				}
			}

			$kit = $this->parsearSiNo((string) ($r['kit'] ?? ''));
			if ($kit === null) {
				$omitir('KIT inválido (usa SI o NO)');
				continue;
			}

			$vinesVistos[$vinTexto] = $fila;

			$claveGrupo = $origenid . '|' . $destinoid . '|' . $fecha;
			if (!isset($grupos[$claveGrupo])) {
				$grupos[$claveGrupo] = [
					'origenid' => $origenid,
					'destinoid' => $destinoid,
					'fecha' => $fecha,
					'ruta' => $ruta,
					// Las observaciones son del traslado: se toman de la
					// primera fila del grupo.
					'observaciones' => strClean(trim((string) ($r['observaciones'] ?? ''))),
					'unidades' => [],
					'filas' => [],
				];
			}

			$grupos[$claveGrupo]['unidades'][] = [
				'vinid' => (int) $vin['id_numeros_serie'],
				'inventarioid' => (int) $vin['inventarioid'],
				'vin' => $vin['numero_serie'],
				'almacenid' => $origenid,
				'entrega_llave' => $entrega,
				'tipo_llave' => $tipoLlave,
				'kit' => $kit,
			];
			$grupos[$claveGrupo]['filas'][] = ['fila' => $fila, 'vin' => $vinTexto];
		}

		require_once 'Models/Inv_trasladosModel.php';
		$trasladosModel = new Inv_trasladosModel();

		$unidadesRegistradas = 0;
		$trasladosCreados = 0;

		foreach ($grupos as $g) {
			try {
				// Tipo de traslado, proveedor y trasladista ya no se capturan
				// (ocultos en Inv_traslados desde 19-nov-2026).
				$resultado = $trasladosModel->insertTraslado(
					$g['origenid'],
					$g['destinoid'],
					null,
					null,
					$g['fecha'],
					$g['observaciones'],
					$_SESSION['idUser'],
					$g['unidades'],
					'',
					'',
					'',
					null,
					''
				);
			} catch (\Throwable $e) {
				error_log('Inv_trascargamasiva::procesarCarga insertTraslado: ' . $e->getMessage());
				$resultado = ['error' => 'Error al guardar el traslado (revisa el formato de los datos)'];
			}

			if ($resultado === true) {
				$unidadesRegistradas += count($g['unidades']);
				$trasladosCreados++;
			} else {
				$motivo = (is_array($resultado) && isset($resultado['error']))
					? 'No se registró el traslado: ' . $resultado['error']
					: 'No fue posible registrar el traslado';
				foreach ($g['filas'] as $f) {
					$log[] = ['fila' => $f['fila'], 'vin' => $f['vin'], 'almacenes' => $g['ruta'], 'motivo' => $motivo];
				}
			}
		}

		usort($log, function ($a, $b) {
			return ($a['fila'] ?? 0) <=> ($b['fila'] ?? 0);
		});

		$_SESSION['cargaMasivaTrasLog'] = $log;

		echo json_encode([
			'status' => true,
			'insertados' => $unidadesRegistradas,
			'traslados' => $trasladosCreados,
			'omitidos' => count($log),
			'totalFilas' => count($rows),
			'msg' => "Proceso finalizado: {$unidadesRegistradas} unidad(es) registrada(s) en {$trasladosCreados} traslado(s), "
				. count($log) . " omitida(s) de " . count($rows) . " fila(s).",
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

		$log = $_SESSION['cargaMasivaTrasLog'] ?? [];

		$ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
		$sheet = $ss->getActiveSheet();
		$sheet->setTitle('Log');

		$sheet->setCellValue('A1', 'Log de unidades NO registradas (Carga masiva de traslados)');
		$sheet->mergeCells('A1:D1');
		$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
		$sheet->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
		$sheet->mergeCells('A2:D2');

		$sheet->fromArray(['FILA_ARCHIVO', 'VIN', 'ORIGEN -> DESTINO', 'MOTIVO'], null, 'A4');
		$sheet->getStyle('A4:D4')->getFont()->setBold(true);
		$sheet->getStyle('A4:D4')->getFill()
			->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
			->getStartColor()->setARGB('FFEFEFEF');
		$sheet->freezePane('A5');

		$r = 5;
		foreach ($log as $item) {
			$sheet->setCellValue('A' . $r, $item['fila'] ?? '');
			$sheet->setCellValueExplicit('B' . $r, (string) ($item['vin'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('C' . $r, (string) ($item['almacenes'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$sheet->setCellValueExplicit('D' . $r, (string) ($item['motivo'] ?? ''), \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
			$r++;
		}

		$sheet->getColumnDimension('A')->setAutoSize(true);
		$sheet->getColumnDimension('B')->setWidth(24);
		$sheet->getColumnDimension('C')->setWidth(60);
		$sheet->getColumnDimension('D')->setWidth(80);

		$this->enviarExcel($ss, 'Log_CargaMasiva_Traslados_' . date('Ymd_His') . '.xlsx');
	}

	/* ===============================
	   HELPERS PRIVADOS
	=============================== */

	/** Mismo criterio que Inv_traslados::getPlantaFiltro(). */
	private function getPlantaFiltro()
	{
		$idrol = $_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0);

		if ((int) $idrol === RADMINISTRADOR) {
			return null;
		}

		$plantaid = $_SESSION['userData']['plantaid'] ?? ($_SESSION['plantaid'] ?? null);

		return $plantaid !== null ? (int) $plantaid : 0;
	}

	private function enviarExcel($ss, string $filename): void
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
			error_log('Inv_trascargamasiva::leerArchivo: ' . $e->getMessage());
			echo json_encode(['status' => false, 'msg' => 'No fue posible leer el archivo. Verifica que uses la plantilla proporcionada.'], JSON_UNESCAPED_UNICODE);
			return null;
		}
	}

	private function leerArchivo(string $tmpName): array
	{
		$spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);

		$sheet = $spreadsheet->sheetNameExists('Traslados')
			? $spreadsheet->getSheetByName('Traslados')
			: $spreadsheet->getSheet(0);

		$highestRow = $sheet->getHighestDataRow();
		$highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

		$mapaEncabezados = [
			'ALMACEN_ORIGEN' => 'almacen_origen',
			'ALMACEN_DESTINO' => 'almacen_destino',
			'VIN' => 'vin',
			'ENTREGA_LLAVE' => 'entrega_llave',
			'TIPO_LLAVE' => 'tipo_llave',
			'KIT' => 'kit',
			'FECHA_PROGRAMADA' => 'fecha_programada',
			'OBSERVACIONES' => 'observaciones',
		];

		$columnas = [];
		for ($col = 1; $col <= $highestColIndex; $col++) {
			$texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
			$clave = preg_replace('/\s+/', '_', strtoupper($this->quitarAcentos($texto)));
			$clave = str_replace(['¿', '?'], '', $clave);
			if (isset($mapaEncabezados[$clave])) {
				$columnas[$col] = $mapaEncabezados[$clave];
			}
		}

		if (!in_array('vin', $columnas, true)) {
			throw new \RuntimeException('No se encontraron los encabezados esperados. Usa la plantilla proporcionada.');
		}

		$rows = [];
		for ($fila = 2; $fila <= $highestRow; $fila++) {
			$registro = [];
			$vacio = true;

			foreach ($columnas as $col => $campo) {
				$registro[$campo] = $this->valorComoTexto($sheet->getCellByColumnAndRow($col, $fila)->getValue());
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

	/** Mapa [nombre normalizado => idalmacen]. */
	private function mapaPorNombre(array $almacenes): array
	{
		$map = [];
		foreach ($almacenes as $a) {
			$map[$this->normalizarTexto((string) $a['descripcion'])] = (int) $a['idalmacen'];
		}
		return $map;
	}

	/** SI / SÍ / 1 => 1, NO / 0 / vacío => 0, cualquier otra cosa => null. */
	private function parsearSiNo(string $valor): ?int
	{
		$v = $this->normalizarTexto($valor);
		if ($v === '' || $v === 'NO' || $v === '0') {
			return 0;
		}
		if ($v === 'SI' || $v === '1') {
			return 1;
		}
		return null;
	}

	/**
	 * Acepta AAAA-MM-DD, DD/MM/AAAA, DD-MM-AAAA o el número de serie de
	 * fecha de Excel (si la celda quedó con formato de fecha). Regresa
	 * AAAA-MM-DD o null si no es una fecha válida.
	 */
	private function parsearFecha(string $valor): ?string
	{
		$valor = trim($valor);
		if ($valor === '') {
			return null;
		}

		if (is_numeric($valor) && (float) $valor > 20000 && (float) $valor < 80000) {
			try {
				return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $valor)->format('Y-m-d');
			} catch (\Throwable $e) {
				return null;
			}
		}

		// Si trae hora (p. ej. "2026-11-25 00:00:00") se toma solo la fecha.
		$valor = preg_split('/\s+/', $valor)[0];

		foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'] as $formato) {
			$dt = \DateTime::createFromFormat('!' . $formato, $valor);
			if ($dt && $dt->format($formato) === $valor) {
				return $dt->format('Y-m-d');
			}
		}

		return null;
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

	/** Valor crudo de celda a texto, sin notación científica. */
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
