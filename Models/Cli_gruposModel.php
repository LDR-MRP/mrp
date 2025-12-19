    <?php

    class Cli_gruposModel extends Mysql
    {
        public $intIdgrupo;
        public $strCodigo;
        public $strNombre;
        public $strDescripcion;
        public $intEstado;

        public function __construct()
        {
            parent::__construct();
        }

        public function selectGrupos()
        {
            $sql = "SELECT * FROM  cli_grupos 
                        WHERE estado != 0 ";
            $request = $this->select_all($sql);
            return $request;
        }

        public function selectGrupo(int $idgrupo)
        {
            $this->intIdgrupo = $idgrupo;
            $sql = "SELECT * FROM cli_grupos WHERE id = $this->intIdgrupo";
            $request = $this->select($sql);
            return $request;
        }

        public function deleteGrupo(int $idgrupo)
        {
            $this->intIdgrupo = $idgrupo;
            $sql = "UPDATE cli_grupos SET estado = ? WHERE id = $this->intIdgrupo ";
            $arrData = array(0);
            $request = $this->update($sql, $arrData);
            return $request;
        }


        public function insertGrupo($codigo, $nombre, $descripcion, $estado)
        {
            $this->strCodigo = $codigo;
            $this->strNombre = $nombre;
            $this->strDescripcion = $descripcion;
            $this->intEstado = $estado;

            $sql = "SELECT * FROM cli_grupos 
            WHERE nombre = '{$this->strNombre}' 
            OR codigo = '{$this->strCodigo}'";
            $request = $this->select_all($sql);

            if (empty($request)) {
                $query_insert = "INSERT INTO cli_grupos(nombre, codigo, descripcion, estado) 
                         VALUES(?,?,?,?)";
                $arrData = array(
                    $this->strNombre,
                    $this->strCodigo,
                    $this->strDescripcion,
                    $this->intEstado
                );
                $request_insert = $this->insert($query_insert, $arrData);
                return $request_insert;
            } else {
                return "exist";
            }
        }


        public function updateGrupo($intIdgrupo, $codigo, $nombre, $descripcion, $estado)
        {
            $this->intIdgrupo = $intIdgrupo;
            $this->strCodigo = $codigo;
            $this->strNombre = $nombre;
            $this->strDescripcion = $descripcion;
            $this->intEstado = $estado;

            $sql = "SELECT * FROM cli_grupos 
            WHERE (nombre = '{$this->strNombre}' 
            OR codigo = '{$this->strCodigo}') 
            AND id != $this->intIdgrupo";
            $request = $this->select_all($sql);

            if (empty($request)) {
                $sql = "UPDATE cli_grupos 
                SET nombre = ?, 
                    codigo = ?, 
                    descripcion = ?, 
                    estado = ? 
                WHERE id = $this->intIdgrupo";
                $arrData = array(
                    $this->strNombre,
                    $this->strCodigo,
                    $this->strDescripcion,
                    $this->intEstado
                );
                $request = $this->update($sql, $arrData);
                return $request;
            } else {
                return "exist";
            }
        }
    }
