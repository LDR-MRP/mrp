/**
 * MRP System - Requisition Read-Only View
 * @module RequisitionRead
 * @description Vista de expediente de requisición con acciones dinámicas según estado.
 */

const RequisitionRead = {
    config: {
        apiBase: `${Sys_Core.Config.baseUrl}/api/v1/requisitions`
    },

    state: {
        id: null,
        data: null 
    },

    dom: {},

    init: function () {
        this.extractId();
        if (!this.state.id) {
            Sys_Core.Navigation.to('com_requisicion');
            return;
        }
        this.cacheDOM();
        this.bindEvents();
        this.loadData();
    },

    extractId: function () {
        const pathSegments = window.location.pathname.split('/');
        const possibleId = pathSegments[pathSegments.length - 1];
        if (!isNaN(possibleId) && possibleId > 0) {
            this.state.id = parseInt(possibleId);
        }
    },

    cacheDOM: function () {
        this.dom = {
            $lblId: $('#lbl-idrequisicion'),
            $lblEstatus: $('#lbl-estatus'),
            $lblTitulo: $('#lbl-titulo'),
            $lblSolicitante: $('#lbl-solicitante'),
            $lblDepto: $('#lbl-departamento'),
            $lblFechaReq: $('#lbl-fecha-requerida'),
            $lblPrioridad: $('#lbl-prioridad'),
            $lblCreacion: $('#lbl-fecha-creacion'),
            $lblJustificacion: $('#lbl-justificacion'),
            $lblTotal: $('#lbl-total-monto'),
            $tblPartidas: $('#tbl-read-partidas'),
            $actionContainer: $('#action-buttons-container'),
            $relatedPOsCard: $('#card-related-pos'), // Tarjeta lateral para ver las OCs hijas
            $relatedPOsList: $('#list-related-pos')
        };
    },

    bindEvents: function () {
        this.dom.$actionContainer.on('click', '.action-btn', (e) => this.handleAction(e));
        this.dom.$actionContainer.on('click', '#btn-export-pdf', () => this.printRequisition());
        this.dom.$tblPartidas.on('click', '.btn-comparativa', (e) => this.openSourcingModal(e));
        $('#formNuevaCotizacion').on('submit', (e) => this.submitQuotation(e));
        // Evento delegado para el botón de Ganadora en la tabla dinámica del modal
        $('#tblComparativa').on('click', '.btn-select-winner', (e) => this.handleSelectWinner(e));
        $('#btn-ejecutar-promocion').on('click', () => this.executePromotion());

        $('#tblComparativa').on('click', '.btn-promote-now', (e) => {
            const id = $(e.currentTarget).data('id');
            this.openPromoteModal(id);
        });

        this.handleCurrencyChange();

        this.dom.$actionContainer.on('click', '.action-btn', (e) => this.handleAction(e));
        $('#tblComparativa').on('click', '.btn-delete-quotation', (e) => this.handleDeleteQuotation(e));

        // Toggle para Proveedor Prospecto
        $(document).on('change', '#chk-es-prospecto', function() {
            const isProspecto = $(this).is(':checked');
            $('#container-select-proveedor').toggleClass('d-none', isProspecto);
            $('#container-input-prospecto').toggleClass('d-none', !isProspecto);
            
            // El select deja de ser requerido si es prospecto y viceversa
            $('[name="id_proveedor"]').prop('required', !isProspecto);
            $('[name="nombre_prospecto"]').prop('required', isProspecto);
        });

        // Toggle para Pago Inmediato dentro de la cotización
        $(document).on('change', '#chk-pago-inmediato-cot', function() {
            $('#section-url-cotizacion').toggleClass('d-none', !$(this).is(':checked'));
            $('[name="url_referencia"]').prop('required', $(this).is(':checked'));
        });
    },

    /**
     * Confirma y ejecuta el borrado de una cotización.
     */
    handleDeleteQuotation: function(e) {
        const id = $(e.currentTarget).data('id');
        
        Sys_Core.UI.confirm({
            title: '¿Eliminar propuesta?',
            text: 'Esta cotización dejará de ser visible en el cuadro comparativo.',
            confirmText: 'Sí, eliminar'
        }).then((result) => {
            if (result.isConfirmed) {
                Sys_Core.Net.post({
                    url: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/quotations/${id}`,
                    method: 'DELETE', // Semantics matter!
                    onDone: (res) => {
                        Sys_Core.UI.notify(res.message, 'info');
                        // Refresh the table
                        this.loadComparisonData(this.state.currentPartidaId);
                    }
                });
            }
        });
    },

    /**
     * Maneja la selección de la cotización ganadora con confirmación.
     */
    handleSelectWinner: function(e) {
        const idCotizacion = $(e.currentTarget).data('id');
        const $btn = $(e.currentTarget);

        Sys_Core.UI.confirm({
            title: '¿Asignar Proveedor?',
            text: 'Al marcar esta cotización como ganadora, el precio de la requisición se actualizará con este valor negociado.',
            icon: 'question',
            confirmText: 'Sí, asignar'
        }).then((result) => {
            if (result.isConfirmed) {
                this.executeSelectWinner(idCotizacion, $btn);
            }
        });
    },

    /**
     * Ejecuta la petición RESTful al nuevo endpoint.
     */
    executeSelectWinner: function(idCotizacion, $btn) {
        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/quotations/${idCotizacion}/select-winner`,
            payload: {}, // El ID viaja en la URL
            $btn: $btn,
            onDone: (res) => {
                Sys_Core.UI.notify(res.message, 'success');

                // 1. Refrescar el Cuadro Comparativo (para ver la fila verde de éxito)
                this.loadComparisonData(this.state.currentPartidaId);

                // 2. Refrescar el Expediente Principal (para ver el nuevo Subtotal y Total)
                this.loadData();
                
                // Opcional: Cerrar el modal tras 1 segundo para que el usuario vea el cambio
                setTimeout(() => $('#modalSourcing').modal('hide'), 1500);
            }
        });
    },

    openSourcingModal: function(e) {
        const idPartida = $(e.currentTarget).data('id');
        this.state.currentPartidaId = idPartida;
        
        // 1. Cargar catálogos de proveedores en el select del modal
        this.loadSuppliersForSourcing();
        
        // 2. Cargar datos de la comparativa
        this.loadComparisonData(idPartida);
        
        // 3. Cargar datos de moneda
        this.loadCurrencies();

        $('#modalSourcing').modal('show');
    },

    /**
     * Carga el catálogo de proveedores autorizados y prospectos 
     * para alimentar el formulario de cotizaciones en el modal.
     */
    loadSuppliersForSourcing: function() {
        // 1. Selector del select dentro del modal de cotizaciones
        const $select = $('#formNuevaCotizacion select[name="id_proveedor"]');
        
        // Estado de carga visual sutil
        $select.empty().append('<option value="">Cargando catálogo...</option>');

        // 2. Petición vía Motor de Red (Stateless)
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers`,
            onSuccess: (res) => {
                if (res.status && res.data) {
                    // 3. Poblar usando la utilidad premium del Core
                    Sys_Core.UI.fillSelect($select, res.data, {
                        valueField: 'id_proveedor',
                        textField: 'razon_social',
                        placeholder: 'Seleccione un proveedor potencial...'
                    });
                }
            }
        });
    },

    loadCurrencies: function() {
        const $select = $('#sel-moneda-cotizacion');
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/currencies`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect($select, res.data, {
                    valueField: 'cve_moneda',
                    textField: 'cve_moneda',
                    selectedValue: 'MXN'
                });
                // IMPORTANTE: Disparar el cambio para que el TC se bloquee inicialmente
                $select.trigger('change');
            }
        });
    },

    handleCurrencyChange: function() {
        // Usamos delegación de eventos por si el modal se refresca
        $(document).on('change', '#sel-moneda-cotizacion', function() {
            const $tc = $('#txt-tc-cotizacion');
            const moneda = $(this).val();

            if (moneda === 'MXN') {
                $tc.val('1.000000')
                   .prop('readonly', true)
                   .addClass('bg-light')
                   .removeClass('border-primary');
            } else {
                // Si es extranjero, habilitamos y damos foco para capturar el TC
                $tc.val('')
                   .prop('readonly', false)
                   .removeClass('bg-light')
                   .addClass('border-primary')
                   .focus();
                
                Sys_Core.UI.notify('Ingrese el tipo de cambio para ' + moneda, 'info');
            }
        });
    },

    loadComparisonData: function(idPartida) {
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/comparison/${idPartida}`,
            onSuccess: (res) => {
                const { item, cotizaciones } = res.data;
                const targetPrice = parseFloat(item.precio_objetivo);

                $('#sourcing-item-name').text(item.descripcion_sourcing || 'Sourcing');
                $('#sourcing-target-price').text(Sys_Core.Format.toCurrency(targetPrice));
                
                const $tbody = $('#tblComparativa tbody').empty();
                
                if (cotizaciones.length === 0) {
                    $tbody.html('<tr><td colspan="5" class="text-center py-4 text-muted fs-12">No hay cotizaciones registradas para este artículo.</td></tr>');
                    return;
                }

                cotizaciones.forEach(cot => {
                    const tc = parseFloat(cot.tipo_cambio) || 1.0;
                    const precioUnitario = parseFloat(cot.precio_unitario) || 0;
                    const precioFinalMXN = precioUnitario * tc;
                    const diff = targetPrice - precioFinalMXN;
                    const diffClass = diff >= 0 ? 'text-success' : 'text-danger';
                    const diffIcon = diff >= 0 ? 'ri-arrow-down-s-fill' : 'ri-arrow-up-s-fill';
                    
                    const isWinner = parseInt(cot.es_ganadora) === 1;
                    const actionButtons = isWinner ? 
                        `<button class="btn btn-sm btn-success btn-promote-now shadow-sm" data-id="${idPartida}">
                            <i class="ri-checkbox-circle-line align-middle"></i> Promover a SKU
                        </button>` : 
                        `<button class="btn btn-sm btn-outline-success btn-select-winner" data-id="${cot.idcotizacion}">Elegir Ganadora</button>`;

                    // --- NUEVOS COMPONENTES VISUALES ---
                    // 1. Botón de Foto (Solo si existe)
                    const photoBtn = cot.url_foto_producto ? 
                        `<a href="${Sys_Core.Config.baseUrl}/${cot.url_foto_producto}" target="_blank" class="btn btn-sm btn-soft-info" title="Ver Fotografía de Referencia"><i class="ri-image-line"></i></a>` : '';

                    // 2. Bloque de Especificaciones Particulares
                    const specsHtml = cot.specs_particulares_proveedor ? 
                        `<div class="mt-2 p-2 bg-light rounded border-start border-2 border-info">
                            <small class="d-block fw-bold text-uppercase fs-9 text-muted mb-1">Oferta Particular:</small>
                            <p class="mb-0 fs-11 text-dark fst-italic">${cot.specs_particulares_proveedor}</p>
                        </div>` : '';

                    const badgePago = parseInt(cot.pago_inmediato) === 1 
                        ? '<span class="badge bg-soft-info text-info ms-1"><i class="ri-flashlight-line"></i> SPOT BUY</span>' 
                        : '';

                    const urlLink = cot.url_referencia 
                        ? `<a href="${cot.url_referencia}" target="_blank" class="ms-1 text-primary" title="Ver Link Retail"><i class="ri-external-link-line"></i></a>` 
                        : '';

                    const html = `
                        <tr class="${isWinner ? 'table-success' : ''} align-middle">
                            <td style="max-width: 300px;">
                                <div class="fw-bold text-dark fs-13">
                                    ${cot.razon_social || cot.nombre_prospecto} 
                                    ${badgePago}
                                    ${urlLink}
                                </div>
                                <div class="fs-10 text-muted mb-1">Ref: ${cot.moneda} ${Sys_Core.Format.toCurrency(precioUnitario)}</div>
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    ${cot.estatus_onboarding === 'Aprobado' ? 
                                        '<span class="badge bg-soft-success text-success border border-success-subtle fs-10"><i class="ri-shield-check-line"></i> Docs OK</span>' : 
                                        '<span class="badge bg-soft-warning text-warning border border-warning-subtle fs-10"><i class="ri-error-warning-line"></i> Docs Pendientes</span>'}
                                    <span class="text-muted fs-11">| <i class="ri-mail-line"></i> ${cot.contacto_email || 'Sin correo'}</span>
                                </div>
                                ${specsHtml} <!-- Inyección de las notas técnicas del proveedor -->
                            </td>
                            <td class="text-center text-muted font-monospace fs-11">
                                x ${tc.toFixed(4)}
                            </td>
                            <td class="text-end fw-bold text-primary fs-14">
                                ${Sys_Core.Format.toCurrency(precioFinalMXN)}
                            </td>
                            <td class="text-center fw-medium ${diffClass}">
                                <div class="d-flex flex-column align-items-center">
                                    <span class="fs-12"><i class="${diffIcon}"></i> ${Sys_Core.Format.toCurrency(Math.abs(diff))}</span>
                                    <small class="fs-10 opacity-75">${diff >= 0 ? 'Ahorro' : 'Déficit'}</small>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column gap-2">
                                    <div class="btn-group">
                                        <a href="${Sys_Core.Config.baseUrl}/${cot.url_pdf_cotizacion}" target="_blank" class="btn btn-sm btn-light border" title="Ver PDF Oficial"><i class="ri-file-pdf-line"></i></a>
                                        ${photoBtn} <!-- Inyección del botón de foto -->
                                        <!-- NEW DELETE BUTTON -->
                                        <button class="btn btn-sm btn-soft-danger btn-delete-quotation" data-id="${cot.idcotizacion}" title="Eliminar Cotización">
                                            <i class="ri-delete-bin-line"></i>
                                        </button>
                                    </div>
                                    ${actionButtons}
                                </div>
                            </td>
                        </tr>`;
                    $tbody.append(html);
                });
            }
        });
    },

    /**
     * Envía la propuesta del proveedor al servidor incluyendo archivos adjuntos.
     */
    submitQuotation: function(e) {
        e.preventDefault();
        
        // 1. Encapsulate all fields (including files)
        const formData = new FormData(e.target);
        
        // 2. Inject context IDs
        formData.append('idrequisicionarticulo', this.state.currentPartidaId);
        formData.append('idrequisicion', this.state.id);
        
        formData.append('pago_inmediato', $('#chk-pago-inmediato-cot').is(':checked') ? 1 : 0);
        formData.append('es_prospecto', $('#chk-es-prospecto').is(':checked') ? 1 : 0);
        
        formData.append('idrequisicionarticulo', this.state.currentPartidaId);
        formData.append('idrequisicion', this.state.id);

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/quotations`,
            payload: formData,
            // Sys_Core.Net.post will automatically set contentType: false and processData: false
            // because the payload is an instance of FormData.
            onDone: (res) => {
                Sys_Core.UI.notify(res.message, 'success');
                
                // 3. Refresh the Comparison Table to show the new entry
                this.loadComparisonData(this.state.currentPartidaId);
                
                // 4. Reset form and clear file previews
                e.target.reset();
                
                // Re-render the main UI to update totals if needed
                this.loadData();
            }
        });
    },

    /**
     * Carga el catálogo de líneas de producto para el modal de catalogación.
     */
    loadProductLines: function() {
        const $select = $('#formPromoverCatalog select[name="lineaproductoid"]');
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/catalogs/product-lines`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect($select, res.data, {
                    valueField: 'idlineaproducto',
                    textField: 'descripcion',
                    placeholder: 'Seleccione línea de producto...'
                });
            }
        });
    },

    /**
     * Abre el modal para convertir el ítem de sourcing en un SKU oficial.
     * Se invoca desde el botón "Ganadora" (éxito) o directamente si ya hay una.
     */
    openPromoteModal: function(idReqArticulo) {
        $('#formPromoverCatalog [name="idrequisicionarticulo"]').val(idReqArticulo);
        this.loadProductLines();
        $('#modalPromoverCatalog').modal('show');
    },

    /**
     * Ejecuta la petición final de Sourcing -> Catálogo Maestro
     */
    executePromotion: function() {
        const form = document.getElementById('formPromoverCatalog');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const payload = Object.fromEntries(new FormData(form).entries());

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/promote-to-catalog`,
            payload: payload,
            $btn: $('#btn-ejecutar-promocion'),
            onDone: (res) => {
                Sys_Core.UI.alert('¡Éxito!', res.message, 'success').then(() => {
                    $('#modalPromoverCatalog').modal('hide');
                    $('#modalSourcing').modal('hide');
                    // RECARGA VITAL: La partida azul (Sourcing) ahora se verá como una partida normal (SKU)
                    this.loadData(); 
                });
            }
        });
    },

    printRequisition: function() {
        const requisitionId = this.state.id;
        if (!requisitionId) return;

        // Notificación de cortesía
        Sys_Core.UI.notify('Generando documento...', 'info');

        Sys_Core.Net.downloadPdf({
            url: `${this.config.apiBase}/${requisitionId}/pdf`,
            filename: `Requisicion_${requisitionId}.pdf`
        });
    },

    loadData: function () {
        Sys_Core.UI.toggleLoader('.page-content', true);
        Sys_Core.Net.get({
            url: `${this.config.apiBase}/${this.state.id}`,
            onSuccess: (res) => {
                if (res.status) {
                    this.state.data = res.data;
                    this.renderUI();
                } else {
                    Sys_Core.UI.alert('Error', 'No se pudo cargar el expediente.', 'error');
                }
            }
        });
        Sys_Core.UI.toggleLoader('.page-content', false);
    },

    renderUI: function () {
        const d = this.state.data;

        this.dom.$lblId.text(d.idrequisicion);
        this.dom.$lblTitulo.text(d.titulo || 'Sin título de referencia');
        this.dom.$lblSolicitante.text(d.solicitante || 'Usuario del Sistema');
        this.dom.$lblDepto.text(d.departamento || 'No asignado');
        this.dom.$lblFechaReq.text(Sys_Core.Format.toDate(d.fecha_requerida));
        this.dom.$lblCreacion.text(Sys_Core.Format.toDate(d.fecha));
        this.dom.$lblJustificacion.text(d.justificacion || 'Sin justificación proporcionada.');
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(d.monto_estimado));

        // FIX: Actualización de Badge sin destruir el elemento (Consistencia)
        const badgeClass = this.getStatusConfig(d.estatus);        
        this.dom.$lblEstatus
            .removeClass() 
            .addClass(`badge ${badgeClass} px-3 py-2 text-capitalize fs-12 shadow-sm ms-3`)
            .text(d.estatus);
  
        const prioColors = { 'baja': 'bg-info', 'media': 'bg-warning', 'alta': 'bg-danger', 'critica': 'bg-dark' };
        const prioColor = prioColors[d.prioridad?.toLowerCase()] || 'bg-secondary';
        
        this.dom.$lblEstatus
            .removeClass() // Limpiamos todas las clases previas
            .addClass(`badge ${badgeClass} px-3 py-2 text-capitalize fs-12 shadow-sm ms-3`)
            .text(d.estatus);
        this.dom.$lblPrioridad
            .removeClass()
            .addClass(`badge ${prioColor} fs-12 px-3 py-1 shadow-sm`)
            .text((d.prioridad || 'Normal')
            .toUpperCase());

        // LÓGICA SPOT BUY: Mostrar información de pago inmediato si aplica
        if (d.tipo_requisicion === 'spot_buy') {
            $('#section-direct-info').removeClass('d-none');
            $('#lbl-pago-sugerido').text(d.nombre_metodo_pago || 'Pago Electrónico');
            $('#link-referencia').attr('href', d.url_referencia || '#').text(d.url_referencia ? 'Ver producto en sitio externo' : 'No proporcionado');
        } else {
            $('#section-direct-info').addClass('d-none');
        }

        this.dom.$tblPartidas.empty();
        if (d.items && d.items.length > 0) {
            d.items.forEach(item => {
                const isSourcing = parseInt(item.es_sourcing) === 1;
                const progress = item.porcentaje_surtido || 0;
                const barColor = progress >= 100 ? 'bg-success' : (progress > 0 ? 'bg-warning' : 'bg-light');
                
                // Normalización de datos
                const sku = item.cve_articulo || (isSourcing ? 'SOURCING' : 'N/A');
                const desc = item.descripcion || 'Sin descripción';
                const price = parseFloat(item.precio_unitario_estimado) || 0;
                const qty = parseFloat(item.cantidad) || 0;
                const subtotal = parseFloat(item.subtotal) || (qty * price);

                const html = `
                    <tr class="${isSourcing ? 'border-start border-4 border-primary' : ''} align-middle">
                        <!-- 1. DESCRIPCIÓN -->
                        <td style="width: 35%;">
                            <div class="ps-2">
                                <div class="fw-bold text-dark fs-13">${sku}</div>
                                <div class="text-muted fs-11 text-truncate" style="max-width: 250px;">
                                    ${isSourcing ? '<i class="ri-auction-line text-primary me-1"></i>' : ''}${desc}
                                </div>
                            </div>
                        </td>

                        <!-- 2. CANTIDAD / ARRIBO -->
                        <td class="text-center" style="width: 20%;">
                            <div class="d-flex justify-content-between mb-1 fs-11">
                                <span class="fw-bold">${item.qty_comprada || 0} / ${qty}</span>
                                <span class="text-muted">${progress}%</span>
                            </div>
                            <div class="progress" style="height: 5px; background-color: #f0f0f0;">
                                <div class="progress-bar ${barColor}" style="width: ${progress}%"></div>
                            </div>
                        </td>

                        <!-- 3. PRECIO UNITARIO -->
                        <td class="text-end" style="width: 15%;">
                            <span class="text-muted fs-12">${Sys_Core.Format.toCurrency(price)}</span>
                        </td>

                        <!-- 4. SUBTOTAL (LA COLUMNA QUE FALTABA) -->
                        <td class="text-end" style="width: 15%;">
                            <span class="fw-bold text-primary fs-13">${Sys_Core.Format.toCurrency(subtotal)}</span>
                        </td>

                        <!-- 5. NOTAS / ACCIONES -->
                        <td class="text-center" style="width: 15%;">
                            <div class="d-flex justify-content-center align-items-center gap-2">
                                ${item.notas ? `<i class="ri-information-line text-info cursor-pointer fs-17" title="${item.notas}" data-bs-toggle="tooltip"></i>` : '---'}
                                
                                ${isSourcing ? `
                                    <button class="btn btn-sm btn-soft-primary p-1 lh-1 btn-comparativa shadow-none" 
                                            data-id="${item.idrequisicionarticulo}" 
                                            title="Ver Cuadro Comparativo">
                                        <i class="ri-scales-3-line fs-14"></i>
                                    </button>` : ''
                                }
                            </div>
                        </td>
                    </tr>`;
                this.dom.$tblPartidas.append(html);
            });
        } else {
            this.dom.$tblPartidas.html('<tr><td colspan="5" class="text-center py-4 text-muted">No hay partidas registradas.</td></tr>');
        }

        if (d.related_pos && d.related_pos.length > 0) {
            this.renderRelatedPOs(d.related_pos);
        }

        this.renderContextualActions(d.estatus);
    },

    renderRelatedPOs: function (pos) {
        const $card = $('#card-related-pos'); // Asegúrate de tener este ID en tu HTML
        const $list = $('#list-related-pos');
        
        if ($card.length) {
            $card.show();
            $list.empty();
            pos.forEach(po => {
                $list.append(`
                    <a href="${Sys_Core.Config.baseUrl}/com_orden/read/${po.idcompra}" class="list-group-item list-group-item-action border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold text-primary">OC #${po.idcompra}</div>
                                <small class="text-muted">${Sys_Core.Format.toDate(po.created_at)}</small>
                            </div>
                            <span class="badge bg-light">${po.estatus.toUpperCase()}</span>
                        </div>
                    </a>
                `);
            });
        }
    },

    // Cambia el nombre a getStatusConfig para mayor claridad semántica
    getStatusConfig: function (status) {
        const strStatus = status ? status.toLowerCase() : '';
        const config = {
            'borrador':   'bg-soft-secondary text-secondary',
            'pendiente':  'bg-soft-warning text-warning',
            'aprobada':   'bg-soft-success text-success',
            'rechazada':  'bg-soft-danger text-danger',
            'en compra':  'bg-soft-info text-info',
            'finalizada': 'bg-success text-white',
            'cancelada':  'bg-dark text-white'
        };
        return config[strStatus] || 'bg-secondary text-white';
    },

    renderContextualActions: function (status) {
        const d = this.state.data;
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r'); 
        const strStatus = status ? status.toLowerCase() : '';

        // Botón Volver
        let html = `<button type="button" class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;

        // Botón PDF
        html += `<button type="button" class="btn btn-outline-danger" id="btn-export-pdf"><i class="ri-file-pdf-line"></i> PDF</button>`;

        // --- LÓGICA DE USABILIDAD STP ---
        if (strStatus === 'finalizada' && d.tipo_requisicion === 'spot_buy') {
            const lastPO = d.related_pos ? d.related_pos[0] : null;
            html += `<hr class="my-2 border-light">`;
            html += `<div class="alert alert-success border-0 shadow-sm mb-2 fs-11 p-2">
                        <i class="ri-checkbox-circle-fill me-1"></i> Proceso automatizado por <b>Sistema</b>.
                     </div>`;
            if (lastPO) {
                html += `<button type="button" class="btn btn-primary w-100 shadow-sm animate__animated animate__fadeInUp" 
                                 data-redirect="com_orden/read/${lastPO.idcompra}">
                            <i class="ri-external-link-line align-middle me-1"></i> Ver Orden de Compra #${lastPO.idcompra}
                         </button>`;
            }
            this.dom.$actionContainer.html(html);
            return; // Salir de la función, no necesitamos el resto
        }

        // --- FLUJO ESTÁNDAR ---
        switch (strStatus) {
            case 'borrador':
                html += `<button type="button" class="btn btn-primary" data-redirect="com_requisicion/create/${this.state.id}"><i class="ri-pencil-line"></i> Editar Borrador</button>`;
                break;
            case 'pendiente':
                if (canApprove) {
                    html += `<button type="button" class="btn btn-danger action-btn" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button>`;
                    html += `<button type="button" class="btn btn-success action-btn" data-accion="approve"><i class="ri-check-line"></i> Aprobar Solicitud</button>`;
                }
                break;
            case 'aprobada':
            case 'en compra':
                if (canApprove) {
                    const btnLabel = (strStatus === 'aprobada') ? 'Generar Orden de Compra' : 'Continuar con Compra';
                    html += `<button type="button" class="btn btn-primary shadow-sm" data-redirect="com_orden/create?req_id=${this.state.id}">
                                <i class="ri-shopping-cart-2-line align-middle me-1"></i> ${btnLabel}
                             </button>`;
                }
                break;
        }

        this.dom.$actionContainer.html(html);
    },

    handleAction: function (e) {
        const accion = $(e.currentTarget).data('accion');
        const title = accion === 'approve' ? 'Aprobar Solicitud' : 'Rechazar Solicitud';
        const color = accion === 'approve' ? '#28a745' : '#dc3545';

        Swal.fire({
            title: title,
            text: "Ingrese un comentario obligatorio para esta acción:",
            input: 'textarea',
            icon: 'warning',
            
            showCancelButton: true,
            confirmButtonColor: color,
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            preConfirm: (comentario) => {
                if (!comentario || comentario.trim() === '') {
                    Swal.showValidationMessage('El comentario es obligatorio.');
                }
                return comentario;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                this.submitAction(accion, result.value);
            }
        });
    },

    submitAction: function (accion, comentario) {
        Sys_Core.Net.post({
            url: `${this.config.apiBase}/${this.state.id}/${accion}`,
            payload: { comentario: comentario },
            successMsg: `Solicitud procesada correctamente.`,
            onDone: () => {
                this.loadData(); // Recarga asíncrona de la UI
            }
        });
    }
};

$(document).ready(function () {
    RequisitionRead.init();
});