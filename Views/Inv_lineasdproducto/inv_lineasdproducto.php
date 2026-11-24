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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listlineasproductos" role="tab">
                                LÍNEAS DE PRODUCTOS
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarlineasproducto" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listlineasproductos" role="tabpanel">

                            <table id="tableLineasProducto"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>FECHA CREACIÓN</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarlineasproducto" role="tabpanel">
                            <form id="formLineasProducto" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idlineaproducto" name="idlineaproducto">
                                <div class="row">

                                    <!-- CLAVE -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="clave-linea-producto-input">CLAVE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="clave-linea-producto-addon">Clave Lin. Prod</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la clave" id="clave-linea-producto-input" name="clave-linea-producto-input"
                                                    aria-describedby="clave-linea-producto-addon" required>
                                                <div class="invalid-feedback">El campo clave es obligatorio</div>
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
                                    <label class="form-label" for="descripcion-linea-producto-textarea">DESCRIPCIÓN</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="descripcion-linea-producto-addon">Desc.</span>
                                        <textarea class="form-control" id="descripcion-linea-producto-textarea" name="descripcion-linea-producto-textarea"
                                            placeholder="Ingresa una descripción" rows="3"
                                            aria-describedby="descripcion-linea-producto-addon"></textarea>
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



<div class="modal fade" id="modalViewLineaProducto" tabindex="-1">
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



<div class="modal fade" id="modalEstructuraLinea" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-primary-subtle p-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-xs me-3 flex-shrink-0">
                        <span class="avatar-title rounded bg-primary text-white fs-5">
                            <i class="ri-node-tree"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title mb-0">Estructura de línea</h5>
                        <small class="text-muted" id="lblLineaEstructura"></small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body p-4">
                <input type="hidden" id="idLineaEstructura">

                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h6 class="mb-0 text-uppercase fw-semibold">
                            Sublíneas
                            <span class="badge bg-primary-subtle text-primary ms-1" id="countSublineas">0</span>
                        </h6>
                        <small class="text-muted">Clasificación de productos dentro de la línea</small>
                    </div>
                    <button type="button" class="btn btn-primary" onclick="nuevoSublinea()">
                        <i class="ri-add-line me-1"></i> Nueva sublínea
                    </button>
                </div>

                <div id="formSublineaContainer" class="card border border-primary-subtle bg-light mb-3 d-none">
                    <div class="card-body">
                        <h6 class="fs-13 text-uppercase text-muted fw-semibold mb-3">
                            <i class="ri-add-circle-line me-1"></i> Nueva sublínea
                        </h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Clave <span class="text-danger">*</span></label>
                                <input type="text" id="sub_cve" class="form-control" placeholder="Ej. LDT">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-medium">Descripción <span class="text-danger">*</span></label>
                                <input type="text" id="sub_desc" class="form-control" placeholder="Ej. LIGHT DUTY TRUCK">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-medium mb-0">Fondo publicidad</label>
                                <small class="text-muted d-block mb-1">Precio sin IVA</small>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" id="sub_fondo" class="form-control text-end" step="0.01" min="0" placeholder="0.00">
                                </div>
                            </div>

                            <div class="col-md-3 d-flex gap-2">
                                <button type="button" class="btn btn-success flex-fill" onclick="guardarSublinea()">
                                    <i class="ri-save-line me-1"></i> Guardar
                                </button>
                                <button type="button" class="btn btn-light flex-fill" onclick="cancelarSublinea()">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive border rounded">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr class="text-uppercase fs-12 text-muted">
                                <th style="width:15%">Clave</th>
                                <th>Descripción</th>
                                <th class="text-end" style="width:20%">
                                    Fondo publicidad
                                    <small class="d-block fw-normal" style="text-transform:none;">Precio sin IVA</small>
                                </th>
                                <th class="text-center" style="width:12%">Estado</th>
                                <th class="text-center" style="width:12%">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbodySublineas"></tbody>
                    </table>
                </div>
            </div>

            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="modalHijo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Nuevo elemento</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="tipoHijo">

                <div class="mb-2">
                    <label>Nombre</label>
                    <input type="text" class="form-control" id="nombreHijo">
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button class="btn btn-primary" onclick="guardarHijo()">Guardar</button>
            </div>

        </div>
    </div>
</div>



<!-- end main content-->
<?php footerAdmin($data); ?>