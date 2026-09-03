<?php

/* =====================================================
   Módulo "Control de Llaves": préstamo y devolución de la
   llave de una unidad a un colaborador interno.

   NO tiene relación con el flujo de traslados de unidades.
   Reutiliza el mismo permiso de módulo (MITRASLADOS) que ya
   tenía este submenú para no requerir que un administrador
   vuelva a configurar permisos por rol.
===================================================== */

class Inv_llaves extends Controllers
{
    public function __construct()
    {
        parent::__construct();

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }

        getPermisos(MITRASLADOS);
    }

    /* =====================================================
       FILTRO POR PLANTA (usuarios.plantaid, ya existente)

       Devuelve null cuando el usuario debe ver TODO (rol
       Administrador), o el plantaid al que está restringido en
       cualquier otro caso. Si el usuario no tiene planta asignada
       se devuelve 0, que no hace match con ningún almacén real
       y por lo tanto no le muestra llaves de nadie (en vez de
       mostrarle todo por error).
    ===================================================== */

    private function getPlantaFiltro()
    {
        $idrol = $_SESSION['userData']['idrol'] ?? ($_SESSION['rolid'] ?? 0);

        if ((int)$idrol === RADMINISTRADOR) {
            return null;
        }

        $plantaid = $_SESSION['userData']['plantaid'] ?? ($_SESSION['plantaid'] ?? null);

        return $plantaid !== null ? (int)$plantaid : 0;
    }

    public function inv_llaves()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
            die();
        }

        $data['page_tag'] = "Control de Llaves";
        $data['page_title'] = "Control de Llaves";
        $data['page_name'] = "Control de Llaves";
        $data['page_functions_js'] = "functions_inv_llaves.js";

        $this->views->getView($this, "inv_llaves", $data);
    }

    /* =====================================================
       UNIDADES (para el select de "Nueva Entrega")
    ===================================================== */

    public function getUnidadesJson()
    {
        try {

            $arrData = $this->model->selectUnidades($this->getPlantaFiltro());

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {

            http_response_code(500);

            echo json_encode(["error" => $e->getMessage()]);
        }

        die();
    }

    /* =====================================================
       RESPONSABLES (colaboradores)
    ===================================================== */

    public function getSelectResponsables()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectResponsablesActivos();

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       BITÁCORA
    ===================================================== */

    public function getLlaves()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectPrestamosLlaves($this->getPlantaFiltro());

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       HISTORIAL DE LLAVES EN TRASLADO (origen/destino completo,
       incluye las ya recibidas -que ya no salen en la bitácora
       principal- y las faltantes)
    ===================================================== */

    public function getHistorialTraslados()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->selectHistorialTraslados($this->getPlantaFiltro());

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       KPIs
    ===================================================== */

    public function getKpisLlaves()
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->getKpisLlavesGeneral($this->getPlantaFiltro());

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       PRÉSTAMO
    ===================================================== */

    public function setPrestamoLlave()
    {
        if ($_POST) {

            $vinid = intval($_POST['vinid']);
            $inventarioid = intval($_POST['inventarioid']);
            $tipo_llave = strClean($_POST['tipo_llave']);
            $almacenid = intval($_POST['almacenid']);
            $nombre_colaborador = strClean($_POST['nombre_responsable']);
            $entregado_porid = intval($_POST['entregado_por'] ?? 0);
            $fecha_prevista = strClean($_POST['fecha_devolucion'] ?? '');
            $observaciones = strClean($_POST['observaciones'] ?? '');

            // "Quién presta la llave" ya no se asume del usuario logeado
            // (cualquier sesión puede estar haciendo la captura): ahora es
            // un campo obligatorio, elegido de la lista de colaboradores
            // (por idusuario, no por nombre libre).
            if ($vinid <= 0 || $tipo_llave === '' || $almacenid <= 0 || $nombre_colaborador === '' || $entregado_porid <= 0) {

                echo json_encode([
                    'status' => false,
                    'msg' => 'Faltan datos obligatorios (unidad, tipo de llave, responsable o quién presta la llave)'
                ], JSON_UNESCAPED_UNICODE);

                die();
            }

            $request = $this->model->prestarLlave(
                $vinid,
                $inventarioid,
                $tipo_llave,
                $almacenid,
                $nombre_colaborador,
                $fecha_prevista ?: null,
                $observaciones,
                $_SESSION['idUser'],
                $this->getPlantaFiltro(),
                $entregado_porid
            );

            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }

        die();
    }

    /* =====================================================
       DEVOLUCIÓN
    ===================================================== */

    public function setDevolucionLlave()
    {
        if ($_POST) {

            $idmovimiento = intval($_POST['idmovimiento']);
            $responsableRecibe = strClean($_POST['responsable_recibe'] ?? '');
            $observaciones = strClean($_POST['observaciones'] ?? '');

            if ($idmovimiento <= 0) {

                echo json_encode([
                    'status' => false,
                    'msg' => 'Préstamo inválido'
                ], JSON_UNESCAPED_UNICODE);

                die();
            }

            $request = $this->model->devolverLlave(
                $idmovimiento,
                $responsableRecibe,
                $observaciones,
                $_SESSION['idUser'],
                $this->getPlantaFiltro()
            );

            echo json_encode($request, JSON_UNESCAPED_UNICODE);
        }

        die();
    }
}
