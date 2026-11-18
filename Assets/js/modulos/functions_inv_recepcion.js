/* ==========================================================
   RECEPCIÓN DE MATERIALES (Inv_recepcion)
   Flujo: OC en tránsito -> recepción parcial/total -> OC recibida_parcial / cerrada
========================================================== */

const RC = {
  ordenes: [],
  grupo: "pendiente",
  compraid: null,
  recepcionid: null,
  cerrada: false,
  detalle: [],
};

const ESTATUS_OC = {
  emitida: { t: "Emitida", c: "secondary" },
  en_transito: { t: "En tránsito", c: "warning" },
  recibida_parcial: { t: "Recibida parcial", c: "info" },
  cerrada: { t: "Cerrada", c: "success" },
};

const ESTATUS_RC = {
  pendiente: { t: "Sin recibir", c: "warning" },
  abierta: { t: "Sin recibir", c: "warning" },
  parcial: { t: "Parcial", c: "info" },
  cerrada: { t: "Cerrada", c: "success" },
};

/* ---------- utilidades ---------- */
const esc = (s) =>
  String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
const num = (v) => {
  const n = parseFloat(v);
  return isNaN(n) ? 0 : n;
};
const fmt = (v) => num(v).toLocaleString("es-MX", { maximumFractionDigits: 2 });
const pct = (a, b) => (num(b) > 0 ? Math.min(100, Math.round((num(a) / num(b)) * 100)) : 0);
const badge = (map, key) => {
  const b = map[key] || { t: key || "-", c: "secondary" };
  return `<span class="badge bg-${b.c}-subtle text-${b.c} border border-${b.c}-subtle">${esc(b.t)}</span>`;
};

function alertaModal(icon, title, text) {
  return Swal.fire({ icon, title, text, confirmButtonColor: "#405189", confirmButtonText: "Aceptar" });
}

function toast(icon, title) {
  return Swal.fire({ toast: true, position: "top-end", icon, title, showConfirmButton: false, timer: 1800, timerProgressBar: true });
}

/* ==========================================================
   INICIO
========================================================== */
document.addEventListener("DOMContentLoaded", () => {
  const params = new URLSearchParams(window.location.search);
  const ocInicial = parseInt(params.get("oc") || params.get("oc_id") || "0", 10);

  cargarOrdenes(ocInicial || null);

  document.getElementById("buscarRecepcion").addEventListener("input", renderLista);
  document.getElementById("btnRefrescar").addEventListener("click", () => cargarOrdenes(RC.compraid));

  document.querySelectorAll(".rc-filtros [data-grupo], .rc-kpi[data-grupo]").forEach((el) =>
    el.addEventListener("click", () => cambiarGrupo(el.dataset.grupo))
  );

  const scanner = document.getElementById("scannerInput");
  scanner.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    const code = scanner.value.trim();
    scanner.value = "";
    if (code) procesarEscaneo(code);
  });

  document.getElementById("btnLimpiarCaptura").addEventListener("click", limpiarCaptura);
  document.getElementById("btnGuardarRecepcion").addEventListener("click", guardarRecepcion);
  document.getElementById("btnAgregarDocumento").addEventListener("click", agregarDocumento);

  const tbody = document.getElementById("detalleRecepcion");
  tbody.addEventListener("input", (e) => {
    if (e.target.classList.contains("rc-input-cant")) recalcular();
  });
  tbody.addEventListener("change", (e) => {
    if (e.target.classList.contains("rc-foto")) validarFotos(e.target);
  });
  tbody.addEventListener("click", (e) => {
    const btnTodo = e.target.closest("[data-todo]");
    if (btnTodo) {
      const input = tbody.querySelector(`.rc-input-cant[data-index="${btnTodo.dataset.todo}"]`);
      input.value = input.max;
      recalcular();
      return;
    }
    const btnFoto = e.target.closest("[data-agregar-foto]");
    if (btnFoto) {
      agregarFoto(btnFoto);
      return;
    }
    const btnQuitar = e.target.closest("[data-quitar]");
    if (btnQuitar) {
      btnQuitar.closest(".rc-foto-item").remove();
      return;
    }
    const btnVer = e.target.closest("[data-ver-evidencias]");
    if (btnVer) verEvidencias(btnVer.dataset.verEvidencias, btnVer.dataset.codigo);
  });
});

