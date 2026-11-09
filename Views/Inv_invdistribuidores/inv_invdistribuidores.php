<?php headerAdmin($data); ?>
<?php
/* Textos que cambian según la base elegida (Retail | Inventario): data-txt-retail / data-txt-inventario */
if (!function_exists('txtDist')) {
    function txtDist(string $retail, string $inventario): string
    {
        return 'data-txt-retail="' . htmlspecialchars($retail, ENT_QUOTES) . '" data-txt-inventario="'
            . htmlspecialchars($inventario, ENT_QUOTES) . '">' . htmlspecialchars($retail, ENT_QUOTES);
    }
}
$puedeSubir = !empty($_SESSION['permisosMod']['w']);
?>
<style>
    /* ---- Retail / inventario de distribuidores ---- */
    /* Encabezado de color: azul en Retail, verde azulado en Inventario */
    .dist-hero { position: relative; overflow: hidden; border-radius: .75rem; padding: 1.5rem 1.75rem; color: #fff;
        background: linear-gradient(120deg, #1f4e78 0%, #2a7bbf 55%, #25a0e2 100%); box-shadow: 0 6px 18px rgba(31, 78, 120, .18); transition: background .3s; }
    .dist-hero.inv { background: linear-gradient(120deg, #0b4f4a 0%, #0f8a7a 55%, #0ab39c 100%); box-shadow: 0 6px 18px rgba(11, 79, 74, .18); }
    .dist-hero::before, .dist-hero::after { content: ""; position: absolute; border-radius: 50%; background: rgba(255, 255, 255, .07); pointer-events: none; }
    .dist-hero::before { width: 260px; height: 260px; right: -60px; top: -120px; }
    .dist-hero::after { width: 160px; height: 160px; right: 180px; bottom: -110px; }
    .dist-hero-icono { width: 58px; height: 58px; border-radius: 14px; display: flex; align-items: center; justify-content: center;
        font-size: 1.8rem; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .25); }
    .dist-hero-titulo { color: #fff; font-weight: 600; font-size: 1.45rem; }
    .dist-hero-desc { color: rgba(255, 255, 255, .8); font-size: .9rem; }
    .dist-hero-meta { display: inline-block; font-size: .8rem; color: #fff; background: rgba(0, 0, 0, .15); border-radius: 50rem; padding: .25rem .75rem; }
    .dist-switch { display: inline-flex; padding: 4px; border-radius: 50rem; background: rgba(0, 0, 0, .18); }
    .dist-switch .dist-modo { border: 0; background: transparent; color: rgba(255, 255, 255, .85); font-weight: 500; padding: .45rem 1.1rem; border-radius: 50rem; transition: background-color .15s, color .15s; }
    .dist-switch .dist-modo:hover { color: #fff; background: rgba(255, 255, 255, .12); }
    .dist-switch .dist-modo.active { background: #fff; color: #1f4e78; box-shadow: 0 2px 6px rgba(0, 0, 0, .15); }
    .dist-hero.inv .dist-switch .dist-modo.active { color: #0b4f4a; }
    .dist-hero-btn { color: #fff; border: 1px solid rgba(255, 255, 255, .5); background: transparent; }
    .dist-hero-btn:hover { color: #fff; background: rgba(255, 255, 255, .15); }
    .dist-kpi .avatar-title { font-size: 1.4rem; }
    .dist-kpi { border-top: 3px solid transparent; }
    .dist-kpi.k-primary { border-top-color: rgb(var(--vz-primary-rgb)); }
    .dist-kpi.k-info { border-top-color: #299cdb; }
    .dist-kpi.k-success { border-top-color: #0ab39c; }
    .dist-kpi.k-warning { border-top-color: #f7b84b; }
    .dist-kpi .dist-kpi-valor { font-size: 1.55rem; font-weight: 600; font-variant-numeric: tabular-nums; }
    .dist-kpi .dist-kpi-sub { font-size: .78rem; }
    .dist-vin { font-family: SFMono-Regular, Menlo, Consolas, monospace; font-size: .82rem; letter-spacing: .02em; }
    .dist-nowrap { white-space: nowrap; }
    .dist-trunc { max-width: 240px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: inline-block; vertical-align: middle; }
    .dist-top-barra { height: 6px; }
    .dist-resumen th:first-child, .dist-resumen td:first-child { position: sticky; left: 0; z-index: 1; background-color: var(--vz-secondary-bg); }
    .dist-resumen thead th:first-child, .dist-resumen tfoot td:first-child { background-color: var(--vz-tertiary-bg); }
    .dist-resumen td { font-variant-numeric: tabular-nums; }
    .dist-paso { width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: 600; font-size: .85rem; flex-shrink: 0; }
    .dist-drop { position: relative; border: 2px dashed var(--vz-border-color); border-radius: .5rem; padding: 1.5rem 1rem; text-align: center; transition: border-color .15s, background-color .15s; }
    .dist-drop.is-over, .dist-drop:hover { border-color: rgb(var(--vz-primary-rgb)); background-color: rgba(var(--vz-primary-rgb), .04); }
    .dist-drop input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
    .dist-drop.tiene-archivo { border-style: solid; border-color: rgb(var(--vz-success-rgb, 10,179,156)); }
    #chartTendencia { min-height: 290px; }
</style>
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

            <!-- ENCABEZADO + BASE: RETAIL | INVENTARIO -->
            <div class="dist-hero mb-3" id="distHero">
                <div class="d-flex flex-wrap align-items-center gap-3 position-relative">
                    <div class="dist-hero-icono flex-shrink-0"><i class="ri-shopping-cart-2-line" id="heroIcono"></i></div>
                    <div class="flex-grow-1" style="min-width:240px">
                        <h3 class="dist-hero-titulo mb-1" <?= txtDist('Retail de distribuidores', 'Inventario de distribuidores') ?></h3>
                        <p class="dist-hero-desc mb-2" <?= txtDist(
                            'Unidades que los distribuidores vendieron a cliente final, por MES RETAIL.',
                            'Foto mensual de las unidades en piso de cada distribuidor, por AÑO R / MES R.'
                        ) ?></p>
                        <div class="dist-hero-meta" id="heroMeta"><i class="ri-time-line align-bottom me-1"></i> Cargando última carga…</div>
                    </div>
                    <div class="d-flex flex-column align-items-stretch align-items-md-end gap-2">
                        <div class="dist-switch" role="radiogroup" aria-label="Base de datos">
                            <button type="button" class="dist-modo active" data-modo="retail" role="radio" aria-checked="true">
                                <i class="ri-shopping-cart-2-line align-bottom me-1"></i> Retail
                            </button>
                            <button type="button" class="dist-modo" data-modo="inventario" role="radio" aria-checked="false">
                                <i class="ri-archive-line align-bottom me-1"></i> Inventario
                            </button>
                        </div>
                        <?php if ($puedeSubir) { ?>
                            <div class="d-flex gap-2 justify-content-md-end">
                                <button type="button" class="btn btn-sm dist-hero-btn" id="heroPlantilla">
                                    <i class="ri-download-2-line align-bottom me-1"></i> Plantilla
                                </button>
                                <button type="button" class="btn btn-sm btn-light" id="heroSubir">
                                    <i class="ri-upload-cloud-2-line align-bottom me-1"></i> Subir archivo
                                </button>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <?php if (empty($data['tablas_listas'])) { ?>
                <div class="alert alert-danger modo-retail" role="alert">
                    <i class="ri-error-warning-line align-bottom me-1"></i>
                    Faltan las tablas de retail. Corre el script
                    <code>Docs/desarrollo/inventario_distribuidores/migracion_retail_distribuidores_20261104.sql</code>.
                </div>
            <?php } ?>
            <?php if (empty($data['tablas_inventario_listas'])) { ?>
                <div class="alert alert-danger modo-inventario d-none" role="alert">
                    <i class="ri-error-warning-line align-bottom me-1"></i>
                    Faltan las tablas de inventario. Corre el script
                    <code>Docs/desarrollo/inventario_distribuidores/migracion_inventario_distribuidores_20261109.sql</code>.
                </div>
            <?php } ?>

            <!-- FILTROS (aplican a KPIs, gráfica, listado, resumen y exportación) -->
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
                        <div class="col-xl-3 col-md-6">
                            <label class="form-label small text-muted mb-1" for="fDistribuidor">Distribuidor</label>
                            <select class="form-select form-select-sm" id="fDistribuidor"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-2 col-md-6">
                            <label class="form-label small text-muted mb-1" for="fModelo" <?= txtDist('Modelo gen', 'Modelo homo') ?></label>
                            <select class="form-select form-select-sm" id="fModelo"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label small text-muted mb-1" for="fEstatus">Estatus</label>
                            <select class="form-select form-select-sm" id="fEstatus"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-2 col-md-4">
                            <label class="form-label small text-muted mb-1" for="fFiltro4" <?= txtDist('Tipo de pago', 'Tipo') ?></label>
                            <select class="form-select form-select-sm" id="fFiltro4"><option value="">Todos</option></select>
                        </div>
                        <div class="col-xl-3 col-md-4">
                            <label class="form-label small text-muted mb-1" for="fMesDesde">Periodo</label>
                            <div class="input-group input-group-sm">
                                <select class="form-select form-select-sm" id="fMesDesde" aria-label="Mes desde"><option value="">Desde</option></select>
                                <span class="input-group-text">a</span>
                                <select class="form-select form-select-sm" id="fMesHasta" aria-label="Mes hasta"><option value="">Hasta</option></select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs (los llena el JS según la base) -->
            <div class="row">
                <?php
                $kpis = [
                    ['id' => 1, 'color' => 'primary', 'icono' => 'ri-stack-line'],
                    ['id' => 2, 'color' => 'info', 'icono' => 'ri-store-2-line'],
                    ['id' => 3, 'color' => 'success', 'icono' => 'ri-calendar-check-line'],
                    ['id' => 4, 'color' => 'warning', 'icono' => 'ri-line-chart-line'],
                ];
                foreach ($kpis as $k) { ?>
                    <div class="col-sm-6 col-xl-3">
                        <div class="card card-animate dist-kpi k-<?= $k['color'] ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-start justify-content-between">
                                    <div class="overflow-hidden me-2">
                                        <p class="text-uppercase fw-medium text-muted text-truncate mb-2" id="kpi<?= $k['id'] ?>Titulo">&nbsp;</p>
                                        <div class="dist-kpi-valor text-body mb-1" id="kpi<?= $k['id'] ?>Valor">—</div>
                                        <div class="dist-kpi-sub text-muted text-truncate" id="kpi<?= $k['id'] ?>Sub">&nbsp;</div>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-<?= $k['color'] ?>-subtle text-<?= $k['color'] ?> rounded-circle" id="kpi<?= $k['id'] ?>Icono">
                                            <i class="<?= $k['icono'] ?>"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>

            <!-- TENDENCIA + TOP DISTRIBUIDORES -->
            <div class="row">
                <div class="col-xl-8">
                    <div class="card card-height-100">
                        <div class="card-header d-flex align-items-center py-2">
                            <h6 class="card-title mb-0 flex-grow-1" <?= txtDist('Retail por mes', 'Inventario al cierre de cada mes') ?></h6>
                            <span class="text-muted small" id="tendenciaRango"></span>
                        </div>
                        <div class="card-body pb-1">
                            <div id="chartTendencia"></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card card-height-100">
                        <div class="card-header d-flex align-items-center py-2">
                            <h6 class="card-title mb-0 flex-grow-1">Top distribuidores</h6>
                            <span class="badge bg-light text-body" id="topMes"></span>
                        </div>
                        <div class="card-body" id="topDistribuidores">
                            <p class="text-muted text-center my-4 mb-0">Cargando…</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-0">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabListado" type="button" role="tab">
                                <i class="ri-list-check-2 align-bottom me-1"></i> <span <?= txtDist('Listado retail', 'Listado inventario') ?></span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabResumen" type="button" role="tab">
                                <i class="ri-grid-line align-bottom me-1"></i> Resumen por mes
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
                                <span class="text-muted small"><i class="ri-information-line align-bottom"></i> Usa <i class="ri-eye-line"></i> para ver todos los datos del renglón.</span>
                                <button type="button" class="btn btn-soft-success btn-sm" id="btnExportar">
                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Exportar lo filtrado
                                </button>
                            </div>
                            <div class="modo-retail">
                                <table id="tableRetail" class="table table-hover table-nowrap align-middle w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mes</th>
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
                            <div class="modo-inventario d-none">
                                <table id="tableInventario" class="table table-hover table-nowrap align-middle w-100">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Mes</th>
                                            <th>Distribuidor</th>
                                            <th>VIN</th>
                                            <th>Modelo</th>
                                            <th>Modelo homo</th>
                                            <th>Año</th>
                                            <th>Tipo</th>
                                            <th>Estatus</th>
                                            <th>Observaciones</th>
                                            <th>Fecha fact.</th>
                                            <th>Cliente final</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                </table>
                            </div>
                        </div>

                        <!-- ============ RESUMEN ============ -->
                        <div class="tab-pane fade" id="tabResumen" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-end gap-2 mb-3">
                                <div>
                                    <label class="form-label small text-muted mb-1" for="rAnio">Año</label>
                                    <select class="form-select form-select-sm" id="rAnio" style="min-width:110px"></select>
                                </div>
                                <div>
                                    <label class="form-label small text-muted mb-1">Agrupar por</label>
                                    <div class="btn-group btn-group-sm d-flex" role="group">
                                        <input type="radio" class="btn-check" name="rAgrupar" id="rAgruparDist" value="distribuidor" checked>
                                        <label class="btn btn-outline-secondary" for="rAgruparDist">Distribuidor</label>
                                        <input type="radio" class="btn-check" name="rAgrupar" id="rAgruparModelo" value="modelo_gen">
                                        <label class="btn btn-outline-secondary" for="rAgruparModelo" <?= txtDist('Modelo gen', 'Modelo homo') ?></label>
                                    </div>
                                </div>
                                <div class="text-muted small ms-md-2 flex-grow-1" style="max-width:640px" <?= txtDist(
                                    'Unidades retail por mes del año elegido. Más intenso = más unidades. Respeta los filtros de arriba (menos el periodo).',
                                    'Unidades en inventario al cierre de cada mes. Como cada mes es una foto, la última columna es el promedio mensual, no la suma. Respeta los filtros (menos el periodo).'
                                ) ?></div>
                            </div>
                            <div class="table-responsive">
                                <table id="tableResumen" class="table table-sm table-bordered align-middle text-end mb-0 dist-resumen">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-start" id="thGrupo">Distribuidor</th>
                                            <th>Ene</th><th>Feb</th><th>Mar</th><th>Abr</th><th>May</th><th>Jun</th>
                                            <th>Jul</th><th>Ago</th><th>Sep</th><th>Oct</th><th>Nov</th><th>Dic</th>
                                            <th <?= txtDist('Total', 'Promedio') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                    <tfoot class="table-light fw-semibold"></tfoot>
                                </table>
                            </div>
                        </div>

                        <!-- ============ CARGAS ============ -->
                        <div class="tab-pane fade" id="tabCargas" role="tabpanel">
                            <div class="alert alert-light border small py-2 mb-3" role="alert">
                                <i class="ri-information-line align-bottom me-1"></i>
                                Cada archivo reemplaza los meses que trae. <strong>Vigentes</strong> = renglones de esa carga que siguen en la tabla
                                (una carga más nueva con los mismos meses los reemplaza).
                            </div>
                            <table id="tableCargas" class="table table-hover table-nowrap align-middle w-100">
                                <thead class="table-light">
                                    <tr>
                                        <th>Folio</th>
                                        <th>Archivo</th>
                                        <th>Meses</th>
                                        <th>Filas</th>
                                        <th>Cargadas</th>
                                        <th>No cargadas</th>
                                        <th>Reemplazó</th>
                                        <th>Vigentes</th>
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
                                        <!-- Paso 1 -->
                                        <div class="d-flex gap-3 mb-4">
                                            <span class="dist-paso bg-primary-subtle text-primary">1</span>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1" <?= txtDist('Descarga la plantilla de retail', 'Descarga la plantilla de inventario') ?></h6>
                                                <p class="text-muted small mb-2">Solo se acepta la plantilla de este módulo; la de retail y la de inventario son distintas.</p>
                                                <button type="button" id="btnDescargarPlantilla" class="btn btn-soft-primary btn-sm">
                                                    <i class="ri-download-2-line align-bottom me-1"></i> <span <?= txtDist('Plantilla de retail', 'Plantilla de inventario') ?></span>
                                                </button>
                                            </div>
                                        </div>
                                        <!-- Paso 2 -->
                                        <div class="d-flex gap-3 mb-4">
                                            <span class="dist-paso bg-primary-subtle text-primary">2</span>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1">Pega tus datos</h6>
                                                <p class="text-muted small mb-0" <?= txtDist(
                                                    'Captura o pega los renglones en la hoja "Retail" de la plantilla. Obligatorias: DISTRIBUIDOR, VIN y MES RETAIL (encabezado verde).',
                                                    'Copia el bloque de la hoja "Inventarios" de tu archivo BD_RT_INV (sin encabezados) y pégalo desde la celda A2: las columnas están en el mismo orden. Obligatorias: DISTRIBUIDOR, VIN, AÑO R y MES R.'
                                                ) ?></p>
                                            </div>
                                        </div>
                                        <!-- Paso 3 -->
                                        <div class="d-flex gap-3">
                                            <span class="dist-paso bg-primary-subtle text-primary">3</span>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-2">Sube el archivo</h6>
                                                <form id="formCarga" autocomplete="off">
                                                    <input type="hidden" name="tipo" id="tipoCarga" value="retail">
                                                    <div class="dist-drop mb-3" id="dropCarga">
                                                        <input type="file" id="archivoCarga" name="archivo" accept=".xlsx,.xls" required aria-label="Plantilla llena (.xlsx)">
                                                        <i class="ri-upload-cloud-2-line display-6 text-muted d-block mb-1" id="dropIcono"></i>
                                                        <div class="fw-medium" id="dropTexto">Arrastra aquí la plantilla llena o haz clic para elegirla</div>
                                                        <div class="text-muted small" id="dropDetalle">Excel .xlsx · máximo 30 MB</div>
                                                    </div>
                                                    <div class="row g-2 align-items-end">
                                                        <div class="col-sm-8">
                                                            <label class="form-label small text-muted mb-1" for="observacionesCarga">Observaciones (opcional)</label>
                                                            <input type="text" class="form-control form-control-sm" id="observacionesCarga" name="observaciones" maxlength="255" placeholder="Ej. Corte septiembre 2026">
                                                        </div>
                                                        <div class="col-sm-4">
                                                            <button type="submit" class="btn btn-success btn-sm w-100">
                                                                <i class="ri-upload-2-line align-bottom me-1"></i> <span <?= txtDist('Subir retail', 'Subir inventario') ?></span>
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
                                            <ul class="text-muted small ps-3 mb-0 modo-retail">
                                                <li class="mb-1">El archivo es <strong>acumulado</strong>: los meses que trae <strong>reemplazan</strong> a los que ya estaban; los que no vienen se conservan.</li>
                                                <li class="mb-1">MES RETAIL es el mes de la venta (p. ej. 01/09/2026).</li>
                                                <li class="mb-1">Captura libre: sin listas ni restricciones en las celdas.</li>
                                                <li class="mb-1">Para corregir algo ya cargado: <strong>Exportar</strong> descarga lo filtrado en este mismo formato; corrígelo y vuelve a subirlo.</li>
                                                <li class="mb-1">Los datos se guardan tal cual: no se validan contra las unidades/VINs del MRP ni el catálogo de clientes.</li>
                                                <li>Las filas que no se puedan cargar y los avisos (VIN repetido en el mismo mes, fechas no reconocidas) quedan en un log descargable.</li>
                                            </ul>
                                            <ul class="text-muted small ps-3 mb-0 modo-inventario d-none">
                                                <li class="mb-1">El archivo es <strong>acumulado</strong>: los meses que trae <strong>reemplazan</strong> a los que ya estaban; los que no vienen se conservan.</li>
                                                <li class="mb-1">AÑO R / MES R es el mes del inventario (p. ej. 2026 y 9 = septiembre 2026).</li>
                                                <li class="mb-1">Las columnas <strong>X</strong> y <strong>Y</strong> (encabezado gris) no se guardan; solo están para que el pegado coincida.</li>
                                                <li class="mb-1">Para corregir algo ya cargado: <strong>Exportar</strong> descarga lo filtrado en este mismo formato; corrígelo y vuelve a subirlo.</li>
                                                <li class="mb-1">Los datos se guardan tal cual: no se validan contra las unidades/VINs del MRP ni el catálogo de clientes.</li>
                                                <li>Las filas que no se puedan cargar y los avisos (VIN repetido en el mismo mes, fechas no reconocidas) quedan en un log descargable.</li>
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

    <!-- Modal detalle de un renglón -->
    <div class="modal fade" id="modalDetalle" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-1"><span <?= txtDist('Detalle retail', 'Detalle inventario') ?></span></h5>
                        <div class="dist-vin text-primary" id="tituloDetalle"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body" id="bodyDetalle"></div>
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
