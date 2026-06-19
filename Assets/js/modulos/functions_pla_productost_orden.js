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




      document.addEventListener('click', async function (e) {

        const btn = e.target.closest('.btnPdfUnidad');

        if (!btn) return;

        e.preventDefault();

        const numUnidad = btn.dataset.unidad;

        //  console.log('Unidad PDF:', numUnidad);

        if (!numUnidad) {
            alert('No se encontró el número de unidad.');
            return;
        }

        const formData = new FormData();
        formData.append('num_unidad', numUnidad);

        const response = await fetch(base_url + '/pla_productost/getUnidadPdf', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        console.log(result);

        if (!result.status) {
            alert(result.msg || 'No se pudo generar el PDF.');
            return;
        }

        generarPdfUnidad(result.data, result.url_qr);
    });




function generarPdfUnidad(u, urlQr) {

    const unidad = u.num_unidad || '';
    const ordenTrabajo = u.num_orden || 'N/A';
    const producto = u.producto || '';
    const vin = u.vin_asignado || 'Sin VIN asignado';
    const planta = u.nombre_planta || '';
    const fechaFinProduccion = u.fecha_fin_real || u.fecha_unidad_terminada || '';
    const numeroMotor = u.numero_motor || 'N/A';
    const numeroTransmision = u.numero_transmision || 'N/A';

    const fechaImpresion = new Date().toLocaleString('es-MX', {
        timeZone: 'America/Mexico_City',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    const docDefinition = {
        pageSize: 'LETTER',
        pageMargins: [32, 20, 32, 20],

        content: [
            {
                canvas: [
                    {
                        type: 'rect',
                        x: 0,
                        y: 0,
                        w: 545,
                        h: 735,
                        r: 8,
                        lineWidth: 1.2,
                        lineColor: '#999999'
                    }
                ],
                absolutePosition: { x: 25, y: 20 }
            },

            logoLdr(),

            {
                text: 'REPORTE DE UNIDAD TERMINADA',
                alignment: 'center',
                fontSize: 25,
                bold: true,
                color: '#222222',
                margin: [0, 6, 0, 4]
            },
            {
                text: 'CERTIFICADO DE UNIDAD TERMINADA',
                alignment: 'center',
                fontSize: 12,
                characterSpacing: 1.5,
                color: '#444444',
                margin: [0, 0, 0, 22]
            },

            sectionTitle('INFORMACIÓN GENERAL DE LA UNIDAD'),

            {
                table: {
                    widths: ['40%', '60%'],
                    body: [
                        [label('ORDEN DE TRABAJO'), valueBold(ordenTrabajo)],
                        [label('UNIDAD'), valueBold(unidad)],
                        [label('PRODUCTO'), valueBold(producto)],
                        [label('VIN ASIGNADO'), valueBold(vin)],

                        [{ text: '', colSpan: 2, border: [false, false, false, true], margin: [0, 3, 0, 3] }, {}],

                        [label('PLANTA DE ENSAMBLE'), value(planta)],
                        [label('FECHA DE FINALIZACIÓN DE PRODUCCIÓN'), value(fechaFinProduccion)],
                        [label('NÚMERO DE MOTOR'), value(numeroMotor)],
                        [label('NÚMERO DE TRANSMISIÓN'), value(numeroTransmision)],

                        [{ text: '', colSpan: 2, border: [false, false, false, true], margin: [0, 3, 0, 3] }, {}],

                        [labelBold('ESTADO ACTUAL'), {
                            text: 'UNIDAD FINALIZADA Y LIBERADA',
                            bold: true,
                            fontSize: 13,
                            color: '#111111',
                            margin: [0, 5, 0, 5]
                        }]
                    ]
                },
                layout: tableLayout(),
                margin: [38, 0, 38, 18]
            },

            sectionTitle('QR DE TRAZABILIDAD'),

            {
                qr: urlQr,
                fit: 120,
                alignment: 'center',
                margin: [0, 12, 0, 8]
            },

       {
    text: 'Este documento fue generado automáticamente por el Sistema MRP de Producción y contiene la información registrada durante el proceso de ensamble y liberación de la unidad. Los datos aquí presentados corresponden al estado de la unidad al momento de su emisión.',
    alignment: 'center',
    fontSize: 8,
    italics: true,
    color: '#555555',
    margin: [42, 0, 42, 12]
},

            {
                canvas: [
                    {
                        type: 'line',
                        x1: 20,
                        y1: 0,
                        x2: 525,
                        y2: 0,
                        lineWidth: 1,
                        lineColor: '#AAAAAA'
                    }
                ],
                margin: [0, 4, 0, 6]
            },

            {
                columns: [
                    {
                        width: '33%',
                        text: 'LDR Solutions México\nSistema MRP de Producción',
                        fontSize: 8.5,
                        color: '#444444'
                    },
                    {
                        width: '33%',
                        text: 'Documento generado\nautomáticamente',
                        fontSize: 8.5,
                        color: '#444444',
                        alignment: 'center'
                    },
                    {
                        width: '33%',
                        text: 'Fecha de impresión\n' + fechaImpresion + ' hrs',
                        fontSize: 8.5,
                        color: '#444444',
                        alignment: 'right'
                    }
                ],
                margin: [35, 0, 35, 0]
            }
        ],

        defaultStyle: {
            font: 'Roboto'
        }
    };

    pdfMake.createPdf(docDefinition).download('Unidad_' + unidad + '.pdf');
}

const LOGO_LDR_BASE64 = 'data:image/png;base64,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';

function logoLdr() {
    if (typeof LOGO_LDR_BASE64 !== 'undefined' && LOGO_LDR_BASE64) {
        return {
            image: LOGO_LDR_BASE64,
            width: 95,
            alignment: 'center',
            margin: [0, 5, 0, 8]
        };
    }

    return {
        stack: [
            {
                text: 'LDR',
                alignment: 'center',
                fontSize: 36,
                bold: true,
                color: '#555555',
                margin: [0, 5, 0, 0]
            },
            {
                text: 'SOLUTIONS',
                alignment: 'center',
                fontSize: 12,
                characterSpacing: 4,
                color: '#666666',
                margin: [0, -5, 0, 8]
            }
        ]
    };
}


function sectionTitle(text) {
    return {
        table: {
            widths: ['100%'],
            body: [[{
                text: text,
                bold: true,
                fontSize: 13,
                color: '#222222',
                fillColor: '#F3F3F3',
                margin: [14, 7, 14, 7],
                border: [true, true, true, true]
            }]]
        },
        layout: {
            hLineColor: () => '#AAAAAA',
            vLineColor: () => '#AAAAAA',
            hLineWidth: () => 0.8,
            vLineWidth: () => 0.8
        },
        margin: [38, 0, 38, 0]
    };
}


function label(text) {
    return {
        text: text,
        fontSize: 10,
        color: '#333333',
        margin: [0, 3, 0, 3]
    };
}

function labelBold(text) {
    return {
        text: text,
        bold: true,
        fontSize: 10.5,
        color: '#222222',
        margin: [0, 3, 0, 3]
    };
}

function value(text) {
    return {
        text: text || '',
        fontSize: 10,
        color: '#111111',
        margin: [0, 3, 0, 3]
    };
}

function valueBold(text) {
    return {
        text: text || '',
        bold: true,
        fontSize: 11,
        color: '#111111',
        margin: [0, 3, 0, 3]
    };
}

function tableLayout() {
    return {
        hLineColor: () => '#CCCCCC',
        vLineColor: () => '#DDDDDD',
        hLineWidth: () => 0.5,
        vLineWidth: () => 0.5,
        paddingLeft: () => 12,
        paddingRight: () => 12,
        paddingTop: () => 3,
        paddingBottom: () => 3
    };
}
});