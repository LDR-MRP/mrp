<?php headerAdmin($data);
?>
<style>
    .kpi-matriz {
        display: flex; flex-direction: column; gap: .15rem; height: 100%;
        padding: .75rem 1rem; border: 1px solid var(--vz-border-color); border-radius: .5rem;
        background: var(--vz-card-bg);
    }
    .kpi-matriz .kpi-label { font-size: .75rem; color: var(--vz-secondary-color, #878a99); }
    .kpi-matriz .kpi-valor { font-size: 1.35rem; font-weight: 600; font-variant-numeric: tabular-nums; }

    .matriz-scroll { overflow-x: auto; border: 1px solid var(--vz-border-color); border-radius: .375rem; }
    .tabla-matriz { border-collapse: separate; border-spacing: 0; }
    .tabla-matriz th, .tabla-matriz td { white-space: nowrap; }
    .tabla-matriz thead th { font-size: .72rem; vertical-align: bottom; background: var(--vz-light); }
    .tabla-matriz td.num, .tabla-matriz th.num { text-align: right; font-variant-numeric: tabular-nums; }
    /* Clave y descripcion fijas al desplazar a la derecha */
    /* !important: DataTables pone position:relative a los encabezados ordenables */
    .tabla-matriz th.fija, .tabla-matriz td.fija { position: sticky !important; z-index: 2; background: var(--vz-card-bg); }
    .tabla-matriz thead th.fija { background: var(--vz-light); z-index: 3; }
    .tabla-matriz .fija-1 { left: 0; }
    .tabla-matriz .fija-2 { left: var(--ancho-fija-1, 160px); max-width: 280px; overflow: hidden; text-overflow: ellipsis;
                            box-shadow: inset -1px 0 0 var(--vz-border-color); }
    .tabla-matriz td.col-total { font-weight: 600; background: rgba(var(--vz-primary-rgb), .06); }
    .tabla-matriz th.col-almacen .planta { display: block; font-weight: 400; color: var(--vz-secondary-color, #878a99); }
    .tabla-matriz td .cero { color: var(--vz-secondary-color, #878a99); opacity: .5; }
    .tabla-matriz td .negativo { color: var(--vz-danger); font-weight: 600; }
    .celda-bajo-min { background: rgba(var(--vz-warning-rgb), .18); border-radius: .25rem; }
    .tabla-matriz tbody tr { cursor: pointer; }
    .tipo-badge { font-size: .68rem; }
    /* Vista desglose: un chip por almacén con existencia */
    .tabla-matriz td.col-desglose { white-space: normal; min-width: 260px; }
    .chip-alm { display: inline-flex; align-items: center; gap: .35rem; margin: .1rem .25rem .1rem 0;
                padding: .1rem .45rem; border: 1px solid var(--vz-border-color); border-radius: 1rem;
                font-size: .72rem; line-height: 1.4; background: var(--vz-light); }
    .chip-alm b { font-variant-numeric: tabular-nums; }
    .chip-alm.bajo-min { background: rgba(var(--vz-warning-rgb), .18); border-color: rgba(var(--vz-warning-rgb), .5); }
    .chip-alm.negativo b { color: var(--vz-danger); }
</style>
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
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" id="nav-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listMultialmacenes" role="tab">
                                MULTIALMACENES
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarMultialmacen" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-outline-success" id="btnExistencias">
                                <i class="ri-eye-line me-1"></i> EXISTENCIAS
                            </button>
                        </div>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listMultialmacenes" role="tabpanel">

                            <!-- KPIs (se recalculan con los filtros) -->
                            <div class="row g-3 mb-3" id="kpisMatriz">
                                <div class="col-6 col-lg-3">
                                    <div class="kpi-matriz">
                                        <span class="kpi-label">Artículos</span>
                                        <span class="kpi-valor" id="kpiArticulos">—</span>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <div class="kpi-matriz">
                                        <span class="kpi-label">Con existencia</span>
                                        <span class="kpi-valor" id="kpiConExistencia">—</span>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <div class="kpi-matriz">
                                        <span class="kpi-label">Unidades en almacenes</span>
                                        <span class="kpi-valor" id="kpiUnidades">—</span>
                                    </div>
                                </div>
                                <div class="col-6 col-lg-3">
                                    <div class="kpi-matriz">
                                        <span class="kpi-label">Almacenes con existencia</span>
                                        <span class="kpi-valor" id="kpiAlmacenes">—</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Filtros -->
                            <div class="row g-2 align-items-end mb-3">
                                <div class="col-sm-6 col-lg-2">
                                    <label class="form-label fs-12 text-muted mb-1" for="filtroTipoMatriz">Tipo</label>
                                    <select class="form-select form-select-sm" id="filtroTipoMatriz">
                                        <option value="">Todos</option>
                                    </select>
                                </div>
                                <div class="col-sm-6 col-lg-2">
                                    <label class="form-label fs-12 text-muted mb-1" for="filtroPlantaMatriz">Planta</label>
                                    <select class="form-select form-select-sm" id="filtroPlantaMatriz">
                                        <option value="">Todas</option>
                                    </select>
                                </div>
                                <div class="col-lg-4 d-flex flex-wrap gap-3 pb-1">
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="soloExistenciaMatriz">
                                        <label class="form-check-label fs-13" for="soloExistenciaMatriz">Solo artículos con existencia</label>
                                    </div>
                                    <div class="form-check form-switch mb-0">
                                        <input class="form-check-input" type="checkbox" id="ocultarVaciosMatriz" checked>
                                        <label class="form-check-label fs-13" for="ocultarVaciosMatriz">Ocultar almacenes sin existencia</label>
                                    </div>
                                </div>
                                <div class="col-lg-4 d-flex justify-content-lg-end align-items-center gap-2">
                                    <div class="btn-group btn-group-sm" role="group" aria-label="Vista">
                                        <input type="radio" class="btn-check" name="vistaMatriz" id="vistaMatriz" value="matriz" checked>
                                        <label class="btn btn-outline-primary" for="vistaMatriz" title="Una columna por almacén">
                                            <i class="ri-grid-line align-bottom"></i> Matriz</label>
                                        <input type="radio" class="btn-check" name="vistaMatriz" id="vistaDesglose" value="desglose">
                                        <label class="btn btn-outline-primary" for="vistaDesglose" title="Total y solo los almacenes con existencia">
                                            <i class="ri-list-check-2 align-bottom"></i> Desglose</label>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-soft-success" id="btnExportarMatriz">
                                        <i class="ri-file-excel-2-line align-bottom me-1"></i> Exportar a Excel
                                    </button>
                                </div>
                            </div>

                            <table id="tableMultialmacenes"
                                class="table table-bordered table-hover table-sm align-middle mb-0 tabla-matriz"
                                style="width:100%">
                                <thead></thead>
                                <tbody></tbody>
                            </table>
                            <div class="fs-12 text-muted mt-2">
                                <i class="ri-information-line align-bottom"></i>
                                Clic en un artículo para ver el detalle por almacén y ajustar stock mínimo / máximo.
                                <span class="celda-bajo-min px-1 ms-2">12</span> = por debajo del stock mínimo.
                            </div>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarMultialmacen" role="tabpanel">
                            <form id="formMultialmacenes" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idmultialmacen" name="idmultialmacen">
                                <div class="row">

                                    <!-- LISTA DE INVENTARIOS -->
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listInventario">INVENTARIO</label>
                                            <input type="hidden" id="listInventario" name="listInventario">

                                            <div class="position-relative">
                                                <input
                                                    type="text"
                                                    id="inventarioSearch"
                                                    class="form-control"
                                                    placeholder="Buscar inventario..."
                                                    autocomplete="off" required />
                                                <div class="invalid-feedback">El campo de inventario es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- LISTA DE ALMACENES -->
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listAlmacenes">ALMACÉN</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Alm</span>
                                                <select class="form-control" id="listAlmacenes" name="listAlmacenes" required=""></select>
                                                <div class="invalid-feedback">El campo de precios es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- EXISTENCIA -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="existencia-input">EXISTENCIA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="simbolo-moneda-addon">Exis.</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa la existencia" id="existencia-input" name="existencia-input"
                                                    aria-describedby="simbolo-moneda-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- STOCK MINIMO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="stockminimo-input">STOCK MINIMO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="simbolo-moneda-addon">Exis.</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa la existencia" id="stockminimo-input" name="stockminimo-input"
                                                    aria-describedby="simbolo-moneda-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- STOCK MAXIMO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="stockmaximo-input">STOCK MAXIMO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="simbolo-moneda-addon">Exis.</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa la existencia" id="stockmaximo-input" name="stockmaximo-input"
                                                    aria-describedby="simbolo-moneda-addon">
                                            </div>
                                        </div>
                                    </div>


                                </div>
                                <!-- end row -->




                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="submit" id="btnActionForm"
                                        class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                        data-nexttab="steparrow-description-info-tab"><i
                                            class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i><span id="btnText">REGISTRAR</span></button>
                                </div>


                            </form>
                        </div>

                        <!-- end tab pane -->
                    </div>
                    <!-- end tab content -->
                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->


            <!--end row-->

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


<div class="modal fade" id="modalViewMultialmacen" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModal">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Inventario:</td>
                            <td id="celinventario"></td>
                        </tr>
                        <tr>
                            <td>Almacenes:</td>
                            <td id="celalmacenes"></td>
                        </tr>
                        <tr>
                            <td>Existencias:</td>
                            <td id="celexistencias"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <div class="hstack gap-2 justify-content-end">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <!-- <button type="submit" id="btnActionForm" class="btn btn-success">
        <span id="btnText">Guardar</span>
      </button> -->
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalExistencias" tabindex="-1"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title">Consulta de Existencias</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="d-flex mb-3">
                    <button class="btn btn-primary" id="btnAbrirBusqueda" type="button">
                        🔍 Buscar artículo
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th>Clave</th>
                                <th>Descripción</th>
                                <th>Almacén</th>
                                <th>Existencia</th>
                                <th>Min</th>
                                <th>Max</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="bodyExistencias"></tbody>
                    </table>
                </div>

                <div class="mt-3 text-end fw-bold">
                    Total: <span id="totalExistencias">0</span>
                </div>

            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalBuscarArticulo" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Buscar artículo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <input type="text" id="inputBuscarArticulo" class="form-control mb-3" placeholder="Clave o descripción">

                <table class="table table-hover table-sm">
                    <thead>
                        <tr>
                            <th>Clave</th>
                            <th>Descripción</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="bodyBuscarArticulo"></tbody>
                </table>

            </div>

        </div>
    </div>
</div>



<!-- end main content-->
<?php footerAdmin($data); ?>