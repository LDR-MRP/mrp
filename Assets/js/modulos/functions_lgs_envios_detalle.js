let g_madrinasProveedor = [];
let g_plataformasProveedor = [];
let g_choferesProveedor = [];
let g_envioData = null;
let g_paradasEnvio = [];
let g_nodosEnvio = [];
let modalVehiculoBs = null;

document.addEventListener('DOMContentLoaded', function () {
    const elModal = document.getElementById('modalAgregarVehiculo');
    if (elModal && typeof bootstrap !== 'undefined') {
        modalVehiculoBs = new bootstrap.Modal(elModal);
    }
    
    cargarDatosDetalle();

    const inputBuscar = document.getElementById('buscar-vin-pool');
    if (inputBuscar) {
        inputBuscar.addEventListener('input', aplicarFiltrosPoolVins);
    }
});

function poblarFiltrosPoolVins(vins) {
    const selCli = document.getElementById('filtro-vin-cliente');
    if (!selCli) return;
    const prev = selCli.value;
    const map = new Map();
    (vins || []).forEach(v => {
        const idc = parseInt(v.id_cliente || 0, 10);
        const nom = (v.cliente_nombre || '').trim();
        if (idc > 0 && nom && !map.has(String(idc))) {
            map.set(String(idc), nom);
        }
    });
    const ord = Array.from(map.entries()).sort((a, b) => a[1].localeCompare(b[1], 'es'));
    let html = '<option value="">Cliente: Todos</option>';
    ord.forEach(([id, nom]) => {
        html += `<option value="${escapeHtml(id)}">${escapeHtml(nom)}</option>`;
    });
    selCli.innerHTML = html;
    if (prev && map.has(prev)) selCli.value = prev;
}

function aplicarFiltrosPoolVins() {
    const term   = (document.getElementById('buscar-vin-pool')?.value || '').toLowerCase().trim();
    const fAut   = document.getElementById('filtro-vin-autorizacion')?.value || '';
    const fCli   = document.getElementById('filtro-vin-cliente')?.value || '';
    const fCob   = document.getElementById('filtro-vin-cobranza')?.value || '';
    const items  = document.querySelectorAll('#vins-disponibles li');
    let visibles = 0;

    items.forEach(item => {
        if (!item.getAttribute('data-vin')) return;
        const hay = [
            item.getAttribute('data-vin'),
            item.getAttribute('data-num-serie'),
            item.getAttribute('data-modelo'),
            item.getAttribute('data-cliente'),
            item.getAttribute('data-pedido'),
            item.getAttribute('data-factura'),
            item.getAttribute('data-destino'),
            item.getAttribute('data-preenvios'),
        ].filter(Boolean).join(' ').toLowerCase();

        const seEnvia   = parseInt(item.getAttribute('data-se-envia') || '0', 10) === 1;
        const cumFac    = parseInt(item.getAttribute('data-cumple-factura') || '0', 10) === 1;
        const cumPag    = parseInt(item.getAttribute('data-cumple-pago') || '0', 10) === 1;
        const cumAut    = parseInt(item.getAttribute('data-cumple-aut') || '0', 10) === 1;
        const idCliente = String(item.getAttribute('data-id-cliente') || '');
        const pagoCod   = String(item.getAttribute('data-pago-cod') ?? '-1');

        let match = true;
        if (term && !hay.includes(term)) match = false;
        if (match && fCli && idCliente !== String(fCli)) match = false;
        if (match && fCob !== '' && pagoCod !== String(fCob)) match = false;
        if (match && fAut) {
            if (fAut === 'SI' && !seEnvia) match = false;
            else if (fAut === 'NO' && seEnvia) match = false;
            else if (fAut === 'FAC_PAG_SIN_AUT' && !(cumFac && cumPag && !cumAut)) match = false;
            else if (fAut === 'AUT_SIN_FAC_PAG' && !(cumAut && (!cumFac || !cumPag))) match = false;
        }

        if (match) {
            item.style.setProperty('display', '', 'important');
            visibles++;
        } else {
            item.style.setProperty('display', 'none', 'important');
        }
    });

    const lblPool = document.getElementById('badge-total-pool-vins');
    if (lblPool) lblPool.textContent = `${visibles} disponibles`;
}

function renderBannerSolicitudExcepcion(sol) {
    const el = document.getElementById('banner-solicitud-excepcion-detalle');
    if (!el) return;
    if (!sol || !sol.estatus) {
        el.classList.add('d-none');
        el.innerHTML = '';
        return;
    }
    const est = String(sol.estatus).toUpperCase();
    let cls = 'alert-soft-info border-info-subtle';
    let icon = 'ri-information-line text-info';
    let msg = '';
    if (est === 'PENDIENTE') {
        cls = 'alert-soft-warning border-warning-subtle';
        icon = 'ri-time-line text-warning';
        msg = `<strong>Solicitud de Autorización Pendiente (${escapeHtml(sol.folio_solicitud)}):</strong> Enviada por ${escapeHtml(sol.solicitante_nombre || 'Operador')} el ${escapeHtml(sol.solicitado_at || '')}. En espera de dictamen en <a href="${base_url}/Lgs_aprobaciones?tab=preenvios" class="fw-bold text-decoration-underline">Aprobaciones</a>.`;
    } else if (est === 'APROBADA') {
        cls = 'alert-soft-success border-success-subtle';
        icon = 'ri-checkbox-circle-line text-success';
        msg = `<strong>Autorización Excepcional Aprobada (${escapeHtml(sol.folio_solicitud)}):</strong> Aprobada por ${escapeHtml(sol.aprobador_nombre || 'Aprobador')}. ${sol.obs_aprobador ? 'Obs: ' + escapeHtml(sol.obs_aprobador) : ''}`;
    } else if (est === 'RECHAZADA') {
        cls = 'alert-soft-danger border-danger-subtle';
        icon = 'ri-close-circle-line text-danger';
        msg = `<strong>Solicitud Rechazada (${escapeHtml(sol.folio_solicitud)}):</strong> ${escapeHtml(sol.obs_aprobador || 'Sin comentarios del aprobador.')}`;
    } else {
        el.classList.add('d-none');
        return;
    }
    el.className = `alert ${cls} border rounded-3 mb-3 d-flex align-items-center gap-2 fs-12`;
    el.innerHTML = `<i class="${icon} fs-20 flex-shrink-0"></i><div>${msg}</div>`;
}

function cargarDatosDetalle() {
    const idEnvio = document.getElementById('id_envio') ? document.getElementById('id_envio').value : 0;
    if (!idEnvio) return;

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/getDetalleEnvioData/' + idEnvio;

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            try {
                let objData = JSON.parse(request.responseText);
                if (objData.status === 'success' || objData.status === true) {
                    g_envioData = objData.data.envio || {};
                    g_madrinasProveedor = objData.data.madrinas || [];
                    g_plataformasProveedor = objData.data.plataformas || [];
                    g_choferesProveedor = objData.data.choferes || [];
                    g_paradasEnvio = objData.data.paradas || [];
                    g_nodosEnvio = objData.data.nodos || [];

                    // 1. Mostrar nombre de la empresa trasladista y actualizar resumen
                    const lblProv = document.getElementById('lbl-trasladista-nombre');
                    if (lblProv) {
                        lblProv.innerText = g_envioData.trasladista || 'Sin Trasladista Asignado';
                    }

                    // Llenar tarjeta de resumen de ruta y KM
                    let acumRuta = 0;
                    let desgloseTramos = [];

                    if (g_nodosEnvio.length > 0) {
                        g_nodosEnvio.forEach((n, idx) => {
                            const kmT = parseFloat(n.km_tramo || 0);
                            acumRuta += kmT;
                            n.km_acumulado = acumRuta;
                            if (idx === 0) {
                                desgloseTramos.push(`🟢 Salida: ${n.nombre || 'Origen'}`);
                            } else {
                                desgloseTramos.push(`P${n.orden} (${n.nombre || 'Nodo'}: +${kmT.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}km)`);
                            }
                        });
                    } else {
                        (g_paradasEnvio || []).forEach(p => {
                            const kmT = parseFloat(p.km_tramo || 0);
                            acumRuta += kmT;
                            p.km_acumulado = acumRuta;
                            desgloseTramos.push(`P${p.orden} (${p.destino_nombre || 'Parada'}: +${kmT.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}km)`);
                        });
                    }

                    const lblOrig = document.getElementById('lbl-resumen-origen');
                    const lblKm   = document.getElementById('lbl-resumen-km-total');
                    const lblPar  = document.getElementById('lbl-resumen-paradas');
                    const lblCost = document.getElementById('lbl-resumen-costo');

                    const primerNodo = g_nodosEnvio.length > 0 ? g_nodosEnvio[0].nombre : g_envioData.origen;
                    if (lblOrig) lblOrig.innerText = primerNodo || '-';
                    if (lblKm)   lblKm.innerText   = (parseFloat(g_envioData.km_total || acumRuta).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })) + ' km Total';
                    
                    const numTramos = g_nodosEnvio.length > 1 ? (g_nodosEnvio.length - 1) : (g_paradasEnvio.length || 0);
                    if (lblPar)  lblPar.innerHTML  = `<strong>${numTramos} tramo(s) (${g_nodosEnvio.length || (g_paradasEnvio.length + 1)} puntos)</strong><small class="d-block text-muted fs-10 mt-1">${desgloseTramos.join(' ➔ ')}</small>`;
                    const tieneVinsAsignados = objData.data.existentes && objData.data.existentes.length > 0;
                    const costoInicial = tieneVinsAsignados ? (parseFloat(g_envioData.costo_total || 0)) : 0;
                    if (lblCost) lblCost.innerText = costoInicial > 0 ? '$' + costoInicial.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '$0.00';

                    // Indicador de Monto Abierto en el Detalle
                    const badgeAbierto = document.getElementById('badge-monto-abierto-detalle');
                    const lblMotivo = document.getElementById('lbl-motivo-monto-abierto');
                    if (parseInt(g_envioData.es_monto_abierto || 0) === 1) {
                        if (badgeAbierto) badgeAbierto.classList.remove('d-none');
                        if (lblMotivo && g_envioData.motivo_monto_abierto) {
                            lblMotivo.style.display = 'block';
                            lblMotivo.innerHTML = `<i class="ri-information-line me-1 text-warning"></i>${g_envioData.motivo_monto_abierto}`;
                        }
                    } else {
                        if (badgeAbierto) badgeAbierto.classList.add('d-none');
                        if (lblMotivo) lblMotivo.style.display = 'none';
                    }

                    const idTipoTraslado = parseInt(g_envioData.id_tipo_traslado || 1);
                    const btnAdd = document.getElementById('btn-agregar-vehiculo');
                    if (btnAdd) {
                        if (idTipoTraslado === 1) {
                            btnAdd.innerHTML = '<i class="ri-truck-line me-1"></i> Agregar Madrina';
                        } else if (idTipoTraslado === 3) {
                            btnAdd.innerHTML = '<i class="ri-truck-line me-1"></i> Agregar Plataforma';
                        } else {
                            btnAdd.innerHTML = '<i class="ri-steering-2-line me-1"></i> Seleccionar Chofer (Rodando)';
                        }
                    }

                    // 1b. Mostrar banner de solicitud excepcional si existe
                    renderBannerSolicitudExcepcion(objData.data.solicitud_excepcion || null);

                    // 2. Renderizar VINs Disponibles en el pool izquierdo y poblar filtros
                    poblarFiltrosPoolVins(objData.data.vins || []);
                    renderPoolVins(objData.data.vins || []);
                    aplicarFiltrosPoolVins();

                    // 3. Renderizar asignaciones existentes si las hay
                    renderAcomodoExistente(objData.data.existentes || []);

                    // 4. Inicializar Sortables
                    initSortables();

                    // 5. Actualizar tarjetas KPI y desglose de Auditoría Financiera
                    if (objData.data.auditoria_financiera) {
                        actualizarAuditoriaFinancieraUI(objData.data.auditoria_financiera);
                    }

                    // 6. Recalcular costo solo en modo editable si quedó sin calcular en BD
                    const esSoloLectura = (typeof ENVIO_READONLY !== 'undefined' && ENVIO_READONLY === true);
                    if (!esSoloLectura && tieneVinsAsignados && parseFloat(g_envioData.costo_total || 0) <= 0) {
                        guardarAcomodoAuto();
                    }
                } else {
                    Swal.fire("Error", objData.message || "No se pudieron cargar los datos del envío.", "error").then(() => {
                        window.location.href = base_url + '/Lgs_envios';
                    });
                }
            } catch (e) {
                console.error("Error al procesar datos de detalle: ", e);
                Swal.fire("Error", "Ocurrió un error al cargar la información.", "error");
            }
        }
    }
}

