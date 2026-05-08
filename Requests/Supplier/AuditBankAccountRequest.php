<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

/**
 * Validador para la aprobación o rechazo de cuentas bancarias.
 */
class AuditBankAccountRequest extends Requests
{
    /**
     * Reglas de integridad para el dictamen bancario (Compliance L2).
     */
    public function rules(): void
    {
        // 1. Validar ID de la Cuenta
        if (empty($this->data['id_cuenta_bancaria']) || !is_numeric($this->data['id_cuenta_bancaria'])) {
            $this->addError('id_cuenta_bancaria', 'El identificador de la cuenta es obligatorio.');
        }

        // 2. Validar el Estatus de Aprobación
        $status = strtoupper((string)($this->data['estatus_aprobacion'] ?? ''));
        $allowed = ['APROBADO', 'RECHAZADO'];

        if (!in_array($status, $allowed, true)) {
            $this->addError('estatus_aprobacion', 'El estatus debe ser APROBADO o RECHAZADO.');
        }

        // 3. Lógica Condicional (Opcional pero recomendada): 
        // Si se rechaza, exigir un breve comentario en el payload.
        // if ($status === 'RECHAZADO') {
        //     $comentario = trim((string)($this->data['comentario'] ?? ''));
        //     if (empty($comentario)) {
        //         $this->addError('comentario', 'Debe proporcionar un motivo para el rechazo de la cuenta.');
        //     }
        // }
    }
}