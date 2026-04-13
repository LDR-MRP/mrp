<?php

class StoreRequisitionRequest extends Requests {
    
    public function rules(): void {
        
        // --- Reglas que aplican SIEMPRE ---
        $this->validateDescription();
        // ... otras reglas comunes ...

        // --- Reglas Condicionales basadas en la intención del usuario ---
        $action = $this->input('estatus', 'borrador'); // Default a 'save_draft' si no se especifica

        if ($action === 'pendiente') {
            $this->applyStrictRules();
        } else {
            $this->applyLaxRules();
        }
    }

    private function validateDescription(): void {
        $description = $this->input('description');
        if (!empty($description) && strlen($description) > 255) {
            $this->addError('description', 'La descripción no puede exceder los 255 caracteres.');
        }
    }

    private function applyStrictRules(): void {
        // Justificación requerida
        if (empty($this->input('justification'))) {
            $this->addError('justification', 'La justificación es requerida para enviar a aprobación.');
        }

        // Items requeridos y no vacíos
        $items = $this->input('items');
        if (empty($items) || !is_array($items)) {
            $this->addError('items', 'Se requiere al menos una partida para enviar a aprobación.');
        } else {
            foreach($items as $index => $item) {
                if (empty($item['quantity']) || $item['quantity'] <= 0) {
                    $this->addError("items.{$index}.quantity", 'La cantidad debe ser mayor a cero.');
                }
            }
        }
        // ... resto de validaciones estrictas ...
    }

    private function applyLaxRules(): void {
        // Para DRAFT, no hay reglas adicionales obligatorias.
        // Podrías validar que si viene un item, tenga un formato válido, pero es opcional.
    }
}
?>