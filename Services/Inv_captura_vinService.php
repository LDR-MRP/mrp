<?php

class Inv_captura_vinService
{

    public $model;

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store($data)
    {

        // VALIDACIÓN
        foreach ($data as $key => $val) {

            if ($key != "id" && empty($val)) {

                return ServiceResponse::error(
                    "Todos los campos son obligatorios"
                );
            }
        }

        // VALIDAR DUPLICADOS
        $exist = $this->model->select(

            "SELECT id_cat_modelo_vin

            FROM cat_modelos_vin

            WHERE id_inventario = ?
            AND id_fabricante = ?
            AND id_tipo_vehiculo = ?
            AND peso_bruto_kg = ?
            AND id_tipo_motor = ?
            AND potencia_hp = ?
            AND distancia_ejes = ?
            AND id_cat_anio_vin = ?
            AND id_planta = ?",

            [

                $data['id_inventario'],
                $data['id_fabricante'],
                $data['id_tipo_vehiculo'],
                $data['peso_bruto_kg'],
                $data['id_tipo_motor'],
                $data['potencia_hp'],
                $data['distancia_ejes'],
                $data['anio'],
                $data['id_planta']

            ]

        );

        // UPDATE
        if (!empty($data['id'])) {

            $antes = $this->model->selectModeloVinDetalle($data['id']);

            $update = $this->model->updateModeloVin($data);

            if ($update) {

                $this->registrarCambiosHistorial($antes, $this->model->selectModeloVinDetalle($data['id']));

                return ServiceResponse::success(
                    [],
                    "Modelo VIN actualizado"
                );
            }

            return ServiceResponse::error(
                "Error al actualizar"
            );
        }

        // INSERT
        if (!empty($exist)) {

            return ServiceResponse::error(
                "Ya existe esta configuración VIN"
            );
        }

        $insert = $this->model->insertModeloVin($data);

        if ($insert) {

            $this->registrarHistorial([
                'id_cat_modelo_vin' => $insert,
                'accion' => 'ALTA',
                'campo' => 'vin_base',
                'valor_nuevo' => $data['vin_base'],
                'detalle' => 'Modelo ' . $data['modelo'],
            ]);

            return ServiceResponse::success(
                [],
                "Modelo VIN guardado"
            );
        }

        return ServiceResponse::error(
            "Error al guardar"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HISTORIAL del VIN base (wms_vin_historial, modulo = VIN_BASE)
    | Una fila por cada campo que cambió, con su valor anterior y nuevo.
    | El historial nunca debe impedir guardar el modelo: si falla, solo se loguea.
    |--------------------------------------------------------------------------
    */

    private const CAMPOS_HISTORIAL = [
        'vin_base' => 'VIN base',
        'modelo' => 'Modelo',
        'fabricante' => 'Fabricante',
        'tipo_vehiculo' => 'Tipo de vehículo',
        'peso_bruto_kg' => 'Peso bruto (kg)',
        'tipo_motor' => 'Tipo de motor',
        'potencia_hp' => 'Potencia (HP)',
        'distancia_ejes' => 'Distancia entre ejes',
        'anio' => 'Año',
        'planta' => 'Planta',
        'estado' => 'Estado',
    ];

    private function registrarCambiosHistorial($antes, $despues)
    {
        if (empty($antes) || empty($despues)) {
            return;
        }
        foreach (self::CAMPOS_HISTORIAL as $campo => $etiqueta) {
            $a = $this->valorHistorial($campo, $antes[$campo] ?? null);
            $d = $this->valorHistorial($campo, $despues[$campo] ?? null);
            if ($a === $d) {
                continue;
            }
            $this->registrarHistorial([
                'id_cat_modelo_vin' => $despues['id_cat_modelo_vin'],
                'accion' => 'MODIFICACION',
                'campo' => $campo,
                'valor_anterior' => $a,
                'valor_nuevo' => $d,
                'detalle' => $etiqueta . ' · modelo ' . $despues['modelo'],
            ]);
        }
    }

    private function valorHistorial($campo, $v)
    {
        if ($v === null) {
            return null;
        }
        if ($campo === 'estado') {
            return $v == 2 ? 'Activo' : 'Inactivo';
        }
        // 4500.00 y 4500 son el mismo valor
        if (is_numeric($v)) {
            return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        }
        return trim((string) $v);
    }

    private function registrarHistorial(array $d)
    {
        try {
            $historial = new Inv_vin_historialModel();
            $historial->registrar($d + [
                'modulo' => 'VIN_BASE',
                'origen' => 'INDIVIDUAL',
                'usuarioid' => $_SESSION['idUser'] ?? null,
            ]);
        } catch (\Throwable $e) {
            error_log('Inv_captura_vinService::registrarHistorial: ' . $e->getMessage());
        }
    }

    public function getHistorial($idModelo)
    {
        $historial = new Inv_vin_historialModel();
        return ServiceResponse::success($historial->selectHistorialModelo(intval($idModelo)));
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL
    |--------------------------------------------------------------------------
    */

    public function getAll()
    {

        return ServiceResponse::success(
            $this->model->getModelosVin()
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DATOS DE INGENIERÍA → campos del NIV base
    | Regresa solo lo que se pudo determinar con certeza; lo demás va en null
    | para que el usuario lo capture.
    |--------------------------------------------------------------------------
    */

    public function getDatosIngenieria($idInventario)
    {
        $idInventario = intval($idInventario);
        if ($idInventario <= 0) {
            return ServiceResponse::success(["encontrado" => false]);
        }

        $conf = $this->model->selectConfiguracionIngenieria($idInventario);
        if (empty($conf)) {
            return ServiceResponse::success(["encontrado" => false]);
        }

        // Especificaciones dinámicas (por clave, no por id: los ids cambian entre ambientes)
        $specs = [];
        foreach ($this->model->selectEspecificacionesIngenieria($conf['id_configuracion']) as $e) {
            $specs[] = [
                "clave" => $this->normalizar($e['clave']),
                "unidad" => strtolower(trim($e['unidad'] ?? '')),
                "valor" => $e['valor'],
            ];
        }

        // ---- Peso bruto (kg): campo de la configuración, si no la especificación PBV
        $peso = $this->numero($conf['peso_bruto']);
        if (!$peso) {
            $peso = $this->numero($this->spec($specs, ['pbv', 'peso bruto vehicular']));
        }

        // ---- Potencia (HP): del motor; si no, especificación "Potencia" (ej. 480@1800)
        $hp = $this->numero($conf['potencia']);
        if ($hp) {
            $u = strtolower($conf['unidad_potencia'] ?? '');
            if (strpos($u, 'kw') !== false) $hp = $hp * 1.341;
            elseif (strpos($u, 'cv') !== false || strpos($u, 'ps') !== false) $hp = $hp * 0.986;
        } else {
            $hp = $this->numero($this->spec($specs, ['potencia']));
        }

        // ---- Tipo de motor (combustible) → cat_vin_tipo_motor
        $combustible = $conf['combustible'] ?: ($conf['motor_tipo_combustible'] ?? '');
        $tipoMotorTxt = $this->tipoMotorVin($combustible, $conf['tipo_motor'] ?? '');
        $idTipoMotor = null;
        if ($tipoMotorTxt) {
            foreach ($this->model->selectTiposMotor() as $t) {
                if ($this->normalizar($t['descripcion']) === $this->normalizar($tipoMotorTxt)) {
                    $idTipoMotor = $t['id_tipo_motor'];
                    break;
                }
            }
        }

        // ---- Distancias (se regresan en mm; el front decide cuál usar según la clasificación)
        $wb = $this->mm($specs, ['distancia entre ejes', 'wb']);
        $largo = $this->mm($specs, ['largo total', 'longitud total']);

        return ServiceResponse::success([
            "encontrado" => true,
            "configuracion" => [
                "id" => $conf['id_configuracion'],
                "nombre" => $conf['nombre_unidad'],
                "estado" => $conf['estado'],
                "motor" => $conf['modelo_motor'],
            ],
            "peso_bruto_kg" => $peso ? round($peso) : null,
            "potencia_hp" => $hp ? (int) round($hp) : null,
            "id_tipo_motor" => $idTipoMotor,
            "combustible" => $combustible,
            "distancia_ejes_mm" => $wb,
            "largo_total_mm" => $largo,
        ]);
    }

    // Combustible de Ingeniería → descripción de cat_vin_tipo_motor.
    // Híbridos / combinados se dejan sin asignar (los decide el usuario).
    private function tipoMotorVin($combustible, $tipoMotor)
    {
        $c = $this->normalizar($combustible);

        // Claves del catálogo de Ingeniería (Ing_configuraciones)
        $claves = [
            'gsl' => 'Gasolina',
            'dsl' => 'Diesel',
            'cng' => 'Gas Natural',
            'gnc' => 'Gas Natural',
            'lng' => 'Gas Natural',
            'ev'  => 'Eléctrico',
            'ele' => 'Eléctrico',
        ];
        if (isset($claves[$c])) {
            return $claves[$c];
        }
        // PHEV, MHEV, FCEV, HIB, GLP: no hay un solo tipo NIV → lo elige el usuario
        if (in_array($c, ['phev', 'mhev', 'fcev', 'hib', 'glp'], true)) {
            return null;
        }

        // Valores anteriores en texto libre
        if (strpos($c, '+') !== false || strpos($c, 'hibrid') !== false || strpos($c, 'hev') !== false) {
            return null;
        }
        if ($tipoMotor === 'ELECTRICO' || strpos($c, 'electric') !== false) return 'Eléctrico';
        if (strpos($c, 'diesel') !== false) return 'Diesel';
        if (strpos($c, 'gasolina') !== false) return 'Gasolina';
        if (strpos($c, 'gnc') !== false || strpos($c, 'gas natural') !== false) return 'Gas Natural';
        return null;
    }

    // Primer valor de especificación cuya clave contenga alguno de los textos
    private function spec(array $specs, array $buscar, &$unidad = null)
    {
        foreach ($buscar as $b) {
            foreach ($specs as $s) {
                if (strpos($s['clave'], $b) !== false && $this->numero($s['valor'])) {
                    $unidad = $s['unidad'];
                    return $s['valor'];
                }
            }
        }
        return null;
    }

    // Especificación de longitud convertida a mm
    private function mm(array $specs, array $buscar)
    {
        $unidad = '';
        $v = $this->numero($this->spec($specs, $buscar, $unidad));
        if (!$v) return null;
        if ($unidad === 'm') $v *= 1000;
        elseif ($unidad === 'cm') $v *= 10;
        return round($v);
    }

    // "4,200" → 4200 · "480@1800" → 480 · "-" → null
    private function numero($v)
    {
        if ($v === null) return null;
        $v = str_replace([',', ' '], '', (string) $v);
        if (preg_match('/^\d+(\.\d+)?/', $v, $m)) {
            $n = floatval($m[0]);
            return $n > 0 ? $n : null;
        }
        return null;
    }

    private function normalizar($t)
    {
        $t = mb_strtolower(trim((string) $t), 'UTF-8');
        return strtr($t, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    }
}
