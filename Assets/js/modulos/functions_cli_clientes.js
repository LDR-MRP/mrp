let tableDistibuidores = null;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const idcliente = document.querySelector("#idcliente");
const nombre = document.querySelector("#nombre-cliente-input");
const estado = document.querySelector("#estado-select");
const direccion = document.querySelector("#direccion-linea-textarea");

const selectPais = document.querySelector("#listPaises");
const selectEstado = document.querySelector("#listEstados");
const selectMunicipio = document.querySelector("#listMunicipios");

// Mis referencias globales
let primerTab; // Tab LISTA
let firstTab; // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formClientes = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS DEL FORMULARIO
    // --------------------------------------------------------------------
    formClientes = document.querySelector("#formClientes");
    spanBtnText = document.querySelector("#btnText");

    fntGrupos();
    fntPaises();

    // --------------------------------------------------------------------
    //  DATATABLE CLIENTES
    // --------------------------------------------------------------------
    tableDistibuidores = $("#cli_distribuidores").dataTable({
      aProcessing: true,
      aServerSide: true,
      ajax: {
        url: " " + base_url + "/cli_clientes/index",
        dataSrc: "",
      },
      columns: [
        { data: "id" },
        { data: "nombre_grupo" },
        { data: "tipo_negocio" },
        { data: "nombre_comercial" },
        { data: "razon_social" },
        { data: "plaza" },
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
      '#nav-tab a[href="#listclientes"]'
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregarclientes"]'
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
        formClientes.reset();
        cliente.value = "";
        estado.value = "2";
      });

      // ----------------------------------------------------------------
      // CLICK EN "CLIENTES" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        cliente.value = "";
        estado.value = "2";
        formClientes.reset();
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR CLIENTE
    // --------------------------------------------------------------------
    formClientes.addEventListener("submit", function (e) {
      e.preventDefault();

      divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");
      let ajaxUrl = base_url + "/cli_clientes/setCliente";
      let formData = new FormData(formClientes);

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
                formClientes.reset();
                cliente.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tableDistibuidores = null.api().ajax.reload();
              } else {
                // Regresar al listado
                formClientes.reset();
                cliente.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                primerTab.show();
                tableDistibuidores = null.api().ajax.reload();
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
              formClientes.reset();
              cliente.value = "";
              estado.value = "2";
              tabNuevo.textContent = "NUEVO";
              spanBtnText.textContent = "REGISTRAR";
              primerTab.show();
              tableDistibuidores = null.api().ajax.reload();
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
// FUNCIÓN EDITAR CLIENTE → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditCliente(idcliente) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_clientes/getCliente/" + idcliente;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        cliente.value = objData.data.idcliente;

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
function fntDelCliente(idcliente) {
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
    let ajaxUrl = base_url + "/cli_clientes/destroy";
    let strData = "idcliente=" + idcliente;

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
          tableDistibuidores.api().ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DEl CLIENTE
// ------------------------------------------------------------------------
function fntViewCliente(idcliente) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_clientes/show/" + idcliente;

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

        document.querySelector("#idcliente").innerHTML = objData.data.id;
        document.querySelector("#nombregrupo").innerHTML =
          objData.data.nombre_grupo;
        document.querySelector("#tiponegocio").innerHTML =
          objData.data.tipo_negocio;
        document.querySelector("#nombrecomercial").innerHTML =
          objData.data.nombre_comercial;
        document.querySelector("#razonsocial").innerHTML =
          objData.data.razon_social;
        document.querySelector("#plaza").innerHTML = objData.data.plaza;
        document.querySelector("#rfc").innerHTML = objData.data.rfc;
        document.querySelector("#repve").innerHTML = objData.data.repve;
        document.querySelector("#telefono").innerHTML = objData.data.telefono;
        document.querySelector("#telefonoalt").innerHTML =
          objData.data.telefono_alt;

        document.querySelector("#tipo").innerHTML = objData.data.tipo;
        document.querySelector("#calle").innerHTML = objData.data.calle;
        document.querySelector("#numero_ext").innerHTML =
          objData.data.numero_ext;
        document.querySelector("#numero_int").innerHTML =
          objData.data.numero_int;
        document.querySelector("#colonia").innerHTML = objData.data.colonia;
        document.querySelector("#codigo_postal").innerHTML =
          objData.data.codigo_postal;
        document.querySelector("#pais").innerHTML = objData.data.pais;
        document.querySelector("#estado_id").innerHTML = objData.data.estado_id;
        document.querySelector("#municipio").innerHTML = objData.data.municipio;
        document.querySelector("#latitud_direccion").innerHTML = objData.data.latitud_direccion;
        document.querySelector("#longitud_direccion").innerHTML = objData.data.longitud_direccion;

        document.querySelector("#fecharegistro").innerHTML =
          objData.data.fecha_registro;
        document.querySelector("#celEstado").innerHTML = estadoUsuario;

        $("#modalViewCliente").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE GRUPOS
// ------------------------------------------------------------------------
function fntGrupos(selectedValue = "") {
  if (document.querySelector("#listGrupos")) {
    let ajaxUrl = base_url + "/cli_clientes/getSelectGrupos";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#listGrupos").innerHTML = request.responseText;

        if (selectedValue !== "") {
          select.value = selectedValue;
        }
      }
    };
  }
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PAISES
// ------------------------------------------------------------------------
function fntPaises(selected = "") {
  let ajaxUrl = base_url + "/cli_clientes/getSelectPaises";
  let request = new XMLHttpRequest();
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectPais.innerHTML = request.responseText;
      if (selected) selectPais.value = selected;
    }
  };

  selectPais.onchange = function () {
    fntEstados(this.value);
    selectMunicipio.innerHTML =
      '<option value="">--Seleccione municipio--</option>';
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE ESTADOS
// ------------------------------------------------------------------------
function fntEstados(pais_id, selected = "") {
  if (!pais_id) {
    selectEstado.innerHTML = '<option value="">--Seleccione estado--</option>';
    return;
  }

  let ajaxUrl = base_url + "/cli_clientes/getSelectEstados/" + pais_id;
  let request = new XMLHttpRequest();
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectEstado.innerHTML = request.responseText;
      if (selected) selectEstado.value = selected;
    }
  };

  selectEstado.onchange = function () {
    fntMunicipios(this.value);
  };
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE MUNICIPIOS
// ------------------------------------------------------------------------
function fntMunicipios(estado_id, selected = "") {
  if (!estado_id) {
    selectMunicipio.innerHTML =
      '<option value="">--Seleccione municipio--</option>';
    return;
  }

  let ajaxUrl = base_url + "/cli_clientes/getSelectMunicipios/" + estado_id;
  let request = new XMLHttpRequest();
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      selectMunicipio.innerHTML = request.responseText;
      if (selected) selectMunicipio.value = selected;
    }
  };
}
