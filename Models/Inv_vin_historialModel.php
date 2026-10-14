<?php

/**
 * Bitácora de VIN (wms_vin_historial).
 * La usan: Inv_series (generador y asociación motor / VIN de origen)
 * e Inv_captura_vin (cambios al VIN base del glosario).
 *
 * modulo: GENERADOR | ASOCIACION | VIN_BASE
 * accion: GENERACION | ALTA | MODIFICACION
 * origen: INDIVIDUAL | MASIVO | SISTEMA
 */
class Inv_vin_historialModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function registrar(array $d): int
    {
        $sql = "INSERT INTO wms_vin_historial
                (id_numeros_serie, numero_serie, id_cat_modelo_vin, modulo, accion, campo,
                 valor_anterior, valor_nuevo, detalle, origen, usuarioid, fecha)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())";

        return (int) $this->insert($sql, [
            $d['id_numeros_serie'] ?? null,
            $d['numero_serie'] ?? null,
            $d['id_cat_modelo_vin'] ?? null,
            $d['modulo'],
            $d['accion'],
            $d['campo'] ?? null,
            isset($d['valor_anterior']) ? mb_substr((string) $d['valor_anterior'], 0, 255) : null,
            isset($d['valor_nuevo']) ? mb_substr((string) $d['valor_nuevo'], 0, 255) : null,
            isset($d['detalle']) ? mb_substr((string) $d['detalle'], 0, 500) : null,
            $d['origen'] ?? 'INDIVIDUAL',
            !empty($d['usuarioid']) ? (int) $d['usuarioid'] : null,
        ]);
    }

    /**
     * Historial completo de un VIN generado: su generación, sus cambios de
     * asociación y los cambios del VIN base (glosario) del que salió.
     */
    public function selectHistorialVin(int $idNumerosSerie): array
    {
        $sql = "SELECT h.*, CONCAT_WS(' ', u.nombres, u.apellidos) AS usuario
                FROM wms_vin_historial h
                LEFT JOIN usuarios u ON u.idusuario = h.usuarioid
                WHERE h.id_numeros_serie = ?
                   OR (h.modulo = 'VIN_BASE' AND h.id_cat_modelo_vin = (
                        SELECT s.id_cat_modelo_vin FROM wms_numeros_series s
                        WHERE s.id_numeros_serie = ?))
                ORDER BY h.fecha DESC, h.id_historial DESC";

        return $this->select_all($sql, [$idNumerosSerie, $idNumerosSerie]);
    }

    /** Historial de un VIN base del glosario (cat_modelos_vin). */
    public function selectHistorialModelo(int $idModelo): array
    {
        $sql = "SELECT h.*, CONCAT_WS(' ', u.nombres, u.apellidos) AS usuario
                FROM wms_vin_historial h
                LEFT JOIN usuarios u ON u.idusuario = h.usuarioid
                WHERE h.modulo = 'VIN_BASE' AND h.id_cat_modelo_vin = ?
                ORDER BY h.fecha DESC, h.id_historial DESC";

        return $this->select_all($sql, [$idModelo]);
    }
}
