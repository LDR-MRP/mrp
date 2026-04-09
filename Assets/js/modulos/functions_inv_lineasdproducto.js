let tableLineasProducto;
let rowTable = "";
let divLoading = document.querySelector("#divLoading");

// Inputs del formulario
const cve_linea_producto = document.querySelector(
  "#clave-linea-producto-input",
);
const estado = document.querySelector("#estado-select");
const descripcion = document.querySelector(
  "#descripcion-linea-producto-textarea",
);

// Mis referencias globales
let primerTab;
let firstTab;
let tabNuevo;
let spanBtnText = null;
let formLineasProducto = null;

document.addEventListener("DOMContentLoaded", function () {
  formLineasProducto = document.querySelector("#formLineasProducto");
  spanBtnText = document.querySelector("#btnText");

  tableLineasProducto = $("#tableLineasProducto").dataTable({
    aProcessing: true,
    aServerSide: false,
    ajax: {
      url: base_url + "/Inv_lineasdproducto/getLineasProductos",
      dataSrc: "",
    },
    columns: [
      { data: "cve_linea_producto" },
      { data: "descripcion" },
      { data: "fecha_creacion" },
      { data: "estado" },
      { data: "options" },
    ],
    dom: "lBfrtip",
    buttons: [],
    responsive: true,
    bDestroy: true,
    iDisplayLength: 10,
    order: [[0, "desc"]],
  });

  const primerTabEl = document.querySelector(
    '#nav-tab a[href="#listlineasproductos"]',
  );
  const firstTabEl = document.querySelector(
    '#nav-tab a[href="#agregarlineasproducto"]',
  );

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);
    tabNuevo = firstTabEl;

    tabNuevo.addEventListener("click", () => {
      spanBtnText.textContent = "REGISTRAR";
      formLineasProducto.reset();
      document.querySelector("#idlineaproducto").value = 0;
    });
  }

  formLineasProducto.addEventListener("submit", function (e) {
    e.preventDefault();

    let formData = new FormData(formLineasProducto);
    let url = base_url + "/Inv_lineasdproducto/setLineaProducto";

    fetch(url, {
      method: "POST",
      body: formData,
    })
      .then((res) => res.json())
      .then((objData) => {
        if (objData.status) {
          $("#tableLineasProducto").DataTable().ajax.reload();
          primerTab.show();
          Swal.fire("Correcto", objData.msg, "success");
          formLineasProducto.reset();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      });
  });
});

// ----------------------------------------------
// VER DETALLE
// ----------------------------------------------
function fntViewLineaProducto(id) {
  fetch(base_url + "/Inv_lineasdproducto/getLineaProducto/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#celClave").innerHTML =
          objData.data.cve_linea_producto;
        document.querySelector("#celDescripcion").innerHTML =
          objData.data.descripcion;
        document.querySelector("#celFecha").innerHTML =
          objData.data.fecha_creacion;
        document.querySelector("#celEstado").innerHTML =
          objData.data.estado == 2 ? "Activo" : "Inactivo";

        $("#modalViewLineaProducto").modal("show");
      }
    });
}

// ----------------------------------------------
// EDITAR
// ----------------------------------------------
function fntEditLineaProducto(id) {
  fetch(base_url + "/Inv_lineasdproducto/getLineaProducto/" + id)
    .then((res) => res.json())
    .then((objData) => {
      if (objData.status) {
        document.querySelector("#idlineaproducto").value =
          objData.data.idlineaproducto;
        cve_linea_producto.value = objData.data.cve_linea_producto;
        descripcion.value = objData.data.descripcion;
        estado.value = objData.data.estado;

        spanBtnText.textContent = "ACTUALIZAR";
        firstTab.show();
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
      cancelButton: "btn btn-danger w-xs mb-1",
    },
    buttonsStyling: false,
    showCloseButton: true,
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = window.XMLHttpRequest
      ? new XMLHttpRequest()
      : new ActiveXObject("Microsoft.XMLHTTP");
    let ajaxUrl = base_url + "/Inv_lineasdproducto/delLineaProducto";
    let strData = "idlineaproducto=" + idlineaproducto;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader(
      "Content-type",
      "application/x-www-form-urlencoded",
    );
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);

        if (objData.status) {
          Swal.fire("Correcto", objData.msg, "success");
          $("#tableLineasProducto").DataTable().ajax.reload();
        } else {
          Swal.fire("Error", objData.msg, "error");
        }
      }
    };
  });
}

