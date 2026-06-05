
let tableAlmacenes;
let tableDocumentos;
let divLoading = null;

let dtCatalogHerramientas = null;
let dtCatalogComponentes = null;


let productoid = null;
let idproducto_documentacion = null;
let idproducto_descriptiva = null;
let inputiddescriptiva = null;
let idproducto_proceso = null;
let idproducto_especificacion = null;
let idespecificacioninput = null;

let id_ruta_producto = null;


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

// RUTA 
let rutaEstaciones = [];
let rutaDetallePendiente = [];
let aplicoRutaPendiente = false;

let tableEspecifica = null;
let estacionActual = 0;

let dtSelectedComponentes = null;
let componentesSeleccionados = []; // 

let dtSelectedHerramientas = null;
let herramientasSeleccionadas = []; // 

// NUEVOS 
let estacionesOriginales = new Set();
let estacionesEliminadas = [];

let estacionSeleccionadaActual = null;
let subensamblesCache = {};

let subensambleSeleccionadoActual = null;
let ayudasVisualesEstacion = {};
let inspeccionConfigEstacion = {};


let pdiPuntosEstacion = {};
let qualityStationsConfig = {};


let puntosPdiEstacion = {};
let ayudasVisualesPendientesFiles = {};

let pdiConfigEstacion = {};
let zonaPdiSeleccionada = null;

let ayudasVisualesSubensamble = {};
let ayudasVisualesSubensamblePendientesFiles = {};




function showLoading() {
  if (divLoading) divLoading.style.display = "flex";
}
function hideLoading() {
  if (divLoading) divLoading.style.display = "none";
}


async function fetchJSON(url, options = {}, { useLoading = true } = {}) {
  try {
    if (useLoading) showLoading();
    const res = await fetch(url, options);
    const data = await res.json().catch(() => null);

    if (!res.ok) {
      return { status: false, msg: `HTTP ${res.status}`, httpStatus: res.status, data };
    }
    return data;
  } catch (err) {
    console.error("fetchJSON error:", err);
    return { status: false, msg: "Error de conexión", error: String(err) };
  } finally {
    if (useLoading) hideLoading();
  }
}


function xhrRequest({ method = "GET", url, data = null, headers = {}, responseType = "json", useLoading = true }) {
  return new Promise((resolve) => {
    try {
      if (useLoading) showLoading();

      let request = (window.XMLHttpRequest)
        ? new XMLHttpRequest()
        : new ActiveXObject('Microsoft.XMLHTTP');

      request.open(method, url, true);

      Object.entries(headers).forEach(([k, v]) => request.setRequestHeader(k, v));

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        let out = null;

        if (request.status >= 200 && request.status < 300) {
          if (responseType === "json") {
            try {
              out = JSON.parse(request.responseText);
            } catch (e) {
              console.error("JSON inválido:", e);
              // console.log("Respuesta cruda:", request.responseText);
              out = { status: false, msg: "JSON inválido" };
            }
          } else {
            out = request.responseText;
          }
        } else {
          out = { status: false, msg: `HTTP ${request.status}`, httpStatus: request.status };
        }

        resolve(out);
      };

      request.onerror = function () {
        resolve({ status: false, msg: "Error de red" });
      };

      request.send(data);
    } catch (err) {
      console.error("xhrRequest error:", err);
      resolve({ status: false, msg: "Error interno", error: String(err) });
    }
  }).finally(() => {
    if (useLoading) hideLoading();
  });
}


// ======================================================================
//  DOM READY
// ======================================================================
document.addEventListener('DOMContentLoaded', function () {

  // --------------------------------------------------------------------
  //  REFERENCIAS BÁSICASE
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
  setPanelDetalleActivo(false);
  limpiarPanelDetalleEstacion();


  // --------------------------------------------------------------------
  //  INIT TAB INFORMACIÓN GENERAL 
  // --------------------------------------------------------------------
  initTabInformacion();
  initAyudasVisualesEventos();

  initPdiModalEventos();

  const btnAbrirModalPdi = document.getElementById('btnAbrirModalPdi');
  if (btnAbrirModalPdi && !btnAbrirModalPdi.dataset.bound) {
    btnAbrirModalPdi.addEventListener('click', async function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      const idproducto = document.getElementById('idproducto_proceso')?.value || '';

      if (!idestacion || !idproducto) {
        Swal.fire('Atención', 'Primero selecciona una estación.', 'warning');
        return;
      }

      await inicializarPdiDesdeLocalOBd(idestacion, idproducto);

      const modalEl = document.getElementById('modalPdi');
      if (modalEl) {
        bootstrap.Modal.getOrCreateInstance(modalEl).show();
      }
    });

    btnAbrirModalPdi.dataset.bound = '1';
  }


  const btnAgregar = document.getElementById('btnAgregarAyudaSub');

  if (btnAgregar) {
    btnAgregar.addEventListener('click', guardarAyudaVisualSubensamble);
  }



  const btnGuardarTodoAyudas = document.getElementById('btnGuardarTodoAyudas');
  if (btnGuardarTodoAyudas && !btnGuardarTodoAyudas.dataset.bound) {
    btnGuardarTodoAyudas.addEventListener('click', function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      guardarTodoAyudasEstacion(idestacion);
    });
    btnGuardarTodoAyudas.dataset.bound = '1';
  }

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
        "url": base_url + "/Plan_confproductosv1/getDocumentos",
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
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
      },
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
  //  TABLA SELECCIONADOS HERRAMIENTAS
  // --------------------------------------------------------------------
  initTablaSeleccionadosHerramientas();
  prepararEventosCatalogoHerramientas();
  prepararGuardarTodoHerramientas();


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
        "url": base_url + "/Plan_confproductosv1/getProductos",
        "dataSrc": ""
      },
      "columns": [
        { "data": "cve_producto" },
        { "data": "descripcion_producto" },
        { "data": "cve_linea_producto" },
        { "data": "descripcion_linea" },
        { "data": "fecha_creacion" },
        { "data": "estado_producto" },
        { "data": "options" }
      ],
      "language": {
        "url": "https://cdn.datatables.net/plug-ins/1.13.8/i18n/es-ES.json"
      },
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
    formConfigProd.addEventListener('submit', async function (e) {
      e.preventDefault();

      const ajaxUrl = base_url + '/Plan_confproductosv1/setProducto';
      const formData = new FormData(formConfigProd);

      const objData = await fetchJSON(ajaxUrl, { method: "POST", body: formData }, { useLoading: true });

      if (!objData || objData.status === false) {
        Swal.fire("Error", objData?.msg || "Ocurrió un error en el servidor.", "error");
        return;
      }

      if (objData.status) {

        if (objData.tipo === 'insert') {

          if (productoid) productoid.value = objData.idproducto;
          if (idproducto_documentacion) idproducto_documentacion.value = objData.idproducto;
          if (idproducto_descriptiva) idproducto_descriptiva.value = objData.idproducto;
          if (inputiddescriptiva) inputiddescriptiva.value = '0';
          if (idproducto_proceso) idproducto_proceso.value = objData.idproducto;
          if (idproducto_especificacion) idproducto_especificacion.value = objData.idproducto;


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

          // INCRUSTAR CLAVE Y DESCRIPCIÓN
          let clave_producto = objData.clave;
          let descripcion_producto = objData.descripcion;

          document.querySelectorAll('.producto_clave').forEach(span => {
            if (clave_producto) span.textContent = 'ID: ' + clave_producto;
          });

          document.querySelectorAll('.descripcion_producto').forEach(span => {
            if (descripcion_producto) span.textContent = descripcion_producto;
          });

        } else {
          if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';
          if (tabNuevo) tabNuevo.textContent = 'ACTUALIZAR';
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
        }

      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    });
  }

  // --------------------------------------------------------------------
  //  SUBMIT FORM PARA GUARDAR LA DESCRIPTIVA
  // --------------------------------------------------------------------
  if (formConfDescriptiva) {
    formConfDescriptiva.addEventListener('submit', async function (e) {
      e.preventDefault();

      const ajaxUrl = base_url + '/Plan_confproductosv1/setDescriptiva';
      const formData = new FormData(formConfDescriptiva);

      const objData = await fetchJSON(ajaxUrl, { method: "POST", body: formData }, { useLoading: true });

      if (!objData || objData.status === false) {
        Swal.fire("Error", objData?.msg || "Ocurrió un error en el servidor.", "error");
        return;
      }

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
    });
  }

  // --------------------------------------------------------------------
  //  SUBMIT FORM RUTA PRODUCTO
  // --------------------------------------------------------------------
  if (formRuta) {
    formRuta.addEventListener('submit', async function (e) {
      e.preventDefault();

      const ok = await guardarRutaProductoDesdePdi();

      if (ok) {
        Swal.fire({
          icon: 'success',
          title: '¡Operación exitosa!',
          text: 'La ruta del producto fue guardada correctamente',
          confirmButtonText: 'Aceptar'
        });
      }
    });
  }

  // --------------------------------------------------------------------
  //  SUBMIT FORM PARA GUARDAR LOS DOCUMENTOS
  // --------------------------------------------------------------------
  if (formDocumentacion) {
    formDocumentacion.addEventListener('submit', async function (e) {
      e.preventDefault();

      const ajaxUrl = base_url + '/Plan_confproductosv1/setDocumentacion';
      const formData = new FormData(formDocumentacion);

      const objData = await fetchJSON(ajaxUrl, { method: "POST", body: formData }, { useLoading: true });

      if (!objData || objData.status === false) {
        Swal.fire("Error", objData?.msg || "Ocurrió un error en el servidor.", "error");
        return;
      }

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

          if (tableDocumentos) tableDocumentos.ajax.reload();
        }
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    });
  }

  // --------------------------------------------------------------------
  //  SUBMIT FORM ESPECIFICACIONES
  // --------------------------------------------------------------------
  if (formEspecificaciones) {
    formEspecificaciones.addEventListener('submit', async function (e) {
      e.preventDefault();

      const tipoContextoEsp = document.querySelector('#tipo_contexto_especificacion')?.value || 'estacion';
      const esCritica = Number(document.querySelector('#es_critica_especificacion')?.value || 0);

      const ajaxUrl = tipoContextoEsp === 'subensamble'
        ? getControllerBase() + '/setEspecificacionSubensamble'
        : getControllerBase() + '/setEspecificacion';

      const formData = new FormData(formEspecificaciones);
      formData.set('es_critica', String(esCritica));

      const objData = await fetchJSON(ajaxUrl, {
        method: "POST",
        body: formData
      }, { useLoading: true });

      if (!objData || objData.status === false) {
        Swal.fire("Error", objData?.msg || "Ocurrió un error en el servidor.", "error");
        return;
      }

      if (!objData.status) {
        Swal.fire("Error", objData.msg || "No se pudo guardar.", "error");
        return;
      }

      Swal.fire("¡Operación exitosa!", objData.msg || "Registro guardado correctamente.", "success");

      resetFormEspecificacion();

      if (tipoContextoEsp === 'subensamble') {
        const idSub = document.querySelector('#idsubensamble_especificacion')?.value || 0;
        const idEstacion = document.querySelector('#idestacion_especificacion')?.value
          || document.querySelector('#idestacion_actual')?.value
          || 0;

        if (idSub) {
          cargarEspecificacionesSubensamble(idSub, esCritica);
          await refrescarMetricasSubensamble(idEstacion, idSub);
        }
      } else {
        const idEstacion = document.querySelector('#idestacion')?.value || estacionActual;
        if (idEstacion) {
          cargarEspecificaciones(idEstacion, esCritica);
          await refrescarEstadoEstacion(idEstacion);
        }
      }
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
      // { data: 'stock' },
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


  const listaRutaEl = document.querySelector('#listaRutaCards');
  if (listaRutaEl && !listaRutaEl.dataset.boundEstampadoOne) {
    listaRutaEl.addEventListener('change', function (e) {
      const chk = e.target.closest('.chk-estampado');
      if (!chk) return;
    });

    listaRutaEl.dataset.boundEstampadoOne = '1';
  }


}, false);


// ------------------------------------------------------------------------
//  INIT TAB INFORMACIÓN GENERAL
// ------------------------------------------------------------------------
function initTabInformacion() {
  fntInventarios();
  fntLineasProducto();
  fntPlantas();
}


async function guardarRutaProductoDesdePdi() {
  const formRuta = document.querySelector('#formRutaProducto');

  if (!formRuta) {
    console.warn('No se encontró el formulario de ruta.');
    return false;
  }

  const payload = construirPayloadRuta();
  const d = payload[0];

  if (!d.listPlantasSelect || !d.listLineasSelect) {
    Swal.fire("Atención", "Selecciona Planta y Línea.", "warning");
    return false;
  }

  if (!d.detalle_ruta || d.detalle_ruta.length === 0) {
    Swal.fire("Atención", "Agrega estaciones a la ruta.", "warning");
    return false;
  }

  const estampadas = (d.detalle_ruta || []).filter(x => Number(x.estampado || 0) === 1);

  if (estampadas.length !== 1) {
    Swal.fire(
      "Atención",
      "Debes marcar EXACTAMENTE 1 estación con “Estampar VIN” para poder guardar la ruta.",
      "warning"
    );
    return false;
  }

  const formData = new FormData(formRuta);
  formData.append('ruta', JSON.stringify(payload));

  const res = await fetchJSON(base_url + '/Plan_confproductosv1/setRutaProducto', {
    method: 'POST',
    body: formData
  }, { useLoading: true });

  if (res.status) {
    const inputRuta = document.querySelector('#id_ruta_producto');
    if (inputRuta && res.idruta) inputRuta.value = res.idruta;

    return true;
  }

  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: res.msg || 'No se pudo guardar la ruta',
    confirmButtonText: 'Aceptar'
  });

  return false;
}


// ------------------------------------------------------------------------
//  CARGAR DESCRIPTIVA TÉCNICA 
// ------------------------------------------------------------------------
async function loadDescriptivaForProducto() {
  if (!formConfDescriptiva) return;

  const btnSubmitDes = formConfDescriptiva.querySelector('button[type="submit"]');
  if (!inputiddescriptiva || !inputiddescriptiva.value.trim()) {
    resetDescriptivaSinHidden();
    if (btnSubmitDes) btnSubmitDes.textContent = 'REGISTRAR';
    return;
  }

  const idProd = inputiddescriptiva.value.trim();
  const ajaxUrl = base_url + '/Plan_confproductosv1/getDescriptiva/' + idProd;

  const objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });


  if (objData && objData.status === false) {
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
  const inputNorma = formConfDescriptiva.querySelector('#txtNorma');
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
  if (inputNorma) inputNorma.value = d.norma ?? '';
  if (inputEquipamiento) inputEquipamiento.value = d.equipamiento ?? '';

  if (btnSubmitDes) btnSubmitDes.textContent = 'ACTUALIZAR';
}


// ------------------------------------------------------------------------
//  CARGAR RUTA SI YA EXISTE 
// ------------------------------------------------------------------------
async function loadProcesoForProducto() {
  if (!formRuta) return;

  const btnSubmit = formRuta.querySelector('button[type="submit"]');

  if (!id_ruta_producto || !id_ruta_producto.value.trim()) {
    resetDescriptivaSinHidden();
    if (btnSubmit) btnSubmit.textContent = 'REGISTRAR RUTA';
    resetRutaUI();
    return;
  }

  const idRutaProd = id_ruta_producto.value.trim();
  const ajaxUrl = base_url + '/Plan_confproductosv1/getRuta/' + idRutaProd;

  const objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });


  if (objData && objData.status === false) {
    resetDescriptivaSinHidden();
    resetRutaCompleta();
    if (btnSubmit) btnSubmit.textContent = 'REGISTRAR RUTA';
    return;
  }

  const d = (Array.isArray(objData) && objData.length > 0) ? objData[0] : null;

  if (!d) {
    resetDescriptivaSinHidden();
    if (btnSubmit) btnSubmit.textContent = 'REGISTRAR RUTA';
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


  fntLineas(plantaId, lineaId);

  if (btnSubmit) btnSubmit.textContent = 'ACTUALIZAR';
}



function requireRutaProductoOrWarn() {
  const idRuta = (document.querySelector('#id_ruta_producto')?.value || '').trim();

  if (idRuta === '' || idRuta === '0') {
    Swal.fire(
      "Atención",
      "Primero guarda la RUTA del producto para poder capturar especificaciones, componentes o herramientas.",
      "warning"
    );
    return false;
  }

  return true;
}

// ------------------------------------------------------------------------
//  RESET COMPLETO DE RUTA 
// ------------------------------------------------------------------------
function resetRutaCompleta() {
  const listPlanta = document.querySelector('#listPlantasSelect');
  const listLinea = document.querySelector('#listLineasSelect');

  if (listPlanta) listPlanta.value = '';
  if (listLinea) listLinea.value = '';

  const listaEstaciones = document.querySelector('#listaEstaciones');
  if (listaEstaciones) listaEstaciones.innerHTML = '';

  const listaRuta = document.querySelector('#listaRuta');
  if (listaRuta) listaRuta.innerHTML = '';

  rutaDetallePendiente = [];
  aplicoRutaPendiente = false;
  estacionesOriginales = new Set();
  estacionesEliminadas = [];

  actualizarPlaceholderRuta();
  actualizarCountRuta();
  actualizarInputHiddenRuta();
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
async function fntInventarios(selectedValue = "") {
  const selectProductos = document.querySelector('#listProductos');
  if (!selectProductos) return;

  const ajaxUrl = base_url + '/Plan_confproductosv1/getSelectProductos';
  const html = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "text", useLoading: true });

  if (typeof html === "string") {
    selectProductos.innerHTML = html;
    if (selectedValue !== "") selectProductos.value = selectedValue;
  }

  if (!selectProductos.dataset.bound) {
    selectProductos.addEventListener('change', function () {
      const idProducto = this.value;
      if (idProducto !== "") fntInventarioDetalle(idProducto);
    });
    selectProductos.dataset.bound = "1";
  }
}

async function fntInventarioDetalle(idInventario) {
  const ajaxUrl = base_url + '/Plan_confproductosv1/getSelectInventario/' + idInventario;
  const objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });

  if (!objData || objData.status === false) return;

  let descripcion = objData.descripcion;
  let lineaproducto = objData.lineaproductoid;

  let inputDescripcion = document.getElementById("txtDescripcion");
  let selectLineasProductos = document.getElementById("listLineasProductos");

  if (inputDescripcion) inputDescripcion.value = descripcion;
  if (selectLineasProductos) selectLineasProductos.value = lineaproducto;
}

async function fntLineasProducto(selectedValue = "") {
  const selectLineasProductos = document.querySelector('#listLineasProductos');
  if (!selectLineasProductos) return;

  const ajaxUrl = base_url + '/Plan_confproductosv1/getSelectLineasProductos';
  const html = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "text", useLoading: true });

  if (typeof html === "string") {
    selectLineasProductos.innerHTML = html;
    if (selectedValue !== "") selectLineasProductos.value = selectedValue;
  }
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
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    const ajaxUrl = base_url + '/Plan_confproductosv1/delDocumento';
    const strData = "iddocumento=" + encodeURIComponent(iddocumento);

    const objData = await xhrRequest({
      method: "POST",
      url: ajaxUrl,
      data: strData,
      headers: { "Content-type": "application/x-www-form-urlencoded" },
      responseType: "json",
      useLoading: true
    });

    if (objData && objData.status) {
      Swal.fire("¡Operación exitosa!", objData.msg, "success");
      if (tableDocumentos) tableDocumentos.ajax.reload();
    } else {
      Swal.fire("Atención!", objData?.msg || "Error al eliminar", "error");
    }
  });
}


