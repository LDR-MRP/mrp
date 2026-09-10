<?php

class Lgs_costos extends Controllers
{
    use ApiResponser;

    private Lgs_costosService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        // Aseguramos permisos de lectura/escritura/edición/eliminación para costos de logística
        $_SESSION['permisosMod'] = [
            'r' => 1,
            'w' => 1,
            'u' => 1,
            'd' => 1
        ];

        $this->service = new Lgs_costosService();
    }

    /**
     * Renderiza la vista principal del Administrador de Costos
     * URL: {{base_url}}/Lgs_costos
     */
    public function Lgs_costos(): void
    {
        $catalogs = $this->service->getFormCatalogs();
        
        $this->views->getView(
            $this,
            "index",
            [
                'page_tag'          => "Admin de Costos",
                'page_title'        => "Administrador de Distancias y Costos Logísticos",
                'page_name'         => "lgs_costos",
                'page_functions_js' => "functions_lgs_costos.js",
                'catalogs'          => $catalogs
            ]
        );
    }

    /**
     * ==========================================
     * MÓDULO 1: DISTANCIAS
     * ==========================================
     */
    public function getDistancias(): void
    {
        try {
            $data = $this->service->listDistancias();
            for ($i = 0; $i < count($data); $i++) {
                $row = $data[$i];
                
                $data[$i]['ruta_html'] = '
                    <div class="d-flex align-items-center">
                        <span class="fw-semibold text-body">' . htmlspecialchars($row['origen_nombre']) . '</span>
                        <i class="ri-arrow-left-right-line text-muted mx-2 fs-16"></i>
                        <span class="fw-bold text-primary">' . htmlspecialchars($row['destino_nombre']) . '</span>
                    </div>';

                $data[$i]['km_html'] = '<span class="fw-medium text-dark"><i class="ri-dashboard-3-line text-muted me-1"></i>' . number_format($row['km'], 2) . ' KM</span>';

                $btnEdit = '<button class="btn btn-sm btn-primary shadow-sm me-1" title="Editar Distancia" onClick="fntEditDistancia(' . $row['id_ubicacion_a'] . ', ' . $row['id_ubicacion_b'] . ', ' . $row['km'] . ')"><i class="ri-edit-2-line align-middle"></i></button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" title="Eliminar Distancia" onClick="fntDeleteDistancia(' . $row['id_distancia'] . ', \'' . addslashes($row['origen_nombre'] . ' ⟷ ' . $row['destino_nombre']) . '\')"><i class="ri-delete-bin-fill align-middle"></i></button>';
                
                $data[$i]['options'] = '<div class="text-center">' . $btnEdit . $btnDelete . '</div>';
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function saveDistancia(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?: $_POST;

            $success = $this->service->saveDistancia($data);
            if ($success) {
                echo $this->successResponse(null, "Distancia guardada correctamente.");
            } else {
                echo $this->errorResponse("No se pudo guardar la distancia.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }

    public function delDistancia(): void
    {
        try {
            $idDistancia = intval($_POST['id_distancia'] ?? 0);
            $success = $this->service->deleteDistancia($idDistancia);
            if ($success) {
                echo $this->successResponse(null, "Distancia eliminada con éxito.");
            } else {
                echo $this->errorResponse("No se pudo eliminar la distancia.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }


    /**
     * ==========================================
     * MÓDULO 2: TARIFAS POR PROVEEDOR
     * ==========================================
     */
    public function getTarifasProveedor(): void
    {
        try {
            $idProveedor = isset($_GET['id_proveedor']) && $_GET['id_proveedor'] !== '' ? intval($_GET['id_proveedor']) : 0;
            $data = $this->service->getTarifasProveedor($idProveedor);
            echo $this->successResponse($data, "Tarifas del proveedor obtenidas con éxito.");
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function saveTarifasProveedor(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?: $_POST;

            $success = $this->service->saveTarifasProveedor($data);
            if ($success) {
                echo $this->successResponse(null, "Tarifas del proveedor guardadas exitosamente.");
            } else {
                echo $this->errorResponse("No se pudieron guardar las tarifas.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function getProveedoresEstadoTarifa(): void
    {
        try {
            $data = $this->service->getProveedoresConEstadoTarifa();
            echo $this->successResponse($data, "Proveedores obtenidos exitosamente.");
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function saveTarifasBaseReplicar(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?: $_POST;

            $proveedoresReplicar = $data['proveedores_replicar'] ?? [];
            if (!is_array($proveedoresReplicar)) {
                $proveedoresReplicar = [];
            }

            $success = $this->service->saveTarifasBaseConReplicacion($data, $proveedoresReplicar);
            if ($success) {
                $count = count($proveedoresReplicar);
                $msg = $count > 0
                    ? "Tarifa Base General guardada y replicada a {$count} proveedor(es) seleccionado(s)."
                    : "Tarifa Base General guardada exitosamente (sin replicar a proveedores).";
                echo $this->successResponse(null, $msg);
            } else {
                echo $this->errorResponse("No se pudieron guardar las tarifas.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function resetTarifasProveedor(): void
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true) ?: $_POST;
            $idProveedor = isset($data['id_proveedor']) ? intval($data['id_proveedor']) : 0;

            if ($idProveedor <= 0) {
                echo $this->errorResponse("Debe seleccionar un proveedor válido para restablecer.", 400);
                die();
            }

            $success = $this->service->resetTarifasProveedor($idProveedor);
            if ($success) {
                echo $this->successResponse(null, "Tarifas personalizadas eliminadas. El proveedor ahora usa la Tarifa Base General.");
            } else {
                echo $this->errorResponse("No se pudieron restablecer las tarifas.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    /**
     * ==========================================
     * MODELOS DE VIN
     * ==========================================
     */
    public function getModelosVin(): void
    {
        try {
            $data = $this->service->listModelosVin();
            for ($i = 0; $i < count($data); $i++) {
                $segmento = $data[$i]['segmento'] ?? '<span class="badge bg-danger-subtle text-danger">Sin Asignar</span>';
                $data[$i]['segmento_html'] = $segmento;

                $btnLink = '<button class="btn btn-sm btn-soft-primary" title="Asignar Segmento" onClick="fntAsignarSegmento(' . $data[$i]['id_cat_modelo_vin'] . ', \'' . addslashes($data[$i]['modelo']) . '\')"><i class="ri-link"></i> Asignar</button>';
                $data[$i]['options'] = '<div class="text-center">' . $btnLink . '</div>';
            }
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function setSegmentoModelo(): void
    {
        try {
            if (empty($_POST['id_modelo_vin'])) {
                echo $this->errorResponse("ID de modelo requerido.", 400);
                die();
            }
            $idModelo = intval($_POST['id_modelo_vin']);
            $idSegmento = !empty($_POST['id_segmento']) ? intval($_POST['id_segmento']) : null;

            $success = $this->service->setSegmentoModelo($idModelo, $idSegmento);
            if ($success) {
                echo $this->successResponse(null, "Segmento asignado correctamente al modelo.");
            } else {
                echo $this->errorResponse("No se pudo realizar la asignación.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }
}
