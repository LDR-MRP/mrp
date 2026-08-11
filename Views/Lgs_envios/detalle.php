<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- HEADER -->
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/Lgs_envios">Envíos</a></li>
                                <li class="breadcrumb-item active text-primary">Acomodo de VINs</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TÍTULO -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <h4 class="mb-1 text-primary fw-bold">
                        <i class="ri-drag-move-2-line me-2"></i> Asignación y Acomodo (Envío #<?= $data['id_envio'] ?>)
                    </h4>
                    <p class="text-muted fs-14 mb-0">Arrastre los VINs desde el pool disponible hacia la Madrina/Chofer y ordénelos según su destino de entrega.</p>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="guardarAcomodo();">
                        <i class="ri-save-3-line me-1"></i> Guardar Distribución
                    </button>
                </div>
            </div>

            <!-- LEYENDA INFORMATIVA ORDEN DE CARGA -->
            <div class="alert alert-info border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
                <i class="ri-information-fill fs-20 me-3 text-info"></i>
                <div class="fs-13">
                    <strong class="text-dark">Secuencia de Carga y Descarga:</strong>
                    La unidad en la <strong>Posición #1</strong> es la <span class="badge bg-success px-2 py-1">1º en Cargar</span> (primera en subir al vehículo). Cada tarjeta muestra su <strong>Modelo</strong>, <strong>Origen ➔ Destino</strong> y su número de secuencia de carga.
                </div>
            </div>

            <input type="hidden" id="id_envio" value="<?= $data['id_envio'] ?>">

            <div class="row">
                <!-- POOL DE VINS DISPONIBLES (Izquierda) -->
                <div class="col-lg-4">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header bg-light border-bottom-0 pt-3 pb-2">
                            <h6 class="card-title mb-0 fw-bold text-secondary"><i class="ri-car-line me-1"></i> VINs Disponibles</h6>
                            <small class="text-muted">Unidades listas en el origen del envío</small>
                        </div>
                        <div class="card-body bg-light" style="min-height: 500px;">
                            <!-- Lista Sortable -->
                            <ul id="vins-disponibles" class="list-group list-group-flush sortable-list rounded" style="min-height: 400px; border: 2px dashed #ccc;">
                                <!-- Se llena dinámicamente desde JS -->
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- CAMIONES / MADRINAS (Derecha) -->
                <div class="col-lg-8">
                    <div class="card shadow-sm border-0 rounded-3 h-100">
                        <div class="card-header border-bottom-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="card-title mb-0 fw-bold text-secondary"><i class="ri-truck-line me-1"></i> Asignación a Vehículos</h6>
                                <small class="text-muted">Arrastre aquí para cargar (El de arriba se baja al último)</small>
                            </div>
                            <!-- Botón para agregar más madrinas/choferes a este envío -->
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3" id="btn-agregar-vehiculo" onclick="agregarVehiculo();">
                                <i class="ri-add-line me-1"></i> Agregar Vehículo
                            </button>
                        </div>
                        <div class="card-body" id="contenedor-vehiculos">
                            <!-- Se inyecta dinámicamente las madrinas/choferes asignados -->
                            <div class="text-center text-muted py-5" id="empty-vehiculos-msg">
                                <i class="ri-truck-line fs-1 display-4 text-muted opacity-50"></i>
                                <p class="mt-2 mb-0">No se han asignado vehículos a este envío.</p>
                                <small class="text-muted">Haga clic en <strong>"Agregar Vehículo / Madrina"</strong> para seleccionar del catálogo del trasladista.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL AGREGAR VEHÍCULO / MADRINA DEL PROVEEDOR -->
<div class="modal fade" id="modalAgregarVehiculo" tabindex="-1" aria-labelledby="modalVehiculoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light border-bottom-0">
                <h5 class="modal-title fw-bold text-primary" id="modalVehiculoLabel">
                    <i class="ri-truck-line me-2"></i> Seleccionar Vehículo / Conductor del Trasladista
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <i class="ri-information-line me-1 fs-15 align-middle"></i> 
                    Empresa Trasladista: <strong id="lbl-trasladista-nombre">Cargando...</strong>
                </div>

                <ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist" id="modal-vehiculo-nav-tabs">
                    <li class="nav-item" id="nav-tab-madrinas">
                        <a class="nav-link active" id="link-tab-madrinas" data-bs-toggle="tab" href="#tab-madrinas" role="tab">
                            <i class="ri-truck-line me-1"></i> Madrinas del Catálogo
                        </a>
                    </li>
                    <li class="nav-item" id="nav-tab-choferes">
                        <a class="nav-link" id="link-tab-choferes" data-bs-toggle="tab" href="#tab-choferes" role="tab">
                            <i class="ri-steering-2-line me-1"></i> Choferes (Rodando)
                        </a>
                    </li>
                </ul>

                <div class="tab-content text-muted">
                    <!-- Pestaña Madrinas -->
                    <div class="tab-pane active" id="tab-madrinas" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tblModalMadrinas">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Económico</th>
                                        <th>Placas</th>
                                        <th>Capacidad</th>
                                        <th>Chofer Asignado</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalMadrinas"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pestaña Choferes -->
                    <div class="tab-pane" id="tab-choferes" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="tblModalChoferes">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Nombre del Conductor</th>
                                        <th>N° Licencia</th>
                                        <th>Tipo Licencia</th>
                                        <th class="text-end">Acción</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModalChoferes"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .cursor-move { cursor: grab; }
    .cursor-move:active { cursor: grabbing; }
    .sortable-list { background-color: #f8f9fa; padding: 10px; }
    .sortable-ghost { opacity: 0.4; background-color: #e9ecef; }
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<?php footerAdmin($data); ?>
