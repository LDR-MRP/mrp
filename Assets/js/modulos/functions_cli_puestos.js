let tablePuestos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const idpuesto = document.querySelector("#idpuesto");
const departamento_id = document.querySelector("#listPuestos");
const nombreInput = document.querySelector("#nombre-puestos-input");
const descripcionInput = document.querySelector("#descripcion-puestos-input");
const estado = document.querySelector("#estado-select");

// Mis referencias globales
let primerTab; // Tab LISTA
let firstTab; // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formPuestos = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS DEL FORMULARIO
    // --------------------------------------------------------------------
    formPuestos = document.querySelector("#formPuestos");
    spanBtnText = document.querySelector("#btnText");

    fntPuestos();

    // --------------------------------------------------------------------
    //  DATATABLE PUESTOS
    // --------------------------------------------------------------------
    tablePuestos = $("#tablePuestos").dataTable({
      aProcessing: true,
      aServerSide: true,
      ajax: {
        url: " " + base_url + "/cli_puestos/index",
        dataSrc: "",
      },
      columns: [
        { data: "id" },
        { data: "nombre_departamento" },
        { data: "nombre_puesto" },
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
      '#nav-tab a[href="#listpuestos"]'
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregarpuesto"]'
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
        formPuestos.reset();
        idpuesto.value = "";
        estado.value = "2";
      });

      // ----------------------------------------------------------------
      // CLICK EN "PUESTOS" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        idpuesto.value = "";
        estado.value = "2";
        formPuestos.reset();
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR PUESTO
    // --------------------------------------------------------------------
    formPuestos.addEventListener("submit", function (e) {
      e.preventDefault();

      divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");
      let ajaxUrl = base_url + "/cli_puestos/setPuesto";
      let formData = new FormData(formPuestos);

      request.open("POST", ajaxUrl, true);
      request.send(formData);

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        divLoading.style.display = "none";

        if (request.status !== 200) {
          Swal.fire(
            "Error",
            "Ocurrió un error en el servidor. Inténtalo de nuevo.",
            "error"
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
                formPuestos.reset();
                idpuesto.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tablePuestos.api().ajax.reload();
              } else {
                // Regresar al listado
                formPuestos.reset();
                idpuesto.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                primerTab.show();
                tablePuestos.api().ajax.reload();
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
              formPuestos.reset();
              idpuesto.value = "";
              estado.value = "2";
              tabNuevo.textContent = "NUEVO";
              spanBtnText.textContent = "REGISTRAR";
              primerTab.show();
              tablePuestos.api().ajax.reload();
            });
          }
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      };
    });
  },
  false
);

// ------------------------------------------------------------------------
// FUNCIÓN EDITAR PUESTO → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(id_puesto) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_puestos/show/" + id_puesto;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        idpuesto.value = objData.data.id;
        departamento_id.value = objData.data.departamento_id;
        nombreInput.value = objData.data.nombre_puesto;
        descripcionInput.value = objData.data.descripcion;
        estado.value = objData.data.estado;

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
function fntDelPuesto(idpuesto) {
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
    let ajaxUrl = base_url + "/cli_puestos/destroy";
    let strData = "idpuesto=" + idpuesto;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader(
      "Content-type",
      "application/x-www-form-urlencoded"
    );
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          tablePuestos.api().ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DEl REGISTRO
// ------------------------------------------------------------------------
function fntViewPuesto(idpuesto) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_puestos/show/" + idpuesto;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        let estadoUsuario =
          objData.data.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        document.querySelector("#idpuesto").innerHTML = objData.data.id;
        document.querySelector("#departamento_id").innerHTML =
          objData.data.nombre_departamento;
        document.querySelector("#nombrePuesto").innerHTML =
          objData.data.nombre_puesto;
        document.querySelector("#descripcionPuesto").innerHTML =
          objData.data.descripcion;
        document.querySelector("#fechaPuesto").innerHTML =
          objData.data.fecha_registro;
        document.querySelector("#estadoPuesto").innerHTML = estadoUsuario;

        $("#modalViewPuesto").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PLANTAS
// ------------------------------------------------------------------------
function fntPuestos(selectedValue = "") {
  if (document.querySelector("#listPuestos")) {
    let ajaxUrl = base_url + "/cli_puestos/getSelectDepartamentos";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#listPuestos").innerHTML = request.responseText;

        if (selectedValue !== "") {
          select.value = selectedValue;
        }
      }
    };
  }
}
