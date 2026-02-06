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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listtipoclientes" role="tab">
                                Lista de tipos de clientes
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregartipocliente" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listtipoclientes" role="tabpanel">

                            <table id="tableTipoCliente"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>NOMBRE DEL TIPO DE CLIENTE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>FECHA</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregartipocliente" role="tabpanel">
                            <form id="formTipoCliente" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idtipocliente" name="idtipocliente">
                                <div class="row">
                                    <!-- campo nombre -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-tipocliente-input">NOMBRE</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="nombre-tipocliente-addon">Nom</span>
                                                <input type="text" class="form-control" placeholder="Ingrese el nombre del tipo de cliente" id="nombre-tipocliente-input"
                                                    name="nombre-tipocliente-input"
                                                    aria-describedby="nombre-tipocliente-addon" required>
                                                <div class="invalid-feedback">El campo nombre es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Descripcion -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="descripcion-tipocliente-input">DESCRIPCIÓN</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="descripcion-tipocliente-addon">Des</span>
                                                <input type="text" class="form-control" placeholder="Ingrese la descripción del tipo de cliente" id="descripcion-tipocliente-input"
                                                    name="descripcion-tipocliente-input"
                                                    aria-describedby="descripcion-tipocliente-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTADO -->
                                    <!-- <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estado-select">ESTADO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estado-addon">Est</span>
                                                <select class="form-select" id="estado-select" name="estado-select"
                                                    aria-describedby="estado-addon" required>
                                                    <option value="2" selected>Activo</option>
                                                    <option value="1">Inactivo</option>
                                                </select>
                                                <div class="invalid-feedback">El campo estado es obligatorio</div>
                                            </div>
                                        </div>
                                    </div> -->


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



<div class="modal fade" id="modalViewTipoCliente" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModal">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>ID:</td>
                            <td id="idtipodecliente">ID</td>
                        </tr>
                        <tr>
                            <td>Nombre del tipo de cliente:</td>
                            <td id="nombreTipoCliente">Nombre</td>
                        </tr>
                        <tr>
                            <td>Descripción:</td>
                            <td id="descripcionTipoCliente">Descripción</td>
                        </tr>
                        <tr>
                            <td>Fecha de creación:</td>
                            <td id="fechaTipoCliente">Fecha de creación</td>
                        </tr>
                        <tr>
                            <td>Estado:</td>
                            <td id="estadoTipoCliente">Estado</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <div class="hstack gap-2 justify-content-end">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>







<!-- end main content-->
<?php footerAdmin($data); ?>