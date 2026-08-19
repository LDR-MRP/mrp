<?php

class Lgs_panelrutasService {

    private Lgs_panelrutasModel $model;

    public function __construct() {
        $this->model = new Lgs_panelrutasModel();
    }

    /**
     * Obtiene el listado de rutas activas con sus coordenadas mapeadas
     */
    public function getRutasActivasMapa(?int $plantaId = null): array {
        $rutas = $this->model->getRutasEnTransito($plantaId);
        
        foreach ($rutas as &$ruta) {
            $destinos = $this->model->getDetalleDestinosRuta($ruta['id_envio']);
            $ruta['destinos'] = $destinos;

            // Simulación / Integración de posición GPS actual en tiempo real vía API
            // Coordenada estimada entre Origen y Primer Destino
            $latOrigen = (float)($ruta['origen_lat'] ?? 19.432608);
            $lngOrigen = (float)($ruta['origen_lng'] ?? -99.133209);
            
            $latDestino = isset($destinos[0]['destino_lat']) ? (float)$destinos[0]['destino_lat'] : ($latOrigen + 0.5);
            $lngDestino = isset($destinos[0]['destino_lng']) ? (float)$destinos[0]['destino_lng'] : ($lngOrigen + 0.5);

            // Coordenada actual en camino (punto medio o telemetría GPS)
            $ruta['gps_actual'] = [
                'lat' => ($latOrigen + $latDestino) / 2,
                'lng' => ($lngOrigen + $lngDestino) / 2,
                'ultima_actualizacion' => date('Y-m-d H:i:s')
            ];
        }

        return $rutas;
    }
}
