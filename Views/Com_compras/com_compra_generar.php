<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                :root { --primary: #0056b3; --success: #28a745; --warning: #ffc107; --danger: #dc3545; }
                body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                .main-content { padding: 20px; }
                .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.04); margin-bottom: 2rem; }
                .card-header { background: #fff; border-bottom: 1px solid #f1f1f1; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
                
                /* Badges LDR Style */
                .badge-draft { background-color: #e2e8f0; color: #475569; }
                .badge-review { background-color: #fef3c7; color: #92400e; }
                .badge-approved { background-color: #dcfce7; color: #166534; }
                .badge-rejected { background-color: #fee2e2; color: #991b1b; }
                .badge-purchasing { background-color: #e0e7ff; color: #3730a3; }
                
                /* Layout Helpers */
                .bg-dark-ldr { background-color: #1a202c; color: white; }
                .table thead th { border-top: none; font-size: 0.75rem; color: #718096; text-transform: uppercase; }
                .input-real-price { background-color: #fffbeb; border: 1px solid #fcd34d; font-weight: bold; }
                .section-separator { border-top: 2px dashed #cbd5e0; margin: 4rem 0; position: relative; }
                .section-separator::after { content: 'SIGUIENTE VISTA'; position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #cbd5e0; padding: 2px 15px; font-size: 10px; border-radius: 10px; color: white; }
            </style>
            <section id="view-generate-oc" class="pb-5">
                <div class="row align-items-center mb-4">
                    <div class="col-md-6">
                        <h3 class="font-weight-bold">Generar Orden de Compra</h3>
                        <p class="text-muted">Conversión de Requisición #1020 a compromiso de compra..</p>
                    </div>
                    <div class="col-md-6 d-flex justify-content-md-end justify-content-start">
                        <button class="btn btn-primary shadow-sm px-4" id="btnNueva">
                            Generar OC Final
                        </button>
                    </div>
                </div>

                <div class="card bg-dark-ldr mb-4">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 border-right border-secondary">
                                <label class="small font-weight-bold">Proveedor Adjudicado</label>
                                <select class="form-control form-control-sm bg-dark text-white border-0">
                                    <option>Seleccionar Proveedor del Catálogo LDR...</option>
                                    <option>Llantas y Neumáticos S.A.</option>
                                </select>
                            </div>
                            <div class="col-md-3 border-right border-secondary pl-4">
                                <label class="small font-weight-bold">Moneda</label>
                                <select class="form-control form-control-sm bg-dark text-white border-0"><option>MXN - Pesos</option></select>
                            </div>
                            <div class="col-md-3 pl-4">
                                <label class="small font-weight-bold">Términos</label>
                                <select class="form-control form-control-sm bg-dark text-white border-0"><option>Crédito 30 Días</option></select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow">
                    <div class="card-header">Ajuste de Precios Reales</div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="pl-4">Producto</th>
                                    <th width="100">Cant.</th>
                                    <th width="150">P. Estimado</th>
                                    <th width="200" class="text-primary font-weight-bold">PRECIO REAL</th>
                                    <th width="150">Ahorro</th>
                                    <th width="150" class="pr-4">Subtotal Real</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="pl-4 font-weight-bold">Neumático Delantero Camión 12R22.5</td>
                                    <td class="text-center">4</td>
                                    <td class="text-muted">$6,125.00</td>
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-prepend"><span class="input-group-text">$</span></div>
                                            <input type="number" class="form-control input-real-price" value="5900.00">
                                        </div>
                                    </td>
                                    <td class="text-success font-weight-bold">-$900.00</td>
                                    <td class="font-weight-bold pr-4 text-right">$23,600.00</td>
                                </tr>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr class="h5">
                                    <td colspan="5" class="text-right border-0 pl-4 font-weight-bold">Monto Total de la OC:</td>
                                    <td class="text-right border-0 pr-4 text-success font-weight-bold">$23,600.00</td>
                                </tr>
                            </tfoot>
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