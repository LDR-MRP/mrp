<?php

class Prv_detExpedienteModel extends Mysql
{
    use Auditable;

    const SUPPLIER_RECORD_PATH = 'Assets/uploads/expedientes/';

    protected $table = 'prv_det_expediente';

    public const REQUIRED_DOCUMENTS = [
        'CONSTITUTIVA' => ['name' => 'Acta Constitutiva', 'required' => true, 'ext' => 'pdf'],
        'PODER' => ['name' => 'Poder Notarial', 'required' => true, 'ext' => 'pdf'], 
        'CSF' => ['name' => 'Constancia de Situación Fiscal', 'required' => true, 'ext' => 'pdf'],
        'RFC' => ['name' => 'Registro Federal de Contribuyentes', 'required' => true, 'ext' => 'pdf'],
        'ID' => ['name' => 'Identificación Oficial', 'required' => true, 'ext' => 'pdf'],
        'DOMICILIO' => ['name' => 'Comprobante de Domicilio', 'required' => true, 'ext' => 'pdf'], 
    ];

    public function getTableName(): string 
    {
        return $this->table;
    }

    public function findByCriteria(array $filters): array
    {
        $sql = 
            "SELECT
                id_documento,
                id_proveedor,
                tipo_documento,
                url_archivo,
                vencimiento,
                estatus_validacion,
                motivo_rechazo,
                created_by,
                updated_by,
                created_at,
                updated_at,
                deleted_at
            FROM {$this->table}
            WHERE 1=1";

        if(array_key_exists('id_proveedor', $filters)){
            $idProveedor = (int) $filters['id_proveedor'];
            $sql .= " AND `{$this->table}`.`id_proveedor` = '{$idProveedor}'";
        }

        if(array_key_exists('tipo_documento', $filters)){
            $docType = (string) $filters['tipo_documento'];
            $sql .= " AND `{$this->table}`.`tipo_documento` = '{$docType}'";
        }

        return $this->select_all($sql);
    }

    /**
     * Saves a document to the database.
     *
     * @param array $data The document data.
     * @return int The ID of the saved document.
     *
     * @deprecated since version 1.1. Use upsertDocument() instead.
     */
    public function saveDocument(array $data): int
    {
        return $this->insert(
            query:
                "INSERT INTO `{$this->table}`
                (
                    id_proveedor, tipo_documento, url_archivo, created_by
                ) VALUES (?, ?, ?, ?)",
            arrValues: [
                $data['id_proveedor'],
                $data['tipo_documento'],
                $data['url_archivo'],
                $data['created_by'],
            ]
        );
    }

    /**
     * Inserta un nuevo documento o actualiza uno existente basado en la clave única (id_proveedor, tipo_documento).
     * En caso de actualización (reemplazo), el estado de validación se resetea a 'Pendiente' y 
     * se limpia cualquier motivo de rechazo previo.
     *
     * @param array $data {
     *     @var int    $id_proveedor    ID del proveedor propietario del documento.
     *     @var string $tipo_documento  Clave del catálogo de documentos (ej. 'acta_constitutiva').
     *     @var string $url_archivo     Ruta física del archivo en el servidor.
     *     @var int    $created_by      ID del usuario que realiza la carga.
     * }
     * @return bool True si la operación fue exitosa (afectó 1 o más filas).
     */
    public function upsertDocument(array $data): bool
    {
        $rowCount = $this->insert(
            query:
                "INSERT INTO `{$this->table}`
                (
                    id_proveedor, tipo_documento, url_archivo, created_by, estatus_validacion, motivo_rechazo
                ) VALUES (?, ?, ?, ?, 0, NULL)
                ON DUPLICATE KEY UPDATE
                    url_archivo = VALUES(url_archivo),
                    created_by = VALUES(created_by),
                    estatus_validacion = 0,
                    motivo_rechazo = NULL,
                    updated_at = CURRENT_TIMESTAMP",
            arrValues: [
                $data['id_proveedor'],
                $data['tipo_documento'],
                $data['url_archivo'],
                $data['created_by'],
            ]
        );

        // En ON DUPLICATE KEY UPDATE, insert devuelve 1, update devuelve 2. 
        // Por eso validamos que sea >= 1.
        return $rowCount >= 1;
    }

    /**
     * 
     */
    public function getDocumentByType(int $supplierId, string $docType): ?array
    {
        $sql = "SELECT url_archivo FROM prv_det_expediente 
                WHERE id_proveedor = ? AND tipo_documento = ? AND deleted_at IS NULL";
        return $this->select($sql, [$supplierId, $docType]) ?: null;
    }

    /**
     * 
     */
    public function uploadedDocuments(int $id_proveedor): array
    {
        return $this->select_all(
            query:
                "SELECT
                    id_documento,
                    id_proveedor,
                    tipo_documento,
                    url_archivo,
                    vencimiento,
                    estatus_validacion,
                    motivo_rechazo,
                    created_by,
                    updated_by,
                    created_at,
                    updated_at,
                    deleted_at
                FROM `{$this->table}`
                WHERE id_proveedor = ?;",
            arrValues: [
                $id_proveedor
            ]
        );
    }

    public function auditDocument(array $values, int $userId): bool
    {
        return $this->update(
            query: 
                "UPDATE {$this->table}
                SET estatus_validacion = ?,
                    motivo_rechazo = ?,
                    updated_by = ?
                WHERE id_documento = ?;",
            arrValues: [
                $values['estatus_validacion'],
                $values['motivo_rechazo'],
                $userId,
                $values['id_documento'],
            ]
        );
    }

    /**
     * Cuenta cuántos documentos únicos tiene cargados un proveedor.
     * Se utiliza para calcular el progreso del Onboarding.
     */
    public function countDocumentsBySupplier(int $supplierId): int
    {
        // Solo contamos registros que tengan un archivo físico vinculado
        // y que no hayan sido borrados lógicamente (deleted_at).
        $sql = "SELECT COUNT(id_documento) as total 
                FROM prv_det_expediente 
                WHERE id_proveedor = ? 
                AND url_archivo IS NOT NULL 
                AND deleted_at IS NULL";

        $result = $this->select($sql, [$supplierId]);
        
        return (int)($result['total'] ?? 0);
    }

    /**
     * Actualiza el dictamen de validación de un documento específico.
     *
     * @param int    $idDoc   ID del registro.
     * @param int    $status  1 (OK), 2 (Rechazo).
     * @param string|null $motivo Descripción del rechazo.
     * @param int    $userId  Administrador que audita.
     * @return bool
     */
    public function updateDocumentStatus(int $idDoc, int $status, ?string $motivo, int $userId): bool
    {
        $sql = "UPDATE `{$this->table}` 
                SET estatus_validacion = ?, 
                    motivo_rechazo = ?, 
                    updated_by = ?, 
                    updated_at = CURRENT_TIMESTAMP 
                WHERE id_documento = ?";
        
        $rowCount = $this->update($sql, [$status, $motivo, $userId, $idDoc]);
        return $rowCount > 0;
    }

    /**
     * Cuenta cuántos documentos han sido marcados como 'Aprobados' (Estatus 1).
     *
     * @param int $supplierId
     * @return int
     */
    public function countApprovedDocuments(int $supplierId): int
    {
        $sql = "SELECT COUNT(*) as total FROM `{$this->table}` 
                WHERE id_proveedor = ? AND estatus_validacion = 1 AND deleted_at IS NULL";
        $res = $this->select($sql, [$supplierId]);
        return (int)($res['total'] ?? 0);
    }
}