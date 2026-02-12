<?php

class Com_requisicionStoreRequest extends Requests {
    
    public function rules() {
        // 1. Validar Cabecera        
        if (empty($this->data['departamentoid'])) {
            $this->addError('departamentoid', 'El departamento solicitante es obligatorio.');
        }

        if (empty($this->data['monto_estimado'])) {
            $this->addError('monto_estimado', 'El monto_estimado del documento es obligatorio.');
        }

        // 2. Validar Detalle (Partidas)
        $detalle = $this->data['articulos'];
        
        if (empty($detalle)) {
            $this->addError('articulos', 'La requisición debe contener al menos un artículo.');
        } else {
            foreach ($detalle as $index => $item) {
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
}