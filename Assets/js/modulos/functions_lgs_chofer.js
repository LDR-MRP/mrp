let activeHtml5QrcodeScanner = null;

document.addEventListener('DOMContentLoaded', function () {
    cargarEnviosChofer();
    setupZipDragAndDrop('contenedor_preview_zip_chofer', 'file_zip_chofer');
});

function cargarEnviosChofer() {
    const listEl = document.getElementById('lista-envios');
    if (!listEl) return;

    fetch(base_url + '/Lgs_ejecucion/getEnviosChofer')
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' || res.status === true) {
                let html = '';
                const viajes = res.data || [];
                if (viajes.length > 0) {
                    viajes.forEach(v => {
                        let badgeEstado = '';
                        if (v.id_estado == 5) {
                            badgeEstado = '<span class="badge bg-warning text-dark"><i class="ri-time-line me-1"></i>Por recolectar</span>';
                        } else if (v.id_estado == 6) {
                            badgeEstado = '<span class="badge bg-primary"><i class="ri-truck-line me-1"></i>En ruta</span>';
                        }
                        
                        html += `
                            <div class="card card-custom p-3" onclick="verDetalleViaje(${v.id_envio}, '${v.folio}', '${v.origen}', '${v.destino}', '${v.origen_dir || ''}')">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-primary">${v.folio}</span>
                                    ${badgeEstado}
                                </div>
                                <div class="d-flex justify-content-between text-muted fs-13 mb-2">
                                    <div><strong>De:</strong> ${v.origen}</div>
                                    <div><strong>A:</strong> ${v.destino}</div>
                                </div>
                                <div class="fs-12 text-muted mt-2 border-top pt-2">
                                    <i class="ri-calendar-event-line me-1"></i>Fecha pactada: ${v.fecha_confirmada_recoleccion || 'No programada'}
                                </div>
                            </div>
                        `;
                    });
                } else {
                    html = '<div class="text-center py-5 text-muted"><i class="ri-information-line fs-1 me-1 d-block mb-2"></i>No tienes viajes activos programados.</div>';
                }
                listEl.innerHTML = html;
            } else {
                listEl.innerHTML = '<div class="alert alert-danger text-center">Error al cargar viajes.</div>';
            }
        })
        .catch(err => {
            listEl.innerHTML = '<div class="alert alert-danger text-center">Falla al comunicar con el servidor.</div>';
        });
}

function verDetalleViaje(idEnvio, folio, origen, destino, origenDir) {
    document.getElementById('section-envios').classList.add('d-none');
    document.getElementById('section-detalle-envio').classList.remove('d-none');
    
    document.getElementById('lblFolioViaje').innerText = folio;
    document.getElementById('lblOrigenViaje').innerText = origen;
    document.getElementById('lblDestinoViaje').innerText = destino;
    document.getElementById('lblOrigenDir').innerText = origenDir || 'Dirección no disponible';

    cargarVinsChecklist(idEnvio);
}

function mostrarListaEnvios() {
    document.getElementById('section-detalle-envio').classList.add('d-none');
    document.getElementById('section-envios').classList.remove('d-none');
    cargarEnviosChofer();
}

function cargarVinsChecklist(idEnvio) {
    const listEl = document.getElementById('lista-vins-checklist');
    listEl.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div> Cargando unidades...</div>';

    fetch(base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio)
        .then(response => response.json())
        .then(res => {
            let html = '';
            const vins = res.data || [];
            if (vins.length > 0) {
                vins.forEach(vin => {
                    let statusBtn = '';
                    let borderClass = 'border-start border-primary border-4';
                    
                    if (vin.estado_unidad_fisico === 'EN_ENTREGAS') {
                        statusBtn = `<span class="badge bg-soft-success text-success border border-success px-3 py-2 rounded-pill fs-12"><i class="ri-check-double-line me-1"></i> Inspeccionado (En entregas)</span>`;
                        borderClass = 'border-start border-success border-4';
                    } else if (vin.estado_unidad_fisico === 'EN_RUTA') {
                        statusBtn = `<span class="badge bg-soft-primary text-primary border border-primary px-3 py-2 rounded-pill fs-12"><i class="ri-truck-line me-1"></i> En Ruta</span>`;
                        borderClass = 'border-start border-primary border-4';
                    } else {
                        statusBtn = `<button class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" onclick="abrirInspeccionVin(${idEnvio}, ${vin.id_unidad}, '${vin.vin}');"><i class="ri-camera-lens-line me-1"></i> Iniciar Inspección</button>`;
                    }

                    html += `
                        <div class="card card-custom p-3 bg-white ${borderClass}">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fw-bold fs-14 text-dark">${vin.vin}</span>
                                <span class="badge bg-light text-dark">Pos #${vin.posicion_acomodo || 1}</span>
                            </div>
                            <div class="text-muted fs-12 mb-3">
                                <strong>Modelo:</strong> ${vin.modelo || 'Unidad'} | <strong>Color:</strong> ${vin.color || 'Blanco'}
                            </div>
                            <div class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                                <span class="fs-12 text-muted">Inspección Física:</span>
                                ${statusBtn}
                            </div>
                        </div>
                    `;
                });
            } else {
                html = '<div class="text-center py-4 text-muted">No hay unidades asignadas en este viaje.</div>';
            }
            listEl.innerHTML = html;
        });
}

