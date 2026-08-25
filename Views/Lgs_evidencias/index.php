<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-evidencias">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Evidencias y Cierre</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-camera-lens-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Evidencias y Cierre de Entrega</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Carga multimedia de recepción/llegada y confirmación de entrega final en destino.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Rutas en Tránsito</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEvidTransito">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">En curso</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Archivos Adjuntos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEvidTotalArchivos">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Evidencias cargadas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-camera-lens-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Entregas Cerradas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEvidEntregadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Finalizadas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-checkbox-circle-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Cobertura Evidencias</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2" id="cardEvidCobertura">0%</h4>
                                        <span class="badge bg-soft-secondary text-secondary fw-medium mb-0 px-2 py-1">Completes</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fs-3">
                                            <i class="ri-shield-check-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. DATATABLE CARD ESTILIZADA -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tableEvidencias" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Envío</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Total VINs</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Evidencias Adjuntas</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Evidencias respaldadas y sincronizadas
                            </small>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- MODAL EVIDENCIAS Y CIERRE DE ENTREGA -->
<div class="modal fade" id="modalEvidencias" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <div>
                    <h5 class="modal-title fw-bold text-primary mb-0" id="titleModalEvidencia">
                        <i class="ri-checkbox-circle-line me-1 text-success"></i> Recepción y Evidencias de Entrega en Destino <span id="lblFolioEvidencia" class="badge bg-primary text-white fs-13 ms-2"></span>
                    </h5>
                    <small class="text-muted">Inspección física en destino, fotografías de recepción y cierre final de viaje</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- FORMULARIO DE INSPECCIÓN DE ENTREGA EN DESTINO -->
                <div class="card border border-success-subtle rounded-4 p-3 mb-4" style="background: #F0FDF4;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold fs-14 text-dark mb-0">
                            <i class="ri-camera-lens-fill me-1 text-success"></i> Capturar Inspección de Entrega en Destino
                        </h6>
                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 fs-11">
                            <i class="ri-shield-check-line me-1"></i> Recepción en Destino
                        </span>
                    </div>

                    <form id="formEvidenciaEntrega" enctype="multipart/form-data">
                        <input type="hidden" id="id_envio_evidencia" name="id_envio" value="">
                        <input type="hidden" name="tipo_checklist" value="entrega_destino">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase">Seleccionar Unidad / VIN a Entregar <span class="text-danger">*</span></label>
                                <select class="form-select fs-13 fw-semibold border-success" id="select_evid_unidad" name="id_unidad" onchange="actualizarVinSeleccionado(this);" required>
                                    <option value="">-- Seleccionar Unidad --</option>
                                </select>
                                <input type="hidden" id="vin_confirmado_entrega" name="vin" value="">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase">Notas u Observaciones de Recepción</label>
                                <input type="text" class="form-control fs-13" id="chk_comentarios_entrega" name="comentarios" placeholder="Condiciones de llegada, firma de recibo, etc.">
                            </div>
                        </div>

                        <!-- 5 FOTOS OBLIGATORIAS DE ENTREGA -->
                        <div class="mb-3">
                            <span class="fs-12 fw-bold text-dark d-block mb-2"><i class="ri-image-line me-1 text-success"></i> Fotografías de Inspección de Entrega (5 Puntos):</span>
                            
                            <style>
                            .photo-preview-dest {
                                width: 100%; height: 105px; border-radius: 12px; border: 2px dashed #CBD5E1; background: #FFFFFF; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; transition: all 0.2s ease;
                            }
                            .photo-preview-dest:hover { border-color: #10B981; background: rgba(16, 185, 129, 0.04); }
                            .photo-preview-dest img { width: 100%; height: 100%; object-fit: cover; }
                            </style>

                            <div class="row g-3 mb-2">
                                <div class="col-md-4 col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">1. Frente</span>
                                    <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_dest_frente')">
                                        <img id="img_dest_frente" class="d-none">
                                        <i id="ico_dest_frente" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_dest_frente" name="frente" accept="image/*" class="d-none" onchange="previewImageDest(this, 'frente')">
                                </div>
                                
                                <div class="col-md-4 col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">2. Atrás</span>
                                    <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_dest_atras')">
                                        <img id="img_dest_atras" class="d-none">
                                        <i id="ico_dest_atras" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_dest_atras" name="atras" accept="image/*" class="d-none" onchange="previewImageDest(this, 'atras')">
                                </div>
                                
                                <div class="col-md-4 col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">3. Lateral Izquierdo</span>
                                    <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_dest_lateral_izq')">
                                        <img id="img_dest_lateral_izq" class="d-none">
                                        <i id="ico_dest_lateral_izq" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_dest_lateral_izq" name="lateral_izq" accept="image/*" class="d-none" onchange="previewImageDest(this, 'lateral_izq')">
                                </div>
                                
                                <div class="col-md-4 col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">4. Lateral Derecho</span>
                                    <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_dest_lateral_der')">
                                        <img id="img_dest_lateral_der" class="d-none">
                                        <i id="ico_dest_lateral_der" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_dest_lateral_der" name="lateral_der" accept="image/*" class="d-none" onchange="previewImageDest(this, 'lateral_der')">
                                </div>

                                <div class="col-md-4 col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">5. Odómetro / Km Llegada</span>
                                    <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_dest_odometro')">
                                        <img id="img_dest_odometro" class="d-none">
                                        <i id="ico_dest_odometro" class="ri-dashboard-3-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_dest_odometro" name="odometro" accept="image/*" class="d-none" onchange="previewImageDest(this, 'odometro')">
                                </div>
                            </div>

                            <!-- Extras dinámicos de entrega -->
                            <div id="contenedor_extras_entrega" class="row g-3 mb-2"></div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill border-dashed fw-semibold" onclick="agregarExtraEntrega();">
                                    <i class="ri-add-circle-line me-1"></i> + Foto Adicional / Remisión Firmada
                                </button>
                                
                                <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold fs-13 shadow-sm" onclick="guardarInspeccionEntrega();">
                                    <i class="ri-checkbox-circle-line me-1"></i> Guardar Evidencia de Entrega
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- TABLA DE EVIDENCIAS EXISTENTES -->
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h6 class="fw-bold fs-13 text-dark mb-0"><i class="ri-gallery-line me-1 text-primary"></i> Evidencias e Inspecciones Registradas</h6>
                    <span class="badge bg-light text-secondary border fs-11" id="badgeTotalEvidenciasModal">0 Registros</span>
                </div>
                <div class="table-responsive border rounded-3 mb-4 bg-white">
                    <table class="table table-hover align-middle mb-0 fs-13">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 150px;">Momento</th>
                                <th style="width: 180px;">Unidad / VIN</th>
                                <th>Archivo / Vista Previa</th>
                                <th>Observaciones</th>
                                <th style="width: 150px;">Fecha</th>
                                <th class="text-center" style="width: 80px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="bodyListaEvidencias">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>

                <!-- CIERRE DE ENTREGA FINAL (SI ESTÁ EN TRÁNSITO) -->
                <div id="cardCierreDestino" class="card border border-success bg-soft-success p-3 rounded-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h6 class="fw-bold text-success mb-1"><i class="ri-checkbox-circle-line me-1"></i> Cierre Definitivo de Entrega</h6>
                            <small class="text-muted">Marcar el envío como completado y finalizar el monitoreo del viaje.</small>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="datetime-local" class="form-control form-control-sm fw-bold text-success" id="fecha_llegada_real">
                            <button type="button" class="btn btn-sm btn-success px-4 rounded-pill fw-bold text-white shadow-sm" onclick="confirmarCierreFinal();">
                                <i class="ri-check-double-line me-1"></i> Finalizar Envío
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-2">
                <button type="button" class="btn btn-light border px-4 rounded-pill fw-semibold" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER EVIDENCIAS / INSPECCIÓN COMPLETA -->
<div class="modal fade" id="modalVerEvidencias" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm bg-primary text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm">
                        <i class="ri-camera-lens-fill fs-20"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="titleModalVerEvidencias">Inspección de Unidad</h5>
                        <small class="text-muted" id="subModalVerEvidencias">Evidencias fotográficas y observaciones registradas</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="bodyModalVerEvidencias" style="background: #F8FAFC;">
                <div id="contenedorObservacionesEvidencias" class="mb-3"></div>
                <div id="gridVerEvidencias">
                    <!-- Las evidencias se cargarán aquí -->
                </div>
            </div>
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-end">
                <button type="button" class="btn btn-secondary rounded-pill fw-semibold px-4" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
