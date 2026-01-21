<?php

class Com_compras extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(COM_COMPRAS);
    }

    public function Com_compras(): void
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Compras";
        $data['page_title'] = "Compras";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_com_compras.js";

        $this->views->getView($this, "com_compras", $data);
    }

    public function index(): void
    {
        $arrData = $this->model->selectCompras();

        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }

    public function setCompra(): void
    {
        try {
            $request = new Com_comprasRequest($_POST);
            
            $request->validate();

            $data = $request->all();

            $db = $this->model->getConexion();

            $db->beginTransaction();

            $idCompra = $this->model->insertCompra($data);

            if($idCompra <= 0){
                throw new Exception("Error al registrar la cabecera de la compra.", 500);
            }

            $detalle = json_decode($data['detalle_partidas'], true);
            foreach ($detalle as $item) {
                $this->model->insertDetalle($idCompra, $item);
            }

            //$model->actualizarFolio($data['serieid'], $data['tipo_documento'], $data['folioid']);

            $db->commit();

            http_response_code(201);
            echo json_encode(["status" => true, "msg" => "Compra procesada con éxito."]);

        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();

            $code = $e->getCode();
            http_response_code(($code >= 400 && $code <= 599) ? $code : 500);

            $message = ($code == 422) ? json_decode($e->getMessage()) : $e->getMessage();
            
            echo json_encode([
                "status" => false, 
                "msg" => ($code == 422) ? "Errores de validación" : "Error de sistema",
                "errors" => $message
            ]);
        }
    }
}


?>