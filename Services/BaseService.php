<?php
namespace Services;

use PDO;

abstract class BaseService
{
    /** @var PDO La instancia de conexión a la base de datos */
    protected PDO $db;

    /**
     * El constructor ACEPTA la conexión, no la crea.
     * Esto lo hace completamente reutilizable para cualquier modelo.
     * @param PDO $dbConnection La conexión ya establecida
     */
    public function __construct(PDO $dbConnection) {
        $this->db = $dbConnection;
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
}
