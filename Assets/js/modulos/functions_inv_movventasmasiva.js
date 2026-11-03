document.addEventListener("DOMContentLoaded", function () {
  const divLoading = document.querySelector("#divLoading");
  const MESES = ["enero", "febrero", "marzo", "abril", "mayo", "junio", "julio", "agosto", "septiembre", "octubre", "noviembre", "diciembre"];

  // ------------------------------------------------------------------
  //  UTILIDADES
  // ------------------------------------------------------------------
  const fmtNum = (n) => Number(n || 0).toLocaleString("es-MX");
  const pad = (n) => String(n).padStart(2, "0");
  const ymd = (d) => d.getFullYear() + "-" + pad(d.getMonth() + 1) + "-" + pad(d.getDate());
  const fechaLarga = (s) => {
    if (!s) return "";
    const p = s.split("-");
    return p.length === 3 ? p[2] + "/" + p[1] + "/" + p[0] : s;
  };

  function mostrarCargando(si) {
    if (divLoading) divLoading.style.display = si ? "flex" : "none";
  }

  // ------------------------------------------------------------------
  //  INDICADORES
  // ------------------------------------------------------------------
  function cargarResumen() {
    fetch(base_url + "/Inv_movventasmasiva/getResumen")
      .then((r) => r.json())
      .then((obj) => {
        if (!obj.status) return;
        const d = obj.data || {};
        const set = (id, v) => {
          const el = document.getElementById(id);
          if (el) el.textContent = fmtNum(v);
        };
        set("kpiFacturadas", d.facturadas);
        set("kpiFacturadasMes", d.facturadas_mes);
        set("kpiDisponibles", d.disponibles);
        set("kpiTatuadas", d.tatuadas);
      })
      .catch((err) => console.error(err));

    const hoy = new Date();
    const lbl = document.getElementById("kpiMesNombre");
    if (lbl) lbl.textContent = "Ventas de " + MESES[hoy.getMonth()] + " " + hoy.getFullYear();
  }
  cargarResumen();

  // ------------------------------------------------------------------
  //  TABLA DE UNIDADES FACTURADAS
  // ------------------------------------------------------------------
  const inpDesde = document.getElementById("factDesde");
  const inpHasta = document.getElementById("factHasta");
  let tableFacturadas = null;

  function textoPeriodo() {
    const d = inpDesde ? inpDesde.value : "";
    const h = inpHasta ? inpHasta.value : "";
    if (!d && !h) return "Todo el histórico";
    if (d && h) return fechaLarga(d) + " – " + fechaLarga(h);
    if (d) return "Desde " + fechaLarga(d);
    return "Hasta " + fechaLarga(h);
  }

  function actualizarResumenTabla(rows) {
    const dist = new Set();
    rows.forEach((r) => {
      const n = (r.distribuidor || "").trim().toUpperCase();
      if (n) dist.add(n);
    });
    document.getElementById("factTotUnidades").textContent = fmtNum(rows.length);
    document.getElementById("factTotDistribuidores").textContent = fmtNum(dist.size);
    document.getElementById("factPeriodo").textContent = textoPeriodo();
  }

  if (document.getElementById("tableFacturadas")) {
    const txt = $.fn.dataTable.render.text();
    tableFacturadas = $("#tableFacturadas").DataTable({
      processing: true,
      pageLength: 25,
      lengthMenu: [10, 25, 50, 100],
      order: [[0, "desc"]],
      dom:
        "<'row align-items-center mb-2'<'col-md-6 d-flex align-items-center gap-2'lB><'col-md-6'f>>" +
        "rt" +
        "<'row align-items-center mt-2'<'col-md-5'i><'col-md-7'p>>",
      buttons: [
        {
          extend: "excelHtml5",
          text: '<i class="ri-file-excel-2-line align-bottom me-1"></i> Exportar Excel',
          className: "btn btn-soft-success btn-sm",
          title: "Unidades facturadas",
          filename: function () {
            return "Unidades_Facturadas_" + ymd(new Date());
          },
        },
      ],
      ajax: {
        url: base_url + "/Inv_movventasmasiva/getFacturadas",
        type: "GET",
        data: function () {
          return {
            desde: inpDesde ? inpDesde.value : "",
            hasta: inpHasta ? inpHasta.value : "",
          };
        },
        dataSrc: function (json) {
          if (!json.status) {
            Swal.fire("Atención", json.msg || "No fue posible consultar las unidades facturadas.", "warning");
            actualizarResumenTabla([]);
            return [];
          }
          actualizarResumenTabla(json.data || []);
          return json.data || [];
        },
        error: function (xhr) {
          console.error(xhr.responseText);
          actualizarResumenTabla([]);
          Swal.fire("Error", "Ocurrió un error al consultar las unidades facturadas.", "error");
        },
      },
      columns: [
        {
          data: "fecha_venta",
          render: function (d, type) {
            return type === "display" || type === "filter" ? fechaLarga(d) : d;
          },
        },
        { data: "vin", className: "mvm-vin fw-semibold", render: txt },
        { data: "numero_motor", render: txt },
        { data: "sku", render: txt },
        { data: "descripcion", render: txt },
        {
          data: "distribuidor",
          render: function (d, type) {
            if (type !== "display") return d;
            return d ? $("<span>").text(d).html() : '<span class="text-muted fst-italic">Sin distribuidor</span>';
          },
        },
        {
          data: "factura",
          render: function (d, type) {
            if (type !== "display") return d;
            return d ? '<span class="badge bg-primary-subtle text-primary fs-12">' + $("<span>").text(d).html() + "</span>" : "";
          },
        },
        { data: "almacen_salida", render: txt },
        { data: "folio_venta", render: txt },
      ],
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json",
        emptyTable: "No hay unidades facturadas en el periodo seleccionado.",
      },
    });

    // Recalcular anchos al volver a la pestaña (la tabla se pudo dibujar oculta)
    document.querySelectorAll('#mvmTabs a[data-bs-toggle="tab"]').forEach((a) => {
      a.addEventListener("shown.bs.tab", function (e) {
        if (e.target.getAttribute("href") === "#tabFacturadas" && tableFacturadas) {
          tableFacturadas.columns.adjust().responsive.recalc();
        }
      });
    });
  }

  function recargarFacturadas() {
    if (!tableFacturadas) return;
    const d = inpDesde.value;
    const h = inpHasta.value;
    if (d && h && d > h) {
      Swal.fire("Atención", "La fecha inicial no puede ser mayor a la final.", "warning");
      return;
    }
    tableFacturadas.ajax.reload();
  }

  function marcarRapido(rango) {
    document.querySelectorAll("#factRapidos [data-rango]").forEach((b) => {
      const activo = b.dataset.rango === rango;
      b.classList.toggle("active", activo);
      b.classList.toggle("btn-primary", activo);
      b.classList.toggle("btn-outline-primary", !activo);
    });
  }

  document.querySelectorAll("#factRapidos [data-rango]").forEach((btn) => {
    btn.addEventListener("click", function () {
      const hoy = new Date();
      let d = "";
      let h = "";
      switch (this.dataset.rango) {
        case "mes":
          d = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
          h = ymd(hoy);
          break;
        case "mesant":
          d = ymd(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1));
          h = ymd(new Date(hoy.getFullYear(), hoy.getMonth(), 0));
          break;
        case "30": {
          const ini = new Date(hoy);
          ini.setDate(ini.getDate() - 29);
          d = ymd(ini);
          h = ymd(hoy);
          break;
        }
        default:
          break;
      }
      inpDesde.value = d;
      inpHasta.value = h;
      marcarRapido(this.dataset.rango);
      recargarFacturadas();
    });
  });

  [inpDesde, inpHasta].forEach((inp) => {
    if (inp) inp.addEventListener("change", () => marcarRapido(null));
  });

  const btnFiltrar = document.getElementById("btnFiltrarFacturadas");
  if (btnFiltrar) btnFiltrar.addEventListener("click", recargarFacturadas);

  const btnLimpiar = document.getElementById("btnLimpiarFacturadas");
  if (btnLimpiar) {
    btnLimpiar.addEventListener("click", function () {
      inpDesde.value = "";
      inpHasta.value = "";
      marcarRapido("todo");
      if (tableFacturadas) tableFacturadas.search("");
      recargarFacturadas();
    });
  }
  marcarRapido("todo");

  // ------------------------------------------------------------------
  //  ZONAS DE ARCHIVO (arrastrar / seleccionar)
  // ------------------------------------------------------------------
  document.querySelectorAll(".mvm-drop").forEach((zona) => {
    const input = zona.querySelector('input[type="file"]');
    const lbl = zona.querySelector(".mvm-file-name");
    const pintar = () => {
      const f = input.files && input.files[0];
      zona.classList.toggle("mvm-has-file", !!f);
      lbl.textContent = f ? f.name : zona.dataset.default;
    };
    input.addEventListener("change", pintar);
    ["dragenter", "dragover"].forEach((ev) => input.addEventListener(ev, () => zona.classList.add("mvm-over")));
    ["dragleave", "drop"].forEach((ev) => input.addEventListener(ev, () => zona.classList.remove("mvm-over")));
    const form = zona.closest("form");
    if (form) form.addEventListener("reset", () => setTimeout(pintar, 0));
  });

  // ------------------------------------------------------------------
  //  SUBIDA GENÉRICA (facturación y devoluciones)
  // ------------------------------------------------------------------
  function configurarCarga(cfg) {
    const form = document.querySelector(cfg.form);
    if (!form) return;

    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const input = form.querySelector('input[type="file"]');
      if (!input || !input.files || input.files.length === 0) {
        Swal.fire("Atención", "Selecciona un archivo antes de continuar.", "warning");
        return;
      }

      const box = document.querySelector(cfg.box);
      const alertBox = document.querySelector(cfg.alert);
      const btnLog = document.querySelector(cfg.btnLog);
      const btnSubmit = form.querySelector('button[type="submit"]');

      mostrarCargando(true);
      if (btnSubmit) btnSubmit.disabled = true;

      fetch(base_url + cfg.url, { method: "POST", body: new FormData(form) })
        .then((r) => r.json())
        .then((obj) => {
          mostrarCargando(false);
          if (btnSubmit) btnSubmit.disabled = false;

          if (!obj.status) {
            Swal.fire("Atención", obj.msg || "No fue posible procesar el archivo.", "warning");
            return;
          }

          const omitidos = obj.omitidos || 0;
          box.style.display = "block";
          alertBox.className = "alert mb-2 " + (omitidos > 0 ? "alert-warning" : "alert-success");
          alertBox.textContent = obj.msg;
          btnLog.style.display = omitidos > 0 ? "inline-block" : "none";
          form.reset();

          // La carga cambia estatus de VINs: refrescar indicadores y listado
          cargarResumen();
          if (tableFacturadas) tableFacturadas.ajax.reload(null, false);

          Swal.fire({
            title: "Proceso finalizado",
            text: obj.msg,
            icon: omitidos > 0 ? "warning" : "success",
            confirmButtonText: "OK",
            confirmButtonColor: "#28a745",
          });
        })
        .catch(function (err) {
          mostrarCargando(false);
          if (btnSubmit) btnSubmit.disabled = false;
          console.error(err);
          Swal.fire("Error", "Ocurrió un error en el servidor. Inténtalo de nuevo.", "error");
        });
    });

    const btnLog = document.querySelector(cfg.btnLog);
    if (btnLog) {
      btnLog.addEventListener("click", function () {
        window.open(base_url + cfg.urlLog, "_blank");
      });
    }
  }

  configurarCarga({
    form: "#formCargaVentas",
    box: "#resultadoCargaVentas",
    alert: "#alertResultadoCargaVentas",
    btnLog: "#btnLogCargaVentas",
    url: "/Inv_movventasmasiva/procesarCarga",
    urlLog: "/Inv_movventasmasiva/exportarLog",
  });

  configurarCarga({
    form: "#formCargaDevoluciones",
    box: "#resultadoCargaDevoluciones",
    alert: "#alertResultadoCargaDevoluciones",
    btnLog: "#btnLogCargaDevoluciones",
    url: "/Inv_movventasmasiva/procesarDevoluciones",
    urlLog: "/Inv_movventasmasiva/exportarLogDevoluciones",
  });

  // ------------------------------------------------------------------
  //  DESCARGA DE LAYOUTS
  // ------------------------------------------------------------------
  const btnLayout = document.querySelector("#btnDescargarLayoutVentas");
  if (btnLayout) {
    btnLayout.addEventListener("click", function () {
      window.open(base_url + "/Inv_movventasmasiva/descargarLayout", "_blank");
    });
  }

  const btnLayoutDev = document.querySelector("#btnDescargarLayoutDev");
  if (btnLayoutDev) {
    btnLayoutDev.addEventListener("click", function () {
      const p = new URLSearchParams();
      const d = document.querySelector("#devDesde").value;
      const h = document.querySelector("#devHasta").value;
      if (d) p.set("desde", d);
      if (h) p.set("hasta", h);
      window.open(base_url + "/Inv_movventasmasiva/descargarLayoutDevoluciones?" + p.toString(), "_blank");
    });
  }
});
