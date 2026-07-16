
const viewHome = document.getElementById('viewHome');
const viewNueva = document.getElementById('viewNueva');
const viewListado = document.getElementById('viewListado');

const btnNuevaPlaneacion = document.getElementById('btnNuevaPlaneacion');
const btnPendientes = document.getElementById('btnPendientes');
const btnFinalizadas = document.getElementById('btnFinalizadas');
const btnProceso = document.getElementById('btnProceso');

const btnVolverHome1 = document.getElementById('btnVolverHome1');
const btnVolverHome2 = document.getElementById('btnVolverHome2');


const btnCancelarNueva = document.getElementById('btnCancelarNueva');
const btnGuardarPlaneacion = document.getElementById('btnGuardarPlaneacion');


const badgeListado = document.getElementById('badgeListado');
const breadcrumbListado = document.getElementById('breadcrumbListado');
const listadoTitulo = document.getElementById('listadoTitulo');
const listadoSubtitulo = document.getElementById('listadoSubtitulo');
const tbodyListados = document.getElementById('tbodyListados');
const btnRefrescarListado = document.getElementById('btnRefrescarListado');


const filterSearch = document.getElementById('filterSearch');
const filterDesde = document.getElementById('filterDesde');
const filterHasta = document.getElementById('filterHasta');
const filterPrioridad = document.getElementById('filterPrioridad');

let currentListado = null;


let estacionesSinComponentes = new Set();
let estacionesSinHerramientas = new Set();

let faltantesComponentesMap = {};
let faltantesHerramientasMap = {};



let subensamblesSinComponentes = new Set();

let faltantesSubensamblesMap = {};





function limpiarFaltantesUI() {
  estacionesSinComponentes = new Set();
  subensamblesSinComponentes = new Set();

  estacionesSinHerramientas = new Set();

  faltantesComponentesMap = {};
  faltantesSubensamblesMap = {};
  faltantesHerramientasMap = {};
}


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
      throw new Error("El servidor no devolvió el JSON");
    }

    if (!res.ok) throw new Error(data?.msg || `HTTP ${res.status}`);
    return data;
  } finally {
    hideLoading();
  }
}


function pad2(n) { return String(n).padStart(2, '0'); }
function todayYYYYMMDD() {
  const d = new Date();
  return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
}

function setMinFechaInicioHoy() {
  const fi = document.querySelector('#fechaInicio');
  if (!fi) return;

  const hoy = todayYYYYMMDD();
  fi.setAttribute('min', hoy);


  if (fi.value && fi.value < hoy) fi.value = hoy;
}

function setMinFechaRequeridaFromInicio() {
  const fi = document.querySelector('#fechaInicio');
  const fr = document.querySelector('#fechaRequerida');
  if (!fi || !fr) return;

  const hoy = todayYYYYMMDD();
  const inicio = fi.value || hoy;


  fr.setAttribute('min', inicio);


  if (fr.value && fr.value < inicio) fr.value = inicio;
}

function initValidacionFechas() {
  const fi = document.querySelector('#fechaInicio');
  const fr = document.querySelector('#fechaRequerida');

  setMinFechaInicioHoy();
  setMinFechaRequeridaFromInicio();

  if (fi) {
    fi.addEventListener('change', () => {
      setMinFechaInicioHoy();
      setMinFechaRequeridaFromInicio();
    });
    fi.addEventListener('input', () => {
      setMinFechaInicioHoy();
      setMinFechaRequeridaFromInicio();
    });
  }

  if (fr) {
    fr.addEventListener('change', () => {
      setMinFechaRequeridaFromInicio();
    });
    fr.addEventListener('input', () => {
      setMinFechaRequeridaFromInicio();
    });
  }
}

// =====================================================
//  INIT
// =====================================================
document.addEventListener('DOMContentLoaded', function () {
  divLoading = document.querySelector("#divLoading");
  hideLoading();


  initValidacionFechas();

  fntProductos();

  fntSupervisores();

  const btnAplicarCalidad = document.querySelector('#btnAplicarCalidad');
if (btnAplicarCalidad) {
  btnAplicarCalidad.addEventListener('click', onAplicarCalidad);
}

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

  const selEnc = document.querySelector('#listOperadores');
  if (selEnc) {
    selEnc.addEventListener('change', () => {
      const estacionid = document.querySelector('#modalEstacionId')?.value || "";
      if (!estacionid) return;
      aplicarBloqueoEncargados(estacionid);
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

    const btnCompSub = e.target.closest('[data-action="ver-faltantes-comp-sub"]');
if (btnCompSub) {
  const idsubensamble = Number(btnCompSub.getAttribute('data-idsubensamble') || 0);
  const nombre = btnCompSub.getAttribute('data-subensamble-nombre') || '';
  abrirModalFaltantesComponentesSubensamble(idsubensamble, nombre);
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

  if (btnPendientes) btnPendientes.addEventListener('click', () => goListado('PENDIENTE'));
  if (btnFinalizadas) btnFinalizadas.addEventListener('click', () => goListado('FINALIZADA'));
  if (btnProceso) btnProceso.addEventListener('click', () => goListado('PROCESO'));

  if (btnNuevaPlaneacion) {
    btnNuevaPlaneacion.addEventListener('click', async () => {
      await limpiarNuevaPlaneacion(true);
      goNueva();
           await initFechaInicioPicker();
           initFechaRequeridaPicker();
    });
  }

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

  // =====================================================
  //  CALENDS
  // =====================================================
  const calendarEl = document.getElementById('calendar');
  if (!calendarEl) return;

  const calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    height: 'auto',
    expandRows: true,

    headerToolbar: {
      left: 'prev,next today',
      center: 'title',
      right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },

    buttonText: { today: 'Hoy', month: 'Mes', week: 'Semana', day: 'Día' },

    events: async (info, successCallback, failureCallback) => {
      try {
        const eventos = await cargarOrdenesParaCalendar();
        successCallback(eventos);
      } catch (err) {
        console.error(err);
        failureCallback(err);
      }
    },

    eventDidMount: function (info) {
      const bg = info.event.backgroundColor;

      if (bg) {
        info.el.style.backgroundColor = bg;
        info.el.style.borderColor = bg;
        info.el.style.color = '#ffffff';
      }

      const folio = info.event.extendedProps?.num_orden || info.event.title;
      const label = info.event.extendedProps?.fase_label || 'Sin estatus';
      info.el.setAttribute('title', `${label} • Orden de trabajo #${folio}`);
    },

    eventClick: (info) => {
      const folio = info.event.extendedProps?.num_orden || info.event.title;
      if (!folio) return;

      abrirModalPlaneacionDesdeCalendar({ folio });
    }
  });

  calendar.render();
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
  [btnPendientes, btnFinalizadas, btnProceso].filter(Boolean).forEach(b => b.classList.remove('active'));
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
    badgeListado.innerHTML = '<i class="ri-close-circle-line me-1"></i> En proceso';
    breadcrumbListado.textContent = 'Inicio → Planeación → En proceso';
    listadoTitulo.textContent = 'Planeaciones en proceso';
    listadoSubtitulo.textContent = 'Órdenes en proceso. Revisión de motivo y control.';
    setActiveNav(btnProceso);
  }

  await renderListado(tipo);
}

// =====================================================
//  LISTADO
// =====================================================
function getListadoEndpoint(tipo) {
  if (tipo === 'PENDIENTE') return base_url + '/plan_planeacionv1/getPendientes';
  if (tipo === 'FINALIZADA') return base_url + '/plan_planeacionv1/getFinalizadas';
  return base_url + '/plan_planeacionv1/getEnProceso';
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

function verificarFechasDisponibles(){
    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');

      const ajaxUrl = base_url + '/plan_planeacionv1/getSelectDates';



    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);

        }
    }
}

