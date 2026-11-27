<?php
/** Seguimiento de Backorders agrupados por pedido. Solo lectura. */
require_once __DIR__ . '/../Services/PedPedidosZonaHoraria.php';
class Ped_backordersModel extends Mysql
{
    use PedPedidosZonaHoraria;
    public function __construct()
    {
        parent::__construct();
        $this->configurarZonaHorariaPedidos();
    }

    /** Todos los filtros se aplican a las mismas filas en indicadores, listado y exportaciones. */
    private function consultaFiltrada(array $f): array
    {
        $where = ['p.estado = 2'];
        $params = [];
        $buscar = trim((string)($f['pedido'] ?? ''));
        if ($buscar !== '') {
            $where[] = '(p.folio_pedido LIKE ? OR p.clave LIKE ? OR c.nombre_comercial LIKE ? OR c.razon_social LIKE ?)';
            for ($i=0; $i<4; $i++) $params[] = '%'.$buscar.'%';
        }
        $modelo = trim((string)($f['modelo'] ?? ''));
        if ($modelo !== '') {
            $where[] = '(u.nombre LIKE ? OR u.modelo LIKE ? OR inv.cve_articulo LIKE ?)';
            for ($i=0; $i<3; $i++) $params[] = '%'.$modelo.'%';
        }
        $estado = strtoupper(trim((string)($f['estado'] ?? '')));
        if (in_array($estado,['PENDIENTE','PARCIAL','ASIGNADO','CANCELADO'],true)) {
            $where[] = 'b.estatus = ?'; $params[] = $estado;
        }
        foreach (['desde'=>['p.fecha_creacion','>='], 'hasta'=>['p.fecha_creacion','<='],
                  'entrega_desde'=>['p.fecha_requerida','>='], 'entrega_hasta'=>['p.fecha_requerida','<=']] as $k=>$data) {
            $v = trim((string)($f[$k] ?? ''));
            if ($v !== '') {
                $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
                if (!$dt || $dt->format('Y-m-d') !== $v) throw new InvalidArgumentException('Fecha inválida en el filtro '.$k);
                $where[] = $data[0].' '.$data[1].' ?';
                $params[] = $v.(($k==='hasta')?' 23:59:59':(($k==='desde')?' 00:00:00':''));
            }
        }
        $mes = trim((string)($f['mes'] ?? ''));
        if ($mes !== '') {
            if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/',$mes)) throw new InvalidArgumentException('Mes de facturación inválido.');
            $where[] = 'p.mes_facturacion_deseado >= ? AND p.mes_facturacion_deseado < ?';
            $params[] = $mes.'-01';
            $params[] = (new DateTimeImmutable($mes.'-01'))->modify('+1 month')->format('Y-m-d');
        }
        $rapido = trim((string)($f['rapido'] ?? ''));
        if ($rapido === 'proximos') $where[] = 'p.fecha_requerida BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)';
        if ($rapido === 'vencidos') $where[] = 'p.fecha_requerida < CURDATE() AND b.cantidad_pendiente > 0';
        if ($rapido === 'sin_fecha') $where[] = 'p.fecha_requerida IS NULL';
        $from = ' FROM ped_backorder b
            INNER JOIN ped_pedidos p ON p.idpedido=b.idpedido
            INNER JOIN cli_clientes c ON c.idcliente=p.idcliente
            INNER JOIN web_unidades u ON u.idunidad=b.idunidad
            LEFT JOIN wms_inventario inv ON inv.idinventario=u.inventarioid
            LEFT JOIN web_unidades_colores uc ON uc.idunidad_color=b.idunidad_color
            LEFT JOIN web_colores co ON co.idcolor=uc.idcolor
            WHERE '.implode(' AND ',$where);
        return [$from,$params];
    }

