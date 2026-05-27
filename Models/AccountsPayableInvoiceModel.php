<?php

class AccountsPayableInvoiceModel extends Mysql
{
    use Auditable;
    
    private string $table = 'cxp_tra_facturas';

    public function __construct()
    {
        parent::__construct();
    }

    public function getTableName(): string 
    {
        return $this->table;
    }

    /**
     * Registra un nuevo pasivo de factura validada en la base de datos de CxP.
     * 
     * @param array $data Información limpia obtenida del InvoiceValidationService.
     * @return int|null Retorna el ID insertado o null en caso de falla.
     */
    public function registrarFactura(array $data): ?int
    {
        $query = "INSERT INTO {$this->table} 
                    (id_proveedor, id_compra, serie_folio, uuid, monto_total, fecha_vencimiento, url_xml, url_pdf, estatus_validacion, created_by)
                  VALUES 
                    (:id_proveedor, :id_compra, :serie_folio, :uuid, :monto_total, :fecha_vencimiento, :url_xml, :url_pdf, :estatus, :created_by)";

        $params = [
            ':id_proveedor'      => $data['id_proveedor'],
            ':id_compra'         => $data['id_compra'],
            ':serie_folio'       => $data['serie_folio'],
            ':uuid'              => $data['uuid'],
            ':monto_total'       => $data['monto_total'],
            ':fecha_vencimiento' => $data['fecha_vencimiento'] ?: null, // <-- NUEVO PARÁMETRO
            ':url_xml'           => $data['url_xml'],
            ':url_pdf'           => $data['url_pdf'],
            ':estatus'           => $data['estatus_validacion'] ?? 1, // 1 = Validada de origen por el SAT
            ':created_by'        => $data['created_by']
        ];

        // El método insert de tu core retorna el ID autogenerado
        $request = $this->insert($query, $params);

        return !empty($request) ? (int)$request : null;
    }

    /**
     * Obtiene el historial de facturas cargadas de un proveedor específico.
     * Realiza un JOIN con com_ordenes_compra para jalar el código legible de la OC.
     */
    public function getByProveedor(int $idProveedor): array
    {
        $query = "SELECT 
                    f.id,
                    f.serie_folio,
                    f.uuid,
                    f.monto_total,
                    f.url_xml,
                    f.url_pdf,
                    f.estatus_validacion,
                    f.motivo_rechazo,
                    f.created_at,
                    oc.idcompra AS id_compra,
                    oc.idcompra AS codigo_oc -- Fallback si no manejas columna de folios en la OC
                  FROM {$this->table} f
                  INNER JOIN com_ordenes_compra oc ON oc.idcompra = f.id_compra
                  WHERE f.id_proveedor = :id_proveedor
                    AND f.deleted_at IS NULL
                  ORDER BY f.created_at DESC";

        $params = [
            ':id_proveedor' => $idProveedor
        ];

        // Asumiendo que tu método select_all() retorna el array completo de registros
        return $this->select_all($query, $params) ?? [];
    }

    /**
     * Obtiene una factura específica por su ID.
     */
    public function getById(int $invoiceId): ?array
    {
        $query = "SELECT * FROM {$this->table} WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        return $this->select($query, [':id' => $invoiceId]) ?? null;
    }

    /**
     * Obtiene la suma total de facturas aprobadas/validadas previamente para una OC.
     * Excluimos la factura actual que estamos evaluando en el motor.
     */
    public function getApprovedSumByOC(int $idOc, int $currentInvoiceId): array
    {
        $query = "SELECT SUM(monto_total) AS total_aprobado 
                  FROM {$this->table} 
                  WHERE id_compra = :id_compra 
                    AND id != :current_id
                    AND estatus_validacion = 1 -- Solo sumamos las ya aprobadas
                    AND deleted_at IS NULL";

        return $this->select($query, [
            ':id_compra' => $idOc,
            ':current_id' => $currentInvoiceId
        ]) ?? ['total_aprobado' => 0];
    }

