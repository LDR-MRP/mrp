// --------------------------
//  VISTAS
// --------------------------
const viewHome = document.getElementById('viewHome');
const viewNueva = document.getElementById('viewNueva');
const viewListado = document.getElementById('viewListado');

// --------------------------
//  BOTONES 
// --------------------------
const btnNuevaPlaneacion = document.getElementById('btnNuevaPlaneacion');
const btnPendientes = document.getElementById('btnPendientes');
const btnFinalizadas = document.getElementById('btnFinalizadas');
const btnCanceladas = document.getElementById('btnCanceladas');

// volver
const btnVolverHome1 = document.getElementById('btnVolverHome1');
const btnVolverHome2 = document.getElementById('btnVolverHome2');

// nueva acciones
const btnCancelarNueva = document.getElementById('btnCancelarNueva');
const btnGuardarPlaneacion = document.getElementById('btnGuardarPlaneacion');

// listado
const badgeListado = document.getElementById('badgeListado');
const breadcrumbListado = document.getElementById('breadcrumbListado');
const listadoTitulo = document.getElementById('listadoTitulo');
const listadoSubtitulo = document.getElementById('listadoSubtitulo');
const tbodyListados = document.getElementById('tbodyListados');
const btnRefrescarListado = document.getElementById('btnRefrescarListado');

// filtros
const filterSearch = document.getElementById('filterSearch');
const filterDesde = document.getElementById('filterDesde');
const filterHasta = document.getElementById('filterHasta');
const filterPrioridad = document.getElementById('filterPrioridad');

let currentListado = null;

// =====================================================
//  FALTANTES (COMPONENTES / HERRAMIENTAS)
// =====================================================
let estacionesSinComponentes = new Set();        
let estacionesSinHerramientas = new Set();        

let faltantesComponentesMap = {};                
let faltantesHerramientasMap = {};               

function limpiarFaltantesUI() {
  estacionesSinComponentes = new Set();
  estacionesSinHerramientas = new Set();
  faltantesComponentesMap = {};
  faltantesHerramientasMap = {};
}


// =====================================================
//  LOADING
// =====================================================
let divLoading = null;

function showLoading() { if (divLoading) divLoading.style.display = "flex"; }
function hideLoading() { if (divLoading) divLoading.style.display = "none"; }

async function fetchJson(url, options = {}) {
  showLoading();
  try {
    const res = await fetch(url, options);
    const text = await res.text();

    let data;
    try {
      data = text ? JSON.parse(text) : {};
    } catch (e) {
      console.error("RESPUESTA NO JSON:", text);
      throw new Error("El servidor no devolvió elñb JSON");
    }

    if (!res.ok) throw new Error(data?.msg || `HTTP ${res.status}`);
    return data;
  } finally {
    hideLoading();
  }
}

// =====================================================
//  INIT
// =====================================================
document.addEventListener('DOMContentLoaded', function () {

  divLoading = document.querySelector("#divLoading");
  hideLoading();

  fntProductos();

  const btnAplicar = document.querySelector('#btnAplicarAsignacion');
  if (btnAplicar) btnAplicar.addEventListener('click', onAplicarAsignacion);

  if (btnGuardarPlaneacion) btnGuardarPlaneacion.addEventListener('click', guardarPlaneacionHandler);

  const selAy = document.querySelector('#selectAyudantes');
  if (selAy) {
    selAy.addEventListener('change', () => {
      const estacionid = document.querySelector('#modalEstacionId')?.value || "";
      if (!estacionid) return;
      aplicarBloqueoAyudantes(estacionid);
    });
  }


  document.addEventListener('click', (e) => {
  const btnComp = e.target.closest('[data-action="ver-faltantes-comp"]');
  if (btnComp) {
    const estacionid = Number(btnComp.getAttribute('data-estacionid') || 0);
    const nombre = btnComp.getAttribute('data-estacion-nombre') || '';
    abrirModalFaltantesComponentes(estacionid, nombre);
    return;
  }

    const btnHer = e.target.closest('[data-action="ver-faltantes-her"]');
    if (btnHer) {
      const estacionid = Number(btnHer.getAttribute('data-estacionid') || 0);
      const nombre = btnHer.getAttribute('data-estacion-nombre') || '';
      abrirModalFaltantesHerramientas(estacionid, nombre);
      return;
    }
  });

  // navegación
  if (btnPendientes) btnPendientes.addEventListener('click', () => goListado('PENDIENTE'));
  if (btnFinalizadas) btnFinalizadas.addEventListener('click', () => goListado('FINALIZADA'));
  if (btnCanceladas) btnCanceladas.addEventListener('click', () => goListado('CANCELADA'));


  if (btnNuevaPlaneacion) {
    btnNuevaPlaneacion.addEventListener('click', async () => {
      await limpiarNuevaPlaneacion(true);
      goNueva();
    });
  }

  // salir de nueva con confirmación
  [btnVolverHome1, btnVolverHome2, btnCancelarNueva].filter(Boolean).forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const ok = await confirmarDescartarSiHayBorrador();
      if (!ok) return;
      await limpiarNuevaPlaneacion(true);
      goHome();
    });
  });

  if (btnRefrescarListado) {
    btnRefrescarListado.addEventListener('click', async () => {
      if (!currentListado) return;
      await renderListado(currentListado);
    });
  }

  [filterSearch, filterDesde, filterHasta, filterPrioridad].filter(Boolean).forEach(el => {
    el.addEventListener('input', async () => {
      if (!currentListado) return;
      await renderListado(currentListado);
    });
    el.addEventListener('change', async () => {
      if (!currentListado) return;
      await renderListado(currentListado);
    });
  });

  goHome();
});

