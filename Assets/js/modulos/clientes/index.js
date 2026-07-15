document.addEventListener('DOMContentLoaded', function () {

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

    const TOTAL_COLUMNAS = 9;

    const ENDPOINTS_CLIENTES = {
        TODAS: `${base_url}/cli_clientes/getTodos`,
        DISTRIBUIDORES: `${base_url}/cli_clientes/getDistribuidores`,
        INTERNOS: `${base_url}/cli_clientes/getInternos`,
        EXTERNOS: `${base_url}/cli_clientes/getExternos`,
        GUBERNAMENTALES: `${base_url}/cli_clientes/getGubernamentales`
    };

    /*
    |--------------------------------------------------------------------------
    | ENDPOINTS
    |--------------------------------------------------------------------------
    */

    function getListadoEndpoint(tipo) {
        const tipoNormalizado = normalizarTextoSimple(tipo).toUpperCase();

        return ENDPOINTS_CLIENTES[tipoNormalizado]
            || ENDPOINTS_CLIENTES.TODAS;
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
        div.textContent = valor === null || valor === undefined
            ? ''
            : String(valor);

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

        return Number.isFinite(numero)
            ? numero
            : valorPredeterminado;
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

        return tipos[idtipo]
            || normalizarTextoSimple(row.tipo_cliente)
            || 'SIN TIPO';
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

        return cantidad === 1
            ? '1 día'
            : `${cantidad} días`;
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
            filterSearch?.value
        );

        const fechaDesde = filterDesde?.value || '';
        const fechaHasta = filterHasta?.value || '';

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
                fechaDesde
                && row.fecha_filtro
                && row.fecha_filtro < fechaDesde
            ) {
                return false;
            }

            if (
                fechaHasta
                && row.fecha_filtro
                && row.fecha_filtro > fechaHasta
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
            typeof bootstrap === 'undefined'
            || typeof bootstrap.Tooltip === 'undefined'
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

        tipoClienteActual = normalizarTextoSimple(tipo).toUpperCase()
            || 'TODAS';

        if (controladorCarga) {
            controladorCarga.abort();
        }

        controladorCarga = new AbortController();

        mostrarCargando();

        try {
            const endpoint = getListadoEndpoint(tipoClienteActual);

            const respuesta = await fetchJson(
                endpoint,
                controladorCarga.signal
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
            controladorCarga = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DEBOUNCE PARA BUSCADOR
    |--------------------------------------------------------------------------
    */

    function debounce(funcion, tiempo = 300) {
        let temporizador;

        return function (...argumentos) {
            clearTimeout(temporizador);

            temporizador = setTimeout(() => {
                funcion.apply(this, argumentos);
            }, tiempo);
        };
    }

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
        filterTipoCliente.addEventListener('change', function () {
            const tipoSeleccionado = this.value || 'TODAS';

            renderListado(tipoSeleccionado);
        });
    }

    if (btnRefrescarListado) {
        btnRefrescarListado.addEventListener('click', function () {
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

    const tipoInicial = filterTipoCliente?.value || 'TODAS';

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
    console.log('Editar cliente:', idcliente);

    /*
    Ejemplo futuro:

    window.location.href =
        `${base_url}/cli_clientes/editar/${idcliente}`;
    */
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

document.getElementById("btnAgregarCliente").addEventListener("click", function () {
    window.location.href = base_url + "/cli_clientes/create";
});