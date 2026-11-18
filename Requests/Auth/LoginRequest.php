<?php
namespace Requests\Auth;

use Requests;

class LoginRequest extends Requests {
    public function rules(): void {
        // Campo puede ser RFC o Email (renombrado para compatibilidad)
        if (empty($this->input('txtEmail'))) {
            $this->addError('txtEmail', 'El RFC o correo electrónico es obligatorio.');
        }
        if (empty($this->input('txtPassword'))) {
            $this->addError('txtPassword', 'La contraseña es obligatoria.');
        }
    }
}