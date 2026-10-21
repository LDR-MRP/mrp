<?php headerAdmin($data); ?>

<style>
/* Modern UI Tokens Scoped to Envíos */
:root {
    --brand-primary: #C46623;
    --brand-primary-hover: #b0571c;
    --brand-dark: #0F172A;
    --surface-card: #FFFFFF;
    --surface-border: #E2E8F0;
    --surface-bg: #F8FAFC;
}

.envios-surface-card {
    background: var(--surface-card);
    border: 1px solid var(--surface-border);
    border-radius: 18px;
    box-shadow: 0 4px 18px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
}

.kpi-card-modern {
    background: #FFFFFF;
    border: 1px solid var(--surface-border);
    border-radius: 16px;
    box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow: hidden;
    position: relative;
}
.kpi-card-modern:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 24px -4px rgba(15, 23, 42, 0.08);
}
.kpi-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 3px;
    background: transparent;
    transition: background 0.2s ease;
}
.kpi-card-modern.border-top-primary::before { background: var(--brand-primary); }
.kpi-card-modern.border-top-warning::before { background: #F59E0B; }
.kpi-card-modern.border-top-info::before { background: #0284C7; }
.kpi-card-modern.border-top-success::before { background: #10B981; }

/* Smart Search Pill */
.search-pill-modern {
    background: #FFFFFF;
    border: 1px solid #CBD5E1;
    border-radius: 30px;
    padding: 6px 16px;
    transition: all 0.2s ease;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.search-pill-modern:focus-within {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 3px rgba(196, 102, 35, 0.15);
}
.search-pill-modern input {
    border: none;
    outline: none;
    box-shadow: none;
    font-size: 13px;
    background: transparent;
}

/* Quick Filter Chips */
.filter-chip-btn {
    border: 1px solid #E2E8F0;
    background: #FFFFFF;
    color: #475569;
    font-size: 11.5px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    transition: all 0.2s ease;
    cursor: pointer;
    user-select: none;
}
.filter-chip-btn:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
    color: #0F172A;
}
.filter-chip-btn.active {
    background: var(--brand-primary);
    border-color: var(--brand-primary);
    color: #FFFFFF;
    box-shadow: 0 2px 8px rgba(196, 102, 35, 0.25);
}

/* Ocultar input default plano de DataTables */
#tableEnvios_filter {
    display: none;
}
#tableEnvios_wrapper .dataTables_length {
    padding: 12px 16px 6px;
}
#tableEnvios_wrapper .dataTables_length label {
    font-size: 12px;
    color: #64748B;
    font-weight: 500;
}
#tableEnvios_wrapper .dataTables_length select {
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    padding: 4px 8px;
    font-size: 12px;
    margin: 0 4px;
}
#tableEnvios_wrapper .dataTables_info,
#tableEnvios_wrapper .dataTables_paginate {
    padding: 12px 16px;
    font-size: 12px;
}

/* Radio Cards para Modalidad de Cálculo */
.radio-card-option {
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 12px 14px;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    background: #FFFFFF;
}
.radio-card-option:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
    transform: translateY(-1px);
}
.radio-card-option.active {
    border-color: var(--brand-primary);
    background: #FFF7ED;
    box-shadow: 0 0 0 2px rgba(196, 102, 35, 0.15);
}
.radio-card-option .radio-dot {
    width: 18px;
    height: 18px;
    border-radius: 50%;
    border: 2px solid #CBD5E1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.radio-card-option.active .radio-dot {
    border-color: var(--brand-primary);
}
.radio-card-option.active .radio-dot::after {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: var(--brand-primary);
}

