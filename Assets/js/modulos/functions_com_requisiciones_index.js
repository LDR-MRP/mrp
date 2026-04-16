/**
 * MRP System - Requisition Index Management
 * @module RequisitionIndex
 * @description Listado de requisiciones RESTful con DataTables y acciones inline.
 * @requires Sys_Core, DataTables
 */

const RequisitionIndex = {

    // 1. CONFIGURACIÓN Y ESTADO
    config: {
        endpoints: {
            base: `${Sys_Core.Config.baseUrl}/api/v1/requisitions`, // API Base para listado y CRUD
            kpis: `${Sys_Core.Config.baseUrl}/api/v1/requisitions/kpis` // Nuevo endpoint RESTful para métricas
        },
        actionDictionary: {
            'approve': { titulo: 'Aprobar',  clase: 'success',   icon: 'ri-check-line',           method: 'POST',   suffix: '/approve' },
            'reject':  { titulo: 'Rechazar', clase: 'danger',    icon: 'ri-close-circle-line',    method: 'POST',   suffix: '/reject' },
            'cancel':  { titulo: 'Cancelar', clase: 'secondary', icon: 'ri-stop-circle-line',     method: 'POST',   suffix: '/cancel' },
            'destroy': { titulo: 'Eliminar', clase: 'danger',    icon: 'ri-delete-bin-6-line',    method: 'DELETE', suffix: '' },
            // NUEVO: Acción para corregir rechazos o retirar envíos
            'return_to_draft': { titulo: 'Devolver a Borrador', clase: 'warning', icon: 'ri-arrow-go-back-line', method: 'POST', suffix: '/return-to-draft' } 
        }
    },

    state: {
        dataTable: null
    },

    dom: {},

    // 2. INICIALIZACIÓN
    init: function () {
        this.cacheDOM();
        this.initDataTable();
        this.bindEvents();
        this.initKPIs();
    },

    cacheDOM: function () {
        this.dom = {
            $table: $('#tblReqs')
        };
    },

    // 3. EVENT LISTENERS
    bindEvents: function () {
        this.dom.$table.on('click', '.action-inline', (e) => this.showInlineAction(e));
        this.dom.$table.on('click', '.btn-confirmar-inline', (e) => this.submitInlineAction(e));
        this.dom.$table.on('click', '.btn-cancelar-inline', () => this.hideInlineAction());
    },

    // 4. CONFIGURACIÓN DATATABLES
    initDataTable: function () {
        this.state.dataTable = this.dom.$table.DataTable({
            ajax: {
                url: this.config.endpoints.base, // GET /api/v1/requisitions
                type: 'GET',
                dataSrc: "data",
                beforeSend: () => Sys_Core.UI.toggleLoader('#tblReqs', true),
                complete: () => Sys_Core.UI.toggleLoader('#tblReqs', false),
                error: (xhr) => Sys_Core.Net.handleError(xhr)
            },
            columns: [
                { data: "idrequisicion", render: (data) => `<span class="fw-bold">#${data}</span>` },
                { data: "titulo" },
                { data: "fecha" },
                { data: "fecha_requerida" },
                { data: "id_empresa" },
                { data: "solicitante" },
                { data: "aprobador" },
                { data: "departamento_descripcion" },
                { data: null, render: () => "NA" },
                { data: "estatus", render: (data) => this.renderStatusBadge(data) },
                { 
                    data: null, 
                    orderable: false, 
                    className: "text-end",
                    render: (data, type, row) => this.renderActionButtons(row) 
                }
            ],
            dom: "<'d-flex justify-content-between align-items-center mb-2'lfB>t<'d-flex justify-content-between mt-2'ip>",
            buttons: [
                { extend: 'excelHtml5', text: '<i class="ri-file-excel-2-line"></i>', className: 'btn btn-success btn-sm', exportOptions: { columns: ':not(:last-child)' } },
                { extend: 'pdfHtml5',   text: '<i class="ri-file-pdf-line"></i>',   className: 'btn btn-danger btn-sm',  exportOptions: { columns: ':not(:last-child)' } }
            ],
            responsive: true,
            autoWidth: false,
            order: [[1, 'desc']],
        });
    },

    // 5. RENDERIZADORES DE UI
    renderStatusBadge: function (status) {
        const clases = {
            'borrador': 'badge-draft',
            'pendiente': 'badge-review',
            'aprobada': 'badge-approved',
            'rechazada': 'badge-rejected',
            'en compra': 'badge-purchasing',
            'finalizada': 'badge-closed',
            'cancelada': 'badge-closed',
            'eliminada': 'badge-closed'
        };
        const badgeClass = clases[status?.toLowerCase()] || 'bg-secondary';
        return `<span class="badge ${badgeClass} px-3 py-2 text-capitalize">${status}</span>`;
    },

    // 5. RENDERIZADORES DE UI (En functions_com_requisiciones_index.js)
    
    renderActionButtons: function (row) {
        // 1. Extraemos los permisos del usuario
        const canUpdate = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u');
        const canDelete = Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'd');
        const canApprove = Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r'); // Asumiendo que este permiso dicta quién aprueba
        
        // 2. Normalizamos el estatus de la base de datos (minúsculas para evitar bugs)
        const status = row.estatus ? row.estatus.toLowerCase() : '';

        // 3. Botón Base: Siempre se puede "Ver" (Read-only)
        let html = `
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-redirect="com_requisicion/read/${row.idrequisicion}">
                    <i class="ri-eye-line"></i> Ver
                </button>
        `;

        // 4. Array para recolectar las acciones disponibles (Dynamic Menu)
        let menuItems = [];

        // --- LÓGICA DE LA MÁQUINA DE ESTADOS ---

        switch (status) {
            case 'borrador':
                // Un borrador se puede seguir editando o eliminar
                if (canUpdate) {
                    menuItems.push(`<li><button class="dropdown-item text-primary" data-redirect="com_requisicion/create/${row.idrequisicion}"><i class="ri-pencil-line"></i> Continuar Editando</button></li>`);
                }
                if (canDelete) {
                    menuItems.push('divider');
                    menuItems.push(`<li><button class="dropdown-item text-danger action-inline" data-id="${row.idrequisicion}" data-accion="destroy"><i class="ri-delete-bin-6-line"></i> Eliminar Borrador</button></li>`);
                }
                break;

            case 'pendiente':
                // Pendiente de Aprobación
                if (canApprove) {
                    menuItems.push(`<li><button class="dropdown-item action-inline" data-id="${row.idrequisicion}" data-accion="approve"><i class="ri-check-line text-success"></i> Aprobar Solicitud</button></li>`);
                    menuItems.push(`<li><button class="dropdown-item action-inline" data-id="${row.idrequisicion}" data-accion="reject"><i class="ri-close-circle-line text-danger"></i> Rechazar</button></li>`);
                }
                if (canUpdate) {
                    menuItems.push('divider');
                    // "return_to_draft" es una acción lógica muy útil para correcciones sin rechazar formalmente
                    menuItems.push(`<li><button class="dropdown-item text-warning action-inline" data-id="${row.idrequisicion}" data-accion="return_to_draft"><i class="ri-arrow-go-back-line"></i> Devolver a Borrador</button></li>`);
                }
                if (canDelete) { // Opcional: Permitir cancelar una solicitud pendiente
                    menuItems.push(`<li><button class="dropdown-item text-danger action-inline" data-id="${row.idrequisicion}" data-accion="cancel"><i class="ri-stop-circle-line"></i> Cancelar Solicitud</button></li>`);
                }
                break;

            case 'rechazada':
                // Si fue rechazada, el usuario puede corregirla (volviéndola a borrador)
                if (canUpdate) {
                    menuItems.push(`<li><button class="dropdown-item text-primary action-inline" data-id="${row.idrequisicion}" data-accion="return_to_draft"><i class="ri-edit-circle-line"></i> Corregir (Volver a Borrador)</button></li>`);
                }
                if (canDelete) {
                    menuItems.push('divider');
                    menuItems.push(`<li><button class="dropdown-item text-danger action-inline" data-id="${row.idrequisicion}" data-accion="cancel"><i class="ri-stop-circle-line"></i> Cancelar Definitivamente</button></li>`);
                }
                break;

            case 'aprobada':
                // Si está aprobada, el siguiente paso es generar la OC (gestionado en otro módulo o aquí)
                if (canApprove) { // O el permiso que corresponda a "Crear OC"
                    menuItems.push(`<li><button class="dropdown-item text-primary" data-redirect="com_orden/create?req_id=${row.idrequisicion}"><i class="ri-file-list-3-line"></i> Generar Orden de Compra</button></li>`);
                }
                if (canDelete) { // Solo un admin debería poder cancelar algo ya aprobado
                    menuItems.push('divider');
                    menuItems.push(`<li><button class="dropdown-item text-danger action-inline" data-id="${row.idrequisicion}" data-accion="cancel"><i class="ri-stop-circle-line"></i> Cancelar Aprobación</button></li>`);
                }
                break;

            case 'en compra':
            case 'finalizada':
            case 'cancelada':
            case 'eliminada':
                // Estados terminales: No hay acciones disponibles en este menú
                // El array menuItems se queda vacío.
                break;
        }

        // 5. Renderizado final del Dropdown (Solo si hay ítems en el menú)
        if (menuItems.length > 0) {
            html += `
                <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown" aria-expanded="false"></button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            `;
            
            menuItems.forEach(item => {
                if (item === 'divider') {
                    html += `<li><hr class="dropdown-divider"></li>`;
                } else {
                    html += item;
                }
            });

            html += `</ul>`;
        }

        html += `</div>`; // Cierra btn-group
        return html;
    },

    // 6. LÓGICA DE NEGOCIO (Acciones RESTful)
    showInlineAction: function (e) {
        const $btn = $(e.currentTarget);
        const id = $btn.data('id');
        const accion = $btn.data('accion');
        const $filaPadre = $btn.closest('tr');

        this.hideInlineAction();

        const config = this.config.actionDictionary[accion];
        if (!config) return;

        // Nota: Si la acción es 'destroy' (Eliminar), podríamos no requerir comentario, 
        // pero lo dejamos habilitado por seguridad de auditoría.
        const htmlInline = `
            <tr class="fila-accion-inline bg-light shadow-sm">
                <td colspan="100%" class="p-3 border-start border-3 border-${config.clase}">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="ri-chat-voice-line text-${config.clase} fs-3"></i>
                        </div>
                        <div class="flex-grow-1 me-3">
                            <input type="text" id="comentario_${id}" class="form-control border-${config.clase}" 
                                   placeholder="Escribe un comentario obligatorio para ${config.titulo.toLowerCase()} la solicitud #${id}..." autofocus>
                        </div>
                        <div class="d-flex">
                            <button class="btn btn-${config.clase} px-4 me-2 btn-confirmar-inline" 
                                    data-idrequisicion="${id}" 
                                    data-accion="${accion}">
                                <i class="${config.icon} align-middle me-1"></i> Confirmar
                            </button>
                            <button class="btn btn-light border btn-cancelar-inline" title="Cancelar">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                    </div>
                </td>
            </tr>`;

        $filaPadre.after(htmlInline);
    },

    submitInlineAction: function (e) {
        const $btn = $(e.currentTarget);
        const idrequisicion = $btn.data('idrequisicion');
        const accion = $btn.data('accion');
        const comentario = $.trim($(`#comentario_${idrequisicion}`).val());
        const originalHtml = $btn.html();

        const actionConfig = this.config.actionDictionary[accion];

        if (comentario === '') {
            Sys_Core.UI.notify('El comentario es obligatorio.', 'warning');
            $(`#comentario_${idrequisicion}`).focus();
            return;
        }

        // Construcción Dinámica de la URL RESTful.
        // Ej: DELETE /api/v1/requisitions/8  O  POST /api/v1/requisitions/8/approve
        const targetUrl = `${this.config.endpoints.base}/${idrequisicion}${actionConfig.suffix}`;

        // Usamos $.ajax directamente para poder manipular los verbos HTTP (DELETE, PUT)
        // que la utilidad Sys_Core.Net.post no soporta nativamente.
        $.ajax({
            url: targetUrl,
            method: actionConfig.method, // Inyectado desde el diccionario (POST, DELETE)
            contentType: 'application/json',
            data: JSON.stringify({ comentario: comentario }), // Payload JSON limpio
            beforeSend: () => {
                Sys_Core.UI.toggleLoader('#tblReqs', true);
                $btn.prop('disabled', true).html('<i class="ri-loader-4-line ri-spin"></i>');
            },
            success: (res) => {
                Sys_Core.UI.notify(res.message || `Acción procesada correctamente.`, 'success');
                this.hideInlineAction();
                this.state.dataTable.ajax.reload(null, false); // Actualiza la tabla sin perder página
                
                // Si tienes KPIs visuales, forzamos un refresh
                if (typeof Sys_Core.UI.Dashboard.refreshKPIs === 'function') {
                    this.initKPIs();
                }
            },
            error: (xhr) => {
                Sys_Core.Net.handleError(xhr);
                $btn.prop('disabled', false).html(originalHtml);
                Sys_Core.UI.toggleLoader('#tblReqs', false);
            }
        });
    },

    hideInlineAction: function () {
        $('.fila-accion-inline').remove();
    },

    // 7. KPIs Y DASHBOARD
    initKPIs: function () {
        const requisicionesMap = {
            'pendiente': 'kpi-pendientes',
            'aprobada':  'kpi-aprobadas',
            'finalizada':'kpi-finalizadas'
        };

        // Asume que el endpoint /api/v1/requisitions/kpis devuelve un array JSON estándar
        Sys_Core.UI.Dashboard.refreshKPIs(
            this.config.endpoints.kpis, 
            requisicionesMap, 
            true
        );
    }
};

// Arrancar Módulo
$(document).ready(function () {
    RequisitionIndex.init();
});