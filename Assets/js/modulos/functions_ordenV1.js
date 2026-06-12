let payloadCriticosPendiente = null;
let origenAccionProduccion = '';
document.addEventListener('DOMContentLoaded', function () {

let timer = null;
let seconds = 0;
let timerStartMs = 0;
let timerNodeKey = '';
  let currentNode = null;
  let currentSelection = null;
  let MRP_STATE = JSON.parse(JSON.stringify(MRP_DATA || {}));

  let timerParo = null;
  let secondsParo = 0;

  let CURRENT_PRODUCTOID = 0;
  let CURRENT_ORIGENID = 0;
  let CURRENT_TIPO = '';
  let CURRENT_IDORDEN = 0;
  let CURRENT_UNIDAD = '';

  let registrosCriticosPendientes = [];

  let origenDecisionNoConforme = '';

  let contextoAccionProduccion = {
    productoid: 0,
    estacionid: 0,
    idordengeneral: 0,
    unidad_actual: '',
    origen_accion: 2
  };

  const tiempoActualEstacion = document.getElementById('tiempoActualEstacion');
  const tiempoUnidadGlobal = document.getElementById('tiempoUnidadGlobal');

  const btnIniciarUnidad = document.getElementById('btnIniciarUnidad');
  const btnFinalizarUnidad = document.getElementById('btnFinalizarUnidad');
  const btnPausarUnidad = document.getElementById('btnPausarUnidad');
  const btnIniciarProduccion = document.getElementById('btnIniciarProduccion');
  const btnFinalizarProduccion = document.getElementById('btnFinalizarProduccion');
  const btnExpandirTodo = document.getElementById('btnExpandirTodo');
  const btnContraerTodo = document.getElementById('btnContraerTodo');

  const flowTitle = document.querySelector('.mrp-flow-title');
  const flowSubtitle = document.querySelector('.mrp-flow-subtitle');
  const flowVisualRule = document.querySelector('.mrp-soft-note');

  const detalleTituloNodo = document.getElementById('detalleTituloNodo');
  const detalleTipoNodo = document.getElementById('detalleTipoNodo');
  const detalleUnidadActual = document.getElementById('detalleUnidadActual');
  const detalleUnidadActualTexto = document.getElementById('detalleUnidadActualTexto');
  const detalleUnidadActualCard = document.getElementById('detalleUnidadActualCard');
  const detalleEstadoUnidad = document.getElementById('detalleEstadoUnidad');
  const detalleBadgePrioridad = document.getElementById('detalleBadgePrioridad');
  const detalleResumenUnidad = document.getElementById('detalleResumenUnidad');
  const detalleOrdenTrabajo = document.getElementById('detalleOrdenTrabajo');
  const detalleSupervisor = document.getElementById('detalleSupervisor');
  const detalleTipoCorto = document.getElementById('detalleTipoCorto');
  const detalleEncargado = document.getElementById('detalleEncargado');
  const detalleAyudante = document.getElementById('detalleAyudante');
  const detalleTiempoAjuste = document.getElementById('detalleTiempoAjuste');
  const detalleInspectorCalidad = document.getElementById('detalleInspectorCalidad');
  const detalleInspectorCriticos = document.getElementById('detalleInspectorCriticos');

  const cardInspectorCalidad = document.getElementById('cardInspectorCalidad');
  const cardInspectorCriticos = document.getElementById('cardInspectorCriticos');

  const detalleCalidad = document.getElementById('detalleCalidad');
  const detalleDescripcionProceso = document.getElementById('detalleDescripcionProceso');

  const detalleSemaforo = document.getElementById('detalleSemaforo');
  const detalleSemaforoTitulo = document.getElementById('detalleSemaforoTitulo');
  const detalleSemaforoTexto = document.getElementById('detalleSemaforoTexto');

  const btnDetalleHerramientas = document.getElementById('btnDetalleHerramientas');
  const btnDetalleComponentes = document.getElementById('btnDetalleComponentes');
  const btnDetalleOperaciones = document.getElementById('btnDetalleOperaciones');
  const btnDetalleAyudas = document.getElementById('btnDetalleAyudas');
  const btnDetallePuntosCalidad = document.getElementById('btnDetallePuntosCalidad');

  const btnCalidad = document.getElementById('btnCalidad');
  const btnOperacionesCriticas = document.getElementById('btnOperacionesCriticas');
  const btnEstamparVin = document.getElementById('btnEstamparVin');

  const contenedorParoMomentaneo = document.getElementById('contenedorParoMomentaneo');

  const timerParoMomentaneo = document.getElementById('timerParoMomentaneo');

  const btnReanudarParo = document.getElementById('btnReanudarParo');

  const contenedorMensajesProceso = document.getElementById('contenedorMensajesProceso');
  const contenedorUnidadesFueraLinea = document.getElementById('contenedorUnidadesFueraLinea');

  if (btnPausarUnidad) {
    btnPausarUnidad.addEventListener('click', function () {

      origenAccionProduccion = 'manual';
      abrirModalAccionProduccion('paro_manual', {
        productoid: currentNode.productoid,
        estacionid: currentNode.estacionid,
        idordengeneral: currentNode.idordengeneral,
        unidad_actual: currentNode.unit
      });
    });
  }

  //FUNCIONES DE PAR

  function renderTimerParo() {

    if (!timerParoMomentaneo) return;

    timerParoMomentaneo.textContent =
      formatTime(secondsParo);
  }

  function iniciarTimerParo() {

    if (timerParo) return;

    timerParo = setInterval(() => {

      secondsParo++;

      renderTimerParo();

    }, 1000);
  }

  function detenerTimerParo() {

    if (timerParo) {

      clearInterval(timerParo);

      timerParo = null;
    }
  }

  function resetTimerParo() {

    detenerTimerParo();

    secondsParo = 0;

    renderTimerParo();
  }


  function obtenerParoActivoEstacion(node) {

    if (!node || !node.unidadRaw) {
      return null;
    }

    return (node.unidadRaw.acciones_produccion || []).find(a =>
      Number(a.tipo_accion) === 1 &&
      Number(a.estado) === 2 &&
      Number(a.estacionid) === Number(node.estacionid)
    ) || null;
  }

  function validarParoMomentaneo(node) {

    if (!node || node.type !== 'estacion' || !node.unidadRaw) {
      if (contenedorParoMomentaneo) {
        contenedorParoMomentaneo.classList.add('d-none');
        contenedorParoMomentaneo.style.display = 'none';
      }

      resetTimerParo();
      return false;
    }

    const paroActivo = (node.unidadRaw.acciones_produccion || []).find(a =>
      Number(a.tipo_accion) === 1 &&
      Number(a.estado) === 2 &&
      Number(a.estacionid) === Number(node.estacionid) &&
      String(a.unidad || '').trim() === String(node.unit || '').trim()
    );

    const existeParoActivo = !!paroActivo;

    if (contenedorParoMomentaneo) {
      if (existeParoActivo) {
        contenedorParoMomentaneo.classList.remove('d-none', 'none');
        contenedorParoMomentaneo.style.display = '';
        renderDetalleParoMomentaneo(node, paroActivo);
      } else {
        contenedorParoMomentaneo.classList.add('d-none');
        contenedorParoMomentaneo.style.display = 'none';
      }
    }

    if (!existeParoActivo) {
      resetTimerParo();
      return false;
    }

    if (btnFinalizarUnidad) {
      btnFinalizarUnidad.disabled = true;
      btnFinalizarUnidad.classList.add('btn-state-disabled');
    }

    if (btnPausarUnidad) {
      btnPausarUnidad.disabled = true;
      btnPausarUnidad.classList.add('btn-state-disabled');
      btnPausarUnidad.textContent = 'Paro activo';
    }

    secondsParo = getElapsedSeconds(paroActivo.fecha_inicio);
    renderTimerParo();
    iniciarTimerParo();

    return true;
  }




  //END FUNCIOENS DE PARO

  function formatTime(totalSeconds) {
    const mins = Math.floor(totalSeconds / 60).toString().padStart(2, '0');
    const secs = (totalSeconds % 60).toString().padStart(2, '0');
    return `${mins}:${secs}`;
  }

  function renderTime() {
    const t = formatTime(seconds);
    if (tiempoActualEstacion) tiempoActualEstacion.textContent = t;
    if (tiempoUnidadGlobal) tiempoUnidadGlobal.textContent = t;
  }

function iniciarTimer() {

  if (timer) {
    clearInterval(timer);
  }

  timer = setInterval(() => {

    if (!timerStartMs) {
      seconds = 0;
      renderTime();
      return;
    }

    seconds = Math.floor(
      (Date.now() - timerStartMs) / 1000
    );

    renderTime();

  }, 1000);
}

  function pausarTimer() {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
  }

function reiniciarTimer() {

  pausarTimer();

  timerStartMs = 0;
  timerNodeKey = '';

  seconds = 0;

  renderTime();
}

  function getStation(estIndex) {
    return (MRP_STATE?.estaciones || [])[estIndex] || null;
  }

  function getSubassembly(estIndex, subIndex) {
    const est = getStation(estIndex);
    if (!est) return null;
    return (est.subensambles || [])[subIndex] || null;
  }


  //nyevas funciones


  // function esAdministrador() {
  //   return Number(window.CURRENT_ROL_ID || 0) === 1;
  // }

  function esAdministrador() {

    const rol =
      Number(window.CURRENT_ROL_ID || 0);

    const userId =
      Number(window.CURRENT_USER_ID || 0);

    const supervisorId =
      Number(MRP_STATE?.supervisorid || 0);

    return (
      rol === 1 ||                // Administrador
      userId === supervisorId     // Supervisor de producción
    );
  }

  function esUsuarioCalidad() {

    const userId =
      Number(window.CURRENT_USER_ID || 0);

    return (MRP_STATE.estaciones || []).some(est => {

      const esInspectorPdi =
        (est.encargado_pdi || []).some(u =>
          Number(u.usuarioid) === userId
        );

      const esInspectorCritico =
        (est.encargado_punto_critico || []).some(u =>
          Number(u.usuarioid) === userId
        );

      return esInspectorPdi || esInspectorCritico;

    });
  }

function validarPermisosProduccion() {

  const esCalidad = esUsuarioCalidad();

  [
    btnIniciarUnidad,
    btnFinalizarUnidad,
    btnPausarUnidad
  ].forEach(btn => {

    if (!btn) return;

    btn.style.display = esCalidad ? 'none' : '';
  });


  validarBotonesProduccion();
}


  function usuarioAsignadoEstacion(estacion) {

    const userId = Number(window.CURRENT_USER_ID || 0);

    const esEncargado =
      (estacion.encargados || []).some(u =>
        Number(u.usuarioid) === userId
      );

    const esAyudante =
      (estacion.ayudantes || []).some(u =>
        Number(u.usuarioid) === userId
      );

    const esInspectorCalidad =
      (estacion.encargado_pdi || []).some(u =>
        Number(u.usuarioid) === userId
      );

    const esInspectorCritico =
      (estacion.encargado_punto_critico || []).some(u =>
        Number(u.usuarioid) === userId
      );

    const tieneCalidad =
      Number(estacion.calidad || 0) === 1;

    const tieneCriticos =
      String(estacion.tiene_especificaciones_criticas || '')
        .toLowerCase() === 'si';

    return (
      esEncargado ||
      esAyudante ||
      (tieneCalidad && esInspectorCalidad) ||
      (tieneCriticos && esInspectorCritico)
    );
  }

  function usuarioAsignadoSubensamble(sub) {

    const userId = Number(window.CURRENT_USER_ID || 0);

    return (
      (sub.encargados || []).some(u =>
        Number(u.usuarioid) === userId
      ) ||
      (sub.ayudantes || []).some(u =>
        Number(u.usuarioid) === userId
      )
    );
  }

  function estacionTieneSubensamblesAsignados(estacion) {

    return (estacion.subensambles || []).some(sub =>
      usuarioAsignadoSubensamble(sub)
    );
  }



  function aplicarFiltroVisualUsuario() {

    const esAdmin = esAdministrador();
    let existeSubPermitidoGlobal = false;

    (MRP_STATE.estaciones || []).forEach((estacion, estIndex) => {

      const wrap = document.querySelector(
        `.mrp-station-wrap[data-est-index="${estIndex}"]`
      );

      const zonaSub = document.querySelector(
        `.mrp-sub-zone[data-est-index="${estIndex}"]`
      );

      const estacionCard = document.querySelector(
        `.js-selectable-node[data-node-type="estacion"][data-est-index="${estIndex}"]`
      );

      const puedeVerEstacion =
        esAdmin || usuarioAsignadoEstacion(estacion);

      let tieneSubPermitido = false;

      if (estacionCard) {
        estacionCard.dataset.permitidoUsuario = puedeVerEstacion ? '1' : '0';
        estacionCard.classList.toggle('mrp-hidden-by-user', !puedeVerEstacion);
      }

      (estacion.subensambles || []).forEach((sub, subIndex) => {

        const puedeVerSub =
          esAdmin || usuarioAsignadoSubensamble(sub);

        const subCard = document.querySelector(
          `.js-selectable-node[data-node-type="subensamble"][data-est-index="${estIndex}"][data-sub-index="${subIndex}"]`
        );

        if (!subCard) return;

        subCard.dataset.permitidoUsuario = puedeVerSub ? '1' : '0';

        if (puedeVerSub) {
          tieneSubPermitido = true;
          existeSubPermitidoGlobal = true;
        }

        const estaContraida =
          zonaSub?.classList.contains('is-collapsed');

        subCard.classList.toggle(
          'mrp-hidden-by-user',
          !puedeVerSub || estaContraida
        );
      });

      if (zonaSub) {
        zonaSub.classList.toggle(
          'mrp-hidden-by-user',
          !tieneSubPermitido
        );
      }

      const mostrarColumna =
        puedeVerEstacion || tieneSubPermitido;

      if (wrap) {
        wrap.classList.toggle('mrp-hidden-by-user', !mostrarColumna);
        wrap.classList.toggle('has-visible-station', puedeVerEstacion);
        wrap.classList.toggle('has-visible-sub', tieneSubPermitido);
      }
    });

    const flowRow = document.querySelector('.mrp-flow-row');


    if (flowRow) {

      const haySubensamblesContraidos =
        flowRow.classList.contains('only-stations-row');

      flowRow.classList.toggle(
        'has-sub-row',
        existeSubPermitidoGlobal && !haySubensamblesContraidos
      );

      flowRow.classList.toggle(
        'only-stations-row',
        !existeSubPermitidoGlobal || haySubensamblesContraidos
      );
    }

    ordenarSubensamblesArriba();
    reordenarFlujoVisible();
    validarControlesFlujo();
  }


  function ordenarSubensamblesArriba() {

    document.querySelectorAll('.mrp-station-wrap').forEach(wrap => {

      const zonaSub = wrap.querySelector('.mrp-sub-zone');
      const estacion = wrap.querySelector('.mrp-station-card');

      if (zonaSub && estacion) {
        wrap.insertBefore(zonaSub, estacion);
      }
    });
  }



  function reordenarFlujoVisible() {

    const flowRow =
      document.querySelector('.mrp-flow-row');

    if (!flowRow) return;

    const visibles = Array.from(
      flowRow.querySelectorAll('.mrp-station-wrap')
    ).filter(wrap =>
      !wrap.classList.contains('mrp-hidden-by-user')
    );

    const arrows = Array.from(
      flowRow.querySelectorAll('.mrp-arrow')
    );

    flowRow.innerHTML = '';

    visibles.forEach((wrap, index) => {

      flowRow.appendChild(wrap);

      if (index < visibles.length - 1) {

        const arrow =
          arrows[index] ||
          document.createElement('div');

        arrow.className = 'mrp-arrow';

        flowRow.appendChild(arrow);
      }
    });
  }

  function seleccionarPrimerNodoVisibleUsuario() {

    const visibles = Array.from(
      document.querySelectorAll('.js-selectable-node')
    ).filter(el =>
      el.offsetParent !== null &&
      el.style.display !== 'none'
    );

    if (!visibles.length) return;

    visibles.sort((a, b) => {

      const aSub =
        a.dataset.nodeType === 'subensamble';

      const bSub =
        b.dataset.nodeType === 'subensamble';

      if (aSub && !bSub) return -1;
      if (!aSub && bSub) return 1;

      return 0;
    });

    const primerNodo = visibles[0];

    const type = primerNodo.dataset.nodeType;

    const estIndex =
      parseInt(primerNodo.dataset.estIndex, 10);

    const subIndex =
      primerNodo.dataset.subIndex !== undefined
        ? parseInt(primerNodo.dataset.subIndex, 10)
        : null;

    const node =
      getNodeData(type, estIndex, subIndex);

    if (!node) return;

    fillDetailPanel(node);

    highlightSelected(primerNodo);
  }



  function validarControlesFlujo() {

    let estacionesPermitidas = 0;
    let subensamblesPermitidos = 0;

    document.querySelectorAll('.mrp-station-wrap').forEach(wrap => {

      if (wrap.style.display === 'none') return;

      const estacionCard = wrap.querySelector(
        '.js-selectable-node[data-node-type="estacion"]'
      );

      const tieneEstacionPermitida =
        estacionCard &&
        estacionCard.dataset.permitidoUsuario === '1';

      const tieneSubensamblePermitido =
        Array.from(
          wrap.querySelectorAll('.js-selectable-node[data-node-type="subensamble"]')
        ).some(sub =>
          sub.dataset.permitidoUsuario === '1'
        );

      if (tieneEstacionPermitida) {
        estacionesPermitidas++;
      }

      if (tieneSubensamblePermitido) {
        subensamblesPermitidos++;
      }
    });

    const mostrarControles =
      estacionesPermitidas > 0 &&
      subensamblesPermitidos > 0;

    if (btnExpandirTodo) {
      btnExpandirTodo.style.display =
        mostrarControles ? '' : 'none';
    }

    if (btnContraerTodo) {
      btnContraerTodo.style.display =
        mostrarControles ? '' : 'none';
    }

    if (flowSubtitle) {
      flowSubtitle.style.display =
        mostrarControles ? '' : 'none';
    }

    if (flowVisualRule) {
      flowVisualRule.style.display =
        mostrarControles ? '' : 'none';
    }
  }



  //end nuevas funciones

  function esRetiroAgvActivo(orden) {
    return (
      Number(orden?.accion_produccion || 0) === 2 &&
      Number(orden?.accion_activa || 0) === 2
    );
  }

  function unidadRetiradaEnEstacionesPrevias(estIndex, unidad) {

    if (!unidad) return false;

    for (let i = 0; i < estIndex; i++) {

      const estAnterior = getStation(i);

      const ordenRetirada = (estAnterior?.ordenes_trabajo || []).find(o => {

        const mismaUnidad =
          o.num_sub_orden === unidad;

        const retiroActivo =
          Number(o.accion_produccion || 0) === 2 &&
          Number(o.accion_activa || 0) === 2;

        return mismaUnidad && retiroActivo;
      });

      if (ordenRetirada) {
        return true;
      }
    }

    return false;
  }

  function getOrdenPorUnidadEnEstacion(estIndex, unidad) {
    const est = getStation(estIndex);

    return (est?.ordenes_trabajo || []).find(o =>
      o.num_sub_orden === unidad
    ) || null;
  }

  function subensambleEntregadoParaUnidad(est, unidad) {
    const subensambles = est?.subensambles || [];

    if (!subensambles.length) return true;

    return subensambles.some(sub => {
      const subOrder = (sub.ordenes_trabajo || []).find(o =>
        o.num_sub_orden === unidad
      );

      return subOrder && Number(subOrder.estado) === 4;
    });
  }

  function puedeMostrarseEnEstacion(orden, estIndex) {
    if (!orden) return false;

    const unidad = orden.num_sub_orden || '';

    if (!unidad) return false;

    if (esRetiroAgvActivo(orden)) {
      return false;
    }

    if (unidadRetiradaEnEstacionesPrevias(estIndex, unidad)) {
      return false;
    }

    if (Number(orden.estatus) === 2) {
      return true;
    }

    if (Number(orden.estatus) !== 1) {
      return false;
    }

    const est = getStation(estIndex);

    if (!subensambleEntregadoParaUnidad(est, unidad)) {
      return false;
    }

    if (estIndex > 0) {
      const ordenAnterior = getOrdenPorUnidadEnEstacion(estIndex - 1, unidad);

      if (!ordenAnterior || Number(ordenAnterior.estatus) !== 3) {
        return false;
      }
    }

    return true;
  }

  function getCurrentOrder(ordenes, type, estIndex = 0) {

    ordenes = ordenes || [];

    const campo =
      type === 'subensamble'
        ? 'estado'
        : 'estatus';

    if (type === 'subensamble') {
      const enProceso = ordenes.find(o => Number(o[campo]) === 2);
      if (enProceso) return enProceso;

      const pendiente = ordenes.find(o => Number(o[campo]) === 1);
      if (pendiente) return pendiente;

      const finalizadas = ordenes.filter(o =>
        [3, 4].includes(Number(o[campo]))
      );

      if (finalizadas.length) {
        return finalizadas[finalizadas.length - 1];
      }

      return ordenes[0] || null;
    }

    const enProceso = ordenes.find(o =>
      Number(o.estatus) === 2 &&
      !esRetiroAgvActivo(o)
    );

    if (enProceso) return enProceso;

    const pendienteDisponible = ordenes.find(o =>
      puedeMostrarseEnEstacion(o, estIndex)
    );

    if (pendienteDisponible) return pendienteDisponible;

    return null;
  }



  function getStatusLabel(type, status) {
    const st = Number(status || 0);

    if (type === 'subensamble') {
      if (st === 1) return 'En espera';
      if (st === 2) return 'Trabajando';
      if (st === 3) return 'Finalizada';
      if (st === 4) return 'Entregada';
      return 'Sin estado';
    }

    if (st === 1) return 'En espera';
    if (st === 2) return 'Trabajando';
    if (st === 3) return 'Finalizada';

    return 'Sin estado';
  }

  function renderCardAlarmaUnidad(node) {

    const card = document.getElementById('cardAlarmaUnidad');

    if (!card) return;

    if (!node || node.type !== 'estacion') {
      card.classList.add('d-none');
      card.innerHTML = '';
      return;
    }

    const alarma = buscarAlarmaUnidadEnRuta(node.unit, node.estIndex);

    if (!alarma) {
      card.classList.add('d-none');
      card.innerHTML = '';
      return;
    }

    const estacionNombre =
      alarma.estacion?.nombre_estacion || 'Estación no identificada';

    const fecha =
      alarma.accion?.fecha_inicio || '-';

    const usuario =
      alarma.accion?.usuario_nombre || '-';

    const origen_accion_texto =
      alarma.accion?.origen_accion_texto || '-';

    card.classList.remove('d-none');

    card.innerHTML = `
    <div class="d-flex align-items-start gap-2">
      <div class="mrp-alarm-icon">
        <i class="ri-alarm-warning-line"></i>
      </div>

      <div class="flex-grow-1">
        <div class="mrp-alarm-title">
          Unidad alarmada
        </div>

        <div class="mrp-alarm-text">
          La unidad <strong>${node.unit}</strong> continúa con una alarma activa.
        </div>

        <div class="mrp-alarm-meta mt-2">
          <div><strong>Alarmada en:</strong> ${estacionNombre}</div>
          <div><strong>Fecha:</strong> ${fecha}</div>
          <div><strong>Registró:</strong> ${usuario}</div>
           <div><strong>POR:</strong> ${origen_accion_texto}</div>
        </div>
      </div>
    </div>
  `;
  }

  function pintarOrderBox(box, info, status, type, orden = null) {

    if (!box) return;

    box.classList.remove(
      'is-ready',
      'is-working',
      'is-done',
      'is-blocked',
      'is-waiting',
      'is-empty',
      'is-alarm'
    );

    const fase = Number(MRP_STATE?.fase || 0);
    const st = Number(status || 0);


    if (fase === 2) {
      box.classList.add('is-blocked');
      return;
    }


    if (fase === 5) {
      box.classList.add('is-done');
      return;
    }

    if (
      info?.clase === 'semaforo-done' ||
      info?.titulo === 'Estación completada' ||
      info?.titulo === 'Producción completada' ||
      st === 3 ||
      st === 4
    ) {
      box.classList.add('is-done');
      return;
    }

    if (st === 2 || info?.clase === 'semaforo-proceso') {
      box.classList.add('is-working');
      return;
    }

    if (info?.clase === 'semaforo-bloqueado') {
      box.classList.add('is-blocked');
      return;
    }

    if (info?.clase === 'semaforo-listo') {
      box.classList.add('is-ready');
      return;
    }

    if (info?.clase === 'semaforo-espera') {
      box.classList.add('is-empty');
      return;
    }

    box.classList.add('is-waiting');
  }


  function actualizarKpisYCards(nodeSeleccionado = currentNode) {
    const kpiCantidadProducir = document.getElementById('kpiCantidadProducir');
    const kpiFinalizadas = document.getElementById('kpiFinalizadas');

    if (kpiCantidadProducir) {
      kpiCantidadProducir.textContent = MRP_STATE?.cantidad || 0;
    }

    let finalizadas = 0;

    if (nodeSeleccionado) {

      if (nodeSeleccionado.type === 'estacion') {
        const est = getStation(nodeSeleccionado.estIndex);

        finalizadas = (est?.ordenes_trabajo || []).filter(o => {
          const retiroActivo =
            Number(o.accion_produccion || 0) === 2 &&
            Number(o.accion_activa || 0) === 2;

          return Number(o.estatus || 0) === 3 && !retiroActivo;
        }).length;
      }

      if (nodeSeleccionado.type === 'subensamble') {
        const sub = getSubassembly(
          nodeSeleccionado.estIndex,
          nodeSeleccionado.subIndex
        );

        finalizadas = (sub?.ordenes_trabajo || []).filter(o =>
          [3, 4].includes(Number(o.estado || 0))
        ).length;
      }
    }

    if (kpiFinalizadas) {
      kpiFinalizadas.textContent = finalizadas;
    }


    const cantidadTotal = Number(MRP_STATE?.cantidad || 0);

    if (
      nodeSeleccionado &&
      nodeSeleccionado.type === 'estacion' &&
      cantidadTotal > 0 &&
      finalizadas >= cantidadTotal
    ) {
      if (detalleSemaforo) {
        detalleSemaforo.classList.remove(
          'semaforo-listo',
          'semaforo-proceso',
          'semaforo-bloqueado',
          'semaforo-espera'
        );
        detalleSemaforo.classList.add('semaforo-listo');
      }

      if (detalleSemaforoTitulo) {
        detalleSemaforoTitulo.textContent = 'Producción completada';
      }

      if (detalleSemaforoTexto) {
        detalleSemaforoTexto.textContent =
          `La estación ${nodeSeleccionado.title} completó correctamente las ${cantidadTotal} unidades programadas.`;
      }
    }


    (MRP_STATE.estaciones || []).forEach((est, estIndex) => {
      const ordenEst = getCurrentOrder(
        est.ordenes_trabajo || [],
        'estacion',
        estIndex
      );

      const boxEst = document.querySelector(
        `.mrp-order-box[data-order-box-type="estacion"][data-est-index="${estIndex}"]`
      );

      if (boxEst && ordenEst) {
        const nodeEst = getNodeData('estacion', estIndex, null);
        const infoEst = validarVisualNodo(nodeEst);
        const estatus = Number(ordenEst.estatus || 0);


        pintarOrderBox(boxEst, infoEst, estatus, 'estacion', ordenEst);

        const statusWrap = boxEst.querySelector('.js-card-status');
        if (statusWrap) {
          statusWrap.innerHTML = badgeEstadoHtml('estacion', estatus, infoEst);
        }

        const orderIdWrap = boxEst.querySelector('.js-card-order-id');
        if (orderIdWrap) {

          orderIdWrap.textContent = ordenEst?.num_sub_orden || 'SIN-UNIDAD';
        }
      }

      (MRP_STATE.estaciones || []).forEach((est, estIndex) => {

        const ordenEst = getCurrentOrder(
          est.ordenes_trabajo || [],
          'estacion',
          estIndex
        );

        const boxEst = document.querySelector(
          `.mrp-order-box[data-order-box-type="estacion"][data-est-index="${estIndex}"]`
        );

        if (boxEst) {

          const nodeEst =
            getNodeData('estacion', estIndex);

          const infoEst =
            validarVisualNodo(nodeEst);

          const estatus =
            Number(ordenEst?.estatus || 0);

          pintarOrderBox(
            boxEst,
            infoEst,
            estatus,
            'estacion'
          );

          const statusWrap =
            boxEst.querySelector('.js-card-status');

          if (statusWrap) {

            statusWrap.innerHTML =
              badgeEstadoHtml('estacion', estatus, infoEst, ordenEst)
          }

          const orderIdWrap =
            boxEst.querySelector('.js-card-order-id');

          if (orderIdWrap) {

            orderIdWrap.textContent =
              ordenEst?.num_sub_orden || 'SIN-UNIDAD';
          }
        }

        // =====================================
        // SUBENSAMBLES
        // =====================================

        (est.subensambles || []).forEach((sub, subIndex) => {

          const boxSub = document.querySelector(
            `.mrp-order-box[data-order-box-type="subensamble"][data-est-index="${estIndex}"][data-sub-index="${subIndex}"]`
          );

          if (!boxSub) return;

          const nodeSub =
            getNodeData(
              'subensamble',
              estIndex,
              subIndex
            );

          const infoSub =
            validarVisualNodo(nodeSub);

          pintarOrderBox(
            boxSub,
            infoSub,
            nodeSub.unitStatus,
            'subensamble'
          );

          const totalSub =
            (sub?.ordenes_trabajo || []).length;

          const entregadas =
            (sub?.ordenes_trabajo || []).filter(o =>
              Number(o.estado || 0) === 4
            ).length;

          boxSub.innerHTML = `

      <div class="d-flex justify-content-between align-items-center mb-2">

        <div class="fw-semibold">
          ${sub.nombre_estacion || 'Subensamble'}
        </div>

        ${badgeEstadoHtml(
            'subensamble',
            nodeSub.unitStatus,
            infoSub
          )}

      </div>

      <div class="small">

        ${entregadas >= totalSub
              ? `
            <span class="text-success fw-semibold">
              Subensambles entregados correctamente
            </span>
          `
              : `
            Ya puedes trabajar el subensamble
            <strong>${entregadas + 1}</strong>
            de
            <strong>${totalSub}</strong>
          `
            }

      </div>
    `;
        });
      });


    });
  }

  function getPrioridadBadge(prioridad) {
    const p = String(prioridad || '').toUpperCase().trim();
    if (p === 'ALTA') return '<span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">Alta</span>';
    if (p === 'MEDIA') return '<span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">Media</span>';
    if (p === 'BAJA') return '<span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">Baja</span>';
    return '<span class="badge rounded-pill bg-secondary-subtle text-body border">N/D</span>';
  }

  function getNodeData(type, estIndex, subIndex = null) {
    const est = getStation(estIndex);
    if (!est) return null;

    if (type === 'subensamble') {
      const sub = getSubassembly(estIndex, subIndex);
      if (!sub) return null;

      const unidad = getCurrentOrder(
        sub.ordenes_trabajo,
        'subensamble',
        estIndex
      );
      return {
        type: 'subensamble',
        // idplaneacion:idplaneacion,
        idplaneacion: MRP_STATE?.idplaneacion || '-',
        estIndex,
        subIndex,
        idorden_subensamble: unidad?.idorden_subensamble || 0,
        idorden: 0,
        idordengeneral: unidad?.idorden_subensamble || 0,
        title: sub.nombre_estacion || 'Subensamble',
        subtitle: sub.proceso || '-',
        orderNumber: MRP_STATE?.num_orden || '-',
        unit: unidad?.num_sub_orden || 'SIN-UNIDAD',
        numbase: MRP_STATE?.num_orden || '',
        numorden: unidad?.num_sub_orden || '',
        unitStatus: Number(unidad?.estado || 1),
        supervisor: MRP_STATE?.supervisor || '-',
        model: MRP_STATE?.descripcion || '-',
        pedido: MRP_STATE?.num_pedido || '-',
        prioridad: MRP_STATE?.prioridad || '-',
        encargado: sub.encargados?.[0]?.nombre_completo || '-',
        ayudante: sub.ayudantes?.[0]?.nombre_completo || '-',
        encargadoCalidad: '-',
        encargadoCriticos: '-',
        tiempoAjuste: sub.tiempo_ajuste || '-',
        calidad: (est.calidad == 1 || unidad?.calidad == 1) ? 'Sí' : 'No',
        proceso: sub.proceso || '-',
        estacionid: sub.subensambleid || sub.idsubensamble || 0,
        productoid: MRP_STATE?.productoid || 0,
        cantidad: MRP_STATE?.cantidad || 0,
        unidadRaw: unidad,
        raw: sub,
        tieneAyudas: String(sub.tiene_ayudas_visuales || 'no').toLowerCase() === 'si',
        tieneOperaciones: String(sub.tiene_especificaciones || 'no').toLowerCase() === 'si',
        // tieneOperacionesCriticas: String(sub.tiene_especificaciones_criticas || 'no').toLowerCase() === 'si',
        // tieneCalidad:
        // Number(est.calidad || 0) === 1 ||
        //Number(unidad?.calidad || 0) === 1,
        tieneOperacionesCriticas: false,
        tieneCalidad: false,
        tieneEstampadoVin: false,
      };
    }

    const unidad = getCurrentOrder(
      est.ordenes_trabajo,
      'estacion',
      estIndex
    );

    return {
      type: 'estacion',
      idplaneacion: MRP_STATE?.idplaneacion || '-',
      inventarioid: MRP_STATE?.inventarioid || '-',
      estIndex,
      subIndex: null,
      idorden: unidad?.idorden || 0,
      idorden_subensamble: 0,
      idordengeneral: unidad?.idorden || 0,
      title: est.nombre_estacion || 'Estación',
      subtitle: est.proceso || '-',
      orderNumber: MRP_STATE?.num_orden || '-',
      unit: unidad?.num_sub_orden || 'SIN-UNIDAD',
      numbase: MRP_STATE?.num_orden || '',
      numorden: unidad?.num_sub_orden || '',
      // unitStatus: Number(unidad?.estatus || 1),
      unitStatus: unidad ? Number(unidad.estatus || 1) : 0,
      supervisor: MRP_STATE?.supervisor || '-',
      model: MRP_STATE?.descripcion || '-',
      pedido: MRP_STATE?.num_pedido || '-',
      prioridad: MRP_STATE?.prioridad || '-',
      encargado: est.encargados?.[0]?.nombre_completo || '-',
      ayudante: est.ayudantes?.[0]?.nombre_completo || '-',
      encargadoCalidad: est.encargado_pdi?.[0]?.nombre_completo || '-',
      encargadoCriticos: est.encargado_punto_critico?.[0]?.nombre_completo || '-',
      tiempoAjuste: est.tiempo_ajuste || '-',
      calidad: est.calidad == 1 ? 'Sí' : 'No',
      proceso: est.proceso || '-',
      estacionid: est.estacionid || est.idestacion || 0,
      productoid: MRP_STATE?.productoid || 0,
      cantidad: MRP_STATE?.cantidad || 0,
      unidadRaw: unidad,
      raw: est,
      tieneAyudas: String(est.tiene_ayudas_visuales || 'no').toLowerCase() === 'si',
      tieneOperaciones: String(est.tiene_especificaciones || 'no').toLowerCase() === 'si',
      tieneOperacionesCriticas: String(est.tiene_especificaciones_criticas || 'no').toLowerCase() === 'si',
      tieneCalidad:
        Number(est.calidad || 0) === 1 ||
        Number(unidad?.calidad || 0) === 1,
      tieneEstampadoVin: Number(est.estampado || 0) === 1 || Number(unidad?.estampado || 0) === 1,
    };
  }


  function esUnidadAlarmada(orden) {
    return (
      Number(orden?.accion_produccion || 0) === 3 &&
      Number(orden?.accion_activa || 0) === 2
    );
  }

  function getAccionAlarma(orden) {
    return (orden?.acciones_produccion || []).find(a =>
      Number(a.tipo_accion || 0) === 3 &&
      Number(a.estado || 0) === 2
    ) || null;
  }

  function buscarAlarmaUnidadEnRuta(unidad, estIndexActual = null) {

    if (!unidad || unidad === 'SIN-UNIDAD') return null;

    const estaciones = MRP_STATE?.estaciones || [];

    for (let i = 0; i < estaciones.length; i++) {

      if (estIndexActual !== null && i > estIndexActual) {
        continue;
      }

      const est = estaciones[i];

      const orden = (est?.ordenes_trabajo || []).find(o =>
        o.num_sub_orden === unidad &&
        esUnidadAlarmada(o)
      );

      if (!orden) continue;

      const accion = getAccionAlarma(orden);

      return {
        orden,
        accion,
        estacion: est,
        estIndex: i
      };
    }

    return null;
  }

  function renderInfoAlarmaUnidad(node) {

    if (!node || node.type !== 'estacion') return '';

    const alarma = buscarAlarmaUnidadEnRuta(
      node.unit,
      node.estIndex
    );

    if (!alarma) return '';

    const estacionNombre =
      alarma.estacion?.nombre_estacion || 'Estación no identificada';

    const fecha =
      alarma.accion?.fecha_inicio || '-';

    const usuario =
      alarma.accion?.usuario_nombre || '-';

    return `
    <div class="alert alert-warning border-0 shadow-sm py-2 px-2 mt-2 mb-0">
      <div class="d-flex align-items-start gap-2">
        <i class="ri-alarm-warning-line fs-16 mt-1"></i>

        <div>
          <div class="fw-semibold small mb-1">
            Unidad alarmada
          </div>

          <div class="small lh-sm">
            Esta unidad fue marcada como alarmada en
            <strong>${estacionNombre}</strong>.
          </div>

          <div class="small text-muted mt-1">
            Fecha: <strong>${fecha}</strong><br>
            Registró: <strong>${usuario}</strong>
          </div>
        </div>
      </div>
    </div>
  `;
  }

  function validarVisualNodo(node) {

    if (!node) {
      return {
        clase: 'semaforo-espera',
        titulo: 'Sin información',
        texto: 'No existe información disponible.',
        canStart: false,
        canFinish: false
      };
    }

    const fase = Number(MRP_STATE?.fase || 0);


    if (fase === 2) {
      return {
        clase: 'semaforo-bloqueado',
        titulo: 'Producción no iniciada',
        texto: 'La producción aún no ha sido iniciada por el supervisor. No se puede operar ninguna estación ni subensamble.',
        canStart: false,
        canFinish: false
      };
    }

    if (fase === 5) {
      return {
        clase: 'semaforo-done',
        titulo: 'Producción completada',
        texto: 'La producción fue finalizada correctamente por el supervisor. No existen procesos pendientes por operar.',
        canStart: false,
        canFinish: false
      };
    }

    if (fase !== 3) {
      return {
        clase: 'semaforo-bloqueado',
        titulo: 'Producción no disponible',
        texto: 'La producción no se encuentra en una fase válida para operar.',
        canStart: false,
        canFinish: false
      };
    }

    if (node.type === 'subensamble') {
      const sub = getSubassembly(node.estIndex, node.subIndex);

      const totalSub = (sub?.ordenes_trabajo || []).length;
      const entregadas = (sub?.ordenes_trabajo || []).filter(o =>
        Number(o.estado || 0) === 4
      ).length;

      const trabajando = (sub?.ordenes_trabajo || []).find(o =>
        Number(o.estado || 0) === 2
      );

      if (trabajando) {
        return {
          clase: 'semaforo-proceso',
          titulo: 'Subensamble en proceso',
          texto: `Subensamble trabajando en ${entregadas + 1} de ${totalSub}.`,
          canStart: false,
          canFinish: true
        };
      }

      if (totalSub > 0 && entregadas >= totalSub) {
        return {
          clase: 'semaforo-done',
          titulo: 'Subensambles completados',
          texto: 'Todos los subensambles fueron entregados correctamente.',
          canStart: false,
          canFinish: false
        };
      }

      return {
        clase: 'semaforo-listo',
        titulo: 'Listo para trabajar',
        texto: `Ya puedes trabajar el subensamble ${entregadas + 1} de ${totalSub}.`,
        canStart: true,
        canFinish: false
      };
    }

    const est = getStation(node.estIndex);
    const cantidadTotal = Number(MRP_STATE?.cantidad || 0);

    const finalizadasEstacion = (est?.ordenes_trabajo || []).filter(o => {
      const retiroActivo =
        Number(o.accion_produccion || 0) === 2 &&
        Number(o.accion_activa || 0) === 2;

      return Number(o.estatus || 0) === 3 && !retiroActivo;
    }).length;

    if (cantidadTotal > 0 && finalizadasEstacion >= cantidadTotal) {
      return {
        clase: 'semaforo-done',
        titulo: 'Estación completada',
        texto: 'Todas las unidades programadas fueron completadas correctamente en esta estación.',
        canStart: false,
        canFinish: false
      };
    }

    if (!node.idorden || node.unit === 'SIN-UNIDAD') {
      return {
        clase: 'semaforo-espera',
        titulo: 'Esperando unidad',
        texto: 'Esta estación aún no tiene una unidad disponible para iniciar operación.',
        canStart: false,
        canFinish: false
      };
    }

    if (node.unitStatus === 2) {
      return {
        clase: 'semaforo-proceso',
        titulo: 'Unidad en proceso',
        texto: `La unidad ${node.unit} está siendo trabajada actualmente.`,
        canStart: false,
        canFinish: true
      };
    }

    if (node.estIndex > 0) {
      const prevEst = getStation(node.estIndex - 1);

      const prevOrder = (prevEst?.ordenes_trabajo || []).find(o =>
        o.num_sub_orden === node.unit
      );

      if (!prevOrder || Number(prevOrder.estatus) !== 3) {
        return {
          clase: 'semaforo-bloqueado',
          titulo: 'Esperando estación anterior',
          texto: 'La unidad aún no ha sido liberada por la estación anterior.',
          canStart: false,
          canFinish: false
        };
      }
    }

    const subensambles = est?.subensambles || [];

    if (subensambles.length) {
      const entregado = subensambles.some(sub => {
        const subOrder = (sub.ordenes_trabajo || []).find(o =>
          o.num_sub_orden === node.unit
        );

        return subOrder && Number(subOrder.estado) === 4;
      });

      if (!entregado) {
        return {
          clase: 'semaforo-bloqueado',
          titulo: 'Esperando subensamble',
          texto: 'El subensamble aún no entrega esta unidad.',
          canStart: false,
          canFinish: false
        };
      }
    }

    return {
      clase: 'semaforo-listo',
      titulo: 'Listo para trabajar',
      texto: `La unidad ${node.unit} ya puede iniciar proceso en ${node.title}.`,
      canStart: true,
      canFinish: false
    };
  }


  function aplicarSemaforo(info) {
    if (!detalleSemaforo) return;

    detalleSemaforo.classList.remove('semaforo-listo', 'semaforo-proceso', 'semaforo-bloqueado', 'semaforo-espera');
    detalleSemaforo.classList.add(info.clase || 'semaforo-espera');

    if (detalleSemaforoTitulo) detalleSemaforoTitulo.textContent = info.titulo || '-';
    if (detalleSemaforoTexto) detalleSemaforoTexto.textContent = info.texto || '-';
  }

  function updateButtons(info) {
    if (btnIniciarUnidad) {
      btnIniciarUnidad.disabled = !info.canStart;

      btnIniciarUnidad.classList.remove('btn-state-disabled', 'btn-state-working', 'btn-state-ready');

      if (info.clase === 'semaforo-proceso') {
        btnIniciarUnidad.textContent = 'En proceso';
        btnIniciarUnidad.classList.add('btn-state-working');
      } else if (info.canStart) {
        btnIniciarUnidad.textContent = 'Iniciar unidad';
        btnIniciarUnidad.classList.add('btn-state-ready');
      } else {
        btnIniciarUnidad.textContent = 'No disponible';
        btnIniciarUnidad.classList.add('btn-state-disabled');
      }
    }

    if (btnFinalizarUnidad) {
      btnFinalizarUnidad.disabled = !info.canFinish;
      btnFinalizarUnidad.classList.toggle('btn-state-disabled', !info.canFinish);
    }


  }


  function limpiarEstadoBotonProceso(btn) {
    if (!btn) return;

    btn.classList.remove(
      'btn-proceso-pendiente',
      'btn-proceso-completado'
    );
  }

  function pintarEstadoBotonProceso(btn, completado) {
    if (!btn) return;

    limpiarEstadoBotonProceso(btn);

    btn.classList.add(
      completado
        ? 'btn-proceso-completado'
        : 'btn-proceso-pendiente'
    );
  }

  function actualizarBotonesDinamicos(node) {

    if (!node) return;

    const esEstacion = node.type === 'estacion';

    const unidadEnProceso =
      esEstacion &&
      Number(node.unidadRaw?.estatus || 0) === 2;

    const mostrarBotonesProceso =
      esEstacion &&
      unidadEnProceso &&
      node.idorden > 0;

    const calidadValor = Number(node.unidadRaw?.calidad || 0);
    const operacionesValor = Number(node.unidadRaw?.operaciones || 0);
    const criticasValor = Number(node.unidadRaw?.especificaciones_criticas || 0);
    const estampadoValor = Number(node.unidadRaw?.estampado || 0);

    // =========================
    // OPERACIONES
    // 0/1 = pendiente
    // 2 = completado
    // =========================
    if (btnDetalleOperaciones) {

      const mostrar =
        mostrarBotonesProceso &&
        node.tieneOperaciones;

      btnDetalleOperaciones.style.display =
        mostrar ? '' : 'none';

      if (mostrar) {

        const completado =
          Number(operacionesValor) === 2;

        pintarEstadoBotonProceso(
          btnDetalleOperaciones,
          completado
        );

      } else {

        limpiarEstadoBotonProceso(
          btnDetalleOperaciones
        );
      }
    }

    // =========================
    // OPERACIONES CRÍTICAS
    // 0/1 = pendiente
    // 2 = completado
    // =========================
    if (btnOperacionesCriticas) {

      const mostrar =
        mostrarBotonesProceso &&
        node.tieneOperacionesCriticas;

      btnOperacionesCriticas.style.display =
        mostrar ? '' : 'none';

      if (mostrar) {

        const completado =
          Number(criticasValor) === 2;

        pintarEstadoBotonProceso(
          btnOperacionesCriticas,
          completado
        );

      } else {

        limpiarEstadoBotonProceso(
          btnOperacionesCriticas
        );
      }
    }

    // =========================
    // CALIDAD
    // 0/1 = pendiente
    // 2 = completado
    // =========================
    if (btnCalidad) {

      const mostrar =
        mostrarBotonesProceso &&
        node.tieneCalidad;

      btnCalidad.style.display =
        mostrar ? '' : 'none';

      if (mostrar) {

        const completado =
          Number(calidadValor) === 2;

        pintarEstadoBotonProceso(
          btnCalidad,
          completado
        );

      } else {

        limpiarEstadoBotonProceso(
          btnCalidad
        );
      }
    }

    // =========================
    // PUNTOS DE CALIDAD
    // 0/1 = pendiente
    // 2 = completado
    // =========================
    if (btnDetallePuntosCalidad) {

      const mostrar =
        mostrarBotonesProceso &&
        node.tieneCalidad;

      btnDetallePuntosCalidad.style.display =
        mostrar ? '' : 'none';

      if (mostrar) {

        const completado =
          Number(calidadValor) === 2;

        pintarEstadoBotonProceso(
          btnDetallePuntosCalidad,
          completado
        );

      } else {

        limpiarEstadoBotonProceso(
          btnDetallePuntosCalidad
        );
      }
    }

    // =========================
    // ESTAMPADO VIN
    // 1 = pendiente
    // 2 = completado
    // =========================
    if (btnEstamparVin) {

      const mostrar =
        mostrarBotonesProceso &&
        node.tieneEstampadoVin;

      btnEstamparVin.style.display =
        mostrar ? '' : 'none';

      if (mostrar) {

        const completado =
          Number(estampadoValor) === 2;

        pintarEstadoBotonProceso(
          btnEstamparVin,
          completado
        );

      } else {

        limpiarEstadoBotonProceso(
          btnEstamparVin
        );
      }
    }

    // =========================
    // AYUDAS VISUALES
    // =========================
    if (btnDetalleAyudas) {
      btnDetalleAyudas.style.display =
        node.tieneAyudas ? '' : 'none';
    }

    // =========================
    // BOTÓN PARO SOLO ESTACIONES EN PROCESO
    // =========================
    if (btnPausarUnidad) {

      const estaTrabajando =
        esEstacion &&
        Number(node.unidadRaw?.estatus || 0) === 2 &&
        String(node.unidadRaw?.estatus_texto || '')
          .toLowerCase()
          .trim() === 'en proceso';

      const existeParoActivo =
        Number(node.unidadRaw?.accion_produccion || 0) === 1 &&
        Number(node.unidadRaw?.accion_activa || 0) === 2;

      btnPausarUnidad.style.display = esEstacion ? '' : 'none';

      const habilitarBoton =
        esEstacion &&
        estaTrabajando &&
        !existeParoActivo;

      btnPausarUnidad.disabled = !habilitarBoton;

      btnPausarUnidad.classList.toggle(
        'btn-state-disabled',
        !habilitarBoton
      );

      btnPausarUnidad.textContent = existeParoActivo
        ? 'Paro activo'
        : 'Pausar / Paro';
    }
  }



  function renderMensajesProceso(node) {

    if (!contenedorMensajesProceso) return;

    const mensajes = [];

    // =====================================
    // OPERACIONES
    // =====================================
    if (node.tieneOperaciones) {

      mensajes.push(`
      <div class="alert alert-info border-0 shadow-sm py-2 px-2 mb-2">
        <div class="d-flex align-items-start gap-2">
          <i class="ri-user-settings-line fs-14 mt-1"></i>

          <div>
            <div class="fw-semibold small mb-1">
              Asignación de operaciones
            </div>

            <div class="small text-muted lh-sm" style="font-size:11px;">
              Registrar quién realizó cada operación antes de liberar la unidad.
            </div>
          </div>
        </div>
      </div>
    `);
    }

    // =====================================
    // OPERACIONES CRÍTICAS
    // =====================================
    if (node.tieneOperacionesCriticas) {

      mensajes.push(`
      <div class="alert alert-danger border-0 shadow-sm py-2 px-2 mb-2">
        <div class="d-flex align-items-start gap-2">
          <i class="ri-shield-check-line fs-14 mt-1"></i>

          <div>
            <div class="fw-semibold small mb-1">
              Especificaciones críticas
            </div>

            <div class="small text-muted lh-sm" style="font-size:11px;">
              Validar y capturar especificaciones críticas antes de finalizar.
            </div>
          </div>
        </div>
      </div>
    `);
    }

    // =====================================
    // CALIDAD
    // =====================================
    if (node.tieneCalidad) {

      mensajes.push(`
      <div class="alert alert-warning border-0 shadow-sm py-2 px-2 mb-2">
        <div class="d-flex align-items-start gap-2">
          <i class="ri-search-eye-line fs-14 mt-1"></i>

          <div>
            <div class="fw-semibold small mb-1">
              Inspección de calidad
            </div>

            <div class="small text-muted lh-sm" style="font-size:11px;">
              Requiere inspección antes de liberar al siguiente proceso.
            </div>
          </div>
        </div>
      </div>
    `);
    }


    if (node.tieneEstampadoVin) {
      mensajes.push(`
    <div class="alert alert-secondary border-0 shadow-sm py-2 px-2 mb-2">
      <div class="d-flex align-items-start gap-2">
        <i class="ri-barcode-line fs-14 mt-1"></i>

        <div>
          <div class="fw-semibold small mb-1">
            Estampado VIN requerido
          </div>

          <div class="small text-muted lh-sm" style="font-size:11px;">
            En esta estación se debe colocar y registrar el VIN correspondiente a la unidad antes de continuar.
          </div>
        </div>
      </div>
    </div>
  `);
    }



    // =====================================
    // VACÍO
    // =====================================
    if (!mensajes.length) {

      mensajes.push(`
      <div class="alert alert-light border shadow-sm py-2 px-2 mb-0">
        <div class="small text-muted" style="font-size:11px;">
          Sin validaciones adicionales configuradas.
        </div>
      </div>
    `);
    }

    contenedorMensajesProceso.innerHTML = mensajes.join('');
  }

  function renderUnidadesFueraLinea(node = currentNode) {

    const cardUnidadesFueraLinea = document.getElementById('cardUnidadesFueraLinea');

    if (!contenedorUnidadesFueraLinea || !cardUnidadesFueraLinea) return;

    if (!node || node.type !== 'estacion') {
      cardUnidadesFueraLinea.classList.add('d-none');
      contenedorUnidadesFueraLinea.innerHTML = '';
      return;
    }

    cardUnidadesFueraLinea.classList.remove('d-none');

    const est = getStation(node.estIndex);
    const html = [];

    (est?.ordenes_trabajo || []).forEach(orden => {

      (orden.unidades_fuera_linea || []).forEach(item => {

        if (Number(item.estado) !== 1) return;

        html.push(`
        <div class="mrp-extra-item mb-2 border rounded-3 p-2">
          <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">

            <div class="flex-grow-1">
              <div class="fw-bold text-danger mb-1">
                <i class="ri-logout-box-r-line me-1"></i>
                ${item.unidad || '-'}
              </div>

              <div class="small text-muted mb-1">
                Retirada por <strong>${item.usuario_salida_nombre || '-'}</strong>
                el <strong>${item.fecha_salida || '-'}</strong>.
              </div>

              <div class="small text-muted mb-1">
                Estación: <strong>${est.nombre_estacion || '-'}</strong>
              </div>

              <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                ${item.estado_texto || 'Fuera de línea'}
              </span>
            </div>

            <button
              type="button"
              class="btn btn-sm btn-success"
              onclick="reincorporarUnidadFueraLinea(${item.idfuera}, '${item.unidad || '-'}', '${est.nombre_estacion || '-'}')">
              <i class="ri-refresh-line me-1"></i>
              Reincorporar
            </button>

          </div>
        </div>
      `);
      });
    });

    contenedorUnidadesFueraLinea.innerHTML = html.length
      ? html.join('')
      : `
      <div class="text-muted small py-2">
        <i class="ri-checkbox-circle-line text-success me-1"></i>
        No existen unidades fuera de línea en esta estación.
      </div>
    `;
  }


  function getElapsedSeconds(fechaInicio) {
    if (!fechaInicio || fechaInicio === '0000-00-00 00:00:00') return 0;

    const normalized = String(fechaInicio).replace(' ', 'T');
    const start = new Date(normalized);

    if (isNaN(start.getTime())) return 0;

    const now = new Date();
    return Math.max(0, Math.floor((now.getTime() - start.getTime()) / 1000));
  }

  function getNodeStartDate(node) {
    if (!node || !node.unidadRaw) return null;

    if (node.type === 'subensamble') {
      return node.unidadRaw.fecha_inicio_real || null;
    }

    return node.unidadRaw.fecha_inicio || null;
  }

  function getNodeKey(node) {

  if (!node) return '';

  if (node.type === 'subensamble') {

    return 'SUB-' + (
      node.idorden_subensamble ||
      node.idordengeneral ||
      0
    );
  }

  return 'EST-' + (
    node.idorden ||
    node.idordengeneral ||
    0
  );
}

function sincronizarTimerDesdeBD(node) {

  if (!node) {

    pausarTimer();

    timerStartMs = 0;
    timerNodeKey = '';

    seconds = 0;

    renderTime();

    return;
  }

  const estado = Number(node.unitStatus || 0);

  if (estado !== 2) {

    pausarTimer();

    timerStartMs = 0;
    timerNodeKey = '';

    seconds = 0;

    renderTime();

    return;
  }

  const fechaInicio = getNodeStartDate(node);

  if (
    !fechaInicio ||
    fechaInicio === '0000-00-00 00:00:00'
  ) {

    pausarTimer();

    timerStartMs = 0;

    seconds = 0;

    renderTime();

    return;
  }

  const key = getNodeKey(node);

  /*
      IMPORTANTE
      Si seguimos viendo exactamente
      la misma estación/subensamble,
      NO volver a reiniciar nada
  */

  if (timerNodeKey === key && timer) {

    seconds = Math.floor(
      (Date.now() - timerStartMs) / 1000
    );

    renderTime();

    return;
  }

  timerNodeKey = key;

  timerStartMs = new Date(
    fechaInicio.replace(' ', 'T')
  ).getTime();

  seconds = Math.floor(
    (Date.now() - timerStartMs) / 1000
  );

  renderTime();

  iniciarTimer();
}


  function badgeEstadoHtml(type, status, info = null, orden = null) {

    const fase = Number(MRP_STATE?.fase || 0);
    const st = Number(status || 0);

    if (fase === 2) {
      return `
      <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">
        No iniciada
      </span>
    `;
    }

    if (fase === 5) {
      return `
      <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
        Producción completada
      </span>
    `;
    }

    if (orden && esUnidadAlarmada(orden)) {
      return `
      <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">
        Alarmada
      </span>
    `;
    }

    if (info?.clase === 'semaforo-done') {
      return `
      <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
        Completada
      </span>
    `;
    }

    if (info?.clase === 'semaforo-espera') {
      return `
      <span class="badge rounded-pill bg-secondary-subtle text-body border">
        Sin unidad
      </span>
    `;
    }

    if (info?.clase === 'semaforo-bloqueado') {
      return `
      <span class="badge rounded-pill bg-danger-subtle text-danger border border-danger-subtle">
        Bloqueado
      </span>
    `;
    }

    if (st === 2) {
      return `
      <span class="badge rounded-pill bg-info-subtle text-info border border-info-subtle">
        Trabajando
      </span>
    `;
    }

    if (st === 3 || st === 4) {
      return `
      <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
        Finalizada
      </span>
    `;
    }

    if (info?.clase === 'semaforo-listo') {
      return `
      <span class="badge rounded-pill bg-success-subtle text-success border border-success-subtle">
        Listo
      </span>
    `;
    }

    return `
    <span class="badge rounded-pill bg-warning-subtle text-warning border border-warning-subtle">
      Pendiente
    </span>
  `;
  }



  function validarBotonIniciarProduccion() {

    if (!btnIniciarProduccion) return;

    const supervisorId = Number(MRP_STATE?.supervisorid || 0);

    const currentUserId = Number(window.CURRENT_USER_ID || 0);

    const currentRolId = Number(window.CURRENT_ROL_ID || 0);

    const esSupervisor =
      currentUserId === supervisorId;

    const esAdmin =
      currentRolId === 1;

    const puedeIniciar =
      esSupervisor || esAdmin;

    btnIniciarProduccion.style.display =
      puedeIniciar ? '' : 'none';
  }

  function validarBotonFinalizarProduccion() {

    if (!btnFinalizarProduccion) return;

    const supervisorId = Number(MRP_STATE?.supervisorid || 0);

    const currentUserId = Number(window.CURRENT_USER_ID || 0);

    const currentRolId = Number(window.CURRENT_ROL_ID || 0);

    const esSupervisor =
      currentUserId === supervisorId;

    const esAdmin =
      currentRolId === 1;

    const puedeFinalizar =
      esSupervisor || esAdmin;

    btnFinalizarProduccion.style.display =
      puedeFinalizar ? '' : 'none';
  }

 function validarBotonesProduccion() {

  if (!btnIniciarProduccion || !btnFinalizarProduccion) {
    return;
  }

  const supervisorId = Number(MRP_STATE?.supervisorid || 0);
  const currentUserId = Number(window.CURRENT_USER_ID || 0);
  const currentRolId = Number(window.CURRENT_ROL_ID || 0);

  const esSupervisor = currentUserId === supervisorId;
  const esAdmin = currentRolId === 1;

  const tienePermiso = esSupervisor || esAdmin;

  if (!tienePermiso) {
    btnIniciarProduccion.style.display = 'none';
    btnFinalizarProduccion.style.display = 'none';
    return;
  }

  const fase = Number(MRP_STATE?.fase || 0);

  // FASE 2 = pendiente / lista para iniciar producción
  if (fase === 2) {
    btnIniciarProduccion.style.display = '';
    btnFinalizarProduccion.style.display = 'none';
    return;
  }

  // FASE 3 = producción en proceso
  if (fase === 3) {
    btnIniciarProduccion.style.display = 'none';
    btnFinalizarProduccion.style.display = '';
    return;
  }

  // FASE 5 = producción finalizada
  if (fase === 5) {
    btnIniciarProduccion.style.display = 'none';
    btnFinalizarProduccion.style.display = 'none';
    return;
  }

  btnIniciarProduccion.style.display = 'none';
  btnFinalizarProduccion.style.display = 'none';
}

  function actualizarTextoUnidadActual(node, info) {

    const card = document.getElementById('detalleUnidadActualTextoCard');
    const titulo = document.getElementById('detalleUnidadActualTextoTitulo');
    const texto = document.getElementById('detalleUnidadActualTexto');

    if (!card || !titulo || !texto) return;

    card.classList.remove(
      'context-ready',
      'context-working',
      'context-blocked',
      'context-warning',
      'context-done'
    );

    const fase = Number(MRP_STATE?.fase || 0);

    if (fase === 2) {
      card.classList.add('context-blocked');
      titulo.textContent = 'Producción no iniciada';
      texto.textContent =
        'La producción aún no ha sido iniciada por el supervisor. Todas las estaciones y subensambles permanecen bloqueados.';
      return;
    }

    if (fase === 5) {
      card.classList.add('context-done');
      titulo.textContent = 'Producción completada';
      texto.textContent =
        'La producción fue finalizada correctamente por el supervisor. Todos los procesos quedan bloqueados.';
      return;
    }

    if (!node) {
      titulo.textContent = 'Estado actual';
      texto.textContent = 'Selecciona una estación o subensamble para validar el estado actual.';
      return;
    }

    if (node.type === 'subensamble') {
      const sub = getSubassembly(node.estIndex, node.subIndex);
      const totalSub = (sub?.ordenes_trabajo || []).length;
      const entregadas = (sub?.ordenes_trabajo || []).filter(o =>
        Number(o.estado || 0) === 4
      ).length;
      const trabajando = (sub?.ordenes_trabajo || []).find(o =>
        Number(o.estado || 0) === 2
      );

      if (trabajando) {
        card.classList.add('context-working');
        titulo.textContent = 'Subensamble trabajando';
        texto.textContent = `Subensamble trabajando en ${entregadas + 1} de ${totalSub}.`;
        return;
      }

      if (totalSub > 0 && entregadas >= totalSub) {
        card.classList.add('context-done');
        titulo.textContent = 'Subensambles completados';
        texto.textContent = 'Todos los subensambles fueron entregados correctamente para esta estación.';
        return;
      }

      card.classList.add('context-ready');
      titulo.textContent = 'Subensamble listo';
      texto.textContent = `Ya puedes trabajar el subensamble ${entregadas + 1} de ${totalSub}.`;
      return;
    }

    if (
      info?.clase === 'semaforo-done' ||
      info?.titulo === 'Estación completada' ||
      info?.titulo === 'Producción completada'
    ) {
      card.classList.add('context-done');
      titulo.textContent = 'Estación completada';
      texto.textContent = 'Todas las unidades programadas fueron completadas correctamente en esta estación.';
      return;
    }

    if (!node.idorden || node.unit === 'SIN-UNIDAD') {
      card.classList.add('context-warning');
      titulo.textContent = 'Esperando unidad';
      texto.textContent = 'Esta estación aún no tiene una unidad disponible para iniciar operación.';
      return;
    }

    if (info?.clase === 'semaforo-proceso') {
      card.classList.add('context-working');
      titulo.textContent = 'Unidad en proceso';
      texto.textContent = `La unidad ${node.unit} se encuentra actualmente en proceso de producción.`;
      return;
    }

    if (info?.clase === 'semaforo-bloqueado') {
      card.classList.add('context-blocked');
      titulo.textContent = 'Producción bloqueada';
      texto.textContent = info?.texto || 'La unidad aún no puede iniciar proceso.';
      return;
    }

    if (info?.clase === 'semaforo-listo') {
      card.classList.add('context-ready');
      titulo.textContent = 'Unidad lista';
      texto.textContent = `La unidad ${node.unit} ya puede comenzar a trabajar en esta estación.`;
      return;
    }

    titulo.textContent = 'Estado actual';
    texto.textContent = 'Esperando validación de producción.';
  }




  function fillDetailPanel(node) {
    if (!node) return;

    currentNode = node;
    currentSelection = {
      type: node.type,
      estIndex: node.estIndex,
      subIndex: node.subIndex
    };

    if (detalleTituloNodo) detalleTituloNodo.textContent = node.title || '-';
    if (detalleTipoNodo) detalleTipoNodo.textContent = node.type === 'subensamble' ? 'Subensamble' : 'Estación';
    if (detalleTipoCorto) detalleTipoCorto.textContent = node.type === 'subensamble' ? 'Subensamble' : 'Estación';
    // if (detalleUnidadActual) detalleUnidadActual.textContent = node.unit || 'SIN-UNIDAD';

    if (detalleUnidadActual) {

      if (node.type === 'subensamble') {

        const sub = getSubassembly(
          node.estIndex,
          node.subIndex
        );

        const totalSub =
          (sub?.ordenes_trabajo || []).length;

        const entregadas =
          (sub?.ordenes_trabajo || []).filter(o =>
            Number(o.estado || 0) === 4
          ).length;

        console.log(entregadas);

        if (entregadas >= totalSub && totalSub > 0) {

          detalleUnidadActual.textContent =
            'SUBENSAMBLES ENTREGADOS';

        } else {

          detalleUnidadActual.textContent =
            `SUBENSAMBLE ${entregadas + 1}`;
        }

      } else {

        detalleUnidadActual.textContent =
          node.unit || 'SIN-UNIDAD';
      }
    }




    if (detalleUnidadActualCard) detalleUnidadActualCard.textContent = node.unit || 'SIN-UNIDAD';
    if (detalleEstadoUnidad) detalleEstadoUnidad.textContent = getStatusLabel(node.type, node.unitStatus);
    if (detalleBadgePrioridad) detalleBadgePrioridad.innerHTML = getPrioridadBadge(node.prioridad);
    if (detalleOrdenTrabajo) detalleOrdenTrabajo.textContent = node.orderNumber || '-';
    if (detalleSupervisor) detalleSupervisor.textContent = node.supervisor || '-';
    if (detalleEncargado) detalleEncargado.textContent = node.encargado || '-';
    if (detalleAyudante) detalleAyudante.textContent = node.ayudante || '-';
    if (detalleTiempoAjuste) detalleTiempoAjuste.textContent = `${node.tiempoAjuste || '-'} min`;


    // =====================================
    // INSPECTOR CALIDAD
    // =====================================
    if (detalleInspectorCalidad) {
      detalleInspectorCalidad.textContent =
        node.encargadoCalidad || '-';
    }

    if (cardInspectorCalidad) {

      const mostrar =
        node.tieneCalidad &&
        node.encargadoCalidad &&
        node.encargadoCalidad !== '-';

      cardInspectorCalidad.style.display =
        mostrar ? '' : 'none';
    }

    // =====================================
    // INSPECTOR CRÍTICOS
    // =====================================
    if (detalleInspectorCriticos) {
      detalleInspectorCriticos.textContent =
        node.encargadoCriticos || '-';
    }

    if (cardInspectorCriticos) {

      const mostrar =
        node.tieneOperacionesCriticas &&
        node.encargadoCriticos &&
        node.encargadoCriticos !== '-';

      cardInspectorCriticos.style.display =
        mostrar ? '' : 'none';
    }




    if (detalleCalidad) detalleCalidad.textContent = node.calidad || 'No';

    if (detalleResumenUnidad) {
      detalleResumenUnidad.innerHTML = `
        <div><strong>Modelo:</strong> ${node.model || '-'}</div>
        <div><strong>Supervisor:</strong> ${node.supervisor || '-'}</div>
        <div><strong>Pedido:</strong> ${node.pedido || '-'}</div>
      `;
    }

    if (detalleDescripcionProceso) {
      detalleDescripcionProceso.innerHTML = `
    Ejecutar <strong>${node.proceso || '-'}</strong>,
    validar herramientas, componentes y completar operaciones del ${node.type === 'subensamble' ? 'subensamble' : 'proceso'}.
  `;
    }

    [
      btnDetalleHerramientas,
      btnDetalleComponentes,
      btnDetalleOperaciones,
      btnDetalleAyudas,
      btnDetallePuntosCalidad
    ].forEach(btn => {
      if (!btn) return;
      btn.dataset.productoid = node.productoid || 0;
      btn.dataset.estacionid = node.estacionid || 0;
      btn.dataset.estacion = node.title || '';
      btn.dataset.proceso = node.proceso || '';
      btn.dataset.tipo = node.type || 'estacion';
      btn.dataset.cantidad = node.cantidad || 0;
    });

    // if (btnDetallePuntosCalidad) {
    //   const tieneCalidad = String(node.calidad || '').toLowerCase() === 'sí' || String(node.calidad || '').toLowerCase() === 'si';
    //   btnDetallePuntosCalidad.style.display = tieneCalidad ? '' : 'none';
    // }

    const info = validarVisualNodo(node);

    aplicarSemaforo(info);

    updateButtons(info);

    actualizarTextoUnidadActual(node, info);

    renderCardAlarmaUnidad(node);

    actualizarBotonesDinamicos(node);

    validarBotonesProduccion();

    validarPermisosProduccion();

    renderMensajesProceso(node);

    sincronizarTimerDesdeBD(node);

    const tieneParoActivo = validarParoMomentaneo(node);

    if (tieneParoActivo) {
      if (btnFinalizarUnidad) {
        btnFinalizarUnidad.disabled = true;
        btnFinalizarUnidad.classList.add('btn-state-disabled');
      }

      if (btnPausarUnidad) {
        btnPausarUnidad.disabled = true;
        btnPausarUnidad.classList.add('btn-state-disabled');
        btnPausarUnidad.textContent = 'Paro activo';
      }
    }

    actualizarKpisYCards(node);
    renderUnidadesFueraLinea(node);
    renderHistorialEventosEstacion(node);
  }

  function highlightSelected(el) {
    document.querySelectorAll('.js-selectable-node').forEach(node => {
      node.classList.remove('is-selected');
    });

    if (el) el.classList.add('is-selected');
  }

  function getSelectedElement() {
    if (!currentSelection) return null;

    if (currentSelection.type === 'subensamble') {
      return document.querySelector(`.js-selectable-node[data-node-type="subensamble"][data-est-index="${currentSelection.estIndex}"][data-sub-index="${currentSelection.subIndex}"]`);
    }

    return document.querySelector(`.js-selectable-node[data-node-type="estacion"][data-est-index="${currentSelection.estIndex}"]`);
  }

  async function postJson(url, payload) {
    const resp = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload || {})
    });

    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

    return await resp.json();
  }

  function swalError(msg) {
    Swal.fire({
      icon: 'warning',
      title: 'Validación',
      text: msg || 'No se pudo realizar la acción.',
      confirmButtonText: 'Entendido',
      background: '#0f172a',
      color: '#fff'
    });
  }

  function swalSuccess(msg) {
    Swal.fire({
      icon: 'success',
      title: 'Correcto',
      text: msg || 'Operación realizada correctamente.',
      timer: 1400,
      showConfirmButton: false,
      background: '#0f172a',
      color: '#fff'
    });
  }

  async function refrescarEstadoProduccion(keepSelection = true) {
    if (!MRP_STATE?.num_orden) return;

    try {
      const data = await postJson(`${base_url}/plan_planeacionv1/getEstadoProduccion`, {
        num_orden: MRP_STATE.num_orden
      });

      if (!data.status || !data.data) return;

      MRP_STATE = data.data;
      validarBotonesProduccion();
      aplicarFiltroVisualUsuario();
      ordenarSubensamblesArriba();

      if (!currentSelection) {
        seleccionarPrimerNodoVisibleUsuario();
      }
      actualizarKpisYCards();
      renderUnidadesFueraLinea();

      if (keepSelection && currentSelection) {
        const node = getNodeData(
          currentSelection.type,
          currentSelection.estIndex,
          currentSelection.subIndex
        );

        fillDetailPanel(node);
        highlightSelected(getSelectedElement());
        renderUnidadesFueraLinea(node);
      }

    } catch (err) {
      console.warn('No se pudo refrescar el estado:', err.message);
    }



  }

  async function iniciarProcesoActual() {
    if (!currentNode) return;

    const info = validarVisualNodo(currentNode);
    if (!info.canStart) {
      swalError(info.texto);
      return;
    }

    let url = '';
    let payload = {};

    if (currentNode.type === 'subensamble') {
      url = `${base_url}/plan_planeacionv1/iniciarSubensamble`;
      payload = { idorden_subensamble: currentNode.idorden_subensamble };
    } else {
      url = `${base_url}/plan_planeacionv1/iniciarEstacion`;
      payload = { idorden: currentNode.idorden };
    }

    try {
      btnIniciarUnidad.disabled = true;

      const data = await postJson(url, payload);

      if (!data.status) {
        swalError(data.msg);
        await refrescarEstadoProduccion(true);
        return;
      }

      // seconds = 0;
      // iniciarTimer();
      swalSuccess(data.msg);
      await refrescarEstadoProduccion(true);

    } catch (err) {
      swalError(err.message);
      await refrescarEstadoProduccion(true);
    }
  }

  async function finalizarProcesoActual() {
    if (!currentNode) return;

    const info = validarVisualNodo(currentNode);
    if (!info.canFinish) {
      swalError('No hay una unidad en proceso para finalizar.');
      return;
    }

    let url = '';
    let payload = {};

    if (currentNode.type === 'subensamble') {
      url = `${base_url}/plan_planeacionv1/finalizarSubensamble`;
      payload = { idorden_subensamble: currentNode.idorden_subensamble };
    } else {
      url = `${base_url}/plan_planeacionv1/finalizarEstacion`;
      payload = { idorden: currentNode.idorden, inventarioid: currentNode.inventarioid };
    }

    try {
      btnFinalizarUnidad.disabled = true;

      const data = await postJson(url, payload);

      if (!data.status) {
        swalError(data.msg);
        await refrescarEstadoProduccion(true);
        return;
      }

      // pausarTimer();
      // seconds = 0;
      // renderTime();
      swalSuccess(data.msg);
      await refrescarEstadoProduccion(true);

    } catch (err) {
      swalError(err.message);
      await refrescarEstadoProduccion(true);
    }
  }

  document.querySelectorAll('.js-selectable-node').forEach(nodeEl => {
    nodeEl.addEventListener('click', function (e) {
      if (e.target.closest('button')) return;

      const type = this.dataset.nodeType;
      const estIndex = parseInt(this.dataset.estIndex, 10);
      const subIndex = this.dataset.subIndex !== undefined ? parseInt(this.dataset.subIndex, 10) : null;

      const node = getNodeData(type, estIndex, subIndex);
      if (!node) return;

      fillDetailPanel(node);
      highlightSelected(this);
    });
  });


  aplicarFiltroVisualUsuario();
  seleccionarPrimerNodoVisibleUsuario();

  if (btnIniciarUnidad) btnIniciarUnidad.addEventListener('click', iniciarProcesoActual);
  if (btnFinalizarUnidad) btnFinalizarUnidad.addEventListener('click', finalizarProcesoActual);

  if (btnIniciarProduccion) {

    btnIniciarProduccion.addEventListener('click', async function () {

      try {

        if (!currentNode || !currentNode.idplaneacion) {

          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'No existe una planeación válida para iniciar.'
          });

          return;
        }

        const idplaneacion = parseInt(currentNode.idplaneacion, 10);

        const confirmacion = await Swal.fire({
          icon: 'question',
          title: '¿Iniciar producción?',
          text: 'Se iniciará la producción de esta planeación.',
          showCancelButton: true,
          confirmButtonText: 'Sí, iniciar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true
        });

        if (!confirmacion.isConfirmed) {
          return;
        }

        await iniciarProduccion(idplaneacion);

      } catch (err) {

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.message || 'Ocurrió un error inesperado.'
        });

      }

    });

  }



  if (btnFinalizarProduccion) {

    btnFinalizarProduccion.addEventListener('click', async function () {

      try {

        if (!currentNode || !currentNode.idplaneacion) {

          Swal.fire({
            icon: 'warning',
            title: 'Atención',
            text: 'No existe una planeación válida para finalizar.'
          });

          return;
        }

        const idplaneacion = parseInt(currentNode.idplaneacion, 10);

        const confirmacion = await Swal.fire({
          icon: 'question',
          title: '¿Finalizar producción?',
          text: 'Se finalizará completamente esta orden de producción.',
          showCancelButton: true,
          confirmButtonText: 'Sí, finalizar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true
        });

        if (!confirmacion.isConfirmed) {
          return;
        }

        await finalizarProduccion(idplaneacion);

      } catch (err) {

        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: err.message || 'Ocurrió un error inesperado.'
        });

      }

    });

  }

  if (btnExpandirTodo) {
    btnExpandirTodo.addEventListener('click', function () {

      document.querySelectorAll('.mrp-sub-zone').forEach(zone => {
        zone.classList.remove('is-collapsed');
      });

      const flowRow =
        document.querySelector('.mrp-flow-row');

      if (flowRow) {
        flowRow.classList.remove('only-stations-row');
      }

      aplicarFiltroVisualUsuario();
    });
  }

  if (btnContraerTodo) {
    btnContraerTodo.addEventListener('click', function () {

      document.querySelectorAll('.mrp-sub-zone').forEach(zone => {
        zone.classList.add('is-collapsed');
      });

      const flowRow =
        document.querySelector('.mrp-flow-row');

      if (flowRow) {
        flowRow.classList.add('only-stations-row');
      }

      aplicarFiltroVisualUsuario();
    });
  }

  if (btnDetalleHerramientas) {
    btnDetalleHerramientas.addEventListener('click', function () {
      if (!currentNode) return;
      openModalHerramientas(currentNode.productoid, currentNode.estacionid, currentNode.title, currentNode.proceso, currentNode.type);
    });
  }

  if (btnDetalleComponentes) {
    btnDetalleComponentes.addEventListener('click', function () {
      if (!currentNode) return;
      openModalComponentes(currentNode.productoid, currentNode.estacionid, currentNode.title, currentNode.proceso, currentNode.cantidad, currentNode.type);
    });
  }

  if (btnDetalleOperaciones) {
    btnDetalleOperaciones.addEventListener('click', function () {
      if (!currentNode) return;
      openModalEspecificaciones(currentNode.productoid, currentNode.estacionid, currentNode.title, currentNode.proceso, currentNode.cantidad, currentNode.type, currentNode.idordengeneral, currentNode.unit);
    });
  }

  if (btnOperacionesCriticas) {
    btnOperacionesCriticas.addEventListener('click', function () {
      // console.log('abrir modal de especificaciones criticas!!');

      if (!currentNode) return;

      openModalEspecificacionesCriticas(
        currentNode.productoid,
        currentNode.estacionid,
        currentNode.title,
        currentNode.proceso,
        currentNode.cantidad,
        currentNode.type,
        currentNode.idordengeneral,
        currentNode.unit
      );
    });
  }

  if (btnDetalleAyudas) {
    btnDetalleAyudas.addEventListener('click', function () {
      if (!currentNode) return;
      modalAyudasVisuales(currentNode.title, currentNode.productoid, currentNode.estacionid, currentNode.type, currentNode.title, currentNode.proceso,);
    });
  }

  if (btnDetallePuntosCalidad) {
    btnDetallePuntosCalidad.addEventListener('click', function () {
      if (!currentNode) return;
      modalDemo('Puntos de calidad', `Aquí mostrarás los puntos de calidad de: ${currentNode.title || '-'}`);
    });
  }

  if (btnCalidad) {
    btnCalidad.addEventListener('click', function () {

      if (!currentNode) return;

      modalCalidad(
        currentNode.productoid,
        currentNode.estacionid,
        currentNode.title,
        currentNode.proceso,
        currentNode.cantidad,
        currentNode.type,
        currentNode.idordengeneral,
        currentNode.unit
      );
    });

    if (btnEstamparVin) {
      btnEstamparVin.addEventListener('click', function () {
        if (!currentNode) return;

        // modalDemo(
        //   'Estampar VIN',
        //   `Aquí registrarás el VIN de la unidad ${currentNode.unit || '-'} en ${currentNode.title || '-'}`
        // );

        openModalIdentificacion(
          currentNode.productoid,
          currentNode.estacionid,
          currentNode.title,
          currentNode.proceso,
          currentNode.cantidad,
          currentNode.idordengeneral,
          currentNode.numorden,
          currentNode.numbase
        );
      });
    }
  }

  if (btnReanudarParo) {

    btnReanudarParo.addEventListener('click', async function () {

      if (!currentNode) return;

      Swal.fire({
        title: '¿Reanudar proceso?',
        text: '¿Estás seguro de reanudar el proceso de producción de esta unidad?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, reanudar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#0ab39c',
        cancelButtonColor: '#f06548'
      }).then(async (result) => {

        if (!result.isConfirmed) return;

        try {

          const response = await postJson(
            `${base_url}/plan_planeacionv1/reanudarParoMomentaneo`,
            {
              idordengeneral: currentNode.idordengeneral
            }
          );

          if (!response.status) {

            swalError(response.msg);

            return;
          }

          detenerTimerParo();

          secondsParo = 0;

          renderTimerParo();

          swalSuccess(response.msg);

          await refrescarEstadoProduccion(true);

        } catch (err) {

          swalError(err.message);
        }

      });

    });
  }

  function modalDemo(title, text) {
    Swal.fire({
      title,
      text,
      icon: 'info',
      confirmButtonText: 'Cerrar',
      background: '#0f172a',
      color: '#fff'
    });
  }

  /// funcion para ver las unidades terminadas 

  function renderHistorialEventosEstacion(node) {
    const cardHistorialEventos = document.getElementById('cardHistorialEventos');
    const contenedorHistorialEventos = document.getElementById('contenedorHistorialEventos');

    if (!cardHistorialEventos || !contenedorHistorialEventos) return;

    if (!node || node.type !== 'estacion') {
      cardHistorialEventos.classList.add('d-none');
      contenedorHistorialEventos.innerHTML = '';
      return;
    }

    cardHistorialEventos.classList.remove('d-none');

    const est = getStation(node.estIndex);

    const finalizadas = (est?.ordenes_trabajo || [])
      .filter(o => {
        const retiroActivo =
          Number(o.accion_produccion || 0) === 2 &&
          Number(o.accion_activa || 0) === 2;

        return Number(o.estatus || 0) === 3 && !retiroActivo;
      })
      .sort((a, b) => {
        const fechaA = new Date(String(a.fecha_fin || '').replace(' ', 'T')).getTime() || 0;
        const fechaB = new Date(String(b.fecha_fin || '').replace(' ', 'T')).getTime() || 0;

        return fechaB - fechaA;
      });

    if (!finalizadas.length) {
      contenedorHistorialEventos.innerHTML = `
      <div class="text-muted small py-2">
        Esta estación aún no tiene unidades finalizadas.
      </div>
    `;
      return;
    }

    if (finalizadas.length >= 5) {

      contenedorHistorialEventos.classList.add(
        'historial-scroll'
      );

    } else {

      contenedorHistorialEventos.classList.remove(
        'historial-scroll'
      );
    }

    contenedorHistorialEventos.innerHTML = finalizadas.map((o, index) => {

      const esUltima = index === 0;

      return `
      <div class="mrp-extra-item ${esUltima ? 'border border-success rounded-3 p-2 bg-success-subtle' : ''}">
        <div class="mrp-extra-main">
          ${esUltima ? '<i class="ri-checkbox-circle-fill text-success me-1"></i>' : '<i class="ri-history-line me-1"></i>'}
          ${o.fecha_fin && o.fecha_fin !== '0000-00-00 00:00:00' ? o.fecha_fin : '-'} · ${o.num_sub_orden || '-'}
        </div>

        <div class="mrp-extra-muted">
          ${esUltima
          ? `Última unidad finalizada correctamente en ${est.nombre_estacion || 'esta estación'}.`
          : `Unidad finalizada correctamente en ${est.nombre_estacion || 'esta estación'}.`
        }
        </div>
      </div>
    `;
    }).join('');
  }



  let datosInspeccionCalidad = {
    productoid: 0,
    estacionid: 0,
    tipo: 'estacion',
    idordengeneral: 0,
    unidad_actual: ''
  };


  actualizarKpisYCards();

  aplicarFiltroVisualUsuario();

  // document.querySelectorAll('.bloque-subensamble')
  //   .forEach(el => el.style.display = 'none');

  // document.querySelectorAll('.mrp-sub-zone')
  //   .forEach(zone => zone.classList.add('is-collapsed'));

  seleccionarPrimerNodoVisibleUsuario();

  renderTime();

  setInterval(() => {
    refrescarEstadoProduccion(true);
  }, 3000);
});



































