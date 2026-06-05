<?php

declare(strict_types=1);

namespace Requests\AccountsPayable;

use Requests;

class StoreInvoiceRequest extends Requests
{
    /**
     * Reglas de validación para el registro de facturas (HU #149)
     */
    public function rules(): void
    {
        // 1. Validar ID del Proveedor
        if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
            $this->addError('id_proveedor', 'Debe seleccionar un proveedor válido.');
        }

        // 2. Validar Datos Fiscales (RFC y UUID)
        if (empty($this->data['rfc_emisor'])) {
            $this->addError('rfc_emisor', 'El RFC del emisor es obligatorio.');
        } elseif (strlen((string)$this->data['rfc_emisor']) < 12) {
            $this->addError('rfc_emisor', 'El RFC parece estar malformado.');
        }

        if (empty($this->data['uuid'])) {
            $this->addError('uuid', 'El Folio Fiscal (UUID) es obligatorio para la validación SAT.');
        }

        // 3. Validar Montos
        if (!isset($this->data['total']) || (float)$this->data['total'] <= 0) {
            $this->addError('total', 'El monto total de la factura debe ser mayor a cero.');
        }

        // 4. Validar Fechas
        if (empty($this->data['fecha_emision'])) {
            $this->addError('fecha_emision', 'La fecha de emisión de la factura es obligatoria.');
        }
    }
}