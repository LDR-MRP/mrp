let tableDistancias;

document.addEventListener("DOMContentLoaded", function () {
    if (document.getElementById("tableDistancias")) {
        tableDistancias = $('#tableDistancias').DataTable({
            "aProcessing": true,
            "aServerSide": false,
            "ajax": {
                "url": base_url + "/Lgs_costos/getDistancias",
                "dataSrc": ""
            },
            "columns": [
                { "data": "ruta_html" },
                { "data": "km_html" },
                { "data": "options" }
            ],
            "responsive": true,
            "iDisplayLength": 10,
            "order": [[0, "asc"]],
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
            }
        });
    }

    if (document.getElementById("select_tarifa_proveedor")) {
        loadTarifasProveedor();
    }
});

/**
 * ==========================================
 * MÓDULO 1: DISTANCIAS
 * ==========================================
 */
function saveDistancia(e) {
    e.preventDefault();
    const form = document.getElementById("formNuevaDistancia");
    const idA = document.getElementById("dist_ubicacion_a").value;
    const idB = document.getElementById("dist_ubicacion_b").value;
    const km = document.getElementById("dist_km").value;

    if (idA === idB) {
        Swal.fire("Atención", "La ubicación de origen y destino no pueden ser la misma.", "warning");
        return;
    }

    const payload = {
        id_ubicacion_a: idA,
        id_ubicacion_b: idB,
        km: km
    };

    fetch(base_url + "/Lgs_costos/saveDistancia", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(payload)
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            Swal.fire("¡Éxito!", data.msg, "success");
            document.getElementById("dist_km").value = "";
            tableDistancias.ajax.reload();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.fire("Error", "Error al conectar con el servidor", "error");
    });
}

function fntEditDistancia(idA, idB, km) {
    document.getElementById("dist_ubicacion_a").value = idA;
    document.getElementById("dist_ubicacion_b").value = idB;
    document.getElementById("dist_km").value = km;
    
    // Si usa select2
    if ($('#dist_ubicacion_a').hasClass('select2-hidden-accessible')) {
        $('#dist_ubicacion_a').trigger('change');
    }
    if ($('#dist_ubicacion_b').hasClass('select2-hidden-accessible')) {
        $('#dist_ubicacion_b').trigger('change');
    }

    document.getElementById("dist_km").focus();
}