/* ==========================================================
   LISTADO DE ÓRDENES
========================================================== */
function cargarOrdenes(seleccionar = null) {
  return fetch(base_url + "/Inv_recepcion/getOrdenesRecepcion")
    .then((r) => r.json())
    .then((data) => {
      RC.ordenes = Array.isArray(data) ? data : [];
      renderKpis();

      if (seleccionar) {
        const oc = RC.ordenes.find((o) => parseInt(o.idcompra) === parseInt(seleccionar));
        if (oc) {
          RC.grupo = oc.grupo;
          marcarGrupo();
          seleccionarOrden(oc.idcompra);
        }
      }
      renderLista();
    })
    .catch((err) => {
      console.error(err);
      document.getElementById("listaOrdenes").innerHTML =
        `<div class="alert alert-danger mb-0">No se pudieron cargar las órdenes de compra.</div>`;
    });
}

function renderKpis() {
  const c = { pendiente: 0, parcial: 0, cerrada: 0 };
  RC.ordenes.forEach((o) => (c[o.grupo] = (c[o.grupo] || 0) + 1));

  ["pendiente", "parcial", "cerrada"].forEach((g) => {
    const cap = g.charAt(0).toUpperCase() + g.slice(1);
    document.getElementById("kpi" + cap).textContent = c[g];
    document.getElementById("tab" + cap).textContent = c[g];
  });
}

function cambiarGrupo(grupo) {
  RC.grupo = grupo;
  marcarGrupo();
  renderLista();
}

function marcarGrupo() {
  document.querySelectorAll(".rc-filtros [data-grupo]").forEach((b) => b.classList.toggle("active", b.dataset.grupo === RC.grupo));
  document.querySelectorAll(".rc-kpi[data-grupo]").forEach((k) => k.classList.toggle("active", k.dataset.grupo === RC.grupo));
}

function renderLista() {
  const filtro = document.getElementById("buscarRecepcion").value.trim().toLowerCase();
  const cont = document.getElementById("listaOrdenes");

  const lista = RC.ordenes.filter((o) => {
    if (o.grupo !== RC.grupo) return false;
    if (!filtro) return true;
    return `${o.folio} ${o.proveedor} ${o.almacen} ${o.folio_recepcion || ""}`.toLowerCase().includes(filtro);
  });

  if (!lista.length) {
    const textos = {
      pendiente: "No hay órdenes en tránsito por recibir.",
      parcial: "No hay recepciones parciales.",
      cerrada: "No hay recepciones cerradas.",
    };
    cont.innerHTML = `<div class="rc-empty py-5"><i class="ri-inbox-line"></i>${filtro ? "Sin resultados para la búsqueda." : textos[RC.grupo]}</div>`;
    return;
  }

  cont.innerHTML = lista
    .map((o) => {
      const p = pct(o.recibido, o.solicitado);
      const color = o.grupo === "cerrada" ? "success" : o.grupo === "parcial" ? "info" : "warning";
      return `
      <div class="rc-oc-item g-${o.grupo} ${parseInt(o.idcompra) === parseInt(RC.compraid) ? "active" : ""}"
           onclick="seleccionarOrden(${parseInt(o.idcompra)})">
        <div class="d-flex justify-content-between align-items-start mb-1">
          <div class="rc-oc-folio">${esc(o.folio)}</div>
          ${badge(ESTATUS_OC, o.estatus_oc)}
        </div>
        <div class="text-truncate fw-medium" title="${esc(o.proveedor)}"><i class="ri-building-line text-muted me-1"></i>${esc(o.proveedor)}</div>
        <div class="d-flex justify-content-between text-muted small mb-2">
          <span class="text-truncate me-2"><i class="ri-store-2-line me-1"></i>${esc(o.almacen)}</span>
          <span class="text-nowrap"><i class="ri-calendar-line me-1"></i>${esc(o.fecha_oc)}</span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <div class="progress flex-grow-1"><div class="progress-bar bg-${color}" style="width:${p}%"></div></div>
          <small class="text-muted text-nowrap">${fmt(o.recibido)}/${fmt(o.solicitado)} · ${p}%</small>
        </div>
      </div>`;
    })
    .join("");
}

