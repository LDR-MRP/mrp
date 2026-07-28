<?php

class Prv_madrinasRequest {

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

        return $errors;
    }
}
