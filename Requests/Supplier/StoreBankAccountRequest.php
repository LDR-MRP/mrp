<?php

declare(strict_types=1);

namespace Requests\Supplier;

use Requests;

class StoreBankAccountRequest extends Requests
{
    private \Prv_detCuentaBancariaModel $prv_detCuentaBancariaModel;

    private \CatBancoModel $catBancoModel;

    public function __construct() 
    {
        parent::__construct();
        $this->prv_detCuentaBancariaModel = new \Prv_detCuentaBancariaModel();
        $this->catBancoModel = new \CatBancoModel();
    }

    public function rules(): void
    {
        // Validaciones de Formato y Condicionales (Nacional vs Extranjero)
        $this->validateBasicRules(); 
        
        // Fail-Fast: Si el formato es incorrecto, abortamos sin tocar la BD
        if (!empty($this->errors)) { 
            return;
        }

        // Validaciones de Negocio (Prevención de Fraude y Cruces Relacionales)
        $this->validateBusinessRules();
    }

    /**
     * FASE 1: Sanitización, tipos de datos y reglas condicionales de la UI.
     */
    private function validateBasicRules(): void
    {
        // Identificadores Base
        if (empty($this->data['id_proveedor']) || !is_numeric($this->data['id_proveedor'])) {
            $this->addError('id_proveedor', 'El identificador del proveedor es inválido o no fue proporcionado.');
        }

        if (empty($this->data['id_banco'])) {
            $this->addError('id_banco', 'Debe seleccionar una institución bancaria.');
        }

        if (empty($this->data['id_moneda_banco'])) {
            $this->addError('id_moneda_banco', 'Debe seleccionar una moneda valida.');
        }

        // Reglas Condicionales (Nacional vs Transferencia Internacional)
        $idMoneda = $this->data['id_moneda_banco'];
        $esNacional = ($this->data['id_moneda_banco'] === 'MXN');

        if ($esNacional) {
            // Reglas estrictas para México (SPEI)
            if (empty($this->data['clabe'])) {
                $this->addError('clabe', 'La CLABE interbancaria es obligatoria para cuentas en MXN.');
            } elseif (!preg_match('/^\d{18}$/', $this->data['clabe'])) {
                $this->addError('clabe', 'La CLABE debe contener exactamente 18 dígitos numéricos.');
            }
        } else {
            // Reglas para Transferencias Internacionales (USD / EUR)
            $hasSwift = !empty($this->data['swift_bic']);
            $hasIban  = !empty($this->data['iban']);

            // Regex mejorado: (Exactamente 8 caracteres O exactamente 11 caracteres)
            if ($idMoneda && $hasSwift && !preg_match('/^([A-Z0-9]{8}|[A-Z0-9]{11})$/i', $this->data['swift_bic'])) {
                $this->addError('swift_bic', 'El SWIFT/BIC debe tener 8 u 11 caracteres alfanuméricos.');
            }

            // Sugerencia extra: Validación básica de IBAN (Si existe)
            if ($idMoneda && $hasIban && !preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/i', $this->data['iban'])) {
                $this->addError('iban', 'El formato del IBAN es inválido (debe iniciar con código de país, ej: US, ES, DE).');
            }
        }

        // Cuenta local (Opcional, pero si viene debe ser limpia)
        if (!empty($this->data['cuenta']) && !preg_match('/^\d{10,20}$/', $this->data['cuenta'])) {
            $this->addError('cuenta', 'El número de cuenta debe contener entre 10 y 20 dígitos numéricos.');
        }

        // Flag de Cuenta Principal
        if (!isset($this->data['banco_es_principal']) || !in_array((int)$this->data['banco_es_principal'], [0, 1], true)) {
            $this->addError('banco_es_principal', 'El indicador de cuenta principal está mal formado.');
        }
    }

    /**
     * FASE 2: Prevención de Fraude y Lógica de Negocio en Base de Datos.
     */
    private function validateBusinessRules(): void
    {
        // 1. Prevención de Fraude (CLABE Duplicada)
        // Ningún proveedor debería tener la misma CLABE que otro. Si esto pasa, 
        // alguien está intentando desviar fondos a una cuenta ya conocida.
        if (!empty($this->data['clabe'])) {
            $cuentaExistente = $this->prv_detCuentaBancariaModel->findByClabe($this->data['clabe']);
            
            if ($cuentaExistente) {
                // Si la CLABE existe, verificamos si le pertenece a OTRO proveedor
                if ((int)$cuentaExistente['id_proveedor'] !== (int)$this->data['id_proveedor']) {
                    $this->addError('clabe', 'ALERTA: Esta CLABE ya se encuentra registrada a nombre de otro proveedor en el sistema. Contacte a Contraloría.');
                } else {
                    $this->addError('clabe', 'Este proveedor ya tiene registrada esta misma CLABE.');
                }
            }
        }

        // 2. Validación de Integridad Referencial
        // Asegurarnos que el id_banco enviado realmente existe en nuestro cat_bancos
        if (!$this->catBancoModel->findById($this->data['id_banco'])) {
            $this->addError('id_banco', 'La institución bancaria seleccionada no existe en el catálogo oficial.');
        }

        // (Opcional) 3. Límite de cuentas por proveedor
        // Si las políticas dictan que un proveedor no puede tener más de 5 cuentas:
        /*
        if ($this->bancosModel->countCuentasByProveedor($this->data['id_proveedor']) >= 5) {
            $this->addError('general', 'El proveedor ha alcanzado el límite máximo de cuentas bancarias permitidas.');
        }
        */
    }
}