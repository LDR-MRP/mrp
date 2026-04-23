<?php

class Inv_tipo_cambio_monedaService
{
    public $model;

    public function getAll($filters): ServiceResponse
    {
        try {

            $data = $this->model->consultarTipoCambio(
                $filters['moneda'] ?? '',
                $filters['desde'] ?? '',
                $filters['hasta'] ?? ''
            );

            return ServiceResponse::success($data);

        } catch (Exception $e) {

            return ServiceResponse::error($e->getMessage());
        }
    }

    public function store($data): ServiceResponse
    {
        try {

            $monedaid = intval($data['monedaid']);
            $tipo = floatval($data['tipo_cambio']);
            $fecha = $data['fecha_creacion'];

            if ($monedaid <= 0 || $tipo <= 0 || empty($fecha)) {
                throw new Exception("Datos inválidos");
            }

            /* VALIDAR DUPLICADO */
            $existe = $this->model->existeTipoCambio($monedaid, $fecha);

            if ($existe) {
                throw new Exception("Ya existe tipo de cambio para esa fecha");
            }

            $this->model->insertarTipoCambio($monedaid, $tipo, $fecha);

            return ServiceResponse::success([], "Guardado correctamente");

        } catch (Exception $e) {

            return ServiceResponse::error($e->getMessage());
        }
    }
}