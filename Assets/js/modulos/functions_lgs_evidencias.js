let tableEvidencias;

document.addEventListener('DOMContentLoaded', function () {
    // Inicializar DataTable Evidencias
    tableEvidencias = $('#tableEvidencias').DataTable({
        "aProcessing": true,
        "aServerSide": false,
        "language": {
            "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json"
        },
        "ajax": {
            "url": base_url + "/Lgs_evidencias/getEnviosEvidencias",
            "dataSrc": ""
        },
        "columns": [
            { "data": "folio" },
            { "data": "trasladista" },
            { "data": "origen" },
            { 
                "data": "total_vins",
                "render": function (data, type, row) {
                    let total = parseInt(data) || 0;
                    let listaVins = row.vins_lista || '';
                    let detalleVins = [];
                    try {
                        detalleVins = typeof row.vins_detalle_json === 'string' 
                            ? JSON.parse(row.vins_detalle_json || '[]') 
                            : (row.vins_detalle_json || []);
                    } catch (e) {
                        detalleVins = [];
                    }

                    if (total === 0) {
                        return '<span class="badge bg-light text-muted border">Sin VINs</span>';
                    }

                    if (total === 1) {
                        let vinObj = (detalleVins && detalleVins.length > 0) ? detalleVins[0] : null;
                        let vinTexto = vinObj ? vinObj.vin : (listaVins || 'S/VIN');
                        let modeloTexto = vinObj ? (vinObj.modelo || '') : '';
                        
                        return `
                            <div class="d-flex flex-column align-items-start">
                                <span class="badge bg-soft-primary text-primary font-monospace fs-12 px-2 py-1 mb-1 border border-primary-subtle" title="VIN de la unidad">
                                    <i class="ri-barcode-line me-1"></i>${vinTexto}
                                </span>
                                ${modeloTexto ? `<small class="text-muted fs-11 text-truncate" style="max-width: 170px;" title="${modeloTexto}">${modeloTexto}</small>` : ''}
                                <span class="d-none">${listaVins}</span>
                            </div>
                        `;
                    } else {
                        let primerVin = (detalleVins && detalleVins.length > 0) ? detalleVins[0].vin : '';
                        return `
                            <div class="d-flex flex-column align-items-start">
                                <div class="d-flex align-items-center gap-1 mb-1">
                                    <span class="badge bg-primary fs-12 px-2 py-1 shadow-xs">
                                        <i class="ri-car-line me-1"></i>${total} VINs
                                    </span>
                                    <button type="button" class="btn btn-xs btn-outline-primary rounded-pill px-2 py-0 fs-11" onclick="fntVerDetalleVins(${row.id_envio}, '${row.folio}')" title="Ver lista de VINs asignados">
                                        <i class="ri-eye-line me-1"></i>Ver lista
                                    </button>
                                </div>
                                <span class="text-muted font-monospace fs-11" title="Primer VIN: ${primerVin}">
                                    ${primerVin ? primerVin.substring(0, 11) + '...' : ''}
                                </span>
                                <span class="d-none">${listaVins}</span>
                            </div>
                        `;
                    }
                }
            },
            { 
                "data": "total_evidencias",
                "render": function (data, type, row) {
                    let planta = parseInt(row.total_evidencias_planta) || 0;
                    let destino = parseInt(row.total_evidencias_destino) || 0;
                    let badgePlanta = `<span class="badge ${planta > 0 ? 'bg-soft-warning text-warning border border-warning' : 'bg-light text-muted border'} px-2 py-1 me-1" title="${planta} foto(s) de inspección en planta"><i class="ri-building-line me-1"></i>${planta} Planta</span>`;
                    let badgeDestino = `<span class="badge ${destino > 0 ? 'bg-soft-success text-success border border-success' : 'bg-light text-muted border'} px-2 py-1" title="${destino} foto(s) de entrega en destino"><i class="ri-map-pin-user-line me-1"></i>${destino} Destino</span>`;
                    return `<div class="d-flex align-items-center">${badgePlanta}${badgeDestino}</div>`;
                }
            },
            { 
                "data": "id_estado",
                "render": function (data, type, row) {
                    let badge = '';
                    switch(parseInt(data)) {
                        case 3: badge = '<span class="badge bg-soft-primary text-primary border border-primary fs-12"><i class="ri-checkbox-circle-line me-1"></i>Aprobado</span>'; break;
                        case 5: badge = '<span class="badge bg-soft-info text-info border border-info fs-12"><i class="ri-calendar-check-line me-1"></i>Programado</span>'; break;
                        case 6: badge = '<span class="badge bg-primary fs-12"><i class="ri-truck-line me-1"></i>En Tránsito</span>'; break;
                        case 7: badge = '<span class="badge bg-success fs-12"><i class="ri-check-double-line me-1"></i>Entregado</span>'; break;
                        default: badge = '<span class="badge bg-light text-dark fs-12">Estado ' + data + '</span>'; break;
                    }
                    return badge;
                }
            },
            {
                "data": "id_envio",
                "render": function (data, type, row) {
                    let estado = parseInt(row.id_estado);
                    let folio = row.folio;
                    let totalPlanta = parseInt(row.total_evidencias_planta) || 0;
                    
                    // 1. Botón Ver Evidencias de Entrega Planta (Requerimiento #10)
                    let btnVerPlanta = `<button class="btn btn-sm ${totalPlanta > 0 ? 'btn-soft-warning text-dark' : 'btn-light text-muted border'} rounded-pill px-2 py-1 fw-semibold me-1" onClick="fntVerEvidenciasPlanta(${data}, '${folio}')" title="Ver Evidencias de Entrega Planta (${totalPlanta} registradas)">
                                            <i class="ri-eye-line me-1"></i> Ver Planta ${totalPlanta > 0 ? `(${totalPlanta})` : ''}
                                        </button>`;

                    // 2. Botón Inspección Entrega Planta (Logística)
                    let btnPlanta = `<button class="btn btn-sm btn-soft-warning text-warning-emphasis border border-warning-subtle rounded-pill px-2 py-1 fw-semibold me-1 shadow-sm" onClick="fntAbrirEvidencias(${data}, '${folio}', ${estado}, 'planta')" title="Inspección y Evidencias de Entrega de Planta (Patio)">
                                        <i class="ri-building-line me-1"></i> Entrega Planta
                                     </button>`;

                    // 3. Botón Acuse de Recibo de Trasladista
                    let btnReciboTrasladista = `<button class="btn btn-sm btn-soft-secondary border rounded-pill px-2 py-1 fw-semibold me-1 shadow-sm" onClick="fntAbrirEvidencias(${data}, '${folio}', ${estado}, 'recibo_trasladista')" title="Acuse y Firma de Recepción del Trasladista">
                                                    <i class="ri-file-user-line me-1"></i> Recibo Trasladista
                                                </button>`;

                    // 4. Botón Entrega Destino (Distribuidor / Cliente Final) - Habilitado para En Tránsito (6) y Entregado (7)
                    let btnDestino = '';
                    let btnCerrarEntrega = '';
                    if (estado >= 6) {
                        let btnDestClass = estado === 6 ? 'btn-success shadow-sm' : 'btn-soft-success';
                        let btnDestText = estado === 6 ? 'Entrega Destino' : 'Ver Destino';
                        btnDestino = `<button class="btn btn-sm ${btnDestClass} rounded-pill px-2 py-1 fw-semibold me-1" onClick="fntAbrirEvidencias(${data}, '${folio}', ${estado}, 'destino')" title="Recepción y Cierre en Destino (Distribuidor o Cliente Final)">
                                        <i class="ri-checkbox-circle-line me-1"></i> ${btnDestText}
                                      </button>`;

                        if (estado === 6) {
                            btnCerrarEntrega = `<button class="btn btn-sm btn-outline-success rounded-pill px-2 py-1 fw-semibold me-1 shadow-xs" onClick="fntConfirmarEntregaDirecta(${data}, '${folio}')" title="Confirmar Entrega en Destino (Cierre Definitivo de Envío y Unidades)">
                                                    <i class="ri-check-double-line me-1"></i> Cerrar Entrega
                                                </button>`;
                        }
                    } else {
                        btnDestino = `<button class="btn btn-sm btn-light text-muted border rounded-pill px-2 py-1 fw-medium me-1" disabled title="Disponible al estar el viaje en tránsito">
                                        <i class="ri-lock-line me-1"></i> Entrega Destino
                                      </button>`;
                    }

                    // 5. Botón PDF Hoja de Entrega / Traspaso
                    let btnPdf = `<a href="${base_url}/Lgs_evidencias/hoja_entrega_pdf/${data}" target="_blank" class="btn btn-sm btn-soft-danger border border-danger-subtle rounded-pill px-2 py-1 fw-semibold me-1 shadow-sm" title="Imprimir / Ver Hoja de Entrega y Traspaso en PDF">
                                    <i class="ri-file-pdf-line me-1 text-danger"></i> Hoja Entrega
                                  </a>`;

                    // 6. Botón Ver Galería Completa
                    let btnGaleria = `<button class="btn btn-sm btn-soft-primary rounded-circle p-1" style="width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center;" onClick="fntAbrirGaleriaCompleta(${data}, '${folio}')" title="Ver Galería Completa">
                                        <i class="ri-gallery-line"></i>
                                      </button>`;

                    return `<div class="text-end pe-2 d-flex align-items-center justify-content-end">${btnVerPlanta}${btnPlanta}${btnReciboTrasladista}${btnDestino}${btnCerrarEntrega}${btnPdf}${btnGaleria}</div>`;
                }
            }
        ],
        "response": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]],
        "drawCallback": function(settings) {
            actualizarMetricasEvidencias(settings.json || []);
        }
    });
});

