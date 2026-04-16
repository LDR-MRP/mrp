/**
 * MRP System - Requisition Management
 * @module RequisitionForm
 * @description Lógica de UI para Creación y Edición de Requisiciones usando Object Literal Pattern.
 * @requires Sys_Core
 */

const RequisitionForm = {

    // 1. CONFIGURACIÓN Y ESTADO Módulo
    config: {
        apiBase: `${Sys_Core.Config.baseUrl}/api/v1/requisitions`,
        catalogsApi: `${Sys_Core.Config.baseUrl}/api/v1/catalogs`, // Ajustar a tus rutas reales
        debounceTimer: null
    },

    state: {
        id: null,          // Si tiene ID, estamos en Update Mode
        isEditMode: false,
        items: [],          // Memoria RAM del frontend para las partidas
        selectedItemsToMove: []
    },

    dom: {}, // Caché de selectores del DOM

    // 2. INICIALIZACIÓN
    init: function () {
        this.cacheDOM();
        this.checkMode();
        this.bindEvents();
        
        // Cargamos catálogos (Departamentos, CC, etc.) y LUEGO cargamos los datos si es Edición
        this.loadDepartments().then(() => {
            if (this.state.isEditMode) {
                this.loadRequisitionData();
            } else {
                this.renderEmptyState();
            }
        });
    },

    checkMode: function () {
        // Obtenemos el ID de la URL. Ej: /requisiciones/create/8 -> id = 8
        const pathSegments = window.location.pathname.split('/');
        const possibleId = pathSegments[pathSegments.length - 1];
        
        if (!isNaN(possibleId) && possibleId > 0) {
            this.state.id = parseInt(possibleId);
            this.state.isEditMode = true;
            // Cambiar título de la UI dinámicamente
            $('.page-title-box li.active').text('Editar Solicitud');
            $('h4.text-dark').text(`Editar Solicitud #${this.state.id}`);

            this.dom.$actionContainer.show();

            $('#tblPartidas thead tr').prepend('<th width="40"></th>');
        }
    },

    cacheDOM: function () {
        this.dom = {
            $form: $('#formRequisicion'),
            $deptSelect: $('select[name="departamentoid"]'),
            $skuInput: $('#sku'),
            $skuFeedback: $('#sku-feedback'),
            $productSelect: $('#producto'),
            $qtyInput: $('#cantidad'),
            $unitInput: $('#unidad_salida'),
            $priceInput: $('#ultimo_costo'),
            $btnAddItem: $('#btn-agregar'),
            $tableBody: $('#tblPartidas tbody'),
            $grandTotalInput: $('#monto_estimado'),
            $btnSubmit: $('.btn-guardar'),
            $actionContainer: $('.container-acciones-edicion'),
            $btnModalMover: $('#btn-modal-mover'),
            $countMover: $('#count-mover'),
            $modalMover: $('#modalMoverPartidas'),
            $radioDestino: $('input[name="optDestinoMover"]'),
            $collapseExistente: $('#collapseDraftsExistentes'),
            $selectDrafts: $('#selectDraftDestino'),
            $btnConfirmarMover: $('#btn-confirmar-mover'),
            $solicitanteContainer: $('#user-avatar-container'),
            $lblSolicitante: $('#lbl-solicitante'),
            $lblSolicitanteRol: $('#lbl-solicitante-rol'),
            $lblFechaCreacion: $('#lbl-fecha-creacion')
        };
    },

    // 3. EVENT LISTENERS
    bindEvents: function () {
        // Buscador de Artículos (Debounce)
        this.dom.$skuInput.on('input keyup change', (e) => this.handleSkuSearch(e));
        
        // Autocompletar al seleccionar producto
        this.dom.$productSelect.on('change', (e) => this.handleProductSelection(e));
        
        // Enter key para agregar
        this.dom.$form.on('keydown', '#sku, #cantidad, #ultimo_costo', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.dom.$btnAddItem.click();
            }
        });

        // Botón Agregar Partida
        this.dom.$btnAddItem.on('click', (e) => {
            e.preventDefault();
            this.addItemToTable();
        });

        // Recálculo en tiempo real al editar inputs de la tabla
        this.dom.$tableBody.on('input', '.input-cantidad-tabla, .input-precio-tabla', (e) => {
            this.updateRowSubtotal($(e.target).closest('tr'));
        });

        // Eliminar Partida
        this.dom.$tableBody.on('click', '.btn-eliminar', (e) => {
            this.removeRow($(e.currentTarget).closest('tr'));
        });

        // Submit del Formulario (Interceptamos los dos botones: Draft y Submit)
        this.dom.$btnSubmit.on('click', (e) => {
            e.preventDefault();
            const action = $(e.currentTarget).data('estatus') === 'borrador' ? 'save_draft' : 'submit_approval';
            this.saveRequisition(action);
        });

        this.dom.$tableBody.on('change', '.chk-mover-partida', () => this.handleCheckboxSelection());
        this.dom.$btnModalMover.on('click', () => this.openMoveModal());
        this.dom.$radioDestino.on('change', (e) => {
            if (e.target.value === 'existente') {
                this.dom.$collapseExistente.collapse('show');
                this.loadUserDrafts();
            } else {
                this.dom.$collapseExistente.collapse('hide');
            }
        });
        this.dom.$btnConfirmarMover.on('click', () => this.submitMoveItems());
    },

    // 4. CARGA DE DATOS E INTERACCIÓN API
    loadDepartments: function () {
        return new Promise((resolve) => {
            // Nota: Se asume que Sys_Core.Net.get está configurado
            $.ajax({
                url: `${Sys_Core.Config.baseUrl}/cli_departamentos/indexapi`,
                method: "GET"
            }).done((res) => {
                this.dom.$deptSelect.empty().append('<option value="" selected disabled>Seleccione Departamento...</option>');
                if (res.status && res.data) {
                    res.data.forEach(dept => {
                        this.dom.$deptSelect.append(`<option value="${dept.id}">${dept.nombre}</option>`);
                    });
                }
                resolve();
            });
        });
    },

    loadRequisitionData: function () {
        Sys_Core.UI.toggleLoader('.page-content', true);

        // Llamamos al endpoint GET /api/v1/requisitions/{id}
        $.ajax({
            url: `${this.config.apiBase}/${this.state.id}`,
            method: 'GET'
        }).done((res) => {
            if (res.status === 'success' || res.status === true) {
                this.populateUI(res.data);
            } else {
                Sys_Core.UI.alert('Error', 'No se pudo cargar la requisición.', 'error');
                Sys_Core.Navigation.to('requisiciones');
            }
        }).always(() => {
            Sys_Core.UI.toggleLoader('.page-content', false);
        });
    },

    populateUI: function (data) {
        // 1. Llenar campos de cabecera usando la utilidad del core
        Sys_Core.UI.fillForm('#formRequisicion', {
            titulo: data.titulo,
            departamentoid: data.departamentoid,
            fecha_requerida: data.fecha_requerida,
            justificacion: data.justificacion,
            prioridad: this.mapPriorityToValue(data.prioridad) // Convertir string a ID del select
        });

        // 2. Limpiar tabla y dibujar partidas existentes
        this.dom.$tableBody.empty();
        if (data.items && data.items.length > 0) {
            data.items.forEach(item => {
                this.renderRow({
                    idrequisicionarticulo: item.idrequisicionarticulo, // Clave para el Update
                    inventarioid: item.inventarioid,
                    sku: item.cve_articulo || 'N/A', 
                    descripcion: item.descripcion || 'Artículo recuperado',
                    unidad: item.unidad_salida || 'PZA',
                    cantidad: item.cantidad,
                    precio: item.precio_unitario_estimado,
                    notas: item.notas || ''
                });
            });
        } else {
            this.renderEmptyState();
        }

        this.dom.$lblSolicitante.text(data.solicitante || 'Usuario del Sistema');
        this.dom.$lblSolicitanteRol.text(data.rol_solicitante || 'Rol no especificado');
        this.dom.$lblFechaCreacion.text(Sys_Core.Format.toDate(data.fecha));

        this.calculateGrandTotal();
    },

    // 5. LÓGICA DE NEGOCIO Y DOM
    handleSkuSearch: function (e) {
        clearTimeout(this.config.debounceTimer);
        const val = $.trim($(e.target).val());
        
        if (val === '') {
            this.clearCaptureBar();
            return;
        }

        this.dom.$skuFeedback.text('Buscando…');

        this.config.debounceTimer = setTimeout(() => {
            $.ajax({
                url: `${Sys_Core.Config.baseUrl}/inv_inventario/index`,
                method: 'GET',
                data: { estado: 2, sku: val },
            }).done((resp) => {
                if (!(resp && resp.status === 'success' && resp.data.length > 0)) {
                    this.dom.$skuFeedback.text('Sin resultados.');
                    this.dom.$productSelect.empty().append('<option value="">— Sin resultados —</option>');
                    return;
                }

                this.dom.$productSelect.empty().append('<option value="">— Selecciona —</option>');
                resp.data.forEach(item => {
                    this.dom.$productSelect.append($('<option/>')
                        .attr('value', item.idinventario)
                        .text(`${item.cve_articulo} — ${item.descripcion}`)
                        .data('item', item) // Guardamos todo el objeto en memoria jQuery
                    );
                });
                this.dom.$skuFeedback.text(`Resultados: ${resp.data.length}`);
            });
        }, 300);
    },

    handleProductSelection: function (e) {
        const $opt = $(e.target).find('option:selected');
        const itemData = $opt.data('item');
        
        if (itemData) {
            this.dom.$unitInput.val(itemData.unidad_salida || 'PZA');
            const costo = parseFloat(itemData.ultimo_costo) || 0;
            this.dom.$priceInput.val(costo.toFixed(2));
            this.dom.$qtyInput.focus();
        }
    },

    addItemToTable: function () {
        const $opt = this.dom.$productSelect.find('option:selected');
        const itemData = $opt.data('item');
        
        const cant = parseFloat(this.dom.$qtyInput.val()) || 0;
        const precio = parseFloat(this.dom.$priceInput.val()) || 0;

        if (!itemData || cant <= 0) {
            Sys_Core.UI.notify('Seleccione un artículo y cantidad válida.', 'warning');
            return;
        }

        // Buscar si ya existe en la tabla para sumar
        let $filaExistente = this.dom.$tableBody.find(`tr[data-invid="${itemData.idinventario}"]`);

        if ($filaExistente.length > 0) {
            let $inputCant = $filaExistente.find('.input-cantidad-tabla');
            $inputCant.val(parseFloat($inputCant.val()) + cant);
            this.updateRowSubtotal($filaExistente);
            Sys_Core.UI.notify('Cantidad incrementada.', 'success');
        } else {
            this.renderRow({
                idrequisicionarticulo: null, // Es nuevo, no tiene ID de DB aún
                inventarioid: itemData.idinventario,
                sku: itemData.cve_articulo,
                descripcion: itemData.descripcion,
                unidad: itemData.unidad_salida || 'PZA',
                cantidad: cant,
                precio: precio,
                notas: ''
            });
        }

        this.clearCaptureBar();
        this.calculateGrandTotal();
    },

    renderRow: function (data) {
        $('.empty-state-row').remove(); // Quitar mensaje de tabla vacía

        // Atributo data-itemid guarda el ID real de BD si estamos en modo edición
        const itemIdAttr = data.idrequisicionarticulo ? `data-itemid="${data.idrequisicionarticulo}"` : '';

        let chkHtml = '';
        if (this.state.isEditMode) {
            if (data.idrequisicionarticulo) {
                // El artículo YA EXISTE en BD: Mostramos el checkbox funcional
                chkHtml = `
                <td width="40" class="text-center align-middle px-3">
                    <div class="form-check d-flex justify-content-center m-0">
                        <input class="form-check-input chk-mover-partida cursor-pointer m-0" type="checkbox" value="${data.idrequisicionarticulo}" data-qty="${data.cantidad}" style="width: 1.2rem; height: 1.2rem;">
                    </div>
                </td>`;
            } else {
                // El artículo es NUEVO: Mostramos una celda vacía o un ícono para mantener la alineación
                chkHtml = `
                <td width="40" class="text-center align-middle px-3">
                    <i class="ri-checkbox-blank-circle-line text-muted opacity-25" title="Guarde primero para poder mover"></i>
                </td>`;
            }
        }

        const html = `
            <tr data-invid="${data.inventarioid}" ${itemIdAttr} class="partida-row">
                ${chkHtml}
                <td class="ps-4">
                    <div class="d-flex flex-column">
                        <span class="fw-bold text-dark">${data.sku} — ${data.descripcion}</span>
                        <small class="text-muted">Unidad: ${data.unidad}</small>
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end input-cantidad-tabla fw-bold" value="${data.cantidad}" min="1">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-end input-precio-tabla bg-light border-0" value="${parseFloat(data.precio).toFixed(2)}" step="0.01">
                </td>
                <td class="text-end pe-4 fw-bold text-primary subtotal-display">
                    ${Sys_Core.Format.toCurrency(data.cantidad * data.precio)}
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-notas-tabla" value="${data.notas}" placeholder="Observaciones">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link btn-sm text-danger p-0 btn-eliminar" title="Quitar">
                        <i class="ri-delete-bin-line fs-5"></i>
                    </button>
                </td>
            </tr>`;
        this.dom.$tableBody.append(html);
    },

    renderEmptyState: function () {
        this.dom.$tableBody.html(`
            <tr class="empty-state-row">
                <td colspan="6" class="text-center py-5">
                    <div class="text-muted">
                        <i class="ri-shopping-basket-2-line fs-1 opacity-25"></i>
                        <p class="mt-2 fs-13">La lista está vacía. Escanee o busque productos arriba.</p>
                    </div>
                </td>
            </tr>
        `);
    },

    removeRow: function ($fila) {
        Sys_Core.UI.confirm({
            title: '¿Quitar artículo?',
            text: 'La partida será eliminada de la lista.',
            confirmText: 'Sí, quitar'
        }).then((result) => {
            if (result.isConfirmed) {
                
                // Leemos el atributo para saber si existe en BD
                const itemId = $fila.data('itemid');

                if (itemId) {
                    // 1. EXISTE EN BD -> Hacemos petición DELETE
                    $.ajax({
                        url: `${this.config.apiBase}/${this.state.id}/items/${itemId}`,
                        method: 'DELETE',
                        beforeSend: () => Sys_Core.UI.toggleLoader('.page-content', true),
                        success: (res) => {
                            $fila.remove();
                            this.calculateGrandTotal();
                            this.handleCheckboxSelection(); // Refrescar contador por si estaba checkeado
                            Sys_Core.UI.notify('Partida eliminada de la base de datos.', 'info');
                        },
                        error: (xhr) => Sys_Core.Net.handleError(xhr),
                        complete: () => Sys_Core.UI.toggleLoader('.page-content', false)
                    });
                } else {
                    // 2. ES NUEVO (Solo DOM) -> Solo quitamos el HTML
                    $fila.remove();
                    this.calculateGrandTotal();
                    Sys_Core.UI.notify('Partida removida.', 'info');
                }
            }
        });
    },

    updateRowSubtotal: function ($fila) {
        const c = parseFloat($fila.find('.input-cantidad-tabla').val()) || 0;
        const p = parseFloat($fila.find('.input-precio-tabla').val()) || 0;
        $fila.find('.subtotal-display').text(Sys_Core.Format.toCurrency(c * p));
        this.calculateGrandTotal();
    },

    calculateGrandTotal: function () {
        let granTotal = 0;
        $('.partida-row').each(function () {
            const c = parseFloat($(this).find('.input-cantidad-tabla').val()) || 0;
            const p = parseFloat($(this).find('.input-precio-tabla').val()) || 0;
            granTotal += (c * p);
        });
        
        // Actualiza el widget visual de la UI
        this.dom.$grandTotalInput.val(Sys_Core.Format.toCurrency(granTotal.toFixed(2)));
    },

    clearCaptureBar: function () {
        this.dom.$skuInput.val('').focus();
        this.dom.$productSelect.empty().append('<option value="">— Buscar en el catálogo —</option>');
        this.dom.$qtyInput.val(1);
        this.dom.$priceInput.val('0.00');
        this.dom.$unitInput.val('');
        this.dom.$skuFeedback.text('');
    },

    // 6. SUBMIT & API COMMUNICATION
    saveRequisition: function (action) {
        if ($('.partida-row').length === 0) {
            Sys_Core.UI.alert('Tabla Vacía', 'Debe agregar al menos un artículo antes de enviar.', 'warning');
            return;
        }

        // Construir el payload
        const payload = {
            action: action, // 'save_draft' o 'submit_approval'
            titulo: $('input[name="titulo"]').val(),
            fecha_requerida: $('input[name="fecha_requerida"]').val(),
            departamentoid: $('select[name="departamentoid"]').val(),
            id_centro_costo: $('select[name="id_centro_costo"]').val(),
            prioridad: $('select[name="prioridad"] option:selected').text().split(' ')[0].toLowerCase(), // Enviar 'alta', 'media', 'baja'
            justificacion: $('textarea[name="justificacion"]').val(),
            articulos: []
        };

        // Recorrer tabla para extraer detalle
        $('.partida-row').each(function() {
            const $row = $(this);
            payload.articulos.push({
                // Si tiene data-itemid, es un artículo existente (Update), si no, es nuevo (Insert)
                idrequisicionarticulo: $row.data('itemid') || null, 
                inventarioid: $row.data('invid'),
                cantidad: $row.find('.input-cantidad-tabla').val(),
                precio_unitario_estimado: $row.find('.input-precio-tabla').val(),
                notas: $row.find('.input-notas-tabla').val()
            });
        });

        // 1. RESTful: Determinar Verbo HTTP y URL basados en el modo del estado
        const isUpdate = this.state.isEditMode;
        const httpMethod = isUpdate ? 'PUT' : 'POST';
        const targetUrl = isUpdate ? `${this.config.apiBase}/${this.state.id}` : this.config.apiBase;

        // 2. Ejecutar petición usando el Sys_Core mejorado
        Sys_Core.Net.post({
            url: targetUrl,
            method: httpMethod, // Pasamos explícitamente POST o PUT
            payload: payload,
            $btn: $(document.activeElement), // Pasamos el botón que el usuario acaba de presionar
            onDone: (res) => {
                setTimeout(() => {
                    // Redirigir al modo vista (Show) tras guardar
                    const redirId = isUpdate ? this.state.id : res.data.requisicion_id;
                    window.location.href = `${Sys_Core.Config.baseUrl}/com_requisicion/read/${redirId}`;
                }, 1500);
            }
        });
    },

    handleCheckboxSelection: function() {
        this.state.selectedItemsToMove = [];
        const $checked = this.dom.$tableBody.find('.chk-mover-partida:checked');
        
        $checked.each((i, el) => {
            this.state.selectedItemsToMove.push({
                requisition_item_id: parseInt($(el).val()),
                qty_to_move: parseFloat($(el).data('qty'))
            });
        });

        const count = $checked.length;
        this.dom.$countMover.text(count);
        this.dom.$btnModalMover.prop('disabled', count === 0);
        
        if (count > 0) {
            this.dom.$btnModalMover.removeClass('btn-outline-primary').addClass('btn-primary shadow-sm');
        } else {
            this.dom.$btnModalMover.removeClass('btn-primary shadow-sm').addClass('btn-outline-primary');
        }
    },

    openMoveModal: function() {
        $('#lbl-modal-count').text(this.state.selectedItemsToMove.length);
        $('#optDestinoNuevo').prop('checked', true).trigger('change');
        this.dom.$modalMover.modal('show');
    },

    loadUserDrafts: function() {
        this.dom.$selectDrafts.empty().append('<option value="">Cargando...</option>');
        $.ajax({
            url: `${this.config.apiBase}`, 
            method: 'GET',
            data: { status: 'borrador' } 
        }).done((res) => {
            this.dom.$selectDrafts.empty();
            if (res.status && res.data && res.data.length > 0) {
                const otherDrafts = res.data.filter(req => req.idrequisicion !== this.state.id);
                if (otherDrafts.length === 0) {
                    this.dom.$selectDrafts.append('<option value="">No tienes otros borradores.</option>');
                } else {
                    this.dom.$selectDrafts.append('<option value="">Selecciona el borrador destino...</option>');
                    otherDrafts.forEach(req => {
                        this.dom.$selectDrafts.append(`<option value="${req.idrequisicion}">#${req.idrequisicion} - ${req.titulo}</option>`);
                    });
                }
            } else {
                this.dom.$selectDrafts.append('<option value="">No tienes borradores.</option>');
            }
        });
    },

    submitMoveItems: function() {
        const createNew = $('#optDestinoNuevo').is(':checked');
        const targetId = this.dom.$selectDrafts.val();

        if (!createNew && (!targetId || targetId === '')) {
            Sys_Core.UI.notify('Seleccione un borrador válido.', 'warning');
            return;
        }

        const payload = {
            create_new: createNew,
            target_requisition_id: createNew ? null : parseInt(targetId),
            items: this.state.selectedItemsToMove
        };

        const originalHtml = this.dom.$btnConfirmarMover.html();

        $.ajax({
            url: `${this.config.apiBase}/${this.state.id}/items/move`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: () => {
                this.dom.$btnConfirmarMover.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Procesando...');
            },
            success: (res) => {
                this.dom.$modalMover.modal('hide');
                Sys_Core.UI.notify(res.message, 'success');
                setTimeout(() => window.location.reload(), 1000);
            },
            error: (xhr) => Sys_Core.Net.handleError(xhr),
            complete: () => this.dom.$btnConfirmarMover.prop('disabled', false).html(originalHtml)
        });
    },

    // Utilities
    mapPriorityToValue: function(priorityString) {
        const map = { 'baja': '3', 'media': '1', 'alta': '2', 'critica': '2' };
        return map[priorityString?.toLowerCase()] || '1';
    }
};

// Arrancar Módulo
$(document).ready(function () {
    RequisitionForm.init();
});