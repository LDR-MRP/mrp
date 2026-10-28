<?php
	class Logout
	{
		public function __construct()
		{
			// 1. Limpiar variables de sesión local de MRP
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION = [];
			session_unset();

			// 2. Borrar la cookie del token local del MRP (preservando la sesión de RRHH)
			setcookie('mrp_token', '', [
				'expires'  => time() - 3600,
				'path'     => '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => COOKIE_SECURE,
				'httponly' => false,
				'samesite' => 'Lax'
			]);
			if (COOKIE_DOMAIN !== '') {
				setcookie('mrp_token', '', time() - 3600, '/');
			}
			unset($_COOKIE['mrp_token']);
			
			session_destroy();
			header('Location: ' . base_url() . '/login');
			die();
		}
	}


