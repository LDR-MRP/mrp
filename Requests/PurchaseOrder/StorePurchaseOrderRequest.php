<?php
namespace Requests\PurchaseOrder;

use Requests;

class StorePurchaseOrderRequest extends Requests {
    
    public function rules(): void {
        // 1. Validar Cabecera General
        if (empty($this->input('requisicionid')) || !is_numeric($this->input('requisicionid'))) {
            $this->addError('requisicionid', 'La requisición de origen es obligatoria.');
        }
        if (empty($this->input('almacenid')) || !is_numeric($this->input('almacenid'))) {
            $this->addError('almacenid', 'Debe especificar el almacén de destino para todos los artículos.');
        }

        // 2. Validar Partidas (El corazón del Splitting)
        $articulos = $this->input('articulos');
        if (empty($articulos) || !is_array($articulos)) {
            $this->addError('articulos', 'No se enviaron artículos para procesar.');
            return;
        }

        foreach ($articulos as $index => $item) {
            $prefijo = "articulos.{$index}";

            // Validar que cada partida tenga su proveedor para poder hacer el splitting
            if (empty($item['proveedorid']) || !is_numeric($item['proveedorid'])) {
                $this->addError("{$prefijo}.proveedorid", "La partida #{$item['idrequisicionarticulo']} no tiene un proveedor asignado.");
            }

            if (empty($item['inventarioid']) || !is_numeric($item['inventarioid'])) {
                $this->addError("{$prefijo}.inventarioid", "El artículo debe estar catalogado (SKU) antes de comprar.");
            }

            if (empty($item['cantidad']) || (float)$item['cantidad'] <= 0) {
                $this->addError("{$prefijo}.cantidad", "La cantidad debe ser mayor a cero.");
            }

            if (!isset($item['costo_unitario']) || (float)$item['costo_unitario'] <= 0) {
                $this->addError("{$prefijo}.costo_unitario", "El costo unitario debe ser un valor positivo.");
            }
        }
    }
}