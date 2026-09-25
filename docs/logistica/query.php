<?php
require_once("Config/Config.php");
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
$pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT * FROM cli_clientes WHERE nombre LIKE '%YUNYI%' OR nombre LIKE '%CASANOVA%' OR nombre LIKE '%XIAN%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM lgs_ubicaciones WHERE nombre LIKE '%YUNYI%' OR nombre LIKE '%CASANOVA%' OR nombre LIKE '%XIAN%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
