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
        selectedItemsToMove: [],
        tempSpecs: null, // Memoria temporal para la ficha técnica del modal
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
            $lblFechaCreacion: $('#lbl-fecha-creacion'),
            $btnItemEspecial: $('#btn-item-especial'),
            $modalEspecial: $('#modalArticuloEspecial'),
            $btnConfirmarEspecial: $('#btn-confirmar-especial'),
            $chkDirecta: $('#chk-compra-directa'),
            $sectionDirecta: $('.section-compra-directa'),
            $selectMetodoPago: $('select[name="idmetodopago"]'),
            $inputUrl: $('input[name="url_referencia"]'),
        };
    },

    // 3. EVENT LISTENERS
    bindEvents: function () {
        // Buscador de Artículos (Debounce)
        this.dom.$skuInput.on('input keyup change', (e) => this.handleSkuSearch(e));
        
        // Autocompletar al seleccionar producto
        this.dom.$productSelect.on('change', (e) => this.handleProductSelection(e));

        this.dom.$chkDirecta = $('#chk-compra-directa');
        this.dom.$sectionDirecta = $('.section-compra-directa');

        this.dom.$chkDirecta.on('change', (e) => {
            if (e.target.checked) {
                this.dom.$sectionDirecta.removeClass('d-none').hide().fadeIn(300);
                this.loadPaymentMethods(); // Cargar catálogo de métodos de pago
                
                // Notificación Premium
                Sys_Core.UI.notify('Modo Compra Directa activado. Se omitirá el proceso de Sourcing.', 'info');
            } else {
                this.dom.$sectionDirecta.fadeOut(300, () => {
                    $(this).addClass('d-none');
                    // Limpiar valores para no enviar basura si se arrepiente
                    $('[name="idmetodopago"]').val('');
                    $('[name="url_referencia"]').val('');
                });
            }
        });
        
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

        this.dom.$chkDirecta.on('change', (e) => {
            this.toggleSpotBuySection(e.target.checked);
        });

        // Submit del Formulario (Interceptamos los dos botones: Draft y Submit)
        this.dom.$btnSubmit.on('click', (e) => {
            e.preventDefault();
            const $clickedBtn = $(e.currentTarget); // <--- Capturamos EL BOTÓN exacto
            const action = $(e.currentTarget).data('estatus') === 'borrador' ? 'save_draft' : 'submit_approval';
            this.saveRequisition(action, $clickedBtn);
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

        this.dom.$btnItemEspecial.on('click', () => this.dom.$modalEspecial.modal('show'));
        this.dom.$btnConfirmarEspecial.on('click', () => this.addSpecialItemToTable());
    },

    /**
     * Controla la visibilidad y carga de datos de la sección Spot Buy
     */
    toggleSpotBuySection: function(show, callback = null) {
        if (show) {
            this.dom.$sectionDirecta.removeClass('d-none').hide().fadeIn(300);
            this.loadPaymentMethods().then(() => {
                if (callback) callback();
            });
        } else {
            this.dom.$sectionDirecta.fadeOut(300, () => {
                this.dom.$sectionDirecta.addClass('d-none');
                this.dom.$selectMetodoPago.val('');
                this.dom.$inputUrl.val('');
            });
        }
    },

    // Método para cargar el catálogo
    loadPaymentMethods: function() {
        return new Promise((resolve) => {
            if (this.dom.$selectMetodoPago.children('option').length > 1) return resolve();

            Sys_Core.Net.get({
                url: `${Sys_Core.Config.baseUrl}/api/v1/catalogs/payment-methods`,
                onSuccess: (res) => {
                    Sys_Core.UI.fillSelect(this.dom.$selectMetodoPago, res.data, {
                        valueField: 'idmetodopago',
                        textField: 'nombre',
                        placeholder: 'Seleccione método de pago...'
                    });
                    resolve();
                }
            });
        });
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

        Sys_Core.Net.get({
            url: `${this.config.apiBase}/${this.state.id}`,
            onSuccess: (res) => {
                // Validación de lógica de negocio (status 200 pero resultado negativo)
                if (res.status === 'success' || res.status === true) {
                    this.populateUI(res.data);
                } else {
                    // Si el backend responde status: false (ej: recurso no encontrado o inactivo)
                    Sys_Core.UI.alert('Error', 'No se pudo cargar la requisición.', 'error');
                    Sys_Core.Navigation.to('requisiciones');
                }
            },
            onComplete: () => {
                // Quitamos el loader sin importar si fue Success o Error (401, 403, 500, etc.)
                Sys_Core.UI.toggleLoader('.page-content', false);
            }
        });
    },

    populateUI: function (data) {
        // --- PASO 1: MAPEO Y TRANSFORMACIÓN (Mantenemos tu lógica original) ---
        const formData = {
            titulo: data.titulo,
            departamentoid: data.departamentoid,
            fecha_requerida: data.fecha_requerida,
            justificacion: data.justificacion,
            idmetodopago: data.idmetodopago,
            url_referencia: data.url_referencia,
            prioridad: this.mapPriorityToValue(data.prioridad) // Tu transformación manual
        };

        // --- PASO 2: GESTIÓN DEL FLUJO SPOT BUY ---
        if (data.tipo_requisicion === 'spot_buy') {
            this.dom.$chkDirecta.prop('checked', true);
            // toggleSpotBuySection ahora recibe los datos transformados para llenar el form
            this.toggleSpotBuySection(true, () => {
                Sys_Core.UI.fillForm('#formRequisicion', formData);
            });
        } else {
            this.dom.$chkDirecta.prop('checked', false);
            this.toggleSpotBuySection(false);
            Sys_Core.UI.fillForm('#formRequisicion', formData);
        }

        // --- PASO 3: POBLAR TABLA (Mantenemos fallbacks de 'N/A') ---
        this.dom.$tableBody.empty();
        if (data.items && data.items.length > 0) {
            data.items.forEach(item => {
                // Si es sourcing, reconstruimos el objeto de specs para el atributo data-specs
                if (parseInt(item.es_sourcing) === 1) {
                    item.specs = {
                        justificacion_proyecto: item.justificacion_proyecto,
                        categoria: item.categoria,
                        descripcion_sourcing: item.descripcion_sourcing,
                        especificaciones_tecnicas: item.especificaciones_tecnicas,
                        dimensiones_principales: item.dimensiones_principales,
                        normas_requeridas: item.normas_requeridas,
                        volumen_anual: item.volumen_anual,
                        precio_objetivo: item.precio_objetivo,
                        fecha_inicio_negociacion: item.fecha_inicio_negociacion,
                        fecha_limite_acuerdo: item.fecha_limite_acuerdo
                    };
                }
                this.renderRow(item);
            });
        } else {
            this.renderEmptyState();
        }

        // --- PASO 4: ETIQUETAS INFORMATIVAS ---
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
        $('.empty-state-row').remove();

        // --- 1. NORMALIZACIÓN DE DATOS (Lo nuevo) ---
        // Detectamos si es sourcing ya sea por el flag del backend (es_sourcing) 
        // o por el flag del frontend (isSourcing) cuando se agrega al vuelo.
        const isSourcing = (parseInt(data.es_sourcing) === 1 || data.isSourcing === true);
        
        // El query ahora manda 'unidad_salida' o 'PZA' por defecto
        const unitLabel = data.unidad_salida || data.unidad || 'PZA';
        
        // Clases visuales según el ADN LDR Premium
        const sourcingClass = isSourcing ? 'table-info border-start border-4 border-primary' : '';

        // --- 2. GESTIÓN DE ATRIBUTOS ---
        const itemIdAttr = data.idrequisicionarticulo ? `data-itemid="${data.idrequisicionarticulo}"` : '';
        
        // Si el artículo ya viene de la BD con specs, o se acaban de crear, las guardamos
        // Si data.specs no existe (porque viene del query plano), podemos omitirlo o reconstruirlo
        const specsData = data.specs ? JSON.stringify(data.specs) : '{}';
        const specsAttr = isSourcing ? `data-specs='${specsData}'` : '';

        let chkHtml = '';
        if (this.state.isEditMode) {
            if (data.idrequisicionarticulo) {
                chkHtml = `
                <td width="40" class="text-center align-middle px-3">
                    <div class="form-check d-flex justify-content-center m-0">
                        <input class="form-check-input chk-mover-partida cursor-pointer m-0" type="checkbox" value="${data.idrequisicionarticulo}" data-qty="${data.cantidad}" style="width: 1.2rem; height: 1.2rem;">
                    </div>
                </td>`;
            } else {
                chkHtml = `
                <td width="40" class="text-center align-middle px-3">
                    <i class="ri-checkbox-blank-circle-line text-muted opacity-25" title="Guarde primero para poder mover"></i>
                </td>`;
            }
        }

        const html = `
            <tr data-invid="${data.inventarioid || ''}" ${itemIdAttr} ${specsAttr} class="partida-row ${sourcingClass}">
                ${chkHtml}
                <td class="ps-4">
                    <div class="d-flex flex-column">
                        <span class="fw-bold ${isSourcing ? 'text-primary' : 'text-dark'}">
                            ${isSourcing ? '<span class="badge bg-primary me-1">SOURCING</span>' : ''} 
                            ${data.cve_articulo} — ${data.descripcion}
                        </span>
                        <small class="text-muted">
                            ${isSourcing ? '<i class="ri-error-warning-line text-warning"></i> Requiere búsqueda de proveedor' : 'Unidad: ' + unitLabel}
                        </small>
                    </div>
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm text-center input-cantidad-tabla fw-bold" value="${data.cantidad}" min="1">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        ${isSourcing ? '<span class="input-group-text bg-light">$</span>' : ''}
                        <input type="number" class="form-control form-control-sm text-end input-precio-tabla ${isSourcing ? 'bg-soft-primary fw-bold' : 'bg-light border-0'}" 
                               value="${parseFloat(data.precio || data.precio_unitario_estimado).toFixed(2)}" step="0.01">
                    </div>
                    ${isSourcing ? '<small class="text-primary d-block text-end">Precio Objetivo</small>' : ''}
                </td>
                <td class="text-end pe-4 fw-bold text-primary subtotal-display">
                    ${Sys_Core.Format.toCurrency(data.cantidad * (data.precio || data.precio_unitario_estimado))}
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm input-notas-tabla" value="${data.notas || ''}" placeholder="Observaciones">
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
                    // 1. EXISTE EN BD -> Petición vía Sys_Core (Maneja JWT y Loader automáticamente)
                    Sys_Core.Net.post({
                        url: `${this.config.apiBase}/${this.state.id}/items/${itemId}`,
                        method: 'DELETE', // Especificamos el verbo RESTful
                        onDone: (res) => {
                            // Esta lógica solo corre si el servidor responde status: true
                            $fila.remove();
                            this.calculateGrandTotal();
                            this.handleCheckboxSelection(); // Refrescar contador por si estaba checkeado
                            
                            // El mensaje de éxito lo puede mandar el backend en res.message, 
                            // o puedes usar el notificación del Core
                            Sys_Core.UI.notify('Partida eliminada correctamente.', 'info');
                        }
                        // No necesitas error: ni complete:, Sys_Core se encarga del rollback del loader y del alert.
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
    saveRequisition: function (action, $triggerBtn) {
        if ($('.partida-row').length === 0) {
            Sys_Core.UI.alert('Tabla Vacía', 'Debe agregar al menos un artículo antes de enviar.', 'warning');
            return;
        }
        
        // Construir el payload
        const payload = {
            action: action, // 'save_draft' o 'submit_approval'
            titulo: $('input[name="titulo"]').val(),
            // --- NUEVOS CAMPOS ---
            tipo_requisicion: $('#chk-compra-directa').is(':checked') ? 'spot_buy' : 'standard',
            idmetodopago: $('select[name="idmetodopago"]').val() || null,
            url_referencia: $('input[name="url_referencia"]').val() || null,
            // ---------------------
            fecha_requerida: $('input[name="fecha_requerida"]').val(),
            departamentoid: $('select[name="departamentoid"]').val(),
            centro_costo: $('input[name="centro_costo"]').val(),
            prioridad: $('select[name="prioridad"] option:selected').text().split(' ')[0].toLowerCase(), // Enviar 'alta', 'media', 'baja'
            justificacion: $('textarea[name="justificacion"]').val(),
            articulos: []
        };

        // Recorrer tabla para extraer detalle
        $('.partida-row').each(function() {
            const $row = $(this);
            const rowSpecs = $row.data('specs'); // Aquí recuperamos el JSON de la ficha técnica

            payload.articulos.push({
                // Si tiene data-itemid, es un artículo existente (Update), si no, es nuevo (Insert)
                idrequisicionarticulo: $row.data('itemid') || null, 
                inventarioid: $row.data('invid'),
                cantidad: $row.find('.input-cantidad-tabla').val(),
                precio_unitario_estimado: $row.find('.input-precio-tabla').val(),
                notas: $row.find('.input-notas-tabla').val(),
                // ENVIAMOS LAS SPECS SI EXISTEN
                specs: rowSpecs || null 
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
            // FIX: Usamos el botón que recibimos, no la clase genérica
            $btn: $triggerBtn, 
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
        const params = new URLSearchParams({ status: 'borrador' });
        const requestUrl = `${this.config.apiBase}?${params.toString()}`;

        Sys_Core.Net.get({
            url: requestUrl,
            onSuccess: (res) => {
                this.dom.$selectDrafts.empty();
                
                // Validación del response
                if (res.status && res.data && res.data.length > 0) {
                    // Evitamos que se pueda fusionar/mover al mismo borrador en el que estamos
                    const otherDrafts = res.data.filter(req => req.idrequisicion !== this.state.id);
                    
                    if (otherDrafts.length === 0) {
                        this.dom.$selectDrafts.append('<option value="">No tienes otros borradores.</option>');
                    } else {
                        this.dom.$selectDrafts.append('<option value="">Selecciona el borrador destino...</option>');
                        otherDrafts.forEach(req => {
                            this.dom.$selectDrafts.append(
                                $('<option>', {
                                    value: req.idrequisicion,
                                    text: `#${req.idrequisicion} - ${req.titulo}`
                                })
                            );
                        });
                    }
                } else {
                    this.dom.$selectDrafts.append('<option value="">No tienes borradores.</option>');
                }
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
        const token = localStorage.getItem('mrp_token');

        $.ajax({
            url: `${this.config.apiBase}/${this.state.id}/items/move`,
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(payload),
            beforeSend: function (request) {
                // this.dom.$btnConfirmarMover.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i> Procesando...');
                if (token) {
                    request.setRequestHeader("Authorization", `Bearer ${token}`);
                }
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
    },

    addSpecialItemToTable: function() {
        const form = document.getElementById('formArticuloEspecial');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const data = Object.fromEntries(new FormData(form).entries());
        
        // Agregamos a la tabla principal con un indicador visual
        this.renderRow({
            idrequisicionarticulo: null,
            inventarioid: null, // IDENTIFICADOR DE ARTÍCULO NUEVO
            sku: 'SOURCING',
            descripcion: data.descripcion_sourcing,
            unidad: 'PZA',
            cantidad: 1, // Por defecto 1 para sourcing, el usuario ajusta en tabla
            precio: data.precio_objetivo,
            notas: `ESPECIAL: ${data.categoria}`,
            isSourcing: true,
            specs: data // Guardamos la ficha técnica oculta en el row
        });

        this.dom.$modalEspecial.modal('hide');
        form.reset();
        this.calculateGrandTotal();
        Sys_Core.UI.notify('Artículo especial añadido correctamente.', 'info');
    },
};

// Arrancar Módulo
$(document).ready(function () {
    RequisitionForm.init();
});