let tableGrupos;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const idgrupo = document.querySelector("#idgrupo");
const codigoInput = document.querySelector("#codigo-grupo-input");
const nombreInput = document.querySelector("#nombre-grupo-input");
const descripcionInput = document.querySelector("#descripcion-grupo-input");
const estado = document.querySelector("#estado-select");

// Mis referencias globales
let primerTab; // Tab LISTA
let firstTab; // Tab NUEVO/ACTUALIZAR
let tabNuevo;
let spanBtnText = null;
let formGrupos = null;

document.addEventListener(
  "DOMContentLoaded",
  function () {
    // --------------------------------------------------------------------
    //  REFERENCIAS DEL FORMULARIO
    // --------------------------------------------------------------------
    formGrupos = document.querySelector("#formGrupos");
    spanBtnText = document.querySelector("#btnText");

    // --------------------------------------------------------------------
    //  DATATABLE GRUPOS
    // --------------------------------------------------------------------
    tableGrupos = $("#tableGrupos").dataTable({
      aProcessing: true,
      aServerSide: true,
      ajax: {
        url: " " + base_url + "/cli_grupos/index",
        dataSrc: "",
      },
      columns: [
        { data: "id" },
        { data: "codigo" },
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
      '#nav-tab a[href="#listgrupos"]'
    );
    const firstTabEl = document.querySelector(
      '#nav-tab a[href="#agregargrupo"]'
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
        formGrupos.reset();
        idgrupo.value = "";
        estado.value = "2";
      });

      // ----------------------------------------------------------------
      // CLICK EN "GRUPOS" → RESETEAR NAV A NUEVO
      // ----------------------------------------------------------------
      primerTabEl.addEventListener("click", () => {
        tabNuevo.textContent = "NUEVO";
        spanBtnText.textContent = "REGISTRAR";
        idgrupo.value = "";
        estado.value = "2";
        formGrupos.reset();
      });
    } else {
      console.warn("Tabs de lineas no encontrados o btnText faltante.");
    }

    // --------------------------------------------------------------------
    // FORM → CREAR / ACTUALIZAR GRUPO
    // --------------------------------------------------------------------
    formGrupos.addEventListener("submit", function (e) {
      e.preventDefault();

      divLoading.style.display = "flex";

      let request = window.XMLHttpRequest
        ? new XMLHttpRequest()
        : new ActiveXObject("Microsoft.XMLHTTP");
      let ajaxUrl = base_url + "/cli_grupos/setGrupo";
      let formData = new FormData(formGrupos);

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
                formGrupos.reset();
                idgrupo.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                tableGrupos.api().ajax.reload();
              } else {
                // Regresar al listado
                formGrupos.reset();
                idgrupo.value = "";
                estado.value = "2";
                tabNuevo.textContent = "NUEVO";
                spanBtnText.textContent = "REGISTRAR";
                primerTab.show();
                tableGrupos.api().ajax.reload();
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
              formGrupos.reset();
              idgrupo.value = "";
              estado.value = "2";
              tabNuevo.textContent = "NUEVO";
              spanBtnText.textContent = "REGISTRAR";
              primerTab.show();
              tableGrupos.api().ajax.reload();
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
// FUNCIÓN EDITAR GRUPO → MODO ACTUALIZAR
// ------------------------------------------------------------------------
function fntEditInfo(id_grupo) {
  // Cambiar textos a modo ACTUALIZAR
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_grupos/show/" + id_grupo;

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        idgrupo.value = objData.data.id;
        codigoInput.value = objData.data.codigo;
        nombreInput.value = objData.data.nombre;
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
function fntDelGrupo(idgrupo) {
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
    let ajaxUrl = base_url + "/cli_grupos/destroy";
    let strData = "idgrupo=" + idgrupo;

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
          tableGrupos.api().ajax.reload();
        } else {
          Swal.fire("Atención!", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  VER EL DETALLE DE LA GRUPO
// ------------------------------------------------------------------------
function fntViewGrupo(idgrupo) {
  let request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject("Microsoft.XMLHTTP");
  let ajaxUrl = base_url + "/cli_grupos/show/" + idgrupo;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      let objData = JSON.parse(request.responseText);

      if (objData.status) {
        let estadoGrupo =
          objData.data.estado == 2
            ? '<span class="badge bg-success">Activo</span>'
            : '<span class="badge bg-danger">Inactivo</span>';

        document.querySelector("#idGrupo").innerHTML = objData.data.id;
        document.querySelector("#codigoGrupo").innerHTML = objData.data.codigo;
        document.querySelector("#nombreGrupo").innerHTML = objData.data.nombre;
        document.querySelector("#descripcionGrupo").innerHTML =
          objData.data.descripcion;
        document.querySelector("#fechaGrupo").innerHTML =
          objData.data.fecha_registro;
        document.querySelector("#estadoGrupo").innerHTML = estadoGrupo;

        let htmlDistribuidores = "";
        let distribuidores = objData.data.distribuidores;

        if (distribuidores.length > 0) {
          distribuidores.forEach((dist) => {
            htmlDistribuidores += `
              <tr>
                <td>${dist.nombre_comercial}</td>
                <td>${dist.razon_social}</td>
                <td>${dist.rfc}</td>
                <td>${dist.telefono}</td>
                <td>${dist.plaza}</td>
              </tr>
            `;
          });
        } else {
          htmlDistribuidores = `
            <tr>
              <td colspan="5" class="text-center text-muted">
                No hay distribuidores asignados
              </td>
            </tr>
          `;
        }

        document.querySelector("#tableDistribuidoresGrupo").innerHTML =
          htmlDistribuidores;

        $("#modalViewGrupo").modal("show");
      } else {
        Swal.fire("Error", objData.msg, "error");
      }
    }
  };
}
