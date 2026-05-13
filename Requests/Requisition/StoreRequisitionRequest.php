<?php
namespace Requests\Requisition;

use Requests;

class StoreRequisitionRequest extends Requests {

    protected $requiredFields = [
        'fecha_requerida' => 'La fecha no contiene un formato válido.',
        'departamentoid' =>  'El departamento solicitante es obligatorio.',
        'justificacion' => 'El comentario de justificación de la compra es obligatorio.',
    ];
    
    public function rules(): void
    {       
        // --- Reglas que aplican SIEMPRE ---
        if (empty($this->data['titulo'])) {
            $this->addError('titulo', 'El título de la requisición es obligatorio.');
        }

        // --- Reglas Condicionales basadas en la intención del usuario ---
        $action = $this->input('action', 'save_draft'); // Default a 'save_draft' si no se especifica

        if ($action === 'submit_approval') {
            $this->applyStrictRules();
        } else {
            $this->applyLaxRules();
        }
    }

    private function applyStrictRules(): void {

        foreach ($this->requiredFields as $field => $message) {
            if (empty(trim($this->data[$field]))) {
                $this->addError($field, $message);
            }
        }

        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $this->data['fecha_requerida'])) {
            $this->addError('fecha_requerida', 'La fecha no contiene un formato válido.');
        }

        if ($this->data['fecha_requerida'] < date('Y-m-d')) {
            $this->addError('fecha_requerida', 'La fecha de la requisición no puede ser menor a la fecha actual.');
        }

        if (empty($items = $this->input('articulos'))) {
            $this->addError('articulos', 'La requisición debe contener al menos un artículo.');
        } else {
            foreach ($items as $index => $item) {
                // 1. Determinar si es una partida de Sourcing
                // Es sourcing si no tiene inventarioid pero tiene ficha técnica (specs)
                // o si es una partida ya guardada que sabemos que es sourcing (id existe, invId es null)
                $isSourcing = empty($item['inventarioid']) && (!empty($item['specs']) || !empty($item['idrequisicionarticulo']));
               
                // 2. Validación de Identidad del Artículo
                // Solo marcamos error si NO tiene inventario Y NO es un caso válido de Sourcing
                if (empty($item['inventarioid']) && !$isSourcing) {
                    $this->addError("partida_$index", "El artículo en la fila " . ($index + 1) . " es obligatorio.");
                }
                
                // 3. Validaciones numéricas comunes
                $cantidad = (float)($item['cantidad'] ?? 0);
                if ($cantidad <= 0) {
                    $this->addError("cantidad_$index", "La cantidad en la fila " . ($index + 1) . " debe ser mayor a cero.");
                }

                $precio = (float)($item['precio_unitario_estimado'] ?? 0);
                if ($precio <= 0) {
                    $this->addError("costo_$index", "El costo unitario en la fila " . ($index + 1) . " no puede ser cero.");
                }
            }
        }
    }

    private function applyLaxRules(): void {
        // Para DRAFT, no hay reglas adicionales obligatorias.
    }
}
?>