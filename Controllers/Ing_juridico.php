<?php

/**
 * Ingeniería - Perfil Jurídico.
 *
 * Pantalla mínima para que el área Jurídica cargue las certificaciones
 * (archivo, número, fechas, estado y observaciones) de cada configuración de
 * vehículo y NADA MÁS. Tiene su propio módulo de permisos (ING_JURIDICO) para
 * que el rol de Jurídico no necesite acceso a Configuraciones ni a ningún otro
 * módulo de Ingeniería.
 *
 *   r = ver la lista y las certificaciones de cada configuración
 *   u = guardar certificaciones / adjuntar archivos
 *
 * Jurídico también puede marcar/desmarcar "Obligatoria" de cada certificación.
 */
class Ing_juridico extends Controllers
{
    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        getPermisos(ING_JURIDICO);
    }

    public function Ing_juridico()
    {
        if (empty($_SESSION['permisosMod']['r'])) {
            header("Location:" . base_url() . '/dashboard');
            die();
        }
        $data['page_tag'] = "Certificaciones - Jurídico";
        $data['page_title'] = "Certificaciones - Jurídico";
        $data['page_name'] = "ing_juridico";
        $data['page_functions_js'] = "functions_ing_juridico.js";
        $this->views->getView($this, "ing_juridico", $data);
    }

    public function getConfiguraciones()
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $arrData = $this->model->selectConfiguraciones();
            for ($i = 0; $i < count($arrData); $i++) {
                // Avance = de las certificaciones OBLIGATORIAS, cuántas ya tienen archivo.
                $total = intval($arrData[$i]['total_certificaciones']);
                $oblig = intval($arrData[$i]['obligatorias']);
                $subidas = intval($arrData[$i]['obligatorias_con_archivo']);
                if ($oblig === 0) {
                    $badge = '<span class="badge bg-secondary">Sin obligatorias</span>';
                } else {
                    $clase = $subidas >= $oblig ? 'bg-success' : ($subidas > 0 ? 'bg-warning text-dark' : 'bg-danger');
                    $badge = '<span class="badge ' . $clase . '">' . $subidas . ' de ' . $oblig . ' obligatorias con archivo</span>';
                }
                $arrData[$i]['avance_label'] = $badge . '<div class="text-muted fs-11 mt-1">' . $oblig . ' obligatorias de ' . $total . ' en catálogo</div>';
                $arrData[$i]['estado_label'] = $this->estadoBadge($arrData[$i]['estado']);
                $arrData[$i]['unidad_label'] = trim(($arrData[$i]['nombre_unidad'] ?? '') . ' ' . ($arrData[$i]['version'] ?? ''));
                $arrData[$i]['options'] = '<div class="text-center"><button class="btn btn-sm btn-soft-primary" title="Cargar certificaciones" onClick="fntCertificaciones('
                    . intval($arrData[$i]['id_configuracion']) . ')"><i class="ri-award-line align-bottom"></i> Certificaciones</button></div>';
            }
            echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function getCertificaciones($idConfiguracion)
    {
        if (!empty($_SESSION['permisosMod']['r'])) {
            $model = new Ing_configuracionesModel();
            echo json_encode($model->selectCertificacionesConfiguracion(intval($idConfiguracion)), JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    public function setCertificaciones()
    {
        if ($_POST) {
            $intIdConfiguracion = intval($_POST['id_configuracion'] ?? 0);
            if (empty($_SESSION['permisosMod']['u'])) {
                $arrResponse = array('status' => false, 'msg' => 'No tienes permiso para esta acción.');
            } else if ($intIdConfiguracion <= 0 || !$this->model->existeConfiguracion($intIdConfiguracion)) {
                $arrResponse = array('status' => false, 'msg' => 'La configuración no existe.');
            } else {
                $certificaciones = json_decode($_POST['certificaciones'] ?? '[]', true);
                $arrResponse = (new Ing_certificacionesService())->guardar(
                    $intIdConfiguracion,
                    is_array($certificaciones) ? $certificaciones : [],
                    $_FILES,
                    $_SESSION['userData']['idusuario'] ?? null,
                    'Jurídico'
                );
                unset($arrResponse['evaluacion']);
            }
            echo json_encode($arrResponse, JSON_UNESCAPED_UNICODE);
        }
        die();
    }

    private function estadoBadge($estado)
    {
        $map = [
            'BORRADOR' => ['bg-secondary', 'Borrador'],
            'EN_REVISION' => ['bg-info', 'En revisión'],
            'EN_CORRECCION' => ['bg-warning text-dark', 'En corrección'],
            'AUTORIZADO' => ['bg-success', 'Autorizado'],
            'BLOQUEADO' => ['bg-danger', 'Bloqueado'],
            'OBSOLETO' => ['bg-dark', 'Obsoleto'],
        ];
        $item = $map[$estado] ?? ['bg-secondary', $estado];
        return '<span class="badge ' . $item[0] . '">' . $item[1] . '</span>';
    }
}
