<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-recepcion-mercantil">
                
                <!-- 1. BREADCRUMBS (Estilo EMR) -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/inv_recepcion">Recepciones</a></li>
                                    <li class="breadcrumb-item active text-primary">Nueva Entrada</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TÍTULO PRINCIPAL (Avatar Naranja LDR & Copys Alineados) -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="avatar-md me-4">
                                    <span class="avatar-title text-white rounded-circle fs-3 shadow-lg border border-light" style="background-color: #C46623 !important; color: #ffffff !important;">
                                        <i class="ri-inbox-archive-line"></i>
                                    </span>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold ls-1 text-body">Recepcionar Mercancía</h3>
                                    <p class="text-muted mb-0 fs-13">Complete los detalles de la recepción física para dar entrada al almacén.</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="text-uppercase fs-11 fw-bold text-muted d-block mb-1">Proveedor</span>
                                <span class="badge bg-light-subtle text-muted fs-13 px-3 py-2 border border-light-subtle" id="lbl-proveedor-nombre">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formRecepcion" autocomplete="off">
                    <div class="row">
                        
                        <!-- COLUMNA IZQUIERDA: Cotejo y Observaciones -->
                        <div class="col-lg-8">
                            
                            <!-- Tarjeta: Tabla de Cotejo (Misma estructura de Partidas) -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-article-line me-1 fs-14 align-middle"></i> Cotejo de Partidas Pendientes</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="tblCotejo" class="table table-nowrap align-middle mb-0 table-hover">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">Descripción del Bien / SKU</th>
                                                    <th width="120" class="text-center text-uppercase text-muted fs-11 fw-bold">Comprado</th>
                                                    <th width="120" class="text-center text-uppercase text-warning fs-11 fw-bold bg-soft-warning">Saldo Pendiente</th>
                                                    <th width="150" class="text-center text-uppercase text-muted fs-11 fw-bold">Recibir Ahora</th>
                                                    <th width="100" class="text-center text-uppercase text-muted fs-11 fw-bold">Unidad</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbl-items-recepcion" class="border-top-0">
                                                <tr>
                                                    <td colspan="5" class="text-center py-5">
                                                        <div class="spinner-border text-primary" role="status"></div>
                                                        <div class="mt-2 text-muted fs-13">Calculando saldos contra órdenes de compra...</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer border-top-0 py-3">
                                    <small class="text-muted fst-italic"><i class="ri-information-line me-1"></i> Ingrese únicamente las cantidades que está recibiendo físicamente.</small>
                                </div>
                            </div>

                            <!-- Tarjeta: Observaciones (Mismo estilo que Justificación) -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-chat-1-line text-secondary me-1 fs-14 align-middle"></i> Observaciones de Recepción
                                    </h5>
                                    <textarea id="txt-observaciones" name="observaciones" class="form-control bg-light-subtle border-0" rows="3"
                                        placeholder="Indique si hay daños, diferencias o notas de empaque..."></textarea>
                                </div>
                            </div>

                        </div>

                        <!-- COLUMNA DERECHA: Acciones, Datos y Auditoría -->
                        <div class="col-lg-4">

                            <!-- Tarjeta: Acciones Disponibles -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header border-bottom border-light">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title mb-0 fw-bold">Acciones de Almacén</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="btn-registrar-entrada" class="btn btn-success btn-lg shadow-md fw-bold">
                                            <i class="ri-check-double-line align-middle me-1"></i> Registrar Entrada
                                        </button>
                                        <button type="button" class="btn btn-light btn-label" data-redirect="com_orden">
                                            <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta: Información de la Remisión -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Información del Documento</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-0">
                                        <label class="form-label text-uppercase fs-10 fw-bold text-muted mb-1">Número de Remisión / Factura <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text border-end-0 text-muted bg-transparent"><i class="ri-file-paper-2-line"></i></span>
                                            <input type="text" id="txt-remision" name="num_remision" class="form-control border-start-0 ps-0 bg-transparent" placeholder="Ej. REM-98230">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta: Orden de Compra Asociada (Mismo Estilo del Gradiente LDR) -->
                            <div class="card border-0 shadow-lg mb-4 bg-primary" style="border-radius: 10px; background: linear-gradient(135deg, #2E3230 0%, #C46623 100%) !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white text-uppercase fs-11 fw-bold opacity-75 mb-1">
                                                Orden de Compra Asociada
                                            </h6>
                                            <h4 class="text-white mb-0 d-flex align-items-center fw-bold" style="font-size: 1.5rem;" id="lbl-oc-id">
                                                #...
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-shopping-bag-3-line text-white fs-24 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-10">MXN - Pesos Mexicanos</div>
                                </div>
                            </div>

                            <!-- Tarjeta: Auditoría de Origen (Mismo estilo que Solicitante) -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body">
                                    <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Auditoría de Origen</h6>
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4 shadow-sm">
                                                    <i class="ri-user-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fs-14 fw-bold text-body" id="lbl-comprador-nombre">...</h6>
                                            <p class="text-muted fs-11 mb-0">Comprador / Solicitante</p>
                                        </div>
                                    </div>
                                    
                                    <hr class="border-dashed mb-3 mt-0">
                                    
                                    <div class="d-flex justify-content-between align-items-center fs-12">
                                        <span class="text-muted"><i class="ri-time-line me-1"></i> Fecha Emisión OC:</span>
                                        <span class="fw-bold text-body" id="lbl-oc-fecha">...</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR Solutions.
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