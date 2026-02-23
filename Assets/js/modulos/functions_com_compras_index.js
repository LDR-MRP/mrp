$(document).ready(function () {
    const tabla = $('#tblCompras').DataTable({
        ajax: {
            url: `${base_url}/com_compra/getReqs?estatus[]=aprobada&estatus[]=en compra`,
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
                                <i class="ri-eye-line"></i> Ver REQ
                            </button>
                    `;

                    if (Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'u') || Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r')) {
                        buttons += `
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                        `;
                    }

                    if (Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'u') && r.estatus === 'aprobada') {
                        buttons += `
                            <li><button class="dropdown-item action-inline" data-redirect="com_compra/creates/${r.idrequisicion}"><i class="ri-check-line"></i> Generar OC</button></li>
                        `;
                    }

                    if (Sys_Core.Auth.hasPermissions(MODS.COM_COMPRAS, 'r') && r.estatus === 'en compra') {
                        buttons += `
                            <li><button class="dropdown-item action-inline btn-pdf-download" data-id="${r.idrequisicion}"><i class="ri-eye-line"></i> Ver OC</button></li>
                        `;
                    }
            
                    buttons += `
                            </ul>
                        </div>
                    `;

                    return buttons;
                }
            },
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

// We use $(document) to ensure it catches the event even inside 
// responsive child rows or dropdowns.
$(document).on('click', '.btn-pdf-download', function(e) {
    // 1. Prevent any default behavior
    e.preventDefault();
    e.stopPropagation(); // Stops the click from closing the dropdown too fast

    // 2. Get the ID
    const id = $(this).data('id');
    
    console.log("PDF Triggered for ID:", id); // Check your console!

    if(id) {
        fntDownloadOC(id);
    } else {
        console.error("ID not found in data-id attribute");
    }
});

/**
 * Generates and downloads the PDF using the AJAX/Blob approach
 * @param {number} id - The Purchase Order ID
 */
async function fntDownloadOC(id) {
    // 1. Show "Carísimo" Loader
    Swal.fire({
        title: 'Generando Documento',
        text: 'Por favor espere mientras preparamos la Orden de Compra...',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); }
    });

    try {
        // 2. Fetch the PDF as a Blob (binary data)
        const response = await fetch(`${base_url}/com_compra/exportPDF/${id}`);
        
        if (!response.ok) throw new Error('Error al generar el PDF');

        const blob = await response.blob();
        
        // 3. Create a temporary URL for the Blob
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.style.display = 'none';
        a.href = url;
        a.download = `OC_LDR_${id}.pdf`; // The filename
        
        // 4. Trigger the download and cleanup
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        
        Swal.close(); // Close the loader
        
    } catch (error) {
        console.error(error);
        Swal.fire('Error', 'No se pudo generar el PDF. Contacte a soporte.', 'error');
    }
}