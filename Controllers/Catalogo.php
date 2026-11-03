<?php

class Catalogo extends Controllers
{
    use ApiResponser;

    protected $catalogoService;

    public function __construct()
    {
        $this->catalogoService = new CatalogoService;
    }

    public function condiciones_pago()
    {
        return $this->apiResponse($this->catalogoService->condicionesPago());
    }

    public function bancos()
    {
        error_log('[Catalogo::bancos] Iniciando consulta de bancos');
        $response = $this->catalogoService->bancos();
        error_log('[Catalogo::bancos] Response: ' . json_encode($response));
        return $this->apiResponse($response);
    }

    public function guardarBanco()
    {
        return $this->apiResponse($this->catalogoService->guardarBanco($this->request, $this->request['auth_user']['id']));
    }

    public function guardarBancoTradicional()
    {
        try {
            error_log('[Catalogo::guardarBancoTradicional] Iniciando guardado de banco (routing tradicional)');

            // Leer JSON del body
            $body = file_get_contents('php://input');
            $data = json_decode($body, true) ?? [];

            error_log('[Catalogo::guardarBancoTradicional] Datos recibidos: ' . json_encode($data));

            // Validación manual
            if (empty($data['nombre_corto'])) {
                echo json_encode(['status' => false, 'error' => 'El nombre del banco es obligatorio']);
                exit;
            }
            if (strlen($data['nombre_corto']) < 3) {
                echo json_encode(['status' => false, 'error' => 'El nombre del banco debe tener al menos 3 caracteres']);
                exit;
            }
            if (empty($data['razon_social'])) {
                echo json_encode(['status' => false, 'error' => 'La razón social es obligatoria']);
                exit;
            }

            $userId = $_SESSION['idUser'] ?? 0;

            error_log('[Catalogo::guardarBancoTradicional] Request validada, userId: ' . $userId);

            $response = $this->catalogoService->guardarBanco($data, $userId);

            error_log('[Catalogo::guardarBancoTradicional] Response: ' . json_encode($response));

            echo json_encode($response);
            exit;
        } catch (\Exception $e) {
            error_log('[Catalogo::guardarBancoTradicional] ERROR: ' . $e->getMessage());
            echo json_encode(['status' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    public function codigos_postales(mixed $cp)
    {
        return $this->apiResponse($this->catalogoService->codigospostales($cp));
    }

    public function cuentas_contables()
    {
        return $this->apiResponse($this->catalogoService->cuentasContables());
    }

    public function estados()
    {
        return $this->apiResponse($this->catalogoService->estados());
    }

    public function paises()
    {
        return $this->apiResponse($this->catalogoService->paises());
    }

    public function productLines()
    {
        return $this->apiResponse($this->catalogoService->getProductLines());
    }

    public function paymentMethods()
    {
        return $this->apiResponse($this->catalogoService->getPaymentMethods());
    }

    public function plants()
    {
        // Capturamos el filtro opcional de la URL
        $filters = [
            'plantaid' => $_GET['plantaid'] ?? null,
            'estado' => 2
        ];

        return $this->apiResponse($this->catalogoService->getPlants($filters));
    }
}