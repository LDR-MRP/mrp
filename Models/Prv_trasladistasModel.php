<?php

class Prv_trasladistasModel extends Mysql {

    public function __construct() {
        parent::__construct();
    }

    public function getTrasladistas(): array {
        $sql = "SELECT 
                    p.id_proveedor,
                    p.rfc,
                    p.razon_social,
                    p.nombre_comercial,
                    p.tipo,
                    p.origen,
                    p.estatus_onboarding,
                    p.estatus_operativo,
                    p.created_at,
                    (SELECT COUNT(*) FROM prv_det_madrinas m WHERE m.id_proveedor = p.id_proveedor AND m.deleted_at IS NULL) AS total_madrinas,
                    (SELECT COUNT(*) FROM prv_det_choferes c WHERE c.id_proveedor = p.id_proveedor AND c.deleted_at IS NULL) AS total_choferes
                FROM prv_cat_proveedores p
                WHERE p.deleted_at IS NULL
                ORDER BY p.id_proveedor DESC";
        return $this->select_all($sql);
    }

    public function getTrasladista(int $id): ?array {
        $sql = "SELECT * FROM prv_cat_proveedores WHERE id_proveedor = ? AND deleted_at IS NULL";
        $res = $this->select($sql, [$id]);
        return $res ?: null;
    }

    public function insertTrasladista(array $data, int $userId): int {
        $sql = "INSERT INTO prv_cat_proveedores (
                    rfc,
                    rfc_activo,
                    razon_social,
                    nombre_comercial,
                    id_tipo_persona,
                    id_regimen_fiscal,
                    tipo,
                    origen,
                    estatus_onboarding,
                    estatus_operativo,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aprobado', 1, ?)";

        $params = [
            strtoupper(trim($data['rfc'])),
            strtoupper(trim($data['rfc'])),
            trim($data['razon_social']),
            trim($data['nombre_comercial']),
            $data['id_tipo_persona'] ?? 'M',
            $data['id_regimen_fiscal'] ?? 601,
            $data['tipo'] ?? 'Externo',
            $data['origen'] ?? 'Nacional',
            $userId
        ];

        return (int)$this->insert($sql, $params);
    }

    public function updateTrasladista(int $id, array $data, int $userId): bool {
        $sql = "UPDATE prv_cat_proveedores 
                SET rfc = ?,
                    rfc_activo = ?,
                    razon_social = ?,
                    nombre_comercial = ?,
                    id_tipo_persona = ?,
                    id_regimen_fiscal = ?,
                    tipo = ?,
                    origen = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id_proveedor = ?";

        $params = [
            strtoupper(trim($data['rfc'])),
            strtoupper(trim($data['rfc'])),
            trim($data['razon_social']),
            trim($data['nombre_comercial']),
            $data['id_tipo_persona'] ?? 'M',
            $data['id_regimen_fiscal'] ?? 601,
            $data['tipo'] ?? 'Externo',
            $data['origen'] ?? 'Nacional',
            $userId,
            $id
        ];

        return (bool)$this->update($sql, $params);
    }

    public function deleteTrasladista(int $id, int $userId): bool {
        $sql = "UPDATE prv_cat_proveedores SET estatus_operativo = 0, deleted_by = ?, deleted_at = NOW() WHERE id_proveedor = ?";
        return (bool)$this->update($sql, [$userId, $id]);
    }
}
