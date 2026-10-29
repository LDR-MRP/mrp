<?php

declare(strict_types=1);

enum DocumentTypeEnum: string
{
    case CONSTITUTIVA = 'acta_constitutiva';
    case PODER = 'poder_notarial';
    case CSF = 'constancia_situacion_fiscal';
    case RFC = 'rfc_cedula';
    case ID = 'identificacion_oficial';
    case DOMICILIO = 'comprobante_domicilio';
    case TAX_ID = 'tax_id_extranjero';
    case PASAPORTE = 'pasaporte_internacional';
    case REPSE = 'repse';
    /**
     * Retorna la configuración de documentos requeridos según el perfil del proveedor.
     * 
     * @param string $tipoPersona 'F' (Física) o 'M' (Moral)
     * @param string $origen 'Nacional' o 'Extranjero'
     * @return array<string, array>
     */
    public static function getRequiredList(string $tipoPersona, string $origen): array
    {
        $list = [];

        if ($origen === 'Nacional') {
            // Documentos para Mexicanos
            if ($tipoPersona === 'M') {
                $list[] = self::CONSTITUTIVA;
                $list[] = self::PODER;
                $list[] = self::REPSE;
            }
            $list[] = self::CSF;
            $list[] = self::RFC;
            $list[] = self::ID;
            $list[] = self::DOMICILIO;
        } else {
            // Documentos para Extranjeros
            $list[] = self::TAX_ID;
            $list[] = self::PASAPORTE;
            $list[] = self::DOMICILIO;
        }
        // Mapear al formato que espera tu UI
        $response = [];
        foreach ($list as $doc) {
            $config = $doc->getConfig();
            // Si es extranjero, marcar como no requerido
            if ($origen !== 'Nacional' && $doc === self::DOMICILIO) {
                $config['required'] = false;
            }
            $response[$doc->value] = $config;
        }

        return $response;
    }

    /**
     * Metadatos para el renderizado de la UI
     */
    public function getConfig(): array
    {
        return match($this) {
            self::CONSTITUTIVA => ['name' => 'Acta Constitutiva', 'required' => true, 'ext' => 'pdf'],
            self::PODER => ['name' => 'Poder Notarial', 'required' => false, 'ext' => 'pdf'],
            self::CSF => ['name' => 'Constancia de Situación Fiscal', 'required' => true, 'ext' => 'pdf'],
            self::RFC => ['name' => 'Cédula de RFC', 'required' => false, 'ext' => 'pdf'],
            self::ID => ['name' => 'Identificación Oficial', 'required' => true, 'ext' => 'pdf'],
            self::DOMICILIO => ['name' => 'Comprobante de Domicilio', 'required' => true, 'ext' => 'pdf'],
            self::TAX_ID => ['name' => 'Tax ID / Registro Fiscal', 'required' => false, 'ext' => 'pdf'],
            self::PASAPORTE => ['name' => 'Pasaporte o ID Internacional', 'required' => false, 'ext' => 'pdf'],
            self::REPSE => ['name' => 'REPSE', 'required' => false, 'ext' => 'pdf'],
        };
    }
}