/**
 * LDR Solutions - WMS Module
 * @module InventoryReceptionForm
 * @description Gestión de cotejo físico y entrada de almacén.
 */

const InventoryReceptionForm = {
    config: {
        apiOC: `${Sys_Core.Config.baseUrl}/api/v1/purchase-orders`,
        apiBase: `${Sys_Core.Config.baseUrl}/api/v1/inventory-receptions`
    },

    state: {
        ocId: null,
        items: []
    },

    dom: {},

    init: function () {
        this.extractParams();
        this.cacheDOM();
        this.bindEvents();
        this.loadOCPendingItems();
    },

    extractParams: function () {
        this.state.ocId = Sys_Core.URL.getParam('oc_id');
        if (!this.state.ocId) {
            Sys_Core.UI.alert('Error', 'No se especificó una Orden de Compra válida.', 'error')
                .then(() => Sys_Core.Navigation.to('com_orden'));
        }
    },

    cacheDOM: function () {
        this.dom = {
            $lblOcId: $('#lbl-oc-id'),
            $lblProveedor: $('#lbl-proveedor-nombre'),
            $lblComprador: $('#lbl-comprador-nombre'),
            $lblOcFecha: $('#lbl-oc-fecha'),
            $tblItems: $('#tbl-items-recepcion'),
            $btnSubmit: $('#btn-registrar-entrada'),
            $txtRemision: $('#txt-remision'),
            $txtObservaciones: $('#txt-observaciones')
        };
    },

    bindEvents: function () {
        // HU #70: Validación de excesos en tiempo real
        this.dom.$tblItems.on('input', '.input-conteo', (e) => this.validateQty(e));

        // Submit de la recepción
        this.dom.$btnSubmit.on('click', () => this.submitReception());
    },

    loadOCPendingItems: function () {
        Sys_Core.Net.get({
            url: `${this.config.apiOC}/${this.state.ocId}/pending-reception`,
            onSuccess: (res) => {
                if (res.status && res.data) {
                    this.state.items = res.data.items_pendientes;
                    this.renderHeader(res.data.orden);
                    this.renderTable(res.data.items_pendientes);
                }
            }
        });
    },

    renderHeader: function (oc) {
        this.dom.$lblOcId.text(`#${oc.idcompra}`);
        this.dom.$lblProveedor.text(oc.proveedor_nombre);
        this.dom.$lblComprador.text(oc.vendedor_asignado || 'Comprador LDR'); // Ajustar segun tu DDL de usuarios
        this.dom.$lblOcFecha.text(Sys_Core.Format.toDate(oc.created_at));
    },

    renderTable: function (items) {
        this.dom.$tblItems.empty();

        if (items.length === 0) {
            this.dom.$tblItems.html('<tr><td colspan="5" class="text-center py-4">No hay partidas pendientes por recibir.</td></tr>');
            this.dom.$btnSubmit.prop('disabled', true);
            return;
        }

        items.forEach(item => {
            const html = `
                <tr class="row-recepcion" data-idreqart="${item.idrequisicionarticulo}" data-invid="${item.inventarioid}">
                    <td class="ps-4">
                        <div class="fw-bold">${item.cve_articulo}</div>
                        <div class="small text-muted text-truncate" style="max-width: 300px;">${item.descripcion}</div>
                    </td>
                    <td class="text-center fw-medium text-muted">
                        ${parseFloat(item.cantidad_comprada).toFixed(2)}
                    </td>
                    <td class="text-center bg-soft-info text-info fw-bold">
                        ${parseFloat(item.saldo_pendiente).toFixed(2)}
                    </td>
                    <td>
                        <input type="number" 
                               class="form-control form-control-sm text-center input-conteo shadow-sm" 
                               value="${parseFloat(item.saldo_pendiente)}" 
                               min="0" 
                               max="${item.saldo_pendiente}" 
                               data-pending="${item.saldo_pendiente}"
                               step="0.01">
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light border">${item.unidad_salida}</span>
                    </td>
                </tr>
            `;
            this.dom.$tblItems.append(html);
        });
    },

    /**
     * HU #70: Lógica de validación de excesos en recepción
     */
    validateQty: function (e) {
        const $input = $(e.target);
        const val = parseFloat($input.val()) || 0;
        const pending = parseFloat($input.data('pending'));

        if (val > pending) {
            Sys_Core.UI.notify(`Cantidad excede el saldo pendiente (${pending})`, 'warning');
            $input.val(pending); // Reset al máximo permitido
            $input.addClass('is-invalid');
            setTimeout(() => $input.removeClass('is-invalid'), 1000);
        } else {
            $input.removeClass('is-invalid');
        }
    },

    submitReception: function () {
        // 1. Validaciones básicas
        const remision = this.dom.$txtRemision.val().trim();
        if (!remision) {
            Sys_Core.UI.alert('Campo Requerido', 'Debe ingresar el número de remisión del proveedor.', 'warning');
            this.dom.$txtRemision.focus();
            return;
        }

        const articulos = [];
        $('.row-recepcion').each(function () {
            const $row = $(this);
            const qty = parseFloat($row.find('.input-conteo').val()) || 0;

            if (qty > 0) {
                articulos.push({
                    idrequisicionarticulo: $row.data('idreqart'),
                    inventarioid: $row.data('invid'),
                    cantidad_recibida: qty
                });
            }
        });

        if (articulos.length === 0) {
            Sys_Core.UI.notify('No ha ingresado cantidades para recibir.', 'warning');
            return;
        }

        // 2. Ejecutar POST
        Sys_Core.Net.post({
            url: this.config.apiBase,
            payload: {
                idcompra: this.state.ocId,
                num_remision: remision,
                observaciones: this.dom.$txtObservaciones.val(),
                articulos: articulos
            },
            $btn: this.dom.$btnSubmit,
            onDone: (res) => {
                Sys_Core.UI.alert('Recepción Exitosa', res.message, 'success')
                    .then(() => Sys_Core.Navigation.to(`com_orden/read/${this.state.ocId}`));
            }
        });
    }
};

$(document).ready(() => InventoryReceptionForm.init());