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
                                <!-- Ejemplo de ítem estático -->
                                <li class="list-group-item cursor-move shadow-sm mb-2 rounded border-start border-3 border-primary" data-id-unidad="1">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-draggable fs-18 text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">VIN: <span class="text-primary">3VW1234567890</span></h6>
                                            <p class="text-muted mb-0 fs-12">Destino: Cliente Final A</p>
                                        </div>
                                    </div>
                                </li>
                                <li class="list-group-item cursor-move shadow-sm mb-2 rounded border-start border-3 border-primary" data-id-unidad="2">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="ri-draggable fs-18 text-muted"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0">VIN: <span class="text-primary">3VW0987654321</span></h6>
                                            <p class="text-muted mb-0 fs-12">Destino: Carrocero B</p>
                                        </div>
                                    </div>
                                </li>
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
                            <!-- Botón para agregar más madrinas a este envío si es necesario -->
                            <button class="btn btn-sm btn-outline-secondary rounded-pill" onclick="agregarVehiculo();">
                                <i class="ri-add-line"></i> Agregar Madrina
                            </button>
                        </div>
                        <div class="card-body" id="contenedor-vehiculos">
                            
                            <!-- EJEMPLO DE MADRINA 1 -->
                            <div class="border rounded p-3 mb-4 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold text-dark mb-0">Madrina 1 (Tracto: T-01 / Nodriza: N-05)</h6>
                                    <span class="badge bg-primary rounded-pill">0/9 VINs</span>
                                </div>
                                <ul class="list-group sortable-list vehiculo-list" data-id-madrina="1" style="min-height: 100px; border: 2px dashed #999;">
                                    <!-- Aquí caen los VINs -->
                                </ul>
                            </div>

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

<!-- Suponiendo que el proyecto usa SortableJS, lo cargamos si no está global -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<?php footerAdmin($data); ?>
