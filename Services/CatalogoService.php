<?php

class CatalogoService
{
    protected $catCondicionPagoModel;

    protected $catBancoModel;

    protected $catCodigoPostalModel;

    protected $catCuentaContableModel;

    protected $catEstadoModel;

    protected $catPaisModel;

    public function __construct()
    {
        $this->catCondicionPagoModel = new CatCondicionPagoModel;
        $this->catBancoModel = new CatBancoModel;
        $this->catCodigoPostalModel = new CatCodigoPostalModel;
        $this->catCuentaContableModel = new CatCuentaContableModel;
        $this->catEstadoModel = new CatEstadoModel;
        $this->catPaisModel = new CatPaisModel;
    }
    
    public function condicionesPago()
    {
        return ServiceResponse::success($this->catCondicionPagoModel->all());
    }

    public function bancos()
    {
        return ServiceResponse::success($this->catBancoModel->all());
    }

    public function codigospostales(mixed $cp)
    {
        $codigosPostales = $this->catCodigoPostalModel->findByCP($cp);

        $data['colonias'] = $codigosPostales;
        $data['municipio'] = array_unique(array_column($codigosPostales, 'municipio'));
        $data['ciudad'] = array_unique(array_column($codigosPostales, 'ciudad'));
        $data['estado'] = array_unique(array_column($codigosPostales, 'estado'));

        return ServiceResponse::success($data);
    }

    public function cuentasContables()
    {
        return ServiceResponse::success($this->catCuentaContableModel->all());
    }

    public function estados()
    {
        return ServiceResponse::success($this->catEstadoModel->all());
    }

    public function paises()
    {
        return ServiceResponse::success($this->catPaisModel->all());
    }
}