function fntDeleteDistancia(idDistancia, rutaNombre) {
    Swal.fire({
        title: "Eliminar Distancia",
        text: `¿Está seguro de eliminar la distancia entre ${rutaNombre}?`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#d33"
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append("id_distancia", idDistancia);

            fetch(base_url + "/Lgs_costos/delDistancia", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    Swal.fire("Eliminada", data.msg, "success");
                    tableDistancias.ajax.reload();
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
 * ==========================================
 * MÓDULO 2: TARIFAS POR PROVEEDOR
 * ==========================================
 */

function loadTarifasProveedor() {
    const idProveedor = document.getElementById("select_tarifa_proveedor").value;
    const tbodyMadrina = document.getElementById("tbodyTarifasMadrina");
    const tbodyChofer = document.getElementById("tbodyTarifasChofer");
    const badgeStatus = document.getElementById("tarifa_status_badge");
    const btnRestablecer = document.getElementById("btnRestablecerGlobal");
    const lblBtnGuardar = document.getElementById("btnGuardarTarifasTexto");

    if (!tbodyMadrina || !tbodyChofer) return;

    tbodyMadrina.innerHTML = `<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><br>Cargando Tarifas...</td></tr>`;
    tbodyChofer.innerHTML = `<tr><td colspan="4" class="text-center py-4"><div class="spinner-border text-warning" role="status"></div><br>Cargando Tarifas...</td></tr>`;

    fetch(base_url + "/Lgs_costos/getTarifasProveedor?id_proveedor=" + idProveedor)
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            const info = data.data;

            // Gestión de interfaz según tipo de tarifa
            if (idProveedor === "0") {
                if (lblBtnGuardar) lblBtnGuardar.textContent = "Guardar Tarifa Base General...";
                if (btnRestablecer) btnRestablecer.classList.add("d-none");
                if (badgeStatus) {
                    badgeStatus.innerHTML = `
                        <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fs-11">
                            <i class="ri-global-line me-1"></i> <b>Tarifa Base General:</b> Plantilla maestra que aplica por defecto a todos los transportistas.
                        </span>
                    `;
                }
            } else {
                if (lblBtnGuardar) lblBtnGuardar.textContent = "Guardar Tarifas del Proveedor";
                if (info.tiene_tarifas_propias) {
                    if (btnRestablecer) btnRestablecer.classList.remove("d-none");
                    if (badgeStatus) {
                        badgeStatus.innerHTML = `
                            <span class="badge bg-success-subtle text-success border border-success px-2 py-1 fs-11">
                                <i class="ri-checkbox-circle-line me-1"></i> <b>Tarifas Personalizadas:</b> Este proveedor cuenta con tarifas específicas guardadas.
                            </span>
                        `;
                    }
                } else {
                    if (btnRestablecer) btnRestablecer.classList.add("d-none");
                    if (badgeStatus) {
                        badgeStatus.innerHTML = `
                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1 fs-11">
                                <i class="ri-information-line me-1"></i> <b>Precargado de Tarifa Base General:</b> Modifique los costos o factores que requiera y presione "Guardar" para personalizar este proveedor.
                            </span>
                        `;
                    }
                }
            }

            renderTarifasMadrina(info.madrina || []);
            renderTarifasChofer(info.chofer || []);
            recalcularTotalesTarifas();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.fire("Error", "No se pudieron cargar las tarifas", "error");
    });
}

function renderTarifasMadrina(madrinaMatriz) {
    const tbody = document.getElementById("tbodyTarifasMadrina");
    tbody.replaceChildren();

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
                       oninput="recalcularTotalesTarifas();">
            </div>
        `;

        const tdPlano = document.createElement("td");
        tdPlano.style.display = "none";
        tdPlano.innerHTML = `
            <input type="hidden" name="madrina_segmentos[${idx}][precio_plano]" value="${parseFloat(item.precio_plano || 0).toFixed(2)}">
        `;

        const tdFactores = document.createElement("td");
        tdFactores.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <span class="badge bg-primary-subtle text-primary border me-2 fs-12 px-2 py-1"><i class="ri-stack-line me-1"></i> 1 a 15 VINs</span>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary shadow-xs" 
                        data-bs-toggle="collapse" data-bs-target="#collapseMadrinaFactores_${idx}" 
                        aria-expanded="false">
                    Ver Factores <i class="ri-arrow-down-s-line"></i>
                </button>
            </div>
        `;

        trMain.appendChild(tdSeg);
        trMain.appendChild(tdCostoKm);
        trMain.appendChild(tdPlano);
        trMain.appendChild(tdFactores);
        tbody.appendChild(trMain);

        // FILA EXPANDIBLE
        const trCollapse = document.createElement("tr");
        trCollapse.className = "bg-light-subtle collapse-madrina-factores-row";
        
        const tdCollapse = document.createElement("td");
        tdCollapse.colSpan = 4;
        tdCollapse.className = "p-0 border-0";

        let cardsHtml = '';
        const factores15 = item.factores_15 || {};
        const baseCost = parseFloat(item.costo_por_km) || 0;

        for (let u = 1; u <= 15; u++) {
            const fVal = parseFloat(factores15[u] !== undefined ? factores15[u] : (1.0 - ((u - 1) * 0.02)));
            const initUnitCost = (baseCost * fVal).toFixed(2);

            cardsHtml += `
                <div class="col" style="min-width: 140px; max-width: 150px;">
                    <div class="card border border-light-subtle shadow-none rounded-2 mb-2 bg-white">
                        <div class="card-header bg-primary text-white py-1 px-2 text-center border-bottom">
                            <span class="fw-bold fs-12 text-white d-block">Factor ${u}</span>
                            <small class="fs-9 text-white-50">${u} VINs</small>
                        </div>
                        <div class="card-body p-2 text-center">
                            <label class="fs-10 text-muted mb-0 d-block text-uppercase fw-semibold">Multiplicador</label>
                            <div class="input-group input-group-sm mb-1">
                                <span class="input-group-text p-1 fs-11">x</span>
                                <input type="number" step="0.0001" 
                                       class="form-control form-control-sm text-end fw-bold factor-madrina-${idx}" 
                                       data-idx="${idx}" data-unit="${u}" 
                                       name="madrina_segmentos[${idx}][factores][${u}]" 
                                       id="madrina_factor_${idx}_${u}" 
                                       value="${fVal.toFixed(4)}" 
                                       oninput="recalcularTotalesTarifas();">
                            </div>
                            <div class="mt-1 border-top pt-1 text-start">
                                <span class="fs-9 text-muted d-block">Preview (x 1 KM):</span>
                                <span class="fs-10 fw-bold text-dark preview-madrina-${idx}-${u}">$ ${initUnitCost} / km</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        tdCollapse.innerHTML = `
            <div class="collapse p-3 border-top border-bottom bg-light-subtle" id="collapseMadrinaFactores_${idx}">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold fs-12 text-dark"><i class="ri-price-tag-3-line text-primary me-1"></i> Configuración de Factores para <b>${item.segmento_nombre}</b>:</span>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.00);">Plano (x1.0)</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.02);">-2% por VIN</button>
                        <button type="button" class="btn btn-outline-secondary" onclick="aplicarPresetMadrina(${idx}, 0.03);">-3% por VIN</button>
                    </div>
                </div>
                <div class="row row-cols-auto g-2 justify-content-start">
                    ${cardsHtml}
                </div>
            </div>
        `;

        trCollapse.appendChild(tdCollapse);
        tbody.appendChild(trCollapse);
    });
}

function renderTarifasChofer(choferMatriz) {
    const tbody = document.getElementById("tbodyTarifasChofer");
    tbody.replaceChildren();

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
                        name="chofer_segmentos[${cIdx}][costo_por_km]" 
                        value="${parseFloat(cItem.costo_por_km || 0).toFixed(2)}">
            </div>
        `;

        const tdCPlano = document.createElement("td");
        tdCPlano.style.display = "none";
        tdCPlano.innerHTML = `
            <input type="hidden" name="chofer_segmentos[${cIdx}][precio_plano]" value="${parseFloat(cItem.precio_plano || 0).toFixed(2)}">
        `;
        
        const tdCTotal = document.createElement("td");
        tdCTotal.innerHTML = `
            <span class="badge bg-warning-subtle text-dark fs-13 fw-bold p-2 d-block text-end">Aplica por cada KM</span>
        `;

        trC.appendChild(tdCSeg);
        trC.appendChild(tdCCostoKm);
        trC.appendChild(tdCPlano);
        trC.appendChild(tdCTotal);
        tbody.appendChild(trC);
    });
}

