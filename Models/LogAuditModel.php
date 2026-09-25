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
                log_audit.resourceid,
                log_audit.usuarioid,
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

        // nombre_tabla acepta un string (una sola tabla) o un array (varias
        // tablas vía IN), útil para pantallas que agrupan varios catálogos
        // relacionados (p. ej. la bitácora de Ingeniería).
        if(array_key_exists('nombre_tabla', $filters)) {
            if (is_array($filters['nombre_tabla'])) {
                $tablas = array_map(function ($t) {
                    return "'" . addslashes($t) . "'";
                }, $filters['nombre_tabla']);
                if (!empty($tablas)) {
                    $query .= "AND log_audit.nombre_tabla IN (" . implode(',', $tablas) . ")";
                }
            } else {
                $query .= "AND log_audit.nombre_tabla = '" . addslashes($filters['nombre_tabla']) . "'";
            }
        }

        if(array_key_exists('resource_id', $filters)) {
            $query .= "AND log_audit.resourceid = '{$filters['resource_id']}'";
        }

        if(array_key_exists('usuarioid', $filters) && $filters['usuarioid'] !== '' && $filters['usuarioid'] !== null) {
            $query .= "AND log_audit.usuarioid = " . intval($filters['usuarioid']) . " ";
        }

        if(array_key_exists('accion', $filters) && $filters['accion'] !== '') {
            $query .= "AND log_audit.accion = '" . addslashes($filters['accion']) . "' ";
        }

        if(array_key_exists('fecha_desde', $filters) && !empty($filters['fecha_desde'])) {
            $query .= "AND log_audit.created_at >= '" . addslashes($filters['fecha_desde']) . " 00:00:00' ";
        }

        if(array_key_exists('fecha_hasta', $filters) && !empty($filters['fecha_hasta'])) {
            $query .= "AND log_audit.created_at <= '" . addslashes($filters['fecha_hasta']) . " 23:59:59' ";
        }

        $query .= " ORDER BY id DESC";
        
        if(array_key_exists('limit', $filters)) {
            $query .= " LIMIT {$filters['limit']}";
        }

        return $this->select_all($query);
    }
}