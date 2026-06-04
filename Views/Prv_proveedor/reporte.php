<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <section id="view-reporte-onboarding-ceo">
                <!-- 1. BREADCRUMBS (Omitido al Imprimir) -->
                <div class="row align-items-center mb-4 d-print-none">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/prv_proveedor">Proveedores</a></li>
                                    <li class="breadcrumb-item active text-primary">Reporte Analítico</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIONES (Imprimir & Filtrar) -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4 shadow-lg border border-light rounded-circle" style="background-color: #C46623 !important;">
                                <span class="avatar-title text-white rounded-circle fs-2">
                                    <i class="ri-file-chart-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Reporte Analítico de Onboarding</h3>
                                <p class="text-muted mb-0 fs-14 d-print-none">
                                    Estatus general de integración de socios comerciales, expediente digital y datos satélite.
                                </p>
                                <p class="text-muted mb-0 fs-13 d-none d-print-block">
                                    LDR Solutions S.A. de C.V. • Generado el: <?= date('d/M/Y H:i') ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Botones de Acción alineados y limpios de CSS personalizados -->
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0 gap-2 d-print-none">
                        <button class="btn btn-outline-primary btn-lg btn-label waves-effect waves-light shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFiltersReport">
                            <i class="ri-filter-2-line label-icon align-middle fs-18 me-2"></i> Filtrar por Planta
                        </button>
                        <button type="button" class="btn btn-success btn-lg btn-label waves-effect waves-light shadow-md" onclick="window.print();">
                            <i class="ri-printer-line label-icon align-middle fs-18 me-2"></i> Imprimir / PDF
                        </button>
                    </div>
                </div>

                <!-- 3. SECCIÓN DE FILTROS AVANZADOS COLLAPSE (ADN unificado con Órdenes de Compra) -->
                <div class="collapse mb-4 d-print-none" id="collapseFiltersReport">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body bg-light">
                            <form id="formFiltrosReporte" class="row g-3 align-items-end">
                                <div class="col-md-6 col-lg-8">
                                    <label class="form-label small fw-bold text-muted">Filtrar por Planta de Cargo</label>
                                    <select name="plantaid" id="sel-planta-reporte" class="form-select border-0 shadow-sm bg-white form-select-lg">
                                        <option value="">— Ver todas las plantas de LDR —</option>
                                    </select>
                                </div>
                                <div class="col-md-6 col-lg-4 d-flex gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg w-100 shadow-sm fw-bold">Aplicar Filtro</button>
                                    <button type="reset" id="btn-limpiar-reporte" class="btn btn-light btn-lg w-100 border">Limpiar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <!-- KPI 1: Onboarding Completado -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Completados (100%)</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-completos">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Socios Activos</span>
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

                    <!-- KPI 2: En Proceso / Carga -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Carga (Onboarding)</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-onboarding-total">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Captura Pendiente</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-user-shared-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Auditoría L2 -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes Finanzas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-pendientes-l2">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Revisión de PDFs</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-shield-user-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Sin Datos Satélite -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Expedientes Incompletos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2 text-danger"><span class="counter-value" id="kpi-incompletos">0</span></h4>
                                        <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Falta Dirección/Banco</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                            <i class="ri-user-unfollow-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. TABLA CARD EJECUTIVA -->
                <div class="card border-0 shadow-xl overflow-hidden" style="border-radius: 12px;">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tblReporteCEO" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-uppercase text-muted fs-11 fw-bold py-3" style="width: 25%;">Proveedor</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold py-3">RFC</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold py-3" style="width: 18%;">Progreso Expediente</th>
                                        <th class="text-center text-uppercase text-muted fs-11 fw-bold py-3">Datos Bancarios</th>
                                        <th class="text-center text-uppercase text-muted fs-11 fw-bold py-3">Dirección</th>
                                        <th class="text-center text-uppercase text-muted fs-11 fw-bold py-3">Contacto</th>
                                        <th class="text-center text-uppercase text-muted fs-11 fw-bold py-3">Config. Financiera</th>
                                        <th class="pe-4 text-center text-uppercase text-muted fs-11 fw-bold py-3">Estatus</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyReporteCEO" class="border-top-0">
                                    <!-- Loader Inicial -->
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Compilando reporte analítico...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer border-top-0 py-4 d-print-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Auditoría integrada del Portal SRM. Datos listos para descarga.
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- ESTILO EXCLUSIVO DE IMPRESIÓN (MEDIOS FÍSICOS / PDF) -->
<style>
@media print {
    /* Ocultar elementos innecesarios al imprimir */
    #layout-wrapper, .app-menu, .vertical-overlay, #page-topbar, .footer, .d-print-none, .btn {
        display: none !important;
    }
    .main-content {
        margin-left: 0 !important;
        margin-right: 0 !important;
        padding-top: 0 !important;
    }
    .page-content {
        padding: 0 !important;
    }
    .card {
        border: 0 !important;
        box-shadow: none !important;
    }
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
    }
}
</style>

<?php footerAdmin($data); ?>