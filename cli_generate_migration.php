<?php

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde la consola (CLI).\n");
}

// Cargar el entorno del framework
require_once __DIR__ . '/Config/Config.php';
require_once __DIR__ . '/Helpers/Helpers.php';
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Scripts/MassSupplierMigrator.php';

use Scripts\MassSupplierMigrator;

$csvFile = __DIR__ . '/proveedores.csv';
$outputFile = __DIR__ . '/srm_suppliers_migration.sql';

if (!file_exists($csvFile)) {
    die("Error: No se encontró el archivo 'proveedores.csv' en este directorio.\n");
}

echo "Leyendo archivo original...\n";
$csvContent = file_get_contents($csvFile);

// A. Solucionar choque de codificaciones (Windows-1252 a UTF-8) [154]
if (!mb_check_encoding($csvContent, 'UTF-8')) {
    echo "Codificación Windows-1252 detectada. Convirtiendo a UTF-8 de forma segura...\n";
    $csvContent = mb_convert_encoding($csvContent, 'UTF-8', 'Windows-1252');
}

// B. Solucionar desmadre de saltos de línea internos de Excel (Regex DOTALL)
echo "Aplanando saltos de línea internos de Excel...\n";
$csvContent = preg_replace_callback('/"([^"]*)"/s', function($matches) {
    return '"' . str_replace(["\r", "\n"], " ", $matches[1]) . '"';
}, $csvContent);

// C. Abrir flujo de memoria temporal
$memHandle = fopen('php://temp', 'r+');
fwrite($memHandle, $csvContent);
rewind($memHandle);

// Inicializar cabecera del archivo SQL consolidado
$sqlContent = "-- ==============================================================================\n";
$sqlContent .= "-- SCRIPT GENERADO AUTOMÁTICAMENTE PARA MIGRACIÓN MASIVA DE PROVEEDORES\n";
$sqlContent .= "-- ECOSISTEMA: SRM & ERP LDR SOLUTIONS\n";
$sqlContent .= "-- GENERADO EL: " . date('Y-m-d H:i:s') . "\n";
$sqlContent .= "-- ==============================================================================\n\n";
$sqlContent .= "START TRANSACTION;\n\n";
$sqlContent .= "SET @admin_id = 1;\n\n";

$migrator = new MassSupplierMigrator();

// Omitir cabecera del CSV
fgetcsv($memHandle, 0, ',');

$count = 0;
$skipped = 0;
$emailsProcesados = [];
$clabesProcesadas = [];

/**
 * Limpia cadenas de texto colapsando múltiples espacios en uno solo.
 */
function cleanString(string $val): string {
    $cleaned = preg_replace('/\s+/', ' ', $val);
    return trim($cleaned);
}

/**
 * Sanitiza y escapa strings para evitar errores de sintaxis SQL.
 * Convierte cualquier comilla simple ' en '' (estándar SQL para escapar).
 */

function escapeSql(string $val): string {
    // 1. Limpiamos espacios y saltos de línea basura
    $cleaned = preg_replace('/\s+/', ' ', $val);
    $cleaned = trim($cleaned);
    // 2. Escapamos comillas simples duplicándolas (ej: O'Reilly -> O''Reilly)
    return str_replace("'", "''", $cleaned);
}

/**
 * SANITIZADOR DE ÉLITE: Cura y rescata las cuentas bancarias.
 * Extrae SWIFT/IBAN si vienen mezclados, remueve prefijos y normaliza CLABEs a dígitos.
 */
