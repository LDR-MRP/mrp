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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listdistribuidores" role="tab">
                                Lista de distribuidores
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregardistribuidores" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listdistribuidores" role="tabpanel">

                            <table id="cli_distribuidores"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>GRUPO</th>
                                        <th>TIPO DE NEGOCIO</th>
                                        <th>NOMBRE COMERCIAL</th>
                                        <th>RAZÓN SOCIAL</th>
                                        <th>REGIÓN</th>
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

                        <div class="tab-pane" id="agregardistribuidores" role="tabpanel">
                            <form id="formDistribuidores" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="iddistribuidor" name="iddistribuidor">
                                <div class="row">
                                    <!-- select persona Física o Moral   -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo_persona-select">TIPO DE PERSONA</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="tipo_persona-addon">TIP</span>
                                                <select class="form-select" id="tipo_persona-select" name="tipo_persona-select"
                                                    aria-describedby="tipo_persona-addon" required>
                                                    <option value="" selected disabled>Seleccione una opción</option>
                                                    <option value="1">Física</option>
                                                    <option value="2">Moral</option>
                                                </select>
                                                <div class="invalid-feedback">El campo tipo de persona es obligatorio </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="personaFisicaWrapper" class="row">
                                        <!-- campo nombrefisica -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="nombre_fisica-distribuidores-input">NOMBRE</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="nombre_fisica-distribuidores-addon">NOM</span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Ingresa el nombre" id="nombre_fisica-distribuidores-input" name="nombre_fisica-distribuidores-input"
                                                        aria-describedby="nombre_fisica-distribuidores-addon" required>
                                                    <div class="invalid-feedback">El campo de nombre es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- campo apellido paterno -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="apellido_paterno-distribuidores-input">APELLIDO PATERNO</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="apellido_paterno-distribuidores-addon">AP</span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Ingresa el apellido paterno" id="apellido_paterno-distribuidores-input"
                                                        name="apellido_paterno-distribuidores-input" aria-describedby="apellido_paterno-distribuidores-addon"
                                                        required>
                                                    <div class="invalid-feedback">El campo de apellido paterno es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- campo apellido materno -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label " for="apellido_materno-distribuidores-input">APELLIDO MATERNO</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="apellido_materno-distribuidores-addon">AM</span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Ingresa el apellido materno" id="apellido_materno-distribuidores-input"
                                                        name="apellido_materno-distribuidores-input" aria-describedby="apellido_materno-distribuidores-addon"
                                                        required>
                                                    <div class="invalid-feedback">El campo de apellido materno es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- campo fecha_nacimiento -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="fecha_nacimiento-distribuidores-input">FECHA DE NACIMIENTO</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="fecha_nacimiento-distribuidores-addon">FEC</span>
                                                    <input type="date" class="form-control"
                                                        placeholder="Ingresa la fecha de nacimiento" id="fecha_nacimiento-distribuidores-input"
                                                        name="fecha_nacimiento-distribuidores-input" aria-describedby="fecha_nacimiento-distribuidores-addon"
                                                        required>
                                                    <div class="invalid-feedback">El campo fecha de nacimiento es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- campo curp -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="curp-distribuidores-input">CURP</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="curp-distribuidores-addon">CURP</span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Ingresa la CURP" id="curp-distribuidores-input" name="curp-distribuidores-input"
                                                        aria-describedby="curp-distribuidores-addon" required>
                                                    <div class="invalid-feedback">El campo CURP es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="personaMoralWrapper" class="row d-none">
                                        <!-- campo representante_legal -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="representante_legal-distribuidores-input">REPRESENTANTE LEGAL</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="representante_legal-distribuidores-addon">REP</span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Ingresa el representante legal" id="representante_legal-distribuidores-input"
                                                        name="representante_legal-distribuidores-input" aria-describedby="representante_legal-distribuidores-addon"
                                                        required>
                                                    <div class="invalid-feedback">El campo de representante legal es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- campo domicilio_fiscal -->
                                        <div class="col-lg-6">
                                            <div class="mb-3">
                                                <label class="form-label" for="domicilio_fiscal-distribuidores-input">DOMICILIO FISCAL</label>
                                                <div class="input-group mb-3">
                                                    <span class="input-group-text" id="domicilio_fiscal-distribuidores-addon">DOM</span>
                                                    <input type="text" class="form-control"
                                                        placeholder="Ingresa el domicilio fiscal" id="domicilio_fiscal-distribuidores-input"
                                                        name="domicilio_fiscal-distribuidores-input" aria-describedby="domicilio_fiscal-distribuidores-addon"
                                                        required>
                                                    <div class="invalid-feedback">El campo de domicilio fiscal es obligatorio</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo correo_electronico -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="correo-distribuidores-input">CORREO ELECTRÓNICO</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="correo-distribuidores-addon">COR</span>
                                                <input type="email" class="form-control"
                                                    placeholder="Ingresa el correo electrónico" id="correo-distribuidores-input"
                                                    name="correo-distribuidores-input" aria-describedby="correo-distribuidores-addon">
                                                <div class="invalid-feedback">El campo de correo electrónico es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- select regional_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="regional_id">REGIONALES</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>REG</span>
                                                <select class="form-control" id="regional_id" name="regional_id" required=""></select>
                                                <div class="invalid-feedback">El campo regional es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- select grupo_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="grupo_id">GRUPOS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>GRU</span>
                                                <select class="form-control" id="grupo_id" name="grupo_id" required=""></select>
                                                <div class="invalid-feedback">El campo grupo es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo nombre_comercial -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-distribuidores-input">NOMBRE COMERCIAL</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-distribuidores-addon">NOM</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el nombre comercial" id="nombre-distribuidores-input" name="nombre-distribuidores-input"
                                                    aria-describedby="nombre-distribuidores-addon" required>
                                                <div class="invalid-feedback">El campo de nombre comercial es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo razon_social -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="razon-distribuidores-input">RAZÓN SOCIAL</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="razon-distribuidores-addon">RAZ</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la razón social" id="razon-distribuidores-input" name="razon-distribuidores-input"
                                                    aria-describedby="razon-distribuidores-addon" required>
                                                <div class="invalid-feedback">El campo de razón social es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo rfc -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="rfc-distribuidores-input">RFC</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="rfc-distribuidores-addon">RFC</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el RFC" id="rfc-distribuidores-input" name="rfc-distribuidores-input"
                                                    aria-describedby="rfc-distribuidores-addon" maxlength="13" minlength="12" oninput="this.value = this.value.replace(/[^a-zA-Z0-9]/g,'').toUpperCase(); validarRFC(this);"
                                                    required>
                                                <div class="invalid-feedback">El campo RFC es obligatorio
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo rpve -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="repve-distribuidores-input">No. REPUVE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="repve-distribuidores-addon">REP</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el No. REPUVE" id="repve-distribuidores-input" name="repve-distribuidores-input"
                                                    aria-describedby="repve-distribuidores-addon" required>
                                                <div class="invalid-feedback">El campo REPUVE es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo plaza -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="plaza-distribuidores-input">PLAZA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="plaza-distribuidores-addon">PLA</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la plaza" id="plaza-distribuidores-input" name="plaza-distribuidores-input"
                                                    aria-describedby="plaza-distribuidores-addon" required>
                                                <div class="invalid-feedback">El campo plaza es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo clasificacion -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label " for="clasificacion-distribuidores-input">CLASIFICACIÓN</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="clasificacion-distribuidores-addon">CLA</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la clasificación" id="clasificacion-distribuidores-input"
                                                    name="clasificacion-distribuidores-input" aria-describedby="clasificacion-distribuidores-addon"
                                                    required>
                                                <div class="invalid-feedback">El campo clasificación es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo estatus 'Activo', 'Desarrollo', 'Inactivo' -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estatus-select">ESTATUS</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estatus-addon">EST</span>
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
                                                <span class="input-group-text" id="tipo_negocio-addon">TIP</span>
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
                                            <label class="form-label" for="telefono-distribuidores-input">TELÉFONO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="telefono-distribuidores-addon">TEL</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el teléfono" id="telefono-distribuidores-input" name="telefono-distribuidores-input" maxlength="10" inputmode="numeric"
                                                    pattern="[0-9]{10}"
                                                    aria-describedby="telefono-distribuidores-addon">
                                                <div class="invalid-feedback">
                                                    Si ingresa un teléfono, debe contener exactamente 10 números
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo telefono_alt -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="telefono_alt-input">TELÉFONO ALTERNATIVO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="telefono_alt-addon">TEL</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el teléfono alternativo" id="telefono_alt-input" name="telefono_alt-input" maxlength="10" inputmode="numeric"
                                                    pattern="[0-9]{10}"
                                                    aria-describedby="telefono_alt-addon">
                                            </div>
                                            <div class="invalid-feedback">
                                                Si ingresa un teléfono alternativo, debe contener exactamente 10 números
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Modelos disponibles</label>
                                            <ul id="modelosDisponibles" class="list-group modelos-box"></ul>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">Modelos seleccionados</label>
                                            <ul id="modelosSeleccionados" class="list-group modelos-box"></ul>

                                            <div
                                                id="modelosHint"
                                                class="text-muted text-center py-2 small">
                                                Arrastra modelos aquí
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <div class="input-group mb-3">
                                                <select class="form-control d-none" id="listModelos" name="listModelos[]" multiple required=""></select>
                                                <div class="invalid-feedback">El campo modelos es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <hr class="mb-4">

                                    <h5 class="mb-3">Direcciones</h5>

                                    <div class="col-lg-12">
                                        <div class="form-check mb-3">
                                            <input class="form-check-input" type="checkbox" id="mismaDireccion" name="mismaDireccion"
                                                value="1" checked>
                                            <label class="form-check-label" for="mismaDireccion">
                                                La dirección fiscal es la misma que la dirección principal
                                            </label>
                                        </div>
                                    </div>

                                    <!-- campo tipo tipo 'Fiscal', 'Comercial', 'Taller', 'Sucursal' -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-select">TIPO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="tipo-addon">TIP</span>
                                                <select class="form-select" id="tipo-select" name="tipo-select" aria-describedby="tipo-addon" required>
                                                    <option value="Comercial">Comercial</option>
                                                    <option value="Taller">Taller</option>
                                                    <option value="Sucursal">Sucursal</option>
                                                </select>
                                                <div class="invalid-feedback">El campo tipo es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo calle -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="calle-distribuidores-input">CALLE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="calle-distribuidores-addon">CAL</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la calle" id="calle-distribuidores-input" name="calle-distribuidores-input"
                                                    aria-describedby="calle-distribuidores-addon" required>
                                                <div class="invalid-feedback">El campo calle es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo numero_ext -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="numero_ext-distribuidores-input">NÚMERO EXTERIOR</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="numero_ext-distribuidores-addon">NUM</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el número exterior" id="numero_ext-distribuidores-input"
                                                    name="numero_ext-distribuidores-input" maxlength="5" aria-describedby="numero_ext-distribuidores-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo numero_int -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="numero_int-distribuidores-input">NÚMERO INTERIOR</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="numero_int-distribuidores-addon">NUM</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el número interior" id="numero_int-distribuidores-input"
                                                    name="numero_int-distribuidores-input" maxlength="5" aria-describedby="numero_int-distribuidores-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo colonia -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="colonia-distribuidores-input">COLONIA</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="colonia-distribuidores-addon">COL</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa la colonia" id="colonia-distribuidores-input" name="colonia-distribuidores-input"
                                                    aria-describedby="colonia-distribuidores-addon" required>
                                                <div class="invalid-feedback">El campo colonia es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- campo codigo_postal -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="codigo_postal-distribuidores-input">CÓDIGO POSTAL</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="codigo_postal-distribuidores-addon">CP</span>
                                                <input type="text" class="form-control"
                                                    placeholder="Ingresa el código postal" id="codigo_postal-distribuidores-input"
                                                    name="codigo_postal-distribuidores-input" maxlength="5" minlength="5" aria-describedby="codigo_postal-distribuidores-addon">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- select pais_id -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="listPaises">PAÍS</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>PAI</span>
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
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>EST</span>
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
                                                <span class="input-group-text" id="nombre-linea-addon" data-choices>MUN</span>
                                                <select class="form-control" id="listMunicipios" name="listMunicipios" required=""></select>
                                                <div class="invalid-feedback">El campo municipio es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- input region -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="region_nombre">REGIÓN</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text">REG</span>
                                                <input
                                                    type="text"
                                                    class="form-control"
                                                    id="region_nombre"
                                                    name="region_nombre"
                                                    readonly />
                                            </div>
                                        </div>
                                    </div>

                                    <div id="direccionFiscalWrapper" class="d-none">
                                        <hr class="mb-4">

                                        <h5 class="mb-3">Dirección fiscal</h5>

                                        <div class="row">


                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="tipo_fiscal">TIPO</label>
                                                    <div class="input-group has-validation mb-3">
                                                        <span class="input-group-text" id="tipo_fiscal-addon">TIP</span>
                                                        <select class="form-select" id="tipo_fiscal" name="tipo_fiscal" aria-describedby="tipo_fiscal-addon" required>
                                                            <option value="Fiscal">Fiscal</option>
                                                        </select>
                                                        <div class="invalid-feedback">El campo tipo es obligatorio</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- campo calle -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="calle-distribuidores-input">CALLE</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="calle-distribuidores-addon">CAL</span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Ingresa la calle" id="calle_fiscal" name="calle_fiscal"
                                                            aria-describedby="calle-distribuidores-addon" required>
                                                        <div class="invalid-feedback">El campo calle es obligatorio</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- campo numero_ext -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="numero_ext-distribuidores-input">NÚMERO EXTERIOR</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="numero_ext-distribuidores-addon">NUM</span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Ingresa el número exterior" id="numero_ext_fiscal"
                                                            name="numero_ext_fiscal" maxlength="5" aria-describedby="numero_ext-distribuidores-addon">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- campo numero_int -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="numero_int-distribuidores-input">NÚMERO INTERIOR</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="numero_int-distribuidores-addon">NUM</span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Ingresa el número interior" id="numero_int_fiscal"
                                                            name="numero_int_fiscal" maxlength="5" aria-describedby="numero_int-distribuidores-addon">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- campo colonia -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="colonia-distribuidores-input">COLONIA</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="colonia-distribuidores-addon">COL</span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Ingresa la colonia" id="colonia_fiscal" name="colonia_fiscal"
                                                            aria-describedby="colonia-distribuidores-addon" required>
                                                        <div class="invalid-feedback">El campo colonia es obligatorio</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- campo codigo_postal -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="codigo_postal-distribuidores-input">CÓDIGO POSTAL</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="codigo_postal-distribuidores-addon">CP</span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Ingresa el código postal" id="codigo_postal_fiscal"
                                                            name="codigo_postal_fiscal" maxlength="5" minlength="5" aria-describedby="codigo_postal-distribuidores-addon">
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- select pais_id -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="listPaisesFiscal">PAÍS</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="nombre-linea-addon" data-choices>PAI</span>
                                                        <select class="form-control" id="listPaisesFiscal" name="listPaisesFiscal" required=""></select>
                                                        <div class="invalid-feedback">El campo país es obligatorio</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- select estado_id -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="listEstadosFiscal">ESTADOS</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="nombre-linea-addon" data-choices>EST</span>
                                                        <select class="form-control" id="listEstadosFiscal" name="listEstadosFiscal" required=""></select>
                                                        <div class="invalid-feedback">El campo estado es obligatorio</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- select municipio_id -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="listMunicipiosFiscal">MUNICIPIOS</label>
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="nombre-linea-addon" data-choices>MUN</span>
                                                        <select class="form-control" id="listMunicipiosFiscal" name="listMunicipiosFiscal" required=""></select>
                                                        <div class="invalid-feedback">El campo municipio es obligatorio</div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- campo estado -->
                                            <div class="col-lg-6">
                                                <div class="mb-3">
                                                    <label class="form-label" for="estado-select">ESTADO</label>
                                                    <div class="input-group has-validation mb-3">
                                                        <span class="input-group-text" id="estado-addon">EST</span>
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

