document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");
  const URL = base_url + "/Inv_invdistribuidores";

  const esc = (v) =>
    String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));

  // En "display" pinta el badge; para buscar/ordenar regresa el texto plano.
  const badgeEstatus = (d, type) =>
    type !== "display"
      ? d === "FACTURADO" ? "Facturado" : d === "DISPONIBLE" ? "Disponible" : ""
      : d === "FACTURADO"
      ? '<span class="badge bg-primary">Facturado</span>'
      : d === "DISPONIBLE"
      ? '<span class="badge bg-success">Disponible</span>'
      : "";

  const filtro = document.querySelector("#filtroDistribuidor");
  const qs = () => (filtro && filtro.value ? "?clienteid=" + encodeURIComponent(filtro.value) : "");

  // ------------------------------------------------------------------
  //  CATÁLOGO DE DISTRIBUIDORES
  // ------------------------------------------------------------------
  fetch(URL + "/getDistribuidores")
    .then((r) => r.json())
    .then((lista) => {
      ["#filtroDistribuidor", "#clienteCarga"].forEach((sel) => {
        const el = document.querySelector(sel);
        if (!el) return;
        lista.forEach((d) => {
          const op = document.createElement("option");
          op.value = d.idcliente;
          op.textContent = d.nombre;
          el.appendChild(op);
        });
      });
    })
    .catch((e) => console.error(e));

  // ------------------------------------------------------------------
  //  TABLAS
  // ------------------------------------------------------------------
  const tableInventario = $("#tableInventario").DataTable({
    processing: true,
    destroy: true,
    scrollX: true,
    autoWidth: false,
    ajax: { url: URL + "/getInventario" + qs(), dataSrc: "" },
    columns: [
      { data: "distribuidor", render: esc },
      { data: "vin", render: (d) => "<strong>" + esc(d) + "</strong>" },
      { data: "estatus_distribuidor", render: badgeEstatus },
      { data: "cve_articulo", render: esc },
      { data: "descripcion", render: esc },
      { data: "numero_motor", render: esc },
      { data: "color", render: esc },
      { data: "factura", render: esc },
      { data: "folio", render: esc },
      { data: "fecha_corte", render: esc },
    ],
    order: [[0, "asc"], [3, "asc"]],
    pageLength: 25,
  });

  const filtroEstatus = document.querySelector("#filtroEstatus");
  if (filtroEstatus) {
    filtroEstatus.addEventListener("change", function () {
      tableInventario.column(2).search(this.value ? "^" + this.value + "$" : "", true, false).draw();
    });
  }

  if (filtro) {
    filtro.addEventListener("change", function () {
      tableInventario.ajax.url(URL + "/getInventario" + qs()).load();
    });
  }

  const tableConciliacion = $("#tableConciliacion").DataTable({
    processing: true,
    destroy: true,
    scrollX: true,
    autoWidth: false,
    ajax: {
      url: URL + "/getConciliacion",
      dataSrc: function (rows) {
        actualizarKpis(rows);
        return rows;
      },
    },
    columns: [
      { data: "distribuidor", render: esc },
      { data: "folio", render: (d) => (d ? esc(d) : '<span class="text-muted">Sin corte</span>') },
      { data: "fecha_corte", render: esc },
      { data: "facturados" },
      { data: "reportados" },
      { data: "disponibles", render: (d) => (d > 0 ? '<span class="badge bg-success">' + d + "</span>" : d) },
      { data: "facturados_dist", render: (d) => (d > 0 ? '<span class="badge bg-primary">' + d + "</span>" : d) },
      {
        data: "no_reportados",
        render: (d) => (d > 0 ? '<span class="badge bg-danger">' + d + "</span>" : d),
      },
      {
        data: "no_conciliados",
        render: (d) => (d > 0 ? '<span class="badge bg-warning text-dark">' + d + "</span>" : d),
      },
      {
        data: "clienteid",
        orderable: false,
        render: (d) =>
          '<button type="button" class="btn btn-sm btn-soft-success btnExcelDist" data-id="' + esc(d) +
          '" title="Descargar conciliación de este distribuidor"><i class="ri-file-excel-2-line"></i></button>',
      },
    ],
    order: [[7, "desc"]],
    pageLength: 25,
  });

  function actualizarKpis(rows) {
    let conCorte = 0, disp = 0, factDist = 0, noRep = 0;
    rows.forEach((r) => {
      if (r.folio) conCorte++;
      disp += Number(r.disponibles || 0);
      factDist += Number(r.facturados_dist || 0);
      noRep += Number(r.no_reportados || 0);
    });
    document.querySelector("#kpiDistribuidores").textContent = conCorte;
    document.querySelector("#kpiDisponibles").textContent = disp;
    document.querySelector("#kpiFacturadosDist").textContent = factDist;
    document.querySelector("#kpiNoReportados").textContent = noRep;
  }

  $("#tableConciliacion").on("click", ".btnExcelDist", function () {
    window.open(URL + "/exportarConciliacion?clienteid=" + encodeURIComponent(this.dataset.id), "_blank");
  });

  const btnConc = document.querySelector("#btnExportarConciliacion");
  if (btnConc) {
    btnConc.addEventListener("click", () => window.open(URL + "/exportarConciliacion", "_blank"));
  }

  const tableCortes = $("#tableCortes").DataTable({
    processing: true,
    destroy: true,
    scrollX: true,
    autoWidth: false,
    ajax: { url: URL + "/getCortes", dataSrc: "" },
    columns: [
      { data: "folio", render: (d) => "<strong>" + esc(d) + "</strong>" },
      { data: "distribuidor", render: esc },
      { data: "fecha_corte", render: esc },
      { data: "total_vines" },
      { data: "observaciones", render: esc },
      { data: "archivo", render: esc },
      { data: "fecha_creacion", render: esc },
      { data: "usuario", render: esc },
      {
        data: null,
        render: (r) => {
          if (String(r.estado) === "0") return '<span class="badge bg-danger">Cancelado</span>';
          if (String(r.vigente) === "1") return '<span class="badge bg-success">Vigente</span>';
          return '<span class="badge bg-secondary">Historial</span>';
        },
      },
      {
        data: null,
        orderable: false,
        render: (r) => {
          let html =
            '<button type="button" class="btn btn-sm btn-soft-primary btnVerCorte me-1" data-id="' + esc(r.idcorte) +
            '" data-folio="' + esc(r.folio) + '" title="Ver VINs"><i class="ri-eye-line"></i></button>';
          if (puedeCancelarCorte && String(r.estado) === "2") {
            html +=
              '<button type="button" class="btn btn-sm btn-soft-danger btnCancelarCorte" data-id="' + esc(r.idcorte) +
              '" data-folio="' + esc(r.folio) + '" title="Cancelar corte"><i class="ri-close-circle-line"></i></button>';
          }
          return html;
        },
      },
    ],
    order: [[0, "desc"]],
    pageLength: 25,
  });

  let tableDetalle = null;
  $("#tableCortes").on("click", ".btnVerCorte", function () {
    document.querySelector("#tituloCorte").textContent = this.dataset.folio;
    const url = URL + "/getDetalleCorte/" + encodeURIComponent(this.dataset.id);
    if (tableDetalle) {
      tableDetalle.ajax.url(url).load();
    } else {
      tableDetalle = $("#tableDetalleCorte").DataTable({
        processing: true,
        autoWidth: false,
        ajax: { url: url, dataSrc: "" },
        columns: [
          { data: "vin", render: esc },
          { data: "cve_articulo", render: esc },
          { data: "descripcion", render: esc },
          { data: "numero_motor", render: esc },
          { data: "estatus_distribuidor", render: badgeEstatus },
          { data: "ubicacion", render: esc },
          { data: "observaciones", render: esc },
        ],
        pageLength: 25,
      });
    }
    bootstrap.Modal.getOrCreateInstance(document.querySelector("#modalDetalleCorte")).show();
  });

  $("#tableCortes").on("click", ".btnCancelarCorte", function () {
    const id = this.dataset.id;
    const folio = this.dataset.folio;
    Swal.fire({
      title: "¿Cancelar el corte " + folio + "?",
      text: "Si es el corte vigente, el inventario del distribuidor regresa a su corte anterior.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, cancelar",
      cancelButtonText: "No",
      confirmButtonColor: "#d33",
    }).then((res) => {
      if (!res.isConfirmed) return;
      const fd = new FormData();
      fd.append("idcorte", id);
      fetch(URL + "/cancelarCorte", { method: "POST", body: fd })
        .then((r) => r.json())
        .then((obj) => {
          Swal.fire(obj.status ? "Listo" : "Atención", obj.msg, obj.status ? "success" : "warning");
          if (obj.status) recargarTodo();
        })
        .catch(() => Swal.fire("Error", "Ocurrió un error en el servidor.", "error"));
    });
  });

  function recargarTodo() {
    tableInventario.ajax.reload(null, false);
    tableConciliacion.ajax.reload(null, false);
    tableCortes.ajax.reload(null, false);
  }

  // Ajusta anchos de columnas al cambiar de pestaña (tablas ocultas al iniciar)
  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((btn) => {
    btn.addEventListener("shown.bs.tab", () => $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust());
  });

  // ------------------------------------------------------------------
  //  CARGA MASIVA
  // ------------------------------------------------------------------
  const btnPlantilla = document.querySelector("#btnDescargarPlantilla");
  if (btnPlantilla) {
    btnPlantilla.addEventListener("click", () => {
      const cli = document.querySelector("#clienteCarga");
      const q = cli && cli.value ? "?clienteid=" + encodeURIComponent(cli.value) : "";
      window.open(URL + "/descargarPlantilla" + q, "_blank");
    });
  }

  const btnLog = document.querySelector("#btnLogCarga");
  if (btnLog) {
    btnLog.addEventListener("click", () => window.open(URL + "/exportarLog", "_blank"));
  }

  const form = document.querySelector("#formCargaDistribuidores");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const input = form.querySelector('input[type="file"]');
      if (!input.files || input.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }
      if (!form.querySelector("#fechaCorte").value) {
        Swal.fire("Atención", "Selecciona la fecha de corte.", "warning");
        return;
      }
      const box = document.querySelector("#resultadoCarga");
      const alertBox = document.querySelector("#alertResultadoCarga");
      const btnSubmit = form.querySelector('button[type="submit"]');
      if (divLoading) divLoading.style.display = "flex";
      btnSubmit.disabled = true;

      fetch(URL + "/procesarCarga", { method: "POST", body: new FormData(form) })
        .then((r) => r.json())
        .then((obj) => {
          if (divLoading) divLoading.style.display = "none";
          btnSubmit.disabled = false;
          if (!obj.status) {
            Swal.fire("Atención", obj.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }
          const omitidos = obj.omitidos || 0;
          box.style.display = "block";
          alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.innerHTML =
            esc(obj.msg) +
            (obj.cortes && obj.cortes.length ? "<br><small>Cortes: " + obj.cortes.map(esc).join(", ") + "</small>" : "");
          btnLog.style.display = omitidos > 0 ? "inline-block" : "none";
          input.value = "";
          recargarTodo();
          Swal.fire({
            title: "Proceso finalizado",
            text: obj.msg,
            icon: omitidos > 0 ? "warning" : "success",
            confirmButtonColor: "#28a745",
          });
        })
        .catch((err) => {
          if (divLoading) divLoading.style.display = "none";
          btnSubmit.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }
});
