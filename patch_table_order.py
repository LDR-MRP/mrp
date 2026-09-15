import re

# 1. Update index.php
with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/index.php', 'r') as f:
    content_index = f.read()

old_thead = """                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Tipo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Motivo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Destino</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Distancia (KM)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">VINs Asignados</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Costo Est.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha Prog.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>"""

new_thead = """                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Destino</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha Prog.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">VINs Asignados</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Costo Est.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Distancia (KM)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Tipo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Motivo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>"""

if old_thead in content_index:
    content_index = content_index.replace(old_thead, new_thead)
    with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/index.php', 'w') as f:
        f.write(content_index)
    print("index.php updated!")
else:
    print("Could not find thead in index.php")


# 2. Update functions_lgs_envios.js
with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'r') as f:
    content_js = f.read()

old_columns = """        "columns": [
            { "data": "id_envio" },
            { 
                "data": "folio",
                "render": function(data, type, row) {
                    let html = '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || 'S/F') + '</span>';
                    if (row.vins_list) {
                        const vins = row.vins_list.split(', ');
                        html += '<div class="mt-1">' + vins.map(v => '<span class="badge bg-soft-secondary text-secondary me-1 fs-10">' + v + '</span>').join('') + '</div>';
                    }
                    return html;
                }
            },
            { "data": "tipo_traslado" },
            { "data": "motivo" },
            { 
                "data": "trasladista",
                "render": function(data) {
                    return '<span class="fw-medium text-dark">' + (data || '-') + '</span>';
                }
            },
            { "data": "origen" },
            { 
                "data": "destino",
                "render": function(data, type, row) {
                    let html = '<span class="fw-medium text-dark">' + (data || 'Sin Destino') + '</span>';
                    if (row.paradas_list) {
                        html += '<div class="mt-1 fs-11 text-muted"><i class="ri-route-line text-info me-1"></i>Ruta: ' + row.paradas_list + '</div>';
                    }
                    return html;
                }
            },
            { 
                "data": "km_total",
                "render": function(data, type, row) {
                    const kmVal = parseFloat(data || 0).toFixed(1);
                    const nParadas = row.total_paradas || 1;
                    return '<span class="badge bg-soft-info text-info fs-12 fw-bold"><i class="ri-route-line me-1"></i>' + kmVal + ' km</span>' +
                           '<div class="text-muted fs-11 mt-1">' + nParadas + ' parada(s)</div>';
                }
            },
            { 
                "data": "total_vins",
                "render": function(data, type, row) {
                    if (!row.vins_list || !row.vins_list.trim()) {
                        return '<span class="badge bg-soft-secondary text-muted fs-12">Sin VINs</span>';
                    }
                    const vins = row.vins_list.split(', ').filter(v => v.trim());
                    let html = vins.map(v => '<span class="badge bg-primary me-1 fs-10" style="font-size:10px;">' + v + '</span>').join('');
                    html += '<div class="text-muted fs-11 mt-1">' + vins.length + ' unidad(es)</div>';
                    return html;
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<span class="fw-bold text-success">$' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            {
                "data": "fecha_tentativa_envio",
                "render": function (data) {
                    if (!data || data === 'null') return '<span class="text-muted fs-11">No definida</span>';
                    return '<span class="fs-12 text-dark fw-medium"><i class="ri-calendar-event-line text-primary me-1"></i>' + data.replace('T', ' ') + '</span>';
                }
            },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-12"><i class="ri-draft-line me-1"></i>Creado</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-time-line me-1"></i>En Aprobación</span>'; break;
                        case 3: badge = '<span class="badge bg-soft-primary text-primary fs-12"><i class="ri-checkbox-circle-line me-1"></i>Aprobado</span>'; break;
                        case 4: badge = '<span class="badge bg-soft-danger text-danger fs-12"><i class="ri-close-circle-line me-1"></i>Rechazado</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-info text-info fs-12"><i class="ri-calendar-check-line me-1"></i>Programado</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-info text-info fs-12"><i class="ri-truck-line me-1"></i>Ejecutado</span>'; break;
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-double-line me-1"></i>Entregado</span>'; break;
                        case 8: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-line me-1"></i>Confirmado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio","""

