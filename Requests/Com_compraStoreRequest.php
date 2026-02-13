<?php

class Com_compraStoreRequest extends Requests {

    public $requisitionModel;
    public $currencyModel;
    
    public function rules() {

        if (empty($this->data['requisicionid'])) {
            $this->addError('proveedor', 'La requisición no es válida.');
        }

        if (empty($this->data['proveedorid'])) {
            $this->addError('proveedor', 'Debe seleccionar un proveedor válido.');
        }
        
        if (empty($this->data['monedaid'])) {
            $this->addError('moneda', 'La moneda del documento es obligatoria.');
        }

        if (empty($this->data['terminoid'])) {
            $this->addError('moneda', 'Las condiciones de pago son obligatorias.');
        }

        if (empty($this->data['almacenid'])) {
            $this->addError('almacen', 'El almacén del documento es obligatorio.');
        }
        
        $requisition = $this->requisitionModel->requisition($this->data['requisicionid']);

        if (!$requisition) {
            $this->addError('requisicionid', 'La requisición no existe.');
            return;
        }

        $currency = current($this->currencyModel->all(['idmoneda' => $this->data['monedaid']]));

        if (!$currency) {
            $this->addError('monedaid', 'La moneda no existe.');
            return;
        }        
    }
}