/* ==========================================================
   SELECCIÓN DE ORDEN
========================================================== */
function seleccionarOrden(id) {
  RC.compraid = parseInt(id);
  document.querySelectorAll(".rc-oc-item").forEach((el) => el.classList.remove("active"));
  renderLista();

  document.getElementById("panelVacio").classList.add("d-none");
  document.getElementById("panelRecepcion").classList.remove("d-none");
  document.getElementById("detalleRecepcion").innerHTML =
    `<tr><td colspan="10" class="text-center py-4"><span class="spinner-border spinner-border-sm"></span> Cargando materiales...</td></tr>`;
  resetDocumentos();

  Promise.all([
    fetch(base_url + "/Inv_recepcion/getHeaderOC/" + RC.compraid).then((r) => r.json()),
    fetch(base_url + "/Inv_recepcion/getDetalleOC/" + RC.compraid).then((r) => r.json()),
  ])
    .then(([header, detalle]) => {
      RC.recepcionid = header && header.idrecepcion ? header.idrecepcion : null;
      RC.cerrada = header && header.estatus_recepcion === "cerrada";
      RC.detalle = Array.isArray(detalle) ? detalle : [];

      renderHeader(header || {});
      renderDetalle();
      aplicarBloqueo();
      cargarHistorial();
      cargarDocumentos();

      if (!RC.cerrada) document.getElementById("scannerInput").focus();
    })
    .catch((err) => {
      console.error(err);
      alertaModal("error", "Error", "No se pudo cargar la orden de compra.");
    });
}

function renderHeader(h) {
  const solicitado = RC.detalle.reduce((a, d) => a + num(d.cantidad_solicitada), 0);
  const recibido = RC.detalle.reduce((a, d) => a + num(d.cantidad_recibida), 0);
  const p = pct(recibido, solicitado);

  document.getElementById("hdrFolio").textContent = h.orden_origen || "OC-" + RC.compraid;
  document.getElementById("hdrProveedor").innerHTML = `<i class="ri-building-line me-1"></i>${esc(h.proveedor || "")}`;
  document.getElementById("hdrEstatusOC").innerHTML = badge(ESTATUS_OC, h.estatus_oc);
  document.getElementById("hdrEstatusRC").innerHTML = badge(ESTATUS_RC, h.estatus_recepcion || "pendiente");
  document.getElementById("hdrFecha").textContent = h.fecha || "-";
  document.getElementById("hdrAlmacen").textContent = h.almacen_destino || "-";
  document.getElementById("hdrFolioRC").textContent = h.idrecepcion ? h.orden_destino : "Se genera al registrar";
  document.getElementById("hdrAvanceTxt").textContent = `${fmt(recibido)} / ${fmt(solicitado)}`;
  document.getElementById("hdrAvancePct").textContent = `(${p}%)`;
  document.getElementById("hdrAvanceBar").style.width = p + "%";
  document.getElementById("observacionesRecepcion").value = h.observaciones || "";
}

