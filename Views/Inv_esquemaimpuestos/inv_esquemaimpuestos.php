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
                            <a class="nav-link active" data-bs-toggle="tab" href="#listimpuestos" role="tab">
                                IMPUESTOS
                            </a>
                        </li>
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#agregarImpuesto" role="tab">
                                    NUEVO
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">
                        <div class="tab-pane active" id="listimpuestos" role="tabpanel">

                            <table id="tableImpuestos"
                                class="table table-bordered dt-responsive nowrap table-striped align-middle"
                                style="width:100%">
                                <thead>
                                    <tr>
                                        <th>CLAVE</th>
                                        <th>DESCRIPCIÓN</th>
                                        <th>impuesto 1</th>
                                        <th>impuesto 2</th>
                                        <th>impuesto 3</th>
                                        <th>impuesto 4</th>
                                        <th>impuesto 5</th>
                                        <th>impuesto 6</th>
                                        <th>impuesto 7</th>
                                        <th>impuesto 8</th>
                                        <th>FECHA CREACIÓN</th>
                                        <th>ESTATUS</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>

                        </div>
                        <!-- end tab-pane -->




                        <div class="tab-pane" id="agregarImpuesto" role="tabpanel">
                            <?php
                            /* =======================
                                Catálogos aplica por impuesto
                                ======================= */
                            $aplicaCatalogos = [

                                1 => [
                                    0 => 'Exento',
                                    1 => 'Precio base'
                                ],

                                2 => [
                                    0 => 'Exento',
                                    1 => 'Precio base',
                                    2 => 'Acumulado 1'
                                ],

                                3 => [
                                    0 => 'Exento',
                                    1 => 'Precio base',
                                    2 => 'Acumulado 1',
                                    3 => 'Acumulado 2'
                                ],

                                4 => [
                                    0 => 'Exento',
                                    1 => 'Precio base',
                                    3 => 'Acumulado 2',
                                    4 => 'Acumulado 3'
                                ],

                                5 => [
                                    0 => 'Precio base',
                                    1 => 'Acumulado 1',
                                    2 => 'Acumulado 2',
                                    3 => 'Acumulado 3',
                                    4 => 'Exento',
                                    6 => 'No aplica',
                                    7 => 'Acumulado 4'
                                ],

                                6 => [
                                    0 => 'Precio base',
                                    1 => 'Acumulado 1',
                                    2 => 'Acumulado 2',
                                    3 => 'Acumulado 3',
                                    4 => 'Exento',
                                    6 => 'No aplica',
                                    7 => 'Acumulado 4',
                                    8 => 'Acumulado 5'
                                ],

                                7 => [
                                    0 => 'Precio base',
                                    1 => 'Acumulado 1',
                                    2 => 'Acumulado 2',
                                    3 => 'Acumulado 3',
                                    4 => 'Exento',
                                    6 => 'No aplica',
                                    7 => 'Acumulado 4',
                                    8 => 'Acumulado 5',
                                    9 => 'Acumulado 6'
                                ],

                                8 => [
                                    0  => 'Precio base',
                                    1  => 'Acumulado 1',
                                    2  => 'Acumulado 2',
                                    3  => 'Acumulado 3',
                                    4  => 'Exento',
                                    6  => 'No aplica',
                                    7  => 'Acumulado 4',
                                    8  => 'Acumulado 5',
                                    9  => 'Acumulado 6',
                                    10 => 'Acumulado 7'
                                ]
                            ];
                            ?>

                            <form id="formImpuestos" autocomplete="off" class="form-steps was-validated" autocomplete="off">
                                <input type="hidden" id="idimpuesto" name="idimpuesto">
                                <div class="row">

                                    <!-- CLAVE -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="clave-impuesto-input">CLAVE</label>
                                            <div class="input-group mb-3">
                                                <span class="input-group-text" id="clave-impuesto-addon">Clave esquema</span>
                                                <input type="number" class="form-control"
                                                    placeholder="Ingresa la clave" id="clave-impuesto-input" name="clave-impuesto-input"
                                                    aria-describedby="clave-impuesto-addon" required>
                                                <div class="invalid-feedback">El campo clave es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ESTADO -->
                                    <div class="col-lg-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estado-select">ESTADO</label>
                                            <div class="input-group has-validation mb-3">
                                                <span class="input-group-text" id="estado-addon">Est</span>
                                                <select class="form-select" id="estado-select" name="estado-select"
                                                    aria-describedby="estado-addon" required>
                                                    <option value="2" selected>Activo</option>
                                                    <option value="1">Inactivo</option>
                                                </select>
                                                <div class="invalid-feedback">El campo estado es obligatorio</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- end row -->

                                <!-- DESCRIPCIÓN -->
                                <div class="mb-3">
                                    <label class="form-label" for="descripcion-impuesto-textarea">DESCRIPCIÓN</label>
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="descripcion-impuesto-addon">Desc.</span>
                                        <textarea class="form-control" id="descripcion-impuesto-textarea" name="descripcion-impuesto-textarea"
                                            placeholder="Ingresa una descripción" rows="3"
                                            aria-describedby="descripcion-impuesto-addon"></textarea>
                                    </div>
                                </div>



                                <!-- =======================
                                    Impuestos
                                    ======================= -->

                                <div class="card-header fw-bold">Configuración de impuestos</div>
                                <div class="card-body">

                                    <?php for ($i = 1; $i <= 8; $i++): ?>
                                        <div class="row align-items-end mb-2 border-bottom pb-2">

                                            <div class="col-lg-6">
                                                <label class="form-label">Impuesto <?= $i ?> (%)</label>
                                                <input
                                                    type="number"
                                                    step="0.01"
                                                    name="impuesto<?= $i ?>"
                                                    class="form-control"
                                                    value="0">
                                            </div>

                                            <div class="col-lg-6">
                                                <label class="form-label">Aplica a</label>
                                                <select name="imp<?= $i ?>_aplica" class="form-select">
                                                    <?php foreach ($aplicaCatalogos[$i] as $val => $txt): ?>
                                                        <option value="<?= $val ?>"><?= $txt ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                        </div>
                                    <?php endfor; ?>

                                </div>


                                <!-- =======================
                                    Acciones
                                    ======================= -->
                                <div class="text-end mt-3">
                                    <button type="submit" class="btn btn-success">
                                        <span id="btnText">REGISTRAR</span>
                                    </button>
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



<div class="modal fade" id="modalViewPrecio" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0">
            <div class="modal-header bg-primary-subtle p-3">
                <h5 class="modal-title">Datos del registro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <table class="table table-bordered">
                    <tbody>
                        <tr>
                            <td>Clave</td>
                            <td id="celClave"></td>
                        </tr>
                        <tr>
                            <td>Descripción</td>
                            <td id="celDescripcion"></td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div id="detalleImpuestos"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>Fecha creación</td>
                            <td id="celFecha"></td>
                        </tr>
                        <tr>
                            <td>Estado</td>
                            <td id="celEstado"></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>








<!-- end main content-->
<?php footerAdmin($data); ?>