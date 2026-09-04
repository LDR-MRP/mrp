"use strict";

/* ============================================================
 * PDIDOS - ADMINISTRACIÓN
 * ============================================================ */

/*
 * ============================================================
 * CONFIGURCIÓN GENERAL
 * ============================================================
 */

const PEDIDOS_POR_PAGINA = 10;
let paginaActualPedidos = 1;
let cargandoPedidos = false;
let temporizadorBusquedaPedidos = null;


document.addEventListener(
    "DOMContentLoaded",
    function () {
        inicializarModuloPedidos();
    }
);

/*
 * ============================================================
 * INICIALIZAR MÓDULO
 * ============================================================
 */

async function inicializarModuloPedidos() {

    configurarEventosPedidos();
    await cargarDistribuidoresPedidos();
    await cargarPedidos();

}


/*
 * ============================================================
 * CONFIGURAR EVENTOS
 * ============================================================
 */

function configurarEventosPedidos() {

    /*
     * ========================================================
     * BOTÓN BUSCAR
     * ========================================================
     */

    const btnAplicarFiltros =document.getElementById("btnAplicarFiltros");

    btnAplicarFiltros
        ?.addEventListener(
            "click",
            function () {
                paginaActualPedidos = 1;
                cargarPedidos();

            }
        );


    /*
     * ========================================================
     * LIMPIAR FILTROS
     * ========================================================
     */

    const btnLimpiarFiltros =document.getElementById("btnLimpiarFiltros");
    btnLimpiarFiltros
        ?.addEventListener(
            "click",
            function () {
                limpiarFiltrosPedidos();
            }
        );


    /*
     * ========================================================
     * REFRESCAR
     * ========================================================
     */

    const btnRefrescar =document.getElementById("btnRefrescarPedidos");
    btnRefrescar
        ?.addEventListener(
            "click",
            async function () {
                await refrescarListadoPedidos(this);
            }
        );


    /*
     * ========================================================
     * BUSCADOR
     * ========================================================
     */

    const buscador =document.getElementById("filterSearch");
    buscador
        ?.addEventListener(
            "input",
            function () {

                clearTimeout(temporizadorBusquedaPedidos);
                temporizadorBusquedaPedidos =
                    setTimeout(
                        function () {
                            paginaActualPedidos = 1;
                            cargarPedidos();

                        },
                        500
                    );

            }
        );


    /*
     * ========================================================
     * ENTER EN BUSCADOR
     * ========================================================
     */

    buscador
        ?.addEventListener(
            "keydown",
            function (event) {

                if (event.key !== "Enter") {
                    return;
                }

                event.preventDefault();
                clearTimeout(temporizadorBusquedaPedidos);
                paginaActualPedidos = 1;
                cargarPedidos();

            }
        );


    /*
     * ========================================================
     * FILTRO ESTATUS
     * ========================================================
     */

    document.getElementById("filterEstatus")
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                cargarPedidos();

            }
        );


    /*
     * ========================================================
     * FILTRO PRIORIDAD
     * ========================================================
     */

    document.getElementById("filterPrioridad")
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                cargarPedidos();
            }
        );


    /*
     * ========================================================
     * FILTRO DISTRIBUIDOR
     * ========================================================
     */

    document.getElementById("filterDistribuidor")
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                cargarPedidos();

            }
        );


    /*
     * ========================================================
     * FECHA DESDE
     * ========================================================
     */

    document.getElementById("filterDesde")
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                validarRangoFechasPedidos();
            }
        );


    /*
     * ========================================================
     * FECHA HASTA
     * ========================================================
     */

    document.getElementById("filterHasta")
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                validarRangoFechasPedidos();
            }
        );


    /*
     * ========================================================
     * FECHA REQUERIDA
     * ========================================================
     */

    document.getElementById("filterFechaRequerida")
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                cargarPedidos();
            }
        );


    /*
     * ========================================================
     * MES FACTURACIÓN
     * ========================================================
     */

    document.getElementById(
            "filterMesFacturacion"
        )
        ?.addEventListener(
            "change",
            function () {
                paginaActualPedidos = 1;
                cargarPedidos();
            }
        );


/* ============================================================
 * ACCIONES DEL LISTADO
 * ============================================================ */

document.addEventListener(
    "click",
    function (event) {

        /*
         * ========================================================
         * VER DETALLE
         * ========================================================
         */

        const btnDetalle =event.target.closest('[data-action="ver-pedido"]');

        if (btnDetalle) {
            abrirDetallePedido(btnDetalle);
            return;
        }

        /*
         * ========================================================
         * IMPRIMIR PEDIDO
         * ========================================================
         */

        const btnImprimir =event.target.closest('[data-action="imprimir-pedido"]');

        if (btnImprimir) {
            imprimirPedidoDesdeAdministracion(
                btnImprimir
            );
            return;
        }


        /*
         * ========================================================
         * GESTIONAR PEDIDO
         * ========================================================
         */

        const btnGestionar =
            event.target.closest(
                '[data-action="gestionar-pedido"]'
            );


        if (
            btnGestionar
        ) {

            abrirGestionPedido(
                btnGestionar
            );

            return;

        }


        /*
         * ========================================================
         * PAGINACIÓN
         * ========================================================
         */

        const btnPagina =event.target.closest("[data-pagina-pedido]");
        if (btnPagina) {
            cambiarPaginaPedidos(btnPagina);
            return;
        }

    }
);
}

