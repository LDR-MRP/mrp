<?php headerAdmin($data); ?>
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
                                <li class="breadcrumb-item"><a href="javascript: void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" role="tablist" id="nav-tab">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listColores" role="tab">COLORES</a>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarColor" role="tab">NUEVO</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listColores" role="tabpanel">
                            <table id="tableColores" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>NOMBRE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>FECHA CREACIÓN</th>
                                        <th>ESTATUS</th>
                                        <th>ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <div class="tab-pane" id="agregarColor" role="tabpanel">
                            <form id="formColor" autocomplete="off">
                                <input type="hidden" id="idcolor" name="idcolor" value="0">
                                <div class="row g-3">
                                    <div class="col-lg-6">
                                        <label class="form-label" for="color_nombre">NOMBRE DEL COLOR</label>
                                        <input type="text" class="form-control" id="color_nombre" name="nombre" maxlength="100" placeholder="Ej. Blanco Ártico" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label" for="color_estado">ESTATUS</label>
                                        <select class="form-select" id="color_estado" name="estado">
                                            <option value="2" selected>Activo</option>
                                            <option value="1">Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="color_descripcion">DESCRIPCIÓN</label>
                                        <textarea class="form-control" id="color_descripcion" name="descripcion" rows="2" maxlength="255" placeholder="Opcional"></textarea>
                                    </div>
                                </div>
                                <div class="d-flex mt-4">
                                    <button type="submit" class="btn btn-success ms-auto" id="btnGuardarColor">
                                        <i class="ri-save-line align-middle me-1"></i><span id="btnTextColor">REGISTRAR</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6"><script>document.write(new Date().getFullYear())</script> © LDR.</div>
                <div class="col-sm-6"><div class="text-sm-end d-none d-sm-block">LDR Solutions · MRP</div></div>
            </div>
        </div>
    </footer>
</div>
<?php footerAdmin($data); ?>
