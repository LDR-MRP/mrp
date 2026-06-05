<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-proveedores">
                <!-- 1. BREADCRUMBS (Estilo EMR) -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active text-primary">Proveedores</li>
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
                                <span class="avatar-title text-primary rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important; color: #ffffff !important;">
                                    <i class="ri-truck-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Gestión de Proveedores</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Administración del directorio de empresas y socios comerciales.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <?php if (hasPermissions(PRV_PROVEEDORES, 'w')): ?>
                            <button type="button" class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md"
                                data-redirect="prv_proveedor/create">
                                <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nuevo Proveedor
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES PREMIUM (SOBREESCRIBIBLE PARA MODO OSCURO) -->
                <div class="row mb-4">
                    <!-- KPI 1: Total Proveedores -->
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Total Proveedores</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-total">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Directorio completo</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-building-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Activos & Operativos -->
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Activos &amp; Operativos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-activos">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Listos para compra</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-check-double-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Bloqueados / Inactivos -->
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Bloqueados / Inactivos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-inactivos">0</span></h4>
                                        <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Requieren Atención</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                            <i class="ri-spam-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. TABLA PRINCIPAL CARD -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tblProveedores" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3 ps-4">Fecha Alta</th>
                                        <th width="10%" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado Onboarding</th>
                                        <th width="10%" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado Operativo</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Nombre Comercial</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Razón Social</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Cuenta Contable</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">RFC</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Teléfono</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Plazo de Crédito</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Ciudad</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">IVA</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Creado Por</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Crédito (MXN)</th>
                                        <th width="10%" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <!-- Tu JS de datatables inyectará las filas de forma idéntica -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Directorio sincronizado en tiempo real
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
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR Solutions.
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