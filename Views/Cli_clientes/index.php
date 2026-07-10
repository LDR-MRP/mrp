<?php headerAdmin($data);
?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row g-3 " id="viewListado">
                <div class="col-12">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">

                            <div class="d-flex align-items-center">

                                <div
                                    class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center me-3">
                                    <i class="ri-team-line fs-2"></i>
                                </div>

                                <div>
                                    <h3 class="mb-0 fw-bold">Clientes</h3>
                                    <small class="text-muted">
                                        Administra y consulta la información de tus clientes.
                                    </small>
                                </div>

                            </div>

                            <div>

                                <button class="btn btn-primary btn-label">
                                    <i class="ri-user-add-line label-icon align-middle fs-16 me-2"></i>
                                    Agregar Cliente
                                </button>

                            </div>

                        </div>
                    </div>
                </div>


                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted">Buscar</label>
                                    <input type="text" class="form-control form-control-sm" id="filterSearch"
                                        placeholder="Clave / Razón social...">
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
                                    <label class="form-label mb-1 small text-muted">Clientes</label>
                                    <select class="form-select form-select-sm" id="filterPrioridad">
                                        <option value="TODOS">Todos</option>
                                        <option value="DISTRIBUIDORES">Distribuidores</option>
                                        <option value="INTERNOS">Internos</option>
                                        <option value="EXTERNOS">Externos</option>
                                        <option value="GUBERNAMENTALES">Gubernamentales</option>

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
                                <table class="table table-hover align-middle table-nowrap mb-0" id="tablaListados">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width:120px;">Clave</th>
                                            <th>Razón Social</th>
                                            <th style="width:140px;">Nombre Comercial</th>
                                            <th style="width:120px;">RFC</th>
                                            <th style="width:140px;">Limite Crédito</th>
                                            <th style="width:140px;">Días de Crédito</th>
                                            <th style="width:120px;">Fecha registro</th>
                                            <th style="width:50px;">Estatus</th>
                                            <th style="width:260px;" class="text-end">Acciones</th>
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
<!-- end main content-->
<?php footerAdmin($data); ?>