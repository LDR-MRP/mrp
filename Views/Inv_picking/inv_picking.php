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
                <div class="col-md-4">
                    <h5>Órdenes de compra por recibir</h5>
                    <ul class="list-group" id="listaPicking"></ul>
                </div>

                <div class="col-md-8">
                    <h5>Recepción de materiales</h5>

                    <!-- Header documento -->
                    <div class="card mb-3">
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <tr>
                                    <th class="w-50">Compra origen</th>
                                    <th class="w-50">Destino</th>
                                </tr>
                                <tr>
                                    <td id="headerOrigen" class="align-top"></td>
                                    <td id="headerDestino" class="align-top"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>Escaneo de producto</strong>
                        </div>
                        <div class="card-body">
                            <input type="text" id="scannerInput" class="form-control"
                                placeholder="Escanea código de barras..."
                                autocomplete="off">
                        </div>
                    </div>

                    <!-- Tabla detalle -->
                    <div class="card mb-3">
                        <div class="card-body p-0">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Código</th>
                                        <th>Descripción</th>
                                        <th>Lote</th>
                                        <th>Solicitado</th>
                                        <th>Recibido</th>
                                        <th>Pendiente</th>
                                        <th>Unidad</th>
                                        <th>Obs.</th>
                                    </tr>
                                </thead>
                                <tbody id="detallePicking"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <strong>Observaciones</strong>
                        </div>
                        <div class="card-body">
                            <textarea id="observacionesPicking" class="form-control" rows="4"></textarea>
                        </div>
                    </div>

                    <button class="btn btn-success" onclick="guardarPicking()">Registrar Recepción</button>
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