/* =====================================================================
 * Generación de VIN (Inv_series)
 * - El consecutivo (pos. 12-17) es por AÑO (pos. 10), sin importar el modelo:
 *   lo calcula el servidor en getUltimoConsecutivo.
 * - El producto sale del modelo VIN (cat_modelos_vin.id_inventario); en modo
 *   orden, además, solo se ofrecen los modelos del producto de la orden.
 * - vin_base viene como 3LD12B2J-VA ("-" = posición 9, el verificador).
 * ===================================================================== */

let tableSeries;
let ordenesCache = [];
let collapsedGroups = {};

function escapeHtml(str) {
  return String(str ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;",
  })[c]);
}

// Dígito verificador (posición 9)
function calcularDigitoVIN(vin) {
  const t = {
    A: 1, B: 2, C: 3, D: 4, E: 5, F: 6, G: 7, H: 8,
    J: 1, K: 2, L: 3, M: 4, N: 5, P: 7, R: 9,
    S: 2, T: 3, U: 4, V: 5, W: 6, X: 7, Y: 8, Z: 9,
    0: 0, 1: 1, 2: 2, 3: 3, 4: 4, 5: 5, 6: 6, 7: 7, 8: 8, 9: 9,
  };
  const pesos = [8, 7, 6, 5, 4, 3, 2, 10, 0, 9, 8, 7, 6, 5, 4, 3, 2];
  let suma = 0;
  for (let i = 0; i < 17; i++) suma += (t[vin[i]] ?? 0) * pesos[i];
  const r = suma % 11;
  return r === 10 ? "X" : String(r);
}

// base (11 chars con "-" en pos. 9) + consecutivo → VIN de 17 con verificador
function armarVin(base, consecutivo) {
  const tmp = (base + String(consecutivo).padStart(6, "0")).substring(0, 17);
  return tmp.substring(0, 8) + calcularDigitoVIN(tmp) + tmp.substring(9);
}