function renderDetalle() {
  const tbody = document.getElementById("detalleRecepcion");
  const completas = RC.detalle.filter((d) => num(d.cantidad_pendiente) <= 0).length;
  document.getElementById("txtPartidas").textContent =
    `${RC.detalle.length} partida(s) · ${completas} completa(s) · ${RC.detalle.length - completas} pendiente(s)`;

  if (!RC.detalle.length) {
    tbody.innerHTML = `<tr><td colspan="10"><div class="rc-empty py-4"><i class="ri-file-warning-line"></i>La orden no tiene partidas.</div></td></tr>`;
    return;
  }

  tbody.innerHTML = RC.detalle
    .map((d, i) => {
      const pend = num(d.cantidad_pendiente);
      const completo = pend <= 0;
      const fotos = parseInt(d.total_evidencias) || 0;
      return `
      <tr data-index="${i}" class="${completo ? "rc-completo" : ""}">
        <td class="text-muted">${i + 1}</td>
        <td style="min-width:200px">
          <div class="fw-semibold">${esc(d.codigo)}</div>
          <small class="text-muted">${esc(d.descripcion)}</small>
          <small class="text-muted d-block">${esc(d.unidad || "")}</small>
        </td>
        <td class="text-center">${fmt(d.cantidad_solicitada)}</td>
        <td class="text-center text-success fw-semibold">${fmt(d.cantidad_recibida)}</td>
        <td class="text-center fw-semibold rc-pendiente">${fmt(pend)}</td>
        <td class="text-center">
          ${
            completo
              ? `<span class="text-muted">—</span>`
              : `<div class="input-group input-group-sm flex-nowrap justify-content-center">
                   <input type="number" class="form-control rc-input-cant" data-index="${i}"
                          min="0" max="${pend}" step="any" value="" placeholder="0">
                   <button class="btn btn-soft-success" type="button" data-todo="${i}" title="Recibir todo (${fmt(pend)})">
                     <i class="ri-check-line"></i>
                   </button>
                 </div>`
          }
        </td>
        <td><input type="text" class="form-control form-control-sm rc-input-txt rc-lote" placeholder="Lote" ${completo ? "disabled" : ""}></td>
        <td><input type="text" class="form-control form-control-sm rc-input-txt rc-obs" placeholder="Comentario" ${completo ? "disabled" : ""}></td>
        <td class="text-center" style="min-width:150px">
          ${
            completo
              ? ""
              : `<div class="rc-fotos">
                   <div class="rc-foto-item mb-1">
                     <input type="file" class="form-control form-control-sm rc-foto" accept="image/*">
                   </div>
                 </div>
                 <button type="button" class="btn btn-sm btn-outline-primary mb-1" data-agregar-foto>
                   <i class="ri-add-line"></i> Agregar foto
                 </button>
                 <small class="text-muted d-block">Máximo 5 fotos</small>`
          }
          ${
            fotos > 0
              ? `<button class="btn btn-sm btn-link p-0" data-ver-evidencias="${d.inventarioid}" data-codigo="${esc(d.codigo)}">
                   <i class="ri-image-line"></i> Ver ${fotos}
                 </button>`
              : completo ? `<small class="text-muted">Sin fotos</small>` : ""
          }
        </td>
        <td class="text-center rc-estado">${estadoFila(d, 0)}</td>
      </tr>`;
    })
    .join("");

  recalcular();
}

function estadoFila(d, capturado) {
  const pend = num(d.cantidad_pendiente);
  if (pend <= 0) return `<span class="badge bg-success"><i class="ri-check-double-line"></i> Completo</span>`;
  if (capturado > 0 && capturado >= pend) return `<span class="badge bg-success-subtle text-success">Se completa</span>`;
  if (capturado > 0) return `<span class="badge bg-info-subtle text-info">Parcial</span>`;
  if (num(d.cantidad_recibida) > 0) return `<span class="badge bg-info-subtle text-info">Pendiente</span>`;
  return `<span class="badge bg-warning-subtle text-warning">Pendiente</span>`;
}

