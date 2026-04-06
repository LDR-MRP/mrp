<?php

trait Loggable
{
    /**
     * Escribe una entrada de log estilo Laravel en un archivo de rotación diaria.
     *
     * @param string|Throwable $message Excepción o mensaje personalizado.
     * @param LogLevel $level Nivel de severidad (Default: ERROR).
     * @param array $context Datos adicionales útiles para debugging.
     */
    protected function logMessage(
        string|Throwable $message,
        LogLevel $level = LogLevel::ERROR,
        array $context =[]
    ): void {
        // Definir el path base. Se asume que el trait está en mrp/Libraries/Core/Traits
        $logDir = dirname(__DIR__, 2) . '/Logs';
        
        // [DevSecOps] Permisos 0750: Solo el owner (www-data) y el grupo pueden leer/escribir.
        if (!is_dir($logDir) && !mkdir($logDir, 0750, true) && !is_dir($logDir)) {
            // Falla silenciosa o manejo de error a nivel de OS si no se puede crear el directorio
            return;
        }

        $date = date('Y-m-d');
        $logFile = sprintf('%s/mrp-%s.log', $logDir, $date);
        $timestamp = date('Y-m-d H:i:s');
        
        // Extraer datos si es una Excepción
        if ($message instanceof Throwable) {
            $logText = $message->getMessage();
            $context['file'] = $message->getFile();
            $context['line'] = $message->getLine();
            
            // Limitamos el stacktrace a errores críticos para evitar Log Bloating
            if (in_array($level,[LogLevel::CRITICAL, LogLevel::EMERGENCY])) {
                $context['trace'] = $message->getTraceAsString();
            }
        } else {
            // [DevSecOps] Prevención de Log Forging (CRLF Injection) sanitizando saltos de línea
            $logText = str_replace(["\r", "\n"], ' ', $message);
        }

        $contextJson = empty($context) 
            ? '' 
            : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $environment = $_ENV['APP_ENV'] ?? 'local';

        // Formato estándar Monolog/Laravel: [YYYY-MM-DD HH:MM:SS] env.LEVEL: Message {"context"}
        $logEntry = sprintf(
            "[%s] %s.%s: %s %s%s",
            $timestamp,
            $environment,
            $level->value,
            $logText,
            $contextJson,
            PHP_EOL
        );

        // [Arquitectura] LOCK_EX previene race conditions cuando múltiples peticiones escriben al mismo log
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
}