/*****************************************************************************************
 *  PLAN_CONFPRODUCTOS.JS (COMPLETO + ORDENADO + FIX FINAL DE RUTA)
 *  - Carga ruta existente: pinta estaciones en #listaRuta (tbody) en orden y bloquea #listaEstaciones
 *  - Fix: funciones de hidden/indices trabajaban con <li> pero tu ruta es <tr>
 *  - Mantiene TODO lo que me pasaste integrado (documentos, descriptiva, procesos, especificaciones,
 *    componentes, herramientas, datatables, etc.)
 ******************************************************************************************/

// ---------------------------------------------
//  GLOBALES
// ---------------------------------------------
let tableAlmacenes;
let tableDocumentos;
let divLoading = null;

let dtCatalogHerramientas = null;
let dtCatalogComponentes = null;

// Inputs / elementos del formulario
let productoid = null;
let idproducto_documentacion = null;
let idproducto_descriptiva = null;
let inputiddescriptiva = null;
let idproducto_proceso = null;
let idproducto_especificacion = null;
let idespecificacioninput = null;

let id_ruta_producto = null;

// Referencias globales para tabs y botón
let primerTab = null;
let tabNuevo = null;
let spanBtnText = null;
let formConfigProd = null;
let formDocumentacion = null;
let formConfDescriptiva = null;
let formRuta = null;
let formEspecificaciones = null;

// NAVS INFERIORES
let btnInfoGeneral = null;
let btnDocumentacion = null;
let btnDescriptiva = null;
let btnProcesos = null;
let btnFinalizado = null;

// RUTA (FIX FINAL)
let rutaEstaciones = [];           // ids en orden (string)
let rutaDetallePendiente = [];     // detalle que viene del backend
let aplicoRutaPendiente = false;   // evita re-aplicar varias veces

let tableEspecifica = null;
let estacionActual = 0;

let dtSelectedComponentes = null;
let componentesSeleccionados = []; // [{inventarioid, name, type, unit, cve, cantidad}]

let dtSelectedHerramientas = null;
let herramientasSeleccionadas = []; // [{inventarioid, name, type, unit, cve, cantidad}]


document.addEventListener('DOMContentLoaded', function () {

    initTablaSeleccionadosHerramientas();
prepararEventosCatalogoHerramientas();
prepararGuardarTodoHerramientas();

  // --------------------------------------------------------------------
  //  REFERENCIAS BÁSICAS
  // --------------------------------------------------------------------
  divLoading = document.querySelector("#divLoading");
  formConfigProd = document.querySelector("#formConfProducto");
  formDocumentacion = document.querySelector("#formDocumentacion");
  formConfDescriptiva = document.querySelector("#formConfDescriptiva");
  formRuta = document.querySelector('#formRutaProducto');
  formEspecificaciones = document.querySelector('#formEspecificaciones');

  spanBtnText = document.querySelector('#btnText');

  productoid = document.querySelector('#idproducto');
  idproducto_documentacion = document.querySelector('#idproducto_documentacion');
  idproducto_descriptiva = document.querySelector('#idproducto_descriptiva');
  inputiddescriptiva = document.querySelector('#iddescriptiva');
  idproducto_proceso = document.querySelector('#idproducto_proceso');
  idproducto_especificacion = document.querySelector('#idproducto_especificacion');
  id_ruta_producto = document.querySelector('#id_ruta_producto');
  idespecificacioninput = document.querySelector('#idespecificacion');

  // NAVS inferiores
  btnInfoGeneral = document.getElementById('tab-informacion-general');
  btnDocumentacion = document.getElementById('tab-documentacion');
  btnDescriptiva = document.getElementById('tab-descriptiva-tecnica');
  btnProcesos = document.getElementById('tab-procesos');
  btnFinalizado = document.getElementById('tab-finalizado');

  refreshLowerTabs();

  // --------------------------------------------------------------------
  //  INIT TAB INFORMACIÓN GENERAL (CARGA SELECTS)
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

  // --------------------------------------------------------------------
  //  TABLA SELECCIONADOS COMPONENTES
  // --------------------------------------------------------------------
  initTablaSeleccionadosComponentes();
  prepararEventosCatalogoComponentes();
  prepararGuardarTodoComponentes();

  // --------------------------------------------------------------------
  //  Recargar documentos / cargar descriptiva / cargar procesos
  // --------------------------------------------------------------------
  if (btnDocumentacion) {
    btnDocumentacion.addEventListener('click', function () {
      if (tableDocumentos) tableDocumentos.ajax.reload();
    });
  }

  if (btnDescriptiva) {
    btnDescriptiva.addEventListener('click', function () {
      loadDescriptivaForProducto();
    });
  }

  if (btnProcesos) {
    btnProcesos.addEventListener('click', function () {
      loadProcesoForProducto();
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
    primerTab = new bootstrap.Tab(primerTabEl);
    tabNuevo = firstTabEl;

    tabNuevo.addEventListener('click', () => {
      tabNuevo.textContent = 'NUEVO';
      spanBtnText.textContent = 'REGISTRAR';

      if (productoid) productoid.value = '';
      if (idproducto_documentacion) idproducto_documentacion.value = '';
      if (idproducto_descriptiva) idproducto_descriptiva.value = '';
      if (idproducto_proceso) idproducto_proceso.value = '';
      if (idproducto_especificacion) idproducto_especificacion.value = '';
      if (inputiddescriptiva) inputiddescriptiva.value = '0';
      if (id_ruta_producto) id_ruta_producto.value = '';

      if (formConfigProd) formConfigProd.reset();

      const selectProductos = document.querySelector('#listProductos');
      const selectLineasProductos = document.querySelector('#listLineasProductos');
      if (selectProductos) selectProductos.value = '';
      if (selectLineasProductos) selectLineasProductos.value = '';

      // reset ruta UI
      resetRutaUI();
      rutaDetallePendiente = [];
      aplicoRutaPendiente = false;

      refreshLowerTabs();
      setInfoGeneralActive();
    });

    primerTabEl.addEventListener('click', () => {
      if (productoid) productoid.value = '';
      if (idproducto_documentacion) idproducto_documentacion.value = '';
      if (idproducto_descriptiva) idproducto_descriptiva.value = '';
      if (idproducto_proceso) idproducto_proceso.value = '';
      if (idproducto_especificacion) idproducto_especificacion.value = '';
      if (inputiddescriptiva) inputiddescriptiva.value = '0';
      if (id_ruta_producto) id_ruta_producto.value = '';

      tabNuevo.textContent = 'NUEVO';
      spanBtnText.textContent = 'REGISTRAR';

      if (formConfigProd) formConfigProd.reset();

      const selectProductos = document.querySelector('#listProductos');
      const selectLineasProductos = document.querySelector('#listLineasProductos');
      if (selectProductos) selectProductos.value = '';
      if (selectLineasProductos) selectLineasProductos.value = '';

      // reset ruta UI
      resetRutaUI();
      rutaDetallePendiente = [];
      aplicoRutaPendiente = false;

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
      e.preventDefault();

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

            if (productoid) productoid.value = objData.idproducto;
            if (idproducto_documentacion) idproducto_documentacion.value = objData.idproducto;
            if (idproducto_descriptiva) idproducto_descriptiva.value = objData.idproducto;
            if (inputiddescriptiva) inputiddescriptiva.value = '0';
            if (idproducto_proceso) idproducto_proceso.value = objData.idproducto;
            if (idproducto_especificacion) idproducto_especificacion.value = objData.idproducto;

            // ruta nueva: limpiar
            if (id_ruta_producto) id_ruta_producto.value = '';
            resetRutaUI();
            rutaDetallePendiente = [];
            aplicoRutaPendiente = false;

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

              if (!result.isConfirmed && primerTab) primerTab.show();
            });



                // EN ESTA PARTE COLOCAMOS LA CLAVE DEL PRODUCTO Y MODELO 

let clave_producto = objData.clave;
let descripcion_producto = objData.descripcion;


document.querySelectorAll('.producto_clave').forEach(span => {
  if (clave_producto) {
    span.textContent = 'ID: ' + clave_producto;
  }
});


document.querySelectorAll('.descripcion_producto').forEach(span => {
  if (descripcion_producto) {
    span.textContent = descripcion_producto;
  }
});







          } else {
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
  //  SUBMIT FORM PARA GUARDAR LA DESCRIPTIVA
  // --------------------------------------------------------------------
  if (formConfDescriptiva) {
    formConfDescriptiva.addEventListener('submit', function (e) {
      e.preventDefault();

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

  // --------------------------------------------------------------------
  //  SUBMIT FORM RUTA PRODUCTO
  // --------------------------------------------------------------------
  if (formRuta) {
    formRuta.addEventListener('submit', function (e) {
      e.preventDefault();

      const payload = construirPayloadRuta();
      const d = payload[0];

      if (!d.listPlantasSelect || !d.listLineasSelect) {
        console.error('Faltan datos: planta/linea/producto');
        Swal.fire("Atención", "Selecciona Planta y Línea.", "warning");
        return;
      }
      if (!d.detalle_ruta || d.detalle_ruta.length === 0) {
        console.error('No hay estaciones en la ruta');
        Swal.fire("Atención", "Agrega estaciones a la ruta.", "warning");
        return;
      }

      const formData = new FormData(formRuta);
      formData.append('ruta', JSON.stringify(payload));

      fetch(base_url + '/Plan_confproductos/setRutaProducto', {
        method: 'POST',
        body: formData
      })
        .then(r => r.json())
        .then(res => {
          if (res.status) {
            Swal.fire({
              icon: 'success',
              title: '¡Operación exitosa!',
              text: res.msg || 'La ruta del producto fue guardada correctamente',
              confirmButtonText: 'Aceptar'
            });

            const inputRuta = document.querySelector('#id_ruta_producto');
            if (inputRuta && res.idruta) inputRuta.value = res.idruta;

          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: res.msg || 'No se pudo guardar la ruta',
              confirmButtonText: 'Aceptar'
            });
          }
        })
        .catch(err => console.error(err));
    });
  }

  // --------------------------------------------------------------------
  //  SUBMIT FORM PARA GUARDAR LOS DOCUMENTOS
  // --------------------------------------------------------------------
  if (formDocumentacion) {
    formDocumentacion.addEventListener('submit', function (e) {
      e.preventDefault();

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
            }).then(() => {
              formDocumentacion.reset();
              if (idproducto_documentacion) idproducto_documentacion.value = objData.idproducto;
            });

          } else {
            // update docs si lo ocupas
          }
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      };
    });
  }

  // --------------------------------------------------------------------
  //  SUBMIT FORM ESPECIFICACIONES
  // --------------------------------------------------------------------
  if (formEspecificaciones) {
    formEspecificaciones.addEventListener('submit', function (e) {
      e.preventDefault();

      if (divLoading) divLoading.style.display = "flex";

      let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

      let ajaxUrl = base_url + '/Plan_confproductos/setEspecificacion';
      let formData = new FormData(formEspecificaciones);

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

            if (tableEspecifica) tableEspecifica.ajax.reload(null, false);
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
            }).then(() => {
              // estos IDs están en tu HTML, si existen se limpian:
              const txtEspecificacion = document.querySelector('#txtEspecificacion');
              if (txtEspecificacion) txtEspecificacion.value = '';
              const btnTextEsp = document.querySelector('#btnTextEspecificacion');
              if (btnTextEsp) btnTextEsp.innerHTML = "Registrar";
            });

          } else {
            if (tableEspecifica) tableEspecifica.ajax.reload(null, false);

            Swal.fire("¡Operación exitosa!", objData.msg, "success");

            const txtEspecificacion = document.querySelector('#txtEspecificacion');
            if (txtEspecificacion) txtEspecificacion.value = '';
            if (idespecificacioninput) idespecificacioninput.value = 0;
            const btnTextEsp = document.querySelector('#btnTextEspecificacion');
            if (btnTextEsp) btnTextEsp.innerHTML = "Registrar";
          }

        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      };
    });
  }

  // --------------------------------------------------------------------
  //  DATATABLES CATALOGOS (Herramientas / Componentes)
  // --------------------------------------------------------------------
  dtCatalogHerramientas = new DataTable('#tblCatalogHerramientas', {
    data: [],
    deferRender: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[0, 'asc']],
    autoWidth: false,
    language: { url: "https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json" },
    columns: [
      { data: 'id' },
      {
        data: 'name',
        render: (data, type, row) => `
          <div class="fw-semibold">${data}</div>
          <small class="text-muted mono">CVE: ${row.cve || ''}</small>
        `
      },
      { data: 'type' },
      { data: 'unit' },
      {
        data: null,
        className: 'text-end',
        orderable: false,
        searchable: false,
        render: (data, type, row) => `
          <button class="btn btn-outline-primary btn-sm btn-add" data-herramientaid="${row.herramientaid}">
            Agregar
          </button>
        `
      }
    ]
  });

  dtCatalogComponentes = new DataTable('#tblCatalogComponentes', {
    data: [],
    deferRender: true,
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    order: [[0, 'asc']],
    autoWidth: false,
    language: { url: "https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json" },
    columns: [
      { data: 'id' },
      {
        data: 'name',
        render: (data, type, row) => `
          <div class="fw-semibold">${data}</div>
          <small class="text-muted mono">CVE: ${row.cve || ''}</small>
        `
      },
      { data: 'stock' },
      { data: 'type' },
      { data: 'unit' },
      {
        data: null,
        className: 'text-end',
        orderable: false,
        searchable: false,
        render: (data, type, row) => `
          <button type="button"
                  class="btn btn-outline-primary btn-sm btn-add"
                  data-inventarioid="${row.inventarioid}">
            Agregar
          </button>
        `
      }
    ]
  });

}, false);


