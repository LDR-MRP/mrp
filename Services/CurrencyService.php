<?php

class CurrencyService
{
    use Loggable;

    private Inv_monedaModel $monedaModel;

    public function __construct() {
        $this->monedaModel = new Inv_monedaModel();
    }

    public function getAll(): ServiceResponse
    {
        $currencies = $this->monedaModel->selectMonedas();
        return ServiceResponse::success($currencies);
    }
}