<?php
namespace Requests\Requisition;

use Requests;

class MoveRequisitionItemsRequest extends Requests {
    
    public function rules(): void {
        
        // --- Regla 1: Validar el Destino (Exclusividad Mutua) ---
        $targetId = $this->input('target_requisition_id');
        $createNew = $this->input('create_new', false); // Default a false si no viene

        if ($createNew === true && !empty($targetId)) {
            $this->addError('target_requisition_id', 'No se puede especificar un DRAFT de destino y crear uno nuevo al mismo tiempo.');
        } elseif ($createNew === false && empty($targetId)) {
            $this->addError('target_requisition_id', 'Debes especificar un DRAFT de destino o marcar la opción para crear uno nuevo.');
        }

        // Si se provee un targetId, debe ser un número entero válido
        if (!empty($targetId) && (!is_numeric($targetId) || $targetId <= 0)) {
            $this->addError('target_requisition_id', 'El ID del DRAFT de destino debe ser un número entero positivo.');
        }

        // Si se provee create_new, debe ser un booleano
        if (isset($this->data['create_new']) && !is_bool($this->data['create_new'])) {
             $this->addError('create_new', 'El campo create_new debe ser un valor booleano (true o false).');
        }


        // --- Regla 2: Validar la existencia y formato del array de Partidas ---
        $items = $this->input('items');

        if (empty($items)) {
            $this->addError('items', 'Debes especificar al menos una partida para mover.');
            return; // Si no hay items, no tiene sentido seguir validando
        }
        
        if (!is_array($items)) {
            $this->addError('items', 'El campo items debe ser un arreglo de partidas.');
            return; // Detenemos para evitar errores en el foreach
        }


        // --- Regla 3: Validar la Integridad de cada Partida dentro del array ---
        foreach ($items as $index => $item) {
            // Cada item debe ser un objeto/array asociativo
            if (!is_array($item)) {
                $this->addError("items.{$index}", "La partida en la posición {$index} tiene un formato inválido.");
                continue; // Saltar al siguiente item
            }

            // Validar 'requisition_item_id'
            if (empty($item['requisition_item_id']) || !is_numeric($item['requisition_item_id']) || $item['requisition_item_id'] <= 0) {
                $this->addError("items.{$index}.requisition_item_id", "La partida en la posición {$index} debe tener un ID numérico y positivo.");
            }

            // Validar 'qty_to_move'
            if (empty($item['qty_to_move']) || !is_numeric($item['qty_to_move']) || $item['qty_to_move'] <= 0) {
                $this->addError("items.{$index}.qty_to_move", "La partida en la posición {$index} debe tener una cantidad a mover numérica y mayor a cero.");
            }
        }
    }
}