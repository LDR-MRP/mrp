<?php

class Inv_seriesModel extends Mysql
{

    public function __construct()
    {
        parent::__construct();
    }

    public function selectSeries()
    {
        $sql = "SELECT s.*,
                   i.descripcion AS producto,
                   a.descripcion AS almacen,
                   va.numero_motor,
                   va.vin_origen,
                   va.color,
                   mv.modelo AS modelo_vin,
                   mv.vin_base
            FROM wms_numeros_series s
            INNER JOIN wms_inventario i
                ON s.inventarioid = i.idinventario
            INNER JOIN wms_almacenes a
                ON s.almacenid = a.idalmacen
            LEFT JOIN wms_vin_asociaciones va
                ON va.id_numeros_serie = s.id_numeros_serie
            LEFT JOIN cat_modelos_vin mv
                ON mv.id_cat_modelo_vin = s.id_cat_modelo_vin
            WHERE s.estado != 0";

        return $this->select_all($sql);
    }

    public function searchProductosOrden()
    {
        $sql = "SELECT 
                i.idinventario,
                i.cve_articulo,
                i.descripcion,
                i.serie,
                p.idproducto,
                pl.num_orden
            FROM wms_inventario i
            INNER JOIN mrp_productos p 
                ON p.inventarioid = i.idinventario
            LEFT JOIN mrp_planeacion pl 
                ON pl.productoid = p.idproducto
            ORDER BY i.descripcion ASC";

        return $this->select_all($sql);
    }

    public function searchProductosLote($term = "")
    {
        $sql = "SELECT 
            i.idinventario,
            i.cve_articulo,
            i.descripcion,
            i.serie,
            i.tipo_elemento
        FROM wms_inventario i
        WHERE i.estado != 0 
        AND i.tipo_elemento = 'P'
        ORDER BY i.descripcion ASC";

        return $this->select_all($sql);
    }

    public function searchOrdenesTrabajo()
    {
        $sql = "SELECT 
                pl.idplaneacion,
                pl.num_orden,
                pl.cantidad,
                i.idinventario,
                i.descripcion AS producto
            FROM mrp_planeacion pl
            INNER JOIN mrp_productos p 
                ON pl.productoid = p.idproducto
            INNER JOIN wms_inventario i 
                ON p.inventarioid = i.idinventario
            ORDER BY pl.fecha_requerida DESC";

        return $this->select_all($sql);
    }


    public function selectAlmacenesSeries()
    {
        $sql = "SELECT idalmacen, cve_almacen, descripcion
            FROM wms_almacenes
            WHERE estado = 2
            ORDER BY descripcion ASC";

        return $this->select_all($sql);
    }


    public function validarSeries($inventarioid, $almacenid, $baseVin, $cantidad)
    {
        if ($cantidad <= 0) {
            return ["status" => false, "msg" => "Cantidad inválida"];
        }

        $baseVin = strtoupper(trim($baseVin));

        //  Obligatorio mínimo 11
        if (strlen($baseVin) < 11) {
            return ["status" => false, "msg" => "Los primeros 11 caracteres del VIN son obligatorios"];
        }

        //  Máximo 17
        if (strlen($baseVin) > 17) {
            return ["status" => false, "msg" => "El VIN no puede exceder 17 caracteres"];
        }

        //  Solo letras y números
        if (!preg_match('/^[A-Z0-9]+$/', $baseVin)) {
            return ["status" => false, "msg" => "El VIN solo puede contener letras y números"];
        }

        //  No permitir I O Q
        if (preg_match('/[IOQÑ]/', $baseVin)) {
            return ["status" => false, "msg" => "El VIN no puede contener I, O o Q"];
        }


        $parteFija = $baseVin;
        $contador = 1;

        if (strlen($baseVin) == 17) {

            if (preg_match('/(\d+)$/', $baseVin, $matches)) {
                $numeroBase = $matches[1];
                $longitudNumerica = strlen($numeroBase);
                $parteFija = substr($baseVin, 0, -$longitudNumerica);
                $contador = intval($numeroBase);
            } else {
                return ["status" => false, "msg" => "El VIN debe terminar en números"];
            }
        } else {
            $longitudNumerica = 17 - strlen($baseVin);
        }

        $generados = [];
        $repetidos = [];
        $disponibles = [];

        for ($i = 0; $i < $cantidad; $i++) {

            $nuevoNumero = str_pad($contador + $i, $longitudNumerica, "0", STR_PAD_LEFT);
            $vinFinal = $parteFija . $nuevoNumero;

            $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
            $existente = $this->select($sqlCheck, array($vinFinal));

            $generados[] = $vinFinal;

            if (!empty($existente)) {
                $repetidos[] = $vinFinal;
            } else {
                $disponibles[] = $vinFinal;
            }
        }

        return [
            "status" => true,
            "generados" => $generados,
            "repetidos" => $repetidos,
            "disponibles" => $disponibles
        ];
    }


