<?php

declare(strict_types=1);

class Lgs_uploadService
{
    private const ALLOWED_EXT_INCIDENCIAS = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'mp4'];
    private const ALLOWED_EXT_GASTOS      = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'xml'];

    private const MIME_MAP = [
        'jpg'  => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png'  => ['image/png'],
        'webp' => ['image/webp'],
        'pdf'  => ['application/pdf'],
        'mp4'  => ['video/mp4'],
        'xml'  => ['text/xml', 'application/xml', 'text/plain'],
    ];

    private const MAX_SIZE_BYTES = 10485760; // 10MB
    private const MAX_VIDEO_SIZE = 26214400; // 25MB

    /**
     * Sube y valida un archivo para evidencias de Incidencias
     */
    public function uploadEvidenciaIncidencia(array $file, int $idIncidencia): array
    {
        return $this->processUpload(
            $file,
            'Uploads/lgs_incidencias/',
            self::ALLOWED_EXT_INCIDENCIAS,
            'ev_inc_' . $idIncidencia . '_'
        );
    }

    /**
     * Sube y valida un documento soporte para Gastos Adicionales
     */
    public function uploadDocumentoGasto(array $file, int $idGasto): array
    {
        return $this->processUpload(
            $file,
            'Uploads/lgs_gastos/',
            self::ALLOWED_EXT_GASTOS,
            'doc_gas_' . $idGasto . '_'
        );
    }

    /**
     * Procesa la subida con validaciones de seguridad estrictas
     */
    private function processUpload(array $file, string $targetDir, array $allowedExts, string $prefix): array
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error en la subida del archivo. Código: " . ($file['error'] ?? 'DESCONOCIDO'));
        }

        $origName = basename($file['name']);
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception("Formato de archivo no permitido (.{$ext}). Permitidos: " . implode(', ', $allowedExts));
        }

        $maxSize = ($ext === 'mp4') ? self::MAX_VIDEO_SIZE : self::MAX_SIZE_BYTES;
        if ($file['size'] > $maxSize) {
            $mb = round($maxSize / (1024 * 1024));
            throw new Exception("El archivo excede el tamaño máximo permitido de {$mb}MB.");
        }

        // Inspección de MIME mediante magic bytes (finfo)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $detectedMime = $finfo->file($file['tmp_name']);
        $expectedMimes = self::MIME_MAP[$ext] ?? [];

        if (!in_array($detectedMime, $expectedMimes, true)) {
            throw new Exception("El contenido del archivo no coincide con su extensión (detectado: {$detectedMime}).");
        }

        // Asegurar directorio destino con protección
        $this->ensureSecureDirectory($targetDir);

        // Nombre de archivo impredecible
        $randomHash = bin2hex(random_bytes(16));
        $safeFileName = $prefix . $randomHash . '.' . $ext;
        $destPath = rtrim($targetDir, '/') . '/' . $safeFileName;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new Exception("No se pudo guardar el archivo en el servidor.");
        }

        chmod($destPath, 0640);

        return [
            'ruta_archivo'    => $destPath,
            'nombre_original' => $origName,
            'mime'            => $detectedMime,
            'tamano_bytes'    => $file['size'],
            'ext'             => $ext
        ];
    }

    /**
     * Asegura que el directorio exista y tenga .htaccess de protección contra ejecución
     */
    private function ensureSecureDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $htaccessPath = rtrim($dir, '/') . '/.htaccess';
        if (!file_exists($htaccessPath)) {
            $htaccessContent = <<<EOT
# Bloqueo estricto de ejecucion de scripts
<FilesMatch "\.(php|phtml|php3|php4|php5|php7|php8|phps|pl|py|cgi|sh|bash)$">
    Require all denied
</FilesMatch>
Options -Indexes -ExecCGI
EOT;
            @file_put_contents($htaccessPath, $htaccessContent);
        }
    }
}
