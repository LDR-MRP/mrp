<?php

class SatCatalogoService
{
    protected $satCatTipoPersonaModel;

    protected $satCatRegimenFiscalModel;

    public function __construct()
    {
        $this->satCatTipoPersonaModel = new SatCatTipoPersonaModel;
        $this->satCatRegimenFiscalModel = new SatCatRegimenFiscalModel;
    }
    
    public function tiposPersonas(): ServiceResponse
    {
        return ServiceResponse::success($this->satCatTipoPersonaModel->all());
    }

    public function regimenesFiscales($tipoPersona): ServiceResponse
    {
        $regimenes = [];
        
        if($tipoPersona === 'F') {
            $regimenes = $this->satCatRegimenFiscalModel->byFisica();
        }
        else{
            $regimenes = $this->satCatRegimenFiscalModel->byMoral();
        }        

        return ServiceResponse::success($regimenes);
    }

    public function regimenFiscal(int $id): ServiceResponse
    {
        return ServiceResponse::success($this->satCatRegimenFiscalModel->byId($id));
    }
}