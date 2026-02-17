(() => {
  'use strict';


  const LOGO_URL = base_url + '/Assets/images/ldr_logo_color.png';

  const ENS_PENDIENTE = 1;
  const ENS_PROCESO = 2;
  const ENS_FINAL = 3;

  const CAL_PEND_INSPECCION = 1;
  const CAL_EN_INSPECCION = 2;
  const CAL_OBSERVACIONES = 3;
  const CAL_RECHAZADO = 4;
  const CAL_LIBERADO = 5;


  const pad2 = (n) => String(n).padStart(2, '0');

  const ahoraSql = () => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = pad2(d.getMonth() + 1);
    const dd = pad2(d.getDate());
    const hh = pad2(d.getHours());
    const mi = pad2(d.getMinutes());
    const ss = pad2(d.getSeconds());
    return `${yyyy}-${mm}-${dd} ${hh}:${mi}:${ss}`;
  };

  const ahoraSelloArchivo = () => {
    const d = new Date();
    const yyyy = d.getFullYear();
    const mm = pad2(d.getMonth() + 1);
    const dd = pad2(d.getDate());
    const hh = pad2(d.getHours());
    const mi = pad2(d.getMinutes());
    const ss = pad2(d.getSeconds());
    return `${yyyy}${mm}${dd}_${hh}${mi}${ss}`;
  };

  const texto = (v) => (v === null || v === undefined) ? "" : String(v);

  const obtenerFila = (btn) => btn?.closest('tr') || null;


  const obtenerCelda = (tr, idx) => {
    if (!tr) return null;
    const tds = tr.querySelectorAll('td');
    return tds && tds[idx] ? tds[idx] : null;
  };

  const ponerFechaCelda = (tr, idx, valor) => {
    const td = obtenerCelda(tr, idx);
    if (!td) return;
    td.innerHTML = `<span class="text-muted">${valor || '—'}</span>`;
  };

  const obtenerBotones = (tr) => ({
    btnIniciar: tr?.querySelector('.btnStartOT') || null,
    btnFinalizar: tr?.querySelector('.btnFinishOT') || null,
    btnComentarios: tr?.querySelector('.btnCommentOT') || null,
    btnIniciarInspeccion: tr?.querySelector('.btnStartInspeccion') || null,
  });

  const ponerBadgeEstatus = (tr, tipo) => {
    const td = obtenerCelda(tr, 1);
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

  const renderBadgeCalidad = (c) => {
    switch (c) {
      case 1: return `<span class="badge bg-secondary-subtle text-secondary">Pendiente de inspección</span>`;
      case 2: return `<span class="badge bg-info-subtle text-info">En inspección</span>`;
      case 3: return `<span class="badge bg-warning-subtle text-warning">Con observaciones</span>`;
      case 4: return `<span class="badge bg-danger-subtle text-danger">Rechazado</span>`;
      case 5: return `<span class="badge bg-success-subtle text-success">Liberado</span>`;
      default: return `<span class="badge bg-light text-muted">—</span>`;
    }
  };

  // =========================================================
  // Swal loading
  // =========================================================
  const mostrarCargando = (textoCarga = 'Procesando...') => {
    Swal.fire({
      title: textoCarga,
      allowOutsideClick: false,
      allowEscapeKey: false,
      didOpen: () => Swal.showLoading()
    });
  };

  const ocultarCargando = () => {
    try { Swal.close(); } catch (e) { }
  };


  const obtenerNumeroSub = (subot) => {
    const m = String(subot || '').match(/-S(\d+)\s*$/i);
    return m ? parseInt(m[1], 10) : 0;
  };

  const setBtnEnabled = (btn, enabled) => {
    if (!btn) return;
    btn.disabled = !enabled;
    btn.classList.toggle('disabled', !enabled);
    btn.setAttribute('aria-disabled', String(!enabled));
  };

  const obtenerEstatusFila = (tr) => {
    const ds = tr?.dataset?.estatus;
    if (ds !== undefined && ds !== null && String(ds).trim() !== '') {
      const n = parseInt(ds, 10);
      if (!Number.isNaN(n)) return n;
    }

    const td = obtenerCelda(tr, 1);
    const txt = td ? (td.textContent || '').trim().toLowerCase() : '';
    if (txt.includes('final')) return ENS_FINAL;
    if (txt.includes('proceso')) return ENS_PROCESO;
    return ENS_PENDIENTE;
  };

  const obtenerCalidadFila = (tr) => {
    const ds = tr?.dataset?.calidad;
    if (ds !== undefined && ds !== null && String(ds).trim() !== '') {
      const n = parseInt(ds, 10);
      if (!Number.isNaN(n)) return n;
    }

    const td = obtenerCelda(tr, 2);
    const txt = td ? (td.textContent || '').trim().toLowerCase() : '';

    if (txt.includes('liberad')) return CAL_LIBERADO;
    if (txt.includes('rechaz')) return CAL_RECHAZADO;
    if (txt.includes('observ')) return CAL_OBSERVACIONES;
    if (txt.includes('en inspe')) return CAL_EN_INSPECCION;
    if (txt.includes('pendiente')) return CAL_PEND_INSPECCION;

    return 0;
  };

  const calidadEnPausa = (tr) => {
    const c = obtenerCalidadFila(tr);
    return (c === CAL_OBSERVACIONES || c === CAL_RECHAZADO);
  };

  const calidadBloqueante = (tr) => {
    const c = obtenerCalidadFila(tr);
    return (c === 0 || c === CAL_PEND_INSPECCION || c === CAL_EN_INSPECCION);
  };

  const calidadLiberada = (tr) => (obtenerCalidadFila(tr) === CAL_LIBERADO);

  const obtenerOrdenEstacion = (tr) => {
    const btnStart = tr?.querySelector('.btnStartOT');
    const vBtn = btnStart?.dataset?.estOrden || btnStart?.getAttribute('data-est-orden');
    if (vBtn !== undefined && vBtn !== null && String(vBtn).trim() !== '') {
      const n = parseInt(String(vBtn).trim(), 10);
      if (!Number.isNaN(n) && n > 0) return n;
    }
    return 0;
  };

  const aplicarReglaComentarios = () => {
    document.querySelectorAll('tr[data-idorden]').forEach(tr => {
      const st = obtenerEstatusFila(tr);
      const { btnComentarios } = obtenerBotones(tr);
      if (!btnComentarios) return;
      setBtnEnabled(btnComentarios, st === ENS_PROCESO);
    });
  };


  const aplicarReglaInspeccion = () => {
    document.querySelectorAll('tr[data-idorden]').forEach(tr => {
      const st = obtenerEstatusFila(tr);
      const { btnIniciarInspeccion } = obtenerBotones(tr);
      if (!btnIniciarInspeccion) return;
      setBtnEnabled(btnIniciarInspeccion, st === ENS_PROCESO);
    });
  };


  const aplicarCandados = () => {
    const filas = Array.from(document.querySelectorAll('tr[data-subot]'));
    if (!filas.length) return;


    filas.forEach(tr => {
      const { btnIniciar, btnFinalizar } = obtenerBotones(tr);
      setBtnEnabled(btnIniciar, false);
      setBtnEnabled(btnFinalizar, false);
    });

    const porEstacion = new Map();

    for (const tr of filas) {
      const subot = (tr.dataset.subot || '').trim();
      if (!subot) continue;

      const estacion = obtenerOrdenEstacion(tr);
      const sn = obtenerNumeroSub(subot);

      if (!sn || !estacion) continue;

      if (!porEstacion.has(estacion)) porEstacion.set(estacion, new Map());
      porEstacion.get(estacion).set(sn, tr);
    }

    const estaciones = Array.from(porEstacion.keys()).sort((a, b) => a - b);


    filas.forEach(tr => {
      if (obtenerEstatusFila(tr) !== ENS_PROCESO) return;
      const { btnFinalizar } = obtenerBotones(tr);
      if (!btnFinalizar) return;

      const puede = calidadLiberada(tr);
      setBtnEnabled(btnFinalizar, puede);
      btnFinalizar.title = puede
        ? 'Finalizar orden'
        : 'No puedes finalizar: Calidad aún no libera (estatus 5).';
    });


    const procesoBloqueanteSn = new Set();
    filas.forEach(tr => {
      if (obtenerEstatusFila(tr) !== ENS_PROCESO) return;
      const sn = obtenerNumeroSub(tr.dataset.subot || '');
      if (!sn) return;
      if (calidadBloqueante(tr)) procesoBloqueanteSn.add(sn);
    });

    for (const ordenEstacion of estaciones) {
      const mapaSub = porEstacion.get(ordenEstacion);
      const numsSub = Array.from(mapaSub.keys()).sort((a, b) => a - b);

      for (const sn of numsSub) {
        const tr = mapaSub.get(sn);
        if (!tr) continue;

        const st = obtenerEstatusFila(tr);


        if (st !== ENS_PENDIENTE) continue;


        if (procesoBloqueanteSn.has(sn)) continue;


        let depSubOk = true;
        if (ordenEstacion === 1 && sn > 1) {
          const prev = mapaSub.get(sn - 1);
          depSubOk = !!prev && (
            obtenerEstatusFila(prev) === ENS_FINAL ||
            calidadEnPausa(prev)
          );
        }

    
        let depEstacionOk = true;
        if (ordenEstacion > 1) {
          const prevStationMap = porEstacion.get(ordenEstacion - 1);
          if (prevStationMap) {
            const prevRow = prevStationMap.get(sn) || null;
            depEstacionOk = !!prevRow && (obtenerEstatusFila(prevRow) === ENS_FINAL);
          } else {
            depEstacionOk = true;
          }
        }

        if (ordenEstacion === 1 && sn === 1) {
          depSubOk = true;
          depEstacionOk = true;
        }

        if (depSubOk && depEstacionOk) {
          const { btnIniciar } = obtenerBotones(tr);
          setBtnEnabled(btnIniciar, true);
        }
      }
    }

    aplicarReglaComentarios();
    aplicarReglaInspeccion();
  };

  // =========================================================
  // Fetch helper 
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


  let __audioCtx = null;

  const asegurarAudio = async () => {
    if (!__audioCtx) __audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    if (__audioCtx.state === 'suspended') await __audioCtx.resume();
  };

  const bip = ({ freq = 880, dur = 0.12, type = 'sine', gain = 0.08, ramp = 0.02 } = {}) => {
    if (!__audioCtx) return;

    const o = __audioCtx.createOscillator();
    const g = __audioCtx.createGain();

    o.type = type;
    o.frequency.setValueAtTime(freq, __audioCtx.currentTime);

    g.gain.setValueAtTime(0.0001, __audioCtx.currentTime);
    g.gain.exponentialRampToValueAtTime(gain, __audioCtx.currentTime + ramp);
    g.gain.exponentialRampToValueAtTime(0.0001, __audioCtx.currentTime + dur);

    o.connect(g);
    g.connect(__audioCtx.destination);

    o.start();
    o.stop(__audioCtx.currentTime + dur + 0.02);
  };

  const sonidoInicio = async () => {
    await asegurarAudio();
    bip({ freq: 660, dur: 0.12, type: 'square', gain: 0.12 });
    setTimeout(() => bip({ freq: 880, dur: 0.12, type: 'square', gain: 0.12 }), 140);
    setTimeout(() => bip({ freq: 1100, dur: 0.14, type: 'square', gain: 0.12 }), 280);
  };

  const sonidoFinalizar = async () => {
    await asegurarAudio();
    bip({ freq: 990, dur: 0.16, type: 'triangle', gain: 0.14 });
    setTimeout(() => bip({ freq: 660, dur: 0.18, type: 'triangle', gain: 0.14 }), 180);
    setTimeout(() => bip({ freq: 1320, dur: 0.08, type: 'sine', gain: 0.10 }), 360);
  };

  // =========================================================
  // INICIAR
  // =========================================================
  const manejarClickIniciar = async (btn) => {
    const tr = obtenerFila(btn);
    if (!tr) return;

    const idorden = (btn.dataset.idorden || '').trim();
    const peid = (btn.dataset.peid || '').trim();
    const subot = (btn.dataset.subot || '').trim();

    if (!idorden) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta data-idorden', timer: 3000, showConfirmButton: false });
      return;
    }
    if (btn.disabled) return;

    setBtnEnabled(btn, false);

    const fecha_inicio = ahoraSql();
    ponerFechaCelda(tr, 3, fecha_inicio);
    ponerBadgeEstatus(tr, 'proceso');
    tr.dataset.estatus = String(ENS_PROCESO);

    const { btnFinalizar } = obtenerBotones(tr);
    setBtnEnabled(btnFinalizar, calidadLiberada(tr));

    const url = base_url + '/plan_planeacion/startOT';
    const payload = { idorden, peid, subot, fecha_inicio };

    mostrarCargando('Iniciando proceso...');

    try {
      const data = await postJson(url, payload);
      ocultarCargando();

      if (!data || data.status === false) {
        ponerFechaCelda(tr, 3, '—');
        ponerBadgeEstatus(tr, 'pendiente');
        tr.dataset.estatus = String(ENS_PENDIENTE);
        setBtnEnabled(btn, true);
        setBtnEnabled(btnFinalizar, false);

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data?.msg || 'No se pudo iniciar',
          timer: 5000,
          showConfirmButton: false,
          timerProgressBar: true
        });
        aplicarCandados();
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'Proceso iniciado',
        text: data?.msg || 'Operación iniciada correctamente',
        timer: 1400,
        showConfirmButton: false,
        timerProgressBar: true
      });

      await sonidoInicio();
      aplicarCandados();

    } catch (err) {
      console.error(err);
      ocultarCargando();

      ponerFechaCelda(tr, 3, '—');
      ponerBadgeEstatus(tr, 'pendiente');
      tr.dataset.estatus = String(ENS_PENDIENTE);
      setBtnEnabled(btn, true);
      setBtnEnabled(btnFinalizar, false);

      Swal.fire({
        icon: 'error',
        title: 'Error de red',
        text: 'No se pudo conectar con el servidor',
        timer: 5000,
        showConfirmButton: false,
        timerProgressBar: true
      });

      aplicarCandados();
    }
  };

  // =========================================================
  // FINALIZAR
  // =========================================================
  const manejarClickFinalizar = async (btn) => {
    const tr = obtenerFila(btn);
    if (!tr) return;

    const idorden = (btn.dataset.idorden || '').trim();
    const peid = (btn.dataset.peid || '').trim();
    const subot = (btn.dataset.subot || '').trim();
    const inventarioid = (btn.dataset.inventarioid || '').trim();

    if (!idorden) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta data-idorden', timer: 3000, showConfirmButton: false });
      return;
    }
    if (btn.disabled) return;

    if (!calidadLiberada(tr)) {
      Swal.fire({
        icon: 'warning',
        title: 'No se puede finalizar',
        text: 'Primero debe estar LIBERADA por Calidad (estatus 5) para poder finalizar esta orden.',
        timer: 3500,
        showConfirmButton: false,
        timerProgressBar: true
      });
      aplicarCandados();
      return;
    }

    setBtnEnabled(btn, false);
 
    const fecha_fin = ahoraSql();
    ponerFechaCelda(tr, 4, fecha_fin);
    ponerBadgeEstatus(tr, 'finalizada');
    tr.dataset.estatus = String(ENS_FINAL);

    const { btnIniciar } = obtenerBotones(tr);
    setBtnEnabled(btnIniciar, false);

    const url = base_url + '/plan_planeacion/finishOT';
    const payload = { idorden, peid, subot, fecha_fin, inventarioid }; 

    mostrarCargando('Finalizando proceso...');

    try {
      const data = await postJson(url, payload);
      ocultarCargando();

      if (!data || data.status === false) {
        ponerFechaCelda(tr, 4, '—');
        ponerBadgeEstatus(tr, 'proceso');
        tr.dataset.estatus = String(ENS_PROCESO);
        setBtnEnabled(btn, true);

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: data?.msg || 'No se pudo finalizar',
          timer: 5000,
          showConfirmButton: false,
          timerProgressBar: true
        });

        aplicarCandados();
        return;
      }

      Swal.fire({
        icon: 'success',
        title: 'Proceso finalizado',
        text: data?.msg || 'Operación completada correctamente',
        timer: 1400,
        showConfirmButton: false,
        timerProgressBar: true
      });

      await sonidoFinalizar();
      aplicarCandados();

    } catch (err) {
      console.error(err);
      ocultarCargando();

      ponerFechaCelda(tr, 4, '—');
      ponerBadgeEstatus(tr, 'proceso');
      tr.dataset.estatus = String(ENS_PROCESO);
      setBtnEnabled(btn, true);

      Swal.fire({
        icon: 'error',
        title: 'Error de red',
        text: 'No se pudo conectar con el servidor',
        timer: 5000,
        showConfirmButton: false,
        timerProgressBar: true
      });

      aplicarCandados();
    }
  };

  // =========================================================
  //  COMENTARIOS SUB-OT (Modal)
  // =========================================================
  const iniciarModalComentarios = () => {
    const modalEl = document.getElementById('modalOTComment');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    const $mSubOT = document.getElementById('mSubOT');
    const $mIdOrden = document.getElementById('mIdOrden');
    const $mPeid = document.getElementById('mPeid');
    const $mComentario = document.getElementById('mComentario');
    const $btnSave = document.getElementById('btnSaveOTComment');

    document.body.addEventListener('click', (e) => {
      const btn = e.target.closest('.btnCommentOT');
      if (!btn) return;

      const tr = btn.closest('tr');
      const st = tr ? obtenerEstatusFila(tr) : 0;

      if (st !== ENS_PROCESO) {
        Swal.fire({
          icon: 'info',
          title: 'Ups',
          text: 'Solo puedes cargar comentarios cuando está En proceso.',
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

        const tr = document.querySelector(`tr[data-idorden="${CSS.escape(idorden)}"]`);
        const st = tr ? obtenerEstatusFila(tr) : 0;

        if (st !== ENS_PROCESO) {
          Swal.fire({
            icon: 'info',
            title: 'Ups',
            text: 'Solo puedes guardar comentarios cuando está En proceso.',
            timer: 2600,
            showConfirmButton: false,
            timerProgressBar: true
          });
          try { modal?.hide(); } catch (e) { }
          return;
        }

        const url = base_url + '/plan_planeacion/setCommentario';
        const payload = { idorden, peid, comentario };

        mostrarCargando('Guardando comentario...');

        try {
          const data = await postJson(url, payload);
          ocultarCargando();

          if (!data || data.status === false) {
            Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo guardar el comentario', timer: 5000, showConfirmButton: false, timerProgressBar: true });
            return;
          }

          if (tr) tr.dataset.coment = comentario;

          Swal.fire({ icon: 'success', title: 'Guardado', text: data?.msg || 'Comentario actualizado', timer: 2000, showConfirmButton: false, timerProgressBar: true });
          modal.hide();

        } catch (err) {
          console.error(err);
          ocultarCargando();
          Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor', timer: 5000, showConfirmButton: false, timerProgressBar: true });
        }
      });
    }
  };

  // =========================================================
  //  SYNC ASÍNCRONO (polling)
  // =========================================================
  const iniciarSyncPolling = () => {
    const planeacionid = (document.getElementById('timeTrackerCard')?.dataset?.planeacion || '').trim();
    if (!planeacionid) return;

    const mapearFilas = () => {
      const m = new Map();
      document.querySelectorAll('tr[data-idorden]').forEach(tr => {
        const id = (tr.dataset.idorden || '').trim();
        if (id) m.set(id, tr);
      });
      return m;
    };

    const aplicarEstatusFila = (tr, row) => {
      const est = String(row.estatus ?? '').trim();
      if (!est) return;

      if (row.calidad !== undefined && row.calidad !== null) {
        const c = parseInt(row.calidad, 10) || 0;
        tr.dataset.calidad = String(c);
        const tdCal = obtenerCelda(tr, 2);
        if (tdCal) tdCal.innerHTML = renderBadgeCalidad(c);
      }

      if ((tr.dataset.estatus || '').trim() === est &&
        (tr.dataset.fi || '') === (row.fecha_inicio || '') &&
        (tr.dataset.ff || '') === (row.fecha_fin || '')
      ) return;

      tr.dataset.estatus = est;
      tr.dataset.fi = row.fecha_inicio || '';
      tr.dataset.ff = row.fecha_fin || '';

      if (est === String(ENS_PENDIENTE)) ponerBadgeEstatus(tr, 'pendiente');
      else if (est === String(ENS_PROCESO)) ponerBadgeEstatus(tr, 'proceso');
      else if (est === String(ENS_FINAL)) ponerBadgeEstatus(tr, 'finalizada');

      ponerFechaCelda(tr, 3, row.fecha_inicio || '—');
      ponerFechaCelda(tr, 4, row.fecha_fin || '—');
    };

    let sincronizando = false;

    const sincronizarServidor = async () => {
      if (sincronizando) return;
      sincronizando = true;

      try {
        const url = base_url + '/plan_planeacion/getStatusOT';
        const data = await postJson(url, { planeacionid });

        if (!data || data.status === false) return;

        const rows = Array.isArray(data.data) ? data.data : [];
        if (!rows.length) return;

        const m = mapearFilas();
        for (const r of rows) {
          const tr = m.get(String(r.idorden || '').trim());
          if (!tr) continue;
          aplicarEstatusFila(tr, r);
        }

        aplicarCandados();

      } catch (e) {
        console.warn('sync error', e);
      } finally {
        sincronizando = false;
      }
    };

    setInterval(sincronizarServidor, 5000);
    sincronizarServidor();
  };



  // =========================================================
  // ====================   PDF SECTION   =====================
  // =========================================================

  function esFechaCero(s) {
    return !s || String(s).startsWith("0000-00-00");
  }

  function parsearMysqlDateTime(s) {
    if (!s || esFechaCero(s)) return null;
    const [datePart, timePart] = String(s).split(' ');
    if (!datePart || !timePart) return null;
    const [Y, M, D] = datePart.split('-').map(Number);
    const [h, m, sec] = timePart.split(':').map(Number);
    if (!Y || !M || !D) return null;
    return new Date(Y, M - 1, D, h || 0, m || 0, sec || 0);
  }

  function segundosDiferencia(inicioStr, finStr) {
    const a = parsearMysqlDateTime(inicioStr);
    const b = parsearMysqlDateTime(finStr);
    if (!a || !b) return 0;
    const ms = b.getTime() - a.getTime();
    if (ms <= 0) return 0;
    return Math.round(ms / 1000);
  }

  function formatoHMSTexto(totalSeconds) {
    const s = Math.max(0, Number(totalSeconds) || 0);
    const hh = Math.floor(s / 3600);
    const mm = Math.floor((s % 3600) / 60);
    const ss = s % 60;

    if (hh > 0) return `${hh} h ${mm} min ${ss} s`;
    if (mm > 0) return `${mm} min ${ss} s`;
    return `${ss} s`;
  }

  function hoyYMD() {
    const d = new Date();
    return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`;
  }

  function construirCodigoDocumento(d) {
    const ot = (d?.num_orden || "OT").replace(/\s+/g, '');
    const ymd = hoyYMD().replaceAll('-', '');
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
  async function obtenerLogoBase64() {
    if (LOGO_BASE64_CACHE) return LOGO_BASE64_CACHE;
    try {
      LOGO_BASE64_CACHE = await urlToBase64(LOGO_URL);
    } catch (e) {
      console.warn('Logo no cargado, se usará texto:', e);
      LOGO_BASE64_CACHE = "";
    }
    return LOGO_BASE64_CACHE;
  }

  async function obtenerDataOrden(numOrden) {
    const url = base_url + '/plan_planeacion/descargarOrden/' + encodeURIComponent(String(numOrden || ''));
    const resp = await fetch(url, { headers: { 'Accept': 'application/json' } });

    const raw = await resp.text();
    let json = null;

    try { json = JSON.parse(raw); }
    catch (e) { throw new Error(`Respuesta inválida (no JSON). Primeros 200: ${raw.slice(0, 200)}`); }

    if (!json || json.status === false) {
      throw new Error(json?.msg || 'No se pudo obtener la data de la OT');
    }
    return json;
  }

  function unirNombres(list) {
    if (!Array.isArray(list) || list.length === 0) return "";
    return list.map(x => x?.nombre_completo).filter(Boolean).join(", ");
  }

  function construirPdfPlaneacionV5(payload, logoBase64, docCode) {
    const d = payload?.data || {};
    const estaciones = Array.isArray(d.estaciones) ? d.estaciones : [];
    const hasLogo = typeof logoBase64 === 'string' && logoBase64.startsWith('data:image');

    const headerTop = {
      table: {
        widths: ['*', '*'],
        body: [
          [
            { text: 'Planeación / Orden de Trabajo', style: 'h1' },
            { text: docCode, style: 'docCode', alignment: 'right' }
          ],
          [
            { text: `OT: ${texto(d.num_orden)}   |   Pedido: ${texto(d.num_pedido)}`, style: 'muted' },
            { text: `Fecha reporte: ${hoyYMD()}`, style: 'muted', alignment: 'right' }
          ]
        ]
      },
      layout: 'noBorders',
      margin: [0, 0, 0, 8]
    };

    const resumen = {
      table: {
        widths: [110, '*', 110, '*'],
        body: [
          [
            { text: 'Producto', style: 'th' },
            { text: `${texto(d.cve_producto)} — ${texto(d.descripcion)}`, style: 'td' },
            { text: 'Prioridad', style: 'th' },
            { text: texto(d.prioridad), style: 'td' }
          ],
          [
            { text: 'Supervisor', style: 'th' },
            { text: texto(d.supervisor), style: 'td' },
            { text: 'Cantidad', style: 'th' },
            { text: texto(d.cantidad), style: 'td' }
          ],
          [
            { text: 'Fecha inicio', style: 'th' },
            { text: texto(d.fecha_inicio), style: 'td' },
            { text: 'Fecha requerida', style: 'th' },
            { text: texto(d.fecha_requerida), style: 'td' }
          ],
          [
            { text: 'Notas', style: 'th' },
            { text: texto(d.notas), style: 'td', colSpan: 3 },
            {}, {}
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
      const encargados = unirNombres(e.encargados) || "—";
      const ayudantes = unirNombres(e.ayudantes) || "—";

      bloques.push({
        table: {
          widths: ['*'],
          body: [[{
            stack: [
              { text: `Estación ${texto(e.orden)} — ${texto(e.nombre_estacion)}`, style: 'stTitle' },
              { text: `Clave: ${texto(e.cve_estacion)}   |   ID estación: ${texto(e.estacionid)}   |   PlaneaciónEstación: ${texto(e.id_planeacion_estacion)}`, style: 'stSub' }
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
            { text: 'Encargado(s)', style: 'th2' },
            { text: encargados, style: 'td2' },
            { text: 'Ayudante(s)', style: 'th2' },
            { text: ayudantes, style: 'td2' }
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
            [{ text: texto(e.proceso) || '—', style: 'boxText' }]
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
        bloques.push({ text: 'Sin sub-órdenes registradas.', style: 'muted', margin: [0, 0, 0, 6] });
        return;
      }

      let totalSeconds = 0;

      const bodyRows = ots.map(o => {
        const secs = segundosDiferencia(o.fecha_inicio, o.fecha_fin);
        totalSeconds += secs;
        const mins = Math.round(secs / 60);

        return ([
          { text: texto(o.num_sub_orden), style: 'tblCellSmall' },
          { text: esFechaCero(o.fecha_inicio) ? "—" : texto(o.fecha_inicio), style: 'tblCellSmall' },
          { text: esFechaCero(o.fecha_fin) ? "—" : texto(o.fecha_fin), style: 'tblCellSmall' },
          { text: texto(o.comentarios) || '—', style: 'tblCellJust' },
          { text: `${mins}`, style: 'tblCellSmall', alignment: 'right' }
        ]);
      });

      bodyRows.push([
        { text: 'Tiempo total', style: 'totLabel', colSpan: 4, alignment: 'right' },
        {}, {}, {},
        { text: formatoHMSTexto(totalSeconds), style: 'totValueAccent', alignment: 'right' }
      ]);

      bloques.push({
        table: {
          headerRows: 1,
          widths: [85, 95, 95, '*', 45],
          body: [
            [
              { text: 'Sub-OT', style: 'tblHead' },
              { text: 'Inicio', style: 'tblHead' },
              { text: 'Fin', style: 'tblHead' },
              { text: 'Comentarios', style: 'tblHead' },
              { text: 'Minutos', style: 'tblHead' }
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
        canvas: [{ type: 'line', x1: 0, y1: 0, x2: 515, y2: 0, lineWidth: 1, lineColor: '#e5e7eb' }],
        margin: [0, 10, 0, 0]
      });
    });

    return {
      pageSize: 'A4',
      pageOrientation: 'portrait',
      pageMargins: [40, 76, 40, 110],

      header: function () {
        return {
          margin: [40, 10, 40, 0],
          columns: [
            hasLogo
              ? { image: logoBase64, width: 60, margin: [0, 0, 0, 0] }
              : { text: 'LDR Solutions', bold: true, fontSize: 12, color: '#111827' },
            {
              stack: [
                { text: 'Documento controlado – MRP', alignment: 'right', fontSize: 10, color: '#111827', bold: true },
                { text: 'Uso interno', alignment: 'right', fontSize: 9, color: '#6b7280' }
              ]
            }
          ]
        };
      },

      footer: function (currentPage, pageCount) {
        return {
          margin: [40, 45, 40, 10],
          columns: [
            { text: `${docCode}`, fontSize: 9, color: '#6b7280' },
            { text: `Página ${currentPage} de ${pageCount}`, alignment: 'right', fontSize: 9, color: '#6b7280' }
          ]
        };
      },

      content: [
        headerTop,
        { text: 'Resumen', style: 'section', margin: [0, 8, 0, 6] },
        resumen,
        { text: 'Detalle por estación', style: 'section', margin: [0, 10, 0, 6] },
        ...bloques
      ],

      styles: {
        h1: { fontSize: 16, bold: true, color: '#111827' },
        docCode: { fontSize: 10, color: '#111827', bold: true },
        muted: { fontSize: 9, color: '#6b7280' },
        section: { fontSize: 13, bold: true, color: '#111827' },

        stTitle: { fontSize: 12, bold: true, color: '#ffffff' },
        stSub: { fontSize: 9, color: '#d1d5db' },

        th: { fontSize: 9, bold: true, color: '#111827', margin: [4, 4, 4, 4] },
        td: { fontSize: 9, color: '#111827', margin: [4, 4, 4, 4] },
        th2: { fontSize: 9, bold: true, color: '#111827', fillColor: '#f3f4f6', margin: [4, 4, 4, 4] },
        td2: { fontSize: 9, color: '#111827', margin: [4, 4, 4, 4] },

        boxHead: { fontSize: 9, bold: true, color: '#111827', margin: [6, 6, 6, 6] },
        boxText: { fontSize: 9, color: '#111827', margin: [6, 6, 6, 6] },

        tblHead: { fontSize: 9, bold: true, fillColor: '#111827', color: '#ffffff', margin: [4, 4, 4, 4] },
        tblCellSmall: { fontSize: 8.5, color: '#111827', margin: [4, 4, 4, 4] },
        tblCellJust: { fontSize: 9, color: '#111827', margin: [4, 4, 4, 4], alignment: 'justify' },

        totLabel: { fontSize: 10, bold: true, fillColor: '#f3f4f6', color: '#111827', margin: [4, 6, 4, 6] },
        totValueAccent: { fontSize: 9, bold: true, fillColor: '#f3f4f6', color: '#2563eb', margin: [4, 6, 4, 6] }
      },

      defaultStyle: { fontSize: 10 }
    };
  }

  function descargarPdfBlob(docDef, filename) {
    return new Promise((resolve, reject) => {
      try {
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




  async function manejarClickPdf(btn) {
    const numOrden = (btn.dataset.numorden || '').trim();
    if (!numOrden) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta data-numorden', timer: 2500, showConfirmButton: false });
      return;
    }

    mostrarCargando('Generando PDF...');

    try {
      const payload = await obtenerDataOrden(numOrden);
      const logoBase64 = await obtenerLogoBase64();

      const d = payload?.data || {};
      const docCode = construirCodigoDocumento(d);
      const docDef = construirPdfPlaneacionV5(payload, logoBase64, docCode);

      const sello = ahoraSelloArchivo();
      const fileName = `P_${texto(d.num_orden) || 'OT'}-${sello}.pdf`;

      await descargarPdfBlob(docDef, fileName);

      ocultarCargando();
      Swal.fire({ icon: 'success', title: 'PDF generado', text: 'Se descargó el PDF correctamente.', timer: 1600, showConfirmButton: false, timerProgressBar: true });

    } catch (err) {
      console.error(err);
      ocultarCargando();
      Swal.fire({ icon: 'error', title: 'Error', text: err?.message || 'No se pudo generar el PDF', timer: 5000, showConfirmButton: false, timerProgressBar: true });
    }
  }

  // =========================================================
  // INIT
  // =========================================================
  const iniciar = () => {
    aplicarCandados();

    document.body.addEventListener('click', (e) => {
      const btnStart = e.target.closest('.btnStartOT');
      if (btnStart) { manejarClickIniciar(btnStart); return; }

      const btnFinish = e.target.closest('.btnFinishOT');
      if (btnFinish) { manejarClickFinalizar(btnFinish); return; }

      const bntPdf = e.target.closest('.btnPdfOT');
      if (bntPdf) { manejarClickPdf(bntPdf); return; }
    });

    iniciarModalComentarios();
    iniciarSyncPolling();
    aplicarReglaComentarios();
    aplicarReglaInspeccion();
  };

  document.addEventListener('DOMContentLoaded', iniciar);

})();














// =========================================================
// ====================== CHAT SUB-OT ======================
// =========================================================



const postJsonLocal = async (url, payload) => {
  const resp = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify(payload)
  });
  const raw = await resp.text();
  try { return JSON.parse(raw); } catch (e) {
    console.error('Respuesta no JSON:', raw);
    return { status: false, msg: 'Respuesta inválida' };
  }
};

let chatPollingTimer = null;
let chatLastId = 0;

// refs
const chatModalEl = document.getElementById('modalChatOT');
const chatModal = chatModalEl ? new bootstrap.Modal(chatModalEl) : null;
const chatBox = document.getElementById('chatMessages');
const chatInput = document.getElementById('chatInput');
const chatSendBtn = document.getElementById('chatSendBtn');
const chatHint = document.getElementById('chatStatusHint');

const chatSubotIn = document.getElementById('chat_subot');
const chatEstacionIn = document.createElement('input');
const chatPlaneacionIn = document.createElement('input');

chatEstacionIn.type = chatPlaneacionIn.type = 'hidden';
chatEstacionIn.id = 'chat_estacionid';
chatPlaneacionIn.id = 'chat_planeacionid';

if (chatModalEl) {
  chatModalEl.appendChild(chatEstacionIn);
  chatModalEl.appendChild(chatPlaneacionIn);
}

const setHint = (t) => { if (chatHint) chatHint.textContent = t; };

const escapeHtml = (s) => {
  return String(s ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#039;');
};

const getInitials = (name) => {
  const n = String(name || '').trim();
  if (!n) return '?';
  const parts = n.split(/\s+/).filter(Boolean);
  return ((parts[0]?.[0] || '?') + (parts[1]?.[0] || '')).toUpperCase();
};

const scrollToBottom = () => {
  if (!chatBox) return;

  requestAnimationFrame(() => {
    requestAnimationFrame(() => {
      chatBox.scrollTop = chatBox.scrollHeight;
    });
  });
};


const renderMessagesVelzon = (rows, mode = 'append') => {
  if (!chatBox) return;

  const myId = String(window.CURRENT_USER_ID || '0');

  const list = (Array.isArray(rows) ? rows : [])
    .slice()
    .sort((a, b) => (parseInt(a.idchat, 10) || 0) - (parseInt(b.idchat, 10) || 0));

  if (mode === 'replace') {
    chatBox.innerHTML = '';
    chatLastId = 0;
  }

  let added = 0;

  for (const r of list) {
    const id = parseInt(r.idchat, 10) || 0;
    if (!id) continue;

    if (mode === 'append' && id <= chatLastId) continue;
    if (id > chatLastId) chatLastId = id;

    const userId = String(r.user_id ?? r.iduser ?? r.userid ?? r.idusuario ?? '');
    const userName = String(r.user_name ?? r.nombre ?? r.usuario ?? 'Usuario');
    const avatar = String(r.user_avatar ?? r.avatar ?? '');
    const msg = String(r.message ?? r.mensaje ?? '');
    const created = String(r.created_at ?? r.fecha ?? '');

    const isMe = (userId && myId && userId === myId);

    const row = document.createElement('div');
    row.className = `v-msg-row ${isMe ? 'me' : 'other'}`;
    const base = base_url.replace(/\/$/, '');

    const avatarSrc = avatar
      ? `${base}/Assets/avatars/${encodeURIComponent(avatar)}`
      : `${base}/Assets/avatars/avatar_default.svg`;

    if (!isMe) {
      const av = document.createElement('div');
      av.className = 'v-avatar';
      if (avatar) av.innerHTML = `<img  src="${avatarSrc}" alt="avatar">`;
      else av.textContent = getInitials(userName);
      row.appendChild(av);
    }

    const bubble = document.createElement('div');
    bubble.className = `v-bubble ${isMe ? 'me' : 'other'}`;
    bubble.innerHTML = `
      <div class="v-meta">
        <div class="v-name">${escapeHtml(userName)}</div>
        <div class="v-time">${escapeHtml(created)}</div>
      </div>
      <div class="v-text">${escapeHtml(msg)}</div>
    `;

    row.appendChild(bubble);
    chatBox.appendChild(row);
    added++;
  }

  if (added > 0) scrollToBottom();
};

// ---------------------------------------------------------
// API calls
// ---------------------------------------------------------
const chatLoad = async () => {
  const payload = {
    subot: chatSubotIn?.value || '',
    estacionid: chatEstacionIn.value || '0',
    planeacionid: chatPlaneacionIn.value || '0'
  };

  setHint('Cargando...');
  const data = await postJsonLocal(base_url + '/plan_planeacion/getChatMessages', payload);

  if (!data?.status) {
    setHint(data?.msg || 'No se pudieron cargar mensajes');
    if (chatBox) chatBox.innerHTML = `<div class="text-center text-muted py-4">Sin mensajes</div>`;
    return;
  }

  renderMessagesVelzon(data.data || [], 'replace');
  setHint('Listo');
};

const chatPoll = async () => {
  const payload = {
    subot: chatSubotIn?.value || '',
    estacionid: chatEstacionIn.value || '0',
    planeacionid: chatPlaneacionIn.value || '0',
    last_id: chatLastId
  };

  try {
    const data = await postJsonLocal(base_url + '/plan_planeacion/getChatMessages', payload);
    if (!data?.status) return;


    renderMessagesVelzon(data.data || [], 'append');
  } catch (e) {
    console.warn('chat poll error', e);
  }
};

// ---------------------------------------------------------
// Abrir chat
// ---------------------------------------------------------
const chatOpen = (btn) => {
  if (!btn || !chatModal) return;

  const subot = btn.dataset.subot || '';
  const estacionid = btn.dataset.estacionid || '0';
  const planeacionid = btn.dataset.planeacionid || '0';

  if (!subot) return;

  // set values
  if (chatSubotIn) chatSubotIn.value = subot;
  chatEstacionIn.value = estacionid;
  chatPlaneacionIn.value = planeacionid;

  const title = document.getElementById('chatSubotTitle');
  if (title) title.textContent = subot;

  // limpia timers
  if (chatPollingTimer) clearInterval(chatPollingTimer);
  chatPollingTimer = null;

  chatModal.show();
};


chatModalEl?.addEventListener('shown.bs.modal', async () => {
  await chatLoad();

  if (chatPollingTimer) clearInterval(chatPollingTimer);
  chatPollingTimer = setInterval(chatPoll, 3000);
});

// al cerrar: limpia
chatModalEl?.addEventListener('hidden.bs.modal', () => {
  if (chatPollingTimer) clearInterval(chatPollingTimer);
  chatPollingTimer = null;
  chatLastId = 0;

  if (chatBox) chatBox.innerHTML = '';
  if (chatInput) chatInput.value = '';
  setHint('Listo');
});

// ---------------------------------------------------------
// Enviar
// ---------------------------------------------------------
const sendMessage = async () => {
  const message = (chatInput?.value || '').trim();
  const subot = chatSubotIn?.value || '';
  if (!subot || !message) return;

  chatInput.value = '';
  setHint('Enviando...');

  const payload = {
    subot,
    estacionid: chatEstacionIn.value || '0',
    planeacionid: chatPlaneacionIn.value || '0',
    message
  };

  const data = await postJsonLocal(base_url + '/plan_planeacion/sendChatMessage', payload);

  if (!data?.status) {
    setHint(data?.msg || 'No se pudo enviar');
    Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo enviar' });
    return;
  }

  // recarga (replace) para asegurar orden y scroll
  await chatLoad();
  setHint('Listo');
};

chatSendBtn?.addEventListener('click', sendMessage);
chatInput?.addEventListener('keydown', (e) => {
  if (e.key === 'Enter') {
    e.preventDefault();
    sendMessage();
  }
});

// Click delegado
document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btnChatOT');
  if (btn) chatOpen(btn);
});



document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-or-descriptiva');
  if (!btn) return;

  openModalOrdenDescriptiva(
    btn.dataset.productoid,
    btn.dataset.descripcion,
    btn.dataset.cantidad
  );
});




async function openModalOrdenDescriptiva(productoid, descripcion, cantidad = 1) {
  productoid = parseInt(productoid, 10) || 0;

  const modalDes = document.getElementById('modalDescriptiva');
  const modal = bootstrap.Modal.getOrCreateInstance(modalDes);

  const tbody = document.getElementById('desTableBody');

  const title = document.getElementById('titleDes');
  if (title) title.textContent = descripcion || 'Producto';

  const titleCant = document.getElementById('titleCantidad');
  if (titleCant) titleCant.textContent = cantidad || '—';


  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center py-4">
        <div class="spinner-border spinner-border-sm"></div>
        <span class="ms-2 text-muted">Cargando ficha técnica…</span>
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/getDescriptiva`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const json = await resp.json();
    if (!json.status) throw new Error(json.msg || 'Error al cargar');


    const arr = json.data?.data || [];
    const info = arr[0] || null;

    if (!info) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-4">No hay ficha técnica registrada</td></tr>`;
      return;
    }

    const fields = [
      ['Marca', 'marca'],
      ['Modelo', 'modelo'],
      ['Motor', 'motor'],
      ['Cilindros', 'cilindros'],
      ['Desplazamiento', 'desplazamiento_c'],
      ['Combustible', 'tipo_combustible'],
      ['Potencia', 'potencia'],
      ['Torque', 'torque'],
      ['Transmisión', 'transmision'],
      ['Dirección', 'direccion'],
      ['Sistema eléctrico', 'sistema_electrico'],
      ['Capacidad combustible', 'capacidad_combustible'],
      ['Largo total', 'largo_total'],
      ['Distancia entre ejes', 'distancia_ejes'],
      ['Peso bruto vehicular', 'peso_bruto_vehicular'],
      ['Llantas', 'llantas'],
      ['Sistema de frenos', 'sistema_frenos'],
      ['Eje delantero', 'eje_delantero'],
      ['Suspensión delantera', 'suspension_delantera'],
      ['Eje trasero', 'eje_trasero'],
      ['Suspensión trasera', 'suspension_trasera'],
      ['Asistencias', 'asistencias'],
      ['Equipamiento', 'equipamiento'],
    ];


    const pairs = fields
      .map(([label, key]) => [label, (info[key] ?? '').toString().trim()])
      .filter(([_, val]) => val !== '');


    let html = '';
    for (let i = 0; i < pairs.length; i += 2) {
      const [k1, v1] = pairs[i];
      const [k2, v2] = pairs[i + 1] || ['', ''];

      html += `
        <tr>
          <td class="key-col">${k1}</td>
          <td class="val-col text-value">${escapeHtml(v1)}</td>
          <td class="key-col">${k2 || ''}</td>
          <td class="val-col text-value">${k2 ? escapeHtml(v2) : ''}</td>
        </tr>
      `;
    }


    if (info.fecha_creacion) {
      html += `
        <tr>
          <td class="key-col">Fecha de registro</td>
          <td class="val-col text-value">${escapeHtml(info.fecha_creacion)}</td>
          <td class="key-col"></td>
          <td class="val-col"></td>
        </tr>
      `;
    }
    

    tbody.innerHTML = html;

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${err.message}</td></tr>`;
  }
}




document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-or-documentacion');
  if (!btn) return;

  openModalOrdenDocumentacion(
    btn.dataset.productoid,
    btn.dataset.descripcion,
    btn.dataset.cantidad
  );
});




async function openModalOrdenDocumentacion(productoid, descripcion, cantidad = 1) {
  productoid = parseInt(productoid, 10) || 0;

  const modalCom = document.getElementById('modalDocumentacion');
  const modal = bootstrap.Modal.getOrCreateInstance(modalCom);
  const tbody = document.getElementById('docTableBody');

  const est = document.getElementById('titleProductoD');
  if (est) est.textContent = descripcion || 'Producto';

  const proc = document.getElementById('titleCantidadD');
  if (proc) proc.textContent = cantidad || 'Cantidad';

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center py-4">
        <div class="spinner-border spinner-border-sm"></div>
        <span class="ms-2 text-muted">Cargando documentación…</span>
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/getDocumentacion`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar');

    const archivos = data.data?.rows || [];

    if (!archivos.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="4" class="text-center text-muted py-4">No hay documentación</td>
        </tr>
      `;
      return;
    }

    const badgeClass = (tipo = '') => {
      const t = (tipo || '').toLowerCase();
      if (t.includes('ayuda')) return 'bg-info';
      if (t.includes('manual')) return 'bg-warning';
      if (t.includes('plano')) return 'bg-primary';
      return 'bg-success';
    };

    tbody.innerHTML = archivos.map(doc => `
      <tr>
        <td>
          <span class="badge ${badgeClass(doc.tipo_documento)}">
            ${escapeHtml(doc.tipo_documento)}
          </span>
        </td>
        <td class="text-muted">${escapeHtml(doc.descripcion || '-')}</td>
        <td>${escapeHtml(doc.fecha_creacion || '-')}</td>
        <td class="text-center">
          <a href="${base_url}/Assets/uploads/doc_componentes/${encodeURIComponent(doc.ruta)}"
             target="_blank"
             class="btn btn-sm btn-outline-primary">
            <i class="ri-eye-line me-1"></i> Ver
          </a>
        </td>
      </tr>
    `).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger py-4">${escapeHtml(err.message)}</td></tr>`;
  }
}






document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-esp');
  if (!btn) return;

  openModalEspecificaciones(
    btn.dataset.productoid,
    btn.dataset.estacionid,
    btn.dataset.estacion,
    btn.dataset.proceso,
    btn.dataset.cantidad
  );
});




