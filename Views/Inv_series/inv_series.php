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
                            <h5>Generación de Números de Serie</h5>
                        </div>
                        <div class="card">
                            <div class="card-header">
                                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist" id="nav-tab">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-bs-toggle="tab" href="#listSeries" role="tab">
                                            SERIES
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
                                                    <th>NÚMERO SERIE</th>
                                                    <th>REFERENCIA</th>
                                                    <th>COSTO</th>
                                                    <th>FECHA</th>
                                                    <th>ESTADO</th>
                                                    <th>PDF</th>
                                                </tr>
                                            </thead>
                                        </table>

                                    </div>

                                    <!-- TAB GENERAR -->
                                    <div class="tab-pane" id="agregarSerie" role="tabpanel">

                                        <form id="formSeries">

                                            <div class="row">

                                                <div class="col-lg-6">
                                                    <input type="hidden" name="inventarioid" id="inventarioid">

                                                    <label>Producto</label>
                                                    <input type="text"
                                                        class="form-control invSearchSerie"
                                                        id="productoSearch"
                                                        placeholder="Buscar producto..."
                                                        autocomplete="off"
                                                        required>

                                                    <div id="listaProductos" class="list-group"></div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <label>Almacén</label>
                                                    <select class="form-select" name="almacenid" id="almacenid" required></select>
                                                </div>

                                                <div class="col-lg-4 mt-3">
                                                    <label>Prefijo</label>
                                                    <input type="text" class="form-control" name="prefijo" required>
                                                </div>

                                                <div class="col-lg-4 mt-3">
                                                    <label>Cantidad</label>
                                                    <input type="number" class="form-control" name="cantidad" min="1" required>
                                                </div>

                                                <div class="col-lg-4 mt-3">
                                                    <label>Costo</label>
                                                    <input type="number" step="0.01" class="form-control" name="costo">
                                                </div>

                                                <div class="col-lg-6 mt-3">
                                                    <label>Referencia</label>
                                                    <input type="text" class="form-control" name="referencia">
                                                </div>

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