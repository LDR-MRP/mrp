<?php


class Inv_operaciones_traslados extends Controllers
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

        getPermisos(MIOPTRASLADOS);
    }

    public function Inv_operaciones_traslados($folio = null)
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Traslados operaciones";
        $data['page_title'] = "Traslados operaciones";
        $data['page_name'] = "Traslados operaciones";
        $data['page_functions_js'] = "functions_inv_operaciones_traslados.js";
        $data['folio_inicial'] = $folio ? strClean($folio) : '';

        $this->views->getView($this, "inv_operaciones_traslados", $data);
    }

    // Nuevo método exclusivo para el QR
    public function escanear($folio = null)
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }

        $data['page_tag'] = "Traslados operaciones";
        $data['page_title'] = "Traslados operaciones";
        $data['page_name'] = "Traslados operaciones";
        $data['page_functions_js'] = "functions_inv_operaciones_traslados.js";
        $data['folio_inicial'] = $folio ? strClean(urldecode($folio)) : '';

        $this->views->getView($this, "inv_operaciones_traslados", $data);
    }

    public function getTrasladoOperacion($folio)
    {
        if ($_SESSION['permisosMod']['r']) {

            $arrData = $this->model->getTrasladoOperacion(
                strClean($folio)
            );

            echo json_encode(
                $arrData,
                JSON_UNESCAPED_UNICODE
            );
        }

        die();
    }

    public function registrarSalida()
    {

        try {

            if ($_SESSION['permisosMod']['r']) {


                $data = json_decode(
                    file_get_contents("php://input"),
                    true
                );


                $folio = $data['folio'];


                try {

                    $arrData = $this->model->registrarSalida(
                        $folio,
                        $_SESSION['idUser']
                    );
                } catch (Throwable $e) {

                    echo json_encode([
                        "status" => false,
                        "error" => $e->getMessage(),
                        "linea" => $e->getLine(),
                        "archivo" => $e->getFile()
                    ]);

                    die();
                }


                echo json_encode($arrData);
            }
        } catch (Throwable $e) {

            echo json_encode([
                "status" => false,
                "error_general" => $e->getMessage(),
                "linea" => $e->getLine()
            ]);
        }


        die();
    }

    public function registrarRecepcion()
    {

        try {

            if ($_SESSION['permisosMod']['r']) {


                $data = json_decode(
                    file_get_contents("php://input"),
                    true
                );


                $folio = $data['folio'];


                $arrData = $this->model->registrarRecepcion(
                    $folio,
                    $_SESSION['idUser']
                );


                echo json_encode($arrData);
            }
        } catch (Throwable $e) {


            echo json_encode([
                "status" => false,
                "error" => $e->getMessage(),
                "linea" => $e->getLine()
            ]);
        }


        die();
    }

    public function registrarUnidadAnomala()
    {
        try {
            if ($_SESSION['permisosMod']['r']) {

                $data = json_decode(file_get_contents("php://input"), true);

                $folio = strClean($data['folio'] ?? '');
                $vin   = strClean($data['vin'] ?? '');

                if ($folio === '' || $vin === '') {
                    echo json_encode([
                        "status" => false,
                        "msg" => "Datos incompletos"
                    ]);
                    die();
                }

                $arrData = $this->model->registrarUnidadAnomala(
                    $folio,
                    $vin,
                    $_SESSION['idUser']
                );

                echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
            }
        } catch (Throwable $e) {
            echo json_encode([
                "status" => false,
                "msg" => $e->getMessage()
            ]);
        }

        die();
    }
}
