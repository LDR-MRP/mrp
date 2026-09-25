let tableTransmisiones;
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
    document.querySelector("#tipo-select").value = d.tipo || "TM";
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
