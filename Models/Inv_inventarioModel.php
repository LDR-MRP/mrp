<?php

class Inv_inventarioModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    /* ===============================
       INSERT
    =============================== */
    public function insertInventario(
        string $cve_articulo,
        string $descripcion,
        string $notas,
        string $unidad_entrada,
        string $unidad_salida,
        string $unidad_empaque,
        float  $ultimo_costo,
        string $ubicacion,
        ?int   $idmarca,
        string $tipo_elemento,
        float  $factor_unidades,
        int    $tiempo_surtido,
        float  $peso,
        float  $volumen,
        string $serie,
        string $lote,
        string $pedimiento
    ) {
        // ==== VALIDAR DUPLICADO (SIN ?)
        $cve_articulo = addslashes($cve_articulo);

        $sql = "SELECT idinventario
        FROM wms_inventario
        WHERE cve_articulo = '{$cve_articulo}'";

        $request = $this->select($sql);


        if (!empty($request)) {
            return "exist";
        }

        // ==== INSERT (CON ?)
        $sql = "INSERT INTO wms_inventario
(
    cve_articulo,
    descripcion,
    notas,
    unidad_entrada,
    unidad_salida,
    unidad_empaque,
    ultimo_costo,
    ubicacion,
    idmarca,
    tipo_elemento,
    factor_unidades,
    tiempo_surtido,
    peso,
    volumen,
    serie,
    lote,
    pedimiento,
    fecha_creacion,
    estado
)
VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),2)";

        return $this->insert($sql, [
            $cve_articulo,
            $descripcion,
            $notas,
            $unidad_entrada,
            $unidad_salida,
            $unidad_empaque,
            $ultimo_costo,
            $ubicacion,
            $idmarca,
            $tipo_elemento,
            $factor_unidades,
            $tiempo_surtido,
            $peso,
            $volumen,
            $serie,
            $lote,
            $pedimiento
        ]);
    }

    /* ===============================
   WMS MULTIALMACÉN
=============================== */
    public function inicializarMultiAlmacen(
        int $inventarioid,
        int $almacenid,
        float $cantidadInicial
    ): bool {
        $sql = "INSERT INTO wms_multialmacen
            (inventarioid, almacenid, existencia)
            VALUES (?, ?, ?)";
        return $this->insert($sql, [$inventarioid, $almacenid, $cantidadInicial]);
    }




    /* ===============================
       SELECT ALL
    =============================== */
    public function selectInventarios()
    {
        $sql = "SELECT 
                    i.idinventario,
                    i.cve_articulo,
                    i.descripcion,
                    lp.descripcion AS linea,
                    i.tipo_elemento,
                    i.estado,
                    i.ultimo_costo,
                    i.ubicacion
                FROM wms_inventario i
                LEFT JOIN wms_linea_producto lp 
                    ON i.lineaproductoid = lp.idlineaproducto
                WHERE i.estado != 0";

        return $this->select_all($sql);
    }

    /* ===============================
       LISTADO PAGINADO (DataTables server-side)
       Solo trae la pagina visible (10/25/50/100 filas) en lugar de
       toda la tabla. Filtros, busqueda y orden se resuelven en MySQL.
       $p = [
         'start' => int, 'length' => int,
         'search' => string,
         'tipos_busqueda' => ['P','K',...]  (codigos cuyo nombre coincide con la busqueda),
         'tipo' => 'P'|'S'|'K'|'C'|'H'|'R'|'',
         'estado' => 'activo'|'inactivo'|'',
         'order_col' => 'cve_articulo'|'descripcion'|'tipo_elemento'|'estado',
         'order_dir' => 'ASC'|'DESC'
       ]
    =============================== */
    public function selectInventariosPaginado(array $p): array
    {
        $where  = ["i.estado != 0"];
        $params = [];

        if (!empty($p['tipo'])) {
            $where[]  = "i.tipo_elemento = ?";
            $params[] = $p['tipo'];
        }

        if ($p['estado'] === 'activo') {
            $where[] = "i.estado = 2";
        } elseif ($p['estado'] === 'inactivo') {
            $where[] = "i.estado <> 2";
        }

        if ($p['search'] !== '') {
            $like = '%' . $p['search'] . '%';
            $or   = ["i.cve_articulo LIKE ?", "i.descripcion LIKE ?"];
            $params[] = $like;
            $params[] = $like;

            if (!empty($p['tipos_busqueda'])) {
                $in   = implode(',', array_fill(0, count($p['tipos_busqueda']), '?'));
                $or[] = "i.tipo_elemento IN ($in)";
                foreach ($p['tipos_busqueda'] as $t) $params[] = $t;
            }
            $where[] = '(' . implode(' OR ', $or) . ')';
        }

        $whereSql = 'WHERE ' . implode(' AND ', $where);

        // Total sin filtros (solo lo que el listado puede mostrar)
        $total = $this->select("SELECT COUNT(*) AS total FROM wms_inventario i WHERE i.estado != 0");
        $recordsTotal = (int)($total['total'] ?? 0);

        // Total con filtros
        if (count($where) === 1) {
            $recordsFiltered = $recordsTotal;
        } else {
            $filt = $this->select("SELECT COUNT(*) AS total FROM wms_inventario i $whereSql", $params);
            $recordsFiltered = (int)($filt['total'] ?? 0);
        }

        // Orden: columna validada contra lista blanca en el controlador
        $orderCol = in_array($p['order_col'], ['cve_articulo', 'descripcion', 'tipo_elemento', 'estado'], true)
            ? $p['order_col'] : 'cve_articulo';
        $orderDir = ($p['order_dir'] === 'DESC') ? 'DESC' : 'ASC';

        $start  = max(0, (int)$p['start']);
        $length = (int)$p['length'];
        $limit  = ($length > 0) ? "LIMIT " . min($length, 500) . " OFFSET $start" : "LIMIT 500 OFFSET $start";

        $sql = "SELECT 
                    i.idinventario,
                    i.cve_articulo,
                    i.descripcion,
                    i.tipo_elemento,
                    i.estado
                FROM wms_inventario i
                $whereSql
                ORDER BY i.$orderCol $orderDir, i.idinventario $orderDir
                $limit";

        $data = $this->select_all($sql, $params);

        return [
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $data,
        ];
    }

    /* ===============================
       SELECT ONE
    =============================== */
    public function selectInventario(int $idinventario)
    {
        $sql = "SELECT * FROM wms_inventario 
            WHERE idinventario = $idinventario";

        $inventario = $this->select($sql);

        $sqlClaves = "SELECT * FROM wms_claves_alternas 
                  WHERE inventarioid = $idinventario";

        $claves = $this->select_all($sqlClaves);

        $inventario['claves'] = $claves;

        return $inventario;
    }


    /* ===============================
       UPDATE
    =============================== */
    public function updateInventario(
        int $idinventario,
        string $cve_articulo,
        string $descripcion,
        string $notas,
        string $unidad_entrada,
        string $unidad_salida,
        string $unidad_empaque,
        float  $ultimo_costo,
        string $ubicacion,
        ?int   $idmarca,
        string $tipo_elemento,
        float  $factor_unidades,
        int    $tiempo_surtido,
        float  $peso,
        float  $volumen,
        string $serie,
        string $lote,
        string $pedimiento,
        int    $estado
    ) {
        // ==== VALIDAR DUPLICADO (SIN ?)
        $idinventario = (int)$idinventario;
        $cve_articulo = addslashes($cve_articulo);

        $sql = "SELECT idinventario
        FROM wms_inventario
        WHERE cve_articulo = '{$cve_articulo}'
          AND idinventario != {$idinventario}";

        $request = $this->select($sql);


        if (!empty($request)) {
            return "exist";
        }

        // ==== UPDATE 
        $sql = "UPDATE wms_inventario SET
    cve_articulo = ?,
    descripcion = ?,
    notas = ?,
    unidad_entrada = ?,
    unidad_salida = ?,
    unidad_empaque = ?,     
    ultimo_costo = ?,
    ubicacion = ?,
    idmarca = ?,
    tipo_elemento = ?,
    factor_unidades = ?,
    tiempo_surtido = ?,
    peso = ?,
    volumen = ?,
    serie = ?,
    lote = ?,
    pedimiento = ?,
    estado = ?
WHERE idinventario = ?";


        return $this->update($sql, [
            $cve_articulo,
            $descripcion,
            $notas,
            $unidad_entrada,
            $unidad_salida,
            $unidad_empaque,
            $ultimo_costo,
            $ubicacion,
            $idmarca,
            $tipo_elemento,
            $factor_unidades,
            $tiempo_surtido,
            $peso,
            $volumen,
            $serie,
            $lote,
            $pedimiento,
            $estado,
            $idinventario
        ]);
    }

    /* ===============================
       DELETE (LÓGICO)
    =============================== */
    public function deleteInventario(int $idinventario)
    {
        $sql = "UPDATE wms_inventario
                SET estado = 0
                WHERE idinventario = ?";

        return $this->update($sql, [$idinventario]);
    }

    /* ===============================
       CLAVES ALTERNAS
    =============================== */
    public function insertClaveAlterna(
        int $inventarioid,
        string $cve_alterna,
        string $tipo
    ) {
        $inventarioid = (int)$inventarioid;
        $cve_alterna = addslashes($cve_alterna);

        $sql = "SELECT idclavealterna
        FROM wms_claves_alternas
        WHERE inventarioid = {$inventarioid}
          AND cve_alterna = '{$cve_alterna}'";

        $request = $this->select($sql);


        if (!empty($request)) {
            return "exist";
        }

        $sql = "INSERT INTO wms_claves_alternas
                (inventarioid, cve_alterna, tipo)
                VALUES (?, ?, ?)";

        return $this->insert($sql, [
            $inventarioid,
            $cve_alterna,
            $tipo
        ]);
    }

    public function upsertClaveAlterna(
        int $inventarioid,
        string $cve_alterna,
        string $tipo
    ) {
        $inventarioid = (int)$inventarioid;
        $cve_alterna = addslashes($cve_alterna);

        // 🔍 Verificar si ya existe una clave para ese inventario
        $sql = "SELECT idclavealterna 
            FROM wms_claves_alternas 
            WHERE inventarioid = $inventarioid 
            LIMIT 1";

        $existe = $this->select($sql);

        if (!empty($existe)) {

            // 🔄 UPDATE
            $sql = "UPDATE wms_claves_alternas 
                SET cve_alterna = ?, tipo = ?
                WHERE inventarioid = ?";

            return $this->update($sql, [
                $cve_alterna,
                $tipo,
                $inventarioid
            ]);
        } else {

            // ➕ INSERT
            $sql = "INSERT INTO wms_claves_alternas 
                (inventarioid, cve_alterna, tipo)
                VALUES (?, ?, ?)";

            return $this->insert($sql, [
                $inventarioid,
                $cve_alterna,
                $tipo
            ]);
        }
    }

    /* ===============================
       BUSCADOR KIT
    =============================== */
    public function buscarProductoKit(string $term)
    {
        $term = addslashes($term);

        $sql = "SELECT idinventario, cve_articulo, descripcion
            FROM wms_inventario
            WHERE cve_articulo LIKE '%{$term}%'
               OR descripcion LIKE '%{$term}%'
            LIMIT 20";

        return $this->select_all($sql);
    }


    /* ===============================
       KIT
    =============================== */
    public function insertKitDetalle(
        int $kitid,
        int $productoId,
        float $cantidad,
        float $porcentaje
    ) {
        $sql = "INSERT INTO wms_kit_detalle
        (idkitconfig, producto_id, cantidad, porcentaje)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            cantidad = VALUES(cantidad),
            porcentaje = VALUES(porcentaje)";

        return $this->insert($sql, [
            $kitid,
            $productoId,
            $cantidad,
            $porcentaje
        ]);
    }

    public function insertKitConfig(
        int $inventarioid,
        float $precio,
        string $descripcion
    ) {
        $sql = "INSERT INTO wms_kit_config
                (inventarioid, precio, descripcion, estado, fecha_creacion)
                VALUES (?, ?, ?, 2, NOW())";

        return $this->insert($sql, [
            $inventarioid,
            $precio,
            $descripcion
        ]);
    }

    public function selectInventariosPC_H()
    {
        $sql = "SELECT idinventario, cve_articulo, descripcion 
            FROM wms_inventario
            WHERE tipo_elemento IN ('P','C','H', 'R') 
            AND estado != 0";
        return $this->select_all($sql);
    }

    public function selectKitCompleto(int $inventarioid)
    {
        $sql = "SELECT kc.idkitconfig, kc.precio, kc.descripcion
            FROM wms_kit_config kc
            WHERE kc.inventarioid = $inventarioid
            AND kc.estado = 2
            LIMIT 1";

        $config = $this->select($sql);

        if (empty($config)) return [];

        $kitid = $config['idkitconfig'];

        $sqlDetalle = "SELECT kd.producto_id, kd.cantidad, kd.porcentaje,
                          i.cve_articulo, i.descripcion
                   FROM wms_kit_detalle kd
                   INNER JOIN wms_inventario i 
                        ON i.idinventario = kd.producto_id
                   WHERE kd.idkitconfig = $kitid";

        $detalle = $this->select_all($sqlDetalle);

        return [
            "config" => $config,
            "detalle" => $detalle
        ];
    }


    public function updateKitConfig(int $kitid, float $precio, string $descripcion)
    {
        $sql = "UPDATE wms_kit_config 
            SET precio = ?, descripcion = ?
            WHERE idkitconfig = ?";
        return $this->update($sql, [$precio, $descripcion, $kitid]);
    }

    public function deleteKitDetalleExcepto(int $kitid, array $ids)
    {
        $kitid = (int)$kitid;

        if (empty($ids)) {
            return $this->delete("DELETE FROM wms_kit_detalle WHERE idkitconfig = $kitid");
        }

        $ids = array_map('intval', $ids);
        $idsStr = implode(',', $ids);

        $sql = "DELETE FROM wms_kit_detalle
            WHERE idkitconfig = $kitid
            AND producto_id NOT IN ($idsStr)";

        return $this->delete($sql);
    }

    public function selectKitConfigByInventario(int $inventarioid)
    {
        $sql = "SELECT idkitconfig
            FROM wms_kit_config
            WHERE inventarioid = $inventarioid
            AND estado = 2
            LIMIT 1";

        return $this->select($sql);
    }

    // =====================================================
    // IMÁGENES INVENTARIO
    // =====================================================

    public function insertImagenInventario($inventarioid, $nombre)
    {
        $sql = "INSERT INTO wms_fotos_inventario (inventarioid, foto) VALUES (?, ?)";
        $arrData = array($inventarioid, $nombre);
        return $this->insert($sql, $arrData);
    }

    public function selectImagenesInventario(int $inventarioid)
    {
        $sql = "SELECT 
                idfotoinventario,
                inventarioid,
                foto
            FROM wms_fotos_inventario
            WHERE inventarioid = $inventarioid";

        return $this->select_all($sql);
    }

    public function selectImagenInventario(int $idfotoinventario)
    {
        $sql = "SELECT
                idfotoinventario,
                inventarioid,
                foto
            FROM wms_fotos_inventario
            WHERE idfotoinventario = $idfotoinventario";

        return $this->select($sql);
    }

    public function deleteImagenInventario(int $idfotoinventario)
    {
        $sql = "DELETE FROM wms_fotos_inventario
            WHERE idfotoinventario = $idfotoinventario";

        return $this->delete($sql);
    }


    // =====================================================
    // PORTAL WEB -> tabla del portal (web_unidades / web_unidades_imagenes)
    // =====================================================
    // web_unidades ahora tiene su propia columna inventarioid (agregada con
    // permiso del desarrollador del portal), asi que ya no se necesita una
    // tabla de enlace de nuestro lado para los productos tipo unidad (P).
    // Refaccion (R) usa su propia tabla, ver mas abajo
    // (getPortalWebRefaccionByInventario / wms_refacciones_portalweb).

    public function getWebUnidadByInventario(int $inventarioid)
    {
        $sql = "SELECT * FROM web_unidades WHERE inventarioid = $inventarioid";
        return $this->select($sql);
    }

    public function getWebUnidad(int $idunidad)
    {
        $sql = "SELECT * FROM web_unidades WHERE idunidad = $idunidad";
        return $this->select($sql);
    }

    public function insertWebUnidad($data)
    {
        $sql = "INSERT INTO web_unidades
            (inventarioid, modelo, clave_modelo, nombre, version, descripcion, anio, marca, motor, stock, precio_estimado, imagen_caratula, estado, fecha_creacion, fecha_actualizacion)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())";

        return $this->insert($sql, [
            $data['inventarioid'],
            $data['modelo'],
            $data['clave_modelo'],
            $data['nombre'],
            $data['version'],
            $data['descripcion'],
            $data['anio'],
            $data['marca'],
            $data['motor'],
            $data['stock'],
            $data['precio_estimado'],
            $data['imagen_caratula'],
            $data['estado'],
        ]);
    }

    public function updateWebUnidad(int $idunidad, $data)
    {
        $sql = "UPDATE web_unidades SET
            modelo = ?,
            clave_modelo = ?,
            nombre = ?,
            version = ?,
            descripcion = ?,
            anio = ?,
            marca = ?,
            motor = ?,
            stock = ?,
            precio_estimado = ?,
            estado = ?,
            fecha_actualizacion = NOW()
            WHERE idunidad = ?";

        return $this->update($sql, [
            $data['modelo'],
            $data['clave_modelo'],
            $data['nombre'],
            $data['version'],
            $data['descripcion'],
            $data['anio'],
            $data['marca'],
            $data['motor'],
            $data['stock'],
            $data['precio_estimado'],
            $data['estado'],
            $idunidad,
        ]);
    }

    public function updateImagenCaratulaWebUnidad(int $idunidad, string $rutaArchivo)
    {
        $sql = "UPDATE web_unidades SET imagen_caratula = ?, fecha_actualizacion = NOW() WHERE idunidad = ?";
        return $this->update($sql, [$rutaArchivo, $idunidad]);
    }

    // -------- Autocompletado (marca / precio / stock) --------

    public function getMarcaAutoPorLinea(int $inventarioid)
    {
        $sql = "SELECT sp.cve_sublinea_producto
            FROM wms_inventario_linea il
            INNER JOIN wms_sublinea_producto sp ON sp.idsublineaproducto = il.sublineaproductoid
            WHERE il.inventarioid = $inventarioid AND il.estado = 2
            ORDER BY il.id_inv_linea DESC
            LIMIT 1";

        $r = $this->select($sql);
        return $r['cve_sublinea_producto'] ?? '';
    }

    public function getPrecioPublicoAuto(int $inventarioid)
    {
        $t = $this->tablaPrecios($inventarioid);
        $anio = $this->colAnioPrecio($inventarioid);
        $sql = "SELECT ip.precio
            FROM {$t} ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.inventarioid = ?
            AND p.cve_precio = 'Precio público'
            AND ip.estado = 2
            ORDER BY {$anio} DESC, ip.id_inv_precio DESC
            LIMIT 1";

        $r = $this->select($sql, [$inventarioid]);

        if (!empty($r['precio'])) {
            return (float) $r['precio'];
        }

        $sql2 = "SELECT ultimo_costo FROM wms_inventario WHERE idinventario = ?";
        $r2 = $this->select($sql2, [$inventarioid]);

        return (float) ($r2['ultimo_costo'] ?? 0);
    }

    public function getStockTotalAuto(int $inventarioid)
    {
        $sql = "SELECT COALESCE(SUM(existencia), 0) AS total
            FROM wms_multialmacen
            WHERE inventarioid = $inventarioid";

        $r = $this->select($sql);
        return (int) round($r['total'] ?? 0);
    }

    // -------- Imágenes (web_unidades_imagenes) --------
    // Cada foto pertenece a la galería general de la unidad (idunidad_color
    // NULL) o a la galería de uno de sus colores (web_unidades_colores).
    // El orden y el límite de fotos son por unidad + color; por eso se filtra
    // con "idunidad_color <=> ?", que también empata NULL con NULL.
    // La imagen principal del modelo es una sola (web_unidades.imagen_caratula
    // + es_principal de la galería general); las fotos de color nunca son principales.

    public function insertImagenWebUnidad($idunidad, $nombreOriginal, $nombreArchivo, $rutaArchivo, $orden, $esPrincipal, ?int $idUnidadColor = null)
    {
        $sql = "INSERT INTO web_unidades_imagenes
            (idunidad, idunidad_color, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal, estado, fecha_creacion)
            VALUES (?,?,?,?,?,?,?,2,NOW())";

        return $this->insert($sql, [$idunidad, $idUnidadColor, $nombreOriginal, $nombreArchivo, $rutaArchivo, $orden, $esPrincipal]);
    }

    public function countImagenesWebUnidad(int $idunidad, ?int $idUnidadColor = null)
    {
        $sql = "SELECT COUNT(*) AS total FROM web_unidades_imagenes
            WHERE idunidad = ? AND idunidad_color <=> ? AND estado != 0";
        $r = $this->select($sql, [$idunidad, $idUnidadColor]);
        return (int) ($r['total'] ?? 0);
    }

    // Siguiente posición libre de la galería (no se usa el conteo porque al
    // borrar fotos quedan huecos y se repetiría el orden)
    public function siguienteOrdenImagenWebUnidad(int $idunidad, ?int $idUnidadColor = null): int
    {
        $sql = "SELECT COALESCE(MAX(orden), 0) + 1 AS siguiente FROM web_unidades_imagenes
            WHERE idunidad = ? AND idunidad_color <=> ? AND estado != 0";
        $r = $this->select($sql, [$idunidad, $idUnidadColor]);
        return (int) ($r['siguiente'] ?? 1);
    }

    public function tienePrincipalImagenWebUnidad(int $idunidad, ?int $idUnidadColor = null): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM web_unidades_imagenes
            WHERE idunidad = ? AND idunidad_color <=> ? AND estado != 0 AND es_principal = 1";
        $r = $this->select($sql, [$idunidad, $idUnidadColor]);
        return (int) ($r['total'] ?? 0) > 0;
    }

    // Galería de la unidad: general (sin color) o la de un color
    public function selectImagenesWebUnidad(int $idunidad, ?int $idUnidadColor = null)
    {
        $sql = "SELECT idimagen, idunidad, idunidad_color, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal
            FROM web_unidades_imagenes
            WHERE idunidad = ? AND idunidad_color <=> ? AND estado != 0
            ORDER BY orden ASC, idimagen ASC";

        return $this->select_all($sql, [$idunidad, $idUnidadColor]);
    }

    // Todas las fotos de la unidad (general + colores), para "Fotos portal"
    public function selectTodasImagenesWebUnidad(int $idunidad)
    {
        $sql = "SELECT idimagen, idunidad, idunidad_color, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal
            FROM web_unidades_imagenes
            WHERE idunidad = ? AND estado != 0
            ORDER BY idunidad_color IS NOT NULL, idunidad_color, orden ASC, idimagen ASC";

        return $this->select_all($sql, [$idunidad]);
    }

    public function selectImagenWebUnidad(int $idimagen)
    {
        $sql = "SELECT idimagen, idunidad, idunidad_color, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal
            FROM web_unidades_imagenes
            WHERE idimagen = ?";

        return $this->select($sql, [$idimagen]);
    }

    // Cambia una foto de galería (general <-> color, o de un color a otro).
    // Se va al final de la galería destino. En un color nunca es principal; si
    // regresa a la general y ésta no tiene principal, pasa a serlo.
    public function moverImagenWebUnidad(int $idimagen, int $idunidad, ?int $idUnidadColorDestino): bool
    {
        $orden = $this->siguienteOrdenImagenWebUnidad($idunidad, $idUnidadColorDestino);
        $esPrincipal = ($idUnidadColorDestino === null && !$this->tienePrincipalImagenWebUnidad($idunidad, null)) ? 1 : 0;

        $sql = "UPDATE web_unidades_imagenes SET idunidad_color = ?, orden = ?, es_principal = ? WHERE idimagen = ? AND idunidad = ?";
        return (bool) $this->update($sql, [$idUnidadColorDestino, $orden, $esPrincipal, $idimagen, $idunidad]);
    }

    // Ficha técnica de Ingeniería (ing_modelo_configuracion) ligada al producto
    // por id_inventario. Solo lectura; se muestra en la pestaña "Fotos portal".
    // Se usa c.* porque algunas columnas (version_unidad, paso_diferencial)
    // se agregaron después y pueden no existir en todas las bases.
    public function selectFichaIngenieriaByInventario(int $inventarioid)
    {
        $sql = "SELECT c.*,
                    l.descripcion AS segmento,
                    s.descripcion AS modelo,
                    m.fabricante AS motor_fabricante,
                    m.modelo_motor AS motor_modelo
                FROM ing_modelo_configuracion c
                LEFT JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                LEFT JOIN wms_linea_producto l ON l.idlineaproducto = s.lineaproductoid
                LEFT JOIN ing_cat_motor m ON m.id_motor = c.id_motor
                WHERE c.id_inventario = ? AND c.deleted_at IS NULL
                ORDER BY c.id_configuracion DESC
                LIMIT 1";

        return $this->select($sql, [$inventarioid]);
    }

    // -------- Precios asignados al producto (pestaña Precio) --------
    // El usuario elige en Portal Web cuál de ellos se publica.

    public function selectPreciosPortal(int $inventarioid)
    {
        $t = $this->tablaPrecios($inventarioid);
        $anio = $this->colAnioPrecio($inventarioid);
        $sql = "SELECT ip.id_inv_precio, ip.idprecio, p.cve_precio, {$anio} AS anio_precio, ip.precio
            FROM {$t} ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.inventarioid = ? AND ip.estado = 2 AND p.estado = 2
            ORDER BY p.cve_precio, {$anio} DESC";
        return $this->select_all($sql, [$inventarioid]);
    }

    // Valida que el precio elegido pertenezca al producto y esté activo
    public function selectPrecioAsignado(int $idInvPrecio, int $inventarioid)
    {
        $t = $this->tablaPrecios($inventarioid);
        $sql = "SELECT ip.id_inv_precio, ip.precio, p.cve_precio
            FROM {$t} ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.id_inv_precio = ? AND ip.inventarioid = ? AND ip.estado = 2 AND p.estado = 2";
        return $this->select($sql, [$idInvPrecio, $inventarioid]);
    }

    // -------- Precio Dealer -> web_unidades.precio_dealer --------
    // La lista "Precio Dealer" (wms_precios.cve_precio) es la que alimenta
    // web_unidades.precio_dealer (precio base para distribuidores, lo usa
    // Cotizaciones).

    const CVE_PRECIO_DEALER = 'Precio Dealer';

    public function esListaPrecioDealer(int $idprecio): bool
    {
        $r = $this->select("SELECT cve_precio FROM wms_precios WHERE idprecio = ?", [$idprecio]);
        return !empty($r) && trim((string) $r['cve_precio']) === self::CVE_PRECIO_DEALER;
    }

    // Precio Dealer activo asignado al producto, o null si no tiene
    public function getPrecioDealerAuto(int $inventarioid)
    {
        $t = $this->tablaPrecios($inventarioid);
        $anio = $this->colAnioPrecio($inventarioid);
        $sql = "SELECT ip.precio
            FROM {$t} ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.inventarioid = ? AND p.cve_precio = ?
            AND ip.estado = 2 AND p.estado = 2
            ORDER BY {$anio} DESC, ip.id_inv_precio DESC
            LIMIT 1";
        $r = $this->select($sql, [$inventarioid, self::CVE_PRECIO_DEALER]);
        return !empty($r) ? (float) $r['precio'] : null;
    }

    // Actualiza precio_dealer de la unidad del portal ligada al producto
    // (si el producto aún no está en web_unidades no hace nada).
    public function updatePrecioDealerWebUnidad(int $inventarioid, $precio)
    {
        $sql = "UPDATE web_unidades SET precio_dealer = ?, fecha_actualizacion = NOW() WHERE inventarioid = ?";
        return $this->update($sql, [$precio, $inventarioid]);
    }

    // -------- Colores de la unidad en el portal --------
    // Catálogo: web_colores (Inventario > Catálogos > Colores de unidad)
    // Asignación: web_unidades_colores (idunidad_color, orden, es_predeterminado,
    // estado 2 = asignado / 1 = quitado). Las fotos por color cuelgan de
    // idunidad_color, por eso una asignación nunca se borra: al quitar el
    // color se pone estado 1 y sus fotos se ocultan hasta que se vuelva a asignar.

    public function selectColoresUnidadActivos()
    {
        $sql = "SELECT idcolor, nombre, codigo_hex
            FROM web_colores
            WHERE estado = 2
            ORDER BY nombre";
        return $this->select_all($sql);
    }

    // Colores asignados (activos) a la unidad, con cuántas fotos tiene cada uno
    public function selectColoresAsignadosWebUnidad(int $idunidad): array
    {
        $sql = "SELECT uc.idunidad_color, uc.idcolor, c.nombre, c.codigo_hex, uc.orden, uc.es_predeterminado,
                (SELECT COUNT(*) FROM web_unidades_imagenes i
                 WHERE i.idunidad_color = uc.idunidad_color AND i.estado != 0) AS total_fotos
            FROM web_unidades_colores uc
            INNER JOIN web_colores c ON c.idcolor = uc.idcolor
            WHERE uc.idunidad = ? AND uc.estado = 2 AND c.estado = 2
            ORDER BY uc.orden ASC, c.nombre ASC";
        return $this->select_all($sql, [$idunidad]) ?: [];
    }

    // Valida que el color asignado pertenezca a la unidad y esté activo
    public function selectUnidadColorActivo(int $idUnidadColor, int $idunidad)
    {
        $sql = "SELECT uc.idunidad_color, uc.idunidad, uc.idcolor, c.nombre
            FROM web_unidades_colores uc
            INNER JOIN web_colores c ON c.idcolor = uc.idcolor
            WHERE uc.idunidad_color = ? AND uc.idunidad = ? AND uc.estado = 2 AND c.estado = 2";
        return $this->select($sql, [$idUnidadColor, $idunidad]);
    }

    // $idsColores llega en el orden en que se muestran. Los colores activos
    // del catálogo que no vengan se marcan como quitados (estado 1); las
    // asignaciones a colores inactivos del catálogo no se tocan.
    public function guardarColoresWebUnidad(int $idunidad, array $idsColores, int $idColorPredeterminado = 0): void
    {
        $ids = [];
        foreach ($idsColores as $id) {
            $id = (int) $id;
            if ($id > 0 && !in_array($id, $ids, true)) {
                $ids[] = $id;
            }
        }
        if (!in_array($idColorPredeterminado, $ids, true)) {
            $idColorPredeterminado = $ids[0] ?? 0;
        }

        $this->update(
            "UPDATE web_unidades_colores
             SET estado = 1, es_predeterminado = 0
             WHERE idunidad = ? AND estado = 2
             AND idcolor IN (SELECT idcolor FROM web_colores WHERE estado = 2)",
            [$idunidad]
        );

        foreach ($ids as $i => $idcolor) {
            $this->insert(
                "INSERT INTO web_unidades_colores (idunidad, idcolor, orden, es_predeterminado, estado)
                 SELECT ?, idcolor, ?, ?, 2 FROM web_colores WHERE idcolor = ? AND estado = 2
                 ON DUPLICATE KEY UPDATE estado = 2, orden = VALUES(orden), es_predeterminado = VALUES(es_predeterminado)",
                [$idunidad, $i + 1, $idcolor === $idColorPredeterminado ? 1 : 0, $idcolor]
            );
        }
    }

    // Reemplaza el archivo de una foto de la galería conservando su orden y si es principal
    public function updateArchivoImagenWebUnidad(int $idimagen, string $nombreOriginal, string $nombreArchivo, string $rutaArchivo)
    {
        $sql = "UPDATE web_unidades_imagenes SET nombre_original = ?, nombre_archivo = ?, ruta_archivo = ? WHERE idimagen = ?";
        return $this->update($sql, [$nombreOriginal, $nombreArchivo, $rutaArchivo, $idimagen]);
    }

    public function deleteImagenWebUnidad(int $idimagen)
    {
        $sql = "DELETE FROM web_unidades_imagenes WHERE idimagen = ?";
        return $this->update($sql, [$idimagen]);
    }

    public function limpiarPrincipalImagenesWebUnidad(int $idunidad, ?int $idUnidadColor = null)
    {
        $sql = "UPDATE web_unidades_imagenes SET es_principal = 0 WHERE idunidad = ? AND idunidad_color <=> ?";
        return $this->update($sql, [$idunidad, $idUnidadColor]);
    }

    // Principal dentro de la galería de la foto (general o su color)
    public function marcarPrincipalImagenWebUnidad(int $idimagen, int $idunidad, ?int $idUnidadColor = null)
    {
        $this->limpiarPrincipalImagenesWebUnidad($idunidad, $idUnidadColor);

        $sql = "UPDATE web_unidades_imagenes SET es_principal = 1 WHERE idimagen = ?";
        return $this->update($sql, [$idimagen]);
    }

    // Si la galería (general o de un color) se quedó sin principal, la
    // primera foto que quede pasa a serlo.
    public function asegurarPrincipalImagenWebUnidad(int $idunidad, ?int $idUnidadColor = null): void
    {
        if ($this->tienePrincipalImagenWebUnidad($idunidad, $idUnidadColor)) {
            return;
        }
        $restantes = $this->selectImagenesWebUnidad($idunidad, $idUnidadColor);
        if (!empty($restantes)) {
            $this->marcarPrincipalImagenWebUnidad((int) $restantes[0]['idimagen'], $idunidad, $idUnidadColor);
        }
    }


    // =====================================================
    // PORTAL WEB -> tabla propia (wms_refacciones_portalweb / wms_refacciones_portalweb_imagenes)
    // Exclusiva para tipo_elemento = 'R' (Refaccion). Herramienta, Componente,
    // Kit y Servicio ya no tienen Portal Web. Mismas columnas que produce el
    // modo unidad (web_unidades / web_unidades_imagenes), pero en las tablas
    // propias del sistema (no las del desarrollador del portal).
    // =====================================================

    public function getPortalWebRefaccionByInventario(int $inventarioid)
    {
        $sql = "SELECT * FROM wms_refacciones_portalweb WHERE inventarioid = $inventarioid";
        return $this->select($sql);
    }

    public function insertPortalWebRefaccion($data)
    {
        $sql = "INSERT INTO wms_refacciones_portalweb
            (inventarioid, modelo, clave_modelo, nombre, descripcion, marca, stock, precio_estimado, imagen_caratula, estado)
            VALUES (?,?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['inventarioid'],
            $data['modelo'],
            $data['clave_modelo'],
            $data['nombre'],
            $data['descripcion'],
            $data['marca'],
            $data['stock'],
            $data['precio_estimado'],
            $data['imagen_caratula'],
            $data['estado'],
        ]);
    }

    public function updatePortalWebRefaccion(int $idportalweb, $data)
    {
        $sql = "UPDATE wms_refacciones_portalweb SET
            modelo = ?,
            clave_modelo = ?,
            nombre = ?,
            descripcion = ?,
            marca = ?,
            stock = ?,
            precio_estimado = ?,
            estado = ?,
            fecha_actualizacion = NOW()
            WHERE idportalweb = ?";

        return $this->update($sql, [
            $data['modelo'],
            $data['clave_modelo'],
            $data['nombre'],
            $data['descripcion'],
            $data['marca'],
            $data['stock'],
            $data['precio_estimado'],
            $data['estado'],
            $idportalweb,
        ]);
    }

    public function updateImagenCaratulaPortalWebRefaccion(int $idportalweb, string $rutaArchivo)
    {
        $sql = "UPDATE wms_refacciones_portalweb SET imagen_caratula = ?, fecha_actualizacion = NOW() WHERE idportalweb = ?";
        return $this->update($sql, [$rutaArchivo, $idportalweb]);
    }

    // -------- Imagenes (wms_refacciones_portalweb_imagenes) --------
    // Enlazadas por idportalweb (la PK de wms_refacciones_portalweb), igual
    // que web_unidades_imagenes se enlaza por idunidad.

    public function insertImagenPortalWebRefaccion($idportalweb, $nombreOriginal, $nombreArchivo, $rutaArchivo, $orden, $esPrincipal)
    {
        $sql = "INSERT INTO wms_refacciones_portalweb_imagenes
            (idportalweb, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal, estado, fecha_creacion)
            VALUES (?,?,?,?,?,?,2,NOW())";

        return $this->insert($sql, [$idportalweb, $nombreOriginal, $nombreArchivo, $rutaArchivo, $orden, $esPrincipal]);
    }

    public function countImagenesPortalWebRefaccion(int $idportalweb)
    {
        $sql = "SELECT COUNT(*) AS total FROM wms_refacciones_portalweb_imagenes
            WHERE idportalweb = $idportalweb AND estado != 0";

        $r = $this->select($sql);
        return (int) ($r['total'] ?? 0);
    }

    public function selectImagenesPortalWebRefaccion(int $idportalweb)
    {
        $sql = "SELECT idfotoportalweb, idportalweb, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal
            FROM wms_refacciones_portalweb_imagenes
            WHERE idportalweb = $idportalweb AND estado != 0
            ORDER BY orden ASC, idfotoportalweb ASC";

        return $this->select_all($sql);
    }

    public function selectImagenPortalWebRefaccion(int $idfotoportalweb)
    {
        $sql = "SELECT idfotoportalweb, idportalweb, nombre_original, nombre_archivo, ruta_archivo, orden, es_principal
            FROM wms_refacciones_portalweb_imagenes
            WHERE idfotoportalweb = $idfotoportalweb";

        return $this->select($sql);
    }

    public function deleteImagenPortalWebRefaccion(int $idfotoportalweb)
    {
        $sql = "DELETE FROM wms_refacciones_portalweb_imagenes WHERE idfotoportalweb = $idfotoportalweb";
        return $this->delete($sql);
    }

    public function limpiarPrincipalImagenesPortalWebRefaccion(int $idportalweb)
    {
        $sql = "UPDATE wms_refacciones_portalweb_imagenes SET es_principal = 0 WHERE idportalweb = ?";
        return $this->update($sql, [$idportalweb]);
    }

    public function marcarPrincipalImagenPortalWebRefaccion(int $idfotoportalweb, int $idportalweb)
    {
        $this->limpiarPrincipalImagenesPortalWebRefaccion($idportalweb);

        $sql = "UPDATE wms_refacciones_portalweb_imagenes SET es_principal = 1 WHERE idfotoportalweb = ?";
        return $this->update($sql, [$idfotoportalweb]);
    }


    //--------------------------------------------INVENTARIO MONEDAS
    public function setMonedaInventario(int $inventarioid, int $idmoneda)
    {
        $sql = "INSERT INTO wms_inventario_moneda (inventarioid,idmoneda,estado) 
            VALUES (?,?,2)";

        return $this->insert($sql, [$inventarioid, $idmoneda]);
    }

    public function insertInventarioMoneda($inventarioid, $idmoneda, $tipo, $fecha, $estado)
    {
        $sql = "INSERT INTO wms_inventario_moneda
            (inventarioid,idmoneda,tipo_cambio,fecha_creacion,estado)
            VALUES (?,?,?,?,?)";

        $arrData = [$inventarioid, $idmoneda, $tipo, $fecha, $estado];
        return $this->insert($sql, $arrData);
    }
    public function getMonedasAsignadas($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT im.idmoneda, COALESCE(m.descripcion, 'Sin descripción') as descripcion, im.tipo_cambio, im.estado
            FROM wms_inventario_moneda im
            LEFT JOIN wms_moneda m ON m.idmoneda = im.idmoneda
            WHERE im.inventarioid = $idinventario";

        return $this->select_all($sql);
    }

    //--------------------------------------------INVENTARIO PRECIOS
    //--------------------------------------------PRECIOS: tabla según tipo de producto
    // Unidades (tipo_elemento = 'P') -> wms_inventario_precios (con año modelo).
    // Todo lo demás (refacciones, componentes, herramientas, kits, servicios)
    // -> wms_inventario_precios_articulos (sin año). Mismas columnas salvo anio_precio.
    const TABLA_PRECIOS_UNIDADES  = 'wms_inventario_precios';
    const TABLA_PRECIOS_ARTICULOS = 'wms_inventario_precios_articulos';

    private $cacheEsUnidad = [];

    public function esUnidadPrecio(int $inventarioid): bool
    {
        if (!isset($this->cacheEsUnidad[$inventarioid])) {
            $r = $this->select("SELECT tipo_elemento FROM wms_inventario WHERE idinventario = ?", [$inventarioid]);
            $this->cacheEsUnidad[$inventarioid] = !empty($r) && $r['tipo_elemento'] === 'P';
        }
        return $this->cacheEsUnidad[$inventarioid];
    }

    private function tablaPrecios(int $inventarioid): string
    {
        return $this->esUnidadPrecio($inventarioid) ? self::TABLA_PRECIOS_UNIDADES : self::TABLA_PRECIOS_ARTICULOS;
    }

    // Columna de año para SELECT/ORDER BY (NULL en artículos, que no tienen año)
    private function colAnioPrecio(int $inventarioid, string $alias = 'ip'): string
    {
        return $this->esUnidadPrecio($inventarioid) ? "{$alias}.anio_precio" : 'NULL';
    }

    public function setPrecioInventario(int $inventarioid, int $idprecio)
    {
        $t = $this->tablaPrecios($inventarioid);
        $sql = "INSERT INTO {$t} (inventarioid,idprecio,estado)
            VALUES (?,?,2)";

        return $this->insert($sql, [$inventarioid, $idprecio]);
    }

    // $anio: año modelo al que aplica el precio (null = sin año). Un producto
    // puede tener la misma lista en varios años, pero no repetida en el mismo año.
    public function insertInventarioPrecio($inventarioid, $idprecio, $precio, $fecha, $estado, ?int $anio = null)
    {
        $inventarioid = (int) $inventarioid;
        $idprecio = (int) $idprecio;

        if (!$this->esUnidadPrecio($inventarioid)) {
            // Artículos: sin año; una sola vez cada lista
            $t = self::TABLA_PRECIOS_ARTICULOS;
            $existe = $this->select("SELECT id_inv_precio FROM {$t} WHERE inventarioid = ? AND idprecio = ?", [$inventarioid, $idprecio]);
            if (!empty($existe)) {
                return "exist";
            }
            return $this->insert(
                "INSERT INTO {$t} (inventarioid, idprecio, precio, fecha_creacion, estado) VALUES (?,?,?,?,?)",
                [$inventarioid, $idprecio, $precio, $fecha, $estado]
            );
        }

        // Unidades: evitar duplicados (misma lista y mismo año)
        $t = self::TABLA_PRECIOS_UNIDADES;
        $existe = $this->select(
            "SELECT id_inv_precio FROM {$t} WHERE inventarioid = ? AND idprecio = ? AND anio_precio <=> ?",
            [$inventarioid, $idprecio, $anio]
        );
        if (!empty($existe)) {
            return "exist";
        }

        return $this->insert(
            "INSERT INTO {$t} (inventarioid, idprecio, anio_precio, precio, fecha_creacion, estado) VALUES (?,?,?,?,?,?)",
            [$inventarioid, $idprecio, $anio, $precio, $fecha, $estado]
        );
    }

    public function getPreciosAsignados($idinventario)
    {
        $idinventario = intval($idinventario);
        $t = $this->tablaPrecios($idinventario);
        $anio = $this->colAnioPrecio($idinventario);

        $sql = "SELECT ip.id_inv_precio,
                   ip.idprecio,
                   p.cve_precio,
                   p.descripcion,
                   {$anio} AS anio_precio,
                   ip.precio,
                   ip.fecha_creacion,
                   ip.estado
            FROM {$t} ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.inventarioid = ?
            ORDER BY ip.estado DESC, p.cve_precio, {$anio} DESC";

        return $this->select_all($sql, [$idinventario]);
    }

    // EDITAR PRECIO ASIGNADO (valida que el registro pertenezca al producto)
    // Edita precio y año modelo. Devuelve "exist" si ya hay otro precio de la
    // misma lista con ese año para el producto.
    public function updateInventarioPrecio(int $id_inv_precio, int $inventarioid, float $precio, ?int $anio = null)
    {
        if (!$this->esUnidadPrecio($inventarioid)) {
            // Artículos: solo el precio (no tienen año)
            $t = self::TABLA_PRECIOS_ARTICULOS;
            return $this->update(
                "UPDATE {$t} SET precio = ? WHERE id_inv_precio = ? AND inventarioid = ?",
                [$precio, $id_inv_precio, $inventarioid]
            );
        }

        $t = self::TABLA_PRECIOS_UNIDADES;
        $dup = $this->select(
            "SELECT o.id_inv_precio
             FROM {$t} o
             INNER JOIN {$t} ip ON ip.id_inv_precio = ? AND ip.inventarioid = ?
             WHERE o.inventarioid = ip.inventarioid
               AND o.idprecio = ip.idprecio
               AND o.anio_precio <=> ?
               AND o.id_inv_precio <> ip.id_inv_precio
             LIMIT 1",
            [$id_inv_precio, $inventarioid, $anio]
        );
        if (!empty($dup)) {
            return "exist";
        }

        $sql = "UPDATE {$t}
            SET precio = ?, anio_precio = ?
            WHERE id_inv_precio = ? AND inventarioid = ?";

        return $this->update($sql, [$precio, $anio, $id_inv_precio, $inventarioid]);
    }






    // HABILITAR / INHABILITAR PRECIO ASIGNADO (2 = activo, 1 = inactivo).
    // Un precio inactivo ya no aparece en la lista de Portal Web.
    public function updateEstadoInventarioPrecio(int $id_inv_precio, int $inventarioid, int $estado)
    {
        $t = $this->tablaPrecios($inventarioid);
        $sql = "UPDATE {$t}
            SET estado = ?
            WHERE id_inv_precio = ? AND inventarioid = ?";

        return $this->update($sql, [$estado, $id_inv_precio, $inventarioid]);
    }

    // Precio asignado sin importar su estado (para saber qué lista es)
    public function selectPrecioAsignadoCualquierEstado(int $idInvPrecio, int $inventarioid)
    {
        $t = $this->tablaPrecios($inventarioid);
        $sql = "SELECT ip.id_inv_precio, ip.precio, ip.estado, p.cve_precio
            FROM {$t} ip
            INNER JOIN wms_precios p ON p.idprecio = ip.idprecio
            WHERE ip.id_inv_precio = ? AND ip.inventarioid = ?";
        return $this->select($sql, [$idInvPrecio, $inventarioid]);
    }

    //-------------------------------------------- INVENTARIO LINEA
    public function insertInventarioLinea($inventarioid, $sublineaproductoid, $fecha, $estado)
    {
        $inventarioid = intval($inventarioid);
        $sublineaproductoid = intval($sublineaproductoid);

        $exist = $this->select("
        SELECT id_inv_linea 
        FROM wms_inventario_linea 
        WHERE inventarioid = $inventarioid 
        AND estado = 2
    ");

        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO wms_inventario_linea 
        (inventarioid, sublineaproductoid, fecha_creacion, estado) 
        VALUES ($inventarioid, $sublineaproductoid, '$fecha', $estado)";

        return $this->insert($sql, []);
    }

    public function updateInventarioLinea($id_inv_linea, $sublineaproductoid)
    {
        $sql = "UPDATE wms_inventario_linea 
        SET sublineaproductoid = $sublineaproductoid
        WHERE id_inv_linea = $id_inv_linea";

        return $this->update($sql, []);
    }



    public function selectLineas()
    {
        $sql = "SELECT idlineaproducto AS idlinea, descripcion, estado 
            FROM wms_linea_producto";
        return $this->select_all($sql);
    }

    public function selectSublineas()
    {
        $sql = "SELECT 
        sl.idsublineaproducto,
        sl.descripcion AS sublinea,
        lp.descripcion AS linea
    FROM wms_sublinea_producto sl
    INNER JOIN wms_linea_producto lp 
        ON sl.lineaproductoid = lp.idlineaproducto
    WHERE sl.estado = 2";

        return $this->select_all($sql);
    }

    public function getLineasAsignadas($idinventario)
    {
        $sql = "SELECT 
        il.id_inv_linea,
        sl.idsublineaproducto,
        sl.lineaproductoid AS idlinea, -- 🔥 AGREGA ESTO
        sl.descripcion AS sublinea,
        lp.descripcion AS linea,
        il.fecha_creacion,
        il.estado
    FROM wms_inventario_linea il
    INNER JOIN wms_sublinea_producto sl 
        ON il.sublineaproductoid = sl.idsublineaproducto
    INNER JOIN wms_linea_producto lp 
        ON sl.lineaproductoid = lp.idlineaproducto
    WHERE il.inventarioid = $idinventario";

        return $this->select_all($sql);
    }





    //-------------------------------------------------Datos fiscales SAT
    // ===================== FISCAL =====================

    public function getFiscalByInventario(int $inventarioid)
    {
        $sql = "SELECT * FROM wms_inventario_fiscal 
            WHERE inventarioid = {$inventarioid}";
        return $this->select($sql);
    }


    public function insertFiscal($data)
    {
        $sql = "INSERT INTO wms_inventario_fiscal
    (inventarioid, clave_sat, desc_sat, 
     clave_unidad_sat, desc_unidad_sat,
     clave_fraccion_sat, desc_fraccion_sat,
     clave_aduana_sat, desc_aduana_sat, estado)
    VALUES (?,?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['inventarioid'],
            $data['clave_sat'],
            $data['desc_sat'],
            $data['clave_unidad_sat'],
            $data['desc_unidad_sat'],
            $data['clave_fraccion_sat'],
            $data['desc_fraccion_sat'],
            $data['clave_aduana_sat'],
            $data['desc_aduana_sat'],
            2
        ]);
    }



    public function updateFiscal($idfiscal, $data)
    {
        $sql = "UPDATE wms_inventario_fiscal SET
        clave_sat = ?,
        desc_sat = ?,
        clave_unidad_sat = ?,
        desc_unidad_sat = ?,
        clave_fraccion_sat = ?,
        desc_fraccion_sat = ?,
        clave_aduana_sat = ?,
        desc_aduana_sat = ?
        WHERE idfiscal = ?";

        return $this->update($sql, [
            $data['clave_sat'],
            $data['desc_sat'],
            $data['clave_unidad_sat'],
            $data['desc_unidad_sat'],
            $data['clave_fraccion_sat'],
            $data['desc_fraccion_sat'],
            $data['clave_aduana_sat'],
            $data['desc_aduana_sat'],
            $idfiscal
        ]);
    }

    public function updateFiscalParcial($idfiscal, $data)
    {
        if (empty($data)) return false;

        $set = [];
        $values = [];

        foreach ($data as $k => $v) {
            $set[] = "$k = ?";
            $values[] = $v;
        }

        $values[] = $idfiscal;

        $sql = "UPDATE wms_inventario_fiscal SET " . implode(',', $set) . " WHERE idfiscal = ?";

        return $this->update($sql, $values);
    }

    // ================= IMPUESTOS =================

    public function selectImpuestosCfg()
    {
        $sql = "SELECT idimpuesto, descripcion 
            FROM wms_impuestos 
            WHERE estado = 2";
        return $this->select_all($sql);
    }
    //================= MARCAS ===================
    public function selectMarcas()
    {
        $sql = "SELECT id, nombre 
            FROM wms_marcas
            WHERE estado = 2";
        return $this->select_all($sql);
    }

    public function insertInventarioImpuestoform($inventarioid, $idimpuesto, $estado)
    {
        $inventarioid = intval($inventarioid);
        $idimpuesto   = intval($idimpuesto);

        // ✅ verificar si ya existe
        $sqlCheck = "SELECT idinvimpuesto 
                 FROM wms_inventario_impuestos 
                 WHERE inventarioid = $inventarioid 
                   AND idimpuesto = $idimpuesto";

        $exist = $this->select($sqlCheck);

        if (!empty($exist)) {
            return "exist";
        }

        // ✅ insertar
        $sql = "INSERT INTO wms_inventario_impuestos
            (inventarioid,idimpuesto,estado)
            VALUES (?,?,?)";

        return $this->insert($sql, [
            $inventarioid,
            $idimpuesto,
            $estado
        ]);
    }


    public function getImpuestosAsignados($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT i.idimpuesto,
                   i.descripcion,
                   ii.estado
            FROM wms_impuestos i
            INNER JOIN wms_inventario_impuestos ii 
              ON i.idimpuesto = ii.idimpuesto
            WHERE ii.inventarioid = $idinventario";

        return $this->select_all($sql);
    }

    //filtros

    public function items(array $filters = []): array
    {
        $query = "SELECT
                    -- data inventario 
                    wms_inventario.idinventario,
                    wms_inventario.cve_articulo,
                    wms_inventario.descripcion,
                    wms_inventario.tipo_elemento,
                    wms_inventario.estado,
                    wms_inventario.ultimo_costo,
                    wms_inventario.unidad_salida,
                    -- data liea de producto
                    wms_linea_producto.descripcion AS descripcion_linea
                FROM wms_inventario
                LEFT JOIN wms_linea_producto
                    ON wms_linea_producto.idlineaproducto = wms_inventario.lineaproductoid
                WHERE true
            ";

        if (array_key_exists('id', $filters)) {
            $query .= "AND wms_inventario.idinventario = '{$filters['id']}'";
        }

        if (array_key_exists('estado', $filters)) {
            $query .= "AND wms_inventario.estado = '{$filters['estado']}'";
        }

        if (array_key_exists('sku', $filters)) {
            $query .= "AND wms_inventario.cve_articulo LIKE '%{$filters['sku']}%'";
        }

        return $this->select_all($query);
    }

    //----------------------------- UBICACIONES INVENTARIO

    public function insertInventarioUbicacion($inventarioid, $ubicacionid, $cantidad, $fecha)
    {
        $inventarioid = intval($inventarioid);
        $ubicacionid = intval($ubicacionid);
        $cantidad = intval($cantidad);

        // VALIDAR DUPLICADO
        $exist = $this->select("
        SELECT idubicacionasignada 
        FROM wms_ubicaciones_asignadas
        WHERE inventarioid = $inventarioid 
        AND ubicacionesid = $ubicacionid
    ");

        if (!empty($exist)) {
            return "exist";
        }

        // VALIDAR SI ESTÁ OCUPADA
        $ubicacion = $this->select("
        SELECT estado 
        FROM wms_ubicaciones 
        WHERE idubicaciones = $ubicacionid
    ");

        if (!empty($ubicacion) && $ubicacion['estado'] != 2) {
            return "ocupada";
        }

        // INSERT
        $sql = "INSERT INTO wms_ubicaciones_asignadas 
        (inventarioid, ubicacionesid, cantidad, fecha_creacion) 
        VALUES ($inventarioid, $ubicacionid, $cantidad, '$fecha')";

        $insert = $this->insert($sql, []);

        if ($insert > 0) {
            $sqlUpdate = "UPDATE wms_ubicaciones 
                      SET estado = 1 
                      WHERE idubicaciones = $ubicacionid";

            $this->update($sqlUpdate, []);

            return $insert;
        }

        return 0;
    }


    // 🔹 SELECT PARA DROPDOWN (TEXTO COMPLETO)
    public function selectUbicacionesFull()
    {
        $sql = "SELECT 
        u.idubicaciones,
        CONCAT(
            s.descripcion, ' - ',
            z.descripcion, ' - ',
            'P', u.pasillo, ' N', u.nivel, ' ', u.lugar
        ) AS nombre
    FROM wms_ubicaciones u
    INNER JOIN wms_zonas z ON u.zonaid = z.idzona
    INNER JOIN wms_sedes s ON z.sedeid = s.idsede
    WHERE u.estado = 2
    ORDER BY s.descripcion, z.descripcion";

        return $this->select_all($sql);
    }


    // 🔹 TABLA
    public function getUbicacionesAsignadas($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT 
        ua.idubicacionasignada,
        ua.cantidad,
        CONCAT(
            s.descripcion, ' - ',
            z.descripcion, ' - ',
            'P', u.pasillo, ' N', u.nivel, ' ', u.lugar
        ) AS ubicacion,
        ua.fecha_creacion
    FROM wms_ubicaciones_asignadas ua
    INNER JOIN wms_ubicaciones u ON ua.ubicacionesid = u.idubicaciones
    INNER JOIN wms_zonas z ON u.zonaid = z.idzona
    INNER JOIN wms_sedes s ON z.sedeid = s.idsede
    WHERE ua.inventarioid = $idinventario";

        return $this->select_all($sql);
    }
    /**
     * Actualiza el último costo negociado en el maestro de inventarios.
     */
    public function updateLastCost(int $idinventario, float $costo): bool
    {
        $sql = "UPDATE wms_inventario 
                SET ultimo_costo = ? 
                WHERE idinventario = ?";

        return $this->update($sql, [$costo, $idinventario]);
    }

    /**
     * Calcula la existencia actual de un artículo en un almacén específico
     * basándose en la sumatoria de movimientos.
     */
    public function getCurrentStock(int $idinventario, int $almacenid): float
    {
        // Sumamos (cantidad * signo). 
        // Entradas: (qty * 1), Salidas: (qty * -1)
        $sql = "SELECT IFNULL(SUM(cantidad * signo), 0) AS stock 
                FROM wms_movimientos_inventario 
                WHERE inventarioid = ? 
                AND almacenid = ? 
                AND estado = 2"; // Estado 2 = Movimiento Activo

        $result = $this->select($sql, [$idinventario, $almacenid]);

        return (float)($result['stock'] ?? 0);
    }

    /**
     * Registra un nuevo movimiento de entrada o salida en el Kardex.
     */
    public function addMovement(array $data): int
    {
        $sql = "INSERT INTO wms_movimientos_inventario (
                    inventarioid, 
                    almacenid, 
                    numero_movimiento, 
                    concepmovid, 
                    referencia, 
                    cantidad, 
                    costo, 
                    existencia, 
                    signo, 
                    fecha_movimiento,
                    estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 2)";

        return $this->insert($sql, [
            $data['inventarioid'],
            $data['almacenid'],
            $data['numero_movimiento'], // ID de la recepción
            $data['concepmovid'],       // Concepto (ej: 1 para compras)
            $data['referencia'],        // Num de remisión
            $data['cantidad'],
            $data['costo'],             // Costo unitario real
            $data['existencia'],        // Saldo calculado (nueva existencia)
            $data['signo']              // 1 o -1
        ]);
    }
    // ================= PROVEEDORES =================

    public function selectProveedoresCfg()
    {
        $sql = "SELECT id_proveedor, nombre_comercial 
            FROM prv_cat_proveedores 
            WHERE estatus_operativo = 0";
        return $this->select_all($sql);
    }

    public function insertInventarioProveedorform($inventarioid, $id_proveedor, $estado)
    {
        $inventarioid = intval($inventarioid);
        $id_proveedor   = intval($id_proveedor);

        //  verificar si ya existe
        $sqlCheck = "SELECT id_inv_proveedores 
                 FROM wms_inventario_proveedores 
                 WHERE inventarioid = $inventarioid 
                   AND id_proveedor = $id_proveedor";

        $exist = $this->select($sqlCheck);

        if (!empty($exist)) {
            return "exist";
        }

        //  insertar
        $sql = "INSERT INTO wms_inventario_proveedores
            (inventarioid,id_proveedor,estado)
            VALUES (?,?,?)";

        return $this->insert($sql, [
            $inventarioid,
            $id_proveedor,
            $estado
        ]);
    }


    public function getProveedoresAsignados($idinventario)
    {
        $idinventario = intval($idinventario);

        $sql = "SELECT p.id_proveedor,
                   p.nombre_comercial,
                   ip.estado
            FROM prv_cat_proveedores p
            INNER JOIN wms_inventario_proveedores ip 
              ON p.id_proveedor = ip.id_proveedor
            WHERE ip.inventarioid = $idinventario";

        return $this->select_all($sql);
    }


    // ================= CANTIDADES  =================
    public function selectCantidadesProducto(int $inventarioid)
    {
        $sql = "SELECT 
                i.idinventario AS inventarioid,
                IFNULL(SUM(ma.existencia),0) AS existencia_total,
                IFNULL(i.stock_minimo,0) AS stock_minimo,
                IFNULL(i.stock_maximo,0) AS stock_maximo,
                IFNULL(SUM(ma.pendiente_surtir),0) AS apartado
            FROM wms_inventario i
            LEFT JOIN wms_multialmacen ma 
                ON ma.inventarioid = i.idinventario
            WHERE i.idinventario = ?
            GROUP BY i.idinventario, i.stock_minimo, i.stock_maximo";

        return $this->select($sql, [$inventarioid]);
    }

    public function selectAlmacenesProducto(int $inventarioid)
    {
        $sql = "SELECT 
                a.descripcion AS almacen,
                m.existencia,
                m.stock_minimo,
                m.stock_maximo,
                m.pendiente_surtir AS apartado
            FROM wms_multialmacen m
            INNER JOIN wms_almacenes a 
                ON a.idalmacen = m.almacenid
            WHERE m.inventarioid = ?";

        return $this->select_all($sql, [$inventarioid]);
    }

    /**
     * VINes (wms_numeros_series) generados para este producto (tipo_elemento = P),
     * sin separar por lote/orden -- eso lo hace el controller con tipo_generacion.
     */
    public function selectVinesProducto(int $inventarioid)
    {
        $sql = "SELECT
                s.numero_serie,
                s.referencia,
                s.fecha,
                s.estado,
                s.tipo_generacion,
                a.descripcion AS almacen
            FROM wms_numeros_series s
            INNER JOIN wms_almacenes a
                ON a.idalmacen = s.almacenid
            WHERE s.inventarioid = ?
            ORDER BY s.fecha DESC";

        return $this->select_all($sql, [$inventarioid]);
    }

    /**
     * Inserta un nuevo artículo en el catálogo maestro.
     * Basado estrictamente en el DDL de wms_inventario.
     */
    public function insertOfficialItem(array $data): int
    {
        $sql = "INSERT INTO wms_inventario (
                    cve_articulo, descripcion, lineaproductoid, serie, 
                    unidad_salida, unidad_empaque, control_almacen, tiempo_surtido, 
                    ultimo_costo, tipo_elemento, unidad_entrada, factor_unidades, 
                    lote, pedimiento, peso, volumen, fecha_creacion, estado
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)";

        // Mapeo exacto para evitar errores de integridad
        $params = [
            $data['cve_articulo'],
            $data['descripcion'],
            (int)$data['lineaproductoid'],
            $data['serie'] ?? 'N',                // S=si, N=NO
            $data['unidad_salida'],
            (float)($data['unidad_empaque'] ?? 1),
            $data['control_almacen'] ?? 'FIFO',
            (int)($data['tiempo_surtido'] ?? 0),
            (float)$data['ultimo_costo'],
            $data['tipo_elemento'],               // K,P,S,H,C
            $data['unidad_entrada'] ?? $data['unidad_salida'],
            (float)($data['factor_unidades'] ?? 1),
            $data['lote'] ?? 'N',                 // S=Si, N=No
            $data['pedimiento'] ?? 'N',           // S=Si, N=No
            (float)($data['peso'] ?? 0),
            (float)($data['volumen'] ?? 0),
            '2'                                   // 2 = Activa
        ];

        return $this->insert($sql, $params) ?? 0;
    }

    /**
     * Registra al proveedor como fuente autorizada para un artículo.
     * Implementa 'ON DUPLICATE KEY UPDATE' para actualizar el precio si el vínculo ya existe.
     */
    public function linkSupplierToItem(array $data): bool
    {
        $sql = "INSERT INTO wms_proveedor_articulos (
                    id_proveedor, idinventario, precio_referencia, id_moneda, 
                    fecha_acuerdo, created_by
                ) VALUES (?, ?, ?, ?, CURRENT_DATE, ?)
                ON DUPLICATE KEY UPDATE 
                    precio_referencia = VALUES(precio_referencia),
                    id_moneda = VALUES(id_moneda),
                    updated_at = CURRENT_TIMESTAMP";

        return $this->insert($sql, [
            (int)$data['id_proveedor'],
            (int)$data['idinventario'],
            (float)$data['precio_referencia'],
            $data['id_moneda'],
            (int)$data['created_by']
        ]);
    }
}
