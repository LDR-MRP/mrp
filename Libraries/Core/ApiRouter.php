<?php
namespace Libraries\Core;

class ApiRouter {
    public static function dispatch(string $requestedUrl): void {
        $logFile = 'Logs/mrp-' . date('Y-m-d') . '.log';
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [ApiRouter::dispatch] URL solicitada: {$requestedUrl}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [ApiRouter::dispatch] Método: {$_SERVER['REQUEST_METHOD']}\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        // 1. Cargamos el archivo donde el usuario define las rutas.
        // Este sí usa require porque no es una clase, es un script procedimental.
        $routesFile = __DIR__ . '/../../Routes/api.php';
        if (file_exists($routesFile)) {
            require_once $routesFile;
        }

        $requestedUrl = trim($requestedUrl, '/');
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // 2. Preparar el request array con $_POST y $_FILES
        $request = array_merge($_POST, $_FILES);
        $logMsg = "[" . date('Y-m-d H:i:s') . "] [ApiRouter::dispatch] Request (POST + FILES): " . json_encode($request) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        $logMsg = "[" . date('Y-m-d H:i:s') . "] [ApiRouter::dispatch] Rutas registradas: " . json_encode(Route::$routes) . "\n";
        file_put_contents($logFile, $logMsg, FILE_APPEND);

        // 2. Iteramos las rutas registradas en la clase Route
        foreach (Route::$routes as $route) {
            
            if ($route['method'] !== $requestMethod) {
                continue;
            }

            // Convertimos {param} a Regex
            $pattern = preg_replace('/\{([a-zA-Z0-9_-]+)\}/', '([a-zA-Z0-9_-]+)', $route['uri']);
            $pattern = "#^" . $pattern . "$#";

            if (preg_match($pattern, $requestedUrl, $matches)) {
                array_shift($matches);
                $params = $matches;

                $controllerClass = $route['action'][0]; // Ej: "Controllers\Api\V1\Usuarios"
                $methodToCall    = $route['action'][1]; // Ej: "show"
                $middlewares     = $route['middlewares'];

                // Si la clase no existe, PHP dispara Autoload.php, la busca y la incluye al vuelo.
                if (!class_exists($controllerClass)) {
                    self::sendError(500, "Controlador no encontrado: {$controllerClass}");
                }

                $controllerInstance = new $controllerClass();

                if (!is_callable([$controllerInstance, $methodToCall])) {
                    self::sendError(501, "Método {$methodToCall} no implementado.");
                }

                // Preparamos el núcleo para el Pipeline
                $coreAction = function($request) use ($controllerInstance, $methodToCall, $params) {
                    $controllerInstance->request = $request;
                    return call_user_func_array([$controllerInstance, $methodToCall], $params);
                };

                // Ejecutamos Pipeline (También cargado por Autoload)
                Pipeline::process($request, $middlewares, $coreAction);
                return;
            }
        }

        self::sendError(404, "Endpoint no encontrado: {$requestedUrl}");
    }

    private static function sendError(int $code, string $message): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode(['status' => false, 'error' => $message]);
        exit;
    }
}
?>
