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

            <div class="row">
                <div class="col-xl-12">
                    <div class="card">
                        <div class="card-header">
                            <h5>Captura de Modelo VIN</h5>
                        </div>

                        <div class="card-body">

                            <form id="formVinModelo">

                                <!-- MODELO -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <label>Modelo</label>
                                        <input type="text" class="form-control" name="modelo" placeholder="EJ.AUMARK S2" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label>Estado</label>
                                        <select class="form-select" name="estado">
                                            <option value="1">Inactivo</option>
                                            <option value="2" selected>Activo</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                <!-- VIN 8 DIGITOS -->
                                <label class="fw-bold">Estructura VIN (8 caracteres)</label>

                                <div class="row text-center mt-3">

                                    <?php
                                    $campos = [
                                        "digt_pais" => "País",
                                        "digit_fabricante" => "Fabricante",
                                        "digit_vehiculo" => "Vehículo",
                                        "digit_modelo" => "Modelo",
                                        "digit_cuerpo" => "Tipo de cuerpo",
                                        "digit_sujecion" => "Sujeción",
                                        "digit_transmision" => "Transmisión",
                                        "digit_motor" => "Motor"
                                    ];

                                    foreach ($campos as $name => $label): ?>

                                        <div class="col">
                                            <input
                                                type="text"
                                                maxlength="1"
                                                class="form-control vin-input text-center"
                                                name="<?= $name ?>"
                                                data-label="<?= $label ?>"
                                                required>
                                            <small><?= $label ?></small>
                                        </div>



                                    <?php endforeach; ?>

                                    <div class="col">
                                        <select class="form-select text-center" name="anio" id="anio" required>
                                            <option value="">Año</option>
                                        </select>
                                        <small>Año (VIN)</small>
                                    </div>

                                    <div class="col">
                                        <input
                                            type="text"
                                            maxlength="1"
                                            class="form-control vin-planta text-center"
                                            name="planta"
                                            id="planta"
                                            required>
                                        <small>Planta</small>
                                    </div>

                                </div>
                                <!-- PREVIEW -->
                                <div class="mt-4 text-center">
                                    <h5>VIN Base:</h5>
                                    <h3 id="vinPreview" class="text-primary">--------</h3>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-success">
                                        Guardar Modelo VIN
                                    </button>
                                </div>

                            </form>

                            <hr>

                            <h5 class="mt-4">Modelos VIN Registrados</h5>

                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Modelo</th>
                                            <th>VIN Base</th>
                                            <th>Año</th>
                                            <th>Planta</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tablaVin">
                                        <tr>
                                            <td colspan="6" class="text-center">Cargando...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>
                </div>
            </div>


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
<!-- end main content-->
<?php footerAdmin($data); ?>