function aplicarBloqueo() {
  const c = RC.cerrada;
  document.getElementById("avisoCerrada").classList.toggle("d-none", !c);
  document.getElementById("avisoCerrada").classList.toggle("d-flex", c);
  document.getElementById("cardScanner").classList.toggle("d-none", c);
  document.getElementById("saveBar").classList.toggle("d-none", c);
  document.getElementById("observacionesRecepcion").readOnly = c;
  document.getElementById("contenedorDocumentos").classList.toggle("d-none", c);
  document.getElementById("btnAgregarDocumento").classList.toggle("d-none", c);
  document.querySelectorAll("#detalleRecepcion input, #detalleRecepcion [data-todo]").forEach((el) => (el.disabled = c));
  document.querySelectorAll("#detalleRecepcion [data-agregar-foto]").forEach((el) => el.classList.toggle("d-none", c));
}

/* ==========================================================
   CAPTURA
========================================================== */
function recalcular() {
  let piezas = 0;
  let partidas = 0;
  let hayError = false;
  let quedaPendiente = false;

  document.querySelectorAll("#detalleRecepcion tr[data-index]").forEach((row) => {
    const d = RC.detalle[row.dataset.index];
    const input = row.querySelector(".rc-input-cant");
    const pend = num(d.cantidad_pendiente);
    const cant = input ? num(input.value) : 0;

    if (input) {
      const invalido = cant < 0 || cant > pend + 0.0001;
      input.classList.toggle("is-invalid", invalido);
      if (invalido) hayError = true;
    }

    if (cant > 0) {
      piezas += cant;
      partidas++;
    }
    if (pend - cant > 0.0001) quedaPendiente = true;

    row.classList.toggle("rc-capturado", cant > 0);
    row.querySelector(".rc-estado").innerHTML = estadoFila(d, cant);
  });

  const btn = document.getElementById("btnGuardarRecepcion");
  const resumen = document.getElementById("resumenCaptura");
  const resultado = document.getElementById("resumenResultado");

  btn.disabled = hayError || piezas <= 0;

  if (hayError) {
    resumen.innerHTML = `<span class="text-danger"><i class="ri-error-warning-line"></i> Hay cantidades mayores a lo pendiente</span>`;
    resultado.textContent = "Corrige las celdas marcadas en rojo.";
  } else if (piezas <= 0) {
    resumen.textContent = "Sin cantidades capturadas";
    resultado.textContent = 'Captura cantidades en "A recibir" o escanea productos.';
  } else {
    resumen.innerHTML = `Recibiendo <strong>${fmt(piezas)}</strong> pieza(s) en <strong>${partidas}</strong> partida(s)`;
    resultado.innerHTML = quedaPendiente
      ? `Al registrar, la OC quedará como <span class="badge bg-info">Recibida parcial</span>`
      : `Al registrar, la OC quedará <span class="badge bg-success">Cerrada</span> (recepción completa)`;
  }

  RC.resultadoCierra = !quedaPendiente;
  RC.piezas = piezas;
  RC.partidas = partidas;
}

function promptCantidad(d, restante) {
  return Swal.fire({
    title: esc(d.codigo),
    html: `
      <div class="text-muted mb-3">${esc(d.descripcion)}</div>
      <div class="d-flex justify-content-center gap-3 mb-2">
        <div class="border rounded p-2 px-3"><small class="text-muted d-block">Pendiente</small><strong class="fs-4">${fmt(restante)}</strong></div>
        <div class="border rounded p-2 px-3"><small class="text-muted d-block">Unidad</small><strong class="fs-6">${esc(d.unidad || "-")}</strong></div>
      </div>`,
    input: "number",
    inputValue: restante >= 1 ? 1 : restante,
    inputAttributes: { min: 0, max: restante, step: "any" },
    showCancelButton: true,
    confirmButtonText: "Agregar",
    cancelButtonText: "Cancelar",
    confirmButtonColor: "#0ab39c",
    inputValidator: (v) => {
      const n = num(v);
      if (n <= 0) return "Captura una cantidad mayor a 0";
      if (n > restante + 0.0001) return `No puede ser mayor a ${fmt(restante)}`;
    },
    didOpen: () => Swal.getInput().select(),
  });
}

