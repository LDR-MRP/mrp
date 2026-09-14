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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listEspecificaciones" role="tab">LISTADO</a>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarEspecificacion" role="tab">NUEVO</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listEspecificaciones" role="tabpanel">
                            <table id="tableEspecificaciones" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CATEGORÍA</th>
                                        <th>CLAVE</th>
                                        <th>UNIDAD</th>
                                        <th>DESGLOSE FACTURACIÓN</th>
                                        <th>ORDEN</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIÓN</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="agregarEspecificacion" role="tabpanel">
                            <form id="formEspecificaciones" autocomplete="off" class="was-validated">
                                <input type="hidden" id="id_especificacion" name="id_especificacion" value="0">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="categoria-select">CATEGORÍA</label>
                                            <div class="input-group">
                                                <select class="form-select" id="categoria-select" name="categoria-select" required>
                                                    <option value="">--Seleccione--</option>
                                                </select>
                                                <button class="btn btn-outline-secondary" type="button" id="btnNuevaCategoria" title="Agregar nueva categoría">
                                                    <i class="ri-add-line"></i>
                                                </button>
                                                <button class="btn btn-outline-secondary" type="button" id="btnGestionarCategorias" title="Gestionar categorías" data-bs-toggle="modal" data-bs-target="#modalCategorias">
                                                    <i class="ri-settings-4-line"></i>
                                                </button>
                                            </div>
                                            <div class="invalid-feedback">La categoría es obligatoria</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="clave-input">CLAVE</label>
                                            <input type="text" class="form-control" id="clave-input" name="clave-input" placeholder="Ej. Largo total" required>
                                            <div class="invalid-feedback">La clave es obligatoria</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-2 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="unidad-input">UNIDAD</label>
                                            <input type="text" class="form-control" id="unidad-input" name="unidad-input" placeholder="Ej. mm">
                                        </div>
                                    </div>
                                    <div class="col-lg-1 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="orden-input">ORDEN</label>
                                            <input type="number" class="form-control" id="orden-input" name="orden-input">
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
                                    <div class="col-lg-1 col-sm-6">
                                        <div class="mb-3 mt-1 form-check">
                                            <input class="form-check-input" type="checkbox" id="desglose-facturacion-check" name="desglose-facturacion-check">
                                            <label class="form-check-label" for="desglose-facturacion-check">¿Desglose facturación?</label>
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

            <!-- MODAL: Gestionar categorías de especificación -->
            <div class="modal fade" id="modalCategorias" tabindex="-1" aria-labelledby="modalCategoriasLabel" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="modalCategoriasLabel"><i class="ri-list-settings-line align-bottom me-1"></i>Gestionar categorías</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p class="text-muted fs-13 mb-3">Edita el nombre o el orden, y activa/desactiva una categoría para que deje (o no) de estar disponible al capturar especificaciones nuevas. Las especificaciones ya guardadas no se ven afectadas.</p>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1">Nombre</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1" style="width:90px">Orden</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 text-center" style="width:120px">Especificaciones</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 text-center" style="width:90px">Activa</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 text-center" style="width:70px">Guardar</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyCategorias"></tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
    </div>
</div>

<?php footerAdmin($data); ?>
