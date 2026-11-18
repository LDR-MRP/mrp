<?php
$esModoPreEnvio = !empty($esModoPreEnvio);
$ocultarBreadcrumbsGrid = !empty($ocultarBreadcrumbsGrid);
$tituloGrid     = $esModoPreEnvio ? 'Pre-envíos / Posibles Envíos' : 'Bandeja de Envíos';
$subtituloGrid  = $esModoPreEnvio
    ? 'Planeación preliminar de posibles envíos (folio PE-), combinaciones, cálculo multi-tramo y acomodos antes de convertir a Envío Oficial (EN-).'
    : 'Gestión centralizada de traslados físicos, cálculo de fletes y asignación de unidades (Madrinas y Choferes).';
$btnNuevoTxt    = $esModoPreEnvio ? 'Nuevo Pre-envío' : 'Nuevo Envío';
$labelTotalCard = $esModoPreEnvio ? 'Total Pre-envíos' : 'Total Envíos';
$labelViajes    = $esModoPreEnvio ? 'Pre-envíos registrados' : 'Viajes registrados';
?>
            <!-- ── SECCIÓN 1: VISTA GRID / BANDEJA ────────────────────────── -->
            <section id="view-index-envios">
                <?php if (!$ocultarBreadcrumbsGrid): ?>
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-3">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-xs rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary fw-semibold"><?= $tituloGrid; ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN PRINCIPAL -->
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 bg-white p-3 rounded-3 border border-light-subtle shadow-xs mb-3">
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm me-3 flex-shrink-0">
                            <span class="avatar-title text-white rounded-3 fs-4 shadow-xs" style="background-color: <?= $esModoPreEnvio ? '#0284C7' : '#C46623'; ?> !important;">
                                <i class="ri-route-line"></i>
                            </span>
                        </div>
                        <div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <h4 class="mb-0 fw-bold text-uppercase ls-1 text-dark fs-16"><?= $tituloGrid; ?></h4>
                                <?php if ($esModoPreEnvio): ?>
                                    <span class="badge bg-soft-info text-info border border-info-subtle fs-11 px-2 py-1">Simulación (PE-) · No bloquea VINs</span>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted mb-0 fs-12 mt-1">
                                <?= $subtituloGrid; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex-shrink-0">
                        <button type="button" class="btn btn-primary btn-toolbar-lgs shadow-xs" onclick="openModal();" <?= $esModoPreEnvio ? 'style="background-color: #0284C7; border-color: #0284C7;"' : 'style="background-color: #C46623; border-color: #C46623;"'; ?>>
                            <i class="ri-add-line fs-16"></i> <?= $btnNuevoTxt; ?>
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS MODERNOS -->
                <div class="row mb-3 g-2">
                    <!-- 1. Total -->
                    <div class="<?= $esModoPreEnvio ? 'col-md-4' : 'col-xxl-3 col-xl-3 col-md-6 col-sm-6'; ?>">
                        <div class="kpi-card-modern border-top-primary p-3" onclick="filtrarPorEstadoCard('', 'chip-estado-todos');" title="Ver todos">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1"><?= $labelTotalCard; ?></p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardTotalEnvios">0</span></h4>
                                    <small class="text-muted fs-11"><?= $labelViajes; ?></small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4">
                                        <i class="ri-route-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. En Planeación / Borrador -->
                    <div class="<?= $esModoPreEnvio ? 'col-md-4' : 'col-xxl-3 col-xl-3 col-md-6 col-sm-6'; ?>">
                        <div class="kpi-card-modern border-top-secondary p-3" onclick="filtrarPorEstadoCard('Borrador', 'chip-estado-planeacion');" title="Filtrar en planeación / borrador">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1"><?= $esModoPreEnvio ? 'En Borrador' : 'En Planeación'; ?></p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosCreados">0</span></h4>
                                    <small class="text-muted fs-11">Borradores / Abiertos</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fs-4">
                                        <i class="ri-draft-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($esModoPreEnvio): ?>
                    <!-- 3. Pre-envíos Finalizados / Listos para convertir -->
                    <div class="col-md-4">
                        <div class="kpi-card-modern border-top-success p-3" onclick="filtrarPorEstadoCard('Finalizado', 'chip-estado-finalizado');" title="Listos para pasar a Envío Oficial">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Acomodo Listo</p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosEntregados">0</span></h4>
                                    <small class="text-muted fs-11">Listos para convertir a EN-</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-4">
                                        <i class="ri-exchange-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- 3. Por Aprobar -->
                    <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-6">
                        <div class="kpi-card-modern border-top-warning p-3" onclick="filtrarPorEstadoCard('Por Aprobar', 'chip-estado-por-aprobar');" title="Filtrar por aprobar">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Por Aprobar</p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosPorAprobar">0</span></h4>
                                    <small class="text-muted fs-11">En revisión</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-4">
                                        <i class="ri-time-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Aprobados -->
                    <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-6">
                        <div class="kpi-card-modern border-top-info p-3" onclick="filtrarPorEstadoCard('Aprobado', 'chip-estado-aprobado');" title="Filtrar aprobados">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Aprobados</p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosAprobados">0</span></h4>
                                    <small class="text-muted fs-11">Autorizados</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-4">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 5. Programados -->
                    <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-6">
                        <div class="kpi-card-modern border-top-purple p-3" onclick="filtrarPorEstadoCard('Programado', 'chip-estado-programado');" title="Filtrar programados">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Programados</p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosProgramados">0</span></h4>
                                    <small class="text-muted fs-11">Fecha y chofer asignados</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title rounded-circle fs-4" style="background-color: rgba(139, 92, 246, 0.15); color: #8B5CF6;">
                                        <i class="ri-calendar-check-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 6. En Tránsito -->
                    <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-6">
                        <div class="kpi-card-modern border-top-primary p-3" onclick="filtrarPorEstadoCard('En Tránsito', 'chip-estado-transito');" title="Filtrar en tránsito">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">En Tránsito</p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosTransito">0</span></h4>
                                    <small class="text-muted fs-11">En ruta activa</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-4">
                                        <i class="ri-truck-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 7. Pausados -->
                    <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-6">
                        <div class="kpi-card-modern border-top-danger p-3" onclick="filtrarPorEstadoCard('PAUSADO', 'chip-estado-pausado');" title="Filtrar en pausa operativa">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Pausados</p>
                                    <h4 class="fs-20 fw-bold text-danger mb-0"><span class="counter-value" id="cardEnviosPausados">0</span></h4>
                                    <small class="text-muted fs-11">Incidencias en ruta</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-4">
                                        <i class="ri-pause-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 8. Entregados -->
                    <div class="col-xxl-3 col-xl-3 col-md-6 col-sm-6">
                        <div class="kpi-card-modern border-top-success p-3" onclick="filtrarPorEstadoCard('Entregado', 'chip-estado-entregado');" title="Filtrar entregados">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Entregados</p>
                                    <h4 class="fs-20 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosEntregados">0</span></h4>
                                    <small class="text-muted fs-11">Viajes finalizados</small>
                                </div>
                                <div class="avatar-xs flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-4">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 3.1 BARRA DE FILTRO POR PERÍODO Y MONTO TOTAL (SIN IVA) -->
                <div class="row align-items-center mb-3 g-2">
                    <div class="col-xl-8 col-lg-7">
                        <div class="d-flex align-items-center gap-2 flex-wrap bg-white p-2 rounded-3 border border-light-subtle shadow-xs">
                            <span class="fs-12 fw-bold text-muted text-uppercase ls-1 me-1">
                                <i class="ri-calendar-todo-line text-primary me-1"></i>Período:
                            </span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary btn-periodo-envios active" id="btnPeriodoTodos" onclick="cambiarPeriodoEnvios('todos', this)">Todos</button>
                                <button type="button" class="btn btn-outline-secondary btn-periodo-envios" id="btnPeriodoSemana" onclick="cambiarPeriodoEnvios('semana', this)">Esta Semana</button>
                                <button type="button" class="btn btn-outline-secondary btn-periodo-envios" id="btnPeriodoMes" onclick="cambiarPeriodoEnvios('mes', this)">Este Mes</button>
                                <button type="button" class="btn btn-outline-secondary btn-periodo-envios" id="btnPeriodoAnio" onclick="cambiarPeriodoEnvios('anio', this)">Este Año</button>
                                <button type="button" class="btn btn-outline-secondary btn-periodo-envios" id="btnPeriodoAbierta" onclick="cambiarPeriodoEnvios('abierta', this)">Fecha Abierta</button>
                            </div>
                            
                            <div id="boxFiltroFechaAbiertaEnvios" class="d-none d-inline-flex align-items-center gap-1 ms-1">
                                <input type="date" id="filtroEnvioFechaInicio" class="form-control form-control-sm" style="width: 135px;" title="Fecha Inicio">
                                <span class="text-muted fs-12">-</span>
                                <input type="date" id="filtroEnvioFechaFin" class="form-control form-control-sm" style="width: 135px;" title="Fecha Fin">
                                <button type="button" class="btn btn-sm btn-primary px-2" onclick="aplicarFiltroFechaAbiertaEnvios()" title="Aplicar rango de fechas">
                                    <i class="ri-check-line"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light border px-2" onclick="limpiarFiltroFechasEnvios()" title="Limpiar fechas">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-xl-4 col-lg-5">
                        <div class="d-flex align-items-center justify-content-lg-end justify-content-start bg-white p-2 rounded-3 border border-light-subtle shadow-xs">
                            <div class="text-end me-3">
                                <span class="fs-11 text-muted text-uppercase fw-semibold d-block">Monto Total (sin IVA)</span>
                                <span class="fs-18 fw-bold text-success" id="cardMontoTotalEnvios">$0.00</span>
                            </div>
                            <div class="avatar-xs flex-shrink-0">
                                <span class="avatar-title bg-success-subtle text-success rounded-circle fs-4">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                            <div class="ms-2 ps-2 border-start">
                                <span class="badge bg-soft-primary text-primary fs-11" id="badgeEnviosFiltradosCount">0 viajes</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. DATATABLE CARD -->
                <div class="card border border-light-subtle shadow-sm bg-white rounded-3 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                            <!-- Buscador en tiempo real -->
                            <div class="search-pill-modern d-flex align-items-center w-100" style="max-width: 380px;">
                                <i class="ri-search-line text-muted me-2"></i>
                                <input type="text" id="buscarVinBandeja" class="w-100"
                                       placeholder="Buscar por VIN, folio, origen, destino, chofer..."
                                       oninput="filtrarTablaPorVin(this.value)">
                                <button class="btn btn-sm btn-link text-muted p-0 ms-1" type="button" onclick="filtrarTablaPorVin(''); document.getElementById('buscarVinBandeja').value='';" title="Limpiar búsqueda">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>

                            <!-- Píldoras de Filtro Rápido (Chips Homologados) -->
                            <div class="d-flex align-items-center gap-1 flex-wrap">
                                <span class="fs-11 text-muted text-uppercase fw-bold me-1 d-none d-xl-inline"><i class="ri-filter-3-line me-1"></i>Filtro:</span>
                                <button type="button" class="filter-chip-btn active" id="chip-estado-todos" onclick="filtrarPorEstadoChip('', this);">
                                    📌 Todos
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-planeacion" onclick="filtrarPorEstadoChip('Borrador', this);">
                                    📝 <?= $esModoPreEnvio ? 'En Borrador' : 'En Planeación'; ?>
                                </button>
                                <?php if ($esModoPreEnvio): ?>
                                <button type="button" class="filter-chip-btn" id="chip-estado-finalizado" onclick="filtrarPorEstadoChip('Finalizado', this);">
                                    ✅ Acomodo Listo
                                </button>
                                <?php else: ?>
                                <button type="button" class="filter-chip-btn" id="chip-estado-por-aprobar" onclick="filtrarPorEstadoChip('Por Aprobar', this);">
                                    ⏳ Por Aprobar
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-aprobado" onclick="filtrarPorEstadoChip('Aprobado', this);">
                                    📋 Aprobados
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-programado" onclick="filtrarPorEstadoChip('Programado', this);">
                                    📅 Programados
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-transito" onclick="filtrarPorEstadoChip('En Tránsito', this);">
                                    🚚 En Tránsito
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-pausado" onclick="filtrarPorEstadoChip('PAUSADO', this);">
                                    ⏸️ Pausados
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-entregado" onclick="filtrarPorEstadoChip('Entregado', this);">
                                    ✅ Entregados
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tableEnvios" class="table table-hover align-middle mb-0 w-100">
                                <thead class="table-light text-muted fs-11 text-uppercase">
                                    <tr>
                                        <th scope="col" class="ps-3 py-3">Folio / Fecha</th>
                                        <th scope="col" class="py-3">Estado</th>
                                        <th scope="col" class="py-3">Ruta (Origen &rarr; Destino)</th>
                                        <th scope="col" class="py-3">Unidades</th>
                                        <th scope="col" class="py-3">Trasladista / Tipo</th>
                                        <th scope="col" class="py-3">Distancia</th>
                                        <th scope="col" class="py-3">Costo Est.</th>
                                        <th scope="col" class="text-end py-3 pe-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top border-light-subtle py-3 px-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium fs-12">
                                <i class="ri-shield-check-line text-success me-1"></i>
                                <?= $esModoPreEnvio ? 'Pre-envíos listos para simular rutas, combinaciones y convertir a Envío Oficial (EN-)' : 'Envíos sincronizados en tiempo real con la mesa de despacho'; ?>
                            </small>
                        </div>
                    </div>
                </div>
            </section>
