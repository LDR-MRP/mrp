let activeHtml5QrcodeScanner = null;
let currentEnvioVins = [];

document.addEventListener('DOMContentLoaded', function () {
    cargarViajesTransito();
    setupZipDragAndDrop('contenedor_preview_zip_entrega', 'file_zip_entrega');
});

function cargarViajesTransito() {
    const select = document.getElementById('select-viaje-entrega');
    if (!select) return;

    fetch(base_url + '/Lgs_ejecucion/getEnviosChofer')
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' || res.status === true) {
                const viajes = res.data || [];
                // Filtrar solo los envíos en tránsito (id_estado = 6)
                const filtrados = viajes.filter(v => v.id_estado == 6);
                
                let html = '<option value="">Seleccione el folio de viaje...</option>';
                if (filtrados.length > 0) {
                    filtrados.forEach(v => {
                        html += `<option value="${v.id_envio}">${v.folio} (Origen: ${v.origen})</option>`;
                    });
                } else {
                    html = '<option value="">No tienes viajes activos en tránsito.</option>';
                }
                select.innerHTML = html;
            }
        });
}

function cargarDetalleEntrega(idEnvio) {
    const container = document.getElementById('detalle-entrega-container');
    if (!idEnvio) {
        container.classList.add('d-none');
        return;
    }

    document.getElementById('ent_id_envio').value = idEnvio;
    container.classList.remove('d-none');

    // Consultar detalles de los VINS y el destino
    fetch(base_url + '/Lgs_ejecucion/getDetalleDespacho/' + idEnvio)
        .then(response => response.json())
        .then(res => {
            const vins = res.data || [];
            currentEnvioVins = vins;
            
            if (vins.length > 0) {
                // Asignar primer destino en pantalla
                document.getElementById('lblDestinoEntrega').innerText = vins[0].chofer || 'Destinatario Cliente'; 
                document.getElementById('lblDireccionEntrega').innerText = 'Dirección fiscal/comercial registrada del destino';

                // Renderizar los VINs para confirmación individual
                const listEl = document.getElementById('lista-vins-entrega');
                let html = '';
                
                vins.forEach(vin => {
                    let checkboxIcon = vin.estado_unidad_fisico === 'ENTREGADO'
                        ? '<span class="badge bg-soft-success text-success border border-success px-3 py-2 rounded-pill fs-12"><i class="ri-check-double-line me-1"></i> Entregado</span>'
                        : `<button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-pill fw-semibold" onclick="marcarVinEntregado(${idEnvio}, ${vin.id_unidad}, '${vin.vin}')"><i class="ri-checkbox-circle-line me-1"></i> Confirmar Recepción</button>`;

                    html += `
                        <div class="card card-custom p-3 bg-white mb-2">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <strong class="fs-14 d-block">${vin.vin}</strong>
                                    <small class="text-muted">${vin.modelo} | ${vin.color}</small>
                                </div>
                                <div>
                                    ${checkboxIcon}
                                </div>
                            </div>
                        </div>
                    `;
                });
                listEl.innerHTML = html;
            }
        });
}

function iniciarEscaneoQR() {
    const idEnvio = document.getElementById('ent_id_envio').value;
    if (!idEnvio) {
        Swal.fire("Atención", "Seleccione primero un viaje para validar su entrega.", "warning");
        return;
    }

    const escaneoContainer = document.getElementById('qr-escaneo-container');
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
        // Pausar/limpiar cámara
        if (activeHtml5QrcodeScanner) {
            try { activeHtml5QrcodeScanner.clear(); } catch(e){}
        }
        escaneoContainer.classList.add('d-none');

        Swal.fire({
            title: 'Validando QR...',
            text: 'Verificando con el servidor',
            allowOutsideClick: false,
            didOpen: () => { Swal.showLoading(); }
        });

        let formData = new FormData();
        formData.append('id_envio', idEnvio);
        formData.append('texto_qr', decodedText);

        fetch(base_url + '/Lgs_ejecucion/validarQrCliente', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(res => {
            if (res.status === 'success' || res.status === true) {
                document.getElementById('qr_cliente_validado').value = "1";
                
                const statusEl = document.getElementById('lblStatusQR');
                statusEl.innerHTML = `<i class="ri-checkbox-circle-line me-1"></i>${res.message || 'QR Validado Correctamente'}`;
                statusEl.className = 'text-success fw-bold fs-13 mt-2';

                Swal.fire("¡Cliente / Destino Validado!", res.message || "Identidad y localización validadas correctamente.", "success");
            } else {
                document.getElementById('qr_cliente_validado').value = "0";
                const statusEl = document.getElementById('lblStatusQR');
                statusEl.innerHTML = '<i class="ri-close-circle-line me-1"></i>QR Inválido o No Coincide';
                statusEl.className = 'text-danger fw-bold fs-13 mt-2';

                Swal.fire("QR Inválido", res.message || "El código escaneado no corresponde a este viaje o destino.", "error");
            }
        })
        .catch(err => {
            document.getElementById('qr_cliente_validado').value = "0";
            Swal.fire("Error", "Falla de comunicación al validar el código QR.", "error");
        });
    }, (error) => {
        // Ignorar errores de frame
    });
}