function sanitizeClabeAndAccount(string $rawClabe, string $rawAccount): array {
    $rawClabe = trim($rawClabe);
    $rawAccount = trim($rawAccount);

    $clabe = null;
    $cuenta = null;
    $swift = null;
    $iban = null;
    $idBanco = null;

    // Caso de Borde: Cuenta Domiciliada o vacía
    if (empty($rawClabe) || strtolower($rawClabe) === 'domiciliado') {
        return [
            'clabe' => null, 'cuenta' => $rawAccount ?: null, 
            'swift_bic' => null, 'iban' => null, 'id_banco' => null
        ];
    }

    // Caso de Borde: Trae código SWIFT mezclado en la CLABE (Ej: "021000021 Swift CHASUS33")
    if (preg_match('/swift\s+([A-Z0-9]{8,11})/i', $rawClabe, $matches)) {
        $swift = strtoupper($matches[1]);
        // Limpiamos el fragmento del SWIFT de la CLABE original
        $rawClabe = trim(preg_replace('/swift\s+[A-Z0-9]{8,11}/i', '', $rawClabe));
    }

    // Caso de Borde: Trae prefijos de captura humana (clabe:, cable:, clave:, etc.)
    $cleanClabe = preg_replace('/^(clabe|cable|clave|cuenta|num_cuenta)\s*:\s*/i', '', $rawClabe);
    
    // Remover caracteres de ruido al inicio (Puntos, dos puntos, espacios)
    $cleanClabe = ltrim($cleanClabe, '.:- ');

    // Remover todos los espacios en blanco intermedios (Ej: 072 180 ... -> 072180...)
    $cleanClabe = str_replace(' ', '', $cleanClabe);

    // Extraer únicamente los dígitos numéricos para la CLABE
    $numericClabe = preg_replace('/[^0-9]/', '', $cleanClabe);

    // Si mide entre 15 y 18 dígitos, es una CLABE nacional válida
    if (strlen($numericClabe) >= 15 && strlen($numericClabe) <= 18) {
        $clabe = $numericClabe;
        $idBanco = substr($clabe, 0, 3); // Extraemos los 3 dígitos del banco
        $cuenta = !empty($rawAccount) ? preg_replace('/[^0-9]/', '', $rawAccount) : substr($clabe, -11);
    } else {
        // Si no es CLABE, podría ser un IBAN internacional
        if (preg_match('/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/i', $cleanClabe)) {
            $iban = strtoupper($cleanClabe);
        } else {
            // Caída por defecto: Guardar como cuenta plana
            $cuenta = !empty($rawAccount) ? preg_replace('/[^0-9]/', '', $rawAccount) : $numericClabe;
        }
    }

    return [
        'clabe' => $clabe,
        'cuenta' => $cuenta ?: null,
        'swift_bic' => $swift,
        'iban' => $iban,
        'id_banco' => $idBanco
    ];
}


