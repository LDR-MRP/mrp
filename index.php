<?php 
	require_once("Config/Config.php");
	require_once("Helpers/Helpers.php");
	if (file_exists('vendor/autoload.php')) {
		require_once 'vendor/autoload.php';
	}
	require_once("Libraries/Core/Autoload.php");
	$url = !empty($_GET['url']) ? $_GET['url'] : 'home/home';
	$url = ltrim($url, '/');

	// --- BIFURCACIÓN HACIA ENRUTAMIENTO API ---
	if (str_starts_with(strtolower($url), 'api/')) {
		\Libraries\Core\ApiRouter::dispatch($url);
		exit; 
	}
	// --- FIN BIFURCACIÓN ---

	$arrUrl = explode("/", $url);
	$controller = $arrUrl[0];
	$method = $arrUrl[0];
	$params = "";

	if(!empty($arrUrl[1]))
	{
		if($arrUrl[1] != "")
		{
			$method = $arrUrl[1];	
		}
	}

	if(!empty($arrUrl[2]))
	{
		if($arrUrl[2] != "")
		{
			for ($i=2; $i < count($arrUrl); $i++) {
				$params .=  $arrUrl[$i].',';
				
			}
			$params = trim($params,',');
		}
	}
	
	require_once("Libraries/Core/Load.php");

 ?>