<?php

class Wms_proveedorService{

    public $model;

    public function showAll(){
        return ServiceResponse::success($this->model->selectProveedores());
    }
}