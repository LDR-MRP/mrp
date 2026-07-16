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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">MRP </a></li>
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
                        <a class="nav-link active" data-bs-toggle="tab" href="#navListProductos" role="tab">
                            LISTADO DE PRODUCTOS
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#navAgregarProducto" role="tab">
                            NUEVO
                        </a>
                    </li>
                </ul>
            </div>
            <!-- end card header -->

            <div class="card-body">
                <div class="tab-content">

                    <!-- TAB LISTADO -->
                    <div class="tab-pane active" id="navListProductos" role="tabpanel">
                        <table id="tableProductos"
                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                            style="width:100%">
                            <thead>
                                <tr>
                                    <th>CLAVE</th>
                                    <!-- <th>CLAVE ARTICULO</th> -->
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
                    <div class="tab-pane" id="navAgregarProducto" role="tabpanel">
                        <div class="card">
                            <div class="card-body checkout-tab">

                                <div class="step-arrow-nav mt-n3 mx-n3 mb-3">
                                    <ul class="nav nav-pills nav-justified custom-nav" role="tablist">

                                        <!-- TAB: INFORMACIÓN GENERAL -->
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3 active" id="tab-informacion-general"
                                                data-bs-toggle="pill" data-bs-target="#pane-informacion-general"
                                                type="button" role="tab" aria-controls="pane-informacion-general"
                                                aria-selected="true">
                                                <i
                                                    class="ri-user-2-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Información General
                                            </button>
                                        </li>

                                        <!-- TAB: DOCUMENTACIÓN -->
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="tab-documentacion"
                                                data-bs-toggle="pill" data-bs-target="#pane-documentacion" type="button"
                                                role="tab" aria-controls="pane-documentacion" aria-selected="false">
                                                <i
                                                    class="ri-file-list-3-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Documentación
                                            </button>
                                        </li>

                                        <!-- TAB: DESCRIPTIVA TÉCNICA -->
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="tab-descriptiva-tecnica"
                                                data-bs-toggle="pill" data-bs-target="#pane-descriptiva-tecnica"
                                                type="button" role="tab" aria-controls="pane-descriptiva-tecnica"
                                                aria-selected="false">
                                                <i
                                                    class="ri-file-list-3-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Descriptiva técnica
                                            </button>
                                        </li>

                                        <!-- TAB: PROCESOS -->
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="tab-procesos" data-bs-toggle="pill"
                                                data-bs-target="#pane-procesos" type="button" role="tab"
                                                aria-controls="pane-procesos" aria-selected="false">
                                                <i
                                                    class="ri-more-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Procesos
                                            </button>
                                        </li>

                                        <!-- TAB: ESPECIFICACIONES CRÍTICAS -->
                                        <!-- <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="tab-especificaciones-criticas"
                                                data-bs-toggle="pill" data-bs-target="#pane-especificaciones-criticas"
                                                type="button" role="tab" aria-controls="pane-especificaciones-criticas"
                                                aria-selected="false">
                                                <i
                                                    class="ri-alert-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Especificaciones críticas
                                            </button>
                                        </li> -->

                                        <!-- TAB: FINALIZADO -->
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link fs-15 p-3" id="tab-finalizado" data-bs-toggle="pill"
                                                data-bs-target="#pane-finalizado" type="button" role="tab"
                                                aria-controls="pane-finalizado" aria-selected="false">
                                                <i
                                                    class="ri-checkbox-circle-line fs-16 p-2 bg-primary-subtle text-primary rounded-circle align-middle me-2"></i>
                                                Finalizado
                                            </button>
                                        </li>

                                    </ul>
                                </div>

                                <div class="tab-content">

                                    <!-- PESTAÑA: INFORMACIÓN GENERAL -->
                                    <div class="tab-pane fade show active" id="pane-informacion-general" role="tabpanel"
                                        aria-labelledby="tab-informacion-general">
                                        <div>
                                            <p class="text-muted mb-4">
                                                Por favor, rellene toda la información a continuación.
                                            </p>
                                        </div>

                                        <form id="formConfProducto" name="formConfProducto"
                                            class="form-steps was-validated" autocomplete="off">
                                            <input type="hidden" id="idproducto" name="idproducto">

                                            <div class="row">
                                                <!-- Producto -->
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

                                                <!-- Descripción -->
                                                <div class="col-lg-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtDescripcion">Descripción</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text"
                                                                id="nombre-producto-addon">Des</span>
                                                            <input type="text" class="form-control form-disabled"
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
                                                <!-- Línea de producto -->
                                                <div class="col-lg-6 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="listLineasProductos">Línea de
                                                            producto</label>
                                                        <div class="input-group mb-3">
                                                            <select class="form-control form-disabled"
                                                                name="listLineasProductos" id="listLineasProductos"
                                                                required></select>
                                                            <div class="invalid-feedback">El campo línea de producto es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Estado -->
                                                <div class="col-lg-6 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="intEstado">Estado</label>
                                                        <div class="input-group has-validation mb-3">
                                                            <span class="input-group-text"
                                                                id="estado-producto-addon">Est</span>
                                                            <select class="form-select" id="intEstado" name="intEstado"
                                                                aria-describedby="estado-producto-addon" required>
                                                                <option value="2" selected>Activo</option>
                                                                <option value="1">Inactivo</option>
                                                            </select>
                                                            <div class="invalid-feedback">
                                                                El campo estado es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <!-- end row -->

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="submit" id="btnActionForm"
                                                    class="btn btn-success btn-label right ms-auto nexttab"
                                                    data-nexttab="tab-documentacion">
                                                    <i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    <span id="btnText">REGISTRAR</span>
                                                </button>
                                            </div>

                                        </form>
                                    </div>
                                    <!-- end pane información general -->

                                    <!-- PESTAÑA: DOCUMENTACIÓN -->

                                    <div class="tab-pane fade" id="pane-documentacion" role="tabpanel"
                                        aria-labelledby="tab-documentacion">

                                        <hr>


                                        <div class="row g-3 align-items-center mb-4 px-lg-4">


                                            <div class="col-lg-8">
                                                <h5 class="mb-1">Documentación</h5>
                                                <p class="text-muted mb-0">
                                                    Captura la documentación inicial del producto y consulta el listado
                                                    de archivos.
                                                </p>
                                            </div>


                                            <div class="col-lg-4 text-lg-end">
                                                <div
                                                    class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
                                                    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json"
                                                        trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d"
                                                        style="width:80px;height:80px"></lord-icon>
                                                    <div class="small">
                                                        <span class="text-muted producto_clave">ID:</span><br>
                                                        <span
                                                            class="fw-semibold descripcion_producto">MFDS1400457854</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>


                                        <!-- CONTENIDO DOCUMENTACIÓN -->
                                        <div class="card-body form-steps">
                                            <form id="formDocumentacion" name="formDocumentacion"
                                                class="form-steps was-validated" autocomplete="off">

                                                <input type="hidden" id="idproducto_documentacion"
                                                    name="idproducto_documentacion">

                                                <div class="row gy-5">

                                                    <!-- FORMULARIO DOCUMENTOS -->
                                                    <div class="col-lg-4">
                                                        <div class="px-lg-4">
                                                            <div class="tab-content">
                                                                <div class="tab-pane fade show active"
                                                                    id="v-pills-bill-address" role="tabpanel">

                                                                    <div class="mb-3">
                                                                        <h5>Registro de documentación</h5>
                                                                        <p class="text-muted mb-0">
                                                                            Captura la documentación inicial del
                                                                            producto
                                                                        </p>
                                                                    </div>

                                                                    <div class="row g-3">

                                                                        <div class="col-12">
                                                                            <label for="tipoDocumento"
                                                                                class="form-label">Tipo de
                                                                                documento</label>
                                                                            <select name="tipoDocumento"
                                                                                id="tipoDocumento" class="form-control"
                                                                                required>
                                                                                <option value="" selected>--Seleccione--
                                                                                </option>
                                                                                <option value="Ayuda visual">Ayuda
                                                                                    Visual</option>
                                                                                <option value="Diagrama">Diagrama
                                                                                </option>
                                                                            </select>
                                                                            <div class="invalid-feedback">
                                                                                El campo tipo de documento es
                                                                                obligatorio
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-12">
                                                                            <label for="txtDescripcionDocumento"
                                                                                class="form-label">
                                                                                Descripción del documento
                                                                            </label>
                                                                            <input type="text" class="form-control"
                                                                                id="txtDescripcionDocumento"
                                                                                name="txtDescripcionDocumento"
                                                                                placeholder="Ingresa una breve descripción del documento a adjuntar"
                                                                                required>
                                                                            <div class="invalid-feedback">
                                                                                El campo de descripción es obligatorio
                                                                            </div>
                                                                        </div>

                                                                        <div class="col-12">
                                                                            <label for="txtFile"
                                                                                class="form-label">Archivo(s)</label>
                                                                            <input type="file" class="form-control"
                                                                                id="txtFile" name="txtFile" required>
                                                                            <div class="invalid-feedback">
                                                                                Debe seleccionar al menos un archivo
                                                                            </div>
                                                                        </div>

                                                                    </div>

                                                                    <hr class="my-4 text-muted">

                                                                    <div class="d-flex align-items-start gap-3 mt-4">
                                                                        <button type="submit"
                                                                            class="btn btn-success btn-label right ms-auto nexttab"
                                                                            data-nexttab="tab-descriptiva-tecnica">
                                                                            <i
                                                                                class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                                            Registrar
                                                                        </button>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- TABLA DOCUMENTOS -->
                                                    <div class="col-lg-8">
                                                        <div class="px-lg-4">
                                                            <div class="tab-content">
                                                                <div class="tab-pane fade show active"
                                                                    id="v-pills-bill-address-list" role="tabpanel">

                                                                    <div class="mb-3">
                                                                        <h5>Listado de documentos</h5>
                                                                    </div>

                                                                    <div id="listProductos" role="tabpanel">
                                                                        <table id="tableDocumentos"
                                                                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                                                            style="width:100%">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>TIPO DE DOCUMENTO</th>
                                                                                    <th>DESCRIPCIÓN</th>
                                                                                    <th>ARCHIVO</th>
                                                                                    <th>FECHA REGISTRO</th>
                                                                                    <th>OPCIONES</th>
                                                                                </tr>
                                                                            </thead>
                                                                            <tbody></tbody>
                                                                        </table>
                                                                    </div>

                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <!-- end col -->

                                                </div>
                                                <!-- end row -->
                                            </form>
                                        </div>

                                    </div>






                                    <div class="tab-pane fade" id="pane-descriptiva-tecnica" role="tabpanel"
                                        aria-labelledby="tab-descriptiva-tecnica">
                                        <!-- <div>
                                            <h5 class="mb-1">Descriptiva técnica</h5>
                                            <p class="text-muted mb-4">
                                                Registra la información técnica detallada del producto.
                                            </p>
                                        </div> -->

                                        <!-- HEADER: DESCRIPTIVA (izquierda) + CARRITO/ID (derecha) -->
                                        <div class="row g-3 align-items-center mb-4 px-lg-4">


                                            <div class="col-lg-8">
                                                <h5 class="mb-1">Descriptiva técnica</h5>
                                                <p class="text-muted mb-0">
                                                    Registra la información técnica detallada del producto.
                                                </p>
                                            </div>


                                            <div class="col-lg-4 text-lg-end">
                                                <div
                                                    class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
                                                    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json"
                                                        trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d"
                                                        style="width:80px;height:80px"></lord-icon>
                                                    <div class="small">
                                                        <span class="text-muted producto_clave">ID:</span><br>
                                                        <span
                                                            class="fw-semibold descripcion_producto">MFDS1400457854</span>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>



                                        <form id="formConfDescriptiva" name="formConfDescriptiva"
                                            class="form-steps was-validated" autocomplete="off">


                                            <input type="hidden" id="idproducto_descriptiva"
                                                name="idproducto_descriptiva">

                                            <input type="hidden" id="iddescriptiva" name="iddescriptiva">


                                            <!-- ========= FICHA TÉCNICA ========= -->


                                            <div class="row">
                                                <!-- Marca -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtMarca">Marca</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Mar</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: FOTON" id="txtMarca" name="txtMarca"
                                                                required>
                                                            <div class="invalid-feedback">El campo marca es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Modelo -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtModelo">Modelo</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Mod</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Wonder Cabina Sencilla" id="txtModelo"
                                                                name="txtModelo" required>
                                                            <div class="invalid-feedback">El campo modelo es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Largo total -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtLargoTotal">Largo
                                                            total</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">LT</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 4,620 mm" id="txtLargoTotal"
                                                                name="txtLargoTotal" required>
                                                            <div class="invalid-feedback">El campo largo total es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">

                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtDistanciaEjes">Distancia entre
                                                            ejes (WB)</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">WB</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 3,080 mm" id="txtDistanciaEjes"
                                                                name="txtDistanciaEjes" required>
                                                            <div class="invalid-feedback">La distancia entre ejes es
                                                                obligatoria</div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtPesoBruto">Peso bruto
                                                            vehicular (PBV)</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">PBV</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 2,700 kgs" id="txtPesoBruto"
                                                                name="txtPesoBruto" required>
                                                            <div class="invalid-feedback">El peso bruto vehicular es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Motor -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtMotor">Motor</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Mot</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: FOTON Harbin Dongan DAM16NS"
                                                                id="txtMotor" name="txtMotor" required>
                                                            <div class="invalid-feedback">El campo motor es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>


                                            <div class="row">

                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtDesplazamientoCilindros">Desplazamiento /
                                                            Cilindros</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Cil</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 1.6L / 4"
                                                                id="txtDesplazamientoCilindros"
                                                                name="txtDesplazamientoCilindros" required>
                                                            <div class="invalid-feedback">Este campo es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Desplazamiento -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtDesplazamiento">Peso chasis
                                                            cabina</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Peso</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 2,400 kgs" id="txtDesplazamiento"
                                                                name="txtDesplazamiento" required>
                                                            <div class="invalid-feedback">El desplazamiento es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Tipo de combustible -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtTipoCombustible">Tipo de
                                                            combustible</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">TC</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Gasolina" id="txtTipoCombustible"
                                                                name="txtTipoCombustible" required>
                                                            <div class="invalid-feedback">El tipo de combustible es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Potencia / Torque / Transmisión -->
                                            <div class="row">
                                                <!-- Potencia -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtPotencia">Potencia</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">HP</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 110 hp @ 5,600 rpm" id="txtPotencia"
                                                                name="txtPotencia" required>
                                                            <div class="invalid-feedback">La potencia es obligatoria
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Torque -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtTorque">Torque</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Tor</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 110 lb-pie @ 4,000 rpm" id="txtTorque"
                                                                name="txtTorque" required>
                                                            <div class="invalid-feedback">El torque es obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Transmisión -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtTransmision">Transmisión</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Tra</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Manual 5 vel. + reversa"
                                                                id="txtTransmision" name="txtTransmision" required>
                                                            <div class="invalid-feedback">La transmisión es obligatoria
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Eje delantero / Suspensión delantera / Eje trasero -->
                                            <div class="row">
                                                <!-- Eje delantero -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtEjeDelantero">Eje
                                                            delantero</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">ED</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Independiente, horquilla..."
                                                                id="txtEjeDelantero" name="txtEjeDelantero" required>
                                                            <div class="invalid-feedback">El eje delantero es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Suspensión delantera -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtSuspensionDelantera">Suspensión delantera</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">SD</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Resorte helicoidal con amortiguador"
                                                                id="txtSuspensionDelantera"
                                                                name="txtSuspensionDelantera" required>
                                                            <div class="invalid-feedback">La suspensión delantera es
                                                                obligatoria</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Eje trasero -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtEjeTrasero">Eje
                                                            trasero</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">ET</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Rígido tipo semiflotante"
                                                                id="txtEjeTrasero" name="txtEjeTrasero" required>
                                                            <div class="invalid-feedback">El eje trasero es obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Suspensión trasera / Llantas / Sistema de frenos -->
                                            <div class="row">
                                                <!-- Suspensión trasera -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtSuspensionTrasera">Suspensión
                                                            trasera</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">ST</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Muelles semielípticos..."
                                                                id="txtSuspensionTrasera" name="txtSuspensionTrasera"
                                                                required>
                                                            <div class="invalid-feedback">La suspensión trasera es
                                                                obligatoria</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Llantas -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtLlantas">Llantas</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Lla</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 175/75R14-99S" id="txtLlantas"
                                                                name="txtLlantas" required>
                                                            <div class="invalid-feedback">Las llantas son obligatorias
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Sistema de frenos -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtSistemaFrenos">Sistema de
                                                            frenos</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Fre</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Hidráulicos, asistidos por vacío"
                                                                id="txtSistemaFrenos" name="txtSistemaFrenos" required>
                                                            <div class="invalid-feedback">El sistema de frenos es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Asistencias / Sistema eléctrico / Capacidad de combustible -->
                                            <div class="row">
                                                <!-- Asistencias -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtAsistencias">Asistencias</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Asi</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: ABS + EBD + ESC" id="txtAsistencias"
                                                                name="txtAsistencias" required>
                                                            <div class="invalid-feedback">Las asistencias son
                                                                obligatorias</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Sistema eléctrico -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtSistemaElectrico">Sistema
                                                            eléctrico</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Ele</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 12V" id="txtSistemaElectrico"
                                                                name="txtSistemaElectrico" required>
                                                            <div class="invalid-feedback">El sistema eléctrico es
                                                                obligatorio</div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Capacidad de combustible -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtCapacidadCombustible">Capacidad de
                                                            combustible</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Cap</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 50 L" id="txtCapacidadCombustible"
                                                                name="txtCapacidadCombustible" required>
                                                            <div class="invalid-feedback">La capacidad de combustible es
                                                                obligatoria</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Dirección / Equipamiento -->
                                            <div class="row">

                                                <!-- Dirección -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtNorma">Norma de
                                                            emisiones</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Nor</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Euro6" id="txtNorma" name="txtNorma"
                                                                required>
                                                            <div class="invalid-feedback">La norma de emisiones es
                                                                obligatorio
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>


                                                <!-- Dirección -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label" for="txtDireccion">Dirección</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Dir</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: Hidráulica" id="txtDireccion"
                                                                name="txtDireccion" required>
                                                            <div class="invalid-feedback">La dirección es obligatoria
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Equipamiento -->
                                                <div class="col-lg-4 col-sm-6">
                                                    <div class="mb-3">
                                                        <label class="form-label"
                                                            for="txtEquipamiento">Equipamiento</label>
                                                        <textarea class="form-control" id="txtEquipamiento"
                                                            name="txtEquipamiento"
                                                            placeholder="Ej: El disponible de línea" rows="2"
                                                            required></textarea>
                                                        <div class="invalid-feedback">El equipamiento es obligatorio
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Botón -->
                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="submit" id="btnActionForm"
                                                    class="btn btn-success btn-label right ms-auto nexttab"
                                                    data-nexttab="tab-documentacion">
                                                    <i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                    <span id="btnText">REGISTRAR</span>

                                                </button>
                                            </div>
                                        </form>






                                        <!-- <div class="d-flex align-items-start gap-3 mt-4">
                                            <button type="button" class="btn btn-light btn-label previestab"
                                                data-previous="tab-documentacion">
                                                <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                Regresar a documentación
                                            </button>

                                            <button type="button"
                                                class="btn btn-primary btn-label right ms-auto nexttab"
                                                data-nexttab="tab-procesos">
                                                <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                Continuar a procesos
                                            </button>
                                        </div> -->
                                    </div>
                                    <!-- end pane descriptiva técnica -->

                                    <!-- PESTAÑA: PROCESOS -->
                                    <div class="tab-pane fade" id="pane-procesos" role="tabpanel"
                                        aria-labelledby="tab-procesos">

                                        <div class="row g-3 align-items-center mb-4 px-lg-4">
                                            <div class="col-lg-8">
                                                <h5 class="mb-1 fw-bold">Procesos de Producción</h5>
                                                <p class="text-muted mb-0">
                                                    Configure las estaciones y la ruta de ensamble. Defina subensambles,
                                                    recursos y especificaciones por estación.
                                                </p>
                                            </div>

                                            <div class="col-lg-4 text-lg-end">
                                                <div
                                                    class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light shadow-sm">
                                                    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json"
                                                        trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d"
                                                        style="width:80px;height:80px"></lord-icon>
                                                    <div class="small text-start">
                                                        <span class="text-muted producto_clave">ID:</span><br>
                                                        <span
                                                            class="fw-semibold descripcion_producto">MFDS1400457854</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <form id="formRutaProducto" class="form-steps was-validated">

                                            <input type="hidden" id="idproducto_proceso" name="idproducto_proceso">
                                            <input type="hidden" id="id_ruta_producto" name="id_ruta_producto">

                                            <div class="row g-3 mb-3">
                                                <div class="col-lg-5 col-md-5">
                                                    <label for="listPlantasSelect" class="form-label">Planta</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i
                                                                class="bi bi-building"></i></span>
                                                        <select id="listPlantasSelect" name="listPlantasSelect"
                                                            class="form-select" required></select>
                                                        <div class="invalid-feedback">
                                                            Debe seleccionar una planta.
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-lg-7 col-md-7">
                                                    <label for="listLineasSelect" class="form-label">Línea de
                                                        producción</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i
                                                                class="bi bi-diagram-3"></i></span>
                                                        <select id="listLineasSelect" name="listLineasSelect"
                                                            class="form-select" required>
                                                            <option value="">Seleccione una planta primero...</option>
                                                        </select>
                                                        <div class="invalid-feedback">
                                                            Debe seleccionar una línea de producción.
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <hr class="my-4">

                                            <div class="row g-4 align-items-stretch">
                                                <!-- PANEL IZQUIERDO -->
                                                <div class="col-lg-3 col-md-4">
                                                    <div class="card h-100 border-0 shadow-sm panel-process rounded-4">
                                                        <div class="card-header bg-body border-0 pb-2">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <h6 class="mb-1 fw-semibold">Estaciones disponibles
                                                                    </h6>
                                                                    <p class="text-muted small mb-0">
                                                                        Arrastra o haz clic para agregar a la ruta
                                                                    </p>
                                                                </div>
                                                                <span
                                                                    class="badge rounded-pill bg-info-subtle text-info-emphasis">
                                                                    <span id="countEstacionesDisponibles">0</span>
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="card-body pt-2 bg-body">
                                                            <div id="listaEstaciones"
                                                                class="list-group small lista-estaciones-panel"></div>

                                                            <div id="mensajeSinEstaciones"
                                                                class="text-muted small mt-2 d-none">
                                                                No hay estaciones configuradas para la planta y línea
                                                                seleccionadas.
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- PANEL CENTRAL -->
                                                <div class="col-lg-4 col-md-4">
                                                    <div class="card h-100 border-0 shadow-sm panel-process rounded-4">
                                                        <div class="card-header bg-body border-0 pb-2">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center">
                                                                <div>
                                                                    <h6 class="mb-1 fw-semibold">Ruta del producto</h6>
                                                                    <p class="text-muted small mb-0">
                                                                        Orden de ensamble y configuración por estación
                                                                    </p>
                                                                </div>
                                                                <span
                                                                    class="badge rounded-pill bg-primary-subtle text-primary-emphasis">
                                                                    <span id="countRuta">0</span> estaciones
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <div class="card-body pt-2 bg-body">
                                                            <div id="dropRuta" class="dropzone dropzone-ruta"
                                                                ondragover="allowDrop(event)" ondrop="dropOnRuta(event)"
                                                                ondragleave="dragLeaveRuta(event)">

                                                                <div id="placeholderRuta"
                                                                    class="text-muted small text-center py-4">
                                                                    Arrastra aquí las estaciones o haz clic sobre ellas
                                                                    para construir la ruta del producto.
                                                                </div>

                                                                <div id="listaRutaCards" class="ruta-estaciones-list">
                                                                </div>
                                                            </div>

                                                            <input type="hidden" name="ruta_estaciones"
                                                                id="ruta_estaciones">

                                                            <div class="mt-3">
                                                                <button type="submit"
                                                                    class="btn btn-success w-100 rounded-pill py-2 fw-semibold">
                                                                    <i class="bi bi-check2-circle me-1"></i> Guardar
                                                                    configuración
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- PANEL DERECHO -->
                                                <div class="col-lg-5 col-md-4">
                                                    <div class="card h-100 border-0 shadow-sm panel-process rounded-4">
                                                        <div class="card-header bg-body border-0">
                                                            <div
                                                                class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                                                <h5 class="mb-0 fw-semibold" id="titleEstacionDetalle">
                                                                    Estación: Selecciona una estación
                                                                </h5>

                                                                <button type="button"
                                                                    class="btn btn-outline-danger btn-sm rounded-pill"
                                                                    id="btnEliminarEstacionPanel"
                                                                    onclick="eliminarEstacionSeleccionadaActual()"
                                                                    disabled>
                                                                    <i class="bi bi-trash3 me-1"></i> Eliminar estación
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <div class="card-body pt-0 bg-body">


                                                            <ul class="nav nav-tabs nav-tabs-custom mb-3 flex-nowrap overflow-auto"
                                                                id="tabsDetalleEstacion" role="tablist">
                                                                <li class="nav-item" role="presentation">
                                                                    <button class="nav-link active" id="tab-det-config"
                                                                        data-bs-toggle="tab"
                                                                        data-bs-target="#pane-det-config" type="button"
                                                                        role="tab">
                                                                        <i class="bi bi-gear me-1"></i> Configuración
                                                                    </button>
                                                                </li>

                                                                <li class="nav-item d-none" role="presentation"
                                                                    id="li-tab-det-sub">
                                                                    <button class="nav-link" id="tab-det-sub"
                                                                        data-bs-toggle="tab"
                                                                        data-bs-target="#pane-det-sub" type="button"
                                                                        role="tab">
                                                                        <i class="bi bi-diagram-3 me-1"></i>
                                                                        Subensambles
                                                                    </button>
                                                                </li>
                                                            </ul>

                                                            <div id="panelDetalleEmpty"
                                                                class="alert alert-light border rounded-4 mb-3 text-center">
                                                                <div class="py-4">
                                                                    <i class="bi bi-ui-checks-grid text-muted"
                                                                        style="font-size: 2rem;"></i>
                                                                    <h6 class="mt-3 mb-1 fw-semibold">Sin configuración
                                                                        seleccionada</h6>
                                                                    <p class="text-muted small mb-0">
                                                                        Selecciona una planta, una línea de producción y
                                                                        agrega al menos una estación a la ruta
                                                                        para habilitar la configuración del panel.
                                                                    </p>
                                                                </div>
                                                            </div>






                                                            <div class="tab-content d-none"
                                                                id="tabContentDetalleEstacion">

                                                                <input type="hidden" id="idestacion_actual">

                                                                <!-- CONFIGURACION -->
                                                                <div class="tab-pane fade show active"
                                                                    id="pane-det-config" role="tabpanel">
                                                                    <div class="station-info-box station-dark mb-3">
                                                                        <div class="row g-3" id="detalleInfoEstacion">

                                                                            <!-- <div class="col-md-3">
                                                                                <label
                                                                                    class="small text-body-secondary">Código</label>
                                                                                <div class="fw-semibold text-body"
                                                                                    id="detCodigoEstacion">-</div>
                                                                            </div> -->

                                                                            <div class="col-md-4">
                                                                                <label
                                                                                    class="small text-body-secondary">Nombre</label>
                                                                                <div class="fw-semibold text-body"
                                                                                    id="detAreaEstacion">-</div>
                                                                            </div>

                                                                            <!-- <div class="col-md-3">
                                                                                <label
                                                                                    class="small text-body-secondary">Tipo</label>
                                                                                <div class="fw-semibold text-body"
                                                                                    id="detTipoEstacion">Estación de
                                                                                    Ensamble</div>
                                                                            </div> -->

                                                                            <div class="col-md-8">
                                                                                <label
                                                                                    class="small text-body-secondary">Proceso</label>
                                                                                <div class="fw-semibold text-body"
                                                                                    id="detDescEstacion">-</div>
                                                                            </div>

                                                                        </div>
                                                                    </div>

                                                                    <div class="row g-3 mb-3">
                                                                        <div class="col-md-3 col-6">
                                                                            <div class="metric-card">
                                                                                <div class="metric-icon text-primary"><i
                                                                                        class="bi bi-tools"></i></div>
                                                                                <div class="metric-value"
                                                                                    id="metricHerramientas">0</div>
                                                                                <div class="metric-label">Herramientas
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3 col-6">
                                                                            <div class="metric-card">
                                                                                <div class="metric-icon text-success"><i
                                                                                        class="bi bi-box-seam"></i>
                                                                                </div>
                                                                                <div class="metric-value"
                                                                                    id="metricComponentes">0</div>
                                                                                <div class="metric-label">Componentes
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <div class="col-md-3 col-6">
                                                                            <div class="metric-card">
                                                                                <div class="metric-icon text-warning"><i
                                                                                        class="bi bi-gear-wide-connected"></i>
                                                                                </div>
                                                                                <div class="metric-value"
                                                                                    id="metricOperaciones">0</div>
                                                                                <div class="metric-label">Operaciones
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                        <!-- <div class="col-md-3 col-6">
                                                                            <div class="metric-card">
                                                                                <div class="metric-icon text-info"><i
                                                                                        class="bi bi-diagram-3"></i>
                                                                                </div>
                                                                                <div class="metric-value"
                                                                                    id="metricSubensambles">0</div>
                                                                                <div class="metric-label">Esp. Críticas
                                                                                </div>
                                                                            </div>
                                                                        </div> -->
                                                                    </div>

                                                                    <div class="d-flex flex-wrap gap-2 mb-3">
                                                                        <button type="button"
                                                                            class="btn btn-primary btn-sm rounded-pill"
                                                                            id="btnAbrirHerramientasPanel">
                                                                            <i class="bi bi-tools me-1"></i> 
                                                                            herramientas
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-success btn-sm rounded-pill"
                                                                            id="btnAbrirComponentesPanel">
                                                                            <i class="bi bi-box-seam me-1"></i> 
                                                                            componentes
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-warning btn-sm rounded-pill"
                                                                            id="btnEspPanelTop">
                                                                            <i
                                                                                class="bi bi-gear-wide-connected me-1"></i>
                                                                             operaciones
                                                                        </button>

                                                                        <button type="button"
                                                                            class="btn btn-danger btn-sm rounded-pill"
                                                                            id="btnEspCriticasPanelTop">
                                                                            <i
                                                                                class="bi bi-exclamation-triangle me-1"></i>
                                                                            Especificaciones críticas
                                                                        </button>



                                                                        <div
                                                                            class="form-check form-switch ms-auto mt-1">
                                                                            <input class="form-check-input"
                                                                                type="checkbox" id="chkVinDetalle">
                                                                            <label
                                                                                class="form-check-label small fw-semibold"
                                                                                for="chkVinDetalle">
                                                                                Estampado VIN
                                                                            </label>
                                                                        </div>
                                                                    </div>

                                                                    <!-- INSPECCION / PDI -->
                                                                    <div
                                                                        class="border rounded-4 p-3 bg-light-subtle mb-3">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                                                            <div>
                                                                                <h6 class="mb-1 fw-semibold">
                                                                                    <i
                                                                                        class="bi bi-clipboard2-pulse me-1 text-warning"></i>
                                                                                    Inspección de calidad / PDI
                                                                                </h6>
                                                                                <p class="text-muted small mb-0">
                                                                                    Aquí puedes configurar múltiples
                                                                                    puntos críticos de inspección para
                                                                                    esta estación. En producción deberán
                                                                                    revisarse y capturarse.
                                                                                </p>
                                                                            </div>
                                                                            <div class="form-check form-switch mt-1">
                                                                                <input class="form-check-input"
                                                                                    type="checkbox"
                                                                                    id="chkRequiereInspeccion">
                                                                                <label
                                                                                    class="form-check-label small fw-semibold"
                                                                                    for="chkRequiereInspeccion">
                                                                                    Realizar inspección aquí
                                                                                </label>
                                                                            </div>
                                                                        </div>

                                                                        <div id="bloqueInspeccionConfig" class="d-none">
                                                                            <div
                                                                                class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                                                                <div class="small text-muted">
                                                                                    Puedes registrar 1, 10, 100 o más
                                                                                    puntos críticos en esta estación.
                                                                                </div>


                                                                                <button type="button"
                                                                                    class="btn btn-outline-primary btn-sm rounded-pill"
                                                                                    id="btnAbrirModalPdi">
                                                                                    <i
                                                                                        class="bi bi-check2-square me-1"></i>
                                                                                    Configurar PDI
                                                                                </button>

                                                                            </div>

                                                                            <div id="listaPuntosPdiEstacion"></div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- AYUDAS VISUALES -->
                                                                    <div class="border rounded-4 p-3 bg-light-subtle">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                                                            <div>
                                                                                <h6 class="mb-1 fw-semibold">Ayudas
                                                                                    visuales</h6>
                                                                                <p class="text-muted small mb-0">
                                                                                    Agrega PDFs o imágenes de apoyo para
                                                                                    esta estación. Esto es opcional.
                                                                                </p>
                                                                            </div>


                                                                            <button type="button"
                                                                                class="btn btn-outline-secondary btn-sm rounded-pill"
                                                                                id="btnAbrirModalAyudas">
                                                                                <i
                                                                                    class="bi bi-file-earmark-image me-1"></i>
                                                                                Ayudas visuales
                                                                            </button>
                                                                        </div>

                                                                        <div id="listaAyudasVisuales"
                                                                            class="small text-muted">
                                                                            No hay ayudas visuales registradas.
                                                                        </div>
                                                                    </div>

                                                                    <div class="d-flex gap-2 mt-3">
                                                                        <button type="button"
                                                                            class="btn btn-outline-secondary btn-sm rounded-pill"
                                                                            onclick="moverEstacionActual('up')">
                                                                            <i class="bi bi-arrow-up"></i> Subir
                                                                            estación
                                                                        </button>
                                                                        <button type="button"
                                                                            class="btn btn-outline-secondary btn-sm rounded-pill"
                                                                            onclick="moverEstacionActual('down')">
                                                                            <i class="bi bi-arrow-down"></i> Bajar
                                                                            estación
                                                                        </button>
                                                                    </div>
                                                                </div>

                                                                <!-- SUBENSAMBLES -->
                                                                <div class="tab-pane fade" id="pane-det-sub"
                                                                    role="tabpanel">
                                                                    <div id="wrapSubensamblesVacio"
                                                                        class="alert alert-light border d-none">
                                                                        Esta estación no tiene subensamble configurado.
                                                                    </div>

                                                                    <input type="hidden" id="id_subensamble_actual"
                                                                        value="">

                                                                    <div id="panelSubensambleUnico" class="d-none">
                                                                        <div
                                                                            class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                                                                            <div>
                                                                                <h6 class="mb-1 fw-semibold">
                                                                                    <i
                                                                                        class="bi bi-diagram-3 me-1 text-primary"></i>
                                                                                    Subensamble de esta estación
                                                                                </h6>
                                                                                <p
                                                                                    class="text-body-secondary small mb-0">
                                                                                    Recursos y configuración del
                                                                                    pre-ensamble asociado a esta
                                                                                    estación.
                                                                                </p>
                                                                            </div>

                                                                        </div>

                                                                        <div class="station-info-box station-dark mb-3">
                                                                            <div class="row g-3">

                                                                                        <div class="col-md-4">
                                                                                    <label
                                                                                        class="small text-body-secondary">Nombre</label>
                                                                                    <div class="fw-semibold text-body"
                                                                                        id="detSubNombre">-</div>
                                                                                </div>


                                                                                <div class="col-md-8">
                                                                                    <label
                                                                                        class="small text-body-secondary">Proceso</label>
                                                                                    <div class="fw-semibold text-body"
                                                                                        id="detSubProceso">-</div>
                                                                                </div>
             
                                                                                <!-- <div class="col-md-3">
                                                                                    <label
                                                                                        class="small text-body-secondary">Tiempo
                                                                                        ajuste</label>
                                                                                    <div class="fw-semibold text-body"
                                                                                        id="detSubTiempo">-</div>
                                                                                </div> -->
                                                                            </div>
                                                                        </div>

                                                                        <div class="row g-3 mb-3">
                                                                            <div class="col-md-3 col-6">
                                                                                <div
                                                                                    class="metric-card metric-card-dark">
                                                                                    <div
                                                                                        class="metric-icon text-primary">
                                                                                        <i class="bi bi-tools"></i>
                                                                                    </div>
                                                                                    <div class="metric-value"
                                                                                        id="metricSubHerramientas">0
                                                                                    </div>
                                                                                    <div class="metric-label">
                                                                                        Herramientas</div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-3 col-6">
                                                                                <div
                                                                                    class="metric-card metric-card-dark">
                                                                                    <div
                                                                                        class="metric-icon text-success">
                                                                                        <i class="bi bi-box-seam"></i>
                                                                                    </div>
                                                                                    <div class="metric-value"
                                                                                        id="metricSubComponentes">0
                                                                                    </div>
                                                                                    <div class="metric-label">
                                                                                        Componentes</div>
                                                                                </div>
                                                                            </div>
                                                                            <!-- <div class="col-md-3 col-6">
                                                                                <div
                                                                                    class="metric-card metric-card-dark">
                                                                                    <div
                                                                                        class="metric-icon text-warning">
                                                                                        <i
                                                                                            class="bi bi-gear-wide-connected"></i>
                                                                                    </div>
                                                                                    <div class="metric-value"
                                                                                        id="metricSubOperaciones">0
                                                                                    </div>
                                                                                    <div class="metric-label">
                                                                                        Operaciones</div>
                                                                                </div>
                                                                            </div> -->
                                                                            <!-- <div class="col-md-3 col-6">
                                                                                <div
                                                                                    class="metric-card metric-card-dark">
                                                                                    <div class="metric-icon text-info">
                                                                                        <i class="bi bi-diagram-3"></i>
                                                                                    </div>
                                                                                 <div class="metric-value"
                                                                                    id="metricSubensamblesCriticos">0</div>
                                                                                <div class="metric-label">Esp. Críticas
                                                                                </div>



                                                                                </div>
                                                                            </div> -->
                                                                        </div>

                                                                        <div class="d-flex flex-wrap gap-2">
                                                                            <button type="button"
                                                                                class="btn btn-primary btn-sm rounded-pill"
                                                                                id="btnSubHerramientas">
                                                                                <i class="bi bi-tools me-1"></i>
                                                                                Herramientas
                                                                            </button>
                                                                            <button type="button"
                                                                                class="btn btn-success btn-sm rounded-pill"
                                                                                id="btnSubComponentes">
                                                                                <i class="bi bi-box-seam me-1"></i>
                                                                                Componentes
                                                                            </button>
                                                                            <!-- <button type="button"
                                                                                class="btn btn-warning btn-sm rounded-pill"
                                                                                id="btnSubOperaciones">
                                                                                <i
                                                                                    class="bi bi-gear-wide-connected me-1"></i>
                                                                                Operaciones
                                                                            </button> -->

                                                                            <!-- <button type="button"
                                                                                class="btn btn-danger btn-sm rounded-pill"
                                                                                id="btnSubEspCriticas">
                                                                                <i
                                                                                    class="bi bi-exclamation-triangle me-1"></i>
                                                                                Especificaciones críticasSS
                                                                            </button> -->


                                                                        </div>







                                                                        <div
                                                                            class="card border-0 shadow-sm rounded-4 mt-3">
                                                                            <div
                                                                                class="card-header bg-body border-0 pb-2">
                                                                                <div
                                                                                    class="d-flex justify-content-between align-items-center">
                                                                                    <div>
                                                                                        <h6 class="mb-1 fw-semibold">
                                                                                            Ayudas visuales del
                                                                                            subensamble</h6>
                                                                                        <p
                                                                                            class="text-body-secondary small mb-0">
                                                                                            Adjunta documentos, imágenes
                                                                                            o archivos de apoyo para
                                                                                            este subensamble
                                                                                        </p>
                                                                                    </div>
                                                                                    <span
                                                                                        class="badge rounded-pill bg-info-subtle text-info-emphasis border border-info-subtle">
                                                                                        <span
                                                                                            id="countAyudasSubensamble">0</span>
                                                                                    </span>
                                                                                </div>
                                                                            </div>

                                                                            <div class="card-body">
                                                                                <div class="row g-3 mb-3">
                                                                                    <div class="col-md-4">
                                                                                        <label
                                                                                            class="form-label">Título</label>
                                                                                        <input type="text"
                                                                                            id="txtTituloAyudaSub"
                                                                                            class="form-control"
                                                                                            placeholder="Ej. Instructivo armado lateral">
                                                                                    </div>

                                                                                    <div class="col-md-3">
                                                                                        <label
                                                                                            class="form-label">Tipo</label>
                                                                                        <select id="selTipoAyudaSub"
                                                                                            class="form-select">
                                                                                            <option value="">Selecciona
                                                                                            </option>
                                                                                            <option
                                                                                                value="Ayuda visual">
                                                                                                Ayuda visual
                                                                                            </option>
                                                                                            <option
                                                                                                value="Intrucciones de trabajo">
                                                                                                Intrucciones de trabajo
                                                                                            </option>
                                                                                            <option value="Diagrama">
                                                                                                Diagrama
                                                                                            </option>
                                                                                        </select>
                                                                                    </div>

                                                                                    <div class="col-md-5">
                                                                                        <label
                                                                                            class="form-label">Archivo</label>
                                                                                        <input type="file"
                                                                                            id="fileAyudaSub"
                                                                                            class="form-control">
                                                                                    </div>
                                                                                </div>

                                                                                <div
                                                                                    class="d-flex justify-content-end mb-3">
                                                                                    <button type="button"
                                                                                        class="btn btn-primary rounded-pill"
                                                                                        id="btnAgregarAyudaSub">
                                                                                        <i
                                                                                            class="bi bi-plus-circle me-1"></i>Agregar
                                                                                        ayuda visual
                                                                                    </button>
                                                                                </div>

                                                                                <div id="listaAyudasSubensamble"></div>

                                                                                <div id="mensajeSinAyudasSub"
                                                                                    class="text-body-secondary small d-none">
                                                                                    No hay ayudas visuales registradas
                                                                                    para este subensamble.
                                                                                </div>
                                                                            </div>
                                                                        </div>







                                                                    </div>
                                                                </div>


                                                            </div>


                                                        </div> 
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- <div class="d-flex justify-content-end gap-2 mt-4">
            <button type="submit" class="btn btn-success rounded-pill px-4">
                <i class="bi bi-check2-circle me-1"></i> Guardar configuración
            </button>
        </div> -->
                                        </form>
                                    </div>
                                    <!-- end pane procesos -->

                                    <!-- PESTAÑA: ESPECIFICACIONES CRÍTICAS -->
                                    <!-- <div class="tab-pane fade" id="pane-especificaciones-criticas" role="tabpanel"
                                        aria-labelledby="tab-especificaciones-criticas">
                                        <div>
                                            <h5 class="mb-1">Especificaciones críticas</h5>
                                            <p class="text-muted mb-4">
                                                Define las especificaciones críticas que deben cumplirse para este
                                                producto.
                                            </p>
                                        </div>

                         
                                        <div class="d-flex align-items-start gap-3 mt-4">
                                            <button type="button" class="btn btn-light btn-label previestab"
                                                data-previous="tab-procesos">
                                                <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                Regresar a procesos
                                            </button>
                                            <button type="button"
                                                class="btn btn-primary btn-label right ms-auto nexttab"
                                                data-nexttab="tab-finalizado">
                                                <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                Ir a finalizado
                                            </button>
                                        </div>
                                    </div> -->
                                    <!-- end pane especificaciones críticas -->

                                    <!-- PESTAÑA: FINALIZADO -->
                                    <div class="tab-pane fade" id="pane-finalizado" role="tabpanel"
                                        aria-labelledby="tab-finalizado">
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
                                                <!-- ID del producto: -->
                                                <div class="text-decoration-underline">
                                                    <span class="text-muted producto_clave">ID:
                                                        P-20251223-0002-V01</span>
                                                </div>






                                            </h3>
                                        </div>
                                    </div>
                                    <!-- end pane finalizado -->

                                </div>
                                <!-- end tab-content -->

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

