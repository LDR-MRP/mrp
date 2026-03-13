<?php

class Prv_detExpedienteModel extends Mysql
{
    public const DOCUMENTOS_REQUERIDOS = [
        'CONSTITUTIVA' => ['name' => 'Acta Constitutiva', 'required' => true, 'ext' => 'pdf'],
        'CSF' => ['name' => 'Constancia de Situación Fiscal', 'required' => true, 'ext' => 'pdf'],
        'RFC' => ['name' => 'Registro Federal de Contribuyentes', 'required' => true, 'ext' => 'pdf'],
        'ID' => ['name' => 'Identificación Oficial', 'required' => true, 'ext' => 'pdf'],
        'DOMICILIO' => ['name' => 'Comprobante de Domicilio', 'required' => true, 'ext' => 'pdf'], 
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function saveDocument(array $data)
    {
        return $this->insert(
            "INSERT INTO `prv_det_expediente`
            (
                id_proveedor, tipo_documento, url_archivo, created_by
            ) VALUES (?, ?, ?, ?)",
            [
                $data['id_proveedor'],
                $data['tipo_documento'],
                $data['url_archivo'],
                $data['created_by'],
            ]
        );
    }

    public function uploadedDocuments(int $id_proveedor)
    {
        return $this->select_all(
            "SELECT id_documento, id_proveedor, tipo_documento, url_archivo, vencimiento, estatus_validacion, created_by, updated_by, created_at, updated_at, deleted_at
            FROM `prv_det_expediente`
            WHERE id_proveedor = ?",
            [$id_proveedor]
        );
    }
}