(() => {
  const baseUrl = base_url;
  console.log("baseUrl:", baseUrl);

  const ESTATUS = {1:'Pendiente',2:'En proceso',3:'Finalizada'};
  const CALIDAD = {1:'Pend. inspección',2:'En inspección',3:'Con observaciones',4:'Rechazado',5:'Liberado'};

  const $ = (id) => document.getElementById(id);

  let chEficSubot = null;
  let chCalidad = null;
  let chEnTiempoEst = null;
  let chStdVsReal = null;

  function ensureChartJS(){
    if(!window.Chart){
      console.warn("Chart.js no está cargado. Agrega el CDN ANTES del script.");
      return false;
    }
    return true;
  }

  function destroyChart(ch){
    try { if(ch) ch.destroy(); } catch(e){}
    return null;
  }

  function badge(text, type='secondary'){
    return `<span class="badge bg-${type}-subtle text-${type}">${text}</span>`;
  }

  function fmtNum(v, dec=1){
    if(v === null || v === undefined || v === '') return '—';
    const n = Number(v);
    if(Number.isNaN(n)) return '—';
    return n.toFixed(dec);
  }


  function clampPct(v){
    const n = Number(v);
    if(!Number.isFinite(n)) return 0;
    if(n <= 0) return 0;             
    if(n < 1) return 1;              
    if(n > 100) return 100;             
    return n;
  }

  function fmtPct(v, dec=1){
    const n = clampPct(v);
    return Number.isFinite(n) ? n.toFixed(dec) : '0.0';
  }

  function timeChip(enTiempo, cerrada){
    if(!cerrada) return badge('Abierta','secondary');
    return enTiempo ? badge('En tiempo','success') : badge('Fuera de tiempo','danger');
  }

  function estatusChip(code){
    const t = ESTATUS[code] || `Estatus ${code}`;
    if(code==3) return badge(t,'primary');
    if(code==2) return badge(t,'warning');
    return badge(t,'secondary');
  }

  function calidadChip(code){
    const t = CALIDAD[code] || `Calidad ${code}`;
    if(code==5) return badge(t,'success');
    if(code==4) return badge(t,'danger');
    if(code==3) return badge(t,'warning');
    if(code==2) return badge(t,'info');
    return badge(t,'secondary');
  }

  async function fetchJSON(url){
    const r = await fetch(url, { headers: { "Accept": "application/json" }});
    const text = await r.text();

    const trimmed = (text || '').trim();
    if (trimmed.startsWith('<')) {
      console.error("Respuesta HTML (probable login/404). Primeros 300 chars:", trimmed.slice(0, 300));
      throw new Error("Respuesta HTML (revisa sesión/ruta).");
    }

    let j;
    try {
      j = JSON.parse(trimmed);
    } catch (e) {
      console.error("No se pudo parsear JSON. Primeros 300 chars:", trimmed.slice(0, 300));
      throw new Error("Respuesta no es JSON válido.");
    }

    if(!j.status) throw new Error(j.msg || 'Error');
    return j.data;
  }


  function buildParams(){
    const params = new URLSearchParams();
    if($('planeacionid')?.value) params.set('planeacionid', $('planeacionid').value);
    if($('fecha_ini')?.value) params.set('fecha_ini', $('fecha_ini').value);
    if($('fecha_fin')?.value) params.set('fecha_fin', $('fecha_fin').value);
    if($('q')?.value?.trim()) params.set('q', $('q').value.trim());
    return params.toString();
  }

  function requirePlaneacion(){
  const val = $('planeacionid')?.value?.trim();
  if(!val){
    Swal.fire({
      icon: 'warning',
      title: 'Falta planeación',
      text: 'Tienes que seleccionar una planeación para generar un reporte.',
      confirmButtonText: 'Entendido',
    });
    return false;
  }
  return true;
}

let __loadingTimer = null;

function showLoading(titulo = 'Generando reporte...'){
  if(!window.Swal) return;

  Swal.fire({
    title: titulo,
    html: 'Por favor espera un momento.',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
      Swal.showLoading();
    }
  });

 
  clearTimeout(__loadingTimer);
  __loadingTimer = setTimeout(() => {
    hideLoading();
  }, 2500);
}

function hideLoading(){
  clearTimeout(__loadingTimer);
  __loadingTimer = null;
  if(window.Swal && Swal.isVisible()) Swal.close();
}


function downloadFile(path){
  if(!requirePlaneacion()) return;

  const qs = buildParams();
  const url = `${baseUrl}/rpt_mrp_planeacion/${path}${qs ? `?${qs}` : ''}`;

  const title = (path === 'exportExcel') ? 'Generando Excel...' : 'Generando PDF...';
  showLoading(title);


  const w = window.open(url, '_blank');
  if(!w){
    hideLoading();
    Swal.fire({
      icon: 'error',
      title: 'Popup bloqueado',
      text: 'Tu navegador bloqueó la descarga. Permite ventanas emergentes para este sitio e inténtalo de nuevo.',
    });
    return;
  }


  const onFocusBack = () => {
    hideLoading();
    window.removeEventListener('focus', onFocusBack);
  };
  window.addEventListener('focus', onFocusBack);


  setTimeout(() => hideLoading(), 2000);
}


$('btnExcel')?.addEventListener('click', () => downloadFile('exportExcel'));
$('btnPdf')?.addEventListener('click', () => downloadFile('exportPdf'));


  async function loadPlaneaciones(){
    const data = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getPlaneaciones`);
    const sel = $('planeacionid');
    if(!sel) return;
    sel.innerHTML = `<option value="">-- Selecciona --</option>` + data.map(x =>
      `<option value="${x.planeacionid}">${x.planeacionid} · ${x.num_orden} · ${x.producto || ''}</option>`
    ).join('');
  }

async function loadCostoTotalPlaneacion(){
  const qs = buildParams();

  if(!$('planeacionid')?.value){
    $('kpi_costo_total').textContent = '$—';
    return;
  }
  const data = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getCostoTotalPlaneacion?${qs}`);
  $('kpi_costo_total').textContent = fmtMoney(data.costo_total_planeacion || 0);
}

