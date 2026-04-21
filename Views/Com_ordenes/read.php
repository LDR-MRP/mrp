<?php headerAdmin($data); ?>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-read-oc">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col-12 d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="mb-1 fw-bold text-dark">
                                Orden de Compra #<span id="lbl-idcompra">...</span>
                                <span id="lbl-estatus" class="badge ms-2 fs-12">Cargando...</span>
                            </h4>
                            <p class="text-muted mb-0">Referencia Requisición: <b id="lbl-req-id">#...</b></p>
                        </div>
                        <div id="action-buttons-container" class="d-flex gap-2">
                            <!-- Botones dinámicos -->
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
                        <!-- Tabla de Partidas Reales -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header bg-white">
                                <h6 class="card-title mb-0 fw-bold text-primary">Partidas de la Orden</h6>
                            </div>
                            <div class="card-body p-0">
                                <table class="table table-nowrap align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4">Descripción</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Costo Unit.</th>
                                            <th class="text-end">Desc.</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl-items"></tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-body">
                                <h6 class="fw-bold text-uppercase fs-11 text-muted mb-2">Observaciones de Compra</h6>
                                <p id="lbl-observaciones" class="text-dark mb-0">...</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Datos del Proveedor -->
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-body">
                                <h6 class="text-uppercase fw-bold text-muted fs-11 mb-3">Proveedor</h6>
                                <h5 id="lbl-proveedor" class="fw-bold text-primary">...</h5>
                                <hr class="border-dashed">
                                <p class="mb-1 text-muted fs-12">Almacén de Entrega:</p>
                                <h6 id="lbl-almacen" class="fw-bold">...</h6>
                            </div>
                        </div>

                        <!-- NUEVA TARJETA DE TRAZABILIDAD -->
                        <div class="card border-0 shadow-sm mb-4" id="card-related-pos" style="display:none;">
                            <div class="card-header bg-soft-info border-bottom-0">
                                <h6 class="card-title mb-0 fw-bold fs-12 text-uppercase text-info">
                                    <i class="ri-links-line me-1"></i> OCs de la misma Requisición
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush" id="list-related-pos">
                                    <!-- Se llena por JS -->
                                </div>
                            </div>
                        </div>

                        <!-- Resumen Financiero -->
                        <div class="card border-0 shadow-sm mb-4 bg-dark-ldr">
                            <div class="card-body p-4 text-white">
                                <div class="d-flex justify-content-between mb-2 opacity-75">
                                    <span>Subtotal:</span> <span id="lbl-subtotal">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-3 opacity-75">
                                    <span>IVA:</span> <span id="lbl-iva">$0.00</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">TOTAL:</span>
                                    <h3 class="text-white mb-0 fw-bold" id="lbl-total">$0.00</h3>
                                </div>
                                <div class="mt-2 small opacity-50 text-end" id="lbl-moneda">MXN</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
<?php footerAdmin($data); ?>