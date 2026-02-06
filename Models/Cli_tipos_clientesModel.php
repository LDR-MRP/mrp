    <?php

    class Cli_tipos_clientesModel extends Mysql
    {
        public $intIdTipoCliente;
        public $strNombre;
        public $strDescripcion;

        public function __construct()
        {
            parent::__construct();
        }

        public function selectTiposClientes()
        {
            $sql = "SELECT * FROM  cli_tipos_cliente 
                        WHERE estado != 0 ";
            $request = $this->select_all($sql);
            return $request;
        }

        public function selectTipoCliente(int $idtipocliente)
        {
            $this->intIdTipoCliente = $idtipocliente;

            $sqlTipoCliente = "SELECT 
                    id,
                    nombre,
                    descripcion,
                    fecha_registro,
                    estado
                 FROM cli_tipos_cliente
                 WHERE id = {$this->intIdTipoCliente}
                 AND estado != 0";

            $tipocliente = $this->select($sqlTipoCliente);

            if (empty($tipocliente)) {
                return null;
            }

            return $tipocliente;
        }

        public function deleteTipoCliente(int $idtipocliente)
        {
            $this->intIdTipoCliente = $idtipocliente;
            $sql = "UPDATE cli_tipos_cliente SET estado = ? WHERE id = $this->intIdTipoCliente ";
            $arrData = array(0);
            $request = $this->update($sql, $arrData);
            return $request;
        }

        public function insertTipoCliente($nombre, $descripcion)
        {
            $this->strNombre = $nombre;
            $this->strDescripcion = $descripcion;

            $sql = "SELECT * FROM cli_tipos_cliente 
            WHERE nombre = '{$this->strNombre}'";
            $request = $this->select_all($sql);

            if (empty($request)) {
                $query_insert = "INSERT INTO cli_tipos_cliente(nombre, descripcion) 
                         VALUES(?,?)";
                $arrData = array(
                    $this->strNombre,
                    $this->strDescripcion,
                );
                $request_insert = $this->insert($query_insert, $arrData);
                return $request_insert;
            } else {
                return "exist";
            }
        }

        public function updateTipoCliente($intIdTipoCliente, $nombre, $descripcion)
        {
            $this->intIdTipoCliente = $intIdTipoCliente;
            $this->strNombre = $nombre;
            $this->strDescripcion = $descripcion;

            $sql = "SELECT * FROM cli_tipos_cliente 
            WHERE (nombre = '{$this->strNombre}') 
            AND id != $this->intIdTipoCliente";
            $request = $this->select_all($sql);

            if (empty($request)) {
                $sql = "UPDATE cli_tipos_cliente 
                SET nombre = ?, 
                    descripcion = ?
                WHERE id = $this->intIdTipoCliente";
                $arrData = array(
                    $this->strNombre,
                    $this->strDescripcion,
                );
                $request = $this->update($sql, $arrData);
                return $request;
            } else {
                return "exist";
            }
        }
    }
