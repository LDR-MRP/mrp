/**
 * MRP System - Requisition Detail (HU-06)
 * @module RequisitionDetail
 * @description Gestión de la vista de detalle, renderizado de partidas y seguimiento de estatus.
 */

$(document).ready(function () {

    /**
     * @description Punto de entrada privado para la inicialización del módulo.
     * @private
     */
    function _init() {
        const reqId = _getRequisitionId();
        if (reqId) {
            _fetchData(reqId);
        }
    }

    /**
     * @description Controla la transición visual entre el Skeleton Screen y el contenido real.
     * @param {boolean} show 
     * @private
     */
    function _toggleSkeleton(show) {
        const $skeleton = $('#view-skeleton');
        const $detail = $('#view-detail');

        if (show) {
            $skeleton.show();
            $detail.hide();
        } else {
            $skeleton.fadeOut(300, () => {
                $detail.fadeIn(200);
            });
        }
    }

    /**
     * @description Recupera los datos de la requisición desde el servidor.
     * @param {string} id 
     * @private
     */
    function _fetchData(id) {
        _toggleSkeleton(true);

        $.ajax({
            url: `${Sys_Core.Config.baseUrl}/com_requisicion/detail/${id}`,
            method: 'GET',
            dataType: 'json'
        }).done(function (res) {
            if (res.status === "success") {
                _renderUI(res.data);
            }
        }).fail(function (xhr) {
            Sys_Core.Net.handleError(xhr);
        }).always(function () {
            _toggleSkeleton(false);
        });
    }

    /**
     * @description Distribuye los datos en los componentes de la interfaz de usuario.
     * @param {Object} data 
     * @private
     */
    function _renderUI(data) {
        $('#req-id').text(`#${data.idrequisicion}`);
        $('#req-requester-name').text(data.solicitante);
        $('#req-date').text(Sys_Core.Format.toDate(data.fecha));
        $('#req-total-amount').text(Sys_Core.Format.toCurrency(data.monto_total));
        
        $('#req-cost-center').text(data.departamentoid === 1 ? 'Logística' : 'Planta LDR');

        const $statusBadge = $('#req-status-badge');
        $statusBadge.text(data.estatus.toUpperCase())
                    .removeClass()
                    .addClass(`badge ${_getStatusBadgeClass(data.estatus)} px-4 py-2`);

        _renderItemsTable(data.partidas);
        _renderAuditLogTable(data.bitacora || []);

        if (data.estatus.toLowerCase() !== 'borrador') {
            $('#btn-edit-draft').hide();
        }
    }

    /**
     * @description Renderiza dinámicamente la tabla de artículos autorizados.
     * @param {Array} items 
     * @private
     */
    function _renderItemsTable(items) {
        const $tbody = $('#table-items tbody');
        $tbody.empty();

        if (!items || items.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center p-4">No items authorized for this requisition.</td></tr>');
            return;
        }

        items.forEach((item, index) => {
            $tbody.append(`
                <tr>
                    <td class="pl-4">${index + 1}</td>
                    <td>
                        <div class="fw-bold text-dark">${item.cve_articulo} - ${item.descripcion}</div>
                        <small class="text-muted italic">${item.notas || 'No additional notes'}</small>
                    </td>
                    <td class="text-center">${item.cantidad} ${item.unidad_entrada}</td>
                    <td class="text-right">${Sys_Core.Format.toCurrency(item.precio_unitario_estimado)}</td>
                    <td class="text-right pr-4 text-primary font-weight-bold">
                        ${Sys_Core.Format.toCurrency(item.total)}
                    </td>
                </tr>
            `);
        });
    }

    /**
     * @description Renderiza el historial de auditoría (bitácora).
     * @param {Array} logs 
     * @private
     */
    function _renderAuditLogTable(logs) {
        const $tbody = $('#table-audit-log tbody');
        $tbody.empty();

        if (!logs || logs.length === 0) {
            $tbody.append('<tr><td class="p-4 text-center">No activity history available.</td></tr>');
            return;
        }

        logs.forEach(log => {
            $tbody.append(`
                <tr>
                    <td width="200" class="pl-4 py-3">${log.created_at}</td>
                    <td class="py-3 text-dark">
                        <strong>${log.user_name}</strong> ${log.action}.
                        ${log.comment ? `<br><small class="text-muted italic">"${log.comment}"</small>` : ''}
                    </td>
                </tr>
            `);
        });
    }

    /**
     * @description Mapea el estatus de la requisición a una clase CSS de badge.
     * @param {string} status 
     * @returns {string}
     * @private
     */
    function _getStatusBadgeClass(status) {
        const statusMap = {
            'borrador': 'badge-draft',
            'pendiente': 'badge-review',
            'aprobada': 'badge-approved',
            'rechazada': 'badge-rejected'
        };
        return statusMap[status.toLowerCase()] || 'bg-secondary';
    }

    /**
     * @description Extrae el ID de la requisición desde el path de la URL actual.
     * @returns {string|null}
     * @private
     */
    function _getRequisitionId() {
        const segments = location.pathname.split('/').filter(Boolean);
        const detailIndex = segments.indexOf('detalle');
        return detailIndex !== -1 && segments[detailIndex + 1] ? segments[detailIndex + 1] : null;
    }

    /**
     * @description Ejecuta la inicialización del módulo.
     */
    _init();

});