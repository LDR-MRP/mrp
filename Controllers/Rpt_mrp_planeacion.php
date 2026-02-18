<?php

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;



class Rpt_mrp_planeacion extends Controllers
{
  public function __construct()
  {
    parent::__construct();
    session_start();
    if (empty($_SESSION['login'])) {
      header('Location: ' . base_url() . '/login');
      die();
    }

     getPermisos(MPINDICADORESOT);
  }

  

  public function Rpt_mrp_planeacion()
  {
    if (empty($_SESSION['permisosMod']['r'])) {
      header("Location:" . base_url() . '/dashboard');
      die();
    }
    $data['page_tag'] = "Reportes";
    $data['page_title'] = "Reportes";
    $data['page_name'] = "Reportes";
    $data['page_functions_js'] = "functions_rpt_mrp_planeacion.js";
    $this->views->getView($this, "rpt_mrp_planeacion", $data);
  }

  private function json($status, $msg, $data = [])
  {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => $status, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    die();
  }

  private function getFilters()
  {
    $planeacionid = isset($_GET['planeacionid']) ? (int) $_GET['planeacionid'] : 0;
    $fecha_ini = isset($_GET['fecha_ini']) ? trim($_GET['fecha_ini']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
    $q = isset($_GET['q']) ? trim($_GET['q']) : '';

    return [
      'planeacionid' => $planeacionid,
      'fecha_ini' => $fecha_ini,
      'fecha_fin' => $fecha_fin,
      'q' => $q
    ];
  }

  // ====== ENDPOINTS EXISTENTES ======
  public function getPlaneaciones()
  {
    $data = $this->model->getPlaneacionesDisponibles();
    $this->json(true, 'OK', $data);
  }

  public function getKpis()
  {
    $f = $this->getFilters();
    $data = $this->model->getKpis($f);
    $this->json(true, 'OK', $data);
  }

  public function getDetalle()
  {
    $f = $this->getFilters();
    $data = $this->model->getDetalle($f);
    $this->json(true, 'OK', $data);
  }

  public function getResumenSubOt()
  {
    $f = $this->getFilters();
    $data = $this->model->getResumenSubOt($f);
    $this->json(true, 'OK', $data);
  }

  public function getEncargados()
  {
    $f = $this->getFilters();
    $data = $this->model->getEncargados($f);
    $this->json(true, 'OK', $data);
  }

  public function getCalidadEstacion()
  {
    $f = $this->getFilters();
    $data = $this->model->getCalidadEstacion($f);
    $this->json(true, 'OK', $data);
  }

  // ====== NUEVOS ENDPOINTS (COSTOS) ======
  public function getCostoTotalPlaneacion()
  {
    $f = $this->getFilters();
    if (empty($f['planeacionid'])) {
      $this->json(false, 'Falta planeacionid', []);
    }

    $row = $this->model->getCostoTotalPlaneacion($f);
    $this->json(true, 'OK', $row);
  }

  public function getCostosEstacion()
  {
    $f = $this->getFilters();
    if (empty($f['planeacionid'])) {
      $this->json(false, 'Falta planeacionid', []);
    }

    $rows = $this->model->getCostosEstacion($f);
    $this->json(true, 'OK', $rows);
  }

  public function getCostosDetalle()
  {
    $f = $this->getFilters();
    if (empty($f['planeacionid'])) {
      $this->json(false, 'Falta planeacionid', []);
    }

    $rows = $this->model->getCostosDetalle($f);
    $this->json(true, 'OK', $rows);
  }


public function exportExcel(){

   
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    while (ob_get_level() > 0) { ob_end_clean(); }

    $f = $this->getFilters();
    if (($f['planeacionid'] ?? 0) <= 0) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Selecciona una Planeación para exportar.";
        exit;
    }

    $rep = $this->model->getReporteCompleto($f);

    $info = $rep['info'] ?? [];
    $k    = $rep['kpis'] ?? [];

    $filename = 'KPI_Planeacion_'.$f['planeacionid'].'_'.date('Ymd_His').'.xlsx';

    $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $ss->getProperties()
        ->setCreator('LDR Solutions · MRP')
        ->setTitle('Reporte KPI · Planeación')
        ->setDescription('Export generado desde MRP');

 
    $ESTATUS = [
        1 => 'Pendiente',
        2 => 'En proceso',
        3 => 'Finalizada'
    ];
    $CALIDAD = [
        1 => 'Pendiente',
        2 => 'En inspección',
        3 => 'Con observaciones (pausa)',
        4 => 'Rechazado',
        5 => 'Liberado'
    ];

   
    $applyHeader = function($sh, $fromCol, $toCol, $row=1){
        $range = "{$fromCol}{$row}:{$toCol}{$row}";
        $sh->getStyle($range)->getFont()->setBold(true);
        $sh->getStyle($range)->getAlignment()
           ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sh->getStyle($range)->getFill()
           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
           ->getStartColor()->setARGB('FFEFEFEF');

        $sh->freezePane("A".($row+1));
        $sh->setAutoFilter($range);
    };

    $capPct = function($v){
        $n = (float)$v;
        if(!is_finite($n)) return 0;
        if($n < 0) return 0;
        if($n > 100) return 100;
        return $n;
    };

    $autoSize = function($sh, $maxColLetter){
        foreach(range('A', $maxColLetter) as $col){
            $sh->getColumnDimension($col)->setAutoSize(true);
        }
    };

    $fillRow = function($sh, $fromCol, $toCol, $row, $argb){
        $range = "{$fromCol}{$row}:{$toCol}{$row}";
        $sh->getStyle($range)->getFill()
           ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
           ->getStartColor()->setARGB($argb);
        $sh->getStyle($range)->getFont()->setBold(true);
    };

    // ============================================================
    // HOJA KPII
    // ============================================================
    $sheet = $ss->getActiveSheet();
    $sheet->setTitle('KPI');


    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/ldr_negro.png';



    if (is_file($logoPath)) {
        $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
        $drawing->setName('Logo');
        $drawing->setDescription('Logo Empresa');
        $drawing->setPath($logoPath);
        $drawing->setCoordinates('A1');
        $drawing->setOffsetX(5);
        $drawing->setOffsetY(3);
        $drawing->setHeight(40); 
        $drawing->setWorksheet($sheet);

        // deja espacio para el logo
        $sheet->getRowDimension(1)->setRowHeight(35);
        $sheet->getColumnDimension('A')->setWidth(18);
    }


    $sheet->setCellValue('B1', 'Reporte KPI · Planeación');
    $sheet->mergeCells('B1:F1');
    $sheet->getStyle('B1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('B1')->getAlignment()
          ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);

   
    $sheet->setCellValue('A3', 'Planeación');
    $sheet->setCellValue('B3', '');

    $sheet->setCellValue('A4', 'OT');            $sheet->setCellValue('B4', $info['num_orden'] ?? '—');
    $sheet->setCellValue('A5', 'Producto');       $sheet->setCellValue('B5', $info['producto'] ?? '—');
    $sheet->setCellValue('A6', 'Supervisor');     $sheet->setCellValue('B6', $info['supervisor'] ?? '—');
    $sheet->setCellValue('A7', 'Generado');       $sheet->setCellValue('B7', date('Y-m-d H:i:s'));


    $sheet->fromArray(['KPI','Valor'], null, 'D3');
    $applyHeader($sheet, 'D','E',3);

    $fillRow($sheet, 'A', 'B', 3, 'FFE8F0FE'); 
    $fillRow($sheet, 'D', 'E', 3, 'FFE8F0FE'); 

    // KPI 
    $sheet->fromArray([
        ['Sub-OT', (int)($k['subot'] ?? 0)],
        ['Eficiencia Prom (%)', $capPct($k['eficiencia_prom'] ?? 0)],
        ['% En tiempo', $capPct($k['pct_en_tiempo'] ?? 0)],
        ['Rechazos', (int)($k['rechazos'] ?? 0)],
        ['Costo Total Planeación', (float)($rep['costo_total']['costo_total_planeacion'] ?? 0)],
    ], null, 'D4');


    $sheet->getStyle('E5:E6')->getNumberFormat()->setFormatCode('0.0');
    $sheet->getStyle('E8')->getNumberFormat()->setFormatCode('"$"#,##0.00');

    $autoSize($sheet, 'F');

    // ============================================================
    // HOJA: Detalle
    // ============================================================
    $ss->createSheet();
    $sh2 = $ss->setActiveSheetIndex(1);
    $sh2->setTitle('Detalle');

    $headers = [
        'Sub-OT','Estación','Proceso','Orden Est.',
        'Encargado','Ayudante',
        'Std (min)','Inicio','Fin','Real (min)',
        'Eficiencia %','En tiempo','Estatus','Calidad'
    ];
    $sh2->fromArray($headers, null, 'A1');
    $applyHeader($sh2, 'A','N',1);

    $rows = [];
    foreach(($rep['detalle'] ?? []) as $r){
        $estatusCode = (int)($r['estatus'] ?? 0);
        $calidadCode = (int)($r['calidad'] ?? 0);

        $rows[] = [
            $r['num_sub_orden'] ?? '',
            ($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''),
            $r['proceso'] ?? '',
            (int)($r['orden_estacion'] ?? 0),
            $r['encargado_nombre'] ?? '',
            $r['ayudante_nombre'] ?? '',
            (float)($r['estandar_min'] ?? 0),
            $r['fecha_inicio_real'] ?? '',
            $r['fecha_fin_real'] ?? '',
            (float)($r['duracion_real_min'] ?? 0),
            $capPct($r['eficiencia_pct_base'] ?? 0),
            ((int)($r['en_tiempo'] ?? 0) === 1 ? 'En tiempo' : 'Fuera'),
            $ESTATUS[$estatusCode] ?? ("Estatus ".$estatusCode),
            $CALIDAD[$calidadCode] ?? ("Calidad ".$calidadCode),
        ];
    }
    if($rows) $sh2->fromArray($rows, null, 'A2');

    $sh2->getStyle('G:G')->getNumberFormat()->setFormatCode('0.00');
    $sh2->getStyle('J:K')->getNumberFormat()->setFormatCode('0.00');


    $sh2->getColumnDimension('C')->setWidth(70);
    $sh2->getStyle('C')->getAlignment()->setWrapText(true);

    $autoSize($sh2, 'N');

    // ============================================================
    // HOJA: Resumen SubOT
    // ============================================================
    $ss->createSheet();
    $sh3 = $ss->setActiveSheetIndex(2);
    $sh3->setTitle('Resumen SubOT');

    $headers = ['Sub-OT','Std Total','Real Total','Eficiencia %','% En tiempo','Rechazos','Últ. Estatus','Últ. Calidad'];
    $sh3->fromArray($headers, null, 'A1');
    $applyHeader($sh3,'A','H',1);

    $rows = [];
    foreach(($rep['resumen'] ?? []) as $r){
        $estatusCode = (int)($r['ultimo_estatus'] ?? 0);
        $calidadCode = (int)($r['ultima_calidad'] ?? 0);

        $rows[] = [
            $r['num_sub_orden'] ?? '',
            (float)($r['std_total'] ?? 0),
            (float)($r['real_total'] ?? 0),
            $capPct($r['eficiencia'] ?? 0),
            $capPct($r['pct_en_tiempo'] ?? 0),
            (int)($r['rechazos'] ?? 0),
            $ESTATUS[$estatusCode] ?? ("Estatus ".$estatusCode),
            $CALIDAD[$calidadCode] ?? ("Calidad ".$calidadCode),
        ];
    }
    if($rows) $sh3->fromArray($rows, null, 'A2');

    $sh3->getStyle('B:E')->getNumberFormat()->setFormatCode('0.00');
    $autoSize($sh3,'H');

    // ============================================================
    // HOJA: Encargados
    // ============================================================
    $ss->createSheet();
    $sh4 = $ss->setActiveSheetIndex(3);
    $sh4->setTitle('Encargados');

    $headers = ['Encargado','Registros','Real Total (min)','Eficiencia Prom %','% En tiempo','Rechazos'];
    $sh4->fromArray($headers, null, 'A1');
    $applyHeader($sh4,'A','F',1);

    $rows = [];
    foreach(($rep['encargados'] ?? []) as $r){
        $rows[] = [
            $r['encargado_nombre'] ?? '',
            (int)($r['registros'] ?? 0),
            (float)($r['real_total'] ?? 0),
            $capPct($r['eficiencia_prom'] ?? 0),
            $capPct($r['pct_en_tiempo'] ?? 0),
            (int)($r['rechazos'] ?? 0),
        ];
    }
    if($rows) $sh4->fromArray($rows, null, 'A2');

    $sh4->getStyle('C:E')->getNumberFormat()->setFormatCode('0.00');
    $autoSize($sh4,'F');

    // ============================================================
    // HOJA: Calidad (conteos)
    // ============================================================
    $ss->createSheet();
    $sh5 = $ss->setActiveSheetIndex(4);
    $sh5->setTitle('Calidad');

    $headers = ['Estación','Pen. Ins','En insp','Obs','Rech','Lib','Total'];
    $sh5->fromArray($headers, null, 'A1');
    $applyHeader($sh5,'A','G',1);

    $rows = [];
    foreach(($rep['calidad'] ?? []) as $r){
        $rows[] = [
            ($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''),
            (int)($r['c1'] ?? 0),
            (int)($r['c2'] ?? 0),
            (int)($r['c3'] ?? 0),
            (int)($r['c4'] ?? 0),
            (int)($r['c5'] ?? 0),
            (int)($r['total'] ?? 0),
        ];
    }
    if($rows) $sh5->fromArray($rows, null, 'A2');

    $autoSize($sh5,'G');

    // ============================================================
    // HOJA: Costos Estación
    // ============================================================
    $ss->createSheet();
    $sh6 = $ss->setActiveSheetIndex(5);
    $sh6->setTitle('Costos Estación');

    $headers = ['Estación','Proceso','Encargado','Ayudante','Costo Total','C1','C2','C3','C4','C5','Total Registros'];
    $sh6->fromArray($headers, null, 'A1');
    $applyHeader($sh6,'A','K',1);

    $rows = [];
    foreach(($rep['costos_estacion'] ?? []) as $r){
        $rows[] = [
            ($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''),
            $r['proceso'] ?? '',
            $r['encargado_nombre'] ?? '',
            $r['ayudante_nombre'] ?? '',
            (float)($r['costo_total_estacion'] ?? 0),
            (int)($r['c1'] ?? 0),
            (int)($r['c2'] ?? 0),
            (int)($r['c3'] ?? 0),
            (int)($r['c4'] ?? 0),
            (int)($r['c5'] ?? 0),
            (int)($r['total_registros'] ?? 0),
        ];
    }
    if($rows) $sh6->fromArray($rows, null, 'A2');

    $sh6->getStyle('E:E')->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sh6->getColumnDimension('B')->setWidth(70);
    $sh6->getStyle('B')->getAlignment()->setWrapText(true);

    $autoSize($sh6,'K');

    // ============================================================
    // HOJA: Costos Detalle
    // ============================================================
    $ss->createSheet();
    $sh7 = $ss->setActiveSheetIndex(6);
    $sh7->setTitle('Costos Detalle');

    $headers = ['Estación','Artículo','Descripción','Cant Planeada','Cant x Prod','Cant Total','Últ Costo','Costo Total'];
    $sh7->fromArray($headers, null, 'A1');
    $applyHeader($sh7,'A','H',1);

    $rows = [];
    foreach(($rep['costos_detalle'] ?? []) as $r){
        $rows[] = [
            ($r['cve_estacion'] ?? '').' · '.($r['nombre_estacion'] ?? ''),
            $r['cve_articulo'] ?? '',
            $r['descripcion'] ?? '',
            (float)($r['cantidad_planeada'] ?? 0),
            (float)($r['cantidad_por_producto'] ?? 0),
            (float)($r['cantidad_total_requerida'] ?? 0),
            (float)($r['ultimo_costo'] ?? 0),
            (float)($r['costo_total_articulo'] ?? 0),
        ];
    }
    if($rows) $sh7->fromArray($rows, null, 'A2');

    $sh7->getStyle('G:H')->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sh7->getColumnDimension('C')->setWidth(70);
    $sh7->getStyle('C')->getAlignment()->setWrapText(true);

    $autoSize($sh7,'H');


    $ss->setActiveSheetIndex(0);

    if (ob_get_length()) { ob_end_clean(); }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($ss);
    $writer->save('php://output');
    exit;
}






public function exportPdfv2(){

    // BLOQUEAR SALIDA
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    while (ob_get_level() > 0) { ob_end_clean(); }

    $f = $this->getFilters();
    if (($f['planeacionid'] ?? 0) <= 0) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Selecciona una Planeación para exportar.";
        exit;
    }

