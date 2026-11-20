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

                                <button id="btnAgregarCliente"  class="btn btn-primary btn-label">
                                    <i class="ri-user-add-line label-icon align-middle fs-16 me-2"></i>
                                    Agregar Cliente
                                </button>

                            </div>

                        </div>
                    </div>
                </div>


                <div class="col-12">
                    <div class="row g-3">
                        <?php foreach ([
                            ['Total de clientes', 'clientesTotal', 'ri-group-line'],
                            ['Activos', 'clientesActivos', 'ri-checkbox-circle-line'],
                            ['Distribuidores', 'clientesDistribuidores', 'ri-truck-line'],
                            ['Resultados filtrados', 'clientesFiltrados', 'ri-filter-3-line']
                        ] as [$etiqueta, $id, $icono]): ?>
                            <div class="col-6 col-xl-3">
                                <div class="card h-100 mb-0">
                                    <div class="card-body d-flex justify-content-between align-items-center gap-2">
                                        <div>
                                            <div class="text-muted small"><?= $etiqueta ?></div>
                                            <strong class="fs-4" id="<?= $id ?>">—</strong>
                                        </div>
                                        <span class="avatar-sm rounded-circle bg-primary-subtle text-primary d-inline-flex align-items-center justify-content-center">
                                            <i class="<?= $icono ?> fs-4" aria-hidden="true"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-2 align-items-end">
                                <div class="col-12 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted">Buscar</label>
                                    <input type="text" class="form-control form-control-sm" id="filterSearch"
                                        placeholder="Código, nombre, RFC o correo">
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
                                        <option value="">Todos</option>
                                        <?php foreach ($data['tipos_cliente'] as $tipo): ?>
                                            <option value="<?= (int) $tipo['id'] ?>">
                                                <?= htmlspecialchars($tipo['nombre'], ENT_QUOTES, 'UTF-8') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted" for="filterEstado">Estatus</label>
                                    <select class="form-select form-select-sm" id="filterEstado">
                                        <option value="">Todos</option>
                                        <option value="2">Activo</option>
                                        <option value="1">Inactivo</option>
                                        <option value="3">Suspendido</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4 col-xxl-3">
                                    <label class="form-label mb-1 small text-muted" for="filterCorreoAcceso">Correo de acceso</label>
                                    <select class="form-select form-select-sm" id="filterCorreoAcceso">
                                        <option value="">Todos</option>
                                        <option value="Enviado">Enviado</option>
                                        <option value="Pendiente">Pendiente</option>
                                        <option value="No aplica">No aplica</option>
                                    </select>
                                </div>
                                <div class="col-6 col-md-4 col-xxl-3 d-grid">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="btnLimpiarFiltros">
                                        <i class="ri-filter-off-line me-1" aria-hidden="true"></i> Limpiar filtros
                                    </button>
                                </div>
                                <div class="col-6 col-md-4 col-xxl-3 d-grid">
                                    <button class="btn btn-outline-secondary btn-sm" type="button"
                                        id="btnRefrescarListado">
                                        <i class="ri-refresh-line me-1" aria-hidden="true"></i> Actualizar
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

                            <div class="d-flex gap-2 justify-content-end mb-3">
                                <button class="btn btn-outline-success btn-sm" type="button" id="btnClientesExcel">
                                    <i class="ri-file-excel-2-line me-1"></i> Exportar Excel
                                </button>
                                <button class="btn btn-outline-danger btn-sm" type="button" id="btnClientesPdf">
                                    <i class="ri-file-pdf-line me-1"></i> Exportar PDF
                                </button>
                            </div>
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
                                            <th>Correo de acceso</th>
                                            <th style="width:260px;" class="text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyListados"></tbody>
                                </table>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <div class="d-flex align-items-center gap-2">
                                    <label class="small text-muted mb-0" for="clientesPorPagina">Filas por página</label>
                                    <select class="form-select form-select-sm w-auto" id="clientesPorPagina">
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                                <span id="clientesRango" class="small text-muted" role="status"></span>
                                <nav class="d-flex align-items-center gap-2" aria-label="Paginación de clientes">
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="clientesAnterior" disabled>Anterior</button>
                                    <span id="clientesPagina" class="small text-nowrap"></span>
                                    <button class="btn btn-outline-secondary btn-sm" type="button" id="clientesSiguiente" disabled>Siguiente</button>
                                </nav>
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