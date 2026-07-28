<?php

class Prv_madrinasModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    public function getMadrinas(): array {
        $sql = "SELECT 
                    m.id_madrina,
                    m.id_proveedor,
                    p.razon_social AS trasladista,
                    m.numero_economico,
                    m.placas,
                    m.marca,
                    m.modelo,
                    m.capacidad_vehiculos,
                    m.estatus_operativo,
                    m.created_at
                FROM prv_det_madrinas m
                INNER JOIN prv_cat_proveedores p ON p.id_proveedor = m.id_proveedor
                WHERE m.deleted_at IS NULL
                ORDER BY m.id_madrina DESC";
        return $this->select_all($sql);
    }

    public function getMadrina(int $id): ?array {
        $sql = "SELECT * FROM prv_det_madrinas WHERE id_madrina = ? AND deleted_at IS NULL";
        $res = $this->select($sql, [$id]);
        return $res ?: null;
    }

    public function insertMadrina(array $data, int $userId): int {
        $sql = "INSERT INTO prv_det_madrinas (
                    id_proveedor,
                    numero_economico,
                    placas,
                    marca,
                    modelo,
                    capacidad_vehiculos,
                    estatus_operativo,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, 1, ?)";

        $params = [
            intval($data['id_proveedor']),
            trim($data['numero_economico']),
            strtoupper(trim($data['placas'])),
            trim($data['marca'] ?? ''),
            trim($data['modelo'] ?? ''),
            intval($data['capacidad_vehiculos'] ?? 1),
            $userId
        ];

        return (int)$this->insert($sql, $params);
    }

    public function updateMadrina(int $id, array $data, int $userId): bool {
        $sql = "UPDATE prv_det_madrinas 
                SET id_proveedor = ?,
                    numero_economico = ?,
                    placas = ?,
                    marca = ?,
                    modelo = ?,
                    capacidad_vehiculos = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id_madrina = ?";

        $params = [
            intval($data['id_proveedor']),
            trim($data['numero_economico']),
            strtoupper(trim($data['placas'])),
            trim($data['marca'] ?? ''),
            trim($data['modelo'] ?? ''),
            intval($data['capacidad_vehiculos'] ?? 1),
            $userId,
            $id
        ];

        return (bool)$this->update($sql, $params);
    }

    public function deleteMadrina(int $id, int $userId): bool {
        $sql = "UPDATE prv_det_madrinas SET estatus_operativo = 0, deleted_by = ?, deleted_at = NOW() WHERE id_madrina = ?";
        return (bool)$this->update($sql, [$userId, $id]);
    }
}
