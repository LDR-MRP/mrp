<?php

class Inv_monedaService{

    public $model;

    public function show(int $id)
    {
        return ServiceResponse::success($this->model->selectMoneda($id));
    }

    public function showAll()
    {
        return ServiceResponse::success($this->model->selectMonedas());
    }
}