// ------------------------------------------------------------------------
//  EDITAR PRODUCTO
// ------------------------------------------------------------------------
async function fntEditProducto(idproducto) {
  if (!idproducto) return;

  const ajaxUrl = base_url + '/Plan_confproductosv1/getProducto/' + idproducto;

  const objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });

  if (!objData || objData.status === false) {
    Swal.fire("Aviso", objData?.msg || "No se encontró la información del producto.", "warning");
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


  let clave_producto = data.cve_producto;
  let descripcion_producto = data.descripcion;

  document.querySelectorAll('.producto_clave').forEach(span => {
    if (clave_producto) span.textContent = 'ID: ' + clave_producto;
  });

  document.querySelectorAll('.descripcion_producto').forEach(span => {
    if (descripcion_producto) span.textContent = descripcion_producto;
  });


  setInfoGeneralActive();
}



async function fntPlantas(selectedValue = "") {
  const selectPlantasLocal = document.querySelector('#listPlantasSelect');
  const selectLineasLocal = document.querySelector('#listLineasSelect');

  if (!selectPlantasLocal) return;

  const ajaxUrl = base_url + '/Cap_plantas/getSelectPlantas';
  const html = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "text", useLoading: true });

  if (typeof html === "string") {
    selectPlantasLocal.innerHTML = html;

    if (selectedValue !== "") {
      selectPlantasLocal.value = selectedValue;
      fntLineas(selectPlantasLocal.value);
    }
  }


  if (!selectPlantasLocal.dataset.bound) {
    selectPlantasLocal.addEventListener('change', function () {
      const idPlanta = this.value;

      resetRutaUI();
      rutaDetallePendiente = [];
      aplicoRutaPendiente = false;

      if (selectLineasLocal) selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
      fntLineas(idPlanta);
    });
    selectPlantasLocal.dataset.bound = '1';
  }

}

async function fntLineas(idPlanta, selectedLinea = "") {
  const selectLineasLocal = document.querySelector('#listLineasSelect');
  if (!selectLineasLocal) return;

  if (!idPlanta) {
    selectLineasLocal.innerHTML = '<option value="">--Seleccione--</option>';
    fntEstaciones("");
    return;
  }

  const ajaxUrl = base_url + '/Cap_lineasdtrabajo/getSelectLineas/' + idPlanta;
  const html = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "text", useLoading: true });

  if (typeof html === "string") {
    selectLineasLocal.innerHTML = html;

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

  // if (!selectLineasLocal.dataset.bound) {
  //   selectLineasLocal.addEventListener('change', function () {
  //     fntEstaciones(this.value || "");
  //   });
  //   selectLineasLocal.dataset.bound = '1';
  // }

  if (!selectLineasLocal.dataset.bound) {
    selectLineasLocal.addEventListener('change', function () {
      resetRutaUI();
      rutaDetallePendiente = [];
      aplicoRutaPendiente = false;

      fntEstaciones(this.value || "");
    });
    selectLineasLocal.dataset.bound = '1';
  }

}

// ---------------------------------------------
//  ESTACIONES
// ---------------------------------------------
async function fntEstaciones(idLinea, selectedEstacion = "") {
  const selectEstaciones = document.querySelector('#listEstacionesSelect');
  const listaEstaciones = document.querySelector('#listaEstaciones');
  const badgeCount = document.querySelector('#countEstacionesDisponibles');
  const msgSinEstaciones = document.querySelector('#mensajeSinEstaciones');

  if (!listaEstaciones || !badgeCount) return;

  // if (!idLinea) {
  //   listaEstaciones.innerHTML = '';
  //   badgeCount.textContent = '0';
  //   if (msgSinEstaciones) msgSinEstaciones.classList.remove('d-none');
  //   if (selectEstaciones) selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';
  //   resetRutaUI();
  //   return;
  // }

  if (!idLinea) {
    listaEstaciones.innerHTML = '';
    badgeCount.textContent = '0';
    if (msgSinEstaciones) msgSinEstaciones.classList.remove('d-none');
    if (selectEstaciones) selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';
    resetRutaUI();
    setPanelDetalleActivo(false);
    return;
  }


  const ajaxUrl = getControllerBase() + '/getSelectEstaciones/' + idLinea;
  const estaciones = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });

  // if (!Array.isArray(estaciones) || estaciones.length === 0) {
  //   listaEstaciones.innerHTML = '';
  //   badgeCount.textContent = '0';
  //   if (msgSinEstaciones) msgSinEstaciones.classList.remove('d-none');
  //   if (selectEstaciones) selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';
  //   resetRutaUI();
  //   return;
  // }

  if (!Array.isArray(estaciones) || estaciones.length === 0) {
    listaEstaciones.innerHTML = '';
    badgeCount.textContent = '0';
    if (msgSinEstaciones) msgSinEstaciones.classList.remove('d-none');
    if (selectEstaciones) selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';
    resetRutaUI();
    setPanelDetalleActivo(false);
    return;
  }


  listaEstaciones.innerHTML = '';
  if (selectEstaciones) selectEstaciones.innerHTML = '<option value="">--Seleccione--</option>';

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
    item.className = 'station-card list-group-item list-group-item-action d-flex justify-content-between align-items-center';
    item.setAttribute('data-idestacion', String(est.idestacion));
    item.setAttribute('data-cve', est.cve_estacion || '');
    item.setAttribute('data-nombre', est.nombre_estacion || '');
    item.setAttribute('data-proceso', est.proceso || '');
    item.setAttribute('data-herramientas', String(est.herramientas ?? 0));
    item.setAttribute('data-tiene-subensamble', String(est.tiene_subensamble ?? 0));
    item.setAttribute('data-tiempo-ajuste', String(est.tiempo_ajuste ?? ''));
    item.setAttribute('draggable', 'true');

    item.innerHTML = `
  <div class="d-flex align-items-start justify-content-between w-100 gap-2">
    <div class="d-flex align-items-start gap-2 text-start">
      <div class="mt-1 text-body-secondary">
        <i class="bi bi-grip-vertical"></i>
      </div>
      <div>
        <div class="fw-semibold">
          <i class="bi bi-hdd-network me-1 text-primary"></i>${est.cve_estacion}
        </div>
        <small class="text-body-secondary d-block">${est.nombre_estacion}</small>
        <div class="mt-1 d-flex flex-wrap gap-1">
          ${Number(est.tiene_subensamble || 0) === 1
        ? `<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                 <i class="bi bi-diagram-3 me-1"></i>Con subensamble
               </span>`
        : `<span class="badge rounded-pill bg-body-secondary text-body border">
                 <i class="bi bi-box me-1"></i>Estación
               </span>`
      }
          ${(est.tiempo_ajuste ?? '') !== ''
        ? `<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                 <i class="bi bi-clock-history me-1"></i>${est.tiempo_ajuste}
               </span>`
        : ''
      }
        </div>
      </div>
    </div>
    <i class="bi bi-chevron-right text-body-secondary mt-1"></i>
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
        proceso: est.proceso,
        herramientas: est.herramientas,
        tiene_subensamble: Number(est.tiene_subensamble || 0),
        tiempo_ajuste: est.tiempo_ajuste || ''
      }, item);
    });

    listaEstaciones.appendChild(item);
  });

  aplicarRutaPendienteSiExiste();
}


// ======================================================================
//  FIX FINAL RUTA
// ======================================================================
function aplicarRutaPendienteSiExiste() {
  if (aplicoRutaPendiente) return;
  if (!Array.isArray(rutaDetallePendiente) || rutaDetallePendiente.length === 0) return;

  const lista = document.querySelector('#listaEstaciones');
  if (!lista) return;

  resetRutaUI();

  estacionesOriginales = new Set(
    rutaDetallePendiente.map(x => String(x.idestacion).trim()).filter(Boolean)
  );

  estacionesEliminadas = [];

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
        proceso: btnOrigen.getAttribute('data-proceso') || '',
        herramientas: Number(btnOrigen.getAttribute('data-herramientas') || 0),
        tiene_subensamble: Number(btnOrigen.getAttribute('data-tiene-subensamble') || 0),
        iddetalle: Number(item.iddetalle || 0),
        estampado: Number(item.estampado || 0),
        calidad: Number(item.calidad || 0)
      };

      agregarEstacionARuta(est, btnOrigen);
    });

  aplicoRutaPendiente = true;

  setTimeout(() => {
    const cards = Array.from(document.querySelectorAll('#listaRutaCards .ruta-card-mini[data-idestacion]'));
    cards.forEach(card => {
      const idest = card.getAttribute('data-idestacion');
      refrescarEstadoEstacion(idest);
    });
  }, 150);
}


function resetRutaUI() {
  const contenedor = document.querySelector('#listaRutaCards');
  if (contenedor) contenedor.innerHTML = '';

  rutaEstaciones = [];
  estacionesOriginales = new Set();
  estacionesEliminadas = [];
  estacionSeleccionadaActual = null;
  subensambleSeleccionadoActual = null;
  subensamblesCache = {};

  const inputEstacionActual = document.querySelector('#idestacion_actual');
  if (inputEstacionActual) inputEstacionActual.value = '';

  actualizarPlaceholderRuta();
  actualizarCountRuta();
  actualizarInputHiddenRuta();

  limpiarPanelDetalleEstacion();
  setPanelDetalleActivo(false);

  const tabConfig = document.querySelector('#tab-det-config');
  if (tabConfig) {
    bootstrap.Tab.getOrCreateInstance(tabConfig).show();
  }
}

// ------------------------------------------------------------------------
//  AGREGAR ESTACIÓN A LA RUTA
// ------------------------------------------------------------------------
function agregarEstacionARuta(est, botonOrigen) {

  const contenedor = document.querySelector('#listaRutaCards');
  if (!contenedor) return;

  const idEstacion = String(est.idestacion).trim();
  if (!idEstacion) return;
  if (rutaEstaciones.includes(idEstacion)) return;

  estacionesEliminadas = estacionesEliminadas.filter(x => String(x.idestacion) !== idEstacion);
  rutaEstaciones.push(idEstacion);

  const estampadoVal = Number(est.estampado || 0);
  const calidadVal = Number(est.calidad || 0);
  const tieneSub = Number(est.tiene_subensamble || 0);

  const card = document.createElement('div');
  card.className = 'ruta-card-mini station-selected';
  if (tieneSub === 1) card.classList.add('has-subensamble');

  card.setAttribute('data-idestacion', idEstacion);
  card.setAttribute('data-iddetalle', String(Number(est.iddetalle || 0)));
  card.setAttribute('data-estampado', String(estampadoVal));
  card.setAttribute('data-cve', est.cve_estacion || '');
  card.setAttribute('data-nombre', est.nombre_estacion || '');
  card.setAttribute('data-proceso', est.proceso || '');
  card.setAttribute('data-tiene-subensamble', String(tieneSub));
  card.setAttribute('data-tiempo-ajuste', String(est.tiempo_ajuste || ''));
  card.setAttribute('data-calidad', String(calidadVal)); //

  card.innerHTML = `
    <div class="ruta-station-wrapper">
      <div class="ruta-subensambles-mini d-none" id="rutaSubWrap-${idEstacion}"></div>

      <div class="d-flex align-items-center justify-content-between gap-3 station-main-row">
        <div class="d-flex align-items-center gap-3 flex-grow-1">
          <div class="ruta-step-circle">${rutaEstaciones.length}</div>
          <div class="flex-grow-1">
            <div class="fw-semibold">
              <i class="bi bi-gear-wide-connected text-primary me-1"></i>${est.cve_estacion || ''}
            </div>
            <small class="text-muted d-block">${est.nombre_estacion || ''}</small>
            <div class="mt-2 d-flex flex-wrap gap-1">
              ${(est.tiempo_ajuste || '') !== ''
      ? `<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-clock-history me-1"></i>${est.tiempo_ajuste}</span>`
      : ''
    }
              ${estampadoVal === 1
      ? `<span class="badge rounded-pill bg-info-subtle text-info-emphasis"><i class="bi bi-upc-scan me-1"></i>VIN</span>`
      : ''
    }
              ${tieneSub === 1
      ? `<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><i class="bi bi-diagram-3 me-1"></i>PRE</span>`
      : ''
    }
            </div>
          </div>
        </div>

        <div class="text-end d-flex align-items-center gap-2">
          <span class="badge-soft-success">
            <i class="bi bi-check-circle me-1"></i>CONFIGURADO
          </span>
        </div>
      </div>
    </div>
  `;

  card.addEventListener('click', function (e) {
    if (e.target.closest('.subensamble-box-mini')) return;
    seleccionarEstacionRuta(idEstacion);
  });

  contenedor.appendChild(card);

  if (botonOrigen) {
    card.dataset.btnOrigenId = idEstacion;
    bloquearBotonEstacion(botonOrigen);
  } else {
    setEstacionDisponibleEnLista(idEstacion, false);
  }

  reindexarRutaVisual();
  actualizarPlaceholderRuta();
  actualizarCountRuta();
  actualizarInputHiddenRuta();

  if (!estacionSeleccionadaActual) {
    seleccionarEstacionRuta(idEstacion);
  }

  cargarSubensamblesMiniEnRuta(idEstacion, tieneSub);
}

function obtenerSubensambleDeEstacion(idestacion) {
  const estacion = rutaProductoDetalle.find(x => String(x.idestacion) === String(idestacion));

  if (!estacion) return null;

  return estacion.subensamble || null;
}


async function cargarSubensamblesMiniEnRuta(idestacion, tieneSub) {
  const wrap = document.querySelector(`#rutaSubWrap-${idestacion}`);
  if (!wrap) return;

  if (Number(tieneSub) !== 1) {
    wrap.classList.add('d-none');
    wrap.innerHTML = '';
    return;
  }

  const idProductoProceso = parseInt(document.getElementById('idproducto_proceso')?.value || 0);
  if (!idProductoProceso) return;

  const url = getControllerBase() + '/getSubensamblesEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);
  const res = await fetchJSON(url, { method: 'GET' }, { useLoading: false });

  let sub = null;

  if (res && res.status) {
    if (Array.isArray(res.data) && res.data.length > 0) {
      sub = res.data[0];
    } else if (res.data && !Array.isArray(res.data)) {
      sub = res.data;
    }
  }

  if (!sub) {
    wrap.classList.add('d-none');
    wrap.innerHTML = '';
    return;
  }

  subensamblesCache[idestacion] = sub;

  wrap.classList.remove('d-none');
  wrap.innerHTML = `
    <div class="ruta-subensamble-mini">
      <div class="subensamble-box-mini" data-idsubensamble="${sub.idsubensamble}">
        <div class="d-flex justify-content-between align-items-center gap-2">
          <div>
            <div class="fw-semibold small">
              <i class="bi bi-diagram-3 me-1 text-primary"></i>${sub.proceso || ''}
            </div>
            <small class="text-muted d-block">Subensamble ligado a esta estación</small>
          </div>
          <span class="badge rounded-pill bg-primary-subtle text-primary-emphasis">PRE</span>
        </div>
      </div>
    </div>
  `;

  const item = wrap.querySelector('.subensamble-box-mini');
  if (item) {
    item.addEventListener('click', function (e) {
      e.stopPropagation();

      seleccionarEstacionRuta(idestacion);
      seleccionarSubensambleEnPanel(idestacion, sub.idsubensamble);

      const tabBtn = document.querySelector('#tab-det-sub');
      if (tabBtn) bootstrap.Tab.getOrCreateInstance(tabBtn).show();

      const inputSub = document.getElementById('id_subensamble_actual');
      if (inputSub) {
        inputSub.value = sub.idsubensamble;
      }



      cargarAyudasVisualesSubensamble(sub.idsubensamble);
    });
  }
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
  const contenedor = document.querySelector('#listaRutaCards');
  const placeholder = document.querySelector('#placeholderRuta');
  if (!placeholder || !contenedor) return;

  const hayFilas = contenedor.querySelectorAll('.ruta-card-mini').length > 0;
  placeholder.classList.toggle('d-none', hayFilas);
}

function actualizarCountRuta() {
  const contenedor = document.querySelector('#listaRutaCards');
  const countRuta = document.querySelector('#countRuta');
  if (!contenedor || !countRuta) return;

  countRuta.textContent = String(contenedor.querySelectorAll('.ruta-card-mini').length);
}


function marcarEstacionConDatosUI(idestacion) {
  const tr = document.querySelector(`#listaRuta tr[data-idestacion="${CSS.escape(String(idestacion))}"]`);
  if (!tr) return;

  // tr.classList.add('table-warning'); 
}

