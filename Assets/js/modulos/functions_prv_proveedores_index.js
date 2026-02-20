/**
 * MRP System - Supplier Index Management
 * @module SupplierIndex
 * @description Listado de proveedores con integración de DataTables.
 * @requires Sys_Core, DataTables
 */

$(document).ready(function () {

    /**
     * @description Inicialización de la tabla principal de proveedores.
     */
    const tabla = $('#tblProveedores').DataTable({
        "ajax": {
            "url": `${Sys_Core.Config.baseUrl}/prv_proveedor/index`,
            "dataSrc": "data",
            "beforeSend": () => Sys_Core.UI.toggleLoader('#tblVendors', true),
            "complete": () => Sys_Core.UI.toggleLoader('#tblVendors', false)
        },
        "columns": [
            { "data": "idproveedor", "render": (data) => `<span class="fw-bold">#${data}</span>` },
            { "data": "clv_proveedor" },
            { "data": "rfc" },
            { "data": "contacto" },
            { "data": "limite_credito", render: (data) => Sys_Core.Format.toCurrency(data) },
            { 
                "data": "estatus", "render": function (data) {
                    const clases = {
                        '2': 'text-success',
                        '1': 'text-danger'
                    };
                    return `<span class="text-uppercase font-weight-bold ${clases[data] || 'text-muted'} px-2 py-1">${data == '2' ? 'Activo' : 'Inactivo'}</span>`;
                }
            },
            {
                "data": null,
                "orderable": false,
                "className": "text-end",
                "render": function (data, type, r){ 
                    let buttons = `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-redirect="prv_proveedor/edit?id=${r.idproveedor}">
                            <i class="ri-eye-line"></i> Ver
                        </button>
                    `;

                    if (Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'd')) {
                        buttons += `
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button class="dropdown-item text-danger btn-delete" data-id="${r.idproveedor}" data-rf="${r.rfc}" data-action="delete"><i class="ri-delete-bin-6-line"></i> Eliminar</button></li>
                            </ul>
                        `;
                    }
            
                    buttons += `
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
     * @description Eliminación de proveedores con confirmación centralizada.
     */
    $('#tblProveedores').on('click', '.btn-delete', function () {
        const idproveedor = $(this).data('id');
        const rfc = $(this).data('rfc');
        Sys_Core.UI.confirm({
            title: '¿Quitar artículo?',
            text: 'El proveedor será eliminado de la lista.',
            confirmText: 'Sí, quitar'
        }).then((result) => {
            if (result.isConfirmed) {
                Sys_Core.Net.post({
                    url: `${Sys_Core.Config.baseUrl}/prv_proveedor/delete`,
                    payload: $.param({ idproveedor, rfc }),
                    successMsg: `Removido correctamente.`,
                    onDone: () => {
                        tabla.ajax.reload(null, false);
                    }
                })
            }
        });
    });

    // Definimos qué estatus de la DB va a qué ID de HTML
    const requisicionesMap = {
        'total': 'kpi-total',
        '2': 'kpi-activos',
        '1': 'kpi-inactivos'
    };

    // Lanzamos la actualización recurrente cada 30 segundos
    Sys_Core.UI.Dashboard.refreshKPIs(
        Sys_Core.Config.baseUrl + '/prv_proveedor/getKpi', 
        requisicionesMap, 
        true
    );
});