function actualizarMetricasEvidencias(data) {
    if (!Array.isArray(data)) return;
    
    let transito = data.filter(e => parseInt(e.id_estado) === 6).length;
    let entregadas = data.filter(e => parseInt(e.id_estado) === 7).length;
    let totalArchivos = data.reduce((acc, e) => acc + (parseInt(e.total_evidencias) || 0), 0);
    
    let total = data.length;
    let conEvidencia = data.filter(e => parseInt(e.total_evidencias) > 0).length;
    let cobertura = total > 0 ? Math.round((conEvidencia / total) * 100) : 0;

    if (document.getElementById('cardEvidTransito')) document.getElementById('cardEvidTransito').innerText = transito;
    if (document.getElementById('cardEvidTotalArchivos')) document.getElementById('cardEvidTotalArchivos').innerText = totalArchivos;
    if (document.getElementById('cardEvidEntregadas')) document.getElementById('cardEvidEntregadas').innerText = entregadas;
    if (document.getElementById('cardEvidCobertura')) document.getElementById('cardEvidCobertura').innerText = cobertura + '%';
}

/**
 * Filtro por buscador de texto en evidencias
 */
function filtrarTablaEvidencias(term) {
    if (typeof tableEvidencias !== "undefined" && tableEvidencias) {
        tableEvidencias.search(term).draw();
    }
}

/**
 * Filtra el DataTable por estado usando los chips de acceso rápido
 */
function filtrarEvidenciasPorChip(filtro, btn) {
    document.querySelectorAll('.chip-evid-btn').forEach(b => {
        b.classList.remove('active');
        b.classList.replace('btn-secondary', 'btn-outline-secondary');
        b.classList.replace('btn-primary', 'btn-outline-primary');
        b.classList.replace('btn-success', 'btn-outline-success');
    });
    
    if (btn) {
        btn.classList.add('active');
        if (btn.classList.contains('btn-outline-secondary')) btn.classList.replace('btn-outline-secondary', 'btn-secondary');
        if (btn.classList.contains('btn-outline-primary')) btn.classList.replace('btn-outline-primary', 'btn-primary');
        if (btn.classList.contains('btn-outline-success')) btn.classList.replace('btn-outline-success', 'btn-success');
    }
    
    if (typeof tableEvidencias !== "undefined" && tableEvidencias) {
        tableEvidencias.search(filtro).draw();
    }
}

/**
 * Permite filtrar la tabla haciendo clic en las cards superiores
 */
