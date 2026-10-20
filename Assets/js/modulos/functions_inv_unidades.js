/* Inventario de unidades (productos tipo P).
 * Una sola carga (getDatos) y todo se calcula en el navegador:
 *  - Por unidad: matriz sublínea > modelo > año [> color] × almacén (como el Excel de inventario).
 *  - Por almacén: una tarjeta por almacén con los modelos que tiene.
 *  - Listado de VIN.
 * Clic en cualquier número abre el panel lateral con los VIN que lo forman.
 * Excel: botones html5 de DataTables (jszip viene en el footer). PDF: reportePDF en el servidor. */
document.addEventListener("DOMContentLoaded", function () {
  const esc = (s) =>
    String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);
  const fmt = (n) => Number(n || 0).toLocaleString("es-MX");
  const byId = (id) => document.getElementById(id);
  const corto = (cve) => String(cve).replace(/^PL\d{2}-ALM-/, "");
  const nombrePlanta = (p) => (p === "OTROS" ? "Otros almacenes" : "Planta " + Number(String(p).slice(2)));
  const anioDe = (u) => String(u.anio ?? "S/A");
  const colorDe = (u) => u.color || "Sin color";

  // Paleta categórica fija para sublíneas (mismo color en KPIs, barras y encabezados)
  const PALETA = ["#405189", "#0ab39c", "#f7b84b", "#299cdb", "#f06548", "#6559cc", "#e83e8c", "#878a99", "#3d78e3", "#20c997"];
  const COLORES_UNIDAD = {
    blanco: "#ffffff", blanca: "#ffffff", negro: "#222222", rojo: "#d63939", azul: "#2c6ecb", gris: "#9aa0a6",
    plata: "#c7cbd1", naranja: "#f08c00", verde: "#2f9e44", amarillo: "#fcc419", cafe: "#8d6e63", "café": "#8d6e63",
  };
  const dot = (c) => {
    const hex = COLORES_UNIDAD[String(c).toLowerCase()] || "transparent";
    return `<span class="iu-dot" style="background:${hex}${hex === "transparent" ? ";border-style:dashed" : ""}"></span>`;
  };

  let datos = [];
  let ordenSub = [];
  const colorSub = {};
  let dtListado = null;

  const estado = { vista: "unidad", q: "", subs: new Set(), anios: new Set(), color: "", planta: "", almacen: "", porColor: false };
  try {
    const v = localStorage.getItem("iu_vista");
    if (["unidad", "almacen", "listado"].includes(v)) estado.vista = v;
    estado.porColor = localStorage.getItem("iu_porColor") === "1";
  } catch (e) {}

  const rankSub = (s) => {
    const i = ordenSub.indexOf(s);
    return i === -1 ? 99 : i;
  };
  const cmpSub = (a, b) => rankSub(a) - rankSub(b) || String(a).localeCompare(String(b));

  /* ---------------- Filtros ---------------- */
  function pasa(u, ignorar) {
    if (ignorar !== "subs" && estado.subs.size && !estado.subs.has(u.sublinea)) return false;
    if (ignorar !== "anios" && estado.anios.size && !estado.anios.has(anioDe(u))) return false;
    if (estado.color && colorDe(u) !== estado.color) return false;
    if (estado.planta && u.planta !== estado.planta) return false;
    if (estado.almacen && String(u.almacenid) !== estado.almacen) return false;
    if (estado.q) {
      const t = (u.cve_articulo + " " + u.descripcion + " " + u.numero_serie + " " + u.sublinea).toLowerCase();
      if (!t.includes(estado.q)) return false;
    }
    return true;
  }
  const filtrados = () => datos.filter((u) => pasa(u));

  function contarPor(lista, fn) {
    const m = new Map();
    lista.forEach((u) => {
      const k = fn(u);
      m.set(k, (m.get(k) || 0) + 1);
    });
    return m;
  }

  // Chips de sublínea y año: el número es lo que habría con los demás filtros aplicados
  function pintarChips() {
    const cSub = contarPor(datos.filter((u) => pasa(u, "subs")), (u) => u.sublinea);
    byId("fSublineas").innerHTML = [...new Set(datos.map((u) => u.sublinea))]
      .sort(cmpSub)
      .map(
        (s) =>
          `<button type="button" class="iu-toggle ${estado.subs.has(s) ? "on" : ""}" data-sub="${esc(s)}"><span class="iu-dot" style="background:${colorSub[s]};border:0;margin-right:.3rem"></span>${esc(s)}<span class="n">${fmt(cSub.get(s) || 0)}</span></button>`,
      )
      .join(" ");
    const cAn = contarPor(datos.filter((u) => pasa(u, "anios")), anioDe);
    byId("fAnios").innerHTML = [...new Set(datos.map(anioDe))]
      .sort()
      .map(
        (a) =>
          `<button type="button" class="iu-toggle ${estado.anios.has(a) ? "on" : ""}" data-anio="${esc(a)}">${esc(a)}<span class="n">${fmt(cAn.get(a) || 0)}</span></button>`,
      )
      .join(" ");
    const n =
      estado.subs.size + estado.anios.size + (estado.color ? 1 : 0) + (estado.planta ? 1 : 0) + (estado.almacen ? 1 : 0) + (estado.q ? 1 : 0);
    byId("nFiltros").textContent = n;
    byId("btnLimpiar").classList.toggle("d-none", n === 0);
  }

  /* ---------------- KPIs ---------------- */
  function pintarKpis(lista) {
    byId("kUnidades").textContent = fmt(lista.length);
    const an = contarPor(lista, anioDe);
    byId("kAnios").innerHTML = [...an.keys()]
      .sort()
      .map((a) => `<span class="iu-chip">${esc(a)} <b>${fmt(an.get(a))}</b></span>`)
      .join("");
    const modelos = new Set(lista.map((u) => u.inventarioid));
    const subs = new Set(lista.map((u) => u.sublinea));
    byId("kModelos").textContent = fmt(modelos.size);
    byId("kModelosSub").textContent = subs.size ? `en ${subs.size} sublínea${subs.size > 1 ? "s" : ""}` : "";
    const alms = new Set(lista.map((u) => u.almacenid));
    const plantas = new Set(lista.map((u) => u.planta));
    byId("kAlmacenes").textContent = fmt(alms.size);
    byId("kAlmacenesSub").textContent = [...plantas].sort().map(nombrePlanta).join(" · ");
    const cs = contarPor(lista, (u) => u.sublinea);
    const total = lista.length || 1;
    const orden = [...cs.keys()].sort(cmpSub);
    byId("kStack").innerHTML = orden
      .map((s) => `<span style="width:${(cs.get(s) * 100) / total}%;background:${colorSub[s]}" title="${esc(s)}: ${fmt(cs.get(s))}"></span>`)
      .join("");
    byId("kLeyenda").innerHTML = orden.map((s) => `<span><i style="background:${colorSub[s]}"></i>${esc(s)} <b>${fmt(cs.get(s))}</b></span>`).join("");
  }

  /* ---------------- Vista: por unidad (matriz) ---------------- */
  // Almacenes (columnas) presentes en la lista, ordenados por planta
  function columnasAlmacen(lista) {
    const m = new Map();
    lista.forEach((u) => m.set(String(u.almacenid), { id: String(u.almacenid), cve: u.cve_almacen, desc: u.almacen, planta: u.planta }));
    return [...m.values()].sort((a, b) => a.planta.localeCompare(b.planta) || a.cve.localeCompare(b.cve));
  }
  const claveFila = (u) => u.sublinea + "|" + u.inventarioid + "|" + anioDe(u) + (estado.porColor ? "|" + colorDe(u) : "");

  function armarFilas(lista) {
    const filas = new Map();
    lista.forEach((u) => {
      const k = claveFila(u);
      if (!filas.has(k))
        filas.set(k, {
          k, sub: u.sublinea, subDesc: u.sublinea_desc, cve: u.cve_articulo,
          modelo: u.descripcion, anio: anioDe(u), color: colorDe(u), alm: {}, total: 0,
        });
      const f = filas.get(k);
      f.alm[u.almacenid] = (f.alm[u.almacenid] || 0) + 1;
      f.total++;
    });
    return [...filas.values()].sort(
      (a, b) => cmpSub(a.sub, b.sub) || a.modelo.localeCompare(b.modelo) || a.anio.localeCompare(b.anio) || a.color.localeCompare(b.color),
    );
  }

  const cerradas = new Set();
  function pintarMatriz(lista) {
    const t = byId("tMatriz");
    if (!lista.length) {
      t.innerHTML = '<tbody><tr><td class="iu-vacio"><i class="ri-inbox-line fs-24 d-block mb-1"></i>No hay unidades con esos filtros</td></tr></tbody>';
      return;
    }
    const cols = columnasAlmacen(lista);
    const filas = armarFilas(lista);
    const max = Math.max(...filas.flatMap((f) => Object.values(f.alm)));
    // Sombreado según cantidad: ayuda a ver de un vistazo dónde se concentra el inventario
    const tinte = (v) => `background:rgba(var(--vz-primary-rgb),${(0.05 + (0.22 * v) / max).toFixed(3)})`;

    const plantas = [];
    cols.forEach((c, i) => {
      if (!plantas.length || plantas[plantas.length - 1].p !== c.planta) plantas.push({ p: c.planta, n: 0, i });
      plantas[plantas.length - 1].n++;
    });
    const iniPl = new Set(plantas.map((p) => p.i));

    let h = '<thead><tr class="plantas"><th class="col-mod"></th>';
    plantas.forEach((p) => (h += `<th class="pl" colspan="${p.n}">${esc(nombrePlanta(p.p))}</th>`));
    h += '<th class="ini-pl"></th></tr><tr><th class="col-mod">Modelo · año' + (estado.porColor ? " · color" : "") + "</th>";
    cols.forEach((c, i) => (h += `<th class="num${iniPl.has(i) ? " ini-pl" : ""}" title="${esc(c.cve)} · ${esc(c.desc)}">${esc(corto(c.cve))}</th>`));
    h += '<th class="num ini-pl">Total</th></tr></thead><tbody>';

    const celdas = (vals, total, clic) =>
      cols
        .map((c, i) => {
          const v = vals[c.id] || 0;
          const cls = "num" + (iniPl.has(i) ? " ini-pl" : "") + (v && clic ? " cel" : "");
          const attr = v && clic ? ` data-alm="${c.id}" style="${tinte(v)}" title="${esc(c.cve)}: ${fmt(v)} — clic para ver VIN"` : "";
          return `<td class="${cls}"${attr}>${v ? fmt(v) : '<span class="cero">·</span>'}</td>`;
        })
        .join("") + `<td class="num ini-pl col-tot">${fmt(total)}</td>`;

    let sub = null;
    const totCol = {};
    filas.forEach((f) => {
      if (f.sub !== sub) {
        sub = f.sub;
        const delSub = filas.filter((x) => x.sub === sub);
        const st = {};
        delSub.forEach((x) => Object.entries(x.alm).forEach(([a, v]) => (st[a] = (st[a] || 0) + v)));
        h += `<tr class="sub${cerradas.has(sub) ? " cerrado" : ""}" data-sub="${esc(sub)}"><td class="col-mod"><span class="caret">▾</span><span class="iu-dot" style="background:${colorSub[sub]};border:0;margin-right:.4rem"></span>${esc(sub)} <span class="text-muted fw-normal fs-12 ms-1">${esc(f.subDesc)}</span></td>${celdas(st, delSub.reduce((s, x) => s + x.total, 0), false)}</tr>`;
      }
      Object.entries(f.alm).forEach(([a, v]) => (totCol[a] = (totCol[a] || 0) + v));
      h += `<tr class="fila${cerradas.has(sub) ? " d-none" : ""}" data-sub="${esc(sub)}" data-k="${esc(f.k)}"><td class="col-mod" title="Ver todas las unidades"><span class="iu-mod-nom">${esc(f.modelo)}</span><span class="iu-anio">${esc(f.anio)}</span>${
        estado.porColor ? `<span class="iu-color">${dot(f.color)}${esc(f.color)}</span>` : ""
      }<span class="iu-mod-cve">${esc(f.cve)}</span></td>${celdas(f.alm, f.total, true)}</tr>`;
    });
    h += `</tbody><tfoot><tr><td class="col-mod">TOTAL</td>${celdas(totCol, lista.length, false)}</tr></tfoot>`;
    t.innerHTML = h;
  }

  byId("tMatriz").addEventListener("click", (e) => {
    const subRow = e.target.closest("tr.sub");
    if (subRow) {
      const s = subRow.dataset.sub;
      if (cerradas.has(s)) cerradas.delete(s);
      else cerradas.add(s);
      subRow.classList.toggle("cerrado", cerradas.has(s));
      byId("tMatriz")
        .querySelectorAll("tr.fila")
        .forEach((r) => {
          if (r.dataset.sub === s) r.classList.toggle("d-none", cerradas.has(s));
        });
      return;
    }
    const fila = e.target.closest("tr.fila");
    if (!fila) return;
    const cel = e.target.closest("td.cel");
    if (!cel && !e.target.closest("td.col-mod")) return;
    const k = fila.dataset.k;
    const lista = filtrados().filter((u) => claveFila(u) === k && (!cel || String(u.almacenid) === cel.dataset.alm));
    if (!lista.length) return;
    const u0 = lista[0];
    abrirDetalle(
      `${u0.descripcion} · ${anioDe(u0)}`,
      [u0.cve_articulo, u0.sublinea, estado.porColor ? colorDe(u0) : null, cel ? u0.cve_almacen : "Todos los almacenes"].filter(Boolean).join(" · "),
      lista,
    );
  });

  /* ---------------- Vista: por almacén ---------------- */
  function pintarAlmacenes(lista) {
    const cont = byId("panelAlmacen");
    if (!lista.length) {
      cont.innerHTML = '<div class="iu-vacio"><i class="ri-inbox-line fs-24 d-block mb-1"></i>No hay unidades con esos filtros</div>';
      return;
    }
    const cols = columnasAlmacen(lista);
    let h = "";
    let planta = null;
    cols.forEach((c) => {
      if (c.planta !== planta) {
        if (planta !== null) h += "</div>";
        planta = c.planta;
        h += `<div class="iu-planta-tit">${esc(nombrePlanta(planta))}</div><div class="row g-3 mb-4">`;
      }
      const delAlm = lista.filter((u) => String(u.almacenid) === c.id);
      const an = contarPor(delAlm, anioDe);
      const mod = new Map();
      delAlm.forEach((u) => {
        const k = u.inventarioid + "|" + anioDe(u);
        if (!mod.has(k)) mod.set(k, { inv: String(u.inventarioid), modelo: u.descripcion, sub: u.sublinea, anio: anioDe(u), n: 0 });
        mod.get(k).n++;
      });
      const mods = [...mod.values()].sort((a, b) => b.n - a.n || a.modelo.localeCompare(b.modelo));
      const maxN = mods[0].n;
      h += `<div class="col-md-6 col-xxl-4"><div class="iu-alm">
              <div class="iu-alm-head">
                <div><div class="cve">${esc(c.cve)}</div><div class="desc">${esc(c.desc)}</div>
                  <div class="mt-1">${[...an.keys()].sort().map((a) => `<span class="iu-chip">${esc(a)} <b>${fmt(an.get(a))}</b></span>`).join("")}</div></div>
                <div class="tot">${fmt(delAlm.length)}<small>unidades</small></div>
              </div>
              <ul class="iu-alm-list">${mods
                .map(
                  (m) => `<li data-alm="${c.id}" data-inv="${m.inv}" data-anio="${esc(m.anio)}" title="Ver VIN">
                    <span>${esc(m.modelo)}<span class="iu-anio">${esc(m.anio)}</span><span class="subl">${esc(m.sub)}</span></span>
                    <span class="n">${fmt(m.n)}</span>
                    <span class="bar"><span style="width:${(m.n * 100) / maxN}%;background:${colorSub[m.sub]}"></span></span></li>`,
                )
                .join("")}</ul></div></div>`;
    });
    cont.innerHTML = h + "</div>";
  }

  byId("panelAlmacen").addEventListener("click", (e) => {
    const li = e.target.closest("li[data-alm]");
    if (!li) return;
    const lista = filtrados().filter(
      (u) => String(u.almacenid) === li.dataset.alm && String(u.inventarioid) === li.dataset.inv && anioDe(u) === li.dataset.anio,
    );
    if (!lista.length) return;
    const u0 = lista[0];
    abrirDetalle(`${u0.descripcion} · ${anioDe(u0)}`, `${u0.cve_articulo} · ${u0.sublinea} · ${u0.cve_almacen}`, lista);
  });

  /* ---------------- Vista: listado de VIN ---------------- */
  function pintarListado(lista) {
    if (!dtListado) {
      dtListado = $("#tListado").DataTable({
        data: lista,
        columns: [
          { data: "numero_serie", render: (d, t) => (t === "display" ? `<span class="fw-semibold font-monospace">${esc(d)}</span>` : d) },
          { data: anioDe },
          { data: colorDe, render: (d, t) => (t === "display" ? `${dot(d)} ${esc(d)}` : d) },
          { data: "sublinea" },
          { data: "descripcion" },
          { data: "cve_articulo" },
          { data: "cve_almacen", render: (d, t, r) => (t === "display" ? `<span title="${esc(r.almacen)}">${esc(d)}</span>` : d) },
          { data: "numero_motor", defaultContent: "" },
          { data: "vin_origen", defaultContent: "" },
          { data: "referencia" },
        ],
        order: [[4, "asc"], [1, "asc"], [0, "asc"]],
        pageLength: 25,
        lengthMenu: [25, 50, 100, 500],
        dom: "<'row mb-2'<'col-sm-6'l><'col-sm-6 text-sm-end'i>><'table-responsive't><'row mt-2'<'col-sm-12'p>>",
        language: {
          lengthMenu: "Mostrar _MENU_",
          info: "_TOTAL_ unidades",
          infoEmpty: "Sin unidades",
          zeroRecords: "No hay unidades con esos filtros",
          paginate: { previous: "Anterior", next: "Siguiente" },
        },
      });
    } else {
      dtListado.clear().rows.add(lista).draw();
    }
    dtListado.columns.adjust();
  }

  /* ---------------- Panel de detalle ---------------- */
  let detalleActual = [];
  function abrirDetalle(titulo, sub, lista) {
    detalleActual = lista.slice().sort((a, b) => a.numero_serie.localeCompare(b.numero_serie));
    byId("iuDetalleTit").textContent = titulo;
    byId("iuDetalleSub").textContent = sub;
    const porAlm = contarPor(lista, (u) => u.cve_almacen);
    const porColor = contarPor(lista, colorDe);
    byId("iuDetalleResumen").innerHTML =
      `<span class="iu-chip"><b>${fmt(lista.length)}</b> unidades</span>` +
      [...porAlm.keys()].sort().map((a) => `<span class="iu-chip">${esc(a)} <b>${fmt(porAlm.get(a))}</b></span>`).join("") +
      [...porColor.keys()].sort().map((c) => `<span class="iu-chip">${dot(c)} ${esc(c)} <b>${fmt(porColor.get(c))}</b></span>`).join("");
    byId("iuDetalleBody").innerHTML = detalleActual
      .map(
        (u) => `<tr><td class="vin">${esc(u.numero_serie)}</td><td>${esc(anioDe(u))}</td><td>${dot(colorDe(u))} ${esc(colorDe(u))}</td><td title="${esc(u.almacen)}">${esc(u.cve_almacen)}</td><td>${esc(u.numero_motor || "")}</td><td class="vin">${u.vin_origen ? esc(u.vin_origen) : '<span class="text-muted fw-normal">—</span>'}</td></tr>`,
      )
      .join("");
    bootstrap.Offcanvas.getOrCreateInstance(byId("iuDetalle")).show();
  }

  function descargarCSV(nombre, filas) {
    const csv = filas.map((r) => r.map((v) => `"${String(v ?? "").replace(/"/g, '""')}"`).join(",")).join("\r\n");
    const a = document.createElement("a");
    a.href = URL.createObjectURL(new Blob(["﻿" + csv], { type: "text/csv;charset=utf-8" }));
    a.download = nombre;
    document.body.appendChild(a);
    a.click();
    setTimeout(() => {
      URL.revokeObjectURL(a.href);
      a.remove();
    }, 0);
  }
  byId("btnDetalleCSV").addEventListener("click", () =>
    descargarCSV("unidades_detalle.csv", [
      ["VIN", "Año modelo", "Color", "Clave", "Modelo", "Sublínea", "Almacén", "No. motor", "VIN origen", "Referencia"],
      ...detalleActual.map((u) => [u.numero_serie, anioDe(u), colorDe(u), u.cve_articulo, u.descripcion, u.sublinea, u.cve_almacen, u.numero_motor, u.vin_origen, u.referencia]),
    ]),
  );

  /* ---------------- Exportar a Excel (xlsx real con los botones de DataTables) ---------------- */
  function exportarXlsx(titulo, archivo, columnas, filas) {
    const cont = byId("iuExport");
    cont.innerHTML = '<table id="tExport"></table>';
    const dt = $("#tExport").DataTable({
      data: filas,
      columns: columnas.map((c, i) => ({ title: c, data: i })),
      paging: false,
      ordering: false,
      searching: false,
      info: false,
      dom: "t",
    });
    new $.fn.dataTable.Buttons(dt, { buttons: [{ extend: "excelHtml5", title: titulo, filename: archivo }] });
    dt.button(0).trigger();
    setTimeout(() => {
      dt.destroy();
      cont.innerHTML = "";
    }, 500);
  }

  byId("btnExcel").addEventListener("click", () => {
    const lista = filtrados();
    const hoy = new Date().toISOString().slice(0, 10);
    if (estado.vista === "unidad") {
      const cols = columnasAlmacen(lista);
      exportarXlsx(
        "Inventario de unidades por almacén",
        "inventario_unidades_" + hoy,
        ["Sublínea", "Clave", "Modelo", "Año modelo", ...(estado.porColor ? ["Color"] : []), ...cols.map((c) => c.cve), "Total"],
        armarFilas(lista).map((f) => [f.sub, f.cve, f.modelo, f.anio, ...(estado.porColor ? [f.color] : []), ...cols.map((c) => f.alm[c.id] || 0), f.total]),
      );
    } else if (estado.vista === "almacen") {
      const m = new Map();
      lista.forEach((u) => {
        const k = [u.planta, u.cve_almacen, u.sublinea, u.cve_articulo, anioDe(u)].join("|");
        if (!m.has(k)) m.set(k, [nombrePlanta(u.planta), u.cve_almacen, u.almacen, u.sublinea, u.cve_articulo, u.descripcion, anioDe(u), 0]);
        m.get(k)[7]++;
      });
      exportarXlsx(
        "Unidades por almacén",
        "unidades_por_almacen_" + hoy,
        ["Planta", "Almacén", "Descripción almacén", "Sublínea", "Clave", "Modelo", "Año modelo", "Unidades"],
        [...m.values()].sort((a, b) => a[1].localeCompare(b[1]) || b[7] - a[7]),
      );
    } else {
      exportarXlsx(
        "Listado de unidades (VIN)",
        "unidades_vin_" + hoy,
        ["VIN", "Año modelo", "Color", "Sublínea", "Clave", "Modelo", "Almacén", "No. motor", "VIN origen", "Referencia"],
        lista.map((u) => [u.numero_serie, anioDe(u), colorDe(u), u.sublinea, u.cve_articulo, u.descripcion, u.cve_almacen, u.numero_motor || "", u.vin_origen || "", u.referencia]),
      );
    }
  });

  byId("btnPDF").addEventListener("click", () => {
    const p = new URLSearchParams({
      sub: [...estado.subs].join(","),
      anio: [...estado.anios].join(","),
      color: estado.color,
      planta: estado.planta,
      almacen: estado.almacen,
      q: estado.q,
      porColor: estado.porColor ? 1 : 0,
    });
    window.open(base_url + "/Inv_unidades/reportePDF?" + p.toString(), "_blank");
  });

  /* ---------------- Render general ---------------- */
  const PANELES = { unidad: "panelUnidad", almacen: "panelAlmacen", listado: "panelListado" };
  function render() {
    const lista = filtrados();
    pintarChips();
    pintarKpis(lista);
    Object.entries(PANELES).forEach(([v, id]) => byId(id).classList.toggle("d-none", estado.vista !== v));
    byId("wrapPorColor").classList.toggle("d-none", estado.vista !== "unidad");
    if (estado.vista === "unidad") pintarMatriz(lista);
    else if (estado.vista === "almacen") pintarAlmacenes(lista);
    else pintarListado(lista);
  }

  function llenarSelects() {
    const colores = [...new Set(datos.map(colorDe))].sort();
    byId("fColor").innerHTML = '<option value="">Todos</option>' + colores.map((c) => `<option>${esc(c)}</option>`).join("");
    const plantas = [...new Set(datos.map((u) => u.planta))].sort();
    byId("fPlanta").innerHTML =
      '<option value="">Todas</option>' + plantas.map((p) => `<option value="${esc(p)}">${esc(nombrePlanta(p))}</option>`).join("");
    pintarSelectAlmacen();
  }
  function pintarSelectAlmacen() {
    const alms = columnasAlmacen(datos).filter((a) => !estado.planta || a.planta === estado.planta);
    if (estado.almacen && !alms.some((a) => a.id === estado.almacen)) estado.almacen = "";
    byId("fAlmacen").innerHTML =
      '<option value="">Todos</option>' +
      alms.map((a) => `<option value="${a.id}" ${a.id === estado.almacen ? "selected" : ""}>${esc(a.cve)}</option>`).join("");
  }

  /* ---------------- Eventos ---------------- */
  document.querySelectorAll('input[name="iuVista"]').forEach((r) =>
    r.addEventListener("change", function () {
      estado.vista = this.value;
      try {
        localStorage.setItem("iu_vista", estado.vista);
      } catch (e) {}
      render();
    }),
  );
  byId("fPorColor").addEventListener("change", function () {
    estado.porColor = this.checked;
    try {
      localStorage.setItem("iu_porColor", this.checked ? "1" : "0");
    } catch (e) {}
    render();
  });
  let tBus;
  byId("fBuscar").addEventListener("input", function () {
    clearTimeout(tBus);
    tBus = setTimeout(() => {
      estado.q = this.value.trim().toLowerCase();
      render();
    }, 200);
  });
  byId("fSublineas").addEventListener("click", (e) => {
    const b = e.target.closest("[data-sub]");
    if (!b) return;
    const s = b.dataset.sub;
    if (estado.subs.has(s)) estado.subs.delete(s);
    else estado.subs.add(s);
    render();
  });
  byId("fAnios").addEventListener("click", (e) => {
    const b = e.target.closest("[data-anio]");
    if (!b) return;
    const a = b.dataset.anio;
    if (estado.anios.has(a)) estado.anios.delete(a);
    else estado.anios.add(a);
    render();
  });
  byId("fColor").addEventListener("change", function () {
    estado.color = this.value;
    render();
  });
  byId("fPlanta").addEventListener("change", function () {
    estado.planta = this.value;
    pintarSelectAlmacen();
    render();
  });
  byId("fAlmacen").addEventListener("change", function () {
    estado.almacen = this.value;
    render();
  });
  byId("btnLimpiar").addEventListener("click", () => {
    Object.assign(estado, { q: "", color: "", planta: "", almacen: "" });
    estado.subs.clear();
    estado.anios.clear();
    byId("fBuscar").value = "";
    byId("fColor").value = "";
    byId("fPlanta").value = "";
    pintarSelectAlmacen();
    render();
  });

  /* ---------------- Carga ---------------- */
  byId(estado.vista === "almacen" ? "vAlmacen" : estado.vista === "listado" ? "vListado" : "vUnidad").checked = true;
  byId("fPorColor").checked = estado.porColor;

  fetch(base_url + "/Inv_unidades/getDatos")
    .then((r) => r.json())
    .then((d) => {
      datos = d.unidades || [];
      ordenSub = d.orden_sublineas || [];
      [...new Set(datos.map((u) => u.sublinea))].sort(cmpSub).forEach((s, i) => (colorSub[s] = PALETA[i % PALETA.length]));
      byId("iuGenerado").textContent = `Existencia al ${d.generado} · ${fmt(datos.length)} unidades`;
      llenarSelects();
      render();
    })
    .catch(() => {
      byId("iuGenerado").textContent = "No se pudo cargar el inventario.";
    });
});
