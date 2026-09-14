<?php

class Ing_configuracionesModel extends Mysql
{
    public function __construct()
    {
        parent::__construct();
    }

    // ------------------------------------------------------------------
    // CONFIGURACIÓN (ing_modelo_configuracion)
    // ------------------------------------------------------------------
    public function selectConfiguraciones()
    {
        $sql = "SELECT
                    c.id_configuracion,
                    c.id_sublineaproducto,
                    c.tipo_origen,
                    c.nombre_unidad,
                    c.nombre_comercial,
                    c.codigo_modelo,
                    c.clave_vehicular,
                    c.peso_bruto,
                    c.nivel_emisiones,
                    c.combustible,
                    c.estado,
                    c.version,
                    l.descripcion AS segmento,
                    s.descripcion AS modelo,
                    m.fabricante AS motor_fabricante,
                    m.modelo_motor,
                    t.fabricante AS transmision_fabricante,
                    t.modelo AS transmision_modelo
                FROM ing_modelo_configuracion c
                INNER JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                INNER JOIN wms_linea_producto l ON l.idlineaproducto = s.lineaproductoid
                LEFT JOIN ing_cat_motor m ON m.id_motor = c.id_motor
                LEFT JOIN ing_cat_transmision t ON t.id_transmision = c.id_transmision
                WHERE c.deleted_at IS NULL
                ORDER BY l.descripcion, s.descripcion, c.nombre_unidad";

        return $this->select_all($sql);
    }

    public function selectConfiguracion(int $id)
    {
        $sql = "SELECT
                    c.*,
                    l.idlineaproducto,
                    l.descripcion AS segmento,
                    s.descripcion AS modelo
                FROM ing_modelo_configuracion c
                INNER JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                INNER JOIN wms_linea_producto l ON l.idlineaproducto = s.lineaproductoid
                WHERE c.id_configuracion = ? AND c.deleted_at IS NULL";

        return $this->select($sql, [$id]);
    }

