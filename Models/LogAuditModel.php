<?php

class LogAuditModel extends Mysql{
    public function __construct()
    {
        parent::__construct();
    }

    public function register(array $data)
    {
        return $this->insert("INSERT INTO log_audit (
                    resourceid, 
                    usuarioid, 
                    nombre_tabla, 
                    accion, 
                    comentario
                ) VALUES (?, ?, ?, ?, ?)",
                [
                    $data['resource_id'],
                    $data['user_id'],
                    $data['table_name'],
                    $data['action'],
                    $data['comment'],
                ]
        ) > 0;
    }

    public function list(array $filters = [])
    {
        $query ="SELECT 
                -- data log
                nombre_tabla,
                accion,
                comentario,
                created_at,
                -- data usuarios
                CONCAT(usuarios.nombres,' ',usuarios.apellidos) as usuario
            FROM log_audit
            LEFT JOIN usuarios
                ON usuarios.idusuario = log_audit.usuarioid
            WHERE true
            ";

        if(array_key_exists('nombre_tabla', $filters)) {
            $query .= "AND log_audit.nombre_tabla = '{$filters['nombre_tabla']}'";
        }

        if(array_key_exists('resource_id', $filters)) {
            $query .= "AND log_audit.resourceid = '{$filters['resource_id']}'";
        }

        return $this->select_all($query);
    }
}