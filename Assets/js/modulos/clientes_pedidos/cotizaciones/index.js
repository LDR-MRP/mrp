"use strict";


document.addEventListener(
    "DOMContentLoaded",
    function() {

        inicializarModuloCotizaciones();

    }
);


/* ============================================================
   INICIALIZAR
============================================================ */

function inicializarModuloCotizaciones() {
    configurarEventosCotizaciones();

    cargarDashboardCotizaciones();

    cargarCotizaciones();
}


/* ============================================================
   EVENTOS
============================================================ */

function configurarEventosCotizaciones() {
    const filtroBuscar =
        document.getElementById(
            "filterSearch"
        );

    const filtroDesde =
        document.getElementById(
            "filterDesde"
        );

    const filtroHasta =
        document.getElementById(
            "filterHasta"
        );

    const filtroTipo =
        document.getElementById(
            "filterTipo"
        );

    const filtroEstatus =
        document.getElementById(
            "filterEstatus"
        );

    const btnRefrescar =
        document.getElementById(
            "btnRefrescarListado"
        );

    const btnLimpiar =
        document.getElementById(
            "btnLimpiarFiltros"
        );


    let temporizadorBusqueda = null;


    if (filtroBuscar) {

        filtroBuscar.addEventListener(
            "input",
            function() {

                clearTimeout(
                    temporizadorBusqueda
                );

                temporizadorBusqueda =
                    setTimeout(
                        function() {

                            cargarCotizaciones();

                        },
                        350
                    );

            }
        );

    }


    [
        filtroDesde,
        filtroHasta,
        filtroTipo,
        filtroEstatus

    ].forEach(

        function(elemento) {

            if (!elemento) {
                return;
            }

            elemento.addEventListener(
                "change",
                function() {

                    cargarCotizaciones();

                }
            );

        }

    );


    if (btnRefrescar) {

        btnRefrescar.addEventListener(
            "click",
            function() {

                cargarDashboardCotizaciones();

                cargarCotizaciones();

            }
        );

    }


    if (btnLimpiar) {

        btnLimpiar.addEventListener(
            "click",
            function() {

                limpiarFiltrosCotizaciones();

            }
        );

    }


    document.addEventListener(
        "click",
        manejarAccionesCotizacion
    );
}


/* ============================================================
   OBTENER FILTROS
============================================================ */

function obtenerFiltrosCotizaciones() {
    const filterSearch = document.getElementById("filterSearch");
    const filterDesde = document.getElementById("filterDesde");
    const filterHasta = document.getElementById("filterHasta");
    const filterTipo = document.getElementById("filterTipo");
    const filterEstatus = document.getElementById("filterEstatus");

    return {
        buscar: filterSearch ? filterSearch.value.trim() : "",
        desde: filterDesde ? filterDesde.value : "",
        hasta: filterHasta ? filterHasta.value : "",
        tipo: filterTipo ? filterTipo.value : "",
        estatus: filterEstatus ? filterEstatus.value : ""
    };
}


/* ============================================================
   CARGAR COTIZACIONES
============================================================ */

async function cargarCotizaciones() {
    console.log('dom cargado');
    const tbody =
        document.getElementById(
            "tbodyCotizaciones"
        );


    if (!tbody) {
        return;
    }


    mostrarCargandoCotizaciones();


    try {

        const filtros =
            obtenerFiltrosCotizaciones();


        const parametros =
            new URLSearchParams(
                filtros
            );


        const respuesta =
            await fetch(
                `${base_url}/ped_cotizaciones/getCotizaciones?${parametros.toString()}`, {
                    method: "GET",
                    headers: {
                        "Accept": "application/json"
                    }
                }
            );


        const resultado =
            await respuesta.json();


        if (!resultado.status) {

            throw new Error(
                resultado.message ||
                "No fue posible consultar las cotizaciones."
            );

        }


        renderizarCotizaciones(
            resultado.data || []
        );


    } catch (error) {

        console.error(
            "Error cargarCotizaciones:",
            error
        );


        mostrarErrorCotizaciones(
            error.message
        );

    }
}


/* ============================================================
   RENDERIZAR
============================================================ */