function filtrarEvidenciasPorCard(filtro, chipId) {
    const chipBtn = document.getElementById(chipId);
    filtrarEvidenciasPorChip(filtro, chipBtn);
    
    const tableEl = document.getElementById('tableEvidencias');
    if (tableEl) {
        tableEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
}

function fntAbrirEvidencias(idEnvio, folio, idEstado, modo = 'destino') {
    document.getElementById('id_envio_evidencia').value = idEnvio;
    document.getElementById('lblFolioEvidencia').innerText = folio;
    document.getElementById('modo_evidencia_actual').value = modo;

    const tipoChecklistInput = document.getElementById('tipo_checklist_input');
    const icoModalHeader = document.getElementById('icoModalHeader');
    const txtModalHeaderTitulo = document.getElementById('txtModalHeaderTitulo');
    const subModalHeaderDesc = document.getElementById('subModalHeaderDesc');
    const cardFormulario = document.getElementById('cardFormularioInspeccion');
    const icoFormulario = document.getElementById('icoFormulario');
    const txtTituloFormulario = document.getElementById('txtTituloFormulario');
    const badgeFormulario = document.getElementById('badgeFormularioInspeccion');
    const txtBadgeFormulario = document.getElementById('txtBadgeFormulario');
    const labelSelectUnidad = document.getElementById('labelSelectUnidad');
    const lblTituloFotos = document.getElementById('lblTituloFotos');
    const lblFotoOdometro = document.getElementById('lblFotoOdometro');
    const txtBtnAgregarExtra = document.getElementById('txtBtnAgregarExtra');
    const btnGuardarInspeccion = document.getElementById('btnGuardarInspeccion');
    const icoBtnGuardar = document.getElementById('icoBtnGuardar');
    const txtBtnGuardar = document.getElementById('txtBtnGuardar');
    const cardCierre = document.getElementById('cardCierreDestino');

    const divSubtipo = document.getElementById('divSubtipoEntrega');

    // Adaptar Modal según Modo (Planta vs Recibo Trasladista vs Destino)
    if (modo === 'planta') {
        if (divSubtipo) divSubtipo.classList.add('d-none');
        if (tipoChecklistInput) tipoChecklistInput.value = 'salida_planta';
        if (icoModalHeader) icoModalHeader.className = 'ri-building-line me-1 text-warning';
        if (txtModalHeaderTitulo) txtModalHeaderTitulo.innerText = 'Inspección de Entrega de Planta (Patio)';
        if (subModalHeaderDesc) subModalHeaderDesc.innerText = 'Control de calidad y estado físico de la unidad en patio / salida de planta (evitar omisión de fallas)';
        
        if (cardFormulario) {
            cardFormulario.style.background = '#FFFBEB';
            cardFormulario.style.borderColor = '#FDE68A';
        }
        if (icoFormulario) icoFormulario.className = 'ri-camera-lens-fill me-1 text-warning';
        if (txtTituloFormulario) txtTituloFormulario.innerText = 'Capturar Inspección de Entrega de Planta (Patio)';
        if (badgeFormulario) {
            badgeFormulario.style.background = '#FEF3C7';
            badgeFormulario.style.color = '#B45309';
            badgeFormulario.style.border = '1px solid #FCD34D';
        }
        if (txtBadgeFormulario) txtBadgeFormulario.innerText = 'Inspección en Planta / Patio';
        if (labelSelectUnidad) labelSelectUnidad.innerHTML = 'Seleccionar Unidad / VIN a Inspeccionar (Planta) <span class="text-danger">*</span>';
        if (lblTituloFotos) lblTituloFotos.innerHTML = '<i class="ri-image-line me-1 text-warning"></i> Fotografías de Inspección en Planta (5 Puntos):';
        if (lblFotoOdometro) lblFotoOdometro.innerText = '5. Odómetro / Km Salida';
        if (txtBtnAgregarExtra) txtBtnAgregarExtra.innerText = '+ Foto Adicional / Daño Detectado en Planta';
        
        if (btnGuardarInspeccion) {
            btnGuardarInspeccion.className = 'btn btn-warning px-4 rounded-pill fw-semibold fs-13 shadow-sm text-dark';
        }
        if (icoBtnGuardar) icoBtnGuardar.className = 'ri-save-line me-1';
        if (txtBtnGuardar) txtBtnGuardar.innerText = 'Guardar Inspección de Planta';

        // Ocultar siempre el cierre final en modo planta
        if (cardCierre) cardCierre.style.display = 'none';
    } else if (modo === 'recibo_trasladista') {
        if (divSubtipo) divSubtipo.classList.add('d-none');
        if (tipoChecklistInput) tipoChecklistInput.value = 'recibo_trasladista';
        if (icoModalHeader) icoModalHeader.className = 'ri-file-user-line me-1 text-primary';
        if (txtModalHeaderTitulo) txtModalHeaderTitulo.innerText = 'Acuse de Recibo del Trasladista (Entrega en Planta)';
        if (subModalHeaderDesc) subModalHeaderDesc.innerText = 'Documento, firma y validación de entrega de unidades de Planta al Trasladista';
        
        if (cardFormulario) {
            cardFormulario.style.background = '#EEF2FF';
            cardFormulario.style.borderColor = '#C7D2FE';
        }
        if (icoFormulario) icoFormulario.className = 'ri-file-signature-line me-1 text-primary';
        if (txtTituloFormulario) txtTituloFormulario.innerText = 'Cargar Acuse y Firma del Trasladista';
        if (badgeFormulario) {
            badgeFormulario.style.background = '#E0E7FF';
            badgeFormulario.style.color = '#3730A3';
            badgeFormulario.style.border = '1px solid #A5B4FC';
        }
        if (txtBadgeFormulario) txtBadgeFormulario.innerText = 'Recibo de Trasladista';
        if (labelSelectUnidad) labelSelectUnidad.innerHTML = 'Seleccionar Unidad / VIN a Entregar al Chofer <span class="text-danger">*</span>';
        if (lblTituloFotos) lblTituloFotos.innerHTML = '<i class="ri-file-shield-line me-1 text-primary"></i> Documentación de Entrega y Acuse de Recibo:';
        if (lblFotoOdometro) lblFotoOdometro.innerText = '5. Acuse Firmado / Remisión';
        if (txtBtnAgregarExtra) txtBtnAgregarExtra.innerText = '+ Documento Adicional';
        
        if (btnGuardarInspeccion) {
            btnGuardarInspeccion.className = 'btn btn-primary px-4 rounded-pill fw-semibold fs-13 shadow-sm text-white';
        }
        if (icoBtnGuardar) icoBtnGuardar.className = 'ri-checkbox-circle-line me-1';
        if (txtBtnGuardar) txtBtnGuardar.innerText = 'Guardar Acuse de Trasladista';

        if (cardCierre) cardCierre.style.display = 'none';
    } else {
        if (divSubtipo) divSubtipo.classList.remove('d-none');
        if (tipoChecklistInput) tipoChecklistInput.value = 'entrega_destino';
        if (icoModalHeader) icoModalHeader.className = 'ri-checkbox-circle-line me-1 text-success';
        if (txtModalHeaderTitulo) txtModalHeaderTitulo.innerText = 'Recepción y Evidencias de Entrega en Destino';
        if (subModalHeaderDesc) subModalHeaderDesc.innerText = 'Inspección física en destino, fotografías de recepción y cierre final de viaje';
        
        if (cardFormulario) {
            cardFormulario.style.background = '#F0FDF4';
            cardFormulario.style.borderColor = '#BBF7D0';
        }
        if (icoFormulario) icoFormulario.className = 'ri-camera-lens-fill me-1 text-success';
        if (txtTituloFormulario) txtTituloFormulario.innerText = 'Capturar Inspección de Entrega en Destino';
        if (badgeFormulario) {
            badgeFormulario.style.background = '#DCFCE7';
            badgeFormulario.style.color = '#15803D';
            badgeFormulario.style.border = '1px solid #86EFAC';
        }
        if (txtBadgeFormulario) txtBadgeFormulario.innerText = 'Recepción en Destino';
        if (labelSelectUnidad) labelSelectUnidad.innerHTML = 'Seleccionar Unidad / VIN a Entregar (Distribuidor / Cliente) <span class="text-danger">*</span>';
        if (lblTituloFotos) lblTituloFotos.innerHTML = '<i class="ri-image-line me-1 text-success"></i> Fotografías de Inspección en Destino (5 Puntos):';
        if (lblFotoOdometro) lblFotoOdometro.innerText = '5. Odómetro / Km Llegada';
        if (txtBtnAgregarExtra) txtBtnAgregarExtra.innerText = '+ Foto Adicional / Remisión Firmada';
        
        if (btnGuardarInspeccion) {
            btnGuardarInspeccion.className = 'btn btn-success px-4 rounded-pill fw-semibold fs-13 shadow-sm text-white';
        }
        if (icoBtnGuardar) icoBtnGuardar.className = 'ri-checkbox-circle-line me-1';
        if (txtBtnGuardar) txtBtnGuardar.innerText = 'Guardar Evidencia de Entrega';

        // Mostrar cierre si está en tránsito (6), ocultar si ya está entregado (7)
        if (cardCierre) {
            cardCierre.style.display = (parseInt(idEstado) === 6) ? 'block' : 'none';
        }
    }
    
    const form = document.querySelector("#formEvidenciaEntrega");
    if (form) form.reset();

    // Limpiar previsualizaciones de fotos
    ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'].forEach(pos => {
        let imgEl = document.getElementById(`img_dest_${pos}`);
        let icoEl = document.getElementById(`ico_dest_${pos}`);
        if (imgEl) { imgEl.classList.add('d-none'); imgEl.src = ''; }
        if (icoEl) icoEl.classList.remove('d-none');
    });

    // Limpiar contenedor de extras
    const contExtras = document.getElementById('contenedor_extras_entrega');
    if (contExtras) contExtras.innerHTML = '';
    window.extraEntregaCount = 0;

    // Setea fecha actual en el campo de fecha de llegada
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    const inputFecha = document.getElementById('fecha_llegada_real');
    if (inputFecha) inputFecha.value = now.toISOString().slice(0, 16);

    // Cargar selector de VINs del envío según el modo
    cargarVinsEnSelector(idEnvio, modo, idEstado);

    // Cargar evidencias asociadas
    cargarEvidenciasLista(idEnvio);
    $('#modalEvidencias').modal('show');
}

