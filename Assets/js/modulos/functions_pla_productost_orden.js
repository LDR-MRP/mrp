document.addEventListener('DOMContentLoaded', function () {

  const tbodyListados = document.getElementById('tbodyListados');
  const filterSearch = document.getElementById('filterSearch');
  const filterDesde = document.getElementById('filterDesde');
  const filterHasta = document.getElementById('filterHasta');
  const filterPrioridad = document.getElementById('filterPrioridad');
  const btnRefrescarListado = document.getElementById('btnRefrescarListado');
  const buscarUnidadesFinalizadas = document.getElementById('buscarUnidadesFinalizadas');

  let listadoActual = [];

  function getListadoEndpoint(tipo) {
    if (tipo === 'PENDIENTE') {
      return base_url + '/pla_productost/getPendientes';
    }

    if (tipo === 'FINALIZADA') {
      return base_url + '/pla_productost/getFinalizadas';
    }

    if (tipo === 'EN_PROCESO') {
      return base_url + '/pla_productost/getEnProceso';
    }

    return base_url + '/pla_productost/getTodas';
  }

  async function fetchJson(url) {
    const response = await fetch(url, {
      method: 'GET',
      headers: {
        'Accept': 'application/json'
      }
    });

    if (!response.ok) {
      throw new Error('Error HTTP ' + response.status);
    }

    return await response.json();
  }

  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text ?? '';
    return div.innerHTML;
  }

  function formatearFecha(fecha) {
    if (!fecha) return '';

    return String(fecha).substring(0, 10);
  }

  function normalizarEstatus(row) {
    const fase = parseInt(row.fase ?? 0);

    if (fase === 2) return 'PENDIENTE';
    if (fase === 3) return 'EN_PROCESO';
    if (fase === 5) return 'FINALIZADA';

    return row.estatus ?? '';
  }

  function badgeEstatus(estatus) {
    estatus = String(estatus || '').trim();

    if (estatus === 'PENDIENTE') {
      return `<span class="badge bg-warning-subtle text-warning border">Pendiente</span>`;
    }

    if (estatus === 'EN_PROCESO') {
      return `<span class="badge bg-primary-subtle text-primary border">En proceso</span>`;
    }

    if (estatus === 'FINALIZADA') {
      return `<span class="badge bg-success-subtle text-success border">Finalizada</span>`;
    }

    return `<span class="badge bg-secondary-subtle text-secondary border">Sin estatus</span>`;
  }

  function badgePrioridad(prioridad) {
    prioridad = String(prioridad || '').trim().toUpperCase();

    if (prioridad === 'CRITICA' || prioridad === 'CRÍTICA') {
      return `<span class="badge bg-danger">Crítica</span>`;
    }

    if (prioridad === 'ALTA') {
      return `<span class="badge bg-warning text-dark">Alta</span>`;
    }

    if (prioridad === 'MEDIA') {
      return `<span class="badge bg-info text-dark">Media</span>`;
    }

    if (prioridad === 'BAJA') {
      return `<span class="badge bg-secondary">Baja</span>`;
    }

    if (prioridad === 'PROTOTIPO') {
      return `<span class="badge bg-dark">Prototipo</span>`;
    }

    return `<span class="badge bg-light text-dark border">${escapeHtml(prioridad || 'N/A')}</span>`;
  }

  function normalizarRows(data) {
    if (!Array.isArray(data)) return [];

    return data.map(row => ({
      id: row.idplaneacion ?? row.id ?? '',
      folio: row.num_orden ?? row.folio ?? '',
      producto: row.descripcion_producto ?? row.producto ?? row.nombre_producto ?? '',
      prioridad: row.prioridad ?? '',
      cantidad: row.cantidad ?? 0,
      inicio: row.fecha_inicio ?? row.inicio ?? '',
      requerida: row.fecha_requerida ?? row.requerida ?? '',
      estatus: normalizarEstatus(row),
      fase: row.fase ?? ''
    }));
  }

  function aplicarFiltros(rows) {
    const texto = String(filterSearch?.value || '').trim().toLowerCase();
    const desde = filterDesde?.value || '';
    const hasta = filterHasta?.value || '';

    return rows.filter(row => {
      const folio = String(row.folio || '').toLowerCase();
      const producto = String(row.producto || '').toLowerCase();
      const fechaInicio = formatearFecha(row.inicio);

      if (texto && !folio.includes(texto) && !producto.includes(texto)) {
        return false;
      }

      if (desde && fechaInicio && fechaInicio < desde) {
        return false;
      }

      if (hasta && fechaInicio && fechaInicio > hasta) {
        return false;
      }

      return true;
    });
  }

  function pintarTabla(rows) {
    if (!tbodyListados) return;

    tbodyListados.innerHTML = '';

    if (!rows.length) {
      tbodyListados.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-muted py-4">
            No hay órdenes de trabajo para mostrar.
          </td>
        </tr>
      `;
      return;
    }

    rows.forEach(row => {
      const tr = document.createElement('tr');

      tr.innerHTML = `
        <td class="fw-semibold">${escapeHtml(row.folio)}</td>
        <td>${escapeHtml(row.producto)}</td>
        <td>${badgePrioridad(row.prioridad)}</td>
        <td>${escapeHtml(row.cantidad)}</td>
        <td>${escapeHtml(formatearFecha(row.inicio))}</td>
        <td>${escapeHtml(formatearFecha(row.requerida))}</td>
        <td>${badgeEstatus(row.estatus)}</td>
        <td class="text-end">
          <a class="btn btn-outline-primary btn-sm me-1"
             href="${base_url}/pla_productost/orden/${encodeURIComponent(row.folio)}">
            <i class="ri-eye-line"></i>
            <span class="d-none d-md-inline">Ver</span>
          </a>
        </td>
      `;

      tbodyListados.appendChild(tr);
    });
  }

  async function renderListado(tipo = 'TODAS') {
    if (!tbodyListados) return;

    tbodyListados.innerHTML = `
      <tr>
        <td colspan="8" class="text-center text-muted py-4">
          Cargando órdenes de trabajo...
        </td>
      </tr>
    `;

    try {
      const url = getListadoEndpoint(tipo);
      const data = await fetchJson(url);

      listadoActual = normalizarRows(data);
      pintarTabla(aplicarFiltros(listadoActual));

    } catch (error) {
      console.error(error);

      tbodyListados.innerHTML = `
        <tr>
          <td colspan="8" class="text-center text-danger py-4">
            Error al cargar órdenes de trabajo: ${escapeHtml(error.message)}
          </td>
        </tr>
      `;
    }
  }

  function refrescarConFiltrosCliente() {
    pintarTabla(aplicarFiltros(listadoActual));
  }

  function filtrarUnidadesFinalizadas() {
    const texto = String(buscarUnidadesFinalizadas?.value || '')
      .trim()
      .toLowerCase();

    const rows = document.querySelectorAll('.row-unidad-finalizada');
    let visibles = 0;

    rows.forEach(row => {
      const contenido = String(row.getAttribute('data-search') || '')
        .trim()
        .toLowerCase();

      const mostrar = texto === '' || contenido.includes(texto);

      row.style.display = mostrar ? '' : 'none';

      if (mostrar) visibles++;
    });

    const mensajeSinResultados = document.getElementById('mensajeSinUnidadesFinalizadas');

    if (mensajeSinResultados) {
      mensajeSinResultados.style.display = visibles === 0 ? '' : 'none';
    }
  }

  if (filterPrioridad) {
    filterPrioridad.addEventListener('change', function () {
      renderListado(this.value || 'TODAS');
    });
  }

  if (btnRefrescarListado) {
    btnRefrescarListado.addEventListener('click', function () {
      renderListado(filterPrioridad?.value || 'TODAS');
    });
  }

  if (filterSearch) {
    filterSearch.addEventListener('input', refrescarConFiltrosCliente);
  }

  if (filterDesde) {
    filterDesde.addEventListener('change', refrescarConFiltrosCliente);
  }

  if (filterHasta) {
    filterHasta.addEventListener('change', refrescarConFiltrosCliente);
  }

  if (buscarUnidadesFinalizadas) {
    buscarUnidadesFinalizadas.addEventListener('input', filtrarUnidadesFinalizadas);
    buscarUnidadesFinalizadas.addEventListener('keyup', filtrarUnidadesFinalizadas);
  }

  renderListado('TODAS');
  filtrarUnidadesFinalizadas();



  const btnVolverHome2 = document.getElementById('btnVolverHome2');

if (btnVolverHome2) {

    btnVolverHome2.addEventListener('click', function () {

        window.location.href = base_url + '/pla_productost';

    });

}

});