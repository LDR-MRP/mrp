<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

class UploadDocumentRequest extends Requests
{
    public function rules(): void
    {
        $logFile = null;
        try {
            $logFile = fopen('C:/xampp/htdocs/mrp/Logs/mrp-' . date('Y-m-d') . '.log', 'a');
            
            // Logging de configuración de PHP
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Config PHP: " . json_encode([
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'max_execution_time' => ini_get('max_execution_time'),
                'memory_limit' => ini_get('memory_limit'),
                'MAX_FILE_SIZE_BYTES' => defined('MAX_FILE_SIZE_BYTES') ? MAX_FILE_SIZE_BYTES : 'NO DEFINIDO'
            ]) . "\n");
            
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Iniciando validación\n");
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Datos recibidos: " . json_encode($this->data) . "\n");
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Archivos recibidos: " . json_encode($this->files()) . "\n");
            
            // 1. Validar ID del Proveedor
            if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
                $this->addError('id_proveedor', 'ID de proveedor inválido.');
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: ID de proveedor inválido\n");
            }

            // 2. Validar Tipo de Documento
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Paso 2: Validando tipo_documento\n");
            if (empty($this->data['tipo_documento'])) {
                $this->addError('tipo_documento', 'El tipo de documento es obligatorio.');
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: Tipo de documento vacío\n");
            } else {
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Obteniendo proveedor ID: " . $this->data['id_proveedor'] . "\n");
                $supplierId = (int)$this->data['id_proveedor'];
                $proveedorModel = new \Prv_proveedorModel();
                $supplier = $proveedorModel->getById($supplierId);
                
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Proveedor obtenido: " . ($supplier ? 'YES' : 'NO') . "\n");
                
                if (!$supplier) {
                    $this->addError('id_proveedor', 'El proveedor no existe.');
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: Proveedor no existe\n");
                } else {
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Datos proveedor: " . json_encode([
                        'id_tipo_persona' => $supplier['id_tipo_persona'],
                        'origen' => $supplier['origen']
                    ]) . "\n");
                    
                    // Manejar valores null para extranjeros
                    $tipoPersona = $supplier['id_tipo_persona'] ?? 'F';
                    $origen = $supplier['origen'] ?? 'Nacional';
                    
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Obteniendo documentos permitidos con defaults\n");
                    $allowedDocs = \DocumentTypeEnum::getRequiredList($tipoPersona, $origen);
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Documentos permitidos obtenidos\n");
                    
                    if (!array_key_exists($this->data['tipo_documento'], $allowedDocs)) {
                        $this->addError('tipo_documento', 'El tipo de documento no es válido para el perfil de este proveedor.');
                        fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: Tipo de documento no válido. Documentos permitidos: " . json_encode(array_keys($allowedDocs)) . "\n");
                    }
                }
            }

            // 3. Validar el Archivo Físico
            if (empty($this->files()['archivo'])) {
                $this->addError('archivo', 'No se ha seleccionado ningún archivo.');
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: No se seleccionó archivo\n");
            } else {
                $file = $this->files()['archivo'];
                
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Archivo info: " . json_encode([
                    'name' => $file['name'],
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'error' => $file['error']
                ]) . "\n");
                
                $maxSize = defined('MAX_FILE_SIZE_BYTES') ? MAX_FILE_SIZE_BYTES : (20 * 1024 * 1024);
                if ($file['size'] > $maxSize) {
                    $maxSizeMB = $maxSize / (1024 * 1024);
                    $this->addError('archivo', "El archivo excede el límite de {$maxSizeMB}MB.");
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: Archivo excede tamaño. Tamaño: " . ($file['size'] / (1024*1024)) . "MB, Límite: {$maxSizeMB}MB\n");
                }

                $allowedMime = 'application/pdf';
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);
                
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] MIME type detectado: {$mimeType}\n");
                
                if ($mimeType !== $allowedMime) {
                    $this->addError('archivo', 'Solo se permiten archivos en formato PDF original.');
                    fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Error: MIME type inválido\n");
                }
            }
            
            // Logging final
            if (!empty($this->errors)) {
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Validación falló. Errores: " . json_encode($this->errors) . "\n");
            } else {
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] Validación completada sin errores\n");
            }
            
        } catch (\Exception $e) {
            if ($logFile) {
                fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] local.ERROR: [UploadDocumentRequest] EXCEPTION: " . $e->getMessage() . " | File: " . $e->getFile() . ":" . $e->getLine() . "\n");
            }
            throw $e; // Re-lanzar para que el código original maneje el error
        } finally {
            if ($logFile) {
                fclose($logFile);
            }
        }
    }
}
