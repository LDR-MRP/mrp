let googleMap;
let trafficLayer;
let activeMarkers = [];
let infoWindow;
let isTrafficVisible = false;
let todasLasRutas = [];

// Inicialización de Google Maps
function initGoogleMap() {
    const defaultCenter = { lat: 20.528400, lng: -103.264100 }; // Planta LDR Solutions El Salto / Jalisco

    const mapOptions = {
        zoom: 11,
        center: defaultCenter,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        mapTypeControl: true,
        mapTypeControlOptions: {
            style: google.maps.MapTypeControlStyle.HORIZONTAL_BAR,
            position: google.maps.ControlPosition.TOP_RIGHT
        },
        streetViewControl: true,
        fullscreenControl: true,
        zoomControl: true,
        styles: [
            {
                "featureType": "poi.business",
                "stylers": [{ "visibility": "off" }]
            },
            {
                "featureType": "transit",
                "elementType": "labels.icon",
                "stylers": [{ "visibility": "off" }]
            }
        ]
    };

    googleMap = new google.maps.Map(document.getElementById('mapaGPS'), mapOptions);
    trafficLayer = new google.maps.TrafficLayer();
    infoWindow = new google.maps.InfoWindow();

    // Cargar rutas
    cargarRutasMapa();
}

// Fallback por si la API se carga antes o después
document.addEventListener('DOMContentLoaded', function () {
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined' && !googleMap) {
        initGoogleMap();
    }
});

function toggleGoogleTraffic() {
    if (!googleMap || !trafficLayer) return;
    const btn = document.getElementById('btn-toggle-traffic');
    if (!isTrafficVisible) {
        trafficLayer.setMap(googleMap);
        isTrafficVisible = true;
        if (btn) {
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline-secondary');
        }
    } else {
        trafficLayer.setMap(null);
        isTrafficVisible = false;
        if (btn) {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-secondary');
        }
    }
}

function cargarRutasMapa() {
    const listaEl = document.getElementById('listaRutasActivas');
    if (listaEl) {
        listaEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary spinner-border-sm" role="status"></div><p class="mt-2 text-muted fs-12">Conectando con señal GPS en tiempo real...</p></div>';
    }

    fetch(base_url + '/Lgs_panelrutas/getRutasMapa')
        .then(response => response.json())
        .then(res => {
            todasLasRutas = (res.status === 'success' || res.status === true) ? (res.data || []) : [];

            // Actualizar KPIs superiores
            let totalMadrinas = 0;
            let totalRodando = 0;
            todasLasRutas.forEach(r => {
                let tipo = (r.tipo_traslado || '').toLowerCase();
                if (tipo.includes('madrina')) totalMadrinas++;
                else totalRodando++;
            });

            if (document.getElementById('kpi-rutas-activas-gps')) document.getElementById('kpi-rutas-activas-gps').innerText = todasLasRutas.length;
            if (document.getElementById('kpi-madrinas-gps')) document.getElementById('kpi-madrinas-gps').innerText = totalMadrinas;
            if (document.getElementById('kpi-rodando-gps')) document.getElementById('kpi-rodando-gps').innerText = totalRodando;
            if (document.getElementById('badge-total-rutas')) document.getElementById('badge-total-rutas').innerText = `${todasLasRutas.length} en ruta`;

            // Renderizar mapa y lista
            renderizarMarcadoresYLista(todasLasRutas);
        })
        .catch(err => {
            console.error(err);
            if (listaEl) listaEl.innerHTML = '<div class="alert alert-danger text-center m-2 py-3">Error al conectar con el servidor GPS.</div>';
        });
}

