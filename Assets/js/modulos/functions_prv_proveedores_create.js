/**
 * Orquestador Principal del Proveedor
 */
const supplierManager = {

    currentId: null,

    init: function() {

        this.currentId = Sys_Core.URL.getParam('id');
        
        this.applyViewState(this.currentId);
        
        this.loadCatalogs().then(() => {
            if (this.currentId) {
                this.loadProfile(this.currentId);
            }
        });

        this.events();
    },

    events: function() {

        const self = this;

        $('#formProveedor').on('submit', function(e) {
            e.preventDefault();
            const data = new FormData(this);
            const payload = Object.fromEntries(data.entries());
            payload.id = self.currentId || null;
            
            Sys_Core.Net.post({
                url: `${Sys_Core.Config.baseUrl}/prv_proveedor/storeSupplier`,
                payload: payload,
                successMsg: 'El proveedor ha sido registrado y/o actualizado correctamente.',
                onDone: () => {
                    setTimeout(() => Sys_Core.Navigation.to('prv_proveedor'), 1500);
                }
            });
        });

        $('input[name="limite_credito"]').on('blur', function() {
            const rawValue = Sys_Core.Format.toNumber($(this).val());
            if (rawValue > 0) {
                $(this).val(Sys_Core.Format.toCurrency(rawValue));
            } else {
                $(this).val('');
            }
        }).on('focus', function() {
            const rawValue = Sys_Core.Format.toNumber($(this).val());
            if (rawValue > 0) {
                $(this).val(rawValue); 
            }
        });
    },

    /**
     * Modifica el DOM basado en si es Creación o Edición
     * @param {string|null} isEdit - El ID del proveedor si existe
     */
    applyViewState: function(isEdit) {
        if (isEdit) {
            $('#page-title').text('Edición de Registro');
            $('#page-description').text('Complete la información fiscal y comercial para editar al socio.');            
            $('#page-icon-container').removeClass('bg-primary').addClass('bg-warning');
            $('#page-icon').removeClass('ri-add-line').addClass('ri-edit-2-line');
            
            
        } else {
            $('#page-title').text('Nuevo Proveedor');
            $('#page-description').text('Complete la información para dar de alta un nuevo socio.');            
            $('#page-icon-container').removeClass('bg-warning').addClass('bg-primary');
            $('#page-icon').removeClass('ri-edit-2-line').addClass('ri-add-line');
            $('.edit-only-tab').addClass('d-none');
        }

        if (isEdit &&
            (Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'u') ||
            Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'r'))
        ) {
            $('.edit-only-tab').removeClass('d-none');
        }
    },

    /**
     * 
     * @returns 
     */
    loadCatalogs: function() {
        const catalogos = [
            { url: 'Catalogo/condiciones_pago', selector: '[name="id_condicion_pago"]' },
            { url: 'Catalogo/cuentas_contables', selector: '[name="id_cuenta_contable"]' },
            { url: 'SatCatalogo/tipos_personas', selector: '[name="id_tipo_persona"]' },
            { url: 'inv_moneda/index', selector: '[name="id_moneda_banco"]' }
        ];

        const promises = catalogos.map(cat => {
            return new Promise((resolve) => {
                Sys_Core.Net.get({
                    url: `${Sys_Core.Config.baseUrl}/${cat.url}`,
                    silent: true,
                    onSuccess: (res) => {
                        Sys_Core.UI.fillSelect(cat.selector, res.data);
                        resolve();
                    }
                });
            });
        });

        return Promise.all(promises);
    },

    /**
     * 
     * @param {*} id 
     */
    loadProfile: function(id) {
        Sys_Core.UI.toggleLoader('.page-content', true);
        
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/show/${id}`,
            silent: true,
            onSuccess: (res) => {
                if (res.status && res.data) {
                    const data = res.data[0];
                
                    // 1. Llenamos el formulario de golpe
                    Sys_Core.UI.fillForm('#formProveedor', data);

                    // 2. Cargamos Régimen Fiscal y al terminar, seleccionamos (Igual que el CP)
                    if (data.id_tipo_persona) {
                        cascadeCatalogs.searchRegime(data.id_tipo_persona, () => {
                            $('[name="id_regimen_fiscal"]').val(data.id_regimen_fiscal).trigger('change');
                        });
                    }

                    // 3. Cargamos Colonias y al terminar, seleccionamos
                    if (data.cp) {
                        cascadeCatalogs.searchCP(data.cp, () => {
                            $('[name="colonia"]').val(data.colonia).trigger('change');
                        });
                    }
                }
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    }
};

const cascadeCatalogs = {
    init: function() {
        this.events();
    },

    events: function() {
        $('#cp').on('keyup', function() {
            const cp = $(this).val();
            if (cp.length === 5) {
                cascadeCatalogs.searchCP(cp);
            }
        });

        $('select[name="id_tipo_persona"]').on('change', function(e) {
            if (e.originalEvent) {
                const tipoPersona = $(this).val();
                cascadeCatalogs.searchRegime(tipoPersona);
            }
        });
    },

    searchCP: function(cp, callback = null) {
        Sys_Core.Net.get({
            url: `${base_url}/catalogo/codigos_postales/${cp}`,
            silent: false,
            onSuccess: (res) => {
                if (res.status) {
                    $('#estado').val(res.data.estado);
                    $('#ciudad').val(res.data.ciudad);
                    $('#municipio').val(res.data.municipio);

                    Sys_Core.UI.fillSelect('#colonia', res.data.colonias, {
                        valueField: 'asentamiento',
                        textField: 'asentamiento',
                        placeholder: 'Selecciona colonia...'
                    });
                    
                    if (callback) callback();
                }
            }
        });
    },

    searchRegime: function(tipoPersona, callback = null) {
        Sys_Core.Net.get({
            url: `${base_url}/SatCatalogo/regimenes_fiscales/${tipoPersona}`,
            silent: false,
            onSuccess: (res) => {
                if (res.status) {
                    Sys_Core.UI.fillSelect('#id_regimen_fiscal', res.data, {
                        valueField: 'id',
                        textField: 'nombre',
                        placeholder: 'Selecciona régimen...'
                    });
                    
                    if (callback) callback();
                }
            }
        })
    },
};

/**
 * Lógica para la gestión de Expediente Digital (Uploads)
 */
const files = {
    isLoaded: false,

    init: function() {
        this.events();
    },

    events: function() {
        const self = this;
        const $container = $('#document-cards-container');

        // --- LAZY LOADING DEL EXPEDIENTE ---
        // Se ejecuta SOLO cuando la pestaña se hace visible (Evento de Bootstrap)
        $('button[data-bs-target="#tab-expediente"], a[href="#tab-expediente"]').on('shown.bs.tab', function () {
            const idProveedor = Sys_Core.URL.getParam('id');
            if (idProveedor && !self.isLoaded) {
                self.loadStatus(idProveedor);
            }
        });

        $(document).on('click', '.dropzone-premium', function(e) {
            const $input = $(this).find('.file-input');
            if (!$input.prop('disabled')) {
                $input.trigger('click');
            }
        });

        $(document).on('click', '.file-input', function(e) {
            e.stopPropagation(); 
        });

        $(document).on('change', '.file-input', function() {
            const docType = $(this).closest('.dropzone-premium').data('type');
            self.upload(this, docType);
        });

        $(document).on('dragover', '.dropzone-premium', function(e) {
            e.preventDefault();
            $(this).addClass('bg-primary-subtle border-primary');
        });

        $(document).on('dragleave drop', '.dropzone-premium', function(e) {
            e.preventDefault();
            $(this).removeClass('bg-primary-subtle border-primary');
            
            if (e.type === 'drop') {
                const $input = $(this).find('.file-input');
                
                if ($input.prop('disabled')) return;

                const files = e.originalEvent.dataTransfer.files;
                const docType = $(this).data('type');
                
                $input[0].files = files;
                self.upload($input[0], docType);
            }
        });

        $container.on('click', '.btn-replace', function(e) {
            e.stopPropagation();
            const docType = $(this).data('type');
            $(`#file-${docType}`).prop('disabled', false).trigger('click');
        });

        $container.on('click', '.btn-approve', function(e) {
            e.stopPropagation();
            const idDoc = $(this).data('doc-id');
            
            Sys_Core.UI.confirm({
                title: '¿Aprobar documento?',
                text: 'Este documento será marcado como válido y verificado.',
                icon: 'question',
                confirmText: 'Sí, aprobar'
            }).then((result) => {
                if (result.isConfirmed) {
                    self.auditDocument(idDoc, 1);
                }
            });
        });

        $container.on('click', '.btn-reject', function(e) {
            e.stopPropagation();
            const idDoc = $(this).data('doc-id');
            
            Swal.fire({
                title: 'Devolver Documento',
                input: 'textarea',
                inputLabel: 'Motivo del rechazo / devolución',
                inputPlaceholder: 'Ej. El documento no es legible o está caducado...',
                inputAttributes: { 'aria-label': 'Motivo del rechazo' },
                showCancelButton: true,
                confirmButtonColor: '#f06548',
                confirmButtonText: 'Devolver Documento',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value) return 'Debes ingresar un motivo para devolver el documento.';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    self.auditDocument(idDoc, 2, result.value);
                }
            });
        });
    },

    /**
     * 
     * @param {*} idProveedor 
     */
    loadStatus: function(idProveedor) {
        Sys_Core.UI.toggleLoader('#tab-expediente', true);
        
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/documents/${idProveedor}`,
            silent: false,
            onSuccess: (res) => {
                if (res.status && res.data) {
                    this.renderStatus(res.data);
                    this.isLoaded = true; // Marcamos como cargado
                }
                Sys_Core.UI.toggleLoader('#tab-expediente', false);
            }
        });
    },

    renderStatus: function(data) {
        const { progress, documents } = data;

        $('#global-progress-bar').css('width', `${progress}%`).attr('aria-valuenow', progress);
        $('#global-progress-text').text(`${progress}%`);

        const $container = $('#document-cards-container');
        $container.empty();

        Object.entries(documents).forEach(([key, doc]) => {
            const isUploaded = doc.uploaded;
            const fileData = doc.file_data;
            const estado = parseInt(fileData?.estatus_validacion, 10) || 0;
            let dropzoneClass = 'bg-light';
            let iconClass = 'text-primary';
            let statusHtml = '';
            
            if (isUploaded) {

                if (estado === 1) {
                    dropzoneClass = 'bg-success-subtle border-success';
                    iconClass = 'text-success';
                    statusHtml = `<i class="ri-checkbox-circle-fill me-1 text-success"></i> <span class="text-success">Documento verificado y aprobado.</span>`;
                } else if (estado === 2) {
                    dropzoneClass = 'bg-danger-subtle border-danger';
                    iconClass = 'text-danger';
                    const motivo = fileData.motivo_rechazo ? `: ${fileData.motivo_rechazo}` : '';
                    statusHtml = `<i class="ri-close-circle-fill me-1 text-danger"></i> <span class="text-danger">Documento devuelto${motivo}</span>`;
                } else {
                    dropzoneClass = 'bg-info-subtle border-info';
                    iconClass = 'text-info';
                    statusHtml = `<i class="ri-information-fill me-1 text-info"></i> <span class="text-info">En revisión por Mesa de Control.</span>`;
                }
            }

            const requiredText = doc.required ? 'Obligatorio' : 'Opcional';
            const acceptedExt = `.${doc.ext.replace(/,/g, ',.')}`;
            
            let dropzoneInnerHtml = '';
            
            if (!isUploaded) {
                dropzoneInnerHtml = `
                    <div class="dz-message">
                        <i class="ri-upload-2-line fs-24 text-primary mb-2 d-block"></i>
                        <p class="fs-13 fw-medium mb-1">Arrastra tu ${doc.name}</p>
                        <p class="text-muted fs-11">O haz clic para explorar en tu equipo</p>
                        <p class="text-muted fs-11">Máximo 5MB • ${doc.ext.toUpperCase()}</p>
                    </div>`;
            } else {
                const canAudit = Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'd') ? true : false;
                const canUpdate = Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'u') ? true : false;
                
                let actionButtons = `<a href="${Sys_Core.Config.baseUrl}/${fileData.url_archivo}" target="_blank" class="btn btn-sm btn-soft-primary"><i class="ri-eye-line align-middle me-1"></i> Ver</a>`;

                if (canAudit && estado === 0) {
                    actionButtons += `
                        <button type="button" class="btn btn-sm btn-success btn-approve" data-doc-id="${fileData.id_documento}"><i class="ri-check-line align-middle"></i></button>
                        <button type="button" class="btn btn-sm btn-danger btn-reject" data-doc-id="${fileData.id_documento}"><i class="ri-close-line align-middle"></i></button>
                    `;
                }
                
                if (!canAudit && estado !== 1) {
                    actionButtons += `<button type="button" class="btn btn-sm btn-soft-warning btn-replace" data-type="${key}"><i class="ri-refresh-line align-middle me-1"></i> Reemplazar</button>`;
                }

                dropzoneInnerHtml = `
                    <div class="dz-uploaded">
                        <i class="ri-file-check-line fs-24 ${iconClass} mb-2 d-block"></i>
                        <p class="fs-13 fw-bold mb-1 text-truncate" title="${doc.name}">${doc.name}</p>
                        <div class="mt-3 d-flex justify-content-center gap-2">
                            ${actionButtons}
                        </div>
                    </div>`;
            }

            const cardHtml = `
                <div class="col-xl-4 col-md-6 doc-card" data-key="${key}" data-required="${doc.required}">
                    <div class="card border shadow-none mb-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar-sm flex-shrink-0">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded fs-22">
                                        <i class="ri-file-text-line"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fs-14 mb-0 fw-bold">${doc.name}</h6>
                                    <small class="text-muted">${requiredText} • ${doc.ext.toUpperCase()}</small>
                                </div>
                            </div>

                            <div class="dropzone-premium border-dashed rounded-3 p-4 text-center ${dropzoneClass}"
                                id="dropzone-${key}" data-type="${key}" style="cursor: ${isUploaded ? 'default' : 'pointer'}; border: 2px dashed #d1d5db;">
                                
                                <input type="file" id="file-${key}" class="d-none file-input" accept="${acceptedExt}" ${isUploaded ? 'disabled' : ''}>
                                ${dropzoneInnerHtml}
                                
                                <div class="progress progress-sm mt-2 d-none" id="progress-${key}">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <div class="mt-2 d-flex align-items-center fs-12 ${isUploaded ? '' : 'd-none'}" id="success-${key}">
                                ${statusHtml}
                            </div>
                        </div>
                    </div>
                </div>`;
            
            $container.append(cardHtml);
        });
    },

    upload: function(input, docType) {
        const file = input.files[0];
        if (!file) return;

        const idProveedor = Sys_Core.URL.getParam('id');
        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('tipo_documento', docType);
        formData.append('id_proveedor', idProveedor);

        const $progress = $(`#progress-${docType}`);
        const $success = $(`#success-${docType}`);

        $progress.removeClass('d-none');
        $success.addClass('d-none');

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/uploadDocument`,
            payload: formData,
            successMsg: `Documento ${docType} cargado correctamente.`,
            onDone: (res) => {
                this.loadStatus(idProveedor);
                $(input).val('');
            }
        });
    },

    /**
     * Envía el dictamen al backend
     * @param {number} idDoc - ID del documento en la tabla prv_det_expediente
     * @param {number} action - 1 (Aprobado), 2 (Rechazado)
     * @param {string} motivo - Texto del motivo (solo aplica en rechazo)
     */
    auditDocument: function(idDoc, action, motivo = '') {
        const idProveedor = new URLSearchParams(window.location.search).get('id');
        
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/auditDocument`,
            payload: $.param({ 
                id_documento: idDoc, 
                estatus_validacion: action, 
                motivo_rechazo: motivo,
                id_proveedor: idProveedor
            }),
            successMsg: action === 1 ? 'Documento aprobado exitosamente.' : 'Documento devuelto al proveedor.',
            onDone: (res) => {
                this.loadStatus(idProveedor);
                
                // Opcional: Si el backend nos responde que ya se alcanzó el 100% aprobado
                if (res.data && res.data.proveedor_activado) {
                    Sys_Core.UI.alert(
                        '¡Proveedor Activo!', 
                        'Todos los documentos han sido aprobados. El proveedor ya puede operar en el sistema.', 
                        'success'
                    );
                    // TODO: Aquí recargar el tab de Onboarding para que muestre el 100%
                }
            }
        });
    },
};

