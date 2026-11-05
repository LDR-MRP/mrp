/* Retail de distribuidores (Inv_invdistribuidores) */
document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");
  const URL = base_url + "/Inv_invdistribuidores";
  const MESES = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];

  const esc = (v) =>
    String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const num = (n) => Number(n || 0).toLocaleString("es-MX");
  const mesTxt = (ym) => {
    if (!ym) return "";
    const [a, m] = ym.split("-");
    return MESES[Number(m) - 1] + " " + a;
  };

  // ------------------------------------------------------------------
  //  FILTROS
  // ------------------------------------------------------------------
  const F = {
    distribuidor: document.querySelector("#fDistribuidor"),
    modelo_gen: document.querySelector("#fModelo"),
    estatus: document.querySelector("#fEstatus"),
    tipo_pago: document.querySelector("#fTipoPago"),
    mes_desde: document.querySelector("#fMesDesde"),
    mes_hasta: document.querySelector("#fMesHasta"),
  };

  const filtros = () => {
    const o = {};
    Object.keys(F).forEach((k) => (o["f_" + k] = F[k] ? F[k].value : ""));
    return o;
  };
  const qsFiltros = (extra) => new URLSearchParams(Object.assign(filtros(), extra || {})).toString();

  function llenar(sel, valores, etiqueta) {
    if (!sel) return;
    const actual = sel.value;
    sel.querySelectorAll("option:not(:first-child)").forEach((o) => o.remove());
    valores.forEach((v) => {
      const op = document.createElement("option");
      op.value = v;
      op.textContent = etiqueta ? etiqueta(v) : v;
      sel.appendChild(op);
    });
    if (valores.includes(actual)) sel.value = actual;
  }

  const rAnio = document.querySelector("#rAnio");
  const rAgrupar = document.querySelector("#rAgrupar");

  function cargarFiltros() {
    return fetch(URL + "/getFiltros")
      .then((r) => r.json())
      .then((d) => {
        llenar(F.distribuidor, d.distribuidores || []);
        llenar(F.modelo_gen, d.modelos || []);
        llenar(F.estatus, d.estatus || []);
        llenar(F.tipo_pago, d.tipos_pago || []);
        const meses = d.meses || [];
        llenar(F.mes_desde, [...meses].reverse(), mesTxt);
        llenar(F.mes_hasta, meses, mesTxt);

        const anioActual = rAnio.value;
        rAnio.innerHTML = "";
        const anios = d.anios && d.anios.length ? d.anios : [new Date().getFullYear()];
        anios.forEach((a) => {
          const op = document.createElement("option");
          op.value = a;
          op.textContent = a;
          rAnio.appendChild(op);
        });
        if (anios.map(String).includes(anioActual)) rAnio.value = anioActual;
      })
      .catch((e) => console.error(e));
  }

  // ------------------------------------------------------------------
  //  KPIs
  // ------------------------------------------------------------------
  function cargarKpis() {
    fetch(URL + "/getKpis?" + qsFiltros())
      .then((r) => r.json())
      .then((k) => {
        document.querySelector("#kpiTotal").textContent = num(k.total);
        document.querySelector("#kpiDistribuidores").textContent = num(k.distribuidores);
        document.querySelector("#kpiMeses").textContent = num(k.meses);
        document.querySelector("#kpiUltimoMes").textContent = k.ultimo_mes ? "(" + mesTxt(k.ultimo_mes) + ")" : "";
        document.querySelector("#kpiEnUltimoMes").textContent = num(k.en_ultimo_mes);
      })
      .catch((e) => console.error(e));
  }

  // ------------------------------------------------------------------
  //  LISTADO (server-side)
  // ------------------------------------------------------------------
  const tableRetail = $("#tableRetail").DataTable({
    processing: true,
    serverSide: true,
    destroy: true,
    scrollX: true,
    autoWidth: false,
    searchDelay: 500,
    ajax: {
      url: URL + "/getRetail",
      type: "POST",
      data: (d) => Object.assign(d, filtros()),
    },
    columns: [
      { data: "mes_retail", render: (d, t) => (t === "display" ? esc(mesTxt(d)) : esc(d)) },
      { data: "distribuidor", render: esc },
      { data: "vin", render: (d) => "<strong>" + esc(d) + "</strong>" },
      { data: "modelo", render: esc },
      { data: "modelo_gen", render: esc },
      { data: "anio_modelo", render: esc },
      { data: "estatus", render: esc },
      { data: "fecha_facturacion", render: esc },
      { data: "razon_social", render: esc },
      { data: "tipo_pago", render: esc },
      { data: "vendedor", render: esc },
      {
        data: null,
        orderable: false,
        render: (r, t, row, meta) =>
          '<button type="button" class="btn btn-sm btn-soft-primary btnDetalle" data-row="' + meta.row +
          '" title="Ver todos los datos"><i class="ri-eye-line"></i></button>',
      },
    ],
    order: [[0, "desc"]],
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100, 250],
  });

  const ETIQUETAS = [
    ["mes_retail", "Mes retail"], ["distribuidor", "Distribuidor"], ["vin", "VIN"], ["modelo", "Modelo"],
    ["modelo_gen", "Modelo gen"], ["anio_modelo", "Año modelo"], ["tipo", "Tipo"], ["estatus", "Estatus"],
    ["observaciones", "Observaciones"], ["fecha_facturacion", "Fecha de facturación"], ["folio_fiscal", "Folio fiscal"],
    ["razon_social", "Nombre o razón social"], ["rfc", "R.F.C."], ["direccion", "Dirección"],
    ["codigo_postal", "Código postal"], ["telefono", "Teléfono"], ["email", "E-mail"], ["tipo_pago", "Tipo de pago"],
    ["institucion", "Institución"], ["enganche", "Enganche"], ["plazo", "Plazo"], ["vendedor", "Nombre del vendedor"],
    ["motor", "Motor"], ["folio_carga", "Carga"],
  ];

  $("#tableRetail").on("click", ".btnDetalle", function () {
    const r = tableRetail.row(Number(this.dataset.row)).data();
    if (!r) return;
    document.querySelector("#tituloDetalle").textContent = r.vin;
    document.querySelector("#bodyDetalle").innerHTML = ETIQUETAS.map(
      ([k, l]) =>
        '<tr><th class="text-muted fw-medium" style="width:35%">' + esc(l) + "</th><td>" +
        esc(k === "mes_retail" ? mesTxt(r[k]) : r[k]) + "</td></tr>"
    ).join("");
    bootstrap.Modal.getOrCreateInstance(document.querySelector("#modalDetalleRetail")).show();
  });

  document.querySelector("#btnExportarRetail").addEventListener("click", () => {
    window.open(URL + "/exportarRetail?" + qsFiltros({ search: tableRetail.search() || "" }), "_blank");
  });

  // ------------------------------------------------------------------
  //  RESUMEN POR MES
  // ------------------------------------------------------------------
  function cargarResumen() {
    const tbody = document.querySelector("#tableResumen tbody");
    const tfoot = document.querySelector("#tableResumen tfoot");
    document.querySelector("#thGrupo").textContent = rAgrupar.value === "modelo_gen" ? "Modelo gen" : "Distribuidor";
    if (!rAnio.value) {
      tbody.innerHTML = "";
      tfoot.innerHTML = "";
      return;
    }
    tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted">Cargando…</td></tr>';
    fetch(URL + "/getResumen?" + qsFiltros({ anio: rAnio.value, agrupar: rAgrupar.value }))
      .then((r) => r.json())
      .then((rows) => {
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted">Sin retail para ese año.</td></tr>';
          tfoot.innerHTML = "";
          return;
        }
        const tot = new Array(13).fill(0);
        tbody.innerHTML = rows
          .map((r) => {
            let html = '<tr><td class="text-start">' + esc(r.grupo) + "</td>";
            for (let m = 1; m <= 12; m++) {
              const v = Number(r["m" + m] || 0);
              tot[m - 1] += v;
              html += "<td>" + (v ? num(v) : '<span class="text-muted">·</span>') + "</td>";
            }
            tot[12] += Number(r.total || 0);
            return html + '<td class="fw-semibold">' + num(r.total) + "</td></tr>";
          })
          .join("");
        tfoot.innerHTML = '<tr><td class="text-start">Total</td>' + tot.map((v) => "<td>" + num(v) + "</td>").join("") + "</tr>";
      })
      .catch((e) => {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="14" class="text-center text-danger">Error al cargar el resumen.</td></tr>';
      });
  }
  rAnio.addEventListener("change", cargarResumen);
  rAgrupar.addEventListener("change", cargarResumen);

  // ------------------------------------------------------------------
  //  CARGAS (historial)
  // ------------------------------------------------------------------
  const tableCargas = $("#tableCargas").DataTable({
    processing: true,
    destroy: true,
    scrollX: true,
    autoWidth: false,
    ajax: { url: URL + "/getCargas", dataSrc: "" },
    columns: [
      { data: "folio", render: (d) => "<strong>" + esc(d) + "</strong>" },
      { data: "archivo", render: esc },
      {
        data: null,
        render: (r) => esc(r.mes_desde === r.mes_hasta ? mesTxt(r.mes_desde) : mesTxt(r.mes_desde) + " – " + mesTxt(r.mes_hasta)),
      },
      { data: "total_filas", render: num },
      { data: "total_cargadas", render: num },
      { data: "total_omitidas", render: (d) => (Number(d) > 0 ? '<span class="badge bg-danger">' + num(d) + "</span>" : num(d)) },
      { data: "filas_reemplazadas", render: num },
      { data: "vigentes", render: num },
      { data: "observaciones", render: esc },
      { data: "fecha_creacion", render: esc },
      { data: "usuario", render: esc },
      {
        data: null,
        render: (r) => {
          if (String(r.estado) === "0") return '<span class="badge bg-danger">Eliminada</span>';
          if (Number(r.vigentes) > 0) return '<span class="badge bg-success">Vigente</span>';
          return '<span class="badge bg-secondary">Reemplazada</span>';
        },
      },
      {
        data: null,
        orderable: false,
        render: (r) =>
          puedeEliminarCarga && String(r.estado) === "2" && Number(r.vigentes) > 0
            ? '<button type="button" class="btn btn-sm btn-soft-danger btnEliminarCarga" data-id="' + esc(r.idcarga) +
              '" data-folio="' + esc(r.folio) + '" data-vigentes="' + esc(r.vigentes) +
              '" title="Eliminar carga"><i class="ri-delete-bin-line"></i></button>'
            : "",
      },
    ],
    order: [[0, "desc"]],
    pageLength: 25,
  });

  $("#tableCargas").on("click", ".btnEliminarCarga", function () {
    const id = this.dataset.id;
    Swal.fire({
      title: "¿Eliminar la carga " + this.dataset.folio + "?",
      text: "Se borrarán " + num(this.dataset.vigentes) + " renglón(es) de retail que siguen vigentes de esta carga. Los meses no se restauran de cargas anteriores.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "No",
      confirmButtonColor: "#d33",
    }).then((res) => {
      if (!res.isConfirmed) return;
      const fd = new FormData();
      fd.append("idcarga", id);
      fetch(URL + "/eliminarCarga", { method: "POST", body: fd })
        .then((r) => r.json())
        .then((obj) => {
          Swal.fire(obj.status ? "Listo" : "Atención", obj.msg, obj.status ? "success" : "warning");
          if (obj.status) recargarTodo(true);
        })
        .catch(() => Swal.fire("Error", "Ocurrió un error en el servidor.", "error"));
    });
  });

  // ------------------------------------------------------------------
  //  REFRESCOS
  // ------------------------------------------------------------------
  function aplicarFiltros() {
    tableRetail.ajax.reload();
    cargarKpis();
    cargarResumen();
  }

  function recargarTodo(conFiltros) {
    const p = conFiltros ? cargarFiltros() : Promise.resolve();
    p.then(() => {
      aplicarFiltros();
      tableCargas.ajax.reload(null, false);
    });
  }

  Object.values(F).forEach((el) => el && el.addEventListener("change", aplicarFiltros));
  document.querySelector("#btnLimpiarFiltros").addEventListener("click", () => {
    Object.values(F).forEach((el) => el && (el.value = ""));
    tableRetail.search("");
    aplicarFiltros();
  });

  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((btn) => {
    btn.addEventListener("shown.bs.tab", () => $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust());
  });

  cargarFiltros().then(() => {
    cargarKpis();
    cargarResumen();
  });

  // ------------------------------------------------------------------
  //  SUBIR ARCHIVO
  // ------------------------------------------------------------------
  const btnPlantilla = document.querySelector("#btnDescargarPlantilla");
  if (btnPlantilla) {
    btnPlantilla.addEventListener("click", () => window.open(URL + "/descargarPlantilla", "_blank"));
  }

  const btnLog = document.querySelector("#btnLogCarga");
  if (btnLog) {
    btnLog.addEventListener("click", () => window.open(URL + "/exportarLog", "_blank"));
  }

  const form = document.querySelector("#formCargaRetail");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const input = form.querySelector('input[type="file"]');
      if (!input.files || input.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
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
          const omitidos = Number(obj.omitidos || 0);
          const avisos = Number(obj.avisos || 0);
          box.style.display = "block";
          alertBox.className = "alert " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.textContent = obj.msg;
          btnLog.style.display = omitidos + avisos > 0 ? "inline-block" : "none";
          input.value = "";
          recargarTodo(true);
          Swal.fire({
            title: "Proceso finalizado",
            text: obj.msg,
            icon: omitidos > 0 || !obj.cargados ? "warning" : "success",
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
