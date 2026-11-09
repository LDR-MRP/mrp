<?php

declare(strict_types=1);

/**
 * Helper para procesamiento seguro de Carga Masiva de Evidencias en archivo ZIP.
 * Maneja extracción, validación contra Zip Slip, inspección de tipos MIME y mapeo inteligente de fotos.
 */
class Lgs_zipEvidenciasHelper
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];
    private const MAX_FILES_PER_ZIP = 50;
    private const MAX_UNCOMPRESSED_BYTES = 104857600; // 100 MB

    /**
     * Procesa un archivo ZIP de evidencias fotográficas para checklist de unidad
     * 
     * @param array  $zipFile   Elemento $_FILES del archivo ZIP
     * @param string $targetDir Directorio donde se guardarán las fotos extraídas
     * @param string $prefix    Prefijo para los nombres de archivo generados
     * @param string $context   Contexto: 'inspeccion' (5 fotos) o 'entrega_destino' (remisión y firma)
     * @return array Arreglo de fotos procesadas: [['key' => 'frente', 'destFile' => '...', 'originalName' => '...']]
     */
    public function procesarZip(array $zipFile, string $targetDir = 'Assets/images/uploads/evidencias/', string $prefix = 'ev_zip_', string $context = 'inspeccion'): array
    {
        if (!isset($zipFile['error']) || $zipFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Error en la subida del archivo ZIP. Código de error: " . ($zipFile['error'] ?? 'DESCONOCIDO'));
        }

        $origName = basename($zipFile['name'] ?? 'evidencias.zip');
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if ($ext !== 'zip') {
            throw new Exception("El archivo debe ser un paquete comprimido en formato .ZIP.");
        }

        if (!class_exists('ZipArchive')) {
            throw new Exception("La extensión ZipArchive no está habilitada en el servidor.");
        }

        $zip = new ZipArchive();
        $openResult = $zip->open($zipFile['tmp_name']);
        if ($openResult !== true) {
            throw new Exception("No se pudo abrir el archivo ZIP. Código de error ZipArchive: {$openResult}");
        }

        try {
            $numFiles = $zip->numFiles;
            if ($numFiles <= 0) {
                throw new Exception("El archivo ZIP se encuentra vacío.");
            }

            if ($numFiles > self::MAX_FILES_PER_ZIP) {
                throw new Exception("El archivo ZIP contiene demasiados elementos ({$numFiles}). El límite permitido es de " . self::MAX_FILES_PER_ZIP . " archivos.");
            }

            // Asegurar directorio destino y protección
            $this->asegurarDirectorioProtegido($targetDir);

            // 1. Validar seguridad global (Zip Slip y Zip Bomb) y recolectar entradas candidatas
            $candidatos = [];
            $totalUncompressedBytes = 0;

            for ($i = 0; $i < $numFiles; $i++) {
                $stat = $zip->statIndex($i);
                if (!$stat) continue;

                $entryName = $stat['name'];

                // Ignorar carpetas y metadatos de sistemas operativos (macOS, Windows)
                if (str_ends_with($entryName, '/') || str_ends_with($entryName, '\\')) {
                    continue;
                }
                if (str_contains($entryName, '__MACOSX') || basename($entryName) === '.DS_Store' || basename($entryName) === 'Thumbs.db') {
                    continue;
                }

                // Prevención de Zip Slip (Path Traversal)
                if (str_contains($entryName, '..') || str_starts_with($entryName, '/') || str_starts_with($entryName, '\\')) {
                    throw new Exception("El archivo ZIP contiene rutas sospechosas o no seguras ({$entryName}).");
                }

                $fileBase = basename($entryName);
                $fileExt = strtolower(pathinfo($fileBase, PATHINFO_EXTENSION));

                if (!in_array($fileExt, self::ALLOWED_EXTENSIONS, true)) {
                    // Ignorar archivos que no sean imágenes válidas (evitar scripts o ejecutables)
                    continue;
                }

                $uncompressedSize = $stat['size'];
                $totalUncompressedBytes += $uncompressedSize;
                if ($totalUncompressedBytes > self::MAX_UNCOMPRESSED_BYTES) {
                    throw new Exception("El tamaño total descomprimido del ZIP excede el límite permitido de 100MB.");
                }

                $candidatos[] = [
                    'index'        => $i,
                    'entry_name'   => $entryName,
                    'file_name'    => $fileBase,
                    'ext'          => $fileExt,
                    'size'         => $uncompressedSize
                ];
            }

            if (empty($candidatos)) {
                throw new Exception("El archivo ZIP no contiene fotografías válidas (.jpg, .jpeg, .png, .webp).");
            }

            // Ordenar candidatos por nombre de archivo para mantener secuencia coherente
            usort($candidatos, function ($a, $b) {
                return strnatcasecmp($a['file_name'], $b['file_name']);
            });

            // 2. Extraer y validar contenido binario real (MIME via finfo)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $fotosExtraidas = [];

            foreach ($candidatos as $item) {
                $stream = $zip->getStream($item['entry_name']);
                if (!$stream) {
                    continue;
                }

                $content = stream_get_contents($stream);
                fclose($stream);

                if ($content === false || strlen($content) === 0) {
                    continue;
                }

                // Validar MIME con magic bytes reales
                $detectedMime = $finfo->buffer($content);
                if (!in_array($detectedMime, self::ALLOWED_MIME_TYPES, true)) {
                    // Contenido no coincide con una imagen real
                    continue;
                }

                $fotosExtraidas[] = [
                    'original_name' => $item['file_name'],
                    'ext'           => $item['ext'],
                    'mime'          => $detectedMime,
                    'size'          => strlen($content),
                    'content'       => $content
                ];
            }

            if (empty($fotosExtraidas)) {
                throw new Exception("Ninguno de los archivos en el ZIP pasó la validación de formato de imagen.");
            }

            // 3. Mapeo inteligente de fotos a posiciones
            $fotosMapeadas = $this->mapearFotos($fotosExtraidas, $context);

            // 4. Guardar archivos en el directorio final con nombres seguros
            $resultado = [];
            foreach ($fotosMapeadas as $f) {
                $safeName = $prefix . $f['key'] . '_' . bin2hex(random_bytes(8)) . '.' . $f['ext'];
                $destPath = rtrim($targetDir, '/') . '/' . $safeName;

                if (file_put_contents($destPath, $f['content']) === false) {
                    throw new Exception("No se pudo guardar la fotografía '{$f['original_name']}' en el servidor.");
                }

                @chmod($destPath, 0666);

                $resultado[] = [
                    'key'           => $f['key'],
                    'destFile'      => $destPath,
                    'original_name' => $f['original_name'],
                    'size'          => $f['size'],
                    'mime'          => $f['mime']
                ];
            }

            return $resultado;
        } finally {
            $zip->close();
        }
    }

    /**
     * Mapea inteligentemente las fotos según el nombre del archivo o su orden secuencial
     */
    private function mapearFotos(array $fotos, string $context): array
    {
        $mapeadas = [];

        if ($context === 'entrega_destino') {
            // Contexto de entrega final en destino (Remisión y Firma)
            $slots = [
                'evidencia_remision' => null,
                'evidencia_firma'    => null
            ];
            $extras = [];

            foreach ($fotos as $foto) {
                $nombreLower = strtolower($foto['original_name']);
                if ($slots['evidencia_remision'] === null && (
                    str_contains($nombreLower, 'remis') ||
                    str_contains($nombreLower, 'acuse') ||
                    str_contains($nombreLower, 'guia')  ||
                    str_contains($nombreLower, 'factura') ||
                    str_contains($nombreLower, 'doc')
                )) {
                    $slots['evidencia_remision'] = $foto;
                } elseif ($slots['evidencia_firma'] === null && (
                    str_contains($nombreLower, 'firm') ||
                    str_contains($nombreLower, 'recib') ||
                    str_contains($nombreLower, 'recept') ||
                    str_contains($nombreLower, 'client')
                )) {
                    $slots['evidencia_firma'] = $foto;
                } else {
                    $extras[] = $foto;
                }
            }

            // Asignar fotos restantes a los slots pendientes
            foreach ($extras as $idx => $foto) {
                if ($slots['evidencia_remision'] === null) {
                    $slots['evidencia_remision'] = $foto;
                    unset($extras[$idx]);
                } elseif ($slots['evidencia_firma'] === null) {
                    $slots['evidencia_firma'] = $foto;
                    unset($extras[$idx]);
                    break;
                }
            }

            if ($slots['evidencia_remision'] !== null) {
                $slot = $slots['evidencia_remision'];
                $slot['key'] = 'evidencia_remision';
                $mapeadas[] = $slot;
            }
            if ($slots['evidencia_firma'] !== null) {
                $slot = $slots['evidencia_firma'];
                $slot['key'] = 'evidencia_firma';
                $mapeadas[] = $slot;
            }

            $extraCount = 1;
            foreach ($extras as $foto) {
                $foto['key'] = 'extra_' . $extraCount++;
                $mapeadas[] = $foto;
            }

            return $mapeadas;
        }

        // Contexto estándar de Inspección (5 Puntos: frente, atras, lateral_izq, lateral_der, odometro)
        $slots = [
            'frente'      => null,
            'atras'       => null,
            'lateral_izq' => null,
            'lateral_der' => null,
            'odometro'    => null
        ];
        $pendientes = [];

        foreach ($fotos as $foto) {
            $nombreLower = strtolower($foto['original_name']);
            $asignado = false;

            // Palabras clave directas
            if ($slots['frente'] === null && (
                str_contains($nombreLower, 'frente') ||
                str_contains($nombreLower, 'front')  ||
                str_contains($nombreLower, 'delant') ||
                str_contains($nombreLower, 'cara')
            )) {
                $slots['frente'] = $foto;
                $asignado = true;
            } elseif ($slots['atras'] === null && (
                str_contains($nombreLower, 'atras')   ||
                str_contains($nombreLower, 'traser')  ||
                str_contains($nombreLower, 'back')    ||
                str_contains($nombreLower, 'post')    ||
                str_contains($nombreLower, 'cola')
            )) {
                $slots['atras'] = $foto;
                $asignado = true;
            } elseif ($slots['lateral_izq'] === null && (
                str_contains($nombreLower, 'izq')  ||
                str_contains($nombreLower, 'left') ||
                str_contains($nombreLower, 'conduct')
            )) {
                $slots['lateral_izq'] = $foto;
                $asignado = true;
            } elseif ($slots['lateral_der'] === null && (
                str_contains($nombreLower, 'der')   ||
                str_contains($nombreLower, 'right') ||
                str_contains($nombreLower, 'copil')
            )) {
                $slots['lateral_der'] = $foto;
                $asignado = true;
            } elseif ($slots['odometro'] === null && (
                str_contains($nombreLower, 'odomet') ||
                str_contains($nombreLower, 'tabler') ||
                str_contains($nombreLower, 'km')     ||
                str_contains($nombreLower, 'kilo')
            )) {
                $slots['odometro'] = $foto;
                $asignado = true;
            }

            if (!$asignado) {
                $pendientes[] = $foto;
            }
        }

        // Asignación secuencial a los slots vacíos
        $clavesSlots = ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'];
        foreach ($clavesSlots as $clave) {
            if ($slots[$clave] === null && !empty($pendientes)) {
                $slots[$clave] = array_shift($pendientes);
            }
        }

        // Consolidar fotos mapeadas de los 5 puntos
        foreach ($clavesSlots as $clave) {
            if ($slots[$clave] !== null) {
                $foto = $slots[$clave];
                $foto['key'] = $clave;
                $mapeadas[] = $foto;
            }
        }

        // Fotos excedentes pasan a extras
        $extraIndex = 1;
        foreach ($pendientes as $foto) {
            $foto['key'] = 'extra_' . $extraIndex++;
            $mapeadas[] = $foto;
        }

        return $mapeadas;
    }

    /**
     * Asegura la creación de la carpeta destino y archivo de seguridad .htaccess
     */
    private function asegurarDirectorioProtegido(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (!is_writable($dir)) {
            @chmod($dir, 0777);
        }

        $htaccess = rtrim($dir, '/') . '/.htaccess';
        if (!file_exists($htaccess)) {
            $content = "# Bloqueo de ejecucion de scripts en carpeta de uploads\n"
                     . "<FilesMatch \"\\.(php|phtml|php3|php4|php5|php7|php8|phps|pl|py|cgi|sh|bash)$\">\n"
                     . "    Require all denied\n"
                     . "</FilesMatch>\n"
                     . "Options -Indexes -ExecCGI\n";
            @file_put_contents($htaccess, $content);
        }
    }
}