// ------------------------------------------------------------------------
//  INIT TAB INFORMACIÓN GENERAL
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

    let d = (Array.isArray(objData) && objData.length > 0) ? objData[0] : null;
    if (!d) {
      resetDescriptivaSinHidden();
      if (btnSubmitDes) btnSubmitDes.textContent = 'REGISTRAR';
      return;
    }

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

    if (btnSubmitDes) btnSubmitDes.textContent = 'ACTUALIZAR';
  };
}

// ------------------------------------------------------------------------
//  CARGAR RUTA SI YA EXISTE (FIX FINAL)
// ------------------------------------------------------------------------
function loadProcesoForProducto() {
  if (!formRuta) return;

  const btnSubmit = formRuta.querySelector('button[type="submit"]');

  if (!id_ruta_producto || !id_ruta_producto.value.trim()) {
    resetDescriptivaSinHidden();
    if (btnSubmit) btnSubmit.textContent = 'REGISTRAR';
    resetRutaUI();
    return;
  }

  const idRutaProd = id_ruta_producto.value.trim();
  const ajaxUrl = base_url + '/Plan_confproductos/getRuta/' + idRutaProd;

  let request = (window.XMLHttpRequest)
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    if (request.status !== 200) {
      console.error("Error en getRuta:", request.status);
      resetDescriptivaSinHidden();
      if (btnSubmit) btnSubmit.textContent = 'REGISTRAR';
      resetRutaUI();
      return;
    }

    let objData;
    try {
      objData = JSON.parse(request.responseText);
    } catch (e) {
      console.error("JSON inválido:", e);
      resetDescriptivaSinHidden();
      if (btnSubmit) btnSubmit.textContent = 'REGISTRAR';
      resetRutaUI();
      return;
    }

        if (objData.status === false) {
  resetDescriptivaSinHidden();
  resetRutaCompleta();
  if (btnSubmit) btnSubmit.textContent = 'REGISTRAR';
  return;
}

    const d = (Array.isArray(objData) && objData.length > 0) ? objData[0] : null;

    if (!d) {
      resetDescriptivaSinHidden();
      if (btnSubmit) btnSubmit.textContent = 'REGISTRAR';
      resetRutaUI();
      return;
    }




    const listPlanta = formRuta.querySelector('#listPlantasSelect');
    const inputProd = document.querySelector('#idproducto_proceso');

    const plantaId = String(d.listPlantasSelect ?? '').trim();
    const lineaId = String(d.listLineasSelect ?? '').trim();

    if (listPlanta) listPlanta.value = plantaId;
    if (inputProd) inputProd.value = String(d.idproducto_proceso ?? '').trim();

    rutaDetallePendiente = Array.isArray(d.detalle_ruta) ? d.detalle_ruta : [];
    rutaDetallePendiente = [...rutaDetallePendiente].sort((a, b) => Number(a.orden) - Number(b.orden));
    aplicoRutaPendiente = false;

    // esto dispara: fntLineas -> fntEstaciones -> aplicarRutaPendienteSiExiste()
    fntLineas(plantaId, lineaId);

    if (btnSubmit) btnSubmit.textContent = 'ACTUALIZAR';
  };
}

