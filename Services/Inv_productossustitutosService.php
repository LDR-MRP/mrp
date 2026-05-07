<?php

class Inv_productossustitutosService
{
    private $model;

    public function __construct()
    {
        $this->model = new Inv_productossustitutosModel();
    }

    public function getListas()
    {
        return ServiceResponse::success($this->model->getListas());
    }

    public function setLista($data)
    {
        $nombre = trim($data['nombre_lista'] ?? '');

        if ($nombre === '') {
            return ServiceResponse::error('Ingresa un nombre de lista');
        }

        $exists = $this->model->existsLista($nombre);
        if ($exists) {
            return ServiceResponse::error('Ya existe una lista con ese nombre');
        }

        $insert = $this->model->insertLista($nombre);

        return $insert
            ? ServiceResponse::success([], 'Lista creada correctamente')
            : ServiceResponse::error('No fue posible guardar');
    }

    public function getProductosLista($idLista)
    {
        return ServiceResponse::success($this->model->getProductosLista($idLista));
    }

    public function setProductoLista($data)
    {
        $idLista = (int)($data['id_clave_lista'] ?? 0);
        $productos = json_decode($data['productos'] ?? '[]', true);

        if ($idLista <= 0 || empty($productos) || !is_array($productos)) {
            return ServiceResponse::error('Datos inválidos');
        }

        // limpiar ids
        $productos = array_map('intval', $productos);
        $productos = array_filter($productos);

        if (empty($productos)) {
            return ServiceResponse::error('No se recibieron productos válidos');
        }

        // regla 1: no repetir en misma captura
        if (count($productos) !== count(array_unique($productos))) {
            return ServiceResponse::error('No puede capturar más de una vez un mismo producto.');
        }

        // obtener tipos de productos seleccionados
        $tipos = $this->model->getTiposProductos($productos);

        if (count(array_unique(array_column($tipos, 'tipo_elemento'))) > 1) {
            return ServiceResponse::error('No se pueden mezclar productos de diferente tipo en la misma lista.');
        }

        $tipoNuevo = $tipos[0]['tipo_elemento'] ?? null;

        // validar tipo ya existente en lista
        $tipoLista = $this->model->getTipoLista($idLista);

        if ($tipoLista && $tipoLista !== $tipoNuevo) {
            return ServiceResponse::error('No se pueden mezclar productos de diferente tipo en la misma lista.');
        }

        $insertados = 0;
        $omitidos = 0;

        foreach ($productos as $idInventario) {

            // ya existe en esta lista
            if ($this->model->existsProductoLista($idLista, $idInventario)) {
                $omitidos++;
                continue;
            }

            // ya pertenece a otra lista
            if ($this->model->existsProductoOtraLista($idLista, $idInventario)) {
                return ServiceResponse::error('Este producto ya pertenece a otra lista de sustitutos.');
            }

            $insert = $this->model->insertProductoLista($idLista, $idInventario);

            if ($insert) {
                $insertados++;
            }
        }

        if ($insertados > 0) {
            return ServiceResponse::success([], "Productos agregados correctamente. Insertados: {$insertados}");
        }

        return ServiceResponse::error('No se pueden agregar productos duplicados en la misma lista.');
    }

    public function getInventario($search, $tipo = '')
    {
        return ServiceResponse::success($this->model->getInventario($search, $tipo));
    }

    public function getLista(int $id)
    {
        return ServiceResponse::success($this->model->getListaById($id));
    }

    public function updateLista($data)
    {
        $id = (int)($data['id_clave_lista'] ?? 0);
        $nombre = trim($data['nombre_lista'] ?? '');

        if ($id <= 0 || $nombre === '') {
            return ServiceResponse::error('Datos inválidos');
        }

        $exists = $this->model->existsListaUpdate($nombre, $id);
        if ($exists) {
            return ServiceResponse::error('Ya existe una lista con ese nombre');
        }

        $update = $this->model->updateLista($id, $nombre);

        return $update
            ? ServiceResponse::success([], 'Lista actualizada correctamente')
            : ServiceResponse::error('No fue posible actualizar');
    }

    public function deleteProductoLista($data)
    {
        $idDetalle = (int)($data['id_detalle'] ?? 0);

        if ($idDetalle <= 0) {
            return ServiceResponse::error('Registro inválido');
        }

        $delete = $this->model->deleteProductoLista($idDetalle);

        return $delete
            ? ServiceResponse::success([], 'Producto eliminado correctamente')
            : ServiceResponse::error('No fue posible eliminar el producto');
    }

    //movimiento entre listas pestaña 3
    public function moverProductosLista($data)
    {
        $origen   = (int)($data['origen'] ?? 0);
        $destino  = (int)($data['destino'] ?? 0);
        $productos = json_decode($data['productos'] ?? '[]', true);

        if ($origen <= 0 || $destino <= 0 || empty($productos) || !is_array($productos)) {
            return ServiceResponse::error('Datos inválidos');
        }

        if ($origen === $destino) {
            return ServiceResponse::error('La lista origen y destino no pueden ser la misma');
        }

        $productos = array_map('intval', $productos);
        $productos = array_filter($productos);

        if (empty($productos)) {
            return ServiceResponse::error('No se recibieron productos válidos');
        }

        $tipoOrigen  = $this->model->getTipoListaById($origen);
        $tipoDestino = $this->model->getTipoListaById($destino);

        if (!$tipoOrigen || !$tipoDestino) {
            return ServiceResponse::error('No se pudo validar el tipo de las listas');
        }

        if ($tipoOrigen !== $tipoDestino) {
            return ServiceResponse::error('Solo se pueden mover productos entre listas del mismo tipo');
        }

        $move = $this->model->moverProductosLista($origen, $destino, $productos);

        if ($move > 0) {
            return ServiceResponse::success([], 'Productos movidos correctamente');
        }

        return ServiceResponse::error('No se movió ningún producto');
    }
}