    $rep  = $this->model->getReporteCompleto($f);
    $info = $rep['info'] ?? [];
    $k    = $rep['kpis'] ?? [];

    // Catálogos
    $ESTATUS = [1=>'Pendiente',2=>'En proceso',3=>'Finalizada'];
    $CALIDAD = [1=>'Pendiente',2=>'En inspección',3=>'Con observaciones (pausa)',4=>'Rechazado',5=>'Liberado'];

    // Helpers
    $capPct = function($v){
        $n = (float)$v;
        if(!is_finite($n)) return 0;
        if($n < 0) return 0;
        if($n > 100) return 100;
        return $n;
    };

    // Normaliza para PDF (por si quieres mostrar texto)
    // Detalle: estatus/calidad texto + cap eficiencia
    $detalle = [];
    foreach(($rep['detalle'] ?? []) as $r){
        $estatusCode = (int)($r['estatus'] ?? 0);
        $calidadCode = (int)($r['calidad'] ?? 0);

        $r['estatus_txt'] = $ESTATUS[$estatusCode] ?? ("Estatus ".$estatusCode);
        $r['calidad_txt'] = $CALIDAD[$calidadCode] ?? ("Calidad ".$calidadCode);
        $r['eficiencia_cap'] = $capPct($r['eficiencia_pct_base'] ?? 0);
        $detalle[] = $r;
    }

