<?php headerAdmin($data); ?>
<div id="contentAjax"></div>



<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div> 
            </div>
            <!-- end page title -->

 

            <div class="row g-3 " id="viewListado">
                <div class="col-12">
                    <div class="view-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary-subtle text-secondary border" id="badgeListado">
                                    <i class="ri-list-check-2 me-1"></i> Listado
                                </span>
                              
                            </div>
                        </div>

                        <!-- <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="btnVolverHome2">
                                <i class="ri-arrow-left-line me-1"></i> Volver
                            </button>
                        </div> -->
                    </div>
                </div>


                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted">Buscar</label>
                                    <input type="text" class="form-control form-control-sm" id="filterSearch"
                                        placeholder="Folio / producto...">
                                </div>
                                <div class="col-12 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted">Desde</label>
                                    <input type="date" class="form-control form-control-sm" id="filterDesde">
                                </div>
                                <div class="col-12 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted">Hasta</label>
                                    <input type="date" class="form-control form-control-sm" id="filterHasta">
                                </div>
                                <div class="col-12 col-md-4 col-xxl-2">
                                    <label class="form-label mb-1 small text-muted">Filtro</label>
                                    <select class="form-select form-select-sm" id="filterPrioridad">
                                        <option value="TODAS">Todas</option>
                                        <option value="FINALIZADA">Finalizadas</option>
                                        <option value="EN_PROCESO">En proceso</option>
                                        <option value="PENDIENTE">Pendientes</option>
                                        
                                    </select>
                                </div>
                                <div class="col-12 col-md-8 col-xxl-1 d-grid">
                                    <button class="btn btn-outline-secondary btn-sm" type="button"
                                        id="btnRefrescarListado">
                                        <i class="ri-refresh-line"></i>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- tabla -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">

                            <div class="table-responsive">
                                <table class="table table-sm table-striped align-middle mb-0" id="tablaListados">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:120px;">Folio (OT)</th>
                                            <th>Producto</th>
                                            <th style="width:130px;">Prioridad</th>
                                            <th style="width:120px;">Cantidad</th>
                                            <th style="width:140px;">Inicio</th>
                                            <th style="width:140px;">Requerida</th>
                                            <th style="width:160px;">Estatus</th>
                                            <th style="width:210px;" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyListados"></tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>

            </div><!-- /viewListado -->

        </div><!-- /container-fluid -->
    </div><!-- /page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>document.write(new Date().getFullYear())</script> © LDR.
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