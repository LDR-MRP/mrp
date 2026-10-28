<?php
	class Logout
	{
		public function __construct()
		{
			// 1. Limpiar variables de sesión
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
			}
			$_SESSION = [];
			session_unset();

			// 2. IMPORTANTE: Crear la cookie que evita el re-login automático inmediato tras cerrar sesión
			setcookie('mrp_forced_logout', '1', [
				'expires'  => time() + 43200, 
				'path'     => '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => COOKIE_SECURE,
				'httponly' => true,
				'samesite' => 'Lax'
			]);

			// 3. Borrar la cookie del token local del MRP y el token SSO compartido (si aplica)
			setcookie('mrp_token', '', [
				'expires'  => time() - 3600,
				'path'     => '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => COOKIE_SECURE,
				'httponly' => false,
				'samesite' => 'Lax'
			]);
			setcookie('token', '', [
				'expires'  => time() - 3600,
				'path'     => '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => COOKIE_SECURE,
				'httponly' => false,
				'samesite' => 'Lax'
			]);
			if (COOKIE_DOMAIN !== '') {
				setcookie('token', '', time() - 3600, '/');
				setcookie('mrp_token', '', time() - 3600, '/');
			}
			unset($_COOKIE['mrp_token'], $_COOKIE['token']);
			
			session_destroy();
			header('Location: ' . base_url() . '/login?logged_out=1');
			die();
		}
	}
