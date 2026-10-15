<?php

class Lgs_enviosService {

    private Lgs_enviosModel $model;

    public function __construct() {
        $this->model = new Lgs_enviosModel();
    }

    /**
     * Obtiene todos los envíos para la vista principal
     */
    public function getAllEnvios(): array {
        return $this->model->getEnviosDataTable();
    }

    public function getCatalogosSelect(): array {
        return $this->model->getSelectCatalogos();
    }

    /**
     * Crea la cabecera de un envío nuevo (Transaction con bloqueo)
     */
    public function createEnvio(array $data, int $userId): int {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            // 1. Bloquea la tabla y genera el folio (EN-000001)
            $folio = $this->model->generarFolioTransaccional($db);
            $data['folio'] = $folio;
            $data['created_by'] = $userId;
            
            // 2. Inserta la cabecera
            $idEnvio = $this->model->insertEnvio($db, $data);
            
            $db->commit();
            return $idEnvio;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Asigna un VIN a un envío con soporte para múltiples paradas
     */
    public function asignarVin(int $idEnvio, int $idUnidad, array $params, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            $params['id_envio'] = $idEnvio;
            $params['id_unidad'] = $idUnidad;
            $params['created_by'] = $userId; // Opcional, si aplicara

            // 1. Insertar el VIN en la pivot
            $this->model->insertVin($db, $params);
            
            // 2. Aquí iría la llamada a recalcularCostoTotal($idEnvio)
            // $this->recalcularCostoTotal($idEnvio, $db);
            
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function recalcularCostoTotal(int $idEnvio, PDO $db = null): float {
        if ($db === null) {
            $db = $this->model->getConexion();
        }

        // 1. Cabecera del envío
        $stmtEnvio = $db->prepare("SELECT id_tipo_traslado, id_proveedor, id_origen, id_destino, km_total, id_estado, costo_total, is_lowboy, tipo_servicio, modalidad_calculo FROM lgs_envios WHERE id_envio = :id");
        $stmtEnvio->execute(['id' => $idEnvio]);
        $envio = $stmtEnvio->fetch(PDO::FETCH_ASSOC);

        if (!$envio) return 0.0;

        // Regla histórica: no recalcular ni alterar envíos cerrados/entregados (id_estado = 7)
        if (isset($envio['id_estado']) && (int)$envio['id_estado'] === 7) {
            $this->asegurarCostosVins($idEnvio, $db);
            return (float)($envio['costo_total'] ?? 0.0);
        }

        $idTipoTraslado = (int)$envio['id_tipo_traslado'];
        $idProveedor    = (int)$envio['id_proveedor'];
        $isLowboy       = (int)($envio['is_lowboy'] ?? 0);
        $modalidad      = strtoupper($envio['modalidad_calculo'] ?? 'ESCALONADO');

        // 2. Obtener los nodos ordenados
        $stmtNodos = $db->prepare("SELECT id_nodo, orden, tipo_nodo, id_ubicacion, id_origen_referencia, destino_nombre_libre, km_tramo_anterior FROM lgs_envios_nodos WHERE id_envio = ? ORDER BY orden ASC");
        $stmtNodos->execute([$idEnvio]);
        $nodos = $stmtNodos->fetchAll(PDO::FETCH_ASSOC);

        if (empty($nodos) || count($nodos) < 2) {
            $db->prepare("DELETE FROM lgs_envios_tramos_costos WHERE id_envio = ?")->execute([$idEnvio]);
            $db->prepare("UPDATE lgs_envios SET costo_total = 0.00 WHERE id_envio = ?")->execute([$idEnvio]);
            return 0.0;
        }

        // Mapear nombre de ubicaciones
        $ubicaciones = [];
        $stmtUbi = $db->query("SELECT id_ubicacion, nombre FROM lgs_cat_ubicaciones");
        while ($row = $stmtUbi->fetch(PDO::FETCH_ASSOC)) {
            $ubicaciones[$row['id_ubicacion']] = $row['nombre'];
        }

        // 3. Obtener VINs
        $stmtVins = $db->prepare("SELECT id, id_unidad, id_madrina, id_plataforma, id_chofer, id_nodo_subida, id_nodo_bajada FROM lgs_envios_vins WHERE id_envio = ?");
        $stmtVins->execute([$idEnvio]);
        $vins = $stmtVins->fetchAll(PDO::FETCH_ASSOC);

        if (empty($vins)) {
            $db->prepare("DELETE FROM lgs_envios_tramos_costos WHERE id_envio = ?")->execute([$idEnvio]);
            $db->prepare("UPDATE lgs_envios SET costo_total = 0.00 WHERE id_envio = ?")->execute([$idEnvio]);
            return 0.0;
        }

        // Candado de negocio: en Madrina (1) no se puede enviar solo 1 unidad
        if ($idTipoTraslado === 1 && count($vins) === 1) {
            throw new Exception("No es posible enviar una sola unidad en Madrina. Para traslados individuales debe utilizarse Plataforma o Chofer (Rodando).");
        }

        foreach ($vins as &$vin) {
            $vin['id_segmento'] = $this->resolveSegmentoForUnit($db, (int)$vin['id_unidad']);
        }
        unset($vin);

        // Agrupar por Madrina (si es madrina o plataforma) o Chofer (si es rodando)
        $unidadesAsignadas = []; // id_madrina_o_chofer => array de vins
        foreach ($vins as $vin) {
            if ($idTipoTraslado === 2) {
                // En rodando (chofer), cada unidad viaja individualmente en sus propias ruedas.
                $key = !empty($vin['id_chofer']) ? ((int)$vin['id_chofer']) : ('vin_' . $vin['id']);
            } elseif ($idTipoTraslado === 3) {
                $key = !empty($vin['id_plataforma']) ? ((int)$vin['id_plataforma']) : ((int)($vin['id_madrina'] ?? 0));
            } else {
                $key = (int)($vin['id_madrina'] ?? 0);
            }
            $unidadesAsignadas[$key][] = $vin;
        }

        // Limpiar tabla de tramos_costos e inicializar costo_unidad en 0
        $db->prepare("DELETE FROM lgs_envios_tramos_costos WHERE id_envio = ?")->execute([$idEnvio]);
        $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = 0 WHERE id_envio = ?")->execute([$idEnvio]);

        $costoTotalEnvio = 0.0;
        $tarifasBasePond = [1 => 27.0, 2 => 30.0, 3 => 40.0, 4 => 50.0, 5 => 60.0];

        if ($modalidad === 'DIRECTO') {
            // ══════════════════════════════════════════════════════════════════════
            // MODALIDAD: ENVÍO DIRECTO CON ESCALAS (SUB-ENVÍOS POR DESTINO)
            // ══════════════════════════════════════════════════════════════════════
            foreach ($unidadesAsignadas as $idAgrupador => $vinsGrupo) {
                // Agrupar VINs de este transporte por su nodo de destino (bajada)
                $gruposPorDestino = [];
                foreach ($vinsGrupo as $vin) {
                    $idNodoBajada = !empty($vin['id_nodo_bajada']) ? (int)$vin['id_nodo_bajada'] : null;
                    if ($idNodoBajada === null) {
                        if (!empty($vin['id_parada'])) {
                            $idNodoBajada = (int)$vin['id_parada'];
                        } else {
                            $idNodoBajada = (int)$nodos[count($nodos) - 1]['id_nodo'];
                        }
                    }
                    $gruposPorDestino[$idNodoBajada][] = $vin;
                }

                foreach ($gruposPorDestino as $idNodoDestino => $vinsDestino) {
                    $numVinsDest = count($vinsDestino);
                    if ($numVinsDest === 0) continue;

                    // Localizar el nodo de destino en el itinerario
                    $nodoDestino = null;
                    foreach ($nodos as $n) {
                        if ((int)$n['id_nodo'] === $idNodoDestino) {
                            $nodoDestino = $n;
                            break;
                        }
                    }
                    if (!$nodoDestino) {
                        $nodoDestino = $nodos[count($nodos) - 1];
                    }

                    // Determinar el origen de referencia para este destino
                    $nodoOrigen = null;
                    if (!empty($nodoDestino['id_origen_referencia'])) {
                        foreach ($nodos as $n) {
                            if ((int)$n['id_nodo'] === (int)$nodoDestino['id_origen_referencia']
                                || (int)$n['orden'] === (int)$nodoDestino['id_origen_referencia']
                                || (int)$n['id_ubicacion'] === (int)$nodoDestino['id_origen_referencia']) {
                                $nodoOrigen = $n;
                                break;
                            }
                        }
                    }

                    if (!$nodoOrigen) {
                        // Si las unidades comparten un nodo de subida explícito
                        $subidasComunes = array_unique(array_filter(array_column($vinsDestino, 'id_nodo_subida')));
                        if (count($subidasComunes) === 1) {
                            $idSub = (int)reset($subidasComunes);
                            foreach ($nodos as $n) {
                                if ((int)$n['id_nodo'] === $idSub) {
                                    $nodoOrigen = $n;
                                    break;
                                }
                            }
                        }
                    }

                    if (!$nodoOrigen) {
                        $nodoOrigen = $nodos[0]; // Origen de partida inicial
                    }

                    $idLocOrigen = (int)$nodoOrigen['id_ubicacion'];
                    $idLocDestino = (int)$nodoDestino['id_ubicacion'];

                    // Resolver distancia directa Origen -> Destino
                    $kmTramo = 0.0;
                    if ($idLocOrigen > 0 && $idLocDestino > 0) {
                        $distMemoria = $this->model->getDistanciaEntre($idLocOrigen, $idLocDestino, $db);
                        if ($distMemoria !== null && $distMemoria > 0) {
                            $kmTramo = $distMemoria;
                        }
                    }

                    if ($kmTramo <= 0) {
                        // Acumular tramos intermedios de la ruta como fallback si no hay memoria directa
                        $ordOrig = (int)$nodoOrigen['orden'];
                        $ordDest = (int)$nodoDestino['orden'];
                        $distAcum = 0.0;
                        foreach ($nodos as $n) {
                            $ordN = (int)$n['orden'];
                            if ($ordN > $ordOrig && $ordN <= $ordDest) {
                                $distAcum += (float)$n['km_tramo_anterior'];
                            }
                        }
                        if ($distAcum > 0) {
                            $kmTramo = $distAcum;
                            if ($idLocOrigen > 0 && $idLocDestino > 0) {
                                $this->model->saveDistancia($idLocOrigen, $idLocDestino, $kmTramo, $db);
                            }
                        } else {
                            $kmTramo = (float)$nodoDestino['km_tramo_anterior'];
                        }
                    }

                    $vinsLigeros = 0;
                    $vinsMedianos = 0;
                    $vinsPesados = 0;
                    $vinsBuses = 0;
                    $vinsEspeciales = 0;

                    foreach ($vinsDestino as $v) {
                        $seg = (int)($v['id_segmento'] ?? 1);
                        if ($seg == 1) $vinsLigeros++;
                        elseif ($seg == 2) $vinsMedianos++;
                        elseif ($seg == 3) $vinsPesados++;
                        elseif ($seg == 4) $vinsBuses++;
                        elseif ($seg == 5) $vinsEspeciales++;
                    }

                    // Regla de negocio: En Madrina y Plataforma el segmento no pondera tarifa base
                    $segmentoDominante = 1;
                    if ($idTipoTraslado === 3 && $isLowboy) {
                        $segmentoDominante = 5;
                    } elseif ($idTipoTraslado === 2) {
                        if ($vinsEspeciales > 0) $segmentoDominante = 5;
                        elseif ($vinsBuses > 0) $segmentoDominante = 4;
                        elseif ($vinsPesados > 0) $segmentoDominante = 3;
                        elseif ($vinsMedianos > 0) $segmentoDominante = 2;
                    }

                    // El factoraje en Envío Directo se calcula según el total de unidades a bordo en la Madrina/Plataforma
                    $volumenParaTarifa = ($idTipoTraslado === 1 || $idTipoTraslado === 3) ? count($vins) : $numVinsDest;
                    if ($idTipoTraslado === 1 && $volumenParaTarifa > 9) {
                        $volumenParaTarifa = 9;
                    } elseif ($idTipoTraslado === 3 && $isLowboy) {
                        $volumenParaTarifa = 4;
                    } elseif ($idTipoTraslado === 3 && $volumenParaTarifa > 3) {
                        $volumenParaTarifa = 3;
                    }

                    $tarifa = $this->getTarifaAplicable($db, $idTipoTraslado, $idProveedor, $segmentoDominante, $volumenParaTarifa);

                    $distanciaUsar = $kmTramo;
                    $costoPorKm = (float)($tarifa['costo_por_km'] ?? 0);
                    $factor = ((float)($tarifa['factor'] ?? 0) > 0) ? (float)$tarifa['factor'] : 1.0;
                    $costoPlano = (float)($tarifa['precio_plano'] ?? 0);

                    if ($idTipoTraslado === 2) {
                        $factor = 1.0;
                    }

                    $tipoServicioEnvio = strtoupper($envio['tipo_servicio'] ?? 'AUTOMATICO');
                    $servicioSubEnvio = 'FORANEO';

                    if ($tipoServicioEnvio === 'SLC') {
                        $servicioSubEnvio = 'SLC';
                    } elseif ($tipoServicioEnvio === 'SLL') {
                        $servicioSubEnvio = 'SLL';
                    } elseif ($tipoServicioEnvio === 'FORANEO') {
                        $servicioSubEnvio = 'FORANEO';
                    } else {
                        // AUTOMATICO o MIXTO: Clasifica según la distancia real hacia este destino
                        if ($kmTramo > 0 && $kmTramo <= 40) {
                            $servicioSubEnvio = 'SLC';
                        } elseif ($kmTramo > 40 && $kmTramo <= 80) {
                            $servicioSubEnvio = 'SLL';
                        } else {
                            $servicioSubEnvio = 'FORANEO';
                        }
                    }

                    if ($servicioSubEnvio === 'SLC') {
                        if ($idTipoTraslado === 1 && count($vins) < 3) {
                            throw new Exception("El servicio Local Corto (SLC) en Madrina requiere al menos 3 unidades a bordo.");
                        }
                        $costoPlano = (float)($tarifa['precio_slc'] ?? 0);
                        $distanciaUsar = 0;
                    } elseif ($servicioSubEnvio === 'SLL') {
                        if ($idTipoTraslado === 1 && count($vins) < 3) {
                            throw new Exception("El servicio Local Largo (SLL) en Madrina requiere al menos 3 unidades a bordo.");
                        }
                        $costoPlano = (float)($tarifa['precio_sll'] ?? 0);
                        $distanciaUsar = 0;
                    }

                    $costoSubEnvio = ($distanciaUsar * $costoPorKm * $factor * $numVinsDest) + $costoPlano;
                    if ($kmTramo == 0 && $costoPlano == 0) {
                        $costoSubEnvio = 0;
                    }

                    $stmtInsertCosto = $db->prepare("
                        INSERT INTO lgs_envios_tramos_costos 
                        (id_envio, id_madrina, id_chofer, id_nodo_origen, id_nodo_destino, km_tramo, vins_ligeros, vins_medianos, vins_pesados, vins_especiales, factor_aplicado, costo_estimado, tarifa_usada_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsertCosto->execute([
                        $idEnvio,
                        ($idTipoTraslado === 1 || $idTipoTraslado === 3) ? $idAgrupador : null,
                        ($idTipoTraslado === 2) ? $idAgrupador : null,
                        $nodoOrigen['id_nodo'],
                        $nodoDestino['id_nodo'],
                        $distanciaUsar,
                        $vinsLigeros,
                        $vinsMedianos,
                        $vinsPesados,
                        ($vinsBuses + $vinsEspeciales),
                        $factor,
                        $costoSubEnvio,
                        $tarifa['id'] ?? null
                    ]);

                    $costoTotalEnvio += $costoSubEnvio;

                    // En Madrina y Plataforma el costo del sub-envío se divide equitativamente entre sus unidades
                    $costoUnitarioBase = round($costoSubEnvio / $numVinsDest, 2);
                    $stmtUpdateVin = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = ?, tipo_servicio = ? WHERE id = ?");
                    foreach ($vinsDestino as $v) {
                        $stmtUpdateVin->execute([$costoUnitarioBase, $servicioSubEnvio, $v['id']]);
                    }
                }
            }
        } else {
            // ══════════════════════════════════════════════════════════════════════
            // MODALIDAD: ENVÍO ESCALONADO (TRADICIONAL POR TRAMOS CONSECUTIVOS)
            // ══════════════════════════════════════════════════════════════════════
            foreach ($unidadesAsignadas as $idAgrupador => $vinsGrupo) {
                
                for ($i = 1; $i < count($nodos); $i++) {
                    $nodoOrigen = $nodos[$i-1];
                    $nodoDestino = $nodos[$i];

                    $idLocOrigen = (int)$nodoOrigen['id_ubicacion'];
                    $idLocDestino = (int)$nodoDestino['id_ubicacion'];
                    $kmTramo = (float)$nodoDestino['km_tramo_anterior'];

                    if ($idLocOrigen > 0 && $idLocDestino > 0) {
                        $distanciaMemoria = $this->model->getDistanciaEntre($idLocOrigen, $idLocDestino, $db);
                        if ($distanciaMemoria !== null && $distanciaMemoria > 0) {
                            $kmTramo = $distanciaMemoria;
                        } elseif ($kmTramo > 0) {
                            $this->model->saveDistancia($idLocOrigen, $idLocDestino, $kmTramo, $db);
                        }
                    }

                    if ($kmTramo > 0 && (float)$nodoDestino['km_tramo_anterior'] != $kmTramo) {
                        $db->prepare("UPDATE lgs_envios_nodos SET km_tramo_anterior = ? WHERE id_nodo = ?")
                           ->execute([$kmTramo, $nodoDestino['id_nodo']]);
                    }

                    $vinsEnTramo = [];
                    $vinsLigeros = 0;
                    $vinsMedianos = 0;
                    $vinsPesados = 0;
                    $vinsBuses = 0;
                    $vinsEspeciales = 0;
                    
                    foreach ($vinsGrupo as $vin) {
                        $ordenSubida = null;
                        $ordenBajada = null;

                        foreach ($nodos as $n) {
                            if (!empty($vin['id_nodo_subida']) && $n['id_nodo'] == $vin['id_nodo_subida']) {
                                $ordenSubida = (int)$n['orden'];
                            }
                            if (!empty($vin['id_nodo_bajada']) && $n['id_nodo'] == $vin['id_nodo_bajada']) {
                                $ordenBajada = (int)$n['orden'];
                            }
                        }

                        if ($ordenSubida === null) {
                            $ordenSubida = 0;
                        }
                        if ($ordenBajada === null) {
                            if (!empty($vin['id_parada'])) {
                                foreach ($nodos as $n) {
                                    if ($n['id_nodo'] == $vin['id_parada']) {
                                        $ordenBajada = (int)$n['orden'];
                                        break;
                                    }
                                }
                            }
                            if ($ordenBajada === null) {
                                $ordenBajada = count($nodos) - 1;
                            }
                        }

                        if ($ordenSubida <= (int)$nodoOrigen['orden'] && $ordenBajada >= (int)$nodoDestino['orden']) {
                            $vinsEnTramo[] = $vin;
                            $seg = $vin['id_segmento'];
                            if ($seg == 1) $vinsLigeros++;
                            elseif ($seg == 2) $vinsMedianos++;
                            elseif ($seg == 3) $vinsPesados++;
                            elseif ($seg == 4) $vinsBuses++;
                            elseif ($seg == 5) $vinsEspeciales++;
                        }
                    }

                    $volumenTotal = count($vinsEnTramo);
                    if ($volumenTotal === 0) continue;

                    // En Madrina y Plataforma el segmento no altera la tarifa
                    if ($idTipoTraslado === 1 || $idTipoTraslado === 3) {
                        $segmentoDominante = ($idTipoTraslado === 3 && $isLowboy) ? 5 : 1;
                    } else {
                        $segmentoDominante = 1;
                        if ($vinsEspeciales > 0) $segmentoDominante = 5;
                        elseif ($vinsBuses > 0) $segmentoDominante = 4;
                        elseif ($vinsPesados > 0) $segmentoDominante = 3;
                        elseif ($vinsMedianos > 0) $segmentoDominante = 2;
                    }

                    $volumenParaTarifa = $volumenTotal;
                    if ($idTipoTraslado === 1 && $volumenTotal > 9) {
                        $volumenParaTarifa = 9;
                    } elseif ($idTipoTraslado === 3 && $isLowboy) {
                        $volumenParaTarifa = 4;
                    } elseif ($idTipoTraslado === 3 && $volumenTotal > 3) {
                        $volumenParaTarifa = 3;
                    }
                    $tarifa = $this->getTarifaAplicable($db, $idTipoTraslado, $idProveedor, $segmentoDominante, $volumenParaTarifa);

                    $distanciaUsar = $kmTramo;
                    $costoPorKm = (float)($tarifa['costo_por_km'] ?? 0);
                    $factor = ((float)($tarifa['factor'] ?? 0) > 0) ? (float)$tarifa['factor'] : 1.0;
                    $costoPlano = (float)($tarifa['precio_plano'] ?? 0);

                    if ($idTipoTraslado === 2) {
                        $factor = 1.0;
                    }

                    $tipoServicioEnvio = strtoupper($envio['tipo_servicio'] ?? 'AUTOMATICO');
                    $servicioTramo = 'FORANEO';

                    if ($tipoServicioEnvio === 'SLC') {
                        $servicioTramo = 'SLC';
                    } elseif ($tipoServicioEnvio === 'SLL') {
                        $servicioTramo = 'SLL';
                    } elseif ($tipoServicioEnvio === 'FORANEO') {
                        $servicioTramo = 'FORANEO';
                    } else {
                        // AUTOMATICO o MIXTO: Clasifica según la distancia de este tramo
                        if ($kmTramo > 0 && $kmTramo <= 40) {
                            $servicioTramo = 'SLC';
                        } elseif ($kmTramo > 40 && $kmTramo <= 80) {
                            $servicioTramo = 'SLL';
                        } else {
                            $servicioTramo = 'FORANEO';
                        }
                    }

                    if ($servicioTramo === 'SLC') {
                        if ($idTipoTraslado === 1 && count($vins) < 3) {
                            throw new Exception("El servicio Local Corto (SLC) en Madrina solo está permitido a partir de 3 unidades a bordo.");
                        }
                        $costoPlano = (float)($tarifa['precio_slc'] ?? 0);
                        $distanciaUsar = 0;
                    } elseif ($servicioTramo === 'SLL') {
                        if ($idTipoTraslado === 1 && count($vins) < 3) {
                            throw new Exception("El servicio Local Largo (SLL) en Madrina solo está permitido a partir de 3 unidades a bordo.");
                        }
                        $costoPlano = (float)($tarifa['precio_sll'] ?? 0);
                        $distanciaUsar = 0;
                    }

                    $costoTramo = ($distanciaUsar * $costoPorKm * $factor * $volumenTotal) + $costoPlano;
                    if ($kmTramo == 0 && $costoPlano == 0) {
                        $costoTramo = 0;
                    }

                    $stmtInsertCosto = $db->prepare("
                        INSERT INTO lgs_envios_tramos_costos 
                        (id_envio, id_madrina, id_chofer, id_nodo_origen, id_nodo_destino, km_tramo, vins_ligeros, vins_medianos, vins_pesados, vins_especiales, factor_aplicado, costo_estimado, tarifa_usada_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmtInsertCosto->execute([
                        $idEnvio,
                        ($idTipoTraslado === 1 || $idTipoTraslado === 3) ? $idAgrupador : null,
                        ($idTipoTraslado === 2) ? $idAgrupador : null,
                        $nodoOrigen['id_nodo'],
                        $nodoDestino['id_nodo'],
                        $distanciaUsar,
                        $vinsLigeros,
                        $vinsMedianos,
                        $vinsPesados,
                        ($vinsBuses + $vinsEspeciales),
                        $factor,
                        $costoTramo,
                        $tarifa['id'] ?? null
                    ]);

                    $costoTotalEnvio += $costoTramo;

                    if ($idTipoTraslado === 1 || $idTipoTraslado === 3) {
                        // En Madrina y Plataforma el tramo se reparte equitativamente entre las unidades
                        $costoUnitarioTramo = $costoTramo / $volumenTotal;
                        $stmtUpdateVin = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = COALESCE(costo_unidad, 0) + ? WHERE id = ?");
                        foreach ($vinsEnTramo as $v) {
                            $stmtUpdateVin->execute([round($costoUnitarioTramo, 2), $v['id']]);
                        }
                    } else {
                        // En Chofer rodando se pondera por segmento
                        $sumaPonderaciones = 0.0;
                        foreach ($vinsEnTramo as $v) {
                            $segId = (int)($v['id_segmento'] ?? 1);
                            $sumaPonderaciones += ($tarifasBasePond[$segId] ?? 27.0);
                        }
                        if ($sumaPonderaciones <= 0) $sumaPonderaciones = (float)$volumenTotal;

                        $stmtUpdateVin = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = COALESCE(costo_unidad, 0) + ? WHERE id = ?");
                        foreach ($vinsEnTramo as $v) {
                            $segId = (int)($v['id_segmento'] ?? 1);
                            $peso = $tarifasBasePond[$segId] ?? 27.0;
                            $costoUnitarioTramo = $costoTramo * ($peso / $sumaPonderaciones);
                            $stmtUpdateVin->execute([round($costoUnitarioTramo, 2), $v['id']]);
                        }
                    }
                }
            }
        }

        // Ajuste de centavos por redondeo al último VIN
        $stmtSum = $db->prepare("SELECT SUM(costo_unidad) as sum_costos FROM lgs_envios_vins WHERE id_envio = ?");
        $stmtSum->execute([$idEnvio]);
        $sumVins = (float)($stmtSum->fetch(PDO::FETCH_ASSOC)['sum_costos'] ?? 0);
        $dif = round($costoTotalEnvio - $sumVins, 2);
        if (abs($dif) > 0.001 && !empty($vins)) {
            $lastVinId = $vins[count($vins) - 1]['id'];
            $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = costo_unidad + ? WHERE id = ?")->execute([$dif, $lastVinId]);
        }

        // Clasificación de tipo_servicio por VIN para Escalonado o Automático
        $tipoServicioEnvio = strtoupper($envio['tipo_servicio'] ?? 'AUTOMATICO');
        $stmtUpVinServ = $db->prepare("UPDATE lgs_envios_vins SET tipo_servicio = ? WHERE id = ?");
        foreach ($vins as $v) {
            $servVin = 'FORANEO';
            if ($tipoServicioEnvio === 'SLC') {
                $servVin = 'SLC';
            } elseif ($tipoServicioEnvio === 'SLL') {
                $servVin = 'SLL';
            } elseif ($tipoServicioEnvio === 'FORANEO') {
                $servVin = 'FORANEO';
            } else {
                $ordSub = 0;
                $ordBaj = count($nodos) - 1;
                foreach ($nodos as $n) {
                    if (!empty($v['id_nodo_subida']) && $n['id_nodo'] == $v['id_nodo_subida']) $ordSub = (int)$n['orden'];
                    if (!empty($v['id_nodo_bajada']) && $n['id_nodo'] == $v['id_nodo_bajada']) $ordBaj = (int)$n['orden'];
                }
                $distVin = 0.0;
                foreach ($nodos as $n) {
                    $ordN = (int)$n['orden'];
                    if ($ordN > $ordSub && $ordN <= $ordBaj) {
                        $distVin += (float)$n['km_tramo_anterior'];
                    }
                }
                if ($distVin > 0 && $distVin <= 40) {
                    $servVin = 'SLC';
                } elseif ($distVin > 40 && $distVin <= 80) {
                    $servVin = 'SLL';
                } else {
                    $servVin = 'FORANEO';
                }
            }
            $stmtUpVinServ->execute([$servVin, $v['id']]);
        }

        // 3. Actualizar el Costo Total y KM Total en la Cabecera
        $stmtKmTotal = $db->prepare("SELECT SUM(km_tramo_anterior) AS total_km FROM lgs_envios_nodos WHERE id_envio = ?");
        $stmtKmTotal->execute([$idEnvio]);
        $rowKmTotal = $stmtKmTotal->fetch(PDO::FETCH_ASSOC);
        $kmTotalCalculado = (float)($rowKmTotal['total_km'] ?? 0);

        $tipoServicio = strtoupper($envio['tipo_servicio'] ?? 'AUTOMATICO');
        if ($tipoServicio === 'SLC' && $kmTotalCalculado > 40) {
            throw new Exception("El envío supera los 40km ({$kmTotalCalculado} km) y no puede ser clasificado como Local Corto (SLC). Utilice la modalidad Automático / Mixto o Foráneo.");
        }
        if ($tipoServicio === 'SLL' && ($kmTotalCalculado <= 40 || $kmTotalCalculado > 80)) {
            throw new Exception("El envío tiene {$kmTotalCalculado} km, lo cual no corresponde al rango de Local Largo SLL (41-80 KM). Utilice la modalidad Automático / Mixto o Foráneo.");
        }

        $stmtUpdate = $db->prepare("UPDATE lgs_envios SET costo_total = :costo, km_total = :km WHERE id_envio = :id");
        $stmtUpdate->execute([
            'costo' => round($costoTotalEnvio, 2),
            'km'    => round($kmTotalCalculado, 2),
            'id'    => $idEnvio
        ]);

        return $costoTotalEnvio;
    }

    /**
     * Asegura que los VINs de un envío tengan su costo unitario calculado y distribuido según su segmento
     */
    public function asegurarCostosVins(int $idEnvio, PDO $db = null): void {
        if ($db === null) {
            $db = $this->model->getConexion();
        }

        $stmtCheck = $db->prepare("SELECT COUNT(*) as t FROM lgs_envios_vins WHERE id_envio = ? AND (costo_unidad IS NULL OR costo_unidad = 0)");
        $stmtCheck->execute([$idEnvio]);
        $faltanCostos = (int)($stmtCheck->fetch(PDO::FETCH_ASSOC)['t'] ?? 0);

        if ($faltanCostos === 0) {
            return; // Ya están calculados
        }

        $stmtTramos = $db->prepare("SELECT * FROM lgs_envios_tramos_costos WHERE id_envio = ? ORDER BY id_tramo_costo ASC");
        $stmtTramos->execute([$idEnvio]);
        $tramos = $stmtTramos->fetchAll(PDO::FETCH_ASSOC);

        $stmtNodos = $db->prepare("SELECT id_nodo, orden FROM lgs_envios_nodos WHERE id_envio = ? ORDER BY orden ASC");
        $stmtNodos->execute([$idEnvio]);
        $nodos = $stmtNodos->fetchAll(PDO::FETCH_ASSOC);

        $stmtVins = $db->prepare("SELECT id, id_unidad, id_madrina, id_plataforma, id_chofer, id_nodo_subida, id_nodo_bajada FROM lgs_envios_vins WHERE id_envio = ?");
        $stmtVins->execute([$idEnvio]);
        $vins = $stmtVins->fetchAll(PDO::FETCH_ASSOC);
        if (empty($vins)) return;

        foreach ($vins as &$vin) {
            $vin['id_segmento'] = $this->resolveSegmentoForUnit($db, (int)$vin['id_unidad']);
        }
        unset($vin);

        $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = 0 WHERE id_envio = ?")->execute([$idEnvio]);

        $tarifasBasePond = [1 => 27.0, 2 => 30.0, 3 => 40.0, 4 => 50.0, 5 => 60.0];

        $stmtEnvioInfo = $db->prepare("SELECT id_tipo_traslado, modalidad_calculo, costo_total, tipo_servicio FROM lgs_envios WHERE id_envio = ?");
        $stmtEnvioInfo->execute([$idEnvio]);
        $envioRow = $stmtEnvioInfo->fetch(PDO::FETCH_ASSOC);
        $modalidadEnvio = strtoupper($envioRow['modalidad_calculo'] ?? 'ESCALONADO');
        $idTipoTrasladoEnvio = (int)($envioRow['id_tipo_traslado'] ?? 1);
        $tipoServicioEnvio = strtoupper($envioRow['tipo_servicio'] ?? 'AUTOMATICO');

        if (!empty($tramos) && !empty($nodos)) {
            if ($modalidadEnvio === 'DIRECTO') {
                // En DIRECTO cada tramo corresponde a un sub-envío hacia id_nodo_destino
                foreach ($tramos as $tr) {
                    $costoTramo = (float)$tr['costo_estimado'];
                    if ($costoTramo <= 0) continue;

                    $vinsEnTramo = [];
                    foreach ($vins as $vin) {
                        $bajId = !empty($vin['id_nodo_bajada']) ? (int)$vin['id_nodo_bajada'] : (int)$nodos[count($nodos) - 1]['id_nodo'];
                        if ($bajId == $tr['id_nodo_destino']) {
                            $vinsEnTramo[] = $vin;
                        }
                    }

                    $vol = count($vinsEnTramo);
                    if ($vol === 0) continue;

                    $kmSub = (float)$tr['km_tramo'];
                    $servVin = 'FORANEO';
                    if ($tipoServicioEnvio === 'SLC') $servVin = 'SLC';
                    elseif ($tipoServicioEnvio === 'SLL') $servVin = 'SLL';
                    elseif ($tipoServicioEnvio === 'FORANEO') $servVin = 'FORANEO';
                    else {
                        if ($kmSub > 0 && $kmSub <= 40) $servVin = 'SLC';
                        elseif ($kmSub > 40 && $kmSub <= 80) $servVin = 'SLL';
                        else $servVin = 'FORANEO';
                    }

                    $cUnit = round($costoTramo / $vol, 2);
                    $stmtUp = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = ?, tipo_servicio = ? WHERE id = ?");
                    foreach ($vinsEnTramo as $v) {
                        $stmtUp->execute([$cUnit, $servVin, $v['id']]);
                    }
                }
            } else {
                // En ESCALONADO
                foreach ($tramos as $tr) {
                    $costoTramo = (float)$tr['costo_estimado'];
                    if ($costoTramo <= 0) continue;

                    $ordOrigen = 0;
                    $ordDestino = 1;
                    foreach ($nodos as $n) {
                        if ($n['id_nodo'] == $tr['id_nodo_origen']) $ordOrigen = (int)$n['orden'];
                        if ($n['id_nodo'] == $tr['id_nodo_destino']) $ordDestino = (int)$n['orden'];
                    }

                    $vinsEnTramo = [];
                    foreach ($vins as $vin) {
                        $ordSub = 0;
                        $ordBaj = count($nodos) - 1;
                        foreach ($nodos as $n) {
                            if (!empty($vin['id_nodo_subida']) && $n['id_nodo'] == $vin['id_nodo_subida']) $ordSub = (int)$n['orden'];
                            if (!empty($vin['id_nodo_bajada']) && $n['id_nodo'] == $vin['id_nodo_bajada']) $ordBaj = (int)$n['orden'];
                        }
                        if ($ordSub <= $ordOrigen && $ordBaj >= $ordDestino) {
                            $vinsEnTramo[] = $vin;
                        }
                    }

                    $vol = count($vinsEnTramo);
                    if ($vol === 0) continue;

                    if ($idTipoTrasladoEnvio === 1 || $idTipoTrasladoEnvio === 3) {
                        $cUnit = round($costoTramo / $vol, 2);
                        $stmtUp = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = COALESCE(costo_unidad, 0) + ? WHERE id = ?");
                        foreach ($vinsEnTramo as $v) {
                            $stmtUp->execute([$cUnit, $v['id']]);
                        }
                    } else {
                        $sumaPond = 0.0;
                        foreach ($vinsEnTramo as $v) {
                            $segId = (int)($v['id_segmento'] ?? 1);
                            $sumaPond += ($tarifasBasePond[$segId] ?? 27.0);
                        }
                        if ($sumaPond <= 0) $sumaPond = (float)$vol;

                        $stmtUp = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = COALESCE(costo_unidad, 0) + ? WHERE id = ?");
                        foreach ($vinsEnTramo as $v) {
                            $segId = (int)($v['id_segmento'] ?? 1);
                            $peso = $tarifasBasePond[$segId] ?? 27.0;
                            $cUnit = $costoTramo * ($peso / $sumaPond);
                            $stmtUp->execute([round($cUnit, 2), $v['id']]);
                        }
                    }
                }

                // Clasificación de tipo_servicio por VIN para Escalonado en asegurarCostosVins
                $stmtUpServ = $db->prepare("UPDATE lgs_envios_vins SET tipo_servicio = ? WHERE id = ?");
                foreach ($vins as $vin) {
                    $ordSub = 0;
                    $ordBaj = count($nodos) - 1;
                    foreach ($nodos as $n) {
                        if (!empty($vin['id_nodo_subida']) && $n['id_nodo'] == $vin['id_nodo_subida']) $ordSub = (int)$n['orden'];
                        if (!empty($vin['id_nodo_bajada']) && $n['id_nodo'] == $vin['id_nodo_bajada']) $ordBaj = (int)$n['orden'];
                    }
                    $distVin = 0.0;
                    foreach ($nodos as $n) {
                        $ordN = (int)$n['orden'];
                        if ($ordN > $ordSub && $ordN <= $ordBaj) {
                            $distVin += (float)$n['km_tramo_anterior'];
                        }
                    }
                    $servVin = 'FORANEO';
                    if ($tipoServicioEnvio === 'SLC') $servVin = 'SLC';
                    elseif ($tipoServicioEnvio === 'SLL') $servVin = 'SLL';
                    elseif ($tipoServicioEnvio === 'FORANEO') $servVin = 'FORANEO';
                    else {
                        if ($distVin > 0 && $distVin <= 40) $servVin = 'SLC';
                        elseif ($distVin > 40 && $distVin <= 80) $servVin = 'SLL';
                        else $servVin = 'FORANEO';
                    }
                    $stmtUpServ->execute([$servVin, $vin['id']]);
                }
            }
        } else {
            // Prorrateo general del costo total si no hay tramos detallados
            $stmtCostoEnvio = $db->prepare("SELECT costo_total FROM lgs_envios WHERE id_envio = ?");
            $stmtCostoEnvio->execute([$idEnvio]);
            $costoTotal = (float)($stmtCostoEnvio->fetch(PDO::FETCH_ASSOC)['costo_total'] ?? 0);

            if ($costoTotal > 0) {
                $sumaPond = 0.0;
                foreach ($vins as $v) {
                    $segId = (int)($v['id_segmento'] ?? 1);
                    $sumaPond += ($tarifasBasePond[$segId] ?? 27.0);
                }
                if ($sumaPond <= 0) $sumaPond = (float)count($vins);

                $stmtUp = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = ? WHERE id = ?");
                foreach ($vins as $v) {
                    $segId = (int)($v['id_segmento'] ?? 1);
                    $peso = $tarifasBasePond[$segId] ?? 27.0;
                    $cUnit = $costoTotal * ($peso / $sumaPond);
                    $stmtUp->execute([round($cUnit, 2), $v['id']]);
                }
            }
        }
    }

    /**
     * Resuelve dinámicamente el segmento de una unidad (VIN)
     */
    public function resolveSegmentoForUnit(PDO $db, int $idUnidad): int {
        // 1. Intentar obtener el modelo del VIN desde lgs_unidades_envios
        $stmtMock = $db->prepare("SELECT vin, modelo FROM lgs_unidades_envios WHERE id_unidad = ? LIMIT 1");
        $stmtMock->execute([$idUnidad]);
        $mock = $stmtMock->fetch(PDO::FETCH_ASSOC);
        
        $vin = '';
        $modelo = '';
        if ($mock) {
            $vin = trim($mock['vin'] ?? '');
            $modelo = trim($mock['modelo'] ?? '');
        } else {
            // Intentar desde mrp_unidades_terminadas
            $stmtReal = $db->prepare("
                SELECT ut.clave AS vin, p.descripcion AS modelo 
                FROM mrp_unidades_terminadas ut
                LEFT JOIN mrp_planeacion pl ON ut.planeacionid = pl.idplaneacion
                LEFT JOIN mrp_productos p ON pl.productoid = p.idproducto
                WHERE ut.idunidad = ? 
                LIMIT 1
            ");
            $stmtReal->execute([$idUnidad]);
            $real = $stmtReal->fetch(PDO::FETCH_ASSOC);
            if ($real) {
                $vin = trim($real['vin'] ?? '');
                $modelo = trim($real['modelo'] ?? '');
            }
        }

        if (empty($vin) && empty($modelo)) {
            return 1; // Default LIGEROS
        }

        $modeloLower = strtolower($modelo);

        // 2. Buscar en cat_modelos_vin
        // A) Coincidencia exacta por nombre de modelo
        if (!empty($modelo)) {
            $stmtExact = $db->prepare("SELECT id_segmento FROM cat_modelos_vin WHERE LOWER(TRIM(modelo)) = ? AND id_segmento IS NOT NULL LIMIT 1");
            $stmtExact->execute([$modeloLower]);
            $resExact = $stmtExact->fetch(PDO::FETCH_ASSOC);
            if ($resExact && !empty($resExact['id_segmento'])) {
                return (int)$resExact['id_segmento'];
            }
        }

        // B) Coincidencia por VIN base (prefijo) o coincidencia bidireccional en modelo
        if (!empty($vin) || !empty($modelo)) {
            $stmtModel = $db->prepare("
                SELECT id_segmento 
                FROM cat_modelos_vin 
                WHERE (
                    (? != '' AND vin_base IS NOT NULL AND vin_base != '' AND ? LIKE CONCAT(vin_base, '%'))
                    OR (? != '' AND ? LIKE CONCAT('%', LOWER(modelo), '%'))
                    OR (? != '' AND LOWER(modelo) LIKE CONCAT('%', ?, '%'))
                )
                AND id_segmento IS NOT NULL
                ORDER BY LENGTH(modelo) DESC
                LIMIT 1
            ");
            $stmtModel->execute([$vin, $vin, $modeloLower, $modeloLower, $modeloLower, $modeloLower]);
            $res = $stmtModel->fetch(PDO::FETCH_ASSOC);
            if ($res && !empty($res['id_segmento'])) {
                return (int)$res['id_segmento'];
            }
        }

        // 3. Fallback inteligente por palabras clave del modelo (Normalizado)
        // 3.1 LOWBOY
        if (strpos($modeloLower, 'lowboy') !== false || strpos($modeloLower, 'sobredimensionado') !== false) {
            return 5;
        }

        // 3.2 BUSES
        if (strpos($modeloLower, 'auv') !== false || strpos($modeloLower, 'araña') !== false || strpos($modeloLower, 'arana') !== false 
            || strpos($modeloLower, 'bus') !== false || strpos($modeloLower, 'autob') !== false 
            || strpos($modeloLower, 'beccar') !== false || strpos($modeloLower, 'orion') !== false || strpos($modeloLower, 'urbi') !== false) {
            return 4;
        }

        // 3.3 PESADOS (HEAVY)
        if (strpos($modeloLower, 'est') !== false || strpos($modeloLower, 'galaxy') !== false || strpos($modeloLower, 'galaxus') !== false 
            || strpos($modeloLower, 'gtl') !== false || strpos($modeloLower, '2491') !== false || strpos($modeloLower, '3256') !== false
            || strpos($modeloLower, 's35') !== false || strpos($modeloLower, 's38') !== false || strpos($modeloLower, 's40') !== false 
            || strpos($modeloLower, 'isg') !== false || strpos($modeloLower, 'tracto') !== false || strpos($modeloLower, 'volteo') !== false
            || strpos($modeloLower, 'heavy') !== false || strpos($modeloLower, 'pesado') !== false) {
            return 3;
        }

        // 3.4 MEDIANOS
        if (strpos($modeloLower, 's8') !== false || strpos($modeloLower, 's12') !== false || strpos($modeloLower, 's13') !== false 
            || strpos($modeloLower, 's20') !== false || strpos($modeloLower, 'mediano') !== false || strpos($modeloLower, 'medium') !== false) {
            return 2;
        }

        // 3.5 LIGEROS (LIGHT)
        if (strpos($modeloLower, 's3') !== false || strpos($modeloLower, 's5') !== false || strpos($modeloLower, 's6') !== false 
            || strpos($modeloLower, 'tunland') !== false || strpos($modeloLower, 'wonder') !== false 
            || strpos($modeloLower, 'tm3') !== false || strpos($modeloLower, 'tm') !== false 
            || strpos($modeloLower, 'miler') !== false || strpos($modeloLower, 'miller') !== false 
            || strpos($modeloLower, 'hivan') !== false || strpos($modeloLower, 'view') !== false 
            || strpos($modeloLower, 'toano') !== false || strpos($modeloLower, 'van') !== false 
            || strpos($modeloLower, 'pickup') !== false || strpos($modeloLower, 'panel') !== false
            || strpos($modeloLower, 'ligero') !== false || strpos($modeloLower, 'light') !== false) {
            return 1;
        }

        return 1; // Default LIGEROS
    }

    /**
     * Helper: Obtiene la tarifa aplicable consultando lgs_tarifas_proveedores (nuevo esquema unificado)
     */
    private function getTarifaAplicable(PDO $db, int $idTipoTraslado, int $idProveedor, int $idSegmento, int $volumenVins): array {
        
        // 1. Intentar tarifa del proveedor específico
        if ($idProveedor > 0) {
            $sql = "SELECT id_tarifa as id, costo_por_km, precio_plano, precio_slc, precio_sll, factor 
                    FROM lgs_tarifas_proveedores 
                    WHERE id_proveedor = ? AND id_tipo_traslado = ? AND id_segmento = ? 
                      AND ? BETWEEN num_vins_min AND num_vins_max 
                      AND activo != 0 
                    LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$idProveedor, $idTipoTraslado, $idSegmento, $volumenVins]);
            $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($tarifa) return $tarifa;
        }

        // 2. Intentar tarifa base general (id_proveedor = 0)
        $sql = "SELECT id_tarifa as id, costo_por_km, precio_plano, precio_slc, precio_sll, factor 
                FROM lgs_tarifas_proveedores 
                WHERE id_proveedor = 0 AND id_tipo_traslado = ? AND id_segmento = ? 
                  AND ? BETWEEN num_vins_min AND num_vins_max 
                  AND activo != 0 
                LIMIT 1";
        $stmt = $db->prepare($sql);
        $stmt->execute([$idTipoTraslado, $idSegmento, $volumenVins]);
        $tarifa = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($tarifa) return $tarifa;

        // 3. Fallback en código por si la tabla está vacía
        $defaultSegmentos = [
            1 => 27.0000, // Ligeros
            2 => 30.0000, // Medianos
            3 => 40.0000, // Pesados
            4 => 50.0000, // Autobuses
            5 => 60.0000  // Lowboy
        ];
        $costoPorKm = $defaultSegmentos[$idSegmento] ?? 20.0000;
        
        // Factor progresivo por defecto si es madrina/plataforma
        $factor = 1.0;
        if ($idTipoTraslado === 1) { // Madrina
            $sacbePrecios = [1=>17, 2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13];
            $precioBaseMadrina = $sacbePrecios[$volumenVins] ?? 13;
            $factor = round($precioBaseMadrina / $costoPorKm, 4);
        } elseif ($idTipoTraslado === 3) { // Plataforma
            // Plataformas cobran un equivalente a mover un Lowboy (~$80/km total)
            // Factor = (80 / 18) / N = 4.4444 / N
            $factor = max(0.20, 4.4444 / $volumenVins);
        }

        return [
            'id' => null,
            'costo_por_km' => $costoPorKm,
            'precio_plano' => 0.00,
            'precio_slc' => 0.00,
            'precio_sll' => 0.00,
            'factor' => $factor
        ];
    }

    /**
     * Helper: Actualiza el costo individual calculado para el VIN
     */
    private function updateCostoVin(PDO $db, int $idVinEnvio, float $costo): void {
        $stmt = $db->prepare("UPDATE lgs_envios_vins SET costo_unidad = :costo WHERE id = :id");
        $stmt->execute(['costo' => $costo, 'id' => $idVinEnvio]);
    }
}
