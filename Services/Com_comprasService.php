<?php

class Com_comprasService{

    public function create(array $data): bool
    {
        try {
            $db = $this->model->getConexion();

            $db->beginTransaction();

            $idCompra = $this->model->insertCompra($data);

            if($idCompra <= 0){
                throw new Exception("Error al registrar la cabecera de la compra.", 500);
            }

            $detalle = json_decode($data['detalle_partidas'], true);
            foreach ($detalle as $item) {
                $this->model->insertDetalle($idCompra, $item);
            }

            $db->commit();
            
            return true;

        } catch (Exception $e) {
            if (isset($db)) $db->rollBack();
            return false;
        }
    }
}