<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

class StoreSupplierRequest extends Requests
{
    private \Prv_proveedorModel $prvProveedorModel;
    private ?array $currentSupplier = null;

    public function __construct() {
        parent::__construct();
        $this->prvProveedorModel = new \Prv_proveedorModel();
    }

    public function rules(): void
    {
        // Validaciones básicas (Sintaxis y Formato)
        $this->validateBasicRules(); 
        
        // Fail-fast: Si fallan las reglas básicas, no tiene caso golpear la BD
        if (!empty($this->errors)) { 
            return;
        }

        // Validaciones de Negocio (Base de Datos)
        $this->validateBusinessRules();
    }

    private function validateBasicRules():void
    {
        $requiredFields = [
            // Datos Maestros (prv_cat_proveedores)
            // 'id_empresa'        => 'La empresa origen es obligatoria para el multi-tenant.',
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
            // 'num_int'           => 'Indique el número interior (use N/A si no aplica).',
            'colonia'           => 'La colonia debe coincidir con el Código Postal.',
            'cp'                => 'El Código Postal es obligatorio para la geolocalización.',

            // Finanzas (prv_det_config_financiera)
            'id_condicion_pago' => 'Debe asignar una condición de pago predeterminada.',
            // 'cuenta_contable'=> 'La cuenta contable es obligatoria para la integración con ERP.',
            // 'limite_credito'    => 'El límite de crédito debe ser un valor numérico.',
            'id_moneda_defecto' => 'Especifique la moneda principal de operación (MXN/USD).',
            'tasa_iva_default'  => 'Indique la tasa de IVA aplicable (ej. 16.00).',

            // Contacto (prv_det_contactos)
            'nombre'            => 'El nombre del contacto principal es obligatorio.',
            'puesto'            => 'Especifique el cargo o puesto del contacto.',
            'email'             => 'El correo electrónico es necesario para el envío de O.C.',
            'telefono'          => 'El número telefónico es obligatorio para seguimiento.',
        ];

        foreach ($requiredFields as $field => $message) {
            // 1. Obtenemos el valor o un string vacío si no existe el índice (blindaje vs null)
            // 2. Forzamos a string por si llega un número (blindaje vs TypeError)
            $val = (string)($this->data[$field] ?? '');

            if (empty(trim($val))) {
                $this->addError($field, $message);
            }
        }

        $email = (string)($this->data['email'] ?? ''); // Nota: En tu captura dice 'correo_electronico' pero en el array pusiste 'email'
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError('email', 'El correo electrónico no es válido.');
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
    }

    private function validateBusinessRules(): void
    {
        $supplierId = $this->data['id'] ?? null;

        if (!empty($supplierId)) {
            // Regla: Si es edición, el proveedor DEBE existir
            // Traemos el registro y lo guardamos en caché de clase para que el Servicio lo pueda usar después
            $this->currentSupplier = current($this->prvProveedorModel->findByCriteria(['id_proveedor' => $supplierId]));
            
            if (!$this->currentSupplier) {
                $this->addError('id_proveedor', 'El proveedor que intentas actualizar no existe en el sistema.');
                return; // Detenemos validaciones posteriores
            }

            // Regla: Bloqueo de RFC para proveedores activos
            if (
                isset($this->data['rfc']) && 
                $this->currentSupplier['estatus_operativo'] == 1 &&
                $this->currentSupplier['rfc'] !== $this->data['rfc']
            ) {
                $this->addError('rfc', "Intento de cambio de RFC bloqueado para el proveedor ID: {$supplierId}");
            }
        }
    }

    public function getCurrentSupplier() {
        return $this->currentSupplier;
    }
}
