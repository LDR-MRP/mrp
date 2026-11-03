document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");
  const COLORES = { 0: "danger", 1: "info", 2: "warning", 3: "success" };
  const SIN_DIST = "__SIN__";

  const inpDesde = document.getElementById("cobDesde");
  const inpHasta = document.getElementById("cobHasta");
  const selDist = document.getElementById("cobDistribuidor");
  const selModelo = document.getElementById("cobModelo");
  const selEstatus = document.getElementById("cobEstatus");
  let tabla = null;
  let filaModal = null;

  // ------------------------------------------------------------------
  //  UTILIDADES
  // ------------------------------------------------------------------
  const fmtNum = (n) => Number(n || 0).toLocaleString("es-MX");
  const pad = (n) => String(n).padStart(2, "0");
  const ymd = (d) => d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
  const esc = (s) => $("<span>").text(s == null ? "" : String(s)).html();
  const fechaCorta = (s) => {
    if (!s) return "";
    const [f, h] = String(s).split(" ");
    const p = f.split("-");
    return (p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : f) + (h ? " " + h : "");
  };
  const badgeEstatus = (e) =>
    '<span class="badge bg-' + COLORES[e] + "-subtle text-" + COLORES[e] + ' fs-12">' + esc(COB_ESTATUS[e]) + "</span>";
  const cargando = (si) => {
    if (divLoading) divLoading.style.display = si ? "flex" : "none";
  };

  // ------------------------------------------------------------------
  //  INDICADORES (clic = filtrar por estatus)
  // ------------------------------------------------------------------
  function cargarResumen() {
    fetch(base_url + "/Inv_cobranzaunidades/getResumen")
      .then((r) => r.json())
      .then((obj) => {
        if (!obj.status) return;
        Object.keys(obj.data || {}).forEach((k) => {
          const el = document.getElementById("kpiEstatus" + k);
          if (el) el.textContent = fmtNum(obj.data[k]);
        });
      })
      .catch((e) => console.error(e));
  }
  cargarResumen();

  function marcarKpi() {
    document.querySelectorAll(".cob-kpi").forEach((c) => {
      c.classList.toggle("cob-activo", selEstatus.value !== "" && c.dataset.estatus === selEstatus.value);
    });
  }

  document.querySelectorAll(".cob-kpi").forEach((card) => {
    card.addEventListener("click", function () {
      selEstatus.value = selEstatus.value === this.dataset.estatus ? "" : this.dataset.estatus;
      marcarKpi();
      if (tabla) tabla.draw();
    });
  });

  // ------------------------------------------------------------------
  //  FILTROS
  // ------------------------------------------------------------------
  function llenarSelect(sel, valores, incluirVacio) {
    const actual = sel.value;
    const conteo = {};
    valores.forEach((v) => (conteo[v || SIN_DIST] = (conteo[v || SIN_DIST] || 0) + 1));
    const unicos = Object.keys(conteo).filter((v) => v !== SIN_DIST).sort((a, b) => a.localeCompare(b, "es"));
    sel.innerHTML = "";
    sel.add(new Option("Todos", ""));
    unicos.forEach((v) => sel.add(new Option(v + " (" + conteo[v] + ")", v)));
    if (incluirVacio && conteo[SIN_DIST]) sel.add(new Option("Sin distribuidor (" + conteo[SIN_DIST] + ")", SIN_DIST));
    sel.value = Array.from(sel.options).some((o) => o.value === actual) ? actual : "";
  }

  $.fn.dataTable.ext.search.push(function (settings, data, idx, row) {
    if (settings.nTable.id !== "tableCobranza") return true;
    const d = selDist.value;
    if (d) {
      const dist = (row.distribuidor || "").trim();
      if (d === SIN_DIST ? dist !== "" : dist !== d) return false;
    }
    if (selModelo.value && row.descripcion !== selModelo.value) return false;
    if (selEstatus.value !== "" && String(row.estatus_pago) !== selEstatus.value) return false;
    return true;
  });

  function textoFiltros() {
    const p = [];
    const d = inpDesde.value;
    const h = inpHasta.value;
    if (d || h) p.push((d ? fechaCorta(d) : "inicio") + " – " + (h ? fechaCorta(h) : "hoy"));
    if (selDist.value) p.push(selDist.value === SIN_DIST ? "Sin distribuidor" : selDist.value);
    if (selModelo.value) p.push(selModelo.value);
    if (selEstatus.value !== "") p.push(COB_ESTATUS[selEstatus.value]);
    return p.length ? p.join(" · ") : "Sin filtros";
  }

  function marcarRapido(rango) {
    document.querySelectorAll("#cobRapidos [data-rango]").forEach((b) => {
      const activo = b.dataset.rango === rango;
      b.classList.toggle("btn-primary", activo);
      b.classList.toggle("btn-outline-primary", !activo);
    });
  }

  function recargar() {
    if (inpDesde.value && inpHasta.value && inpDesde.value > inpHasta.value) {
      Swal.fire("Atención", "La fecha inicial no puede ser mayor a la final.", "warning");
      return;
    }
    tabla.ajax.reload();
  }

  document.querySelectorAll("#cobRapidos [data-rango]").forEach((btn) => {
    btn.addEventListener("click", function () {
      const hoy = new Date();
      let d = "";
      let h = "";
      if (this.dataset.rango === "mes") {
        d = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
        h = ymd(hoy);
      } else if (this.dataset.rango === "mesant") {
        d = ymd(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1));
        h = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 0));
      } else if (this.dataset.rango === "30") {
        const ini = new Date(hoy);
        ini.setDate(ini.getDate() - 29);
        d = ymd(ini);
        h = ymd(hoy);
      }
      inpDesde.value = d;
      inpHasta.value = h;
      marcarRapido(this.dataset.rango);
      recargar();
    });
  });
  [inpDesde, inpHasta].forEach((i) => i.addEventListener("change", () => marcarRapido(null)));
  marcarRapido("todo");

  document.getElementById("btnCobConsultar").addEventListener("click", recargar);
  document.getElementById("btnCobLimpiar").addEventListener("click", function () {
    inpDesde.value = "";
    inpHasta.value = "";
    selDist.value = "";
    selModelo.value = "";
    selEstatus.value = "";
    marcarRapido("todo");
    marcarKpi();
    tabla.search("");
    recargar();
  });
  [selDist, selModelo, selEstatus].forEach((s) =>
    s.addEventListener("change", () => {
      marcarKpi();
      tabla.draw();
    })
  );

  // ------------------------------------------------------------------
  //  TABLA
  // ------------------------------------------------------------------
  const txt = $.fn.dataTable.render.text();
  tabla = $("#tableCobranza").DataTable({
    processing: true,
    pageLength: 25,
    lengthMenu: [10, 25, 50, 100],
    order: [[0, "desc"]],
    ajax: {
      url: base_url + "/Inv_cobranzaunidades/getUnidades",
      type: "GET",
      data: () => ({ desde: inpDesde.value, hasta: inpHasta.value }),
      dataSrc: function (json) {
        if (!json.status) {
          Swal.fire("Atención", json.msg || "No fue posible consultar las unidades.", "warning");
          return [];
        }
        const rows = json.data || [];
        llenarSelect(selDist, rows.map((r) => (r.distribuidor || "").trim()), true);
        llenarSelect(selModelo, rows.map((r) => r.descripcion || ""), false);
        return rows;
      },
      error: function (xhr) {
        console.error(xhr.responseText);
        Swal.fire("Error", "Ocurrió un error al consultar las unidades.", "error");
      },
    },
    columns: [
      { data: "fecha_venta", render: (d, t) => (t === "display" || t === "filter" ? fechaCorta(d) : d) },
      { data: "vin", className: "cob-vin fw-semibold", render: txt },
      { data: "descripcion", render: txt },
      {
        data: "distribuidor",
        render: (d, t) => (t !== "display" ? d : d ? esc(d) : '<span class="text-muted fst-italic">Sin distribuidor</span>'),
      },
      { data: "factura", render: txt },
      {
        data: "estatus_pago",
        render: (d, t) => (t === "display" ? badgeEstatus(d) : t === "filter" ? COB_ESTATUS[d] : d),
      },
      {
        data: "fecha_estatus",
        render: function (d, t, row) {
          if (t !== "display") return d || "";
          if (!d) return '<span class="text-muted">—</span>';
          return '<div class="cob-upd">' + fechaCorta(d) + '<br><span class="text-muted">' + esc(row.usuario_estatus) + "</span></div>";
        },
      },
      { data: "sku", render: txt },
      { data: "folio_venta", render: txt },
      {
        data: null,
        orderable: false,
        searchable: false,
        className: "text-center",
        render: () =>
          COB_PUEDE_ACTUALIZAR
            ? '<button type="button" class="btn btn-sm btn-soft-primary cob-editar"><i class="ri-edit-2-line align-bottom me-1"></i>Cambiar</button>'
            : '<button type="button" class="btn btn-sm btn-soft-secondary cob-editar"><i class="ri-history-line align-bottom me-1"></i>Historial</button>',
      },
    ],
    language: {
      url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
      emptyTable: "No hay unidades facturadas con esos filtros.",
    },
  });

  tabla.on("draw", function () {
    document.getElementById("cobTotUnidades").textContent = fmtNum(tabla.rows({ search: "applied" }).count());
    document.getElementById("cobFiltrosTxt").textContent = textoFiltros();
  });

  document.querySelectorAll('#cobTabs a[data-bs-toggle="tab"]').forEach((a) =>
    a.addEventListener("shown.bs.tab", (e) => {
      if (e.target.getAttribute("href") === "#tabUnidades") tabla.columns.adjust().responsive.recalc();
    })
  );

  // ------------------------------------------------------------------
  //  MODAL: CAMBIAR ESTATUS + HISTORIAL
  // ------------------------------------------------------------------
  const modalEl = document.getElementById("modalCobEstatus");
  const modal = new bootstrap.Modal(modalEl);

  $("#tableCobranza tbody").on("click", ".cob-editar", function () {
    let tr = $(this).closest("tr");
    if (tr.hasClass("child")) tr = tr.prev(); // fila expandida en responsive
    filaModal = tabla.row(tr);
    abrirModal(filaModal.data());
  });

  function abrirModal(u) {
    document.getElementById("cobMVin").textContent = u.vin;
    document.getElementById("cobMModelo").textContent = u.descripcion + " · " + u.sku;
    document.getElementById("cobMDist").textContent = u.distribuidor || "Sin distribuidor";
    document.getElementById("cobMFactura").textContent = u.factura || "—";
    document.getElementById("cobMFecha").textContent = fechaCorta(u.fecha_venta);
    if (COB_PUEDE_ACTUALIZAR) {
      document.getElementById("cobMId").value = u.id_numeros_serie;
      document.getElementById("cobMMov").value = u.idmovinventario;
      const opt = document.getElementById("cobOpt" + u.estatus_pago);
      if (opt) opt.checked = true;
    }
    const ul = document.getElementById("cobMHistorial");
    ul.innerHTML = '<li class="text-muted">Cargando…</li>';
    modal.show();

    fetch(base_url + "/Inv_cobranzaunidades/getHistorial?id=" + encodeURIComponent(u.id_numeros_serie))
      .then((r) => r.json())
      .then((obj) => {
        const h = obj.status ? obj.data || [] : [];
        if (!h.length) {
          ul.innerHTML = '<li class="text-muted">Sin cambios registrados. La unidad está en Pendiente desde su venta.</li>';
          return;
        }
        ul.innerHTML = h
          .map(
            (x) =>
              "<li><div><strong>" + esc(x.valor_anterior) + "</strong> → <strong>" + esc(x.valor_nuevo) + "</strong>" +
              ' <span class="badge bg-light text-muted border ms-1">' + (x.origen === "MASIVA" ? "Excel" : "Individual") + "</span></div>" +
              '<div class="text-muted">' + fechaCorta(x.fecha) + " · " + esc(x.usuario || "—") + " · " + esc(x.detalle) + "</div></li>"
          )
          .join("");
      })
      .catch(() => (ul.innerHTML = '<li class="text-danger">No fue posible cargar el historial.</li>'));
  }

  const formEstatus = document.getElementById("formCobEstatus");
  if (formEstatus) {
    formEstatus.addEventListener("submit", function (e) {
      e.preventDefault();
      if (!formEstatus.querySelector('input[name="estatus_pago"]:checked')) {
        Swal.fire("Atención", "Selecciona un estatus.", "warning");
        return;
      }
      const btn = document.getElementById("btnCobGuardar");
      btn.disabled = true;
      fetch(base_url + "/Inv_cobranzaunidades/setEstatus", { method: "POST", body: new FormData(formEstatus) })
        .then((r) => r.json())
        .then((obj) => {
          btn.disabled = false;
          if (!obj.status) {
            Swal.fire("Atención", obj.msg, "warning");
            return;
          }
          modal.hide();
          if (obj.data && filaModal) filaModal.data(obj.data).draw(false);
          cargarResumen();
          Swal.fire({ title: obj.msg, icon: "success", timer: 1500, showConfirmButton: false });
        })
        .catch((err) => {
          btn.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }

  // ------------------------------------------------------------------
  //  DESCARGA DEL LAYOUT (con los filtros actuales)
  // ------------------------------------------------------------------
  function descargarLayout() {
    const p = new URLSearchParams();
    if (inpDesde.value) p.set("desde", inpDesde.value);
    if (inpHasta.value) p.set("hasta", inpHasta.value);
    if (selDist.value) p.set("distribuidor", selDist.value);
    if (selModelo.value) p.set("modelo", selModelo.value);
    if (selEstatus.value !== "") p.set("estatus", selEstatus.value);
    window.open(base_url + "/Inv_cobranzaunidades/descargarLayout?" + p.toString(), "_blank");
  }
  ["btnCobDescargar", "btnCobDescargar2"].forEach((id) => {
    const b = document.getElementById(id);
    if (b) b.addEventListener("click", descargarLayout);
  });

  // ------------------------------------------------------------------
  //  CARGA MASIVA
  // ------------------------------------------------------------------
  document.querySelectorAll(".cob-drop").forEach((zona) => {
    const input = zona.querySelector('input[type="file"]');
    const lbl = zona.querySelector(".cob-file-name");
    const pintar = () => {
      const f = input.files && input.files[0];
      zona.classList.toggle("cob-has-file", !!f);
      lbl.textContent = f ? f.name : zona.dataset.default;
    };
    input.addEventListener("change", pintar);
    ["dragenter", "dragover"].forEach((ev) => input.addEventListener(ev, () => zona.classList.add("cob-over")));
    ["dragleave", "drop"].forEach((ev) => input.addEventListener(ev, () => zona.classList.remove("cob-over")));
    zona.closest("form").addEventListener("reset", () => setTimeout(pintar, 0));
  });

  const formCarga = document.getElementById("formCobCarga");
  if (formCarga) {
    formCarga.addEventListener("submit", function (e) {
      e.preventDefault();
      const input = formCarga.querySelector('input[type="file"]');
      if (!input.files || !input.files.length) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }
      const btn = formCarga.querySelector('button[type="submit"]');
      cargando(true);
      btn.disabled = true;
      fetch(base_url + "/Inv_cobranzaunidades/procesarCarga", { method: "POST", body: new FormData(formCarga) })
        .then((r) => r.json())
        .then((obj) => {
          cargando(false);
          btn.disabled = false;
          if (!obj.status) {
            Swal.fire("Atención", obj.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }
          const omitidos = obj.omitidos || 0;
          document.getElementById("cobResultado").style.display = "block";
          const alertBox = document.getElementById("cobAlertResultado");
          alertBox.className = "alert mb-2 " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.textContent = obj.msg;
          document.getElementById("btnCobLog").style.display = omitidos > 0 ? "inline-block" : "none";
          formCarga.reset();
          cargarResumen();
          tabla.ajax.reload(null, false);
          Swal.fire({
            title: "Proceso finalizado",
            text: obj.msg,
            icon: omitidos > 0 ? "warning" : "success",
            confirmButtonColor: "#28a745",
          });
        })
        .catch((err) => {
          cargando(false);
          btn.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });
  }

  const btnLog = document.getElementById("btnCobLog");
  if (btnLog) {
    btnLog.addEventListener("click", () => window.open(base_url + "/Inv_cobranzaunidades/exportarLog", "_blank"));
  }
});
