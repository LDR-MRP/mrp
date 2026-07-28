<?php

class Prv_choferes extends Controllers {

    use ApiResponser;

    private Prv_choferesService $service;

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->service = new Prv_choferesService();
    }

    public function Prv_choferes(): void {
        $data['page_tag'] = "Choferes - Logística";
        $data['page_title'] = "Gestión de Choferes / Operadores";
        $data['page_name'] = "prv_choferes";
        $data['page_functions_js'] = "functions_prv_choferes.js";

        // Obtener lista de trasladistas para el combo
        $trasladistasModel = new Prv_trasladistasModel();
        $data['trasladistas'] = $trasladistasModel->getTrasladistas();

        $this->views->getView($this, "../Prv_choferes/index", $data);
    }

    public function getChoferes(): void {
        try {
            $arrData = $this->service->getAll();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnEdit = '<button class="btn btn-sm btn-soft-primary me-1" onclick="fntEditChofer(' . $arrData[$i]['id_chofer'] . ')" title="Editar"><i class="ri-edit-line"></i></button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" onclick="fntDelChofer(' . $arrData[$i]['id_chofer'] . ')" title="Eliminar"><i class="ri-delete-bin-line"></i></button>';
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getChofer(int $id): void {
        try {
            $data = $this->service->getById($id);
            if (!$data) {
                $this->errorResponse("Chofer no encontrado", 404);
            }
            $this->successResponse($data);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function store(): void {
        try {
            $errors = Prv_choferesRequest::validate($_POST);
            if (!empty($errors)) {
                $this->errorResponse("Errores de validación", 422, $errors);
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $idChofer = intval($_POST['id_chofer'] ?? 0);

            if ($idChofer > 0) {
                $this->service->update($idChofer, $_POST, $userId);
                $this->successResponse(null, "Chofer actualizado con éxito.", 200);
            } else {
                $id = $this->service->create($_POST, $userId);
                $this->successResponse(['id_chofer' => $id], "Chofer registrado con éxito.", 201);
            }
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function delete(int $id): void {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->delete($id, $userId);
            $this->successResponse(null, "Chofer eliminado con éxito.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }
}