async function procesarEscaneo(code) {
  const scanner = document.getElementById("scannerInput");
  const idx = RC.detalle.findIndex((d) => String(d.codigo).trim().toLowerCase() === code.toLowerCase());

  if (idx < 0) {
    await alertaModal("error", "Código no encontrado", `El código "${code}" no pertenece a esta orden de compra.`);
    scanner.focus();
    return;
  }

  const d = RC.detalle[idx];
  const row = document.querySelector(`#detalleRecepcion tr[data-index="${idx}"]`);
  const input = row.querySelector(".rc-input-cant");

  if (!input) {
    await alertaModal("info", "Partida completa", `${d.codigo} ya fue recibido en su totalidad.`);
    scanner.focus();
    return;
  }

  const restante = num(d.cantidad_pendiente) - num(input.value);
  if (restante <= 0) {
    await alertaModal("warning", "Sin pendiente", `Ya capturaste todo lo pendiente de ${d.codigo}.`);
    scanner.focus();
    return;
  }

  const res = await promptCantidad(d, restante);
  if (res.isConfirmed) {
    input.value = +(num(input.value) + num(res.value)).toFixed(4);
    recalcular();
    row.scrollIntoView({ behavior: "smooth", block: "center" });
    row.classList.remove("rc-flash");
    void row.offsetWidth;
    row.classList.add("rc-flash");
    toast("success", `${d.codigo}: +${fmt(res.value)}`);
  }
  scanner.focus();
}

function limpiarCaptura() {
  document.querySelectorAll("#detalleRecepcion .rc-input-cant, #detalleRecepcion .rc-lote, #detalleRecepcion .rc-obs, #detalleRecepcion .rc-foto")
    .forEach((el) => (el.value = ""));
  document.querySelectorAll("#detalleRecepcion .rc-foto-item.rc-extra").forEach((el) => el.remove());
  recalcular();
  document.getElementById("scannerInput").focus();
}

function validarFotos(input) {
  const cont = input.closest(".rc-fotos");
  const total = Array.from(cont.querySelectorAll(".rc-foto")).reduce((a, i) => a + i.files.length, 0);
  if (total > 5) {
    alertaModal("warning", "Límite alcanzado", "Solo puedes adjuntar hasta 5 fotografías por producto.");
    input.value = "";
  }
}

function agregarFoto(btn) {
  const cont = btn.closest("td").querySelector(".rc-fotos");
  if (cont.querySelectorAll(".rc-foto-item").length >= 5) {
    alertaModal("warning", "Límite alcanzado", "Solo puedes adjuntar hasta 5 fotografías por producto.");
    return;
  }
  const div = document.createElement("div");
  div.className = "rc-foto-item rc-extra mb-1 d-flex gap-1";
  div.innerHTML = `
    <input type="file" class="form-control form-control-sm rc-foto" accept="image/*">
    <button type="button" class="btn btn-sm btn-soft-danger" data-quitar title="Quitar"><i class="ri-delete-bin-line"></i></button>`;
  cont.appendChild(div);
}

function agregarDocumento() {
  const cont = document.getElementById("contenedorDocumentos");
  const div = document.createElement("div");
  div.className = "rc-doc-item rc-extra mb-2 d-flex gap-1";
  div.innerHTML = `
    <input type="file" class="form-control rc-doc" accept=".pdf,.jpg,.jpeg,.png,.webp,.xml,.xls,.xlsx,.doc,.docx,.csv,.txt">
    <button type="button" class="btn btn-soft-danger" title="Quitar"><i class="ri-delete-bin-line"></i></button>`;
  div.querySelector("button").addEventListener("click", () => div.remove());
  cont.appendChild(div);
}

function resetDocumentos() {
  document.querySelectorAll("#contenedorDocumentos .rc-doc-item.rc-extra").forEach((el) => el.remove());
  document.querySelectorAll("#contenedorDocumentos .rc-doc").forEach((el) => (el.value = ""));
}