function resetRutaCompleta() {
  const listPlanta = document.querySelector('#listPlantasSelect');
  const listLinea  = document.querySelector('#listLineasSelect');

  if (listPlanta) listPlanta.value = '';
  if (listLinea)  listLinea.value  = '';

  // Limpia estaciones disponibles
  const listaEstaciones = document.querySelector('#listaEstaciones');
  if (listaEstaciones) listaEstaciones.innerHTML = '';

  // Limpia ruta asignada
  const listaRuta = document.querySelector('#listaRuta');
  if (listaRuta) listaRuta.innerHTML = '';

  // Resetea flags
  rutaDetallePendiente = [];
  aplicoRutaPendiente = false;
}


function resetDescriptivaSinHidden() {
  if (!formConfDescriptiva) return;

  const valProducto = idproducto_descriptiva ? idproducto_descriptiva.value : '';
  const valDescriptiva = inputiddescriptiva ? inputiddescriptiva.value : '';

  formConfDescriptiva.reset();

  if (idproducto_descriptiva) idproducto_descriptiva.value = valProducto;
  if (inputiddescriptiva) inputiddescriptiva.value = valDescriptiva;
}

// ------------------------------------------------------------------------
//  SELECT PRODUCTOS
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

      if (selectedValue !== "") selectProductos.value = selectedValue;
    }
  };

  selectProductos.addEventListener('change', function () {
    const idProducto = this.value;
    if (idProducto !== "") fntInventarioDetalle(idProducto);
  });
}

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
      if (selectedValue !== "") selectLineasProductos.value = selectedValue;
    }
  };
}

// ------------------------------------------------------------------------
//  TABS INFERIORES
// ------------------------------------------------------------------------
function refreshLowerTabs() {
  const hasProducto = idproducto_documentacion && idproducto_documentacion.value.trim() !== '';

  if (btnInfoGeneral) btnInfoGeneral.disabled = false;
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
//  ELIMINAR DOCUMENTO
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

    if (!result.isConfirmed) return;

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

    let data = objData.data || objData;

    const tabAgregarEl = document.querySelector('#nav-tab a[href="#navAgregarProducto"]');
    if (tabAgregarEl) {
      const tab = new bootstrap.Tab(tabAgregarEl);
      tab.show();
      tabNuevo = tabAgregarEl;
    }

    if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
    if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';

    if (productoid) productoid.value = data.idproducto;
    if (idproducto_documentacion) idproducto_documentacion.value = data.idproducto;
    if (inputiddescriptiva) inputiddescriptiva.value = data.iddescriptiva;
    if (idproducto_proceso) idproducto_proceso.value = data.idproducto;
    if (idproducto_especificacion) idproducto_especificacion.value = data.idproducto;
    if (id_ruta_producto) id_ruta_producto.value = data.idruta_producto;

    refreshLowerTabs();

    const selectProductos = document.querySelector('#listProductos');
    const selectLineasProductos = document.querySelector('#listLineasProductos');
    const inputDescripcion = document.querySelector('#txtDescripcion');
    const selectEstado = document.querySelector('#intEstado');

    if (selectProductos && data.inventarioid) selectProductos.value = data.inventarioid;
    if (inputDescripcion && data.descripcion) inputDescripcion.value = data.descripcion;
    if (selectLineasProductos && data.lineaproductoid) selectLineasProductos.value = data.lineaproductoid;
    if (selectEstado && data.estado) selectEstado.value = data.estado;


    // EN ESTA PARTE COLOCAMOS LA CLAVE DEL PRODUCTO Y MODELO 

let clave_producto = data.cve_producto;
let descripcion_producto = data.descripcion;


document.querySelectorAll('.producto_clave').forEach(span => {
  if (clave_producto) {
    span.textContent = 'ID: ' + clave_producto;
  }
});


document.querySelectorAll('.descripcion_producto').forEach(span => {
  if (descripcion_producto) {
    span.textContent = descripcion_producto;
  }
});





  

    // al entrar, deja info general activa
    setInfoGeneralActive();
  };
}

// ------------------------------------------------------------------------
//  PLANTAS / LÍNEAS / ESTACIONES
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
        fntLineas(selectPlantasLocal.value);
      }
    }
  };

  if (!selectPlantasLocal.dataset.bound) {
    selectPlantasLocal.addEventListener('change', function () {
      const idPlanta = this.value;
      if (selectLineasLocal) selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
      fntLineas(idPlanta);
    });
    selectPlantasLocal.dataset.bound = '1';
  }
}

function fntLineas(idPlanta, selectedLinea = "") {
  const selectLineasLocal = document.querySelector('#listLineasSelect');
  if (!selectLineasLocal) return;

  if (!idPlanta) {
    selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
    fntEstaciones("");
    return;
  }

  let ajaxUrl = base_url + '/Cap_lineasdtrabajo/getSelectLineas/' + idPlanta;
  let request = (window.XMLHttpRequest)
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    if (request.status === 200) {
      selectLineasLocal.innerHTML = request.responseText;

      const sel = String(selectedLinea ?? "").trim();

      if (sel !== "") {
        selectLineasLocal.value = sel;

        const existe = Array.from(selectLineasLocal.options)
          .some(opt => String(opt.value).trim() === sel);

        if (!existe) selectLineasLocal.value = "";
      } else {
        selectLineasLocal.value = "";
      }

      const idLineaActual = selectLineasLocal.value;
      fntEstaciones(idLineaActual || "");
    } else {
      selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
      fntEstaciones("");
    }
  };

  if (!selectLineasLocal.dataset.bound) {
    selectLineasLocal.addEventListener('change', function () {
      fntEstaciones(this.value || "");
    });
    selectLineasLocal.dataset.bound = '1';
  }
}

// ---------------------------------------------
//  ESTACIONES (con hook para aplicar ruta)
// ---------------------------------------------
function fntEstaciones(idLinea, selectedEstacion = "") {
  const selectEstaciones = document.querySelector('#listEstacionesSelect');
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

    // limpia ruta si no hay línea
    resetRutaUI();
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
        resetRutaUI();
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

          if (selectedEstacion && selectedEstacion == est.idestacion) option.selected = true;
          selectEstaciones.appendChild(option);
        }

        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
        item.setAttribute('data-idestacion', String(est.idestacion));
        item.setAttribute('data-cve', est.cve_estacion);
        item.setAttribute('data-nombre', est.nombre_estacion);
        item.setAttribute('data-herramientas', String(est.herramientas ?? 0));
        item.setAttribute('draggable', 'true');

        item.innerHTML = `
          <div class="text-start">
            <div class="fw-semibold">${est.cve_estacion}</div>
            <small class="text-muted">${est.nombre_estacion}</small>
          </div>
        `;

        item.addEventListener('dragstart', function (ev) {
          ev.dataTransfer.setData('text/plain', String(est.idestacion));
        });

        item.addEventListener('click', function () {
          agregarEstacionARuta({
            idestacion: est.idestacion,
            cve_estacion: est.cve_estacion,
            nombre_estacion: est.nombre_estacion,
            herramientas: est.herramientas
          }, item);
        });

        listaEstaciones.appendChild(item);
      });

      // ✅ aquí ya existe DOM de estaciones -> aplicar ruta del backend (si existe)
      aplicarRutaPendienteSiExiste();

    } else {
      console.error('Error al consultar estaciones. Status:', request.status);
    }
  };
}


