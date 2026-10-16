let tableTransmisiones;
let tableTiposTransmision;
let divLoading = null;
let formTransmisiones = null;
let idTransmisionInput = null;
let primerTab = null;
let firstTab = null;
let tabNuevo = null;
let spanBtnText = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");
  formTransmisiones = document.querySelector("#formTransmisiones");
  spanBtnText = document.querySelector("#btnText");
  idTransmisionInput = document.querySelector("#id_transmision");

  if (!formTransmisiones) {
    console.warn("formTransmisiones no encontrado. JS de transmisiones no se inicializa en esta vista.");
    return;
  }

  tableTransmisiones = $("#tableTransmisiones").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_transmisiones/getTransmisiones",
      dataSrc: "",
    },
    columns: [
      { data: "fabricante" },
      { data: "modelo" },
      { data: "tipo" },
      { data: "numero_velocidades" },
      { data: "activo_label" },
      { data: "options" },
    ],
    responsive: true,
    destroy: true,
    pageLength: 10,
  });

  fntCargarSelectTipos();
  fntInitTiposTransmision();

  const primerTabEl = document.querySelector('#nav-tab a[href="#listTransmisiones"]');
  const firstTabEl = document.querySelector('#nav-tab a[href="#agregarTransmision"]');

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
    if (idTransmisionInput) idTransmisionInput.value = "0";
    if (formTransmisiones) formTransmisiones.reset();
  }

  formTransmisiones.addEventListener("submit", function (e) {
    e.preventDefault();
    if (divLoading) divLoading.style.display = "flex";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_transmisiones/setTransmision";
    let formData = new FormData(formTransmisiones);

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
          if (tableTransmisiones) tableTransmisiones.ajax.reload();
          if (primerTab) primerTab.show();
        });
      } else {
        Swal.fire("Atención", objData.msg, "warning");
      }
    };
  });
});

function fntEditInfo(idTransmision) {
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Ing_transmisiones/getTransmision/" + idTransmision;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Error al consultar la transmisión.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    let d = objData.data;
    document.querySelector("#id_transmision").value = d.id_transmision;
    document.querySelector("#fabricante-input").value = d.fabricante || "";
    document.querySelector("#modelo-input").value = d.modelo || "";
    fntSetTipoSelect(d.id_tipo_transmision);
    document.querySelector("#velocidades-input").value = d.numero_velocidades || "";
    document.querySelector("#descripcion-textarea").value = d.descripcion || "";
    document.querySelector("#activo-select").value = d.activo;

    if (firstTab) firstTab.show();
  };
}

function fntDelInfo(idTransmision) {
  Swal.fire({
    title: "Confirmar eliminación",
    text: "¿Estás seguro de que deseas eliminar esta transmisión? Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_transmisiones/delTransmision";
    let strData = "id_transmision=" + idTransmision;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableTransmisiones) tableTransmisiones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "error");
        }
      }
    };
  });
}

// =====================================================================
// CATÁLOGO DE TIPOS DE TRANSMISIÓN (ing_cat_tipo_transmision) 16-oct-2026
// =====================================================================

// Llena el select TIPO del formulario de transmisiones desde el catálogo.
// Devuelve una promesa para poder seleccionar un valor después de cargar.
function fntCargarSelectTipos() {
  const select = document.querySelector("#tipo-select");
  if (!select) return Promise.resolve();
  const valorActual = select.value;
  return fetch(base_url + "/Ing_transmisiones/getSelectTipos")
    .then((r) => r.text())
    .then((html) => {
      select.innerHTML = html;
      if (valorActual) select.value = valorActual;
    })
    .catch(() => console.error("No se pudo cargar el catálogo de tipos de transmisión."));
}

