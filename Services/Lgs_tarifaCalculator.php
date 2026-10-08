<?php

declare(strict_types=1);

class Lgs_tarifaCalculator
{
    /**
     * Obtiene la tarifa aplicable consultando lgs_tarifas_proveedores
     */
    public function getTarifaAplicable(PDO $db, int $idTipoTraslado, int $idProveedor, int $idSegmento, int $volumenVins): array
    {
        // 1. Tarifa del proveedor específico
        if ($idProveedor > 0) {
            $sql = "SELECT id_tarifa, costo_por_km, precio_plano, precio_slc, precio_sll, factor 
                    FROM lgs_tarifas_proveedores 
                    WHERE id_proveedor = ? AND id_tipo_traslado = ? AND id_segmento = ? 
                      AND ? BETWEEN num_vins_min AND num_vins_max 
                      AND activo != 0 
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idProveedor, $idTipoTraslado, $idSegmento, $volumenVins]);
            $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($tarifa) {
                return $tarifa;
            }
        }

        // 2. Tarifa base general (id_proveedor = 0)
        $sql = "SELECT id_tarifa, costo_por_km, precio_plano, precio_slc, precio_sll, factor 
                FROM lgs_tarifas_proveedores 
                WHERE id_proveedor = 0 AND id_tipo_traslado = ? AND id_segmento = ? 
                  AND ? BETWEEN num_vins_min AND num_vins_max 
                  AND activo != 0 
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipoTraslado, $idSegmento, $volumenVins]);
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tarifa) {
            return $tarifa;
        }

        // 3. Fallback por defecto según reglas 2026
        $defaultSegmentos = [
            1 => 18.00, // Ligeros
            2 => 20.00, // Medianos
            3 => 25.00, // Pesados
            4 => 25.00, // Buses
            5 => 80.00  // Lowboy
        ];
        $costoPorKm = $defaultSegmentos[$idSegmento] ?? 20.00;

        $factor = 1.0;
        if ($idTipoTraslado === 1) { // Madrina
            $preciosMadrina = [2 => 40.0, 3 => 26.66, 4 => 17.0, 5 => 17.0, 6 => 17.0, 7 => 15.0, 8 => 15.0, 9 => 13.0, 10 => 13.0];
            $precioBase = $preciosMadrina[$volumenVins] ?? 17.0;
            $factor = round($precioBase / $costoPorKm, 4);
        } elseif ($idTipoTraslado === 3) { // Plataforma
            $factor = ($volumenVins <= 1) ? 2.5 : ($volumenVins === 2 ? 1.5 : 1.0);
        }

        return [
            'id_tarifa'    => null,
            'costo_por_km' => $costoPorKm,
            'precio_plano' => 0.00,
            'precio_slc'   => 1440.00,
            'precio_sll'   => 2240.00,
            'factor'       => $factor,
        ];
    }

    /**
     * Calcula el monto a cobrar por KM adicional por cambio de ruta
     * respetando tarifas vigentes y posibles saltos de clasificación (SLC -> SLL -> Foráneo).
     */
    public function calcularKmAdicional(PDO $db, array $envioInfo, float $kmAdicionales, int $vinsSeleccionadosCount): array
    {
        $kmAprobados = floatval($envioInfo['km_total'] ?? 0.0);
        $kmNuevo = $kmAprobados + $kmAdicionales;

        $tipoTraslado = intval($envioInfo['id_tipo_traslado'] ?? 1);
        $idProveedor = intval($envioInfo['id_proveedor'] ?? 0);
        $idSegmento = intval($envioInfo['id_segmento_dominante'] ?? 1);
        $totalVinsEnvio = max(1, intval($envioInfo['total_vins'] ?? 1));

        $servicioOriginal = $this->clasificarServicio($kmAprobados);
        $servicioNuevo = $this->clasificarServicio($kmNuevo);

        // Usamos el volumen original del envío para mantener el factor pactado
        $tarifa = $this->getTarifaAplicable($db, $tipoTraslado, $idProveedor, $idSegmento, $totalVinsEnvio);
        $precioKmUnitario = floatval($tarifa['costo_por_km']) * floatval($tarifa['factor'] ?? 1.0);
        $precioSlc = floatval($tarifa['precio_slc'] ?? 0.0);
        $precioSll = floatval($tarifa['precio_sll'] ?? 0.0);

        $montoCalculado = 0.0;

        if ($servicioOriginal === 'FORANEO' && $servicioNuevo === 'FORANEO') {
            // Foráneo a Foráneo: costo unitario x KM extra x unidades afectadas
            if ($tipoTraslado === 2) { // Rodando (1 chofer)
                $montoCalculado = $precioKmUnitario * $kmAdicionales;
            } else {
                $montoCalculado = $precioKmUnitario * $kmAdicionales * $vinsSeleccionadosCount;
            }
        } elseif ($servicioOriginal === $servicioNuevo && in_array($servicioOriginal, ['SLC', 'SLL'], true)) {
            // Se mantiene dentro del mismo rango de tarifa plana local
            $montoCalculado = 0.00;
        } elseif ($servicioOriginal === 'SLC' && $servicioNuevo === 'SLL') {
            // Salto de Local Corto a Local Largo: diferencia de tarifa plana
            $montoCalculado = max(0.0, $precioSll - $precioSlc);
        } elseif (in_array($servicioOriginal, ['SLC', 'SLL'], true) && $servicioNuevo === 'FORANEO') {
            // Salto de Local a Foráneo: Costo foráneo del nuevo trayecto menos la tarifa local ya contratada
            $costoForaneoNuevo = ($tipoTraslado === 2) 
                ? ($precioKmUnitario * $kmNuevo)
                : ($precioKmUnitario * $kmNuevo * $totalVinsEnvio);

            $tarifaPlanaPrevia = ($servicioOriginal === 'SLC') ? $precioSlc : $precioSll;
            $montoCalculado = max(0.0, $costoForaneoNuevo - $tarifaPlanaPrevia);
        } else {
            // Fallback genérico por km
            $montoCalculado = $precioKmUnitario * $kmAdicionales * max(1, $vinsSeleccionadosCount);
        }

        return [
            'monto_calculado'        => round($montoCalculado, 2),
            'tarifa_aplicada'        => round($precioKmUnitario, 2),
            'tipo_servicio_original' => $servicioOriginal,
            'tipo_servicio_nuevo'    => $servicioNuevo,
            'km_aprobados'           => $kmAprobados,
            'km_adicionales'         => $kmAdicionales,
            'km_nuevo'               => $kmNuevo,
        ];
    }

    private function clasificarServicio(float $km): string
    {
        if ($km <= 40.0) {
            return 'SLC';
        }
        if ($km <= 80.0) {
            return 'SLL';
        }
        return 'FORANEO';
    }
}
