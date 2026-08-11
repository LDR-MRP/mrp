<?php headerAdmin($data); ?>

<div id="contentAjax"></div>

<div class="main-content">

    <div class="page-content">

        <div class="container-fluid">

            <!-- ============================================= -->
            <!-- BREADCRUMB -->
            <!-- ============================================= -->

            <div class="row">
                <div class="col-12">

                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">

                        <h4 class="mb-sm-0">
                            Solicitudes de Traslado
                        </h4>

                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item">
                                    <a href="<?= base_url(); ?>">
                                        Dashboard
                                    </a>
                                </li>

                                <li class="breadcrumb-item active">
                                    Traslados
                                </li>
                            </ol>
                        </div>

                    </div>

                </div>
            </div>

            <!-- ============================================= -->
            <!-- HEADER -->
            <!-- ============================================= -->

            <div class="row mb-4">

                <div class="col-lg-8">

                    <div class="d-flex align-items-center">

                        <div class="avatar-lg flex-shrink-0">

                            <div class="avatar-title rounded-circle bg-success text-white fs-1">
                                <i class="ri-truck-line"></i>
                            </div>

                        </div>

                        <div class="ms-3">

                            <h1 class="mb-1">
                                BANDEJA DE TRASLADOS
                            </h1>

                            <p class="text-muted mb-0">
                                Gestión centralizada de movimientos de unidades entre almacenes.
                            </p>

                        </div>

                    </div>

                </div>

                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">

                    <a href="<?= base_url(); ?>/Inv_traslados/nuevo_traslado"
                        class="btn btn-primary btn-lg">

                        <i class="ri-add-line me-1"></i>

                        Nueva Solicitud

                    </a>

                </div>

            </div>

            <div class="alert alert-info border-0 mb-4">

                <div class="d-flex flex-wrap gap-4">

                    <span>
                        <i class="ri-file-list-3-line me-1"></i>
                        Solicitud
                    </span>

                    <i class="ri-arrow-right-line"></i>

                    <span>
                        <i class="ri-truck-line me-1"></i>
                        Salida
                    </span>

                    <i class="ri-arrow-right-line"></i>

                    <span>
                        <i class="ri-road-map-line me-1"></i>
                        En tránsito
                    </span>

                    <i class="ri-arrow-right-line"></i>

                    <span>
                        <i class="ri-home-4-line me-1"></i>
                        Recepción
                    </span>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- KPIs -->
            <!-- ============================================= -->

            <div class="row">

                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">
                            <div class="d-flex justify-content-between">

                                <div>
                                    <p class="text-uppercase fw-medium text-muted mb-2">
                                        Pendientes
                                    </p>

                                    <h2 id="kpiPendientes">0</h2>

                                    <small class="text-warning">
                                        Requieren atención
                                    </small>
                                </div>

                                <div class="avatar-sm">
                                    <div class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="ri-time-line"></i>
                                    </div>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-uppercase fw-medium text-muted mb-2">
                                        En Tránsito
                                    </p>

                                    <h2 id="kpiTransito">
                                        0
                                    </h2>

                                    <small class="text-info">
                                        Unidades en movimiento
                                    </small>

                                </div>

                                <div class="avatar-sm">
                                    <div class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-road-map-line"></i>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-uppercase fw-medium text-muted mb-2">
                                        Recibidas
                                    </p>

                                    <h2 id="kpiRecibidas">
                                        0
                                    </h2>

                                    <small class="text-success">
                                        Traslados completados
                                    </small>

                                </div>

                                <div class="avatar-sm">
                                    <div class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

                <div class="col-xl-3 col-md-6">

                    <div class="card">

                        <div class="card-body">

                            <div class="d-flex justify-content-between">

                                <div>

                                    <p class="text-uppercase fw-medium text-muted mb-2">
                                        Canceladas
                                    </p>

                                    <h2 id="kpiCanceladas">
                                        0
                                    </h2>

                                    <small class="text-danger">
                                        Solicitudes canceladas
                                    </small>

                                </div>

                                <div class="avatar-sm">
                                    <div class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                        <i class="ri-close-circle-line"></i>
                                    </div>
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            <!-- ============================================= -->
            <!-- TABLA -->
            <!-- ============================================= -->

            <div class="card">

                <div class="card-body">
                    <div class="table-responsive">

                        <table id="tableTraslados"
                            class="table table-hover align-middle w-100">

                            <thead>

                                <tr>

                                    <th>FOLIO</th>
                                    <th>ORIGEN</th>
                                    <th>DESTINO</th>
                                    <th>TIPO</th>
                                    <th>PROVEEDOR</th>
                                    <th>UNIDADES</th>
                                    <th>FECHA PROGRAMADA</th>
                                    <th>ESTATUS</th>
                                    <th>ACCIONES</th>

                                </tr>

                            </thead>

                            <tbody>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>



    <div
        class="modal fade"
        id="modalDetalleTraslado"
        tabindex="-1">

        <div class="modal-dialog modal-xl">

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Detalle del Traslado
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal">
                    </button>

                </div>

                <div class="modal-body">

                    <div id="detalleTraslado">

                    </div>
                    <div id="detalleTraslado">

                        <table class="table table-sm table-bordered">

                            <thead>
                                <tr>
                                    <th>VIN</th>
                                    <th>MODELO</th>
                                </tr>
                            </thead>

                            <tbody id="tbodyDetalle"></tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>




    <footer class="footer">

        <div class="container-fluid">

            <div class="row">

                <div class="col-sm-6">

                    <script>
                        document.write(new Date().getFullYear())
                    </script>

                    © LDR.

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

<?php footerAdmin($data); ?>