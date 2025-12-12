let tableAlmacenes;
let tableDocumentos;
let divLoading = null;

// Inputs / elementos del formulario
let productoid = null;          
let idproducto_documentacion = null;   
let idproducto_descriptiva = null;
let inputiddescriptiva = null;
let idproducto_proceso = null;

// Referencias globales para tabs y botón
let primerTab = null;         // instancia bootstrap.Tab (LISTA)
let tabNuevo = null;          // <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null;      
let formConfigProd = null;           
let formDocumentacion = null; 
let formConfDescriptiva = null;

// NAVS INFERIORES 
let btnInfoGeneral = null;
let btnDocumentacion = null;
let btnDescriptiva = null;
let btnProcesos = null;
let btnFinalizado = null;

let rutaEstaciones = [];

document.addEventListener('DOMContentLoaded', function () {

    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading = document.querySelector("#divLoading");
    formConfigProd = document.querySelector("#formConfProducto");
    formDocumentacion = document.querySelector("#formDocumentacion");
    formConfDescriptiva = document.querySelector("#formConfDescriptiva");


    spanBtnText = document.querySelector('#btnText');

    productoid = document.querySelector('#idproducto');
    idproducto_documentacion = document.querySelector('#idproducto_documentacion');
    idproducto_descriptiva = document.querySelector('#idproducto_descriptiva');
    inputiddescriptiva = document.querySelector('#iddescriptiva');
    idproducto_proceso = document.querySelector('#idproducto_proceso');



    // DECLARACIÓN DE NAVS INFERIORES
    btnInfoGeneral = document.getElementById('tab-informacion-general');
    btnDocumentacion = document.getElementById('tab-documentacion');
    btnDescriptiva = document.getElementById('tab-descriptiva-tecnica');
    btnProcesos = document.getElementById('tab-procesos');;
    btnFinalizado = document.getElementById('tab-finalizado');

    // Estado inicial de las tabs inferiores 
    refreshLowerTabs();

    // --------------------------------------------------------------------
    //  CARGAR SELECTS (PRODUCTOS / LÍNEAS) - TAB INFORMACIÓN GENERAL
    // --------------------------------------------------------------------
    initTabInformacion();

    // --------------------------------------------------------------------
    //  DATATABLE DOCUMENTOS
    // --------------------------------------------------------------------
    const tableDocEl = document.querySelector('#tableDocumentos');

    if (!tableDocEl) {
        console.warn('tableDocumentos no encontrada en el DOM. No se inicializa DataTable de documentos.');
    } else {
        tableDocumentos = $(tableDocEl).DataTable({
            "aProcessing": true,
            "aServerSide": true,
            "ajax": {
                "url": base_url + "/Plan_confproductos/getDocumentos",
                "type": "POST",
                "data": function (d) {
 
                    d.idproducto_documentacion = idproducto_documentacion ? idproducto_documentacion.value : '';
                },
                "dataSrc": ""
            },
            "columns": [
                { "data": "tipo_documento" },
                { "data": "descripcion" },
                { "data": "documento" },
                { "data": "fecha_creacion" },
                { "data": "options" }
            ],
            'dom': 'lBfrtip',
            'buttons': [],
            "responsive": true,
            "bDestroy": true,
            "iDisplayLength": 10,
            "order": [[0, "desc"]]
        });
    }

    // Recargar documentos al entrar a la pestaña Documentación
    if (btnDocumentacion) {
        btnDocumentacion.addEventListener('click', function () {
            if (tableDocumentos) {
                tableDocumentos.ajax.reload();
            }
        });
    }

    if (btnDescriptiva) {
        btnDescriptiva.addEventListener('click', function () {
            loadDescriptivaForProducto();
        });
    }



    // --------------------------------------------------------------------
    //  DATATABLE PRODUCTOS
    // --------------------------------------------------------------------
    const tableCompEl = document.querySelector('#tableProductos');

    if (!tableCompEl) {
        console.warn('tableProductos no encontrada en el DOM. No se inicializa DataTable de componentes.');
    } else {
        tableAlmacenes = $(tableCompEl).DataTable({
            "aProcessing": true,
            "aServerSide": true, 
            "ajax": {
                "url": base_url + "/Plan_confproductos/getProductos",
                "dataSrc": ""
            },
            "columns": [
                { "data": "cve_producto" },
                { "data": "cve_articulo" },
                { "data": "descripcion_producto" },
                { "data": "cve_linea_producto" },
                { "data": "descripcion_linea" },
                { "data": "fecha_creacion" },
                { "data": "estado_producto" },
                { "data": "options" }
            ],
            'dom': 'lBfrtip',
            'buttons': [],
            "responsive": true,
            "bDestroy": true,
            "iDisplayLength": 10,
            "order": [[0, "desc"]]
        });
    }

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP (LISTA / NUEVO)
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector('#nav-tab a[href="#navListProductos"]');
    const firstTabEl = document.querySelector('#nav-tab a[href="#navAgregarProducto"]');

    if (primerTabEl && firstTabEl && spanBtnText) {
        primerTab = new bootstrap.Tab(primerTabEl); // LISTA
        tabNuevo = firstTabEl;                     // NUEVO / ACTUALIZAR

        // CLICK EN "NUEVO" → modo NUEVO
        tabNuevo.addEventListener('click', () => {
            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';

            if (productoid) productoid.value = '';
            if (idproducto_documentacion) idproducto_documentacion.value = '';
            if (idproducto_descriptiva) idproducto_descriptiva.value = '';
            if (idproducto_proceso) idproducto_proceso.value = '';






            if (formConfigProd) formConfigProd.reset();

            const selectProductos = document.querySelector('#listProductos');
            const selectLineasProductos = document.querySelector('#listLineasProductos');

            if (selectProductos) selectProductos.value = '';
            if (selectLineasProductos) selectLineasProductos.value = '';

            // Nuevo producto → bloquear pasos inferiores otra vez
            refreshLowerTabs();
            setInfoGeneralActive();
        });

        // CLICK EN "LISTA" → reset form
        primerTabEl.addEventListener('click', () => {
            if (productoid) productoid.value = '';
            if (idproducto_documentacion) idproducto_documentacion.value = '';
            if (idproducto_descriptiva) idproducto_descriptiva.value = '';
            if (idproducto_proceso) idproducto_proceso.value = '';



            tabNuevo.textContent = 'NUEVO';
            spanBtnText.textContent = 'REGISTRAR';

            if (formConfigProd) formConfigProd.reset();

            const selectProductos = document.querySelector('#listProductos');
            const selectLineasProductos = document.querySelector('#listLineasProductos');

            if (selectProductos) selectProductos.value = '';
            if (selectLineasProductos) selectLineasProductos.value = '';
            refreshLowerTabs();
        });
    } else {
        console.warn('Tabs de productos o btnText no encontrados.');
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM PARA AGREGAR / ACTUALIZAR PRODUCTOS
    // --------------------------------------------------------------------
    if (formConfigProd) {
        formConfigProd.addEventListener('submit', function (e) {
            e.preventDefault(); // evitar envío por URL

            if (divLoading) divLoading.style.display = "flex";

            let request = (window.XMLHttpRequest)
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');

            let ajaxUrl = base_url + '/Plan_confproductos/setProducto';
            let formData = new FormData(formConfigProd);

            request.open("POST", ajaxUrl, true);
            request.send(formData);

            request.onreadystatechange = function () {
                if (request.readyState !== 4) return;

                if (divLoading) divLoading.style.display = "none";

                if (request.status !== 200) {
                    Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
                    return;
                }

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    if (objData.tipo === 'insert') {

                        // Guardamos el id del producto en ambos inputs
                        if (productoid) productoid.value = objData.idproducto;
                        if (idproducto_documentacion) idproducto_documentacion.value = objData.idproducto;
                        if (idproducto_descriptiva) idproducto_descriptiva.value = objData.idproducto;
                        if (inputiddescriptiva) inputiddescriptiva.value = '0';

                        if (idproducto_proceso) idproducto_proceso.value = objData.idproducto;

                        refreshLowerTabs();

                        Swal.fire({
                            title: objData.msg,
                            text: 'A continuación, se procederá con la carga de la documentación del producto.',
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3545',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {

                            if (tableAlmacenes) tableAlmacenes.ajax.reload();

                            if (btnDocumentacion) {
                                const tabDoc = new bootstrap.Tab(btnDocumentacion);
                                tabDoc.show();
                            }

                            formConfigProd.reset();

                            const selectProductos = document.querySelector('#listProductos');
                            const selectLineasProductos = document.querySelector('#listLineasProductos');

                            if (selectProductos) selectProductos.value = '';
                            if (selectLineasProductos) selectLineasProductos.value = '';

                            if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
                            if (tabNuevo) tabNuevo.textContent = 'NUEVO';

                            if (!result.isConfirmed && primerTab) {
                                primerTab.show();
                            }
                        });

                    } else {
                        // caso UPDATE 
                        if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';
                        if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
                        Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    }

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            };
        });
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM PARA GUARDAR LOS DOCUMENTOS
    // --------------------------------------------------------------------
    if (formDocumentacion) {
        formDocumentacion.addEventListener('submit', function (e) {
            e.preventDefault(); // evitar envío por URL

            if (divLoading) divLoading.style.display = "flex";

            let request = (window.XMLHttpRequest)
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');

            let ajaxUrl = base_url + '/Plan_confproductos/setDocumentacion';
            let formData = new FormData(formDocumentacion);

            request.open("POST", ajaxUrl, true);
            request.send(formData);

            request.onreadystatechange = function () {
                if (request.readyState !== 4) return;

                if (divLoading) divLoading.style.display = "none";

                if (request.status !== 200) {
                    Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
                    return;
                }

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    if (objData.tipo === 'insert') {

                        if (tableDocumentos) tableDocumentos.ajax.reload();

                        refreshLowerTabs();

                        Swal.fire({
                            title: '¡Operación exitosa!',
                            text: objData.msg,
                            icon: 'success',
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3545',
                            allowOutsideClick: false,
                            allowEscapeKey: false
                        }).then((result) => {

                            formDocumentacion.reset();
                            if (idproducto_documentacion) idproducto_documentacion.value = objData.idproducto;

                        });

                    } else {
                        // caso UPDATE docs
                    }

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            };
        });
    }



    // --------------------------------------------------------------------
    //  SUBMIT FORM PARA GUARDAR LA DESCRIPTIVA
    // --------------------------------------------------------------------
    if (formConfDescriptiva) {
        formConfDescriptiva.addEventListener('submit', function (e) {
            e.preventDefault(); // evitar envío por URL

            if (divLoading) divLoading.style.display = "flex";

            let request = (window.XMLHttpRequest)
                ? new XMLHttpRequest()
                : new ActiveXObject('Microsoft.XMLHTTP');

            let ajaxUrl = base_url + '/Plan_confproductos/setDescriptiva';
            let formData = new FormData(formConfDescriptiva);

            request.open("POST", ajaxUrl, true);
            request.send(formData);

            request.onreadystatechange = function () {
                if (request.readyState !== 4) return;

                if (divLoading) divLoading.style.display = "none";

                if (request.status !== 200) {
                    Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
                    return;
                }

                let objData = JSON.parse(request.responseText);

                if (objData.status) {

                    if (objData.tipo === 'insert') {

                         if (inputiddescriptiva) inputiddescriptiva.value = objData.iddescriptiva;
if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';
                        
                        Swal.fire("¡Operación exitosa!", objData.msg, "success");

                    } else {
                        

                        Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    }

                } else {
                    Swal.fire("Error", objData.msg, "error");
                }
            };
        });
    }






}, false);

// ------------------------------------------------------------------------
//  INIT TAB INFORMACIÓN GENERAL (CARGA SELECTS)
// ------------------------------------------------------------------------
function initTabInformacion() {
    fntInventarios();
    fntLineasProducto();
    fntPlantas();
}


// ------------------------------------------------------------------------
//  CARGAR DESCRIPTIVA TÉCNICA SI YA EXISTE
// ------------------------------------------------------------------------
function loadDescriptivaForProducto() {
    if (!formConfDescriptiva) return;

    const btnSubmitDes = formConfDescriptiva.querySelector('button[type="submit"]');
    if (!inputiddescriptiva || !inputiddescriptiva.value.trim()) {
        resetDescriptivaSinHidden();
        if (btnSubmitDes) btnSubmitDes.textContent = 'REGISTRAR';
        return;
    }

    const idProd = inputiddescriptiva.value.trim();

    let ajaxUrl = base_url + '/Plan_confproductos/getDescriptiva/' + idProd;
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        if (request.status !== 200) {
            console.error("Error en getDescriptiva:", request.status);
            resetDescriptivaSinHidden();
            if (btnSubmitDes) btnSubmitDes.textContent = 'REGISTRAR';
            return;
        }

        let objData;
        try {
            objData = JSON.parse(request.responseText);
        } catch (e) {
            console.error("JSON inválido:", e);
            resetDescriptivaSinHidden();
            if (btnSubmitDes) btnSubmitDes.textContent = 'REGISTRAR';
            return;
        }

        console.log('getDescriptiva response:', objData);

        let d = null;

        if (Array.isArray(objData) && objData.length > 0) {
            d = objData[0];   
        }

        if (!d) {
            resetDescriptivaSinHidden();
            if (btnSubmitDes) btnSubmitDes.textContent = 'REGISTRAR';
            return;
        }

        //  existe descriptiva → llenar form
        const inputMarca = formConfDescriptiva.querySelector('#txtMarca');
        const inputModelo = formConfDescriptiva.querySelector('#txtModelo');
        const inputLargoTotal = formConfDescriptiva.querySelector('#txtLargoTotal');
        const inputDistanciaEjes = formConfDescriptiva.querySelector('#txtDistanciaEjes');
        const inputPesoBrutoVehicular = formConfDescriptiva.querySelector('#txtPesoBruto');
        const inputMotor = formConfDescriptiva.querySelector('#txtMotor');
        const inputCilindros = formConfDescriptiva.querySelector('#txtDesplazamientoCilindros');
        const inputDesplazamiento = formConfDescriptiva.querySelector('#txtDesplazamiento');
        const inputTipoCombustible = formConfDescriptiva.querySelector('#txtTipoCombustible');
        const inputPotencia = formConfDescriptiva.querySelector('#txtPotencia');
        const inputTorque = formConfDescriptiva.querySelector('#txtTorque');
        const inputTransmision = formConfDescriptiva.querySelector('#txtTransmision');
        const inputEjeDelantero = formConfDescriptiva.querySelector('#txtEjeDelantero');
        const inputSuspDelantera = formConfDescriptiva.querySelector('#txtSuspensionDelantera');
        const inputEjeTrasero = formConfDescriptiva.querySelector('#txtEjeTrasero');
        const inputSuspTrasera = formConfDescriptiva.querySelector('#txtSuspensionTrasera');
        const inputLlantas = formConfDescriptiva.querySelector('#txtLlantas');
        const inputSistemaFrenos = formConfDescriptiva.querySelector('#txtSistemaFrenos');
        const inputAsistencias = formConfDescriptiva.querySelector('#txtAsistencias');
        const inputSistemaElectrico = formConfDescriptiva.querySelector('#txtSistemaElectrico');
        const inputCapCombustible = formConfDescriptiva.querySelector('#txtCapacidadCombustible');
        const inputDireccion = formConfDescriptiva.querySelector('#txtDireccion');
        const inputEquipamiento = formConfDescriptiva.querySelector('#txtEquipamiento');

        if (inputMarca) inputMarca.value = d.marca ?? '';
        if (inputModelo) inputModelo.value = d.modelo ?? '';
        if (inputLargoTotal) inputLargoTotal.value = d.largo_total ?? '';
        if (inputDistanciaEjes) inputDistanciaEjes.value = d.distancia_ejes ?? '';
        if (inputPesoBrutoVehicular) inputPesoBrutoVehicular.value = d.peso_bruto_vehicular ?? '';
        if (inputMotor) inputMotor.value = d.motor ?? '';
        if (inputCilindros) inputCilindros.value = d.cilindros ?? '';
        if (inputDesplazamiento) inputDesplazamiento.value = d.desplazamiento_c ?? '';
        if (inputTipoCombustible) inputTipoCombustible.value = d.tipo_combustible ?? '';
        if (inputPotencia) inputPotencia.value = d.potencia ?? '';
        if (inputTorque) inputTorque.value = d.torque ?? '';
        if (inputTransmision) inputTransmision.value = d.transmision ?? '';
        if (inputEjeDelantero) inputEjeDelantero.value = d.eje_delantero ?? '';
        if (inputSuspDelantera) inputSuspDelantera.value = d.suspension_delantera ?? '';
        if (inputEjeTrasero) inputEjeTrasero.value = d.eje_trasero ?? '';
        if (inputSuspTrasera) inputSuspTrasera.value = d.suspension_trasera ?? '';
        if (inputLlantas) inputLlantas.value = d.llantas ?? '';
        if (inputSistemaFrenos) inputSistemaFrenos.value = d.sistema_frenos ?? '';
        if (inputAsistencias) inputAsistencias.value = d.asistencias ?? '';
        if (inputSistemaElectrico) inputSistemaElectrico.value = d.sistema_electrico ?? '';
        if (inputCapCombustible) inputCapCombustible.value = d.capacidad_combustible ?? '';
        if (inputDireccion) inputDireccion.value = d.direccion ?? '';
        if (inputEquipamiento) inputEquipamiento.value = d.equipamiento ?? '';

        // Botón en modo ACTUALIZAR
        if (btnSubmitDes) btnSubmitDes.textContent = 'ACTUALIZAR';
    };
}



function resetDescriptivaSinHidden() {
    if (!formConfDescriptiva) return;

    // Guardar los valores ANTES de hacer reset
    const valProducto   = idproducto_descriptiva ? idproducto_descriptiva.value : '';
    const valDescriptiva = inputiddescriptiva ? inputiddescriptiva.value : '';

    formConfDescriptiva.reset();

    if (idproducto_descriptiva) idproducto_descriptiva.value = valProducto;
    if (inputiddescriptiva)     inputiddescriptiva.value = valDescriptiva;
}





// ------------------------------------------------------------------------
//  VER EL CATÁLOGO DE PRODUCTOS
// ------------------------------------------------------------------------
function fntInventarios(selectedValue = "") {
    const selectProductos = document.querySelector('#listProductos');
    if (!selectProductos) return;

    let ajaxUrl = base_url + '/Plan_confproductos/getSelectProductos';
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            selectProductos.innerHTML = request.responseText;

            if (selectedValue !== "") {
                selectProductos.value = selectedValue;
            }
        }
    };

    selectProductos.addEventListener('change', function () {
        const idProducto = this.value;
        if (idProducto !== "") {
            fntInventarioDetalle(idProducto);
        }
    });
}

