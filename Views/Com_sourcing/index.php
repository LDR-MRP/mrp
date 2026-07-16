<?php headerAdmin($data); ?>
<div class="main-content bg-light">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-sourcing">
                <!-- HEADER Resuelve el problema de fondo) -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 4px;">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <!-- Izquierda: Identidad del Módulo -->
                            <div class="col-md-8">
                                <div class="d-flex align-items-center">
                                    <!-- Icono Sobrio (Graphite Style) -->
                                    <div class="avatar-md flex-shrink-0 me-3">
                                        <div class="avatar-title rounded-2 bg-dark-subtle text-muted fs-1 border border-light-subtle shadow-sm">
                                            <i class="ri-node-tree"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <!-- Breadcrumb discreto arriba del título -->
                                        <nav aria-label="breadcrumb">
                                            <ol class="breadcrumb breadcrumb-dot mb-1 fs-12 fw-medium">
                                                <li class="breadcrumb-item"><a href="javascript: void(0);" class="text-muted">Compras</a></li>
                                                <li class="breadcrumb-item active text-primary">Negociaciones</li>
                                            </ol>
                                        </nav>
                                        <h3 class="mb-0 fw-bold text-uppercase ls-1 text-body">Panel de Negociaciones</h3>
                                        <p class="text-muted mb-0 fs-13 mt-1 fw-medium opacity-75">
                                            <i class="ri-focus-3-line align-middle me-1 text-primary"></i> Sourcing Hub | Consolidación de requerimientos y adjudicación.
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- Derecha: Acciones Rápidas (Opcional) -->
                            <div class="col-md-4 text-md-end mt-3 mt-md-0 d-none">
                                <!-- Botón secundario aquí si fuera necesario -->
                            </div>
                        </div>
                    </div>
                </div>
                            

                <!-- 2. KPIs DE NEGOCIACIÓN -->
                <div class="row">
                    
                    <!-- KPI 1: Negociaciones Activas -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card card-animate border-0 shadow-sm" style="border-radius: 3px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-uppercase fw-bold text-muted mb-0 fs-11 ls-1">Negociaciones Activas</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 fs-10 fw-bold">
                                            <i class="ri-pulse-line align-middle me-1"></i> EN PROCESO
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-28 fw-bold ff-secondary mb-1 text-primary">
                                            <span id="kpi-activas" class="counter-value">0</span>
                                        </h4>
                                        <p class="text-muted fs-12 mb-0 fw-medium">Folios abiertos en Hub</p>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-light text-secondary rounded-1 fs-2 border border-light-subtle">
                                            <i class="ri-hand-coin-line text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Items sin Negociar (Inbox) -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card card-animate border-0 shadow-sm" style="border-radius: 3px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-uppercase fw-bold text-muted mb-0 fs-11 ls-1">Items sin Negociar</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle rounded-pill px-2 fs-10 fw-bold">
                                            <i class="ri-error-warning-line align-middle me-1"></i> REQUIERE ATENCIÓN
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-28 fw-bold ff-secondary mb-1 text-warning">
                                            <span id="kpi-pendientes" class="counter-value">0</span>
                                        </h4>
                                        <p class="text-muted fs-12 mb-0 fw-medium">Artículos especiales aprobados</p>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-light text-secondary rounded-1 fs-2 border border-light-subtle">
                                            <i class="ri-inbox-archive-line text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Ahorro Mensual (Financiero) -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card card-animate border-0 shadow-sm" style="border-radius: 3px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-uppercase fw-bold text-muted mb-0 fs-11 ls-1">Ahorro Mensual</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2 fs-10 fw-bold">
                                            <i class="ri-line-chart-line align-middle me-1"></i> VS PRECIO OBJ.
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-28 fw-bold ff-secondary mb-1 text-success">
                                            <span id="kpi-ahorro" class="counter-value">0.00</span>
                                        </h4>
                                        <p class="text-muted fs-12 mb-0 fw-medium">Logrado en el mes actual</p>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-light text-secondary rounded-1 fs-2 border border-light-subtle">
                                            <i class="ri-money-dollar-circle-line text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Pendientes Dictamen (SoD) -->
                    <div class="col-md-6 col-xl-3">
                        <div class="card card-animate border-0 shadow-sm" style="border-radius: 3px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <p class="text-uppercase fw-bold text-muted mb-0 fs-11 ls-1">Pendientes Dictamen</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-info-subtle text-info border border-info-subtle rounded-pill px-2 fs-10 fw-bold">
                                            <i class="ri-auction-line align-middle me-1"></i> DECISIÓN PENDIENTE
                                        </span>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-28 fw-bold ff-secondary mb-1 text-info">
                                            <span id="kpi-dictamen" class="counter-value">0</span>
                                        </h4>
                                        <p class="text-muted fs-12 mb-0 fw-medium">Esperando adjudicación</p>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-light text-secondary rounded-1 fs-2 border border-light-subtle">
                                            <i class="ri-shield-user-line text-muted"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- 3. TABLA DE NEGOCIACIONES -->
                <div class="card shadow-sm border-0 overflow-hidden" style="border-radius: 3px;">
                    
                    <div class="card-header border-0 align-items-center d-flex py-3">
                        <h5 class="card-title mb-0 flex-grow-1 fw-bold text-uppercase fs-12 text-muted">
                            <i class="ri-list-settings-line me-1"></i> Historial de Negociaciones
                        </h5>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary btn-sm btn-label waves-effect waves-light shadow-sm" id="btn-nueva-negociacion">
                                <i class="ri-add-circle-line label-icon align-middle fs-16 me-2"></i> Nueva Negociación
                            </button>
                        </div>
                    </div>
                    
                    <!-- FILTROS TÉCNICOS -->
                    <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0 py-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="search-box">
                                    <input type="text" class="form-control border-light shadow-sm" placeholder="Buscar por folio o título..." id="searchNegociacion">
                                    <i class="ri-search-2-line search-icon"></i>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select border-light shadow-sm" id="filter-status">
                                    <option value="">Todos los estatus</option>
                                    <option value="ABIERTO">Abierto</option>
                                    <option value="DICTAMEN">En Dictamen</option>
                                    <option value="ADJUDICADO">Adjudicado</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-nowrap align-middle mb-0" id="tblNegociaciones">
                                <thead class="bg-light-subtle text-muted text-uppercase fs-11 ls-1">
                                    <tr>
                                        <th style="width: 80px;" class="ps-4">Folio</th>
                                        <th>Título de Negociación</th>
                                        <th>Comprador Responsable</th>
                                        <th class="text-center">Ofertas / Compliance</th>
                                        <th>Creación</th>
                                        <th>Estatus</th>
                                        <th class="text-end pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyNegociaciones" class="fs-13">
                                    <!-- Render Dinámico -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </section>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>document.write(new Date().getFullYear())</script> © LDR Solutions.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        MRP System v1.0
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>
<?php footerAdmin($data); ?>