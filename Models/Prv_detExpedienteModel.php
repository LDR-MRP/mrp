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
     * Saves or updates a document to the database.
     *
     * @param array $data The document data.
     * @return bool True on success.
     */
    public function upsertDocument(array $data): bool
    {
        $rowCount = $this->insert(
            query:
                "INSERT INTO `{$this->table}`
                (
                    id_proveedor, tipo_documento, url_archivo, created_by
                ) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    url_archivo = VALUES(url_archivo),
                    created_by = VALUES(created_by),
                    updated_at = CURRENT_TIMESTAMP",
            arrValues: [
                $data['id_proveedor'],
                $data['tipo_documento'],
                $data['url_archivo'],
                $data['created_by'],
            ]
        );

        return $rowCount >= 1;
    }

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
}