////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
// FUNCIÓPN PARA CAMBIAR TEXTO EN DIV DE PARO
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////

function renderDetalleParoMomentaneo(node) {
  const detalleParo = document.getElementById('detalleParoMomentaneo');
  if (!detalleParo || !node || !node.unidadRaw) return;

  const acciones = node.unidadRaw.acciones_produccion || [];

  const accion = acciones.find(a =>
    Number(a.tipo_accion) === 1 &&
    Number(a.estado) === 2
  );

  if (!accion) {
    detalleParo.innerHTML = `
      La unidad se encuentra temporalmente detenida.
      Mientras el paro esté activo no será posible finalizar
      el proceso de esta estación hasta reanudar la operación.
    `;
    return;
  }

  detalleParo.innerHTML = `
    <div class="fw-semibold mb-2">
      Paro activo registrado en producción
    </div>

    <div>
      En la unidad <strong>${accion.unidad || '-'}</strong>, se registró un 
      <strong>${accion.origen_accion_texto || 'paro'}</strong> de tipo 
      <strong>${accion.tipo_accion_texto || 'paro momentáneo'}</strong>.
    </div>

    <div class="mt-2">
      El paro fue iniciado por <strong>${accion.usuario_nombre || '-'}</strong>
      el día <strong>${accion.fecha_inicio || '-'}</strong>.
      Actualmente el paro se encuentra en estado 
      <strong>${accion.estado_texto || 'Activo'}</strong>.
    </div>

    <div class="mt-2 text-warning fw-semibold">
      Mientras este paro permanezca activo, no será posible finalizar el proceso
      de esta estación hasta que la operación sea reanudada.
    </div>
  `;
}