    // Resumen: estatus/calidad texto + cap %
    $resumen = [];
    foreach(($rep['resumen'] ?? []) as $r){
        $estatusCode = (int)($r['ultimo_estatus'] ?? 0);
        $calidadCode = (int)($r['ultima_calidad'] ?? 0);

        $r['ultimo_estatus_txt'] = $ESTATUS[$estatusCode] ?? ("Estatus ".$estatusCode);
        $r['ultima_calidad_txt'] = $CALIDAD[$calidadCode] ?? ("Calidad ".$calidadCode);
        $r['eficiencia_cap'] = $capPct($r['eficiencia'] ?? 0);
        $r['pct_en_tiempo_cap'] = $capPct($r['pct_en_tiempo'] ?? 0);
        $resumen[] = $r;
    }

    $data = [
        'filtros' => $f,
        'info' => $info,
        'kpis' => [
            'subot' => (int)($k['subot'] ?? 0),
            'eficiencia_prom' => $capPct($k['eficiencia_prom'] ?? 0),
            'pct_en_tiempo' => $capPct($k['pct_en_tiempo'] ?? 0),
            'rechazos' => (int)($k['rechazos'] ?? 0),
        ],
        'costo_total' => (float)($rep['costo_total']['costo_total_planeacion'] ?? 0),
        'resumen' => $resumen,
        'encargados' => ($rep['encargados'] ?? []),
        'costos_estacion' => ($rep['costos_estacion'] ?? []),
        // si luego quieres anexar detalle completo:
        'detalle' => $detalle,
        'calidad' => ($rep['calidad'] ?? []),
        'costos_detalle' => ($rep['costos_detalle'] ?? []),
    ];

