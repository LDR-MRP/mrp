<?php

class Lgs_incidencias extends Controllers
{
    use ApiResponser;

    private Lgs_incidenciasService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        $this->service = new Lgs_incidenciasService();
    }

    /**
     * Renderiza la vista principal del Módulo de Incidencias Operativas
     * URL: {{base_url}}/Lgs_incidencias
     */
    public function Lgs_incidencias(): void
    {
        $catalogos = $this->service->getCatalogos();

        $this->views->getView(
            $this,
            "../Lgs_incidencias/index",
            [
                'page_tag'          => "Incidencias Operativas",
                'page_title'        => "Gestión de Incidencias en Ruta",
                'page_name'         => "lgs_incidencias",
                'page_functions_js' => "functions_lgs_incidencias.js",
                'catalogos'         => $catalogos,
            ]
        );
    }

    /**
     * Devuelve los catálogos en JSON
     * URL: {{base_url}}/Lgs_incidencias/getCatalogos
     */
    public function getCatalogos(): void
    {
        try {
            $data = $this->service->getCatalogos();
            echo $this->successResponse($data, "Catálogos obtenidos");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve envíos elegibles (estados 3, 6, 7) para select/buscador
     * URL: {{base_url}}/Lgs_incidencias/getEnviosElegibles?q=...
     */
    public function getEnviosElegibles(): void
    {
        try {
            $query = trim($_GET['q'] ?? '');
            $filtros = [
                'id_estado'        => !empty($_GET['id_estado']) ? intval($_GET['id_estado']) : null,
                'id_proveedor'     => !empty($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : null,
                'folio_envio'      => trim($_GET['folio_envio'] ?? ''),
                'folio_planeacion' => trim($_GET['folio_planeacion'] ?? ''),
                'vin'              => trim($_GET['vin'] ?? ''),
                'fecha_desde'      => trim($_GET['fecha_desde'] ?? ''),
                'fecha_hasta'      => trim($_GET['fecha_hasta'] ?? ''),
            ];
            $data = $this->service->getEnviosElegibles($query, $filtros);
            echo $this->successResponse($data, "Envíos elegibles obtenidos");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Devuelve los VINs de un envío con indicador de 'a bordo'
     * URL: {{base_url}}/Lgs_incidencias/getVinsEnvio/12?fecha=2026-10-06T12:00
     */
    public function getVinsEnvio($idEnvio = 0): void
    {
        try {
            $idEnvio = intval($idEnvio);
            if ($idEnvio <= 0) {
                throw new Exception("ID de envío no válido.");
            }
            $fecha = !empty($_GET['fecha']) ? str_replace('T', ' ', $_GET['fecha']) : null;
            $data = $this->service->getVinsEnvio($idEnvio, $fecha);
            echo $this->successResponse($data, "VINs del envío obtenidos");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * DataTable de Incidencias
     * URL: {{base_url}}/Lgs_incidencias/getIncidencias
     */
    public function getIncidencias(): void
    {
        try {
            $filtros = [
                'id_estado'           => $_GET['id_estado'] ?? null,
                'id_tipo_incidencia'  => $_GET['id_tipo_incidencia'] ?? null,
                'id_proveedor'        => $_GET['id_proveedor'] ?? null,
                'es_post_entrega'     => $_GET['es_post_entrega'] ?? null,
                'fecha_desde'         => $_GET['fecha_desde'] ?? null,
                'fecha_hasta'         => $_GET['fecha_hasta'] ?? null,
            ];
            $data = $this->service->getIncidencias($filtros);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    /**
     * Devuelve el detalle completo de una incidencia
     * URL: {{base_url}}/Lgs_incidencias/getDetalle/5
     */
    public function getDetalle($idIncidencia = 0): void
    {
        try {
            $idIncidencia = intval($idIncidencia);
            if ($idIncidencia <= 0) {
                throw new Exception("ID de incidencia no válido.");
            }
            $data = $this->service->getIncidenciaDetalle($idIncidencia);
            echo $this->successResponse($data, "Detalle de incidencia obtenido");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Registra una nueva incidencia operativa
     * URL: {{base_url}}/Lgs_incidencias/store
     */
    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;

            $idEnvio = intval($_POST['id_envio'] ?? 0);
            $idTipoIncidencia = intval($_POST['id_tipo_incidencia'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $origen = $_POST['origen'] ?? 'OPERACION';
            $severidad = $_POST['severidad'] ?? 'MEDIA';
            $ubicacionTexto = trim($_POST['ubicacion_texto'] ?? '');
            $fechaIncidente = !empty($_POST['fecha_incidente']) ? str_replace('T', ' ', $_POST['fecha_incidente']) : date('Y-m-d H:i:s');

            $vinsRaw = $_POST['vins_afectados'] ?? [];
            if (is_string($vinsRaw)) {
                $vinsRaw = json_decode($vinsRaw, true) ?: [];
            }

            if ($idEnvio <= 0 || $idTipoIncidencia <= 0 || empty($descripcion)) {
                throw new Exception("Faltan datos obligatorios para el registro de la incidencia.");
            }

            if (empty($vinsRaw)) {
                throw new Exception("Debe seleccionar al menos un VIN afectado.");
            }

            $idIncidencia = $this->service->guardarIncidencia([
                'id_envio'           => $idEnvio,
                'id_tipo_incidencia' => $idTipoIncidencia,
                'origen'             => $origen,
                'severidad'          => $severidad,
                'fecha_incidente'    => $fechaIncidente,
                'ubicacion_texto'    => $ubicacionTexto,
                'descripcion'        => $descripcion,
            ], $vinsRaw, $userId);

            // Subir evidencia inicial si viene en la misma petición
            if (isset($_FILES['archivo_evidencia']) && $_FILES['archivo_evidencia']['error'] === UPLOAD_ERR_OK) {
                $tipoEv = $_POST['tipo_evidencia_inicial'] ?? 'FOTO';
                $idVinEv = !empty($_POST['id_envio_vin_evidencia']) ? intval($_POST['id_envio_vin_evidencia']) : null;
                $this->service->subirEvidencia($idIncidencia, $idVinEv, $tipoEv, $_FILES['archivo_evidencia'], $userId);
            }

            echo $this->successResponse(['id_incidencia' => $idIncidencia], "Incidencia operativa registrada correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Actualiza una incidencia existente
     * URL: {{base_url}}/Lgs_incidencias/update/5
     */
    public function update($idIncidencia = 0): void
    {
        try {
            $idIncidencia = intval($idIncidencia);
            if ($idIncidencia <= 0) {
                throw new Exception("ID de incidencia no válido.");
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $idTipoIncidencia = intval($_POST['id_tipo_incidencia'] ?? 0);
            $descripcion = trim($_POST['descripcion'] ?? '');
            $origen = $_POST['origen'] ?? 'OPERACION';
            $severidad = $_POST['severidad'] ?? 'MEDIA';
            $ubicacionTexto = trim($_POST['ubicacion_texto'] ?? '');
            $fechaIncidente = !empty($_POST['fecha_incidente']) ? str_replace('T', ' ', $_POST['fecha_incidente']) : date('Y-m-d H:i:s');

            $vinsRaw = $_POST['vins_afectados'] ?? [];
            if (is_string($vinsRaw)) {
                $vinsRaw = json_decode($vinsRaw, true) ?: [];
            }

            if ($idTipoIncidencia <= 0 || empty($descripcion)) {
                throw new Exception("Tipo de incidencia y descripción son obligatorios.");
            }

            $this->service->actualizarIncidencia($idIncidencia, [
                'id_tipo_incidencia' => $idTipoIncidencia,
                'origen'             => $origen,
                'severidad'          => $severidad,
                'fecha_incidente'    => $fechaIncidente,
                'ubicacion_texto'    => $ubicacionTexto,
                'descripcion'        => $descripcion,
            ], $vinsRaw, $userId);

            echo $this->successResponse(['id_incidencia' => $idIncidencia], "Incidencia actualizada correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Dictamina la absorción de la incidencia
     * URL: {{base_url}}/Lgs_incidencias/dictaminar/5
     */
    public function dictaminar($idIncidencia = 0): void
    {
        try {
            $idIncidencia = intval($idIncidencia);
            if ($idIncidencia <= 0) {
                throw new Exception("ID de incidencia no válido.");
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $idAbsorcion = intval($_POST['id_absorcion'] ?? 0);
            $notas = trim($_POST['dictamen_notas'] ?? '');
            $porcentajeProv = isset($_POST['porcentaje_proveedor']) && $_POST['porcentaje_proveedor'] !== '' 
                              ? floatval($_POST['porcentaje_proveedor']) : null;

            if ($idAbsorcion <= 0) {
                throw new Exception("Debe seleccionar una opción válida de absorción.");
            }
            if (empty($notas)) {
                throw new Exception("Las observaciones del dictamen son obligatorias.");
            }

            $this->service->dictaminar($idIncidencia, $idAbsorcion, $porcentajeProv, $notas, $userId);

            echo $this->successResponse(['id_incidencia' => $idIncidencia], "Dictamen de absorción guardado correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Cambia el estado de la incidencia
     * URL: {{base_url}}/Lgs_incidencias/cambiarEstado/5
     */
    public function cambiarEstado($idIncidencia = 0): void
    {
        try {
            $idIncidencia = intval($idIncidencia);
            if ($idIncidencia <= 0) {
                throw new Exception("ID de incidencia no válido.");
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $nuevoEstado = intval($_POST['nuevo_estado'] ?? 1);
            $comentario = trim($_POST['comentario'] ?? '');

            $this->service->cambiarEstado($idIncidencia, $nuevoEstado, $comentario, $userId);

            echo $this->successResponse(['id_incidencia' => $idIncidencia], "Estado de la incidencia actualizado correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Sube una evidencia a la incidencia
     * URL: {{base_url}}/Lgs_incidencias/uploadEvidencia/5
     */
    public function uploadEvidencia($idIncidencia = 0): void
    {
        try {
            $idIncidencia = intval($idIncidencia);
            if ($idIncidencia <= 0) {
                throw new Exception("ID de incidencia no válido.");
            }

            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("No se ha enviado un archivo válido.");
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $tipo = $_POST['tipo'] ?? 'FOTO';
            $idEnvioVin = !empty($_POST['id_envio_vin']) ? intval($_POST['id_envio_vin']) : null;

            $res = $this->service->subirEvidencia($idIncidencia, $idEnvioVin, $tipo, $_FILES['archivo'], $userId);

            echo $this->successResponse($res, "Evidencia agregada exitosamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Elimina una evidencia por ID
     * URL: {{base_url}}/Lgs_incidencias/deleteEvidencia/10
     */
    public function deleteEvidencia($idEvidencia = 0): void
    {
        try {
            $idEvidencia = intval($idEvidencia);
            if ($idEvidencia <= 0) {
                throw new Exception("ID de evidencia no válido.");
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->eliminarEvidencia($idEvidencia, $userId);

            echo $this->successResponse(null, "Evidencia eliminada correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET: Descarga segura de archivo de evidencia
     * URL: {{base_url}}/Lgs_incidencias/descargarEvidencia/10
     */
    public function descargarEvidencia($idEvidencia = 0): void
    {
        try {
            $idEvidencia = intval($idEvidencia);
            if ($idEvidencia <= 0) {
                die("ID no válido.");
            }

            $model = new Lgs_incidenciasModel();
            $stmt = $model->getConexion()->prepare("SELECT ruta_archivo, nombre_original, mime FROM lgs_det_incidencias_evidencias WHERE id_evidencia = ?");
            $stmt->execute([$idEvidencia]);
            $ev = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$ev || !file_exists($ev['ruta_archivo'])) {
                header("HTTP/1.0 404 Not Found");
                die("Archivo no encontrado.");
            }

            // Normalización de ruta para verificar boundary
            $realPath = realpath($ev['ruta_archivo']);
            $allowedBase = realpath('Uploads/lgs_incidencias');
            if ($realPath === false || $allowedBase === false || !str_starts_with($realPath, $allowedBase)) {
                header("HTTP/1.0 403 Forbidden");
                die("Acceso denegado.");
            }

            header('Content-Type: ' . $ev['mime']);
            header('Content-Disposition: attachment; filename="' . basename($ev['nombre_original']) . '"');
            header('Content-Length: ' . filesize($realPath));
            header('X-Content-Type-Options: nosniff');
            header('Cache-Control: private, no-transform, no-store, must-revalidate');

            readfile($realPath);
            exit;
        } catch (Throwable $e) {
            header("HTTP/1.0 500 Internal Server Error");
            die("Error al descargar archivo.");
        }
    }
}