function cargarVinsEnSelector(idEnvio, modo = 'destino', idEstado = 0) {
    const select = document.getElementById('select_evid_unidad');
    if (!select) return;

    select.innerHTML = '<option value="">Cargando VINs...</option>';

    fetch(base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio)
        .then(response => response.json())
        .then(res => {
            let defaultLabel = (modo === 'planta') ? '-- Seleccionar Unidad a Inspeccionar (Planta) --' : '-- Seleccionar Unidad a Entregar --';
            let html = `<option value="">${defaultLabel}</option>`;
            const vins = res.data || [];
            vins.forEach(v => {
                let yaEntregado = (v.estado_unidad_fisico === 'ENTREGADO');
                let tagEntregado = yaEntregado ? ' [Ya Entregado]' : '';
                if (modo === 'planta' || parseInt(idEstado) === 7 || !yaEntregado) {
                    html += `<option value="${v.id_unidad}" data-vin="${v.vin}">${v.vin} (${v.modelo || 'Unidad'}) - Pos #${v.posicion_acomodo || 1}${tagEntregado}</option>`;
                }
            });
            select.innerHTML = html;
        })
        .catch(() => {
            select.innerHTML = '<option value="">-- Seleccionar Unidad --</option>';
        });
}

function actualizarVinSeleccionado(select) {
    const selectedOption = select.options[select.selectedIndex];
    const vin = selectedOption ? (selectedOption.getAttribute('data-vin') || '') : '';
    const vinInput = document.getElementById('vin_confirmado_entrega');
    if (vinInput) vinInput.value = vin;
}

function triggerFileDest(idInput) {
    const el = document.getElementById(idInput);
    if (el) el.click();
}

