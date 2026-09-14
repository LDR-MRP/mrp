<?php

namespace Scripts;

class MassPasswordHasher extends \Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Script para encriptar contraseñas en texto plano de forma masiva.
     * Ideal para ejecutarse una sola vez durante la migración inicial.
     */
    public function runMigration(): void
    {
        // 1. Traer los registros que necesitan ser encriptados
        // (Asumiendo que los insertaste con un flag o que su hash no empieza con '$2y$')
        $query = "SELECT id, password FROM prv_cat_usuarios WHERE password NOT LIKE '$2y$%'";
        $usuarios = $this->select_all($query); // Asumo que select_all trae múltiples filas

        if (empty($usuarios)) {
            echo "No hay contraseñas pendientes por encriptar.\n";
            return;
        }

        $procesados = 0;

        // 2. Transacción para asegurar la integridad de la carga masiva
        $this->getConexion()->beginTransaction();

        try {
            $updateQuery = "UPDATE prv_cat_usuarios SET password = :password WHERE id = :id";

            foreach ($usuarios as $user) {
                // Encriptar la contraseña en texto plano
                $hashSeguro = password_hash($user['password'], PASSWORD_BCRYPT);

                // Ejecutar el update
                $this->update($updateQuery, [
                    ':password' => $hashSeguro,
                    ':id'       => $user['id']
                ]);

                $procesados++;
            }

            // Confirmar los cambios
            $this->getConexion()->commit();
            echo "Migración exitosa. {$procesados} contraseñas encriptadas con BCRYPT.\n";

        } catch (\Exception $e) {
            // Revertir si algo falla
            $this->getConexion()->rollBack();
            echo "Error en la migración: " . $e->getMessage() . "\n";
        }
    }
}