// ------------------------------------------------------------------------
//  DETALLE DEL INVENTARIO SELECCIONADO
// ------------------------------------------------------------------------
function fntInventarioDetalle(idInventario) {
    let ajaxUrl = base_url + '/Plan_confproductos/getSelectInventario/' + idInventario;
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let descripcion = objData.descripcion;
            let lineaproducto = objData.lineaproductoid;

            let inputDescripcion = document.getElementById("txtDescripcion");
            let selectLineasProductos = document.getElementById("listLineasProductos");

            if (inputDescripcion) inputDescripcion.value = descripcion;
            if (selectLineasProductos) selectLineasProductos.value = lineaproducto;
        }
    };
}

// ------------------------------------------------------------------------
//  VER EL CATÁLOGO DE LÍNEAS DE PRODUCTO
// ------------------------------------------------------------------------
function fntLineasProducto(selectedValue = "") {
    const selectLineasProductos = document.querySelector('#listLineasProductos');
    if (!selectLineasProductos) return;

    let ajaxUrl = base_url + '/Plan_confproductos/getSelectLineasProductos';
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            selectLineasProductos.innerHTML = request.responseText;

            if (selectedValue !== "") {
                selectLineasProductos.value = selectedValue;
            }
        }
    };
}

// ------------------------------------------------------------------------
//  CONTROL DE HABILITAR / DESHABILITAR TABS INFERIORES
// ------------------------------------------------------------------------
function refreshLowerTabs() {
    const hasProducto = idproducto_documentacion && idproducto_documentacion.value.trim() !== '';

    if (btnInfoGeneral) btnInfoGeneral.disabled = false; // siempre disponible
    if (btnDocumentacion) btnDocumentacion.disabled = !hasProducto;
    if (btnDescriptiva) btnDescriptiva.disabled = !hasProducto;
    if (btnProcesos) btnProcesos.disabled = !hasProducto;
    if (btnFinalizado) btnFinalizado.disabled = !hasProducto;

}

