<?php

class Catalogo extends Controllers
{
    use ApiResponser;

    protected $catalogoService;

    public function __construct()
    {
        $this->catalogoService = new CatalogoService;
    }

    public function condiciones_pago()
    {
        return $this->apiResponse($this->catalogoService->condicionesPago());
    }

    public function bancos()
    {
        return $this->apiResponse($this->catalogoService->bancos());
    }

    public function codigos_postales(mixed $cp)
    {
        return $this->apiResponse($this->catalogoService->codigospostales($cp));
    }

    public function cuentas_contables()
    {
        return $this->apiResponse($this->catalogoService->cuentasContables());
    }

    public function estados()
    {
        return $this->apiResponse($this->catalogoService->estados());
    }

    public function paises()
    {
        return $this->apiResponse($this->catalogoService->paises());
    }

    public function productLines()
    {
        return $this->apiResponse($this->catalogoService->getProductLines());
    }

    public function paymentMethods()
    {
        return $this->apiResponse($this->catalogoService->getPaymentMethods());
    }
}