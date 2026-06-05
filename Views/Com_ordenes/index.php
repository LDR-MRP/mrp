<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <section id="view-index-ordenes">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item active text-primary">Órdenes de Compra</li>
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
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important; color: #ffffff !important;">
                                    <i class="ri-shopping-bag-3-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Bandeja de Órdenes de Compra</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Seguimiento de compras, control de estatus de tránsito y recepciones de almacén.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0 gap-2">
                        <button class="btn btn-outline-primary btn-lg btn-label waves-effect waves-light shadow-sm" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFilters">
                            <i class="ri-filter-2-line label-icon align-middle fs-18 me-2"></i> Filtros
                        </button>
                    </div>
                </div>

                <!-- 3. NUEVO BLOQUE DE KPIS CIRCULARES (ESTILO 3-WAY MATCH / CXP) -->
                <div class="row mb-4">
                    <!-- KPI 1: Emitidas -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Emitidas (En Firma)</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-emitidas">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">En validación</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-file-list-3-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: En Tránsito -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Tránsito</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-transito">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Ruta al almacén</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Surtido Parcial -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Surtido Parcial</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-parciales">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Recepciones WMS</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-time-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Finalizadas (Cerradas) -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Finalizadas (Cerradas)</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-cerradas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Entregadas</span>
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
                </div>

                <!-- 4. FILTROS AVANZADOS COLLAPSE -->
                <div class="collapse mb-4" id="collapseFilters">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body bg-light">
                            <form id="formFiltrosOC" class="row g-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-bold text-muted">Proveedor</label>
                                    <select name="proveedorid" class="form-select form-select-sm border-0 shadow-sm">
                                        <option value="">Todos los proveedores</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Estatus</label>
                                    <select name="estatus" class="form-select form-select-sm border-0 shadow-sm">
                                        <option value="">Todos</option>
                                        <option value="emitida">Emitida</option>
                                        <option value="en_transito">En Tránsito</option>
                                        <option value="recibida_parcial">Recibida Parcial</option>
                                        <option value="cerrada">Cerrada</option>
                                        <option value="cancelada">Cancelada</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Desde</label>
                                    <input type="date" name="fecha_desde" class="form-control form-control-sm border-0 shadow-sm">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label small fw-bold text-muted">Hasta</label>
                                    <input type="date" name="fecha_hasta" class="form-control form-control-sm border-0 shadow-sm">
                                </div>
                                <div class="col-md-3 d-flex align-items-end gap-2">
                                    <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm fw-bold">Aplicar</button>
                                    <button type="reset" class="btn btn-light btn-sm w-100 border">Limpiar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 5. TABLA PRINCIPAL CARD -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tblOrders" class="table table-nowrap align-middle mb-0 table-hover" style="width:100%">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">Folio</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold">Fecha</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold">Proveedor</th>
                                        <th class="text-uppercase text-muted fs-11 fw-bold">Ref. Req</th>
                                        <th class="text-end text-uppercase text-muted fs-11 fw-bold">Total</th>
                                        <th class="text-center text-uppercase text-muted fs-11 fw-bold">Estatus</th>
                                        <th class="pe-4 text-end text-uppercase text-muted fs-11 fw-bold">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="border-top-0">
                                    <!-- Loader Inicial -->
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Cargando órdenes...</span>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Sincronizado con Almacén &amp; CxP en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>