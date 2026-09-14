let tableEspecificaciones;
let divLoading = null;
let formEspecificaciones = null;
let idEspecificacionInput = null;
let primerTab = null;
let firstTab = null;
let tabNuevo = null;
let spanBtnText = null;
let collapsedCategorias = {};
let modalCategoriasEl = null;
let modalCategorias = null;
let tbodyCategoriasEl = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");
  formEspecificaciones = document.querySelector("#formEspecificaciones");
  spanBtnText = document.querySelector("#btnText");
  idEspecificacionInput = document.querySelector("#id_especificacion");

  if (!formEspecificaciones) {
    console.warn("formEspecificaciones no encontrado. JS de especificaciones no se inicializa en esta vista.");
    return;
  }

  cargarSelectCategorias();

  let btnNuevaCategoria = document.querySelector("#btnNuevaCategoria");
  if (btnNuevaCategoria) {
    btnNuevaCategoria.addEventListener("click", function () {
      Swal.fire({
        title: "Nueva categoría",
        input: "text",
        inputPlaceholder: "Ej. Iluminación",
        showCancelButton: true,
        confirmButtonText: "Agregar",
        cancelButtonText: "Cancelar",
        confirmButtonColor: "#28a745",
        inputValidator: function (value) {
          if (!value || !value.trim()) {
            return "El nombre de la categoría es obligatorio";
          }
        },
      }).then((result) => {
        if (!result.isConfirmed) return;

        let request = new XMLHttpRequest();
        let strData = "nombre=" + encodeURIComponent(result.value.trim());

        request.open("POST", base_url + "/Ing_especificaciones/setCategoria", true);
        request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        request.send(strData);

        request.onreadystatechange = function () {
          if (request.readyState !== 4 || request.status !== 200) return;
          let objData = JSON.parse(request.responseText);
          if (objData.status) {
            cargarSelectCategorias(objData.nombre);
            Swal.fire("¡Operación exitosa!", objData.msg, "success");
          } else {
            Swal.fire("Atención", objData.msg, "warning");
          }
        };
      });
    });
  }

  modalCategoriasEl = document.querySelector("#modalCategorias");
  if (modalCategoriasEl) modalCategorias = new bootstrap.Modal(modalCategoriasEl);
  tbodyCategoriasEl = document.querySelector("#tbodyCategorias");

  let btnGestionarCategorias = document.querySelector("#btnGestionarCategorias");
  if (btnGestionarCategorias) {
    btnGestionarCategorias.addEventListener("click", function () {
      cargarCategorias();
    });
  }

  tableEspecificaciones = $("#tableEspecificaciones").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_especificaciones/getEspecificaciones",
      dataSrc: "",
    },
    columns: [
      { data: "categoria" },
      { data: "clave" },
      { data: "unidad" },
      { data: "desglose_facturacion_label" },
      { data: "orden" },
      { data: "activo_label" },
      { data: "options" },
    ],
    responsive: true,
    destroy: true,
    paging: false,
    order: [[0, "asc"], [3, "asc"], [1, "asc"]],
    rowGroup: {
      dataSrc: "categoria",
      startRender: function (rows, group) {
        if (collapsedCategorias[group] === undefined) {
          collapsedCategorias[group] = true; // retraído al entrar
        }
        let collapsed = collapsedCategorias[group];

        rows.nodes().each(function (r) {
          r.style.display = collapsed ? "none" : "";
        });

        return $(`
          <tr class="categoria-group-row" data-categoria="${group}">
            <td colspan="7">
              <i class="ri-arrow-${collapsed ? "right" : "down"}-s-line categoria-group-icon"></i>
              <span class="categoria-group-title">${group}</span>
              <span class="categoria-group-badge">${rows.count()}</span>
            </td>
          </tr>
        `);
      },
    },
  });

  $("#tableEspecificaciones tbody").on("click", "tr.categoria-group-row", function () {
    let group = $(this).data("categoria");
    collapsedCategorias[group] = !collapsedCategorias[group];
    tableEspecificaciones.draw(false);
  });

  const primerTabEl = document.querySelector('#nav-tab a[href="#listEspecificaciones"]');
  const firstTabEl = document.querySelector('#nav-tab a[href="#agregarEspecificacion"]');

  if (primerTabEl && firstTabEl && spanBtnText) {
    primerTab = new bootstrap.Tab(primerTabEl);
    firstTab = new bootstrap.Tab(firstTabEl);
    tabNuevo = firstTabEl;

    tabNuevo.addEventListener("click", resetForm);
    primerTabEl.addEventListener("click", resetForm);
  }

  function resetForm() {
    if (tabNuevo) tabNuevo.textContent = "NUEVO";
    if (spanBtnText) spanBtnText.textContent = "REGISTRAR";
    if (idEspecificacionInput) idEspecificacionInput.value = "0";
    if (formEspecificaciones) formEspecificaciones.reset();
  }

  formEspecificaciones.addEventListener("submit", function (e) {
    e.preventDefault();
    if (divLoading) divLoading.style.display = "flex";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_especificaciones/setEspecificacion";
    let formData = new FormData(formEspecificaciones);

    request.open("POST", ajaxUrl, true);
    request.send(formData);

    request.onreadystatechange = function () {
      if (request.readyState !== 4) return;
      if (divLoading) divLoading.style.display = "none";

      if (request.status !== 200) {
        Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        return;
      }

      let objData = JSON.parse(request.responseText);
      if (objData.status) {
        Swal.fire({
          title: objData.msg,
          icon: "success",
          confirmButtonText: "OK",
          confirmButtonColor: "#28a745",
        }).then(() => {
          resetForm();
          if (tableEspecificaciones) tableEspecificaciones.ajax.reload();
          if (primerTab) primerTab.show();
        });
      } else {
        Swal.fire("Atención", objData.msg, "warning");
      }
    };
  });
});

