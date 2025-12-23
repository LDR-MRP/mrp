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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listplantas" role="tab">
                                Lista de distribuidores
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarplanta" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listplantas" role="tabpanel">

                            <table id="cli_distribuidores"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>GRUPO</th>
                                        <th>TIPO DE NEGOCIO</th>
                                        <th>NOMBRE COMERCIAL</th>
                                        <th>RAZON SOCIAL</th>
                                        <th>PLAZA</th>
                                        <th>ESTADO</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->

                        <div class="tab-pane" id="agregarplanta" role="tabpanel">
                            <form id="formPlantas" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idplanta" name="idplanta">
                                <div class="row">
                                    <!-- select grupo_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listGrupos">GRUPOS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Gru</span>
                                                <select class="form-control" id="listGrupos" name="listGrupos" required=""></select>
                                                <div class="invalid-feedback">El campo grupo es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo nombre_comercial -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre_comercial-input">NOMBRE COMERCIAL</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre_comercial-addon">Nom</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el nombre comercial" id="nombre_comercial-input" name="nombre_comercial-input"
                                                    aria-describedby="nombre_comercial-addon" required>
                                                <div class="invalid-feedback">El campo de nombre comercial es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo razon_social -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="razon_social-input">RAZÓN SOCIAL</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="razon_social-addon">Razón Social</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la razón social" id="razon_social-input" name="razon_social-input"
                                                    aria-describedby="razon_social-addon" required>
                                                <div class="invalid-feedback">El campo de razón social es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo rfc -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="rfc-input">RFC</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="rfc-addon">RFC</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el RFC" id="rfc-input" name="rfc-input"
                                                    aria-describedby="rfc-addon" required>
                                                <div class="invalid-feedback">El campo de RFC es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo rpve -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="rpve">No. REPUVE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="rpve-addon">No. REPUVE</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el No. REPUVE" id="rpve" name="rpve"
                                                    aria-describedby="rpve-addon" required>
                                                <div class="invalid-feedback">El campo No. REPUVE es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>


                                    <!-- campo plaza -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="plaza-input">PLAZA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="plaza-addon">Plaza</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la plaza" id="plaza-input" name="plaza-input"
                                                    aria-describedby="plaza-addon" required>
                                                <div class="invalid-feedback">El campo de plaza es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo estatus 'Activo', 'Desarrollo', 'Inactivo' -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estatus-select">ESTATUS</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estatus-addon">Est</span>
                                                <select class="form-select" id="estatus-select" name="estatus-select"
                                                    aria-describedby="estatus-addon" required>
                                                    <option value="Activo" selected>Activo</option>
                                                    <option value="Desarrollo">Desarrollo</option>
                                                    <option value="Inactivo">Inactivo</option>
                                                </select>
                                                <div class="invalid-feedback">El campo estatus es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo tipo_negocio 'Matriz', 'Sucursal'  -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo_negocio-select">TIPO DE NEGOCIO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="tipo_negocio-addon">Tipo</span>
                                                <select class="form-select" id="tipo_negocio-select" name="tipo_negocio-select"
                                                    aria-describedby="tipo_negocio-addon" required>
                                                    <option value="Matriz" selected>Matriz</option>
                                                    <option value="Sucursal">Sucursal</option>
                                                </select>
                                                <div class="invalid-feedback">El campo tipo de negocio es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo telefono -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="telefono-input">TELEFONO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="telefono-addon">Tel</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el telefono" id="telefono-input" name="telefono-input"
                                                    aria-describedby="telefono-addon" required>
                                                <div class="invalid-feedback">El campo telefono es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo telefono_alt -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="telefono_alt-input">TELEFONO ALTERNATIVO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="telefono_alt-addon">Tel Alt</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el telefono alternativo" id="telefono_alt-input" name="telefono_alt-input"
                                                    aria-describedby="telefono_alt-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mb-4">

                                    <!-- campo tipo tipo 'Fiscal', 'Comercial', 'Taller', 'Sucursal' -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-input">TIPO</label>
                                            <select class="form-select" id="tipo-input" name="tipo-input" required>
                                                <option value="" disabled selected>Selecciona un tipo</option>
                                                <option value="Fiscal">Fiscal</option>
                                                <option value="Comercial">Comercial</option>
                                                <option value="Taller">Taller</option>
                                                <option value="Sucursal">Sucursal</option>
                                            </select>
                                            <div class="invalid-feedback">El campo tipo es obligatorio</div>
                                        </div>
                                    </div>

                                    <!-- campo calle -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="calle-input">CALLE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="calle-addon">Calle</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la calle" id="calle-input" name="calle-input"
                                                    aria-describedby="calle-addon" required>
                                                <div class="invalid-feedback">El campo calle es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo numero_ext -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="numero_ext-input">NUMERO EXTERIOR</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="numero_ext-addon">Número Exterior</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el número exterior" id="numero_ext-input"
                                                    name="numero_ext-input" aria-describedby="numero_ext-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo numero_int -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="numero_int-input">NUMERO INTERIOR</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="numero_int-addon">Número Interior</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el número interior" id="numero_int-input"
                                                    name="numero_int-input" aria-describedby="numero_int-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo colonia -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="colonia-input">COLONIA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="colonia-addon">Colonia</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la colonia" id="colonia-input" name="colonia-input"
                                                    aria-describedby="colonia-addon" required>
                                                <div class="invalid-feedback">El campo colonia es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo codigo_postal -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="codigo_postal-input">CÓDIGO POSTAL</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="codigo_postal-addon">Código Postal</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el código postal" id="codigo_postal-input"
                                                    name="codigo_postal-input" aria-describedby="codigo_postal-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- select pais_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listPaises">PAÍS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Pai</span>
                                                <select class="form-control" id="listPaises" name="listPaises" required=""></select>
                                                <div class="invalid-feedback">El campo país es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- select estado_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listEstados">ESTADOS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Est</span>
                                                <select class="form-control" id="listEstados" name="listEstados" required=""></select>
                                                <div class="invalid-feedback">El campo estado es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- select municipio_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listMunicipios">MUNICIPIOS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>Mun</span>
                                                <select class="form-control" id="listMunicipios" name="listMunicipios" required=""></select>
                                                <div class="invalid-feedback">El campo municipio es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo latitud  -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="latitud">LATITUD</label>
                                            <input type="text" class="form-control" id="latitud" name="latitud"
                                                placeholder="Latitud del cliente">
                                        </div>
                                    </div>

                                    <!-- campo longitud -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="longitud">LONGITUD</label>
                                            <input type="text" class="form-control" id="longitud" name="longitud"
                                                placeholder="Longitud del cliente">
                                        </div>
                                    </div>

                                    <!-- campo estado -->
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estado-select">ESTADO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estado-addon">Est</span>
                                                <select class="form-select" id="estado-select" name="estado-select"
                                                    aria-describedby="estado-addon" required>
                                                    <option value="2" selected>Activo</option>
                                                    <option value="1">inactivo</option>
                                                </select>
                                                <div class="invalid-feedback">El campo estado es obligatorio</div>
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