    // LOGO (ruta absoluta recomendada)
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/ldr_negro.png';
    $logoDataUri = '';
    if (is_file($logoPath)) {
        $mime = 'image/png';
        $b64  = base64_encode(file_get_contents($logoPath));
        $logoDataUri = "data:$mime;base64,$b64";
    }
    $data['logo'] = $logoDataUri;

    // Render HTML con output buffering (sin depender de tu getView)
    ob_start();
    $d = $data;
    require __DIR__ . '/../Views/rpt_mrp_planeacion/pdf_kpi_ejecutivo.php';
    $html = ob_get_clean();

    $options = new \Dompdf\Options();

    

 
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->setPaper('A4', 'portrait'); // ejecutivo
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    $filename = 'KPI_Planeacion_'.$f['planeacionid'].'_'.date('Ymd_His').'.pdf';

    if (ob_get_length()) { ob_end_clean(); }
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}





public function exportPdf(){


    ini_set('display_errors', '0');
    error_reporting(E_ALL);
    while (ob_get_level() > 0) { ob_end_clean(); }

    $f = $this->getFilters();
    if (($f['planeacionid'] ?? 0) <= 0) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "Selecciona una Planeación para exportar.";
        exit;
    }

    $rep  = $this->model->getReporteCompleto($f);
    $info = $rep['info'] ?? [];
    $k    = $rep['kpis'] ?? [];

    $ESTATUS = [1=>'Pendiente',2=>'En proceso',3=>'Finalizada'];
    $CALIDAD = [1=>'Pendiente',2=>'En inspección',3=>'Con observaciones (pausa)',4=>'Rechazado',5=>'Liberado'];

    $capPct = function($v){
        $n = (float)$v;
        if(!is_finite($n)) return 0;
        if($n < 0) return 0;
        if($n > 100) return 100;
        return $n;
    };


    $detalle = [];
    foreach(($rep['detalle'] ?? []) as $r){
        $estatusCode = (int)($r['estatus'] ?? 0);
        $calidadCode = (int)($r['calidad'] ?? 0);
        $r['estatus_txt'] = $ESTATUS[$estatusCode] ?? ("Estatus ".$estatusCode);
        $r['calidad_txt'] = $CALIDAD[$calidadCode] ?? ("Calidad ".$calidadCode);
        $r['eficiencia_cap'] = $capPct($r['eficiencia_pct_base'] ?? 0);
        $detalle[] = $r;
    }

    // Normaliza Resumen
    $resumen = [];
    foreach(($rep['resumen'] ?? []) as $r){
        $estatusCode = (int)($r['ultimo_estatus'] ?? 0);
        $calidadCode = (int)($r['ultima_calidad'] ?? 0);
        $r['ultimo_estatus_txt'] = $ESTATUS[$estatusCode] ?? ("Estatus ".$estatusCode);
        $r['ultima_calidad_txt'] = $CALIDAD[$calidadCode] ?? ("Calidad ".$calidadCode);
        $r['eficiencia_cap']     = $capPct($r['eficiencia'] ?? 0);
        $r['pct_en_tiempo_cap']  = $capPct($r['pct_en_tiempo'] ?? 0);
        $resumen[] = $r;
    }

    // KPI capados
    $kpis = [
        'subot' => (int)($k['subot'] ?? 0),
        'eficiencia_prom' => $capPct($k['eficiencia_prom'] ?? 0),
        'pct_en_tiempo' => $capPct($k['pct_en_tiempo'] ?? 0),
        'rechazos' => (int)($k['rechazos'] ?? 0),
        'costo_total' => (float)($rep['costo_total']['costo_total_planeacion'] ?? 0),
    ];

    // Logo
    $logoPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/ldr_negro.png';
    $logoDataUri = '';
    if (is_file($logoPath)) {
        $mime = 'image/png';
        $b64  = base64_encode(file_get_contents($logoPath));
        $logoDataUri = "data:$mime;base64,$b64";
    }

    $data = [
        'filtros' => $f,
        'info' => $info,
        'kpis' => $kpis,
        'logo' => $logoDataUri,
        'resumen' => $resumen,
        'encargados' => ($rep['encargados'] ?? []),
        'calidad' => ($rep['calidad'] ?? []),
        'costos_estacion' => ($rep['costos_estacion'] ?? []),
        'costos_detalle' => ($rep['costos_detalle'] ?? []),
        'detalle' => $detalle,
    ];

    // Render HTML
    ob_start();
    $d = $data;
    require __DIR__ . '/../Views/rpt_mrp_planeacion/pdf_kpi_ejecutivo.php';
    $html = ob_get_clean();



    $options = new \Dompdf\Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    $dompdf = new \Dompdf\Dompdf($options);
    $dompdf->setPaper('A4', 'landscape'); 
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    while (ob_get_level() > 0) { ob_end_clean(); }

    $filename = 'KPI_Planeacion_'.$f['planeacionid'].'_COMPLETO_'.date('Ymd_His').'.pdf';
    $dompdf->stream($filename, ['Attachment' => true]);
    exit;
}


}
?>