async function renderListado(tipo) {
  if (!tbodyListados) return;

  tbodyListados.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-4">Cargando listado…</td></tr>`;

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
             href="${base_url}/plan_planeacionv1/ordenv1/${encodeURIComponent(r.folio)}">
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
  const url = base_url + '/plan_planeacionv1/ordenv1/' + encodeURIComponent(numOrden);
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

  let contadorGlobalSubensambles = 0;



  tbody.innerHTML = rows.map((st) => { 
    const orden = Number(st.orden || 0);
    const estacionid = Number(st.estacionid || 0);
    const estampado = Number(st.estampado || 0);
    const operaciones = Number(st.operaciones || 0);
     const calidad = Number(st.calidad || 0);
     const especificaciones = Number(st.especificaciones || 0);
    const tieneSubensamble = Number(st.tiene_subensamble || 0);

    const nombreRaw = st.nombre_estacion || "";
    const nombre = escapeHtml(nombreRaw);
    const proceso = escapeHtml(st.proceso || "");
    const mantTxt = st.mantenimiento_texto || "";
    const mantHtml = mantenimientoUI(mantTxt);

    const sinComp = estacionesSinComponentes.has(estacionid);
    const sinHer = estacionesSinHerramientas.has(estacionid);

    const compHtml = sinComp ? `
      <div class="mt-1">
        <button type="button"
          class="btn btn-link p-0 text-danger text-decoration-underline fw-semibold"
          data-action="ver-faltantes-comp"
          data-estacionid="${estacionid}"
          data-especificaciones="${especificaciones}"
          data-estacion-nombre="${escapeHtml(nombreRaw)}"
          data-operaciones="${operaciones}">
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
          data-especificaciones="${especificaciones}"
          data-estacion-nombre="${escapeHtml(nombreRaw)}"
           data-operaciones="${operaciones}">
          <i class="ri-tools-line me-1"></i> Faltan herramientas en inventario
        </button>
      </div>
    ` : '';




    

    let rowsSubensambles = "";

    if (tieneSubensamble === 1 && Array.isArray(st.subensambles) && st.subensambles.length > 0) {
      rowsSubensambles = st.subensambles.map((sub) => {
        contadorGlobalSubensambles++;

        const idsubensamble = Number(sub.idsubensamble || 0);
        const subNombreRaw = sub.nombre_estacion || `Subensamble ${contadorGlobalSubensambles}`;
        const subProcesoRaw = sub.proceso || "";

        const subNombre = escapeHtml(subNombreRaw);
        const subProceso = escapeHtml(subProcesoRaw);


    const sinCompSub = subensamblesSinComponentes.has(idsubensamble);

    const compSubHtml = sinCompSub ? `
      <div class="mt-2">
        <button type="button"
          class="btn btn-link p-0 text-danger text-decoration-underline fw-semibold"
          data-action="ver-faltantes-comp-sub"
          data-idsubensamble="${idsubensamble}"
          data-subensamble-nombre="${escapeHtml(subNombreRaw)}">
          <i class="ri-error-warning-line me-1"></i> Faltan componentes en inventario
        </button>
      </div>
    ` : '';

        return `
          <tr
            class="tr-subensamble"
            data-tipo="subensamble"
            data-estacionid="${estacionid}"
            data-especificaciones="${especificaciones}"
            data-idsubensamble="${idsubensamble}"
            data-parent-orden="${orden}"
            data-orden-sub="S${contadorGlobalSubensambles}"
          >
            <td class="fw-semibold bg-primary-subtle border-start">
              <div class="d-flex align-items-center gap-2 ps-2">
                <i class="ri-corner-down-right-line text-primary"></i>
                <span class="text-primary">S${contadorGlobalSubensambles}</span>
              </div>
            </td>

            <td class="bg-primary-subtle">
              <div class="ms-3 rounded-3 px-3 py-2 border border-primary-subtle bg-body">
                <div class="small fw-semibold text-primary mb-1 d-flex align-items-center gap-2 flex-wrap">
                  <i class="ri-git-branch-line"></i>
                  <span>${subNombre}</span>
                  <span class="badge bg-info-subtle text-info border border-info-subtle">
                    Subensamble de ${nombre}
                  </span>
                </div>

                <div class="small text-muted">
                  ${subProceso || "Sin descripción de proceso"}
                </div>

                ${compSubHtml}
              </div>
            </td>

            <td class="bg-primary-subtle">
              <div class="d-flex flex-column gap-1">
                <div class="text-muted small" id="ops_empty_sub_${idsubensamble}">Sin operadores asignados</div>
                <div class="d-flex gap-1 flex-wrap" id="ops_sub_${idsubensamble}"></div>
              </div>
            </td>

            <td class="text-end bg-primary-subtle">
              <button type="button"
                class="btn btn-outline-primary btn-sm"
                onclick="abrirModalOperadoresSubensamble(${idsubensamble}, ${estacionid}, '${escapeHtml(subNombreRaw)}', '${escapeHtml(subProcesoRaw)}')">
                <i class="ri-user-add-line me-1"></i> Asignar
              </button>
            </td>
          </tr>
        `;
      }).join("");
    }

    const accionesCalidadHtml = `
  ${especificaciones === 1 ? `
    <div class="alert alert-warning-subtle border mt-2 mb-0 py-2">
      <div class="small">
        <i class="ri-error-warning-line me-1"></i>
        Esta estación tiene puntos críticos configurados. Debes asignar personal para su validación.
      </div>
      <button type="button"
        class="btn btn-outline-warning btn-sm mt-2"
        onclick="abrirModalCalidad(${estacionid}, '${escapeHtml(nombreRaw)}', '${escapeHtml(st.proceso || "")}', 'CRITICOS')">
        <i class="ri-user-star-line me-1"></i> Asignar críticos
      </button>
    </div>
  ` : ''}

  ${calidad === 1 ? `
    <div class="alert alert-info-subtle border mt-2 mb-0 py-2">
      <div class="small">
        <i class="ri-shield-check-line me-1"></i>
        Esta estación tiene PDI configurado. Selecciona el personal que realizará la evaluación.
      </div>
      <button type="button"
        class="btn btn-outline-info btn-sm mt-2"
        onclick="abrirModalCalidad(${estacionid}, '${escapeHtml(nombreRaw)}', '${escapeHtml(st.proceso || "")}', 'PDI')">
        <i class="ri-user-search-line me-1"></i> Asignar PDI
      </button>
    </div>
  ` : ''}

  <div class="d-flex gap-1 flex-wrap mt-2" id="calidad_${estacionid}"></div>
`;



    const rowEstacion = `
      <tr
        data-tipo="estacion"
        data-estacionid="${estacionid}"
        data-especificaciones="${especificaciones}"
        data-orden="${orden}"
         data-calidad="${calidad}"
        data-estampado="${estampado}"
        data-operaciones="${operaciones}">
        <td class="fw-semibold">${orden}</td>

        <td>
          <div class="fw-semibold d-flex align-items-center gap-2 flex-wrap">
            <span class="nombre-estacion">${nombre}</span>
            ${mantHtml ? `<span class="d-inline-flex align-items-center">${mantHtml}</span>` : ``}
            ${tieneSubensamble === 1 ? `
              <span class="badge bg-light text-body border">
                <i class="ri-layout-top-line me-1"></i> Estación padre
              </span>
            ` : ``}
          </div>

          <div class="text-muted small proceso-estacion">${proceso}</div>

          ${compHtml}
          ${herHtml}
          ${accionesCalidadHtml}
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

    return rowsSubensambles + rowEstacion;
  }).join("");

  const c = document.querySelector("#countEstaciones");
  if (c) c.textContent = String(rows.length);

  restaurarAsignacionesEnTabla();
  restaurarCalidadEnTabla();
}

// =====================================================
//  API PRODUCTOS / ESTACIONES
// =====================================================
async function fntProductos(selectedValue = "") {
  const selectLocal = document.querySelector('#selectProducto');
  if (!selectLocal) return;

  const ajaxUrl = base_url + '/plan_planeacionv1/getSelectProductos';

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

        try { localStorage.removeItem(getLSKeyAsignaciones()); } catch (e) { }
            //  localStorage.removeItem(getLSKeyCalidad);
              try { localStorage.removeItem(getLSKeyCalidad()); } catch (e) { }
             console.log('debería de limpiarse');
            //  await limpiarNuevaPlaneacion(true);

        await fntEstaciones(this.value || "");
      };
    } else {
      console.error("Error cargando productos", request.status);
    }
  };
}




// =====================================================
//  api para obtener os supervispres
// =====================================================
async function fntSupervisores(selectedValue = "") {
  const selectLocalS = document.querySelector('#selectSupervisor');
  if (!selectLocalS) return;

  const ajaxUrl = base_url + '/plan_planeacionv1/getSelectSupervisor';

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
      selectLocalS.innerHTML = request.responseText;

      if (selectedValue !== "") selectLocalS.value = selectedValue;

      // selectLocalS.onchange = async function () {
      //   limpiarFaltantesUI();
      //   renderEmptyTbody();

      //   try { localStorage.removeItem(getLSKeyAsignaciones()); } catch (e) { }

      //   await fntEstaciones(this.value || "");
      // };
    } else {
      console.error("Error cargando supervisores", request.status);
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
    const ajaxUrl = base_url + "/Plan_planeacionv1/getSelectEstaciones/" + encodeURIComponent(idProducto);
    const rutas = await fetchJson(ajaxUrl);

    if (!Array.isArray(rutas) || rutas.length === 0) {
      renderTbodyEstaciones([]);
      return;
    }

    const ruta = rutas[0] || {};
    const detalle = Array.isArray(ruta.detalle) ? ruta.detalle : [];

    await validarComponentesEnRuta(detalle);
    // await validarHerramientasEnRuta(detalle);

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

  const estaciones = [];
  const subensambles = [];

  (Array.isArray(detalle) ? detalle : []).forEach(x => {
    const estacionid = Number(x.estacionid || 0);
    if (estacionid > 0) {
      estaciones.push({ estacionid });
    }

    if (Array.isArray(x.subensambles) && x.subensambles.length > 0) {
      x.subensambles.forEach(sub => {
        const idsubensamble = Number(sub.idsubensamble || 0);
        if (idsubensamble > 0) {
          subensambles.push({
            idsubensamble,
            estacionid
          });
        }
      });
    }
  });

  return {
    productoid,
    cantidad,
    estaciones,
    subensambles
  };
}

function normalizarErrores(resp) {
  if (!resp) return [];
  if (Array.isArray(resp.errores)) return resp.errores;
  if (Array.isArray(resp.data)) return resp.data;
  return [];
}

async function validarComponentesEnRuta(detalle) {
  const payload = buildPayloadValidacion(detalle);

  if (
    !payload.productoid ||
    payload.cantidad <= 0 ||
    (payload.estaciones.length === 0 && payload.subensambles.length === 0)
  ) return;

  try {
    const resp = await fetchJson(base_url + '/plan_planeacionv1/validarExistencias', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (resp && resp.status === false) {
      const errores = normalizarErrores(resp);

      errores.forEach(item => {
        const tipo = String(item.tipo || 'ESTACION').toUpperCase();

        if (tipo === 'SUBENSAMBLE') {
          const idsubensamble = Number(item.idsubensamble || 0);
          if (!idsubensamble) return;

          subensamblesSinComponentes.add(idsubensamble);

          if (!faltantesSubensamblesMap[idsubensamble]) {
            faltantesSubensamblesMap[idsubensamble] = [];
          }
          faltantesSubensamblesMap[idsubensamble].push(item);
          return;
        }

        const id = Number(item.estacionid || 0);
        if (!id) return;

        estacionesSinComponentes.add(id);

        if (!faltantesComponentesMap[id]) {
          faltantesComponentesMap[id] = [];
        }
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
    const resp = await fetchJson(base_url + '/plan_planeacionv1/validarHerramientasExistencias', {
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
    estaciones: payloadPlaneacion.estaciones.map(x => ({
      estacionid: x.estacionid
    })),
    subensambles: payloadPlaneacion.subensambles.map(x => ({
      idsubensamble: x.idsubensamble,
      estacionid: x.estacionid
    }))
  };

  return await fetchJson(base_url + '/plan_planeacionv1/validarExistencias', {
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
  return await fetchJson(base_url + '/plan_planeacionv1/validarHerramientasExistencias', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });
}

// =====================================================
//  MODALES FALTANTES
// =====================================================
function abrirModalFaltantesComponentes(estacionid, nombreEstacion = '') {
  const modalEl = document.getElementById('modalFaltantesInventario');
  if (!modalEl) {
    Swal.fire({ icon: 'warning', title: 'Falta modal', text: 'No existe el modal de faltantes de componentes.' });
    return;
  }

  const title = modalEl.querySelector('#titleModalFaltantes');
  const subt = modalEl.querySelector('#subTitleFaltantes');
  const tbody = modalEl.querySelector('#tbodyFaltantes');

  if (title) title.textContent = 'Faltantes de componentes';
  if (subt) subt.textContent = `Estación: ${nombreEstacion || ('ID ' + estacionid)}`;

  const items = faltantesComponentesMap[estacionid] || [];

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

function abrirModalFaltantesComponentesSubensamble(idsubensamble, nombreSubensamble = '') {
  const modalEl = document.getElementById('modalFaltantesInventario');
  if (!modalEl) {
    Swal.fire({
      icon: 'warning',
      title: 'Falta modal',
      text: 'No existe el modal de faltantes de componentes.'
    });
    return;
  }

  const title = modalEl.querySelector('#titleModalFaltantes');
  const subt = modalEl.querySelector('#subTitleFaltantes');
  const tbody = modalEl.querySelector('#tbodyFaltantes');

  if (title) title.textContent = 'Faltantes de componentes';
  if (subt) subt.textContent = `Subensamble: ${nombreSubensamble || ('ID ' + idsubensamble)}`;

  const items = faltantesSubensamblesMap[idsubensamble] || [];

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
    Swal.fire({ icon: 'warning', title: 'Falta modal', text: 'No existe el modal de faltantes de herramientas.' });
    return;
  }

  const title = modalEl.querySelector('#titleModalFaltantesHer');
  const subt = modalEl.querySelector('#subTitleFaltantesHer');
  const tbody = modalEl.querySelector('#tbodyFaltantesHer');

  if (title) title.textContent = 'Faltantes de herramientas';
  if (subt) subt.textContent = `Estación: ${nombreEstacion || ('ID ' + estacionid)}`;

  const items = faltantesHerramientasMap[estacionid] || [];

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
//  MODAL ASIGNACIÓN OPERADORES
// =====================================================

function abrirModalOperadores(estacionid, nombreEstacion, proceso) {
  cargarOperadoresDisponibles();
  cargarOperadoresAyudantes();

  const title = document.querySelector("#titleModal");
  if (title) title.textContent = "Agregar operadores";

  const nom = document.querySelector("#modalEstacionNombre");
  const pro = document.querySelector("#modalEstacionProceso");
  const hid = document.querySelector("#modalEstacionId");
  const hidSub = document.querySelector("#modalSubensambleId");

  if (nom) nom.textContent = nombreEstacion || "—";
  if (pro) pro.textContent = proceso || "—";
  if (hid) hid.value = estacionid || "";
  if (hidSub) hidSub.value = "";

  const modalEl = document.getElementById("modalAddOperador");
  if (!modalEl) return console.error("No existe #modalAddOperador");

  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  setTimeout(() => {
    cargarAsignacionEnModalEstacion(estacionid);
    aplicarBloqueoAyudantes(estacionid);
    aplicarBloqueoEncargados(estacionid);
  }, 300);
}

function abrirModalOperadoresSubensamble(idsubensamble, estacionid, nombreSubensamble, proceso) {
  cargarOperadoresDisponibles();
  cargarOperadoresAyudantes();

  const title = document.querySelector("#titleModal");
  if (title) title.textContent = "Agregar operadores";

  const nom = document.querySelector("#modalEstacionNombre");
  const pro = document.querySelector("#modalEstacionProceso");
  const hid = document.querySelector("#modalEstacionId");
  const hidSub = document.querySelector("#modalSubensambleId");

  if (nom) nom.textContent = nombreSubensamble || "—";
  if (pro) pro.textContent = proceso || "—";
  if (hid) hid.value = estacionid || "";
  if (hidSub) hidSub.value = idsubensamble || "";

  const modalEl = document.getElementById("modalAddOperador");
  if (!modalEl) return console.error("No existe #modalAddOperador");

  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  setTimeout(() => {
    cargarAsignacionEnModalSubensamble(idsubensamble);

    // EN SUBENSAMBLE NO HAY BLOQUEO DE DUPLICADOS
    limpiarBloqueosOperadoresModal();
  }, 300);
}

function limpiarBloqueosOperadoresModal() {
  const selEnc = document.querySelector('#listOperadores');
  const selAy = document.querySelector('#selectAyudantes');

  if (selEnc) {
    Array.from(selEnc.options).forEach(opt => {
      opt.disabled = false;
    });
  }

  if (selAy) {
    Array.from(selAy.options).forEach(opt => {
      opt.disabled = false;
    });
  }
}

function cargarOperadoresDisponibles() {
  const sel = document.querySelector('#listOperadores');
  if (!sel) return;

  const ajaxUrl = base_url + '/plan_planeacionv1/getSelectOperadores';
  showLoading();

  const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    hideLoading();

    if (request.status === 200) {
      sel.innerHTML = `<option value="" selected>-- Selecciona encargado --</option>` + request.responseText;

      const idsub = document.querySelector('#modalSubensambleId')?.value || "";
      const estacionid = document.querySelector('#modalEstacionId')?.value || "";

      if (idsub) {
        // EN SUBENSAMBLE NO SE BLOQUEA
        limpiarBloqueosOperadoresModal();
      } else if (estacionid) {
        // SOLO ESTACIONES
        aplicarBloqueoEncargados(estacionid);
      }
    } else {
      console.error("Error cargando encargados", request.status);
    }
  };
}

function cargarOperadoresAyudantes() {
  const sel = document.querySelector('#selectAyudantes');
  if (!sel) return;

  const ajaxUrl = base_url + '/plan_planeacionv1/getSelectOperadoresAyudantes';
  showLoading();

  const request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    hideLoading();

    if (request.status === 200) {
      sel.innerHTML = request.responseText;

      const idsub = document.querySelector('#modalSubensambleId')?.value || "";
      const estacionid = document.querySelector('#modalEstacionId')?.value || "";

      if (idsub) {
        // EN SUBENSAMBLE NO SE BLOQUEA
        limpiarBloqueosOperadoresModal();
      } else if (estacionid) {
        // SOLO ESTACIONES
        aplicarBloqueoAyudantes(estacionid);
      }
    } else {
      console.error("Error cargando ayudantes", request.status);
    }
  };
}

function cargarAsignacionEnModalEstacion(estacionid) {
  const asignaciones = getAsignacionesLS();
  const data = asignaciones.estaciones[String(estacionid)];

  const encSel = document.querySelector('#listOperadores');
  const aySel = document.querySelector('#selectAyudantes');

  if (!data) {
    if (encSel) encSel.value = "";
    if (aySel) Array.from(aySel.options).forEach(o => o.selected = false);
    return;
  }

  if (encSel) {
    encSel.value = data.encargado !== null && data.encargado !== undefined
      ? String(data.encargado)
      : "";
  }

  if (aySel) {
    const setIds = new Set((data.ayudantes || []).map(String));
    Array.from(aySel.options).forEach(opt => {
      opt.selected = setIds.has(String(opt.value));
    });
  }
}

function cargarAsignacionEnModalSubensamble(idsubensamble) {
  const asignaciones = getAsignacionesLS();
  const data = asignaciones.subensambles[String(idsubensamble)];

  const encSel = document.querySelector('#listOperadores');
  const aySel = document.querySelector('#selectAyudantes');

  if (!data) {
    if (encSel) encSel.value = "";
    if (aySel) Array.from(aySel.options).forEach(o => o.selected = false);
    return;
  }

  if (encSel) {
    encSel.value = data.encargado !== null && data.encargado !== undefined
      ? String(data.encargado)
      : "";
  }

  if (aySel) {
    const setIds = new Set((data.ayudantes || []).map(String));
    Array.from(aySel.options).forEach(opt => {
      opt.selected = setIds.has(String(opt.value));
    });
  }
}

function onAplicarAsignacion() {
  const estacionid = document.querySelector('#modalEstacionId')?.value || "";
  const idsubensamble = document.querySelector('#modalSubensambleId')?.value || "";

  if (!estacionid) return;

  const encargado = document.querySelector('#listOperadores')?.value || "";
  const selAy = document.querySelector('#selectAyudantes');
  const ayudantes = selAy ? Array.from(selAy.selectedOptions).map(o => o.value) : [];

  const encSel = document.querySelector('#listOperadores');
  const aySel = document.querySelector('#selectAyudantes');

  const encargadoTexto = encargado
    ? getTextoOption(encSel, encargado, `Encargado: ${encargado}`)
    : "";

  const ayudantesTextos = ayudantes.map(id => {
    return getTextoOption(aySel, id, `Ayudante: ${id}`);
  });

  const asignaciones = getAsignacionesLS();

  if (idsubensamble) {
    // SUBENSAMBLE
    asignaciones.subensambles[String(idsubensamble)] = {
      idsubensamble: Number(idsubensamble),
      estacionid: Number(estacionid),
      encargado: encargado ? Number(encargado) : null,
      encargado_texto: encargadoTexto,
      ayudantes: ayudantes.map(x => Number(x)),
      ayudantes_textos: ayudantesTextos,
      updated_at: new Date().toISOString()
    };

    setAsignacionesLS(asignaciones);
    pintarOperadoresSubensamble(idsubensamble);
  } else {
    // ESTACIÓN
    asignaciones.estaciones[String(estacionid)] = {
      estacionid: Number(estacionid),
      encargado: encargado ? Number(encargado) : null,
      encargado_texto: encargadoTexto,
      ayudantes: ayudantes.map(x => Number(x)),
      ayudantes_textos: ayudantesTextos,
      updated_at: new Date().toISOString()
    };

    setAsignacionesLS(asignaciones);
    pintarOperadoresEnFila(estacionid);
  }

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
  const data = asignaciones.estaciones[String(estacionid)];

  const cont = document.querySelector(`#ops_${estacionid}`);
  const empty = document.querySelector(`#ops_empty_${estacionid}`);
  if (!cont) return;

  if (!data || (!data.encargado && (!data.ayudantes || data.ayudantes.length === 0))) {
    cont.innerHTML = '';
    if (empty) empty.classList.remove('d-none');
    return;
  }

  if (empty) empty.classList.add('d-none');

  const textoEnc = data.encargado_texto || (data.encargado ? `Encargado: ${data.encargado}` : '');
  const textosAy = Array.isArray(data.ayudantes_textos) ? data.ayudantes_textos : [];

  cont.innerHTML = `
    ${textoEnc ? `<span class="badge text-bg-primary">${escapeHtml(textoEnc)}</span>` : ``}
    ${textosAy.map(t => `<span class="badge text-bg-secondary">${escapeHtml(t)}</span>`).join(" ")}
  `;
}

function pintarOperadoresSubensamble(idsubensamble) {
  const asignaciones = getAsignacionesLS();
  const data = asignaciones.subensambles[String(idsubensamble)];

  const cont = document.querySelector(`#ops_sub_${idsubensamble}`);
  const empty = document.querySelector(`#ops_empty_sub_${idsubensamble}`);
  if (!cont) return;

  if (!data || (!data.encargado && (!data.ayudantes || data.ayudantes.length === 0))) {
    cont.innerHTML = '';
    if (empty) empty.classList.remove('d-none');
    return;
  }

  if (empty) empty.classList.add('d-none');

  const textoEnc = data.encargado_texto || (data.encargado ? `Encargado: ${data.encargado}` : '');
  const textosAy = Array.isArray(data.ayudantes_textos) ? data.ayudantes_textos : [];

  cont.innerHTML = `
    ${textoEnc ? `<span class="badge text-bg-primary">${escapeHtml(textoEnc)}</span>` : ``}
    ${textosAy.map(t => `<span class="badge text-bg-secondary">${escapeHtml(t)}</span>`).join(" ")}
  `;
}


function restaurarAsignacionesEnTabla() {
  const asignaciones = getAsignacionesLS();

  Object.keys(asignaciones.estaciones || {}).forEach(estacionid => {
    pintarOperadoresEnFila(estacionid);
  });

  Object.keys(asignaciones.subensambles || {}).forEach(idsubensamble => {
    pintarOperadoresSubensamble(idsubensamble);
  });
}

// =====================================================
//  LOCAL STORAGE (ASIGNACIONES)
// =====================================================
function getLSKeyAsignaciones() {
  const prod = document.querySelector('#selectProducto')?.value || '0';
  return `plan_asignaciones_prod_${prod}`;
}

function getAsignacionesLS() {
  try {
    const raw = localStorage.getItem(getLSKeyAsignaciones());
    const parsed = raw ? JSON.parse(raw) : {};

    return {
      estaciones: parsed.estaciones || {},
      subensambles: parsed.subensambles || {}
    };
  } catch (e) {
    console.error("JSON inválido en localStorage", e);
    return { estaciones: {}, subensambles: {} };
  }
}

function setAsignacionesLS(obj) {
  const payload = {
    estaciones: obj?.estaciones || {},
    subensambles: obj?.subensambles || {}
  };
  localStorage.setItem(getLSKeyAsignaciones(), JSON.stringify(payload));
}

function getTextoOption(selectEl, value, fallback = "") {
  if (!selectEl || value === null || value === undefined || value === "") return fallback;
  const opt = selectEl.querySelector(`option[value="${value}"]`);
  return opt ? String(opt.textContent || "").trim() : fallback;
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





// function restaurarAsignacionesEnTabla() {
//   const asignaciones = getAsignacionesLS();
//   Object.keys(asignaciones).forEach(estacionid => {
//     pintarOperadoresEnFila(estacionid);
//   });
// }



// =====================================================
//  BLOQUEO AYUDANTES
// =====================================================

function getAyudantesUsados(exceptEstacionId = null) {
  const asignaciones = getAsignacionesLS();
  const usados = new Set();

  // SOLO TOMAMOS AYUDANTES DE ESTACIONES
  Object.keys(asignaciones.estaciones || {}).forEach((estId) => {
    if (exceptEstacionId && String(estId) === String(exceptEstacionId)) return;
    const arr = asignaciones.estaciones[estId]?.ayudantes || [];
    arr.forEach((id) => usados.add(String(id)));
  });

  return usados;
}

function aplicarBloqueoAyudantes(estacionidActual) {
  const idsub = document.querySelector('#modalSubensambleId')?.value || "";

  // SI ES SUBENSAMBLE, NO BLOQUEAR NADA
  if (idsub) {
    limpiarBloqueosOperadoresModal();
    return;
  }

  const sel = document.querySelector('#selectAyudantes');
  if (!sel) return;

  const usados = getAyudantesUsados(estacionidActual);
  const asignaciones = getAsignacionesLS();
  const current = new Set(
    (asignaciones.estaciones[String(estacionidActual)]?.ayudantes || []).map(String)
  );

  Array.from(sel.options).forEach(opt => {
    const id = String(opt.value || "");
    if (!id) return;
    opt.disabled = usados.has(id) && !current.has(id);
  });
}

// =====================================================
//  BLOQUEO ENCARGADOS
//  SOLO ENTRE ESTACIONES
//  SUBENSAMBLES NO BLOQUEAN NADA
// =====================================================
function getEncargadosUsados(exceptEstacionId = null) {
  const asignaciones = getAsignacionesLS();
  const usados = new Set();

  // SOLO TOMAMOS ENCARGADOS DE ESTACIONES
  Object.keys(asignaciones.estaciones || {}).forEach((estId) => {
    if (exceptEstacionId && String(estId) === String(exceptEstacionId)) return;
    const enc = asignaciones.estaciones[estId]?.encargado ?? null;
    if (enc) usados.add(String(enc));
  });

  return usados;
}

function aplicarBloqueoEncargados(estacionidActual) {
  const idsub = document.querySelector('#modalSubensambleId')?.value || "";

  // SI ES SUBENSAMBLE, NO BLOQUEAR NADA
  if (idsub) {
    limpiarBloqueosOperadoresModal();
    return;
  }

  const sel = document.querySelector('#listOperadores');
  if (!sel) return;

  const usados = getEncargadosUsados(estacionidActual);
  const asignaciones = getAsignacionesLS();
  const currentEnc = asignaciones.estaciones[String(estacionidActual)]?.encargado ?? null;
  const current = currentEnc ? String(currentEnc) : null;

  Array.from(sel.options).forEach(opt => {
    const id = String(opt.value || "");
    if (!id) return;
    opt.disabled = usados.has(id) && id !== current;
  });
}


// =====================================================
//   VALIDACIONES
// =====================================================
function getHeaderPlaneacion() { 
  return {
    productoid: Number(document.querySelector('#selectProducto')?.value || 0),
    pedido: document.querySelector('#numPedido')?.value || "",
    supervisor: (document.querySelector('#selectSupervisor')?.value || "").trim(),
    prioridad: (document.querySelector('#selectPrioridad')?.value || "").trim(),
    cantidad: Number(document.querySelector('#txtCantidad')?.value || 0),
    fecha_inicio: (document.querySelector('#fechaInicio')?.value || "").trim(),
    fecha_requerida: (document.querySelector('#fechaRequerida')?.value || "").trim(),
    notas: (document.querySelector('#txtNotas')?.value || "").trim()
  };
}

function getEstacionesDeTabla() {
  const rows = Array.from(document.querySelectorAll('#tbodyEstaciones tr[data-tipo="estacion"][data-estacionid]'));

  return rows.map(tr => ({
    estacionid: Number(tr.dataset.estacionid || 0),
    orden: Number(tr.dataset.orden || 0),
    calidad: Number(tr.dataset.calidad || 0),
    especificaciones: Number(tr.dataset.especificaciones || 0),
    estampado: Number(tr.dataset.estampado || 0),
    operaciones: Number(tr.dataset.operaciones || 0)
  })).filter(x => x.estacionid > 0);
}

function getSubensamblesDeTabla() {
  const rows = Array.from(document.querySelectorAll('#tbodyEstaciones tr[data-tipo="subensamble"][data-idsubensamble]'));

  return rows.map(tr => ({
    idsubensamble: Number(tr.dataset.idsubensamble || 0),
    estacionid: Number(tr.dataset.estacionid || 0),
    orden_sub: String(tr.dataset.ordenSub || "")
  })).filter(x => x.idsubensamble > 0);
} 

function getAsignacionesParaGuardar() {
  const asignaciones = getAsignacionesLS();
  const estaciones = getEstacionesDeTabla();
  const subensambles = getSubensamblesDeTabla();

  return {
    estaciones: estaciones.map(s => {
      const a = asignaciones.estaciones[String(s.estacionid)] || null;
      return {
        estacionid: s.estacionid,
        orden: s.orden,
        estampado: s.estampado,
        calidad: s.calidad,
        operaciones: s.operaciones,
        especificaciones: s.especificaciones,
        encargado: a?.encargado ?? null,
        ayudantes: Array.isArray(a?.ayudantes) ? a.ayudantes : []
      };
    }),

    subensambles: subensambles.map(s => {
      const a = asignaciones.subensambles[String(s.idsubensamble)] || null;
      return {
        idsubensamble: s.idsubensamble,
        estacionid: s.estacionid,
        orden_sub: s.orden_sub,
        encargado: a?.encargado ?? null,
        ayudantes: Array.isArray(a?.ayudantes) ? a.ayudantes : []
      };
    })
  };
}

function buildPayloadPlaneacion() {
  const estaciones = getEstacionesDeTabla();
  const subensambles = getSubensamblesDeTabla();
  const asignaciones = getAsignacionesParaGuardar();
  const calidad = getAsignacionesCalidadParaGuardar();

  return {
    header: getHeaderPlaneacion(),
    estaciones,
    subensambles,
    asignaciones,
    calidad
  };
}

function validarAsignacionesCompletas() {
  const estaciones = getEstacionesDeTabla();
  const subensambles = getSubensamblesDeTabla();
  const asignaciones = getAsignacionesLS();
  const faltantes = [];

  estaciones.forEach(s => {
    const a = asignaciones.estaciones[String(s.estacionid)];
    const encargadoOk = !!(a && a.encargado);

    if (!encargadoOk) {
      faltantes.push({
        tipo: 'estacion',
        estacionid: s.estacionid,
        orden: s.orden,
        faltaEncargado: true
      });
    }
  });

  subensambles.forEach(s => {
    const a = asignaciones.subensambles[String(s.idsubensamble)];
    const encargadoOk = !!(a && a.encargado);

    if (!encargadoOk) {
      faltantes.push({
        tipo: 'subensamble',
        idsubensamble: s.idsubensamble,
        orden_sub: s.orden_sub,
        estacionid: s.estacionid,
        faltaEncargado: true
      });
    }
  });

  return faltantes;
}

function resaltarFilasIncompletas(faltantes) {
  document.querySelectorAll('#tbodyEstaciones tr[data-tipo]').forEach(tr => {
    tr.classList.remove('table-danger');
  });

  faltantes.forEach(f => {
    if (f.tipo === 'estacion') {
      const tr = document.querySelector(`#tbodyEstaciones tr[data-tipo="estacion"][data-estacionid="${f.estacionid}"]`);
      if (tr) tr.classList.add('table-danger');
    }

    if (f.tipo === 'subensamble') {
      const tr = document.querySelector(`#tbodyEstaciones tr[data-tipo="subensamble"][data-idsubensamble="${f.idsubensamble}"]`);
      if (tr) tr.classList.add('table-danger');
    }
  });
}

// =====================================================
//  GUARDAR PLANEACIÓN
// =====================================================
async function guardarPlaneacionHandler() {

  setMinFechaInicioHoy();
  setMinFechaRequeridaFromInicio();

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

  const hoy = todayYYYYMMDD();
  if (payload.header.fecha_inicio < hoy) {
    Swal.fire({ icon: 'warning', title: 'Fecha inicio inválida', text: 'La fecha de inicio no puede ser anterior a hoy.' });
    return;
  }
  if (payload.header.fecha_requerida < payload.header.fecha_inicio) {
    Swal.fire({ icon: 'warning', title: 'Fecha requerida inválida', text: 'La fecha requerida no puede ser anterior a la fecha de inicio.' });
    return;
  }

  // VALIDAR COMPONENTES
  try {
    const valComp = await validarComponentesAntesDeGuardar(payload);
    if (valComp && valComp.status === false) {
      Swal.fire({ icon: 'warning', title: 'Sin existencias', text: valComp.msg || 'Faltan componentes.' });
      await fntEstaciones(String(payload.header.productoid));
      return;
    }
  } catch (e) {
    console.error(e);
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo validar componentes.' });
    return;
  }

  // VALIDAR HERRAMIENTAS
  try {
    const valHer = await validarHerramientasAntesDeGuardar(payload);
    if (valHer && valHer.status === false) {
      Swal.fire({ icon: 'warning', title: 'Sin existencias', text: valHer.msg || 'Faltan herramientas.' });
      await fntEstaciones(String(payload.header.productoid));
      return;
    }
  } catch (e) {
    console.error(e);
    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo validar herramientas.' });
    return;
  }

  // VALIDAR OPERADORES
 const faltantes = validarAsignacionesCompletas();
if (faltantes.length > 0) {
  
  resaltarFilasIncompletas(faltantes);

const lista = faltantes
  .sort((a, b) => {
    const av = a.tipo === 'estacion' ? Number(a.orden || 0) : 9999;
    const bv = b.tipo === 'estacion' ? Number(b.orden || 0) : 9999;
    return av - bv;
  })
  .map(x => {
    if (x.tipo === 'estacion') {
      return `• Estación orden ${x.orden}: sin encargado`;
    }

    return `• Subensamble ${escapeHtml(String(x.orden_sub || x.idsubensamble))}: sin encargado`;
  })
  .join('<br>');

  Swal.fire({
    icon: 'warning',
    title: 'Faltan asignaciones',
    html: `<div class="text-start">
            <div class="fw-bold mb-2">Todas las estaciones y subensambles deben tener un encargado asignado:</div>
            ${lista}
          </div>`
  });
  return;
}



const faltantesCalidad = validarAsignacionesCalidadCompletas();

if (faltantesCalidad.length > 0) {
  const lista = faltantesCalidad.map(x => {
    return `• Estación orden ${x.orden}: falta asignar ${x.tipo === 'PDI' ? 'evaluador PDI' : 'inspector de puntos críticos'}`;
  }).join('<br>');

  Swal.fire({
    icon: 'warning',
    title: 'Faltan asignaciones de calidad',
    html: `<div class="text-start">
            <div class="fw-bold mb-2">Debes asignar el personal requerido para calidad:</div>
            ${lista}
          </div>`
  });

  return;
}

  //  CONFIRMACIÓN ANTES DE GUARDAR
  const confirm = await Swal.fire({
    icon: 'question',
    title: 'Confirmar guardado',
    html: `<div class="text-start">
            <div class="mb-2">Estás a punto de <b>guardar</b> esta orden de trabajo / planeación.</div>
            <div class="text-muted">Verifica que el producto, cantidad, prioridad, fechas y asignaciones sean correctas. Al confirmar, se registrará la información en el sistema.</div>
          </div>`,
    showCancelButton: true,
    confirmButtonText: 'Sí, guardar',
    cancelButtonText: 'No, cancelar',
    reverseButtons: true,
    allowOutsideClick: false
  });

  if (!confirm.isConfirmed) {
    Swal.fire({
      icon: 'info',
      title: 'Operación cancelada',
      text: 'No se realizaron cambios.'
    });
    return;
  }

  // GUARDAR
  try {
    const data = await fetchJson(base_url + '/plan_planeacionv1/setPlaneacion', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });

    if (!data.status) throw new Error(data.msg || 'Error al guardar');

    Swal.fire({ icon: 'success', title: '¡Operación exitosa!', text: 'Planeación guardada correctamente.' });

    localStorage.removeItem(getLSKeyAsignaciones());
    localStorage.removeItem(getLSKeyCalidad);
    await limpiarNuevaPlaneacion(false);

    window.location.href = base_url + '/plan_planeacionv1/ordenv1/' + data.num_planeacion;

  } catch (err) {
    console.error(err);
    Swal.fire({ icon: 'error', title: 'Error', text: err.message });
  }
}


// =====================================================
//  LIMPIEZA + CONFIRMACIONES
// =====================================================
async function limpiarNuevaPlaneacion(limpiarLS = true) {
  const selProd = document.querySelector('#selectProducto');
  const sup = document.querySelector('#selectSupervisor');
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

  setMinFechaInicioHoy();
  setMinFechaRequeridaFromInicio();

  renderEmptyTbody();
  document.querySelectorAll('#tbodyEstaciones tr').forEach(tr => tr.classList.remove('table-danger'));
  setBloqueoGuardarPorMantenimiento(false);

if (limpiarLS) {
  try { localStorage.removeItem(getLSKeyAsignaciones()); } catch (e) { }
  try { localStorage.removeItem(getLSKeyCalidad()); } catch (e) { }
}

  const enc = document.querySelector('#listOperadores');
  const ay = document.querySelector('#selectAyudantes');
  const hidEst = document.querySelector('#modalEstacionId');
  const hidSub = document.querySelector('#modalSubensambleId');

  if (enc) enc.innerHTML = `<option value="">-- Selecciona encargado --</option>`;
  if (ay) ay.innerHTML = ``;
  if (hidEst) hidEst.value = "";
  if (hidSub) hidSub.value = "";
}

async function confirmarDescartarSiHayBorrador() {
  const asignaciones = getAsignacionesLS();
  const totalEst = Object.keys(asignaciones.estaciones || {}).length;
  const totalSub = Object.keys(asignaciones.subensambles || {}).length;
  const hayAlgo = (totalEst + totalSub) > 0;

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

// =====================================================
//  CALEFNDAR HELPER
// =====================================================
function toIsoFromMysql(dt) {
  if (!dt) return null;
  return String(dt).replace(' ', 'T');
}

function faseMeta(fase) {
  const f = Number(fase);

  switch (f) {
    case 2: return { color: '#f59e0b', label: 'Planeada', badge: 'warning' };
    case 3: return { color: '#3b82f6', label: 'Programada', badge: 'primary' };
    case 5: return { color: '#22c55e', label: 'En producción', badge: 'success' };
    case 6: return { color: '#ef4444', label: 'Detenida', badge: 'danger' };
    default: return { color: '#6b7280', label: 'Sin estatus', badge: 'secondary' };
  }
}
 
async function cargarOrdenesParaCalendar() {
  const url = base_url + '/plan_planeacionv1/getOrdenes';
  const resp = await fetchJson(url);

  const rows = Array.isArray(resp)
    ? resp
    : (Array.isArray(resp.data) ? resp.data : (Array.isArray(resp.rows) ? resp.rows : []));

  return rows
    .filter(r => r.fecha_inicio) 
    .map(r => {
      const start = toIsoFromMysql(r.fecha_inicio);

      let end = r.fecha_fin ? toIsoFromMysql(r.fecha_fin) : undefined;


      if (end && new Date(end).getTime() <= new Date(start).getTime()) {
        end = new Date(new Date(start).getTime() + 60_000).toISOString(); 
      }

      const meta = faseMeta(r.fase);

      return {
        id: String(r.idplaneacion ?? ''),
        title: `#${String(r.num_orden ?? 'OT')}`,
        start,
        end,              
        allDay: false,
        backgroundColor: meta.color,
        borderColor: meta.color,
        textColor: '#ffffff',
        extendedProps: {
          ...r,
          fase_label: meta.label,
          fase_badge: meta.badge
        }
      };
    });
}





// =====================================================
//  MODAL PLANEACIÓN 
// =====================================================
function setModalPlaneacionLoading(isLoading) {
  const loading = document.getElementById('modalPlaneacionLoading');
  const content = document.getElementById('modalPlaneacionContent');
  if (loading) loading.classList.toggle('d-none', !isLoading);
  if (content) content.classList.toggle('d-none', isLoading);
}

function setText(id, value) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = (value === null || value === undefined || value === '') ? '—' : String(value);
}

async function fetchPlaneacionById(idplaneacion) {
  const url = base_url + '/plan_planeacionv1/getPlaneacionById/' + encodeURIComponent(idplaneacion);
  return await fetchJson(url);
}

function renderPlaneacionModal(payload) {
  const data = payload?.data ?? payload ?? {};
  const h = data.header ?? data.planeacion ?? data ?? {};

  const detalle =
    (Array.isArray(data.detalle) ? data.detalle : null) ||
    (Array.isArray(data.estaciones) ? data.estaciones : null) ||
    (Array.isArray(data.asignaciones) ? data.asignaciones : null) ||
    [];

  setText('mp_num_orden', h.num_orden);
  setText('mp_num_pedido', h.num_pedido);
  setText('mp_prioridad', h.prioridad);
  setText('mp_cantidad', h.cantidad);
  setText('mp_inicio', h.fecha_inicio);
    setText('mp_fin', h.fecha_fin);
  setText('mp_requerida', h.fecha_requerida);
  setText('mp_supervisor', h.supervisor);
  setText('mp_notas', h.notas);

  const sub = document.getElementById('subTitleModalPlaneacion');
  if (sub) sub.textContent = `Planeación ID: ${h.idplaneacion ?? '—'}`;

  const tbody = document.getElementById('tbodyPlaneacionDetalle');
  const count = document.getElementById('mp_count_detalle');

  const estaciones = Array.isArray(data.estaciones) ? data.estaciones : [];
  const metaPorPlaneacionEst = new Map();

  estaciones.forEach(est => {
    const peid = Number(est.id_planeacion_estacion || 0);
    if (!peid) return;

    const estacionNombre = est.nombre_estacion || '—';
    const estacionOrden = Number(est.orden || 0);

    const encargado = (Array.isArray(est.encargados) && est.encargados.length)
      ? (est.encargados[0].nombre_completo || '—')
      : '—';

    const ayudantes = (Array.isArray(est.ayudantes) ? est.ayudantes : [])
      .map(a => a?.nombre_completo)
      .filter(Boolean);

    metaPorPlaneacionEst.set(peid, {
      estacion: estacionNombre,
      orden_estacion: estacionOrden,
      encargado,
      ayudantes_txt: ayudantes.length ? ayudantes.join(', ') : '—'
    });
  });

  const subOrdenes = estaciones.flatMap(est => Array.isArray(est.ordenes_trabajo) ? est.ordenes_trabajo : []);
  const ordenadas = [...subOrdenes].sort((a, b) => Number(a.idorden || 0) - Number(b.idorden || 0));

  if (count) count.textContent = String(ordenadas.length || 0);
  if (!tbody) return;

  if (!ordenadas.length) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">Sin sub órdenes</td></tr>`;
    return;
  }

  tbody.innerHTML = ordenadas.map(o => {
    const idorden = o.idorden ?? '—';
    const numSub = o.num_sub_orden ?? '—';
    const peid = Number(o.planeacion_estacionid || 0);

    const meta = metaPorPlaneacionEst.get(peid) || {
      estacion: '—',
      orden_estacion: 0,
      encargado: '—',
      ayudantes_txt: '—'
    };

    return `
      <tr>
        <td class="fw-semibold">${escapeHtml(String(idorden))}</td>
        <td>
          <span class="badge bg-primary-subtle text-primary border">
            ${escapeHtml(String(numSub))}
          </span>
        </td>
        <td>${escapeHtml(String(meta.encargado))}</td>
        <td>${escapeHtml(String(meta.ayudantes_txt))}</td>
      </tr>
    `;
  }).join('');
}

async function abrirModalPlaneacionDesdeCalendar({ folio }) {
  const modalEl = document.getElementById('modalPlaneacionCalendar');
  if (!modalEl) {
    Swal.fire({ icon: 'warning', title: 'Falta modal', text: 'No existe #modalPlaneacionCalendar en la vista.' });
    return;
  }

  const btnVer = document.getElementById('btnVerMasDetalle');
  if (btnVer) {
    btnVer.onclick = () => {
      window.location.href = base_url + '/plan_planeacionv1/ordenv1/' + encodeURIComponent(folio);
    };
  }

  setModalPlaneacionLoading(true);
  bootstrap.Modal.getOrCreateInstance(modalEl).show();

  try {
    const payload = await fetchPlaneacionPorFolio(folio);
    if (payload && payload.status === false) throw new Error(payload.msg || 'No se pudo cargar');

    renderPlaneacionModal(payload);
  } catch (err) {
    console.error(err);
    Swal.fire({ icon: 'error', title: 'Error', text: err.message || 'Error al cargar' });
  } finally {
    setModalPlaneacionLoading(false);
  }
}

async function fetchPlaneacionPorFolio(folio) {
  const url = base_url + '/plan_planeacionv1/ordenv1/' + encodeURIComponent(folio) + '?json=1';
  return await fetchJson(url);
}





// =========================================================
//  Planeación - Flatpickr COMPLETO 
// =========================================================

let fpInicio = null;
let fpRequerida = null;


if (window.flatpickr && flatpickr.l10ns && flatpickr.l10ns.es) {
  flatpickr.localize(flatpickr.l10ns.es);
}

// =======================
// Helpers
// =======================
function prepareInputForManualFlatpickr(input) {
  if (!input) return;

  // Evitar init automático del template
  input.removeAttribute("data-provider");
  input.removeAttribute("data-date-format");
  input.removeAttribute("data-enable-time");


  if (input._flatpickr) {
    input._flatpickr.destroy();
  }
}

function mysqlToDate(dt) {
  if (!dt) return null;
  const [d, t = "00:00:00"] = String(dt).trim().split(" ");
  const [Y, M, D] = d.split("-").map(Number);
  const [hh, mm, ss] = t.split(":").map(Number);
  return new Date(Y, (M - 1), D, hh || 0, mm || 0, ss || 0);
}

function addMinutes(date, minutes) {
  return new Date(date.getTime() + minutes * 60000);
}

function isWeekend(date) {
  const d = date.getDay();
  return d === 0 || d === 6;
}

function findCollision(date, ranges) {
  const t = date.getTime();
  for (const r of ranges) {
    if (!r?.from || !r?.to) continue;
    if (t >= r.from.getTime() && t <= r.to.getTime()) return r;
  }
  return null;
}

function nextAvailable(date, ranges, toleranceMin = 15) {
  let d = new Date(date.getTime());
  while (true) {
    const col = findCollision(d, ranges);
    if (!col) return d;
    d = addMinutes(col.to, toleranceMin);
  }
}

function startOfDay(d) {
  return new Date(d.getFullYear(), d.getMonth(), d.getDate(), 0, 0, 0);
}

// =======================
// Rangos ocupados 
// =======================
async function getRangosOcupados() {
  const url = base_url + "/plan_planeacionv1/getSelectDates";
  const resp = await fetch(url, { headers: { Accept: "application/json" } });
  const json = await resp.json();

  const rows = Array.isArray(json) ? json : (Array.isArray(json?.data) ? json.data : []);
  const TOL = 15;

  return rows
    .filter(r => r.fecha_inicio && r.fecha_fin)
    .map(r => {
      const from = mysqlToDate(r.fecha_inicio);
      let to = mysqlToDate(r.fecha_fin);

  
      if (from && to && to.getTime() <= from.getTime()) {
        to = addMinutes(from, 5);
      }


      if (to) to = addMinutes(to, TOL);

      return { from, to };
    })
    .filter(x => x.from && x.to);
}

// =======================
// Bloqueo día 100% ocupado
// =======================
function dayIsFullyBlocked(day, ranges) {
  const start = new Date(day.getFullYear(), day.getMonth(), day.getDate(), 0, 0, 0);
  const end   = new Date(day.getFullYear(), day.getMonth(), day.getDate(), 23, 59, 59);
  return ranges.some(r => start >= r.from && end <= r.to);
}

// =========================================================
// REQUERIDA
// =========================================================
function updateRequeridaMinByDay() {
  if (!fpRequerida) return;

  const ini = fpInicio?.selectedDates?.[0];
  if (!ini) {
    fpRequerida.set("minDate", "today");
    fpRequerida.set("disable", []);
    return;
  }

  const minDay = startOfDay(ini);


  fpRequerida.set("minDate", minDay);
  fpRequerida.set("disable", [
    (date) => startOfDay(date).getTime() < minDay.getTime()
  ]);


  const req = fpRequerida.selectedDates?.[0];
  if (req && startOfDay(req).getTime() < minDay.getTime()) {
    fpRequerida.clear();
  }
}

// =========================================================
// INIT INICIO PRODUCCIÓN 
// =========================================================
async function initFechaInicioPicker() {
  const input = document.getElementById("fechaInicio");
  if (!input) return;

  prepareInputForManualFlatpickr(input);
  if (fpInicio) fpInicio.destroy();

  let ranges = await getRangosOcupados();
  console.log("Rangos ocupados:", ranges);

  let _ajustando = false;

  fpInicio = flatpickr(input, {
    locale: "es",
    enableTime: true,
    time_24hr: true,
    minuteIncrement: 5,
    minDate: "today",
    defaultHour: 9,
    defaultMinute: 0,

   
    altInput: true,
    altFormat: "d.m.Y H:i",

    dateFormat: "Y-m-d H:i",


    disable: [
      (date) => isWeekend(date) || dayIsFullyBlocked(date, ranges)
    ],

    onOpen: async (selectedDates, dateStr, instance) => {
      ranges = await getRangosOcupados();
      instance.set("disable", [
        (date) => isWeekend(date) || dayIsFullyBlocked(date, ranges)
      ]);
    },

    onChange: (selectedDates, dateStr, instance) => {
      if (_ajustando) return;
      if (!selectedDates?.length) return;

      const picked = selectedDates[0];


      const soloDia = (picked.getHours() === 12 && picked.getMinutes() === 0);

      let candidate = picked;
      if (soloDia) {
        candidate = new Date(
          picked.getFullYear(),
          picked.getMonth(),
          picked.getDate(),
          9, 0, 0
        );
      }


      const col = findCollision(candidate, ranges);
      if (col) {
        const next = nextAvailable(candidate, ranges, 15);

        _ajustando = true;
        instance.setDate(next, true); 
        _ajustando = false;

        if (window.Swal) {
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "info",
            title: "Horario ocupado",
            text: "Se ajustó automáticamente al siguiente horario disponible (+15 min).",
            showConfirmButton: false,
            timer: 2200
          });
        }
      } else if (soloDia) {
     
        if (picked.getHours() !== 9 || picked.getMinutes() !== 0) {
          _ajustando = true;
          instance.setDate(candidate, true);
          _ajustando = false;
        }
      }


      updateRequeridaMinByDay();
    }
  });


  updateRequeridaMinByDay();
}