function buildBadgesEstatusUnidad(meta) {
    const pagoClsMap = { 3: 'bg-success text-white', 2: 'bg-warning text-dark', 1: 'bg-info text-white', 0: 'bg-soft-danger text-danger', '-1': 'bg-light text-muted border' };
    const pCod   = meta.estatus_pago_cod !== undefined && meta.estatus_pago_cod !== null ? String(meta.estatus_pago_cod) : '-1';
    const pTxt   = meta.estatus_pago_texto || 'Sin Facturar';
    const pCls   = pagoClsMap[pCod] || pagoClsMap['-1'];
    const seEnvia = parseInt(meta.se_envia || 0, 10) === 1;
    const cumFac  = parseInt(meta.cumple_factura || 0, 10) === 1;
    const cumPag  = parseInt(meta.cumple_pago || 0, 10) === 1;
    const cumAut  = parseInt(meta.cumple_autorizacion || 0, 10) === 1;
    const autExc  = parseInt(meta.autorizado_excepcion || 0, 10) === 1;
    const motivo  = meta.estado_envio_motivo || (seEnvia ? 'Autorizada' : 'Pendiente de autorización');

    const badges = [];
    if (seEnvia || autExc) {
        badges.push(`<span class="badge bg-success text-white fs-10" title="${escapeHtml(motivo)}"><i class="ri-check-double-line me-1"></i>Envío: SÍ</span>`);
    } else {
        badges.push(`<span class="badge bg-danger text-white fs-10" title="${escapeHtml(motivo)}"><i class="ri-close-circle-line me-1"></i>Envío: NO</span>`);
    }

    if (cumAut) {
        badges.push(`<span class="badge bg-soft-success text-success border border-success-subtle fs-10" title="Con autorización de movimiento (Bandeja)">Aut. Mov.: SÍ</span>`);
    } else {
        const titleAut = (cumFac && cumPag) ? 'Cumple Factura y Pago, falta Autorización de Movimiento en Bandeja' : 'Sin Autorización de Movimiento';
        badges.push(`<span class="badge bg-soft-warning text-dark border border-warning-subtle fs-10" title="${titleAut}">Aut. Mov.: NO</span>`);
    }

    if (meta.cliente_nombre) badges.push(`<span class="badge bg-soft-primary text-primary fs-10">${escapeHtml(meta.cliente_nombre)}</span>`);
    if (meta.folio_pedido) badges.push(`<span class="badge bg-soft-warning text-warning fs-10">Ped: ${escapeHtml(meta.folio_pedido)}</span>`);
    if (meta.factura) {
        badges.push(`<span class="badge bg-soft-secondary text-dark fs-10">Fac: ${escapeHtml(meta.factura)}</span>`);
    }
    badges.push(`<span class="badge ${pCls} fs-10">${escapeHtml(pTxt)}</span>`);
    if (!seEnvia && !autExc && cumFac && cumPag && !cumAut) {
        badges.push(`<span class="badge bg-warning-subtle text-dark border border-warning fs-10"><i class="ri-shield-keyhole-line me-1"></i>Cumple Fac+Pago · Falta Aut.</span>`);
    }
    if (meta.preenvio_folios) {
        badges.push(`<span class="badge bg-soft-info text-info fs-10" title="En pre-envío ficticio (no bloquea)">PE: ${escapeHtml(meta.preenvio_folios)}</span>`);
    }
    return badges.join('');
}

function renderPoolVins(vins) {
    const poolUl = document.getElementById('vins-disponibles');
    const lblPool = document.getElementById('badge-total-pool-vins');
    if (!poolUl) return;
    if (lblPool) lblPool.textContent = Array.isArray(vins) ? (vins.length + ' disponibles') : '0 disponibles';

    if (!Array.isArray(vins) || vins.length === 0) {
        poolUl.innerHTML = `<li class="list-group-item text-center text-muted py-4 border-0">
            <i class="ri-car-line fs-2 d-block mb-1 opacity-50"></i>
            No hay VINs disponibles en este origen.
        </li>`;
        return;
    }

    let html = '';
    vins.forEach(v => {
        const mod = v.modelo || 'Unidad';
        const orig = v.origen || 'Origen';
        const dest = v.destino || 'Destino';
        const cliNom = v.cliente_nombre || '';
        const pedFol = v.folio_pedido || '';
        const facFol = v.factura || '';
        const peFol  = v.preenvio_folios || '';
        const pCod   = v.estatus_pago_cod !== undefined && v.estatus_pago_cod !== null ? String(v.estatus_pago_cod) : '-1';
        const pTxt   = v.estatus_pago_texto || 'Sin Facturar';
        const seEnvia = parseInt(v.se_envia || 0, 10) === 1;

        let coincide = (v.coincide_destino !== false);
        let estaAsignado = Boolean(v.asignado_folio);
        
        // Solo deshabilitamos el arrastre si la unidad ya se encuentra asignada a otro envío oficial activo
        let isDraggable = !estaAsignado;
        
        let liClass = isDraggable ? "cursor-move shadow-sm bg-white" : "disabled-vin bg-light opacity-50";
        let dragger = isDraggable ? '<i class="ri-draggable fs-18 text-muted"></i>' : '<i class="ri-close-circle-line fs-18 text-danger"></i>';
        let borderColor = seEnvia ? 'border-success' : 'border-primary';
        
        let badgeWarn = '';
        let btnAsignar = '';

        if (estaAsignado) {
            badgeWarn = `<span class="badge bg-danger ms-2" title="Esta unidad ya se encuentra asignada al envío ${escapeHtml(v.asignado_folio)}">Oficial ${escapeHtml(v.asignado_folio)}</span>`;
        } else {
            if (!coincide) {
                badgeWarn = '<span class="badge bg-warning-subtle text-warning border border-warning ms-1" title="El destino registrado en la unidad difiere textualmente de las paradas del envío. Puede asignarse con normalidad."><i class="ri-alert-line me-1"></i>Otro Dest.</span>';
            }
            if (!ENVIO_READONLY) {
                btnAsignar = `<button type="button" class="btn btn-sm btn-outline-success py-0 px-2 fs-11 ms-1" onclick="asignarVinRapido(this)" title="Asignar al vehículo activo"><i class="ri-arrow-right-line me-1"></i>Asignar</button>`;
            }
        }

        const badgesHtml = buildBadgesEstatusUnidad(v);

        html += `
        <li class="list-group-item mb-2 rounded border-start border-3 ${borderColor} ${liClass}" 
            data-id-unidad="${v.id_unidad || 0}"
            data-id-numeros-serie="${v.id_numeros_serie || 0}"
            data-id-cliente="${v.id_cliente || 0}"
            data-vin="${escapeHtml(v.vin)}"
            data-num-serie="${escapeHtml(v.num_serie || '')}"
            data-modelo="${escapeHtml(mod)}"
            data-origen="${escapeHtml(orig)}"
            data-destino="${escapeHtml(dest)}"
            data-cliente="${escapeHtml(cliNom)}"
            data-pedido="${escapeHtml(pedFol)}"
            data-factura="${escapeHtml(facFol)}"
            data-pago-cod="${escapeHtml(pCod)}"
            data-pago-texto="${escapeHtml(pTxt)}"
            data-cumple-factura="${v.cumple_factura ? 1 : 0}"
            data-cumple-pago="${v.cumple_pago ? 1 : 0}"
            data-cumple-aut="${v.cumple_autorizacion ? 1 : 0}"
            data-autorizado-excepcion="${v.autorizado_excepcion ? 1 : 0}"
            data-se-envia="${seEnvia ? 1 : 0}"
            data-estado-motivo="${escapeHtml(v.estado_envio_motivo || '')}"
            data-preenvios="${escapeHtml(peFol)}">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    ${dragger}
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                        <h6 class="mb-0 fs-13 text-primary fw-bold font-monospace">${escapeHtml(v.vin)}</h6>
                        <div class="d-flex align-items-center flex-wrap gap-1">
                            <span class="badge bg-soft-info text-info fs-11">${escapeHtml(mod)}</span>
                            ${badgeWarn}
                            ${btnAsignar}
                        </div>
                    </div>
                    <p class="text-dark mb-1 fs-11 fw-semibold">
                        <i class="ri-map-pin-line text-danger me-1"></i>${escapeHtml(orig)} 
                        <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                        <i class="ri-map-pin-2-fill text-success me-1"></i>${escapeHtml(dest)}
                    </p>
                    <div class="d-flex flex-wrap gap-1">${badgesHtml}</div>
                </div>
            </div>
        </li>`;
    });
    poolUl.innerHTML = html;
}

function renderAcomodoExistente(existentes) {
    if (!Array.isArray(existentes) || existentes.length === 0) return;

    // Agrupar por Madrina, Plataforma o Chofer
    const grupos = {};
    existentes.forEach(item => {
        let key = item.id_madrina 
            ? 'madrina_' + item.id_madrina 
            : (item.id_plataforma || item.plataforma_nombre 
                ? 'plataforma_' + (item.id_plataforma || 'sub_' + (item.posicion_acomodo || 1)) 
                : 'chofer_' + item.id_chofer);

        if (!grupos[key]) {
            let nombre = 'Chofer: ' + (item.chofer_nombre || 'Rodando');
            if (item.id_madrina) nombre = 'Madrina: ' + (item.madrina_nombre || 'S/N');
            if (item.id_plataforma || item.plataforma_nombre) nombre = 'Plataforma: ' + (item.plataforma_nombre || 'Subcontratada');
            
            grupos[key] = {
                id_madrina: item.id_madrina,
                id_plataforma: item.id_plataforma,
                id_chofer: item.id_chofer,
                nombre: nombre,
                vins: []
            };
        }
        grupos[key].vins.push(item);
    });

    Object.values(grupos).forEach(grupo => {
        if (grupo.id_madrina) {
            let m = g_madrinasProveedor.find(x => x.id_madrina == grupo.id_madrina);
            inyectarContenedorVehiculo({
                id_madrina: grupo.id_madrina,
                id_plataforma: null,
                id_chofer: null,
                titulo: grupo.nombre + (m && m.placas ? ' (' + m.placas + ')' : ''),
                capacidad: m ? m.capacidad_vehiculos : 99
            }, grupo.vins);
        } else if (grupo.id_plataforma || grupo.nombre.includes('Plataforma')) {
            let p = g_plataformasProveedor.find(x => x.id_plataforma == grupo.id_plataforma);
            inyectarContenedorVehiculo({
                id_madrina: null,
                id_plataforma: grupo.id_plataforma || null,
                id_chofer: null,
                titulo: grupo.nombre + (p && p.placas ? ' (' + p.placas + ')' : ''),
                capacidad: p ? p.capacidad_vehiculos : Math.max(1, grupo.vins.length)
            }, grupo.vins);
        } else if (grupo.id_chofer) {
            inyectarContenedorVehiculo({
                id_madrina: null,
                id_plataforma: null,
                id_chofer: grupo.id_chofer,
                titulo: grupo.nombre,
                capacidad: 1
            }, grupo.vins);
        }
    });

    document.querySelectorAll('.vehiculo-list').forEach(ul => {
        actualizarConteoYSecuencia(ul);
        agruparYOrdenarParadas(ul);
    });
}

function initSortables() {
    // 1. Contenedor de VINs Disponibles
    const pool = document.getElementById('vins-disponibles');
    if (pool && !pool.getAttribute('data-sortable-init')) {
        pool.setAttribute('data-sortable-init', 'true');
        new Sortable(pool, {
            group: 'shared',
            animation: 150,
            disabled: (typeof ENVIO_READONLY !== 'undefined' && ENVIO_READONLY),
            filter: '.disabled-vin',
            preventOnFilter: true,
            ghostClass: 'sortable-ghost',
            onAdd: function (evt) {
                actualizarConteoYSecuencia(evt.from);
                limpiarBadgesPool(evt.item);
                actualizarContadorPoolVins();
            }
        });
    }

    // 2. Contenedores de Madrinas / Choferes
    const vehiculos = document.querySelectorAll('.vehiculo-list');
    vehiculos.forEach(v => {
        if (!v.getAttribute('data-sortable-init')) {
            v.setAttribute('data-sortable-init', 'true');
            new Sortable(v, {
                group: 'shared',
                animation: 150,
                disabled: (typeof ENVIO_READONLY !== 'undefined' && ENVIO_READONLY),
                ghostClass: 'sortable-ghost',
                onAdd: function (evt) {
                    const cap = parseInt(evt.to.getAttribute('data-capacidad') || 99, 10);
                    if (cap === 1 && evt.to.querySelectorAll('li').length > 1) {
                        Swal.fire('Atención', 'En envíos por chofer (Rodando) solo se permite 1 unidad por chofer.', 'warning');
                        evt.from.appendChild(evt.item); // Regresar al origen
                        return;
                    }
                    actualizarConteoYSecuencia(evt.to);
                    agruparYOrdenarParadas(evt.to);
                    actualizarContadorPoolVins();
                    guardarAcomodoAuto();
                },
                onRemove: function (evt) {
                    actualizarConteoYSecuencia(evt.from);
                    guardarAcomodoAuto();
                },
                onUpdate: function (evt) {
                    actualizarConteoYSecuencia(evt.to);
                    agruparYOrdenarParadas(evt.to);
                    guardarAcomodoAuto();
                },
                onEnd: function (evt) {
                    if (evt.to && evt.to.id !== 'vins-disponibles') {
                        actualizarConteoYSecuencia(evt.to);
                        agruparYOrdenarParadas(evt.to);
                        guardarAcomodoAuto();
                    }
                }
            });
        }
    });

    document.querySelectorAll('.vehiculo-list').forEach(ul => {
        actualizarConteoYSecuencia(ul);
        agruparYOrdenarParadas(ul);
    });
}