function toggleFactoresMadrina(expand) {
    const collapses = document.querySelectorAll('#tbodyTarifasMadrina .collapse');
    collapses.forEach(el => {
        if (expand) {
            $(el).collapse('show');
        } else {
            $(el).collapse('hide');
        }
    });
}

function aplicarPresetMadrina(idx, tasaDescuento) {
    const inputCostoKm = document.querySelector(`.madrina-costo-km[data-idx="${idx}"]`);
    const costoKm = parseFloat(inputCostoKm ? inputCostoKm.value : 0) || 0;

    for (let u = 1; u <= 15; u++) {
        let f = 1.0 - ((u - 1) * tasaDescuento);
        if (f < 0.20) f = 0.20;

        const inputFactor = document.getElementById(`madrina_factor_${idx}_${u}`);
        if (inputFactor) inputFactor.value = f.toFixed(4);
    }
    recalcularTotalesTarifas();
}

function recalcularTotalesTarifas() {
    const inputsMadrina = document.querySelectorAll(".madrina-costo-km");
    inputsMadrina.forEach((input) => {
        const idx = input.getAttribute("data-idx");
        const costoKm = parseFloat(input.value) || 0;

        for (let u = 1; u <= 15; u++) {
            const inputFactor = document.getElementById(`madrina_factor_${idx}_${u}`);
            const previewSpan = document.querySelector(`.preview-madrina-${idx}-${u}`);

            if (inputFactor && previewSpan) {
                const factorVal = parseFloat(inputFactor.value) || 1.0;
                const unitCost = costoKm * factorVal;
                previewSpan.textContent = "$ " + unitCost.toFixed(2) + " / km";
            }
        }
    });
}

