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

                // Llenar header del modal
                $('#sourcing-item-name').text(item.descripcion_sourcing || 'Sourcing');
                $('#sourcing-target-price').text(Sys_Core.Format.toCurrency(targetPrice));
                
                // Renderizar tabla
                const $tbody = $('#tblComparativa tbody').empty();
                
                if (cotizaciones.length === 0) {
                    $tbody.html('<tr><td colspan="4" class="text-center py-4 text-muted fs-12">No hay cotizaciones registradas para este artículo.</td></tr>');
                    return;
                }

                cotizaciones.forEach(cot => {
                    const precioFinal = parseFloat(cot.precio_unitario) * parseFloat(cot.tipo_cambio || 1);
                    const diff = targetPrice - precioFinal;
                    const diffClass = diff >= 0 ? 'text-success' : 'text-danger';
                    const diffIcon = diff >= 0 ? 'ri-arrow-down-circle-line' : 'ri-arrow-up-circle-line';
                    const isWinner = parseInt(cot.es_ganadora) === 1;
                    const actionButtons = isWinner ? 
                        `<button class="btn btn-sm btn-success btn-promote-now" data-id="${idPartida}">
                            <i class="ri-checkbox-circle-line"></i> Promover a SKU
                        </button>` : 
                        `<button class="btn btn-sm btn-outline-success btn-select-winner" data-id="${cot.idcotizacion}">Ganadora</button>`;
                    const tc = parseFloat(cot.tipo_cambio) || 1.0;
                    const precioUnitario = parseFloat(cot.precio_unitario) || 0;
                    const precioFinalMXN = precioUnitario * tc; // Normalización a MXN
                    const html = `
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">${cot.razon_social}</div>
                                <div class="fs-10 text-muted">Ref: ${cot.moneda} ${Sys_Core.Format.toCurrency(precioUnitario)}</div>
                                <div class="fs-10">
                                    ${cot.estatus_onboarding === 'Aprobado' ? 
                                        '<span class="text-success"><i class="ri-checkbox-circle-line"></i> Docs OK</span>' : 
                                        '<span class="text-warning"><i class="ri-error-warning-line"></i> Docs Pendientes</span>'}
                                    <span class="ms-2 text-muted">| ${cot.contacto_email || 'Sin asignar'}</span>
                                </div>
                            </td>
                            <td class="text-center text-muted font-monospace fs-11">
                                x ${tc.toFixed(4)}
                            </td>
                            <td class="text-end fw-bold">
                                ${Sys_Core.Format.toCurrency(precioFinalMXN)}
                            </td>
                            <td class="text-center fw-medium ${diffClass}">
                                ${diff >= 0 ? '<i class="ri-arrow-down-s-fill"></i>' : '<i class="ri-arrow-up-s-fill"></i>'}
                                ${Sys_Core.Format.toCurrency(Math.abs(diff))}
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="${Sys_Core.Config.baseUrl}/${cot.url_pdf_cotizacion}" target="_blank" class="btn btn-sm btn-light border"><i class="ri-file-pdf-line"></i></a>
                                    ${actionButtons}
                                </div>
                            </td>
                        </tr>`;
                    $tbody.append(html);
                });
            }
        });
    },

    submitQuotation: function(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        formData.append('idrequisicionarticulo', this.state.currentPartidaId);

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/quotations`,
            payload: formData,
            onDone: (res) => {
                Sys_Core.UI.notify(res.message, 'success');
                this.loadComparisonData(this.state.currentPartidaId);
                e.target.reset();
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

        this.dom.$lblEstatus.replaceWith(this.getStatusBadge(d.estatus));
        
        const prioColors = { 'baja': 'bg-info', 'media': 'bg-warning', 'alta': 'bg-danger', 'critica': 'bg-dark' };
        const prioColor = prioColors[d.prioridad?.toLowerCase()] || 'bg-secondary';
        this.dom.$lblPrioridad.removeClass().addClass(`badge ${prioColor} fs-12 px-3 py-1 shadow-sm`).text((d.prioridad || 'Normal').toUpperCase());

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

    getStatusBadge: function (status) {
        const strStatus = status ? status.toLowerCase() : '';
        const clases = {
            'borrador': 'badge text-bg-light',
            'pendiente': 'badge text-bg-warning',
            'aprobada': 'badge text-bg-success',
            'rechazada': 'badge text-bg-danger',
            'en compra': 'badge text-bg-info',
            'finalizada': 'badge text-bg-secondary',
            'cancelada': 'badge text-bg-danger',
            'eliminada': 'badge text-bg-danger'
        };
        const badgeClass = clases[strStatus] || 'bg-secondary';
        return `<span id="lbl-estatus" class="badge ${badgeClass} px-3 py-2 text-capitalize fs-13 shadow-sm ms-3">${status}</span>`;
    },

    renderContextualActions: function (status) {
        const canUpdate = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u');
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r'); 
        const strStatus = status ? status.toLowerCase() : '';

        // Botón Volver
        let html = `<button type="button" class="btn btn-light" data-redirect="com_requisicion"><i class="ri-arrow-left-line"></i> Volver</button>`;

        // Botón PDF
        html += `<button type="button" class="btn btn-outline-danger" id="btn-export-pdf"><i class="ri-file-pdf-line"></i> PDF</button>`;

        switch (strStatus) {
            case 'borrador':
                if (canUpdate) {
                    html += `<button type="button" class="btn btn-primary" data-redirect="com_requisicion/create/${this.state.id}"><i class="ri-pencil-line"></i> Editar Borrador</button>`;
                }
                break;
            case 'pendiente':
                if (canApprove) {
                    html += `<button type="button" class="btn btn-danger action-btn" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button>`;
                    html += `<button type="button" class="btn btn-success action-btn" data-accion="approve"><i class="ri-check-line"></i> Aprobar Solicitud</button>`;
                }
                break;
            case 'aprobada':
            case 'en compra':
                // ¡LÓGICA DE COMPRAS!: Si está aprobada o en proceso, permitir generar/continuar la OC
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