<?php

class Com_comprasService{

    public $model;

    public function index(): array
    {
        return $this->model->selectCompras();
    }

    public function create(array $data): ServiceResponse
    {
        try {
            $request = new Com_comprasRequest($data);

            $request->validate();

            $validated = $request->all();

            $db = $this->model->getConexion();

            $db->beginTransaction();

            $idCompra = $this->model->insertCompra($validated);

            if($idCompra <= 0){
                throw new Exception("Error al registrar la cabecera de la compra.", 500);
            }

            $detalle = json_decode($validated['detalle_partidas'], true);
            foreach ($detalle as $item) {
                $this->model->insertDetalle($idCompra, $item);
            }

            $db->commit();
            
            return ServiceResponse::success(
                [
                    'compraid' => $idCompra,
                ],
                "Compra creada",
                201
            );

        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();

            $code = $e->getCode();

            return ServiceResponse::error(
                ($code == 422) ? "Errores de validación" : "Error de sistema",
                ($code >= 400 && $code <= 599) ? $code : 500,
                ($code !== 422) ? null : json_decode($e->getMessage())
            );
        }
    }
}