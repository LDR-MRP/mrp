/**
 * Controlador de Órdenes de Compra para Proveedores (SRM)
 * Utiliza e integra el motor central Sys_Core
 */

const SrmPurchaseOrders = {

    ui: {
        tblBody: '#tbl-body-orders',
        modal: '#modalVerOC',
        modalPartidas: '#tbl-body-partidas-oc',
        btnDescargarPdf: '#btn-pdf-descargar'
    },

    init: function() {
        this.validateSession();
        this.loadPurchaseOrders();
    },

    validateSession: function() {
        const token = localStorage.getItem('mrp_token');
        if (!token) window.location.href = base_url + '/srm_login';
    },

    /**
     * Carga el listado de OCs desde la API
     */
    loadPurchaseOrders: function() {
        Sys_Core.UI.toggleLoader('.page-content', true);

        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/purchase-orders`,
            silent: false,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    this.renderOrdersTable(response.data);
                }
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    /**
     * Renderiza las filas de la tabla principal
     */
    renderOrdersTable: function(orders) {
        const $tbl = $(this.ui.tblBody);
        $tbl.empty(); // Limpiar loader inicial

        if (!orders || orders.length === 0) {
            $tbl.html('<tr><td colspan="6" class="text-center text-muted py-4">No tienes órdenes de compra registradas.</td></tr>');
            return;
        }

        orders.forEach(oc => {
            let badge = '';
            const status = oc.estatus.toUpperCase();

            // Mapeo de badges nativos de Velzon
            switch(status) {
                case 'AUTORIZADA':
                    badge = `<span class="badge bg-primary-subtle text-primary px-2 py-1 fs-12 fw-medium">Autorizada</span>`;
                    break;
                case 'RECIBIDA_PARCIAL':
                    badge = `<span class="badge bg-warning-subtle text-warning px-2 py-1 fs-12 fw-medium">Recibida Parcial</span>`;
                    break;
                case 'CERRADA':
                    badge = `<span class="badge bg-success-subtle text-success px-2 py-1 fs-12 fw-medium">Cerrada</span>`;
                    break;
                default:
                    badge = `<span class="badge bg-light text-muted px-2 py-1 fs-12 fw-medium">${oc.estatus}</span>`;
            }

            const row = `
                <tr>
                    <td class="ps-4 fw-semibold text-primary">#${oc.idcompra}</td>
                    <td>${Sys_Core.Format.toDate(oc.created_at)}</td>
                    <td>${oc.cve_almacen}</td>
                    <td class="text-end fw-bold text-dark">${Sys_Core.Format.toCurrency(oc.total)}</td>
                    <td>${badge}</td>
                    <td class="pe-4 text-end">
                        <button type="button" onclick="SrmPurchaseOrders.viewDetails(${oc.idcompra})" class="btn btn-soft-secondary btn-sm fw-medium me-1">
                            <i class="ri-eye-line align-middle"></i> Ver Detalle
                        </button>
                    </td>
                </tr>`;
            $tbl.append(row);
        });
    },

    /**
     * Consulta el detalle específico de una OC y despliega el modal
     */
    viewDetails: function(idOc) {
        Sys_Core.UI.toggleLoader('.page-content', true);

        Sys_Core.Net.get({
            url: `${base_url}/api/v1/srm/purchase-orders/${idOc}`,
            silent: false,
            onSuccess: (response) => {
                if (response.status === 'success' || response.status === true) {
                    const oc = response.data;

                    // Llenar metadatos en el modal
                    $('#lbl-modal-oc').text(oc.idcompra);
                    $('#lbl-modal-planta').text(oc.almacen_nombre);
                    $('#lbl-modal-total').text(Sys_Core.Format.toCurrency(oc.total));

                    // Llenar partidas
                    const $tblPartidas = $(this.ui.modalPartidas);
                    $tblPartidas.empty();

                    oc.items.forEach(item => {
                        const row = `
                            <tr>
                                <td class="fw-medium">${item.descripcion}</td>
                                <td class="text-center">${item.cantidad}</td>
                                <td class="text-end text-muted">${Sys_Core.Format.toCurrency(item.costo_unitario)}</td>
                                <td class="text-end fw-bold text-dark">${Sys_Core.Format.toCurrency(item.cantidad * item.costo_unitario)}</td>
                            </tr>`;
                        $tblPartidas.append(row);
                    });

                    // Configurar acción del botón de descarga PDF
                    $(this.ui.btnDescargarPdf).off('click').on('click', () => {
                        this.downloadPdf(oc.idcompra, `OC_${oc.idcompra}.pdf`);
                    });

                    // Mostrar modal nativo de Bootstrap 5
                    const myModal = new bootstrap.Modal(document.getElementById('modalVerOC'));
                    myModal.show();
                }
            },
            onComplete: () => {
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    /**
     * Utiliza el motor nativo de descarga segura de PDF de tu Sys_Core
     */
    downloadPdf: function(idOc, filename) {
        Sys_Core.Net.downloadPdf({
            url: `${base_url}/api/v1/srm/purchase-orders/${idOc}/pdf`,
            filename: filename
        });
    }
};

$(document).ready(function() {
    SrmPurchaseOrders.init();
});