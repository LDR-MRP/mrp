/**
 * MRP System - Requisition Index Management
 * @module RequisitionIndex
 * @description Listado de requisiciones con integración de DataTables y acciones inline.
 * @requires Sys_Core, DataTables
 */

$(document).ready(function () {

    /**
     * @description Inicialización de la tabla principal de requisiciones.
     */
    const tabla = $('#tblReqs').DataTable({
        "ajax": {
            "url": `${Sys_Core.Config.baseUrl}/com_requisicion/index`,
            "dataSrc": "data",
            "beforeSend": () => Sys_Core.UI.toggleLoader('#tblReqs', true),
            "complete": () => Sys_Core.UI.toggleLoader('#tblReqs', false)
        },
        "columns": [
            { "data": "idrequisicion", "render": (data) => `<span class="fw-bold">#${data}</span>` },
            { "data": "fecha" },
            { "data": "solicitante" },
            { "data": "departamento_descripcion" },
            {
                "data": "estatus", "render": function (data) {
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
                    return `<span class="badge ${clases[data.toLowerCase()] || 'bg-secondary'} px-3 py-2 text-capitalize">${data}</span>`;
                }
            },
            {
                "data": null,
                "orderable": false,
                "className": "text-end",
                render: function(data, type, r) {
                    let buttons = `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-redirect="com_requisicion/detalle/${r.idrequisicion}">
                                <i class="ri-eye-line"></i> Ver
                            </button>
                    `;

                    if (Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u') || Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'd')) {
                        buttons += `
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                        `;
                    }

                    if (Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'u')) {
                        buttons += `
                            <li><button class="dropdown-item action-inline" data-id="${r.idrequisicion}" data-accion="approve"><i class="ri-check-line"></i> Aprobar</button></li>
                            <li><button class="dropdown-item action-inline" data-id="${r.idrequisicion}" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button></li>
                        `;
                    }

                    if (Sys_Core.Auth.hasPermissions(MODS.COM_REQUISICIONES, 'd')) {
                        buttons += `
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item action-inline" data-id="${r.idrequisicion}" data-accion="cancel"><i class="ri-close-line"></i> Cancelar</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger action-inline" data-id="${r.idrequisicion}" data-accion="destroy"><i class="ri-delete-bin-6-line"></i> Eliminar</button></li>
                        `;
                    }
            
                    buttons += `
                            </ul>
                        </div>
                    `;

                    return buttons;
                }
            }
        ],
        dom: "<'d-flex justify-content-between align-items-center mb-2'lfB>t<'d-flex justify-content-between mt-2'ip>",
        buttons: [
            {
                extend: 'excelHtml5',
                text: '<i class="ri-file-excel-2-line"></i>',
                titleAttr: 'Exportar a Excel',
                className: 'btn btn-success btn-sm',
                exportOptions: { columns: ':not(:last-child)' }
            },
            {
                extend: 'pdfHtml5',
                text: '<i class="ri-file-pdf-line"></i>',
                titleAttr: 'Exportar a PDF',
                className: 'btn btn-danger btn-sm',
                exportOptions: { columns: ':not(:last-child)' }
            }
        ],
        responsive: true,
        autoWidth: false,
    });

    /**
     * @description Inyecta una fila de acción rápida (inline) para procesamiento con comentarios.
     */
    $('#tblReqs').on('click', '.action-inline', function () {
        const btn = $(this);
        const id = btn.data('id');
        const accion = btn.data('accion');
        const filaPadre = btn.closest('tr');

        $('.fila-accion-inline').remove();

        const config = {
            'approve': { titulo: 'Aprobar', clase: 'success' },
            'reject': { titulo: 'Rechazar', clase: 'danger' },
            'cancel': { titulo: 'Cancelar', clase: 'secondary' },
            'destroy': { titulo: 'Eliminar', clase: 'danger' }
        };
        const c = config[accion];

        const htmlInline = `
            <tr class="fila-accion-inline bg-light">
                <td colspan="100%" class="p-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="ri-chat-voice-line text-${c.clase} fs-3"></i>
                        </div>
                        <div class="flex-grow-1 me-3">
                            <input type="text" id="comentario_${id}" class="form-control form-control-sm border-${c.clase}" 
                                   placeholder="Comentario para ${c.titulo} de #${id}..." autofocus>
                        </div>
                        <div class="d-flex">
                            <button class="btn btn-sm btn-${c.clase} px-4 me-2 btn-confirmar-inline" 
                                    data-idrequisicion="${id}" 
                                    data-accion="${accion}">Confirmar</button>
                            <button class="btn btn-sm btn-light border btn-cancelar-inline"><i class="ri-close-line"></i></button>
                        </div>
                    </div>
                </td>
            </tr>`;

        filaPadre.after(htmlInline);
    });

    /**
     * @description Procesa la confirmación de la acción inline usando el motor Sys_Core.Net.
     */
    $(document).on('click', '.btn-confirmar-inline', function () {
        const idrequisicion = $(this).data('idrequisicion');
        const accion = $(this).data('accion');
        const comentario = $(`#comentario_${idrequisicion}`).val();

        Sys_Core.Net.post({
            url: `${Sys_Core.Config.baseUrl}/com_requisicion/${accion}`,
            payload: $.param({ idrequisicion, comentario, accion }),
            successMsg: `Acción ${accion} procesada correctamente.`,
            onDone: () => {
                $('.fila-accion-inline').remove();
                tabla.ajax.reload(null, false);
            }
        });
    });

    /**
     * @description Remueve la fila de acción rápida sin procesar datos.
     */
    $(document).on('click', '.btn-cancelar-inline', () => $('.fila-accion-inline').remove());

    // Definimos qué estatus de la DB va a qué ID de HTML
    const requisicionesMap = {
        'pendiente': 'kpi-pendientes',
        'aprobada': 'kpi-aprobadas',
        'finalizada': 'kpi-finalizadas'
    };

    // Lanzamos la actualización recurrente cada 30 segundos
    Sys_Core.UI.Dashboard.refreshKPIs(
        Sys_Core.Config.baseUrl + '/com_requisicion/getKpi', 
        requisicionesMap, 
        true
    );

});