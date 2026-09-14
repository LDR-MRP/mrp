<?php

class Lgs_ejecucion extends Controllers
{
    use ApiResponser;

    private Lgs_ejecucionService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_ejecucionService();
    }

    /**
     * Renderiza la vista principal de la Mesa de Despacho y Salidas
     * URL: {{base_url}}/Lgs_ejecucion
     */
    public function Lgs_ejecucion(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_ejecucion/index",
            [
                'page_tag' => "Despacho de Envíos",
                'page_title' => "Mesa de Ejecución y Salidas",
                'page_name' => "lgs_ejecucion",
                'page_functions_js' => "functions_lgs_ejecucion.js",
            ]
        );
    }

    /**
     * Renderiza la vista móvil para el chofer trasladista
     * URL: {{base_url}}/Lgs_ejecucion/chofer_movil
     */
    public function chofer_movil(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_ejecucion/chofer_movil",
            [
                'page_tag' => "Inspección Móvil",
                'page_title' => "Inspección Trasladista",
                'page_name' => "lgs_chofer_movil",
                'page_functions_js' => "functions_lgs_chofer.js",
            ]
        );
    }

    /**
     * Renderiza la vista de confirmación de entrega en destino con QR
     * URL: {{base_url}}/Lgs_ejecucion/entrega_destino
     */
    public function entrega_destino(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_ejecucion/entrega_destino",
            [
                'page_tag' => "Confirmación de Entrega",
                'page_title' => "Entrega Destino",
                'page_name' => "lgs_entrega_destino",
                'page_functions_js' => "functions_lgs_entrega.js",
            ]
        );
    }

    /**
     * Devuelve el JSON para alimentar el DataTable de Envíos Aprobados/En Tránsito, filtrando por planta si no es administrador
     * URL: {{base_url}}/Lgs_ejecucion/getEnviosDespacho
     */
    public function getEnviosDespacho(): void
    {
        try {
            $plantaId = ($_SESSION['userData']['idrol'] ?? 0) == 1 ? null : ($_SESSION['userData']['plantaid'] ?? null);
            $data = $this->service->getEnviosDespacho($plantaId);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Obtiene el detalle de VINs y su orden de acomodo para el área de entregas
     * URL: {{base_url}}/Lgs_ejecucion/getDetalleDespacho/12
     */
    public function getDetalleDespacho($idEnvio = 0): void
    {
        try {
            $idEnvio = intval($idEnvio);
            if ($idEnvio <= 0) {
                throw new Exception("ID de envío no válido.");
            }
            $data = $this->service->getDetalleDespacho($idEnvio);
            echo $this->successResponse($data, "Detalle de VINs obtenido");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET: Reinicia el envío para volver a probar el flujo de despacho desde cero
     * URL: {{base_url}}/Lgs_ejecucion/resetPrueba/16
     */
    public function resetPrueba($idEnvio = 16): void
    {
        try {
            $idEnvio = intval($idEnvio);
            if ($idEnvio <= 0) $idEnvio = 16;
            $this->service->resetEnvioParaPrueba($idEnvio);
            echo $this->successResponse(null, "El envío #{$idEnvio} ha sido reiniciado a estado 'Aprobado' y sus VINs a 'En Patio' para pruebas.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Confirma la fecha pactada de recolección por el administrador
     * URL: {{base_url}}/Lgs_ejecucion/confirmarRecoleccion
     */
    public function confirmarRecoleccion(): void
    {
        try {
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $fechaRecoleccion = $_POST['fecha_recoleccion'] ?? '';

            if ($idEnvio === 0 || empty($fechaRecoleccion)) {
                throw new Exception("El ID de envío y la fecha de recolección son obligatorios.");
            }

            $this->service->confirmarFechaRecoleccion($idEnvio, $fechaRecoleccion);
            echo $this->successResponse(null, "Fecha de recolección programada y unidades listas en área de entregas.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Registra el despacho real del envío
     * URL: {{base_url}}/Lgs_ejecucion/registrarDespacho
     */
    public function registrarDespacho(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $fechaSalida = $_POST['fecha_salida_real'] ?? date('Y-m-d H:i:s');
            $evidenciasJson = $_POST['evidencias_json'] ?? null;

            if ($idEnvio === 0) {
                throw new Exception("El ID de envío es requerido.");
            }

            $this->service->registrarDespacho($idEnvio, $fechaSalida, $evidenciasJson, $userId);
            
            echo $this->successResponse(null, "Despacho de envío registrado correctamente. Solicitud enviada a entregas.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Confirma la entrega física de un VIN individual en planta
     * URL: {{base_url}}/Lgs_ejecucion/confirmarVin
     */
    public function confirmarVin(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $idUnidad = intval($_POST['id_unidad'] ?? 0);

            if ($idEnvio === 0 || $idUnidad === 0) {
                throw new Exception("Parámetros incompletos para confirmar el VIN.");
            }

            $enTransito = $this->service->confirmarSalidaVin($idEnvio, $idUnidad, $userId);
            
            $msg = $enTransito 
                ? "¡Todos los VINs fueron entregados! El envío ahora se encuentra EN TRÁNSITO." 
                : "VIN confirmado e entregado al trasladista exitosamente.";

            echo $this->successResponse(['en_transito' => $enTransito], $msg);
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET: Obtiene los envíos del chofer logueado
     * URL: {{base_url}}/Lgs_ejecucion/getEnviosChofer
     */
    public function getEnviosChofer(): void
    {
        try {
            // El ID del chofer/usuario se obtiene de la sesión
            $idUsuario = $_SESSION['idUser'] ?? 0;
            if ($idUsuario === 0) {
                throw new Exception("Sesión inválida.");
            }

            $data = $this->service->getEnviosChofer($idUsuario);
            echo $this->successResponse($data, "Envíos obtenidos correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Registra el checklist del trasladista o personal de planta
     * URL: {{base_url}}/Lgs_ejecucion/guardarChecklistTrasladista
     */
    public function guardarChecklistTrasladista(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $idUnidad = intval($_POST['id_unidad'] ?? 0);
            $tipoChecklist = $_POST['tipo_checklist'] ?? '';
            $vin = $_POST['vin'] ?? '';
            $comentarios = $_POST['comentarios'] ?? '';

            if ($idEnvio === 0 || $idUnidad === 0 || empty($tipoChecklist) || empty($vin)) {
                throw new Exception("Parámetros incompletos para guardar el checklist.");
            }

            // Sanitizar archivos cargados
            $fotos = [];
            if (!empty($_FILES)) {
                foreach ($_FILES as $key => $file) {
                    if ($file['error'] === UPLOAD_ERR_OK) {
                        $fotos[$key] = $file;
                    }
                }
            }

            $this->service->registrarChecklistTrasladista($idEnvio, $idUnidad, $tipoChecklist, $vin, $userId, $comentarios, $fotos);
            echo $this->successResponse(null, "Checklist e inspección registrados exitosamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Valida el código QR escaneado por el chofer contra los destinos/datos del viaje
     * URL: {{base_url}}/Lgs_ejecucion/validarQrCliente
     */
    public function validarQrCliente(): void
    {
        try {
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $textoQr = $_POST['texto_qr'] ?? '';

            if ($idEnvio === 0 || empty($textoQr)) {
                throw new Exception("Datos incompletos para validar el QR.");
            }

            $resultado = $this->service->validarQrCliente($idEnvio, $textoQr);

            if ($resultado['valido']) {
                echo $this->successResponse($resultado, $resultado['mensaje'] ?? "QR validado correctamente.");
            } else {
                echo $this->errorResponse($resultado['mensaje'] ?? "El código QR no es válido para este envío.", 400);
            }
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getEvidenciasUnidad($idEnvio = 0, $idUnidad = 0): void
    {
        try {
            if (is_string($idEnvio) && strpos($idEnvio, ',') !== false) {
                [$idEnvio, $idUnidad] = array_pad(explode(',', $idEnvio), 2, 0);
            }
            $data = $this->service->getEvidenciasPorUnidad((int)$idEnvio, (int)$idUnidad);
            echo $this->successResponse($data, "Evidencias obtenidas");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function revertirVin(): void
    {
        try {
            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $idUnidad = intval($_POST['id_unidad'] ?? 0);

            if ($idEnvio === 0 || $idUnidad === 0) {
                throw new Exception("Parámetros incompletos.");
            }

            $this->service->revertirConfirmacionVin($idEnvio, $idUnidad);
            echo $this->successResponse(null, "Validación revertida. Unidad devuelta a estado 'En Patio'.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