async function loadCostosEstacion(){
  const qs = buildParams();
  const tbody = document.querySelector('#tblCostosEstacion tbody');
  if(!tbody) return;

  if(!$('planeacionid')?.value){
    tbody.innerHTML = '';
    return;
  }

  const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getCostosEstacion?${qs}`);

  tbody.innerHTML = rows.map(r => {
    const enc = r.encargado_nombre || '—';
    const ayu = r.ayudante_nombre ? `<div class="text-muted"><small>Ayudante: ${r.ayudante_nombre}</small></div>` : '';
    return `
      <tr>
        <td>
          <div class="fw-semibold">${r.cve_estacion || ''} · ${r.nombre_estacion || ''}</div>
          <div class="text-muted"><small>${r.proceso || ''}</small></div>
        </td>
        <td>
          <div class="fw-semibold">${enc}</div>
          ${ayu}
        </td>
        <td class="text-end fw-semibold">${fmtMoney(r.costo_total_estacion || 0)}</td>
        <td class="text-end">${r.c1 || 0}</td>
        <td class="text-end">${r.c2 || 0}</td>
        <td class="text-end">${r.c3 || 0}</td>
        <td class="text-end">${r.c4 || 0}</td>
        <td class="text-end">${r.c5 || 0}</td>
        <td class="text-end">${r.total_registros || 0}</td>
      </tr>
    `;
  }).join('');
}

async function loadCostosDetalle(){
  const qs = buildParams();
  const tbody = document.querySelector('#tblCostosDetalle tbody');
  if(!tbody) return;

  if(!$('planeacionid')?.value){
    tbody.innerHTML = '';
    return;
  }

  const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getCostosDetalle?${qs}`);

  tbody.innerHTML = rows.map(r => `
    <tr>
      <td>
        <div class="fw-semibold">${r.cve_estacion || ''} · ${r.nombre_estacion || ''}</div>
        <div class="text-muted"><small>${r.proceso || ''}</small></div>
      </td>
      <td>
        <div class="fw-semibold">${r.cve_articulo || ''}</div>
        <div class="text-muted"><small>${r.descripcion || ''}</small></div>
      </td>
      <td class="text-end">${fmtNum(r.cantidad_planeada,0)}</td>
      <td class="text-end">${fmtNum(r.cantidad_por_producto,3)}</td>
      <td class="text-end">${fmtNum(r.cantidad_total_requerida,3)}</td>
      <td class="text-end">${fmtMoney(r.ultimo_costo || 0)}</td>
      <td class="text-end fw-semibold">${fmtMoney(r.costo_total_articulo || 0)}</td>
    </tr>
  `).join('');
}


