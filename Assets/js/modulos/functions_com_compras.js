// Inputs del formulario
const selectProveedor = document.querySelector("#proveedor");
const selectAlmacen = document.querySelector("#almacen");
const selectMoneda = document.querySelector("#moneda");
let idFila = 0;
            
document.addEventListener(
    "DOMContentLoaded",
    function () {
        const table = $('#tableCompras').DataTable({
            ajax: {
                url: base_url + "/com_compras/index",
                dataSrc: "",
            },
            columns: [
                {
                    data: null,
                    defaultContent: '',
                    orderable: false,
                    searchable: false
                },
                { data: "tipo_documento" },
                { data: "status" },
                { data: "enlazado" },
                { data: "clv_documento" },
                { data: "nombre_comercial" },
                { data: "cantidad_total" },
                { data: "fecha_documento" },
                { data: "fecha_elaboracion" },
            ],
            columnDefs: [
                {
                    targets: 0,
                    render: function (data, type, row) {
                        return `
                            <button type="button" class="btn btn-sm btn-info badge view"
                                data-id="${row.idcompra}">
                                <i class="ri-eye-fill align-bottom"></i>
                            </button>
                            `
                    }
                }
            ],
            dom: "lBfrtip",
            buttons: [],
            responsive: false,
            scrollX: true,
            scrollCollapse: true,
            autoWidth: false,
            bDestroy: true,
            iDisplayLength: 10,
            order: [[7, "desc"]],
        });

        $('#tableCompras tbody').on('click', '.view', function () {
            const tr = $(this).closest('tr');
            const data = table.row(tr).data();
            const row = table.row(tr);   
            const subTableId = `subtable-${data.idcompra}`;       

            if (row.child.isShown()) {
                row.child.hide();
                tr.removeClass('shown');
            } else {
                row.child(`
                    <div class="p-3 bg-light border shadow-sm rounded">
                        <h6 class="fw-bold text-primary mb-3">Artículos de la Compra #${data.idcompra}</h6>
                        <table id="${subTableId}" class="table table-sm table-hover display nowrap" style="width:100%">
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th>Cantidad</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                    `).show();

                $(`#${subTableId}`).DataTable({
                    ajax: {
                        url: base_url + `/com_partidas/getPartidasByCompraId/${data.idcompra}`,
                        dataSrc: "data"
                    },
                    columns: [
                        { data: "cve_articulo" },
                        { data: "descripcion" },
                        { data: "cantidad" },
                        { 
                            data: "costo_unitario",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$') 
                        },
                        { 
                            data: "subtotal_partida",
                            render: $.fn.dataTable.render.number(',', '.', 2, '$') 
                        }
                    ],
                    dom: 't', // 't' means only show the table (no search/paging for sub-items)
                    paging: false,
                    info: false,
                    scrollX: true,
                    autoWidth: false
                });
            }
        });

        // Escuchar el cambio de pestañas
        const tabEl = document.querySelector('a[data-bs-toggle="tab"][href="#agregarcompra"]');

        if (tabEl) {
            tabEl.addEventListener('shown.bs.tab', function (event) {

                if (selectProveedor && selectProveedor.options.length <= 1) {
                    console.log("Cargando proveedores por primera vez...");
                    fntGetProveedores();
                }

                if (selectAlmacen && selectAlmacen.options.length <= 1) {
                    console.log("Cargando almacenes por primera vez...");
                    fntGetAlmacenes();
                }

                if (selectMoneda && selectMoneda.options.length <= 1) {
                    console.log("Cargando monedas por primera vez...");
                    fntGetMonedas();
                }
            });
        }

        const formCompras = document.querySelector("#formCompras");

        if (formCompras) {
            formCompras.addEventListener('submit', function (e) {
                e.preventDefault(); // Evita que la página se recargue
                fntSaveCompra();
            });
        }
    })

/**
 * 
 */
// function fntMostrarDetallePartidas(data) {debugger
//     return `
//         <div class="p-3 border-start border-4 border-info bg-light">
//             <h5>Detalles de Compra: ${data.idcompra}</h5>
//             <table class="table table-sm mb-0">
//                 <tr>
//                     <td><strong>Proveedor ID:</strong></td>
//                     <td>${data.proveedorid}</td>
//                     <td><strong>Fecha Elaboración:</strong></td>
//                     <td>${data.fecha_elaboracion}</td>
//                 </tr>
//                 <tr>
//                     <td><strong>Status:</strong></td>
//                     <td><span class="badge bg-success">${data.status}</span></td>
//                     <td><strong>Enlazado:</strong></td>
//                     <td>${data.enlazado}</td>
//                 </tr>
//             </table>
//         </div>`;
// }

/**
 * Carga el listado de proveedores en el select del formulario
 */
