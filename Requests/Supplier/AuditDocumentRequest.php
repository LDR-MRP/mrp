<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

/**
 * Validador para el dictamen de documentos del expediente digital.
 */
class AuditDocumentRequest extends Requests
{
    /**
     * Define las reglas de integridad para la auditoría de documentos.
     */
    public function rules(): void
    {
        // 1. Validar ID del Documento
        if (empty($this->data['id_documento']) || !is_numeric($this->data['id_documento'])) {
            $this->addError('id_documento', 'El identificador del documento es obligatorio.');
        }

        // 2. Validar ID del Proveedor (Necesario para recalcular progreso)
        if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
            $this->addError('id_proveedor', 'El identificador del proveedor es obligatorio.');
        }

        // 3. Validar Estatus de Validación
        $status = $this->data['estatus_validacion'] ?? null;
        if (!in_array((int)$status, [1, 2], true)) {
            $this->addError('estatus_validacion', 'El dictamen debe ser Aprobado (1) o Rechazado (2).');
        }

        // 4. Lógica Condicional: Motivo de Rechazo
        // Si el estatus es 2 (Rechazado), el motivo NO puede estar vacío.
        if ((int)$status === 2) {
            $motivo = trim((string)($this->data['motivo_rechazo'] ?? ''));
            if (empty($motivo)) {
                $this->addError('motivo_rechazo', 'Debe proporcionar un motivo para rechazar el documento.');
            } elseif (strlen($motivo) < 5) {
                $this->addError('motivo_rechazo', 'El motivo de rechazo es demasiado corto.');
            }
        }
    }
}