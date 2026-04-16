<?php
namespace Requests\PurchaseOrder;

use Requests;

class StorePurchaseOrderRequest extends Requests {
    
    public function rules(): void {
        // 1. Validar Cabecera
        if (empty($this->input('requisicionid')) || !is_numeric($this->input('requisicionid'))) {
            $this->addError('requisicionid', 'El ID de la requisición origen es obligatorio.');
        }
        if (empty($this->input('proveedorid')) || !is_numeric($this->input('proveedorid'))) {
            $this->addError('proveedorid', 'Debe seleccionar un proveedor válido.');
        }
        if (empty($this->input('almacenid')) || !is_numeric($this->input('almacenid'))) {
            $this->addError('almacenid', 'Debe seleccionar un almacén de destino.');
        }

        // 2. Validar Partidas
        $articulos = $this->input('articulos');
        if (empty($articulos) || !is_array($articulos) || count($articulos) === 0) {
            $this->addError('articulos', 'La orden de compra debe contener al menos un artículo.');
            return;
        }

        foreach ($articulos as $index => $item) {
            if (empty($item['idrequisicionarticulo']) || !is_numeric($item['idrequisicionarticulo'])) {
                $this->addError("articulos.{$index}.idrequisicionarticulo", "Falta la referencia a la partida original.");
            }
            if (empty($item['inventarioid']) || !is_numeric($item['inventarioid'])) {
                $this->addError("articulos.{$index}.inventarioid", "El ID del inventario es inválido.");
            }
            if (empty($item['cantidad']) || (float)$item['cantidad'] <= 0) {
                $this->addError("articulos.{$index}.cantidad", "La cantidad a comprar debe ser mayor a cero.");
            }
            if (!isset($item['costo_unitario']) || (float)$item['costo_unitario'] < 0) {
                $this->addError("articulos.{$index}.costo_unitario", "El costo unitario no puede ser negativo.");
            }
        }
    }
}
?>