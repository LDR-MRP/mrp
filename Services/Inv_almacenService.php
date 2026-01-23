<?php

class Inv_almacenService{
    
    public $model;

    public function showAll()
    {
        return ServiceResponse::success($this->model->selectAlmacenes());
    }
}