/**
 * MRP System - Requisition Management
 * @module Requisition
 * @description Manejo de captura, cálculo y envío de requisiciones de compra.
 * @requires Sys_Core
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
     * @description Recupera los datos de la requisición desde el servidor.
     * @param {string} id 
     * @private
     */
    function _fetchData(id) {
        //_toggleSkeleton(true);

        $.ajax({
            url: `${Sys_Core.Config.baseUrl}/com_requisicion/detail/${id}`,
            method: 'GET',
            dataType: 'json'
        }).done(function (res) {
            if (res.status === "success") {
                _renderItemsTable(res.data);
                _loadSuppliers();
                _loadCurrencies();
                _loadWarehouses();
            }
        }).fail(function (xhr) {
            Sys_Core.Net.handleError(xhr);
        }).always(function () {
            //_toggleSkeleton(false);
        });
    }    

    /**
     * @description Renderiza dinámicamente la tabla de artículos autorizados.
     * @param {Array} data 
     * @private
     */
    function _renderItemsTable(data) {
        const $tbody = $('#table-items tbody');
        $tbody.empty();

        if (!data.partidas || data.partidas.length === 0) {
            $tbody.append('<tr><td colspan="5" class="text-center p-4">No items authorized for this requisition.</td></tr>');
            return;
        }

        data.partidas.forEach((item, index) => {
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

        $('#req-total-amount').text(Sys_Core.Format.toCurrency(data.monto_estimado));
    }

    /**
     * @description Pobla el select de proveedores desde el endpoint.
     * @private
     */
    function _loadSuppliers() {
        const $select = $('select[name="proveedor"]');

        $.ajax({
                "url": `${Sys_Core.Config.baseUrl}/com_compra/suppliers`,
                "method": "GET",
                "timeout": 0,
        }).done(function (res) {
            $select.empty().append('<option value="" selected disabled>Seleccione Proveedor...</option>');
                
            if (res.status && res.data) {
                res.data.forEach(supplier => {
                    $select.append(`<option value="${supplier.idproveedor}">${supplier.nombre_comercial}</option>`);
                });
            }
        });
    }

    /**
     * @description Pobla el select de monedas desde el endpoint.
     * @private
     */
    function _loadCurrencies() {
        const $select = $('select[name="moneda"]');

        $.ajax({
                "url": `${Sys_Core.Config.baseUrl}/inv_moneda/index`,
                "method": "GET",
                "timeout": 0,
        }).done(function (res) {
            $select.empty().append('<option value="" selected disabled>Seleccione Moneda...</option>');
                
            if (res.status && res.data) {
                res.data.forEach(currency => {
                    $select.append(`<option value="${currency.idmoneda}">${currency.simbolo}</option>`);
                });
            }
        });
    }

    /**
     * @description Pobla el select de almacenes desde el endpoint.
     * @private
     */
    function _loadWarehouses() {
        const $select = $('select[name="almacen"]');

        $.ajax({
                "url": `${Sys_Core.Config.baseUrl}/inv_almacenes/showAll`,
                "method": "GET",
                "timeout": 0,
        }).done(function (res) {
            $select.empty().append('<option value="" selected disabled>Seleccione Almacen...</option>');
                
            if (res.status && res.data) {
                res.data.forEach(warehouse => {
                    $select.append(`<option value="${warehouse.idalmacen}">${warehouse.descripcion}</option>`);
                });
            }
        });
    }

    /**
     * @description Procesa el envío del formulario estructurando el payload para la API.
     */
    $('#formCompra').on('submit', function (e) {
        e.preventDefault();

        const payload = {
            requisicionid: _getRequisitionId(),
            proveedorid: $('select[name="proveedor"]').val(),
            monedaid: $('select[name="moneda"]').val(),
            terminoid: $('select[name="termino"]').val(),
            iva: $('select[name="iva"]').val(),
            almacenid: $('select[name="almacen"]').val()
        };

        Sys_Core.Net.ajaxRequest({
            url: `${Sys_Core.Config.baseUrl}/com_compra/store`,
            payload: payload,
            successMsg: '¡Requisición enviada correctamente!',
            onDone: (res) => {
                setTimeout(() => {
                    window.location.href = `${Sys_Core.Config.baseUrl}/com_requisicion/detalle/${res.data.requisicionid}`;
                }, 1500);
            }
        });
    });

    /**
     * @description Extrae el ID de la requisición desde el path de la URL actual.
     * @returns {string|null}
     * @private
     */
    function _getRequisitionId() {
        const segments = location.pathname.split('/').filter(Boolean);
        const detailIndex = segments.indexOf('generar');
        return detailIndex !== -1 && segments[detailIndex + 1] ? segments[detailIndex + 1] : null;
    }

    _init();
});