////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////
// FUNCIÓPN PARA OBTENER LAS AYUDAS VISUALES POR PRODUCTO Y ESTACIÓN
////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////

async function modalAyudasVisuales(title, productoid, estacionid, tipo, nombreEstacion = '', procesoTxt = '',) {

  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;

  const modalAyudas = document.getElementById('modalAyudas');
  const tbody = document.getElementById('AyudasTableBody');

  if (!modalAyudas || !tbody) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalAyudas);

  document.getElementById('titleEstacionAyuda').textContent = nombreEstacion || 'Estación';
  document.getElementById('titleProcesoAyuda').textContent = procesoTxt || 'Proceso';

  tbody.innerHTML = `
    <tr>
      <td colspan="2" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/getAyudas`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid, tipo })
    });

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar ayudas');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">No hay ayudas</td></tr>`;
      return;
    }



    tbody.innerHTML = rows.map(r => {

      const rutaArchivo = `${base_url}/Assets/uploads/ayudas_estacion/${r.archivo}`;

      return `
    <tr>
      <td>${r.titulo || '-'}</td>

      <td class="text-center">
        ${r.tipo || '-'}
      </td>

      <td class="text-center">
        ${r.archivo
          ? `
              <a 
                href="${rutaArchivo}" 
                target="_blank"
                class="btn btn-sm btn-soft-info"
                title="Ver documento">

                <i class="ri-eye-fill align-bottom"></i>
              </a>
            `
          : '-'
        }
      </td>
    </tr>
  `;

    }).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-danger">${err.message}</td></tr>`;
  }

}