// ======================================================================
//  FIX FINAL RUTA: aplicar detalle del backend + bloqueo estaciones
// ======================================================================
function aplicarRutaPendienteSiExiste() {
  if (aplicoRutaPendiente) return;
  if (!Array.isArray(rutaDetallePendiente) || rutaDetallePendiente.length === 0) return;

  const lista = document.querySelector('#listaEstaciones');
  if (!lista) return;

  resetRutaUI();

  rutaDetallePendiente
    .sort((a, b) => Number(a.orden) - Number(b.orden))
    .forEach(item => {
      const idEst = String(item.idestacion).trim();
      const btnOrigen = lista.querySelector(`button[data-idestacion="${CSS.escape(idEst)}"]`);
      if (!btnOrigen) return;

      const est = {
        idestacion: idEst,
        cve_estacion: btnOrigen.getAttribute('data-cve') || '',
        nombre_estacion: btnOrigen.getAttribute('data-nombre') || '',
        herramientas: Number(btnOrigen.getAttribute('data-herramientas') || 0)
      };

      agregarEstacionARuta(est, btnOrigen);
    });

  aplicoRutaPendiente = true;
}

function resetRutaUI() {
  const tbody = document.querySelector('#listaRuta');
  if (tbody) tbody.innerHTML = '';

  rutaEstaciones = [];

  actualizarPlaceholderRuta();
  actualizarCountRuta();
  actualizarInputHiddenRuta();
}

// ------------------------------------------------------------------------
//  AGREGAR ESTACIÓN A LA RUTA (TABLA: <tr>)
// ------------------------------------------------------------------------
function agregarEstacionARuta(est, botonOrigen) {
  const tbody = document.querySelector('#listaRuta');
  if (!tbody) return;

  const idEstacion = String(est.idestacion).trim();
  if (!idEstacion) return;

  if (rutaEstaciones.includes(idEstacion)) return;

  rutaEstaciones.push(idEstacion);

  const tr = document.createElement('tr');
  tr.setAttribute('data-idestacion', idEstacion);

  const btnHerramientas = (Number(est.herramientas) === 1)
    ? `
      <button type="button"
              class="btn btn-outline-success btn-sm"
              onclick="abrirHerramientas(${idEstacion},'${est.cve_estacion}')"
              title="Asignar herramientas">
        <i class="ri-tools-line"></i>
        <span class="d-none d-md-inline">Herramientas</span>
      </button>
    `
    : `<span class="text-muted small">N/A</span>`;

  tr.innerHTML = `
    <td><span class="badge text-bg-primary badge-step">1</span></td>

    <td>
      <div class="fw-semibold">${est.cve_estacion || ''}</div>
      <small class="text-muted">${est.nombre_estacion || ''}</small>
    </td>

    <td style="width: 150px!important;">
      <button type="button" class="btn btn-outline-info btn-sm"
        onclick="abrirEspecificaciones(${idEstacion},'${est.cve_estacion}')" title="Asignar especificaciones">
        <i class="ri-settings-3-line"></i>
        <span class="d-none d-md-inline">Especificaciones</span>
      </button>
    </td>

    <td>
      <button type="button" class="btn btn-outline-primary btn-sm"
        onclick="abrirComponentes(${idEstacion},'${est.cve_estacion}')" title="Asignar componentes">
        <i class="ri-settings-3-line"></i>
        <span class="d-none d-md-inline">Componentes</span>
      </button>
    </td>

    <td>${btnHerramientas}</td>

    <td class="text-end">
      <div class="btn-group btn-group-sm">
        <button type="button" class="btn btn-outline-secondary" onclick="moverArriba(this)" title="Subir">
          <i class="ri-arrow-up-s-line"></i>
        </button>
        <button type="button" class="btn btn-outline-secondary" onclick="moverAbajo(this)" title="Bajar">
          <i class="ri-arrow-down-s-line"></i>
        </button>
        <button type="button" class="btn btn-outline-danger" onclick="eliminarDeRuta(this)" title="Eliminar">
          <i class="ri-delete-bin-5-fill"></i>
        </button>
      </div>
    </td>
  `;

  tbody.appendChild(tr);

  if (botonOrigen) {
    tr.dataset.btnOrigenId = idEstacion;
    bloquearBotonEstacion(botonOrigen);
  } else {
    setEstacionDisponibleEnLista(idEstacion, false);
  }

  reindexarRutaVisual();
  actualizarPlaceholderRuta();
  actualizarCountRuta();
  actualizarInputHiddenRuta();
}

function bloquearBotonEstacion(btn) {
  btn.disabled = true;
  btn.classList.add('disabled', 'opacity-50');
  btn.style.pointerEvents = 'none';
  btn.setAttribute('draggable', 'false');
}

function desbloquearBotonEstacionPorId(idEstacion) {
  const lista = document.querySelector('#listaEstaciones');
  if (!lista) return;

  const btn = lista.querySelector(`button[data-idestacion="${CSS.escape(String(idEstacion))}"]`);
  if (!btn) return;

  btn.disabled = false;
  btn.classList.remove('disabled', 'opacity-50');
  btn.style.pointerEvents = '';
  btn.setAttribute('draggable', 'true');
}

function setEstacionDisponibleEnLista(idEstacion, disponible) {
  const lista = document.querySelector('#listaEstaciones');
  if (!lista) return;

  const btn = lista.querySelector(`button[data-idestacion="${CSS.escape(String(idEstacion))}"]`);
  if (!btn) return;

  if (disponible) desbloquearBotonEstacionPorId(idEstacion);
  else bloquearBotonEstacion(btn);
}

function actualizarPlaceholderRuta() {
  const tbody = document.querySelector('#listaRuta');
  const placeholder = document.querySelector('#placeholderRuta');
  if (!placeholder || !tbody) return;

  const hayFilas = tbody.querySelectorAll('tr').length > 0;
  placeholder.classList.toggle('d-none', hayFilas);
}

function actualizarCountRuta() {
  const tbody = document.querySelector('#listaRuta');
  const countRuta = document.querySelector('#countRuta');
  if (!tbody || !countRuta) return;

  countRuta.textContent = String(tbody.querySelectorAll('tr').length);
}

function actualizarInputHiddenRuta() {
  const inputRuta = document.querySelector('#ruta_estaciones');
  const tbody = document.querySelector('#listaRuta');
  if (!inputRuta || !tbody) return;

  const filas = Array.from(tbody.querySelectorAll('tr[data-idestacion]'));
  rutaEstaciones = filas.map(tr => String(tr.getAttribute('data-idestacion')).trim()).filter(Boolean);
  inputRuta.value = rutaEstaciones.join(',');
}

function reindexarRutaVisual() {
  const tbody = document.querySelector('#listaRuta');
  if (!tbody) return;

  const filas = Array.from(tbody.querySelectorAll('tr'));
  filas.forEach((tr, idx) => {
    const badge = tr.querySelector('.badge-step');
    if (badge) badge.textContent = String(idx + 1);
  });
}

function moverArriba(btn) {
  const tr = btn.closest('tr');
  const tbody = document.querySelector('#listaRuta');
  if (!tr || !tbody) return;

  const prev = tr.previousElementSibling;
  if (!prev) return;

  tbody.insertBefore(tr, prev);

  reindexarRutaVisual();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}

function moverAbajo(btn) {
  const tr = btn.closest('tr');
  const tbody = document.querySelector('#listaRuta');
  if (!tr || !tbody) return;

  const next = tr.nextElementSibling;
  if (!next) return;

  tbody.insertBefore(next, tr);

  reindexarRutaVisual();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}

function eliminarDeRuta(btn) {
  const tr = btn.closest('tr');
  if (!tr) return;

  const id = String(tr.getAttribute('data-idestacion') || '').trim();
  if (id) desbloquearBotonEstacionPorId(id);

  tr.remove();

  reindexarRutaVisual();
  actualizarPlaceholderRuta();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}