/* ==========================================================
   GUARDAR
========================================================== */
async function guardarRecepcion() {
  recalcular();
  if (!RC.compraid || RC.piezas <= 0) return;

  const folio = document.getElementById("hdrFolio").textContent;
  const confirm = await Swal.fire({
    icon: "question",
    title: "¿Registrar recepción?",
    html: `
      <p class="mb-2">Se darán de entrada <strong>${fmt(RC.piezas)}</strong> pieza(s) en <strong>${RC.partidas}</strong> partida(s)
      al almacén <strong>${esc(document.getElementById("hdrAlmacen").textContent)}</strong>.</p>
      ${
        RC.resultadoCierra
          ? `<div class="alert alert-success mb-0">La recepción queda <strong>completa</strong> y la <strong>${esc(folio)}</strong> se marcará como <strong>CERRADA</strong>. Ya no podrá modificarse.</div>`
          : `<div class="alert alert-info mb-0">Quedará material pendiente. La <strong>${esc(folio)}</strong> se marcará como <strong>RECIBIDA PARCIAL</strong> y podrás completar la recepción después.</div>`
      }`,
    showCancelButton: true,
    confirmButtonText: "Sí, registrar",
    cancelButtonText: "Revisar",
    confirmButtonColor: "#0ab39c",
  });
  if (!confirm.isConfirmed) return;

  const detalle = [];
  const formData = new FormData();

  document.querySelectorAll("#detalleRecepcion tr[data-index]").forEach((row) => {
    const d = RC.detalle[row.dataset.index];
    const input = row.querySelector(".rc-input-cant");
    const cant = input ? num(input.value) : 0;

    if (cant > 0) {
      detalle.push({
        iddetalle: d.iddetalle,
        inventarioid: d.inventarioid,
        codigo: d.codigo,
        lote: row.querySelector(".rc-lote").value.trim(),
        cantidad_solicitada: d.cantidad_solicitada,
        cantidad_recibida: cant,
        observaciones: row.querySelector(".rc-obs").value.trim(),
      });
    }

    const fotos = [];
    row.querySelectorAll(".rc-foto").forEach((inp) => fotos.push(...Array.from(inp.files)));
    if (fotos.length) {
      fotos.slice(0, 5).forEach((f) => formData.append(`evidencias[${d.iddetalle}][]`, f));
      formData.append(`evidencias_meta[${d.iddetalle}][inventarioid]`, d.inventarioid);
    }
  });

  formData.append("compraid", RC.compraid);
  formData.append("observaciones", document.getElementById("observacionesRecepcion").value);
  formData.append("detalle", JSON.stringify(detalle));
  document.querySelectorAll("#contenedorDocumentos .rc-doc").forEach((inp) =>
    Array.from(inp.files).forEach((f) => formData.append("documentos[]", f))
  );

  const btn = document.getElementById("btnGuardarRecepcion");
  btn.disabled = true;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm me-1"></span> Registrando...`;

  try {
    const res = await fetch(base_url + "/Inv_recepcion/setRecepcion", { method: "POST", body: formData }).then((r) => r.json());

    if (!res.status) {
      await alertaModal("error", "No se registró la recepción", res.msg);
      return;
    }

    await alertaModal("success", res.estatus_oc === "cerrada" ? "Recepción completa" : "Recepción parcial registrada", res.msg);
    await cargarOrdenes(RC.compraid);
  } catch (err) {
    console.error(err);
    alertaModal("error", "Error", "Ocurrió un error de comunicación con el servidor.");
  } finally {
    btn.innerHTML = `<i class="ri-check-double-line me-1"></i> Registrar recepción`;
    recalcular();
  }
}