<!-- MODALES CREADOS PARA LA CONFIGURACIÓN DE PRODUCTOS -->

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

<!-- MODALES PARA ESPECIFICACIONES -->

<div class="modal fade" id="modalEspecificaciones" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog  modal-xl">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModalEspecificaciones">Capturar Operación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    id="close-modal"></button>
            </div>
            <div class="modal-body">

                <div class="card-body form-steps">
                    <form id="formEspecificaciones" name="formEspecificaciones" class="form-steps was-validated"
                        autocomplete="off">

                        <input type="hidden" id="idproducto_especificacion" name="idproducto_especificacion">

                        <input type="hidden" id="idespecificacion" name="idespecificacion" value="0">

                        <input type="hidden" id="idestacion" name="idestacion">

                        <input type="hidden" id="idsubensamble_especificacion" name="idsubensamble_especificacion"
                            value="0">
                        <input type="hidden" id="tipo_contexto_especificacion" name="tipo_contexto_especificacion"
                            value="estacion">

                                    <input type="hidden" id="iddetalle" name="iddetalle"
                            value="">
                        <!-- 
<input type="hidden" id="idproducto_especificacion_sub" name="idproducto_especificacion"> -->
                        <input type="hidden" id="idespecificacionsubensamble" name="idespecificacionsubensamble">
                        <!-- <input type="hidden" id="idsubensamble_especificacion" name="idsubensamble"> -->

                        <input type="hidden" id="es_critica_especificacion" name="es_critica" value="">

                        <div class="row gy-5">
                            <!-- FORMULARIO DOCUMENTOS -->
                            <div class="col-lg-4">
                                <div class="px-lg-4">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="v-pills-bill-address"
                                            role="tabpanel">
                                            <div>
                                                <!-- <h5>Registro de operaciones</h5> -->
                                                <p class="text-muted">
                                                    Captura las operaciones para esta estación
                                                </p>
                                            </div>

                                            <div>
                                                <div class="row g-3">




                                                    <div class="col-12">
                                                        <label for="txtEspecificacion" class="form-label">Ingresa una
                                                            breve descripción
                                                        </label>

                                                        <textarea class="form-control" name="txtEspecificacion"
                                                            id="txtEspecificacion" rows="5" required></textarea>
                                                        <div class="invalid-feedback">El campo
                                                            de descripción es obligatorio</div>
                                                    </div>
                                                </div>

                                                <hr class="my-4 text-muted">
                                            </div>

                                            <div class="d-flex align-items-start gap-3 mt-4">
                                                <button type="submit"
                                                    class="btn btn-success btn-label right ms-auto nexttab"
                                                    data-nexttab="tab-descriptiva-tecnica">
                                                    <i
                                                        class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>


                                                    <span id="btnTextEspecificacion">Registrar</span>


                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TABLA DOCUMENTOS -->
                            <div class="col-lg-8">
                                <div class="px-lg-4">
                                    <div class="tab-content">
                                        <div class="tab-pane fade show active" id="v-pills-bill-address-list"
                                            role="tabpanel">
                                            <div>
                                                <h5>Listado</h5>
                                            </div>

                                            <div id="listEspecificaciones" role="tabpanel">
                                                <table id="tableEspecificaciones"
                                                    class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                                    style="width:100%">
                                                    <thead>
                                                        <tr>
                                                            <!-- <th>#</th> -->
                                                            <th>DESCRIPCIÓN</th>
                                                            <th>FECHA REGISTRO</th>
                                                            <th>OPCIONES</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    </tbody>
                                                </table>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                        <!-- end row -->
                    </form>
                </div>


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