// =====================================================
//  VISTAS
// =====================================================
function hideAll() {
  if (viewHome) viewHome.classList.add('d-none');
  if (viewNueva) viewNueva.classList.add('d-none');
  if (viewListado) viewListado.classList.add('d-none');
}

function setActiveNav(activeBtn) {
  [btnPendientes, btnFinalizadas, btnCanceladas].filter(Boolean).forEach(b => b.classList.remove('active'));
  if (activeBtn) activeBtn.classList.add('active');
}

function goHome() {
  hideAll();
  setActiveNav(null);
  if (viewHome) viewHome.classList.remove('d-none');
}

function goNueva() {
  hideAll();
  setActiveNav(null);
  if (viewNueva) viewNueva.classList.remove('d-none');
}

async function goListado(tipo) {
  hideAll();
  if (viewListado) viewListado.classList.remove('d-none');
  currentListado = tipo;

  if (!badgeListado || !breadcrumbListado || !listadoTitulo || !listadoSubtitulo) return;

  if (tipo === 'PENDIENTE') {
    badgeListado.className = 'badge bg-warning-subtle text-warning border';
    badgeListado.innerHTML = '<i class="ri-time-line me-1"></i> Pendientes';
    breadcrumbListado.textContent = 'Inicio → Planeación → Pendientes';
    listadoTitulo.textContent = 'Planeaciones Pendientes';
    listadoSubtitulo.textContent = 'Órdenes en espera / en proceso. Administra y da seguimiento.';
    setActiveNav(btnPendientes);
  } else if (tipo === 'FINALIZADA') {
    badgeListado.className = 'badge bg-success-subtle text-success border';
    badgeListado.innerHTML = '<i class="ri-checkbox-circle-line me-1"></i> Finalizadas';
    breadcrumbListado.textContent = 'Inicio → Planeación → Finalizadas';
    listadoTitulo.textContent = 'Planeaciones Finalizadas';
    listadoSubtitulo.textContent = 'Órdenes completadas. Consulta historial y evidencia.';
    setActiveNav(btnFinalizadas);
  } else {
    badgeListado.className = 'badge bg-danger-subtle text-danger border';
    badgeListado.innerHTML = '<i class="ri-close-circle-line me-1"></i> Canceladas';
    breadcrumbListado.textContent = 'Inicio → Planeación → Canceladas';
    listadoTitulo.textContent = 'Planeaciones Canceladas';
    listadoSubtitulo.textContent = 'Órdenes canceladas. Revisión de motivo y control.';
    setActiveNav(btnCanceladas);
  }

  await renderListado(tipo);
}

// =====================================================
//  LISTADO 
// =====================================================
function getListadoEndpoint(tipo) {
  if (tipo === 'PENDIENTE') return base_url + '/plan_planeacion/getPendientes';
  if (tipo === 'FINALIZADA') return base_url + '/plan_planeacion/getFinalizadas';
  return base_url + '/plan_planeacion/getCanceladas';
}

function normalizeListadoResponse(payload) {
  if (Array.isArray(payload)) return payload;
  if (payload && Array.isArray(payload.data)) return payload.data;
  if (payload && Array.isArray(payload.rows)) return payload.rows;
  return [];
}

function escapeHtml(str = "") {
  return String(str)
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");
}

function badgePrioridad(p) {
  if (p === 'CRITICA') return '<span class="badge bg-danger-subtle text-danger border">CRÍTICA</span>';
  if (p === 'ALTA') return '<span class="badge bg-warning-subtle text-warning border">ALTA</span>';
  if (p === 'MEDIA') return '<span class="badge bg-primary-subtle text-primary border">MEDIA</span>';
  if (p === 'BAJA') return '<span class="badge bg-secondary-subtle text-secondary border">BAJA</span>';
  return `<span class="badge bg-light text-dark border">${escapeHtml(p)}</span>`;
}

function badgeEstatus(e) {
  if (e === 'PENDIENTE') return '<span class="badge bg-warning-subtle text-warning border">PENDIENTE</span>';
  if (e === 'FINALIZADA') return '<span class="badge bg-success-subtle text-success border">FINALIZADA</span>';
  if (e === 'CANCELADA') return '<span class="badge bg-danger-subtle text-danger border">CANCELADA</span>';
  return `<span class="badge bg-light text-dark border">${escapeHtml(e)}</span>`;
}

function applyClientFilters(rows) {
  const q = (filterSearch?.value || '').trim().toLowerCase();
  const d1 = filterDesde?.value || '';
  const d2 = filterHasta?.value || '';
  const pr = filterPrioridad?.value || '';

  return rows.filter(r => {
    const folio = String(r.folio ?? r.num_orden ?? r.orden ?? '');
    const producto = String(r.producto ?? r.nombre_producto ?? r.descripcion_producto ?? '');
    const inicio = String(r.inicio ?? r.fecha_inicio ?? '');
    const prioridad = String(r.prioridad ?? '').trim();

    if (q) {
      const hit = (folio + ' ' + producto).toLowerCase().includes(q);
      if (!hit) return false;
    }
    if (pr && prioridad !== pr) return false;
    if (d1 && inicio && inicio < d1) return false;
    if (d2 && inicio && inicio > d2) return false;
    return true;
  });
}

