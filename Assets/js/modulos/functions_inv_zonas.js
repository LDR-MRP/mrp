let tableZonas;
let rowTable = "";
let divLoading = null;

// Inputs del formulario (los llenamos en DOMContentLoaded)
let almacen = null; // #idzona (hidden)
let cve_almacen = null; // #clave-zona-input
let estado = null; // #estado-select

// Referencias globales para tabs y botón
let primerTab = null; // instancia de bootstrap.Tab (lista)
let firstTab = null; // instancia de bootstrap.Tab (nuevo/actualizar)
let tabNuevo = null; // elemento <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null; // span del botón (REGISTRAR / ACTUALIZAR)
let selectPrecios = null;
let formZonas = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading = document.querySelector("#divLoading");
    formZonas = document.querySelector("#formZonas");
    spanBtnText = document.querySelector("#btnText");
    selectPrecios = document.querySelector("#listSedes");

    almacen = document.querySelector("#idzona");
    cve_almacen = document.querySelector("#clave-zona-input");
    estado = document.querySelector("#estado-select");

    // Si este JS se carga en una vista donde no existe el form, salimos
    if (!formZonas) {
      console.warn(
        "formZonas no encontrado. JS de lineas no se inicializa en esta vista.",
      );
      return;
    }

    // --------------------------------------------------------------------
    //  CARGAR ALMACENES POR AJAX
    // --------------------------------------------------------------------
    fntAlmacenes(); // solo llena el select

    // --------------------------------------------------------------------
    //  DATATABLE Almacenes
    // --------------------------------------------------------------------
    tableZonas = $("#tableZonas").DataTable({
      processing: true,
      serverSide: false, // ✅ CAMBIO
      ajax: {
        url: base_url + "/Inv_zonas/getZonas",
        dataSrc: "",
      },
      columns: [
        { data: "cve_zona" },
        { data: "descripcion" },
        { data: "sede" },
        { data: "fecha_creacion" },
        { data: "estado" },
        { data: "options" },
      ],
      responsive: true,
      destroy: true,
      pageLength: 10,
    });

    // --------------------------------------------------------------------
    //  TABS BOOTSTRAP (solo si existen)
    // --------------------------------------------------------------------
    const primerTabElp = document.querySelector(
      '#nav-tab a[href="#listzonas"]',
    );
    const firstTabElp = document.querySelector(
      '#nav-tab a[href="#agregarZona"]',
    );

    if (primerTabElp && firstTabElp && spanBtnText) {
      // ⚠️ IMPORTANTE: NO usar "let" aquí, usamos las globales
      primerTab = new bootstrap.Tab(primerTabElp); // LISTA
      firstTab = new bootstrap.Tab(firstTabElp); // NUEVO / ACTUALIZAR
      tabNuevo = firstTabElp; // elemento del tab

      // CLICK EN "NUEVO" → MODO NUEVO
      tabNuevo.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        almacen.value = "";
        formZonas.reset();
        if (selectPrecios) selectPrecios.value = "";
      });

      // CLICK EN "LISTA" → RESET
      primerTabElp.addEventListener("click", () => {
        almacen.value = "";
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        formZonas.reset();
        if (selectPrecios) selectPrecios.value = "";
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM → SOLO AJAX
    // --------------------------------------------------------------------
    formZonas.addEventListener("submit", function (e) {
      e.preventDefault(); // evitar envío por URL

      // Validar almacen si aplica
      // if (selectPrecios && selectPrecios.value === '') {
      //     Swal.fire("Aviso", "Debes seleccionar una almacen.", "warning");
      //     return;
      // }

      if (divLoading) divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");

      let ajaxUrl = base_url + "/Inv_zonas/setZona";
      let formData = new FormData(formZonas);

      request.open("POST", ajaxUrl, true);
      request.send(formData);

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        if (divLoading) divLoading.style.display = "none";

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
              // Siempre recargamos el DataTable
              if (tableZonas) tableZonas.ajax.reload();

              // Modo NUEVO nuevamente
              formZonas.reset();
              if (selectPrecios) selectPrecios.value = "";
              if (estado) estado.value = "2";
              if (spanBtnText) spanBtnText.textContent = "REGISTRAR";
              if (tabNuevo) tabNuevo.textContent = "NUEVO";

              if (!result.isConfirmed && primerTab) {
                // Regresar al listado
                primerTab.show();
              }
            });
          } else {
            // UPDATE
            Swal.fire({
              title: objData.msg,
              icon: "success",
              confirmButtonText: "OK",
              confirmButtonColor: "#28a745",
              allowOutsideClick: false,
              allowEscapeKey: false,
            }).then(() => {
              formZonas.reset();
              if (selectPrecios) selectPrecios.value = "";
              if (estado) estado.value = "2";
              if (spanBtnText) spanBtnText.textContent = "REGISTRAR";
              if (tabNuevo) tabNuevo.textContent = "NUEVO";
              if (primerTab) primerTab.show();
              if (tableZonas) tableZonas.ajax.reload();
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
// FUNCIÓN EDITAR almacen → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(idzona) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  // Opcional: limpiar antes de llenar
  if (formZonas) formZonas.reset();

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");

  let ajaxUrl = base_url + "/Inv_zonas/getZona/" + idzona;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState != 4) return;
    if (request.status != 200) {
      Swal.fire("Error", "Error al consultar la línea.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);

    if (objData.status) {
      // Asegurarnos de tener las referencias por si el DOM cambió
      almacen.value = objData.data.idzona;
      cve_almacen.value = objData.data.cve_zona;
      document.querySelector("#descripcion-zona-textarea").value =
        objData.data.descripcion;
      estado.value = objData.data.estado;
      selectPrecios.value = objData.data.sedeid;
      firstTab.show();
      if (firstTab) firstTab.show();
    } else {
      Swal.fire("Error", objData.msg, "error");
    }
  };
}

// ------------------------------------------------------------------------
//  ELIMINAR UN REGISTRO DEL LISTADO
// ------------------------------------------------------------------------
function fntDelInfo(idzona) {
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

    let ajaxUrl = base_url + "/Inv_zonas/delZona";
    let strData = "idzona=" + idzona;

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
          if (tableZonas) tableZonas.ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  VER EL CATALOGO DE PLANTAS
// ------------------------------------------------------------------------
function fntAlmacenes(selectedValue = "") {
  if (document.querySelector("#listSedes")) {
    let ajaxUrl = base_url + "/Inv_sedes/getSelectSedes";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#listSedes").innerHTML = request.responseText;

        if (selectedValue !== "") {
          document.querySelector("#listSedes").value = selectedValue;
        }
      }
    };
  }
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA LA almacen
// ------------------------------------------------------------------------
function fntViewZona(idzona) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/Inv_zonas/getZona/" + idzona;
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

        document.querySelector("#celclave").innerHTML =
          objData.data.cve_almacen;
        document.querySelector("#celdescripcion").innerHTML =
          objData.data.descripcion;
        document.querySelector("#celdireccion").innerHTML =
          objData.data.direccion;
        document.querySelector("#celencargado").innerHTML =
          objData.data.encargado;
        document.querySelector("#celtelefono").innerHTML =
          objData.data.telefono;
        document.querySelector("#cellistaprecio").innerHTML =
          objData.data.lista_precio;
        document.querySelector("#celFecha").innerHTML =
          objData.data.fecha_creacion;
        document.querySelector("#celEstado").innerHTML = estadoUsuario;

        $("#modalViewAlmacen").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}