async function refrescarEstadoEstacion(idestacion) {
  const idProductoProceso = parseInt(document.getElementById('idproducto_proceso')?.value || 0);
  if (!idestacion || !idProductoProceso) return;

  const card = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`);
  if (!card) return;

  await cargarAyudasDesdeServidor(idestacion, idProductoProceso);



  const urlEsp = getControllerBase() + '/getEspecificaciones/' + idestacion + '/' + idProductoProceso;
  const resEsp = await fetchJSON(urlEsp, { method: 'GET' }, { useLoading: false });
  const totalEsp = !!(resEsp && resEsp.status && Array.isArray(resEsp.data)) ? resEsp.data.length : 0;

  const urlComp = getControllerBase() + '/getComponentesEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);
  const resComp = await fetchJSON(urlComp, { method: 'GET' }, { useLoading: false });
  const totalComp = !!(resComp && resComp.status && Array.isArray(resComp.data)) ? resComp.data.length : 0;

  const urlHerr = getControllerBase() + '/getHerramientasEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);
  const resHerr = await fetchJSON(urlHerr, { method: 'GET' }, { useLoading: false });
  const totalHerr = !!(resHerr && resHerr.status && Array.isArray(resHerr.data)) ? resHerr.data.length : 0;

  const urlOps = getControllerBase() + '/getOperacionesEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);
  const resOps = await fetchJSON(urlOps, { method: 'GET' }, { useLoading: false });
  const totalOps = !!(resOps && resOps.status && Array.isArray(resOps.data)) ? resOps.data.length : 0;


  const urlSubC = getControllerBase() + '/getOperacionesSubensamble/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);
  const resSuben = await fetchJSON(urlSubC, { method: 'GET' }, { useLoading: false });
  const totalOperCriticas = !!(resSuben && resSuben.status && Array.isArray(resSuben.data)) ? resSuben.data.length : 0;



  const urlSub = getControllerBase() + '/getSubensamblesEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);
  const resSub = await fetchJSON(urlSub, { method: 'GET' }, { useLoading: false });

  let totalSub = 0;
  if (resSub && resSub.status) {
    if (Array.isArray(resSub.data)) totalSub = resSub.data.length;
    else if (resSub.data) totalSub = 1;
  }



  if (String(estacionSeleccionadaActual) === String(idestacion)) {
    const metricHerr = document.querySelector('#metricHerramientas');
    const metricComp = document.querySelector('#metricComponentes');
    const metricOps = document.querySelector('#metricOperaciones');
    const totalOperC = document.querySelector('#metricSubensambles');

    if (metricHerr) metricHerr.textContent = totalHerr;
    if (metricComp) metricComp.textContent = totalComp;
    if (metricOps) metricOps.textContent = totalEsp;
    if (totalOperC) totalOperC.textContent = totalOperCriticas;
  }

  if (resSub && resSub.status && resSub.data) {
    subensamblesCache[idestacion] = Array.isArray(resSub.data) ? resSub.data[0] : resSub.data;
  }


  const badgeContainer = card.querySelector('.flex-grow-1 .mt-2');
  if (badgeContainer) {
    const tiempo = card.getAttribute('data-tiempo-ajuste') || '';
    const estampado = Number(card.getAttribute('data-estampado') || 0);

    badgeContainer.innerHTML = `
      ${tiempo ? `<span class="badge rounded-pill bg-warning-subtle text-warning-emphasis"><i class="bi bi-clock-history me-1"></i>${tiempo}</span>` : ''}
      ${estampado === 1 ? `<span class="badge rounded-pill bg-info-subtle text-info-emphasis"><i class="bi bi-upc-scan me-1"></i>VIN</span>` : ''}
      ${totalSub > 0 ? `<span class="badge rounded-pill bg-primary-subtle text-primary-emphasis"><i class="bi bi-diagram-3 me-1"></i>PRE</span>` : ''}
    `;
  }

}


async function refrescarMetricasSubensamble(idestacion = null, idsubensamble = null) {
  idestacion = idestacion || document.querySelector('#idestacion_actual')?.value || '';
  idsubensamble = idsubensamble || document.querySelector('#id_subensamble_actual')?.value || '';
  const idproducto = document.querySelector('#idproducto_proceso')?.value || '';

  if (!idestacion || !idsubensamble || !idproducto) return;

  const url = getControllerBase()
    + '/getSubensamblesEstacion/'
    + encodeURIComponent(idestacion)
    + '?idproducto='
    + encodeURIComponent(idproducto);

  const res = await fetchJSON(url, { method: 'GET' }, { useLoading: false });

  let sub = null;

  if (res && res.status) {
    if (Array.isArray(res.data)) {
      sub = res.data.find(x => String(x.idsubensamble) === String(idsubensamble)) || res.data[0];
    } else if (res.data) {
      sub = res.data;
    }
  }

  if (!sub) return;

  subensamblesCache[idestacion] = sub;

  const mHerr = document.querySelector('#metricSubHerramientas');
  const mComp = document.querySelector('#metricSubComponentes');
  const mOp = document.querySelector('#metricSubOperaciones');
  const mEsp1 = document.querySelector('#metricSubSubensambles');
  const mEsp2 = document.querySelector('#metricSubEspecificaciones');

  if (mHerr) mHerr.textContent = Number(sub.total_herramientas || 0);
  if (mComp) mComp.textContent = Number(sub.total_componentes || 0);
  if (mOp) mOp.textContent = Number(sub.total_operaciones || 0);
  if (mEsp1) mEsp1.textContent = Number(sub.total_especificaciones || 0);
  if (mEsp2) mEsp2.textContent = Number(sub.total_especificaciones || 0);
}

function pintarBotonEstado(btn, tiene, color) {
  if (!btn) return;


  btn.classList.remove(
    'btn-info', 'btn-primary', 'btn-success',
    'btn-outline-info', 'btn-outline-primary', 'btn-outline-success'
  );

  const outline = `btn-outline-${color}`;
  const filled = `btn-${color}`;

  btn.classList.add(tiene ? filled : outline);
}

function actualizarInputHiddenRuta() {
  const inputRuta = document.querySelector('#ruta_estaciones');
  const contenedor = document.querySelector('#listaRutaCards');
  if (!inputRuta || !contenedor) return;

  const cards = Array.from(contenedor.querySelectorAll('.ruta-card-mini[data-idestacion]'));
  rutaEstaciones = cards.map(card => String(card.getAttribute('data-idestacion')).trim()).filter(Boolean);
  inputRuta.value = rutaEstaciones.join(',');
}

function reindexarRutaVisual() {
  const contenedor = document.querySelector('#listaRutaCards');
  if (!contenedor) return;

  const cards = Array.from(contenedor.querySelectorAll('.ruta-card-mini'));
  cards.forEach((card, idx) => {
    const badge = card.querySelector('.ruta-step-circle');
    if (badge) badge.textContent = String(idx + 1);
  });
}


function seleccionarEstacionRuta(idestacion) {
  document.getElementById('idestacion_actual').value = idestacion;
  estacionSeleccionadaActual = String(idestacion);
  setPanelDetalleActivo(true);
  subensambleSeleccionadoActual = null;

  document.querySelectorAll('#listaRutaCards .ruta-card-mini').forEach(card => {
    const activa = String(card.dataset.idestacion) === String(idestacion);
    card.classList.toggle('active', activa);
  });

  document.querySelectorAll('.subensamble-box-mini').forEach(item => {
    item.classList.remove('active');
  });



  cargarDetalleEstacionPanel(idestacion);
  inicializarDatosLocalesOBdEstacion(idestacion);
  //  obtenerSubensambleDeEstacion(idestacion);

}

async function cargarDetalleEstacionPanelold(idestacion) {
  const card = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`);
  if (!card) return;


  subensambleSeleccionadoActual = null;

  const inputSub = document.getElementById('id_subensamble_actual');
  if (inputSub) {
    inputSub.value = '';
  }

  const cve = card.dataset.cve || '';
  const nombre = card.dataset.nombre || '';
  const tieneSub = Number(card.dataset.tieneSubensamble || 0);
  const estampado = Number(card.dataset.estampado || 0);
  const tiempoAjuste = card.dataset.tiempoAjuste || '-';
  const calidad = Number(card.dataset.calidad || 0);

  const proceso = card.dataset.proceso || '';

  const title = document.querySelector('#titleEstacionDetalle');
  if (title) title.textContent = `Estación principal: ${cve}`;

  const detCodigo = document.querySelector('#detCodigoEstacion');
  const detArea = document.querySelector('#detAreaEstacion');
  const detTipo = document.querySelector('#detTipoEstacion');
  const detDesc = document.querySelector('#detDescEstacion');
  const detTiempo = document.querySelector('#detTiempoAjusteEstacion');

  if (detCodigo) detCodigo.textContent = cve;
  if (detArea) detArea.textContent = nombre;
  if (detTipo) detTipo.textContent = 'Estación principal';
  if (detDesc) detDesc.textContent = proceso;
  if (detTiempo) detTiempo.textContent = tiempoAjuste || '-';

  document.getElementById('idestacion_actual').value = idestacion;

  const chkVin = document.querySelector('#chkVinDetalle');
  if (chkVin) {
    chkVin.checked = estampado === 1;
    chkVin.onchange = function () {
      marcarVinDesdePanel(idestacion, this.checked);
    };
  }

  const chkCalidad = document.querySelector('#chkRequiereInspeccion');
  if (chkCalidad) {
    chkCalidad.checked = calidad == 1;
    // chkCalidad.onchange = function () {
    //   marcarCalidadDesdePanel(idestacion, this.checked);
    // };
  }

  const liSubTab = document.querySelector('#li-tab-det-sub');
  if (liSubTab) liSubTab.classList.toggle('d-none', tieneSub !== 1);

  const activeTabBtn = document.querySelector('#tabsDetalleEstacion .nav-link.active');
  if (tieneSub !== 1 && activeTabBtn && activeTabBtn.id === 'tab-det-sub') {
    const configTab = document.querySelector('#tab-det-config');
    if (configTab) bootstrap.Tab.getOrCreateInstance(configTab).show();
  }

  const btnEliminar = document.querySelector('#btnEliminarEstacionPanel');
  if (btnEliminar) btnEliminar.disabled = false;

  const btnHerr = document.querySelector('#btnAbrirHerramientasPanel');
  const btnComp = document.querySelector('#btnAbrirComponentesPanel');
  const btnOp = document.querySelector('#btnAbrirOperacionesPanel');
  const btnHerrTop = document.querySelector('#btnHerrPanelTop');
  const btnCompTop = document.querySelector('#btnCompPanelTop');
  const btnEspTop = document.querySelector('#btnEspPanelTop');
  const btnOpTop = document.querySelector('#btnOpPanelTop');
  const btnEspCritTop = document.querySelector('#btnEspCriticasPanelTop');




  if (btnHerr) btnHerr.onclick = () => abrirHerramientas(idestacion, cve);
  if (btnComp) btnComp.onclick = () => abrirComponentes(idestacion, cve);
  if (btnOp) btnOp.onclick = () => abrirOperaciones(idestacion, cve);
  if (btnHerrTop) btnHerrTop.onclick = () => abrirHerramientas(idestacion, cve);
  if (btnEspTop) {
    btnEspTop.onclick = () => abrirEspecificaciones(idestacion, cve);
  }
  if (btnEspTop) btnEspTop.onclick = () => abrirEspecificaciones(idestacion, cve);
  if (btnOpTop) btnOpTop.onclick = () => abrirOperaciones(idestacion, cve);



  if (btnEspCritTop) {
    btnEspCritTop.onclick = () => abrirEspecificacionesCriticasEstacion(idestacion, cve);
  }

  inicializarControlesInspeccionEstacion(idestacion);
  renderAyudasVisualesEstacion(idestacion);

  await refrescarEstadoEstacion(idestacion);
  await cargarSubensamblesPanel(idestacion, tieneSub);

}




function setText(selector, value) {
  const el = document.querySelector(selector);
  if (el) el.textContent = value && String(value).trim() !== '' ? value : '-';
}

async function cargarDetalleEstacionPanel(idestacion) {
  const card = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`);
  if (!card) return;

  subensambleSeleccionadoActual = null;

  const inputSub = document.getElementById('id_subensamble_actual');
  if (inputSub) inputSub.value = '';

  const cve = card.dataset.cve || '';
  const nombre = card.dataset.nombre || '';
  const proceso = card.dataset.proceso || '';
  const tieneSub = Number(card.dataset.tieneSubensamble || 0);
  const estampado = Number(card.dataset.estampado || 0);
  const tiempoAjuste = card.dataset.tiempoAjuste || '-';
  const calidad = Number(card.dataset.calidad || 0);

  setText('#titleEstacionDetalle', `Estación principal: ${cve}`);
  setText('#detCodigoEstacion', cve);
  setText('#detAreaEstacion', nombre);
  setText('#detTipoEstacion', 'Estación principal');
  setText('#detDescEstacion', proceso);
  setText('#detTiempoAjusteEstacion', tiempoAjuste);

  document.getElementById('idestacion_actual').value = idestacion;

  const chkVin = document.querySelector('#chkVinDetalle');
  if (chkVin) {
    chkVin.checked = estampado === 1;
    chkVin.onchange = function () {
      marcarVinDesdePanel(idestacion, this.checked);
    };
  }

  const chkCalidad = document.querySelector('#chkRequiereInspeccion');
  if (chkCalidad) chkCalidad.checked = calidad === 1;

  const liSubTab = document.querySelector('#li-tab-det-sub');
  if (liSubTab) liSubTab.classList.toggle('d-none', tieneSub !== 1);

  const btnHerr = document.querySelector('#btnAbrirHerramientasPanel');
  const btnComp = document.querySelector('#btnAbrirComponentesPanel');
  const btnOp = document.querySelector('#btnAbrirOperacionesPanel');
  const btnHerrTop = document.querySelector('#btnHerrPanelTop');
  const btnCompTop = document.querySelector('#btnCompPanelTop');
  const btnEspTop = document.querySelector('#btnEspPanelTop');
  const btnOpTop = document.querySelector('#btnOpPanelTop');
  const btnEspCritTop = document.querySelector('#btnEspCriticasPanelTop');

  if (btnHerr) btnHerr.onclick = () => abrirHerramientas(idestacion, cve);
  if (btnComp) btnComp.onclick = () => abrirComponentes(idestacion, cve);
  if (btnOp) btnOp.onclick = () => abrirOperaciones(idestacion, cve);
  if (btnHerrTop) btnHerrTop.onclick = () => abrirHerramientas(idestacion, cve);
  if (btnCompTop) btnCompTop.onclick = () => abrirComponentes(idestacion, cve);
  if (btnEspTop) btnEspTop.onclick = () => abrirEspecificaciones(idestacion, cve);
  if (btnOpTop) btnOpTop.onclick = () => abrirOperaciones(idestacion, cve);
  if (btnEspCritTop) btnEspCritTop.onclick = () => abrirEspecificacionesCriticasEstacion(idestacion, cve);

  inicializarControlesInspeccionEstacion(idestacion);
  renderAyudasVisualesEstacion(idestacion);

  await refrescarEstadoEstacion(idestacion);
  await cargarSubensamblesPanel(idestacion, tieneSub);
}

function inicializarControlesInspeccionEstacion(idestacion) {
  const chk = document.querySelector('#chkRequiereInspeccion');
  const bloque = document.querySelector('#bloqueInspeccionConfig');
  const selTipo = document.querySelector('#selTipoInspeccion');
  const txtGrupo = document.querySelector('#txtGrupoPdi');
  const selSeveridad = document.querySelector('#selSeveridadInspeccion');
  const txtNota = document.querySelector('#txtNotaInspeccion');
  const btnAgregarPunto = document.querySelector('#btnAgregarPuntoPdi');

  if (!chk || !bloque) return;

  const card = document.querySelector(
    `#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`
  );

  const calidadCard = card ? (parseInt(card.dataset.calidad, 10) || 0) : 0;

  if (!inspeccionConfigEstacion[idestacion]) {
    inspeccionConfigEstacion[idestacion] = {
      requiere_inspeccion: calidadCard,
      tipo_inspeccion: '',
      grupo_pdi: '',
      severidad: '',
      nota: ''
    };
  }

  if (!puntosPdiEstacion[idestacion]) {
    puntosPdiEstacion[idestacion] = [];
  }

  const cfg = inspeccionConfigEstacion[idestacion];

  chk.checked = Number(cfg.requiere_inspeccion || 0) === 1;
  bloque.classList.toggle('d-none', !chk.checked);

  if (selTipo) selTipo.value = cfg.tipo_inspeccion || '';
  if (txtGrupo) txtGrupo.value = cfg.grupo_pdi || '';
  if (selSeveridad) selSeveridad.value = cfg.severidad || '';
  if (txtNota) txtNota.value = cfg.nota || '';

  chk.onchange = function () {
    const valor = this.checked ? 1 : 0;

    inspeccionConfigEstacion[idestacion].requiere_inspeccion = valor;
    bloque.classList.toggle('d-none', !this.checked);

    const card = document.querySelector(
      `#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`
    );
    if (card) {
      card.setAttribute('data-calidad', String(valor));
    }
  };

  if (selTipo) {
    selTipo.onchange = () => {
      inspeccionConfigEstacion[idestacion].tipo_inspeccion = selTipo.value;
    };
  }

  if (txtGrupo) {
    txtGrupo.oninput = () => {
      inspeccionConfigEstacion[idestacion].grupo_pdi = txtGrupo.value;
    };
  }

  if (selSeveridad) {
    selSeveridad.onchange = () => {
      inspeccionConfigEstacion[idestacion].severidad = selSeveridad.value;
    };
  }

  if (txtNota) {
    txtNota.oninput = () => {
      inspeccionConfigEstacion[idestacion].nota = txtNota.value;
    };
  }

  if (btnAgregarPunto) {
    btnAgregarPunto.onclick = function () {
      agregarPuntoPdiEstacion(idestacion);
    };
  }

  inicializarPdiDesdeLocalOBd(idestacion, idproducto);
}

function agregarPuntoPdiEstacion(idestacion) {
  Swal.fire({
    title: 'Agregar punto PDI',
    html: `
      <input id="swalPuntoTitulo" class="swal2-input" placeholder="Punto a revisar">
      <input id="swalPuntoCategoria" class="swal2-input" placeholder="Categoría">
      <input id="swalPuntoCriterio" class="swal2-input" placeholder="Criterio de aceptación">
      <select id="swalPuntoSeveridad" class="swal2-input">
        <option value="">Severidad</option>
        <option value="critica">Crítica</option>
        <option value="mayor">Mayor</option>
        <option value="menor">Menor</option>
      </select>
      <select id="swalPuntoEvidencia" class="swal2-input">
        <option value="">Evidencia</option>
        <option value="no">No</option>
        <option value="foto">Foto</option>
        <option value="firma">Firma</option>
        <option value="lectura">Lectura</option>
      </select>
    `,
    focusConfirm: false,
    preConfirm: () => {
      const titulo = document.getElementById('swalPuntoTitulo').value.trim();
      const categoria = document.getElementById('swalPuntoCategoria').value.trim();
      const criterio = document.getElementById('swalPuntoCriterio').value.trim();
      const severidad = document.getElementById('swalPuntoSeveridad').value.trim();
      const evidencia = document.getElementById('swalPuntoEvidencia').value.trim();

      if (!titulo || !criterio) {
        Swal.showValidationMessage('Punto a revisar y criterio son obligatorios');
        return false;
      }

      return { titulo, categoria, criterio, severidad, evidencia };
    }
  }).then(result => {
    if (!result.isConfirmed) return;

    const idproducto = document.getElementById('idproducto_proceso')?.value || '';

    if (!puntosPdiEstacion[idestacion]) {
      puntosPdiEstacion[idestacion] = [];
    }

    puntosPdiEstacion[idestacion].push({
      punto: result.value.titulo,
      categoria: result.value.categoria,
      criterio: result.value.criterio,
      severidad: result.value.severidad,
      evidencia: result.value.evidencia
    });

    guardarPdiEnLocal(idproducto, idestacion);
    // renderPuntosPdiEstacion(idestacion);
    // renderModalPdi(idestacion);

    inicializarPdiDesdeLocalOBd(idestacion, idproducto);
  });
}

