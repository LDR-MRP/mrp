let tableBitacora;

document.addEventListener("DOMContentLoaded", function () {
  const tablaSelect = document.querySelector("#filtro-tabla-select");
  const usuarioSelect = document.querySelector("#filtro-usuario-select");
  const fechaDesdeInput = document.querySelector("#filtro-fecha-desde-input");
  const fechaHastaInput = document.querySelector("#filtro-fecha-hasta-input");
  const formFiltros = document.querySelector("#formFiltrosBitacora");

  if (!document.querySelector("#tableBitacora")) {
    return;
  }

  // --------------------------------------------------------------
  // SELECT DE USUARIOS (solo los que tienen movimientos registrados)
  // --------------------------------------------------------------
  function cargarSelectUsuarios() {
    let request = new XMLHttpRequest();
    request.open("GET", base_url + "/Ing_bitacora/getSelectUsuariosBitacora", true);
    request.send();
    request.onreadystatechange = function () {
      if (request.readyState === 4 && request.status === 200 && usuarioSelect) {
        usuarioSelect.innerHTML = request.responseText;
      }
    };
  }
  cargarSelectUsuarios();

  // --------------------------------------------------------------
  // DATATABLE
  // --------------------------------------------------------------
  tableBitacora = $("#tableBitacora").DataTable({
    processing: true,
    serverSide: false,
    ajax: {
      url: base_url + "/Ing_bitacora/getBitacora",
      dataSrc: "",
      data: function (params) {
        params.tabla = tablaSelect ? tablaSelect.value : "";
        params.usuario = usuarioSelect ? usuarioSelect.value : "";
        params.fecha_desde = fechaDesdeInput ? fechaDesdeInput.value : "";
        params.fecha_hasta = fechaHastaInput ? fechaHastaInput.value : "";
      },
    },
    columns: [
      { data: "created_at" },
      {
        data: null,
        render: function (row) {
          return row.usuario || "—";
        },
      },
      { data: "tabla_label" },
      { data: "accion_label" },
      {
        data: null,
        render: function (row) {
          return "#" + row.resourceid;
        },
      },
      { data: "comentario" },
    ],
    order: [[0, "desc"]],
    responsive: true,
    destroy: true,
    pageLength: 25,
    language: {
      emptyTable: "No hay movimientos registrados con esos filtros.",
    },
  });

  if (formFiltros) {
    formFiltros.addEventListener("submit", function (e) {
      e.preventDefault();
      tableBitacora.ajax.reload();
    });
  }
});
