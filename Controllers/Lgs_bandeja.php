<?php

class Lgs_bandeja extends Controllers {

    use ApiResponser;

    private Lgs_bandejaService $service;

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        $this->service = new Lgs_bandejaService();
    }

    // ─── Vista principal de la bandeja ────────────────────────────────────────

    public function Lgs_bandeja(): void {
        $data['page_tag']          = "Bandeja - Logística";
        $data['page_title']        = "Bandeja de Logística";
        $data['page_name']         = "lgs_bandeja";
        $data['page_functions_js'] = "functions_lgs_bandeja.js";
        $data['motivos']           = $this->service->getMotivos();
        $data['destinos']          = $this->service->getDestinos();
        $data['distribuidores']    = $this->service->getListaDistribuidores();

        $this->views->getView($this, "../Lgs_bandeja/index", $data);
    }

    // ─── DataTable: listado de unidades en bandeja ────────────────────────────

    public function getBandeja(): void {
        try {
            $filtros = [
                'id_estado_proceso'  => $_GET['estado']              ?? '',
                'id_destino'         => $_GET['destino']             ?? '',
                'id_motivo'          => $_GET['motivo']              ?? '',
                'activado_comercial' => $_GET['activado_comercial']  ?? '',
                'estatus_finanzas'   => $_GET['estatus_finanzas']    ?? '',
                'destino_confirmado' => $_GET['destino_confirmado']  ?? '',
                'busqueda'           => $_GET['busqueda']            ?? '',
            ];
            $arrData = $this->service->getBandeja($filtros);

            foreach ($arrData as &$row) {
                $row['options'] = $this->_buildRowOptions($row);
            }
            unset($row);

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    // ─── Detalle de una unidad ────────────────────────────────────────────────

    public function getUnidad(int $id): void {
        try {
            $data = $this->service->getDetalle($id);
            $this->successResponse($data);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 404);
        }
    }

    // ─── Asignar Destino y Motivo (flujo global) ──────────────────────────────

    public function asignarDestino(): void {
        try {
            $userId       = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad  = intval($_POST['id_lgs_unidad'] ?? 0);
            $idMotivo     = intval($_POST['id_motivo']     ?? 0);
            $idDestino    = intval($_POST['id_destino']    ?? 0);
            $destDesc     = trim($_POST['destino_descripcion'] ?? '');

            if ($idLgsUnidad <= 0 || $idMotivo <= 0 || $idDestino <= 0) {
                $this->errorResponse("Datos incompletos: se requiere unidad, motivo y destino.", 422);
                return;
            }

            $this->service->asignarDestinoMotivo($idLgsUnidad, [
                'id_motivo'           => $idMotivo,
                'id_destino'          => $idDestino,
                'destino_descripcion' => $destDesc,
            ], $userId);

            $this->successResponse(null, "Destino y motivo asignados correctamente.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    // ─── Registrar fechas de salida / llegada ─────────────────────────────────

    public function registrarFechas(): void {
        try {
            $userId      = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad = intval($_POST['id_lgs_unidad']  ?? 0);
            $fechaSalida = trim($_POST['fecha_salida']      ?? '') ?: null;
            $fechaLleg   = trim($_POST['fecha_llegada']     ?? '') ?: null;

            if ($idLgsUnidad <= 0) {
                $this->errorResponse("ID de unidad logística requerido.", 422);
                return;
            }

            $this->service->registrarFechas($idLgsUnidad, $fechaSalida, $fechaLleg, $userId);
            $this->successResponse(null, "Fechas registradas correctamente.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    // ─── Siguiente Área (finalizar traslado) ──────────────────────────────────

    public function siguienteArea(): void {
        try {
            $userId      = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad = intval($_POST['id_lgs_unidad'] ?? 0);

            if ($idLgsUnidad <= 0) {
                $this->errorResponse("ID de unidad logística requerido.", 422);
                return;
            }

            $this->service->finalizarUnidad($idLgsUnidad, $userId);
            $this->successResponse(null, "Unidad marcada como Entregada. Traslado finalizado.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 400);
        }
    }

    // ─── Entrega Interna ──────────────────────────────────────────────────────

    public function solicitarEntrega(): void {
        try {
            $userId   = $_SESSION['idUser'] ?? 1;
            $idUnidad = intval($_POST['id_unidad']      ?? 0);
            $obs      = trim($_POST['observaciones']    ?? '') ?: null;

            if ($idUnidad <= 0) {
                $this->errorResponse("ID de unidad requerido.", 422);
                return;
            }

            $id = $this->service->solicitarEntregaInterna($idUnidad, $obs, $userId);
            $this->successResponse(['id_entrega_interna' => $id], "Entrega interna solicitada.", 201);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function confirmarEntrega(int $id): void {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->confirmarEntregaInterna($id, $userId);
            $this->successResponse(null, "Entrega interna confirmada.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function cancelarEntrega(int $id): void {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->cancelarEntregaInterna($id, $userId);
            $this->successResponse(null, "Solicitud de entrega interna cancelada.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    // ─── Catálogos (para selects dinámicos) ──────────────────────────────────

    public function getMotivos(): void {
        try {
            $this->successResponse($this->service->getMotivos());
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getDestinos(): void {
        try {
            $this->successResponse($this->service->getDestinos());
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    // ─── Puntos 5, 8 y 9: Métodos de Confirmación y Activación ───────────────

    /**
     * Confirma el destino que viene de Pedidos con un clic (Punto 5)
     */
    public function confirmarDestinoPedido(): void {
        try {
            $userId      = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad = intval($_POST['id_lgs_unidad'] ?? 0);

            if ($idLgsUnidad <= 0) {
                $this->errorResponse("ID de unidad logística requerido.", 422);
                return;
            }

            $this->service->confirmarDestinoDesdePedido($idLgsUnidad, $userId);
            $this->successResponse(null, "Destino originado en pedidos confirmado exitosamente.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    /**
     * Ajusta el destino confirmado si Adriana requiere especificar ubicación logística (Punto 5)
     */
    public function ajustarDestinoLogistica(): void {
        try {
            $userId            = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad       = intval($_POST['id_lgs_unidad'] ?? 0);
            $destinoConfirmado = trim($_POST['destino_confirmado'] ?? '');

            if ($idLgsUnidad <= 0 || empty($destinoConfirmado)) {
                $this->errorResponse("Datos incompletos: se requiere ID de unidad y destino confirmado.", 422);
                return;
            }

            $this->service->ajustarDestino($idLgsUnidad, $destinoConfirmado, $userId);
            $this->successResponse(null, "Destino logístico confirmado y actualizado.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    /**
     * Conmuta la activación comercial (Punto 9: 1 = Listo Despacho, 0 = Solo Preplaneación)
     */
    public function setActivacionComercial(): void {
        try {
            $userId      = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad = intval($_POST['id_lgs_unidad'] ?? 0);
            $activado    = intval($_POST['activado'] ?? 0);

            if ($idLgsUnidad <= 0) {
                $this->errorResponse("ID de unidad logística requerido.", 422);
                return;
            }

            $this->service->toggleActivacionComercial($idLgsUnidad, $activado, $userId);
            $msg = ($activado === 1) ? "Unidad activada por Comercial para despacho inmediato." : "Unidad marcada para preplaneación únicamente.";
            $this->successResponse(null, $msg);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    /**
     * Actualiza el estado de finanzas (Punto 8)
     */
    public function setEstatusFinanzas(): void {
        try {
            $userId      = $_SESSION['idUser'] ?? 1;
            $idLgsUnidad = intval($_POST['id_lgs_unidad'] ?? 0);
            $estatus     = trim($_POST['estatus_finanzas'] ?? '');

            if ($idLgsUnidad <= 0 || empty($estatus)) {
                $this->errorResponse("Datos incompletos.", 422);
                return;
            }

            $this->service->actualizarEstatusFinanzas($idLgsUnidad, $estatus, $userId);
            $this->successResponse(null, "Estatus financiero actualizado a '{$estatus}'.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    // ─── Privado: construir botones de acción por fila ────────────────────────

    private function _buildRowOptions(array $row): string {
        $id          = intval($row['id_lgs_unidad']);
        $estado      = intval($row['id_estado_proceso']);
        $activadoCom = intval($row['activado_comercial'] ?? 0);
        $destConf    = intval($row['destino_confirmado'] ?? 0);
        $destPed     = htmlspecialchars($row['destino_pedido'] ?? '', ENT_QUOTES, 'UTF-8');

        $btnDetalle = '<button class="btn btn-sm btn-soft-info me-1" onclick="fntVerUnidad(' . $id . ')" title="Ver detalle"><i class="ri-eye-line"></i></button>';

        // Botón Confirmar Destino de Pedidos (Punto 5)
        $btnConfirmDest = '';
        if ($destConf === 0 && !empty($row['destino_pedido']) && $row['destino_pedido'] !== 'Por Definir') {
            $btnConfirmDest = '<button class="btn btn-sm btn-soft-success me-1" onclick="fntConfirmarDestinoPedido(' . $id . ', \'' . addslashes($destPed) . '\')" title="Confirmar Destino de Pedido"><i class="ri-check-double-line"></i></button>';
        }

        $btnDestino = '<button class="btn btn-sm btn-soft-primary me-1" onclick="fntAsignarDestino(' . $id . ')" title="Ajustar / Asignar Destino"><i class="ri-map-pin-2-line"></i></button>';

        // Botón Conmutar Activación Comercial (Punto 9)
        $iconAct = ($activadoCom === 1) ? 'ri-flashlight-fill text-warning' : 'ri-flashlight-line text-muted';
        $titleAct = ($activadoCom === 1) ? 'Activado por Comercial (Listo para Despacho) - Clic para cambiar' : 'Solo Preplaneación - Clic para activar';
        $nextAct = ($activadoCom === 1) ? 0 : 1;
        $btnAct = '<button class="btn btn-sm btn-soft-secondary me-1" onclick="fntToggleActivacion(' . $id . ', ' . $nextAct . ')" title="' . $titleAct . '"><i class="' . $iconAct . '"></i></button>';

        $btnFechas  = '<button class="btn btn-sm btn-soft-warning me-1" onclick="fntRegistrarFechas(' . $id . ')" title="Registrar Fechas"><i class="ri-calendar-check-line"></i></button>';

        // Solo habilitado si tiene fechas de salida y llegada (estado >= 2)
        $disabledSig = ($estado < 2) ? ' disabled' : '';
        $btnSig = '<button class="btn btn-sm btn-soft-success' . $disabledSig . '" onclick="fntSiguienteArea(' . $id . ')" title="Siguiente Área"><i class="ri-arrow-right-circle-line"></i></button>';

        return '<div class="text-center d-flex justify-content-center gap-1">' . $btnDetalle . $btnConfirmDest . $btnAct . $btnDestino . $btnFechas . $btnSig . '</div>';
    }
}