function renderPuntosPdiEstacion(idestacion) {
  const tbody = document.querySelector('#tbodyPuntosPdi');
  const msg = document.querySelector('#msgSinPuntosPdi');
  if (!tbody || !msg) return;

  const lista = puntosPdiEstacion[idestacion] || [];
  tbody.innerHTML = '';

  if (lista.length === 0) {
    msg.classList.remove('d-none');
    return;
  }

  msg.classList.add('d-none');

  lista.forEach((item, idx) => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${idx + 1}</td>
      <td class="fw-semibold"><i class="bi bi-check2-square text-primary me-1"></i>${item.punto || '-'}</td>
      <td>${item.categoria || '-'}</td>
      <td>${item.criterio || '-'}</td>
      <td>${item.severidad || '-'}</td>
      <td>${item.evidencia || '-'}</td>
      <td class="text-end">
        <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
          onclick="eliminarPuntoPdiEstacion('${idestacion}', ${idx})">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function eliminarPuntoPdiEstacion(idestacion, idx) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';

  if (!puntosPdiEstacion[idestacion]) return;
  puntosPdiEstacion[idestacion].splice(idx, 1);

  guardarPdiEnLocal(idproducto, idestacion);
  renderPuntosPdiEstacion(idestacion);
  // renderModalPdi(idestacion);
}


function initAyudasVisualesEventos() {
  const btn = document.querySelector('#btnAgregarAyudaVisual');
  if (!btn || btn.dataset.boundAyuda) return;

  btn.addEventListener('click', function () {
    if (!estacionSeleccionadaActual) {
      Swal.fire('Atención', 'Primero selecciona una estación.', 'warning');
      return;
    }

    Swal.fire({
      title: 'Agregar ayuda visual',
      html: `
        <input id="swalTituloAyuda" class="swal2-input" placeholder="Título de la ayuda visual">
        <select id="swalTipoAyuda" class="swal2-input">
          <option value="">Seleccione tipo</option>
          <option value="pdf">PDF</option>
          <option value="imagen">Imagen</option>
        </select>
        <input id="swalArchivoAyuda" class="swal2-input" placeholder="Nombre temporal o ruta del archivo">
      `,
      focusConfirm: false,
      preConfirm: () => {
        const titulo = document.getElementById('swalTituloAyuda').value.trim();
        const tipo = document.getElementById('swalTipoAyuda').value.trim();
        const archivo = document.getElementById('swalArchivoAyuda').value.trim();

        if (!titulo || !tipo || !archivo) {
          Swal.showValidationMessage('Todos los campos son obligatorios');
          return false;
        }

        return { titulo, tipo, archivo };
      }
    }).then(result => {
      if (!result.isConfirmed) return;

      if (!ayudasVisualesEstacion[estacionSeleccionadaActual]) {
        ayudasVisualesEstacion[estacionSeleccionadaActual] = [];
      }

      ayudasVisualesEstacion[estacionSeleccionadaActual].push({
        titulo: result.value.titulo,
        tipo: result.value.tipo,
        archivo: result.value.archivo
      });

      renderAyudasVisualesEstacion(estacionSeleccionadaActual);
    });
  });

  btn.dataset.boundAyuda = '1';
}

function renderAyudasVisualesEstacion(idestacion) {
  const cont = document.querySelector('#listaAyudasVisuales');
  if (!cont) return;

  const lista = ayudasVisualesEstacion[idestacion] || [];


  if (lista.length === 0) {
    cont.innerHTML = '<div class="text-muted small">No hay ayudas visuales registradas.</div>';
    return;
  }

  cont.innerHTML = lista.map((item, idx) => {


    const rutaArchivo = `${base_url}/Assets/uploads/ayudas_estacion/${item.archivo}`;

    return `
  <div class="d-flex justify-content-between align-items-center border rounded-3 px-3 py-2 mb-2 bg-body">
      
    <div>
      <div class="fw-semibold">
        <i class="bi bi-file-earmark-richtext me-1 text-primary"></i>${item.titulo}
      </div>

   

      <small class="text-muted">
        ${String(item.tipo || '').toUpperCase()} · 
        ${item.archivo}
      </small>
    </div>

    <div class="d-flex align-items-center gap-2">
      
      ${item.archivo
        ? `<button type="button" 
                class="btn btn-sm btn-soft-info"
                onclick="verDocumento('${rutaArchivo}')">
                <i class="ri-eye-fill align-bottom"></i>
             </button>`
        : ''
      }

      <button type="button" class="btn btn-soft-danger btn-sm"
        onclick="eliminarAyudaVisualEstacion('${idestacion}', ${idx}, ${item.idayuda})">
        <i class="ri-delete-bin-line"></i>
      </button>

    </div>

  </div>
`;
  }).join('');
}

// function eliminarAyudaVisualEstacion(idestacion, idx, idayuda) {
//   if (!ayudasVisualesEstacion[idestacion]) return;
//   ayudasVisualesEstacion[idestacion].splice(idx, 1);
//   renderAyudasVisualesEstacion(idestacion);
// }

function eliminarAyudaVisualEstacion(idestacion, idx, idayuda) {
  if (!ayudasVisualesEstacion[idestacion]) return;

  fetch(base_url + '/Plan_confproductosv1/eliminarAyudaVisual', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      idayuda: idayuda
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.status) {
        ayudasVisualesEstacion[idestacion].splice(idx, 1);
        renderAyudasVisualesEstacion(idestacion);
      } else {
        alert(data.msg || 'No se pudo actualizar el estado de la ayuda visual');
      }
    })
    .catch(error => {
      console.error('Error al actualizar ayuda visual:', error);
    });
}



function renderAyudasSubensamble(idsubensamble) {
  const cont = document.querySelector('#listaAyudasSubensamble');
  const msg = document.querySelector('#mensajeSinAyudasSub');
  const badge = document.querySelector('#countAyudasSubensamble');

  if (!cont) return;

  const lista = ayudasVisualesSubensamble[idsubensamble] || [];
  badge.textContent = lista.length;

  if (lista.length === 0) {
    cont.innerHTML = '';
    msg?.classList.remove('d-none');
    return;
  }

  msg?.classList.add('d-none');

  cont.innerHTML = lista.map((item, index) => `
    <div class="d-flex justify-content-between align-items-center border rounded-4 px-3 py-2 mb-2 bg-body-subtle">
      <div class="d-flex align-items-center gap-3">
        <div class="fs-4 text-primary">
          <i class="bi ${obtenerIconoAyuda(item.tipo)}"></i>
        </div>
        <div>
          <div class="fw-semibold">${item.titulo || 'Sin título'}</div>
          <small class="text-body-secondary">
            ${String(item.tipo || '').toUpperCase()} · ${item.archivo || ''}
          </small>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2">
        ${item.archivo
      ? `<a href="${base_url}/Uploads/subensambles/${item.archivo}" target="_blank" class="btn btn-sm btn-soft-info">
                <i class="ri-eye-fill align-bottom text-muted"></i>
              </a>`
      : ''
    }

        <button type="button" class="btn btn-soft-danger btn-sm"
          onclick="eliminarAyudaSubensamble('${idsubensamble}', ${index}, ${item.idaysubayuda || 0})">
          <i class="ri-delete-bin-line"></i>
        </button>
      </div>
    </div>
  `).join('');
}

function guardarAyudaVisualSubensamble() {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  const idsubensamble = document.getElementById('id_subensamble_actual')?.value || '';
  const titulo = document.getElementById('txtTituloAyudaSub')?.value.trim() || '';
  const tipo = document.getElementById('selTipoAyudaSub')?.value || '';
  const fileInput = document.getElementById('fileAyudaSub');

  if (!idsubensamble) {
    alert('No se encontró el subensamble.');
    return;
  }

  if (!titulo) {
    alert('Debes capturar el título.');
    return;
  }

  if (!tipo) {
    alert('Debes seleccionar el tipo.');
    return;
  }

  if (!fileInput || !fileInput.files.length) {
    alert('Debes seleccionar un archivo.');
    return;
  }

  const archivo = fileInput.files[0];
  const formData = new FormData();
  formData.append('productoid', idproducto);
  formData.append('subensambleid', idsubensamble);
  formData.append('titulo', titulo);
  formData.append('tipo', tipo);
  formData.append('archivo', archivo);

  fetch(base_url + '/plan_confproductosv1/setAyudaVisualSubensamble', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        if (!ayudasVisualesSubensamble[idsubensamble]) {
          ayudasVisualesSubensamble[idsubensamble] = [];
        }

        ayudasVisualesSubensamble[idsubensamble].push(data.item);
        renderAyudasSubensamble(idsubensamble);
        limpiarFormularioAyudaSub();
      } else {
        alert(data.msg || 'No se pudo guardar la ayuda visual.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Ocurrió un error al guardar la ayuda visual.');
    });
}

function limpiarFormularioAyudaSub() {
  const titulo = document.getElementById('txtTituloAyudaSub');
  const tipo = document.getElementById('selTipoAyudaSub');
  const archivo = document.getElementById('fileAyudaSub');

  if (titulo) titulo.value = '';
  if (tipo) tipo.value = '';
  if (archivo) archivo.value = '';
}

function eliminarAyudaSubensamble(idsubensamble, index, idaysubayuda) {
  const ayuda = ayudasVisualesSubensamble[idsubensamble]?.[index] || null;
  if (!ayuda || !ayudasVisualesSubensamble[idsubensamble]) return;

  fetch(base_url + '/plan_confproductosv1/delAyudaVisualSubensamble', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      idaysubayuda: idaysubayuda
    })
  })
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        ayudasVisualesSubensamble[idsubensamble].splice(index, 1);
        renderAyudasSubensamble(idsubensamble);
      } else {
        alert(data.msg || 'No se pudo desactivar la ayuda visual.');
      }
    })
    .catch(err => {
      console.error(err);
      alert('Ocurrió un error al eliminar la ayuda visual.');
    });
}

function cargarAyudasVisualesSubensamble(idsubensamble) {
  fetch(base_url + '/plan_confproductosv1/getAyudasVisualesSubensamble/' + idsubensamble)
    .then(res => res.json())
    .then(data => {
      if (data.status) {
        ayudasVisualesSubensamble[idsubensamble] = data.data || [];
        renderAyudasSubensamble(idsubensamble);
      } else {
        ayudasVisualesSubensamble[idsubensamble] = [];
        renderAyudasSubensamble(idsubensamble);
      }
    })
    .catch(err => {
      console.error(err);
      ayudasVisualesSubensamble[idsubensamble] = [];
      renderAyudasSubensamble(idsubensamble);
    });
}

function obtenerIconoAyuda(tipo) {
  switch ((tipo || '').toLowerCase()) {
    case 'pdf': return 'bi-file-earmark-pdf';
    case 'imagen': return 'bi-image';
    case 'video': return 'bi-camera-video';
    case 'documento': return 'bi-file-earmark-text';
    default: return 'bi-file-earmark';
  }
}


async function cargarSubensamblesPanel(idestacion, tieneSub) {
  const wrapVacio = document.querySelector('#wrapSubensamblesVacio');
  const panel = document.querySelector('#panelSubensambleUnico');

  if (!wrapVacio || !panel) return;

  panel.classList.add('d-none');
  wrapVacio.classList.add('d-none');
  subensambleSeleccionadoActual = null;

  if (Number(tieneSub) !== 1) {
    wrapVacio.classList.add('d-none');
    panel.classList.add('d-none');
    return;
  }

  const idProductoProceso = parseInt(document.getElementById('idproducto_proceso')?.value || 0);
  const url = getControllerBase() + '/getSubensamblesEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idProductoProceso);

  const res = await fetchJSON(url, { method: 'GET' }, { useLoading: false });

  let sub = null;

  if (res && res.status) {
    if (Array.isArray(res.data) && res.data.length > 0) {
      sub = res.data[0];
    } else if (res.data && !Array.isArray(res.data)) {
      sub = res.data;
    }
  }

  if (!sub) {
    wrapVacio.classList.remove('d-none');
    wrapVacio.textContent = 'Esta estación tiene habilitado subensamble, pero aún no existe registro configurado.';
    return;
  }

  subensamblesCache[idestacion] = sub;
  panel.classList.remove('d-none');

  seleccionarSubensambleEnPanel(idestacion, sub.idsubensamble);
}

function seleccionarSubensambleEnPanel(idestacion, idsubensamble) {
  subensambleSeleccionadoActual = String(idsubensamble);

  const inputSub = document.getElementById('id_subensamble_actual');
  if (inputSub) {
    inputSub.value = idsubensamble || '';
  }

  cargarAyudasVisualesSubensamble(idsubensamble);

  document.querySelectorAll(`#rutaSubWrap-${idestacion} .subensamble-box-mini`).forEach(item => {
    item.classList.toggle('active', String(item.dataset.idsubensamble) === String(idsubensamble));
  });

  cargarDetalleSubensamble(idestacion, idsubensamble);
}

async function cargarDetalleSubensambleold(idestacion, idsubensamble) {
  const panel = document.querySelector('#panelSubensambleUnico');
  if (!panel) return;

  const sub = subensamblesCache[idestacion];
  if (!sub || String(sub.idsubensamble) !== String(idsubensamble)) return;

  const detProceso = document.querySelector('#detSubProceso');
  const detEstandar = document.querySelector('#detSubEstandar');
  const detTiempo = document.querySelector('#detSubTiempo');
  const detEstado = document.querySelector('#detSubEstado');

  if (detProceso) detProceso.textContent = sub.proceso || '-';
  if (detEstandar) detEstandar.textContent = sub.estandar || '-';
  if (detTiempo) detTiempo.textContent = sub.tiempo_ajuste || '-'; // ya no mostrar tiempo de ajuste en subensamble
  if (detEstado) {
    detEstado.closest('.col')?.classList.add('d-none'); // ocultamos estado
  }

  const mHerr = document.querySelector('#metricSubHerramientas');
  const mComp = document.querySelector('#metricSubComponentes');
  const mOp = document.querySelector('#metricSubOperaciones');
  const mEsp = document.querySelector('#metricSubSubensambles');

  if (mHerr) mHerr.textContent = Number(sub.total_herramientas || 0);
  if (mComp) mComp.textContent = Number(sub.total_componentes || 0);
  if (mOp) mOp.textContent = Number(sub.total_operaciones || 0);
  if (mEsp) mEsp.textContent = Number(sub.total_especificaciones || 0);

  const btnSubHerr = document.querySelector('#btnSubHerramientas');
  const btnSubComp = document.querySelector('#btnSubComponentes');
  const btnSubOp = document.querySelector('#btnSubOperaciones');
  const btnSubEspCrit = document.querySelector('#btnSubEspCriticas');
  const btnSubEsp = document.querySelector('#btnSubEspecificaciones');
  const btnEdit = document.querySelector('#btnEditarSubensambleUnico');
  const btnDel = document.querySelector('#btnEliminarSubensambleUnico');

  if (btnSubHerr) btnSubHerr.onclick = () => abrirHerramientasSubensamble(idsubensamble, sub.proceso || '');
  if (btnSubComp) btnSubComp.onclick = () => abrirComponentesSubensamble(idsubensamble, sub.proceso || '');
  if (btnSubOp) btnSubOp.onclick = () => abrirEspecificacionesSubensamble(idsubensamble, sub.proceso || '');

  if (btnSubEspCrit) {
    btnSubEspCrit.onclick = () => abrirEspecificacionesCriticasSubensamble(idsubensamble, sub.proceso || '');
  }

  // if (btnSubEsp) btnSubEsp.onclick = () => abrirEspecificacionesSubensamble(idsubensamble, sub.proceso || '');
  if (btnEdit) btnEdit.onclick = () => editarSubensamble(idsubensamble);
  if (btnDel) btnDel.onclick = () => eliminarSubensamble(idsubensamble);
}





async function cargarDetalleSubensamble(idestacion, idsubensamble) {
  const panel = document.querySelector('#panelSubensambleUnico');
  if (!panel) return;

  const sub = subensamblesCache[idestacion];
  if (!sub || String(sub.idsubensamble) !== String(idsubensamble)) return;

  const nombreSub = sub.nombre_estacion || sub.nombre_subensamble || sub.nombre || 'Subensamble';
  const procesoSub = sub.proceso || '-';
  const estandarSub = sub.estandar || '-';
  const tiempoSub = sub.tiempo_ajuste || '-';

  setText('#titleSubensambleDetalle', `Subensamble: ${nombreSub}`);
  setText('#detSubNombre', nombreSub);
  setText('#detSubProceso', procesoSub);
  setText('#detSubEstandar', estandarSub);
  setText('#detSubTiempo', tiempoSub);

  const detEstado = document.querySelector('#detSubEstado');
  if (detEstado) detEstado.closest('.col')?.classList.add('d-none');

  const mHerr = document.querySelector('#metricSubHerramientas');
  const mComp = document.querySelector('#metricSubComponentes');
  const mOp = document.querySelector('#metricSubOperaciones');
  const mEsp = document.querySelector('#metricSubEspecificaciones');
  const mSub = document.querySelector('#metricSubSubensambles');

  if (mHerr) mHerr.textContent = Number(sub.total_herramientas || 0);
  if (mComp) mComp.textContent = Number(sub.total_componentes || 0);
  if (mOp) mOp.textContent = Number(sub.total_operaciones || 0);
  if (mEsp) mEsp.textContent = Number(sub.total_especificaciones || 0);


  if (mSub) mSub.textContent = Number(sub.total_subensambles || 1);

  const btnSubHerr = document.querySelector('#btnSubHerramientas');
  const btnSubComp = document.querySelector('#btnSubComponentes');
  const btnSubOp = document.querySelector('#btnSubOperaciones');
  const btnSubEspCrit = document.querySelector('#btnSubEspCriticas');
  const btnEdit = document.querySelector('#btnEditarSubensambleUnico');
  const btnDel = document.querySelector('#btnEliminarSubensambleUnico');

  if (btnSubHerr) btnSubHerr.onclick = () => abrirHerramientasSubensamble(idsubensamble, procesoSub);
  if (btnSubComp) btnSubComp.onclick = () => abrirComponentesSubensamble(idsubensamble, procesoSub);
  if (btnSubOp) btnSubOp.onclick = () => abrirEspecificacionesSubensamble(idsubensamble, procesoSub);
  if (btnSubEspCrit) btnSubEspCrit.onclick = () => abrirEspecificacionesCriticasSubensamble(idsubensamble, procesoSub);
  if (btnEdit) btnEdit.onclick = () => editarSubensamble(idsubensamble);
  if (btnDel) btnDel.onclick = () => eliminarSubensamble(idsubensamble);
}

function limpiarPanelDetalleEstacion() {
  const title = document.querySelector('#titleEstacionDetalle');
  if (title) title.textContent = 'Estación: Selecciona una estación';

  ['#detCodigoEstacion', '#detAreaEstacion', '#detDescEstacion', '#detTiempoAjusteEstacion'].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.textContent = '-';
  });

  const detTipo = document.querySelector('#detTipoEstacion');
  if (detTipo) detTipo.textContent = 'Estación de Ensamble';

  ['#metricHerramientas', '#metricComponentes', '#metricOperaciones', '#metricSubensambles'].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.textContent = '0';
  });

  ['#metricSubHerramientas', '#metricSubComponentes', '#metricSubOperaciones', '#metricSubEspecificaciones'].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.textContent = '0';
  });

  const chkVin = document.querySelector('#chkVinDetalle');
  if (chkVin) chkVin.checked = false;

  const chkInsp = document.querySelector('#chkRequiereInspeccion');
  const bloqueInsp = document.querySelector('#bloqueInspeccionConfig');
  if (chkInsp) chkInsp.checked = false;
  if (bloqueInsp) bloqueInsp.classList.add('d-none');

  const listaAyudas = document.querySelector('#listaAyudasVisuales');
  if (listaAyudas) {
    listaAyudas.innerHTML = 'No hay ayudas visuales registradas.';
  }

  const wrap = document.querySelector('#wrapSubensamblesVacio');
  if (wrap) {
    wrap.classList.add('d-none');
    wrap.textContent = '';
  }

  const panelSub = document.querySelector('#panelSubensambleUnico');
  if (panelSub) panelSub.classList.add('d-none');

  const liSubTab = document.querySelector('#li-tab-det-sub');
  if (liSubTab) liSubTab.classList.add('d-none');

  const btnEliminar = document.querySelector('#btnEliminarEstacionPanel');
  if (btnEliminar) btnEliminar.disabled = true;

  const inputEstacionActual = document.querySelector('#idestacion_actual');
  if (inputEstacionActual) inputEstacionActual.value = '';
}


