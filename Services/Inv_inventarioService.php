<?php

class Inv_inventarioService{
    public $model;

    public function items(array $filters = []): ServiceResponse
    {
        return ServiceResponse::success($this->model->items($filters));
    }
}