while (($row = fgetcsv($memHandle, 0, ',')) !== false) {
    if (count($row) < 21) {
        continue; 
    }

    $razonSocial = escapeSql(cleanString($row[1]));
    $rfc = strtoupper(cleanString($row[15]));

    if (strlen($rfc) > 13 || strlen($rfc) < 12) {
        echo "\033[1;33m[SALTADO]\033[0m: '{$razonSocial}' (RFC {$rfc} no válido).\n";
        $skipped++;
        continue; 
    }

    // Prevención de duplicados en tiempo de compilación
    if ($migrator->rfcExists($rfc)) {
        echo "\033[1;33m[SALTADO]\033[0m: '{$razonSocial}' (RFC {$rfc} ya se encuentra registrado y activo).\n";
        $skipped++;
        continue; 
    }

    $calle = escapeSql(cleanString($row[3]));
    $colonia = escapeSql(cleanString($row[4]));
    
    $numExt = cleanString($row[5]);
    $numExtSql = ($numExt === '' || strtolower($numExt) === 'n/a') ? "NULL" : "'" . escapeSql($numExt) . "'";
    
    $numInt = cleanString($row[6]);
    $numIntSql = ($numInt === '' || strtolower($numInt) === 'n/a') ? "NULL" : "'" . escapeSql($numInt) . "'";
    
    $cp = cleanString($row[7]);
    $ciudad = escapeSql(cleanString($row[8]));
    $estado = escapeSql(cleanString($row[9]));
    $telefono = escapeSql(cleanString($row[10]));
    
    // Limpieza de números financieros
    $limiteCredito = (float) str_replace([',', ' '], '', cleanString($row[11]));
    $plazoCredito = (int) cleanString($row[12]);
    
    // --- CURACIÓN BANCARIA AVANZADA ---
    // 2. DEDUPLICACIÓN DE CLABE (Tanto en archivo como en BD)
    $bankDetails = sanitizeClabeAndAccount($row[14], $row[13]);
    $clabe = $bankDetails['clabe'];

    if ($clabe) {
        // ¿Ya la procesamos en este archivo?
        if (in_array($clabe, $clabesProcesadas)) {
            echo "\033[1;33m[SALTADO]\033[0m: '{$razonSocial}' (CLABE {$clabe} duplicada en archivo).\n";
            $skipped++; continue;
        }
        // ¿Ya existe en la base de datos de Hostinger?
        if ($migrator->clabeExists($clabe)) {
            echo "\033[1;33m[SALTADO]\033[0m: '{$razonSocial}' (CLABE {$clabe} ya registrada en BD).\n";
            $skipped++; continue;
        }
        $clabesProcesadas[] = $clabe;
    }
   

    $email = escapeSql(strtolower(cleanString($row[16])));

    // --- NUEVA LÓGICA DE DEDUPLICACIÓN DE CORREOS ---
    if (!$email) {
        echo "\033[1;31m[SALTADO]\033[0m: '{$razonSocial}' (Sin correo).\n";
        $skipped++;
        continue;
    }

    // --- NUEVA LÓGICA DE DEDUPLICACIÓN DE CORREOS ---
    if (in_array($email, $emailsProcesados)) {
        echo "\033[1;31m[SALTADO]\033[0m: '{$razonSocial}' (Correo duplicado: {$email}).\n";
        $skipped++;
        continue;
    }
    $emailsProcesados[] = $email;
    // --- FIN DE LÓGICA ---

    $colaboradorEmails = cleanString($row[19]);
    
    // Encriptar contraseña en caliente con BCRYPT
    $passwordPlana = cleanString($row[20]);
    $passwordHash = password_hash($passwordPlana, PASSWORD_BCRYPT);

    $sqlContent .= "-- ------------------------------------------------------------------------------\n";
    $sqlContent .= "-- PROVEEDOR " . ($count + 1) . ": {$razonSocial}\n";
    $sqlContent .= "-- ------------------------------------------------------------------------------\n";
    
    $sqlContent .= "SET @raw_emails = '{$colaboradorEmails}';\n";
    $sqlContent .= "SET @first_email = TRIM(SUBSTRING_INDEX(@raw_emails, ',', 1));\n";
    $sqlContent .= "SET @rfc = '{$rfc}';\n";
    $sqlContent .= "SET @tipo_persona = IF(CHAR_LENGTH(TRIM(@rfc)) = 12, 'M', 'F');\n";
    $sqlContent .= "SET @regimen_fiscal = IF(CHAR_LENGTH(TRIM(@rfc)) = 12, 601, 612);\n\n";

    $sqlContent .= "SELECT idusuario, COALESCE(plantaid, 50) INTO @resolved_user_id, @resolved_planta_id\n";
    $sqlContent .= "FROM (SELECT idusuario, plantaid, 1 AS prioridad FROM usuarios WHERE email_user = @first_email COLLATE utf8mb4_swedish_ci AND status = 1\n";
    $sqlContent .= "UNION ALL SELECT idusuario, plantaid, 2 AS prioridad FROM usuarios WHERE idusuario = 67) AS res ORDER BY prioridad ASC LIMIT 1;\n\n";

    $sqlContent .= "INSERT INTO `prv_cat_proveedores` (id_empresa, id_planta, rfc, razon_social, nombre_comercial, id_tipo_persona, id_regimen_fiscal, tipo, origen, estatus_onboarding, estatus_operativo, created_by) \n";
    $sqlContent .= "VALUES (1, @resolved_planta_id, @rfc, '{$razonSocial}', '{$razonSocial}', @tipo_persona, @regimen_fiscal, 'Externo', 'Nacional', 'Prospecto', 0, @resolved_user_id);\n";
    $sqlContent .= "SET @last_prov_id = LAST_INSERT_ID();\n\n";

    $sqlContent .= "INSERT INTO `prv_cat_usuarios` (proveedor_id, email, password, nombre_contacto, estatus, created_by) \n";
    $sqlContent .= "VALUES (@last_prov_id, '{$email}', '{$passwordHash}', '{$razonSocial}', 'ACTIVE', @resolved_user_id);\n\n";

    $sqlContent .= "INSERT INTO `prv_det_direcciones` (id_proveedor, calle, num_ext, num_int, colonia, cp, ciudad, municipio, estado, tipo, es_principal, created_by) \n";
    $sqlContent .= "VALUES (@last_prov_id, '{$calle}', {$numExtSql}, {$numIntSql}, '{$colonia}', '{$cp}', '{$ciudad}', '{$ciudad}', '{$estado}', 'Fiscal', 1, @resolved_user_id);\n\n";

    $sqlContent .= "INSERT INTO `prv_det_contactos` (id_proveedor, nombre, email, telefono, puesto, notificar_compras, created_by) \n";
    $sqlContent .= "VALUES (@last_prov_id, '{$razonSocial}', '{$email}', '{$telefono}', 'Ventas / Contacto Principal', 1, @resolved_user_id);\n\n";

    // --- INSERCIÓN BANCARIA CURADA Y SANITIZADA (Same-Site spei ready) ---
    // Solo generamos el bloque si el sanitizador rescató alguna cuenta o clabe válida [4]
    if ($bankDetails['clabe'] || $bankDetails['cuenta'] || $bankDetails['iban']) {
        
        $clabeSql  = $bankDetails['clabe'] ? "'{$bankDetails['clabe']}'" : "NULL";
        $cuentaSql = $bankDetails['cuenta'] ? "'{$bankDetails['cuenta']}'" : "NULL";
        $swiftSql  = $bankDetails['swift_bic'] ? "'{$bankDetails['swift_bic']}'" : "NULL";
        $ibanSql   = $bankDetails['iban'] ? "'{$bankDetails['iban']}'" : "NULL";

        if ($bankDetails['id_banco']) {
            // Si hay banco detectado, lo resolvemos de forma síncrona contra cat_bancos [150]
            $sqlContent .= "SET @target_bank_code = '{$bankDetails['id_banco']}';\n";
            $sqlContent .= "SET @resolved_bank_id = NULL;\n";
            $sqlContent .= "SELECT id_banco INTO @resolved_bank_id FROM cat_bancos WHERE id_banco = @target_bank_code LIMIT 1;\n\n";
            $bancoSql = "@resolved_bank_id";
        } else {
            $bancoSql = "NULL";
        }

        $sqlContent .= "INSERT INTO `prv_det_cuentas_bancarias` (id_proveedor, id_banco, id_moneda, cuenta, clabe, swift_bic, iban, es_principal, estatus_aprobacion, created_by) \n";
        $sqlContent .= "VALUES (@last_prov_id, {$bancoSql}, 'MXN', {$cuentaSql}, {$clabeSql}, {$swiftSql}, {$ibanSql}, 1, 'PENDIENTE', @resolved_user_id);\n\n";
    } else {
        $sqlContent .= "-- Nota: Este proveedor no tiene cuenta bancaria válida registrada en el Excel.\n\n";
    }

    $sqlContent .= "SET @resolved_condicion_pago = NULL;\n";
    $sqlContent .= "SELECT id_condicion FROM cat_condiciones_pago WHERE dias_credito = {$plazoCredito} AND estatus = 1 LIMIT 1;\n";
    $sqlContent .= "SET @resolved_condicion_pago = COALESCE(@resolved_condicion_pago, 1);\n\n";

    $sqlContent .= "INSERT INTO `prv_det_config_financiera` (id_proveedor, cuenta_contable, id_condicion_pago, limite_credito, id_moneda_defecto, tasa_iva_default, created_by) \n";
    $sqlContent .= "VALUES (@last_prov_id, '00-00-0001', @resolved_condicion_pago, {$limiteCredito}, 'MXN', 16.00, @resolved_user_id);\n\n";

    $sqlContent .= "INSERT INTO `log_audit` (resourceid, nombre_tabla, accion, comentario, usuarioid) \n";
    $sqlContent .= "VALUES (@last_prov_id, 'prv_cat_proveedores', 'creacion', 'Carga masiva de datos maestros mediante script automatizado de conciliación de identidad.', @resolved_user_id);\n\n\n";

    $count++;
}

fclose($memHandle);

$sqlContent .= "COMMIT;\n";

file_put_contents($outputFile, $sqlContent);

echo "=================================================\n";
echo " GENERACIÓN DE MIGRACIÓN COMPLETA \n";
echo "=================================================\n";
echo "Se procesaron exitosamente: {$count} nuevos proveedores.\n";
echo "Se saltaron por duplicidad:  {$skipped} proveedores existentes.\n";
echo "Archivo generado con curación de CLABEs: 'srm_suppliers_migration.sql'\n";
echo "=================================================\n";