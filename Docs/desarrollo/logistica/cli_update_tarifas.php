<?php
require_once __DIR__ . '/Config/Config.php';

$dbHost = defined('DB_HOST') ? DB_HOST : 'localhost';
if (file_exists('/.dockerenv') && $dbHost === 'localhost') {
    $dbHost = 'mrp-db';
}

try {
    $db = new PDO("mysql:host=" . $dbHost . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Auto-migrar columna si no existe
    try {
        $db->exec("ALTER TABLE `lgs_tarifas_proveedores` ADD COLUMN `es_personalizada` TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {}

    $db->beginTransaction();

    // Limpiar tarifas anteriores
    $db->exec("DELETE FROM lgs_tarifas_proveedores WHERE id_proveedor > 0");

    $proveedores = [
        'Logistica Teromo' => [
            'madrina' => [1=>45, 2=>45, 3=>35, 4=>28, 5=>25, 6=>22, 7=>20, 8=>20, 9=>20, 10=>20],
            'plataforma' => [1=>45, 2=>45, 3=>35, 4=>80],
        ],
        'Clinicar' => [
            'madrina' => [1=>40, 2=>40, 3=>26.66, 4=>17.00, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
            'plataforma' => [1=>45, 2=>40, 3=>26.66, 4=>80],
        ],
        'Traslados Sacbe' => [
            'madrina' => [1=>17, 2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13]
        ],
        'Jose Daniel Garcia Vazquez' => [
            'madrina' => [1=>17, 2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
            'plataforma' => [1=>45, 2=>25, 3=>17, 4=>80],
        ],
        'Automotive Translead' => [
            'madrina' => [1=>17, 2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
            'plataforma' => [1=>45, 2=>40, 3=>17, 4=>80],
        ],
        'SCX' => [
            'madrina' => [1=>23, 2=>23, 3=>23, 4=>20, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13]
        ],
        'Live Transfer' => [
            'plataforma' => [1=>45, 2=>45, 3=>45, 4=>80],
        ]
    ];

    $bases = [
        1 => 27.00, // Ligeros
        2 => 30.00, // Medianos
        3 => 40.00, // Pesados
        4 => 50.00, // Buses
        5 => 60.00  // Lowboy
    ];

    $sacbe_precios = [1=>17, 2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13];
    $sacbe_plataforma = [1=>45, 2=>40, 3=>26.6667, 4=>80]; // Default platform prices
    
    // Tarifas globales por defecto para Chofer (Rodando) según segmento
    $chofer_global = [
        1 => 18.00, // Ligeros
        2 => 20.00, // Medianos
        3 => 25.00, // Pesados
        4 => 25.00, // Buses
        5 => 25.00  // Lowboy (por defecto igual al más alto si aplica)
    ];

    $stmtUpdateBase = $db->prepare("UPDATE lgs_tarifas_proveedores SET costo_por_km = ? WHERE id_proveedor = 0 AND id_segmento = ?");
    $stmtIns = $db->prepare("INSERT INTO lgs_tarifas_proveedores (id_proveedor, id_tipo_traslado, id_segmento, num_vins_min, num_vins_max, costo_por_km, precio_plano, factor, es_personalizada) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)");

    $mantenerPersonalizadas = in_array('--mantener-personalizadas', $argv ?? []);

    // Limpiar tarifas: si se mantiene personalizadas, solo borramos las que NO son personalizadas (es_personalizada = 0)
    if ($mantenerPersonalizadas) {
        $db->exec("DELETE FROM lgs_tarifas_proveedores WHERE id_proveedor = 0 OR es_personalizada = 0");
    } else {
        $db->exec("DELETE FROM lgs_tarifas_proveedores WHERE id_proveedor >= 0");
    }

    $count = 0;
    
    // Insertar para id_proveedor = 0 (Base Universal)
    foreach ($bases as $id_seg => $base) {
        // Madrina
        foreach ($sacbe_precios as $u => $precio) {
            $factor = round($precio / $base, 4);
            $stmtIns->execute([0, 1, $id_seg, $u, $u, $base, $factor, 0]);
        }
        // Plataforma
        foreach ($sacbe_plataforma as $u => $precio) {
            $factor = round($precio / $base, 4);
            $stmtIns->execute([0, 3, $id_seg, $u, $u, $base, $factor, 0]);
        }
        // Chofer (Rodando: tarifa fija por km por unidad, factor 1.0000)
        if (isset($chofer_global[$id_seg])) {
            $precioC = $chofer_global[$id_seg];
            $stmtIns->execute([0, 2, $id_seg, 1, 1, $precioC, 1.0000, 0]);
        }
    }

    $stmtFind = $db->prepare("SELECT id_proveedor, razon_social, nombre_comercial FROM prv_cat_proveedores");
    $stmtFind->execute();
    $todos_proveedores = $stmtFind->fetchAll(PDO::FETCH_ASSOC);

    $stmtCheckPers = $db->prepare("SELECT COUNT(*) FROM lgs_tarifas_proveedores WHERE id_proveedor = ? AND es_personalizada = 1 AND activo != 0");
    
    foreach ($todos_proveedores as $prov) {
        $id_prov = (int)$prov['id_proveedor'];
        $nombre = strtoupper($prov['razon_social'] . ' ' . $prov['nombre_comercial']);
        if ($id_prov > 0) {
            if ($mantenerPersonalizadas) {
                $stmtCheckPers->execute([$id_prov]);
                if ((int)$stmtCheckPers->fetchColumn() > 0) {
                    continue; // Conservar tarifas manuales del proveedor
                }
            }

            // Buscar si tiene tarifa personalizada en el array
            $madrina_precios = $sacbe_precios;
            $plataforma_precios = $sacbe_plataforma;
            $chofer_precios = $chofer_global;

            foreach ($proveedores as $key => $datos) {
                if (strpos($nombre, strtoupper($key)) !== false) {
                    if (isset($datos['madrina'])) {
                        $madrina_precios = $datos['madrina'];
                    }
                    if (isset($datos['plataforma'])) {
                        $plataforma_precios = $datos['plataforma'];
                    }
                    if (isset($datos['chofer'])) {
                        $chofer_precios = $datos['chofer'];
                    }
                    break;
                }
            }

            foreach ($bases as $id_seg => $base) {
                // Madrina
                foreach ($madrina_precios as $u => $precio) {
                    $factor = round($precio / $base, 4);
                    $stmtIns->execute([$id_prov, 1, $id_seg, $u, $u, $base, $factor, 0]);
                    $count++;
                }
                // Plataforma
                foreach ($plataforma_precios as $u => $precio) {
                    $factor = round($precio / $base, 4);
                    $stmtIns->execute([$id_prov, 3, $id_seg, $u, $u, $base, $factor, 0]);
                    $count++;
                }
                // Chofer (Rodando: tarifa fija por km por unidad, factor 1.0000)
                if (isset($chofer_precios[$id_seg])) {
                    $precioC = $chofer_precios[$id_seg];
                    $stmtIns->execute([$id_prov, 2, $id_seg, 1, 1, $precioC, 1.0000, 0]);
                    $count++;
                }
            }
        }
    }

    // Asegurar factor 1.0000 en cualquier tarifa de chofer existente
    $db->exec("UPDATE lgs_tarifas_proveedores SET factor = 1.0000 WHERE id_tipo_traslado = 2");

    // =========================================================================
    // CATALOGACIÓN COMPLETA DE MODELOS Y SEGMENTOS (LIGEROS / PESADOS / ETC.)
    // =========================================================================
    $modelosCatalog = [
        // LIGEROS (Segmento 1)
        ['modelo' => 'S5-E6 MT', 'id_segmento' => 1, 'vin_base' => 'LVBV18'],
        ['modelo' => 'Aumark S5-E6', 'id_segmento' => 1, 'vin_base' => 'LVBV18'],
        ['modelo' => 'Aumark S5-E6-MT', 'id_segmento' => 1, 'vin_base' => 'LVBV18'],
        ['modelo' => 'S3-E6 MT', 'id_segmento' => 1, 'vin_base' => 'LVBV14'],
        ['modelo' => 'Tunland V7 gasolina 4X4', 'id_segmento' => 1, 'vin_base' => '3LDC2A2F-4X4'],
        ['modelo' => 'Tunland G7 4K22-DC', 'id_segmento' => 1, 'vin_base' => '3LDC2A2F-G7'],
        ['modelo' => 'Tunland V7 (MHEV)', 'id_segmento' => 1, 'vin_base' => '3LDC2B2F'],
        ['modelo' => 'Tunland V9 (MHEV)', 'id_segmento' => 1, 'vin_base' => '3LDC2B2F9'],
        ['modelo' => 'Tunland V7 gasolina 4X2', 'id_segmento' => 1, 'vin_base' => '3LDC2A2F-4X2'],
        ['modelo' => 'HiVan Pasajeros', 'id_segmento' => 1, 'vin_base' => '3LDA2B2F'],
        ['modelo' => 'TM3 1.6L', 'id_segmento' => 1, 'vin_base' => 'LVAV2JVB'],
        ['modelo' => 'Wonder DC', 'id_segmento' => 1, 'vin_base' => 'LVBV27-DC'],
        ['modelo' => 'VIEW CS2 Pasajeros', 'id_segmento' => 1, 'vin_base' => '3LDA2A2F'],
        ['modelo' => 'TUNLAND EV', 'id_segmento' => 1, 'vin_base' => 'LVBV36'],
        ['modelo' => 'Tunland G7 TM GS 4x2', 'id_segmento' => 1, 'vin_base' => '3LDC1A2F-TA'],
        ['modelo' => 'Tunland G7 MT Gasolina', 'id_segmento' => 1, 'vin_base' => '3LDC2A2FX'],
        ['modelo' => 'Tunland G7 4X4', 'id_segmento' => 1, 'vin_base' => '3LDC2A2F6'],
        ['modelo' => 'VIEW CS2-2501 Pasajeros', 'id_segmento' => 1, 'vin_base' => '3LDA2A2F'],
        ['modelo' => 'View CS2 Royal', 'id_segmento' => 1, 'vin_base' => '3LDA2A2F9'],
        ['modelo' => 'HiVan Panel', 'id_segmento' => 1, 'vin_base' => '3LDA2B2FX'],
        ['modelo' => 'HiVan Cargo', 'id_segmento' => 1, 'vin_base' => '3LDA2B2F3'],

        // PESADOS (Segmento 3)
        ['modelo' => 'GTL / 2491', 'id_segmento' => 3, 'vin_base' => 'LVBV76'],
        ['modelo' => 'EST-A 6X4', 'id_segmento' => 3, 'vin_base' => '3LD34B4J'],
        ['modelo' => 'EST-A 6X4 X13-E6', 'id_segmento' => 3, 'vin_base' => '3LD34B4J3'],
        ['modelo' => 'EST-S38 / AMT 6X4', 'id_segmento' => 3, 'vin_base' => '3LD24B3J'],
        ['modelo' => 'GALAXUS', 'id_segmento' => 3, 'vin_base' => 'LVBV74'],
        ['modelo' => 'Galaxy 3256', 'id_segmento' => 3, 'vin_base' => 'LVBV70'],

        // MEDIANOS (Segmento 2)
        ['modelo' => 'S8-E6 AMT', 'id_segmento' => 2, 'vin_base' => '3LD23B2J'],
    ];

    $stmtCheckMod = $db->prepare("SELECT id_cat_modelo_vin FROM cat_modelos_vin WHERE LOWER(TRIM(modelo)) = LOWER(TRIM(?)) LIMIT 1");
    $stmtUpdMod = $db->prepare("UPDATE cat_modelos_vin SET id_segmento = ?, vin_base = COALESCE(vin_base, ?) WHERE id_cat_modelo_vin = ?");
    $stmtInsMod = $db->prepare("INSERT INTO cat_modelos_vin (modelo, id_fabricante, id_tipo_vehiculo, peso_bruto_kg, id_tipo_motor, potencia_hp, distancia_ejes, id_cat_anio_vin, id_planta, id_segmento, vin_base, fecha_creacion, estado) VALUES (?, 1, 1, 12000, 1, 350, 4500, 1, 1, ?, ?, NOW(), 2)");

    $modsActualizados = 0;
    foreach ($modelosCatalog as $m) {
        $stmtCheckMod->execute([$m['modelo']]);
        $existingId = $stmtCheckMod->fetchColumn();
        if ($existingId) {
            $stmtUpdMod->execute([$m['id_segmento'], $m['vin_base'], $existingId]);
        } else {
            $stmtInsMod->execute([$m['modelo'], $m['id_segmento'], $m['vin_base']]);
        }
        $modsActualizados++;
    }

    // Reglas maestras de actualización por familia de modelo
    $db->exec("UPDATE cat_modelos_vin SET id_segmento = 3 WHERE modelo LIKE '%EST%' OR modelo LIKE '%Galaxy%' OR modelo LIKE '%Galaxus%' OR modelo LIKE '%GTL%' OR modelo LIKE '%S35%' OR modelo LIKE '%S38%' OR modelo LIKE '%S40%'");
    $db->exec("UPDATE cat_modelos_vin SET id_segmento = 2 WHERE (modelo LIKE '%S8%' OR modelo LIKE '%S12%' OR modelo LIKE '%S13%' OR modelo LIKE '%S20%') AND id_segmento != 3");
    $db->exec("UPDATE cat_modelos_vin SET id_segmento = 4 WHERE (modelo LIKE '%AUV%' OR modelo LIKE '%Midibus%' OR modelo LIKE '%Bus%' OR modelo LIKE '%URBI%' OR modelo LIKE '%ORION%')");
    $db->exec("UPDATE cat_modelos_vin SET id_segmento = 5 WHERE (modelo LIKE '%Lowboy%' OR modelo LIKE '%Sobredimensionado%')");
    $db->exec("UPDATE cat_modelos_vin SET id_segmento = 1 WHERE (modelo LIKE '%Tunland%' OR modelo LIKE '%HiVan%' OR modelo LIKE '%View%' OR modelo LIKE '%Wonder%' OR modelo LIKE '%TM%' OR modelo LIKE '%Miler%' OR modelo LIKE '%Toano%' OR (modelo LIKE '%S3%' AND modelo NOT LIKE '%S35%' AND modelo NOT LIKE '%S38%') OR modelo LIKE '%S5%' OR modelo LIKE '%S6%') AND (id_segmento IS NULL OR id_segmento = 1)");
    $db->exec("UPDATE cat_modelos_vin SET id_segmento = 1 WHERE id_cat_modelo_vin = 1");

    // Limpiar costos de envíos que se quedaron sin VINs asignados
    $numReseteados = $db->exec("
        UPDATE lgs_envios e 
        SET e.costo_total = 0.00 
        WHERE e.id_estado != 7 
          AND (
            e.id_envio NOT IN (SELECT DISTINCT id_envio FROM lgs_envios_vins)
            OR (SELECT COUNT(*) FROM lgs_envios_vins ev WHERE ev.id_envio = e.id_envio) = 0
          )
    ");
    $db->exec("
        DELETE tc FROM lgs_envios_tramos_costos tc
        INNER JOIN lgs_envios e ON tc.id_envio = e.id_envio
        WHERE e.id_estado != 7 
          AND (
            e.id_envio NOT IN (SELECT DISTINCT id_envio FROM lgs_envios_vins)
            OR (SELECT COUNT(*) FROM lgs_envios_vins ev WHERE ev.id_envio = e.id_envio) = 0
          )
    ");
    $db->exec("UPDATE lgs_envios SET costo_total = 0.00 WHERE deleted_at IS NOT NULL");

    $db->commit();
    echo "Exito! Se insertaron $count tarifas, se catalogaron correctamente los modelos vehiculares y se resetearon a $0.00 los envios sin VINs ($numReseteados corregidos).\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
