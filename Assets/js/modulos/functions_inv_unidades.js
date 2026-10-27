/* Inventario de unidades PT (productos tipo P).
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
  const plural = (n, s, p) => `${fmt(n)} ${n === 1 ? s : p || s + "s"}`;
  const sinMovimiento = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  // Paleta categórica fija para sublíneas (mismo color en KPIs, filtros, barras y encabezados)
  const PALETA = ["#25a0e2", "#0ab39c", "#f7b84b", "#6559cc", "#f06548", "#e83e8c", "#20c997", "#405189", "#fd7e14", "#878a99"];
  const COLORES_UNIDAD = {
    blanco: "#ffffff", blanca: "#ffffff", negro: "#222222", rojo: "#d63939", azul: "#2c6ecb", gris: "#9aa0a6",
    plata: "#c7cbd1", naranja: "#f08c00", verde: "#2f9e44", amarillo: "#fcc419", cafe: "#8d6e63", "café": "#8d6e63",
  };
  const dot = (c) => {
    const hex = COLORES_UNIDAD[String(c).toLowerCase()] || "transparent";
    return `<span class="iu-dot" style="background:${hex}${hex === "transparent" ? ";border-style:dashed" : ""}"></span>`;
  };
  const vacio = (titulo, texto) =>
    `<div class="iu-vacio"><div class="ico"><i class="ri-inbox-2-line"></i></div><h6>${titulo}</h6><div class="fs-13">${texto}</div>` +
    `<button type="button" class="btn btn-sm btn-soft-primary mt-3" data-accion="limpiar"><i class="ri-filter-off-line align-bottom me-1"></i>Limpiar filtros</button></div>`;

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
  // Plantas en orden (PL01, PL02…) y "Otros almacenes" al final
  const cmpPlanta = (a, b) => (a === "OTROS") - (b === "OTROS") || String(a).localeCompare(String(b));

  /* ---------------- Utilidades de UI ---------------- */
  // Cuenta animada del valor anterior al nuevo
  function animarNumero(el, valor) {
    const desde = Number(el.dataset.v || 0);
    el.dataset.v = valor;
    if (sinMovimiento || desde === valor) {
      el.textContent = fmt(valor);
      return;
    }
    const t0 = performance.now();
    const dur = 450;
    const paso = (t) => {
      const p = Math.min(1, (t - t0) / dur);
      const e = 1 - Math.pow(1 - p, 3);
      el.textContent = fmt(Math.round(desde + (valor - desde) * e));
      if (p < 1 && Number(el.dataset.v) === valor) requestAnimationFrame(paso);
    };
    requestAnimationFrame(paso);
  }

  let tToast;
  function aviso(msg) {
    const t = byId("iuToast");
    t.innerHTML = `<i class="ri-checkbox-circle-line align-bottom me-1"></i>${esc(msg)}`;
    t.classList.add("on");
    clearTimeout(tToast);
    tToast = setTimeout(() => t.classList.remove("on"), 1800);
  }

  function copiar(texto, msg) {
    const ok = () => aviso(msg || "Copiado al portapapeles");
    if (navigator.clipboard && window.isSecureContext) {
      navigator.clipboard.writeText(texto).then(ok, () => copiarViejo(texto, ok));
    } else copiarViejo(texto, ok);
  }
  function copiarViejo(texto, ok) {
    const ta = document.createElement("textarea");
    ta.value = texto;
    ta.style.cssText = "position:fixed;left:-9999px";
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand("copy");
      ok();
    } catch (e) {}
    ta.remove();
  }
  const btnCopiar = (vin) => `<button type="button" class="iu-copy" data-copiar="${esc(vin)}" title="Copiar VIN"><i class="ri-file-copy-line"></i></button>`;

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
      .map((s) => {
        const n = cSub.get(s) || 0;
        return `<button type="button" class="iu-toggle ${estado.subs.has(s) ? "on" : ""} ${n ? "" : "vacio"}" data-sub="${esc(s)}" aria-pressed="${estado.subs.has(s)}"><span class="sw" style="background:${colorSub[s]}"></span>${esc(s)}<span class="n">${fmt(n)}</span></button>`;
      })
      .join("");
    const cAn = contarPor(datos.filter((u) => pasa(u, "anios")), anioDe);
    byId("fAnios").innerHTML = [...new Set(datos.map(anioDe))]
      .sort()
      .map((a) => {
        const n = cAn.get(a) || 0;
        return `<button type="button" class="iu-toggle ${estado.anios.has(a) ? "on" : ""} ${n ? "" : "vacio"}" data-anio="${esc(a)}" aria-pressed="${estado.anios.has(a)}">${esc(a)}<span class="n">${fmt(n)}</span></button>`;
      })
      .join("");
  }

  // Barra de "filtros activos": cada filtro se puede quitar de forma individual
  function pintarActivos() {
    const p = [];
    const pill = (tipo, valor, etiqueta, texto) =>
      `<span class="iu-pill"><small>${etiqueta}:</small> ${texto}<button type="button" data-quitar="${tipo}" data-valor="${esc(valor)}" aria-label="Quitar filtro"><i class="ri-close-line"></i></button></span>`;
    if (estado.q) p.push(pill("q", "", "Búsqueda", `“${esc(estado.q)}”`));
    [...estado.subs].sort(cmpSub).forEach((s) => p.push(pill("sub", s, "Sublínea", esc(s))));
    [...estado.anios].sort().forEach((a) => p.push(pill("anio", a, "Año", esc(a))));
    if (estado.planta) p.push(pill("planta", "", "Planta", esc(nombrePlanta(estado.planta))));
    if (estado.almacen) {
      const a = datos.find((u) => String(u.almacenid) === estado.almacen);
      p.push(pill("almacen", "", "Almacén", esc(a ? a.cve_almacen : estado.almacen)));
    }
    if (estado.color) p.push(pill("color", "", "Color", `${dot(estado.color)} ${esc(estado.color)}`));
    const cont = byId("iuActivos");
    cont.classList.toggle("d-none", !p.length);
    cont.innerHTML = p.length
      ? `<span class="tit"><i class="ri-filter-3-line align-bottom me-1"></i>Filtros activos</span>${p.join("")}<button type="button" class="btn btn-sm btn-link text-danger text-decoration-none ms-auto p-0" data-accion="limpiar"><i class="ri-close-circle-line align-bottom"></i> Limpiar todo</button>`
      : "";
  }

  /* ---------------- KPIs ---------------- */
  function pintarKpis(lista) {
    animarNumero(byId("kUnidades"), lista.length);
    const an = contarPor(lista, anioDe);
    byId("kAnios").innerHTML = [...an.keys()]
      .sort()
      .map((a) => `<span class="iu-chip">${esc(a)} <b>${fmt(an.get(a))}</b></span>`)
      .join("");
    const modelos = new Set(lista.map((u) => u.inventarioid));
    const subs = new Set(lista.map((u) => u.sublinea));
    animarNumero(byId("kModelos"), modelos.size);
    byId("kModelosSub").textContent = subs.size ? `en ${plural(subs.size, "sublínea")}` : "Sin modelos con estos filtros";
    const alms = new Set(lista.map((u) => u.almacenid));
    const plantas = new Set(lista.map((u) => u.planta));
    animarNumero(byId("kAlmacenes"), alms.size);
    byId("kAlmacenesSub").textContent = [...plantas].sort(cmpPlanta).map(nombrePlanta).join(" · ");
    const cs = contarPor(lista, (u) => u.sublinea);
    const total = lista.length || 1;
    const orden = [...cs.keys()].sort(cmpSub);
    byId("kStack").innerHTML = orden
      .map((s) => {
        const pct = (cs.get(s) * 100) / total;
        return `<span style="width:${pct}%;background:${colorSub[s]}" title="${esc(s)}: ${fmt(cs.get(s))} (${pct.toFixed(1)}%)"></span>`;
      })
      .join("");
    byId("kLeyenda").innerHTML = orden
      .map(
        (s) =>
          `<button type="button" class="${estado.subs.has(s) ? "on" : ""}" data-sub="${esc(s)}"><i style="background:${colorSub[s]}"></i>${esc(s)} <b>${fmt(cs.get(s))}</b></button>`,
      )
      .join("");
  }

  /* ---------------- Vista: por unidad (matriz) ---------------- */
  // Almacenes (columnas) presentes en la lista, ordenados por planta
  function columnasAlmacen(lista) {
    const m = new Map();
    lista.forEach((u) => m.set(String(u.almacenid), { id: String(u.almacenid), cve: u.cve_almacen, desc: u.almacen, planta: u.planta }));
    return [...m.values()].sort((a, b) => cmpPlanta(a.planta, b.planta) || a.cve.localeCompare(b.cve));
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

  // Sombreado según cantidad: ayuda a ver de un vistazo dónde se concentra el inventario
  const alfaTinte = (v, max) => (0.05 + (0.3 * v) / max).toFixed(3);
  byId("heatLeg").innerHTML = [1, 2, 3, 4, 5].map((i) => `<i style="background:rgba(var(--vz-primary-rgb),${alfaTinte(i, 5)})"></i>`).join("");

  // Sublíneas contraídas en la matriz. Al entrar, todas inician contraídas (se llena en la primera carga).
  const cerradas = new Set();
  let primeraCarga = true;
  function pintarMatriz(lista) {
    const t = byId("tMatriz");
    byId("mBar").classList.toggle("invisible", !lista.length);
    if (!lista.length) {
      t.innerHTML = `<tbody><tr><td>${vacio("No hay unidades con esos filtros", "Prueba quitando algún filtro o cambiando la búsqueda.")}</td></tr></tbody>`;
      return;
    }
    const cols = columnasAlmacen(lista);
    const filas = armarFilas(lista);
    const max = Math.max(...filas.flatMap((f) => Object.values(f.alm)));
    const tinte = (v) => `background-color:rgba(var(--vz-primary-rgb),${alfaTinte(v, max)})`;

    const plantas = [];
    cols.forEach((c, i) => {
      if (!plantas.length || plantas[plantas.length - 1].p !== c.planta) plantas.push({ p: c.planta, n: 0, i });
      plantas[plantas.length - 1].n++;
    });
    const iniPl = new Set(plantas.map((p) => p.i));

    let h = '<thead><tr class="plantas"><th class="col-mod"></th>';
    plantas.forEach((p) => (h += `<th class="pl" colspan="${p.n}">${esc(nombrePlanta(p.p))}</th>`));
    h += '<th class="ini-pl"></th></tr><tr class="cols"><th class="col-mod">Modelo · año' + (estado.porColor ? " · color" : "") + "</th>";
    cols.forEach(
      (c, i) => (h += `<th class="num${iniPl.has(i) ? " ini-pl" : ""}" data-c="${c.id}" title="${esc(c.cve)} · ${esc(c.desc)}">${esc(corto(c.cve))}</th>`),
    );
    h += '<th class="num ini-pl" data-c="tot">Total</th></tr></thead><tbody>';

    const celdas = (vals, total, clic) =>
      cols
        .map((c, i) => {
          const v = vals[c.id] || 0;
          const cls = "num" + (iniPl.has(i) ? " ini-pl" : "") + (v && clic ? " cel" : "");
          const attr = v && clic ? ` style="${tinte(v)}" title="${esc(c.cve)}: ${fmt(v)} — clic para ver VIN"` : "";
          return `<td class="${cls}" data-alm="${c.id}"${attr}>${v ? fmt(v) : '<span class="cero">–</span>'}</td>`;
        })
        .join("") + `<td class="num ini-pl col-tot" data-alm="tot">${fmt(total)}</td>`;

    let sub = null;
    const totCol = {};
    filas.forEach((f) => {
      if (f.sub !== sub) {
        sub = f.sub;
        const delSub = filas.filter((x) => x.sub === sub);
        const st = {};
        delSub.forEach((x) => Object.entries(x.alm).forEach(([a, v]) => (st[a] = (st[a] || 0) + v)));
        h += `<tr class="sub${cerradas.has(sub) ? " cerrado" : ""}" data-sub="${esc(sub)}" style="--iu-sc:${colorSub[sub]}"><td class="col-mod"><span class="caret"><i class="ri-arrow-down-s-line"></i></span><span class="iu-dot" style="background:${colorSub[sub]};border:0;margin-right:.45rem"></span>${esc(sub)}<span class="cnt">${esc(f.subDesc)} · ${plural(delSub.length, "renglón", "renglones")}</span></td>${celdas(st, delSub.reduce((s, x) => s + x.total, 0), false)}</tr>`;
      }
      Object.entries(f.alm).forEach(([a, v]) => (totCol[a] = (totCol[a] || 0) + v));
      h += `<tr class="fila${cerradas.has(sub) ? " d-none" : ""}" data-sub="${esc(sub)}" data-k="${esc(f.k)}"><td class="col-mod" title="Ver todas las unidades de este modelo"><span class="iu-mod-nom">${esc(f.modelo)}</span><span class="iu-anio">${esc(f.anio)}</span>${
        estado.porColor ? `<span class="iu-color">${dot(f.color)}${esc(f.color)}</span>` : ""
      }<span class="iu-mod-cve">${esc(f.cve)}</span></td>${celdas(f.alm, f.total, true)}</tr>`;
    });
    h += `</tbody><tfoot><tr><td class="col-mod">TOTAL GENERAL</td>${celdas(totCol, lista.length, false)}</tr></tfoot>`;
    t.innerHTML = h;
  }

  function alternarSub(s, cerrar) {
    if (cerrar) cerradas.add(s);
    else cerradas.delete(s);
    const t = byId("tMatriz");
    t.querySelectorAll("tr.sub").forEach((r) => {
      if (r.dataset.sub === s) r.classList.toggle("cerrado", cerrar);
    });
    t.querySelectorAll("tr.fila").forEach((r) => {
      if (r.dataset.sub === s) r.classList.toggle("d-none", cerrar);
    });
  }
  byId("btnExpandir").addEventListener("click", () => [...cerradas].forEach((s) => alternarSub(s, false)));
  byId("btnContraer").addEventListener("click", () =>
    byId("tMatriz").querySelectorAll("tr.sub").forEach((r) => alternarSub(r.dataset.sub, true)),
  );

  // Resalta el encabezado del almacén bajo el cursor (lectura de columna)
  let colHl = null;
  byId("tMatriz").addEventListener("mouseover", (e) => {
    const td = e.target.closest("td[data-alm]");
    const c = td ? td.dataset.alm : null;
    if (c === colHl) return;
    const t = byId("tMatriz");
    if (colHl) t.querySelectorAll(`th[data-c="${colHl}"]`).forEach((th) => th.classList.remove("hl"));
    colHl = c;
    if (c) t.querySelectorAll(`th[data-c="${c}"]`).forEach((th) => th.classList.add("hl"));
  });
  byId("tMatriz").addEventListener("mouseleave", () => {
    if (colHl) byId("tMatriz").querySelectorAll(`th[data-c="${colHl}"]`).forEach((th) => th.classList.remove("hl"));
    colHl = null;
  });

  byId("tMatriz").addEventListener("click", (e) => {
    const subRow = e.target.closest("tr.sub");
    if (subRow) {
      alternarSub(subRow.dataset.sub, !cerradas.has(subRow.dataset.sub));
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
      cel ? "Modelo en un almacén" : "Modelo en todos los almacenes",
    );
  });

  /* ---------------- Vista: por almacén ---------------- */
  function pintarAlmacenes(lista) {
    const cont = byId("panelAlmacen");
    if (!lista.length) {
      cont.innerHTML = vacio("No hay unidades con esos filtros", "Prueba quitando algún filtro o cambiando la búsqueda.");
      return;
    }
    const cols = columnasAlmacen(lista);
    const porPlanta = contarPor(lista, (u) => u.planta);
    const almsPlanta = contarPor(cols, (c) => c.planta);
    let h = "";
    let planta = null;
    cols.forEach((c) => {
      if (c.planta !== planta) {
        if (planta !== null) h += "</div>";
        planta = c.planta;
        h += `<div class="iu-planta-tit"><span><i class="ri-map-pin-2-line align-bottom me-1"></i>${esc(nombrePlanta(planta))}</span><span class="n">${plural(porPlanta.get(planta), "unidad", "unidades")} · ${plural(almsPlanta.get(planta), "almacén", "almacenes")}</span></div><div class="row g-3 mb-4">`;
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
      const pct = (delAlm.length * 100) / lista.length;
      h += `<div class="col-md-6 col-xxl-4"><div class="iu-alm">
              <div class="iu-alm-head">
                <div class="r1">
                  <div><div class="cve">${esc(c.cve)}</div><div class="desc">${esc(c.desc)}</div></div>
                  <div class="tot">${fmt(delAlm.length)}<small>${delAlm.length === 1 ? "unidad" : "unidades"}</small></div>
                </div>
                <div class="iu-share" title="Participación sobre el total filtrado"><span class="trk"><span style="width:${pct}%"></span></span>${pct.toFixed(1)}% del total</div>
                <div class="iu-chips">${[...an.keys()].sort().map((a) => `<span class="iu-chip">${esc(a)} <b>${fmt(an.get(a))}</b></span>`).join("")}<span class="iu-chip">${plural(mods.length, "modelo")}</span></div>
              </div>
              <ul class="iu-alm-list">${mods
                .map(
                  (m) => `<li data-alm="${c.id}" data-inv="${m.inv}" data-anio="${esc(m.anio)}" title="Ver VIN">
                    <span class="m">${esc(m.modelo)}<span class="iu-anio">${esc(m.anio)}</span><span class="subl">${esc(m.sub)}</span></span>
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
    abrirDetalle(`${u0.descripcion} · ${anioDe(u0)}`, `${u0.cve_articulo} · ${u0.sublinea} · ${u0.cve_almacen}`, lista, "Modelo en un almacén");
  });

  /* ---------------- Vista: listado de VIN ---------------- */
  function pintarListado(lista) {
    if (!dtListado) {
      dtListado = $("#tListado").DataTable({
        data: lista,
        columns: [
          { data: "numero_serie", render: (d, t) => (t === "display" ? `<span class="iu-vin">${esc(d)}</span>${btnCopiar(d)}` : d) },
          { data: anioDe, render: (d, t) => (t === "display" ? `<span class="iu-anio ms-0">${esc(d)}</span>` : d) },
          { data: colorDe, render: (d, t) => (t === "display" ? `${dot(d)} ${esc(d)}` : d) },
          {
            data: "sublinea",
            render: (d, t) => (t === "display" ? `<span class="iu-sub-tag"><span class="iu-dot" style="background:${colorSub[d]};border:0"></span>${esc(d)}</span>` : d),
          },
          { data: "descripcion", render: (d, t) => (t === "display" ? `<span class="fw-medium">${esc(d)}</span>` : d) },
          { data: "cve_articulo", render: (d, t) => (t === "display" ? `<span class="text-muted">${esc(d)}</span>` : d) },
          { data: "cve_almacen", render: (d, t, r) => (t === "display" ? `<span title="${esc(r.almacen)}">${esc(d)}</span>` : d) },
          { data: "numero_motor", defaultContent: "", render: (d, t) => (t === "display" ? (d ? esc(d) : '<span class="text-muted">—</span>') : d || "") },
          { data: "vin_origen", defaultContent: "", render: (d, t) => (t === "display" ? (d ? `<span class="iu-vin fw-normal">${esc(d)}</span>` : '<span class="text-muted">—</span>') : d || "") },
          { data: "referencia", defaultContent: "", render: (d, t) => (t === "display" ? esc(d) : d) },
        ],
        order: [[4, "asc"], [1, "asc"], [0, "asc"]],
        pageLength: 25,
        lengthMenu: [25, 50, 100, 500],
        dom: "<'table-responsive't><'d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3'<'d-flex align-items-center gap-3'li>p>",
        language: {
          lengthMenu: "Mostrar _MENU_",
          info: "_START_–_END_ de _TOTAL_ unidades",
          infoEmpty: "Sin unidades",
          zeroRecords: "No hay unidades con esos filtros",
          paginate: { previous: "‹", next: "›" },
        },
      });
    } else {
      dtListado.clear().rows.add(lista).draw();
    }
    dtListado.columns.adjust();
  }

  byId("panelListado").addEventListener("click", (e) => {
    const b = e.target.closest("[data-copiar]");
    if (b) copiar(b.dataset.copiar, "VIN copiado");
  });

  /* ---------------- Panel de detalle ---------------- */
  let detalleActual = [];
  let detalleVisible = [];
  function abrirDetalle(titulo, sub, lista, contexto) {
    detalleActual = lista.slice().sort((a, b) => a.numero_serie.localeCompare(b.numero_serie));
    byId("iuDetalleEye").textContent = contexto || "Detalle de unidades";
    byId("iuDetalleTit").textContent = titulo;
    byId("iuDetalleSub").textContent = sub;
    const porAlm = contarPor(lista, (u) => u.cve_almacen);
    const porColor = contarPor(lista, colorDe);
    byId("dUnidades").textContent = fmt(lista.length);
    byId("dAlmacenes").textContent = fmt(porAlm.size);
    byId("dColores").textContent = fmt(porColor.size);
    byId("dPorAlm").innerHTML = [...porAlm.keys()].sort().map((a) => `<span class="iu-chip">${esc(a)} <b>${fmt(porAlm.get(a))}</b></span>`).join("");
    byId("dPorColor").innerHTML = [...porColor.keys()].sort().map((c) => `<span class="iu-chip">${dot(c)} ${esc(c)} <b>${fmt(porColor.get(c))}</b></span>`).join("");
    byId("dBuscar").value = "";
    pintarDetalle();
    bootstrap.Offcanvas.getOrCreateInstance(byId("iuDetalle")).show();
  }

  function pintarDetalle() {
    const q = byId("dBuscar").value.trim().toLowerCase();
    detalleVisible = q
      ? detalleActual.filter((u) => (u.numero_serie + " " + (u.numero_motor || "") + " " + (u.vin_origen || "") + " " + u.cve_almacen + " " + colorDe(u)).toLowerCase().includes(q))
      : detalleActual;
    byId("iuDetalleBody").innerHTML = detalleVisible.length
      ? detalleVisible
          .map(
            (u, i) =>
              `<tr><td class="text-muted">${i + 1}</td><td><span class="iu-vin">${esc(u.numero_serie)}</span>${btnCopiar(u.numero_serie)}</td><td><span class="iu-anio ms-0">${esc(anioDe(u))}</span></td><td>${dot(colorDe(u))} ${esc(colorDe(u))}</td><td title="${esc(u.almacen)}">${esc(u.cve_almacen)}</td><td>${u.numero_motor ? esc(u.numero_motor) : '<span class="text-muted">—</span>'}</td><td>${u.vin_origen ? `<span class="iu-vin fw-normal">${esc(u.vin_origen)}</span>` : '<span class="text-muted">—</span>'}</td></tr>`,
          )
          .join("")
      : `<tr><td colspan="7" class="text-center text-muted py-4">Ningún VIN coincide con “${esc(q)}”</td></tr>`;
    byId("dPie").textContent =
      detalleVisible.length === detalleActual.length
        ? `${plural(detalleActual.length, "VIN")}`
        : `Mostrando ${fmt(detalleVisible.length)} de ${plural(detalleActual.length, "VIN")}`;
  }
  byId("dBuscar").addEventListener("input", pintarDetalle);
  byId("iuDetalleBody").addEventListener("click", (e) => {
    const b = e.target.closest("[data-copiar]");
    if (b) copiar(b.dataset.copiar, "VIN copiado");
  });
  byId("btnDetalleCopiar").addEventListener("click", () => {
    if (!detalleVisible.length) return;
    copiar(detalleVisible.map((u) => u.numero_serie).join("\n"), `${plural(detalleVisible.length, "VIN")} copiados`);
  });

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
      ...detalleVisible.map((u) => [u.numero_serie, anioDe(u), colorDe(u), u.cve_articulo, u.descripcion, u.sublinea, u.cve_almacen, u.numero_motor, u.vin_origen, u.referencia]),
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
    aviso("Generando Excel…");
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
  let vistaPintada = null;
  function render() {
    const lista = filtrados();
    pintarChips();
    pintarActivos();
    pintarKpis(lista);

    // Contadores de cada pestaña y resumen
    byId("nVUnidad").textContent = fmt(new Set(lista.map((u) => u.inventarioid)).size);
    byId("nVAlmacen").textContent = fmt(new Set(lista.map((u) => u.almacenid)).size);
    byId("nVListado").textContent = fmt(lista.length);
    byId("iuResumen").innerHTML =
      lista.length === datos.length
        ? `<b>${fmt(datos.length)}</b> unidades en total`
        : `Mostrando <b>${fmt(lista.length)}</b> de ${fmt(datos.length)} unidades`;

    document.querySelectorAll(".iu-tab").forEach((b) => {
      const on = b.dataset.vista === estado.vista;
      b.classList.toggle("on", on);
      b.setAttribute("aria-selected", on);
    });
    Object.entries(PANELES).forEach(([v, id]) => {
      const p = byId(id);
      const ver = estado.vista === v;
      p.classList.toggle("d-none", !ver);
      // Reinicia la animación de entrada solo al cambiar de vista
      if (ver && vistaPintada !== v) {
        p.style.animation = "none";
        void p.offsetWidth;
        p.style.animation = "";
      }
    });
    vistaPintada = estado.vista;
    byId("wrapPorColor").classList.toggle("d-none", estado.vista !== "unidad");
    if (estado.vista === "unidad") pintarMatriz(lista);
    else if (estado.vista === "almacen") pintarAlmacenes(lista);
    else pintarListado(lista);
  }

  function llenarSelects() {
    const colores = [...new Set(datos.map(colorDe))].sort();
    if (estado.color && !colores.includes(estado.color)) estado.color = "";
    byId("fColor").innerHTML =
      '<option value="">Todos</option>' + colores.map((c) => `<option ${c === estado.color ? "selected" : ""}>${esc(c)}</option>`).join("");
    const plantas = [...new Set(datos.map((u) => u.planta))].sort(cmpPlanta);
    if (estado.planta && !plantas.includes(estado.planta)) estado.planta = "";
    byId("fPlanta").innerHTML =
      '<option value="">Todas</option>' +
      plantas.map((p) => `<option value="${esc(p)}" ${p === estado.planta ? "selected" : ""}>${esc(nombrePlanta(p))}</option>`).join("");
    pintarSelectAlmacen();
  }
  function pintarSelectAlmacen() {
    const alms = columnasAlmacen(datos).filter((a) => !estado.planta || a.planta === estado.planta);
    if (estado.almacen && !alms.some((a) => a.id === estado.almacen)) estado.almacen = "";
    byId("fAlmacen").innerHTML =
      '<option value="">Todos</option>' +
      alms.map((a) => `<option value="${a.id}" ${a.id === estado.almacen ? "selected" : ""}>${esc(a.cve)}</option>`).join("");
  }

  function limpiarFiltros() {
    Object.assign(estado, { q: "", color: "", planta: "", almacen: "" });
    estado.subs.clear();
    estado.anios.clear();
    byId("fBuscar").value = "";
    byId("wrapBuscar").classList.remove("lleno");
    byId("fColor").value = "";
    byId("fPlanta").value = "";
    pintarSelectAlmacen();
    render();
  }

  /* ---------------- Eventos ---------------- */
  document.querySelectorAll(".iu-tab").forEach((b) =>
    b.addEventListener("click", function () {
      if (estado.vista === this.dataset.vista) return;
      estado.vista = this.dataset.vista;
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
    byId("wrapBuscar").classList.toggle("lleno", this.value !== "");
    clearTimeout(tBus);
    tBus = setTimeout(() => {
      estado.q = this.value.trim().toLowerCase();
      render();
    }, 200);
  });
  byId("btnBuscarClr").addEventListener("click", () => {
    byId("fBuscar").value = "";
    byId("wrapBuscar").classList.remove("lleno");
    estado.q = "";
    render();
    byId("fBuscar").focus();
  });
  // Atajos: "/" enfoca la búsqueda, Esc la borra
  document.addEventListener("keydown", (e) => {
    const enCampo = /^(INPUT|TEXTAREA|SELECT)$/.test(document.activeElement.tagName);
    if (e.key === "/" && !enCampo && !document.querySelector(".offcanvas.show, .modal.show")) {
      e.preventDefault();
      byId("fBuscar").focus();
    } else if (e.key === "Escape" && document.activeElement === byId("fBuscar") && byId("fBuscar").value) {
      byId("btnBuscarClr").click();
    }
  });
  const toggleSet = (set, v) => (set.has(v) ? set.delete(v) : set.add(v));
  byId("fSublineas").addEventListener("click", (e) => {
    const b = e.target.closest("[data-sub]");
    if (!b) return;
    toggleSet(estado.subs, b.dataset.sub);
    render();
  });
  byId("kLeyenda").addEventListener("click", (e) => {
    const b = e.target.closest("[data-sub]");
    if (!b) return;
    toggleSet(estado.subs, b.dataset.sub);
    render();
  });
  byId("fAnios").addEventListener("click", (e) => {
    const b = e.target.closest("[data-anio]");
    if (!b) return;
    toggleSet(estado.anios, b.dataset.anio);
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
  // Quitar un filtro desde la barra de activos / botones "Limpiar"
  document.querySelector(".iu-wrap").addEventListener("click", (e) => {
    if (e.target.closest('[data-accion="limpiar"]')) {
      limpiarFiltros();
      return;
    }
    const b = e.target.closest("[data-quitar]");
    if (!b) return;
    const v = b.dataset.valor;
    switch (b.dataset.quitar) {
      case "q":
        byId("fBuscar").value = "";
        byId("wrapBuscar").classList.remove("lleno");
        estado.q = "";
        break;
      case "sub":
        estado.subs.delete(v);
        break;
      case "anio":
        estado.anios.delete(v);
        break;
      case "planta":
        estado.planta = "";
        byId("fPlanta").value = "";
        pintarSelectAlmacen();
        break;
      case "almacen":
        estado.almacen = "";
        byId("fAlmacen").value = "";
        break;
      case "color":
        estado.color = "";
        byId("fColor").value = "";
        break;
    }
    render();
  });

  /* ---------------- Carga ---------------- */
  byId("fPorColor").checked = estado.porColor;

  function cargar() {
    const btn = byId("btnRefrescar");
    const ico = btn.querySelector("i");
    btn.disabled = true;
    ico.classList.add("iu-spin");
    return fetch(base_url + "/Inv_unidades/getDatos")
      .then((r) => r.json())
      .then((d) => {
        datos = d.unidades || [];
        ordenSub = d.orden_sublineas || [];
        [...new Set(datos.map((u) => u.sublinea))].sort(cmpSub).forEach((s, i) => (colorSub[s] = PALETA[i % PALETA.length]));
        if (primeraCarga) {
          datos.forEach((u) => cerradas.add(u.sublinea));
          primeraCarga = false;
        }
        byId("iuEstado").classList.remove("err");
        byId("iuEstado").textContent = `Existencia al ${d.generado}`;
        byId("iuGenerado").textContent = `${plural(datos.length, "VIN")} en inventario`;
        llenarSelects();
        render();
      })
      .catch(() => {
        byId("iuEstado").classList.add("err");
        byId("iuEstado").textContent = "No se pudo cargar el inventario. Intenta con «Actualizar».";
      })
      .finally(() => {
        btn.disabled = false;
        ico.classList.remove("iu-spin");
      });
  }
  byId("btnRefrescar").addEventListener("click", () => cargar().then(() => datos.length && aviso("Existencia actualizada")));
  cargar();
});
