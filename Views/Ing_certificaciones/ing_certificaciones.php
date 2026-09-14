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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listCertificaciones" role="tab">LISTADO</a>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarCertificacion" role="tab">NUEVO</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listCertificaciones" role="tabpanel">
                            <table id="tableCertificaciones" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CÓDIGO</th>
                                        <th>NOMBRE</th>
                                        <th>AUTORIDAD</th>
                                        <th>DOCUMENTO</th>
                                        <th>VIGENCIA</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="agregarCertificacion" role="tabpanel">
                            <form id="formCertificaciones" autocomplete="off" class="was-validated">
                                <input type="hidden" id="id_certificacion" name="id_certificacion" value="0">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="codigo-input">CÓDIGO</label>
                                            <input type="text" class="form-control" id="codigo-input" name="codigo-input" placeholder="Ej. NOM-042" required>
                                            <div class="invalid-feedback">El código es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-input">NOMBRE</label>
                                            <input type="text" class="form-control" id="nombre-input" name="nombre-input" required>
                                            <div class="invalid-feedback">El nombre es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="autoridad-input">AUTORIDAD</label>
                                            <input type="text" class="form-control" id="autoridad-input" name="autoridad-input" placeholder="Ej. SEMARNAT">
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
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-input">TIPO</label>
                                            <input type="text" class="form-control" id="tipo-input" name="tipo-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6">
                                        <div class="mb-3 mt-4 pt-2 form-check">
                                            <input class="form-check-input" type="checkbox" id="requiere-documento-check" name="requiere-documento-check" checked>
                                            <label class="form-check-label" for="requiere-documento-check">Requiere documento adjunto</label>
                                        </div>
                                    </div>
                                    <div class="col-lg-5 col-sm-6">
                                        <div class="mb-3 mt-4 pt-2 form-check">
                                            <input class="form-check-input" type="checkbox" id="requiere-vigencia-check" name="requiere-vigencia-check" checked>
                                            <label class="form-check-label" for="requiere-vigencia-check">Requiere control de vigencia</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
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
