<?php
class Dashboard extends Controllers{
	public function __construct()
	{
		parent::__construct();
	}

	public function dashboard()
	{
		$data['page_id'] = 2;
		$data['page_tag'] = "Dashboard - MRP";
		$data['page_title'] = "Dashboard - MRP";
		$data['page_name'] = "dashboard";
		$data['page_functions_js'] = "functions_dashboard.js";
		$this->views->getView($this,"index",$data);
	}
}
?>