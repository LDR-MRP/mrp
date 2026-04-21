<?php
namespace Controllers\Api\V1;

use Services\AuthService;

class AuthController {
    use \ApiResponser;

    protected \AuthService $authService;

    public function __construct() {
        $this->authService = new \AuthService();
    }

    public function login() {
        // Pasamos null al servicio porque el login no requiere usuario autenticado previo
        $serviceResponse = $this->authService->authenticate();
        return $this->apiResponse($serviceResponse);
    }
}