function previewImageDest(input, pos) {
    const img = document.getElementById(`img_dest_${pos}`);
    const ico = document.getElementById(`ico_dest_${pos}`);
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (img) {
                img.src = e.target.result;
                img.classList.remove('d-none');
            }
            if (ico) ico.classList.add('d-none');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function agregarExtraEntrega() {
    window.extraEntregaCount = (window.extraEntregaCount || 0) + 1;
    let count = window.extraEntregaCount;
    let posId = 'extra_dest_' + count;
    const modo = document.getElementById('modo_evidencia_actual') ? document.getElementById('modo_evidencia_actual').value : 'destino';
    const labelTexto = (modo === 'planta') ? `Falla / Daño Adicional ${count}` : `Adicional / Remisión ${count}`;
    
    let html = `
        <div class="col-md-4 col-6" id="div_${posId}">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="fs-11 text-muted fw-bold">${labelTexto}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 m-0 text-decoration-none fs-13" onclick="document.getElementById('div_${posId}').remove();"><i class="ri-close-circle-line"></i></button>
            </div>
            <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_${posId}')">
                <img id="img_${posId}" class="d-none">
                <i id="ico_${posId}" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
            </div>
            <input type="file" id="file_${posId}" name="extra_${count}" accept="image/*,application/pdf" class="d-none" onchange="previewImageDest(this, '${posId}')">
        </div>
    `;
    
    const cont = document.getElementById('contenedor_extras_entrega');
    if (cont) cont.insertAdjacentHTML('beforeend', html);
}

function guardarInspeccionEntrega() {
    const idEnvio = document.getElementById('id_envio_evidencia').value;
    const selectUnidad = document.getElementById('select_evid_unidad');
    const idUnidad = selectUnidad ? selectUnidad.value : '';
    const vin = document.getElementById('vin_confirmado_entrega').value;
    const modo = document.getElementById('modo_evidencia_actual') ? document.getElementById('modo_evidencia_actual').value : 'destino';

    if (!idEnvio) {
        Swal.fire("Atención", "No se ha seleccionado un envío válido.", "warning");
        return;
    }

    if (!idUnidad) {
        let msg = (modo === 'planta') 
            ? "Por favor seleccione la unidad / VIN que está inspeccionando en planta." 
            : "Por favor seleccione la unidad / VIN que está recibiendo o entregando.";
        Swal.fire("Atención", msg, "warning");
        return;
    }

    // Validar fotos obligatorias de inspección
    const inputsFotos = ['file_dest_frente', 'file_dest_atras', 'file_dest_lateral_izq', 'file_dest_lateral_der', 'file_dest_odometro'];
    let faltantes = 0;
    for(let i=0; i<inputsFotos.length; i++) {
        let el = document.getElementById(inputsFotos[i]);
        if (!el || el.files.length === 0) {
            faltantes++;
        }
    }

    if (faltantes > 0) {
        let advertencia = (modo === 'planta')
            ? "Se recomienda adjuntar las 5 fotografías de inspección en patio (Frente, Atrás, Laterales y Odómetro de Salida). ¿Desea guardar de todos modos?"
            : "Se recomienda adjuntar las 5 fotografías de inspección en destino (Frente, Atrás, Laterales y Odómetro de Llegada). ¿Desea guardar de todos modos?";
        Swal.fire({
            title: "Fotos Incompletas",
            text: advertencia,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sí, guardar",
            cancelButtonText: "Completar fotos"
        }).then(res => {
            if (res.isConfirmed) {
                enviarInspeccionEntregaForm();
            }
        });
        return;
    }

    enviarInspeccionEntregaForm();
}

function enviarInspeccionEntregaForm() {
    const form = document.getElementById('formEvidenciaEntrega');
    const formData = new FormData(form);
    const modo = document.getElementById('modo_evidencia_actual') ? document.getElementById('modo_evidencia_actual').value : 'destino';

    let loadingMsg = (modo === 'planta') 
        ? 'Subiendo fotografías de inspección de entrega de planta' 
        : 'Subiendo fotografías de recepción y entrega en destino';

    Swal.fire({
        title: 'Guardando Evidencias...',
        text: loadingMsg,
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
            let exitoMsg = (modo === 'planta')
                ? "Las evidencias de entrega de planta e inspección física de la unidad fueron guardadas exitosamente."
                : "Las evidencias de recepción y estado de la unidad fueron guardadas exitosamente.";
            Swal.fire("¡Inspección Registrada!", exitoMsg, "success");
            
            // Limpiar formulario
            form.reset();
            ['frente', 'atras', 'lateral_izq', 'lateral_der', 'odometro'].forEach(pos => {
                let imgEl = document.getElementById(`img_dest_${pos}`);
                let icoEl = document.getElementById(`ico_dest_${pos}`);
                if (imgEl) { imgEl.classList.add('d-none'); imgEl.src = ''; }
                if (icoEl) icoEl.classList.remove('d-none');
            });
            const contExtras = document.getElementById('contenedor_extras_entrega');
            if (contExtras) contExtras.innerHTML = '';

            const idEnvio = document.getElementById('id_envio_evidencia').value;
            cargarEvidenciasLista(idEnvio);
            cargarVinsEnSelector(idEnvio, modo);
            if (tableEvidencias) tableEvidencias.ajax.reload();
        } else {
            Swal.fire("Error", res.message || res.msg || "No se pudieron guardar las evidencias.", "error");
        }
    })
    .catch(() => {
        Swal.fire("Error", "Falla de comunicación al guardar la inspección.", "error");
    });
}