function renderizarCotizaciones(cotizaciones) {
    const tbody =
        document.getElementById(
            "tbodyCotizaciones"
        );

    const contador =
        document.getElementById(
            "contadorRegistros"
        );


    if (!tbody) {
        return;
    }


    if (contador) {

        contador.textContent =
            `${cotizaciones.length} ${
                cotizaciones.length === 1
                    ? "registro"
                    : "registros"
            }`;

    }


    if (!cotizaciones.length) {

        tbody.innerHTML = `

            <tr>

                <td
                    colspan="9"
                    class="text-center py-5">

                    <div class="text-muted">

                        <i
                            class="ri-file-search-line fs-1 d-block mb-2">
                        </i>

                        <h6>
                            No se encontraron cotizaciones
                        </h6>

                        <p class="mb-0">

                            Intenta modificar los filtros
                            de búsqueda.

                        </p>

                    </div>

                </td>

            </tr>
        `;

        return;
    }


    tbody.innerHTML =
        cotizaciones
        .map(
            construirFilaCotizacion
        )
        .join("");
}


/* ============================================================
   FILA
============================================================ */

function construirFilaCotizacion(cotizacion) {
    const clave =
        escaparHtml(
            cotizacion.clave || ""
        );

    const folio =
        escaparHtml(
            cotizacion.folio_cotizacion || ""
        );

    const distribuidor =
        escaparHtml(
            cotizacion.nombre_comercial ||
            cotizacion.razon_social ||
            "Sin distribuidor"
        );

    const tipo =
        String(
            cotizacion.tipo_cotizacion || ""
        ).toUpperCase();

    const estatus =
        String(
            cotizacion.estatus || ""
        ).toUpperCase();


    return `

        <tr>

            <td>

                <div class="fw-semibold text-primary">
                    ${folio}
                </div>

                ${
                    cotizacion.folio_pedido
                        ? `
                            <small class="text-muted">
                                Pedido:
                                ${escaparHtml(
                                    cotizacion.folio_pedido
                                )}
                            </small>
                        `
                        : ""
                }

            </td>


            <td>

                <div class="fw-medium">
                    ${distribuidor}
                </div>

                ${
                    cotizacion.codigo_cliente
                        ? `
                            <small class="text-muted">
                                ${escaparHtml(
                                    cotizacion.codigo_cliente
                                )}
                            </small>
                        `
                        : ""
                }

            </td>


            <td>
                ${obtenerBadgeTipoCotizacion(
                    tipo
                )}
            </td>


            <td class="text-center">

                <span
                    class="badge bg-secondary-subtle text-secondary">

                    V${Number(
                        cotizacion.version_actual || 1
                    )}

                </span>

            </td>


            <td>

                ${formatearFecha(
                    cotizacion.fecha_cotizacion
                )}

            </td>


            <td>

                ${formatearFecha(
                    cotizacion.fecha_vigencia
                )}

            </td>


            <td class="text-end fw-semibold">

                ${formatearMoneda(
                    cotizacion.total
                )}

            </td>


            <td class="text-center">

                ${obtenerBadgeEstatusCotizacion(
                    estatus
                )}

            </td>


            <td class="text-end">

                ${construirAccionesCotizacion(
                    clave,
                    folio,
                    estatus
                )}

            </td>

        </tr>
    `;
}


/* ============================================================
   ACCIONES
============================================================ */

function construirAccionesCotizacion(
    clave,
    folio,
    estatus
) {

    const puedeEditar =
        [
            "BORRADOR",
            "EN_AJUSTE"

        ].includes(
            estatus
        );


    const puedeEnviar =
        estatus === "BORRADOR";


    return `

        <div
            class="dropdown d-inline-block">

            <button
                class="btn btn-soft-secondary btn-sm dropdown-toggle"
                type="button"
                data-bs-toggle="dropdown"
                aria-expanded="false">

                <i class="ri-more-2-fill"></i>

            </button>


            <ul
                class="dropdown-menu dropdown-menu-end">


                <li>

                    <button
                        type="button"
                        class="dropdown-item"
                        data-action="ver-cotizacion"
                        data-clave="${clave}">

                        <i
                            class="ri-eye-line align-bottom me-2 text-muted">
                        </i>

                        Ver detalle

                    </button>

                </li>


                <li>

                    <button
                        type="button"
                        class="dropdown-item"
                        data-action="editar-cotizacion"
                        data-clave="${clave}"
                        ${!puedeEditar
                            ? "disabled"
                            : ""}>

                        <i
                            class="ri-edit-line align-bottom me-2 text-muted">
                        </i>

                        Editar

                    </button>

                </li>


                <li>

                    <button
                        type="button"
                        class="dropdown-item"
                        data-action="imprimir-cotizacion"
                        data-clave="${clave}">

                        <i
                            class="ri-file-pdf-2-line align-bottom me-2 text-muted">
                        </i>

                        Imprimir PDF

                    </button>

                </li>


                ${
                    puedeEnviar
                        ? `

                            <li>

                                <button
                                    type="button"
                                    class="dropdown-item"
                                    data-action="enviar-cotizacion"
                                    data-clave="${clave}"
                                    data-folio="${folio}">

                                    <i
                                        class="ri-send-plane-line align-bottom me-2 text-muted">
                                    </i>

                                    Enviar al distribuidor

                                </button>

                            </li>

                        `
                        : ""
                }

            </ul>

        </div>
    `;
}