    /**
     * Actualiza el estatus de validación y las observaciones de conciliación de la factura.
     */
    public function updateValidationStatus(int $invoiceId, int $estatus, string $comentarios): bool
    {
        $query = "UPDATE {$this->table} 
                  SET estatus_validacion = :estatus,
                      motivo_rechazo = :comentarios
                  WHERE id = :id";

        return (bool) $this->update($query, [
            ':estatus'     => $estatus,
            ':comentarios' => $comentarios,
            ':id'          => $invoiceId
        ]);
    }

    /**
     * Obtiene todas las facturas de una OC específica que estén congeladas (estatus 0).
     */
    public function getPendingInvoicesByOC(int $idOc): array
    {
        $query = "SELECT id FROM {$this->table} 
                  WHERE id_compra = :id_compra 
                    AND estatus_validacion = 0 -- 0 = Congelada / Match Pendiente
                    AND deleted_at IS NULL";

        return $this->select_all($query, [':id_compra' => $idOc]) ?? [];
    }

    /**
     * Obtiene el listado completo de facturas para el ERP con filtros de búsqueda.
     */
    public function getInvoicesList(array $filters): array
    {
        $query = "SELECT 
                    f.id,
                    f.serie_folio,
                    f.uuid,
                    f.monto_total,
                    f.fecha_vencimiento,
                    f.url_xml,
                    f.url_pdf,
                    f.estatus_validacion,
                    f.motivo_rechazo,
                    f.created_at,
                    p.nombre_comercial AS proveedor_nombre,
                    p.rfc AS proveedor_rfc,
                    oc.idcompra AS id_compra,
                    oc.idcompra AS codigo_oc
                  FROM {$this->table} f
                  INNER JOIN com_ordenes_compra oc ON oc.idcompra = f.id_compra
                  INNER JOIN prv_cat_proveedores p ON p.id_proveedor = f.id_proveedor
                  WHERE f.deleted_at IS NULL";

        $params = [];

        // Filtros Dinámicos Seguros
        if (!empty($filters['proveedorid'])) {
            $query .= " AND f.id_proveedor = :proveedorid";
            $params[':proveedorid'] = (int)$filters['proveedorid'];
        }

        if (isset($filters['estatus'])) {
            $query .= " AND f.estatus_validacion = :estatus";
            $params[':estatus'] = (int)$filters['estatus'];
        }

        if (!empty($filters['fecha_desde']) && !empty($filters['fecha_hasta'])) {
            $query .= " AND DATE(f.created_at) BETWEEN :desde AND :hasta";
            $params[':desde'] = $filters['fecha_desde'];
            $params[':hasta'] = $filters['fecha_hasta'];
        }

        $query .= " ORDER BY f.created_at DESC";

        return $this->select_all($query, $params) ?? [];
    }

    /**
     * Calcula de forma atómica los contadores de los 4 KPIs del Dashboard de CxP.
     */
    public function getDashboardKpis(): array
    {
        $query = "SELECT 
                    -- KPI 1: Congeladas (Held) por diferencia con almacén
                    COALESCE(SUM(CASE WHEN estatus_validacion = 0 THEN 1 ELSE 0 END), 0) AS congeladas,
                    
                    -- KPI 2: Aprobadas para Programación de Pago
                    COALESCE(SUM(CASE WHEN estatus_validacion = 1 THEN 1 ELSE 0 END), 0) AS aprobadas,
                    
                    -- KPI 3: Rechazadas por fallas del SAT o excedentes
                    COALESCE(SUM(CASE WHEN estatus_validacion = 2 THEN 1 ELSE 0 END), 0) AS rechazadas,
                    
                    -- KPI 4: Vencidas (Urgentes) -> Aprobadas cuya fecha de vencimiento ya pasó
                    COALESCE(SUM(CASE WHEN estatus_validacion = 1 AND fecha_vencimiento < CURRENT_DATE() THEN 1 ELSE 0 END), 0) AS vencidas
                  FROM {$this->table}
                  WHERE deleted_at IS NULL";

        return $this->select($query) ?? [
            'congeladas' => 0,
            'aprobadas' => 0,
            'rechazadas' => 0,
            'vencidas' => 0
        ];
    }
}