////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////
// FUNCIÓPN PARA OBTENER LAS HERRAMIENTAS POR PRODUCTO Y ESTACIÓN
////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

async function openModalHerramientas(productoid, estacionid, nombreEstacion = '', procesoTxt = '', tipo = 'estacion') {
  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;

  const modalHerr = document.getElementById('modalHerramientas');
  const tbody = document.getElementById('herrTableBody');

  if (!modalHerr || !tbody) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalHerr);

  document.getElementById('titleEstacionH').textContent = nombreEstacion || 'Estación';
  document.getElementById('titleProcesoH').textContent = procesoTxt || 'Proceso';

  tbody.innerHTML = `
    <tr>
      <td colspan="2" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/getHerramientas`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid, tipo })
    });

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar herramientas');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="2" class="text-center text-muted">No hay herramientas</td></tr>`;
      return;
    }

    tbody.innerHTML = rows.map(r => `
      <tr>
        <td>${r.herramienta || '-'}</td>
        <td class="text-center">${r.cantidad || 0}</td>
      </tr>
    `).join('');

  } catch (err) {
    tbody.innerHTML = `<tr><td colspan="2" class="text-center text-danger">${err.message}</td></tr>`;
  }
}


////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////
// FUNCIÓPN PARA OBTENER LOS COMPONENTES POR PRODUCTO Y ESTACIÓN
////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////
async function openModalComponentes(productoid, estacionid, nombreEstacion = '', procesoTxt = '', cantidadPedido = 1, tipo = 'estacion') {
  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;
  cantidadPedido = parseFloat(cantidadPedido) || 0;

  const modalCom = document.getElementById('modalComponentes');
  const tbody = document.getElementById('compTableBody');

  if (!modalCom || !tbody) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalCom);

  document.getElementById('titleEstacion').textContent = nombreEstacion || 'Estación';
  document.getElementById('titleProceso').textContent = procesoTxt || 'Proceso';

  tbody.innerHTML = `
    <tr>
      <td colspan="3" class="text-center">
        <div class="spinner-border spinner-border-sm"></div> Cargando...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/getComponentes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
      body: JSON.stringify({ productoid, estacionid, tipo })
    });

    const data = await resp.json();
    if (!data.status) throw new Error(data.msg || 'Error al cargar componentes');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">No hay componentes</td></tr>`;
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
    tbody.innerHTML = `<tr><td colspan="3" class="text-center text-danger">${err.message}</td></tr>`;
  }
}


