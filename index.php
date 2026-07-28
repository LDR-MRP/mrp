<?php

declare(strict_types=1);

// Ocultar errores al público
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);

// Forzar el registro de errores
ini_set('log_errors', '1');

// Usar la ruta absoluta automática hacia un archivo en esta misma carpeta
ini_set('error_log', __DIR__ . '/debug_local.log');

// Solo errores fatales y de parseo
error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);

/**
 * --------------------------------------------------------------------------
 * CONFIGURACIÓN DE COOKIES Y SESIÓN (SSO)
 * --------------------------------------------------------------------------
 */

// 1. Detectar el Host actual
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';

// 2. Determinar si estamos en entorno local
// Buscamos si el host es localhost, termina en .localhost o es una IP de red local
$isLocal = (bool)preg_match('/(^localhost|\.localhost)|^127\.0\.0\.1/', $httpHost);

if ($isLocal) {
    /**
     * ENTORNO: LOCAL
     * Si se accede directamente via 'localhost' o IP, el dominio de la cookie debe ser vacuo '' 
     * para que el navegador acepte la cookie de sesión/JWT sin rechazarla.
     */
    $cookieDomain = (str_contains($httpHost, 'ldrhumanresources.localhost')) ? '.ldrhumanresources.localhost' : '';
    define('ENV_TYPE', 'local');
    define('COOKIE_DOMAIN', $cookieDomain);
    define('COOKIE_SECURE', false);
    define('CONFIG_FILE', __DIR__ . "/Config/Config_local.php");
} else {
    /**
     * ENTORNO: PRODUCCIÓN
     * Dominio: .ldrhumanresources.com (Permite compartir entre rrhh. y mrp.)
     * Secure: true (Obligatorio ya que Hostinger usa Certificados SSL)
     */
    define('ENV_TYPE', 'production');
    define('COOKIE_DOMAIN', '.ldrhumanresources.com');
    define('COOKIE_SECURE', true);
    define('CONFIG_FILE', __DIR__ . "/Config/Config.php");
}

// Opcional: Tiempo de vida estándar (10 horas = 36000 seg)
define('COOKIE_EXPIRE', time() + 36000);

require_once(CONFIG_FILE);
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
    : 'login';

$url = ltrim($url, '/');

if (str_starts_with($url, 'mrp/')) {
    $url = substr($url, 4);
}

if (empty($url) || $url === 'mrp' || $url === 'index.php') {
    $url = 'login';
}

/*
|--------------------------------------------------------------------------
| Definición del contexto de portal
| - Identificamos si la petición pertenece a un portal externo o es ruido técnico.
|--------------------------------------------------------------------------
*/
$uriPath      = ltrim((string)$url, '/');
$firstSegment = explode('/', $uriPath)[0] ?? '';

// Portales que manejan su propia identidad y NO deben disparar el IdentityService administrativo
$isExternalPortal = in_array($firstSegment, ['srm', 'orders'], true);

// Peticiones técnicas (.well-known, devtools, etc) o archivos estáticos
$isSystemRequest = str_starts_with($firstSegment, '.') 
    || $firstSegment === 'Assets' 
    || preg_match('/\.(?:json|ico|css|js|png|jpg|jpeg|gif|webp|svg|map)$/i', $url);

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
| Se dispara ÚNICAMENTE si:
| - NO es un portal externo (SRM/Orders).
| - NO es una petición de sistema o archivo estático.
| - NO existe la sesión local ('mrp_token').
| - SÍ existe la cookie del SSO central ('token').
|
*/
$isInternalContext = !$isExternalPortal && !$isSystemRequest;
$needsSsoSync      = !isset($_COOKIE['mrp_token']) && isset($_COOKIE['token']);

if ($isInternalContext && $needsSsoSync) {
    /**
     * El servicio solo se instancia si hay algo que sincronizar 
     * y estamos en el lugar correcto.
     */
    $identity = new \Services\IdentityService();
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