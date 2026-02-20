$(document).ready(function () {
    const tabla = $('#tblCompras').DataTable({
        ajax: {
            url: `${base_url}/com_compra/getReqs?estatus=aprobada`,
            dataSrc: "data",
            beforeSend: () => { $('#loaderTable').removeClass('d-none'); $('#tblCompras').addClass('opacity-50'); },
            complete: () => { $('#loaderTable').addClass('d-none'); $('#tblCompras').removeClass('opacity-50'); }
        },
        columns: [
            { "data": "idrequisicion", "render": (data) => `<span class="fw-bold">#${data}</span>` },
            {
                "data": "prioridad", "render": function (data) {
                    const clases = {
                        'alta': 'text-danger',
                        'media': 'text-warning',
                        'baja': 'text-primary'
                    };
                    return `<span class="text-uppercase font-weight-bold ${clases[data.toLowerCase()] || 'text-muted'} px-2 py-1">${data}</span>`;
                }
            },
            { "data": "departamento_descripcion" },
            { "data": "solicitante" },
            { "data": "monto_estimado", "render": (data) => Sys_Core.Format.toCurrency(data) },
            {
                 "data": "estatus", render: function (data) {
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
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-redirect="com_requisicion/read/${r.idrequisicion}">
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
            
                    buttons += `
                            </ul>
                        </div>
                    `;

                    return buttons;
                }
            },
            {
                "data": null,
                "orderable": false,
                "className": "text-end",
                "render": (data, type, r) => `
                    <div class="btn-group" role="group">
                        <button class="btn btn-success btn-sm px-4 shadow-sm" data-redirect="com_compra/create/${r.idrequisicion}">
                            Generar OC <i class="ri-arrow-right-line ml-1"></i>
                        </button>
                    </div>`
            }
        ],
        dom:            
            "<'d-flex justify-content-between align-items-center mb-2'lfB>" +
            "t" +
            "<'d-flex justify-content-between mt-2'ip>",
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
            },
            {
                extend: 'print',
                text: '<i class="ri-printer-line"></i>',
                titleAttr: 'Imprimir',
                className: 'btn btn-secondary btn-sm',
                exportOptions: { columns: ':not(:last-child)' }
            }
        ],
        responsive: true,
        scrollX: false,
        scrollCollapse: true,
        autoWidth: false,
    });
});