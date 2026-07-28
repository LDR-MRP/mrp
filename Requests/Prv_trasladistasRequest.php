<?php

class Prv_trasladistasRequest {

    public static function validate(array $data): array {
        $errors = [];

        if (empty($data['rfc'])) {
            $errors[] = "El RFC es obligatorio.";
        } else {
            $rfc = strtoupper(trim($data['rfc']));
            if (!preg_match('/^[A-Z&Ñ]{3,4}\d{6}[A-Z0-9]{3}$/', $rfc)) {
                $errors[] = "El formato del RFC no es válido.";
            }
        }

        if (empty($data['razon_social'])) {
            $errors[] = "La razón social es obligatoria.";
        }

        if (empty($data['nombre_comercial'])) {
            $errors[] = "El nombre comercial es obligatorio.";
        }

        return $errors;
    }
}
