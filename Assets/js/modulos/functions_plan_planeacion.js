

  // --------------------------
  //  VISTAS
  // --------------------------
  const viewHome    = document.getElementById('viewHome');
  const viewNueva   = document.getElementById('viewNueva');
  const viewListado = document.getElementById('viewListado');

  // --------------------------
  //  BOTONES SIDE
  // --------------------------
  const btnNuevaPlaneacion = document.getElementById('btnNuevaPlaneacion');
  const btnPendientes  = document.getElementById('btnPendientes');
  const btnFinalizadas = document.getElementById('btnFinalizadas');
  const btnCanceladas  = document.getElementById('btnCanceladas');

  // volver
  const btnVolverHome1 = document.getElementById('btnVolverHome1');
  const btnVolverHome2 = document.getElementById('btnVolverHome2');

  // nueva acciones
  const btnCancelarNueva = document.getElementById('btnCancelarNueva');
  const btnGuardarPlaneacion = document.getElementById('btnGuardarPlaneacion');

  // listado
  const badgeListado     = document.getElementById('badgeListado');
  const breadcrumbListado= document.getElementById('breadcrumbListado');
  const listadoTitulo    = document.getElementById('listadoTitulo');
  const listadoSubtitulo = document.getElementById('listadoSubtitulo');
  const tbodyListados    = document.getElementById('tbodyListados');
  const btnRefrescarListado = document.getElementById('btnRefrescarListado');

  // filtros (demo)
  const filterSearch    = document.getElementById('filterSearch');
  const filterDesde     = document.getElementById('filterDesde');
  const filterHasta     = document.getElementById('filterHasta');
  const filterPrioridad = document.getElementById('filterPrioridad');

  let currentListado = null; // 'PENDIENTE' | 'FINALIZADA' | 'CANCELADA'

  function hideAll() {
    viewHome.classList.add('d-none');
    viewNueva.classList.add('d-none');
    viewListado.classList.add('d-none');
  }

  function setActiveNav(activeBtn) {
    [btnPendientes, btnFinalizadas, btnCanceladas].forEach(b => b.classList.remove('active'));
    if (activeBtn) activeBtn.classList.add('active');
  }

  function goHome() {
    hideAll();
    setActiveNav(null);
    viewHome.classList.remove('d-none');
  }

  function goNueva() {
    hideAll();
    setActiveNav(null);
    viewNueva.classList.remove('d-none');
  }

  function goListado(tipo) {
    hideAll();
    viewListado.classList.remove('d-none');
    currentListado = tipo;

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

    renderListado(tipo);
  }

  // --------------------------
  //  DATA DEMO (reemplaza con AJAX)
  // --------------------------
  function getData(tipo) {
    const rows = [
      { id: 21, folio:'OP-00021', producto:'Soporte motor', prioridad:'ALTA', cantidad:120, inicio:'2025-12-26', requerida:'2025-12-29', estatus:'PENDIENTE' },
      { id: 22, folio:'OP-00022', producto:'Bracket transmisión', prioridad:'CRITICA', cantidad:50, inicio:'2025-12-26', requerida:'2025-12-27', estatus:'PENDIENTE' },
      { id: 15, folio:'OP-00015', producto:'Base alternador', prioridad:'MEDIA', cantidad:80, inicio:'2025-12-20', requerida:'2025-12-22', estatus:'FINALIZADA' },
      { id: 12, folio:'OP-00012', producto:'Soporte radiador', prioridad:'BAJA', cantidad:200, inicio:'2025-12-18', requerida:'2025-12-25', estatus:'CANCELADA' }
    ];

    return rows.filter(r => r.estatus === tipo);
  }

  function badgePrioridad(p) {
    if (p === 'CRITICA') return '<span class="badge bg-danger-subtle text-danger border">CRÍTICA</span>';
    if (p === 'ALTA')    return '<span class="badge bg-warning-subtle text-warning border">ALTA</span>';
    if (p === 'MEDIA')   return '<span class="badge bg-primary-subtle text-primary border">MEDIA</span>';
    if (p === 'BAJA')    return '<span class="badge bg-secondary-subtle text-secondary border">BAJA</span>';
    return `<span class="badge bg-light text-dark border">${p}</span>`;
  }

  function badgeEstatus(e) {
    if (e === 'PENDIENTE')  return '<span class="badge bg-warning-subtle text-warning border">PENDIENTE</span>';
    if (e === 'FINALIZADA') return '<span class="badge bg-success-subtle text-success border">FINALIZADA</span>';
    if (e === 'CANCELADA')  return '<span class="badge bg-danger-subtle text-danger border">CANCELADA</span>';
    return `<span class="badge bg-light text-dark border">${e}</span>`;
  }

  function applyClientFilters(rows) {
    const q = (filterSearch.value || '').trim().toLowerCase();
    const d1 = filterDesde.value || '';
    const d2 = filterHasta.value || '';
    const pr = filterPrioridad.value || '';

    return rows.filter(r => {
      if (q) {
        const hit = (r.folio + ' ' + r.producto).toLowerCase().includes(q);
        if (!hit) return false;
      }
      if (pr && r.prioridad !== pr) return false;
      if (d1 && r.inicio < d1) return false;
      if (d2 && r.inicio > d2) return false;
      return true;
    });
  }

  function renderListado(tipo) {
    let rows = getData(tipo);
    rows = applyClientFilters(rows);

    tbodyListados.innerHTML = '';

    if (!rows.length) {
      tbodyListados.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-muted py-4">
            No hay registros para este filtro.
          </td>
        </tr>`;
      return;
    }

    rows.forEach(r => {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td class="fw-semibold">${r.folio}</td>
        <td>${r.producto}</td>
        <td>${badgePrioridad(r.prioridad)}</td>
        <td>${r.cantidad}</td>
        <td>${r.inicio}</td>
        <td>${r.requerida}</td>
        <td>${badgeEstatus(r.estatus)}</td>
        <td class="text-end">
          <button type="button" class="btn btn-outline-primary btn-sm me-1" data-action="ver" data-id="${r.id}">
            <i class="ri-eye-line"></i> <span class="d-none d-md-inline">Ver</span>
          </button>
          <button type="button" class="btn btn-outline-warning btn-sm me-1" data-action="editar" data-id="${r.id}">
            <i class="ri-edit-2-line"></i> <span class="d-none d-md-inline">Editar</span>
          </button>
          <button type="button" class="btn btn-outline-danger btn-sm" data-action="eliminar" data-id="${r.id}">
            <i class="ri-delete-bin-6-line"></i> <span class="d-none d-md-inline">Eliminar</span>
          </button>
        </td>
      `;
      tbodyListados.appendChild(tr);
    });
  }

  // --------------------------
  //  EVENTOS
  // --------------------------
  btnNuevaPlaneacion.addEventListener('click', goNueva);

  btnPendientes.addEventListener('click', () => goListado('PENDIENTE'));
  btnFinalizadas.addEventListener('click', () => goListado('FINALIZADA'));
  btnCanceladas.addEventListener('click', () => goListado('CANCELADA'));

  btnVolverHome1.addEventListener('click', goHome);
  btnVolverHome2.addEventListener('click', goHome);
  btnCancelarNueva.addEventListener('click', goHome);

  btnRefrescarListado.addEventListener('click', () => {
    if (!currentListado) return;
    renderListado(currentListado);
  });

  [filterSearch, filterDesde, filterHasta, filterPrioridad].forEach(el => {
    el.addEventListener('input', () => {
      if (!currentListado) return;
      renderListado(currentListado);
    });
    el.addEventListener('change', () => {
      if (!currentListado) return;
      renderListado(currentListado);
    });
  });

  // Acciones tabla
  document.addEventListener('click', (e) => {
    const b = e.target.closest('button[data-action]');
    if (!b) return;

    const action = b.getAttribute('data-action');
    const id = b.getAttribute('data-id');

    if (action === 'ver') {
      alert('Ver (demo) ID: ' + id);
    }
    if (action === 'editar') {
      alert('Editar (demo) ID: ' + id);
    }
    if (action === 'eliminar') {
      const ok = confirm('¿Eliminar planeación ID ' + id + '?');
      if (ok) alert('Eliminada (demo) ID: ' + id);
    }
  });

  // Guardar planeación (demo)
  btnGuardarPlaneacion.addEventListener('click', () => {
    // Aquí conectas tu AJAX real.
    // Valida producto y ruta antes de guardar.
    alert('Guardar Planeación (demo)');
  });

  // Default
  goHome();
