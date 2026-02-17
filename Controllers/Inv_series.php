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
        getPermisos(MIPRECIOS);
    }

    public function Inv_series()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Series";
        $data['page_title'] = "Series";
        $data['page_name'] = "series";
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
                } else {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">No disponible</span>';
                }
            }

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setSeries()
    {
        if ($_POST) {

            if (empty($_POST['inventarioid']) || empty($_POST['almacenid']) || empty($_POST['prefijo']) || empty($_POST['cantidad'])) {
                $arrResponse = array("status" => false, "msg" => "Datos incorrectos");
            } else {

                $inventarioid = intval($_POST['inventarioid']);
                $almacenid    = intval($_POST['almacenid']);
                $prefijo      = strClean($_POST['prefijo']);
                $cantidad     = intval($_POST['cantidad']);
                $costo        = floatval($_POST['costo']);
                $referencia   = strClean($_POST['referencia']);

                $request = $this->model->generarSeries(
                    $inventarioid,
                    $almacenid,
                    $prefijo,
                    $cantidad,
                    $costo,
                    $referencia
                );

                if ($request["status"] === false) {

                    $arrResponse = array(
                        "status" => false,
                        "msg" => $request["msg"]
                    );
                } else {

                    $insertados = $request["insertados"];
                    $duplicados = count($request["duplicados"]);

                    if ($duplicados > 0) {

                        $arrResponse = array(
                            "status" => true,
                            "msg" => "Insertados: $insertados | Duplicados: $duplicados"
                        );
                    } else {

                        $arrResponse = array(
                            "status" => true,
                            "msg" => "Series generadas correctamente ($insertados registros)"
                        );
                    }
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function getProductos()
    {
        if ($_SESSION['permisosMod']['r']) {

            $term = strClean($_GET['term'] ?? '');

            $arrData = $this->model->searchProductos($term);

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

    public function setSeriesConfirmadas()
    {
        $data = json_decode(file_get_contents("php://input"), true);

        $lista = $data['lista'];
        $inventarioid = $data['inventarioid'];
        $almacenid = $data['almacenid'];
        $referencia = $data['referencia'];
        $costo = $data['costo'];

        $request = $this->model->insertarSeriesConfirmadas(
            $lista,
            $inventarioid,
            $almacenid,
            $referencia,
            $costo
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
    $pdf->Cell(0, 6, 'IDENTIFICACION DE SERIE', 0, 1, 'L');
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
    $barcodeWidth  = $pageWidth - 28;
    $barcodeHeight = 12;
    $barcodeX      = ($pageWidth - $barcodeWidth) / 2;

    $style = [
        'align' => 'C',
        'text'  => false
    ];

    $pdf->write1DBarcode(
        $data['numero_serie'],
        'C128',
        $barcodeX,
        $yStartBarcode,
        $barcodeWidth,
        $barcodeHeight,
        0.4,
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

        $qrUrl = base_url() . "/Inv_series/ver/" . $data['numero_serie'];

        // QR centrado
        $pdf->write2DBarcode(
            $qrUrl,
            'QRCODE,H',
            20,
            30,
            40,
            40
        );

        $pdf->Ln(48);

        // Pie discreto
        $pdf->SetFont('helvetica', '', 7);
        $pdf->Cell(0, 4, 'Escanee para consultar la informacion del producto', 0, 1, 'C');

        $pdf->Output('QR_' . $vin . '.pdf', 'I');
        exit;
    }
}
