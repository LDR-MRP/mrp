<?php

class Prv_choferesRequest {

    public static function validate(array $data): array {
        $errors = [];

        if (empty($data['id_proveedor']) || intval($data['id_proveedor']) <= 0) {
            $errors[] = "Debe seleccionar una empresa Trasladista.";
        }

        if (empty($data['nombre'])) {
            $errors[] = "El nombre es obligatorio.";
        }

        if (empty($data['apellidos'])) {
            $errors[] = "Los apellidos son obligatorios.";
        }

        if (empty($data['num_licencia'])) {
            $errors[] = "El número de licencia es obligatorio.";
        }

        return $errors;
    }
}
