<?php
class Dashboard extends Controllers{
	public function __construct()
	{
		parent::__construct();
		if (session_status() === PHP_SESSION_NONE) {
			session_start();
		}
		if (empty($_SESSION['login'])) {
			header('Location: ' . base_url() . '/login');
			die();
		}
		getPermisos(MDASHBOARD);
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