// Al editar: si el tipo de la transmisión está inactivo no viene en el
// select, así que se recarga y, si sigue sin existir, se agrega temporal.
function fntSetTipoSelect(idTipo) {
  const select = document.querySelector("#tipo-select");
  if (!select) return;
  if (!idTipo) { select.value = ""; return; }
  select.value = String(idTipo);
  if (select.value !== String(idTipo)) {
    fntCargarSelectTipos().then(() => {
      select.value = String(idTipo);
      if (select.value !== String(idTipo)) {
        const opt = document.createElement("option");
        opt.value = idTipo;
        opt.textContent = "(tipo inactivo)";
        select.appendChild(opt);
        select.value = String(idTipo);
      }
    });
  }
}

function fntInitTiposTransmision() {
  if (!document.querySelector("#tableTiposTransmision")) return;

  tableTiposTransmision = $("#tableTiposTransmision").DataTable({
    processing: true,
    serverSide: false,
    ajax: { url: base_url + "/Ing_transmisiones/getTipos", dataSrc: "" },
    columns: [
      { data: "clave" },
      { data: "descripcion" },
      { data: "total_transmisiones", className: "text-center" },
      { data: "activo_label" },
      { data: "options" },
    ],
    responsive: true,
    destroy: true,
    pageLength: 10,
  });

  // Al mostrar la pestaña, ajustar columnas (estaba oculta al crear la tabla)
  const tabTipos = document.querySelector('#nav-tab a[href="#tiposTransmision"]');
  if (tabTipos) {
    tabTipos.addEventListener("shown.bs.tab", () => tableTiposTransmision.columns.adjust().responsive.recalc());
  }

  const formTipo = document.querySelector("#formTipoTransmision");
  if (!formTipo) return;

  document.querySelector("#btnCancelarTipo").addEventListener("click", fntResetFormTipo);

  formTipo.addEventListener("submit", function (e) {
    e.preventDefault();
    if (divLoading) divLoading.style.display = "flex";

    fetch(base_url + "/Ing_transmisiones/setTipo", { method: "POST", body: new FormData(formTipo) })
      .then((r) => r.json())
      .then((objData) => {
        if (divLoading) divLoading.style.display = "none";
        if (objData.status) {
          Swal.fire({ title: objData.msg, icon: "success", confirmButtonColor: "#28a745" });
          fntResetFormTipo();
          tableTiposTransmision.ajax.reload();
          fntCargarSelectTipos();
          if (tableTransmisiones) tableTransmisiones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "warning");
        }
      })
      .catch(() => {
        if (divLoading) divLoading.style.display = "none";
        Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
      });
  });
}

function fntResetFormTipo() {
  const formTipo = document.querySelector("#formTipoTransmision");
  if (!formTipo) return;
  formTipo.reset();
  document.querySelector("#id_tipo_transmision").value = "0";
  document.querySelector("#btnTextTipo").textContent = "REGISTRAR";
}

function fntEditTipo(idTipo) {
  fetch(base_url + "/Ing_transmisiones/getTipo/" + idTipo)
    .then((r) => r.json())
    .then((objData) => {
      if (!objData.status) {
        Swal.fire("Error", objData.msg, "error");
        return;
      }
      const d = objData.data;
      document.querySelector("#id_tipo_transmision").value = d.id_tipo_transmision;
      document.querySelector("#tipo-clave-input").value = d.clave || "";
      document.querySelector("#tipo-descripcion-input").value = d.descripcion || "";
      document.querySelector("#tipo-activo-select").value = d.activo;
      document.querySelector("#btnTextTipo").textContent = "ACTUALIZAR";
      document.querySelector("#tipo-clave-input").focus();
    })
    .catch(() => Swal.fire("Error", "Error al consultar el tipo de transmisión.", "error"));
}

function fntDelTipo(idTipo) {
  Swal.fire({
    title: "Confirmar eliminación",
    text: "¿Deseas eliminar este tipo de transmisión?",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (!result.isConfirmed) return;
    const body = new URLSearchParams({ id_tipo_transmision: idTipo });
    fetch(base_url + "/Ing_transmisiones/delTipo", { method: "POST", body })
      .then((r) => r.json())
      .then((objData) => {
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          tableTiposTransmision.ajax.reload();
          fntCargarSelectTipos();
        } else {
          Swal.fire("Atención", objData.msg, "warning");
        }
      });
  });
}
