<?php
// Detectar entorno para cargar la configuración adecuada
if (file_exists(__DIR__ . '/Config/Config_local.php') && (php_sapi_name() === 'cli' || ($_SERVER['HTTP_HOST'] ?? 'localhost') === 'localhost')) {
    require_once __DIR__ . '/Config/Config_local.php';
    $host = (DB_HOST === 'mrp-db' && php_sapi_name() === 'cli' && gethostbyname('mrp-db') === 'mrp-db') ? '127.0.0.1' : DB_HOST;
    $dbName = DB_NAME;
    $dbUser = DB_USER;
    $dbPass = DB_PASSWORD;
} else {
    require_once __DIR__ . '/Config/Config.php';
    $host = DB_HOST;
    $dbName = DB_NAME;
    $dbUser = DB_USER;
    $dbPass = DB_PASSWORD;
}

try {
    $dsn = "mysql:host=" . $host . ";dbname=" . $dbName . ";charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 5
    ]);

    $sqlFile = __DIR__ . '/docs_desarrollo_logistica/logistica/1_DDL_MIGRACION_LOGISTICA.sql';
    $sql = file_get_contents($sqlFile);
    
    if ($sql === false) {
        die("Error reading SQL file.\n");
    }

    $pdo->exec($sql);
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Connection or execution failed: " . $e->getMessage() . "\n";
}
