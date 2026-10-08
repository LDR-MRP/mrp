<!-- Modal Registro de Gasto Adicional -->
<div class="modal fade" id="modalFormGasto" tabindex="-1" aria-labelledby="modalFormGastoLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title fw-bold" id="modalFormGastoLabel">
                    <i class="ri-money-dollar-circle-fill text-success me-1"></i> Registrar Gasto Adicional
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formGasto" autocomplete="off" enctype="multipart/form-data">
                <input type="hidden" id="id_gasto" name="id_gasto" value="">
                <div class="modal-body p-4">
                    
                    <!-- PASO 1: ORIGEN DEL GASTO (LIGADO VS INDEPENDIENTE) -->
                    <div class="card border border-light-subtle shadow-none mb-3 bg-light-subtle">
                        <div class="card-body p-3">
                            <h6 class="fw-semibold text-primary mb-2">
                                <i class="ri-node-tree me-1"></i> 1. Origen del Gasto
                            </h6>
                            <div class="row g-3 align-items-center mb-2">
                                <div class="col-md-6">
                                    <div class="btn-group w-100" role="group">
                                        <input type="radio" class="btn-check" name="origen_modo" id="modo_ligado" value="LIGADO" checked onchange="onOrigenModoChange();">
                                        <label class="btn btn-outline-primary" for="modo_ligado">
                                            <i class="ri-links-line me-1"></i> Ligado a Incidencia
                                        </label>

                                        <input type="radio" class="btn-check" name="origen_modo" id="modo_independiente" value="INDEPENDIENTE" onchange="onOrigenModoChange();">
                                        <label class="btn btn-outline-secondary" for="modo_independiente">
                                            <i class="ri-shield-check-line me-1"></i> Gasto Independiente / Directo
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENEDOR SI ES LIGADO A INCIDENCIA -->
                            <div id="secIncidencia" class="row g-2">
                                <div class="col-12 mb-2">
                                    <div class="mb-1">
                                        <label class="form-label fs-12 fw-bold text-primary mb-0">
                                            <i class="ri-alert-line me-1"></i> Incidencia Operativa Afectada <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <input type="hidden" id="id_incidencia" name="id_incidencia" value="" required>
                                    <input type="hidden" id="id_envio_incidencia" value="">

                                    <!-- Estado vacío: Sin seleccionar -->
                                    <div id="boxIncidenciaVacia" class="border border-dashed rounded-3 p-3 text-center bg-white">
                                        <i class="ri-alert-line text-warning fs-24 d-block mb-1"></i>
                                        <div class="fs-13 text-muted mb-2">No se ha seleccionado ninguna incidencia operativa.</div>
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="abrirBuscadorIncidencias();">
                                            <i class="ri-search-line me-1"></i> Buscar Incidencia Operativa
                                        </button>
                                    </div>

                                    <!-- Ficha de Incidencia Seleccionada -->
                                    <div id="infoIncidenciaSeleccionada" class="d-none">
                                        <div class="p-3 border rounded-3 bg-white shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom flex-wrap gap-2">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-danger fs-13" id="txtIncFolio">-</span>
                                                    <span class="badge bg-soft-info text-info fs-12" id="txtIncTipo">-</span>
                                                    <span class="badge bg-soft-secondary text-secondary fs-12" id="txtIncEnvioFolio">
                                                        <i class="ri-truck-line me-1"></i>-
                                                    </span>
                                                    <span class="badge bg-soft-secondary text-secondary fs-12" id="txtIncPlaneacionFolio">
                                                        <i class="ri-calendar-todo-line me-1"></i>-
                                                    </span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <button type="button" class="btn btn-sm btn-soft-secondary rounded-pill px-2 py-1 fs-12" onclick="abrirBuscadorIncidencias();" title="Cambiar Incidencia">
                                                        <i class="ri-refresh-line me-1"></i> Cambiar Incidencia
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row g-2 fs-12 text-muted">
                                                <div class="col-md-7">
                                                    <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-file-text-line text-primary me-1"></i> Descripción del Incidente</span>
                                                    <div class="text-dark fs-12" id="txtIncDescripcion">-</div>
                                                </div>
                                                <div class="col-md-5">
                                                    <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-scales-line text-primary me-1"></i> Dictamen / Absorción</span>
                                                    <div id="txtIncDictamen">-</div>
                                                </div>
                                                <div class="col-md-6 mt-1">
                                                    <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-building-line text-primary me-1"></i> Trasladista</span>
                                                    <span class="text-dark" id="txtIncTrasladista">-</span>
                                                </div>
                                                <div class="col-md-6 mt-1">
                                                    <span class="text-muted d-block fs-11 text-uppercase fw-bold"><i class="ri-car-line text-primary me-1"></i> VINs Afectados</span>
                                                    <span class="text-dark" id="txtIncVinsAfectados">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTENEDOR SI ES INDEPENDIENTE -->
                            <div id="secIndependiente" class="row g-2 d-none">
                                <div class="col-12 mb-2">
                                    <div class="mb-1">
                                        <label class="form-label fs-12 fw-bold text-primary mb-0">
                                            <i class="ri-truck-line me-1"></i> Envío Afectado (Aprobado, En Tránsito o Entregado) <span class="text-danger">*</span>
                                        </label>
                                    </div>
                                    <input type="hidden" id="id_envio_indep" name="id_envio" value="">

                                    <!-- Estado vacío -->
                                    <div id="boxEnvioIndepVacio" class="border border-dashed rounded-3 p-3 text-center bg-white">
                                        <i class="ri-truck-line text-muted fs-20 d-block mb-1"></i>
                                        <div class="fs-12 text-muted mb-2">Ningún envío seleccionado para este gasto.</div>
                                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm" onclick="abrirBuscadorEnvios('gasto_independiente');">
                                            <i class="ri-search-line me-1"></i> Buscar Envío o Planeación
                                        </button>
                                    </div>

                                    <!-- Ficha seleccionada -->
                                    <div id="infoEnvioIndepSeleccionado" class="d-none">
                                        <div class="p-2 border rounded-3 bg-white shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center mb-1 pb-1 border-bottom flex-wrap gap-2">
                                                <div>
                                                    <span class="badge bg-primary fs-12 me-1" id="txtEnvioIndepFolio">-</span>
                                                    <span class="badge bg-soft-secondary text-secondary fs-11" id="txtEnvioIndepPlaneacion">Plan: -</span>
                                                </div>
                                                <div class="d-flex align-items-center gap-1">
                                                    <span class="badge" id="txtEnvioIndepEstado">-</span>
                                                    <button type="button" class="btn btn-sm btn-soft-secondary rounded-pill px-2 py-0 fs-11" onclick="abrirBuscadorEnvios('gasto_independiente');">
                                                        <i class="ri-refresh-line"></i> Cambiar
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="row g-1 fs-11 text-muted">
                                                <div class="col-md-5"><strong>Ruta:</strong> <span id="txtEnvioIndepRuta">-</span></div>
                                                <div class="col-md-4"><strong>Trasladista:</strong> <span id="txtEnvioIndepTrasladista">-</span></div>
                                                <div class="col-md-3"><strong>VINs:</strong> <span id="txtEnvioIndepTotalVins">-</span></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fs-12 fw-medium text-muted">Justificación del Acuerdo / Pago Directo <span class="text-danger">*</span></label>
                                    <input type="text" id="justificacion_independiente" name="justificacion_independiente" class="form-control" placeholder="Ej. Tarifa extraordinaria negociada por urgencia o cierre de carretera...">
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- PASO 2: TIPO DE GASTO Y PARÁMETROS DE CÁLCULO -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-medium text-muted">Concepto / Tipo de Gasto <span class="text-danger">*</span></label>
                            <select id="id_tipo_gasto" name="id_tipo_gasto" class="form-select" required onchange="onTipoGastoChange();">
                                <option value="">Seleccione tipo de gasto...</option>
                                <?php foreach ($data['catalogos']['tipos_gasto'] as $tg): ?>
                                    <option value="<?= $tg['id_tipo_gasto']; ?>" 
                                            data-clave="<?= $tg['clave']; ?>"
                                            data-calculo="<?= $tg['calculo']; ?>"
                                            data-naturaleza="<?= $tg['naturaleza']; ?>"
                                            data-documento="<?= $tg['documento']; ?>"
                                            data-req-incidencia="<?= $tg['requiere_incidencia']; ?>">
                                        <?= htmlspecialchars($tg['nombre']); ?> (<?= $tg['naturaleza']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Motivo / Causa Específica</label>
                            <select id="id_motivo_gasto" name="id_motivo_gasto" class="form-select">
                                <option value="">Seleccione motivo...</option>
                                <?php foreach ($data['catalogos']['motivos_gasto'] as $mg): ?>
                                    <option value="<?= $mg['id_motivo_gasto']; ?>" data-tipo="<?= $mg['id_tipo_gasto']; ?>">
                                        <?= htmlspecialchars($mg['descripcion']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label fs-12 fw-medium text-muted">Fecha del Gasto <span class="text-danger">*</span></label>
                            <input type="datetime-local" id="fecha_gasto" name="fecha_gasto" class="form-control" required>
                        </div>

                        <!-- PANEL DINÁMICO SEGÚN TIPO DE CÁLCULO -->
                        <div class="col-12" id="panelCalculoDinamico">
                            <!-- Inyectado dinámicamente: inputs para KM, Días x Tarifa o Monto Libre -->
                        </div>

                        <div class="col-12">
                            <label class="form-label fs-12 fw-medium text-muted">Descripción Detallada del Gasto <span class="text-danger">*</span></label>
                            <textarea id="descripcion_gasto" name="descripcion" class="form-control" rows="2" required placeholder="Detalle las razones del cobro, tramo, proveedor o circunstancias..."></textarea>
                        </div>
                    </div>

                    <!-- PASO 3: SELECCIÓN DE VINS Y REPARTO EN PARTES IGUALES -->
                    <div class="card border border-light-subtle shadow-none mb-3">
                        <div class="card-header bg-light py-2 px-3 d-flex justify-content-between align-items-center">
                            <h6 class="fw-semibold text-dark mb-0 fs-13">
                                <i class="ri-pie-chart-2-line me-1 text-primary"></i> Asignación y Prorrateo por Unidad
                            </h6>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="seleccionarVinsGasto('ABORDOS');">A Bordo</button>
                                <button type="button" class="btn btn-outline-secondary" onclick="seleccionarVinsGasto('TODOS');">Todos</button>
                                <button type="button" class="btn btn-outline-light text-dark" onclick="seleccionarVinsGasto('NINGUNO');">Limpiar</button>
                            </div>
                        </div>
                        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
                            <div id="vinsGastoEmpty" class="text-center py-3 text-muted fs-12">
                                Seleccione una incidencia o envío para cargar las unidades transportadas.
                            </div>
                            <div id="vinsGastoCheckboxList" class="row g-2"></div>
                        </div>
                        <div class="card-footer bg-light-subtle py-2 px-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fs-12">Unidades asignadas: <strong id="lblVinsAsignadosCount">0</strong></span>
                                <span class="fs-13 fw-bold text-dark">
                                    Cuota por Unidad: <span id="lblCuotaPorVin" class="text-primary">$0.00</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- PASO 4: DOCUMENTO SOPORTE INICIAL -->
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label fs-12 fw-medium text-muted">Comprobante / Cotización / Ticket (Opcional)</label>
                            <input type="file" id="archivo_soporte" name="archivo_soporte" class="form-control" accept="image/*,application/pdf,text/xml">
                            <div class="form-text fs-11">Permitido: JPG, PNG, PDF, XML (máx. 10MB).</div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fs-12 fw-medium text-muted">Tipo de Comprobante</label>
                            <select id="tipo_documento_inicial" name="tipo_documento_inicial" class="form-select">
                                <option value="COTIZACION">Cotización / Presupuesto</option>
                                <option value="TICKET">Ticket de Caseta / Peaje</option>
                                <option value="ACUERDO">Acuerdo Comercial / Correo</option>
                                <option value="FACTURA_PDF">Factura PDF</option>
                                <option value="OTRO">Otro Documento</option>
                            </select>
                        </div>
                    </div>

                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarGasto" class="btn btn-primary px-4">
                        <i class="ri-save-line me-1"></i> Guardar Gasto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