new_columns = """        "columns": [
            { 
                "data": "folio",
                "render": function(data, type, row) {
                    let html = '<span class="badge bg-soft-primary text-primary fs-12 fw-bold">' + (data || 'S/F') + '</span>';
                    if (row.vins_list) {
                        const vins = row.vins_list.split(', ');
                        html += '<div class="mt-1">' + vins.map(v => '<span class="badge bg-soft-secondary text-secondary me-1 fs-10">' + v + '</span>').join('') + '</div>';
                    }
                    return html;
                }
            },
            { 
                "data": "id_estado",
                "render": function (data) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 1: badge = '<span class="badge bg-soft-secondary text-secondary fs-12"><i class="ri-draft-line me-1"></i>Creado</span>'; break;
                        case 2: badge = '<span class="badge bg-soft-warning text-warning fs-12"><i class="ri-time-line me-1"></i>En Aprobación</span>'; break;
                        case 3: badge = '<span class="badge bg-soft-primary text-primary fs-12"><i class="ri-checkbox-circle-line me-1"></i>Aprobado</span>'; break;
                        case 4: badge = '<span class="badge bg-soft-danger text-danger fs-12"><i class="ri-close-circle-line me-1"></i>Rechazado</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-info text-info fs-12"><i class="ri-calendar-check-line me-1"></i>Programado</span>'; break;
                        case 6: badge = '<span class="badge bg-soft-info text-info fs-12"><i class="ri-truck-line me-1"></i>Ejecutado</span>'; break;
                        case 7: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-double-line me-1"></i>Entregado</span>'; break;
                        case 8: badge = '<span class="badge bg-soft-success text-success fs-12"><i class="ri-check-line me-1"></i>Confirmado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            { "data": "origen" },
            { 
                "data": "destino",
                "render": function(data, type, row) {
                    let html = '<span class="fw-medium text-dark">' + (data || 'Sin Destino') + '</span>';
                    if (row.paradas_list) {
                        html += '<div class="mt-1 fs-11 text-muted"><i class="ri-route-line text-info me-1"></i>Ruta: ' + row.paradas_list + '</div>';
                    }
                    return html;
                }
            },
            {
                "data": "fecha_tentativa_envio",
                "render": function (data) {
                    if (!data || data === 'null') return '<span class="text-muted fs-11">No definida</span>';
                    return '<span class="fs-12 text-dark fw-medium"><i class="ri-calendar-event-line text-primary me-1"></i>' + data.replace('T', ' ') + '</span>';
                }
            },
            { 
                "data": "total_vins",
                "render": function(data, type, row) {
                    if (!row.vins_list || !row.vins_list.trim()) {
                        return '<span class="badge bg-soft-secondary text-muted fs-12">Sin VINs</span>';
                    }
                    const vins = row.vins_list.split(', ').filter(v => v.trim());
                    let html = vins.map(v => '<span class="badge bg-primary me-1 fs-10" style="font-size:10px;">' + v + '</span>').join('');
                    html += '<div class="text-muted fs-11 mt-1">' + vins.length + ' unidad(es)</div>';
                    return html;
                }
            },
            { 
                "data": "trasladista",
                "render": function(data) {
                    return '<span class="fw-medium text-dark">' + (data || '-') + '</span>';
                }
            },
            { 
                "data": "costo_total",
                "render": function (data) {
                    if (data == null) return '$0.00';
                    return '<span class="fw-bold text-success">$' + parseFloat(data).toFixed(2) + '</span>';
                }
            },
            { 
                "data": "km_total",
                "render": function(data, type, row) {
                    const kmVal = parseFloat(data || 0).toFixed(1);
                    const nParadas = row.total_paradas || 1;
                    return '<span class="badge bg-soft-info text-info fs-12 fw-bold"><i class="ri-route-line me-1"></i>' + kmVal + ' km</span>' +
                           '<div class="text-muted fs-11 mt-1">' + nParadas + ' parada(s)</div>';
                }
            },
            { "data": "tipo_traslado" },
            { "data": "motivo" },
            { "data": "id_envio" },
            {
                "data": "id_envio","""

if old_columns in content_js:
    content_js = content_js.replace(old_columns, new_columns)
    # Also need to update the default sort order to match ID column or Folio. ID was index 0, now it's index 11.
    # Folio is now 0. So `"order": [[0, "desc"]]` will order by Folio descending, which is perfect.
    
    with open('/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_envios.js', 'w') as f:
        f.write(content_js)
    print("functions_lgs_envios.js updated!")
else:
    print("Could not find columns in functions_lgs_envios.js")
