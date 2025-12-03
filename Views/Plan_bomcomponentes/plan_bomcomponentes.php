<?php headerAdmin($data);
?>


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
                <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist" id="nav-tab">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#listComponentes" role="tab">
                            LISTADO DE PRODUCTOS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#agregarProducto" role="tab">
                            NUEVO
                        </a>
                    </li>
                </ul>
            </div>
            <!-- end card header -->

            <div class="card-body">
                <div class="tab-content">

                    <!-- TAB LISTADO -->
                    <div class="tab-pane active" id="listComponentes" role="tabpanel">
                        <table id="tableComponentes"
                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>CLAVE</th>
                                    <th>CLAVE ARTICULO</th>
                                    <th>DESCRIPCION PRODUCTO</th>
                                    <th>CLAVE LÍNEA</th>
                                    <th>DESCRIPCIÓN LÍNEA</th>
                                    <th>FECHA CREACIÓN</th>
                                    <th>ESTADO</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>
                    <!-- end tab-pane listado -->

                    <!-- TAB AGREGAR PRODUCTO -->
                    <div class="tab-pane" id="agregarProducto" role="tabpanel">
                        <div class="card">
                            <div class="card-body checkout-tab">


                                <div class="step-arrow-nav mt-n3 mx-n3 mb-3">

                                    <ul class="nav nav-pills nav-justified custom-nav" role="tablist">

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3 active" id="informacion_general"
                                                data-bs-toggle="pill" data-bs-target="#pills-bill-info" type="button"
                                                role="tab" aria-controls="pills-bill-info" aria-selected="true">
                                                <i
                                                    class="ri-user-2-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Información General
                                            </button>
                                        </li>

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="documentacion"
                                                data-bs-toggle="pill" data-bs-target="#pills-bill-address" type="button"
                                                role="tab" aria-controls="pills-bill-address" aria-selected="false">
                                                <i
                                                    class="ri-file-list-3-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Documentación
                                            </button>
                                        </li>

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="procesos"
                                                data-bs-toggle="pill" data-bs-target="#pills-payment" type="button"
                                                role="tab" aria-controls="pills-payment" aria-selected="false">
                                                <i
                                                    class="ri-more-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Procesos
                                            </button>
                                        </li>

                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="pills-finish-tab"
                                                data-bs-toggle="pill" data-bs-target="#pills-finish" type="button"
                                                role="tab" aria-controls="pills-finish" aria-selected="false">
                                                <i
                                                    class="ri-checkbox-circle-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Finalizado
                                            </button>
                                        </li>

                                    </ul>

                                </div>

                                <div class="tab-content">
                                    <!-- PESTAÑA INFORMACIÓN GENERAL -->
                                    <div class="tab-pane fade show active" id="pills-bill-info" role="tabpanel"
                                        aria-labelledby="inforfation-general">
                                        <div>
                                            <!-- <h5 class="mb-1">Billing Information</h5> -->
                                            <p class="text-muted mb-4">
                                                Por favor, rellene toda la información a continuación.
                                            </p>
                                        </div>

                                        <form id="formBomComponentes" name="formBomComponentes" autocomplete="off"
                                            class="form-steps was-validated" autocomplete="off">
                                            <input type="hidden" id="idcomponente" name="idcomponente">

                                            <div class="row">
                                                <!-- Almacen -->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="listProductos">Producto</label>
                                                        <div class="input-group mb-3">
                                                            <select class="form-control" name="listProductos"
                                                                id="listProductos" required></select>
                                                            <div class="invalid-feedback">El campo producto es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- NOMBRE -->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtDescripcion">Descripción</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text"
                                                                id="nombre-producto-addon">Des</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ingresa el nombre del producto"
                                                                id="txtDescripcion" name="txtDescripcion"
                                                                aria-describedby="nombre-producto-addon" required>
                                                            <div class="invalid-feedback">
                                                                El campo de descripción es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- end row -->



                                            <div class="row">
                                                <!-- FRACC. ARANCELARÍA -->
                                                <div class="col-lg-6 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="listLineasProductos">Línea de
                                                            producto</label>
                                                        <div class="input-group mb-3">
                                                            <select class="form-control" name="listLineasProductos"
                                                                id="listLineasProductos" required></select>
                                                            <div class="invalid-feedback">El campo línea de producto es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- ESTADO -->
                                                <!-- <div class="col-lg-6 col-sm-6">
                                                        <div class="mb-3">
                                                            <label class="form-label"
                                                                for="estado-producto-select">ESTADO</label>
                                                            <div class="input-group has-validation mb-3">
                                                                <span class="input-group-text"
                                                                    id="estado-producto-addon">Est</span>
                                                                <select class="form-select" id="estado-producto-select"
                                                                    name="estado-producto-select"
                                                                    aria-describedby="estado-producto-addon" required>
                                                                    <option value="2" selected>Activo</option>
                                                                    <option value="1">inactivo</option>
                                                                </select>
                                                                <div class="invalid-feedback">
                                                                    El campo estado es obligatorio
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div> -->
                                            </div>
                                            <!-- end row -->

                                            <!-- OBSERVACION -->
                                            <!-- <div class="mb-3">
                                                    <label class="form-label"
                                                        for="observacion-producto-textarea">OBSERVACIONES</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text"
                                                            id="observacion-producto-addon">Obser</span>
                                                        <textarea class="form-control"
                                                            id="observacion-producto-textarea"
                                                            name="observacion-producto-textarea"
                                                            placeholder="Ingresa una observación sobre este producto"
                                                            rows="3"
                                                            aria-describedby="observacion-producto-addon"></textarea>
                                                    </div>
                                                </div> -->

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="submit" id="btnActionForm"
                                                    class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                                    data-nexttab="steparrow-description-info-tab">
                                                    <i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    <span id="btnText">REGISTRAR</span>
                                                </button>
                                            </div>

                                        </form>

                                    </div>
                                    <!-- end tab pane info general -->

                                    <!-- PESTAÑA DOCUMENTACIÓN -->
                                    <div class="tab-pane fade" id="pills-bill-address" role="tabpanel"
                                        aria-labelledby="pills-bill-address-tab">
                                        <!-- <div>
                                            <h5 class="mb-1">Documentación</h5>
                                            <p class="text-muted mb-4">
                                                Captura la documentación inicial del producto
                                            </p>
                                        </div> -->


