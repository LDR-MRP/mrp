<?php

declare(strict_types=1);

class Lgs_gastosadicionalesService
{
    private Lgs_gastosadicionalesModel $model;
    private Lgs_incidenciasModel $incidenciasModel;
    private Lgs_tarifaCalculator $tarifaCalculator;
    private Lgs_uploadService $uploadService;

    public function __construct()
    {
        $this->model = new Lgs_gastosadicionalesModel();
        $this->incidenciasModel = new Lgs_incidenciasModel();
        $this->tarifaCalculator = new Lgs_tarifaCalculator();
        $this->uploadService = new Lgs_uploadService();
    }

    public function getCatalogos(): array
    {
        return $this->model->getCatalogosFormulario();
    }

    public function buscarIncidencias(string $query = '', array $filtros = []): array
    {
        return $this->model->buscarIncidenciasDisponibles($query, $filtros);
    }

    public function getGastos(array $filtros = []): array
    {
        return $this->model->getGastosDataTable($filtros);
    }

    public function getGastoDetalle(int $idGasto): array
    {
        if ($idGasto <= 0) {
            throw new InvalidArgumentException("ID de gasto no válido.");
        }
        $detalle = $this->model->getGastoDetalle($idGasto);
        if (!$detalle) {
            throw new Exception("Gasto adicional no encontrado.");
        }
        return $detalle;
    }

    /**
     * Previsualiza el cálculo y reparto del gasto sin persistir
     */
    public function previsualizarCalculo(array $data, array $vinsSeleccionados): array
    {
        $idEnvio = intval($data['id_envio'] ?? 0);
        $idTipoGasto = intval($data['id_tipo_gasto'] ?? 0);
        $montoManual = isset($data['monto_final']) ? floatval($data['monto_final']) : null;

        if ($idEnvio <= 0) {
            throw new Exception("Debe especificar un envío válido.");
        }
        if (empty($vinsSeleccionados)) {
            throw new Exception("Debe seleccionar al menos una unidad para el reparto.");
        }

        $db = $this->model->getConexion();
        $envioInfo = $this->model->getEnvioConSegmentoDominante($idEnvio);
        if (!$envioInfo) {
            throw new Exception("No se encontró información del envío.");
        }

        // Consultar tipo de gasto
        $stmtTipo = $db->prepare("SELECT * FROM lgs_cat_tipos_gasto_adicional WHERE id_tipo_gasto = ?");
        $stmtTipo->execute([$idTipoGasto]);
        $tipoRow = $stmtTipo->fetch(PDO::FETCH_ASSOC);

        if (!$tipoRow) {
            throw new Exception("Tipo de gasto no válido.");
        }

        $calculoModo = $tipoRow['calculo']; // KM, CANTIDAD_X_UNITARIO, MONTO_LIBRE
        $numVins = count($vinsSeleccionados);

        $montoCalculado = 0.00;
        $tarifaAplicada = 0.00;
        $kmInfo = [];

        if ($calculoModo === 'KM') {
            $kmExtra = floatval($data['km_adicionales'] ?? 0.0);
            $calc = $this->tarifaCalculator->calcularKmAdicional($db, $envioInfo, $kmExtra, $numVins);
            $montoCalculado = $calc['monto_calculado'];
            $tarifaAplicada = $calc['tarifa_aplicada'];
            $kmInfo = $calc;
        } elseif ($calculoModo === 'CANTIDAD_X_UNITARIO') {
            $cantidad = floatval($data['cantidad'] ?? 1.0);
            $precioUnitario = floatval($data['precio_unitario'] ?? 0.0);
            $tarifaAplicada = $precioUnitario;
            $montoCalculado = round($cantidad * $precioUnitario, 2);
        } else {
            // MONTO_LIBRE
            $montoCalculado = ($montoManual !== null) ? round($montoManual, 2) : 0.00;
        }

        $montoFinal = ($montoManual !== null && $montoManual > 0) ? round($montoManual, 2) : $montoCalculado;

        // Reparto en partes iguales con ajuste de centavos al último VIN
        $reparto = $this->calcularRepartoPartesIguales($montoFinal, $vinsSeleccionados);

        return [
            'monto_calculado' => $montoCalculado,
            'monto_final'     => $montoFinal,
            'tarifa_aplicada' => $tarifaAplicada,
            'km_info'         => $kmInfo,
            'reparto'         => $reparto,
        ];
    }