/*
 * ============================================================
 * CARGAR PEDIDOS
 * ============================================================
 */

async function cargarPedidos() {

    if (cargandoPedidos) {
        return;
    }

    cargandoPedidos = true;
    mostrarCargandoPedidos(true);
    ocultarSinResultadosPedidos();


    try {

        /*
         * ======================================================
         * OBTENER FILTROS
         * ======================================================
         */

        const filtros =obtenerFiltrosPedidos();

        /*
         * ======================================================
         * ARMAR URL
         * ======================================================
         */

        const parametros =new URLSearchParams();

        parametros.append("pagina",filtros.pagina);
        parametros.append("limite",filtros.limite);
        if (filtros.busqueda) {
            parametros.append("busqueda",filtros.busqueda);
        }

        if (filtros.estatus) {
            parametros.append("estatus",filtros.estatus);
        }

        if (filtros.prioridad) {
            parametros.append("prioridad",filtros.prioridad);
        }

        if (filtros.idcliente) {
            parametros.append("idcliente",filtros.idcliente);
        }


        if (filtros.desde) {
            parametros.append("desde",filtros.desde);
        }

        if (filtros.hasta) {
            parametros.append("hasta",filtros.hasta);
        }

        if (filtros.fecha_requerida) {
            parametros.append("fecha_requerida",filtros.fecha_requerida);
        }

        if (filtros.mes_facturacion) {
            parametros.append("mes_facturacion",filtros.mes_facturacion);
        }

        /*
         * ======================================================
         * CONSULTA
         * ======================================================
         */

        const response =
            await fetch(`${base_url}/ped_pedidos/getPedidos?${parametros.toString()}`,
                {
                    method:"GET",
                    headers: {
                        "Accept":"application/json"
                    },
                    cache:"no-store"
                }
            );


        /*
         * ======================================================
         * VALIDAR RESPUESTA
         * ======================================================
         */

        const resultado =await procesarRespuestaJsonPedidos(
                response
            );

        if (!response.ok || !resultado.status) {

            throw new Error(
                resultado.message
                || "No fue posible obtener los pedidos."
            );
        }

        /*
         * ======================================================
         * INFORMACIÓN RECIBIDA
         * ======================================================
         */

        const data =resultado.data || {};
        const pedidos =Array.isArray(data.pedidos)? data.pedidos : [];
        const paginacion =data.paginacion || {};
        const indicadores =data.indicadores || {};

        /*
         * ======================================================
         * RENDERIZAR
         * ======================================================
         */

        renderizarPedidos(pedidos);
        actualizarIndicadoresPedidos(indicadores);
        renderizarPaginacionPedidos(paginacion);
        actualizarCantidadRegistrosPedidos(paginacion.total_registros ?? pedidos.length);

        if (pedidos.length === 0) {
            mostrarSinResultadosPedidos();
        }

    } catch (error) {

        console.error(
            "Error cargarPedidos:",
            error
        );

        limpiarTablaPedidos();
        actualizarIndicadoresPedidos({});
        actualizarCantidadRegistrosPedidos(0);
        renderizarPaginacionPedidos({});
        mostrarAlertaPedidos(
            error.message
            || "No fue posible cargar los pedidos.",
            "error"
        );

    } finally {
        mostrarCargandoPedidos(false);
        cargandoPedidos =false;
    }

}

/*
 * ============================================================
 * OBTENER FILTROS
 * ============================================================
 */

function obtenerFiltrosPedidos() {

    return {
        pagina:paginaActualPedidos,
        limite:PEDIDOS_POR_PAGINA,
        busqueda:obtenerValorElementoPedidos("filterSearch"),
        estatus:obtenerValorElementoPedidos("filterEstatus").toUpperCase(),
        prioridad:obtenerValorElementoPedidos("filterPrioridad").toUpperCase(),
        idcliente:obtenerValorElementoPedidos("filterDistribuidor"),
        desde:obtenerValorElementoPedidos("filterDesde"),
        hasta:obtenerValorElementoPedidos("filterHasta"),
        fecha_requerida:obtenerValorElementoPedidos("filterFechaRequerida"),
        mes_facturacion:obtenerValorElementoPedidos("filterMesFacturacion")
    };

}

/*
 * ============================================================
 * OBTENER VALOR DE ELEMENTO
 * ============================================================
 */

function obtenerValorElementoPedidos(idElemento) {
    const elemento =document.getElementById(idElemento);

    if (!elemento) {
        return "";
    }

    return String(
        elemento.value
        || ""
    ).trim();

}

/*
 * ============================================================
 * RENDERIZAR PEDIDOS
 * ============================================================
 */

function renderizarPedidos(pedidos) {

    const tbody =document.getElementById("tbodyPedidos");
    if (!tbody) {
        return;
    }

    tbody.innerHTML ="";

    if (!Array.isArray(pedidos) || pedidos.length === 0) {

        return;
    }

    const fragmento =document.createDocumentFragment();

    pedidos.forEach(
        pedido => {
            const fila =crearFilaPedido(pedido);
            fragmento.appendChild(fila);
        }
    );

    tbody.appendChild(fragmento);
}


