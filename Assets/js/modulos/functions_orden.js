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

  // ✅ NUEVO: para filename (YYYYMMDD_HHMMSS)
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

  // Tabla: 0 SubOT, 1 Estatus, 2 Inicio, 3 Fin, 4 Acciones
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
    btnComentarios: tr?.querySelector('.btnCommentOT') || null
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

  // =========================================================
  // Loading (SweetAlert)
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
    try { Swal.close(); } catch (e) {}
  };

  // =========================================================
  // Parseadores / reglas (candados)
  // =========================================================
  const obtenerNumeroSub = (subot) => {
    const m = String(subot || '').match(/-S(\d+)\s*$/i);
    return m ? parseInt(m[1], 10) : 0;
  };

  // ✅ Botones enable/disable consistente (atributo + clase)
  const setBtnEnabled = (btn, enabled) => {
    if (!btn) return;
    btn.disabled = !enabled;
    btn.classList.toggle('disabled', !enabled);
    btn.setAttribute('aria-disabled', String(!enabled));
  };

  // ✅ Estatus desde dataset (preferido) o texto (fallback)
  const obtenerEstatusFila = (tr) => {
    const ds = tr?.dataset?.estatus;
    if (ds !== undefined && ds !== null && String(ds).trim() !== '') {
      const n = parseInt(ds, 10);
      if (!Number.isNaN(n)) return n;
    }

    const td = obtenerCelda(tr, 1);
    const txt = td ? (td.textContent || '').trim().toLowerCase() : '';
    if (txt.includes('final')) return 3;
    if (txt.includes('proceso')) return 2;
    if (txt.includes('deten')) return 4;
    return 1;
  };

  // ✅ Orden de estación (MUY IMPORTANTE para vistas parciales)
  // Prioridad:
  // 1) data-est-orden en fila o contenedor
  // 2) parsear "#2" del título de la estación (por ejemplo: "#2 : Inspección Inicial")
  // 3) fallback 0
  const obtenerOrdenEstacion = (tr) => {
    // 1) en la fila
    const v1 = tr?.dataset?.estOrden || tr?.dataset?.est_orden || tr?.dataset?.estorden;
    if (v1 !== undefined && v1 !== null && String(v1).trim() !== '') {
      const n = parseInt(v1, 10);
      if (!Number.isNaN(n) && n > 0) return n;
    }

    // 2) en contenedor cercano con dataset
    const cont = tr?.closest('[data-est-orden],[data-est_orden],[data-estorden],[data-estorden]');
    if (cont) {
      const v2 = cont.dataset.estOrden || cont.dataset.est_orden || cont.dataset.estorden || cont.getAttribute('data-est-orden');
      const n = parseInt(String(v2 || '').trim(), 10);
      if (!Number.isNaN(n) && n > 0) return n;
    }

    // 3) parsear desde el título del acordeón/estación
    const stationItem = tr?.closest('.accordion-item') || tr?.closest('.station-item') || tr?.closest('[data-station]');
    if (stationItem) {
      const titleEl =
        stationItem.querySelector('.accordion-button') ||
        stationItem.querySelector('.accordion-header') ||
        stationItem.querySelector('h5, h4, h6, .station-title');

      const rawTitle = (titleEl?.textContent || '').trim();
      // Ejemplos que soporta:
      // "#2 : Inspección Inicial" / "#2: Inspección" / "2 - Inspección"
      let m = rawTitle.match(/#\s*(\d+)/);
      if (!m) m = rawTitle.match(/\b(\d+)\s*[:\-]/);

      if (m) {
        const n = parseInt(m[1], 10);
        if (!Number.isNaN(n) && n > 0) return n;
      }
    }

    return 0;
  };

  // ✅ Comentarios solo si estatus = 2
  const aplicarReglaComentarios = () => {
    document.querySelectorAll('tr[data-idorden]').forEach(tr => {
      const st = obtenerEstatusFila(tr);
      const { btnComentarios } = obtenerBotones(tr);
      if (!btnComentarios) return;
      setBtnEnabled(btnComentarios, st === 2);
    });
  };

  // =========================================================
  // ✅ CANDADOS (AJUSTADO PARA:
  //  - SIEMPRE poder FINALIZAR si está en proceso
  //  - vistas parciales: si no existe la estación anterior en DOM, NO bloquear por esa dependencia
  // =========================================================
  const aplicarCandados = () => {
    const filas = Array.from(document.querySelectorAll('tr[data-subot]'));
    if (!filas.length) return;

    // 1) Bloquea todo por defecto (start/finish)
    filas.forEach(tr => {
      const { btnIniciar, btnFinalizar } = obtenerBotones(tr);
      setBtnEnabled(btnIniciar, false);
      setBtnEnabled(btnFinalizar, false);
    });

    // 2) Si hay filas EN PROCESO, SIEMPRE habilita FINALIZAR en esas
    // (esto evita tu bug de: "En proceso" pero no puedo finalizar)
    const enProcesoGlobal = filas.filter(tr => obtenerEstatusFila(tr) === 2);
    if (enProcesoGlobal.length) {
      enProcesoGlobal.forEach(tr => {
        const { btnIniciar, btnFinalizar } = obtenerBotones(tr);
        setBtnEnabled(btnIniciar, false);
        setBtnEnabled(btnFinalizar, true);
      });

      // Comentarios
      aplicarReglaComentarios();
      return; // ✅ si hay algo en proceso visible, no habilites inicios extra
    }

    // 3) Mapear por estación y Sxx (para habilitar el siguiente INICIO correcto)
    const porEstacion = new Map();
    for (const tr of filas) {
      const subot = (tr.dataset.subot || '').trim();
      if (!subot) continue;

      const estacion = obtenerOrdenEstacion(tr);   // 1..n (o 0 si no hay)
      const sn = obtenerNumeroSub(subot);

      // Si estación viene 0 (vista incompleta), no entra a reglas por estación
      if (!sn || !estacion) continue;

      if (!porEstacion.has(estacion)) porEstacion.set(estacion, new Map());
      porEstacion.get(estacion).set(sn, tr);
    }

    const estaciones = Array.from(porEstacion.keys()).sort((a, b) => a - b);

    // 4) Fallback si NO se pudo mapear ninguna estación (vista muy parcial):
    // habilita el primer pendiente por Sxx (solo UI; backend valida real)
    if (!estaciones.length) {
      const candidata = filas
        .slice()
        .filter(tr => obtenerEstatusFila(tr) === 1)
        .sort((a, b) => obtenerNumeroSub(a.dataset.subot) - obtenerNumeroSub(b.dataset.subot))[0];

      if (candidata) {
        const { btnIniciar, btnFinalizar } = obtenerBotones(candidata);
        setBtnEnabled(btnIniciar, true);
        setBtnEnabled(btnFinalizar, false);
      }

      aplicarReglaComentarios();
      return;
    }

    // 5) Reglas por estación:
    // - habilita el primer pendiente que cumpla:
    //   A) S(n-1) finalizada dentro de la MISMA estación
    //   B) MISMA Sxx finalizada en estación anterior SOLO si esa estación anterior está en DOM
    for (const ordenEstacion of estaciones) {
      const mapaSub = porEstacion.get(ordenEstacion);
      const numsSub = Array.from(mapaSub.keys()).sort((a, b) => a - b);

      let candidata = null;

      for (const sn of numsSub) {
        const tr = mapaSub.get(sn);
        const st = obtenerEstatusFila(tr);

        if (st !== 1) continue; // solo pendientes

        // Dep A: dentro de estación
        let depSubOk = true;
        if (sn > 1) {
          const prev = mapaSub.get(sn - 1);
          depSubOk = !!prev && obtenerEstatusFila(prev) === 3;
        }

        // Dep B: entre estaciones (SOLO si existe estación anterior en DOM)
        let depEstacionOk = true;
        if (ordenEstacion > 1) {
          const prevStationMap = porEstacion.get(ordenEstacion - 1);

          // ✅ si no existe la estación anterior (vista por rol/asignación),
          // NO bloquees aquí (backend valida si realmente no se puede)
          if (prevStationMap) {
            const prevRow = prevStationMap.get(sn) || null;
            depEstacionOk = !!prevRow && obtenerEstatusFila(prevRow) === 3;
          } else {
            depEstacionOk = true;
          }
        }

        // Estación 1: S01 siempre inicia
        if (ordenEstacion === 1 && sn === 1) {
          depSubOk = true;
          depEstacionOk = true;
        }

        if (depSubOk && depEstacionOk) { candidata = tr; break; }
      }

      if (candidata) {
        const { btnIniciar, btnFinalizar } = obtenerBotones(candidata);
        setBtnEnabled(btnIniciar, true);
        setBtnEnabled(btnFinalizar, false);
      }
    }

    aplicarReglaComentarios();
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

  // =========================
  // 🔊 SONIDOS (Web Audio API)
  // =========================
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

    // UI optimistic
    setBtnEnabled(btn, false);

    const fecha_inicio = ahoraSql();
    ponerFechaCelda(tr, 2, fecha_inicio);
    ponerBadgeEstatus(tr, 'proceso');
    tr.dataset.estatus = '2';

    // Asegura: si está en proceso, que FINALIZAR se habilite
    const { btnFinalizar } = obtenerBotones(tr);
    setBtnEnabled(btnFinalizar, true);

    const url = base_url + '/plan_planeacion/startOT';
    const payload = { idorden, peid, subot, fecha_inicio };

    mostrarCargando('Iniciando proceso...');

    try {
      const data = await postJson(url, payload);
      ocultarCargando();

      if (!data || data.status === false) {
        ponerFechaCelda(tr, 2, '—');
        ponerBadgeEstatus(tr, 'pendiente');
        tr.dataset.estatus = '1';
        setBtnEnabled(btn, true);
        setBtnEnabled(btnFinalizar, false);

        Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo iniciar', timer: 5000, showConfirmButton: false, timerProgressBar: true });
        aplicarCandados();
        return;
      }

      Swal.fire({ icon: 'success', title: 'Proceso iniciado', text: data?.msg || 'Operación iniciada correctamente', timer: 1400, showConfirmButton: false, timerProgressBar: true });
      await sonidoInicio();
      aplicarCandados();

    } catch (err) {
      console.error(err);
      ocultarCargando();

      ponerFechaCelda(tr, 2, '—');
      ponerBadgeEstatus(tr, 'pendiente');
      tr.dataset.estatus = '1';
      setBtnEnabled(btn, true);
      setBtnEnabled(btnFinalizar, false);

      Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor', timer: 5000, showConfirmButton: false, timerProgressBar: true });
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

    if (!idorden) {
      Swal.fire({ icon: 'warning', title: 'Atención', text: 'Falta data-idorden', timer: 3000, showConfirmButton: false });
      return;
    }
    if (btn.disabled) return;

    // UI optimistic
    setBtnEnabled(btn, false);

    const fecha_fin = ahoraSql();
    ponerFechaCelda(tr, 3, fecha_fin);
    ponerBadgeEstatus(tr, 'finalizada');
    tr.dataset.estatus = '3';

    // En finalizada: iniciar/finish off
    const { btnIniciar } = obtenerBotones(tr);
    setBtnEnabled(btnIniciar, false);

    const url = base_url + '/plan_planeacion/finishOT';
    const payload = { idorden, peid, subot, fecha_fin };

    mostrarCargando('Finalizando proceso...');

    try {
      const data = await postJson(url, payload);
      ocultarCargando();

      if (!data || data.status === false) {
        // revertir UI
        ponerFechaCelda(tr, 3, '—');
        ponerBadgeEstatus(tr, 'proceso');
        tr.dataset.estatus = '2';
        setBtnEnabled(btn, true); // volver a permitir finalizar

        Swal.fire({ icon: 'error', title: 'Error', text: data?.msg || 'No se pudo finalizar', timer: 5000, showConfirmButton: false, timerProgressBar: true });
        aplicarCandados();
        return;
      }

      Swal.fire({ icon: 'success', title: 'Proceso finalizado', text: data?.msg || 'Operación completada correctamente', timer: 1400, showConfirmButton: false, timerProgressBar: true });
      await sonidoFinalizar();
      aplicarCandados();

    } catch (err) {
      console.error(err);
      ocultarCargando();

      // revertir UI
      ponerFechaCelda(tr, 3, '—');
      ponerBadgeEstatus(tr, 'proceso');
      tr.dataset.estatus = '2';
      setBtnEnabled(btn, true);

      Swal.fire({ icon: 'error', title: 'Error de red', text: 'No se pudo conectar con el servidor', timer: 5000, showConfirmButton: false, timerProgressBar: true });
      aplicarCandados();
    }
  };

  // =========================================================
  // ✅ COMENTARIOS SUB-OT (Modal)
  // =========================================================
  const iniciarModalComentarios = () => {
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
      const st = tr ? obtenerEstatusFila(tr) : 0;

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

        const tr = document.querySelector(`tr[data-idorden="${CSS.escape(idorden)}"]`);
        const st = tr ? obtenerEstatusFila(tr) : 0;
        if (st !== 2) {
          Swal.fire({
            icon: 'info',
            title: 'Ups',
            text: 'Finalizaste la orden, ya no puedes cargar comentarios',
            timer: 2600,
            showConfirmButton: false,
            timerProgressBar: true
          });
          try { modal?.hide(); } catch (e) {}
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
  // ✅ SYNC ASÍNCRONO (polling)
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

      // si no cambió nada, no toca
      if ((tr.dataset.estatus || '').trim() === est &&
          (tr.dataset.fi || '') === (row.fecha_inicio || '') &&
          (tr.dataset.ff || '') === (row.fecha_fin || '')
      ) return;

      tr.dataset.estatus = est;
      tr.dataset.fi = row.fecha_inicio || '';
      tr.dataset.ff = row.fecha_fin || '';

      if (est === '1') ponerBadgeEstatus(tr, 'pendiente');
      else if (est === '2') ponerBadgeEstatus(tr, 'proceso');
      else if (est === '3') ponerBadgeEstatus(tr, 'finalizada');
      else if (est === '4') ponerBadgeEstatus(tr, 'detenida');

      ponerFechaCelda(tr, 2, row.fecha_inicio || '—');
      ponerFechaCelda(tr, 3, row.fecha_fin || '—');
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

      const btnPdf = e.target.closest('.btnPdfOT');
      if (btnPdf) { manejarClickPdf(btnPdf); return; }
    });

    iniciarModalComentarios();
    iniciarSyncPolling();

    aplicarReglaComentarios();
  };

  document.addEventListener('DOMContentLoaded', iniciar);

})();
