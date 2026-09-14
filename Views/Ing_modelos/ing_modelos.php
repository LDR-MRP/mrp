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

            <div class="alert alert-info">
                Los <strong>Segmentos</strong> y <strong>Modelos</strong> se dan de alta en
                <a href="<?= base_url(); ?>/Inv_lineasdproducto">Inventario &rarr; Líneas de producto</a>.
                Aquí solo se captura el detalle técnico adicional (marca, tipo de carrocería, vigencia) que
                Ingeniería necesita sobre cada modelo ya existente.
            </div>

            <div class="card">
                <div class="card-body">
                    <table id="tableModelos" class="table table-bordered dt-responsive nowrap table-striped align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>SEGMENTO</th>
                                <th>MODELO</th>
                                <th>MARCA</th>
                                <th>CARROCERÍA</th>
                                <th>ESTADO</th>
                                <th>VERSIÓN</th>
                                <th>DETALLE</th>
                                <th>ACCIÓN</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Modal: detalle del modelo -->
<div class="modal fade" id="modalDetalleModelo" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de ingeniería del modelo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDetalleModelo" autocomplete="off">
                <div class="modal-body">
                    <input type="hidden" id="id_sublineaproducto" name="id_sublineaproducto">
                    <div class="mb-3">
                        <label class="form-label">Modelo</label>
                        <input type="text" class="form-control" id="modelo-readonly" readonly>
                    </div>
                    <div class="row">
                        <div class="col-lg-4 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="marca-input">MARCA</label>
                                <input type="text" class="form-control" id="marca-input" name="marca-input" value="FOTON">
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="carroceria-input">TIPO DE CARROCERÍA</label>
                                <input type="text" class="form-control" id="carroceria-input" name="carroceria-input">
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="estado-select">ESTADO DEL MODELO</label>
                                <select class="form-select" id="estado-select" name="estado-select">
                                    <option value="ACTIVO" selected>Activo</option>
                                    <option value="AUTORIZADO">Autorizado</option>
                                    <option value="EN_CORRECCION">En corrección</option>
                                    <option value="OBSOLETO">Obsoleto</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="version-input">VERSIÓN</label>
                                <input type="text" class="form-control" id="version-input" name="version-input">
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="fecha-inicio-input">VIGENTE DESDE</label>
                                <input type="date" class="form-control" id="fecha-inicio-input" name="fecha-inicio-input">
                            </div>
                        </div>
                        <div class="col-lg-4 col-sm-6">
                            <div class="mb-3">
                                <label class="form-label" for="fecha-fin-input">VIGENTE HASTA</label>
                                <input type="date" class="form-control" id="fecha-fin-input" name="fecha-fin-input">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
