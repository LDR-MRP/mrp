/**
 * LDR Solutions - MRP System
 * Ref: HU-01, HU-02, HU-07, HU-09
 */

$(document).ready(function () {
    // 1. INICIALIZACIÓN DE DATATABLE
    const tabla = $('#tblReqs').DataTable({
        ajax: {
            url: `${base_url}/com_requisicion/index?estatus=aprobada`,
            dataSrc: "data",
            beforeSend: () => { $('#loaderTable').removeClass('d-none'); $('#tblReqs').addClass('opacity-50'); },
            complete: () => { $('#loaderTable').addClass('d-none'); $('#tblReqs').removeClass('opacity-50'); }
        },
        columns: [
            { "data": "idrequisicion", "render": (data) => `<span class="fw-bold">#${data}</span>` },
            { "data": "departamento_descripcion" },
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
            { "data": "monto_estimado", "render": (data) => formatoMoneda(data) },
            {
                "data": null,
                "orderable": false,
                "className": "text-end",
                "render": (data, type, r) => `
                    <div class="btn-group" role="group">
                        <button class="btn btn-success btn-sm px-4 shadow-sm  action-nueva" data-id="${r.idrequisicion}">
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

    // 2. LISTENERS DE ACCIONES
    const tbl = $('#tblReqs');

    // Botón principal "Generar OC"
    tbl.on('click', '.action-nueva', function() {
        window.location.href = `${base_url}/com_compra/generar/${$(this).data('id')}`;
    });

    // Filtros y buscadores (Mantener igual)
    $('#q').on('keyup', () => tabla.search($('#q').val()).draw());
    $('#estadoQuick').on('change', () => tabla.column(4).search($('#estadoQuick').val()).draw());
});

function formatoMoneda(valor) {
    return new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(valor || 0);
}