<?php headerAdmin($data);
?>
<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <!-- start page title -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>

                        <div class="page-title-right d-flex align-items-center gap-2">
                            <a href="<?= base_url() ?>/Inv_movcargamasiva" class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Carga de entradas
                            </a>
                            <a href="<?= base_url() ?>/Inv_movimientosinventario" class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Volver a Movimientos
                            </a>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= base_url() ?>/Inv_movimientosinventario">Movimientos Inventario</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>

                    </div>
                </div>
            </div>
            <!-- end page title -->

            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                        <div>
                            <i class="ri-file-excel-2-line align-bottom me-1"></i>
                            Descarga el layout con <strong>todos los VINs en inventario</strong> (todas las plantas y almacenes), cambia el estatus a <strong>3 - Facturado</strong> con su distribuidor y factura, y súbelo. El archivo es <strong>solo de actualización</strong>.
                        </div>
                        <button type="button" id="btnDescargarLayoutVentas" class="btn btn-success btn-sm">
                            <i class="ri-download-2-line align-bottom me-1"></i> Descargar layout de VINs
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Carga masiva de ventas / facturación de unidades</h5>
                </div>
                <div class="card-body">

                    <?php if ($_SESSION['permisosMod']['w']) { ?>
                        <ul class="text-muted mb-3">
                            <li>Estatus del VIN: <span class="badge bg-success">1 - Disponible</span> <span class="badge bg-danger">2 - Tatuado</span> <span class="badge bg-secondary">3 - Facturado</span>.</li>
                            <li>Se puede pasar a <strong>3 - Facturado</strong> desde <strong>1 - Disponible</strong> o <strong>2 - Tatuado</strong>. <strong>Una unidad por factura</strong>: cada VIN genera su propio movimiento de Ventas y baja la existencia de su almacén.</li>
                            <li>Solo se venden unidades de almacenes de <strong>Producto Terminado</strong> o de <strong>Tránsito</strong>; las de otros almacenes van al log.</li>
                            <li>Si se agregan filas o se modifica el VIN, SKU, nombre, almacén o número de motor, esa fila <strong>no se sube</strong> y queda en el log descargable.</li>
                        </ul>

                        <form id="formCargaVentas" autocomplete="off">
                            <div class="row align-items-end">
                                <div class="col-lg-6 col-sm-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="archivoVentas">ARCHIVO (.xlsx)</label>
                                        <input type="file" class="form-control" id="archivoVentas" name="archivo" accept=".xlsx,.xls" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-4">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-success w-100" id="btnSubirVentas">
                                            <i class="ri-upload-2-line align-bottom me-1"></i> Subir y actualizar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="resultadoCargaVentas" class="mt-3" style="display:none;">
                            <div class="alert" id="alertResultadoCargaVentas" role="alert"></div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCargaVentas" style="display:none;">
                                <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de lo que no se subió
                            </button>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">No tienes permisos para registrar ventas mediante carga masiva.</p>
                    <?php } ?>

                </div>
            </div>

            <!-- ===================== DEVOLUCIONES ===================== -->
            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0"><i class="ri-arrow-go-back-line align-bottom me-1"></i> Devoluciones de unidades</h5>
                </div>
                <div class="card-body">
                    <ul class="text-muted mb-3">
                        <li>El layout trae los VINs <span class="badge bg-secondary">3 - Facturado</span> con su distribuidor, factura y fecha de venta. Puedes acotarlo por fecha de venta.</li>
                        <li>Para devolver solo cambia ESTATUS a <strong>DEVOLUCION</strong>. La unidad regresa al mismo almacén del que salió.</li>
                        <li>Cada VIN genera un movimiento <strong>Devol. de venta</strong> (suma 1 en multialmacén) y queda como <span class="badge bg-success">1 - Disponible</span>, listo para volver a venderse.</li>
                    </ul>

                    <div class="row align-items-end g-2 mb-3">
                        <div class="col-sm-4 col-lg-3">
                            <label class="form-label" for="devDesde">Ventas desde</label>
                            <input type="date" class="form-control" id="devDesde">
                        </div>
                        <div class="col-sm-4 col-lg-3">
                            <label class="form-label" for="devHasta">Ventas hasta</label>
                            <input type="date" class="form-control" id="devHasta">
                        </div>
                        <div class="col-sm-4 col-lg-3">
                            <button type="button" id="btnDescargarLayoutDev" class="btn btn-success w-100">
                                <i class="ri-download-2-line align-bottom me-1"></i> Descargar VINs facturados
                            </button>
                        </div>
                    </div>

                    <?php if ($_SESSION['permisosMod']['w']) { ?>
                        <form id="formCargaDevoluciones" autocomplete="off">
                            <div class="row align-items-end">
                                <div class="col-lg-6 col-sm-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="archivoDevoluciones">ARCHIVO (.xlsx)</label>
                                        <input type="file" class="form-control" id="archivoDevoluciones" name="archivo" accept=".xlsx,.xls" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-4">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-warning w-100">
                                            <i class="ri-upload-2-line align-bottom me-1"></i> Subir devoluciones
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="resultadoCargaDevoluciones" class="mt-3" style="display:none;">
                            <div class="alert" id="alertResultadoCargaDevoluciones" role="alert"></div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCargaDevoluciones" style="display:none;">
                                <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de lo que no se subió
                            </button>
                        </div>
                    <?php } ?>
                </div>
            </div>

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
