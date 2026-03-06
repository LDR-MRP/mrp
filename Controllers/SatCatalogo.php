<?php

class SatCatalogo extends Controllers
{
    use ApiResponser;

    protected $satCatalogoService;

    public function __construct()
    {
        $this->satCatalogoService = new SatCatalogoService;
    }

    public function tipos_personas()
    {
        return $this->apiResponse($this->satCatalogoService->tiposPersonas());
    }

    public function regimenes_fiscales($tipoPersona)
    {
        return $this->apiResponse($this->satCatalogoService->regimenesFiscales($tipoPersona));
    }
}