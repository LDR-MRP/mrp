/**
 * MRP System - Supplier Index Management
 * @module SupplierIndex
 * @description Listado de proveedores con integración de DataTables.
 * @requires Sys_Core, DataTables
 */

$(document).ready(function () {
    Sys_Core.Auth.validateSession();

    const token = Sys_Core.Auth.getCookie('mrp_token');

    /**
     * @description Inicialización de la tabla principal de proveedores.
     */
    const tabla = $('#tblProveedores').DataTable({
        "ajax": {
            "url": `${Sys_Core.Config.baseUrl}/api/v1/suppliers`,
            "dataSrc": "data",
            beforeSend: function (request) {
                Sys_Core.UI.toggleLoader('#tblProveedores', true);
                if (token) {
                    request.setRequestHeader("Authorization", `Bearer ${token}`);
                }
            },
            "complete": () => Sys_Core.UI.toggleLoader('#tblProveedores', false)
        },
        "columns": [
            { "data": "created_at", "render": (data) => `<span class="fw-bold">${data}</span>` },
            { 
                "data": "estatus_onboarding", "render": function (data) {
                    const clases = {
                        'Prospecto': 'text-warning',
                        'En Revision': 'text-info',
                        'Aprobado': 'text-success',
                        'Rechazado': 'text-danger'
                    };
                    return `<span class="text-uppercase font-weight-bold ${clases[data] || 'text-muted'} px-2 py-1">${data}</span>`;
                }
            },
            { 
                "data": "estatus_operativo", "render": function (data) {
                    const clases = {
                        '1': 'text-success',
                        '0': 'text-danger'
                    };
                    return `<span class="text-uppercase font-weight-bold ${clases[data] || 'text-muted'} px-2 py-1">${data == '1' ? 'Activo' : 'Inactivo'}</span>`;
                }
            },
            { "data": "nombre_comercial" },
            { "data": "razon_social" },
            { "data": "cuenta_contable" },
            { "data": "rfc" },
            { "data": "origen" },
            { "data": "telefono" },
            { "data": "descripcion" },
            { "data": "ciudad" },
            { "data": "estado" },
            { "data": "tasa_iva_default" },
            { "data": "created_by" },
            { "data": "limite_credito", render: (data) => Sys_Core.Format.toCurrency(data) },
            {
                "data": null,
                "orderable": false,
                "className": "text-end",
                "render": function (data, type, r){ 
                    let buttons = `
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-redirect="prv_proveedor/edit?id=${r.id}">
                            <i class="ri-eye-line"></i> Ver
                        </button>
                    `;

                    if (Sys_Core.Auth.hasPermissions(MODS.PRV_PROVEEDORES, 'd')) {
                        buttons += `
                            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown"></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><button class="dropdown-item text-danger btn-delete" data-id="${r.id}" data-rf="${r.rfc}" data-action="delete"><i class="ri-delete-bin-6-line"></i> Eliminar</button></li>
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
                    url: `${Sys_Core.Config.baseUrl}/prv_proveedor/delete`, // TODO
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
    const proveedoresMap = {
        'total': 'kpi-total',
        'aprobado': 'kpi-activos',
        'prospecto': 'kpi-inactivos'
    };

    // Lanzamos la actualización recurrente cada 30 segundos
    Sys_Core.UI.Dashboard.refreshKPIs(
        Sys_Core.Config.baseUrl + '/api/v1/suppliers/kpis', 
        proveedoresMap, 
        true
    );
});