async function renderListado(tipo) {
  if (!tbodyListados) return;

  tbodyListados.innerHTML = `
    <tr><td colspan="8" class="text-center text-muted py-4">Cargando listado…</td></tr>`;

  try {
    const url = getListadoEndpoint(tipo);
    const payload = await fetchJson(url);

    let rows = normalizeListadoResponse(payload);

    rows = rows.map(r => ({
      ...r,
      estatus: r.estatus ?? tipo,
      folio: r.folio ?? r.num_orden ?? r.orden ?? '',
      producto: r.producto ?? r.nombre_producto ?? r.descripcion_producto ?? '',
      prioridad: r.prioridad ?? '',
      cantidad: r.cantidad ?? r.qty ?? r.cant ?? 0,
      inicio: r.inicio ?? r.fecha_inicio ?? '',
      requerida: r.requerida ?? r.fecha_requerida ?? ''
    }));

    rows = applyClientFilters(rows);

    tbodyListados.innerHTML = '';

    if (!rows.length) {
      tbodyListados.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">No hay registros para este filtro.</td></tr>`;
      return;
    }

    rows.forEach(r => {
      const idRow = String(r.id ?? r.idplaneacion ?? r.id_planeacion ?? '');
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="fw-semibold">${escapeHtml(r.folio)}</td>
        <td>${escapeHtml(r.producto)}</td>
        <td>${badgePrioridad(String(r.prioridad || '').trim())}</td>
        <td>${escapeHtml(String(r.cantidad ?? 0))}</td>
        <td>${escapeHtml(String(r.inicio || ''))}</td>
        <td>${escapeHtml(String(r.requerida || ''))}</td>
        <td>${badgeEstatus(String(r.estatus || tipo).trim())}</td>
        <td class="text-end">
  <a class="btn btn-outline-primary btn-sm me-1"
     href="${base_url}/plan_planeacion/orden/${encodeURIComponent(r.folio)}">
    <i class="ri-eye-line"></i>
    <span class="d-none d-md-inline">Ver</span>
  </a>

          <button type="button" class="btn btn-outline-danger btn-sm" data-action="Cancelar" data-id="${escapeHtml(idRow)}">
            <i class="ri-delete-bin-6-line"></i> <span class="d-none d-md-inline">Cancelar</span>
          </button>
        </td>
      `;
      tbodyListados.appendChild(tr);
    });

  } catch (err) {
    console.error(err);
    tbodyListados.innerHTML = `
      <tr><td colspan="8" class="text-center text-danger py-4">
        Error al cargar listado: ${escapeHtml(err.message || 'Error')}
      </td></tr>`;
  }
}



function detalleOrden(numOrden) {

  const url = base_url + '/plan_planeacion/orden/' + encodeURIComponent(numOrden);
  window.location.href = url;
}








// =====================================================
//  MANTENIMIENTO UI 
// =====================================================
function mantenimientoUI(mantTexto = "") {
  const t = String(mantTexto || "").toLowerCase();
  if (!t || t.includes("sin mantenimiento")) return "";

  let badgeClass = "text-bg-warning";
  let label = mantTexto;

  if (t.includes("en proceso")) badgeClass = "text-bg-danger";
  else if (t.includes("programado")) badgeClass = "text-bg-warning";

  const showLink = t.includes("programado") || t.includes("en proceso");

  return `
    <span class="badge ${badgeClass}">${escapeHtml(label)}</span>
    ${showLink ? `
      <a href="javascript:void(0)" class="small ms-2 link-primary text-decoration-underline" data-action="ver-mantenimiento">
        Ver mantenimiento
      </a>
    ` : ""}
  `;
}

function setBloqueoGuardarPorMantenimiento(isBlocked) {
  const alertBox = document.querySelector("#alertMantenimientoBloqueo");
  if (alertBox) alertBox.classList.toggle("d-none", !isBlocked);

  if (btnGuardarPlaneacion) btnGuardarPlaneacion.disabled = !!isBlocked;

  if (btnGuardarPlaneacion) {
    btnGuardarPlaneacion.title = isBlocked
      ? "No puedes guardar porque hay una estación En proceso de mantenimiento"
      : "";
  }
}

// =====================================================
//  TABLA ESTACIONES
// =====================================================
function renderEmptyTbody() {
  const tbody = document.querySelector("#tbodyEstaciones");
  if (!tbody) return;

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center text-muted py-4">
        Selecciona un producto para cargar su ruta.
      </td>
    </tr>
  `;

  const c = document.querySelector("#countEstaciones");
  if (c) c.textContent = "0";

  limpiarFaltantesUI();
  setBloqueoGuardarPorMantenimiento(false);
}

function renderTbodyEstaciones(detalle = []) {
  const tbody = document.querySelector("#tbodyEstaciones");
  if (!tbody) return;

  if (!Array.isArray(detalle) || detalle.length === 0) {
    tbody.innerHTML = `
      <tr>
        <td colspan="4" class="text-center text-muted py-4">
          No hay estaciones configuradas para este producto.
        </td>
      </tr>
    `;
    const c = document.querySelector("#countEstaciones");
    if (c) c.textContent = "0";
    setBloqueoGuardarPorMantenimiento(false);
    return;
  }

  const rows = [...detalle].sort((a, b) => Number(a.orden) - Number(b.orden));

  const hayEnProceso = rows.some(x =>
    String(x.mantenimiento_texto || "").toLowerCase().includes("en proceso")
  );
  setBloqueoGuardarPorMantenimiento(hayEnProceso);

  tbody.innerHTML = rows.map((st) => {
    const orden = Number(st.orden || 0);
    const estacionid = Number(st.estacionid || 0);

    const nombreRaw = (st.nombre_estacion || "");
    const nombre = escapeHtml(nombreRaw);
    const proceso = escapeHtml(st.proceso || "");
    const mantTxt = st.mantenimiento_texto || "";
    const mantHtml = mantenimientoUI(mantTxt);

    const sinComp = estacionesSinComponentes.has(estacionid);
    const sinHer  = estacionesSinHerramientas.has(estacionid);

    const compHtml = sinComp ? `
      <div class="mt-1">
        <button type="button"
          class="btn btn-link p-0 text-danger text-decoration-underline fw-semibold"
          data-action="ver-faltantes-comp"
          data-estacionid="${estacionid}"
          data-estacion-nombre="${escapeHtml(nombreRaw)}">
          <i class="ri-error-warning-line me-1"></i> Faltan componentes en inventario
        </button>
      </div>
    ` : '';

    const herHtml = sinHer ? `
      <div class="mt-1">
        <button type="button"
          class="btn btn-link p-0 text-danger text-decoration-underline fw-semibold"
          data-action="ver-faltantes-her"
          data-estacionid="${estacionid}"
          data-estacion-nombre="${escapeHtml(nombreRaw)}">
          <i class="ri-tools-line me-1"></i> Faltan herramientas en inventario
        </button>
      </div>
    ` : '';

    return `
      <tr data-estacionid="${estacionid}" data-orden="${orden}">
        <td class="fw-semibold">${orden}</td>

        <td>
          <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
            <span class="nombre-estacion">${nombre}</span>
            ${mantHtml ? `<span class="d-inline-flex align-items-center">${mantHtml}</span>` : ``}
          </div>

          <div class="text-muted small proceso-estacion">${proceso}</div>

          ${compHtml}
          ${herHtml}
        </td>

        <td>
          <div class="d-flex flex-column gap-1">
            <div class="text-muted small" id="ops_empty_${estacionid}">Sin operadores asignados</div>
            <div class="d-flex gap-1 flex-wrap" id="ops_${estacionid}"></div>
          </div>
        </td>

        <td class="text-end">
          <button type="button"
            class="btn btn-outline-primary btn-sm"
            onclick="abrirModalOperadores(${estacionid}, '${escapeHtml(nombreRaw)}', '${escapeHtml(st.proceso || "")}')">
            <i class="ri-user-add-line me-1"></i> Asignar
          </button>
        </td>
      </tr>
    `;
  }).join("");

  const c = document.querySelector("#countEstaciones");
  if (c) c.textContent = String(rows.length);

  restaurarAsignacionesEnTabla();
}

// =====================================================
//  API PRODUCTOSd / ESTACIONES
// =====================================================
async function fntProductos(selectedValue = "") {
  const selectLocal = document.querySelector('#selectProducto');
  if (!selectLocal) return;

  const ajaxUrl = base_url + '/plan_planeacion/getSelectProductos';

  showLoading();

  const request = (window.XMLHttpRequest)
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    hideLoading();

    if (request.status === 200) {
      selectLocal.innerHTML = request.responseText;

      if (selectedValue !== "") selectLocal.value = selectedValue;

      selectLocal.onchange = async function () {
        limpiarFaltantesUI();
        renderEmptyTbody();

        try { localStorage.removeItem(getLSKeyAsignaciones()); } catch (e) {}

        await fntEstaciones(this.value || "");
      };
    } else {
      console.error("Error cargando productos", request.status);
    }
  };
}

async function fntEstaciones(idProducto) {
  if (!idProducto) {
    renderEmptyTbody();
    return;
  }

  limpiarFaltantesUI(); 

  try {
    const ajaxUrl = base_url + "/Plan_planeacion/getSelectEstaciones/" + encodeURIComponent(idProducto);
    const rutas = await fetchJson(ajaxUrl);

    if (!Array.isArray(rutas) || rutas.length === 0) {
      renderTbodyEstaciones([]);
      return;
    }

    const ruta = rutas[0] || {};
    const detalle = Array.isArray(ruta.detalle) ? ruta.detalle : [];

    // validar componentes y herramientas para marcar estaciones
    await validarComponentesEnRuta(detalle);
    await validarHerramientasEnRuta(detalle);

    // pintams tabla
    renderTbodyEstaciones(detalle);

  } catch (error) {
    console.error("Error al cargar estaciones:", error);
    limpiarFaltantesUI();
    renderTbodyEstaciones([]);
  }
}

// =====================================================
//  VALIDAR COMPONENTES / HERRAMIENTAS 
// =====================================================
function buildPayloadValidacion(detalle = []) {
  const productoid = Number(document.querySelector('#selectProducto')?.value || 0);
  const cantidad = Number(document.querySelector('#txtCantidad')?.value || 0);

  const estaciones = (Array.isArray(detalle) ? detalle : [])
    .map(x => ({ estacionid: Number(x.estacionid || 0) }))
    .filter(x => x.estacionid > 0);

  return { productoid, cantidad, estaciones };
}

function normalizarErrores(resp) {
  if (!resp) return [];
  if (Array.isArray(resp.errores)) return resp.errores;
  if (Array.isArray(resp.data)) return resp.data;
  return [];
}

async function validarComponentesEnRuta(detalle) {
  const payload = buildPayloadValidacion(detalle);
  if (!payload.productoid || payload.cantidad <= 0 || payload.estaciones.length === 0) return;

  try {
    const resp = await fetchJson(base_url + '/plan_planeacion/validarExistencias', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });


    if (resp && resp.status === false) {
      const errores = normalizarErrores(resp);

      errores.forEach(item => {
        const id = Number(item.estacionid || 0);
        if (!id) return;

        estacionesSinComponentes.add(id);

        if (!faltantesComponentesMap[id]) faltantesComponentesMap[id] = [];
        faltantesComponentesMap[id].push(item);
      });
    }
  } catch (e) {
    console.error("Error validarComponentesEnRuta:", e);
  }
}


async function validarHerramientasEnRuta(detalle) {
  const payload = buildPayloadValidacion(detalle);
  if (!payload.productoid || payload.cantidad <= 0 || payload.estaciones.length === 0) return;

  try {
    const resp = await fetchJson(base_url + '/plan_planeacion/validarHerramientasExistencias', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (resp && resp.status === false) {
      const errores = normalizarErrores(resp);
      errores.forEach(item => {
        const id = Number(item.estacionid || 0);
        if (!id) return;
        estacionesSinHerramientas.add(id);
        if (!faltantesHerramientasMap[id]) faltantesHerramientasMap[id] = [];
        faltantesHerramientasMap[id].push(item);
      });
    }
  } catch (e) {
    console.error("Error validarHerramientasEnRuta:", e);
  }
}

async function validarComponentesAntesDeGuardar(payloadPlaneacion) {
  const payload = {
    productoid: payloadPlaneacion.header.productoid,
    cantidad: payloadPlaneacion.header.cantidad,
    estaciones: payloadPlaneacion.estaciones.map(x => ({ estacionid: x.estacionid }))
  };
  return await fetchJson(base_url + '/plan_planeacion/validarExistencias', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
}

async function validarHerramientasAntesDeGuardar(payloadPlaneacion) {
  const payload = {
    productoid: payloadPlaneacion.header.productoid,
    cantidad: payloadPlaneacion.header.cantidad,
    estaciones: payloadPlaneacion.estaciones.map(x => ({ estacionid: x.estacionid }))
  };
  return await fetchJson(base_url + '/plan_planeacion/validarHerramientasExistencias', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
}

// =====================================================
//  MODALES FALTANTES (COMP / HER)
// =====================================================
function abrirModalFaltantesComponentes(estacionid, nombreEstacion = '') {
  const modalEl = document.getElementById('modalFaltantesInventario');
  if (!modalEl) {
    Swal.fire({ icon:'warning', title:'Falta modal', text:'No existe el modal de faltantes de componentes.' });
    return;
  }

  const title = modalEl.querySelector('#titleModalFaltantes');
  const subt  = modalEl.querySelector('#subTitleFaltantes');
  const tbody = modalEl.querySelector('#tbodyFaltantes');

  if (title) title.textContent = 'Faltantes de componentes';
  if (subt)  subt.textContent  = `Estación: ${nombreEstacion || ('ID ' + estacionid)}`;

  const items = faltantesComponentesMap[estacionid] || [];

  console.log(items);

  if (tbody) {
    tbody.innerHTML = !items.length
      ? `<tr><td colspan="5" class="text-center text-muted py-3">No hay detalle de faltantes.</td></tr>`
      : items.map(x => `
          <tr>
            <td>${escapeHtml(String(x.descripcion ?? ''))}</td>
            <td class="text-end">${formatNumber(x.requerido)}</td>
            <td class="text-end">${formatNumber(x.existencia)}</td>
            <td class="text-end fw-semibold text-danger">${formatNumber(x.faltante)}</td>
            <td>${escapeHtml(String(x.descripcion_almacen ?? ''))}</td>
          </tr>
        `).join('');
  }

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}





function abrirModalFaltantesHerramientas(estacionid, nombreEstacion = '') {
  const modalEl = document.getElementById('modalFaltantesHerramientas');
  if (!modalEl) {
    Swal.fire({ icon:'warning', title:'Falta modal', text:'No existe el modal de faltantes de herramientas.' });
    return;
  }

  const title = modalEl.querySelector('#titleModalFaltantesHer');
  const subt  = modalEl.querySelector('#subTitleFaltantesHer');
  const tbody = modalEl.querySelector('#tbodyFaltantesHer');

  if (title) title.textContent = 'Faltantes de herramientas';
  if (subt)  subt.textContent  = `Estación: ${nombreEstacion || ('ID ' + estacionid)}`;

  const items = faltantesHerramientasMap[estacionid] || [];

   console.log(items);



  if (tbody) {
    tbody.innerHTML = !items.length ? `
      <tr><td colspan="5" class="text-center text-muted py-3">No hay detalle de faltantes.</td></tr>
    ` : items.map(x => `
      <tr>
        <td>${escapeHtml(String(x.descripcion ?? ''))}</td>
        <td class="text-end">${formatNumber(x.requerido)}</td>
        <td class="text-end">${formatNumber(x.existencia)}</td>
        <td class="text-end fw-semibold text-danger">${formatNumber(x.faltante)}</td>
        <td>${escapeHtml(String(x.descripcion_almacen ?? ''))}</td>
      </tr>
    `).join('');
  }

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}


function formatNumber(value) {
  if (value === null || value === undefined || value === '') return '';
  return Number(value).toLocaleString('es-MX'); 

}

// =====================================================
//  MODAL ASIGNACIÓN OPERADORES (IGUAL)
// =====================================================
function abrirModalOperadores(estacionid, nombreEstacion, proceso) {
  cargarOperadoresDisponibles();
  cargarOperadoresAyudantes();

  const title = document.querySelector("#titleModal");
  if (title) title.textContent = "Agregar operadores";

  const nom = document.querySelector("#modalEstacionNombre");
  const pro = document.querySelector("#modalEstacionProceso");
  const hid = document.querySelector("#modalEstacionId");

  if (nom) nom.textContent = nombreEstacion || "—";
  if (pro) pro.textContent = proceso || "—";
  if (hid) hid.value = estacionid || "";

  const modalEl = document.getElementById("modalAddOperador");
  if (!modalEl) return console.error("No existe #modalAddOperador");
  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  setTimeout(() => {
    cargarAsignacionEnModal(estacionid);
    aplicarBloqueoAyudantes(estacionid);
  }, 300);
}

function cargarOperadoresDisponibles() {
  const sel = document.querySelector('#listOperadores');
  if (!sel) return;

  const ajaxUrl = base_url + '/plan_planeacion/getSelectOperadores';
  showLoading();

  const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    hideLoading();
    if (request.status === 200) {
      sel.innerHTML = `<option value="" selected>-- Selecciona encargado --</option>` + request.responseText;
    } else {
      console.error("Error cargando encargados", request.status);
    }
  };
}

function cargarOperadoresAyudantes() {
  const sel = document.querySelector('#selectAyudantes');
  if (!sel) return;

  const ajaxUrl = base_url + '/plan_planeacion/getSelectOperadoresAyudantes';
  showLoading();

  const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    hideLoading();
    if (request.status === 200) {
      sel.innerHTML = request.responseText;
    } else {
      console.error("Error cargando ayudantes", request.status);
    }
  };
}

function getLSKeyAsignaciones() {
  const prod = document.querySelector('#selectProducto')?.value || '0';
  return `plan_asignaciones_prod_${prod}`;
}

function getAsignacionesLS() {
  try {
    const raw = localStorage.getItem(getLSKeyAsignaciones());
    return raw ? JSON.parse(raw) : {};
  } catch (e) {
    console.error("JSON inválido en localStorage", e);
    return {};
  }
}

function setAsignacionesLS(obj) {
  localStorage.setItem(getLSKeyAsignaciones(), JSON.stringify(obj || {}));
}

function cargarAsignacionEnModal(estacionid) {
  const asignaciones = getAsignacionesLS();
  const data = asignaciones[String(estacionid)];

  const encSel = document.querySelector('#listOperadores');
  const aySel = document.querySelector('#selectAyudantes');

  if (!data) {
    if (encSel) encSel.value = "";
    if (aySel) Array.from(aySel.options).forEach(o => o.selected = false);
    return;
  }

  if (encSel && data.encargado !== null) encSel.value = String(data.encargado);

  if (aySel) {
    const setIds = new Set((data.ayudantes || []).map(String));
    Array.from(aySel.options).forEach(opt => {
      opt.selected = setIds.has(String(opt.value));
    });
  }
}

function onAplicarAsignacion() {
  const estacionid = document.querySelector('#modalEstacionId')?.value || "";
  if (!estacionid) return;

  const encargado = document.querySelector('#listOperadores')?.value || "";
  const selAy = document.querySelector('#selectAyudantes');
  const ayudantes = selAy ? Array.from(selAy.selectedOptions).map(o => o.value) : [];

  const asignaciones = getAsignacionesLS();
  asignaciones[String(estacionid)] = {
    encargado: encargado ? Number(encargado) : null,
    ayudantes: ayudantes.map(x => Number(x)),
    updated_at: new Date().toISOString()
  };
  setAsignacionesLS(asignaciones);

  pintarOperadoresEnFila(estacionid);

  Swal.fire({
    icon: "success",
    title: "Guardado",
    text: "Se guardó correctamente la asignación de operadores.",
    timer: 1200,
    showConfirmButton: false
  });

  const modalEl = document.getElementById('modalAddOperador');
  if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
}

function pintarOperadoresEnFila(estacionid) {
  const asignaciones = getAsignacionesLS();
  const data = asignaciones[String(estacionid)];

  const cont = document.querySelector(`#ops_${estacionid}`);
  const empty = document.querySelector(`#ops_empty_${estacionid}`);
  if (!cont) return;

  if (!data || (!data.encargado && (!data.ayudantes || data.ayudantes.length === 0))) {
    cont.innerHTML = '';
    if (empty) empty.classList.remove('d-none');
    return;
  }

  if (empty) empty.classList.add('d-none');

  const selEnc = document.querySelector('#listOperadores');
  const selAy = document.querySelector('#selectAyudantes');

  const textoEnc = data.encargado
    ? (selEnc?.querySelector(`option[value="${data.encargado}"]`)?.textContent || `Encargado: ${data.encargado}`)
    : '';

  const textosAy = (data.ayudantes || []).map(id => {
    return selAy?.querySelector(`option[value="${id}"]`)?.textContent || `Ayudante: ${id}`;
  });

  cont.innerHTML = `
    ${textoEnc ? `<span class="badge text-bg-primary">${escapeHtml(textoEnc)}</span>` : ``}
    ${textosAy.map(t => `<span class="badge text-bg-secondary">${escapeHtml(t)}</span>`).join(" ")}
  `;
}

function restaurarAsignacionesEnTabla() {
  const asignaciones = getAsignacionesLS();
  Object.keys(asignaciones).forEach(estacionid => {
    pintarOperadoresEnFila(estacionid);
  });
}

// =====================================================
//  BLOQUEO AYUDANTES 
// =====================================================
function getAyudantesUsados(exceptEstacionId = null) {
  const asignaciones = getAsignacionesLS();
  const usados = new Set();

  Object.keys(asignaciones).forEach((estId) => {
    if (exceptEstacionId && String(estId) === String(exceptEstacionId)) return;
    const arr = asignaciones[estId]?.ayudantes || [];
    arr.forEach((id) => usados.add(String(id)));
  });

  return usados;
}

function aplicarBloqueoAyudantes(estacionidActual) {
  const sel = document.querySelector('#selectAyudantes');
  if (!sel) return;

  const usados = getAyudantesUsados(estacionidActual);
  const asignaciones = getAsignacionesLS();
  const current = new Set((asignaciones[String(estacionidActual)]?.ayudantes || []).map(String));

  Array.from(sel.options).forEach(opt => {
    const id = String(opt.value || "");
    if (!id) return;
    opt.disabled = usados.has(id) && !current.has(id);
  });
}

// =====================================================
//  PAYLOAD / VALIDACIONES 
// =====================================================
function getHeaderPlaneacion() {
  return {
    productoid: Number(document.querySelector('#selectProducto')?.value || 0),
    pedido: document.querySelector('#numPedido')?.value || "",
    supervisor: (document.querySelector('#inputSupervisor')?.value || "").trim(),
    prioridad: (document.querySelector('#selectPrioridad')?.value || "").trim(),
    cantidad: Number(document.querySelector('#txtCantidad')?.value || 0),
    fecha_inicio: (document.querySelector('#fechaInicio')?.value || "").trim(),
    fecha_requerida: (document.querySelector('#fechaRequerida')?.value || "").trim(),
    notas: (document.querySelector('#txtNotas')?.value || "").trim()
  };
}

function getEstacionesDeTabla() {
  const rows = Array.from(document.querySelectorAll('#tbodyEstaciones tr[data-estacionid]'));
  return rows.map(tr => ({
    estacionid: Number(tr.dataset.estacionid || 0),
    orden: Number(tr.dataset.orden || 0)
  })).filter(x => x.estacionid > 0);
}

function getAsignacionesParaGuardar() {
  const asignaciones = getAsignacionesLS();
  const estaciones = getEstacionesDeTabla();

  return estaciones.map(s => {
    const a = asignaciones[String(s.estacionid)] || null;
    return {
      estacionid: s.estacionid,
      orden: s.orden,
      encargado: a?.encargado ?? null,
      ayudantes: Array.isArray(a?.ayudantes) ? a.ayudantes : []
    };
  });
}

function buildPayloadPlaneacion() {
  return {
    header: getHeaderPlaneacion(),
    estaciones: getEstacionesDeTabla(),
    asignaciones: getAsignacionesParaGuardar()
  };
}

function validarAsignacionesCompletas() {
  const estaciones = getEstacionesDeTabla();
  const asignaciones = getAsignacionesLS();
  const faltantes = [];

  estaciones.forEach(s => {
    const a = asignaciones[String(s.estacionid)];
    const encargadoOk = !!(a && a.encargado);
    const ayudantesOk = !!(a && Array.isArray(a.ayudantes) && a.ayudantes.length > 0);

    if (!encargadoOk || !ayudantesOk) {
      faltantes.push({
        estacionid: s.estacionid,
        orden: s.orden,
        faltaEncargado: !encargadoOk,
        faltaAyudantes: !ayudantesOk
      });
    }
  });

  return faltantes;
}

function resaltarFilasIncompletas(faltantes) {
  document.querySelectorAll('#tbodyEstaciones tr[data-estacionid]').forEach(tr => {
    tr.classList.remove('table-danger');
  });

  faltantes.forEach(f => {
    const tr = document.querySelector(`#tbodyEstaciones tr[data-estacionid="${f.estacionid}"]`);
    if (tr) tr.classList.add('table-danger');
  });
}

// =====================================================
//  GUARDAR PLANEACIÓN 
// =====================================================
async function guardarPlaneacionHandler() {
  const payload = buildPayloadPlaneacion();

  if (!payload.header.productoid) {
    Swal.fire({ icon: 'warning', title: 'Falta producto', text: 'Selecciona un producto.' });
    return;
  }
  if (!payload.header.prioridad) {
    Swal.fire({ icon: 'warning', title: 'Falta prioridad', text: 'Selecciona la prioridad.' });
    return;
  }
  if (!payload.header.cantidad || payload.header.cantidad < 1) {
    Swal.fire({ icon: 'warning', title: 'Cantidad inválida', text: 'La cantidad debe ser mayor a 0.' });
    return;
  }
  if (!payload.header.fecha_inicio || !payload.header.fecha_requerida) {
    Swal.fire({ icon: 'warning', title: 'Faltan fechas', text: 'Selecciona fecha de inicio y requerida.' });
    return;
  }

  //  VALIDAR COMPONENTES
  try {
    const valComp = await validarComponentesAntesDeGuardar(payload);
    if (valComp && valComp.status === false) {
      Swal.fire({ icon:'warning', title:'Sin existencias', text: valComp.msg || 'Faltan componentes.' });
      await fntEstaciones(String(payload.header.productoid));
      return;
    }
  } catch (e) {
    console.error(e);
    Swal.fire({ icon:'error', title:'Error', text:'No se pudo validar componentes.' });
    return;
  }

  // VALIDAR HERRAMIENTAS
  try {
    const valHer = await validarHerramientasAntesDeGuardar(payload);
    if (valHer && valHer.status === false) {
      Swal.fire({ icon:'warning', title:'Sin existencias', text: valHer.msg || 'Faltan herramientas.' });
      await fntEstaciones(String(payload.header.productoid));
      return;
    }
  } catch (e) {
    console.error(e);
    Swal.fire({ icon:'error', title:'Error', text:'No se pudo validar herramientas.' });
    return;
  }

  // VALIDAR OPERADORES
  const faltantes = validarAsignacionesCompletas();
  if (faltantes.length > 0) {
    resaltarFilasIncompletas(faltantes);

    const lista = faltantes
      .sort((a, b) => a.orden - b.orden)
      .map(x => `• Estación orden ${x.orden}: ${x.faltaEncargado ? 'sin encargado' : ''}${(x.faltaEncargado && x.faltaAyudantes) ? ' y ' : ''}${x.faltaAyudantes ? 'sin ayudantes' : ''}`)
      .join('<br>');

    Swal.fire({
      icon: 'warning',
      title: 'Faltan asignaciones',
      html: `<div class="text-start">
              <div class="fw-bold mb-2">Todas las estaciones necesitan gente de operación para trabajar:</div>
              ${lista}
            </div>`
    });
    return;
  }

  //  GUARDARS
  try {
    const data = await fetchJson(base_url + '/plan_planeacion/setPlaneacion', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!data.status) throw new Error(data.msg || 'Error al guardar');

    Swal.fire({ icon: 'success', title: 'Guardado', text: 'Planeación guardada correctamente.' });

    localStorage.removeItem(getLSKeyAsignaciones());
    await limpiarNuevaPlaneacion(false);
    goHome();

  } catch (err) {
    console.error(err);
    Swal.fire({ icon: 'error', title: 'Error', text: err.message });
  }
}

// =====================================================
//  LIMPIEZA MÁS CONFIRMACIONES 
// =====================================================
async function limpiarNuevaPlaneacion(limpiarLS = true) {
  const selProd = document.querySelector('#selectProducto');
  const sup = document.querySelector('#inputSupervisor');
  const pri = document.querySelector('#selectPrioridad');
  const cant = document.querySelector('#txtCantidad');
  const ped = document.querySelector('#numPedido');
  const fi = document.querySelector('#fechaInicio');
  const fr = document.querySelector('#fechaRequerida');
  const notas = document.querySelector('#txtNotas');

  if (selProd) selProd.value = "";
   if (ped) ped.value = "";
  if (sup) sup.value = "";
  if (pri) pri.value = "";
  if (cant) cant.value = 1;
  if (fi) fi.value = "";
  if (fr) fr.value = "";
  if (notas) notas.value = "";

  renderEmptyTbody();
  document.querySelectorAll('#tbodyEstaciones tr').forEach(tr => tr.classList.remove('table-danger'));
  setBloqueoGuardarPorMantenimiento(false);

  if (limpiarLS) {
    try { localStorage.removeItem(getLSKeyAsignaciones()); } catch (e) {}
  }

  const enc = document.querySelector('#listOperadores');
  const ay = document.querySelector('#selectAyudantes');
  if (enc) enc.innerHTML = `<option value="">-- Selecciona encargado --</option>`;
  if (ay) ay.innerHTML = ``;
}

async function confirmarDescartarSiHayBorrador() {
  const asignaciones = getAsignacionesLS();
  const hayAlgo = asignaciones && Object.keys(asignaciones).length > 0;
  if (!hayAlgo) return true;

  const res = await Swal.fire({
    icon: 'warning',
    title: 'Tienes asignaciones sin guardar',
    text: '¿Deseas cancelar y borrar la información capturada?',
    showCancelButton: true,
    confirmButtonText: 'Sí, cancelar',
    cancelButtonText: 'No'
  });

  return !!res.isConfirmed;
}
