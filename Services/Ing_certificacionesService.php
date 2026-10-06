<?php

/**
 * Captura de certificaciones por configuración de vehículo (Ingeniería).
 *
 * Lo comparten dos pantallas con permisos distintos:
 *   - Ing_configuraciones (Ingeniería: puede editar todo).
 *   - Ing_juridico        (Jurídico: solo carga certificaciones).
 *
 * El permiso de quién puede llamar a este servicio se valida en cada
 * controlador; aquí viven las validaciones de datos y de archivos, el guardado,
 * la bitácora y la evaluación del motor de reglas.
 */
class Ing_certificacionesService
{
    const DIRECTORIO = 'Assets/uploads/ing_certificaciones/';
    const EXTENSIONES_PERMITIDAS = ['pdf', 'jpg', 'jpeg', 'png'];
    const MIMES_PERMITIDOS = ['application/pdf', 'image/jpeg', 'image/png'];
    const TAMANO_MAXIMO = 10485760; // 10 MB

    private Ing_configuracionesModel $model;

    public function __construct()
    {
        $this->model = new Ing_configuracionesModel();
    }

    /**
     * @param array $certificaciones Filas enviadas por el formulario (id_certificacion, estado, etc.).
     * @param array $files           Normalmente $_FILES; los adjuntos vienen como archivo_<id_certificacion>.
     * @param bool  $conservarObligatoria true = ignora el valor "obligatoria" enviado por el cliente y
     *                                    conserva el que ya tiene guardado (perfil Jurídico).
     * @return array ['status' => bool, 'msg' => string, 'evaluacion' => array|null]
     */
    public function guardar(
        int $idConfiguracion,
        array $certificaciones,
        array $files,
        ?int $idusuario,
        string $origen = 'Ingeniería',
        bool $conservarObligatoria = false
    ): array {
        if ($idConfiguracion <= 0) {
            return ['status' => false, 'msg' => 'Primero guarda los datos generales de la configuración.'];
        }

        // Estado actual (para códigos en bitácora y para conservar "obligatoria").
        $actuales = [];
        foreach ($this->model->selectCertificacionesConfiguracion($idConfiguracion) as $fila) {
            $actuales[intval($fila['id_certificacion'])] = $fila;
        }

        // 1) Validar TODOS los archivos antes de guardar nada.
        $errorArchivos = $this->validarArchivos($certificaciones, $files);
        if ($errorArchivos !== null) {
            return ['status' => false, 'msg' => $errorArchivos];
        }

        // 2) Guardar.
        $ok = true;
        $archivosCargados = [];
        $capturadas = 0;

        foreach ($certificaciones as $cert) {
            $idCertificacion = intval($cert['id_certificacion'] ?? 0);
            if ($idCertificacion <= 0) {
                continue;
            }
            $capturadas++;

            $archivo = $this->moverArchivo($files, $idConfiguracion, $idCertificacion);
            if ($archivo !== null) {
                $archivosCargados[] = $actuales[$idCertificacion]['codigo'] ?? ('#' . $idCertificacion);
            }

            if ($conservarObligatoria) {
                $existente = $actuales[$idCertificacion]['obligatoria'] ?? null;
                $obligatoria = ($existente === null) ? 1 : (intval($existente) === 1 ? 1 : 0);
            } else {
                $obligatoria = !empty($cert['obligatoria']) ? 1 : 0;
            }

            $data = [
                'obligatoria'        => $obligatoria,
                'estado'             => strClean($cert['estado'] ?? 'PENDIENTE'),
                'numero_certificado' => strClean($cert['numero_certificado'] ?? ''),
                'fecha_emision'      => !empty($cert['fecha_emision']) ? $cert['fecha_emision'] : null,
                'fecha_inicio'       => !empty($cert['fecha_inicio']) ? $cert['fecha_inicio'] : null,
                'fecha_vencimiento'  => !empty($cert['fecha_vencimiento']) ? $cert['fecha_vencimiento'] : null,
                'observaciones'      => strClean($cert['observaciones'] ?? ''),
                'archivo'            => $archivo,
            ];

            if (!$this->model->upsertCertificacionConfiguracion($idConfiguracion, $idCertificacion, $data)) {
                $ok = false;
            }
        }

        if (!$ok) {
            return ['status' => false, 'msg' => 'Ocurrió un error al guardar alguna de las certificaciones.'];
        }

        // 3) Bitácora.
        $comentario = 'Certificaciones actualizadas desde ' . $origen . ' (' . $capturadas . ' certificaciones capturadas';
        if (!empty($archivosCargados)) {
            $comentario .= '; archivos cargados: ' . implode(', ', $archivosCargados);
        }
        $comentario .= ').';
        $this->model->logAudit($idConfiguracion, AuditAction::UPDATED, $comentario, $idusuario);

        // 4) Motor de reglas.
        $msg = 'Las certificaciones se guardaron correctamente.';
        $evaluacion = (new Ing_reglasService())->evaluarConfiguracion($idConfiguracion, 'GUARDADO');
        if (!empty($evaluacion['cambio'])) {
            $msg .= ' ' . $this->mensajeEvaluacion($evaluacion);
        }

        return ['status' => true, 'msg' => trim($msg), 'evaluacion' => $evaluacion];
    }

