import sys

file_path = '/home/christianguarneros/proyectos/mrp/Models/Lgs_planeacionesModel.php'
with open(file_path, 'r') as f:
    content = f.read()

target = """                            COALESCE(NULLIF(cli.nombre_comercial, ''), cli.razon_social, d.nombre, p.destino_nombre_libre, 'Destino General') AS destino_parada,
                            p.orden AS orden_parada
                        FROM lgs_envios_vins v
                        LEFT JOIN lgs_unidades_envios u ON v.id_unidad = u.id_unidad
                        LEFT JOIN mrp_unidades_terminadas ut ON v.id_unidad = ut.idunidad
                        LEFT JOIN prv_det_madrinas m ON v.id_madrina = m.id_madrina
                        LEFT JOIN prv_det_choferes c ON v.id_chofer = c.id_chofer
                        LEFT JOIN lgs_envios_paradas p ON v.id_parada = p.id_parada
                        LEFT JOIN cli_clientes cli ON p.id_destino_cat = cli.idcliente
                        LEFT JOIN lgs_cat_destinos d ON p.id_destino_cat = d.id_destino"""

replacement = """                            COALESCE(ubi.nombre, n.destino_nombre_libre, 'Destino General') AS destino_parada,
                            n.orden AS orden_parada
                        FROM lgs_envios_vins v
                        LEFT JOIN lgs_unidades_envios u ON v.id_unidad = u.id_unidad
                        LEFT JOIN mrp_unidades_terminadas ut ON v.id_unidad = ut.idunidad
                        LEFT JOIN prv_det_madrinas m ON v.id_madrina = m.id_madrina
                        LEFT JOIN prv_det_choferes c ON v.id_chofer = c.id_chofer
                        LEFT JOIN lgs_envios_nodos n ON v.id_nodo_bajada = n.id_nodo
                        LEFT JOIN lgs_cat_ubicaciones ubi ON n.id_ubicacion = ubi.id_ubicacion"""

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Patched successfully")
else:
    print("Target not found")
