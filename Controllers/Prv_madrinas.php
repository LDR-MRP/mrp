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

        // Obtener lista de trasladistas para el combo
        $trasladistasModel = new Prv_trasladistasModel();
        $data['trasladistas'] = $trasladistasModel->getTrasladistas();

        $this->views->getView($this, "../Prv_madrinas/index", $data);
    }

    public function getMadrinas(): void {
        try {
            $arrData = $this->service->getAll();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnEdit = '<button class="btn btn-sm btn-soft-primary me-1" onclick="fntEditMadrina(' . $arrData[$i]['id_madrina'] . ')" title="Editar"><i class="ri-edit-line"></i></button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" onclick="fntDelMadrina(' . $arrData[$i]['id_madrina'] . ')" title="Eliminar"><i class="ri-delete-bin-line"></i></button>';
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . $btnDelete . '</div>';
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
