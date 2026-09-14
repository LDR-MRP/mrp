<?php

/**
 * Servicio para integracion con la API de Google Maps (Distance Matrix / Geocoding)
 * y calculo de distancias logisticas para envios y paradas.
 */
class GoogleMapsService {

    private string $apiKey;

    public function __construct(?string $apiKey = null) {
        $this->apiKey = $apiKey ?? (defined('GOOGLE_MAPS_API_KEY') ? GOOGLE_MAPS_API_KEY : '');
    }

    /**
     * Calcula la distancia en kilometros entre dos puntos por coordenadas (lat, lng)
     */
    public function calcularDistanciaCoords(float $lat1, float $lng1, float $lat2, float $lng2): float {
        if ($lat1 == $lat2 && $lng1 == $lng2) {
            return 0.0;
        }

        // Si tenemos API Key configurada, consultar Google Distance Matrix API
        if (!empty($this->apiKey)) {
            $kmGoogle = $this->queryGoogleDistanceMatrix("{$lat1},{$lng1}", "{$lat2},{$lng2}");
            if ($kmGoogle !== null) {
                return $kmGoogle;
            }
        }

        // Fallback: Haversine Formula con factor de ruta terrestre (1.25x)
        return $this->haversineDistance($lat1, $lng1, $lat2, $lng2);
    }

    /**
     * Calcula la distancia en kilometros entre dos direcciones en texto libre
     */
    public function calcularDistanciaTexto(string $origenText, string $destinoText): float {
        if (empty(trim($origenText)) || empty(trim($destinoText))) {
            return 0.0;
        }

        if (!empty($this->apiKey)) {
            $kmGoogle = $this->queryGoogleDistanceMatrix(urlencode($origenText), urlencode($destinoText));
            if ($kmGoogle !== null) {
                return $kmGoogle;
            }
        }

        // Si no hay API key o falla, estimar una distancia base representativa
        return 120.0;
    }

    /**
     * Consulta la API de Google Distance Matrix via cURL/file_get_contents
     */
    private function queryGoogleDistanceMatrix(string $origin, string $destination): ?float {
        try {
            $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins={$origin}&destinations={$destination}&units=metric&key={$this->apiKey}";
            
            $ctx = stream_context_create([
                'http' => [
                    'timeout' => 4,
                    'user_agent' => 'LDR-MRP-Logistica/1.0'
                ]
            ]);
            $json = @file_get_contents($url, false, $ctx);
            if (!$json) return null;

            $data = json_decode($json, true);
            if (isset($data['status']) && $data['status'] === 'OK') {
                $element = $data['rows'][0]['elements'][0] ?? null;
                if ($element && isset($element['status']) && $element['status'] === 'OK') {
                    $meters = $element['distance']['value'] ?? 0;
                    return round($meters / 1000.0, 2);
                }
            }
        } catch (Throwable $e) {
            // Fallback silencioso
        }
        return null;
    }

    /**
     * Formula Haversine (Gran Circulo) ajustada por factor de carretera (1.25x)
     */
    public function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $earthRadiusKm = 6371.0;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $directKm = $earthRadiusKm * $c;

        // Multiplicador terrestre realista (las carreteras mexicanas son ~1.25x la distancia en linea recta)
        $roadDistanceKm = $directKm * 1.25;

        return round($roadDistanceKm, 2);
    }
}
