let map;
let markersGroup;
let polylineGroup;

document.addEventListener('DOMContentLoaded', function () {
    initMap();
    cargarRutasMapa();
});

function initMap() {
    // Coordenadas por defecto (Centro de México)
    map = L.map('mapaGPS').setView([20.659698, -100.389888], 6);

    // Servidor de Tiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    markersGroup = L.layerGroup().addTo(map);
    polylineGroup = L.layerGroup().addTo(map);
}

function cargarRutasMapa() {
    markersGroup.clearLayers();
    polylineGroup.clearLayers();

    document.getElementById('listaRutasActivas').innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary spinner-border-sm" role="status"></div> Cargando monitoreo GPS...</div>';

    let request = (window.XMLHttpRequest) ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    let ajaxUrl = base_url + '/Lgs_panelrutas/getRutasMapa';

    request.open("GET", ajaxUrl, true);
    request.send();
    request.onreadystatechange = function () {
        if (request.readyState == 4 && request.status == 200) {
            let objData = JSON.parse(request.responseText);
            let htmlLista = '';

            if (objData.status && objData.data.length > 0) {
                let bounds = [];

                objData.data.forEach(ruta => {
                    let gpsActual = ruta.gps_actual;
                    let lat = gpsActual.lat;
                    let lng = gpsActual.lng;

                    bounds.push([lat, lng]);

                    // Marcador en Mapa
                    let marker = L.marker([lat, lng])
                        .bindPopup(`
                            <div class="text-center p-1">
                                <h6 class="fw-bold text-primary mb-1">${ruta.folio}</h6>
                                <p class="mb-1 text-dark fs-12"><strong>Trasladista:</strong> ${ruta.trasladista}</p>
                                <p class="mb-1 text-muted fs-12"><strong>VINs:</strong> ${ruta.total_vins} unidades</p>
                                <span class="badge bg-primary">En Tránsito</span>
                            </div>
                        `);
                    
                    markersGroup.addLayer(marker);

                    // Card en lista lateral
                    htmlLista += `
                        <button class="list-group-item list-group-item-action p-3 mb-2 rounded border shadow-sm" onclick="centrarEnRuta(${lat}, ${lng}, '${ruta.folio}')">
                            <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                <h6 class="mb-0 fw-bold text-primary">${ruta.folio}</h6>
                                <span class="badge bg-success-subtle text-success border border-success">GPS Activo</span>
                            </div>
                            <p class="mb-1 text-dark fs-13"><i class="ri-truck-line me-1"></i> ${ruta.trasladista}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><i class="ri-map-pin-line me-1"></i> Origen: ${ruta.origen_nombre}</small>
                                <small class="fw-bold text-secondary">${ruta.total_vins} VINs</small>
                            </div>
                        </button>
                    `;
                });

                if (bounds.length > 0) {
                    map.fitBounds(bounds, { padding: [50, 50] });
                }

            } else {
                htmlLista = '<div class="text-center text-muted py-5"><i class="ri-map-pin-time-line fs-24"></i><br>No hay unidades activas en tránsito actualmente.</div>';
            }

            document.getElementById('listaRutasActivas').innerHTML = htmlLista;
        }
    }
}

function centrarEnRuta(lat, lng, folio) {
    map.setView([lat, lng], 12);
}
