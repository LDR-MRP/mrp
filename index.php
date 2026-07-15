<?php

// Ocultar errores al público
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

/*
|--------------------------------------------------------------------------
| Obtener ruta solicitada
|--------------------------------------------------------------------------
*/

$url = !empty($_GET['url'])
    ? $_GET['url']
    : 'home/home';

$url = ltrim($url, '/');

/*
|--------------------------------------------------------------------------
| Configuración independiente de sesiones
|--------------------------------------------------------------------------
|
| Panel administrativo:
|   PHPSESSID
|
| Portal de pedidos:
|   PEDIDOSSESSID
|
*/

if (session_status() === PHP_SESSION_NONE) {

    /*
     * Detectar si la solicitud pertenece al Portal de Pedidos.
     *
     * Ejemplos que detectará:
     * orders/login
     * orders/autenticar
     * orders/validarPin
     * orders/micuenta
     */
    $esPortalPedidos =
        preg_match(
            '#^orders(?:/|$)#i',
            $url
        ) === 1;

    /*
     * Detectar HTTPS directo o mediante proxy.
     */
    $esHttps =
        (
            !empty($_SERVER['HTTPS'])
            && strtolower($_SERVER['HTTPS']) !== 'off'
        )
        || (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower(
                $_SERVER['HTTP_X_FORWARDED_PROTO']
            ) === 'https'
        );

    /*
     * Asignar nombre de sesión según el módulo.
     */
    if ($esPortalPedidos) {
        session_name('PEDIDOSSESSID');
    } else {
        session_name('PHPSESSID');
    }

    /*
     * Configuración de la cookie.
     *
     * Se recomienda path "/" porque todas las rutas
     * pasan por el mismo index.php.
     *
     * La diferencia real entre sesiones será el nombre.
     */
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Capa de identidad SSO
|--------------------------------------------------------------------------
|
| Importante:
| Si IdentityService modifica la sesión administrativa,
| probablemente no debe ejecutarse para el portal de pedidos.
|
*/

$esPortalPedidos =
    preg_match(
        '#^orders(?:/|$)#i',
        $url
    ) === 1;

if (!$esPortalPedidos) {
    $identity = new Services\IdentityService();
    $identity->attemptSsoSync();
}

/*
|--------------------------------------------------------------------------
| Bifurcación hacia API
|--------------------------------------------------------------------------
*/

if (str_starts_with(strtolower($url), 'api/')) {
    \Libraries\Core\ApiRouter::dispatch($url);
    exit;
}

/*
|--------------------------------------------------------------------------
| Enrutamiento normal
|--------------------------------------------------------------------------
*/

$arrUrl = explode("/", $url);

$controller = $arrUrl[0];
$method = $arrUrl[0];
$params = "";

if (!empty($arrUrl[1])) {
    if ($arrUrl[1] != "") {
        $method = $arrUrl[1];
    }
}

if (!empty($arrUrl[2])) {
    if ($arrUrl[2] != "") {
        for ($i = 2; $i < count($arrUrl); $i++) {
            $params .= $arrUrl[$i] . ',';
        }

        $params = trim($params, ',');
    }
}

require_once("Libraries/Core/Load.php");