document.addEventListener("DOMContentLoaded", function () {
  /* ================================================================
   * LISTADO
   * ============================================================== */
  const sinAsociar = '<span class="asoc-vacio">Sin asociar</span>';
  const puedeEscribir = typeof PUEDE_ESCRIBIR_VIN !== "undefined" && PUEDE_ESCRIBIR_VIN;

  // Filtro "Todos / Con asociación / Sin asociar"
  $.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
    if (settings.nTable.id !== "tableSeries") return true;
    const filtro = document.getElementById("filtroAsociacion").value;
    if (!filtro) return true;
    const row = settings.aoData[dataIndex]._aData;
    const asociado = !!row.numero_motor;
    return filtro === "si" ? asociado : !asociado;
  });

  // Etiqueta del tipo de generación: lote / orden de trabajo / carga inicial (Excel de inventario)
  function pillTipo(t, extra = "") {
    if (t === "lote") return `<span class="tipo-pill tipo-lote ${extra}"><i class="ri-stack-line"></i>Lote</span>`;
    if (t === "carga") return `<span class="tipo-pill tipo-carga ${extra}"><i class="ri-upload-2-line"></i>Carga inicial</span>`;
    return `<span class="tipo-pill tipo-orden ${extra}"><i class="ri-file-list-3-line"></i>Orden de trabajo</span>`;
  }

  // Encabezado de grupo: tipo de generación (lote / orden / carga) y modelo(s) VIN del grupo
  function tipoGrupo(rows) {
    const tipos = [...new Set(rows.data().toArray().map((r) => r.tipo_generacion))];
    return tipos.map((t) => pillTipo(t, "ms-2")).join("");
  }

  function modelosGrupo(rows) {
    const vistos = new Map();
    rows.data().toArray().forEach((r) => {
      const clave = r.modelo_vin ? r.modelo_vin + "|" + (r.vin_base || "") : "";
      if (!vistos.has(clave)) vistos.set(clave, r);
    });
    return [...vistos.entries()]
      .map(([clave, r]) =>
        clave
          ? `<span class="ms-3 fs-13"><i class="ri-car-line align-bottom text-muted me-1"></i><strong>Modelo:</strong> ${escapeHtml(r.modelo_vin)}
             ${r.vin_base ? `<span class="mono text-muted ms-1">(${escapeHtml(r.vin_base)})</span>` : ""}</span>`
          : `<span class="ms-3 fs-13 text-muted fst-italic" title="VIN generado antes de ligar el modelo del glosario"><i class="ri-car-line align-bottom me-1"></i>Modelo VIN no identificado</span>`,
      )
      .join("");
  }

  tableSeries = $("#tableSeries").DataTable({
    ajax: {
      url: base_url + "/Inv_series/getSeries",
      dataSrc: function (json) {
        const data = Array.isArray(json) ? json : [];
        const asociados = data.filter((r) => r.numero_motor).length;
        document.getElementById("kpiAsociados").innerHTML =
          `<i class="ri-links-line align-bottom me-1"></i> ${asociados} asociados`;
        document.getElementById("kpiPendientes").innerHTML =
          `<i class="ri-error-warning-line align-bottom me-1"></i> ${data.length - asociados} sin asociar`;
        return data;
      },
    },
    columns: [
      // responsivePriority: menor número = se queda visible; el resto pasa al "+" cuando no cabe
      { data: "producto", responsivePriority: 5 },
      { data: "almacen", responsivePriority: 8 },
      { data: "numero_serie", responsivePriority: 1, render: (d) => `<span class="mono fw-semibold">${escapeHtml(d)}</span>` },
      { data: "numero_motor", responsivePriority: 3, defaultContent: "", render: (d) => (d ? `<span class="mono">${escapeHtml(d)}</span>` : sinAsociar) },
      { data: "vin_origen", responsivePriority: 4, defaultContent: "", render: (d) => (d ? `<span class="mono">${escapeHtml(d)}</span>` : sinAsociar) },
      { data: "referencia", responsivePriority: 6 },
      { data: "fecha", responsivePriority: 9 },
      { data: "estado", responsivePriority: 7 },
      {
        data: "numero_serie",
        responsivePriority: 2,
        orderable: false,
        render: function (data, type, row) {
          const asociar = puedeEscribir
            ? `<button type="button" class="btn btn-sm btn-soft-${row.numero_motor ? "secondary" : "success"} btn-asociar" data-vin="${escapeHtml(data)}" title="${row.numero_motor ? "Editar asociación" : "Asociar motor / VIN de origen"}">
                <i class="${row.numero_motor ? "ri-pencil-line" : "ri-links-line"} align-bottom"></i> ${row.numero_motor ? "Editar" : "Asociar"}
              </button>`
            : "";
          return `
            ${asociar}
            <button type="button" class="btn btn-sm btn-soft-dark btn-historial" data-id="${row.id_numeros_serie}" data-vin="${escapeHtml(data)}" title="Historial">
              <i class="ri-history-line align-bottom"></i>
            </button>
            <a href="${base_url}/Inv_series/generarCodigoPDF/${data}" target="_blank" class="btn btn-sm btn-soft-primary" title="Código de barras">
              <i class="ri-barcode-line align-bottom"></i> Código
            </a>
            <a href="${base_url}/Inv_series/generarQRPDF/${data}" target="_blank" class="btn btn-sm btn-soft-success" title="Código QR">
              <i class="ri-qr-code-line align-bottom"></i> QR
            </a>`;
        },
      },
    ],
    responsive: true,
    bDestroy: true,
    paging: false,
    order: [[6, "desc"]],
    rowGroup: {
      dataSrc: "referencia",
      startRender: function (rows, group) {
        if (collapsedGroups[group] === undefined) collapsedGroups[group] = true; // inicia colapsado
        // con un filtro de asociación activo se muestran los grupos abiertos
        const collapsed = document.getElementById("filtroAsociacion").value ? false : collapsedGroups[group];
        rows.nodes().each((r) => (r.style.display = collapsed ? "none" : ""));

        return $(`
          <tr class="group-row" data-name="${escapeHtml(group)}">
            <td colspan="9">
              <i class="${collapsed ? "ri-arrow-right-s-line" : "ri-arrow-down-s-line"} align-middle fs-16 me-1"></i>
              <strong>Referencia:</strong> ${escapeHtml(group)}
              ${tipoGrupo(rows)}
              <span class="badge bg-light text-body border ms-1">${rows.count()} VIN</span>
              ${modelosGrupo(rows)}
              <button class="btn btn-sm btn-soft-danger float-end btn-pdf-group" data-ref="${escapeHtml(group)}">
                <i class="ri-file-pdf-2-line align-bottom"></i> Descargar PDF
              </button>
            </td>
          </tr>`);
      },
    },
  });

  // Los grupos inician colapsados: DataTables mide el ancho sin filas visibles y
  // cree que todo cabe. Se recalcula el modo responsivo en cada dibujado para que
  // las columnas que no caben siempre pasen al "+" (antes solo pasaba tras recargar).
  let recalculandoResponsive = false;
  tableSeries.on("draw", function () {
    if (recalculandoResponsive) return;
    recalculandoResponsive = true;
    setTimeout(() => {
      tableSeries.columns.adjust().responsive.recalc();
      recalculandoResponsive = false;
    }, 0);
  });

  $("#tableSeries tbody").on("click", "tr.group-row", function () {
    if (document.getElementById("filtroAsociacion").value) return; // abiertos mientras hay filtro
    const group = $(this).data("name");
    collapsedGroups[group] = !collapsedGroups[group];
    tableSeries.draw(false);
  });

  document.getElementById("filtroAsociacion").addEventListener("change", () => tableSeries.draw());

  $("#tableSeries tbody").on("click", ".btn-pdf-group", function (e) {
    e.stopPropagation();
    window.open(base_url + "/Inv_series/generarOrdenPDF/" + $(this).data("ref"), "_blank");
  });

  /* ================================================================
   * NAVEGACIÓN listado ⇄ generar (botón principal del encabezado)
   * ============================================================== */
  function mostrarSeccion(generar) {
    document.getElementById("listSeries").classList.toggle("active", !generar);
    document.getElementById("listSeries").classList.toggle("show", !generar);
    document.getElementById("agregarSerie").classList.toggle("active", generar);
    document.getElementById("agregarSerie").classList.toggle("show", generar);

    const irGenerar = document.getElementById("btnIrGenerar");
    const irListado = document.getElementById("btnIrListado");
    const accAsoc = document.getElementById("accionesAsociacion");
    if (irGenerar) irGenerar.classList.toggle("d-none", generar);
    if (irListado) irListado.classList.toggle("d-none", !generar);
    if (accAsoc) accAsoc.classList.toggle("d-none", generar);

    document.getElementById("subtituloVin").textContent = generar
      ? "Nuevo lote de VIN · consecutivo por año de fabricación (posición 10)"
      : "VINes generados, agrupados por orden o lote";

    if (!generar) tableSeries.columns.adjust().responsive.recalc();
    window.scrollTo({ top: 0, behavior: "smooth" });
  }

  const btnIrGenerar = document.getElementById("btnIrGenerar");
  const btnIrListado = document.getElementById("btnIrListado");
  if (btnIrGenerar) btnIrGenerar.addEventListener("click", () => mostrarSeccion(true));
  if (btnIrListado) btnIrListado.addEventListener("click", () => mostrarSeccion(false));

  /* ================================================================
   * GENERAR
   * ============================================================== */
  const byId = (id) => document.getElementById(id);

  let modelosVinCache = [];
  let vinsPreview = [];
  const estado = { modo: "orden", base: "", ultimo: null, token: 0 };

  const modo = () => estado.modo;
  const campo = (orden, lote) => (modo() === "orden" ? byId(orden) : byId(lote));

  /* ---------- catálogos ---------- */

  function crearOptionModelo(m) {
    const opt = document.createElement("option");
    opt.value = m.id_cat_modelo_vin;
    opt.dataset.base = m.vin_base;
    opt.dataset.anio = m.codigo_anio;
    opt.dataset.planta = m.caracter_planta;
    opt.dataset.inventario = m.id_inventario || "";
    opt.dataset.modelo = m.modelo;
    opt.textContent = `${m.modelo} (${m.anio})`;
    if (!m.id_inventario) {
      opt.disabled = true;
      opt.textContent += " - sin producto ligado";
    }
    return opt;
  }

  fetch(base_url + "/Inv_captura_vin/getModelosVin")
    .then((res) => res.json())
    .then((res) => {
      if (!res.success) return;
      modelosVinCache = res.data.filter((m) => m.estado == 2 || m.estado === undefined);
      const sel = byId("modelo_vin_lote");
      modelosVinCache.forEach((m) => sel.appendChild(crearOptionModelo(m)));
    });

  fetch(base_url + "/Inv_series/getOrdenesTrabajo")
    .then((res) => res.json())
    .then((data) => (ordenesCache = Array.isArray(data) ? data : []));

  fetch(base_url + "/Inv_series/getAlmacenes")
    .then((res) => res.json())
    .then((data) => {
      ["almacenid", "almacenid_lote"].forEach((id) => {
        const sel = byId(id);
        sel.innerHTML = '<option value="">Seleccione almacén</option>';
        data.forEach((a) => sel.appendChild(new Option(`${a.cve_almacen} - ${a.descripcion}`, a.idalmacen)));
      });
    });

  /* ---------- modo ---------- */

  document.querySelectorAll('input[name="modoRadio"]').forEach((r) =>
    r.addEventListener("change", function () {
      if (!this.checked) return;
      estado.modo = this.value;
      byId("modoGeneracion").value = this.value;
      byId("bloqueOrden").style.display = this.value === "orden" ? "" : "none";
      byId("bloqueLote").style.display = this.value === "lote" ? "" : "none";
      byId("resumenModo").textContent = this.value === "orden" ? "Orden de trabajo" : "Lote";
      // tomar el modelo que tenga el modo activo
      campo("modelo_vin", "modelo_vin_lote").dispatchEvent(new Event("change"));
    }),
  );

  /* ---------- modelo VIN ---------- */

  function aplicarModelo(opt, chipId, hiddenId) {
    const chip = byId(chipId);
    if (!opt || !opt.value) {
      byId(hiddenId).value = "";
      chip.className = "vin-chip is-empty";
      chip.innerHTML = "<span>-----------</span>";
      setBase("", "", "");
      return;
    }
    const b = opt.dataset.base;
    byId(hiddenId).value = b;
    chip.className = "vin-chip";
    chip.innerHTML = `<span class="seg-wmi">${escapeHtml(b.substring(0, 3))}</span><span class="seg-vds">${escapeHtml(b.substring(3, 8))}</span><span class="seg-chk">?</span><span class="seg-vis">${escapeHtml(b.substring(9, 11))}</span>`;
    setBase(b, opt.dataset.anio, opt.dataset.planta);
  }

  function setBase(base, anio, planta) {
    byId("vin_parte_1_8").value = base;
    byId("vin_anio").value = anio;
    byId("vin_planta").value = planta;
    estado.base = base;
    estado.ultimo = null;
    actualizarResumen();
    if (base) consultarUltimo();
  }

  // Último consecutivo del año (pos. 10) desde el servidor
  function consultarUltimo() {
    const token = ++estado.token;
    byId("resUltimo").innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    return fetch(base_url + "/Inv_series/getUltimoConsecutivo?baseVin=" + encodeURIComponent(estado.base))
      .then((res) => res.json())
      .then((data) => {
        if (token !== estado.token) return estado.ultimo; // respuesta vieja
        estado.ultimo = data.status ? parseInt(data.ultimo, 10) || 0 : 0;
        actualizarResumen();
        return estado.ultimo;
      })
      .catch(() => {
        if (token === estado.token) {
          estado.ultimo = null;
          byId("resUltimo").textContent = "No disponible";
        }
        return null;
      });
  }

  byId("modelo_vin_lote").addEventListener("change", function () {
    const opt = this.selectedOptions[0];
    byId("inventarioid_lote").value = opt && opt.value ? opt.dataset.inventario : "";
    if (modo() === "lote") aplicarModelo(opt, "vinBaseChip_lote", "vinBasePreview_lote");
  });

  byId("modelo_vin").addEventListener("change", function () {
    if (modo() === "orden") aplicarModelo(this.selectedOptions[0], "vinBaseChip", "vinBasePreview");
  });

  // Modo ORDEN: solo modelos VIN del producto de la orden (bloqueado si hay uno)
  function llenarModelosOrden(idinventario) {
    const select = byId("modelo_vin");
    const help = byId("modeloVinOrdenHelp");
    select.innerHTML = "";
    select.disabled = false;
    help.className = "text-muted";
    help.textContent = "Solo se muestran los modelos VIN del producto de la orden";

    if (!idinventario) {
      select.innerHTML = '<option value="">Seleccione una orden primero</option>';
      select.dispatchEvent(new Event("change"));
      return;
    }

    const modelos = modelosVinCache.filter((m) => String(m.id_inventario) === String(idinventario));

    if (modelos.length === 0) {
      select.innerHTML = '<option value="">Sin modelo VIN configurado</option>';
      help.className = "text-danger";
      help.textContent = "El producto de esta orden no tiene modelo VIN. Capture uno en Glosario VIN.";
      select.dispatchEvent(new Event("change"));
      return;
    }

    select.appendChild(new Option("Seleccione modelo VIN", ""));
    modelos.forEach((m) => select.appendChild(crearOptionModelo(m)));

    if (modelos.length === 1) {
      select.value = modelos[0].id_cat_modelo_vin;
      select.disabled = true;
      help.textContent = "Único modelo VIN configurado para el producto de la orden";
    }
    select.dispatchEvent(new Event("change"));
  }

  /* ---------- autocomplete de orden ---------- */

  function cerrarListaOrden() {
    document.querySelectorAll(".autocomplete-items").forEach((el) => el.remove());
  }

  function limpiarOrden() {
    byId("inventarioid").value = "";
    byId("referencia").value = "";
    byId("productoNombre").value = "";
    byId("cantidadOrden").value = "";
    byId("cantidadPreview").value = "";
    llenarModelosOrden(null);
  }

  byId("ordenSearch").addEventListener("input", function () {
    const val = this.value.trim().toLowerCase();
    cerrarListaOrden();
    limpiarOrden();
    if (!val) return;

    const lista = document.createElement("div");
    lista.className = "autocomplete-items list-group position-absolute w-100";
    this.closest(".position-relative").appendChild(lista);

    const coincidencias = ordenesCache.filter((o) => String(o.num_orden).toLowerCase().includes(val)).slice(0, 10);

    if (coincidencias.length === 0) {
      lista.innerHTML = '<div class="list-group-item text-muted fs-13">Sin coincidencias</div>';
      return;
    }

    coincidencias.forEach((o) => {
      const item = document.createElement("button");
      item.type = "button";
      item.className = "list-group-item list-group-item-action";
      item.innerHTML = `<div class="fw-semibold">${escapeHtml(o.num_orden)}</div>
        <div class="text-muted fs-12">${escapeHtml(o.producto)} · ${escapeHtml(o.cantidad)} pzas</div>`;

      item.addEventListener("click", function () {
        byId("ordenSearch").value = o.num_orden;
        byId("referencia").value = o.num_orden;
        byId("inventarioid").value = o.idinventario;
        byId("productoNombre").value = o.producto;
        byId("cantidadOrden").value = o.cantidad;
        byId("cantidadPreview").value = o.cantidad;
        cerrarListaOrden();
        llenarModelosOrden(o.idinventario);
      });
      lista.appendChild(item);
    });
  });

  document.addEventListener("click", function (e) {
    if (!e.target.closest(".autocomplete-items") && e.target.id !== "ordenSearch") cerrarListaOrden();
  });

  /* ---------- resumen ---------- */

  ["almacenid", "almacenid_lote", "cantidad_lote", "lote"].forEach((id) => {
    byId(id).addEventListener("input", actualizarResumen);
    byId(id).addEventListener("change", actualizarResumen);
  });

  function datosActuales() {
    const esOrden = modo() === "orden";
    const almSel = campo("almacenid", "almacenid_lote");
    const modSel = campo("modelo_vin", "modelo_vin_lote");
    const modOpt = modSel.selectedOptions[0];
    return {
      inventarioid: esOrden ? byId("inventarioid").value : byId("inventarioid_lote").value,
      producto: esOrden ? byId("productoNombre").value : modOpt && modOpt.value ? modOpt.dataset.modelo : "",
      referencia: (esOrden ? byId("referencia").value : byId("lote").value).trim(),
      almacenid: almSel.value,
      almacen: almSel.value ? almSel.selectedOptions[0].textContent : "",
      cantidad: parseInt(esOrden ? byId("cantidadOrden").value : byId("cantidad_lote").value, 10) || 0,
    };
  }

  function renderAnatomia(vin) {
    const seg = (i) => (i <= 3 ? "wmi" : i <= 8 ? "vds" : i === 9 ? "chk" : i <= 11 ? "vis" : "num");
    let html = "";
    for (let i = 1; i <= 17; i++) {
      if (i === 4 || i === 10 || i === 12) html += '<span class="niv-gap"></span>';
      const ch = vin ? vin[i - 1] : "";
      html += `<div class="niv-cell seg-${seg(i)}${ch ? "" : " is-empty"}"><span class="ch">${escapeHtml(ch)}</span><span class="ix">${i}</span></div>`;
    }
    byId("vinAnatomy").innerHTML = html;
  }

  function actualizarResumen() {
    const d = datosActuales();
    const listo = estado.base && estado.ultimo !== null;
    const primero = listo ? armarVin(estado.base, estado.ultimo + 1) : "";

    renderAnatomia(primero);
    byId("vinPreviewFinal").textContent = primero || "-----------------";
    byId("vinPreviewNota").textContent = !estado.base
      ? "Seleccione un modelo VIN"
      : listo
        ? `Consecutivo ${String(estado.ultimo + 1).padStart(6, "0")} del año "${byId("vin_anio").value}"`
        : "Consultando consecutivo...";

    byId("resProducto").textContent = d.producto || "—";
    byId("resReferencia").textContent = d.referencia || "—";
    byId("resAlmacen").textContent = d.almacen || "—";
    if (estado.ultimo !== null) byId("resUltimo").textContent = String(estado.ultimo).padStart(6, "0");
    else if (!estado.base) byId("resUltimo").textContent = "—";
    byId("resCantidad").textContent = d.cantidad ? `${d.cantidad} VIN` : "—";
    byId("resRango").innerHTML =
      listo && d.cantidad
        ? `${escapeHtml(primero)}<br>${escapeHtml(armarVin(estado.base, estado.ultimo + d.cantidad))}`
        : "—";

    byId("btnPreview").disabled = !(listo && d.inventarioid && d.referencia && d.almacenid && d.cantidad > 0);
  }

  /* ---------- previsualizar ---------- */

  byId("btnPreview").addEventListener("click", function () {
    const d = datosActuales();
    const faltan = [];
    if (!d.referencia) faltan.push(modo() === "orden" ? "orden de trabajo" : "lote");
    if (!estado.base) faltan.push("modelo VIN");
    if (!d.inventarioid) faltan.push("producto ligado al modelo VIN");
    if (!d.almacenid) faltan.push("almacén");
    if (!(d.cantidad > 0)) faltan.push("cantidad");
    if (faltan.length) {
      Swal.fire("Faltan datos", "Complete: " + faltan.join(", "), "warning");
      return;
    }

    const btn = this;
    const txt = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Calculando...';

    // se vuelve a consultar el consecutivo para tener el dato al momento
    consultarUltimo()
      .then((ultimo) => {
        if (ultimo === null) throw new Error();
        vinsPreview = [];
        for (let i = 1; i <= d.cantidad; i++) vinsPreview.push(armarVin(estado.base, ultimo + i));
        renderModal(d);
        new bootstrap.Modal(byId("modalPreviewSeries")).show();
      })
      .catch(() => Swal.fire("Error", "No se pudo obtener el consecutivo", "error"))
      .finally(() => {
        btn.innerHTML = txt;
        actualizarResumen();
      });
  });

  function renderModal(d) {
    const tarjeta = (icono, lbl, val, mono = false) => `
      <div class="col-sm-6 col-lg-3">
        <div class="border rounded p-2 h-100">
          <div class="text-muted fs-11 text-uppercase"><i class="${icono} align-bottom me-1"></i>${lbl}</div>
          <div class="fw-semibold text-truncate ${mono ? "mono" : ""}" title="${escapeHtml(val)}">${escapeHtml(val)}</div>
        </div>
      </div>`;

    byId("modalResumen").innerHTML =
      tarjeta(modo() === "orden" ? "ri-file-list-3-line" : "ri-stack-line", modo() === "orden" ? "Orden de trabajo" : "Lote", d.referencia) +
      tarjeta("ri-car-line", "Producto", d.producto) +
      tarjeta("ri-store-2-line", "Almacén", d.almacen) +
      tarjeta("ri-hashtag", "Rango", `${vinsPreview[0].substring(11)} – ${vinsPreview[vinsPreview.length - 1].substring(11)}`, true);

    byId("modalTotal").textContent = vinsPreview.length;
    byId("previewContainer").innerHTML = vinsPreview
      .map(
        (v, i) => `<div class="vin-item"><span class="n">${i + 1}</span>
          <span>${escapeHtml(v.substring(0, 8))}<span class="d">${escapeHtml(v[8])}</span>${escapeHtml(v.substring(9, 11))}<span class="c">${escapeHtml(v.substring(11))}</span></span></div>`,
      )
      .join("");
  }

  byId("btnCopiarVins").addEventListener("click", function () {
    navigator.clipboard
      .writeText(vinsPreview.join("\n"))
      .then(() => {
        this.innerHTML = '<i class="ri-check-line align-bottom"></i> Copiado';
        setTimeout(() => (this.innerHTML = '<i class="ri-file-copy-line align-bottom"></i> Copiar'), 1500);
      })
      .catch(() => {});
  });

  /* ---------- confirmar ---------- */

  byId("btnConfirmSeries").addEventListener("click", function () {
    if (vinsPreview.length === 0) {
      Swal.fire("Error", "No hay VIN para guardar", "error");
      return;
    }
    const d = datosActuales();
    const btn = this;
    const txt = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Registrando...';

    fetch(base_url + "/Inv_series/setSeriesConfirmadas", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        lista: vinsPreview,
        inventarioid: d.inventarioid,
        almacenid: d.almacenid,
        referencia: d.referencia,
        costo: 0,
        modo: modo(),
        id_cat_modelo_vin: campo("modelo_vin", "modelo_vin_lote").value || null,
      }),
    })
      .then((res) => res.json())
      .then((data) => {
        if (data.status) {
          bootstrap.Modal.getInstance(byId("modalPreviewSeries")).hide();
          Swal.fire({ icon: "success", title: "VIN registrados", text: data.msg });
          limpiarTodo();
          tableSeries.ajax.reload(null, false);
          mostrarSeccion(false);
        } else {
          Swal.fire("Error", data.msg, "error");
        }
      })
      .catch(() => Swal.fire("Error", "Error en el servidor", "error"))
      .finally(() => {
        btn.disabled = false;
        btn.innerHTML = txt;
      });
  });

  function limpiarTodo() {
    vinsPreview = [];
    byId("previewContainer").innerHTML = "";
    byId("formSeries").reset();
    byId("ordenSearch").value = "";
    limpiarOrden();
    byId("inventarioid_lote").value = "";
    byId("modelo_vin_lote").value = "";
    byId("modelo_vin_lote").dispatchEvent(new Event("change"));
    campo("modelo_vin", "modelo_vin_lote").dispatchEvent(new Event("change"));
    actualizarResumen();
  }

  actualizarResumen();

  /* ================================================================
   * ASOCIACIÓN: número de motor + VIN de origen
   * Flujo con lector: VIN [Enter] → motor [Enter] → VIN origen [Enter] = guardar
   * ============================================================== */
  const asoc = {
    modal: byId("modalAsociarVin") ? new bootstrap.Modal(byId("modalAsociarVin")) : null,
    vin: byId("asoc_vin"),
    id: byId("asoc_id_serie"),
    motor: byId("asoc_motor"),
    origen: byId("asoc_origen"),
    info: byId("asocInfoVin"),
    guardar: byId("btnGuardarAsociacion"),
    continuar: byId("asocContinuar"),
    token: 0,
  };

  function vinOrigenError(v) {
    if (!v) return "";
    if (/[IOQ]/.test(v)) return "No puede contener las letras I, O o Q.";
    if (!/^[A-Z0-9]*$/.test(v)) return "Solo letras y números.";
    if (v.length !== 17) return `Debe tener 17 caracteres (lleva ${v.length}).`;
    return "";
  }

  function asocHabilitar(on) {
    asoc.motor.disabled = !on;
    asoc.origen.disabled = !on;
    asocValidar();
  }

  function asocValidar() {
    const v = asoc.origen.value.trim().toUpperCase();
    const err = vinOrigenError(v);
    byId("asocOrigenContador").textContent = `${v.length}/17`;
    byId("asocOrigenContador").className = "fs-12 mono " + (v.length === 17 && !err ? "text-success" : "text-muted");
    asoc.origen.classList.toggle("is-invalid", !!err && v.length > 0 && (v.length >= 17 || /[IOQ]/.test(v)));
    byId("asocOrigenError").textContent = err;
    asoc.guardar.disabled = !(asoc.id.value && asoc.motor.value.trim() && v.length === 17 && !err);
  }

  function asocLimpiar(mantenerVinBloqueado) {
    asoc.id.value = "";
    if (!mantenerVinBloqueado) {
      asoc.vin.value = "";
      asoc.vin.readOnly = false;
    }
    asoc.motor.value = "";
    asoc.origen.value = "";
    asoc.info.innerHTML = "";
    asoc.origen.classList.remove("is-invalid");
    asocHabilitar(false);
  }

  function asocBuscar() {
    const vin = asoc.vin.value.trim().toUpperCase();
    asoc.vin.value = vin;
    asoc.id.value = "";
    asocHabilitar(false);
    if (!vin) return;

    const token = ++asoc.token;
    asoc.info.innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span>';
    fetch(base_url + "/Inv_series/getAsociacion?vin=" + encodeURIComponent(vin))
      .then((res) => res.json())
      .then((res) => {
        if (token !== asoc.token) return;
        if (!res.status) {
          asoc.info.innerHTML = `<div class="alert alert-danger py-2 px-3 mb-0 fs-13"><i class="ri-close-circle-line align-bottom me-1"></i>${escapeHtml(res.msg)}</div>`;
          asoc.vin.select();
          return;
        }
        const d = res.data;
        asoc.id.value = d.id_numeros_serie;
        asoc.motor.value = d.numero_motor || "";
        asoc.origen.value = d.vin_origen || "";
        const estadoAsoc = d.id_asociacion
          ? `<span class="badge bg-success-subtle text-success">Ya asociado · se editará</span>`
          : `<span class="badge bg-warning-subtle text-warning">Sin asociar</span>`;
        asoc.info.innerHTML = `
          <div class="border rounded px-3 py-2 fs-13 d-flex flex-wrap gap-3 align-items-center">
            <span><span class="text-muted">Producto:</span> <strong>${escapeHtml(d.producto)}</strong></span>
            <span><span class="text-muted">Referencia:</span> ${escapeHtml(d.referencia)}</span>
            <span class="ms-auto">${estadoAsoc}</span>
          </div>`;
        asocHabilitar(true);
        asoc.motor.focus();
        asoc.motor.select();
      })
      .catch(() => {
        if (token === asoc.token) asoc.info.innerHTML = '<div class="text-danger fs-13">Error al consultar el VIN.</div>';
      });
  }

  function abrirAsociar(vin) {
    if (!asoc.modal) return;
    asocLimpiar(false);
    asoc.continuar.checked = !vin; // desde el encabezado: modo lector continuo
    if (vin) {
      asoc.vin.value = vin;
      asoc.vin.readOnly = true;
    }
    asoc.modal.show();
    if (vin) asocBuscar();
  }

  if (asoc.modal) {
    byId("modalAsociarVin").addEventListener("shown.bs.modal", () => {
      if (!asoc.vin.readOnly) asoc.vin.focus();
    });

    byId("btnAsociarLector").addEventListener("click", () => abrirAsociar(null));
    $("#tableSeries tbody").on("click", ".btn-asociar", function (e) {
      e.stopPropagation();
      abrirAsociar(this.dataset.vin);
    });

    asoc.vin.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); asocBuscar(); }
    });
    asoc.vin.addEventListener("input", () => {
      asoc.id.value = "";
      asoc.info.innerHTML = "";
      asocHabilitar(false);
    });
    byId("btnBuscarAsocVin").addEventListener("click", asocBuscar);

    asoc.motor.addEventListener("input", function () {
      this.value = this.value.toUpperCase();
      asocValidar();
    });
    asoc.motor.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); asoc.origen.focus(); asoc.origen.select(); }
    });
    asoc.origen.addEventListener("input", function () {
      this.value = this.value.toUpperCase().replace(/\s+/g, "");
      asocValidar();
    });
    asoc.origen.addEventListener("keydown", (e) => {
      if (e.key === "Enter") { e.preventDefault(); if (!asoc.guardar.disabled) asoc.guardar.click(); }
    });

    asoc.guardar.addEventListener("click", function () {
      asocValidar();
      if (this.disabled) return;
      const fd = new FormData();
      fd.append("id_numeros_serie", asoc.id.value);
      fd.append("numero_motor", asoc.motor.value);
      fd.append("vin_origen", asoc.origen.value);

      const btn = this;
      const txt = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Guardando...';

      fetch(base_url + "/Inv_series/setAsociacion", { method: "POST", body: fd })
        .then((res) => res.json())
        .then((res) => {
          if (!res.status) {
            Swal.fire("No se guardó", res.msg, "error");
            return;
          }
          tableSeries.ajax.reload(null, false);
          Swal.fire({
            toast: true, position: "top-end", timer: 2200, showConfirmButton: false,
            icon: res.tipo === "sin_cambios" ? "info" : "success",
            title: `${asoc.vin.value}: ${res.msg}`,
          });
          if (asoc.continuar.checked) {
            asocLimpiar(false);
            asoc.vin.focus();
          } else {
            asoc.modal.hide();
          }
        })
        .catch(() => Swal.fire("Error", "Error en el servidor", "error"))
        .finally(() => {
          btn.innerHTML = txt;
          asocValidar();
        });
    });
  }

  /* ---------- carga masiva ---------- */

  if (byId("modalCargaAsociacion")) {
    const modalCarga = new bootstrap.Modal(byId("modalCargaAsociacion"));

    /* --- paso 1: elegir lotes / órdenes para la plantilla --- */
    let refsCache = [];
    const refsSel = new Set();

    function filtroTipoRef() {
      const r = document.querySelector('input[name="filtroTipoRef"]:checked');
      return r ? r.value : "";
    }

    function renderRefs() {
      const cont = byId("listaRefsAsoc");
      const q = byId("buscarRefAsoc").value.trim().toLowerCase();
      const tipo = filtroTipoRef();
      const lista = refsCache.filter(
        (r) =>
          (!tipo || r.tipo_generacion === tipo) &&
          (!q || [r.referencia, r.modelo_vin, r.producto].some((v) => String(v ?? "").toLowerCase().includes(q))),
      );
      if (!lista.length) {
        cont.innerHTML = `<div class="text-center text-muted py-4 fs-13"><i class="ri-inbox-line fs-20 d-block mb-1"></i>${refsCache.length ? "Sin coincidencias" : "No hay VIN generados"}</div>`;
        return;
      }
      cont.innerHTML = lista
        .map((r) => {
          const total = parseInt(r.total, 10) || 0;
          const asoc = parseInt(r.asociados, 10) || 0;
          const pct = total ? Math.round((asoc * 100) / total) : 0;
          const completo = total > 0 && asoc === total;
          const tipoPill = pillTipo(r.tipo_generacion);
          const marcado = refsSel.has(r.referencia);
          return `<label class="ref-item ${marcado ? "is-checked" : ""}">
              <input type="checkbox" class="form-check-input ref-check" value="${escapeHtml(r.referencia)}" ${marcado ? "checked" : ""}>
              <div class="ref-main">
                <div class="d-flex align-items-center gap-2"><span class="mono fw-semibold">${escapeHtml(r.referencia)}</span>${tipoPill}</div>
                <div class="ref-modelo" title="${escapeHtml(r.modelo_vin || r.producto)}"><i class="ri-car-line align-bottom me-1"></i>${escapeHtml(r.modelo_vin || r.producto)}</div>
              </div>
              <div class="ref-avance">
                <span class="${completo ? "text-success fw-semibold" : ""}">${asoc}/${total} asociados</span>
                <div class="progress"><div class="progress-bar ${completo ? "bg-success" : ""}" style="width:${pct}%"></div></div>
              </div>
            </label>`;
        })
        .join("");
    }

    function actualizarResumenRefs() {
      const soloPend = byId("soloPendientesRef").checked;
      let vins = 0;
      refsCache.forEach((r) => {
        if (refsSel.has(r.referencia)) vins += soloPend ? r.total - r.asociados : parseInt(r.total, 10);
      });
      byId("resumenRefsAsoc").textContent = refsSel.size
        ? `${refsSel.size} seleccionado${refsSel.size > 1 ? "s" : ""} · ${vins} VIN en la plantilla`
        : "Ninguno seleccionado";
      byId("btnDescargarRefs").disabled = !refsSel.size || vins === 0;
    }

    function cargarRefs() {
      byId("listaRefsAsoc").innerHTML = '<div class="text-center text-muted py-4 fs-13"><span class="spinner-border spinner-border-sm me-1"></span> Cargando...</div>';
      fetch(base_url + "/Inv_series/getReferenciasAsociacion")
        .then((res) => res.json())
        .then((data) => {
          refsCache = (Array.isArray(data) ? data : []).map((r) => ({
            ...r,
            total: parseInt(r.total, 10) || 0,
            asociados: parseInt(r.asociados, 10) || 0,
          }));
          renderRefs();
          actualizarResumenRefs();
        })
        .catch(() => (byId("listaRefsAsoc").innerHTML = '<div class="text-danger text-center py-4 fs-13">No se pudo cargar la lista.</div>'));
    }

    byId("listaRefsAsoc").addEventListener("change", function (e) {
      if (!e.target.classList.contains("ref-check")) return;
      if (e.target.checked) refsSel.add(e.target.value);
      else refsSel.delete(e.target.value);
      e.target.closest(".ref-item").classList.toggle("is-checked", e.target.checked);
      actualizarResumenRefs();
    });
    byId("buscarRefAsoc").addEventListener("input", renderRefs);
    document.querySelectorAll('input[name="filtroTipoRef"]').forEach((r) => r.addEventListener("change", renderRefs));
    byId("soloPendientesRef").addEventListener("change", actualizarResumenRefs);

    byId("btnDescargarRefs").addEventListener("click", function () {
      const params = new URLSearchParams();
      refsSel.forEach((r) => params.append("refs[]", r));
      if (byId("soloPendientesRef").checked) params.append("pendientes", "1");
      window.location.href = base_url + "/Inv_series/descargarPlantillaAsociacion?" + params.toString();
    });

    byId("btnCargaAsociacion").addEventListener("click", () => {
      byId("formCargaAsociacion").reset();
      byId("resultadoCargaAsociacion").innerHTML = "";
      byId("buscarRefAsoc").value = "";
      byId("refTipoTodos").checked = true;
      refsSel.clear();
      cargarRefs();
      modalCarga.show();
    });

    byId("btnProcesarAsociacion").addEventListener("click", function () {
      const archivo = byId("archivoAsociacion").files[0];
      if (!archivo) {
        Swal.fire("Falta el archivo", "Seleccione el Excel a procesar.", "warning");
        return;
      }
      const fd = new FormData();
      fd.append("archivo", archivo);

      const btn = this;
      const txt = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Procesando...';
      byId("resultadoCargaAsociacion").innerHTML = "";

      fetch(base_url + "/Inv_series/procesarAsociacion", { method: "POST", body: fd })
        .then((res) => res.json())
        .then((res) => {
          if (!res.status) {
            byId("resultadoCargaAsociacion").innerHTML =
              `<div class="alert alert-danger mb-0">${escapeHtml(res.msg)}</div>`;
            return;
          }
          const kpi = (lbl, n, color) => `
            <div class="col-6 col-md-3">
              <div class="border rounded p-2 text-center">
                <div class="fs-20 fw-semibold text-${color}">${n}</div>
                <div class="text-muted fs-12">${lbl}</div>
              </div>
            </div>`;
          let html = `<div class="row g-2 mb-3">
              ${kpi("Nuevas", res.nuevas, "success")}
              ${kpi("Actualizadas", res.actualizadas, "primary")}
              ${kpi("Sin cambios", res.sin_cambios, "secondary")}
              ${kpi("Omitidas", res.omitidas, res.omitidas ? "danger" : "secondary")}
            </div>`;
          if (res.omitidas) {
            html += `
              <div class="d-flex align-items-center mb-2">
                <h6 class="mb-0 flex-grow-1">Filas no procesadas${res.omitidas > 50 ? " (primeras 50)" : ""}</h6>
                <a class="btn btn-sm btn-soft-danger" href="${base_url}/Inv_series/exportarLogAsociacion">
                  <i class="ri-download-2-line align-bottom"></i> Descargar log
                </a>
              </div>
              <div class="table-responsive" style="max-height:260px">
                <table class="table table-sm table-bordered fs-12 mb-0">
                  <thead class="table-light"><tr><th>Fila</th><th>VIN</th><th>Motivo</th></tr></thead>
                  <tbody>${res.log
                    .map((l) => `<tr><td>${l.fila}</td><td class="mono">${escapeHtml(l.vin)}</td><td>${escapeHtml(l.motivo)}</td></tr>`)
                    .join("")}</tbody>
                </table>
              </div>`;
          }
          byId("resultadoCargaAsociacion").innerHTML = html;
          tableSeries.ajax.reload(null, false);
          cargarRefs(); // actualiza el avance de asociación de cada lote / orden
        })
        .catch(() => {
          byId("resultadoCargaAsociacion").innerHTML = '<div class="alert alert-danger mb-0">Error en el servidor.</div>';
        })
        .finally(() => {
          btn.disabled = false;
          btn.innerHTML = txt;
        });
    });
  }

  /* ---------- historial ---------- */

  const modalHistorial = new bootstrap.Modal(byId("modalHistorialVin"));
  $("#tableSeries tbody").on("click", ".btn-historial", function (e) {
    e.stopPropagation();
    byId("historialVinTitulo").textContent = this.dataset.vin;
    byId("historialVinBody").innerHTML =
      '<div class="text-center text-muted py-4"><span class="spinner-border spinner-border-sm me-1"></span> Cargando...</div>';
    modalHistorial.show();
    fetch(base_url + "/Inv_series/getHistorial?id=" + encodeURIComponent(this.dataset.id))
      .then((res) => res.json())
      .then((rows) => (byId("historialVinBody").innerHTML = renderHistorialVin(rows)))
      .catch(() => (byId("historialVinBody").innerHTML = '<div class="text-danger">No se pudo cargar el historial.</div>'));
  });
});

