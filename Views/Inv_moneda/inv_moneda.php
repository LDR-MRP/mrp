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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listmonedas" role="tab">
                                MONEDAS
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarMoneda" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listmonedas" role="tabpanel">

                            <table id="tableMonedas"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>SIMBOLO</th>
                                        <th>TIPO DE CAMBIO</th>
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




                        <div class="tab-pane" id="agregarMoneda" role="tabpanel">
                            <form id="formMonedas" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idmoneda" name="idmoneda">
                                <div class="row">

                                    <!-- CLAVE -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="clave-moneda-input">CLAVE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="clave-moneda-addon">Clave mon.</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la clave" id="clave-moneda-input" name="clave-moneda-input"
                                                    aria-describedby="clave-moneda-addon" required>
                                                <div class="invalid-feedback">El campo clave es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- TIPO DE CAMBIO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="cambio-moneda-input">TIPO DE CAMBIO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="cambio-moneda-addon">Tip. Camb.</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el cambio" id="cambio-moneda-input" name="cambio-moneda-input"
                                                    aria-describedby="cambio-moneda-addon" required>
                                                <div class="invalid-feedback">El campo tipo de cambio es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- SIMBOLO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="simbolo-moneda-input">SIMBOLO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="simbolo-moneda-addon">Sim.</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el simbolo" id="simbolo-moneda-input" name="simbolo-moneda-input"
                                                    aria-describedby="simbolo-moneda-addon" required>
                                                <div class="invalid-feedback">El campo simbolo es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTADO -->
                                    <div class="col-lg-6">
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
                                </div>
                                <!-- end row -->

                                <!-- DESCRIPCIÓN -->
                                <div class="mb-3">
                                    <label class="form-label" for="descripcion-moneda-textarea">DESCRIPCIÓN</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="descripcion-moneda-addon">Desc.</span>
                                        <textarea class="form-control" id="descripcion-moneda-textarea" name="descripcion-moneda-textarea"
                                            placeholder="Ingresa una descripción" rows="3"
                                            aria-describedby="descripcion-moneda-addon"></textarea>
                                    </div>
                                </div>

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



<div class="modal fade" id="modalViewMoneda" tabindex="-1">
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
                            <td>Tipo de cambio</td>
                            <td id="celCambio"></td>
                        </tr>
                        <tr>
                            <td>Simbolo</td>
                            <td id="celSimbolo"></td>
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