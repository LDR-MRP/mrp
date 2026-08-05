<?php headerAdmin($data); ?>

<!-- Cargar Leaflet CSS para Mapas -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-panelrutas">
                <!-- BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Monitoreo GPS</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ENCABEZADO -->
                <div class="row mb-4">
                    <div class="col-12 col-md-8">
                        <h4 class="mb-1 text-primary fw-bold">
                            <i class="ri-map-pin-user-line me-2"></i> Monitoreo Geográfico y Tracking GPS
                        </h4>
                        <p class="text-muted fs-14 mb-0">Ubicación en tiempo real de unidades y madrinas activas en tránsito.</p>
                    </div>
                    <div class="col-12 col-md-4 text-md-end mt-3 mt-md-0">
                        <button type="button" class="btn btn-outline-primary rounded-pill px-4 shadow-sm" onclick="cargarRutasMapa();">
                            <i class="ri-refresh-line me-1"></i> Actualizar GPS
                        </button>
                    </div>
                </div>

                <div class="row">
                    <!-- LISTA DE RUTAS ACTIVAS (Izquierda) -->
                    <div class="col-lg-4">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-header bg-light border-bottom-0 pt-3 pb-2">
                                <h6 class="card-title mb-0 fw-bold text-secondary"><i class="ri-truck-line me-1"></i> Rutas en Tránsito</h6>
                            </div>
                            <div class="card-body p-2" style="max-height: 600px; overflow-y: auto;">
                                <div class="list-group list-group-flush" id="listaRutasActivas">
                                    <!-- Carga por JS -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- MAPA INTERACTIVO (Derecha) -->
                    <div class="col-lg-8">
                        <div class="card shadow-sm border-0 rounded-3 h-100">
                            <div class="card-body p-0 rounded-3 overflow-hidden">
                                <div id="mapaGPS" style="height: 600px; width: 100%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

            </section>
        </div>
    </div>
</div>

<!-- Cargar Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>

<?php footerAdmin($data); ?>