/* =====================================================================
 * Historial de VIN (wms_vin_historial) → línea de tiempo
 * ===================================================================== */
function renderHistorialVin(rows) {
  if (!Array.isArray(rows) || rows.length === 0) {
    return '<div class="text-center text-muted py-4"><i class="ri-inbox-line fs-20 d-block mb-1"></i>Sin movimientos registrados</div>';
  }
  const MODULOS = {
    GENERADOR: ["Generador de VIN", "hist-gen", "primary"],
    ASOCIACION: ["Asociación", "hist-asoc", "success"],
    VIN_BASE: ["VIN base (glosario)", "hist-base", "warning"],
  };
  const ACCIONES = { GENERACION: "Generación", ALTA: "Alta", MODIFICACION: "Modificación" };
  const CAMPOS = {
    numero_serie: "VIN", numero_motor: "Número de motor", vin_origen: "VIN de origen",
    vin_base: "VIN base", modelo: "Modelo", fabricante: "Fabricante", tipo_vehiculo: "Tipo de vehículo",
    peso_bruto_kg: "Peso bruto (kg)", tipo_motor: "Tipo de motor", potencia_hp: "Potencia (HP)",
    distancia_ejes: "Distancia entre ejes", anio: "Año", planta: "Planta", estado: "Estado",
  };
  const ORIGEN = { MASIVO: "Carga masiva", INDIVIDUAL: "Captura individual", SISTEMA: "Sistema" };

  return `<ul class="hist-list">${rows
    .map((h) => {
      const [modLbl, modCls, color] = MODULOS[h.modulo] || [h.modulo, "hist-gen", "secondary"];
      const campo = h.campo ? CAMPOS[h.campo] || h.campo : "";
      const ant = h.valor_anterior;
      const nue = h.valor_nuevo;
      let cambio = "";
      if (ant !== null && ant !== undefined && ant !== "") {
        cambio = `<div class="hist-cambio"><del>${escapeHtml(ant)}</del> <i class="ri-arrow-right-line align-middle text-muted"></i> <ins>${escapeHtml(nue ?? "—")}</ins></div>`;
      } else if (nue) {
        cambio = `<div class="hist-cambio"><ins>${escapeHtml(nue)}</ins></div>`;
      }
      return `<li class="hist-item ${modCls}">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
            <span class="badge bg-${color}-subtle text-${color}">${escapeHtml(modLbl)}</span>
            <span class="fw-semibold fs-13">${escapeHtml(ACCIONES[h.accion] || h.accion)}${campo ? " · " + escapeHtml(campo) : ""}</span>
            <span class="ms-auto text-muted fs-12">${escapeHtml(h.fecha)}</span>
          </div>
          ${cambio}
          ${h.detalle ? `<div class="text-muted fs-12 mt-1">${escapeHtml(h.detalle)}</div>` : ""}
          <div class="text-muted fs-11 mt-1"><i class="ri-user-line align-bottom"></i> ${escapeHtml(h.usuario || "—")} · ${escapeHtml(ORIGEN[h.origen] || h.origen)}</div>
        </li>`;
    })
    .join("")}</ul>`;
}
