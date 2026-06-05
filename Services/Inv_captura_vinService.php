<?php

class Inv_captura_vinService
{

    public $model;

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store($data)
    {

        // VALIDACIÓN
        foreach ($data as $key => $val) {

            if ($key != "id" && empty($val)) {

                return ServiceResponse::error(
                    "Todos los campos son obligatorios"
                );
            }
        }

        // VALIDAR DUPLICADOS
        $exist = $this->model->select(

            "SELECT id_cat_modelo_vin

            FROM cat_modelos_vin

            WHERE modelo = ?
            AND id_fabricante = ?
            AND id_tipo_vehiculo = ?
            AND peso_bruto_kg = ?
            AND id_tipo_motor = ?
            AND potencia_hp = ?
            AND distancia_ejes = ?
            AND id_cat_anio_vin = ?
            AND id_planta = ?",

            [

                $data['modelo'],
                $data['id_fabricante'],
                $data['id_tipo_vehiculo'],
                $data['peso_bruto_kg'],
                $data['id_tipo_motor'],
                $data['potencia_hp'],
                $data['distancia_ejes'],
                $data['anio'],
                $data['id_planta']

            ]

        );

        // UPDATE
        if (!empty($data['id'])) {

            $update = $this->model->updateModeloVin($data);

            if ($update) {

                return ServiceResponse::success(
                    [],
                    "Modelo VIN actualizado"
                );
            }

            return ServiceResponse::error(
                "Error al actualizar"
            );
        }

        // INSERT
        if (!empty($exist)) {

            return ServiceResponse::error(
                "Ya existe esta configuración VIN"
            );
        }

        $insert = $this->model->insertModeloVin($data);

        if ($insert) {

            return ServiceResponse::success(
                [],
                "Modelo VIN guardado"
            );
        }

        return ServiceResponse::error(
            "Error al guardar"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL
    |--------------------------------------------------------------------------
    */

    public function getAll()
    {

        return ServiceResponse::success(
            $this->model->getModelosVin()
        );
    }
}