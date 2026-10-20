<?php
class Inv_multialmacenes extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MIMULTIALMACENES);
    }

    public function Inv_multialmacenes()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Multialmacenes";
        $data['page_title'] = "Multialmacenes";
        $data['page_name'] = "multialmacenes";
        $data['page_functions_js'] = "functions_inv_multialmacenes.js";
        $this->views->getView($this, "inv_multialmacenes", $data);
    }

    //CAPTURAR UN NUEVO MULTIALMACEN
    public function setMultialmacen()
    {
        if ($_POST) {

            if (
                empty($_POST['listInventario']) ||
                empty($_POST['listAlmacenes'])
            ) {
                $arrResponse = ['status' => false, 'msg' => 'Datos incorrectos.'];
            } else {

                $idmultialmacen = intval($_POST['idmultialmacen']);
                $inventarioid  = intval($_POST['listInventario']);
                $almacenid     = intval($_POST['listAlmacenes']);
                $existencia    = floatval($_POST['existencia-input']);
                $stockmin      = floatval($_POST['stockminimo-input']);
                $stockmax      = floatval($_POST['stockmaximo-input']);

                if ($idmultialmacen == 0) {

                    if ($_SESSION['permisosMod']['w']) {
                        $request = $this->model->insertMultialmacen($inventarioid, $almacenid, $existencia, $stockmin, $stockmax);
                        $tipo = "insert";
                    }
                } else {

                    if ($_SESSION['permisosMod']['u']) {
                        $request = $this->model->updateMultialmacen(
                            $idmultialmacen,
                            $inventarioid,
                            $almacenid,
                            $existencia,
                            $stockmin,
                            $stockmax
                        );
                        $tipo = "update";
                    }
                }

                if ($request === "exist") {

                    $arrResponse = [
                        'status' => false,
                        'msg' => 'Ya existe ese inventario en ese almacén'
                    ];
                } else if ($request > 0) {

                    $arrResponse = [
                        'status' => true,
                        'msg' => $tipo == 'insert'
                            ? 'Registro creado correctamente'
                            : 'Registro actualizado correctamente'
                    ];
                } else {

                    $arrResponse = [
                        'status' => false,
                        'msg' => 'No se pudo guardar'
                    ];
                }
            }

            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Buscar inventarios para el autocomplete
    public function searchInventarios()
    {
        if ($_GET) {
            $term = strClean($_GET['term']);
            $arrData = $this->model->searchInventarios($term);

            $data = [];
            foreach ($arrData as $row) {
                $data[] = [
                    "id" => $row['idinventario'],
                    "label" => $row['cve_articulo'] . " - " . $row['descripcion'],
                    "value" => $row['cve_articulo'] . " - " . $row['descripcion']
                ];
            }

            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getSelectInventariosJson()
    {
        $arrData = $this->model->selectInventariosPC_H();
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }


    // Traer todos los multialmacenes
    public function getmultialmacenes()
    {
        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectMultialmacenes();
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Traer un multialmacen
    public function getMultialmacen($id)
    {
        if ($_SESSION['permisosMod']['r']) {
            $id = intval($id);
            $arrData = $this->model->selectMultialmacen($id);
            if (empty($arrData)) {
                $arrResponse = ['status' => false, 'msg' => 'Datos no encontrados.'];
            } else {
                $arrResponse = ['status' => true, 'data' => $arrData];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Eliminar
    public function delmultiAlmacen()
    {
        if ($_POST) {
            $id = intval($_POST['idmultialmacen']);
            $request = $this->model->deleteMultialmacen($id);
            if ($request) {
                $arrResponse = ['status' => true, 'msg' => 'Registro eliminado correctamente.'];
            } else {
                $arrResponse = ['status' => false, 'msg' => 'Error al eliminar.'];
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Select inventarios solo P,C,H
    // Select inventarios solo P,C,H
    public function getSelectInventarios()
    {
        $htmlOptions = '<option value="">--Seleccione Inventario--</option>';
        $arrData = $this->model->selectInventariosPC_H(); // tu model ya filtra P,C,H
        foreach ($arrData as $inv) {
            $htmlOptions .= '<option value="' . $inv['idinventario'] . '">' . $inv['cve_articulo'] . ' - ' . $inv['descripcion'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }


    // Select almacenes
    public function getSelectAlmacenes()
    {
        $htmlOptions = '<option value="">--Seleccione Almacén--</option>';
        $arrData = $this->model->selectOptionAlmacenes();
        foreach ($arrData as $alm) {
            $htmlOptions .= '<option value="' . $alm['idalmacen'] . '">' . $alm['descripcion'] . '</option>';
        }
        echo $htmlOptions;
        die();
    }

    public function buscarExistencias()
{
    $q = $_GET['q'] ?? '';
    $arrData = $this->model->buscarExistencias($q);
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
}

public function updateStock()
{
    $id = intval($_POST['id']);
    $min = floatval($_POST['stock_min']);
    $max = floatval($_POST['stock_max']);

    $resp = $this->model->updateStockMinMax($id, $min, $max);

    echo json_encode([
        "status" => $resp,
        "msg" => $resp ? "Stock actualizado" : "Error al guardar"
    ], JSON_UNESCAPED_UNICODE);
    die();
}

public function buscarArticulos()
{
    $q = $_GET['q'] ?? '';

    if ($q == '') {
        echo json_encode([]);
        die();
    }

    $arrData = $this->model->searchInventarios($q);
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
}



    /* =================================================================
     * MATRIZ DE EXISTENCIAS (articulo x almacen)
     * ================================================================= */

    private const TIPOS_MATRIZ = [
        'P' => 'Producto',
        'C' => 'Componente',
        'K' => 'Kit',
        'H' => 'Herramienta',
        'R' => 'Refacción',
        'S' => 'Servicio',
    ];

    // Columnas de almacen para armar el encabezado de la matriz
    public function getAlmacenesMatriz()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        echo json_encode([
            'almacenes' => $this->model->selectAlmacenesMatriz(),
            'tipos'     => self::TIPOS_MATRIZ,
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    /** Filtros comunes de la matriz (tabla y exportacion), saneados. */
    private function filtrosMatriz(array $req): array
    {
        $search = mb_substr(trim((string)($req['search']['value'] ?? $req['q'] ?? '')), 0, 100);

        $tipo = (string)($req['filtro_tipo'] ?? '');
        if (!isset(self::TIPOS_MATRIZ[$tipo])) {
            $tipo = '';
        }

        // Alcance: almacenes de la planta elegida (o todos)
        $planta = strtoupper((string)($req['filtro_planta'] ?? ''));
        $almacenes = [];
        foreach ($this->model->selectAlmacenesMatriz() as $a) {
            if ($planta === '' || $a['planta'] === $planta) {
                $almacenes[] = (int)$a['idalmacen'];
            }
        }

        return [
            'search'          => $search,
            'tipo'            => $tipo,
            'solo_existencia' => !empty($req['solo_existencia']) && $req['solo_existencia'] !== '0',
            'almacenes'       => $almacenes,
        ];
    }

    // DataTables server-side
    public function getMatriz()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $req = $_POST + $_GET;
        $p = $this->filtrosMatriz($req);

        // Orden: la llave es el "data" de la columna (validada en el modelo)
        $idx = (int)($req['order'][0]['column'] ?? 0);
        $p['order_key'] = (string)($req['columns'][$idx]['data'] ?? 'cve_articulo');
        $p['order_dir'] = (strtolower($req['order'][0]['dir'] ?? 'asc') === 'desc') ? 'DESC' : 'ASC';
        $p['start']     = (int)($req['start'] ?? 0);
        $p['length']    = (int)($req['length'] ?? 25);

        $result = $this->model->selectMatrizPaginada($p);

        echo json_encode([
            'draw'            => (int)($req['draw'] ?? 0),
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'resumen'         => $result['resumen'],
            'data'            => $result['data'],
        ], JSON_UNESCAPED_UNICODE);
        die();
    }

    // Exportar la matriz completa (con los filtros actuales) a CSV para Excel
    public function exportarMatriz()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            die();
        }
        $p = $this->filtrosMatriz($_GET);
        $p['order_key']  = 'cve_articulo';
        $p['order_dir']  = 'ASC';
        $p['sin_limite'] = true;

        $almacenes = array_values(array_filter(
            $this->model->selectAlmacenesMatriz(),
            fn($a) => in_array((int)$a['idalmacen'], $p['almacenes'], true)
        ));
        $result = $this->model->selectMatrizPaginada($p);

        if (ob_get_length()) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="existencias_por_almacen_' . date('Ymd_His') . '.csv"');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // BOM para que Excel respete acentos
        $head = ['Clave', 'Descripción', 'Tipo', 'Total'];
        foreach ($almacenes as $a) {
            $head[] = $a['cve_almacen'];
        }
        fputcsv($out, $head);
        foreach ($result['data'] as $r) {
            $row = [
                $r['cve_articulo'],
                $r['descripcion'],
                self::TIPOS_MATRIZ[$r['tipo_elemento']] ?? $r['tipo_elemento'],
                (float)$r['total'],
            ];
            foreach ($almacenes as $a) {
                $v = $r['a_' . $a['idalmacen']];
                $row[] = ($v === null) ? '' : (float)$v;
            }
            fputcsv($out, $row);
        }
        fclose($out);
        die();
    }

}
