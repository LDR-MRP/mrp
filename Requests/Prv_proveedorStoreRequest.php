<?php

class Prv_proveedorStoreRequest extends Requests
{
    public function rules(): void
    {
        $requiredFields = [
            // Datos Maestros (prv_cat_proveedores)
            'id_empresa'        => 'La empresa origen es obligatoria para el multi-tenant.',
            'rfc'               => 'El RFC es obligatorio para la validación fiscal.',
            'razon_social'      => 'La Razón Social es requerida según la Constancia de Situación Fiscal.',
            'nombre_comercial'  => 'El Nombre Comercial es necesario para identificar al proveedor.',
            'id_tipo_persona'   => 'Debe especificar si es Persona Física o Moral.',
            'id_regimen_fiscal' => 'El Régimen Fiscal es obligatorio para la facturación CFDI 4.0.',
            'origen'            => 'Especifique si el proveedor es Nacional o Extranjero.',

            // Dirección (prv_det_direcciones)
            'tipo'              => 'Especifique el tipo de dirección (Fiscal, Bodega, etc.).',
            'calle'             => 'El nombre de la calle es obligatorio.',
            'num_ext'           => 'El número exterior es requerido.',
            'num_int'           => 'Indique el número interior (use N/A si no aplica).',
            'colonia'           => 'La colonia debe coincidir con el Código Postal.',
            'cp'                => 'El Código Postal es obligatorio para la geolocalización.',
            'es_principal'      => 'Indique si esta es la dirección principal del proveedor.',

            // Finanzas (prv_det_config_financiera)
            'id_condicion_pago' => 'Debe asignar una condición de pago predeterminada.',
            'id_cuenta_contable'=> 'La cuenta contable es obligatoria para la integración con ERP.',
            'limite_credito'    => 'El límite de crédito debe ser un valor numérico.',
            'id_moneda_defecto' => 'Especifique la moneda principal de operación (MXN/USD).',
            'tasa_iva_default'  => 'Indique la tasa de IVA aplicable (ej. 16.00).',

            // Contacto (prv_det_contactos)
            'nombre'            => 'El nombre del contacto principal es obligatorio.',
            'puesto'            => 'Especifique el cargo o puesto del contacto.',
            'email'             => 'El correo electrónico es necesario para el envío de O.C.',
            'telefono'          => 'El número telefónico es obligatorio para seguimiento.',
        ];

        $supplierModel = new Prv_proveedorModel;
        $supplier = $supplierModel->findByCriteria(['rfc' => $this->data['rfc']]);

        if(!empty($supplier)) {
            $this->addError('rfc', 'Ya existe un proveedor con el RFC proporcionado.');
        }

        foreach ($requiredFields as $field => $message) {
            if (empty($this->data[$field])) {
                $this->addError($field, $message);
            }
        }

        if (!empty($this->data['correo_electronico']) && !filter_var($this->data['correo_electronico'], FILTER_VALIDATE_EMAIL)) {
            $this->addError('correo_electronico', 'El correo electrónico no es válido.');
        }

        if (!empty($this->data['rfc']) && !empty($this->data['id_tipo_persona'])) {
            $rfc = strtoupper(trim($this->data['rfc']));
            $person = $this->data['id_tipo_persona'];
            $regexMoral  = '/^[A-ZÑ&]{3}[0-9]{6}[A-Z0-9]{3}$/i';
            $regexFisica = '/^[A-ZÑ&]{4}[0-9]{6}[A-Z0-9]{3}$/i';
            
            if ($person == 'M') {
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
