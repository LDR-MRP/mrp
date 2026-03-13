/**
 * Orquestador Principal del Proveedor
 */
const proveedorManager = {
    init: function() {
        const idProveedor = new URLSearchParams(window.location.search).get('id');

        // 1. Aplicamos el estado visual de la página (Textos y Tabs)
        this.applyViewState(idProveedor);
        
        // 2. Cargar catálogos base usando Promesas para evitar condiciones de carrera
        this.loadCatalogs().then(() => {
            // Si estamos en edición, cargamos el perfil DESPUÉS de los catálogos
            if (idProveedor) {
                this.loadProfile(idProveedor);
            }
        });

        this.events();
    },

    events: function() {
        $('#formProveedor').on('submit', function(e) {
            e.preventDefault();
            const data = new FormData(this);
            const payload = Object.fromEntries(data.entries());
            payload.notificar_compras = $('#notificar_compras').is(':checked') ? 1 : 0;
            
            Sys_Core.Net.post({
                url: `${Sys_Core.Config.baseUrl}/prv_proveedor/registrarProveedor`,
                payload: payload,
                successMsg: 'El proveedor ha sido registrado y/o actualizado correctamente.',
                onDone: () => {
                    setTimeout(() => Sys_Core.Navigation.to('prv_proveedor'), 1500);
                }
            });
        });
    },

    /**
     * Modifica el DOM basado en si es Creación o Edición
     * @param {string|null} isEdit - El ID del proveedor si existe
     */
    applyViewState: function(isEdit) {
        if (isEdit) {
            // Textos e Iconos para EDICIÓN
            $('#page-title').text('Edición de Registro');
            $('#page-description').text('Complete la información fiscal y comercial para editar al socio.');
            
            // Cambiamos el color e icono del avatar (como en tu PHP original)
            $('#page-icon-container').removeClass('bg-primary').addClass('bg-warning');
            $('#page-icon').removeClass('ri-add-line').addClass('ri-edit-2-line');
            
            
        } else {
            // Textos e Iconos para CREACIÓN (Ya están por defecto, pero asegura el estado)
            $('#page-title').text('Nuevo Proveedor');
            $('#page-description').text('Complete la información para dar de alta un nuevo socio.');
            
            $('#page-icon-container').removeClass('bg-warning').addClass('bg-primary');
            $('#page-icon').removeClass('ri-edit-2-line').addClass('ri-add-line');
            
            // Ocultamos las pestañas exclusivas de edición
            $('.edit-only-tab').addClass('d-none');
        }

        if (isEdit &&
            (Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'u') ||
            Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'r'))
        ) {
            // Mostramos las pestañas exclusivas de edición para roles con permiso de edición
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
            { url: 'SatCatalogo/tipos_personas', selector: '[name="id_tipo_persona"]' }
        ];

        // Mapeamos las peticiones a Promesas
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
                    const data = res.data;
                    
                    // Magia: Rellenamos todo el formulario de golpe
                    Sys_Core.UI.fillForm('#formProveedor', data[0]);

                    // Si trae CP, disparamos la cascada y al terminar, seteamos la colonia
                    if (data.cp) {
                        cascadeCatalogs.buscarCP(data.cp, () => {
                            $('[name="colonia"]').val(data.colonia);
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
                cascadeCatalogs.buscarCP(cp);
            }
        });
        
        $('select[name="id_tipo_persona"]').on('change', function() {
            const tipoPersona = $(this).val();
            cascadeCatalogs.buscarRegimen(tipoPersona);
        })
    },

    buscarCP: function(cp, callback = null) {
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
                        placeholder: 'Seleccione colonia...'
                    });
                    
                    Sys_Core.UI.notify('Ubicación localizada', 'success');
                } else {
                    Sys_Core.UI.notify('Código Postal no encontrado', 'warning');
                }
            }
        });
    },

    buscarRegimen: function(tipoPersona) {
        Sys_Core.Net.get({
            url: `${base_url}/SatCatalogo/regimenes_fiscales/${tipoPersona}`,
            silent: false,
            onSuccess: (res) => {
                if (res.status) {

                    Sys_Core.UI.fillSelect('#id_regimen_fiscal', res.data, {
                        valueField: 'id',
                        textField: 'nombre',
                        placeholder: 'Seleccione régimen...'
                    });
                    
                    Sys_Core.UI.notify('Régimen localizado', 'success');
                } else {
                    Sys_Core.UI.notify('Régimen no encontrado', 'warning');
                }
            }
        })
    }
};

/**
 * Lógica para la gestión de Expediente Digital (Uploads)
 */
const files = {
    isLoaded: false, // Bandera para no cargar 2 veces

    init: function() {
        this.events();
    },

    events: function() {
        const self = this;

        // --- LAZY LOADING DEL EXPEDIENTE ---
        // Se ejecuta SOLO cuando la pestaña se hace visible (Evento de Bootstrap)
        $('button[data-bs-target="#tab-expediente"], a[href="#tab-expediente"]').on('shown.bs.tab', function () {
            const idProveedor = new URLSearchParams(window.location.search).get('id');
            if (idProveedor && !self.isLoaded) {
                self.loadStatus(idProveedor);
            }
        });

        $(document).on('click', '.dropzone-premium', function(e) {
            $(this).find('.file-input').trigger('click');
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
                const files = e.originalEvent.dataTransfer.files;
                const docType = $(this).data('type');
                const input = $(this).find('.file-input')[0];
                
                input.files = files;
                self.upload(input, docType);
            }
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

        // 1. Pintamos la barra general que devuelve el backend
        $('#global-progress-bar').css('width', `${progress}%`).attr('aria-valuenow', progress);
        $('#global-progress-text').text(`${progress}%`);

        // 2. Limpiamos el contenedor
        const $container = $('#document-cards-container');
        $container.empty();

        // 3. Iteramos los documentos y construimos el HTML
        Object.entries(documents).forEach(([key, doc]) => {
            const isUploaded = doc.uploaded;
            const fileData = doc.file_data; // Objeto directo de tu tabla prv_det_expediente
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

        const idProveedor = new URLSearchParams(window.location.search).get('id');
        const formData = new FormData();
        formData.append('archivo', file);
        formData.append('tipo_documento', docType);
        formData.append('id_proveedor', idProveedor);

        const $progress = $(`#progress-${docType}`);
        const $success = $(`#success-${docType}`);
        const $dropzone = $(`#dropzone-${docType}`);

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
    }
};

$(document).ready(() => {
    cascadeCatalogs.init();
    proveedorManager.init();
    files.init();
});