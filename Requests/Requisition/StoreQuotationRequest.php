<?php

declare(strict_types=1);

namespace Requests\Requisition;

use Requests;

/**
 * Validador para el registro de cotizaciones de proveedores (Sourcing).
 * HU #3: Cuadro Comparativo.
 */
class StoreQuotationRequest extends Requests
{
    /**
     * Define las reglas de integridad para las propuestas económicas de proveedores.
     */
    public function rules(): void
    {
        // 1. Identificadores Base
        if (empty($this->data['idrequisicionarticulo']) || !is_numeric($this->data['idrequisicionarticulo'])) {
            $this->addError('idrequisicionarticulo', 'La vinculación con la partida de la requisición es obligatoria.');
        }

        if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
            $this->addError('id_proveedor', 'Debe seleccionar un proveedor válido del catálogo.');
        }

        // 2. Validación Financiera
        $precio = (float)($this->data['precio_unitario'] ?? 0);
        if ($precio <= 0) {
            $this->addError('precio_unitario', 'El precio unitario cotizado debe ser mayor a cero.');
        }

        if (empty($this->data['moneda'])) {
            $this->addError('moneda', 'Especifique la moneda de la cotización (MXN/USD).');
        }

        // Si la moneda no es MXN, el tipo de cambio es obligatorio y debe ser > 1
        if (($this->data['moneda'] ?? '') !== 'MXN') {
            $tc = (float)($this->data['tipo_cambio'] ?? 0);
            if ($tc <= 1) {
                $this->addError('tipo_cambio', 'Para moneda extranjera, debe proporcionar un tipo de cambio válido.');
            }
        }

        // 3. Validación de Evidencia Física (PDF)
        // Usamos el método files() de tu clase base
        $files = $this->files();
        if (empty($files['cotizacion_pdf']) || $files['cotizacion_pdf']['error'] !== UPLOAD_ERR_OK) {
            $this->addError('cotizacion_pdf', 'Es obligatorio adjuntar la cotización oficial en formato PDF.');
        } else {
            // Verificación de MIME Type real (Seguridad DevSecOps)
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($files['cotizacion_pdf']['tmp_name']);
            if ($mimeType !== 'application/pdf') {
                $this->addError('cotizacion_pdf', 'El archivo cargado no es un PDF válido.');
            }
        }

        // 4. Comentarios (Opcional, pero limitamos longitud por cordura de DB)
        if (strlen((string)($this->data['comentarios_comprador'] ?? '')) > 1000) {
            $this->addError('comentarios_comprador', 'El comentario es demasiado largo (máximo 1000 caracteres).');
        }

         // 5. NUEVO: Fotografía del Producto (Opcional pero validada)
        if (!empty($this->files()['foto_producto'])) {
            $photo = $this->files()['foto_producto'];
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($photo['tmp_name']);

            if (!in_array($mimeType, $allowedMimes)) {
                $this->addError('foto_producto', 'La foto debe ser formato JPG, PNG o WEBP.');
            }
            if ($photo['size'] > 5 * 1024 * 1024) { // 5MB limit
                $this->addError('foto_producto', 'La foto no debe exceder los 5MB.');
            }
        }

        // 6. NUEVO: Especificaciones Particulares
        if (empty($this->data['specs_particulares_proveedor'])) {
            $this->addError('specs_particulares_proveedor', 'Debe indicar las especificaciones particulares que ofrece este proveedor.');
        }
    }
}