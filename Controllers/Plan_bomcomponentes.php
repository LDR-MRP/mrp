<?php
	class Plan_bomcomponentes extends Controllers{
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
			getPermisos(MPBOMCOMPONENTES);
		}

		public function Plan_bomcomponentes()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}
			$data['page_tag'] = "BOM";
			$data['page_title'] = "BOM"; 
			$data['page_name'] = "bom";
			$data['page_functions_js'] = "functions_plan_bomcomponentes.js";
			$this->views->getView($this,"plan_bomcomponentes",$data);
		}

		

		    public function getSelectAlmacenes()
    {

        $htmlOptions = '<option value="">--Seleccione--</option>';
        $arrData = $this->model->selectOptionAlmacenes();
        if (count($arrData) > 0) {
            for ($i = 0; $i < count($arrData); $i++) {
                // if ($arrData[$i]['estado'] == 2) {
                    $htmlOptions .= '<option value="' . $arrData[$i]['idalmacen'] . '">' . $arrData[$i]['cve_almacen'] .'</option>';
                // }
            }
        }
        echo $htmlOptions;
        die();   
    }

			    public function getSelectAlmacen($idalmacen)
    {
        $arrData = $this->model->selectAlmacen($idalmacen);
		  echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

	


	}


 ?>