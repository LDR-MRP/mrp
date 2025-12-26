let tableAlmacenes;
let rowTable = "";
let divLoading = null;

// Inputs del formulario (los llenamos en DOMContentLoaded)
let almacen = null; // #idalmacen (hidden)
let cve_almacen = null; // #clave-almacen-input
let estado = null; // #estado-select

// Referencias globales para tabs y botón
let primerTab = null; // instancia de bootstrap.Tab (lista)
let firstTab = null; // instancia de bootstrap.Tab (nuevo/actualizar)
let tabNuevo = null; // elemento <a> del tab "NUEVO/ACTUALIZAR"
let spanBtnText = null; // span del botón (REGISTRAR / ACTUALIZAR)
let selectPrecios = null;
let formAlmacenes = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS BÁSICAS
    // --------------------------------------------------------------------
    divLoading = document.querySelector("#divLoading");
    formAlmacenes = document.querySelector("#formAlmacenes");
    spanBtnText = document.querySelector("#btnText");
    selectPrecios = document.querySelector("#listPrecios");

    almacen = document.querySelector("#idalmacen");
    cve_almacen = document.querySelector("#clave-almacen-input");
    estado = document.querySelector("#estado-select");

    // Si este JS se carga en una vista donde no existe el form, salimos
    if (!formAlmacenes) {
      console.warn(
        "formAlmacenes no encontrado. JS de lineas no se inicializa en esta vista."
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
    tableAlmacenes = $("#tableAlmacenes").DataTable({
      processing: true,
      serverSide: false, // ✅ CAMBIO
      ajax: {
        url: base_url + "/Inv_almacenes/getAlmacenes",
        dataSrc: "",
      },
      columns: [
        { data: "cve_almacen" },
        { data: "descripcion" },
        { data: "direccion" },
        { data: "encargado" },
        { data: "telefono" },
        { data: "lista_precio" },
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
      '#nav-tab a[href="#listAlmacenes"]'
    );
    const firstTabElp = document.querySelector(
      '#nav-tab a[href="#agregarAlmacen"]'
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
        formAlmacenes.reset();
        if (selectPrecios) selectPrecios.value = "";
      });

      // CLICK EN "LISTA" → RESET
      primerTabElp.addEventListener("click", () => {
        almacen.value = "";
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        formAlmacenes.reset();
        if (selectPrecios) selectPrecios.value = "";
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    //  SUBMIT FORM → SOLO AJAX
    // --------------------------------------------------------------------
    formAlmacenes.addEventListener("submit", function (e) {
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

      let ajaxUrl = base_url + "/Inv_almacenes/setAlmacen";
      let formData = new FormData(formAlmacenes);

      request.open("POST", ajaxUrl, true);
      request.send(formData);

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;

        if (divLoading) divLoading.style.display = "none";

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
              // Siempre recargamos el DataTable
              if (tableAlmacenes) tableAlmacenes.ajax.reload();

              // Modo NUEVO nuevamente
              formAlmacenes.reset();
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
              formAlmacenes.reset();
              if (selectPrecios) selectPrecios.value = "";
              if (estado) estado.value = "2";
              if (spanBtnText) spanBtnText.textContent = "REGISTRAR";
              if (tabNuevo) tabNuevo.textContent = "NUEVO";
              if (primerTab) primerTab.show();
              if (tableAlmacenes) tableAlmacenes.ajax.reload();
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
// FUNCIÓN EDITAR almacen → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(idalmacen) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  // Opcional: limpiar antes de llenar
  if (formAlmacenes) formAlmacenes.reset();

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");

  let ajaxUrl = base_url + "/Inv_almacenes/getAlmacen/" + idalmacen;

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
      almacen.value = objData.data.idalmacen;
      cve_almacen.value = objData.data.cve_almacen;
      document.querySelector("#direccion-input").value = objData.data.direccion;
      document.querySelector("#encargado-input").value = objData.data.encargado;
      document.querySelector("#telefono-input").value = objData.data.telefono;
      document.querySelector('#descripcion-almacen-textarea').value = objData.data.descripcion;
      estado.value = objData.data.estado;
      selectPrecios.value = objData.data.listaprecioid;
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
function fntDelInfo(idalmacen) {
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

    let ajaxUrl = base_url + "/Inv_almacenes/delAlmacen";
    let strData = "idalmacen=" + idalmacen;

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
          if (tableAlmacenes) tableAlmacenes.ajax.reload();
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
  if (document.querySelector("#listPrecios")) {
    let ajaxUrl = base_url + "/Inv_precios/getSelectPrecios";
    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        document.querySelector("#listPrecios").innerHTML = request.responseText;

        if (selectedValue !== "") {
          document.querySelector("#listPrecios").value = selectedValue;
        }
      }
    };
  }
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA LA almacen
// ------------------------------------------------------------------------
function fntViewAlmacen(idalmacen) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/Inv_almacenes/getAlmacen/" + idalmacen;
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