////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////
// FUNCIÓPN PARA OBTENER LAS ESPECIFICACIONES POR PRODUCTO Y ESTACIÓN
////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////

async function openModalEspecificaciones(
  productoid,
  origenid,
  nombreEstacion = '',
  procesoTxt = '',
  cantidadPedido = 1,
  tipo = 'estacion',
  idordengeneral = 0,
  unidad_actual = ''
) {

  productoid = parseInt(productoid, 10) || 0;
  origenid = parseInt(origenid, 10) || 0;

  const modalEsp = document.getElementById('modalEspecificaciones');
  const tbody = document.getElementById('specTableBody');

  if (!modalEsp || !tbody) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEsp);

  document.getElementById('titleEstacionEs').textContent = nombreEstacion || 'Proceso';
  document.getElementById('titleProcesoEs').textContent = procesoTxt || 'Operaciones';
  document.getElementById('titleUnidad').textContent = unidad_actual || 'Unidad';

  tbody.innerHTML = `
    <tr>
      <td colspan="3" class="text-center py-4">
        <div class="spinner-border spinner-border-sm me-2"></div>
        Cargando operaciones...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/getEspecificaciones`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        productoid,
        origenid,
        tipo,
        idordengeneral,
        unidad_actual
      })
    });

    const data = await resp.json();

    if (!data.status) throw new Error(data.msg || 'Error al cargar operaciones');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-success py-4">
            <i class="ri-checkbox-circle-line fs-24 d-block mb-1"></i>
            Todas las operaciones ya fueron registradas.
          </td>
        </tr>
      `;
      return;
    }

    tbody.innerHTML = rows.map((r) => {
      const idespecificacion = tipo === 'subensamble'
        ? r.idespecificacionsubensamble
        : r.idespecificacion;

      const operacionTexto = r.especificacion || '-';

      return `
        <tr id="rowOperacion_${tipo}_${idespecificacion}">
          <td>
            <div class="d-flex align-items-start gap-2">
              <div class="avatar-xs bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center">
                <i class="ri-tools-line"></i>
              </div>
              <div>
                <div class="fw-semibold">${operacionTexto}</div>
                <small class="text-muted">
                  ${tipo === 'subensamble' ? 'Operación de subensamble' : 'Operación de estación'}
                </small>
              </div>
            </div>
          </td>

