document.addEventListener('DOMContentLoaded', function() {

    /*
    |--------------------------------------------------------------------------
    | ELEMENTOS DEL DOM
    |--------------------------------------------------------------------------
    */

    const tbodyListados = document.getElementById('tbodyListados');
    const filterSearch = document.getElementById('filterSearch');
    const filterDesde = document.getElementById('filterDesde');
    const filterHasta = document.getElementById('filterHasta');

    // Aunque actualmente se llama filterPrioridad,
    // aquí se utilizará como filtro de tipo de cliente.
    const filterTipoCliente = document.getElementById('filterPrioridad');

    const btnRefrescarListado = document.getElementById('btnRefrescarListado');

    /*
    |--------------------------------------------------------------------------
    | VARIABLES GLOBALES
    |--------------------------------------------------------------------------
    */

    let listadoActual = [];
    let tipoClienteActual = 'TODAS';
    let controladorCarga = null;

    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN
    |--------------------------------------------------------------------------
    */

    const TOTAL_COLUMNAS = 10;

    function getListadoEndpoint(tipo) {
        const id = Number(tipo);
        const base = `${base_url}/cli_clientes/getTodos`;
        return id > 0 ? `${base}?tipo=${encodeURIComponent(id)}` : base;
    }

    /*
    |--------------------------------------------------------------------------
    | PETICIONES HTTP
    |--------------------------------------------------------------------------
    */

    async function fetchJson(url, signal = null) {
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            signal
        });

        if (!response.ok) {
            throw new Error(`Error HTTP ${response.status}`);
        }

        const contentType = response.headers.get('content-type') || '';

        if (!contentType.includes('application/json')) {
            throw new Error('La respuesta del servidor no tiene formato JSON.');
        }

        return await response.json();
    }

    /*
    |--------------------------------------------------------------------------
    | SEGURIDAD Y NORMALIZACIÓN
    |--------------------------------------------------------------------------
    */

    function escapeHtml(valor) {
        const div = document.createElement('div');
        div.textContent = valor === null || valor === undefined ?
            '' :
            String(valor);

        return div.innerHTML;
    }

    function normalizarTextoSimple(valor) {
        return String(valor ?? '').trim();
    }

    function normalizarTextoBusqueda(valor) {
        return normalizarTextoSimple(valor)
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    }

    function convertirNumero(valor, valorPredeterminado = 0) {
        const numero = Number(valor);

        return Number.isFinite(numero) ?
            numero :
            valorPredeterminado;
    }

    function obtenerArrayRespuesta(data) {
        if (Array.isArray(data)) {
            return data;
        }

        if (Array.isArray(data?.data)) {
            return data.data;
        }

        if (Array.isArray(data?.clientes)) {
            return data.clientes;
        }

        if (Array.isArray(data?.results)) {
            return data.results;
        }

        return [];
    }

    /*
    |--------------------------------------------------------------------------
    | TIPOS DE CLIENTE
    |--------------------------------------------------------------------------
    */

    function obtenerTipoCliente(row) {
        const idtipo = convertirNumero(row.idtipo_cliente);

        const tipos = {
            1: 'DISTRIBUIDOR',
            2: 'INTERNO',
            3: 'EXTERNO',
            4: 'GUBERNAMENTAL'
        };

        return tipos[idtipo] ||
            normalizarTextoSimple(row.tipo_cliente) ||
            'SIN TIPO';
    }

    /*
    |--------------------------------------------------------------------------
    | ESTADOS
    |--------------------------------------------------------------------------
    */

    function obtenerNombreEstado(estado) {
        const estadoNumero = convertirNumero(estado, -1);

        if (estadoNumero === 2) {
            return 'ACTIVO';
        }

        if (estadoNumero === 1) {
            return 'INACTIVO';
        }

        if (estadoNumero === 0) {
            return 'ELIMINADO';
        }

        return 'SIN ESTADO';
    }

    function badgeEstado(estado) {
        const estadoNumero = convertirNumero(estado, -1);

        if (estadoNumero === 2) {
            return `
                <span class="badge bg-success-subtle text-success border border-success">
                    <i class="ri-checkbox-circle-fill me-1"></i>
                    Activo
                </span>
            `;
        }

        if (estadoNumero === 1) {
            return `
                <span class="badge bg-danger-subtle text-danger border border-danger">
                    <i class="ri-close-circle-fill me-1"></i>
                    Inactivo
                </span>
            `;
        }

        if (estadoNumero === 0) {
            return `
                <span class="badge bg-secondary-subtle text-secondary border border-secondary">
                    <i class="ri-delete-bin-line me-1"></i>
                    Eliminado
                </span>
            `;
        }

        return `
            <span class="badge bg-light text-dark border">
                <i class="ri-question-line me-1"></i>
                Sin estado
            </span>
        `;
    }

    function badgeTipoCliente(tipoCliente) {
        const tipo = normalizarTextoSimple(tipoCliente).toUpperCase();

        const configuracion = {
            DISTRIBUIDOR: {
                clase: 'bg-primary-subtle text-primary border-primary',
                icono: 'ri-store-2-line',
                texto: 'Distribuidor'
            },
            INTERNO: {
                clase: 'bg-info-subtle text-info border-info',
                icono: 'ri-building-line',
                texto: 'Interno'
            },
            EXTERNO: {
                clase: 'bg-warning-subtle text-warning border-warning',
                icono: 'ri-user-shared-line',
                texto: 'Externo'
            },
            GUBERNAMENTAL: {
                clase: 'bg-dark-subtle text-dark border-dark',
                icono: 'ri-government-line',
                texto: 'Gubernamental'
            }
        };

        const item = configuracion[tipo];

        if (!item) {
            return `
                <span class="badge bg-light text-dark border">
                    <i class="ri-question-line me-1"></i>
                    ${escapeHtml(tipoCliente || 'Sin tipo')}
                </span>
            `;
        }

        return `
            <span class="badge ${item.clase} border">
                <i class="${item.icono} me-1"></i>
                ${item.texto}
            </span>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | FORMATOS
    |--------------------------------------------------------------------------
    */

    function formatearMoneda(valor) {
        const monto = convertirNumero(valor);

        return new Intl.NumberFormat('es-MX', {
            style: 'currency',
            currency: 'MXN',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(monto);
    }

    function obtenerFechaISO(fecha) {
        if (!fecha) {
            return '';
        }

        const coincidencia = String(fecha).match(/^(\d{4})-(\d{2})-(\d{2})/);

        if (!coincidencia) {
            return '';
        }

        return `${coincidencia[1]}-${coincidencia[2]}-${coincidencia[3]}`;
    }

    function convertirFechaLocal(fecha) {
        const fechaISO = obtenerFechaISO(fecha);

        if (!fechaISO) {
            return null;
        }

        const partes = fechaISO.split('-').map(Number);

        return new Date(
            partes[0],
            partes[1] - 1,
            partes[2]
        );
    }

    function formatearFecha(fecha) {
        const objetoFecha = convertirFechaLocal(fecha);

        if (!objetoFecha || Number.isNaN(objetoFecha.getTime())) {
            return 'Sin fecha';
        }

        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }).format(objetoFecha);
    }

    function formatearFechaHora(fecha) {
        if (!fecha) {
            return 'Sin fecha';
        }

        const fechaNormalizada = String(fecha).replace(' ', 'T');
        const objetoFecha = new Date(fechaNormalizada);

        if (Number.isNaN(objetoFecha.getTime())) {
            return formatearFecha(fecha);
        }

        return new Intl.DateTimeFormat('es-MX', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        }).format(objetoFecha);
    }

    function formatearDiasCredito(dias) {
        const cantidad = convertirNumero(dias);

        if (cantidad === 0) {
            return 'Sin crédito';
        }

        return cantidad === 1 ?
            '1 día' :
            `${cantidad} días`;
    }

    /*
    |--------------------------------------------------------------------------
    | NORMALIZACIÓN DE REGISTROS
    |--------------------------------------------------------------------------
    */

    function normalizarRows(data) {
        const registros = obtenerArrayRespuesta(data);

        return registros.map(row => {
            const estado = convertirNumero(row.estado, -1);
            const tipoCliente = obtenerTipoCliente(row);

            return {
                idcliente: convertirNumero(
                    row.idcliente ?? row.id
                ),

                idtipo_cliente: convertirNumero(
                    row.idtipo_cliente
                ),

                idregimen_fiscal: convertirNumero(
                    row.idregimen_fiscal
                ),

                codigo_cliente: normalizarTextoSimple(
                    row.codigo_cliente
                ),

                razon_social: normalizarTextoSimple(
                    row.razon_social
                ),

                nombre_comercial: normalizarTextoSimple(
                    row.nombre_comercial
                ),

                rfc: normalizarTextoSimple(
                    row.rfc
                ).toUpperCase(),

                correo: normalizarTextoSimple(
                    row.correo
                ),

                telefono: normalizarTextoSimple(
                    row.telefono
                ),

                celular: normalizarTextoSimple(
                    row.celular
                ),

                limite_credito: convertirNumero(
                    row.limite_credito
                ),

                dias_credito: convertirNumero(
                    row.dias_credito
                ),

                requiere_factura: convertirNumero(
                    row.requiere_factura
                ),

                estado,

                nombre_estado: obtenerNombreEstado(estado),

                tipo_cliente: tipoCliente,
                nombre_tipo_cliente: normalizarTextoSimple(row.tipo_cliente) || tipoCliente,
                fecha_envio_accesos: normalizarTextoSimple(row.fecha_envio_accesos),

                fecha_creacion: normalizarTextoSimple(
                    row.fecha_creacion
                ),

                fecha_actualizacion: normalizarTextoSimple(
                    row.fecha_actualizacion
                ),

                fecha_filtro: obtenerFechaISO(
                    row.fecha_creacion
                )
            };
        });
    }

    /*
    |--------------------------------------------------------------------------
    | FILTROS
    |--------------------------------------------------------------------------
    */

    function aplicarFiltros(rows) {
        const textoBusqueda = normalizarTextoBusqueda(
            filterSearch ?.value
        );

        const fechaDesde = filterDesde ?.value || '';
        const fechaHasta = filterHasta ?.value || '';

        return rows.filter(row => {
            const camposBusqueda = [
                row.codigo_cliente,
                row.razon_social,
                row.nombre_comercial,
                row.rfc
            ];

            const coincideBusqueda = camposBusqueda.some(campo => {
                return normalizarTextoBusqueda(campo)
                    .includes(textoBusqueda);
            });

            if (textoBusqueda && !coincideBusqueda) {
                return false;
            }

            if (
                fechaDesde &&
                row.fecha_filtro &&
                row.fecha_filtro < fechaDesde
            ) {
                return false;
            }

            if (
                fechaHasta &&
                row.fecha_filtro &&
                row.fecha_filtro > fechaHasta
            ) {
                return false;
            }

            return true;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | BOTONES DE OPCIONES
    |--------------------------------------------------------------------------
    */

    function construirBotonesOpciones(row) {
        const idcliente = convertirNumero(row.idcliente);

        return `
            <div class="d-flex justify-content-end align-items-center gap-1 flex-nowrap">

                <button
                    type="button"
                    class="btn btn-sm btn-soft-info"
                    title="Ver información del cliente"
                    aria-label="Ver información del cliente"
                    onclick="fntViewCliente(${idcliente})"
                >
                    <i class="ri-eye-line"></i>
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-soft-primary"
                    title="Administrar accesos"
                    aria-label="Administrar accesos"
                    onclick="fntAccesosCliente(${idcliente})"
                >
                    <i class="ri-key-2-line"></i>
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-soft-warning"
                    title="Editar cliente"
                    aria-label="Editar cliente"
                    onclick="fntEditCliente(${idcliente})"
                >
                    <i class="ri-pencil-line"></i>
                </button>

                <button
                    type="button"
                    class="btn btn-sm btn-soft-danger"
                    title="Eliminar cliente"
                    aria-label="Eliminar cliente"
                    onclick="fntDelCliente(${idcliente})"
                >
                    <i class="ri-delete-bin-6-line"></i>
                </button>

            </div>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | MENSAJES DE TABLA
    |--------------------------------------------------------------------------
    */

    function mostrarCargando() {
        if (!tbodyListados) {
            return;
        }

        tbodyListados.innerHTML = `
            <tr>
                <td colspan="${TOTAL_COLUMNAS}" class="text-center py-5">
                    <div
                        class="spinner-border spinner-border-sm text-primary me-2"
                        role="status"
                        aria-hidden="true"
                    ></div>

                    <span class="text-muted">
                        Cargando clientes...
                    </span>
                </td>
            </tr>
        `;
    }

    function mostrarSinResultados() {
        if (!tbodyListados) {
            return;
        }

        tbodyListados.innerHTML = `
            <tr>
                <td colspan="${TOTAL_COLUMNAS}" class="text-center py-5">
                    <div class="mb-2">
                        <i class="ri-user-search-line fs-1 text-muted"></i>
                    </div>

                    <div class="fw-semibold text-muted">
                        No se encontraron clientes
                    </div>

                    <small class="text-muted">
                        Intenta cambiar los filtros de búsqueda.
                    </small>
                </td>
            </tr>
        `;
    }

    function mostrarError(mensaje) {
        if (!tbodyListados) {
            return;
        }

        tbodyListados.innerHTML = `
            <tr>
                <td colspan="${TOTAL_COLUMNAS}" class="text-center py-5">
                    <div class="mb-2">
                        <i class="ri-error-warning-line fs-1 text-danger"></i>
                    </div>

                    <div class="fw-semibold text-danger">
                        No fue posible cargar los clientes
                    </div>

                    <small class="text-muted">
                        ${escapeHtml(mensaje)}
                    </small>
                </td>
            </tr>
        `;
    }

    /*
    |--------------------------------------------------------------------------
    | PINTAR TABLA
    |--------------------------------------------------------------------------
    */

    function pintarTabla(rows) {
        if (!tbodyListados) {
            return;
        }

        tbodyListados.innerHTML = '';

        if (!Array.isArray(rows) || rows.length === 0) {
            mostrarSinResultados();
            return;
        }

        const fragmento = document.createDocumentFragment();

        rows.forEach(row => {
            const tr = document.createElement('tr');

            tr.dataset.idcliente = row.idcliente;

            tr.innerHTML = `
                <td>
                    <div class="d-flex align-items-center">
                        <span
                            class="avatar-xs flex-shrink-0 me-2"
                            title="${escapeHtml(row.tipo_cliente)}"
                        >
                            <span
                                class="avatar-title rounded-circle bg-primary-subtle text-primary"
                            >
                                <i class="ri-user-3-line"></i>
                            </span>
                        </span>

                        <div>
                            <div class="fw-semibold text-dark">
                                ${escapeHtml(row.codigo_cliente || 'Sin clave')}
                            </div>

                            <small>
                                ${badgeTipoCliente(row.tipo_cliente)}
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    <div class="d-flex align-items-start">
                        <i
                            class="ri-building-2-line text-primary me-2 mt-1"
                            title="Razón social"
                        ></i>

                        <span>
                            ${escapeHtml(row.razon_social || 'Sin razón social')}
                        </span>
                    </div>
                </td>

                <td>
                    <div class="d-flex align-items-start">
                        <i
                            class="ri-store-2-line text-warning me-2 mt-1"
                            title="Nombre comercial"
                        ></i>

                        <span>
                            ${escapeHtml(row.nombre_comercial || 'Sin nombre comercial')}
                        </span>
                    </div>
                </td>

                <td>
                    <div class="d-flex align-items-center">
                        <i
                            class="ri-file-list-3-line text-secondary me-2"
                            title="RFC"
                        ></i>

                        <span class="fw-medium text-uppercase">
                            ${escapeHtml(row.rfc || 'Sin RFC')}
                        </span>
                    </div>
                </td>

                <td class="text-end">
                    <div class="fw-semibold text-success">
                        ${escapeHtml(formatearMoneda(row.limite_credito))}
                    </div>

                    <small class="text-muted">
                        <i class="ri-money-dollar-circle-line me-1"></i>
                        Límite autorizado
                    </small>
                </td>

                <td class="text-center">
                    <div class="fw-medium">
                        ${escapeHtml(formatearDiasCredito(row.dias_credito))}
                    </div>

                    <small class="text-muted">
                        <i class="ri-calendar-check-line me-1"></i>
                        Crédito
                    </small>
                </td>

                <td>
                    <div class="d-flex align-items-start">
                        <i
                            class="ri-calendar-event-line text-info me-2 mt-1"
                            title="Fecha de registro"
                        ></i>

                        <div>
                            <div class="fw-medium">
                                ${escapeHtml(formatearFecha(row.fecha_creacion))}
                            </div>

                            <small
                                class="text-muted"
                                title="${escapeHtml(formatearFechaHora(row.fecha_creacion))}"
                            >
                                Fecha de registro
                            </small>
                        </div>
                    </div>
                </td>

                <td class="text-center">
                    ${badgeEstado(row.estado)}
                </td>

                <td class="text-nowrap">
                    ${escapeHtml(estadoCorreoAcceso(row))}
                    ${row.fecha_envio_accesos ? '<br><small class="text-muted">' +
                        escapeHtml(formatearFechaHora(row.fecha_envio_accesos)) + '</small>' : ''}
                </td>
                <td class="text-end">
                    ${construirBotonesOpciones(row)}
                </td>
            `;

            fragmento.appendChild(tr);
        });

        tbodyListados.appendChild(fragmento);

        inicializarTooltips();
    }

    /*
    |--------------------------------------------------------------------------
    | TOOLTIP DE BOOTSTRAP
    |--------------------------------------------------------------------------
    */

    function inicializarTooltips() {
        if (
            typeof bootstrap === 'undefined' ||
            typeof bootstrap.Tooltip === 'undefined'
        ) {
            return;
        }

        const elementos = tbodyListados.querySelectorAll('[title]');

        elementos.forEach(elemento => {
            const tooltipExistente =
                bootstrap.Tooltip.getInstance(elemento);

            if (tooltipExistente) {
                tooltipExistente.dispose();
            }

            new bootstrap.Tooltip(elemento);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | RENDERIZADO
    |--------------------------------------------------------------------------
    */

    function refrescarConFiltrosCliente() {
        const filasFiltradas = aplicarFiltros(listadoActual);
        pintarTabla(filasFiltradas);
    }

    async function renderListado(tipo = 'TODAS') {
        if (!tbodyListados) {
            return;
        }

        tipoClienteActual = normalizarTextoSimple(tipo).toUpperCase() ||
            'TODAS';

        if (controladorCarga) {
            controladorCarga.abort();
        }

        const solicitud = new AbortController();
        controladorCarga = solicitud;

        mostrarCargando();

        try {
            const endpoint = getListadoEndpoint(tipoClienteActual);

            const respuesta = await fetchJson(
                endpoint,
                solicitud.signal
            );

            listadoActual = normalizarRows(respuesta);

            refrescarConFiltrosCliente();

        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error('Error al cargar clientes:', error);

            listadoActual = [];

            mostrarError(
                error.message || 'Ocurrió un error inesperado.'
            );

        } finally {
            if (controladorCarga === solicitud) {
                controladorCarga = null;
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DEBOUNCE PARA BUSCADOR
    |--------------------------------------------------------------------------
    */

    function debounce(funcion, tiempo = 300) {
        let temporizador;

        return function(...argumentos) {
            clearTimeout(temporizador);

            temporizador = setTimeout(() => {
                funcion.apply(this, argumentos);
            }, tiempo);
        };
    }

    function estadoCorreoAcceso(row) {
        if (row.idtipo_cliente !== 1) return 'No aplica';
        return row.fecha_envio_accesos ? 'Enviado' : 'Pendiente';
    }

    const recursosReporte = new Map();

    function cargarScriptReporte(url) {
        if (!recursosReporte.has(url)) {
            const promesa = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = url;
                script.onload = resolve;
                script.onerror = () => {
                    script.remove();
                    recursosReporte.delete(url);
                    reject(new Error('No se pudo cargar la biblioteca de exportación.'));
                };
                document.head.appendChild(script);
            });
            recursosReporte.set(url, promesa);
        }
        return recursosReporte.get(url);
    }

    async function obtenerLogoReporte() {
        const respuesta = await fetch(`${base_url}/cli_clientes/getLogoReporte`);
        if (!respuesta.ok || !respuesta.headers.get('content-type')?.startsWith('image/png')) {
            throw new Error('No se pudo cargar el logotipo del reporte.');
        }
        const imagen = await respuesta.blob();
        return new Promise((resolve, reject) => {
            const lector = new FileReader();
            lector.onload = () => resolve(lector.result);
            lector.onerror = reject;
            lector.readAsDataURL(imagen);
        });
    }

    async function exportarClientes(formato, boton) {
        if (controladorCarga || boton.disabled) return;
        const filas = aplicarFiltros(listadoActual);
        if (!filas.length) {
            window.alert('No hay clientes para exportar con los filtros actuales.');
            return;
        }

        const encabezados = [
            'Código', 'Tipo de cliente', 'Razón social', 'Nombre comercial', 'RFC', 'Correo',
            'Teléfono', 'Celular', 'Límite de crédito', 'Días de crédito', 'Estatus',
            'Fecha de registro', 'Correo de acceso', 'Último envío'
        ];
        const datos = filas.map(row => [
            row.codigo_cliente, row.nombre_tipo_cliente, row.razon_social, row.nombre_comercial,
            row.rfc, row.correo, row.telefono, row.celular, row.limite_credito,
            row.dias_credito, row.nombre_estado, formatearFecha(row.fecha_creacion),
            estadoCorreoAcceso(row), row.fecha_envio_accesos ? formatearFechaHora(row.fecha_envio_accesos) : ''
        ]);

        boton.disabled = true;
        try {
            const logo = await obtenerLogoReporte();
            const fecha = new Intl.DateTimeFormat('es-MX', { dateStyle: 'short' }).format(new Date());
            if (formato === 'xlsx') {
                if (!window.ExcelJS) {
                    await cargarScriptReporte('https://cdn.jsdelivr.net/npm/exceljs@4.4.0/dist/exceljs.min.js');
                }
                const libro = new window.ExcelJS.Workbook();
                const hoja = libro.addWorksheet('Clientes', { views: [{ state: 'frozen', ySplit: 5 }] });
                const idLogo = libro.addImage({ base64: logo, extension: 'png' });
                hoja.addImage(idLogo, { tl: { col: 0, row: 0 }, ext: { width: 140, height: 42 } });
                hoja.getCell('C2').value = 'Reporte de clientes';
                hoja.getCell('C2').font = { bold: true, size: 16 };
                hoja.getCell('C3').value = `Fecha de emisión ${fecha}`;
                hoja.getRow(5).values = encabezados;
                hoja.getRow(5).eachCell(celda => {
                    celda.font = { bold: true, color: { argb: 'FFFFFFFF' } };
                    celda.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FF25374B' } };
                });
                hoja.addRows(datos);
                hoja.autoFilter = { from: 'A5', to: 'N5' };
                hoja.getColumn(9).numFmt = '"$"#,##0.00';
                [19, 25, 38, 30, 18, 36, 19, 19, 22, 18, 16, 20, 20, 24]
                    .forEach((ancho, indice) => { hoja.getColumn(indice + 1).width = ancho; });
                const archivo = await libro.xlsx.writeBuffer();
                const url = URL.createObjectURL(new Blob([archivo], {
                    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                }));
                const enlace = document.createElement('a');
                enlace.href = url;
                enlace.download = 'Reporte-clientes.xlsx';
                enlace.click();
                setTimeout(() => URL.revokeObjectURL(url), 1000);
            } else {
                if (!window.pdfMake?.createPdf) {
                    await cargarScriptReporte('https://cdn.jsdelivr.net/npm/pdfmake@0.2.12/build/pdfmake.min.js');
                }
                if (!window.pdfMake.vfs && !window.pdfMake.fonts?.Roboto) {
                    await cargarScriptReporte('https://cdn.jsdelivr.net/npm/pdfmake@0.2.12/build/vfs_fonts.js');
                }
                const cuerpo = [
                    encabezados.map(text => ({ text, bold: true, color: 'white', fillColor: '#25374B' })),
                    ...datos.map(fila => fila.map((valor, indice) =>
                        indice === 8 ? formatearMoneda(valor) : String(valor)))
                ];
                window.pdfMake.createPdf({
                    pageSize: 'A3',
                    pageOrientation: 'landscape',
                    pageMargins: [28, 72, 28, 36],
                    defaultStyle: { fontSize: 8 },
                    header: {
                        margin: [28, 20, 28, 0],
                        columns: [
                            { image: logo, fit: [112, 40] },
                            { stack: [{ text: 'Reporte de clientes', bold: true, fontSize: 17 }, fecha],
                                alignment: 'right' }
                        ]
                    },
                    footer: (pagina, total) => ({
                        text: `Página ${pagina} de ${total}`, alignment: 'right', margin: [28, 0]
                    }),
                    content: [{
                        table: { headerRows: 1, widths: [67, 70, '*', '*', 64, '*', 53, 53, 60, 46, 45, 56, 53, 66], body: cuerpo },
                        layout: 'lightHorizontalLines'
                    }]
                }).download('Reporte-clientes.pdf');
            }
        } catch (error) {
            window.alert(error.message || 'No se pudo generar el reporte.');
        } finally {
            boton.disabled = false;
        }
    }

    document.getElementById('btnClientesExcel')?.addEventListener('click', event => {
        exportarClientes('xlsx', event.currentTarget);
    });
    document.getElementById('btnClientesPdf')?.addEventListener('click', event => {
        exportarClientes('pdf', event.currentTarget);
    });

    const ejecutarBusqueda = debounce(
        refrescarConFiltrosCliente,
        250
    );

    /*
    |--------------------------------------------------------------------------
    | EVENTOS
    |--------------------------------------------------------------------------
    */

    if (filterTipoCliente) {
        filterTipoCliente.addEventListener('change', function() {
            const tipoSeleccionado = this.value || 'TODAS';

            renderListado(tipoSeleccionado);
        });
    }

    if (btnRefrescarListado) {
        btnRefrescarListado.addEventListener('click', function() {
            renderListado(tipoClienteActual);
        });
    }

    if (filterSearch) {
        filterSearch.addEventListener('input', ejecutarBusqueda);
    }

    if (filterDesde) {
        filterDesde.addEventListener(
            'change',
            refrescarConFiltrosCliente
        );
    }

    if (filterHasta) {
        filterHasta.addEventListener(
            'change',
            refrescarConFiltrosCliente
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CARGA INICIAL
    |--------------------------------------------------------------------------
    */

    const tipoInicial = filterTipoCliente ?.value || 'TODAS';

    renderListado(tipoInicial);
});


/*
|--------------------------------------------------------------------------
| FUNCIONES DE OPCIONES
|--------------------------------------------------------------------------
| Estas funciones quedan preparadas para conectar posteriormente con el
| backend, modales, formularios y SweetAlert.
*/

function fntViewCliente(idcliente) {
    console.log('Ver cliente:', idcliente);

    /*
    Ejemplo futuro:

    window.location.href =
        `${base_url}/cli_clientes/ver/${idcliente}`;
    */
}


function fntAccesosCliente(idcliente) {
    // console.log('Administrar accesos del cliente:', idcliente);


    window.location.href =
        `${base_url}/cli_clientes/accesos/${idcliente}`;

}


function fntEditCliente(idcliente) {
    idcliente = Number(idcliente);

    if (!Number.isInteger(idcliente) || idcliente <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Cliente inválido',
            text: 'No fue posible identificar al cliente seleccionado.'
        });

        return;
    }

    window.location.href =
        `${base_url}/cli_clientes/editar/${idcliente}`;
}


function fntDelCliente(idcliente) {
    console.log('Eliminar cliente:', idcliente);

    /*
    Ejemplo futuro con SweetAlert:

    Swal.fire({
        title: '¿Eliminar cliente?',
        text: 'El cliente será marcado como eliminado.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then(resultado => {
        if (resultado.isConfirmed) {
            // Aquí realizarías la petición al backend.
        }
    });
    */
}

/*creamos la funcionalidad para redirecionar a la vista de crear un nuevo cliente */

document.getElementById("btnAgregarCliente").addEventListener("click", function() {
    window.location.href = base_url + "/cli_clientes/create";
});