function setInfoGeneralActive() {
    if (!btnInfoGeneral) return;

    const tabInfo = new bootstrap.Tab(btnInfoGeneral);
    tabInfo.show();
}

// ------------------------------------------------------------------------
//  ELIMINAR UN DOCUMENTO POR PRODUCTO
// ------------------------------------------------------------------------
function fntDelDocumento(iddocumento) {

    Swal.fire({
        html: `
        <div class="mt-3">
            <lord-icon 
                src="https://cdn.lordicon.com/gsqxdxog.json" 
                trigger="loop" 
                colors="primary:#f7b84b,secondary:#f06548" 
                style="width:100px;height:100px">
            </lord-icon>

            <div class="mt-4 pt-2 fs-15 mx-5">
                <h4>Confirmar eliminación</h4>
                <p class="text-muted mx-4 mb-0">
                    ¿Estás seguro de que deseas eliminar este registro? 
                    Esta acción no se puede deshacer.
                </p>
            </div>
        </div>
    `,
        showCancelButton: true,
        confirmButtonText: "Sí, eliminar",
        cancelButtonText: "Cancelar",
        customClass: {
            confirmButton: "btn btn-primary w-xs me-2 mb-1",
            cancelButton: "btn btn-danger w-xs mb-1"
        },
        buttonsStyling: false,
        showCloseButton: true
    }).then((result) => {

        if (!result.isConfirmed) {
            return;
        }

        let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
        let ajaxUrl = base_url + '/Plan_confproductos/delDocumento';
        let strData = "iddocumento=" + iddocumento;

        request.open("POST", ajaxUrl, true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {
            if (request.readyState == 4 && request.status == 200) {
                let objData = JSON.parse(request.responseText);
                if (objData.status) {
                    Swal.fire("¡Operación exitosa!", objData.msg, "success");
                    if (tableDocumentos) tableDocumentos.ajax.reload();
                } else {
                    Swal.fire("Atención!", objData.msg, "error");
                }
            }
        }

    });

}

// ------------------------------------------------------------------------
//  EDITAR PRODUCTO
// ------------------------------------------------------------------------
function fntEditProducto(idproducto) {
    if (!idproducto) return;

    if (divLoading) divLoading.style.display = "flex";

    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    let ajaxUrl = base_url + '/Plan_confproductos/getProducto/' + idproducto;

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState != 4) return;

        if (divLoading) divLoading.style.display = "none";

        if (request.status != 200) {
            Swal.fire("Error", "Error al consultar el PRODUCTO.", "error");
            return;
        }

        let objData = JSON.parse(request.responseText);

        if (!objData.status) {
            Swal.fire("Aviso", objData.msg || "No se encontró la información del producto.", "warning");
            return;
        }

        // Datos reales del producto
        let data = objData.data || objData;

        //  Abrir la pestaña "NUEVO" (navAgregarProducto)
        const tabAgregarEl = document.querySelector('#nav-tab a[href="#navAgregarProducto"]');
        if (tabAgregarEl) {
            const tab = new bootstrap.Tab(tabAgregarEl);
            tab.show();
            tabNuevo = tabAgregarEl; // actualizamos la referencia global por si acaso
        }

        if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
        if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';

        // 3) Setear los IDs ocultosFD
        if (productoid) productoid.value = data.idproducto;
        if (idproducto_documentacion) idproducto_documentacion.value = data.idproducto;
        if (inputiddescriptiva) inputiddescriptiva.value = data.iddescriptiva;

        // Habilitar tabs inferiores porque ya hay producto
        refreshLowerTabs();

        const selectProductos = document.querySelector('#listProductos');
        const selectLineasProductos = document.querySelector('#listLineasProductos');
        const inputDescripcion = document.querySelector('#txtDescripcion');
        const selectEstado = document.querySelector('#intEstado');

        if (selectProductos && data.inventarioid) {
            selectProductos.value = data.inventarioid;
        }

        if (inputDescripcion && data.descripcion) {
            inputDescripcion.value = data.descripcion;
        }

        if (selectLineasProductos && data.lineaproductoid) {
            selectLineasProductos.value = data.lineaproductoid;
        }

        if (selectEstado && data.estado) {
            selectEstado.value = data.estado;
        }
        setInfoGeneralActive();
    };
}

 

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PLANTAS
// ------------------------------------------------------------------------
function fntPlantas(selectedValue = "") {
    const selectPlantasLocal = document.querySelector('#listPlantasSelect');
    const selectLineasLocal = document.querySelector('#listLineasSelect');

    if (!selectPlantasLocal) return;

    let ajaxUrl = base_url + '/Cap_plantas/getSelectPlantas';
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState === 4 && request.status === 200) {
            selectPlantasLocal.innerHTML = request.responseText;

            if (selectedValue !== "") {
                selectPlantasLocal.value = selectedValue;
                // Cargar líneas para esa planta
                fntLineas(selectPlantasLocal.value);
            }
        }
    };

    if (!selectPlantasLocal.dataset.bound) {
        selectPlantasLocal.addEventListener('change', function () {
            const idPlanta = this.value;
            if (selectLineasLocal) {
                selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
            }
            // Llamar líneas
            fntLineas(idPlanta);
        });
        selectPlantasLocal.dataset.bound = '1';
    }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE LÍNEAS
