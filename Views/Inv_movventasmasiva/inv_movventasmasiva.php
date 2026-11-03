<?php headerAdmin($data);
$puedeEscribir = !empty($_SESSION['permisosMod']['w']);
?>
<style>
    /* ===== Carga masiva de ventas / facturadas (prefijo mvm-) ===== */
    .mvm-kpi .avatar-title { font-size: 1.5rem; }
    .mvm-kpi h3 { font-size: 1.6rem; }
    .mvm-tabs .nav-link { font-weight: 600; letter-spacing: .2px; padding: .9rem 1.1rem; }
    .mvm-tabs .nav-link i { font-size: 1.05rem; vertical-align: -2px; }
    .mvm-tabs .nav-link .badge { font-size: .7rem; vertical-align: 1px; }

    .mvm-step { display: flex; gap: 1rem; padding: 1.1rem 0; border-bottom: 1px dashed var(--vz-border-color); }
    .mvm-step:first-child { padding-top: 0; }
    .mvm-step:last-child { border-bottom: 0; padding-bottom: 0; }
    .mvm-step-num {
        flex: 0 0 2.25rem; width: 2.25rem; height: 2.25rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700; font-size: .95rem;
        background: var(--vz-primary-bg-subtle); color: var(--vz-primary);
    }
    .mvm-step.mvm-warn .mvm-step-num { background: var(--vz-warning-bg-subtle); color: var(--vz-warning-text-emphasis); }
    .mvm-step-body { flex: 1 1 auto; min-width: 0; }
    .mvm-step-body h6 { margin-bottom: .25rem; font-weight: 600; }

    .mvm-drop {
        position: relative; border: 2px dashed var(--vz-gray-400); border-radius: .5rem;
        padding: 1.4rem 1rem; text-align: center; transition: border-color .15s, background-color .15s;
        background: var(--vz-light-bg-subtle);
    }
    .mvm-drop:hover, .mvm-drop.mvm-over { border-color: var(--vz-primary); background: var(--vz-primary-bg-subtle); }
    .mvm-drop.mvm-has-file { border-style: solid; border-color: var(--vz-success); background: var(--vz-success-bg-subtle); }
    .mvm-drop input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .mvm-drop i { font-size: 2rem; line-height: 1; }
    .mvm-drop .mvm-file-name { font-weight: 600; word-break: break-all; }

    .mvm-rules { list-style: none; padding: 0; margin: 0; }
    .mvm-rules li { display: flex; gap: .6rem; padding: .5rem 0; font-size: .85rem; color: var(--vz-secondary-color); }
    .mvm-rules li + li { border-top: 1px solid var(--vz-border-color-translucent); }
    .mvm-rules li i { flex: 0 0 auto; font-size: 1rem; line-height: 1.25rem; }
    .mvm-rules strong { color: var(--vz-body-color); }

    .mvm-filters { background: var(--vz-light-bg-subtle); border: 1px solid var(--vz-border-color); border-radius: .5rem; padding: .9rem 1rem; }
    .mvm-filters .btn-group .btn { white-space: nowrap; }
    #tableFacturadas td.mvm-vin { font-family: var(--vz-font-monospace); font-size: .82rem; }
</style>