function fntGetProveedores() {
    const ajaxUrl = base_url + "/wms_proveedores/getProveedores";
    const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            try {
                const objData = JSON.parse(request.responseText);

                if (request.status == 200 && objData.status) {
                    let htmlOptions = '<option value="">Seleccione Proveedor...</option>';
                    objData.data.forEach(proveedores => {
                        if (proveedores.estado == 2) {
                            htmlOptions += `<option value="${proveedores.idproveedor}">${proveedores.razon_social}</option>`;
                        }
                    });
                    selectProveedor.innerHTML = htmlOptions;
                } else {
                    Swal.fire("Error", objData.msg || "No se pudieron cargar los proveedores", "error");
                }
            } catch (e) {
                console.error("Error parseando respuesta JSON:", e);
            }
        }
    };
}

/**
 * Carga el listado de almacenes en el select del formulario
 */
function fntGetAlmacenes() {
    const ajaxUrl = base_url + "/inv_almacenes/getAlmacenesJson";
    const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            try {
                const objData = JSON.parse(request.responseText);

                if (request.status == 200 && objData.status) {
                    let htmlOptions = '<option value="">Seleccione Almacén...</option>';
                    objData.data.forEach(almacenes => {
                        if (almacenes.estado == 2) {
                            htmlOptions += `<option value="${almacenes.idalmacen}">${almacenes.descripcion}</option>`;
                        }
                    });
                    selectAlmacen.innerHTML = htmlOptions;
                } else {
                    Swal.fire("Error", objData.msg || "No se pudieron cargar los almacenes", "error");
                }
            } catch (e) {
                console.error("Error parseando respuesta JSON:", e);
            }
        }
    };
}

/**
 * Carga el listado de monedas en el select del formulario
 */
function fntGetMonedas() {
    const ajaxUrl = base_url + "/wms_monedas/getMonedas";
    const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            try {
                const objData = JSON.parse(request.responseText);

                if (request.status == 200 && objData.status) {
                    let htmlOptions = '<option value="">Seleccione Moneda...</option>';
                    objData.data.forEach(monedas => {
                        if (monedas.estado == 2) {
                            htmlOptions += `<option value="${monedas.idmoneda}">${monedas.descripcion}</option>`;
                        }
                    });
                    selectMoneda.innerHTML = htmlOptions;
                } else {
                    Swal.fire("Error", objData.msg || "No se pudieron cargar las monedas", "error");
                }
            } catch (e) {
                console.error("Error parseando respuesta JSON:", e);
            }
        }
    };
}

/**
 * Carga el listado de monedas en el select del formulario
 */
function fntGetArticulos(selectElement) {
    const ajaxUrl = base_url + "/inv_inventario/getArticulos";
    const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            try {
                const objData = JSON.parse(request.responseText);

                if (request.status == 200 && objData.status) {
                    let htmlOptions = '<option value="">Seleccione Artículo...</option>';
                    objData.data.forEach(articulos => {
                        if (articulos.estado == 2) {
                            htmlOptions += `<option value="${articulos.idinventario}" data-costo="${articulos.ultimo_costo}">${articulos.cve_articulo} - ${articulos.descripcion}</option>`;
                        }
                    });
                    selectElement.innerHTML = htmlOptions;
                } else {
                    Swal.fire("Error", objData.msg || "No se pudieron cargar los artículos", "error");
                }
            } catch (e) {
                console.error("Error parseando respuesta JSON:", e);
            }
        }
    };
}

/**
 * Añade una nueva fila de artículo a la tabla
 */
function fntAddArticulo() {
    idFila++;
    const tr = document.createElement("tr");
    tr.id = `row_${idFila}`;
    tr.className = "text-center";
    
    tr.innerHTML = `
        <td>
            <select class="form-select select-articulo" name="articulo[]" onchange="fntGetInfoArticulo(this, ${idFila})" required>
                <option value="">Seleccione Clave...</option>
                </select>
        </td>
        <td>
            <input type="number" step="1" class="form-control text-end input-cantidad" 
                   name="cantidad[]" value="1" oninput="fntCalcularFila(${idFila})" min="1" max="1000" required>
        </td>
        <td>
            <input type="number" step="0.000001" class="form-control text-end input-costo" 
                   id="costo_${idFila}" name="costo[]" value="0.000000" oninput="fntCalcularFila(${idFila})" required>
        </td>
        <td>
            <input type="number" step="0.01" class="form-control text-end input-impuesto" 
                   name="impuesto[]" value="0.00" oninput="fntCalcularFila(${idFila})">
        </td>
        <td class="align-middle">
            <span class="fw-bold" id="txt_subtotal_${idFila}">$ 0.00</span>
            <input type="hidden" class="input-subtotal-partida" name="subtotal_partida[]" value="0">
        </td>
        <td>
            <button type="button" class="btn btn-soft-danger btn-icon waves-effect waves-light btn-sm" 
                    onclick="fntDelArticulo(${idFila})">
                <i class="ri-delete-bin-fill"></i>
            </button>
        </td>
    `;
    document.querySelector("#cuerpoDetalle").appendChild(tr);
    
    // Aquí podrías llamar a una función para llenar el select con los datos de wms_inventario
    fntGetArticulos(tr.querySelector(".select-articulo"));
}

