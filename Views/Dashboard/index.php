<?php headerAdmin($data); ?>

<div class="main-content">

    <div class="page-content">
        <!-- Contenedor Principal -->
        <div class="container-fluid">
            <!-- BANNER DE BIENVENIDA -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card bg-primary-subtle border-0 overflow-hidden">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-sm-8">
                                    <h3 class="fw-semibold mb-2">¡Bienvenido, <span id="lbl-user-name">...</span>!</h3>
                                    <p class="text-muted mb-0">Monitor global de operaciones para <span class="fw-bold text-primary">LDR Solutions</span>.</p>
                                    <div class="mt-3 d-flex gap-3">
                                    </div>
                                </div>
                                <div class="col-sm-4 d-none d-sm-block text-center">
                                    <img src="" id="img-welcome" alt="" class="img-fluid" style="max-height: 120px;">
                                </div>
                            </div>
                        </div>
                    </div>
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