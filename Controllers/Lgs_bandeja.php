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
        $catalogosEnvios = [];
        try {
            if (file_exists("Services/Lgs_enviosService.php")) {
                require_once "Services/Lgs_enviosService.php";
                $enviosService = new Lgs_enviosService();
                $catalogosEnvios = $enviosService->getCatalogosSelect();
            }
        } catch (Throwable $e) {
            $catalogosEnvios = [];
        }

        $data['page_tag']            = "Bandeja - Logística";
        $data['page_title']          = "Bandeja de Logística";
        $data['page_name']           = "lgs_bandeja";
        $data['page_functions_js']   = ['functions_lgs_bandeja.js', 'functions_lgs_envios.js'];
        $data['motivos']             = $this->service->getMotivos();
        $data['destinos']            = $this->service->getDestinos();
        $data['distribuidores']      = $this->service->getListaDistribuidores();
        $data['catalogos_simulador'] = $this->service->getCatalogosSimuladorBandeja();
        $data['catalogos_sedes_cli'] = $this->service->getSedesAgrupadasPorCliente();
        $data['catalogos']           = $catalogosEnvios;

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

    // ─── Pestaña 2: Catálogo Global Unidades PT y Simulador de Envíos ─────────

    public function getUnidadesPT(): void {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $filtros = [
                'facturacion' => $_GET['facturacion'] ?? '',
                'pago'        => $_GET['pago'] ?? '',
                'id_segmento' => $_GET['id_segmento'] ?? '',
                'planta'      => $_GET['planta'] ?? '',
                'id_cliente'  => $_GET['id_cliente'] ?? '',
                'con_pedido'  => $_GET['con_pedido'] ?? '',
                'busqueda'    => $_GET['busqueda'] ?? '',
            ];
            $data = $this->service->getUnidadesPTGlobales($filtros);
            $this->successResponse($data, "Unidades PT obtenidas");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getSedesCliente(): void {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $idCliente = intval($_GET['id_cliente'] ?? $_POST['id_cliente'] ?? 0);
            $res = $this->service->getSedesAgrupadasPorCliente();
            if ($idCliente > 0) {
                $sedes = $res['sedes_por_cliente'][strval($idCliente)] ?? [];
                $this->successResponse([
                    'id_cliente' => $idCliente,
                    'sedes'      => $sedes,
                ], "Sedes del cliente obtenidas");
                return;
            }
            $this->successResponse($res, "Catálogo de sedes por cliente obtenido");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function asignarSedeUnidadPT(): void {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $userId = intval($_SESSION['idUser'] ?? 1);
            $raw = file_get_contents('php://input');
            $input = $raw ? json_decode($raw, true) : null;
            if (!is_array($input)) {
                $input = $_POST;
            }
            $idNs = intval($input['id_numeros_serie'] ?? 0);
            $vin  = strtoupper(trim((string)($input['vin'] ?? '')));
            $idUbicacion = intval($input['id_ubicacion'] ?? 0);
            $unitSnapshot = is_array($input['unit'] ?? null) ? $input['unit'] : [];

            $res = $this->service->asignarSedeUnidadPT($idNs, $vin, $idUbicacion, $userId, $unitSnapshot);
            $this->successResponse($res, "Sede '{$res['sede_nombre']}' asignada correctamente al VIN {$res['vin']}.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 422);
        }
    }

    public function simularEnvioPT(): void {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $raw = file_get_contents('php://input');
            $input = $raw ? json_decode($raw, true) : null;
            if (!is_array($input)) {
                $input = $_POST;
            }
            if (!isset($input['vins']) && !empty($input['vins_json'])) {
                $input['vins'] = json_decode((string)$input['vins_json'], true) ?: [];
            }
            $res = $this->service->simularPosibleEnvioPT($input);
            $this->successResponse($res, "Simulación calculada");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function sincronizarPTBandeja(): void {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $userId = intval($_SESSION['idUser'] ?? 1);
            $raw = file_get_contents('php://input');
            $input = $raw ? json_decode($raw, true) : null;
            if (!is_array($input)) {
                $input = $_POST;
            }
            $vins = $input['vins'] ?? [];
            if (!is_array($vins) && !empty($input['vins_json'])) {
                $vins = json_decode((string)$input['vins_json'], true) ?: [];
            }
            if (empty($vins)) {
                $this->errorResponse("Debe seleccionar al menos una unidad PT para sincronizar.", 422);
                return;
            }
            $res = $this->service->sincronizarUnidadesPTEnBandeja($vins, $userId);
            $this->successResponse($res, "Se sincronizaron {$res['total']} unidad(es) PT hacia la Bandeja Operativa ({$res['creados']} nueva(s), {$res['actualizados']} actualizada(s)).");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function crearEnvioDesdePT(): void {
        if (ob_get_length()) {
            ob_clean();
        }
        try {
            $userId = intval($_SESSION['idUser'] ?? 1);
            $raw = file_get_contents('php://input');
            $input = $raw ? json_decode($raw, true) : null;
            if (!is_array($input)) {
                $input = $_POST;
            }
            $vins = $input['vins'] ?? [];
            if (!is_array($vins) && !empty($input['vins_json'])) {
                $vins = json_decode((string)$input['vins_json'], true) ?: [];
            }
            if (empty($vins)) {
                $this->errorResponse("Debe seleccionar al menos una unidad PT para generar el envío.", 422);
                return;
            }
            $res = $this->service->crearPreEnvioDesdePT($input, $vins, $userId);
            $this->successResponse($res, "Envío en borrador {$res['folio_envio']} creado con {$res['total_vins']} unidad(es). Listo para Acomodo.");
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

        $clsIcon = 'btn btn-sm btn-icon-lgs';
        $btns = [];

        if ($destConf === 0 && !empty($row['destino_pedido']) && $row['destino_pedido'] !== 'Por Definir') {
            $btns[] = '<button type="button" class="' . $clsIcon . ' btn-soft-success" onclick="fntConfirmarDestinoPedido(' . $id . ', \'' . addslashes($destPed) . '\')" title="Confirmar Destino de Pedido"><i class="ri-check-double-line"></i></button>';
        }

        $iconAct  = ($activadoCom === 1) ? 'ri-flashlight-fill text-warning' : 'ri-flashlight-line text-muted';
        $titleAct = ($activadoCom === 1) ? 'Autorizado para Movimiento (Clic para desactivar)' : 'Solo Preplaneación (Clic para autorizar movimiento)';
        $nextAct  = ($activadoCom === 1) ? 0 : 1;
        $btns[]   = '<button type="button" class="' . $clsIcon . ' btn-light border" onclick="fntToggleActivacion(' . $id . ', ' . $nextAct . ')" title="' . $titleAct . '"><i class="' . $iconAct . '"></i></button>';

        $btns[] = '<button type="button" class="' . $clsIcon . ' btn-soft-primary" onclick="fntAsignarDestino(' . $id . ')" title="Asignar / Ajustar Destino"><i class="ri-map-pin-2-line"></i></button>';

        if ($estado >= 2) {
            $btns[] = '<button type="button" class="' . $clsIcon . ' btn-soft-warning" onclick="fntRegistrarFechas(' . $id . ')" title="Registrar Fechas de Salida / Llegada"><i class="ri-calendar-check-line"></i></button>';
        }
        if ($estado === 2) {
            $btns[] = '<button type="button" class="' . $clsIcon . ' btn-soft-success" onclick="fntSiguienteArea(' . $id . ')" title="Concluir Traslado / Siguiente Área"><i class="ri-arrow-right-circle-line"></i></button>';
        }

        $btns[] = '<button type="button" class="' . $clsIcon . ' btn-light border text-secondary" onclick="fntVerUnidad(' . $id . ')" title="Ver Detalle de Unidad"><i class="ri-eye-line"></i></button>';

        return '<div class="d-inline-flex align-items-center justify-content-end gap-1 flex-nowrap">' . implode('', $btns) . '</div>';
    }
}
