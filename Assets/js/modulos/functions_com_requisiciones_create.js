/**
 * MRP System - Requisition Management
 * @module Requisition
 * @description Manejo de captura, cálculo y envío de requisiciones de compra.
 * @requires Sys_Core
 */

$(document).ready(function () {

    /**
     * @description Inicializa los componentes de la interfaz.
     */
    function _init() {
        _loadDepartments();
    }

    /**
     * @description Pobla el select de departamentos desde el endpoint.
     * @private
     */
    function _loadDepartments() {
        const $select = $('select[name="departamentoid"]');

        $.ajax({
                "url": `${Sys_Core.Config.baseUrl}/cli_departamentos/indexapi`,
                "method": "GET",
                "timeout": 0,
        }).done(function (res) {
            $select.empty().append('<option value="" selected disabled>Seleccione Departamento...</option>');
                
            if (res.status && res.data) {
                res.data.forEach(dept => {
                    $select.append(`<option value="${dept.id}">${dept.nombre}</option>`);
                });
            }
        });
    }

    /**
     * @description Buscador de SKU con técnica debounce para optimizar peticiones.
     */
    $(document).on('input keyup change', '#sku', function () {
        clearTimeout($(this).data('t'));
        $(this).data('t', setTimeout($.proxy(function () {
            const val = $.trim($(this).val());
            
            if (val === '') {
                $('#sku-feedback').text('');
                $('#producto').empty().append('<option value="">— Selecciona —</option>');
                return;
            }

            $('#sku-feedback').text('Buscando…');

            $.ajax({
                url: `${Sys_Core.Config.baseUrl}/inv_inventario/index`,
                method: 'GET',
                data: { estado: 2, sku: val },
            }).done(function (resp) {
                if (!(resp && resp.status === 'success' && resp.data.length > 0)) {
                    $('#sku-feedback').text('Sin resultados.');
                    $('#producto').empty().append('<option value="">— Sin resultados —</option>');
                    return;
                }

                $('#producto').empty().append('<option value="">— Selecciona —</option>');
                $.each(resp.data, function () {
                    $('#producto').append($('<option/>')
                        .attr('value', this.idinventario)
                        .text(`${this.cve_articulo} — ${this.descripcion}`)
                        .attr('data-sku', this.cve_articulo)
                        .attr('data-descripcion', this.descripcion)
                        .attr('data-unidad', this.unidad_salida)
                        .attr('data-costo', this.ultimo_costo));
                });
                $('#sku-feedback').text(`Resultados: ${resp.data.length}`);
            });
        }, this), 300));
    });

    /**
     * @description Autocompletado de campos técnicos al seleccionar un artículo.
     */
    $('#producto').on('change', function () {
        const $opt = $(this).find('option:selected');
        $('#unidad_salida').val($opt.data('unidad') || '');
        const costo = parseFloat($opt.data('costo')) || 0;
        $('#ultimo_costo').val(costo.toFixed(2));
    });

    /**
     * @description Intercepta la tecla Enter en controles de captura para disparar la adición a tabla.
     */
    $(document).on('keydown', '#sku, #cantidad, #ultimo_costo', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $('#btn-agregar').click();
        }
    });

    /**
     * @description Gestión de inserción y agrupamiento de partidas en la tabla de requisición.
     */
    $('#btn-agregar').on('click', function (e) {
        e.preventDefault();
        const $opt = $('#producto option:selected');
        const invId = $opt.val();
        const sku = $opt.data('sku');
        const desc = $opt.data('descripcion');
        const unidad = $('#unidad_salida').val();
        const cant = parseFloat($('#cantidad').val()) || 0;
        const precio = parseFloat($('#ultimo_costo').val()) || 0;

        if (!invId || cant <= 0) {
            Sys_Core.UI.notify('Seleccione artículo y cantidad válida.', 'warning');
            return;
        }

        let $filaExistente = $(`#tblPartidas tbody tr[data-invid="${invId}"]`);

        if ($filaExistente.length > 0) {
            let $inputCant = $filaExistente.find('.input-cantidad-tabla');
            let totalCant = parseFloat($inputCant.val()) + cant;
            $inputCant.val(totalCant);
            _updateRowSubtotal($filaExistente);
            Sys_Core.UI.notify('Cantidad actualizada en la partida.', 'success');
        } else {
            const html = `
                <tr data-invid="${invId}" class="partida-row">
                    <td class="pl-4">
                        <div class="d-flex flex-column">
                            <span class="font-weight-bold text-dark">${sku} — ${desc}</span>
                            <small class="text-muted">Unidad: ${unidad}</small>
                            <input type="hidden" name="articulos_ids[]" value="${invId}">
                        </div>
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-right input-cantidad-tabla" value="${cant}" min="1">
                    </td>
                    <td>
                        <input type="number" class="form-control form-control-sm text-right input-precio-tabla" value="${precio.toFixed(2)}" step="0.01">
                    </td>
                    <td class="text-right pr-4 font-weight-bold text-primary subtotal-display">
                        ${Sys_Core.Format.toCurrency(cant * precio)}
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm input-notas-tabla" placeholder="Observaciones">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-eliminar">
                            <i class="ri-delete-bin-line fs-5"></i>
                        </button>
                    </td>
                </tr>`;
            $('#tblPartidas tbody').append(html);
        }

        _clearCaptureBar();
        _calculateGrandTotal();
    });

    /**
     * @description Escucha activa sobre los inputs de la tabla para recalcular totales en tiempo real.
     */
    $('#tblPartidas').on('input', '.input-cantidad-tabla, .input-precio-tabla', function () {
        _updateRowSubtotal($(this).closest('tr'));
        _calculateGrandTotal();
    });

    /**
     * @description Eliminación de partidas con confirmación centralizada.
     */
    $('#tblPartidas').on('click', '.btn-eliminar', function () {
        const $fila = $(this).closest('tr');
        Sys_Core.UI.confirm({
            title: '¿Quitar artículo?',
            text: 'La partida será eliminada de la lista actual.',
            confirmText: 'Sí, quitar'
        }).then((result) => {
            if (result.isConfirmed) {
                $fila.remove();
                _calculateGrandTotal();
                Sys_Core.UI.notify('Partida removida.', 'info');
            }
        });
    });

    /**
     * @description Procesa el envío del formulario estructurando el payload para la API.
     */
    $('.btn-guardar').on('click', function (e) {
        e.preventDefault();

        if ($('.partida-row').length === 0) {
            Sys_Core.UI.alert('Tabla Vacía', 'Debe agregar al menos un artículo antes de enviar.', 'warning');
            return;
        }

        const payload = {
            titulo: $('input[name="titulo"]').val(),
            fecha_requerida: $('input[name="fecha_requerida"]').val(),
            departamentoid: $('select[name="departamentoid"]').val(),
            monto_estimado: $('input[name="monto_estimado"]').val(),
            justificacion: $('textarea[name="justificacion"]').val(),
            prioridad: $('select[name="prioridad"]').val(),
            estatus: $(this).data('estatus'),
            articulos: []
        };

        $('.partida-row').each(function() {
            payload.articulos.push({
                inventarioid: $(this).data('invid'),
                cantidad: $(this).find('.input-cantidad-tabla').val(),
                precio_unitario_estimado: $(this).find('.input-precio-tabla').val(),
                notas: $(this).find('.input-notas-tabla').val()
            });
        });

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/com_requisicion/store`,
            payload: payload,
            successMsg: '¡Requisición enviada correctamente!',
            onDone: (res) => {
                setTimeout(() => {
                    window.location.href = `${Sys_Core.Config.baseUrl}/com_requisicion/read/${res.data.requisicion_id}`;
                }, 1500);
            }
        });
    });

    /**
     * @description Calcula el subtotal de una fila específica y actualiza su visualización.
     * @param {jQuery} $fila - Elemento TR de la tabla de partidas.
     */
    function _updateRowSubtotal($fila) {
        const c = parseFloat($fila.find('.input-cantidad-tabla').val()) || 0;
        const p = parseFloat($fila.find('.input-precio-tabla').val()) || 0;
        $fila.find('.subtotal-display').text(Sys_Core.Format.toCurrency(c * p));
    }

    /**
     * @description Recorre todas las partidas para actualizar el monto total del formulario.
     */
    function _calculateGrandTotal() {
        let granTotal = 0;
        $('.partida-row').each(function () {
            const c = parseFloat($(this).find('.input-cantidad-tabla').val()) || 0;
            const p = parseFloat($(this).find('.input-precio-tabla').val()) || 0;
            granTotal += (c * p);
        });
        
        $('#monto_estimado').val(granTotal.toFixed(2));
        $('#total_fmt').text(Sys_Core.Format.toCurrency(granTotal));
    }

    /**
     * @description Limpia y resetea los controles de la barra de búsqueda de artículos.
     */
    function _clearCaptureBar() {
        $('#sku').val('').focus();
        $('#producto').empty().append('<option value="">— Selecciona —</option>');
        $('#cantidad').val(1);
        $('#ultimo_costo').val('0.00');
        $('#unidad_salida').val('');
        $('#sku-feedback').text('');
    }

    _init();
});