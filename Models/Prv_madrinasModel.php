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
                    m.placa_caja,
                    m.marca,
                    m.modelo,
                    m.anio,
                    m.color,
                    m.num_serie_vin,
                    m.capacidad_vehiculos,
                    m.estatus_operativo,
                    m.created_at,
                    (SELECT CONCAT(c.nombre, ' ', c.apellidos) 
                     FROM prv_det_madrina_chofer_historial h
                     INNER JOIN prv_det_choferes c ON c.id_chofer = h.id_chofer
                     WHERE h.id_madrina = m.id_madrina AND h.activo = 1 LIMIT 1) AS chofer_actual
                FROM prv_det_madrinas m
                INNER JOIN prv_cat_proveedores p ON p.id_proveedor = m.id_proveedor
                WHERE m.deleted_at IS NULL
                ORDER BY m.id_madrina DESC";
        return $this->select_all($sql);
    }

    public function getMadrina(int $id): ?array {
        $sql = "SELECT m.*, p.razon_social AS trasladista 
                FROM prv_det_madrinas m
                INNER JOIN prv_cat_proveedores p ON p.id_proveedor = m.id_proveedor
                WHERE m.id_madrina = ? AND m.deleted_at IS NULL";
        $res = $this->select($sql, [$id]);
        return $res ?: null;
    }

    public function getHistorialChoferes(int $idMadrina): array {
        $sql = "SELECT 
                    h.id_historial,
                    h.id_madrina,
                    h.id_chofer,
                    CONCAT(c.nombre, ' ', c.apellidos) AS chofer_nombre,
                    c.num_licencia,
                    c.telefono,
                    h.fecha_inicio,
                    h.fecha_fin,
                    h.activo,
                    h.observaciones
                FROM prv_det_madrina_chofer_historial h
                INNER JOIN prv_det_choferes c ON c.id_chofer = h.id_chofer
                WHERE h.id_madrina = ?
                ORDER BY h.activo DESC, h.id_historial DESC";
        return $this->select_all($sql, [$idMadrina]);
    }

    public function asignarChofer(int $idMadrina, int $idChofer, ?string $observaciones, int $userId): bool {
        // 1. Inactivar cualquier chofer activo previo
        $sqlInactivar = "UPDATE prv_det_madrina_chofer_historial 
                         SET activo = 0, fecha_fin = NOW(), updated_by = ? 
                         WHERE id_madrina = ? AND activo = 1";
        $this->update($sqlInactivar, [$userId, $idMadrina]);

        // 2. Insertar nuevo chofer activo en el historial
        $sqlInsert = "INSERT INTO prv_det_madrina_chofer_historial (
                        id_madrina,
                        id_chofer,
                        fecha_inicio,
                        activo,
                        observaciones,
                        created_by
                      ) VALUES (?, ?, NOW(), 1, ?, ?)";
        return (bool)$this->insert($sqlInsert, [$idMadrina, $idChofer, $observaciones, $userId]);
    }

    public function insertMadrina(array $data, int $userId): int {
        $sql = "INSERT INTO prv_det_madrinas (
                    id_proveedor,
                    numero_economico,
                    placas,
                    placa_caja,
                    marca,
                    modelo,
                    anio,
                    color,
                    num_serie_vin,
                    capacidad_vehiculos,
                    estatus_operativo,
                    created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?)";

        $params = [
            intval($data['id_proveedor']),
            trim($data['numero_economico']),
            strtoupper(trim($data['placas'])),
            !empty($data['placa_caja']) ? strtoupper(trim($data['placa_caja'])) : null,
            trim($data['marca'] ?? ''),
            trim($data['modelo'] ?? ''),
            !empty($data['anio']) ? intval($data['anio']) : null,
            trim($data['color'] ?? ''),
            !empty($data['num_serie_vin']) ? strtoupper(trim($data['num_serie_vin'])) : null,
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
                    placa_caja = ?,
                    marca = ?,
                    modelo = ?,
                    anio = ?,
                    color = ?,
                    num_serie_vin = ?,
                    capacidad_vehiculos = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id_madrina = ?";

        $params = [
            intval($data['id_proveedor']),
            trim($data['numero_economico']),
            strtoupper(trim($data['placas'])),
            !empty($data['placa_caja']) ? strtoupper(trim($data['placa_caja'])) : null,
            trim($data['marca'] ?? ''),
            trim($data['modelo'] ?? ''),
            !empty($data['anio']) ? intval($data['anio']) : null,
            trim($data['color'] ?? ''),
            !empty($data['num_serie_vin']) ? strtoupper(trim($data['num_serie_vin'])) : null,
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