function abrirInspeccionVin(idEnvio, idUnidad, vin) {
    document.getElementById('formInspeccionVin').reset();
    document.getElementById('chk_id_envio').value = idEnvio;
    document.getElementById('chk_id_unidad').value = idUnidad;
    document.getElementById('lblVinInspeccion').innerText = vin;
    document.getElementById('vin_confirmado').value = '';
    
    // Ocultar previsualizaciones de imágenes obligatorias
    ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'].forEach(pos => {
        let imgEl = document.getElementById(`img_${pos}`);
        let icoEl = document.getElementById(`ico_${pos}`);
        if(imgEl) { imgEl.classList.add('d-none'); imgEl.src = ''; }
        if(icoEl) icoEl.classList.remove('d-none');
    });

    // Limpiar extras dinámicos
    document.getElementById('contenedor_extras_chofer').innerHTML = '';
    window.extraEvidenciaCountChofer = 0;

    // Resetear pestaña de Carga Masiva (ZIP)
    if (document.getElementById('file_zip_chofer')) document.getElementById('file_zip_chofer').value = '';
    limpiarZipInput('file_zip_chofer', 'contenedor_preview_zip_chofer');
    const tabInd = document.getElementById('tab-fotos-ind-chofer');
    if (tabInd && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        const bsTab = bootstrap.Tab.getOrCreateInstance(tabInd);
        bsTab.show();
    }

    const modal = new bootstrap.Modal(document.getElementById('modalInspeccionVin'));
    modal.show();
}

function triggerFile(idInput) {
    document.getElementById(idInput).click();
}

function previewImage(input, pos) {
    const img = document.getElementById(`img_${pos}`);
    const ico = document.getElementById(`ico_${pos}`);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            img.src = e.target.result;
            img.classList.remove('d-none');
            ico.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function previewZipFile(input, targetId) {
    const card = document.getElementById(targetId);
    if (!card) return;
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
        card.innerHTML = `
            <div class="d-flex align-items-center justify-content-between p-2 bg-soft-warning border border-warning rounded-3 shadow-xs">
                <div class="d-flex align-items-center">
                    <i class="ri-folder-zip-fill fs-2 text-warning me-2"></i>
                    <div class="text-start">
                        <span class="mb-0 fw-bold fs-12 text-dark d-block text-truncate" style="max-width: 180px;">${file.name}</span>
                        <small class="text-muted fs-11">${sizeMb} MB · ZIP Listo</small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 26px; height: 26px;" onclick="limpiarZipInput('${input.id}', '${targetId}')" title="Quitar">
                    <i class="ri-close-line"></i>
                </button>
            </div>
        `;
    }
}

function limpiarZipInput(inputId, targetId) {
    const input = document.getElementById(inputId);
    if (input) input.value = '';
    const card = document.getElementById(targetId);
    if (card) {
        card.innerHTML = `
            <div class="p-3 text-center border-dashed rounded-3 bg-light" style="border: 2px dashed #CBD5E1; cursor: pointer;" onclick="document.getElementById('${inputId}').click();">
                <i class="ri-folder-zip-line fs-1 text-warning mb-1 d-block"></i>
                <span class="fs-12 fw-bold text-dark d-block">Seleccionar archivo .ZIP</span>
                <small class="text-muted d-block mt-1">Sube el ZIP con las fotos de la unidad.</small>
            </div>
        `;
    }
}

