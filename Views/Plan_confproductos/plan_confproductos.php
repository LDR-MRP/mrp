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
                            <li class="breadcrumb-item"><a href="javascript: void(0);">MRP - test carlos cruuuz</a></li>
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
                                                            <select class="form-control form-disabled" name="listLineasProductos"
                                                                id="listLineasProductos" required></select>
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
                            
<div class="tab-pane fade" id="pane-documentacion" role="tabpanel" aria-labelledby="tab-documentacion">

    <hr>


<div class="row g-3 align-items-center mb-4 px-lg-4">


  <div class="col-lg-8">
    <h5 class="mb-1">Documentación</h5>
    <p class="text-muted mb-0">
      Captura la documentación inicial del producto y consulta el listado de archivos.
    </p>
  </div>


  <div class="col-lg-4 text-lg-end">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
      <div class="small">
        <span class="text-muted producto_clave">ID:</span><br>
        <span class="fw-semibold descripcion_producto">MFDS1400457854</span>
      </div>
    </div>
  </div>

</div>


    <!-- CONTENIDO DOCUMENTACIÓN -->
    <div class="card-body form-steps">
        <form id="formDocumentacion" name="formDocumentacion"
              class="form-steps was-validated" autocomplete="off">

            <input type="hidden" id="idproducto_documentacion" name="idproducto_documentacion">

            <div class="row gy-5">

                <!-- FORMULARIO DOCUMENTOS -->
                <div class="col-lg-4">
                    <div class="px-lg-4">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="v-pills-bill-address" role="tabpanel">

                                <div class="mb-3">
                                    <h5>Registro de documentación</h5>
                                    <p class="text-muted mb-0">
                                        Captura la documentación inicial del producto
                                    </p>
                                </div>

                                <div class="row g-3">

                                    <div class="col-12">
                                        <label for="tipoDocumento" class="form-label">Tipo de documento</label>
                                        <select name="tipoDocumento" id="tipoDocumento" class="form-control" required>
                                            <option value="" selected>--Seleccione--</option>
                                            <option value="Ayuda visual">Ayuda Visual</option>
                                            <option value="Diagrama">Diagrama</option>
                                        </select>
                                        <div class="invalid-feedback">
                                            El campo tipo de documento es obligatorio
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="txtDescripcionDocumento" class="form-label">
                                            Descripción del documento
                                        </label>
                                        <input type="text"
                                               class="form-control"
                                               id="txtDescripcionDocumento"
                                               name="txtDescripcionDocumento"
                                               placeholder="Ingresa una breve descripción del documento a adjuntar"
                                               required>
                                        <div class="invalid-feedback">
                                            El campo de descripción es obligatorio
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <label for="txtFile" class="form-label">Archivo(s)</label>
                                        <input type="file"
                                               class="form-control"
                                               id="txtFile"
                                               name="txtFile"
                                               required>
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
                                        <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
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
                            <div class="tab-pane fade show active" id="v-pills-bill-address-list" role="tabpanel">

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
    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
      <div class="small">
        <span class="text-muted producto_clave">ID:</span><br>
        <span class="fw-semibold descripcion_producto">MFDS1400457854</span>
      </div>
    </div>
  </div>

</div>

                                   

                                        <form id="formConfDescriptiva" name="formConfDescriptiva"
                                            class="form-steps was-validated" autocomplete="off">
                                     

                                            <input type="hidden" id="idproducto_descriptiva" name="idproducto_descriptiva">

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
                                                        <label class="form-label"
                                                            for="txtDesplazamiento">Desplazamiento</label>
                                                        <div class="input-group mb-3">
                                                            <span class="input-group-text">Des</span>
                                                            <input type="text" class="form-control"
                                                                placeholder="Ej: 1.6L" id="txtDesplazamiento"
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
                                                <div class="col-lg-6">
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
                                                <div class="col-lg-6">
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
                                        <!-- <div>
                                            <h5 class="mb-1">Procesos</h5>
                                            <p class="text-muted mb-4">
                                                Información de los procesos relacionados con este producto
                                            </p>
                                        </div> -->

                                                                                
