<?php

/**
 * Motor de reglas de autorización/bloqueo automático de configuraciones
 * de vehículo (Ingeniería - Épica 3).
 *
 * Reglas de autorización (deben cumplirse TODAS):
 *   1. Modelo activo (wms_sublinea_producto.estado = 2, y si existe
 *      detalle de ingeniería, que también esté ACTIVO).
 *   2. Configuración completa (motor, transmisión, combustible, peso
 *      bruto y nivel de emisiones capturados).
 *   3. Clave vehicular capturada.
 *   4. Especificaciones técnicas completas (todas las del catálogo
 *      activo tienen un valor capturado para esta configuración).
 *   5. Certificaciones obligatorias vigentes (todas las del catálogo
 *      activo, salvo las marcadas explícitamente como "no aplica"
 *      para esta configuración, están en estado VIGENTE y, si
 *      requieren vigencia, con fecha de vencimiento futura).
 *   6. Documentación disponible: para certificaciones que requieren
 *      documento, por ahora se valida que tengan número de
 *      certificado capturado (el módulo aún no tiene carga de
 *      archivos; cuando exista, este punto debe validar el archivo).
 *
 * Bloqueo automático: si una configuración AUTORIZADA deja de cumplir
 * la regla 5 (por ejemplo, vence una certificación obligatoria), pasa
 * a BLOQUEADO. Si una configuración BLOQUEADA vuelve a cumplir todo
 * (se renovó la certificación), regresa a AUTORIZADO automáticamente.
 *
 * Solo se evalúan configuraciones en EN_REVISION, AUTORIZADO o
 * BLOQUEADO. BORRADOR y OBSOLETO quedan fuera del motor: BORRADOR
 * porque todavía se está capturando, OBSOLETO porque es un retiro
 * manual y definitivo.
 */
class Ing_reglasService
{
    private Ing_reglasModel $model;

    const ESTADOS_EVALUABLES = ['EN_REVISION', 'AUTORIZADO', 'BLOQUEADO'];

    public function __construct()
    {
        $this->model = new Ing_reglasModel();
    }

    /**
     * Evalúa una configuración y, si corresponde, cambia su estado y
     * deja registro en la bitácora. Devuelve el detalle del resultado
     * aunque no haya habido cambio de estado.
     */
    public function evaluarConfiguracion(int $idConfiguracion, string $origen = 'GUARDADO'): array
    {
        $config = $this->model->selectConfiguracionParaEvaluar($idConfiguracion);

        $resultado = [
            'evaluado'        => false,
            'cambio'          => false,
            'aprueba'         => false,
            'estado_anterior' => $config['estado'] ?? null,
            'estado_nuevo'    => $config['estado'] ?? null,
            'motivos'         => [],
        ];

        if (empty($config)) {
            return $resultado;
        }

        $estadoActual = $config['estado'];
        if (!in_array($estadoActual, self::ESTADOS_EVALUABLES)) {
            return $resultado;
        }

        // Refleja vencimientos de certificaciones antes de evaluar, para no
        // depender de que alguien haya vuelto a guardar esa certificación.
        $this->model->marcarCertificacionesVencidas();

        $motivos = $this->evaluarReglas($config, $idConfiguracion);
        $aprueba = empty($motivos);

        $resultado['evaluado'] = true;
        $resultado['aprueba'] = $aprueba;
        $resultado['motivos'] = $motivos;

        $estadoNuevo = $estadoActual;
        if ($aprueba && $estadoActual !== 'AUTORIZADO') {
            $estadoNuevo = 'AUTORIZADO';
        } else if (!$aprueba && $estadoActual === 'EN_REVISION') {
            $estadoNuevo = 'EN_CORRECCION';
        } else if (!$aprueba && $estadoActual === 'AUTORIZADO') {
            $estadoNuevo = 'BLOQUEADO';
        }

        if ($estadoNuevo !== $estadoActual) {
            $this->model->actualizarEstadoConfiguracion($idConfiguracion, $estadoNuevo);
            $this->model->insertLogEvaluacion($idConfiguracion, $estadoActual, $estadoNuevo, $aprueba, $motivos, $origen);
            $resultado['cambio'] = true;
            $resultado['estado_nuevo'] = $estadoNuevo;
        }

        return $resultado;
    }

