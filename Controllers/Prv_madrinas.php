<?php

class Prv_madrinas extends Controllers {

    use ApiResponser;

    private Prv_madrinasService $service;

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->service = new Prv_madrinasService();
    }

    public function Prv_madrinas(): void {
        $data['page_tag'] = "Madrinas - Logística";
        $data['page_title'] = "Gestión de Madrinas / Unidades";
        $data['page_name'] = "prv_madrinas";
        $data['page_functions_js'] = "functions_prv_madrinas.js";

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

        $this->views->getView($this, "../Prv_madrinas/index", $data);
    }

    public function getMadrinas(): void {
        try {
            $arrData = $this->service->getAll();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnHistorial = '<button class="btn btn-sm btn-soft-info me-1" onclick="fntHistorialMadrina(' . $arrData[$i]['id_madrina'] . ')" title="Ver Historial / Detalle"><i class="ri-history-line"></i></button>';
                $btnEdit = '<button class="btn btn-sm btn-soft-primary me-1" onclick="fntEditMadrina(' . $arrData[$i]['id_madrina'] . ')" title="Editar"><i class="ri-edit-line"></i></button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" onclick="fntDelMadrina(' . $arrData[$i]['id_madrina'] . ')" title="Eliminar"><i class="ri-delete-bin-line"></i></button>';
                $arrData[$i]['options'] = '<div class="text-center">' . $btnHistorial . $btnEdit . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getMadrina(int $id): void {
        try {
            $data = $this->service->getById($id);
            if (!$data) {
                $this->errorResponse("Madrina no encontrada", 404);
            }
            $this->successResponse($data);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getHistorial(int $id): void {
        try {
            $historial = $this->service->getHistorialChoferes($id);
            $madrina = $this->service->getById($id);
            $this->successResponse([
                'madrina' => $madrina,
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
            $idMadrina = intval($_POST['id_madrina'] ?? 0);
            $idChofer = intval($_POST['id_chofer'] ?? 0);
            $observaciones = trim($_POST['observaciones'] ?? '');

            if ($idMadrina <= 0 || $idChofer <= 0) {
                $this->errorResponse("Seleccione una madrina y un chofer válidos.", 422);
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->asignarChofer($idMadrina, $idChofer, $observaciones, $userId);
            $this->successResponse(null, "Chofer asignado correctamente a la madrina.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function store(): void {
        try {
            $errors = Prv_madrinasRequest::validate($_POST);
            if (!empty($errors)) {
                $this->errorResponse("Errores de validación", 422, $errors);
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $idMadrina = intval($_POST['id_madrina'] ?? 0);

            if ($idMadrina > 0) {
                $this->service->update($idMadrina, $_POST, $userId);
                $this->successResponse(null, "Madrina actualizada con éxito.", 200);
            } else {
                $id = $this->service->create($_POST, $userId);
                $this->successResponse(['id_madrina' => $id], "Madrina registrada con éxito.", 201);
            }
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function delete(int $id): void {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->delete($id, $userId);
            $this->successResponse(null, "Madrina eliminada con éxito.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }
}
