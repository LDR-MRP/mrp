<?php

declare(strict_types=1);

namespace Requests\Banco;

use Requests;

class StoreBancoRequest extends Requests
{
    public function rules(): void
    {
        // Validar nombre del banco
        if (empty($this->data['nombre_corto'])) {
            $this->addError('nombre_corto', 'El nombre del banco es obligatorio.');
        } else {
            // Validar longitud
            if (strlen($this->data['nombre_corto']) < 3) {
                $this->addError('nombre_corto', 'El nombre del banco debe tener al menos 3 caracteres.');
            }
            if (strlen($this->data['nombre_corto']) > 100) {
                $this->addError('nombre_corto', 'El nombre del banco no puede exceder 100 caracteres.');
            }
        }

        // Validar razón social
        if (empty($this->data['razon_social'])) {
            $this->addError('razon_social', 'La razón social es obligatoria.');
        }
    }
}
