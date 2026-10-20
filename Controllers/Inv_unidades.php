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
        echo json_encode([
            'generado'        => date('Y-m-d H:i'),
            'orden_sublineas' => self::ORDEN_SUBLINEAS,
            'unidades'        => $this->model->selectUnidades(),
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
        foreach ($u as $r) {
            $alms[$r['almacenid']] = ['cve' => $r['cve_almacen'], 'planta' => $r['planta']];
        }
        uasort($alms, fn($a, $b) => [$a['planta'], $a['cve']] <=> [$b['planta'], $b['cve']]);

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
            $th1 .= '<td width="' . ($wAlm * $n) . '%" colspan="' . $n . '" align="center">' . ($p === 'OTROS' ? 'Otros' : 'Planta ' . (int)substr($p, 2)) . '</td>';
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
        if ($planta !== '') $filtros[] = 'Planta: ' . $planta;
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
}