    public function insertConfiguracion(array $data)
    {
        $sql = "SELECT id_configuracion FROM ing_modelo_configuracion
                WHERE id_sublineaproducto = ? AND tipo_origen = ? AND clave_vehicular = ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['id_sublineaproducto'], $data['tipo_origen'], $data['clave_vehicular']]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "INSERT INTO ing_modelo_configuracion
            (id_sublineaproducto, tipo_origen, nombre_unidad, nombre_comercial, codigo_modelo, clave_vehicular,
             peso_bruto, nivel_emisiones, id_motor, id_transmision, combustible, estado, version, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $data['id_sublineaproducto'], $data['tipo_origen'], $data['nombre_unidad'], $data['nombre_comercial'],
            $data['codigo_modelo'], $data['clave_vehicular'], $data['peso_bruto'], $data['nivel_emisiones'],
            $data['id_motor'], $data['id_transmision'], $data['combustible'], $data['estado'], $data['version'],
            $_SESSION['userData']['idusuario'] ?? null,
        ]);
    }

    public function updateConfiguracion(int $id, array $data)
    {
        $sql = "SELECT id_configuracion FROM ing_modelo_configuracion
                WHERE id_sublineaproducto = ? AND tipo_origen = ? AND clave_vehicular = ? AND id_configuracion != ? AND deleted_at IS NULL";
        $exist = $this->select($sql, [$data['id_sublineaproducto'], $data['tipo_origen'], $data['clave_vehicular'], $id]);
        if (!empty($exist)) {
            return "exist";
        }

        $sql = "UPDATE ing_modelo_configuracion SET
            id_sublineaproducto = ?, tipo_origen = ?, nombre_unidad = ?, nombre_comercial = ?, codigo_modelo = ?,
            clave_vehicular = ?, peso_bruto = ?, nivel_emisiones = ?, id_motor = ?, id_transmision = ?,
            combustible = ?, estado = ?, version = ?, updated_by = ?
            WHERE id_configuracion = ?";

        return $this->update($sql, [
            $data['id_sublineaproducto'], $data['tipo_origen'], $data['nombre_unidad'], $data['nombre_comercial'],
            $data['codigo_modelo'], $data['clave_vehicular'], $data['peso_bruto'], $data['nivel_emisiones'],
            $data['id_motor'], $data['id_transmision'], $data['combustible'], $data['estado'], $data['version'],
            $_SESSION['userData']['idusuario'] ?? null, $id,
        ]);
    }

    public function deleteConfiguracion(int $id)
    {
        $sql = "UPDATE ing_modelo_configuracion SET deleted_at = NOW() WHERE id_configuracion = ?";
        return $this->update($sql, [$id]);
    }

    // Configuración con TODOS los datos de motor y transmisión (no solo los ids),
    // usada para armar la ficha técnica de solo lectura.
    public function selectFichaTecnica(int $id)
    {
        $sql = "SELECT
                    c.*,
                    wi.cve_articulo AS inv_cve_articulo,
                    l.descripcion AS segmento,
                    s.descripcion AS modelo,
                    m.fabricante AS motor_fabricante,
                    m.modelo_motor,
                    m.tipo_motor,
                    m.cilindrada,
                    m.numero_cilindros,
                    m.potencia,
                    m.unidad_potencia,
                    m.torque,
                    m.unidad_torque,
                    m.tipo_combustible AS motor_tipo_combustible,
                    m.tipo_admision,
                    m.fabricante_bateria,
                    m.tipo_bateria,
                    m.capacidad_bateria,
                    m.consumo AS motor_consumo,
                    m.conector,
                    m.proteccion_ip,
                    m.sistema_electrico,
                    t.fabricante AS transmision_fabricante,
                    t.modelo AS transmision_modelo,
                    t.tipo AS transmision_tipo,
                    t.numero_velocidades,
                    t.descripcion AS transmision_descripcion
                FROM ing_modelo_configuracion c
                INNER JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                INNER JOIN wms_linea_producto l ON l.idlineaproducto = s.lineaproductoid
                LEFT JOIN ing_cat_motor m ON m.id_motor = c.id_motor
                LEFT JOIN ing_cat_transmision t ON t.id_transmision = c.id_transmision
                LEFT JOIN wms_inventario wi ON wi.idinventario = c.id_inventario
                WHERE c.id_configuracion = ? AND c.deleted_at IS NULL";

        return $this->select($sql, [$id]);
    }

    // ------------------------------------------------------------------
    // ALTA EN INVENTARIO (WMS) — botón en la ficha técnica cuando AUTORIZADO.
    // Crea un solo artículo (SKU) por configuración en wms_inventario
    // (tipo_elemento='P', serie='Y'); las unidades físicas se distinguen
    // después por su VIN, dado de alta en el módulo VIN (Inv_series) como
    // cualquier otro producto serializado. Aquí NO se toca
    // wms_numeros_series ni se captura VIN.
    // ------------------------------------------------------------------
    public function selectConfiguracionParaAltaInventario(int $id)
    {
        $sql = "SELECT
                    c.id_configuracion,
                    c.id_inventario,
                    c.estado,
                    c.nombre_unidad,
                    c.tipo_origen,
                    c.combustible,
                    l.descripcion AS segmento,
                    s.descripcion AS modelo
                FROM ing_modelo_configuracion c
                INNER JOIN wms_sublinea_producto s ON s.idsublineaproducto = c.id_sublineaproducto
                INNER JOIN wms_linea_producto l ON l.idlineaproducto = s.lineaproductoid
                WHERE c.id_configuracion = ? AND c.deleted_at IS NULL";

        return $this->select($sql, [$id]);
    }

    // ------------------------------------------------------------------
    // GENERADOR DE SKU — a partir de las especificaciones ya capturadas de
    // la configuracion. Reglas confirmadas con el usuario (09-sep-2026,
    // ajustadas el mismo dia: se quitaron Carroceria/Version y el formato
    // paso de "todo separado por guion" a "solo la marca/segmento separada":
    //   - Segmento:        primera + letra intermedia + ultima letra   (FOTON      -> FTN)
    //   - Nombre unidad:   primera + ultima letra                     (Wonder     -> WR)
    //   - Modelo/sublinea: inicial de cada palabra                    (MINI TRUCK -> MT)
    //   - Origen:          N (Nacional) / I (Importado)
    //   - Combustible:     codigo de 3 letras via catalogo interno (GSL/DSL/ELE/HIB/GLP/GNC...),
    //                      si no se reconoce toma las primeras 3 letras
    // Formato final: "<SEGMENTO>-<restodelSKUjunto>" — solo el segmento
    // (la marca) va separado por guion, todo lo demas se concatena sin
    // separadores. Los segmentos vacios (dato no capturado todavia) se
    // omiten. Si el codigo base ya existe en inventario se agrega un
    // consecutivo numerico (-02, -03...) hasta encontrar uno libre: el
    // generador nunca rechaza por choque ni reutiliza un SKU ajeno.
    // ------------------------------------------------------------------
    private function normalizarTexto(string $texto)
    {
        $texto = trim($texto);
        if ($texto === '') {
            return '';
        }
        $texto = mb_strtoupper($texto, 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U',
            'Ñ' => 'N', 'Ü' => 'U',
        ]);
        return trim(preg_replace('/[^A-Z0-9 ]+/', '', $texto));
    }

    private function extraerPrimeraIntermediaUltima(string $texto)
    {
        $limpio = str_replace(' ', '', $this->normalizarTexto($texto));
        $len = mb_strlen($limpio);
        if ($len === 0) {
            return '';
        }
        if ($len < 3) {
            return $limpio;
        }
        $primera = mb_substr($limpio, 0, 1);
        $ultima = mb_substr($limpio, -1, 1);
        $intermedia = mb_substr($limpio, intdiv($len, 2), 1);
        return $primera . $intermedia . $ultima;
    }

    private function extraerPrimeraUltima(string $texto)
    {
        $limpio = str_replace(' ', '', $this->normalizarTexto($texto));
        $len = mb_strlen($limpio);
        if ($len === 0) {
            return '';
        }
        if ($len === 1) {
            return $limpio;
        }
        return mb_substr($limpio, 0, 1) . mb_substr($limpio, -1, 1);
    }

    private function extraerIniciales(string $texto)
    {
        $limpio = $this->normalizarTexto($texto);
        if ($limpio === '') {
            return '';
        }
        $palabras = preg_split('/\s+/', trim($limpio));
        if (count($palabras) > 1) {
            $iniciales = '';
            foreach ($palabras as $palabra) {
                if ($palabra !== '') {
                    $iniciales .= mb_substr($palabra, 0, 1);
                }
            }
            return $iniciales;
        }
        return mb_substr($palabras[0], 0, 2);
    }

    private function codigoCombustible(string $texto)
    {
        $limpio = $this->normalizarTexto($texto);
        if ($limpio === '') {
            return '';
        }
        $mapa = [
            'GASOLINA'    => 'GSL',
            'DIESEL'      => 'DSL',
            'ELECTRICO'   => 'ELE',
            'HIBRIDO'     => 'HIB',
            'GAS LP'      => 'GLP',
            'GLP'         => 'GLP',
            'GAS NATURAL' => 'GNC',
            'GNC'         => 'GNC',
        ];
        if (isset($mapa[$limpio])) {
            return $mapa[$limpio];
        }
        return mb_substr(str_replace(' ', '', $limpio), 0, 3);
    }

    public function generarSku(array $ficha)
    {
        $segmento = $this->extraerPrimeraIntermediaUltima($ficha['segmento'] ?? '');

        $resto = [
            $this->extraerPrimeraUltima($ficha['nombre_unidad'] ?? ''),
            $this->extraerIniciales($ficha['modelo'] ?? ''),
            ($ficha['tipo_origen'] ?? '') === 'IMPORTADO' ? 'I' : 'N',
            $this->codigoCombustible($ficha['combustible'] ?? ''),
        ];
        $resto = implode('', array_filter($resto, function ($p) {
            return $p !== '';
        }));

        $partes = array_filter([$segmento, $resto], function ($p) {
            return $p !== '';
        });

        return implode('-', $partes);
    }

    public function generarSkuUnico(array $ficha)
    {
        $base = $this->generarSku($ficha);
        if ($base === '') {
            $base = 'SKU';
        }
        $sku = $base;
        $intento = 1;
        while ($this->existeSku($sku)) {
            $intento++;
            $sku = $base . '-' . str_pad((string) $intento, 2, '0', STR_PAD_LEFT);
        }
        return $sku;
    }

    // El SKU (cve_articulo) lo captura el usuario a mano en el modal del
    // botón "Dar de alta en inventario" (no se deriva automáticamente de
    // clave_vehicular) — validar que no exista ya se hace ANTES de llamar
    // aquí, vía existeSku().
    public function crearInventarioParaConfiguracion(array $config, string $cveArticulo)
    {
        $sql = "INSERT INTO wms_inventario
            (cve_articulo, descripcion, notas, unidad_entrada, unidad_salida, unidad_empaque,
             ultimo_costo, ubicacion, idmarca, tipo_elemento, factor_unidades, tiempo_surtido,
             peso, volumen, serie, lote, pedimiento, fecha_creacion, estado)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),2)";

        return $this->insert($sql, [
            $cveArticulo,
            $config['nombre_unidad'] . ' (' . $config['tipo_origen'] . ')',
            'Generado automáticamente por el módulo de Ingeniería para la configuración #' . $config['id_configuracion'],
            'PZA', 'PZA', 'PZA',
            0, '', null, 'P', 1, 0, 0, 0,
            'Y', '', '',
        ]);
    }

    public function existeSku(string $cveArticulo)
    {
        $sql = "SELECT idinventario FROM wms_inventario WHERE cve_articulo = ?";
        return !empty($this->select($sql, [$cveArticulo]));
    }

    public function vincularInventarioConfiguracion(int $idConfiguracion, int $idInventario)
    {
        $sql = "UPDATE ing_modelo_configuracion SET id_inventario = ? WHERE id_configuracion = ?";
        return $this->update($sql, [$idInventario, $idConfiguracion]);
    }

    public function selectSkuInventario(int $idInventario)
    {
        $sql = "SELECT cve_articulo FROM wms_inventario WHERE idinventario = ?";
        return $this->select($sql, [$idInventario]);
    }

    // ------------------------------------------------------------------
    // ESPECIFICACIONES (ing_cat_especificacion + ing_modelo_especificacion_valor)
    // ------------------------------------------------------------------
    public function selectEspecificacionesConfiguracion(int $idConfiguracion)
    {
        $sql = "SELECT
                    e.id_especificacion,
                    e.categoria,
                    e.clave,
                    e.unidad,
                    e.orden,
                    v.id_valor,
                    v.valor
                FROM ing_cat_especificacion e
                LEFT JOIN ing_modelo_especificacion_valor v
                    ON v.id_especificacion = e.id_especificacion AND v.id_configuracion = ?
                WHERE e.activo = 1 AND e.deleted_at IS NULL
                ORDER BY e.categoria, e.orden, e.clave";

        return $this->select_all($sql, [$idConfiguracion]);
    }

    public function upsertEspecificacionValor(int $idConfiguracion, int $idEspecificacion, string $valor)
    {
        $idusuario = $_SESSION['userData']['idusuario'] ?? null;

        if ($valor === '') {
            $sql = "DELETE FROM ing_modelo_especificacion_valor WHERE id_configuracion = ? AND id_especificacion = ?";
            return $this->update($sql, [$idConfiguracion, $idEspecificacion]);
        }

        $sql = "INSERT INTO ing_modelo_especificacion_valor (id_configuracion, id_especificacion, valor, updated_by)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE valor = VALUES(valor), updated_by = VALUES(updated_by)";

        return $this->update($sql, [$idConfiguracion, $idEspecificacion, $valor, $idusuario]);
    }

    // ------------------------------------------------------------------
    // CERTIFICACIONES (ing_cat_certificacion + ing_modelo_certificacion)
    // ------------------------------------------------------------------
    public function selectCertificacionesConfiguracion(int $idConfiguracion)
    {
        $sql = "SELECT
                    cert.id_certificacion,
                    cert.codigo,
                    cert.nombre,
                    cert.autoridad,
                    cert.requiere_documento,
                    cert.requiere_vigencia,
                    mc.id_modelo_certificacion,
                    mc.obligatoria,
                    mc.estado,
                    mc.numero_certificado,
                    mc.fecha_emision,
                    mc.fecha_inicio,
                    mc.fecha_vencimiento,
                    mc.observaciones
                FROM ing_cat_certificacion cert
                LEFT JOIN ing_modelo_certificacion mc
                    ON mc.id_certificacion = cert.id_certificacion AND mc.id_configuracion = ?
                WHERE cert.activo = 1 AND cert.deleted_at IS NULL
                ORDER BY cert.nombre";

        return $this->select_all($sql, [$idConfiguracion]);
    }

    public function upsertCertificacionConfiguracion(int $idConfiguracion, int $idCertificacion, array $data)
    {
        $idusuario = $_SESSION['userData']['idusuario'] ?? null;

        $sql = "SELECT id_modelo_certificacion FROM ing_modelo_certificacion
                WHERE id_configuracion = ? AND id_certificacion = ?";
        $exist = $this->select($sql, [$idConfiguracion, $idCertificacion]);

        if (!empty($exist)) {
            $sql = "UPDATE ing_modelo_certificacion SET
                obligatoria = ?, estado = ?, numero_certificado = ?, fecha_emision = ?, fecha_inicio = ?,
                fecha_vencimiento = ?, observaciones = ?, updated_by = ?
                WHERE id_configuracion = ? AND id_certificacion = ?";

            return $this->update($sql, [
                $data['obligatoria'], $data['estado'], $data['numero_certificado'], $data['fecha_emision'],
                $data['fecha_inicio'], $data['fecha_vencimiento'], $data['observaciones'], $idusuario,
                $idConfiguracion, $idCertificacion,
            ]);
        }

        $sql = "INSERT INTO ing_modelo_certificacion
            (id_configuracion, id_certificacion, obligatoria, estado, numero_certificado, fecha_emision,
             fecha_inicio, fecha_vencimiento, observaciones, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)";

        return $this->insert($sql, [
            $idConfiguracion, $idCertificacion, $data['obligatoria'], $data['estado'], $data['numero_certificado'],
            $data['fecha_emision'], $data['fecha_inicio'], $data['fecha_vencimiento'], $data['observaciones'],
            $idusuario,
        ]);
    }
}
