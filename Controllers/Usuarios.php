<?php 

	class Usuarios extends Controllers{
		public function __construct()
		{
			parent::__construct();
			session_start();
			if(empty($_SESSION['login']))
			{
				header('Location: '.base_url().'/login');
				die();
			}
			getPermisos(MUSUARIOS);
		}

		public function Usuarios()
		{
			if(empty($_SESSION['permisosMod']['r'])){
				header("Location:".base_url().'/dashboard');
			}

			
			$data['page_tag'] = "Usuarios";
			$data['page_title'] = "USUARIOS <small>Tienda Virtual</small>";
			$data['page_name'] = "usuarios";
			$data['page_functions_js'] = "functions_usuarios.js";
			$this->views->getView($this,"usuarios",$data);
		}

		public function setUsuario(){

	
			if($_POST){			
				if(empty($_POST['txtNombre']) || empty($_POST['txtApellido']) || empty($_POST['txtTelefono']) || empty($_POST['txtEmail']) || empty($_POST['listRolid']) || empty($_POST['listStatus']) )
				{
					$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
				}else{ 
					$idUsuario = intval($_POST['idUsuario']);
					// $strIdentificacion = strClean($_POST['txtIdentificacion']);
					$strNombre = ucwords(strClean($_POST['txtNombre']));
					$strApellido = ucwords(strClean($_POST['txtApellido']));
					$intTelefono = intval(strClean($_POST['txtTelefono']));
					$strEmail = strtolower(strClean($_POST['txtEmail']));
					$intTipoId = intval(strClean($_POST['listRolid']));
					$intStatus = intval(strClean($_POST['listStatus']));
					$intEnviarCorreo = isset($_POST['chkEnviarPass']) 
                    ? intval(strClean($_POST['chkEnviarPass'])) 
                    : 0;
					$strPasswordLineal =  $_POST['txtPassword'];
					$request_user = "";
					if($idUsuario == 0)
					{
						$option = 1;
						$strPassword =  empty($_POST['txtPassword']) ? hash("SHA256",passGenerator()) : hash("SHA256",$_POST['txtPassword']);

						if($_SESSION['permisosMod']['w']){
						$request_user = $this->model->insertUsuario(
							$strNombre,
							$strApellido,
							$intTelefono,
							$strEmail,
							$strPassword,
							$intTipoId,
							$intStatus
						);

						if ($intEnviarCorreo == 1) {
							$dataUsuario = array(
								'nombreUsuario' => $strNombre,
								'email' => $strEmail,
								'password' => $strPasswordLineal,
								'asunto' => 'Bienvenido a MRP'
							);

							$correos_copia="carlos.cruz@ldrsolutions.com.mx,alejandro.hernandez@ldrsolutions.com.mx";

							sendMailLocal($dataUsuario,'email_bienvenida',$correos_copia);

						}
					}
				} else {
						$option = 2;
						$strPassword =  empty($_POST['txtPassword']) ? "" : hash("SHA256",$_POST['txtPassword']);
						if($_SESSION['permisosMod']['u']){
							$request_user = $this->model->updateUsuario($idUsuario,
																		$strNombre,
																		$strApellido, 
																		$intTelefono, 
																		$strEmail,
																		$strPassword, 
																		$intTipoId, 
																		$intStatus);
						}

					}

					if($request_user > 0 )
					{
						if($option == 1){
							$arrResponse = array('status' => true, 'msg' => 'Datos guardados correctamente.');
						}else{
							$arrResponse = array('status' => true, 'msg' => 'Datos Actualizados correctamente.');
						}
					}else if($request_user == 'exist'){
						$arrResponse = array('status' => false, 'msg' => '¡Atención! el email o la identificación ya existe, ingrese otro.');		
					}else{
						$arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
					}
				}
				echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
			}
			die();
		}



		public function getUsuarios()
		{
			if($_SESSION['permisosMod']['r']){
				$arrData = $this->model->selectUsuarios();
				for ($i=0; $i < count($arrData); $i++) {
					$btnView = '';
					$btnEdit = '';
					$btnDelete = '';

					if($arrData[$i]['status'] == 1)
					{
						$arrData[$i]['status'] = '<span class="badge bg-success">Activo</span>';
					}else{ 
						$arrData[$i]['status'] = '<span class="badge bg-danger">Inactivo</span>';
					}

					if($_SESSION['permisosMod']['r']){
						//$btnView = '<button class="btn btn-info btn-sm btnViewUsuario"  title="Ver usuario"><i class="far fa-eye"></i></button>';
						$btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver usuario" onClick="fntViewUsuario('.$arrData[$i]['idusuario'].')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';

					}
					if($_SESSION['permisosMod']['u']){
						if(($_SESSION['idUser'] == 1 and $_SESSION['userData']['idrol'] == 1) ||
							($_SESSION['userData']['idrol'] == 1 and $arrData[$i]['idrol'] != 1) ){
							$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar usuario" onClick="fntEditUsuario(this,'.$arrData[$i]['idusuario'].')"><i class="ri-pencil-fill align-bottom"></i></button>';

						}else{
							$btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar usuario" disabled><i class="ri-pencil-fill align-bottom"></i></button>';

						}
					}
					if($_SESSION['permisosMod']['d']){
						if(($_SESSION['idUser'] == 1 and $_SESSION['userData']['idrol'] == 1) ||
							($_SESSION['userData']['idrol'] == 1 and $arrData[$i]['idrol'] != 1) and
							($_SESSION['userData']['idusuario'] != $arrData[$i]['idusuario'] )
							 ){
							$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" onClick="fntDelUsuario('.$arrData[$i]['idusuario'].')"><i class="ri-delete-bin-5-fill align-bottom" title="Eliminar usuario"></i></button>';

						}else{
							$btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list disabled"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
						}
					}
					$arrData[$i]['options'] = '<div class="text-center">'.$btnView.' '.$btnEdit.' '.$btnDelete.'</div>';
				}
				echo json_encode($arrData,JSON_UNESCAPED_UNICODE);
			}
			die();
		}

		public function getUsuario($idusuario){
			if($_SESSION['permisosMod']['r']){
				$idusuario = intval($idusuario);
				if($idusuario > 0)
				{
					$arrData = $this->model->selectUsuario($idusuario);
					if(empty($arrData))
					{
						$arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
					}else{
						$arrResponse = array('status' => true, 'data' => $arrData);
					}
					echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
				}
			}
			die();
		}

		public function delUsuario()
		{
			if($_POST){
				if($_SESSION['permisosMod']['d']){
					$intIdusuario = intval($_POST['idUsuario']);
					$requestDelete = $this->model->deleteUsuario($intIdusuario);
					if($requestDelete)
					{
						$arrResponse = array('status' => true, 'msg' => 'Se ha eliminado el usuario');
					}else{
						$arrResponse = array('status' => false, 'msg' => 'Error al eliminar el usuario.');
					}
					echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
				}
			}
			die();
		}

		public function perfil(){
			$data['page_tag'] = "Perfil";
			$data['page_title'] = "Perfil de usuario";
			$data['page_name'] = "perfil";
			$data['page_functions_js'] = "functions_usuarios.js";

			 $data['usuario'] = $this->model->getUsuarioDatada();
			$this->views->getView($this,"perfil",$data);
		}

		public function putPerfil(){
			if($_POST){
				if(empty($_POST['txtIdentificacion']) || empty($_POST['txtNombre']) || empty($_POST['txtApellido']) || empty($_POST['txtTelefono']) )
				{
					$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
				}else{
					$idUsuario = $_SESSION['idUser'];
					$strIdentificacion = strClean($_POST['txtIdentificacion']);
					$strNombre = strClean($_POST['txtNombre']);
					$strApellido = strClean($_POST['txtApellido']);
					$intTelefono = intval(strClean($_POST['txtTelefono']));
					$strPassword = "";
					if(!empty($_POST['txtPassword'])){
						$strPassword = hash("SHA256",$_POST['txtPassword']);
					}
					$request_user = $this->model->updatePerfil($idUsuario,
																$strIdentificacion, 
																$strNombre,
																$strApellido, 
																$intTelefono, 
																$strPassword);
					if($request_user)
					{
						sessionUser($_SESSION['idUser']);
						$arrResponse = array('status' => true, 'msg' => 'Datos Actualizados correctamente.');
					}else{
						$arrResponse = array("status" => false, "msg" => 'No es posible actualizar los datos.');
					}
				}
				echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
			}
			die();
		}

		public function putDFical(){
			if($_POST){
				if(empty($_POST['txtNit']) || empty($_POST['txtNombreFiscal']) || empty($_POST['txtDirFiscal']) )
				{
					$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
				}else{
					$idUsuario = $_SESSION['idUser'];
					$strNit = strClean($_POST['txtNit']);
					$strNomFiscal = strClean($_POST['txtNombreFiscal']);
					$strDirFiscal = strClean($_POST['txtDirFiscal']);
					$request_datafiscal = $this->model->updateDataFiscal($idUsuario,
																		$strNit,
																		$strNomFiscal, 
																		$strDirFiscal);
					if($request_datafiscal)
					{
						sessionUser($_SESSION['idUser']);
						$arrResponse = array('status' => true, 'msg' => 'Datos Actualizados correctamente.');
					}else{
						$arrResponse = array("status" => false, "msg" => 'No es posible actualizar los datos.');
					}
				}
				echo json_encode($arrResponse,JSON_UNESCAPED_UNICODE);
			}
			die();
		}


// public function setAvatar()
// {
//     header('Content-Type: application/json; charset=utf-8');
//     date_default_timezone_set('America/Mexico_City');

//     // 1) leer JSON raw
//     $raw = file_get_contents("php://input");
//     $json = json_decode($raw, true);

//     if (json_last_error() !== JSON_ERROR_NONE || !is_array($json)) {
//         echo json_encode(['status' => false, 'msg' => 'Payload JSON inválido'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }

//     // 2) validar campos mínimos
//     $usuarioid = (int)($json['usuarioid'] ?? 0);
//     $seed      = trim((string)($json['seed'] ?? ''));
//     $gender    = trim((string)($json['gender'] ?? 'unisex'));
//     $options   = $json['options'] ?? null;
//     $svg       = (string)($json['svg'] ?? '');

//     if ($usuarioid <= 0) {
//         echo json_encode(['status' => false, 'msg' => 'usuarioid inválido'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }
//     if ($seed === '' || $svg === '') {
//         echo json_encode(['status' => false, 'msg' => 'Faltan datos (seed/svg)'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }

//     // 3) seguridad: validar gender
//     $allowedGender = ['male','female','unisex'];
//     if (!in_array($gender, $allowedGender, true)) $gender = 'unisex';

//     // 4) validar svg básico
//     if (stripos($svg, '<svg') === false) {
//         echo json_encode(['status' => false, 'msg' => 'SVG inválido'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }

//     // 5) carpeta destino (AJUSTA a tu estructura)
//     // Ej: /home/.../public_html/Assets/avatars/
//     $dir = dirname(__FILE__, 3) . '/Assets/avatars'; 
//     if (!is_dir($dir)) {
//         @mkdir($dir, 0755, true);
//     }
//     if (!is_dir($dir) || !is_writable($dir)) {
//         echo json_encode(['status' => false, 'msg' => 'Carpeta avatars no disponible'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }

//     // 6) nombre único
//     // Recomendado: usuarioid + fecha + random
//     $unique = $usuarioid . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
//     $filename = 'avatar_' . $unique . '.svg';
//     $filepath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;

//     // 7) normalizar svg (opcional)
//     $svgClean = trim($svg);

//     // 8) guardar archivo
//     $okFile = @file_put_contents($filepath, $svgClean);
//     if ($okFile === false) {
//         echo json_encode(['status' => false, 'msg' => 'No se pudo guardar el archivo SVG'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }

//     // 9) options a string JSON para BD
//     $optionsJson = '';
//     if (is_array($options) || is_object($options)) {
//         $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
//     } else {
//         // si te llega string ya en JSON
//         $optionsJson = (string)$options;
//     }

//     // 10) (opcional) borrar avatar anterior si existía
//     $old = $this->model->getAvatarByUser($usuarioid);
//     $oldFile = $old['avatar_file'] ?? '';
//     if (!empty($oldFile) && $oldFile !== $filename) {
//         $oldPath = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $oldFile;
//         if (is_file($oldPath)) @unlink($oldPath);
//     }

//     // 11) actualizar BD
//     $saved = $this->model->updateAvatar($usuarioid, $filename, $seed, $gender, $optionsJson);
//     if (!$saved) {
//         // rollback archivo si falla BD
//         if (is_file($filepath)) @unlink($filepath);
//         echo json_encode(['status' => false, 'msg' => 'No se pudo actualizar BD'], JSON_UNESCAPED_UNICODE);
//         exit;
//     }

//     // 12) responder url pública
//     // AJUSTA a tu helper (media()) si lo tienes:
//     // $url = media().'/avatars/'.$filename;
//     $url = (function_exists('media') ? media() : '') . '/avatars/' . $filename;

//     echo json_encode([
//         'status' => true,
//         'msg'    => 'Avatar guardado',
//         'file'   => $filename,
//         'url'    => $url,
//         'seed'   => $seed,
//         'gender' => $gender
//     ], JSON_UNESCAPED_UNICODE);
//     exit;
// }


public function setAvatar()
{
    header('Content-Type: application/json; charset=utf-8');

    $body = json_decode(file_get_contents('php://input'), true);

    $usuarioid = (int)($body['usuarioid'] ?? 0);
    $svg       = (string)($body['svg'] ?? '');
    $seed      = (string)($body['seed'] ?? '');
    $gender    = (string)($body['gender'] ?? 'unisex');
    $options   = $body['options'] ?? [];

    if ($usuarioid <= 0 || $svg === '') {
        echo json_encode(['status'=>false,'msg'=>'Faltan datos (usuarioid/svg)']);
        exit;
    }


    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');


    $dir = $docRoot . '/Assets/avatars';

    $debug = [
        'document_root' => $docRoot,
        'target_dir'    => $dir,
        'dir_exists'    => is_dir($dir),
        'dir_writable'  => is_writable($dir),
        'php_user'      => function_exists('get_current_user') ? get_current_user() : null,
    ];

    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
        $debug['dir_exists_after_mkdir'] = is_dir($dir);
        $debug['dir_writable_after_mkdir'] = is_writable($dir);
    }

    if (!is_dir($dir) || !is_writable($dir)) {
        echo json_encode([
            'status' => false,
            'msg'    => 'Carpeta no existe o no es escribible (permisos/ruta)',
            'debug'  => $debug
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    date_default_timezone_set('America/Mexico_City');
    $ts   = date('YmdHis');
    $rand = bin2hex(random_bytes(4));

    $fileName = "avatar_{$usuarioid}_{$ts}_{$rand}.svg";
    $fullPath = $dir . '/' . $fileName;


    $bytes = file_put_contents($fullPath, $svg, LOCK_EX);

    if ($bytes === false || $bytes <= 0) {
        echo json_encode([
            'status' => false,
            'msg'    => 'No se pudo escribir el SVG en disco',
            'debug'  => array_merge($debug, [
                'fullPath' => $fullPath
            ])
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }


    $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);

    $ok = $this->model->updateAvatarUsuario($usuarioid, $fileName, $seed, $gender, $optionsJson);

    if (!$ok) {
        @unlink($fullPath);
        echo json_encode(['status'=>false,'msg'=>'Se guardó el archivo pero falló BD, se revirtió.']);
        exit;
    }

    echo json_encode([
        'status' => true,
        'msg'    => 'Avatar guardado correctamente',
        'file'   => $fileName,
        'bytes'  => $bytes,
        'url'    => media() . '/avatars/' . $fileName
    ], JSON_UNESCAPED_UNICODE);
    exit;
}


		public function setAvatarUpload(){
			dep($_POST);
		}

	}
 ?>