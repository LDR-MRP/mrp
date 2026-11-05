<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

class UploadDocumentRequest extends Requests
{
    public function rules(): void
    {
        try {
            error_log("[UploadDocumentRequest] INICIO - Config PHP: upload_max=" . ini_get('upload_max_filesize') . " post_max=" . ini_get('post_max_size'));
            error_log("[UploadDocumentRequest] Datos recibidos: " . json_encode($this->data));
            error_log("[UploadDocumentRequest] Archivos recibidos: " . json_encode($this->files()));

            // 1. Validar ID del Proveedor
            if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
                $this->addError('id_proveedor', 'ID de proveedor inválido.');
                error_log("[UploadDocumentRequest] ERROR: ID de proveedor inválido");
            }

            // 2. Validar Tipo de Documento
            error_log("[UploadDocumentRequest] Validando tipo_documento");
            if (empty($this->data['tipo_documento'])) {
                $this->addError('tipo_documento', 'El tipo de documento es obligatorio.');
                error_log("[UploadDocumentRequest] ERROR: Tipo de documento vacío");
            } else {
                error_log("[UploadDocumentRequest] Buscando proveedor ID: " . $this->data['id_proveedor']);
                $supplierId = (int)$this->data['id_proveedor'];
                $proveedorModel = new \Prv_proveedorModel();
                $supplier = $proveedorModel->getById($supplierId);

                error_log("[UploadDocumentRequest] Proveedor encontrado: " . ($supplier ? 'YES' : 'NO'));

                if (!$supplier) {
                    $this->addError('id_proveedor', 'El proveedor no existe.');
                    error_log("[UploadDocumentRequest] ERROR: Proveedor no existe");
                } else {
                    error_log("[UploadDocumentRequest] Proveedor datos: " . json_encode([
                        'id_tipo_persona' => $supplier['id_tipo_persona'],
                        'origen' => $supplier['origen']
                    ]));

                    $tipoPersona = $supplier['id_tipo_persona'] ?? 'F';
                    $origen = $supplier['origen'] ?? 'Nacional';

                    error_log("[UploadDocumentRequest] Obteniendo documentos permitidos");
                    $allowedDocs = \DocumentTypeEnum::getRequiredList($tipoPersona, $origen);
                    error_log("[UploadDocumentRequest] Documentos permitidos: " . json_encode(array_keys($allowedDocs)));

                    if (!array_key_exists($this->data['tipo_documento'], $allowedDocs)) {
                        $this->addError('tipo_documento', 'El tipo de documento no es válido para el perfil de este proveedor.');
                        error_log("[UploadDocumentRequest] ERROR: Tipo de documento no válido");
                    }
                }
            }

            // 3. Validar el Archivo Físico
            error_log("[UploadDocumentRequest] Validando archivo");
            if (empty($this->files()['archivo'])) {
                $this->addError('archivo', 'No se ha seleccionado ningún archivo.');
                error_log("[UploadDocumentRequest] ERROR: No se seleccionó archivo");
            } else {
                $file = $this->files()['archivo'];

                error_log("[UploadDocumentRequest] Archivo: name=" . ($file['name'] ?? 'N/A') . " size=" . ($file['size'] ?? 'N/A') . " error=" . ($file['error'] ?? 'N/A'));

                $maxSize = defined('MAX_FILE_SIZE_BYTES') ? MAX_FILE_SIZE_BYTES : (20 * 1024 * 1024);
                $maxSizeMB = $maxSize / (1024 * 1024);
                error_log("[UploadDocumentRequest] Límite: {$maxSizeMB}MB");

                if ($file['size'] > $maxSize) {
                    $this->addError('archivo', "El archivo excede el límite de {$maxSizeMB}MB.");
                    error_log("[UploadDocumentRequest] ERROR: Archivo excede tamaño");
                }

                $allowedMime = 'application/pdf';
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                error_log("[UploadDocumentRequest] MIME detectado: {$mimeType}");

                if ($mimeType !== $allowedMime) {
                    $this->addError('archivo', 'Solo se permiten archivos en formato PDF original.');
                    error_log("[UploadDocumentRequest] ERROR: MIME inválido");
                }
            }

            // Logging final
            if (!empty($this->errors)) {
                error_log("[UploadDocumentRequest] FIN - Validación falló: " . json_encode($this->errors));
            } else {
                error_log("[UploadDocumentRequest] FIN - Validación OK");
            }

        } catch (\Exception $e) {
            error_log("[UploadDocumentRequest] EXCEPTION: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            throw $e;
        }
    }
}