    public function insertarSeriesConfirmadas($lista, $inventarioid, $almacenid, $referencia, $costo, $modo, $idModeloVin = null, $usuarioid = null)
    {
        $fecha = date('Y-m-d H:i:s');
        $insertados = 0;
        $idModeloVin = !empty($idModeloVin) ? intval($idModeloVin) : null;
        $historial = new Inv_vin_historialModel();
        $detalleGen = "Generado por " . ($modo === "orden" ? "orden " : "lote ") . $referencia;
        if ($idModeloVin) {
            $m = $this->select("SELECT modelo, vin_base FROM cat_modelos_vin WHERE id_cat_modelo_vin = ?", [$idModeloVin]);
            if (!empty($m)) {
                $detalleGen .= " · modelo " . $m['modelo'] . " (VIN base " . $m['vin_base'] . ")";
            }
        }

        $cantidad = count($lista);

        // ============================
        // VALIDAR ORDEN (AQUÍ VA)
        // ============================
        if ($modo === "orden") {
            $sql = "SELECT COUNT(*) as total 
            FROM wms_numeros_series 
            WHERE referencia = ?";

            $existe = $this->select($sql, [$referencia]);

            if ($existe['total'] > 0) {
                return [
                    "status" => false,
                    "msg" => "Esta orden ya tiene VIN generados"
                ];
            }
        }

        // ============================
        //  CONSECUTIVO POR AÑO (posición 10)
        // El consecutivo es compartido por todos los modelos del mismo año.
        // Si entre la vista previa y la confirmación alguien más generó VIN
        // de ese año, los consecutivos ya no son válidos: se rechaza.
        // ============================
        $anioLote = strtoupper(substr(trim($lista[0]), 9, 1));
        $minConsecutivo = null;

        foreach ($lista as $v) {
            $v = strtoupper(trim($v));
            if (substr($v, 9, 1) !== $anioLote) {
                return [
                    "status" => false,
                    "msg" => "Todos los VIN del lote deben ser del mismo año (posición 10)."
                ];
            }
            $cons = intval(substr($v, 11, 6));
            if ($minConsecutivo === null || $cons < $minConsecutivo) {
                $minConsecutivo = $cons;
            }
        }

        $ultimo = $this->getUltimoConsecutivo($lista[0]);

        if ($minConsecutivo <= $ultimo) {
            return [
                "status" => false,
                "msg" => "El último consecutivo del año '$anioLote' ahora es "
                    . str_pad($ultimo, 6, "0", STR_PAD_LEFT)
                    . ". Vuelva a generar la vista previa."
            ];
        }

        // Lote generado para el almacén de tránsito (ALM-TRANSITO): los VINs
        // entran con existencia (concepto Ajustes) para poder facturarlos y
        // después hacer devoluciones aunque no estén físicamente ahí.
        $esTransito = ($modo === 'lote') && $this->esAlmacenTransito((int) $almacenid);
        $vinesTransito = [];
        $pdo = $this->conexion;
        if ($esTransito) {
            $pdo->beginTransaction();
        }

        try {
        for ($i = 0; $i < $cantidad; $i++) {

            $vin = strtoupper(trim($lista[$i]));

            // validaciones
            if (strlen($vin) != 17) continue;
            if (preg_match('/[IOQÑ]/', $vin)) continue;
            if (!preg_match('/^[A-Z0-9]{17}$/', $vin)) continue;

            // validar duplicado
            $sqlCheck = "SELECT id_numeros_serie FROM wms_numeros_series WHERE numero_serie = ?";
            $existente = $this->select($sqlCheck, array($vin));

            if (!empty($existente)) {
                if ($modo === "orden") {
                    return [
                        "status" => false,
                        "msg" => "La orden ya tiene VIN generados o hay duplicados."
                    ];
                }
                continue;
            }

            $sql = "INSERT INTO wms_numeros_series
    (inventarioid, id_cat_modelo_vin, almacenid, numero_serie, referencia, costo, fecha, estado, tipo_generacion)
    VALUES (?,?,?,?,?,?,?,?,?)";

            $arrData = [
                $inventarioid,
                $idModeloVin,
                $almacenid,
                $vin,
                $referencia,
                $costo,
                $fecha,
                1,
                $modo
            ];

            $insert = $this->insert($sql, $arrData);

            if ($insert) {
                $insertados++;
                if ($esTransito) {
                    $vinesTransito[] = ['id' => (int) $insert, 'vin' => $vin];
                }
                $this->registrarGeneracion($historial, [
                    'id_numeros_serie' => $insert,
                    'numero_serie' => $vin,
                    'id_cat_modelo_vin' => $idModeloVin,
                    'modulo' => 'GENERADOR',
                    'accion' => 'GENERACION',
                    'campo' => 'numero_serie',
                    'valor_nuevo' => $vin,
                    'detalle' => $detalleGen,
                    'origen' => 'INDIVIDUAL',
                    'usuarioid' => $usuarioid,
                ]);
            }
        }

            if ($esTransito && !empty($vinesTransito)) {
                $this->registrarEntradaTransito((int) $inventarioid, (int) $almacenid, (string) $referencia,
                    (float) $costo, $vinesTransito, $idModeloVin, $usuarioid, $historial);
            }
            if ($esTransito) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Inv_seriesModel::insertarSeriesConfirmadas: ' . $e->getMessage());
            return [
                "status" => false,
                "msg" => "No se pudo generar el lote (no se guardó ningún VIN). Inténtalo de nuevo."
            ];
        }

        return [
            "status" => $insertados > 0,
            "msg" => $insertados > 0
                ? "$insertados VIN insertados correctamente"
                    . ($esTransito ? " y dados de entrada en el almacén de tránsito (concepto Ajustes)" : "")
                : "No se insertó ningún VIN"
        ];
    }