function saveTarifasProveedor() {
    const idProveedor = document.getElementById("select_tarifa_proveedor").value;
    if (idProveedor === "0") {
        openModalReplicarTarifaBase();
    } else {
        saveTarifasProveedorIndividual();
    }
}

function saveTarifasProveedorIndividual() {
    const form = document.getElementById("formTarifasProveedor");
    const formData = new FormData(form);
    
    const idProveedor = document.getElementById("select_tarifa_proveedor").value;
    formData.append("id_proveedor", idProveedor);

    Swal.fire({
        title: "Guardando Tarifas",
        text: "Actualizando tarifas del proveedor...",
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(base_url + "/Lgs_costos/saveTarifasProveedor", {
        method: "POST",
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        Swal.close();
        if (data.status) {
            Swal.fire("¡Tarifas Guardadas!", data.msg, "success");
            loadTarifasProveedor();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(error => {
        Swal.close();
        Swal.fire("Error", "Error al guardar las tarifas", "error");
    });
}

/**
 * ==============================================================
 * REPLICACIÓN SELECTIVA DE TARIFA BASE A PROVEEDORES
 * ==============================================================
 */

let sysProveedoresEstado = [];

function openModalReplicarTarifaBase() {
    const modalEl = document.getElementById("modalReplicarTarifaBase");
    if (!modalEl) return;
    const modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl);
    
    const tbody = document.getElementById("tbodyReplicarProveedores");
    tbody.innerHTML = `
        <tr>
            <td colspan="3" class="text-center py-4 text-muted">
                <div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando transportistas...
            </td>
        </tr>
    `;

    modalInstance.show();

    fetch(base_url + "/Lgs_costos/getProveedoresEstadoTarifa")
    .then(res => res.json())
    .then(data => {
        if (data.status) {
            sysProveedoresEstado = data.data || [];
            renderProveedoresReplicar(sysProveedoresEstado);
        } else {
            tbody.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-danger">Error: ${data.msg}</td></tr>`;
        }
    })
    .catch(err => {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-danger">Error al cargar transportistas</td></tr>`;
    });
}

function renderProveedoresReplicar(proveedores) {
    const tbody = document.getElementById("tbodyReplicarProveedores");
    tbody.replaceChildren();

    if (proveedores.length === 0) {
        tbody.innerHTML = `<tr><td colspan="3" class="text-center py-3 text-muted">No hay transportistas activos registrados.</td></tr>`;
        actualizarContadorReplicar();
        return;
    }

    proveedores.forEach(p => {
        const tr = document.createElement("tr");
        tr.className = "align-middle";

        const esPersonalizada = parseInt(p.tiene_personalizada) === 1;
        const badgeHtml = esPersonalizada 
            ? `<span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1"><i class="ri-edit-2-line me-1"></i> Tarifa Personalizada</span>`
            : `<span class="badge bg-light text-muted border px-2 py-1"><i class="ri-global-line me-1"></i> Tarifa General</span>`;

        tr.innerHTML = `
            <td class="text-center">
                <input type="checkbox" class="form-check-input chk-proveedor-replicar" 
                       value="${p.id_proveedor}" 
                       data-personalizada="${esPersonalizada ? '1' : '0'}" 
                       checked 
                       onchange="actualizarContadorReplicar();">
            </td>
            <td>
                <span class="fw-bold text-dark d-block fs-13">🚚 ${p.razon_social}</span>
                ${p.nombre_comercial ? `<small class="text-muted fs-11">${p.nombre_comercial}</small>` : ''}
            </td>
            <td class="text-center">
                ${badgeHtml}
            </td>
        `;
        tbody.appendChild(tr);
    });

    const masterChk = document.getElementById("chkReplicarMaster");
    if (masterChk) masterChk.checked = true;

    actualizarContadorReplicar();
}

function actualizarContadorReplicar() {
    const checkboxes = document.querySelectorAll(".chk-proveedor-replicar");
    const total = checkboxes.length;
    let checkedCount = 0;
    let personalizadosSeleccionados = 0;

    checkboxes.forEach(chk => {
        if (chk.checked) {
            checkedCount++;
            if (chk.getAttribute("data-personalizada") === "1") {
                personalizadosSeleccionados++;
            }
        }
    });

    const lbl = document.getElementById("lblContadorReplicar");
    if (lbl) {
        lbl.textContent = `${checkedCount} de ${total} seleccionados`;
    }

    const masterChk = document.getElementById("chkReplicarMaster");
    if (masterChk) {
        masterChk.checked = (total > 0 && checkedCount === total);
        masterChk.indeterminate = (checkedCount > 0 && checkedCount < total);
    }

    const alerta = document.getElementById("alertaPersonalizadosSeleccionados");
    const txtAlerta = document.getElementById("txtAlertaPersonalizados");
    if (alerta && txtAlerta) {
        if (personalizadosSeleccionados > 0) {
            alerta.classList.remove("d-none");
            txtAlerta.innerHTML = `Ha seleccionado <b>${personalizadosSeleccionados} proveedor(es) con tarifas personalizadas previas</b>. Al continuar, sus tarifas personalizadas serán reemplazadas por la nueva base general.`;
        } else {
            alerta.classList.add("d-none");
        }
    }
}

function toggleReplicarMaster(masterEl) {
    const checkboxes = document.querySelectorAll(".chk-proveedor-replicar");
    checkboxes.forEach(chk => {
        chk.checked = masterEl.checked;
    });
    actualizarContadorReplicar();
}

function filtrarSeleccionReplicar(tipo) {
    const checkboxes = document.querySelectorAll(".chk-proveedor-replicar");
    checkboxes.forEach(chk => {
        if (tipo === 'todos') {
            chk.checked = true;
        } else if (tipo === 'ninguno') {
            chk.checked = false;
        } else if (tipo === 'solo_base') {
            chk.checked = (chk.getAttribute("data-personalizada") === "0");
        }
    });
    actualizarContadorReplicar();
}

function ejecutarGuardadoConReplicacion(soloBase = false) {
    const form = document.getElementById("formTarifasProveedor");
    const formData = new FormData(form);

    const seleccionados = [];
    if (!soloBase) {
        const checkboxes = document.querySelectorAll(".chk-proveedor-replicar:checked");
        checkboxes.forEach(chk => {
            formData.append("proveedores_replicar[]", chk.value);
            seleccionados.push(chk.value);
        });
    }

    Swal.fire({
        title: soloBase ? "Guardando Tarifa Base" : "Guardando y Replicando",
        text: soloBase 
            ? "Guardando únicamente la plantilla base general..." 
            : `Actualizando base y replicando a ${seleccionados.length} proveedor(es)...`,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    fetch(base_url + "/Lgs_costos/saveTarifasBaseReplicar", {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        Swal.close();
        if (data.status) {
            const modalEl = document.getElementById("modalReplicarTarifaBase");
            if (modalEl) {
                const modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
            }
            Swal.fire("¡Tarifas Guardadas!", data.msg, "success");
            loadTarifasProveedor();
        } else {
            Swal.fire("Error", data.msg, "error");
        }
    })
    .catch(err => {
        Swal.close();
        Swal.fire("Error", "Error al procesar el guardado de tarifas", "error");
    });
}

function resetTarifasProveedor() {
    const select = document.getElementById("select_tarifa_proveedor");
    const idProveedor = select.value;
    const nombreProveedor = select.options[select.selectedIndex].text;

    if (idProveedor === "0") return;

    Swal.fire({
        title: "¿Restablecer a Base General?",
        html: `Se eliminarán las tarifas personalizadas de <b>${nombreProveedor}</b>.<br><br>El proveedor volverá a heredar automáticamente los valores de la <b>Tarifa Base General</b>.`,
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        cancelButtonColor: "#6c757d",
        confirmButtonText: "Sí, restablecer",
        cancelButtonText: "Cancelar"
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: "Restableciendo...",
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            const formData = new FormData();
            formData.append("id_proveedor", idProveedor);

            fetch(base_url + "/Lgs_costos/resetTarifasProveedor", {
                method: "POST",
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                Swal.close();
                if (data.status) {
                    Swal.fire("Restablecido", data.msg, "success");
                    loadTarifasProveedor();
                } else {
                    Swal.fire("Error", data.msg, "error");
                }
            })
            .catch(err => {
                Swal.close();
                Swal.fire("Error", "No se pudo restablecer la tarifa", "error");
            });
        }
    });
}

