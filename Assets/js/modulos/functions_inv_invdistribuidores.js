/* Retail e inventario de distribuidores (Inv_invdistribuidores) */
document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");
  const URL = base_url + "/Inv_invdistribuidores";
  const MESES = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
  const LS_MODO = "invdist_modo";

  const esc = (v) =>
    String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const num = (n) => Number(n || 0).toLocaleString("es-MX");
  const num1 = (n) => Number(n || 0).toLocaleString("es-MX", { maximumFractionDigits: 1 });
  const mesTxt = (ym) => {
    if (!ym) return "";
    const [a, m] = ym.split("-");
    return MESES[Number(m) - 1] + " " + a;
  };
  const vacio = '<span class="text-muted">—</span>';
  const txt = (v) => (v === null || v === undefined || v === "" ? vacio : esc(v));
  const cssVar = (n, def) => getComputedStyle(document.documentElement).getPropertyValue(n).trim() || def;

  /** Color de badge según palabras del estatus / tipo. */
  function badgeEstatus(v) {
    if (!v) return vacio;
    const u = String(v).toUpperCase();
    let c = "secondary";
    if (/CANCEL|NO VENDIDO/.test(u)) c = "danger";
    else if (/FACTURADO|VENDIDO|RETAIL/.test(u)) c = "success";
    else if (/POR LLEGAR|PENDIENTE|NO RECIBIDO|NO SE HA RECIBIDO|EN PLANTA|ARRIBO|APARTADO/.test(u)) c = "warning";
    else if (/DEMO/.test(u)) c = "info";
    else if (/DISTRIBUIDOR|DISPONIBLE|PISO/.test(u)) c = "primary";
    return '<span class="badge bg-' + c + '-subtle text-' + c + '">' + esc(v) + "</span>";
  }

  // ------------------------------------------------------------------
  //  BASE ACTIVA: retail | inventario
  // ------------------------------------------------------------------
  let modo = "retail";
  try {
    if (localStorage.getItem(LS_MODO) === "inventario") modo = "inventario";
  } catch (e) {}
  const esInv = () => modo === "inventario";

  /** Aplica textos (data-txt-*), visibilidad (.modo-*) y la tarjeta activa. */
  function pintarModo() {
    document.querySelectorAll("[data-txt-retail]").forEach((el) => {
      el.textContent = el.dataset["txt" + (esInv() ? "Inventario" : "Retail")];
    });
    document.querySelectorAll(".modo-retail").forEach((el) => el.classList.toggle("d-none", esInv()));
    document.querySelectorAll(".modo-inventario").forEach((el) => el.classList.toggle("d-none", !esInv()));
    document.querySelectorAll(".dist-modo").forEach((card) => {
      const act = card.dataset.modo === modo;
      card.classList.toggle("active", act);
      card.setAttribute("aria-checked", act ? "true" : "false");
    });
    const tipoCarga = document.querySelector("#tipoCarga");
    if (tipoCarga) tipoCarga.value = modo;
    document.querySelector("#distHero").classList.toggle("inv", esInv());
    document.querySelector("#heroIcono").className = esInv() ? "ri-archive-line" : "ri-shopping-cart-2-line";
    document.querySelector("#heroMeta").innerHTML = '<i class="ri-time-line align-bottom me-1"></i> Cargando última carga…';
  }

  // ------------------------------------------------------------------
  //  FILTROS
  // ------------------------------------------------------------------
  const F = {
    distribuidor: document.querySelector("#fDistribuidor"),
    modelo_gen: document.querySelector("#fModelo"),
    estatus: document.querySelector("#fEstatus"),
    filtro4: document.querySelector("#fFiltro4"), // tipo_pago (retail) | tipo (inventario)
    mes_desde: document.querySelector("#fMesDesde"),
    mes_hasta: document.querySelector("#fMesHasta"),
  };

  const filtros = () => {
    const o = { tipo: modo };
    Object.keys(F).forEach((k) => {
      const v = F[k] ? F[k].value : "";
      if (k === "filtro4") o[esInv() ? "f_tipo" : "f_tipo_pago"] = v;
      else o["f_" + k] = v;
    });
    return o;
  };
  const qsFiltros = (extra) => new URLSearchParams(Object.assign(filtros(), extra || {})).toString();

  function pintarBadgeFiltros() {
    const n = Object.values(F).filter((el) => el && el.value !== "").length;
    const b = document.querySelector("#badgeFiltros");
    b.textContent = n + (n === 1 ? " activo" : " activos");
    b.classList.toggle("d-none", n === 0);
  }

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
    sel.value = valores.includes(actual) ? actual : "";
  }

  const rAnio = document.querySelector("#rAnio");
  const agrupar = () => (document.querySelector('input[name="rAgrupar"]:checked') || {}).value || "distribuidor";

  function cargarFiltros() {
    return fetch(URL + "/getFiltros?tipo=" + modo)
      .then((r) => r.json())
      .then((d) => {
        llenar(F.distribuidor, d.distribuidores || []);
        llenar(F.modelo_gen, d.modelos || []);
        llenar(F.estatus, d.estatus || []);
        llenar(F.filtro4, d.filtro4 || []);
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
        pintarBadgeFiltros();
      })
      .catch((e) => console.error(e));
  }

  // ------------------------------------------------------------------
  //  KPIs + TENDENCIA + TOP (se piden juntos para calcular la variación)
  // ------------------------------------------------------------------
  function setKpi(i, titulo, valor, sub) {
    document.querySelector("#kpi" + i + "Titulo").textContent = titulo;
    document.querySelector("#kpi" + i + "Valor").innerHTML = valor;
    document.querySelector("#kpi" + i + "Sub").innerHTML = sub || "&nbsp;";
  }
  function setIcono(i, icono) {
    document.querySelector("#kpi" + i + "Icono").innerHTML = '<i class="' + icono + '"></i>';
  }

  /** Badge ▲/▼ comparando el último mes con el anterior. */
  function variacion(serie) {
    if (!serie || serie.length < 2) return "";
    const act = serie[serie.length - 1].n;
    const ant = serie[serie.length - 2].n;
    if (!ant) return "";
    const pct = ((act - ant) / ant) * 100;
    const sube = pct >= 0;
    const c = Math.abs(pct) < 0.05 ? "secondary" : sube ? "success" : "danger";
    return (
      '<span class="badge bg-' + c + '-subtle text-' + c + ' me-1"><i class="' +
      (sube ? "ri-arrow-up-line" : "ri-arrow-down-line") + ' align-middle"></i> ' +
      num1(Math.abs(pct)) + " %</span>vs " + esc(mesTxt(serie[serie.length - 2].mes))
    );
  }

  let chart = null;
  let ultimaPeticion = 0;

  function cargarPanel() {
    const id = ++ultimaPeticion;
    const tipo = modo;
    Promise.all([
      fetch(URL + "/getKpis?" + qsFiltros()).then((r) => r.json()),
      fetch(URL + "/getTendencia?" + qsFiltros()).then((r) => r.json()),
    ])
      .then(([k, t]) => {
        if (id !== ultimaPeticion || tipo !== modo) return; // llegó una respuesta vieja
        const serie = t.serie || [];
        const ult = k.ultimo_mes ? mesTxt(k.ultimo_mes) : "—";
        const prom = k.meses ? k.total / k.meses : 0;
        const rango = serie.length ? mesTxt(serie[0].mes) + " – " + mesTxt(serie[serie.length - 1].mes) : "";

        if (esInv()) {
          setIcono(1, "ri-archive-line");
          setIcono(2, "ri-store-2-line");
          setIcono(3, "ri-line-chart-line");
          setIcono(4, "ri-calendar-2-line");
          setKpi(1, "Inventario " + ult, num(k.en_ultimo_mes), variacion(serie));
          setKpi(2, "Distribuidores", num(k.distribuidores_ultimo_mes), "en " + esc(ult) + " · " + num(k.distribuidores) + " en el periodo");
          setKpi(3, "Promedio mensual", num1(prom), "unidades en piso por mes");
          setKpi(4, "Meses cargados", num(k.meses), esc(rango));
        } else {
          setIcono(1, "ri-shopping-cart-2-line");
          setIcono(2, "ri-store-2-line");
          setIcono(3, "ri-calendar-check-line");
          setIcono(4, "ri-line-chart-line");
          setKpi(1, "Unidades retail", num(k.total), esc(rango) || "&nbsp;");
          setKpi(2, "Distribuidores", num(k.distribuidores), num(k.distribuidores_ultimo_mes) + " vendieron en " + esc(ult));
          setKpi(3, "Retail " + ult, num(k.en_ultimo_mes), variacion(serie));
          setKpi(4, "Promedio mensual", num1(prom), "en " + num(k.meses) + " mes(es)");
        }

        document.querySelector("#tendenciaRango").textContent = rango;
        pintarGrafica(serie);
        pintarTop(t.top || [], t.mes_top, serie.length ? serie[serie.length - 1].n : 0);
      })
      .catch((e) => console.error(e));
  }

  function pintarGrafica(serie) {
    const el = document.querySelector("#chartTendencia");
    if (typeof ApexCharts === "undefined") {
      el.innerHTML = '<p class="text-muted text-center my-5">No se pudo cargar la librería de gráficas.</p>';
      return;
    }
    if (!serie.length) {
      if (chart) {
        chart.destroy();
        chart = null;
      }
      el.innerHTML = '<p class="text-muted text-center my-5">Sin datos para los filtros elegidos.</p>';
      return;
    }
    const color = esInv() ? "#0ab39c" : cssVar("--vz-primary", "#25a0e2");
    const opciones = {
      chart: { type: esInv() ? "area" : "bar", height: 290, toolbar: { show: false }, fontFamily: "inherit", animations: { speed: 300 } },
      series: [{ name: esInv() ? "Unidades en inventario" : "Unidades retail", data: serie.map((s) => s.n) }],
      xaxis: {
        categories: serie.map((s) => mesTxt(s.mes)),
        labels: { rotate: -45, rotateAlways: serie.length > 14, style: { fontSize: "11px" } },
        axisTicks: { show: false },
      },
      yaxis: { labels: { formatter: (v) => num(Math.round(v)) } },
      colors: [color],
      plotOptions: { bar: { borderRadius: 3, columnWidth: "60%" } },
      fill: esInv() ? { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0.02 } } : { opacity: 1 },
      stroke: { width: esInv() ? 2 : 0, curve: "smooth" },
      dataLabels: { enabled: false },
      grid: { borderColor: cssVar("--vz-border-color", "#e9ebec"), strokeDashArray: 3 },
      tooltip: {
        y: {
          formatter: (v, o) => {
            const s = serie[o.dataPointIndex];
            return num(v) + " unidades · " + num(s ? s.distribuidores : 0) + " distribuidor(es)";
          },
        },
      },
    };
    if (chart) chart.destroy();
    el.innerHTML = "";
    chart = new ApexCharts(el, opciones);
    chart.render();
  }

  function pintarTop(top, mes, totalMes) {
    const box = document.querySelector("#topDistribuidores");
    document.querySelector("#topMes").textContent = mes ? mesTxt(mes) : "";
    if (!top.length) {
      box.innerHTML = '<p class="text-muted text-center my-4 mb-0">Sin datos.</p>';
      return;
    }
    const max = top[0].n || 1;
    const color = esInv() ? "success" : "primary";
    box.innerHTML = top
      .map((r, i) => {
        const pct = totalMes ? (r.n / totalMes) * 100 : 0;
        return (
          '<div class="' + (i < top.length - 1 ? "mb-3" : "") + '">' +
          '<div class="d-flex justify-content-between align-items-baseline mb-1 gap-2">' +
          '<span class="text-truncate small fw-medium" title="' + esc(r.distribuidor) + '">' +
          '<span class="text-muted me-1">' + (i + 1) + ".</span>" + esc(r.distribuidor) + "</span>" +
          '<span class="small dist-nowrap"><strong>' + num(r.n) + '</strong> <span class="text-muted">· ' + num1(pct) + " %</span></span>" +
          "</div>" +
          '<div class="progress dist-top-barra"><div class="progress-bar bg-' + color + '" style="width:' +
          ((r.n / max) * 100).toFixed(1) + '%"></div></div></div>'
        );
      })
      .join("");
  }

  // ------------------------------------------------------------------
  //  LISTADOS (server-side): una tabla por base
  // ------------------------------------------------------------------
  const btnDetalle = (r, t, row, meta) =>
    '<button type="button" class="btn btn-sm btn-ghost-primary btn-icon btnDetalle" data-row="' + meta.row +
    '" title="Ver todos los datos"><i class="ri-eye-line"></i></button>';
  const colMes = {
    data: "mes",
    className: "dist-nowrap",
    render: (d, t) => (t === "display" ? '<span class="badge bg-light text-body">' + esc(mesTxt(d)) + "</span>" : esc(d)),
  };
  const colDist = { data: "distribuidor", render: (d) => '<span class="dist-trunc fw-medium" title="' + esc(d) + '">' + esc(d) + "</span>" };
  const colVin = { data: "vin", render: (d) => '<span class="dist-vin">' + esc(d) + "</span>" };
  const colTrunc = (campo) => ({
    data: campo,
    render: (d) => (d ? '<span class="dist-trunc" title="' + esc(d) + '">' + esc(d) + "</span>" : vacio),
  });

  const COLUMNAS = {
    retail: [
      colMes, colDist, colVin,
      colTrunc("modelo"),
      { data: "modelo_gen", render: txt },
      { data: "anio_modelo", render: txt },
      { data: "estatus", render: badgeEstatus },
      { data: "fecha_facturacion", className: "dist-nowrap", render: txt },
      colTrunc("razon_social"),
      { data: "tipo_pago", render: txt },
      colTrunc("vendedor"),
      { data: null, orderable: false, className: "text-end", render: btnDetalle },
    ],
    inventario: [
      colMes, colDist, colVin,
      colTrunc("modelo"),
      { data: "modelo_gen", render: txt },
      { data: "anio_modelo", render: txt },
      { data: "tipo", render: badgeEstatus },
      { data: "estatus", render: badgeEstatus },
      colTrunc("observaciones"),
      { data: "fecha_facturacion", className: "dist-nowrap", render: txt },
      colTrunc("razon_social"),
      { data: null, orderable: false, className: "text-end", render: btnDetalle },
    ],
  };
  const SELECTOR = { retail: "#tableRetail", inventario: "#tableInventario" };
  const tablas = {};

  /** Crea la tabla de la base la primera vez que se usa. */
  function tablaActual() {
    if (!tablas[modo]) {
      const t = modo;
      tablas[t] = $(SELECTOR[t]).DataTable({
        processing: true,
        serverSide: true,
        destroy: true,
        scrollX: true,
        autoWidth: false,
        searchDelay: 500,
        ajax: {
          url: URL + "/getListado",
          type: "POST",
          data: (d) => Object.assign(d, filtros(), { tipo: t }),
        },
        columns: COLUMNAS[t],
        order: [[0, "desc"]],
        pageLength: 25,
        lengthMenu: [10, 25, 50, 100, 250],
        language: {
          search: "",
          searchPlaceholder: "Buscar VIN, distribuidor, cliente…",
          lengthMenu: "Mostrar _MENU_",
          info: "_START_–_END_ de _TOTAL_",
          infoEmpty: "Sin registros",
          infoFiltered: "(de _MAX_)",
          zeroRecords: "No hay registros con esos filtros",
          processing: "Cargando…",
          paginate: { previous: "‹", next: "›" },
        },
      });
      tablas[t].__nueva = true;
    }
    return tablas[modo];
  }

  /** Secciones del modal de detalle. */
  const SECCIONES = {
    retail: [
      ["Unidad", [["mes", "Mes retail"], ["distribuidor", "Distribuidor"], ["modelo", "Modelo"], ["modelo_gen", "Modelo gen"],
        ["anio_modelo", "Año modelo"], ["motor", "Motor"], ["tipo", "Tipo"], ["estatus", "Estatus"], ["observaciones", "Observaciones"]]],
      ["Factura y cliente final", [["fecha_facturacion", "Fecha de facturación"], ["folio_fiscal", "Folio fiscal"],
        ["razon_social", "Nombre o razón social"], ["rfc", "R.F.C."], ["direccion", "Dirección"], ["codigo_postal", "Código postal"],
        ["telefono", "Teléfono"], ["email", "E-mail"]]],
      ["Venta", [["tipo_pago", "Tipo de pago"], ["institucion", "Institución"], ["enganche", "Enganche"], ["plazo", "Plazo"],
        ["vendedor", "Vendedor"]]],
    ],
    inventario: [
      ["Unidad", [["mes", "Mes inventario"], ["distribuidor", "Distribuidor"], ["modelo", "Modelo"], ["modelo_gen", "Modelo homo"],
        ["anio_modelo", "Año"], ["tipo", "Tipo"], ["estatus", "Estatus"], ["observaciones", "Observaciones"]]],
      ["Factura y cliente final", [["fecha_facturacion", "Fecha de facturación"], ["folio_fiscal", "Folio fiscal"],
        ["razon_social", "Nombre o razón social"], ["rfc", "R.F.C."], ["direccion", "Dirección"], ["codigo_postal", "Código postal"]]],
      ["Venta", [["tipo_pago", "Tipo de pago"], ["institucion", "Institución"], ["enganche", "Enganche"], ["plazo", "Plazo"],
        ["vendedor", "Vendedor"]]],
    ],
  };

  $("#tableRetail, #tableInventario").on("click", ".btnDetalle", function () {
    const r = tablaActual().row(Number(this.dataset.row)).data();
    if (!r) return;
    document.querySelector("#tituloDetalle").textContent = r.vin;
    const valor = (k) => {
      if (k === "mes") return esc(mesTxt(r.mes));
      if (k === "estatus" || k === "tipo") return badgeEstatus(r[k]);
      return txt(r[k]);
    };
    document.querySelector("#bodyDetalle").innerHTML =
      SECCIONES[modo]
        .map(
          ([titulo, campos]) =>
            '<h6 class="text-uppercase text-muted small fw-semibold mb-2">' + esc(titulo) + "</h6>" +
            '<div class="row g-2 mb-4">' +
            campos
              .map(
                ([k, l]) =>
                  '<div class="col-sm-6"><div class="small text-muted">' + esc(l) + '</div><div class="fw-medium text-break">' +
                  valor(k) + "</div></div>"
              )
              .join("") +
            "</div>"
        )
        .join("") +
      '<div class="small text-muted border-top pt-2">Carga: <strong>' + txt(r.folio_carga) + "</strong></div>";
    bootstrap.Modal.getOrCreateInstance(document.querySelector("#modalDetalle")).show();
  });

  document.querySelector("#btnExportar").addEventListener("click", () => {
    window.open(URL + "/exportar?" + qsFiltros({ search: tablaActual().search() || "" }), "_blank");
  });

  // ------------------------------------------------------------------
  //  RESUMEN POR MES (mapa de calor)
  //  Retail: última columna = total del año.
  //  Inventario: cada mes es una foto => última columna = promedio de los meses con datos.
  // ------------------------------------------------------------------
  function cargarResumen() {
    const tbody = document.querySelector("#tableResumen tbody");
    const tfoot = document.querySelector("#tableResumen tfoot");
    const etqModelo = esInv() ? "Modelo homo" : "Modelo gen";
    document.querySelector("#thGrupo").textContent = agrupar() === "modelo_gen" ? etqModelo : "Distribuidor";
    if (!rAnio.value) {
      tbody.innerHTML = "";
      tfoot.innerHTML = "";
      return;
    }
    const tipo = modo;
    tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted py-4">Cargando…</td></tr>';
    fetch(URL + "/getResumen?" + qsFiltros({ anio: rAnio.value, agrupar: agrupar() }))
      .then((r) => r.json())
      .then((rows) => {
        if (tipo !== modo) return; // cambió de base mientras cargaba
        if (!rows.length) {
          tbody.innerHTML = '<tr><td colspan="14" class="text-center text-muted py-4">Sin ' +
            (esInv() ? "inventario" : "retail") + " para ese año.</td></tr>";
          tfoot.innerHTML = "";
          return;
        }
        const tot = new Array(12).fill(0);
        let max = 0;
        rows.forEach((r) => {
          for (let m = 1; m <= 12; m++) {
            const v = Number(r["m" + m] || 0);
            tot[m - 1] += v;
            if (v > max) max = v;
          }
        });
        const mesesConDatos = tot.filter((v) => v > 0).length || 1;
        const ultima = (suma) => (esInv() ? num1(suma / mesesConDatos) : num(suma));
        const rgb = esInv() ? "10,179,156" : "var(--vz-primary-rgb, 37,160,226)";
        const celda = (v) => {
          if (!v) return '<td class="text-muted">·</td>';
          const a = (0.08 + (v / (max || 1)) * 0.5).toFixed(2);
          return '<td style="background-color:rgba(' + rgb + "," + a + ')">' + num(v) + "</td>";
        };

        tbody.innerHTML = rows
          .map((r) => {
            let html = '<tr><td class="text-start dist-nowrap"><span class="dist-trunc" title="' + esc(r.grupo) + '">' + esc(r.grupo) + "</span></td>";
            for (let m = 1; m <= 12; m++) html += celda(Number(r["m" + m] || 0));
            return html + '<td class="fw-semibold">' + ultima(Number(r.total || 0)) + "</td></tr>";
          })
          .join("");
        const sumaTot = tot.reduce((a, b) => a + b, 0);
        tfoot.innerHTML =
          '<tr><td class="text-start">Total</td>' + tot.map((v) => "<td>" + (v ? num(v) : "·") + "</td>").join("") +
          "<td>" + ultima(sumaTot) + "</td></tr>";
      })
      .catch((e) => {
        console.error(e);
        tbody.innerHTML = '<tr><td colspan="14" class="text-center text-danger py-4">Error al cargar el resumen.</td></tr>';
      });
  }
  rAnio.addEventListener("change", cargarResumen);
  document.querySelectorAll('input[name="rAgrupar"]').forEach((r) => r.addEventListener("change", cargarResumen));

  // ------------------------------------------------------------------
  //  CARGAS (historial)
  // ------------------------------------------------------------------
  const urlCargas = () => URL + "/getCargas?tipo=" + modo;
  const tableCargas = $("#tableCargas").DataTable({
    processing: true,
    destroy: true,
    scrollX: true,
    autoWidth: false,
    ajax: { url: urlCargas(), dataSrc: "" },
    columns: [
      { data: "folio", render: (d) => '<span class="fw-semibold">' + esc(d) + "</span>" },
      {
        data: "archivo",
        render: (d) => '<i class="ri-file-excel-2-line text-success align-middle me-1"></i><span class="dist-trunc" title="' + esc(d) + '">' + esc(d) + "</span>",
      },
      {
        data: null,
        className: "dist-nowrap",
        render: (r) => esc(r.mes_desde === r.mes_hasta ? mesTxt(r.mes_desde) : mesTxt(r.mes_desde) + " – " + mesTxt(r.mes_hasta)),
      },
      { data: "total_filas", className: "text-end", render: num },
      { data: "total_cargadas", className: "text-end", render: num },
      {
        data: "total_omitidas",
        className: "text-end",
        render: (d) => (Number(d) > 0 ? '<span class="badge bg-danger-subtle text-danger">' + num(d) + "</span>" : '<span class="text-muted">0</span>'),
      },
      { data: "filas_reemplazadas", className: "text-end", render: num },
      { data: "vigentes", className: "text-end", render: num },
      colTrunc("observaciones"),
      { data: "fecha_creacion", className: "dist-nowrap", render: esc },
      { data: "usuario", render: txt },
      {
        data: null,
        render: (r) => {
          if (String(r.estado) === "0") return '<span class="badge bg-danger-subtle text-danger">Eliminada</span>';
          if (Number(r.vigentes) > 0) return '<span class="badge bg-success-subtle text-success">Vigente</span>';
          return '<span class="badge bg-secondary-subtle text-secondary">Reemplazada</span>';
        },
      },
      {
        data: null,
        orderable: false,
        className: "text-end",
        render: (r) =>
          puedeEliminarCarga && String(r.estado) === "2" && Number(r.vigentes) > 0
            ? '<button type="button" class="btn btn-sm btn-ghost-danger btn-icon btnEliminarCarga" data-id="' + esc(r.idcarga) +
              '" data-folio="' + esc(r.folio) + '" data-vigentes="' + esc(r.vigentes) +
              '" title="Eliminar carga"><i class="ri-delete-bin-line"></i></button>'
            : "",
      },
    ],
    order: [[0, "desc"]],
    pageLength: 25,
    language: { search: "", searchPlaceholder: "Buscar…", zeroRecords: "Aún no hay cargas", info: "_START_–_END_ de _TOTAL_", infoEmpty: "", lengthMenu: "Mostrar _MENU_" },
  });

  /** Encabezado: datos de la carga más reciente que sigue vigente. */
  tableCargas.on("xhr", function () {
    const data = tableCargas.ajax.json() || [];
    const meta = document.querySelector("#heroMeta");
    const ult = (Array.isArray(data) ? data : []).find((c) => String(c.estado) === "2" && Number(c.vigentes) > 0);
    if (!ult) {
      meta.innerHTML = '<i class="ri-inbox-line align-bottom me-1"></i> Aún no hay cargas de ' + (esInv() ? "inventario" : "retail");
      return;
    }
    const rango = ult.mes_desde === ult.mes_hasta ? mesTxt(ult.mes_desde) : mesTxt(ult.mes_desde) + " – " + mesTxt(ult.mes_hasta);
    meta.innerHTML =
      '<i class="ri-time-line align-bottom me-1"></i> Última carga <strong>' + esc(ult.folio) + "</strong> · " +
      esc(ult.fecha_creacion) + (ult.usuario ? " · " + esc(ult.usuario) : "") + " · " + esc(rango);
  });

  $("#tableCargas").on("click", ".btnEliminarCarga", function () {
    const id = this.dataset.id;
    const que = esInv() ? "inventario" : "retail";
    Swal.fire({
      title: "¿Eliminar la carga " + this.dataset.folio + "?",
      text: "Se borrarán " + num(this.dataset.vigentes) + " renglón(es) de " + que +
        " que siguen vigentes de esta carga. Los meses no se restauran de cargas anteriores.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonText: "Sí, eliminar",
      cancelButtonText: "No",
      confirmButtonColor: "#d33",
    }).then((res) => {
      if (!res.isConfirmed) return;
      const fd = new FormData();
      fd.append("idcarga", id);
      fd.append("tipo", modo);
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
    pintarBadgeFiltros();
    const t = tablaActual();
    if (t.__nueva) t.__nueva = false; // la primera carga ya la hizo DataTables
    else t.ajax.reload();
    cargarPanel();
    cargarResumen();
  }

  function recargarTodo(conFiltros) {
    const p = conFiltros ? cargarFiltros() : Promise.resolve();
    p.then(() => {
      aplicarFiltros();
      tableCargas.ajax.url(urlCargas()).load(null, false);
    });
  }

  function ajustarColumnas() {
    $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
  }

  function cambiarModo(nuevo) {
    if (nuevo === modo) return;
    modo = nuevo;
    try {
      localStorage.setItem(LS_MODO, modo);
    } catch (e) {}
    pintarModo();
    Object.values(F).forEach((el) => el && (el.value = ""));
    const box = document.querySelector("#resultadoCarga");
    if (box) box.style.display = "none";
    recargarTodo(true);
    setTimeout(ajustarColumnas, 50);
  }

  document.querySelectorAll(".dist-modo").forEach((card) => {
    card.addEventListener("click", () => cambiarModo(card.dataset.modo));
    card.addEventListener("keydown", (e) => {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        cambiarModo(card.dataset.modo);
      }
    });
  });

  Object.values(F).forEach((el) => el && el.addEventListener("change", aplicarFiltros));
  document.querySelector("#btnLimpiarFiltros").addEventListener("click", () => {
    Object.values(F).forEach((el) => el && (el.value = ""));
    tablaActual().search("");
    aplicarFiltros();
  });

  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((btn) => {
    btn.addEventListener("shown.bs.tab", ajustarColumnas);
  });

  pintarModo();
  cargarFiltros().then(() => {
    aplicarFiltros();
  });

  // ------------------------------------------------------------------
  //  SUBIR ARCHIVO
  // ------------------------------------------------------------------
  const abrirPlantilla = () => window.open(URL + "/descargarPlantilla?tipo=" + modo, "_blank");
  const btnPlantilla = document.querySelector("#btnDescargarPlantilla");
  if (btnPlantilla) btnPlantilla.addEventListener("click", abrirPlantilla);
  const heroPlantilla = document.querySelector("#heroPlantilla");
  if (heroPlantilla) heroPlantilla.addEventListener("click", abrirPlantilla);
  const heroSubir = document.querySelector("#heroSubir");
  const tabSubir = document.querySelector('button[data-bs-target="#tabCarga"]');
  if (heroSubir && tabSubir) {
    heroSubir.addEventListener("click", () => {
      bootstrap.Tab.getOrCreateInstance(tabSubir).show();
      tabSubir.closest(".card").scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }

  let tipoUltimaCarga = modo;
  const btnLog = document.querySelector("#btnLogCarga");
  if (btnLog) {
    btnLog.addEventListener("click", () => window.open(URL + "/exportarLog?tipo=" + tipoUltimaCarga, "_blank"));
  }

  // Zona para arrastrar / elegir el archivo
  const drop = document.querySelector("#dropCarga");
  const inputArchivo = document.querySelector("#archivoCarga");
  function pintarArchivo() {
    if (!drop) return;
    const f = inputArchivo.files && inputArchivo.files[0];
    drop.classList.toggle("tiene-archivo", !!f);
    document.querySelector("#dropIcono").className =
      (f ? "ri-file-excel-2-line text-success" : "ri-upload-cloud-2-line text-muted") + " display-6 d-block mb-1";
    document.querySelector("#dropTexto").textContent = f ? f.name : "Arrastra aquí la plantilla llena o haz clic para elegirla";
    document.querySelector("#dropDetalle").textContent = f
      ? (f.size / 1024 / 1024).toFixed(2) + " MB · haz clic para cambiarlo"
      : "Excel .xlsx · máximo 30 MB";
  }
  if (drop) {
    inputArchivo.addEventListener("change", pintarArchivo);
    ["dragenter", "dragover"].forEach((ev) => drop.addEventListener(ev, () => drop.classList.add("is-over")));
    ["dragleave", "drop"].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove("is-over")));
  }

  const form = document.querySelector("#formCarga");
  if (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!inputArchivo.files || inputArchivo.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }
      const box = document.querySelector("#resultadoCarga");
      const alertBox = document.querySelector("#alertResultadoCarga");
      const btnSubmit = form.querySelector('button[type="submit"]');
      document.querySelector("#tipoCarga").value = modo;
      tipoUltimaCarga = modo;
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
          alertBox.className = "alert mb-2 " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.innerHTML =
            '<i class="' + (omitidos > 0 ? "ri-error-warning-line" : "ri-checkbox-circle-line") + ' align-bottom me-1"></i>' + esc(obj.msg);
          btnLog.style.display = omitidos + avisos > 0 ? "inline-block" : "none";
          inputArchivo.value = "";
          pintarArchivo();
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
