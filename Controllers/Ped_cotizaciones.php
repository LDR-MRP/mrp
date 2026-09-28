<?php


class Ped_cotizaciones extends Controllers
{

    public function __construct()
    {
        parent::__construct();

        session_start();
    }


    /* ============================================================
       INDEX
    ============================================================ */

    public function Ped_cotizaciones()
    {
        // if (
        //     empty($_SESSION['permisosMod']['r'])
        // ) {

        //     header(
        //         "Location:"
        //             . base_url()
        //             . "/dashboard"
        //     );

        //     exit;
        // }


        $data['page_tag'] =
            "Cotizaciones";


        $data['page_title'] =
            "Gestión de Cotizaciones";


        $data['page_name'] =
            "ped_cotizaciones";


        $data['page_functions_js'] = [

            "/modulos/clientes_pedidos/cotizaciones/index.js"

        ];


        $this->views->getView(
            $this,
            "index",
            $data
        );
    }


    /* ============================================================
       LISTADO
    ============================================================ */

    public function getCotizaciones()
    {
        header(
            "Content-Type: application/json; charset=utf-8"
        );


        try {

            // if (
            //     empty($_SESSION['permisosMod']['r'])
            // ) {

            //     throw new Exception(
            //         "No tienes permisos para consultar las cotizaciones."
            //     );
            // }


            $buscar =
                trim(
                    $_GET['buscar']
                        ?? ""
                );


            $desde =
                trim(
                    $_GET['desde']
                        ?? ""
                );


            $hasta =
                trim(
                    $_GET['hasta']
                        ?? ""
                );


            $tipo =
                strtoupper(
                    trim(
                        $_GET['tipo']
                            ?? ""
                    )
                );


            $estatus =
                strtoupper(
                    trim(
                        $_GET['estatus']
                            ?? ""
                    )
                );


            $filtros = [

                'buscar' =>
                $buscar,

                'desde' =>
                $desde,

                'hasta' =>
                $hasta,

                'tipo' =>
                $tipo,

                'estatus' =>
                $estatus

            ];


            $cotizaciones =
                $this->model
                ->selectCotizaciones(
                    $filtros
                );


            echo json_encode(
                [
                    'status' =>
                    true,

                    'message' =>
                    'Cotizaciones consultadas correctamente.',

                    'data' =>
                    $cotizaciones
                ],
                JSON_UNESCAPED_UNICODE
            );
        } catch (Throwable $e) {

            http_response_code(
                400
            );


            echo json_encode(
                [
                    'status' =>
                    false,

                    'message' =>
                    $e->getMessage(),

                    'data' =>
                    []
                ],
                JSON_UNESCAPED_UNICODE
            );
        }


        exit;
    }


    /* ============================================================
       DASHBOARD
    ============================================================ */

    public function getDashboard()
    {
        header(
            "Content-Type: application/json; charset=utf-8"
        );


        try {

            // if (
            //     empty($_SESSION['permisosMod']['r'])
            // ) {

            //     throw new Exception(
            //         "No tienes permisos para consultar esta información."
            //     );
            // }


            $dashboard =
                $this->model
                ->selectDashboardCotizaciones();


            echo json_encode(
                [
                    'status' =>
                    true,

                    'message' =>
                    'Indicadores consultados correctamente.',

                    'data' =>
                    $dashboard
                ],
                JSON_UNESCAPED_UNICODE
            );
        } catch (Throwable $e) {

            http_response_code(
                400
            );


            echo json_encode(
                [
                    'status' =>
                    false,

                    'message' =>
                    $e->getMessage(),

                    'data' =>
                    []
                ],
                JSON_UNESCAPED_UNICODE
            );
        }


        exit;
    }
}