<td>
  <div class="position-relative">
    <div class="input-group input-group-sm">
      <span class="input-group-text">
        <i class="ri-qr-scan-2-line"></i>
      </span>

      <input
        type="text"
        class="form-control empleado-operacion-input"
        id="empleadoOperacion_${tipo}_${idespecificacion}"
        placeholder="Escanea QR, escribe número, nombre o correo"
        autocomplete="off"
        data-tipo="${tipo}"
        data-idespecificacion="${idespecificacion}"
      >
    </div>

    <input type="hidden" id="usuarioOperacion_${tipo}_${idespecificacion}">
    <input type="hidden" id="numColaboradorOperacion_${tipo}_${idespecificacion}">
    

    <div 
      class="autocomplete-usuarios shadow-lg d-none"
      id="listaUsuarios_${tipo}_${idespecificacion}">
    </div>
  </div>

  <div class="mt-2 d-none" id="previewUsuario_${tipo}_${idespecificacion}">
    <div class="alert alert-success border-0 py-2 px-3 mb-0">
      <div class="d-flex align-items-center gap-2">

        <div>
          <div class="fw-semibold small" id="nombreUsuario_${tipo}_${idespecificacion}"></div>
          <div class="text-muted small">
            No. colaborador: 
            <span class="fw-semibold" id="numeroUsuario_${tipo}_${idespecificacion}"></span>
          </div>
          <div class="text-muted small" id="correoUsuario_${tipo}_${idespecificacion}"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="text-danger small mt-1 d-none" id="errorUsuario_${tipo}_${idespecificacion}"></div>
