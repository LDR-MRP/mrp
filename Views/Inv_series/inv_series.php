<?php headerAdmin($data);

?>

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

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Generación de números de VIN</h5>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist" id="nav-tab">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#listSeries" role="tab">
                                            VINES
                                        </a>
                                    </li>
                                    <?php if ($_SESSION['permisosMod']['w']) { ?>
                                        <li class="nav-item">
                                            <a class="nav-link" data-bs-toggle="tab" href="#agregarSerie" role="tab">
                                                GENERAR
                                            </a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </div>

                            <div class="card-body">
                                <div class="tab-content">
                                    <!-- TAB LISTADO -->
                                    <div class="tab-pane active" id="listSeries" role="tabpanel">
                                        <table id="tableSeries"
                                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                            style="width:100%">
                                            <thead>
                                                <tr>
                                                    <th>PRODUCTO</th>
                                                    <th>ALMACÉN</th>
                                                    <th>NÚMERO VIN</th>
                                                    <th>REFERENCIA</th>
                                                    <th>FECHA</th>
                                                    <th>ESTADO</th>
                                                    <th>PDF</th>
                                                </tr>
                                            </thead>
                                        </table>
                                    </div>

                                    <!-- TAB GENERAR -->
                                    <div class="tab-pane" id="agregarSerie" role="tabpanel">

                                        <div class="col-lg-6 mt-3">
                                            <label>Modo de generación</label>
                                            <select id="modoGeneracion" class="form-select">
                                                <option value="orden">Por orden de trabajo</option>
                                                <option value="lote">Por lote</option>
                                            </select>
                                        </div>

                                        <form id="formSeries">
                                            <div id="bloqueOrden">
                                                <div class="row">

                                                    <!-- ORDEN DE TRABAJO -->
                                                    <div class="col-lg-6 mt-3 position-relative">
                                                        <label>Orden de trabajo</label>

                                                        <input type="hidden" name="inventarioid" id="inventarioid">
                                                        <input type="hidden" name="referencia" id="referencia">

                                                        <input type="text"
                                                            class="form-control ordenSearch"
                                                            id="ordenSearch"
                                                            placeholder="Buscar orden de trabajo..."
                                                            autocomplete="off"
                                                            required>

                                                        <div id="listaOrdenes" class="list-group"></div>
                                                    </div>

                                                    <!-- PRODUCTO -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Producto</label>
                                                        <input type="text"
                                                            class="form-control"
                                                            id="productoNombre"
                                                            readonly>
                                                    </div>

                                                    <!-- ALMACÉN -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Almacén</label>
                                                        <select class="form-select" name="almacenid" id="almacenid" required></select>
                                                    </div>

                                                    <!-- MODELO VIN -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Modelo VIN</label>
                                                        <select class="form-select" name="modelo_vin" id="modelo_vin" required>
                                                            <option value="">Seleccione modelo VIN</option>
                                                        </select>
                                                    </div>

                                                    <!-- VIN BASE -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>VIN Base</label>
                                                        <input type="text"
                                                            class="form-control"
                                                            id="vinBasePreview"
                                                            readonly>

                                                        <small class="text-muted">
                                                            Se genera automáticamente con el modelo seleccionado
                                                        </small>
                                                    </div>

                                                    <!-- CANTIDAD -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Cantidad a generar</label>
                                                        <input type="text"
                                                            id="cantidadPreview"
                                                            class="form-control"
                                                            readonly>
                                                    </div>

                                                </div>

                                                <!-- INPUTS OCULTOS -->
                                                <input type="hidden" id="vin_parte_1_8">
                                                <input type="hidden" id="vin_anio">
                                                <input type="hidden" id="vin_planta">

                                                <input type="hidden" id="cantidadOrden" name="cantidad">
                                            </div>

                                            <div id="bloqueLote" style="display:none;">
                                                <div class="row">

                                                    <!-- LOTE -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Lote</label>
                                                        <input type="text" id="lote" name="lote" class="form-control">
                                                    </div>

                                                    <div class="col-lg-6 mt-3 position-relative">
                                                        <label>Producto</label>

                                                        <input type="hidden" id="inventarioid_lote">

                                                        <input type="text"
                                                            class="form-control productoSearch"
                                                            id="productoSearchLote"
                                                            placeholder="Buscar producto..."
                                                            autocomplete="off"
                                                            required>

                                                        <div id="listaProductos" class="list-group position-absolute w-100"></div>
                                                    </div>

                                                    <!-- ALMACÉN -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Almacén</label>
                                                        <select class="form-select" id="almacenid_lote"></select>
                                                    </div>

                                                    <!-- MODELO VIN -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Modelo VIN</label>
                                                        <select class="form-select" id="modelo_vin_lote">
                                                            <option value="">Seleccione modelo VIN</option>
                                                        </select>
                                                    </div>

                                                    <!-- VIN BASE -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>VIN Base</label>
                                                        <input type="text" id="vinBasePreview_lote" class="form-control" readonly>
                                                    </div>

                                                    <!-- CANTIDAD -->
                                                    <div class="col-lg-6 mt-3">
                                                        <label>Cantidad</label>
                                                        <input type="number" id="cantidad_lote" class="form-control">
                                                    </div>
                                                </div>

                                            </div>


                                            <!-- PREVIEW FINAL -->
                                            <div class="mt-4 text-center">
                                                <h5>VIN Generado (Preview)</h5>
                                                <h3 id="vinPreviewFinal" class="text-primary">-----------------</h3>
                                            </div>


                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="button" id="btnPreview"
                                                    class="btn btn-primary">
                                                    PREVISUALIZAR
                                                </button>
                                            </div>
                                        </form>

                                    </div>

                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>



            <!--end row-->

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->


    <div class="modal fade" id="modalPreviewSeries" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Previsualización de Series</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div id="previewContainer" class="row"></div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-success" id="btnConfirmSeries">
                        Confirmar Registro
                    </button>
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