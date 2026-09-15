import os

ctrl_file = '/home/christianguarneros/proyectos/mrp/Controllers/Lgs_planeaciones.php'

content = """<?php
require_once('Services/ApiResponser.php');
require_once('Services/Lgs_planeacionesService.php');
require_once('Models/Lgs_planeacionesModel.php');

class Lgs_planeaciones extends Controllers
{
    use ApiResponser;

    private Lgs_planeacionesService $service;

    public function __construct()
    {
        parent::__construct();
        session_start();
        $this->service = new Lgs_planeacionesService();
    }

    public function Lgs_planeaciones(): void
    {
        $this->views->getView(
            $this,
            "../Lgs_planeaciones/index",
            [
                'page_tag' => "Planeación de Logística",
                'page_title' => "Mis Planeaciones",
                'page_name' => "lgs_planeaciones",
                'page_functions_js' => "functions_lgs_planeaciones.js",
            ]
        );
    }

    public function getPlaneaciones(): void
    {
        try {
            $data = $this->service->getAllPlaneaciones();
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
            exit;
        } catch (Throwable $e) {
            echo json_encode([]);
            exit;
        }
    }

    public function getEnviosDisponibles(): void
    {
        try {
            $data = $this->service->getEnviosDisponibles();
            echo $this->successResponse($data, "Listado de envíos disponibles obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function store(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            
            $descripcion = $_POST['descripcion'] ?? '';
            $enviosIdsStr = $_POST['envios_ids'] ?? ''; 
            $enviosIds = !empty($enviosIdsStr) ? explode(',', $enviosIdsStr) : [];
            $enviosIds = array_map('intval', array_filter($enviosIds));

            $data = [
                'descripcion' => $descripcion,
                'obs_operador' => $_POST['obs_operador'] ?? ''
            ];

            $idPlan = $this->service->createPlaneacion($data, $enviosIds, $userId);
            
            echo $this->successResponse(['id_planeacion' => $idPlan], "Planeación enviada a aprobación exitosamente.");

        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getDetalleCompletoPlan(int $idPlaneacion): void
    {
        try {
            $data = $this->service->getDetalleCompletoPlan($idPlaneacion);
            echo $this->successResponse($data, "Detalle completo de la planeación obtenido");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function reabrir(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);

            if ($idPlaneacion <= 0) {
                throw new Exception("ID de planeación no válido.");
            }

            $this->service->reabrirPlaneacion($idPlaneacion, $userId);
            echo $this->successResponse(null, "Planeación y sus envíos reabiertos con éxito.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function enviarAprobacion(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);

            if ($idPlaneacion <= 0) {
                throw new Exception("ID de planeación no válido.");
            }

            $this->service->enviarAprobacion($idPlaneacion, $userId);
            echo $this->successResponse(null, "Planeación enviada a aprobación exitosamente.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function clonarPlaneacion(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);

            if ($idPlaneacion <= 0) {
                throw new Exception("ID de planeación no válido.");
            }

            $model = new Lgs_planeacionesModel();
            $res = $model->clonarPlaneacion($idPlaneacion, $userId);
            
            echo $this->successResponse(['id_planeacion' => $res['id_planeacion']], "Planeación clonada con éxito.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function changeEstado(): void
    {
        try {
            $userId = $_SESSION['idUser'] ?? 1;
            $idPlaneacion = intval($_POST['id_planeacion'] ?? 0);
            $newEstado = intval($_POST['id_estado'] ?? 0);
            $msg = $_POST['msg'] ?? '';

            if ($idPlaneacion <= 0 || $newEstado <= 0) {
                throw new Exception("Datos no válidos.");
            }

            $model = new Lgs_planeacionesModel();
            $model->changeEstado($idPlaneacion, $newEstado, $userId, $msg);
            
            echo $this->successResponse([], "Estado actualizado correctamente.");
        } catch (Exception $e) {
            echo $this->errorResponse($e->getMessage(), 500);
        }
    }
}
"""

with open(ctrl_file, 'w') as f:
    f.write(content)
