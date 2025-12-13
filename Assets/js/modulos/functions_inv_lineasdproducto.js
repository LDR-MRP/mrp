let tableLineasProducto;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
let cve_linea_producto = null;
let estado = null;
let descripcion = null;

// Mis referencias globales
let primerTab;   // Tab LISTA
let firstTab;    // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formLineasProducto = null;

document.addEventListener('DOMContentLoaded', function () {

  // --------------------------------------------------------------------
  //  REFERENCIAS DEL FORMULARIO
  // --------------------------------------------------------------------
  formLineasProducto = document.querySelector("#formLineasProducto");
  spanBtnText = document.querySelector('#btnText');

  // Inputs (si existen)
  cve_linea_producto = document.querySelector('#clave-linea-producto-input');
  estado             = document.querySelector('#estado-select');
  descripcion        = document.querySelector('#descripcion-linea-producto-textarea');

  // --------------------------------------------------------------------
  //  DATATABLE LINEAS PRODUCTO
  // --------------------------------------------------------------------
  if (document.querySelector('#tableLineasProducto')) {

    // evita doble inicialización si tu vista se carga más de una vez
    if (!$.fn.DataTable.isDataTable('#tableLineasProducto')) {
      tableLineasProducto = $('#tableLineasProducto').dataTable({
        "aProcessing": true,
        "aServerSide": true,
        "ajax": {
          "url": base_url + "/Inv_lineasdproducto/getLineasProductos",
          "dataSrc": ""
        },
        "columns": [
          { "data": "cve_linea_producto" },
          { "data": "descripcion" },
          { "data": "fecha_creacion" },
          { "data": "estado" },
          { "data": "options" }
        ],
        "dom": "lBfrtip",
        "buttons": [],
        "responsive": true,
        "bDestroy": true,
        "iDisplayLength": 10,
        "order": [[0, "desc"]]
      });
    }
  }

  // --------------------------------------------------------------------
  //  TABS BOOTSTRAP
  // --------------------------------------------------------------------
  const primerTabEl = document.querySelector('#nav-tab a[href="#listlineasproductos"]');
  const firstTabEl  = document.querySelector('#nav-tab a[href="#agregarlineasproducto"]');

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl); // LISTA
    firstTab  = new bootstrap.Tab(firstTabEl);  // NUEVO / ACTUALIZAR
    tabNuevo  = firstTabEl;                     // elemento del tab

    // CLICK EN "NUEVO" → MODO NUEVO
    tabNuevo.addEventListener('click', () => {
      spanBtnText.textContent = 'REGISTRAR';

      if (formLineasProducto) formLineasProducto.reset();

      const idHidden = document.querySelector("#idlineaproducto");
      if (idHidden) idHidden.value = 0;

      // defaults
      if (estado) estado.value = '2';
    });

    // CLICK EN "LISTA" → RESETEAR A NUEVO
    primerTabEl.addEventListener('click', () => {
      if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';

      const idHidden = document.querySelector("#idlineaproducto");
      if (idHidden) idHidden.value = 0;

      if (estado) estado.value = '2';
      if (formLineasProducto) formLineasProducto.reset();
    });

  } else {
    console.warn('Tabs de lineas producto no encontrados o btnText faltante.');
  }

  // --------------------------------------------------------------------
  // FORM → CREAR / ACTUALIZAR LINEA PRODUCTO
  // --------------------------------------------------------------------
  if (!formLineasProducto) {
    // Importante: si tu app inyecta esta vista sin recargar página,
    // este log te confirma que el script corrió antes de que existiera el form.
    console.warn('formLineasProducto no encontrado al cargar. (Si tu navegación es AJAX, hay que inicializar al insertar la vista).');
    return;
  }

  formLineasProducto.addEventListener('submit', function (e) {
    e.preventDefault();

    if (divLoading) divLoading.style.display = "flex";

    let url = base_url + "/Inv_lineasdproducto/setLineaProducto";
    let formData = new FormData(formLineasProducto);

    fetch(url, { method: "POST", body: formData })
      .then(res => res.json())
      .then(objData => {

        if (divLoading) divLoading.style.display = "none";

        if (objData.status) {

          Swal.fire({
            title: objData.msg,
            text: '¿Deseas ingresar un nuevo registro?',
            icon: 'success',
            showCancelButton: true,
            confirmButtonText: 'Sí',
            cancelButtonText: 'No',
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#dc3545',
            allowOutsideClick: false,
            allowEscapeKey: false
          }).then((result) => {

            // Recargar tabla
            if (tableLineasProducto) tableLineasProducto.api().ajax.reload();

            if (result.isConfirmed) {
              // Seguir en modo NUEVO
              formLineasProducto.reset();

              const idHidden = document.querySelector("#idlineaproducto");
              if (idHidden) idHidden.value = 0;

              if (estado) estado.value = '2';
              if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';

            } else {
              // Regresar al listado
              formLineasProducto.reset();

              const idHidden = document.querySelector("#idlineaproducto");
              if (idHidden) idHidden.value = 0;

              if (estado) estado.value = '2';
              if (spanBtnText) spanBtnText.textContent = 'REGISTRAR';
              if (primerTab) primerTab.show();
            }
          });

        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      })
      .catch(err => {
        console.error(err);
        if (divLoading) divLoading.style.display = "none";
        Swal.fire("Error", "Error de red o servidor.", "error");
      });

  });

}, false);

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewLineaProducto(id){
  fetch(base_url + "/Inv_lineasdproducto/getLineaProducto/" + id)
    .then(res => res.json())
    .then(objData => {
      if(objData.status){
        document.querySelector("#celClave").innerHTML = objData.data.cve_linea_producto;
        document.querySelector("#celDescripcion").innerHTML = objData.data.descripcion;
        document.querySelector("#celFecha").innerHTML = objData.data.fecha_creacion;
        document.querySelector("#celEstado").innerHTML = objData.data.estado == 2 ? "Activo" : "Inactivo";
        $("#modalViewLineaProducto").modal("show");
      }
    });
}

