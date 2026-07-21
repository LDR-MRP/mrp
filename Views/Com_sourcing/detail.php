<?php headerAdmin($data); ?>
<div class="main-content bg-light">
    <div class="page-content">
        <div class="container-fluid">

            <!-- 1. HERO HEADER INTEGRADO (Graphite & Ghost) -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 4px;">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md flex-shrink-0 me-3">
                                <div class="avatar-title rounded-2 bg-dark-subtle text-muted fs-1 border border-light-subtle shadow-sm">
                                    <i class="ri-exchange-funds-line"></i>
                                </div>
                            </div>
                            <div>
                                <nav aria-label="breadcrumb">
                                    <ol class="breadcrumb breadcrumb-dot mb-1 fs-12 fw-medium">
                                        <li class="breadcrumb-item"><a href="javascript: void(0);" onclick="window.history.back();" class="text-muted">Negociaciones</a></li>
                                        <li class="breadcrumb-item active text-primary" id="lbl-folio-breadcrumb">SOUR-260707-A1B2</li>
                                    </ol>
                                </nav>
                                <h3 class="mb-0 fw-bold text-uppercase ls-1 text-body" id="lbl-event-title">Compra Global de UPS para Servidores</h3>
                                <p class="text-muted mb-0 fs-13 mt-1 fw-medium opacity-75">
                                    <span id="lbl-comprador-name"><i class="ri-user-star-line me-1 text-primary"></i> Erick Pulido</span>
                                    <span class="mx-2 text-light">|</span>
                                    <i class="ri-calendar-line me-1 text-primary"></i> <span id="lbl-event-date">07/07/2026</span>
                                </p>
                            </div>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-2 fs-11" id="lbl-status-header">ABIERTO</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- COLUMNA IZQUIERDA: FICHA Y CAPTURA (33%) -->
                <div class="col-xl-4">
                    <!-- CARD: PARTIDAS -->
                    <div class="card shadow-sm border-0 mb-3" style="border-radius: 3px;">
                        <div class="card-header border-0 bg-light-subtle py-3">
                            <h6 class="card-title mb-0 fw-bold text-uppercase fs-11 text-muted ls-1">
                                <i class="ri-shopping-bag-3-line me-1"></i> Partidas Incluidas
                            </h6>
                        </div>
                        <div class="card-body p-0" id="container-items-list">
                            <!-- Renderizado vía JS -->
                        </div>
                    </div>

                    <!-- CARD: FORMULARIO DE CAPTURA -->
                    <div class="card shadow-sm border-0 sticky-top" style="top: 80px; border-radius: 3px;">
                        <div class="card-header border-0 bg-light-subtle py-3">
                            <h6 class="card-title mb-0 fw-bold text-uppercase fs-11 text-muted ls-1">
                                <i class="ri-add-box-line me-1 text-primary"></i> Registrar Cotización
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="form-add-quote" enctype="multipart/form-data">

                                <!-- 1. Identidad de la Fuente -->
                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted ls-1">Fuente de la Oferta</label>
                                    <select class="form-select border-light shadow-sm" id="sel-source-type">
                                        <option value="REGISTRADO" selected>Proveedor de Catálogo</option>
                                        <option value="PROSPECTO">Nuevo Prospecto (Evaluación)</option>
                                        <option value="RETAIL">Retail / Tienda Online (Spot Buy)</option>
                                    </select>
                                </div>

                                <!-- 2. Switch de Términos (Dinámico) -->
                                <div class="mb-3 p-2 rounded-2 border border-dashed border-info d-none" id="container-spot-buy">
                                    <div class="form-check form-switch">
                                        <!-- Si se elige RETAIL, este checkbox se marcará y bloqueará vía JS -->
                                        <input class="form-check-input" type="checkbox" id="check-pago-inmediato">
                                        <label class="form-check-label text-info fw-bold fs-11 text-uppercase" for="check-pago-inmediato">
                                            <i class="ri-flashlight-line me-1"></i> Pago Inmediato Requerido (Spot Buy)
                                        </label>
                                    </div>
                                </div>

                                
                                <!-- PROVEEDOR -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-0">Proveedor</label>
                                    </div>
                                    <div id="wrapper-select-provider">
                                        <select class="form-select border-light shadow-sm" id="sel-provider" name="id_proveedor">
                                            <option value="">Seleccione proveedor...</option>
                                            <option value="1000000003">COMPUREDES Y SERVICIO</option>
                                            <option value="102">ABASTEO</option>
                                        </select>
                                    </div>
                                    <div id="wrapper-input-prospect" class="d-none">
                                        <input type="text" class="form-control border-light shadow-sm" id="txt-prospect-name" placeholder="Nombre del prospecto o tienda...">
                                    </div>
                                </div>

                                <div class="row g-2 mb-3">
                                    <div class="col-7">
                                        <label class="form-label fs-11 text-uppercase fw-bold text-muted">Precio Unit.</label>
                                        <input type="number" step="0.0001" class="form-control border-light shadow-sm" id="txt-price" name="precio_unitario" placeholder="0.00" required>
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label fs-11 text-uppercase fw-bold text-muted">Moneda</label>
                                        <select class="form-select border-light shadow-sm" id="sel-currency" name="moneda">
                                            <option value="MXN">MXN</option>
                                            <option value="USD">USD</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row g-2 mb-3 align-items-end">
                                    <div class="col-6 d-none" id="group-tc">
                                        <label class="form-label fs-11 text-uppercase fw-bold text-muted">Tipo de Cambio</label>
                                        <input type="number" step="0.0001" class="form-control border-light shadow-sm bg-light-subtle text-primary fw-bold" id="txt-tc" name="tipo_cambio" value="1.0000">
                                    </div>
                                    <div class="col-6">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" id="check-iva-inc" name="iva_inc">
                                            <label class="form-check-label text-muted fs-11 text-uppercase" for="check-iva-inc">IVA Incluido</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted">Cotización PDF (Obligatorio)</label>
                                    <input type="file" class="form-control border-light shadow-sm" id="file-pdf" name="cotizacion_pdf" accept="application/pdf" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted">Foto / Evidencia (Opcional)</label>
                                    <input type="file" class="form-control border-light shadow-sm" id="file-img" accept="image/*">
                                </div>

                                <!-- Campo condicional de URL de Referencia -->
                                <div class="mb-3 d-none" id="container-url-referencia">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-info ls-1">
                                        <i class="ri-link-m me-1"></i> URL de Referencia (Obligatoria)
                                    </label>
                                    <input type="url" class="form-control border-info shadow-sm bg-info-subtle bg-opacity-10" 
                                        id="txt-url-referencia" name="url_referencia" 
                                        placeholder="https://www.amazon.com.mx/dp/...">
                                    <small class="text-muted fs-10">Obligatorio para compras de contado o retail.</small>
                                </div>

                                <button class="btn btn-primary w-100 shadow btn-label waves-effect waves-light" type="button" id="btn-save-quote">
                                    <i class="ri-add-line label-icon align-middle fs-16 me-2"></i> Agregar al Cuadro
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: GRID COMPARATIVO (66%) -->
                <div class="col-xl-8">
                    <div class="card shadow-sm border-0" style="border-radius: 3px;">
                        <div class="card-header border-0 bg-light-subtle d-flex align-items-center py-3">
                            <h6 class="card-title mb-0 flex-grow-1 fw-bold text-uppercase fs-11 text-muted ls-1">
                                <i class="ri-layout-grid-line me-1"></i> Análisis de Propuestas
                            </h6>
                            <div class="flex-shrink-0 text-muted fs-11 text-uppercase fw-medium">
                                Presupuesto Objetivo: 
                                <b class="text-body fs-14" id="lbl-target-price">$0.00</b> 
                                <span class="badge bg-light text-muted border border-light-subtle ms-1">Neto</span> <!-- Etiqueta de claridad -->
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3" id="container-comparison-grid">
                                <!-- Renderizado vía JS: Cards de Proveedores -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    
    <!-- MODAL: ENRIQUECIMIENTO TÉCNICO (PROMOVER A SKU) -->
    <div class="modal fade" id="modalPromoteSku" tabindex="-1" aria-labelledby="modalPromoteSkuLabel" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <!-- Header con ADN Graphite -->
                <div class="modal-header bg-dark-subtle p-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-xs me-2">
                            <div class="avatar-title bg-transparent text-muted fs-16">
                                <i class="ri-rocket-2-line"></i>
                            </div>
                        </div>
                        <h5 class="modal-title text-uppercase fw-bold ls-1 fs-13 text-body" id="modalPromoteSkuLabel">Catalogación Maestra de Artículo</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-4 bg-light-subtle">
                    <!-- Resumen de Adjudicación (Contexto) -->
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="p-3 border border-dashed border-light rounded-2 bg-body shadow-sm">
                                <div class="row align-items-center">
                                    <div class="col-md-5">
                                        <small class="text-muted text-uppercase fw-bold fs-10 ls-1 d-block mb-1">Artículo Negociado</small>
                                        <h6 class="mb-0 fw-bold text-primary text-truncate" id="mdl-item-name">---</h6>
                                    </div>
                                    <div class="col-md-3 text-md-center mt-2 mt-md-0 border-start border-light">
                                        <small class="text-muted text-uppercase fw-bold fs-10 ls-1 d-block mb-1">Proveedor Ganador</small>
                                        <h6 class="mb-0 fw-bold text-body text-truncate" id="mdl-provider-name">---</h6>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-2 mt-md-0 border-start border-light">
                                        <small class="text-muted text-uppercase fw-bold fs-10 ls-1 d-block mb-1">Costo de Adjudicación</small>
                                        <h5 class="mb-0 fw-bold text-success" id="mdl-winner-price">$0.00</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulario de Enriquecimiento -->
                    <form id="form-promote-sku">
                        <input type="hidden" id="mdl-id-req-art">
                        <input type="hidden" id="mdl-id-cotizacion">

                        <div class="row g-3">
                            <!-- Columna 1 -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted ls-1">Clave de Artículo (SKU) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control border-light shadow-sm" id="mdl-txt-sku" placeholder="Ej: LLAN-RAD-001" required>
                                    <small class="text-muted fs-10">Use la nomenclatura oficial de almacén.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted ls-1">Línea de Producto <span class="text-danger">*</span></label>
                                    <select class="form-select border-light shadow-sm" id="mdl-sel-line" required>
                                        <option value="">Seleccione una línea...</option>
                                        <!-- Dinámico desde Catálogos -->
                                    </select>
                                </div>
                            </div>

                            <!-- Columna 2 -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted ls-1">Tipo de Elemento</label>
                                    <select class="form-select border-light shadow-sm bg-light-subtle" id="mdl-sel-type">
                                        <option value="P">Producto (Stockeable)</option>
                                        <option value="H">Insumo / Consumo</option>
                                        <option value="S">Servicio (Gasto)</option>
                                        <option value="K">Kit / Combo</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted ls-1">Unidad de Medida (UOM) <span class="text-danger">*</span></label>
                                    <select class="form-select border-light shadow-sm" id="mdl-sel-uom" required>
                                        <option value="PIEZA">PIEZA</option>
                                        <option value="SERVICIO">SERVICIO</option>
                                        <option value="KILO">KILOGRAMO</option>
                                        <option value="METRO">METRO LINEAL</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mb-0">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted ls-1">Descripción Final para Catálogo</label>
                                    <textarea class="form-control border-light shadow-sm" rows="2" id="mdl-txt-desc-final" placeholder="Ajuste la descripción si es necesario..."></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="modal-footer bg-light border-top-0">
                    <button type="button" class="btn btn-ghost-secondary btn-sm fw-medium" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success btn-label waves-effect waves-light shadow" id="btn-execute-promotion">
                        <i class="ri-check-double-line label-icon align-middle fs-16 me-2"></i> Catalogar y Finalizar Sourcing
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php footerAdmin($data); ?>