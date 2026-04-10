<?php
	class Inv_picking extends Controllers{
		public function __construct()
		{
			parent::__construct();
			session_start();
			//session_regenerate_id(true);
			if(empty($_SESSION['login']))
			{
				header('Location: '.base_url().'/login');
				die();
			} 
			getPermisos(MIPICKING); 
		}

		public function Inv_picking()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "Picking";
			$data['page_title'] = "Picking";
			$data['page_name'] = "Picking";
			$data['page_functions_js'] = "functions_inv_picking.js";
			$this->views->getView($this,"inv_picking",$data);
		}

        // 🔹 LISTAR PICKINGS
public function getPickings(){
    $arrData = $this->model->selectPickings();
    echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    die();
}

// 🔹 DETALLE
public function getDetalle($idPicking){
    try{
        $arrData = $this->model->selectDetalle($idPicking);
        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
    }catch(Exception $e){
        echo json_encode([
            "error" => $e->getMessage()
        ]);
    }
    die();
}

// 🔹 GUARDAR PICKING
public function setPicking(){
    $data = json_decode(file_get_contents("php://input"), true);

    foreach($data as $item){

        $iddetalle = $item['iddetalle'];
        $cantidad = $item['cantidad'];
        $inventarioid = $item['inventarioid'];
        $ubicacionid = $item['ubicacionid'];

        // actualizar picking
        $this->model->updatePicking($iddetalle, $cantidad);

        // descontar inventario
        $this->model->descontarInventario($inventarioid, $ubicacionid, $cantidad);
    }

    echo json_encode(["status"=>true,"msg"=>"Picking actualizado"]);
    die();
}

public function setPickingHeader(){

    $data = json_decode(file_get_contents("php://input"), true);

    $folio = $data['folio'];
    $pedido = $data['pedido'];
    $prioridad = $data['prioridad'];

    $request = $this->model->insertPicking($folio, $pedido, $prioridad);

    echo json_encode(["status"=>true]);
    die();
}

public function addDetalle(){

    $data = json_decode(file_get_contents("php://input"), true);

    $this->model->insertDetalle(
        $data['pickingid'],
        $data['inventarioid'],
        $data['ubicacionid'],
        $data['cantidad']
    );

    echo json_encode(["status"=>true]);
    die();
}


	}


 ?>