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

            <?php if (empty($data['almacen'])) { ?>
                <div class="alert alert-danger" role="alert">
                    <i class="ri-error-warning-line align-bottom me-1"></i>
                    No existe el almacén <strong>ALM-DISTRIBUIDORES</strong>. Corre el script
                    <code>Docs/desarrollo/inventario_distribuidores/inv_distribuidores.sql</code> antes de usar este módulo.
                </div>
            <?php } ?>

            <!-- KPIs -->
            <div class="row" id="kpisDistribuidores">
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Distribuidores con corte</p>
                        <h4 class="mb-0" id="kpiDistribuidores">0</h4>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Disponibles en distribuidores</p>
                        <h4 class="mb-0 text-success" id="kpiDisponibles">0</h4>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Facturados por distribuidores</p>
                        <h4 class="mb-0 text-primary" id="kpiFacturadosDist">0</h4>
                    </div></div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="card card-animate"><div class="card-body">
                        <p class="text-uppercase fw-medium text-muted mb-1">Vendidos por LDR sin reportar</p>
                        <h4 class="mb-0 text-danger" id="kpiNoReportados">0</h4>
                    </div></div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabInventario" type="button" role="tab">
                                <i class="ri-car-line align-bottom me-1"></i> Inventario vigente
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabConciliacion" type="button" role="tab">
                                <i class="ri-scales-3-line align-bottom me-1"></i> Conciliación
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCortes" type="button" role="tab">
                                <i class="ri-history-line align-bottom me-1"></i> Cortes (historial)
                            </button>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCarga" type="button" role="tab">
                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Carga masiva
                                </button>
                            </li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <!-- ============ INVENTARIO VIGENTE ============ -->
                        <div class="tab-pane fade show active" id="tabInventario" role="tabpanel">
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-md-5">
                                    <label class="form-label" for="filtroDistribuidor">Distribuidor</label>
                                    <select class="form-select" id="filtroDistribuidor">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label" for="filtroEstatus">Estatus</label>
                                    <select class="form-select" id="filtroEstatus">
                                        <option value="">Todos</option>
                                        <option value="Disponible">Disponible</option>
                                        <option value="Facturado">Facturado</option>
                                    </select>
                                </div>
                                <div class="col-md-5 text-muted small">
                                    Se muestra el <strong>último corte</strong> de cada distribuidor.
                                    <span class="badge bg-success">Disponible</span> = en su piso;
                                    <span class="badge bg-primary">Facturado</span> = ya lo vendió a su cliente final.
                                </div>
                            </div>
                            <table id="tableInventario" class="table table-bordered table-striped table-hover align-middle w-100">
                                <thead>
                                    <tr>
                                        <th>Distribuidor</th>
                                        <th>VIN</th>
                                        <th>Estatus</th>
                                        <th>SKU</th>
                                        <th>Modelo</th>
                                        <th>Motor</th>
                                        <th>Color</th>
                                        <th>Factura</th>
                                        <th>Corte</th>
                                        <th>Fecha corte</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <!-- ============ CONCILIACIÓN ============ -->
                        <div class="tab-pane fade" id="tabConciliacion" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                <div class="text-muted small">
                                    Compara las unidades que <strong>LDR vendió</strong> a cada distribuidor contra lo que reportó en su corte vigente (disponibles y facturadas a su cliente final).
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="btnExportarConciliacion">
                                    <i class="ri-download-2-line align-bottom me-1"></i> Descargar conciliación (Excel)
                                </button>
                            </div>
                            <table id="tableConciliacion" class="table table-bordered table-striped table-hover align-middle w-100">
                                <thead>
                                    <tr>
                                        <th>Distribuidor</th>
                                        <th>Corte vigente</th>
                                        <th>Fecha corte</th>
                                        <th>Vendidos por LDR</th>
                                        <th>Reportados</th>
                                        <th>Disponibles</th>
                                        <th>Facturados por distribuidor</th>
                                        <th>Sin reportar</th>
                                        <th>Ya no vendidos a él</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <!-- ============ CORTES ============ -->
                        <div class="tab-pane fade" id="tabCortes" role="tabpanel">
                            <table id="tableCortes" class="table table-bordered table-striped table-hover align-middle w-100">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Distribuidor</th>
                                        <th>Fecha corte</th>
                                        <th>VINs</th>
                                        <th>Observaciones</th>
                                        <th>Archivo</th>
                                        <th>Subido</th>
                                        <th>Usuario</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>

                        <!-- ============ CARGA MASIVA ============ -->
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <div class="tab-pane fade" id="tabCarga" role="tabpanel">
                                <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                                    <div>
                                        <i class="ri-file-excel-2-line align-bottom me-1"></i>
                                        La plantilla ya trae las <strong>unidades vendidas a cada distribuidor</strong> (o solo las del distribuidor que elijas abajo). Marca el ESTATUS de cada una (Disponible o Facturado) y súbela con la fecha de corte.
                                    </div>
                                    <button type="button" id="btnDescargarPlantilla" class="btn btn-success btn-sm">
                                        <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                                    </button>
                                </div>

                                <ul class="text-muted mb-3">
                                    <li>Solo se aceptan VINs que <strong>existen</strong> en el sistema, están <span class="badge bg-secondary">3 - Facturado</span> y cuya última venta fue <strong>a ese mismo distribuidor</strong>.</li>
                                    <li>Columna <strong>ESTATUS</strong> por VIN: <span class="badge bg-success">Disponible</span> = sigue en el piso del distribuidor; <span class="badge bg-primary">Facturado</span> = ya lo vendió a su cliente final.</li>
                                    <li>Cada distribuidor del archivo genera <strong>su propio corte</strong>. El último corte es su inventario vigente; los anteriores quedan como historial.</li>
                                    <li>Si eliges un distribuidor abajo, la plantilla sale solo con sus unidades y al subir se usa para las filas con la columna DISTRIBUIDOR vacía.</li>
                                    <li>Este módulo <strong>no mueve existencias</strong> del MRP: es el conteo reportado por cada distribuidor.</li>
                                </ul>

                                <form id="formCargaDistribuidores" autocomplete="off">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-3 col-sm-6">
                                            <label class="form-label" for="fechaCorte">FECHA DE CORTE <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" id="fechaCorte" name="fecha_corte" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required>
                                        </div>
                                        <div class="col-lg-4 col-sm-6">
                                            <label class="form-label" for="clienteCarga">DISTRIBUIDOR (opcional)</label>
                                            <select class="form-select" id="clienteCarga" name="clienteid">
                                                <option value="">— Tomar del archivo —</option>
                                            </select>
                                        </div>
                                        <div class="col-lg-5">
                                            <label class="form-label" for="observacionesCarga">OBSERVACIONES</label>
                                            <input type="text" class="form-control" id="observacionesCarga" name="observaciones" maxlength="255">
                                        </div>
                                        <div class="col-lg-6 col-sm-8">
                                            <label class="form-label" for="archivoDistribuidores">ARCHIVO (.xlsx) <span class="text-danger">*</span></label>
                                            <input type="file" class="form-control" id="archivoDistribuidores" name="archivo" accept=".xlsx,.xls" required>
                                        </div>
                                        <div class="col-lg-3 col-sm-4">
                                            <button type="submit" class="btn btn-success w-100">
                                                <i class="ri-upload-2-line align-bottom me-1"></i> Subir inventario
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <div id="resultadoCarga" class="mt-3" style="display:none;">
                                    <div class="alert" id="alertResultadoCarga" role="alert"></div>
                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCarga" style="display:none;">
                                        <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de lo que no se subió
                                    </button>
                                </div>
                            </div>
                        <?php } ?>

                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Modal detalle de corte -->
    <div class="modal fade" id="modalDetalleCorte" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle del corte <span id="tituloCorte"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <table id="tableDetalleCorte" class="table table-sm table-bordered table-striped align-middle w-100">
                        <thead>
                            <tr>
                                <th>VIN</th>
                                <th>SKU</th>
                                <th>Modelo</th>
                                <th>Motor</th>
                                <th>Estatus</th>
                                <th>Ubicación</th>
                                <th>Observaciones</th>
                            </tr>
                        </thead>
                    </table>
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
    const puedeCancelarCorte = <?= !empty($_SESSION['permisosMod']['d']) ? 'true' : 'false' ?>;
</script>
<?php footerAdmin($data); ?>
