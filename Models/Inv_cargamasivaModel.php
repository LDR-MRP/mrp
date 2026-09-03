<?php

class Inv_cargamasivaModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Recibe un arreglo de claves (cve_articulo) y regresa un mapa
     * [cve_articulo => idinventario] solo con las que YA existen en BD.
     */
    public function mapExistentesPorClaves(array $claves): array
    {
        if (empty($claves)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($claves), '?'));
        $sql = "SELECT idinventario, cve_articulo FROM wms_inventario WHERE cve_articulo IN ($placeholders)";
        $rows = $this->select_all($sql, $claves);

        $map = [];
        foreach ($rows as $row) {
            $map[$row['cve_articulo']] = (int) $row['idinventario'];
        }
        return $map;
    }

    public function selectMarcasActivas(): array
    {
        // OJO: a diferencia de wms_inventario/wms_almacenes, las marcas
        // (Cli_marcasModel::insertMarca) NUNCA guardan estado=2 al crearse;
        // el campo se queda NULL. La convención real aquí es "estado != 0"
        // (0 = eliminada), igual que en Cli_marcasModel::selectMarcas().
        $sql = "SELECT id, nombre FROM wms_marcas WHERE estado != 0 OR estado IS NULL ORDER BY nombre";
        return $this->select_all($sql);
    }

    public function selectProducto(int $idinventario)
    {
        $sql = "SELECT * FROM wms_inventario WHERE idinventario = ?";
        return $this->select($sql, [$idinventario]);
    }

    public function insertProducto(array $d): int
    {
        $sql = "INSERT INTO wms_inventario
            (cve_articulo, descripcion, notas, serie, unidad_salida, unidad_empaque,
             ubicacion, idmarca, tiempo_surtido, ultimo_costo, tipo_elemento, unidad_entrada,
             factor_unidades, lote, pedimiento, peso, volumen, stock_minimo, stock_maximo,
             fecha_creacion, estado)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),?)";

        return $this->insert($sql, [
            $d['cve_articulo'],
            $d['descripcion'],
            $d['notas'],
            $d['serie'],
            $d['unidad_salida'],
            $d['unidad_empaque'],
            $d['ubicacion'],
            $d['idmarca'],
            $d['tiempo_surtido'],
            $d['ultimo_costo'],
            $d['tipo_elemento'],
            $d['unidad_entrada'],
            $d['factor_unidades'],
            $d['lote'],
            $d['pedimiento'],
            $d['peso'],
            $d['volumen'],
            $d['stock_minimo'],
            $d['stock_maximo'],
            $d['estado'],
        ]);
    }

    public function updateProducto(int $idinventario, array $d): bool
    {
        $sql = "UPDATE wms_inventario SET
                descripcion = ?,
                notas = ?,
                serie = ?,
                unidad_salida = ?,
                unidad_empaque = ?,
                ubicacion = ?,
                idmarca = ?,
                tiempo_surtido = ?,
                ultimo_costo = ?,
                tipo_elemento = ?,
                unidad_entrada = ?,
                factor_unidades = ?,
                lote = ?,
                pedimiento = ?,
                peso = ?,
                volumen = ?,
                stock_minimo = ?,
                stock_maximo = ?,
                estado = ?
            WHERE idinventario = ?";

        return (bool) $this->update($sql, [
            $d['descripcion'],
            $d['notas'],
            $d['serie'],
            $d['unidad_salida'],
            $d['unidad_empaque'],
            $d['ubicacion'],
            $d['idmarca'],
            $d['tiempo_surtido'],
            $d['ultimo_costo'],
            $d['tipo_elemento'],
            $d['unidad_entrada'],
            $d['factor_unidades'],
            $d['lote'],
            $d['pedimiento'],
            $d['peso'],
            $d['volumen'],
            $d['stock_minimo'],
            $d['stock_maximo'],
            $d['estado'],
            $idinventario,
        ]);
    }
}
