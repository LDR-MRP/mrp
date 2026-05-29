<?php // Ocultar errores al público
	ini_set('display_errors', 0);
	ini_set('display_startup_errors', 0);

	// Forzar el registro de errores
	ini_set('log_errors', '1');

	// Usar la ruta absoluta automática hacia un archivo en esta misma carpeta
	ini_set('error_log', __DIR__ . '/debug_local.log');

	// Solo errores fatales y de parseo
	error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);

	require_once("Config/Config.php");
	require_once("Helpers/Helpers.php");
	if (file_exists('vendor/autoload.php')) {
		require_once 'vendor/autoload.php';
	}
	require_once("Libraries/Core/Autoload.php");
	$url = !empty($_GET['url']) ? $_GET['url'] : 'home/home';
	$url = ltrim($url, '/');

	// --- CAPA DE IDENTIDAD (SSO) ---
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// El IdentityService se encarga de todo el "How", index.php solo dice "When"
	$identity = new Services\IdentityService();
	$identity->attemptSsoSync(); 

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