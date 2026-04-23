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
            <div class="container-fluid">

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5>Tipo de cambio por moneda</h5>
                        <button class="btn btn-primary" onclick="toggleForm()">
                            Nuevo Tipo de Cambio
                        </button>
                    </div>

                    <!-- FORM -->
                    <div id="formContainer" class="card-body border-top" style="display:none;">
                        <div class="alert alert-info mb-3">
                            <i class="bi bi-info-circle"></i>
                            Registra el tipo de cambio diario por moneda.
                            Solo se permite <b>un registro por día y moneda</b>.
                        </div>

                        <form id="formTipoCambio">

                            <div class="row mb-3">

                                <div class="col-md-4">
                                    <label class="form-label">Moneda</label>
                                    <select name="monedaid" id="monedaid" class="form-select" required>
                                    </select>

                                    <small class="text-muted">
                                        Selecciona la moneda a la que corresponde el tipo de cambio
                                    </small>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Tipo de cambio</label>
                                    <input
                                        type="number"
                                        step="0.0001"
                                        name="tipo_cambio"
                                        class="form-control"
                                        placeholder="Ej: 17.2567"
                                        required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Fecha</label>
                                    <input
                                        type="date"
                                        name="fecha_creacion"
                                        id="fecha_creacion"
                                        class="form-control"
                                        readonly>

                                    <small class="text-muted">
                                        La fecha se asigna automáticamente (día actual)
                                    </small>
                                </div>

                            </div>

                            <div class="text-end">
                                <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancelar</button>
                                <button type="submit" class="btn btn-success">Guardar</button>
                            </div>

                        </form>

                    </div>

                    <!-- FILTROS -->
                    <div class="card-body border-top">

                        <div class="row mb-3">

                            <div class="col-md-3">
                                <select id="filtro_moneda" class="form-select">
                                    <option value="">Todas las monedas</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <input type="date" id="fecha_desde" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <input type="date" id="fecha_hasta" class="form-control">
                            </div>

                            <div class="col-md-3">
                                <button id="btnBuscar" class="btn btn-success w-100">
                                    Buscar
                                </button>
                            </div>

                        </div>

                        <!-- TABLA -->
                        <table class="table table-bordered" id="tableTipoCambio">
                            <thead>
                                <tr>
                                    <th>Moneda</th>
                                    <th>Tipo de cambio</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                        </table>

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