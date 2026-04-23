<?php

class Inv_captura_vinModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function insertModeloVin($data)
    {
        $sql = "INSERT INTO cat_modelos_vin
    (modelo, digt_pais, digit_fabricante, digit_vehiculo, digit_modelo,
     digit_cuerpo, digit_sujecion, digit_transmision, digit_motor,
     id_cat_anio_vin, digit_fabricacion, fecha_creacion, estado)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?)";

        return $this->insert($sql, [
            $data['modelo'],
            $data['digt_pais'],
            $data['digit_fabricante'],
            $data['digit_vehiculo'],
            $data['digit_modelo'],
            $data['digit_cuerpo'],
            $data['digit_sujecion'],
            $data['digit_transmision'],
            $data['digit_motor'],
            $data['anio'],        // 👈 ahora es FK
            $data['planta'],      // 👈 digit_fabricacion
            $data['estado']
        ]);
    }

    public function selectAniosVin()
    {
        $sql = "SELECT id_cat_anio_vin, anio, codigo 
            FROM cat_anio_vin
            ORDER BY anio DESC";

        return $this->select_all($sql);
    }

    public function getModelosVin()
    {
        $sql = "SELECT m.id_cat_modelo_vin, m.modelo,
               m.digt_pais, m.digit_fabricante, m.digit_vehiculo,
               m.digit_modelo, m.digit_cuerpo, m.digit_sujecion,
               m.digit_transmision, m.digit_motor,
               m.id_cat_anio_vin, 
               a.anio, a.codigo,
               m.digit_fabricacion,
               m.estado
        FROM cat_modelos_vin m
        INNER JOIN cat_anio_vin a 
            ON a.id_cat_anio_vin = m.id_cat_anio_vin
        ORDER BY m.id_cat_modelo_vin DESC";

        return $this->select_all($sql);
    }

    public function updateModeloVin($data)
    {
        $sql = "UPDATE cat_modelos_vin SET
        modelo = ?,
        digt_pais = ?,
        digit_fabricante = ?,
        digit_vehiculo = ?,
        digit_modelo = ?,
        digit_cuerpo = ?,
        digit_sujecion = ?,
        digit_transmision = ?,
        digit_motor = ?,
        id_cat_anio_vin = ?,
        digit_fabricacion = ?,
        estado = ?
        WHERE id_cat_modelo_vin = ?";

        return $this->update($sql, [
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
            $data['estado'],
            $data['id']
        ]);
    }
}