function cargarEvidenciasLista(idEnvio) {
    const tbody = document.getElementById('bodyListaEvidencias');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-3"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando evidencias...</td></tr>';

    fetch(base_url + '/Lgs_evidencias/getEvidenciasEnvio/' + idEnvio)
        .then(response => response.json())
        .then(objData => {
            let htmlBody = '';
            const evidencias = objData.data || [];
            
            if (document.getElementById('badgeTotalEvidenciasModal')) {
                document.getElementById('badgeTotalEvidenciasModal').innerText = evidencias.length + ' Registros';
            }

            if (objData.status && evidencias.length > 0) {
                evidencias.forEach(ev => {
                    let tipoBadge = parseInt(ev.tipo_evidencia) === 1 
                        ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1"><i class="ri-building-line me-1"></i>Entrega Planta</span>' 
                        : '<span class="badge bg-success-subtle text-success border border-success px-2 py-1"><i class="ri-map-pin-user-line me-1"></i>Entrega Destino</span>';

                    let vinBadge = ev.vin && ev.vin !== 'General de Envío'
                        ? `<span class="badge bg-light text-dark border font-monospace fs-12"><i class="ri-car-line me-1 text-primary"></i>${ev.vin}</span>`
                        : `<span class="badge bg-secondary-subtle text-muted fs-12">General Envío</span>`;

                    let fileLink = ev.ruta_archivo.startsWith('http') ? ev.ruta_archivo : (base_url + '/' + ev.ruta_archivo.replace(/^\/+/, ''));
                    
                    let previewBtn = '';
                    if (ev.id_unidad) {
                        previewBtn = `
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fs-12" onclick="verEvidenciasUnidad(${idEnvio}, ${ev.id_unidad}, '${ev.vin || ''}');" title="Ver Inspección Completa">
                                    <i class="ri-camera-lens-fill me-1"></i>Ver Fotos
                                </button>
                                <a href="${fileLink}" target="_blank" class="btn btn-sm btn-light border rounded-pill px-2 py-1 fs-12 text-muted" title="Ver Archivo">
                                    <i class="ri-external-link-line"></i>
                                </a>
                            </div>
                        `;
                    } else {
                        previewBtn = `<a href="${fileLink}" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1 fs-12" title="Abrir archivo"><i class="ri-external-link-line me-1"></i>Ver Archivo</a>`;
                    }

                    let usuarioStr = ev.usuario_nombre && ev.usuario_nombre.trim() !== '' ? `<small class="d-block text-muted fs-11"><i class="ri-user-line me-1"></i>${ev.usuario_nombre}</small>` : '';

                    htmlBody += `
                        <tr>
                            <td>${tipoBadge}</td>
                            <td>${vinBadge}</td>
                            <td>${previewBtn}</td>
                            <td><span class="text-dark">${ev.observaciones || '-'}</span></td>
                            <td><small class="text-muted">${ev.created_at || '-'}</small>${usuarioStr}</td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" onclick="borrarEvidencia(${ev.id_evidencia}, ${idEnvio});" title="Eliminar">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
            } else {
                htmlBody = '<tr><td colspan="6" class="text-center text-muted py-4"><i class="ri-image-line fs-2 d-block mb-1 opacity-50"></i>No hay evidencias registradas para este envío.</td></tr>';
            }
            
            tbody.innerHTML = htmlBody;
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Error al consultar evidencias.</td></tr>';
        });
}

function verEvidenciasUnidad(idEnvio, idUnidad, vin) {
    const titleEl = document.getElementById('titleModalVerEvidencias');
    const subEl = document.getElementById('subModalVerEvidencias');
    const gridEl = document.getElementById('gridVerEvidencias');
    const obsCont = document.getElementById('contenedorObservacionesEvidencias');

    if (titleEl) titleEl.innerHTML = `Inspección de Unidad: <span class="text-primary font-monospace">${vin}</span>`;
    if (subEl) subEl.innerText = `Envío #${idEnvio} · Fotografías y observaciones de inspección`;
    if (obsCont) obsCont.innerHTML = '';
    if (gridEl) gridEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Cargando evidencias fotográficas...</p></div>';

    const modal = new bootstrap.Modal(document.getElementById('modalVerEvidencias'));
    modal.show();

    fetch(base_url + '/Lgs_ejecucion/getEvidenciasUnidad/' + idEnvio + '/' + idUnidad)
        .then(response => response.json())
        .then(res => {
            if (!res.status || !res.data) {
                gridEl.innerHTML = '<div class="alert alert-warning text-center">No se encontraron evidencias registradas para esta unidad.</div>';
                return;
            }

            const data = res.data;
            const checklist = (data && data.checklist) ? data.checklist : { comentarios: (Array.isArray(data) && data[0] ? data[0].comentarios : '') };
            const fotos = Array.isArray(data) ? data : (data.fotos || []);

            if (checklist.comentarios && checklist.comentarios.trim() !== '') {
                obsCont.innerHTML = `
                    <div class="card border-0 shadow-sm rounded-3 p-3 bg-white border-start border-4 border-info">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="ri-chat-1-line text-info fs-16"></i>
                            <strong class="text-dark fs-13">Observaciones de Inspección:</strong>
                        </div>
                        <p class="text-muted mb-0 fs-13 ps-4">${checklist.comentarios}</p>
                    </div>
                `;
            }

            const etiquetas = {
                'frente': { label: 'Frente', icon: 'ri-arrow-up-circle-line' },
                'atras': { label: 'Atrás / Posterior', icon: 'ri-arrow-down-circle-line' },
                'lateral_izq': { label: 'Lateral Izquierdo', icon: 'ri-arrow-left-circle-line' },
                'lateral_der': { label: 'Lateral Derecho', icon: 'ri-arrow-right-circle-line' },
                'odometro': { label: 'Odómetro / Tablero', icon: 'ri-dashboard-3-line' }
            };

            let htmlGrid = '<div class="row g-3">';
            if (fotos.length > 0) {
                fotos.forEach((foto, idx) => {
                    const info = etiquetas[foto.tipo_foto] || { label: 'Foto Adicional ' + (idx + 1), icon: 'ri-image-line' };
                    let imgUrl = foto.ruta_archivo.startsWith('http') ? foto.ruta_archivo : (base_url + '/' + foto.ruta_archivo.replace(/^\/+/, ''));

                    htmlGrid += `
                        <div class="col-md-4 col-sm-6">
                            <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100 bg-white">
                                <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                    <span class="fs-12 fw-bold text-dark"><i class="${info.icon} me-1 text-primary"></i> ${info.label}</span>
                                    <small class="text-muted fs-10">${foto.created_at || ''}</small>
                                </div>
                                <div class="position-relative" style="height: 180px; background: #000;">
                                    <img src="${imgUrl}" class="w-100 h-100 object-fit-cover" alt="${info.label}" style="cursor: pointer;" onclick="window.open('${imgUrl}', '_blank');">
                                </div>
                            </div>
                        </div>
                    `;
                });
            } else {
                htmlGrid += '<div class="col-12"><div class="alert alert-info text-center py-4 mb-0"><i class="ri-image-line fs-2 d-block mb-1"></i>No hay fotos registradas en esta inspección.</div></div>';
            }
            htmlGrid += '</div>';

            gridEl.innerHTML = htmlGrid;
        })
        .catch(() => {
            gridEl.innerHTML = '<div class="alert alert-danger text-center">Error al consultar las evidencias fotográficas.</div>';
        });
}

function borrarEvidencia(idEvidencia, idEnvio) {
    Swal.fire({
        title: '¿Eliminar Evidencia?',
        text: "¿Realmente desea borrar este registro multimedia?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(base_url + '/Lgs_evidencias/delete/' + idEvidencia, {
                method: 'POST'
            })
            .then(response => response.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    Swal.fire("Eliminado", objData.message || "Evidencia eliminada correctamente.", "success");
                    cargarEvidenciasLista(idEnvio);
                    if (tableEvidencias) tableEvidencias.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || "No se pudo eliminar la evidencia.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Error de red al eliminar la evidencia.", "error");
            });
        }
    });
}

