<?php
/** Consulta y reportes de Backorders. Conserva el modal de detalle actual. */
class Ped_backorders extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['login'])) { header('Location: '.base_url().'/login'); exit; }
    }
    public function Ped_backorders()
    {
        $data=['page_tag'=>'Backorders','page_title'=>'Seguimiento de Backorders',
            'page_name'=>'backorders','page_functions_js'=>['modulos/clientes_pedidos/backorders/index.js']];
        $this->views->getView($this,'index',$data);
    }
    private function filtros(): array
    {
        $campos=['pedido','modelo','estado','desde','hasta','entrega_desde','entrega_hasta','mes','rapido'];
        $f=[];
        foreach ($campos as $c) $f[$c]=trim((string)($_GET[$c]??''));
        return $f;
    }
    private function respuestaError(Throwable $e, string $prefijo): void
    {
        error_log($prefijo.' '.$e->getMessage());
        http_response_code($e instanceof InvalidArgumentException ? 400 : 500);
        echo json_encode(['status'=>false,'message'=>$e instanceof InvalidArgumentException ? $e->getMessage() : 'No fue posible consultar los backorders.'],JSON_UNESCAPED_UNICODE);
    }
    public function getBackorders()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);echo json_encode(['status'=>false,'message'=>'Método no permitido']);return;}
        try{
            $pagina=max(1,min(100000,(int)($_GET['pagina']??1)));
            echo json_encode(['status'=>true,'data'=>$this->model->listar($this->filtros(),$pagina,20)],JSON_UNESCAPED_UNICODE);
        }catch(Throwable $e){$this->respuestaError($e,'[PED_BACKORDERS]');}
    }
    public function getReporte()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);echo json_encode(['status'=>false,'message'=>'Método no permitido']);return;}
        try{echo json_encode(['status'=>true,'data'=>$this->model->reporte($this->filtros())],JSON_UNESCAPED_UNICODE);}
        catch(Throwable $e){$this->respuestaError($e,'[PED_BACKORDERS_REPORTE]');}
    }
    public function exportarExcel()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); return; }
        try {
            require_once __DIR__.'/../Services/PedBackordersReporteXlsx.php';
            $f=$this->filtros();
            $d=$this->model->reporte($f);
            PedBackordersReporteXlsx::descargar($d,$f);
        } catch(Throwable $e) {
            error_log('[PED_BACKORDERS_XLSX] '.$e->getMessage());
            if(!headers_sent()) { http_response_code(500); header('Content-Type: text/plain; charset=utf-8'); }
            echo 'No se pudo generar el Excel: '.$e->getMessage();
        }
    }
    public function getModelos()
    {
        header('Content-Type: application/json; charset=utf-8');
        if ($_SERVER['REQUEST_METHOD']!=='GET') { http_response_code(405); echo json_encode(['status'=>false,'message'=>'Método no permitido']); return; }
        try { echo json_encode(['status'=>true,'data'=>$this->model->modelosDisponibles()],JSON_UNESCAPED_UNICODE); }
        catch(Throwable $e) { $this->respuestaError($e,'[PED_BACKORDERS_MODELOS]'); }
    }
    public function getDetalle()
    {
        header('Content-Type: application/json; charset=utf-8');
        if($_SERVER['REQUEST_METHOD']!=='GET'){http_response_code(405);echo json_encode(['status'=>false,'message'=>'Método no permitido']);return;}
        $clave=trim((string)($_GET['clave']??''));
        if($clave===''||strlen($clave)>100){http_response_code(400);echo json_encode(['status'=>false,'message'=>'Pedido inválido']);return;}
        try{echo json_encode(['status'=>true,'data'=>$this->model->detalle($clave)],JSON_UNESCAPED_UNICODE);}
        catch(Throwable $e){$this->respuestaError($e,'[PED_BACKORDERS_DETALLE]');}
    }
}
