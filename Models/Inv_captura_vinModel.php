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
            id_inventario,
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

        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())";

        return $this->insert($sql, [

            $data['modelo'],
            $data['id_inventario'],
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
    id_inventario = ?,
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
            $data['id_inventario'],
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

    /*
    |--------------------------------------------------------------------------
    | DETALLE LEGIBLE de un modelo VIN (para comparar antes / después en el
    | historial: se guardan descripciones, no ids de catálogo)
    |--------------------------------------------------------------------------
    */

    public function selectModeloVinDetalle($id)
    {
        $sql = "SELECT m.id_cat_modelo_vin, m.modelo, m.vin_base,
                       f.fabricante, tv.descripcion AS tipo_vehiculo,
                       m.peso_bruto_kg, tm.descripcion AS tipo_motor,
                       m.potencia_hp, m.distancia_ejes,
                       a.anio, p.planta, m.estado
                FROM cat_modelos_vin m
                LEFT JOIN cat_vin_fabricantes f ON f.id_fabricante = m.id_fabricante
                LEFT JOIN cat_vin_tipo_vehiculo tv ON tv.id_tipo_vehiculo = m.id_tipo_vehiculo
                LEFT JOIN cat_vin_tipo_motor tm ON tm.id_tipo_motor = m.id_tipo_motor
                LEFT JOIN cat_anio_vin a ON a.id_cat_anio_vin = m.id_cat_anio_vin
                LEFT JOIN cat_vin_plantas p ON p.id_planta = m.id_planta
                WHERE m.id_cat_modelo_vin = ?";

        return $this->select($sql, [intval($id)]);
    }

    public function selectModelosInventario()
    {

        $sql = "SELECT

                idinventario,
                cve_articulo,
                descripcion

            FROM wms_inventario

            WHERE tipo_elemento = 'P'
            AND estado = '2'

            ORDER BY descripcion ASC";

        return $this->select_all($sql);
    }

    /*
    |--------------------------------------------------------------------------
    | DATOS DE INGENIERÍA (para precargar el NIV base)
    | Configuración de Ingeniería ligada al mismo artículo de inventario.
    | Si hay varias, se prefiere la AUTORIZADA más reciente.
    |--------------------------------------------------------------------------
    */

    public function selectConfiguracionIngenieria($idInventario)
    {
        $sql = "SELECT
                    c.id_configuracion,
                    c.nombre_unidad,
                    c.estado,
                    c.peso_bruto,
                    c.combustible,
                    m.modelo_motor,
                    m.tipo_motor,
                    m.potencia,
                    m.unidad_potencia,
                    m.tipo_combustible AS motor_tipo_combustible
                FROM ing_modelo_configuracion c
                LEFT JOIN ing_cat_motor m
                    ON m.id_motor = c.id_motor
                WHERE c.id_inventario = ?
                  AND c.deleted_at IS NULL
                ORDER BY (c.estado = 'AUTORIZADO') DESC, c.id_configuracion DESC
                LIMIT 1";

        return $this->select($sql, [$idInventario]);
    }

    public function selectEspecificacionesIngenieria($idConfiguracion)
    {
        $sql = "SELECT e.clave, e.unidad, v.valor
                FROM ing_modelo_especificacion_valor v
                INNER JOIN ing_cat_especificacion e
                    ON e.id_especificacion = v.id_especificacion
                WHERE v.id_configuracion = ?
                  AND e.deleted_at IS NULL";

        return $this->select_all($sql, [$idConfiguracion]);
    }
}