function initFechaRequeridaPicker() {
  const inputReq = document.getElementById("fechaRequerida");
  if (!inputReq) return;

  prepareInputForManualFlatpickr(inputReq);
  if (fpRequerida) fpRequerida.destroy();

  fpRequerida = flatpickr(inputReq, {
    locale: "es",
    enableTime: true,
    time_24hr: true,
    minuteIncrement: 5,
    defaultHour: 9,
    defaultMinute: 0,


    altInput: true,
    altFormat: "d.m.Y H:i",


    dateFormat: "Y-m-d H:i",

    minDate: "today",

    onOpen: () => {
      updateRequeridaMinByDay();
    },

    onChange: (selectedDates, dateStr, instance) => {
      if (!selectedDates?.length) return;

      const ini = fpInicio?.selectedDates?.[0];
      if (!ini) return;

   
      const minDay = startOfDay(ini);
      const reqDay = startOfDay(selectedDates[0]);

      if (reqDay.getTime() < minDay.getTime()) {
        instance.clear();

        if (window.Swal) {
          Swal.fire({
            toast: true,
            position: "top-end",
            icon: "warning",
            title: "Fecha inválida",
            text: "La fecha requerida no puede ser anterior al día de inicio.",
            showConfirmButton: false,
            timer: 2500
          });
        }
      }
    }
  });

  updateRequeridaMinByDay();
}

