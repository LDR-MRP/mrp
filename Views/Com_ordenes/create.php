<?php headerAdmin($data); ?>

<div class="main-content bg-light">
    <div class="page-content">
        <div class="container-fluid">
            <section id="id-create-oc">
                <!-- HEADER FUSIONADO (Regreso al Dashboard + Contexto) -->
                <div class="card shadow-sm border-0 mb-4" style="border-radius: 4px;">
                    <div class="card-body p-3">
                        <div class="d-flex align-items-center">

                            <!-- SECCIÓN IZQUIERDA: IDENTIDAD Y NAVEGACIÓN -->
                            <div class="col-md-7">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-md flex-shrink-0 me-3">
                                        <div class="avatar-title rounded-2 bg-dark-subtle text-muted fs-1 border border-light-subtle shadow-sm">
                                            <i class="ri-file-list-3-line"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <nav aria-label="breadcrumb">
                                            <ol class="breadcrumb breadcrumb-dot mb-1 fs-12 fw-medium">
                                                <li class="breadcrumb-item"><a href="javascript: void(0);" onclick="window.history.back();" class="text-muted">Requisición</a></li>
                                                <li class="breadcrumb-item active text-primary">Generar Órden</li>
                                            </ol>
                                        </nav>
                                        <h3 class="mb-0 fw-bold text-uppercase ls-1 text-body">
                                            Generar Orden de Compra
                                        </h3>
                                        <p class="text-muted mb-0 fs-13 mt-1 fw-medium opacity-75">Basada en la Requisición Aprobada <b id="lbl-req-id" class="text-primary">#...</b></p>
                                    </div>
                                </div>
                            </div>

                            <!-- SECCIÓN DERECHA: INDICADORES DE ESTADO (KPI STACK) -->
                            <div class="col-md-5 mt-3 mt-md-0">
                                <div class="d-flex justify-content-md-end align-items-center">  

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formOrdenCompra" autocomplete="off">
                    <div class="row">
                        <!-- COLUMNA IZQUIERDA: Partidas y Observaciones -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-lg mb-4">
                                <div class="card-header bg-soft-primary border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-list-check-2 text-primary me-1 fs-14 align-middle"></i> Partidas a Comprar</h6>
                                    <span class="badge bg-primary fs-11" id="lbl-req-title">Cargando...</span>
                                </div>

                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="tblPartidasOC" class="table table-nowrap align-middle mb-0 table-hover">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th></th>
                                                    <th class="ps-4">Artículo</th>
                                                    <th width="50" class="text-center" title="Saldo Pendiente por Comprar">Pendiente</th>
                                                    <th width="50" class="text-center">Cant. a Comprar</th>
                                                    <th width="50" class="text-end">Costo Unit. Real</th>
                                                    <th width="50" class="text-end">Descuento %</th>
                                                    <th width="50" class="text-end">Descuento $</th>
                                                    <th width="50" class="text-end">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr><td colspan="7" class="text-center py-5"><i class="ri-loader-4-line ri-spin fs-1 text-primary"></i><br>Calculando saldos pendientes...</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer border-top-0 py-3">
                                    <small class="text-muted fst-italic"><i class="ri-information-line me-1"></i> Puede ajustar las cantidades a comprar y los precios negociados finales. Elimine las filas que no comprará a este proveedor.</small>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-chat-1-line text-secondary me-1 fs-14 align-middle"></i> Observaciones / Condiciones Comerciales
                                    </h5>
                                    <textarea name="observaciones" class="form-control bg-light border-0" rows="3" placeholder="Ej. Entregar en puerta 3, pago a 30 días..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Proveedor, Totales y Acciones -->
                        <div class="col-lg-4">
                            <div class="card border-0 shadow-lg mb-4">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Datos Comerciales</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Proveedor <span class="text-danger">*</span></label>
                                        <select name="proveedorid" class="form-select border-primary">
                                            <option value="">Seleccione Proveedor...</option>
                                            <!-- Llenar por JS -->
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Almacén Destino <span class="text-danger">*</span></label>
                                        <select name="almacenid" class="form-select">
                                            <option value="">Seleccione Almacén...</option>
                                            <!-- Llenar por JS -->
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Moneda</label>
                                            <select name="moneda" class="form-select bg-light">
                                                <option value="MXN" selected>MXN</option>
                                                <option value="USD">USD</option>
                                                <option value="EUR">EUR</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Cambio</label>
                                            <input type="number" name="tipo_cambio" class="form-control bg-light" value="1.000000" step="0.000001">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Botonera -->
                            <div class="card border-0 shadow-lg mb-4">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Acciones Disponibles</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="btn-generar-oc" class="btn btn-primary btn-lg shadow-sm">
                                            <i class="ri-shopping-cart-2-fill"></i> Generar Orden de Compra
                                        </button>
                                        <button type="button" class="btn btn-light btn-label" data-redirect="com_requisicion">
                                            <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Tarjeta de Totales Financieros -->
                            <div class="card border-0 shadow-lg mb-4 bg-primary" style="border-radius: 10px; background: linear-gradient(135deg, #405189 0%, #0ab39c 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span class="text-white-50">Subtotal:</span>
                                        <span class="text-white fw-medium" id="lbl-subtotal">$0.00</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-3">
                                        <span class="text-white-50">IVA (16%):</span>
                                        <span class="text-white fw-medium" id="lbl-iva">$0.00</span>
                                    </div>
                                    <hr class="border-secondary mt-0 mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-white text-uppercase fs-12 fw-bold">Total OC:</span>
                                        <h3 class="text-white mb-0 fw-bold" id="lbl-total">$0.00</h3>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
    <?php footerAdmin($data); ?>
</div>