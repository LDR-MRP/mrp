<?php headerAdmin($data); ?>

<style>
    /* ===== Generación de VIN · estilos del módulo ===== */
    .seg-wmi { --seg-rgb: var(--vz-primary-rgb); }
    .seg-vds { --seg-rgb: var(--vz-success-rgb); }
    .seg-chk { --seg-rgb: var(--vz-warning-rgb); }
    .seg-vis { --seg-rgb: var(--vz-info-rgb); }
    .seg-sec { --seg-rgb: var(--vz-secondary-rgb); }
    .seg-num { --seg-rgb: var(--vz-danger-rgb); }

    /* tarjetas de modo (orden = primary, lote = info) */
    .modo-orden { --modo-rgb: var(--vz-primary-rgb); }
    .modo-lote  { --modo-rgb: var(--vz-success-rgb); }

    /* etiqueta de tipo en el encabezado de grupo (mismos colores que las tarjetas) */
    .tipo-pill {
        display: inline-flex; align-items: center; gap: .3rem; vertical-align: middle;
        padding: .2rem .6rem .2rem .45rem; border-radius: 999px;
        font-size: .72rem; font-weight: 600; letter-spacing: .02em;
        color: rgb(var(--tipo-rgb)); background: rgba(var(--tipo-rgb), .1);
        border: 1px solid rgba(var(--tipo-rgb), .35);
    }
    .tipo-pill i {
        display: grid; place-items: center; width: 18px; height: 18px; border-radius: 50%;
        font-size: .7rem; color: #fff; background: rgb(var(--tipo-rgb));
    }
    .tipo-lote  { --tipo-rgb: var(--vz-success-rgb); }
    .tipo-orden { --tipo-rgb: var(--vz-primary-rgb); }
    .tipo-carga { --tipo-rgb: var(--vz-warning-rgb); }
    .tipo-importado { --tipo-rgb: var(--vz-info-rgb); }
    .modo-card {
        position: relative; display: flex; gap: 1rem; align-items: center;
        padding: 1.1rem 1.25rem 1.1rem 1.4rem; border: 1px solid var(--vz-border-color);
        border-radius: .65rem; cursor: pointer; height: 100%; overflow: hidden;
        background: var(--vz-card-bg, #fff);
        transition: border-color .15s, background .15s, box-shadow .15s, transform .15s;
    }
    .modo-card::before {
        content: ""; position: absolute; left: 0; top: 0; bottom: 0; width: 4px;
        background: rgb(var(--modo-rgb)); opacity: 0; transition: opacity .15s;
    }
    .modo-card:hover { border-color: rgba(var(--modo-rgb), .55); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(0, 0, 0, .05); }
    .modo-icon {
        flex: none; width: 48px; height: 48px; border-radius: .6rem; display: grid; place-items: center;
        font-size: 1.4rem; color: rgb(var(--modo-rgb)); background: rgba(var(--modo-rgb), .12);
        transition: background .15s, color .15s;
    }
    .modo-card h6 { font-size: .95rem; margin-bottom: .2rem; }
    .modo-card .modo-desc { font-size: .8rem; color: var(--vz-secondary-color); margin-bottom: .5rem; }
    .modo-tags { display: flex; flex-wrap: wrap; gap: .35rem; }
    .modo-tags span {
        font-size: .7rem; font-weight: 500; padding: .15rem .5rem; border-radius: 999px;
        border: 1px solid var(--vz-border-color); color: var(--vz-secondary-color);
    }
    .modo-check {
        flex: none; margin-left: auto; width: 22px; height: 22px; border-radius: 50%;
        border: 2px solid var(--vz-border-color); display: grid; place-items: center;
        font-size: .85rem; color: transparent; transition: all .15s;
    }
    .btn-check:checked + .modo-card {
        border-color: rgb(var(--modo-rgb));
        background: rgba(var(--modo-rgb), .05);
        box-shadow: 0 0 0 1px rgb(var(--modo-rgb)) inset, 0 6px 18px rgba(var(--modo-rgb), .12);
    }
    .btn-check:checked + .modo-card::before { opacity: 1; }
    .btn-check:checked + .modo-card .modo-icon { background: rgb(var(--modo-rgb)); color: #fff; }
    .btn-check:checked + .modo-card .modo-check { border-color: rgb(var(--modo-rgb)); background: rgb(var(--modo-rgb)); color: #fff; }
    .btn-check:checked + .modo-card .modo-tags span { border-color: rgba(var(--modo-rgb), .35); color: rgb(var(--modo-rgb)); }
    .btn-check:focus-visible + .modo-card { outline: 2px solid rgba(var(--modo-rgb), .5); outline-offset: 2px; }

    .gen-section-title {
        display: flex; align-items: center; gap: .5rem;
        font-size: .8rem; font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
        color: var(--vz-secondary-color); margin-bottom: 1rem;
    }
    .gen-section + .gen-section { border-top: 1px dashed var(--vz-border-color); padding-top: 1.25rem; margin-top: .25rem; }

    /* VIN base chip */
    .vin-chip {
        display: inline-flex; font-family: var(--vz-font-monospace); font-weight: 700;
        letter-spacing: .06em; border-radius: 4px; overflow: hidden; border: 1px solid var(--vz-border-color);
    }
    .vin-chip span { padding: 4px 6px; color: rgb(var(--seg-rgb)); background: rgba(var(--seg-rgb), .1); }
    .vin-chip.is-empty span { color: var(--vz-secondary-color); background: transparent; }

    /* anatomía */
    .niv-anatomy { display: flex; gap: 3px; justify-content: center; }
    .niv-cell { display: flex; flex-direction: column; align-items: center; gap: 4px; flex: 1 1 0; max-width: 30px; min-width: 0; }
    .niv-cell .ch {
        width: 100%; aspect-ratio: 3 / 4; display: grid; place-items: center; border-radius: 5px;
        font-family: var(--vz-font-monospace); font-weight: 700; font-size: clamp(.75rem, 1.3vw, 1rem);
        background: rgba(var(--seg-rgb), .12); color: rgb(var(--seg-rgb)); border: 1px solid rgba(var(--seg-rgb), .35);
    }
    .niv-cell.is-empty .ch { background: transparent; color: var(--vz-secondary-color); border-style: dashed; border-color: var(--vz-border-color); }
    .niv-cell .ix { font-size: .6rem; color: var(--vz-secondary-color); line-height: 1; }
    .niv-gap { width: 5px; flex: none; }
    .niv-legend { display: flex; flex-wrap: wrap; justify-content: center; gap: .3rem .9rem; font-size: .7rem; color: var(--vz-secondary-color); }
    .niv-legend span { display: inline-flex; align-items: center; gap: .3rem; }
    .niv-legend i { width: 8px; height: 8px; border-radius: 2px; background: rgb(var(--seg-rgb)); display: inline-block; }

    .vin-string { font-family: var(--vz-font-monospace); font-size: 1.25rem; font-weight: 700; letter-spacing: .1em; word-break: break-all; }
    .resumen-list { list-style: none; padding: 0; margin: 0; }
    .resumen-list li { display: flex; justify-content: space-between; gap: 1rem; padding: .45rem 0; font-size: .82rem; border-bottom: 1px solid var(--vz-border-color); }
    .resumen-list li:last-child { border-bottom: 0; }
    .resumen-list .lbl { color: var(--vz-secondary-color); }
    .resumen-list .val { font-weight: 600; text-align: right; }
    .mono { font-family: var(--vz-font-monospace); }

    @media (min-width: 1200px) { .gen-sticky { position: sticky; top: 90px; } }

    /* autocomplete */
    .autocomplete-items { z-index: 1056; max-height: 280px; overflow-y: auto; box-shadow: 0 6px 18px rgba(0,0,0,.08); }

    /* modal VIN */
    .vin-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(210px, 1fr)); gap: .5rem; max-height: 52vh; overflow-y: auto; }
    .vin-item {
        display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem;
        border: 1px solid var(--vz-border-color); border-radius: .4rem; font-family: var(--vz-font-monospace);
        font-weight: 600; letter-spacing: .05em; font-size: .88rem;
    }
    .vin-item .n { font-size: .68rem; color: var(--vz-secondary-color); min-width: 1.8rem; font-family: inherit; font-weight: 400; }
    .vin-item .d { color: var(--vz-warning); }
    .vin-item .c { color: var(--vz-danger); }

    /* botón principal "Generar nuevos VIN" */
    .btn-generar-vin {
        font-weight: 600;
        padding: .6rem 1.25rem .6rem 3rem;
        box-shadow: 0 4px 14px rgba(var(--vz-primary-rgb), .35);
    }

    /* listado agrupado */
    #tableSeries tr.group-row > td { background: var(--vz-tertiary-bg); cursor: pointer; }

    /* asociación motor / VIN de origen */
    .asoc-paso { display: flex; gap: .85rem; align-items: flex-start; margin-bottom: 1.1rem; }
    .asoc-num {
        flex: none; width: 26px; height: 26px; margin-top: 1.9rem; border-radius: 50%;
        display: grid; place-items: center; font-size: .75rem; font-weight: 700;
        background: rgba(var(--vz-success-rgb), .12); color: var(--vz-success);
    }
    .asoc-vacio { color: var(--vz-secondary-color); font-style: italic; font-size: .8rem; }

    /* carga masiva: selección de lotes / órdenes */
    .carga-paso {
        display: inline-grid; place-items: center; width: 20px; height: 20px; margin-right: .35rem;
        border-radius: 50%; font-size: .7rem; font-weight: 700; color: #fff; background: var(--vz-primary);
    }
    .ref-list { max-height: 280px; overflow-y: auto; }
    .ref-item {
        display: flex; align-items: center; gap: .75rem; padding: .55rem .75rem; margin: 0;
        border-bottom: 1px solid var(--vz-border-color); cursor: pointer; transition: background .12s;
    }
    .ref-item:last-child { border-bottom: 0; }
    .ref-item:hover { background: var(--vz-tertiary-bg); }
    .ref-item.is-checked { background: rgba(var(--vz-primary-rgb), .06); }
    .ref-item .form-check-input { margin: 0; flex: none; }
    .ref-item .ref-main { flex: 1 1 auto; min-width: 0; }
    .ref-item .ref-modelo { font-size: .75rem; color: var(--vz-secondary-color); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ref-item .ref-avance { flex: none; width: 110px; text-align: right; font-size: .72rem; color: var(--vz-secondary-color); }
    .ref-item .ref-avance .progress { height: 4px; margin-top: .25rem; }

    /* historial (línea de tiempo) */
    .hist-list { list-style: none; margin: 0; padding: 0 0 0 1.4rem; position: relative; }
    .hist-list::before { content: ""; position: absolute; left: .45rem; top: .3rem; bottom: .3rem; border-left: 2px solid var(--vz-border-color); }
    .hist-item { position: relative; padding: 0 0 1rem .6rem; }
    .hist-item::before {
        content: ""; position: absolute; left: -1.24rem; top: .3rem; width: 12px; height: 12px; border-radius: 50%;
        background: var(--vz-card-bg, #fff); border: 3px solid rgb(var(--hist-rgb));
    }
    .hist-gen { --hist-rgb: var(--vz-primary-rgb); }
    .hist-asoc { --hist-rgb: var(--vz-success-rgb); }
    .hist-base { --hist-rgb: var(--vz-warning-rgb); }
    .hist-cambio { font-family: var(--vz-font-monospace); font-size: .82rem; }
    .hist-cambio del { color: var(--vz-danger); }
    .hist-cambio ins { color: var(--vz-success); text-decoration: none; font-weight: 600; }
</style>

<script>const PUEDE_ESCRIBIR_VIN = <?= !empty($_SESSION['permisosMod']['w']) ? 'true' : 'false' ?>;</script>
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
                                <li class="breadcrumb-item"><a href="javascript: void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="d-flex align-items-center gap-2 flex-grow-1">
                            <div class="avatar-xs flex-shrink-0">
                                <span class="avatar-title rounded bg-primary-subtle text-primary fs-16"><i class="ri-barcode-line"></i></span>
                            </div>
                            <div>
                                <h5 class="card-title mb-0">Generación de números VIN</h5>
                                <p class="text-muted mb-0 fs-12" id="subtituloVin">VINes generados, agrupados por orden o lote</p>
                            </div>
                        </div>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <div class="d-flex flex-wrap gap-2" id="accionesAsociacion">
                                <button type="button" class="btn btn-soft-success" id="btnAsociarLector">
                                    <i class="ri-links-line align-bottom me-1"></i> Asociar motor / VIN origen
                                </button>
                                <button type="button" class="btn btn-soft-info" id="btnCargaAsociacion">
                                    <i class="ri-file-excel-2-line align-bottom me-1"></i> Carga masiva
                                </button>
                                <button type="button" class="btn btn-soft-primary" id="btnImportados" title="Unidades importadas sin VIN nacional">
                                    <i class="ri-ship-line align-bottom me-1"></i> Unidades importadas
                                </button>
                            </div>
                            <button type="button" class="btn btn-primary btn-label btn-generar-vin" id="btnIrGenerar">
                                <i class="ri-add-circle-line label-icon align-middle fs-18 me-2"></i> Generar nuevos VIN
                            </button>
                            <button type="button" class="btn btn-soft-secondary d-none" id="btnIrListado">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Ver VINes generados
                            </button>
                        <?php } ?>
                    </div>
                </div>

                <div class="card-body">
                    <div class="tab-content">

                        <!-- ================= TAB LISTADO ================= -->
                        <div class="tab-pane active" id="listSeries" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <span class="badge bg-success-subtle text-success fs-12 px-2 py-1" id="kpiAsociados">
                                    <i class="ri-links-line align-bottom me-1"></i> — asociados
                                </span>
                                <span class="badge bg-warning-subtle text-warning fs-12 px-2 py-1" id="kpiPendientes">
                                    <i class="ri-error-warning-line align-bottom me-1"></i> — sin asociar
                                </span>
                                <span class="badge bg-info-subtle text-info fs-12 px-2 py-1" id="kpiImportados" title="Unidades importadas sin VIN nacional (VIN = VIN de origen)">
                                    <i class="ri-ship-line align-bottom me-1"></i> — importadas
                                </span>
                                <div class="ms-auto d-flex gap-2">
                                    <a class="btn btn-sm btn-soft-info" href="<?= base_url(); ?>/Inv_series/exportarImportados" title="Excel con VIN de origen, motor, color, SKU y almacén de las importadas">
                                        <i class="ri-download-2-line align-bottom"></i> Exportar importadas
                                    </a>
                                    <select class="form-select form-select-sm" id="filtroAsociacion" style="min-width:190px">
                                        <option value="">Todos los VIN</option>
                                        <option value="si">Con motor / VIN origen</option>
                                        <option value="no">Sin asociar</option>
                                        <option value="imp">Importadas (sin VIN nacional)</option>
                                    </select>
                                </div>
                            </div>
                            <table id="tableSeries" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead>
                                                <tr>
                                                    <th>PRODUCTO</th>
                                                    <th>ALMACÉN</th>
                                                    <th>NÚMERO VIN</th>
                                                    <th>NÚMERO DE MOTOR</th>
                                                    <th>VIN DE ORIGEN</th>
                                                    <th>COLOR</th>
                                                    <th>REFERENCIA</th>
                                                    <th>FECHA</th>
                                                    <th>ESTADO</th>
                                                    <th>ACCIONES</th>
                                                    <th>ORDEN GRUPO</th>
                                                </tr>
                                            </thead>
                            </table>
                        </div>

                        <!-- ================= TAB GENERAR ================= -->
                        <div class="tab-pane" id="agregarSerie" role="tabpanel">

                            <!-- Modo de generación -->
                            <input type="hidden" id="modoGeneracion" value="orden">
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="modoRadio" id="modoOrden" value="orden" checked>
                                    <label class="modo-card modo-orden mb-0" for="modoOrden">
                                        <span class="modo-icon"><i class="ri-file-list-3-line"></i></span>
                                        <div class="flex-grow-1">
                                            <h6>Por orden de trabajo</h6>
                                            <p class="modo-desc">El producto y la cantidad se toman de la orden.</p>
                                            <div class="modo-tags"><span>Orden de trabajo</span><span>Cantidad automática</span></div>
                                        </div>
                                        <span class="modo-check"><i class="ri-check-line"></i></span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="modoRadio" id="modoLote" value="lote">
                                    <label class="modo-card modo-lote mb-0" for="modoLote">
                                        <span class="modo-icon"><i class="ri-stack-line"></i></span>
                                        <div class="flex-grow-1">
                                            <h6>Por lote</h6>
                                            <p class="modo-desc">Elija el modelo VIN y capture la cantidad a generar.</p>
                                            <div class="modo-tags"><span>Modelo VIN</span><span>Cantidad libre</span></div>
                                        </div>
                                        <span class="modo-check"><i class="ri-check-line"></i></span>
                                    </label>
                                </div>
                            </div>

                            <form id="formSeries" autocomplete="off" onsubmit="return false;">
                                <div class="row">

                                    <!-- ===== FORMULARIO ===== -->
                                    <div class="col-xl-8">

                                        <!-- ---------- MODO ORDEN ---------- -->
                                        <div id="bloqueOrden">
                                            <div class="gen-section">
                                                <div class="gen-section-title"><i class="ri-file-list-3-line fs-16"></i> Orden de trabajo</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6 position-relative">
                                                        <label class="form-label" for="ordenSearch">Orden de trabajo</label>
                                                        <input type="hidden" name="inventarioid" id="inventarioid">
                                                        <input type="hidden" name="referencia" id="referencia">
                                                        <div class="search-box">
                                                            <input type="text" class="form-control ordenSearch" id="ordenSearch" placeholder="Buscar por número de orden..." autocomplete="off">
                                                            <i class="ri-search-line search-icon"></i>
                                                        </div>
                                                        <div id="listaOrdenes" class="list-group"></div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="productoNombre">Producto</label>
                                                        <input type="text" class="form-control bg-light" id="productoNombre" placeholder="Se toma de la orden" readonly>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="gen-section">
                                                <div class="gen-section-title"><i class="ri-barcode-box-line fs-16"></i> Modelo VIN</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="modelo_vin">Modelo VIN</label>
                                                        <select class="form-select" name="modelo_vin" id="modelo_vin">
                                                            <option value="">Seleccione una orden primero</option>
                                                        </select>
                                                        <small class="text-muted" id="modeloVinOrdenHelp">Solo se muestran los modelos VIN del producto de la orden</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label d-block">VIN base</label>
                                                        <input type="hidden" id="vinBasePreview">
                                                        <div class="vin-chip is-empty" id="vinBaseChip"><span>-----------</span></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="gen-section">
                                                <div class="gen-section-title"><i class="ri-store-2-line fs-16"></i> Destino y cantidad</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="almacenid">Almacén</label>
                                                        <select class="form-select" name="almacenid" id="almacenid"></select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="cantidadPreview">Cantidad a generar</label>
                                                        <div class="input-group">
                                                            <input type="text" id="cantidadPreview" class="form-control bg-light" placeholder="Se toma de la orden" readonly>
                                                            <span class="input-group-text">VIN</span>
                                                        </div>
                                                        <input type="hidden" id="cantidadOrden" name="cantidad">
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ---------- MODO LOTE ---------- -->
                                        <div id="bloqueLote" style="display:none;">
                                            <div class="gen-section">
                                                <div class="gen-section-title"><i class="ri-stack-line fs-16"></i> Lote</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="lote">Lote</label>
                                                        <input type="text" id="lote" name="lote" class="form-control" placeholder="Ej. LT001">
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="gen-section">
                                                <div class="gen-section-title"><i class="ri-barcode-box-line fs-16"></i> Modelo VIN</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="modelo_vin_lote">Modelo VIN</label>
                                                        <input type="hidden" id="inventarioid_lote">
                                                        <select class="form-select" id="modelo_vin_lote">
                                                            <option value="">Seleccione modelo VIN</option>
                                                        </select>
                                                        <small class="text-muted">El producto de inventario se toma del modelo VIN</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label d-block">VIN base</label>
                                                        <input type="hidden" id="vinBasePreview_lote">
                                                        <div class="vin-chip is-empty" id="vinBaseChip_lote"><span>-----------</span></div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="gen-section">
                                                <div class="gen-section-title"><i class="ri-store-2-line fs-16"></i> Destino y cantidad</div>
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="almacenid_lote">Almacén</label>
                                                        <select class="form-select" id="almacenid_lote"></select>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <label class="form-label" for="cantidad_lote">Cantidad a generar</label>
                                                        <div class="input-group">
                                                            <input type="number" id="cantidad_lote" class="form-control" min="1" placeholder="Ej. 10">
                                                            <span class="input-group-text">VIN</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- ocultos compartidos -->
                                        <input type="hidden" id="vin_parte_1_8">
                                        <input type="hidden" id="vin_anio">
                                        <input type="hidden" id="vin_planta">
                                    </div>

                                    <!-- ===== RESUMEN ===== -->
                                    <div class="col-xl-4">
                                        <div class="card border shadow-none gen-sticky mb-0">
                                            <div class="card-header d-flex align-items-center">
                                                <h6 class="card-title mb-0 flex-grow-1">Siguiente VIN</h6>
                                                <span class="badge bg-light text-muted" id="resumenModo">Orden de trabajo</span>
                                            </div>
                                            <div class="card-body">
                                                <div class="niv-anatomy mb-2" id="vinAnatomy"></div>
                                                <div class="niv-legend mb-3">
                                                    <span class="seg-wmi"><i></i>WMI</span>
                                                    <span class="seg-vds"><i></i>VDS</span>
                                                    <span class="seg-chk"><i></i>Verificador</span>
                                                    <span class="seg-vis"><i></i>Año / planta</span>
                                                    <span class="seg-num"><i></i>Consecutivo</span>
                                                </div>

                                                <div class="text-center p-3 rounded bg-light mb-3">
                                                    <div class="vin-string" id="vinPreviewFinal">-----------------</div>
                                                    <div class="text-muted fs-11 mt-1" id="vinPreviewNota">Seleccione un modelo VIN</div>
                                                </div>

                                                <ul class="resumen-list mb-4">
                                                    <li><span class="lbl">Producto</span><span class="val" id="resProducto">—</span></li>
                                                    <li><span class="lbl">Referencia</span><span class="val" id="resReferencia">—</span></li>
                                                    <li><span class="lbl">Almacén</span><span class="val" id="resAlmacen">—</span></li>
                                                    <li><span class="lbl">Último consecutivo del año</span><span class="val mono" id="resUltimo">—</span></li>
                                                    <li><span class="lbl">Cantidad</span><span class="val" id="resCantidad">—</span></li>
                                                    <li><span class="lbl">Rango</span><span class="val mono fs-12" id="resRango">—</span></li>
                                                </ul>

                                                <div class="d-grid">
                                                    <button type="button" id="btnPreview" class="btn btn-primary" disabled>
                                                        <i class="ri-eye-line align-bottom me-1"></i> Previsualizar VIN
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <!-- ================= MODAL CONFIRMACIÓN ================= -->
    <div class="modal fade" id="modalPreviewSeries" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0">Confirmar generación de VIN</h5>
                        <p class="text-muted mb-0 fs-12">Revise los números antes de registrarlos. Esta acción no se puede deshacer.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row g-3 mb-3" id="modalResumen"></div>
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0 flex-grow-1">VIN a registrar <span class="badge bg-primary-subtle text-primary ms-1" id="modalTotal">0</span></h6>
                        <span class="fs-12 text-muted me-3"><span class="text-warning fw-bold">■</span> verificador · <span class="text-danger fw-bold">■</span> consecutivo</span>
                        <button type="button" class="btn btn-sm btn-soft-secondary" id="btnCopiarVins"><i class="ri-file-copy-line align-bottom"></i> Copiar</button>
                    </div>
                    <div class="vin-grid" id="previewContainer"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnConfirmSeries">
                        <i class="ri-check-double-line align-bottom me-1"></i> Confirmar registro
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL ASOCIAR MOTOR / VIN DE ORIGEN ================= -->
    <div class="modal fade" id="modalAsociarVin" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0">Asociar número de motor y VIN de origen</h5>
                        <p class="text-muted mb-0 fs-12">Funciona con lector: escanee y presione Enter para pasar al siguiente campo.</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formAsociarVin" autocomplete="off" onsubmit="return false;">
                    <div class="modal-body">
                        <input type="hidden" id="asoc_id_serie">

                        <div class="asoc-paso">
                            <span class="asoc-num">1</span>
                            <div class="flex-grow-1">
                                <label class="form-label" for="asoc_vin">VIN generado</label>
                                <div class="input-group">
                                    <input type="text" class="form-control mono text-uppercase" id="asoc_vin" maxlength="17" placeholder="Escanee o escriba el VIN generado">
                                    <button type="button" class="btn btn-soft-primary" id="btnBuscarAsocVin"><i class="ri-search-line"></i></button>
                                </div>
                                <div id="asocInfoVin" class="mt-2"></div>
                            </div>
                        </div>

                        <div class="asoc-paso">
                            <span class="asoc-num">2</span>
                            <div class="flex-grow-1">
                                <label class="form-label" for="asoc_motor">Número de motor</label>
                                <input type="text" class="form-control mono text-uppercase" id="asoc_motor" maxlength="30" placeholder="Número de motor" disabled>
                            </div>
                        </div>

                        <div class="asoc-paso mb-0">
                            <span class="asoc-num">3</span>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label" for="asoc_origen">VIN de origen (fabricante)</label>
                                    <span class="fs-12 text-muted mono" id="asocOrigenContador">0/17</span>
                                </div>
                                <input type="text" class="form-control mono text-uppercase" id="asoc_origen" maxlength="17" placeholder="17 caracteres, sin I, O ni Q" disabled>
                                <div class="invalid-feedback" id="asocOrigenError"></div>
                            </div>
                        </div>

                        <div class="asoc-paso mb-0 mt-3">
                            <span class="asoc-num">4</span>
                            <div class="flex-grow-1">
                                <label class="form-label" for="asoc_color">Color <span class="text-muted fw-normal fs-12">(opcional)</span></label>
                                <input type="text" class="form-control" id="asoc_color" maxlength="30" list="asocColores" placeholder="Ej. Blanco" disabled>
                                <datalist id="asocColores"></datalist>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="asocContinuar">
                            <label class="form-check-label fs-13" for="asocContinuar">Seguir capturando al guardar</label>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-success" id="btnGuardarAsociacion" disabled>
                                <i class="ri-save-3-line align-bottom me-1"></i> Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ================= MODAL CARGA MASIVA ================= -->
    <div class="modal fade" id="modalCargaAsociacion" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0">Carga masiva de asociaciones</h5>
                        <p class="text-muted mb-0 fs-12">VIN generado · número de motor · VIN de origen · color</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Paso 1: elegir lotes / órdenes y descargar -->
                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0 flex-grow-1"><span class="carga-paso">1</span> Elige los lotes u órdenes a descargar</h6>
                        <a class="fs-12 link-secondary" href="<?= base_url(); ?>/Inv_series/descargarPlantillaAsociacion">
                            <i class="ri-file-line align-bottom"></i> Plantilla en blanco
                        </a>
                    </div>
                    <div class="border rounded mb-3">
                        <div class="p-2 border-bottom d-flex flex-wrap gap-2 align-items-center">
                            <div class="search-box flex-grow-1" style="min-width:200px">
                                <input type="text" class="form-control form-control-sm" id="buscarRefAsoc" placeholder="Buscar lote, orden o modelo...">
                                <i class="ri-search-line search-icon"></i>
                            </div>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="filtroTipoRef" id="refTipoTodos" value="" checked>
                                <label class="btn btn-outline-light text-body" for="refTipoTodos">Todos</label>
                                <input type="radio" class="btn-check" name="filtroTipoRef" id="refTipoLote" value="lote">
                                <label class="btn btn-outline-light text-body" for="refTipoLote">Lotes</label>
                                <input type="radio" class="btn-check" name="filtroTipoRef" id="refTipoOrden" value="orden">
                                <label class="btn btn-outline-light text-body" for="refTipoOrden">Órdenes</label>
                                <input type="radio" class="btn-check" name="filtroTipoRef" id="refTipoCarga" value="carga">
                                <label class="btn btn-outline-light text-body" for="refTipoCarga">Carga inicial</label>
                                <input type="radio" class="btn-check" name="filtroTipoRef" id="refTipoImportado" value="importado">
                                <label class="btn btn-outline-light text-body" for="refTipoImportado">Importadas</label>
                            </div>
                        </div>
                        <div class="ref-list" id="listaRefsAsoc">
                            <div class="text-center text-muted py-4 fs-13">Cargando...</div>
                        </div>
                        <div class="p-2 border-top d-flex flex-wrap gap-2 align-items-center bg-light-subtle">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="soloPendientesRef" checked>
                                <label class="form-check-label fs-13" for="soloPendientesRef">Solo VIN sin asociar</label>
                            </div>
                            <span class="ms-auto fs-12 text-muted" id="resumenRefsAsoc">Ninguno seleccionado</span>
                            <button type="button" class="btn btn-sm btn-primary" id="btnDescargarRefs" disabled>
                                <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                            </button>
                        </div>
                    </div>

                    <!-- Paso 2: subir -->
                    <h6 class="mb-2"><span class="carga-paso">2</span> Llena el motor, el VIN de origen y el color, y sube el archivo</h6>
                    <div class="alert alert-info fs-13 py-2 mb-2">
                        Si un VIN ya tiene asociación y el archivo trae datos distintos, se <strong>actualiza</strong> y el cambio queda en su historial.
                        Las filas con motor y VIN de origen vacíos se ignoran. El color es opcional: si viene vacío se conserva el que ya tenga.
                    </div>
                    <form id="formCargaAsociacion" onsubmit="return false;">
                        <label class="form-label fs-13 text-muted" for="archivoAsociacion">Archivo (.xlsx, .xls o .csv)</label>
                        <input type="file" class="form-control" id="archivoAsociacion" name="archivo" accept=".xlsx,.xls,.csv">
                    </form>
                    <div id="resultadoCargaAsociacion" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" id="btnProcesarAsociacion">
                        <i class="ri-upload-2-line align-bottom me-1"></i> Procesar archivo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL UNIDADES IMPORTADAS ================= -->
    <div class="modal fade" id="modalImportados" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0">Unidades importadas</h5>
                        <p class="text-muted mb-0 fs-12">Sin VIN nacional · el VIN de la unidad es el VIN de origen del fabricante</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info fs-13 py-2 mb-3">
                        Cada fila da de alta la unidad con su <strong>VIN de origen como VIN</strong>, su número de motor y color,
                        y la <strong>entrada al inventario</strong> en el almacén indicado (concepto Ajustes). Las unidades con VIN nacional
                        (3LD...) se siguen generando con <em>Generar nuevos VIN</em> y se asocian con <em>Carga masiva</em>.
                    </div>

                    <div class="d-flex align-items-center mb-2">
                        <h6 class="mb-0 flex-grow-1"><span class="carga-paso">1</span> Descarga la plantilla</h6>
                    </div>
                    <p class="fs-13 text-muted mb-2">
                        Columnas: <span class="mono">SKU</span>, <span class="mono">VIN_ORIGEN</span>, <span class="mono">NUMERO_MOTOR</span>,
                        <span class="mono">COLOR</span> (opcional) y <span class="mono">ALMACEN</span>. El SKU y el almacén se eligen de una lista.
                    </p>
                    <a class="btn btn-sm btn-soft-primary mb-3" href="<?= base_url(); ?>/Inv_series/descargarPlantillaImportados">
                        <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                    </a>

                    <h6 class="mb-2"><span class="carga-paso">2</span> Sube el archivo lleno</h6>
                    <form id="formImportados" onsubmit="return false;">
                        <label class="form-label fs-13 text-muted" for="archivoImportados">Archivo (.xlsx, .xls o .csv)</label>
                        <input type="file" class="form-control" id="archivoImportados" name="archivo" accept=".xlsx,.xls,.csv">
                    </form>
                    <div id="resultadoImportados" class="mt-3"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="btnProcesarImportados">
                        <i class="ri-upload-2-line align-bottom me-1"></i> Procesar archivo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= MODAL HISTORIAL ================= -->
    <div class="modal fade" id="modalHistorialVin" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header border-bottom">
                    <div>
                        <h5 class="modal-title mb-0">Historial del VIN <span class="mono text-primary" id="historialVinTitulo"></span></h5>
                        <p class="text-muted mb-0 fs-12">Generación, asociación de motor / VIN de origen y cambios del VIN base</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="historialVinBody"></div>
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
<!-- end main content-->
<?php footerAdmin($data); ?>
