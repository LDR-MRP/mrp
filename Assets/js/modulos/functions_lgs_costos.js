let tableRutas;

document.addEventListener("DOMContentLoaded", function () {
    // Inicializar DataTable de Rutas Agrupadas
    tableRutas = $('#tableRutas').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "ajax": {
            "url": base_url + "/Lgs_costos/getRutas",
            "dataSrc": ""
        },
        "columns": [
            { "data": "tipo_traslado_html" },
            { "data": "proveedor_html" },
            { "data": "ruta_html" },
            { "data": "km_html" },
            { "data": "segmentos_html" },
            { "data": "options" }
        ],
        "responsive": true,
        "iDisplayLength": 10,
        "order": [[2, "asc"]],
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        }
    });
});

/**
 * Filtra el listado de rutas por proveedor (o tarifa base general)
 */
function onFilterProveedorChange(idProveedor) {
    const url = base_url + "/Lgs_costos/getRutas" + (idProveedor !== "" ? "?id_proveedor=" + encodeURIComponent(idProveedor) : "");
    if (tableRutas) {
        tableRutas.ajax.url(url).load();
    }
}

/**
 * Abre el modal para crear una nueva ruta
 */
function openNuevaRutaModal() {
    document.getElementById("formNuevaRuta").reset();
    document.getElementById("new_km").value = "0.00";
    if (document.getElementById("new_id_proveedor")) {
        document.getElementById("new_id_proveedor").value = "0";
    }
    recalcularTotalesNuevaRuta();
    $('#modalNuevaRuta').modal('show');
}

/**
 * Recalcula en tiempo real los costos estimados en el modal de nueva ruta
 */
