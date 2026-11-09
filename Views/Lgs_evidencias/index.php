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

                <!-- 3. BLOQUE DE KPIS HOMOLOGADOS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3 h-100" style="cursor: pointer;" onclick="filtrarEvidenciasPorCard('En Tránsito', 'chip-evid-transito');" title="Filtrar viajes en tránsito">
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
                        <div class="card card-animate border-0 shadow-sm rounded-3 h-100">
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
                        <div class="card card-animate border-0 shadow-sm rounded-3 h-100" style="cursor: pointer;" onclick="filtrarEvidenciasPorCard('Entregado', 'chip-evid-entregado');" title="Filtrar viajes entregados">
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
                        <div class="card card-animate border-0 shadow-sm rounded-3 h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Cobertura Evidencias</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2" id="cardEvidCobertura">0%</h4>
                                        <span class="badge bg-soft-secondary text-secondary fw-medium mb-0 px-2 py-1">Completitud</span>
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

                <!-- 4. DATATABLE CARD ESTILIZADA CON CHIPS DE FILTRO -->
                <div class="card border-0 shadow-xl rounded-3 overflow-hidden">
                    <div class="bg-primary" style="height: 4px;"></div>
                    <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                            <!-- Buscador -->
                            <div class="d-flex align-items-center w-100" style="max-width: 360px;">
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                                    <input type="text" id="buscarEvidenciasInput" class="form-control bg-light border-start-0" placeholder="Buscar folio, trasladista..." oninput="filtrarTablaEvidencias(this.value);">
                                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="document.getElementById('buscarEvidenciasInput').value=''; filtrarTablaEvidencias('');" title="Limpiar"><i class="ri-close-line"></i></button>
                                </div>
                            </div>

                            <!-- Chips de Filtro Rápido -->
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="fs-11 text-muted text-uppercase fw-bold me-1 d-none d-xl-inline"><i class="ri-filter-3-line me-1"></i>Filtro:</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fs-12 active chip-evid-btn" id="chip-evid-todos" onclick="filtrarEvidenciasPorChip('', this);">
                                    📌 Todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fs-12 chip-evid-btn" id="chip-evid-transito" onclick="filtrarEvidenciasPorChip('En Tránsito', this);">
                                    🚚 En Tránsito
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fs-12 chip-evid-btn" id="chip-evid-entregado" onclick="filtrarEvidenciasPorChip('Entregado', this);">
                                    ✅ Entregados
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tableEvidencias" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Envío</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">VINs / Unidades</th>
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
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <div>
                        <h5 class="modal-title fw-bold text-primary mb-0" id="titleModalEvidencia">
                            <i id="icoModalHeader" class="ri-checkbox-circle-line me-1 text-success"></i> <span id="txtModalHeaderTitulo">Recepción y Evidencias de Entrega en Destino</span> <span id="lblFolioEvidencia" class="badge bg-primary text-white fs-13 ms-2"></span>
                        </h5>
                        <small class="text-muted" id="subModalHeaderDesc">Inspección física en destino, fotografías de recepción y cierre final de viaje</small>
                    </div>
                </div>
                <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- FORMULARIO DE INSPECCIÓN (ADAPTABLE A PLANTA O DESTINO) -->
                <div class="card border rounded-4 p-3 mb-4" id="cardFormularioInspeccion" style="background: #F0FDF4; border-color: #BBF7D0 !important;">
                    <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                        <h6 class="fw-bold fs-14 text-dark mb-0" id="titleFormularioInspeccion">
                            <i id="icoFormulario" class="ri-camera-lens-fill me-1 text-success"></i> <span id="txtTituloFormulario">Capturar Inspección de Entrega en Destino</span>
                        </h6>
                        <span class="badge px-2 py-1 fs-11" id="badgeFormularioInspeccion" style="background: #DCFCE7; color: #15803D; border: 1px solid #86EFAC;">
                            <i class="ri-shield-check-line me-1"></i> <span id="txtBadgeFormulario">Recepción en Destino</span>
                        </span>
                    </div>

                    <form id="formEvidenciaEntrega" enctype="multipart/form-data">
                        <input type="hidden" id="id_envio_evidencia" name="id_envio" value="">
                        <input type="hidden" id="tipo_checklist_input" name="tipo_checklist" value="entrega_destino">
                        <input type="hidden" id="modo_evidencia_actual" value="destino">
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase" id="labelSelectUnidad">Seleccionar Unidad / VIN a Entregar <span class="text-danger">*</span></label>
                                <select class="form-select fs-13 fw-semibold border-success" id="select_evid_unidad" name="id_unidad" onchange="actualizarVinSeleccionado(this);" required>
                                    <option value="">-- Seleccionar Unidad --</option>
                                </select>
                                <input type="hidden" id="vin_confirmado_entrega" name="vin" value="">
                            </div>
                            <div class="col-md-4" id="divSubtipoEntrega">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase" id="labelSubtipoEntrega">Tipo de Receptor</label>
                                <select class="form-select fs-13" id="subtipo_entrega_destino" name="subtipo_receptor">
                                    <option value="DISTRIBUIDOR">Distribuidor Autorizado / Concesionario</option>
                                    <option value="CLIENTE_FINAL">Cliente Final / Particular</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-muted fs-11 text-uppercase" id="labelComentariosInspeccion">Notas u Observaciones</label>
                                <input type="text" class="form-control fs-13" id="chk_comentarios_entrega" name="comentarios" placeholder="Fallas, condición física, firma...">
                            </div>
                        </div>

                        <!-- FOTOGRAFÍAS DE INSPECCIÓN: INDIVIDUAL vs ZIP -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="fs-12 fw-bold text-dark d-block" id="lblTituloFotos"><i class="ri-image-line me-1 text-success"></i> Fotografías de Inspección:</span>
                            </div>

                            <!-- PESTAÑAS: FOTOS INDIVIDUALES vs CARGA MASIVA (ZIP) -->
                            <ul class="nav nav-pills nav-custom nav-custom-light mb-3" id="pills-evidencias-modal" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active rounded-pill px-3 py-1 fs-12 fw-semibold" id="tab-fotos-ind-evid" data-bs-toggle="pill" data-bs-target="#pane-fotos-ind-evid" type="button" role="tab">
                                        <i class="ri-camera-line me-1"></i> Fotos Individuales (5 Puntos)
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link rounded-pill px-3 py-1 fs-12 fw-semibold text-warning-emphasis bg-soft-warning border border-warning-subtle" id="tab-fotos-zip-evid" data-bs-toggle="pill" data-bs-target="#pane-fotos-zip-evid" type="button" role="tab">
                                        <i class="ri-folder-zip-fill me-1 text-warning"></i> Carga Masiva (ZIP)
                                    </button>
                                </li>
                            </ul>
                            
                            <style>
                            .photo-preview-dest {
                                width: 100%; height: 105px; border-radius: 12px; border: 2px dashed #CBD5E1; background: #FFFFFF; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden; transition: all 0.2s ease;
                            }
                            .photo-preview-dest:hover { border-color: #10B981; background: rgba(16, 185, 129, 0.04); }
                            .photo-preview-dest img { width: 100%; height: 100%; object-fit: cover; }
                            </style>

                            <div class="tab-content" id="pills-evidencias-content">
                                <!-- PESTAÑA 1: FOTOS INDIVIDUALES -->
                                <div class="tab-pane fade show active" id="pane-fotos-ind-evid" role="tabpanel">
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
                                            <span class="fs-11 text-muted fw-bold d-block mb-1" id="lblFotoOdometro">5. Odómetro / Km</span>
                                            <div class="photo-preview-dest text-muted" onclick="triggerFileDest('file_dest_odometro')">
                                                <img id="img_dest_odometro" class="d-none">
                                                <i id="ico_dest_odometro" class="ri-dashboard-3-line fs-2 text-muted opacity-50"></i>
                                            </div>
                                            <input type="file" id="file_dest_odometro" name="odometro" accept="image/*" class="d-none" onchange="previewImageDest(this, 'odometro')">
                                        </div>
                                    </div>

                                    <!-- Extras dinámicos de entrega -->
                                    <div id="contenedor_extras_entrega" class="row g-3 mb-2"></div>
                                </div>

                                <!-- PESTAÑA 2: CARGA MASIVA CON ZIP -->
                                <div class="tab-pane fade" id="pane-fotos-zip-evid" role="tabpanel">
                                    <div class="mb-3" id="contenedor_preview_zip_evid">
                                        <div class="p-4 text-center border-dashed rounded-3 bg-light" style="border: 2px dashed #CBD5E1; cursor: pointer; transition: all 0.2s;" onclick="document.getElementById('file_zip_evid').click();">
                                            <i class="ri-folder-zip-line fs-1 text-warning mb-2 d-block"></i>
                                            <span class="fs-13 fw-bold text-dark d-block">Haz clic aquí o arrastra tu archivo .ZIP</span>
                                            <small class="text-muted d-block mt-1">Sube un archivo ZIP con todas las fotos de la unidad dentro de una carpeta.</small>
                                            <span class="badge bg-soft-info text-info mt-2">Formatos: JPG, PNG, WEBP (Hasta 50MB)</span>
                                        </div>
                                    </div>
                                    <input type="file" id="file_zip_evid" name="archivo_zip" accept=".zip,application/zip,application/x-zip-compressed" class="d-none" onchange="previewZipFile(this, 'contenedor_preview_zip_evid')">
                                    <div class="alert alert-light border fs-12 text-muted mb-2 py-2 px-3 rounded-3">
                                        <i class="ri-information-line text-primary me-1"></i> <strong>Tip de Nombres:</strong> Las fotos pueden llamarse <code>frente.jpg</code>, <code>atras.jpg</code>, <code>izq.jpg</code>, <code>der.jpg</code>, <code>odometro.jpg</code> o números (1 a 5). Si tienen nombres automáticos de cámara, se asignarán en orden.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill border-dashed fw-semibold" id="btnAgregarExtraInspeccion" onclick="agregarExtraEntrega();">
                                    <i class="ri-add-circle-line me-1"></i> <span id="txtBtnAgregarExtra">+ Foto Adicional / Remisión</span>
                                </button>
                                
                                <button type="button" class="btn btn-success px-4 rounded-pill fw-semibold fs-13 shadow-sm" id="btnGuardarInspeccion" onclick="guardarInspeccionEntrega();">
                                    <i class="ri-checkbox-circle-line me-1" id="icoBtnGuardar"></i> <span id="txtBtnGuardar">Guardar Evidencia de Entrega</span>
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
            <div class="modal-footer bg-white border-top py-3 px-4 d-flex justify-content-between align-items-center">
                <a id="btnDescargarZipEnvio" href="#" target="_blank" class="btn btn-warning rounded-pill fw-semibold px-4 text-dark shadow-xs">
                    <i class="ri-folder-zip-line me-1"></i> Descargar Evidencias (ZIP)
                </a>
                <button type="button" class="btn btn-secondary rounded-pill fw-semibold px-4" data-bs-dismiss="modal">
                    <i class="ri-close-line me-1"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL DETALLE DE VINS ASIGNADOS AL ENVÍO -->
<div class="modal fade" id="modalDetalleVins" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="avatar-sm bg-primary-subtle text-primary rounded-3 d-flex align-items-center justify-content-center">
                        <i class="ri-car-line fs-20"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0">
                            Unidades del Envío <span id="lblFolioModalVins" class="badge bg-primary text-white fs-13 ms-2"></span>
                        </h5>
                        <small class="text-muted">Desglose de VINs, modelos y estado físico de entrega</small>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="table-responsive border rounded-3 bg-white">
                    <table class="table table-hover align-middle mb-0 fs-13">
                        <thead class="bg-light">
                            <tr>
                                <th style="width: 50px;" class="text-center">#</th>
                                <th>VIN / Serie</th>
                                <th>Modelo</th>
                                <th class="text-center" style="width: 130px;">Estado Físico</th>
                                <th class="text-end pe-3" style="width: 90px;">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="bodyListaVinsEnvio">
                            <!-- Inyectado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light border-top py-2 px-4 d-flex justify-content-between align-items-center">
                <small class="text-muted" id="lblTotalVinsModalFooter">0 unidades asignadas</small>
                <button type="button" class="btn btn-secondary rounded-pill fw-semibold px-4" data-bs-dismiss="modal">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
