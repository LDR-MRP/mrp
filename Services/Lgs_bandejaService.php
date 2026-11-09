<?php

class Lgs_bandejaService {

    private Lgs_bandejaModel $model;

    public function __construct() {
        $this->model = new Lgs_bandejaModel();
    }

    // ─── Bandeja principal ───────────────────────────────────────────────────

    public function getBandeja(array $filtros = []): array {
        return $this->model->getUnidadesBandeja($filtros);
    }

    public function getDetalle(int $idLgsUnidad): ?array {
        $unidad = $this->model->getUnidadDetalle($idLgsUnidad);
        if (!$unidad) {
            throw new Exception("Unidad no encontrada en logística.");
        }
        return $unidad;
    }

    // ─── Flujo global: Asignar Destino y Motivo ──────────────────────────────

    public function asignarDestinoMotivo(int $idLgsUnidad, array $data, int $userId): bool {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $ok = $this->model->asignarDestinoMotivo($idLgsUnidad, $data, $userId);
            if (!$ok) {
                throw new Exception("No se pudo actualizar la unidad.");
            }
            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    // ─── Registrar fechas ─────────────────────────────────────────────────────

    public function registrarFechas(int $idLgsUnidad, ?string $fechaSalida, ?string $fechaLlegada, int $userId): bool {
        // Validación básica de formato de fecha si están presentes
        foreach (['fechaSalida' => $fechaSalida, 'fechaLlegada' => $fechaLlegada] as $campo => $val) {
            if ($val !== null && !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/', $val)) {
                throw new Exception("Formato de fecha inválido en '{$campo}'.");
            }
        }
        return $this->model->registrarFechas($idLgsUnidad, $fechaSalida, $fechaLlegada, $userId);
    }

    // ─── Finalizar (Siguiente Área) ───────────────────────────────────────────

    public function finalizarUnidad(int $idLgsUnidad, int $userId): bool {
        $unidad = $this->model->getUnidadDetalle($idLgsUnidad);
        if (!$unidad) {
            throw new Exception("Unidad no encontrada.");
        }
        if (empty($unidad['fecha_salida']) || empty($unidad['fecha_llegada'])) {
            throw new Exception("La unidad debe tener fecha de salida y llegada registradas antes de finalizar.");
        }
        return $this->model->finalizarUnidad($idLgsUnidad, $userId);
    }

    // ─── Ingresar VIN a bandeja ───────────────────────────────────────────────

    public function ingresarUnidad(int $idUnidad, int $userId): int {
        return $this->model->insertarUnidad($idUnidad, $userId);
    }

    // ─── Catálogos ────────────────────────────────────────────────────────────

    public function getMotivos(): array {
        return $this->model->getMotivos();
    }

    public function getDestinos(): array {
        return $this->model->getDestinos();
    }

    public function getListaDistribuidores(): array {
        return $this->model->getListaDistribuidores();
    }

    // ─── Entrega interna ──────────────────────────────────────────────────────

    public function solicitarEntregaInterna(int $idUnidad, ?string $obs, int $userId): int {
        return $this->model->solicitarEntregaInterna($idUnidad, $obs, $userId);
    }

    public function confirmarEntregaInterna(int $idEntrega, int $userId): bool {
        return $this->model->confirmarEntregaInterna($idEntrega, $userId);
    }

    public function cancelarEntregaInterna(int $idEntrega, int $userId): bool {
        return $this->model->cancelarEntregaInterna($idEntrega, $userId);
    }

    // ─── Puntos 5, 8 y 9 (Minuta 04/11) ──────────────────────────────────────

    /**
     * Confirma el destino que viene de Pedidos (Punto 5).
     * Adriana analiza la ubicación de pedidos y la confirma como destino logístico.
     */
    public function confirmarDestinoDesdePedido(int $idLgsUnidad, int $userId): bool {
        return $this->model->confirmarDestinoDesdePedido($idLgsUnidad, $userId);
    }

    /**
     * Ajusta el destino confirmado si Adriana requiere especificar un nodo o patio logístico (Punto 5).
     */
    public function ajustarDestino(int $idLgsUnidad, string $destinoConfirmado, int $userId): bool {
        $destinoConfirmado = trim($destinoConfirmado);
        if (empty($destinoConfirmado)) {
            throw new Exception("El destino confirmado no puede estar vacío.");
        }
        return $this->model->ajustarDestino($idLgsUnidad, $destinoConfirmado, $userId);
    }

    /**
     * Activa o desactiva la unidad para despacho comercial (Punto 9).
     * 1 = Activado por Comercial (Listo para despacho en firme)
     * 0 = Solo Preplaneación (Permitido para rutas preliminares pero no despacho)
     */
    public function toggleActivacionComercial(int $idLgsUnidad, int $activado, int $userId): bool {
        $activadoVal = ($activado === 1 || $activado === true) ? 1 : 0;
        return $this->model->toggleActivacionComercial($idLgsUnidad, $activadoVal, $userId);
    }

    /**
     * Actualiza el estatus de finanzas de la unidad (Punto 8).
     * Valores válidos: PAGADO, CREDITO_AUTORIZADO, PARCIAL_AUTORIZADO, PENDIENTE
     */
    public function actualizarEstatusFinanzas(int $idLgsUnidad, string $estatusFinanzas, int $userId): bool {
        $validos = ['PAGADO', 'CREDITO_AUTORIZADO', 'PARCIAL_AUTORIZADO', 'PENDIENTE'];
        $estatusUpper = strtoupper(trim($estatusFinanzas));
        if (!in_array($estatusUpper, $validos)) {
            throw new Exception("Estatus financiero no válido. Opciones permitidas: " . implode(', ', $validos));
        }
        return $this->model->actualizarEstatusFinanzas($idLgsUnidad, $estatusUpper, $userId);
    }
}
