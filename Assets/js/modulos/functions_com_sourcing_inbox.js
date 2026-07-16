/**
 * MRP System - Sourcing Inbox (Partidas por Negociar)
 * @module SourcingInbox
 * @description Gestión de recolección de partidas especiales para crear eventos de negociación.
 */

const SourcingInbox = {

    // 1. CONFIGURACIÓN Y ESTADO
    config: {
        endpoints: {
            pending: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/pending-items`,
            create: `${Sys_Core.Config.baseUrl}/api/v1/sourcing/events`
        }
    },

    state: {
        items: [],     // Datos crudos del servidor
        selected: [],   // IDs de partidas seleccionadas
        targetId: null
    },

    dom: {},

    // 2. INICIALIZACIÓN
    init: function () {
        this.state.targetId = Sys_Core.URL.getParam('target');
        // Validar sesión antes de cargar nada
        Sys_Core.Auth.validateSession();
        
        this.cacheDOM();
        this.bindEvents();
        this.loadData();
    },

    cacheDOM: function () {
        this.dom = {
            $tbody: $('#tbodyInbox'),
            $checkAll: $('#checkAll'),
            $btnConfirm: $('#btn-confirm-event'),
            $countLabel: $('#lbl-count-selected'),
            $budgetLabel: $('#lbl-budget-selected'),
            $titleInput: $('#txtEventTitle'),
            $loaderContainer: $('#tblInbox').closest('.card')
        };
    },

    // 3. EVENT LISTENERS (Delegación para elementos dinámicos)
    bindEvents: function () {
        const self = this;

        // Selección masiva
        this.dom.$checkAll.on('change', function() {
            const isChecked = $(this).is(':checked');
            self.toggleAll(isChecked);
        });

        // Selección individual (Delegado)
        this.dom.$tbody.on('change', '.check-item', function() {
            self.syncSelection();
        });

        // Botón de acción principal
        this.dom.$btnConfirm.on('click', function() {
            self.createEvent();
        });
        
        // Búsqueda local básica (Opcional, para UX rápida)
        $('#searchInbox').on('keyup', function() {
            const term = $(this).val().toLowerCase();
            self.dom.$tbody.find('tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(term) > -1);
            });
        });
    },

    // 4. PERSISTENCIA Y CARGA (API)
    loadData: function () {
        const self = this;
        
        Sys_Core.Net.get({
            url: this.config.endpoints.pending,
            onSuccess: function (res) {
                self.state.items = res.data;
                self.renderTable();

                if (self.state.targetId) {
                    self.highlightTarget(self.state.targetId);
                }
            }
        });
    },

    // 5. RENDERIZADORES DE UI
    renderTable: function () {
        let html = '';
        
        if (this.state.items.length === 0) {
            html = `
            <tr>
                <td colspan="7" class="text-center p-5 text-muted opacity-50">
                    <i class="ri-inbox-archive-line fs-1 d-block mb-2"></i>
                    No hay partidas en estado "Sourcing" para su planta en este momento.
                </td>
            </tr>`;
        } else {
            this.state.items.forEach(item => {
                const isTarget = (item.idrequisicionarticulo == this.state.targetId);
                const rowClass = isTarget ? 'bg-info-subtle border-start border-3 border-info' : 'animate__animated animate__fadeIn';
                const isChecked = isTarget ? 'checked' : '';
                const p = (item.prioridad || 'Baja').toUpperCase();
                const pClass = (p === 'ALTA' || p === 'URGENTE') ? 'text-danger' : (p === 'MEDIA' ? 'text-warning' : 'text-success');
                
                // Formateo del precio para el data-attribute
                const rawPrice = parseFloat(item.precio_objetivo) || 0;

                html += `
                <tr class="${rowClass}" id="row-item-${item.idrequisicionarticulo}">
                    <td class="ps-4">
                        <div class="form-check">
                            <input class="form-check-input check-item" type="checkbox" 
                                   value="${item.idrequisicionarticulo}" 
                                   data-price="${rawPrice}"
                                   ${isChecked}>
                        </div>
                    </td>
                    <td><span class="fw-bold text-body">${item.folio_requisicion || '---'}</span></td>
                    <td>
                        <div class="fw-medium text-body text-uppercase fs-12">${item.descripcion_item}</div>
                        <small class="text-muted">Item ID: #${item.idrequisicionarticulo}</small>
                    </td>
                    <td>
                        <span class="badge bg-light-subtle text-muted border border-light-subtle px-2 py-1 fs-10">
                            ${item.categoria_sourcing || 'GENERAL'}
                        </span>
                    </td>
                    <td class="text-end fw-bold text-primary">
                        ${Sys_Core.Format.toCurrency(rawPrice)}
                    </td>
                    <td class="text-center">
                        <span class="fs-10 fw-bold ${pClass}">
                            <i class="ri-checkbox-blank-circle-fill me-1"></i>${p}
                        </span>
                    </td>
                    <td class="text-end pe-4 text-muted fs-12">
                        ${Sys_Core.Format.toDate(item.fecha_requisicion)}
                    </td>
                </tr>`;
            });
        }
        this.dom.$tbody.html(html);
        this.syncSelection(); // Resetear conteos
    },

    highlightTarget: function(id) {
        const $row = $(`#row-item-${id}`);
        if ($row.length) {
            // Animación sutil para llamar la atención (ADN Velzon)
            $row.addClass('animate__animated animate__pulse');
            
            // Scroll suave hacia la fila para que quede a la vista
            $('html, body').animate({
                scrollTop: $row.offset().top - 150
            }, 800);

            Sys_Core.UI.notify(`Item #${id} localizado y seleccionado automáticamente.`, 'info');
        }
    },

    // 6. LÓGICA DE NEGOCIO (SELECCIÓN Y CÁLCULOS)
    toggleAll: function (isChecked) {
        this.dom.$tbody.find('.check-item:visible').prop('checked', isChecked);
        this.syncSelection();
    },

    syncSelection: function () {
        const $checked = this.dom.$tbody.find('.check-item:checked');
        this.state.selected = $checked.map((i, el) => $(el).val()).get();
        
        // Calcular presupuesto total acumulado de lo seleccionado
        let totalBudget = 0;
        $checked.each((i, el) => {
            totalBudget += parseFloat($(el).data('price')) || 0;
        });

        // Actualizar UI del panel lateral (Sticky)
        this.dom.$countLabel.text(this.state.selected.length);
        this.dom.$budgetLabel.text(Sys_Core.Format.toCurrency(totalBudget));
        
        // Bloqueo de seguridad: No enviar si no hay nada elegido
        this.dom.$btnConfirm.prop('disabled', this.state.selected.length === 0);
    },

    /**
     * Envía la agrupación a la API para generar el folio SOUR-XXX
     */
    createEvent: function () {
        const title = $.trim(this.dom.$titleInput.val());
        const self = this;

        if (!title) {
            Sys_Core.UI.notify('Debe asignar un nombre descriptivo a la negociación.', 'warning');
            this.dom.$titleInput.focus();
            return;
        }

        const payload = {
            titulo: title,
            items: this.state.selected
        };

        Sys_Core.UI.confirm({
            title: '¿Generar Folio de Negociación?',
            text: `Se agruparán ${this.state.selected.length} partidas en un nuevo folio de Sourcing. Esta acción es irreversible.`,
            confirmText: 'Sí, crear folio'
        }).then((result) => {
            if (result.isConfirmed) {
                Sys_Core.Net.post({
                    url: this.config.endpoints.create,
                    payload: payload,
                    $btn: this.dom.$btnConfirm, // Muestra spinner automáticamente
                    onDone: function (res) {
                        // El servicio devuelve {folio: "SOUR-XXXX"}
                        Sys_Core.UI.notify(`Evento ${res.data.folio} generado con éxito.`, 'success');
                        
                        // Redirigir al Panel principal después de un breve delay
                        setTimeout(() => {
                            Sys_Core.Navigation.to('com_sourcing');
                        }, 1200);
                    }
                });
            }
        });
    }
};

// Inicialización al cargar el DOM
$(document).ready(function () {
    SourcingInbox.init();
});