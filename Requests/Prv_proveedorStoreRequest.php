<?php

class Prv_proveedorStoreRequest extends Requests
{
    public function rules(): void
    {
        $requiredFields = [
            'razon_social'               => 'La razón social es obligatoria.',
            'clv_proveedor'              => 'La clave es obligatoria.',
            'tipo_persona'               => 'El tipo de persona es obligatorio.',
            'rfc'                        => 'El RFC es obligatorio.',
            'nombre_comercial'           => 'El nombre comercial es obligatorio.',
            'contacto'                   => 'El contacto es obligatorio.',
            'telefono'                   => 'El teléfono es obligatorio.',
            'metodo_pago_predeterminado' => 'El método de pago es obligatorio.',
            'idmoneda_predeterminada'    => 'La moneda predeterminada es obligatoria.'
        ];

        foreach ($requiredFields as $field => $message) {
            if (empty($this->data[$field])) {
                $this->addError($field, $message);
            }
        }

        if (!empty($this->data['correo_electronico']) && !filter_var($this->data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            $this->addError('correo_electronico', 'El correo electrónico no es válido.');
        }

        if (!empty($this->data['rfc']) && !empty($this->data['tipo_persona'])) {
            $rfc = strtoupper(trim($this->data['rfc']));
            $person = $this->data['tipo_persona'];
            $regexMoral  = '/^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/i';
            $regexFisica = '/^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/i';
            
            if ($person == '2') {
                if (strlen($rfc) !== 12) {
                    $this->addError('rfc', 'El RFC para Persona Moral debe tener exactamente 12 caracteres.');
                } elseif (!preg_match($regexMoral, $rfc)) {
                    $this->addError('rfc', 'El formato del RFC para Persona Moral es inválido.');
                }
            } else {
                if (strlen($rfc) !== 13) {
                    $this->addError('rfc', 'El RFC para Persona Física debe tener exactamente 13 caracteres.');
                } elseif (!preg_match($regexFisica, $rfc)) {
                    $this->addError('rfc', 'El formato del RFC para Persona Física es inválido.');
                }
            }
        }

        if (!empty($this->data['limite_credito']) && !is_numeric($this->data['limite_credito'])) {
            $this->addError('limite_credito', 'El límite de crédito debe ser un valor numérico.');
        }

        if (!empty($this->data['telefono'])) {
            $phone = preg_replace('/\D/', '', $this->data['telefono']);
            $regexMexicoPhone = '/^[2-9][0-9]{9}$/';

            if (strlen($phone) !== 10) {
                $this->addError('telefono', 'El teléfono debe tener exactamente 10 dígitos.');
            } elseif (!preg_match($regexMexicoPhone, $phone)) {
                $this->addError('telefono', 'El formato del teléfono es inválido (debe ser un número de 10 dígitos).');
            }
        }

        $files = $this->files();

        if (!empty($logo = $files['logo']) && !empty($logo['tmp_name'])) {
            
            if ($logo['type'] !== 'image/jpeg' && $logo['type'] !== 'image/png') {
                $this->addError('logo', 'El logo debe ser de tipo JPEG o PNG.');
            }
        }
    }
}
