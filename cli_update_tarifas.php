<?php
require_once __DIR__ . '/Config/Config.php';

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASSWORD);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        // Chofer
        if (isset($chofer_global[$id_seg])) {
            $precioC = $chofer_global[$id_seg];
            $factorC = round($precioC / $base, 4);
            $stmtIns->execute([0, 2, $id_seg, 1, 1, $precioC, $factorC, 0]);
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
                // Chofer
                if (isset($chofer_precios[$id_seg])) {
                    $precioC = $chofer_precios[$id_seg];
                    $factorC = round($precioC / $base, 4);
                    $stmtIns->execute([$id_prov, 2, $id_seg, 1, 1, $precioC, $factorC, 0]);
                    $count++;
                }
            }
        }
    }

    $db->commit();
    echo "Exito! Se insertaron $count tarifas basadas en el Excel de forma precisa.\n";

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "Error: " . $e->getMessage() . "\n";
}
