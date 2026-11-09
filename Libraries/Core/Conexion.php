<?php
class Conexion{
	private static $instance = null;

	private $conect;

	public function __construct(){
		$host = getenv('DB_HOST') ?: DB_HOST;
		$dbName = getenv('DB_NAME') ?: DB_NAME;
		$user = getenv('DB_USER') ?: DB_USER;
		$pass = getenv('DB_PASSWORD') ?: DB_PASSWORD;
		$connectionString = "mysql:host=".$host.";dbname=".$dbName.";charset=".DB_CHARSET;
		try{
			$this->conect = new PDO($connectionString, $user, $pass);
			$this->conect->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    //echo "conexión exitosa";
		}catch(PDOException $e){
			$this->conect = 'Error de conexión';
		    echo "ERROR: " . $e->getMessage();
		}
	}

	public static function getInstance()
	{
		if (self::$instance === null) {
            self::$instance = new Conexion();
        }
        return self::$instance;
	}

	public function conect(){
		return $this->conect;
	}
}

?>