function actualizarConteoYSecuencia(listaUl) {
    if (!listaUl || listaUl.id === 'vins-disponibles') return;

    const container = listaUl.closest('.envios-surface-card') || listaUl.closest('div.border');
    const items = listaUl.querySelectorAll('li');
    const cap = parseInt(listaUl.getAttribute('data-capacidad') || 99, 10);
    const tieneNodos = Array.isArray(g_nodosEnvio) && g_nodosEnvio.length > 0;

    items.forEach((li, idx) => {
        li.classList.remove('border-primary');
        li.classList.add('border-success');

        const vin = li.getAttribute('data-vin') || li.querySelector('h6')?.innerText.replace('VIN:', '').trim() || '';
        const numSerie = li.getAttribute('data-num-serie') || '';
        const modelo = li.getAttribute('data-modelo') || 'Unidad';
        const origen = li.getAttribute('data-origen') || 'Origen';
        const destino = li.getAttribute('data-destino') || 'Destino';

        const posIndex = idx + 1;
        let badgeSecuencia = (posIndex === 1)
            ? `<span class="badge bg-success px-2 py-1 fs-11 me-1"><i class="ri-number-1 me-1"></i>1º EN CARGAR</span>`
            : `<span class="badge bg-info px-2 py-1 fs-11 me-1"><i class="ri-truck-line me-1"></i>${posIndex}º EN CARGAR</span>`;

        if (tieneNodos) {
            let idSubidaActual = li.getAttribute('data-id-nodo-subida') || '';
            let idBajadaActual = li.getAttribute('data-id-nodo-bajada') || li.getAttribute('data-id-parada') || '';

            // Auto-matching Subida
            if (!idSubidaActual) {
                let bestSubida = g_nodosEnvio[0];
                if (origen) {
                    const oLow = origen.toLowerCase().trim();
                    const match = g_nodosEnvio.find(n => (n.nombre || '').toLowerCase().trim() === oLow || (n.nombre || '').toLowerCase().trim().includes(oLow) || oLow.includes((n.nombre || '').toLowerCase().trim()));
                    if (match && parseInt(match.orden || 0) < (g_nodosEnvio.length - 1)) {
                        bestSubida = match;
                    }
                }
                idSubidaActual = String(bestSubida.id_nodo);
            }

            // Auto-matching Bajada
            if (!idBajadaActual) {
                let bestBajada = g_nodosEnvio[g_nodosEnvio.length - 1];
                if (destino) {
                    const dLow = destino.toLowerCase().trim();
                    const match = g_nodosEnvio.find(n => (n.nombre || '').toLowerCase().trim() === dLow || (n.nombre || '').toLowerCase().trim().includes(dLow) || dLow.includes((n.nombre || '').toLowerCase().trim()));
                    if (match && parseInt(match.orden || 0) > 0) {
                        bestBajada = match;
                    }
                }
                idBajadaActual = String(bestBajada.id_nodo);
            }

            let objSubida = g_nodosEnvio.find(n => String(n.id_nodo) === String(idSubidaActual)) || g_nodosEnvio[0];
            let objBajada = g_nodosEnvio.find(n => String(n.id_nodo) === String(idBajadaActual)) || g_nodosEnvio[g_nodosEnvio.length - 1];

            let ordSub = parseInt(objSubida.orden || 0);
            let ordBaj = parseInt(objBajada.orden || 0);

            // Validar coherencia: Bajada debe ser posterior a Subida
            if (ordBaj <= ordSub) {
                let candidate = g_nodosEnvio.find(n => parseInt(n.orden || 0) > ordSub);
                if (candidate) {
                    objBajada = candidate;
                    idBajadaActual = String(candidate.id_nodo);
                    ordBaj = parseInt(candidate.orden || 0);
                } else if (ordSub > 0) {
                    objSubida = g_nodosEnvio[0];
                    idSubidaActual = String(objSubida.id_nodo);
                    ordSub = 0;
                }
            }

            li.setAttribute('data-id-nodo-subida', idSubidaActual);
            li.setAttribute('data-id-nodo-bajada', idBajadaActual);
            li.setAttribute('data-id-parada', idBajadaActual);

            // Generar options para Subida
            let optsSubida = '';
            g_nodosEnvio.forEach((n, i) => {
                const tipo = String(n.tipo_nodo || '').toLowerCase().trim();
                const isCarga = (tipo === 'carga' || tipo === 'origen' || parseInt(n.orden || 0) === 0 || parseInt(n.id_tipo_destino || 0) === 5);
                if (i < g_nodosEnvio.length - 1 && isCarga) {
                    const sel = (String(n.id_nodo) === String(idSubidaActual)) ? 'selected' : '';
                    const numPunto = parseInt(n.orden || 0) + 1;
                    const prefix = (parseInt(n.orden || 0) === 0) ? '🟢 Inicio: ' : `🔵 Pto ${numPunto} (Carga): `;
                    optsSubida += `<option value="${n.id_nodo}" ${sel}>${prefix}${n.nombre || 'Nodo ' + n.orden}</option>`;
                }
            });

            // Generar options para Bajada
            let optsBajada = '';
            g_nodosEnvio.forEach((n, i) => {
                const tipo = String(n.tipo_nodo || '').toLowerCase().trim();
                const isEntrega = (tipo === 'entrega' || i === g_nodosEnvio.length - 1);
                if (i > ordSub && isEntrega) {
                    const sel = (String(n.id_nodo) === String(idBajadaActual)) ? 'selected' : '';
                    const isFinal = (i === g_nodosEnvio.length - 1);
                    const numPunto = parseInt(n.orden || 0) + 1;
                    const prefix = isFinal ? '🏁 Fin: ' : `🔴 Pto ${numPunto} (Entrega): `;
                    optsBajada += `<option value="${n.id_nodo}" ${sel}>${prefix}${n.nombre || 'Nodo ' + n.orden}</option>`;
                }
            });

            // Calcular KM y tramos a bordo
            const esDirectoModalidad = (g_envioData && String(g_envioData.modalidad_calculo || '').toUpperCase() === 'DIRECTO');
            let kmVin = 0;
            let tramosVin = 0;
            g_nodosEnvio.forEach(n => {
                const o = parseInt(n.orden || 0);
                if (o > ordSub && o <= ordBaj) {
                    if (!esDirectoModalidad) {
                        kmVin += parseFloat(n.km_tramo || 0);
                    }
                    tramosVin++;
                }
            });
            if (esDirectoModalidad) {
                const kmDir = parseFloat((objBajada && (objBajada.km_directo || objBajada.km_tramo)) || 0);
                if (kmDir > 0) {
                    kmVin = kmDir;
                } else {
                    g_nodosEnvio.forEach(n => {
                        const o = parseInt(n.orden || 0);
                        if (o > ordSub && o <= ordBaj) {
                            kmVin += parseFloat(n.km_tramo || 0);
                        }
                    });
                }
            }

            // Determinar tipo de servicio y badge según distancia del VIN
            const tipoServicioEnvio = (g_envioData && g_envioData.tipo_servicio ? g_envioData.tipo_servicio : 'AUTOMATICO').toUpperCase();
            let tipoServicioVin = 'FORANEO';
            let badgeServicio = '';

            if (tipoServicioEnvio === 'SLC') {
                tipoServicioVin = 'SLC';
                badgeServicio = `<span class="badge bg-soft-success text-success border border-success fs-10 fw-bold ms-1" title="Servicio Local Corto (0-40 KM)"><i class="ri-map-pin-user-line me-1"></i>SLC (Local)</span>`;
            } else if (tipoServicioEnvio === 'SLL') {
                tipoServicioVin = 'SLL';
                badgeServicio = `<span class="badge bg-soft-warning text-warning border border-warning fs-10 fw-bold ms-1" title="Servicio Local Largo (41-80 KM)"><i class="ri-map-pin-range-line me-1"></i>SLL (Local Largo)</span>`;
            } else if (tipoServicioEnvio === 'FORANEO') {
                tipoServicioVin = 'FORANEO';
                badgeServicio = `<span class="badge bg-soft-primary text-primary border border-primary fs-10 fw-bold ms-1" title="Servicio Foráneo por KM"><i class="ri-road-map-line me-1"></i>FORÁNEO</span>`;
            } else {
                // AUTOMATICO / MIXTO
                if (kmVin > 0 && kmVin <= 40) {
                    tipoServicioVin = 'SLC';
                    badgeServicio = `<span class="badge bg-soft-success text-success border border-success fs-10 fw-bold ms-1" title="Cálculo Automático: 0-40 KM (Tarifa Fija SLC)"><i class="ri-map-pin-user-line me-1"></i>SLC (Local)</span>`;
                } else if (kmVin > 40 && kmVin <= 80) {
                    tipoServicioVin = 'SLL';
                    badgeServicio = `<span class="badge bg-soft-warning text-warning border border-warning fs-10 fw-bold ms-1" title="Cálculo Automático: 41-80 KM (Tarifa Fija SLL)"><i class="ri-map-pin-range-line me-1"></i>SLL (Local Largo)</span>`;
                } else {
                    tipoServicioVin = 'FORANEO';
                    badgeServicio = `<span class="badge bg-soft-primary text-primary border border-primary fs-10 fw-bold ms-1" title="Cálculo Automático: >80 KM (Factor por KM)"><i class="ri-road-map-line me-1"></i>FORÁNEO</span>`;
                }
            }
            li.setAttribute('data-tipo-servicio', tipoServicioVin);
            const seEnviaCard = parseInt(li.getAttribute('data-se-envia') || '0', 10) === 1 || parseInt(li.getAttribute('data-autorizado-excepcion') || '0', 10) === 1;
            const motivoCard  = li.getAttribute('data-estado-motivo') || (seEnviaCard ? 'Autorizada' : 'Pendiente de autorización');
            const badgeEnvioCard = seEnviaCard
                ? `<span class="badge bg-success text-white fs-10 me-1" title="${escapeHtml(motivoCard)}">Envío: SÍ</span>`
                : `<span class="badge bg-danger text-white fs-10 me-1" title="${escapeHtml(motivoCard)}">Envío: NO</span>`;

            li.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    <i class="ri-draggable fs-18 text-muted"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                        <h6 class="mb-0 fs-13 text-success fw-bold">VIN: ${vin}</h6>
                        <div>
                            ${badgeEnvioCard}
                            ${badgeSecuencia}
                            <span class="badge bg-soft-secondary text-dark fs-11">${modelo}</span>
                        </div>
                    </div>
                    <p class="text-dark mb-1 fs-11 fw-semibold">
                        <i class="ri-map-pin-line text-danger me-1"></i>${origen} 
                        <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                        <i class="ri-map-pin-2-fill text-success me-1"></i>${destino}
                    </p>
                    <div class="mt-1 p-2 bg-light rounded border">
                        <div class="row g-1 align-items-center">
                            <div class="col-6">
                                <label class="fs-10 text-success fw-bold mb-0 d-block"><i class="ri-login-box-line me-1"></i>Subida (Carga)</label>
                                <select class="form-select form-select-sm py-0 nodo-subida-select" 
                                        style="font-size:11px;"
                                        data-vin-key="${vin}"
                                        onchange="li_setNodoSubida(this)">
                                    ${optsSubida}
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="fs-10 fw-bold mb-0 d-block" style="color: #fd7e14;"><i class="ri-logout-box-line me-1"></i>Descarga (Bajada)</label>
                                <select class="form-select form-select-sm py-0 nodo-bajada-select" 
                                        style="font-size:11px;"
                                        data-vin-key="${vin}"
                                        onchange="li_setNodoBajada(this)">
                                    ${optsBajada}
                                </select>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-1 flex-wrap gap-1">
                            <div>
                                <span class="badge bg-soft-info text-info border border-info fs-10 fw-bold">
                                    <i class="ri-route-line me-1"></i>${kmVin.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} km (${tramosVin} tramo${tramosVin > 1 ? 's' : ''} a bordo)
                                </span>
                                ${badgeServicio}
                            </div>
                            <small class="text-muted fs-10">N/S: ${numSerie || 'N/A'}</small>
                        </div>
                    </div>
                </div>
            </div>`;
        } else {
            // Modo retrocompatible con g_paradasEnvio simple
            const paradaActual = li.getAttribute('data-id-parada') || '';
            let paradaAutoselect = paradaActual;
            if (!paradaAutoselect && g_paradasEnvio.length > 0 && destino) {
                const destinoLower = destino.toLowerCase().trim();
                let bestMatch = null;
                let bestScore = 0;
                g_paradasEnvio.forEach(p => {
                    const nombreParada = (p.destino_nombre || p.destino_nombre_libre || '').toLowerCase().trim();
                    if (!nombreParada) return;
                    let score = 0;
                    if (nombreParada === destinoLower) score = 100;
                    else if (nombreParada.includes(destinoLower) || destinoLower.includes(nombreParada)) score = 60;
                    if (score > bestScore) { bestScore = score; bestMatch = p; }
                });
                if (bestMatch && bestScore >= 20) paradaAutoselect = String(bestMatch.id_parada);
            }

            const objParadaSel = (g_paradasEnvio || []).find(p => String(p.id_parada) === String(paradaAutoselect));
            const kmTramo = objParadaSel ? parseFloat(objParadaSel.km_tramo || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) : null;
            const kmAcum = objParadaSel ? parseFloat(objParadaSel.km_acumulado || objParadaSel.km_tramo || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 }) : null;

            const kmAcumNum = objParadaSel ? parseFloat(objParadaSel.km_acumulado || objParadaSel.km_tramo || 0) : 0;
            const tipoServicioEnvioLegacy = (g_envioData && g_envioData.tipo_servicio ? g_envioData.tipo_servicio : 'AUTOMATICO').toUpperCase();
            let tipoServicioVinLegacy = 'FORANEO';
            let badgeServicioLegacy = '';
            if (tipoServicioEnvioLegacy === 'SLC') {
                tipoServicioVinLegacy = 'SLC';
                badgeServicioLegacy = `<span class="badge bg-soft-success text-success border border-success fs-10 fw-bold ms-1">SLC (Local)</span>`;
            } else if (tipoServicioEnvioLegacy === 'SLL') {
                tipoServicioVinLegacy = 'SLL';
                badgeServicioLegacy = `<span class="badge bg-soft-warning text-warning border border-warning fs-10 fw-bold ms-1">SLL (Local Largo)</span>`;
            } else if (tipoServicioEnvioLegacy === 'FORANEO') {
                tipoServicioVinLegacy = 'FORANEO';
                badgeServicioLegacy = `<span class="badge bg-soft-primary text-primary border border-primary fs-10 fw-bold ms-1">FORÁNEO</span>`;
            } else {
                if (kmAcumNum > 0 && kmAcumNum <= 40) {
                    tipoServicioVinLegacy = 'SLC';
                    badgeServicioLegacy = `<span class="badge bg-soft-success text-success border border-success fs-10 fw-bold ms-1">SLC (Local)</span>`;
                } else if (kmAcumNum > 40 && kmAcumNum <= 80) {
                    tipoServicioVinLegacy = 'SLL';
                    badgeServicioLegacy = `<span class="badge bg-soft-warning text-warning border border-warning fs-10 fw-bold ms-1">SLL (Local Largo)</span>`;
                } else {
                    tipoServicioVinLegacy = 'FORANEO';
                    badgeServicioLegacy = `<span class="badge bg-soft-primary text-primary border border-primary fs-10 fw-bold ms-1">FORÁNEO</span>`;
                }
            }
            li.setAttribute('data-tipo-servicio', tipoServicioVinLegacy);

            let opts = '<option value="">-- Sin parada --</option>';
            if (g_paradasEnvio && g_paradasEnvio.length > 0) {
                g_paradasEnvio.forEach(p => {
                    const sel = (String(p.id_parada) === String(paradaAutoselect)) ? 'selected' : '';
                    const kmTxt = p.km_tramo ? ` (+${parseFloat(p.km_tramo).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}km)` : '';
                    opts += `<option value="${p.id_parada}" ${sel}>Parada ${p.orden}: ${p.destino_nombre || p.destino_nombre_libre || 'Sin Nombre'}${kmTxt}</option>`;
                });
            }

            li.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    <i class="ri-draggable fs-18 text-muted"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fs-13 text-success fw-bold">VIN: ${vin}</h6>
                        <div>
                            ${badgeSecuencia}
                            <span class="badge bg-soft-secondary text-dark fs-11">${modelo}</span>
                        </div>
                    </div>
                    <p class="text-dark mb-1 fs-11 fw-semibold">
                        <i class="ri-map-pin-line text-danger me-1"></i>${origen} 
                        <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                        <i class="ri-map-pin-2-fill text-success me-1"></i>${destino}
                    </p>
                    ${ g_paradasEnvio.length > 0 ? `
                    <div class="mt-1 d-flex align-items-center gap-2 flex-wrap">
                        <i class="ri-route-line text-primary fs-12"></i>
                        <select class="form-select form-select-sm py-0 parada-vin-select" 
                                style="max-width:230px; font-size:11px;"
                                data-vin-key="${vin}"
                                onchange="li_setParada(this)">
                            ${opts}
                        </select>
                        <span class="badge bg-soft-info text-info border border-info fs-11 fw-bold vin-km-tramo-badge" style="${kmTramo ? '' : 'display:none;'}">
                            <i class="ri-map-pin-distance-line me-1"></i>+${kmTramo || 0} km (Total: ${kmAcum || 0} km)
                        </span>
                        ${badgeServicioLegacy}
                    </div>` : ''}
                    <small class="text-muted fs-11">N/S: ${numSerie || 'N/A'}</small>
                </div>
            </div>`;

            if (paradaAutoselect) li.setAttribute('data-id-parada', paradaAutoselect);
            const sel = li.querySelector('.parada-vin-select');
            if (sel) sel.addEventListener('change', () => {
                li.setAttribute('data-id-parada', sel.value);
            });
        }
    });

    // Calcular ocupación y recorrido del vehículo por tramo
    if (container) {
        const badge = container.querySelector('.badge');
        if (badge) {
            if (tieneNodos && g_nodosEnvio.length > 1) {
                let maxCargaSimultanea = 0;
                let tramoSobrecapacidad = null;
                let kmTotalVehiculo = 0;

                for (let i = 1; i < g_nodosEnvio.length; i++) {
                    const nodoAnt = g_nodosEnvio[i - 1];
                    const nodoAct = g_nodosEnvio[i];
                    const ordAnt = parseInt(nodoAnt.orden || 0);
                    const ordAct = parseInt(nodoAct.orden || 0);

                    let vinsEnEsteTramo = 0;
                    items.forEach(li => {
                        const sId = li.getAttribute('data-id-nodo-subida');
                        const bId = li.getAttribute('data-id-nodo-bajada');
                        const oSub = g_nodosEnvio.find(n => String(n.id_nodo) === String(sId));
                        const oBaj = g_nodosEnvio.find(n => String(n.id_nodo) === String(bId));
                        const ordS = oSub ? parseInt(oSub.orden || 0) : 0;
                        const ordB = oBaj ? parseInt(oBaj.orden || 0) : (g_nodosEnvio.length - 1);
                        if (ordS <= ordAnt && ordB >= ordAct) {
                            vinsEnEsteTramo++;
                        }
                    });

                    if (vinsEnEsteTramo > 0) {
                        kmTotalVehiculo += parseFloat(nodoAct.km_tramo || 0);
                    }

                    if (vinsEnEsteTramo > maxCargaSimultanea) {
                        maxCargaSimultanea = vinsEnEsteTramo;
                    }
                    if (vinsEnEsteTramo > cap && !tramoSobrecapacidad) {
                        tramoSobrecapacidad = `Tramo ${ordAnt}➔${ordAct}`;
                    }
                }

                if (tramoSobrecapacidad) {
                    badge.className = 'badge bg-danger rounded-pill me-2';
                    badge.innerHTML = `<i class="ri-alert-line me-1"></i> Carga máx: ${maxCargaSimultanea} / ${cap} (Excede en ${tramoSobrecapacidad}) | ${kmTotalVehiculo.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} km`;
                } else {
                    badge.className = 'badge bg-primary rounded-pill me-2';
                    const kmTxt = kmTotalVehiculo > 0 ? ` | 🛣️ ${kmTotalVehiculo.toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 })} km` : '';
                    badge.innerHTML = `${items.length} VINs asignados (Carga máx: ${maxCargaSimultanea} / ${cap})${kmTxt}`;
                }
            } else {
                badge.innerHTML = items.length + ' / ' + cap + ' VINs';
            }
        }
    }

    renderInfografiaRuta();
}

