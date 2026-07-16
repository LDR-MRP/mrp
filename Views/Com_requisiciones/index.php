<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <section id="view-index-general">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active text-primary">Requisiciones</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-file-list-3-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Bandeja de Requisiciones</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión centralizada y seguimiento de solicitudes internas.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <button class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md" data-redirect="com_requisicion/create" data-permiso="COM_REQUISICIONES|w">
                            <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nueva Requisición
                        </button>
                    </div>
                </div>

                <!-- 3. NUEVO BLOQUE DE KPIS CIRCULARES (ESTILO 3-WAY MATCH) -->
                <div class="row mb-4">
                    <!-- KPI 1: Pendientes de Revisión -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes de Revisión</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-pendientes">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Requieren firma</span>
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

                    <!-- KPI 2: Listas para Compra -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Listas para Compra</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-aprobadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Aprobadas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-check-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: En Proceso de Compra -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Proceso de Compra</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-en-compra">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">En Cumplimiento Parcial</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-shopping-cart-fill"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Finalizadas este Mes -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Finalizadas este Mes</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-finalizadas">0</span></h4>
                                        <span class="badge bg-soft-secondary text-secondary fw-medium mb-0 px-2 py-1">Cerradas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fs-3">
                                            <i class="ri-check-double-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. TABLA CARD -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tblReqs" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Título</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha de Elaboración</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha de Recepción de Requisición</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Empresa</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Solicitó</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Autorizó</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Departamento</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Centro de Costo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado Actual</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyReqs" class="border-top-0">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Datos sincronizados en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>document.write(new Date().getFullYear())</script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">LDR Solutions · MRP</div>
                </div>
            </div>
        </div>
    </footer>
</div>

<?php footerAdmin($data); ?>