<!-- MODALES PARA COMPONENTES -->

<div class="modal fade" id="modalComponentes" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModalComponentes">Capturar Componentes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    id="close-modal"></button>
            </div>
            <div class="modal-body">



                <!-- Top bar -->
                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                    <div>
                        <h3 class="mb-1 page-title"> <i class="mdi mdi-video-input-component"></i> Inventario
                            Componentes </h3>
                        <div class="text-muted">Seleccione el almacén y gestione los componentes requeridos con
                            cantidades.</div>
                    </div>
                    <div class="d-flex gap-2">

                        <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
                            <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop"
                                colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
                            <div class="small">
                                <span class="text-muted producto_clave">ID:</span><br>
                                <span class="fw-semibold descripcion_producto">MFDS1400457854</span>
                            </div>
                        </div>
                    </div>
                </div>







                <!-- Warehouse selector -->
                <div class="card soft-shadow mb-3">
                    <div class="card-body p-3 p-md-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6 col-lg-6">
                                <label class="form-label mb-1">Almacén</label>
                                <input type="hidden" id="componentes_producto" name="componentes_producto">
                                <input type="hidden" id="estacion_id" name="estacion_id">

                                <input type="hidden" id="subensamble_id_comp" name="subensamble_id_comp">
                                <input type="hidden" id="tipo_contexto_comp" name="tipo_contexto_comp" value="estacion">

                                <select class="form-control" name="listAlmaceneSCompSelect" id="listAlmaceneSCompSelect"
                                    required></select>
                                <div class="form-text">El catálogo se actualizará automáticamente al cambiar el almacén.
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-6">
                                <div class="info-panel">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <p class="title mb-1">Catálogo de inventario</p>
                                            <p class="desc">
                                                Los componentes disponibles se cargan automáticamente con base en el
                                                almacén seleccionado.
                                            </p>
                                        </div>



                                        <!-- <span class="pill">
                  <span class="dot"></span>
                  Almacén activo: <span id="lblAlmacenActual">N/A</span>
                </span> -->
                                    </div>
                                </div>

                                <!-- <div class="text-muted mt-2">
              <small class="mono">
             
              </small>
            </div> -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables -->
                <div class="row g-3">
                    <!-- Catalog -->
                    <div class="col-xl-7">
                        <div class="card soft-shadow h-100">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div>
                                        <div class="section-title">Catálogo por almacén</div>
                                        <p class="section-subtitle">Busque, ordene y seleccione Componentes desde el
                                            inventario.</p>
                                    </div>
                                    <span class="pill"><span class="dot"></span> Catálogo</span>
                                </div>

                                <div class="table-responsive">
                                    <table id="tblCatalogComponentes" class="display table table-hover">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>#</th>
                                                <th>Componente</th>
                                                <!-- <th>En stock</th> -->
                                                <th>Tipo</th>
                                                <th>Unidad</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <!-- <div class="alert alert-warning border-0" role="alert" id="msgSelectAlmacen">
                                                    <strong>  Selecciona un almacén </strong> para visualizar los componentes disponibles. 
                                                </div> -->

                            </div>
                        </div>
                    </div>

                    <!-- Selected -->
                    <div class="col-xl-5">
                        <div class="card soft-shadow h-100">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div>
                                        <div class="section-title">Componentes seleccionados</div>
                                        <p class="section-subtitle">Indique la cantidad requerida por componente.</p>
                                    </div>
                                    <!-- <span class="pill"><span class="dot"></span> Seleccionados : <span id="countSelected">0</span></span> -->

                                    <span class="badge badge-label bg-warning">
                                        <span id="countRuta">Seleccionados: </span> <span id="countSelected">0</span>
                                    </span>



                                </div>

                                <div class="table-responsive">
                                    <table id="tblSelectedComponentes"
                                        class="display table table-striped align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>#</th>
                                                <th>Componente</th>
                                                <th>Tipo</th>
                                                <th>Unidad</th>
                                                <th>Cantidad</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="text-muted mt-2">
                                    <small class="mono">Regla: cantidad mínima 1.</small>
                                </div>


                                <div id="saveBar" class="save-bar">
                                    <button id="btnGuardarTodo" class="btn btn-success btn-save-all">
                                        Guardar todo
                                    </button>
                                    <!-- <div class="text-muted mt-2">
                <small>Se enviará el detalle por componente (una fila por registro) al backend.</small>
              </div> -->
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- 
    <div class="mt-3 text-muted">
      <small class="mono">Nota: El botón “Guardar todo” se muestra automáticamente cuando hay al menos 1 componente agregado.</small>
    </div> -->

                <!-- <div class="alert alert-danger alert-border-left alert-dismissible fade show mb-xl-0" role="alert">
                                                    <i class="ri-error-warning-line me-3 align-middle fs-16"></i><strong>Nota</strong>
                                                    - El botón “Guardar todo” se muestra automáticamente cuando hay al menos 1 componente agregado.
                                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                                </div> -->


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