// ------------------------------------------------------------------------
//  AGREGAR SUBMODULOS A LA LINEA DE PRODUCTO
// ------------------------------------------------------------------------
function fntEstructuraLinea(idLinea) {
  document.querySelector("#idLineaEstructura").value = idLinea;
  $("#modalEstructuraLinea").modal("show");
  cargarSublineas(idLinea);
}

function cargarSublineas(idLinea) {
  fetch(base_url + "/Inv_lineasdproducto/getSublineas/" + idLinea)
    .then((res) => res.json())
    .then((data) => {
      let html = "";

      data.forEach((s) => {
        html += `
          <tr>
            <td>${s.cve_sublinea_producto}</td>
            <td>${s.descripcion}</td>
            <td>${s.estado == 2 ? "Activo" : "Inactivo"}</td>
            <td>
              <button class="btn btn-sm btn-warning" onclick="editarSublinea(${s.idsublineaproducto})">✏</button>
              <button class="btn btn-sm btn-danger" onclick="eliminarSublinea(${s.idsublineaproducto})">🗑</button>
            </td>
          </tr>
        `;
      });

      document.querySelector("#tbodySublineas").innerHTML = html;
    });
}

function nuevoSublinea() {
  document.querySelector("#formSublineaContainer").classList.remove("d-none");

  document.querySelector("#sub_cve").value = "";
  document.querySelector("#sub_desc").value = "";
}

function guardarSublinea() {
  let cve = document.querySelector("#sub_cve").value.trim();
  let desc = document.querySelector("#sub_desc").value.trim();
  let idLinea = document.querySelector("#idLineaEstructura").value;

  if (cve === "" || desc === "") {
    Swal.fire("Atención", "Todos los campos son obligatorios", "warning");
    return;
  }

  fetch(base_url + "/Inv_lineasdproducto/setSublinea", {
    method: "POST",
    body: new URLSearchParams({
      lineaproductoid: idLinea,
      cve: cve,
      descripcion: desc,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status) {
        Swal.fire("Correcto", data.msg, "success");

        // refrescar tabla
        cargarSublineas(idLinea);

        // ocultar form
        document
          .querySelector("#formSublineaContainer")
          .classList.add("d-none");
      } else {
        Swal.fire("Error", data.msg, "error");
      }
    });
}

function editarSublinea(id) {
  let fila = event.target.closest("tr");

  let cve = fila.children[0].innerText;
  let desc = fila.children[1].innerText;

  fila.innerHTML = `
    <td><input class="form-control form-control-sm" id="edit_cve_${id}" value="${cve}"></td>
    <td><input class="form-control form-control-sm" id="edit_desc_${id}" value="${desc}"></td>
    <td>EDITANDO</td>
    <td>
      <button class="btn btn-sm btn-success" onclick="actualizarSublinea(${id})">💾</button>
      <button class="btn btn-sm btn-secondary" onclick="recargarSublineas()">❌</button>
    </td>
  `;
}

function actualizarSublinea(id) {
  let cve = document.querySelector(`#edit_cve_${id}`).value.trim();
  let desc = document.querySelector(`#edit_desc_${id}`).value.trim();
  let idLinea = document.querySelector("#idLineaEstructura").value;

  if (cve === "" || desc === "") {
    Swal.fire("Atención", "Campos obligatorios", "warning");
    return;
  }

  fetch(base_url + "/Inv_lineasdproducto/updateSublinea", {
    method: "POST",
    body: new URLSearchParams({
      idsublinea: id,
      cve: cve,
      descripcion: desc,
    }),
  })
    .then((res) => res.json())
    .then((data) => {
      if (data.status) {
        Swal.fire("Correcto", data.msg, "success");
        cargarSublineas(idLinea);
      } else {
        Swal.fire("Error", data.msg, "error");
      }
    });
}

function recargarSublineas() {
  let idLinea = document.querySelector("#idLineaEstructura").value;
  cargarSublineas(idLinea);
}

function eliminarSublinea(id) {
  Swal.fire({
    title: "¿Eliminar?",
    text: "Se desactivará la sublínea",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar"
  }).then((result) => {
    if (!result.isConfirmed) return;

    fetch(base_url + "/Inv_lineasdproducto/deleteSublinea", {
      method: "POST",
      body: new URLSearchParams({
        idsublinea: id
      })
    })
      .then(res => res.json())
      .then(data => {
        let idLinea = document.querySelector("#idLineaEstructura").value;

        if (data.status) {
          Swal.fire("Correcto", data.msg, "success");
          cargarSublineas(idLinea);
        } else {
          Swal.fire("Error", data.msg, "error");
        }
      });
  });
}
