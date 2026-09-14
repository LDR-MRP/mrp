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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listTransmisiones" role="tab">LISTADO</a>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarTransmision" role="tab">NUEVO</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listTransmisiones" role="tabpanel">
                            <table id="tableTransmisiones" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>FABRICANTE</th>
                                        <th>MODELO</th>
                                        <th>TIPO</th>
                                        <th>VELOCIDADES</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="agregarTransmision" role="tabpanel">
                            <form id="formTransmisiones" autocomplete="off" class="was-validated">
                                <input type="hidden" id="id_transmision" name="id_transmision" value="0">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="fabricante-input">FABRICANTE</label>
                                            <input type="text" class="form-control" id="fabricante-input" name="fabricante-input" placeholder="Ej. Harbin Dongan" required>
                                            <div class="invalid-feedback">El fabricante es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="modelo-input">MODELO</label>
                                            <input type="text" class="form-control" id="modelo-input" name="modelo-input" placeholder="Ej. LD-516MR" required>
                                            <div class="invalid-feedback">El modelo es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-select">TIPO</label>
                                            <select class="form-select" id="tipo-select" name="tipo-select">
                                                <option value="TMA" selected>T.M.A</option>
                                                <option value="AMT">A.M.T</option>
                                                <option value="AT">A.T</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="velocidades-input">VELOCIDADES</label>
                                            <input type="text" class="form-control" id="velocidades-input" name="velocidades-input" placeholder="Ej. 5 + reversa">
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="activo-select">ESTATUS</label>
                                            <select class="form-select" id="activo-select" name="activo-select">
                                                <option value="1" selected>Activo</option>
                                                <option value="0">Inactivo</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="mb-3">
                                            <label class="form-label" for="descripcion-textarea">DESCRIPCIÓN</label>
                                            <textarea class="form-control" id="descripcion-textarea" name="descripcion-textarea" rows="2"></textarea>
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
