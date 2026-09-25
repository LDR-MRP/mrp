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
                    <h5 class="card-title mb-0">Bitácora de Ingeniería</h5>
                    <p class="text-muted mb-0">Registro de quién dio de alta o editó cada modelo, configuración, motor, transmisión, certificación y especificación, y qué campos cambiaron.</p>
                </div>
                <div class="card-body">

                    <form id="formFiltrosBitacora" class="row g-2 align-items-end mb-3">
                        <div class="col-lg-3 col-sm-6">
                            <label class="form-label" for="filtro-tabla-select">MÓDULO</label>
                            <select class="form-select" id="filtro-tabla-select">
                                <option value="">--Todos--</option>
                                <?php foreach ($data['tablas'] as $tabla => $label) { ?>
                                    <option value="<?= $tabla ?>"><?= $label ?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-lg-3 col-sm-6">
                            <label class="form-label" for="filtro-usuario-select">USUARIO</label>
                            <select class="form-select" id="filtro-usuario-select">
                                <option value="">--Todos--</option>
                            </select>
                        </div>
                        <div class="col-lg-2 col-sm-6">
                            <label class="form-label" for="filtro-fecha-desde-input">DESDE</label>
                            <input type="date" class="form-control" id="filtro-fecha-desde-input">
                        </div>
                        <div class="col-lg-2 col-sm-6">
                            <label class="form-label" for="filtro-fecha-hasta-input">HASTA</label>
                            <input type="date" class="form-control" id="filtro-fecha-hasta-input">
                        </div>
                        <div class="col-lg-2 col-sm-12">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ri-filter-3-line align-bottom me-1"></i> Filtrar
                            </button>
                        </div>
                    </form>

                    <div class="table-responsive">
                        <table id="tableBitacora" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                            <thead>
                                <tr>
                                    <th>FECHA</th>
                                    <th>USUARIO</th>
                                    <th>MÓDULO</th>
                                    <th>ACCIÓN</th>
                                    <th>REGISTRO</th>
                                    <th>DETALLE</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