</td>

          <td class="text-center">
            <button
              type="button"
              class="btn btn-sm btn-success"
              id="btnRegistrarOperacion_${tipo}_${idespecificacion}"
              disabled
              onclick="registrarOperacionRealizada({
                productoid: ${productoid},
                origenid: ${origenid},
                tipo_origen: '${tipo}',
                idordengeneral: ${idordengeneral},
                unidad_actual: '${unidad_actual}',
                idespecificacion: ${idespecificacion},
                operacion_texto: \`${operacionTexto.replace(/`/g, '')}\`
              })"
            >
              <i class="ri-save-3-line me-1"></i>
              Registrar
            </button>
          </td>
        </tr>
      `;
    }).join('');

    inicializarBusquedaUsuariosOperacion();

  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="3" class="text-center text-danger py-4">
          ${err.message}
        </td>
      </tr>
    `;
  }
}


//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////
// FUNCIÓPN PARA OBTENER LAS ESPECIFICACIONES CRITICAS POR PRODUCTO Y ESTACIÓN
//////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////

async function openModalEspecificacionesCriticas(
  productoid,
  origenid,
  nombreEstacion = '',
  procesoTxt = '',
  cantidadPedido = 1,
  tipo = 'estacion',
  idordengeneral = 0,
  unidad_actual = ''
) {

  datosValidacionCritica = {
    productoid: parseInt(productoid, 10) || 0,
    origenid: parseInt(origenid, 10) || 0,
    tipo_origen: tipo,
    idordengeneral: parseInt(idordengeneral, 10) || 0,
    unidad_actual: unidad_actual || ''
  };

  productoid = parseInt(productoid, 10) || 0;
  origenid = parseInt(origenid, 10) || 0;

  const modalEsp = document.getElementById('modalEspecificacionesCriticas');
  const tbody = document.getElementById('specCriticasTableBody');

  if (!modalEsp || !tbody) return;

  const modal = bootstrap.Modal.getOrCreateInstance(modalEsp);

  document.getElementById('titleEstacionEsC').textContent = nombreEstacion || 'Proceso';
  document.getElementById('titleProcesoEsC').textContent = procesoTxt || 'Operaciones';
  document.getElementById('titleUnidadC').textContent = unidad_actual || 'Unidad';

  tbody.innerHTML = `
    <tr>
      <td colspan="3" class="text-center py-4">
        <div class="spinner-border spinner-border-sm me-2"></div>
        Cargando operaciones...
      </td>
    </tr>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/getEspecificacionesCriticas`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        productoid,
        origenid,
        tipo,
        idordengeneral,
        unidad_actual
      })
    });

    const data = await resp.json();

    if (!data.status) throw new Error(data.msg || 'Error al cargar operaciones');

    const rows = data.data?.rows || [];

    if (!rows.length) {
      tbody.innerHTML = `
        <tr>
          <td colspan="3" class="text-center text-success py-4">
            <i class="ri-checkbox-circle-line fs-24 d-block mb-1"></i>
            Todas las operaciones ya fueron registradas.
          </td>
        </tr>
      `;
      return;
    }



    tbody.innerHTML = rows.map((r) => {

      const idespecificacion = tipo === 'subensamble'
        ? r.idespecificacionsubensamble
        : r.idespecificacion;

      const especificacionTexto = r.especificacion || '-';

      return `
    <tr id="rowCritica_${tipo}_${idespecificacion}" data-operacion="${especificacionTexto}">

      <!-- ESPECIFICACIÓN -->
      <td width="50%">

        <div class="d-flex align-items-start gap-2">

          <div class="avatar-xs bg-danger-subtle text-danger rounded-circle d-flex align-items-center justify-content-center">
            <i class="ri-shield-check-line"></i>
          </div>

          <div>

            <div class="fw-semibold">
              ${especificacionTexto}
            </div>

            <small class="text-muted">
              ${tipo === 'subensamble'
          ? 'Validación crítica de subensamble'
          : 'Validación crítica de estación'}
            </small>

          </div>

        </div>

      </td>

      <!-- RESULTADO -->
      <td width="25%">

        <div class="d-flex flex-column gap-2">

          <div class="form-check form-radio-success">

            <input
              class="form-check-input"
              type="radio"
              name="resultado_${tipo}_${idespecificacion}"
              value="1"
              checked
              onchange="toggleIncidencia('${tipo}', ${idespecificacion}, false)"
            >

            <label class="form-check-label fw-semibold text-success">
              Conforme
            </label>

          </div>

          <div class="form-check form-radio-danger">

            <input
              class="form-check-input"
              type="radio"
              name="resultado_${tipo}_${idespecificacion}"
              value="2"
              onchange="toggleIncidencia('${tipo}', ${idespecificacion}, true)"
            >

            <label class="form-check-label fw-semibold text-danger">
              No conforme
            </label>

          </div>

        </div>

      </td>

      <!-- OBSERVACIÓN -->
      <td width="25%">

        <div
          class="d-none"
          id="containerIncidencia_${tipo}_${idespecificacion}"
        >

          <textarea
            class="form-control form-control-sm border-danger"
            rows="3"
            id="observacion_${tipo}_${idespecificacion}"
            placeholder="Captura el detalle de la desviación detectada..."
          ></textarea>

          <small class="text-muted">
            Describe el motivo por el cual la validación fue marcada como no conforme.
          </small>

        </div>

        <div
          class="text-success small fw-semibold"
          id="textoConforme_${tipo}_${idespecificacion}"
        >
          <i class="ri-checkbox-circle-line me-1"></i>
          Validación aprobada.
        </div>

      </td>

    </tr>
  `;

    }).join('');

    // inicializarBusquedaUsuariosOperacion();

  } catch (err) {
    tbody.innerHTML = `
      <tr>
        <td colspan="3" class="text-center text-danger py-4">
          ${err.message}
        </td>
      </tr>
    `;
  }
}


function toggleIncidencia(tipo, idespecificacion, mostrar) {

  const contenedor = document.getElementById(
    `containerIncidencia_${tipo}_${idespecificacion}`
  );

  const textoConforme = document.getElementById(
    `textoConforme_${tipo}_${idespecificacion}`
  );

  if (contenedor) {
    contenedor.classList.toggle('d-none', !mostrar);
  }

  if (textoConforme) {
    textoConforme.classList.toggle('d-none', mostrar);
  }
}


////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
// FUNCIÓN PARA INICIAR TODA LA PRODUCCIÓN
////////////////////////////////////////////////////////
////////////////////////////////////////////////////////

async function iniciarProduccion(idplaneacion) {

  Swal.fire({
    title: 'Iniciando producción...',
    text: 'Por favor espera un momento.',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  const resp = await fetch(
    `${base_url}/plan_planeacionv1/iniciarPlaneacion`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        idplaneacion
      })
    }
  );

  let data = null;

  try {
    data = await resp.json();
  } catch (e) {
    throw new Error('Respuesta inválida del servidor.');
  }

  if (!resp.ok) {
    throw new Error('Error de comunicación con el servidor.');
  }

  if (!data.status) {
    throw new Error(data.msg || 'No fue posible iniciar la producción.');
  }

  Swal.fire({
    icon: 'success',
    title: 'Correcto',
    text: data.msg || 'Producción iniciada correctamente.',
    confirmButtonText: 'Aceptar'
  });

  if (typeof MRP_STATE !== 'undefined') {
    MRP_STATE.fase = 3;
  }

  if (typeof validarBotonesProduccion === 'function') {
    validarBotonesProduccion();
  }

}


////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
// FUNCIÓN PARA FINAÑLIZAR TODA LA PRODUCCIÓN
////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
async function finalizarProduccion(idplaneacion) {

  Swal.fire({
    title: 'Finalizando producción...',
    text: 'Por favor espera un momento.',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  const resp = await fetch(
    `${base_url}/plan_planeacionv1/finalizarPlaneacion`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        idplaneacion
      })
    }
  );

  let data = null;

  try {

    data = await resp.json();

  } catch (e) {

    throw new Error('Respuesta inválida del servidor.');
  }

  if (!resp.ok) {
    throw new Error('Error de comunicación con el servidor.');
  }

  if (!data.status) {
    throw new Error(data.msg || 'No fue posible finalizar la producción.');
  }

  Swal.fire({
    icon: 'success',
    title: 'Correcto',
    text: data.msg || 'Producción finalizada correctamente.',
    confirmButtonText: 'Aceptar'
  });


  if (typeof MRP_STATE !== 'undefined') {
    MRP_STATE.fase = 5;
  }

  if (typeof validarBotonesProduccion === 'function') {
    validarBotonesProduccion();
  }

}

////////////////////////////////////////////////////////
////////////////////////////////////////////////////////
// FUNCIÓN PARA GUARDAR LAS ESPECIFICACIONES CRITICAS
////////////////////////////////////////////////////////
////////////////////////////////////////////////////////

function abrirModalAccionProduccion(origen = 'paro_manual', contexto = {}) {

  const opcionesAccionProduccion = {
    criticas: [
      {
        value: 1,
        titulo: 'Paro momentáneo',
        descripcion: 'Detiene temporalmente la estación actual mientras se corrige o valida la incidencia. La unidad permanece dentro de línea.',
        icono: 'ri-pause-circle-line',
        clase: 'text-danger'
      },
      {
        value: 2,
        titulo: 'Retiro de AGV',
        descripcion: 'La unidad será retirada temporalmente de la línea de producción mediante AGV. La siguiente unidad podrá continuar proceso.',
        icono: 'ri-truck-line',
        clase: 'text-warning'
      },
      {
        value: 3,
        titulo: 'Continuar unidad alarmada',
        descripcion: 'La unidad continuará hacia la siguiente estación, pero quedará marcada con advertencia para seguimiento.',
        icono: 'ri-alert-line',
        clase: 'text-warning'
      }
    ],

    paro_manual: [
      {
        value: 1,
        titulo: 'Paro momentáneo',
        descripcion: 'Detiene temporalmente la estación actual mientras se corrige o valida la incidencia. La unidad permanece dentro de línea.',
        icono: 'ri-pause-circle-line',
        clase: 'text-danger'
      },
      {
        value: 2,
        titulo: 'Retiro de AGV',
        descripcion: 'La unidad será retirada temporalmente de la línea de producción mediante AGV. La siguiente unidad podrá continuar proceso.',
        icono: 'ri-truck-line',
        clase: 'text-warning'
      },
      {
        value: 3,
        titulo: 'Unidad alarmada',
        descripcion: 'La unidad continuará su proceso, pero quedará marcada con advertencia.',
        icono: 'ri-alert-line',
        clase: 'text-warning'
      },
      {
        value: 4,
        titulo: 'Solicitud de asistencia',
        descripcion: 'Se notificará al supervisor para solicitar asistencia. No se detiene la estación ni se retira la unidad.',
        icono: 'ri-user-voice-line',
        clase: 'text-info'
      },
      {
        value: 5,
        titulo: 'Falta de material',
        descripcion: 'Se notificará al supervisor por falta de material. No se detiene la estación automáticamente.',
        icono: 'ri-archive-line',
        clase: 'text-danger'
      }
    ]
  };

  contextoAccionProduccion = {
    productoid: parseInt(contexto.productoid, 10) || 0,
    estacionid: parseInt(contexto.estacionid, 10) || 0,
    idordengeneral: parseInt(contexto.idordengeneral, 10) || 0,
    unidad_actual: contexto.unidad_actual || '',
    origen_accion: origen === 'criticas' ? 1 : 2
  };

  const opciones = opcionesAccionProduccion[origen] || [];

  document.getElementById('tituloModalAccionProduccion').textContent =
    origen === 'criticas'
      ? 'Acción requerida por no conformidad'
      : 'Pausar / Paro de producción';

  document.getElementById('subtituloModalAccionProduccion').textContent =
    origen === 'criticas'
      ? 'Se detectó al menos una especificación crítica no conforme.'
      : 'Selecciona el tipo de evento que se aplicará a la unidad actual.';

  document.getElementById('descripcionModalAccionProduccion').textContent =
    'Esta acción quedará registrada para trazabilidad de producción.';

  const contenedor = document.getElementById('contenedorOpcionesAccionProduccion');

  contenedor.innerHTML = opciones.map(op => `
    <label class="border rounded p-3 cursor-pointer">
      <div class="form-check">
        <input
          class="form-check-input"
          type="radio"
          name="tipoAccionProduccion"
          value="${op.value}"
        >

        <span class="fw-semibold ${op.clase}">
          <i class="${op.icono} me-1"></i>
          ${op.titulo}
        </span>
      </div>

      <div class="small text-muted mt-2 ps-4">
        ${op.descripcion}
      </div>
    </label>
  `).join('');

  bootstrap.Modal.getOrCreateInstance(
    document.getElementById('modalAccionProduccion')
  ).show();
}

async function guardarEspecificacionesCriticas(forzarGuardado = false) {

  try {

    const registros = [];

    document.querySelectorAll('[id^="rowCritica_"]').forEach(row => {

      const rowId = row.id;
      const partes = rowId.split('_');

      const tipo = partes[1];
      const idespecificacion = partes[2];

      const operacion_texto = row.dataset.operacion || '';

      const resultado = document.querySelector(
        `input[name="resultado_${tipo}_${idespecificacion}"]:checked`
      )?.value || 1;

      const observaciones = document.getElementById(
        `observacion_${tipo}_${idespecificacion}`
      )?.value || '';

      if (parseInt(resultado) === 2 && observaciones.trim() === '') {
        throw new Error('Debes capturar el detalle de la desviación detectada.');
      }

      registros.push({
        idespecificacion: parseInt(idespecificacion, 10) || 0,
        operacion_texto,
        resultado: parseInt(resultado, 10) || 1,
        observaciones: observaciones.trim()
      });

    });

    if (!registros.length) {
      throw new Error('No hay especificaciones críticas para validar.');
    }

    const payload = {
      productoid: datosValidacionCritica.productoid,
      origenid: datosValidacionCritica.origenid,
      tipo_origen: datosValidacionCritica.tipo_origen,
      idordengeneral: datosValidacionCritica.idordengeneral,
      unidad_actual: datosValidacionCritica.unidad_actual,
      registros
    };

    const existeNoConforme = registros.some(item => item.resultado === 2);

    // ==========================================
    // SOLO ABRIR MODAL SI NO ES FORZADO
    // ==========================================
    if (existeNoConforme && !forzarGuardado) {

      payloadCriticosPendiente = payload;

      origenAccionProduccion = 'criticas';

      abrirModalAccionProduccion('criticas', {
        productoid: datosValidacionCritica.productoid,
        estacionid: datosValidacionCritica.origenid,
        idordengeneral: datosValidacionCritica.idordengeneral,
        unidad_actual: datosValidacionCritica.unidad_actual
      });

      return;
    }

    await enviarValidacionCritica(payload);

  } catch (err) {

    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: err.message || 'Ocurrió un error inesperado.'
    });

  }
}


async function confirmarAccionProduccion() {

  try {

    const tipoAccion = document.querySelector(
      'input[name="tipoAccionProduccion"]:checked'
    )?.value || '';

    if (!tipoAccion) {
      throw new Error('Selecciona una acción para continuar.');
    }

    const payload = {
      productoid: contextoAccionProduccion.productoid,
      estacionid: contextoAccionProduccion.estacionid,
      idordengeneral: contextoAccionProduccion.idordengeneral,
      unidad_actual: contextoAccionProduccion.unidad_actual,
      origen_accion: contextoAccionProduccion.origen_accion,
      tipo_accion: parseInt(tipoAccion, 10)
    };

    await registrarAccionProduccion(payload);

  } catch (err) {

    Swal.fire({
      icon: 'warning',
      title: 'Atención',
      text: err.message || 'Ocurrió un error inesperado.'
    });

  }
}


async function enviarValidacionCritica(payload) {

  Swal.fire({
    title: 'Guardando validaciones...',
    text: 'Por favor espera un momento.',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  const resp = await fetch(
    `${base_url}/plan_planeacionv1/guardarEspecificacionesCriticas`,
    {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    }
  );

  const data = await resp.json();

  if (!data.status) {
    throw new Error(data.msg || 'No se pudo guardar la validación.');
  }

  cerrarModalBootstrap('modalEspecificacionesCriticas');

  Swal.fire({
    icon: 'success',
    title: 'Correcto',
    text: data.msg || 'Validaciones críticas registradas correctamente.',
    confirmButtonText: 'Aceptar'
  });
}



async function registrarAccionProduccion(payload) {

  Swal.fire({
    title: 'Registrando acción...',
    text: 'Por favor espera un momento.',
    allowOutsideClick: false,
    didOpen: () => Swal.showLoading()
  });

  const resp = await fetch(`${base_url}/plan_planeacionv1/registrarAccionProduccion`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
    body: JSON.stringify(payload)
  });

  const data = await resp.json();

  if (!data.status) {
    throw new Error(data.msg || 'No se pudo registrar la acción.');
  }

  // =========================
  // GUARDAR CRÍTICOS DESPUÉS
  // =========================
  if (
    origenAccionProduccion === 'criticas' &&
    payloadCriticosPendiente
  ) {

    await enviarValidacionCritica(payloadCriticosPendiente);

    payloadCriticosPendiente = null;

    origenAccionProduccion = '';
  }

  cerrarModalBootstrap('modalAccionProduccion');

  Swal.fire({
    icon: 'success',
    title: 'Correcto',
    text: data.msg || 'Acción registrada correctamente.'
  });
}

function cerrarModalBootstrap(idModal) {

  const modalElement = document.getElementById(idModal);

  if (!modalElement) return;

  const modalInstance = bootstrap.Modal.getInstance(modalElement);

  if (modalInstance) {
    modalInstance.hide();
  }

  document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());

  document.body.classList.remove('modal-open');
  document.body.style = '';
}


////////////////////////////////////////////////////////
// FIN FUNCIÓN PARA GUARDAR LAS ESPECIFICACIONES CRITICAS
////////////////////////////////////////////////////////



/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
//ESTA SECCIÓN SON FUNCIONES PARA EL GUARDADO DE ASIGNAR OPERACIONES A EMPLEADOS
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////

function inicializarBusquedaUsuariosOperacion() {
  document.querySelectorAll('.empleado-operacion-input').forEach(input => {
    // let timer = null;

    input.addEventListener('input', function () {
      clearTimeout(timer);

      const valor = this.value.trim();
      const tipo = this.dataset.tipo;
      const idespecificacion = this.dataset.idespecificacion;

      limpiarPreviewUsuarioOperacion(tipo, idespecificacion);

      if (valor.length < 2) {
        ocultarListaUsuarios(tipo, idespecificacion);
        return;
      }

      timer = setTimeout(() => {
        buscarUsuariosOperacion(valor, tipo, idespecificacion);
      }, 300);
    });

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();

        const valor = this.value.trim();
        const tipo = this.dataset.tipo;
        const idespecificacion = this.dataset.idespecificacion;

        if (valor.length > 0) {
          buscarUsuariosOperacion(valor, tipo, idespecificacion, true);
        }
      }
    });
  });

  document.addEventListener('click', function (e) {
    if (!e.target.closest('.position-relative')) {
      document.querySelectorAll('.autocomplete-usuarios').forEach(el => {
        el.classList.add('d-none');
      });
    }
  });
}

function limpiarPreviewUsuarioOperacion(tipo, idespecificacion) {

  document.getElementById(`usuarioOperacion_${tipo}_${idespecificacion}`).value = '';

  document.getElementById(`numColaboradorOperacion_${tipo}_${idespecificacion}`).value = '';

  document.getElementById(`previewUsuario_${tipo}_${idespecificacion}`).classList.add('d-none');

  document.getElementById(`errorUsuario_${tipo}_${idespecificacion}`).classList.add('d-none');

  document.getElementById(`btnRegistrarOperacion_${tipo}_${idespecificacion}`).disabled = true;
}

function ocultarListaUsuarios(tipo, idespecificacion) {
  const lista = document.getElementById(`listaUsuarios_${tipo}_${idespecificacion}`);

  if (lista) {
    lista.classList.add('d-none');
    lista.innerHTML = '';
  }
}

async function buscarUsuariosOperacion(valor, tipo, idespecificacion, seleccionarAutomatico = false) {
  const lista = document.getElementById(`listaUsuarios_${tipo}_${idespecificacion}`);
  const errorBox = document.getElementById(`errorUsuario_${tipo}_${idespecificacion}`);

  try {
    lista.innerHTML = `
      <div class="p-3 text-muted small">
        <span class="spinner-border spinner-border-sm me-2"></span>
        Buscando usuario...
      </div>
    `;
    lista.classList.remove('d-none');

    const resp = await fetch(`${base_url}/plan_planeacionv1/buscarUsuariosOperacion`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ busqueda: valor })
    });

    const data = await resp.json();

    if (!data.status) {
      throw new Error(data.msg || 'Usuario no encontrado');
    }

    const usuarios = data.data || [];

    if (!usuarios.length) {
      throw new Error('No se encontraron usuarios.');
    }


    const usuarioExacto = usuarios.find(u => {
      return String(u.numcolaborador || '').toLowerCase() === valor.toLowerCase()
        || String(u.email_user || '').toLowerCase() === valor.toLowerCase();
    });

    if (usuarioExacto || seleccionarAutomatico) {
      seleccionarUsuarioOperacion(usuarioExacto || usuarios[0], tipo, idespecificacion);
      return;
    }

    lista.innerHTML = usuarios.map(u => `
      <div 
        class="autocomplete-usuario-item"
        onclick='seleccionarUsuarioOperacion(${JSON.stringify(u)}, "${tipo}", "${idespecificacion}")'
      >
        <div class="d-flex align-items-center gap-2">
          <div class="avatar-xs bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
            <i class="ri-user-line"></i>
          </div>

          <div class="flex-grow-1">
            <div class="autocomplete-usuario-name">
              ${u.nombre_completo || 'Sin nombre'}
            </div>
            <div class="autocomplete-usuario-meta">
              No. colaborador: <b>${u.numcolaborador || 'N/A'}</b>
              ${u.email_user ? ` · ${u.email_user}` : ''}
            </div>
          </div>
        </div>
      </div>
    `).join('');

  } catch (error) {
    lista.classList.add('d-none');
    errorBox.textContent = error.message;
    errorBox.classList.remove('d-none');
  }
}

function seleccionarUsuarioOperacion(usuario, tipo, idespecificacion) {
  console.log('escribiendo');
  const input = document.getElementById(`empleadoOperacion_${tipo}_${idespecificacion}`);
  const lista = document.getElementById(`listaUsuarios_${tipo}_${idespecificacion}`);

  document.getElementById(`usuarioOperacion_${tipo}_${idespecificacion}`).value = usuario.idusuario;
  document.getElementById(`numColaboradorOperacion_${tipo}_${idespecificacion}`).value = usuario.numcolaborador || '';

  document.getElementById(`nombreUsuario_${tipo}_${idespecificacion}`).textContent = usuario.nombre_completo || 'Sin nombre';
  document.getElementById(`numeroUsuario_${tipo}_${idespecificacion}`).textContent = usuario.numcolaborador || 'N/A';
  document.getElementById(`correoUsuario_${tipo}_${idespecificacion}`).textContent = usuario.email_user || 'Sin correo';

  input.value = `${usuario.nombre_completo} · ${usuario.numcolaborador || 'N/A'}`;

  document.getElementById(`previewUsuario_${tipo}_${idespecificacion}`).classList.remove('d-none');
  document.getElementById(`errorUsuario_${tipo}_${idespecificacion}`).classList.add('d-none');
  document.getElementById(`btnRegistrarOperacion_${tipo}_${idespecificacion}`).disabled = false;

  lista.classList.add('d-none');
  lista.innerHTML = '';
}

async function registrarOperacionRealizada(dataOperacion) {

  const tipo = dataOperacion.tipo_origen;
  const idespecificacion = dataOperacion.idespecificacion;

  const usuarioid = document.getElementById(
    `usuarioOperacion_${tipo}_${idespecificacion}`
  )?.value;

  const numcolaborador = document.getElementById(
    `numColaboradorOperacion_${tipo}_${idespecificacion}`
  )?.value;

  if (!usuarioid) {

    Swal.fire(
      'Atención',
      'Primero debes seleccionar un empleado válido.',
      'warning'
    );

    return;
  }

  try {

    const resp = await fetch(
      `${base_url}/plan_planeacionv1/registrarOperacionRealizada`,
      {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          ...dataOperacion,
          usuarioid,
          numcolaborador
        })
      }
    );

    const result = await resp.json();

    if (!result.status) {
      throw new Error(result.msg || 'No se pudo registrar la operación');
    }

    const row = document.getElementById(
      `rowOperacion_${tipo}_${idespecificacion}`
    );

    if (row) {
      row.classList.add('table-success');
    }

    setTimeout(() => {

      // =========================
      // ELIMINAR FILA ACTUAL
      // =========================
      if (row) {
        row.remove();
      }

      // =========================
      // VALIDAR SI QUEDAN FILAS
      // =========================
      const filasPendientes = document.querySelectorAll(
        '[id^="rowOperacion_"]'
      );

      // =========================
      // SI YA NO HAY FILAS
      // =========================
      if (filasPendientes.length === 0) {

        cerrarModalBootstrap('modalEspecificaciones');

        Swal.fire({
          icon: 'success',
          title: 'Operaciones completas',
          text: 'Todas las operaciones fueron registradas correctamente.',
          timer: 1300,
          showConfirmButton: false
        });

      }

    }, 500);

  } catch (error) {

    Swal.fire(
      'Error',
      error.message || 'Ocurrió un error inesperado.',
      'error'
    );

  }
}

/////////////////////////////////////////////////////////////////////////////////
// FIN  DE LA SECCIÓN DE ASIGNACIÓN DE OPERACIONES AL EMPLEADO
/////////////////////////////////////////////////////////////////////////////////


/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////
//ESTA SECCIÓN SON FUNCIONES PARA EL APARTADO DE APLICAR LA INSPECCIÓN DE CALIDAD 
/////////////////////////////////////////////////////////////////////////////////
/////////////////////////////////////////////////////////////////////////////////


async function modalCalidad(
  productoid,
  origenid,
  nombreEstacion = '',
  procesoTxt = '',
  cantidadPedido = 1,
  tipo = 'estacion',
  idordengeneral = 0,
  unidad_actual = ''
) {
  const modalEsp = document.getElementById('modalCalidad');
  const contenedor = document.getElementById('contenedorPuntosCalidad');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEsp);

  datosInspeccionCalidad = {
    productoid: parseInt(productoid, 10) || 0,
    estacionid: parseInt(origenid, 10) || 0,
    tipo,
    idordengeneral: parseInt(idordengeneral, 10) || 0,
    unidad_actual: unidad_actual || ''
  };

  contenedor.innerHTML = `
    <div class="text-center py-5">
      <div class="spinner-border text-primary"></div>
      <div class="mt-2 text-muted">Cargando inspección...</div>
    </div>
  `;

  modal.show();

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/getPuntosInspeccion`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        productoid,
        estacionid: origenid
      })
    });

    const data = await resp.json();

    if (!data.status) {
      throw new Error(data.msg || 'Error al cargar');
    }

    const rows = data.data || [];

    if (!rows.length) {
      contenedor.innerHTML = `
        <div class="alert alert-success text-center">
          <i class="ri-checkbox-circle-line fs-2 d-block mb-2"></i>
          No existen puntos de inspección registrados.
        </div>
      `;
      return;
    }

    renderizarPuntosCalidad(rows);

  } catch (error) {
    contenedor.innerHTML = `
      <div class="alert alert-danger">${error.message}</div>
    `;
  }
}


function renderizarPuntosCalidad(rows) {
  const contenedor = document.getElementById('contenedorPuntosCalidad');
  const zonas = {};

  rows.forEach(r => {
    if (!zonas[r.idzona]) {
      zonas[r.idzona] = {
        idzona: r.idzona,
        nombre: r.nombre_zona,
        referencia: r.referencia,
        puntos: []
      };
    }

    zonas[r.idzona].puntos.push(r);
  });

  let html = `<div class="row g-3">`;

  Object.values(zonas).forEach(zona => {
    html += `
      <div class="col-xl-6 col-lg-6 col-md-12">
        <div class="card border shadow-sm h-100">

          <div class="card-header bg-dark text-white">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <h5 class="mb-0 text-white">${zona.nombre}</h5>
                <small class="text-white-50">${zona.referencia || ''}</small>
              </div>

              <span class="badge bg-light text-dark">
                ${zona.puntos.length} puntos
              </span>
            </div>
          </div>

          <div class="card-body p-0">
            <div class="table-responsive">
              <table class="table table-bordered table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th width="40" class="text-center">No.</th>
                    <th>Punto a inspeccionar</th>
                    <th width="120" class="text-center">Inspección</th>
                    <th width="260">Observaciones</th>
                  </tr>
                </thead>
                <tbody>
    `;

    zona.puntos.forEach((p, i) => {
      html += `
        <tr 
          id="row_pdi_${p.idpuntopdi}"
          data-idpuntopdi="${p.idpuntopdi}"
          data-check-mexico="${p.check_mexico || 2}"
          data-check-i1="${p.check_i1 || 2}"
        >
          <td class="text-center fw-bold">${i + 1}</td>

          <td>
            <div class="fw-semibold">${p.punto}</div>
            <small class="text-muted">Validación de calidad</small>
          </td>

          <td class="text-center">
            <div class="d-flex justify-content-center gap-2">

              <button
                type="button"
                class="btn btn-sm btn-outline-success"
                id="btn_ok_${p.idpuntopdi}"
                onclick="marcarCheckPdi(${p.idpuntopdi}, 1)"
              >
                <i class="ri-check-line"></i>
              </button>

              <button
                type="button"
                class="btn btn-sm btn-outline-danger"
                id="btn_no_${p.idpuntopdi}"
                onclick="marcarCheckPdi(${p.idpuntopdi}, 2)"
              >
                <i class="ri-close-line"></i>
              </button>

            </div>

            <input 
              type="hidden" 
              id="resultado_${p.idpuntopdi}" 
              value=""
            >
          </td>

          <td>
            <textarea
              class="form-control form-control-sm"
              rows="2"
              id="obs_${p.idpuntopdi}"
              placeholder="Observación..."
              oninput="limpiarErrorObservacion(${p.idpuntopdi})"
            ></textarea>

            <small 
              class="text-danger d-none"
              id="error_obs_${p.idpuntopdi}"
            >
              La observación es obligatoria cuando el punto no es conforme.
            </small>
          </td>
        </tr>
      `;
    });

    html += `
                </tbody>
              </table>
            </div>
          </div>

        </div>
      </div>
    `;
  });

  html += `
    </div>

    <div class="position-sticky bottom-0  border-top p-3 mt-3 text-end shadow-sm">
      <button
        type="button"
        class="btn btn-success btn-lg"
        onclick="guardarInspeccionCalidad()"
      >
        <i class="ri-save-line me-1"></i>
        Guardar inspección
      </button>
    </div>
  `;

  contenedor.innerHTML = html;
}


