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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listcontactos" role="tab">
                                Lista de contactos
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarcontacto" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listcontactos" role="tabpanel">

                            <table id="tableContactos"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>DISTRIBUIDOR</th>
                                        <th>PUESTO</th>
                                        <th>NOMBRE COMERCIAL</th>
                                        <th>CORREO</th>
                                        <th>TELÉFONO</th>
                                        <th>ESTADO</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarcontacto" role="tabpanel">
                            <form id="formContactos" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idcontacto" name="idcontacto">
                                <div class="row">

                                    <!-- DISTRIBUIDOR_ID -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listDistribuidores">DISTRIBUIDOR</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Dis</span>
                                                <select class="form-control" id="listDistribuidores" name="listDistribuidores" required=""></select>
                                                <div class="invalid-feedback">El campo distribuidor es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- PUESTO_ID -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listPuestos">PUESTO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Pue</span>
                                                <select class="form-control" id="listPuestos" name="listPuestos" required=""></select>
                                                <div class="invalid-feedback">El campo puesto es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo nombre -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-contactos-input">NOMBRE</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="nombre-contactos-addon">Nom</span>
                                                <input type="text" class="form-control" placeholder="Ingrese el nombre del contacto" id="nombre-contactos-input"
                                                    name="nombre-contactos-input"
                                                    aria-describedby="nombre-contactos-addon" required>
                                                <div class="invalid-feedback">El campo nombre es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo correo -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="correo-contactos-input">CORREO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="correo-contactos-addon">Cor</span>
                                                <input type="email" class="form-control" placeholder="Ingrese el correo del contacto" id="correo-contactos-input"
                                                    name="correo-contactos-input"
                                                    aria-describedby="correo-contactos-addon" required>
                                                <div class="invalid-feedback">Si ingresa un correo, debe ser válido</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo extensión -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="extension-contactos-input">EXTENSIÓN TELÉFONO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="extension-contactos-addon">Ext</span>
                                                <input type="text" class="form-control" placeholder="Ej. 101" id="extension-contactos-input"
                                                    name="extension-contactos-input" maxlength="6" inputmode="numeric"
                                                    aria-describedby="extension-contactos-addon" required>
                                                <div class="invalid-feedback">Si ingresa una extensión, debe contener al menos 3 números</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!--  campo telefono -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="telefono-contactos-input">TELÉFONO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="telefono-contactos-addon">Tel</span>
                                                <input type="text" class="form-control" placeholder="Ingrese el teléfono del puesto" id="telefono-contactos-input"
                                                    name="telefono-contactos-input" maxlength="10" inputmode="numeric" pattern="[0-9]{10}"
                                                    aria-describedby="telefono-contactos-addon" required>
                                                <div class="invalid-feedback"> Si ingresa un teléfono, debe contener exactamente 10 números
                                                </div>
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

                                    <!-- campo checkbox -->
                                    <!-- <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label">ENCUESTA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">
                                                    <input class="form-check-input mt-0" type="checkbox"
                                                        id="encuesta-checkbox" name="encuesta-checkbox" value="1">
                                                </span>
                                                <input type="text" class="form-control" readonly
                                                    value="Desea recibir encuestas de satisfacción por parte de LDR Solutions?">
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



<div class="modal fade" id="modalViewContacto" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <td>ID:</td>
                            <td id="idcontacto">1</td>
                        </tr>
                        <tr>
                            <td>DISTRIBUIDOR:</td>
                            <td id="nombreDistribuidor">Nombre distribuidor</td>
                        </tr>
                        <tr>
                            <td>PUESTO:</td>
                            <td id="nombrePuesto">Nombre puesto</td>
                        </tr>
                        <tr>
                            <td>NOMBRE COMERCIAL:</td>
                            <td id="nombreContacto">Nombre comercial</td>
                        </tr>
                        <tr>
                            <td>CORREO:</td>
                            <td id="correoContacto">Correo</td>
                        </tr>
                        <tr>
                            <td>EXTENSIÓN:</td>
                            <td id="extensionContacto">Extensión</td>
                        </tr>
                        <tr>
                            <td>TELÉFONO:</td>
                            <td id="telefonoContacto">Teléfono</td>
                        </tr>
                        <tr>
                            <td>FECHA DE REGISTRO:</td>
                            <td id="fechaContacto">Fecha de registro</td>
                        </tr>
                        <tr>
                            <td>ESTADO:</td>
                            <td id="estadoContacto">Estado</td>
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

<script>
    // teléfono
    const telefonoInput = document.getElementById('telefono-contactos-input');

    telefonoInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');

        if (this.value.length > 10) {
            this.value = this.value.slice(0, 10);
        }
    });

    // extensión
    const extensionInput = document.getElementById('extension-contactos-input');

    extensionInput.addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');

        if (this.value.length > 6) {
            this.value = this.value.slice(0, 6);
        }
    });
</script>








<!-- end main content-->
<?php footerAdmin($data); ?>