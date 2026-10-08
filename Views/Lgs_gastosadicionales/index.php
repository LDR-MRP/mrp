<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- ── SECCIÓN 1: BREADCRUMBS ────────────────────────────────── -->
            <div class="row align-items-center mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                <li class="breadcrumb-item active text-primary">Gastos Adicionales</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 2: HEADER PRINCIPAL Y ACCIONES ───────────────── -->
            <div class="row align-items-center mb-4">
                <div class="col-md-7">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-lg" style="background-color: #C46623 !important;">
                                <i class="ri-money-dollar-circle-line"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Gastos Adicionales</h3>
                            <p class="text-muted mb-0 fs-14">
                                Autorización, prorrateo por unidad y documentación financiera de costos extraordinarios en envíos.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-3 mt-md-0 gap-2">
                    <a href="<?= base_url(); ?>/Lgs_incidencias" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-semibold">
                        <i class="ri-alert-line me-1"></i> Ir a Incidencias Operativas
                    </a>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" onclick="openModalGasto();">
                        <i class="ri-add-line align-middle fs-16 me-1"></i> Nuevo Gasto
                    </button>
                </div>
            </div>

            <!-- ── SECCIÓN 3: KPIS FINANCIEROS ───────────────────────────── -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes de Aprobación</p>
                                    <h4 class="fs-22 fw-bold text-body mb-0"><span id="kpiPendientes">0</span></h4>
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
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Aprobados por Documentar</p>
                                    <h4 class="fs-22 fw-bold text-body mb-0"><span id="kpiPorDocumentar">0</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-file-text-line"></i>
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
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Cargos Adicionales (Mes)</p>
                                    <h4 class="fs-20 fw-bold text-danger mb-0"><span id="kpiTotalCargos">$0.00</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                        <i class="ri-arrow-up-circle-line"></i>
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
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Deducciones por Recuperar</p>
                                    <h4 class="fs-20 fw-bold text-success mb-0"><span id="kpiTotalDeducciones">$0.00</span></h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-arrow-down-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 4: FILTROS ────────────────────────────────────── -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-transparent border-bottom py-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <h6 class="card-title mb-0 fw-semibold text-body">
                            <i class="ri-filter-3-line me-1 text-primary"></i> Filtros
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
                                <option value="1">1 - Registrado</option>
                                <option value="2">2 - En Revisión</option>
                                <option value="3">3 - Aprobado</option>
                                <option value="4">4 - Rechazado</option>
                                <option value="5">5 - Documentado</option>
                                <option value="0">0 - Cancelado</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Tipo de Gasto</label>
                            <select id="filtro_tipo" class="form-select form-select-sm">
                                <option value="">Todos los tipos</option>
                                <?php foreach ($data['catalogos']['tipos_gasto'] as $t): ?>
                                    <option value="<?= $t['id_tipo_gasto']; ?>"><?= htmlspecialchars($t['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Proveedor Trasladista</label>
                            <select id="filtro_proveedor" class="form-select form-select-sm">
                                <option value="">Todos los proveedores</option>
                                <?php foreach ($data['catalogos']['proveedores'] as $p): ?>
                                    <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Origen</label>
                            <select id="filtro_independiente" class="form-select form-select-sm">
                                <option value="">Todos los orígenes</option>
                                <option value="0">Ligado a Incidencia</option>
                                <option value="1">Gasto Independiente</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── SECCIÓN 5: TABLA PRINCIPAL ────────────────────────────── -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table id="tableGastos" class="table table-hover align-middle table-nowrap mb-0 w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Origen</th>
                                    <th>Envío</th>
                                    <th>Tipo de Gasto</th>
                                    <th>Naturaleza</th>
                                    <th>Proveedor</th>
                                    <th>Monto Final</th>
                                    <th>Unidades</th>
                                    <th>Estado</th>
                                    <th>Documento</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- INCLUSIÓN DE MODALES -->
<?php require_once("modal_gasto.php"); ?>
<?php require_once("modal_detalle.php"); ?>
<?php require_once("modal_documentar.php"); ?>
<?php require_once("modal_buscador_incidencias.php"); ?>
<?php require_once(dirname(__DIR__) . "/Lgs_incidencias/modal_buscador_envios.php"); ?>

<?php footerAdmin($data); ?>
