<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                .badge-draft {
                    background: #f1f5f9;
                    color: #475569;
                }

                .badge-review {
                    background: #e0f2fe;
                    color: #0369a1;
                }

                .badge-observed {
                    background: #fffbeb;
                    color: #b45309;
                }

                .badge-approved {
                    background: #f0fdf4;
                    color: #15803d;
                }

                .badge-rejected {
                    background: #fef2f2;
                    color: #b91c1c;
                }

                .badge-purchasing {
                    background: #f5f3ff;
                    color: #6d28d9;
                }

                .badge-closed {
                    background: #f8fafc;
                    color: #1e293b;
                    border: 1px solid #e2e8f0;
                }

                :root {
                    --primary: #0056b3;
                    --success: #28a745;
                    --warning: #ffc107;
                    --danger: #dc3545;
                }

                body {
                    background-color: #f4f7f6;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
            </style>
            <section id="view-index-general">
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Requisiciones</li>
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
                                    <i class="ri-file-list-3-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 text-dark fw-bold text-uppercase ls-1">Bandeja de Requisiciones</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión centralizada y seguimiento de solicitudes internas.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <?php if (hasPermissions(COM_REQUISICIONES, 'w')): ?>
                        <button class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md" data-redirect="com_requisicion/create">
                            <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nueva Requisición
                        </button>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm border-start border-warning border-3" style="border-radius: 10px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Pendientes de Revisión</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-warning fs-14 mb-0">
                                            <i class="ri-time-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-pendientes">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Requieren firma</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm border-start border-info border-3" style="border-radius: 10px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">En Proceso de Compra</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-info fs-14 mb-0">
                                            <i class="ri-shopping-cart-2-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-aprobadas">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Aprobadas</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Finalizadas este Mes</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-success fs-14 mb-0">
                                            <i class="ri-check-double-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-finalizadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Cerradas</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-xl" style="border-radius: 12px;">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body">
                        <div class="">
                            <table id="tblReqs" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3 ps-4">Folio</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha Solicitud</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Solicitante</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Departamento</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado Actual</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyReqs" class="border-top-0">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Datos sincronizados en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        LDR Solutions · MRP
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>

<?php footerAdmin($data); ?>