    /**
     * Recorre todas las configuraciones vigilables (para la tarea programada
     * diaria o para el botón "Reevaluar todas ahora"). Devuelve un resumen.
     */
    public function evaluarTodas(string $origen = 'TAREA_PROGRAMADA'): array
    {
        $configuraciones = $this->model->selectConfiguracionesParaEvaluar();

        $resumen = [
            'total'       => count($configuraciones),
            'autorizadas' => 0,
            'bloqueadas'  => 0,
            'correccion'  => 0,
            'sin_cambio'  => 0,
        ];

        foreach ($configuraciones as $row) {
            $r = $this->evaluarConfiguracion((int) $row['id_configuracion'], $origen);
            if (!$r['cambio']) {
                $resumen['sin_cambio']++;
                continue;
            }
            switch ($r['estado_nuevo']) {
                case 'AUTORIZADO':
                    $resumen['autorizadas']++;
                    break;
                case 'BLOQUEADO':
                    $resumen['bloqueadas']++;
                    break;
                case 'EN_CORRECCION':
                    $resumen['correccion']++;
                    break;
            }
        }

        return $resumen;
    }

    /**
     * Calcula el progreso de una configuración por etapa (datos generales,
     * especificaciones, certificaciones) más el estado final (autorizado o
     * no), para mostrarlo como línea de proceso en la pantalla. A diferencia
     * de evaluarConfiguracion(), este método es de SOLO LECTURA: no cambia
     * el estado ni escribe bitácora, y se puede llamar en cualquier estado
     * (incluido BORRADOR) para que el usuario vea qué falta antes de mandar
     * la configuración a revisión.
     */
    public function evaluarProgreso(int $idConfiguracion): array
    {
        $config = $this->model->selectConfiguracionParaEvaluar($idConfiguracion);
        if (empty($config)) {
            return [];
        }

        $motivos = $this->evaluarReglas($config, $idConfiguracion);

        $motivosDatos = [];
        $motivosEspecificaciones = [];
        $motivosCertificaciones = [];
        $motivosOtros = [];

        foreach ($motivos as $motivo) {
            if (strpos($motivo, 'especificaci') !== false) {
                $motivosEspecificaciones[] = $motivo;
            } else if (strpos($motivo, 'certificaci') !== false) {
                $motivosCertificaciones[] = $motivo;
            } else if (strpos($motivo, 'datos generales') !== false || strpos($motivo, 'clave vehicular') !== false) {
                $motivosDatos[] = $motivo;
            } else {
                // Ej. "modelo (sublínea de producto) no está activo": no es
                // capturable desde esta pantalla, se muestra aparte.
                $motivosOtros[] = $motivo;
            }
        }

        $totalEspecificaciones = $this->model->contarEspecificacionesTotal();
        $faltantesEspecificaciones = $this->model->contarEspecificacionesFaltantes($idConfiguracion);

        $totalCertObligatorias = 0;
        $completasCertObligatorias = 0;
        foreach ($this->model->selectCertificacionesParaEvaluar($idConfiguracion) as $cert) {
            // obligatoria es NULL cuando la configuración nunca capturó esta
            // certificación (se trata como obligatoria por default, igual
            // que evaluarReglas()).
            $esObligatoria = $cert['obligatoria'] === null || (int) $cert['obligatoria'] === 1;
            if (!$esObligatoria) {
                continue;
            }
            $totalCertObligatorias++;
            $completa = !empty($cert['estado']) && $cert['estado'] === 'VIGENTE'
                && (empty($cert['requiere_vigencia']) || !empty($cert['fecha_vencimiento']))
                && (empty($cert['requiere_documento']) || !empty($cert['numero_certificado']));
            if ($completa) {
                $completasCertObligatorias++;
            }
        }

        return [
            'estado' => $config['estado'],
            'datos_generales' => [
                'completo' => empty($motivosDatos),
                'motivos'  => $motivosDatos,
            ],
            'especificaciones' => [
                'completo'   => empty($motivosEspecificaciones),
                'total'      => $totalEspecificaciones,
                'capturadas' => max(0, $totalEspecificaciones - $faltantesEspecificaciones),
                'motivos'    => $motivosEspecificaciones,
            ],
            'certificaciones' => [
                'completo'  => empty($motivosCertificaciones),
                'total'     => $totalCertObligatorias,
                'completas' => $completasCertObligatorias,
                'motivos'   => $motivosCertificaciones,
            ],
            'otros_motivos' => $motivosOtros,
        ];
    }