function confirmarCierreFinal() {
    const idEnvio = document.getElementById('id_envio_evidencia').value;
    const fechaLlegada = document.getElementById('fecha_llegada_real').value;

    if (!fechaLlegada) {
        Swal.fire("Atención", "Ingrese la fecha y hora real de llegada a destino.", "warning");
        return;
    }

    Swal.fire({
        title: 'Confirmar Entrega Final',
        text: "¿Confirmar que el envío llegó a su destino y finalizar monitoreo?",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, finalizar envío',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('fecha_llegada_real', fechaLlegada);

            fetch(base_url + '/Lgs_evidencias/confirmarEntrega', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    $('#modalEvidencias').modal('hide');
                    Swal.fire("¡Envío Completado!", objData.message || "El envío ha sido completado exitosamente.", "success");
                    if (tableEvidencias) tableEvidencias.ajax.reload();
                } else {
                    Swal.fire("Error", objData.message || "No se pudo confirmar la entrega.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Falla de red al confirmar entrega final.", "error");
            });
        }
    });
}

/**
 * Abre el modal con todas las evidencias del envío organizadas por etapa (Planta y Destino)
 */
function fntAbrirGaleriaCompleta(idEnvio, folio) {
    const titleEl = document.getElementById('titleModalVerEvidencias');
    const subEl = document.getElementById('subModalVerEvidencias');
    const gridEl = document.getElementById('gridVerEvidencias');
    const obsCont = document.getElementById('contenedorObservacionesEvidencias');

    if (titleEl) titleEl.innerHTML = `Galería Completa: <span class="text-primary font-monospace">${folio}</span>`;
    if (subEl) subEl.innerText = `Envío #${idEnvio} · Inspecciones fotográficas de Planta y Destino`;
    if (obsCont) obsCont.innerHTML = '';
    if (gridEl) gridEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Cargando galería fotográfica...</p></div>';

    const modal = new bootstrap.Modal(document.getElementById('modalVerEvidencias'));
    modal.show();

    fetch(base_url + '/Lgs_evidencias/getEvidenciasEnvio/' + idEnvio)
        .then(response => response.json())
        .then(res => {
            const fotos = res.data || [];
            if (!res.status || fotos.length === 0) {
                gridEl.innerHTML = '<div class="alert alert-info text-center py-4 mb-0"><i class="ri-image-line fs-2 d-block mb-1"></i>No hay evidencias registradas para este envío aún.</div>';
                return;
            }

            const fotosPlanta = fotos.filter(f => parseInt(f.tipo_evidencia) === 1);
            const fotosDestino = fotos.filter(f => parseInt(f.tipo_evidencia) === 2);

            let htmlTabs = `
                <ul class="nav nav-pills nav-custom nav-custom-light mb-3" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active fw-semibold" data-bs-toggle="pill" href="#tabGaleriaTodas" role="tab">
                            <i class="ri-gallery-line me-1"></i> Todas (${fotos.length})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-warning-emphasis" data-bs-toggle="pill" href="#tabGaleriaPlanta" role="tab">
                            <i class="ri-building-line me-1 text-warning"></i> Entrega Planta (${fotosPlanta.length})
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link fw-semibold text-success" data-bs-toggle="pill" href="#tabGaleriaDestino" role="tab">
                            <i class="ri-map-pin-user-line me-1 text-success"></i> Entrega Destino (${fotosDestino.length})
                        </a>
                    </li>
                </ul>
                <div class="tab-content text-muted">
                    <div class="tab-pane active" id="tabGaleriaTodas" role="tabpanel">
                        ${renderGridCardsEvidencias(fotos)}
                    </div>
                    <div class="tab-pane" id="tabGaleriaPlanta" role="tabpanel">
                        ${renderGridCardsEvidencias(fotosPlanta)}
                    </div>
                    <div class="tab-pane" id="tabGaleriaDestino" role="tabpanel">
                        ${renderGridCardsEvidencias(fotosDestino)}
                    </div>
                </div>
            `;

            gridEl.innerHTML = htmlTabs;
        })
        .catch(() => {
            gridEl.innerHTML = '<div class="alert alert-danger text-center">Error al consultar la galería fotográfica del envío.</div>';
        });
}