async function loadCostosDetalle(){
  const planeacionid = $('planeacionid')?.value;
  const tbody = document.querySelector('#tblCostosDetalle tbody');
  if(!tbody) return;

  if(!planeacionid){
    tbody.innerHTML = '';
    return;
  }

  const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getCostosDetalle?planeacionid=${encodeURIComponent(planeacionid)}`);

  tbody.innerHTML = rows.map(r => `
    <tr>
      <td>
        <div class="fw-semibold">${r.cve_estacion || ''} · ${r.nombre_estacion || ''}</div>
        <div class="text-muted"><small>${r.proceso || ''}</small></div>
      </td>
      <td>
        <div class="fw-semibold">${r.cve_articulo || ''}</div>
        <div class="text-muted"><small>${r.descripcion || ''}</small></div>
      </td>
      <td class="text-end">${fmtNum(r.cantidad_planeada,0)}</td>
      <td class="text-end">${fmtNum(r.cantidad_por_producto,3)}</td>
      <td class="text-end">${fmtNum(r.cantidad_total_requerida,3)}</td>
      <td class="text-end">${fmtMoney(r.ultimo_costo || 0)}</td>
      <td class="text-end fw-semibold">${fmtMoney(r.costo_total_articulo || 0)}</td>
    </tr>
  `).join('');
}


  async function loadKpis(){
    const qs = buildParams();
    const k = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getKpis?${qs}`);
    $('kpi_subot').textContent = k.subot || 0;

    
    $('kpi_eficiencia').textContent = fmtPct(k.eficiencia_prom, 1);

 
    $('kpi_ent').textContent = fmtPct(k.pct_en_tiempo, 1);

    $('kpi_rech').textContent = k.rechazos || 0;
  }

  async function loadDetalle(){
    const qs = buildParams();
    const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getDetalle?${qs}`);

    $('lbl_registros').textContent = rows.length;

    const tbody = document.querySelector('#tblDetalle tbody');
    if(tbody){
      tbody.innerHTML = rows.map(r => {
        const real = r.duracion_real_min ? fmtNum(r.duracion_real_min, 1) : '—';


        const hasEff = (r.eficiencia_pct_base !== null && r.eficiencia_pct_base !== undefined && r.eficiencia_pct_base !== '');
        const effVal = hasEff ? clampPct(r.eficiencia_pct_base) : null;
        const eff = hasEff ? `${fmtNum(effVal,1)}%` : 'N/A';

        const enc = r.encargado_nombre || '—';
        const ayu = r.ayudante_nombre ? `<div class="text-muted"><small>Ayudante: ${r.ayudante_nombre}</small></div>` : '';

        return `
          <tr>
            <td>${badge('#'+(r.num_sub_orden ?? '—'),'primary')}</td>
            <td>
              <div class="fw-semibold">${r.cve_estacion || ''} · ${r.nombre_estacion || ''}</div>
              <div class="text-muted"><small>${r.proceso || ''}</small></div>
            </td>
            <td>
              <div class="fw-semibold">${enc}</div>
              ${ayu}
            </td>
            <td class="text-end">${fmtNum(r.estandar_min, 0)}</td>
            <td class="text-end">${real}</td>
            <td class="text-end">${badge(eff, (hasEff && effVal>=100) ? 'success' : 'warning')}</td>
            <td>${timeChip(Number(r.en_tiempo)===1, Number(r.cerrada)===1)}</td>
            <td>${estatusChip(Number(r.estatus))}</td>
            <td>${calidadChip(Number(r.calidad))}</td>
          </tr>
        `;
      }).join('');
    }

    return rows;
  }

  async function loadResumen(){
    const qs = buildParams();
    const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getResumenSubOt?${qs}`);
    const tbody = document.querySelector('#tblResumen tbody');
    if(!tbody) return;

    tbody.innerHTML = rows.map(r => {
      const hasEff = (r.eficiencia !== null && r.eficiencia !== undefined && r.eficiencia !== '');
      const effVal = hasEff ? clampPct(r.eficiencia) : null;

      return `
        <tr>
          <td>${badge('#'+r.num_sub_orden,'primary')}</td>
          <td class="text-end">${fmtNum(r.std_total,1)}</td>
          <td class="text-end">${fmtNum(r.real_total,1)}</td>
          <td class="text-end">${hasEff ? fmtNum(effVal,1)+'%' : 'N/A'}</td>
          <td class="text-end">${fmtNum(clampPct(r.pct_en_tiempo),1)}%</td>
          <td class="text-end">${r.rechazos}</td>
          <td>${estatusChip(Number(r.ultimo_estatus))}</td>
          <td>${calidadChip(Number(r.ultima_calidad))}</td>
        </tr>
      `;
    }).join('');
  }

  async function loadEncargados(){
    const qs = buildParams();
    const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getEncargados?${qs}`);
    const tbody = document.querySelector('#tblEncargados tbody');
    if(!tbody) return;

    tbody.innerHTML = rows.map(r => {
      const hasEff = (r.eficiencia_prom !== null && r.eficiencia_prom !== undefined && r.eficiencia_prom !== '');
      const effVal = hasEff ? clampPct(r.eficiencia_prom) : null;

      return `
        <tr>
          <td class="fw-semibold">${r.encargado_nombre || '—'}</td>
          <td class="text-end">${r.registros}</td>
          <td class="text-end">${fmtNum(r.real_total,1)}</td>
          <td class="text-end">${hasEff ? fmtNum(effVal,1)+'%' : 'N/A'}</td>
          <td class="text-end">${fmtNum(clampPct(r.pct_en_tiempo),1)}%</td>
          <td class="text-end">${r.rechazos}</td>
        </tr>
      `;
    }).join('');
  }

  async function loadCalidad(){
    const qs = buildParams();
    const rows = await fetchJSON(`${baseUrl}/rpt_mrp_planeacion/getCalidadEstacion?${qs}`);
    const tbody = document.querySelector('#tblCalidad tbody');
    if(!tbody) return;
    tbody.innerHTML = rows.map(r => `
      <tr>
        <td><div class="fw-semibold">${r.cve_estacion} · ${r.nombre_estacion}</div></td>
        <td class="text-end">${r.c1}</td>
        <td class="text-end">${r.c2}</td>
        <td class="text-end">${r.c3}</td>
        <td class="text-end">${r.c4}</td>
        <td class="text-end">${r.c5}</td>
        <td class="text-end">${r.total}</td>
      </tr>
    `).join('');
  }

  function buildChartsFromDetalle(rows){
    const bySub = new Map();
    for(const r of rows){
      const sub = String(r.num_sub_orden ?? '');
      if(!sub) continue;
      const std = Number(r.estandar_min ?? 0) || 0;
      const real = Number(r.duracion_real_min ?? 0) || 0;

      const cur = bySub.get(sub) || { std: 0, real: 0 };
      cur.std += std;
      cur.real += real;
      bySub.set(sub, cur);
    }

    const subLabels = Array.from(bySub.keys()).sort((a,b)=>Number(a)-Number(b));
    const subEff = subLabels.map(s => {
      const v = bySub.get(s);
      if(!v || v.std<=0 || v.real<=0) return 0;
 
      return clampPct((v.std / v.real) * 100);
    });

    const qCount = {1:0,2:0,3:0,4:0,5:0};
    for(const r of rows){
      const c = Number(r.calidad);
      if(qCount[c] !== undefined) qCount[c] += 1;
    }
    const calLabels = Object.keys(qCount).map(k => CALIDAD[k] || ('Calidad '+k));
    const calVals = Object.keys(qCount).map(k => qCount[k]);

    const byEst = new Map();
    for(const r of rows){
      const key = String(r.cve_estacion || r.nombre_estacion || r.estacionid || '');
      if(!key) continue;

      const cerrada = Number(r.cerrada) === 1;
      if(!cerrada) continue;

      const cur = byEst.get(key) || { tot: 0, ok: 0 };
      cur.tot += 1;
      if(Number(r.en_tiempo) === 1) cur.ok += 1;
      byEst.set(key, cur);
    }
    const estLabels = Array.from(byEst.keys());
    const estPct = estLabels.map(k => {
      const v = byEst.get(k);
      if(!v || v.tot<=0) return 0;
  
      return clampPct((v.ok / v.tot) * 100);
    });

    const byEstMin = new Map();
    for(const r of rows){
      const key = String(r.cve_estacion || r.nombre_estacion || r.estacionid || '');
      if(!key) continue;

      const cur = byEstMin.get(key) || { std: 0, real: 0 };
      cur.std += Number(r.estandar_min ?? 0) || 0;
      cur.real += Number(r.duracion_real_min ?? 0) || 0;
      byEstMin.set(key, cur);
    }
    const est2Labels = Array.from(byEstMin.keys());
    const std2 = est2Labels.map(k => byEstMin.get(k).std);
    const real2= est2Labels.map(k => byEstMin.get(k).real);

    return {
      eficienciaSubOt: { labels: subLabels.map(x => '#'+x), values: subEff },
      calidad: { labels: calLabels, values: calVals },
      enTiempoEst: { labels: estLabels, values: estPct },
      realVsStd: { labels: est2Labels, std: std2, real: real2 }
    };
  }

  function renderCharts(d){
    if(!ensureChartJS()) return;

    const c1 = $('chartEficienciaSubOt');
    const c2 = $('chartCalidad');
    const c3 = $('chartEnTiempoEstacion');
    const c4 = $('chartRealVsStd');

    if(c1){
      chEficSubot = destroyChart(chEficSubot);
      chEficSubot = new Chart(c1, {
        type: 'bar',
        data: {
          labels: d.eficienciaSubOt.labels,
          datasets: [{ label: 'Eficiencia (%)', data: d.eficienciaSubOt.values }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: {
        
            y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } }
          }
        }
      });
    }

    if(c2){
      chCalidad = destroyChart(chCalidad);
      chCalidad = new Chart(c2, {
        type: 'doughnut',
        data: {
          labels: d.calidad.labels,
          datasets: [{ label: 'Calidad', data: d.calidad.values }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { position: 'bottom' } }
        }
      });
    }

    if(c3){
      chEnTiempoEst = destroyChart(chEnTiempoEst);
      chEnTiempoEst = new Chart(c3, {
        type: 'bar',
        data: {
          labels: d.enTiempoEst.labels,
          datasets: [{ label: '% En tiempo', data: d.enTiempoEst.values }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: {
            y: { beginAtZero: true, max: 100, ticks: { callback: (v) => v + '%' } }
          }
        }
      });
    }

    if(c4){
      chStdVsReal = destroyChart(chStdVsReal);
      chStdVsReal = new Chart(c4, {
        type: 'bar',
        data: {
          labels: d.realVsStd.labels,
          datasets: [
            { label: 'Estándar (min)', data: d.realVsStd.std },
            { label: 'Real (min)', data: d.realVsStd.real },
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: { legend: { display: true } },
          scales: { y: { beginAtZero: true } }
        }
      });
    }
  }

  async function refreshAll(){
    await loadKpis();

    const rowsDetalle = await loadDetalle();

    const d = buildChartsFromDetalle(rowsDetalle || []);
    renderCharts(d);

    await loadResumen();
    await loadEncargados();
    await loadCalidad();

    await loadCostoTotalPlaneacion();
await loadCostosEstacion();
await loadCostosDetalle();

  }

  $('btnAplicar')?.addEventListener('click', () => refreshAll().catch(console.error));

  $('btnLimpiar')?.addEventListener('click', async () => {
    if($('planeacionid')) $('planeacionid').value = '';
    if($('fecha_ini')) $('fecha_ini').value = '';
    if($('fecha_fin')) $('fecha_fin').value = '';
    if($('q')) $('q').value = '';
    await refreshAll().catch(console.error);
  });


  loadPlaneaciones()
    .then(() => refreshAll())
    .catch(err => console.error(err));
})();



function fmtMoney(v){
  const n = Number(v);
  if(!Number.isFinite(n)) return '—';
  return new Intl.NumberFormat('es-MX', { style:'currency', currency:'MXN' }).format(n);
}
