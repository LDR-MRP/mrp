<?php headerAdmin($data); ?>
<?php include __DIR__ . '/../Lgs_envios/_partial_envios_styles.php'; ?>
<script>
    window.LGS_ENVIOS_CONFIG = { modoPreEnvio: true };
    window.LGS_BANDEJA_SEDES_INIT = <?= json_encode($data['catalogos_sedes_cli'] ?? ['sedes_por_cliente' => [], 'clientes' => []], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div id="tabsBandejaWrapper">
            <section id="view-index-bandeja">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Bandeja Operativa</li>
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
                                    <i class="ri-inbox-archive-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Bandeja Operativa de Logística</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Seguimiento centralizado de unidades por VIN, asignación de destinos y estatus de movimiento.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- NAVEGACIÓN POR PESTAÑAS: BANDEJA OPERATIVA vs PRE-ENVÍOS (PE-) + UNIDADES PT -->
                <ul class="nav nav-pills nav-customs nav-danger mb-4 gap-2 bg-white p-2 rounded-3 shadow-sm" id="tabsBandejaLogistica" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-semibold rounded-3 px-3 py-2" id="tab-bandeja-operativa-btn"
                                data-bs-toggle="tab" data-bs-target="#tabPaneBandejaOperativa" type="button" role="tab"
                                aria-controls="tabPaneBandejaOperativa" aria-selected="true">
                            <i class="ri-inbox-archive-line me-1 align-bottom"></i> Bandeja Operativa de Logística
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-semibold rounded-3 px-3 py-2" id="tab-preenvios-btn"
                                data-bs-toggle="tab" data-bs-target="#tabPanePreEnvios" type="button" role="tab"
                                aria-controls="tabPanePreEnvios" aria-selected="false" onclick="onAbrirTabPreEnvios();">
                            <i class="ri-route-line me-1 align-bottom"></i> Pre-envíos / Envíos Ficticios (PE-)
                            <span class="badge bg-info text-white ms-1" id="badgeTotalPreEnviosTab">0</span>
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="tabsBandejaLogisticaContent">
                    <div class="tab-pane fade show active" id="tabPaneBandejaOperativa" role="tabpanel" aria-labelledby="tab-bandeja-operativa-btn">

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Unidades en Logística</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-total-bandeja">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Registradas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-car-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes de Salida</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-pendientes-bandeja">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">En Patio</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Tránsito</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-transito-bandeja">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">En Trayecto</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Entregadas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-entregados-bandeja">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">En Destino</span>
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

                <!-- 4. DATATABLE CARD ESTILIZADA -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>
                    <div class="card-body">
                        <!-- Filtros Rápidos -->
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Filtrar por Estado Logístico</label>
                                <select id="filtroEstado" class="form-select bg-light border-0" onchange="recargarBandeja()">
                                    <option value="">Estado: Todos</option>
                                    <option value="1">Pendiente</option>
                                    <option value="2">En Tránsito</option>
                                    <option value="3">Entregado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Activación Comercial (Punto 9)</label>
                                <select id="filtroActivacionComercial" class="form-select bg-light border-0" onchange="recargarBandeja()">
                                    <option value="">Comercial: Todos</option>
                                    <option value="1">🚀 Listo para Despacho (Activado)</option>
                                    <option value="0">📝 Solo Preplaneación</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Estatus Finanzas (Punto 8)</label>
                                <select id="filtroFinanzas" class="form-select bg-light border-0" onchange="recargarBandeja()">
                                    <option value="">Finanzas: Todos</option>
                                    <option value="PAGADO">Pagado</option>
                                    <option value="CREDITO_AUTORIZADO">Crédito Autorizado</option>
                                    <option value="PARCIAL_AUTORIZADO">Parcialmente Pagado</option>
                                    <option value="PENDIENTE">Pendiente Finanzas</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Confirmación Destino (Punto 5)</label>
                                <select id="filtroDestinoConfirmado" class="form-select bg-light border-0" onchange="recargarBandeja()">
                                    <option value="">Destino: Todos</option>
                                    <option value="1">✅ Destino Confirmado</option>
                                    <option value="0">⚠️ Pendiente de Confirmar</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Filtrar por Destino Logístico</label>
                                <select id="filtroDestino" class="form-select bg-light border-0" onchange="recargarBandeja()">
                                    <option value="">Destino: Todos los Catálogos</option>
                                    <?php foreach ($data['destinos'] as $dest): ?>
                                        <option value="<?= intval($dest['id_destino']); ?>"><?= htmlspecialchars($dest['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Filtrar por Motivo</label>
                                <select id="filtroMotivo" class="form-select bg-light border-0" onchange="recargarBandeja()">
                                    <option value="">Motivo: Todos</option>
                                    <?php foreach ($data['motivos'] as $mot): ?>
                                        <option value="<?= intval($mot['id_motivo']); ?>"><?= htmlspecialchars($mot['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-11 fw-bold text-muted text-uppercase mb-1">Búsqueda Rápida</label>
                                <input type="text" id="filtroBusqueda" class="form-control bg-light border-0" placeholder="Buscar VIN, pedido o destino..." oninput="recargarBandeja()">
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover table-lg align-middle mb-0" id="tableBandeja" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">VIN / N/S</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Modelo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Pedido</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Destino de Pedidos (Punto 5)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Destino Confirmado</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Finanzas (Punto 8)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Comercial (Punto 9)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Bandeja sincronizada en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
                    </div><!-- /#tabPaneBandejaOperativa -->

                    <!-- PESTAÑA 2 UNIFICADA: UNA SOLA TABLA BASE DE PRE-ENVÍOS (PE-) DONDE VIVEN TODAS LAS PT -->
                    <div class="tab-pane fade" id="tabPanePreEnvios" role="tabpanel" aria-labelledby="tab-preenvios-btn">
                        <?php
                            $esModoPreEnvio = true;
                            $ocultarBreadcrumbsGrid = true;
                            include __DIR__ . '/../Lgs_envios/_partial_envios_grid.php';
                        ?>
                    </div><!-- /#tabPanePreEnvios -->
                </div><!-- /.tab-content -->
            </section>
            </div><!-- /#tabsBandejaWrapper -->

            <?php
                $esModoPreEnvio = true;
                include __DIR__ . '/../Lgs_envios/_partial_envios_form.php';
            ?>
        </div>
    </div>
</div>

<!-- MODAL ASIGNAR DESTINO Y MOTIVO -->
<div class="modal fade" id="modalAsignarDestino" tabindex="-1" aria-labelledby="labelModalDestino" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="labelModalDestino"><i class="ri-map-pin-2-line me-2"></i>Asignar Destino y Motivo de Envío</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="asig_id_lgs_unidad">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted fs-11 text-uppercase">VIN <small class="text-muted" id="asig_vin_label"></small></label>
                </div>

                <div class="mb-3">
                    <label for="asig_id_motivo" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Motivo de Envío <span class="text-danger">*</span></label>
                    <select id="asig_id_motivo" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($data['motivos'] as $m): ?>
                            <option value="<?= intval($m['id_motivo']); ?>"><?= htmlspecialchars($m['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="asig_id_destino" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Destino <span class="text-danger">*</span></label>
                    <select id="asig_id_destino" class="form-select">
                        <option value="">-- Seleccionar --</option>
                        <?php foreach ($data['destinos'] as $d): ?>
                            <option value="<?= intval($d['id_destino']); ?>"><?= htmlspecialchars($d['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="asig_destino_descripcion" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Distribuidor / Destino Específico</label>
                    <input type="text" id="asig_destino_descripcion" class="form-control" list="listaDistribuidores" placeholder="Ej: ARG BROKER, XIAN MOTORS, etc.">
                    <datalist id="listaDistribuidores">
                        <?php if (!empty($data['distribuidores'])): ?>
                            <?php foreach ($data['distribuidores'] as $dist): ?>
                                <option value="<?= htmlspecialchars($dist['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </datalist>
                    <small class="text-muted fs-11">Seleccione o escriba el nombre del distribuidor / agencia destino.</small>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success px-4 shadow-sm" id="btnGuardarDestino" onclick="fntGuardarDestino()"><i class="ri-save-line me-1"></i> Guardar Asignación</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL VER DETALLE UNIDAD -->
<div class="modal fade" id="modalDetalleUnidad" tabindex="-1" aria-labelledby="labelModalDetalle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-primary" id="labelModalDetalle"><i class="ri-car-line me-2"></i>Detalle de Unidad en Logística</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4" id="detalleUnidadBody">
                <div class="text-center py-4"><div class="spinner-border text-info" role="status"></div></div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR FECHAS -->
<div class="modal fade" id="modalFechas" tabindex="-1" aria-labelledby="labelModalFechas" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <h5 class="modal-title fw-bold text-warning" id="labelModalFechas"><i class="ri-calendar-check-line me-2"></i>Registrar Fechas de Traslado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="fec_id_lgs_unidad">
                <div class="mb-3">
                    <label for="fec_fecha_salida" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Fecha y Hora de Salida</label>
                    <input type="datetime-local" id="fec_fecha_salida" class="form-control">
                </div>
                <div class="mb-3">
                    <label for="fec_fecha_llegada" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Fecha y Hora de Llegada</label>
                    <input type="datetime-local" id="fec_fecha_llegada" class="form-control">
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning px-4 shadow-sm" onclick="fntGuardarFechas()"><i class="ri-save-line me-1"></i> Guardar Fechas</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL SELECCIÓN DIRECTA DE SEDE PARA UNIDAD PT (RESTRINGIDA AL CLIENTE DEL VIN) -->
<div class="modal fade" id="modalSeleccionarSedePT" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-md">
        <div class="modal-content border-0 shadow-lg rounded-3">
            <div class="modal-header bg-light border-bottom-0 pb-3">
                <div>
                    <h5 class="modal-title fw-bold text-primary mb-0"><i class="ri-store-2-line me-2"></i>Seleccionar Sede de Entrega</h5>
                    <small class="text-muted">Solo se muestran sedes y direcciones registradas para el cliente de la unidad.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="sedePTVin">
                <input type="hidden" id="sedePTIdNs">

                <div class="bg-light rounded p-2 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="fs-11 text-muted text-uppercase fw-bold d-block">Unidad / VIN</span>
                            <span class="font-monospace fw-bold text-primary fs-13" id="sedePTVinLabel">-</span>
                        </div>
                        <div class="text-end" id="sedePTPedidoFactBadge"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="sedePTClienteSelect" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Cliente / Distribuidor</label>
                    <select id="sedePTClienteSelect" class="form-select" onchange="poblarSedesModalPT(this.value);">
                        <option value="">-- Seleccionar cliente --</option>
                    </select>
                    <small class="text-muted fs-11" id="sedePTClienteHint"></small>
                </div>

                <div class="mb-2">
                    <label for="sedePTSelect" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Sede / Dirección de Entrega <span class="text-danger">*</span></label>
                    <select id="sedePTSelect" class="form-select">
                        <option value="">Primero seleccione un cliente...</option>
                    </select>
                    <small class="text-muted fs-11 d-block mt-1" id="sedePTDireccionPreview"></small>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-3">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary px-4 shadow-sm" onclick="guardarSedeUnidadPT();">
                    <i class="ri-check-line me-1"></i> Guardar Sede
                </button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
