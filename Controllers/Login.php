<?php

class Login extends Controllers
{
	// public function __construct()
	// {
	// 	session_start();
	// 	if(isset($_SESSION['login']))
	// 	{
	// 		header('Location: '.base_url().'/dashboard');
	// 		die();
	// 	}
	// 	parent::__construct();
	// }

	public function __construct()
	{
		session_start();
		$raw = $_GET['numerocolaborador'] ?? '';
		$raw = trim((string)$raw);

		if (isset($_SESSION['login']) && $raw === '') {
			header('Location: ' . base_url() . '/dashboard');
			die();
		}
		parent::__construct();
	}



	public function loginold()
	{
		$data['page_tag'] = "Login - MRP";
		$data['page_title'] = "MRP";
		$data['page_name'] = "login";
		$data['page_functions_js'] = "functions_login.js";
		dep($data);
		$this->views->getView($this, "login", $data);
	}

	public function login()
	{
		$data['page_tag'] = "Login - MRP";
		$data['page_title'] = "MRP";
		$data['page_name'] = "login";
		$data['page_functions_js'] = "functions_login.js";

		if (!empty($_SESSION['error_sso'])) {
			$data['error_sso'] = $_SESSION['error_sso'];
			unset($_SESSION['error_sso']);
		}


		$raw = $_GET['numerocolaborador'] ?? '';
		$raw = trim((string)$raw);

		if ($raw !== '') {
			$raw = urldecode($raw);
			$raw = trim($raw, " \t\n\r\0\x0B'\"");

			if (preg_match('/^[A-Za-z0-9+\/=]+$/', $raw)) {
				$decoded = base64_decode($raw, true);
				if ($decoded !== false) $raw = trim($decoded);
			}

			if (preg_match('/^\d{1,20}$/', $raw)) {
				$requestUser = $this->model->loginByNumColaborador($raw);

				if (!empty($requestUser) && (int)$requestUser['status'] === 1) {

					$_SESSION['idUser'] = $requestUser['idusuario'];
					$_SESSION['login'] = true;
					$_SESSION['avatar_file'] = $requestUser['avatar_file'];
					$_SESSION['rolid'] = $requestUser['rolid'];
					$_SESSION['plantaid'] = $requestUser['plantaid'];

					$this->model->sessionLogin($_SESSION['idUser']);

					$evento = 'Inicio de Sesión (SSO)';
					$ip = $_SERVER['REMOTE_ADDR'] ?? '';
					$detalle = $_SERVER['HTTP_USER_AGENT'] ?? '';
					$fecha_creacion = date('Y-m-d H:i:s');
					$this->model->registrarAcceso($_SESSION['idUser'], $evento, $ip, $detalle, $fecha_creacion);

					sessionUser($_SESSION['idUser']);

					header("Location: " . base_url() . "/dashboard");
					die();
				} else {
					$data['error_sso'] = "No se pudo validar el acceso automático.";
				}
			} else {
				$data['error_sso'] = "Parámetro numerocolaborador inválido.";
			}
		}


		$this->views->getView($this, "login", $data);
	}




