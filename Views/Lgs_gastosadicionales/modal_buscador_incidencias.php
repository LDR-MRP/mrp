<!-- ── MODAL BUSCADOR AVANZADO DE INCIDENCIAS OPERATIVAS ── -->
<div class="modal fade" id="modalBuscadorIncidencias" tabindex="-1" aria-labelledby="modalBuscadorIncidenciasLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-2xl rounded-3">
            <!-- HEADER -->
            <div class="modal-header bg-gradient bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3 flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-20">
                            <i class="ri-alert-line"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalBuscadorIncidenciasLabel">
                            Buscador de Incidencias Operativas
                        </h5>
                        <p class="text-muted fs-12 mb-0">
                            Busque por Folio de Incidencia, Envío, Planeación, VIN, Trasladista o Motivo para asignar gastos adicionales.
                        </p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- BODY -->
            <div class="modal-body p-4 bg-light-subtle">
                <!-- BARRA DE BÚSQUEDA RÁPIDA -->
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-lg-8">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white border-end-0 text-danger">
                                        <i class="ri-search-line fs-18"></i>
                                    </span>
                                    <input type="text" id="inputBuscarIncidenciaModal" class="form-control border-start-0 ps-0 fs-14" 
                                           placeholder="Buscar por Folio (INC-...), Envío (ENV-...), Planeación (PLN-...), VIN, Trasladista o Motivo..." 
                                           oninput="filtrarIncidenciasEnMemoria();" autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" onclick="limpiarBuscadorIncidenciasRapido();" title="Limpiar texto">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <button type="button" class="btn btn-soft-secondary px-3" data-bs-toggle="collapse" data-bs-target="#collapseFiltrosIncidencias" aria-expanded="false">
                                    <i class="ri-filter-3-line me-1"></i> Filtros por Parámetros
                                    <span class="badge bg-danger ms-1" id="badgeFiltrosIncidenciasActivos" style="display: none;">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-danger px-3 ms-1" onclick="recargarIncidenciasServidor();" title="Refrescar lista desde el servidor">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                        </div>

                        <!-- PANEL DESPLEGABLE DE FILTROS POR PARÁMETROS -->
                        <div class="collapse mt-3 pt-3 border-top" id="collapseFiltrosIncidencias">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Tipo de Incidencia</label>
                                    <select id="modalFiltroTipoIncidencia" class="form-select form-select-sm" onchange="recargarIncidenciasServidor();">
                                        <option value="">Todos los Tipos</option>
                                        <?php foreach ($data['catalogos']['tipos_incidencia'] ?? [] as $ti): ?>
                                            <option value="<?= $ti['id_tipo_incidencia'] ?>"><?= htmlspecialchars($ti['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Dictamen / Absorción</label>
                                    <select id="modalFiltroAbsorcionIncidencia" class="form-select form-select-sm" onchange="recargarIncidenciasServidor();">
                                        <option value="">Todos los Dictámenes</option>
                                        <?php foreach ($data['catalogos']['absorciones'] ?? [] as $ab): ?>
                                            <option value="<?= $ab['id_absorcion'] ?>"><?= htmlspecialchars($ab['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Trasladista</label>
                                    <select id="modalFiltroProveedorIncidencia" class="form-select form-select-sm" onchange="recargarIncidenciasServidor();">
                                        <option value="">Todos</option>
                                        <?php foreach ($data['catalogos']['proveedores'] ?? [] as $pr): ?>
                                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Folio Planeación</label>
                                    <input type="text" id="modalFiltroPlaneacionIncidencia" class="form-control form-control-sm" placeholder="Ej. PLN-00012" onkeydown="if(event.key==='Enter') recargarIncidenciasServidor();">
                                </div>
                                <div class="col-md-2 d-flex align-items-end gap-1">
                                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="recargarIncidenciasServidor();">
                                        <i class="ri-search-2-line me-1"></i> Filtrar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="limpiarTodosFiltrosIncidenciasModal();" title="Limpiar filtros">
                                        <i class="ri-restart-line"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RESUMEN Y CONTADOR -->
                <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                    <span class="fs-13 text-muted">
                        Mostrando <strong class="text-danger" id="lblCountIncidenciasModal">0</strong> incidencias encontradas
                    </span>
                    <div id="spinnerCargandoIncidencias" class="spinner-border spinner-border-sm text-danger d-none" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

                <!-- TABLA DE RESULTADOS CON SCROLL INTERNO -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive" style="max-height: 440px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="tablaBuscadorIncidenciasModal">
                            <thead class="table-light fs-11 text-uppercase text-muted sticky-top bg-light" style="z-index: 2;">
                                <tr>
                                    <th style="width: 140px;">Incidencia</th>
                                    <th>Tipo de Incidencia</th>
                                    <th style="width: 160px;">Envío / Planeación</th>
                                    <th>Trasladista</th>
                                    <th>VINs Afectados</th>
                                    <th>Dictamen / Absorción</th>
                                    <th class="text-end" style="width: 120px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyBuscadorIncidenciasModal" class="fs-13">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- ESTADO VACÍO CUANDO NO HAY COINCIDENCIAS -->
                    <div id="msgSinResultadosIncidencias" class="text-center py-5 d-none">
                        <i class="ri-alert-line text-muted display-4 opacity-50"></i>
                        <h6 class="fw-semibold text-muted mt-2 mb-1">No se encontraron incidencias</h6>
                        <p class="text-muted fs-12 mb-0">Intente modificar los términos de búsqueda o limpiar los filtros.</p>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer bg-light py-2 px-4 justify-content-between">
                <small class="text-muted">
                    <i class="ri-information-line me-1"></i> Solo se admiten incidencias operativas en estado <strong>Abierta</strong>, <strong>En Investigación</strong> o <strong>Dictaminada</strong>.
                </small>
                <button type="button" class="btn btn-sm btn-light border px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url(); ?>/Assets/js/modulos/functions_lgs_buscador_incidencias.js"></script>