function marcarVinEntregado(idEnvio, idUnidad, vin) {
    Swal.fire({
        title: 'Confirmar Unidad',
        text: `¿Confirma que la unidad con VIN ${vin} se descarga en excelente estado?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            // Simulamos guardado individual de checklist destino para este VIN
            let formData = new FormData();
            formData.append('id_envio', idEnvio);
            formData.append('id_unidad', idUnidad);
            formData.append('tipo_checklist', 'entrega_destino');
            formData.append('vin', vin);
            formData.append('comentarios', 'Entregado en concesionario conforme.');

            fetch(base_url + '/Lgs_ejecucion/guardarChecklistTrasladista', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(res => {
                if (res.status === 'success' || res.status === true) {
                    Swal.fire("Confirmado", "VIN marcado como entregado.", "success");
                    cargarDetalleEntrega(idEnvio);
                } else {
                    Swal.fire("Error", "No se pudo actualizar el estatus de la unidad.", "error");
                }
            });
        }
    });
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
                <small class="text-muted d-block mt-1">Sube el ZIP con la remisión, firma y fotos de entrega en una carpeta.</small>
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

function guardarEntregaDestino() {
    const idEnvio = document.getElementById('ent_id_envio').value;
    const qrValido = document.getElementById('qr_cliente_validado').value;
    const nombreRecibe = document.getElementById('nombre_recibe').value.trim();

    if (qrValido !== "1") {
        Swal.fire("Atención", "Es obligatorio escanear el QR del cliente antes de cerrar la entrega.", "warning");
        return;
    }

    if (!nombreRecibe) {
        Swal.fire("Atención", "Debe ingresar el nombre de la persona que recibe.", "warning");
        return;
    }

    const tabZipActive = document.getElementById('tab-fotos-zip-entrega')?.classList.contains('active');
    const zipInput = document.getElementById('file_zip_entrega');

    if (tabZipActive) {
        if (!zipInput || zipInput.files.length === 0) {
            Swal.fire("Atención", "Debe seleccionar un archivo .ZIP con las evidencias de entrega.", "warning");
            return;
        }
    } else {
        // Validar fotos obligatorias de remisión y firma
        if (document.getElementById('file_remision').files.length === 0 || document.getElementById('file_firma').files.length === 0) {
            Swal.fire("Atención", "Debe adjuntar la foto de la remisión firmada y la firma del receptor, o utilizar la pestaña de Carga Masiva (ZIP).", "warning");
            return;
        }
    }

    const form = document.getElementById('formEntregaFinal');
    const formData = new FormData(form);

    Swal.fire({
        title: 'Cerrando Entrega...',
        text: 'Enviando comprobantes y finalizando el viaje',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    // Subimos evidencias finales del envío y cerramos el viaje
    fetch(base_url + '/Lgs_ejecucion/finalizarEntregaDestino', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(res => {
        if (res.status === 'success' || res.status === true) {
            Swal.fire({
                title: '¡Viaje Completado!',
                text: res.message || 'El traslado ha sido cerrado y registrado exitosamente en el sistema MRP.',
                icon: 'success'
            }).then(() => {
                window.location.href = base_url + "/Lgs_ejecucion/chofer_movil";
            });
        } else {
            Swal.fire("Error", res.message || res.msg || "No se pudo finalizar la entrega.", "error");
        }
    })
    .catch(err => {
        Swal.fire("Error", "Error de red al completar el traslado.", "error");
    });
}
