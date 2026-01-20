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
                <div class="col-md-8">
                    <label>Producto</label>
                    <input type="text" class="form-control" id="inventarioSearch" placeholder="Buscar por clave o descripción">
                    <input type="hidden" id="inventarioid">
                </div>


                <div class="col-md-2 align-self-end">
                    <button class="btn btn-primary" id="btnBuscar">
                        <i class="fas fa-search"></i> Consultar
                    </button>
                </div>
            </div>

            <hr>

            <div class="row g-3 mb-4">

                <div class="col-md-6">
                    <label>Artículo</label>
                    <input type="text" class="form-control" id="articulo" readonly>
                </div>

                <div class="col-md-3">
                    <label>Unidad de salida</label>
                    <input type="text" class="form-control" id="unidad_salida" readonly>
                </div>

                <div class="col-md-3">
                    <label>Unidad de entrada</label>
                    <input type="text" class="form-control" id="unidad_entrada" readonly>
                </div>

                <div class="col-md-3">
                    <label>Ubicación</label>
                    <input type="text" class="form-control" id="ubicacion" readonly>
                </div>

                <div class="col-md-3">
                    <label>Fecha última compra</label>
                    <input type="text" class="form-control" id="fecha_ultima_compra" readonly>
                </div>

                <div class="col-md-3">
                    <label>Costo promedio</label>
                    <input type="text" class="form-control" id="costo_promedio" readonly>
                </div>

                <div class="col-md-3">
                    <label>Existencia</label>
                    <input type="text" class="form-control" id="existencia_actual" readonly>
                </div>

            </div>


            <hr>

            <div class="row">
                <div class="col-12">
                    <table class="table table-bordered table-striped" id="tableKardex">
                        <thead>
                            <tr>
                                <th>DOCUMENTO</th>
                                <th>DESCRIPCIÓN</th>
                                <th>CANTIDAD</th>
                                <th>COSTO</th>
                                <th>EXISTENCIA</th>
                                <th>COMPRAS</th>
                                <th>FECHA</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-4">

                <div class="col-md-3">
                    <label>Totales</label>
                    <input type="text" class="form-control" id="total_existencia" readonly>
                </div>

                <div class="col-md-3">
                    <label>Entradas</label>
                    <input type="text" class="form-control" id="total_entradas" readonly>
                </div>

                <div class="col-md-3">
                    <label>Salidas</label>
                    <input type="text" class="form-control" id="total_salidas" readonly>
                </div>

                <div class="col-md-3">
                    <label>Compras</label>
                    <input type="text" class="form-control" id="total_compras" readonly>
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