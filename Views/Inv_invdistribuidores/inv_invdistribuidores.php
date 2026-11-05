<?php headerAdmin($data); ?>
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
                                <li class="breadcrumb-item"><a href="<?= base_url() ?>/inv_unidades">Inventario</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <?php if (empty($data['tablas_listas'])) { ?>
                <div class="alert alert-danger" role="alert">
                    <i class="ri-error-warning-line align-bottom me-1"></i>
                    Faltan las tablas del módulo. Corre el script
                    <code>Docs/desarrollo/inventario_distribuidores/migracion_retail_distribuidores_20261104.sql</code>.
                </div>
            <?php } ?>

            <!-- FILTROS (aplican a KPIs, listado, resumen y exportación) -->
            <div class="card">
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-xl-3 col-md-6">
                            <label class="form-label" for="fDistribuidor">Distribuidor</label>
                            <select class="form-select" id="fDistribuidor"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-2 col-md-6">
                            <label class="form-label" for="fModelo">Modelo gen</label>
                            <select class="form-select" id="fModelo"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label" for="fEstatus">Estatus</label>
                            <select class="form-select" id="fEstatus"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label" for="fTipoPago">Tipo de pago</label>
                            <select class="form-select" id="fTipoPago"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-1 col-md-2 col-6">
                            <label class="form-label" for="fMesDesde">Mes desde</label>
                            <select class="form-select" id="fMesDesde"><option value="">—</option></select>
                        </div>
                        <div class="col-xl-1 col-md-2 col-6">
                            <label class="form-label" for="fMesHasta">Mes hasta</label>
                            <select class="form-select" id="fMesHasta"><option value="">—</option></select>
                        </div>
                        <div class="col-xl-1 col-md-12 text-end">
                            <button type="button" class="btn btn-light w-100" id="btnLimpiarFiltros" title="Limpiar filtros">
                                <i class="ri-filter-off-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row">
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Unidades retail</p>
                        <h4 class="mb-0 text-primary" id="kpiTotal">0</h4>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Distribuidores</p>
                        <h4 class="mb-0" id="kpiDistribuidores">0</h4>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Meses con retail</p>
                        <h4 class="mb-0" id="kpiMeses">0</h4>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Último mes <span id="kpiUltimoMes" class="text-body"></span></p>
                        <h4 class="mb-0 text-success" id="kpiEnUltimoMes">0</h4>
                    </div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRetail" type="button" role="tab">
                                <i class="ri-shopping-cart-2-line align-bottom me-1"></i> Retail
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabResumen" type="button" role="tab">
                                <i class="ri-bar-chart-grouped-line align-bottom me-1"></i> Resumen por mes
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCargas" type="button" role="tab">
                                <i class="ri-history-line align-bottom me-1"></i> Cargas (historial)
                            </button>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCarga" type="button" role="tab">
                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Subir archivo
                                </button>
                            </li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <!-- ============ RETAIL ============ -->
                        <div class="tab-pane fade show active" id="tabRetail" role="tabpanel">
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" class="btn btn-success btn-sm" id="btnExportarRetail">
                                    <i class="ri-download-2-line align-bottom me-1"></i> Exportar lo filtrado (formato plantilla)
                                </button>
                            </div>
                            <table id="tableRetail" class="table table-bordered table-striped table-hover align-middle w-100">
                                <thead>
                                    <tr>
                                        <th>Mes retail</th>
                                        <th>Distribuidor</th>
                                        <th>VIN</th>
                                        <th>Modelo</th>
                                        <th>Modelo gen</th>
                                        <th>Año</th>
                                        <th>Estatus</th>
                                        <th>Fecha fact.</th>
                                        <th>Cliente final</th>
                                        <th>Tipo de pago</th>
                                        <th>Vendedor</th>
                                        <th></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <!-- ============ RESUMEN ============ -->
                        <div class="tab-pane fade" id="tabResumen" role="tabpanel">
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-2 col-6">
                                    <label class="form-label" for="rAnio">Año</label>
                                    <select class="form-select" id="rAnio"></select>
                                </div>
                                <div class="col-md-3 col-6">
                                    <label class="form-label" for="rAgrupar">Agrupar por</label>
                                    <select class="form-select" id="rAgrupar">
                                        <option value="distribuidor">Distribuidor</option>
                                        <option value="modelo_gen">Modelo gen</option>
                                    </select>
                                </div>
                                <div class="col-md-7 text-muted small">
                                    Unidades retail por MES RETAIL del año elegido. Respeta los filtros de arriba (menos el rango de meses).
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="tableResumen" class="table table-sm table-bordered table-hover align-middle text-end mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start" id="thGrupo">Distribuidor</th>
                                            <th>Ene</th><th>Feb</th><th>Mar</th><th>Abr</th><th>May</th><th>Jun</th>
                                            <th>Jul</th><th>Ago</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dic</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot class="table-light fw-semibold"></tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- ============ CARGAS ============ -->
                        <div class="tab-pane fade" id="tabCargas" role="tabpanel">
                            <p class="text-muted small mb-2">
                                Cada archivo reemplaza los meses que trae. <strong>Vigentes</strong> = renglones de esa carga que siguen en la tabla
                                (una carga más nueva con los mismos meses los reemplaza).
                            </p>
                            <table id="tableCargas" class="table table-bordered table-striped table-hover align-middle w-100">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Archivo</th>
                                        <th>Meses</th>
                                        <th>Filas archivo</th>
                                        <th>Cargadas</th>
                                        <th>No cargadas</th>
                                        <th>Reemplazó</th>
                                        <th>Vigentes</th>
                                        <th>Observaciones</th>
                                        <th>Subido</th>
                                        <th>Usuario</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <!-- ============ SUBIR ARCHIVO ============ -->
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <div class="tab-pane fade" id="tabCarga" role="tabpanel">
                                <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                                    <div>
                                        <i class="ri-file-excel-2-line align-bottom me-1"></i>
                                        <strong>Solo se acepta la plantilla de este módulo.</strong> Descárgala, captura o pega tus datos en la hoja <strong>Retail</strong> y súbela.
                                    </div>
                                    <button type="button" id="btnDescargarPlantilla" class="btn btn-success btn-sm">
                                        <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                                    </button>
                                </div>

                                <ul class="text-muted mb-3">
                                    <li>Obligatorias por fila (encabezado verde): <strong>DISTRIBUIDOR</strong>, <strong>VIN</strong> y <strong>MES RETAIL</strong> (día 1 del mes de la venta).</li>
                                    <li>La plantilla es de <strong>captura libre</strong>: no tiene listas ni restricciones en las celdas.</li>
                                    <li>Para corregir datos ya cargados: <strong>Exportar</strong> en la pestaña Retail descarga lo filtrado en este mismo formato; corrígelo y vuelve a subirlo.</li>
                                    <li>El archivo es <strong>acumulado</strong>: los meses que trae <strong>reemplazan</strong> a los que ya estaban; los meses que no vienen se conservan.</li>
                                    <li>Los datos se guardan <strong>tal cual</strong>: no se validan contra las unidades/VINs del MRP ni contra el catálogo de clientes.</li>
                                    <li>Las filas que no se puedan cargar y los avisos (VIN repetido en el mismo mes, fechas no reconocidas) quedan en un log descargable.</li>
                                </ul>

                                <form id="formCargaRetail" autocomplete="off">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-5">
                                            <label class="form-label" for="archivoRetail">PLANTILLA LLENA (.xlsx) <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="archivoRetail" name="archivo" accept=".xlsx,.xls" required>
                                        </div>
                                        <div class="col-lg-4">
                                            <label class="form-label" for="observacionesCarga">OBSERVACIONES</label>
                                            <input type="text" class="form-control" id="observacionesCarga" name="observaciones" maxlength="255" placeholder="Ej. Retail septiembre 2026">
                                        </div>
                                        <div class="col-lg-3">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="ri-upload-2-line align-bottom me-1"></i> Subir retail
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div id="resultadoCarga" class="mt-3" style="display:none;">
                                    <div class="alert" id="alertResultadoCarga" role="alert"></div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCarga" style="display:none;">
                                        <i class="ri-file-download-line align-bottom me-1"></i> Descargar log
                                    </button>
                                </div>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal detalle de un renglón -->
    <div class="modal fade" id="modalDetalleRetail" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle retail <span id="tituloDetalle" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered mb-0"><tbody id="bodyDetalle"></tbody></table>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">LDR Solutions · MRP</div>
                </div>
            </div>
        </div>
    </footer>
</div>
<script>
    const puedeEliminarCarga = <?= !empty($_SESSION['permisosMod']['d']) ? 'true' : 'false' ?>;
</script>
<?php footerAdmin($data); ?>