<hr>
                                        <div class="card-body form-steps">
                                            <form  id="formDocumentacion" name="formDocumentacion" class="form-steps was-validated" autocomplete="off">
                                                <input type="hidden" id="idcomponentedoc" name="idcomponentedoc">
                                                <div class="row gy-5">
                                                    <div class="col-lg-4">

                                                              <div class="px-lg-4">
                                                            <div class="tab-content">
                                          
                                                                <!-- end tab pane -->
                                                                <div class="tab-pane fade show active"
                                                                    id="v-pills-bill-address" role="tabpanel"
                                                                    aria-labelledby="v-pills-bill-address-tab">
                                                                    <div>
                                                                        <h5>Registro de documentación</h5>
                                                                        <p class="text-muted">Captura la documentación inicial del producto
                                                                        </p>
                                                                    </div>

                                                                    <div>
                                                                        <div class="row g-3">
                                                                            <div class="col-12">
                                                                                <label for="txtDescripcionDocumento"
                                                                                    class="form-label">Descripción corta</label>
                                                                                <input type="text" class="form-control"
                                                                                    id="txtDescripcionDocumento" name="txtDescripcionDocumento"
                                                                                    placeholder="Ingresa una breve descripción del documento adjuntar" required>
                                                                                <div class="invalid-feedback">El campo de descripción es obligatorio</div>
                                                                            </div>

                                                                            <div class="col-12">
                                                                                <label for="txtFile"
                                                                                    class="form-label">Archivo(s) <span
                                                                                        class="text-muted"></span></label>
                                                                                <input type="file" class="form-control"
                                                                                    id="txtFile" name="txtFile"
                                                                                     required />
                                                                            </div>
                                                                        </div>

                                                                        <hr class="my-4 text-muted">


                                                                    </div>
                                                                    <div class="d-flex align-items-start gap-3 mt-4">
                         
                                                                        <button type="submit"
                                                                            class="btn btn-success btn-label right ms-auto nexttab nexttab"
                                                                            data-nexttab="v-pills-payment-tab"><i
                                                                                class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>Registrar</button>
                                                                    </div>
                                                                </div>
                                                                <!-- end tab pane -->
                                                   

                                                            </div>
                                                            <!-- end tab content -->
                                                        </div>



                                                        <!-- end nav -->
                                                    </div> <!-- end col-->
                                                    <div class="col-lg-8">
                                                        <div class="px-lg-4">
                                                            <div class="tab-content">
                                          
                                                                <!-- end tab pane -->
                                                                <div class="tab-pane fade show active"
                                                                    id="v-pills-bill-address" role="tabpanel"
                                                                    aria-labelledby="v-pills-bill-address-tab">
                                                                    <div>
                                                                        <h5>Listado de documentos</h5>
                                                                        <!-- <p class="text-muted">Fill all information below
                                                                        </p> -->
                                                                    </div>

                                                                                        <div class="tab-pane active" id="listComponentes" role="tabpanel">
                        <table id="tableDocumentos"
                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>DESCRIPCIÓN</th>
                                    <th>ARCHIVO</th>
                                    <th>FECHA REGISTRO</th>
                                    <th>OPCIONES</th>
                                </tr>
                            </thead>
                            <tbody>

                            </tbody>
                        </table>
                    </div>




                                                                </div>
                                                                <!-- end tab pane -->
                                                   

                                                            </div>
                                                            <!-- end tab content -->
                                                        </div>
                                                    </div>
                                                    <!-- end col -->
                                                </div>
                                                <!-- end row -->
                                            </form>
                                        </div>


                                    </div>
                                    <!-- end tab pane documentación -->

                                    <!-- PESTAÑA OTROS / PAYMENT -->
                                    <div class="tab-pane fade" id="pills-payment" role="tabpanel"
                                        aria-labelledby="pills-payment-tab">
                                        <div>
                                            <h5 class="mb-1">Otros</h5>
                                            <p class="text-muted mb-4">
                                                Información adicional
                                            </p>
                                        </div>

                                        <div class="d-flex align-items-start gap-3 mt-4">
                                            <button type="button" class="btn btn-light btn-label previestab"
                                                data-previous="pills-bill-address-tab">
                                                <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                Regresar a la documentación
                                            </button>
                                            <button type="button"
                                                class="btn btn-primary btn-label right ms-auto nexttab"
                                                data-nexttab="pills-finish-tab">
                                                <i
                                                    class="ri-shopping-basket-line label-icon align-middle fs-16 ms-2"></i>
                                                Guardar
                                            </button>
                                        </div>
                                    </div>
                                    <!-- end tab pane payment -->

                                    <!-- PESTAÑA FINALIZADO -->
                                    <div class="tab-pane fade" id="pills-finish" role="tabpanel"
                                        aria-labelledby="pills-finish-tab">
                                        <div class="text-center py-5">

                                            <div class="mb-4">
                                                <lord-icon src="https://cdn.lordicon.com/lupuorrc.json" trigger="loop"
                                                    colors="primary:#25a0e2,secondary:#00bd9d"
                                                    style="width:120px;height:120px">
                                                </lord-icon>
                                            </div>

                                            <h5>¡Gracias! El producto ha sido registrado correctamente.</h5>
                                            <p class="text-muted">
                                                Todo se completó con éxito y el proceso ha finalizado sin problemas.
                                            </p>

                                            <h3 class="fw-semibold">
                                                ID del producto:
                                                <a href="apps-ecommerce-order-details.html"
                                                    class="text-decoration-underline">
                                                    VZ2451
                                                </a>
                                            </h3>
                                        </div>
                                    </div>
                                    <!-- end tab pane finish -->

                                </div>
                                <!-- end tab content -->


                            </div>
                            <!-- end card body -->
                        </div>
                        <!-- end inner card -->
                    </div>
                    <!-- end tab-pane agregarProducto -->

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

