let tableContactos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const idcontacto = document.querySelector("#idcontacto");
const distribuidor_id = document.querySelector("#listDistribuidores");
const puesto_id = document.querySelector("#listPuestos");
const nombre = document.querySelector("#nombre-contactos-input");
const correo = document.querySelector("#correo-contactos-input");
const telefono = document.querySelector("#telefono-contactos-input");
const estado = document.querySelector("#estado-select");

// Mis referencias globales
let primerTab; // Tab LISTA
let firstTab; // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formContactos = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS DEL FORMULARIO
    // --------------------------------------------------------------------
    formContactos = document.querySelector("#formContactos");
    spanBtnText = document.querySelector("#btnText");

    fntDistribuidor();
    fntPuestos();

    // --------------------------------------------------------------------
    //  DATATABLE CONTACTOS
    // --------------------------------------------------------------------
    tableContactos = $("#tableContactos").dataTable({
      aProcessing: true,
      aServerSide: true,
      ajax: {
        url: " " + base_url + "/cli_contactos/index",
        dataSrc: "",
      },
      columns: [
        { data: "id" },
        { data: "nombre_distribuidor" },
        { data: "nombre_puesto" },
        { data: "nombre_contacto" },
        { data: "correo" },
        { data: "telefono" },
        { data: "estado" },
        { data: "options" },
      ],
      dom: "lBfrtip",
      buttons: [],
      resonsieve: "true",
      bDestroy: true,
      iDisplayLength: 10,
      order: [[0, "desc"]],
    });

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP
    // --------------------------------------------------------------------
    const primerTabEl = document.querySelector(
      '#nav-tab a[href="#listcontactos"]'
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregarcontacto"]'
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
        formContactos.reset();
        idcontacto.value = "";
        estado.value = "2";
      });

      // ----------------------------------------------------------------
      // CLICK EN "CONTACTOS" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        idcontacto.value = "";
        estado.value = "2";
        formContactos.reset();
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR CONTACTO
    // --------------------------------------------------------------------
    formContactos.addEventListener("submit", function (e) {
      e.preventDefault();

      divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");
      let ajaxUrl = base_url + "/cli_contactos/setContacto/";
      let formData = new FormData(formContactos);

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
                formContactos.reset();
                idcontacto.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tableContactos.api().ajax.reload();
              } else {
                // Regresar al listado
                formContactos.reset();
                idcontacto.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                primerTab.show();
                tableContactos.api().ajax.reload();
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
              formContactos.reset();
              idcontacto.value = "";
              estado.value = "2";
              tabNuevo.textContent = "NUEVO";
              spanBtnText.textContent = "REGISTRAR";
              primerTab.show();
              tableContactos.api().ajax.reload();
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
// FUNCIÓN EDITAR CONTACTO → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(id_contacto) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_contactos/show/" + id_contacto;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        idcontacto.value = objData.data.id;
        distribuidor_id.value = objData.data.distribuidor_id;
        puesto_id.value = objData.data.puesto_id;
        nombre.value = objData.data.nombre_contacto;
        correo.value = objData.data.correo;
        telefono.value = objData.data.telefono;
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
function fntDelContacto(idcontacto) {
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
    let ajaxUrl = base_url + "/cli_contactos/destroy";
    let strData = "idcontacto=" + idcontacto;

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
          tableContactos.api().ajax.reload();
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
function fntViewContacto(idcontacto) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_contactos/show/" + idcontacto;
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

        document.querySelector("#idcontacto").innerHTML = objData.data.id;  
        document.querySelector("#nombreDistribuidor").innerHTML =
          objData.data.nombre_distribuidor;
        document.querySelector("#nombrePuesto").innerHTML =
          objData.data.nombre_puesto;
        document.querySelector("#nombreContacto").innerHTML =
          objData.data.nombre_contacto;
        document.querySelector("#correoContacto").innerHTML =
          objData.data.correo;
        document.querySelector("#telefonoContacto").innerHTML =
          objData.data.telefono;
        document.querySelector("#fechaContacto").innerHTML =
          objData.data.fecha_registro;
        document.querySelector("#estadoContacto").innerHTML = estadoUsuario;

        $("#modalViewContacto").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE DISTRIBUIDORES
// ------------------------------------------------------------------------
function fntDistribuidor(selectedValue = "") {
  if (document.querySelector("#listDistribuidores")) {
    let ajaxUrl = base_url + "/cli_contactos/getSelectDistribuidores";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#listDistribuidores").innerHTML =
          request.responseText;

        if (selectedValue !== "") {
          select.value = selectedValue;
        }
      }
    };
  }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PUESTOS
// ------------------------------------------------------------------------
function fntPuestos(selectedValue = "") {
  if (document.querySelector("#listPuestos")) {
    let ajaxUrl = base_url + "/cli_contactos/getSelectPuestos";
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