/*
 * ============================================================
 * CREAR FILA PEDIDO
 * ============================================================
 */

function crearFilaPedido(pedido) {

    const tr =document.createElement("tr");
    const clave =String(pedido.clave || "");
    const folio =String(pedido.folio_pedido || "Sin folio");
    const distribuidor = pedido.nombre_comercial || pedido.razon_social || "Sin distribuidor";
    const codigoDistribuidor =pedido.clave_distribuidor || pedido.codigo_cliente || "";
    const solicitante =obtenerNombreSolicitantePedido(pedido);
    const cantidadUnidades =Number(pedido.total_unidades || 0);
    const estatus =String(pedido.estatus || "").toUpperCase();
    const prioridad =String(pedido.prioridad || "").toUpperCase();
    const textoAccion =estatus === "PENDIENTE" ? "Gestionar" : "Ver detalle";
    const iconoAccion = estatus === "PENDIENTE" ? "ri-play-circle-line" : "ri-eye-line";
    tr.dataset.clave =clave;
    tr.dataset.estatus =estatus;

    tr.innerHTML = `

        <!-- ==================================================
             FOLIO
        =================================================== -->
        <td>

            <div class="fw-semibold text-body">
                ${escaparHtmlPedidos(folio)}
            </div>

            ${
                clave
                    ? `
                        <div class="text-muted fs-12">
                            ${escaparHtmlPedidos(clave)}
                        </div>
                    `
                    : ""
            }

        </td>


        <!-- ==================================================
             DISTRIBUIDOR
        =================================================== -->
        <td>

            <div>

                <div class="fw-medium text-body">
                    ${escaparHtmlPedidos(distribuidor)}
                </div>

                ${
                    codigoDistribuidor
                        ? `
                            <div class="text-muted fs-12">
                                ${escaparHtmlPedidos(codigoDistribuidor)}
                            </div>
                        `
                        : ""
                }

                ${
                    solicitante
                        ? `
                            <div class="text-muted fs-12 mt-1">

                                <i class="ri-user-line me-1"></i>

                                ${escaparHtmlPedidos(solicitante)}

                            </div>
                        `
                        : ""
                }

            </div>

        </td>


        <!-- ==================================================
             FECHA PEDIDO
        =================================================== -->
        <td>

            ${formatearFechaPedidos(
                pedido.fecha_pedido
                || pedido.fecha_creacion
            )}

        </td>


        <!-- ==================================================
             FECHA REQUERIDA
        =================================================== -->
        <td>

            ${
                pedido.fecha_requerida
                    ? formatearFechaPedidos(
                        pedido.fecha_requerida
                    )
                    : `
                        <span class="text-muted">
                            No especificada
                        </span>
                    `
            }

        </td>


        <!-- ==================================================
             UNIDADES
        =================================================== -->
        <td class="text-center">

            <span
                class="badge bg-primary-subtle text-primary fs-12">

                ${cantidadUnidades}

            </span>

        </td>


        <!-- ==================================================
             TOTAL
        =================================================== -->
        <td class="text-end">

            <span class="fw-semibold text-body">

                ${formatearMonedaPedidos(
                    pedido.total
                )}

            </span>

        </td>


        <!-- ==================================================
             PRIORIDAD
        =================================================== -->
        <td class="text-center">

            ${obtenerBadgePrioridadPedido(
                prioridad
            )}

        </td>


        <!-- ==================================================
             ESTATUS
        =================================================== -->
        <td class="text-center">

            ${obtenerBadgeEstatusPedido(
                estatus
            )}

        </td>


        <!-- ==================================================
             ACTUALIZACIÓN
        =================================================== -->
        <td>

            ${
                pedido.fecha_ultima_modificacion
                || pedido.fecha_actualizacion
                    ? formatearFechaPedidos(
                        pedido.fecha_ultima_modificacion
                        || pedido.fecha_actualizacion,
                        true
                    )
                    : `
                        <span class="text-muted">
                            Sin actualización
                        </span>
                    `
            }

        </td>


        <!-- ==================================================
  ACCIONES
  =================================================== -->

<td class="text-end">

    <div class="d-flex justify-content-end gap-2">

        <!-- VER DETALLE -->
        <button
            type="button"
            class="btn btn-sm btn-soft-secondary"
            data-action="ver-pedido"
            data-clave="${escaparHtmlPedidos(clave)}"
            title="Ver detalle del pedido">

            <i class="ri-eye-line"></i>

        </button>


        <!-- IMPRIMIR PEDIDO -->
        <button
            type="button"
            class="btn btn-sm btn-soft-info"
            data-action="imprimir-pedido"
            data-clave="${escaparHtmlPedidos(clave)}"
            title="Imprimir pedido">

            <i class="ri-printer-line"></i>

        </button>


        <!-- GESTIONAR -->
        ${generarBotonGestionPedido(
            pedido
        )}

    </div>

</td>
    `;


    return tr;

}

