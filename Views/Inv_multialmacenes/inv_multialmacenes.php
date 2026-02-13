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

                            <table id="tableMultialmacenes"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>INVENTARIO</th>
                                        <th>ALMACÉN</th>
                                        <th>EXISTENCIA</th>

                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

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