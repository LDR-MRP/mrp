<?php headerAdmin($data); ?>
<?php
$puedeSubir = !empty($_SESSION['permisosMod']['w']);
$almTransito = $data['almacen_transito'] ?? null;
?>
<style>
    /* ---- Order delivery plan ---- */
    .odp-hero { position: relative; overflow: hidden; border-radius: .75rem; padding: 1.5rem 1.75rem; color: #fff;
        background: linear-gradient(120deg, #3b2a6b 0%, #5a46a8 55%, #7b6ad8 100%); box-shadow: 0 6px 18px rgba(59, 42, 107, .18); }
    .odp-hero::before, .odp-hero::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .07); pointer-events: none; }
    .odp-hero::before { width: 260px; height: 260px; right: -60px; top: -120px; }
    .odp-hero::after { width: 160px; height: 160px; right: 180px; bottom: -110px; }
    .odp-hero-icono { width: 58px; height: 58px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .25); }
    .odp-hero-titulo { color: #fff; font-weight: 600; font-size: 1.45rem; }
    .odp-hero-desc { color: rgba(255, 255, 255, .82); font-size: .9rem; }
    .odp-hero-meta { display: inline-block; font-size: .8rem; color: #fff; background: rgba(0, 0, 0, .15); border-radius: 50rem; padding: .25rem .75rem; }
    .odp-hero-btn { color: #fff; border: 1px solid rgba(255, 255, 255, .5); background: transparent; }
    .odp-hero-btn:hover { color: #fff; background: rgba(255, 255, 255, .15); }
    .odp-kpi { border-top: 3px solid transparent; }
    .odp-kpi .avatar-title { font-size: 1.4rem; }
    .odp-kpi.k-primary { border-top-color: rgb(var(--vz-primary-rgb)); }
    .odp-kpi.k-info { border-top-color: #299cdb; }
    .odp-kpi.k-success { border-top-color: #0ab39c; }
    .odp-kpi.k-warning { border-top-color: #f7b84b; }
    .odp-kpi-valor { font-size: 1.55rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .odp-kpi-sub { font-size: .78rem; }
    .odp-mono { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .8rem; }
    .odp-nowrap { white-space: nowrap; }
    .odp-trunc { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; }
    .odp-resumen td, .odp-resumen th { font-variant-numeric: tabular-nums; }
    .odp-resumen th:first-child, .odp-resumen td:first-child { position: sticky; left: 0; z-index: 1; background-color: var(--vz-secondary-bg); }
    .odp-resumen thead th:first-child, .odp-resumen tfoot td:first-child { background-color: var(--vz-tertiary-bg); }
    .odp-paso { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: .85rem; flex-shrink: 0; }
    .odp-drop { position: relative; border: 2px dashed var(--vz-border-color); border-radius: .5rem; padding: 1.5rem 1rem; text-align: center; transition: border-color .15s, background-color .15s; }
    .odp-drop.is-over, .odp-drop:hover { border-color: rgb(var(--vz-primary-rgb)); background-color: rgba(var(--vz-primary-rgb), .04); }
    .odp-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .odp-drop.tiene-archivo { border-style: solid; border-color: rgb(var(--vz-success-rgb, 10,179,156)); }
</style>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

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

            <!-- ENCABEZADO -->
            <div class="odp-hero mb-3">
                <div class="d-flex flex-wrap align-items-center gap-3 position-relative">
                    <div class="odp-hero-icono flex-shrink-0"><i class="ri-ship-2-line"></i></div>
                    <div class="flex-grow-1" style="min-width:240px">
                        <h3 class="odp-hero-titulo mb-1">Order delivery plan</h3>
                        <p class="odp-hero-desc mb-2">
                            Unidades en tránsito por contrato, con su producto del inventario. Cada carga se suma a las anteriores
                            y queda en <strong><?= htmlspecialchars($almTransito['cve_almacen'] ?? 'ALM-TRANSITO') ?></strong>; no mueve existencias.
                        </p>
                        <div class="odp-hero-meta" id="heroMeta"><i class="ri-time-line align-bottom me-1"></i> Cargando última carga…</div>
                    </div>
                    <?php if ($puedeSubir) { ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm odp-hero-btn" id="heroPlantilla">
                                <i class="ri-download-2-line align-bottom me-1"></i> Plantilla
                            </button>
                            <button type="button" class="btn btn-sm btn-light" id="heroSubir">
                                <i class="ri-upload-cloud-2-line align-bottom me-1"></i> Subir archivo
                            </button>
                        </div>
                    <?php } ?>
                </div>
            </div>

            <?php if (empty($data['tablas_listas'])) { ?>
                <div class="alert alert-danger" role="alert">
                    <i class="ri-error-warning-line align-bottom me-1"></i>
                    Faltan las tablas del módulo. Corre el script <code><?= htmlspecialchars($data['script']) ?></code>.
                </div>
            <?php } elseif (empty($almTransito)) { ?>
                <div class="alert alert-warning" role="alert">
                    <i class="ri-error-warning-line align-bottom me-1"></i>
                    No existe el almacén de tránsito (ALM-TRANSITO); no se podrán subir archivos hasta darlo de alta.
                </div>
            <?php } ?>

            <!-- FILTROS -->
            <div class="card">
                <div class="card-header d-flex align-items-center py-2">
                    <h6 class="card-title mb-0 flex-grow-1">
                        <i class="ri-filter-3-line align-bottom me-1 text-muted"></i> Filtros
                        <span class="badge bg-primary ms-1 d-none" id="badgeFiltros">0</span>
                    </h6>
                    <button type="button" class="btn btn-sm btn-ghost-secondary" id="btnLimpiarFiltros">
                        <i class="ri-filter-off-line align-bottom me-1"></i> Limpiar
                    </button>
                </div>
                <div class="card-body">
                    <div class="row g-2 align-items-end">
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label small text-muted mb-1" for="fMes">Mes de carga</label>
                            <select class="form-select form-select-sm" id="fMes"></select>
                        </div>
                        <div class="col-xl-6 col-md-4">
                            <label class="form-label small text-muted mb-1" for="fProducto">Producto</label>
                            <select class="form-select form-select-sm" id="fProducto"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-4 col-md-4">
                            <label class="form-label small text-muted mb-1" for="fStatus">Status</label>
                            <select class="form-select form-select-sm" id="fStatus"><option value="">Todos</option></select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="row">
                <?php
                $kpis = [
                    ['id' => 1, 'color' => 'primary', 'icono' => 'ri-truck-line', 'titulo' => 'Unidades en tránsito'],
                    ['id' => 2, 'color' => 'info', 'icono' => 'ri-file-list-3-line', 'titulo' => 'Contratos'],
                    ['id' => 3, 'color' => 'success', 'icono' => 'ri-car-line', 'titulo' => 'Productos'],
                    ['id' => 4, 'color' => 'warning', 'icono' => 'ri-anchor-line', 'titulo' => 'Con ETA at POD'],
                ];
                foreach ($kpis as $k) { ?>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-animate odp-kpi k-<?= $k['color'] ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="overflow-hidden me-2">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-2"><?= $k['titulo'] ?></p>
                                        <div class="odp-kpi-valor text-body mb-1" id="kpi<?= $k['id'] ?>Valor">—</div>
                                        <div class="odp-kpi-sub text-muted text-truncate" id="kpi<?= $k['id'] ?>Sub">&nbsp;</div>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-<?= $k['color'] ?>-subtle text-<?= $k['color'] ?> rounded-circle">
                                            <i class="<?= $k['icono'] ?>"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <div class="card">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabListado" type="button" role="tab">
                                <i class="ri-list-check-2 align-bottom me-1"></i> Plan
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabResumen" type="button" role="tab">
                                <i class="ri-grid-line align-bottom me-1"></i> Resumen por producto
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCargas" type="button" role="tab">
                                <i class="ri-history-line align-bottom me-1"></i> Historial de cargas
                            </button>
                        </li>
                        <?php if ($puedeSubir) { ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCarga" type="button" role="tab">
                                    <i class="ri-upload-cloud-2-line align-bottom me-1"></i> Subir archivo
                                </button>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <!-- ============ LISTADO ============ -->
                        <div class="tab-pane fade show active" id="tabListado" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-muted small"><i class="ri-information-line align-bottom"></i> Un renglón por renglón del archivo, en el mismo orden.</span>
                                <button type="button" class="btn btn-soft-success btn-sm" id="btnExportar">
                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Exportar lo filtrado
                                </button>
                            </div>
                            <table id="tablePlan" class="table table-hover table-nowrap align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th>Modelo</th>
                                        <th>Contract No.</th>
                                        <th class="text-end">QTY</th>
                                        <th>ETA at POD</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- ============ RESUMEN ============ -->
                        <div class="tab-pane fade" id="tabResumen" role="tabpanel">
                            <p class="text-muted small mb-3">Unidades por producto y status. Respeta los filtros de arriba.</p>
                            <div class="table-responsive">
                                <table id="tableResumen" class="table table-sm table-bordered align-middle text-end mb-0 odp-resumen">
                                    <thead class="table-light"></thead>
                                    <tbody></tbody>
                                    <tfoot class="table-light fw-semibold"></tfoot>
                                </table>
                            </div>
                        </div>
                        <!-- ============ CARGAS ============ -->
                        <div class="tab-pane fade" id="tabCargas" role="tabpanel">
                            <div class="alert alert-light border small py-2 mb-3" role="alert">
                                <i class="ri-information-line align-bottom me-1"></i>
                                Las cargas <strong>se suman</strong>: cada archivo agrega sus renglones a los anteriores. Para deshacer una carga equivocada, elimínala aquí
                                (se borran solo sus renglones).
                            </div>
                            <table id="tableCargas" class="table table-hover table-nowrap align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Folio</th>
                                        <th>Mes</th>
                                        <th>Archivo</th>
                                        <th>Filas</th>
                                        <th>Cargadas</th>
                                        <th>No cargadas</th>
                                        <th>Unidades</th>
                                        <th>Observaciones</th>
                                        <th>Subido</th>
                                        <th>Usuario</th>
                                        <th>Estado</th>
                                        <th></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                        <!-- ============ SUBIR ARCHIVO ============ -->
                        <?php if ($puedeSubir) { ?>
                            <div class="tab-pane fade" id="tabCarga" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-lg-7">
                                        <div class="d-flex gap-3 mb-4">
                                            <span class="odp-paso bg-primary-subtle text-primary">1</span>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Descarga la plantilla</h6>
                                                <p class="text-muted small mb-2">Trae la lista de productos (tipo P) con SKU y nombre comercial de Ingeniería.</p>
                                                <button type="button" id="btnDescargarPlantilla" class="btn btn-soft-primary btn-sm">
                                                    <i class="ri-download-2-line align-bottom me-1"></i> Plantilla Order Delivery Plan
                                                </button>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-3 mb-4">
                                            <span class="odp-paso bg-primary-subtle text-primary">2</span>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Llena la plantilla</h6>
                                                <p class="text-muted small mb-0">
                                                    Elige el <strong>PRODUCTO</strong> de la lista (columna A) y copia del <strong>Current order</strong> las columnas
                                                    <strong>Modelo (texto libre), Contract No., QTY, ETA at POD y Status</strong>. Obligatorias: PRODUCTO y QTY.
                                                </p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-3">
                                            <span class="odp-paso bg-primary-subtle text-primary">3</span>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">Sube el archivo</h6>
                                                <form id="formCarga" autocomplete="off">
                                                    <div class="odp-drop mb-3" id="dropCarga">
                                                        <input type="file" id="archivoCarga" name="archivo" accept=".xlsx,.xls" required aria-label="Plantilla llena (.xlsx)">
                                                        <i class="ri-upload-cloud-2-line display-6 text-muted d-block mb-1" id="dropIcono"></i>
                                                        <div class="fw-medium" id="dropTexto">Arrastra aquí la plantilla llena o haz clic para elegirla</div>
                                                        <div class="text-muted small" id="dropDetalle">Excel .xlsx · máximo 20 MB</div>
                                                    </div>
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-sm-8">
                                                            <label class="form-label small text-muted mb-1" for="observacionesCarga">Observaciones (opcional)</label>
                                                            <input type="text" class="form-control form-control-sm" id="observacionesCarga" name="observaciones" maxlength="255" placeholder="Ej. Order Delivery plan MX 2026-11-2">
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <button type="submit" class="btn btn-success btn-sm w-100" <?= empty($almTransito) ? 'disabled' : '' ?>>
                                                                <i class="ri-upload-2-line align-bottom me-1"></i> Subir plan
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                                <div id="resultadoCarga" class="mt-3" style="display:none;">
                                                    <div class="alert mb-2" id="alertResultadoCarga" role="alert"></div>
                                                    <button type="button" class="btn btn-soft-danger btn-sm" id="btnLogCarga" style="display:none;">
                                                        <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de la carga
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="border rounded p-3 h-100">
                                            <h6 class="mb-2"><i class="ri-book-open-line align-bottom me-1 text-muted"></i> Cómo funciona la carga</h6>
                                            <ul class="text-muted small ps-3 mb-0">
                                                <li class="mb-1">Las cargas <strong>se suman</strong>: cada archivo agrega sus renglones a lo ya cargado. Sube solo lo nuevo. Cada renglón guarda el mes en que se subió (<span id="mesActualTxt"></span>) para filtrar.</li>
                                                <li class="mb-1">Cada renglón queda en el almacén de tránsito (<?= htmlspecialchars($almTransito['cve_almacen'] ?? 'ALM-TRANSITO') ?>). <strong>No</strong> da entrada de existencias ni genera VINs.</li>
                                                <li class="mb-1">Contrato partido en varios renglones: si Contract No. viene con celdas combinadas (como en el Current order), los renglones de abajo toman el contrato y, si van vacíos, el Modelo y el PRODUCTO del primero.</li>
                                                <li class="mb-1">Los renglones de subtotal (solo QTY) se ignoran solos.</li>
                                                <li class="mb-1">ETA at POD como TBD se guarda como texto.</li>
                                                <li class="mb-1">Si un renglón es igual a uno ya cargado (producto, contrato, QTY y ETA) verás un aviso de posible duplicado en el log.</li>
                                                <li>Para corregir una carga equivocada: elimínala en <strong>Historial de cargas</strong> y sube el archivo corregido.</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
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