/* ============================================================
   CLICK ACCIONES
============================================================ */

function manejarAccionesCotizacion(event)
{
    const boton =
        event.target.closest(
            "[data-action]"
        );


    if (!boton) {
        return;
    }


    const accion =
        boton.dataset.action;

    const clave =
        boton.dataset.clave || "";


    switch (accion) {

        case "ver-cotizacion":

            window.location.href =
                `${base_url}/ped_cotizaciones/ver/${encodeURIComponent(clave)}`;

            break;


        case "editar-cotizacion":

            window.location.href =
                `${base_url}/ped_cotizaciones/editar/${encodeURIComponent(clave)}`;

            break;


        case "imprimir-cotizacion":

            imprimirCotizacion(
                clave
            );

            break;


        case "enviar-cotizacion":

            confirmarEnvioCotizacion(
                clave,
                boton.dataset.folio || ""
            );

            break;

    }
}


/* ============================================================
   DASHBOARD
============================================================ */

async function cargarDashboardCotizaciones()
{
    try {

        const respuesta =
            await fetch(
                `${base_url}/ped_cotizaciones/getDashboard`,
                {
                    headers: {
                        "Accept":
                            "application/json"
                    }
                }
            );


        const resultado =
            await respuesta.json();


        if (!resultado.status) {
            return;
        }


        const datos =
            resultado.data || {};


        colocarTexto(
            "indicadorTotal",
            datos.total_cotizaciones || 0
        );

        colocarTexto(
            "indicadorPendientes",
            datos.pendientes || 0
        );

        colocarTexto(
            "indicadorAjuste",
            datos.en_ajuste || 0
        );

        colocarTexto(
            "indicadorAceptadas",
            datos.aceptadas || 0
        );

        colocarTexto(
            "indicadorRechazadas",
            datos.rechazadas || 0
        );

        colocarTexto(
            "indicadorValorCotizado",
            formatearMoneda(
                datos.valor_cotizado || 0
            )
        );

        colocarTexto(
            "indicadorValorAceptado",
            formatearMoneda(
                datos.valor_aceptado || 0
            )
        );


    } catch (error) {

        console.error(
            "Error dashboard cotizaciones:",
            error
        );

    }
}


/* ============================================================
   BADGE TIPO
============================================================ */

function obtenerBadgeTipoCotizacion(tipo)
{
    if (tipo === "PEDIDO") {

        return `
            <span
                class="badge bg-primary-subtle text-primary">

                <i class="ri-shopping-bag-3-line me-1"></i>

                Pedido

            </span>
        `;

    }


    return `
        <span
            class="badge bg-info-subtle text-info">

            <i class="ri-file-add-line me-1"></i>

            Directa

        </span>
    `;
}


/* ============================================================
   BADGE ESTATUS
============================================================ */

function obtenerBadgeEstatusCotizacion(estatus)
{
    const configuracion = {

        BORRADOR: [
            "secondary",
            "Borrador"
        ],

        PENDIENTE_RESPUESTA: [
            "warning",
            "Pendiente respuesta"
        ],

        EN_AJUSTE: [
            "info",
            "En ajuste"
        ],

        ACEPTADA: [
            "success",
            "Aceptada"
        ],

        RECHAZADA: [
            "danger",
            "Rechazada"
        ],

        VENCIDA: [
            "dark",
            "Vencida"
        ],

        CANCELADA: [
            "danger",
            "Cancelada"
        ]

    };


    const datos =
        configuracion[estatus]
        || [
            "secondary",
            estatus
        ];


    return `
        <span
            class="badge bg-${datos[0]}-subtle text-${datos[0]}">

            ${escaparHtml(
                datos[1]
            )}

        </span>
    `;
}


