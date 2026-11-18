<?php

require_once('Services/Lgs_notificacionesHelper.php');

class Lgs_aprobacionesService {

    private Lgs_aprobacionesModel $model;

    public function __construct() {
        $this->model = new Lgs_aprobacionesModel();
    }

    public function getAllParaAprobacion(): array {
        return $this->model->getPlaneacionesAprobacion();
    }

    public function getDetallePlan(int $idPlaneacion): array {
        require_once('Services/Lgs_planeacionesService.php');
        $planService = new Lgs_planeacionesService();
        $planCompleto = $planService->getDetalleCompletoPlan($idPlaneacion);
        return $planCompleto['envios'] ?? [];
    }

    /**
     * Valida si el usuario cuenta con el permiso desacoplado para autorizar planeaciones
     * Regla: 1 solo nivel (Solicitante -> Aprobador), validado por PERMISO (no casado con persona o puesto)
     */
    public function tienePermisoAprobacion(int $userId): bool {
        $userRole = (int)($_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0));
        if (in_array($userRole, [1, 56, 65, 70], true)) return true; // Administrador, Director Corporativo, Gerente/Admin Logística

        // 1. Revisar permisos en sesión
        if (!empty($_SESSION['permisos'][157]['u']) || !empty($_SESSION['permisos'][157]['w'])) {
            return true;
        }

