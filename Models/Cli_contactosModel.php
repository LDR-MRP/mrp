<?php

class Cli_contactosModel extends Mysql
{
    public $intIdcontacto;
    public $intDistribuidorId;
    public $intPuestoId;
    public $strNombre;
    public $strCorreo;
    public $strExtension;
    public $strTelefono;
    public $intEstado;

    public function __construct()
    {
        parent::__construct();
    }

    public function selectContactos()
    {
        $sql = "SELECT 
                c.id,
                d.nombre_comercial AS nombre_distribuidor,
                p.nombre AS nombre_puesto,
                c.nombre AS nombre_contacto,
                c.correo,
                c.telefono,
                c.fecha_registro,
                c.estado
            FROM cli_contactos c
            INNER JOIN cli_puestos p 
                ON c.puesto_id = p.id
            INNER JOIN cli_distribuidores d 
                ON c.distribuidor_id = d.id
            WHERE c.estado != 0";

        $request = $this->select_all($sql);
        return $request;
    }

    public function selectContacto(int $idcontacto)
    {
        $this->intIdcontacto = $idcontacto;

        $sql = "SELECT 
                c.id,
                d.nombre_comercial AS nombre_distribuidor,
                p.nombre AS nombre_puesto,
                c.nombre AS nombre_contacto,
                c.correo,
                c.extension,
                c.telefono,
                c.fecha_registro,
                c.estado,
                c.distribuidor_id,
                p.id AS puesto_id
            FROM cli_contactos c
            INNER JOIN cli_puestos p 
                ON c.puesto_id = p.id
            INNER JOIN cli_distribuidores d 
                ON c.distribuidor_id = d.id
            WHERE c.id = $this->intIdcontacto
              AND c.estado != 0";

        $request = $this->select($sql);
        return $request;
    }

    public function deleteContacto(int $idcontacto)
    {
        $this->intIdcontacto = $idcontacto;
        $sql = "UPDATE cli_contactos SET estado = ? WHERE id = $this->intIdcontacto ";
        $arrData = array(0);
        $request = $this->update($sql, $arrData);
        return $request;
    }

    public function insertContacto($distribuidor_id, $puesto_id, $nombre, $correo, $extension, $telefono, $estado)
    {
        $this->intDistribuidorId = $distribuidor_id;
        $this->intPuestoId = $puesto_id;
        $this->strNombre = $nombre;
        $this->strCorreo = $correo;
        $this->strExtension = $extension;
        $this->strTelefono = $telefono;
        $this->intEstado = $estado;

        $sql = "SELECT * FROM cli_contactos WHERE correo = '{$this->strCorreo}' OR telefono = '{$this->strTelefono}'";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $query_insert = "INSERT INTO cli_contactos(distribuidor_id, puesto_id, nombre, correo, extension, telefono, estado) VALUES(?,?,?,?,?,?,?)";
            $arrData = array(
                $this->intDistribuidorId,
                $this->intPuestoId,
                $this->strNombre,
                $this->strCorreo,
                $this->strExtension,
                $this->strTelefono,
                $this->intEstado
            );
            $request_insert = $this->insert($query_insert, $arrData);
            return $request_insert;
        } else {
            return "exist";
        }
    }

    public function updateContacto($intIdcontacto, $distribuidor_id, $puesto_id, $nombre, $correo, $extension, $telefono, $estado)
    {
        $this->intIdcontacto = $intIdcontacto;
        $this->intDistribuidorId = $distribuidor_id;
        $this->intPuestoId = $puesto_id;
        $this->strNombre = $nombre;
        $this->strCorreo = $correo;
        $this->strExtension = $extension;
        $this->strTelefono = $telefono;
        $this->intEstado = $estado;

        $sql = "SELECT * FROM cli_contactos WHERE (correo = '$this->strCorreo' OR telefono = '$this->strTelefono') AND id != $this->intIdcontacto";
        $request = $this->select_all($sql);

        if (empty($request)) {
            $sql = "UPDATE cli_contactos SET distribuidor_id = ?, puesto_id = ?, nombre = ?, correo = ?, extension = ?, telefono = ?, estado = ? WHERE id = $this->intIdcontacto ";
            $arrData = array(
                $this->intDistribuidorId,
                $this->intPuestoId,
                $this->strNombre,
                $this->strCorreo,
                $this->strExtension,
                $this->strTelefono,
                $this->intEstado
            );
            $request = $this->update($sql, $arrData);
            return $request;
        } else {
            return "exist";
        }
    }

    public function selectOptionDistribuidores()
    {
        $sql = "SELECT * FROM  cli_distribuidores 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }

    public function selectOptionPuestos()
    {
        $sql = "SELECT * FROM  cli_puestos 
                    WHERE estado = 2";
        $request = $this->select_all($sql);
        return $request;
    }
}
