<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                [cite_start]/* Estilos de Estados: HU-09 [cite: 4] */
                .text-primary { color: #0369a1; }
                .text-warning { color: #15803d; }
                .text-danger { color: #b91c1c; }
                :root { --primary: #0056b3; --success: #28a745; --warning: #ffc107; --danger: #dc3545; }
                body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
            </style>
            <section id="view-index-adquisiciones">
                <div class="row align-items-center mb-4">
                    <div class="col-md-6">
                        <h3 class="font-weight-bold text-success"><i class="ri-check-double-line"></i> Adquisiciones Pendientes</h3>
                        <p class="text-muted">Requisiciones autorizadas listas para compra.</p>
                    </div>
                </div>

                <div class="card border-left-success" style="border-left: 5px solid var(--success) !important;">
                    <div class="card-body">
                        <table id="tblReqs" class="table table-hover align-middle responsive table-striped" style="width:100% !important;">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Departamento</th>
                                    <th>Prioridad</th>
                                    <th>Monto Est.</th>
                                    <th class="text-right">Operación</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyReqs">
                            </tbody>
                        </table>
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