/* ============================================================
   LIMPIAR FILTROS
============================================================ */

function limpiarFiltrosCotizaciones()
{
    [
        "filterSearch",
        "filterDesde",
        "filterHasta",
        "filterTipo",
        "filterEstatus"

    ].forEach(

        function (id) {

            const elemento =
                document.getElementById(
                    id
                );

            if (elemento) {

                elemento.value = "";

            }

        }

    );


    cargarCotizaciones();
}


/* ============================================================
   LOADING
============================================================ */

function mostrarCargandoCotizaciones()
{
    const tbody =
        document.getElementById(
            "tbodyCotizaciones"
        );


    if (!tbody) {
        return;
    }


    tbody.innerHTML = `

        <tr>

            <td
                colspan="9"
                class="text-center py-5 text-muted">

                <div
                    class="spinner-border spinner-border-sm me-2"
                    role="status">
                </div>

                Consultando cotizaciones...

            </td>

        </tr>
    `;
}


/* ============================================================
   ERROR
============================================================ */

function mostrarErrorCotizaciones(mensaje)
{
    const tbody =
        document.getElementById(
            "tbodyCotizaciones"
        );


    if (!tbody) {
        return;
    }


    tbody.innerHTML = `

        <tr>

            <td
                colspan="9"
                class="text-center py-5">

                <i
                    class="ri-error-warning-line fs-1 text-danger d-block mb-2">
                </i>

                <h6>
                    No fue posible cargar las cotizaciones
                </h6>

                <span class="text-muted">

                    ${escaparHtml(
                        mensaje
                    )}

                </span>

            </td>

        </tr>
    `;
}


/* ============================================================
   UTILIDADES
============================================================ */

function colocarTexto(
    id,
    valor
) {

    const elemento =
        document.getElementById(
            id
        );


    if (elemento) {

        elemento.textContent =
            valor;

    }
}


function formatearMoneda(valor)
{
    const numero =
        Number(
            valor || 0
        );


    return new Intl.NumberFormat(
        "es-MX",
        {
            style: "currency",
            currency: "MXN"
        }
    ).format(
        numero
    );
}


function formatearFecha(fecha)
{
    if (!fecha) {

        return "-";

    }


    const parteFecha =
        String(fecha)
            .substring(
                0,
                10
            );


    const partes =
        parteFecha.split("-");


    if (partes.length !== 3) {

        return escaparHtml(
            fecha
        );

    }


    return `${partes[2]}/${partes[1]}/${partes[0]}`;
}


function escaparHtml(valor)
{
    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        valor ?? "";


    return div.innerHTML;
}


/* ============================================================
   FUNCIONES PREPARADAS PARA SIGUIENTE ETAPA
============================================================ */

function imprimirCotizacion(clave)
{
    window.open(
        `${base_url}/ped_cotizaciones/pdf/${encodeURIComponent(clave)}`,
        "_blank"
    );
}


async function confirmarEnvioCotizacion(
    clave,
    folio
) {

    const resultado =
        await Swal.fire({

            title:
                "¿Enviar cotización?",

            html: `

                <div class="text-center">

                    <p>

                        Se enviará la cotización

                        <strong>
                            ${escaparHtml(folio)}
                        </strong>

                        al distribuidor para su revisión.

                    </p>

                    <p class="text-muted mb-0">

                        Una vez enviada, esta versión
                        quedará registrada en el historial
                        comercial.

                    </p>

                </div>
            `,

            icon:
                "question",

            showCancelButton:
                true,

            confirmButtonText:
                "Sí, enviar",

            cancelButtonText:
                "Cancelar",

            reverseButtons:
                true

        });


    if (!resultado.isConfirmed) {
        return;
    }


    Swal.fire({

        title:
            "Función preparada",

        text:
            "El proceso de envío se implementará en la siguiente etapa.",

        icon:
            "info"

    });
}