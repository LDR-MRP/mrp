<?php

declare(strict_types=1);

class Lgs_tarifaCalculator
{
    public const KM_MAX_SLC = 40.0;
    public const KM_MAX_SLL = 80.0;
    public const SERVICIOS_VALIDOS = ['AUTOMATICO', 'FORANEO', 'SLC', 'SLL'];

    /**
     * Normaliza el modo de servicio solicitado contra la lista permitida.
     */
    public static function normalizarModoServicio(?string $tipoServicio): string
    {
        $modo = strtoupper(trim((string)$tipoServicio));
        return in_array($modo, self::SERVICIOS_VALIDOS, true) ? $modo : 'AUTOMATICO';
    }

    /**
     * Resuelve la clasificación efectiva (SLC, SLL o FORANEO) según la distancia y el modo del envío.
     */
    public static function resolverTipoServicio(float $km, string $tipoServicioEnvio = 'AUTOMATICO'): string
    {
        $modo = self::normalizarModoServicio($tipoServicioEnvio);
        if ($modo !== 'AUTOMATICO') {
            return $modo;
        }

        if ($km > 0 && $km <= self::KM_MAX_SLC) {
            return 'SLC';
        }
        if ($km > self::KM_MAX_SLC && $km <= self::KM_MAX_SLL) {
            return 'SLL';
        }
        return 'FORANEO';
    }

    /**
     * Detecta la clasificación según distancia, devolviendo PENDIENTE si aún no hay KM (> 0).
     */
    public static function detectarPorKm(float $km): string
    {
        if ($km <= 0) {
            return 'PENDIENTE';
        }
        if ($km <= self::KM_MAX_SLC) {
            return 'SLC';
        }
        if ($km <= self::KM_MAX_SLL) {
            return 'SLL';
        }
        return 'FORANEO';
    }

    /**
     * Devuelve la etiqueta legible de una clasificación de servicio.
     */
    public static function etiquetaServicio(string $codigo): string
    {
        $map = [
            'SLC'       => 'Local Corto (SLC 0-40 km)',
            'SLL'       => 'Local Largo (SLL 41-80 km)',
            'FORANEO'   => 'Foráneo (>80 km)',
            'MIXTO'     => 'Mixto / Campechaneado',
            'PENDIENTE' => 'Pendiente de KM',
        ];
        $key = strtoupper(trim($codigo));
        return $map[$key] ?? $map['FORANEO'];
    }

    /**
     * Resume las clasificaciones detectadas en las paradas/tramos de una ruta.
     */
    public static function resumirServiciosRuta(array $serviciosPorTramo, string $modoSolicitado = 'AUTOMATICO'): array
    {
        $modo = self::normalizarModoServicio($modoSolicitado);
        $conteo = [
            'SLC'     => 0,
            'SLL'     => 0,
            'FORANEO' => 0,
        ];

        foreach ($serviciosPorTramo as $serv) {
            $s = strtoupper(trim((string)$serv));
            if (isset($conteo[$s])) {
                $conteo[$s]++;
            }
        }

        $serviciosUnicos = [];
        foreach ($conteo as $k => $cantidad) {
            if ($cantidad > 0) {
                $serviciosUnicos[] = $k;
            }
        }

        $esMixto = count($serviciosUnicos) > 1;
        $tieneLocales = ($conteo['SLC'] > 0 || $conteo['SLL'] > 0);

        if ($esMixto) {
            $clasificacionGlobal = 'MIXTO';
        } elseif (count($serviciosUnicos) === 1) {
            $clasificacionGlobal = $serviciosUnicos[0];
        } else {
            $clasificacionGlobal = 'PENDIENTE';
        }

        return [
            'modo_solicitado'      => $modo,
            'clasificacion_global' => $clasificacionGlobal,
            'etiqueta_global'      => self::etiquetaServicio($clasificacionGlobal),
            'es_mixto'             => $esMixto,
            'tiene_locales'        => $tieneLocales,
            'servicios_unicos'     => $serviciosUnicos,
            'conteo'               => $conteo,
        ];
    }
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

            $sqlNearest = "SELECT id_tarifa, costo_por_km, precio_plano, precio_slc, precio_sll, factor 
                           FROM lgs_tarifas_proveedores 
                           WHERE id_proveedor = ? AND id_tipo_traslado = ? AND id_segmento = ? 
                             AND activo != 0 
                           ORDER BY ABS(num_vins_min - ?) ASC, num_vins_min ASC 
                           LIMIT 1";
            $stmtNearest = $db->prepare($sqlNearest);
            $stmtNearest->execute([$idProveedor, $idTipoTraslado, $idSegmento, $volumenVins]);
            $tarifaNearest = $stmtNearest->fetch(PDO::FETCH_ASSOC);
            if ($tarifaNearest) {
                return $tarifaNearest;
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

        $sqlBaseNearest = "SELECT id_tarifa, costo_por_km, precio_plano, precio_slc, precio_sll, factor 
                           FROM lgs_tarifas_proveedores 
                           WHERE id_proveedor = 0 AND id_tipo_traslado = ? AND id_segmento = ? 
                             AND activo != 0 
                           ORDER BY ABS(num_vins_min - ?) ASC, num_vins_min ASC 
                           LIMIT 1";
        $stmtBaseNearest = $db->prepare($sqlBaseNearest);
        $stmtBaseNearest->execute([$idTipoTraslado, $idSegmento, $volumenVins]);
        $tarifaBaseNearest = $stmtBaseNearest->fetch(PDO::FETCH_ASSOC);
        if ($tarifaBaseNearest) {
            return $tarifaBaseNearest;
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
            $factor = round($precioBase / $costoPorKm, 5);
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
            // Salto de Local Corto a Local Largo: diferencia de tarifa local por unidad
            $unidadesLocal = ($tipoTraslado === 2) ? 1 : max(1, $vinsSeleccionadosCount);
            $montoCalculado = max(0.0, ($precioSll - $precioSlc) * $unidadesLocal);
        } elseif (in_array($servicioOriginal, ['SLC', 'SLL'], true) && $servicioNuevo === 'FORANEO') {
            // Salto de Local a Foráneo: Costo foráneo del nuevo trayecto menos la tarifa local ya contratada
            $costoForaneoNuevo = ($tipoTraslado === 2) 
                ? ($precioKmUnitario * $kmNuevo)
                : ($precioKmUnitario * $kmNuevo * $totalVinsEnvio);

            $unidadesLocalPrev = ($tipoTraslado === 2) ? 1 : $totalVinsEnvio;
            $tarifaPlanaPrevia = (($servicioOriginal === 'SLC') ? $precioSlc : $precioSll) * $unidadesLocalPrev;
            $montoCalculado = max(0.0, $costoForaneoNuevo - $tarifaPlanaPrevia);
        } else {
            // Fallback genérico por km
            $montoCalculado = $precioKmUnitario * $kmAdicionales * max(1, $vinsSeleccionadosCount);
        }

        return [
            'monto_calculado'        => round($montoCalculado, 2),
            'tarifa_aplicada'        => round($precioKmUnitario, 5),
            'tipo_servicio_original' => $servicioOriginal,
            'tipo_servicio_nuevo'    => $servicioNuevo,
            'km_aprobados'           => $kmAprobados,
            'km_adicionales'         => $kmAdicionales,
            'km_nuevo'               => $kmNuevo,
        ];
    }

    private function clasificarServicio(float $km): string
    {
        if ($km <= self::KM_MAX_SLC) {
            return 'SLC';
        }
        if ($km <= self::KM_MAX_SLL) {
            return 'SLL';
        }
        return 'FORANEO';
    }
}