async function openModalEspecificaciones(productoid, estacionid, nombreEstacion = '', procesoTxt = '', cantidadPedido = 1) {
  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;
  cantidadPedido = parseFloat(cantidadPedido) || 0;

  const modalCom = document.getElementById('modalEspecificaciones');
  const modal = bootstrap.Modal.getOrCreateInstance(modalCom);
  const tbody = document.getElementById('specTableBody');

  const est = document.getElementById('titleEstacionEs');
  if (est) est.textContent = nombreEstacion || 'Estación';

  const proc = document.getElementById('titleProcesoEs');
  if (proc) proc.textContent = procesoTxt || 'Proceso';

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/getEspecificaciones`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay especificaciones</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => {


      return `
        <tr>
      <td>${r.especificacion || '-'}</td>
     

        </tr>
      `;
    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${err.message}</td></tr>`;
  }
}




document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-comp');
  if (!btn) return;

  openModalComponentes(
    btn.dataset.productoid,
    btn.dataset.estacionid,
    btn.dataset.estacion,
    btn.dataset.proceso,
    btn.dataset.cantidad
  );
});




async function openModalComponentes(productoid, estacionid, nombreEstacion = '', procesoTxt = '', cantidadPedido = 1) {
  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;
  cantidadPedido = parseFloat(cantidadPedido) || 0;

  const modalCom = document.getElementById('modalComponentes');
  const modal = bootstrap.Modal.getOrCreateInstance(modalCom);
  const tbody = document.getElementById('compTableBody');

  const est = document.getElementById('titleEstacion');
  if (est) est.textContent = nombreEstacion || 'Estación';

  const proc = document.getElementById('titleProceso');
  if (proc) proc.textContent = procesoTxt || 'Proceso';

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/getComponentes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay componentes</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => {
      const reqPorUnidad = parseFloat(r.cantidad) || 0;
      const totalRequerido = cantidadPedido * reqPorUnidad;

      return `
        <tr>
          <td>${r.componente || '-'}</td>
          <td class="text-center">${reqPorUnidad}</td>
          <td class="text-center fw-semibold">${totalRequerido}</td>

        </tr>
      `;
    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${err.message}</td></tr>`;
  }
}






document.addEventListener('click', (e) => {
  const btn = e.target.closest('.js-herr');
  if (!btn) return;

  openModalHerramientas(
    btn.dataset.productoid,
    btn.dataset.estacionid,
    btn.dataset.estacion,
    btn.dataset.proceso
  );
});




async function openModalHerramientas(productoid, estacionid, nombreEstacion = '', procesoTxt = '') {
  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;


  const modalHerr = document.getElementById('modalHerramientas');
  const modal = bootstrap.Modal.getOrCreateInstance(modalHerr);
  const tbody = document.getElementById('herrTableBody');

  const est = document.getElementById('titleEstacionH');
  if (est) est.textContent = nombreEstacion || 'Estación';

  const proc = document.getElementById('titleProcesoH');
  if (proc) proc.textContent = procesoTxt || 'Proceso';

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/getHerramientas`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay herramientas</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => {

      return `
        <tr>
          <td>${r.herramienta || '-'}</td>
          <td class="text-center">${r.cantidad}</td>
 

        </tr>
      `;
    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${err.message}</td></tr>`;
  }
}


////////////////////////////////////////////////////
///// funciones para inspección de calidad ////////


document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btnInspeccionCalidad');
  if (!btn) return;

  openModalInspeccionCalidad(
    btn.dataset.productoid,
    btn.dataset.estacionid,
    btn.dataset.estacion,
    btn.dataset.proceso,
    btn.dataset.cantidad,
    btn.dataset.idorden,
    btn.dataset.numorden
  );
});

// ============================
// Helpers
// ============================
const $ = (id) => document.getElementById(id);


function esc(str) {
  return String(str ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", "&#039;");
}

function isImageFile(fileNameOrMime = '') {
  const s = String(fileNameOrMime).toLowerCase();
  return s.includes('image/') || s.endsWith('.jpg') || s.endsWith('.jpeg') || s.endsWith('.png') || s.endsWith('.webp');
}

function setButtonsByEstado() {
  const btnLiberar = $('btnLiberarCalidad');
  const btnPausar = $('btnPausarCalidad');
  const tbody = $('calidadTableBody');

  if (!btnLiberar || !btnPausar || !tbody) return;

  const trs = [...tbody.querySelectorAll('tr[data-especificacionid]')];

  if (!trs.length) {
    btnLiberar.classList.add('d-none');
    btnPausar.classList.add('d-none');
    return;
  }

  let okCount = 0;
  let noCount = 0;
  let pending = 0;

  trs.forEach(tr => {
    const radios = [...tr.querySelectorAll('.resultado-radio')];
    const selected = radios.find(r => r.checked);

    if (!selected) pending++;
    else if (selected.value === 'OK') okCount++;
    else if (selected.value === 'NO_OK') noCount++;
  });


  if (pending > 0) {
    btnLiberar.classList.add('d-none');
    btnPausar.classList.add('d-none');
    return;
  }

  if (noCount > 0) {
    btnLiberar.classList.add('d-none');
    btnPausar.classList.remove('d-none');
    return;
  }


  btnLiberar.classList.remove('d-none');
  btnPausar.classList.add('d-none');
}


document.addEventListener('change', (e) => {
  const radio = e.target.closest('.resultado-radio');
  if (!radio) return;

  const tr = radio.closest('tr');
  const comentario = tr.querySelector('.comentario');

  // if (radio.value === 'NO_OK' && radio.checked) {
  //   comentario.disabled = false;
  //   comentario.required = true;
  //   comentario.focus();
  // }

  // if (radio.value === 'OK' && radio.checked) {
  //   comentario.value = '';
  //   comentario.disabled = true;
  //   comentario.required = false;
  // }


  comentario.disabled = false;

  if (radio.value === 'NO_OK' && radio.checked) {
    comentario.required = true;
    comentario.focus();
  } else if (radio.value === 'OK' && radio.checked) {
    comentario.required = false;


  }



  tr.classList.remove('table-danger');
  const err = tr.querySelector('.row-error');
  if (err) err.classList.add('d-none');


  setButtonsByEstado();
});




function renderLocalEvidenceLinks(tr) {
  const input = tr.querySelector('.evidencia-file');
  const box = tr.querySelector('.evidencia-links');
  if (!input || !box) return;

  const especId = parseInt(tr.dataset.especificacionid || 0, 10) || 0;


  [...box.querySelectorAll('[data-blob-url]')].forEach(a => {
    try { URL.revokeObjectURL(a.getAttribute('data-blob-url')); } catch { }
  });

  let localWrap = box.querySelector('.evidencia-local');
  if (!localWrap) {
    localWrap = document.createElement('div');
    localWrap.className = 'evidencia-local mt-1';
    box.appendChild(localWrap);
  }
  localWrap.innerHTML = '';

  const files = input.files ? [...input.files] : [];
  if (!files.length) {

    return;
  }

  const list = document.createElement('div');
  list.className = 'd-flex flex-column gap-1';

  files.forEach((file, index) => {
    const blobUrl = URL.createObjectURL(file);
    const isImg = isImageFile(file.type || file.name);

    const row = document.createElement('div');
    row.className = 'd-flex align-items-center justify-content-between gap-2';

    row.innerHTML = `
      <div class="d-flex align-items-center gap-2">
        <a href="javascript:void(0)"
           class="link-primary text-decoration-underline evidencia-open-blob"
           data-blob-url="${esc(blobUrl)}"
           data-especid="${especId}"
           data-index="${index}">
           Ver ${isImg ? 'imagen' : 'archivo'}
        </a>
   
      </div>

      <button type="button"
        class="btn btn-sm btn-outline-danger py-0 px-2 evidencia-remove-local"
        data-especid="${especId}"
        data-index="${index}"
        title="Quitar archivo">
        <i class="ri-delete-bin-6-line"></i>
      </button>
    `;
    list.appendChild(row);
  });

  localWrap.appendChild(list);
}


function removeLocalFileFromInput(input, removeIndex) {
  const dt = new DataTransfer();
  const files = input.files ? [...input.files] : [];
  files.forEach((f, idx) => {
    if (idx !== removeIndex) dt.items.add(f);
  });
  input.files = dt.files;
}


document.addEventListener('change', (e) => {
  const input = e.target.closest('.evidencia-file');
  if (!input) return;
  const tr = input.closest('tr[data-especificacionid]');
  if (!tr) return;

  renderLocalEvidenceLinks(tr);
});


document.addEventListener('click', (e) => {
  const a = e.target.closest('.evidencia-open-blob');
  if (!a) return;

  const url = a.getAttribute('data-blob-url');
  if (!url) return;

  window.open(url, '_blank', 'noopener');
});


document.addEventListener('click', (e) => {
  const btn = e.target.closest('.evidencia-remove-local');
  if (!btn) return;

  const tr = btn.closest('tr[data-especificacionid]');
  if (!tr) return;

  const input = tr.querySelector('.evidencia-file');
  if (!input) return;

  const idx = parseInt(btn.getAttribute('data-index') || '-1', 10);
  if (idx < 0) return;


  const link = tr.querySelector(`.evidencia-open-blob[data-index="${idx}"]`);
  if (link) {
    const blobUrl = link.getAttribute('data-blob-url');
    if (blobUrl) {
      try { URL.revokeObjectURL(blobUrl); } catch { }
    }
  }

  removeLocalFileFromInput(input, idx);
  renderLocalEvidenceLinks(tr);
});


async function openModalInspeccionCalidad(
  productoid,
  estacionid,
  nombreEstacion = '',
  procesoTxt = '',
  cantidadPedido = 1,
  idorden,
  numot
) {
  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;
  idorden = parseInt(idorden, 10) || 0;

  const myId = String(window.CURRENT_ROL_ID || '0');
  const isUser5 = parseInt(myId, 10) === 5;


  const btnPausarEl = document.getElementById('btnPausarCalidad');
  const btnLiberarEl = document.getElementById('btnLiberarCalidad');

  if (btnPausarEl) btnPausarEl.style.visibility = isUser5 ? 'visible' : 'hidden';
  if (btnLiberarEl) btnLiberarEl.style.visibility = isUser5 ? 'visible' : 'hidden';

  // ---------------------------------------------------------
  // Modal 
  // ---------------------------------------------------------
  const modalCom = $('modalInspeccionCalidad');
  const modal = bootstrap.Modal.getOrCreateInstance(modalCom);
  const tbody = $('calidadTableBody');

  $('titleEstacionCal').textContent = nombreEstacion || 'Estación';
  $('titleProcesoCal').textContent = procesoTxt || 'Proceso';
  $('numSubOrdenT').textContent = numot || '-';

  $('calidad_idorden').value = idorden;
  $('calidad_numot').value = String(numot || '');
  $('estacion_id').value = estacionid;

  const prodHidden = $('producto_id');
  if (prodHidden) prodHidden.value = productoid;


  $('btnLiberarCalidad').classList.add('d-none');
  $('btnPausarCalidad').classList.add('d-none');

  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/getEspecificaciones`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar');

    const rows = data.data?.rows || [];
    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay especificaciones</td></tr>`;
      setButtonsByEstado();
      return;
    }

    // Render tabla
    tbody.innerHTML = rows.map((r, idx) => {
      const especTxt = (r.especificacion || '-');
      const especId = r.idespecificacion || r.especificacionid || (idx + 1);
      const nameResultado = `res_${especId}`;

      const evidenciaBlock = isUser5
        ? `
          <input type="file"
                 class="form-control form-control-sm evidencia-file"
                 accept=".jpg,.jpeg,.png,.pdf"
                 multiple>

          <button type="button" class="btn btn-sm btn-outline-secondary btnCamTake mt-2">
            <i class="ri-camera-line me-1"></i> Tomar foto
          </button>

          <div class="text-muted small mt-1">Foto o PDF (opcional).</div>
          <div class="evidencia-links mt-2 small"></div>
        `
        : `
          <div class="text-muted small">Evidencia: solo disponible para encargados de estaciones.</div>
          <div class="evidencia-links mt-2 small"></div>
        `;

      return `
        <tr data-especificacionid="${especId}">
          <td>
            <div class="fw-semibold">${esc(especTxt)}</div>
            <div class="text-muted small">${esc(r.fecha_creacion || '-')}</div>
            <div class="text-danger small mt-1 d-none row-error">Revisa esta fila.</div>
          </td>

          <td class="text-center align-middle">
            <div class="d-flex justify-content-center align-items-center flex-wrap gap-3">
              <div class="form-check form-check-success">
                <input class="form-check-input resultado-radio" type="radio"
                       name="${nameResultado}" value="OK" id="${nameResultado}_ok">
                <label class="form-check-label" for="${nameResultado}_ok">Aprobar</label>
              </div>

              <div class="form-check form-check-danger">
                <input class="form-check-input resultado-radio" type="radio"
                       name="${nameResultado}" value="NO_OK" id="${nameResultado}_no">
                <label class="form-check-label" for="${nameResultado}_no">Pausar</label>
              </div>
            </div>
          </td>

          <td>
            ${evidenciaBlock}
          </td>

          <td>
            <textarea class="form-control form-control-sm comentario"
                      rows="4"
                      placeholder="Comentario (solo si es Pausar)"
                      disabled></textarea>
          </td>
        </tr>
      `;
    }).join('');


    try {
      const respPrev = await fetch(`${base_url}/plan_planeacion/getInspeccionCalidad`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ idorden, estacionid })
      });

      const prev = await respPrev.json().catch(() => null);

      if (respPrev.ok && prev && prev.status) {
        const detPrev = prev.data?.detalle || [];

        const mapPrev = new Map();
        detPrev.forEach(x => {
          const eid = parseInt(x.especificacionid, 10) || 0;
          if (eid > 0) mapPrev.set(eid, x);
        });

        const trs = [...tbody.querySelectorAll('tr[data-especificacionid]')];

        trs.forEach(tr => {
          const eid = parseInt(tr.dataset.especificacionid, 10) || 0;
          const info = mapPrev.get(eid);
          if (!info) return;


          const radios = [...tr.querySelectorAll('.resultado-radio')];
          const rOk = radios.find(r => r.value === 'OK');
          const rNo = radios.find(r => r.value === 'NO_OK');

          if (info.resultado === 'OK' && rOk) rOk.checked = true;
          if (info.resultado === 'NO_OK' && rNo) rNo.checked = true;

          // Comentario
          const txt = tr.querySelector('.comentario');
          if (txt) {
            txt.disabled = false;

            if (info.resultado === 'NO_OK') {
              txt.required = true;
              txt.value = (info.comentario || '');
            } else {
              txt.required = false;
            }
          }


          const list = Array.isArray(info.evidencias) ? info.evidencias : [];
          if (!list.length) return;

          const box = tr.querySelector('.evidencia-links');
          if (!box) return;

          let savedWrap = box.querySelector('.evidencia-saved');
          if (!savedWrap) {
            savedWrap = document.createElement('div');
            savedWrap.className = 'evidencia-saved';
            box.appendChild(savedWrap);
          }

          const UPLOAD_PATH = `${base_url}/Assets/uploads/calidad_evidencias/`;

          savedWrap.innerHTML = `
            <div class="d-flex flex-column gap-1">
              ${list.map((ev, idx) => {
            const name = ev.nombre_original || ev.archivo || `evidencia_${idx + 1}`;
            const file = ev.archivo || '';
            const url = file ? (UPLOAD_PATH + encodeURIComponent(file)) : '#';
            const isImg = (ev.mime && String(ev.mime).startsWith('image/')) ||
              (String(name).toLowerCase().match(/\.(jpg|jpeg|png|webp)$/));

            return `
                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <div class="d-flex align-items-center gap-2">
                      <a class="link-success text-decoration-underline"
                         href="${url}"
                         target="_blank" rel="noopener">
                        Ver ${isImg ? 'imagen' : 'archivo'}
                      </a>
                    </div>

                    <!-- Solo oculta el link (no borra servidor) -->
                    <button type="button"
                      class="btn btn-sm btn-outline-info py-0 px-2 evidencia-hide-saved"
                      title="Ocultar de la vista">
                      <i class="ri-eye-off-line"></i>
                    </button>
                  </div>
                `;
          }).join('')}
            </div>
          `;
        });
      }
    } catch (e) {
      // silencioso
    }

    setButtonsByEstado();
  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${err.message}</td></tr>`;
    setButtonsByEstado();
  }
}





document.addEventListener('click', (e) => {
  const btn = e.target.closest('.evidencia-hide-saved');
  if (!btn) return;

  const row = btn.closest('div.d-flex');
  if (row) row.remove();
});


function buildPayloadCalidad() {
  const idorden = parseInt($('calidad_idorden')?.value || 0, 10) || 0;
  const numot = String($('calidad_numot')?.value || '');

  const estacionid = parseInt($('estacion_id')?.value || 0, 10) || 0;

  const tbody = $('calidadTableBody');
  const trs = [...tbody.querySelectorAll('tr[data-especificacionid]')];

  const detalle = trs.map(tr => {
    const especificacionid = parseInt(tr.dataset.especificacionid, 10) || 0;
    const selected = [...tr.querySelectorAll('.resultado-radio')].find(r => r.checked);
    const resultado = selected ? selected.value : '';
    const comentario = (tr.querySelector('.comentario')?.value || '').trim();

    return { especificacionid, resultado, comentario };
  });

  return { idorden, numot, detalle, estacionid };
}

document.addEventListener('click', (e) => {
  if (!e.target.closest('#btnLiberarCalidad')) return;


  const payload = buildPayloadCalidad();
  console.log('LIBERAR payload:', payload);


});


async function enviarInspeccionCalidad(accion = 'PAUSAR') {
  const idorden = parseInt(document.getElementById('calidad_idorden')?.value || 0, 10) || 0;
  const numot = String(document.getElementById('calidad_numot')?.value || '');
  const estacion = document.getElementById('modalInspeccionCalidad');
  const tbody = document.getElementById('calidadTableBody');

  const productoid = parseInt(document.getElementById('producto_id')?.value || 0, 10) || 0;
  const estacionid = parseInt(document.getElementById('estacion_id')?.value || 0, 10) || 0;

  const trs = [...tbody.querySelectorAll('tr[data-especificacionid]')];

  if (!trs.length) {
    alert('No hay especificaciones para enviar.');
    return;
  }

  // ----------------------------
  // Validación según acción
  // ----------------------------
  let hasError = false;

  trs.forEach(tr => {
    tr.classList.remove('table-danger');
    const err = tr.querySelector('.row-error');
    if (err) err.classList.add('d-none');

    const especId = parseInt(tr.dataset.especificacionid || 0, 10) || 0;
    const selected = [...tr.querySelectorAll('.resultado-radio')].find(r => r.checked);
    const comentario = (tr.querySelector('.comentario')?.value || '').trim();
    const filesCount = tr.querySelector('.evidencia-file')?.files?.length || 0;

    if (!selected) {
      hasError = true;
      tr.classList.add('table-danger');
      if (err) { err.textContent = 'Selecciona Aprobar o Pausar.'; err.classList.remove('d-none'); }
      return;
    }

    if (accion === 'LIBERAR') {

      if (selected.value !== 'OK') {
        hasError = true;
        tr.classList.add('table-danger');
        if (err) { err.textContent = 'Para liberar, todo debe estar en Aprobar.'; err.classList.remove('d-none'); }
        return;
      }
    }

    if (accion === 'PAUSAR' && selected.value === 'NO_OK') {
      if (!comentario) {
        hasError = true;
        tr.classList.add('table-danger');
        if (err) { err.textContent = 'Comentario requerido para Pausar.'; err.classList.remove('d-none'); }
        return;
      }

      if (filesCount <= 0) {
        hasError = true;
        tr.classList.add('table-danger');
        if (err) { err.textContent = 'Evidencia requerida para Pausar.'; err.classList.remove('d-none'); }
        return;
      }
    }
  });

  if (hasError) return;

  // ----------------------------
  // Construir  JSON
  // ----------------------------
  const detalle = trs.map(tr => {
    const especId = parseInt(tr.dataset.especificacionid || 0, 10) || 0;
    const selected = [...tr.querySelectorAll('.resultado-radio')].find(r => r.checked);
    return {
      especificacionid: especId,
      resultado: selected ? selected.value : '',
      comentario: (tr.querySelector('.comentario')?.value || '').trim()
    };
  });


  const fd = new FormData();
  fd.append('accion', accion);
  fd.append('idorden', String(idorden));
  fd.append('numot', numot);
  fd.append('productoid', String(productoid));
  fd.append('estacionid', String(estacionid));
  fd.append('detalle', JSON.stringify(detalle));


  trs.forEach(tr => {
    const especId = parseInt(tr.dataset.especificacionid || 0, 10) || 0;
    const input = tr.querySelector('.evidencia-file');
    const files = input?.files ? [...input.files] : [];
    files.forEach(file => {
      fd.append(`evidencia_${especId}[]`, file);
    });
  });

  // ----------------------------
  // Fetch
  // ----------------------------
  try {
    const resp = await fetch(`${base_url}/plan_planeacion/setInspeccionCalidad`, {
      method: 'POST',
      body: fd
    });

    const data = await resp.json().catch(() => null);
    if (!resp.ok || !data || !data.status) {
      const msg = (data && data.msg) ? data.msg : `Error HTTP ${resp.status}`;
      throw new Error(msg);
    }

    Swal.fire({ icon: 'success', title: 'OK', text: data.msg || 'Guardado' })
      .then(() => {
        const modalEl = document.getElementById('modalInspeccionCalidad');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.hide();
        window.location.reload();
      });

  } catch (err) {
    alert(err.message);
  }
}

// Botones
document.addEventListener('click', (e) => {
  if (e.target.closest('#btnLiberarCalidad')) {
    enviarInspeccionCalidad('LIBERAR');
  }
  if (e.target.closest('#btnPausarCalidad')) {
    enviarInspeccionCalidad('PAUSAR');
  }
});


// =====================================================
// FUNCIONNES PARA VER LA INSPEeCCIÓN CAPTURADA POR CALIDADA
// =====================================================



document.addEventListener('click', (e) => {
  const btn = e.target.closest('.btnViewInspeccionCalidad');
  if (!btn) return;

  openModalVerInspeccion(
    btn.dataset.estacionid,
    btn.dataset.idorden,
    btn.dataset.estacion,
    btn.dataset.proceso,
    btn.dataset.numorden
  );
});




async function openModalVerInspeccion(estacionid, idorden, nombreEstacion = '', nombreProceso = '', numOrden = '') {
  estacionid = parseInt(estacionid, 10) || 0;
  idorden = parseInt(idorden, 10) || 0;

  const modalEl = $('modalViewInspeccionCalidad');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);


  const tbody = $('calidadViewTableBody');

  const numorden = $('numSubOrdenTView');
  if (numorden) numorden.textContent = numOrden || '-';

  const est = $('titleEstacionCalView');
  if (est) est.textContent = nombreEstacion || 'Estación';

  const proc = $('titleProcesoCalView');
  if (proc) proc.textContent = nombreProceso || 'Proceso';


  tbody.innerHTML = `
    <tr>
      <td colspan="4" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;


  const setText = (id, val) => { const el = $(id); if (el) el.textContent = (val ?? '—'); };

  setText('viewInspectorNombre', '—');
  setText('viewInspectorEmail', '—');
  setText('viewFechaInicio', '—');
  setText('viewFechaCierre', '—');

  const badge = $('viewEstadoBadge');
  if (badge) {
    badge.className = 'badge rounded-pill border fs-12';
    badge.textContent = '—';
  }

  setText('viewCountOk', 0);
  setText('viewCountNoOk', 0);
  setText('viewCountEv', 0);

  const resumenBox = $('viewResumenComentarios');
  if (resumenBox) resumenBox.innerHTML = `<div class="text-muted small">—</div>`;

  modal.show();

  try {
    const respPrev = await fetch(`${base_url}/plan_planeacion/getViewInspeccionCalidad`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ idorden, estacionid })
    });

    const prev = await respPrev.json().catch(() => null);

    if (!respPrev.ok || !prev || !prev.status) {
      throw new Error(prev?.msg || `Error HTTP ${respPrev.status}`);
    }

    const header = prev.data?.header || {};
    const detalle = prev.data?.detalle || [];


    const nombreInspector = `${header.nombres || ''} ${header.apellidos || ''}`.trim() || '—';
    setText('viewInspectorNombre', nombreInspector);
    setText('viewInspectorEmail', header.email_user || '—');
    setText('viewFechaInicio', header.fecha_creacion || '—');
    setText('viewFechaCierre', header.fecha_cierre || '—');

    if (badge) {
      if (parseInt(header.estado, 10) === 2) {
        badge.className = 'badge rounded-pill bg-success-subtle text-success border border-success-subtle fs-12';
        badge.innerHTML = `<i class="ri-check-double-line me-1"></i> Liberada`;
      } else if (parseInt(header.estado, 10) === 1) {
        badge.className = 'badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle fs-12';
        badge.innerHTML = `<i class="ri-pause-circle-line me-1"></i> Pausada`;
      } else {
        badge.className = 'badge rounded-pill bg-secondary-subtle text-secondary border border-secondary-subtle fs-12';
        badge.innerHTML = `<i class="ri-question-line me-1"></i> Sin estado`;
      }
    }

    if (!detalle.length) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay inspección guardada.</td></tr>`;
      return;
    }


    const UPLOAD_PATH = `${base_url}/Assets/uploads/calidad_evidencias/`;

    const fmtSize = (n) => {
      n = parseInt(n || 0, 10) || 0;
      if (n < 1024) return `${n} B`;
      if (n < 1024 * 1024) return `${(n / 1024).toFixed(1)} KB`;
      return `${(n / (1024 * 1024)).toFixed(1)} MB`;
    };

    const isImg = (mime, file) => {
      const m = String(mime || '').toLowerCase();
      const f = String(file || '').toLowerCase();
      return m.startsWith('image/') || f.endsWith('.jpg') || f.endsWith('.jpeg') || f.endsWith('.png') || f.endsWith('.webp');
    };


    let countOk = 0, countNo = 0, countEv = 0;

    const noOkResumen = [];

    detalle.forEach(d => {
      const res = String(d.resultado || '');
      const evs = Array.isArray(d.evidencias) ? d.evidencias : [];
      countEv += evs.length;

      if (res === 'OK') countOk++;
      if (res === 'NO_OK') {
        countNo++;
        const motivo = (d.comentario_no_ok || '').trim();
        const corr = (d.accion_correctiva || '').trim();

        noOkResumen.push({
          especificacion: d.especificacion || `Especificación ${d.especificacionid}`,
          motivo,
          corr,
          evidencias: evs.length
        });
      }
    });

    setText('viewCountOk', countOk);
    setText('viewCountNoOk', countNo);
    setText('viewCountEv', countEv);

    if (resumenBox) {
      if (!noOkResumen.length) {
        resumenBox.innerHTML = `<div class="text-success small"><i class="ri-check-line me-1"></i> No hay NO OK registrados en esta inspección.</div>`;
      } else {
        resumenBox.innerHTML = `
          <div class="text-danger small fw-semibold mb-2">
            <i class="ri-alarm-warning-line me-1"></i> NO OK registrados:
          </div>
          <div class="d-flex flex-column gap-2">
            ${noOkResumen.map(x => `
              <div class="p-2 rounded border bg-danger-subtle">
                <div class="fw-semibold small">${esc(x.especificacion)}</div>
                <div class="small"><span class="text-danger fw-semibold">Motivo:</span> ${x.motivo ? esc(x.motivo) : '<span class="text-muted">—</span>'}</div>
                <div class="small"><span class="text-success fw-semibold">Acción correctiva:</span> ${x.corr ? esc(x.corr) : '<span class="text-muted">—</span>'}</div>
                <div class="small text-muted mt-1">Evidencias: <b>${x.evidencias}</b></div>
              </div>
            `).join('')}
          </div>
        `;
      }
    }


    tbody.innerHTML = detalle.map((d) => {
      const res = d.resultado || '';
      const evs = Array.isArray(d.evidencias) ? d.evidencias : [];

      const badgeRes = (res === 'NO_OK')
        ? `<span class="badge bg-danger-subtle text-danger border border-danger-subtle">
             <i class="ri-close-circle-line me-1"></i> NO OK
           </span>`
        : `<span class="badge bg-success-subtle text-success border border-success-subtle">
             <i class="ri-checkbox-circle-line me-1"></i> OK
           </span>`;

      const evHtml = evs.length
        ? `
          <div class="d-flex flex-column gap-1">
            ${evs.map((ev) => {
          const file = ev.archivo || '';
          const url = file ? (UPLOAD_PATH + encodeURIComponent(file)) : '#';
          const icon = isImg(ev.mime, file) ? 'ri-image-2-line text-primary' : 'ri-file-pdf-2-line text-danger';
          const label = isImg(ev.mime, file) ? 'Ver imagen' : 'Ver archivo';

          return `
                <div class="d-flex align-items-center justify-content-between gap-2">
                  <a class="link-primary text-decoration-underline"
                     href="${url}" target="_blank" rel="noopener">
                    <i class="${icon} me-1"></i> ${label}
                  </a>
                  <span class="text-muted small">${fmtSize(ev.size_bytes)}</span>
                </div>
                <div class="text-muted small" title="${esc(ev.nombre_original || file)}">
                  ${esc(ev.nombre_original || file)}
                </div>
              `;
        }).join('')}
          </div>
        `
        : `<span class="text-muted">—</span>`;


      const motivo = (d.comentario_no_ok || '').trim();
      const corr = (d.accion_correctiva || '').trim();

      const comentariosHtml = `
        <div class="small">
          <div><span class="text-danger fw-semibold">Motivo:</span> ${motivo ? esc(motivo) : '<span class="text-muted">—</span>'}</div>
          <div class="mt-1"><span class="text-success fw-semibold">Correctiva:</span> ${corr ? esc(corr) : '<span class="text-muted">—</span>'}</div>
        </div>
      `;

      return `
        <tr class="${res === 'NO_OK' ? 'table-danger' : ''}">
          <td>
            <div class="fw-semibold">${esc(d.especificacion || `Especificación ${d.especificacionid}`)}</div>
        
          </td>

          <td class="text-center align-middle">
            ${badgeRes}
          </td>

          <td>
            ${evHtml}
          </td>

          <td>
            ${comentariosHtml}
          </td>
        </tr>
      `;
    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="4" class="text-center text-danger">${esc(err.message)}</td></tr>`;
  }
}









// =====================================================
// FUNCIONNES CÁMARA 
// =====================================================


let __filaObjetivoCamara = null;


let __streamCamara = null;


let __camaraLado = 'environment';


let __archivosSesionCamara = [];

// -----------------------------------------------------
// Detener cámar
// -----------------------------------------------------
function detenerCamara() {
  if (__streamCamara) {
    __streamCamara.getTracks().forEach(t => t.stop());
    __streamCamara = null;
  }
}

// -----------------------------------------------------
// Iniciar cámara
// -----------------------------------------------------
async function iniciarCamara(lado = 'environment') {
  detenerCamara();

  const video = document.getElementById('camVideo');
  const info = document.getElementById('camInfo');

  const constraints = {
    video: {
      facingMode: { ideal: lado },
      width: { ideal: 1280 },
      height: { ideal: 720 }
    },
    audio: false
  };

  __streamCamara = await navigator.mediaDevices.getUserMedia(constraints);
  video.srcObject = __streamCamara;
  __camaraLado = lado;

  if (info) {
    info.textContent = (lado === 'environment') ? 'Cámara trasera' : 'Cámara frontal';
  }
}

function agregarArchivosAInput(input, nuevosArchivos) {
  const dt = new DataTransfer();
  const existentes = input.files ? [...input.files] : [];

  existentes.forEach(f => dt.items.add(f));
  nuevosArchivos.forEach(f => dt.items.add(f));

  input.files = dt.files;
}

// -----------------------------------------------------
// Crear archivo desde canvtas
// -----------------------------------------------------
function archivoDesdeCanvas(canvas, nombreArchivo) {
  return new Promise(resolve => {
    canvas.toBlob(blob => {
      resolve(new File([blob], nombreArchivo, { type: 'image/jpeg' }));
    }, 'image/jpeg', 0.92);
  });
}

// -----------------------------------------------------
// Mostrar miniatuhras de la sesión
// -----------------------------------------------------
function renderizarMiniaturasCamara() {
  const contenedor = document.getElementById('camThumbs');
  if (!contenedor) return;

  contenedor.innerHTML = '';

  __archivosSesionCamara.forEach((file, index) => {
    const url = URL.createObjectURL(file);

    const div = document.createElement('div');
    div.className = 'position-relative';
    div.style.width = '84px';
    div.style.height = '84px';

    div.innerHTML = `
      <img src="${url}" class="rounded border w-100 h-100" style="object-fit:cover"
           data-thumb-url="${url}">
      <button type="button"
        class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1 p-0"
        style="width:22px;height:22px"
        data-cam-eliminar="${index}">
        <i class="ri-close-line"></i>
      </button>
    `;

    contenedor.appendChild(div);
  });
}


document.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-cam-eliminar]');
  if (!btn) return;

  const index = parseInt(btn.dataset.camEliminar, 10);
  if (index < 0) return;

  __archivosSesionCamara.splice(index, 1);

  document.querySelectorAll('img[data-thumb-url]').forEach(img => {
    try { URL.revokeObjectURL(img.dataset.thumbUrl); } catch { }
  });

  renderizarMiniaturasCamara();
});

// -----------------------------------------------------
// Abrir cámarat
// -----------------------------------------------------
document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.btnCamTake');
  if (!btn) return;

  const tr = btn.closest('tr[data-especificacionid]');
  if (!tr) return;

  if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
    alert('La cámara no está disponible. Usa el selector de archivos.');
    return;
  }

  __filaObjetivoCamara = tr;
  __archivosSesionCamara = [];

  const modalEl = document.getElementById('modalCamCalidad');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  modal.show();

  try {
    await iniciarCamara('environment');
    renderizarMiniaturasCamara();
  } catch {
    try {
      await iniciarCamara('user');
      renderizarMiniaturasCamara();
    } catch {
      alert('No se pudo abrir la cámara. Revisa permisos o HTTPS.');
      modal.hide();
    }
  }
});

// -----------------------------------------------------
// Cambiar cámara
// -----------------------------------------------------
document.addEventListener('click', async (e) => {
  if (!e.target.closest('#btnCamSwitch')) return;

  const nuevoLado = (__camaraLado === 'environment') ? 'user' : 'environment';
  try {
    await iniciarCamara(nuevoLado);
  } catch { }
});

// -----------------------------------------------------
// Tomar foto
// -----------------------------------------------------
document.addEventListener('click', async (e) => {
  if (!e.target.closest('#btnCamShot')) return;

  const video = document.getElementById('camVideo');
  const canvas = document.getElementById('camCanvas');
  if (!video || !canvas) return;

  canvas.width = video.videoWidth || 1280;
  canvas.height = video.videoHeight || 720;

  canvas.getContext('2d').drawImage(video, 0, 0);

  const tr = __filaObjetivoCamara;
  const especId = tr ? parseInt(tr.dataset.especificacionid, 10) : 0;

  const d = new Date();
  const nombre = `cam_${d.getTime()}_ES${especId}.jpg`;

  const archivo = await archivoDesdeCanvas(canvas, nombre);
  __archivosSesionCamara.push(archivo);

  renderizarMiniaturasCamara();
});

// -----------------------------------------------------
// Usar fotos y cerrrar
// -----------------------------------------------------
document.addEventListener('click', (e) => {
  if (!e.target.closest('#btnCamUse')) return;
  if (!__filaObjetivoCamara) return;

  const input = __filaObjetivoCamara.querySelector('.evidencia-file');
  if (!input) return;

  if (__archivosSesionCamara.length) {
    agregarArchivosAInput(input, __archivosSesionCamara);


    renderLocalEvidenceLinks(__filaObjetivoCamara);
  }

  __archivosSesionCamara = [];

  const modalEl = document.getElementById('modalCamCalidad');
  bootstrap.Modal.getOrCreateInstance(modalEl).hide();
});


document.getElementById('modalCamCalidad')?.addEventListener('hidden.bs.modal', () => {
  detenerCamara();

  document.querySelectorAll('img[data-thumb-url]').forEach(img => {
    try { URL.revokeObjectURL(img.dataset.thumbUrl); } catch { }
  });

  const thumbs = document.getElementById('camThumbs');
  if (thumbs) thumbs.innerHTML = '';

  __filaObjetivoCamara = null;
  __archivosSesionCamara = [];
});







document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.js-or-iniciar');
  if (!btn) return;

  const idplaneacion = parseInt(btn.dataset.idplaneacion, 10) || 0;
  if (!idplaneacion) return;

  const res = await Swal.fire({
    title: '¿Iniciar producción?',
    text: 'Se marcará la orden como EN PRODUCCIÓN y quedará en ejecución.',
    icon: 'question',
    showCancelButton: true,
    confirmButtonText: 'Sí, iniciar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  });

  if (!res.isConfirmed) return;

  // UI: bloqueo para evitar doble click
  btn.disabled = true;

  try {
    await iniciarPlaneacion(idplaneacion);

    await Swal.fire({
      title: 'Producción iniciada',
      text: 'La planeación fue marcada como EN PRODUCCIÓN.',
      icon: 'success',
      timer: 1400,
      showConfirmButton: false
    });

        window.location.reload();

  } catch (err) {
    await Swal.fire({
      title: 'No se pudo iniciar',
      text: err?.message || 'Ocurrió un error inesperado.',
      icon: 'error'
    });
  } finally {
    btn.disabled = false;
  }
});


async function iniciarPlaneacion(idplaneacion) {
  idplaneacion = parseInt(idplaneacion, 10) || 0;
  if (!idplaneacion) throw new Error('ID inválido.');

  const resp = await fetch(`${base_url}/plan_planeacion/iniciarPlaneacion`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
    body: JSON.stringify({ idplaneacion })
  });

  if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

  const data = await resp.json();
  if (!data.status) throw new Error(data.msg || 'Error al iniciar producción');

  return data;
}

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.js-or-finalizar');
  if (!btn) return;

  const idplaneacion = parseInt(btn.dataset.idplaneacion, 10) || 0;
  if (!idplaneacion) return;

  const res = await Swal.fire({
    title: '¿Finalizar producción?',
    text: 'Se marcará la orden como FINALIZADA y se cerrará la ejecución.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sí, finalizar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  });

  if (!res.isConfirmed) return;

  btn.disabled = true;

  try {
    const resp = await fetch(`${base_url}/plan_planeacion/finalizarPlaneacion`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ idplaneacion })
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al finalizar');

    await Swal.fire({
      title: 'Producción finalizada',
      text: 'La planeación fue cerrada correctamente.',
      icon: 'success',
      timer: 1300,
      showConfirmButton: false
    });

    window.location.reload();

  } catch (err) {
    await Swal.fire({
      title: 'No se pudo finalizar',
      text: err?.message || 'Ocurrió un error inesperado.',
      icon: 'error'
    });
    btn.disabled = false;
  }
});