/*
 * ============================================================
 * NOMBRE DEL SOLICITANTE
 * ============================================================
 */

function obtenerNombreSolicitantePedido(pedido) {

    const nombre =String(pedido.nombre_usuario || "").trim();
    const apellido =String(pedido.apellido_usuario || "").trim();
    return `${nombre} ${apellido}`.trim();

}


/*
 * ============================================================
 * BADGE ESTATUS
 * ============================================================
 */

function obtenerBadgeEstatusPedido(estatus) {

    const valor =
        String(
            estatus
            || ""
        ).toUpperCase();


    const configuracion = {

        PENDIENTE: {
            clase:"bg-warning-subtle text-warning",
            texto:"Pendiente",
            icono:"ri-time-line"
        },

        EN_REVISION: {
            clase:"bg-info-subtle text-info",
            texto:"En revisión",
            icono:"ri-search-eye-line"
        },

        AUTORIZADO: {
            clase:"bg-success-subtle text-success",
            texto:"Autorizado",
            icono:"ri-checkbox-circle-line"
        },

        RECHAZADO: {
            clase:"bg-danger-subtle text-danger",
            texto:"Rechazado",
            icono:"ri-close-circle-line"
        },

        CANCELADO: {
            clase:"bg-danger-subtle text-danger",
            texto:"Cancelado",
            icono:"ri-forbid-line"
        },

        FINALIZADO: {
            clase:"bg-success-subtle text-success",
            texto:"Finalizado",
            icono:"ri-check-double-line"
        }

    };


    const item =configuracion[valor] || {
            clase:"bg-secondary-subtle text-secondary",
            texto:valor || "Sin estatus",
            icono:"ri-information-line"
        };


    return `

        <span
            class="badge ${item.clase} fs-12">
            <i class="${item.icono} me-1"></i>
            ${escaparHtmlPedidos(item.texto)}
        </span>

    `;

}


/*
 * ============================================================
 * BADGE PRIORIDAD
 * ============================================================
 */

function obtenerBadgePrioridadPedido(prioridad) {

    const valor =
        String(
            prioridad
            || ""
        ).toUpperCase();


    const configuracion = {

        BAJA: {
            clase:"bg-secondary-subtle text-secondary",
            texto:"Baja"
        },

        MEDIA: {
            clase:"bg-info-subtle text-info",
            texto:"Media"
        },

        ALTA: {
            clase:"bg-warning-subtle text-warning",
            texto:"Alta"
        },

        URGENTE: {
            clase:"bg-danger-subtle text-danger",
            texto:"Urgente"
        }

    };

    const item =
        configuracion[valor]
        || {
            clase:"bg-secondary-subtle text-secondary",
            texto:valor || "Normal"
        };


    return `

        <span
            class="badge ${item.clase}">
            ${escaparHtmlPedidos(
                item.texto
            )}
        </span>

    `;

}

/*
 * ============================================================
 * CARGAR DISTRIBUIDORES
 * ============================================================
 */

async function cargarDistribuidoresPedidos() {

    const select =document.getElementById("filterDistribuidor");

    if (!select) {
        return;
    }


    try {
        select.disabled =true;

        const response =
            await fetch(
                `${base_url}/ped_pedidos/getDistribuidores`,
                {
                    method:"GET",
                    headers: {
                        "Accept":"application/json"
                    },
                    cache:"no-store"
                }
            );


        const resultado =await procesarRespuestaJsonPedidos(response);

        if (!response.ok || !resultado.status) {

            throw new Error(
                resultado.message
                || "No fue posible obtener los distribuidores."
            );

        }

        const distribuidores =
            Array.isArray(
                resultado.data
            )
                ? resultado.data
                : (
                    Array.isArray(
                        resultado.data?.distribuidores
                    )
                        ? resultado.data.distribuidores
                        : []
                );

        llenarSelectDistribuidoresPedidos(distribuidores);

    } catch (error) {

        console.error(
            "Error cargarDistribuidoresPedidos:",
            error
        );

    } finally {
        select.disabled =false;
    }

}

/*
 * ============================================================
 * LLENAR SELECT DISTRIBUIDORES
 * ============================================================
 */

function llenarSelectDistribuidoresPedidos(distribuidores) {
    const select =document.getElementById("filterDistribuidor");
    if (!select) {
        return;
    }


    let html = `
        <option value="">
            Todos los distribuidores
        </option>
    `;


    distribuidores.forEach(
        distribuidor => {

            const idcliente =Number(distribuidor.idcliente || 0);
            if (idcliente <= 0) {
                return;
            }


            const nombre =
                distribuidor.nombre_comercial
                || distribuidor.razon_social
                || distribuidor.codigo_cliente
                || `Cliente ${idcliente}`;


            html += `

                <option
                    value="${idcliente}">

                    ${escaparHtmlPedidos(
                        nombre
                    )}

                </option>

            `;

        }
    );

    select.innerHTML =html;
}

/*
 * ============================================================
 * INDICADORES
 * ============================================================
 */

