<?php 

	class Errors extends Controllers{
		public function __construct()
		{
			parent::__construct();
		}

    public function notFound()
{
    header("Location: " . base_url() . "/login");
    exit; // Importante para detener la ejecución
}
	}


	$notFound = new Errors();
	$notFound->notFound();
 ?>