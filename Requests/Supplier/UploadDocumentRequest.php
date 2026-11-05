<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

class UploadDocumentRequest extends Requests
{
    public function rules(): void
    {
        try {
            error_log("[UploadDocumentRequest] Iniciando validación");
            error_log("[UploadDocumentRequest] Datos recibidos: " . json_encode($this->data));
            error_log("[UploadDocumentRequest] Archivos recibidos: " . json_encode($this->files()));

            // 1. Validar ID del Proveedor
            if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
                $this->addError('id_proveedor', 'ID de proveedor inválido.');
                error_log("[UploadDocumentRequest] Error: ID de proveedor inválido");
            }

            // 2. Validar Tipo de Documento
            error_log("[UploadDocumentRequest] Paso 2: Validando tipo_documento");
            if (empty($this->data['tipo_documento'])) {
                $this->addError('tipo_documento', 'El tipo de documento es obligatorio.');
                error_log("[UploadDocumentRequest] Error: Tipo de documento vacío");
            } else {
                error_log("[UploadDocumentRequest] Obteniendo proveedor ID: " . $this->data['id_proveedor']);
                $supplierId = (int)$this->data['id_proveedor'];
                $proveedorModel = new \Prv_proveedorModel();
                $supplier = $proveedorModel->getById($supplierId);

                error_log("[UploadDocumentRequest] Proveedor obtenido: " . ($supplier ? 'YES' : 'NO'));

                if (!$supplier) {
                    $this->addError('id_proveedor', 'El proveedor no existe.');
                    error_log("[UploadDocumentRequest] Error: Proveedor no existe");
                } else {
                    error_log("[UploadDocumentRequest] Datos proveedor: " . json_encode([
                        'id_tipo_persona' => $supplier['id_tipo_persona'],
                        'origen' => $supplier['origen']
                    ]));

                    // Manejar valores null para extranjeros
                    $tipoPersona = $supplier['id_tipo_persona'] ?? 'F';
                    $origen = $supplier['origen'] ?? 'Nacional';

                    error_log("[UploadDocumentRequest] Obteniendo documentos permitidos con defaults");
                    $allowedDocs = \DocumentTypeEnum::getRequiredList($tipoPersona, $origen);
                    error_log("[UploadDocumentRequest] Documentos permitidos obtenidos");

                    if (!array_key_exists($this->data['tipo_documento'], $allowedDocs)) {
                        $this->addError('tipo_documento', 'El tipo de documento no es válido para el perfil de este proveedor.');
                        error_log("[UploadDocumentRequest] Error: Tipo de documento no válido. Documentos permitidos: " . json_encode(array_keys($allowedDocs)));
                    }
                }
            }

            // 3. Validar el Archivo Físico
            if (empty($this->files()['archivo'])) {
                $this->addError('archivo', 'No se ha seleccionado ningún archivo.');
                error_log("[UploadDocumentRequest] Error: No se seleccionó archivo");
            } else {
                $file = $this->files()['archivo'];

                error_log("[UploadDocumentRequest] Archivo info: " . json_encode([
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'error' => $file['error']
                ]));

                $maxSize = defined('MAX_FILE_SIZE_BYTES') ? MAX_FILE_SIZE_BYTES : (20 * 1024 * 1024);
                if ($file['size'] > $maxSize) {
                    $maxSizeMB = $maxSize / (1024 * 1024);
                    $this->addError('archivo', "El archivo excede el límite de {$maxSizeMB}MB.");
                    error_log("[UploadDocumentRequest] Error: Archivo excede tamaño. Tamaño: " . ($file['size'] / (1024*1024)) . "MB, Límite: {$maxSizeMB}MB");
                }

                $allowedMime = 'application/pdf';
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                error_log("[UploadDocumentRequest] MIME type detectado: {$mimeType}");

                if ($mimeType !== $allowedMime) {
                    $this->addError('archivo', 'Solo se permiten archivos en formato PDF original.');
                    error_log("[UploadDocumentRequest] Error: MIME type inválido");
                }
            }

            // Logging final
            if (!empty($this->errors)) {
                error_log("[UploadDocumentRequest] Validación falló. Errores: " . json_encode($this->errors));
            } else {
                error_log("[UploadDocumentRequest] Validación completada sin errores");
            }

        } catch (\Exception $e) {
            error_log("[UploadDocumentRequest] EXCEPTION: " . $e->getMessage() . " | File: " . $e->getFile() . ":" . $e->getLine());
            throw $e;
        }
    }
}
