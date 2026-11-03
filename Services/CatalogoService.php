<?php

class CatalogoService
{
    protected CatCondicionPagoModel $catCondicionPagoModel;

    protected CatBancoModel $catBancoModel;

    protected CatCodigoPostalModel $catCodigoPostalModel;

    protected CatCuentaContableModel $catCuentaContableModel;

    protected CatEstadoModel $catEstadoModel;

    protected CatPaisModel $catPaisModel;

    protected CatMetodosPago $catMetodosPago;

    protected Cap_plantasModel $catPlantsModel;

    protected Inv_lineasdproductoModel $lineasProductoModel;

    public function __construct()
    {
        $this->catCondicionPagoModel = new CatCondicionPagoModel;
        $this->catBancoModel = new CatBancoModel;
        $this->catCodigoPostalModel = new CatCodigoPostalModel;
        $this->catCuentaContableModel = new CatCuentaContableModel;
        $this->catEstadoModel = new CatEstadoModel;
        $this->catPaisModel = new CatPaisModel;
        $this->catMetodosPago = new CatMetodosPago;
        $this->lineasProductoModel = new Inv_lineasdproductoModel;
        $this->catPlantsModel = new Cap_plantasModel;
    }
    
    public function condicionesPago()
    {
        return ServiceResponse::success($this->catCondicionPagoModel->all());
    }

    public function bancos()
    {
        $bancos = $this->catBancoModel->all();
        error_log('[CatalogoService::bancos] Bancos devueltos: ' . json_encode($bancos));
        return ServiceResponse::success($bancos);
    }

    public function guardarBanco(array $data, int $userId)
    {
        // Generar ID manualmente (la tabla no tiene AUTO_INCREMENT)
        $maxIdResult = $this->catBancoModel->select("SELECT MAX(id_banco) as max_id FROM cat_bancos");
        $nextId = ($maxIdResult[0]['max_id'] ?? 0) + 1;

        $sql = "INSERT INTO cat_bancos (
                    id_banco,
                    nombre_corto,
                    razon_social,
                    origen_registro,
                    estatus
                ) VALUES (?, ?, ?, ?, 1)";

        $values = [
            $nextId,
            $data['nombre_corto'],
            $data['razon_social'],
            $data['origen_registro'] ?? 'USUARIO'
        ];

        $bancoId = $this->catBancoModel->insert($sql, $values);

        if (!$bancoId) {
            return ServiceResponse::error("No se pudo crear el banco.");
        }

        return ServiceResponse::success(['id_banco' => $nextId], "Banco registrado exitosamente.", 201);
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

    public function getProductLines()
    {
        return ServiceResponse::success($this->lineasProductoModel->selectLineasProductos());
    }

    public function getPaymentMethods()
    {
        return ServiceResponse::success($this->catMetodosPago->all());
    }

    public function getPlants(array $filters)
    {
        return ServiceResponse::success($this->catPlantsModel->findByCriteria($filters));
    }


}