// ------------------------------------------------------------------------
//  DRAG & DROP SOBRE RUTA
// ------------------------------------------------------------------------
function allowDrop(ev) {
  ev.preventDefault();
  const dropRuta = document.querySelector('#dropRuta');
  if (dropRuta) dropRuta.classList.add('dropzone-hover');
}

function dragLeaveRuta(ev) {
  const dropRuta = document.querySelector('#dropRuta');
  if (dropRuta) dropRuta.classList.remove('dropzone-hover');
}

function dropOnRuta(ev) {
  ev.preventDefault();

  const dropRuta = document.querySelector('#dropRuta');
  if (dropRuta) dropRuta.classList.remove('dropzone-hover');

  const idEstacion = ev.dataTransfer.getData('text/plain');
  if (!idEstacion) return;

  const btnOrigen = document.querySelector(`#listaEstaciones button[data-idestacion="${CSS.escape(String(idEstacion))}"]`);
  if (!btnOrigen) {
    console.warn('No se encontró el botón origen para la estación: ', idEstacion);
    return;
  }

  const est = {
    idestacion: idEstacion,
    cve_estacion: btnOrigen.getAttribute('data-cve') || '',
    nombre_estacion: btnOrigen.getAttribute('data-nombre') || '',
    herramientas: Number(btnOrigen.getAttribute('data-herramientas') || 0)
  };

  agregarEstacionARuta(est, btnOrigen);
}


// ======================================================================
//  ESPECIFICACIONES (MODAL + DATATABLE)
// ======================================================================
function abrirEspecificaciones(idestacion, cve_estacion) {
  const modal = document.getElementById('modalEspecificaciones');
  const inputIdEstacion = modal ? modal.querySelector('#idestacion') : null;
  if (inputIdEstacion) inputIdEstacion.value = idestacion || '';
  document.querySelector('#titleModalEspecificaciones').innerHTML = "Especificaciones - " + cve_estacion;
  $('#modalEspecificaciones').modal('show');
  cargarEspecificaciones(idestacion);
}

function cargarEspecificaciones(idEstacion) {
  estacionActual = parseInt(idEstacion || 0);
  if (!estacionActual) return;

  if (tableEspecifica) {
    tableEspecifica.ajax.url(base_url + '/Plan_confproductos/getEspecificaciones/' + estacionActual).load();
    return;
  }

  tableEspecifica = $('#tableEspecificaciones').DataTable({
    responsive: true,
    processing: true,
    serverSide: false,
    destroy: true,
    ajax: {
      url: base_url + '/Plan_confproductos/getEspecificaciones/' + estacionActual,
      dataSrc: function (json) {
        return (json && json.status && Array.isArray(json.data)) ? json.data : [];
      }
    },
    columns: [
      { data: 'idespecificacion' },
      { data: 'especificacion' },
      { data: 'fecha_creacion' },
      { data: 'options', orderable: false, searchable: false }
    ],
    order: [[1, 'desc']]
  });
}

function fntDelEspecificacion(idespecificacion) {
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

    if (!result.isConfirmed) return;

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Plan_confproductos/delEspecificacion';
    let strData = "idespecificacion=" + idespecificacion;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableEspecifica) tableEspecifica.ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    }
  });
}

function fntEditEspecificacion(idespecificacion) {
  const btnTextEsp = document.querySelector('#btnTextEspecificacion');
  if (btnTextEsp) btnTextEsp.innerHTML = "Actualizar";

  let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  let ajaxUrl = base_url + '/Plan_confproductos/getEspecificacion/' + idespecificacion;
  request.open("GET", ajaxUrl, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);
      if (objData.status) {
        document.querySelector("#idespecificacion").value = objData.data.idespecificacion;
        document.querySelector("#txtEspecificacion").value = objData.data.especificacion;
      } else {
        swal("Error", objData.msg, "error");
      }
    }
  }
}


// ======================================================================
//  COMPONENTES (MODAL + CARGA + GUARDADO)
// ======================================================================
function abrirComponentes(idestacion, cve_estacion) {

  const inputEstacion = document.querySelector('#estacion_id');
  if (inputEstacion) inputEstacion.value = idestacion;

  if (typeof idproducto_proceso !== 'undefined' && idproducto_proceso) {
    const inputComponentes = document.querySelector('#componentes_producto');
    if (inputComponentes) inputComponentes.value = idproducto_proceso.value;
  }

  resetModalComponentes();

  fntAlmacenesComponentes();

  document.querySelector('#titleModalComponentes').innerHTML = "Componentes - " + cve_estacion;

  $('#modalComponentes').modal('show');

  cargarComponentesGuardadosEstacion(idestacion);
}

function resetModalComponentes() {
  const sel = document.querySelector('#listAlmaceneSCompSelect');
  if (sel) sel.value = '';

  componentesSeleccionados = [];

  if (dtCatalogComponentes) dtCatalogComponentes.clear().draw(false);
  if (dtSelectedComponentes) dtSelectedComponentes.clear().draw(false);

  const count = document.querySelector('#countSelected');
  if (count) count.textContent = '0';

//   const saveBar = document.querySelector('#saveBar');
//   if (saveBar) saveBar.classList.add('d-none');

  const btnGuardar = document.querySelector('#btnGuardarTodo');
  if (btnGuardar) btnGuardar.textContent = 'Guardar todo';
}

function fntAlmacenesHerramientas(selectedValue = "") {
  const selectAlmacenesLocal = document.querySelector('#listAlmacenesHerrSelect');
  if (!selectAlmacenesLocal) return;

  let ajaxUrl = base_url + '/Plan_confproductos/getSelectAlmacenes';
  let request = (window.XMLHttpRequest)
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectAlmacenesLocal.innerHTML = request.responseText;

      if (selectedValue !== "") {
        selectAlmacenesLocal.value = selectedValue;
        fntLineas(selectAlmacenesLocal.value);
      }
    }
  };

  if (!selectAlmacenesLocal.dataset.bound) {
    selectAlmacenesLocal.addEventListener('change', function () {
      const idAlmacen = this.value;
      fntHerramientas(idAlmacen);
    });
    selectAlmacenesLocal.dataset.bound = '1';
  }
}

function fntAlmacenesComponentes(selectedValue = "") {
  const sel = document.querySelector('#listAlmaceneSCompSelect');
  if (!sel) return Promise.resolve(false);

  return fetch(base_url + '/Plan_confproductos/getSelectComponentes')
    .then(r => r.text())
    .then(html => {
      sel.innerHTML = html;
      if (selectedValue !== "") sel.value = String(selectedValue);
      return true;
    })
    .catch(err => {
      console.error(err);
      return false;
    })
    .finally(() => {
      if (!sel.dataset.bound) {
        sel.addEventListener('change', function () {
          fntComponentes(this.value);
        });
        sel.dataset.bound = '1';
      }
    });
}


// ------------------------------------------------------------------------
//  MOSTRAR HERRAMIENTAS
// ------------------------------------------------------------------------
function fntHerramientas(idAlmacen) {
  let ajaxUrl = base_url + '/Plan_confproductos/getHerramientas/' + idAlmacen;

  let request = (window.XMLHttpRequest)
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    if (request.status === 200) {
      let objData = [];

      try {
        objData = JSON.parse(request.responseText);
      } catch (e) {
        console.error("JSON inválido:", e);
        console.log("Respuesta cruda:", request.responseText);
        return;
      }

      if (!Array.isArray(objData)) objData = [objData];

      const dataCatalog = objData.map((item, index) => ({
        id: index + 1,
        name: item.descripcion_articulo || '',
        herramientaid: item.inventarioid,
        type: 'Herramienta',
        unit: item.unidad_salida || 'PZA',
        cve: item.cve_articulo || ''
      }));

      dtCatalogHerramientas.clear();
      dtCatalogHerramientas.rows.add(dataCatalog);
      dtCatalogHerramientas.draw(false);

    } else {
      console.error("Error AJAX. Status:", request.status);
      console.log("Respuesta:", request.responseText);
    }
  };
}