<div class="row g-3 align-items-center mb-4 px-lg-4">


  <div class="col-lg-8">
    <h5 class="mb-1">Procesos</h5>
    <p class="text-muted mb-0">
       Información de los procesos relacionados con este producto
    </p>
  </div>


  <div class="col-lg-4 text-lg-end">
    <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
      <div class="small">
        <span class="text-muted producto_clave">ID:</span><br>
        <span class="fw-semibold descripcion_producto">MFDS1400457854</span>
      </div>
    </div>
  </div>

</div>


                                             <form id="formRutaProducto" class="form-steps was-validated" >

                                             <input type="hidden" id="idproducto_proceso" name="idproducto_proceso">
                                                 <input type="hidden" id="id_ruta_producto" name="id_ruta_producto">


          
                <div class="row g-3 mb-3">

                    <!-- Planta -->
                    <div class="col-lg-5 col-md-5">
                        <label for="listPlantasSelect" class="form-label">Planta</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-building"></i></span>
                            <select id="listPlantasSelect" name="listPlantasSelect" class="form-select" required>
                            
                            </select>
                            <div class="invalid-feedback">
                                Debe seleccionar una planta.
                            </div>
                        </div>
                    </div>

                    <!-- Línea -->
                    <div class="col-lg-7 col-md-7">
                        <label for="listLineasSelect" class="form-label">Línea de producción</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-diagram-3"></i></span>
                            <select id="listLineasSelect" name="listLineasSelect" class="form-select" required>
                                <option value="">Seleccione una planta primero...</option>
                            </select>
                            <div class="invalid-feedback">
                                Debe seleccionar una línea de producción.
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Títulos secciones estaciones -->
                <div class="row mb-2">
                    <div class="col-md-5">
                        <h6 class="text-muted text-uppercase mb-2">
                            Estaciones disponibles
                        </h6>
                        <p class="text-muted small mb-0">
                            Selecciona planta y línea para visualizar las estaciones disponibles.
                            Puedes arrastrarlas o dar clic para agregarlas a la ruta del producto.
                        </p>
                    </div>
                    <div class="col-md-7 mt-3 mt-md-0">
                        <h6 class="text-muted text-uppercase mb-2">
                            Ruta del producto
                        </h6>
                        <p class="text-muted small mb-0">
                            Arrastra o agrega por clic las estaciones en el orden en que el producto será procesado.
                            Puedes reordenar y eliminar estaciones.
                        </p>
                    </div>
                </div>

   
                <div class="row g-4 align-items-stretch">
                    <!-- Estaciones disponibles -->
                    <div class="col-md-5">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-list-ul me-1"></i> Estaciones disponibles</span>
                                    <span class="badge badge-label bg-success">
                                        <span id="countEstacionesDisponibles">0</span> estación(es)
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="listaEstaciones" class="list-group small">
                              
                                </div>
                                <div id="mensajeSinEstaciones" class="text-muted small mt-2 d-none">
                                    No hay estaciones configuradas para la planta y línea seleccionadas.
                                </div>
                            </div>
                        </div>
                    </div>

                
                    <div class="col-md-7">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-arrow-down-up me-1"></i> Ruta del producto</span>
                                    <span class="badge badge-label bg-warning">
                                        <span id="countRuta">0</span> estación(es) en ruta
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="dropRuta"
                                     class="dropzone"
                                     ondragover="allowDrop(event)"
                                     ondrop="dropOnRuta(event)"
                                     ondragleave="dragLeaveRuta(event)"> 
                                    <p id="placeholderRuta" class="text-muted small mb-0">
                                        Arrastre aquí las estaciones o haga clic sobre ellas para construir la ruta del producto.
                                    </p>

 <div class="table-responsive">
  <table class="table table-sm align-middle table-hover mb-0 tabla-ruta">
    <thead class="table-light">
      <tr> 
        <th style="width: 60px;">#</th>
        <th style="width: 150px!important;">Estaciones</th>
        <th style="width: 150px;">Especificaciones</th>
        <th style="width: 130px;">Componentes</th>
        <th style="width: 130px;">Herramientas</th>
        <th class="text-end" style="width: 160px;">Opciones</th>
      </tr>
    </thead>
    <tbody id="listaRuta"></tbody>
  </table>