function actualizarIndicadoresPedidos(indicadores) {

    const totalPedidos =Number(indicadores.total_pedidos || 0);
    const pendientes =Number(indicadores.pendientes || 0);
    const enRevision =Number(indicadores.en_revision || 0);
    const importe =Number(indicadores.importe_total || 0);
    actualizarTextoElementoPedidos(
        "statTotalPedidos",
        totalPedidos.toLocaleString(
            "es-MX"
        )
    );


    actualizarTextoElementoPedidos(
        "statPendientes",
        pendientes.toLocaleString(
            "es-MX"
        )
    );


    actualizarTextoElementoPedidos(
        "statEnRevision",
        enRevision.toLocaleString(
            "es-MX"
        )
    );


    actualizarTextoElementoPedidos(
        "statImportePedidos",
        formatearMonedaPedidos(
            importe
        )
    );

}


/*
 * ============================================================
 * ACTUALIZAR TEXTO ELEMENTO
 * ============================================================
 */

function actualizarTextoElementoPedidos(idElemento,valor) {
    const elemento =document.getElementById(idElemento);

    if (elemento) {
        elemento.textContent =valor;

    }
}

/*
 * ============================================================
 * ACTUALIZAR TOTAL REGISTROS
 * ============================================================
 */

function actualizarCantidadRegistrosPedidos(total) {

    const elemento =document.getElementById("badgeTotalRegistros");
    if (!elemento) {
        return;
    }
    const cantidad =Number(total || 0);
    elemento.textContent =
        `${cantidad.toLocaleString(
            "es-MX"
        )} ${
            cantidad === 1
                ? "registro"
                : "registros"
        }`;

}

/*
 * ============================================================
 * PAGINACIÓN
 * ============================================================
 */

function renderizarPaginacionPedidos(paginacion) {

    const contenedor =document.getElementById("paginationPedidos");
    const info =document.getElementById("infoPaginacionPedidos");
    const footer =document.getElementById("footerPaginacionPedidos");
    if (!contenedor) {
        return;
    }

    const paginaActual =Number(paginacion.pagina_actual || 1);
    const totalPaginas =Number(paginacion.total_paginas || 0);
    const totalRegistros =Number(paginacion.total_registros || 0);
    const limite =Number(paginacion.limite || PEDIDOS_POR_PAGINA);
    paginaActualPedidos = paginaActual;

    /*
     * ========================================================
     * INFORMACIÓN
     * ========================================================
     */

    if (info) {
        if (totalRegistros === 0) {
            info.textContent ="Mostrando 0 registros";
        } else {

            const inicio =((paginaActual - 1) * limite) + 1;
            const fin =Math.min(paginaActual * limite,totalRegistros);
            info.innerHTML = `

                Mostrando
                <strong>
                    ${inicio}
                </strong>

                a
                <strong>
                    ${fin}
                </strong>

                de

                <strong>
                    ${totalRegistros}
                </strong>

                pedidos

            `;

        }

    }


    /*
     * ========================================================
     * FOOTER
     * ========================================================
     */

    if (footer) {

        footer.style.display =
            totalRegistros > 0
                ? ""
                : "none";

    }

    /*
     * ========================================================
     * SIN PÁGINAS
     * ========================================================
     */

    if (totalPaginas <= 1) {
        contenedor.innerHTML ="";
        return;
    }

    let html ="";

    /*
     * ========================================================
     * ANTERIOR
     * ========================================================
     */

    html += `

        <li
            class="page-item ${
                paginaActual <= 1
                    ? "disabled"
                    : ""
            }">

            <button
                type="button"
                class="page-link"
                data-pagina-pedido="${paginaActual - 1}"
                ${
                    paginaActual <= 1
                        ? "disabled"
                        : ""
                }>

                <i class="ri-arrow-left-s-line"></i>

            </button>

        </li>

    `;

    /*
     * ========================================================
     * NÚMEROS
     * ========================================================
     */

    const paginas =obtenerPaginasVisiblesPedidos(paginaActual,totalPaginas);

    paginas.forEach(
        pagina => {
            if (
                pagina === "..."
            ) {

                html += `

                    <li class="page-item disabled">

                        <span class="page-link">
                            ...
                        </span>

                    </li>

                `;

                return;

            }

            html += `

                <li
                    class="page-item ${
                        pagina === paginaActual
                            ? "active"
                            : ""
                    }">

                    <button
                        type="button"
                        class="page-link"
                        data-pagina-pedido="${pagina}">

                        ${pagina}

                    </button>

                </li>

            `;

        }
    );

    /*
     * ========================================================
     * SIGUIENTE
     * ========================================================
     */

    html += `

        <li
            class="page-item ${
                paginaActual >= totalPaginas
                    ? "disabled"
                    : ""
            }">

            <button
                type="button"
                class="page-link"
                data-pagina-pedido="${paginaActual + 1}"
                ${
                    paginaActual >= totalPaginas
                        ? "disabled"
                        : ""
                }>

                <i class="ri-arrow-right-s-line"></i>

            </button>

        </li>

    `;

    contenedor.innerHTML =html;
}

/*
 * ============================================================
 * PAGINAS VISIBLES
 * ============================================================
 */

