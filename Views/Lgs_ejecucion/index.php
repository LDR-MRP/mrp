<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-ejecucion">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Mesa de Despacho</li>
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
                                    <i class="ri-ship-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Mesa de Despacho</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión de salida física de unidades aprobadas, control de asignación y despacho en planta.
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes Salida</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardDespPendientes">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">En patio</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-time-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Tránsito Activo</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardDespTransito">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">En trayecto</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">VINs Entregados</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardVinsEntregados">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Despachados</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-car-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Completados Hoy</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardDespCompletados">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Finalizados</span>
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
                </div>

                <!-- 4. PESTAÑAS: PENDIENTES DE DESPACHO VS HISTÓRICO -->
                <ul class="nav nav-pills mb-3 gap-2" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="btn btn-sm btn-outline-primary active rounded-pill px-4 fw-semibold shadow-sm" id="tab-btn-pendientes" onclick="filtrarMesaDespacho('pendientes')">
                            <i class="ri-truck-line me-1"></i> Por Despachar (<span id="badgeCountPendientes">0</span>)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="btn btn-sm btn-outline-secondary rounded-pill px-4 fw-semibold shadow-sm" id="tab-btn-historico" onclick="filtrarMesaDespacho('historico')">
                            <i class="ri-history-line me-1"></i> Histórico de Despachos (<span id="badgeCountHistorico">0</span>)
                        </button>
                    </li>
                </ul>

                <!-- 5. DATATABLE CARD ESTILIZADA -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tableEjecucion" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio Envío</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen (Sede)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Chofer / Madrina</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Total VINs</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3" id="thFechaEjecucion">Fecha Programada</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Despacho sincronizado en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section> <!-- End view-index-ejecucion -->

            <!-- Despacho Planilla Section (Inline View instead of Modal) -->
            <section id="section-despacho-planilla" class="d-none">
                <div class="card border-0 shadow-lg rounded-3 mb-4">
                    <div class="card-header bg-light border-bottom-0 pb-3 d-flex justify-content-between align-items-center">
                        <h5 class="card-title fw-bold text-primary mb-0" id="titleModalDespacho">
                            <i class="ri-ship-line me-1"></i> Despacho y Entrega a Trasladista: <span id="lblFolioDespacho" class="badge bg-primary fs-14"></span>
                        </h5>
                        <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold" onclick="cerrarDespachoPlanilla();">
                            <i class="ri-arrow-left-line me-1"></i> Volver a la Mesa
                        </button>
                    </div>
                    <div class="card-body p-4">
                        <form id="formDespacho">
                            <input type="hidden" id="id_envio_despacho" name="id_envio" value="">
                            
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted fs-11 text-uppercase">Fecha y Hora Real de Salida de Patio <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control fw-bold text-primary" id="fecha_salida_real" name="fecha_salida_real" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold text-muted fs-11 text-uppercase">Observaciones de Patio / Inspección</label>
                                    <input type="text" class="form-control" id="desp_observaciones" name="observaciones" placeholder="Odómetro inicial, condiciones climatológicas, etc.">
                                </div>
                            </div>
                        </form>

                        <h6 class="fw-bold text-uppercase fs-12 text-muted mb-2"><i class="ri-checkbox-multiple-line me-1 text-primary"></i> Verificación y Carga de VINs en Madrina</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-0">
                                <thead class="table-light fs-11 text-uppercase text-muted">
                                    <tr>
                                        <th class="text-center" style="width: 130px;">Acomodo</th>
                                        <th>VIN / Chasis</th>
                                        <th>Modelo</th>
                                        <th>Color</th>
                                        <th>Estatus en Patio</th>
                                        <th class="text-center" style="width: 200px;">Acción Patio</th>
                                    </tr>
                                </thead>
                                <tbody id="bodyAcomodoPlanta" class="fs-12">
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top-0 pt-3 d-flex justify-content-between">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-sm btn-soft-warning border border-warning px-3 rounded-pill fw-semibold" id="btnResetPruebaSalida" onclick="fntResetPrueba();">
                                <i class="ri-restart-line me-1"></i> Reiniciar Estatus (Prueba)
                            </button>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm" id="btnGuardarDespachoSalida" onclick="guardarDespacho();">
                            <i class="ri-truck-line me-1"></i> Confirmar Salida y Poner en Tránsito
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<!-- MODAL PROGRAMAR RECOLECCIÓN (ADMINISTRATIVO) -->
<div class="modal fade" id="modalProgramarRecoleccion" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="ri-calendar-event-line me-1"></i> Programar Día de Recolección
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formProgramarRecoleccion">
                    <input type="hidden" id="rec_id_envio" name="id_envio" value="">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted fs-11 text-uppercase">Fecha y Hora Pactada de Recolección <span class="text-danger">*</span></label>
                        <input type="date" class="form-control fw-bold text-primary" id="fecha_recoleccion" name="fecha_recoleccion" required>
                        <small class="text-muted d-block mt-2">Al confirmar, el traslado pasará a estado <strong>Confirmado Recolección</strong> y las unidades se marcarán como listas para entregar en el patio de origen.</small>
                    </div>
                </form>
            </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-sm btn-light border px-3 rounded-pill fw-semibold me-2" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary px-4 rounded-pill fw-semibold shadow-sm" onclick="guardarProgramacionRecoleccion();">Confirmar Programación</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Captura y Checklist por VIN -->