<div class="modal fade" id="modalViewDistribuidor" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0">

            <div class="modal-header bg-primary-subtle pb-3">
                <h5 class="modal-title" id="titleModal">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" id="close-modal"></button>
            </div>

            <!-- NAV TABS -->
            <ul class="nav nav-tabs px-3" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general">
                        General
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-direccion">
                        Dirección
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fiscal">
                        Dirección fiscal
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contactos">
                        Contactos
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-modelos">
                        Modelos
                    </button>
                </li>
            </ul>

            <div class="modal-body">
                <div class="tab-content">
                    <!-- TAB GENERAL -->
                    <div class="tab-pane fade show active" id="tab-general">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>ID</td>
                                    <td id="iddistri"></td>
                                </tr>
                                <tr>
                                    <td>Tipo de persona</td>
                                    <td id="tipopersona"></td>
                                </tr>
                                <tr class="persona-fisica">
                                    <td>Nombre</td>
                                    <td id="nombre_fisica"></td>
                                </tr>
                                <tr class="persona-fisica">
                                    <td>Apellido paterno</td>
                                    <td id="apellido_paterno"></td>
                                </tr>
                                <tr class="persona-fisica">
                                    <td>Apellido materno</td>
                                    <td id="apellido_materno"></td>
                                </tr>
                                <tr class="persona-fisica">
                                    <td>Fecha de nacimiento</td>
                                    <td id="fecha_nacimiento"></td>
                                </tr>
                                <tr class="persona-fisica">
                                    <td>CURP</td>
                                    <td id="curp"></td>
                                </tr>
                                <tr class="persona-moral">
                                    <td>Representante legal</td>
                                    <td id="representante_legal"></td>
                                </tr>
                                <tr class="persona-moral">
                                    <td>Domicilio fiscal</td>
                                    <td id="domicilio_fiscal"></td>
                                </tr>
                                <tr>
                                    <td>Correo</td>
                                    <td id="correo"></td>
                                </tr>
                                <tr>
                                    <td>Regional:</td>
                                    <td id="nombreRegional"></td>
                                </tr>
                                <tr>
                                    <td>Grupo</td>
                                    <td id="nombregrupo"></td>
                                </tr>
                                <tr>
                                    <td>Nombre comercial</td>
                                    <td id="nombrecomercial"></td>
                                </tr>
                                <tr>
                                    <td>Razón social</td>
                                    <td id="razonsocial"></td>
                                </tr>
                                <tr>
                                    <td>RFC</td>
                                    <td id="rfc"></td>
                                </tr>
                                <tr>
                                    <td>REPUVE</td>
                                    <td id="repve"></td>
                                </tr>
                                <tr>
                                    <td>Plaza</td>
                                    <td id="plaza"></td>
                                </tr>
                                <tr>
                                    <td>Clasificación</td>
                                    <td id="clasificacion"></td>
                                </tr>
                                <tr>
                                    <td>Tipo de negocio</td>
                                    <td id="tiponegocio"></td>
                                </tr>
                                <tr>
                                    <td>Teléfono</td>
                                    <td id="telefono"></td>
                                </tr>
                                <tr>
                                    <td>Teléfono alternativo</td>
                                    <td id="telefonoalt"></td>
                                </tr>
                                <tr>
                                    <td>Fecha registro</td>
                                    <td id="fecharegistro"></td>
                                </tr>
                                <tr>
                                    <td>Estatus</td>
                                    <td id="celEstado"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TAB DIRECCIÓN -->
                    <div class="tab-pane fade" id="tab-direccion">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Tipo</td>
                                    <td id="tipo"></td>
                                </tr>
                                <tr>
                                    <td>Calle</td>
                                    <td id="calle"></td>
                                </tr>
                                <tr>
                                    <td>Núm. Ext</td>
                                    <td id="numero_ext"></td>
                                </tr>
                                <tr>
                                    <td>Núm. Int</td>
                                    <td id="numero_int"></td>
                                </tr>
                                <tr>
                                    <td>Colonia</td>
                                    <td id="colonia"></td>
                                </tr>
                                <tr>
                                    <td>C.P.</td>
                                    <td id="codigo_postal"></td>
                                </tr>
                                <tr>
                                    <td>País</td>
                                    <td id="pais"></td>
                                </tr>
                                <tr>
                                    <td>Estado</td>
                                    <td id="estado"></td>
                                </tr>
                                <tr>
                                    <td>Municipio</td>
                                    <td id="municipio"></td>
                                </tr>
                                <tr>
                                    <td>Región</td>
                                    <td id="region"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TAB DIRECCIÓN FISCAL -->
                    <div class="tab-pane fade" id="tab-fiscal">
                        <table class="table table-bordered">
                            <tbody>
                                <tr>
                                    <td>Tipo</td>
                                    <td id="tipofiscal"></td>
                                </tr>
                                <tr>
                                    <td>Calle</td>
                                    <td id="callefiscal"></td>
                                </tr>
                                <tr>
                                    <td>Núm. Ext</td>
                                    <td id="numeroext_fiscal"></td>
                                </tr>
                                <tr>
                                    <td>Núm. Int</td>
                                    <td id="numeroint_fiscal"></td>
                                </tr>
                                <tr>
                                    <td>Colonia</td>
                                    <td id="coloniafiscal"></td>
                                </tr>
                                <tr>
                                    <td>C.P.</td>
                                    <td id="codigopostal_fiscal"></td>
                                </tr>
                                <tr>
                                    <td>País</td>
                                    <td id="pais_fiscal"></td>
                                </tr>
                                <tr>
                                    <td>Estado</td>
                                    <td id="estado_fiscal"></td>
                                </tr>
                                <tr>
                                    <td>Municipio</td>
                                    <td id="municipio_fiscal"></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- TAB CONTACTOS -->
                    <div class="tab-pane fade" id="tab-contactos">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered dt-responsive nowrap table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>PUESTO</th>
                                        <th>NOMBRE CONTACTO</th>
                                        <th>CORREO</th>
                                        <th>TELÉFONO</th>
                                        <th>ESTATUS</th>
                                        <th>FECHA DE REGISTRO</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyContactos">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- TAB MODELOS -->
                    <div class="tab-pane fade" id="tab-modelos">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table table-bordered dt-responsive nowrap table-striped align-middle">
                                <thead>
                                    <tr>
                                        <th>ID LINEA DE PRODUCTO</th>
                                        <th>CVE LINEA DE PRODUCTO</th>
                                        <th>DESCRIPCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyModelos">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<style>
    .modelos-box {
        min-height: 260px;
        max-height: 260px;
        overflow-y: auto;
        border: 1px dashed #ced4da;
        border-radius: 8px;
        padding: 10px;
        background: #f8f9fa;
    }

    .modelos-box li {
        cursor: grab;
        margin-bottom: 6px;
    }

    .modelos-box li:active {
        cursor: grabbing;
    }

    .modelos-box {
        scrollbar-width: thin;
        /* Firefox */
    }

    .modelos-box::-webkit-scrollbar {
        width: 6px;
    }

    .modelos-box::-webkit-scrollbar-thumb {
        background: #adb5bd;
        border-radius: 4px;
    }
</style>

<script>
    function validarTelefono(input) {
        input.value = input.value.replace(/\D/g, '');

        if (input.value.length > 10) {
            input.value = input.value.slice(0, 10);
        }
    }

    document.querySelectorAll('#telefono-distribuidores-input, #telefono_alt-input')
        .forEach(input => {
            input.addEventListener('input', () => validarTelefono(input));
        });
</script>


<!-- end main content-->
<?php footerAdmin($data); ?>