/** Cuando el usuario cambia el nodo de subida de un VIN */
function li_setNodoSubida(selectEl) {
    const li = selectEl.closest('li');
    if (!li) return;
    const val = selectEl.value;
    li.setAttribute('data-id-nodo-subida', val);

    const objSub = (g_nodosEnvio || []).find(n => String(n.id_nodo) === String(val));
    const objBaj = (g_nodosEnvio || []).find(n => String(n.id_nodo) === String(li.getAttribute('data-id-nodo-bajada')));
    const ordSub = objSub ? parseInt(objSub.orden || 0) : 0;
    const ordBaj = objBaj ? parseInt(objBaj.orden || 0) : 0;

    if (ordSub >= ordBaj) {
        const candidateBaj = (g_nodosEnvio || []).find(n => parseInt(n.orden || 0) > ordSub);
        if (candidateBaj) {
            li.setAttribute('data-id-nodo-bajada', candidateBaj.id_nodo);
            li.setAttribute('data-id-parada', candidateBaj.id_nodo);
        }
    }

    const ul = li.closest('ul');
    if (ul) {
        actualizarConteoYSecuencia(ul);
        agruparYOrdenarParadas(ul);
        guardarAcomodoAuto();
    }
}

/** Cuando el usuario cambia el nodo de bajada de un VIN */
function li_setNodoBajada(selectEl) {
    const li = selectEl.closest('li');
    if (!li) return;
    const val = selectEl.value;
    li.setAttribute('data-id-nodo-bajada', val);
    li.setAttribute('data-id-parada', val);

    const ul = li.closest('ul');
    if (ul) {
        actualizarConteoYSecuencia(ul);
        agruparYOrdenarParadas(ul);
        guardarAcomodoAuto();
    }
}

