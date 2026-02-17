<?php

class Com_requisicionStoreRequest extends Requests {
    
    public function rules() {

        if (empty($this->data['titulo'])) {
            $this->addError('titulo', 'El título de la requisición es obligatorio.');
        }
        
        if (empty($this->data['departamentoid'])) {
            $this->addError('departamentoid', 'El departamento solicitante es obligatorio.');
        }
        
        if (!preg_match('/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[12][0-9]|3[01])$/', $this->data['fecha_requerida'])) {
            $this->addError('fecha_requerida', 'La fecha no contiene un formato válido.');
        } 

        if ($this->data['fecha_requerida'] < date('Y-m-d')) {
            $this->addError('fecha_requerida', 'La fecha de la requisición no puede ser menor a la fecha actual.');
        }

        if (empty($this->data['monto_estimado'])) {
            $this->addError('monto_estimado', 'El monto estimado del documento es obligatorio.');
        }

        if (empty($this->data['justificacion'])) {
            $this->addError('justificacion', 'El comentario de justificación de la compra es obligatorio.');
        }
        
        if (empty($detalle = $this->data['articulos'])) {
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