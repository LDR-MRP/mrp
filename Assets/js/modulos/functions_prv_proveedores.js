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
    const tabla = $('#tblVendors').DataTable({
        "ajax": {
            "url": `${Sys_Core.Config.baseUrl}/prv_proveedor/index`,
            "dataSrc": "data",
            "beforeSend": () => Sys_Core.UI.toggleLoader('#tblVendors', true),
            "complete": () => Sys_Core.UI.toggleLoader('#tblVendors', false)
        },
        "columns": [
            { "data": "idproveedor", "render": (data) => `<span class="fw-bold">#${data}</span>` },
            { "data": "clv_proveedor" },
            { "data": "nombre_comercial" },
            {
                "data": null,
                "orderable": false,
                "className": "text-end",
                "render": (data, type, r) => `
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-secondary btn-sm action-ver" data-id="${r.idproveedor}" data-redirect="prv_proveedor/nuevo">
                            <i class="ri-eye-line"></i> Ver
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><button class="dropdown-item action-inline" data-id="${r.idproveedor}" data-accion="approve"><i class="ri-check-line"></i> Aprobar</button></li>
                            <li><button class="dropdown-item action-inline" data-id="${r.idproveedor}" data-accion="reject"><i class="ri-close-circle-line"></i> Rechazar</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item action-inline" data-id="${r.idproveedor}" data-accion="cancel"><i class="ri-close-line"></i> Cancelar</button></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><button class="dropdown-item text-danger action-inline" data-id="${r.idproveedor}" data-accion="destroy"><i class="ri-delete-bin-6-line"></i> Eliminar</button></li>
                        </ul>
                    </div>`
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
     * @description Redirección al detalle del proveedor.
     */
    $('#tblVendors').on('click', '.action-ver', function () {
        window.location.href = `${Sys_Core.Config.baseUrl}/prv_proveedor/detalle/${$(this).data('id')}`;
    });

 


    /**
     * @description Navegación hacia la interfaz de nuevo proveedor.
     */
    $('#btnNueva').on('click', function () {
        window.location.href = `${Sys_Core.Config.baseUrl}/prv_proveedor/nuevo`;
    });
});