<!-- Modal Dictamen de Absorción -->
<div class="modal fade" id="modalDictamenIncidencia" tabindex="-1" aria-labelledby="modalDictamenLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-light p-3">
                <h5 class="modal-title fw-bold" id="modalDictamenLabel">
                    <i class="ri-scales-3-line text-primary me-1"></i> Dictamen de Absorción de Daño / Evento
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formDictamen" autocomplete="off">
                <input type="hidden" id="dictamen_id_incidencia" name="id_incidencia" value="">
                <div class="modal-body p-4">
                    
                    <div class="alert alert-info py-2 px-3 mb-3 fs-13">
                        <div class="fw-semibold" id="dictamen_info_incidencia">IN-000000 - Envío EN-000000</div>
                        <div class="text-muted small" id="dictamen_info_tipo">Tipo de Incidencia</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">¿Quién absorbe el costo / responsabilidad? <span class="text-danger">*</span></label>
                        <select id="dictamen_id_absorcion" name="id_absorcion" class="form-select" required onchange="onAbsorcionChange();">
                            <option value="">Seleccione dictamen...</option>
                            <?php foreach ($data['catalogos']['absorciones'] as $a): ?>
                                <option value="<?= $a['id_absorcion']; ?>" 
                                        data-clave="<?= $a['clave']; ?>"
                                        data-genera="<?= $a['genera_gasto']; ?>">
                                    <?= htmlspecialchars($a['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Div opcional para porcentaje cuando es Compartido -->
                    <div class="mb-3 d-none" id="divCompartido">
                        <label class="form-label fs-12 fw-medium text-muted">Porcentaje a cargo del Proveedor Trasladista (%) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" id="dictamen_porcentaje_proveedor" name="porcentaje_proveedor" class="form-control" min="1" max="99" step="0.5" placeholder="Ej. 50">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="form-text fs-11 text-muted" id="lblPorcentajeEmpresa">El resto será absorbido por la empresa (LDR).</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fs-12 fw-medium text-muted">Justificación / Notas del Dictamen <span class="text-danger">*</span></label>
                        <textarea id="dictamen_notas" name="dictamen_notas" class="form-control" rows="3" required placeholder="Fundamente el criterio de absorción, deslinde o dictamen técnico..."></textarea>
                    </div>

                    <div class="alert alert-warning py-2 px-3 fs-12 mb-0 d-none" id="alertaImprocedente">
                        <i class="ri-alert-line me-1"></i> Al dictaminar <strong>Improcedente</strong>, la incidencia pasará a <em>Resuelta sin costo</em> y no permitirá generar gastos adicionales.
                    </div>

                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" id="btnGuardarDictamen" class="btn btn-primary px-4">
                        <i class="ri-check-line me-1"></i> Guardar Dictamen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
