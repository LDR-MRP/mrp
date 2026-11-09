<?php
/** Seguimiento de compromisos aceptados. No altera el flujo de aprobación. */
class Ped_backorders extends Controllers {
    public function __construct() {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['login'])) { header('Location: '.base_url().'/login'); exit; }
        // getPermisos(MCLIENTES_PEDIDOS);
    }
    public function Ped_backorders() {
        $data=['page_tag'=>'Backorders','page_title'=>'Seguimiento de Backorders',
            'page_name'=>'backorders','page_functions_js'=>['modulos/clientes_pedidos/backorders/index.js']];
        $this->views->getView($this,'index',$data);
    }
    public function getBackorders() {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD']!=='GET') {http_response_code(405);echo json_encode(['status'=>false,'message'=>'Método no permitido']);return;}
        try {
            $filtro=trim((string)($_GET['pedido']??''));
            $estado=trim((string)($_GET['estado']??''));
            $pagina=max(1,min(100000,(int)($_GET['pagina']??1)));
            $resultado=$this->model->listar($filtro,$estado,$pagina,20);
            // dep($resultado);
            echo json_encode(['status'=>true,'data'=>$resultado],JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            error_log('[PED_BACKORDERS] '.$e->getMessage());
            http_response_code(500);
            echo json_encode(['status'=>false,'message'=>'No fue posible consultar los backorders.']);
        }
    }
    public function getDetalle() {
        header('Content-Type: application/json; charset=utf-8');
        if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);echo json_encode(['status'=>false,'message'=>'Método no permitido']);return;}
        $clave=trim((string)($_GET['clave']??''));
        if($clave==='' || strlen($clave)>100){http_response_code(400);echo json_encode(['status'=>false,'message'=>'Pedido inválido']);return;}
        try {
            $items=$this->model->detalle($clave);
            echo json_encode(['status'=>true,'data'=>$items],JSON_UNESCAPED_UNICODE);
        } catch(Throwable $e){error_log('[PED_BACKORDERS_DETALLE] '.$e->getMessage());http_response_code(500);echo json_encode(['status'=>false,'message'=>'No fue posible consultar el detalle']);}
    }
}
