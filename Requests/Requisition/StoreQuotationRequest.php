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

        // --- INICIO LÓGICA PROSPECTO VS CATÁLOGO ---
        $esProspecto = ($this->data['es_prospecto'] ?? '0') === '1';

        if ($esProspecto) {
            if (empty($this->data['nombre_prospecto'])) {
                $this->addError('nombre_prospecto', 'El nombre del prospecto o tienda es obligatorio.');
            }
        } else {
            if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
                $this->addError('id_proveedor', 'Debe seleccionar un proveedor del catálogo.');
            }
        }
        // --- FIN LÓGICA PROSPECTO ---

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
        // Solo validamos si REALMENTE se subió un archivo (error === 0)
        if (isset($files['foto_producto']) && $files['foto_producto']['error'] === UPLOAD_ERR_OK) {
            $photo = $files['foto_producto'];
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($photo['tmp_name']);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mimeType, $allowedMimes)) {
                $this->addError('foto_producto', 'Formato de imagen inválido.');
            }
            if ($photo['size'] > 5 * 1024 * 1024) {
                $this->addError('foto_producto', 'La foto excede 5MB.');
            }
        }

        // 6. NUEVO: Especificaciones Particulares
        if (($this->data['pago_inmediato'] ?? '0') === '1') {
            if (empty($this->data['url_referencia'])) {
                $this->addError('url_referencia', 'Para pagos inmediatos, la URL de referencia es obligatoria.');
            }
        }
    }
}