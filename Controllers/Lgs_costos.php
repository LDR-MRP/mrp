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

            $mantenerPersonalizadas = !empty($data['mantener_personalizadas']) && $data['mantener_personalizadas'] != '0';

            $success = $this->service->saveTarifasBaseConReplicacion($data, $proveedoresReplicar, $mantenerPersonalizadas);
            if ($success) {
                $count = count($proveedoresReplicar);
                $msg = $count > 0
                    ? "Tarifa Base General guardada y replicada a {$count} proveedor(es) seleccionado(s)."
                    : "Tarifa Base General guardada exitosamente (sin replicar a proveedores).";
                if ($mantenerPersonalizadas) {
                    $msg .= " Las tarifas de proveedores configurados manualmente se mantuvieron intactas.";
                }
                echo $this->successResponse(null, $msg);
            } else {
                echo $this->errorResponse("No se pudieron guardar las tarifas.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 500);
        }
        die();
    }

    public function compareProveedorConGlobal(): void
    {
        try {
            $idProveedor = isset($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : 0;
            if ($idProveedor <= 0) {
                echo $this->errorResponse("Debe proporcionar un ID de proveedor válido.", 400);
                die();
            }
            $data = $this->service->compareProveedorConGlobal($idProveedor);
            echo $this->successResponse($data, "Comparación realizada con éxito.");
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
                $idSeg = (int)($data[$i]['id_segmento'] ?? 0);
                $badgeClass = 'bg-secondary-subtle text-secondary';
                if ($idSeg === 1) $badgeClass = 'bg-success-subtle text-success border border-success-subtle';
                elseif ($idSeg === 2) $badgeClass = 'bg-primary-subtle text-primary border border-primary-subtle';
                elseif ($idSeg === 3) $badgeClass = 'bg-danger-subtle text-danger border border-danger-subtle';
                elseif ($idSeg === 4) $badgeClass = 'bg-warning-subtle text-warning border border-warning-subtle';
                elseif ($idSeg === 5) $badgeClass = 'bg-dark-subtle text-dark border border-dark-subtle';

                $segNombre = $data[$i]['segmento'] ?? 'Sin Asignar';
                $data[$i]['segmento_html'] = '<span class="badge ' . $badgeClass . ' fs-12 px-2 py-1"><i class="ri-checkbox-circle-line me-1"></i>' . htmlspecialchars($segNombre) . '</span>';

                // Costo por km en Rodando
                $costoRodando = '$0.00 / km';
                if ($idSeg === 1) $costoRodando = '<span class="badge bg-success-subtle text-success fw-bold fs-12">$18.00 / km</span>';
                elseif ($idSeg === 2) $costoRodando = '<span class="badge bg-primary-subtle text-primary fw-bold fs-12">$20.00 / km</span>';
                elseif ($idSeg === 3) $costoRodando = '<span class="badge bg-danger-subtle text-danger fw-bold fs-12">$25.00 / km</span>';
                elseif ($idSeg === 4 || $idSeg === 5) $costoRodando = '<span class="badge bg-dark-subtle text-dark fw-bold fs-12">$25.00 / km</span>';
                $data[$i]['costo_rodando_html'] = $costoRodando;

                $btnLink = '<button type="button" class="btn btn-sm btn-soft-primary fw-bold shadow-sm" title="Cambiar Segmento" onClick="fntAsignarSegmento(' . $data[$i]['id_cat_modelo_vin'] . ', \'' . addslashes($data[$i]['modelo']) . '\', ' . $idSeg . ')"><i class="ri-edit-line me-1"></i> Cambiar Segmento</button>';
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

    public function storeModeloVin(): void
    {
        try {
            if (empty($_POST['modelo'])) {
                echo $this->errorResponse("El nombre del modelo es requerido.", 400);
                die();
            }
            $modelo = trim($_POST['modelo']);
            $idSegmento = !empty($_POST['id_segmento']) ? intval($_POST['id_segmento']) : 1;
            $vinBase = !empty($_POST['vin_base']) ? trim($_POST['vin_base']) : null;

            $success = $this->service->addModeloVin($modelo, $idSegmento, $vinBase);
            if ($success) {
                echo $this->successResponse(null, "Modelo registrado y catalogado exitosamente.");
            } else {
                echo $this->errorResponse("No se pudo registrar el modelo.", 500);
            }
        } catch (Throwable $t) {
            echo $this->errorResponse($t->getMessage(), 400);
        }
        die();
    }
}
