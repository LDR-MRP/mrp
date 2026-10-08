<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- ── SECCIÓN 1: BREADCRUMBS Y CABECERA ────────────────────── -->
            <div class="row align-items-center mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                <li class="breadcrumb-item active text-primary">Incidencias Operativas</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 2: HEADER PRINCIPAL Y ACCIÓN ─────────────────── -->
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-lg" style="background-color: #d9534f !important;">
                                <i class="ri-alert-line"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Incidencias en Ruta</h3>
                            <p class="text-muted mb-0 fs-14">
                                Registro operativo de eventualidades, desvíos, daños y reclamos en envíos (Aprobados, En Tránsito y Entregados).
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-3 mt-md-0 gap-2">
                    <a href="<?= base_url(); ?>/Lgs_gastosadicionales" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">
                        <i class="ri-money-dollar-circle-line me-1"></i> Ir a Gastos Adicionales
                    </a>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" onclick="openModalIncidencia();">
                        <i class="ri-add-line align-middle fs-16 me-1"></i> Nueva Incidencia
                    </button>
                </div>
            </div>

            <!-- ── SECCIÓN 3: KPIS OPERATIVOS ────────────────────────────── -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Incidencias Abiertas</p>
                                    <h4 class="fs-22 fw-bold text-body mb-0"><span id="kpiAbiertas">0</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="ri-time-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Investigación</p>
                                    <h4 class="fs-22 fw-bold text-body mb-0"><span id="kpiInvestigacion">0</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                        <i class="ri-search-eye-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Con Gastos Ligados</p>
                                    <h4 class="fs-22 fw-bold text-body mb-0"><span id="kpiConGastos">0</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-money-dollar-box-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Reclamos Post-Entrega</p>
                                    <h4 class="fs-22 fw-bold text-body mb-0"><span id="kpiPostEntrega">0</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                        <i class="ri-shield-user-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 4: FILTROS AVANZADOS ─────────────────────────── -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="card-title mb-0 fw-semibold text-body">
                            <i class="ri-filter-3-line me-1 text-primary"></i> Filtros de Búsqueda
                        </h6>
                        <button type="button" class="btn btn-sm btn-ghost-secondary" onclick="limpiarFiltros();">
                            <i class="ri-refresh-line me-1"></i> Limpiar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Estado</label>
                            <select id="filtro_estado" class="form-select form-select-sm">
                                <option value="">Todos los estados</option>
                                <option value="1">1 - Abierta</option>
                                <option value="2">2 - En Investigación</option>
                                <option value="3">3 - Dictaminada</option>
                                <option value="4">4 - Con Gastos</option>
                                <option value="5">5 - Resuelta sin costo</option>
                                <option value="6">6 - Cerrada</option>
                                <option value="0">0 - Cancelada</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Tipo de Incidencia</label>
                            <select id="filtro_tipo" class="form-select form-select-sm">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($data['catalogos']['tipos_incidencia'] as $t): ?>
                                    <option value="<?= $t['id_tipo_incidencia']; ?>"><?= htmlspecialchars($t['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Proveedor / Trasladista</label>
                            <select id="filtro_proveedor" class="form-select form-select-sm">
                                <option value="">Todos los proveedores</option>
                                <?php foreach ($data['catalogos']['proveedores'] as $p): ?>
                                    <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Momento del Reporte</label>
                            <select id="filtro_post_entrega" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <option value="0">En tránsito</option>
                                <option value="1">Post-entrega (Reclamo)</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 5: TABLA PRINCIPAL ────────────────────────────── -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="tableIncidencias" class="table table-hover align-middle table-nowrap mb-0 w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Envío</th>
                                    <th>Tipo</th>
                                    <th>Severidad</th>
                                    <th>Trasladista</th>
                                    <th>VINs Afectados</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                    <th>Gastos Ligados</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- DataTables vía AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- INCLUSIÓN DE MODALES -->
<?php require_once("modal_incidencia.php"); ?>
<?php require_once("modal_dictamen.php"); ?>
<?php require_once("modal_detalle.php"); ?>
<?php require_once("modal_evidencia.php"); ?>
<?php require_once("modal_buscador_envios.php"); ?>

<?php footerAdmin($data); ?>
