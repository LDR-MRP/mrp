<?php
// Archivo simple para guardar bancos sin routing complejo
error_log('[guardar_banco.php] INICIO - Archivo cargado');

try {
    session_start();
    header('Content-Type: application/json');
    error_log('[guardar_banco.php] Session iniciada');

    // Leer JSON del body
    $body = file_get_contents('php://input');
    $data = json_decode($body, true) ?? [];

    error_log('[guardar_banco.php] Datos recibidos: ' . json_encode($data));

    // Validación manual
    if (empty($data['nombre_corto'])) {
        echo json_encode(['status' => false, 'error' => 'El nombre del banco es obligatorio']);
        exit;
    }
    if (strlen($data['nombre_corto']) < 3) {
        echo json_encode(['status' => false, 'error' => 'El nombre del banco debe tener al menos 3 caracteres']);
        exit;
    }
    if (empty($data['razon_social'])) {
        echo json_encode(['status' => false, 'error' => 'La razón social es obligatoria']);
        exit;
    }

    error_log('[guardar_banco.php] Validación pasada');

    // Conexión directa a base de datos sin usar el framework
    require_once(__DIR__ . '/../Config/Config_local.php');
    
    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASSWORD;
    $db   = DB_NAME;

    error_log('[guardar_banco.php] Config DB cargada: ' . $db);

    $conn = new mysqli($host, $user, $pass, $db);

    if ($conn->connect_error) {
        error_log('[guardar_banco.php] Error de conexión: ' . $conn->connect_error);
        echo json_encode(['status' => false, 'error' => 'Error de conexión a la base de datos']);
        exit;
    }

    error_log('[guardar_banco.php] Conexión exitosa');

    // Generar ID con nomenclatura X0000N
    $result = $conn->query("SELECT MAX(CAST(SUBSTRING(id_banco, 2) AS UNSIGNED)) as max_id FROM cat_bancos WHERE id_banco LIKE 'X%'");
    $row = $result->fetch_assoc();
    $nextIdNum = ($row['max_id'] ?? 0) + 1;
    $nextId = 'X' . str_pad((string)$nextIdNum, 5, '0', STR_PAD_LEFT);

    error_log('[guardar_banco.php] ID generado: ' . $nextId);

    // Insertar banco
    $origenRegistro = $data['origen_registro'] ?? 'USUARIO';
    $stmt = $conn->prepare("INSERT INTO cat_bancos (id_banco, nombre_corto, razon_social, origen_registro, estatus) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $nextId, $data['nombre_corto'], $data['razon_social'], $origenRegistro);

    if ($stmt->execute()) {
        error_log('[guardar_banco.php] Banco creado exitosamente');
        echo json_encode([
            'status' => 'success',
            'code' => 201,
            'message' => 'Banco registrado exitosamente',
            'data' => ['id_banco' => $nextId]
        ]);
    } else {
        error_log('[guardar_banco.php] Error al insertar: ' . $stmt->error);
        echo json_encode(['status' => false, 'error' => 'Error al insertar: ' . $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} catch (\Exception $e) {
    error_log('[guardar_banco.php] ERROR: ' . $e->getMessage());
    echo json_encode(['status' => false, 'error' => $e->getMessage()]);
}
