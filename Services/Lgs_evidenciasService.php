<?php

class Lgs_evidenciasService {

    private Lgs_evidenciasModel $model;

    public function __construct() {
        $this->model = new Lgs_evidenciasModel();
    }

    public function getEnviosEvidencias(): array {
        return $this->model->getEnviosParaEvidencias();
    }

    public function getEvidenciasEnvio(int $idEnvio): array {
        return $this->model->getEvidenciasPorEnvio($idEnvio);
    }

    public function guardarEvidencia(array $data, int $userId): int {
        if (empty($data['id_envio']) || empty($data['ruta_archivo']) || empty($data['tipo_evidencia'])) {
            throw new Exception("Datos incompletos para guardar la evidencia.");
        }

        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $data['created_by'] = $userId;
            $idEvidencia = $this->model->insertEvidencia($db, $data);
            $db->commit();
            return $idEvidencia;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function borrarEvidencia(int $idEvidencia): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->deleteEvidencia($db, $idEvidencia);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function confirmarLlegadaDestino(int $idEnvio, string $fechaLlegada, int $userId): void {
        $db = $this->model->getConexion();
        try {
            $db->beginTransaction();
            $this->model->registrarEntregaFinal($db, $idEnvio, $fechaLlegada, $userId);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Procesa y guarda un lote de evidencias desde un archivo ZIP
     */
    public function guardarEvidenciasZip(int $idEnvio, ?int $idUnidad, int $tipoEvidencia, array $zipFile, string $observaciones, int $userId): array {
        require_once 'Services/Lgs_zipEvidenciasHelper.php';
        $helper = new Lgs_zipEvidenciasHelper();
        $uploadDir = 'Assets/images/uploads/evidencias/';
        $prefix = 'ev_' . $idEnvio . '_' . ($idUnidad ? $idUnidad . '_' : '');
        $context = ($tipoEvidencia === 2) ? 'entrega_destino' : 'inspeccion';
        $fotos = $helper->procesarZip($zipFile, $uploadDir, $prefix, $context);

        $db = $this->model->getConexion();
        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
            }
            $ids = [];
            foreach ($fotos as $f) {
                $obs = "Foto " . ucfirst($f['key']) . ($observaciones ? " - " . $observaciones : "");
                $id = $this->model->insertEvidencia($db, [
                    'id_envio' => $idEnvio,
                    'id_unidad' => $idUnidad,
                    'tipo_evidencia' => $tipoEvidencia,
                    'ruta_archivo' => $f['destFile'],
                    'observaciones' => $obs,
                    'created_by' => $userId
                ]);
                $ids[] = $id;
            }
            if ($db->inTransaction()) {
                $db->commit();
            }
            return $ids;
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Genera un archivo ZIP consolidado con todas las evidencias de un envío
     * @return array ['path' => string, 'filename' => string]
     */
    public function generarZipEvidenciasEnvio(int $idEnvio): array {
        $evidencias = $this->model->getEvidenciasPorEnvio($idEnvio);
        if (empty($evidencias)) {
            throw new Exception("El envío no cuenta con evidencias registradas para descargar.");
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception("La extensión ZipArchive no está habilitada en el servidor.");
        }

        // Consultar folio del envío
        $db = $this->model->getConexion();
        $stmtFolio = $db->prepare("SELECT folio FROM lgs_envios WHERE id_envio = ?");
        $stmtFolio->execute([$idEnvio]);
        $folio = $stmtFolio->fetchColumn() ?: "EN-" . str_pad((string)$idEnvio, 6, '0', STR_PAD_LEFT);

        $tmpZip = tempnam(sys_get_temp_dir(), 'ev_zip_') . '.zip';
        $zip = new ZipArchive();
        if ($zip->open($tmpZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception("No se pudo crear el archivo ZIP temporal.");
        }

        $contador = 1;
        foreach ($evidencias as $ev) {
            $ruta = $ev['ruta_archivo'] ?? '';
            if (empty($ruta) || !file_exists($ruta) || !is_readable($ruta)) {
                continue;
            }

            $ext = pathinfo($ruta, PATHINFO_EXTENSION) ?: 'jpg';
            $tipoMomento = ((int)$ev['tipo_evidencia'] === 2) ? 'Destino' : 'Planta';
            $vin = !empty($ev['vin']) ? preg_replace('/[^a-zA-Z0-9_-]/', '_', $ev['vin']) : 'General';
            $nombreArchivoEnZip = sprintf("%02d_%s_%s_%d.%s", $contador++, $tipoMomento, $vin, $ev['id_evidencia'], $ext);

            $zip->addFile($ruta, $nombreArchivoEnZip);
        }

        $numFiles = $zip->numFiles;
        $zip->close();

        if ($numFiles === 0) {
            @unlink($tmpZip);
            throw new Exception("No se encontraron archivos físicos legibles de evidencias para este envío.");
        }

        return [
            'path' => $tmpZip,
            'filename' => "Evidencias_{$folio}.zip"
        ];
    }
}
