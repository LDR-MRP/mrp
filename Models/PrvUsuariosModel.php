<?php

class PrvUsuariosModel extends Mysql
{
    private string $table = 'prv_cat_usuarios';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Busca un usuario proveedor por su correo electrónico.
     * 
     * @param string $email Correo electrónico del proveedor.
     * @return array|null Retorna el array asociativo del usuario o null si no existe.
     */
    public function findByEmail(string $email): ?array
    {
        // Solo traemos las columnas estrictamente necesarias para el login
        // y validamos que no sea un registro eliminado lógicamente (Soft Delete)
        $query = "SELECT 
                    id, 
                    proveedor_id, 
                    email, 
                    password, 
                    nombre_contacto, 
                    estatus 
                  FROM {$this->table} 
                  WHERE email = :email 
                    AND deleted_at IS NULL 
                  LIMIT 1";

        $params = [
            ':email' => $email
        ];

        $request = $this->select($query, $params);

        return !empty($request) ? $request : null;
    }

    /**
     * Actualiza la fecha y hora del último acceso del proveedor.
     * 
     * @param int $id ID del registro en prv_cat_usuarios.
     * @return bool True si se actualizó correctamente, False en caso contrario.
     */
    public function updateLastLogin(int $id): bool
    {
        // Usamos CURRENT_TIMESTAMP nativo de MySQL
        $query = "UPDATE {$this->table} 
                  SET ultimo_acceso = CURRENT_TIMESTAMP 
                  WHERE id = :id";

        $params = [
            ':id' => $id
        ];

        $request = $this->update($query, $params);

        return (bool) $request;
    }
}