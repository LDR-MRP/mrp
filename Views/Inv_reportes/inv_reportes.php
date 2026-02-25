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

                <!-- QR PRODUCTOS -->
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body text-center">
                            <div class="avatar-sm mx-auto mb-3">
                                <span class="avatar-title bg-dark rounded-circle fs-4">
                                    <i class="ri-qr-code-line"></i>
                                </span>
                            </div>
                            <h5 class="card-title">QR de Inventario</h5>
                            <p class="text-muted">Generación individual o masiva</p>
                            <button class="btn btn-dark btn-sm" onclick="verModuloQr()">
                                Abrir
                            </button>
                        </div>
                    </div>
                </div>

                <!-- INVENTARIO GENERAL -->
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate">
                        <div class="card-body text-center">
                            <div class="avatar-sm mx-auto mb-3">
                                <span class="avatar-title bg-secondary rounded-circle fs-4">
                                    <i class="ri-file-list-3-line"></i>
                                </span>
                            </div>
                            <h5 class="card-title">Inventario General</h5>
                            <p class="text-muted">Existencias actuales</p>
                            <button class="btn btn-secondary btn-sm">
                                Abrir
                            </button>
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