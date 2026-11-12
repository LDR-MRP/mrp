<?php
class Inv_unidades extends Controllers
{
    /** Orden en que se presentan las sublineas (las demas van al final, alfabetico). */
    private const ORDEN_SUBLINEAS = ['PICKUP', 'VAN', 'MT', 'LDT', 'MDT', 'HDT', 'EV', 'CHC', 'AUV'];

    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIUNIDADES);
    }

    public function Inv_unidades()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
            die();
        }
        $data['page_tag'] = "Unidades";
        $data['page_title'] = "Inventario de unidades";
        $data['page_name'] = "unidades";
        $data['page_functions_js'] = "functions_inv_unidades.js";
        $this->views->getView($this, "inv_unidades", $data);
    }

    public function getDatos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        // Unidades por llegar (Order delivery plan): sin entrada al inventario.
        // Si el módulo aún no está instalado completo, la pantalla sigue funcionando sin ellas.
        try {
            $transito = $this->model->selectTransitoODP();
            $contratos = $this->model->selectTransitoContratos();
        } catch (\Throwable $e) {
            error_log('Inv_unidades::getDatos (ODP): ' . $e->getMessage());
            $transito = $contratos = [];
        }
        echo json_encode([
            'generado'        => date('Y-m-d H:i'),
            'orden_sublineas' => self::ORDEN_SUBLINEAS,
            'unidades'        => $this->model->selectUnidades(),
            'transito'        => $transito,
            'transito_contratos' => $contratos,
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    private static function ordenSublinea(string $s): int
    {
        $i = array_search($s, self::ORDEN_SUBLINEAS, true);
        return $i === false ? 99 : $i;
    }

    /**
     * PDF de la matriz (sublinea > modelo > anio [> color] x almacen) con los
     * mismos filtros de la pantalla: sub, anio (listas separadas por coma),
     * color, planta, almacen, q y porColor (1 = un renglon por color).
     */
    public function reportePDF()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $lista   = fn($k) => array_values(array_filter(array_map('trim', explode(',', (string)($_GET[$k] ?? ''))), 'strlen'));
        $subs    = $lista('sub');
        $anios   = $lista('anio');
        $color   = trim((string)($_GET['color'] ?? ''));
        $planta  = strtoupper(trim((string)($_GET['planta'] ?? '')));
        $almacen = (int)($_GET['almacen'] ?? 0);
        $q       = mb_strtolower(trim((string)($_GET['q'] ?? '')));
        $porColor = !empty($_GET['porColor']) && $_GET['porColor'] !== '0';

        $u = array_values(array_filter($this->model->selectUnidades(), function ($r) use ($subs, $anios, $color, $planta, $almacen, $q) {
            if ($subs && !in_array($r['sublinea'], $subs, true)) return false;
            if ($anios && !in_array((string)($r['anio'] ?? 'S/A'), $anios, true)) return false;
            if ($color !== '' && ($r['color'] ?? 'Sin color') !== $color) return false;
            if ($planta !== '' && $r['planta'] !== $planta) return false;
            if ($almacen > 0 && (int)$r['almacenid'] !== $almacen) return false;
            if ($q !== '' && mb_strpos(mb_strtolower($r['cve_articulo'] . ' ' . $r['descripcion'] . ' ' . $r['numero_serie']), $q) === false) return false;
            return true;
        }));

        // Columnas: almacenes con unidades, agrupados por planta
        $alms = [];
        $nombresPlanta = [];
        foreach ($u as $r) {
            $alms[$r['almacenid']] = ['cve' => $r['cve_almacen'], 'planta' => $r['planta']];
            $nombresPlanta[$r['planta']] = $r['planta_nombre'] ?? $r['planta'];
        }
        // Plantas por clave (PL01, PL02...) y "Sin planta asignada" al final
        // Orden: plantas por clave, luego "En tránsito", luego "Sin planta asignada"
        $sinPlanta = fn($p) => $p === 'TRANSITO' ? 1 : (in_array($p, ['SIN', 'OTROS'], true) ? 2 : 0);
        uasort($alms, fn($a, $b) => [$sinPlanta($a['planta']), $a['planta'], $a['cve']] <=> [$sinPlanta($b['planta']), $b['planta'], $b['cve']]);

        // Renglones
        $rows = [];
        foreach ($u as $r) {
            $anio = (string)($r['anio'] ?? 'S/A');
            $col  = $r['color'] ?? 'Sin color';
            $k = $r['sublinea'] . '|' . $r['inventarioid'] . '|' . $anio . ($porColor ? '|' . $col : '');
            if (!isset($rows[$k])) {
                $rows[$k] = ['sub' => $r['sublinea'], 'sub_desc' => $r['sublinea_desc'], 'cve' => $r['cve_articulo'],
                             'modelo' => $r['descripcion'], 'anio' => $anio, 'color' => $col, 'alm' => [], 'total' => 0];
            }
            $rows[$k]['alm'][$r['almacenid']] = ($rows[$k]['alm'][$r['almacenid']] ?? 0) + 1;
            $rows[$k]['total']++;
        }
        uasort($rows, fn($a, $b) => [self::ordenSublinea($a['sub']), $a['sub'], $a['modelo'], $a['anio'], $a['color']]
                                <=> [self::ordenSublinea($b['sub']), $b['sub'], $b['modelo'], $b['anio'], $b['color']]);

        $h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        $corto = fn($cve) => preg_replace('/^PL\d{2}-ALM-/', '', $cve);
        // TCPDF necesita ancho explicito en cada celda (sin rowspan) para alinear columnas
        $nAlm   = count($alms);
        $wAnio  = 6;
        $wColor = $porColor ? 9 : 0;
        $wTotal = 7;
        $wAlm   = $nAlm ? min(10, floor((100 - 28 - $wAnio - $wColor - $wTotal) / $nAlm * 10) / 10) : 0;
        $wMod   = 100 - $wAnio - $wColor - $wTotal - $wAlm * $nAlm;
        $td = fn($w, $txt, $extra = '') => '<td width="' . $w . '%"' . $extra . '>' . $txt . '</td>';
        $fijas = function ($a, $b, $c, $extra = '') use ($td, $wMod, $wAnio, $wColor, $porColor) {
            return $td($wMod, $a, $extra) . $td($wAnio, $b, ' align="center"' . $extra) . ($porColor ? $td($wColor, $c, $extra) : '');
        };

        // Encabezado de 2 niveles: planta / almacen
        $porPlanta = [];
        foreach ($alms as $a) $porPlanta[$a['planta']] = ($porPlanta[$a['planta']] ?? 0) + 1;
        $azul = ' style="background-color:#405189;color:#ffffff;font-weight:bold;"';
        $th1 = '<tr' . $azul . '>' . $fijas('', '', '');
        foreach ($porPlanta as $p => $n) {
            $th1 .= '<td width="' . ($wAlm * $n) . '%" colspan="' . $n . '" align="center">' . $h($nombresPlanta[$p] ?? $p) . '</td>';
        }
        $th1 .= $td($wTotal, '') . '</tr>';
        $th2 = '<tr' . $azul . '>' . $fijas('Modelo', 'Año', 'Color');
        foreach ($alms as $a) $th2 .= $td($wAlm, $h($corto($a['cve'])), ' align="right"');
        $th2 .= $td($wTotal, 'Total', ' align="right"') . '</tr>';

        $body = '';
        $totCol = array_fill_keys(array_keys($alms), 0);
        $grand = 0;
        $subActual = null;
        $subTot = [];
        $subN = 0;
        $gris = ' style="background-color:#eef1f8;font-weight:bold;"';
        $cierraSub = function () use (&$body, &$subTot, &$subN, &$subActual, $alms, $fijas, $td, $wAlm, $wTotal, $gris, $h) {
            if ($subActual === null) return;
            $body .= '<tr' . $gris . '>' . $fijas('Total ' . $h($subActual), '', '');
            foreach ($alms as $id => $a) $body .= $td($wAlm, ($subTot[$id] ?? 0) ?: '', ' align="right"');
            $body .= $td($wTotal, $subN, ' align="right"') . '</tr>';
        };
        foreach ($rows as $r) {
            if ($r['sub'] !== $subActual) {
                $cierraSub();
                $subActual = $r['sub'];
                $subTot = [];
                $subN = 0;
                $body .= '<tr style="background-color:#dfe4f2;font-weight:bold;"><td width="100%" colspan="' . ($nAlm + ($porColor ? 4 : 3)) . '">'
                    . $h($r['sub']) . ' · ' . $h($r['sub_desc']) . '</td></tr>';
            }
            $body .= '<tr>' . $fijas($h($r['modelo']) . ' <font color="#888888" size="6">' . $h($r['cve']) . '</font>', $h($r['anio']), $h($r['color']));
            foreach ($alms as $id => $a) {
                $v = $r['alm'][$id] ?? 0;
                $body .= $td($wAlm, $v ?: '', ' align="right"');
                $totCol[$id] += $v;
                $subTot[$id] = ($subTot[$id] ?? 0) + $v;
            }
            $body .= $td($wTotal, '<b>' . $r['total'] . '</b>', ' align="right"') . '</tr>';
            $grand += $r['total'];
            $subN += $r['total'];
        }
        $cierraSub();
        $foot = '<tr' . $azul . '>' . $fijas('TOTAL', '', '');
        foreach ($alms as $id => $a) $foot .= $td($wAlm, $totCol[$id], ' align="right"');
        $foot .= $td($wTotal, $grand, ' align="right"') . '</tr>';

        $filtros = [];
        if ($subs)          $filtros[] = 'Sublínea: ' . implode(', ', $subs);
        if ($anios)         $filtros[] = 'Año: ' . implode(', ', $anios);
        if ($color !== '')  $filtros[] = 'Color: ' . $color;
        if ($planta !== '') $filtros[] = 'Planta: ' . ($nombresPlanta[$planta] ?? $planta);
        if ($almacen > 0 && $alms) $filtros[] = 'Almacén: ' . reset($alms)['cve'];
        if ($q !== '')      $filtros[] = 'Búsqueda: "' . $q . '"';

        $leyenda = '';
        foreach ($alms as $a) $leyenda .= $h($corto($a['cve'])) . ' = ' . $h($a['cve']) . ' · ';

        $html = '<table cellpadding="0"><tr><td width="70%"><h2 style="color:#405189;">Inventario de unidades</h2></td>'
            . '<td width="30%" align="right" style="font-size:9pt;"><b>' . number_format($grand) . '</b> unidades<br>'
            . '<font color="#666666" size="7">Al ' . date('d/m/Y H:i') . '</font></td></tr></table>'
            . '<p style="font-size:7pt;color:#666;">' . ($filtros ? $h(implode(' · ', $filtros)) . '<br>' : '')
            . 'Existencia = unidades físicas (VIN) de productos activos; año modelo = posición 10 del VIN. '
            . rtrim($leyenda, ' ·') . '</p>'
            . '<table border="0.3" cellpadding="2.5" style="font-size:7.5pt;">' . $th1 . $th2 . $body . $foot . '</table>';
        if (!$rows) {
            $html .= '<p>No hay unidades con esos filtros.</p>';
        }

        if (ob_get_length()) {
            ob_end_clean();
        }
        require_once(__DIR__ . '/../Libraries/tcpdf/tcpdf.php');
        $pdf = new TCPDF('L', 'mm', 'LETTER', true, 'UTF-8', false);
        $pdf->SetCreator('LDR MRP');
        $pdf->SetTitle('Inventario de unidades');
        $pdf->setPrintHeader(false);
        $pdf->setFooterData([0, 0, 0], [255, 255, 255]);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('inventario_unidades_' . date('Ymd_His') . '.pdf', 'I');
        exit;
    }

    /* =====================================================================
     * REPORTE EJECUTIVO (Dirección): inventario real vs. unidades en tránsito
     * ---------------------------------------------------------------------
     * Izquierda: unidades físicas (VIN) por almacén, agrupadas por planta
     *            (misma base que la pantalla: selectUnidades).
     * Derecha:   unidades en tránsito del Order delivery plan (wms_odp) por mes
     *            de carga; no tienen entrada al inventario. Todas cuentan como
     *            color Blanco.
     * Renglones: Segmento (sublínea) > Modelo (nombre comercial) > Color.
     * Filtros que respeta (de la pantalla): sub (lista por comas) y q.
     * ?formato=pdf (default) | xlsx
     * ===================================================================== */
    private const COLOR_TRANSITO = 'Blanco';
    private const MESES_CORTOS = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    /** Paleta del reporte (marca LDR: naranja). */
    private const C_INV = '2F4A7A';      // inventario real
    private const C_INV_SUAVE = 'E8EDF6';
    private const C_TRA = 'D4622A';      // en tránsito (naranja LDR)
    private const C_TRA_SUAVE = 'FBEDE5';
    private const C_OSCURO = '1F2430';
    private const C_SEG = 'EEF0F4';
    private const C_LINEA = 'D5D9E0';
    /** Almacenes que en el reporte ejecutivo van en su propia columna (clave => encabezado); el resto suma en "Planta". */
    private const ALMACENES_APARTE = ['ALM-XIAN' => 'XIAN', 'ALM-VELCEN' => 'VELCEN'];

    public function reporteEjecutivo()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $subs = array_values(array_filter(array_map('trim', explode(',', (string) ($_GET['sub'] ?? ''))), 'strlen'));
        $q = mb_strtolower(trim((string) ($_GET['q'] ?? '')));
        $d = $this->datosEjecutivo($subs, $q);
        $formato = (string) ($_GET['formato'] ?? 'pdf');
        if ($formato === 'xlsx') {
            $this->ejecutivoXLSX($d);
        }
        $this->ejecutivoPDF($d);
    }

    private function mesCorto(string $ym): string
    {
        return self::MESES_CORTOS[(int) substr($ym, 5, 2) - 1] . ' ' . substr($ym, 0, 4);
    }

    private function datosEjecutivo(array $subs, string $q): array
    {
        $nombres = $this->model->selectNombresComerciales();
        $pasa = function ($sub, $texto) use ($subs, $q) {
            return (!$subs || in_array($sub, $subs, true)) && ($q === '' || mb_strpos(mb_strtolower($texto), $q) !== false);
        };
        $rows = [];
        $fila = function ($sub, $subDesc, $invId, $cve, $desc, $color) use (&$rows, $nombres) {
            $k = $sub . '|' . $invId . '|' . mb_strtoupper($color);
            if (!isset($rows[$k])) {
                $rows[$k] = ['sub' => $sub, 'sub_desc' => $subDesc, 'cve' => $cve,
                             'modelo' => $nombres[(int) $invId] ?? $desc, 'color' => $color,
                             'alm' => [], 'inv' => 0, 'tra' => [], 'trans' => 0];
            }
            return $k;
        };

        $alms = [];
        foreach ($this->model->selectUnidades() as $r) {
            if (!$pasa($r['sublinea'], $r['cve_articulo'] . ' ' . $r['descripcion'] . ' ' . ($nombres[(int) $r['inventarioid']] ?? ''))) {
                continue;
            }
            $color = trim((string) ($r['color'] ?? '')) !== '' ? mb_convert_case(trim($r['color']), MB_CASE_TITLE, 'UTF-8') : self::COLOR_TRANSITO;   // sin color = Blanco
            $k = $fila($r['sublinea'], $r['sublinea_desc'], $r['inventarioid'], $r['cve_articulo'], $r['descripcion'], $color);
            // Solo existencias: todos los almacenes se agrupan en "Planta", excepto los
            // que se muestran por separado (ALMACENES_APARTE: XIAN y VELCEN).
            $cveAlm = strtoupper(trim((string) $r['cve_almacen']));
            if (isset(self::ALMACENES_APARTE[$cveAlm])) {
                $aid = 'ALM:' . $cveAlm;
                $alms[$aid] = ['cve' => self::ALMACENES_APARTE[$cveAlm], 'orden' => 1];
            } else {
                $aid = 'PLANTA';
                $alms[$aid] = ['cve' => 'Planta/Montenegro', 'orden' => 0];
            }
            $rows[$k]['alm'][$aid] = ($rows[$k]['alm'][$aid] ?? 0) + 1;
            $rows[$k]['inv']++;
        }

        $meses = [];
        foreach ($this->model->selectTransitoODP() as $t) {
            if (!$pasa($t['sublinea'], $t['cve_articulo'] . ' ' . $t['descripcion'] . ' ' . ($nombres[(int) $t['inventarioid']] ?? ''))) {
                continue;
            }
            $k = $fila($t['sublinea'], $t['sublinea_desc'], $t['inventarioid'], $t['cve_articulo'], $t['descripcion'], self::COLOR_TRANSITO);
            $n = (int) $t['qty'];
            $rows[$k]['tra'][$t['mes']] = ($rows[$k]['tra'][$t['mes']] ?? 0) + $n;
            $rows[$k]['trans'] += $n;
            $meses[$t['mes']] = true;
        }
        $meses = array_keys($meses);
        sort($meses);

        uasort($alms, fn($a, $b) => [$a['orden'], $a['cve']] <=> [$b['orden'], $b['cve']]);
        uasort($rows, fn($a, $b) => [self::ordenSublinea($a['sub']), $a['sub'], mb_strtoupper($a['modelo']), $a['cve'], mb_strtoupper($a['color'])]
                                <=> [self::ordenSublinea($b['sub']), $b['sub'], mb_strtoupper($b['modelo']), $b['cve'], mb_strtoupper($b['color'])]);

        // Totales por segmento, por almacén / mes y generales
        $segs = [];
        $tot = ['alm' => array_fill_keys(array_keys($alms), 0), 'tra' => array_fill_keys($meses, 0), 'inv' => 0, 'trans' => 0];
        foreach ($rows as $r) {
            if (!isset($segs[$r['sub']])) {
                $segs[$r['sub']] = ['sub' => $r['sub'], 'desc' => $r['sub_desc'], 'alm' => [], 'tra' => [], 'inv' => 0, 'trans' => 0, 'modelos' => []];
            }
            $s = &$segs[$r['sub']];
            foreach ($r['alm'] as $id => $n) {
                $s['alm'][$id] = ($s['alm'][$id] ?? 0) + $n;
                $tot['alm'][$id] += $n;
            }
            foreach ($r['tra'] as $m => $n) {
                $s['tra'][$m] = ($s['tra'][$m] ?? 0) + $n;
                $tot['tra'][$m] += $n;
            }
            $s['inv'] += $r['inv'];
            $s['trans'] += $r['trans'];
            $s['modelos'][$r['cve']] = true;
            $tot['inv'] += $r['inv'];
            $tot['trans'] += $r['trans'];
            unset($s);
        }

        $filtros = [];
        if ($subs) {
            $filtros[] = 'Segmento: ' . implode(', ', $subs);
        }
        if ($q !== '') {
            $filtros[] = 'Búsqueda: "' . $q . '"';
        }
        return ['rows' => $rows, 'alms' => $alms, 'meses' => $meses,
                'segs' => $segs, 'tot' => $tot, 'filtros' => $filtros, 'fecha' => date('d/m/Y H:i')];
    }

    private function logoReporte(): ?string
    {
        $p = __DIR__ . '/../Assets/images/ldr_logo_color.png';
        return is_file($p) ? $p : null;
    }

    /* ---------------------------- EXCEL con formato ---------------------------- */
    private function ejecutivoXLSX(array $d): void
    {
        $ss = $this->construirXLSXEjecutivo($d);
        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="reporte_ejecutivo_unidades_' . date('Ymd_His') . '.xlsx"');
        header('Cache-Control: max-age=0');
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss))->save('php://output');
        exit;
    }

    private function construirXLSXEjecutivo(array $d)
    {
        $CO = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::class;
        $FILL = \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID;
        $THIN = \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN;
        $L = fn($i) => $CO::stringFromColumnIndex($i);
        $estilo = function ($sh, string $rango, array $o) use ($FILL, $THIN) {
            $s = $sh->getStyle($rango);
            if (isset($o['bg'])) {
                $s->getFill()->setFillType($FILL)->getStartColor()->setRGB($o['bg']);
            }
            if (isset($o['color']) || isset($o['bold']) || isset($o['size'])) {
                $f = $s->getFont();
                if (isset($o['color'])) $f->getColor()->setRGB($o['color']);
                if (isset($o['bold'])) $f->setBold($o['bold']);
                if (isset($o['size'])) $f->setSize($o['size']);
            }
            if (isset($o['h'])) $s->getAlignment()->setHorizontal($o['h']);
            if (isset($o['wrap'])) $s->getAlignment()->setWrapText(true);
            $s->getAlignment()->setVertical('center');
            if (!empty($o['borde'])) {
                $s->getBorders()->getAllBorders()->setBorderStyle($THIN)->getColor()->setRGB(self::C_LINEA);
            }
        };
        $NUM = '#,##0;-#,##0;"–"';
        $t = $d['tot'];
        $total = $t['inv'] + $t['trans'];

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ss->getProperties()->setCreator('LDR Solutions · MRP')->setTitle('Reporte ejecutivo de unidades');
        $ss->getDefaultStyle()->getFont()->setName('Calibri')->setSize(10);

        $encabezado = function ($sh, string $titulo, string $ultCol) use ($d, $estilo) {
            $sh->getRowDimension(1)->setRowHeight(34);
            $sh->getRowDimension(2)->setRowHeight(16);
            $sh->mergeCells("B1:{$ultCol}1");
            $sh->setCellValue('B1', $titulo);
            $estilo($sh, "A1:{$ultCol}1", ['bold' => true, 'size' => 16, 'color' => self::C_OSCURO]);
            $sh->mergeCells("B2:{$ultCol}2");
            $sh->setCellValue('B2', 'LDR Solutions · Generado el ' . $d['fecha'] . ($d['filtros'] ? '   ·   ' . implode(' · ', $d['filtros']) : ''));
            $estilo($sh, "B2:{$ultCol}2", ['size' => 9, 'color' => '6B7280']);
            $sh->getStyle("A3:{$ultCol}3")->getBorders()->getTop()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM)->getColor()->setRGB(self::C_TRA);
            if ($logo = $this->logoReporte()) {
                $dr = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $dr->setPath($logo);
                $dr->setHeight(40);
                $dr->setCoordinates('A1');
                $dr->setOffsetX(4);
                $dr->setOffsetY(2);
                $dr->setWorksheet($sh);
            }
            $sh->setShowGridlines(false);
            $sh->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                ->setPaperSize(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::PAPERSIZE_LETTER)
                ->setFitToWidth(1)->setFitToHeight(0);
            $sh->getPageMargins()->setLeft(0.3)->setRight(0.3)->setTop(0.4)->setBottom(0.5);
            $sh->getHeaderFooter()->setOddFooter('&L&8LDR Solutions · Reporte ejecutivo de unidades&R&8Página &P de &N');
        };

        /* ===== Hoja 1: Resumen ===== */
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Resumen');
        $encabezado($sh, 'Reporte ejecutivo · Inventario y tránsito de unidades', 'H');
        $sh->getColumnDimension('A')->setWidth(12);
        foreach (['B' => 34, 'C' => 14, 'D' => 14, 'E' => 14, 'F' => 14, 'G' => 3, 'H' => 3] as $c => $w) {
            $sh->getColumnDimension($c)->setWidth($w);
        }
        // Tarjetas
        $kpis = [['B', 'INVENTARIO REAL', $t['inv'], self::C_INV, self::C_INV_SUAVE],
                 ['C', 'EN TRÁNSITO', $t['trans'], self::C_TRA, self::C_TRA_SUAVE],
                 ['E', 'TOTAL', $total, self::C_OSCURO, self::C_SEG]];
        $sh->mergeCells('C5:D5');
        $sh->mergeCells('C6:D6');
        $sh->mergeCells('E5:F5');
        $sh->mergeCells('E6:F6');
        foreach ($kpis as [$c, $tit, $v, $col, $suave]) {
            $fin = $c === 'B' ? 'B' : chr(ord($c) + 1);
            $sh->setCellValue("{$c}5", $tit);
            $sh->setCellValue("{$c}6", $v);
            $estilo($sh, "{$c}5:{$fin}5", ['bg' => $col, 'color' => 'FFFFFF', 'bold' => true, 'size' => 9, 'h' => 'center']);
            $estilo($sh, "{$c}6:{$fin}6", ['bg' => $suave, 'color' => $col, 'bold' => true, 'size' => 20, 'h' => 'center']);
            $sh->getStyle("{$c}6")->getNumberFormat()->setFormatCode('#,##0');
        }
        $sh->getRowDimension(6)->setRowHeight(34);
        $sh->setCellValue('B7', $total ? 'Tránsito = ' . round($t['trans'] / $total * 100, 1) . '% del total' : '');
        $estilo($sh, 'B7', ['size' => 9, 'color' => '6B7280']);

        // Resumen por segmento
        $r = 9;
        $sh->setCellValue("B{$r}", 'Segmento');
        $sh->setCellValue("C{$r}", 'Inventario real');
        $sh->setCellValue("D{$r}", 'En tránsito');
        $sh->setCellValue("E{$r}", 'Total');
        $sh->setCellValue("F{$r}", '% del total');
        $estilo($sh, "B{$r}:F{$r}", ['bg' => self::C_OSCURO, 'color' => 'FFFFFF', 'bold' => true, 'h' => 'center', 'borde' => true]);
        $estilo($sh, "C{$r}", ['bg' => self::C_INV]);
        $estilo($sh, "D{$r}", ['bg' => self::C_TRA]);
        $sh->getRowDimension($r)->setRowHeight(22);
        $ini = $r + 1;
        foreach ($d['segs'] as $s) {
            $r++;
            $sh->setCellValue("B{$r}", $s['sub'] . ' · ' . $s['desc']);
            $sh->setCellValue("C{$r}", $s['inv']);
            $sh->setCellValue("D{$r}", $s['trans']);
            $sh->setCellValue("E{$r}", "=C{$r}+D{$r}");
            $sh->setCellValue("F{$r}", $total ? "=E{$r}/" . $total : 0);
            $estilo($sh, "B{$r}:F{$r}", ['borde' => true]);
            if (($r - $ini) % 2 === 1) {
                $estilo($sh, "B{$r}:F{$r}", ['bg' => 'F7F8FA']);
            }
        }
        $fin = $r;
        $r++;
        $sh->setCellValue("B{$r}", 'TOTAL');
        $sh->setCellValue("C{$r}", "=SUM(C{$ini}:C{$fin})");
        $sh->setCellValue("D{$r}", "=SUM(D{$ini}:D{$fin})");
        $sh->setCellValue("E{$r}", "=SUM(E{$ini}:E{$fin})");
        $sh->setCellValue("F{$r}", $total ? 1 : 0);
        $estilo($sh, "B{$r}:F{$r}", ['bg' => self::C_OSCURO, 'color' => 'FFFFFF', 'bold' => true, 'borde' => true]);
        $sh->getStyle("C{$ini}:E{$r}")->getNumberFormat()->setFormatCode($NUM);
        $sh->getStyle("F{$ini}:F{$r}")->getNumberFormat()->setFormatCode('0.0%');
        $estilo($sh, "C{$ini}:F{$r}", ['h' => 'right']);
        $estilo($sh, "C{$ini}:C{$fin}", ['color' => self::C_INV, 'bold' => true]);
        $estilo($sh, "D{$ini}:D{$fin}", ['color' => self::C_TRA, 'bold' => true]);

        // Tránsito por mes de carga
        if ($d['meses']) {
            $r += 2;
            $sh->setCellValue("B{$r}", 'En tránsito por mes de carga');
            $sh->setCellValue("C{$r}", 'Unidades');
            $estilo($sh, "B{$r}:C{$r}", ['bg' => self::C_TRA, 'color' => 'FFFFFF', 'bold' => true, 'borde' => true]);
            foreach ($d['meses'] as $m) {
                $r++;
                $sh->setCellValue("B{$r}", $this->mesCorto($m));
                $sh->setCellValue("C{$r}", $t['tra'][$m]);
                $estilo($sh, "B{$r}:C{$r}", ['borde' => true]);
                $sh->getStyle("C{$r}")->getNumberFormat()->setFormatCode($NUM);
            }
        }

        /* ===== Hoja 2: Detalle ===== */
        $sh = $ss->createSheet();
        $sh->setTitle('Detalle');
        $alms = $d['alms'];                          // columnas de inventario: Planta / En tránsito / Otras
        $meses = $d['meses'];
        $cInv0 = 4;                                  // A=Segmento B=Modelo C=Color
        $cMes0 = $cInv0 + count($alms);
        $cTot = $cMes0 + count($meses);
        $ult = $L($cTot);
        $encabezado($sh, 'Detalle por modelo · Inventario real vs. en tránsito', $ult);
        $sh->getColumnDimension('A')->setWidth(12);
        $sh->getColumnDimension('B')->setWidth(52);
        $sh->getColumnDimension('C')->setWidth(14);
        for ($c = $cInv0; $c <= $cTot; $c++) {
            $sh->getColumnDimension($L($c))->setWidth(13);
        }
        $ci = $cInv0;
        foreach ($alms as $a) {                      // columnas con nombre largo (Planta/Montenegro)
            $sh->getColumnDimension($L($ci++))->setWidth(max(13, mb_strlen($a['cve']) + 3));
        }

        // Encabezado: fila 5 bloques, filas 6-7 columnas (meses: ALM-TRANSITO en 6, mes en 7)
        [$r1, $r2, $r3] = [5, 6, 7];
        $sh->mergeCells("A{$r1}:C{$r2}");
        if ($alms) {
            $sh->mergeCells($L($cInv0) . $r1 . ':' . $L($cMes0 - 1) . $r1);
            $sh->setCellValue($L($cInv0) . $r1, 'INVENTARIO REAL · en almacenes');
        }
        if ($meses) {
            $sh->mergeCells($L($cMes0) . $r1 . ':' . $L($cTot - 1) . $r1);
            $sh->setCellValue($L($cMes0) . $r1, 'EN TRÁNSITO');
            $sh->mergeCells($L($cMes0) . $r2 . ':' . $L($cTot - 1) . $r2);
            $sh->setCellValue($L($cMes0) . $r2, 'ALM-TRANSITO · mes de carga');
        }
        $sh->mergeCells($L($cTot) . $r1 . ':' . $L($cTot) . $r3);
        $sh->setCellValue($L($cTot) . $r1, 'TOTAL');
        $c = $cInv0;
        foreach ($alms as $a) {
            $sh->mergeCells($L($c) . $r2 . ':' . $L($c) . $r3);
            $sh->setCellValue($L($c++) . $r2, $a['cve']);
        }
        $sh->setCellValue("A{$r3}", 'Segmento');
        $sh->setCellValue("B{$r3}", 'Modelo');
        $sh->setCellValue("C{$r3}", 'Color');
        $c = $cMes0;
        foreach ($meses as $m) {
            $sh->setCellValue($L($c++) . $r3, $this->mesCorto($m));
        }
        $estilo($sh, "A{$r1}:{$ult}{$r3}", ['bold' => true, 'color' => 'FFFFFF', 'h' => 'center', 'wrap' => true, 'borde' => true, 'bg' => self::C_OSCURO]);
        if ($alms) {
            $estilo($sh, $L($cInv0) . $r1 . ':' . $L($cMes0 - 1) . $r3, ['bg' => self::C_INV]);
        }
        if ($meses) {
            $estilo($sh, $L($cMes0) . $r1 . ':' . $L($cTot - 1) . $r3, ['bg' => self::C_TRA]);
        }
        $estilo($sh, "A{$r3}:C{$r3}", ['h' => 'left']);
        $sh->getRowDimension($r1)->setRowHeight(20);
        $sh->getRowDimension($r2)->setRowHeight(18);
        $sh->getRowDimension($r3)->setRowHeight(18);

        // Cuerpo
        $r = $r3;
        $segActual = null;
        $iniSeg = 0;
        $sumCols = function ($desde, $hasta) use ($cInv0, $cTot, $L, $sh, &$r) {
            for ($c = $cInv0; $c <= $cTot; $c++) {
                $col = $L($c);
                $sh->setCellValue($col . $r, "=SUBTOTAL(9,{$col}{$desde}:{$col}{$hasta})");
            }
        };
        $cierraSeg = function () use (&$segActual, &$iniSeg, &$r, $sh, $estilo, $ult, $sumCols, $cTot, $L) {
            // Sin subtotal por segmento (lo pidió Dirección): solo el total general
        };
        $par = false;
        foreach ($d['rows'] as $row) {
            if ($row['sub'] !== $segActual) {
                $cierraSeg();
                $segActual = $row['sub'];
                $r++;
                $sh->setCellValue("A{$r}", $row['sub'] . ' · ' . $row['sub_desc']);
                $sh->mergeCells("A{$r}:{$ult}{$r}");
                $estilo($sh, "A{$r}:{$ult}{$r}", ['bold' => true, 'color' => self::C_OSCURO, 'bg' => 'DDE1E8', 'borde' => true]);
                $iniSeg = $r + 1;
                $par = false;
            }
            $r++;
            $sh->setCellValue("A{$r}", $row['sub']);
            $sh->setCellValue("B{$r}", $row['modelo'] . '  ·  ' . $row['cve']);
            $sh->setCellValue("C{$r}", $row['color']);
            $c = $cInv0;
            foreach ($alms as $id => $a) {
                if ($v = $row['alm'][$id] ?? 0) {
                    $sh->setCellValue($L($c) . $r, $v);
                }
                $c++;
            }
            foreach ($meses as $m) {
                if ($v = $row['tra'][$m] ?? 0) {
                    $sh->setCellValue($L($c) . $r, $v);
                }
                $c++;
            }
            $sh->setCellValue($L($cTot) . $r, $cTot > $cInv0 ? '=SUM(' . $L($cInv0) . $r . ':' . $L($cTot - 1) . $r . ')' : 0);
            $estilo($sh, "A{$r}:{$ult}{$r}", ['borde' => true] + ($par ? ['bg' => 'F7F8FA'] : []));
            $estilo($sh, $L($cTot) . $r, ['bg' => 'ECEEF1', 'bold' => true]);
            $sh->getStyle("A{$r}")->getFont()->getColor()->setRGB('9CA3AF');
            $par = !$par;
        }
        $cierraSeg();
        $r++;
        $sh->setCellValue("A{$r}", 'TOTAL GENERAL');
        $sh->mergeCells("A{$r}:C{$r}");
        $sumCols($r3 + 1, $r - 1);
        $estilo($sh, "A{$r}:{$ult}{$r}", ['bg' => self::C_OSCURO, 'color' => 'FFFFFF', 'bold' => true, 'borde' => true]);
        $sh->getRowDimension($r)->setRowHeight(20);
        $sh->getStyle($L($cInv0) . ($r3 + 1) . ":{$ult}{$r}")->getNumberFormat()->setFormatCode($NUM);
        $estilo($sh, $L($cInv0) . ($r3 + 1) . ":{$ult}{$r}", ['h' => 'right']);
        $sh->freezePane('D' . ($r3 + 1));
        $sh->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($r1, $r3);

        $ss->setActiveSheetIndex(0);
        return $ss;
    }

    /* ---------------------------- PDF ---------------------------- */
    private function ejecutivoPDF(array $d): void
    {
        require_once(__DIR__ . '/../Libraries/tcpdf/tcpdf.php');
        // Encabezado (logo, título, subtítulo, fecha) y pie (nota + página) en TODAS las páginas
        $pdf = new class('L', 'mm', 'LETTER', true, 'UTF-8', false) extends \TCPDF {
            public $hdr;
            public $ftr;
            public function Header()
            {
                if ($this->hdr) {
                    ($this->hdr)($this);
                }
            }
            public function Footer()
            {
                if ($this->ftr) {
                    ($this->ftr)($this);
                }
            }
        };
        $fecha = $d['fecha'];
        $logo = $this->logoReporte();
        $pdf->SetCreator('LDR Solutions · MRP');
        $pdf->SetTitle('Reporte ejecutivo de unidades');
        $pdf->SetMargins(9, 25, 9);
        $pdf->SetHeaderMargin(0);
        $pdf->SetAutoPageBreak(true, 16);
        $pdf->SetFont('helvetica', '', 8);
        $subtitulo = 'Resumen' . ($d['filtros'] ? ' · ' . implode(' · ', $d['filtros']) : '');

        $rgb = fn($hex) => [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $num = fn($n) => $n ? number_format($n) : '<font color="#C0C4CC">–</font>';
        $t = $d['tot'];
        $total = $t['inv'] + $t['trans'];
        $W = 279.4 - 18;   // ancho útil (mm)

        // Encabezado de cada página (banda con logo, título y fecha)
        $pdf->hdr = function ($pdf) use ($logo, $fecha, $rgb, $W, &$subtitulo) {
            $sub = $subtitulo;
            $pdf->SetXY(26, 7.5);
            $pdf->SetFont('helvetica', 'B', 15);
            $pdf->SetTextColor(...$rgb(self::C_OSCURO));
            $pdf->Cell(160, 7, 'Reporte ejecutivo · Inventario y tránsito de unidades', 0, 1);
            $pdf->SetX(26);
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->SetTextColor(107, 114, 128);
            $pdf->Cell(160, 5, $sub, 0, 0);
            $pdf->SetXY(9 + $W - 80, 8);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(80, 5, 'Generado el ' . $fecha, 0, 1, 'R');
            $pdf->SetDrawColor(...$rgb(self::C_TRA));
            $pdf->SetLineWidth(0.6);
            $pdf->Line(9, 22, 9 + $W, 22);
            $pdf->SetLineWidth(0.2);
            $pdf->SetTextColor(0, 0, 0);
        };
        $pdf->ftr = function ($pdf) use ($W) {
            $pdf->SetY(-10);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetTextColor(150, 150, 150);
            $pdf->Cell($W / 2, 4, 'LDR Solutions · Reporte ejecutivo de unidades · Uso interno', 0, 0, 'L');
            $pdf->Cell($W / 2, 4, 'Página ' . $pdf->getAliasNumPage() . ' de ' . $pdf->getAliasNbPages(), 0, 0, 'R');
        };

        /* ===== Página 1: resumen ===== */
        $pdf->AddPage();

        // Tarjetas KPI dibujadas
        $kpis = [['INVENTARIO REAL', $t['inv'], self::C_INV, self::C_INV_SUAVE, 'Unidades con VIN en almacenes'],
                 ['EN TRÁNSITO', $t['trans'], self::C_TRA, self::C_TRA_SUAVE, 'Order delivery plan · sin entrada'],
                 ['TOTAL', $total, self::C_OSCURO, self::C_SEG, $total ? 'Tránsito = ' . round($t['trans'] / $total * 100, 1) . '% del total' : '']];
        $gap = 5;
        $wk = ($W - 2 * $gap) / 3;
        $y = $pdf->GetY();
        foreach ($kpis as $i => [$tit, $v, $col, $suave, $nota]) {
            $x = 9 + $i * ($wk + $gap);
            $pdf->SetFillColor(...$rgb($suave));
            $pdf->RoundedRect($x, $y, $wk, 24, 2, '1111', 'F');
            $pdf->SetFillColor(...$rgb($col));
            $pdf->Rect($x, $y, 2.2, 24, 'F');
            $pdf->SetXY($x + 6, $y + 3);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(...$rgb($col));
            $pdf->Cell($wk - 8, 4, $tit, 0, 2);
            $pdf->SetFont('helvetica', 'B', 22);
            $pdf->Cell($wk - 8, 10, number_format($v), 0, 2);
            $pdf->SetFont('helvetica', '', 7.5);
            $pdf->SetTextColor(107, 114, 128);
            $pdf->Cell($wk - 8, 4, $nota, 0, 2);
        }
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetY($y + 30);

        // Resumen por segmento con barras (inventario | tránsito)
        $maxSeg = 1;
        foreach ($d['segs'] as $s) {
            $maxSeg = max($maxSeg, $s['inv'] + $s['trans']);
        }
        $th = 'style="background-color:#' . self::C_OSCURO . ';color:#ffffff;font-weight:bold;"';
        $html = '<table cellpadding="3.5" style="font-size:8.5pt;">'
            . '<tr ' . $th . '><td width="28%">Segmento</td><td width="12%" align="right" style="background-color:#' . self::C_INV . ';">Inventario real</td>'
            . '<td width="12%" align="right" style="background-color:#' . self::C_TRA . ';">En tránsito</td><td width="10%" align="right">Total</td>'
            . '<td width="8%" align="right">% total</td><td width="30%">Composición (inventario | tránsito)</td></tr>';
        $i = 0;
        foreach ($d['segs'] as $s) {
            $tt = $s['inv'] + $s['trans'];
            $bg = $i++ % 2 ? ' style="background-color:#F7F8FA;"' : '';
            $wInv = max(0, round($s['inv'] / $maxSeg * 100));
            $wTra = max(0, round($s['trans'] / $maxSeg * 100));
            $barra = '<table cellpadding="0" cellspacing="0"><tr>'
                . ($wInv ? '<td width="' . $wInv . '%" style="background-color:#' . self::C_INV . ';font-size:5pt;">&nbsp;</td>' : '')
                . ($wTra ? '<td width="' . $wTra . '%" style="background-color:#' . self::C_TRA . ';font-size:5pt;">&nbsp;</td>' : '')
                . (100 - $wInv - $wTra > 0 ? '<td width="' . (100 - $wInv - $wTra) . '%" style="font-size:5pt;">&nbsp;</td>' : '')
                . '</tr></table>';
            $html .= '<tr' . $bg . '><td width="28%"><b>' . $h($s['sub']) . '</b> <font color="#6B7280">· ' . $h($s['desc']) . '</font></td>'
                . '<td width="12%" align="right" style="color:#' . self::C_INV . ';"><b>' . $num($s['inv']) . '</b></td>'
                . '<td width="12%" align="right" style="color:#' . self::C_TRA . ';"><b>' . $num($s['trans']) . '</b></td>'
                . '<td width="10%" align="right"><b>' . number_format($tt) . '</b></td>'
                . '<td width="8%" align="right">' . ($total ? number_format($tt / $total * 100, 1) . '%' : '') . '</td>'
                . '<td width="30%">' . $barra . '</td></tr>';
        }
        $html .= '<tr ' . $th . '><td width="28%">TOTAL</td><td width="12%" align="right">' . number_format($t['inv']) . '</td>'
            . '<td width="12%" align="right">' . number_format($t['trans']) . '</td><td width="10%" align="right">' . number_format($total) . '</td>'
            . '<td width="8%" align="right">' . ($total ? '100%' : '') . '</td><td width="30%"></td></tr></table>';
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetTextColor(...$rgb(self::C_OSCURO));
        $pdf->Cell(0, 6, 'Resumen por segmento', 0, 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');

        // Tránsito por mes de carga (renglón compacto)
        if ($d['meses']) {
            $pdf->Ln(1);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->SetTextColor(...$rgb(self::C_OSCURO));
            $pdf->Cell(0, 6, 'En tránsito por mes de carga', 0, 1);
            $wm = min(40, $W / max(1, count($d['meses'])));
            $y = $pdf->GetY();
            foreach (array_values($d['meses']) as $i => $m) {
                $x = 9 + $i * ($wm + 2);
                $pdf->SetFillColor(...$rgb(self::C_TRA_SUAVE));
                $pdf->RoundedRect($x, $y, $wm, 13, 1.5, '1111', 'F');
                $pdf->SetXY($x, $y + 1.5);
                $pdf->SetFont('helvetica', '', 7.5);
                $pdf->SetTextColor(107, 114, 128);
                $pdf->Cell($wm, 4, $this->mesCorto($m), 0, 2, 'C');
                $pdf->SetFont('helvetica', 'B', 12);
                $pdf->SetTextColor(...$rgb(self::C_TRA));
                $pdf->Cell($wm, 6, number_format($t['tra'][$m]), 0, 2, 'C');
            }
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetY($y + 16);
        }

        /* ===== Páginas siguientes: detalle por modelo ===== */
        $alms = $d['alms'];                          // columnas de inventario: Planta / En tránsito / Otras
        $meses = $d['meses'];
        $nAlm = count($alms);
        $nMes = count($meses);
        $wColor = 8;
        $wTot = 7;
        $nDin = $nAlm + $nMes;
        $wDin = $nDin ? min(9, floor((100 - 40 - $wColor - $wTot) / $nDin * 10) / 10) : 0;
        $wMod = 100 - $wColor - $wTot - $wDin * $nDin;
        $td = fn($w, $txt, $extra = '') => '<td width="' . $w . '%"' . $extra . '>' . $txt . '</td>';
        $sInv = 'background-color:#' . self::C_INV . ';color:#ffffff;font-weight:bold;';
        $sTra = 'background-color:#' . self::C_TRA . ';color:#ffffff;font-weight:bold;';
        $sOsc = 'background-color:#' . self::C_OSCURO . ';color:#ffffff;font-weight:bold;';

        // Encabezado: fila 1 bloques; fila 2 columnas de inventario + ALM-TRANSITO; fila 3 meses
        $thead = '<thead><tr><td width="' . ($wMod + $wColor) . '%" colspan="2" style="' . $sOsc . '"></td>'
            . ($nAlm ? '<td width="' . ($wDin * $nAlm) . '%" colspan="' . $nAlm . '" align="center" style="' . $sInv . '">INVENTARIO REAL · en almacenes</td>' : '')
            . ($nMes ? '<td width="' . ($wDin * $nMes) . '%" colspan="' . $nMes . '" align="center" style="' . $sTra . '">EN TRÁNSITO</td>' : '')
            . $td($wTot, '', ' style="' . $sOsc . '"') . '</tr><tr>'
            . '<td width="' . ($wMod + $wColor) . '%" colspan="2" style="' . $sOsc . '"></td>';
        foreach ($alms as $a) {
            $thead .= $td($wDin, str_replace('/', '/<br>', $h($a['cve'])), ' align="center" style="' . $sInv . '"');
        }
        $thead .= ($nMes ? '<td width="' . ($wDin * $nMes) . '%" colspan="' . $nMes . '" align="center" style="' . $sTra . 'font-size:6.5pt;">ALM-TRANSITO · mes de carga</td>' : '')
            . $td($wTot, 'TOTAL', ' align="center" style="' . $sOsc . '"') . '</tr><tr>'
            . $td($wMod, 'Modelo', ' style="' . $sOsc . '"') . $td($wColor, 'Color', ' style="' . $sOsc . '"');
        foreach ($alms as $a) {
            $thead .= $td($wDin, '', ' style="' . $sInv . '"');
        }
        foreach ($meses as $m) {
            $thead .= $td($wDin, $h($this->mesCorto($m)), ' align="center" style="' . $sTra . '"');
        }
        $thead .= $td($wTot, '', ' style="' . $sOsc . '"') . '</tr></thead>';

        $celdas = function (array $alm, int $inv, array $tra, int $trans, bool $resalta = false) use ($alms, $meses, $td, $wDin, $wTot, $num) {
            $o = '';
            foreach ($alms as $id => $a) {
                $o .= $td($wDin, $num($alm[$id] ?? 0), ' align="right"');
            }
            foreach ($meses as $m) {
                $o .= $td($wDin, $num($tra[$m] ?? 0), ' align="right"');
            }
            return $o . $td($wTot, '<b>' . $num($inv + $trans) . '</b>', ' align="right" style="background-color:#' . ($resalta ? 'D9DCE2' : 'ECEEF1') . ';"');
        };
        $colspan = 2 + $nAlm + $nMes + 1;
        $body = '';
        $segActual = null;
        $par = false;
        foreach ($d['rows'] as $r) {
            if ($r['sub'] !== $segActual) {
                $segActual = $r['sub'];
                $par = false;
                $body .= '<tr nobr="true"><td width="100%" colspan="' . $colspan . '" style="background-color:#DDE1E8;color:#' . self::C_OSCURO . ';font-weight:bold;font-size:8pt;">'
                    . $h($r['sub']) . ' <font color="#6B7280">· ' . $h($r['sub_desc']) . '</font></td></tr>';
            }
            $body .= '<tr nobr="true"' . ($par ? ' style="background-color:#F7F8FA;"' : '') . '>'
                . $td($wMod, $h($r['modelo']) . ' <font color="#9CA3AF" size="5.5">' . $h($r['cve']) . '</font>')
                . $td($wColor, $h($r['color'])) . $celdas($r['alm'], $r['inv'], $r['tra'], $r['trans']) . '</tr>';
            $par = !$par;
        }
        $foot = '<tr nobr="true" style="' . $sOsc . '">' . $td($wMod + $wColor, 'TOTAL GENERAL', ' colspan="2"');
        foreach ($alms as $id => $a) {
            $foot .= $td($wDin, number_format($t['alm'][$id] ?? 0), ' align="right"');
        }
        foreach ($meses as $m) {
            $foot .= $td($wDin, number_format($t['tra'][$m] ?? 0), ' align="right"');
        }
        $foot .= $td($wTot, number_format($total), ' align="right"') . '</tr>';

        $subtitulo = 'Detalle por segmento, modelo y color' . ($d['filtros'] ? ' · ' . implode(' · ', $d['filtros']) : '');
        $pdf->AddPage();
        if ($d['rows']) {
            $pdf->SetFont('helvetica', '', 7);
            $pdf->writeHTML('<table border="0.2" cellpadding="1.2" style="font-size:6.8pt;border-color:#' . self::C_LINEA . ';">' . $thead . $body . $foot . '</table>', false, false, true, false, '');
        } else {
            $pdf->Cell(0, 8, 'No hay unidades con esos filtros.', 0, 1);
        }
        // Logo en cada página al final (dentro de Header() TCPDF lo pierde en los saltos de página de tablas largas)
        if ($logo) {
            $pdf->SetAutoPageBreak(false);
            for ($i = 1, $n = $pdf->getNumPages(); $i <= $n; $i++) {
                $pdf->setPage($i);
                $pdf->Image($logo, 9, 7, 0, 13, 'PNG');
            }
            $pdf->lastPage();
        }
        if (ob_get_length()) {
            ob_end_clean();
        }
        $pdf->Output('reporte_ejecutivo_unidades_' . date('Ymd_His') . '.pdf', 'I');
        exit;
    }
}