function marcarCheckPdi(idpuntopdi, resultado) {
  const inputResultado = document.getElementById(`resultado_${idpuntopdi}`);
  const textarea = document.getElementById(`obs_${idpuntopdi}`);
  const btnOk = document.getElementById(`btn_ok_${idpuntopdi}`);
  const btnNo = document.getElementById(`btn_no_${idpuntopdi}`);
  const row = document.getElementById(`row_pdi_${idpuntopdi}`);

  if (!inputResultado) return;

  inputResultado.value = resultado;

  row?.classList.remove('table-danger', 'border', 'border-danger');

  btnOk?.classList.remove('btn-success');
  btnOk?.classList.add('btn-outline-success');

  btnNo?.classList.remove('btn-danger');
  btnNo?.classList.add('btn-outline-danger');

  if (resultado === 1) {
    btnOk?.classList.remove('btn-outline-success');
    btnOk?.classList.add('btn-success');

    textarea?.removeAttribute('required');
    textarea?.classList.remove('is-invalid');

    document.getElementById(`error_obs_${idpuntopdi}`)?.classList.add('d-none');
  }

  if (resultado === 2) {
    btnNo?.classList.remove('btn-outline-danger');
    btnNo?.classList.add('btn-danger');

    textarea?.setAttribute('required', 'required');
    textarea?.focus();
  }
}

window.marcarCheckPdi = marcarCheckPdi;
window.guardarInspeccionCalidad = guardarInspeccionCalidad;

function construirPayloadInspeccion() {
  const rows = document.querySelectorAll('#contenedorPuntosCalidad tr[data-idpuntopdi]');
  const detalles = [];
  const faltantes = [];
  const observacionesInvalidas = [];

  rows.forEach(row => {
    const idpuntopdi = row.dataset.idpuntopdi;
    const resultado = document.getElementById(`resultado_${idpuntopdi}`)?.value || '';
    const observacion = document.getElementById(`obs_${idpuntopdi}`)?.value.trim() || '';

    row.classList.remove('table-danger', 'border', 'border-danger');

    if (!resultado) {
      faltantes.push(idpuntopdi);
      row.classList.add('table-danger');
      return;
    }

    if (Number(resultado) === 2 && observacion === '') {
      observacionesInvalidas.push(idpuntopdi);

      const textarea = document.getElementById(`obs_${idpuntopdi}`);
      const error = document.getElementById(`error_obs_${idpuntopdi}`);

      textarea?.classList.add('is-invalid');
      error?.classList.remove('d-none');

      row.classList.add('table-danger');
      return;
    }

    detalles.push({
      idpuntopdi: Number(idpuntopdi),
      check_mexico: Number(row.dataset.checkMexico || 2),
      check_i1: Number(row.dataset.checkI1 || 2),
      resultado: Number(resultado),
      observacion
    });
  });

  return {
    payload: {
      ...datosInspeccionCalidad,
      detalles
    },
    faltantes,
    observacionesInvalidas
  };
}

async function guardarInspeccionCalidad() {
  const {
    payload,
    faltantes,
    observacionesInvalidas
  } = construirPayloadInspeccion();

  if (observacionesInvalidas.length > 0) {
    Swal.fire({
      icon: 'warning',
      title: 'Observaciones obligatorias',
      text: 'Tienes puntos marcados como no conformes sin observación.'
    });
    return;
  }

  if (faltantes.length > 0) {
    const result = await Swal.fire({
      icon: 'warning',
      title: 'Te faltan puntos por evaluar',
      text: '¿Deseas continuar guardando solo los puntos evaluados?',
      showCancelButton: true,
      confirmButtonText: 'Sí, continuar',
      cancelButtonText: 'No, revisar',
      confirmButtonColor: '#198754',
      cancelButtonColor: '#dc3545'
    });

    if (!result.isConfirmed) {
      return;
    }
  }

  if (!payload.detalles.length) {
    Swal.fire({
      icon: 'warning',
      title: 'Sin puntos evaluados',
      text: 'Debes evaluar al menos un punto para guardar la inspección.'
    });
    return;
  }

  try {
    const resp = await fetch(`${base_url}/plan_planeacionv1/guardarInspeccionCalidad`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(payload)
    });

    const data = await resp.json();

    if (!data.status) {
      throw new Error(data.msg || 'No se pudo guardar la inspección');
    }

    Swal.fire({
      icon: 'success',
      title: 'Inspección guardada',
      text: data.msg || 'La inspección se registró correctamente.'
    });

    const modalElement = document.getElementById('modalCalidad');
    const modalInstance = bootstrap.Modal.getInstance(modalElement);

    if (modalInstance) {
      modalInstance.hide();
    }

  } catch (error) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: error.message
    });
  }
}

///////////////////////////////////////////////
// FIN DE LA SECCIÓN DE CALIDAD 
///////////////////////////////////////////////


///////////////////////////////////////////////
///////////////////////////////////////////////
// FUNCIONES PARA LA ASIGNACIÓN DE VINES
///////////////////////////////////////////////
///////////////////////////////////////////////


async function openModalIdentificacion(
  productoid,
  estacionid,
  nombreEstacion = '',
  procesoTxt = '',
  cantidadPedido = 1,
  idorden,
  numot,
  numbase
) {

  productoid = parseInt(productoid, 10) || 0;
  estacionid = parseInt(estacionid, 10) || 0;
  idorden = parseInt(idorden, 10) || 0;

  console.log('ID ORDEN:', idorden);

  const modalIdentiEl = document.getElementById('modalIdentificacion');

  if (!modalIdentiEl) {
    console.error('No existe el modal con id="modalIdentificacion"');
    return;
  }

  const modal = bootstrap.Modal.getOrCreateInstance(modalIdentiEl);

  document.getElementById('titleEstacionIdenti').textContent =
    nombreEstacion || 'Estación';

  document.getElementById('titleProcesoIdenti').textContent =
    procesoTxt || 'Proceso';

  document.getElementById('numSubOrdenIdenti').textContent =
    numot || '-';


  // =========================================
  // INPUT HIDDEN ORDEN
  // =========================================

  const inputOrden = document.getElementById('ordenid');

  if (!inputOrden) {
    console.error('No existe el input hidden con id="ordenid"');
    return;
  }

  // ESTA LÍNEA TE FALTABA
  inputOrden.value = String(idorden);

  console.log('VALOR GUARDADO EN HIDDEN:', inputOrden.value);


  // =========================================
  // OTROS INPUTS
  // =========================================

  const inputCalidadOrden = document.getElementById('calidad_idorden');

  if (inputCalidadOrden) {
    inputCalidadOrden.value = String(idorden);
  }

  const inputCalidadNumot = document.getElementById('calidad_numot');

  if (inputCalidadNumot) {
    inputCalidadNumot.value = String(numot || '');
  }

  const inputEstacion = document.getElementById('estacion_id');

  if (inputEstacion) {
    inputEstacion.value = String(estacionid);
  }


  // =========================================
  // RESET FORM
  // =========================================

  setModalModoLectura(false);

  const box = document.getElementById('boxInfoVinAsignado');

  if (box) {
    box.classList.add('d-none');
    box.innerHTML = '';
  }

  const selectVin = document.getElementById('selectVinIdenti');

  if (selectVin) {
    selectVin.innerHTML = `
      <option value="" selected disabled>
        — Selecciona —
      </option>
    `;
  }

  const inputMotor = document.getElementById('inputMotorIdenti');

  if (inputMotor) {
    inputMotor.value = '';
  }

  const inputVinOrigen = document.getElementById('inputVinOrigenIdenti');

  if (inputVinOrigen) {
    inputVinOrigen.value = '';
  }

  const inputTransmision = document.getElementById('inputTransmisionIdenti');

  if (inputTransmision) {
    inputTransmision.value = '';
  }


  // =========================================
  // CARGAR VINS
  // =========================================

  await cargarVinesDisponibles(numbase, idorden);

  modal.show();
}

function setModalModoLectura(isReadOnly) {
  const sel = document.getElementById('selectVinIdenti');
  const motor = document.getElementById('inputMotorIdenti');
  const btnGuardar = document.getElementById('btnGuardarIdenti');

  if (sel) sel.disabled = !!isReadOnly;
  if (motor) motor.disabled = !!isReadOnly;

  if (btnGuardar) {
    btnGuardar.classList.toggle('d-none', !!isReadOnly);
  }
}

async function cargarVinesDisponibles(numbase, inputOrden) {
  try {
    numbase = String(numbase || '').trim();
    inputOrden = parseInt(inputOrden, 10) || 0;

    const sel = document.getElementById('selectVinIdenti');
    const form = document.getElementById('formIdentificacionUnidad');
    const box = document.getElementById('boxInfoVinAsignado');
    const btnGuardar = document.getElementById('btnGuardarIdenti');

    if (!sel) {
      console.error('No existe el select con id="selectVinIdenti"');
      return;
    }

    if (form) form.classList.remove('d-none');
    if (btnGuardar) btnGuardar.classList.remove('d-none');

    if (box) {
      box.classList.add('d-none');
      box.innerHTML = '';
    }

    sel.disabled = false;
    sel.innerHTML = `<option value="" selected disabled>— Selecciona —</option>`;

    if (!numbase) return;

    if (!inputOrden) {
      console.error('No se recibió el id de la orden para consultar VIN.');
      return;
    }

    const parametro = `${numbase},${inputOrden}`;
    const url = `${base_url}/plan_planeacionv1/getVinesDisponibles/${encodeURIComponent(parametro)}`;

    const res = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    const json = await res.json();

    if (!json.status) {
      if (
        json.msg &&
        json.msg.toLowerCase().includes('vin ya fue asignado')
      ) {
        if (form) form.classList.add('d-none');
        if (btnGuardar) btnGuardar.classList.add('d-none');

        if (box) {
          box.classList.remove('d-none');
          box.classList.remove('alert-info', 'alert-danger', 'alert-warning');
          box.classList.add('alert-success');

          box.innerHTML = `
            <div class="d-flex align-items-start gap-2">
              <div class="avatar-sm bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                <i class="ri-check-line fs-20"></i>
              </div>

              <div>
                <h5 class="mb-1 text-success">
                  VIN ya asignado
                </h5>
                <div class="text-muted">
                  ${json.msg}
                </div>
              </div>
            </div>
          `;
        }

        return;
      }

      sel.innerHTML = `
        <option value="" selected disabled>
          ${json.msg || '— Sin VIN disponibles —'}
        </option>
      `;

      return;
    }

    const vines = Array.isArray(json.data) ? json.data : [];

    if (vines.length === 0) {
      sel.innerHTML = `<option value="" selected disabled>— Sin VIN disponibles —</option>`;
      return;
    }

    vines.forEach(v => {
      const opt = document.createElement('option');
      opt.value = v.vin;
      opt.textContent = v.vin;
      opt.dataset.id = v.id;
      sel.appendChild(opt);
    });

  } catch (err) {
    console.error(err);
    Swal.fire('Error', 'No se pudieron cargar los VIN disponibles.', 'error');
  }
}

document.addEventListener('click', async (e) => {
  const btn = e.target.closest('#btnGuardarIdenti');
  if (!btn) return;

  await guardarAsignacionVin();
});


async function guardarAsignacionVin() {
  try {

    const ordenId = parseInt(document.getElementById('ordenid')?.value || '0', 10) || 0;


    const sel = document.getElementById('selectVinIdenti');

    const vin = (sel?.value || '').trim();

    const numeroMotor = (
      document.getElementById('inputMotorIdenti')?.value || ''
    ).trim();

    const vinOrigen = (
      document.getElementById('inputVinOrigenIdenti')?.value || ''
    ).trim();

    const numeroTransmision = (
      document.getElementById('inputTransmisionIdenti')?.value || ''
    ).trim();

    const selectedOpt = sel?.options?.[sel.selectedIndex];

    const numeroSerieId = parseInt(
      selectedOpt?.dataset?.id || '0',
      10
    ) || 0;


    if (!ordenId) {
      Swal.fire('Atención', 'No se detectó la Orden de Trabajo.', 'warning');
      return;
    }

    if (!vin) {
      Swal.fire('Atención', 'Selecciona un VIN para poder asignar.', 'warning');
      sel?.focus();
      return;
    }

    if (!numeroMotor) {
      Swal.fire('Atención', 'Ingresa el número de motor.', 'warning');
      document.getElementById('inputMotorIdenti')?.focus();
      return;
    }


    if (!numeroSerieId) {
      Swal.fire(
        'Atención',
        'El VIN seleccionado no contiene el ID de serie.',
        'warning'
      );
      return;
    }


    // =========================
    // LOADING BTN
    // =========================

    const btn = document.getElementById('btnGuardarIdenti');

    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `
        <i class="ri-loader-4-line ri-spin me-1"></i>
        Guardando...
      `;
    }


    // =========================
    // REQUEST
    // =========================

    const url = `${base_url}/plan_planeacionv1/setVinAsignacion`;

    const fd = new FormData();

    fd.append('orden_trabajo_id', String(ordenId));
    fd.append('vin', vin);
    fd.append('numero_serie_id', String(numeroSerieId));
    fd.append('numero_motor', numeroMotor);

    // NUEVOS CAMPOS
    fd.append('vin_origen', vinOrigen);
    fd.append('numero_transmision', numeroTransmision);


    const res = await fetch(url, {
      method: 'POST',
      body: fd,
      headers: {
        'Accept': 'application/json'
      }
    });

    const json = await res.json();


    if (!json.status) {
      Swal.fire(
        'Atención',
        json.msg || 'No se pudo asignar el VIN.',
        'warning'
      );
      return;
    }


    Swal.fire(
      'Listo',
      json.msg || 'VIN asignado correctamente.',
      'success'
    );


    // =========================
    // ACTUALIZAR BOTONES
    // =========================

    if (ordenId) {

      const btnAsignar = document.querySelector(
        `.btnIdentificacionUnidad[data-idorden="${ordenId}"]`
      );

      const btnVer = document.querySelector(
        `.btnVerVinUnidad[data-idorden="${ordenId}"]`
      );

      if (btnAsignar) {
        btnAsignar.classList.add('d-none');
      }

      if (btnVer) {
        btnVer.classList.remove('d-none');
      }
    }


    // =========================
    // CERRAR MODAL
    // =========================

    const modalEl = document.getElementById('modalIdentificacion');

    const modal = bootstrap.Modal.getInstance(modalEl);

    modal?.hide();


  } catch (err) {

    console.error(err);

    Swal.fire(
      'Error',
      'Ocurrió un error al guardar la asignación.',
      'error'
    );

  } finally {

    const btn = document.getElementById('btnGuardarIdenti');

    if (btn) {
      btn.disabled = false;
      btn.innerHTML = `
        <i class="ri-save-3-line me-1"></i>
        Guardar asignación
      `;
    }
  }
}


// =========================
// FUNCIONES PARA REINCOPOPRRAR UNIDADES A PRODUCCIÓN
// =========================


async function reincorporarUnidadFueraLinea(idfuera, unidad, estacion) {

  const confirmacion = await Swal.fire({
    icon: 'question',
    title: '¿Reincorporar unidad?',
    text: `¿Estás seguro de reincorporar la unidad ${unidad} a producción en la estación ${estacion}?`,
    showCancelButton: true,
    confirmButtonText: 'Sí, reincorporar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#0ab39c',
    cancelButtonColor: '#f06548',
    reverseButtons: true
  });

  if (!confirmacion.isConfirmed) return;

  try {

    const resp = await fetch(`${base_url}/plan_planeacionv1/reincorporarUnidadFueraLinea`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        idfuera: idfuera
      })
    });

    const data = await resp.json();

    if (!data.status) {
      Swal.fire({
        icon: 'warning',
        title: 'Validación',
        text: data.msg || 'No se pudo reincorporar la unidad.',
        confirmButtonText: 'Entendido',
        background: '#0f172a',
        color: '#fff'
      });
      return;
    }

    Swal.fire({
      icon: 'success',
      title: 'Correcto',
      text: data.msg || 'Unidad reincorporada correctamente.',
      timer: 1600,
      showConfirmButton: false,
      background: '#0f172a',
      color: '#fff'
    });

    // setTimeout(() => {
    //   location.reload();
    // }, 900);

  } catch (err) {
    Swal.fire({
      icon: 'error',
      title: 'Error',
      text: err.message || 'Ocurrió un error al reincorporar la unidad.',
      confirmButtonText: 'Entendido',
      background: '#0f172a',
      color: '#fff'
    });
  }
}