<!-- MODALES PARA HERRAMIENTAS -->

<div class="modal fade" id="modalHerramientas" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title" id="titleModalHerramientas">Capturar Herramientas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    id="close-modal"></button>
            </div>
            <div class="modal-body">


                <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
                    <div>
                        <h3 class="mb-1 page-title"><i class="mdi mdi-tools"></i>Inventario Herramientas</h3>
                        <div class="text-muted">Seleccione el almacén y gestione las herramientas requeridas con
                            cantidades.</div>
                    </div>
                    <div class="d-flex gap-2">

                        <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
                            <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop"
                                colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
                            <div class="small">
                                <span class="text-muted producto_clave">ID:</span><br>
                                <span class="fw-semibold descripcion_producto">MFDS1400457854</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Warehouse selector -->
                <div class="card soft-shadow mb-3">
                    <div class="card-body p-3 p-md-3">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-6 col-lg-6">
                                <label class="form-label mb-1">Almacén</label>

                                <input type="hidden" id="herramientas_producto" value="">
                                <input type="hidden" id="estacion_id_herr" value="">

                                <input type="hidden" id="subensamble_id_herr" value="">
                                <input type="hidden" id="tipo_contexto_herr" value="estacion">


                                <select class="form-control" name="listAlmacenesHerrSelect" id="listAlmacenesHerrSelect"
                                    required></select>
                                <div class="form-text">El catálogo se actualizará automáticamente al cambiar el almacén.
                                </div>
                            </div>

                            <div class="col-md-6 col-lg-6">
                                <div class="info-panel">
                                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                        <div>
                                            <p class="title mb-1">Catálogo de inventario</p>
                                            <p class="desc">
                                                Las herramientas disponibles se cargan automáticamente con base en el
                                                almacén seleccionado.
                                            </p>
                                        </div>

                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tables -->
                <div class="row g-3">
                    <!-- Catalog -->
                    <div class="col-xl-7">
                        <div class="card soft-shadow h-100">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div>
                                        <div class="section-title">Catálogo por almacén</div>
                                        <p class="section-subtitle">Busque, ordene y seleccione herramientas desde el
                                            inventario.</p>
                                    </div>
                                    <span class="pill"><span class="dot"></span> Catálogo</span>
                                </div>

                                <div class="table-responsive">
                                    <table id="tblCatalogHerramientas" class="display table table-hover">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>#</th>
                                                <th>Herramienta</th>
                                                <th>Tipo</th>
                                                <th>Unidad</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>


                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Selected -->
                    <div class="col-xl-5">
                        <div class="card soft-shadow h-100">
                            <div class="card-body p-3 p-md-4">
                                <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
                                    <div>
                                        <div class="section-title">Herramientas seleccionadas</div>
                                        <p class="section-subtitle">Indique la cantidad requerida por herramienta.</p>
                                    </div>
                                    <span class="pill"><span class="dot"></span> Seleccionados: <span
                                            id="countSelected">0</span></span>
                                </div>

                                <div class="table-responsive">
                                    <table id="tblSelectedHerramientas"
                                        class="display table table-striped align-middle mb-0">
                                        <thead>
                                            <tr class="text-muted">
                                                <th>#</th>
                                                <th>Herramienta</th>
                                                <th>Tipo</th>
                                                <th>Unidad</th>
                                                <th>Cantidad</th>
                                                <th class="text-end">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                                <div class="text-muted mt-2">
                                    <small class="mono">Regla: cantidad mínima 1.</small>
                                </div>

                                <div id="saveBarHerr" class="save-bar">
                                    <button id="btnGuardarTodoHerramientas" class="btn btn-success btn-save-all">
                                        Guardar todo
                                    </button>
                                    <!-- <div class="text-muted mt-2">
                <small>Se enviará el detalle por herramienta (una fila por registro) al backend.</small>
              </div> -->
                                </div>

                            </div>
                        </div>
                    </div>
                </div>


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
</div>



