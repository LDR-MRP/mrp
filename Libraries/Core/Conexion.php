<?php
class Conexion{
	private static $instance = null;

	private $conect;

	public function __construct(){
		$connectionString = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET;
		try{
			$this->conect = new PDO($connectionString, DB_USER, DB_PASSWORD);
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