function cargarSelectCategorias(seleccionar) {
  let sel = document.querySelector("#categoria-select");
  if (!sel) return;
  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_especificaciones/getSelectCategorias", true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      sel.innerHTML = request.responseText;
      if (seleccionar) sel.value = seleccionar;
    }
  };
}

// --------------------------------------------------------------
// MODAL "GESTIONAR CATEGORÍAS": editar nombre/orden, activar/desactivar.
// Catálogo de soporte chico (sin pantalla propia) — se administra aquí
// mismo, dentro de Especificaciones.
// --------------------------------------------------------------
function cargarCategorias() {
  if (!tbodyCategoriasEl) return;
  let request = new XMLHttpRequest();
  request.open("GET", base_url + "/Ing_especificaciones/getCategorias", true);
  request.send();
  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
      return;
    }
    let objData = JSON.parse(request.responseText);
    renderCategorias(objData);
  };
}

function renderCategorias(data) {
  if (!tbodyCategoriasEl) return;

  if (!data || !data.length) {
    tbodyCategoriasEl.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Sin categorías registradas.</td></tr>';
    return;
  }

  let html = "";
  data.forEach(function (c) {
    let nombreSeguro = (c.nombre || "").replace(/"/g, "&quot;");
    let ordenValor = c.orden === null || c.orden === undefined ? "" : c.orden;
    let checked = parseInt(c.activo) === 1 ? " checked" : "";

    html += '<tr data-id="' + c.id_categoria + '">';
    html += '<td><input type="text" class="form-control form-control-sm cat-nombre-input" value="' + nombreSeguro + '"></td>';
    html += '<td><input type="number" class="form-control form-control-sm cat-orden-input" value="' + ordenValor + '"></td>';
    html += '<td class="text-center"><span class="badge bg-secondary-subtle text-secondary">' + (c.total_especificaciones || 0) + '</span></td>';
    html +=
      '<td class="text-center"><div class="form-check form-switch d-flex justify-content-center mb-0">' +
      '<input class="form-check-input cat-activo-check" type="checkbox" role="switch"' +
      checked +
      "></div></td>";
    html += '<td class="text-center"><button type="button" class="btn btn-sm btn-soft-primary btn-guardar-categoria" title="Guardar cambios"><i class="ri-save-line"></i></button></td>';
    html += "</tr>";
  });

  tbodyCategoriasEl.innerHTML = html;

  tbodyCategoriasEl.querySelectorAll(".btn-guardar-categoria").forEach(function (btn) {
    btn.addEventListener("click", function () {
      let tr = btn.closest("tr");
      let idCategoria = tr.dataset.id;
      let nombre = tr.querySelector(".cat-nombre-input").value.trim();
      let orden = tr.querySelector(".cat-orden-input").value;
      let activo = tr.querySelector(".cat-activo-check").checked ? 1 : 0;
      let totalUso = parseInt(tr.querySelector(".badge").textContent) || 0;

      if (!nombre) {
        Swal.fire("Atención", "El nombre de la categoría es obligatorio.", "warning");
        return;
      }

      if (activo === 0 && totalUso > 0) {
        Swal.fire({
          title: "¿Desactivar categoría en uso?",
          text:
            "Esta categoría tiene " +
            totalUso +
            " especificación(es) ya capturada(s). No se ven afectadas, pero la categoría dejará de estar disponible para especificaciones nuevas. ¿Continuar?",
          icon: "warning",
          showCancelButton: true,
          confirmButtonText: "Sí, desactivar",
          cancelButtonText: "Cancelar",
        }).then(function (result) {
          if (result.isConfirmed) guardarCategoria(idCategoria, nombre, orden, activo);
        });
      } else {
        guardarCategoria(idCategoria, nombre, orden, activo);
      }
    });
  });
}

function guardarCategoria(idCategoria, nombre, orden, activo) {
  let request = new XMLHttpRequest();
  let strData =
    "id_categoria=" +
    encodeURIComponent(idCategoria) +
    "&nombre=" +
    encodeURIComponent(nombre) +
    "&orden=" +
    encodeURIComponent(orden) +
    "&activo=" +
    encodeURIComponent(activo);

  request.open("POST", base_url + "/Ing_especificaciones/updateCategoria", true);
  request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
  request.send(strData);

  request.onreadystatechange = function () {
    if (request.readyState !== 4 || request.status !== 200) return;
    let objData = JSON.parse(request.responseText);
    if (objData.status) {
      Swal.fire({ title: "¡Operación exitosa!", text: objData.msg, icon: "success", timer: 1800, showConfirmButton: false });
      cargarCategorias();
      cargarSelectCategorias();
      if (tableEspecificaciones) tableEspecificaciones.ajax.reload(null, false);
    } else {
      Swal.fire("Atención", objData.msg, "warning");
    }
  };
}

function fntEditInfo(idEspecificacion) {
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Ing_especificaciones/getEspecificacion/" + idEspecificacion;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Error al consultar la especificación.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    let d = objData.data;
    document.querySelector("#id_especificacion").value = d.id_especificacion;
    document.querySelector("#categoria-select").value = d.categoria || "";
    document.querySelector("#clave-input").value = d.clave || "";
    document.querySelector("#unidad-input").value = d.unidad || "";
    document.querySelector("#desglose-facturacion-check").checked = d.desglose_facturacion == 1;
    document.querySelector("#orden-input").value = d.orden || "";
    document.querySelector("#activo-select").value = d.activo;

    if (firstTab) firstTab.show();
  };
}

function fntDelInfo(idEspecificacion) {
  Swal.fire({
    title: "Confirmar eliminación",
    text: "¿Estás seguro de que deseas eliminar esta especificación? Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_especificaciones/delEspecificacion";
    let strData = "id_especificacion=" + idEspecificacion;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableEspecificaciones) tableEspecificaciones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "error");
        }
      }
    };
  });
}