        // 2. Revisar permisos en BD (rol o usuario específico en tabla de aprobadores)
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT COUNT(*) 
                FROM usuarios u
                LEFT JOIN permisos p ON p.rolid = u.rolid AND p.moduloid = 157 AND (p.u = 1 OR p.w = 1)
                LEFT JOIN lgs_aprobadores ap ON ap.id_usuario = u.idusuario AND ap.activo = 1
                WHERE u.idusuario = ? AND (u.rolid IN (1, 56, 65, 70) OR u.idusuario = 72 OR p.idpermiso IS NOT NULL OR ap.id IS NOT NULL)
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Procesa la decisión del aprobador: Aprobar, Rechazar, Aprobar Reapertura o Denegar Reapertura
     */
    public function resolverPlaneacion(int $idPlaneacion, string $decision, string $observaciones, int $userId): void {
        // Validación de permiso de aprobación
        if (!$this->tienePermisoAprobacion($userId)) {
            throw new Exception("Acceso Denegado: No cuenta con el permiso requerido para autorizar o rechazar planeaciones.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            // Segregación de funciones: El solicitante (creador) no puede auto-aprobar su planeación a menos que cuente con rol autorizador [1, 56, 65, 70]
            $userRole = (int)($_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0));
            if ($userRole === 0 && $userId > 0) {
                $stmtUser = $db->prepare("SELECT rolid FROM usuarios WHERE idusuario = ?");
                $stmtUser->execute([$userId]);
                $userRole = (int)$stmtUser->fetchColumn();
            }

            $stmtPlan = $db->prepare("SELECT created_by, folio, id_estado FROM lgs_planeaciones WHERE id_planeacion = ?");
            $stmtPlan->execute([$idPlaneacion]);
            $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
            if ($plan && (int)$plan['created_by'] === $userId && !in_array($userRole, [1, 56, 65, 70], true)) {
                throw new Exception("Segregación de funciones: El usuario solicitante que creó la planeación no puede autorizarse a sí mismo.");
            }

            $estadoActual = (int)($plan['id_estado'] ?? 0);
            $obsGuardar = $observaciones;
            $tipoNotificacion = 'normal';

            if ($decision === 'aprobar_reapertura' || ($decision === 'aprobar' && $estadoActual === 6)) {
                $estadoPlan = 1; // Borrador editable
                $estadoEnvio = 1; // Envío editable
                $obsGuardar = "[Reapertura Aprobada]: " . (trim($observaciones) !== '' ? trim($observaciones) : 'Autorizada para edición');
                $tipoNotificacion = 'reapertura_aprobada';
            } elseif ($decision === 'denegar_reapertura' || ($decision === 'rechazar' && $estadoActual === 6)) {
                $estadoPlan = 5; // Se mantiene Aprobada
                $estadoEnvio = 3; // Envío Aprobado
                $obsGuardar = "[Reapertura Denegada]: " . (trim($observaciones) !== '' ? trim($observaciones) : 'No autorizada');
                $tipoNotificacion = 'reapertura_denegada';
            } elseif ($decision === 'aprobar') {
                $estadoPlan = 5; // Planeación Aprobada
                $estadoEnvio = 3; // Envío Aprobado (Listo para despachar)
            } elseif ($decision === 'rechazar') {
                $estadoPlan = 3; // Planeación Regresada
                $estadoEnvio = 4; // Envío Regresado (El operador debe replanearlo)
            } else {
                throw new Exception("Decisión no válida.");
            }

            // 1. Actualizamos la cabecera de la Planeación
            $this->model->updateEstadoPlaneacion($db, $idPlaneacion, $estadoPlan, $obsGuardar, $userId);
            
            // 2. Liberamos o bloqueamos los envíos que estaban adentro
            $this->model->updateEstadoEnviosMasivo($db, $idPlaneacion, $estadoEnvio);
            
            $db->commit();

            // Disparar notificaciones post-resolución
            if ($tipoNotificacion === 'reapertura_aprobada') {
                $this->notificarResolucionReapertura($idPlaneacion, true, $observaciones, $userId);
            } elseif ($tipoNotificacion === 'reapertura_denegada') {
                $this->notificarResolucionReapertura($idPlaneacion, false, $observaciones, $userId);
            } elseif ($decision === 'aprobar') {
                $this->notificarResolucionAprobada($idPlaneacion, $observaciones, $userId);
            } else {
                $this->notificarResolucionRechazada($idPlaneacion, $observaciones, $userId);
            }
        } catch (Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Notifica la aprobación a Logística (para despachar) y a Finanzas (informativo de gasto comprometido)
     */
    private function notificarResolucionAprobada(int $idPlaneacion, string $observaciones, int $aprobadorId): void
    {
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT p.id_planeacion, p.folio, p.descripcion, p.costo_total,
                       (SELECT COUNT(*) FROM lgs_planeaciones_envios WHERE id_planeacion = p.id_planeacion) AS total_rutas,
                       (SELECT COUNT(*) FROM lgs_envios_vins ev INNER JOIN lgs_planeaciones_envios pe ON ev.id_envio = pe.id_envio WHERE pe.id_planeacion = p.id_planeacion) AS total_vins,
                       u.email_user AS creador_email,
                       CONCAT(COALESCE(u.nombres, ''), ' ', COALESCE(u.apellidos, '')) AS solicitante,
                       CONCAT(COALESCE(ap.nombres, ''), ' ', COALESCE(ap.apellidos, '')) AS aprobador_nombre
                FROM lgs_planeaciones p
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                LEFT JOIN usuarios ap ON ap.idusuario = ?
                WHERE p.id_planeacion = ?
            ");
            $stmt->execute([$aprobadorId, $idPlaneacion]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) return;

            $folio = $plan['folio'] ?? "PL-" . str_pad((string)$idPlaneacion, 4, '0', STR_PAD_LEFT);
            $descripcion = $plan['descripcion'] ?? 'Planeación de Traslados';
            $costoTotal = '$' . number_format((float)($plan['costo_total'] ?? 0), 2);
            $totalRutas = (int)($plan['total_rutas'] ?? 0);
            $totalVins = (int)($plan['total_vins'] ?? 0);
            $aprobador = trim($plan['aprobador_nombre'] ?? '') ?: 'Dirección Corporativa';
            $obsTexto = trim($observaciones) ?: 'Sin observaciones adicionales.';
            $fechaAprob = date('Y-m-d H:i');
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            // A. Notificación al equipo de Logística
            $destLogistica = Lgs_notificacionesHelper::getCorreosLogistica($db);
            if (!empty($plan['creador_email']) && filter_var($plan['creador_email'], FILTER_VALIDATE_EMAIL)) {
                $destLogistica[] = $plan['creador_email'];
            }

            $asuntoLog = "✅ PLANEACIÓN APROBADA: {$folio} Lista para Mesa de Despacho ({$totalVins} VINs)";
            $htmlLog = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #16A34A; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Planeación Autorizada</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        La planeación de envíos <strong>{$folio}</strong> ha sido <strong>APROBADA</strong> por <strong>{$aprobador}</strong>. Los envíos han pasado automáticamente a estado <strong>CONFIRMADO</strong> y están listos para despacho en patio.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Autorizado por:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$aprobador}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Unidades / Rutas:</strong></td>
                            <td style='padding: 8px 0; color: #16A34A; font-weight: bold;'>{$totalVins} VINs en {$totalRutas} trayectos</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Monto Autorizado:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A; font-weight: bold;'>{$costoTotal}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Observaciones:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$obsTexto}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$fechaAprob}</td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_ejecucion' style='background-color: #16A34A; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Ir a Mesa de Despacho
                        </a>
                    </div>
                </div>
            </div>";
            Lgs_notificacionesHelper::enviar($destLogistica, $asuntoLog, $htmlLog);

            // B. Notificación Informativa a Finanzas (Contador, Tesorero, Cobranza)
            $destFinanzas = Lgs_notificacionesHelper::getCorreosFinanzas($db);
            if (!empty($destFinanzas)) {
                $asuntoFin = "💰 AVISO FINANZAS: Gasto de Fletes Aprobado para Planeación {$folio} ({$costoTotal})";
                $htmlFin = "
                <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                    <div style='background-color: #047857; color: #ffffff; padding: 22px; text-align: center;'>
                        <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Aviso de Gasto Logístico Comprometido</h2>
                        <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                    </div>
                    <div style='padding: 24px; background-color: #ffffff;'>
                        <p style='font-size: 14px; color: #334155;'>
                            Se informa a las áreas de Tesorería, Contabilidad y Cobranza que la planeación <strong>{$folio}</strong> ha sido <strong>AUTORIZADA</strong> por Dirección Corporativa ({$aprobador}). El importe correspondiente ha quedado formalmente programado para ejecución logística.
                        </p>
                        <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Importe Total:</strong></td>
                                <td style='padding: 8px 0; color: #047857; font-weight: bold; font-size: 15px;'>{$costoTotal}</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Volumen:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$totalVins} VINs en {$totalRutas} trayectos</td>
                            </tr>
                            <tr style='border-bottom: 1px solid #F1F5F9;'>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Autorizado por:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$aprobador}</td>
                            </tr>
                            <tr>
                                <td style='padding: 8px 0; color: #64748B;'><strong>Fecha de Autorización:</strong></td>
                                <td style='padding: 8px 0; color: #0F172A;'>{$fechaAprob}</td>
                            </tr>
                        </table>
                    </div>
                </div>";
                Lgs_notificacionesHelper::enviar($destFinanzas, $asuntoFin, $htmlFin);
            }
        } catch (Throwable $e) {
            error_log("Error al notificar resolución aprobada: " . $e->getMessage());
        }
    }

    /**
     * Notifica el rechazo al equipo de Logística para que realice las correcciones pertinentes
     */
    private function notificarResolucionRechazada(int $idPlaneacion, string $observaciones, int $aprobadorId): void
    {
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT p.id_planeacion, p.folio, p.descripcion,
                       u.email_user AS creador_email,
                       CONCAT(COALESCE(ap.nombres, ''), ' ', COALESCE(ap.apellidos, '')) AS aprobador_nombre
                FROM lgs_planeaciones p
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                LEFT JOIN usuarios ap ON ap.idusuario = ?
                WHERE p.id_planeacion = ?
            ");
            $stmt->execute([$aprobadorId, $idPlaneacion]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) return;

            $folio = $plan['folio'] ?? "PL-" . str_pad((string)$idPlaneacion, 4, '0', STR_PAD_LEFT);
            $descripcion = $plan['descripcion'] ?? 'Planeación de Traslados';
            $aprobador = trim($plan['aprobador_nombre'] ?? '') ?: 'Dirección Corporativa';
            $obsTexto = trim($observaciones) ?: 'Sin comentarios adicionales.';
            $fechaRechazo = date('Y-m-d H:i');
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $destLogistica = Lgs_notificacionesHelper::getCorreosLogistica($db);
            if (!empty($plan['creador_email']) && filter_var($plan['creador_email'], FILTER_VALIDATE_EMAIL)) {
                $destLogistica[] = $plan['creador_email'];
            }

            $asunto = "❌ PLANEACIÓN REGRESADA: {$folio} Requiere Ajustes";
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: #DC2626; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>Planeación Regresada para Corrección</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        La planeación de envíos <strong>{$folio}</strong> ha sido <strong>RECHAZADA / REGRESADA</strong> por <strong>{$aprobador}</strong>. Los envíos han retornado a estado editable para permitir su replanificación y solventar las observaciones señaladas.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Revisado por:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$aprobador}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Motivo / Observaciones:</strong></td>
                            <td style='padding: 8px 0; color: #DC2626; font-weight: bold;'>{$obsTexto}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$fechaRechazo}</td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_planeaciones' style='background-color: #DC2626; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Replanear en Sistema
                        </a>
                    </div>
                </div>
            </div>";
            Lgs_notificacionesHelper::enviar($destLogistica, $asunto, $html);
        } catch (Throwable $e) {
            error_log("Error al notificar resolución rechazada: " . $e->getMessage());
        }
    }

    /**
     * Notifica al equipo de Logística el dictamen de una solicitud de reapertura (Aprobada o Denegada)
     */
    private function notificarResolucionReapertura(int $idPlaneacion, bool $aprobada, string $observaciones, int $aprobadorId): void
    {
        try {
            $db = $this->model->getConexion();
            $stmt = $db->prepare("
                SELECT p.id_planeacion, p.folio, p.descripcion,
                       u.email_user AS creador_email,
                       CONCAT(COALESCE(ap.nombres, ''), ' ', COALESCE(ap.apellidos, '')) AS aprobador_nombre
                FROM lgs_planeaciones p
                LEFT JOIN usuarios u ON p.created_by = u.idusuario
                LEFT JOIN usuarios ap ON ap.idusuario = ?
                WHERE p.id_planeacion = ?
            ");
            $stmt->execute([$aprobadorId, $idPlaneacion]);
            $plan = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$plan) return;

            $folio = $plan['folio'] ?? "PL-" . str_pad((string)$idPlaneacion, 4, '0', STR_PAD_LEFT);
            $descripcion = $plan['descripcion'] ?? 'Planeación de Traslados';
            $aprobador = trim($plan['aprobador_nombre'] ?? '') ?: 'Dirección Corporativa';
            $obsTexto = trim($observaciones) ?: ($aprobada ? 'Autorizada para edición.' : 'Solicitud denegada.');
            $fechaRes = date('Y-m-d H:i');
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $destLogistica = Lgs_notificacionesHelper::getCorreosLogistica($db);
            if (!empty($plan['creador_email']) && filter_var($plan['creador_email'], FILTER_VALIDATE_EMAIL)) {
                $destLogistica[] = $plan['creador_email'];
            }

            $color = $aprobada ? '#16A34A' : '#DC2626';
            $titulo = $aprobada ? 'Reapertura de Planeación APROBADA' : 'Reapertura de Planeación DENEGADA';
            $estadoLabel = $aprobada ? 'APROBADA (Desbloqueada en Borrador)' : 'DENEGADA (Se mantiene Aprobada)';
            $asunto = ($aprobada ? '🔓 REAPERTURA APROBADA' : '🔒 REAPERTURA DENEGADA') . ": Planeación {$folio}";

            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: {$color}; color: #ffffff; padding: 22px; text-align: center;'>
                    <h2 style='margin: 0; font-size: 19px; text-transform: uppercase;'>{$titulo}</h2>
                    <p style='margin: 6px 0 0 0; font-size: 13px; opacity: 0.9;'>Folio: <strong>{$folio}</strong></p>
                </div>
                <div style='padding: 24px; background-color: #ffffff;'>
                    <p style='font-size: 14px; color: #334155;'>
                        La solicitud de reapertura de la planeación <strong>{$folio}</strong> fue <strong>{$estadoLabel}</strong> por <strong>{$aprobador}</strong>.
                    </p>
                    <table style='width: 100%; border-collapse: collapse; font-size: 13px; margin: 16px 0;'>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B; width: 35%;'><strong>Planeación:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'><strong>{$folio}</strong> - {$descripcion}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Dictaminado por:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$aprobador}</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #F1F5F9;'>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Observaciones:</strong></td>
                            <td style='padding: 8px 0; color: {$color}; font-weight: bold;'>{$obsTexto}</td>
                        </tr>
                        <tr>
                            <td style='padding: 8px 0; color: #64748B;'><strong>Fecha / Hora:</strong></td>
                            <td style='padding: 8px 0; color: #0F172A;'>{$fechaRes}</td>
                        </tr>
                    </table>
                    <div style='text-align: center; margin-top: 24px;'>
                        <a href='{$baseUrl}/Lgs_planeaciones' style='background-color: {$color}; color: #ffffff; text-decoration: none; padding: 12px 28px; border-radius: 20px; font-weight: bold; font-size: 13px; display: inline-block;'>
                            Ir a Mis Planeaciones
                        </a>
                    </div>
                </div>
            </div>";
            Lgs_notificacionesHelper::enviar($destLogistica, $asunto, $html);
        } catch (Throwable $e) {
            error_log("Error al notificar resolución de reapertura: " . $e->getMessage());
        }
    }

    public function getSolicitudesPreEnvio(): array
    {
        return $this->model->getSolicitudesPreEnvioAprobacion();
    }

    public function getDetalleSolicitudPreEnvio(int $idSolicitud): array
    {
        return $this->model->getDetalleSolicitudPreEnvio($idSolicitud);
    }

    /**
     * Aprueba o rechaza una solicitud excepcional de paso a Envío Oficial (PE- -> EN- o EN- borrador).
     * Al aprobar un Pre-envío, lo convierte inmediatamente a Envío Oficial (EN-).
     */
    public function resolverSolicitudPreEnvio(int $idSolicitud, string $decision, string $observaciones, int $userId): array
    {
        if (!$this->tienePermisoAprobacion($userId)) {
            throw new Exception("Acceso Denegado: No cuenta con permiso requerido para autorizar o rechazar solicitudes.");
        }

        $decision = strtolower(trim($decision));
        if (!in_array($decision, ['aprobar', 'rechazar'], true)) {
            throw new Exception("Decisión no válida.");
        }

        $obsTrim = trim($observaciones);
        if ($decision === 'rechazar' && $obsTrim === '') {
            throw new Exception("Para rechazar la solicitud es obligatorio ingresar el motivo o comentario.");
        }

        require_once 'Models/Lgs_enviosModel.php';
        $envModel = new Lgs_enviosModel();
        $db = $this->model->getConexion();
        $envModel->ensureSolicitudesPreEnvioTable($db);

        $stmtSol = $db->prepare("SELECT * FROM lgs_solicitudes_preenvio_envio WHERE id_solicitud = ? LIMIT 1");
        $stmtSol->execute([$idSolicitud]);
        $sol = $stmtSol->fetch(PDO::FETCH_ASSOC);
        if (!$sol) {
            throw new Exception("La solicitud especificada no existe.");
        }
        if (($sol['estatus'] ?? '') !== 'PENDIENTE') {
            throw new Exception("Esta solicitud ya fue dictaminada con estatus: {$sol['estatus']}.");
        }

        $userRole = (int)($_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0));
        if ($userRole === 0 && $userId > 0) {
            $stR = $db->prepare("SELECT rolid FROM usuarios WHERE idusuario = ?");
            $stR->execute([$userId]);
            $userRole = (int)$stR->fetchColumn();
        }
        if (intval($sol['solicitado_by']) === $userId && !in_array($userRole, [1, 56, 65, 70], true)) {
            throw new Exception("Segregación de funciones: El usuario que envió la solicitud no puede auto-autorizarla.");
        }

        $idEnvio = intval($sol['id_envio']);
        $folioGenerado = null;

        if ($decision === 'aprobar') {
            $cab = $envModel->getEnvioCabecera($idEnvio);
            if (empty($cab)) {
                throw new Exception("El envío/pre-envío asociado ya no existe.");
            }
            $esPre = (intval($cab['es_preenvio'] ?? 0) === 1 || strpos((string)($cab['folio'] ?? ''), 'PE-') === 0);
            if ($esPre) {
                $conv = $envModel->convertirPreEnvioAEnvioOficial($idEnvio, $userId, true);
                $folioGenerado = (string)($conv['folio'] ?? '');
            } else {
                // Envío oficial que estaba en borrador esperando autorización excepcional
                $val = $envModel->validarRequisitosConversionEnvio($idEnvio);
                $folioGenerado = (string)($cab['folio'] ?? '');
                $db->beginTransaction();
                try {
                    if (!empty($val['draft_conflicts'])) {
                        $stmtDelDraft = $db->prepare("DELETE FROM lgs_envios_vins WHERE id_envio = ? AND id_unidad = ?");
                        foreach ($val['draft_conflicts'] as $dc) {
                            $stmtDelDraft->execute([$dc['id_envio'], $dc['id_unidad']]);
                        }
                    }
                    $db->prepare("UPDATE lgs_envios SET id_estado = 8, updated_by = ?, updated_at = NOW() WHERE id_envio = ?")
                       ->execute([$userId, $idEnvio]);
                    $db->commit();
                } catch (Throwable $eTx) {
                    if ($db->inTransaction()) $db->rollBack();
                    throw $eTx;
                }
            }

            $db->prepare("
                UPDATE lgs_solicitudes_preenvio_envio
                SET estatus = 'APROBADA',
                    folio_envio_generado = ?,
                    resuelto_by = ?,
                    resuelto_at = NOW(),
                    obs_aprobador = ?
                WHERE id_solicitud = ?
            ")->execute([
                $folioGenerado,
                $userId,
                $obsTrim !== '' ? $obsTrim : 'Autorizado excepcionalmente para envío.',
                $idSolicitud,
            ]);
        } else {
            $db->prepare("
                UPDATE lgs_solicitudes_preenvio_envio
                SET estatus = 'RECHAZADA',
                    resuelto_by = ?,
                    resuelto_at = NOW(),
                    obs_aprobador = ?
                WHERE id_solicitud = ?
            ")->execute([$userId, $obsTrim, $idSolicitud]);
        }

        $this->notificarResolucionSolicitudPreEnvio($idSolicitud, $decision === 'aprobar', $folioGenerado, $obsTrim, $userId);

        return [
            'id_solicitud'         => $idSolicitud,
            'folio_solicitud'      => (string)$sol['folio_solicitud'],
            'estatus'              => $decision === 'aprobar' ? 'APROBADA' : 'RECHAZADA',
            'id_envio'             => $idEnvio,
            'folio_preenvio'       => (string)$sol['folio_preenvio'],
            'folio_envio_generado' => $folioGenerado,
        ];
    }

    private function notificarResolucionSolicitudPreEnvio(int $idSolicitud, bool $aprobada, ?string $folioGenerado, string $obs, int $aprobadorId): void
    {
        try {
            $db = $this->model->getConexion();
            $st = $db->prepare("
                SELECT s.*, us.email_user AS solicitante_email,
                       TRIM(CONCAT(COALESCE(ap.nombres,''), ' ', COALESCE(ap.apellidos,''))) AS aprobador_nombre
                FROM lgs_solicitudes_preenvio_envio s
                LEFT JOIN usuarios us ON us.idusuario = s.solicitado_by
                LEFT JOIN usuarios ap ON ap.idusuario = ?
                WHERE s.id_solicitud = ?
                LIMIT 1
            ");
            $st->execute([$aprobadorId, $idSolicitud]);
            $sol = $st->fetch(PDO::FETCH_ASSOC);
            if (!$sol) return;

            $dest = Lgs_notificacionesHelper::getCorreosLogistica($db);
            if (!empty($sol['solicitante_email']) && filter_var($sol['solicitante_email'], FILTER_VALIDATE_EMAIL)) {
                $dest[] = $sol['solicitante_email'];
            }
            $dest = array_unique($dest);
            if (empty($dest)) return;

            $color   = $aprobada ? '#16A34A' : '#DC2626';
            $titulo  = $aprobada ? 'Excepción APROBADA · Paso a Envío Oficial' : 'Excepción RECHAZADA';
            $folSol  = htmlspecialchars((string)$sol['folio_solicitud'], ENT_QUOTES, 'UTF-8');
            $folPre  = htmlspecialchars((string)$sol['folio_preenvio'], ENT_QUOTES, 'UTF-8');
            $folGen  = htmlspecialchars((string)($folioGenerado ?: 'N/A'), ENT_QUOTES, 'UTF-8');
            $apNom   = htmlspecialchars(trim((string)($sol['aprobador_nombre'] ?? '')) ?: 'Aprobador', ENT_QUOTES, 'UTF-8');
            $obsEsc  = htmlspecialchars($obs !== '' ? $obs : 'Sin observaciones.', ENT_QUOTES, 'UTF-8');
            $baseUrl = defined('BASE_URL') ? rtrim(BASE_URL, '/') : '';

            $asunto = ($aprobada ? '✅ EXCEPCIÓN APROBADA: ' : '❌ EXCEPCIÓN RECHAZADA: ') . "{$folSol} ({$folPre})";
            $html = "
            <div style='font-family: Arial, sans-serif; max-width: 650px; margin: auto; border: 1px solid #E2E8F0; border-radius: 12px; overflow: hidden;'>
                <div style='background-color: {$color}; color: #fff; padding: 20px; text-align: center;'>
                    <h2 style='margin:0; font-size:18px;'>{$titulo}</h2>
                    <p style='margin:5px 0 0 0; font-size:13px;'>Solicitud {$folSol} · Pre-envío {$folPre}</p>
                </div>
                <div style='padding: 22px; background-color: #fff;'>
                    <p style='font-size:14px; color:#334155;'>Dictamen registrado por <strong>{$apNom}</strong>.</p>
                    " . ($aprobada ? "<p style='font-size:14px; color:#16A34A;'><strong>Folio Oficial Generado:</strong> {$folGen}</p>" : "") . "
                    <p style='font-size:13px; color:#0F172A;'><strong>Observaciones:</strong> {$obsEsc}</p>
                    <div style='text-align:center; margin-top:20px;'>
                        <a href='{$baseUrl}/" . ($aprobada ? 'Lgs_envios' : 'Lgs_bandeja?tab=preenvios') . "' style='background-color:{$color}; color:#fff; text-decoration:none; padding:10px 24px; border-radius:20px; font-weight:bold; font-size:13px;'>" . ($aprobada ? 'Ir a Envíos' : 'Ir a Pre-envíos') . "</a>
                    </div>
                </div>
            </div>";
            Lgs_notificacionesHelper::enviar($dest, $asunto, $html);
        } catch (Throwable $e) {
            error_log("Error notificación resolución solicitud pre-envío: " . $e->getMessage());
        }
    }
}
