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


            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0 " role="tablist" id="nav-tab">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listmovimiento" role="tab">
                                MOVIMIENTOS
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarMovimiento" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listmovimiento" role="tabpanel">

                            <table id="tableMovimientos"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Producto</th>
                                        <th>Almacén</th>
                                        <th>Concepto</th>
                                        <th>Referencia</th>
                                        <th>Cantidad</th>
                                        <th>Existencia</th>
                                        <th>Fecha</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>


                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarMovimiento" role="tabpanel">
                            <form id="formMovimiento" autocomplete="off" class="was-validated">

                                <input type="hidden" name="idmovinventario" value="0">

                                <div class="row g-3 mb-3">

                                    <div class="col-md-4">
                                        <label class="form-label">Concepto movimiento</label>
                                        <select class="form-control" id="concepmovid" name="concepmovid" required></select>
                                    </div>

                                    <div class="col-md-4" id="bloqueCPN" style="display:none;">
                                        <label class="form-label" id="labelCPN">Tipo</label>
                                        <input
                                            type="text"
                                            class="form-control"
                                            id="cpnValor"
                                            disabled
                                            readonly>
                                    </div>


                                    <div class="col-md-4">
                                        <label class="form-label">Almacén</label>
                                        <select class="form-control" id="almacenid" name="almacenid" required></select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label">Referencia</label>
                                        <input type="text" class="form-control" id="referencia" name="referencia" required>
                                    </div>

                                </div>


                                <div class="card mt-3 shadow-sm">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <span class="fw-semibold">Detalle del movimiento</span>
                                        <button type="button" class="btn btn-sm btn-primary" id="btnAddRow">
                                            + Agregar partida
                                        </button>
                                    </div>

                                    <div class="card-body p-2">

                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover align-middle mb-0" id="tablaPartidas">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th width="90">Cantidad</th>
                                                        <th>Producto</th>
                                                        <th width="120">Costo por unidad</th>
                                                        <th width="120">Total</th>
                                                        <th width="50"></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td><input name="cantidad[]" type="number" class="form-control cantidad"></td>
                                                        <td>
                                                            <input type="text" class="form-control invSearch">
                                                            <input type="hidden" name="inventarioid[]">
                                                        </td>
                                                        <td><input name="costo_cantidad[]" type="number" class="form-control costo"></td>
                                                        <td><input name="total[]" type="number" class="form-control total" readonly></td>
                                                        <td><button type="button" class="btn btn-danger btn-sm btnDel">X</button></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>

                                    </div>
                                </div>
                                <div class="row mt-3 align-items-center">
                                    <div class="col-md-6"></div>

                                    <div class="col-md-3 text-end fw-semibold">
                                        Total movimiento:
                                    </div>

                                    <div class="col-md-3">
                                        <input type="text" class="form-control text-end" id="granTotal" readonly value="0.00">
                                    </div>
                                </div>

                                <div class="text-end mt-4">
                                    <button class="btn btn-success px-4" type="submit">
                                        Registrar movimiento
                                    </button>
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



<div class="modal fade" id="modalViewPrecio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Clave</td>
                            <td id="celClave"></td>
                        </tr>
                        <tr>
                            <td>Descripción</td>
                            <td id="celDescripcion"></td>
                        </tr>
                        <tr>
                            <td>Impuesto</td>
                            <td id="celImpuesto"></td>
                        </tr>
                        <tr>
                            <td>Fecha creación</td>
                            <td id="celFecha"></td>
                        </tr>
                        <tr>
                            <td>Estado</td>
                            <td id="celEstado"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>








<!-- end main content-->
<?php footerAdmin($data); ?>