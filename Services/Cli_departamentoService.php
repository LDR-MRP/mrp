<?php

class Cli_departamentoService {
    
    protected $model;

    public function __construct($model)
    {
        $this->model = $model;
    }

    public function index(array $filters = [])
    {
        return ServiceResponse::success(
            $this->model->selectDepartamentos()
        );
    }
}