<div class="modal fade" id="modalInspeccionVin" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-2xl" style="border-radius: 24px;">
            <div class="modal-header bg-dark text-white border-0 pb-3" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                <div class="d-flex align-items-center">
                    <div class="avatar-xs bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="background-color: #C46623 !important; width: 32px; height: 32px;">
                        <i class="ri-shield-check-line fs-5"></i>
                    </div>
                    <h6 class="modal-title fw-bold text-white mb-0" id="titleModalInspeccion">Inspección Previa a Carga</h6>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formInspeccionVin" enctype="multipart/form-data">
                    <input type="hidden" id="chk_id_envio" name="id_envio">
                    <input type="hidden" id="chk_id_unidad" name="id_unidad">
                    <input type="hidden" id="chk_tipo_checklist" name="tipo_checklist" value="entrada_trasladista">
                    
                    <div class="mb-4 text-center">
                        <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Código VIN de la Unidad</span>
                        <span class="badge bg-light text-dark fs-14 border border-secondary shadow-sm font-monospace px-3 py-2" id="lblVinInspeccion"></span>
                        <input type="hidden" id="vin_confirmado" name="vin" value="">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-top pt-3">
                            <h6 class="fw-bold fs-13 mb-0"><i class="ri-camera-lens-line me-1 text-primary"></i>5 Fotos Obligatorias (Evidencia)</h6>
                        </div>
                        
                        <style>
                        .photo-preview {
                            width: 100%; height: 110px; border-radius: 14px; border: 2px dashed #CBD5E1; background: #F8FAFC; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; position: relative; overflow: hidden;
                        }
                        .photo-preview:hover { border-color: #C46623; background: rgba(196, 102, 35, 0.03); }
                        .photo-preview img { width: 100%; height: 100%; object-fit: cover; }
                        </style>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4 col-6">
                                <span class="fs-11 text-muted fw-bold d-block mb-1">1. Frente</span>
                                <div class="photo-preview text-muted" onclick="triggerFile('file_frente')">
                                    <img id="img_frente" class="d-none">
                                    <i id="ico_frente" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                </div>
                                <input type="file" id="file_frente" name="frente" accept="image/*" class="d-none" onchange="previewImage(this, 'frente')">
                            </div>
                            
                            <div class="col-md-4 col-6">
                                <span class="fs-11 text-muted fw-bold d-block mb-1">2. Atrás</span>
                                <div class="photo-preview text-muted" onclick="triggerFile('file_atras')">
                                    <img id="img_atras" class="d-none">
                                    <i id="ico_atras" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                </div>
                                <input type="file" id="file_atras" name="atras" accept="image/*" class="d-none" onchange="previewImage(this, 'atras')">
                            </div>
                            
                            <div class="col-md-4 col-6">
                                <span class="fs-11 text-muted fw-bold d-block mb-1">3. Lateral Izquierdo</span>
                                <div class="photo-preview text-muted" onclick="triggerFile('file_lateral_izq')">
                                    <img id="img_lateral_izq" class="d-none">
                                    <i id="ico_lateral_izq" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                </div>
                                <input type="file" id="file_lateral_izq" name="lateral_izq" accept="image/*" class="d-none" onchange="previewImage(this, 'lateral_izq')">
                            </div>
                            
                            <div class="col-md-4 col-6">
                                <span class="fs-11 text-muted fw-bold d-block mb-1">4. Lateral Derecho</span>
                                <div class="photo-preview text-muted" onclick="triggerFile('file_lateral_der')">
                                    <img id="img_lateral_der" class="d-none">
                                    <i id="ico_lateral_der" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                </div>
                                <input type="file" id="file_lateral_der" name="lateral_der" accept="image/*" class="d-none" onchange="previewImage(this, 'lateral_der')">
                            </div>

                            <div class="col-md-4 col-6">
                                <span class="fs-11 text-muted fw-bold d-block mb-1">5. Kilometraje / Odómetro</span>
                                <div class="photo-preview text-muted" onclick="triggerFile('file_odometro')">
                                    <img id="img_odometro" class="d-none">
                                    <i id="ico_odometro" class="ri-dashboard-3-line fs-2 text-muted opacity-50"></i>
                                </div>
                                <input type="file" id="file_odometro" name="odometro" accept="image/*" class="d-none" onchange="previewImage(this, 'odometro')">
                            </div>
                        </div>

                        <!-- Contenedor dinámico de extras -->
                        <div id="contenedor_extras_admin" class="row g-3 mb-3"></div>
                        <div class="text-center mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill border-dashed fw-semibold" onclick="agregarEvidenciaExtraAdmin();">
                                <i class="ri-add-circle-line me-1"></i> Agregar Fotografía Adicional
                            </button>
                        </div>

                        <div class="mb-2">
                            <label class="form-label fs-12 text-muted fw-bold">Observaciones / Comentarios</label>
                            <textarea class="form-control fs-13 rounded-3" id="chk_comentarios" name="comentarios" rows="2" placeholder="Describa rayones, faltantes o detalles físicos..."></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light border-0 p-3 d-flex flex-column gap-2" style="border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;">
                <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-bold fs-15 shadow-sm" onclick="guardarInspeccionAdmin();">
                    <i class="ri-checkbox-circle-line me-1"></i> Subir Evidencia y Validar Carga
                </button>
                <button type="button" class="btn btn-light border w-100 rounded-pill py-2 fs-13 fw-semibold" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER EVIDENCIAS -->
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