// ------------------------------------------------------------------------
function fntLineas(idPlanta, selectedLinea = "") {
    const selectLineasLocal = document.querySelector('#listLineasSelect');

    if (!selectLineasLocal) return;

    if (!idPlanta) {
        selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
        return;
    }

    let ajaxUrl = base_url + '/Cap_lineasdtrabajo/getSelectLineas/' + idPlanta;
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState === 4 && request.status === 200) {
            selectLineasLocal.innerHTML = request.responseText;


            if (selectedLinea !== "") {
                selectLineasLocal.value = selectedLinea;
            }

        
            const idLineaActual = selectLineasLocal.value;
            if (idLineaActual) {
                fntEstaciones(idLineaActual);
            } else {
                fntEstaciones("");
            }
        }
    };

    // Solo agregar el change UNA vez
    if (!selectLineasLocal.dataset.bound) {
        selectLineasLocal.addEventListener('change', function () {
            const idLinea = this.value;
            fntEstaciones(idLinea);
        });
        selectLineasLocal.dataset.bound = '1';
    }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE ESTACIONES POR LÍNEA
// ------------------------------------------------------------------------
function fntEstaciones(idLinea, selectedEstacion = "") {
    const selectEstaciones = document.querySelector('#listEstacionesSelect'); // opcional
    const listaEstaciones = document.querySelector('#listaEstaciones');
    const badgeCount = document.querySelector('#countEstacionesDisponibles');
    const msgSinEstaciones = document.querySelector('#mensajeSinEstaciones');

    if (!listaEstaciones || !badgeCount) return;

    if (!idLinea) {
        listaEstaciones.innerHTML = '';
        badgeCount.textContent = '0';
        if (msgSinEstaciones) msgSinEstaciones.classList.remove('d-none');

        if (selectEstaciones) {
            selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';
        }
        return;
    }

    let ajaxUrl = base_url + '/Plan_confproductos/getSelectEstaciones/' + idLinea;
    let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

    request.open("GET", ajaxUrl, true);
    request.send();

    request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        if (request.status === 200) {
            let estaciones = [];

            try {
                estaciones = JSON.parse(request.responseText);
            } catch (e) {
                console.error('Respuesta JSON inválida en estaciones:', e);
                return;
            }

            listaEstaciones.innerHTML = '';

            if (selectEstaciones) {
                selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';
            }

            if (!Array.isArray(estaciones) || estaciones.length === 0) {
                badgeCount.textContent = '0';
                if (msgSinEstaciones) msgSinEstaciones.classList.remove('d-none');
                return;
            }

            badgeCount.textContent = estaciones.length.toString();
            if (msgSinEstaciones) msgSinEstaciones.classList.add('d-none');

            estaciones.forEach(est => {
                const textConcatenado = `${est.cve_estacion} - ${est.nombre_estacion}`;

                if (selectEstaciones) {
                    const option = document.createElement('option');
                    option.value = est.idestacion;
                    option.textContent = textConcatenado;

                    if (selectedEstacion && selectedEstacion == est.idestacion) {
                        option.selected = true;
                    }

                    selectEstaciones.appendChild(option);
                }

                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
                item.setAttribute('data-idestacion', est.idestacion);
                item.setAttribute('data-cve', est.cve_estacion);
                item.setAttribute('data-nombre', est.nombre_estacion);
                item.setAttribute('draggable', 'true');

                item.innerHTML = `
                    <div class="text-start">
                        <div class="fw-semibold">${est.cve_estacion}</div>
                        <small class="text-muted">${est.nombre_estacion}</small>
                    </div>
                `;

                // DRAG: comenzar arrastre
                item.addEventListener('dragstart', function (ev) {
                    ev.dataTransfer.setData('text/plain', est.idestacion);
                });

                // CLICK: misma lógica que drag & drop
                item.addEventListener('click', function () {
                    agregarEstacionARuta(
                        {
                            idestacion: est.idestacion,
                            cve_estacion: est.cve_estacion,
                            nombre_estacion: est.nombre_estacion
                        },
                        item
                    );
                });

                listaEstaciones.appendChild(item);
            });
        } else {
            console.error('Error al consultar estaciones. Status:', request.status);
        }
    };
}


