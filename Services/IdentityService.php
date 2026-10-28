<?php
declare(strict_types=1);

namespace Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use AuthService;

class IdentityService 
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Intenta realizar un inicio de sesión automático mediante SSO (RRHH).
     * Sincroniza tanto el estado Legacy (Session) como el Moderno (Cookie JWT).
     */
    public function attemptSsoSync(): bool
    {
        // 1. Si ya existe sesión local, no hacemos nada (Optimización)
        if (!empty($_SESSION['login'])) {
            return true;
        }

        // 2. Si no hay cookie de RRHH, no hay nada que sincronizar
        if (empty($_COOKIE['token'])) {
            return false;
        }

        try {
            // 3. Validar Token de RRHH (Limpiando prefijo Bearer si existiera)
            $rawToken = trim(str_replace('Bearer ', '', (string)$_COOKIE['token']));

            $decoded = JWT::decode(
                $rawToken, 
                new Key(JWT_SECRET_RRHH, 'HS256')
            );

            // Convertir de stdClass a array asociativo para garantizar acceso por clave
            $decodedArray = json_decode(json_encode($decoded), true);
            if (!is_array($decodedArray)) {
                return false;
            }

            // 4. Delegar el intercambio de tokens y JIT Provisioning al AuthService
            $ssoResponse = $this->authService->authenticateViaSso($decodedArray);

            if ($ssoResponse->success) {
                if (isset($_COOKIE['mrp_forced_logout'])) {
                    if (!headers_sent()) {
                        setcookie('mrp_forced_logout', '', time() - 3600, '/', COOKIE_DOMAIN);
                    }
                    unset($_COOKIE['mrp_forced_logout']);
                }
                $this->hydrateLegacySession($ssoResponse->data['user']);
                $this->setLocalStatelessCookie($ssoResponse->data['access_token']);
                return true;
            }

        } catch (\Throwable $e) {
            error_log("IdentityService SSO Error: " . $e->getMessage());
        }

        return false;
    }

    /**
     * Replica exactamente el estado que el controlador legacy generaba.
     */
    private function hydrateLegacySession(array $userData): void
    {
        $userId = (int)$userData['id'];
        $_SESSION['idUser']      = $userId;
        $_SESSION['login']       = true;
        $_SESSION['avatar_file'] = $userData['avatar'] ?? 'avatar_default.svg';
        $_SESSION['rolid']       = (int)($userData['rolid'] ?? 3);
        $_SESSION['plantaid']    = (int)($userData['plantaid'] ?? 1);

        // Invocamos el helper vital del sistema anterior
        if (function_exists('sessionUser')) {
            sessionUser($userId);
        }
    }

    /**
     * Setea la cookie para los módulos modernos (Stateless).
     */
    private function setLocalStatelessCookie(string $token): void
    {
        if (!headers_sent()) {
            setcookie('mrp_token', $token, [
                'expires'  => time() + 36000,
                'path'     => '/',
                'domain'   => COOKIE_DOMAIN,
                'secure'   => COOKIE_SECURE,
                'httponly' => false,
                'samesite' => 'Lax'
            ]);
        }
        $_COOKIE['mrp_token'] = $token;
    }
}