    /** Almacén de tránsito: clave ALM-TRANSITO (descripción "ALMACEN DE TRANSITO"). */
    private function esAlmacenTransito(int $almacenid): bool
    {
        $r = $this->select(
            "SELECT idalmacen FROM wms_almacenes
             WHERE idalmacen = ?
               AND (UPPER(TRIM(cve_almacen)) = 'ALM-TRANSITO'
                    OR UPPER(TRIM(descripcion)) IN ('ALMACEN DE TRANSITO', 'ALMACÉN DE TRANSITO', 'ALMACÉN DE TRÁNSITO'))",
            [$almacenid]
        );
        return !empty($r);
    }

    /**
     * Entrada al inventario de un lote de VINs en tránsito: un solo movimiento
     * (concepto 6 Ajustes) con cantidad = # de VINs, existencia +N en
     * wms_multialmacen, y cada VIN ligado en wms_movimientos_inventario_series.
     * Corre dentro de la transacción de insertarSeriesConfirmadas().
     */
    private function registrarEntradaTransito(int $inventarioid, int $almacenid, string $referencia, float $costo,
        array $vines, $idModeloVin, $usuarioid, Inv_vin_historialModel $historial): void
    {
        $cantidad = count($vines);
        $concepto = $this->conceptoEntradaAjuste();
        $numero = 'MOV-' . date('YmdHis') . '-' . rand(100, 999);

        $this->insert(
            "INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE existencia = COALESCE(existencia, 0) + VALUES(existencia)",
            [$inventarioid, $almacenid, $cantidad]
        );
        $exis = $this->select(
            "SELECT existencia FROM wms_multialmacen WHERE inventarioid = ? AND almacenid = ?",
            [$inventarioid, $almacenid]
        );

        $idMov = (int) $this->insert(
            "INSERT INTO wms_movimientos_inventario
             (inventarioid, almacenid, numero_movimiento, concepmovid, referencia, lote,
              cantidad, costo_cantidad, existencia, signo, fecha_movimiento, estado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW(), 2)",
            [$inventarioid, $almacenid, $numero, $concepto, $referencia, $referencia,
             $cantidad, $costo, (float) ($exis['existencia'] ?? $cantidad)]
        );

        foreach ($vines as $v) {
            $this->insert(
                "INSERT INTO wms_movimientos_inventario_series
                 (idmovinventario, numero_movimiento, id_numeros_serie, numero_serie,
                  almacenid, signo, estado_anterior, usuarioid, fecha)
                 VALUES (?, ?, ?, ?, ?, 1, '1', ?, NOW())",
                [$idMov, $numero, $v['id'], $v['vin'], $almacenid, !empty($usuarioid) ? (int) $usuarioid : null]
            );
            $this->registrarGeneracion($historial, [
                'id_numeros_serie' => $v['id'],
                'numero_serie' => $v['vin'],
                'id_cat_modelo_vin' => $idModeloVin,
                'modulo' => 'INVENTARIO',
                'accion' => 'ENTRADA',
                'campo' => 'almacenid',
                'valor_nuevo' => 'ALM-TRANSITO',
                'detalle' => "Entrada al almacén de tránsito por lote {$referencia} ({$numero}, concepto Ajustes)",
                'origen' => 'INDIVIDUAL',
                'usuarioid' => $usuarioid,
            ]);
        }
    }


    public function getSerieByVin($vin)
    {
        $sql = "SELECT s.numero_serie,
                   s.referencia,
                   i.descripcion AS producto
            FROM wms_numeros_series s
            INNER JOIN wms_inventario i 
                ON s.inventarioid = i.idinventario
            WHERE s.numero_serie = ?";

        return $this->select($sql, array($vin));
    }

    public function validarOrdenTrabajoPorOrden($num_orden)
    {
        $sql = "SELECT idplaneacion 
            FROM mrp_planeacion 
            WHERE num_orden = ?
            LIMIT 1";

        return $this->select($sql, array($num_orden));
    }