function obtenerPaginasVisiblesPedidos(paginaActual,totalPaginas) {

    if (totalPaginas <= 7) {

        return Array.from(
            {
                length:totalPaginas
            },
            (_, index) =>
                index + 1
        );

    }

    const paginas =[1];

    if (paginaActual > 4) {
        paginas.push(
            "..."
        );
    }

    const inicio =Math.max(2,paginaActual - 1);
    const fin =Math.min(totalPaginas - 1,paginaActual + 1);

    for (let pagina = inicio;pagina <= fin;pagina++) {
        paginas.push(pagina);
    }

    if (paginaActual < totalPaginas - 3) {
        paginas.push(
            "..."
        );
    }

    paginas.push(totalPaginas);
    return paginas;
}

/*
 * ============================================================
 * CAMBIAR PÁGINA
 * ============================================================
 */

function cambiarPaginaPedidos(boton) {

    if (boton.disabled) {
        return;
    }
    const pagina =Number(boton.dataset.paginaPedido || 0);

    if (pagina <= 0|| pagina === paginaActualPedidos) {

        return;
    }

    paginaActualPedidos =pagina;
    cargarPedidos();
    document
        .getElementById(
            "tablaPedidos"
        )
        ?.scrollIntoView({
            behavior:
                "smooth",

            block:
                "start"
        });

}

/*
 * ============================================================
 * LIMPIAR FILTROS
 * ============================================================
 */

async function limpiarFiltrosPedidos() {

    const ids = [

        "filterSearch",
        "filterEstatus",
        "filterPrioridad",
        "filterDistribuidor",
        "filterDesde",
        "filterHasta",
        "filterFechaRequerida",
        "filterMesFacturacion"
    ];


    ids.forEach(
        id => {

            const elemento =document.getElementById(id);
            if (elemento) {
                elemento.value ="";
            }

        }
    );

    paginaActualPedidos =1;
    await cargarPedidos();

}

/*
 * ============================================================
 * VALIDAR RANGO DE FECHAS
 * ============================================================
 */

function validarRangoFechasPedidos() {

    const desde =obtenerValorElementoPedidos("filterDesde");
    const hasta =obtenerValorElementoPedidos("filterHasta");

    if (desde && hasta && desde > hasta) {

        mostrarAlertaPedidos(
            "La fecha inicial no puede ser mayor a la fecha final.",
            "warning"
        );
        return false;
    }

    cargarPedidos();
    return true;
}

/*
 * ============================================================
 * REFRESCAR LISTADO
 * ============================================================
 */

async function refrescarListadoPedidos(boton) {

    const htmlOriginal =boton?.innerHTML || "";

    if (boton) {
        boton.disabled =true;
        boton.innerHTML = `
            <i class="ri-loader-4-line ri-spin me-1"></i>
            Actualizando...

        `;

    }

    try {

        await cargarDistribuidoresPedidos();
        await cargarPedidos();

    } finally {

        if (boton) {
            boton.disabled =false;
            boton.innerHTML =htmlOriginal;
        }

    }

}

/*
 * ============================================================
 * ABRIR DETALLE
 * ============================================================
 */

function abrirDetallePedido(boton) {

    const clave =
        String(
            boton.dataset.clave
            || ""
        ).trim();

    if (!clave) {
        mostrarAlertaPedidos(
            "No fue posible identificar el pedido.",
            "error"
        );

        return;
    }

    window.location.href =`${base_url}/ped_pedidos/detalle/${encodeURIComponent(clave)}`;

}

/*
 * ============================================================
 * ESTADO CARGANDO
 * ============================================================
 */

function mostrarCargandoPedidos(mostrar) {

    const loading = document.getElementById("pedidosLoading");
    const tabla = document.getElementById("tablaPedidos");
    if (loading) {
        loading.style.display =
            mostrar
                ? ""
                : "none";

    }

    if (tabla) {
        tabla.style.opacity =
            mostrar
                ? "0.45"
                : "1";

    }

}

/*
 * ============================================================
 * SIN RESULTADOS
 * ============================================================
 */

function mostrarSinResultadosPedidos() {

    const elemento =document.getElementById("pedidosSinResultados");
    if (elemento) {
        elemento.style.display ="";
    }

}

/*
 * ============================================================
 * OCULTAR SIN RESULTADOS
 * ============================================================
 */

function ocultarSinResultadosPedidos() {

    const elemento =document.getElementById("pedidosSinResultados");
    if (elemento) {
        elemento.style.display ="none";
    }

}


/*
 * ============================================================
 * LIMPIAR TABLA
 * ============================================================
 */

function limpiarTablaPedidos() {

    const tbody =document.getElementById("tbodyPedidos");
    if (tbody) {
        tbody.innerHTML ="";
    }

}


/*
 * ============================================================
 * PROCESAR RESPUESTA JSON
 * ============================================================
 */

async function procesarRespuestaJsonPedidos(response) {

    const contentType =
        response.headers.get(
            "content-type"
        )
        || "";


    if (!contentType.includes("application/json")) {

        const texto =await response.text();
        console.error("Respuesta del servidor:",texto);
        throw new Error(
            "El servidor devolvió una respuesta no válida."
        );

    }

    return await response.json();
}

/*
 * ============================================================
 * FORMATEAR MONEDA
 * ============================================================
 */

