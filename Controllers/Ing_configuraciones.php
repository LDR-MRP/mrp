<?php
class Ing_configuraciones extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_CONFIGURACIONES);
    }

    // Estados que se pueden capturar manualmente. AUTORIZADO y BLOQUEADO
    // quedan reservados para el motor de reglas automático (Épica 3).
    const ESTADOS_MANUALES = ['BORRADOR', 'EN_REVISION', 'EN_CORRECCION', 'OBSOLETO'];

    public function Ing_configuraciones()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
        }
        $data['page_tag'] = "Configuraciones";
        $data['page_title'] = "Configuraciones de vehículo";
        $data['page_name'] = "Configuraciones";
        $data['page_functions_js'] = "functions_ing_configuraciones.js";
        $this->views->getView($this, "ing_configuraciones", $data);
    }

    public function getConfiguraciones()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectConfiguraciones();
            for ($i = 0; $i < count($arrData); $i++) {
                $arrData[$i]['tipo_origen_label'] = $arrData[$i]['tipo_origen'] == 'NACIONAL' ? 'Nacional' : 'Importado';
                $arrData[$i]['motor_label'] = trim(($arrData[$i]['motor_fabricante'] ?? ''));
                $arrData[$i]['transmision_label'] = trim(($arrData[$i]['transmision_fabricante'] ?? '') . ' ' . ($arrData[$i]['transmision_modelo'] ?? ''));
                $arrData[$i]['estado_label'] = $this->estadoBadge($arrData[$i]['estado']);

                $btnEdit = '';
                $btnDelete = '';
                if (!empty($_SESSION['permisosMod']['u'])) {
                    $btnEdit = '<button class="btn btn-sm btn-soft-warning edit-list" title="Editar configuración" onClick="fntEditInfo(' . $arrData[$i]['id_configuracion'] . ')"><i class="ri-pencil-fill align-bottom"></i></button>';
                }
                if (!empty($_SESSION['permisosMod']['d'])) {
                    $btnDelete = '<button class="btn btn-sm btn-soft-danger remove-list" title="Eliminar configuración" onClick="fntDelInfo(' . $arrData[$i]['id_configuracion'] . ')"><i class="ri-delete-bin-5-fill align-bottom"></i></button>';
                }
                $arrData[$i]['options'] = '<div class="text-center">' . $btnEdit . ' ' . $btnDelete . '</div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getConfiguracion($id)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($id);
            $arrData = $this->model->selectConfiguracion($intId);
            if (empty($arrData)) {
                $arrResponse = array('status' => false, 'msg' => 'Datos no encontrados.');
            } else {
                $arrResponse = array('status' => true, 'data' => $arrData);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setConfiguracion()
    {
        if ($_POST) {
            if (empty($_POST['id-sublinea-select']) || empty($_POST['nombre-unidad-input']) || empty($_POST['clave-vehicular-input'])) {
                $arrResponse = array('status' => false, 'msg' => 'El modelo, el nombre de unidad y la clave vehicular son obligatorios.');
            } else {
                $intIdConfiguracion = intval($_POST['id_configuracion']);
                $request = false;
                $option = 1;

                $estado = strClean($_POST['estado-select'] ?? 'BORRADOR');
                if (!in_array($estado, self::ESTADOS_MANUALES)) {
                    $estado = 'BORRADOR';
                }

                $data = [
                    'id_sublineaproducto' => intval($_POST['id-sublinea-select']),
                    'tipo_origen'         => in_array(($_POST['tipo-origen-select'] ?? ''), ['NACIONAL', 'IMPORTADO']) ? $_POST['tipo-origen-select'] : 'NACIONAL',
                    'nombre_unidad'       => strClean($_POST['nombre-unidad-input']),
                    'nombre_comercial'    => strClean($_POST['nombre-comercial-input'] ?? ''),
                    'codigo_modelo'       => strClean($_POST['codigo-modelo-input'] ?? ''),
                    'clave_vehicular'     => strClean($_POST['clave-vehicular-input']),
                    'peso_bruto'          => $_POST['peso-bruto-input'] !== '' ? floatval($_POST['peso-bruto-input']) : null,
                    'nivel_emisiones'     => strClean($_POST['nivel-emisiones-input'] ?? ''),
                    'id_motor'            => !empty($_POST['id-motor-select']) ? intval($_POST['id-motor-select']) : null,
                    'id_transmision'      => !empty($_POST['id-transmision-select']) ? intval($_POST['id-transmision-select']) : null,
                    'combustible'         => strClean($_POST['combustible-input'] ?? ''),
                    'estado'              => $estado,
                    'version'             => strClean($_POST['version-input'] ?? ''),
                ];

                if ($intIdConfiguracion == 0) {
                    if (!empty($_SESSION['permisosMod']['w'])) {
                        $request = $this->model->insertConfiguracion($data);
                        $option = 1;
                    }
                } else {
                    if (!empty($_SESSION['permisosMod']['u'])) {
                        $request = $this->model->updateConfiguracion($intIdConfiguracion, $data);
                        $option = 2;
                    }
                }

                if ($request === 'exist') {
                    $arrResponse = array('status' => false, 'msg' => '¡Atención! Ya existe una configuración con ese modelo, origen y clave vehicular.');
                } else if (!empty($request)) {
                    $idConfiguracionGuardada = $option == 1 ? intval($request) : $intIdConfiguracion;
                    $arrResponse = $option == 1
                        ? array('status' => true, 'msg' => 'La configuración se registró exitosamente.', 'tipo' => 'insert', 'id_configuracion' => $idConfiguracionGuardada)
                        : array('status' => true, 'msg' => 'La configuración se actualizó correctamente.', 'tipo' => 'update', 'id_configuracion' => $idConfiguracionGuardada);

                    $evaluacion = (new Ing_reglasService())->evaluarConfiguracion($idConfiguracionGuardada, 'GUARDADO');
                    if ($evaluacion['cambio']) {
                        $arrResponse['msg'] .= ' ' . $this->mensajeEvaluacion($evaluacion);
                    }
                    $arrResponse['evaluacion'] = $evaluacion;
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'No fue posible guardar la configuración.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function delConfiguracion()
    {
        if ($_POST) {
            if (!empty($_SESSION['permisosMod']['d'])) {
                $intId = intval($_POST['id_configuracion']);
                $request = $this->model->deleteConfiguracion($intId);
                $arrResponse = $request
                    ? array('status' => true, 'msg' => 'La configuración fue eliminada satisfactoriamente.')
                    : array('status' => false, 'msg' => 'Error al eliminar la configuración.');
                echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    // ------------------------------------------------------------------
    // ESPECIFICACIONES TÉCNICAS
    // ------------------------------------------------------------------
    public function getEspecificaciones($idConfiguracion)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectEspecificacionesConfiguracion(intval($idConfiguracion));
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setEspecificaciones()
    {
        if ($_POST) {
            $intIdConfiguracion = intval($_POST['id_configuracion'] ?? 0);
            if ($intIdConfiguracion <= 0) {
                $arrResponse = array('status' => false, 'msg' => 'Primero guarda los datos generales de la configuración.');
            } else if (empty($_SESSION['permisosMod']['u']) && empty($_SESSION['permisosMod']['w'])) {
                $arrResponse = array('status' => false, 'msg' => 'No tienes permiso para esta acción.');
            } else {
                $valores = $_POST['valor'] ?? [];
                $ok = true;
                foreach ($valores as $idEspecificacion => $valor) {
                    $result = $this->model->upsertEspecificacionValor($intIdConfiguracion, intval($idEspecificacion), strClean($valor));
                    if (!$result) {
                        $ok = false;
                    }
                }
                if ($ok) {
                    $arrResponse = array('status' => true, 'msg' => 'Las especificaciones se guardaron correctamente.');
                    $evaluacion = (new Ing_reglasService())->evaluarConfiguracion($intIdConfiguracion, 'GUARDADO');
                    if ($evaluacion['cambio']) {
                        $arrResponse['msg'] .= ' ' . $this->mensajeEvaluacion($evaluacion);
                    }
                    $arrResponse['evaluacion'] = $evaluacion;
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'Ocurrió un error al guardar alguna de las especificaciones.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // ------------------------------------------------------------------
    // CERTIFICACIONES
    // ------------------------------------------------------------------
    public function getCertificaciones($idConfiguracion)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectCertificacionesConfiguracion(intval($idConfiguracion));
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setCertificaciones()
    {
        if ($_POST) {
            $intIdConfiguracion = intval($_POST['id_configuracion'] ?? 0);
            if ($intIdConfiguracion <= 0) {
                $arrResponse = array('status' => false, 'msg' => 'Primero guarda los datos generales de la configuración.');
            } else if (empty($_SESSION['permisosMod']['u']) && empty($_SESSION['permisosMod']['w'])) {
                $arrResponse = array('status' => false, 'msg' => 'No tienes permiso para esta acción.');
            } else {
                $certificaciones = json_decode($_POST['certificaciones'] ?? '[]', true);
                $ok = true;
                if (is_array($certificaciones)) {
                    foreach ($certificaciones as $cert) {
                        $data = [
                            'obligatoria'        => !empty($cert['obligatoria']) ? 1 : 0,
                            'estado'             => strClean($cert['estado'] ?? 'PENDIENTE'),
                            'numero_certificado' => strClean($cert['numero_certificado'] ?? ''),
                            'fecha_emision'      => !empty($cert['fecha_emision']) ? $cert['fecha_emision'] : null,
                            'fecha_inicio'       => !empty($cert['fecha_inicio']) ? $cert['fecha_inicio'] : null,
                            'fecha_vencimiento'  => !empty($cert['fecha_vencimiento']) ? $cert['fecha_vencimiento'] : null,
                            'observaciones'      => strClean($cert['observaciones'] ?? ''),
                        ];
                        $result = $this->model->upsertCertificacionConfiguracion($intIdConfiguracion, intval($cert['id_certificacion']), $data);
                        if (!$result) {
                            $ok = false;
                        }
                    }
                }
                if ($ok) {
                    $arrResponse = array('status' => true, 'msg' => 'Las certificaciones se guardaron correctamente.');
                    $evaluacion = (new Ing_reglasService())->evaluarConfiguracion($intIdConfiguracion, 'GUARDADO');
                    if ($evaluacion['cambio']) {
                        $arrResponse['msg'] .= ' ' . $this->mensajeEvaluacion($evaluacion);
                    }
                    $arrResponse['evaluacion'] = $evaluacion;
                } else {
                    $arrResponse = array('status' => false, 'msg' => 'Ocurrió un error al guardar alguna de las certificaciones.');
                }
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // ------------------------------------------------------------------
    // MOTOR DE REGLAS (Épica 3)
    // ------------------------------------------------------------------
    public function getHistorial($idConfiguracion)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $reglasModel = new Ing_reglasModel();
            $arrData = $reglasModel->selectLogConfiguracion(intval($idConfiguracion));
            foreach ($arrData as &$row) {
                $row['origen_label'] = [
                    'GUARDADO'          => 'Al guardar',
                    'MANUAL'            => 'Reevaluación manual',
                    'TAREA_PROGRAMADA'  => 'Tarea programada',
                ][$row['origen']] ?? $row['origen'];
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // Permite forzar la reevaluación de todas las configuraciones vigilables
    // sin esperar a la tarea programada diaria (útil mientras esta se configura
    // en el servidor, o para revisar el efecto de un cambio en un catálogo).
    public function ejecutarReglasManual()
    {
        if ($_POST) {
            if (empty($_SESSION['permisosMod']['u'])) {
                $arrResponse = array('status' => false, 'msg' => 'No tienes permiso para esta acción.');
            } else {
                $resumen = (new Ing_reglasService())->evaluarTodas('MANUAL');
                $arrResponse = array(
                    'status' => true,
                    'msg' => 'Reevaluación completada: ' . $resumen['autorizadas'] . ' autorizada(s), '
                        . $resumen['bloqueadas'] . ' bloqueada(s), ' . $resumen['correccion'] . ' en corrección, '
                        . $resumen['sin_cambio'] . ' sin cambio (de ' . $resumen['total'] . ' evaluadas).',
                    'resumen' => $resumen,
                );
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // ------------------------------------------------------------------
    // PROGRESO (línea de proceso: datos generales / especificaciones /
    // certificaciones / autorizado). Solo lectura, reutiliza el motor de
    // reglas de la Épica 3 (Ing_reglasService) sin cambiar el estado.
    // ------------------------------------------------------------------
    public function getProgreso($idConfiguracion)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $progreso = (new Ing_reglasService())->evaluarProgreso(intval($idConfiguracion));

            if (empty($progreso)) {
                echo json_encode(array('status' => false, 'msg' => 'Datos no encontrados.'), JSON_UNESCAPED_UNICODE);
                die();
            }

            $progreso['status'] = true;
            $progreso['estado_label'] = $this->estadoBadge($progreso['estado']);
            echo json_encode($progreso, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    // ------------------------------------------------------------------
    // ALTA EN INVENTARIO (WMS) — botón en la ficha técnica cuando AUTORIZADO.
    // Crea el SKU en wms_inventario (tipo_elemento='P'); el VIN de cada
    // unidad física se genera después en el módulo VIN (Inv_series).
    // ------------------------------------------------------------------
    public function setAltaInventario()
    {
        if ($_POST) {
            if (empty($_SESSION['permisosMod']['w'])) {
                echo json_encode(array('status' => false, 'msg' => 'No tienes permiso para esta acción.'), JSON_UNESCAPED_UNICODE);
                die();
            }

            $intId = intval($_POST['id_configuracion'] ?? 0);
            if ($intId == 0) {
                echo json_encode(array('status' => false, 'msg' => 'Configuración no válida.'), JSON_UNESCAPED_UNICODE);
                die();
            }

            $config = $this->model->selectConfiguracionParaAltaInventario($intId);
            if (empty($config)) {
                echo json_encode(array('status' => false, 'msg' => 'La configuración no existe.'), JSON_UNESCAPED_UNICODE);
                die();
            }

            if (!empty($config['id_inventario'])) {
                $sku = $this->model->selectSkuInventario((int) $config['id_inventario']);
                echo json_encode(array(
                    'status'        => true,
                    'msg'           => 'Esta configuración ya estaba dada de alta en inventario.',
                    'cve_articulo'  => $sku['cve_articulo'] ?? null,
                    'id_inventario' => (int) $config['id_inventario'],
                ), JSON_UNESCAPED_UNICODE);
                die();
            }

            if ($config['estado'] !== 'AUTORIZADO') {
                echo json_encode(array('status' => false, 'msg' => 'Solo se puede dar de alta en inventario una configuración AUTORIZADA. Estado actual: ' . $config['estado'] . '.'), JSON_UNESCAPED_UNICODE);
                die();
            }

            $sku = $this->model->generarSkuUnico($config);

            $pdo = $this->model->getConexion();
            $pdo->beginTransaction();
            try {
                $idInventario = $this->model->crearInventarioParaConfiguracion($config, $sku);
                if (empty($idInventario)) {
                    throw new Exception('No fue posible crear el artículo de inventario.');
                }

                $ok = $this->model->vincularInventarioConfiguracion($intId, $idInventario);
                if (!$ok) {
                    throw new Exception('No fue posible vincular el artículo de inventario a la configuración.');
                }

                $pdo->commit();

                $skuRow = $this->model->selectSkuInventario($idInventario);
                echo json_encode(array(
                    'status'        => true,
                    'msg'           => 'Configuración dada de alta en inventario correctamente.',
                    'cve_articulo'  => $skuRow['cve_articulo'] ?? null,
                    'id_inventario' => $idInventario,
                ), JSON_UNESCAPED_UNICODE);
            } catch (Exception $e) {
                $pdo->rollBack();
                echo json_encode(array('status' => false, 'msg' => 'Error al dar de alta en inventario: ' . $e->getMessage()), JSON_UNESCAPED_UNICODE);
            }
        }
        die();
    }

    // ------------------------------------------------------------------
    // FICHA TÉCNICA (vista de solo lectura con todo lo capturado)
    // ------------------------------------------------------------------
    public function getFichaTecnica($idConfiguracion)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $intId = intval($idConfiguracion);
            $config = $this->model->selectFichaTecnica($intId);

            if (empty($config)) {
                echo json_encode(array('status' => false, 'msg' => 'Datos no encontrados.'), JSON_UNESCAPED_UNICODE);
                die();
            }

            $config['tipo_origen_label'] = $config['tipo_origen'] == 'NACIONAL' ? 'Nacional' : 'Importado';
            $config['estado_label'] = $this->estadoBadge($config['estado']);

            $especificaciones = $this->model->selectEspecificacionesConfiguracion($intId);
            $grupos = [];
            foreach ($especificaciones as $esp) {
                $grupos[$esp['categoria']][] = $esp;
            }

            $certificaciones = $this->model->selectCertificacionesConfiguracion($intId);
            foreach ($certificaciones as &$cert) {
                $cert['estado_label'] = $this->certificacionBadge($cert['estado'] ?? null);
            }
            unset($cert);

            $arrResponse = array(
                'status'           => true,
                'configuracion'    => $config,
                'especificaciones' => $grupos,
                'certificaciones'  => $certificaciones,
            );
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    private function mensajeEvaluacion(array $evaluacion): string
    {
        switch ($evaluacion['estado_nuevo']) {
            case 'AUTORIZADO':
                return 'El sistema la autorizó automáticamente al cumplir todas las reglas.';
            case 'BLOQUEADO':
                return 'El sistema la bloqueó automáticamente: ' . implode('; ', $evaluacion['motivos']) . '.';
            case 'EN_CORRECCION':
                return 'Quedó en corrección: ' . implode('; ', $evaluacion['motivos']) . '.';
            default:
                return '';
        }
    }

    private function estadoBadge($estado)
    {
        $map = [
            'BORRADOR'     => 'bg-secondary',
            'EN_REVISION'  => 'bg-info',
            'EN_CORRECCION' => 'bg-warning text-dark',
            'AUTORIZADO'   => 'bg-success',
            'BLOQUEADO'    => 'bg-danger',
            'OBSOLETO'     => 'bg-dark',
        ];
        $labels = [
            'BORRADOR'     => 'Borrador',
            'EN_REVISION'  => 'En revisión',
            'EN_CORRECCION' => 'En corrección',
            'AUTORIZADO'   => 'Autorizado',
            'BLOQUEADO'    => 'Bloqueado',
            'OBSOLETO'     => 'Obsoleto',
        ];
        $class = $map[$estado] ?? 'bg-secondary';
        $label = $labels[$estado] ?? $estado;
        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }

    private function certificacionBadge($estado)
    {
        $map = [
            'NO_APLICA'     => 'bg-secondary',
            'PENDIENTE'     => 'bg-secondary',
            'EN_PROCESO'    => 'bg-info',
            'VIGENTE'       => 'bg-success',
            'POR_VENCER'    => 'bg-warning text-dark',
            'VENCIDA'       => 'bg-danger',
            'POR_REVISAR'   => 'bg-warning text-dark',
            'NO_DISPONIBLE' => 'bg-dark',
        ];
        $labels = [
            'NO_APLICA'     => 'No aplica',
            'PENDIENTE'     => 'Pendiente',
            'EN_PROCESO'    => 'En proceso',
            'VIGENTE'       => 'Vigente',
            'POR_VENCER'    => 'Por vencer',
            'VENCIDA'       => 'Vencida',
            'POR_REVISAR'   => 'Por revisar',
            'NO_DISPONIBLE' => 'No disponible',
        ];
        if (empty($estado)) {
            return '<span class="badge bg-secondary">Sin capturar</span>';
        }
        $class = $map[$estado] ?? 'bg-secondary';
        $label = $labels[$estado] ?? $estado;
        return '<span class="badge ' . $class . '">' . $label . '</span>';
    }
}