/* ==========================================================
   HISTORIAL, DOCUMENTOS, EVIDENCIAS
========================================================== */
function cargarHistorial() {
  const cont = document.getElementById("historialRecepcion");
  fetch(base_url + "/Inv_recepcion/getHistorialRecepcion/" + RC.compraid)
    .then((r) => r.json())
    .then((data) => {
      data = Array.isArray(data) ? data : [];
      const grupos = {};
      data.forEach((h) => (grupos[h.fecha] = grupos[h.fecha] || []).push(h));
      const fechas = Object.keys(grupos);
      document.getElementById("badgeHistorial").textContent = fechas.length;

      if (!fechas.length) {
        cont.innerHTML = `<div class="text-muted text-center py-3">Aún no se ha registrado ninguna recepción para esta orden.</div>`;
        return;
      }

      cont.innerHTML = fechas
        .map((f) => {
          const items = grupos[f];
          const total = items.reduce((a, x) => a + num(x.cantidad_recibida), 0);
          return `
          <div class="rc-historial-grupo">
            <div class="d-flex justify-content-between mb-1">
              <strong><i class="ri-calendar-check-line me-1"></i>${esc(f)}</strong>
              <small class="text-muted">${items.length} partida(s) · ${fmt(total)} pieza(s)</small>
            </div>
            <table class="table table-sm table-borderless mb-0 small">
              ${items
                .map(
                  (x) => `<tr>
                    <td style="width:130px" class="fw-semibold">${esc(x.codigo)}</td>
                    <td class="text-muted">${esc(x.descripcion)}</td>
                    <td class="text-end text-nowrap text-success fw-semibold">+${fmt(x.cantidad_recibida)} ${esc(x.unidad || "")}</td>
                    <td class="text-nowrap">${x.lote ? `<span class="badge bg-light text-body">Lote ${esc(x.lote)}</span>` : ""}</td>
                    <td class="text-muted">${esc(x.observaciones || "")}</td>
                  </tr>`
                )
                .join("")}
            </table>
          </div>`;
        })
        .join("");
    });
}

function cargarDocumentos() {
  const cont = document.getElementById("listaDocumentosExistentes");
  if (!RC.recepcionid) {
    cont.innerHTML = "";
    return;
  }
  fetch(base_url + "/Inv_recepcion/getDocumentosRecepcion?recepcionid=" + RC.recepcionid)
    .then((r) => r.json())
    .then((data) => {
      data = Array.isArray(data) ? data : [];
      cont.innerHTML = data.length
        ? `<div class="text-muted mb-1">Documentos cargados:</div>` +
          data
            .map(
              (d) => `<a class="list-group-item list-group-item-action px-0 d-flex justify-content-between" target="_blank"
                   href="${base_url}/Assets/uploads/recepciones/documentos/${encodeURIComponent(d.ruta)}">
                  <span class="text-truncate"><i class="ri-file-line me-1"></i>${esc(d.nombre || d.ruta)}</span>
                  <small class="text-muted text-nowrap ms-2">${esc(d.fecha || "")}</small>
                </a>`
            )
            .join("")
        : `<small class="text-muted">Sin documentos cargados.</small>`;
    });
}

function verEvidencias(inventarioid, codigo) {
  document.getElementById("modalEvidenciasProducto").textContent = codigo ? "· " + codigo : "";
  const galeria = document.getElementById("galeriaProducto");
  galeria.innerHTML = `<div class="text-center py-4"><span class="spinner-border spinner-border-sm"></span></div>`;
  new bootstrap.Modal(document.getElementById("modalEvidencias")).show();

  fetch(base_url + "/Inv_recepcion/getEvidenciasProducto?recepcionid=" + RC.recepcionid + "&inventarioid=" + inventarioid)
    .then((r) => r.json())
    .then((data) => {
      data = Array.isArray(data) ? data : [];
      galeria.innerHTML = data.length
        ? data
            .map((foto) => {
              const url = `${base_url}/Assets/uploads/recepciones/evidencias/${encodeURIComponent(foto.ruta)}`;
              return `<div class="col-6 col-md-3 mb-3">
                  <a href="${url}" target="_blank"><img src="${url}" class="img-fluid rounded shadow-sm"></a>
                  <small class="text-muted d-block mt-1">${esc(foto.fecha || "")}</small>
                </div>`;
            })
            .join("")
        : `<div class="text-muted text-center py-4">Sin fotografías.</div>`;
    });
}
