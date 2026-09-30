<?php
require 'Config/Config.php';
$db = new PDO("mysql:host=localhost;dbname=mrp;charset=utf8", "mrp", "mrp");
$stmt = $db->query("SELECT id_proveedor, nombre_comercial FROM lgs_cat_proveedores");
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['id_proveedor'] . " | " . $row['nombre_comercial'] . "\n";
}