// ------------------------------------------------------------------------
//  MOSTRAR COMPONENTES
// ------------------------------------------------------------------------
function fntComponentes(idAlmacen) {
  let ajaxUrl = base_url + '/Plan_confproductos/getComponentes/' + idAlmacen;

  let request = (window.XMLHttpRequest)
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    if (request.status === 200) {
      let objData = [];

      try {
        objData = JSON.parse(request.responseText);
      } catch (e) {
        console.error("JSON inválido:", e);
        console.log("Respuesta cruda:", request.responseText);
        return;
      }

      if (!Array.isArray(objData)) objData = [objData];

      const dataCatalog = objData.map((item, index) => ({
        id: index + 1,
        name: item.descripcion_articulo || '',
        stock: item.existencia || '',
        inventarioid: item.inventarioid,
        type: 'Componente',
        unit: item.unidad_salida || 'PZA',
        cve: item.cve_articulo || ''
      }));

      dtCatalogComponentes.clear();
      dtCatalogComponentes.rows.add(dataCatalog);
      dtCatalogComponentes.draw(false);

      sincronizarBotonesCatalogoConSeleccionados();

    } else {
      console.error("Error AJAX. Status:", request.status);
      console.log("Respuesta:", request.responseText);
    }
  };
}

function sincronizarBotonesCatalogoConSeleccionados() {
  const setSel = new Set(componentesSeleccionados.map(x => String(x.inventarioid)));
  document.querySelectorAll('#tblCatalogComponentes .btn-add[data-inventarioid]').forEach(btn => {
    const id = String(btn.dataset.inventarioid);
    const ya = setSel.has(id);
    deshabilitarBotonAgregarCatalogo(id, ya);
  });
}


// ------------------------------------------------------------------------
//  TABLA SELECCIONADOS COMPONENTES (DT)
// ------------------------------------------------------------------------
function initTablaSeleccionadosComponentes() {
  if (dtSelectedComponentes) return;

  dtSelectedComponentes = new DataTable('#tblSelectedComponentes', {
    data: [],
    deferRender: true,
    pageLength: 10,
    searching: false,
    lengthChange: false,
    info: false,
    ordering: false,
    autoWidth: false,
    language: { url: "https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json" },
    columns: [
      { data: 'index' },
      {
        data: 'name',
        render: (data, type, row) => `
          <div class="fw-semibold">${data}</div>
          <small class="text-muted mono">CVE: ${row.cve || ''}</small>
        `
      },
      { data: 'type' },
      { data: 'unit' },
      {
        data: 'cantidad',
        render: (data, type, row) => `
          <input type="number"
                 class="form-control form-control-sm input-cantidad"
                 min="1"
                 value="${data || ''}"
                 data-inventarioid="${row.inventarioid}">
        `
      },
      {
        data: null,
        className: 'text-end',
        render: (data, type, row) => `
          <button class="btn btn-outline-danger btn-sm btn-eliminar"
                  data-inventarioid="${row.inventarioid}">
            Eliminar
          </button>
        `
      }
    ]
  });

  prepararEventosSeleccionadosComponentes();
}

function prepararEventosCatalogoComponentes() {
  const tabla = document.querySelector('#tblCatalogComponentes');
  if (!tabla || tabla.dataset.boundAdd) return;

  tabla.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-add');
    if (!btn) return;
    if (btn.disabled) return;

    const fila = dtCatalogComponentes.row(btn.closest('tr')).data();
    if (!fila) return;

    agregarComponenteASeleccionados(fila);
  });

  tabla.dataset.boundAdd = '1';
}

function agregarComponenteASeleccionados(filaCatalogo) {
  const inventarioid = String(filaCatalogo.inventarioid);

  const existe = componentesSeleccionados.some(x => String(x.inventarioid) === inventarioid);
  if (existe) return;

  componentesSeleccionados.push({
    inventarioid: filaCatalogo.inventarioid,
    name: filaCatalogo.name,
    type: filaCatalogo.type,
    unit: filaCatalogo.unit,
    cve: filaCatalogo.cve,
    cantidad: 1
  });

  deshabilitarBotonAgregarCatalogo(inventarioid, true);
  refrescarSeleccionadosComponentes();
}

function deshabilitarBotonAgregarCatalogo(inventarioid, deshabilitar) {
  const btn = document.querySelector(`#tblCatalogComponentes .btn-add[data-inventarioid="${inventarioid}"]`);
  if (!btn) return;

  btn.disabled = !!deshabilitar;
  btn.classList.toggle('disabled', !!deshabilitar);
  btn.classList.toggle('opacity-50', !!deshabilitar);
  btn.textContent = deshabilitar ? 'Agregado' : 'Agregar';
}

function refrescarSeleccionadosComponentes() {
  if (!dtSelectedComponentes) return;

  const data = componentesSeleccionados.map((x, idx) => ({ ...x, index: idx + 1 }));

  dtSelectedComponentes.clear();
  dtSelectedComponentes.rows.add(data);
  dtSelectedComponentes.draw(false);

  const count = document.querySelector('#countSelected');
  if (count) count.textContent = String(componentesSeleccionados.length);

//   const saveBar = document.querySelector('#saveBar');
//   if (saveBar) saveBar.classList.toggle('d-none', componentesSeleccionados.length === 0);
}

function prepararEventosSeleccionadosComponentes() {
  const tabla = document.querySelector('#tblSelectedComponentes');
  if (!tabla || tabla.dataset.boundSel) return;

  tabla.addEventListener('input', function (e) {
    const inp = e.target.closest('.input-cantidad');
    if (!inp) return;

    const inventarioid = String(inp.dataset.inventarioid || '');
    let cantidad = parseInt(inp.value, 10);

    if (!cantidad || cantidad < 1) {
      cantidad = 1;
      inp.value = 1;
    }

    const item = componentesSeleccionados.find(x => String(x.inventarioid) === inventarioid);
    if (item) item.cantidad = cantidad;
  });

  tabla.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-eliminar');
    if (!btn) return;

    const inventarioid = String(btn.dataset.inventarioid || '');
    eliminarComponenteSeleccionado(inventarioid);
  });

  tabla.dataset.boundSel = '1';
}

function eliminarComponenteSeleccionado(inventarioid) {
  componentesSeleccionados = componentesSeleccionados.filter(x => String(x.inventarioid) !== String(inventarioid));
  deshabilitarBotonAgregarCatalogo(inventarioid, false);
  refrescarSeleccionadosComponentes();
}

function prepararGuardarTodoComponentes() {
  const btn = document.querySelector('#btnGuardarTodo');
  if (!btn || btn.dataset.boundSave) return;

  btn.addEventListener('click', function () {
    const selectAlmacen = document.querySelector('#listAlmaceneSCompSelect');
    const idAlmacen = selectAlmacen ? (selectAlmacen.value || '') : '';

    const inputProducto = document.querySelector('#componentes_producto');
    const idProducto = inputProducto ? (inputProducto.value || '') : '';

    const inputEstacion = document.querySelector('#estacion_id');
    const idEstacion = inputEstacion ? (inputEstacion.value || '') : '';

    if (!idProducto) {
      Swal.fire("Atención", "No se detectó el producto actual.", "warning");
      return;
    }
    if (!idEstacion) {
      Swal.fire("Atención", "No se detectó la estación actual.", "warning");
      return;
    }
    if (!idAlmacen) {
      Swal.fire("Atención", "Selecciona un almacén para guardar.", "warning");
      return;
    }
    if (componentesSeleccionados.length === 0) {
      Swal.fire("Atención", "No hay componentes seleccionados.", "warning");
      return;
    }

    const lista = componentesSeleccionados.map(x => ({
      inventarioid: x.inventarioid,
      cantidad: x.cantidad
    }));

    const payload = [{
      idalmacen: idAlmacen,
      idproducto: idProducto,
      idestacion: idEstacion,
      sync: 1,
      detalle_componentes: lista
    }];

    if (divLoading) divLoading.style.display = "flex";

    const formData = new FormData();
    formData.append('componentes', JSON.stringify(payload));

    fetch(base_url + '/Plan_confproductos/setComponentesEstacion', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (divLoading) divLoading.style.display = "none";

        if (res.status) {
          Swal.fire("¡Operación exitosa!", res.msg || "Guardado correctamente", "success");
          $('#modalComponentes').modal('hide');
        } else {
          Swal.fire("Error", res.msg || "No se pudo guardar", "error");
        }
      })
      .catch(err => {
        if (divLoading) divLoading.style.display = "none";
        console.error(err);
        Swal.fire("Error", "Ocurrió un error al guardar.", "error");
      });
  });

  btn.dataset.boundSave = '1';
}