// ------------------------------------------------------------------------
//  AGREGAR ESTACIÓN A LA RUTA (por clic o drop)
// ------------------------------------------------------------------------
function agregarEstacionARuta(est, botonOrigen) {
    const listaRuta = document.querySelector('#listaRuta');
    const placeholderRuta = document.querySelector('#placeholderRuta');

    if (!listaRuta) return;

    const idEstacion = est.idestacion.toString();

    // Evitar duplicados
    if (rutaEstaciones.includes(idEstacion)) {
        return;
    }

    // Agregamos temporalmente al arreglo (después se recalcula en actualizarHiddenRuta)
    rutaEstaciones.push(idEstacion);

    // Ocultamos el placeholder porque ya hay al menos una estación
    if (placeholderRuta) {
        placeholderRuta.classList.add('d-none');
    }

    // Crear el item visual para la ruta
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';
    li.setAttribute('data-idestacion', idEstacion);

    const indexVisual = rutaEstaciones.length;

    li.innerHTML = `
        <div class="d-flex align-items-center gap-2">
            <span class="badge text-bg-primary badge-step">${indexVisual}</span>
            <div>
                <div class="fw-semibold">${est.cve_estacion}</div>
                <small class="text-muted">${est.nombre_estacion}</small>
            </div>
        </div>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" onclick="moverArriba(this)">
                <i class="ri-arrow-up-s-line fs-16"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="moverAbajo(this)">
                <i class="ri-arrow-down-s-line fs-16"></i>
            </button>
            <button type="button" class="btn btn-outline-danger" onclick="eliminarDeRuta(this)">
                <i class="ri-delete-bin-5-fill fs-16"></i>
            </button>
        </div>
    `;

    // Agregamos el item a la lista de la ruta
    listaRuta.appendChild(li);

    // Inhabilitar el botón de la lista de estaciones disponibles
    if (botonOrigen) {
        botonOrigen.disabled = true;
        botonOrigen.classList.add('disabled', 'opacity-50');
    }

    // Recalcular arreglo, índices, contador e input hidden
    actualizarHiddenRuta();
}


