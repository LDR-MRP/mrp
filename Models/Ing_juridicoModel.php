<?php

/**
 * Perfil Jurídico de Ingeniería: listado ligero de configuraciones con el
 * avance de carga de certificaciones. La lectura/escritura de las
 * certificaciones en sí la hace Ing_configuracionesModel (vía
 * Ing_certificacionesService), para no duplicar consultas.
 */
class Ing_juridicoModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    public function selectConfiguraciones()
    {
        $sql = "SELECT
                    c.id_configuracion,
                    c.nombre_unidad,
                    c.clave_vehicular,
                    c.version,
                    c.estado,
                    l.descripcion AS segmento,
                    s.descripcion AS modelo,
                    (SELECT COUNT(*) FROM ing_cat_certificacion k
                        WHERE k.activo = 1 AND k.deleted_at IS NULL) AS total_certificaciones,
                    (SELECT COUNT(*) FROM ing_cat_certificacion k
                        LEFT JOIN ing_modelo_certificacion mc
                            ON mc.id_certificacion = k.id_certificacion AND mc.id_configuracion = c.id_configuracion
                        WHERE k.activo = 1 AND k.deleted_at IS NULL
                          AND COALESCE(mc.obligatoria, 1) = 1) AS obligatorias,
                    (SELECT COUNT(*) FROM ing_cat_certificacion k
                        LEFT JOIN ing_modelo_certificacion mc
                            ON mc.id_certificacion = k.id_certificacion AND mc.id_configuracion = c.id_configuracion
                        WHERE k.activo = 1 AND k.deleted_at IS NULL
                          AND COALESCE(mc.obligatoria, 1) = 1
                          AND mc.archivo IS NOT NULL AND mc.archivo <> '') AS obligatorias_con_archivo
                FROM ing_modelo_configuracion c
                INNER JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                INNER JOIN wms_linea_producto l ON l.idlineaproducto = s.lineaproductoid
                WHERE c.deleted_at IS NULL
                ORDER BY c.id_configuracion DESC";

        return $this->select_all($sql);
    }

    public function existeConfiguracion(int $idConfiguracion): bool
    {
        $row = $this->select(
            "SELECT id_configuracion FROM ing_modelo_configuracion WHERE id_configuracion = ? AND deleted_at IS NULL",
            [$idConfiguracion]
        );
        return !empty($row);
    }
}
