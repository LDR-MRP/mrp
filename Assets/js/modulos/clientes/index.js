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
    const filterEstado = document.getElementById('filterEstado');
    const filterCorreo = document.getElementById('filterCorreoAcceso');
    const porPagina = document.getElementById('clientesPorPagina');
    const anterior = document.getElementById('clientesAnterior');
    const siguiente = document.getElementById('clientesSiguiente');
    const rango = document.getElementById('clientesRango');
    const paginaTexto = document.getElementById('clientesPagina');

    const btnRefrescarListado = document.getElementById('btnRefrescarListado');

    /*
    |--------------------------------------------------------------------------
    | VARIABLES GLOBALES
    |--------------------------------------------------------------------------
    */

    let listadoActual = [];
    let paginaActual = 1;
    let controladorCarga = null;

    /*
    |--------------------------------------------------------------------------
    | CONFIGURACIÓN
    |--------------------------------------------------------------------------
    */

    const TOTAL_COLUMNAS = 10;

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

        return estadoNumero === 3 ? 'SUSPENDIDO' : 'SIN ESTADO';
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
                ${estadoNumero === 3 ? "Suspendido" : "Sin estado"}
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
        const tipo = filterTipoCliente.value;
        const estado = filterEstado.value;
        const correo = filterCorreo.value;

        return rows.filter(row => {
            if ((tipo && String(row.idtipo_cliente) !== tipo)
                || (estado && String(row.estado) !== estado)
                || (correo && estadoCorreoAcceso(row) !== correo)) {
                return false;
            }

            const camposBusqueda = [
                row.codigo_cliente,
                row.razon_social,
                row.nombre_comercial,
                row.rfc, row.correo, row.telefono, row.celular
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
                (!row.fecha_filtro || row.fecha_filtro < fechaDesde)
            ) {
                return false;
            }

            if (
                fechaHasta &&
                (!row.fecha_filtro || row.fecha_filtro > fechaHasta)
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
        const botonFicha = row.codigo_cliente ? `
            <button type="button" class="btn btn-sm btn-soft-danger" data-ficha-cliente=${idcliente}
                title="Descargar ficha PDF" aria-label="Descargar ficha PDF">
                <i class="ri-file-pdf-line" aria-hidden="true"></i>
            </button>` : '';

        return `
            <div class="d-flex justify-content-end align-items-center gap-1 flex-nowrap">
                ${botonFicha}

                <button
                    type="button"
                    class="btn btn-sm btn-soft-info d-none"
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

    function actualizarIndicadores(totalFiltrado) {
        document.getElementById('clientesTotal').textContent = listadoActual.length;
        document.getElementById('clientesActivos').textContent = listadoActual.filter(row => row.estado === 2).length;
        document.getElementById('clientesDistribuidores').textContent = listadoActual.filter(row => row.idtipo_cliente === 1).length;
        document.getElementById('clientesFiltrados').textContent = totalFiltrado;
    }

    function refrescarConFiltrosCliente() {
        const filas = aplicarFiltros(listadoActual);
        const tamanio = Number(porPagina.value);
        const paginas = Math.max(1, Math.ceil(filas.length / tamanio));
        paginaActual = Math.max(1, Math.min(paginaActual, paginas));
        const desde = (paginaActual - 1) * tamanio;
        const hasta = Math.min(desde + tamanio, filas.length);

        anterior.disabled = paginaActual === 1;
        siguiente.disabled = paginaActual >= paginas;
        paginaTexto.textContent = filas.length ? `Página ${paginaActual} de ${paginas}` : '';
        rango.textContent = filas.length ? `${desde + 1}–${hasta} de ${filas.length} registros` : '0 registros';
        actualizarIndicadores(filas.length);
        pintarTabla(filas.slice(desde, hasta));
    }

    function reiniciarPagina() {
        paginaActual = 1;
        if (!controladorCarga) refrescarConFiltrosCliente();
    }

    async function renderListado() {
        if (controladorCarga) {
            controladorCarga.abort();
        }

        const solicitud = new AbortController();
        controladorCarga = solicitud;

        mostrarCargando();
        anterior.disabled = siguiente.disabled = true;
        rango.textContent = '';
        paginaTexto.textContent = '';

        try {
            const endpoint = `${base_url}/cli_clientes/getTodos`;

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
            actualizarIndicadores(0);

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

    async function cargarPdfMake() {
        if (!window.pdfMake?.createPdf) {
            await cargarScriptReporte('https://cdn.jsdelivr.net/npm/pdfmake@0.2.12/build/pdfmake.min.js');
        }
        if (!window.pdfMake.vfs && !window.pdfMake.fonts?.Roboto) {
            await cargarScriptReporte('https://cdn.jsdelivr.net/npm/pdfmake@0.2.12/build/vfs_fonts.js');
        }
    }

    async function descargarFichaCliente(cliente, boton) {
        boton.disabled = true;
        try {
            const url = `${base_url}/cli_clientes/getFichaCliente?codigo=${encodeURIComponent(cliente.codigo_cliente)}`;
            const respuesta = await fetch(url, { cache: 'no-store', headers: { Accept: 'application/json' } });
            const ficha = await respuesta.json();
            if (!respuesta.ok || !ficha.status) {
                throw new Error(ficha.message || 'No fue posible consultar la ficha.');
            }

            const [logo] = await Promise.all([obtenerLogoReporte().catch(() => null), cargarPdfMake()]);
            const { codigo, nombre, secciones } = ficha.data;
            const contenido = [
                { text: 'Ficha del cliente', style: 'titulo' },
                { text: nombre, style: 'nombre' },
                { text: codigo, color: '#475569', margin: [0, 2, 0, 12] }
            ];
            for (const { titulo, registros } of secciones) {
                const filas = [[{ text: titulo, colSpan: 2, style: 'seccion' }, {}]];
                if (!registros.some(campos => campos.length)) {
                    filas.push([{ text: 'Sin información registrada', colSpan: 2, color: '#64748b' }, {}]);
                }
                registros.forEach((campos, indice) => {
                    if (registros.length > 1) {
                        filas.push([{ text: `Registro ${indice + 1}`, colSpan: 2, bold: true, fillColor: '#eef2f7' }, {}]);
                    }
                    for (const [etiqueta, valor] of campos) {
                        filas.push([{ text: etiqueta, color: '#334155', bold: true }, { text: valor }]);
                    }
                });
                contenido.push({
                    margin: [0, 0, 0, 12],
                    table: { headerRows: 1, keepWithHeaderRows: 1, widths: [136, '*'], body: filas },
                    layout: {
                        hLineWidth: () => 0.5, hLineColor: () => '#dbe4ee', vLineWidth: () => 0,
                        paddingLeft: () => 9, paddingRight: () => 9, paddingTop: () => 5, paddingBottom: () => 5
                    }
                });
            }

            const fecha = new Intl.DateTimeFormat('es-MX', {
                dateStyle: 'medium', timeZone: 'America/Mexico_City'
            }).format(new Date());
            const archivo = window.pdfMake.createPdf({
                info: { title: `Ficha del cliente ${codigo}` },
                pageSize: 'A4',
                pageMargins: [40, 76, 40, 47],
                defaultStyle: { fontSize: 9, color: '#0f172a' },
                header: {
                    margin: [40, 24, 40, 0],
                    columns: [
                        logo ? { image: logo, fit: [115, 36] } : { text: 'MRP', bold: true, fontSize: 14 },
                        { stack: [{ text: 'Ficha del cliente', bold: true }, fecha],
                            alignment: 'right', color: '#475569', fontSize: 9 }
                    ]
                },
                footer: (pagina, total) => ({
                    margin: [40, 12, 40, 0], color: '#475569', fontSize: 8,
                    columns: [{ text: `${codigo}  ·  Uso interno` }, { text: `Página ${pagina} de ${total}`, alignment: 'right' }]
                }),
                styles: {
                    titulo: { fontSize: 25, bold: true, color: '#172c44' },
                    nombre: { fontSize: 13, bold: true, margin: [0, 5, 0, 0] },
                    seccion: { fontSize: 10, bold: true, color: 'white', fillColor: '#25374b' }
                },
                content: contenido
            });
            await new Promise(resolve => archivo.download(`Ficha-${codigo.replace(/[^a-z0-9_-]/gi, '-')}.pdf`, resolve));
        } catch (error) {
            window.alert(error.message || 'No fue posible generar el PDF del cliente.');
        } finally {
            boton.disabled = false;
        }
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
                await cargarPdfMake();
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

    filterSearch.addEventListener('input', debounce(reiniciarPagina, 250));
    [filterTipoCliente, filterDesde, filterHasta, filterEstado, filterCorreo, porPagina]
        .forEach(campo => campo.addEventListener('change', reiniciarPagina));
    btnRefrescarListado.addEventListener('click', renderListado);
    document.getElementById('btnLimpiarFiltros').addEventListener('click', () => {
        [filterSearch, filterTipoCliente, filterDesde, filterHasta, filterEstado, filterCorreo]
            .forEach(campo => { campo.value = ''; });
        reiniciarPagina();
    });
    anterior.addEventListener('click', () => {
        paginaActual--;
        refrescarConFiltrosCliente();
    });
    siguiente.addEventListener('click', () => {
        paginaActual++;
        refrescarConFiltrosCliente();
    });
    tbodyListados.addEventListener('click', event => {
        const boton = event.target.closest('[data-ficha-cliente]');
        if (boton) {
            const cliente = listadoActual.find(row => String(row.idcliente) === boton.dataset.fichaCliente);
            if (cliente) descargarFichaCliente(cliente, boton);
        }
    });

    renderListado();
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