    private function selectAgrupado(): string
    {
        return 'SELECT p.idpedido, MAX(p.clave) AS clave, MAX(p.folio_pedido) AS folio_pedido,
          MAX(p.fecha_creacion) AS fecha_pedido, MAX(p.fecha_requerida) AS fecha_requerida,
          MAX(p.mes_facturacion_deseado) AS mes_facturacion_deseado,
          MAX(c.razon_social) AS distribuidor,
          COUNT(DISTINCT b.idunidad) AS modelos, COUNT(b.idbackorder) AS renglones,
          MIN(b.fecha_creacion) AS fecha_backorder, MAX(b.fecha_actualizacion) AS ultima_actualizacion,
          COALESCE(SUM(b.cantidad_comprometida),0) AS comprometidas,
          COALESCE(SUM(b.cantidad_atendida_inicial),0) AS atendidas_iniciales,
          COALESCE(SUM(b.cantidad_asignada),0) AS asignadas,
          COALESCE(SUM(b.cantidad_pendiente),0) AS pendientes,
          COALESCE(SUM(b.total),0) AS monto_comprometido,
          SUM(CASE WHEN b.estatus=\'CANCELADO\' THEN 1 ELSE 0 END) AS cancelados,
          SUM(CASE WHEN b.estatus=\'ASIGNADO\' THEN 1 ELSE 0 END) AS completos,
          SUM(CASE WHEN b.estatus=\'PARCIAL\' THEN 1 ELSE 0 END) AS parciales';
    }

    private function totales($db, string $from, array $params): array
    {
        $sql = 'SELECT COUNT(DISTINCT b.idpedido) AS pedidos, COUNT(*) AS renglones,
            COALESCE(SUM(b.cantidad_comprometida),0) AS comprometidas,
            COALESCE(SUM(b.cantidad_atendida_inicial),0) AS atendidas_iniciales,
            COALESCE(SUM(b.cantidad_asignada),0) AS asignadas,
            COALESCE(SUM(b.cantidad_pendiente),0) AS pendientes,
            COALESCE(SUM(b.total),0) AS monto_comprometido,
            COALESCE(SUM(CASE WHEN p.fecha_requerida BETWEEN CURDATE() AND DATE_ADD(CURDATE(),INTERVAL 7 DAY) THEN b.cantidad_pendiente ELSE 0 END),0) AS proximos,
            COALESCE(SUM(CASE WHEN p.fecha_requerida IS NULL THEN b.cantidad_pendiente ELSE 0 END),0) AS sin_fecha'
            .$from;
        $stmt=$db->prepare($sql); $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function listar(array $f=[], int $pagina=1, int $limite=20): array
    {
        $db=$this->getConexion(); [$from,$params]=$this->consultaFiltrada($f);
        $stmt=$db->prepare('SELECT COUNT(DISTINCT b.idpedido)'.$from);$stmt->execute($params);
        $total=(int)$stmt->fetchColumn();$limite=max(1,min(100,$limite));$pagina=max(1,$pagina);$offset=($pagina-1)*$limite;
        $sql=$this->selectAgrupado().$from.' GROUP BY p.idpedido ORDER BY fecha_backorder DESC,p.idpedido DESC LIMIT '.$limite.' OFFSET '.$offset;
        $stmt=$db->prepare($sql);$stmt->execute($params);
        return ['items'=>$stmt->fetchAll(PDO::FETCH_ASSOC),'totales'=>$this->totales($db,$from,$params),
            'pagina'=>$pagina,'paginas'=>max(1,(int)ceil($total/$limite)),'total'=>$total];
    }

    /** Reporte acotado: encabezados y renglones resultantes de los filtros, no solo la página visible. */
    public function reporte(array $f): array
    {
        $db=$this->getConexion();[$from,$params]=$this->consultaFiltrada($f);
        $stmt=$db->prepare('SELECT COUNT(*)'.$from);$stmt->execute($params);
        $cantidad=(int)$stmt->fetchColumn();
        if ($cantidad>10000) throw new InvalidArgumentException('El reporte supera 10,000 renglones. Reduce el rango de fechas.');
        $stmt=$db->prepare('SELECT COALESCE(SUM(b.cantidad_comprometida),0)'.$from);
        $stmt->execute($params);
        if ((int)$stmt->fetchColumn() > 30000) throw new InvalidArgumentException('El detalle supera 30,000 unidades. Reduce los filtros o el rango de fechas.');
        $stmt=$db->prepare($this->selectAgrupado().$from.' GROUP BY p.idpedido ORDER BY fecha_backorder DESC,p.idpedido DESC');
        $stmt->execute($params);$pedidos=$stmt->fetchAll(PDO::FETCH_ASSOC);
        $sql='SELECT p.idpedido,p.folio_pedido,p.fecha_creacion AS fecha_creacion_pedido,
          p.fecha_requerida AS entrega_programada,p.mes_facturacion_deseado AS mes_facturar,
          c.nombre_comercial,c.razon_social,
          b.idbackorder,b.origen,b.estatus,b.fecha_creacion AS fecha_backorder,
          b.cantidad_comprometida,b.cantidad_atendida_inicial,b.cantidad_asignada,b.cantidad_pendiente,
          b.total,b.precio_unitario,b.precio_incluye_iva,b.descuento,
          b.fondo_publicidad_unitario,b.fondo_publicidad_total,
          u.nombre AS unidad,u.modelo,inv.cve_articulo AS sku,co.nombre AS color'
          .$from.' ORDER BY p.idpedido DESC,b.idbackorder';
        $stmt=$db->prepare($sql);$stmt->execute($params);$detalles=$stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['pedidos'=>$pedidos,'detalles'=>$detalles,'totales'=>$this->totales($db,$from,$params)];
    }

    /** Modelos disponibles en Backorders, para el selector del reporte (no modifica datos). */
    public function modelosDisponibles(): array
    {
        $db=$this->getConexion();
        $sql='SELECT DISTINCT u.nombre AS unidad, u.modelo, inv.cve_articulo AS sku
             FROM ped_backorder b
             INNER JOIN ped_pedidos p ON p.idpedido=b.idpedido AND p.estado=2
             INNER JOIN web_unidades u ON u.idunidad=b.idunidad
             LEFT JOIN wms_inventario inv ON inv.idinventario=u.inventarioid
             ORDER BY u.nombre,u.modelo LIMIT 1500';
        $stmt=$db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Se conserva el modal del pedido, agregando el SKU en el detalle. */
    public function detalle(string $clave): array
    {
        $db=$this->getConexion();
        $sql='SELECT b.idbackorder,b.origen,b.estatus,b.cantidad_comprometida,b.cantidad_atendida_inicial,
            b.cantidad_asignada,b.cantidad_pendiente,b.precio_unitario,b.descuento,b.subtotal,b.iva,b.total,
            b.fecha_creacion,b.fecha_actualizacion,b.observaciones,p.fecha_requerida,p.mes_facturacion_deseado,
            pd.tipo_entrega,pd.direccion_entrega,suc.nombre_sucursal AS sucursal_entrega,
            u.nombre AS unidad,u.modelo,inv.cve_articulo AS sku,co.nombre AS color
            FROM ped_backorder b INNER JOIN ped_pedidos p ON p.idpedido=b.idpedido
            INNER JOIN web_unidades u ON u.idunidad=b.idunidad
            LEFT JOIN wms_inventario inv ON inv.idinventario=u.inventarioid
            LEFT JOIN ped_pedidos_detalle pd ON pd.idpedido_detalle=b.idpedido_detalle
            LEFT JOIN cli_clientes_sucursales suc ON suc.idsucursal=pd.idsucursal_entrega
            LEFT JOIN web_unidades_colores uc ON uc.idunidad_color=b.idunidad_color
            LEFT JOIN web_colores co ON co.idcolor=uc.idcolor
            WHERE p.clave=? AND p.estado=2 ORDER BY b.idbackorder';
        $stmt=$db->prepare($sql);$stmt->execute([$clave]);return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
