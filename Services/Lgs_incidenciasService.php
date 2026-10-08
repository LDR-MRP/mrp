<?php

declare(strict_types=1);

class Lgs_incidenciasService
{
    private Lgs_incidenciasModel $model;
    private Lgs_uploadService $uploadService;

    public function __construct()
    {
        $this->model = new Lgs_incidenciasModel();
        $this->uploadService = new Lgs_uploadService();
    }

    public function getCatalogos(): array
    {
        return $this->model->getCatalogosFormulario();
    }

    public function getEnviosElegibles(string $query = '', array $filtros = []): array
    {
        return $this->model->getEnviosElegibles($query, $filtros);
    }

    public function getVinsEnvio(int $idEnvio, ?string $fechaIncidente = null): array
    {
        if ($idEnvio <= 0) {
            throw new InvalidArgumentException("ID de envío no válido.");
        }
        return $this->model->getVinsEnvio($idEnvio, $fechaIncidente);
    }

    public function getIncidencias(array $filtros = []): array
    {
        return $this->model->getIncidenciasDataTable($filtros);
    }

    public function getIncidenciaDetalle(int $idIncidencia): array
    {
        if ($idIncidencia <= 0) {
            throw new InvalidArgumentException("ID de incidencia no válido.");
        }
        $detalle = $this->model->getIncidenciaDetalle($idIncidencia);
        if (!$detalle) {
            throw new Exception("Incidencia no encontrada.");
        }
        return $detalle;
    }