/**
 * Lógica para la gestión de Datos Bancarios (1:N)
 */
const bankingManager = {
    isLoaded: false,

    init: function() {
        this.events();
    },

    events: function() {
        const self = this;

        // --- LAZY LOADING DE DATOS BANCARIOS ---
        $('button[data-bs-target="#tab-banking"], a[href="#tab-banking"]').on('shown.bs.tab', function () {
            const idProveedor = Sys_Core.URL.getParam('id');
            if (idProveedor && !self.isLoaded) {
                self.loadCatalogs();
                self.loadAccounts(idProveedor);
            }
        });

        // Guardar nueva cuenta (Independiente del formulario maestro)
        $('#btnGuardarCuenta').on('click', function(e) {
            e.preventDefault();
            self.storeAccount();
        });

        // --- EVENT DELEGATION PARA LA TABLA ---
        $('#lista-cuentas-bancarias').on('click', '.btn-delete-bank', function() {
            const idCuenta = $(this).data('id'); // Inyección de estado temporal
            Sys_Core.UI.confirm({
                title: '¿Eliminar Cuenta?',
                text: 'La cuenta será dada de baja. Esta acción auditará el movimiento.',
                icon: 'warning',
                confirmText: 'Sí, eliminar'
            }).then((result) => {
                if (result.isConfirmed) {
                    self.deleteAccount(idCuenta);
                }
            });
        });
    },

    loadCatalogs: function() {
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/Catalogo/bancos`,
            silent: true,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect('#id_banco', res.data, {
                    valueField: 'id_banco',
                    textField: 'nombre_corto',
                    placeholder: 'Selecciona un banco...'
                });
            }
        });
    },

    loadAccounts: function(idProveedor) {
        Sys_Core.UI.toggleLoader('#tab-banking', true);

        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/banks/${idProveedor}`,
            silent: false,
            onSuccess: (res) => {
                this.renderTable(res.data);
                this.isLoaded = true;
                Sys_Core.UI.toggleLoader('#tab-banking', false);
            }
        });
    },

    renderTable: function(cuentas) {
        const $tbody = $('#lista-cuentas-bancarias');
        $tbody.empty();

        if (!cuentas || cuentas.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="5" class="text-center py-4 text-muted">
                        <i class="ri-bank-card-line fs-24 d-block mb-2 text-light"></i>
                        Aún no hay cuentas bancarias registradas.
                    </td>
                </tr>
            `);
            return;
        }

        let html = '';
        cuentas.forEach(c => {
            // Badges de Estatus (Compliance L2)
            let badgeEstatus = `<span class="badge bg-warning text-white">Pendiente</span>`;
            if (c.estatus_aprobacion === 'APROBADO') badgeEstatus = `<span class="badge bg-success">Aprobado</span>`;
            if (c.estatus_aprobacion === 'RECHAZADO') badgeEstatus = `<span class="badge bg-danger">Rechazado</span>`;

            // Badge si es Principal
            const badgePrincipal = c.es_principal == 1 ? `<span class="badge bg-info-subtle text-info ms-1 border border-info">Principal</span>` : '';

            // Lógica de visualización: Nacional vs Extranjero
            const identificador = c.clabe ? c.clabe : (c.swift_bic ? `SWIFT: ${c.swift_bic}` : `IBAN: ${c.iban}`);

            html += `
                <tr>
                    <td>
                        <div class="fw-bold text-dark">${c.id_banco ?? 'N/A'}</div>
                        <div class="text-muted fs-11">Cuenta: ${c.cuenta ?? '---'}</div>
                    </td>
                    <td>
                        <span class="font-monospace fs-12">${identificador}</span> ${badgePrincipal}
                    </td>
                    <td class="fw-medium">${c.id_moneda}</td>
                    <td>${badgeEstatus}</td>
                    <td class="text-end">
                        <button type="button" class="btn btn-sm btn-soft-danger btn-delete-bank" data-id="${c.id_cuenta_bancaria}">
                            <i class="ri-delete-bin-line"></i>
                        </button>
                    </td>
                </tr>
            `;
        });

        $tbody.html(html);
    },

    storeAccount: function() {
        const idProveedor = Sys_Core.URL.getParam('id');
        if (!idProveedor) {
            Sys_Core.UI.notify('Debes guardar los Datos Maestros primero.', 'warning');
            return;
        }

        const form = document.getElementById('formDatosBancarios');
        const payload = new FormData(form);
        payload.append('id_proveedor', idProveedor);

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/storeBank`,
            payload: payload,
            successMsg: 'Cuenta bancaria agregada y enviada a revisión.',
            onDone: () => {
                Sys_Core.UI.clearForm('#formDatosBancarios');
                $('#banco_principal_no').prop('checked', true);
                this.loadAccounts(idProveedor);
            }
        });
    },

    deleteAccount: function(idCuenta) {
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/prv_proveedor/deleteBank`,
            payload: { id_cuenta_bancaria: idCuenta },
            successMsg: 'Cuenta eliminada del registro.',
            onDone: () => {
                const idProveedor = Sys_Core.URL.getParam('id');
                this.loadAccounts(idProveedor);
            }
        });
    }
};

$(document).ready(() => {
    cascadeCatalogs.init();
    supplierManager.init();
    files.init();
    bankingManager.init();
});