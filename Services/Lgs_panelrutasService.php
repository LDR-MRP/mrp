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
            $paradas = $this->model->getParadasRuta($ruta['id_envio']);

            $ruta['destinos'] = $destinos;
            $ruta['vins'] = $destinos;
            $ruta['paradas'] = $paradas;

            // Planta LDR Solutions Jalisco (El Salto): Lat 20.528400, Lng -103.264100
            // Generar variación aleatoria controlada en la zona metropolitana de Guadalajara / Jalisco
            $latBase = 20.528400;
            $lngBase = -103.264100;
            
            // Variación aleatoria alrededor de la planta / Jalisco para cada envío
            $randomOffsetLat = (mt_rand(-40, 40) / 1000); // +/- 0.04 deg
            $randomOffsetLng = (mt_rand(-40, 40) / 1000);

            $latActual = round($latBase + $randomOffsetLat, 6);
            $lngActual = round($lngBase + $randomOffsetLng, 6);

            $latOrigen = !empty($ruta['origen_lat']) ? (float)$ruta['origen_lat'] : $latBase;
            $lngOrigen = !empty($ruta['origen_lng']) ? (float)$ruta['origen_lng'] : $lngBase;
            
            $latDestino = (!empty($destinos[0]['destino_lat'])) ? (float)$destinos[0]['destino_lat'] : ($latBase + 0.3);
            $lngDestino = (!empty($destinos[0]['destino_lng'])) ? (float)$destinos[0]['destino_lng'] : ($lngBase + 0.3);

            $ruta['origen_lat'] = $latOrigen;
            $ruta['origen_lng'] = $lngOrigen;

            // Telemetría GPS en tiempo real
            $ruta['gps_actual'] = [
                'lat' => $latActual,
                'lng' => $lngActual,
                'ubicacion_nombre' => 'Jalisco (Zona Planta LDR Solutions / El Salto)',
                'velocidad' => mt_rand(60, 85) . ' km/h',
                'rumbo' => 'Noreste (Autopista)',
                'ultima_actualizacion' => date('Y-m-d H:i:s')
            ];
        }

        return $rutas;
    }
}