    /**
     * Registra una nueva incidencia operativa
     */
    public function guardarIncidencia(array $data, array $vinsSeleccionados, int $userId): int
    {
        $idEnvio = intval($data['id_envio'] ?? 0);
        $idTipoIncidencia = intval($data['id_tipo_incidencia'] ?? 0);
        $descripcion = trim($data['descripcion'] ?? '');
        $fechaIncidente = $data['fecha_incidente'] ?? date('Y-m-d H:i:s');

        if ($idEnvio <= 0) {
            throw new Exception("Debe seleccionar un envío válido.");
        }
        if ($idTipoIncidencia <= 0) {
            throw new Exception("Debe seleccionar el tipo de incidencia.");
        }
        if (empty($descripcion)) {
            throw new Exception("La descripción de la incidencia es obligatoria.");
        }
        if (empty($vinsSeleccionados)) {
            throw new Exception("Debe seleccionar al menos un VIN afectado.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            // 1. Validar estado del envío (3: Aprobado, 6: Ejecutado, 7: Entregado)
            $stmtEnvio = $db->prepare("SELECT id_estado, id_proveedor FROM lgs_envios WHERE id_envio = ? AND deleted_at IS NULL FOR UPDATE");
            $stmtEnvio->execute([$idEnvio]);
            $envio = $stmtEnvio->fetch(PDO::FETCH_ASSOC);

            if (!$envio) {
                throw new Exception("El envío especificado no existe o ha sido eliminado.");
            }

            $estadoEnvio = intval($envio['id_estado']);
            if (!in_array($estadoEnvio, [3, 6, 7], true)) {
                throw new Exception("Solo se pueden registrar incidencias en envíos Aprobados (3), En Tránsito/Ejecutados (6) o Entregados (7). Estado actual: {$estadoEnvio}.");
            }

            $esPostEntrega = ($estadoEnvio === 7) ? 1 : 0;
            $idProveedor = !empty($envio['id_proveedor']) ? intval($envio['id_proveedor']) : null;

            // 2. Generar Folio IN-XXXXXX
            $folio = $this->model->generarFolioTransaccional($db);

            // 3. Insertar Cabecera
            $incidenciaData = [
                'folio'                     => $folio,
                'id_envio'                  => $idEnvio,
                'id_tipo_incidencia'        => $idTipoIncidencia,
                'id_proveedor'              => $idProveedor,
                'origen'                    => $data['origen'] ?? 'OPERACION',
                'severidad'                 => $data['severidad'] ?? 'MEDIA',
                'fecha_incidente'           => $fechaIncidente,
                'ubicacion_texto'           => trim($data['ubicacion_texto'] ?? '') ?: null,
                'descripcion'               => $descripcion,
                'estado_envio_al_registrar' => $estadoEnvio,
                'es_post_entrega'           => $esPostEntrega,
                'id_absorcion'              => null,
                'porcentaje_proveedor'      => null,
                'dictamen_notas'            => null,
                'dictaminado_by'            => null,
                'dictaminado_at'            => null,
                'id_estado'                 => 1, // Abierta
                'created_by'                => $userId,
            ];

            $idIncidencia = $this->model->insertIncidencia($db, $incidenciaData);

            // 4. Insertar VINs afectados verificando pertenencia al envío
            $stmtVinInfo = $db->prepare("SELECT id, id_unidad, fecha_entrega_real FROM lgs_envios_vins WHERE id = ? AND id_envio = ?");
            foreach ($vinsSeleccionados as $vinItem) {
                $idEnvioVin = is_array($vinItem) ? intval($vinItem['id_envio_vin']) : intval($vinItem);
                $stmtVinInfo->execute([$idEnvioVin, $idEnvio]);
                $vinRow = $stmtVinInfo->fetch(PDO::FETCH_ASSOC);

                if (!$vinRow) {
                    throw new Exception("El VIN ID {$idEnvioVin} no pertenece al envío {$idEnvio}.");
                }

                $estadoVinAlIncidente = (!empty($vinRow['fecha_entrega_real']) && $vinRow['fecha_entrega_real'] <= $fechaIncidente) 
                                        ? 'ENTREGADO' : 'A_BORDO';

                $vinTexto = is_array($vinItem) && !empty($vinItem['vin']) ? trim($vinItem['vin']) : 'VIN-' . $vinRow['id_unidad'];

                $this->model->insertVinIncidencia($db, [
                    'id_incidencia'           => $idIncidencia,
                    'id_envio_vin'            => $idEnvioVin,
                    'id_unidad'               => intval($vinRow['id_unidad']),
                    'vin'                     => $vinTexto,
                    'estado_vin_al_incidente' => $estadoVinAlIncidente,
                ]);
            }

            // 5. Bitácora inicial
            $this->model->insertLogIncidencia(
                $db,
                $idIncidencia,
                null,
                1,
                "Incidencia registrada con folio {$folio} ({$descripcion})",
                $userId
            );

            $db->commit();
            return $idIncidencia;
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Actualiza una incidencia existente
     */
    public function actualizarIncidencia(int $idIncidencia, array $data, array $vinsSeleccionados, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $inc = $this->model->getIncidenciaDetalle($idIncidencia);
            if (!$inc) {
                throw new Exception("Incidencia no encontrada.");
            }

            if (!in_array(intval($inc['id_estado']), [1, 2], true)) {
                throw new Exception("Solo se pueden editar incidencias en estado Abierta o En Investigación.");
            }

            $idEnvio = intval($inc['id_envio']);
            $fechaIncidente = $data['fecha_incidente'] ?? $inc['fecha_incidente'];

            $this->model->updateIncidencia($db, $idIncidencia, [
                'id_tipo_incidencia' => intval($data['id_tipo_incidencia']),
                'origen'             => $data['origen'] ?? 'OPERACION',
                'severidad'          => $data['severidad'] ?? 'MEDIA',
                'fecha_incidente'    => $fechaIncidente,
                'ubicacion_texto'    => trim($data['ubicacion_texto'] ?? '') ?: null,
                'descripcion'        => trim($data['descripcion']),
                'updated_by'         => $userId,
            ]);

            // Reemplazar VINs
            if (!empty($vinsSeleccionados)) {
                $this->model->deleteVinsIncidencia($db, $idIncidencia);
                $stmtVinInfo = $db->prepare("SELECT id, id_unidad, fecha_entrega_real FROM lgs_envios_vins WHERE id = ? AND id_envio = ?");
                foreach ($vinsSeleccionados as $vinItem) {
                    $idEnvioVin = is_array($vinItem) ? intval($vinItem['id_envio_vin']) : intval($vinItem);
                    $stmtVinInfo->execute([$idEnvioVin, $idEnvio]);
                    $vinRow = $stmtVinInfo->fetch(PDO::FETCH_ASSOC);

                    if (!$vinRow) {
                        continue;
                    }

                    $estadoVinAlIncidente = (!empty($vinRow['fecha_entrega_real']) && $vinRow['fecha_entrega_real'] <= $fechaIncidente) 
                                            ? 'ENTREGADO' : 'A_BORDO';
                    $vinTexto = is_array($vinItem) && !empty($vinItem['vin']) ? trim($vinItem['vin']) : 'VIN-' . $vinRow['id_unidad'];

                    $this->model->insertVinIncidencia($db, [
                        'id_incidencia'           => $idIncidencia,
                        'id_envio_vin'            => $idEnvioVin,
                        'id_unidad'               => intval($vinRow['id_unidad']),
                        'vin'                     => $vinTexto,
                        'estado_vin_al_incidente' => $estadoVinAlIncidente,
                    ]);
                }
            }

            $this->model->insertLogIncidencia($db, $idIncidencia, intval($inc['id_estado']), intval($inc['id_estado']), "Incidencia actualizada", $userId);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Sube y asocia una evidencia a la incidencia
     */
    public function subirEvidencia(int $idIncidencia, ?int $idEnvioVin, string $tipo, array $file, int $userId): array
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $inc = $this->model->getIncidenciaDetalle($idIncidencia);
            if (!$inc) {
                throw new Exception("Incidencia no encontrada.");
            }

            $uploadResult = $this->uploadService->uploadEvidenciaIncidencia($file, $idIncidencia);

            $idEvidencia = $this->model->insertEvidenciaIncidencia($db, [
                'id_incidencia'   => $idIncidencia,
                'id_envio_vin'    => $idEnvioVin,
                'tipo'            => $tipo,
                'ruta_archivo'    => $uploadResult['ruta_archivo'],
                'nombre_original' => $uploadResult['nombre_original'],
                'mime'            => $uploadResult['mime'],
                'tamano_bytes'    => $uploadResult['tamano_bytes'],
                'created_by'      => $userId,
            ]);

            $db->commit();
            return array_merge($uploadResult, ['id_evidencia' => $idEvidencia]);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Elimina una evidencia física y lógicamente
     */
    public function eliminarEvidencia(int $idEvidencia, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $ruta = $this->model->deleteEvidencia($db, $idEvidencia);
            $db->commit();

            if ($ruta && file_exists($ruta)) {
                @unlink($ruta);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Dictamina la absorción del daño / evento
     */
    public function dictaminar(int $idIncidencia, int $idAbsorcion, ?float $porcentajeProv, string $notas, int $userId): void
    {
        if ($idAbsorcion <= 0) {
            throw new Exception("Debe seleccionar una opción válida de absorción.");
        }
        if (empty(trim($notas))) {
            throw new Exception("Debe ingresar las notas u observaciones del dictamen.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $inc = $this->model->getIncidenciaDetalle($idIncidencia);
            if (!$inc) {
                throw new Exception("Incidencia no encontrada.");
            }

            $this->model->dictaminarIncidencia($db, $idIncidencia, $idAbsorcion, $porcentajeProv, trim($notas), $userId);

            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cambia de estado una incidencia
     */
    public function cambiarEstado(int $idIncidencia, int $nuevoEstado, ?string $comentario, int $userId): void
    {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->updateEstadoIncidencia($db, $idIncidencia, $nuevoEstado, $comentario, $userId);
            $db->commit();
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
