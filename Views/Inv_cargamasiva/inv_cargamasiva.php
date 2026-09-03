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
                            <a href="<?= base_url() ?>/Inv_inventario" class="btn btn-light btn-sm">
                                <i class="ri-arrow-left-line align-bottom me-1"></i> Volver a Inventario
                            </a>
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= base_url() ?>/Inv_inventario">Inventario</a></li>
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
                            Descarga la plantilla, llénala y súbela en la pestaña correspondiente. La plantilla incluye una hoja de instrucciones.
                        </div>
                        <button type="button" id="btnDescargarPlantilla" class="btn btn-success btn-sm">
                            <i class="ri-download-2-line align-bottom me-1"></i> Descargar plantilla
                        </button>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" id="nav-tab-carga" role="tablist">
                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#tabAltas" role="tab">
                                    ALTA MASIVA
                                </a>
                            </li>
                        <?php } ?>
                        <?php if ($_SESSION['permisosMod']['u']) { ?>
                            <li class="nav-item">
                                <a class="nav-link <?= empty($_SESSION['permisosMod']['w']) ? 'active' : '' ?>" data-bs-toggle="tab" href="#tabActualizacion" role="tab">
                                    ACTUALIZACIÓN MASIVA
                                </a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <!-- end card header -->
                <div class="card-body">
                    <div class="tab-content">

                        <?php if ($_SESSION['permisosMod']['w']) { ?>
                        <div class="tab-pane active" id="tabAltas" role="tabpanel">
                            <p class="text-muted">
                                Inserta únicamente los productos <strong>nuevos</strong>. Si la <code>CLAVE_ARTICULO</code> ya existe en el sistema o está repetida dentro del archivo, la fila se omite y podras descargar las claves repetidas.
                            </p>

                            <form id="formAltasMasivas" autocomplete="off">
                                <div class="row align-items-end">
                                    <div class="col-lg-6 col-sm-8">
                                        <div class="mb-3">
                                            <label class="form-label" for="archivoAltas">ARCHIVO (.xlsx)</label>
                                            <input type="file" class="form-control" id="archivoAltas" name="archivo" accept=".xlsx,.xls" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-4">
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-success w-100" id="btnSubirAltas">
                                                <i class="ri-upload-2-line align-bottom me-1"></i> Subir e insertar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div id="resultadoAltas" class="mt-3" style="display:none;">
                                <div class="alert" id="alertResultadoAltas" role="alert"></div>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogAltas" style="display:none;">
                                    <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de omitidos
                                </button>
                            </div>
                        </div>
                        <?php } ?>
                        <!-- end tab altas -->

                        <?php if ($_SESSION['permisosMod']['u']) { ?>
                        <div class="tab-pane <?= empty($_SESSION['permisosMod']['w']) ? 'active' : '' ?>" id="tabActualizacion" role="tabpanel">
                            <p class="text-muted">
                                Actualiza productos <strong>existentes</strong> comparando la <code>CLAVE_ARTICULO</code>. Las celdas vacías conservan el valor actual del producto. Si la clave no está registrada, la fila se omite y no se insertara.
                            </p>

                            <form id="formActualizacionMasiva" autocomplete="off">
                                <div class="row align-items-end">
                                    <div class="col-lg-6 col-sm-8">
                                        <div class="mb-3">
                                            <label class="form-label" for="archivoActualizacion">ARCHIVO (.xlsx)</label>
                                            <input type="file" class="form-control" id="archivoActualizacion" name="archivo" accept=".xlsx,.xls" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-4">
                                        <div class="mb-3">
                                            <button type="submit" class="btn btn-warning w-100" id="btnSubirActualizacion">
                                                <i class="ri-refresh-line align-bottom me-1"></i> Subir y actualizar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <div id="resultadoActualizacion" class="mt-3" style="display:none;">
                                <div class="alert" id="alertResultadoActualizacion" role="alert"></div>
                                <button type="button" class="btn btn-outline-danger btn-sm" id="btnLogActualizacion" style="display:none;">
                                    <i class="ri-file-download-line align-bottom me-1"></i> Descargar log de no actualizados
                                </button>
                            </div>
                        </div>
                        <?php } ?>
                        <!-- end tab actualizacion -->

                    </div>
                    <!-- end tab content -->
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
