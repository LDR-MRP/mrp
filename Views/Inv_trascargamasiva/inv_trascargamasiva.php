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
                            <a href="<?= base_url() ?>/inv_traslados" class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Volver a Traslados
                            </a>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= base_url() ?>/inv_traslados">Traslados</a></li>
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
                            Descarga la plantilla, llénala y súbela. Cada fila es una unidad (VIN). La plantilla incluye instrucciones y la lista de unidades que hoy puedes trasladar.
                        </div>
                        <button type="button" id="btnDescargarPlantillaTras" class="btn btn-success btn-sm">
                            <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Carga masiva de traslados de unidades</h5>
                </div>
                <div class="card-body">

                    <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                        <p class="text-muted">
                            Las filas con el mismo <strong>Almacén origen, Almacén destino y Fecha programada</strong> se registran en un mismo traslado (un solo folio TRU-).
                            Si una fila tiene datos inválidos (VIN inexistente, que no está en el almacén origen, ya en otro traslado, etc.) se omite y se manda al log, sin afectar a las demás.
                        </p>

                        <form id="formCargaTraslados" autocomplete="off">
                            <div class="row align-items-end">
                                <div class="col-lg-6 col-sm-8">
                                    <div class="mb-3">
                                        <label class="form-label" for="archivoTraslados">ARCHIVO (.xlsx)</label>
                                        <input type="file" class="form-control" id="archivoTraslados" name="archivo" accept=".xlsx,.xls" required>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-sm-4">
                                    <div class="mb-3">
                                        <button type="submit" class="btn btn-success w-100" id="btnSubirTraslados">
                                            <i class="ri-upload-2-line align-bottom me-1"></i> Subir y registrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div id="resultadoCargaTraslados" class="mt-3" style="display:none;">
                            <div class="alert" id="alertResultadoCargaTraslados" role="alert"></div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogCargaTraslados" style="display:none;">
                                    <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de omitidos
                                </button>
                                <a href="<?= base_url() ?>/inv_traslados" class="btn btn-outline-primary btn-sm" id="btnIrBandejaTraslados" style="display:none;">
                                    <i class="ri-list-check align-bottom me-1"></i> Ver traslados registrados
                                </a>
                            </div>
                        </div>
                    <?php } else { ?>
                        <p class="text-muted mb-0">No tienes permisos para registrar traslados mediante carga masiva.</p>
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
