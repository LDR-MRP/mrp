<?php
class Inv_series extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIASIGNACIONESINV); // 71 = Generar VIN (antes usaba MIPRECIOS=17 por error)
    }

    public function Inv_series()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "VIN";
        $data['page_title'] = "VIN";
        $data['page_name'] = "VIN";
        $data['page_functions_js'] = "functions_inv_series.js";
        $this->views->getView($this, "inv_series", $data);
    }

    public function getSeries()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectSeries();

            for ($i = 0; $i < count($arrData); $i++) {

                if ($arrData[$i]['estado'] == 1) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Disponible</span>';
                } elseif ($arrData[$i]['estado'] == 3) {
                    $arrData[$i]['estado'] = '<span class="badge bg-secondary">Salida</span>';
                } else {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">No disponible</span>';
                }
            }

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getProductos()
    {
        if ($_SESSION['permisosMod']['r']) {

            $modo = $_GET['modo'] ?? 'lote'; // default lote

            if ($modo === "orden") {
                $arrData = $this->model->searchProductosOrden();
            } else {
                $arrData = $this->model->searchProductosLote();
            }

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getAlmacenes()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectAlmacenesSeries();

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function validarSeries()
    {

        $inventarioid = intval($_POST['inventarioid']);
        $almacenid = intval($_POST['almacenid']);
        $prefijo = $_POST['prefijo'];
        $cantidad = intval($_POST['cantidad']);

        $request = $this->model->validarSeries(
            $inventarioid,
            $almacenid,
            $prefijo,
            $cantidad
        );

        echo json_encode($request, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getOrdenesTrabajo()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->searchOrdenesTrabajo();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }


    public function setSeriesConfirmadas()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $lista = $data['lista'];
        $inventarioid = $data['inventarioid'];
        $almacenid = $data['almacenid'];
        $referencia = $data['referencia']; // orden o lote
        $costo = $data['costo'] ?? 0;
        $modo = $data['modo'];

        if ($modo === "orden") {

            // validar orden
            $orden = $this->model->validarOrdenTrabajoPorOrden($referencia);

            if (empty($orden)) {
                echo json_encode([
                    "status" => false,
                    "msg" => "Orden de trabajo inválida."
                ]);
                die();
            }
        } else if ($modo === "lote") {

            // validar lote
            if (empty($referencia)) {
                echo json_encode([
                    "status" => false,
                    "msg" => "El lote es obligatorio."
                ]);
                die();
            }
        }

        $request = $this->model->insertarSeriesConfirmadas(
            $lista,
            $inventarioid,
            $almacenid,
            $referencia,
            $costo,
            $modo, // IMPORTANTE
            $data['id_cat_modelo_vin'] ?? null, // VIN base del glosario (historial)
            $_SESSION['idUser'] ?? null
        );

        echo json_encode($request, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function generarCodigoPDF($vin)
    {
        $vin = strClean($vin);
        $data = $this->model->getSerieByVin($vin);

        if (empty($data)) {
            die("VIN no encontrado");
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        require_once(__DIR__ . '/../Libraries/tcpdf/tcpdf.php');

        $pdf = new TCPDF('L', 'mm', [60, 90], true, 'UTF-8', false);
        $pdf->SetMargins(6, 6, 6);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        $pageWidth  = $pdf->getPageWidth();
        $pageHeight = $pdf->getPageHeight();

        // Marco
        $pdf->Rect(4, 4, $pageWidth - 8, $pageHeight - 8);

        // ------------------ TÍTULO
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'IDENTIFICACION DE VIN', 0, 1, 'L');
        $pdf->Ln(2);

        // ------------------ PRODUCTO
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'Producto: ' . $data['producto'], 0, 1, 'L');
        $pdf->Cell(0, 5, 'Referencia: ' . $data['referencia'], 0, 1, 'L');
        $pdf->Ln(3);

        // ------------------ VIN SUPERIOR
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(0, 6, 'VIN: ' . $data['numero_serie'], 0, 1, 'L');
        $pdf->Ln(2);

        // Guardar Y actual
        $yStartBarcode = $pdf->GetY();

        // ------------------ BARCODE
        // Ancho casi completo de la etiqueta (antes se desperdiciaban ~16mm de espacio)
        // y ancho de barra explicito, para que TCPDF no comprima el modulo del
        // codigo por debajo de un tamano legible por el lector al codificar un VIN de 17 caracteres.
        $barcodeWidth  = $pageWidth - 12;
        $barcodeHeight = 13;
        $barcodeX      = ($pageWidth - $barcodeWidth) / 2;

        $style = [
            'align'    => 'C',
            'text'     => false,
            'hpadding' => 'auto' // zona muda estandar de 10 modulos por lado (antes 2mm, ~6 modulos: bajo norma Code128)
        ];

        $pdf->write1DBarcode(
            $data['numero_serie'],
            'C128',
            $barcodeX,
            $yStartBarcode,
            $barcodeWidth,
            $barcodeHeight,
            0.33, // ancho de barra objetivo en mm; con el ancho de arriba ya no se recorta para un VIN de 17 caracteres
            $style,
            'N'
        );

        // ------------------ VIN INFERIOR
        $yVinInferior = $yStartBarcode + $barcodeHeight + 3;

        // Verificación de seguridad para que no se salga
        if ($yVinInferior + 6 > $pageHeight - 6) {
            $yVinInferior = $pageHeight - 12;
        }

        $pdf->SetY($yVinInferior);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, $data['numero_serie'], 0, 1, 'C');

        $pdf->Output('Codigo_' . $vin . '.pdf', 'I');
        exit;
    }



    public function generarQRPDF($vin)
    {
        $vin = strClean($vin);
        $data = $this->model->getSerieByVin($vin);

        if (empty($data)) {
            die("VIN no encontrado");
        }

        if (ob_get_length()) {
            ob_end_clean();
        }

        require_once(__DIR__ . '/../Libraries/tcpdf/tcpdf.php');

        // Tamaño 80x80 mm
        $pdf = new TCPDF('P', 'mm', [80, 80], true, 'UTF-8', false);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(false);
        $pdf->AddPage();

        // Marco externo
        $pdf->Rect(4, 4, 72, 72);

        // Encabezado
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->Cell(0, 6, 'CODIGO QR DE SERIE', 0, 1, 'C');

        $pdf->Ln(4);

        // VIN
        $pdf->SetFont('helvetica', '', 9);
        $pdf->Cell(0, 5, 'VIN / Serie:', 0, 1, 'C');

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 6, $data['numero_serie'], 0, 1, 'C');

        $pdf->Ln(4);

        // El QR guarda solo el VIN (antes una URL a Inv_series/ver/{vin}, ruta que no existe)
        $qrContenido = $data['numero_serie'];

        // QR centrado
        $pdf->write2DBarcode(
            $qrContenido,
            'QRCODE,H',
            20,
            30,
            40,
            40,
            [
                'border'  => false,
                'padding' => 4, // zona muda de 4 modulos exigida por la norma QR (antes 0)
                'fgcolor' => [0, 0, 0],
                'bgcolor' => [255, 255, 255]
            ],
            'N'
        );

        $pdf->Ln(48);

        // Pie discreto
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, 'Escanee para leer el VIN', 0, 1, 'C');

        $pdf->Output('QR_' . $vin . '.pdf', 'I');
        exit;
    }

    public function generarOrdenPDF($orden)
    {
        $orden = strClean($orden);

        $series = $this->model->getSeriesByOrden($orden);

        if (empty($series)) {
            die("No existen VIN para esta orden");
        }

        // 🔹 limpiar buffer antes del PDF
        if (ob_get_length()) {
            ob_end_clean();
        }

        require_once(__DIR__ . '/../Libraries/tcpdf/tcpdf.php');

        $pdf = new TCPDF('L', 'mm', [60, 90], true, 'UTF-8', false);
        $pdf->SetMargins(6, 6, 6);
        $pdf->SetAutoPageBreak(false);

        foreach ($series as $data) {

            $pdf->AddPage();

            $pageWidth  = $pdf->getPageWidth();
            $pageHeight = $pdf->getPageHeight();

            // marco
            $pdf->Rect(4, 4, $pageWidth - 8, $pageHeight - 8);

            // título
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'IDENTIFICACION DE VIN', 0, 1, 'L');

            $pdf->Ln(2);

            // producto
            $pdf->SetFont('helvetica', '', 9);
            $pdf->Cell(0, 5, 'Producto: ' . $data['producto'], 0, 1, 'L');
            $pdf->Cell(0, 5, 'Referencia: ' . $data['referencia'], 0, 1, 'L');

            $pdf->Ln(3);

            // VIN
            $pdf->SetFont('helvetica', 'B', 13);
            $pdf->Cell(0, 6, 'VIN: ' . $data['numero_serie'], 0, 1, 'L');

            $pdf->Ln(2);

            $yStartBarcode = $pdf->GetY();

            $barcodeWidth  = $pageWidth - 12;
            $barcodeHeight = 13;
            $barcodeX      = ($pageWidth - $barcodeWidth) / 2;

            $style = [
                'align'    => 'C',
                'text'     => false,
                'hpadding' => 'auto' // zona muda estandar de 10 modulos por lado (antes 2mm, ~6 modulos: bajo norma Code128)
            ];

            $pdf->write1DBarcode(
                $data['numero_serie'],
                'C128',
                $barcodeX,
                $yStartBarcode,
                $barcodeWidth,
                $barcodeHeight,
                0.33,
                $style,
                'N'
            );

            $yVinInferior = $yStartBarcode + $barcodeHeight + 3;

            $pdf->SetY($yVinInferior);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, $data['numero_serie'], 0, 1, 'C');
        }

        $pdf->Output('VIN_ORDEN_' . $orden . '.pdf', 'I');
        exit;
    }

    public function getUltimoConsecutivo()
    {
        $baseVin = $_GET['baseVin'] ?? '';

        if (empty($baseVin)) {
            echo json_encode(["status" => false]);
            die();
        }

        $ultimo = $this->model->getUltimoConsecutivo($baseVin);

        echo json_encode([
            "status" => true,
            "ultimo" => $ultimo
        ]);
        die();
    }

    /* =================================================================
     * ASOCIACIÓN VIN <-> NÚMERO DE MOTOR <-> VIN DE ORIGEN
     * ================================================================= */

    // Busca un VIN generado (lector o captura) y regresa su asociación actual
    public function getAsociacion()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['permisosMod']['r'])) {
            echo json_encode(["status" => false, "msg" => "Sin permisos."], JSON_UNESCAPED_UNICODE);
            die();
        }

        $vin = strtoupper(trim($_GET['vin'] ?? ''));
        if ($vin === '') {
            echo json_encode(["status" => false, "msg" => "Capture el VIN."], JSON_UNESCAPED_UNICODE);
            die();
        }

        $data = $this->model->selectSerieAsociacion($vin);
        if (empty($data)) {
            echo json_encode(["status" => false, "msg" => "El VIN $vin no está registrado en los VIN generados."], JSON_UNESCAPED_UNICODE);
            die();
        }

        echo json_encode(["status" => true, "data" => $data], JSON_UNESCAPED_UNICODE);
        die();
    }

    // Colores ya capturados (sugerencias del campo Color)
    public function getColores()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['permisosMod']['r'])) {
            echo json_encode([]);
            die();
        }
        echo json_encode($this->model->selectColores(), JSON_UNESCAPED_UNICODE);
        die();
    }

    // Alta / modificación individual
    public function setAsociacion()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['permisosMod']['w'])) {
            echo json_encode(["status" => false, "msg" => "No tienes permisos para asociar VIN."], JSON_UNESCAPED_UNICODE);
            die();
        }

        $idSerie = intval($_POST['id_numeros_serie'] ?? 0);
        if ($idSerie <= 0) {
            echo json_encode(["status" => false, "msg" => "Seleccione un VIN generado."], JSON_UNESCAPED_UNICODE);
            die();
        }

        $request = $this->model->guardarAsociacion(
            $idSerie,
            $_POST['numero_motor'] ?? '',
            $_POST['vin_origen'] ?? '',
            $_SESSION['idUser'] ?? null,
            'INDIVIDUAL',
            $_POST['color'] ?? ''
        );

        echo json_encode($request, JSON_UNESCAPED_UNICODE);
        die();
    }

    // Historial completo de un VIN (generación + asociación + VIN base)
    public function getHistorial()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['permisosMod']['r'])) {
            echo json_encode([], JSON_UNESCAPED_UNICODE);
            die();
        }

        $id = intval($_GET['id'] ?? 0);
        $historial = new Inv_vin_historialModel();
        echo json_encode($id > 0 ? $historial->selectHistorialVin($id) : [], JSON_UNESCAPED_UNICODE);
        die();
    }

    /* ---------------- carga masiva (Excel) ---------------- */

    // Lotes / órdenes con VIN generados y su avance de asociación (para elegir qué descargar)
    public function getReferenciasAsociacion()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (empty($_SESSION['permisosMod']['r'])) {
            echo json_encode([]);
            die();
        }
        $data = $this->model->select_all(
            "SELECT s.referencia,
                    MAX(s.tipo_generacion) AS tipo_generacion,
                    MAX(mv.modelo) AS modelo_vin,
                    MAX(i.descripcion) AS producto,
                    COUNT(*) AS total,
                    SUM(va.id_asociacion IS NOT NULL) AS asociados,
                    MAX(s.fecha) AS fecha
             FROM wms_numeros_series s
             INNER JOIN wms_inventario i ON i.idinventario = s.inventarioid
             LEFT JOIN cat_modelos_vin mv ON mv.id_cat_modelo_vin = s.id_cat_modelo_vin
             LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = s.id_numeros_serie
             WHERE s.estado != 0
             GROUP BY s.referencia
             ORDER BY MAX(s.fecha) DESC"
        );
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function descargarPlantillaAsociacion()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }

        // ?refs[]=LT004&refs[]=OT...  → precarga solo los VIN de esos lotes / órdenes
        // &pendientes=1                → de esos, solo los que aún no tienen asociación
        // (sin refs y con pendientes=1 se conserva el comportamiento anterior: todos los pendientes)
        $refs = $_GET['refs'] ?? [];
        if (!is_array($refs)) {
            $refs = [$refs];
        }
        $refs = array_values(array_unique(array_filter(array_map(function ($r) {
            return trim((string) $r);
        }, $refs), 'strlen')));
        $soloPendientes = !empty($_GET['pendientes']);

        $pendientes = [];
        if (!empty($refs) || $soloPendientes) {
            $where = "s.estado != 0";
            $params = [];
            if (!empty($refs)) {
                $where .= " AND s.referencia IN (" . implode(',', array_fill(0, count($refs), '?')) . ")";
                $params = $refs;
            }
            if ($soloPendientes) {
                $where .= " AND va.id_asociacion IS NULL";
            }
            $pendientes = $this->model->select_all(
                "SELECT s.numero_serie, s.referencia, i.descripcion AS producto,
                        va.numero_motor, va.vin_origen, va.color
                 FROM wms_numeros_series s
                 INNER JOIN wms_inventario i ON i.idinventario = s.inventarioid
                 LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = s.id_numeros_serie
                 WHERE $where
                 ORDER BY s.referencia, s.numero_serie",
                $params
            );
        }

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ss->getProperties()
            ->setCreator('LDR Solutions · MRP')
            ->setTitle('Plantilla asociación VIN')
            ->setDescription('VIN generado, número de motor, VIN de origen y color');

        /* ---------- Instrucciones ---------- */
        $h1 = $ss->getActiveSheet();
        $h1->setTitle('Instrucciones');
        $h1->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA · ASOCIACIÓN DE VIN');
        $h1->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $textos = [
            '1. Llena la hoja "Asociaciones" sin modificar los encabezados de la fila 1.',
            '2. VIN: número VIN generado en el sistema (17 caracteres). Debe existir en Generar VIN.',
            '3. NUMERO_MOTOR: número de motor de la unidad (hasta 30 caracteres; letras, números, espacio, guion, diagonal y punto).',
            '4. VIN_ORIGEN: VIN del fabricante (17 caracteres, sin las letras I, O ni Q).',
            '5. COLOR: color de la unidad (opcional, ej. Blanco, Negro, Rojo). Si lo dejas vacío se conserva el color que ya tenga.',
            '6. Un número de motor y un VIN de origen solo pueden estar asociados a un VIN generado.',
            '7. Si el VIN ya tenía asociación y los datos son distintos, se ACTUALIZA y el cambio queda en el historial.',
            '8. Las filas con NUMERO_MOTOR y VIN_ORIGEN vacíos se ignoran. Los VIN que ya tenían asociación vienen llenos: si no los cambias, se quedan igual.',
            '9. Al terminar puedes descargar un log en Excel con las filas que no se procesaron y el motivo.',
        ];
        $f = 3;
        foreach ($textos as $t) {
            $h1->setCellValue('A' . $f, $t);
            $f++;
        }
        $f++;
        $h1->setCellValue('A' . $f, 'Ejemplo de una fila (en la hoja "Asociaciones"):');
        $h1->getStyle('A' . $f)->getFont()->setBold(true);
        $f++;
        $h1->setCellValue('A' . $f, 'VIN = 3LD12B2J2VA000123   ·   NUMERO_MOTOR = 4D30-A123456   ·   VIN_ORIGEN = LFV3A28K0A3000001   ·   COLOR = Blanco');
        $h1->getColumnDimension('A')->setWidth(120);

        /* ---------- Asociaciones ---------- */
        $h2 = $ss->createSheet();
        $h2->setTitle('Asociaciones');
        $h2->fromArray(['VIN', 'NUMERO_MOTOR', 'VIN_ORIGEN', 'COLOR', 'REFERENCIA (informativo)', 'PRODUCTO (informativo)'], null, 'A1');
        $h2->getStyle('A1:F1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $h2->getStyle('A1:D1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF2E7D32');
        $h2->getStyle('E1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF9E9E9E');
        $h2->freezePane('A2');

        $ultimaFila = max(500, count($pendientes) + 1);
        // Todo como TEXTO: evita que Excel convierta motores numéricos a notación científica
        $h2->getStyle('A2:D' . $ultimaFila)->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);

        $r = 2;
        foreach ($pendientes as $p) {
            $h2->setCellValueExplicit('A' . $r, $p['numero_serie'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            // los que ya tienen asociación salen llenos: si no se tocan, quedan "sin cambios"
            if (!empty($p['numero_motor'])) {
                $h2->setCellValueExplicit('B' . $r, $p['numero_motor'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $h2->setCellValueExplicit('C' . $r, $p['vin_origen'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            if (!empty($p['color'])) {
                $h2->setCellValueExplicit('D' . $r, $p['color'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
            $h2->setCellValue('E' . $r, $p['referencia']);
            $h2->setCellValue('F' . $r, $p['producto']);
            $r++;
        }

        $h2->getColumnDimension('A')->setWidth(24);
        $h2->getColumnDimension('B')->setWidth(24);
        $h2->getColumnDimension('C')->setWidth(24);
        $h2->getColumnDimension('D')->setWidth(16);
        $h2->getColumnDimension('E')->setWidth(22);
        $h2->getColumnDimension('F')->setWidth(40);
        $ss->setActiveSheetIndex(1);

        if (empty($pendientes)) {
            $filename = 'Plantilla_Asociacion_VIN.xlsx';
        } elseif (count($refs) === 1) {
            $filename = 'Asociacion_VIN_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $refs[0]) . '_' . date('Ymd') . '.xlsx';
        } else {
            $filename = 'Asociacion_VIN_' . date('Ymd_His') . '.xlsx';
        }
        $this->enviarExcel($ss, $filename);
    }

    public function procesarAsociacion()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['permisosMod']['w'])) {
            echo json_encode(['status' => false, 'msg' => 'No tienes permisos para asociar VIN.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => false, 'msg' => 'No se recibió el archivo.'], JSON_UNESCAPED_UNICODE);
            die();
        }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            echo json_encode(['status' => false, 'msg' => 'Formato no válido. Usa .xlsx, .xls o .csv.'], JSON_UNESCAPED_UNICODE);
            die();
        }
        if ($_FILES['archivo']['size'] > 15 * 1024 * 1024) {
            echo json_encode(['status' => false, 'msg' => 'El archivo excede 15 MB.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->leerArchivoAsociacion($_FILES['archivo']['tmp_name']);
        } catch (\Throwable $e) {
            error_log('Inv_series::procesarAsociacion: ' . $e->getMessage());
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo leer el archivo.';
            echo json_encode(['status' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
            die();
        }

        if (empty($rows)) {
            echo json_encode(['status' => false, 'msg' => 'El archivo no contiene asociaciones para procesar.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $mapa = $this->model->mapSeriesAsociacion(array_map(function ($r) {
            return strtoupper(preg_replace('/\s+/', '', $r['vin']));
        }, $rows));

        $log = [];
        $res = ['nuevas' => 0, 'actualizadas' => 0, 'sin_cambios' => 0];
        $vistos = ['vin' => [], 'motor' => [], 'origen' => []];

        foreach ($rows as $r) {
            $fila = $r['_fila'];
            $vin = strtoupper(preg_replace('/\s+/', '', $r['vin']));
            $motor = Inv_seriesModel::normalizarMotor($r['motor']);
            $origen = Inv_seriesModel::normalizarVinOrigen($r['origen']);
            $color = Inv_seriesModel::normalizarColor($r['color'] ?? '');
            $logBase = ['fila' => $fila, 'vin' => $vin, 'motor' => $motor, 'origen' => $origen, 'color' => $color];

            if ($motor === '' && $origen === '') {
                continue; // fila de la plantilla de pendientes sin llenar
            }
            if ($vin === '') {
                $log[] = $logBase + ['motivo' => 'Fila sin VIN.'];
                continue;
            }
            if (!isset($mapa[$vin])) {
                $log[] = $logBase + ['motivo' => 'El VIN no está registrado en los VIN generados.'];
                continue;
            }
            $error = Inv_seriesModel::validarDatosAsociacion($motor, $origen) ?? Inv_seriesModel::validarColor($color);
            if ($error) {
                $log[] = $logBase + ['motivo' => $error];
                continue;
            }
            if (isset($vistos['vin'][$vin])) {
                $log[] = $logBase + ['motivo' => 'VIN repetido en el archivo (ya viene en la fila ' . $vistos['vin'][$vin] . ').'];
                continue;
            }
            if (isset($vistos['motor'][$motor])) {
                $log[] = $logBase + ['motivo' => 'Número de motor repetido en el archivo (fila ' . $vistos['motor'][$motor] . ').'];
                continue;
            }
            if (isset($vistos['origen'][$origen])) {
                $log[] = $logBase + ['motivo' => 'VIN de origen repetido en el archivo (fila ' . $vistos['origen'][$origen] . ').'];
                continue;
            }
            $vistos['vin'][$vin] = $fila;
            $vistos['motor'][$motor] = $fila;
            $vistos['origen'][$origen] = $fila;

            $r2 = $this->model->guardarAsociacion(
                (int) $mapa[$vin]['id_numeros_serie'],
                $motor,
                $origen,
                $_SESSION['idUser'] ?? null,
                'MASIVO',
                $color
            );

            if (!$r2['status']) {
                $log[] = $logBase + ['motivo' => $r2['msg']];
                continue;
            }
            $tipo = $r2['tipo'] ?? 'nueva';
            if ($tipo === 'nueva') $res['nuevas']++;
            elseif ($tipo === 'actualizada') $res['actualizadas']++;
            else $res['sin_cambios']++;
        }

        $_SESSION['cargaAsociacionVinLog'] = $log;

        $procesadas = $res['nuevas'] + $res['actualizadas'];
        echo json_encode([
            'status' => true,
            'msg' => "$procesadas asociación(es) guardada(s).",
            'nuevas' => $res['nuevas'],
            'actualizadas' => $res['actualizadas'],
            'sin_cambios' => $res['sin_cambios'],
            'omitidas' => count($log),
            'log' => array_slice($log, 0, 50),
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function exportarLogAsociacion()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $log = $_SESSION['cargaAsociacionVinLog'] ?? [];

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Log');
        $sh->setCellValue('A1', 'Log de asociaciones de VIN NO registradas (carga masiva)');
        $sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
        $sh->fromArray(['FILA_ARCHIVO', 'VIN', 'NUMERO_MOTOR', 'VIN_ORIGEN', 'COLOR', 'MOTIVO'], null, 'A4');
        $sh->getStyle('A4:F4')->getFont()->setBold(true);
        $sh->getStyle('A4:F4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEFEFEF');
        $sh->freezePane('A5');

        $r = 5;
        $txt = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
        foreach ($log as $l) {
            $sh->setCellValue('A' . $r, $l['fila']);
            $sh->setCellValueExplicit('B' . $r, (string) $l['vin'], $txt);
            $sh->setCellValueExplicit('C' . $r, (string) $l['motor'], $txt);
            $sh->setCellValueExplicit('D' . $r, (string) $l['origen'], $txt);
            $sh->setCellValueExplicit('E' . $r, (string) ($l['color'] ?? ''), $txt);
            $sh->setCellValueExplicit('F' . $r, (string) $l['motivo'], $txt);
            $r++;
        }
        foreach (['A', 'B', 'C', 'D', 'E'] as $c) {
            $sh->getColumnDimension($c)->setWidth(22);
        }
        $sh->getColumnDimension('F')->setWidth(70);

        $this->enviarExcel($ss, 'Log_Asociacion_VIN_' . date('Ymd_His') . '.xlsx');
    }

    /* =================================================================
     * UNIDADES IMPORTADAS (sin VIN nacional: VIN = VIN de origen)
     * Carga masiva: SKU, VIN de origen, número de motor, color y almacén.
     * Cada fila da de alta el VIN (tipo 'importado'), su asociación, la
     * entrada al inventario y el historial. Ver Inv_seriesModel::altaUnidadImportada.
     * ================================================================= */

    public function descargarPlantillaImportados()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $txt = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
        $productos = array_values($this->model->mapProductosUnidad());
        $almacenes = array_values($this->model->mapAlmacenesActivos());

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ss->getProperties()->setCreator('LDR Solutions · MRP')->setTitle('Plantilla unidades importadas');

        /* ---------- Instrucciones ---------- */
        $h1 = $ss->getActiveSheet();
        $h1->setTitle('Instrucciones');
        $h1->setCellValue('A1', 'PLANTILLA DE CARGA MASIVA · UNIDADES IMPORTADAS (SIN VIN NACIONAL)');
        $h1->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $textos = [
            '1. Usa esta plantilla solo para unidades importadas que NO llevan VIN nacional: su VIN es el VIN de origen del fabricante.',
            '   Las unidades con VIN nacional (3LD...) se generan en "Generar nuevos VIN" y se asocian con "Carga masiva".',
            '2. Llena la hoja "Importados" sin modificar los encabezados de la fila 1. Una fila por unidad.',
            '3. SKU: elige el producto de la lista; se muestra como "SKU | Nombre" (hoja "Productos"). También puedes escribir o pegar solo el SKU.',
            '4. VIN_ORIGEN: VIN del fabricante, 17 caracteres, sin las letras I, O ni Q. Será el VIN de la unidad en el sistema.',
            '5. NUMERO_MOTOR: número de motor (hasta 30 caracteres; letras, números, espacio, guion, diagonal y punto).',
            '6. COLOR: opcional (ej. Blanco, Gris).',
            '7. ALMACEN: clave del almacén donde está la unidad (elígela de la lista; viene de la hoja "Almacenes").',
            '8. Cada fila crea el VIN, su asociación (motor, VIN de origen, color) y la entrada al inventario (concepto Ajustes) en ese almacén.',
            '9. Se omiten las filas cuyo VIN de origen o número de motor ya existan en el sistema o vengan repetidos en el archivo.',
            '10. Al terminar puedes descargar un log en Excel con las filas que no se procesaron y el motivo.',
        ];
        $f = 3;
        foreach ($textos as $t) {
            $h1->setCellValue('A' . $f++, $t);
        }
        $h1->getColumnDimension('A')->setWidth(130);

        /* ---------- Importados ---------- */
        $h2 = $ss->createSheet();
        $h2->setTitle('Importados');
        $h2->fromArray(['SKU', 'VIN_ORIGEN', 'NUMERO_MOTOR', 'COLOR', 'ALMACEN'], null, 'A1');
        $h2->getStyle('A1:E1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $h2->getStyle('A1:E1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0277BD');
        $h2->freezePane('A2');
        $ultimaFila = 1000;
        $h2->getStyle('A2:E' . $ultimaFila)->getNumberFormat()
            ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_TEXT);
        foreach (['A' => 55, 'B' => 24, 'C' => 22, 'D' => 14, 'E' => 18] as $c => $w) {
            $h2->getColumnDimension($c)->setWidth($w);
        }

        /* ---------- Catálogos ---------- */
        $h3 = $ss->createSheet();
        $h3->setTitle('Productos');
        // Columna C = "SKU | Nombre": es la que alimenta la lista de la hoja Importados,
        // para que al elegir se vea el nombre; al procesar se toma solo el SKU.
        $h3->fromArray(['SKU', 'DESCRIPCION', 'SKU | NOMBRE (lista)'], null, 'A1');
        $h3->getStyle('A1:C1')->getFont()->setBold(true);
        $r = 2;
        foreach ($productos as $p) {
            $h3->setCellValueExplicit('A' . $r, $p['cve_articulo'], $txt);
            $h3->setCellValue('B' . $r, $p['descripcion']);
            $h3->setCellValueExplicit('C' . $r, $p['cve_articulo'] . ' | ' . $p['descripcion'], $txt);
            $r++;
        }
        $h3->getColumnDimension('A')->setWidth(32);
        $h3->getColumnDimension('B')->setWidth(45);
        $h3->getColumnDimension('C')->setWidth(70);
        $ultProd = max(2, $r - 1);

        $h4 = $ss->createSheet();
        $h4->setTitle('Almacenes');
        $h4->fromArray(['ALMACEN', 'DESCRIPCION'], null, 'A1');
        $h4->getStyle('A1:B1')->getFont()->setBold(true);
        $r = 2;
        foreach ($almacenes as $a) {
            $h4->setCellValueExplicit('A' . $r, $a['cve_almacen'], $txt);
            $h4->setCellValue('B' . $r, $a['descripcion']);
            $r++;
        }
        $h4->getColumnDimension('A')->setWidth(20);
        $h4->getColumnDimension('B')->setWidth(55);
        $ultAlm = max(2, $r - 1);

        // Listas desplegables (SKU y ALMACEN). En PhpSpreadsheet showDropDown=true
        // es lo que SÍ muestra la flecha en Excel (el atributo XML está invertido).
        foreach ([['A', "Productos!\$C\$2:\$C\$$ultProd"], ['E', "Almacenes!\$A\$2:\$A\$$ultAlm"]] as [$col, $formula]) {
            // objeto nuevo (no getCell()->getDataValidation(), que registraría además
            // una validación suelta en la fila 2 encimada con la del rango)
            $dv = new \PhpOffice\PhpSpreadsheet\Cell\DataValidation();
            $dv->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $dv->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
            $dv->setAllowBlank(true);
            $dv->setShowErrorMessage(true);
            $dv->setShowDropDown(true);
            $dv->setFormula1($formula);
            $h2->setDataValidation($col . '2:' . $col . $ultimaFila, $dv);
        }

        $ss->setActiveSheetIndex(1);
        $this->enviarExcel($ss, 'Plantilla_Unidades_Importadas.xlsx');
    }

    public function procesarImportados()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_SESSION['permisosMod']['w'])) {
            echo json_encode(['status' => false, 'msg' => 'No tienes permisos para dar de alta unidades.'], JSON_UNESCAPED_UNICODE);
            die();
        }
        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['status' => false, 'msg' => 'No se recibió el archivo.'], JSON_UNESCAPED_UNICODE);
            die();
        }
        $ext = strtolower(pathinfo($_FILES['archivo']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            echo json_encode(['status' => false, 'msg' => 'Formato no válido. Usa .xlsx, .xls o .csv.'], JSON_UNESCAPED_UNICODE);
            die();
        }
        if ($_FILES['archivo']['size'] > 15 * 1024 * 1024) {
            echo json_encode(['status' => false, 'msg' => 'El archivo excede 15 MB.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        try {
            $rows = $this->leerArchivoImportados($_FILES['archivo']['tmp_name']);
        } catch (\Throwable $e) {
            error_log('Inv_series::procesarImportados: ' . $e->getMessage());
            $msg = $e instanceof \RuntimeException ? $e->getMessage() : 'No se pudo leer el archivo.';
            echo json_encode(['status' => false, 'msg' => $msg], JSON_UNESCAPED_UNICODE);
            die();
        }
        if (empty($rows)) {
            echo json_encode(['status' => false, 'msg' => 'El archivo no contiene unidades para procesar.'], JSON_UNESCAPED_UNICODE);
            die();
        }

        $productos = $this->model->mapProductosUnidad();
        $almacenes = $this->model->mapAlmacenesActivos();
        foreach ($rows as &$r) {
            $r['vin'] = Inv_seriesModel::normalizarVinOrigen($r['origen']);
            $r['motor'] = Inv_seriesModel::normalizarMotor($r['motor']);
            $r['color'] = Inv_seriesModel::normalizarColor($r['color']);
            // la lista de la plantilla trae "SKU | Nombre": solo cuenta lo que va antes del |
            $r['sku'] = strtoupper(trim(explode('|', $r['sku'])[0]));
            $r['cve_almacen'] = strtoupper(trim($r['almacen']));
        }
        unset($r);
        $existentes = $this->model->mapExistentesImportados(array_column($rows, 'vin'), array_column($rows, 'motor'));

        $referencia = 'IMP-' . date('Ymd-His');
        $log = [];
        $altas = 0;
        $vistos = ['vin' => [], 'motor' => []];

        foreach ($rows as $r) {
            $fila = $r['_fila'];
            $vin = $r['vin'];
            $logBase = ['fila' => $fila, 'sku' => $r['sku'], 'vin' => $vin, 'motor' => $r['motor'], 'color' => $r['color'], 'almacen' => $r['cve_almacen']];

            if (!isset($productos[$r['sku']])) {
                $log[] = $logBase + ['motivo' => $r['sku'] === '' ? 'Fila sin SKU.' : 'El SKU no existe como producto terminado activo.'];
                continue;
            }
            if (!isset($almacenes[$r['cve_almacen']])) {
                $log[] = $logBase + ['motivo' => $r['cve_almacen'] === '' ? 'Fila sin almacén.' : 'El almacén no existe o no está activo.'];
                continue;
            }
            $error = Inv_seriesModel::validarDatosAsociacion($r['motor'], $vin) ?? Inv_seriesModel::validarColor($r['color']);
            if ($error) {
                $log[] = $logBase + ['motivo' => $error];
                continue;
            }
            if (isset($vistos['vin'][$vin])) {
                $log[] = $logBase + ['motivo' => 'VIN de origen repetido en el archivo (fila ' . $vistos['vin'][$vin] . ').'];
                continue;
            }
            if (isset($vistos['motor'][$r['motor']])) {
                $log[] = $logBase + ['motivo' => 'Número de motor repetido en el archivo (fila ' . $vistos['motor'][$r['motor']] . ').'];
                continue;
            }
            if (isset($existentes['vin'][$vin])) {
                $log[] = $logBase + ['motivo' => 'El VIN ya está registrado en el sistema.'];
                continue;
            }
            if (isset($existentes['origen'][$vin])) {
                $log[] = $logBase + ['motivo' => 'El VIN de origen ya está asociado al VIN ' . $existentes['origen'][$vin] . '.'];
                continue;
            }
            if (isset($existentes['motor'][$r['motor']])) {
                $log[] = $logBase + ['motivo' => 'El número de motor ya está asociado al VIN ' . $existentes['motor'][$r['motor']] . '.'];
                continue;
            }
            $vistos['vin'][$vin] = $fila;
            $vistos['motor'][$r['motor']] = $fila;

            $res = $this->model->altaUnidadImportada([
                'inventarioid' => (int) $productos[$r['sku']]['idinventario'],
                'almacenid' => (int) $almacenes[$r['cve_almacen']]['idalmacen'],
                'cve_almacen' => $almacenes[$r['cve_almacen']]['cve_almacen'],
                'vin' => $vin,
                'motor' => $r['motor'],
                'color' => $r['color'],
                '_fila' => $fila,
            ], $referencia, $_SESSION['idUser'] ?? null, 'MASIVO');

            if (!$res['status']) {
                $log[] = $logBase + ['motivo' => $res['msg']];
                continue;
            }
            $altas++;
        }

        $_SESSION['cargaImportadosLog'] = $log;

        echo json_encode([
            'status' => true,
            'msg' => "$altas unidad(es) importada(s) registrada(s).",
            'altas' => $altas,
            'omitidas' => count($log),
            'referencia' => $referencia,
            'log' => array_slice($log, 0, 50),
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    public function exportarLogImportados()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $log = $_SESSION['cargaImportadosLog'] ?? [];
        $txt = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Log');
        $sh->setCellValue('A1', 'Unidades importadas NO registradas (carga masiva)');
        $sh->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sh->setCellValue('A2', 'Generado: ' . date('Y-m-d H:i:s'));
        $sh->fromArray(['FILA_ARCHIVO', 'SKU', 'VIN_ORIGEN', 'NUMERO_MOTOR', 'COLOR', 'ALMACEN', 'MOTIVO'], null, 'A4');
        $sh->getStyle('A4:G4')->getFont()->setBold(true);
        $sh->getStyle('A4:G4')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFEFEFEF');
        $sh->freezePane('A5');
        $r = 5;
        foreach ($log as $l) {
            $sh->setCellValue('A' . $r, $l['fila']);
            $sh->setCellValueExplicit('B' . $r, (string) $l['sku'], $txt);
            $sh->setCellValueExplicit('C' . $r, (string) $l['vin'], $txt);
            $sh->setCellValueExplicit('D' . $r, (string) $l['motor'], $txt);
            $sh->setCellValueExplicit('E' . $r, (string) $l['color'], $txt);
            $sh->setCellValueExplicit('F' . $r, (string) $l['almacen'], $txt);
            $sh->setCellValueExplicit('G' . $r, (string) $l['motivo'], $txt);
            $r++;
        }
        foreach (['A' => 14, 'B' => 28, 'C' => 22, 'D' => 20, 'E' => 12, 'F' => 16, 'G' => 70] as $c => $w) {
            $sh->getColumnDimension($c)->setWidth($w);
        }
        $this->enviarExcel($ss, 'Log_Unidades_Importadas_' . date('Ymd_His') . '.xlsx');
    }

    /** Listado de unidades importadas: VIN (= VIN de origen), motor, color, SKU y almacén. */
    public function exportarImportados()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $rows = $this->model->selectImportados();
        $txt = \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING;
        $estados = ['1' => 'Disponible', '2' => 'En inventario', '3' => 'Salida'];

        $ss = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sh = $ss->getActiveSheet();
        $sh->setTitle('Importadas');
        $sh->fromArray(['VIN / VIN DE ORIGEN', 'NUMERO_MOTOR', 'COLOR', 'SKU', 'PRODUCTO', 'ALMACEN', 'DESCRIPCION ALMACEN', 'REFERENCIA', 'ESTADO', 'FECHA'], null, 'A1');
        $sh->getStyle('A1:J1')->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $sh->getStyle('A1:J1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FF0277BD');
        $sh->freezePane('A2');
        $r = 2;
        foreach ($rows as $u) {
            $sh->setCellValueExplicit('A' . $r, (string) $u['numero_serie'], $txt);
            $sh->setCellValueExplicit('B' . $r, (string) $u['numero_motor'], $txt);
            $sh->setCellValue('C' . $r, $u['color']);
            $sh->setCellValueExplicit('D' . $r, (string) $u['cve_articulo'], $txt);
            $sh->setCellValue('E' . $r, $u['producto']);
            $sh->setCellValueExplicit('F' . $r, (string) $u['cve_almacen'], $txt);
            $sh->setCellValue('G' . $r, $u['almacen']);
            $sh->setCellValue('H' . $r, $u['referencia']);
            $sh->setCellValue('I' . $r, $estados[(string) $u['estado']] ?? $u['estado']);
            $sh->setCellValue('J' . $r, $u['fecha']);
            $r++;
        }
        foreach (['A' => 24, 'B' => 20, 'C' => 12, 'D' => 28, 'E' => 34, 'F' => 14, 'G' => 40, 'H' => 34, 'I' => 14, 'J' => 12] as $c => $w) {
            $sh->getColumnDimension($c)->setWidth($w);
        }
        $sh->setAutoFilter('A1:J' . max(1, $r - 1));
        $this->enviarExcel($ss, 'Unidades_Importadas_' . date('Ymd') . '.xlsx');
    }

    /** Lee la hoja "Importados" (o la primera) mapeando columnas por encabezado. */
    private function leerArchivoImportados(string $tmpName): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
        $sheet = $spreadsheet->sheetNameExists('Importados')
            ? $spreadsheet->getSheetByName('Importados')
            : $spreadsheet->getSheet(0);

        $highestRow = $sheet->getHighestDataRow();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $mapa = [
            'SKU' => 'sku',
            'CLAVE_ARTICULO' => 'sku',
            'VIN_ORIGEN' => 'origen',
            'VIN_DE_ORIGEN' => 'origen',
            'COD_ORIGEN' => 'origen',
            'COD_ORIGEN*' => 'origen',
            'VIN' => 'origen',
            'NUMERO_MOTOR' => 'motor',
            'NUMERO_DE_MOTOR' => 'motor',
            'MOTOR' => 'motor',
            'EIN' => 'motor',
            'COLOR' => 'color',
            'ALMACEN' => 'almacen',
        ];

        $columnas = [];
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
            $clave = strtoupper(strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']));
            $clave = preg_replace('/\s+/', '_', $clave);
            // la primera columna que coincida gana (VIN_ORIGEN antes que VIN)
            if (isset($mapa[$clave]) && !in_array($mapa[$clave], $columnas, true)) {
                $columnas[$col] = $mapa[$clave];
            }
        }
        foreach (['sku', 'origen', 'motor', 'almacen'] as $req) {
            if (!in_array($req, $columnas, true)) {
                throw new \RuntimeException('No se encontraron los encabezados SKU, VIN_ORIGEN, NUMERO_MOTOR y ALMACEN. Usa la plantilla proporcionada.');
            }
        }

        $rows = [];
        for ($fila = 2; $fila <= $highestRow; $fila++) {
            $reg = ['sku' => '', 'origen' => '', 'motor' => '', 'color' => '', 'almacen' => ''];
            foreach ($columnas as $col => $campo) {
                $reg[$campo] = $this->valorComoTexto($sheet->getCellByColumnAndRow($col, $fila)->getValue());
            }
            if (implode('', $reg) === '') {
                continue;
            }
            $reg['_fila'] = $fila;
            $rows[] = $reg;
        }
        return $rows;
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

    /** Lee la hoja "Asociaciones" (o la primera) mapeando columnas por encabezado. */
    private function leerArchivoAsociacion(string $tmpName): array
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($tmpName);
        $sheet = $spreadsheet->sheetNameExists('Asociaciones')
            ? $spreadsheet->getSheetByName('Asociaciones')
            : $spreadsheet->getSheet(0);

        $highestRow = $sheet->getHighestDataRow();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

        $mapa = [
            'VIN' => 'vin',
            'NUMERO_MOTOR' => 'motor',
            'NUMERO_DE_MOTOR' => 'motor',
            'MOTOR' => 'motor',
            'VIN_ORIGEN' => 'origen',
            'VIN_DE_ORIGEN' => 'origen',
            'COLOR' => 'color',
        ];

        $columnas = [];
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $texto = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
            $clave = strtoupper(strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U']));
            $clave = preg_replace('/\s+/', '_', $clave);
            if (isset($mapa[$clave]) && !in_array($mapa[$clave], $columnas, true)) {
                $columnas[$col] = $mapa[$clave];
            }
        }

        foreach (['vin', 'motor', 'origen'] as $req) {
            if (!in_array($req, $columnas, true)) {
                throw new \RuntimeException('No se encontraron los encabezados VIN, NUMERO_MOTOR y VIN_ORIGEN. Usa la plantilla proporcionada.');
            }
        }

        $rows = [];
        for ($fila = 2; $fila <= $highestRow; $fila++) {
            $reg = ['vin' => '', 'motor' => '', 'origen' => '', 'color' => ''];
            foreach ($columnas as $col => $campo) {
                $reg[$campo] = $this->valorComoTexto($sheet->getCellByColumnAndRow($col, $fila)->getValue());
            }
            if ($reg['vin'] === '' && $reg['motor'] === '' && $reg['origen'] === '') {
                continue;
            }
            $reg['_fila'] = $fila;
            $rows[] = $reg;
        }
        return $rows;
    }

    private function valorComoTexto($valor): string
    {
        if ($valor === null) return '';
        if ($valor instanceof \PhpOffice\PhpSpreadsheet\RichText\RichText) return trim($valor->getPlainText());
        if (is_string($valor)) return trim($valor);
        if (is_int($valor)) return (string) $valor;
        if (is_float($valor)) {
            if (fmod($valor, 1.0) === 0.0 && abs($valor) < 1e15) return sprintf('%.0f', $valor);
            return rtrim(rtrim(sprintf('%.10f', $valor), '0'), '.');
        }
        return trim((string) $valor);
    }
}