    public function getSeriesByOrden($orden)
    {
        $sql = "SELECT s.numero_serie,
                   s.referencia,
                   i.descripcion AS producto
            FROM wms_numeros_series s
            INNER JOIN wms_inventario i 
                ON s.inventarioid = i.idinventario
            WHERE s.referencia = ?
            ORDER BY s.numero_serie ASC";

        return $this->select_all($sql, array($orden));
    }

    /**
     * Último consecutivo (posiciones 12-17) del AÑO del VIN (posición 10),
     * sin importar el modelo, la planta ni el resto de la estructura.
     * Solo cuenta VIN del mismo fabricante (WMI, posiciones 1-3, ej. 3LD):
     * los VIN de unidades importadas (LVA..., LVC...) traen su propio
     * consecutivo y no deben mover el nuestro.
     * Ej. si existe 3LD12B2J2VA000006, el siguiente VIN con año "V"
     * de cualquier modelo toma el 000007.
     *
     * $vin puede ser la base del modelo (3LD12B2J-VA) o un VIN completo:
     * en ambos el año está en la posición 10 (índice 9).
     */
    public function getUltimoConsecutivo($vin)
    {
        $vin = strtoupper(trim($vin));
        $anio = substr($vin, 9, 1);
        $wmi = substr($vin, 0, 3);

        if ($anio === false || $anio === '' || strlen($wmi) !== 3) {
            return 0;
        }

        $sql = "SELECT MAX(CAST(SUBSTRING(numero_serie, 12, 6) AS UNSIGNED)) AS ultimo
                FROM wms_numeros_series
                WHERE LENGTH(numero_serie) = 17
                  AND LEFT(numero_serie, 3) = ?
                  AND SUBSTRING(numero_serie, 10, 1) = ?
                  AND SUBSTRING(numero_serie, 12, 6) REGEXP '^[0-9]{6}$'";

        $result = $this->select($sql, [$wmi, $anio]);
        $ultimo = !empty($result['ultimo']) ? intval($result['ultimo']) : 0;

        // VIN reales del Excel INVENTARIO_LDR_2027 que aun no se pueden dar de
        // alta (modelo sin SKU en Ingenieria): su consecutivo ya esta troquelado
        // en una unidad, asi que tambien cuenta para no volver a usarlo.
        // Ver Docs/desarrollo/2026-10-16_carga_vin_inventario_ldr_2027.sql
        try {
            $sqlCarga = "SELECT MAX(CAST(SUBSTRING(vin, 12, 6) AS UNSIGNED)) AS ultimo
                         FROM vin_carga_inv2027
                         WHERE LENGTH(vin) = 17
                           AND LEFT(vin, 3) = ?
                           AND SUBSTRING(vin, 10, 1) = ?
                           AND SUBSTRING(vin, 12, 6) REGEXP '^[0-9]{6}$'";
            $carga = $this->select($sqlCarga, [$wmi, $anio]);
            if (!empty($carga['ultimo'])) {
                $ultimo = max($ultimo, intval($carga['ultimo']));
            }
        } catch (\Throwable $e) {
            // La tabla de carga no existe en este ambiente: se ignora.
        }

        return $ultimo;
    }

    /* =================================================================
     * ASOCIACIÓN: VIN generado <-> número de motor <-> VIN de origen
     * (wms_vin_asociaciones, 1 a 1; cada cambio queda en wms_vin_historial)
     * ================================================================= */

    // El historial no debe cortar la generación a la mitad del lote
    private function registrarGeneracion(Inv_vin_historialModel $historial, array $d): void
    {
        try {
            $historial->registrar($d);
        } catch (\Throwable $e) {
            error_log('Inv_seriesModel::registrarGeneracion: ' . $e->getMessage());
        }
    }

    public static function normalizarMotor($motor): string
    {
        $motor = strtoupper(trim((string) $motor));
        return preg_replace('/\s+/', ' ', $motor);
    }

    public static function normalizarVinOrigen($vin): string
    {
        return strtoupper(preg_replace('/\s+/', '', (string) $vin));
    }