function renderGridCardsEvidencias(lista) {
    if (!lista || lista.length === 0) {
        return '<div class="alert alert-light text-center py-4 border"><i class="ri-image-line fs-2 d-block mb-1 text-muted"></i>No hay fotografías en esta etapa.</div>';
    }

    let html = '<div class="row g-3">';
    lista.forEach((f, idx) => {
        let isPlanta = parseInt(f.tipo_evidencia) === 1;
        let badgeTipo = isPlanta 
            ? '<span class="badge bg-warning-subtle text-warning-emphasis border border-warning fs-10"><i class="ri-building-line me-1"></i>Planta / Patio</span>'
            : '<span class="badge bg-success-subtle text-success border border-success fs-10"><i class="ri-map-pin-user-line me-1"></i>Destino</span>';

        let vinStr = f.vin && f.vin !== 'General de Envío' ? `<span class="badge bg-dark font-monospace text-white fs-10">${f.vin}</span>` : '';
        let imgUrl = f.ruta_archivo.startsWith('http') ? f.ruta_archivo : (base_url + '/' + f.ruta_archivo.replace(/^\/+/, ''));
        let usuario = f.usuario_nombre && f.usuario_nombre.trim() !== '' ? `<small class="text-muted fs-10 d-block"><i class="ri-user-line me-1"></i>${f.usuario_nombre}</small>` : '';

        html += `
            <div class="col-md-4 col-sm-6">
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden h-100 bg-white">
                    <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                        <div>${badgeTipo} ${vinStr}</div>
                        <small class="text-muted fs-10">${f.created_at || ''}</small>
                    </div>
                    <div class="position-relative" style="height: 180px; background: #000;">
                        <img src="${imgUrl}" class="w-100 h-100 object-fit-cover" alt="Evidencia" style="cursor: pointer;" onclick="window.open('${imgUrl}', '_blank');">
                    </div>
                    <div class="card-body p-2">
                        <p class="fs-11 text-dark mb-1 text-truncate" title="${f.observaciones || 'Sin observaciones'}">${f.observaciones || 'Fotografía de inspección'}</p>
                        ${usuario}
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    return html;
}

/**
 * Abre la galería filtrando y activando la pestaña de evidencias de Planta directamente
 */
function fntVerEvidenciasPlanta(idEnvio, folio) {
    fntAbrirGaleriaCompleta(idEnvio, folio);
    setTimeout(() => {
        let tabPlanta = document.querySelector('a[href="#tabGaleriaPlanta"]');
        if (tabPlanta) {
            let tab = new bootstrap.Tab(tabPlanta);
            tab.show();
        }
    }, 450);
}

/**
 * Despliega el modal con la lista detallada de VINs asignados al envío
 */
function fntVerDetalleVins(idEnvio, folio) {
    const lblFolio = document.getElementById('lblFolioModalVins');
    const tbody = document.getElementById('bodyListaVinsEnvio');
    const footerTxt = document.getElementById('lblTotalVinsModalFooter');

    if (lblFolio) lblFolio.innerText = folio;
    if (tbody) tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando unidades...</td></tr>';

    $('#modalDetalleVins').modal('show');

    fetch(base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio)
        .then(res => res.json())
        .then(res => {
            const vins = res.data || [];
            if (footerTxt) footerTxt.innerText = `${vins.length} unidad(es) asignada(s)`;

            if (vins.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No hay unidades asignadas a este envío.</td></tr>';
                return;
            }

            let html = '';
            vins.forEach((v, idx) => {
                let badgeEstado = '';
                switch (v.estado_unidad_fisico) {
                    case 'ENTREGADO':
                        badgeEstado = '<span class="badge bg-success"><i class="ri-checkbox-circle-line me-1"></i>Entregado</span>';
                        break;
                    case 'EN_RUTA':
                        badgeEstado = '<span class="badge bg-primary"><i class="ri-truck-line me-1"></i>En Ruta</span>';
                        break;
                    case 'EN_ENTREGAS':
                        badgeEstado = '<span class="badge bg-info"><i class="ri-time-line me-1"></i>En Carga</span>';
                        break;
                    default:
                        badgeEstado = '<span class="badge bg-warning text-dark"><i class="ri-parking-box-line me-1"></i>En Patio</span>';
                        break;
                }

                html += `
                    <tr>
                        <td class="text-center fw-bold text-muted">${v.posicion_acomodo || (idx + 1)}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="font-monospace fw-bold text-dark fs-13">${v.vin}</span>
                                <button type="button" class="btn btn-sm btn-link text-muted p-0" onclick="navigator.clipboard.writeText('${v.vin}'); Swal.fire({toast:true, position:'top-end', icon:'success', title:'VIN copiado', showConfirmButton:false, timer:1500});" title="Copiar VIN">
                                    <i class="ri-file-copy-line"></i>
                                </button>
                            </div>
                        </td>
                        <td>${v.modelo || 'Unidad'}</td>
                        <td class="text-center">${badgeEstado}</td>
                        <td class="text-end pe-3">
                            <button type="button" class="btn btn-sm btn-soft-primary rounded-pill px-2 py-0 fs-11" onclick="$('#modalDetalleVins').modal('hide'); verEvidenciasUnidad(${idEnvio}, ${v.id_unidad}, '${v.vin}');" title="Ver evidencias de esta unidad">
                                <i class="ri-eye-line me-1"></i>Evidencias
                            </button>
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error al consultar las unidades del envío.</td></tr>';
        });
}

/**
 * Permite confirmar la entrega final en destino de forma directa y sincronizada en BD
 */
function fntConfirmarEntregaDirecta(idEnvio, folio) {
    let now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    let fechaActual = now.toISOString().slice(0, 16);

    Swal.fire({
        title: `Confirmar Entrega en Destino`,
        html: `
            <div class="text-start">
                <p class="text-muted fs-13 mb-3">
                    ¿Confirmar que el envío <strong class="text-primary font-monospace">${folio}</strong> llegó a su destino final?
                    Todas sus unidades asociadas se actualizarán como <strong>Entregadas</strong> tanto en este módulo como en la <strong>Bandeja de Unidades</strong> y el <strong>Monitoreo GPS</strong>.
                </p>
                <label class="form-label fw-bold fs-12 text-uppercase text-muted">Fecha y Hora Real de Llegada:</label>
                <input type="datetime-local" id="swal_fecha_llegada_directa" class="form-control" value="${fechaActual}">
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-check-double-line me-1"></i> Sí, Finalizar Traslado',
        cancelButtonText: 'Cancelar',
        preConfirm: () => {
            const fecha = document.getElementById('swal_fecha_llegada_directa').value;
            if (!fecha) {
                Swal.showValidationMessage('Debe ingresar la fecha y hora de llegada');
            }
            return { fecha: fecha };
        }
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            Swal.fire({
                title: 'Finalizando Envío...',
                text: 'Actualizando estatus del envío y unidades a Entregado.',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('fecha_llegada_real', result.value.fecha);

            fetch(base_url + '/Lgs_evidencias/confirmarEntrega', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(objData => {
                if (objData.status === 'success' || objData.status === true) {
                    Swal.fire("¡Traslado Finalizado!", objData.message || "El envío y sus unidades han sido marcados como Entregados exitosamente.", "success");
                    if (typeof tableEvidencias !== 'undefined' && tableEvidencias) {
                        tableEvidencias.ajax.reload();
                    }
                } else {
                    Swal.fire("Error", objData.message || "No se pudo confirmar la entrega.", "error");
                }
            })
            .catch(() => {
                Swal.fire("Error", "Error de comunicación con el servidor.", "error");
            });
        }
    });
}