function renderizarMarcadoresYLista(rutas) {
    // Limpiar marcadores anteriores
    activeMarkers.forEach(m => m.marker.setMap(null));
    activeMarkers = [];

    const listaEl = document.getElementById('listaRutasActivas');
    if (!listaEl) return;

    if (rutas.length === 0) {
        listaEl.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="ri-search-eye-line fs-32 d-block mb-2 text-muted opacity-75"></i>
                <h6 class="fw-semibold text-secondary">No se encontraron unidades</h6>
                <small class="text-muted">Intente con otro término de búsqueda o verifique la mesa de despacho.</small>
            </div>
        `;
        return;
    }

    let htmlLista = '';
    const bounds = googleMap ? new google.maps.LatLngBounds() : null;

    rutas.forEach((ruta, index) => {
        let gps = ruta.gps_actual || {};
        let lat = parseFloat(gps.lat) || 20.528400;
        let lng = parseFloat(gps.lng) || -103.264100;
        let position = { lat: lat, lng: lng };

        if (bounds) bounds.extend(position);

        // Icono de Camión SVG estilizado
        const truckSvg = encodeURIComponent(`
            <svg xmlns="http://www.w3.org/2000/svg" width="44" height="44" viewBox="0 0 44 44">
                <circle cx="22" cy="22" r="20" fill="#C46623" stroke="#FFFFFF" stroke-width="3" filter="drop-shadow(0px 3px 6px rgba(0,0,0,0.3))"/>
                <path d="M14 16h10v10h-10zm11 3h4l3 3v4h-7zm-9 9a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zm12 0a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5z" fill="#FFFFFF"/>
            </svg>
        `);

        if (googleMap) {
            const marker = new google.maps.Marker({
                position: position,
                map: googleMap,
                title: `${ruta.folio} - ${ruta.trasladista || 'En Tránsito'}`,
                icon: {
                    url: 'data:image/svg+xml;charset=UTF-8,' + truckSvg,
                    scaledSize: new google.maps.Size(44, 44),
                    anchor: new google.maps.Point(22, 22)
                },
                animation: google.maps.Animation.DROP
            });

            // Contenido del InfoWindow
            const infoContent = `
                <div style="min-width: 250px; font-family: system-ui, sans-serif; padding: 4px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #E2E8F0; padding-bottom: 6px; margin-bottom: 8px;">
                        <strong style="color: #C46623; font-size: 15px;">${ruta.folio}</strong>
                        <span style="background: #EBF7EE; color: #16A34A; padding: 2px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">● GPS En Línea</span>
                    </div>
                    <div style="font-size: 12px; color: #334155; line-height: 1.5;">
                        ${ruta.planeacion_folio ? `<div style="margin-bottom: 4px;"><strong>Planeación:</strong> <span style="background: #F1F5F9; padding: 2px 6px; border-radius: 4px; font-weight: bold;">${ruta.planeacion_folio}</span></div>` : ''}
                        <div style="margin-bottom: 4px;"><strong>Trasladista:</strong> ${ruta.trasladista || 'N/A'}</div>
                        <div style="margin-bottom: 4px;"><strong>Modalidad:</strong> ${ruta.tipo_traslado || 'Madrina'}</div>
                        <div style="margin-bottom: 4px;"><strong>Unidades a bordo:</strong> <span style="background: #EEF2FF; color: #4F46E5; padding: 1px 6px; border-radius: 6px; font-weight: 600;">${ruta.total_vins} VINs</span></div>
                        <div style="margin-bottom: 4px;"><strong>Velocidad actual:</strong> <strong style="color: #0F172A;">${gps.velocidad || '70 km/h'}</strong></div>
                        <div style="margin-bottom: 4px;"><strong>Ubicación:</strong> ${gps.ubicacion_nombre || 'Jalisco'}</div>
                        <div style="margin-top: 6px; padding-top: 4px; border-top: 1px dashed #CBD5E1; color: #64748B; font-size: 11px; text-align: center;">
                            Último reporte: ${gps.ultima_actualizacion || ''}
                        </div>
                    </div>
                </div>
            `;

            marker.addListener('click', () => {
                infoWindow.setContent(infoContent);
                infoWindow.open(googleMap, marker);
            });

            activeMarkers.push({
                id_envio: ruta.id_envio,
                folio: ruta.folio,
                marker: marker,
                position: position,
                infoContent: infoContent
            });
        }

        // Generar lista de paradas / destinos
        const paradas = ruta.paradas || [];
        let paradasHtml = '';
        if (paradas.length > 0) {
            paradasHtml = paradas.map((p, pIdx) => `
                <div class="d-flex align-items-start gap-2 mb-2">
                    <span class="badge bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; font-size: 10px;">${p.orden || (pIdx + 1)}</span>
                    <div class="flex-grow-1">
                        <div class="fw-semibold text-dark fs-12">${p.destino_nombre || 'Destino intermedio'}</div>
                        ${p.observaciones ? `<small class="text-muted fs-11">${p.observaciones}</small>` : ''}
                    </div>
                </div>
            `).join('');
        } else {
            paradasHtml = '<div class="text-muted fs-11 fst-italic">Sin paradas intermedias desglosadas.</div>';
        }

        // Generar lista de VINs a bordo
        const vins = ruta.vins || [];
        let vinsHtml = '';
        if (vins.length > 0) {
            vinsHtml = vins.map((v, vIdx) => {
                const isEntregado = (v.estado_unidad_fisico === 'ENTREGADO' || (v.fecha_entrega_real && v.fecha_entrega_real !== null));
                const badgeEstado = isEntregado 
                    ? `<span class="badge bg-success-subtle text-success border border-success fs-10 px-2 py-1"><i class="ri-checkbox-circle-fill me-1"></i>Entregado</span>`
                    : `<span class="badge bg-primary-subtle text-primary border border-primary fs-10 px-2 py-1"><i class="ri-truck-line me-1"></i>En Madrina</span>`;

                return `
                <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-light">
                    <div class="d-flex align-items-start gap-2">
                        <span class="badge bg-light text-secondary border rounded-circle d-flex align-items-center justify-content-center mt-1" style="width: 20px; height: 20px; font-size: 10px;" title="Secuencia de desembarque #${vIdx + 1}">
                            ${vIdx + 1}
                        </span>
                        <div>
                            <div>
                                <span class="font-monospace fw-bold fs-12 text-primary">${v.vin}</span>
                                ${v.modelo ? `<small class="text-muted ms-1 fs-11">(${v.modelo})</small>` : ''}
                            </div>
                            <div class="fs-11 text-secondary mt-1">
                                <i class="ri-map-pin-line text-danger me-1"></i>${v.destino_nombre || 'Destino pendiente'}
                            </div>
                        </div>
                    </div>
                    <div class="text-end ps-2 flex-shrink-0">
                        ${badgeEstado}
                    </div>
                </div>
            `;
            }).join('');
        } else {
            vinsHtml = '<div class="text-muted fs-11 fst-italic">No hay VINs registrados.</div>';
        }

        const collapseId = `collapseEnvio_${ruta.id_envio}`;

        // Tarjeta Desplegable en lista lateral
        htmlLista += `
            <div class="card border shadow-sm mb-3 rounded-3 overflow-hidden card-envio-tracking bg-white" id="card_envio_${ruta.id_envio}">
                <!-- Encabezado de la Tarjeta -->
                <div class="card-header bg-light border-0 p-3" role="button" onclick="centrarEnRutaGoogle(${lat}, ${lng}, '${ruta.folio}')">
                    <div class="d-flex justify-content-between align-items-start mb-1">
                        <div>
                            <span class="fs-15 fw-bold text-primary">${ruta.folio}</span>
                            ${ruta.planeacion_folio ? `<span class="badge bg-soft-secondary text-secondary ms-1 fs-11">Plan: ${ruta.planeacion_folio}</span>` : ''}
                        </div>
                        <span class="badge bg-success-subtle text-success border border-success fs-11 px-2 py-1"><i class="ri-radio-button-line me-1"></i> GPS Activo</span>
                    </div>
                    
                    <p class="mb-1 text-dark fs-13 fw-semibold"><i class="ri-truck-line me-1 text-muted"></i> ${ruta.trasladista || 'Transportista Asignado'}</p>
                    
                    <div class="d-flex justify-content-between align-items-center fs-12 text-muted mb-2">
                        <span><i class="ri-map-pin-line me-1 text-danger"></i> ${gps.ubicacion_nombre || 'Jalisco'}</span>
                        <span class="badge bg-primary text-white fw-bold">${ruta.total_vins} VINs</span>
                    </div>

                    <div class="d-flex justify-content-between align-items-center fs-11 text-secondary pt-1 border-top">
                        <span><i class="ri-dashboard-3-line me-1 text-primary"></i> ${gps.velocidad || '70 km/h'} (${gps.rumbo || 'En ruta'})</span>
                        <button class="btn btn-sm btn-link p-0 text-decoration-none fw-bold fs-12 text-primary" type="button" data-bs-toggle="collapse" data-bs-target="#${collapseId}" aria-expanded="false" onclick="event.stopPropagation();" title="Ver VINs y Paradas">
                            <i class="ri-menu-unfold-line me-1"></i> Ver Detalle <i class="ri-arrow-down-s-line"></i>
                        </button>
                    </div>
                </div>

                <!-- Contenido Desplegable (Accordion) con VINs y Paradas -->
                <div class="collapse border-top" id="${collapseId}">
                    <div class="card-body p-3 bg-white">
                        <!-- Pestañas o Secciones del Desglose -->
                        <ul class="nav nav-pills nav-justified mb-2 bg-light p-1 rounded-pill" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-1 fs-12 fw-semibold rounded-pill" data-bs-toggle="pill" href="#tab_paradas_${ruta.id_envio}">
                                    <i class="ri-map-pin-2-line me-1"></i> Paradas (${paradas.length})
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 fs-12 fw-semibold rounded-pill" data-bs-toggle="pill" href="#tab_vins_${ruta.id_envio}">
                                    <i class="ri-car-line me-1"></i> VINs (${vins.length})
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content pt-2">
                            <!-- Tab 1: Paradas -->
                            <div class="tab-pane fade show active" id="tab_paradas_${ruta.id_envio}">
                                <div class="p-2 border rounded-3 bg-light bg-opacity-50 mb-2">
                                    <small class="text-uppercase fw-bold text-muted fs-10 d-block mb-2 letter-spacing-1">Itinerario de Ruta</small>
                                    ${paradasHtml}
                                </div>
                            </div>

                            <!-- Tab 2: VINs -->
                            <div class="tab-pane fade" id="tab_vins_${ruta.id_envio}">
                                <div class="p-2 border rounded-3 bg-light bg-opacity-50 mb-2" style="max-height: 180px; overflow-y: auto;">
                                    <small class="text-uppercase fw-bold text-muted fs-10 d-block mb-2 letter-spacing-1">Unidades a Bordo</small>
                                    ${vinsHtml}
                                </div>
                            </div>
                        </div>

                        <!-- Botón de Acción en Mapa -->
                        <div class="d-flex gap-2 mt-2 pt-2 border-top">
                            <button class="btn btn-sm btn-soft-primary w-100 rounded-pill py-1 fs-12 fw-semibold" onclick="centrarEnRutaGoogle(${lat}, ${lng}, '${ruta.folio}')">
                                <i class="ri-focus-3-line me-1"></i> Enfocar en Google Maps
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });

    listaEl.innerHTML = htmlLista;

    if (googleMap && bounds && rutas.length > 0) {
        if (rutas.length === 1) {
            googleMap.setCenter({ lat: parseFloat(rutas[0].gps_actual.lat), lng: parseFloat(rutas[0].gps_actual.lng) });
            googleMap.setZoom(12);
        } else {
            googleMap.fitBounds(bounds);
        }
    }
}