    /** Color de la unidad: "BLANCO  perla" -> "Blanco Perla" (vacío = sin color). */
    public static function normalizarColor($color): string
    {
        $color = preg_replace('/\s+/', ' ', trim((string) $color));
        return $color === '' ? '' : mb_convert_case(mb_strtolower($color, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
    }

    public static function validarColor(string $color): ?string
    {
        if ($color === '') {
            return null;
        }
        if (mb_strlen($color) > 30) {
            return 'El color no puede exceder 30 caracteres.';
        }
        if (!preg_match('/^[\p{L}0-9][\p{L}0-9 \-\/\.]*$/u', $color)) {
            return 'El color solo admite letras, números, espacio, guion, diagonal y punto.';
        }
        return null;
    }

    /** Colores ya capturados (para sugerirlos en el formulario y no tener "Blanco" y "Blanca"). */
    public function selectColores(): array
    {
        return array_column($this->select_all(
            "SELECT color, COUNT(*) AS n FROM wms_vin_asociaciones
             WHERE color IS NOT NULL AND color <> '' GROUP BY color ORDER BY n DESC, color"
        ), 'color');
    }

    /** Regresa el motivo del error o null si los datos son válidos (ya normalizados). */
    public static function validarDatosAsociacion(string $motor, string $vinOrigen): ?string
    {
        if ($motor === '') {
            return 'El número de motor es obligatorio.';
        }
        if (mb_strlen($motor) > 30) {
            return 'El número de motor no puede exceder 30 caracteres.';
        }
        if (!preg_match('/^[A-Z0-9][A-Z0-9 \-\/\.]*$/', $motor)) {
            return 'El número de motor solo admite letras, números, espacio, guion, diagonal y punto.';
        }
        if ($vinOrigen === '') {
            return 'El VIN de origen es obligatorio.';
        }
        if (strlen($vinOrigen) !== 17) {
            return 'El VIN de origen debe tener 17 caracteres (tiene ' . strlen($vinOrigen) . ').';
        }
        if (!preg_match('/^[A-Z0-9]{17}$/', $vinOrigen)) {
            return 'El VIN de origen solo puede contener letras y números.';
        }
        if (preg_match('/[IOQ]/', $vinOrigen)) {
            return 'El VIN de origen no puede contener las letras I, O o Q.';
        }
        return null;
    }

    /** VIN generado + su asociación actual (si tiene). */
    public function selectSerieAsociacion(string $vin)
    {
        $sql = "SELECT s.id_numeros_serie, s.numero_serie, s.referencia, s.tipo_generacion,
                       s.id_cat_modelo_vin, s.estado,
                       i.descripcion AS producto,
                       va.id_asociacion, va.numero_motor, va.vin_origen, va.color,
                       va.fecha_alta, va.fecha_modifica
                FROM wms_numeros_series s
                INNER JOIN wms_inventario i ON i.idinventario = s.inventarioid
                LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = s.id_numeros_serie
                WHERE s.numero_serie = ? AND s.estado != 0
                LIMIT 1";

        return $this->select($sql, [strtoupper(trim($vin))]);
    }

    /** id_numeros_serie del VIN generado → datos básicos. */
    public function selectSerieById(int $idSerie)
    {
        return $this->select(
            "SELECT id_numeros_serie, numero_serie, id_cat_modelo_vin, tipo_generacion
             FROM wms_numeros_series WHERE id_numeros_serie = ? AND estado != 0",
            [$idSerie]
        );
    }

    /**
     * Mapa para la carga masiva: numero_serie => fila con asociación actual.
     * Una sola consulta con IN() para todos los VIN del archivo.
     */
    public function mapSeriesAsociacion(array $vins): array
    {
        $vins = array_values(array_unique(array_filter($vins)));
        if (empty($vins)) {
            return [];
        }
        $mapa = [];
        foreach (array_chunk($vins, 500) as $bloque) {
            $in = implode(',', array_fill(0, count($bloque), '?'));
            $sql = "SELECT s.id_numeros_serie, s.numero_serie, s.id_cat_modelo_vin,
                           va.numero_motor, va.vin_origen, va.color
                    FROM wms_numeros_series s
                    LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = s.id_numeros_serie
                    WHERE s.estado != 0 AND s.numero_serie IN ($in)";
            foreach ($this->select_all($sql, $bloque) as $r) {
                $mapa[$r['numero_serie']] = $r;
            }
        }
        return $mapa;
    }

    /**
     * Alta o modificación de la asociación de un VIN.
     * $origenCaptura: INDIVIDUAL | MASIVO
     * $color: null o '' = no se toca el color que ya tenga (la carga masiva puede venir sin color)
     * Respuesta: ['status' => bool, 'msg' => string, 'tipo' => 'nueva'|'actualizada'|'sin_cambios']
     */
    public function guardarAsociacion(int $idSerie, string $motor, string $vinOrigen, $usuarioid, string $origenCaptura = 'INDIVIDUAL', ?string $color = null): array
    {
        $motor = self::normalizarMotor($motor);
        $vinOrigen = self::normalizarVinOrigen($vinOrigen);
        $color = self::normalizarColor($color);

        $error = self::validarDatosAsociacion($motor, $vinOrigen) ?? self::validarColor($color);
        if ($error) {
            return ['status' => false, 'msg' => $error];
        }

        $serie = $this->selectSerieById($idSerie);
        if (empty($serie)) {
            return ['status' => false, 'msg' => 'El VIN generado no existe o está dado de baja.'];
        }

        // Unidad importada: no tiene VIN nacional, su VIN ES el VIN de origen
        if (($serie['tipo_generacion'] ?? '') === 'importado' && $vinOrigen !== $serie['numero_serie']) {
            return ['status' => false, 'msg' => "Unidad importada: el VIN de origen debe ser el mismo VIN ({$serie['numero_serie']})."];
        }

        // Motor / VIN de origen ya usados por OTRO VIN generado
        $usoMotor = $this->select(
            "SELECT s.numero_serie FROM wms_vin_asociaciones va
             INNER JOIN wms_numeros_series s ON s.id_numeros_serie = va.id_numeros_serie
             WHERE va.numero_motor = ? AND va.id_numeros_serie <> ? LIMIT 1",
            [$motor, $idSerie]
        );
        if (!empty($usoMotor)) {
            return ['status' => false, 'msg' => "El número de motor $motor ya está asociado al VIN {$usoMotor['numero_serie']}."];
        }

        $usoOrigen = $this->select(
            "SELECT s.numero_serie FROM wms_vin_asociaciones va
             INNER JOIN wms_numeros_series s ON s.id_numeros_serie = va.id_numeros_serie
             WHERE va.vin_origen = ? AND va.id_numeros_serie <> ? LIMIT 1",
            [$vinOrigen, $idSerie]
        );
        if (!empty($usoOrigen)) {
            return ['status' => false, 'msg' => "El VIN de origen $vinOrigen ya está asociado al VIN {$usoOrigen['numero_serie']}."];
        }

        $actual = $this->select(
            "SELECT id_asociacion, numero_motor, vin_origen, color FROM wms_vin_asociaciones WHERE id_numeros_serie = ?",
            [$idSerie]
        );

        $cambios = [];
        if (empty($actual)) {
            $cambios = [
                ['campo' => 'numero_motor', 'antes' => null, 'despues' => $motor],
                ['campo' => 'vin_origen', 'antes' => null, 'despues' => $vinOrigen],
            ];
            if ($color !== '') {
                $cambios[] = ['campo' => 'color', 'antes' => null, 'despues' => $color];
            }
        } else {
            if ($actual['numero_motor'] !== $motor) {
                $cambios[] = ['campo' => 'numero_motor', 'antes' => $actual['numero_motor'], 'despues' => $motor];
            }
            if ($actual['vin_origen'] !== $vinOrigen) {
                $cambios[] = ['campo' => 'vin_origen', 'antes' => $actual['vin_origen'], 'despues' => $vinOrigen];
            }
            if ($color !== '' && (string) $actual['color'] !== $color) {
                $cambios[] = ['campo' => 'color', 'antes' => $actual['color'], 'despues' => $color];
            }
            if ($color === '') {
                $color = $actual['color']; // se conserva
            }
            if (empty($cambios)) {
                return ['status' => true, 'msg' => 'Sin cambios: el VIN ya tenía esa asociación.', 'tipo' => 'sin_cambios'];
            }
        }

        $usuarioid = !empty($usuarioid) ? (int) $usuarioid : null;
        $accion = empty($actual) ? 'ALTA' : 'MODIFICACION';
        $historial = new Inv_vin_historialModel();

        // Misma conexión PDO (singleton) para la asociación y su historial
        $pdo = $this->conexion;
        $propiaTransaccion = !$pdo->inTransaction();
        try {
            if ($propiaTransaccion) {
                $pdo->beginTransaction();
            }

            if (empty($actual)) {
                $this->insert(
                    "INSERT INTO wms_vin_asociaciones
                     (id_numeros_serie, numero_motor, vin_origen, color, usuario_alta, fecha_alta)
                     VALUES (?,?,?,?,?,NOW())",
                    [$idSerie, $motor, $vinOrigen, $color !== '' ? $color : null, $usuarioid]
                );
            } else {
                $this->update(
                    "UPDATE wms_vin_asociaciones
                     SET numero_motor = ?, vin_origen = ?, color = ?, usuario_modifica = ?, fecha_modifica = NOW()
                     WHERE id_asociacion = ?",
                    [$motor, $vinOrigen, ($color ?? '') !== '' ? $color : null, $usuarioid, $actual['id_asociacion']]
                );
            }

            foreach ($cambios as $c) {
                $historial->registrar([
                    'id_numeros_serie' => $idSerie,
                    'numero_serie' => $serie['numero_serie'],
                    'id_cat_modelo_vin' => $serie['id_cat_modelo_vin'],
                    'modulo' => 'ASOCIACION',
                    'accion' => $accion,
                    'campo' => $c['campo'],
                    'valor_anterior' => $c['antes'],
                    'valor_nuevo' => $c['despues'],
                    'origen' => $origenCaptura,
                    'usuarioid' => $usuarioid,
                ]);
            }

            if ($propiaTransaccion) {
                $pdo->commit();
            }
        } catch (PDOException $e) {
            if ($propiaTransaccion && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // 23000: llave única (otro usuario asoció el mismo motor / VIN de origen al mismo tiempo)
            if ($e->getCode() === '23000') {
                return ['status' => false, 'msg' => 'El número de motor o el VIN de origen ya está asociado a otro VIN.'];
            }
            error_log('Inv_seriesModel::guardarAsociacion: ' . $e->getMessage());
            return ['status' => false, 'msg' => 'No se pudo guardar la asociación.'];
        }

        return [
            'status' => true,
            'msg' => empty($actual) ? 'Asociación registrada.' : 'Asociación actualizada.',
            'tipo' => empty($actual) ? 'nueva' : 'actualizada',
        ];
    }

    /* =================================================================
     * UNIDADES IMPORTADAS (sin VIN nacional)
     * El VIN de la unidad ES el VIN de origen del fabricante. Se guardan en
     * las mismas tablas que el resto (wms_numeros_series con
     * tipo_generacion = 'importado', sin VIN base) para que existencias,
     * traslados, llaves y ventas las vean igual que a cualquier unidad.
     * ================================================================= */

    /** Productos terminados activos: cve_articulo => [idinventario, descripcion]. */
    public function mapProductosUnidad(): array
    {
        $mapa = [];
        foreach ($this->select_all(
            "SELECT idinventario, cve_articulo, descripcion FROM wms_inventario
             WHERE tipo_elemento = 'P' AND estado != 0 ORDER BY descripcion"
        ) as $r) {
            $mapa[strtoupper(trim($r['cve_articulo']))] = $r;
        }
        return $mapa;
    }

    /** Almacenes activos: cve_almacen => [idalmacen, descripcion]. */
    public function mapAlmacenesActivos(): array
    {
        $mapa = [];
        foreach ($this->select_all(
            "SELECT idalmacen, cve_almacen, descripcion FROM wms_almacenes
             WHERE estado = 2 ORDER BY cve_almacen"
        ) as $r) {
            $mapa[strtoupper(trim($r['cve_almacen']))] = $r;
        }
        return $mapa;
    }

    /**
     * VIN / motores que ya existen, para validar el archivo completo con 3 consultas.
     * Regresa ['vin' => [VIN => true], 'motor' => [MOTOR => VIN], 'origen' => [ORIGEN => VIN]].
     */
    public function mapExistentesImportados(array $vins, array $motores): array
    {
        $out = ['vin' => [], 'motor' => [], 'origen' => []];
        foreach (array_chunk(array_values(array_unique(array_filter($vins))), 500) as $b) {
            $in = implode(',', array_fill(0, count($b), '?'));
            foreach ($this->select_all("SELECT numero_serie FROM wms_numeros_series WHERE numero_serie IN ($in)", $b) as $r) {
                $out['vin'][$r['numero_serie']] = true;
            }
            foreach ($this->select_all(
                "SELECT va.vin_origen, s.numero_serie FROM wms_vin_asociaciones va
                 INNER JOIN wms_numeros_series s ON s.id_numeros_serie = va.id_numeros_serie
                 WHERE va.vin_origen IN ($in)", $b) as $r) {
                $out['origen'][$r['vin_origen']] = $r['numero_serie'];
            }
        }
        foreach (array_chunk(array_values(array_unique(array_filter($motores))), 500) as $b) {
            $in = implode(',', array_fill(0, count($b), '?'));
            foreach ($this->select_all(
                "SELECT va.numero_motor, s.numero_serie FROM wms_vin_asociaciones va
                 INNER JOIN wms_numeros_series s ON s.id_numeros_serie = va.id_numeros_serie
                 WHERE va.numero_motor IN ($in)", $b) as $r) {
                $out['motor'][$r['numero_motor']] = $r['numero_serie'];
            }
        }
        return $out;
    }

    /** idconcepmov del concepto 6 "Ajustes" (entrada), el mismo que usaron las cargas de inventario. */
    private function conceptoEntradaAjuste(): int
    {
        $r = $this->select("SELECT idconcepmov FROM wms_conceptos_mov WHERE cve_concep_mov = '6' LIMIT 1");
        return !empty($r) ? (int) $r['idconcepmov'] : 6;
    }

    /**
     * Alta de UNA unidad importada, todo o nada:
     * VIN (= VIN de origen) + asociación (motor, origen, color) + entrada al
     * inventario (concepto 6 Ajustes) + existencia + historial.
     * Los datos ya vienen normalizados y validados por el controlador.
     */
    public function altaUnidadImportada(array $u, string $referencia, $usuarioid, string $origenCaptura = 'MASIVO'): array
    {
        $usuarioid = !empty($usuarioid) ? (int) $usuarioid : null;
        $vin = $u['vin'];
        $color = $u['color'] !== '' ? $u['color'] : null;
        $concepto = $this->conceptoEntradaAjuste();
        $historial = new Inv_vin_historialModel();

        $pdo = $this->conexion;
        try {
            $pdo->beginTransaction();

            $idSerie = (int) $this->insert(
                "INSERT INTO wms_numeros_series
                 (inventarioid, id_cat_modelo_vin, almacenid, numero_serie, referencia, costo, fecha, estado, tipo_generacion)
                 VALUES (?, NULL, ?, ?, ?, 0, NOW(), '2', 'importado')",
                [$u['inventarioid'], $u['almacenid'], $vin, $referencia]
            );

            $this->insert(
                "INSERT INTO wms_vin_asociaciones
                 (id_numeros_serie, numero_motor, vin_origen, color, usuario_alta, fecha_alta)
                 VALUES (?,?,?,?,?,NOW())",
                [$idSerie, $u['motor'], $vin, $color, $usuarioid]
            );

            // existencia: fila del producto en el almacén (se crea si no existe)
            $this->insert(
                "INSERT INTO wms_multialmacen (inventarioid, almacenid, existencia)
                 VALUES (?, ?, 1)
                 ON DUPLICATE KEY UPDATE existencia = COALESCE(existencia, 0) + 1",
                [$u['inventarioid'], $u['almacenid']]
            );
            $exis = $this->select(
                "SELECT existencia FROM wms_multialmacen WHERE inventarioid = ? AND almacenid = ?",
                [$u['inventarioid'], $u['almacenid']]
            );

            $this->insert(
                "INSERT INTO wms_movimientos_inventario
                 (inventarioid, almacenid, numero_movimiento, concepmovid, id_proveedor, referencia, lote,
                  cantidad, costo_cantidad, precio, costo, existencia, signo, fecha_movimiento, estado)
                 VALUES (?, ?, ?, ?, NULL, ?, NULL, 1, 0, 0, 0, ?, 1, NOW(), 2)",
                [$u['inventarioid'], $u['almacenid'], $referencia, $concepto, $vin, (float) ($exis['existencia'] ?? 1)]
            );

            $base = [
                'id_numeros_serie' => $idSerie,
                'numero_serie' => $vin,
                'id_cat_modelo_vin' => null,
                'origen' => $origenCaptura,
                'usuarioid' => $usuarioid,
            ];
            $detalle = 'Alta de unidad importada (sin VIN nacional). ' . $referencia
                . (isset($u['_fila']) ? ', fila ' . $u['_fila'] : '');
            $historial->registrar($base + ['modulo' => 'GENERADOR', 'accion' => 'GENERACION', 'campo' => 'numero_serie',
                'valor_nuevo' => $vin, 'detalle' => $detalle]);
            $historial->registrar($base + ['modulo' => 'ASOCIACION', 'accion' => 'ALTA', 'campo' => 'numero_motor',
                'valor_nuevo' => $u['motor'], 'detalle' => $detalle]);
            $historial->registrar($base + ['modulo' => 'ASOCIACION', 'accion' => 'ALTA', 'campo' => 'vin_origen',
                'valor_nuevo' => $vin, 'detalle' => $detalle]);
            if ($color !== null) {
                $historial->registrar($base + ['modulo' => 'ASOCIACION', 'accion' => 'ALTA', 'campo' => 'color',
                    'valor_nuevo' => $color, 'detalle' => $detalle]);
            }
            $historial->registrar($base + ['modulo' => 'INVENTARIO', 'accion' => 'ENTRADA', 'campo' => 'almacenid',
                'valor_nuevo' => $u['cve_almacen'], 'detalle' => $detalle]);

            $pdo->commit();
            return ['status' => true, 'id_numeros_serie' => $idSerie];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e->getCode() === '23000') {
                return ['status' => false, 'msg' => 'El VIN, el número de motor o el VIN de origen ya existe en el sistema.'];
            }
            error_log('Inv_seriesModel::altaUnidadImportada: ' . $e->getMessage());
            return ['status' => false, 'msg' => 'No se pudo registrar la unidad.'];
        }
    }

    /** Listado de unidades importadas (para exportar a Excel). */
    public function selectImportados(): array
    {
        return $this->select_all(
            "SELECT s.numero_serie, va.numero_motor, va.color,
                    i.cve_articulo, i.descripcion AS producto,
                    a.cve_almacen, a.descripcion AS almacen,
                    s.referencia, s.estado, DATE_FORMAT(s.fecha, '%Y-%m-%d') AS fecha
             FROM wms_numeros_series s
             INNER JOIN wms_inventario i ON i.idinventario = s.inventarioid
             LEFT JOIN wms_almacenes a ON a.idalmacen = s.almacenid
             LEFT JOIN wms_vin_asociaciones va ON va.id_numeros_serie = s.id_numeros_serie
             WHERE s.tipo_generacion = 'importado' AND s.estado != 0
             ORDER BY a.cve_almacen, i.descripcion, s.numero_serie"
        );
    }
}
