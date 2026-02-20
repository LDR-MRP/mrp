<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-proveedores">
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Proveedores</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title bg-white text-primary rounded-circle fs-2 shadow-lg border border-light">
                                    <i class="ri-truck-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 text-dark fw-bold text-uppercase ls-1">Gestión de Proveedores</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Administración del directorio de empresas y socios comerciales.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <!-- Attention! -->
                        <?php if (hasPermissions(PRV_PROVEEDORES, 'w')): ?>
                            <button type="button" class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md"
                                data-redirect="prv_proveedor/create">
                                <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nuevo Proveedor
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm border-start border-primary border-3" style="border-radius: 10px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Total Proveedores</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-primary fs-14 mb-0">
                                            <i class="ri-building-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-total">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Directorio completo</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm border-start border-success border-3" style="border-radius: 10px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Activos & Operativos</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-success fs-14 mb-0">
                                            <i class="ri-check-double-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-activos">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Listos para compra</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm border-start border-danger border-3" style="border-radius: 10px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Bloqueados / Inactivos</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-danger fs-14 mb-0">
                                            <i class="ri-spam-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-inactivos">0</span></h4>
                                        <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Requieren Atención</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-xl overflow-hidden" style="border-radius: 12px;">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tblProveedores" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th width="5%" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3 ps-4">ID</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Clave</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Razón Social / RFC</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Contacto</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Crédito (MXN)</th>
                                        <th width="10%" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th width="10%" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top-0 py-4">
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