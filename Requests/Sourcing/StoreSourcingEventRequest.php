<?php
declare(strict_types=1);

namespace Requests\Sourcing;

use Requests;

class StoreSourcingEventRequest extends Requests {
    public function rules(): void {
        if (empty($this->data) || !is_array($this->data)) {
            $this->addError('items', "Debe seleccionar al menos una partida para crear el evento.");
        }
    }
}