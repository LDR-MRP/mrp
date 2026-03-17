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

    public function regimenes_fiscales(mixed $tipoPersona)
    {
        return $this->apiResponse($this->satCatalogoService->regimenesFiscales($tipoPersona));
    }

    public function regimen_fiscal(int $id)
    {
        return $this->apiResponse($this->satCatalogoService->regimenFiscal($id));
    }
}