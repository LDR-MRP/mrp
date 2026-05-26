<?php require_once("Views/Template/Srm/header_srm.php"); ?>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Saludo de bienvenida -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark fs-18">Bienvenido, <span id="lbl-welcome-user">...</span></h4>
                            <p class="text-muted mb-0 fs-13">Este es el resumen de la relación comercial con LDR Solutions hoy.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- FILA DE KPIs (4 Tarjetas Modernas) -->
            <div class="row">
                <!-- KPI 1: Órdenes Activas -->
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate fs-11 mb-2">Órdenes de Compra Activas</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0" id="kpi-ordenes">0</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-shopping-bag-3-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Facturas en Proceso -->
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate fs-11 mb-2">Facturas en Proceso</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0" id="kpi-facturas">0</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="ri-file-text-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Cuentas por Cobrar (MXN) -->
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate fs-11 mb-2">Monto Pendiente de Pago</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0" id="kpi-pagos">$0.00</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-money-dollar-box-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: Compliance / Expediente % (Ajustado a Naranja LDR) -->
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate fs-11 mb-2">Estatus del Expediente</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0" id="kpi-compliance">Cargando...</h4>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <!-- Usamos info-subtle para que el CSS lo pinte con el naranja LDR -->
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3" id="kpi-compliance-icon">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila de Suministros y Actividad -->
            <div class="row">
                <!-- Gráfica de Suministros (8 Columnas) -->
                <div class="col-xl-8">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold text-dark mb-0">Historial de Suministros</h5>
                        </div>
                        <div class="card-body">
                            <div id="chart-suministros" class="apex-charts text-center py-5 text-muted">
                                <i class="ri-bar-chart-fill display-4 text-light d-block mb-3" style="color: rgba(196, 102, 35, 0.2) !important;"></i>
                                Pronto se mostrará la analítica de tus facturas y órdenes entregadas.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actividad Reciente / Auditoría (4 Columnas) -->
                <div class="col-xl-4">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold text-dark mb-0">Actividad Reciente</h5>
                        </div>
                        <div class="card-body">
                            <div class="acitivity-timeline" id="recent-activity-list">
                                <div class="text-center text-muted py-5">
                                    Cargando bitácora...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once("Views/Template/Srm/footer_srm.php"); ?>