<div class="modal fade" id="modalViewEstacion" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModal">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    id="close-modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Clave:</td>
                            <td id="celClave">654654654</td>
                        </tr>
                        <tr>
                            <td>Nombre:</td>
                            <td id="celNombre">Jacob</td>
                        </tr>
                        <tr>
                            <td>Proceso:</td>
                            <td id="celProceso">Jacob</td>
                        </tr>
                        <tr>
                            <td>Estandar:</td>
                            <td id="celEstandar">Jacob</td>
                        </tr>
                        <tr>
                            <td>Unidad de Medida:</td>
                            <td id="celUnidad">Jacob</td>
                        </tr>
                        <tr>
                            <td>Tiempo de ajuste:</td>
                            <td id="celTiempo">Jacob</td>
                        </tr>
                        <tr>
                            <td>MX:</td>
                            <td id="celProceso">Jacob</td>
                        </tr>
                        <tr>
                            <td>Proceso:</td>
                            <td id="celMx">Jacob</td>
                        </tr>
                        <tr>
                            <td>Línea:</td>
                            <td id="celLinea">Jacob</td>
                        </tr>
                        <tr>
                            <td>Estado:</td>
                            <td id="celEstado">Larry</td>
                        </tr>
                        <tr>
                            <td>Descripción:</td>
                            <td id="celDescripcion">Larry</td>
                        </tr>
                        <tr>
                            <td>Fecha:</td>
                            <td id="celFecha">Larry</td>
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