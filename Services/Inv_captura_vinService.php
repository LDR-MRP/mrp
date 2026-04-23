<?php

class Inv_captura_vinService
{

    public $model;

    public function index(array $filters)
    {
        return ServiceResponse::success($this->model->all($filters));
    }


    public function store($data)
    {
        foreach ($data as $key => $val) {
            if ($key !== "estado" && $key !== "id" && empty($val)) {
                return ServiceResponse::error("Todos los campos son obligatorios");
            }
        }

        if (empty($data['anio'])) {
            return ServiceResponse::error("Debes seleccionar un año");
        }

        // 🔥 SI EXISTE ID → UPDATE
        if (!empty($data['id'])) {
            $exist = $this->model->select(
        "SELECT id_cat_modelo_vin 
         FROM cat_modelos_vin 
         WHERE modelo = ?
         AND digt_pais = ?
         AND digit_fabricante = ?
         AND digit_vehiculo = ?
         AND digit_modelo = ?
         AND digit_cuerpo = ?
         AND digit_sujecion = ?
         AND digit_transmision = ?
         AND digit_motor = ?
         AND id_cat_anio_vin = ?
         AND digit_fabricacion = ?
         AND id_cat_modelo_vin != ?",
        [
            $data['modelo'],
            $data['digt_pais'],
            $data['digit_fabricante'],
            $data['digit_vehiculo'],
            $data['digit_modelo'],
            $data['digit_cuerpo'],
            $data['digit_sujecion'],
            $data['digit_transmision'],
            $data['digit_motor'],
            $data['anio'],
            $data['planta'],
            $data['id']
        ]
    );

    if (!empty($exist)) {
        return ServiceResponse::error("Ya existe este VIN configurado");
    }

            // 🔹 UPDATE
            $update = $this->model->updateModeloVin($data);

            if ($update) {
                return ServiceResponse::success([], "Modelo VIN actualizado");
            }

            return ServiceResponse::error("Error al actualizar");
        }

        // 🔹 INSERT NORMAL
        $exist = $this->model->select(
            "SELECT id_cat_modelo_vin 
     FROM cat_modelos_vin 
     WHERE modelo = ?
     AND digt_pais = ?
     AND digit_fabricante = ?
     AND digit_vehiculo = ?
     AND digit_modelo = ?
     AND digit_cuerpo = ?
     AND digit_sujecion = ?
     AND digit_transmision = ?
     AND digit_motor = ?
     AND id_cat_anio_vin = ?
     AND digit_fabricacion = ?",
            [
                $data['modelo'],
                $data['digt_pais'],
                $data['digit_fabricante'],
                $data['digit_vehiculo'],
                $data['digit_modelo'],
                $data['digit_cuerpo'],
                $data['digit_sujecion'],
                $data['digit_transmision'],
                $data['digit_motor'],
                $data['anio'],
                $data['planta']
            ]
        );

        if (!empty($exist)) {
            return ServiceResponse::error("El modelo ya existe");
        }

        $insert = $this->model->insertModeloVin($data);

        if ($insert) {
            return ServiceResponse::success([], "Modelo VIN guardado");
        }

        return ServiceResponse::error("Error al guardar");
    }

    public function getAll()
    {
        return ServiceResponse::success(
            $this->model->getModelosVin()
        );
    }
}
