<!-- ── MODAL BUSCADOR AVANZADO DE ENVÍOS Y PLANEACIONES ── -->
<div class="modal fade" id="modalBuscadorEnvios" tabindex="-1" aria-labelledby="modalBuscadorEnviosLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-2xl rounded-3">
            <!-- HEADER -->
            <div class="modal-header bg-gradient bg-light border-bottom py-3 px-4">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3 flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-20">
                            <i class="ri-search-eye-line"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="modalBuscadorEnviosLabel">
                            Buscador de Envíos y Planeaciones
                        </h5>
                        <p class="text-muted fs-12 mb-0">
                            Busque por Folio de Envío, Planeación, VIN / Unidad, Trasladista o Destino para asignar el envío afectado.
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
                                    <span class="input-group-text bg-white border-end-0 text-primary">
                                        <i class="ri-search-line fs-18"></i>
                                    </span>
                                    <input type="text" id="inputBuscarEnvioModal" class="form-control border-start-0 ps-0 fs-14" 
                                           placeholder="Buscar por Folio (ENV-...), Planeación (PLN-...), VIN, Trasladista o Ruta..." 
                                           oninput="filtrarEnviosEnMemoria();" autocomplete="off">
                                    <button class="btn btn-outline-secondary" type="button" onclick="limpiarBuscadorEnviosRapido();" title="Limpiar texto">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-4 text-lg-end">
                                <button type="button" class="btn btn-soft-secondary px-3" data-bs-toggle="collapse" data-bs-target="#collapseFiltrosEnvio" aria-expanded="false">
                                    <i class="ri-filter-3-line me-1"></i> Filtros por Parámetros
                                    <span class="badge bg-primary ms-1" id="badgeFiltrosActivos" style="display: none;">0</span>
                                </button>
                                <button type="button" class="btn btn-outline-primary px-3 ms-1" onclick="recargarEnviosServidor();" title="Refrescar lista desde el servidor">
                                    <i class="ri-refresh-line"></i>
                                </button>
                            </div>
                        </div>

                        <!-- PANEL DESPLEGABLE DE FILTROS POR PARÁMETROS -->
                        <div class="collapse mt-3 pt-3 border-top" id="collapseFiltrosEnvio">
                            <div class="row g-2">
                                <div class="col-md-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Estado de Envío</label>
                                    <select id="modalFiltroEstadoEnvio" class="form-select form-select-sm" onchange="recargarEnviosServidor();">
                                        <option value="">Todos los Estados Permitidos</option>
                                        <option value="3">🟢 3 - Aprobado</option>
                                        <option value="6">🔵 6 - En Tránsito (Ejecutado)</option>
                                        <option value="7">🟣 7 - Entregado</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Empresa Trasladista</label>
                                    <select id="modalFiltroProveedorEnvio" class="form-select form-select-sm" onchange="recargarEnviosServidor();">
                                        <option value="">Todos los Trasladistas</option>
                                        <?php foreach ($data['catalogos']['proveedores'] ?? [] as $pr): ?>
                                            <option value="<?= $pr['id'] ?>"><?= htmlspecialchars($pr['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">Folio Planeación</label>
                                    <input type="text" id="modalFiltroPlaneacion" class="form-control form-control-sm" placeholder="Ej. PLN-00012" onkeydown="if(event.key==='Enter') recargarEnviosServidor();">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label fs-11 text-uppercase fw-bold text-muted mb-1">VIN / Serie</label>
                                    <input type="text" id="modalFiltroVin" class="form-control form-control-sm" placeholder="Ej. 3FA..." onkeydown="if(event.key==='Enter') recargarEnviosServidor();">
                                </div>
                                <div class="col-md-2 d-flex align-items-end gap-1">
                                    <button type="button" class="btn btn-sm btn-primary w-100" onclick="recargarEnviosServidor();">
                                        <i class="ri-search-2-line me-1"></i> Filtrar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-light border" onclick="limpiarTodosFiltrosModal();" title="Limpiar filtros">
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
                        Mostrando <strong class="text-primary" id="lblCountEnviosModal">0</strong> envíos encontrados
                    </span>
                    <div id="spinnerCargandoEnvios" class="spinner-border spinner-border-sm text-primary d-none" role="status">
                        <span class="visually-hidden">Cargando...</span>
                    </div>
                </div>

                <!-- TABLA DE RESULTADOS CON SCROLL INTERNO -->
                <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
                    <div class="table-responsive" style="max-height: 440px; overflow-y: auto;">
                        <table class="table table-hover align-middle mb-0" id="tablaBuscadorEnviosModal">
                            <thead class="table-light fs-11 text-uppercase text-muted sticky-top bg-light" style="z-index: 2;">
                                <tr>
                                    <th style="width: 130px;">Envío</th>
                                    <th style="width: 140px;">Planeación</th>
                                    <th>Ruta / Recorrido</th>
                                    <th>Trasladista</th>
                                    <th>VINs / Unidades</th>
                                    <th style="width: 110px;">Fecha Salida</th>
                                    <th class="text-end" style="width: 120px;">Acción</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyBuscadorEnviosModal" class="fs-13">
                                <!-- Se llena dinámicamente -->
                            </tbody>
                        </table>
                    </div>

                    <!-- ESTADO VACÍO CUANDO NO HAY COINCIDENCIAS -->
                    <div id="msgSinResultadosEnvios" class="text-center py-5 d-none">
                        <i class="ri-truck-line text-muted display-4 opacity-50"></i>
                        <h6 class="fw-semibold text-muted mt-2 mb-1">No se encontraron envíos</h6>
                        <p class="text-muted fs-12 mb-0">Intente modificar los términos de búsqueda o limpiar los filtros.</p>
                    </div>
                </div>
            </div>

            <!-- FOOTER -->
            <div class="modal-footer bg-light py-2 px-4 justify-content-between">
                <small class="text-muted">
                    <i class="ri-information-line me-1"></i> Solo se admiten envíos en estado <strong>Aprobado (3)</strong>, <strong>En Tránsito (6)</strong> o <strong>Entregado (7)</strong>.
                </small>
                <button type="button" class="btn btn-sm btn-light border px-4" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url(); ?>/Assets/js/modulos/functions_lgs_buscador_envios.js"></script>

