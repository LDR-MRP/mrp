<?php
class Ing_bitacora extends Controllers
{
    // Módulos de Ingeniería cuyo permiso de lectura da acceso a la bitácora
    // (es una vista de solo lectura que cruza todos los catálogos).
    private const MODULOS_INGENIERIA = [
        ING_MODELOS,
        ING_CONFIGURACIONES,
        ING_MOTORES,
        ING_TRANSMISIONES,
        ING_CERTIFICACIONES,
        ING_ESPECIFICACIONES,
    ];

    public function __construct()
    {
        parent::__construct();
        session_start();
        if (empty($_SESSION['login'])) {
            header('Location: ' . base_url() . '/login');
            die();
        }
        // Carga $_SESSION['permisos'] con TODOS los módulos del rol
        // (getPermisos siempre deja el catálogo completo, no solo el id que se le pasa).
        getPermisos(ING_CONFIGURACIONES);
    }

    private function tieneAccesoLectura(): bool
    {
        foreach (self::MODULOS_INGENIERIA as $idmodulo) {
            if (!empty($_SESSION['permisos'][$idmodulo]['r'])) {
                return true;
            }
        }
        return false;
    }

    public function Ing_bitacora()
    {
        if (!$this->tieneAccesoLectura()) {
            header("Location:" . base_url() . '/dashboard');
            die();
        }
        $data['page_tag'] = "Bitácora";
        $data['page_title'] = "Bitácora de Ingeniería";
        $data['page_name'] = "Bitácora de Ingeniería";
        $data['page_functions_js'] = "functions_ing_bitacora.js";
        $data['tablas'] = Ing_bitacoraModel::TABLAS;
        $this->views->getView($this, "ing_bitacora", $data);
    }

    public function getBitacora()
    {
        if (!$this->tieneAccesoLectura()) {
            die();
        }

        $filters = [
            'nombre_tabla' => strClean($_GET['tabla'] ?? ''),
            'usuarioid'    => $_GET['usuario'] ?? '',
            'fecha_desde'  => strClean($_GET['fecha_desde'] ?? ''),
            'fecha_hasta'  => strClean($_GET['fecha_hasta'] ?? ''),
            'limit'        => 500,
        ];

        $arrData = $this->model->listIngenieria($filters);

        foreach ($arrData as &$row) {
            $row['tabla_label'] = Ing_bitacoraModel::TABLAS[$row['nombre_tabla']] ?? $row['nombre_tabla'];

            $row['accion_label'] = match ($row['accion']) {
                'creacion'      => '<span class="badge bg-success">Alta</span>',
                'actualizacion' => '<span class="badge bg-info">Actualización</span>',
                default         => '<span class="badge bg-secondary">' . htmlspecialchars((string) $row['accion'], ENT_QUOTES) . '</span>',
            };
        }
        unset($row);

        echo json_encode($arrData, JSON_UNESCAPED_UNICODE);
        die();
    }

    public function getSelectUsuariosBitacora()
    {
        if (!$this->tieneAccesoLectura()) {
            die();
        }

        $htmlOptions = '<option value="">--Todos--</option>';
        $arrData = $this->model->selectUsuariosConMovimientos();
        foreach ($arrData as $row) {
            $htmlOptions .= '<option value="' . $row['idusuario'] . '">' . htmlspecialchars($row['nombre'], ENT_QUOTES) . '</option>';
        }
        echo $htmlOptions;
        die();
    }
}