	public function loginUser()
	{
		//dep($_POST);
		if ($_POST) {
			if (empty($_POST['txtEmail']) || empty($_POST['txtPassword'])) {
				$arrResponse = array('status' => false, 'msg' => 'Error de datos. Ingrese usuario y contraseña.');
			} else {
				$strUsuario  =  strtolower(strClean($_POST['txtEmail']));
				$strPassword = hash("SHA256", $_POST['txtPassword']);

				$userCheck = $this->model->getUserEmail($strUsuario);

				if (empty($userCheck)) {
					$arrResponse = array('status' => false, 'msg' => 'El usuario no se encuentra registrado.');
				} else if (strtolower($userCheck['password']) !== strtolower($strPassword)) {
					$arrResponse = array('status' => false, 'msg' => 'La contraseña ingresada es incorrecta.');
				} else if ($userCheck['status'] != 1) {
					$arrResponse = array('status' => false, 'msg' => 'El usuario se encuentra inactivo. Contacte al administrador.');
				} else {
					$requestUser = $this->model->loginUser($strUsuario, $strPassword);
					$arrData = $requestUser;
					if ($arrData['status'] == 1) {
						$_SESSION['idUser'] = $arrData['idusuario'];
						$_SESSION['login'] = true;
						$_SESSION['avatar_file'] = $arrData['avatar_file'];
						$_SESSION['rolid'] = $arrData['rolid'];

						$arrData = $this->model->sessionLogin($_SESSION['idUser']);

						$evento = 'Inicio de Sesión';
						$ip = $_SERVER['REMOTE_ADDR'];
						$detalle = $_SERVER['HTTP_USER_AGENT'];
						$fecha_creacion = date('Y-m-d H:i:s');
						$this->model->registrarAcceso($_SESSION['idUser'], $evento, $ip, $detalle, $fecha_creacion);

						sessionUser($_SESSION['idUser']);

						// Generar JWT para Sys_Core frontend
						$now = time();
						$tokenPayload = [
							'iat'  => $now,
							'exp'  => $now + (60 * 60 * 10),
							'data' => [
								'id'       => $arrData['idusuario'],
								'nombre'   => $arrData['nombres'] . ' ' . $arrData['apellidos'],
								'rolid'    => $arrData['rolid'],
								'plantaid' => $arrData['plantaid'] ?? 1,
								'rol'      => $arrData['rol_nombre'] ?? 'Administrador',
								'avatar'   => $arrData['avatar_file'] ?? 'avatar_default.svg',
								'is_vendor'=> false
							]
						];
						if (class_exists('\Firebase\JWT\JWT') && defined('JWT_SECRET')) {
							$jwt = \Firebase\JWT\JWT::encode($tokenPayload, JWT_SECRET, 'HS256');
							setcookie('mrp_token', $jwt, [
								'expires'  => time() + 36000,
								'path'     => '/',
								'domain'   => COOKIE_DOMAIN,
								'secure'   => COOKIE_SECURE,
								'httponly' => false,
								'samesite' => 'Lax'
							]);
						}

						$arrResponse = array('status' => true, 'msg' => 'ok');
					} else {
						$arrResponse = array('status' => false, 'msg' => 'Usuario inactivo.');
					}
				}
			}
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}
		die();
	}

	public function resetPass()
	{
		if ($_POST) {

			// 	dep($_POST);
			// exit; 
			error_reporting(0);
			$correos_copias = "carlos.cruz@ldrsolutions.com.mx";

			if (empty($_POST['txtEmailReset'])) {
				$arrResponse = array('status' => false, 'msg' => 'Error de datos');
			} else {
				$token = token();
				$strEmail  =  strtolower(strClean($_POST['txtEmailReset']));
				$arrData = $this->model->getUserEmail($strEmail);

				if (empty($arrData)) {
					$arrResponse = array('status' => false, 'msg' => 'Usuario no existente.');
				} else {
					$idusuario = $arrData['idusuario'];
					$nombreUsuario = $arrData['nombres'] . ' ' . $arrData['apellidos'];

					$url_recovery = base_url() . '/login/confirmUser/' . $strEmail . '/' . $token;
					$requestUpdate = $this->model->setTokenUser($idusuario, $token);

					$dataUsuario = array(
						'nombreUsuario' => $nombreUsuario,
						'email' => $strEmail,
						'asunto' => 'Recuperar cuenta - ' . NOMBRE_REMITENTE,
						'url_recovery' => $url_recovery
					);
					if ($requestUpdate) {
						$sendEmail = sendMailLocal($dataUsuario, 'email_cambioPassword', $correos_copias);

						if ($sendEmail) {
							$arrResponse = array(
								'status' => true,
								'msg' => 'Se ha enviado un email a tu cuenta de correo para cambiar tu contraseña.'
							);
						} else {
							$arrResponse = array(
								'status' => false,
								'msg' => 'No es posible realizar el proceso, intenta más tarde.'
							);
						}
					} else {
						$arrResponse = array(
							'status' => false,
							'msg' => 'No es posible realizar el proceso, intenta más tarde.'
						);
					}
				}
			}
			echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		}
		die();
	}


