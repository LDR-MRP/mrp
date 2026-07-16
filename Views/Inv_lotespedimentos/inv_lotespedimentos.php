<?php headerAdmin($data);
?>
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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listLotesPedimentos" role="tab">
                                VER
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarLote" role="tab">
                                    +LOTE
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarPedimento" role="tab">
                                    +PEDIMENTO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listLotesPedimentos" role="tabpanel">

                            <table id="tableLotesPedimentos"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>LOTE</th>
                                        <th>PEDIMENTO</th>
                                        <th>ALMACÉN</th>
                                        <th>ADUANA</th>
                                        <th>CANTIDAD</th>
                                        <th>CIUDAD</th>
                                        <th>FRONTERA</th>
                                        <th>GLN</th>
                                        <th>FECHA CADUCIDAD</th>
                                        <th>FECHA CREACIÓN</th>
                                        <th>ESTATUS</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->



                        <!--FORMULARIO ALTA DE PRODUCTOS-->
                        <div class="tab-pane" id="agregarLote" role="tabpanel">
                            <?php include_once('forms/inv_form_lote.php'); ?>
                        </div>

                        <!--FORMULARIO ALTA DE SERVICIOS-->
                        <div class="tab-pane" id="agregarPedimento" role="tabpanel">
                            <?php include_once('forms/inv_form_pedimento.php'); ?>
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


<div class="modal fade" id="modalViewLotesPedimentos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <td>Clave artículo:</td>
                            <td id="celArticulo"></td>
                        </tr>
                        <tr>
                            <td>Descripción:</td>
                            <td id="celDescripcion"></td>
                        </tr>
                        <tr>
                            <td>Lote:</td>
                            <td id="celLote"></td>
                        </tr>
                        <tr>
                            <td>Pedimento:</td>
                            <td id="celPedimento"></td>
                        </tr>
                        <tr>
                            <td>Cantidad:</td>
                            <td id="celCantidad"></td>
                        </tr>
                        <tr>
                            <td>Almacén:</td>
                            <td id="celAlmacen"></td>
                        </tr>
                        <tr>
                            <td>Fecha caducidad:</td>
                            <td id="celFechaCad"></td>
                        </tr>
                        <tr>
                            <td>Fecha aduana:</td>
                            <td id="celFechaAduana"></td>
                        </tr>
                        <tr>
                            <td>Aduana:</td>
                            <td id="celAduana"></td>
                        </tr>
                        <tr>
                            <td>Estado:</td>
                            <td id="celEstado"></td>
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

<!-- end main content-->
<?php footerAdmin($data); ?>