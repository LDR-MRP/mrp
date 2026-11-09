<?php

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
        if ($userRole === 1 || $userRole === 70) return true; // Administrador del sistema o Admin Logística

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
                WHERE u.idusuario = ? AND (u.rolid = 1 OR p.idpermiso IS NOT NULL OR ap.id IS NOT NULL)
            ");
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * Procesa la decisión del aprobador: Aprobar o Rechazar (1 solo nivel: Solicitante -> Aprobador)
     */
    public function resolverPlaneacion(int $idPlaneacion, string $decision, string $observaciones, int $userId): void {
        // Validación de permiso de aprobación
        if (!$this->tienePermisoAprobacion($userId)) {
            throw new Exception("Acceso Denegado: No cuenta con el permiso requerido para autorizar o rechazar planeaciones.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();

            // Segregación de funciones: El solicitante (creador) no puede auto-aprobar su planeación a menos que sea Administrador (rol 1 o 70)
            $userRole = (int)($_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0));
            if ($userRole === 0 && $userId > 0) {
                $stmtUser = $db->prepare("SELECT rolid FROM usuarios WHERE idusuario = ?");
                $stmtUser->execute([$userId]);
                $userRole = (int)$stmtUser->fetchColumn();
            }

            $stmtPlan = $db->prepare("SELECT created_by, folio FROM lgs_planeaciones WHERE id_planeacion = ?");
            $stmtPlan->execute([$idPlaneacion]);
            $plan = $stmtPlan->fetch(PDO::FETCH_ASSOC);
            if ($plan && (int)$plan['created_by'] === $userId && !in_array($userRole, [1, 70])) {
                throw new Exception("Segregación de funciones: El usuario solicitante que creó la planeación no puede autorizarse a sí mismo.");
            }
            
            if ($decision === 'aprobar') {
                $estadoPlan = 5; // Planeación Aprobada
                $estadoEnvio = 3; // Envío Aprobado (Listo para despachar)
            } else if ($decision === 'rechazar') {
                $estadoPlan = 3; // Planeación Regresada
                $estadoEnvio = 4; // Envío Regresado (El operador debe replanearlo)
            } else {
                throw new Exception("Decisión no válida.");
            }

            // 1. Actualizamos la cabecera de la Planeación
            $this->model->updateEstadoPlaneacion($db, $idPlaneacion, $estadoPlan, $observaciones, $userId);
            
            // 2. Liberamos o bloqueamos los envíos que estaban adentro
            $this->model->updateEstadoEnviosMasivo($db, $idPlaneacion, $estadoEnvio);
            
            // Opcional: Aquí se podría integrar el servicio de correos para notificar al operador.
            // NotificationService::notifyOperadorLogistica($idPlaneacion, $decision, $observaciones);
            
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
