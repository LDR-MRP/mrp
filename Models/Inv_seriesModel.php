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

        return [
            "status" => $insertados > 0,
            "msg" => $insertados > 0
                ? "$insertados VIN insertados correctamente"
                : "No se insertó ningún VIN"
        ];
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

        if ($anio === false || $anio === '') {
            return 0;
        }

        $sql = "SELECT MAX(CAST(SUBSTRING(numero_serie, 12, 6) AS UNSIGNED)) AS ultimo
                FROM wms_numeros_series
                WHERE LENGTH(numero_serie) = 17
                  AND SUBSTRING(numero_serie, 10, 1) = ?
                  AND SUBSTRING(numero_serie, 12, 6) REGEXP '^[0-9]{6}$'";

        $result = $this->select($sql, [$anio]);
        $ultimo = !empty($result['ultimo']) ? intval($result['ultimo']) : 0;

        // VIN reales del Excel INVENTARIO_LDR_2027 que aun no se pueden dar de
        // alta (modelo sin SKU en Ingenieria): su consecutivo ya esta troquelado
        // en una unidad, asi que tambien cuenta para no volver a usarlo.
        // Ver Docs/desarrollo/2026-10-16_carga_vin_inventario_ldr_2027.sql
        try {
            $sqlCarga = "SELECT MAX(CAST(SUBSTRING(vin, 12, 6) AS UNSIGNED)) AS ultimo
                         FROM vin_carga_inv2027
                         WHERE LENGTH(vin) = 17
                           AND SUBSTRING(vin, 10, 1) = ?
                           AND SUBSTRING(vin, 12, 6) REGEXP '^[0-9]{6}$'";
            $carga = $this->select($sqlCarga, [$anio]);
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
                       va.id_asociacion, va.numero_motor, va.vin_origen,
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
            "SELECT id_numeros_serie, numero_serie, id_cat_modelo_vin
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
                           va.numero_motor, va.vin_origen
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
     * Respuesta: ['status' => bool, 'msg' => string, 'tipo' => 'nueva'|'actualizada'|'sin_cambios']
     */
    public function guardarAsociacion(int $idSerie, string $motor, string $vinOrigen, $usuarioid, string $origenCaptura = 'INDIVIDUAL'): array
    {
        $motor = self::normalizarMotor($motor);
        $vinOrigen = self::normalizarVinOrigen($vinOrigen);

        $error = self::validarDatosAsociacion($motor, $vinOrigen);
        if ($error) {
            return ['status' => false, 'msg' => $error];
        }

        $serie = $this->selectSerieById($idSerie);
        if (empty($serie)) {
            return ['status' => false, 'msg' => 'El VIN generado no existe o está dado de baja.'];
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
            "SELECT id_asociacion, numero_motor, vin_origen FROM wms_vin_asociaciones WHERE id_numeros_serie = ?",
            [$idSerie]
        );

        $cambios = [];
        if (empty($actual)) {
            $cambios = [
                ['campo' => 'numero_motor', 'antes' => null, 'despues' => $motor],
                ['campo' => 'vin_origen', 'antes' => null, 'despues' => $vinOrigen],
            ];
        } else {
            if ($actual['numero_motor'] !== $motor) {
                $cambios[] = ['campo' => 'numero_motor', 'antes' => $actual['numero_motor'], 'despues' => $motor];
            }
            if ($actual['vin_origen'] !== $vinOrigen) {
                $cambios[] = ['campo' => 'vin_origen', 'antes' => $actual['vin_origen'], 'despues' => $vinOrigen];
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
                     (id_numeros_serie, numero_motor, vin_origen, usuario_alta, fecha_alta)
                     VALUES (?,?,?,?,NOW())",
                    [$idSerie, $motor, $vinOrigen, $usuarioid]
                );
            } else {
                $this->update(
                    "UPDATE wms_vin_asociaciones
                     SET numero_motor = ?, vin_origen = ?, usuario_modifica = ?, fecha_modifica = NOW()
                     WHERE id_asociacion = ?",
                    [$motor, $vinOrigen, $usuarioid, $actual['id_asociacion']]
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
}
