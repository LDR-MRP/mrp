<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                [cite_start]/* Estilos de Estados: HU-09 [cite: 4] */
                .badge-draft { background: #f1f5f9; color: #475569; }
                .badge-review { background: #e0f2fe; color: #0369a1; }
                .badge-observed { background: #fffbeb; color: #b45309; }
                .badge-approved { background: #f0fdf4; color: #15803d; }
                .badge-rejected { background: #fef2f2; color: #b91c1c; }
                .badge-purchasing { background: #f5f3ff; color: #6d28d9; }
                .badge-closed { background: #f8fafc; color: #1e293b; border: 1px solid #e2e8f0; }

                :root { --primary: #0056b3; --success: #28a745; --warning: #ffc107; --danger: #dc3545; }
                body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            </style>
            <section id="view-index-general">
                <div class="row align-items-center mb-4">
                    <div class="col-md-6">
                        <h3 class="font-weight-bold">Bandeja de Requisiciones</h3>
                        <p class="text-muted">Gestión y seguimiento de solicitudes internas.</p>
                    </div>
                    <div class="col-md-6 d-flex justify-content-md-end justify-content-start">
                        <button class="btn btn-primary shadow-sm px-4" id="btnNueva">
                            <i class="ri-add-line mr-1"></i> Nueva Requisición
                        </button>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <div>
                            <table id="tblReqs" class="table table-hover align-middle responsive table-striped" style="width:100% !important;">
                                <thead>
                                    <tr>
                                        <th>Folio</th>
                                        <th>Fecha</th>
                                        <th>Solicitante</th>
                                        <th>Estado</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyReqs">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
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

<?php footerAdmin($data); ?>