<div class="modal fade" id="modalViewCliente" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
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
                            <td id="idcliente">654654654</td>
                        </tr>
                        <tr>
                            <td>GRUPO:</td>
                            <td id="nombregrupo">654654654</td>
                        </tr>
                        <tr>
                            <td>TIPO DE NEGOCIO:</td>
                            <td id="tiponegocio">654654654</td>
                        </tr>
                        <tr>
                            <td>NOMBRE COMERCIAL:</td>
                            <td id="nombrecomercial">654654654</td>
                        </tr>
                        <tr>
                            <td>RAZON SOCIAL:</td>
                            <td id="razonsocial">654654654</td>
                        </tr>
                        <tr>
                            <td>RFC:</td>
                            <td id="rfc">654654654</td>
                        </tr>
                        <tr>
                            <td>No. REPUVE:</td>
                            <td id="repve">654654654</td>
                        </tr>
                        <tr>
                            <td>PLAZA:</td>
                            <td id="plaza">654654654</td>
                        </tr>
                        <tr>
                            <td>TELÉFONO</td>
                            <td id="telefono">654654654</td>
                        </tr>
                        <tr>
                            <td>TELÉFONO ALT:</td>
                            <td id="telefonoalt">654654654</td>
                        </tr>

                        <tr>
                            <td>TIPO</td>
                            <td id="tipo">654654654</td>
                        </tr>
                        <tr>
                            <td>CALLE</td>
                            <td id="calle">654654654</td>
                        </tr>
                        <tr>
                            <td>NÚMERO EXT</td>
                            <td id="numero_ext">654654654</td>
                        </tr>
                        <tr>
                            <td>NÚMERO INT</td>
                            <td id="numero_int">654654654</td>
                        </tr>
                        <tr>
                            <td>COLONIA</td>
                            <td id="colonia">654654654</td>
                        </tr>
                        <tr>
                            <td>CODIGO POSTAL</td>
                            <td id="codigo_postal">654654654</td>
                        </tr>
                        <tr>
                            <td>PAÍS</td>
                            <td id="pais">654654654</td>
                        </tr>
                        <tr>
                            <td>ESTADO</td>
                            <td id="estado_id">654654654</td>
                        </tr>
                        <tr>
                            <td>MUNICÍPIO</td>
                            <td id="municipio">654654654</td>
                        </tr>
                        <tr>
                            <td>LATITUD</td>
                            <td id="latitud_direccion">654654654</td>
                        </tr>
                        <tr>
                            <td>LONGITUD</td>
                            <td id="longitud_direccion">654654654</td>
                        </tr>
                        <tr>
                            <td>FECHA DE REGISTRO:</td>
                            <td id="fecharegistro">654654654</td>
                        </tr>
                        <tr>
                            <td>Estado:</td>
                            <td id="celEstado">654654654</td>
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