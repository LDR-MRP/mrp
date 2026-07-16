<?php

/**
 * CLI Entry Point para Migración Masiva de Contraseñas
 * Ejecución: php cli_hash_passwords.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso denegado.\n");
}

// Carga las configuraciones base de tu framework
require_once __DIR__ . '/Config/Config_dev.php';
require_once __DIR__ . '/Helpers/Helpers.php';

// 1. Cargamos el Autoloader de Composer (para que encuentre la clase Mysql)
require_once __DIR__ . '/vendor/autoload.php';

// 2. FORZAMOS la carga del script temporal saltándonos el PSR-4
require_once __DIR__ . '/Scripts/MassPasswordHasher.php';

use Scripts\MassPasswordHasher;

echo "=================================================\n";
echo " INICIANDO MIGRACIÓN A BCRYPT (PHP 8.3) \n";
echo "=================================================\n";

try {
    $hasher = new MassPasswordHasher();
    $hasher->runMigration();
} catch (\Exception $e) {
    echo "[ERROR CRÍTICO] " . $e->getMessage() . "\n";
}

echo "=================================================\n";
echo " PROCESO FINALIZADO \n";
echo "=================================================\n";