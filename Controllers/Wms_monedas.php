<?php

class Wms_monedas extends Controllers{

    public function __construct()
    {
        parent::__construct();
    }

    public function getMonedas()
    {
        header("Content-Type: application/json; charset=UTF-8");

        try {
            $data = $this->model->selectMonedas();

            http_response_code(200);
            echo json_encode([
                "status" => true,
                "msg"    => "Datos obtenidos correctamente",
                "data"   => $data
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {

            $code = $e->getCode();
            
            if ($code < 400 || $code > 599) $code = 500; 

            http_response_code($code);
            echo json_encode([
                "status" => false,
                "msg"    => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}