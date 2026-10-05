<?php
require 'Config/Config.php';
require 'Libraries/Core/Conexion.php';
$con = new Conexion();
$db = $con->conect();
$sql = "SELECT e.id_envio FROM lgs_envios e LEFT JOIN prv_cat_proveedores pr ON e.id_proveedor = pr.id_proveedor LEFT JOIN lgs_cat_origenes o ON e.id_origen = o.id_origen LEFT JOIN cli_clientes c ON e.id_destino = c.idcliente LEFT JOIN lgs_cat_destinos d ON e.id_destino = d.id_destino WHERE e.id_envio = 47 AND e.deleted_at IS NULL";
$stmt = $db->prepare($sql);
$stmt->execute();
var_dump($stmt->fetch(PDO::FETCH_ASSOC));
var_dump($stmt->errorInfo());