function formatearMonedaPedidos(valor) {

    const cantidad =Number(valor|| 0);

    if (Number.isNaN(cantidad)) {
        return "$0.00";
    }

    return cantidad.toLocaleString(
        "es-MX",
        {
            style:"currency",
            currency:"MXN",
            minimumFractionDigits:2,
            maximumFractionDigits:2
        }
    );
}

/*
 * ============================================================
 * FORMATEAR FECHA
 * ============================================================
 */
function formatearFechaPedidos(fecha,incluirHora = false) {

    if (
        !fecha
        || fecha === "0000-00-00"
        || fecha === "0000-00-00 00:00:00"
    ) {

        return "—";

    }

    const fechaNormalizada =
        String(
            fecha
        ).replace(
            " ",
            "T"
        );


    const date =new Date(fechaNormalizada);

    if (Number.isNaN(date.getTime())) {
        return escaparHtmlPedidos(fecha );
    }

    if (incluirHora) {
        return date.toLocaleString(
            "es-MX",
            {
                day:"2-digit",
                month:"2-digit",
                year:"numeric",
                hour:"2-digit",
                minute:"2-digit",
                hour12:false
            }
        );

    }

    return date.toLocaleDateString(
        "es-MX",
        {
            day:"2-digit",
            month:"2-digit",
            year:"numeric"
        }
    );

}

/*
 * ============================================================
 * ESCAPAR HTML
 * ============================================================
 */

function escaparHtmlPedidos(valor) {

    return String(
        valor
        ?? ""
    )
        .replace(
            /&/g,
            "&amp;"
        )
        .replace(
            /</g,
            "&lt;"
        )
        .replace(
            />/g,
            "&gt;"
        )
        .replace(
            /"/g,
            "&quot;"
        )
        .replace(
            /'/g,
            "&#039;"
        );

}

/*
 * ============================================================
 * ALERTAS
 * ============================================================
 */

function mostrarAlertaPedidos(mensaje,tipo = "info") {

    if (typeof Swal !== "undefined") {
        Swal.fire({

            icon:tipo,
            title:tipo === "error"
                    ? "No fue posible continuar"
                    : tipo === "warning"
                        ? "Revisa la información"
                        : "Información",

            text:mensaje,
            confirmButtonText:"Aceptar"

        });

        return;
    }

    alert(mensaje);
}




/* ============================================================
 * GENERAR BOTÓN DE GESTIÓN
 * ============================================================ */

function generarBotonGestionPedido(
    pedido
) {

    const estatus =
        String(
            pedido.estatus
            || ""
        )
            .trim()
            .toUpperCase();


    const clave =
        String(
            pedido.clave
            || ""
        );


    /*
     * ========================================================
     * PEDIDO PENDIENTE
     * ========================================================
     */

    if (
        estatus === "PENDIENTE"
    ) {

        return `

            <button
                type="button"
                class="btn btn-sm btn-soft-primary"
                data-action="gestionar-pedido"
                data-clave="${escaparHtmlPedidos(clave)}"
                data-estatus="${escaparHtmlPedidos(estatus)}"
                title="Iniciar gestión del pedido">

                <i class="ri-play-circle-line me-1"></i>

                Gestionar

            </button>

        `;

    }


    /*
     * ========================================================
     * PEDIDO EN REVISIÓN
     * ========================================================
     */

    if (
        estatus === "EN_REVISION"
    ) {

        return `

            <button
                type="button"
                class="btn btn-sm btn-soft-primary"
                data-action="gestionar-pedido"
                data-clave="${escaparHtmlPedidos(clave)}"
                data-estatus="${escaparHtmlPedidos(estatus)}"
                title="Continuar gestión del pedido">

                <i class="ri-settings-3-line me-1"></i>

                Continuar

            </button>

        `;

    }


    /*
     * ========================================================
     * PEDIDOS QUE CONTINÚAN EN PROCESO
     * ========================================================
     */

    if (
        estatus === "AUTORIZADO"
        || estatus === "EN_PROCESO"
        || estatus === "ATENDIDO"
    ) {

        return `

            <button
                type="button"
                class="btn btn-sm btn-soft-primary"
                data-action="gestionar-pedido"
                data-clave="${escaparHtmlPedidos(clave)}"
                data-estatus="${escaparHtmlPedidos(estatus)}"
                title="Consultar gestión del pedido">

                <i class="ri-settings-3-line me-1"></i>

                Gestión

            </button>

        `;

    }


    /*
     * ========================================================
     * PEDIDO FINALIZADO
     * ========================================================
     */

    if (
        estatus === "FINALIZADO"
    ) {

        return `

            <button
                type="button"
                class="btn btn-sm btn-soft-success"
                data-action="gestionar-pedido"
                data-clave="${escaparHtmlPedidos(clave)}"
                data-estatus="${escaparHtmlPedidos(estatus)}"
                title="Consultar gestión finalizada">

                <i class="ri-checkbox-circle-line me-1"></i>

                Ver gestión

            </button>

        `;

    }


    /*
     * ========================================================
     * CANCELADO / RECHAZADO
     * ========================================================
     */

    if (
        estatus === "CANCELADO"
        || estatus === "RECHAZADO"
    ) {

        return `

            <button
                type="button"
                class="btn btn-sm btn-soft-secondary"
                disabled
                title="Este pedido ya no puede gestionarse">

                <i class="ri-forbid-line me-1"></i>

                Gestión

            </button>

        `;

    }


    /*
     * ========================================================
     * OTROS ESTATUS
     * ========================================================
     */

    return `

        <button
            type="button"
            class="btn btn-sm btn-soft-secondary"
            data-action="gestionar-pedido"
            data-clave="${escaparHtmlPedidos(clave)}"
            data-estatus="${escaparHtmlPedidos(estatus)}"
            title="Consultar gestión">

            <i class="ri-settings-3-line me-1"></i>

            Gestión

        </button>

    `;

}