</div>

<!-- <div id="placeholderRuta" class="text-muted small mt-2">
  Aún no hay estaciones en la ruta.
</div> -->

                                </div>
                                <input type="hidden" name="ruta_estaciones" id="ruta_estaciones">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="reset" class="btn btn-outline-secondary" onclick="resetFormulario()">
                        <i class="bi bi-x-circle me-1"></i> Limpiar
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check2-circle me-1"></i> Guardar configuración
                    </button>
                </div>
            </form>


                                        <!-- <div class="d-flex align-items-start gap-3 mt-4">
                                            <button type="button" class="btn btn-light btn-label previestab"
                                                data-previous="tab-descriptiva-tecnica">
                                                <i class="ri-arrow-left-line label-icon align-middle fs-16 me-2"></i>
                                                Regresar a descriptiva técnica
                                            </button>
                                            <button type="button"
                                                class="btn btn-primary btn-label right ms-auto nexttab"
                                                data-nexttab="tab-especificaciones-criticas">
                                                <i class="ri-arrow-right-line label-icon align-middle fs-16 ms-2"></i>
                                                Continuar a especificaciones críticas
                                            </button>
                                        </div> -->
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
                                                     <span class="text-muted producto_clave">ID: P-20251223-0002-V01</span>
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
        <h5 class="modal-title" id="titleModalEspecificaciones">Capturar Especificaciones</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
      </div>
  <div class="modal-body">

    <div class="card-body form-steps">
                                            <form id="formEspecificaciones" name="formEspecificaciones"
                                                class="form-steps was-validated" autocomplete="off">

                                                <input type="hidden" id="idproducto_especificacion"
                                                    name="idproducto_especificacion">

                                                           <input type="hidden" id="idespecificacion"
                                                    name="idespecificacion" value="0">

                                                                                       <input type="hidden" id="idestacion"
                                                    name="idestacion">

                                                <div class="row gy-5">
                                                    <!-- FORMULARIO DOCUMENTOS -->
                                                    <div class="col-lg-4">
                                                        <div class="px-lg-4">
                                                            <div class="tab-content">
                                                                <div class="tab-pane fade show active"
                                                                    id="v-pills-bill-address" role="tabpanel">
                                                                    <div>
                                                                        <h5>Registro de especificaciones</h5>
                                                                        <p class="text-muted">
                                                                            Captura las especificaciones criticas para esta estación
                                                                        </p>
                                                                    </div>

                                                                    <div>
                                                                        <div class="row g-3">




                                                                            <div class="col-12">
                                                                                <label for="txtEspecificacion"
                                                                                    class="form-label">Ingresa una breve descripción
                                                                                </label>
                                    
                                                                                    <textarea class="form-control" name="txtEspecificacion" id="txtEspecificacion" rows="5" required></textarea>
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
                                                                <div class="tab-pane fade show active"
                                                                    id="v-pills-bill-address-list" role="tabpanel">
                                                                    <div>
                                                                        <h5>Listado de especificaciones</h5>
                                                                    </div>

                                                                    <div id="listEspecificaciones" role="tabpanel">
                                                                        <table id="tableEspecificaciones"
                                                                            class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                                                            style="width:100%">
                                                                            <thead>
                                                                                <tr>
                                                                                    <th>#</th>
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
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
      </div>
  <div class="modal-body">

  

    <!-- Top bar -->
    <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
      <div>
        <h3 class="mb-1 page-title"> <i class="mdi mdi-video-input-component"></i> Inventario Componentes </h3>
        <div class="text-muted">Seleccione el almacén y gestione los componentes requeridos con cantidades.</div>
      </div>
      <div class="d-flex gap-2">

            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
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

            <select class="form-control" name="listAlmaceneSCompSelect"
            id="listAlmaceneSCompSelect" required></select>
            <div class="form-text">El catálogo se actualizará automáticamente al cambiar el almacén.</div>
          </div>

          <div class="col-md-6 col-lg-6">
            <div class="info-panel">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <p class="title mb-1">Catálogo de inventario</p>
                  <p class="desc">
                    Los componentes disponibles se cargan automáticamente con base en el almacén seleccionado.
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
                <p class="section-subtitle">Busque, ordene y seleccione Componentes desde el inventario.</p>
              </div>
              <span class="pill"><span class="dot"></span> Catálogo</span>
            </div>

            <div class="table-responsive">
              <table id="tblCatalogComponentes" class="display table table-hover">
                <thead>
                  <tr class="text-muted">
                    <th>#</th>
                    <th>Componente</th>
                     <th>En stock</th>
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
              <table id="tblSelectedComponentes" class="display table table-striped align-middle mb-0">
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
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
      </div>
  <div class="modal-body">

  

    <!-- Top bar -->
    <!-- <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
      <div>
        <h3 class="mb-1 page-title"><i class="mdi mdi-tools"></i>Inventario Herramientas</h3>
        <div class="text-muted">Seleccione el almacén y gestione las herramientas requeridas con cantidades.</div>
      </div>
      <div class="d-flex gap-2">
        <button id="btnClear" class="btn btn-outline-danger">Limpiar</button>
        <button id="btnExport" class="btn btn-primary">Exportar JSON</button>
      </div>
    </div> -->

        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 gap-2">
      <div>
    <h3 class="mb-1 page-title"><i class="mdi mdi-tools"></i>Inventario Herramientas</h3>
        <div class="text-muted">Seleccione el almacén y gestione las herramientas requeridas con cantidades.</div>
      </div>
      <div class="d-flex gap-2">

            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 rounded-3 border bg-light">
    <lord-icon src="https://cdn.lordicon.com/uetqnvvg.json" trigger="loop" colors="primary:#25a0e2,secondary:#00bd9d" style="width:80px;height:80px"></lord-icon>
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


                           <select class="form-control" name="listAlmacenesHerrSelect"
                                                                id="listAlmacenesHerrSelect" required></select>
            <div class="form-text">El catálogo se actualizará automáticamente al cambiar el almacén.</div>
          </div>

          <div class="col-md-6 col-lg-6">
            <div class="info-panel">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <p class="title mb-1">Catálogo de inventario</p>
                  <p class="desc">
                    Las herramientas disponibles se cargan automáticamente con base en el almacén seleccionado.
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
                <p class="section-subtitle">Busque, ordene y seleccione herramientas desde el inventario.</p>
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

            <!-- <div class="alert alert-warning border-0" role="alert" id="msgSelectAlmacen">
                                                    <strong>  Selecciona un almacén </strong> para visualizar las herramientas disponibles. 
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
                <div class="section-title">Herramientas seleccionadas</div>
                <p class="section-subtitle">Indique la cantidad requerida por herramienta.</p>
              </div>
              <span class="pill"><span class="dot"></span> Seleccionados: <span id="countSelected">0</span></span>
            </div>

            <div class="table-responsive">
              <table id="tblSelectedHerramientas" class="display table table-striped align-middle mb-0">
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

    <!-- <div class="mt-3 text-muted">
      <small class="mono">Nota: El botón “Guardar todo” se muestra automáticamente cuando hay al menos 1 herramienta agregada.</small>
    </div> -->

    <!-- <div class="alert alert-danger alert-border-left alert-dismissible fade show mb-xl-0" role="alert">
                                                    <i class="ri-error-warning-line me-3 align-middle fs-16"></i><strong>Nota</strong>
                                                    - El botón “Guardar todo” se muestra automáticamente cuando hay al menos 1 herramienta agregada.
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


<!-- end main content-->
<?php footerAdmin($data); ?>