function recalcularTotalesNuevaRuta() {
    const km = parseFloat(document.getElementById("new_km").value) || 0;
    const inputsCosto = document.querySelectorAll(".new-costo-km");
    const badgesTotal = document.querySelectorAll(".new-costo-total");

    inputsCosto.forEach((input, index) => {
        const costoKm = parseFloat(input.value) || 0;
        const total = km * costoKm;
        if (badgesTotal[index]) {
            badgesTotal[index].textContent = "$ " + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
}

/**
 * Guarda una nueva ruta completa
 */
function saveNuevaRuta(e) {
    e.preventDefault();
    const form = document.getElementById("formNuevaRuta");
    const formData = new FormData(form);

    fetch(base_url + "/Lgs_costos/saveRutaMatriz", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            Swal.fire("¡Ruta Creada!", data.msg, "success");
            $('#modalNuevaRuta').modal('hide');
            tableRutas.ajax.reload();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.fire("Error", "Error al conectar con el servidor", "error");
    });
}

/**
 * Filtra el listado de rutas por modalidad (Madrina o Chofer)
 */
function filterTableByModalidad(term) {
    const btnAll = document.getElementById("btnFilterAll");
    const btnMadrina = document.getElementById("btnFilterMadrina");
    const btnChofer = document.getElementById("btnFilterChofer");

    if (btnAll) btnAll.classList.remove("active");
    if (btnMadrina) btnMadrina.classList.remove("active");
    if (btnChofer) btnChofer.classList.remove("active");

    if (term === 'Madrina' && btnMadrina) {
        btnMadrina.classList.add("active");
    } else if (term === 'Chofer' && btnChofer) {
        btnChofer.classList.add("active");
    } else if (btnAll) {
        btnAll.classList.add("active");
    }

    if (tableRutas) {
        tableRutas.column(0).search(term).draw();
    }
}

/**
 * Abre el Modal Dual para consultar/editar tanto Madrina como Chofer del mismo trayecto
 */
function fntOpenMatrizModal(idTipoTraslado, idOrigen, idDestino, origenNombre, destinoNombre, tipoTrasladoNombre, idProveedor = 0, proveedorNombre = 'Tarifa Base General') {
    document.getElementById("matriz_id_origen").value = idOrigen;
    document.getElementById("matriz_id_destino").value = idDestino;
    document.getElementById("matriz_id_proveedor").value = idProveedor || 0;
    document.getElementById("label_origen_nombre").textContent = origenNombre;
    document.getElementById("label_destino_nombre").textContent = destinoNombre;

    const badgeProv = document.getElementById("label_proveedor_badge");
    if (badgeProv) {
        if (idProveedor && idProveedor > 0 && proveedorNombre !== 'Tarifa Base General') {
            badgeProv.className = "badge bg-info-subtle text-info fs-13 px-3 py-1";
            badgeProv.innerHTML = '<i class="ri-truck-line me-1"></i> ' + proveedorNombre;
        } else {
            badgeProv.className = "badge bg-secondary-subtle text-secondary fs-13 px-3 py-1";
            badgeProv.innerHTML = '<i class="ri-global-line me-1"></i> Tarifa Base General';
        }
    }

    // Activar la pestaña correspondiente al tipo de fila seleccionada
    if (idTipoTraslado == 2) {
        const triggerEl = document.getElementById('pill-chofer-tab');
        if (triggerEl) bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    } else {
        const triggerEl = document.getElementById('pill-madrina-tab');
        if (triggerEl) bootstrap.Tab.getOrCreateInstance(triggerEl).show();
    }

    const tbodyMadrina = document.getElementById("tbodyMatrizMadrina");
    const tbodyChofer = document.getElementById("tbodyMatrizChofer");
    tbodyMadrina.replaceChildren();
    tbodyChofer.replaceChildren();

    Swal.fire({
        title: "Cargando Tarifario",
        text: "Obteniendo precios de Madrina (Factores 1-15) y Chofer...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    const queryProv = (idProveedor && idProveedor > 0) ? "&id_proveedor=" + idProveedor : "";
    fetch(base_url + "/Lgs_costos/getRutaDual?id_origen=" + idOrigen + "&id_destino=" + idDestino + queryProv)
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.status) {
            const info = data.data;
            const km = parseFloat(info.km || 0);
            document.getElementById("matriz_km").value = km.toFixed(2);

            // 1. RENDERIZAR TABLA MADRINA (Factores 1 al 15)
            const madrinaMatriz = info.madrina || [];
            madrinaMatriz.forEach((item, idx) => {
                const trMain = document.createElement("tr");
                trMain.className = "align-middle bg-white";

                const tdSeg = document.createElement("td");
                tdSeg.innerHTML = `
                    <input type="hidden" name="madrina_segmentos[${idx}][id_segmento]" value="${item.id_segmento}">
                    <span class="fw-bold text-dark fs-14 d-block">${item.segmento_nombre}</span>
                    <small class="text-muted fs-11">${item.segmento_descripcion || ""}</small>
                `;

                const tdCostoKm = document.createElement("td");
                tdCostoKm.innerHTML = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" step="0.01" class="form-control text-end fw-bold madrina-costo-km" 
                               data-idx="${idx}" name="madrina_segmentos[${idx}][costo_por_km]" 
                               value="${parseFloat(item.costo_por_km || 0).toFixed(2)}" 
                               oninput="recalcularTotalesDual();">
                    </div>
                `;

                const tdEstimado = document.createElement("td");
                tdEstimado.innerHTML = `
                    <span class="badge bg-success-subtle text-success fs-13 fw-bold p-2 d-block text-end" id="madrina_total_base_${idx}">$ 0.00</span>
                `;
                const tdPlano = document.createElement("td");
                tdPlano.style.display = "none";
                tdPlano.innerHTML = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" step="0.01" class="form-control text-end" 
                                name="madrina_segmentos[${idx}][precio_plano]" 
                                value="${parseFloat(item.precio_plano || 0).toFixed(2)}">
                    </div>
                `;

                const tdFactores = document.createElement("td");
                tdFactores.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary-subtle text-primary border me-2 fs-12 px-2 py-1"><i class="ri-stack-line me-1"></i> Factores 1 al 15</span>
                            <small class="text-muted fs-11">Precios por volumen de unidades</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary shadow-xs" 
                                data-bs-toggle="collapse" data-bs-target="#collapseMadrinaFactores_${idx}" 
                                aria-expanded="false">
                            <i class="ri-edit-line me-1"></i> Editar Factores 1-15 <i class="ri-arrow-down-s-line"></i>
                        </button>
                    </div>
                `;

                trMain.appendChild(tdSeg);
                trMain.appendChild(tdCostoKm);
                trMain.appendChild(tdEstimado);
                trMain.appendChild(tdPlano);
                trMain.appendChild(tdFactores);
                tbodyMadrina.appendChild(trMain);

                // FILA EXPANDIBLE: GRILLA DE FACTOR 1 AL FACTOR 15
                const trCollapse = document.createElement("tr");
                trCollapse.className = "bg-light-subtle collapse-madrina-factores-row";
                
                const tdCollapse = document.createElement("td");
                tdCollapse.colSpan = 5;
                tdCollapse.className = "p-0 border-0";

                let cardsHtml = '';
                const factores15 = item.factores_15 || {};
                const basePrice = km * (parseFloat(item.costo_por_km) || 0);

                for (let u = 1; u <= 15; u++) {
                    const fVal = parseFloat(factores15[u] !== undefined ? factores15[u] : (1.0 - ((u - 1) * 0.02)));
                    const initUnitCost = (basePrice * fVal).toFixed(2);

                    cardsHtml += `
                        <div class="col" style="min-width: 145px; max-width: 155px;">
                            <div class="card border border-light-subtle shadow-none rounded-2 mb-2 bg-white">
                                <div class="card-header bg-primary text-white py-1 px-2 text-center border-bottom">
                                    <span class="fw-bold fs-12 text-white d-block">Factor ${u}</span>
                                    <small class="fs-9 text-white-50">${u} ${u === 1 ? 'Unidad' : 'Unidades'}</small>
                                </div>
                                <div class="card-body p-2 text-center">
                                    <label class="fs-10 text-muted mb-0 d-block text-uppercase fw-semibold">Precio / VIN ($)</label>
                                    <div class="input-group input-group-sm mb-1">
                                        <span class="input-group-text p-1 fs-11">$</span>
                                        <input type="number" step="0.01" 
                                               class="form-control form-control-sm text-end fw-bold precio-madrina-${idx}" 
                                               data-idx="${idx}" data-unit="${u}" 
                                               id="madrina_precio_${idx}_${u}" 
                                               value="${initUnitCost}" 
                                               oninput="onMadrinaPrecioChange(${idx}, ${u});">
                                    </div>
                                    <input type="hidden" class="factor-madrina-${idx}" 
                                           data-idx="${idx}" data-unit="${u}" 
                                           name="madrina_segmentos[${idx}][factores][${u}]" 
                                           id="madrina_factor_${idx}_${u}" 
                                           value="${fVal.toFixed(4)}">
                                    <div class="mt-1 border-top pt-1 text-start">
                                        <span class="fs-9 text-muted d-block">Ratio: <b class="text-primary factor-madrina-ratio-${idx}-${u}">${fVal.toFixed(2)}x</b></span>
                                        <span class="fs-9 text-muted d-block">Total (${u} VINs):</span>
                                        <span class="fs-10 fw-bold text-dark total-madrina-${idx}-${u}">$ 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }

                tdCollapse.innerHTML = `
                    <div class="collapse p-3 border-top border-bottom bg-light-subtle" id="collapseMadrinaFactores_${idx}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold fs-12 text-dark"><i class="ri-price-tag-3-line text-primary me-1"></i> Precios por Factor (1 a 15 Unidades) para <b>${item.segmento_nombre}</b>:</span>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.00);">Base x Factor</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.02);">-2% x Factor</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.03);">-3% x Factor</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.05);">-5% x Factor</button>
                            </div>
                        </div>
                        <div class="row row-cols-auto g-2 justify-content-start">
                            ${cardsHtml}
                        </div>
                    </div>
                `;

                trCollapse.appendChild(tdCollapse);
                tbodyMadrina.appendChild(trCollapse);
            });

            // 2. RENDERIZAR TABLA CHOFER (1 Sola Unidad)
            const choferMatriz = info.chofer || [];
            choferMatriz.forEach((cItem, cIdx) => {
                const trC = document.createElement("tr");
                trC.className = "align-middle bg-white";

                const tdCSeg = document.createElement("td");
                tdCSeg.innerHTML = `
                    <input type="hidden" name="chofer_segmentos[${cIdx}][id_segmento]" value="${cItem.id_segmento}">
                    <span class="fw-bold text-dark fs-14 d-block">${cItem.segmento_nombre}</span>
                    <small class="text-muted fs-11">${cItem.segmento_descripcion || ""}</small>
                `;

                const tdCCostoKm = document.createElement("td");
                tdCCostoKm.innerHTML = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" step="0.01" class="form-control text-end fw-bold chofer-costo-km" 
                                data-idx="${cIdx}" name="chofer_segmentos[${cIdx}][costo_por_km]" 
                                value="${parseFloat(cItem.costo_por_km || 0).toFixed(2)}" 
                                oninput="recalcularTotalesDual();">
                    </div>
                `;

                const tdCPlano = document.createElement("td");
                tdCPlano.style.display = "none";
                tdCPlano.innerHTML = `
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light">$</span>
                        <input type="number" step="0.01" class="form-control text-end chofer-precio-plano" 
                                data-idx="${cIdx}" name="chofer_segmentos[${cIdx}][precio_plano]" 
                                value="${parseFloat(cItem.precio_plano || 0).toFixed(2)}"
                                oninput="recalcularTotalesDual();">
                    </div>
                `;

                const tdCTotal = document.createElement("td");
                tdCTotal.innerHTML = `
                    <span class="badge bg-warning-subtle text-dark fs-13 fw-bold p-2 d-block text-end" id="chofer_total_${cIdx}">$ 0.00</span>
                `;

                trC.appendChild(tdCSeg);
                trC.appendChild(tdCCostoKm);
                trC.appendChild(tdCPlano);
                trC.appendChild(tdCTotal);
                tbodyChofer.appendChild(trC);
            });

            recalcularTotalesDual();
            $('#modalRutaMatriz').modal('show');
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire("Error", "No se pudieron cargar las tarifas de la ruta", "error");
    });
}

/**
 * Cuando el usuario cambia manualmente el Precio / VIN de Madrina
 */
function onMadrinaPrecioChange(idx, u) {
    const km = parseFloat(document.getElementById("matriz_km").value) || 0;
    const inputCostoKm = document.querySelector(`.madrina-costo-km[data-idx="${idx}"]`);
    const costoKm = parseFloat(inputCostoKm ? inputCostoKm.value : 0) || 0;
    const totalBase = km * costoKm;

    const inputPrecio = document.getElementById(`madrina_precio_${idx}_${u}`);
    const inputFactor = document.getElementById(`madrina_factor_${idx}_${u}`);
    const badgeRatio = document.querySelector(`.factor-madrina-ratio-${idx}-${u}`);
    const badgeFlete = document.querySelector(`.total-madrina-${idx}-${u}`);

    const precioVal = parseFloat(inputPrecio ? inputPrecio.value : 0) || 0;

    let ratio = 1.0;
    if (totalBase > 0) {
        ratio = precioVal / totalBase;
    }

    if (inputFactor) inputFactor.value = ratio.toFixed(4);
    if (badgeRatio) badgeRatio.textContent = ratio.toFixed(2) + "x";
    if (badgeFlete) {
        const flete = precioVal * u;
        badgeFlete.textContent = "$ " + flete.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
}

/**
 * Expande o contrae todos los paneles de factores de Madrina
 */
function expandirTodosFactoresMadrina(expand) {
    const collapses = document.querySelectorAll('#tbodyMatrizMadrina .collapse');
    collapses.forEach(el => {
        if (expand) {
            $(el).collapse('show');
        } else {
            $(el).collapse('hide');
        }
    });
}

/**
 * Aplica preset de factores para Madrina
 */
function aplicarPresetMadrina(idx, tasaDescuento) {
    const km = parseFloat(document.getElementById("matriz_km").value) || 0;
    const inputCostoKm = document.querySelector(`.madrina-costo-km[data-idx="${idx}"]`);
    const costoKm = parseFloat(inputCostoKm ? inputCostoKm.value : 0) || 0;
    const totalBase = km * costoKm;

    for (let u = 1; u <= 15; u++) {
        let f = 1.0 - ((u - 1) * tasaDescuento);
        if (f < 0.20) f = 0.20;

        const inputFactor = document.getElementById(`madrina_factor_${idx}_${u}`);
        const inputPrecio = document.getElementById(`madrina_precio_${idx}_${u}`);
        const badgeRatio = document.querySelector(`.factor-madrina-ratio-${idx}-${u}`);
        const badgeFlete = document.querySelector(`.total-madrina-${idx}-${u}`);

        if (inputFactor) inputFactor.value = f.toFixed(4);
        if (badgeRatio) badgeRatio.textContent = f.toFixed(2) + "x";

        const unitCost = totalBase * f;
        if (inputPrecio) inputPrecio.value = unitCost.toFixed(2);

        if (badgeFlete) {
            const flete = unitCost * u;
            badgeFlete.textContent = "$ " + flete.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    }
}

/**
 * Recalcula en tiempo real los totales para ambas modalidades (Madrina y Chofer)
 */
function recalcularTotalesDual() {
    const km = parseFloat(document.getElementById("matriz_km").value) || 0;

    // 1. Recalcular Madrina
    const inputsMadrina = document.querySelectorAll(".madrina-costo-km");
    inputsMadrina.forEach((input) => {
        const idx = input.getAttribute("data-idx");
        const costoKm = parseFloat(input.value) || 0;
        const totalBase = km * costoKm;

        const badgeBase = document.getElementById(`madrina_total_base_${idx}`);
        if (badgeBase) {
            badgeBase.textContent = "$ " + totalBase.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        for (let u = 1; u <= 15; u++) {
            const inputFactor = document.getElementById(`madrina_factor_${idx}_${u}`);
            const inputPrecio = document.getElementById(`madrina_precio_${idx}_${u}`);
            const badgeRatio = document.querySelector(`.factor-madrina-ratio-${idx}-${u}`);
            const badgeFlete = document.querySelector(`.total-madrina-${idx}-${u}`);

            if (inputFactor) {
                const factorVal = parseFloat(inputFactor.value) || 1.0;
                const unitCost = totalBase * factorVal;

                if (inputPrecio && document.activeElement !== inputPrecio) {
                    inputPrecio.value = unitCost.toFixed(2);
                }
                if (badgeRatio) {
                    badgeRatio.textContent = factorVal.toFixed(2) + "x";
                }
                if (badgeFlete) {
                    const currentPrice = inputPrecio ? (parseFloat(inputPrecio.value) || unitCost) : unitCost;
                    const flete = currentPrice * u;
                    badgeFlete.textContent = "$ " + flete.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            }
        }
    });

    // 2. Recalcular Chofer (1 Unidad)
    const inputsChofer = document.querySelectorAll(".chofer-costo-km");
    inputsChofer.forEach((cInput) => {
        const cIdx = cInput.getAttribute("data-idx");
        const cCostoKm = parseFloat(cInput.value) || 0;
        const planoInput = document.querySelector(`.chofer-precio-plano[data-idx="${cIdx}"]`);
        const cPlano = parseFloat(planoInput ? planoInput.value : 0) || 0;

        const totalChofer = (km * cCostoKm) + cPlano;
        const badgeCTotal = document.getElementById(`chofer_total_${cIdx}`);
        if (badgeCTotal) {
            badgeCTotal.textContent = "$ " + totalChofer.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
    });
}

/**
 * Guarda las modificaciones de ambas modalidades del trayecto
 */
function saveRutaMatrizDual(e) {
    e.preventDefault();
    const form = document.getElementById("formRutaMatriz");
    const formData = new FormData(form);

    Swal.fire({
        title: "Guardando Tarifario",
        text: "Actualizando tarifas de Madrina y Chofer para este trayecto...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(base_url + "/Lgs_costos/saveRutaDual", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.status) {
            Swal.fire("¡Tarifas Actualizadas!", data.msg, "success");
            $('#modalRutaMatriz').modal('hide');
            tableRutas.ajax.reload();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire("Error", "Error al guardar las tarifas del trayecto", "error");
    });
}

/**
 * Elimina una ruta completa con confirmación
 */
function fntDeleteRuta(idTipoTraslado, idOrigen, idDestino, rutaNombre) {
    Swal.fire({
        title: "Eliminar Ruta",
        text: "¿Está seguro de eliminar todas las tarifas configuradas para " + rutaNombre + "?",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar ruta",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("id_tipo_traslado", idTipoTraslado);
            formData.append("id_origen", idOrigen);
            formData.append("id_destino", idDestino);

            fetch(base_url + "/Lgs_costos/delRuta", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    Swal.fire("Eliminada", data.msg, "success");
                    tableRutas.ajax.reload();
                } else {
                    Swal.fire("Error", data.msg, "error");
                }
            })
            .catch(error => {
                Swal.fire("Error", "Error al conectar con el servidor", "error");
            });
        }
    });
}

/**
 * Abre el modal para importar el CSV
 */
function openImportModal() {
    document.getElementById("formImportCSV").reset();
    $('#modalImportCSV').modal('show');
}

/**
 * Envía el archivo CSV para su importación
 */
function submitImportCSV(e) {
    e.preventDefault();
    const form = document.getElementById("formImportCSV");
    const formData = new FormData(form);

    Swal.fire({
        title: "Procesando Importación",
        text: "Espere un momento mientras se lee el archivo...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(base_url + "/Lgs_costos/importTarifas", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.status) {
            const res = data.data;
            let msg = `Se importaron/actualizaron correctamente ${res.imported_records} tarifas con origen "${res.origin}".`;
            if (res.errors && res.errors.length > 0) {
                msg += `\nErrores encontrados: ${res.errors.length}`;
            }
            Swal.fire("Importación Exitosa", msg, "success");
            $('#modalImportCSV').modal('hide');
            tableRutas.ajax.reload();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire("Error", "Error durante la importación del archivo", "error");
    });
}
