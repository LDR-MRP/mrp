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
            // El backend nos mandó cantidad_pendiente y precio_unitario_estimado
            const maxQty = parseFloat(item.cantidad_pendiente);
            const hasWinner = item.id_proveedor_ganador !== null;
            const price = hasWinner ? parseFloat(item.precio_pactado) : parseFloat(item.precio_unitario_estimado);

            const html = `
                <tr class="partida-row" 
                    data-idreqart="${item.idrequisicionarticulo}" 
                    data-invid="${item.inventarioid}">
                    
                    <td class="ps-4">
                        <div class="fw-bold">ID Inv: ${item.inventarioid}</div>
                        <small class="text-muted">${item.notas || 'Sin notas'}</small>
                    </td>
                    
                    <td class="text-center bg-light fw-bold text-secondary">
                        ${maxQty.toFixed(2)}
                    </td>
                    
                    <td>
                        <!-- El max evita visualmente que pongan más del saldo pendiente -->
                        <input type="number" class="form-control form-control-sm text-end input-calc input-qty fw-bold text-primary" 
                               value="${maxQty}" min="0.01" max="${maxQty}" step="0.01">
                    </td>
                    
                    <td>
                        <input type="number" class="form-control form-control-sm text-end input-calc input-price" 
                               value="${price.toFixed(2)}" min="0" step="0.01">
                    </td>

                    <!-- COLUMNA NUEVA: PORCENTAJE -->
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="number" class="form-control text-end input-calc input-pct-discount text-danger" 
                                value="0" min="0" max="100" step="0.1">
                            <span class="input-group-text">%</span>
                        </div>
                    </td>
                    
                    <td>
                        <input type="number" class="form-control form-control-sm text-end input-calc input-discount text-danger" 
                               value="0.00" min="0" step="0.01">
                    </td>
                    
                    <td class="text-end pe-4 fw-bold row-subtotal">
                        ${Sys_Core.Format.toCurrency(maxQty * price)}
                    </td>
                    
                    <td class="text-center">
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-quitar" title="No comprar ahora">
                            <i class="ri-close-circle-line fs-5"></i>
                        </button>
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

        this.dom.$tblBody.find('.partida-row').each((i, el) => {
            const $row = $(el);
            const qty = parseFloat($row.find('.input-qty').val()) || 0;
            const price = parseFloat($row.find('.input-price').val()) || 0;
            const discount = parseFloat($row.find('.input-discount').val()) || 0;
            
            let rowSub = (qty * price) - discount;
            if (rowSub > 0) grandSubtotal += rowSub;
        });

        const iva = grandSubtotal * this.config.taxRate;
        const total = grandSubtotal + iva;

        this.dom.$lblSubtotal.text(Sys_Core.Format.toCurrency(grandSubtotal));
        this.dom.$lblIva.text(Sys_Core.Format.toCurrency(iva));
        this.dom.$lblTotal.text(Sys_Core.Format.toCurrency(total));
    },

    submitPurchaseOrder: function () {
        if (this.dom.$tblBody.find('.partida-row').length === 0) {
            Sys_Core.UI.alert('Orden Vacía', 'Debe incluir al menos un artículo para generar la OC.', 'warning');
            return;
        }

        // Construir Payload idéntico al que probaste en Postman
        const payload = {
            requisicionid: this.state.reqId,
            proveedorid: this.dom.$selectProveedor.val(),
            almacenid: this.dom.$selectAlmacen.val(),
            moneda: $('select[name="moneda"]').val(),
            tipo_cambio: $('input[name="tipo_cambio"]').val(),
            observaciones: $('textarea[name="observaciones"]').val(),
            articulos: []
        };

        this.dom.$tblBody.find('.partida-row').each((i, el) => {
            const $row = $(el);
            payload.articulos.push({
                idrequisicionarticulo: $row.data('idreqart'),
                inventarioid: $row.data('invid'),
                cantidad: $row.find('.input-qty').val(),
                costo_unitario: $row.find('.input-price').val(),
                porcentaje_descuento: parseFloat($row.find('.input-pct-discount').val()),
                descuento_partida: $row.find('.input-discount').val()
            });
        });

        // Usamos el Sys_Core.Net.post mejorado
        Sys_Core.Net.post({
            url: this.config.apiPOs,
            method: 'POST',
            payload: payload,
            $btn: this.dom.$btnSubmit,
            onDone: (res) => {
                setTimeout(() => {
                    // Redirigir a la vista de la OC generada (o de vuelta a requisiciones)
                    Sys_Core.Navigation.to(`com_orden/read/${res.data.orden_compra_id}`);
                }, 1500);
            }
        });
    }
};

$(document).ready(function () {
    PurchaseOrderForm.init();
});