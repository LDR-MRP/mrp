<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

class UploadDocumentRequest extends Requests
{
    public function rules(): void
    {
        // 1. Validar ID del Proveedor
        if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
            $this->addError('id_proveedor', 'ID de proveedor inválido.');
        }

        // 2. Validar Tipo de Documento (Debe ser uno de los permitidos)
        if (empty($this->data['tipo_documento'])) {
            $this->addError('tipo_documento', 'El tipo de documento es obligatorio.');
        }

        // 3. Validar el Archivo Físico
        if (empty($this->files()['archivo'])) {
            $this->addError('archivo', 'No se ha seleccionado ningún archivo.');
        } else {
            $file = $this->files()['archivo'];
            
            // Límite de 5MB
            $maxSize = 5 * 1024 * 1024;
            if ($file['size'] > $maxSize) {
                $this->addError('archivo', 'El archivo excede el límite de 5MB.');
            }

            // Validar que sea PDF real (MIME type)
            $allowedMime = 'application/pdf';
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file['tmp_name']);
            
            if ($mimeType !== $allowedMime) {
                $this->addError('archivo', 'Solo se permiten archivos en formato PDF original.');
            }
        }
    }
}