<?php

/**
 * Bitácora de Ingeniería: solo consulta (vista de solo lectura) sobre la
 * tabla global de auditoría (log_audit / trait Auditable), filtrada a las
 * tablas del módulo de Ingeniería.
 *
 * No registra nada aquí: cada modelo de Ingeniería (Ing_modelosModel,
 * Ing_configuracionesModel, Ing_motoresModel, Ing_transmisionesModel,
 * Ing_certificacionesModel, Ing_especificacionesModel) usa el trait
 * Auditable directamente para escribir sus propios registros.
 */
class Ing_bitacoraModel extends LogAuditModel
{
    // Tablas del módulo de Ingeniería que se muestran en la bitácora,
    // junto con la etiqueta legible que se le muestra al usuario.
    public const TABLAS = [
        'ing_modelo_detalle'         => 'Modelos',
        'ing_modelo_configuracion'   => 'Configuraciones',
        'ing_cat_motor'              => 'Motores',
        'ing_cat_transmision'        => 'Transmisiones',
        'ing_cat_certificacion'      => 'Certificaciones (catálogo)',
        'ing_cat_especificacion'     => 'Especificaciones (catálogo)',
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function listIngenieria(array $filters = [])
    {
        // Si piden una tabla específica, la validamos contra el whitelist.
        // Si no, se filtra por TODAS las tablas de Ingeniería.
        if (!empty($filters['nombre_tabla']) && array_key_exists($filters['nombre_tabla'], self::TABLAS)) {
            $filters['nombre_tabla'] = $filters['nombre_tabla'];
        } else {
            $filters['nombre_tabla'] = array_keys(self::TABLAS);
        }

        return $this->list($filters);
    }

    // Usuarios distintos que aparecen en la bitácora de Ingeniería, para
    // alimentar el filtro "Usuario" del listado.
    public function selectUsuariosConMovimientos()
    {
        $tablas = array_map(function ($t) {
            return "'" . addslashes($t) . "'";
        }, array_keys(self::TABLAS));

        $sql = "SELECT DISTINCT
                    usuarios.idusuario,
                    CONCAT(usuarios.nombres, ' ', usuarios.apellidos) AS nombre
                FROM log_audit
                INNER JOIN usuarios ON usuarios.idusuario = log_audit.usuarioid
                WHERE log_audit.nombre_tabla IN (" . implode(',', $tablas) . ")
                ORDER BY nombre";

        return $this->select_all($sql);
    }
}
