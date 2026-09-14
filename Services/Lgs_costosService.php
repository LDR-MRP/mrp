<?php

class Lgs_costosService
{
    private Lgs_costosModel $model;

    public function __construct()
    {
        $this->model = new Lgs_costosModel();
    }

    /**
     * Obtiene todos los selectores de catálogos necesarios para los formularios
     */
    public function getFormCatalogs(): array
    {
        return [
            'tipos_traslado' => $this->model->selectTiposTraslado(),
            'ubicaciones'    => $this->model->selectUbicaciones(),
            'segmentos'      => $this->model->selectSegmentos(),
            'proveedores'    => $this->model->selectProveedores(),
            'kpis'           => $this->model->getKpis()
        ];
    }

    /**
     * ==========================================
     * MÓDULO 1: DISTANCIAS
     * ==========================================
     */
    public function listDistancias(): array
    {
        return $this->model->selectDistanciasAgrupadas();
    }

    public function saveDistancia(array $data): bool
    {
        $idA = intval($data['id_ubicacion_a'] ?? 0);
        $idB = intval($data['id_ubicacion_b'] ?? 0);
        $km = floatval($data['km'] ?? 0);

        if ($idA <= 0 || $idB <= 0) {
            throw new Exception("Debe seleccionar ambas ubicaciones.", 400);
        }
        if ($idA === $idB) {
            throw new Exception("El origen y destino no pueden ser la misma ubicación.", 400);
        }
        if ($km <= 0) {
            throw new Exception("La distancia en kilómetros debe ser mayor a 0.", 400);
        }

        return $this->model->saveDistancia($idA, $idB, $km);
    }

    public function deleteDistancia(int $idDistancia): bool
    {
        if ($idDistancia <= 0) {
            throw new Exception("ID de distancia inválido.", 400);
        }
        return $this->model->deleteDistancia($idDistancia);
    }

    /**
     * ==========================================
     * MÓDULO 2: TARIFAS POR PROVEEDOR
     * ==========================================
     */
    public function getTarifasProveedor(?int $idProveedor = null): array
    {
        return $this->model->selectTarifasProveedor($idProveedor);
    }

    public function saveTarifasProveedor(array $data): bool
    {
        $idProveedor = isset($data['id_proveedor']) ? intval($data['id_proveedor']) : 0;
        
        $madrinaSegs = $data['madrina_segmentos'] ?? [];
        $choferSegs = $data['chofer_segmentos'] ?? [];

        return $this->model->saveTarifasProveedor($idProveedor, $madrinaSegs, $choferSegs);
    }

    public function saveTarifasBaseConReplicacion(array $data, array $proveedoresReplicar): bool
    {
        $madrinaSegs = $data['madrina_segmentos'] ?? [];
        $choferSegs = $data['chofer_segmentos'] ?? [];

        return $this->model->saveTarifasBaseConReplicacion($madrinaSegs, $choferSegs, $proveedoresReplicar);
    }

    public function getProveedoresConEstadoTarifa(): array
    {
        return $this->model->selectProveedoresConEstadoTarifa();
    }

    public function resetTarifasProveedor(int $idProveedor): bool
    {
        if ($idProveedor <= 0) {
            throw new Exception("ID de proveedor inválido para restablecer.", 400);
        }
        return $this->model->resetTarifasProveedor($idProveedor);
    }

    /**
     * ==========================================
     * OTROS MÉTODOS EXISTENTES
     * ==========================================
     */
    public function listModelosVin(): array
    {
        return $this->model->selectModelosVin();
    }

    public function setSegmentoModelo(int $idModelo, ?int $idSegmento): bool
    {
        if ($idModelo <= 0) {
            throw new Exception("ID de modelo de VIN inválido.", 400);
        }
        return $this->model->updateModeloSegmento($idModelo, $idSegmento);
    }
}
