<?php
namespace Requests\Requisition;

use Requests;

class StoreRequisitionRequest extends Requests {

    protected $requiredFields = [
        'fecha_requerida' => 'La fecha no contiene un formato válido.',
        'departamentoid' =>  'El departamento solicitante es obligatorio.',
        'monto_estimado' => 'El monto estimado del documento es obligatorio.',
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
                if (empty($item['inventarioid'])) {
                    $this->addError("partida_$index", "El artículo en la fila ".($index+1)." es obligatorio.");
                }
                if ($item['cantidad'] <= 0) {
                    $this->addError("cantidad_$index", "La cantidad en la fila ".($index+1)." debe ser mayor a cero.");
                }
                if ($item['precio_unitario_estimado'] <= 0) {
                    $this->addError("costo_$index", "El costo unitario en la fila ".($index+1)." no puede ser cero.");
                }
            }
        }
    }

    private function applyLaxRules(): void {
        // Para DRAFT, no hay reglas adicionales obligatorias.
    }
}
?>