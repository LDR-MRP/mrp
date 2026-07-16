<?php
declare(strict_types=1);

namespace Requests\Sourcing;

use Requests;

class PromoteToCatalogRequest extends Requests {
    public function rules(): void {
        // 1. Vinculación
        if (empty($this->data['idrequisicionarticulo'])) {
            $this->addError('idrequisicionarticulo', 'La partida es obligatoria.');
        }

        // 2. Datos del Maestro (wms_inventario)
        if (empty($this->data['cve_articulo'])) {
            $this->addError('cve_articulo', 'Debe asignar un SKU / Clave de artículo oficial.');
        }

        if (!empty($this->data['cve_articulo']) && strlen($this->data['cve_articulo']) > 20) {
            $this->addError('cve_articulo', 'No debe ser mayor a 20 caracteres.');
        }

        if (empty($this->data['lineaproductoid'])) {
            $this->addError('lineaproductoid', 'Debe seleccionar una línea de producto del catálogo.');
        }

        if (empty($this->data['tipo_elemento'])) {
            $this->addError('tipo_elemento', 'Especifique si es Producto o Servicio.');
        }

        if (empty($this->data['descripcion_final'])) {
            $this->addError('descripcion_final', 'Especifique la descripción del Producto o Servicio.');
        }
    }
}