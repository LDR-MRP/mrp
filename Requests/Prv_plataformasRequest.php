<?php

class Prv_plataformasRequest {

    public static function validate(array $data): array {
        $errors = [];

        if (empty($data['id_proveedor']) || intval($data['id_proveedor']) <= 0) {
            $errors[] = "Debe seleccionar una empresa Trasladista.";
        }

        if (empty($data['numero_economico'])) {
            $errors[] = "El número económico es obligatorio.";
        }

        if (empty($data['placas'])) {
            $errors[] = "Las placas son obligatorias.";
        }

        $capacidad = isset($data['capacidad_vehiculos']) ? intval($data['capacidad_vehiculos']) : 4;
        if ($capacidad <= 0) {
            $errors[] = "La capacidad debe ser mayor a 0.";
        } elseif ($capacidad > 4) {
            $errors[] = "La capacidad máxima permitida para una plataforma es de 4 unidades.";
        }

        return $errors;
    }
}
