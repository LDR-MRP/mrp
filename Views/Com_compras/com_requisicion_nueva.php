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
            <form id="formRequisicion">
                <section id="view-create-edit">
                    <div class="row align-items-center mb-4">
                        <div class="col-md-6">
                            <h3 class="font-weight-bold">Gestión de Requisición #<span id="folio-display">NUEVA</span></h3>
                            <p class="text-muted">Generación de solicitudes internas.</p>
                        </div>

                        <div class="col-md-6 d-flex justify-content-md-end justify-content-start">
                            <button type="button" class="btn btn-light border mr-2" data-redirect="com_requisicion">Cancelar</button>
                            <button type="submit" class="btn btn-primary shadow-sm px-4">
                                <i class="ri-save-3-line"></i> Guardar Requisición
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header">Captura de Partidas</div>
                                <div class="card-body">
                                    <div class="row p-3 bg-light rounded mb-4">
                                        <div class="col-md-2">
                                            <label class="small font-weight-bold text-uppercase">CLAVE</label>
                                            <input id="sku" name="sku" type="text" class="form-control" placeholder="Buscar por Clave...">
                                            <div id="sku-feedback" class="small text-muted mt-1"></div>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="small font-weight-bold text-uppercase">Descripción</label>
                                            <select id="producto" class="form-control">
                                                <option value="">— Selecciona —</option>
                                            </select>
                                        </div>
                                        <div class="col-md-1">
                                            <label class="small font-weight-bold text-uppercase">Cant.</label>
                                            <input id="cantidad" type="number" step="1" class="form-control text-end" value="1" min="1" max="1000">
                                        </div>
                                        <div class="col-md-1">
                                            <label class="small font-weight-bold text-uppercase">Ud.</label>
                                            <input id="unidad_salida" type="text" class="form-control-plaintext" disabled readonly>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="small font-weight-bold text-uppercase">P. Est. <span class="small text-muted">(MXN)</span></label>
                                            <input id="ultimo_costo" type="text" step="0.000001" class="form-control text-end" value="0.000000" required>
                                        </div>
                                        <div class="col-md-1">
                                            <label>&nbsp;</label>
                                            <button id="btn-agregar" class="form-control btn btn-success"><i class="ri-checkbox-circle-line text-white"></i></button>
                                        </div>
                                    </div>
                                    <table id="tblPartidas" class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Descripción</th>
                                                <th width="80">Cant</th>
                                                <th width="120">P. Unit</th>
                                                <th width="120">Subtotal</th>
                                                <th width="120">Notas</th>
                                                <th width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4">
                            <div class="card">
                                <div class="card-header">General</div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Departamento</label>
                                        <select name="departamentoid" class="form-control">
                                            <option value="1">Logística</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Prioridad</label>
                                        <select name="prioridad" class="form-control">
                                            <option value="media">Media</option>
                                            <option value="alta">Alta</option>
                                            <option value="baja">Baja</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label class="small font-weight-bold">Comentarios</label>
                                        <textarea name="comentarios" class="form-control form-control-sm" rows="3"></textarea>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="font-weight-bold text-muted small">TOTAL ESTIMADO:</span>
                                        <div class="d-flex align-items-center">
                                            <span class="h4 mb-0 text-primary mr-1">$</span>
                                            <input type="text" readonly name="monto_estimado" id="monto_estimado" 
                                                class="form-control-plaintext form-control-lg font-weight-bold text-primary text-right h4 mb-0" 
                                                style="width: 150px;" value="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </form>
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