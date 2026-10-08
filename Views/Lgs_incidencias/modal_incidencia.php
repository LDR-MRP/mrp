<!-- Modal Registro / Edición de Incidencia -->
<div class="modal fade" id="modalFormIncidencia" tabindex="-1" aria-labelledby="modalFormIncidenciaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title fw-bold" id="modalFormIncidenciaLabel">
                    <i class="ri-alert-fill text-danger me-1"></i> Registrar Incidencia Operativa
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formIncidencia" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" id="id_incidencia" name="id_incidencia" value="">
                <div class="modal-body p-4">
                    
                    <!-- PASO 1: SELECCIÓN DE ENVÍO -->
                    <div class="card border border-primary-subtle bg-soft-primary shadow-none mb-3">
                        <div class="card-body p-3">
                            <div class="mb-2">
                                <h6 class="fw-bold text-primary mb-0">
                                    <i class="ri-truck-line me-1"></i> 1. Envío Afectado <span class="text-danger">*</span>
                                </h6>
                            </div>

                            <input type="hidden" id="id_envio" name="id_envio" value="" required>

                            <!-- Estado vacío: Sin seleccionar -->
                            <div id="boxEnvioVacio" class="border border-dashed rounded-3 p-3 text-center bg-white">
                                <i class="ri-truck-line text-muted fs-24 d-block mb-1"></i>
                                <div class="fs-13 text-muted mb-2">No se ha seleccionado ningún envío.</div>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="abrirBuscadorEnvios('incidencia');">
                                    <i class="ri-search-line me-1"></i> Buscar Envío o Planeación
                                </button>
                            </div>

                            <!-- Ficha de envío seleccionado -->
                            <div class="d-none" id="infoEnvioSeleccionado">
                                <div class="p-3 border rounded-3 bg-white shadow-sm">
                                    <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap gap-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge bg-primary fs-13" id="txtEnvioFolio">-</span>
                                            <span class="badge bg-soft-secondary text-secondary fs-12" id="txtEnvioPlaneacion">
                                                <i class="ri-calendar-todo-line me-1"></i>Plan: -
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="badge" id="txtEnvioEstado">-</span>
                                            <button type="button" class="btn btn-sm btn-soft-secondary rounded-pill px-2 py-1" onclick="abrirBuscadorEnvios('incidencia');" title="Cambiar de envío">
                                                <i class="ri-refresh-line me-1"></i> Cambiar Envío
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row g-2 fs-12 text-muted">
                                        <div class="col-md-5">
                                            <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-route-line text-primary me-1"></i> Ruta</span>
                                            <strong class="text-dark fs-12" id="txtEnvioRuta">-</strong>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-truck-line text-info me-1"></i> Trasladista</span>
                                            <strong class="text-dark fs-12" id="txtEnvioTrasladista">-</strong>
                                        </div>
                                        <div class="col-md-3">
                                            <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-car-line text-success me-1"></i> VINs Asignados</span>
                                            <strong class="text-dark fs-12" id="txtEnvioTotalVins">-</strong>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top fs-11 text-muted" id="txtEnvioVinsResumenCont">
                                        <strong>VINs en tránsito:</strong> <span id="txtEnvioVinsResumen" class="font-monospace text-secondary">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 2: DATOS DEL EVENTO -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-medium text-muted">Tipo de Incidencia <span class="text-danger">*</span></label>
                            <select id="id_tipo_incidencia" name="id_tipo_incidencia" class="form-select" required onchange="onTipoIncidenciaChange();">
                                <option value="">Seleccione tipo...</option>
                                <?php foreach ($data['catalogos']['tipos_incidencia'] as $t): ?>
                                    <option value="<?= $t['id_tipo_incidencia']; ?>" 
                                            data-dictamen="<?= $t['requiere_dictamen']; ?>"
                                            data-evidencia="<?= $t['requiere_evidencia']; ?>">
                                        <?= htmlspecialchars($t['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="alertaTipoIncidencia" class="form-text fs-11 text-info mt-1 d-none">
                                <i class="ri-information-line me-1"></i> Este tipo requiere dictamen de absorción para autorizar gastos.
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Origen del Reporte <span class="text-danger">*</span></label>
                            <select id="origen" name="origen" class="form-select" required>
                                <option value="OPERACION">Operación / Logística</option>
                                <option value="CHOFER">Chofer Trasladista</option>
                                <option value="CLIENTE">Distribuidor / Cliente</option>
                                <option value="PROVEEDOR">Proveedor Transporte</option>
                                <option value="PLANTA">Planta Lagos / Origen</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Severidad <span class="text-danger">*</span></label>
                            <select id="severidad" name="severidad" class="form-select" required>
                                <option value="BAJA">Baja</option>
                                <option value="MEDIA" selected>Media</option>
                                <option value="ALTA">Alta / Urgente</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-medium text-muted">Fecha y Hora del Suceso <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="fecha_incidente" name="fecha_incidente" class="form-control" required onchange="cargarVinsDeEnvio();">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-medium text-muted">Ubicación / Tramo Carretero</label>
                            <input type="text" id="ubicacion_texto" name="ubicacion_texto" class="form-control" placeholder="Ej. KM 120 Autopista México-Querétaro / Caseta Palmillas">
                        </div>
                    </div>

                    <!-- PASO 3: SELECCIÓN DE VINS AFECTADOS -->
                    <div class="card border border-light-subtle shadow-none mb-3">
                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-semibold text-dark mb-0 fs-13">
                                <i class="ri-car-line me-1 text-primary"></i> VINs Afectados por el Percance <span class="text-danger">*</span>
                            </h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="seleccionarVins('ABORDOS');">
                                    <i class="ri-checkbox-line me-1"></i> Solo a Bordo
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="seleccionarVins('TODOS');">
                                    Todos del Envío
                                </button>
                                <button type="button" class="btn btn-outline-light text-dark" onclick="seleccionarVins('NINGUNO');">
                                    Limpiar
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-2" style="max-height: 220px; overflow-y: auto;">
                            <div id="loadingVins" class="text-center py-3 d-none">
                                <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                                <span class="ms-2 fs-12 text-muted">Cargando unidades del envío...</span>
                            </div>
                            <div id="vinsEmptyMsg" class="text-center py-3 text-muted fs-12">
                                Seleccione un envío para listar las unidades transportadas.
                            </div>
                            <div id="vinsCheckboxList" class="row g-2"></div>
                        </div>
                        <div class="card-footer bg-transparent py-1 px-3 fs-11 text-muted d-flex justify-content-between">
                            <span>Seleccionados: <strong id="lblTotalVinsSeleccionados">0</strong> unidades</span>
                            <span class="text-danger" id="lblWarningEntregados"></span>
                        </div>
                    </div>

                    <!-- PASO 4: DESCRIPCIÓN Y EVIDENCIA -->
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label fs-12 fw-medium text-muted">Descripción Detallada del Percance / Evento <span class="text-danger">*</span></label>
                            <textarea id="descripcion" name="descripcion" class="form-control" rows="3" required placeholder="Describa claramente la causa del desvío, tipo de daño detectado, motivo de retraso o inconformidad..."></textarea>
                        </div>

                        <div class="col-md-7" id="divArchivoInicial">
                            <label class="form-label fs-12 fw-medium text-muted">Adjuntar Evidencia Fotográfica / Documento (Opcional)</label>
                            <input type="file" id="archivo_evidencia" name="archivo_evidencia" class="form-control" accept="image/*,video/mp4,application/pdf">
                            <div class="form-text fs-11">Permitido: JPG, PNG, WEBP, PDF, MP4 (máx. 10MB).</div>
                        </div>
                        <div class="col-md-5" id="divTipoArchivoInicial">
                            <label class="form-label fs-12 fw-medium text-muted">Tipo de Evidencia</label>
                            <select id="tipo_evidencia_inicial" name="tipo_evidencia_inicial" class="form-select">
                                <option value="FOTO">Fotografía en Sitio</option>
                                <option value="VIDEO">Video</option>
                                <option value="DICTAMEN">Dictamen / Nota Técnica</option>
                                <option value="REPORTE_CLIENTE">Reporte de Distribuidor</option>
                                <option value="OTRO">Otro Documento</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarIncidencia" class="btn btn-primary px-4">
                        <i class="ri-save-line me-1"></i> Guardar Incidencia
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
