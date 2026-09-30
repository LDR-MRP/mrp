<?php
$sql = "DELETE FROM lgs_tarifas_proveedores WHERE id_proveedor > 0;\n\n";

$proveedores = [
    'Logistica Teromo' => [
        'madrina' => [2=>45, 3=>35, 4=>28, 5=>25, 6=>22, 7=>20, 8=>20, 9=>20, 10=>20],
        'plataforma' => [1=>80, 2=>40, 3=>26.6667, 4=>20], // Divides $80 flat
        'slc' => 3850,
        'sll' => 5780
    ],
    'Clinicar' => [
        'madrina' => [2=>40, 3=>26.66, 4=>21.83, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
        'plataforma' => [1=>80, 2=>40, 3=>26.6667, 4=>20],
        'slc' => 3850,
        'sll' => 5780
    ],
    'Traslados Sacbe' => [
        'madrina' => [2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
        'slc' => 3850,
        'sll' => 5780
    ],
    'Jose Daniel Garcia Vazquez' => [
        'madrina' => [2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
        'plataforma' => [1=>50, 2=>25, 3=>16.6667, 4=>12.5], // 2 units is $25, so total is $50
        'slc' => 3850,
        'sll' => 5780
    ],
    'Automotive Translead' => [
        'madrina' => [2=>17, 3=>17, 4=>17, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
        'plataforma' => [1=>80, 2=>40, 3=>26.6667, 4=>20],
        'slc' => 3850,
        'sll' => 5780
    ],
    'SCX' => [
        'madrina' => [2=>23, 3=>23, 4=>20, 5=>17, 6=>17, 7=>15, 8=>15, 9=>13, 10=>13],
        'slc' => 3850,
        'sll' => 5780
    ],
    'Victor Hugo Alatorre Gudiño' => [
        'plataforma' => [1=>80, 2=>45, 3=>30, 4=>22.5], // Lowboy is 80, but Plataforma is 45 for 2u (90 total). We use 90 for Plataforma.
        'slc' => 3850,
        'sll' => 5780
    ]
];

$bases = [
    1 => 27.00, // Ligeros
    2 => 30.00, // Medianos
    3 => 40.00, // Pesados
    4 => 50.00, // Buses
    5 => 60.00  // Lowboy
];

foreach ($bases as $id_seg => $base) {
    $sql .= "UPDATE lgs_tarifas_proveedores SET costo_por_km = $base WHERE id_proveedor = 0 AND id_segmento = $id_seg;\n";
}

foreach ($proveedores as $nombre => $datos) {
    $sql .= "SET @id_prov = (SELECT id_proveedor FROM lgs_cat_proveedores WHERE nombre_comercial LIKE '%$nombre%' OR razon_social LIKE '%$nombre%' LIMIT 1);\n";
    $sql .= "IF @id_prov IS NOT NULL THEN\n";
    
    // Madrina (tipo_traslado = 1)
    if (isset($datos['madrina'])) {
        foreach ($bases as $id_seg => $base) {
            for ($u = 1; $u <= 10; $u++) {
                $factor = max(0.20, 1.0 - (($u - 1) * 0.02));
                $plano = 0; // Flat rates are usually applied per route, we keep them 0 here and maybe configure them on the global/route level.
                $sql .= "  INSERT INTO lgs_tarifas_proveedores (id_proveedor, id_tipo_traslado, id_segmento, num_vins_min, num_vins_max, costo_por_km, precio_plano, factor) ";
                $sql .= "VALUES (@id_prov, 1, $id_seg, $u, $u, $base, $plano, $factor);\n";
            }
        }
    }

    // Plataforma (tipo_traslado = 3)
    if (isset($datos['plataforma'])) {
        foreach ($bases as $id_seg => $base) {
            for ($u = 1; $u <= 4; $u++) {
                $factor = max(0.20, (60.0 / $base) / $u);
                $plano = 0; 
                $sql .= "  INSERT INTO lgs_tarifas_proveedores (id_proveedor, id_tipo_traslado, id_segmento, num_vins_min, num_vins_max, costo_por_km, precio_plano, factor) ";
                $sql .= "VALUES (@id_prov, 3, $id_seg, $u, $u, $base, $plano, $factor);\n";
            }
        }
    }
    
    $sql .= "END IF;\n\n";
}

file_put_contents('update_tarifas.sql', $sql);
echo "SQL generated in update_tarifas.sql\n";
