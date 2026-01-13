<?php 

	class UsuariosModel extends Mysql
	{
		private $intIdUsuario;
		
		private $strNombre;
		private $strApellido;
		private $intTelefono;
		private $strEmail;
		private $strPassword;
		private $strToken;
		private $intTipoId;
		private $intStatus;
		private $strNit;
		private $strNomFiscal;
		private $strDirFiscal;
		private $strAvatar;

		public function __construct()
		{
			parent::__construct();
		}	

		public function insertUsuario(string $nombre, string $apellido, int $telefono, string $email, string $password, int $tipoid, int $status){

	
			$this->strNombre = $nombre;
			$this->strApellido = $apellido;
			$this->intTelefono = $telefono;
			$this->strEmail = $email;
			$this->strPassword = $password;
			$this->intTipoId = $tipoid;
			$this->intStatus = $status;
			$return = 0;

			$sql = "SELECT * FROM usuarios WHERE 
					email_user = '{$this->strEmail}'";
			$request = $this->select_all($sql);

			if(empty($request))
			{
				$query_insert  = "INSERT INTO usuarios(nombres,apellidos,telefono,email_user,password,rolid,status) 
								  VALUES(?,?,?,?,?,?,?)";
	        	$arrData = array($this->strNombre,
        						$this->strApellido,
        						$this->intTelefono,
        						$this->strEmail,
        						$this->strPassword,
        						$this->intTipoId,
        						$this->intStatus);
	        	$request_insert = $this->insert($query_insert,$arrData);
	        	$return = $request_insert;
			}else{
				$return = "exist";
			}
	        return $return;
		}

		public function selectUsuarios()
		{
			$whereAdmin = "";
			if($_SESSION['idUser'] != 1 ){
				$whereAdmin = " and p.idusuario != 1 ";
			}
			$sql = "SELECT p.idusuario,p.nombres,p.apellidos,p.telefono,p.email_user,p.status,r.idrol,r.nombrerol 
					FROM usuarios p 
					INNER JOIN rol r
					ON p.rolid = r.idrol
					WHERE p.status != 0 ".$whereAdmin;
					$request = $this->select_all($sql);
					return $request;
		}
		public function selectUsuario(int $idusuario){
			$this->intIdUsuario = $idusuario;
			$sql = "SELECT p.idusuario,p.nombres,p.apellidos,p.telefono,p.email_user,p.nit,p.nombrefiscal,p.direccionfiscal,r.idrol,r.nombrerol,p.status, DATE_FORMAT(p.datecreated, '%d-%m-%Y') as fechaRegistro 
					FROM usuarios p
					INNER JOIN rol r
					ON p.rolid = r.idrol
					WHERE p.idusuario = $this->intIdUsuario";
			$request = $this->select($sql);
			return $request;
		}

				public function getUsuarioDatada(){

				$usuarioId=	$_SESSION['idUser'];
			// $this->intIdUsuario = $idusuario;
			$sql = "SELECT p.idusuario,p.nombres,p.apellidos,p.telefono,p.email_user,p.nit,p.nombrefiscal,p.direccionfiscal,r.idrol,r.nombrerol,p.status, avatar_file,avatar_seed,avatar_gender,avatar_options 
					FROM usuarios p
					INNER JOIN rol r
					ON p.rolid = r.idrol
					WHERE p.idusuario = $usuarioId";
			$request = $this->select($sql);
			return $request;
		}


		

		public function updateUsuario(int $idUsuario, string $nombre, string $apellido, int $telefono, string $email, string $password, int $tipoid, int $status){

			$this->intIdUsuario = $idUsuario;

			$this->strNombre = $nombre;
			$this->strApellido = $apellido;
			$this->intTelefono = $telefono;
			$this->strEmail = $email;
			$this->strPassword = $password;
			$this->intTipoId = $tipoid;
			$this->intStatus = $status;

			$sql = "SELECT * FROM usuarios WHERE (email_user = '{$this->strEmail}' AND idusuario != $this->intIdUsuario)
										   AND idusuario != $this->intIdUsuario) ";
			$request = $this->select_all($sql);

			if(empty($request))
			{
				if($this->strPassword  != "")
				{
					$sql = "UPDATE usuarios  nombres=?, apellidos=?, telefono=?, email_user=?, password=?, rolid=?, status=? 
							WHERE idusuario = $this->intIdUsuario ";
					$arrData = array($this->strNombre,
	        						$this->strApellido,
	        						$this->intTelefono,
	        						$this->strEmail,
	        						$this->strPassword,
	        						$this->intTipoId,
	        						$this->intStatus);
				}else{
					$sql = "UPDATE usuarios SET  nombres=?, apellidos=?, telefono=?, email_user=?, rolid=?, status=? 
							WHERE idusuario = $this->intIdUsuario ";
					$arrData = array($this->strNombre,
	        						$this->strApellido,
	        						$this->intTelefono,
	        						$this->strEmail,
	        						$this->intTipoId,
	        						$this->intStatus);
				}
				$request = $this->update($sql,$arrData);
			}else{
				$request = "exist";
			}
			return $request;
		
		}
		public function deleteUsuario(int $intIdusuario)
		{
			$this->intIdUsuario = $intIdusuario;
			$sql = "UPDATE usuarios SET status = ? WHERE idusuario = $this->intIdUsuario ";
			$arrData = array(0);
			$request = $this->update($sql,$arrData);
			return $request;
		}

		public function updatePerfil(int $idUsuario, string $nombre, string $apellido, int $telefono, string $password){
			$this->intIdUsuario = $idUsuario;
			$this->strNombre = $nombre;
			$this->strApellido = $apellido;
			$this->intTelefono = $telefono;
			$this->strPassword = $password;

			if($this->strPassword != "")
			{
				$sql = "UPDATE usuarios SET  nombres=?, apellidos=?, telefono=?, password=? 
						WHERE idusuario = $this->intIdUsuario ";
				$arrData = array($this->strNombre,
								$this->strApellido,
								$this->intTelefono,
								$this->strPassword);
			}else{
				$sql = "UPDATE usuarios SET  nombres=?, apellidos=?, telefono=? 
						WHERE idusuario = $this->intIdUsuario ";
				$arrData = array($this->strNombre,
								$this->strApellido,
								$this->intTelefono);
			}
			$request = $this->update($sql,$arrData);
		    return $request;
		}

		public function updateDataFiscal(int $idUsuario, string $strNit, string $strNomFiscal, string $strDirFiscal){
			$this->intIdUsuario = $idUsuario;
			$this->strNit = $strNit;
			$this->strNomFiscal = $strNomFiscal;
			$this->strDirFiscal = $strDirFiscal;
			$sql = "UPDATE usuarios SET nit=?, nombrefiscal=?, direccionfiscal=? 
						WHERE idusuario = $this->intIdUsuario ";
			$arrData = array($this->strNit,
							$this->strNomFiscal,
							$this->strDirFiscal);
			$request = $this->update($sql,$arrData);
		    return $request;
		}

		public function updateAvatarUser(int $idUsuario, string $avatar){
			$this->intIdUsuario = $idUsuario;
			$this->strAvatar = $avatar;
			$sql = "UPDATE usuarios SET avatar=?
						WHERE idusuario = $this->intIdUsuario ";
			$arrData = array($this->strAvatar);
			$request = $this->update($sql,$arrData);
		    return $request;

		}



		public function getAvatarByUser(int $usuarioid)
{
	$this->intIdUsuario = $usuarioid;
    $sql = "SELECT idusuario, avatar_file, avatar_seed, avatar_gender, avatar_options
            FROM usuarios
            WHERE idusuario = $this->intIdUsuario";
    // return $this->select($sql, [$usuarioid]);


				$request = $this->select($sql);
			return $request;
}

public function updateAvatarUsuario(int $usuarioid, string $filename, string $seed, string $gender, string $optionsJson)
{
    $sql = "UPDATE usuarios
            SET avatar_file = ?,
                avatar_seed = ?,
                avatar_gender = ?,
                avatar_options = ?,
                avatar_updated_at = NOW()
            WHERE idusuario = ?";
    return $this->update($sql, [$filename, $seed, $gender, $optionsJson, $usuarioid]);
}


	}
 ?>