///////////////////////////////////////////////////////////////////
// SE INICIAN LAS FUNCIONES PARA PODER ADIGNAR OPERADORES DE CALIDAD
///////////////////////////////////////////////////////////////////

function getLSKeyCalidad() {
  const prod = document.querySelector('#selectProducto')?.value || '0';
  return `plan_calidad_prod_${prod}`;
}

function getAsignacionesCalidadLS() {
  try {
    const raw = localStorage.getItem(getLSKeyCalidad());
    return raw ? JSON.parse(raw) : {};
  } catch (e) {
    return {};
  }
}

function setAsignacionesCalidadLS(obj) {
  localStorage.setItem(getLSKeyCalidad(), JSON.stringify(obj || {}));
}

function cargarPersonalCalidad() {
  const sel = document.querySelector('#selectPersonalCalidad');
  if (!sel) return;

  const ajaxUrl = base_url + '/plan_planeacionv1/getSelectPersonalCalidad';

  showLoading();

  const request = window.XMLHttpRequest
    ? new XMLHttpRequest()
    : new ActiveXObject('Microsoft.XMLHTTP');

  request.open("GET", ajaxUrl, true);
  request.send();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;
    hideLoading();

    if (request.status === 200) {
      sel.innerHTML = `<option value="">-- Selecciona personal --</option>` + request.responseText;

      const estacionid = document.querySelector('#modalCalidadEstacionId')?.value || "";
      const tipo = document.querySelector('#modalCalidadTipo')?.value || "";

      const asignaciones = getAsignacionesCalidadLS();
      const key = `${tipo}_${estacionid}`;
      const data = asignaciones[key];

      if (data?.usuarioid) {
        sel.value = String(data.usuarioid);
      }
    }
  };
}

