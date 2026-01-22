    <?php

    class Cli_regionalesModel extends Mysql
    {
        public $intIdregional;
        public $strNombre;
        public $strApellido_paterno;
        public $strApellido_materno;
        public $intEstado;

        public function __construct()
        {
            parent::__construct();
        }

        public function selectRegionales()
        {
            $sql = "SELECT * FROM  cli_regionales 
                        WHERE estado != 0 ";
            $request = $this->select_all($sql);
            return $request;
        }

        public function selectRegional(int $idregional)
        {
            $this->intIdregional = $idregional;
            $sql = "SELECT * FROM cli_regionales WHERE id = $this->intIdregional";
            $request = $this->select($sql);
            return $request;
        }

        public function deleteRegional(int $idregional)
        {
            $this->intIdregional = $idregional;
            $sql = "UPDATE cli_regionales SET estado = ? WHERE id = $this->intIdregional ";
            $arrData = array(0);
            $request = $this->update($sql, $arrData);
            return $request;
        }

        public function insertRegional($nombre, $apellido_paterno, $apellido_materno, $estado)
        {
            $this->strNombre = $nombre;
            $this->strApellido_paterno = $apellido_paterno;
            $this->strApellido_materno = $apellido_materno;
            $this->intEstado = $estado;

            $sql = "SELECT * FROM cli_regionales 
            WHERE nombre = '{$this->strNombre}' 
            OR apellido_paterno = '{$this->strApellido_paterno}' 
            OR apellido_materno = '{$this->strApellido_materno}'";
            $request = $this->select_all($sql);

            if (empty($request)) {
                $query_insert = "INSERT INTO cli_regionales(nombre, apellido_paterno, apellido_materno, estado) 
                         VALUES(?,?,?,?)";
                $arrData = array(
                    $this->strNombre,
                    $this->strApellido_paterno,
                    $this->strApellido_materno,
                    $this->intEstado
                );
                $request_insert = $this->insert($query_insert, $arrData);
                return $request_insert;
            } else {
                return "exist";
            }
        }

        public function updateRegional($intIdregional, $nombre, $apellido_paterno, $apellido_materno, $estado)
        {
            $this->intIdregional = $intIdregional;
            $this->strNombre = $nombre;
            $this->strApellido_paterno = $apellido_paterno;
            $this->strApellido_materno = $apellido_materno;
            $this->intEstado = $estado;

            $sql = "SELECT * FROM cli_regionales 
            WHERE (nombre = '{$this->strNombre}' 
            OR apellido_paterno = '{$this->strApellido_paterno}' 
            OR apellido_materno = '{$this->strApellido_materno}') 
            AND id != $this->intIdregional";
            $request = $this->select_all($sql);

            if (empty($request)) {
                $sql = "UPDATE cli_regionales 
                SET nombre = ?, 
                    apellido_paterno = ?, 
                    apellido_materno = ?, 
                    estado = ? 
                WHERE id = $this->intIdregional";
                $arrData = array(
                    $this->strNombre,
                    $this->strApellido_paterno,
                    $this->strApellido_materno,
                    $this->intEstado
                );
                $request = $this->update($sql, $arrData);
                return $request;
            } else {
                return "exist";
            }
        }
    }
