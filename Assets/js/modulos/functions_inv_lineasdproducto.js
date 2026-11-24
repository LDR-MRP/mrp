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
  document.querySelector("#formSublineaContainer").classList.add("d-none");

  // Nombre de la línea en el encabezado del modal
  let lbl = "";
  try {
    let tr = event && event.target ? $(event.target).closest("tr") : null;
    if (tr && tr.hasClass("child")) tr = tr.prev();
    let row = tr ? tableLineasProducto.api().row(tr).data() : null;
    if (row) lbl = "Línea: " + row.cve_linea_producto + (row.descripcion ? " — " + row.descripcion : "");
  } catch (e) {}
  document.querySelector("#lblLineaEstructura").textContent = lbl;

  $("#modalEstructuraLinea").modal("show");
  cargarSublineas(idLinea);
}

function badgeEstadoSublinea(estado) {
  return estado == 2
    ? '<span class="badge bg-success-subtle text-success text-uppercase">Activo</span>'
    : '<span class="badge bg-secondary-subtle text-secondary text-uppercase">Inactivo</span>';
}

function cargarSublineas(idLinea) {
  const tbody = document.querySelector("#tbodySublineas");
  tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-4">
      <span class="spinner-border spinner-border-sm me-2"></span>Cargando...</td></tr>`;

  fetch(base_url + "/Inv_lineasdproducto/getSublineas/" + idLinea)
    .then((res) => res.json())
    .then((data) => {
      document.querySelector("#countSublineas").textContent = data.length;

      if (!data.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-5">
            <i class="ri-inbox-line fs-2 d-block mb-1"></i>
            Esta línea aún no tiene sublíneas registradas</td></tr>`;
        return;
      }

      let html = "";
      data.forEach((s) => {
        html += `
          <tr data-fondo="${s.fondo_publicidad ?? 0}">
            <td class="fw-semibold">${s.cve_sublinea_producto}</td>
            <td>${s.descripcion}</td>
            <td class="text-end">${formatFondoPublicidad(s.fondo_publicidad)}</td>
            <td class="text-center">${badgeEstadoSublinea(s.estado)}</td>
            <td class="text-center">
              <button type="button" class="btn btn-sm btn-soft-warning" title="Editar" onclick="editarSublinea(${s.idsublineaproducto})"><i class="ri-pencil-line"></i></button>
              <button type="button" class="btn btn-sm btn-soft-danger" title="Eliminar" onclick="eliminarSublinea(${s.idsublineaproducto})"><i class="ri-delete-bin-line"></i></button>
            </td>
          </tr>
        `;
      });

      tbody.innerHTML = html;
    });
}

function formatFondoPublicidad(valor) {
  let n = parseFloat(valor);
  if (isNaN(n)) n = 0;
  return "$" + n.toLocaleString("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function nuevoSublinea() {
  document.querySelector("#formSublineaContainer").classList.remove("d-none");

  document.querySelector("#sub_cve").value = "";
  document.querySelector("#sub_desc").value = "";
  document.querySelector("#sub_fondo").value = "";
  document.querySelector("#sub_cve").focus();
}

function cancelarSublinea() {
  document.querySelector("#formSublineaContainer").classList.add("d-none");
}

function guardarSublinea() {
  let cve = document.querySelector("#sub_cve").value.trim();
  let desc = document.querySelector("#sub_desc").value.trim();
  let fondo = document.querySelector("#sub_fondo").value.trim();
  let idLinea = document.querySelector("#idLineaEstructura").value;

  if (cve === "" || desc === "") {
    Swal.fire("Atención", "Clave y descripción son obligatorias", "warning");
    return;
  }
  if (fondo !== "" && (isNaN(fondo) || parseFloat(fondo) < 0)) {
    Swal.fire("Atención", "El fondo de publicidad debe ser un número mayor o igual a 0", "warning");
    return;
  }

  fetch(base_url + "/Inv_lineasdproducto/setSublinea", {
    method: "POST",
    body: new URLSearchParams({
      lineaproductoid: idLinea,
      cve: cve,
      descripcion: desc,
      fondo_publicidad: fondo === "" ? 0 : fondo,
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
  let fondo = parseFloat(fila.dataset.fondo);
  if (isNaN(fondo)) fondo = 0;

  fila.classList.add("table-warning");
  fila.innerHTML = `
    <td><input class="form-control form-control-sm" id="edit_cve_${id}" value="${cve}"></td>
    <td><input class="form-control form-control-sm" id="edit_desc_${id}" value="${desc}"></td>
    <td>
      <div class="input-group input-group-sm">
        <span class="input-group-text">$</span>
        <input type="number" step="0.01" min="0" class="form-control text-end" id="edit_fondo_${id}" value="${fondo.toFixed(2)}">
      </div>
    </td>
    <td class="text-center"><span class="badge bg-warning-subtle text-warning text-uppercase">Editando</span></td>
    <td class="text-center">
      <button type="button" class="btn btn-sm btn-success" title="Guardar" onclick="actualizarSublinea(${id})"><i class="ri-save-line"></i></button>
      <button type="button" class="btn btn-sm btn-light" title="Cancelar" onclick="recargarSublineas()"><i class="ri-close-line"></i></button>
    </td>
  `;
}

function actualizarSublinea(id) {
  let cve = document.querySelector(`#edit_cve_${id}`).value.trim();
  let desc = document.querySelector(`#edit_desc_${id}`).value.trim();
  let fondo = document.querySelector(`#edit_fondo_${id}`).value.trim();
  let idLinea = document.querySelector("#idLineaEstructura").value;

  if (cve === "" || desc === "") {
    Swal.fire("Atención", "Clave y descripción son obligatorias", "warning");
    return;
  }
  if (fondo !== "" && (isNaN(fondo) || parseFloat(fondo) < 0)) {
    Swal.fire("Atención", "El fondo de publicidad debe ser un número mayor o igual a 0", "warning");
    return;
  }

  fetch(base_url + "/Inv_lineasdproducto/updateSublinea", {
    method: "POST",
    body: new URLSearchParams({
      idsublinea: id,
      cve: cve,
      descripcion: desc,
      fondo_publicidad: fondo === "" ? 0 : fondo,
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
