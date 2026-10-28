<?php
declare(strict_types=1);

class Sso extends Controllers
{
    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        parent::__construct();
    }

    /**
     * Endpoint de acceso directo desde el Portal de RRHH: /sso
     * Valida el JWT en $_COOKIE['token'], hidrata la sesión y redirige a /dashboard.
     */
    public function sso(): void
    {
        if (isset($_COOKIE['mrp_forced_logout'])) {
            if (!headers_sent()) {
                setcookie('mrp_forced_logout', '', time() - 3600, '/', COOKIE_DOMAIN);
            }
            unset($_COOKIE['mrp_forced_logout']);
        }

        if (empty($_SESSION['login']) && class_exists(\Services\IdentityService::class)) {
            $identity = new \Services\IdentityService();
            $identity->attemptSsoSync();
        }

        if (!empty($_SESSION['login'])) {
            $target = base_url() . '/dashboard';
            if (ob_get_length()) {
                ob_clean();
            }
            if (!headers_sent()) {
                header('Location: ' . $target);
            }
            echo "<script>window.location.href = '" . $target . "';</script>";
            exit;
        }

        $_SESSION['error_sso'] = "No se detectó una sesión activa válida en el Portal de RRHH o el token expiró.";
        $target = base_url() . '/login';
        if (ob_get_length()) {
            ob_clean();
        }
        if (!headers_sent()) {
            header('Location: ' . $target);
        }
        echo "<script>window.location.href = '" . $target . "';</script>";
        exit;
    }
}

