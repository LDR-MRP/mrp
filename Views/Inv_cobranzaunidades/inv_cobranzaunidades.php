<?php headerAdmin($data);
$puedeActualizar = !empty($_SESSION['permisosMod']['u']);
$estatusPago = $data['estatus_pago'];
?>
<style>
    /* ===== Cobranza de unidades (prefijo cob-) ===== */
    .cob-kpi { cursor: pointer; transition: box-shadow .15s, transform .15s; }
    .cob-kpi .avatar-title { font-size: 1.5rem; }
    .cob-kpi h3 { font-size: 1.6rem; }
    .cob-kpi.cob-activo { box-shadow: 0 0 0 2px var(--vz-primary); }
    .cob-tabs .nav-link { font-weight: 600; padding: .9rem 1.1rem; }
    .cob-tabs .nav-link i { font-size: 1.05rem; vertical-align: -2px; }

    .cob-filters { background: var(--vz-light-bg-subtle); border: 1px solid var(--vz-border-color); border-radius: .5rem; padding: .9rem 1rem; }
    #tableCobranza td.cob-vin { font-family: var(--vz-font-monospace); font-size: .82rem; }
    #tableCobranza .cob-upd { font-size: .75rem; line-height: 1.15; }

    .cob-step { display: flex; gap: 1rem; padding: 1.1rem 0; border-bottom: 1px dashed var(--vz-border-color); }
    .cob-step:first-child { padding-top: 0; }
    .cob-step:last-child { border-bottom: 0; padding-bottom: 0; }
    .cob-step-num {
        flex: 0 0 2.25rem; width: 2.25rem; height: 2.25rem; border-radius: 50%;
        display: flex; align-items: center; justify-content: center; font-weight: 700;
        background: var(--vz-primary-bg-subtle); color: var(--vz-primary);
    }
    .cob-step-body { flex: 1 1 auto; min-width: 0; }
    .cob-step-body h6 { margin-bottom: .25rem; font-weight: 600; }

    .cob-drop {
        position: relative; border: 2px dashed var(--vz-gray-400); border-radius: .5rem;
        padding: 1.4rem 1rem; text-align: center; background: var(--vz-light-bg-subtle);
        transition: border-color .15s, background-color .15s;
    }
    .cob-drop:hover, .cob-drop.cob-over { border-color: var(--vz-primary); background: var(--vz-primary-bg-subtle); }
    .cob-drop.cob-has-file { border-style: solid; border-color: var(--vz-success); background: var(--vz-success-bg-subtle); }
    .cob-drop input[type="file"] { position: absolute; inset: 0; width: 100%; height: 100%; opacity: 0; cursor: pointer; }
    .cob-drop i { font-size: 2rem; line-height: 1; }
    .cob-drop .cob-file-name { font-weight: 600; word-break: break-all; }

    .cob-opciones { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: .5rem; }
    .cob-opciones label {
        display: flex; align-items: center; gap: .5rem; padding: .65rem .75rem; margin: 0;
        border: 1px solid var(--vz-border-color); border-radius: .5rem; cursor: pointer; font-weight: 500;
        transition: border-color .15s, background-color .15s;
    }
    .cob-opciones input { display: none; }
    .cob-opciones input:checked + label { border-color: var(--vz-primary); background: var(--vz-primary-bg-subtle); }
    .cob-opciones .cob-dot { width: .7rem; height: .7rem; border-radius: 50%; flex: 0 0 auto; }
    .cob-hist { max-height: 220px; overflow-y: auto; }
    .cob-hist li { padding: .45rem 0; border-bottom: 1px solid var(--vz-border-color-translucent); font-size: .8rem; }
    .cob-hist li:last-child { border-bottom: 0; }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-1"><?= $data['page_title'] ?></h4>
                            <p class="text-muted mb-0 fs-13">Estatus de pago de las unidades facturadas</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===================== INDICADORES (clic = filtrar) ===================== -->
            <div class="row">
                <?php
                $kpis = [
                    0 => ['Pendientes', 'Sin estatus de pago', 'danger', 'ri-time-line'],
                    1 => ['Crédito', 'Vendidas a crédito', 'info', 'ri-bank-card-line'],
                    2 => ['Parcialmente pagadas', 'Con pago parcial', 'warning', 'ri-pie-chart-2-line'],
                    3 => ['Pagadas', 'Liquidadas', 'success', 'ri-checkbox-circle-line'],
                ];
                foreach ($kpis as $k => [$titulo, $sub, $color, $icono]) { ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate cob-kpi" data-estatus="<?= $k ?>" title="Filtrar por <?= $titulo ?>">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-semibold text-muted text-truncate mb-2 fs-12"><?= $titulo ?></p>
                                        <h3 class="fw-semibold mb-1" id="kpiEstatus<?= $k ?>">—</h3>
                                        <span class="text-muted fs-12"><?= $sub ?></span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-<?= $color ?>-subtle text-<?= $color ?> rounded-3">
                                            <i class="<?= $icono ?>"></i>
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
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0 cob-tabs" role="tablist" id="cobTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tabUnidades" role="tab">
                                <i class="ri-car-line me-1"></i> Unidades facturadas
                            </a>
                        </li>
                        <?php if ($puedeActualizar) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#tabMasiva" role="tab">
                                    <i class="ri-file-excel-2-line me-1"></i> Actualizar por Excel
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <!-- ============ UNIDADES ============ -->
                        <div class="tab-pane active" id="tabUnidades" role="tabpanel">
                            <div class="cob-filters mb-3">
                                <div class="row g-2 align-items-end">
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="cobDesde">Venta desde</label>
                                        <input type="date" class="form-control form-control-sm" id="cobDesde">
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="cobHasta">Venta hasta</label>
                                        <input type="date" class="form-control form-control-sm" id="cobHasta">
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="cobDistribuidor">Distribuidor</label>
                                        <select class="form-select form-select-sm" id="cobDistribuidor">
                                            <option value="">Todos</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="cobModelo">Modelo</label>
                                        <select class="form-select form-select-sm" id="cobModelo">
                                            <option value="">Todos</option>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-lg-2">
                                        <label class="form-label fs-12 text-muted mb-1" for="cobEstatus">Estatus de pago</label>
                                        <select class="form-select form-select-sm" id="cobEstatus">
                                            <option value="">Todos</option>
                                            <?php foreach ($estatusPago as $k => $v) { ?>
                                                <option value="<?= $k ?>"><?= $v ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-sm-6 col-lg-2 d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-sm flex-grow-1" id="btnCobConsultar">
                                            <i class="ri-search-line align-bottom me-1"></i> Consultar
                                        </button>
                                        <button type="button" class="btn btn-soft-secondary btn-sm" id="btnCobLimpiar" title="Limpiar filtros">
                                            <i class="ri-refresh-line align-bottom"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-2">
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <span class="fs-12 text-muted">Periodo rápido:</span>
                                        <div class="btn-group btn-group-sm" role="group" id="cobRapidos">
                                            <button type="button" class="btn btn-outline-primary" data-rango="mes">Este mes</button>
                                            <button type="button" class="btn btn-outline-primary" data-rango="mesant">Mes anterior</button>
                                            <button type="button" class="btn btn-outline-primary" data-rango="30">Últimos 30 días</button>
                                            <button type="button" class="btn btn-outline-primary" data-rango="todo">Todo</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <span class="badge bg-primary-subtle text-primary fs-12 px-3 py-2"><i class="ri-car-line me-1"></i><span id="cobTotUnidades">0</span> unidades</span>
                                <span class="badge bg-secondary-subtle text-secondary fs-12 px-3 py-2"><i class="ri-filter-3-line me-1"></i><span id="cobFiltrosTxt">Sin filtros</span></span>
                            </div>

                            <table id="tableCobranza" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead class="table-light">
                                    <tr>
                                        <th>FECHA VENTA</th>
                                        <th>VIN</th>
                                        <th>MODELO</th>
                                        <th>DISTRIBUIDOR</th>
                                        <th>FACTURA</th>
                                        <th>ESTATUS DE PAGO</th>
                                        <th>ACTUALIZADO</th>
                                        <th>SKU</th>
                                        <th>FOLIO MOV.</th>
                                        <th class="text-center">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <!-- ============ ACTUALIZAR POR EXCEL ============ -->
                        <?php if ($puedeActualizar) { ?>
                            <div class="tab-pane" id="tabMasiva" role="tabpanel">
                                <div class="row g-4">
                                    <div class="col-lg-7">
                                        <div class="cob-step">
                                            <div class="cob-step-num">1</div>
                                            <div class="cob-step-body">
                                                <h6>Descarga las unidades</h6>
                                                <p class="text-muted fs-13 mb-2">El archivo trae las unidades facturadas con los filtros que tengas en la pestaña <strong>Unidades facturadas</strong> (fechas, distribuidor, modelo y estatus).</p>
                                                <button type="button" class="btn btn-success btn-sm" id="btnCobDescargar2">
                                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Descargar unidades filtradas
                                                </button>
                                            </div>
                                        </div>
                                        <div class="cob-step">
                                            <div class="cob-step-num">2</div>
                                            <div class="cob-step-body">
                                                <h6>Captura el estatus</h6>
                                                <p class="text-muted fs-13 mb-0">Solo edita la columna verde <strong>ESTATUS_PAGO</strong>:
                                                    <?php foreach ($estatusPago as $k => $v) { ?>
                                                        <span class="badge bg-light text-body border"><?= $v ?></span>
                                                    <?php } ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="cob-step">
                                            <div class="cob-step-num">3</div>
                                            <div class="cob-step-body">
                                                <h6>Sube el archivo</h6>
                                                <form id="formCobCarga" autocomplete="off">
                                                    <div class="cob-drop mb-3" data-default="Arrastra aquí el archivo o haz clic para seleccionarlo">
                                                        <input type="file" id="archivoCobranza" name="archivo" accept=".xlsx,.xls">
                                                        <i class="ri-upload-cloud-2-line text-primary"></i>
                                                        <div class="cob-file-name mt-2">Arrastra aquí el archivo o haz clic para seleccionarlo</div>
                                                        <div class="text-muted fs-12">Formato .xlsx</div>
                                                    </div>
                                                    <button type="submit" class="btn btn-primary">
                                                        <i class="ri-upload-2-line align-bottom me-1"></i> Subir y actualizar
                                                    </button>
                                                </form>
                                                <div id="cobResultado" class="mt-3" style="display:none;">
                                                    <div class="alert mb-2" id="cobAlertResultado" role="alert"></div>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" id="btnCobLog" style="display:none;">
                                                        <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de lo que no se aplicó
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5">
                                        <div class="border rounded-3 p-3 h-100">
                                            <h6 class="fw-semibold mb-2"><i class="ri-information-line text-primary me-1"></i> Cómo funciona</h6>
                                            <ul class="list-unstyled text-muted fs-13 mb-0">
                                                <li class="mb-2"><i class="ri-checkbox-blank-circle-fill fs-10 me-2 text-primary"></i>Es <strong class="text-body">solo de actualización</strong>: no agregues filas.</li>
                                                <li class="mb-2"><i class="ri-checkbox-blank-circle-fill fs-10 me-2 text-primary"></i>Las filas sin cambio de estatus se ignoran.</li>
                                                <li class="mb-2"><i class="ri-checkbox-blank-circle-fill fs-10 me-2 text-primary"></i>VIN y FOLIO_VENTA identifican la venta: si la unidad se devolvió o se volvió a vender después de descargar, esa fila va al log.</li>
                                                <li class="mb-0"><i class="ri-checkbox-blank-circle-fill fs-10 me-2 text-primary"></i>Cada cambio queda en el historial de la unidad (quién y cuándo).</li>
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

    <!-- ===================== MODAL: CAMBIAR ESTATUS / HISTORIAL ===================== -->
    <div class="modal fade" id="modalCobEstatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="ri-money-dollar-circle-line me-1"></i> Estatus de pago</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="bg-light rounded-3 p-3 mb-3 fs-13">
                        <div class="fw-semibold font-monospace" id="cobMVin"></div>
                        <div class="text-muted" id="cobMModelo"></div>
                        <div class="mt-1"><span class="text-muted">Distribuidor:</span> <span id="cobMDist"></span></div>
                        <div><span class="text-muted">Factura:</span> <span id="cobMFactura"></span> · <span class="text-muted">Venta:</span> <span id="cobMFecha"></span></div>
                    </div>

                    <?php if ($puedeActualizar) { ?>
                        <form id="formCobEstatus" autocomplete="off">
                            <input type="hidden" name="id_numeros_serie" id="cobMId">
                            <input type="hidden" name="idmovinventario" id="cobMMov">
                            <label class="form-label fw-semibold">Nuevo estatus</label>
                            <div class="cob-opciones mb-3">
                                <?php
                                $colores = [0 => 'danger', 1 => 'info', 2 => 'warning', 3 => 'success'];
                                foreach ($estatusPago as $k => $v) { ?>
                                    <div>
                                        <input type="radio" name="estatus_pago" id="cobOpt<?= $k ?>" value="<?= $k ?>">
                                        <label for="cobOpt<?= $k ?>"><span class="cob-dot bg-<?= $colores[$k] ?>"></span><?= $v ?></label>
                                    </div>
                                <?php } ?>
                            </div>
                        </form>
                    <?php } ?>

                    <h6 class="fs-13 fw-semibold mb-1">Historial</h6>
                    <ul class="list-unstyled cob-hist mb-0" id="cobMHistorial"></ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <?php if ($puedeActualizar) { ?>
                        <button type="submit" form="formCobEstatus" class="btn btn-primary" id="btnCobGuardar">
                            <i class="ri-save-line align-bottom me-1"></i> Guardar
                        </button>
                    <?php } ?>
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
    const COB_PUEDE_ACTUALIZAR = <?= $puedeActualizar ? 'true' : 'false' ?>;
    const COB_ESTATUS = <?= json_encode($estatusPago, JSON_UNESCAPED_UNICODE) ?>;
</script>
<?php footerAdmin($data); ?>