// Filtro en Vivo por Envío, Planeación o VIN
function filtrarListaRutas() {
    const input = document.getElementById('inputBuscarRuta');
    if (!input) return;
    const term = input.value.trim().toLowerCase();

    if (!term) {
        renderizarMarcadoresYLista(todasLasRutas);
        return;
    }

    const filtradas = todasLasRutas.filter(ruta => {
        // Buscar por Folio de Envío
        if (ruta.folio && ruta.folio.toLowerCase().includes(term)) return true;
        // Buscar por Planeación
        if (ruta.planeacion_folio && ruta.planeacion_folio.toLowerCase().includes(term)) return true;
        if (ruta.planeacion_desc && ruta.planeacion_desc.toLowerCase().includes(term)) return true;
        // Buscar por Transportista
        if (ruta.trasladista && ruta.trasladista.toLowerCase().includes(term)) return true;
        // Buscar por Origen
        if (ruta.origen_nombre && ruta.origen_nombre.toLowerCase().includes(term)) return true;

        // Buscar por VINs contenidos
        const matchVin = (ruta.vins || []).some(v => {
            const isEntregado = (v.estado_unidad_fisico === 'ENTREGADO' || (v.fecha_entrega_real && v.fecha_entrega_real !== null));
            const estadoTexto = isEntregado ? 'entregado' : 'en madrina';
            return (v.vin && v.vin.toLowerCase().includes(term)) ||
                   (v.modelo && v.modelo.toLowerCase().includes(term)) ||
                   (v.destino_nombre && v.destino_nombre.toLowerCase().includes(term)) ||
                   estadoTexto.includes(term);
        });
        if (matchVin) return true;

        // Buscar por Paradas
        const matchParada = (ruta.paradas || []).some(p => {
            return (p.destino_nombre && p.destino_nombre.toLowerCase().includes(term)) ||
                   (p.observaciones && p.observaciones.toLowerCase().includes(term));
        });
        if (matchParada) return true;

        return false;
    });

    renderizarMarcadoresYLista(filtradas);

    // Si solo hay un resultado, abrir su detalle automáticamente
    if (filtradas.length === 1) {
        const collapseEl = document.getElementById(`collapseEnvio_${filtradas[0].id_envio}`);
        if (collapseEl && !collapseEl.classList.contains('show')) {
            const bsCollapse = new bootstrap.Collapse(collapseEl, { toggle: true });
        }
    }
}

function centrarEnRutaGoogle(lat, lng, folio) {
    if (!googleMap) return;
    const targetPos = { lat: lat, lng: lng };
    googleMap.panTo(targetPos);
    googleMap.setZoom(14);

    const found = activeMarkers.find(m => m.folio === folio);
    if (found && infoWindow) {
        infoWindow.setContent(found.infoContent);
        infoWindow.open(googleMap, found.marker);
    }
}
