(() => {
  'use strict';

  // =========================================================
  // Config
  // =========================================================
  const LOGO_URL = base_url + '/Assets/images/ldr_logo_color.png';

  // =========================================================
  // Helpers basicos
  // =========================================================
  const pad2 = (n) => String(n).padStart(2, '0');

  const nowSql = () => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = pad2(d.getMonth() + 1);
    const dd = pad2(d.getDate());
    const hh = pad2(d.getHours());
    const mi = pad2(d.getMinutes());
    const ss = pad2(d.getSeconds());
    return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`;
  };

  // ✅ NUEVO: para filename (YYYYMMDD_HHMMSS)
  const nowFileStamp = () => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = pad2(d.getMonth() + 1);
    const dd = pad2(d.getDate());
    const hh = pad2(d.getHours());
    const mi = pad2(d.getMinutes());
    const ss = pad2(d.getSeconds());
    return `${yyyy}${mm}${dd}_${hh}${mi}${ss}`;
  };

  const fmt = (v) => (v === null || v === undefined) ? "" : String(v);

  const getRow = (btn) => btn?.closest('tr') || null;

  // Tabla: 0 SubOT, 1 Estatus, 2 Inicio, 3 Fin, 4 Acciones
  const getCell = (tr, idx) => {
    if (!tr) return null;
    const tds = tr.querySelectorAll('td');
    return tds && tds[idx] ? tds[idx] : null;
  };

  const setCellDT = (tr, idx, value) => {
    const td = getCell(tr, idx);
    if (!td) return;
    td.innerHTML = `<span class="text-muted">${value || '—'}</span>`;
  };

  const getButtons = (tr) => ({
    btnStart: tr?.querySelector('.btnStartOT') || null,
    btnFinish: tr?.querySelector('.btnFinishOT') || null,
    // ✅ NUEVO: botón comentarios por fila
    btnComment: tr?.querySelector('.btnCommentOT') || null
  });

  const setBadge = (tr, tipo) => {
    const td = getCell(tr, 1);
    if (!td) return;

    let cls = 'badge bg-secondary-subtle text-secondary';
    let txt = 'Pendiente';

    if (tipo === 'proceso') {
      cls = 'badge bg-warning-subtle text-warning';
      txt = 'En proceso';
    } else if (tipo === 'finalizada') {
      cls = 'badge bg-success-subtle text-success';
      txt = 'Finalizada';
    } else if (tipo === 'detenida') {
      cls = 'badge bg-danger-subtle text-danger';
      txt = 'Detenida';
    }

    td.innerHTML = `<span class="${cls}">${txt}</span>`;
  };

  // =========================================================
  // Loading (SweetAlert)
  // =========================================================
  const showLoading = (text = 'Procesando...') => {
    Swal.fire({
      title: text,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading()
    });
  };

  const hideLoading = () => {
    try { Swal.close(); } catch (e) {}
  };

  // =========================================================
  // Parseadores / reglas (candados)
  // =========================================================
  const getSNum = (subot) => {
    const m = String(subot || '').match(/-S(\d+)\s*$/i);
    return m ? parseInt(m[1], 10) : 0;
  };

  // 1) dataset order, 2) fallback accordion index
  const getEstOrden = (tr) => {
    const a = tr?.dataset?.estOrden || tr?.dataset?.est_orden;
    if (a !== undefined && a !== null && a !== '') {
      const n = parseInt(a, 10);
      if (!Number.isNaN(n) && n > 0) return n;
    }

    const stationItem = tr.closest('.accordion-item');
    if (stationItem) {
      const allStationItems = Array.from(document.querySelectorAll('.accordion-item'));
      const idx = allStationItems.indexOf(stationItem);
      if (idx >= 0) return idx + 1;
    }

    return 0;
  };

  const getEstatusFromRow = (tr) => {
    const ds = tr?.dataset?.estatus;
    if (ds !== undefined && ds !== null && ds !== '') {
      const n = parseInt(ds, 10);
      if (!Number.isNaN(n)) return n;
    }

    const td = getCell(tr, 1);
    const txt = td ? (td.textContent || '').trim().toLowerCase() : '';
    if (txt.includes('final')) return 3;
    if (txt.includes('proceso')) return 2;
    if (txt.includes('deten')) return 4;
    return 1;
  };

  // ✅ NUEVO: aplica regla de comentarios (solo estatus 2)
  const applyCommentsRule = () => {
    document.querySelectorAll('tr[data-idorden]').forEach(tr => {
      const st = getEstatusFromRow(tr);
      const { btnComment } = getButtons(tr);
      if (!btnComment) return;

      // Solo habilitar comentarios cuando está EN PROCESO (2)
      const allow = (st === 2);
      btnComment.disabled = !allow;

      // opcional: estilo visual
      btnComment.classList.toggle('disabled', !allow);
    });
  };

  // ✅ candado
  const lockByRules = () => {
    const allRows = Array.from(document.querySelectorAll('tr[data-subot]'));
    if (!allRows.length) return;

    const byStation = new Map();

    for (const tr of allRows) {
      const subot = (tr.dataset.subot || '').trim();
      if (!subot) continue;

      const station = getEstOrden(tr);
      const sn = getSNum(subot);

      if (!sn || !station) continue;

      if (!byStation.has(station)) byStation.set(station, new Map());
      byStation.get(station).set(sn, tr);
    }

    // bloquear todo start/finish
    allRows.forEach(tr => {
      const { btnStart, btnFinish } = getButtons(tr);
      if (btnStart) btnStart.disabled = true;
      if (btnFinish) btnFinish.disabled = true;
    });

    const stations = Array.from(byStation.keys()).sort((a, b) => a - b);

    // fallback
    if (!stations.length) {
      const firstRow = allRows
        .slice()
        .sort((a, b) => getSNum(a.dataset.subot) - getSNum(b.dataset.subot))[0];

      if (firstRow) {
        const { btnStart, btnFinish } = getButtons(firstRow);
        const st = getEstatusFromRow(firstRow);
        if (st === 1 && btnStart) btnStart.disabled = false;
        if (st === 2 && btnFinish) btnFinish.disabled = false;
      }

      // ✅ NUEVO: siempre aplica regla de comentarios
      applyCommentsRule();
      return;
    }

    for (const stOrder of stations) {
      const sMap = byStation.get(stOrder);
      const sNums = Array.from(sMap.keys()).sort((a, b) => a - b);

      // a) si hay uno en proceso, solo finish
      let inProcess = null;
      for (const sn of sNums) {
        const tr = sMap.get(sn);
        if (getEstatusFromRow(tr) === 2) { inProcess = tr; break; }
      }
      if (inProcess) {
        const { btnStart, btnFinish } = getButtons(inProcess);
        if (btnStart) btnStart.disabled = true;
        if (btnFinish) btnFinish.disabled = false;
        continue;
      }

      // b) primer pendiente que cumpla dependencias
      let candidate = null;

      for (const sn of sNums) {
        const tr = sMap.get(sn);
        const st = getEstatusFromRow(tr);

        if (st === 3 || st === 4) continue;
        if (st !== 1) continue;

        // Dep A: dentro de estación -> S(n-1) finalizada
        let depSubOk = true;
        if (sn > 1) {
          const prev = sMap.get(sn - 1);
          depSubOk = !!prev && getEstatusFromRow(prev) === 3;
        }

        // Dep B: entre estaciones -> misma Sxx finalizada en estación anterior
        let depStationOk = true;
        if (stOrder > 1) {
          const prevStationMap = byStation.get(stOrder - 1);
          const prevRow = prevStationMap ? prevStationMap.get(sn) : null;
          depStationOk = !!prevRow && getEstatusFromRow(prevRow) === 3;
        }

        // Estación 1: S01 siempre inicia
        if (stOrder === 1 && sn === 1) {
          depSubOk = true;
          depStationOk = true;
        }

        if (depSubOk && depStationOk) { candidate = tr; break; }
      }

      if (candidate) {
        const { btnStart, btnFinish } = getButtons(candidate);
        if (btnStart) btnStart.disabled = false;
        if (btnFinish) btnFinish.disabled = true;
      }
    }

    // ✅ NUEVO: siempre aplica regla de comentarios
    applyCommentsRule();
  };

  // =========================================================
  // Fetch helper (POST JSON)
  // =========================================================
  const postJson = async (url, payload) => {
    const resp = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify(payload)
    });

    const raw = await resp.text();
    let json = null;
    try { json = JSON.parse(raw); }
    catch (e) { throw new Error(`Respuesta inválida (no JSON). ${raw.slice(0, 200)}`); }

    return json;
  };

  // =========================================================
  // START
  // =========================================================
  const handleStartClick = async (btn) => {
    const tr = getRow(btn);
    if (!tr) return;

    const idorden = (btn.dataset.idorden || '').trim();
    const peid = (btn.dataset.peid || '').trim();
    const subot = (btn.dataset.subot || '').trim();

    if (!idorden) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta data-idorden', timer: 3000, showConfirmButton: false });
      return;
    }
    if (btn.disabled) return;

    btn.disabled = true;

    const fecha_inicio = nowSql();
    setCellDT(tr, 2, fecha_inicio);
    setBadge(tr, 'proceso');
    tr.dataset.estatus = '2';

    const url = base_url + '/plan_planeacion/startOT';
    const payload = { idorden, peid, subot, fecha_inicio };

    showLoading('Iniciando proceso...');

    try {
      const data = await postJson(url, payload);
      hideLoading();

      if (!data || data.status === false) {
        setCellDT(tr, 2, '—');
        setBadge(tr, 'pendiente');
        tr.dataset.estatus = '1';
        btn.disabled = false;

        Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo iniciar', timer: 5000, showConfirmButton: false, timerProgressBar: true });
        lockByRules();
        return;
      }

      Swal.fire({ icon: 'success', title: 'Proceso iniciado', text: data?.msg || 'Operación iniciada correctamente', timer: 1400, showConfirmButton: false, timerProgressBar: true });
      lockByRules();

    } catch (err) {
      console.error(err);
      hideLoading();

      setCellDT(tr, 2, '—');
      setBadge(tr, 'pendiente');
      tr.dataset.estatus = '1';
      btn.disabled = false;

      Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor', timer: 5000, showConfirmButton: false, timerProgressBar: true });
      lockByRules();
    }
  };

  // =========================================================
  // FINISH
  // =========================================================
  const handleFinishClick = async (btn) => {
    const tr = getRow(btn);
    if (!tr) return;

    const idorden = (btn.dataset.idorden || '').trim();
    const peid = (btn.dataset.peid || '').trim();
    const subot = (btn.dataset.subot || '').trim();

    if (!idorden) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta data-idorden', timer: 3000, showConfirmButton: false });
      return;
    }
    if (btn.disabled) return;

    btn.disabled = true;

    const fecha_fin = nowSql();
    setCellDT(tr, 3, fecha_fin);
    setBadge(tr, 'finalizada');
    tr.dataset.estatus = '3';

    const url = base_url + '/plan_planeacion/finishOT';
    const payload = { idorden, peid, subot, fecha_fin };

    showLoading('Finalizando proceso...');

    try {
      const data = await postJson(url, payload);
      hideLoading();

      if (!data || data.status === false) {
        setCellDT(tr, 3, '—');
        setBadge(tr, 'proceso');
        tr.dataset.estatus = '2';
        btn.disabled = false;

        Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo finalizar', timer: 5000, showConfirmButton: false, timerProgressBar: true });
        lockByRules();
        return;
      }

      Swal.fire({ icon: 'success', title: 'Proceso finalizado', text: data?.msg || 'Operación completada correctamente', timer: 1400, showConfirmButton: false, timerProgressBar: true });
      lockByRules();

    } catch (err) {
      console.error(err);
      hideLoading();

      setCellDT(tr, 3, '—');
      setBadge(tr, 'proceso');
      tr.dataset.estatus = '2';
      btn.disabled = false;

      Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor', timer: 5000, showConfirmButton: false, timerProgressBar: true });
      lockByRules();
    }
  };

  // =========================================================
  // ✅ COMENTARIOS SUB-OT (Modal)
  // =========================================================
  const initCommentsModal = () => {
    const modalEl = document.getElementById('modalOTComment');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    const $mSubOT = document.getElementById('mSubOT');
    const $mIdOrden = document.getElementById('mIdOrden');
    const $mPeid = document.getElementById('mPeid');
    const $mComentario = document.getElementById('mComentario');
    const $btnSave = document.getElementById('btnSaveOTComment');

    // Abrir modal (SOLO si está en proceso)
    document.body.addEventListener('click', (e) => {
      const btn = e.target.closest('.btnCommentOT');
      if (!btn) return;

      const tr = btn.closest('tr');
      const st = tr ? getEstatusFromRow(tr) : 0;

      // ✅ NUEVO: solo cuando EN PROCESO
      if (st !== 2) {
        Swal.fire({
          icon: 'info',
          title: 'Ups',
          text: 'Finalizaste la orden, ya no puedes cargar comentarios',
          timer: 2600,
          showConfirmButton: false,
          timerProgressBar: true
        });
        return;
      }

      if (!modal) return;

      const idorden = btn.dataset.idorden || '';
      const peid = btn.dataset.peid || '';
      const subot = btn.dataset.subot || '';
      const coment = tr ? (tr.dataset.coment || '') : '';

      if ($mSubOT) $mSubOT.textContent = subot || '—';
      if ($mIdOrden) $mIdOrden.value = idorden;
      if ($mPeid) $mPeid.value = peid;
      if ($mComentario) $mComentario.value = coment;

      modal.show();
    });

    // Guardar comentario (solo si está en proceso)
    if ($btnSave) {
      $btnSave.addEventListener('click', async () => {
        const idorden = ($mIdOrden?.value || '').trim();
        const peid = ($mPeid?.value || '').trim();
        const comentario = ($mComentario?.value || '').trim();

        if (!idorden) {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta idorden', timer: 3000, showConfirmButton: false });
          return;
        }
        if (!comentario) {
          Swal.fire({ icon: 'warning', title: 'Atención', text: 'El comentario es obligatorio', timer: 3000, showConfirmButton: false });
          $mComentario?.focus();
          return;
        }

        // ✅ NUEVO: si ya no está en proceso, no permitir
        const tr = document.querySelector(`tr[data-idorden="${CSS.escape(idorden)}"]`);
        const st = tr ? getEstatusFromRow(tr) : 0;
        if (st !== 2) {
          Swal.fire({
            icon: 'info',
            title: 'Ups',
            text: 'Finalizaste la orden, ya no puedes cargar comentarios',
            timer: 2600,
            showConfirmButton: false,
            timerProgressBar: true
          });
          try { modal?.hide(); } catch(e){}
          return;
        }

        const url = base_url + '/plan_planeacion/setCommentario';
        const payload = { idorden, peid, comentario };

        showLoading('Guardando comentario...');

        try {
          const data = await postJson(url, payload);
          hideLoading();

          if (!data || data.status === false) {
            Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo guardar el comentario', timer: 5000, showConfirmButton: false, timerProgressBar: true });
            return;
          }

          if (tr) tr.dataset.coment = comentario;

          Swal.fire({ icon: 'success', title: 'Guardado', text: data?.msg || 'Comentario actualizado', timer: 2000, showConfirmButton: false, timerProgressBar: true });

          modal.hide();

        } catch (err) {
          console.error(err);
          hideLoading();

          Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor', timer: 5000, showConfirmButton: false, timerProgressBar: true });
        }
      });
    }
  };

  // =========================================================
  // ✅ SYNC ASÍNCRONO (polling)
  // =========================================================
  const initSyncPolling = () => {
    const planeacionid = (document.getElementById('timeTrackerCard')?.dataset?.planeacion || '').trim();
    if (!planeacionid) return;

    const mapRows = () => {
      const m = new Map();
      document.querySelectorAll('tr[data-idorden]').forEach(tr => {
        const id = (tr.dataset.idorden || '').trim();
        if (id) m.set(id, tr);
      });
      return m;
    };

    const applyStatusToRow = (tr, row) => {
      const est = String(row.estatus ?? '').trim();
      if (!est) return;

      if ((tr.dataset.estatus || '').trim() === est &&
        (tr.dataset.fi || '') === (row.fecha_inicio || '') &&
        (tr.dataset.ff || '') === (row.fecha_fin || '')
      ) return;

      tr.dataset.estatus = est;
      tr.dataset.fi = row.fecha_inicio || '';
      tr.dataset.ff = row.fecha_fin || '';

      if (est === '1') setBadge(tr, 'pendiente');
      else if (est === '2') setBadge(tr, 'proceso');
      else if (est === '3') setBadge(tr, 'finalizada');
      else if (est === '4') setBadge(tr, 'detenida');

      setCellDT(tr, 2, row.fecha_inicio || '—');
      setCellDT(tr, 3, row.fecha_fin || '—');
    };

    let syncing = false;

    const syncFromServer = async () => {
      if (syncing) return;
      syncing = true;

      try {
        const url = base_url + '/plan_planeacion/getStatusOT';
        const data = await postJson(url, { planeacionid });

        if (!data || data.status === false) return;

        const rows = Array.isArray(data.data) ? data.data : [];
        if (!rows.length) return;

        const m = mapRows();

        for (const r of rows) {
          const tr = m.get(String(r.idorden || '').trim());
          if (!tr) continue;
          applyStatusToRow(tr, r);
        }

        lockByRules();

      } catch (e) {
        console.warn('sync error', e);
      } finally {
        syncing = false;
      }
    };

    setInterval(syncFromServer, 5000);
    syncFromServer();
  };

  // =========================================================
  // ====================   PDF SECTION   =====================
  // =========================================================

  function isZeroDate(s){
    return !s || String(s).startsWith("0000-00-00");
  }

  function parseMysqlDateTime(s){
    if (!s || isZeroDate(s)) return null;
    const [datePart, timePart] = String(s).split(' ');
    if (!datePart || !timePart) return null;
    const [Y, M, D] = datePart.split('-').map(Number);
    const [h, m, sec] = timePart.split(':').map(Number);
    if (!Y || !M || !D) return null;
    return new Date(Y, M-1, D, h||0, m||0, sec||0);
  }

  function secondsDiff(startStr, endStr){
    const a = parseMysqlDateTime(startStr);
    const b = parseMysqlDateTime(endStr);
    if (!a || !b) return 0;
    const ms = b.getTime() - a.getTime();
    if (ms <= 0) return 0;
    return Math.round(ms / 1000);
  }

  function formatHMSText(totalSeconds){
    const s = Math.max(0, Number(totalSeconds)||0);
    const hh = Math.floor(s / 3600);
    const mm = Math.floor((s % 3600) / 60);
    const ss = s % 60;

    if (hh > 0) return `${hh} h ${mm} min ${ss} s`;
    if (mm > 0) return `${mm} min ${ss} s`;
    return `${ss} s`;
  }

  function todayYMD(){
    const d = new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth()+1)}-${pad2(d.getDate())}`;
  }

  function buildDocCode(d){
    const ot = (d?.num_orden || "OT").replace(/\s+/g,'');
    const ymd = todayYMD().replaceAll('-','');
    return `MRP-${ot}-${ymd}-V1.0`;
  }

  // Logo base64 cache
  let LOGO_BASE64_CACHE = "";
  async function urlToBase64(url) {
    const res = await fetch(url, { cache: "no-store" });
    if (!res.ok) throw new Error("No se pudo cargar el logo");
    const blob = await res.blob();

    return await new Promise((resolve, reject) => {
      const reader = new FileReader();
      reader.onload = () => resolve(reader.result);
      reader.onerror = reject;
      reader.readAsDataURL(blob);
    });
  }
  async function getLogoBase64() {
    if (LOGO_BASE64_CACHE) return LOGO_BASE64_CACHE;
    try {
      LOGO_BASE64_CACHE = await urlToBase64(LOGO_URL);
    } catch (e) {
      console.warn('Logo no cargado, se usará texto:', e);
      LOGO_BASE64_CACHE = "";
    }
    return LOGO_BASE64_CACHE;
  }

  async function fetchOrdenData(numOrden){
    const url = base_url + '/plan_planeacion/descargarOrden/' + encodeURIComponent(String(numOrden || ''));
    const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });

    const raw = await resp.text();
    let json = null;

    try { json = JSON.parse(raw); }
    catch (e) { throw new Error(`Respuesta inválida (no JSON). Primeros 200: ${raw.slice(0,200)}`); }

    if (!json || json.status === false) {
      throw new Error(json?.msg || 'No se pudo obtener la data de la OT');
    }
    return json;
  }

  function joinNames(list){
    if (!Array.isArray(list) || list.length === 0) return "";
    return list.map(x => x?.nombre_completo).filter(Boolean).join(", ");
  }

  function buildPdfPlaneacionV5(payload, logoBase64, docCode){
    const d = payload?.data || {};
    const estaciones = Array.isArray(d.estaciones) ? d.estaciones : [];
    const hasLogo = typeof logoBase64 === 'string' && logoBase64.startsWith('data:image');

    const headerTop = {
      table: {
        widths: ['*','*'],
        body: [
          [
            { text: 'Planeación / Orden de Trabajo', style: 'h1' },
            { text: docCode, style: 'docCode', alignment:'right' }
          ],
          [
            { text: `OT: ${fmt(d.num_orden)}   |   Pedido: ${fmt(d.num_pedido)}`, style: 'muted' },
            { text: `Fecha reporte: ${todayYMD()}`, style: 'muted', alignment:'right' }
          ]
        ]
      },
      layout: 'noBorders',
      margin: [0, 0, 0, 8]
    };

    const resumen = {
      table: {
        widths: [110,'*', 110,'*'],
        body: [
          [
            {text:'Producto', style:'th'},
            {text:`${fmt(d.cve_producto)} — ${fmt(d.descripcion)}`, style:'td'},
            {text:'Prioridad', style:'th'},
            {text: fmt(d.prioridad), style:'td'}
          ],
          [
            {text:'Supervisor', style:'th'},
            {text: fmt(d.supervisor), style:'td'},
            {text:'Cantidad', style:'th'},
            {text: fmt(d.cantidad), style:'td'}
          ],
          [
            {text:'Fecha inicio', style:'th'},
            {text: fmt(d.fecha_inicio), style:'td'},
            {text:'Fecha requerida', style:'th'},
            {text: fmt(d.fecha_requerida), style:'td'}
          ],
          [
            {text:'Notas', style:'th'},
            {text: fmt(d.notas), style:'td', colSpan: 3},
            {},{}
          ]
        ]
      },
      layout: {
        fillColor: (rowIndex, node, columnIndex) => {
          const cell = node.table.body[rowIndex][columnIndex] || {};
          return cell.style === 'th' ? '#f3f4f6' : null;
        }
      },
      margin: [0, 0, 0, 10]
    };

    const bloques = [];

    estaciones.forEach((e, idx) => {
      const ots = Array.isArray(e.ordenes_trabajo) ? e.ordenes_trabajo : [];
      const encargados = joinNames(e.encargados) || "—";
      const ayudantes  = joinNames(e.ayudantes) || "—";

      bloques.push({
        table: {
          widths: ['*'],
          body: [[{
            stack: [
              { text: `Estación ${fmt(e.orden)} — ${fmt(e.nombre_estacion)}`, style: 'stTitle' },
              { text: `Clave: ${fmt(e.cve_estacion)}   |   ID estación: ${fmt(e.estacionid)}   |   PlaneaciónEstación: ${fmt(e.id_planeacion_estacion)}`, style:'stSub' }
            ],
            margin: [8, 8, 8, 6]
          }]]
        },
        layout: { fillColor: () => '#111827', hLineWidth: () => 0, vLineWidth: () => 0 },
        margin: [0, (idx === 0 ? 6 : 12), 0, 6]
      });

      bloques.push({
        table: {
          widths: [90, '*', 90, '*'],
          body: [[
            { text: 'Encargado(s)', style:'th2' },
            { text: encargados, style:'td2' },
            { text: 'Ayudante(s)', style:'th2' },
            { text: ayudantes, style:'td2' }
          ]]
        },
        layout: {
          hLineColor: () => '#e5e7eb',
          vLineColor: () => '#e5e7eb',
          paddingLeft: () => 6,
          paddingRight: () => 6,
          paddingTop: () => 4,
          paddingBottom: () => 4
        },
        margin: [0, 0, 0, 6]
      });

      bloques.push({
        table: {
          widths: ['*'],
          body: [
            [{ text: 'Proceso', style: 'boxHead' }],
            [{ text: fmt(e.proceso) || '—', style: 'boxText' }]
          ]
        },
        layout: {
          fillColor: (rowIndex) => rowIndex === 0 ? '#f3f4f6' : null,
          hLineColor: () => '#e5e7eb',
          vLineColor: () => '#e5e7eb'
        },
        margin: [0, 0, 0, 8]
      });

      if (ots.length === 0) {
        bloques.push({ text: 'Sin sub-órdenes registradas.', style:'muted', margin:[0,0,0,6] });
        return;
      }

      let totalSeconds = 0;

      const bodyRows = ots.map(o => {
        const secs = secondsDiff(o.fecha_inicio, o.fecha_fin);
        totalSeconds += secs;
        const mins = Math.round(secs / 60);

        return ([
          { text: fmt(o.num_sub_orden), style:'tblCellSmall' },
          { text: isZeroDate(o.fecha_inicio) ? "—" : fmt(o.fecha_inicio), style:'tblCellSmall' },
          { text: isZeroDate(o.fecha_fin) ? "—" : fmt(o.fecha_fin), style:'tblCellSmall' },
          { text: fmt(o.comentarios) || '—', style:'tblCellJust' },
          { text: `${mins}`, style:'tblCellSmall', alignment:'right' }
        ]);
      });

      bodyRows.push([
        { text: 'Tiempo total', style:'totLabel', colSpan: 4, alignment:'right' },
        {}, {}, {},
        { text: formatHMSText(totalSeconds), style:'totValueAccent', alignment:'right' }
      ]);

      bloques.push({
        table: {
          headerRows: 1,
          widths: [85, 95, 95, '*', 45],
          body: [
            [
              {text:'Sub-OT', style:'tblHead'},
              {text:'Inicio', style:'tblHead'},
              {text:'Fin', style:'tblHead'},
              {text:'Comentarios', style:'tblHead'},
              {text:'Minutos', style:'tblHead'}
            ],
            ...bodyRows
          ]
        },
        layout: {
          hLineColor: () => '#e5e7eb',
          vLineColor: () => '#e5e7eb',
          paddingLeft: () => 6,
          paddingRight: () => 6,
          paddingTop: () => 4,
          paddingBottom: () => 4
        },
        margin: [0, 0, 0, 8]
      });

      bloques.push({
        canvas: [{ type:'line', x1:0, y1:0, x2:515, y2:0, lineWidth:1, lineColor:'#e5e7eb' }],
        margin:[0,10,0,0]
      });
    });

    return {
      pageSize: 'A4',
      pageOrientation: 'portrait',
      pageMargins: [40, 76, 40, 110],

      header: function(){
        return {
          margin: [40, 10, 40, 0],
          columns: [
            hasLogo
              ? { image: logoBase64, width: 60, margin:[0,0,0,0] }
              : { text: 'LDR Solutions', bold: true, fontSize: 12, color:'#111827' },
            {
              stack: [
                { text: 'Documento controlado – MRP', alignment:'right', fontSize: 10, color:'#111827', bold:true },
                { text: 'Uso interno', alignment:'right', fontSize: 9, color:'#6b7280' }
              ]
            }
          ]
        };
      },

      footer: function(currentPage, pageCount){
        return {
          margin: [40, 45, 40, 10],
          columns: [
            { text: `${docCode}`, fontSize: 9, color:'#6b7280' },
            { text: `Página ${currentPage} de ${pageCount}`, alignment:'right', fontSize: 9, color:'#6b7280' }
          ]
        };
      },

      content: [
        headerTop,
        { text: 'Resumen', style:'section', margin:[0,8,0,6] },
        resumen,
        { text: 'Detalle por estación', style:'section', margin:[0,10,0,6] },
        ...bloques
      ],

      styles: {
        h1: { fontSize: 16, bold: true, color:'#111827' },
        docCode: { fontSize: 10, color:'#111827', bold:true },
        muted: { fontSize: 9, color:'#6b7280' },
        section: { fontSize: 13, bold: true, color:'#111827' },

        stTitle: { fontSize: 12, bold: true, color:'#ffffff' },
        stSub: { fontSize: 9, color:'#d1d5db' },

        th: { fontSize: 9, bold: true, color:'#111827', margin:[4,4,4,4] },
        td: { fontSize: 9, color:'#111827', margin:[4,4,4,4] },
        th2: { fontSize: 9, bold: true, color:'#111827', fillColor:'#f3f4f6', margin:[4,4,4,4] },
        td2: { fontSize: 9, color:'#111827', margin:[4,4,4,4] },

        boxHead: { fontSize: 9, bold: true, color:'#111827', margin:[6,6,6,6] },
        boxText: { fontSize: 9, color:'#111827', margin:[6,6,6,6] },

        tblHead: { fontSize: 9, bold: true, fillColor:'#111827', color:'#ffffff', margin:[4,4,4,4] },
        tblCellSmall: { fontSize: 8.5, color:'#111827', margin:[4,4,4,4] },
        tblCellJust: { fontSize: 9, color:'#111827', margin:[4,4,4,4], alignment:'justify' },

        totLabel: { fontSize: 10, bold:true, fillColor:'#f3f4f6', color:'#111827', margin:[4,6,4,6] },
        totValueAccent: { fontSize: 9, bold:true, fillColor:'#f3f4f6', color:'#2563eb', margin:[4,6,4,6] }
      },

      defaultStyle: { fontSize: 10 }
    };
  }

  function downloadPdfBlob(docDef, filename) {
    return new Promise((resolve, reject) => {
      try{
        pdfMake.createPdf(docDef).getBlob((blob) => {
          const url = URL.createObjectURL(blob);
          const a = document.createElement('a');
          a.href = url;
          a.download = filename;
          document.body.appendChild(a);
          a.click();
          a.remove();
          URL.revokeObjectURL(url);
          resolve(true);
        });
      } catch (e) {
        reject(e);
      }
    });
  }

  async function handlePdfClick(btn){
    const numOrden = (btn.dataset.numorden || '').trim();
    if (!numOrden){
      Swal.fire({ icon:'warning', title:'Atención', text:'Falta data-numorden', timer:2500, showConfirmButton:false });
      return;
    }

    showLoading('Generando PDF...');

    try{
      const payload = await fetchOrdenData(numOrden);
      const logoBase64 = await getLogoBase64();

      const d = payload?.data || {};
      const docCode = buildDocCode(d);
      const docDef = buildPdfPlaneacionV5(payload, logoBase64, docCode);

      // ✅ AJUSTE: filename P_<OTbase>-<timestamp>.pdf
      // ejemplo: P_OT260115-004-20260115_121530.pdf
      const stamp = nowFileStamp();
      const fileName = `P_${fmt(d.num_orden) || 'OT'}-${stamp}.pdf`;

      await downloadPdfBlob(docDef, fileName);

      hideLoading();
      Swal.fire({ icon:'success', title:'PDF generado', text:'Se descargó el PDF correctamente.', timer:1600, showConfirmButton:false, timerProgressBar:true });

    }catch(err){
      console.error(err);
      hideLoading();
      Swal.fire({ icon:'error', title:'Error', text: err?.message || 'No se pudo generar el PDF', timer:5000, showConfirmButton:false, timerProgressBar:true });
    }
  }

  // =========================================================
  // INIT
  // =========================================================
  const init = () => {
    lockByRules();

    document.body.addEventListener('click', (e) => {
      const btnStart = e.target.closest('.btnStartOT');
      if (btnStart) { handleStartClick(btnStart); return; }

      const btnFinish = e.target.closest('.btnFinishOT');
      if (btnFinish) { handleFinishClick(btnFinish); return; }

      const btnPdf = e.target.closest('.btnPdfOT');
      if (btnPdf) { handlePdfClick(btnPdf); return; }
    });

    initCommentsModal();
    initSyncPolling();

    // ✅ NUEVO: al cargar, aplicar regla comentarios
    applyCommentsRule();
  };

  document.addEventListener('DOMContentLoaded', init);

})();
