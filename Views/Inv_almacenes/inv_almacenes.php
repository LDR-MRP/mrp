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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listAlmacenes" role="tab">
                                ALMACENES
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarAlmacen" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listAlmacenes" role="tabpanel">

                            <table id="tableAlmacenes"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>DIRECCIÓN</th>
                                        <th>ENCARGADO</th>
                                        <th>TELÉFONO</th>
                                        <th>PRECIO ASIGNADO</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIÓN</th>

                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarAlmacen" role="tabpanel">
                            <form id="formAlmacenes" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idalmacen" name="idalmacen">
                                <div class="row">

                                    <!-- CLAVE -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="clave-almacen-input">CLAVE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="clave-almacen-addon">Clave</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la clave" id="clave-almacen-input" name="clave-almacen-input"
                                                    aria-describedby="clave-almacen-addon" required>
                                                <div class="invalid-feedback">El campo clave es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DIRECCION -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="direccion-input">DIRECCIÓN</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="direccion-addon">Dir</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la dirección" id="direccion-input" name="direccion-input"
                                                    aria-describedby="direccion-addon" required>
                                                <div class="invalid-feedback">El campo dirección es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ENCARGADO -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="encargado-input">ENCARGADO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="encargado-addon">Enc</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la dirección" id="encargado-input" name="encargado-input"
                                                    aria-describedby="encargado-addon" required>
                                                <div class="invalid-feedback">El campo encargado es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TELEFONO -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="telefono-input">TELÉFONO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="telefono-addon">Tel</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa el teléfono" id="telefono-input" name="telefono-input"
                                                    aria-describedby="telefono-addon" required>
                                                <div class="invalid-feedback">El campo teléfono es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- LISTA DE PRECIOS -->
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listPrecios">PRECIOS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Pre</span>
                                                <select class="form-control" id="listPrecios" name="listPrecios" required=""></select>
                                                <div class="invalid-feedback">El campo de precios es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Estado -->
                                    <div class="col-lg-4 col-sm-6">
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
                                    </div>

                                    <!-- DESCRIPCIÓN -->
                                    <div class="mb-3">
                                        <label class="form-label" for="descripcion-almacen-textarea">DESCRIPCIÓN</label>
                                        <div class="input-group mb-3">
                                            <span class="input-group-text" id="descripcion-almacen-addon">Desc.</span>
                                            <textarea class="form-control" id="descripcion-almacen-textarea" name="descripcion-almacen-textarea"
                                                placeholder="Ingresa una descripción" rows="3"
                                                aria-describedby="descripcion-almacen-addon"></textarea>
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


<div class="modal fade" id="modalViewAlmacen" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <td>Clave:</td>
                            <td id="celclave"></td>
                        </tr>
                        <tr>
                            <td>Descripción:</td>
                            <td id="celdescripcion"></td>
                        </tr>
                        <tr>
                            <td>Dirección:</td>
                            <td id="celdireccion"></td>
                        </tr>
                        <tr>
                            <td>Encargado:</td>
                            <td id="celencargado"></td>
                        </tr>
                        <tr>
                            <td>Teléfono:</td>
                            <td id="celtelefono"></td>
                        </tr>
                        <tr>
                        <tr>
                            <td>Precio asignado:</td>
                            <td id="cellistaprecio"></td>
                        </tr>
                        <tr>
                            <td>Fecha Registro:</td>
                            <td id="celFecha"></td>
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