<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1"><?= $data['page_title'] ?></h4>
                            <p class="text-muted mb-0 fs-13">Facturación, devoluciones y consulta de unidades vendidas</p>
                        </div>

                        <div class="page-title-right d-flex align-items-center gap-2 mt-2 mt-sm-0">
                            <a href="<?= base_url() ?>/Inv_movimientosinventario" class="btn btn-soft-secondary btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Regresar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <!-- ===================== INDICADORES ===================== -->
            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate mvm-kpi">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-semibold text-muted text-truncate mb-2 fs-12">Facturadas (histórico)</p>
                                    <h3 class="fw-semibold mb-1" id="kpiFacturadas">—</h3>
                                    <span class="text-muted fs-12">VINs en estatus Facturado</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-3">
                                        <i class="ri-file-list-3-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate mvm-kpi">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-semibold text-muted text-truncate mb-2 fs-12">Facturadas este mes</p>
                                    <h3 class="fw-semibold mb-1" id="kpiFacturadasMes">—</h3>
                                    <span class="text-muted fs-12" id="kpiMesNombre">Mes en curso</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-info-subtle text-info rounded-3">
                                        <i class="ri-calendar-check-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate mvm-kpi">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-semibold text-muted text-truncate mb-2 fs-12">VIN disponibles</p>
                                    <h3 class="fw-semibold mb-1" id="kpiDisponibles">—</h3>
                                    <span class="text-muted fs-12">Listas para facturar</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-3">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate mvm-kpi">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-semibold text-muted text-truncate mb-2 fs-12">Unidades en inventario</p>
                                    <h3 class="fw-semibold mb-1" id="kpiTatuadas">—</h3>
                                    <span class="text-muted fs-12">También se pueden facturar</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle text-danger rounded-3">
                                        <i class="ri-fingerprint-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== PESTAÑAS ===================== -->
            <div class="card">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0 mvm-tabs" role="tablist" id="mvmTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tabFacturadas" role="tab">
                                <i class="ri-car-line me-1"></i> Unidades facturadas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tabFacturar" role="tab">
                                <i class="ri-bill-line me-1"></i> Facturar unidades
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tabDevoluciones" role="tab">
                                <i class="ri-arrow-go-back-line me-1"></i> Devoluciones
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <!-- ============ TAB 1: UNIDADES FACTURADAS ============ -->
                        <div class="tab-pane active" id="tabFacturadas" role="tabpanel">

                            <div class="mvm-filters mb-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="factDesde">Venta desde</label>
                                        <input type="date" class="form-control form-control-sm" id="factDesde">
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="factHasta">Venta hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="factHasta">
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <label class="form-label fs-12 text-muted mb-1" for="factDistribuidor">Distribuidor</label>
                                        <select class="form-select form-select-sm" id="factDistribuidor">
                                            <option value="">Todos los distribuidores</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-lg-3">
                                        <label class="form-label fs-12 text-muted mb-1" for="factModelo">Modelo</label>
                                        <select class="form-select form-select-sm" id="factModelo">
                                            <option value="">Todos los modelos</option>
                                        </select>
                                    </div>
                                    <div class="col-lg-2 d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnFiltrarFacturadas">
                                            <i class="ri-search-line align-bottom me-1"></i> Consultar
                                        </button>
                                        <button type="button" class="btn btn-soft-secondary btn-sm" id="btnLimpiarFacturadas" title="Limpiar filtros">
                                            <i class="ri-refresh-line align-bottom"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                    <span class="fs-12 text-muted">Periodo rápido:</span>
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Periodo rápido" id="factRapidos">
                                        <button type="button" class="btn btn-outline-primary" data-rango="mes">Este mes</button>
                                        <button type="button" class="btn btn-outline-primary" data-rango="mesant">Mes anterior</button>
                                        <button type="button" class="btn btn-outline-primary" data-rango="30">Últimos 30 días</button>
                                        <button type="button" class="btn btn-outline-primary" data-rango="todo">Todo</button>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3" id="factResumen">
                                <span class="badge bg-primary-subtle text-primary fs-12 px-3 py-2"><i class="ri-car-line me-1"></i><span id="factTotUnidades">0</span> unidades</span>
                                <span class="badge bg-info-subtle text-info fs-12 px-3 py-2"><i class="ri-store-2-line me-1"></i><span id="factTotDistribuidores">0</span> distribuidores</span>
                                <span class="badge bg-secondary-subtle text-secondary fs-12 px-3 py-2"><i class="ri-calendar-line me-1"></i><span id="factPeriodo">Todo el histórico</span></span>
                            </div>

                            <table id="tableFacturadas" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>FECHA VENTA</th>
                                        <th>VIN</th>
                                        <th>MOTOR</th>
                                        <th>SKU</th>
                                        <th>UNIDAD</th>
                                        <th>DISTRIBUIDOR</th>
                                        <th>FACTURA</th>
                                        <th>ALMACÉN SALIDA</th>
                                        <th>FOLIO MOV.</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- ============ TAB 2: FACTURAR ============ -->
                        <div class="tab-pane" id="tabFacturar" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-7">
                                    <div class="mvm-step">
                                        <div class="mvm-step-num">1</div>
                                        <div class="mvm-step-body">
                                            <h6>Descarga el layout de VINs</h6>
                                            <p class="text-muted fs-13 mb-2">Trae el histórico general de VINs (Disponibles, Tatuados y Facturados) de todas las plantas y almacenes.</p>
                                            <button type="button" id="btnDescargarLayoutVentas" class="btn btn-success btn-sm">
                                                <i class="ri-file-excel-2-line align-bottom me-1"></i> Descargar layout de VINs
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mvm-step">
                                        <div class="mvm-step-num">2</div>
                                        <div class="mvm-step-body">
                                            <h6>Captura en Excel</h6>
                                            <p class="text-muted fs-13 mb-0">
                                                Cambia <strong>ESTATUS</strong> a <span class="badge bg-secondary">Facturado</span> y escribe el
                                                <strong>DISTRIBUIDOR</strong> (texto libre) y la <strong>FACTURA</strong>. Solo se editan las columnas en verde.
                                            </p>
                                        </div>
                                    </div>
                                    <div class="mvm-step">
                                        <div class="mvm-step-num">3</div>
                                        <div class="mvm-step-body">
                                            <h6>Sube el archivo</h6>
                                            <?php if ($puedeEscribir) { ?>
                                                <form id="formCargaVentas" autocomplete="off">
                                                    <div class="mvm-drop mb-3" data-default="Arrastra aquí el layout o haz clic para seleccionarlo">
                                                        <input type="file" id="archivoVentas" name="archivo" accept=".xlsx,.xls">
                                                        <i class="ri-upload-cloud-2-line text-primary"></i>
                                                        <div class="mvm-file-name mt-2">Arrastra aquí el layout o haz clic para seleccionarlo</div>
                                                        <div class="text-muted fs-12">Formato .xlsx</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary" id="btnSubirVentas">
                                                        <i class="ri-upload-2-line align-bottom me-1"></i> Subir y facturar
                                                    </button>
                                                </form>

                                                <div id="resultadoCargaVentas" class="mt-3" style="display:none;">
                                                    <div class="alert mb-2" id="alertResultadoCargaVentas" role="alert"></div>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCargaVentas" style="display:none;">
                                                        <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de lo que no se subió
                                                    </button>
                                                </div>
                                            <?php } else { ?>
                                                <div class="alert alert-light border mb-0 fs-13">
                                                    <i class="ri-lock-line me-1"></i> No tienes permisos para registrar ventas mediante carga masiva.
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="border rounded-3 p-3 h-100">
                                        <h6 class="fw-semibold mb-2"><i class="ri-information-line text-primary me-1"></i> Reglas de la carga</h6>
                                        <ul class="mvm-rules">
                                            <li><i class="ri-arrow-right-circle-line text-primary"></i><span>Se factura desde <span class="badge bg-success">Disponible</span> o <span class="badge bg-danger">Tatuado</span>.</span></li>
                                            <li><i class="ri-file-paper-2-line text-primary"></i><span><strong>Una unidad por factura:</strong> cada VIN genera su propio movimiento de Ventas y baja la existencia de su almacén.</span></li>
                                            <li><i class="ri-building-line text-primary"></i><span>Solo se venden unidades de almacenes de <strong>Producto Terminado</strong> o <strong>Tránsito</strong>.</span></li>
                                            <li><i class="ri-edit-2-line text-primary"></i><span>En VINs ya facturados puedes <strong>corregir</strong> el distribuidor o la factura (ventas de una unidad).</span></li>
                                            <li><i class="ri-error-warning-line text-warning"></i><span>Filas nuevas o con VIN, SKU, nombre, almacén o motor modificados <strong>no se suben</strong> y quedan en el log.</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ============ TAB 3: DEVOLUCIONES ============ -->
                        <div class="tab-pane" id="tabDevoluciones" role="tabpanel">
                            <div class="row g-4">
                                <div class="col-lg-7">
                                    <div class="mvm-step mvm-warn">
                                        <div class="mvm-step-num">1</div>
                                        <div class="mvm-step-body">
                                            <h6>Descarga los VINs facturados</h6>
                                            <p class="text-muted fs-13 mb-2">Opcional: acota por fecha de venta.</p>
                                            <div class="row g-2 align-items-end">
                                                <div class="col-sm-4">
                                                    <label class="form-label fs-12 text-muted mb-1" for="devDesde">Ventas desde</label>
                                                    <input type="date" class="form-control form-control-sm" id="devDesde">
                                                </div>
                                                <div class="col-sm-4">
                                                    <label class="form-label fs-12 text-muted mb-1" for="devHasta">Ventas hasta</label>
                                                    <input type="date" class="form-control form-control-sm" id="devHasta">
                                                </div>
                                                <div class="col-sm-4">
                                                    <button type="button" id="btnDescargarLayoutDev" class="btn btn-success btn-sm w-100">
                                                        <i class="ri-file-excel-2-line align-bottom me-1"></i> Descargar
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mvm-step mvm-warn">
                                        <div class="mvm-step-num">2</div>
                                        <div class="mvm-step-body">
                                            <h6>Marca las devoluciones</h6>
                                            <p class="text-muted fs-13 mb-0">En los VINs a devolver cambia <strong>ESTATUS</strong> a <strong>DEVOLUCION</strong>. No modifiques las demás columnas.</p>
                                        </div>
                                    </div>
                                    <div class="mvm-step mvm-warn">
                                        <div class="mvm-step-num">3</div>
                                        <div class="mvm-step-body">
                                            <h6>Sube el archivo</h6>
                                            <?php if ($puedeEscribir) { ?>
                                                <form id="formCargaDevoluciones" autocomplete="off">
                                                    <div class="mvm-drop mb-3" data-default="Arrastra aquí el archivo de devoluciones o haz clic para seleccionarlo">
                                                        <input type="file" id="archivoDevoluciones" name="archivo" accept=".xlsx,.xls">
                                                        <i class="ri-upload-cloud-2-line text-warning"></i>
                                                        <div class="mvm-file-name mt-2">Arrastra aquí el archivo de devoluciones o haz clic para seleccionarlo</div>
                                                        <div class="text-muted fs-12">Formato .xlsx</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-warning">
                                                        <i class="ri-upload-2-line align-bottom me-1"></i> Subir devoluciones
                                                    </button>
                                                </form>

                                                <div id="resultadoCargaDevoluciones" class="mt-3" style="display:none;">
                                                    <div class="alert mb-2" id="alertResultadoCargaDevoluciones" role="alert"></div>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCargaDevoluciones" style="display:none;">
                                                        <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de lo que no se subió
                                                    </button>
                                                </div>
                                            <?php } else { ?>
                                                <div class="alert alert-light border mb-0 fs-13">
                                                    <i class="ri-lock-line me-1"></i> No tienes permisos para registrar devoluciones.
                                                </div>
                                            <?php } ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-5">
                                    <div class="border rounded-3 p-3 h-100">
                                        <h6 class="fw-semibold mb-2"><i class="ri-information-line text-warning me-1"></i> Qué pasa al devolver</h6>
                                        <ul class="mvm-rules">
                                            <li><i class="ri-home-4-line text-warning"></i><span>La unidad regresa al <strong>mismo almacén</strong> del que salió.</span></li>
                                            <li><i class="ri-exchange-line text-warning"></i><span>Cada VIN genera un movimiento <strong>Devol. de venta</strong> (suma 1 en multialmacén).</span></li>
                                            <li><i class="ri-checkbox-circle-line text-success"></i><span>El VIN queda en <span class="badge bg-success">Disponible</span>, listo para volver a venderse.</span></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
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
