let tableTipoCliente;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const idtipocliente = document.querySelector("#idtipocliente");
const nombreInput = document.querySelector("#nombre-tipocliente-input");
const descripcionInput = document.querySelector(
  "#descripcion-tipocliente-input",
);

// Mis referencias globales
let primerTab; // Tab LISTA
let firstTab; // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formTipoCliente = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS DEL FORMULARIO
    // --------------------------------------------------------------------
    formTipoCliente = document.querySelector("#formTipoCliente");
    spanBtnText = document.querySelector("#btnText");

    // --------------------------------------------------------------------
    //  DATATABLE TIPOS DE CLIENTES
    // --------------------------------------------------------------------
    tableTipoCliente = $("#tableTipoCliente").dataTable({
      aProcessing: true,
      aServerSide: true,
      ajax: {
        url: " " + base_url + "/cli_tipos_clientes/index",
        dataSrc: "",
      },
      columns: [
        { data: "id" },
        { data: "nombre" },
        { data: "descripcion" },
        { data: "fecha_registro" },
        { data: "estado" },
        { data: "options" },
      ],
      dom: "lBfrtip",
      buttons: [],
      resonsieve: "true",
      bDestroy: true,
      iDisplayLength: 10,
      order: [[0, "desc"]],

      language: {
        processing: "Procesando...",
        search: "Buscar:",
        lengthMenu: "Mostrar _MENU_ registros",
        info: "Mostrando _START_ a _END_ de _TOTAL_ registros",
        infoEmpty: "Mostrando 0 a 0 de 0 registros",
        infoFiltered: "(filtrado de _MAX_ registros totales)",
        loadingRecords: "Cargando...",
        zeroRecords: "No se encontraron resultados",
        emptyTable: "No hay datos disponibles en la tabla",
        paginate: {
          first: "Primero",
          previous: "Anterior",
          next: "Siguiente",
          last: "Último",
        },
        aria: {
          sortAscending: ": activar para ordenar la columna ascendente",
          sortDescending: ": activar para ordenar la columna descendente",
        },
      },
    });

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector(
      '#nav-tab a[href="#listtipoclientes"]',
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregartipocliente"]',
    );

    if (primerTabEl && firstTabEl && spanBtnText) {
      // ⚠️ IMPORTANTE: NO usar "let" aquí, usamos las globales
      primerTab = new bootstrap.Tab(primerTabEl); // LISTA
      firstTab = new bootstrap.Tab(firstTabEl); // NUEVO / ACTUALIZAR
      tabNuevo = firstTabEl; // elemento del tab

      // ----------------------------------------------------------------
      //  CLICK EN "NUEVO" → MODO NUEVO
      // ----------------------------------------------------------------
      tabNuevo.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";

        // Limpiar formulario
        formTipoCliente.reset();
        idtipocliente.value = "";
      });

      // ----------------------------------------------------------------
      // CLICK EN "TIPOS DE CLIENTES" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        idtipocliente.value = "";
        formTipoCliente.reset();
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR TIPO DE CLIENTE
    // --------------------------------------------------------------------
    formTipoCliente.addEventListener("submit", function (e) {
      e.preventDefault();

      divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");
      let ajaxUrl = base_url + "/cli_tipos_clientes/setTipoCliente";
      let formData = new FormData(formTipoCliente);

      request.open("POST", ajaxUrl, true);
      request.send(formData);

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        divLoading.style.display = "none";

        if (request.status !== 200) {
          Swal.fire(
            "Error",
            "Ocurrió un error en el servidor. Inténtalo de nuevo.",
            "error",
          );
          return;
        }

        let objData = JSON.parse(request.responseText);

        if (objData.status) {
          if (objData.tipo == "insert") {
            Swal.fire({
              title: objData.msg,
              text: "¿Deseas ingresar un nuevo registro?",
              icon: "success",
              showCancelButton: true,
              confirmButtonText: "Sí",
              cancelButtonText: "No",
              confirmButtonColor: "#28a745",
              cancelButtonColor: "#dc3545",
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then((result) => {
              if (result.isConfirmed) {
                // Seguir en modo NUEVO
                formTipoCliente.reset();
                idtipocliente.value = "";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tableTipoCliente.api().ajax.reload();
              } else {
                // Regresar al listado
                formTipoCliente.reset();
                idtipocliente.value = "";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                primerTab.show();
                tableTipoCliente.api().ajax.reload();
              }
            });
          } else {
            Swal.fire({
              title: objData.msg,
              icon: "success",
              confirmButtonText: "OK",
              confirmButtonColor: "#28a745",
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then(() => {
              // Acción final después de OK (opcional)
              formTipoCliente.reset();
              idtipocliente.value = "";
              tabNuevo.textContent = "NUEVO";
              spanBtnText.textContent = "REGISTRAR";
              primerTab.show();
              tableTipoCliente.api().ajax.reload();
            });
          }
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      };
    });
  },
  false,
);

// ------------------------------------------------------------------------
// FUNCIÓN EDITAR TIPO DE CLIENTE → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(id_tipocliente) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_tipos_clientes/show/" + id_tipocliente;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        idtipocliente.value = objData.data.id;
        nombreInput.value = objData.data.nombre;
        descripcionInput.value = objData.data.descripcion;

        // Cambiar al tab de captura
        if (firstTab) firstTab.show();
      } else {
        swal("Error", objData.msg, "error");
      }
    }
  };
}

// ------------------------------------------------------------------------
//  ELIMINAR UN REGISTRO DEL LISTADO
// ------------------------------------------------------------------------
function fntDelTipoCliente(idtipocliente) {
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
      cancelButton: "btn btn-danger w-xs mb-1",
    },
    buttonsStyling: false,
    showCloseButton: true,
  }).then((result) => {
    if (!result.isConfirmed) {
      return;
    }

    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    let ajaxUrl = base_url + "/cli_tipos_clientes/destroy";
    let strData = "idtipocliente=" + idtipocliente;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader(
      "Content-type",
      "application/x-www-form-urlencoded",
    );
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          tableTipoCliente.api().ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DEl TIPO DE CLIENTE
// ------------------------------------------------------------------------
function fntViewTipoCliente(idtipocliente) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_tipos_clientes/show/" + idtipocliente;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        let estadoTipoCliente =
          objData.data.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        document.querySelector("#idtipodecliente").innerHTML = objData.data.id;
        document.querySelector("#nombreTipoCliente").innerHTML =
          objData.data.nombre;
        document.querySelector("#descripcionTipoCliente").innerHTML =
          objData.data.descripcion;
        document.querySelector("#fechaTipoCliente").innerHTML =
          objData.data.fecha_registro;
        document.querySelector("#estadoTipoCliente").innerHTML =
          estadoTipoCliente;

        $("#modalViewTipoCliente").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}