<div class="modal fade" id="modalPdi" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0">
            <div class="modal-header bg-light-subtle">
                <div>
                    <h5 class="modal-title mb-1">
                        <i class="ri-clipboard-line me-2 text-primary"></i>
                        Configuración PDI por estación
                    </h5>
                    <p class="text-muted mb-0 small">
                        Defina zonas de inspección y puntos a evaluar para esta estación.
                    </p>
                </div>
                <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">
                <div class="row g-0">
                    <!-- PANEL IZQUIERDO -->
                    <div class="col-lg-4 border-end">
                        <div class="p-3">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h6 class="mb-1">Zonas de inspección</h6>
                                    <small class="text-muted">Seleccione una zona o cree una nueva.</small>
                                </div>
                                <button type="button" class="btn btn-primary btn-sm rounded-pill" id="btnNuevaZonaPdi">
                                    <i class="ri-add-line me-1"></i> Zona
                                </button>
                            </div>

                            <div id="listaZonasPdi" class="pdi-zonas-list"></div>

                            <div id="msgSinZonasPdi" class="text-muted small py-3">
                                No hay zonas registradas para esta estación.
                            </div>
                        </div>
                    </div>

                    <!-- PANEL DERECHO -->
                    <div class="col-lg-8">
                        <div class="p-3">
                            <div class="row g-3 mb-3">
                                <div class="col-md-7">
                                    <label class="form-label">Zona</label>
                                    <input type="text" id="txtZonaPdiNombre" class="form-control"
                                        placeholder="Ej. Zona Izquierda de Cabina Exterior" autocomplete="off">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Referencia</label>
                                    <input type="text" id="txtZonaPdiReferencia" class="form-control"
                                        placeholder="Ej. Lateral izquierdo" autocomplete="off">
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill"
                                    id="btnGuardarZonaPdi">
                                    <i class="ri-save-line me-1"></i> Guardar zona
                                </button>

                                <button type="button" class="btn btn-outline-danger btn-sm rounded-pill"
                                    id="btnEliminarZonaPdi">
                                    <i class="ri-delete-bin-line me-1"></i> Eliminar zona
                                </button>

                                <button type="button" class="btn btn-success btn-sm rounded-pill ms-auto"
                                    id="btnNuevoPuntoPdi">
                                    <i class="ri-add-line me-1"></i> Agregar punto
                                </button>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-sm align-middle table-bordered mb-0">
                                    <!-- <thead class="table-light text-center">
                                        <tr>
                                            <th style="width: 60px;">No.</th>
                                            <th style="min-width: 320px;">Punto a inspeccionar</th>
                                            <th style="width: 70px;">CHI</th>
                                            <th style="width: 70px;">MEX</th>
                                            <th style="width: 70px;">I1</th>
                                            <th style="width: 70px;">I2</th>
                                            <th style="width: 70px;">I3</th>
                                            <th style="width: 70px;">I4</th>
                                            <th style="width: 150px;">Opciones</th>
                                        </tr>
                                    </thead> -->

                                    <thead class="table-light text-center">
    <tr>
        <th style="width: 60px;">No.</th>
        <th style="min-width: 320px;">Punto a inspeccionar</th>

        <th class="d-none">CHI</th>
        <th class="d-none">MEX</th>
        <th class="d-none">I1</th>
        <th class="d-none">I2</th>
        <th class="d-none">I3</th>
        <th class="d-none">I4</th>

        <th style="width: 150px;">Opciones</th>
    </tr>
