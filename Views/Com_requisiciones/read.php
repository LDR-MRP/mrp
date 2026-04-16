<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Mantengo tus estilos intactos -->
            <style>
                :root {
                    --primary: #0056b3;
                    --success: #28a745;
                    --warning: #ffc107;
                    --danger: #dc3545;
                }

                body {
                    background-color: #f4f7f6;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }

                .card {
                    border: none;
                    border-radius: 10px;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04);
                    margin-bottom: 2rem;
                }

                .card-header {
                    background: #fff;
                    border-bottom: 1px solid #f1f1f1;
                    font-weight: bold;
                    text-transform: uppercase;
                    font-size: 0.8rem;
                    letter-spacing: 1px;
                }

                /* Badges LDR Style */
                .badge-draft { background-color: #e2e8f0; color: #475569; }
                .badge-review { background-color: #fef3c7; color: #92400e; }
                .badge-approved { background-color: #dcfce7; color: #166534; }
                .badge-rejected { background-color: #fee2e2; color: #991b1b; }
                .badge-purchasing { background-color: #e0e7ff; color: #3730a3; }

                .table thead th {
                    border-top: none;
                    font-size: 0.75rem;
                    color: #718096;
                    text-transform: uppercase;
                }
                
                /* Estilo extra para simular campos de solo lectura elegantes */
                .read-only-field {
                    background-color: #f8f9fa;
                    border: 1px solid #e9ecef;
                    border-radius: 6px;
                    padding: 10px 15px;
                    min-height: 42px;
                }
            </style>

            <section id="view-read-requisicion">
                <!-- Breadcrumb -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/com_requisiciones">Requisiciones</a></li>
                                    <li class="breadcrumb-item active">Ver Solicitud</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Título Principal y Estatus -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="avatar-md me-3">
                                    <span class="avatar-title bg-primary text-white rounded-circle fs-3 shadow-sm">
                                        <i class="ri-file-list-3-line"></i>
                                    </span>
                                </div>
                                <div>
                                    <h4 class="mb-1 text-dark fw-bold ls-05 d-flex align-items-center">
                                        Solicitud de Compra #<span id="lbl-idrequisicion" class="ms-1">...</span>
                                        <span id="lbl-estatus" class="ms-3 badge bg-light text-muted fs-12 fw-normal">Cargando...</span>
                                    </h4>
                                    <p class="text-muted mb-0 fs-13">Expediente de requisición de solo lectura.</p>
                                </div>
                            </div>
                            
                            <!-- Badges de Prioridad (Movido arriba para mejor visibilidad) -->
                            <div class="text-end">
                                <span class="text-uppercase fs-11 fw-bold text-muted d-block mb-1">Prioridad</span>
                                <span id="lbl-prioridad" class="badge bg-light text-dark fs-12 px-3 py-1 shadow-sm">...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- COLUMNA IZQUIERDA: Detalles -->
                    <div class="col-lg-8">
                        
                        <!-- Tarjeta: Datos Generales -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-header bg-soft-primary border-bottom border-light d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 text-dark fw-bold"><i class="ri-article-line text-primary me-1 fs-14 align-middle"></i> Datos Generales</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Título de referencia</label>
                                        <div class="read-only-field fs-15 fw-bold text-dark" id="lbl-titulo">...</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Departamento de Cargo</label>
                                        <div class="read-only-field text-dark" id="lbl-departamento">...</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Fecha Requerida</label>
                                        <div class="read-only-field text-dark d-flex align-items-center">
                                            <i class="ri-calendar-event-line text-muted me-2"></i> 
                                            <span id="lbl-fecha-requerida">...</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Partidas -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-header bg-soft-primary border-bottom border-light d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 text-dark fw-bold"><i class="ri-shopping-basket-line text-primary me-1"></i> Partidas / Artículos Solicitados</h6>
                            </div>
                            
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-nowrap align-middle mb-0 table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">Descripción</th>
                                                <th width="100" class="text-center text-uppercase text-muted fs-11 fw-bold">Cant.</th>
                                                <th width="120" class="text-end text-uppercase text-muted fs-11 fw-bold">P. Unit</th>
                                                <th width="120" class="text-end text-uppercase text-muted fs-11 fw-bold">Subtotal</th>
                                                <th width="150" class="text-uppercase text-muted fs-11 fw-bold">Notas</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbl-read-partidas">
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <div class="spinner-border text-primary avatar-sm" role="status">
                                                        <span class="visually-hidden">Cargando...</span>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Justificación -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-body p-4">
                                <h5 class="card-title mb-3 text-uppercase fw-bold text-muted fs-12 ls-1">
                                    <i class="ri-chat-1-line text-secondary me-1 fs-14 align-middle"></i> Justificación del Gasto
                                </h5>
                                <div class="bg-light p-3 rounded" style="min-height: 80px;">
                                    <p class="mb-0 text-dark" id="lbl-justificacion" style="white-space: pre-wrap; font-style: italic;">...</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLUMNA DERECHA: Acciones y Resumen -->
                    <div class="col-lg-4">

                        <!-- Tarjeta: Acciones Contextuales -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-header bg-white border-bottom border-light">
                                <h6 class="card-title mb-0 fw-bold">Acciones Disponibles</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2" id="action-buttons-container">
                                    <!-- JS Inyectará los botones aquí (Aprobar, Rechazar, Editar, Volver) -->
                                    <button class="btn btn-light" disabled><i class="ri-loader-line ri-spin"></i> Cargando acciones...</button>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Monto Total -->
                        <div class="card border-0 shadow-lg mb-4 bg-primary" style="border-radius: 10px; background: linear-gradient(135deg, #405189 0%, #0ab39c 100%);">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-white text-uppercase fs-11 fw-bold opacity-75 mb-1">
                                            Monto Estimado Total
                                        </h6>
                                        <h4 class="text-white mb-0 d-flex align-items-center fw-bold" style="font-size: 1.8rem;" id="lbl-total-monto">
                                            $0.00
                                        </h4>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="ri-wallet-3-line text-white fs-24 opacity-50"></i>
                                    </div>
                                </div>
                                <div class="text-white-50 fs-10 mt-2">MXN - Pesos Mexicanos</div>
                            </div>
                        </div>

                        <!-- Tarjeta: Solicitante y Creación -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-body">
                                <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Solicitante</h6>
                                <div class="d-flex align-items-center mb-4">
                                    <div class="me-3">
                                        <div class="avatar-sm">
                                            <span class="avatar-title bg-soft-info text-info rounded-circle fs-4 shadow-sm">
                                                <i class="ri-user-line"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-0 fs-14 fw-bold" id="lbl-solicitante">...</h6>
                                        <p class="text-muted fs-11 mb-0">Usuario del Sistema</p>
                                    </div>
                                </div>
                                
                                <hr class="border-dashed mb-3 mt-0">
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted fs-12 fw-medium"><i class="ri-time-line me-1"></i> Creado el:</span>
                                    <span class="text-dark fw-bold fs-12" id="lbl-fecha-creacion">...</span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>document.write(new Date().getFullYear())</script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">LDR Solutions · MRP</div>
                </div>
            </div>
        </div>
    </footer>
</div>

<?php footerAdmin($data); ?>