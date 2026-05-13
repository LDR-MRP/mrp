
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
     * Obtiene todas las cotizaciones de una partida para el Cuadro Comparativo.
     */
    public function getComparisonTable(int $idReqArt): array
    {
        $sql = "SELECT 
                    c.*,
                    p.razon_social,
                    p.estatus_onboarding, -- Para ver 'Cumplimiento Documentos Legales'
                    (SELECT email FROM prv_det_contactos WHERE id_proveedor = p.id_proveedor LIMIT 1) as contacto_email,
                    (SELECT telefono FROM prv_det_contactos WHERE id_proveedor = p.id_proveedor LIMIT 1) as contacto_tel
                FROM com_requisicion_cotizaciones c
                INNER JOIN prv_cat_proveedores p ON c.id_proveedor = p.id_proveedor
                WHERE c.idrequisicionarticulo = ?
                ORDER BY c.precio_unitario ASC";

        return $this->select_all($sql, [$idReqArt]);
    }

    /**
     * Registra una propuesta económica de un proveedor para un artículo de sourcing.
     *
     * @param array $data {
     *     @var int    $idrequisicionarticulo
     *     @var int    $id_proveedor
     *     @var float  $precio_unitario
     *     @var string $moneda
     *     @var float  $tipo_cambio
     *     @var string $url_pdf_cotizacion
     *     @var string $comentarios_comprador
     * }
     * @return int ID de la cotización generada.
     */
    public function insertQuotation(array $data): int
    {
        $sql = "INSERT INTO com_requisicion_cotizaciones (
                    idrequisicionarticulo, 
                    id_proveedor, 
                    precio_unitario, 
                    moneda, 
                    tipo_cambio, 
                    url_pdf_cotizacion, 
                    comentarios_comprador
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        // MAPEO MANUAL: Garantizamos orden y cantidad de parámetros (7 vs 7)
        $params = [
            (int)$data['idrequisicionarticulo'],
            (int)$data['id_proveedor'],
            (float)$data['precio_unitario'],
            $data['moneda'],
            (float)$data['tipo_cambio'],
            $data['url_pdf_cotizacion'],
            $data['comentarios_comprador']
        ];

        // Ejecutamos la inserción
        return $this->insert($sql, $params) ?? 0;
    }

    /**
     * Resetea todas las cotizaciones de una partida a 'no ganadora'.
     */
    public function resetWinnersByPartida(int $idReqArt): bool
    {
        $sql = "UPDATE com_requisicion_cotizaciones SET es_ganadora = 0 WHERE idrequisicionarticulo = ?";
        return $this->update($sql, [$idReqArt]);
    }

    /**
     * Marca una cotización específica como ganadora.
     */
    public function setWinner(int $idCotizacion): bool
    {
        $sql = "UPDATE com_requisicion_cotizaciones SET es_ganadora = 1 WHERE idcotizacion = ?";
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
                INNER JOIN prv_cat_proveedores p ON c.id_proveedor = p.id_proveedor
                INNER JOIN com_requisiciones_detalle rd ON c.idrequisicionarticulo = rd.idrequisicionarticulo
                WHERE c.idrequisicionarticulo = ? AND c.es_ganadora = 1 LIMIT 1";
        return $this->select($sql, [$idReqArt]) ?: null;
    }
}