    /**
     * Devuelve la lista de motivos por los que NO se cumplen las reglas.
     * Un arreglo vacío significa que la configuración cumple todo.
     */
    private function evaluarReglas(array $config, int $idConfiguracion): array
    {
        $motivos = [];

        // 1. Modelo activo
        if ((int) $config['sublinea_estado'] !== 2) {
            $motivos[] = 'El modelo (sublínea de producto) no está activo.';
        }
        if (!empty($config['detalle_estado']) && $config['detalle_estado'] !== 'ACTIVO') {
            $motivos[] = 'El detalle de ingeniería del modelo no está en estado ACTIVO.';
        }

        // 2. Configuración completa
        $camposRequeridos = [
            'id_motor'        => 'motor',
            'id_transmision'  => 'transmisión',
            'combustible'     => 'combustible',
            'peso_bruto'      => 'peso bruto vehicular',
            'nivel_emisiones' => 'nivel de emisiones',
        ];
        foreach ($camposRequeridos as $campo => $label) {
            if ($config[$campo] === null || $config[$campo] === '') {
                $motivos[] = 'Falta capturar ' . $label . ' en los datos generales.';
            }
        }

        // 3. Clave vehicular válida
        if (empty($config['clave_vehicular'])) {
            $motivos[] = 'Falta capturar la clave vehicular.';
        }

        // 4. Especificaciones completas
        $faltantes = $this->model->contarEspecificacionesFaltantes($idConfiguracion);
        if ($faltantes > 0) {
            $motivos[] = 'Faltan ' . $faltantes . ' especificación(es) técnica(s) por capturar.';
        }

        // 5. Certificaciones obligatorias vigentes + 6. Documentación disponible
        $certificaciones = $this->model->selectCertificacionesParaEvaluar($idConfiguracion);
        foreach ($certificaciones as $cert) {
            // obligatoria es NULL cuando la configuración nunca capturó esta certificación
            // (se trata como obligatoria por default, igual que el checkbox de la pantalla).
            $esObligatoria = $cert['obligatoria'] === null || (int) $cert['obligatoria'] === 1;
            if (!$esObligatoria) {
                continue;
            }

            if (empty($cert['estado'])) {
                $motivos[] = 'Falta capturar la certificación obligatoria "' . $cert['nombre'] . '".';
                continue;
            }

            if ($cert['estado'] !== 'VIGENTE') {
                $motivos[] = 'La certificación "' . $cert['nombre'] . '" está en estado ' . $cert['estado'] . '.';
            }

            if (!empty($cert['requiere_vigencia']) && empty($cert['fecha_vencimiento'])) {
                $motivos[] = 'La certificación "' . $cert['nombre'] . '" requiere fecha de vencimiento y no la tiene capturada.';
            }

            if (!empty($cert['requiere_documento']) && empty($cert['numero_certificado'])) {
                $motivos[] = 'La certificación "' . $cert['nombre'] . '" requiere documento/número de certificado y no lo tiene capturado.';
            }
        }

        return $motivos;
    }
}
