<?php

declare(strict_types=1);

ob_start();

// Ocultar errores al público.
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Registrar errores.
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/debug_local.log');

error_reporting(E_ERROR | E_PARSE | E_COMPILE_ERROR);

/*
|--------------------------------------------------------------------------
| Configuración del entorno
|--------------------------------------------------------------------------
| Modificado: 03/11/2026
| Soporte para el VirtualHost local http://mrp.com/.
|--------------------------------------------------------------------------
*/

// Normalizar el host y quitar un posible puerto.
$httpHost = strtolower(
    trim((string) ($_SERVER['HTTP_HOST'] ?? 'localhost'))
);

$hostname = parse_url('http://' . $httpHost, PHP_URL_HOST);

$hostname = is_string($hostname)
    ? rtrim($hostname, '.')
    : 'localhost';

$isLocal = in_array(
    $hostname,
    [
        'localhost',
        '127.0.0.1',
        '[::1]',
        'mrp.com',
        'www.mrp.com',
    ],
    true
) || str_ends_with($hostname, '.localhost');

// Detectar HTTPS directo.
$esHttps =
    (
        !empty($_SERVER['HTTPS'])
        && strtolower((string) $_SERVER['HTTPS']) !== 'off'
    )
    || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;

// Conservar la detección por proxy en producción.
if (!$isLocal) {
    $esHttps = $esHttps
        || (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower(
                trim((string) $_SERVER['HTTP_X_FORWARDED_PROTO'])
            ) === 'https'
        );
}

if ($isLocal) {
    $isSharedLocalDomain =
        $hostname === 'ldrhumanresources.localhost'
        || str_ends_with(
            $hostname,
            '.ldrhumanresources.localhost'
        );

    define('ENV_TYPE', 'local');

    define(
        'COOKIE_DOMAIN',
        $isSharedLocalDomain ? '.ldrhumanresources.localhost' : ''
    );

    define('COOKIE_SECURE', $esHttps);

    /*
     * Tu Config.php contiene los datos de MySQL de XAMPP.
     * Config_local.php contiene los datos de Docker (mrp-db).
     */
    define('CONFIG_FILE', __DIR__ . '/Config/Config.php');
} else {
    define('ENV_TYPE', 'production');
    define('COOKIE_DOMAIN', '.ldrhumanresources.com');
    define('COOKIE_SECURE', true);
    define('CONFIG_FILE', __DIR__ . '/Config/Config.php');
}

define('COOKIE_EXPIRE', time() + 36000);

/*
|--------------------------------------------------------------------------
| Cargar configuración y dependencias
|--------------------------------------------------------------------------
*/

require_once CONFIG_FILE;
require_once __DIR__ . '/Helpers/Helpers.php';

if (is_file(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

require_once __DIR__ . '/Libraries/Core/Autoload.php';

/*
|--------------------------------------------------------------------------
| Obtener ruta solicitada
|--------------------------------------------------------------------------
*/

$url = !empty($_GET['url']) && is_string($_GET['url'])
    ? $_GET['url']
    : 'login';

$url = ltrim($url, '/');

if (str_starts_with($url, 'mrp/')) {
    $url = substr($url, 4);
}

if ($url === '' || $url === 'mrp' || $url === 'index.php') {
    $url = 'login';
}

/*
|--------------------------------------------------------------------------
| Contexto del portal
|--------------------------------------------------------------------------
*/

$uriPath = ltrim($url, '/');
$firstSegment = explode('/', $uriPath)[0] ?? '';
$normalizedSegment = strtolower($firstSegment);

// Portales con identidad independiente.
$isExternalPortal = in_array(
    $normalizedSegment,
    ['srm', 'orders'],
    true
);

// Peticiones técnicas y archivos estáticos.
$isSystemRequest =
    str_starts_with($firstSegment, '.')
    || $normalizedSegment === 'assets'
    || preg_match(
        '/\.(?:json|ico|css|js|png|jpg|jpeg|gif|webp|svg|map)$/i',
        $url
    ) === 1;

/*
|--------------------------------------------------------------------------
| Sesiones independientes
|--------------------------------------------------------------------------
| Administrativo: PHPSESSID
| Pedidos:        PEDIDOSSESSID
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {
    $esPortalPedidos = preg_match(
        '#^orders(?:/|$)#i',
        $url
    ) === 1;

    session_name(
        $esPortalPedidos
            ? 'PEDIDOSSESSID'
            : 'PHPSESSID'
    );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $esHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

/*
|--------------------------------------------------------------------------
| Identidad SSO
|--------------------------------------------------------------------------
| Sincronizar únicamente al entrar a /sso.
|--------------------------------------------------------------------------
*/

$isInternalContext = !$isExternalPortal && !$isSystemRequest;

$isSsoRoute = $normalizedSegment === 'sso';

$needsSsoSync =
    $isSsoRoute
    && empty($_SESSION['login'])
    && !empty($_COOKIE['token']);

if ($isInternalContext && $needsSsoSync) {
    $identity = new \Services\IdentityService();
    $identity->attemptSsoSync();
}

/*
|--------------------------------------------------------------------------
| Enrutamiento API
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

$arrUrl = explode('/', $url);

$controller = $arrUrl[0];
$method = $arrUrl[0];
$params = '';

if (!empty($arrUrl[1])) {
    $method = $arrUrl[1];
}

if (!empty($arrUrl[2])) {
    for ($i = 2; $i < count($arrUrl); $i++) {
        $params .= $arrUrl[$i] . ',';
    }

    $params = trim($params, ',');
}

require_once __DIR__ . '/Libraries/Core/Load.php';