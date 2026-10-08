<?php
require_once __DIR__ . '/../../../Config/Config_local.php';
require_once __DIR__ . '/../../../Libraries/Core/Conexion.php';
require_once __DIR__ . '/../../../Libraries/Core/Mysql.php';
require_once __DIR__ . '/../../../Libraries/Core/Auditable.php';
require_once __DIR__ . '/../../../Models/Lgs_enviosModel.php';
require_once __DIR__ . '/../../../Services/Lgs_enviosService.php';

$dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
if (file_exists('/.dockerenv') && $dbHost === 'localhost') {
    $dbHost = 'mrp-db';
}

try {
    $db = new PDO("mysql:host=" . $dbHost . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Obtener todos los envíos que NO están cancelados (deleted_at IS NULL)
    // También podemos procesar todos
    $stmt = $db->query("SELECT id_envio, costo_total, is_lowboy FROM lgs_envios WHERE deleted_at IS NULL");
    $envios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Encontrados " . count($envios) . " envíos activos.\n";

    $service = new Lgs_enviosService();

    // 2. Poner costos actuales a 0.00 antes del recalculo en la base real 
    $db->exec("UPDATE lgs_envios SET costo_total = 0");
    $db->exec("UPDATE lgs_envios_tramos_costos SET costo_estimado = 0");
    $db->exec("UPDATE lgs_envios_vins SET costo_unidad = 0");

    echo "Costos inicializados a 0.\n";

    // 3. Recalcular cada uno
    $count = 0;
    foreach ($envios as $e) {
        try {
            // Desbloquear id_estado=7 para forzar recálculo
            $db->prepare("UPDATE lgs_envios SET id_estado = 1 WHERE id_envio = ? AND id_estado = 7")->execute([$e['id_envio']]);
            
            $service->recalcularCostoTotal((int)$e['id_envio'], $db);
            $count++;
        } catch (Exception $ex) {
            echo "Error en envio " . $e['id_envio'] . ": " . $ex->getMessage() . "\n";
        }
    }

    echo "Recalculados $count envíos.\n";

} catch (Exception $e) {
    echo "Error general: " . $e->getMessage() . "\n";
}
