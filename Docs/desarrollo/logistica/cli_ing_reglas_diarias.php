<?php

/**
 * CLI Entry Point — Tarea programada diaria del motor de reglas de Ingeniería.
 * Reevalúa todas las configuraciones de vehículo en EN_REVISION, AUTORIZADO
 * o BLOQUEADO: autoriza las que ya cumplen todas las reglas, bloquea las
 * autorizadas que dejaron de cumplirlas (p.ej. venció una certificación) y
 * marca en corrección las que estaban en revisión y no cumplen.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Acceso denegado.\n");
}

if (file_exists(__DIR__ . '/Config/Config_local.php')) {
    require_once __DIR__ . '/Config/Config_local.php';
} else {
    require_once __DIR__ . '/Config/Config.php';
}
require_once __DIR__ . '/Helpers/Helpers.php';

if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}
require_once __DIR__ . '/Libraries/Core/Autoload.php';

echo "=================================================\n";
echo " MOTOR DE REGLAS DE INGENIERÍA - " . date('Y-m-d H:i:s') . "\n";
echo "=================================================\n";

try {
    $service = new Ing_reglasService();
    $resumen = $service->evaluarTodas('TAREA_PROGRAMADA');

    echo "Configuraciones evaluadas: " . $resumen['total'] . "\n";
    echo "  Autorizadas automáticamente: " . $resumen['autorizadas'] . "\n";
    echo "  Bloqueadas automáticamente:  " . $resumen['bloqueadas'] . "\n";
    echo "  Enviadas a corrección:       " . $resumen['correccion'] . "\n";
    echo "  Sin cambio:                  " . $resumen['sin_cambio'] . "\n";
} catch (\Exception $e) {
    echo "[ERROR CRÍTICO] " . $e->getMessage() . "\n";
}

echo "=================================================\n";
echo " PROCESO FINALIZADO \n";
echo "=================================================\n";
