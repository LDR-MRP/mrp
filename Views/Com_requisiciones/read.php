<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-read-requisicion">
                <!-- Breadcrumb -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/com_requisicion">Requisiciones</a></li>
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
                                    <h4 class="mb-1 fw-bold ls-05 d-flex align-items-center">
                                        Solicitud de Compra #<span id="lbl-idrequisicion" class="ms-1">...</span>
                                        <span id="lbl-estatus" class="ms-3 badge bg-light text-muted fs-12 fw-normal">Cargando...</span>
                                    </h4>
                                    <p class="text-muted mb-0 fs-13">Expediente de requisición de solo lectura.</p>
                                </div>
                            </div>
                            
                            <div class="text-end">
                                <span class="text-uppercase fs-11 fw-bold text-muted d-block mb-1">Prioridad</span>
                                <span id="lbl-prioridad" class="badge bg-light fs-12 px-3 py-1 shadow-sm">...</span>
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
                                <h6 class="card-title mb-0 fw-bold"><i class="ri-article-line me-1 fs-14 align-middle"></i> Datos Generales</h6>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-4">
                                    <div class="col-12">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Título de referencia</label>
                                        <div class="read-only-field fs-15 fw-bold" id="lbl-titulo">...</div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Departamento de Cargo</label>
                                        <div class="read-only-field" id="lbl-departamento">...</div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Fecha Requerida</label>
                                        <div class="read-only-field d-flex align-items-center">
                                            <i class="ri-calendar-event-line text-muted me-2"></i> 
                                            <span id="lbl-fecha-requerida">...</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="row mt-3 d-none" id="section-direct-info">
                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Método de Pago</label>
                                        <div class="read-only-field" id="lbl-pago-sugerido">...</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Referencia Digital</label>
                                        <div class="read-only-field">
                                            <i class="ri-external-link-line text-primary me-2"></i>
                                            <a href="#" id="link-referencia" target="_blank" class="fw-medium">Ver producto</a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tarjeta: Partidas -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-header bg-soft-primary border-bottom border-light d-flex justify-content-between align-items-center">
                                <h6 class="card-title mb-0 fw-bold"><i class="ri-shopping-basket-line me-1"></i> Partidas / Artículos Solicitados</h6>
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
                                    <p class="mb-0" id="lbl-justificacion" style="white-space: pre-wrap; font-style: italic;">...</p>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- COLUMNA DERECHA: Acciones y Resumen -->
                    <div class="col-lg-4">

                        <!-- Tarjeta: Acciones Contextuales -->
                        <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                            <div class="card-header border-bottom border-light">
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
                                    <span class="fw-bold fs-12" id="lbl-fecha-creacion">...</span>
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

    <div class="modal fade" id="modalSourcing" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-fullscreen modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-soft-primary p-3">
                    <h5 class="modal-title text-primary fw-bold fs-15"><i class="ri-scales-3-line me-2"></i>Cuadro Comparativo de Sourcing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <!-- Resumen de Meta (Directiva Tito) -->
                    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1 fw-bold text-dark" id="sourcing-item-name">Cargando artículo...</h6>
                            <p class="mb-0 text-muted fs-12" id="sourcing-item-specs">Ficha técnica del requerimiento.</p>
                        </div>
                        <div class="text-end">
                            <span class="text-uppercase fs-10 fw-bold text-muted d-block">Precio Objetivo</span>
                            <h4 class="mb-0 fw-bold text-primary" id="sourcing-target-price">$0.00</h4>
                        </div>
                    </div>

                    <div class="row g-0">
                        <!-- Izquierda: Formulario de Nueva Cotización -->
                        <div class="col-lg-4 border-end p-4">
                            <h6 class="fw-bold mb-3 text-uppercase fs-11 ls-1">Registrar Cotización</h6>
                            <form id="formNuevaCotizacion" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-bold">Proveedor Potencial</label>
                                    <select name="id_proveedor" class="form-select form-select-sm" required></select>
                                </div>
                                <!-- Fila de Precio y Moneda actualizada -->
                                <div class="row g-2 mb-3">
                                    <div class="col-4">
                                        <label class="form-label fs-11 fw-bold">Precio Unit.</label>
                                        <input type="number" name="precio_unitario" class="form-control form-control-sm" step="0.01" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fs-11 fw-bold">Moneda</label>
                                        <select name="moneda" id="sel-moneda-cotizacion" class="form-select form-select-sm" required>
                                            <!-- Dinámico -->
                                        </select>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label fs-11 fw-bold">T. Cambio</label>
                                        <input type="number" name="tipo_cambio" id="txt-tc-cotizacion" class="form-control form-control-sm bg-light" value="1.000000" step="0.000001" readonly>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-bold text-uppercase">Evidencia (PDF de Cotización) <span class="text-danger">*</span></label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="ri-file-pdf-line"></i></span>
                                        <input type="file" name="cotizacion_pdf" class="form-control" accept=".pdf" required>
                                    </div>
                                </div>
                                <!-- NEW FIELD: Product Photo -->
                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-bold text-uppercase">Fotografía del Producto / Referencia</label>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light"><i class="ri-image-add-line"></i></span>
                                        <input type="file" name="foto_producto" class="form-control" accept="image/*">
                                    </div>
                                    <small class="text-muted fs-10">Opcional. Formatos permitidos: JPG, PNG.</small>
                                </div>
                                <!-- NEW FIELD: Particular Specs -->
                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-bold text-uppercase">Especificaciones Particulares del Proveedor <span class="text-danger">*</span></label>
                                    <textarea name="specs_particulares_proveedor" class="form-control fs-12 bg-light-subtle" rows="3" 
                                            placeholder="Describa aquí si el proveedor ofrece una alternativa o cambios técnicos..." required></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fs-11 fw-bold text-uppercase">Notas Internas (Comprador)</label>
                                    <textarea name="comentarios_comprador" class="form-control fs-12" rows="2" 
                                            placeholder="Notas para el equipo de finanzas..."></textarea>
                                </div>

                                <button type="submit" class="btn btn-primary btn-sm w-100 shadow-sm fw-bold">
                                    <i class="ri-add-line align-middle"></i> Agregar al Cuadro
                                </button>
                            </form>
                        </div>

                        <!-- Derecha: Tabla Comparativa -->
                        <div class="col-lg-8 p-4 bg-soft-light">
                            <h6 class="fw-bold mb-3 text-uppercase fs-11 ls-1">Análisis de Propuestas</h6>
                            <div class="table-responsive">
                                <!-- Tabla Comparativa con columna T.C. -->
                                <table class="table table-nowrap align-middle mb-0" id="tblComparativa">
                                    <thead class="bg-white">
                                        <tr>
                                            <th>Proveedor / Compliance</th>
                                            <th class="text-center">T.C.</th> <!-- NUEVA COLUMNA -->
                                            <th class="text-end">Precio MXN</th>
                                            <th class="text-center">Ahorro / Déficit</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalPromoverCatalog" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                <div class="modal-header bg-soft-success p-3">
                    <h5 class="modal-title text-success fw-bold fs-15"><i class="ri-price-tag-3-line me-2"></i>Alta en Catálogo Maestro</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formPromoverCatalog">
                        <input type="hidden" name="idrequisicionarticulo">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fs-11 fw-bold text-uppercase">SKU / Clave Oficial <span class="text-danger">*</span></label>
                                <input type="text" name="cve_articulo" class="form-control fw-bold text-primary" placeholder="Ej: LLAN-RAD-001" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fs-11 fw-bold text-uppercase">Línea de Producto <span class="text-danger">*</span></label>
                                <select name="lineaproductoid" class="form-select" required>
                                    <!-- Llenar con catálogo -->
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-bold text-uppercase">Tipo</label>
                                <select name="tipo_elemento" class="form-select">
                                    <option value="P">Producto</option>
                                    <option value="S">Servicio</option>
                                    <option value="H">Herramienta</option>
                                    <option value="C">Componente</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fs-11 fw-bold text-uppercase">Unidad</label>
                                <input type="text" name="unidad_salida" class="form-control" value="PIEZA" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light-subtle">
                    <button type="button" id="btn-ejecutar-promocion" class="btn btn-success w-100 shadow-sm fw-bold">
                        <i class="ri-check-line align-middle me-1"></i> Crear SKU y Vincular
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>