	public function confirmUser(string $params)
	{

		if (empty($params)) {
			header('Location: ' . base_url());
		} else {
			$arrParams = explode(',', $params);
			$strEmail = strClean($arrParams[0]);
			$strToken = strClean($arrParams[1]);
			$arrResponse = $this->model->getUsuario($strEmail, $strToken);
			if (empty($arrResponse)) {
				header("Location: " . base_url());
			} else {
				$data['page_tag'] = "Cambiar contraseña";
				$data['page_name'] = "cambiar_contrasenia";
				$data['page_title'] = "Cambiar Contraseña";
				$data['email'] = $strEmail;
				$data['token'] = $strToken;
				$data['idusuario'] = $arrResponse['idusuario'];
				$data['page_functions_js'] = "functions_login.js";
				$this->views->getView($this, "cambiar_password", $data);
			}
		}
		die();
	}

	public function setPassword()
	{

		if (empty($_POST['idUsuario']) || empty($_POST['txtEmail']) || empty($_POST['txtToken']) || empty($_POST['txtPassword']) || empty($_POST['txtPasswordConfirm'])) {

			$arrResponse = array(
				'status' => false,
				'msg' => 'Error de datos'
			);
		} else {
			$intIdusuario = intval($_POST['idUsuario']);
			$strPassword = $_POST['txtPassword'];
			$strPasswordConfirm = $_POST['txtPasswordConfirm'];
			$strEmail = strClean($_POST['txtEmail']);
			$strToken = strClean($_POST['txtToken']);

			if ($strPassword != $strPasswordConfirm) {
				$arrResponse = array(
					'status' => false,
					'msg' => 'Las contraseñas no son iguales.'
				);
			} else {
				$arrResponseUser = $this->model->getUsuario($strEmail, $strToken);
				if (empty($arrResponseUser)) {
					$arrResponse = array(
						'status' => false,
						'msg' => 'Erro de datos.'
					);
				} else {
					$strPassword = hash("SHA256", $strPassword);
					$requestPass = $this->model->insertPassword($intIdusuario, $strPassword);

					if ($requestPass) {
						$arrResponse = array(
							'status' => true,
							'msg' => 'Contraseña actualizada con éxito.'
						);
					} else {
						$arrResponse = array(
							'status' => false,
							'msg' => 'No es posible realizar el proceso, intente más tarde.'
						);
					}
				}
			}
		}
		echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
		die();
	}

	/**
	 * Punto de entrada manual para login vía RRHH.
	 * Útil cuando el usuario cerró sesión pero quiere volver a entrar vía SSO.
	 */
	public function sso_login(): void
	{
		// 1. Limpiar cookie de bloqueo si existía
		if (isset($_COOKIE['mrp_forced_logout'])) {
			setcookie('mrp_forced_logout', '', time() - 3600, '/', COOKIE_DOMAIN);
		}

		// 2. Intentar la sincronización SSO inmediatamente
		if (class_exists(\Services\IdentityService::class)) {
			$identity = new \Services\IdentityService();
			$succeeded = $identity->attemptSsoSync();

			if ($succeeded || !empty($_SESSION['login'])) {
				$target = base_url() . '/dashboard';
				if (ob_get_length()) ob_clean();
				if (!headers_sent()) {
					header('Location: ' . $target);
				}
				echo "<script>window.location.href = '" . $target . "';</script>";
				exit;
			}
		}

		// 3. Si no hay cookie 'token' o la firma del JWT falló:
		$_SESSION['error_sso'] = "No se detectó una sesión activa válida en el Portal de RRHH o el token fue rechazado. Asegúrate de iniciar sesión primero en el Portal corporativo.";
		$target = base_url() . '/login';
		if (ob_get_length()) ob_clean();
		if (!headers_sent()) {
			header('Location: ' . $target);
		}
		echo "<script>window.location.href = '" . $target . "';</script>";
		exit;
	}
}