// ------------------------------------------------------------------------
//  REMOVER ESTACIÓN DE LA RUTA
// ------------------------------------------------------------------------
function removerEstacionDeRuta(idEstacion) {
    const listaRuta = document.querySelector('#listaRuta');
    const inputRuta = document.querySelector('#ruta_estaciones');

    if (!listaRuta || !inputRuta) return;

    const idStr = idEstacion.toString();

    // Quitar del arregloO
    rutaEstaciones = rutaEstaciones.filter(id => id !== idStr);

    // Quitar el <li> correspondient
    const li = listaRuta.querySelector(`li[data-idestacion="${idStr}"]`);
    if (li) {
        listaRuta.removeChild(li);
    }

    // Re-habilitar el botó en la lista de estaciones disponibles
    const listaEstaciones = document.querySelector('#listaEstaciones');
    if (listaEstaciones) {
        const btnOrigen = listaEstaciones.querySelector(`button[data-idestacion="${idStr}"]`);
        if (btnOrigen) {
            btnOrigen.disabled = false;
            btnOrigen.classList.remove('disabled', 'opacity-50');
        }
    }

    //Renumerar los badges de orden (1,2,3,...) después de quitar
    renumerarRuta();

    //  Actualizar contador y hidden
    actualizarResumenRuta();
}


