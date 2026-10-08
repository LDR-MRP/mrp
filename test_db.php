<?php
$host = "localhost";
$user = "u546825723_mrpuser";
$password = "L=9xlH6~e";
$dbname = "u546825723_dbmrp";
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$sql = "SELECT idmodulo, titulo, descripcion, status FROM modulo ORDER BY idmodulo ASC";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        echo "id: " . $row["idmodulo"]. " - Titulo: " . $row["titulo"]. " - Descripcion: " . $row["descripcion"]. "\n";
    }
} else {
    echo "0 results";
}
$conn->close();
?>