async function cargarComponentesGuardadosEstacion(idestacion) {
  const inputProducto = document.querySelector('#componentes_producto');
  const idProducto = inputProducto ? (inputProducto.value || '') : '';
  if (!idestacion || !idProducto) return;

  try {
    const r = await fetch(
      base_url + '/Plan_confproductos/getComponentesEstacion/' + idestacion +
      '?idproducto=' + encodeURIComponent(idProducto)
    );
    const res = await r.json();
    if (!res || !res.status) return;

    const idAlmacen = String(res.idalmacen || '');
    const data = Array.isArray(res.data) ? res.data : [];
    if (!idAlmacen || data.length === 0) return;

    await fntAlmacenesComponentes(idAlmacen);

    const sel = document.querySelector('#listAlmaceneSCompSelect');
    if (sel) {
      const existe = !!sel.querySelector(`option[value="${CSS.escape(idAlmacen)}"]`);
      if (!existe) {
        const opt = document.createElement('option');
        opt.value = idAlmacen;
        opt.textContent = idAlmacen;
        sel.appendChild(opt);
      }

      sel.value = idAlmacen;
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    componentesSeleccionados = data.map(x => ({
      inventarioid: x.inventarioid,
      name: x.descripcion || '',
      type: 'Componente',
      unit: x.unidad_salida || 'PZA',
      cve: x.cve_articulo || '',
      cantidad: Number(x.cantidad) || 1
    }));

    refrescarSeleccionadosComponentes();

    setTimeout(() => sincronizarBotonesCatalogoConSeleccionados(), 120);

    const btnGuardar = document.querySelector('#btnGuardarTodo');
    if (btnGuardar) btnGuardar.textContent = 'Actualizar todo';

  } catch (err) {
    console.error(err);
  }
}


// ======================================================================
//  HERRAMIENTAS (MODAL)
// ======================================================================
// ======================================================================
//  HERRAMIENTAS (MODAL + CARGA + GUARDADO) - CLON DE COMPONENTES
// ======================================================================



// ----------------------------------------------------------------------
//  ABRIR MODAL HERRAMIENTAS (REEMPLAZA tu abrirHerramientas actual)
// ----------------------------------------------------------------------
function abrirHerramientas(idestacion, cve_estacion) {

  // set hidden estación
  const inputEstacion = document.querySelector('#estacion_id_herr');
  if (inputEstacion) inputEstacion.value = idestacion || '';

  // set hidden producto (usa idproducto_proceso como lo haces en componentes)
  if (typeof idproducto_proceso !== 'undefined' && idproducto_proceso) {
    const inputProducto = document.querySelector('#herramientas_producto');
    if (inputProducto) inputProducto.value = idproducto_proceso.value;
  }

  resetModalHerramientas();

  // Cargar select almacenes + bind change -> fntHerramientas(idAlmacen)
  fntAlmacenesHerramientas();

  // Título
  const title = document.querySelector('#titleModalHerramientas');
  if (title) title.innerHTML = "Herramientas - " + cve_estacion;

  // Mostrar modal
  $('#modalHerramientas').modal('show');

  // Cargar guardadas de esa estación
  cargarHerramientasGuardadasEstacion(idestacion);
}

// ----------------------------------------------------------------------
//  RESET MODAL HERRAMIENTAS
// ----------------------------------------------------------------------
function resetModalHerramientas() {
  const sel = document.querySelector('#listAlmacenesHerrSelect');
  if (sel) sel.value = '';

  herramientasSeleccionadas = [];

  if (dtCatalogHerramientas) dtCatalogHerramientas.clear().draw(false);
  if (dtSelectedHerramientas) dtSelectedHerramientas.clear().draw(false);

  const count = document.querySelector('#countSelectedHerr');
  if (count) count.textContent = '0';

//   const saveBar = document.querySelector('#saveBarHerr');
//   if (saveBar) saveBar.classList.add('d-none');

  const btnGuardar = document.querySelector('#btnGuardarTodoHerramientas');
  if (btnGuardar) btnGuardar.textContent = 'Guardar todo';
}

// ----------------------------------------------------------------------
//  INIT TABLA SELECCIONADAS HERRAMIENTAS (DT)
// ----------------------------------------------------------------------
function initTablaSeleccionadosHerramientas() {
  if (dtSelectedHerramientas) return;

  dtSelectedHerramientas = new DataTable('#tblSelectedHerramientas', {
    data: [],
    deferRender: true,
    pageLength: 10,
    searching: false,
    lengthChange: false,
    info: false,
    ordering: false,
    autoWidth: false,
    language: { url: "https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json" },
    columns: [
      { data: 'index' },
      {
        data: 'name',
        render: (data, type, row) => `
          <div class="fw-semibold">${data}</div>
          <small class="text-muted mono">CVE: ${row.cve || ''}</small>
        `
      },
      { data: 'type' },
      { data: 'unit' },
      {
        data: 'cantidad',
        render: (data, type, row) => `
          <input type="number"
                 class="form-control form-control-sm input-cantidad-herr"
                 min="1"
                 value="${data || ''}"
                 data-inventarioid="${row.inventarioid}">
        `
      },
      {
        data: null,
        className: 'text-end',
        render: (data, type, row) => `
          <button class="btn btn-outline-danger btn-sm btn-eliminar-herr"
                  data-inventarioid="${row.inventarioid}">
            Eliminar
          </button>
        `
      }
    ]
  });

  prepararEventosSeleccionadosHerramientas();
}

// ----------------------------------------------------------------------
//  EVENTOS CATÁLOGO HERRAMIENTAS (btn-add)
// ----------------------------------------------------------------------
function prepararEventosCatalogoHerramientas() {
  const tabla = document.querySelector('#tblCatalogHerramientas');
  if (!tabla || tabla.dataset.boundAddHerr) return;

  tabla.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-add');
    if (!btn) return;
    if (btn.disabled) return;

    const fila = dtCatalogHerramientas.row(btn.closest('tr')).data();
    if (!fila) return;

    agregarHerramientaASeleccionadas(fila);
  });

  tabla.dataset.boundAddHerr = '1';
}

function agregarHerramientaASeleccionadas(filaCatalogo) {
  // IMPORTANTE: en tu catálogo usas herramientaid=item.inventarioid
  const inventarioid = String(filaCatalogo.herramientaid || filaCatalogo.inventarioid || '');

  if (!inventarioid) return;

  const existe = herramientasSeleccionadas.some(x => String(x.inventarioid) === inventarioid);
  if (existe) return;

  herramientasSeleccionadas.push({
    inventarioid: inventarioid,
    name: filaCatalogo.name,
    type: 'Herramienta',
    unit: filaCatalogo.unit,
    cve: filaCatalogo.cve,
    cantidad: 1
  });

  deshabilitarBotonAgregarCatalogoHerr(inventarioid, true);
  refrescarSeleccionadosHerramientas();
}

function deshabilitarBotonAgregarCatalogoHerr(inventarioid, deshabilitar) {
  const btn = document.querySelector(`#tblCatalogHerramientas .btn-add[data-herramientaid="${inventarioid}"]`);
  if (!btn) return;

  btn.disabled = !!deshabilitar;
  btn.classList.toggle('disabled', !!deshabilitar);
  btn.classList.toggle('opacity-50', !!deshabilitar);
  btn.textContent = deshabilitar ? 'Agregado' : 'Agregar';
}

