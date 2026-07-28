<?php

class Prv_trasladistas extends Controllers {

    use ApiResponser;

    private Prv_trasladistasService $service;

    public function __construct() {
        parent::__construct();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->service = new Prv_trasladistasService();
    }

    public function Prv_trasladistas(): void {
        $data['page_tag'] = "Trasladistas - Logística";
        $data['page_title'] = "Gestión de Trasladistas";
        $data['page_name'] = "prv_trasladistas";
        $data['page_functions_js'] = "functions_prv_trasladistas.js";

        $this->views->getView($this, "../Prv_trasladistas/index", $data);
    }

    public function getTrasladistas(): void {
        try {
            $arrData = $this->service->getAll();
            for ($i = 0; $i < count($arrData); $i++) {
                $btnEdit = '<button class="btn btn-sm btn-soft-primary me-1" onclick="fntEditTrasladista(' . $arrData[$i]['id_proveedor'] . ')" title="Editar"><i class="ri-edit-line"></i></button>';
                $btnDelete = '<button class="btn btn-sm btn-soft-danger" onclick="fntDelTrasladista(' . $arrData[$i]['id_proveedor'] . ')" title="Eliminar"><i class="ri-delete-bin-line"></i></button>';
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function getTrasladista(int $id): void {
        try {
            $data = $this->service->getById($id);
            if (!$data) {
                $this->errorResponse("Trasladista no encontrado", 404);
            }
            $this->successResponse($data);
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function store(): void {
        try {
            $errors = Prv_trasladistasRequest::validate($_POST);
            if (!empty($errors)) {
                $this->errorResponse("Errores de validación", 422, $errors);
            }

            $userId = $_SESSION['idUser'] ?? 1;
            $idProveedor = intval($_POST['id_proveedor'] ?? 0);

            if ($idProveedor > 0) {
                $this->service->update($idProveedor, $_POST, $userId);
                $this->successResponse(null, "Trasladista actualizado con éxito.", 200);
            } else {
                $id = $this->service->create($_POST, $userId);
                $this->successResponse(['id_proveedor' => $id], "Trasladista creado con éxito.", 201);
            }
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }

    public function delete(int $id): void {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $this->service->delete($id, $userId);
            $this->successResponse(null, "Trasladista eliminado con éxito.");
        } catch (Throwable $t) {
            $this->errorResponse($t->getMessage(), 500);
        }
    }
}