    /** Devuelve un mensaje de error o null si todos los archivos son válidos. */
    private function validarArchivos(array $certificaciones, array $files): ?string
    {
        foreach ($certificaciones as $cert) {
            $idCertificacion = intval($cert['id_certificacion'] ?? 0);
            $campo = 'archivo_' . $idCertificacion;
            if ($idCertificacion <= 0 || !isset($files[$campo])) {
                continue;
            }
            $f = $files[$campo];
            if ($f['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE || $f['size'] > self::TAMANO_MAXIMO) {
                return 'El archivo "' . strClean($f['name']) . '" excede el tamaño máximo permitido (10 MB).';
            }
            if ($f['error'] !== UPLOAD_ERR_OK) {
                return 'No se pudo cargar el archivo "' . strClean($f['name']) . '". Intenta de nuevo.';
            }
            $extension = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, self::EXTENSIONES_PERMITIDAS, true)) {
                return 'El archivo "' . strClean($f['name']) . '" no es válido. Solo se permiten PDF, JPG o PNG.';
            }
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $f['tmp_name']);
                finfo_close($finfo);
                if (!in_array($mime, self::MIMES_PERMITIDOS, true)) {
                    return 'El contenido de "' . strClean($f['name']) . '" no corresponde a un PDF, JPG o PNG.';
                }
            }
        }
        return null;
    }

    /** Mueve el archivo subido (si hay uno) y devuelve su nombre, o null si no se subió ninguno. */
    private function moverArchivo(array $files, int $idConfiguracion, int $idCertificacion): ?string
    {
        $campo = 'archivo_' . $idCertificacion;
        if (!isset($files[$campo]) || $files[$campo]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if (!file_exists(self::DIRECTORIO)) {
            mkdir(self::DIRECTORIO, 0777, true);
        }
        $extension = strtolower(pathinfo($files[$campo]['name'], PATHINFO_EXTENSION));
        $nombre = 'cert_' . $idConfiguracion . '_' . $idCertificacion . '_' . date('YmdHis') . '_'
            . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 5) . '.' . $extension;

        return move_uploaded_file($files[$campo]['tmp_name'], self::DIRECTORIO . $nombre) ? $nombre : null;
    }

    private function mensajeEvaluacion(array $evaluacion): string
    {
        switch ($evaluacion['estado_nuevo'] ?? '') {
            case 'AUTORIZADO':
                return 'El sistema la autorizó automáticamente al cumplir todas las reglas.';
            case 'BLOQUEADO':
                return 'El sistema la bloqueó automáticamente: ' . implode('; ', $evaluacion['motivos'] ?? []) . '.';
            case 'EN_CORRECCION':
                return 'Quedó en corrección: ' . implode('; ', $evaluacion['motivos'] ?? []) . '.';
            default:
                return '';
        }
    }
}
