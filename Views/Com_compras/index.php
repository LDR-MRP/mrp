<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                /* Mantenemos los badges para coherencia visual */
                .badge-approved { background: #f0fdf4; color: #15803d; }
                .badge-purchasing { background: #f5f3ff; color: #6d28d9; }
                .badge-priority-high { background: #fef2f2; color: #b91c1c; font-weight: bold; }
                .badge-priority-mid { background: #fffbeb; color: #b45309; }

                :root {
                    --primary: #0056b3;
                    --success: #28a745;
                    --info: #0dcaf0;
                }

                body {
                    background-color: #f4f7f6;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }

                .ls-1 { letter-spacing: 1px; }
            </style>

            <section id="view-index-compras">
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active">Mesa de Control</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title bg-white text-success rounded-circle fs-2 shadow-lg border border-light">
                                    <i class="ri-shopping-basket-2-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 text-dark fw-bold text-uppercase ls-1">Mesa de Control de Compras</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Conversión de requisiciones aprobadas en órdenes de compra formales.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-xl-4 col-md-6">
                        <div class="card card-animate border-0 shadow-sm border-start border-success border-3" style="border-radius: 10px;">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Listas para Comprar</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-success fs-14 mb-0">
                                            <i class="ri-checkbox-circle-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-listas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Esperando OC</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Monto Est. en Fila</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-info fs-14 mb-0">
                                            <i class="ri-money-dollar-circle-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2">$<span class="counter-value" id="kpi-monto-fila">0.00</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Valor aproximado</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-0 fs-12 ls-1">Urgencias Detectadas</p>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <h5 class="text-danger fs-14 mb-0">
                                            <i class="ri-error-warning-line fs-22 align-middle"></i>
                                        </h5>
                                    </div>
                                </div>
                                <div class="d-flex align-items-end justify-content-between mt-3">
                                    <div>
                                        <h4 class="fs-24 fw-bold text-dark mb-2"><span class="counter-value" id="kpi-urgentes">0</span></h4>
                                        <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Atención inmediata</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-xl" style="border-radius: 12px;">
                    <div class="bg-success" style="height: 4px;"></div> <div class="card-body">
                        <div class="table">
                            <table id="tblCompras" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3 ps-4">Folio Req</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Prioridad</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Departamento</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Solicitante</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Monto Est.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Operación</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyCompras" class="border-top-0">
                                    </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-information-line text-info me-1"></i> Mostrando únicamente requisiciones con estatus <b>APROBADA</b>.
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
                    <?= date('Y'); ?> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        LDR Solutions · MRP Procurement
                    </div>
                </div>
            </div>
        </div>
    </footer>
</div>

<?php footerAdmin($data); ?>