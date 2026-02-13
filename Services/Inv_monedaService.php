<?php

class Inv_monedaService{

    public $model;

    public function index(array $filters)
    {
        return ServiceResponse::success($this->model->all($filters));
    }
}