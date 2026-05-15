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

            // LIMPIEZA: Antes de enviar, convertimos a número real
            if (payload.limite_credito) {
                payload.limite_credito = Sys_Core.Format.toNumber(payload.limite_credito);
            }
            
            Sys_Core.Net.post({
                url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers`,
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
            { url: 'SatCatalogo/tipos_personas', selector: '[name="id_tipo_persona"]' }
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
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/${id}`,
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
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/${idProveedor}/documents`,
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
        const $progressBar = $progress.find('.progress-bar');
        const $success = $(`#success-${docType}`);

        // UI: Iniciar estado de carga
        $progress.removeClass('d-none');
        $progressBar.css('width', '50%'); // Feedback visual inmediato
        $success.addClass('d-none');

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/documents`,
            payload: formData,
            // Importante: No pasamos successMsg aquí para no saturar con Toasts, 
            // dejamos que la UI se actualice sola con loadStatus
            onDone: (res) => {
                // El backend devuelve el nuevo progreso global
                if (res.data && res.data.progress !== undefined) {
                    $('#global-progress-bar').css('width', `${res.data.progress}%`);
                    $('#global-progress-text').text(`${res.data.progress}%`);
                }
                
                Sys_Core.UI.notify(`${docType.replace('_', ' ')} cargado con éxito.`, 'success');
                this.loadStatus(idProveedor);
            },
            // Limpieza en caso de error (Sys_Core ya lanza el alert)
            onError: () => {
                $progress.addClass('d-none');
                $progressBar.css('width', '0%');
            },
            complete: () => {
                $(input).val(''); // Resetear el input file siempre
            }
        });
    },

    /**
     * Envía el dictamen de validación (Aprobado/Rechazado) al servidor.
     * 
     * @param {number} idDoc - ID del registro en prv_det_expediente.
     * @param {number} action - 1 (Aprobado), 2 (Rechazado).
     * @param {string} motivo - Comentario de rechazo (obligatorio si action es 2).
     */
    auditDocument: function(idDoc, action, motivo = '') {
        const idProveedor = Sys_Core.URL.getParam('id');
        
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/audit-document`,
            payload: { 
                id_documento: idDoc, 
                estatus_validacion: action, 
                motivo_rechazo: motivo,
                id_proveedor: idProveedor
            },
            onDone: (res) => {
                // Notificación Premium según la acción
                const msg = action === 1 ? 'Documento verificado.' : 'Observación enviada al proveedor.';
                Sys_Core.UI.notify(msg, action === 1 ? 'success' : 'warning');

                // Recargar el estatus del expediente para actualizar cards y barra de progreso
                this.loadStatus(idProveedor);
                
                // Si el backend activó al proveedor automáticamente
                if (res.data && res.data.proveedor_activado) {
                    Sys_Core.UI.alert(
                        '¡Expediente Validado!', 
                        'Todos los documentos han sido aprobados. El proveedor ha sido ACTIVADO para operaciones de compra.', 
                        'success'
                    );
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

        // --- EVENTO PARA APROBAR / RECHAZAR CUENTA ---
        $('#lista-cuentas-bancarias').on('click', '.btn-audit-bank', function() {
            const idCuenta = $(this).data('id');
            const nuevoEstatus = $(this).data('status');
            const idProveedor = Sys_Core.URL.getParam('id');

            Sys_Core.UI.confirm({
                title: `¿${nuevoEstatus === 'APROBADO' ? 'Aprobar' : 'Rechazar'} cuenta?`,
                text: `La cuenta será marcada como ${nuevoEstatus.toLowerCase()} para pagos.`,
                confirmText: 'Confirmar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Sys_Core.Net.post({
                        url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/audit-bank`,
                        payload: { 
                            id_cuenta_bancaria: idCuenta, 
                            estatus_aprobacion: nuevoEstatus 
                        },
                        onDone: (res) => {
                            Sys_Core.UI.notify(res.message, 'success');
                            self.loadAccounts(idProveedor);
                        }
                    });
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

        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/currencies`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect('#id_moneda_banco', res.data, { 
                    valueField: 'cve_moneda', // Ej: 'MXN', 'USD'
                    textField: 'cve_moneda',
                    selectedValue: 'MXN' // Pre-seleccionar Moneda Nacional
                });
                // Disparar el cambio manualmente para bloquear el input de TC si es MXN
                $('#id_moneda_banco').trigger('change');
            }
        });
    },

    /**
     * Carga las cuentas bancarias desde la API REST.
     */
    loadAccounts: function(idProveedor) {
        Sys_Core.UI.toggleLoader('#tab-banking', true);

        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/${idProveedor}/banks`,
            onSuccess: (res) => {
                if (res.status) {
                    this.renderTable(res.data);
                    this.isLoaded = true;
                }
                Sys_Core.UI.toggleLoader('#tab-banking', false);
            }
        });
    },

    /**
     * Renderiza la tabla de cuentas con lógica de permisos y estatus.
     */
    renderTable: function(cuentas) {
        const $tbody = $('#lista-cuentas-bancarias');
        $tbody.empty();

        if (!cuentas || cuentas.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="5" class="text-center py-5 text-muted">
                        <div class="avatar-sm mx-auto mb-3">
                            <div class="avatar-title bg-light text-primary rounded-circle fs-24">
                                <i class="ri-bank-card-line"></i>
                            </div>
                        </div>
                        <h6 class="fw-bold">Sin cuentas registradas</h6>
                        <p class="mb-0 fs-12">El proveedor no ha proporcionado datos de transferencia.</p>
                    </td>
                </tr>
            `);
            return;
        }

        // Determinar si el usuario actual puede AUDITAR (Aprobar/Rechazar)
        // Usamos el permiso 'd' (Delete/Audit) como gatillo para Tesorería
        const canAudit = Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'd');

        let html = '';
        cuentas.forEach(c => {
            const status = c.estatus_aprobacion.toUpperCase();
            
            // 1. Mapeo de Badges Premium
            const badges = {
                'PENDIENTE': 'bg-warning',
                'APROBADO':  'bg-success',
                'RECHAZADO': 'bg-danger'
            };
            const badgeClass = badges[status] || 'bg-secondary';

            // 2. Identificador Único (CLABE, SWIFT o IBAN)
            const identificador = c.clabe || (c.swift_bic ? `SWIFT: ${c.swift_bic}` : `IBAN: ${c.iban}`);
            
            // 3. Botones Dinámicos
            let actionButtons = '';

            // Si está pendiente y el usuario es auditor -> Mostrar botones de Visto Bueno
            if (status === 'PENDIENTE' && canAudit) {
                actionButtons += `
                    <button type="button" class="btn btn-sm btn-success btn-audit-bank shadow-sm" 
                            data-id="${c.id_cuenta_bancaria}" data-status="APROBADO" title="Aprobar">
                        <i class="ri-check-line"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger btn-audit-bank shadow-sm" 
                            data-id="${c.id_cuenta_bancaria}" data-status="RECHAZADO" title="Rechazar">
                        <i class="ri-close-line"></i>
                    </button>
                `;
            }

            // Botón de eliminar (Solo si no está aprobado o si es admin)
            if (status !== 'APROBADO' || canAudit) {
                actionButtons += `
                    <button type="button" class="btn btn-sm btn-soft-danger btn-delete-bank" 
                            data-id="${c.id_cuenta_bancaria}" title="Eliminar">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                `;
            }

            html += `
                <tr class="align-middle">
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar-xs me-2">
                                <div class="avatar-title bg-soft-primary text-primary rounded fs-16">
                                    <i class="ri-bank-line"></i>
                                </div>
                            </div>
                            <div>
                                <h6 class="fs-13 mb-0 fw-bold">${c.id_banco ?? 'BANCO'}</h6>
                                <p class="text-muted mb-0 fs-11">Cuenta: ${c.cuenta || '---'}</p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <code class="text-primary fw-bold fs-12">${identificador}</code>
                        ${c.es_principal == 1 ? '<span class="badge bg-soft-info text-info ms-1 border border-info-subtle">Principal</span>' : ''}
                    </td>
                    <td class="text-center fw-medium">${c.id_moneda}</td>
                    <td class="text-center">
                        <span class="badge ${badgeClass} shadow-sm">${status}</span>
                    </td>
                    <td>
                        <div class="d-flex justify-content-end gap-1">
                            ${actionButtons}
                        </div>
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
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/store-bank`,
            payload: payload,
            successMsg: 'Cuenta bancaria agregada y enviada a revisión.',
            onDone: () => {
                Sys_Core.UI.clearForm('#formDatosBancarios');
                $('#banco_principal_no').prop('checked', true);
                this.loadAccounts(idProveedor);
            }
        });
    },

    /**
     * Ejecuta el borrado lógico de la cuenta bancaria.
     */
    deleteAccount: function(idCuenta) {
        const idProveedor = Sys_Core.URL.getParam('id');

        Sys_Core.Net.post({
            // Cambiamos a la ruta RESTful con el verbo DELETE
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/banks/${idCuenta}`,
            method: 'DELETE', // Semántica RESTful
            onDone: (res) => {
                Sys_Core.UI.notify(res.message, 'info');
                this.loadAccounts(idProveedor);
            }
        });
    }
};

/**
 * Gestión visual del Stepper de Onboarding
 */
const onboardingManager = {
    isLoaded: false,

    init: function() {
        const self = this;
        // Lazy loading al cambiar a la pestaña
        $('button[data-bs-target="#tab-onboarding"], a[href="#tab-onboarding"]').on('shown.bs.tab', function () {
            const id = Sys_Core.URL.getParam('id');
            if (id) self.loadTimeline(id);
        });
    },

    loadTimeline: function(id) {
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers/${id}/onboarding-timeline`,
            onSuccess: (res) => {
                if (res.status) {
                    this.render(res.data);
                    this.isLoaded = true;
                }
            }
        });
    },

    render: function(data) {
        const { steps, current_status_text } = data;
        
        // 1. Actualizar Banner Central
        $('#onboarding-status-title').text(`Estatus: ${current_status_text}`);
        
        const $list = $('.custom-timeline-list');
        $list.empty();

        const stepIcons = {
            step1: 'ri-user-add-line',
            step2: 'ri-folder-open-line',
            step3: 'ri-shield-check-line',
            step4: 'ri-bank-card-2-line'
        };

        const stepLabels = {
            step1: 'Registro Inicial',
            step2: 'Expediente Digital',
            step3: 'Validación',
            step4: 'Alta en ERP'
        };

        Object.entries(steps).forEach(([key, info]) => {
            const isCompleted = info.status === 'completed';
            const isActive = info.status === 'active';
            
            let itemClass = isCompleted ? 'completed' : (isActive ? 'active' : '');
            let iconClass = isCompleted ? 'bg-success text-white' : (isActive ? 'bg-warning text-white' : 'bg-light text-muted');
            let contentClass = isActive ? 'active-card' : '';
            let icon = isCompleted ? 'ri-check-line' : stepIcons[key];

            const html = `
                <li class="timeline-item ${itemClass}">
                    <div class="timeline-icon ${iconClass}">
                        <i class="${icon}"></i>
                    </div>
                    <div class="timeline-content ${contentClass}">
                        <h6 class="fs-14 fw-bold mb-1 ${isActive ? 'text-warning' : 'text-dark'}">${stepLabels[key]}</h6>
                        <span class="badge ${isCompleted ? 'bg-success' : 'bg-warning'} text-white">${info.badge}</span>
                        <p class="text-muted fs-12 mb-0 mt-1">${info.date || ''}</p>
                    </div>
                </li>
            `;
            $list.append(html);
        });
    }
};

$(document).ready(() => {
    cascadeCatalogs.init();
    supplierManager.init();
    files.init();
    bankingManager.init();
    onboardingManager.init();
});