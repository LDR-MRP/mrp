let tableCertificaciones;
let divLoading = null;
let formCertificaciones = null;
let idCertificacionInput = null;
let primerTab = null;
let firstTab = null;
let tabNuevo = null;
let spanBtnText = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");
  formCertificaciones = document.querySelector("#formCertificaciones");
  spanBtnText = document.querySelector("#btnText");
  idCertificacionInput = document.querySelector("#id_certificacion");

  if (!formCertificaciones) {
    console.warn("formCertificaciones no encontrado. JS de certificaciones no se inicializa en esta vista.");
    return;
  }

  tableCertificaciones = $("#tableCertificaciones").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_certificaciones/getCertificaciones",
      dataSrc: "",
    },
    columns: [
      { data: "codigo" },
      { data: "nombre" },
      { data: "autoridad" },
      { data: "requiere_documento_label" },
      { data: "requiere_vigencia_label" },
      { data: "activo_label" },
      { data: "options" },
    ],
    responsive: true,
    destroy: true,
    pageLength: 10,
  });

  const primerTabEl = document.querySelector('#nav-tab a[href="#listCertificaciones"]');
  const firstTabEl = document.querySelector('#nav-tab a[href="#agregarCertificacion"]');

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
    if (idCertificacionInput) idCertificacionInput.value = "0";
    if (formCertificaciones) formCertificaciones.reset();
    document.querySelector("#requiere-documento-check").checked = true;
    document.querySelector("#requiere-vigencia-check").checked = true;
  }

  formCertificaciones.addEventListener("submit", function (e) {
    e.preventDefault();
    if (divLoading) divLoading.style.display = "flex";

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_certificaciones/setCertificacion";
    let formData = new FormData(formCertificaciones);

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
          if (tableCertificaciones) tableCertificaciones.ajax.reload();
          if (primerTab) primerTab.show();
        });
      } else {
        Swal.fire("Atención", objData.msg, "warning");
      }
    };
  });
});

function fntEditInfo(idCertificacion) {
  if (tabNuevo) tabNuevo.textContent = "ACTUALIZAR";
  if (spanBtnText) spanBtnText.textContent = "ACTUALIZAR";

  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Ing_certificaciones/getCertificacion/" + idCertificacion;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Error al consultar la certificación.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    let d = objData.data;
    document.querySelector("#id_certificacion").value = d.id_certificacion;
    document.querySelector("#codigo-input").value = d.codigo || "";
    document.querySelector("#nombre-input").value = d.nombre || "";
    document.querySelector("#autoridad-input").value = d.autoridad || "";
    document.querySelector("#tipo-input").value = d.tipo || "";
    document.querySelector("#descripcion-textarea").value = d.descripcion || "";
    document.querySelector("#requiere-documento-check").checked = d.requiere_documento == 1;
    document.querySelector("#requiere-vigencia-check").checked = d.requiere_vigencia == 1;
    document.querySelector("#activo-select").value = d.activo;

    if (firstTab) firstTab.show();
  };
}

function fntDelInfo(idCertificacion) {
  Swal.fire({
    title: "Confirmar eliminación",
    text: "¿Estás seguro de que deseas eliminar esta certificación? Esta acción no se puede deshacer.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#dc3545",
  }).then((result) => {
    if (!result.isConfirmed) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + "/Ing_certificaciones/delCertificacion";
    let strData = "id_certificacion=" + idCertificacion;

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    request.send(strData);

    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200) {
        let objData = JSON.parse(request.responseText);
        if (objData.status) {
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableCertificaciones) tableCertificaciones.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "error");
        }
      }
    };
  });
}
