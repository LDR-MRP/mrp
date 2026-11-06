<?php
/** Consultas de Backorders agrupadas por pedido. Solo lectura. */
class Ped_backordersModel extends Mysql {
    private function condiciones(string $busqueda, string $estado): array {
        $where = ['p.estado = 2'];
        $params = [];
        if ($busqueda !== '') {
            $where[] = '(p.folio_pedido LIKE ? OR p.clave LIKE ? OR c.nombre_comercial LIKE ? OR c.razon_social LIKE ?)';
            $termino = '%'.$busqueda.'%';
            $params = [$termino, $termino, $termino, $termino];
        }
        if (in_array($estado, ['PENDIENTE','PARCIAL','ASIGNADO','CANCELADO'], true)) {
            $where[] = 'b.estatus = ?';
            $params[] = $estado;
        }
        return [implode(' AND ', $where), $params];
    }
    public function listar(string $busqueda = '', string $estado = '', int $pagina = 1, int $limite = 20): array {
        $db = $this->getConexion();
        [$where, $params] = $this->condiciones($busqueda, $estado);
        $from = ' FROM ped_backorder b INNER JOIN ped_pedidos p ON p.idpedido = b.idpedido INNER JOIN cli_clientes c ON c.idcliente = p.idcliente WHERE '.$where;
        $stmt = $db->prepare('SELECT COUNT(DISTINCT b.idpedido)'.$from);
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();
        $limite = max(1, min(100, $limite));
        $pagina = max(1, $pagina);
        $offset = ($pagina - 1) * $limite;
        // Evitar agrupaciones de columnas de clientes/pedidos: subconsulta agrupada solo por idpedido.
        // Las condiciones y parametros se aplican una sola vez al query exterior.
        $sql = 'SELECT p.idpedido, p.clave, p.folio_pedido, p.fecha_creacion AS fecha_pedido,
            COALESCE(NULLIF(c.nombre_comercial,\'\'), c.razon_social) AS distribuidor,
            MIN(b.fecha_creacion) AS fecha_backorder, MAX(b.fecha_actualizacion) AS ultima_actualizacion,
            COUNT(b.idbackorder) AS renglones,
            COALESCE(SUM(b.cantidad_comprometida),0) AS comprometidas,
            COALESCE(SUM(b.cantidad_atendida_inicial),0) AS atendidas_iniciales,
            COALESCE(SUM(b.cantidad_asignada),0) AS asignadas,
            COALESCE(SUM(b.cantidad_pendiente),0) AS pendientes,
            SUM(CASE WHEN b.estatus=\'CANCELADO\' THEN 1 ELSE 0 END) AS cancelados,
            SUM(CASE WHEN b.estatus=\'ASIGNADO\' THEN 1 ELSE 0 END) AS completos,
            SUM(CASE WHEN b.estatus=\'PARCIAL\' THEN 1 ELSE 0 END) AS parciales'
            .$from.' GROUP BY p.idpedido ORDER BY fecha_backorder DESC, p.idpedido DESC LIMIT '.$limite.' OFFSET '.$offset;
        $sql = str_replace(
            ['p.clave, p.folio_pedido, p.fecha_creacion AS fecha_pedido,', "COALESCE(NULLIF(c.nombre_comercial,''), c.razon_social) AS distribuidor,"],
            ['MAX(p.clave) AS clave, MAX(p.folio_pedido) AS folio_pedido, MAX(p.fecha_creacion) AS fecha_pedido,', "MAX(COALESCE(NULLIF(c.nombre_comercial,''), c.razon_social)) AS distribuidor,"],
            $sql
        );
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $db->prepare('SELECT COUNT(DISTINCT b.idpedido) AS pedidos, COUNT(*) AS renglones, COALESCE(SUM(b.cantidad_comprometida),0) AS comprometidas, COALESCE(SUM(b.cantidad_atendida_inicial),0) AS atendidas_iniciales, COALESCE(SUM(b.cantidad_asignada),0) AS asignadas, COALESCE(SUM(b.cantidad_pendiente),0) AS pendientes'.$from);
        $stmt->execute($params);
        $totales = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return ['items'=>$items, 'totales'=>$totales, 'pagina'=>$pagina, 'paginas'=>max(1,(int)ceil($total/$limite)), 'total'=>$total];
    }
    public function detalle(string $clave): array {
        $db = $this->getConexion();
        $sql = 'SELECT b.idbackorder,b.origen,b.estatus,b.cantidad_comprometida,b.cantidad_atendida_inicial,b.cantidad_asignada,b.cantidad_pendiente,b.precio_unitario,b.descuento,b.subtotal,b.iva,b.total,b.fecha_creacion,b.fecha_actualizacion,b.observaciones, pd.tipo_entrega, pd.direccion_entrega, suc.nombre_sucursal AS sucursal_entrega, u.nombre AS unidad,u.modelo,c.nombre AS color FROM ped_backorder b INNER JOIN ped_pedidos p ON p.idpedido=b.idpedido INNER JOIN web_unidades u ON u.idunidad=b.idunidad LEFT JOIN ped_pedidos_detalle pd ON pd.idpedido_detalle=b.idpedido_detalle LEFT JOIN cli_clientes_sucursales suc ON suc.idsucursal=pd.idsucursal_entrega LEFT JOIN web_unidades_colores uc ON uc.idunidad_color=b.idunidad_color LEFT JOIN web_colores c ON c.idcolor=uc.idcolor WHERE p.clave=? AND p.estado=2 ORDER BY b.idbackorder';
        $stmt = $db->prepare($sql);
        $stmt->execute([$clave]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