/* ============================================================
 * IMPRIMIR PEDIDO DESDE ADMINISTRACIÓN
 * ============================================================ */

async function imprimirPedidoDesdeAdministracion(
    boton
) {

    const clave =
        String(
            boton.dataset.clave
            || ""
        ).trim();


    if (
        !clave
    ) {

        mostrarAlertaPedidos(
            "No fue posible identificar el pedido.",
            "error"
        );

        return;

    }


    const htmlOriginal =
        boton.innerHTML;


    try {

        /*
         * ====================================================
         * BLOQUEAR BOTÓN
         * ====================================================
         */

        boton.disabled =
            true;


        boton.innerHTML = `
            <i class="ri-loader-4-line ri-spin"></i>
        `;


        /*
         * ====================================================
         * GENERAR PDF
         * ====================================================
         */

        await imprimirPedidoPdf(
            clave
        );


    } catch (error) {

        console.error(
            "Error imprimirPedidoDesdeAdministracion:",
            error
        );


        mostrarAlertaPedidos(
            error.message
            || "No fue posible generar el PDF del pedido.",
            "error"
        );


    } finally {

        /*
         * ====================================================
         * RESTAURAR BOTÓN
         * ====================================================
         */

        boton.disabled =
            false;


        boton.innerHTML =
            htmlOriginal;

    }

}



/* ============================================================
 * IMPRIMIR PEDIDO PDF
 * ============================================================
 *
 * Obtiene la información del pedido desde el backend
 * y genera el PDF utilizando pdfMake.
 *
 * @param {string} clave
 * @param {string|null} endpoint
 * ============================================================ */

async function imprimirPedidoPdf(
    clave,
    endpoint = null
) {

    clave =
        String(
            clave
            || ""
        ).trim();


    if (
        !clave
    ) {

        throw new Error(
            "No se recibió la clave del pedido."
        );

    }


    /*
     * ========================================================
     * VALIDAR PDFMAKE
     * ========================================================
     */

    if (
        typeof pdfMake === "undefined"
    ) {

        throw new Error(
            "La librería para generar el PDF no está disponible."
        );

    }


    /*
     * ========================================================
     * ENDPOINT
     * ========================================================
     *
     * Si no mandamos endpoint:
     * utiliza el endpoint administrativo.
     *
     * Ejemplo:
     *
     * /ped_pedidos/getPedidoPdf/ABC123
     * ========================================================
     */

    if (
        !endpoint
    ) {

        endpoint =`${base_url}/orders/getPedidoPdf/${encodeURIComponent(clave)}`;

    }


    try {

        /*
         * ====================================================
         * CONSULTAR INFORMACIÓN DEL PEDIDO
         * ====================================================
         */

        const response =
            await fetch(
                endpoint,
                {
                    method:
                        "GET",

                    headers: {

                        "Accept":
                            "application/json"

                    },

                    cache:
                        "no-store"
                }
            );


        /*
         * ====================================================
         * VALIDAR TIPO DE RESPUESTA
         * ====================================================
         */

        const contentType =
            response.headers.get(
                "content-type"
            )
            || "";


        if (
            !contentType.includes(
                "application/json"
            )
        ) {

            const texto =
                await response.text();


            console.error(
                "Respuesta no JSON:",
                texto
            );


            throw new Error(
                "El servidor devolvió una respuesta no válida."
            );

        }


        /*
         * ====================================================
         * JSON
         * ====================================================
         */

        const resultado =
            await response.json();


        /*
         * ====================================================
         * VALIDAR RESPUESTA
         * ====================================================
         */

        if (
            !response.ok
            || !resultado.status
        ) {

            throw new Error(
                resultado.message
                || resultado.msg
                || "No fue posible obtener la información del pedido."
            );

        }


        /*
         * ====================================================
         * DATOS
         * ====================================================
         */

        const pedido =
            resultado.data?.pedido
            || null;


        const detalles =
            Array.isArray(
                resultado.data?.detalles
            )
                ? resultado.data.detalles
                : [];


        if (
            !pedido
        ) {

            throw new Error(
                "No se encontró la información del pedido."
            );

        }


        /*
         * ====================================================
         * GENERAR PDF
         * ====================================================
         */

        await generarPdfPedido(
            pedido,
            detalles
        );


        return true;


    } catch (error) {

        console.error(
            "Error imprimirPedidoPdf:",
            error
        );


        throw error;

    }

}