function setPanelDetalleActivo(activo = false) {
  const empty = document.querySelector('#panelDetalleEmpty');
  const content = document.querySelector('#tabContentDetalleEstacion');
  const btnEliminar = document.querySelector('#btnEliminarEstacionPanel');

  if (empty) empty.classList.toggle('d-none', !!activo);
  if (content) content.classList.toggle('d-none', !activo);
  if (btnEliminar) btnEliminar.disabled = !activo;
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

function moverArriba(btn) {
  const card = btn.closest('.ruta-card-mini');
  const contenedor = document.querySelector('#listaRutaCards');
  if (!card || !contenedor) return;

  const prev = card.previousElementSibling;
  if (!prev) return;

  contenedor.insertBefore(card, prev);

  reindexarRutaVisual();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}

function moverAbajo(btn) {
  const card = btn.closest('.ruta-card-mini');
  const contenedor = document.querySelector('#listaRutaCards');
  if (!card || !contenedor) return;

  const next = card.nextElementSibling;
  if (!next) return;

  contenedor.insertBefore(next, card);

  reindexarRutaVisual();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}

function eliminarDeRuta(btn) {
  const card = btn.closest('.ruta-card-mini');
  if (!card) return;

  eliminarDeRutaDesdeCard(card);
}

function eliminarDeRutaDesdeCard(card) {
  if (!card) return;

  const idestacion = String(card.getAttribute('data-idestacion') || '').trim();
  const iddetalle = Number(card.getAttribute('data-iddetalle') || 0);

  if (iddetalle > 0 && idestacion) {
    const ya = estacionesEliminadas.some(x => Number(x.iddetalle) === iddetalle);
    if (!ya) estacionesEliminadas.push({ iddetalle, idestacion, orden: 0 });
  }

  if (idestacion) desbloquearBotonEstacionPorId(idestacion);

  card.remove();

  // if (String(estacionSeleccionadaActual) === String(idestacion)) {
  //   estacionSeleccionadaActual = null;
  //   limpiarPanelDetalleEstacion();

  //   const first = document.querySelector('#listaRutaCards .ruta-card-mini');
  //   if (first) seleccionarEstacionRuta(first.dataset.idestacion);
  // }

  const totalRestantes = document.querySelectorAll('#listaRutaCards .ruta-card-mini').length;
  if (totalRestantes === 0) {
    setPanelDetalleActivo(false);
  }



  if (String(estacionSeleccionadaActual) === String(idestacion)) {
    estacionSeleccionadaActual = null;
    limpiarPanelDetalleEstacion();

    const first = document.querySelector('#listaRutaCards .ruta-card-mini');
    if (first) {
      seleccionarEstacionRuta(first.dataset.idestacion);
    } else {
      setPanelDetalleActivo(false);
    }
  }

  reindexarRutaVisual();
  actualizarPlaceholderRuta();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}


function moverEstacionActual(direction) {
  if (!estacionSeleccionadaActual) return;

  const card = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(estacionSeleccionadaActual))}"]`);
  const contenedor = document.querySelector('#listaRutaCards');
  if (!card || !contenedor) return;

  if (direction === 'up') {
    const prev = card.previousElementSibling;
    if (prev) contenedor.insertBefore(card, prev);
  }

  if (direction === 'down') {
    const next = card.nextElementSibling;
    if (next) contenedor.insertBefore(next, card);
  }

  reindexarRutaVisual();
  actualizarInputHiddenRuta();
  actualizarCountRuta();
}

function eliminarEstacionSeleccionadaActual() {
  if (!estacionSeleccionadaActual) return;

  const card = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(estacionSeleccionadaActual))}"]`);
  if (!card) return;

  eliminarDeRutaDesdeCard(card);
}

function marcarVinDesdePanel(idestacion, checked) {
  const actual = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`);
  if (!actual) return;

  if (checked) {
    document.querySelectorAll('#listaRutaCards .ruta-card-mini').forEach(card => {
      card.setAttribute('data-estampado', card === actual ? '1' : '0');
    });
  } else {
    actual.setAttribute('data-estampado', '0');
  }
}


function marcarCalidadDesdePanel(idestacion, checked) {
  const actual = document.querySelector(`#listaRutaCards .ruta-card-mini[data-idestacion="${CSS.escape(String(idestacion))}"]`);
  if (!actual) return;

  if (checked) {
    document.querySelectorAll('#listaRutaCards .ruta-card-mini').forEach(card => {
      card.setAttribute('data-calidad', card === actual ? '1' : '0');
    });
  } else {
    actual.setAttribute('data-calidad', '0');
  }
}

function abrirOperaciones(idestacion, cve_estacion) {
  Swal.fire('Operaciones', `Aquí abriremos el modal de operaciones para la estación ${cve_estacion}`, 'info');
}

function abrirSubensamble(idestacion, cve_estacion) {
  Swal.fire('Subensambles', `Aquí abriremos el modal de alta/edición de subensamble para la estación ${cve_estacion}`, 'info');
}

function moverSubensambleArriba(idsubensamble) {

}

function moverSubensambleAbajo(idsubensamble) {

}

function editarSubensamble(idsubensamble) {

}

function eliminarSubensamble(idsubensamble) {

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
  if (!btnOrigen) return;

  const est = {
    idestacion: idEstacion,
    cve_estacion: btnOrigen.getAttribute('data-cve') || '',
    nombre_estacion: btnOrigen.getAttribute('data-nombre') || '',
    proceso: btnOrigen.getAttribute('data-proceso') || '',
    herramientas: Number(btnOrigen.getAttribute('data-herramientas') || 0),
    tiene_subensamble: Number(btnOrigen.getAttribute('data-tiene-subensamble') || 0)
  };

  agregarEstacionARuta(est, btnOrigen);
}


// ======================================================================
//  ESPECIFICACIONES (MODAL + DATATABLE)
// ======================================================================
function abrirEspecificaciones(idestacion, cve_estacion) {
  if (!requireRutaProductoOrWarn()) return;

  configurarModalEspecificaciones({
    tipo: 'estacion',
    idestacion,
    idsubensamble: 0,
    nombre: cve_estacion,
    es_critica: 0
  });
}

function abrirEspecificacionesCriticasEstacion(idestacion, cve_estacion) {
  if (!requireRutaProductoOrWarn()) return;

  configurarModalEspecificaciones({
    tipo: 'estacion',
    idestacion,
    idsubensamble: 0,
    nombre: cve_estacion,
    es_critica: 1
  });
}


function cargarEspecificaciones(idEstacion, esCritica = 0) {
  const idProductoProceso = parseInt(document.getElementById('idproducto_proceso')?.value || 0);
  estacionActual = parseInt(idEstacion || 0);

  if (!estacionActual || !idProductoProceso) return;

  const url = getControllerBase()
    + '/getEspecificaciones/'
    + estacionActual
    + '/'
    + idProductoProceso
    + '?es_critica='
    + encodeURIComponent(Number(esCritica || 0));

  cargarTablaEspecificaciones(url);
}


function cargarTablaEspecificaciones(url) {
  if (tableEspecifica) {
    tableEspecifica.ajax.url(url).load();
    return;
  }

  tableEspecifica = $('#tableEspecificaciones').DataTable({
    responsive: true,
    processing: true,
    serverSide: false,
    destroy: true,
    ajax: {
      url: url,
      dataSrc: function (json) {
        return (json && json.status && Array.isArray(json.data)) ? json.data : [];
      }
    },
    columns: [
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
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    const inputCritica = document.querySelector('#es_critica_especificacion');
    const esCritica = inputCritica ? Number(inputCritica.value || 0) : 0;

    const ajaxUrl = base_url + '/Plan_confproductosv1/delEspecificacion';

    const strData =
      "idespecificacion=" + encodeURIComponent(idespecificacion) +
      "&es_critica=" + encodeURIComponent(esCritica);

    const objData = await xhrRequest({
      method: "POST",
      url: ajaxUrl,
      data: strData,
      headers: { "Content-type": "application/x-www-form-urlencoded" },
      responseType: "json",
      useLoading: true
    });

    if (objData && objData.status) {
      Swal.fire("¡Operación exitosa!", objData.msg, "success");

      if (tableEspecifica) {
        tableEspecifica.ajax.reload();
      }

    } else {
      Swal.fire("Atención!", objData?.msg || "Error al eliminar", "error");
    }
  });
}


async function fntEditEspecificacion(idespecificacion) {
  const btnTextEsp = document.querySelector('#btnTextEspecificacion');
  if (btnTextEsp) btnTextEsp.innerHTML = "Actualizar";

  const inputCritica = document.querySelector('#es_critica_especificacion');
  const esCritica = inputCritica ? Number(inputCritica.value || 0) : 0;

  const ajaxUrl = getControllerBase() + '/getEspecificacion/' + idespecificacion + '/' + esCritica;

  const objData = await xhrRequest({
    method: "GET",
    url: ajaxUrl,
    responseType: "json",
    useLoading: true
  });

  if (objData && objData.status) {
    document.querySelector("#idespecificacion").value = objData.data.idespecificacion;
    document.querySelector("#txtEspecificacion").value = objData.data.especificacion;

    if (inputCritica) {
      inputCritica.value = Number(objData.data.es_critica || esCritica || 0);
    }

  } else {
    Swal.fire("Error", objData?.msg || "No se pudo cargar", "error");
  }
}


// ======================================================================
//  COMPONENTES (MODAL + CARGA + GUARDADO)
// ======================================================================
function abrirComponentes(idestacion, cve_estacion) {
  if (!requireRutaProductoOrWarn()) return;

  const idproducto = document.querySelector('#idproducto_proceso')?.value || '';

  setContextoCaptura('estacion', {
    idestacion: idestacion,
    nombre: cve_estacion
  });

  const inputProducto = document.querySelector('#componentes_producto');
  const inputEstacion = document.querySelector('#estacion_id');
  const inputSub = document.querySelector('#subensamble_id_comp');
  const inputTipo = document.querySelector('#tipo_contexto_comp');

  if (inputProducto) inputProducto.value = idproducto;
  if (inputEstacion) inputEstacion.value = idestacion;
  if (inputSub) inputSub.value = 0;
  if (inputTipo) inputTipo.value = 'estacion';

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

  const btnGuardar = document.querySelector('#btnGuardarTodo');
  if (btnGuardar) btnGuardar.textContent = 'Guardar todo';
}

async function fntAlmacenesHerramientas(selectedValue = "") {
  const selectAlmacenesLocal = document.querySelector('#listAlmacenesHerrSelect');
  if (!selectAlmacenesLocal) return;

  const ajaxUrl = base_url + '/Plan_confproductosv1/getSelectAlmacenes';
  const html = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "text", useLoading: true });

  if (typeof html === "string") {
    selectAlmacenesLocal.innerHTML = html;

    if (selectedValue !== "") {
      selectAlmacenesLocal.value = selectedValue;
    }
  }

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

  showLoading();
  return fetch(base_url + '/Plan_confproductosv1/getSelectComponentes')
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
      hideLoading();
      if (!sel.dataset.bound) {
        sel.addEventListener('change', function () {
          fntComponentes(this.value);
        });
        sel.dataset.bound = '1';
      }
    });
}


// ------------------------------------------------------------------------
//  MOSTRAR HERRAMIENTAS (LOADING)
// ------------------------------------------------------------------------
async function fntHerramientas(idAlmacen) {
  let ajaxUrl = base_url + '/Plan_confproductosv1/getHerramientas/' + idAlmacen;

  let objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });

  if (!objData || objData.status === false) {
    console.error("No se pudieron cargar herramientas:", objData?.msg || objData);
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
}


// ------------------------------------------------------------------------
//  MOSTRAR COMPONENTES (LOADING)
// ------------------------------------------------------------------------
async function fntComponentes(idAlmacen) {
  let ajaxUrl = base_url + '/Plan_confproductosv1/getComponentes/' + idAlmacen;

  let objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });

  if (!objData || objData.status === false) {
    console.error("No se pudieron cargar componentes:", objData?.msg || objData);
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

  btn.addEventListener('click', async function () {
    const selectAlmacen = document.querySelector('#listAlmaceneSCompSelect');
    const idAlmacen = selectAlmacen ? (selectAlmacen.value || '') : '';

    const inputProducto = document.querySelector('#componentes_producto');
    const idProducto = inputProducto ? (inputProducto.value || '') : '';

    const inputEstacion = document.querySelector('#estacion_id');
    const idEstacion = inputEstacion ? (inputEstacion.value || '') : '';

    const inputSub = document.querySelector('#subensamble_id_comp');
    const idSubensamble = inputSub ? (inputSub.value || '') : '';

    const inputTipo = document.querySelector('#tipo_contexto_comp');
    const tipoContexto = inputTipo ? (inputTipo.value || 'estacion') : 'estacion';

    if (!idProducto) {
      Swal.fire("Atención", "No se detectó el producto actual.", "warning");
      return;
    }

    if (tipoContexto === 'estacion' && !idEstacion) {
      Swal.fire("Atención", "No se detectó la estación actual.", "warning");
      return;
    }

    if (tipoContexto === 'subensamble' && !idSubensamble) {
      Swal.fire("Atención", "No se detectó el sub-ensamble actual.", "warning");
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
      idestacion: tipoContexto === 'estacion' ? idEstacion : 0,
      idsubensamble: tipoContexto === 'subensamble' ? idSubensamble : 0,
      tipo_contexto: tipoContexto,
      detalle_componentes: lista
    }];

    const formData = new FormData();
    formData.append('componentes', JSON.stringify(payload));

    const endpoint = tipoContexto === 'subensamble'
      ? getControllerBase() + '/setComponentesSubensamble'
      : getControllerBase() + '/setComponentesEstacion';

    const res = await fetchJSON(endpoint, {
      method: 'POST',
      body: formData
    }, { useLoading: true });

    if (res.status) {
      Swal.fire("¡Operación exitosa!", res.msg || "Guardado correctamente", "success");

      if (tipoContexto === 'subensamble') {
        await cargarComponentesGuardadosSubensamble(idSubensamble);
        await refrescarMetricasSubensamble(idEstacion, idSubensamble);
      } else {
        await cargarComponentesGuardadosEstacion(idEstacion);
        await refrescarEstadoEstacion(idEstacion);
      }

      $('#modalComponentes').modal('hide');
    } else {
      Swal.fire("Error", res.msg || "No se pudo guardar", "error");
    }
  });

  btn.dataset.boundSave = '1';
}

async function cargarComponentesGuardadosEstacion(idestacion) {
  const inputProducto = document.querySelector('#componentes_producto');
  const idProducto = inputProducto ? (inputProducto.value || '') : '';
  if (!idestacion || !idProducto) return;

  const url = base_url + '/Plan_confproductosv1/getComponentesEstacion/' + idestacion +
    '?idproducto=' + encodeURIComponent(idProducto);

  const res = await fetchJSON(url, { method: "GET" }, { useLoading: true });
  if (!res || !res.status) return;

  const idAlmacen = String(res.idalmacen || '');
  const data = Array.isArray(res.data) ? res.data : [];
  if (!idAlmacen) return;

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

  if (data.length === 0) {
    componentesSeleccionados = [];
    refrescarSeleccionadosComponentes();
    setTimeout(() => sincronizarBotonesCatalogoConSeleccionados(), 120);
    const btnGuardar = document.querySelector('#btnGuardarTodo');
    if (btnGuardar) btnGuardar.textContent = 'Guardar todo';
    return;
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

  setTimeout(() => refrescarEstadoEstacion(idestacion), 120);
}



// ABRIR MODAL HERRAMIENTAS
function abrirHerramientas(idestacion, cve_estacion) {
  if (!requireRutaProductoOrWarn()) return;

  const idproducto = document.querySelector('#idproducto_proceso')?.value || '';

  setContextoCaptura('estacion', {
    idestacion: idestacion,
    nombre: cve_estacion
  });

  const inputProducto = document.querySelector('#herramientas_producto');
  const inputEstacion = document.querySelector('#estacion_id_herr');
  const inputSub = document.querySelector('#subensamble_id_herr');
  const inputTipo = document.querySelector('#tipo_contexto_herr');

  if (inputProducto) inputProducto.value = idproducto;
  if (inputEstacion) inputEstacion.value = idestacion;
  if (inputSub) inputSub.value = 0;
  if (inputTipo) inputTipo.value = 'estacion';

  resetModalHerramientas();
  fntAlmacenesHerramientas();

  const title = document.querySelector('#titleModalHerramientas');
  if (title) title.innerHTML = "Herramientas - " + cve_estacion;

  $('#modalHerramientas').modal('show');
  cargarHerramientasGuardadasEstacion(idestacion);
}

function resetModalHerramientas() {
  const sel = document.querySelector('#listAlmacenesHerrSelect');
  if (sel) sel.value = '';

  herramientasSeleccionadas = [];

  if (dtCatalogHerramientas) dtCatalogHerramientas.clear().draw(false);
  if (dtSelectedHerramientas) dtSelectedHerramientas.clear().draw(false);

  const count = document.querySelector('#countSelectedHerr');
  if (count) count.textContent = '0';

  const btnGuardar = document.querySelector('#btnGuardarTodoHerramientas');
  if (btnGuardar) btnGuardar.textContent = 'Guardar todo';
}

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


document.getElementById('btnAbrirModalPdi').onclick = () => {
  const idestacion = document.getElementById('idestacion_actual').value;
  // renderModalPdi(idestacion);
  new bootstrap.Modal(document.getElementById('modalPdi')).show();
};

document.getElementById('btnAbrirModalAyudas').onclick = () => {
  const idestacion = document.getElementById('idestacion_actual').value;
  renderAyudas(idestacion);
  new bootstrap.Modal(document.getElementById('modalAyudas')).show();
};


async function guardarConfiguracionInspeccionEstacion(idestacion) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  if (!idproducto || !idestacion) return;

  const cfg = inspeccionConfigEstacion[idestacion] || {
    requiere_inspeccion: 0,
    tipo_inspeccion: '',
    grupo_pdi: '',
    severidad: '',
    nota: ''
  };

  const formData = new FormData();
  formData.append('idproducto', idproducto);
  formData.append('idestacion', idestacion);
  formData.append('requiere_inspeccion', cfg.requiere_inspeccion || 0);
  formData.append('tipo_inspeccion', cfg.tipo_inspeccion || '');
  formData.append('grupo_pdi', cfg.grupo_pdi || '');
  formData.append('severidad', cfg.severidad || '');
  formData.append('nota', cfg.nota || '');

  const res = await fetchJSON(base_url + '/Plan_confproductosv1/setInspeccionEstacion', {
    method: 'POST',
    body: formData
  }, { useLoading: true });

  return res;
}


document.getElementById('btnAgregarAyuda').onclick = function () {
  const idestacion = document.getElementById('idestacion_actual')?.value || '';
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';

  const titulo = document.getElementById('txtTituloAyuda').value.trim();
  const tipo = document.getElementById('selTipoAyuda').value.trim();
  const fileInput = document.getElementById('fileAyuda');
  const file = fileInput?.files?.[0] || null;

  if (!titulo || !tipo || !file || !idestacion || !idproducto) {
    Swal.fire('Atención', 'Completa todos los campos de ayuda visual.', 'warning');
    return;
  }

  if (!ayudasVisualesEstacion[idestacion]) {
    ayudasVisualesEstacion[idestacion] = [];
  }

  if (!ayudasVisualesPendientesFiles[idestacion]) {
    ayudasVisualesPendientesFiles[idestacion] = [];
  }

  const item = {
    titulo,
    tipo,
    archivo: file.name,
    _tmpId: `${Date.now()}_${Math.random().toString(36).slice(2, 8)}`
  };

  ayudasVisualesEstacion[idestacion].push(item);
  ayudasVisualesPendientesFiles[idestacion].push({
    tmpId: item._tmpId,
    file
  });

  guardarAyudasEnLocal(idproducto, idestacion);
  renderAyudas(idestacion);

  document.getElementById('txtTituloAyuda').value = '';
  document.getElementById('selTipoAyuda').value = '';
  document.getElementById('fileAyuda').value = '';
};

