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

            <div class="card mb-3">
                <div class="card-body">
                    <h5>Crear Picking</h5>

                    <div class="row">
                        <div class="col-md-3">
                            <input type="text" id="folio" class="form-control" placeholder="Folio">
                        </div>

                        <div class="col-md-3">
                            <input type="text" id="pedido" class="form-control" placeholder="Pedido cliente">
                        </div>

                        <div class="col-md-3">
                            <select id="prioridad" class="form-control">
                                <option value="Alta">Alta</option>
                                <option value="Media">Media</option>
                                <option value="Baja">Baja</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <button class="btn btn-primary" onclick="crearPicking()">Crear</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <h5>Órdenes de Picking</h5>
                    <ul class="list-group" id="listaPicking"></ul>
                </div>

                <div class="col-md-8">
                    <h5>Detalle</h5>

                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Producto</th>
                                <th>Ubicación</th>
                                <th>Lote</th>
                                <th>Solicitado</th>
                                <th>Existente</th>
                                <th>Pickear</th>
                            </tr>
                        </thead>
                        <tbody id="detallePicking"></tbody>
                    </table>

                    <button class="btn btn-success" onclick="guardarPicking()">Guardar Picking</button>
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