    /**
     * Guarda un nuevo gasto adicional (ligado o independiente)
     */
    public function guardarGasto(array $data, array $vinsSeleccionados, int $userId): int
    {
        $idTipoGasto = intval($data['id_tipo_gasto'] ?? 0);
        $idIncidencia = !empty($data['id_incidencia']) ? intval($data['id_incidencia']) : null;
        $idEnvio = intval($data['id_envio'] ?? 0);
        $descripcion = trim($data['descripcion'] ?? '');
        $justificacionIndep = trim($data['justificacion_independiente'] ?? '');
        $fechaGasto = $data['fecha_gasto'] ?? date('Y-m-d H:i:s');

        if ($idTipoGasto <= 0) {
            throw new Exception("Debe seleccionar el tipo de gasto adicional.");
        }
        if (empty($descripcion)) {
            throw new Exception("La descripción del concepto es obligatoria.");
        }
        if (empty($vinsSeleccionados)) {
            throw new Exception("Debe seleccionar al menos una unidad para asignar el gasto.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            // 1. Obtener catálogo del tipo de gasto
            $stmtTipo = $db->prepare("SELECT * FROM lgs_cat_tipos_gasto_adicional WHERE id_tipo_gasto = ?");
            $stmtTipo->execute([$idTipoGasto]);
            $tipoGasto = $stmtTipo->fetch(PDO::FETCH_ASSOC);
            if (!$tipoGasto) {
                throw new Exception("Tipo de gasto no válido.");
            }

            $responsable = $tipoGasto['responsable'];
            $naturaleza  = $tipoGasto['naturaleza'];
            $documento   = $tipoGasto['documento'];
            $idAbsorcion = null;

            // 2. Si está ligado a incidencia
            if ($idIncidencia !== null && $idIncidencia > 0) {
                $inc = $this->incidenciasModel->getIncidenciaDetalle($idIncidencia);
                if (!$inc) {
                    throw new Exception("La incidencia especificada no existe.");
                }

                $idEnvio = intval($inc['id_envio']);
                $idProveedor = !empty($inc['id_proveedor']) ? intval($inc['id_proveedor']) : null;

                // Si es daño, validar que la incidencia tenga dictamen
                if ($tipoGasto['clave'] === 'DANIO_UNIDAD') {
                    if (empty($inc['id_absorcion'])) {
                        throw new Exception("Para gastos de daño en unidades, la incidencia debe contar con dictamen de absorción previo.");
                    }
                    if ($inc['absorcion_clave'] === 'IMPROCEDENTE') {
                        throw new Exception("No se puede generar gasto: la incidencia fue dictaminada como Improcedente.");
                    }

                    $idAbsorcion = intval($inc['id_absorcion']);
                    $naturaleza  = $inc['absorcion_naturaleza'] ?? $naturaleza;
                    $documento   = $inc['absorcion_documento'] ?? $documento;
                    if ($inc['absorcion_clave'] === 'PROVEEDOR') {
                        $responsable = 'PROVEEDOR';
                    } elseif ($inc['absorcion_clave'] === 'EMPRESA') {
                        $responsable = 'INTERNO';
                    }
                }
            } else {
                // Es independiente
                if (intval($tipoGasto['requiere_incidencia']) === 1) {
                    throw new Exception("Este tipo de gasto ({$tipoGasto['nombre']}) requiere obligatoriamente estar vinculado a una incidencia dictaminada.");
                }
                if (mb_strlen($justificacionIndep) < 15) {
                    throw new Exception("Los gastos independientes requieren una justificación comercial/operativa detallada (mínimo 15 caracteres).");
                }
                if ($idEnvio <= 0) {
                    throw new Exception("Debe seleccionar un envío para vincular el gasto.");
                }

                $envioRow = $this->model->getEnvioConSegmentoDominante($idEnvio);
                if (!$envioRow) {
                    throw new Exception("Envío no encontrado.");
                }
                $idProveedor = !empty($envioRow['id_proveedor']) ? intval($envioRow['id_proveedor']) : null;
            }

            // 3. Validar estado del envío (3, 6, 7)
            $stmtEnvio = $db->prepare("SELECT id_estado FROM lgs_envios WHERE id_envio = ? AND deleted_at IS NULL");
            $stmtEnvio->execute([$idEnvio]);
            $estadoEnvio = intval($stmtEnvio->fetchColumn());
            if (!in_array($estadoEnvio, [3, 6, 7], true)) {
                throw new Exception("Solo se pueden registrar gastos en envíos Aprobados (3), En Tránsito (6) o Entregados (7).");
            }

            // 4. Calcular importes
            $preview = $this->previsualizarCalculo(array_merge($data, ['id_envio' => $idEnvio]), $vinsSeleccionados);
            $montoCalculado = $preview['monto_calculado'];
            $montoFinal     = $preview['monto_final'];
            $tarifaAplicada = $preview['tarifa_aplicada'];
            $kmInfo         = $preview['km_info'];
            $repartoPartidas= $preview['reparto'];

            if ($montoFinal <= 0) {
                throw new Exception("El monto final del gasto debe ser mayor a cero.");
            }

            // 5. Generar Folio GA-XXXXXX
            $folio = $this->model->generarFolioTransaccional($db);

            // 6. Insertar cabecera
            $gastoData = [
                'folio'                       => $folio,
                'id_envio'                    => $idEnvio,
                'id_incidencia'               => $idIncidencia,
                'id_tipo_gasto'               => $idTipoGasto,
                'id_motivo_gasto'             => !empty($data['id_motivo_gasto']) ? intval($data['id_motivo_gasto']) : null,
                'alcance_aplicado'            => $data['alcance_aplicado'] ?? $tipoGasto['alcance'],
                'id_proveedor'                => $idProveedor,
                'responsable'                 => $responsable,
                'naturaleza'                  => $naturaleza,
                'documento'                   => $documento,
                'id_absorcion'                => $idAbsorcion,
                'justificacion_independiente' => $justificacionIndep ?: null,
                'km_aprobados'                => $kmInfo['km_aprobados'] ?? null,
                'km_adicionales'              => $kmInfo['km_adicionales'] ?? null,
                'tipo_servicio_original'      => $kmInfo['tipo_servicio_original'] ?? null,
                'tipo_servicio_nuevo'         => $kmInfo['tipo_servicio_nuevo'] ?? null,
                'cantidad'                    => !empty($data['cantidad']) ? floatval($data['cantidad']) : null,
                'precio_unitario'             => !empty($data['precio_unitario']) ? floatval($data['precio_unitario']) : null,
                'tarifa_aplicada'             => $tarifaAplicada,
                'monto_calculado'             => $montoCalculado,
                'monto_final'                 => $montoFinal,
                'justificacion_ajuste'        => trim($data['justificacion_ajuste'] ?? '') ?: null,
                'moneda'                      => 'MXN',
                'fecha_gasto'                 => $fechaGasto,
                'descripcion'                 => $descripcion,
                'id_estado'                   => 1, // Registrado
                'created_by'                  => $userId,
            ];

            $idGasto = $this->model->insertGasto($db, $gastoData);

            // 7. Insertar partidas de reparto por VIN
            foreach ($repartoPartidas as $partida) {
                $this->model->insertRepartoVin($db, [
                    'id_gasto'     => $idGasto,
                    'id_envio_vin' => $partida['id_envio_vin'],
                    'id_unidad'    => $partida['id_unidad'],
                    'vin'          => $partida['vin'],
                    'porcentaje'   => $partida['porcentaje'],
                    'monto'        => $partida['monto'],
                ]);
            }

            // 8. Bitácora
            $this->model->insertLogGasto($db, $idGasto, null, 1, "Gasto registrado con folio {$folio} ({$descripcion})", $userId);

            // 9. Actualizar estado de la incidencia a Con Gastos (4) si aplica
            if ($idIncidencia !== null && $idIncidencia > 0) {
                $stmtIncEst = $db->prepare("SELECT id_estado FROM lgs_tra_incidencias WHERE id_incidencia = ?");
                $stmtIncEst->execute([$idIncidencia]);
                $estIncActual = intval($stmtIncEst->fetchColumn());

                if (in_array($estIncActual, [1, 2, 3], true)) {
                    $this->incidenciasModel->updateEstadoIncidencia(
                        $db,
                        $idIncidencia,
                        4,
                        "Incidencia pasa a Con Gastos tras asignación de {$folio}",
                        $userId
                    );
                }
            }

            $db->commit();
            return $idGasto;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Envía un gasto registrado a revisión (Estado 2)
     */
    public function enviarRevision(int $idGasto, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) throw new Exception("Gasto no encontrado.");
            if (intval($g['id_estado']) !== 1) {
                throw new Exception("Solo se pueden enviar a revisión gastos en estado Registrado.");
            }

            $this->model->updateEstadoGasto($db, $idGasto, 2, "Gasto enviado a revisión para aprobación", $userId);
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Aprueba un gasto adicional (Estado 3)
     */
    public function aprobarGasto(int $idGasto, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) throw new Exception("Gasto no encontrado.");

            if (!in_array(intval($g['id_estado']), [1, 2], true)) {
                throw new Exception("Solo se pueden aprobar gastos en estado Registrado o En Revisión.");
            }

            // Validar permiso de aprobador
            if (!$this->model->esUsuarioAprobador($userId)) {
                throw new Exception("No cuenta con permisos de aprobador para autorizar este gasto adicional.");
            }

            // Segregación de funciones: quien registró no puede aprobar su propio gasto
            if (intval($g['created_by']) === $userId) {
                throw new Exception("Segregación de funciones: el usuario que registró el gasto no puede autorizarlo.");
            }

            $this->model->updateEstadoGasto($db, $idGasto, 3, "Gasto aprobado por el usuario autorizador", $userId);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Rechaza un gasto adicional (Estado 4)
     */
    public function rechazarGasto(int $idGasto, string $motivo, int $userId): void
    {
        if (empty(trim($motivo))) {
            throw new Exception("Debe especificar el motivo del rechazo.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) throw new Exception("Gasto no encontrado.");

            if (!in_array(intval($g['id_estado']), [1, 2], true)) {
                throw new Exception("Solo se pueden rechazar gastos pendientes.");
            }

            if (!$this->model->esUsuarioAprobador($userId)) {
                throw new Exception("No cuenta con permisos de aprobador para rechazar este gasto.");
            }

            $sql = "UPDATE lgs_tra_gastos_adicionales SET id_estado = 4, motivo_rechazo = ?, updated_by = ?, updated_at = NOW() WHERE id_gasto = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$motivo, $userId, $idGasto]);

            $this->model->insertLogGasto($db, $idGasto, intval($g['id_estado']), 4, "Gasto rechazado: " . $motivo, $userId);

            // Si estaba ligado a incidencia, verificar si se debe cerrar
            if (!empty($g['id_incidencia'])) {
                $this->incidenciasModel->verificarYCerrarIncidencia($db, intval($g['id_incidencia']), $userId);
            }

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Cancela un gasto adicional (Estado 0)
     */
    public function cancelarGasto(int $idGasto, string $motivo, int $userId): void
    {
        if (empty(trim($motivo))) {
            throw new Exception("Debe indicar el motivo de la cancelación.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) throw new Exception("Gasto no encontrado.");

            if (intval($g['id_estado']) === 5) {
                throw new Exception("No se puede cancelar un gasto que ya ha sido documentado financieramente.");
            }

            $sql = "UPDATE lgs_tra_gastos_adicionales SET id_estado = 0, motivo_rechazo = ?, updated_by = ?, updated_at = NOW() WHERE id_gasto = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$motivo, $userId, $idGasto]);

            $this->model->insertLogGasto($db, $idGasto, intval($g['id_estado']), 0, "Gasto cancelado: " . $motivo, $userId);

            if (!empty($g['id_incidencia'])) {
                $this->incidenciasModel->verificarYCerrarIncidencia($db, intval($g['id_incidencia']), $userId);
            }

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Documenta el gasto con factura o nota de cargo aparte (Estado 5)
     */
    public function documentarGasto(int $idGasto, array $docData, ?array $file, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) throw new Exception("Gasto no encontrado.");

            if (intval($g['id_estado']) !== 3) {
                throw new Exception("Solo se pueden documentar gastos previamente Aprobados.");
            }

            $this->model->documentarGasto($db, $idGasto, $docData, $userId);

            // Si viene archivo de comprobante
            if ($file && isset($file['error']) && $file['error'] === UPLOAD_ERR_OK) {
                $uploadRes = $this->uploadService->uploadDocumentoGasto($file, $idGasto);
                $tipoDoc = ($docData['doc_tipo'] === 'NOTA_CARGO') ? 'NOTA_CARGO' : 'FACTURA_PDF';
                $this->model->insertDocumentoGasto($db, [
                    'id_gasto'        => $idGasto,
                    'tipo'            => $tipoDoc,
                    'ruta_archivo'    => $uploadRes['ruta_archivo'],
                    'nombre_original' => $uploadRes['nombre_original'],
                    'mime'            => $uploadRes['mime'],
                    'tamano_bytes'    => $uploadRes['tamano_bytes'],
                    'created_by'      => $userId,
                ]);
            }

            // Verificar cierre automático de la incidencia
            if (!empty($g['id_incidencia'])) {
                $this->incidenciasModel->verificarYCerrarIncidencia($db, intval($g['id_incidencia']), $userId);
            }

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Sube un documento soporte al gasto
     */
    public function subirDocumentoSoporte(int $idGasto, string $tipo, array $file, int $userId): array
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) throw new Exception("Gasto no encontrado.");

            $uploadRes = $this->uploadService->uploadDocumentoGasto($file, $idGasto);

            $idDoc = $this->model->insertDocumentoGasto($db, [
                'id_gasto'        => $idGasto,
                'tipo'            => $tipo,
                'ruta_archivo'    => $uploadRes['ruta_archivo'],
                'nombre_original' => $uploadRes['nombre_original'],
                'mime'            => $uploadRes['mime'],
                'tamano_bytes'    => $uploadRes['tamano_bytes'],
                'created_by'      => $userId,
            ]);

            $db->commit();
            return array_merge($uploadRes, ['id_documento' => $idDoc]);
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Elimina un documento soporte
     */
    public function eliminarDocumentoSoporte(int $idDoc, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $ruta = $this->model->deleteDocumentoGasto($db, $idDoc);
            $db->commit();

            if ($ruta && file_exists($ruta)) {
                @unlink($ruta);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Obtiene el resumen de costo real por VIN consultando la vista vw_lgs_costo_real_vin
     */
    public function getResumenEnvioCostoReal(int $idEnvio): array
    {
        $sql = "SELECT * FROM vw_lgs_costo_real_vin WHERE id_envio = ? ORDER BY id_envio_vin ASC";
        $vins = $this->model->select_all($sql, [$idEnvio]) ?: [];

        $totalPlaneado = 0.0;
        $totalCargos = 0.0;
        $totalDeducciones = 0.0;
        $totalReal = 0.0;
        $totalNeto = 0.0;

        foreach ($vins as $v) {
            $totalPlaneado += floatval($v['costo_planeado_unidad']);
            $totalCargos += floatval($v['total_cargos_adicionales']);
            $totalDeducciones += floatval($v['total_deducciones']);
            $totalReal += floatval($v['costo_real_unidad']);
            $totalNeto += floatval($v['costo_neto_unidad']);
        }

        return [
            'id_envio'          => $idEnvio,
            'total_planeado'    => round($totalPlaneado, 2),
            'total_cargos'      => round($totalCargos, 2),
            'total_deducciones' => round($totalDeducciones, 2),
            'total_real'        => round($totalReal, 2),
            'total_neto'        => round($totalNeto, 2),
            'vins'              => $vins,
        ];
    }

    /**
     * Helper: Reparte en partes iguales sin importar el segmento,
     * asignando centavos residuales al último VIN para cuadratura exacta.
     */
    private function calcularRepartoPartesIguales(float $montoTotal, array $vins): array
    {
        $numVins = count($vins);
        if ($numVins === 0) {
            return [];
        }

        $cuotaBase = floor(($montoTotal / $numVins) * 100) / 100;
        $centavosSobrantes = round($montoTotal - ($cuotaBase * $numVins), 2);

        $porcentajeBase = round(100.0 / $numVins, 4);
        $reparto = [];

        foreach ($vins as $idx => $v) {
            $idEnvioVin = is_array($v) ? intval($v['id_envio_vin']) : intval($v);
            $vinTexto = is_array($v) && !empty($v['vin']) ? trim($v['vin']) : 'VIN-' . $idEnvioVin;
            $idUnidad = is_array($v) && !empty($v['id_unidad']) ? intval($v['id_unidad']) : 0;

            // Al último VIN se le suman los centavos residuales
            $montoAsignado = ($idx === $numVins - 1) ? round($cuotaBase + $centavosSobrantes, 2) : $cuotaBase;

            $reparto[] = [
                'id_envio_vin' => $idEnvioVin,
                'id_unidad'    => $idUnidad,
                'vin'          => $vinTexto,
                'porcentaje'   => $porcentajeBase,
                'monto'        => $montoAsignado,
            ];
        }

        return $reparto;
    }
}