function renderAyudas(idestacion) {
  const lista = document.getElementById('listaAyudas');
  if (!lista) return;

  lista.innerHTML = '';

  const ayudas = ayudasVisualesEstacion[idestacion] || [];

  if (ayudas.length === 0) {
    lista.innerHTML = '<li class="list-group-item text-muted small">No hay ayudas visuales registradas.</li>';
    return;
  }

  ayudas.forEach((a, i) => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex justify-content-between align-items-center';

    li.innerHTML = `
      <div>
        <div class="fw-semibold">
          <i class="bi bi-file-earmark me-2"></i>${a.titulo}
        </div>
        <small class="text-muted">${a.tipo} · ${a.archivo}</small>
      </div>
      <button class="btn btn-soft-danger btn-sm" onclick="eliminarAyuda('${idestacion}', ${i}, ${a.idayuda})">
        <i class="ri-delete-bin-line"></i>
      </button>
    `;

    lista.appendChild(li);
  });
}



function eliminarAyuda(idestacion, index, idayuda) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  const ayuda = ayudasVisualesEstacion[idestacion]?.[index] || null;

  if (!ayuda || !ayudasVisualesEstacion[idestacion]) return;

  const eliminarLocalmente = () => {
    if (ayuda && ayudasVisualesPendientesFiles[idestacion]) {
      ayudasVisualesPendientesFiles[idestacion] = ayudasVisualesPendientesFiles[idestacion]
        .filter(x => x.tmpId !== ayuda._tmpId);
    }

    ayudasVisualesEstacion[idestacion].splice(index, 1);

    guardarAyudasEnLocal(idproducto, idestacion);
    renderAyudas(idestacion);
  };


  if (!idayuda || Number(idayuda) <= 0) {
    eliminarLocalmente();
    return;
  }

  fetch(base_url + '/Plan_confproductosv1/eliminarAyudaVisual', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      idayuda: idayuda
    })
  })
    .then(response => response.json())
    .then(data => {
      if (data.status) {
        eliminarLocalmente();
      } else {
        alert(data.msg || 'No se pudo desactivar la ayuda visual.');
      }
    })
    .catch(error => {
      console.error('Error al desactivar ayuda visual:', error);
      alert('Ocurrió un error al desactivar la ayuda visual.');
    });
}

async function guardarTodoAyudasEstacion(idestacion) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  if (!idproducto || !idestacion) {
    Swal.fire('Atención', 'Falta producto o estación.', 'warning');
    return;
  }

  const lista = ayudasVisualesEstacion[idestacion] || [];
  const filesPendientes = ayudasVisualesPendientesFiles[idestacion] || [];

  if (lista.length === 0) {
    Swal.fire('Atención', 'No hay ayudas visuales por guardar.', 'warning');
    return;
  }

  for (const item of lista) {
    if (item.idayuda) continue; // ya guardado en BD

    const fileInfo = filesPendientes.find(x => x.tmpId === item._tmpId);
    if (!fileInfo || !fileInfo.file) {
      Swal.fire('Error', `No se encontró el archivo real para "${item.titulo}".`, 'error');
      return;
    }

    const formData = new FormData();
    formData.append('idproducto', idproducto);
    formData.append('idestacion', idestacion);
    formData.append('titulo', item.titulo);
    formData.append('tipo', item.tipo);
    formData.append('archivo', fileInfo.file);

    const res = await fetchJSON(base_url + '/Plan_confproductosv1/setAyudaVisualEstacion', {
      method: 'POST',
      body: formData
    }, { useLoading: true });

    if (!res || !res.status) {
      guardarAyudasEnLocal(idproducto, idestacion);
      Swal.fire('Error', res?.msg || `No fue posible guardar "${item.titulo}".`, 'error');
      return;
    }

    item.idayuda = res.idayuda || null;
  }

  limpiarAyudasLocal(idproducto, idestacion);
  await cargarAyudasDesdeServidor(idestacion, idproducto);

  ayudasVisualesPendientesFiles[idestacion] = [];
  Swal.fire('Éxito', 'Ayudas visuales guardadas correctamente.', 'success');
}

async function cargarAyudasDesdeServidor(idestacion, idproducto) {
  try {
    const url = base_url + '/Plan_confproductosv1/getAyudasVisualesEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idproducto);

    // console.log('Consultando ayudas:', url);

    const res = await fetchJSON(url, { method: 'GET' }, { useLoading: false });

    // console.log('Respuesta ayudas visuales:', res);

    if (res && res.status && Array.isArray(res.data)) {
      ayudasVisualesEstacion[idestacion] = res.data.map(x => ({
        idayuda: x.idayuda || null,
        titulo: x.titulo || '',
        tipo: x.tipo || '',
        archivo: x.archivo || ''
      }));
    } else {
      ayudasVisualesEstacion[idestacion] = [];
    }

    renderAyudas(idestacion);
    renderAyudasVisualesEstacion(idestacion);

  } catch (error) {
    console.error('Error al cargar ayudas visuales:', error);
    ayudasVisualesEstacion[idestacion] = [];
    renderAyudas(idestacion);
    renderAyudasVisualesEstacion(idestacion);
  }
}

async function inicializarDatosLocalesOBdEstacion(idestacion) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  if (!idproducto || !idestacion) return;

  const pdiLocal = cargarPdiDesdeLocal(idproducto, idestacion);
  if (Array.isArray(pdiLocal) && pdiLocal.length > 0) {
    puntosPdiEstacion[idestacion] = pdiLocal;
    renderPuntosPdiEstacion(idestacion);
    // renderModalPdi(idestacion);
  } else {
    await cargarPdiDesdeServidorNuevo(idestacion, idproducto);
  }

  const ayudasLocal = cargarAyudasDesdeLocal(idproducto, idestacion);
  if (Array.isArray(ayudasLocal) && ayudasLocal.length > 0) {
    ayudasVisualesEstacion[idestacion] = ayudasLocal;
    renderAyudas(idestacion);
  } else {
    await cargarAyudasDesdeServidor(idestacion, idproducto);
  }
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
}

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

function prepararGuardarTodoHerramientas() {
  const btn = document.querySelector('#btnGuardarTodoHerramientas');
  if (!btn || btn.dataset.boundSaveHerr) return;

  btn.addEventListener('click', async function () {
    const selectAlmacen = document.querySelector('#listAlmacenesHerrSelect');
    const idAlmacen = selectAlmacen ? (selectAlmacen.value || '') : '';

    const inputProducto = document.querySelector('#herramientas_producto');
    const idProducto = inputProducto ? (inputProducto.value || '') : '';

    const inputEstacion = document.querySelector('#estacion_id_herr');
    const idEstacion = inputEstacion ? (inputEstacion.value || '') : '';

    const inputSub = document.querySelector('#subensamble_id_herr');
    const idSubensamble = inputSub ? (inputSub.value || '') : '';

    const inputTipo = document.querySelector('#tipo_contexto_herr');
    const tipoContexto = inputTipo ? (inputTipo.value || 'estacion') : 'estacion';

    if (!idProducto) {
      Swal.fire("Atención", "No se detectó el producto actual.", "warning");
      return;
    }

    if (tipoContexto === 'estacion' && !idEstacion) {
      Swal.fire("Atención", "No se detectó la estación actual.", "warning");
      return;
    }

    if (tipoContexto === 'subensamble' && !idSubensamble) {
      Swal.fire("Atención", "No se detectó el sub-ensamble actual.", "warning");
      return;
    }

    if (!idAlmacen) {
      Swal.fire("Atención", "Selecciona un almacén para guardar.", "warning");
      return;
    }

    const lista = herramientasSeleccionadas.map(x => ({
      inventarioid: x.inventarioid,
      cantidad: x.cantidad
    }));

    const payload = [{
      idalmacen: idAlmacen,
      idproducto: idProducto,
      idestacion: tipoContexto === 'estacion' ? idEstacion : 0,
      idsubensamble: tipoContexto === 'subensamble' ? idSubensamble : 0,
      tipo_contexto: tipoContexto,
      detalle_herramientas: lista
    }];

    const formData = new FormData();
    formData.append('herramientas', JSON.stringify(payload));

    const endpoint = tipoContexto === 'subensamble'
      ? getControllerBase() + '/setHerramientasSubensamble'
      : getControllerBase() + '/setHerramientasEstacion';

    const res = await fetchJSON(endpoint, {
      method: 'POST',
      body: formData
    }, { useLoading: true });

    if (res.status) {
      Swal.fire("¡Operación exitosa!", res.msg || "Guardado correctamente", "success");

      if (tipoContexto === 'subensamble') {
        await cargarHerramientasGuardadasSubensamble(idSubensamble);
        await refrescarMetricasSubensamble(idEstacion, idSubensamble);
      } else {
        await cargarHerramientasGuardadasEstacion(idEstacion);
        await refrescarEstadoEstacion(idEstacion);
      }

      $('#modalHerramientas').modal('hide');
    } else {
      Swal.fire("Error", res.msg || "No se pudo guardar", "error");
    }
  });

  btn.dataset.boundSaveHerr = '1';
}

async function cargarHerramientasGuardadasEstacion(idestacion) {
  const inputProducto = document.querySelector('#herramientas_producto');
  const idProducto = inputProducto ? (inputProducto.value || '') : '';
  if (!idestacion || !idProducto) return;

  const url = base_url + '/Plan_confproductosv1/getHerramientasEstacion/' + idestacion +
    '?idproducto=' + encodeURIComponent(idProducto);

  const res = await fetchJSON(url, { method: "GET" }, { useLoading: true });
  if (!res || !res.status) return;

  const idAlmacen = String(res.idalmacen || '');
  const data = Array.isArray(res.data) ? res.data : [];
  if (!idAlmacen) return;

  const sel = document.querySelector('#listAlmacenesHerrSelect');
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

  if (data.length === 0) {
    herramientasSeleccionadas = [];
    refrescarSeleccionadosHerramientas();
    setTimeout(() => sincronizarBotonesCatalogoHerrConSeleccionadas(), 120);
    const btnGuardar = document.querySelector('#btnGuardarTodoHerramientas');
    if (btnGuardar) btnGuardar.textContent = 'Guardar todo';
    return;
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
}


// ======================================================================
//  PAYLOAD RUTA
// ======================================================================
function construirPayloadRuta() {
  const plantaSel = document.querySelector('#listPlantasSelect');
  const lineaSel = document.querySelector('#listLineasSelect');
  const inpProd = document.querySelector('#idproducto_proceso');
  const contenedorRuta = document.querySelector('#listaRutaCards');

  const planta = plantaSel ? (plantaSel.value || '') : '';
  const linea = lineaSel ? (lineaSel.value || '') : '';
  const idproducto = inpProd ? (inpProd.value || '') : '';

  const cards = contenedorRuta ? Array.from(contenedorRuta.querySelectorAll('.ruta-card-mini[data-idestacion]')) : [];

  const actuales = cards.map((card, idx) => ({
    iddetalle: Number(card.getAttribute('data-iddetalle') || 0),
    idestacion: String(card.getAttribute('data-idestacion') || '').trim(),
    orden: idx + 1,
    estampado: Number(card.getAttribute('data-estampado') || 0),
    calidad: Number(card.getAttribute('data-calidad') || 0),
  })).filter(x => x.idestacion);

  const eliminadas = estacionesEliminadas.map(x => ({
    iddetalle: Number(x.iddetalle || 0),
    idestacion: String(x.idestacion || '').trim(),
    orden: 0
  })).filter(x => x.idestacion && x.iddetalle > 0);

  const map = new Map();
  eliminadas.forEach(x => map.set(x.idestacion, x));
  actuales.forEach(x => map.set(x.idestacion, x));

  const detalle_ruta = Array.from(map.values());

  return [{
    listPlantasSelect: planta,
    listLineasSelect: linea,
    idproducto_proceso: idproducto,
    detalle_ruta
  }];
}





async function fntReportProducto(idproducto) {
  if (!idproducto) return;

  const ajaxUrl = base_url + '/Plan_confproductosv1/getProductoReporte/' + idproducto;

  const objData = await xhrRequest({
    method: "GET",
    url: ajaxUrl,
    responseType: "json",
    useLoading: true
  });

  if (!objData || objData.status === false) {
    Swal.fire("Aviso", objData?.msg || "No se encontró la información del producto.", "warning");
    return;
  }

  const data = objData.data || objData;

  const logoUrl = base_url + '/Assets/images/ldr_logo_color.png';
  const logoBase64 = await urlToBase64(logoUrl);

  buildPdfProductoV1(data, logoBase64);
}

function buildPdfProductoV1(payload, logoBase64) {



  const p = payload?.producto || {};
  const doc = payload?.documentacion?.data || [];
  const dt = payload?.descriptiva_tecnica?.data || {};
  const ruta = payload?.producto_configurado?.data || {};
  const estaciones = Array.isArray(ruta?.estaciones_registradas) ? ruta.estaciones_registradas : [];

  const fmt = (v) => (v === null || v === undefined) ? "" : String(v);
  const safeList = (arr) => Array.isArray(arr) ? arr : [];

  const nowStr = () => {
    const d = new Date();
    const pad = (n) => String(n).padStart(2, "0");
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
  };

  // EstiloS
  const tableLayout = {
    hLineWidth: () => 0.5,
    vLineWidth: () => 0.5,
    hLineColor: () => "#d9d9d9",
    vLineColor: () => "#d9d9d9",
    paddingLeft: () => 6,
    paddingRight: () => 6,
    paddingTop: () => 4,
    paddingBottom: () => 4,
  };

  const sectionBar = (title) => ({
    table: { widths: ["*"], body: [[{ text: title, color: "#fff", bold: true, margin: [6, 5, 6, 5] }]] },
    layout: "noBorders",
    fillColor: "#8c8c8c",
    margin: [0, 10, 0, 6]
  });

  const headerRow = (cols, fillColor = "#f5f5f5") =>
    cols.map((t) => ({ text: t, bold: true, fillColor }));

  const emptyRow = (colCount, label = "Sin registros") => {
    const row = new Array(colCount).fill("");
    row[0] = { text: "—", alignment: "center" };
    row[1] = label;
    return row;
  };

  // ---------------------------
  // Contenido
  // ---------------------------
  const content = [];

  // ---------------------------
  // Encabezado
  // ---------------------------
  content.push({
    table: {
      widths: [70, "*", 170],
      body: [[
        logoBase64
          ? { image: logoBase64, width: 65, margin: [0, 0, 0, 0] }
          : { text: "", width: 65 },
        {
          text: "CONFIGURACIÓN DE PRODUCTO",
          alignment: "center",
          bold: true,
          color: "#777",
          fontSize: 12,
          margin: [0, 12, 0, 0]
        },
{
  stack: [
    {
      text: [
        { text: "Doc. Code: ", bold: true },
        "MRP-CP-V1.0"
      ]
    },
    {
      text: [
        { text: "Versión: ", bold: true },
        "1.0"
      ]
    },
    {
      text: [
        { text: "Fecha: ", bold: true },
        nowStr()
      ]
    }
  ],
  fontSize: 9,
  alignment: "right",
  margin: [0, 4, 0, 0]
}
      ]]
    },
    layout: {
      ...tableLayout,
      vLineWidth: () => 0,
      hLineWidth: (i, node) => (i === node.table.body.length ? 1 : 0),
      hLineColor: () => "#999"
    },
    margin: [0, 0, 0, 6]
  });

  // ---------------------------
  // Datos generales
  // ---------------------------
  content.push(sectionBar("DATOS GENERALES DEL PRODUCTO"));

  content.push({
    table: {
      widths: ["20%", "30%", "20%", "30%"],
      body: [
        ["Nombre / Descripción", fmt(p.descripcion), "CVE Producto", fmt(p.cve_producto)],
        ["Línea Producto", fmt(p.nombre_linea), "Fecha / Hora Registro", fmt(p.fecha_creacion)]
        // ["Fecha / Hora Registro", fmt(p.fecha_creacion), "Avance General", fmt(p.avance_general)]
      ]
    },
    layout: tableLayout
  });

  // ---------------------------
  // Documentación asociada
  // ---------------------------
  content.push(sectionBar("DOCUMENTACIÓN ASOCIADA"));

  const docBody = [
    headerRow(["#", "Tipo", "Descripción", "Archivo (Link)", "Fecha / Hora"], "#f5f5f5")
  ];

  if (safeList(doc).length) {
safeList(doc).forEach((x, idx) => {
  const fileName = fmt(x.ruta);


  const publicLink = base_url + "/Assets/uploads/doc_componentes/" + fileName;

  docBody.push([
    { text: String(idx + 1), alignment: "center" },
    fmt(x.tipo_documento),
    fmt(x.descripcion),
    fileName
      ? { 
          text: "Ver documento",
          link: publicLink,
          color: "#1a73e8",
          decoration: "underline"
        }
      : "",
    fmt(x.fecha_creacion)
  ]);
});

  } else {
    docBody.push(emptyRow(5));
  }

  content.push({
    table: { widths: [24, 80, "*", 100, 110], body: docBody },
    layout: tableLayout
  });

 
  content.push(sectionBar("DESCRIPTIVA TÉCNICA"));

  const descBody = [
    ["Marca", fmt(dt.marca), "Modelo", fmt(dt.modelo)],
    ["Largo total", fmt(dt.largo_total), "Distancia entre ejes", fmt(dt.distancia_ejes)],
    ["Peso bruto vehicular", fmt(dt.peso_bruto_vehicular), "Motor", fmt(dt.motor)],
    ["Cilindros", fmt(dt.cilindros), "Desplazamiento", fmt(dt.desplazamiento_c)],
    ["Tipo combustible", fmt(dt.tipo_combustible), "Potencia", fmt(dt.potencia)],
    ["Torque", fmt(dt.torque), "Transmisión", fmt(dt.transmision)],
    ["Eje delantero", fmt(dt.eje_delantero), "Suspensión delantera", fmt(dt.suspension_delantera)],
    ["Eje trasero", fmt(dt.eje_trasero), "Suspensión trasera", fmt(dt.suspension_trasera)],
    ["Llantas", fmt(dt.llantas), "Sistema frenos", fmt(dt.sistema_frenos)],
    ["Asistencias", fmt(dt.asistencias), "Sistema eléctrico", fmt(dt.sistema_electrico)],
    ["Capacidad combustible", fmt(dt.capacidad_combustible), "Dirección", fmt(dt.direccion)],
    ["Equipamiento", fmt(dt.equipamiento), "", ""],
  ];

  content.push({
    table: { widths: ["20%", "30%", "20%", "30%"], body: descBody },
    layout: tableLayout
  });

  // ---------------------------
  // Estaciones (por proceso)
  // ---------------------------
  estaciones.forEach((e) => {
    const estTitle = `ESTACIÓN ${fmt(e.orden)} – ${fmt(e.est_cve_estacion)} | ${fmt(e.est_nombre_estacion)}`;
    content.push(sectionBar(estTitle));

    // ========== Especificaciones críticas ==========
    const espList = safeList(e?.especificaciones?.data);
    const espBody = [
      headerRow(["#", "Especificación crítica", "Fecha / Hora"], "#EEF2F7")
    ];

    if (espList.length) {
      espList.forEach((x, i) => {
        espBody.push([
          { text: String(i + 1), alignment: "center" },
          fmt(x.especificacion),
          fmt(x.fecha_creacion)
        ]);
      });
    } else {
      espBody.push(emptyRow(3));
    }

    content.push({
      table: { widths: [24, "*", 140], body: espBody },
      layout: tableLayout
    });

    const compList = safeList(e?.componentes?.data);
    const compBody = [
      headerRow(["#", "Componente", "Cantidad", "Fecha / Hora"], "#EEF7F1")
    ];

    if (compList.length) {
      compList.forEach((x, i) => {
        compBody.push([
          { text: String(i + 1), alignment: "center" },
          fmt(x.nombre_componente),     
          fmt(x.cantidad),
          fmt(x.fecha_creacion)
        ]);
      });
    } else {
      compBody.push(emptyRow(4));
    }

    content.push({
      table: { widths: [24, "*", 70, 140], body: compBody },
      layout: tableLayout,
      margin: [0, 8, 0, 0]
    });


    const toolList = safeList(e?.herramientas?.data);
    const toolBody = [
      headerRow(["#", "Herramienta", "Cantidad", "Fecha / Hora"], "#F7F2EE")
    ];

    if (toolList.length) {
      toolList.forEach((x, i) => {
        toolBody.push([
          { text: String(i + 1), alignment: "center" },
          fmt(x.nombre_material),
          fmt(x.cantidad),
          fmt(x.fecha_creacion)
        ]);
      });
    } else {
      toolBody.push(emptyRow(4));
    }

    content.push({
      table: { widths: [24, "*", 70, 140], body: toolBody },
      layout: tableLayout,
      margin: [0, 8, 0, 0]
    });
  });


  content.push(sectionBar("CONTROL Y APROBACIÓN"));

  content.push({
    table: {
      widths: ["12%", "21%", "12%", "21%", "12%", "22%"],
      body: [
        ["Elaboró", "", "Revisó", "", "Aprobó", ""],
        [{ text: "Nombre / Firma / Fecha", color: "#777", fontSize: 8 }, "", { text: "Nombre / Firma / Fecha", color: "#777", fontSize: 8 }, "", { text: "Nombre / Firma / Fecha", color: "#777", fontSize: 8 }, ""]
      ]
    },
    layout: tableLayout
  });

  // Observaciones
  content.push(sectionBar("OBSERVACIONES / NOTAS"));
  content.push({
    table: { widths: ["*"], body: [[{ text: " ", margin: [0, 20, 0, 20] }]] },
    layout: tableLayout
  });

  // ---------------------------
  // Documento 
  // ---------------------------
  const docDefinition = {
    pageSize: "A4",
    pageMargins: [40, 50, 40, 70],
    defaultStyle: { fontSize: 9 },
    footer: function (currentPage, pageCount) {
      return {
        text: `Documento controlado – MRP LDR Solutions | Uso interno   •   Página ${currentPage} de ${pageCount}`,
        alignment: "center",
        fontSize: 8,
        color: "#777",
        margin: [40, 10, 40, 0]
      };
    },
    content
  };

  const filename = `CFG_PRODUCTO_${fmt(p.cve_producto || p.idproducto || "reporte")}.pdf`;
  pdfMake.createPdf(docDefinition).download(filename);
}


async function urlToBase64(url) {
  const res = await fetch(url, { cache: "no-store" });
  const blob = await res.blob();

  return await new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onload = () => resolve(reader.result);
    reader.onerror = reject;
    reader.readAsDataURL(blob);
  });
}

