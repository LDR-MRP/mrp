<?php

class Inv_captura_vinModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    /*
    |--------------------------------------------------------------------------
    | INSERT
    |--------------------------------------------------------------------------
    */

    public function insertModeloVin($data)
    {

        $sql = "INSERT INTO cat_modelos_vin (

            modelo,
            vin_base,
            id_fabricante,
            id_tipo_vehiculo,
            peso_bruto_kg,
            id_tipo_motor,
            potencia_hp,
            distancia_ejes,
            id_cat_anio_vin,
            id_planta,
            estado,
            fecha_creacion

        )

        VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())";

        return $this->insert($sql, [

            $data['modelo'],
            $data['vin_base'],
            $data['id_fabricante'],
            $data['id_tipo_vehiculo'],
            $data['peso_bruto_kg'],
            $data['id_tipo_motor'],
            $data['potencia_hp'],
            $data['distancia_ejes'],
            $data['anio'],
            $data['id_planta'],
            $data['estado']

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function updateModeloVin($data)
    {

        $sql = "UPDATE cat_modelos_vin SET

    modelo = ?,
    vin_base = ?,
    id_fabricante = ?,
    id_tipo_vehiculo = ?,
    peso_bruto_kg = ?,
    id_tipo_motor = ?,
    potencia_hp = ?,
    distancia_ejes = ?,
    id_cat_anio_vin = ?,
    id_planta = ?,
    estado = ?

WHERE id_cat_modelo_vin = ?";

        return $this->update($sql, [

            $data['modelo'],
            $data['vin_base'],
            $data['id_fabricante'],
            $data['id_tipo_vehiculo'],
            $data['peso_bruto_kg'],
            $data['id_tipo_motor'],
            $data['potencia_hp'],
            $data['distancia_ejes'],
            $data['anio'],
            $data['id_planta'],
            $data['estado'],
            $data['id']

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | LISTADO
    |--------------------------------------------------------------------------
    */

    public function getModelosVin()
    {

        $sql = "SELECT

                m.*,

                f.fabricante,
                f.wmi,

                tv.descripcion AS tipo_vehiculo,
                tv.caracter AS caracter_vehiculo,
                tv.categoria,

                tm.descripcion AS motor,
                tm.caracter AS caracter_motor,

                a.anio,
                a.codigo AS codigo_anio,

                p.planta,
                p.caracter AS caracter_planta

            FROM cat_modelos_vin m

            INNER JOIN cat_vin_fabricantes f
                ON f.id_fabricante = m.id_fabricante

            INNER JOIN cat_vin_tipo_vehiculo tv
                ON tv.id_tipo_vehiculo = m.id_tipo_vehiculo

            INNER JOIN cat_vin_tipo_motor tm
                ON tm.id_tipo_motor = m.id_tipo_motor

            INNER JOIN cat_anio_vin a
                ON a.id_cat_anio_vin = m.id_cat_anio_vin

            INNER JOIN cat_vin_plantas p
                ON p.id_planta = m.id_planta

            ORDER BY m.id_cat_modelo_vin DESC";

        return $this->select_all($sql);
    }

    /*
    |--------------------------------------------------------------------------
    | CATÁLOGOS
    |--------------------------------------------------------------------------
    */

    public function selectAniosVin()
    {

        $sql = "SELECT *
                FROM cat_anio_vin
                ORDER BY anio DESC";

        return $this->select_all($sql);
    }

    public function selectFabricantes()
    {

        $sql = "SELECT *
                FROM cat_vin_fabricantes
                WHERE estado = 1";

        return $this->select_all($sql);
    }

    public function selectTiposVehiculo()
    {

        $sql = "SELECT *
                FROM cat_vin_tipo_vehiculo
                WHERE estado = 1";

        return $this->select_all($sql);
    }

    public function selectTiposMotor()
    {

        $sql = "SELECT *
                FROM cat_vin_tipo_motor
                WHERE estado = 1";

        return $this->select_all($sql);
    }

    public function selectPlantas()
    {

        $sql = "SELECT *
                FROM cat_vin_plantas
                WHERE estado = 1";

        return $this->select_all($sql);
    }
}
