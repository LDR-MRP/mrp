<?php headerAdmin($data);
?>
<div id="contentAjax"></div>
<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Ingeniería</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" id="nav-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listMotores" role="tab">LISTADO</a>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarMotor" role="tab">NUEVO</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listMotores" role="tabpanel">
                            <table id="tableMotores" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>FABRICANTE</th>
                                        <th>MODELO</th>
                                        <th>TIPO</th>
                                        <th>POTENCIA</th>
                                        <th>COMBUSTIBLE</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="agregarMotor" role="tabpanel">
                            <form id="formMotores" autocomplete="off" class="was-validated">
                                <input type="hidden" id="id_motor" name="id_motor" value="0">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="fabricante-input">FABRICANTE</label>
                                            <input type="text" class="form-control" id="fabricante-input" name="fabricante-input" placeholder="Ej. FOTON Harbin Dongan" required>
                                            <div class="invalid-feedback">El fabricante es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="modelo-motor-input">MODELO DE MOTOR</label>
                                            <input type="text" class="form-control" id="modelo-motor-input" name="modelo-motor-input" placeholder="Ej. DAM16NS" required>
                                            <div class="invalid-feedback">El modelo de motor es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-motor-select">TIPO DE MOTOR</label>
                                            <select class="form-select" id="tipo-motor-select" name="tipo-motor-select">
                                                <option value="COMBUSTION" selected>Combustión</option>
                                                <option value="ELECTRICO">Eléctrico</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="activo-select">ESTATUS</label>
                                            <select class="form-select" id="activo-select" name="activo-select">
                                                <option value="1" selected>Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="fieldsetCombustion" class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="cilindrada-input">CILINDRADA (L)</label>
                                            <input type="number" step="0.01" class="form-control" id="cilindrada-input" name="cilindrada-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="cilindros-input">NÚMERO DE CILINDROS</label>
                                            <input type="number" class="form-control" id="cilindros-input" name="cilindros-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-combustible-input">TIPO DE COMBUSTIBLE</label>
                                            <input type="text" class="form-control" id="tipo-combustible-input" name="tipo-combustible-input" placeholder="Ej. Gasolina">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-admision-input">TIPO DE ADMISIÓN</label>
                                            <input type="text" class="form-control" id="tipo-admision-input" name="tipo-admision-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="potencia-input">POTENCIA</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" id="potencia-input" name="potencia-input">
                                                <select class="form-select" id="unidad-potencia-select" name="unidad-potencia-select" style="max-width:90px">
                                                    <option value="HP" selected>hp</option>
                                                    <option value="KW">kW</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="torque-input">TORQUE</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control" id="torque-input" name="torque-input">
                                                <select class="form-select" id="unidad-torque-select" name="unidad-torque-select" style="max-width:110px">
                                                    <option value="LB-PIE" selected>lb-pie</option>
                                                    <option value="NM">N.m</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="fieldsetElectrico" class="row" style="display:none">
                                    <div class="col-12"><h6 class="text-muted mt-2">DATOS DE BATERÍA (solo motores eléctricos)</h6></div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="fabricante-bateria-input">FABRICANTE DE BATERÍA</label>
                                            <input type="text" class="form-control" id="fabricante-bateria-input" name="fabricante-bateria-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-bateria-input">TIPO DE BATERÍA</label>
                                            <input type="text" class="form-control" id="tipo-bateria-input" name="tipo-bateria-input" placeholder="Ej. LiFePO4">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="capacidad-bateria-input">CAPACIDAD</label>
                                            <input type="text" class="form-control" id="capacidad-bateria-input" name="capacidad-bateria-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="consumo-input">CONSUMO</label>
                                            <input type="text" class="form-control" id="consumo-input" name="consumo-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="conector-input">CONECTOR</label>
                                            <input type="text" class="form-control" id="conector-input" name="conector-input" placeholder="Ej. GB/T">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="proteccion-ip-input">PROTECCIÓN IP</label>
                                            <input type="text" class="form-control" id="proteccion-ip-input" name="proteccion-ip-input" placeholder="Ej. IP67">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="sistema-electrico-input">SISTEMA ELÉCTRICO</label>
                                            <input type="text" class="form-control" id="sistema-electrico-input" name="sistema-electrico-input" placeholder="Ej. 12V">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="submit" id="btnActionForm" class="btn btn-success btn-label right ms-auto">
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i><span id="btnText">REGISTRAR</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
