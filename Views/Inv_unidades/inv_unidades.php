<?php headerAdmin($data);
?>
<style>
    /* ---------- KPIs ---------- */
    .iu-kpi { height: 100%; padding: 1rem 1.15rem; border: 1px solid var(--vz-border-color); border-radius: .6rem; background: var(--vz-card-bg); }
    .iu-kpi .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--vz-secondary-color, #878a99); }
    .iu-kpi .val { font-size: 1.9rem; font-weight: 600; line-height: 1.15; font-variant-numeric: tabular-nums; }
    .iu-kpi .sub { font-size: .78rem; color: var(--vz-secondary-color, #878a99); }
    .iu-chip { display: inline-flex; gap: .3rem; align-items: baseline; padding: .1rem .5rem; margin: .15rem .25rem 0 0;
               border-radius: 1rem; background: var(--vz-light); font-size: .75rem; }
    .iu-chip b { font-variant-numeric: tabular-nums; }
    .iu-stack { display: flex; height: 12px; border-radius: 6px; overflow: hidden; background: var(--vz-light); margin: .55rem 0 .45rem; }
    .iu-stack span { display: block; height: 100%; }
    .iu-leg { display: flex; flex-wrap: wrap; gap: .15rem .7rem; font-size: .72rem; }
    .iu-leg i { display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin-right: .3rem; }

    /* ---------- Barra de filtros ---------- */
    .iu-toolbar { display: flex; flex-wrap: wrap; gap: .75rem 1rem; align-items: center; }
    .iu-filtros { display: flex; flex-wrap: wrap; gap: .6rem 1.25rem; align-items: center; padding-top: .85rem; margin-top: .85rem; border-top: 1px dashed var(--vz-border-color); }
    .iu-fgrupo { display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
    .iu-fgrupo > .lbl { font-size: .72rem; text-transform: uppercase; letter-spacing: .04em; color: var(--vz-secondary-color, #878a99); margin-right: .15rem; }
    .iu-toggle { border: 1px solid var(--vz-border-color); background: var(--vz-card-bg); color: var(--vz-body-color, #212529);
                 border-radius: 1rem; padding: .15rem .65rem; font-size: .78rem; line-height: 1.5; cursor: pointer; }
    .iu-toggle:hover { border-color: rgb(var(--vz-primary-rgb)); }
    .iu-toggle.on { background: rgb(var(--vz-primary-rgb)); border-color: rgb(var(--vz-primary-rgb)); color: #fff; }
    .iu-toggle .n { opacity: .7; margin-left: .25rem; font-variant-numeric: tabular-nums; }
    .iu-busca { min-width: 240px; max-width: 320px; }

    /* ---------- Matriz por unidad ---------- */
    .iu-scroll { overflow-x: auto; border: 1px solid var(--vz-border-color); border-radius: .5rem; }
    .iu-matriz { width: 100%; border-collapse: separate; border-spacing: 0; font-size: .82rem; }
    .iu-matriz th, .iu-matriz td { padding: .45rem .6rem; border-bottom: 1px solid var(--vz-border-color); white-space: nowrap; }
    .iu-matriz thead th { background: var(--vz-light); font-size: .7rem; text-transform: uppercase; letter-spacing: .03em; color: var(--vz-secondary-color, #878a99); }
    .iu-matriz thead tr.plantas th { text-align: center; border-bottom: 2px solid var(--vz-border-color); }
    .iu-matriz thead tr.plantas th.pl { border-left: 1px solid var(--vz-border-color); color: var(--vz-body-color, #212529); }
    .iu-matriz .num { text-align: right; font-variant-numeric: tabular-nums; min-width: 64px; }
    .iu-matriz .ini-pl { border-left: 1px solid var(--vz-border-color); }
    .iu-matriz .col-mod { position: sticky; left: 0; z-index: 1; background: var(--vz-card-bg); min-width: 260px; max-width: 340px; white-space: normal; }
    .iu-matriz thead .col-mod { z-index: 2; background: var(--vz-light); }
    .iu-matriz .col-tot { font-weight: 600; background: rgba(var(--vz-primary-rgb), .07); }
    .iu-matriz tr.sub td { background: var(--vz-light); font-weight: 600; cursor: pointer; }
    .iu-matriz tr.sub td.col-mod { background: var(--vz-light); }
    .iu-matriz tr.sub .caret { display: inline-block; width: 1rem; transition: transform .15s; }
    .iu-matriz tr.sub.cerrado .caret { transform: rotate(-90deg); }
    .iu-matriz tr.fila td.col-mod { padding-left: 1.6rem; cursor: pointer; }
    .iu-matriz tr.fila:hover td { background: rgba(var(--vz-primary-rgb), .04); }
    .iu-matriz tr.fila:hover td.col-mod { background: var(--vz-card-bg); }
    .iu-matriz td.cel { cursor: pointer; }
    .iu-matriz td.cel:hover { outline: 2px solid rgba(var(--vz-primary-rgb), .45); outline-offset: -2px; }
    .iu-matriz .cero { color: var(--vz-secondary-color, #878a99); opacity: .35; }
    .iu-matriz tfoot td { background: var(--vz-light); font-weight: 700; border-top: 2px solid var(--vz-border-color); }
    .iu-mod-nom { font-weight: 500; }
    .iu-mod-cve { display: block; font-size: .7rem; color: var(--vz-secondary-color, #878a99); }
    .iu-anio { display: inline-block; padding: 0 .4rem; margin-left: .35rem; border-radius: .3rem; font-size: .7rem; font-weight: 600;
               background: rgba(var(--vz-primary-rgb), .1); color: rgb(var(--vz-primary-rgb)); vertical-align: middle; }
    .iu-color { display: inline-flex; align-items: center; gap: .3rem; margin-left: .4rem; font-size: .72rem; color: var(--vz-secondary-color, #878a99); }
    .iu-dot { display: inline-block; width: 9px; height: 9px; border-radius: 50%; border: 1px solid rgba(0, 0, 0, .25); }

    /* ---------- Vista por almacen ---------- */
    .iu-planta-tit { font-size: .78rem; text-transform: uppercase; letter-spacing: .05em; color: var(--vz-secondary-color, #878a99); margin: .25rem 0 .6rem; }
    .iu-alm { height: 100%; border: 1px solid var(--vz-border-color); border-radius: .6rem; background: var(--vz-card-bg); overflow: hidden; }
    .iu-alm-head { display: flex; justify-content: space-between; gap: .75rem; padding: .85rem 1rem .6rem; border-bottom: 1px solid var(--vz-border-color); }
    .iu-alm-head .cve { font-weight: 600; }
    .iu-alm-head .desc { font-size: .74rem; color: var(--vz-secondary-color, #878a99); }
    .iu-alm-head .tot { font-size: 1.6rem; font-weight: 600; line-height: 1; font-variant-numeric: tabular-nums; text-align: right; }
    .iu-alm-head .tot small { display: block; font-size: .68rem; font-weight: 400; color: var(--vz-secondary-color, #878a99); }
    .iu-alm-list { list-style: none; margin: 0; padding: .35rem 0; max-height: 330px; overflow-y: auto; }
    .iu-alm-list li { display: grid; grid-template-columns: 1fr auto; gap: .1rem .75rem; padding: .4rem 1rem; cursor: pointer; font-size: .8rem; }
    .iu-alm-list li:hover { background: rgba(var(--vz-primary-rgb), .05); }
    .iu-alm-list .bar { grid-column: 1 / -1; height: 4px; border-radius: 2px; background: var(--vz-light); overflow: hidden; }
    .iu-alm-list .bar span { display: block; height: 100%; border-radius: 2px; }
    .iu-alm-list .n { font-weight: 600; font-variant-numeric: tabular-nums; }
    .iu-alm-list .subl { font-size: .68rem; color: var(--vz-secondary-color, #878a99); margin-left: .35rem; }

    /* ---------- Panel de detalle ---------- */
    #iuDetalle { width: min(820px, 100vw); }
    #iuDetalle .resumen { display: flex; flex-wrap: wrap; gap: .25rem; }
    #iuDetalle table { font-size: .78rem; }
    #iuDetalle td.vin { font-family: var(--bs-font-monospace, monospace); font-weight: 600; }

    .iu-vacio { padding: 3rem 1rem; text-align: center; color: var(--vz-secondary-color, #878a99); }
    .iu-oculto { position: absolute; left: -99999px; top: 0; }
    @media (max-width: 575.98px) {
        .iu-busca { min-width: 0; max-width: none; width: 100%; }
        .iu-matriz .col-mod { min-width: 180px; }
    }
</style>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $data['page_title'] ?></h4>
                            <div class="fs-12 text-muted mt-1" id="iuGenerado">Cargando…</div>
                        </div>
                        <div class="d-flex gap-2 mt-2 mt-sm-0">
                            <button type="button" class="btn btn-sm btn-soft-success" id="btnExcel">
                                <i class="ri-file-excel-2-line align-bottom me-1"></i> Exportar Excel
                            </button>
                            <button type="button" class="btn btn-sm btn-soft-danger" id="btnPDF">
                                <i class="ri-file-pdf-2-line align-bottom me-1"></i> Reporte PDF
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- KPIs: siguen a los filtros -->
            <div class="row g-3 mb-3">
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi">
                        <div class="lbl">Unidades en inventario</div>
                        <div class="val" id="kUnidades">—</div>
                        <div id="kAnios"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi">
                        <div class="lbl">Modelos</div>
                        <div class="val" id="kModelos">—</div>
                        <div class="sub" id="kModelosSub"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi">
                        <div class="lbl">Almacenes con unidades</div>
                        <div class="val" id="kAlmacenes">—</div>
                        <div class="sub" id="kAlmacenesSub"></div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-3">
                    <div class="iu-kpi">
                        <div class="lbl">Composición por sublínea</div>
                        <div class="iu-stack" id="kStack"></div>
                        <div class="iu-leg" id="kLeyenda"></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="iu-toolbar">
                        <div class="btn-group btn-group-sm" role="group" aria-label="Vista">
                            <input type="radio" class="btn-check" name="iuVista" id="vUnidad" value="unidad" checked>
                            <label class="btn btn-outline-primary" for="vUnidad"><i class="ri-car-line align-bottom me-1"></i>Por unidad</label>
                            <input type="radio" class="btn-check" name="iuVista" id="vAlmacen" value="almacen">
                            <label class="btn btn-outline-primary" for="vAlmacen"><i class="ri-building-2-line align-bottom me-1"></i>Por almacén</label>
                            <input type="radio" class="btn-check" name="iuVista" id="vListado" value="listado">
                            <label class="btn btn-outline-primary" for="vListado"><i class="ri-list-unordered align-bottom me-1"></i>Listado de VIN</label>
                        </div>
                        <div class="position-relative iu-busca">
                            <input type="search" class="form-control form-control-sm ps-4" id="fBuscar" placeholder="Buscar modelo, clave o VIN">
                            <i class="ri-search-line position-absolute top-50 translate-middle-y text-muted" style="left:.55rem"></i>
                        </div>
                        <div class="form-check form-switch mb-0" id="wrapPorColor">
                            <input class="form-check-input" type="checkbox" id="fPorColor">
                            <label class="form-check-label fs-13" for="fPorColor">Separar por color</label>
                        </div>
                        <button type="button" class="btn btn-sm btn-link text-decoration-none ms-auto d-none" id="btnLimpiar">
                            <i class="ri-close-circle-line align-bottom"></i> Limpiar filtros (<span id="nFiltros">0</span>)
                        </button>
                    </div>
                    <div class="iu-filtros">
                        <div class="iu-fgrupo"><span class="lbl">Sublínea</span><span id="fSublineas"></span></div>
                        <div class="iu-fgrupo"><span class="lbl">Año modelo</span><span id="fAnios"></span></div>
                        <div class="iu-fgrupo">
                            <span class="lbl">Color</span>
                            <select class="form-select form-select-sm w-auto" id="fColor"><option value="">Todos</option></select>
                        </div>
                        <div class="iu-fgrupo">
                            <span class="lbl">Planta</span>
                            <select class="form-select form-select-sm w-auto" id="fPlanta"><option value="">Todas</option></select>
                        </div>
                        <div class="iu-fgrupo">
                            <span class="lbl">Almacén</span>
                            <select class="form-select form-select-sm w-auto" id="fAlmacen"><option value="">Todos</option></select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div id="panelUnidad">
                        <div class="iu-scroll"><table class="iu-matriz" id="tMatriz"></table></div>
                        <div class="fs-12 text-muted mt-2">
                            <i class="ri-cursor-line align-bottom"></i> Clic en un número para ver los VIN de ese modelo en ese almacén; clic en el modelo para ver todas sus unidades.
                            Clic en una sublínea para contraerla.
                        </div>
                    </div>
                    <div id="panelAlmacen" class="d-none"></div>
                    <div id="panelListado" class="d-none">
                        <table id="tListado" class="table table-sm table-hover align-middle mb-0" style="width:100%">
                            <thead>
                                <tr>
                                    <th>VIN</th>
                                    <th>Año</th>
                                    <th>Color</th>
                                    <th>Sublínea</th>
                                    <th>Modelo</th>
                                    <th>Clave</th>
                                    <th>Almacén</th>
                                    <th>No. motor</th>
                                    <th>VIN origen</th>
                                    <th>Referencia</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="fs-12 text-muted mt-3 border-top pt-2">
                        Existencia = unidades físicas (VIN) de productos activos. El año modelo es la posición 10 del VIN; la sublínea es la asignada en Ingeniería.
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Detalle de VIN (panel lateral) -->
    <div class="offcanvas offcanvas-end" tabindex="-1" id="iuDetalle" aria-labelledby="iuDetalleTit">
        <div class="offcanvas-header border-bottom align-items-start">
            <div>
                <h5 class="offcanvas-title mb-1" id="iuDetalleTit">Unidades</h5>
                <div class="fs-12 text-muted" id="iuDetalleSub"></div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="resumen" id="iuDetalleResumen"></div>
                <button type="button" class="btn btn-sm btn-soft-success flex-shrink-0" id="btnDetalleCSV">
                    <i class="ri-download-2-line align-bottom"></i> CSV
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr><th>VIN</th><th>Año</th><th>Color</th><th>Almacén</th><th>No. motor</th><th>VIN origen</th></tr>
                    </thead>
                    <tbody id="iuDetalleBody"></tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="iu-oculto" id="iuExport"></div>

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
<?php footerAdmin($data); ?>
