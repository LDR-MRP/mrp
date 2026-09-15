import sys

file_path = '/home/christianguarneros/proyectos/mrp/Assets/js/modulos/functions_lgs_aprobaciones.js'
with open(file_path, 'r') as f:
    content = f.read()

target = """            let objData = JSON.parse(request.responseText);
            let htmlBody = '';
            
            if (objData.status && objData.data.length > 0) {
                let calcCosto = 0;
                let calcKm = 0;

                objData.data.forEach(ruta => {
                    calcCosto += parseFloat(ruta.costo_total) || 0;
                    calcKm += parseFloat(ruta.km_total) || 0;

                    htmlBody += `
                        <tr>
                            <td class="fw-bold text-primary">${ruta.folio}</td>
                            <td><span class="badge bg-light text-dark border">${ruta.tipo_traslado || 'N/A'}</span></td>
                            <td>${ruta.origen}</td>
                            <td>${ruta.trasladista}</td>
                            <td class="text-center"><span class="badge bg-primary">${ruta.total_vins} VINs</span></td>
                            <td class="fw-bold text-success">$${parseFloat(ruta.costo_total).toFixed(2)}</td>
                        </tr>
                    `;
                });

                // Si no venía costo o km en cabecera, actualizar con la suma de las rutas
                if (!costo || parseFloat(costo) === 0) {
                    document.getElementById('lblCostoModal').innerText = '$' + calcCosto.toFixed(2);
                }
                if (!km || parseFloat(km) === 0) {
                    document.getElementById('lblKmModal').innerText = calcKm.toFixed(2) + ' km';
                }
            } else {
                htmlBody = '<tr><td colspan="6" class="text-center text-muted">No se encontraron rutas asociadas.</td></tr>';
            }
            
            document.getElementById('bodyDetalleRutas').innerHTML = htmlBody;"""

replacement = """            let objData = JSON.parse(request.responseText);
            const contEnvios = document.getElementById('vdp_contenedor_envios');
            if (contEnvios) contEnvios.innerHTML = '';
            
            if (objData.status && objData.data && objData.data.length > 0) {
                let calcCosto = 0;
                let calcKm = 0;

                objData.data.forEach(env => {
                    calcCosto += parseFloat(env.costo_total) || 0;
                    calcKm += parseFloat(env.km_total) || 0;

                    let rowsVins = '';
                    if (env.vins && env.vins.length > 0) {
                        env.vins.forEach(v => {
                            const pos = v.posicion_acomodo || 1;
                            const badgePos = (pos === 1)
                                ? '<span class="badge bg-success fs-11">1º en Cargar</span>'
                                : `<span class="badge bg-soft-primary text-primary fs-11">${pos}º en Cargar</span>`;

                            rowsVins += `
                                <tr>
                                    <td class="text-center">${badgePos}</td>
                                    <td>
                                        <strong class="text-primary fs-12">${v.vin}</strong>
                                        <small class="d-block text-muted fs-10">N/S: ${v.num_serie || 'N/A'}</small>
                                    </td>
                                    <td><span class="badge bg-soft-secondary text-dark">${v.modelo}</span></td>
                                    <td><i class="ri-map-pin-line text-danger me-1"></i>${v.destino_parada}</td>
                                    <td><i class="ri-truck-line text-info me-1"></i>${v.madrina}</td>
                                    <td class="text-end fw-bold text-success">$${(parseFloat(v.costo_unidad) || 0).toFixed(2)}</td>
                                </tr>
                            `;
                        });
                    } else {
                        rowsVins = '<tr><td colspan="6" class="text-center text-muted py-3">Sin unidades asignadas a este envío.</td></tr>';
                    }

                    const cardEnvHtml = `
                        <div class="card border shadow-none rounded-3 mb-3">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center py-2 px-3">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-primary fs-12 fw-bold">${env.folio}</span>
                                    <span class="badge bg-soft-info text-info border">${env.tipo_traslado || 'Madrina'}</span>
                                    <span class="fs-12 text-dark fw-medium"><i class="ri-map-pin-range-line text-muted me-1"></i>${env.origen}</span>
                                    <span class="fs-12 text-muted">| ${env.trasladista || 'Sin Trasladista'}</span>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="fs-12 text-muted"><i class="ri-car-line me-1"></i>${env.total_vins} unidad(es)</span>
                                    <strong class="fs-14 text-success">$${(parseFloat(env.costo_total) || 0).toFixed(2)}</strong>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped align-middle mb-0">
                                        <thead class="table-light fs-11 text-uppercase text-muted">
                                            <tr>
                                                <th class="text-center" style="width: 120px;">Secuencia</th>
                                                <th>VIN / N/S</th>
                                                <th>Modelo</th>
                                                <th>Parada / Destino</th>
                                                <th>Vehículo Asignado</th>
                                                <th class="text-end pe-3">Costo Est.</th>
                                            </tr>
                                        </thead>
                                        <tbody class="fs-12">
                                            ${rowsVins}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    `;

                    if (contEnvios) contEnvios.innerHTML += cardEnvHtml;
                });

                // Si no venía costo o km en cabecera, actualizar con la suma de las rutas
                if (!costo || parseFloat(costo) === 0) {
                    document.getElementById('lblCostoModal').innerText = '$' + calcCosto.toFixed(2);
                }
                if (!km || parseFloat(km) === 0) {
                    document.getElementById('lblKmModal').innerText = calcKm.toFixed(2) + ' km';
                }
            } else {
                if (contEnvios) contEnvios.innerHTML = '<div class="alert alert-soft-secondary text-center py-3">No hay envíos vinculados a esta planeación.</div>';
            }"""

if target in content:
    content = content.replace(target, replacement)
    with open(file_path, 'w') as f:
        f.write(content)
    print("Patched JS successfully")
else:
    print("Target not found in JS")