/* Nodos del Itinerario Stepper */
.nodo-item {
    border-radius: 14px !important;
    border: 1px solid #E2E8F0 !important;
    background: #FFFFFF !important;
    box-shadow: 0 3px 10px -2px rgba(15, 23, 42, 0.04) !important;
    transition: all 0.2s ease;
}
.nodo-item:hover {
    box-shadow: 0 8px 20px -3px rgba(15, 23, 42, 0.08) !important;
}
.nodo-item .form-select-sm, .nodo-item .form-control-sm {
    border-radius: 8px;
    border-color: #CBD5E1;
}
.nodo-item .form-select-sm:focus, .nodo-item .form-control-sm:focus {
    border-color: var(--brand-primary);
    box-shadow: 0 0 0 2px rgba(196, 102, 35, 0.15);
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- ── SECCIÓN 1: VISTA GRID / BANDEJA ────────────────────────── -->
            <section id="view-index-envios">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-3">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-xs rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary fw-semibold">Bandeja de Envíos</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN PRINCIPAL -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-sm border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-route-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-dark">Bandeja de Envíos</h3>
                                <p class="text-muted mb-0 fs-13">
                                    Gestión centralizada de traslados físicos, cálculo de fletes y asignación de unidades (Madrinas y Choferes).
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-3 mt-md-0">
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm" onclick="openModal();">
                            <i class="ri-add-circle-fill align-middle fs-16 me-1"></i> Nuevo Envío
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS MODERNOS -->
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-primary p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Total Envíos</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0"><span class="counter-value" id="cardTotalEnvios">0</span></h4>
                                    <small class="text-muted fs-11">Viajes programados</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-route-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-warning p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">En Planeación</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosCreados">0</span></h4>
                                    <small class="text-muted fs-11">Sin consolidar / Borrador</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="ri-draft-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-info p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">En Tránsito</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosTransito">0</span></h4>
                                    <small class="text-muted fs-11">En ruta activa</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                        <i class="ri-truck-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-success p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Entregados</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0"><span class="counter-value" id="cardEnviosEntregados">0</span></h4>
                                    <small class="text-muted fs-11">Viajes finalizados</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-checkbox-circle-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. DATATABLE CARD -->
                <div class="card border border-light-subtle shadow-sm bg-white rounded-3 mb-4">
                    <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                        <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                            <!-- Buscador en tiempo real -->
                            <div class="search-pill-modern d-flex align-items-center w-100" style="max-width: 420px;">
                                <i class="ri-search-line text-muted me-2"></i>
                                <input type="text" id="buscarVinBandeja" class="w-100"
                                       placeholder="Buscar por VIN, folio, origen, destino, chofer..."
                                       oninput="filtrarTablaPorVin(this.value)">
                                <button class="btn btn-sm btn-link text-muted p-0 ms-1" type="button" onclick="filtrarTablaPorVin(''); document.getElementById('buscarVinBandeja').value='';" title="Limpiar búsqueda">
                                    <i class="ri-close-line"></i>
                                </button>
                            </div>

                            <!-- Píldoras de Filtro Rápido (Chips) -->
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <span class="fs-11 text-muted text-uppercase fw-bold me-1 d-none d-xl-inline"><i class="ri-filter-3-line me-1"></i>Filtro:</span>
                                <button type="button" class="filter-chip-btn active" id="chip-estado-todos" onclick="filtrarPorEstadoChip('', this);">
                                    📌 Todos
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-planeacion" onclick="filtrarPorEstadoChip('Creado', this);">
                                    ⏳ En Planeación
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-transito" onclick="filtrarPorEstadoChip('Ejecutado', this);">
                                    🚚 En Tránsito
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-entregado" onclick="filtrarPorEstadoChip('Entregado', this);">
                                    ✅ Entregados
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-slc" onclick="filtrarPorEstadoChip('SLC', this);">
                                    🟢 SLC
                                </button>
                                <button type="button" class="filter-chip-btn" id="chip-estado-sll" onclick="filtrarPorEstadoChip('SLL', this);">
                                    🟡 SLL
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="tableEnvios" class="table table-hover align-middle mb-0 w-100">
                                <thead class="table-light text-muted fs-11 text-uppercase">
                                    <tr>
                                        <th scope="col" class="ps-4 py-3">Folio</th>
                                        <th scope="col" class="py-3">Estado</th>
                                        <th scope="col" class="py-3">Origen</th>
                                        <th scope="col" class="py-3">Destino</th>
                                        <th scope="col" class="py-3">Fecha Prog.</th>
                                        <th scope="col" class="py-3">VINs Asignados</th>
                                        <th scope="col" class="py-3">Trasladista</th>
                                        <th scope="col" class="py-3">Costo Est.</th>
                                        <th scope="col" class="py-3">Distancia (KM)</th>
                                        <th scope="col" class="py-3">Tipo</th>
                                        <th scope="col" class="py-3">Motivo</th>
                                        <th scope="col" class="py-3">ID</th>
                                        <th scope="col" class="text-end py-3 pe-4">Acciones</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Envíos sincronizados en tiempo real con la mesa de despacho
                            </small>
                        </div>
                    </div>
                </div>
            </section>


            <!-- ── SECCIÓN 2: VISTA FORMULARIO SEPARADO ────────────────────── -->
            <section id="view-form-envios" style="display: none;">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-xs rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="cancelFormEnvio();">Envíos</a></li>
                                    <li class="breadcrumb-item active text-primary fw-semibold" id="breadcrumb-form-envio">Nuevo Envío</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER FORMULARIO -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title bg-primary text-white rounded-circle fs-3 shadow-sm" style="background-color: var(--brand-primary) !important;">
                                    <i class="ri-file-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold ls-05 text-dark" id="form-envio-title">Crear Solicitud de Traslado</h4>
                                <p class="text-muted mb-0 fs-13">Configure el itinerario de paradas, modalidad de cálculo y transportista responsable.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formEnvio" name="formEnvio" autocomplete="off" onsubmit="event.preventDefault(); saveEnvio();">
                    <input type="hidden" id="id_envio" name="id_envio" value="">

                    <div class="row g-4">
                        <!-- COLUMNA PRINCIPAL (8 de 12) -->
                        <div class="col-lg-8">
                            <!-- 1. PARÁMETROS OPERATIVOS DEL ENVÍO -->
                            <div class="envios-surface-card p-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 fw-bold text-dark fs-14">
                                        <i class="ri-settings-4-line text-primary me-2 align-middle"></i> Parámetros de Operación
                                    </h6>
                                    <span class="badge bg-light text-muted border fs-11">Datos Generales</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Empresa Trasladista <span class="text-danger">*</span></label>
                                        <select class="form-select border rounded-3" id="id_proveedor" name="id_proveedor" required onchange="handleProveedorChange()">
                                            <option value="">Seleccione Trasladista...</option>
                                            <?php foreach ($data['catalogos']['proveedores'] ?? [] as $p): ?>
                                                <option value="<?= $p['id']; ?>" data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-modalidad="<?= htmlspecialchars($p['modalidad_envio_default'] ?? 'DIRECTO', ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Traslado <span class="text-danger">*</span></label>
                                        <select class="form-select border rounded-3" id="id_tipo_traslado" name="id_tipo_traslado" required onchange="handleTipoTrasladoEnvio()">
                                            <option value="">Seleccione Tipo...</option>
                                            <?php foreach ($data['catalogos']['tipos_traslado'] ?? [] as $t): ?>
                                                <option value="<?= $t['id']; ?>"><?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <!-- Check Lowboy (Oculto por defecto, visible solo si es Plataforma) -->
                                        <div id="divCheckLowboy" class="mt-2 form-check form-switch" style="display: none;">
                                            <input class="form-check-input border-dark bg-secondary" type="checkbox" role="switch" id="is_lowboy" name="is_lowboy" value="1">
                                            <label class="form-check-label fw-bold text-dark fs-12" for="is_lowboy">
                                                Check Lowboy <span class="badge bg-dark ms-1">Tarifa $80</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Motivo del Traslado <span class="text-danger">*</span></label>
                                        <select class="form-select border rounded-3" id="id_motivo" name="id_motivo" required>
                                            <option value="">Seleccione Motivo...</option>
                                            <?php foreach ($data['catalogos']['motivos'] ?? [] as $m): ?>
                                                <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Servicio</label>
                                        <select class="form-select border rounded-3" id="tipo_servicio" name="tipo_servicio" onchange="verificarYCalcularRuta()">
                                            <option value="AUTOMATICO" selected>Automático / Mixto (Detecta SLC, SLL y Foráneo)</option>
                                            <option value="FORANEO">Foráneo (Costo por KM)</option>
                                            <option value="SLC">Local Corto SLC (0-40 KM)</option>
                                            <option value="SLL">Local Largo SLL (41-80 KM)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. MODALIDAD DE CÁLCULO (RADIO CARDS INTERACTIVAS) -->
                            <div class="envios-surface-card p-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 fw-bold text-dark fs-14">
                                        <i class="ri-calculator-line text-primary me-2 align-middle"></i> Modalidad de Cálculo de Flete
                                    </h6>
                                    <span class="badge bg-soft-primary text-primary fs-11 fw-semibold">Prorrateo Tarifario</span>
                                </div>

                                <!-- Input select nativo oculto para compatibilidad 100% con backend y validaciones -->
                                <select class="d-none" id="modalidad_calculo" name="modalidad_calculo" onchange="handleModalidadCalculoChange(true)">
                                    <option value="ESCALONADO">Escalonado (Tradicional)</option>
                                    <option value="DIRECTO">Directo con Escalas por Origen</option>
                                </select>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="radio-card-option d-flex align-items-start gap-3" id="card-modalidad-directo" onclick="seleccionarModalidadCard('DIRECTO');">
                                            <div class="radio-dot mt-1"></div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <strong class="fs-13 text-dark"><i class="ri-direction-line text-primary me-1"></i>Directo con Escalas</strong>
                                                    <span class="badge bg-success-subtle text-success fs-10 fw-semibold">Recomendado</span>
                                                </div>
                                                <p class="text-muted fs-11 mb-0 mt-1">
                                                    Cálculo individual por unidad desde su origen de carga hasta su destino final.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="radio-card-option active d-flex align-items-start gap-3" id="card-modalidad-escalonado" onclick="seleccionarModalidadCard('ESCALONADO');">
                                            <div class="radio-dot mt-1"></div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <strong class="fs-13 text-dark"><i class="ri-route-line text-secondary me-1"></i>Escalonado Tradicional</strong>
                                                    <span class="badge bg-light text-secondary border fs-10 fw-semibold">Por Tramo</span>
                                                </div>
                                                <p class="text-muted fs-11 mb-0 mt-1">
                                                    Prorrateo escalonado tramo por tramo según el recorrido físico de la madrina.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 text-muted fs-11" id="helpModalidadCalculo">
                                    <i class="ri-information-line me-1"></i> Prorrateo escalonado tradicional por tramo.
                                </div>
                            </div>

                            <!-- 2.1 MONTO ABIERTO / ACUERDO COMERCIAL -->
                            <div class="envios-surface-card p-4 mb-4" id="card-monto-abierto-container">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-bold text-dark fs-14">
                                            <i class="ri-hand-coin-line text-warning me-2 align-middle"></i> Modalidad Comercial: Monto Abierto
                                        </h6>
                                        <span class="badge bg-soft-warning text-warning fs-11 fw-semibold">Acuerdo Especial / MKT</span>
                                    </div>
                                    <div class="form-check form-switch form-switch-md mb-0">
                                        <input class="form-check-input" type="checkbox" id="check_monto_abierto" onchange="toggleMontoAbierto(this.checked);">
                                        <label class="form-check-label fs-12 fw-medium text-dark" for="check_monto_abierto">Habilitar Monto Abierto</label>
                                    </div>
                                </div>

                                <input type="hidden" id="es_monto_abierto" name="es_monto_abierto" value="0">

                                <div id="campos_monto_abierto" style="display: none;">
                                    <div class="alert alert-soft-warning border-0 p-3 mb-3 rounded-3 d-flex align-items-center gap-2">
                                        <i class="ri-information-line text-warning fs-20 flex-shrink-0"></i>
                                        <div class="fs-12 text-dark">
                                            Al activar <strong>Monto Abierto</strong>, el sistema respetará el costo pactado directamente con el proveedor (para estadías de operadores, vuelos, viáticos o condiciones de Marketing), omitiendo el recálculo tarifario estándar por kilómetro.
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Monto Total Pactado ($ MXN) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border fw-bold text-muted">$</span>
                                                <input type="number" step="0.01" min="0" class="form-control fw-bold fs-14 text-success" id="monto_pactado" name="monto_pactado" placeholder="0.00" oninput="actualizarCostoEstimadoPorMontoAbierto();">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Motivo / Justificación del Acuerdo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="motivo_monto_abierto" name="motivo_monto_abierto" placeholder="Ej. Movimiento MKT: estadías toda la semana y vuelo operador Cancún">
                                        </div>
                                    </div>
                                </div>
                                <div id="help_monto_abierto_inactivo" class="text-muted fs-11">
                                    <i class="ri-checkbox-blank-circle-line me-1 text-muted" style="font-size: 8px;"></i> Tabulador estándar activo por defecto. Active esta opción solo si existe un acuerdo comercial o gasto extraordinario fuera de tabulador.
                                </div>
                            </div>

                            <!-- 3. ITINERARIO Y SECUENCIA DE PARADAS (MULTI-ORIGEN Y MULTI-DESTINO) -->
                            <div class="envios-surface-card p-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark fs-14">
                                            <i class="ri-route-line text-primary me-2 align-middle"></i> Itinerario del Recorrido (Multi-Origen y Multi-Destino) <span class="text-danger">*</span>
                                        </h6>
                                        <small class="text-muted fs-11">
                                            Construye la secuencia del viaje: <strong><span class="text-success">Carga Inicial (Verde)</span></strong>, <strong><span style="color: #0d6efd;">Cargas Adicionales (Azul)</span></strong> y <strong><span style="color: #fd7e14;">Bajadas / Descargas (Naranja)</span></strong>.
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-soft-primary rounded-pill px-3 shadow-xs" onclick="verificarYCalcularRuta(null, true)" title="Consultar distancias en memoria">
                                            <i class="ri-refresh-line me-1"></i> ⚡ Distancias
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-xs" onclick="agregarNodoRuta(null, true)">
                                            <i class="ri-add-line me-1"></i> Añadir Carga
                                        </button>
                                        <button type="button" class="btn btn-sm text-white rounded-pill px-3 shadow-xs" style="background-color: #fd7e14; border-color: #fd7e14;" onclick="agregarNodoRuta(null, false)">
                                            <i class="ri-add-line me-1"></i> Añadir Descarga
                                        </button>
                                    </div>
                                </div>

                                <!-- Badge Resumen Distancia Total -->
                                <div id="badge-distancia-total-container" class="alert alert-soft-primary d-flex align-items-center justify-content-between p-2 mb-3 rounded-3" style="display:none !important;">
                                    <span class="fs-12 fw-medium"><i class="ri-road-map-line text-primary me-1"></i>Distancia Total del Recorrido:</span>
                                    <span id="badge-km-total-val" class="badge bg-primary fs-13">0 km</span>
                                </div>

                                <!-- Contenedor dinámico de nodos del recorrido -->
                                <div id="contenedor-nodos-ruta" class="d-flex flex-column gap-3 mb-3">
                                    <!-- Los nodos se agregan aquí dinámicamente -->
                                </div>

                                <div id="msg-sin-nodos" class="text-center py-4 border border-dashed rounded-3 text-muted bg-light" style="border-style: dashed !important; display:none;">
                                    <i class="ri-map-pin-add-line fs-28 d-block mb-1 text-primary opacity-50"></i>
                                    <span class="fs-12">Sin puntos en el recorrido. Haz clic en <strong>"Añadir Carga"</strong> o <strong>"Añadir Descarga"</strong> para comenzar.</span>
                                </div>

                                <!-- Campos ocultos de sincronización -->
                                <input type="hidden" id="fecha_tentativa_envio" name="fecha_tentativa_envio" value="">
                                <input type="hidden" id="fecha_tentativa_llegada" name="fecha_tentativa_llegada" value="">
                                <input type="hidden" id="nodos_json" name="nodos" value="[]">
                                <input type="hidden" id="paradas_json" name="paradas" value="[]">
                                <input type="hidden" id="id_origen" name="id_origen" value="">
                                <input type="hidden" id="id_destino" name="id_destino" value="">
                                <input type="hidden" id="destino_nombre_libre" name="destino_nombre_libre" value="">

                                <!-- Catálogos completos para el JS -->
                                <script id="catalogoUbicaciones" type="application/json">
                                    <?= json_encode($data['catalogos']['ubicaciones'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>
                                <script id="catalogoDestinos" type="application/json">
                                    <?= json_encode($data['catalogos']['destinos'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>
                                <script id="catalogoDistribuidores" type="application/json">
                                    <?= json_encode($data['catalogos']['distribuidores'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>

                                <div class="mt-3">
                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Observaciones Operativas</label>
                                    <textarea class="form-control border rounded-3" id="observaciones" name="observaciones" rows="2" placeholder="Instrucciones especiales para el viaje, accesos a patios, contactos de entrega..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA LATERAL (4 de 12) -->
                        <div class="col-lg-4">
                            <!-- Card: Acciones Principales -->
                            <div class="envios-surface-card p-4 mb-4">
                                <h6 class="fw-bold text-dark mb-3 fs-14 border-bottom pb-2">
                                    <i class="ri-play-list-add-line text-primary me-2 align-middle"></i> Acciones del Envío
                                </h6>
                                <div class="d-grid gap-2">
                                    <button type="button" id="btnActionForm" class="btn btn-primary rounded-pill py-2 shadow-sm fw-bold" onclick="saveEnvio();" style="background-color: var(--brand-primary); border-color: var(--brand-primary);">
                                        <i class="ri-save-3-line align-middle me-1"></i> <span id="btnText">Guardar Envío</span>
                                    </button>
                                    <button type="button" id="btnReabrirEditar" class="btn btn-warning rounded-pill py-2 shadow-sm fw-semibold" onclick="fntReabrirParaEditar()" style="display:none;">
                                        <i class="ri-lock-unlock-line align-middle me-1"></i> Reabrir para Editar
                                    </button>
                                    <button type="button" class="btn btn-light border rounded-pill py-2 fw-semibold text-secondary" onclick="cancelFormEnvio();">
                                        <i class="ri-arrow-go-back-line align-middle fs-16 me-1"></i> Cancelar y Volver
                                    </button>
                                </div>
                            </div>

                            <!-- Card: Estatus del Envío -->
                            <div class="card border-0 shadow-sm mb-4 text-white" style="border-radius: 16px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <span class="text-white-50 text-uppercase fs-11 fw-bold ls-1 d-block mb-1">
                                                Estatus del Envío
                                            </span>
                                            <h4 class="text-white mb-0 fw-bold" id="lbl-estatus-envio-lateral">
                                                En Borrador
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0" id="icon-estatus-envio-lateral">
                                            <i class="ri-route-line text-white fs-28 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-11 mt-3 pt-2 border-top border-secondary border-opacity-25" id="desc-estatus-envio-lateral">
                                        <i class="ri-information-line me-1"></i> Las subidas y bajadas de VINs por tramo se configuran en la siguiente pantalla.
                                    </div>
                                </div>
                            </div>
                    </div>
                </form>
            </section>

            <!-- ── MODAL CAPTURA DISTANCIAS FALTANTES (MEMORIA PROGRESIVA) ── -->
            <div class="modal fade" id="modalDistanciasFaltantes" tabindex="-1" aria-labelledby="modalDistanciasLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                        <div class="modal-header bg-soft-warning border-bottom border-warning py-3">
                            <h5 class="modal-title fw-bold text-dark fs-16" id="modalDistanciasLabel">
                                <i class="ri-route-line text-warning me-2 fs-18"></i> Distancias de Tramos Desconocidas
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-soft-info d-flex align-items-center mb-3 rounded-3">
                                <i class="ri-lightbulb-line fs-22 me-3 text-info flex-shrink-0"></i>
                                <div class="fs-13">
                                    Se detectaron tramos nuevos en la ruta sin distancia en el sistema. 
                                    <strong>Ingresa los kilómetros una sola vez:</strong> el sistema los guardará en su memoria progresiva para utilizarlos en este y futuros envíos.
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="fs-12 text-uppercase text-muted">
                                            <th style="width: 50px;">Tramo</th>
                                            <th>Punto de Origen</th>
                                            <th style="width: 30px;" class="text-center text-muted">➔</th>
                                            <th>Punto de Destino</th>
                                            <th style="width: 170px;">Distancia (KM) <span class="text-danger">*</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyDistanciasFaltantes">
                                        <!-- Se llena dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-sm btn-primary px-4 fw-semibold" id="btnGuardarDistanciasModal" onclick="guardarDistanciasFaltantesModal()">
                                <i class="ri-save-line me-1"></i> Guardar en Memoria y Continuar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
