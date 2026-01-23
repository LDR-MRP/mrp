<?php

class Inv_inventarioService{
    public $model;

    public function show(int $id)
    {
        return ServiceResponse::success($this->model->selectInventario($id));
    }

    public function showAll()
    {
        return ServiceResponse::success($this->model->selectInventarios());
    }
}