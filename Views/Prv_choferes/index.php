<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title']; ?></h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Logística</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag']; ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" id="nav-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listChoferes" role="tab" id="tabList">
                                CHOFERES
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#agregarChofer" role="tab" id="tabForm" onclick="fntNewChofer();">
                                NUEVO CHOFER
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content">
                        <!-- TABLISTA -->
                        <div class="tab-pane active" id="listChoferes" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered dt-responsive nowrap table-striped align-middle" id="tableChoferes" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Trasladista</th>
                                            <th>Nombre Completo</th>
                                            <th>No. Licencia</th>
                                            <th>Tipo Licencia</th>
                                            <th>Vigencia</th>
                                            <th>Teléfono</th>
                                            <th>Estatus</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB FORMULARIO -->
                        <div class="tab-pane" id="agregarChofer" role="tabpanel">
                            <form id="formChofer" name="formChofer" autocomplete="off">
                                <input type="hidden" id="id_chofer" name="id_chofer" value="">
                                
                                <div class="row">
                                    <div class="col-lg-6 col-md-12 mb-3">
                                        <label for="id_proveedor" class="form-label">Empresa Trasladista <span class="text-danger">*</span></label>
                                        <select class="form-select" id="id_proveedor" name="id_proveedor" required>
                                            <option value="">-- Seleccionar Trasladista --</option>
                                            <?php foreach ($data['trasladistas'] as $t) { ?>
                                                <option value="<?= $t['id_proveedor']; ?>"><?= $t['razon_social']; ?> (<?= $t['rfc']; ?>)</option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="nombre" class="form-label">Nombre(s) <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="ej. Juan Carlos">
                                    </div>

                                    <div class="col-lg-3 col-md-6 mb-3">
                                        <label for="apellidos" class="form-label">Apellidos <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="apellidos" name="apellidos" required placeholder="ej. Pérez Gómez">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label for="num_licencia" class="form-label">Número de Licencia <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control text-uppercase" id="num_licencia" name="num_licencia" required placeholder="ej. LIC-884920">
                                    </div>

                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label for="tipo_licencia" class="form-label">Tipo de Licencia</label>
                                        <select class="form-select" id="tipo_licencia" name="tipo_licencia">
                                            <option value="A">Tipo A (Particular)</option>
                                            <option value="B">Tipo B (Carga)</option>
                                            <option value="C">Tipo C (Pesado / Articulado)</option>
                                            <option value="E">Tipo E (Federal Carga)</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label for="vigencia_licencia" class="form-label">Vigencia Licencia</label>
                                        <input type="date" class="form-control" id="vigencia_licencia" name="vigencia_licencia">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-md-12 mb-3">
                                        <label for="telefono" class="form-label">Teléfono de Contacto</label>
                                        <input type="text" class="form-control" id="telefono" name="telefono" placeholder="ej. 55 1234 5678">
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="button" class="btn btn-light me-2" onclick="cancelForm();">Cancelar</button>
                                    <button type="submit" class="btn btn-primary" id="btnActionForm"><span id="btnText">Guardar</span></button>
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
