<?php

class Prv_detExpedienteModel extends Mysql
{
    protected $table = 'prv_det_expediente';

    public const DOCUMENTOS_REQUERIDOS = [
        'CONSTITUTIVA' => ['name' => 'Acta Constitutiva', 'required' => true, 'ext' => 'pdf'],
        'CSF' => ['name' => 'Constancia de Situación Fiscal', 'required' => true, 'ext' => 'pdf'],
        'RFC' => ['name' => 'Registro Federal de Contribuyentes', 'required' => true, 'ext' => 'pdf'],
        'ID' => ['name' => 'Identificación Oficial', 'required' => true, 'ext' => 'pdf'],
        'DOMICILIO' => ['name' => 'Comprobante de Domicilio', 'required' => true, 'ext' => 'pdf'], 
    ];

    public function getTableName(): string 
    {
        return $this->table;
    }

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

    public function auditDocument(array $values): bool
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
                $_SESSION['idUser'],
                $values['id_documento'],
            ]
        );
    }
}