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

            // 3. Validar estado del envío (1: Creado, 8: Confirmado, 2: En Aprobación, 3: Aprobado, 5: Programado, 6: Ejecutado, 7: Entregado)
            $stmtEnvio = $db->prepare("SELECT id_estado FROM lgs_envios WHERE id_envio = ? AND deleted_at IS NULL");
            $stmtEnvio->execute([$idEnvio]);
            $estadoEnvio = intval($stmtEnvio->fetchColumn());
            if (!in_array($estadoEnvio, [1, 2, 3, 5, 6, 7, 8], true)) {
                throw new Exception("Solo se pueden registrar gastos en envíos activos (En Creación, Confirmados, En Aprobación, Aprobados, Programados, En Tránsito o Entregados).");
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
            $this->notificarSolicitudAprobacionGasto($idGasto, 'NUEVO_GASTO');
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
            $this->notificarSolicitudAprobacionGasto($idGasto, 'REVISION');
        } catch (Exception $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    /**
     * Valida si el usuario cuenta con el permiso desacoplado para autorizar gastos adicionales
     * Regla: 1 solo nivel (Solicitante -> Aprobador), validado por PERMISO (no casado con persona o puesto)
     */
    public function tienePermisoAprobacion(int $userId): bool
    {
        $userRole = (int)($_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0));
        if ($userRole === 1 || $userRole === 70) return true; // Administrador general o Admin Logística

        // 1. Revisar permisos en sesión para módulo de gastos (162) o aprobaciones (157)
        if (!empty($_SESSION['permisos'][162]['u']) || !empty($_SESSION['permisos'][157]['u']) ||
            !empty($_SESSION['permisos'][162]['w']) || !empty($_SESSION['permisos'][157]['w'])) {
            return true;
        }

        // 2. Revisar en base de datos permisos por rol o usuario
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM usuarios u
                LEFT JOIN permisos p ON p.rolid = u.rolid AND p.moduloid IN (157, 162) AND (p.u = 1 OR p.w = 1)
                LEFT JOIN lgs_aprobadores ap ON ap.id_usuario = u.idusuario AND ap.activo = 1
                WHERE u.idusuario = ? AND (u.rolid IN (1, 70) OR p.idpermiso IS NOT NULL OR ap.id IS NOT NULL)
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
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
            if (!$this->tienePermisoAprobacion($userId)) {
                throw new Exception("Acceso Denegado: No cuenta con el permiso requerido para autorizar gastos adicionales.");
            }

            // Segregación de funciones: quien registró no puede aprobar su propio gasto (salvo admin rol 1)
            $userRole = (int)($_SESSION['userData']['idrol'] ?? 0);
            if (intval($g['created_by']) === $userId && $userRole !== 1) {
                throw new Exception("Segregación de funciones: el usuario solicitante que registró el gasto no puede autorizarlo.");
            }

            $this->model->updateEstadoGasto($db, $idGasto, 3, "Gasto aprobado por el usuario autorizador", $userId);

            $db->commit();
            $this->notificarResolucionGasto($idGasto, 'APROBADO', null, $userId);
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

            if (!$this->tienePermisoAprobacion($userId)) {
                throw new Exception("Acceso Denegado: No cuenta con el permiso requerido para rechazar gastos adicionales.");
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
            $this->notificarResolucionGasto($idGasto, 'RECHAZADO', $motivo, $userId);
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
        $vins = [];
        try {
            $sql = "SELECT * FROM vw_lgs_costo_real_vin WHERE id_envio = ? ORDER BY id_envio_vin ASC";
            $vins = $this->model->select_all($sql, [$idEnvio]) ?: [];
        } catch (Throwable $e) {
            $vins = [];
        }

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

        $porcentajeBase = round(100.0 / $numVins, 5);
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

    /**
     * Obtiene los correos electrónicos de los usuarios con facultades de aprobación
     */
    private function obtenerCorreosAprobadores(): array
    {
        try {
            $db = $this->model->getConexion();
            $sqlAprob = "
                SELECT DISTINCT u.email_user 
                FROM usuarios u
                LEFT JOIN permisos p ON p.rolid = u.rolid AND p.moduloid IN (157, 162) AND (p.u = 1 OR p.w = 1)
                LEFT JOIN lgs_aprobadores ap ON ap.id_usuario = u.idusuario AND ap.activo = 1
                WHERE (u.rolid = 1 OR p.idpermiso IS NOT NULL OR ap.id IS NOT NULL)
                  AND u.status = 1 
                  AND u.email_user IS NOT NULL
            ";
            $stmt = $db->query($sqlAprob);
            $correos = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];

            $destinatarios = array_filter(array_unique($correos), function($e) {
                return filter_var(trim((string)$e), FILTER_VALIDATE_EMAIL);
            });

            if (defined('MAIL_WEBMASTER') && filter_var(MAIL_WEBMASTER, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = MAIL_WEBMASTER;
            }

            return array_values(array_unique($destinatarios));
        } catch (Throwable $e) {
            error_log("Error obteniendo correos de aprobadores de gastos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene el correo electrónico de un usuario por su ID
     */
    private function obtenerEmailUsuario(int $userId): ?string
    {
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("SELECT email_user FROM usuarios WHERE idusuario = ? AND status = 1");
            $stmt->execute([$userId]);
            $correo = $stmt->fetchColumn();
            return ($correo && filter_var(trim((string)$correo), FILTER_VALIDATE_EMAIL)) ? trim((string)$correo) : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * Envía un correo electrónico en formato HTML mediante PHPMailer (con fallback a mail)
     */
    private function enviarEmailHtml(array $destinatarios, string $asunto, string $html): void
    {
        if (empty($destinatarios)) return;

        try {
            if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer') && defined('MAIL_HOST') && !empty(MAIL_HOST)) {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = MAIL_HOST;
                $mail->SMTPAuth   = true;
                $mail->Username   = MAIL_USER;
                $mail->Password   = MAIL_PASS;
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = MAIL_PORT;
                $mail->CharSet    = 'UTF-8';
                $fromName         = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'MRP Logística';
                $mail->setFrom(MAIL_USER, $fromName);

                foreach ($destinatarios as $correo) {
                    $mail->addAddress($correo);
                }

                $mail->isHTML(true);
                $mail->Subject = $asunto;
                $mail->Body    = $html;
                $mail->send();
            } else {
                $headers = "MIME-Version: 1.0\r\nContent-type: text/html; charset=UTF-8\r\nFrom: no-reply@ldrsolutions.com.mx\r\n";
                @mail(implode(',', $destinatarios), $asunto, $html, $headers);
            }
        } catch (Throwable $th) {
            error_log("Error enviando correo de gastos adicionales: " . $th->getMessage());
        }
    }

    /**
     * Notifica a los aprobadores sobre una nueva solicitud de gasto adicional o envío a revisión
     */
    public function notificarSolicitudAprobacionGasto(int $idGasto, string $tipoEvento = 'REVISION'): void
    {
        try {
            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) return;

            $destinatarios = $this->obtenerCorreosAprobadores();
            if (empty($destinatarios)) return;

            $folio = $g['folio'] ?? 'GA-' . $idGasto;
            $tipoGasto = $g['tipo_gasto'] ?? 'Gasto Adicional';
            $monto = '$' . number_format((float)($g['monto_final'] ?? 0), 2);
            $solicitante = $g['registrado_por'] ?? 'Operador';
            $envio = !empty($g['folio_envio']) ? $g['folio_envio'] : 'Gasto Independiente';
            $descripcion = htmlspecialchars($g['descripcion'] ?? 'Sin descripción', ENT_QUOTES, 'UTF-8');
            $naturaleza = $g['naturaleza'] ?? 'CARGO';
            $colorNaturaleza = ($naturaleza === 'CARGO') ? '#DC2626' : '#16A34A';
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $tituloHeader = ($tipoEvento === 'REVISION') 
                ? 'Gasto Adicional Enviado a Revisión' 
                : 'Nuevo Gasto Adicional Registrado';
            $asunto = "🔔 Autorización Requerida: {$folio} ({$tipoGasto}) - {$monto}";

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #C46623; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>{$tituloHeader}</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        Se ha solicitado la aprobación de un gasto adicional extraordinario en el módulo de logística.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Tipo de Gasto:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$tipoGasto}</strong></td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Importe Solicitado:</strong></td>
                            <td style='padding: 8px 0; color: {$colorNaturaleza}; font-weight: bold; font-size: 15px;'>{$monto} MXN ({$naturaleza})</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Envío Asociado:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$envio}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Solicitante:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$solicitante}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748B; vertical-align: top;'><strong>Concepto / Justificación:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A; line-height: 1.4;'>{$descripcion}</td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_gastosadicionales' style='background-color: #C46623; color: #ffffff; text-decoration: none; padding: 11px 26px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Revisar y Dictaminar Gasto
                        </a>
                    </div>
                </div>
            </div>";

            $this->enviarEmailHtml($destinatarios, $asunto, $html);
        } catch (Throwable $e) {
            error_log("Error al notificar solicitud de aprobación de gasto: " . $e->getMessage());
        }
    }

    /**
     * Notifica al solicitante sobre la resolución (Aprobado o Rechazado) del gasto adicional
     */
    public function notificarResolucionGasto(int $idGasto, string $resolucion, ?string $motivo = null, int $aprobadorId = 0): void
    {
        try {
            $g = $this->model->getGastoDetalle($idGasto);
            if (!$g) return;

            $destinatarios = [];
            $creatorId = intval($g['created_by'] ?? 0);
            if ($creatorId > 0) {
                $emailCreador = $this->obtenerEmailUsuario($creatorId);
                if ($emailCreador) {
                    $destinatarios[] = $emailCreador;
                }
            }

            if (defined('MAIL_WEBMASTER') && filter_var(MAIL_WEBMASTER, FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = MAIL_WEBMASTER;
            }

            $destinatarios = array_values(array_unique($destinatarios));
            if (empty($destinatarios)) return;

            $folio = $g['folio'] ?? 'GA-' . $idGasto;
            $tipoGasto = $g['tipo_gasto'] ?? 'Gasto Adicional';
            $monto = '$' . number_format((float)($g['monto_final'] ?? 0), 2);
            $aprobadorNombre = !empty($g['aprobado_por']) ? $g['aprobado_por'] : 'Aprobador de Logística';
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $esAprobado = ($resolucion === 'APROBADO');
            $headerColor = $esAprobado ? '#16A34A' : '#DC2626';
            $asunto = $esAprobado 
                ? "✅ Gasto Adicional {$folio} APROBADO ({$monto})" 
                : "❌ Gasto Adicional {$folio} RECHAZADO";

            $mensajeIntro = $esAprobado
                ? "Su solicitud de gasto adicional ha sido <strong>APROBADA</strong> y se encuentra autorizada en el sistema para proceder con su documentación financiera (factura o comprobante fiscal)."
                : "Su solicitud de gasto adicional ha sido <strong>RECHAZADA</strong> por la coordinación.";

            $filaMotivo = '';
            if (!$esAprobado && !empty($motivo)) {
                $motivoSeguro = htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8');
                $filaMotivo = "
                <tr style='border-bottom: 1px solid #F1F5F9;'>
                    <td style='padding: 8px 0; color: #DC2626; vertical-align: top;'><strong>Motivo del Rechazo:</strong></td>
                    <td style='padding: 8px 0; color: #991B1B; font-weight: bold; line-height: 1.4;'>{$motivoSeguro}</td>
                </tr>";
            }

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: {$headerColor}; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Gasto Adicional " . ($esAprobado ? 'Aprobado' : 'Rechazado') . "</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        {$mensajeIntro}
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Tipo de Gasto:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$tipoGasto}</strong></td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Importe:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A; font-weight: bold;'>{$monto} MXN</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Dictaminado por:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$aprobadorNombre}</td>
                        </tr>
                        {$filaMotivo}
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_gastosadicionales' style='background-color: {$headerColor}; color: #ffffff; text-decoration: none; padding: 11px 26px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            " . ($esAprobado ? 'Documentar Comprobante' : 'Ver Detalle en Sistema') . "
                        </a>
                    </div>
                </div>
            </div>";

            $this->enviarEmailHtml($destinatarios, $asunto, $html);
        } catch (Throwable $e) {
            error_log("Error al notificar resolución de gasto: " . $e->getMessage());
        }
    }
}
