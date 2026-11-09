<?php

require_once('Services/Lgs_notificacionesHelper.php');

class Lgs_planeacionesService {

    private Lgs_planeacionesModel $model;

    public function __construct() {
        $this->model = new Lgs_planeacionesModel();
    }

    public function getAllPlaneaciones(): array {
        return $this->model->getPlaneacionesDataTable();
    }

    public function getEnviosDisponibles(int $idPlaneacion = 0): array {
        return $this->model->getEnviosDisponiblesPlan($idPlaneacion);
    }

    /**
     * Crea una planeación agrupando varios envíos y cambiándolos a estado 2 (En Revisión)
     */
    public function createPlaneacion(array $data, array $enviosIds, int $userId): int {
        if (empty($enviosIds)) {
            throw new Exception("Debe seleccionar al menos un envío para la planeación.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            
            // 1. Folio y datos base
            $folio = $this->model->generarFolioPlan($db);
            $data['folio'] = $folio;
            $data['created_by'] = $userId;
            $data['id_estado'] = 2; // Enviada a Aprobación (Estado 2 de Planeación = Enviada)
            
            // Recalcular el gran total de la planeación sumando los envíos
            $costoAcumulado = 0.0;
            $kmAcumulados = 0.0;

            // Bloqueamos los envíos para lectura segura
            $inIds = implode(',', array_fill(0, count($enviosIds), '?'));
            $stmt = $db->prepare("SELECT id_envio, costo_total, km_total FROM lgs_envios WHERE id_envio IN ($inIds) FOR UPDATE");
            $stmt->execute($enviosIds);
            $enviosData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($enviosData as $e) {
                $costoAcumulado += (float)$e['costo_total'];
                $kmAcumulados += (float)$e['km_total'];
            }

            $data['costo_total'] = $costoAcumulado;
            $data['km_total'] = $kmAcumulados;

            // 2. Insertar cabecera de la Planeación
            $idPlaneacion = $this->model->insertPlaneacion($db, $data);
            
            // 3. Vincular los envíos y cambiarles el estado
            $stmtUpdateEnvio = $db->prepare("UPDATE lgs_envios SET id_estado = 2 WHERE id_envio = ?");
            
            foreach ($enviosIds as $idEnvio) {
                $this->model->insertPlanEnvio($db, $idPlaneacion, $idEnvio);
                $stmtUpdateEnvio->execute([$idEnvio]);
            }
            
            $db->commit();
            return $idPlaneacion;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Actualiza una planeación existente (en estado 1 - Borrador)
     */
    public function updatePlaneacion(int $idPlaneacion, array $data, array $enviosIds, int $userId): void {
        if (empty($enviosIds)) {
            throw new Exception("Debe seleccionar al menos un envío para la planeación.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            $data['updated_at'] = date('Y-m-d H:i:s');
            
            // Recalcular el gran total de la planeación sumando los envíos
            $costoAcumulado = 0.0;
            $kmAcumulados = 0.0;

            // Bloqueamos los envíos para lectura segura
            $inIds = implode(',', array_fill(0, count($enviosIds), '?'));
            $stmt = $db->prepare("SELECT id_envio, costo_total, km_total FROM lgs_envios WHERE id_envio IN ($inIds) FOR UPDATE");
            $stmt->execute($enviosIds);
            $enviosData = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($enviosData as $e) {
                $costoAcumulado += (float)$e['costo_total'];
                $kmAcumulados += (float)$e['km_total'];
            }

            $data['costo_total'] = $costoAcumulado;
            $data['km_total'] = $kmAcumulados;

            // 1. Actualizar cabecera de la Planeación
            $this->model->updatePlaneacion($db, $idPlaneacion, $data);
            
            // 2. Liberar los envíos anteriores (volver a estado 8)
            $oldEnvios = $this->model->deletePlanEnvios($db, $idPlaneacion);
            if (!empty($oldEnvios)) {
                $inOldIds = implode(',', array_fill(0, count($oldEnvios), '?'));
                $stmtRelease = $db->prepare("UPDATE lgs_envios SET id_estado = 8 WHERE id_envio IN ($inOldIds)");
                $stmtRelease->execute($oldEnvios);
            }
            
            // 3. Vincular los nuevos envíos y cambiarles el estado
            // Nota: Si se guarda un borrador, el estado de los envíos debería ser 1, pero según reabrirPlaneacion, el estado es 1 (editable).
            // Sin embargo, si al crear se envían a aprobación, id_estado es 2.
            // Para mantener coherencia, si el estado de la planeación se mantiene en 1 (Borrador), los envíos deberían ser 1.
            $stmtUpdateEnvio = $db->prepare("UPDATE lgs_envios SET id_estado = 1 WHERE id_envio = ?");
            
            foreach ($enviosIds as $idEnvio) {
                $this->model->insertPlanEnvio($db, $idPlaneacion, $idEnvio);
                $stmtUpdateEnvio->execute([$idEnvio]);
            }
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function getDetalleCompletoPlan(int $idPlaneacion): array {
        // 1. Recalcular costos de los envíos de esta planeación para evitar desfazamientos
        $db = $this->model->getConexion();
        $stmt = $db->prepare("SELECT id_envio FROM lgs_planeaciones_envios WHERE id_planeacion = ?");
        $stmt->execute([$idPlaneacion]);
        $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($envios)) {
            require_once 'Lgs_enviosService.php';
            $enviosService = new Lgs_enviosService();
            foreach ($envios as $envio) {
                $enviosService->recalcularCostoTotal((int)$envio['id_envio']);
                $enviosService->asegurarCostosVins((int)$envio['id_envio']);
            }
            
            // Recalcular también el costo y km total de la planeación
            $this->model->actualizarCostoYKmPlan($idPlaneacion);
        }

        return $this->model->getDetalleCompletoPlan($idPlaneacion);
    }

    /**
     * Reabre una planeación regresándola a borrador, desbloqueando sus envíos y notificando a los entes autorizadores
     */
    public function reabrirPlaneacion(int $idPlaneacion, string $motivoSelect, string $motivoDetalle, int $userId, string $userName): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $motivoTexto = "{$motivoSelect} - {$motivoDetalle}";
            $planData = $this->model->reabrirPlaneacion($db, $idPlaneacion, $motivoTexto);
            $db->commit();

            // Disparar notificación por correo a entes autorizadores
            $this->notificarReaperturaAprobadores($idPlaneacion, $planData, $motivoSelect, $motivoDetalle, $userName);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Envía correo a los entes autorizadores cuando se solicita una reapertura
     */
    private function notificarReaperturaAprobadores(int $idPlaneacion, array $planData, string $motivoSelect, string $motivoDetalle, string $userName): void
    {
        try {
            $folio = $planData['folio'] ?? "PL-" . str_pad((string)$idPlaneacion, 4, '0', STR_PAD_LEFT);
            $descripcion = $planData['descripcion'] ?? 'Planeación de Envíos';
            $rutas = $planData['total_rutas'] ?? 0;
            $monto = isset($planData['costo_total']) ? '$' . number_format((float)$planData['costo_total'], 2) : '$0.00';

            $db = $this->model->getConexion();
            $destinatarios = Lgs_notificacionesHelper::getCorreosAprobadores($db);
            if (empty($destinatarios)) return;

            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';
            $asunto = "⚠️ AVISO: Planeación {$folio} REABIERTA para Reajuste";
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #D97706; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Planeación Reabierta para Edición</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        El usuario <strong>{$userName}</strong> ha reabierto la planeación de envíos para realizar ajustes y modificaciones antes de su despacho. Los envíos asociados han regresado a estado editable.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Monto Estimado:</strong></td>
                            <td style='padding: 8px 0; color: #16A34A; font-weight: bold;'>{$monto} ({$rutas} envíos)</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Motivo Principal:</strong></td>
                            <td style='padding: 8px 0; color: #D97706; font-weight: bold;'>{$motivoSelect}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748B; vertical-align: top;'><strong>Detalle / Justificación:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A; line-height: 1.4;'>{$motivoDetalle}</td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_planeaciones' style='background-color: #D97706; color: #ffffff; text-decoration: none; padding: 10px 24px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Ver Planeación en Sistema
                        </a>
                    </div>
                </div>
            </div>";

            Lgs_notificacionesHelper::enviar($destinatarios, $asunto, $html);
        } catch (Throwable $ignored) {
            error_log("Error al enviar notificación de reapertura de planeación: " . $ignored->getMessage());
        }
    }

    /**
     * Envía una planeación existente en borrador a aprobación
     */
    public function enviarAprobacion(int $idPlaneacion, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->enviarAprobacion($db, $idPlaneacion);
            $db->commit();

            // Disparar notificación a Javier (Aprobador) y aviso informativo a Finanzas (Punto 3 de la minuta)
            $this->notificarSolicitudAprobacion($idPlaneacion, $userId);
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Dispara notificación de aprobación a Javier y notificación informativa a Finanzas (Punto 3 de Minuta)
     * Regla Minuta: Pasa directo de logística, no aprueba finanzas solo se notifica. La única aprobación es Javier.
     */
    private function notificarSolicitudAprobacion(int $idPlaneacion, int $userId): void
    {
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT p.id_planeacion, p.folio, p.descripcion, p.costo_total, p.created_at,
                       (SELECT COUNT(*) FROM lgs_planeaciones_envios WHERE id_planeacion = p.id_planeacion) AS total_rutas,
                       (SELECT COUNT(*) FROM lgs_envios_vins ev INNER JOIN lgs_planeaciones_envios pe ON ev.id_envio = pe.id_envio WHERE pe.id_planeacion = p.id_planeacion) AS total_vins,
                       CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS solicitante
                FROM lgs_planeaciones p
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                WHERE p.id_planeacion = ?
            ");
            $stmt->execute([$idPlaneacion]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$plan) return;

            $folio = $plan['folio'] ?? "PL-" . str_pad((string)$idPlaneacion, 4, '0', STR_PAD_LEFT);
            $descripcion = $plan['descripcion'] ?? 'Planeación de Traslados';
            $costoTotal = '$' . number_format((float)($plan['costo_total'] ?? 0), 2);
            $totalRutas = (int)($plan['total_rutas'] ?? 0);
            $totalVins = (int)($plan['total_vins'] ?? 0);
            $solicitante = trim($plan['solicitante'] ?? '') ?: 'Operador de Logística';
            $fechaEnvio = date('Y-m-d H:i');
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            // 1. Notificación a Javier Rodríguez (Aprobador Único en lgs_aprobadores)
            $aprobadores = Lgs_notificacionesHelper::getCorreosAprobadores($db);
            if (!empty($aprobadores)) {
                $asuntoAprob = "📋 SOLICITUD DE APROBACIÓN: Planeación {$folio} ({$totalVins} VINs / {$totalRutas} Rutas)";
                $htmlAprob = "
                <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #1E3A8A; color: #ffffff; padding: 22px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Solicitud de Aprobación de Planeación</h2>
                        <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                    </div>
                    <div style='padding: 24px; background-color: #ffffff;'>
                        <p style='font-size: 14px; color: #334155;'>
                            Estimado(a) Director Corporativo,<br><br>
                            El área de Logística ha consolidado la planeación de envíos <strong>{$folio}</strong> y requiere de su revisión y autorización formal para proceder con el despacho en firme.
                        </p>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Solicitante:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$solicitante}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Rutas / Envíos:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$totalRutas} trayectos consolidables</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Total Unidades:</strong></td>
                                <td style='padding: 8px 0; color: #0284C7; font-weight: bold;'>{$totalVins} VINs</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Costo Total Estimado:</strong></td>
                                <td style='padding: 8px 0; color: #16A34A; font-weight: bold;'>{$costoTotal}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$fechaEnvio}</td>
                            </tr>
                        </table>
                        <div style='text-align: center; margin-top: 24px;'>
                            <a href='{$baseUrl}/Lgs_aprobaciones' style='background-color: #1E3A8A; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                                Revisar y Autorizar en Sistema
                            </a>
                        </div>
                    </div>
                </div>";
                Lgs_notificacionesHelper::enviar($aprobadores, $asuntoAprob, $htmlAprob);
            }

            // 2. Notificación Informativa a Finanzas (Contador, Tesorero, Cobranza) — NO APRUEBA
            $finanzas = Lgs_notificacionesHelper::getCorreosFinanzas($db);
            if (!empty($finanzas)) {
                $asuntoFin = "📊 AVISO INFORMATIVO: Planeación {$folio} en Proceso de Autorización ({$totalVins} VINs)";
                $htmlFin = "
                <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #047857; color: #ffffff; padding: 22px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Aviso Informativo de Traslados de Unidades</h2>
                        <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                    </div>
                    <div style='padding: 24px; background-color: #ffffff;'>
                        <div style='background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 8px; padding: 12px; margin-bottom: 16px;'>
                            <p style='margin: 0; font-size: 13px; color: #166534;'>
                                ℹ️ <strong>Nota Importante:</strong> Esta notificación es exclusivamente <strong>informativa</strong> para la previsión contable y de flujo de tesorería. <strong>El área de Finanzas no requiere realizar ninguna acción de aprobación</strong>. El plan se encuentra en revisión con Dirección Corporativa (Javier Rodríguez Murrieta).
                            </p>
                        </div>
                        <p style='font-size: 14px; color: #334155;'>
                            Se comparte el resumen económico y volumétrico de los fletes programados por Logística:
                        </p>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Rutas / Envíos:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$totalRutas} trayectos</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Total Unidades:</strong></td>
                                <td style='padding: 8px 0; color: #047857; font-weight: bold;'>{$totalVins} VINs</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Costo Total Estimado:</strong></td>
                                <td style='padding: 8px 0; color: #16A34A; font-weight: bold;'>{$costoTotal}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$fechaEnvio}</td>
                            </tr>
                        </table>
                    </div>
                </div>";
                Lgs_notificacionesHelper::enviar($finanzas, $asuntoFin, $htmlFin);
            }
        } catch (Throwable $e) {
            error_log("Error al notificar solicitud de aprobación: " . $e->getMessage());
        }
    }
}