function fntGetInfoArticulo(select, idFila) {

    const selectedOption = select.options[select.selectedIndex];
    
    const costo = selectedOption.dataset.costo || 0;

    const inputCosto = document.querySelector(`#costo_${idFila}`);

    if (inputCosto) {

        inputCosto.value = parseFloat(costo).toFixed(6);
        
        fntCalcularFila(idFila);
    }
}

/**
 * Elimina una fila y actualiza los totales
 */
function fntDelArticulo(id) {
    const row = document.querySelector(`#row_${id}`);
    if (row) {
        row.remove();
        fntActualizarTotales();
    }
}

/**
 * Calcula el subtotal de una sola fila
 */
function fntCalcularFila(id) {
    const row = document.querySelector(`#row_${id}`);
    const cant = parseFloat(row.querySelector(".input-cantidad").value) || 0;
    const costo = parseFloat(row.querySelector(".input-costo").value) || 0;
    const imp = parseFloat(row.querySelector(".input-impuesto").value) || 0;

    const subtotal = (cant * costo) + imp;
    
    row.querySelector(`#txt_subtotal_${id}`).innerText = `$ ${subtotal.toLocaleString('en-US', {minimumFractionDigits: 2})}`;
    row.querySelector(".input-subtotal-partida").value = subtotal;

    fntActualizarTotales();
}

/**
 * Suma todos los subtotales para el encabezado de la compra
 */
function fntActualizarTotales() {
    let totalGeneral = 0;
    document.querySelectorAll(".input-subtotal-partida").forEach(input => {
        totalGeneral += parseFloat(input.value) || 0;
    });

    // Actualizamos los campos de la cabecera que definimos antes
    document.querySelector("#importe").value = totalGeneral.toFixed(6);
    document.querySelector("#cantidad_total").value = totalGeneral.toFixed(6);
}

/**
 * Función principal para procesar el guardado
 */
function fntSaveCompra() {
    const form = document.querySelector("#formCompras");
    const filas = document.querySelectorAll("#cuerpoDetalle tr");

    // Recolección de datos de cabecera
    let formData = new FormData(form);

    // Recolección manual del detalle (Partidas)
    let detalle = [];
    filas.forEach(fila => {
        let item = {
            inventario: fila.querySelector(".select-articulo").value,
            cantidad: fila.querySelector(".input-cantidad").value,
            costo_unitario: fila.querySelector(".input-costo").value,
            impuesto: fila.querySelector(".input-impuesto").value,
            subtotal_partida: fila.querySelector(".input-subtotal-partida").value
        };
        detalle.push(item);
    });

    // Agregamos el detalle como un string JSON al FormData
    formData.append("detalle_partidas", JSON.stringify(detalle));

    // Configuración de la petición AJAX (XMLHttpRequest)
    const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    const ajaxUrl = base_url + "/com_compras/setCompra";

    request.open("POST", ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function() {
        if (request.readyState == 4) {
            try {
                const objData = JSON.parse(request.responseText);

                if (request.status == 201 || (request.status == 200 && objData.status)) {
                    // ÉXITO: Compra guardada
                    Swal.fire("Guardado", objData.msg, "success").then(() => {
                        fntResetModulo();
                    });
                } 
                else if (request.status == 422) {
                    // ERROR DE VALIDACIÓN (FormRequest)
                    // Mostramos los errores específicos que devolvió el backend
                    let msgError = "";
                    for (const campo in objData.errors) {
                        msgError += `<b>${campo}:</b> ${objData.errors[campo]}<br>`;
                    }
                    Swal.fire("Errores de Validación", msgError, "warning");
                } 
                else {
                    // OTROS ERRORES (500, 403, etc)
                    Swal.fire("Error del Sistema", objData.msg || "Ocurrió un error inesperado.", "error");
                }
            } catch (e) {
                Swal.fire("Error Crítico", "No se pudo procesar la respuesta del servidor.", "error");
                console.error("Respuesta cruda:", request.responseText);
            }
        }
    };
}

/**
 * Limpia el formulario y regresa a la pestaña de listado
 */
function fntResetModulo() {
    const form = document.querySelector("#formCompras");
    form.reset();
    form.classList.remove('was-validated');
    document.querySelector("#cuerpoDetalle").innerHTML = "";
    
    // Regresar a la pestaña de lista (usando Bootstrap JS)
    const firstTabEl = document.querySelector('#nav-tab li:first-child a');
    const firstTab = new bootstrap.Tab(firstTabEl);
    firstTab.show();

    // Recargar tabla de registros si existe
    const dt = $('#tableCompras').DataTable(); 
    if (dt) {
        dt.ajax.reload(null, false); // null = no callback, false = stay on current page
    }
}