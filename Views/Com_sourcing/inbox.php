<?php headerAdmin($data); ?>
<div class="main-content bg-light">
    <div class="page-content">
        <div class="container-fluid">

            <!-- HEADER FUSIONADO (Regreso al Dashboard + Contexto) -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 4px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md flex-shrink-0 me-3">
                                <div class="avatar-title rounded-2 bg-dark-subtle text-muted fs-1 border border-light-subtle shadow-sm">
                                    <i class="ri-inbox-archive-line"></i>
                                </div>
                            </div>
                            <div>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb breadcrumb-dot mb-1 fs-12 fw-medium">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);" onclick="window.history.back();" class="text-muted">Negociaciones</a></li>
                                        <li class="breadcrumb-item active text-primary">Inbox de Pendientes</li>
                                    </ol>
                                </nav>
                                <h3 class="mb-0 fw-bold text-uppercase ls-1 text-body">Bandeja de Entrada (Sourcing)</h3>
                                <p class="text-muted mb-0 fs-13 mt-1 fw-medium opacity-75">Seleccione las partidas aprobadas que desea agrupar para negociar.</p>
                            </div>
                        </div>
                        <button class="btn btn-soft-secondary btn-sm border" onclick="window.history.back();">
                            <i class="ri-arrow-left-line me-1"></i> Volver al Historial
                        </button>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- COLUMNA DE SELECCIÓN (LISTADO) -->
                <div class="col-xl-9">
                    <div class="card shadow-sm border-0" style="border-radius: 3px;">
                        <div class="card-header border-0 bg-light-subtle py-3">
                            <div class="row g-3">
                                <div class="col-md-5">
                                    <div class="search-box">
                                        <input type="text" class="form-control border-light shadow-sm" placeholder="Buscar por descripción o folio REQ..." id="searchInbox">
                                        <i class="ri-search-2-line search-icon"></i>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select border-light shadow-sm" id="filter-category">
                                        <option value="">Todas las categorías</option>
                                        <option value="COMPONENTE">Componentes</option>
                                        <option value="SERVICIO">Servicios</option>
                                        <option value="TI">Tecnología / IT</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="tblInbox">
                                    <thead class="bg-light-subtle text-muted text-uppercase fs-11 ls-1">
                                        <tr>
                                            <th style="width: 40px;" class="ps-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="checkAll">
                                                </div>
                                            </th>
                                            <th style="width: 120px;">Folio REQ</th>
                                            <th>Descripción Técnica (Sourcing)</th>
                                            <th>Categoría</th>
                                            <th class="text-end">Precio Obj.</th>
                                            <th class="text-center">Prioridad</th>
                                            <th class="text-end pe-4">Aprobación</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyInbox" class="fs-13">
                                        <!-- Renderizado vía JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DE ACCIÓN (STIKCY CONFIG) -->
                <div class="col-xl-3">
                    <div class="card shadow-lg border-0 sticky-top" style="top: 80px; border-radius: 3px;">
                        <div class="bg-primary" style="height: 4px;"></div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold text-uppercase fs-12 mb-3 text-muted">Configuración del Evento</h5>
                            
                            <div class="mb-3">
                                <label class="form-label fs-12 text-uppercase fw-bold text-muted">Título de la Negociación</label>
                                <input type="text" class="form-control border-light shadow-sm bg-light-subtle" 
                                       id="txtEventTitle" placeholder="Ej: Licitación de Llantas Q3">
                                <small class="text-muted">Este nombre identificará al grupo en el Hub.</small>
                            </div>

                            <hr class="border-light opacity-50">

                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted fs-13">Items seleccionados:</span>
                                <span class="fw-bold text-primary fs-13" id="lbl-count-selected">0</span>
                            </div>
                            <div class="d-flex justify-content-between mb-4">
                                <span class="text-muted fs-13">Presupuesto Acumulado:</span>
                                <span class="fw-bold fs-13" id="lbl-budget-selected">$0.00</span>
                            </div>

                            <button class="btn btn-primary w-100 btn-label waves-effect waves-light shadow" id="btn-confirm-event" disabled>
                                <i class="ri-flashlight-line label-icon align-middle fs-16 me-2"></i> Generar Folio Sourcing
                            </button>
                            
                            <p class="text-center text-muted fs-11 mt-3 mb-0 italic">
                                <i class="ri-information-line"></i> Al confirmar, las partidas se bloquearán para otras negociaciones.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

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


