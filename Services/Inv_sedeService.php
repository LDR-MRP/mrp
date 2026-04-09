<?php

class Inv_sedeService{

    public $model;

    public function index(array $filters)
    {
        return ServiceResponse::success($this->model->all($filters));
    }
}