function abrirHerramientasSubensamble(idsubensamble, proceso) {
  if (!requireRutaProductoOrWarn()) return;

  const idproducto = document.querySelector('#idproducto_proceso')?.value || '';
  const idestacion = document.querySelector('#idestacion_actual')?.value || '';

  setContextoCaptura('subensamble', {
    idestacion: idestacion,
    idsubensamble: idsubensamble,
    nombre: proceso
  });

  const inputProducto = document.querySelector('#herramientas_producto');
  const inputEstacion = document.querySelector('#estacion_id_herr');
  const inputSub = document.querySelector('#subensamble_id_herr');
  const inputTipo = document.querySelector('#tipo_contexto_herr');

  if (inputProducto) inputProducto.value = idproducto;
  if (inputEstacion) inputEstacion.value = idestacion;
  if (inputSub) inputSub.value = idsubensamble;
  if (inputTipo) inputTipo.value = 'subensamble';

  resetModalHerramientas();
  fntAlmacenesHerramientas();

  const title = document.querySelector('#titleModalHerramientas');
  if (title) title.innerHTML = `Herramientas - Sub-ensamble ${proceso}`;

  $('#modalHerramientas').modal('show');
  cargarHerramientasGuardadasSubensamble(idsubensamble);
}

function abrirComponentesSubensamble(idsubensamble, proceso) {
  if (!requireRutaProductoOrWarn()) return;

  const idproducto = document.querySelector('#idproducto_proceso')?.value || '';
  const idestacion = document.querySelector('#idestacion_actual')?.value || '';

  setContextoCaptura('subensamble', {
    idestacion: idestacion,
    idsubensamble: idsubensamble,
    nombre: proceso
  });

  const inputProducto = document.querySelector('#componentes_producto');
  const inputEstacion = document.querySelector('#estacion_id');
  const inputSub = document.querySelector('#subensamble_id_comp');
  const inputTipo = document.querySelector('#tipo_contexto_comp');

  if (inputProducto) inputProducto.value = idproducto;
  if (inputEstacion) inputEstacion.value = idestacion;
  if (inputSub) inputSub.value = idsubensamble;
  if (inputTipo) inputTipo.value = 'subensamble';

  resetModalComponentes();
  fntAlmacenesComponentes();

  const title = document.querySelector('#titleModalComponentes');
  if (title) title.innerHTML = `Componentes - Sub-ensamble ${proceso}`;

  $('#modalComponentes').modal('show');
  cargarComponentesGuardadosSubensamble(idsubensamble);
}

function abrirEspecificacionesSubensamble(idsubensamble, proceso) {
  if (!requireRutaProductoOrWarn()) return;

  const idestacion = document.querySelector('#idestacion_actual')?.value || '';

  configurarModalEspecificaciones({
    tipo: 'subensamble',
    idestacion,
    idsubensamble,
    nombre: proceso,
    es_critica: 0
  });
}

function abrirEspecificacionesCriticasSubensamble(idsubensamble, proceso) {
  if (!requireRutaProductoOrWarn()) return;

  const idestacion = document.querySelector('#idestacion_actual')?.value || '';

  configurarModalEspecificaciones({
    tipo: 'subensamble',
    idestacion,
    idsubensamble,
    nombre: proceso,
    es_critica: 1
  });
}



function getPdiStorageKey(idproducto, idestacion) {
  return `mrp_pdi_${idproducto}_${idestacion}`;
}

function getAyudasStorageKey(idproducto, idestacion) {
  return `mrp_ayudas_${idproducto}_${idestacion}`;
}

function guardarPdiEnLocal(idproducto, idestacion) {
  const key = getPdiStorageKey(idproducto, idestacion);
  const data = puntosPdiEstacion[idestacion] || [];
  localStorage.setItem(key, JSON.stringify(data));
}

function cargarPdiDesdeLocal(idproducto, idestacion) {
  const key = getPdiStorageKey(idproducto, idestacion);
  const raw = localStorage.getItem(key);
  if (!raw) return [];
  try {
    return JSON.parse(raw) || [];
  } catch {
    return [];
  }
}

function limpiarPdiLocal(idproducto, idestacion) {
  const key = getPdiStorageKey(idproducto, idestacion);
  localStorage.removeItem(key);
}

function guardarAyudasEnLocal(idproducto, idestacion) {
  const key = getAyudasStorageKey(idproducto, idestacion);
  const data = ayudasVisualesEstacion[idestacion] || [];
  localStorage.setItem(key, JSON.stringify(data));
}

function cargarAyudasDesdeLocal(idproducto, idestacion) {
  const key = getAyudasStorageKey(idproducto, idestacion);
  const raw = localStorage.getItem(key);
  if (!raw) return [];
  try {
    return JSON.parse(raw) || [];
  } catch {
    return [];
  }
}

function limpiarAyudasLocal(idproducto, idestacion) {
  const key = getAyudasStorageKey(idproducto, idestacion);
  localStorage.removeItem(key);
}


function getControllerBase() {

  return base_url + '/Plan_confproductosv1';
}

function isDarkVelzonMode() {
  return document.documentElement.getAttribute('data-layout-mode') === 'dark'
    || document.documentElement.getAttribute('data-bs-theme') === 'dark'
    || document.documentElement.getAttribute('data-theme') === 'dark'
    || document.body.getAttribute('data-layout-mode') === 'dark';
}

function setContextoCaptura(tipo, opts = {}) {
  contextoCapturaActual = {
    tipo: tipo === 'subensamble' ? 'subensamble' : 'estacion',
    idestacion: Number(opts.idestacion || 0),
    idsubensamble: Number(opts.idsubensamble || 0),
    nombre: String(opts.nombre || ''),
    es_critica: Number(opts.es_critica || 0)
  };
}


function resetFormEspecificacion() {
  const txt = document.querySelector('#txtEspecificacion');
  const idEsp = document.querySelector('#idespecificacion');
  const idEspSub = document.querySelector('#idespecificacionsubensamble');
  const btnText = document.querySelector('#btnTextEspecificacion');

  if (txt) txt.value = '';
  if (idEsp) idEsp.value = 0;
  if (idEspSub) idEspSub.value = 0;
  if (btnText) btnText.innerHTML = 'Registrar';
}

function configurarModalEspecificaciones({
  tipo = 'estacion',
  idestacion = 0,
  idsubensamble = 0,
  nombre = '',
  es_critica = 0
}) {
  const idproducto = document.querySelector('#idproducto_proceso')?.value || '';
  const modal = document.getElementById('modalEspecificaciones');
  if (!modal) return false;

  setContextoCaptura(tipo, {
    idestacion,
    idsubensamble,
    nombre,
    es_critica
  });

  const inputProducto = modal.querySelector('#idproducto_especificacion');
  const inputEstacion = modal.querySelector('#idestacion');
  const inputSub = modal.querySelector('#idsubensamble_especificacion');
  const inputTipo = modal.querySelector('#tipo_contexto_especificacion');
  const inputCritica = modal.querySelector('#es_critica_especificacion');

  if (inputProducto) inputProducto.value = idproducto;
  if (inputEstacion) inputEstacion.value = idestacion || 0;
  if (inputSub) inputSub.value = tipo === 'subensamble' ? idsubensamble : 0;
  if (inputTipo) inputTipo.value = tipo;
  if (inputCritica) inputCritica.value = Number(es_critica || 0);

  resetFormEspecificacion();

  const title = document.querySelector('#titleModalEspecificaciones');
  const labelTipo = es_critica ? 'Especificaciones críticas' : 'Operaciones';
  const labelContexto = tipo === 'subensamble' ? 'Subensamble' : 'Estación';

  if (title) {
    title.innerHTML = `${labelTipo} - ${labelContexto} ${nombre}`;
  }

  const formTitle = modal.querySelector('h5');
  const formDesc = modal.querySelector('p.text-muted');

  if (formTitle) formTitle.textContent = es_critica ? 'Registro de especificaciones críticas' : 'Registro de operaciones';
  if (formDesc) {
    formDesc.textContent = es_critica
      ? `Captura las especificaciones críticas para ${tipo === 'subensamble' ? 'este subensamble' : 'esta estación'}`
      : `Captura las operaciones para ${tipo === 'subensamble' ? 'este subensamble' : 'esta estación'}`;
  }

  $('#modalEspecificaciones').modal('show');

  if (tipo === 'subensamble') {
    cargarEspecificacionesSubensamble(idsubensamble, es_critica);
  } else {
    cargarEspecificaciones(idestacion, es_critica);
  }

  return true;
}




function resetContextoCaptura() {
  let contextoCapturaActual = {
    tipo: 'estacion',
    idestacion: 0,
    idsubensamble: 0,
    nombre: '',
    es_critica: 0
  };
}

function esContextoSubensamble() {
  return String(contextoCapturaActual.tipo) === 'subensamble';
}


// ==========================================================
// PDI NUEVO - CONFIGURACIÓN POR ZONAS
// ==========================================================

function getPdiStorageKey(idproducto, idestacion) {
  return `mrp_pdi_config_${idproducto}_${idestacion}`;
}

function crearEstructuraPdiVacia() {
  return {
    idpdi: 0,
    zonas: []
  };
}

function ensurePdiConfig(idestacion) {
  if (!pdiConfigEstacion[idestacion]) {
    pdiConfigEstacion[idestacion] = crearEstructuraPdiVacia();
  }

  if (!Array.isArray(pdiConfigEstacion[idestacion].zonas)) {
    pdiConfigEstacion[idestacion].zonas = [];
  }

  return pdiConfigEstacion[idestacion];
}

function guardarPdiEnLocal(idproducto, idestacion) {
  const cfg = ensurePdiConfig(idestacion);
  localStorage.setItem(getPdiStorageKey(idproducto, idestacion), JSON.stringify(cfg));
}

function cargarPdiDesdeLocal(idproducto, idestacion) {
  try {
    const raw = localStorage.getItem(getPdiStorageKey(idproducto, idestacion));
    if (!raw) return null;

    const parsed = JSON.parse(raw);
    if (!parsed || typeof parsed !== 'object') return null;
    if (!Array.isArray(parsed.zonas)) parsed.zonas = [];
    return parsed;
  } catch (e) {
    return null;
  }
}

function limpiarPdiLocal(idproducto, idestacion) {
  localStorage.removeItem(getPdiStorageKey(idproducto, idestacion));
}

async function inicializarPdiDesdeLocalOBd(idestacion, idproducto) {
  const zonaActualAntes = String(zonaPdiSeleccionada || '');

  await cargarPdiDesdeServidorNuevo(idestacion, idproducto);

  const cfgBd = ensurePdiConfig(idestacion);

  if (!cfgBd.zonas.length) {
    const localData = cargarPdiDesdeLocal(idproducto, idestacion);
    if (localData && Array.isArray(localData.zonas) && localData.zonas.length) {
      pdiConfigEstacion[idestacion] = localData;
    }
  }

  const cfg = ensurePdiConfig(idestacion);

  if (cfg.zonas.length > 0) {
    conservarZonaSeleccionada(idestacion, zonaActualAntes);
  } else {
    zonaPdiSeleccionada = null;
  }

  renderListaZonasPdi(idestacion);
  renderDetalleZonaPdi(idestacion);
}

function initPdiModalEventos() {
  const btnNuevaZona = document.getElementById('btnNuevaZonaPdi');
  const btnGuardarZona = document.getElementById('btnGuardarZonaPdi');
  const btnEliminarZona = document.getElementById('btnEliminarZonaPdi');
  const btnNuevoPunto = document.getElementById('btnNuevoPuntoPdi');
  const btnGuardarTodo = document.getElementById('btnGuardarTodoPdi');

  if (btnNuevaZona && !btnNuevaZona.dataset.bound) {
    btnNuevaZona.addEventListener('click', function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      const idproducto = document.getElementById('idproducto_proceso')?.value || '';
      if (!idestacion || !idproducto) return;

      crearNuevaZonaPdi(idestacion);
      guardarPdiEnLocal(idproducto, idestacion);
    });
    btnNuevaZona.dataset.bound = '1';
  }

  if (btnGuardarZona && !btnGuardarZona.dataset.bound) {
    btnGuardarZona.addEventListener('click', function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      const idproducto = document.getElementById('idproducto_proceso')?.value || '';
      if (!idestacion || !idproducto) return;

      const ok = guardarZonaActualPdi(idestacion);
      if (!ok) return;

      guardarPdiEnLocal(idproducto, idestacion);
      renderListaZonasPdi(idestacion);
      renderDetalleZonaPdi(idestacion);

      Swal.fire({
        icon: 'success',
        title: 'Zona guardada',
        text: 'La zona se actualizó correctamente.',
        timer: 1000,
        showConfirmButton: false
      });
    });
    btnGuardarZona.dataset.bound = '1';
  }

  if (btnEliminarZona && !btnEliminarZona.dataset.bound) {
    btnEliminarZona.addEventListener('click', function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      const idproducto = document.getElementById('idproducto_proceso')?.value || '';
      if (!idestacion || !idproducto) return;

      eliminarZonaSeleccionadaPdi(idestacion);
      guardarPdiEnLocal(idproducto, idestacion);
    });
    btnEliminarZona.dataset.bound = '1';
  }

  if (btnNuevoPunto && !btnNuevoPunto.dataset.bound) {
    btnNuevoPunto.addEventListener('click', function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      const idproducto = document.getElementById('idproducto_proceso')?.value || '';
      if (!idestacion || !idproducto) return;

      const ok = guardarZonaActualPdi(idestacion);
      if (!ok) return;

      agregarNuevoPuntoPdi(idestacion);
      guardarPdiEnLocal(idproducto, idestacion);
    });
    btnNuevoPunto.dataset.bound = '1';
  }

  if (btnGuardarTodo && !btnGuardarTodo.dataset.bound) {
    btnGuardarTodo.addEventListener('click', function () {
      const idestacion = document.getElementById('idestacion_actual')?.value || '';
      guardarTodoPdiEstacion(idestacion);
    });
    btnGuardarTodo.dataset.bound = '1';
  }
}

