<?php

class Prv_choferesModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    public function getChoferes(): array {
        $sql = "SELECT 
                    c.id_chofer,
                    c.id_proveedor,
                    p.razon_social AS trasladista,
                    c.nombre,
                    c.apellidos,
                    CONCAT(c.nombre, ' ', c.apellidos) AS nombre_completo,
                    c.num_licencia,
                    c.tipo_licencia,
                    c.vigencia_licencia,
                    c.telefono,
                    c.estatus_operativo,
                    c.created_at
                FROM prv_det_choferes c
                INNER JOIN prv_cat_proveedores p ON p.id_proveedor = c.id_proveedor
                WHERE c.deleted_at IS NULL
                ORDER BY c.id_chofer DESC";
        return $this->select_all($sql);
    }

    public function getChofer(int $id): ?array {
        $sql = "SELECT * FROM prv_det_choferes WHERE id_chofer = ? AND deleted_at IS NULL";
        $res = $this->select($sql, [$id]);
        return $res ?: null;
    }

    public function insertChofer(array $data, int $userId): int {
        $sql = "INSERT INTO prv_det_choferes (
                    id_proveedor,
                    nombre,
                    apellidos,
                    num_licencia,
                    tipo_licencia,
                    vigencia_licencia,
                    telefono,
                    estatus_operativo,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?)";

        $params = [
            intval($data['id_proveedor']),
            trim($data['nombre']),
            trim($data['apellidos']),
            strtoupper(trim($data['num_licencia'])),
            strtoupper(trim($data['tipo_licencia'] ?? 'A')),
            !empty($data['vigencia_licencia']) ? $data['vigencia_licencia'] : null,
            trim($data['telefono'] ?? ''),
            $userId
        ];

        return (int)$this->insert($sql, $params);
    }

    public function updateChofer(int $id, array $data, int $userId): bool {
        $sql = "UPDATE prv_det_choferes 
                SET id_proveedor = ?,
                    nombre = ?,
                    apellidos = ?,
                    num_licencia = ?,
                    tipo_licencia = ?,
                    vigencia_licencia = ?,
                    telefono = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id_chofer = ?";

        $params = [
            intval($data['id_proveedor']),
            trim($data['nombre']),
            trim($data['apellidos']),
            strtoupper(trim($data['num_licencia'])),
            strtoupper(trim($data['tipo_licencia'] ?? 'A')),
            !empty($data['vigencia_licencia']) ? $data['vigencia_licencia'] : null,
            trim($data['telefono'] ?? ''),
            $userId,
            $id
        ];

        return (bool)$this->update($sql, $params);
    }

    public function deleteChofer(int $id, int $userId): bool {
        $sql = "UPDATE prv_det_choferes SET estatus_operativo = 0, deleted_by = ?, deleted_at = NOW() WHERE id_chofer = ?";
        return (bool)$this->update($sql, [$userId, $id]);
    }
}
