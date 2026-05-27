<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <section id="view-index-cxp">
                <!-- 1. BREADCRUMBS (Estilo EMR) -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active text-primary">Bandeja de Conciliación CxP</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN (Naranja LDR & Grafito) -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important; color: #ffffff !important;">
                                    <i class="ri-calculator-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Bandeja de Conciliación (CxP)</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Auditoría del motor de Three-Way Match y liberación de facturas de proveedores.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES (ESTILO 3-WAY MATCH) -->
                <div class="row mb-4">
                    <!-- KPI 1: Congeladas (Holds de 3-Way Match) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Retenidas (3-Way Hold)</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-congeladas">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">En proceso de cotejo</span>
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

                    <!-- KPI 2: Autorizadas para Pago -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Autorizadas para Pago</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-aprobadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Listas para tesorería</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-checkbox-circle-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Rechazadas -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Rechazadas (SAT/Total)</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-rechazadas">0</span></h4>
                                        <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Requieren Corrección</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                            <i class="ri-close-circle-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Vencidas (Urgentes) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Vencidas (Urgentes)</p>
                                        <h4 class="fs-22 fw-bold text-danger mb-2"><span class="counter-value" id="kpi-vencidas">0</span></h4>
                                        <span class="badge bg-danger text-white fw-medium mb-0 px-2 py-1">Plazo de crédito expirado</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-danger text-white rounded-circle fs-3" style="background-color: #f06548 !important;">
                                            <i class="ri-error-warning-line"></i>
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
                            <table class="table table-nowrap align-middle mb-0 table-hover" id="tbl-cxp-invoices" style="width: 100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Serie / Folio</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Proveedor</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha de Carga</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">OC Asociada</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold text-end ls-1 py-3">Monto Total</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estatus Validación</th>
                                        <th class="pe-4 text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbl-body-cxp-invoices" class="border-top-0">
                                    <!-- Loader Inicial -->
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando facturas...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Auditoría integrada con el motor de Three-Way Match de forma Stateless
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