function crearNuevaZonaPdi(idestacion) {
  const cfg = ensurePdiConfig(idestacion);

  const nuevaZona = {
    idzona: 0,
    _tmpid: `tmp_zona_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    nombre_zona: '',
    referencia: '',
    orden: cfg.zonas.length + 1,
    puntos: []
  };

  cfg.zonas.push(nuevaZona);
  zonaPdiSeleccionada = String(nuevaZona._tmpid);

  renderListaZonasPdi(idestacion);
  renderDetalleZonaPdi(idestacion);
}

function getZonaSeleccionadaPdi(idestacion) {
  const cfg = ensurePdiConfig(idestacion);

  return cfg.zonas.find(z =>
    String(z._tmpid || z.idzona) === String(zonaPdiSeleccionada)
  ) || null;
}

function seleccionarZonaPdi(idestacion, zonaKey) {
  zonaPdiSeleccionada = String(zonaKey);
  renderListaZonasPdi(idestacion);
  renderDetalleZonaPdi(idestacion);
}

function guardarZonaActualPdi(idestacion) {
  const zona = getZonaSeleccionadaPdi(idestacion);
  if (!zona) {
    Swal.fire('Atención', 'Primero crea o selecciona una zona.', 'warning');
    return false;
  }

  const txtZonaNombre = document.getElementById('txtZonaPdiNombre');
  const txtZonaReferencia = document.getElementById('txtZonaPdiReferencia');

  const nombre = txtZonaNombre?.value?.trim() || '';
  const referencia = txtZonaReferencia?.value?.trim() || '';

  if (!nombre) {
    Swal.fire('Atención', 'Debes capturar el nombre de la zona.', 'warning');
    return false;
  }

  zona.nombre_zona = nombre;
  zona.referencia = referencia;

  return true;
}

function eliminarZonaSeleccionadaPdi(idestacion) {
  const cfg = ensurePdiConfig(idestacion);

  if (!zonaPdiSeleccionada) {
    Swal.fire('Atención', 'No hay zona seleccionada.', 'warning');
    return;
  }

  cfg.zonas = cfg.zonas.filter(z => String(z._tmpid || z.idzona) !== String(zonaPdiSeleccionada));

  cfg.zonas.forEach((zona, i) => {
    zona.orden = i + 1;
  });

  zonaPdiSeleccionada = cfg.zonas.length
    ? String(cfg.zonas[0]._tmpid || cfg.zonas[0].idzona)
    : null;

  renderListaZonasPdi(idestacion);
  renderDetalleZonaPdi(idestacion);
}

function renderListaZonasPdi(idestacion) {
  const lista = document.getElementById('listaZonasPdi');
  const msg = document.getElementById('msgSinZonasPdi');
  if (!lista || !msg) return;

  const cfg = ensurePdiConfig(idestacion);
  lista.innerHTML = '';

  if (!cfg.zonas.length) {
    msg.classList.remove('d-none');
    return;
  }

  msg.classList.add('d-none');

  cfg.zonas
    .sort((a, b) => Number(a.orden || 0) - Number(b.orden || 0))
    .forEach((zona, index) => {
      const key = String(zona._tmpid || zona.idzona || `zona_${index}`);
      const puntos = Array.isArray(zona.puntos) ? zona.puntos.length : 0;
      const active = String(zonaPdiSeleccionada) === key;

      const div = document.createElement('div');
      div.className = `pdi-zona-item ${active ? 'active' : ''}`;
      div.innerHTML = `
        <div class="d-flex justify-content-between align-items-start gap-2">
          <div class="flex-grow-1">
            <div class="pdi-zona-title">
              <i class="ri-map-pin-2-line me-1 text-primary"></i>
              ${escapeHtml(zona.nombre_zona || `Zona ${index + 1}`)}
            </div>
            <div class="pdi-zona-ref">
              ${escapeHtml(zona.referencia || 'Sin referencia')}
            </div>
          </div>
          <span class="pdi-zona-badge">${puntos}</span>
        </div>
      `;

      div.addEventListener('click', function () {
        seleccionarZonaPdi(idestacion, key);
      });

      lista.appendChild(div);
    });
}

function renderDetalleZonaPdi(idestacion) {
  const txtZonaNombre = document.getElementById('txtZonaPdiNombre');
  const txtZonaReferencia = document.getElementById('txtZonaPdiReferencia');
  const tbody = document.getElementById('tbodyModalPdi');
  const msg = document.getElementById('msgPdiVacio');

  if (!txtZonaNombre || !txtZonaReferencia || !tbody || !msg) return;

  const zona = getZonaSeleccionadaPdi(idestacion);

  if (!zona) {
    txtZonaNombre.value = '';
    txtZonaReferencia.value = '';
    tbody.innerHTML = '';
    msg.style.display = 'block';
    msg.textContent = 'No hay zona seleccionada.';
    return;
  }

  txtZonaNombre.value = zona.nombre_zona || '';
  txtZonaReferencia.value = zona.referencia || '';

  tbody.innerHTML = '';

  if (!Array.isArray(zona.puntos) || !zona.puntos.length) {
    msg.style.display = 'block';
    msg.textContent = 'No hay puntos registrados en esta zona.';
    return;
  }

  msg.style.display = 'none';

  zona.puntos
    .sort((a, b) => Number(a.orden || 0) - Number(b.orden || 0))
    .forEach((punto, index) => {
      const tr = document.createElement('tr');

      tr.innerHTML = `
        <td class="text-center fw-semibold">${index + 1}</td>
        <td>
          <input type="text"
                 class="form-control form-control-sm pdi-punto-input"
                 data-index="${index}"
                 value="${escapeAttr(punto.punto || '')}"
                 placeholder="Describe el punto a inspeccionar">
        </td>

        <td class="d-none">
    <input class="form-check-input pdi-check"
           type="checkbox"
           data-index="${index}"
           data-field="check_china">
</td>

<td class="d-none">
    <input class="form-check-input pdi-check"
           type="checkbox"
           data-index="${index}"
           data-field="check_mexico"
           checked>
</td>

<td class="d-none">
    <input class="form-check-input pdi-check"
           type="checkbox"
           data-index="${index}"
           data-field="check_i1">
</td>

<td class="d-none">
    <input class="form-check-input pdi-check"
           type="checkbox"
           data-index="${index}"
           data-field="check_i2">
</td>

<td class="d-none">
    <input class="form-check-input pdi-check"
           type="checkbox"
           data-index="${index}"
           data-field="check_i3">
</td>

<td class="d-none">
    <input class="form-check-input pdi-check"
           type="checkbox"
           data-index="${index}"
           data-field="check_i4">
</td>

        <td class="text-center">
          <div class="btn-group btn-group-sm pdi-action-btns">
            <button type="button" class="btn btn-soft-secondary" onclick="moverPuntoPdi('${idestacion}', ${index}, 'up')" title="Subir">
              <i class="ri-arrow-up-s-line"></i>
            </button>
            <button type="button" class="btn btn-soft-secondary" onclick="moverPuntoPdi('${idestacion}', ${index}, 'down')" title="Bajar">
              <i class="ri-arrow-down-s-line"></i>
            </button>
            <button type="button" class="btn btn-soft-danger" onclick="eliminarPuntoPdi('${idestacion}', ${index})" title="Eliminar">
              <i class="ri-delete-bin-line"></i>
            </button>
          </div>
        </td>
      `;

      tbody.appendChild(tr);
    });

  bindTablaPdiEventos(idestacion);
}

function bindTablaPdiEventos(idestacion) {
  const tbody = document.getElementById('tbodyModalPdi');
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  if (!tbody) return;

  tbody.querySelectorAll('.pdi-punto-input').forEach(input => {
    input.addEventListener('input', function () {
      const zona = getZonaSeleccionadaPdi(idestacion);
      if (!zona) return;

      const index = Number(this.dataset.index);
      if (!zona.puntos[index]) return;

      zona.puntos[index].punto = this.value;
      guardarPdiEnLocal(idproducto, idestacion);
      renderListaZonasPdi(idestacion);
    });
  });

  tbody.querySelectorAll('.pdi-check').forEach(chk => {
    chk.addEventListener('change', function () {
      const zona = getZonaSeleccionadaPdi(idestacion);
      if (!zona) return;

      const index = Number(this.dataset.index);
      const field = this.dataset.field;
      if (!zona.puntos[index]) return;

      zona.puntos[index][field] = this.checked ? 1 : 0;
      guardarPdiEnLocal(idproducto, idestacion);
    });
  });
}

function agregarNuevoPuntoPdi(idestacion) {
  const zona = getZonaSeleccionadaPdi(idestacion);
  if (!zona) {
    Swal.fire('Atención', 'Primero guarda o selecciona una zona.', 'warning');
    return;
  }

  if (!Array.isArray(zona.puntos)) zona.puntos = [];

  zona.puntos.push({
    idpuntopdi: 0,
    _tmppunto: `tmp_punto_${Date.now()}_${Math.random().toString(36).slice(2, 7)}`,
    punto: '',
    orden: zona.puntos.length + 1,
    check_china: 0,
    check_mexico: 1,
    check_i1: 0,
    check_i2: 0,
    check_i3: 0,
    check_i4: 0
  });

  renderDetalleZonaPdi(idestacion);
  renderListaZonasPdi(idestacion);
}

function eliminarPuntoPdi(idestacion, index) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  const zona = getZonaSeleccionadaPdi(idestacion);
  if (!zona || !Array.isArray(zona.puntos)) return;

  zona.puntos.splice(index, 1);

  zona.puntos.forEach((p, i) => {
    p.orden = i + 1;
  });

  guardarPdiEnLocal(idproducto, idestacion);
  renderDetalleZonaPdi(idestacion);
  renderListaZonasPdi(idestacion);
}

function moverPuntoPdi(idestacion, index, direccion) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  const zona = getZonaSeleccionadaPdi(idestacion);
  if (!zona || !Array.isArray(zona.puntos)) return;

  if (direccion === 'up' && index > 0) {
    [zona.puntos[index - 1], zona.puntos[index]] = [zona.puntos[index], zona.puntos[index - 1]];
  }

  if (direccion === 'down' && index < zona.puntos.length - 1) {
    [zona.puntos[index + 1], zona.puntos[index]] = [zona.puntos[index], zona.puntos[index + 1]];
  }

  zona.puntos.forEach((p, i) => {
    p.orden = i + 1;
  });

  guardarPdiEnLocal(idproducto, idestacion);
  renderDetalleZonaPdi(idestacion);
}

async function guardarTodoPdiEstacion(idestacion) {
  const idproducto = document.getElementById('idproducto_proceso')?.value || '';
  if (!idproducto || !idestacion) {
    Swal.fire('Atención', 'Falta producto o estación.', 'warning');
    return;
  }

  const zonaActualAntes = String(zonaPdiSeleccionada || '');

  if (zonaPdiSeleccionada) {
    const ok = guardarZonaActualPdi(idestacion);
    if (!ok) return;
  }

  const cfg = ensurePdiConfig(idestacion);

  const zonas = (cfg.zonas || [])
    .map((zona, zIndex) => ({
      idzona: Number(zona.idzona || 0),
      _tmpid: String(zona._tmpid || ''),
      nombre_zona: String(zona.nombre_zona || '').trim(),
      referencia: String(zona.referencia || '').trim(),
      orden: zIndex + 1,
      puntos: (zona.puntos || [])
        .filter(p => String(p.punto || '').trim() !== '')
        .map((p, pIndex) => ({
          idpuntopdi: Number(p.idpuntopdi || 0),
          punto: String(p.punto || '').trim(),
          orden: pIndex + 1,
          check_china: Number(p.check_china || 0),
          check_mexico: Number(p.check_mexico || 0),
          check_i1: Number(p.check_i1 || 0),
          check_i2: Number(p.check_i2 || 0),
          check_i3: Number(p.check_i3 || 0),
          check_i4: Number(p.check_i4 || 0)
        }))
    }))
    .filter(z => z.nombre_zona !== '');

  // if (!zonas.length) {
  //   Swal.fire('Atención', 'Debes registrar al menos una zona de inspección.', 'warning');
  //   return;
  // }

  // const totalPuntos = zonas.reduce((acc, z) => acc + (z.puntos?.length || 0), 0);
  // if (!totalPuntos) {
  //   Swal.fire('Atención', 'Debes registrar al menos un punto de inspección.', 'warning');
  //   return;
  // }

  const payload = [{
    idproducto: parseInt(idproducto, 10),
    idestacion: parseInt(idestacion, 10),
    zonas
  }];

  const formData = new FormData();
  formData.append('pdi_config', JSON.stringify(payload));

  const res = await fetchJSON(base_url + '/Plan_confproductosv1/setPdiCompletoEstacion', {
    method: 'POST',
    body: formData
  }, { useLoading: true });

  if (res && res.status) {



    if (res.desactivar_pdi === true) {
      const chkCalidad = document.querySelector('#chkRequiereInspeccion');

      if (chkCalidad) {
        chkCalidad.checked = false;

        // Por si tienes eventos change escuchando este check
        chkCalidad.dispatchEvent(new Event('change', { bubbles: true }));
      }

    }

    construirPayloadRuta()
    await guardarRutaProductoDesdePdi();

    limpiarPdiLocal(idproducto, idestacion);

    await cargarPdiDesdeServidorNuevo(idestacion, idproducto);

    // intentar conservar exactamente la misma zona
    conservarZonaSeleccionada(idestacion, zonaActualAntes);

    renderListaZonasPdi(idestacion);
    renderDetalleZonaPdi(idestacion);

    Swal.fire('Éxito', res.msg || 'PDI guardado correctamente.', 'success');
  } else {
    guardarPdiEnLocal(idproducto, idestacion);
    Swal.fire('Error', res?.msg || 'No fue posible guardar el PDI.', 'error');
  }
}

async function cargarPdiDesdeServidorNuevo(idestacion, idproducto) {
  const zonaActualAntes = String(zonaPdiSeleccionada || '');

  const url = base_url + '/Plan_confproductosv1/getPdiCompletoEstacion/' + idestacion + '?idproducto=' + encodeURIComponent(idproducto);

  const res = await fetchJSON(url, { method: 'GET' }, { useLoading: false });

  if (res && res.status && res.data && Array.isArray(res.data.zonas)) {
    pdiConfigEstacion[idestacion] = {
      idpdi: Number(res.data.idpdi || 0),
      zonas: res.data.zonas.map((z, zi) => ({
        idzona: Number(z.idzona || 0),
        _tmpid: `db_zona_${z.idzona || zi + 1}`,
        nombre_zona: z.nombre_zona || '',
        referencia: z.referencia || '',
        orden: Number(z.orden || zi + 1),
        puntos: Array.isArray(z.puntos) ? z.puntos.map((p, pi) => ({
          idpuntopdi: Number(p.idpuntopdi || 0),
          _tmppunto: `db_punto_${p.idpuntopdi || pi + 1}`,
          punto: p.punto || '',
          orden: Number(p.orden || pi + 1),
          check_china: Number(p.check_china || 0),
          check_mexico: Number(p.check_mexico || 0),
          check_i1: Number(p.check_i1 || 0),
          check_i2: Number(p.check_i2 || 0),
          check_i3: Number(p.check_i3 || 0),
          check_i4: Number(p.check_i4 || 0)
        })) : []
      }))
    };
  } else {
    pdiConfigEstacion[idestacion] = crearEstructuraPdiVacia();
  }

  conservarZonaSeleccionada(idestacion, zonaActualAntes);
}

function escapeHtml(str) {
  return String(str || '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function escapeAttr(str) {
  return escapeHtml(str);
}


function conservarZonaSeleccionada(idestacion, preferredKey = null) {
  const cfg = ensurePdiConfig(idestacion);
  if (!cfg || !Array.isArray(cfg.zonas) || cfg.zonas.length === 0) {
    zonaPdiSeleccionada = null;
    return;
  }

  const keyBuscada = String(preferredKey || zonaPdiSeleccionada || '');

  if (keyBuscada) {
    const encontrada = cfg.zonas.find(z =>
      String(z.idzona || '') === keyBuscada ||
      String(z._tmpid || '') === keyBuscada
    );

    if (encontrada) {
      zonaPdiSeleccionada = String(encontrada.idzona || encontrada._tmpid);
      return;
    }
  }

  zonaPdiSeleccionada = String(cfg.zonas[0].idzona || cfg.zonas[0]._tmpid);
}


async function cargarComponentesGuardadosSubensamble(idsubensamble) {
  const inputProducto = document.querySelector('#componentes_producto');
  const idProducto = inputProducto ? (inputProducto.value || '') : '';
  if (!idsubensamble || !idProducto) return;

  const url = getControllerBase() + '/getComponentesSubensamble/' + idsubensamble +
    '?idproducto=' + encodeURIComponent(idProducto);

  const res = await fetchJSON(url, { method: "GET" }, { useLoading: true });
  if (!res || !res.status) {
    componentesSeleccionados = [];
    refrescarSeleccionadosComponentes();
    return;
  }

  const idAlmacen = String(res.idalmacen || '');
  const data = Array.isArray(res.data) ? res.data : [];
  if (!idAlmacen) return;

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
  if (btnGuardar) btnGuardar.textContent = data.length ? 'Actualizar todo' : 'Guardar todo';
}

async function cargarHerramientasGuardadasSubensamble(idsubensamble) {
  const inputProducto = document.querySelector('#herramientas_producto');
  const idProducto = inputProducto ? (inputProducto.value || '') : '';
  if (!idsubensamble || !idProducto) return;

  const url = getControllerBase() + '/getHerramientasSubensamble/' + idsubensamble +
    '?idproducto=' + encodeURIComponent(idProducto);

  const res = await fetchJSON(url, { method: "GET" }, { useLoading: true });
  if (!res || !res.status) {
    herramientasSeleccionadas = [];
    refrescarSeleccionadosHerramientas();
    return;
  }

  const idAlmacen = String(res.idalmacen || '');
  const data = Array.isArray(res.data) ? res.data : [];
  if (!idAlmacen) return;

  const sel = document.querySelector('#listAlmacenesHerrSelect');
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
  if (btnGuardar) btnGuardar.textContent = data.length ? 'Actualizar todo' : 'Guardar todo';
}


function cargarEspecificacionesSubensamble(idSubensamble, esCritica = 0) {
  const idProductoProceso = parseInt(document.getElementById('idproducto_proceso')?.value || 0);
  if (!idSubensamble || !idProductoProceso) return;

  const url = getControllerBase()
    + '/getEspecificacionesSubensamble/'
    + idSubensamble
    + '/'
    + idProductoProceso
    + '?es_critica='
    + encodeURIComponent(Number(esCritica || 0));

  cargarTablaEspecificaciones(url);
}




async function fntEditEspecificacionSubensamble(idespecificacion) {
  const btnTextEsp = document.querySelector('#btnTextEspecificacion');
  if (btnTextEsp) btnTextEsp.innerHTML = "Actualizar";

  const inputCritica = document.querySelector('#es_critica_especificacion');
  const esCritica = inputCritica ? Number(inputCritica.value || 0) : 0;

  const ajaxUrl = getControllerBase() + '/getEspecificacionSubensamble/' + idespecificacion + '/' + esCritica;;
  const objData = await xhrRequest({ method: "GET", url: ajaxUrl, responseType: "json", useLoading: true });

  if (objData && objData.status) {
    document.querySelector("#idespecificacionsubensamble").value = objData.data.idespecificacionsubensamble;
    document.querySelector("#txtEspecificacion").value = objData.data.especificacion;

    const inputCritica = document.querySelector('#es_critica_especificacion');
    if (inputCritica) inputCritica.value = Number(objData.data.es_critica || 0);
  } else {
    Swal.fire("Error", objData?.msg || "No se pudo cargar", "error");
  }
}


function fntDelEspecificacionSubensamble(idespecificacion) {
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
  }).then(async (result) => {
    if (!result.isConfirmed) return;

    const inputCritica = document.querySelector('#es_critica_especificacion');
    const esCritica = inputCritica ? Number(inputCritica.value || 0) : 0;

    const ajaxUrl = base_url + '/Plan_confproductosv1/delEspecificacionSubensamble';
    const strData = "idespecificacionsubensamble=" + encodeURIComponent(idespecificacion) +
      "&es_critica=" + encodeURIComponent(esCritica);

    const objData = await xhrRequest({
      method: "POST",
      url: ajaxUrl,
      data: strData,
      headers: { "Content-type": "application/x-www-form-urlencoded" },
      responseType: "json",
      useLoading: true
    });

    if (objData && objData.status) {
      Swal.fire("¡Operación exitosa!", objData.msg, "success");
      if (tableEspecifica) tableEspecifica.ajax.reload();
      await refrescarMetricasSubensamble();
    } else {
      Swal.fire("Atención!", objData?.msg || "Error al eliminar", "error");
    }
  });
}