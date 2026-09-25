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
                            <a href="<?= base_url() ?>/Inv_movimientosalmacenes" class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Volver a Traspasos
                            </a>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= base_url() ?>/Inv_movimientosalmacenes">Traspaso entre almacenes</a></li>
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
                            Descarga la plantilla, llénala y súbela. Las filas con el mismo Almacén origen y Almacén destino se agrupan en un mismo traspaso. La plantilla incluye una hoja de instrucciones.
                        </div>
                        <button type="button" id="btnDescargarPlantillaMovAlm" class="btn btn-success btn-sm">
                            <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Carga masiva de traspasos entre almacenes</h5>
                </div>
                <!-- end card header -->
                <div class="card-body">

                    <?php if ($_SESSION['permisosMod']['w']) { ?>
                        <p class="text-muted">
                            Las filas que compartan <strong>Almacén origen y Almacén destino</strong> se agrupan en un mismo traspaso (un solo folio TRF- con varias partidas). Si una fila tiene datos inválidos, se omite y no afecta a las demás filas.
                        </p>

                        <form id="formCargaTraspasos" autocomplete="off">
                            <div class="row align-items-end">
                                <div class="col-lg-6 col-sm-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="archivoTraspasos">ARCHIVO (.xlsx)</label>
                                        <input type="file" class="form-control" id="archivoTraspasos" name="archivo" accept=".xlsx,.xls" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-4">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-success w-100" id="btnSubirTraspasos">
                                            <i class="ri-upload-2-line align-bottom me-1"></i> Subir y registrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="resultadoCargaTraspasos" class="mt-3" style="display:none;">
                            <div class="alert" id="alertResultadoCargaTraspasos" role="alert"></div>
                            <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCargaTraspasos" style="display:none;">
                                <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de omitidos
                            </button>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">No tienes permisos para registrar traspasos mediante carga masiva.</p>
                    <?php } ?>

                </div>
                <!-- end card body -->
            </div>
            <!-- end card -->

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
