<?php
namespace Requests\Auth;

use Requests;

class LoginRequest extends Requests {
    public function rules(): void {
        if (empty($this->input('txtEmail'))) {
            $this->addError('txtEmail', 'El correo electrónico es obligatorio.');
        }
        if (empty($this->input('txtPassword'))) {
            $this->addError('txtPassword', 'La contraseña es obligatoria.');
        }
    }
}