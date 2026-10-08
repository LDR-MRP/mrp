<?php

class Lgs_gastosadicionales extends Controllers
{
    use ApiResponser;

    private Lgs_gastosadicionalesService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        $this->service = new Lgs_gastosadicionalesService();
    }

    /**
     * Renderiza la vista principal del Módulo de Gastos Adicionales
     * URL: {{base_url}}/Lgs_gastosadicionales
     */
    public function Lgs_gastosadicionales(): void
    {
        $catalogos = $this->service->getCatalogos();

        $this->views->getView(
            $this,
            "../Lgs_gastosadicionales/index",
            [
                'page_tag'          => "Gastos Adicionales",
                'page_title'        => "Gestión Financiera de Gastos Adicionales",
                'page_name'         => "lgs_gastosadicionales",
                'page_functions_js' => "functions_lgs_gastosadicionales.js",
                'catalogos'         => $catalogos,
            ]
        );
    }

    public function getCatalogos(): void
    {
        try {
            $data = $this->service->getCatalogos();
            echo $this->successResponse($data, "Catálogos obtenidos");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function buscarIncidencias(): void
    {
        try {
            $q = trim(strval($_GET['q'] ?? ''));
            $filtros = [
                'id_tipo_incidencia' => intval($_GET['id_tipo_incidencia'] ?? 0),
                'id_absorcion'       => intval($_GET['id_absorcion'] ?? 0),
                'id_proveedor'       => intval($_GET['id_proveedor'] ?? 0),
                'folio_planeacion'   => trim(strval($_GET['folio_planeacion'] ?? '')),
                'vin'                => trim(strval($_GET['vin'] ?? '')),
            ];
            $data = $this->service->buscarIncidencias($q, $filtros);
            echo $this->successResponse($data, "Incidencias encontradas");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getGastos(): void
    {
        try {
            $filtros = [
                'id_estado'        => $_GET['id_estado'] ?? null,
                'id_tipo_gasto'    => $_GET['id_tipo_gasto'] ?? null,
                'id_proveedor'     => $_GET['id_proveedor'] ?? null,
                'es_independiente' => $_GET['es_independiente'] ?? null,
            ];
            $data = $this->service->getGastos($filtros);
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    public function getDetalle($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            if ($idGasto <= 0) {
                throw new Exception("ID de gasto no válido.");
            }
            $data = $this->service->getGastoDetalle($idGasto);
            echo $this->successResponse($data, "Detalle obtenido");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Previsualiza el cálculo y desglose por VIN
     * URL: {{base_url}}/Lgs_gastosadicionales/previsualizar
     */
    public function previsualizar(): void
    {
        try {
            $vinsRaw = $_POST['vins_afectados'] ?? [];
            if (is_string($vinsRaw)) {
                $vinsRaw = json_decode($vinsRaw, true) ?: [];
            }

            $res = $this->service->previsualizarCalculo($_POST, $vinsRaw);
            echo $this->successResponse($res, "Cálculo previsualizado");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Guarda un nuevo gasto adicional
     * URL: {{base_url}}/Lgs_gastosadicionales/store
     */
    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;

            $vinsRaw = $_POST['vins_afectados'] ?? [];
            if (is_string($vinsRaw)) {
                $vinsRaw = json_decode($vinsRaw, true) ?: [];
            }

            $idGasto = $this->service->guardarGasto($_POST, $vinsRaw, $userId);

            // Subir documento inicial si viene archivo
            if (isset($_FILES['archivo_soporte']) && $_FILES['archivo_soporte']['error'] === UPLOAD_ERR_OK) {
                $tipoDoc = $_POST['tipo_documento_inicial'] ?? 'COTIZACION';
                $this->service->subirDocumentoSoporte($idGasto, $tipoDoc, $_FILES['archivo_soporte'], $userId);
            }

            echo $this->successResponse(['id_gasto' => $idGasto], "Gasto adicional registrado correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function enviarRevision($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->enviarRevision($idGasto, $userId);
            echo $this->successResponse(null, "Gasto enviado a revisión correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function aprobar($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->aprobarGasto($idGasto, $userId);
            echo $this->successResponse(null, "Gasto adicional aprobado correctamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function rechazar($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            $userId = $_SESSION['idUser'] ?? 1;
            $motivo = trim($_POST['motivo_rechazo'] ?? '');
            $this->service->rechazarGasto($idGasto, $motivo, $userId);
            echo $this->successResponse(null, "Gasto adicional rechazado.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function cancelar($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            $userId = $_SESSION['idUser'] ?? 1;
            $motivo = trim($_POST['motivo_rechazo'] ?? $_POST['motivo_cancelacion'] ?? '');
            $this->service->cancelarGasto($idGasto, $motivo, $userId);
            echo $this->successResponse(null, "Gasto adicional cancelado.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * POST: Documentar con comprobante aparte (Factura o Nota de Cargo)
     * URL: {{base_url}}/Lgs_gastosadicionales/documentar/5
     */
    public function documentar($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            $userId = $_SESSION['idUser'] ?? 1;

            $docData = [
                'doc_tipo'  => $_POST['doc_tipo'] ?? 'FACTURA',
                'doc_folio' => trim($_POST['doc_folio'] ?? ''),
                'doc_uuid'  => trim($_POST['doc_uuid'] ?? '') ?: null,
                'doc_fecha' => !empty($_POST['doc_fecha']) ? $_POST['doc_fecha'] : date('Y-m-d'),
            ];

            if (empty($docData['doc_folio'])) {
                throw new Exception("El folio del documento fiscal o comprobante es obligatorio.");
            }

            $file = $_FILES['archivo_documento'] ?? null;
            $this->service->documentarGasto($idGasto, $docData, $file, $userId);

            echo $this->successResponse(null, "Gasto documentado financieramente de forma exitosa.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function uploadDocumento($idGasto = 0): void
    {
        try {
            $idGasto = intval($idGasto);
            if (!isset($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("Archivo no válido.");
            }
            $userId = $_SESSION['idUser'] ?? 1;
            $tipo = $_POST['tipo'] ?? 'FACTURA_PDF';

            $res = $this->service->subirDocumentoSoporte($idGasto, $tipo, $_FILES['archivo'], $userId);
            echo $this->successResponse($res, "Documento adjuntado exitosamente.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteDocumento($idDoc = 0): void
    {
        try {
            $idDoc = intval($idDoc);
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->eliminarDocumentoSoporte($idDoc, $userId);
            echo $this->successResponse(null, "Documento eliminado.");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * GET: Descarga segura de comprobante financiero
     */
    public function descargarDocumento($idDoc = 0): void
    {
        try {
            $idDoc = intval($idDoc);
            if ($idDoc <= 0) die("ID no válido.");

            $model = new Lgs_gastosadicionalesModel();
            $stmt = $model->getConexion()->prepare("SELECT ruta_archivo, nombre_original, mime FROM lgs_det_gastos_adicionales_docs WHERE id_documento = ?");
            $stmt->execute([$idDoc]);
            $doc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$doc || !file_exists($doc['ruta_archivo'])) {
                header("HTTP/1.0 404 Not Found");
                die("Archivo no encontrado.");
            }

            $realPath = realpath($doc['ruta_archivo']);
            $allowedBase = realpath('Uploads/lgs_gastos');
            if ($realPath === false || $allowedBase === false || !str_starts_with($realPath, $allowedBase)) {
                header("HTTP/1.0 403 Forbidden");
                die("Acceso denegado.");
            }

            header('Content-Type: ' . $doc['mime']);
            header('Content-Disposition: attachment; filename="' . basename($doc['nombre_original']) . '"');
            header('Content-Length: ' . filesize($realPath));
            header('X-Content-Type-Options: nosniff');
            readfile($realPath);
            exit;
        } catch (Throwable $e) {
            header("HTTP/1.0 500 Internal Server Error");
            die("Error al descargar documento.");
        }
    }

    public function getResumenEnvio($idEnvio = 0): void
    {
        try {
            $idEnvio = intval($idEnvio);
            $data = $this->service->getResumenEnvioCostoReal($idEnvio);
            echo $this->successResponse($data, "Resumen obtenido");
        } catch (Throwable $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
