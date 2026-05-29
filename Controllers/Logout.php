<?php
	class Logout
	{
		public function __construct()
		{
			// 1. Limpiar variables de sesión
			session_start();
			session_unset();

			 // 2. IMPORTANTE: Crear la cookie que evita el re-login automático
			// La seteamos por un tiempo razonable (ej. 12 horas)
			setcookie('mrp_forced_logout', '1', [
				'expires'  => time() + 43200, 
				'path'     => '/',
				'domain'   => COOKIE_DOMAIN,
				'secure'   => COOKIE_SECURE,
				'httponly' => true,
				'samesite' => 'Lax'
			]);

			// 3. Borrar la cookie del token local del MRP
			setcookie('mrp_token', '', time() - 3600, '/', COOKIE_DOMAIN);
			
			session_destroy();
			header('location: '.base_url().'/login');
			die();
		}
	}
 ?>