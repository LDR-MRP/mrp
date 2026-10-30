// Inventario > Catálogos > Colores de unidad
let tableColores;
let tabListaColores = null;
let tabNuevoColor = null;

document.addEventListener("DOMContentLoaded", function () {
  const formColor = document.querySelector("#formColor");
  const btnText = document.querySelector("#btnTextColor");

  tableColores = $("#tableColores").DataTable({
    ajax: { url: base_url + "/Inv_coloresunidad/getColores", dataSrc: "" },
    columns: [
      { data: "nombre" },
      { data: "descripcion" },
      { data: "fecha_creacion" },
      { data: "estado_badge" },
      { data: "options", orderable: false },
    ],
    responsive: true,
    destroy: true,
    pageLength: 10,
    order: [[0, "asc"]],
  });

  const listaEl = document.querySelector('#nav-tab a[href="#listColores"]');
  const nuevoEl = document.querySelector('#nav-tab a[href="#agregarColor"]');
  if (listaEl) tabListaColores = new bootstrap.Tab(listaEl);
  if (nuevoEl) {
    tabNuevoColor = new bootstrap.Tab(nuevoEl);
    nuevoEl.addEventListener("click", () => limpiarFormColor());
  }

  function limpiarFormColor() {
    formColor.reset();
    document.querySelector("#idcolor").value = 0;
    if (btnText) btnText.textContent = "REGISTRAR";
  }

  if (formColor) {
    formColor.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!document.querySelector("#color_nombre").value.trim()) {
        Swal.fire("Aviso", "Captura el nombre del color", "warning");
        return;
      }
      fetch(base_url + "/Inv_coloresunidad/setColor", { method: "POST", body: new FormData(formColor) })
        .then((r) => r.json())
        .then((res) => {
          if (res.status) {
            Swal.fire("Correcto", res.msg, "success");
            tableColores.ajax.reload();
            limpiarFormColor();
            if (tabListaColores) tabListaColores.show();
          } else {
            Swal.fire("Error", res.msg, "error");
          }
        })
        .catch(() => Swal.fire("Error", "No se pudo guardar el color", "error"));
    });
  }

  window.fntEditColor = function (id) {
    fetch(base_url + "/Inv_coloresunidad/getColor/" + id)
      .then((r) => r.json())
      .then((res) => {
        if (!res.status) return Swal.fire("Error", res.msg, "error");
        const d = res.data;
        document.querySelector("#idcolor").value = d.idcolor;
        document.querySelector("#color_nombre").value = d.nombre || "";
        document.querySelector("#color_descripcion").value = d.descripcion || "";
        document.querySelector("#color_estado").value = d.estado;
        if (btnText) btnText.textContent = "ACTUALIZAR";
        if (tabNuevoColor) tabNuevoColor.show();
      });
  };
});

function fntDelColor(id) {
  Swal.fire({
    title: "¿Eliminar color?",
    text: "Dejará de mostrarse en el portal en las unidades que lo tengan asignado.",
    icon: "warning",
    showCancelButton: true,
    confirmButtonText: "Sí, eliminar",
    cancelButtonText: "Cancelar",
  }).then((result) => {
    if (!result.isConfirmed) return;
    const fd = new FormData();
    fd.append("idcolor", id);
    fetch(base_url + "/Inv_coloresunidad/delColor", { method: "POST", body: fd })
      .then((r) => r.json())
      .then((res) => {
        Swal.fire(res.status ? "Correcto" : "Error", res.msg, res.status ? "success" : "error");
        if (res.status) tableColores.ajax.reload();
      });
  });
}