function sincronizarBotonesCatalogoHerrConSeleccionadas() {
  const setSel = new Set(herramientasSeleccionadas.map(x => String(x.inventarioid)));
  document.querySelectorAll('#tblCatalogHerramientas .btn-add[data-herramientaid]').forEach(btn => {
    const id = String(btn.dataset.herramientaid);
    const ya = setSel.has(id);
    deshabilitarBotonAgregarCatalogoHerr(id, ya);
  });
}

function refrescarSeleccionadosHerramientas() {
  if (!dtSelectedHerramientas) return;

  const data = herramientasSeleccionadas.map((x, idx) => ({ ...x, index: idx + 1 }));

  dtSelectedHerramientas.clear();
  dtSelectedHerramientas.rows.add(data);
  dtSelectedHerramientas.draw(false);

  const count = document.querySelector('#countSelectedHerr');
  if (count) count.textContent = String(herramientasSeleccionadas.length);

//   const saveBar = document.querySelector('#saveBarHerr');
//   if (saveBar) saveBar.classList.toggle('d-none', herramientasSeleccionadas.length === 0);
}

// ----------------------------------------------------------------------
//  EVENTOS TABLA SELECCIONADAS (cantidad / eliminar)
// ----------------------------------------------------------------------
function prepararEventosSeleccionadosHerramientas() {
  const tabla = document.querySelector('#tblSelectedHerramientas');
  if (!tabla || tabla.dataset.boundSelHerr) return;

  tabla.addEventListener('input', function (e) {
    const inp = e.target.closest('.input-cantidad-herr');
    if (!inp) return;

    const inventarioid = String(inp.dataset.inventarioid || '');
    let cantidad = parseInt(inp.value, 10);

    if (!cantidad || cantidad < 1) {
      cantidad = 1;
      inp.value = 1;
    }

    const item = herramientasSeleccionadas.find(x => String(x.inventarioid) === inventarioid);
    if (item) item.cantidad = cantidad;
  });

  tabla.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-eliminar-herr');
    if (!btn) return;

    const inventarioid = String(btn.dataset.inventarioid || '');
    eliminarHerramientaSeleccionada(inventarioid);
  });

  tabla.dataset.boundSelHerr = '1';
}

function eliminarHerramientaSeleccionada(inventarioid) {
  herramientasSeleccionadas = herramientasSeleccionadas.filter(x => String(x.inventarioid) !== String(inventarioid));
  deshabilitarBotonAgregarCatalogoHerr(inventarioid, false);
  refrescarSeleccionadosHerramientas();
}

// ----------------------------------------------------------------------
//  GUARDAR TODO HERRAMIENTAS (SYNC)
// ----------------------------------------------------------------------
function prepararGuardarTodoHerramientas() {
  const btn = document.querySelector('#btnGuardarTodoHerramientas');
  if (!btn || btn.dataset.boundSaveHerr) return;

  btn.addEventListener('click', function () {

    const selectAlmacen = document.querySelector('#listAlmacenesHerrSelect');
    const idAlmacen = selectAlmacen ? (selectAlmacen.value || '') : '';

    const inputProducto = document.querySelector('#herramientas_producto');
    const idProducto = inputProducto ? (inputProducto.value || '') : '';

    const inputEstacion = document.querySelector('#estacion_id_herr');
    const idEstacion = inputEstacion ? (inputEstacion.value || '') : '';

    if (!idProducto) {
      Swal.fire("Atención", "No se detectó el producto actual.", "warning");
      return;
    }
    if (!idEstacion) {
      Swal.fire("Atención", "No se detectó la estación actual.", "warning");
      return;
    }
    if (!idAlmacen) {
      Swal.fire("Atención", "Selecciona un almacén para guardar.", "warning");
      return;
    }
    // if (herramientasSeleccionadas.length === 0) {
    //   Swal.fire("Atención", "No hay herramientas seleccionadas.", "warning");
    //   return;
    // }

    const lista = herramientasSeleccionadas.map(x => ({
      inventarioid: x.inventarioid,
      cantidad: x.cantidad
    }));

    const payload = [{
      idalmacen: idAlmacen,
      idproducto: idProducto,
      idestacion: idEstacion,
      sync: 1,
      detalle_herramientas: lista
    }];

    if (divLoading) divLoading.style.display = "flex";

    const formData = new FormData();
    formData.append('herramientas', JSON.stringify(payload));

    fetch(base_url + '/Plan_confproductos/setHerramientasEstacion', {
      method: 'POST',
      body: formData
    })
      .then(r => r.json())
      .then(res => {
        if (divLoading) divLoading.style.display = "none";

        if (res.status) {
          Swal.fire("¡Operación exitosa!", res.msg || "Guardado correctamente", "success");
          $('#modalHerramientas').modal('hide');
        } else {
          Swal.fire("Error", res.msg || "No se pudo guardar", "error");
        }
      })
      .catch(err => {
        if (divLoading) divLoading.style.display = "none";
        console.error(err);
        Swal.fire("Error", "Ocurrió un error al guardar.", "error");
      });

  });

  btn.dataset.boundSaveHerr = '1';
}

// ----------------------------------------------------------------------
//  CARGAR HERRAMIENTAS GUARDADAS POR ESTACIÓN (para editar)
// ----------------------------------------------------------------------
async function cargarHerramientasGuardadasEstacion(idestacion) {
    console.log(idestacion);
  const inputProducto = document.querySelector('#herramientas_producto');
  const idProducto = inputProducto ? (inputProducto.value || '') : '';
  if (!idestacion || !idProducto) return;

  try {
    const r = await fetch(
      base_url + '/Plan_confproductos/getHerramientasEstacion/' + idestacion +
      '?idproducto=' + encodeURIComponent(idProducto)
    );
    const res = await r.json();
    if (!res || !res.status) return;

    const idAlmacen = String(res.idalmacen || '');
    const data = Array.isArray(res.data) ? res.data : [];
    if (!idAlmacen || data.length === 0) return;

    // Seleccionar almacén
    const sel = document.querySelector('#listAlmacenesHerrSelect');
    if (sel) {
      // si tu select no contiene el almacén, lo agregamos temporal
      const existe = !!sel.querySelector(`option[value="${CSS.escape(idAlmacen)}"]`);
      if (!existe) {
        const opt = document.createElement('option');
        opt.value = idAlmacen;
        opt.textContent = idAlmacen;
        sel.appendChild(opt);
      }

      sel.value = idAlmacen;
      // dispara carga del catálogo
      sel.dispatchEvent(new Event('change', { bubbles: true }));
    }

    herramientasSeleccionadas = data.map(x => ({
      inventarioid: String(x.inventarioid),
      name: x.descripcion || '',
      type: 'Herramienta',
      unit: x.unidad_salida || 'PZA',
      cve: x.cve_articulo || '',
      cantidad: Number(x.cantidad) || 1
    }));

    refrescarSeleccionadosHerramientas();

    setTimeout(() => sincronizarBotonesCatalogoHerrConSeleccionadas(), 120);

    const btnGuardar = document.querySelector('#btnGuardarTodoHerramientas');
    if (btnGuardar) btnGuardar.textContent = 'Actualizar todo';

  } catch (err) {
    console.error(err);
  }
}




// ======================================================================
//  PAYLOAD RUTA
// ======================================================================
function construirPayloadRuta() {
  const plantaSel = document.querySelector('#listPlantasSelect');
  const lineaSel = document.querySelector('#listLineasSelect');
  const inpProd = document.querySelector('#idproducto_proceso');
  const tbodyRuta = document.querySelector('#listaRuta');

  const planta = plantaSel ? (plantaSel.value || '') : '';
  const linea = lineaSel ? (lineaSel.value || '') : '';
  const idproducto = inpProd ? (inpProd.value || '') : '';

  const filas = tbodyRuta ? Array.from(tbodyRuta.querySelectorAll('tr')) : [];
  const detalle_ruta = filas.map((tr, idx) => ({
    idestacion: (tr.getAttribute('data-idestacion') || '').toString().trim(),
    orden: idx + 1
  }));

  return [{
    listPlantasSelect: planta,
    listLineasSelect: linea,
    idproducto_proceso: idproducto,
    detalle_ruta
  }];
}

