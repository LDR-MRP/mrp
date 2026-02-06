<?php

class Com_comprasRequest extends Requests {
    
    public function rules() {
        // 1. Validar Cabecera
        if (empty($this->data['proveedor'])) {
            $this->addError('proveedor', 'Debe seleccionar un proveedor válido.');
        }

        if (empty($this->data['fecha_documento'])) {
            $this->addError('fecha_documento', 'La fecha del documento es obligatoria.');
        }

        if (empty($this->data['almacen'])) {
            $this->addError('almacen', 'El almacen del documento es obligatorio.');
        }
        
        if (empty($this->data['moneda'])) {
            $this->addError('moneda', 'La moneda del documento es obligatoria.');
        }

        if (empty($this->data['serieid'])) {
            $this->addError('serieid', 'La serie del documento es obligatoria.');
        }

        if (empty($this->data['cantidad_total'])) {
            $this->addError('cantidad_total', 'La cantidad total del documento es obligatoria.');
        }

        // 2. Validar Detalle (Partidas)
        $detalle = json_decode($this->data['detalle_partidas'] ?? '[]', true);
        
        if (empty($detalle)) {
            $this->addError('detalle_partidas', 'La compra debe contener al menos un artículo.');
        } else {
            foreach ($detalle as $index => $item) {
                if (empty($item['inventario'])) {
                    $this->addError("partida_$index", "El artículo en la fila ".($index+1)." es obligatorio.");
                }
                if ($item['cantidad'] <= 0) {
                    $this->addError("cantidad_$index", "La cantidad en la fila ".($index+1)." debe ser mayor a cero.");
                }
                if ($item['costo_unitario'] <= 0) {
                    $this->addError("costo_$index", "El costo unitario en la fila ".($index+1)." no puede ser cero.");
                }
                if ($item['impuesto_partida'] <= 0) {
                    $this->addError('impuesto_partida', "El impuesto en la fila ".($index+1)." no puede ser cero.");
                }
                if ($item['subtotal_partida'] <= 0) {
                    $this->addError('subtotal_partida', "El subtotal en la fila ".($index+1)." no puede ser cero.");
                }
            }
        }
    }
}