function abrirModalCalidad(estacionid, nombreEstacion, proceso, tipo) {
  const title = document.querySelector('#titleModalCalidad');
  const nom = document.querySelector('#modalCalidadEstacionNombre');
  const pro = document.querySelector('#modalCalidadEstacionProceso');
  const hidEst = document.querySelector('#modalCalidadEstacionId');
  const hidTipo = document.querySelector('#modalCalidadTipo');

  if (title) {
    title.textContent = tipo === 'PDI'
      ? 'Asignar evaluador PDI'
      : 'Asignar inspector de puntos críticos';
  }

  if (nom) nom.textContent = nombreEstacion || '—';
  if (pro) pro.textContent = proceso || '—';
  if (hidEst) hidEst.value = estacionid || '';
  if (hidTipo) hidTipo.value = tipo || '';

  cargarPersonalCalidad();

  const modalEl = document.getElementById('modalAddCalidad');
  if (!modalEl) return console.error('No existe #modalAddCalidad');

  bootstrap.Modal.getOrCreateInstance(modalEl).show();
}

function onAplicarCalidad() {
  const estacionid = document.querySelector('#modalCalidadEstacionId')?.value || '';
  const tipo = document.querySelector('#modalCalidadTipo')?.value || '';
  const sel = document.querySelector('#selectPersonalCalidad');
  const usuarioid = sel?.value || '';

  if (!estacionid || !tipo) return;

  if (!usuarioid) {
    Swal.fire({
      icon: 'warning',
      title: 'Falta personal',
      text: 'Selecciona el personal de calidad.'
    });
    return;
  }

  const texto = getTextoOption(sel, usuarioid, `Usuario: ${usuarioid}`);
  const asignaciones = getAsignacionesCalidadLS();
  const key = `${tipo}_${estacionid}`;

  asignaciones[key] = {
    estacionid: Number(estacionid),
    tipo,
    usuarioid: Number(usuarioid),
    usuario_texto: texto,
    updated_at: new Date().toISOString()
  };

  setAsignacionesCalidadLS(asignaciones);
  pintarCalidadEnFila(estacionid);

  Swal.fire({
    icon: 'success',
    title: 'Guardado',
    text: 'Se guardó correctamente la asignación de calidad.',
    timer: 1200,
    showConfirmButton: false
  });

  const modalEl = document.getElementById('modalAddCalidad');
  if (modalEl) bootstrap.Modal.getOrCreateInstance(modalEl).hide();
}

