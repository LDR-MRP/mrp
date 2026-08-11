/**
 * MRP System - Purchase Order Creation
 * @module PurchaseOrderForm
 * @description Generación de OC a partir de saldos pendientes de una Requisición.
 */

const PurchaseOrderForm = {
    config: {
        apiReqs: `${Sys_Core.Config.baseUrl}/api/v1/requisitions`,
        apiPOs: `${Sys_Core.Config.baseUrl}/api/v1/purchase-orders`,
        taxRate: 0.16 // 16% IVA por defecto
    },

    state: {
        reqId: null,
        pendingItems: []
    },

    dom: {},

    init: function () {
        Sys_Core.Auth.validateSession();
        this.cacheDOM();
        this.extractReqId();
        this.bindEvents();
        
        // Cargar catálogos (Simulados aquí, ajusta a tus endpoints reales)
        this.loadCatalogs();
    },

    extractReqId: function () {
        // Obtenemos el req_id de la URL: ?req_id=8
        const reqId = Sys_Core.URL.getParam('req_id');
        if (!reqId || isNaN(reqId)) {
            Sys_Core.UI.alert('Error', 'No se especificó una requisición de origen válida.', 'error')
                .then(() => Sys_Core.Navigation.to('com_requisicion'));
            return;
        }
        this.state.reqId = parseInt(reqId);
        this.dom.$lblReqId.text(`#${this.state.reqId}`);
        
        // Disparamos la carga de saldos
        this.loadPendingItems();
    },

    cacheDOM: function () {
        this.dom = {
            $form: $('#formOrdenCompra'),
            $lblReqId: $('#lbl-req-id'),
            $lblReqTitle: $('#lbl-req-title'),
            $tblBody: $('#tblPartidasOC tbody'),
            $selectProveedor: $('select[name="proveedorid"]'),
            $selectAlmacen: $('select[name="almacenid"]'),
            $lblSubtotal: $('#lbl-subtotal'),
            $lblIva: $('#lbl-iva'),
            $lblTotal: $('#lbl-total'),
            $btnSubmit: $('#btn-generar-oc'),
            $selectMoneda: $('select[name="moneda"]'),
            $inputTipoCambio: $('input[name="tipo_cambio"]'),
        };
    },

    bindEvents: function () {
        // Recálculo en tiempo real al modificar Cantidad, Precio o Descuento
        this.dom.$tblBody.on('input', '.input-calc', (e) => {
            this.updateRowSubtotal($(e.target).closest('tr'));
        });

        // Eliminar fila (Si no quiere comprar este artículo a este proveedor)
        this.dom.$tblBody.on('click', '.btn-quitar', (e) => {
            $(e.currentTarget).closest('tr').remove();
            this.calculateGrandTotals();
        });

        // Submit
        this.dom.$btnSubmit.on('click', (e) => {
            e.preventDefault();
            this.submitPurchaseOrder();
        });

        this.dom.$selectMoneda.on('change', (e) => {
            const moneda = $(e.target).val();
            if (moneda === 'MXN') {
                this.dom.$inputTipoCambio.val('1.000000').prop('readonly', true).addClass('bg-light');
            } else {
                this.dom.$inputTipoCambio.prop('readonly', false).removeClass('bg-light').focus();
            }
        });

        this.dom.$tblBody.on('input', '.input-calc', (e) => {
            const $row = $(e.target).closest('.partida-row');
            this.updateRowSubtotal($row, $(e.target)); // Pasamos el target para saber qué cambió
        });

        this.dom.$tblBody.on('change', '.check-item', () => {
            this.calculateGrandTotals();
            this.checkSplittingNeeds(); // Función para advertir sobre múltiples órdenes
        });
    },

    loadCatalogs: function () {
        // 1. Cargar Proveedores
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/suppliers`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect(this.dom.$selectProveedor, res.data, { textField: 'razon_social' });
            }
        });

        // 2. Cargar Almacenes
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/warehouses`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect(this.dom.$selectAlmacen, res.data, { valueField: 'idalmacen', textField: 'cve_almacen' });
            }
        });

        // 3. Cargar Monedas (Nueva integración)
        Sys_Core.Net.get({
            url: `${Sys_Core.Config.baseUrl}/api/v1/currencies`,
            onSuccess: (res) => {
                Sys_Core.UI.fillSelect(this.dom.$selectMoneda, res.data, { 
                    valueField: 'cve_moneda', // Ej: 'MXN', 'USD'
                    textField: 'cve_moneda',
                    selectedValue: 'MXN' // Pre-seleccionar Moneda Nacional
                });
                // Disparar el cambio manualmente para bloquear el input de TC si es MXN
                this.dom.$selectMoneda.trigger('change');
            }
        });
    },

    loadPendingItems: function () {
        Sys_Core.Net.get({
            url: `${this.config.apiReqs}/${this.state.reqId}/pending-items`,
            onSuccess: (res) => {
                if (res.status && res.data) {
                    this.dom.$lblReqTitle.text(res.data.requisicion.titulo || 'Sin título');
                    
                    if (res.data.items_pendientes.length === 0) {
                        Sys_Core.UI.alert('Completada', 'Esta requisición ya fue comprada en su totalidad. No hay saldos pendientes.', 'info')
                            .then(() => Sys_Core.Navigation.to('com_requisicion'));
                        return;
                    }

                    this.state.pendingItems = res.data.items_pendientes;
                    this.renderItemsTable();
                }
            }
        });
    },

    renderItemsTable: function () {
        this.dom.$tblBody.empty();

        this.state.pendingItems.forEach(item => {
            const maxQty = parseFloat(item.cantidad_pendiente);
            
            // 1. DETERMINACIÓN DE ESTADOS (Bifurcación Comercial)
            const isReady = item.operation_status === 'READY';
            const isSourcing = item.id_provider_final !== null;
            const isPriceLocked = item.is_price_locked == 1;
            
            // El precio base viene normalizado del backend (Sin IVA)
            const price = parseFloat(item.costo_base_pactado || item.precio_unitario_estimado);

            // 2. SEMÁFORO DE OPERACIÓN (Configuración Visual)
            let lockBadge = '';
            let rowClass = '';
            let checkDisabled = isReady ? '' : 'disabled';

            switch (item.operation_status) {
                case 'IN_SOURCING':
                    lockBadge = `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle fs-10"><i class="ri-search-eye-line me-1"></i>EN NEGOCIACIÓN</span>`;
                    rowClass = 'opacity-75 bg-light-subtle';
                    break;
                case 'PENDING_PROMOTION':
                    lockBadge = `<span class="badge bg-warning-subtle text-warning border border-warning-subtle fs-10"><i class="ri-rocket-2-line me-1"></i>REQUERIR CATALOGACIÓN</span>`;
                    rowClass = 'opacity-75';
                    break;
                case 'BLOCKED_ONBOARDING':
                    lockBadge = `<span class="badge bg-info-subtle text-info border border-info-subtle fs-10"><i class="ri-shield-user-line me-1"></i>PROVEEDOR EN ONBOARDING</span>`;
                    rowClass = 'opacity-75';
                    break;
                case 'READY':
                    lockBadge = `<span class="badge bg-success-subtle text-success fs-10"><i class="ri-check-line me-1"></i>LISTO</span>`;
                    rowClass = ''; // Sin opacidad para los que están listos
                    break;
            }

            // 3. IDENTIDAD DEL PROVEEDOR (Evitar el 'null' visual)
            const vendorName = item.proveedor_nombre || 'POR DEFINIR';
            const vendorBadge = `<span class="badge bg-light text-muted border fs-10" title="Estatus Comercial">
                                    <i class="ri-user-follow-line me-1 ${isSourcing ? 'text-primary' : ''}"></i> ${vendorName}
                                </span>`;           

            // 4. CONSTRUCCIÓN DEL HTML
            const html = `
                <tr class="partida-row ${rowClass}" 
                    data-idreqart="${item.idrequisicionarticulo}" 
                    data-invid="${item.inventarioid}"
                    data-id-proveedor="${item.id_proveedor_final || ''}"
                    data-tipo-elemento="${item.tipo_elemento || 'P'}">
                    
                    <td class="ps-4" style="width: 40px;">
                        <div class="form-check">
                            <input class="form-check-input check-item" type="checkbox" 
                                ${checkDisabled} 
                                ${isReady ? 'checked' : ''}>
                        </div>
                    </td>
                    
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="fs-13 mb-1 text-body fw-bold text-uppercase">${item.descripcion}</h6>
                                <div class="d-flex gap-2 align-items-center">
                                    <small class="text-muted">SKU: <b class="text-dark">${item.cve_articulo}</b></small>
                                    ${lockBadge}
                                    ${vendorBadge}
                                </div>
                            </div>
                        </div>
                    </td>
                    
                    <td class="text-center bg-light-subtle fw-bold text-muted fs-12">
                        ${maxQty.toFixed(2)}
                    </td>
                    
                    <td>
                        <input type="number" class="form-control form-control-sm text-end input-calc input-qty fw-bold text-primary border-light shadow-sm" 
                            value="${maxQty}" min="0.01" max="${maxQty}" step="0.01" ${!isReady ? 'disabled' : ''}>
                    </td>
                    
                    <td>
                        <input type="number" class="form-control form-control-sm text-end input-calc input-price ${isPriceLocked ? 'bg-light fw-bold text-dark' : ''}" 
                            value="${price.toFixed(2)}" min="0" step="0.01" 
                            ${isPriceLocked || !isReady ? 'readonly' : ''}>
                    </td>

                    <td>
                        <div class="input-group input-group-sm shadow-sm">
                            <input type="number" class="form-control text-end input-calc input-pct-discount text-danger" 
                                value="0" min="0" max="100" step="0.1" ${!isReady ? 'disabled' : ''}>
                            <span class="input-group-text bg-light border-0 fs-10">%</span>
                        </div>
                    </td>
                    
                    <td>
                        <input type="number" class="form-control form-control-sm text-end input-calc input-discount text-danger border-light" 
                            value="0.00" min="0" step="0.01" ${!isReady ? 'disabled' : ''}>
                    </td>
                    
                    <td class="text-end pe-4 fw-bold row-subtotal fs-14 text-body">
                        ${Sys_Core.Format.toCurrency(maxQty * price)}
                    </td>
                </tr>
            `;
            this.dom.$tblBody.append(html);
        });

        this.calculateGrandTotals();
    },

    updateRowSubtotal: function ($row, triggerInput) {
        let qty = parseFloat($row.find('.input-qty').val()) || 0;
        const maxQty = parseFloat($row.find('.input-qty').attr('max'));
        const price = parseFloat($row.find('.input-price').val()) || 0;
        const pct = parseFloat($row.find('.input-pct-discount').val()) || 0;
        let discount = parseFloat($row.find('.input-discount').val()) || 0;

        // 1. Validación de Cantidad (Anti-Fraude)
        if (qty > maxQty) {
            qty = maxQty;
            $row.find('.input-qty').val(maxQty);
            Sys_Core.UI.notify('No puede comprar más del saldo pendiente.', 'warning');
        }

        const bruto = qty * price;

        // 2. Lógica de Descuento: Si cambiaron el %, recalculamos el monto en $
        if (triggerInput && triggerInput.hasClass('input-pct-discount')) {
            discount = bruto * (pct / 100);
            $row.find('.input-discount').val(discount.toFixed(2));
        } 
        // Opcional: Si cambiaron el monto $, podríamos recalcular el % para feedback visual
        else if (triggerInput && triggerInput.hasClass('input-discount')) {
            const newPct = bruto > 0 ? (discount / bruto) * 100 : 0;
            $row.find('.input-pct-discount').val(newPct.toFixed(1));
        }

        // 3. Aplicar subtotal
        const subtotal = bruto - discount;
        $row.find('.row-subtotal').text(Sys_Core.Format.toCurrency(subtotal > 0 ? subtotal : 0));
        
        this.calculateGrandTotals();
    },

    calculateGrandTotals: function () {
        let grandSubtotal = 0;

        // Solo recorremos las filas que tienen el checkbox marcado
        this.dom.$tblBody.find('.partida-row').each((i, el) => {
            const $row = $(el);
            const $checkbox = $row.find('.check-item');
            
            if ($checkbox.is(':checked')) {
                const qty = parseFloat($row.find('.input-qty').val()) || 0;
                const price = parseFloat($row.find('.input-price').val()) || 0;
                const discount = parseFloat($row.find('.input-discount').val()) || 0;
                
                let rowSub = (qty * price) - discount;
                if (rowSub > 0) grandSubtotal += rowSub;
                
                // Efecto visual: Resaltar fila seleccionada
                $row.addClass('table-active');
            } else {
                $row.removeClass('table-active');
            }
        });

        const iva = grandSubtotal * this.config.taxRate;
        const total = grandSubtotal + iva;

        this.dom.$lblSubtotal.text(Sys_Core.Format.toCurrency(grandSubtotal));
        this.dom.$lblIva.text(Sys_Core.Format.toCurrency(iva));
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(total));
        
        // Validar si el botón de generar debe estar habilitado
        this.dom.$btnSubmit.prop('disabled', this.dom.$tblBody.find('.check-item:checked').length === 0);
    },

    checkSplittingNeeds: function () {
        const selectedProviders = [];
        const globalProvider = this.dom.$selectProveedor.val();

        this.dom.$tblBody.find('.partida-row').each((i, el) => {
            const $row = $(el);
            if ($row.find('.check-item').is(':checked')) {
                // Si la fila tiene proveedor fijo (Sourcing), usamos ese. 
                // Si no, usamos el global del select.
                const pId = $row.data('id-proveedor') || globalProvider;
                if (pId && !selectedProviders.includes(pId)) {
                    selectedProviders.push(pId);
                }
            }
        });

        if (selectedProviders.length > 1) {
            // Mostrar alerta de Splitting (Puedes crear un div específico en el HTML)
            $('#splitting-alert').removeClass('d-none').html(`
                <div class="alert alert-info border-0 shadow-sm mb-3 animate__animated animate__headShake">
                    <i class="ri-information-line me-1"></i> <b>Aviso de Splitting:</b> 
                    Se generarán ${selectedProviders.length} Órdenes de Compra independientes debido a la mezcla de proveedores.
                </div>
            `);
        } else {
            $('#splitting-alert').addClass('d-none');
        }
    },

    submitPurchaseOrder: function () {
        const $selectedRows = this.dom.$tblBody.find('.partida-row').filter(function() {
            return $(this).find('.check-item').is(':checked');
        });

        if ($selectedRows.length === 0) {
            Sys_Core.UI.alert('Selección Vacía', 'Debe marcar al menos un artículo para generar la(s) orden(es) de compra.', 'warning');
            return;
        }

        const globalProvider = this.dom.$selectProveedor.val();
        
        // Construir Payload para Splitting
        const payload = {
            requisicionid: this.state.reqId,
            almacenid: this.dom.$selectAlmacen.val(),
            moneda: this.dom.$selectMoneda.val(),
            tipo_cambio: this.dom.$inputTipoCambio.val(),
            observaciones: $('textarea[name="observaciones"]').val(),
            articulos: []
        };

        let validationError = false;

        $selectedRows.each((i, el) => {
            const $row = $(el);
            // Prioridad: 1. Proveedor de Sourcing (data-id-proveedor) | 2. Proveedor Global (Select)
            const itemProvider = $row.data('id-proveedor') || globalProvider;

            if (!itemProvider) {
                validationError = true;
                $row.addClass('table-danger');
            }

            payload.articulos.push({
                idrequisicionarticulo: $row.data('idreqart'),
                inventarioid: $row.data('invid'),
                proveedorid: itemProvider, // Inyectamos el proveedor por partida
                cantidad: $row.find('.input-qty').val(),
                costo_unitario: $row.find('.input-price').val(),
                porcentaje_descuento: parseFloat($row.find('.input-pct-discount').val()) || 0,
                descuento_partida: $row.find('.input-discount').val() || 0,
                tipo_elemento: $row.data('tipo-elemento')
            });
        });

        if (validationError) {
            Sys_Core.UI.notify('Hay partidas sin proveedor asignado. Por favor seleccione un proveedor global.', 'error');
            return;
        }

        Sys_Core.Net.post({
            url: this.config.apiPOs,
            method: 'POST',
            payload: payload,
            $btn: this.dom.$btnSubmit,
            onDone: (res) => {
                // El backend ahora devuelve un array de IDs generados por el splitting
                const ids = res.data.ordenes_generadas; // Ej: [101, 102]
                
                Sys_Core.UI.notify(`${ids.length} Orden(es) de compra generada(s) correctamente.`, 'success');

                setTimeout(() => {
                    // Si es una sola, vamos al detalle. Si son varias, volvemos al listado.
                    if (ids.length === 1) {
                        Sys_Core.Navigation.to(`com_orden/read/${ids[0]}`);
                    } else {
                        Sys_Core.Navigation.to(`com_orden`);
                    }
                }, 2000);
            }
        });
    }
};

$(document).ready(function () {
    PurchaseOrderForm.init();
});