/** Retrocompatibilidad: Cuando el usuario cambia la parada de un VIN desde el select de parada */
function li_setParada(selectEl) {
    const li = selectEl.closest('li');
    if (!li) return;
    const idParada = selectEl.value;
    li.setAttribute('data-id-parada', idParada);

    const objParadaSel = (g_paradasEnvio || []).find(p => String(p.id_parada) === String(idParada));
    const kmSpan = li.querySelector('.vin-km-tramo-badge');
    if (kmSpan) {
        if (objParadaSel && typeof objParadaSel.km_tramo !== 'undefined') {
            const kmTramo = parseFloat(objParadaSel.km_tramo || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
            const kmAcum  = parseFloat(objParadaSel.km_acumulado || objParadaSel.km_tramo || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
            kmSpan.innerHTML = `<i class="ri-map-pin-distance-line me-1"></i>+${kmTramo} km (Total: ${kmAcum} km)`;
            kmSpan.style.display = 'inline-block';
        } else {
            kmSpan.style.display = 'none';
        }
    }

    const ul = li.closest('ul');
    if (ul) {
        actualizarConteoYSecuencia(ul);
        agruparYOrdenarParadas(ul);
        guardarAcomodoAuto();
    } else {
        guardarAcomodoAuto();
    }
}

/** Agrupa y ordena VINs por LIFO (los que bajan más lejos van al fondo) */
function agruparYOrdenarParadas(ul) {
    if (!ul || ul.id === 'vins-disponibles') return;
    const items = Array.from(ul.querySelectorAll('li'));
    if (items.length <= 1) return;

    let listOrder = items.map(li => {
        let bId = li.getAttribute('data-id-nodo-bajada') || li.getAttribute('data-id-parada');
        let sId = li.getAttribute('data-id-nodo-subida');

        let objBaj = (g_nodosEnvio || []).find(n => String(n.id_nodo) === String(bId));
        if (!objBaj && g_paradasEnvio) {
            objBaj = g_paradasEnvio.find(p => String(p.id_parada) === String(bId));
        }
        let objSub = (g_nodosEnvio || []).find(n => String(n.id_nodo) === String(sId));

        return {
            li: li,
            ordenBajada: objBaj ? parseInt(objBaj.orden || 0) : 99,
            ordenSubida: objSub ? parseInt(objSub.orden || 0) : 0
        };
    });

    listOrder.sort((a, b) => {
        if (b.ordenBajada !== a.ordenBajada) {
            return b.ordenBajada - a.ordenBajada;
        }
        return a.ordenSubida - b.ordenSubida;
    });

    let cambio = false;
    listOrder.forEach((item, idx) => {
        if (items[idx] !== item.li) {
            cambio = true;
        }
    });

    if (cambio) {
        listOrder.forEach(item => ul.appendChild(item.li));
        actualizarConteoYSecuencia(ul);
    }
}

function asignarVinRapido(btn) {
    if (typeof ENVIO_READONLY !== 'undefined' && ENVIO_READONLY) return;
    const li = btn.closest('li');
    if (!li) return;

    // Buscar contenedores de vehículos en la columna derecha
    const vehiculos = Array.from(document.querySelectorAll('.vehiculo-list'));
    if (vehiculos.length === 0) {
        Swal.fire("Atención", "Primero debes agregar un vehículo o conductor con el botón 'Agregar Vehículo' para poder asignarle unidades.", "warning");
        return;
    }

    // Encontrar el primer vehículo con espacio disponible
    let destinoUl = null;
    for (const vUl of vehiculos) {
        const cap = parseInt(vUl.getAttribute('data-capacidad') || '99', 10);
        const asignados = vUl.querySelectorAll('li').length;
        if (asignados < cap) {
            destinoUl = vUl;
            break;
        }
    }

    if (!destinoUl) {
        Swal.fire("Capacidad Llena", "Todos los vehículos agregados han alcanzado su capacidad máxima. Agrega otro vehículo si requieres asignar más unidades.", "warning");
        return;
    }

    // Mover el elemento li
    destinoUl.appendChild(li);
    btn.remove();
    actualizarConteoYSecuencia(destinoUl);
    agruparYOrdenarParadas(destinoUl);
    actualizarContadorPoolVins();
    guardarAcomodoAuto();
}

function actualizarContadorPoolVins() {
    if (typeof aplicarFiltrosPoolVins === 'function') {
        aplicarFiltrosPoolVins();
        return;
    }
    const pool = document.getElementById('vins-disponibles');
    const lblPool = document.getElementById('badge-total-pool-vins');
    if (pool && lblPool) {
        const disponibles = pool.querySelectorAll('li:not(.disabled-vin)').length;
        lblPool.innerText = `${disponibles} disponibles`;
    }
}

function limpiarBadgesPool(li) {
    if (!li) return;
    const seEnvia = parseInt(li.getAttribute('data-se-envia') || '0', 10) === 1;
    li.classList.remove('border-success', 'border-primary');
    li.classList.add(seEnvia ? 'border-success' : 'border-primary');

    const vin      = li.getAttribute('data-vin') || li.querySelector('h6')?.innerText.replace('VIN:', '').trim() || '';
    const numSerie = li.getAttribute('data-num-serie') || '';
    const modelo   = li.getAttribute('data-modelo') || 'Unidad';
    const origen   = li.getAttribute('data-origen') || 'Origen';
    const destino  = li.getAttribute('data-destino') || 'Destino';

    const badgesHtml = buildBadgesEstatusUnidad({
        cliente_nombre:      li.getAttribute('data-cliente') || '',
        folio_pedido:        li.getAttribute('data-pedido') || '',
        factura:             li.getAttribute('data-factura') || '',
        estatus_pago_cod:    li.getAttribute('data-pago-cod') ?? '-1',
        estatus_pago_texto:  li.getAttribute('data-pago-texto') || 'Sin Facturar',
        cumple_factura:      li.getAttribute('data-cumple-factura') || '0',
        cumple_pago:         li.getAttribute('data-cumple-pago') || '0',
        cumple_autorizacion: li.getAttribute('data-cumple-aut') || '0',
        autorizado_excepcion:li.getAttribute('data-autorizado-excepcion') || '0',
        se_envia:            li.getAttribute('data-se-envia') || '0',
        estado_envio_motivo: li.getAttribute('data-estado-motivo') || '',
        preenvio_folios:     li.getAttribute('data-preenvios') || '',
    });

    let btnAsignar = (typeof ENVIO_READONLY === 'undefined' || !ENVIO_READONLY)
        ? `<button type="button" class="btn btn-sm btn-outline-success py-0 px-2 fs-11 ms-1" onclick="asignarVinRapido(this)" title="Asignar al vehículo activo"><i class="ri-arrow-right-line me-1"></i>Asignar</button>`
        : '';

    li.innerHTML = `
    <div class="d-flex align-items-center">
        <div class="flex-shrink-0 me-2">
            <i class="ri-draggable fs-18 text-muted"></i>
        </div>
        <div class="flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-1">
                <h6 class="mb-0 fs-13 text-primary fw-bold font-monospace">${escapeHtml(vin)}</h6>
                <div class="d-flex align-items-center flex-wrap gap-1">
                    <span class="badge bg-soft-info text-info fs-11">${escapeHtml(modelo)}</span>
                    ${btnAsignar}
                </div>
            </div>
            <p class="text-dark mb-1 fs-11 fw-semibold">
                <i class="ri-map-pin-line text-danger me-1"></i>${escapeHtml(origen)}
                <i class="ri-arrow-right-line mx-1 text-muted"></i>
                <i class="ri-map-pin-2-fill text-success me-1"></i>${escapeHtml(destino)}
            </p>
            <div class="d-flex flex-wrap gap-1 align-items-center">
                ${badgesHtml}
                <small class="text-muted fs-10 ms-auto">N/S: ${escapeHtml(numSerie || 'N/A')}</small>
            </div>
        </div>
    </div>`;
}

function agregarVehiculo() {
    // Llenar tabla de Madrinas en el Modal
    const tbodyM = document.getElementById('tbodyModalMadrinas');
    if (tbodyM) {
        if (g_madrinasProveedor.length === 0) {
            tbodyM.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No hay madrinas registradas para este trasladista.</td></tr>`;
        } else {
            let htmlM = '';
            g_madrinasProveedor.forEach(m => {
                htmlM += `
                <tr>
                    <td><strong class="text-primary">${m.numero_economico || 'S/N'}</strong></td>
                    <td>${m.placas || '-'} ${m.placa_caja ? '/ Caja: ' + m.placa_caja : ''}</td>
                    <td><span class="badge bg-soft-info text-info fs-12">${m.capacidad_vehiculos || 0} Vehículos</span></td>
                    <td>${m.chofer_asignado || '<span class="text-muted">Sin Chofer Asignado</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-success px-3" onclick="seleccionarMadrina(${m.id_madrina})">
                            <i class="ri-check-line me-1"></i> Asignar
                        </button>
                    </td>
                </tr>`;
            });
            tbodyM.innerHTML = htmlM;
        }
    }

    // Llenar tabla de Choferes en el Modal
    const tbodyC = document.getElementById('tbodyModalChoferes');
    if (tbodyC) {
        if (g_choferesProveedor.length === 0) {
            tbodyC.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay choferes registrados para este trasladista.</td></tr>`;
        } else {
            let htmlC = '';
            g_choferesProveedor.forEach(c => {
                htmlC += `
                <tr>
                    <td><strong class="text-dark">${c.nombre_completo}</strong></td>
                    <td>${c.num_licencia || '-'}</td>
                    <td>${c.tipo_licencia || '-'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-primary px-3" onclick="seleccionarChofer(${c.id_chofer})">
                            <i class="ri-check-line me-1"></i> Seleccionar (Rodando)
                        </button>
                    </td>
                </tr>`;
            });
            tbodyC.innerHTML = htmlC;
        }
    }

    // Llenar tabla de Plataformas en el Modal
    const tbodyP = document.getElementById('tbodyModalPlataformas');
    if (tbodyP) {
        if (g_plataformasProveedor.length === 0) {
            tbodyP.innerHTML = `<tr><td colspan="5" class="text-center text-muted">No hay plataformas registradas para este trasladista.</td></tr>`;
        } else {
            let htmlP = '';
            g_plataformasProveedor.forEach(p => {
                htmlP += `
                <tr>
                    <td><strong class="text-primary">${p.numero_economico || 'S/N'}</strong></td>
                    <td>${p.placas || '-'}</td>
                    <td><span class="badge bg-soft-info text-info fs-12">${p.capacidad_vehiculos || 0} Vehículos</span></td>
                    <td>${p.chofer_asignado || '<span class="text-muted">Sin Chofer Asignado</span>'}</td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-success px-3" onclick="seleccionarPlataforma(${p.id_plataforma})">
                            <i class="ri-check-line me-1"></i> Asignar
                        </button>
                    </td>
                </tr>`;
            });
            tbodyP.innerHTML = htmlP;
        }
    }

    // Filtrar pestañas según Tipo de Traslado (1 = Madrina, 2 = Chofer Rodando, 3 = Plataforma)
    const idTipoTraslado = parseInt(g_envioData ? g_envioData.id_tipo_traslado : 1);
    const tabMadrinasNav = document.getElementById('nav-tab-madrinas');
    const tabChoferesNav = document.getElementById('nav-tab-choferes');
    const tabPlataformasNav = document.getElementById('nav-tab-plataformas');
    const linkMadrinas  = document.getElementById('link-tab-madrinas');
    const linkChoferes  = document.getElementById('link-tab-choferes');
    const linkPlataformas  = document.getElementById('link-tab-plataformas');
    const paneMadrinas  = document.getElementById('tab-madrinas');
    const paneChoferes  = document.getElementById('tab-choferes');
    const panePlataformas  = document.getElementById('tab-plataformas');
    const modalTitle    = document.getElementById('modalVehiculoLabel');

    if (idTipoTraslado === 1) {
        // Es Traslado en Madrina: Mostrar solo pestaña de Madrinas
        if (tabMadrinasNav) tabMadrinasNav.style.display = 'block';
        if (tabChoferesNav) tabChoferesNav.style.display = 'none';
        if (tabPlataformasNav) tabPlataformasNav.style.display = 'none';

        if (linkMadrinas) linkMadrinas.classList.add('active');
        if (linkChoferes) linkChoferes.classList.remove('active');
        if (linkPlataformas) linkPlataformas.classList.remove('active');
        
        if (paneMadrinas) paneMadrinas.classList.add('show', 'active');
        if (paneChoferes) paneChoferes.classList.remove('show', 'active');
        if (panePlataformas) panePlataformas.classList.remove('show', 'active');

        if (modalTitle) modalTitle.innerHTML = '<i class="ri-truck-line me-2"></i> Seleccionar Madrina del Trasladista';
    } else if (idTipoTraslado === 3) {
        // Es Traslado en Plataforma: Mostrar solo pestaña de Plataformas
        if (tabMadrinasNav) tabMadrinasNav.style.display = 'none';
        if (tabChoferesNav) tabChoferesNav.style.display = 'none';
        if (tabPlataformasNav) tabPlataformasNav.style.display = 'block';

        if (linkPlataformas) linkPlataformas.classList.add('active');
        if (linkMadrinas) linkMadrinas.classList.remove('active');
        if (linkChoferes) linkChoferes.classList.remove('active');
        
        if (panePlataformas) panePlataformas.classList.add('show', 'active');
        if (paneMadrinas) paneMadrinas.classList.remove('show', 'active');
        if (paneChoferes) paneChoferes.classList.remove('show', 'active');

        if (modalTitle) modalTitle.innerHTML = '<i class="ri-truck-line me-2"></i> Seleccionar Plataforma del Trasladista';
    } else {
        // Es Traslado por Chofer (Rodando): Mostrar solo pestaña de Choferes
        if (tabMadrinasNav) tabMadrinasNav.style.display = 'none';
        if (tabChoferesNav) tabChoferesNav.style.display = 'block';
        if (tabPlataformasNav) tabPlataformasNav.style.display = 'none';

        if (linkChoferes) linkChoferes.classList.add('active');
        if (linkMadrinas) linkMadrinas.classList.remove('active');
        if (linkPlataformas) linkPlataformas.classList.remove('active');
        
        if (paneChoferes) paneChoferes.classList.add('show', 'active');
        if (paneMadrinas) paneMadrinas.classList.remove('show', 'active');
        if (panePlataformas) panePlataformas.classList.remove('show', 'active');

        if (modalTitle) modalTitle.innerHTML = '<i class="ri-steering-2-line me-2"></i> Seleccionar Conductor (Rodando) del Trasladista';
    }

    // Abrir Modal
    const elModal = document.getElementById('modalAgregarVehiculo');
    if (elModal) {
        modalVehiculoBs = modalVehiculoBs || new bootstrap.Modal(elModal);
        modalVehiculoBs.show();
    }
}

function seleccionarMadrina(idMadrina) {
    const madrina = g_madrinasProveedor.find(m => m.id_madrina == idMadrina);
    if (!madrina) return;

    // Verificar si ya fue agregada
    if (document.querySelector(`.vehiculo-list[data-id-madrina="${idMadrina}"]`)) {
        Swal.fire("Atención", "Esta Madrina ya ha sido agregada a la lista.", "warning");
        return;
    }

    inyectarContenedorVehiculo({
        id_madrina: idMadrina,
        id_plataforma: null,
        id_chofer: null,
        titulo: `Madrina ${madrina.numero_economico} (Placas: ${madrina.placas || 'S/P'}) - Chofer: ${madrina.chofer_asignado || 'Sin asignar'}`,
        capacidad: madrina.capacidad_vehiculos || 99
    });

    if (modalVehiculoBs) modalVehiculoBs.hide();
}

function seleccionarPlataforma(idPlataforma) {
    const plataforma = g_plataformasProveedor.find(p => p.id_plataforma == idPlataforma);
    if (!plataforma) return;

    if (document.querySelector(`.vehiculo-list[data-id-plataforma="${idPlataforma}"]`)) {
        Swal.fire("Atención", "Esta Plataforma ya ha sido agregada a la lista.", "warning");
        return;
    }

    inyectarContenedorVehiculo({
        id_madrina: null,
        id_plataforma: idPlataforma,
        id_chofer: null,
        titulo: `Plataforma ${plataforma.numero_economico} (Placas: ${plataforma.placas || 'S/P'}) - Chofer: ${plataforma.chofer_asignado || 'Sin asignar'}`,
        capacidad: plataforma.capacidad_vehiculos || 99
    });

    if (modalVehiculoBs) modalVehiculoBs.hide();
}

function toggleFormPlataformaTemporal() {
    const f = document.getElementById('form-plataforma-temporal');
    if (!f) return;
    f.style.display = (f.style.display === 'none' || !f.style.display) ? 'block' : 'none';
}

function agregarPlataformaTemporal() {
    const inpEco = document.getElementById('tmp_plat_economico');
    const inpPlacas = document.getElementById('tmp_plat_placas');
    const inpChofer = document.getElementById('tmp_plat_chofer');
    const inpCap = document.getElementById('tmp_plat_capacidad');

    const eco = (inpEco?.value || 'Subcontratada').trim();
    const placas = (inpPlacas?.value || 'S/P').trim();
    const chofer = (inpChofer?.value || 'Por confirmar').trim();
    const cap = parseInt(inpCap?.value || '1', 10) || 1;

    // Asignar ID temporal único
    const tmpId = -1 * (Math.floor(Date.now() / 1000) + Math.floor(Math.random() * 500));

    inyectarContenedorVehiculo({
        id_madrina: null,
        id_plataforma: tmpId,
        id_chofer: null,
        titulo: `Plataforma Subcontratada: ${eco} (Placas: ${placas}) - Operador: ${chofer}`,
        capacidad: cap
    });

    if (modalVehiculoBs) modalVehiculoBs.hide();
}


function seleccionarChofer(idChofer) {
    const chofer = g_choferesProveedor.find(c => c.id_chofer == idChofer);
    if (!chofer) return;

    if (document.querySelector(`.vehiculo-list[data-id-chofer="${idChofer}"]`)) {
        Swal.fire("Atención", "Este Chofer ya ha sido agregado a la lista.", "warning");
        return;
    }

    inyectarContenedorVehiculo({
        id_madrina: null,
        id_chofer: idChofer,
        titulo: `Chofer Rodando: ${chofer.nombre_completo} (Licencia: ${chofer.num_licencia || 'S/L'})`,
        capacidad: 1
    });

    if (modalVehiculoBs) modalVehiculoBs.hide();
}

function inyectarContenedorVehiculo(vehiculo, vinsIniciales = []) {
    const msgEmpty = document.getElementById('empty-vehiculos-msg');
    if (msgEmpty) msgEmpty.style.display = 'none';

    const contenedor = document.getElementById('contenedor-vehiculos');
    if (!contenedor) return;

    const divCard = document.createElement('div');
    divCard.className = 'envios-surface-card vehiculo-card-item p-3 mb-4';
    
    let htmlVins = '';
    vinsIniciales.forEach((v, idx) => {
        const mod = v.modelo || 'Unidad';
        const orig = v.origen || 'Origen';
        const dest = v.destino || 'Destino';
        const posIndex = idx + 1;
        const badgeSecuencia = (posIndex === 1) 
            ? `<span class="badge bg-success px-2 py-1 fs-11 me-1"><i class="ri-number-1 me-1"></i>1º EN CARGAR</span>`
            : `<span class="badge bg-info px-2 py-1 fs-11 me-1"><i class="ri-truck-line me-1"></i>${posIndex}º EN CARGAR</span>`;

        htmlVins += `
        <li class="list-group-item cursor-move shadow-sm mb-2 rounded-3 border-start border-3 border-success bg-white" 
            data-id-unidad="${v.id_unidad || 0}"
            data-id-numeros-serie="${v.id_numeros_serie || 0}"
            data-id-cliente="${v.id_cliente || 0}"
            data-vin="${escapeHtml(v.vin || '')}"
            data-num-serie="${escapeHtml(v.num_serie || '')}"
            data-modelo="${escapeHtml(mod)}"
            data-cliente="${escapeHtml(v.cliente_nombre || '')}"
            data-pedido="${escapeHtml(v.folio_pedido || '')}"
            data-factura="${escapeHtml(v.factura || '')}"
            data-pago-cod="${v.estatus_pago_cod !== undefined && v.estatus_pago_cod !== null ? v.estatus_pago_cod : -1}"
            data-pago-texto="${escapeHtml(v.estatus_pago_texto || '')}"
            data-cumple-factura="${v.cumple_factura ? 1 : 0}"
            data-cumple-pago="${v.cumple_pago ? 1 : 0}"
            data-cumple-aut="${v.cumple_autorizacion ? 1 : 0}"
            data-autorizado-excepcion="${v.autorizado_excepcion ? 1 : 0}"
            data-se-envia="${v.se_envia ? 1 : 0}"
            data-estado-motivo="${escapeHtml(v.estado_envio_motivo || '')}"
            data-origen="${escapeHtml(orig)}"
            data-destino="${escapeHtml(dest)}"
            data-preenvios="${escapeHtml(v.preenvio_folios || '')}"
            data-id-parada="${v.id_parada || v.id_nodo_bajada || ''}"
            data-id-nodo-subida="${v.id_nodo_subida || ''}"
            data-id-nodo-bajada="${v.id_nodo_bajada || v.id_parada || ''}">
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-2">
                    <i class="ri-draggable fs-18 text-muted"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <h6 class="mb-0 fs-13 text-success fw-bold">VIN: ${v.vin}</h6>
                        <div>
                            ${badgeSecuencia}
                            <span class="badge bg-soft-secondary text-dark fs-11">${mod}</span>
                        </div>
                    </div>
                    <p class="text-dark mb-0 fs-11 fw-semibold">
                        <i class="ri-map-pin-line text-danger me-1"></i>${orig} 
                        <i class="ri-arrow-right-line mx-1 text-muted"></i> 
                        <i class="ri-map-pin-2-fill text-success me-1"></i>${dest}
                    </p>
                    <small class="text-muted fs-11">N/S: ${v.num_serie || 'N/A'}</small>
                </div>
            </div>
        </li>`;
    });

    let attrMadrina    = vehiculo.id_madrina ? `data-id-madrina="${vehiculo.id_madrina}"` : '';
    let attrPlataforma = vehiculo.id_plataforma ? `data-id-plataforma="${vehiculo.id_plataforma}"` : '';
    let attrChofer     = vehiculo.id_chofer ? `data-id-chofer="${vehiculo.id_chofer}"` : '';

    let iconoTitulo = 'ri-steering-2-line text-warning';
    if (vehiculo.id_madrina) iconoTitulo = 'ri-truck-line text-primary';
    if (vehiculo.id_plataforma) iconoTitulo = 'ri-truck-line text-info';

    divCard.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h6 class="fw-bold text-dark mb-0 fs-14">
                <i class="${iconoTitulo} me-2 fs-16 align-middle"></i>
                ${vehiculo.titulo}
            </h6>
            <div class="d-flex align-items-center">
                <span class="badge bg-primary-subtle text-primary rounded-pill me-2 fs-11 px-2 py-1 fw-bold">${vinsIniciales.length} / ${vehiculo.capacidad} VINs</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="removerContenedorVehiculo(this)" title="Quitar Vehículo">
                    <i class="ri-delete-bin-line fs-16"></i>
                </button>
            </div>
        </div>
        <ul class="list-group sortable-list vehiculo-list" ${attrMadrina} ${attrPlataforma} ${attrChofer} data-capacidad="${vehiculo.capacidad}" style="min-height: 110px; border: 2px dashed #CBD5E1; border-radius: 12px;">
            ${htmlVins}
        </ul>
    `;

    contenedor.appendChild(divCard);
    initSortables();
}

function removerContenedorVehiculo(btn) {
    const card = btn.closest('.envios-surface-card') || btn.closest('div.border');
    if (!card) return;

    // Regresar VINs al pool disponible antes de remover
    const listUl = card.querySelector('.vehiculo-list');
    if (listUl) {
        const poolUl = document.getElementById('vins-disponibles');
        const items = listUl.querySelectorAll('li');
        items.forEach(li => {
            if (poolUl) poolUl.appendChild(li);
        });
    }

    card.remove();

    const contenedor = document.getElementById('contenedor-vehiculos');
    if (contenedor && contenedor.querySelectorAll('.vehiculo-card-item, .envios-surface-card, div.border').length === 0) {
        const msgEmpty = document.getElementById('empty-vehiculos-msg');
        if (msgEmpty) msgEmpty.style.display = 'block';
    }

    guardarAcomodoAuto();
}

function recolectarAsignacionesAcomodo() {
    const asignaciones = [];
    const vistos = new Set();
    const vehiculos = document.querySelectorAll('.vehiculo-list');

    vehiculos.forEach(v => {
        const idMadrina    = v.getAttribute('data-id-madrina') || null;
        const idPlataforma = v.getAttribute('data-id-plataforma') || null;
        const idChofer     = v.getAttribute('data-id-chofer') || null;
        const items        = v.querySelectorAll('li');

        let posicion = 1;
        items.forEach(li => {
            const idUnidad = parseInt(li.getAttribute('data-id-unidad') || '0', 10) || 0;
            const idNs     = parseInt(li.getAttribute('data-id-numeros-serie') || '0', 10) || 0;
            const vin      = (li.getAttribute('data-vin') || '').trim().toUpperCase();
            if (!idUnidad && !vin) return;

            const dedupKey = vin ? ('V:' + vin) : ('U:' + idUnidad);
            if (vistos.has(dedupKey)) return;
            vistos.add(dedupKey);

            let idSubida = li.getAttribute('data-id-nodo-subida') || null;
            let idBajada = li.getAttribute('data-id-nodo-bajada') || null;
            let idParada = li.getAttribute('data-id-parada') || idBajada;

            const selSub = li.querySelector('.nodo-subida-select');
            if (selSub && selSub.value) idSubida = selSub.value;

            const selBaj = li.querySelector('.nodo-bajada-select');
            if (selBaj && selBaj.value) {
                idBajada = selBaj.value;
                idParada = selBaj.value;
            }

            const selLegacy = li.querySelector('.parada-vin-select');
            if (selLegacy && selLegacy.value) {
                idParada = selLegacy.value;
                if (!idBajada) idBajada = selLegacy.value;
            }

            asignaciones.push({
                id_unidad: idUnidad,
                id_numeros_serie: idNs,
                vin: vin,
                num_serie: li.getAttribute('data-num-serie') || '',
                modelo: li.getAttribute('data-modelo') || '',
                origen: li.getAttribute('data-origen') || '',
                destino: li.getAttribute('data-destino') || '',
                id_parada: idParada,
                id_nodo_subida: idSubida,
                id_nodo_bajada: idBajada,
                id_madrina: idMadrina,
                id_plataforma: idPlataforma,
                id_chofer: idChofer,
                posicion_acomodo: posicion++,
                tipo_servicio: li.getAttribute('data-tipo-servicio') || null
            });
        });
    });

    return asignaciones;
}

function guardarAcomodoAuto() {
    if (typeof ENVIO_READONLY !== 'undefined' && ENVIO_READONLY === true) {
        return;
    }
    const idEnvio = document.getElementById('id_envio') ? document.getElementById('id_envio').value : 0;
    if (!idEnvio) return;

    const asignaciones = recolectarAsignacionesAcomodo();

    const lblCosto = document.getElementById('lbl-resumen-costo');
    const prevCostoTxt = lblCosto ? (lblCosto.innerText || '$0.00') : '$0.00';

    const idTipoTrasladoAct = parseInt(g_envioData ? g_envioData.id_tipo_traslado : 1);
    if (idTipoTrasladoAct === 1 && asignaciones.length === 1) {
        if (lblCosto) lblCosto.innerHTML = '<span class="text-warning fs-11 fw-semibold" title="Mínimo 2 unidades en Madrina"><i class="ri-alert-line me-1"></i>Requiere mín. 2 uds en Madrina</span>';
        return;
    }

    if (lblCosto) lblCosto.innerHTML = '<i class="spinner-border spinner-border-sm me-1"></i> Calculando...';

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/storeAcomodo';

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({
        id_envio: idEnvio,
        asignaciones: asignaciones
    }));

    const mostrarErrorCosto = (msgError) => {
        if (!lblCosto) return;
        const msgLimpio = String(msgError || 'Error al recalcular costo');
        const span = document.createElement('span');
        span.className = 'text-danger fs-11 fw-semibold';
        span.setAttribute('title', msgLimpio);
        const icon = document.createElement('i');
        icon.className = 'ri-alert-line me-1';
        span.appendChild(icon);
        span.appendChild(document.createTextNode(msgLimpio.length > 65 ? msgLimpio.slice(0, 62) + '...' : msgLimpio));
        lblCosto.replaceChildren(span);
    };

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === 'success' || objData.code === 200) {
                        if (lblCosto) {
                            let costoTxt = objData.data && objData.data.costo_total ? '$' + parseFloat(objData.data.costo_total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '$0.00';
                            lblCosto.textContent = costoTxt;
                        }
                        if (objData.data && objData.data.auditoria_financiera) {
                            actualizarAuditoriaFinancieraUI(objData.data.auditoria_financiera);
                        }
                    } else {
                        mostrarErrorCosto(objData.message || objData.msg || prevCostoTxt);
                    }
                } catch (e) {
                    if (lblCosto) lblCosto.textContent = prevCostoTxt;
                }
            } else {
                try {
                    let errData = JSON.parse(request.responseText);
                    mostrarErrorCosto(errData.message || errData.msg || prevCostoTxt);
                } catch (e) {
                    if (lblCosto) lblCosto.textContent = prevCostoTxt;
                }
            }
        }
    }
}

function guardarAcomodo(finalizar = false) {
    if (finalizar) {
        const esPre = (typeof g_envioData !== 'undefined' && g_envioData && (parseInt(g_envioData.es_preenvio) === 1 || String(g_envioData.folio || '').startsWith('PE-')));
        Swal.fire({
            title: esPre ? "¿Guardar Acomodo del Pre-envío?" : "¿Confirmar y Finalizar Envío?",
            text: esPre
                ? "Se guardará el acomodo de unidades y vehículos de este Pre-envío, dejándolo listo para convertir a Envío Oficial (EN-)."
                : "Al finalizar, este envío quedará confirmado y disponible para agruparse en Planeaciones. ¿Desea continuar?",
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: esPre ? "#0284C7" : "#C46623",
            confirmButtonText: esPre ? "Sí, guardar acomodo" : "Sí, finalizar",
            cancelButtonText: "Cancelar"
        }).then((result) => {
            if (result.isConfirmed) {
                procesarAcomodo(true);
            }
        });
    } else {
        procesarAcomodo(false);
    }
}

function procesarAcomodo(finalizar) {
    const idEnvio = document.getElementById('id_envio') ? document.getElementById('id_envio').value : 0;
    if (!idEnvio) {
        Swal.fire("Error", "No se encontró el ID del envío.", "error");
        return;
    }

    const asignaciones = recolectarAsignacionesAcomodo();

    if (asignaciones.length === 0) {
        Swal.fire("Atención", "Arrastre al menos un VIN desde el pool disponible hacia una Madrina o Chofer.", "warning");
        return;
    }

    const idTipoTrasladoAct = parseInt(g_envioData ? g_envioData.id_tipo_traslado : 1);
    if (idTipoTrasladoAct === 1 && asignaciones.length === 1) {
        Swal.fire({
            title: "Capacidad Mínima de Madrina",
            text: "No se puede enviar solo 1 unidad en una Madrina. Para traslados individuales de una sola unidad debe utilizarse Plataforma o Chofer (Rodando).",
            icon: "warning"
        });
        return;
    }

    // Validar si algún vehículo excede su capacidad física en algún tramo
    const vehiculos = document.querySelectorAll('.vehiculo-list');
    let haySobrecapacidad = false;
    let mensajeSobrecapacidad = '';
    vehiculos.forEach(v => {
        const cap = parseInt(v.getAttribute('data-capacidad') || 99, 10);
        const items = v.querySelectorAll('li');
        if (g_nodosEnvio && g_nodosEnvio.length > 1) {
            for (let i = 1; i < g_nodosEnvio.length; i++) {
                const ordAnt = parseInt(g_nodosEnvio[i - 1].orden || 0);
                const ordAct = parseInt(g_nodosEnvio[i].orden || 0);
                let count = 0;
                items.forEach(li => {
                    const sId = li.getAttribute('data-id-nodo-subida');
                    const bId = li.getAttribute('data-id-nodo-bajada');
                    const oSub = g_nodosEnvio.find(n => String(n.id_nodo) === String(sId));
                    const oBaj = g_nodosEnvio.find(n => String(n.id_nodo) === String(bId));
                    const ordS = oSub ? parseInt(oSub.orden || 0) : 0;
                    const ordB = oBaj ? parseInt(oBaj.orden || 0) : (g_nodosEnvio.length - 1);
                    if (ordS <= ordAnt && ordB >= ordAct) count++;
                });
                if (count > cap) {
                    haySobrecapacidad = true;
                    mensajeSobrecapacidad = `El vehículo excede su capacidad (${cap} unidades) en el Tramo N${ordAnt}➔N${ordAct} (${count} unidades a bordo).`;
                    break;
                }
            }
        }
    });

    if (haySobrecapacidad) {
        Swal.fire({
            title: "Capacidad Excedida",
            text: mensajeSobrecapacidad + " ¿Desea continuar de todos modos o ajustar las unidades?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Continuar de todos modos",
            cancelButtonText: "Ajustar acomodo"
        }).then(result => {
            if (result.isConfirmed) {
                enviarAcomodoAlServidor(idEnvio, asignaciones, finalizar);
            }
        });
        return;
    }

    enviarAcomodoAlServidor(idEnvio, asignaciones, finalizar);
}

function enviarAcomodoAlServidor(idEnvio, asignaciones, finalizar = false) {
    Swal.fire({
        title: 'Guardando Acomodo...',
        text: 'Por favor espere mientras se asignan los VINs y se recalculan los costos del envío.',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    let request = new XMLHttpRequest();
    let ajaxUrl = base_url + '/Lgs_envios/storeAcomodo';

    request.open("POST", ajaxUrl, true);
    request.setRequestHeader("Content-Type", "application/json");
    request.send(JSON.stringify({
        id_envio: idEnvio,
        asignaciones: asignaciones,
        finalizar: finalizar
    }));

    request.onreadystatechange = function () {
        if (request.readyState == 4) {
            if (request.status == 200) {
                try {
                    let objData = JSON.parse(request.responseText);
                    if (objData.status === 'success' || objData.code === 200) {
                        if (objData.data && objData.data.requires_authorization) {
                            const folioActual = (g_envioData && g_envioData.folio) ? g_envioData.folio : ('#' + idEnvio);
                            mostrarModalSolicitudExcepcionDetalle(idEnvio, folioActual, objData.data, true);
                            return;
                        }
                        let costoTxt = objData.data && objData.data.costo_total ? '$' + parseFloat(objData.data.costo_total).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '$0.00';
                        const esPre = (typeof g_envioData !== 'undefined' && g_envioData && (parseInt(g_envioData.es_preenvio) === 1 || String(g_envioData.folio || '').startsWith('PE-')));
                        if (esPre) {
                            Swal.fire({
                                title: "¡Acomodo de Pre-envío Guardado!",
                                text: `Unidades asignadas correctamente (Costo estimado: ${costoTxt}). ¿Deseas pasarlo ahora a Envío Oficial (EN-) o volver a Pre-envíos?`,
                                icon: "success",
                                showCancelButton: true,
                                confirmButtonColor: "#198754",
                                cancelButtonColor: "#64748B",
                                confirmButtonText: '<i class="ri-exchange-line me-1"></i> Pasar a Envío (EN-) Ahora',
                                cancelButtonText: "Volver a Pre-envíos"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    convertirPreEnvioAEnvioDesdeDetalle(idEnvio);
                                } else {
                                    window.location.href = base_url + '/Lgs_bandeja?tab=preenvios';
                                }
                            });
                        } else {
                            Swal.fire({
                                title: "¡Acomodo Finalizado!",
                                text: `Las unidades han sido asignadas correctamente (Costo estimado: ${costoTxt}). El envío está listo para Planeaciones.`,
                                icon: "success",
                                showCancelButton: true,
                                confirmButtonColor: "#C46623",
                                cancelButtonColor: "#64748B",
                                confirmButtonText: "Volver a Bandeja de Envíos",
                                cancelButtonText: '<i class="ri-file-list-3-line me-1"></i> Ir a Planeaciones'
                            }).then((result) => {
                                if (result.dismiss === Swal.DismissReason.cancel) {
                                    window.location.href = base_url + '/Lgs_planeaciones';
                                } else {
                                    window.location.href = base_url + '/Lgs_envios';
                                }
                            });
                        }
                    } else {
                        Swal.fire("Error", objData.message || "Error al guardar el acomodo.", "error");
                    }
                } catch (e) {
                    Swal.fire("Error", "Respuesta no válida del servidor.", "error");
                }
            } else {
                try {
                    let objData = JSON.parse(request.responseText);
                    Swal.fire("Error (" + request.status + ")", objData.message || "Error en el servidor.", "error");
                } catch (e) {
                    Swal.fire("Error (" + request.status + ")", "Error en el servidor al guardar el acomodo.", "error");
                }
            }
        }
    }
}

function renderInfografiaRuta() {
    const container = document.getElementById('panel-infografia-ruta');
    if (!container) return;

    if (!g_nodosEnvio || g_nodosEnvio.length === 0) {
        container.innerHTML = '';
        return;
    }

    const modalidad = (g_envioData && g_envioData.modalidad_calculo) ? g_envioData.modalidad_calculo : 'ESCALONADO';
    const esDirecto = (modalidad === 'DIRECTO');

    let html = `
    <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px; background: linear-gradient(to right, #f8f9fa, #ffffff);">
        <div class="card-header border-bottom border-light bg-transparent py-3 d-flex align-items-center justify-content-between">
            <h6 class="card-title mb-0 fw-bold text-uppercase fs-12 text-primary">
                <i class="ri-git-merge-line me-1 fs-15 align-middle"></i> Infografía de Factoraje y Ruta
            </h6>
            <div>
                ${(g_envioData && parseInt(g_envioData.es_monto_abierto || 0) === 1)
                    ? '<span class="badge bg-warning text-dark border border-warning fs-11" title="' + (g_envioData.motivo_monto_abierto || 'Acuerdo Comercial') + '"><i class="ri-hand-coin-line me-1"></i>Acuerdo Comercial (Monto Abierto)</span>'
                    : (esDirecto 
                        ? '<span class="badge bg-soft-info text-info border border-info fs-11"><i class="ri-direction-line me-1"></i>Modalidad: Directo con Escalas por Origen</span>' 
                        : '<span class="badge bg-soft-secondary text-secondary border fs-11"><i class="ri-route-line me-1"></i>Modalidad: Escalonado Proporcional</span>')}
            </div>
        </div>
        <div class="card-body p-4">
            <div class="d-flex align-items-stretch justify-content-start overflow-auto pb-3" style="min-width: max-content;">
    `;

    // Obtener todos los VINs asignados a los vehículos (ignorar el pool)
    const vehiculos = document.querySelectorAll('.vehiculo-list');
    const todosLosVins = [];
    vehiculos.forEach(v => {
        v.querySelectorAll('li').forEach(li => {
            todosLosVins.push({
                sId: li.getAttribute('data-id-nodo-subida'),
                bId: li.getAttribute('data-id-nodo-bajada')
            });
        });
    });

    let factorAcumulado = 0; // Asumiremos 1 unidad = 1 factor volumétrico estándar

    for (let i = 0; i < g_nodosEnvio.length; i++) {
        const nodoAct = g_nodosEnvio[i];
        const ordAct = parseInt(nodoAct.orden || 0);

        // Calcular cuántos suben y cuántos bajan
        let suben = 0;
        let bajan = 0;
        todosLosVins.forEach(vin => {
            const oSub = g_nodosEnvio.find(n => String(n.id_nodo) === String(vin.sId));
            const oBaj = g_nodosEnvio.find(n => String(n.id_nodo) === String(vin.bId));
            const ordS = oSub ? parseInt(oSub.orden || 0) : 0;
            const ordB = oBaj ? parseInt(oBaj.orden || 0) : (g_nodosEnvio.length - 1);
            
            if (ordS === ordAct) suben++;
            if (ordB === ordAct && ordB !== ordS) bajan++; 
        });

        factorAcumulado += suben;
        factorAcumulado -= bajan;

        const isPrimerNodo = (i === 0);
        const isUltimoNodo = (i === g_nodosEnvio.length - 1);

        html += `
            <div class="d-flex flex-column align-items-center position-relative" style="min-width: 160px;">
                <div class="text-center mb-2">
                    <span class="badge ${isPrimerNodo ? 'bg-primary' : (isUltimoNodo ? 'bg-dark' : 'bg-secondary')} px-2 py-1 fs-10 text-uppercase rounded-pill shadow-sm">
                        ${isPrimerNodo ? 'Origen' : (isUltimoNodo ? 'Destino Final' : 'Pto ' + (ordAct + 1))}
                    </span>
                </div>
                
                <div class="rounded-circle d-flex align-items-center justify-content-center shadow" style="width: 50px; height: 50px; background-color: #fff; border: 3px solid ${isPrimerNodo ? '#0ab39c' : (isUltimoNodo ? '#f06548' : '#299cdb')}; z-index: 2;">
                    <i class="ri-map-pin-2-fill fs-20 ${isPrimerNodo ? 'text-success' : (isUltimoNodo ? 'text-danger' : 'text-info')}"></i>
                </div>
                
                <div class="text-center mt-2 fw-bold fs-12 text-dark px-2 text-truncate" style="max-width: 140px;" title="${nodoAct.nombre}">
                    ${nodoAct.nombre || 'Nodo ' + ordAct}
                </div>

                <div class="mt-2 d-flex flex-column gap-1 align-items-center">
                    ${suben > 0 ? `<span class="badge bg-soft-success text-success fs-10 border border-success"><i class="ri-arrow-up-circle-fill me-1"></i> Sube +F${suben}</span>` : ''}
                    ${bajan > 0 ? `<span class="badge fs-10 border" style="background-color: rgba(253, 126, 20, 0.12); color: #e65100; border-color: #fd7e14 !important;"><i class="ri-arrow-down-circle-fill me-1" style="color: #fd7e14;"></i> Descarga -F${bajan}</span>` : ''}
                </div>
            </div>
        `;

        if (i < g_nodosEnvio.length - 1) {
            const nodoSig = g_nodosEnvio[i + 1];
            const kmEfectivoNum = esDirecto
                ? parseFloat(nodoSig.km_directo || nodoSig.km_tramo || 0)
                : parseFloat(nodoSig.km_tramo || 0);
            const kmTramo = parseFloat(nodoSig.km_tramo || 0).toLocaleString('en-US', { minimumFractionDigits: 1, maximumFractionDigits: 1 });
            const tipoServEnvioInf = (g_envioData && g_envioData.tipo_servicio ? String(g_envioData.tipo_servicio) : 'AUTOMATICO').toUpperCase();
            let servTramoInf = (nodoSig.tipo_servicio_detectado || '').toUpperCase();
            if (!['SLC', 'SLL', 'FORANEO'].includes(servTramoInf)) {
                if (tipoServEnvioInf === 'SLC' || tipoServEnvioInf === 'SLL' || tipoServEnvioInf === 'FORANEO') {
                    servTramoInf = tipoServEnvioInf;
                } else if (kmEfectivoNum > 0 && kmEfectivoNum <= 40) {
                    servTramoInf = 'SLC';
                } else if (kmEfectivoNum > 40 && kmEfectivoNum <= 80) {
                    servTramoInf = 'SLL';
                } else if (kmEfectivoNum > 80) {
                    servTramoInf = 'FORANEO';
                } else {
                    servTramoInf = '';
                }
            }
            const badgeServInfHtml = servTramoInf === 'SLC'
                ? '<span class="badge bg-soft-success text-success border border-success fs-10 mt-1">SLC</span>'
                : (servTramoInf === 'SLL'
                    ? '<span class="badge bg-soft-warning text-warning border border-warning fs-10 mt-1">SLL</span>'
                    : (servTramoInf === 'FORANEO'
                        ? '<span class="badge bg-soft-primary text-primary border border-primary fs-10 mt-1">FORÁNEO</span>'
                        : ''));
            
            html += `
            <div class="d-flex flex-column justify-content-center flex-grow-1 position-relative px-2" style="min-width: 140px; margin-top: -30px;">
                <div style="height: 3px; background-color: #e9ecef; width: 100%; position: absolute; top: 50px; left: 0; z-index: 1;"></div>
                
                <div class="text-center position-relative z-index-2 mt-4">
                    <span class="badge bg-light text-dark border border-secondary shadow-sm px-2 py-1 fs-11">
                        <i class="ri-route-line text-muted me-1"></i>${kmTramo} km
                    </span>
                    ${badgeServInfHtml ? `<div>${badgeServInfHtml}</div>` : ''}
                </div>
                <div class="text-center position-relative z-index-2 mt-2">
                    <span class="badge bg-soft-primary text-primary border border-primary px-2 py-1 fs-11 fw-bold shadow-sm">
                        <i class="ri-truck-fill me-1"></i>Factor A Bordo: F${factorAcumulado}
                    </span>
                </div>
            </div>
            `;
        }
    }

    html += `
            </div>
        </div>
        <div class="card-footer bg-light py-2">
            <small class="text-muted fs-11">
                <i class="ri-information-line me-1 text-info"></i>
                ${esDirecto
                    ? 'En <strong>Envío Directo con Escalas</strong>, cada destino se cotiza de forma independiente desde su punto de salida asignado, aplicando el factor correspondiente al volumen de unidades que comparten ese destino.'
                    : 'El factoraje volumétrico se ajusta progresivamente por tramo según las unidades a bordo. Unidades en el pool no asignadas no contabilizan factor.'}
            </small>
        </div>
    </div>`;

    container.innerHTML = html;
}


function regresarABorrador() {
    Swal.fire({
        title: '¿Regresar a Borrador?',
        text: 'El envío volverá a estado de Borrador para permitir su edición y recalcular costos. ¿Desea continuar?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, regresar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                title: 'Reabriendo...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            const idEnvio = document.getElementById('id_envio').value;
            let request = new XMLHttpRequest();
            let ajaxUrl = base_url + '/Lgs_envios/reabrir';
            let formData = new FormData();
            formData.append('id_envio', idEnvio);

            request.open("POST", ajaxUrl, true);
            request.send(formData);

            request.onreadystatechange = function () {
                if (request.readyState == 4) {
                    if (request.status == 200) {
                        try {
                            let objData = JSON.parse(request.responseText);
                            if (objData.status === 'success' || objData.status === true) {
                                Swal.fire("Éxito", objData.msg, "success").then(() => {
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire("Error", objData.msg || "Ocurrió un error.", "error");
                            }
                        } catch(e) {
                            Swal.fire("Error de Servidor", "La respuesta no es válida.", "error");
                            console.error("Respuesta no JSON:", request.responseText);
                        }
                    } else {
                        Swal.fire("Error", "Ocurrió un problema de red (Código: " + request.status + ").", "error");
                    }
                }
            }
        }
    });
}

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * Actualiza en tiempo real las 4 tarjetas KPI de Auditoría y el desglose de Costo Real por VIN
 */
function actualizarAuditoriaFinancieraUI(audit) {
    if (!audit || typeof audit !== 'object') return;
    const totales = audit.totales || {};

    const lblCostoBase = document.getElementById('lbl-audit-costo-base');
    const lblCargos = document.getElementById('lbl-audit-cargos');
    const lblGastosCount = document.getElementById('lbl-audit-gastos-count');
    const lblCostoReal = document.getElementById('lbl-audit-costo-real');
    const lblIncidenciasCount = document.getElementById('lbl-audit-incidencias-count');
    const lblVinsCount = document.getElementById('lbl-audit-vins-count');
    const tabVinsCount = document.getElementById('lbl-audit-tab-vins-count');
    const tabGastosCount = document.getElementById('lbl-audit-tab-gastos-count');
    const tabIncidenciasCount = document.getElementById('lbl-audit-tab-incidencias-count');
    const tbodyVins = document.getElementById('tbody-audit-vins');

    const fmtMoney = (val) => '$' + (parseFloat(val) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    if (lblCostoBase) lblCostoBase.innerText = fmtMoney(totales.costo_planeado);
    if (lblCargos) {
        const cargos = parseFloat(totales.total_cargos) || 0;
        lblCargos.innerText = (cargos > 0 ? '+$' : '$') + (cargos).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    if (lblGastosCount) lblGastosCount.innerText = `${parseInt(totales.gastos_count) || 0} gastos registrados`;
    if (lblCostoReal) lblCostoReal.innerText = fmtMoney(totales.costo_real);
    if (lblIncidenciasCount) lblIncidenciasCount.innerText = parseInt(totales.incidencias_count) || 0;
    if (lblVinsCount) lblVinsCount.innerText = `${parseInt(totales.vins_count) || 0} VINs en el envío`;

    const vins = audit.vins || [];
    const gastos = audit.gastos || [];
    const incidencias = audit.incidencias || [];

    if (tabVinsCount) tabVinsCount.innerText = vins.length;
    if (tabGastosCount) tabGastosCount.innerText = gastos.length;
    if (tabIncidenciasCount) tabIncidenciasCount.innerText = incidencias.length;

    if (tbodyVins) {
        if (vins.length === 0) {
            tbodyVins.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-3">No hay unidades cargadas en este envío.</td></tr>';
        } else {
            let html = '';
            vins.forEach((v, idx) => {
                const cPlaneado = parseFloat(v.costo_planeado_unidad) || 0;
                const cCargos = parseFloat(v.total_cargos_adicionales) || 0;
                const cDeduc = parseFloat(v.total_deducciones) || 0;
                const cReal = parseFloat(v.costo_real_unidad) || 0;
                const gActivos = parseInt(v.total_gastos_activos) || 0;

                const txtCargos = cCargos > 0 ? `+$${cCargos.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '$0.00';
                const txtDeduc = cDeduc > 0 ? `-$${cDeduc.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}` : '$0.00';
                const badgeGastos = gActivos > 0 ? `<span class="badge bg-soft-warning text-warning rounded-pill">${gActivos} gasto(s)</span>` : `<span class="text-muted fs-11">Sin extras</span>`;

                html += `
                    <tr>
                        <td>${idx + 1}</td>
                        <td>
                            <span class="badge bg-light text-dark font-monospace fs-12 px-2 py-1 border">${escapeHtml(v.vin || '')}</span>
                        </td>
                        <td class="text-end text-muted">${fmtMoney(cPlaneado)}</td>
                        <td class="text-end text-danger fw-medium">${txtCargos}</td>
                        <td class="text-end text-success fw-medium">${txtDeduc}</td>
                        <td class="text-end fw-bold text-success fs-14">${fmtMoney(cReal)}</td>
                        <td class="text-center">${badgeGastos}</td>
                    </tr>
                `;
            });
            tbodyVins.innerHTML = html;
        }
    }
}

function mostrarModalSolicitudExcepcionDetalle(idEnvio, folio, dataAuth, esDesdeEnvioOficial = false) {
    const observados = Array.isArray(dataAuth.vins_observados) ? dataAuth.vins_observados : [];
    const solPend    = dataAuth.solicitud_pendiente || null;
    const ultRech    = dataAuth.ultima_rechazada || null;

    const rowsHtml = observados.map(w => {
        const facBadge = parseInt(w.cumple_factura || 0, 10) === 1
            ? `<span class="badge bg-soft-success text-success">SÍ (${escapeHtml(w.factura)})</span>`
            : '<span class="badge bg-soft-danger text-danger">Sin Factura</span>';
        const pagBadge = parseInt(w.cumple_pago || 0, 10) === 1
            ? `<span class="badge bg-soft-success text-success">${escapeHtml(w.estatus_pago_texto)}</span>`
            : `<span class="badge bg-soft-warning text-dark">${escapeHtml(w.estatus_pago_texto || 'Pendiente')}</span>`;
        const autBadge = parseInt(w.cumple_autorizacion || 0, 10) === 1
            ? '<span class="badge bg-soft-success text-success">Autorizada</span>'
            : '<span class="badge bg-soft-danger text-danger">Sin Aut. Mov.</span>';
        return `<tr>
            <td class="text-start font-monospace fw-bold fs-11">${escapeHtml(w.vin)}<div class="text-muted fw-normal">${escapeHtml(w.modelo || '')}</div></td>
            <td>${facBadge}</td>
            <td>${pagBadge}</td>
            <td>${autBadge}</td>
        </tr>`;
    }).join('');

    let estadoPrevHtml = '';
    if (ultRech && !solPend) {
        estadoPrevHtml = `<div class="alert alert-soft-danger border border-danger-subtle text-start fs-12 py-2 px-3 mb-2">
            <strong><i class="ri-close-circle-line me-1"></i>Solicitud previa rechazada (${escapeHtml(ultRech.folio_solicitud || '')}):</strong>
            ${escapeHtml(ultRech.obs_aprobador || 'Sin comentarios')}
        </div>`;
    }

    if (solPend) {
        Swal.fire({
            title: 'Solicitud Pendiente de Aprobación',
            width: '720px',
            icon: 'info',
            html: `
                <div class="text-start fs-13 mb-2">
                    El registro <strong>${escapeHtml(folio || ('#' + idEnvio))}</strong> ${esDesdeEnvioOficial ? 'quedó guardado en borrador porque ' : ''}tiene unidades pendientes de Factura, Pago o Autorización de Movimiento y ya cuenta con la solicitud
                    <span class="badge bg-warning text-dark">${escapeHtml(solPend.folio_solicitud)}</span> pendiente de dictamen en <strong>Aprobaciones</strong>.
                </div>
                <div class="p-2 bg-light border rounded text-start fs-12 mb-3">
                    <strong>Justificación enviada:</strong> ${escapeHtml(solPend.motivo_solicitud || '')}
                </div>
                <div class="table-responsive border rounded" style="max-height:220px; overflow-y:auto;">
                    <table class="table table-sm align-middle mb-0 fs-11">
                        <thead class="table-light"><tr><th class="text-start">VIN / Modelo</th><th>Factura</th><th>Pago</th><th>Aut. Mov.</th></tr></thead>
                        <tbody>${rowsHtml}</tbody>
                    </table>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#C46623',
            cancelButtonColor: '#64748B',
            confirmButtonText: '<i class="ri-shield-check-line me-1"></i> Ir a Aprobaciones',
            cancelButtonText: 'Cerrar'
        }).then((r) => {
            if (r.isConfirmed) {
                window.location.href = base_url + '/Lgs_aprobaciones?tab=preenvios';
            }
        });
        return;
    }

    const introMsg = esDesdeEnvioOficial
        ? `El acomodo se guardó en borrador, pero no se puede finalizar <strong>${escapeHtml(folio)}</strong> porque las siguientes unidades aún no cumplen Factura, Pago/Crédito y Autorización de Movimiento:`
        : `No es posible pasar <strong>${escapeHtml(folio)}</strong> directamente a Envío Oficial (<code>EN-</code>) porque las siguientes unidades aún no cumplen Factura, Pago/Crédito y Autorización de Movimiento:`;

    Swal.fire({
        title: 'Validación de Factura / Pago / Autorización',
        width: '760px',
        icon: 'warning',
        html: `
            ${estadoPrevHtml}
            <p class="text-start fs-13 mb-2">${introMsg}</p>
            <div class="table-responsive border rounded mb-3" style="max-height:220px; overflow-y:auto;">
                <table class="table table-sm align-middle mb-0 fs-11">
                    <thead class="table-light"><tr><th class="text-start">VIN / Modelo</th><th>Factura</th><th>Pago</th><th>Aut. Mov.</th></tr></thead>
                    <tbody>${rowsHtml}</tbody>
                </table>
            </div>
            <div class="text-start">
                <label class="form-label fs-12 fw-bold text-dark mb-1">
                    <i class="ri-send-plane-line text-warning me-1"></i> Forzar bajo solicitud (Justificación para Aprobaciones) <span class="text-danger">*</span>
                </label>
                <textarea id="swal_motivo_excepcion_detalle" class="form-control fs-12" rows="3" placeholder="Ej. Cliente autorizado por Dirección Comercial; factura o pago en proceso..."></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonColor: '#d97706',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-shield-user-line me-1"></i> Solicitar Autorización en Aprobaciones',
        cancelButtonText: 'Ajustar Acomodo',
        preConfirm: () => {
            const motivo = (document.getElementById('swal_motivo_excepcion_detalle')?.value || '').trim();
            if (motivo.length < 5) {
                Swal.showValidationMessage('Ingrese el motivo o justificación (mínimo 5 caracteres).');
                return false;
            }
            return motivo;
        }
    }).then((c) => {
        if (!c.isConfirmed) return;
        Swal.fire({ title: 'Enviando Solicitud...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const fd = new FormData();
        fd.append('id_envio', idEnvio);
        fd.append('motivo_solicitud', c.value);
        fetch(base_url + '/Lgs_envios/solicitarAutorizacionConversion', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(resp => {
                if (!resp || (resp.status !== 'success' && resp.status !== true && resp.code !== 200)) {
                    throw new Error(resp?.message || 'No se pudo enviar la solicitud');
                }
                Swal.fire({
                    title: 'Solicitud Enviada a Aprobaciones',
                    text: resp.message || 'Un aprobador revisará la solicitud en el Panel de Aprobaciones.',
                    icon: 'success',
                    showCancelButton: true,
                    confirmButtonColor: '#C46623',
                    cancelButtonColor: '#64748B',
                    confirmButtonText: '<i class="ri-shield-check-line me-1"></i> Ir a Aprobaciones',
                    cancelButtonText: 'Seguir aquí'
                }).then((go) => {
                    if (go.isConfirmed) {
                        window.location.href = base_url + '/Lgs_aprobaciones?tab=preenvios';
                    } else {
                        cargarDatosDetalle();
                    }
                });
            })
            .catch(err => Swal.fire('Error', err.message || 'Error de comunicación', 'error'));
    });
}

function convertirPreEnvioAEnvioDesdeDetalle(idEnvio) {
    const ejecutar = async () => {
        try {
            Swal.fire({ title: 'Validando Pre-envío...', text: 'Verificando acomodo, factura, cobranza y autorización...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            // Sincronizar acomodo actual si la pantalla no es solo lectura
            if (typeof ENVIO_READONLY === 'undefined' || !ENVIO_READONLY) {
                const asignaciones = recolectarAsignacionesAcomodo();
                if (asignaciones.length > 0) {
                    const syncRes = await fetch(base_url + '/Lgs_envios/storeAcomodo', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id_envio: idEnvio, asignaciones: asignaciones, finalizar: false })
                    }).then(r => r.json());
                    if (!syncRes || (syncRes.status !== 'success' && syncRes.code !== 200)) {
                        throw new Error(syncRes?.message || 'No se pudo guardar el acomodo previo a la validación.');
                    }
                }
            }

            const fd = new FormData();
            fd.append('id_envio', idEnvio);
            const obj = await fetch(base_url + '/Lgs_envios/convertirAEnvioOficial', { method: 'POST', body: fd }).then(r => r.json());

            if (!obj || (obj.status !== 'success' && obj.status !== true && obj.code !== 200)) {
                throw new Error(obj?.message || 'No se pudo convertir el pre-envío');
            }
            if (obj.data && obj.data.requires_authorization) {
                const folioActual = (g_envioData && g_envioData.folio) ? g_envioData.folio : ('PE-' + idEnvio);
                mostrarModalSolicitudExcepcionDetalle(idEnvio, folioActual, obj.data, false);
                return;
            }

            Swal.fire({
                title: '¡Convertido a Envío Oficial!',
                text: obj.message || 'Ahora cuenta con folio oficial EN- y está listo para Planeaciones.',
                icon: 'success',
                showCancelButton: true,
                confirmButtonColor: '#C46623',
                cancelButtonColor: '#0284C7',
                confirmButtonText: '<i class="ri-truck-line me-1"></i> Ir a Bandeja de Envíos',
                cancelButtonText: '<i class="ri-file-list-3-line me-1"></i> Ir a Planeaciones'
            }).then((go) => {
                if (go.isConfirmed) {
                    window.location.href = base_url + '/Lgs_envios';
                } else if (go.dismiss === Swal.DismissReason.cancel) {
                    window.location.href = base_url + '/Lgs_planeaciones';
                } else {
                    window.location.reload();
                }
            });
        } catch (err) {
            Swal.fire('Validación', err.message || 'Error de comunicación', 'warning');
        }
    };

    Swal.fire({
        title: '¿Pasar Pre-envío a Envío Oficial (EN-)?',
        text: 'Se validará ruta, acomodo, Factura, Pago y Autorización de Movimiento. Si cumple o ya fue autorizado en Aprobaciones, pasará al módulo de Envíos.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d',
        confirmButtonText: '<i class="ri-exchange-line me-1"></i> Validar y Pasar a Envío',
        cancelButtonText: 'Cancelar'
    }).then((res) => {
        if (res.isConfirmed) ejecutar();
    });
}