function pintarCalidadEnFila(estacionid) {
  const asignaciones = getAsignacionesCalidadLS();
  const cont = document.querySelector(`#calidad_${estacionid}`);
  if (!cont) return;

  const crit = asignaciones[`CRITICOS_${estacionid}`];
  const pdi = asignaciones[`PDI_${estacionid}`];

  cont.innerHTML = `
    ${crit?.usuario_texto ? `<span class="badge bg-warning-subtle text-warning border"><i class="ri-error-warning-line me-1"></i>${escapeHtml(crit.usuario_texto)}</span>` : ''}
    ${pdi?.usuario_texto ? `<span class="badge bg-info-subtle text-info border"><i class="ri-shield-check-line me-1"></i>${escapeHtml(pdi.usuario_texto)}</span>` : ''}
  `;
}

function restaurarCalidadEnTabla() {
  document.querySelectorAll('#tbodyEstaciones tr[data-tipo="estacion"][data-estacionid]').forEach(tr => {
    pintarCalidadEnFila(tr.dataset.estacionid);
  });
}


function getAsignacionesCalidadParaGuardar() {
  const asignaciones = getAsignacionesCalidadLS();
  const estaciones = getEstacionesDeTabla();

  return estaciones.map(s => {
    const crit = asignaciones[`CRITICOS_${s.estacionid}`] || null;
    const pdi = asignaciones[`PDI_${s.estacionid}`] || null;

    return {
      estacionid: s.estacionid,
      orden: s.orden,
      requiere_criticos: Number(s.especificaciones || 0),
      requiere_pdi: Number(s.calidad || 0),
      inspector_criticos: crit?.usuarioid ?? null,
      inspector_pdi: pdi?.usuarioid ?? null
    };
  }).filter(x => x.requiere_criticos === 1 || x.requiere_pdi === 1);
}


function validarAsignacionesCalidadCompletas() {
  const estaciones = getEstacionesDeTabla();
  const asignaciones = getAsignacionesCalidadLS();
  const faltantes = [];

  estaciones.forEach(s => {
    if (Number(s.especificaciones || 0) === 1) {
      const crit = asignaciones[`CRITICOS_${s.estacionid}`];
      if (!crit?.usuarioid) {
        faltantes.push({
          tipo: 'CRITICOS',
          estacionid: s.estacionid,
          orden: s.orden
        });
      }
    }

    if (Number(s.calidad || 0) === 1) {
      const pdi = asignaciones[`PDI_${s.estacionid}`];
      if (!pdi?.usuarioid) {
        faltantes.push({
          tipo: 'PDI',
          estacionid: s.estacionid,
          orden: s.orden
        });
      }
    }
  });

  return faltantes;
}



