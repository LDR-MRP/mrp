<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <!-- Estilos heredados de tu UI -->
            <style>
                :root { --primary: #0056b3; --success: #28a745; --warning: #ffc107; --danger: #dc3545; }
                body { background-color: #f4f7f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
                .card { border: none; border-radius: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.04); margin-bottom: 2rem; }
                .card-header { background: #fff; border-bottom: 1px solid #f1f1f1; font-weight: bold; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
                .table thead th { border-top: none; font-size: 0.75rem; color: #718096; text-transform: uppercase; }
                .bg-soft-primary { background-color: #e0e7ff; }
            </style>

            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/com_ordenes">Órdenes de Compra</a></li>
                                <li class="breadcrumb-item active">Generar OC</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">
                            <span class="avatar-title bg-primary text-white rounded-circle fs-3 shadow-lg">
                                <i class="ri-shopping-cart-2-line"></i>
                            </span>
                        </div>
                        <div>
                            <h4 class="mb-1 text-dark fw-bold ls-05">Generar Orden de Compra</h4>
                            <p class="text-muted mb-0 fs-13">Basada en la Requisición Aprobada <b id="lbl-req-id" class="text-primary">#...</b></p>
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
                                <h6 class="card-title mb-0 text-dark fw-bold"><i class="ri-list-check-2 text-primary me-1 fs-14 align-middle"></i> Partidas a Comprar</h6>
                                <span class="badge bg-primary fs-11" id="lbl-req-title">Cargando...</span>
                            </div>

                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="tblPartidasOC" class="table table-nowrap align-middle mb-0 table-hover">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="ps-4">Artículo</th>
                                                <th width="120" class="text-center" title="Saldo Pendiente por Comprar">Pendiente</th>
                                                <th width="120" class="text-center">Cant. a Comprar</th>
                                                <th width="140" class="text-end">Costo Unit. Real</th>
                                                <th width="120" class="text-end">Descuento</th>
                                                <th width="120" class="text-end">Subtotal</th>
                                                <th width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr><td colspan="7" class="text-center py-5"><i class="ri-loader-4-line ri-spin fs-1 text-primary"></i><br>Calculando saldos pendientes...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 py-3">
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
                            <div class="card-header bg-white border-bottom border-light">
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

                        <!-- Tarjeta de Totales Financieros -->
                        <div class="card border-0 shadow-lg mb-4 bg-dark-ldr">
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

                        <!-- Botonera -->
                        <div class="card border-0 shadow-lg mb-4">
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <button type="button" id="btn-generar-oc" class="btn btn-primary btn-lg shadow-sm">
                                        <i class="ri-file-check-line align-middle me-1"></i> Generar Orden de Compra
                                    </button>
                                    <button type="button" class="btn btn-light" data-redirect="com_requisicion">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php footerAdmin($data); ?>
</div>