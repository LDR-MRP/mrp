<?php

class Prv_proveedorStoreRequest extends Requests
{
    public function rules(): void
    {
        if (empty($this->data['clv_proveedor'])) $this->addError('clv_proveedor', 'La clave es obligatoria.');
        if (empty($this->data['rfc'])) $this->addError('rfc', 'El RFC es obligatorio.');
        if (empty($this->data['razon_social'])) $this->addError('razon_social', 'La razón social es obligatoria.');
        
        // Validación de formato de correo si existe
        if (!empty($this->data['correo_electronico']) && !filter_var($this->data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            $this->addError('correo_electronico', 'El formato del correo es inválido.');
        }

        // El RFC debe tener longitud válida (12 o 13)
        $rfcLen = strlen($this->data['rfc'] ?? '');
        if ($rfcLen < 12 || $rfcLen > 13) {
            $this->addError('rfc', 'El RFC debe tener entre 12 y 13 caracteres.');
        }
    }
}