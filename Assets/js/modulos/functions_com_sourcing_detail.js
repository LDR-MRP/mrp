/**
 * MRP System - Sourcing Detail (Workspace Integration)
 * @module SourcingDetail
 */

const SourcingDetail = {

    config: {
        endpoints: {
            // Nuevo: Carga el workspace completo por ID de Evento
            workspace: (id) => `${Sys_Core.Config.baseUrl}/api/v1/sourcing/events/${id}/workspace`,
            // Para cambiar entre partidas en la lista izquierda
            getItem: (id) => `${Sys_Core.Config.baseUrl}/api/v1/sourcing/comparison/${id}`,
            saveQuote: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/quotations`,
            selectWinner: (id) => `${Sys_Core.Config.baseUrl}/api/v1/sourcing/quotations/${id}/select-winner`,
            promote: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/promote-to-catalog`,
            productLines: `${Sys_Core.Config.baseUrl}/api/v1/catalogs/product-lines`,
        }
    },

    state: {
        eventId: null,
        activeItem: null, // ID de la partida actualmente seleccionada
        targetPrice: 0,
        quotes: [],
        sidebarItems: [],
        productLines: []
    },

    dom: {},

    init: function (idEvent) {
        this.state.eventId = idEvent;
        const targetItem = Sys_Core.URL.getParam('target');
        this.state.activeItem = targetItem ? parseInt(targetItem) : null;
        Sys_Core.Auth.validateSession();
        this.cacheDOM();
        this.bindEvents();
        this.loadCatalogs(); 
        this.loadWorkspace();
    },

    cacheDOM: function () {
        this.dom = {
            $grid: $('#container-comparison-grid'),
            $sidebar: $('#container-items-list'),
            $form: $('#form-add-quote'),
            $btnSave: $('#btn-save-quote'),
            $btnPromote: $('#btn-execute-promotion')
        };
    },

    bindEvents: function () {
        const self = this;

        $('#sel-currency').on('change', function() {
            const isMXN = $(this).val() === 'MXN';
            $('#group-tc').toggleClass('d-none', isMXN);
            if(isMXN) $('#txt-tc').val('1.0000');
        });

        // Cambio de Partida en el menú lateral
        this.dom.$sidebar.on('click', '.item-negotiation', function() {
            const idReqArt = $(this).data('id');
            self.switchItem(idReqArt);
        });

        this.dom.$btnSave.on('click', () => this.submitQuote());

        this.dom.$grid.on('click', '.btn-action', function() {
            const id = $(this).data('id');
            const action = $(this).data('action');
            if (action === 'select') self.executeSelectWinner(id);
            if (action === 'promote') self.openPromoteModal(id);
        });

        this.dom.$btnPromote.on('click', () => this.submitFinalPromotion());

        /**
         * Control dinámico de Fuente de Oferta y Reglas de Spot Buy
         */
        $('#sel-source-type').on('change', function() {
            const type = $(this).val(); // REGISTRADO, PROSPECTO, RETAIL
            const $spotBuyContainer = $('#container-spot-buy');
            const $spotBuyCheck = $('#check-pago-inmediato');
            const $urlContainer = $('#container-url-referencia');
            
            // Determinamos si es un flujo de "nombre libre" (Prospecto/Retail) 
            // o de "catálogo" (Registrado)
            const isExternal = (type === 'PROSPECTO' || type === 'RETAIL');

            // Si es RETAIL, mostramos la URL obligatoriamente
            if (type === 'RETAIL') {
                $urlContainer.removeClass('d-none');
                $('#txt-url-referencia').attr('required', true);
            } else {
                $urlContainer.addClass('d-none');
                $('#txt-url-referencia').removeAttr('required').val('');
            }

            switch (type) {
                case 'RETAIL':
                    // Amazon/ML: Siempre es Spot Buy, siempre bloqueado.
                    $spotBuyContainer.removeClass('d-none');
                    $spotBuyCheck.prop('checked', true).prop('disabled', true);
                    Sys_Core.UI.notify('Las compras Retail se mapean automáticamente como Spot Buy.', 'info');
                    break;
                    
                case 'PROSPECTO':
                    // Nuevo: Sugerimos Spot Buy pero permitimos al BO cambiarlo si negoció crédito.
                    $spotBuyContainer.removeClass('d-none');
                    $spotBuyCheck.prop('checked', true).prop('disabled', false);
                    break;

                default: // REGISTRADO
                    // Proveedor de casa: Ocultamos lógica de contado (asumimos crédito).
                    $spotBuyContainer.addClass('d-none');
                    $spotBuyCheck.prop('checked', false).prop('disabled', false);
                    break;
            }

            // INTERFAZ: Mostramos el input de texto si es Externo, sino mostramos el select de catálogo
            $('#wrapper-select-provider').toggleClass('d-none', isExternal);
            $('#wrapper-input-prospect').toggleClass('d-none', !isExternal);
        });
    },

    /**
     * Carga los catálogos necesarios para los modales
     */
    loadCatalogs: function() {
        const self = this;
        Sys_Core.Net.get({
            url: this.config.endpoints.productLines,
            onSuccess: function (res) {
                self.state.productLines = res.data;
                // Una vez cargados, poblamos el select del modal
                self.renderProductLines();
            }
        });
    },

    /**
     * Puebla el select del modal de catalogación
     */
    renderProductLines: function() {
        // Usamos el helper de Sys_Core para poblar el select de forma limpia
        Sys_Core.UI.fillSelect('#mdl-sel-line', this.state.productLines, {
            valueField: 'idlineaproducto',
            textField: 'descripcion', // Puedes usar 'cve_linea_producto' si prefieres el código
            placeholder: 'Seleccione línea de producto...'
        });
    },

    /**
     * Carga inicial: Procesa el JSON "Workspace"
     */
    loadWorkspace: function () {
        const self = this;
        let url = this.config.endpoints.workspace(this.state.eventId);
        if (this.state.activeItem) {
            url += `?target_item=${this.state.activeItem}`;
        }
        
        Sys_Core.Net.get({
            url: url,
            onSuccess: function (res) {
                const { event, items, initial_item } = res.data;

                self.state.sidebarItems = items; 
                
                // 1. Hidratar Hero Header
                $('#lbl-folio-breadcrumb').text(event.folio);
                $('#lbl-event-title').text(event.titulo);
                $('#lbl-comprador-name').html(`<i class="ri-user-star-line me-1 text-primary"></i> ${event.comprador_nombre}`);
                $('#lbl-status-header').text(event.estatus_evento);

                // 2. Renderizar Menú Lateral (Partidas)
                self.renderSidebar(items, initial_item.specs.idrequisicionarticulo);

                // 3. Cargar la comparativa inicial
                self.processItemData(initial_item.specs, initial_item.quotations);

                // 4. Feedback Visual: Si venimos de un target, notificamos al usuario
                if (self.state.activeItem) {
                    Sys_Core.UI.notify(`Enfoque automático en partida #${self.state.activeItem}`, 'info');
                }
            }
        });
    },

    /**
     * Cambia el enfoque a otra partida del mismo evento
     */
    switchItem: function(idReqArt) {
        const self = this;
        
         // 1. Sincronizar Estado Global
        this.state.activeItem = idReqArt;

        // 2. Feedback Visual
        // Limpiamos el grid y ponemos un loader sutil para indicar actividad
        this.dom.$grid.html(`
            <div class="col-12 text-center p-5 opacity-50">
                <div class="spinner-border text-primary shadow-sm" role="status" style="width: 3rem; height: 3rem;"></div>
                <p class="mt-3 fs-13 fw-bold text-uppercase ls-1">Sincronizando comparativa...</p>
            </div>
        `);
        
        Sys_Core.Net.get({
            url: this.config.endpoints.getItem(idReqArt),
            onSuccess: function (res) {
                // Extraemos la data del nuevo formato de respuesta
                const { item, cotizaciones } = res.data;

                // 4. Actualizar Estado Interno
                self.state.targetPrice = parseFloat(item.precio_objetivo);
                self.state.quotes = cotizaciones;

                self.processItemData(item, cotizaciones);
                self.updateSidebarUI(idReqArt); // Marca el ítem como activo en la izquierda
            }
        });
    },

    /**
     * Actualiza la clase activa en el menú lateral sin re-renderizar todo el sidebar
     */
    updateSidebarUI: function(idActive) {
        $('.item-negotiation').removeClass('bg-light border-primary-subtle shadow-sm');
        $(`.item-negotiation[data-id="${idActive}"]`).addClass('bg-light border-primary-subtle shadow-sm');
    },

    processItemData: function(specs, quotations) {
        this.state.activeItem = specs.idrequisicionarticulo;
        this.state.targetPrice = parseFloat(specs.precio_objetivo);
        this.state.quotes = quotations;
        const isCataloged = (specs.inventarioid !== null && specs.inventarioid !== undefined);

        this.toggleReadOnlyMode(isCataloged);        
        this.renderSpecs(specs);
        this.renderComparison(isCataloged);
    },

    /**
     * Gestiona la disponibilidad de la UI según el estado de la partida
     */
    toggleReadOnlyMode: function(isCataloged) {
        const $form = this.dom.$form;
        const $btnSave = this.dom.$btnSave;

        if (isCataloged) {
            // Modo Lectura: Deshabilitar inputs y botón de captura
            $form.find('input, select, textarea').prop('disabled', true);
            $btnSave.prop('disabled', true).html('<i class="ri-lock-2-line me-1"></i> Partida Cerrada');
            $form.closest('.card').addClass('opacity-75'); // Efecto Ghost
        } else {
            // Modo Edición: Habilitar todo
            $form.find('input, select, textarea').prop('disabled', false);
            $('#txt-tc').prop('disabled', false); // Asegurar casos especiales
            $btnSave.prop('disabled', false).html('<i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Agregar al Cuadro');
            $form.closest('.card').removeClass('opacity-75');
        }
    },

    // --- RENDERIZADORES (Diseño Graphite & Ghost Conservado) ---

    renderSidebar: function(items, activeId) {
        let html = '';
        items.forEach(i => {
            const isActive = i.idrequisicionarticulo == activeId ? 'bg-light border-primary-subtle shadow-sm' : '';
            // Check de finalizado
            const isDone = i.inventarioid !== null;

            html += `
            <div class="p-3 border-bottom cursor-pointer item-negotiation transition-all ${isActive}" data-id="${i.idrequisicionarticulo}">
                <!-- 1. Nivel Superior: Identidad y Estatus de Compliance -->
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="flex-grow-1 me-2">
                        <h6 class="fs-13 mb-0 ${isDone ? 'text-success' : 'text-body'} fw-bold text-uppercase ls-1 text-truncate" style="max-width: 180px;">
                            ${isDone ? '<i class="ri-checkbox-circle-fill me-1"></i>' : ''} ${i.descripcion}
                        </h6>
                    </div>
                    <span class="badge ${isDone ? 'bg-success-subtle text-success' : 'bg-info-subtle text-info'} border fs-10 px-2">
                        ${isDone ? 'COMPLETADO' : i.total_cotizaciones + ' Ofertas'}
                    </span>
                </div>

                <!-- 2. Nivel Inferior: Datos Técnicos y Meta Financiera -->
                <div class="d-flex justify-content-between align-items-end">
                    <div class="flex-grow-1">
                        <small class="text-muted text-uppercase fs-9 d-block ls-1 mb-1">Cantidad Solicitada</small>
                        <span class="text-body fw-bold fs-12">${parseFloat(i.cantidad)} PZA</span>
                    </div>
                    <div class="text-end flex-shrink-0">
                        <small class="text-muted d-block fs-9 text-uppercase ls-1 mb-1">Presupuesto (Neto)</small>
                        <b class="text-primary fs-14">${Sys_Core.Format.toCurrency(i.precio_objetivo)}</b>
                    </div>
                </div>
            </div>`;
        });
        this.dom.$sidebar.html(html);
    },

    renderSpecs: function(specs) {
        // Actualizamos el Precio Objetivo en el header del cuadro comparativo
        $('#lbl-target-price').text(Sys_Core.Format.toCurrency(specs.precio_objetivo));
    },

    getNormalizedPrice: function(q) {
        return parseFloat(q.precio_base_mxn || 0);
    },

    renderComparison: function (isCataloged) {
        let html = '';
        if (this.state.quotes.length === 0) {
            html = `<div class="col-12 text-center p-5 text-muted opacity-50"><p>Sin propuestas para esta partida.</p></div>`;
        } else {
            this.state.quotes.forEach(q => {
                const priceBaseMXN = this.getNormalizedPrice(q);
                const diff = this.state.targetPrice - priceBaseMXN;
                const isWinner = q.es_ganadora == 1;
                const cardClass = isWinner ? 'border-success bg-success-subtle bg-opacity-10' : 'border-light-subtle';
                const taxLabel = (parseInt(q.iva_inc) === 1) 
                    ? '<span class="text-info">IVA Incluido</span>' 
                    : '<span class="text-muted">Precio Neto</span>';

                // Lógica de etiquetas de impuestos para la oferta original
                const originalTaxLabel = (parseInt(q.iva_inc) === 1) ? 'IVA Incluido' : 'Precio Neto';
                const originalPriceFormatted = `${q.moneda} ${parseFloat(q.precio_unitario).toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
                let actionButton = '';

                if (isCataloged) {
                    // Si ya está catalogado, solo mostramos un badge de éxito en la ganadora
                    if (q.es_ganadora == 1) {
                        actionButton = `
                            <div class="alert alert-success border-0 shadow-sm mb-0 text-center py-2">
                                <i class="ri-checkbox-circle-fill me-1"></i> PROCESO COMPLETADO
                            </div>`;
                    } else {
                        actionButton = `<button class="btn btn-light w-100 disabled opacity-50">SIN SELECCIÓN</button>`;
                    }
                } else {
                    // Si sigue abierto, mostramos los botones de acción normales (Elegir o Promover)
                    actionButton = q.es_ganadora == 1 
                        ? `<button class="btn btn-success w-100 btn-sm fw-bold btn-action shadow" data-id="${q.idcotizacion}" data-action="promote">
                            <i class="ri-rocket-2-line me-1"></i> PROMOVER A SKU
                        </button>`
                        : `<button class="btn btn-outline-primary w-100 btn-sm btn-action" data-id="${q.idcotizacion}" data-action="select">
                            ELEGIR GANADORA
                        </button>`;
                }
                
                html += `
                <div class="col-md-6 col-xxl-4 animate__animated animate__fadeIn">
                    <div class="card border ${cardClass} shadow-none mb-0 h-100">
                        <div class="card-header bg-light-subtle border-0 py-2 d-flex justify-content-between align-items-center">
                            <span class="badge bg-white-subtle text-muted border border-light-subtle fs-10 ls-1 text-uppercase">${q.tipo_fuente}</span>
                            <i class="ri-more-2-fill text-muted"></i>
                        </div>
                        <div class="card-body">
                            <!-- Nombre del Proveedor -->
                            <h6 class="fs-14 fw-bold text-body mb-3 text-truncate" title="${q.razon_social}">${q.razon_social}</h6>

                            <!-- Números -->
                            <div class="row align-items-center mb-4">
                                <div class="col-7">
                                    <h4 class="mb-0 fw-bold text-primary">${Sys_Core.Format.toCurrency(priceBaseMXN)}</h4>
                                    <small class="text-muted text-uppercase fs-10 fw-bold ls-1">Subtotal</small>
                                </div>
                                <div class="col-5 text-end">
                                    <span class="fw-bold fs-12 d-block ${diff >= 0 ? 'text-success' : 'text-danger'}">
                                        <i class="${diff >= 0 ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill'}"></i>
                                        ${Sys_Core.Format.toCurrency(Math.abs(diff))}
                                    </span>
                                    <small class="text-muted fs-10 text-uppercase">${diff >= 0 ? 'Ahorro' : 'Déficit'}</small>
                                </div>
                            </div>

                            <!-- Sección de Referencia (Espejo de la captura) -->
                            <div class="bg-light-subtle rounded p-2 mb-4 border border-dashed border-light">
                                <div class="d-flex justify-content-between fs-11 text-muted mb-1">
                                    <span>Oferta Original:</span>
                                    <span class="text-body fw-bold">${originalPriceFormatted}</span>
                                </div>
                                <div class="d-flex justify-content-between fs-11 text-muted">
                                    <span>Condición:</span>
                                    <span class="badge bg-light text-muted border border-light-subtle fs-9">${originalTaxLabel}</span>
                                </div>
                                ${q.tipo_cambio > 1 ? `
                                <div class="d-flex justify-content-between fs-10 text-muted mt-1 pt-1 border-top border-light">
                                    <span>Tipo de Cambio:</span>
                                    <span>x ${parseFloat(q.tipo_cambio).toFixed(4)}</span>
                                </div>` : ''}
                            </div>

                            <!-- Evidencias -->
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fs-11 text-muted text-uppercase fw-bold ls-1">Evidencias:</span>
                                <div>
                                    ${q.url_pdf_cotizacion ? `<a href="${Sys_Core.Config.baseUrl}/${q.url_pdf_cotizacion}" target="_blank" class="me-2 text-danger" title="Ver Cotización PDF"><i class="ri-file-pdf-line fs-18"></i></a>` : ''}
                                    ${q.url_foto_producto ? `<a href="${Sys_Core.Config.baseUrl}/${q.url_foto_producto}" target="_blank" class="text-info" title="Ver Evidencia Física"><i class="ri-image-line fs-18"></i></a>` : ''}
                                </div>
                            </div>

                            <!-- Botonera de Acción -->
                            ${actionButton}
                        </div>
                    </div>
                </div>`;
            });
        }
        this.dom.$grid.html(html);
    },

    // --- ACCIONES (Persistencia) ---

    submitQuote: function() {
        const self = this;
        const formData = new FormData(this.dom.$form[0]);
        formData.append('idrequisicionarticulo', this.state.activeItem);
        
        // Capturamos el tipo de fuente seleccionado
        const sourceType = $('#sel-source-type').val(); 

        // Si es PROSPECTO o RETAIL, enviamos el flag de prospecto y el nombre libre
        if (sourceType !== 'REGISTRADO') {
            formData.append('es_prospecto', '1');
            formData.append('nombre_prospecto', $('#txt-prospect-name').val());
        }

        formData.append('tipo_fuente', sourceType); 

        // Integramos el flag de Pago Inmediato (Spot Buy)
        // Usamos ternario para asegurar que el backend reciba '1' o '0'
        formData.append('pago_inmediato', $('#check-pago-inmediato').is(':checked') ? '1' : '0');

        // Integramos el flag de IVA Incluido
        // Usamos ternario para asegurar que el backend reciba '1' o '0'
        formData.append('iva_inc', $('#check-iva-inc').is(':checked') ? '1' : '0');

        Sys_Core.Net.post({
            url: this.config.endpoints.saveQuote,
            payload: formData,
            $btn: this.dom.$btnSave,
            contentType: false,
            processData: false,
            onDone: () => {
                // LIMPIEZA: Reset del formulario y ocultar campos condicionales
                self.dom.$form[0].reset();
                $('#group-tc, #container-url-referencia, #container-spot-buy').addClass('d-none');
                $('#wrapper-select-provider').removeClass('d-none');
                $('#wrapper-input-prospect').addClass('d-none');

                // AUTO-REFRESCO: Llamamos a switchItem para ver la nueva card inmediatamente
                self.switchItem(self.state.activeItem);
            }
        });
    },

    executeSelectWinner: function(idCot) {
        Sys_Core.UI.confirm({ title: '¿Confirmar Selección?', text: 'Puedes seleccionar una diferente más adelante.', confirmText: 'Sí, elegir ganadora' }).then((res) => {
            if (res.isConfirmed) {
                Sys_Core.Net.post({
                    url: this.config.endpoints.selectWinner(idCot),
                    payload: { idcotizacion: idCot },
                    onDone: () => this.switchItem(this.state.activeItem)
                });
            }
        });
    },

    openPromoteModal: function (quoteId) {
        const self = this;
        const quote = this.state.quotes.find(q => q.idcotizacion == quoteId);
        if (!quote) return;

        // 1. Obtener la ficha técnica del estado (sidebarItems)
        const itemData = this.state.sidebarItems.find(i => i.idrequisicionarticulo == this.state.activeItem);
        
        if (!itemData) {
            Sys_Core.UI.notify('Error: No se encontró la información técnica del artículo.', 'danger');
            return;
        }

        // 2. HIDRATACIÓN DEL CONTEXTO (Banners de solo lectura)
        $('#mdl-id-cotizacion').val(quote.idcotizacion);
        $('#mdl-id-req-art').val(this.state.activeItem);
        
        // Estos son <h6>, usamos .text()
        $('#mdl-item-name').text(itemData.descripcion); 
        $('#mdl-provider-name').text(quote.razon_social); // Asegúrate que este ID exista en tu HTML
        $('#mdl-winner-price').text(Sys_Core.Format.toCurrency(this.getNormalizedPrice(quote)));

        // 3. PREPARACIÓN DEL FORMULARIO (Campos editables)
        $('#mdl-txt-sku').val('').focus();
        $('#mdl-sel-line').val('').trigger('change');
        $('#mdl-txt-desc-final').val(itemData.descripcion); // Heredamos la descripción original

        // 4. MOSTRAR MODAL
        const modalEl = document.getElementById('modalPromoteSku');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    },

    /**
     * Envía la data técnica al SourcingService para crear el registro en WMS
     * y cerrar el ciclo de la partida.
     */
    submitFinalPromotion: function () {
        const self = this;
        const $btn = $('#btn-execute-promotion');

        // Recolección de datos técnicos
        const payload = {
            idcotizacion: $('#mdl-id-cotizacion').val(),
            idrequisicionarticulo: $('#mdl-id-req-art').val(),
            cve_articulo: $.trim($('#mdl-txt-sku').val()),
            lineaproductoid: $('#mdl-sel-line').val(),
            tipo_elemento: $('#mdl-sel-type').val(),
            unidad_salida: $('#mdl-sel-uom').val(),
            descripcion_final: $.trim($('#mdl-txt-desc-final').val())
        };

        Sys_Core.UI.confirm({
            title: '¿Confirmar Catalogación?',
            text: 'El artículo se creará oficialmente en el WMS y el sourcing se dará por concluido para esta partida.',
            confirmText: 'Sí, crear SKU'
        }).then((result) => {
            if (result.isConfirmed) {
                
                Sys_Core.Net.post({
                    url: this.config.endpoints.promote,
                    payload: payload,
                    $btn: $btn, // Muestra spinner en el botón del modal
                    onDone: function (res) {
                        // 1. Cerrar Modal
                        bootstrap.Modal.getInstance(document.getElementById('modalPromoteSku')).hide();

                        // 2. Feedback de Negocio (Considerando el "Congelamiento" por Prospecto)
                        // El backend devolverá en el mensaje si se congeló por Onboarding
                        Sys_Core.UI.notify(res.message, 'success');

                        // 3. Finalización: Redirigir al Panel de Negociaciones
                        // para que el comprador vea que su folio ya avanzó de estatus.
                        setTimeout(() => {
                            Sys_Core.Navigation.to('com_sourcing');
                        }, 1500);
                    }
                });
            }
        });
    }
};

// Punto de Entrada: /negociaciones/detalle/22
$(document).ready(() => {
    const idEvent = window.location.pathname.split('/').pop();
    if (idEvent) SourcingDetail.init(idEvent);
});