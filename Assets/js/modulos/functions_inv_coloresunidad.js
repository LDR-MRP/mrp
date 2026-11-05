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
      { data: "codigo" },
      { data: "muestra", orderable: false },
      { data: "unidades", className: "text-center" },
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

  // Selector de color <-> campo #RRGGBB
  const picker = document.querySelector("#color_picker");
  const hexInput = document.querySelector("#color_hex");
  if (picker && hexInput) {
    picker.addEventListener("input", () => (hexInput.value = picker.value.toUpperCase()));
    hexInput.addEventListener("input", () => {
      let v = hexInput.value.trim().toUpperCase();
      if (v && v[0] !== "#") v = "#" + v;
      hexInput.value = v;
      if (/^#[0-9A-F]{6}$/.test(v)) picker.value = v;
    });
  }

  function limpiarFormColor() {
    formColor.reset();
    document.querySelector("#idcolor").value = 0;
    if (picker) picker.value = "#FFFFFF";
    if (btnText) btnText.textContent = "REGISTRAR";
  }

  if (formColor) {
    formColor.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!document.querySelector("#color_nombre").value.trim()) {
        Swal.fire("Aviso", "Captura el nombre del color", "warning");
        return;
      }
      const hex = hexInput ? hexInput.value.trim() : "";
      if (hex && !/^#[0-9A-Fa-f]{6}$/.test(hex)) {
        Swal.fire("Aviso", "La muestra de color debe tener el formato #RRGGBB", "warning");
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
        document.querySelector("#color_codigo").value = d.codigo || "";
        if (hexInput) hexInput.value = d.codigo_hex || "";
        if (picker) picker.value = /^#[0-9A-Fa-f]{6}$/.test(d.codigo_hex || "") ? d.codigo_hex : "#FFFFFF";
        document.querySelector("#color_estado").value = d.estado;
        if (btnText) btnText.textContent = "ACTUALIZAR";
        if (tabNuevoColor) tabNuevoColor.show();
      });
  };
});

function fntDelColor(id) {
  const fila = (tableColores?.rows().data().toArray() || []).find((r) => Number(r.idcolor) === Number(id));
  const unidades = fila ? Number(fila.unidades) || 0 : 0;
  Swal.fire({
    title: "¿Eliminar color?",
    text:
      unidades > 0
        ? `Está asignado a ${unidades} unidad(es). Dejará de mostrarse en el portal junto con sus fotos de ese color.`
        : "Dejará de mostrarse en el portal en las unidades que lo tengan asignado.",
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
