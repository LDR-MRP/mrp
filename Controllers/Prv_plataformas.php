<?php

class Prv_plataformas extends Controllers {

    use ApiResponser;

    private Prv_plataformasService $service;

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->service = new Prv_plataformasService();
    }

    public function Prv_plataformas(): void {
        $data['page_tag'] = "Plataformas - Logística";
        $data['page_title'] = "Gestión de Plataformas / Unidades";
        $data['page_name'] = "prv_plataformas";
        $data['page_functions_js'] = "functions_prv_plataformas.js";

        // Obtener proveedores clasificados con la actividad de Trasladista (cve_actividad = 'TRASLADO_UNIDADES')
        $proveedorModel = new Prv_proveedorModel();
        $sql = "SELECT p.id_proveedor, p.razon_social, p.rfc 
                FROM prv_cat_proveedores p
                INNER JOIN prv_rel_proveedores_actividades r ON r.id_proveedor = p.id_proveedor
                INNER JOIN prv_cat_actividades a ON a.id_actividad = r.id_actividad
                WHERE a.cve_actividad = 'TRASLADO_UNIDADES' 
                  AND p.deleted_at IS NULL
                ORDER BY p.razon_social ASC";
        $data['trasladistas'] = $proveedorModel->select_all($sql);

        $this->views->getView($this, "../Prv_plataformas/index", $data);
    }

    public function getPlataformas(): void {
        try {
            $arrData = $this->service->getAll();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnHistorial = '<button class="btn btn-sm btn-soft-info me-1" onclick="fntHistorialPlataforma(' . $arrData[$i]['id_plataforma'] . ')" title="Ver Historial / Detalle"><i class="ri-history-line"></i></button>';
                $btnEdit = '<button class="btn btn-sm btn-soft-primary me-1" onclick="fntEditPlataforma(' . $arrData[$i]['id_plataforma'] . ')" title="Editar"><i class="ri-edit-line"></i></button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" onclick="fntDelPlataforma(' . $arrData[$i]['id_plataforma'] . ')" title="Eliminar"><i class="ri-delete-bin-line"></i></button>';
                $arrData[$i]['options'] = '<div class="text-center">' . $btnHistorial . $btnEdit . $btnDelete . '</div>';
            }
            header('Content-Type: application/json');
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getPlataforma(int $id): void {
        try {
            $data = $this->service->getById($id);
            if (!$data) {
                $this->errorResponse("Plataforma no encontrada", 404);
            }
            $this->successResponse($data);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getHistorial(int $id): void {
        try {
            $historial = $this->service->getHistorialChoferes($id);
            $plataforma = $this->service->getById($id);
            $this->successResponse([
                'plataforma' => $plataforma,
                'historial' => $historial
            ]);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getChoferesPorProveedor(int $idProveedor): void {
        try {
            $choferesModel = new Prv_choferesModel();
            $sql = "SELECT id_chofer, CONCAT(nombre, ' ', apellidos) AS nombre_completo, num_licencia 
                    FROM prv_det_choferes 
                    WHERE id_proveedor = ? AND deleted_at IS NULL AND estatus_operativo = 1 
                    ORDER BY nombre ASC";
            $data = $choferesModel->select_all($sql, [$idProveedor]);
            $this->successResponse($data);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function asignarChofer(): void {
        try {
            $idPlataforma = intval($_POST['id_plataforma'] ?? 0);
            $idChofer = intval($_POST['id_chofer'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            if ($idPlataforma <= 0 || $idChofer <= 0) {
                $this->errorResponse("Seleccione una plataforma y un chofer válidos.", 422);
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->asignarChofer($idPlataforma, $idChofer, $observaciones, $userId);
            $this->successResponse(null, "Chofer asignado correctamente a la plataforma.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function store(): void {
        try {
            $errors = Prv_plataformasRequest::validate($_POST);
            if (!empty($errors)) {
                $this->errorResponse("Errores de validación", 422, $errors);
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $idPlataforma = intval($_POST['id_plataforma'] ?? 0);

            if ($idPlataforma > 0) {
                $this->service->update($idPlataforma, $_POST, $userId);
                $this->successResponse(null, "Plataforma actualizada con éxito.", 200);
            } else {
                $id = $this->service->create($_POST, $userId);
                $this->successResponse(['id_plataforma' => $id], "Plataforma registrada con éxito.", 201);
            }
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function delete(int $id): void {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->delete($id, $userId);
            $this->successResponse(null, "Plataforma eliminada con éxito.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }
}