// ------------------------------------------------------------------------
//  RENUMERAR ORDEN VISUAL DE LA RUTA
// ------------------------------------------------------------------------
function renumerarRuta() {
    const listaRuta = document.querySelector('#listaRuta');
    if (!listaRuta) return;

    const items = listaRuta.querySelectorAll('li');
    let index = 1;
    items.forEach(li => {
        const badge = li.querySelector('.badge');
        if (badge) {
            badge.textContent = index;
        }
        index++;
    });
}


// ------------------------------------------------------------------------
//  ACTUALIZAR CONTADOR, PLACEHOLDER E INPUT HIDDEN
// ------------------------------------------------------------------------
function actualizarResumenRuta() {
    const countRuta = document.querySelector('#countRuta');
    const placeholderRuta = document.querySelector('#placeholderRuta');
    const listaRuta = document.querySelector('#listaRuta');
    const inputRuta = document.querySelector('#ruta_estaciones');

    if (!countRuta || !listaRuta || !inputRuta) return;

    const total = rutaEstaciones.length;

    // Contador del badge
    countRuta.textContent = total.toString();

    // Mostrar / ocultar placeholder
    if (placeholderRuta) {
        if (total === 0) {
            placeholderRuta.classList.remove('d-none');
        } else {
            placeholderRuta.classList.add('d-none');
        }
    }

    // Guardar IDs en el input hidden (como lista separada por comas)
    inputRuta.value = rutaEstaciones.join(',');
}