// ----------------------------------------------
// EDITAR → MODO ACTUALIZAR
// ----------------------------------------------
function fntEditLineaProducto(id){

  // Cambiar textos a modo ACTUALIZAR (como Plantas)
  if (tabNuevo)    tabNuevo.textContent    = 'ACTUALIZAR';
  if (spanBtnText) spanBtnText.textContent = 'ACTUALIZAR';

  fetch(base_url + "/Inv_lineasdproducto/getLineaProducto/" + id)
    .then(res => res.json())
    .then(objData => {

      if(objData.status){

        // refrescar referencias por si el DOM cambió
        formLineasProducto = document.querySelector("#formLineasProducto");
        cve_linea_producto = document.querySelector('#clave-linea-producto-input');
        descripcion        = document.querySelector('#descripcion-linea-producto-textarea');
        estado             = document.querySelector('#estado-select');

        const idHidden = document.querySelector("#idlineaproducto");

        if (idHidden) idHidden.value = objData.data.idlineaproducto;
        if (cve_linea_producto) cve_linea_producto.value = objData.data.cve_linea_producto;
        if (descripcion) descripcion.value = objData.data.descripcion;
        if (estado) estado.value = objData.data.estado;

        if (firstTab) firstTab.show();
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    });
}

// ------------------------------------------------------------------------
//  ELIMINAR UNA LINEA DE PRODUCTO
// ------------------------------------------------------------------------
function fntDelInfo(idlineaproducto) {

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
            ¿Estás seguro de eliminar este registro?
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
    let ajaxUrl = base_url + '/Inv_lineasdproducto/delLineaProducto';
    let strData = "idlineaproducto=" + idlineaproducto;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);

        if (objData.status) {
          Swal.fire("Correcto", objData.msg, "success");
          if (tableLineasProducto) tableLineasProducto.api().ajax.reload();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      }
    }
  });
}
