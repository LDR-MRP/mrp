/* Order delivery plan · unidades en tránsito (Inv_orderdelivery) */
document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");
  const URL = base_url + "/Inv_orderdelivery";
  const MESES = ["Ene", "Feb", "Mar", "Abr", "May", "Jun", "Jul", "Ago", "Sep", "Oct", "Nov", "Dic"];
  const MESES_LARGO = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

  const esc = (v) =>
    String(v ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const num = (n) => Number(n || 0).toLocaleString("es-MX");
  const mesTxt = (ym) => {
    if (!ym) return "";
    const [a, m] = ym.split("-");
    return MESES[Number(m) - 1] + " " + a;
  };
  const vacio = '<span class="text-muted">—</span>';
  const txt = (v) => (v === null || v === undefined || v === "" ? vacio : esc(v));
  /** 'YYYY-MM-DD' => dd/mm/aaaa; texto (TBD, /) en gris. */
  const fecha = (v) => {
    if (v === null || v === undefined || v === "") return vacio;
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(v);
    return m ? '<span class="odp-nowrap">' + m[3] + "/" + m[2] + "/" + m[1] + "</span>" : '<span class="text-muted">' + esc(v) + "</span>";
  };

  function badgeStatus(v) {
    if (!v) return vacio;
    const u = String(v).toUpperCase();
    let c = "secondary";
    if (/FINISH|ARRIV|DELIVER/.test(u)) c = "success";
    else if (/READY|SHIP/.test(u) && !/WAIT/.test(u)) c = "info";
    else if (/PRODUCTION/.test(u)) c = "warning";
    else if (/WAIT|PAYMENT|PENDING/.test(u)) c = "danger";
    return '<span class="badge bg-' + c + '-subtle text-' + c + '">' + esc(v) + "</span>";
  }

  // ------------------------------------------------------------------
  //  FILTROS (siempre un mes: cada mes es una foto completa del plan)
  // ------------------------------------------------------------------
  const F = {
    mes: document.querySelector("#fMes"),
    inventarioid: document.querySelector("#fProducto"),
    status: document.querySelector("#fStatus"),
  };
  const filtros = () => {
    const o = {};
    Object.keys(F).forEach((k) => (o["f_" + k] = F[k].value));
    return o;
  };
  const qsFiltros = (extra) => new URLSearchParams(Object.assign(filtros(), extra || {})).toString();

  function pintarBadgeFiltros() {
    const n = ["inventarioid", "status"].filter((k) => F[k].value !== "").length;
    const b = document.querySelector("#badgeFiltros");
    b.textContent = n + (n === 1 ? " activo" : " activos");
    b.classList.toggle("d-none", n === 0);
  }

  function llenar(sel, items, conVacio) {
    const actual = sel.value;
    sel.querySelectorAll("option:not(:first-child)").forEach((o) => o.remove());
    items.forEach(([v, t]) => {
      const op = document.createElement("option");
      op.value = v;
      op.textContent = t;
      sel.appendChild(op);
    });
    if (conVacio) {
      const op = document.createElement("option");
      op.value = "__vacio__";
      op.textContent = "(sin " + conVacio + ")";
      sel.appendChild(op);
    }
    sel.value = [...sel.options].some((o) => o.value === actual) ? actual : "";
  }

  let hayCargas = false;
  /** Pide meses + catálogos del mes elegido ('' = todos los meses). */
  function cargarFiltros() {
    return fetch(URL + "/getFiltros?" + new URLSearchParams({ f_mes: F.mes.value }))
      .then((r) => r.json())
      .then((d) => {
        hayCargas = !!(d.meses && d.meses.length);
        F.mes.innerHTML = '<option value="">Todos los meses</option>';
        (d.meses || []).forEach((m) => {
          const op = document.createElement("option");
          op.value = m;
          op.textContent = mesTxt(m);
          F.mes.appendChild(op);
        });
        F.mes.value = d.mes || "";
        llenar(F.inventarioid, (d.productos || []).map((p) => [p.id, (p.nombre || "") + " · " + p.sku]));
        llenar(F.status, (d.status || []).map((v) => [v, v]), "status");
        pintarBadgeFiltros();
      })
      .catch((e) => console.error(e));
  }

  // ------------------------------------------------------------------
  //  KPIs
  // ------------------------------------------------------------------
  function setKpi(i, valor, sub) {
    document.querySelector("#kpi" + i + "Valor").innerHTML = valor;
    document.querySelector("#kpi" + i + "Sub").innerHTML = sub || "&nbsp;";
  }
  function cargarKpis() {
    if (!hayCargas) {
      [1, 2, 3, 4].forEach((i) => setKpi(i, "0", "Aún no hay cargas"));
      return;
    }
    fetch(URL + "/getKpis?" + qsFiltros())
      .then((r) => r.json())
      .then((k) => {
        setKpi(1, num(k.unidades), num(k.renglones) + " renglón(es) · " + (F.mes.value ? esc(mesTxt(F.mes.value)) : "todos los meses"));
        setKpi(2, num(k.contratos), "Contract No. distintos");
        setKpi(3, num(k.productos), "SKU distintos del inventario");
        const pct = k.unidades ? Math.round((k.con_eta / k.unidades) * 100) : 0;
        setKpi(
          4,
          num(k.con_eta),
          k.proxima_eta
            ? "Próxima ETA " + fecha(k.proxima_eta) + " · " + pct + "% del total"
            : k.con_eta ? pct + "% del total" : "Sin ETA confirmada aún"
        );
      })
      .catch((e) => console.error(e));
  }

  // ------------------------------------------------------------------
  //  LISTADO (serverSide)
  // ------------------------------------------------------------------
  const tablePlan = $("#tablePlan").DataTable({
    processing: true,
    serverSide: true,
    deferLoading: 0,
    scrollX: true,
    autoWidth: false,
    searchDelay: 500,
    ajax: {
      url: URL + "/getListado",
      type: "POST",
      data: (d) => Object.assign(d, filtros()),
    },
    columns: [
      {
        data: "nombre_comercial",
        render: (d, t, r) =>
          '<span class="odp-trunc fw-medium" title="' + esc(d) + '">' + esc(d) + '</span><div class="small text-muted odp-mono">' + esc(r.sku) + "</div>",
      },
      { data: "modelo", render: txt },
      {
        data: "contrato",
        render: (d) => (d ? '<span class="fw-medium odp-mono">' + esc(d) + "</span>" : '<span class="text-muted">Sin contrato</span>'),
      },
      { data: "qty", className: "text-end fw-semibold", render: num },
      { data: "eta_pod", render: fecha },
      { data: "status", render: badgeStatus },
    ],
    order: [],
    pageLength: 50,
    lengthMenu: [25, 50, 100, 250],
    language: {
      search: "",
      searchPlaceholder: "Buscar contrato, modelo, SKU, status…",
      lengthMenu: "Mostrar _MENU_",
      info: "_START_–_END_ de _TOTAL_",
      infoEmpty: "Sin registros",
      infoFiltered: "(de _MAX_)",
      zeroRecords: "No hay renglones con esos filtros",
      emptyTable: "Aún no hay cargas",
      processing: "Cargando…",
      paginate: { previous: "‹", next: "›" },
    },
  });

  document.querySelector("#btnExportar").addEventListener("click", () => {
    if (!hayCargas) {
      Swal.fire("Atención", "Aún no hay cargas para exportar.", "info");
      return;
    }
    window.open(URL + "/exportar?" + qsFiltros({ search: tablePlan.search() || "" }), "_blank");
  });

  // ------------------------------------------------------------------
  //  RESUMEN POR PRODUCTO × STATUS
  // ------------------------------------------------------------------
  function cargarResumen() {
    const tabla = document.querySelector("#tableResumen");
    const thead = tabla.querySelector("thead");
    const tbody = tabla.querySelector("tbody");
    const tfoot = tabla.querySelector("tfoot");
    if (!hayCargas) {
      thead.innerHTML = "";
      tfoot.innerHTML = "";
      tbody.innerHTML = '<tr><td class="text-center text-muted py-4">Aún no hay cargas</td></tr>';
      return;
    }
    fetch(URL + "/getResumen?" + qsFiltros())
      .then((r) => r.json())
      .then((d) => {
        const st = d.status || [];
        const filas = d.filas || [];
        thead.innerHTML =
          '<tr><th class="text-start">Producto</th><th>Contratos</th>' + st.map((s) => "<th>" + esc(s) + "</th>").join("") +
          "<th>Total</th><th>Primera ETA</th></tr>";
        if (!filas.length) {
          tbody.innerHTML = '<tr><td colspan="' + (st.length + 4) + '" class="text-center text-muted py-4">Sin datos con esos filtros</td></tr>';
          tfoot.innerHTML = "";
          return;
        }
        const max = Math.max(...filas.flatMap((f) => st.map((s) => f.por_status[s] || 0)), 1);
        const celda = (n) => {
          if (!n) return '<td class="text-muted">·</td>';
          const a = (0.1 + 0.55 * (n / max)).toFixed(2);
          return '<td style="background-color: rgba(var(--vz-primary-rgb), ' + a + ')">' + num(n) + "</td>";
        };
        tbody.innerHTML = filas
          .map(
            (f) =>
              '<tr><td class="text-start"><div class="fw-medium">' + esc(f.nombre) + '</div><div class="small text-muted odp-mono">' + esc(f.sku) +
              "</div></td><td>" + num(f.contratos) + "</td>" + st.map((s) => celda(f.por_status[s] || 0)).join("") +
              '<td class="fw-semibold">' + num(f.total) + "</td><td>" + fecha(f.primera_eta) + "</td></tr>"
          )
          .join("");
        const tot = (s) => filas.reduce((a, f) => a + (f.por_status[s] || 0), 0);
        tfoot.innerHTML =
          '<tr><td class="text-start">Total</td><td></td>' + st.map((s) => "<td>" + num(tot(s)) + "</td>").join("") +
          "<td>" + num(filas.reduce((a, f) => a + f.total, 0)) + "</td><td></td></tr>";
      })
      .catch((e) => console.error(e));
  }

  // ------------------------------------------------------------------
  //  HISTORIAL DE CARGAS
  // ------------------------------------------------------------------
  const tableCargas = $("#tableCargas").DataTable({
    processing: true,
    scrollX: true,
    autoWidth: false,
    ajax: { url: URL + "/getCargas", dataSrc: "" },
    columns: [
      { data: "folio", render: (d) => '<span class="fw-semibold">' + esc(d) + "</span>" },
      { data: "mes", className: "odp-nowrap", render: (d) => esc(mesTxt(d)) },
      {
        data: "archivo",
        render: (d) => '<i class="ri-file-excel-2-line text-success align-middle me-1"></i><span class="odp-trunc" title="' + esc(d) + '">' + esc(d) + "</span>",
      },
      { data: "total_filas", className: "text-end", render: num },
      { data: "total_cargadas", className: "text-end", render: num },
      {
        data: "total_omitidas",
        className: "text-end",
        render: (d) => (Number(d) > 0 ? '<span class="badge bg-danger-subtle text-danger">' + num(d) + "</span>" : '<span class="text-muted">0</span>'),
      },
      { data: "total_unidades", className: "text-end", render: num },
      { data: "observaciones", render: (d) => (d ? '<span class="odp-trunc" title="' + esc(d) + '">' + esc(d) + "</span>" : vacio) },
      { data: "fecha_creacion", className: "odp-nowrap", render: esc },
      { data: "usuario", render: txt },
      {
        data: null,
        render: (r) => {
          if (String(r.estado) === "0") return '<span class="badge bg-danger-subtle text-danger">Eliminada</span>';
          return '<span class="badge bg-success-subtle text-success">Vigente</span>';
        },
      },
      {
        data: null,
        orderable: false,
        className: "text-end",
        render: (r) =>
          puedeEliminarCarga && String(r.estado) === "2" && Number(r.vigentes) > 0
            ? '<button type="button" class="btn btn-sm btn-ghost-danger btn-icon btnEliminarCarga" data-id="' + esc(r.idcarga) +
              '" data-folio="' + esc(r.folio) + '" data-vigentes="' + esc(r.vigentes) + '" title="Eliminar carga"><i class="ri-delete-bin-line"></i></button>'
            : "",
      },
    ],
    order: [[0, "desc"]],
    pageLength: 25,
    language: { search: "", searchPlaceholder: "Buscar…", zeroRecords: "Aún no hay cargas", emptyTable: "Aún no hay cargas", info: "_START_–_END_ de _TOTAL_", infoEmpty: "", lengthMenu: "Mostrar _MENU_" },
  });

  tableCargas.on("xhr", function () {
    const data = tableCargas.ajax.json() || [];
    const meta = document.querySelector("#heroMeta");
    const ult = (Array.isArray(data) ? data : []).find((c) => String(c.estado) === "2" && Number(c.vigentes) > 0);
    if (!ult) {
      meta.innerHTML = '<i class="ri-inbox-line align-bottom me-1"></i> Aún no hay cargas';
      return;
    }
    meta.innerHTML =
      '<i class="ri-time-line align-bottom me-1"></i> Última carga <strong>' + esc(ult.folio) + "</strong> · " + esc(mesTxt(ult.mes)) + " · " +
      esc(ult.fecha_creacion) + (ult.usuario ? " · " + esc(ult.usuario) : "") + " · " + num(ult.total_unidades) + " unidades";
  });

  $("#tableCargas").on("click", ".btnEliminarCarga", function () {
    const id = this.dataset.id;
    Swal.fire({
      title: "¿Eliminar la carga " + this.dataset.folio + "?",
      text: "Se borrarán los " + num(this.dataset.vigentes) + " renglón(es) de esta carga. Las demás cargas no se tocan.",
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
          if (obj.status) recargarTodo();
        })
        .catch(() => Swal.fire("Error", "Ocurrió un error en el servidor.", "error"));
    });
  });

  // ------------------------------------------------------------------
  //  REFRESCOS
  // ------------------------------------------------------------------
  function aplicarFiltros() {
    pintarBadgeFiltros();
    tablePlan.ajax.reload();
    cargarKpis();
    cargarResumen();
  }
  function recargarTodo() {
    cargarFiltros().then(() => {
      aplicarFiltros();
      tableCargas.ajax.reload(null, false);
    });
  }
  const ajustarColumnas = () => $.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();

  // Al cambiar de mes se vuelven a pedir los catálogos de ese mes
  F.mes.addEventListener("change", () => cargarFiltros().then(aplicarFiltros));
  ["inventarioid", "status"].forEach((k) => F[k].addEventListener("change", aplicarFiltros));
  document.querySelector("#btnLimpiarFiltros").addEventListener("click", () => {
    ["inventarioid", "status"].forEach((k) => (F[k].value = ""));
    tablePlan.search("");
    aplicarFiltros();
  });
  document.querySelectorAll('button[data-bs-toggle="tab"]').forEach((btn) => btn.addEventListener("shown.bs.tab", ajustarColumnas));

  cargarFiltros().then(aplicarFiltros);

  // ------------------------------------------------------------------
  //  SUBIR ARCHIVO
  // ------------------------------------------------------------------
  const hoy = new Date();
  const mesActual = document.querySelector("#mesActualTxt");
  if (mesActual) mesActual.textContent = MESES_LARGO[hoy.getMonth()] + " " + hoy.getFullYear();

  const abrirPlantilla = () => window.open(URL + "/descargarPlantilla", "_blank");
  ["#btnDescargarPlantilla", "#heroPlantilla"].forEach((s) => {
    const b = document.querySelector(s);
    if (b) b.addEventListener("click", abrirPlantilla);
  });
  const heroSubir = document.querySelector("#heroSubir");
  const tabSubir = document.querySelector('button[data-bs-target="#tabCarga"]');
  if (heroSubir && tabSubir) {
    heroSubir.addEventListener("click", () => {
      bootstrap.Tab.getOrCreateInstance(tabSubir).show();
      tabSubir.closest(".card").scrollIntoView({ behavior: "smooth", block: "start" });
    });
  }
  const btnLog = document.querySelector("#btnLogCarga");
  if (btnLog) btnLog.addEventListener("click", () => window.open(URL + "/exportarLog", "_blank"));

  const drop = document.querySelector("#dropCarga");
  const inputArchivo = document.querySelector("#archivoCarga");
  function pintarArchivo() {
    if (!drop) return;
    const f = inputArchivo.files && inputArchivo.files[0];
    drop.classList.toggle("tiene-archivo", !!f);
    document.querySelector("#dropIcono").className = (f ? "ri-file-excel-2-line text-success" : "ri-upload-cloud-2-line text-muted") + " display-6 d-block mb-1";
    document.querySelector("#dropTexto").textContent = f ? f.name : "Arrastra aquí la plantilla llena o haz clic para elegirla";
    document.querySelector("#dropDetalle").textContent = f ? (f.size / 1024 / 1024).toFixed(2) + " MB · haz clic para cambiarlo" : "Excel .xlsx · máximo 20 MB";
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
      const btnSubmit = form.querySelector('button[type="submit"]');
      const enviar = () => {
        const box = document.querySelector("#resultadoCarga");
        const alertBox = document.querySelector("#alertResultadoCarga");
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
            alertBox.innerHTML = '<i class="' + (omitidos > 0 ? "ri-error-warning-line" : "ri-checkbox-circle-line") + ' align-bottom me-1"></i>' + esc(obj.msg);
            btnLog.style.display = omitidos + avisos > 0 ? "inline-block" : "none";
            inputArchivo.value = "";
            pintarArchivo();
            recargarTodo();
            Swal.fire({ title: "Proceso finalizado", text: obj.msg, icon: omitidos > 0 || !obj.cargados ? "warning" : "success", confirmButtonColor: "#28a745" });
          })
          .catch((err) => {
            if (divLoading) divLoading.style.display = "none";
            btnSubmit.disabled = false;
            console.error(err);
            Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
          });
      };
      enviar();
    });
  }
});
