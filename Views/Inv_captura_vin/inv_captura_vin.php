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
                                <input type="hidden" name="id" id="id">

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

                                <!-- CONFIGURACIÓN VIN -->
                                <label class="fw-bold">Configuración VIN</label>

                                <div class="row mt-3">

                                    <!-- FABRICANTE -->
                                    <div class="col-md-4 mb-3">
                                        <label>Fabricante (WMI)</label>
                                        <select class="form-select" name="id_fabricante" id="id_fabricante" required>
                                            <option value="">Seleccionar</option>
                                        </select>
                                    </div>

                                    <!-- TIPO VEHICULO -->
                                    <div class="col-md-4 mb-3">
                                        <label>Tipo de vehículo</label>
                                        <select class="form-select" name="id_tipo_vehiculo" id="id_tipo_vehiculo" required>
                                            <option value="">Seleccionar</option>
                                        </select>
                                    </div>

                                    <!-- PESO -->
                                    <div class="col-md-4 mb-3">
                                        <label>Peso bruto vehicular (kg)</label>
                                        <input type="number"
                                            class="form-control"
                                            name="peso_bruto_kg"
                                            id="peso_bruto_kg"
                                            min="1"
                                            required>
                                    </div>

                                    <!-- MOTOR -->
                                    <div class="col-md-4 mb-3">
                                        <label>Tipo de motor</label>
                                        <select class="form-select" name="id_tipo_motor" id="id_tipo_motor" required>
                                            <option value="">Seleccionar</option>
                                        </select>
                                    </div>

                                    <!-- POTENCIA -->
                                    <div class="col-md-4 mb-3">
                                        <label>Potencia HP</label>
                                        <input type="number"
                                            class="form-control"
                                            name="potencia_hp"
                                            id="potencia_hp"
                                            min="1"
                                            required>
                                    </div>

                                    <div class="col-md-4 mb-3">

                                        <label>Clasificación distancia</label>

                                        <select
                                            class="form-select"
                                            id="tipo_distancia">

                                            <option value="">
                                                Seleccionar
                                            </option>

                                            <option value="AUTO">
                                                Automóvil (mm)
                                            </option>

                                            <option value="CAMION">
                                                Autobús / Camiones (m)
                                            </option>

                                        </select>

                                    </div>

                                    <!-- DISTANCIA -->
                                    <div class="col-md-4 mb-3">

                                        <label id="label_distancia">
                                            Distancia entre ejes
                                        </label>

                                        <input
                                            type="number"
                                            step="1"
                                            class="form-control"
                                            name="distancia_ejes"
                                            id="distancia_ejes"
                                            required>

                                        <small
                                            class="text-muted"
                                            id="help_distancia">

                                            Seleccione clasificación

                                        </small>

                                    </div>

                                    <!-- AÑO -->
                                    <div class="col-md-4 mb-3">
                                        <label>Año VIN</label>
                                        <select class="form-select" name="anio" id="anio" required>
                                            <option value="">Seleccionar</option>
                                        </select>
                                    </div>

                                    <!-- PLANTA -->
                                    <div class="col-md-4 mb-3">
                                        <label>Planta</label>
                                        <select class="form-select" name="id_planta" id="id_planta" required>
                                            <option value="">Seleccionar</option>
                                        </select>
                                    </div>

                                </div>
                                <!-- PREVIEW -->
                                <div class="mt-4 text-center">
                                    <h5>VIN Base:</h5>
                                    <h3 id="vinPreview" class="text-primary">--------</h3>
                                </div>
                                <input type="hidden" name="vin_base" id="vin_base">

                                <div class="mt-4 text-end">
                                    <button
                                        type="submit"
                                        id="btnGuardar"
                                        class="btn btn-success">
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