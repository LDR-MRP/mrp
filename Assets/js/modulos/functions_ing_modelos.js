let tableModelos;
let divLoading = null;
let formDetalleModelo = null;

document.addEventListener("DOMContentLoaded", function () {
  divLoading = document.querySelector("#divLoading");
  formDetalleModelo = document.querySelector("#formDetalleModelo");

  if (!document.querySelector("#tableModelos")) {
    console.warn("tableModelos no encontrada. JS de modelos no se inicializa en esta vista.");
    return;
  }

  tableModelos = $("#tableModelos").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_modelos/getModelos",
      dataSrc: "",
    },
    columns: [
      { data: "segmento" },
      { data: "modelo" },
      { data: "marca" },
      { data: "tipo_carroceria" },
      { data: "estado" },
      { data: "version" },
      { data: "detalle_label" },
      { data: "options" },
    ],
    responsive: true,
    destroy: true,
    pageLength: 10,
  });

  if (formDetalleModelo) {
    formDetalleModelo.addEventListener("submit", function (e) {
      e.preventDefault();
      if (divLoading) divLoading.style.display = "flex";

      let request = new XMLHttpRequest();
      let ajaxUrl = base_url + "/Ing_modelos/setModeloDetalle";
      let formData = new FormData(formDetalleModelo);

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
          $("#modalDetalleModelo").modal("hide");
          Swal.fire("¡Operación exitosa!", objData.msg, "success");
          if (tableModelos) tableModelos.ajax.reload();
        } else {
          Swal.fire("Atención", objData.msg, "warning");
        }
      };
    });
  }
});

function fntEditInfo(idSublinea) {
  let request = new XMLHttpRequest();
  let ajaxUrl = base_url + "/Ing_modelos/getModelo/" + idSublinea;
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    if (request.status !== 200) {
      Swal.fire("Error", "Error al consultar el modelo.", "error");
      return;
    }

    let objData = JSON.parse(request.responseText);
    if (!objData.status) {
      Swal.fire("Error", objData.msg, "error");
      return;
    }

    let d = objData.data;
    document.querySelector("#id_sublineaproducto").value = d.idsublineaproducto;
    document.querySelector("#modelo-readonly").value = (d.segmento || "") + " / " + (d.modelo || "");
    document.querySelector("#marca-input").value = d.marca || "FOTON";
    document.querySelector("#carroceria-input").value = d.tipo_carroceria || "";
    document.querySelector("#estado-select").value = d.estado || "ACTIVO";
    document.querySelector("#version-input").value = d.version || "";
    document.querySelector("#fecha-inicio-input").value = d.fecha_inicio || "";
    document.querySelector("#fecha-fin-input").value = d.fecha_fin || "";

    $("#modalDetalleModelo").modal("show");
  };
}
