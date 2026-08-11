<?php
namespace Middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Closure;
use Exception;

class AuthMiddleware implements MiddlewareInterface {
    
    private string $secretKey;

    public function __construct() {
        // 
        $this->secretKey = JWT_SECRET; 
    }

    public function handle(array $request, Closure $next) {
        // 1. Obtener el Header de Autorización
        $headers = function_exists('apache_request_headers') ? apache_request_headers() : [];
        
        // Buscamos en el array de Apache, $_SERVER (incluyendo rewrites de FastCGI) o fallback en Cookies
        $authHeader = $headers['Authorization'] ?? 
                      $headers['authorization'] ?? 
                      $_SERVER['HTTP_AUTHORIZATION'] ?? 
                      $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ??
                      '';

        if (empty($authHeader) && !empty($_COOKIE['mrp_token'])) {
            $authHeader = 'Bearer ' . $_COOKIE['mrp_token'];
        } elseif (empty($authHeader) && !empty($_COOKIE['srm_token'])) {
            $authHeader = 'Bearer ' . $_COOKIE['srm_token'];
        }

        if (empty($authHeader) || !preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            $this->unauthorized("Token de acceso no proporcionado o formato inválido.");
        }

        $jwt = $matches[1];

        try {
            // 2. Decodificar y Validar el Token
            $decoded = JWT::decode($jwt, new Key($this->secretKey, 'HS256'));

            // 3. INYECCIÓN DE SEGURIDAD
            // Guardamos los datos del usuario en el array $request para que lleguen al controlador
            $request['auth_user'] = (array) $decoded->data;
            
            // 4. Continuar al siguiente middleware o controlador
            return $next($request);

        } catch (Exception $e) {
            $this->unauthorized("Sesión inválida o expirada: " . $e->getMessage());
        }
    }

    private function unauthorized(string $message): void {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'error',
            'code' => 401,
            'message' => 'Unauthorized',
            'error' => $message
        ]);
        exit;
    }
}