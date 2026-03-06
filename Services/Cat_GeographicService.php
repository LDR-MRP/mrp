<?php

class Cat_GeographicService{

protected $CPModel;

    public function __construct()
    {
        $this->CPModel = new Cat_CodigoPostalModel;
    }

    public function consultaCP(mixed $cp)
    {
        $data = $this->CPModel->findByCP($cp);

        return ServiceResponse::success($data);
        
    }
}