
<?php

class Com_requisicionCotizacionModel extends Mysql
{
    use Auditable;

    protected string $table = 'com_requisicion_cotizaciones';

    public function __construct()
    {
        parent::__construct();
    }

    public function getTableName(): string 
    {
        return $this->table;
    }

    /**
     * Obtiene el set de datos para el Cuadro Comparativo (Sourcing Hybrid).
     * Resuelve nombres de prospectos y calcula el cumplimiento normativo.
     * 
     * @param int $idReqArt ID de la partida (artículo) en la requisición.
     * @return array Listado de propuestas económicas.
     */
    public function getComparisonTable(int $idReqArt): array
    {
        $sql = "SELECT 
                    c.idcotizacion,
                    c.idrequisicionarticulo,
                    c.id_proveedor,
                    c.tipo_fuente,
                    -- RESOLUCIÓN DE NOMBRE: Prioriza catálogo, de lo contrario usa el prospecto
                    COALESCE(p.razon_social, c.nombre_prospecto) as razon_social,
                    -- ESTATUS DE CUMPLIMIENTO: Si es prospecto, marcamos como Pendiente Onboarding
                    COALESCE(p.estatus_onboarding, 'Prospecto') as estatus_onboarding,
                    c.comentarios_comprador,
                    c.specs_particulares_proveedor,
                    c.moneda,
                    c.tipo_cambio,
                    c.iva_inc,
                    c.precio_base_mxn,
                    c.precio_unitario,
                    -- NUEVOS CAMPOS DE INTELIGENCIA RETAIL
                    c.pago_inmediato,
                    c.url_referencia,
                    c.url_pdf_cotizacion,
                    c.url_foto_producto,
                    c.es_ganadora,
                    c.adjudicado_por,
                    c.created_at,
                    -- CONTACTOS: Subconsultas optimizadas
                    (SELECT email FROM prv_det_contactos WHERE id_proveedor = p.id_proveedor LIMIT 1) as contacto_email,
                    (SELECT telefono FROM prv_det_contactos WHERE id_proveedor = p.id_proveedor LIMIT 1) as contacto_tel
                FROM com_requisicion_cotizaciones c
                LEFT JOIN prv_cat_proveedores p ON c.id_proveedor = p.id_proveedor
                WHERE c.idrequisicionarticulo = ?
                AND c.deleted_at IS NULL
                -- ORDENAMIENTO FINANCIERO: Comparamos peras con peras (MXN Final)
                ORDER BY (c.precio_unitario * c.tipo_cambio) ASC";

        return $this->select_all($sql, [$idReqArt]);
    }

    /**
     * Registra una propuesta económica de un proveedor (Catálogo o Prospecto/Retail).
     * Soporta la nueva lógica de Spot Buy e integración de Sourcing.
     *
     * @param array $data {
     *     @var int         $idrequisicionarticulo
     *     @var int|null    $id_proveedor       ID del catálogo (null si es prospecto)
     *     @var string|null $nombre_prospecto   Nombre manual si no está en catálogo
     *     @var float       $precio_unitario
     *     @var string      $moneda
     *     @var float       $tipo_cambio
     *     @var string      $url_pdf_cotizacion
     *     @var string|null $url_foto_producto
     *     @var string|null $comentarios_comprador
     *     @var string      $specs_particulares_proveedor
     *     @var int         $pago_inmediato     Flag (0|1)
     *     @var string|null $url_referencia     URL de tienda retail
     * }
     * @return int ID de la cotización generada.
     */
    public function insertQuotation(array $data): int
    {
        $sql = "INSERT INTO com_requisicion_cotizaciones (
                    idrequisicionarticulo, 
                    src_evento_sourcing_id,
                    id_proveedor, 
                    tipo_fuente,
                    nombre_prospecto,
                    precio_unitario, 
                    moneda, 
                    tipo_cambio,
                    iva_inc,
                    precio_base_mxn,
                    url_pdf_cotizacion,
                    url_foto_producto,
                    comentarios_comprador,
                    specs_particulares_proveedor,
                    pago_inmediato,
                    url_referencia,
                    created_by
                ) VALUES (?,?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        /**
         * MAPEO SEGURO: 
         * Manejamos tipos estrictos y nulos para evitar errores de integridad 
         * en la llave foránea id_proveedor.
         */
        $params = [
            (int)$data['idrequisicionarticulo'],
            (int)$data['src_evento_sourcing_id'],
            // Si no hay id_proveedor, enviamos null (requiere que el DDL permita null en la FK)
            !empty($data['id_proveedor']) ? (int)$data['id_proveedor'] : null,
            $data['tipo_fuente'],
            $data['nombre_prospecto'] ?? null,
            (float)$data['precio_unitario'],
            $data['moneda'] ?? 'MXN',
            (float)($data['tipo_cambio'] ?? 1.0),
            $data['iva_inc'] ?? null,
            $data['precio_base_mxn'] ?? null,
            $data['url_pdf_cotizacion'] ?? null,
            $data['url_foto_producto'] ?? null,
            $data['comentarios_comprador'] ?? null,
            $data['specs_particulares_proveedor'] ?? '',
            (int)($data['pago_inmediato'] ?? 0),
            $data['url_referencia'] ?? null,
            $data['created_by'] ?? null
        ];

        return (int)$this->insert($sql, $params) ?? 0;
    }