function setupZipDragAndDrop(containerId, inputId) {
    const el = document.getElementById(containerId);
    const input = document.getElementById(inputId);
    if (!el || !input) return;

    ['dragenter', 'dragover'].forEach(eventName => {
        el.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            el.style.borderColor = '#F59E0B';
            el.style.backgroundColor = '#FEF3C7';
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        el.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            el.style.borderColor = '#CBD5E1';
            el.style.backgroundColor = '#F8FAFC';
        }, false);
    });

    el.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            input.files = files;
            previewZipFile(input, containerId);
        }
    }, false);
}

function iniciarEscaneo() {
    const escaneoContainer = document.getElementById('escaneo-container');
    escaneoContainer.classList.remove('d-none');

    if (activeHtml5QrcodeScanner) {
        activeHtml5QrcodeScanner.clear();
    }

    activeHtml5QrcodeScanner = new Html5QrcodeScanner(
        "reader", 
        { fps: 10, qrbox: {width: 250, height: 250} },
        false
    );

    activeHtml5QrcodeScanner.render((decodedText, decodedResult) => {
        document.getElementById('vin_confirmado').value = decodedText;
        activeHtml5QrcodeScanner.clear();
        escaneoContainer.classList.add('d-none');
        Swal.fire("Código Leído", "VIN escaneado: " + decodedText, "success");
    }, (error) => {
        // Ignorar errores de escaneo continuos
    });
}

function guardarInspeccion() {
    const vinConfirmado = document.getElementById('vin_confirmado').value.trim();
    const vinEsperado = document.getElementById('lblVinInspeccion').innerText.trim();

    if (vinConfirmado !== vinEsperado) {
        Swal.fire("Atención", "El VIN ingresado/escaneado no coincide con el asignado a la unidad.", "error");
        return;
    }

    const tabZipActive = document.getElementById('tab-fotos-zip-chofer')?.classList.contains('active');
    const zipInput = document.getElementById('file_zip_chofer');

    if (tabZipActive) {
        if (!zipInput || zipInput.files.length === 0) {
            Swal.fire("Atención", "Debe seleccionar un archivo .ZIP con las fotos de la unidad.", "warning");
            return;
        }
    } else {
        // Validar fotos obligatorias individuales
        const inputsFotos = ['file_frente', 'file_atras', 'file_lateral_izq', 'file_lateral_der', 'file_odometro'];
        for(let i=0; i<inputsFotos.length; i++) {
            if (document.getElementById(inputsFotos[i]).files.length === 0) {
                Swal.fire("Atención", "Debe cargar todas las fotos de inspección obligatorias antes de guardar, o utilizar la pestaña de Carga Masiva (ZIP).", "warning");
                return;
            }
        }
    }

    const form = document.getElementById('formInspeccionVin');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Guardando Inspección...',
        text: 'Subiendo evidencias al servidor',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    fetch(base_url + '/Lgs_ejecucion/guardarChecklistTrasladista', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success' || res.status === true) {
            Swal.fire("¡Inspeccionado!", "Unidad verificada y cargada exitosamente.", "success");
            const modalEl = document.getElementById('modalInspeccionVin');
            const modal = bootstrap.Modal.getInstance(modalEl);
            modal.hide();
            
            if (activeHtml5QrcodeScanner) {
                try { activeHtml5QrcodeScanner.clear(); } catch(e){}
            }

            cargarVinsChecklist(document.getElementById('chk_id_envio').value);
        } else {
            Swal.fire("Error", res.message || res.msg || "No se pudo guardar la inspección.", "error");
        }
    })
    .catch(err => {
        Swal.fire("Error", "Falla de red o de servidor.", "error");
    });
}

function agregarEvidenciaExtraChofer() {
    window.extraEvidenciaCountChofer = (window.extraEvidenciaCountChofer || 0) + 1;
    let count = window.extraEvidenciaCountChofer;
    let posId = 'extra_' + count;
    
    let html = `
        <div class="col-6 mt-2" id="div_${posId}">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fs-11 text-muted fw-bold">Adicional ${count}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 m-0 text-decoration-none fs-13" onclick="document.getElementById('div_${posId}').remove();"><i class="ri-close-circle-line"></i></button>
            </div>
            <div class="photo-preview text-muted" onclick="triggerFile('file_${posId}')">
                <img id="img_${posId}" class="d-none">
                <i id="ico_${posId}" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
            </div>
            <input type="file" id="file_${posId}" name="${posId}" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, '${posId}')">
        </div>
    `;
    
    document.getElementById('contenedor_extras_chofer').insertAdjacentHTML('beforeend', html);
}
