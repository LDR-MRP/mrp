<?php
class Inv_reportes extends Controllers
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
        getPermisos(MIREPORTES);
    }

    public function Inv_reportes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Reportes";
        $data['page_title'] = "Reportes";
        $data['page_name'] = "Reportes";
        $data['page_functions_js'] = "functions_inv_reportes.js";
        $this->views->getView($this, "inv_reportes", $data);
    }


    public function qrProductos()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "QR Productos";
        $data['page_title'] = "Generador QR de Productos";
        $data['page_name'] = "qr_productos";
        $data['page_functions_js'] = "functions_inv_reportes.js";

        // 🔹 Cargar líneas
        $data['lineas'] = $this->model->select_all("
        SELECT idlineaproducto, descripcion 
        FROM wms_linea_producto
        ORDER BY descripcion ASC
    ");

        $this->views->getView($this, "qr_productos", $data);
    }


    public function buscarProductoQr()
    {
        if ($_POST) {

            $busqueda = strClean($_POST['busqueda']);
            $arrData = $this->model->buscarProductoQr($busqueda);

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            die();
        }
    }

    public function generarEtiquetasMasivas()
    {
        if ($_POST) {

            $data = [
                'desde' => strClean($_POST['desde']),
                'hasta' => strClean($_POST['hasta']),
                'numEtiquetas' => intval($_POST['numEtiquetas']),
                'linea' => !empty($_POST['linea']) ? intval($_POST['linea']) : ""
            ];


            $productos = $this->model->getProductosEtiquetas($data);

            require_once('./Libraries/tcpdf/tcpdf.php');

            $pdf = new TCPDF();
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor('LDR');
            $pdf->SetTitle('Etiquetas QR');
            $pdf->SetMargins(10, 10, 10);
            $pdf->AddPage();

            foreach ($productos as $producto) {

                for ($i = 0; $i < $data['numEtiquetas']; $i++) {

                    // 🔹 Posición inicial
                    $y = $pdf->GetY();

                    // 🔹 Información
                    $html = "
                <table border='1' cellpadding='4'>
                    <tr>
                        <td width='65%'>
                            <b>Clave del Artículo:</b> {$producto['cve_articulo']}<br>
                            <b>Descripción:</b> {$producto['descripcion']}<br>
                            <b>Línea:</b> {$producto['linea']}
                        </td>
                        <td width='35%' align='center'>
                            &nbsp;
                        </td>
                    </tr>
                </table>
                ";

                    $pdf->writeHTML($html, true, false, true, false, '');

                    // 🔹 Generar QR
                    $style = array(
                        'border' => 0,
                        'padding' => 2,
                        'fgcolor' => array(0, 0, 0),
                        'bgcolor' => false
                    );

                    $pdf->write2DBarcode(
                        $producto['cve_articulo'], // contenido QR
                        'QRCODE,H',
                        150,        // X
                        $y + 5,     // Y
                        35,         // ancho
                        35,         // alto
                        $style,
                        'N'
                    );

                    $pdf->Ln(40);
                }
            }
            ob_end_clean();
            $pdf->Output('etiquetas_qr.pdf', 'I');
            exit;
        }
    }
}
