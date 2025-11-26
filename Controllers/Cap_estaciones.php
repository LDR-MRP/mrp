<?php
class Cap_estaciones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        //session_regenerate_id(true);
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(MCLINEAS);
    }

    public function Cap_estaciones()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Estaciones";
        $data['page_title'] = "Estaciones";
        $data['page_name'] = "bom";
        $data['page_functions_js'] = "functions_cap_estaciones.js";
        $this->views->getView($this, "cap_estaciones", $data);
    }

    //CAPTURAR UNA NUEVA LÍNEA
    // public function setEstacion()
    // {
    // 	if ($_POST) {
    // 		if (
    // 			empty($_POST['nombre-linea-input'])
    // 			|| empty($_POST['proceso-linea-input'])
    // 			|| empty($_POST['estandar-input'])
    // 			|| empty($_POST['unidad-medida-input'])
    // 			|| empty($_POST['merma-fija-input'])
    // 			|| empty($_POST['merma-proceso-input'])
    // 			|| empty($_POST['tiempo-ajuste-input'])
    // 			|| empty($_POST['unidad-entrada-input'])
    // 			|| empty($_POST['unidad-salida-input'])
    // 			|| empty($_POST['usd-input'])
    // 			|| empty($_POST['mx-input'])
    // 			|| empty($_POST['estado-select'])
    // 			|| empty($_POST['descripcion-linea-textarea'])

    // 		) {
    // 			$arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
    // 		} else {

    // 			$intIdestacion = intval($_POST['idestacion']);
    // 			$strNombre = strClean($_POST['nombre-linea-input']);
    // 			$strProceso = strClean($_POST['proceso-linea-input']);
    // 			$strEstandar = strClean($_POST['estandar-input']);
    // 			$strUnMedida = strClean($_POST['unidad-medida-input']);
    // 			$strMermaFija = strClean($_POST['merma-fija-input']);
    // 			$strMermaProceso = strClean($_POST['merma-proceso-input']);
    // 			$strTiemajuste = strClean($_POST['tiempo-ajuste-inpu']);
    // 			$strUnEntrada = strClean($_POST['unidad-entrada-input']);
    // 			$strUnSalida = strClean($_POST['unidad-salida-input']);
    // 			$strUSD = strClean($_POST['usd-input']);
    // 			$strMX = strClean($_POST['mx-input']);
    // 			$intEstatus = strClean($_POST['estado-select']);
    // 			$strDescripcion = strClean($_POST['descripcion-linea-textarea']);

    // 			if ($intIdestacion == 0) {

    // 				$claveUnica = $this->model->generarClave();

    // 					//Crear
    // 					if($_SESSION['permisosMod']['w']){
    // 						$request_cateria = $this->model->insertEstacion($claveUnica,$strNombre,$strProceso,$strEstandar,$strUnMedida,$strMermaFija,$strMermaProceso,$strTiemajuste,$strUnEntrada,$strUnSalida,$strUSD,$strMX,$intEstatus,$strDescripcion);
    // 						$option = 1;
    // 					}

    // 			}
    // 		}
    // 	}
    // }


    public function setEstacion()
    {


        if ($_POST) {
            if (
                empty($_POST['nombre-estacion-input'])
                || empty($_POST['listLineas'])
                || empty($_POST['estado-select'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
            } else {

                $intIdEstacion = intval($_POST['idestacion']);
                $planta = intval($_POST['listPlantas']);
                $linea = intval($_POST['listLineas']);
                $nombre_estacion = strClean($_POST['nombre-estacion-input']);
                $proceso = strClean($_POST['proceso-estacion-input']);
                $estandar = strClean($_POST['estandar-input']);
                $unidaddmedida = strClean($_POST['unidad-medida-select']);
                $tiempoajuste = strClean($_POST['tiempo-ajuste-input']);
                $mxinput = strClean($_POST['mx-input']);
                $descripcion = strClean($_POST['descripcion-estacion-textarea']);
                $estado = intval($_POST['estado-select']);

                $requiereHerramientas = intval($_POST['requiere_herramientas']);




                if ($intIdEstacion == 0) {

                    $claveUnica = $this->model->generarClave($linea);
                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    if ($_SESSION['permisosMod']['w']) {
                        $request_estacion = $this->model->insertEstacion($claveUnica, $planta, $linea, $nombre_estacion, $proceso, $estandar, $unidaddmedida, $tiempoajuste, $mxinput, $descripcion, $fecha_creacion, $requiereHerramientas, $estado);
                        $option = 1;
                    }

                } else {
                    //Actualizar
                    if ($_SESSION['permisosMod']['u']) {
                        $request_estacion = $this->model->updateEstacion($intIdEstacion, $planta, $linea, $nombre_estacion, $proceso, $estandar, $unidaddmedida, $tiempoajuste, $mxinput, $descripcion, $requiereHerramientas, $estado);
                        $option = 2;
                    }
                }


                if ($request_estacion === 'exist') {

                    $arrResponse = array('status' => false, 'msg' => '¡Atención! La estación ya existe.');

                } else if ($request_estacion > 0) {  

                    if ($option == 1) {
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }

                } else {

                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getEstaciones()
    {


        if ($_SESSION['permisosMod']['r']) {
            $arrData = $this->model->selectEstaciones();
            for ($i = 0; $i < count($arrData); $i++) {

                $btnView = '';
                $btnEdit = '';
                $btnDelete = '';
                $btnaddMantemimiento = '';

                // Guardamos el estado numérico ORIGINAL
                $estado = intval($arrData[$i]['estado']);

                $mantenimiento = intval($arrData[$i]['estacion_mantenimiento']);

                // ESTATUS
                if ($estado == 2) {
                    $arrData[$i]['estado'] = '<span class="badge bg-success">Activo</span>';
                } else if ($estado == 1) {
                    $arrData[$i]['estado'] = '<span class="badge bg-danger">Inactivo</span>';
                } else if ($estado == 3) {
                    $arrData[$i]['estado'] = '<span class="badge bg-warning">En mantenimiento</span>';
                }


                // ESTATUS
                if ($mantenimiento == 1) {
                    // Sin mantenimiento
                    $arrData[$i]['estacion_mantenimiento'] =
                        '<span class="badge bg-warning-subtle text-warning text-uppercase">Sin mantenimiento</span>';

                } else if ($mantenimiento == 2) {
                    // Programado
                    $arrData[$i]['estacion_mantenimiento'] =
                        '<span class="badge bg-info-subtle text-info text-uppercase">Programado</span>';

                } else if ($mantenimiento == 3) {
                    // En proceso
                    $arrData[$i]['estacion_mantenimiento'] =
                        '<span class="badge bg-success-subtle text-success text-uppercase">En proceso</span>';

                } else if ($mantenimiento == 4) {
                    // Finalizado
                    $arrData[$i]['estacion_mantenimiento'] =
                        '<span class="badge bg-success-subtle text-success text-uppercase">Finalizado</span>';

                } else if ($mantenimiento == 5) {
                    // Cancelado
                    $arrData[$i]['estacion_mantenimiento'] =
                        '<span class="badge bg-danger-subtle text-danger text-uppercase">Cancelado</span>';
                }






                if ($_SESSION['permisosMod']['r']) {
                    $btnView = '<button class="btn btn-sm btn-soft-info edit-list" title="Ver Estación" onClick="fntViewLinea(' . $arrData[$i]['idestacion'] . ')"><i class="ri-eye-fill align-bottom text-muted"></i></button>';
                }

                if ($_SESSION['permisosMod']['u']) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar Estación" onClick="fntEditInfo(' . $arrData[$i]['idestacion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';

                    // Aquí usamos el estado numérico ORIGINAL
                    if ($mantenimiento == 1 || $mantenimiento == 4 || $mantenimiento == 5) {
                        $btnaddMantemimiento = '<button class="btn btn-sm btn-soft-success edit-list" title="Agregar Mantenimiento" onClick="fntAddMantenimiento(' . $arrData[$i]['idestacion'] . ')"><i class="ri-settings-3-fill fs-16"></i></button>';

                    } else {
                        $btnaddMantemimiento = '<button class="btn btn-sm btn-soft-success edit-list" title="Editar Mantenimiento" onClick="fnteditMantenimiento(' . $arrData[$i]['id_mantenimiento'] . ')"><i class="ri-settings-3-fill fs-16"></i></button>';

                    }
                }

                if ($_SESSION['permisosMod']['d']) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar Estación" onClick="fntDelInfo(' . $arrData[$i]['idestacion'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }

                $arrData[$i]['options'] = '<div class="text-center">' . $btnView . ' ' . $btnEdit . ' ' . $btnaddMantemimiento . ' ' . $btnDelete . '</div>';
            }

            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }

        die();
    }


    public function getEstacion($idestacion)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intIdestacion = intval($idestacion);
            if ($intIdestacion > 0) {
                $arrData = $this->model->selectEstacion($intIdestacion);
                if (empty($arrData)) {
                    $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
                } else {

                    $arrResponse = array('status' => true, 'data' => $arrData);
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    public function delEstacion()
    {
        if ($_POST) {
            if ($_SESSION['permisosMod']['d']) {
                $intIdestacion = intval($_POST['idestacion']);
                $requestDelete = $this->model->deleteEstacion($intIdestacion);
                if ($requestDelete) {
                    $arrResponse = array('status' => true, 'msg' => 'El registro fue eliminado satisfactoriamente.');
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'Error al eliminar el usuario.');
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    ////////////////////////////////////////////////////////////////////////////
    // APARTADO DE MANTENIMIENTO
    ////////////////////////////////////////////////////////////////////////////


    public function setMantenimiento()
    {


        if ($_POST) {
            if (
                empty($_POST['idestacionmto'])
                || empty($_POST['tipo_mantenimiento'])
            ) {
                $arrResponse = array("status" => false, "msg" => 'Datos incorrectos.');
            } else {
                $intIdMantenimiento = intval($_POST['idmantenimiento']);
                $intIdEstacion = intval($_POST['idestacionmto']);

               
               $responsable = strClean($_POST['responsable-mantenimiento-input']);
                $tipoMantenimiento = strClean($_POST['tipo_mantenimiento']);
                $fechaProgramada = strClean($_POST['fecha_programada']);
                $fechaInicio = strClean($_POST['fecha_inicio']);
                $fechaFin = strClean($_POST['fecha_fin']);
                $mantenimiento = intval($_POST['estado-mantenimiento-select']);


                $comentarios = strClean($_POST['comentarios']);



                if ($intIdMantenimiento == 0) {

                    $fecha_creacion = date('Y-m-d H:i:s');

                    //Crear 
                    // if ($_SESSION['permisosMod']['w']) {
                    $request_mantenimiento = $this->model->insertManteminiento($intIdEstacion, $responsable, $tipoMantenimiento, $fechaProgramada, $fechaInicio, $fechaFin, $mantenimiento, $comentarios, $fecha_creacion);
                    $option = 1;


                    // }

                } else {
                    //Actualizar
                    // if ($_SESSION['permisosMod']['u']) {
                    $request_mantenimiento = $this->model->updateMantenimiento($intIdMantenimiento, $responsable, $tipoMantenimiento, $fechaProgramada, $fechaInicio, $fechaFin, $mantenimiento, $comentarios);
                    $option = 2;
                    // }
                }


                if ($request_mantenimiento > 0) {





                    if ($option == 1) {
                        //    $this->model->updateEstacionMantenimiento($intIdEstacion);
                        $arrResponse = array('status' => true, 'msg' => 'La información se ha registrado exitosamente', 'tipo' => 'insert');
                    } else {
                        $arrResponse = array('status' => true, 'msg' => 'La información ha sido actualizada correctamente.', 'tipo' => 'update');
                    }

                } else {

                    $arrResponse = array("status" => false, "msg" => 'No es posible almacenar los datos.');
                }

                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
    }


    public function getMantenimiento($idmantenimiento)
    {
        if ($_SESSION['permisosMod']['r']) {
            $intIdmantenimiento = intval($idmantenimiento);
            if ($intIdmantenimiento > 0) {
                $arrData = $this->model->selectMantenimiento($intIdmantenimiento);
                if (empty($arrData)) {
                    $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
                } else {

                    $arrResponse = array('status' => true, 'data' => $arrData);
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

        public function getMantenimientosByEstacion($idestacion)
    {
        // if ($_SESSION['permisosMod']['r']) {
            $intIdestacion = intval($idestacion);
            if ($intIdestacion > 0) {
                $arrData = $this->model->MantenimientoByEstacion($intIdestacion);
                if (empty($arrData)) {
                    $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
                } else {

                    $arrResponse = array('status' => true, 'data' => $arrData);
                }
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        // }
        die();
    }


    







}


?>