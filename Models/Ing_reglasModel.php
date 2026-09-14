<?php

class Ing_reglasModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    // Configuración + estado del modelo (wms_sublinea_producto/ing_modelo_detalle)
    public function selectConfiguracionParaEvaluar(int $idConfiguracion)
    {
        $sql = "SELECT
                    c.*,
                    s.estado AS sublinea_estado,
                    d.estado AS detalle_estado
                FROM ing_modelo_configuracion c
                INNER JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                LEFT JOIN ing_modelo_detalle d ON d.id_sublineaproducto = c.id_sublineaproducto AND d.deleted_at IS NULL
                WHERE c.id_configuracion = ? AND c.deleted_at IS NULL";

        return $this->select($sql, [$idConfiguracion]);
    }

    // Todas las configuraciones que el motor de reglas debe vigilar
    // (las que ya entraron al flujo de validación automática).
    public function selectConfiguracionesParaEvaluar()
    {
        $sql = "SELECT id_configuracion FROM ing_modelo_configuracion
                WHERE estado IN ('EN_REVISION','AUTORIZADO','BLOQUEADO') AND deleted_at IS NULL";

        return $this->select_all($sql);
    }

    // Especificaciones activas del catálogo que no tienen valor capturado para esta configuración
    public function contarEspecificacionesFaltantes(int $idConfiguracion)
    {
        $sql = "SELECT COUNT(*) AS faltantes
                FROM ing_cat_especificacion e
                LEFT JOIN ing_modelo_especificacion_valor v
                    ON v.id_especificacion = e.id_especificacion AND v.id_configuracion = ?
                WHERE e.activo = 1 AND e.deleted_at IS NULL
                    AND (v.id_valor IS NULL OR v.valor = '')";

        $row = $this->select($sql, [$idConfiguracion]);
        return (int) ($row['faltantes'] ?? 0);
    }

    // Total de especificaciones activas del catálogo (para mostrar "X de Y
    // capturadas" en la línea de proceso de la pantalla de Configuraciones).
    public function contarEspecificacionesTotal()
    {
        $sql = "SELECT COUNT(*) AS total FROM ing_cat_especificacion WHERE activo = 1 AND deleted_at IS NULL";
        $row = $this->select($sql);
        return (int) ($row['total'] ?? 0);
    }

    // Certificaciones activas del catálogo, con su estado capturado (si existe) para esta configuración
    public function selectCertificacionesParaEvaluar(int $idConfiguracion)
    {
        $sql = "SELECT
                    cert.id_certificacion,
                    cert.nombre,
                    cert.requiere_documento,
                    cert.requiere_vigencia,
                    mc.obligatoria,
                    mc.estado,
                    mc.numero_certificado,
                    mc.fecha_vencimiento
                FROM ing_cat_certificacion cert
                LEFT JOIN ing_modelo_certificacion mc
                    ON mc.id_certificacion = cert.id_certificacion AND mc.id_configuracion = ?
                WHERE cert.activo = 1 AND cert.deleted_at IS NULL";

        return $this->select_all($sql, [$idConfiguracion]);
    }

    public function actualizarEstadoConfiguracion(int $idConfiguracion, string $estado)
    {
        $sql = "UPDATE ing_modelo_configuracion SET estado = ? WHERE id_configuracion = ?";
        return $this->update($sql, [$estado, $idConfiguracion]);
    }

    // Marca como VENCIDA cualquier certificación capturada cuya fecha de vencimiento ya pasó
    // (para que el listado y el motor de reglas reflejen la realidad sin esperar captura manual).
    public function marcarCertificacionesVencidas()
    {
        $sql = "UPDATE ing_modelo_certificacion
                SET estado = 'VENCIDA'
                WHERE fecha_vencimiento IS NOT NULL AND fecha_vencimiento < CURDATE() AND estado != 'VENCIDA'";
        return $this->update($sql, []);
    }

    public function insertLogEvaluacion(int $idConfiguracion, string $estadoAnterior, string $estadoNuevo, bool $aprueba, array $motivos, string $origen)
    {
        $sql = "INSERT INTO ing_configuracion_evaluacion_log
            (id_configuracion, estado_anterior, estado_nuevo, aprueba, motivos, origen)
            VALUES (?,?,?,?,?,?)";

        return $this->insert($sql, [
            $idConfiguracion, $estadoAnterior, $estadoNuevo, $aprueba ? 1 : 0,
            !empty($motivos) ? implode(' | ', $motivos) : null, $origen,
        ]);
    }

    public function selectLogConfiguracion(int $idConfiguracion)
    {
        $sql = "SELECT * FROM ing_configuracion_evaluacion_log
                WHERE id_configuracion = ? ORDER BY created_at DESC";

        return $this->select_all($sql, [$idConfiguracion]);
    }
}
