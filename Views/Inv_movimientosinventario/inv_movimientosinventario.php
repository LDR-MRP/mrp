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

                                <div class="row">

                                    <!-- PRODUCTO -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="inventarioid">PRODUCTO</label>
                                                <input type="text" class="form-control" id="inventarioSearchMov" placeholder="Buscar por clave o descripción" required>
                                                <input type="hidden" id="inventarioid" name="inventarioid">
                                                <div class="invalid-feedback">El campo producto es obligatorio</div>
                                            
                                        </div>
                                    </div>

                                    <!-- ALMACÉN -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="almacenid">ALMACÉN</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="almacen-addon">Alm</span>
                                                <select class="form-control" id="almacenid" name="almacenid" data-choices required=""></select>
                                                <div class="invalid-feedback">El campo almacén es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CONCEPTO -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="concepmovid">CONCEPTO MOVIMIENTO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="concep-mov-addon">Con. mov</span>
                                                <select class="form-control" id="concepmovid" name="concepmovid" data-choices required=""></select>
                                                <div class="invalid-feedback">El campo concepto movimiento es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- REFERENCIA -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="referencia">REFERENCIA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="referencia-addon">Ref</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la referencia" id="referencia" name="referencia"
                                                    aria-describedby="referencia-addon" required>
                                                <div class="invalid-feedback">El campo referencia es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- CANTIDAD -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="cantidad">CANTIDAD</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="cantidad-addon">Cant</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa la cantidad" id="cantidad" name="cantidad"
                                                    aria-describedby="cantidad-addon" required>
                                                <div class="invalid-feedback">El campo cantidad es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- COSTO DE LA CANTIDAD -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="costo_cantidad">COSTO DE LA CANTIDAD</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="costo_cantidad-addon">Cos.Cant</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa el costo de la cantidad" id="costo_cantidad" name="costo_cantidad"
                                                    aria-describedby="costo_cantidad-addon" required>
                                                <div class="invalid-feedback">El campo costo de la cantidad es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <button class="btn btn-success" type="submit">
                                    Registrar movimiento
                                </button>

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