// ------------------------------------------------------------------------
//  MOVER ESTACIÓN HACIA ARRIBA EN LA RUTA
// ------------------------------------------------------------------------
function moverArriba(btn) {
    const li = btn.closest('li');
    const prev = li.previousElementSibling;
    if (prev) {
        li.parentNode.insertBefore(li, prev);
        actualizarHiddenRuta();
    }
}

// ------------------------------------------------------------------------
//  MOVER ESTACIÓN HACIA ABAJO EN LA RUTA
// ------------------------------------------------------------------------
function moverAbajo(btn) {
    const li = btn.closest('li');
    const next = li.nextElementSibling;
    if (next) {
        li.parentNode.insertBefore(next, li);
        actualizarHiddenRuta();
    }
}

// ------------------------------------------------------------------------
//  ELIMINAR ESTACIÓN DE LA RUTA
// ------------------------------------------------------------------------
function eliminarDeRuta(btn) {
    const li = btn.closest('li');
    const idEstacion = li.getAttribute('data-idestacion');

    // Eliminar el li del DOM
    li.parentNode.removeChild(li);

    // Re-habilitar el botón en la lista de estaciones disponibles
    const listaEstaciones = document.querySelector('#listaEstaciones');
    if (listaEstaciones) {
        const btnOrigen = listaEstaciones.querySelector(`button[data-idestacion="${idEstacion}"]`);
        if (btnOrigen) {
            btnOrigen.disabled = false;
            btnOrigen.classList.remove('disabled', 'opacity-50');
        }
    }

    // Recalcular arreglo, índices, contador e hidden
    actualizarHiddenRuta();
}

// ------------------------------------------------------------------------
//  ACTUALIZAR ARREGLO, CONTADOR, PLACEHOLDER E INPUT HIDDEN
// ------------------------------------------------------------------------
function actualizarHiddenRuta() {
    const listaRuta = document.querySelector('#listaRuta');
    const countRuta = document.querySelector('#countRuta');
    const placeholderRuta = document.querySelector('#placeholderRuta');
    const inputRuta = document.querySelector('#ruta_estaciones');

    if (!listaRuta || !countRuta || !inputRuta) return;

    // Reconstruir arreglo rutaEstaciones según el orden actual en el DOM
    rutaEstaciones = [];
    const items = listaRuta.querySelectorAll('li[data-idestacion]');
    items.forEach(li => {
        const idEstacion = li.getAttribute('data-idestacion');
        rutaEstaciones.push(idEstacion);
    });

    // Actualizar contador
    const total = rutaEstaciones.length;
    countRuta.textContent = total.toString();

    // Mostrar / ocultar placeholder
    if (placeholderRuta) {
        if (total === 0) {
            placeholderRuta.classList.remove('d-none');
        } else {
            placeholderRuta.classList.add('d-none');
        }
    }

    // Actualizar input hidden con los IDs en orden
    inputRuta.value = rutaEstaciones.join(',');

    // Renumerar badges (1,2,3,...) de los paso
    actualizarIndicesRuta();
}

// ------------------------------------------------------------------------
//  RENUMERAR ÍNDICES VISUALES (BADGE)
// ------------------------------------------------------------------------
function actualizarIndicesRuta() {
    const listaRuta = document.querySelector('#listaRuta');
    if (!listaRuta) return;

    const items = listaRuta.querySelectorAll('li[data-idestacion]');
    let index = 1;
    items.forEach(li => {
        const badge = li.querySelector('.badge-step');
        if (badge) {
            badge.textContent = index;
        }
        index++;
    });
}





// ------------------------------------------------------------------------
//  PERMITIR DROP SOBRE LA RUTA
// ------------------------------------------------------------------------
function allowDrop(ev) {
    ev.preventDefault(); 
    const dropRuta = document.querySelector('#dropRuta');
    if (dropRuta) {
        dropRuta.classList.add('dropzone-hover');
    }
}

// ------------------------------------------------------------------------
//  QUITAR ESTILO AL SALIR DEL DROPZONE
// ------------------------------------------------------------------------
function dragLeaveRuta(ev) {
    const dropRuta = document.querySelector('#dropRuta');
    if (dropRuta) {
        dropRuta.classList.remove('dropzone-hover');
    }
}

// ------------------------------------------------------------------------
//  MANEJAR EL DROP DE UNA ESTACIÓN EN LA RUTA
// ------------------------------------------------------------------------
function dropOnRuta(ev) {
    ev.preventDefault();

    const dropRuta = document.querySelector('#dropRuta');
    if (dropRuta) {
        dropRuta.classList.remove('dropzone-hover');
    }

    const idEstacion = ev.dataTransfer.getData('text/plain');
    if (!idEstacion) return;

    const btnOrigen = document.querySelector(
        `#listaEstaciones button[data-idestacion="${idEstacion}"]`
    );

    if (!btnOrigen) {
        console.warn('No se encontró el botón origen para la estación: ', idEstacion);
        return;
    }

    const est = {
        idestacion: idEstacion,
        cve_estacion: btnOrigen.getAttribute('data-cve'),
        nombre_estacion: btnOrigen.getAttribute('data-nombre')
    };

    // Reutilizamos la misma lógica de click
    agregarEstacionARuta(est, btnOrigen);
}

