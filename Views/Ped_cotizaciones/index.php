<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row mb-3">
                <div class="col-12 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center"><i class="ri-file-list-3-line fs-2"></i></div>
                        <div>
                            <h3 class="mb-0 fw-bold">Propuestas Comerciales</h3><small class="text-muted">Administra propuestas, modelos y autorizaciones comerciales.</small>
                        </div>
                    </div>
                    <!-- <a href="<?= base_url(); ?>/ped_cotizaciones/nueva" class="btn btn-primary"><i class="ri-add-line me-1"></i>Nueva propuesta</a> -->
                          <a href="<?= base_url(); ?>/ped_cotizaciones/nueva" class="btn btn-primary"><i class="ri-add-line me-1"></i>Nueva propuesta</a>
                </div>
            </div>
            <div class="card border-warning mb-3" id="panelBandejaAutorizaciones" style="display:none">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ri-notification-3-line text-warning me-2"></i>Mi bandeja de autorizaciones <span class="badge bg-warning text-dark" id="contadorAutorizaciones">0</span></h5>
                    <button class="btn btn-sm btn-outline-primary" type="button" id="btnActualizarBandeja"><i class="ri-refresh-line me-1"></i>Actualizar</button>
                </div>
                <div class="card-body"><div class="table-responsive"><table class="table table-hover align-middle mb-0"><thead><tr><th>Propuesta</th><th>Cotización</th><th>Distribuidor</th><th>Dirección</th><th>Solicitada</th><th></th></tr></thead><tbody id="tbodyBandeja"></tbody></table></div></div>
            </div>
            <div class="row g-3 mb-3">
                <?php
                $indicadores = [
                    ['indicadorTotal', 'Total propuestas', 'ri-file-list-3-line', 'primary'],
                    ['indicadorBorradores', 'Borradores', 'ri-draft-line', 'secondary'],
                    ['indicadorProceso', 'En proceso', 'ri-time-line', 'warning'],
                    ['indicadorAutorizadas', 'Autorizadas', 'ri-shield-check-line', 'info'],
                    ['indicadorEnviadas', 'Enviadas', 'ri-send-plane-line', 'primary'],
                    ['indicadorAceptadas', 'Aceptadas', 'ri-checkbox-circle-line', 'success'],
                ];
                foreach ($indicadores as [$id, $titulo, $icono, $color]): ?>
                    <div class="col-xl-2 col-md-4 col-sm-6">
                        <div class="card card-animate h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start"><span class="text-uppercase fw-medium text-muted fs-12"><?= htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') ?></span><i class="<?= htmlspecialchars($icono, ENT_QUOTES, 'UTF-8') ?> fs-3 text-<?= $color ?>"></i></div>
                                <h3 id="<?= $id ?>" class="mt-3 mb-0 fw-semibold">0</h3>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mb-1">Valor propuesto (MXN)</p>
                            <h4 id="indicadorValorPropuesto" class="mb-0">$0.00</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mb-1">Valor aceptado (MXN)</p>
                            <h4 id="indicadorValorAceptado" class="mb-0">$0.00</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <p class="text-muted mb-1">Propuestas canceladas</p>
                            <h4 id="indicadorCanceladas" class="mb-0">0</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><i class="ri-filter-3-line me-1"></i>Filtros de búsqueda</h5>
                    <button type="button" class="btn btn-sm btn-ghost-secondary" id="btnLimpiarFiltros"><i class="ri-filter-off-line me-1"></i>Limpiar</button>
                </div>
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-12 col-lg-4"><label for="filterSearch" class="form-label">Buscar</label>
                            <div class="input-group"><span class="input-group-text"><i class="ri-search-line"></i></span><input id="filterSearch" class="form-control" placeholder="Folio, distribuidor, pedido..."></div>
                        </div>
                        <div class="col-6 col-lg-2"><label for="filterDesde" class="form-label">Desde</label><input type="date" id="filterDesde" class="form-control"></div>
                        <div class="col-6 col-lg-2"><label for="filterHasta" class="form-label">Hasta</label><input type="date" id="filterHasta" class="form-control"></div>
                        <div class="col-6 col-lg-2"><label for="filterTipo" class="form-label">Origen</label><select id="filterTipo" class="form-select">
                                <option value="">Todos</option>
                                <option value="DIRECTA">Directa</option>
                                <option value="PEDIDO">Pedido</option>
                            </select></div>
                        <div class="col-6 col-lg-2"><label for="filterEstatus" class="form-label">Estatus</label><select id="filterEstatus" class="form-select">
                                <option value="">Todos</option>
                                <option value="BORRADOR">Borrador</option>
                                <option value="EN_PROCESO">En proceso</option>
                                <option value="PARCIALMENTE_AUTORIZADA">Parcialmente autorizada</option>
                                <option value="AUTORIZADA">Autorizada</option>
                                <option value="PARCIALMENTE_ENVIADA">Parcialmente enviada</option>
                                <option value="ENVIADA">Enviada</option>
                                <option value="PARCIALMENTE_ACEPTADA">Parcialmente aceptada</option>
                                <option value="ACEPTADA">Aceptada</option>
                                <option value="CANCELADA">Cancelada</option>
                            </select></div>
                        <div class="col-12"><button type="button" id="btnRefrescarListado" class="btn btn-outline-secondary btn-sm"><i class="ri-refresh-line me-1"></i>Actualizar listado</button></div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title mb-1">Propuestas registradas</h5><small class="text-muted">Una fila por propuesta; cada propuesta puede incluir varios modelos.</small>
                    </div><span class="badge bg-primary-subtle text-primary" id="contadorRegistros">0 registros</span>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle table-nowrap mb-0" id="tablaPropuestas">
                            <thead class="table-light">
                                <tr>
                                    <th>Folio</th>
                                    <th>Distribuidor</th>
                                    <th>Origen</th>
                                    <th class="text-center">Modelos</th>
                                    <th class="text-center">Autorizadas</th>
                                    <th>Fecha</th>
                                    <th class="text-end">Importe</th>
                                    <th class="text-center">Estatus</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPropuestas">
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-5">Cargando propuestas...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">© LDR Solutions</div>
                <div class="col-sm-6 text-sm-end">MRP · Propuestas Comerciales</div>
            </div>
        </div>
    </footer>
</div>
<?php footerAdmin($data); ?>