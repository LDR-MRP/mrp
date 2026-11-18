<?php

/**
 * Sincroniza el catálogo maestro de Clientes / Sucursales / Direcciones de Entrega (`cli_*`)
 * hacia las tablas operativas de Logística (`lgs_cat_distribuidores` y `lgs_cat_ubicaciones`),
 * preservando siempre los `id_ubicacion` existentes (para conservar `lgs_distancias` y `lgs_costos_rutas`).
 */
class Lgs_clientesSyncService
{
    private PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?: Conexion::getInstance()->conect();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Sincroniza todos los clientes elegibles (Distribuidores o clientes con actividad comercial/logística)
     * junto con sus sucursales (`cli_clientes_sucursales`) y direcciones de entrega (`cli_direcciones`).
     */
    public function sincronizarDistribuidoresYSedes(): array
    {
        $stats = [
            'clientes_procesados' => 0,
            'distribuidores_sync' => 0,
            'sedes_sync'          => 0,
            'direcciones_sync'    => 0,
        ];

        $clientes = $this->db->query("
            SELECT c.idcliente, c.idtipo_cliente, c.codigo_cliente, c.razon_social, c.nombre_comercial,
                   c.clave_distribuidor, c.estado, COALESCE(t.nombre, '') AS tipo_nombre
            FROM cli_clientes c
            LEFT JOIN cli_tipos_cliente t ON t.id = c.idtipo_cliente
            WHERE c.estado = 2
              AND (
                  c.idtipo_cliente = 1
                  OR UPPER(TRIM(COALESCE(t.nombre, ''))) = 'DISTRIBUIDOR'
                  OR EXISTS (SELECT 1 FROM lgs_cat_distribuidores d WHERE d.id_cliente = c.idcliente)
                  OR EXISTS (SELECT 1 FROM ped_pedidos p WHERE p.idcliente = c.idcliente AND p.estado = 2)
                  OR EXISTS (SELECT 1 FROM wms_vin_facturacion vf WHERE vf.id_cliente = c.idcliente AND vf.estatus IN (1, 2))
              )
            ORDER BY c.idcliente ASC
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($clientes as $cli) {
            $stats['clientes_procesados']++;
            $res = $this->sincronizarFilaCliente($cli);
            if (!empty($res['id_distribuidor'])) {
                $stats['distribuidores_sync']++;
            }
            $stats['sedes_sync'] += (int)($res['sedes_sync'] ?? 0);
            $stats['direcciones_sync'] += (int)($res['direcciones_sync'] ?? 0);
        }

        // Desactivar distribuidores/ubicaciones cuyo cliente ya no está activo en cli_clientes
        $this->db->exec("
            UPDATE lgs_cat_distribuidores d
            INNER JOIN cli_clientes c ON c.idcliente = d.id_cliente
            SET d.activo = 0
            WHERE c.estado <> 2 AND d.activo <> 0
        ");
        $this->db->exec("
            UPDATE lgs_cat_ubicaciones u
            INNER JOIN cli_clientes c ON c.idcliente = u.id_cliente
            SET u.activo = 0
            WHERE c.estado <> 2 AND u.activo <> 0
        ");

        return $stats;
    }

    /**
     * Sincronización incremental de un cliente puntual desde Clientes, Pedidos o Logística.
     */
    public function sincronizarCliente(int $idCliente): array
    {
        if ($idCliente <= 0) {
            return ['id_distribuidor' => null, 'sedes_sync' => 0, 'direcciones_sync' => 0];
        }

        $stmt = $this->db->prepare("
            SELECT c.idcliente, c.idtipo_cliente, c.codigo_cliente, c.razon_social, c.nombre_comercial,
                   c.clave_distribuidor, c.estado, COALESCE(t.nombre, '') AS tipo_nombre
            FROM cli_clientes c
            LEFT JOIN cli_tipos_cliente t ON t.id = c.idtipo_cliente
            WHERE c.idcliente = ?
            LIMIT 1
        ");
        $stmt->execute([$idCliente]);
        $cli = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$cli) {
            return ['id_distribuidor' => null, 'sedes_sync' => 0, 'direcciones_sync' => 0];
        }

        if ((int)$cli['estado'] !== 2) {
            $this->db->prepare("UPDATE lgs_cat_distribuidores SET activo = 0 WHERE id_cliente = ?")->execute([$idCliente]);
            $this->db->prepare("UPDATE lgs_cat_ubicaciones SET activo = 0 WHERE id_cliente = ?")->execute([$idCliente]);
            return ['id_distribuidor' => null, 'sedes_sync' => 0, 'direcciones_sync' => 0];
        }

        return $this->sincronizarFilaCliente($cli);
    }

    /**
     * Resuelve el idcliente en `cli_clientes` dado un texto libre o clave de distribuidor
     * (usado en Carga Masiva de Ventas y conciliación histórica).
     */
    public function resolverIdClientePorNombre(string $texto): ?int
    {
        $normBuscado = self::normalizarTexto($texto);
        if ($normBuscado === '') {
            return null;
        }
        $compactBuscado = str_replace(' ', '', $normBuscado);

        // 1. Buscar coincidencia directa en cli_clientes activos
        $clientes = $this->db->query("
            SELECT idcliente, codigo_cliente, clave_distribuidor, razon_social, nombre_comercial
            FROM cli_clientes
            WHERE estado = 2
            ORDER BY idcliente ASC
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($clientes as $c) {
            $candidatos = [
                (string)($c['razon_social'] ?? ''),
                (string)($c['nombre_comercial'] ?? ''),
                (string)($c['codigo_cliente'] ?? ''),
                (string)($c['clave_distribuidor'] ?? ''),
            ];
            foreach ($candidatos as $cand) {
                $normCand = self::normalizarTexto($cand);
                if ($normCand === '') {
                    continue;
                }
                if ($normCand === $normBuscado || str_replace(' ', '', $normCand) === $compactBuscado) {
                    return (int)$c['idcliente'];
                }
            }
        }

        // 2. Alias conocidos históricos en lgs_cat_distribuidores ya enlazados
        $dists = $this->db->query("
            SELECT id_cliente, nombre, clave
            FROM lgs_cat_distribuidores
            WHERE id_cliente IS NOT NULL
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($dists as $d) {
            $normDist = self::normalizarTexto((string)($d['nombre'] ?? ''));
            $normClave = self::normalizarTexto((string)($d['clave'] ?? ''));
            if (
                ($normDist !== '' && ($normDist === $normBuscado || str_replace(' ', '', $normDist) === $compactBuscado))
                || ($normClave !== '' && $normClave === $normBuscado)
            ) {
                return (int)$d['id_cliente'];
            }
        }

        return null;
    }

    private function sincronizarFilaCliente(array $cli): array
    {
        $idCliente = (int)($cli['idcliente'] ?? 0);
        if ($idCliente <= 0) {
            return ['id_distribuidor' => null, 'sedes_sync' => 0, 'direcciones_sync' => 0];
        }

        $idDistribuidor = $this->upsertDistribuidorParaCliente($cli);
        $esDistribuidor = ((int)($cli['idtipo_cliente'] ?? 0) === 1)
            || (strtoupper(trim((string)($cli['tipo_nombre'] ?? ''))) === 'DISTRIBUIDOR');
        $idTipoDestino = $esDistribuidor ? 1 : 3;

        $sedesSync = $this->sincronizarSucursalesCliente($idCliente, $idDistribuidor, $idTipoDestino, $cli);
        $dirSync   = $this->sincronizarDireccionesEntregaCliente($idCliente, $idDistribuidor, $idTipoDestino, $cli);

        // Si el cliente tiene sucursales específicas o direcciones en cli_direcciones, ocultar homónimas legacy sin dirección real
        $nombreCliNorm = self::normalizarTexto(self::resolverNombreCliente($cli));
        if ($nombreCliNorm !== '') {
            $stOtras = $this->db->prepare("
                SELECT COUNT(*)
                FROM lgs_cat_ubicaciones
                WHERE id_cliente = ?
                  AND activo = 1
                  AND (
                      id_direccion_cli IS NOT NULL
                      OR UPPER(TRIM(nombre)) <> ?
                  )
            ");
            $stOtras->execute([$idCliente, $nombreCliNorm]);
            if ((int)$stOtras->fetchColumn() > 0) {
                $stDesactLegacy = $this->db->prepare("
                    SELECT id_ubicacion, nombre, id_sucursal_cli
                    FROM lgs_cat_ubicaciones
                    WHERE id_cliente = ?
                      AND activo = 1
                      AND id_direccion_cli IS NULL
                      AND (direccion IS NULL OR TRIM(direccion) = '' OR UPPER(TRIM(direccion)) = UPPER(TRIM(nombre)))
                ");
                $stDesactLegacy->execute([$idCliente]);
                foreach ($stDesactLegacy->fetchAll(PDO::FETCH_ASSOC) ?: [] as $rowLeg) {
                    if (self::normalizarTexto((string)$rowLeg['nombre']) === $nombreCliNorm) {
                        $this->db->prepare("UPDATE lgs_cat_ubicaciones SET activo = 0 WHERE id_ubicacion = ?")
                            ->execute([(int)$rowLeg['id_ubicacion']]);
                        if (!empty($rowLeg['id_sucursal_cli'])) {
                            $this->db->prepare("UPDATE cli_clientes_sucursales SET estado = 0 WHERE idsucursal = ? AND codigo_postal = '00000'")
                                ->execute([(int)$rowLeg['id_sucursal_cli']]);
                        }
                    }
                }
            }
        }

        // Asegurar propagación de id_cliente en ubicaciones previamente ligadas a id_distribuidor
        $this->db->prepare("
            UPDATE lgs_cat_ubicaciones
            SET id_cliente = ?
            WHERE id_distribuidor = ?
              AND (id_cliente IS NULL OR id_cliente <> ?)
        ")->execute([$idCliente, $idDistribuidor, $idCliente]);

        return [
            'id_distribuidor'  => $idDistribuidor,
            'sedes_sync'       => $sedesSync,
            'direcciones_sync' => $dirSync,
        ];
    }

    private function upsertDistribuidorParaCliente(array $cli): int
    {
        $idCliente = (int)$cli['idcliente'];
        $nombreBase = self::resolverNombreCliente($cli);
        $clave = mb_substr(trim((string)($cli['clave_distribuidor'] ?: $cli['codigo_cliente'] ?: '')), 0, 20);
        $clave = $clave !== '' ? $clave : null;

        // 1) Buscar por id_cliente
        $st = $this->db->prepare("SELECT id_distribuidor, nombre FROM lgs_cat_distribuidores WHERE id_cliente = ? LIMIT 1");
        $st->execute([$idCliente]);
        $exist = $st->fetch(PDO::FETCH_ASSOC);

        if (!$exist) {
            // 2) Buscar distribuidor aún sin id_cliente cuyo nombre coincida sin acentos/espacios
            $allFree = $this->db->query("
                SELECT id_distribuidor, nombre
                FROM lgs_cat_distribuidores
                WHERE id_cliente IS NULL
                ORDER BY id_distribuidor ASC
            ")->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $normRaz = str_replace(' ', '', self::normalizarTexto((string)($cli['razon_social'] ?? '')));
            $normCom = str_replace(' ', '', self::normalizarTexto((string)($cli['nombre_comercial'] ?? '')));

            foreach ($allFree as $row) {
                $normRow = str_replace(' ', '', self::normalizarTexto((string)$row['nombre']));
                if ($normRow !== '' && ($normRow === $normRaz || ($normCom !== '' && $normRow === $normCom))) {
                    $exist = $row;
                    break;
                }
            }
        }

        if ($exist) {
            $idDist = (int)$exist['id_distribuidor'];
            $nombreFinal = $this->resolverNombreUnicoDistribuidor($nombreBase, $idDist, $idCliente);
            $this->db->prepare("
                UPDATE lgs_cat_distribuidores
                SET nombre = ?, clave = COALESCE(?, clave), id_cliente = ?, activo = 1
                WHERE id_distribuidor = ?
            ")->execute([$nombreFinal, $clave, $idCliente, $idDist]);
            return $idDist;
        }

        $nombreFinal = $this->resolverNombreUnicoDistribuidor($nombreBase, 0, $idCliente);
        $this->db->prepare("
            INSERT INTO lgs_cat_distribuidores (nombre, clave, id_cliente, activo)
            VALUES (?, ?, ?, 1)
        ")->execute([$nombreFinal, $clave, $idCliente]);

        return (int)$this->db->lastInsertId();
    }

    private function sincronizarSucursalesCliente(int $idCliente, int $idDistribuidor, int $idTipoDestino, array $cli): int
    {
        $st = $this->db->prepare("
            SELECT idsucursal, nombre_sucursal, calle, numero_exterior, numero_interior,
                   colonia, codigo_postal, municipio, estado_republica, pais, estado
            FROM cli_clientes_sucursales
            WHERE idcliente = ?
            ORDER BY idsucursal ASC
        ");
        $st->execute([$idCliente]);
        $sucursales = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $count = 0;
        foreach ($sucursales as $s) {
            $idSuc = (int)$s['idsucursal'];
            $activo = ((int)($s['estado'] ?? 0) === 2) ? 1 : 0;
            $nombreSuc = trim((string)($s['nombre_sucursal'] ?? ''));
            if ($nombreSuc === '') {
                continue;
            }
            $direccion = self::construirDireccionLegible($s);
            if ($direccion !== '' && self::normalizarTexto($direccion) === self::normalizarTexto($nombreSuc)) {
                $direccion = '';
            }

            $ex = $this->db->prepare("SELECT id_ubicacion, nombre, id_tipo_destino FROM lgs_cat_ubicaciones WHERE id_sucursal_cli = ? LIMIT 1");
            $ex->execute([$idSuc]);
            $ubi = $ex->fetch(PDO::FETCH_ASSOC);

            if (!$ubi && $activo === 1) {
                // Reusar ubicación sin sucursal asignada perteneciente al mismo distribuidor/cliente si el nombre coincide
                $allUbi = $this->db->prepare("
                    SELECT id_ubicacion, nombre, id_tipo_destino
                    FROM lgs_cat_ubicaciones
                    WHERE (id_cliente = ? OR id_distribuidor = ?)
                      AND id_sucursal_cli IS NULL
                      AND id_direccion_cli IS NULL
                    ORDER BY id_ubicacion ASC
                ");
                $allUbi->execute([$idCliente, $idDistribuidor]);
                $normSuc = self::normalizarTexto($nombreSuc);
                foreach ($allUbi->fetchAll(PDO::FETCH_ASSOC) ?: [] as $cand) {
                    if (self::normalizarTexto((string)$cand['nombre']) === $normSuc) {
                        $ubi = $cand;
                        break;
                    }
                }
            }

            if ($ubi) {
                $idUbi = (int)$ubi['id_ubicacion'];
                if ($activo === 0) {
                    $this->db->prepare("UPDATE lgs_cat_ubicaciones SET activo = 0 WHERE id_ubicacion = ?")->execute([$idUbi]);
                } else {
                    $nombreUnico = $this->resolverNombreUnicoUbicacion($nombreSuc, $cli, $idUbi);
                    $tipoFinal = !empty($ubi['id_tipo_destino']) ? (int)$ubi['id_tipo_destino'] : $idTipoDestino;
                    $this->db->prepare("
                        UPDATE lgs_cat_ubicaciones
                        SET nombre = ?,
                            direccion = NULLIF(?, ''),
                            id_tipo_destino = ?,
                            id_distribuidor = ?,
                            id_cliente = ?,
                            id_sucursal_cli = ?,
                            activo = 1
                        WHERE id_ubicacion = ?
                    ")->execute([$nombreUnico, $direccion, $tipoFinal, $idDistribuidor, $idCliente, $idSuc, $idUbi]);
                }
                $count++;
                continue;
            }

            if ($activo === 1) {
                $nombreUnico = $this->resolverNombreUnicoUbicacion($nombreSuc, $cli, 0);
                $this->db->prepare("
                    INSERT INTO lgs_cat_ubicaciones
                        (nombre, direccion, id_tipo_destino, id_distribuidor, id_cliente, id_sucursal_cli, activo)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ")->execute([$nombreUnico, $direccion !== '' ? $direccion : null, $idTipoDestino, $idDistribuidor, $idCliente, $idSuc]);
                $count++;
            }
        }

        return $count;
    }

    private function sincronizarDireccionesEntregaCliente(int $idCliente, int $idDistribuidor, int $idTipoDestino, array $cli): int
    {
        $st = $this->db->prepare("
            SELECT iddireccion, tipo_direccion, calle, numero_exterior, numero_interior,
                   colonia, codigo_postal, municipio, estado_republica, pais, estado
            FROM cli_direcciones
            WHERE idcliente = ?
            ORDER BY iddireccion ASC
        ");
        $st->execute([$idCliente]);
        $direcciones = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $stHasSuc = $this->db->prepare("SELECT COUNT(*) FROM cli_clientes_sucursales WHERE idcliente = ? AND estado = 2");
        $stHasSuc->execute([$idCliente]);
        $tieneSucursales = ((int)$stHasSuc->fetchColumn()) > 0;
        if (!$tieneSucursales) {
            $stHasUbi = $this->db->prepare("
                SELECT COUNT(*)
                FROM lgs_cat_ubicaciones
                WHERE (id_cliente = ? OR id_distribuidor = ?)
                  AND activo = 1
                  AND id_direccion_cli IS NULL
            ");
            $stHasUbi->execute([$idCliente, $idDistribuidor]);
            $tieneSucursales = ((int)$stHasUbi->fetchColumn()) > 0;
        }
        $tieneEntregaExplicit = false;
        foreach ($direcciones as $chkD) {
            if ((int)($chkD['estado'] ?? 0) === 2 && in_array(strtoupper(trim((string)($chkD['tipo_direccion'] ?? ''))), ['ENTREGA', 'SUCURSAL'], true)) {
                $tieneEntregaExplicit = true;
                break;
            }
        }

        $count = 0;
        foreach ($direcciones as $d) {
            $idDir = (int)$d['iddireccion'];
            $tipoDir = strtoupper(trim((string)($d['tipo_direccion'] ?? '')));
            $esEntrega = in_array($tipoDir, ['ENTREGA', 'SUCURSAL'], true)
                || (!$tieneSucursales && !$tieneEntregaExplicit && $tipoDir !== '');
            $activo = ($esEntrega && (int)($d['estado'] ?? 0) === 2) ? 1 : 0;

            $ex = $this->db->prepare("SELECT id_ubicacion, id_tipo_destino FROM lgs_cat_ubicaciones WHERE id_direccion_cli = ? LIMIT 1");
            $ex->execute([$idDir]);
            $ubi = $ex->fetch(PDO::FETCH_ASSOC);

            if ($activo === 0) {
                if ($ubi) {
                    $this->db->prepare("UPDATE lgs_cat_ubicaciones SET activo = 0 WHERE id_ubicacion = ?")->execute([(int)$ubi['id_ubicacion']]);
                }
                continue;
            }

            $lugar = trim((string)($d['municipio'] ?? ''));
            $edo = trim((string)($d['estado_republica'] ?? ''));
            $calle = trim((string)($d['calle'] ?? ''));
            $prefijo = ($tipoDir === 'FISCAL' || $tipoDir === 'COMERCIAL') ? ('Domicilio ' . ucfirst(strtolower($tipoDir)) . ': ') : 'Entrega: ';
            $etiqueta = trim($prefijo . ($lugar !== '' ? $lugar : $calle) . ($edo !== '' && strcasecmp($lugar, $edo) !== 0 ? ', ' . $edo : ''));
            if ($calle !== '' && strcasecmp($lugar, $calle) !== 0) {
                $etiqueta = mb_substr($etiqueta . ' (' . $calle . ')', 0, 145);
            }
            $direccion = self::construirDireccionLegible($d);

            if ($ubi) {
                $idUbi = (int)$ubi['id_ubicacion'];
                $nombreUnico = $this->resolverNombreUnicoUbicacion($etiqueta, $cli, $idUbi);
                $tipoFinal = !empty($ubi['id_tipo_destino']) ? (int)$ubi['id_tipo_destino'] : $idTipoDestino;
                $this->db->prepare("
                    UPDATE lgs_cat_ubicaciones
                    SET nombre = ?,
                        direccion = COALESCE(NULLIF(?, ''), direccion),
                        id_tipo_destino = ?,
                        id_distribuidor = ?,
                        id_cliente = ?,
                        id_direccion_cli = ?,
                        activo = 1
                    WHERE id_ubicacion = ?
                ")->execute([$nombreUnico, $direccion, $tipoFinal, $idDistribuidor, $idCliente, $idDir, $idUbi]);
            } else {
                $nombreUnico = $this->resolverNombreUnicoUbicacion($etiqueta, $cli, 0);
                $this->db->prepare("
                    INSERT INTO lgs_cat_ubicaciones
                        (nombre, direccion, id_tipo_destino, id_distribuidor, id_cliente, id_direccion_cli, activo)
                    VALUES (?, ?, ?, ?, ?, ?, 1)
                ")->execute([$nombreUnico, $direccion !== '' ? $direccion : null, $idTipoDestino, $idDistribuidor, $idCliente, $idDir]);
            }
            $count++;
        }

        return $count;
    }

    private function resolverNombreUnicoDistribuidor(string $nombreBase, int $excludeId, int $idCliente = 0): string
    {
        $cand = mb_substr(trim($nombreBase), 0, 150);
        if ($cand === '') {
            $cand = 'DISTRIBUIDOR #' . ($idCliente ?: ($excludeId ?: time()));
        }
        $st = $this->db->prepare("SELECT id_distribuidor FROM lgs_cat_distribuidores WHERE nombre = ? AND id_distribuidor <> ? LIMIT 1");
        $st->execute([$cand, $excludeId]);
        if (!$st->fetch()) {
            return $cand;
        }
        $suf = ' #' . ($idCliente ?: ($excludeId ?: 1));
        return mb_substr($cand, 0, 150 - mb_strlen($suf)) . $suf;
    }

    private function resolverNombreUnicoUbicacion(string $nombreBase, array $cli, int $excludeId): string
    {
        $cand = mb_substr(trim($nombreBase), 0, 150);
        if ($cand === '') {
            $cand = 'SEDE #' . ($excludeId ?: time());
        }
        $st = $this->db->prepare("SELECT id_ubicacion FROM lgs_cat_ubicaciones WHERE nombre = ? AND id_ubicacion <> ? LIMIT 1");
        $st->execute([$cand, $excludeId]);
        if (!$st->fetch()) {
            return $cand;
        }

        $cliNom = mb_substr(self::resolverNombreCliente($cli), 0, 40);
        $conCli = mb_substr($cand, 0, 150 - mb_strlen(" ({$cliNom})")) . " ({$cliNom})";
        $st->execute([$conCli, $excludeId]);
        if (!$st->fetch()) {
            return $conCli;
        }

        $idCli = (int)($cli['idcliente'] ?? 0);
        $suf = ' #' . ($idCli ?: rand(100, 999));
        $final = mb_substr($conCli, 0, 150 - mb_strlen($suf)) . $suf;
        $i = 1;
        while (true) {
            $st->execute([$final, $excludeId]);
            if (!$st->fetch()) {
                return $final;
            }
            $tag = '-' . (++$i);
            $final = mb_substr($conCli, 0, 150 - mb_strlen($suf . $tag)) . $suf . $tag;
        }
    }

    public static function resolverNombreCliente(array $cli): string
    {
        $raz = trim((string)($cli['razon_social'] ?? ''));
        $com = trim((string)($cli['nombre_comercial'] ?? ''));
        return $raz !== '' ? $raz : ($com !== '' ? $com : ('CLIENTE #' . (int)($cli['idcliente'] ?? 0)));
    }

    public static function construirDireccionLegible(array $row): string
    {
        $calle = trim((string)($row['calle'] ?? ''));
        $ext   = trim((string)($row['numero_exterior'] ?? ''));
        $int   = trim((string)($row['numero_interior'] ?? ''));
        $col   = trim((string)($row['colonia'] ?? ''));
        $cp    = trim((string)($row['codigo_postal'] ?? ''));
        $mun   = trim((string)($row['municipio'] ?? ''));
        $edo   = trim((string)($row['estado_republica'] ?? ''));
        $pais  = trim((string)($row['pais'] ?? ''));

        $partes = [];
        $via = trim($calle . ($ext !== '' && strtoupper($ext) !== 'S/N' ? ' #' . $ext : '') . ($int !== '' ? ' Int. ' . $int : ''));
        if ($via !== '') $partes[] = $via;
        if ($col !== '') $partes[] = 'Col. ' . $col;
        if ($cp !== '' && $cp !== '00000') $partes[] = 'C.P. ' . $cp;
        if ($mun !== '') $partes[] = $mun;
        if ($edo !== '') $partes[] = $edo;
        if ($pais !== '' && strcasecmp($pais, 'México') !== 0 && strcasecmp($pais, 'Mexico') !== 0) $partes[] = $pais;

        return mb_substr(implode(', ', $partes), 0, 255);
    }

    public static function normalizarTexto(string $texto): string
    {
        $busca = ['á', 'é', 'í', 'ó', 'ú', 'ü', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ü', 'ñ', 'Ñ'];
        $reemp = ['a', 'e', 'i', 'o', 'u', 'u', 'A', 'E', 'I', 'O', 'U', 'U', 'n', 'N'];
        $limpio = str_replace($busca, $reemp, trim($texto));
        $limpio = preg_replace('/\s+/', ' ', $limpio);
        return strtoupper(trim((string)$limpio));
    }
}
