<?php
require "Config/Config.php";
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $stmt = $pdo->query("SELECT id_nodo, orden, nombre, tipo_nodo FROM lgs_envios_nodos n LEFT JOIN lgs_cat_ubicaciones u ON n.id_ubicacion = u.id_ubicacion WHERE id_envio = 2");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
