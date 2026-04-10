<?php

class Inv_zonaService{

    public $model;

    public function index(array $filters)
    {
        return ServiceResponse::success($this->model->all($filters));
    }
}