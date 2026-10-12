"use strict";

(() => {
    const endpoint = `${base_url}/ped_cotizaciones`;
    let contadorSolicitud = 0;
    let controladorActual = null;
    let temporizadorBusqueda = null;

    const $ = (id) => document.getElementById(id);
    const texto = (id, valor) => { if ($(id)) $(id).textContent = String(valor ?? 0); };
    const escapeHtml = (valor) => String(valor ?? "").replace(/[&<>"']/g, (c) => ({"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"})[c]);
    const dinero = (valor) => new Intl.NumberFormat("es-MX", {style: "currency", currency: "MXN"}).format(Number(valor) || 0);
    const fecha = (valor) => { const s = String(valor || "").slice(0,10); return /^\d{4}-\d{2}-\d{2}$/.test(s) ? `${s.slice(8,10)}/${s.slice(5,7)}/${s.slice(0,4)}` : "—"; };
    const filtros = () => ({
        buscar: $("filterSearch")?.value.trim() || "",
        desde: $("filterDesde")?.value || "",
        hasta: $("filterHasta")?.value || "",
        tipo: $("filterTipo")?.value || "",
        estatus: $("filterEstatus")?.value || ""
    });
    const nombresEstado = {
        BORRADOR: ["secondary", "Borrador"], EN_PROCESO: ["warning", "En proceso"],
        PARCIALMENTE_AUTORIZADA: ["info", "Parcialmente autorizada"], AUTORIZADA: ["success", "Autorizada"],
        PARCIALMENTE_ENVIADA: ["info", "Parcialmente enviada"], ENVIADA: ["primary", "Enviada"],
        PARCIALMENTE_ACEPTADA: ["info", "Parcialmente aceptada"], ACEPTADA: ["success", "Aceptada"],
        CANCELADA: ["danger", "Cancelada"]
    };
    const badgeEstado = (estado) => {
        const [color, titulo] = nombresEstado[estado] || ["secondary", estado || "Sin estatus"];
        return `<span class="badge bg-${color}-subtle text-${color}">${escapeHtml(titulo)}</span>`;
    };
    const badgeOrigen = (origen) => origen === "PEDIDO"
        ? '<span class="badge bg-primary-subtle text-primary">Pedido</span>'
        : '<span class="badge bg-info-subtle text-info">Directa</span>';

    async function consultarJson(url, signal) {
        const response = await fetch(url, {headers: {Accept: "application/json"}, credentials: "same-origin", signal});
        const json = await response.json();
        if (!response.ok || !json.status) throw new Error(json.message || "No fue posible consultar la información.");
        return json.data;
    }
    function fila(propuesta) {
        const clave = String(propuesta.clave || "");
        const valido = /^[a-f0-9]{40}$/i.test(clave);
        const total = Number(propuesta.total_cotizaciones) || 0;
        const autorizadas = Number(propuesta.cotizaciones_autorizadas) || 0;
        const nombre = propuesta.nombre_comercial || propuesta.razon_social || "Sin distribuidor";
        return `<tr>
            <td><span class="fw-semibold text-primary">${escapeHtml(propuesta.folio_propuesta)}</span>${propuesta.folio_pedido ? `<div class="small text-muted">Pedido: ${escapeHtml(propuesta.folio_pedido)}</div>` : ""}</td>
            <td><div class="fw-medium">${escapeHtml(nombre)}</div><small class="text-muted">${escapeHtml(propuesta.codigo_cliente)}</small></td>
            <td>${badgeOrigen(propuesta.tipo_propuesta)}</td>
            <td class="text-center">${total}</td>
            <td class="text-center"><span class="badge bg-secondary-subtle text-secondary">${autorizadas} / ${total}</span></td>
            <td>${fecha(propuesta.fecha_creacion)}</td>
            <td class="text-end fw-semibold">${dinero(propuesta.importe_total)}</td>
            <td class="text-center">${badgeEstado(propuesta.estatus)}</td>
            <td class="text-end"><button type="button" class="btn btn-sm btn-soft-primary" data-action="ver-propuesta" data-clave="${valido ? escapeHtml(clave) : ""}" ${valido ? "" : "disabled"} title="Ver cotizaciones de la propuesta"><i class="ri-eye-line me-1"></i>Ver</button></td>
        </tr>`;
    }
    async function cargarListado() {
        const tbody = $("tbodyPropuestas");
        if (!tbody) return;
        const solicitud = ++contadorSolicitud;
        controladorActual?.abort();
        controladorActual = new AbortController();
        tbody.innerHTML = '<tr><td colspan="9" class="text-center py-5 text-muted"><span class="spinner-border spinner-border-sm me-2"></span>Consultando propuestas...</td></tr>';
        try {
            const data = await consultarJson(`${endpoint}/getPropuestas?${new URLSearchParams(filtros())}`, controladorActual.signal);
            if (solicitud !== contadorSolicitud) return;
            const registros = Array.isArray(data) ? data : [];
            texto("contadorRegistros", `${registros.length} ${registros.length === 1 ? "registro" : "registros"}`);
            tbody.innerHTML = registros.length ? registros.map(fila).join("") : '<tr><td colspan="9" class="text-center text-muted py-5"><i class="ri-file-search-line fs-1 d-block mb-2"></i>No se encontraron propuestas comerciales.</td></tr>';
        } catch (error) {
            if (error.name === "AbortError" || solicitud !== contadorSolicitud) return;
            tbody.innerHTML = `<tr><td colspan="9" class="text-center text-danger py-5">${escapeHtml(error.message)}</td></tr>`;
        }
    }
    async function cargarDashboard() {
        try {
            const d = await consultarJson(`${endpoint}/getDashboardPropuestas`);
            texto("indicadorTotal", d.total_propuestas);
            texto("indicadorBorradores", d.borradores);
            texto("indicadorProceso", d.en_proceso);
            texto("indicadorAutorizadas", d.autorizadas);
            texto("indicadorEnviadas", d.enviadas);
            texto("indicadorAceptadas", d.aceptadas);
            texto("indicadorCanceladas", d.canceladas);
            texto("indicadorValorPropuesto", dinero(d.valor_propuesto));
            texto("indicadorValorAceptado", dinero(d.valor_aceptado));
        } catch (error) { console.error("Dashboard propuestas:", error); }
    }
    function inicializar() {
        if (!$("tbodyPropuestas")) return;
        $("filterSearch")?.addEventListener("input", () => {clearTimeout(temporizadorBusqueda); temporizadorBusqueda = setTimeout(cargarListado, 350);});
        ["filterDesde", "filterHasta", "filterTipo", "filterEstatus"].forEach((id) => $(id)?.addEventListener("change", cargarListado));
        $("btnLimpiarFiltros")?.addEventListener("click", () => {
            ["filterSearch", "filterDesde", "filterHasta", "filterTipo", "filterEstatus"].forEach((id) => {if ($(id)) $(id).value = "";});
            cargarListado();
        });
        $("btnRefrescarListado")?.addEventListener("click", () => {cargarListado(); cargarDashboard();});
        $("tbodyPropuestas")?.addEventListener("click", (event) => {
            const boton = event.target.closest('[data-action="ver-propuesta"]');
            if (!boton) return;
            const clave = boton.dataset.clave || "";
            if (/^[a-f0-9]{40}$/i.test(clave)) window.location.href = `${endpoint}/gestion/${encodeURIComponent(clave)}`;
        });
        cargarDashboard(); cargarListado();
    }
    if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", inicializar);
    else inicializar();
})();