    /**
     * Resetea todas las cotizaciones de una partida a 'no ganadora'.
     */
    public function resetWinnersByPartida(int $idReqArt): bool
    {
        $sql = "UPDATE com_requisicion_cotizaciones SET es_ganadora = 0, estatus_cotizacion = 'BORRADOR' WHERE idrequisicionarticulo = ?";
        return $this->update($sql, [$idReqArt]);
    }

    /**
     * Marca una cotización específica como ganadora.
     */
    public function setWinner(int $idCotizacion): bool
    {
        $sql = "UPDATE com_requisicion_cotizaciones SET es_ganadora = 1, estatus_cotizacion = 'GANADORA' WHERE idcotizacion = ?";
        return $this->update($sql, [$idCotizacion]);
    }

    /**
     * Obtiene una cotización incluyendo el ID de la requisición padre.
     */
    public function getQuotationById(int $id): ?array
    {
        $sql = "SELECT c.*, rd.requisicionid 
                FROM com_requisicion_cotizaciones c
                INNER JOIN com_requisiciones_detalle rd ON c.idrequisicionarticulo = rd.idrequisicionarticulo
                WHERE c.idcotizacion = ? LIMIT 1";
        return $this->select($sql, [$id]) ?: null;
    }

    public function getWinnerQuotation(int $idReqArt): ?array {
        $sql = "SELECT c.*, p.razon_social, rd.requisicionid 
                FROM com_requisicion_cotizaciones c
                LEFT JOIN prv_cat_proveedores p ON c.id_proveedor = p.id_proveedor
                LEFT JOIN com_requisiciones_detalle rd ON c.idrequisicionarticulo = rd.idrequisicionarticulo
                WHERE c.idrequisicionarticulo = ? AND c.es_ganadora = 1 LIMIT 1";
        return $this->select($sql, [$idReqArt]) ?: null;
    }

    /**
     * Counts the number of active (not deleted) quotations for a specific requisition item.
     * This is used to enforce the "Rule of 3" procurement policy.
     *
     * @param int $idrequisicionarticulo The ID of the specific item being sourced.
     * @return int Total number of active quotations found.
     */
    public function countActiveQuotations(int $idrequisicionarticulo): int
    {
        $sql = "SELECT COUNT(*) as total 
                FROM `{$this->table}` 
                WHERE idrequisicionarticulo = ? 
                AND deleted_at IS NULL";

        $result = $this->select($sql, [$idrequisicionarticulo]);

        // We return the integer value of 'total', or 0 if something went wrong.
        return (int)($result['total'] ?? 0);
    }

    /**
     * Marca una cotización como eliminada.
     */
    public function softDelete(int $id, int $userId): bool
    {
        $sql = "UPDATE com_requisicion_cotizaciones 
                SET deleted_at = NOW() 
                WHERE idcotizacion = ?";
        return $this->update($sql, [$id]);
    }

    /**
     * Actualiza el vínculo de una cotización con un ID de proveedor oficial.
     * Se usa cuando un prospecto es pre-registrado en el catálogo.
     * 
     * @param int $idCotizacion ID de la cotización.
     * @param int $idProveedor  ID del proveedor generado en el catálogo.
     * @return bool
     */
    public function updateProviderLink(int $idCotizacion, int $idProveedor): bool
    {
        $query = "UPDATE com_requisicion_cotizaciones 
                SET id_proveedor = ?, 
                    updated_at = ? 
                WHERE idcotizacion = ?";

        $params = [
            $idProveedor,
            date('Y-m-d H:i:s'),
            $idCotizacion
        ];

        // Usamos updateAffected para confirmar que el registro existía y cambió
        return $this->updateAffected($query, $params) > 0;
    }

    /**
     * Vincula la cotización ganadora con la Orden de Compra que la ejecutó.
     * 
     * @param int $idReqArt ID de la partida de la requisición.
     * @param int $ocId ID de la Orden de Compra generada.
     * @return bool
     */
    public function linkPurchaseOrder(int $idReqArt, int $ocId): bool
    {
        $query = "UPDATE com_requisicion_cotizaciones 
                SET id_orden_compra_final = ?, 
                    updated_at = ? 
                WHERE idrequisicionarticulo = ? 
                AND es_ganadora = 1 
                AND deleted_at IS NULL";

        $params = [
            $ocId,
            date('Y-m-d H:i:s'),
            $idReqArt
        ];

        // Usamos updateAffected para asegurar consistencia
        return $this->updateAffected($query, $params) > 0;
    }
}