</thead>
                                    <tbody id="tbodyModalPdi"></tbody>
                                </table>
                            </div>

                            <div id="msgPdiVacio" class="text-muted small mt-3">
                                Selecciona o crea una zona para comenzar.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer bg-light-subtle">
                <div class="me-auto text-muted small">
                    Los cambios se mantienen temporalmente en memoria/localStorage hasta guardar.
                </div>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    Cerrar
                </button>
                <button type="button" class="btn btn-success" id="btnGuardarTodoPdi">
                    <i class="ri-check-double-line me-1"></i> Guardar todo
                </button>
            </div>
        </div>
    </div>
</div>



<div class="modal fade" id="modalAyudas" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="bi bi-images me-2"></i> Ayudas visuales
                </h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">

                <div class="row g-2 mb-3">
                    <div class="col-md-4">
                        <input type="text" id="txtTituloAyuda" class="form-control" placeholder="Título"
                            autocomplete="off">
                    </div>

                    <div class="col-md-4">
                        <select id="selTipoAyuda" class="form-select">
                            <option value="">Tipo</option>
                            <option value="Ayuda visual">Ayuda visual</option>
                            <option value="Intrucciones de trabajo">Intrucciones de trabajo</option>
                            <option value="Diagrama">Diagrama</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <input type="file" id="fileAyuda" class="form-control" autocomplete="off">
                    </div>
                </div>

                <button class="btn btn-primary btn-sm mb-3" id="btnAgregarAyuda">
                    <i class="bi bi-plus"></i> Agregar ayuda
                </button>

                <ul class="list-group" id="listaAyudas"></ul>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" id="btnGuardarTodoAyudas">
                    <i class="bi bi-check2-circle me-1"></i> Guardar todo
                </button>
            </div>
        </div>
    </div>
</div>


<!-- end main content-->
<?php footerAdmin($data); ?>