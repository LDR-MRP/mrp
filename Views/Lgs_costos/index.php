<?php headerAdmin($data); ?>

<style>
/* Modern UI Tokens Scoped to Distancias y Tarifas */
:root {
    --brand-primary: #C46623;
    --brand-primary-hover: #b0571c;
    --brand-dark: #0F172A;
    --surface-card: #FFFFFF;
    --surface-border: #E2E8F0;
    --surface-bg: #F8FAFC;
}

.costos-surface-card {
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
.kpi-card-modern.border-top-info::before { background: #0284C7; }
.kpi-card-modern.border-top-success::before { background: #10B981; }
.kpi-card-modern.border-top-warning::before { background: #F59E0B; }

/* Segmented Tabs Control */
.nav-segmented-wrapper {
    background: #F1F5F9;
    padding: 6px;
    border-radius: 16px;
    display: inline-flex;
    width: 100%;
    border: 1px solid #E2E8F0;
    margin-bottom: 24px;
}
.nav-segmented {
    display: flex;
    width: 100%;
    gap: 6px;
    border: none;
    margin: 0;
    padding: 0;
}
.nav-segmented .nav-item {
    flex: 1;
}
.nav-segmented .nav-link {
    width: 100%;
    border: none !important;
    border-radius: 12px !important;
    padding: 12px 18px;
    font-size: 13.5px;
    font-weight: 600;
    color: #64748B;
    background: transparent;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    align-items: center;
    justify-content: center;
    text-align: center;
    gap: 8px;
}
.nav-segmented .nav-link:hover {
    color: #0F172A;
    background: rgba(255, 255, 255, 0.6);
}
.nav-segmented .nav-link.active {
    background: #FFFFFF !important;
    color: var(--brand-primary) !important;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
}

/* Route Connector in Tab 1 */
.route-connector-badge {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #F8FAFC;
    border: 2px solid #E2E8F0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--brand-primary);
    font-size: 16px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.04);
}

/* Modalidad Interactive Cards in Tab 2 */
.modalidad-card-option {
    border: 2px solid #E2E8F0;
    border-radius: 14px;
    padding: 16px 18px;
    background: #FFFFFF;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    user-select: none;
}
.modalidad-card-option:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
    transform: translateY(-1px);
}
.modalidad-card-option.active {
    border-color: var(--brand-primary);
    background: rgba(196, 102, 35, 0.04);
    box-shadow: 0 4px 14px rgba(196, 102, 35, 0.12);
}
.modalidad-card-option.disabled {
    opacity: 0.65;
    cursor: not-allowed;
    pointer-events: none;
}
.modalidad-check-circle {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    border: 2px solid #CBD5E1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: transparent;
    transition: all 0.2s ease;
}
.modalidad-card-option.active .modalidad-check-circle {
    border-color: var(--brand-primary);
    background: var(--brand-primary);
    color: white;
}

/* Modern Accordion */
.accordion-modern .accordion-item {
    border: 1px solid #E2E8F0;
    border-radius: 14px !important;
    overflow: hidden;
    margin-bottom: 14px;
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.02);
    transition: box-shadow 0.2s ease;
}
.accordion-modern .accordion-item:hover {
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.04);
}
.accordion-modern .accordion-button {
    padding: 16px 20px;
    font-weight: 700;
    box-shadow: none !important;
}
.accordion-modern .accordion-button:not(.collapsed) {
    background-color: #F8FAFC;
    border-bottom: 1px solid #E2E8F0;
}
.accordion-modern .accordion-button.madrina-header {
    border-left: 5px solid var(--brand-primary);
}
.accordion-modern .accordion-button.chofer-header {
    border-left: 5px solid #F59E0B;
}
.accordion-modern .accordion-button.plataforma-header {
    border-left: 5px solid #0284C7;
}

/* Search bar styling */
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

/* Ocultar input default plano de DataTables para usar el buscador moderno */
#tableDistancias_filter {
    display: none;
}
#tableDistancias_wrapper .dataTables_length {
    padding: 12px 16px 6px;
}
#tableDistancias_wrapper .dataTables_length label {
    font-size: 12px;
    color: #64748B;
    font-weight: 500;
}
#tableDistancias_wrapper .dataTables_length select {
    border-radius: 8px;
    border: 1px solid #CBD5E1;
    padding: 4px 8px;
    font-size: 12px;
    margin: 0 4px;
}
#tableDistancias_wrapper .dataTables_info,
#tableDistancias_wrapper .dataTables_paginate {
    padding: 12px 16px;
    font-size: 12px;
}
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-costos">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-3">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-xs rounded-3 px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary fw-semibold">Distancias y Costos</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON ACCIONES -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-sm border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-dark">Distancias y Tarifas de Logística</h3>
                                <p class="text-muted mb-0 fs-13">
                                    Control maestro de kilometrajes geográficos, modalidades de viaje y tarifas personalizadas por transportista.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS MODERNOS -->
                <div class="row mb-4 g-3">
                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-primary p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Distancias Registradas</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0">
                                        <span id="kpi-total-distancias"><?= $data['catalogs']['kpis']['total_distancias'] ?? '0' ?></span>
                                    </h4>
                                    <small class="text-muted fs-11">Rutas geográficas activas</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-road-map-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-info p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Ubicaciones Cubiertas</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0">
                                        <span id="kpi-total-nodos"><?= $data['catalogs']['kpis']['total_nodos'] ?? '0' ?></span>
                                    </h4>
                                    <small class="text-muted fs-11">Plantas, patios y clientes</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                        <i class="ri-map-pin-2-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-success p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Proveedores c/ Tarifa</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0">
                                        <span id="kpi-total-proveedores"><?= $data['catalogs']['kpis']['total_proveedores_config'] ?? '0' ?></span>
                                    </h4>
                                    <small class="text-muted fs-11">Transportistas configurados</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-truck-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6">
                        <div class="kpi-card-modern border-top-warning p-3">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-1 fs-11 ls-1">Segmentos Activos</p>
                                    <h4 class="fs-22 fw-bold text-dark mb-0">
                                        <span id="kpi-total-segmentos"><?= $data['catalogs']['kpis']['total_segmentos'] ?? '0' ?></span>
                                    </h4>
                                    <small class="text-muted fs-11">Ligeros, Medianos, Pesados</small>
                                </div>
                                <div class="avatar-sm flex-shrink-0 ms-2">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                        <i class="ri-caravan-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. PESTAÑAS (SEGMENTED TABS) MODERNAS -->
                <div class="nav-segmented-wrapper shadow-xs">
                    <ul class="nav nav-segmented" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#tab-distancias" role="tab">
                                <i class="ri-map-pin-line fs-16"></i> 1. Matriz de Distancias (KMs)
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-tarifas" role="tab">
                                <i class="ri-truck-line fs-16"></i> 2. Tarifas por Proveedor
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#tab-modelos" role="tab" onclick="loadModelosSegmentos();">
                                <i class="ri-car-line fs-16"></i> 3. Modelos y Segmentos (Catálogo)
                            </a>
                        </li>
                    </ul>
                </div>
                    
                <div class="tab-content">
                    <!-- ============================================== -->
                    <!-- TAB 1: DISTANCIAS -->
                    <!-- ============================================== -->
                    <div class="tab-pane active" id="tab-distancias" role="tabpanel">
                        <!-- Formulario Creador de Trayecto -->
                        <div class="card border border-light-subtle shadow-sm mb-4 bg-white rounded-3">
                            <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs me-2">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-14">
                                                <i class="ri-route-line"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="fs-13 fw-bold mb-0 text-dark text-uppercase">Creador de Trayectos (Ruta Bidireccional)</h6>
                                            <small class="text-muted fs-11">El kilometraje guardado aplica automáticamente en ambos sentidos (Ida y Vuelta).</small>
                                        </div>
                                    </div>
                                    <span class="badge bg-soft-info text-info border border-info px-2 py-1 fs-11">
                                        <i class="ri-shield-check-line me-1"></i>Distancia Compartida
                                    </span>
                                </div>
                            </div>
                            <div class="card-body p-4 bg-white rounded-bottom">
                                <form id="formNuevaDistancia" onsubmit="saveDistancia(event);">
                                    <div class="row g-3 align-items-end">
                                        <div class="col-lg-4 col-md-6">
                                            <label for="dist_ubicacion_a" class="form-label fw-bold text-muted fs-11 text-uppercase mb-1">
                                                <i class="ri-map-pin-user-line me-1 text-success"></i>Ubicación Origen
                                            </label>
                                            <select class="form-select select2-ubicaciones shadow-xs" id="dist_ubicacion_a" name="id_ubicacion_a" required>
                                                <option value="">-- Seleccione Origen --</option>
                                                <?php foreach ($data['catalogs']['ubicaciones'] as $u): ?>
                                                    <option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label for="dist_ubicacion_b" class="form-label fw-bold text-muted fs-11 text-uppercase mb-1">
                                                <i class="ri-flag-line me-1 text-danger"></i>Ubicación Destino
                                            </label>
                                            <select class="form-select select2-ubicaciones shadow-xs" id="dist_ubicacion_b" name="id_ubicacion_b" required>
                                                <option value="">-- Seleccione Destino --</option>
                                                <?php foreach ($data['catalogs']['ubicaciones'] as $u): ?>
                                                    <option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-2 col-md-6">
                                            <label for="dist_km" class="form-label fw-bold text-muted fs-11 text-uppercase mb-1">
                                                <i class="ri-speed-up-line me-1 text-primary"></i>Distancia (KM)
                                            </label>
                                            <div class="input-group shadow-xs">
                                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="ri-dashboard-3-line"></i></span>
                                                <input type="number" step="0.01" class="form-control fw-bold border-start-0 text-end" id="dist_km" name="km" placeholder="0.00" required>
                                                <span class="input-group-text bg-light text-muted fs-12">km</span>
                                            </div>
                                        </div>

                                        <div class="col-lg-2 col-md-6">
                                            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm py-2">
                                                <i class="ri-save-3-line me-1"></i> Guardar Ruta
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Barra de Búsqueda Rápida y Tabla de Distancias -->
                        <div class="card border border-light-subtle shadow-sm bg-white rounded-3 mb-4">
                            <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                                <div class="row align-items-center g-2">
                                    <div class="col-md-6">
                                        <h6 class="fs-13 fw-bold mb-0 text-dark text-uppercase">
                                            <i class="ri-table-line me-1 text-primary"></i>Catálogo de Distancias Registradas
                                        </h6>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex justify-content-md-end">
                                            <div class="search-pill-modern d-flex align-items-center w-100" style="max-width: 340px;">
                                                <i class="ri-search-line text-muted me-2"></i>
                                                <input type="text" id="buscarDistanciaTabla" class="w-100" placeholder="Filtrar trayecto o ciudad en vivo..." oninput="filtrarTablaDistancias(this.value)">
                                                <button class="btn btn-sm btn-link text-muted p-0 ms-1" type="button" onclick="limpiarFiltroDistancias()" title="Limpiar filtro">
                                                    <i class="ri-close-line"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table id="tableDistancias" class="table table-hover align-middle table-nowrap mb-0 w-100 bg-white">
                                        <thead class="table-light text-muted fs-11 text-uppercase">
                                            <tr>
                                                <th class="ps-4">Trayecto (Ruta Bidireccional)</th>
                                                <th style="width: 180px;">Kilómetros (KM)</th>
                                                <th class="text-center pe-4" style="width: 120px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAB 2: TARIFAS POR PROVEEDOR -->
                    <!-- ============================================== -->
                    <div class="tab-pane" id="tab-tarifas" role="tabpanel">
                        <!-- Selector de Proveedor y Botón Guardar -->
                        <div class="card border border-light-subtle shadow-sm mb-3 bg-white rounded-3">
                            <div class="card-body p-3">
                                <div class="row align-items-center g-3">
                                    <div class="col-xl-5 col-lg-6 col-md-12">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm me-3 flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-18">
                                                    <i class="ri-truck-line"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1" style="min-width: 0;">
                                                <label for="select_tarifa_proveedor" class="form-label fw-bold text-muted fs-11 text-uppercase ls-1 mb-1">Configurando Tarifas para:</label>
                                                <select class="form-select form-select-md fw-bold border-primary text-primary shadow-xs" id="select_tarifa_proveedor" onchange="loadTarifasProveedor();">
                                                    <option value="0">🌐 Tarifa Base General (Aplica a todos por defecto)</option>
                                                    <?php if (!empty($data['catalogs']['proveedores'])): ?>
                                                        <optgroup label="── Tarifas Específicas ──">
                                                            <?php foreach ($data['catalogs']['proveedores'] as $prv): ?>
                                                                <option value="<?= $prv['id_proveedor'] ?>">🚚 <?= htmlspecialchars($prv['razon_social']) ?></option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-xl-7 col-lg-6 col-md-12">
                                        <div class="d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-primary fw-semibold d-none shadow-xs" id="btnAjustarAlGlobal" onclick="ajustarAlGlobal();" title="Carga los valores de la Tarifa Base General en este formulario para revisarlos antes de guardar">
                                                <i class="ri-equalizer-line me-1"></i> Ajustar al Global
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger fw-semibold d-none shadow-xs" id="btnRestablecerGlobal" onclick="resetTarifasProveedor();" title="Elimina las tarifas personalizadas de este proveedor para volver a heredar la Tarifa Base General">
                                                <i class="ri-history-line me-1"></i> Restablecer a Base General
                                            </button>
                                            <button type="button" class="btn btn-success fw-bold shadow-sm px-3" id="btnGuardarTarifas" onclick="saveTarifasProveedor();">
                                                <i class="ri-save-3-fill me-1"></i> <span id="btnGuardarTarifasTexto">Guardar Tarifa Base General...</span>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Fila para el Estado / Badge de la Tarifa -->
                                    <div class="col-12 pt-2 border-top border-light-subtle">
                                        <div id="tarifa_status_badge" class="d-flex align-items-center">
                                            <!-- Estado dinámico inyectado por JS -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Acordeón de Configuración de Costos -->
                        <form id="formTarifasProveedor">
                            <!-- CONFIGURACIÓN DE MODALIDAD POR DEFECTO (INTERACTIVA) -->
                            <div class="card border border-light-subtle shadow-sm mb-3 bg-white rounded-3">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom border-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-3 flex-shrink-0">
                                                <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16">
                                                    <i class="ri-route-line"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h6 class="fs-13 fw-bold mb-0 text-dark text-uppercase">
                                                    Modalidad de Envío por Defecto (Madrinas y Plataformas)
                                                </h6>
                                                <small class="text-muted fs-11" id="txtHelpModalidadDefault">
                                                    Regla de cálculo sugerida automáticamente al programar viajes con este transportista.
                                                </small>
                                            </div>
                                        </div>
                                        <span class="badge bg-light text-muted border px-2 py-1 fs-11" id="badgeModalidadEstado">
                                            Configuración por Proveedor
                                        </span>
                                    </div>

                                    <!-- Selector sincronizado para envío de formulario -->
                                    <select class="d-none" id="modalidad_envio_default_prov" name="modalidad_envio_default">
                                        <option value="DIRECTO">DIRECTO</option>
                                        <option value="ESCALONADO">ESCALONADO</option>
                                    </select>

                                    <!-- Tarjetas Interactivas de Selección -->
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <div class="modalidad-card-option active" id="cardModDirecto" onclick="seleccionarModalidadCard('DIRECTO');">
                                                <div class="d-flex align-items-start justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-2 flex-shrink-0">
                                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-14">
                                                                <i class="ri-direction-line"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <h6 class="fs-13 fw-bold mb-0 text-dark">Directo con Escalas por Origen</h6>
                                                            <span class="badge bg-primary text-white fs-10 px-2 py-0">Recomendado / Default</span>
                                                        </div>
                                                    </div>
                                                    <div class="modalidad-check-circle"><i class="ri-check-line"></i></div>
                                                </div>
                                                <p class="fs-11 text-muted mt-2 mb-0">
                                                    Factoriza el total de unidades a bordo por sub-envío hacia cada destino individual. Ideal para rutas directas con descargas progresivas.
                                                </p>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="modalidad-card-option" id="cardModEscalonado" onclick="seleccionarModalidadCard('ESCALONADO');">
                                                <div class="d-flex align-items-start justify-content-between">
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar-xs me-2 flex-shrink-0">
                                                            <span class="avatar-title bg-warning-subtle text-warning-emphasis rounded-circle fs-14">
                                                                <i class="ri-git-commit-line"></i>
                                                            </span>
                                                        </div>
                                                        <div>
                                                            <h6 class="fs-13 fw-bold mb-0 text-dark">Escalonado Tradicional</h6>
                                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning fs-10 px-2 py-0">Por Tramos Consecutivos</span>
                                                        </div>
                                                    </div>
                                                    <div class="modalidad-check-circle"><i class="ri-check-line"></i></div>
                                                </div>
                                                <p class="fs-11 text-muted mt-2 mb-0">
                                                    Divide el costo tramo por tramo cronológico (Nodo A &rarr; Nodo B &rarr; Nodo C). Ideal para traslados troncales compartidos.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion accordion-modern" id="accordionTarifas">
                                <!-- MADRINA -->
                                <div class="accordion-item shadow-sm border-0 mb-3 rounded-3">
                                    <h2 class="accordion-header" id="headingMadrina">
                                        <button class="accordion-button madrina-header fw-bold fs-15 bg-white text-dark rounded-top" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMadrina" aria-expanded="true" aria-controls="collapseMadrina">
                                            <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3 flex-shrink-0">
                                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-16">
                                                            <i class="ri-truck-line"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="d-block text-dark fw-bold">1. Tarifas de Madrina (Con factores de Volumen)</span>
                                                        <small class="text-muted fw-normal fs-11">Desglose dinámico por unidad (2 a 9 autos) y tarifas planas locales SLC / SLL</small>
                                                    </div>
                                                </div>
                                                <span class="badge bg-primary-subtle text-primary border border-primary px-2 py-1 fs-11 d-none d-md-inline-block">
                                                    Hasta 9 Unidades
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapseMadrina" class="accordion-collapse collapse show" aria-labelledby="headingMadrina" data-bs-parent="#accordionTarifas">
                                        <div class="accordion-body bg-white pt-2">
                                            <div class="d-flex justify-content-end mb-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-2 rounded-pill px-3 fs-11 fw-semibold shadow-2xs" onclick="toggleFactoresMadrina(true);">
                                                    <i class="ri-arrow-down-s-line me-1"></i> Expandir Factores
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fs-11 fw-semibold shadow-2xs" onclick="toggleFactoresMadrina(false);">
                                                    <i class="ri-arrow-up-s-line me-1"></i> Contraer Factores
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr class="text-uppercase fs-11 text-muted">
                                                            <th style="width: 250px;">Segmento Vehicular</th>
                                                            <th style="display: none;">Costo Base / KM ($)</th>
                                                            <th style="width: 170px;">Local Corto SLC<br><small class="text-muted">(0-40 KM)</small><br><span class="badge bg-warning text-dark fs-10 mt-1">&ge; 3 unidades</span></th>
                                                            <th style="width: 170px;">Local Largo SLL<br><small class="text-muted">(41-80 KM)</small><br><span class="badge bg-warning text-dark fs-10 mt-1">&ge; 3 unidades</span></th>
                                                            <th>Desglose de Factores por Unidad (2 a 9 Unidades)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyTarifasMadrina">
                                                        <!-- Renderizado vía JS -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CHOFER RODANDO -->
                                <div class="accordion-item shadow-sm border-0 rounded-3 mb-3">
                                    <h2 class="accordion-header" id="headingChofer">
                                        <button class="accordion-button chofer-header collapsed fw-bold fs-15 bg-white text-dark rounded-top" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChofer" aria-expanded="false" aria-controls="collapseChofer">
                                            <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3 flex-shrink-0">
                                                        <span class="avatar-title bg-warning-subtle text-warning-emphasis rounded-circle fs-16">
                                                            <i class="ri-steering-2-line"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="d-block text-dark fw-bold">2. Tarifas Chofer / Rodando (Tarifa Fija 1 Unidad)</span>
                                                        <small class="text-muted fw-normal fs-11">Cobro directo por kilómetro conducido para 1 vehículo</small>
                                                    </div>
                                                </div>
                                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning px-2 py-1 fs-11 d-none d-md-inline-block">
                                                    1 Vehículo
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapseChofer" class="accordion-collapse collapse" aria-labelledby="headingChofer" data-bs-parent="#accordionTarifas">
                                        <div class="accordion-body bg-white pt-2">
                                            <div class="alert alert-warning border-0 d-flex align-items-center mb-3 p-3 rounded-3 shadow-2xs">
                                                <i class="ri-information-line fs-20 text-warning me-3"></i>
                                                <div class="fs-12">Para traslados rodando (1 operador manejando 1 vehículo), ingrese el costo estándar por KM para cada segmento vehicular.</div>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr class="text-uppercase fs-11 text-muted">
                                                            <th style="width: 250px;">Segmento Vehicular</th>
                                                            <th style="width: 170px;">Costo / KM ($)</th>
                                                            <th style="width: 170px;">Local Corto SLC<br><small class="text-muted">(0-40 KM)</small></th>
                                                            <th style="width: 170px;">Local Largo SLL<br><small class="text-muted">(41-80 KM)</small></th>
                                                            <th></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyTarifasChofer">
                                                        <!-- Renderizado vía JS -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- PLATAFORMA -->
                                <div class="accordion-item shadow-sm border-0 rounded-3">
                                    <h2 class="accordion-header" id="headingPlataforma">
                                        <button class="accordion-button plataforma-header collapsed fw-bold fs-15 bg-white text-dark rounded-top" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePlataforma" aria-expanded="false" aria-controls="collapsePlataforma">
                                            <div class="d-flex align-items-center justify-content-between w-100 me-3">
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-xs me-3 flex-shrink-0">
                                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                            <i class="ri-truck-fill"></i>
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="d-block text-dark fw-bold">3. Tarifas Plataforma (Capacidad hasta 3 Unidades)</span>
                                                        <small class="text-muted fw-normal fs-11">Costos diferenciados para traslados en grúa o plataforma plana</small>
                                                    </div>
                                                </div>
                                                <span class="badge bg-info-subtle text-info border border-info px-2 py-1 fs-11 d-none d-md-inline-block">
                                                    1 a 3 Unidades
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapsePlataforma" class="accordion-collapse collapse" aria-labelledby="headingPlataforma" data-bs-parent="#accordionTarifas">
                                        <div class="accordion-body bg-white pt-2">
                                            <div class="alert alert-info border-0 d-flex align-items-center mb-3 p-3 rounded-3 shadow-2xs">
                                                <i class="ri-information-line fs-20 text-info me-3"></i>
                                                <div class="fs-12">Los precios de <b>Local Corto (SLC)</b> y <b>Local Largo (SLL)</b> en plataforma se configuran dentro de cada factor, ya que varían según la cantidad de unidades transportadas (1 unidad vs 2 o más unidades).</div>
                                            </div>
                                            <div class="d-flex justify-content-end mb-3">
                                                <button type="button" class="btn btn-sm btn-outline-secondary me-2 rounded-pill px-3 fs-11 fw-semibold shadow-2xs" onclick="toggleFactoresPlataforma(true);">
                                                    <i class="ri-arrow-down-s-line me-1"></i> Expandir Factores
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fs-11 fw-semibold shadow-2xs" onclick="toggleFactoresPlataforma(false);">
                                                    <i class="ri-arrow-up-s-line me-1"></i> Contraer Factores
                                                </button>
                                            </div>
                                            <div class="table-responsive">
                                                <table class="table table-bordered align-middle mb-0">
                                                    <thead class="table-light">
                                                        <tr class="text-uppercase fs-11 text-muted">
                                                            <th style="width: 250px;">Segmento Vehicular</th>
                                                            <th style="display: none;">Costo Base / KM ($)</th>
                                                            <th style="display: none;">Local Corto SLC<br><small>(0-40 KM)</small></th>
                                                            <th style="display: none;">Local Largo SLL<br><small>(41-80 KM)</small></th>
                                                            <th>Desglose de Factores (1 a 3 Unidades y Lowboy)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody id="tbodyTarifasPlataforma">
                                                        <!-- Renderizado vía JS -->
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- ============================================== -->
                    <!-- TAB 3: MODELOS Y SEGMENTOS                     -->
                    <!-- ============================================== -->
                    <div class="tab-pane" id="tab-modelos" role="tabpanel">
                        <div class="card border border-light-subtle shadow-sm mb-4 bg-white rounded-3">
                            <div class="card-body p-4 bg-white rounded">
                                <!-- Header de la sección -->
                                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4 pb-3 border-bottom border-light-subtle">
                                    <div>
                                        <h5 class="fs-16 fw-bold mb-1 text-dark">
                                            <i class="ri-car-line me-2 text-primary"></i>Catálogo de Modelos y Segmentación
                                        </h5>
                                        <p class="text-muted fs-13 mb-0">
                                            Clasifica los modelos vehiculares en sus segmentos correspondientes (Ligeros, Medianos, Pesados, Buses, Lowboy) para el cálculo preciso de fletes y traslados rodando.
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm fw-semibold rounded-pill px-3 shadow-2xs" onclick="loadModelosSegmentos();">
                                            <i class="ri-refresh-line me-1"></i> Actualizar
                                        </button>
                                        <button type="button" class="btn btn-primary btn-sm fw-bold shadow-sm rounded-pill px-3" onclick="abrirModalNuevoModelo();">
                                            <i class="ri-add-circle-line me-1"></i> Registrar Modelo
                                        </button>
                                    </div>
                                </div>

                                <!-- KPIs resumidos de modelos -->
                                <div class="row g-3 mb-4">
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-light rounded-3 border text-center shadow-2xs">
                                            <span class="text-muted fs-11 fw-semibold text-uppercase d-block mb-1">Total Modelos</span>
                                            <h4 class="fs-22 fw-bold mb-0 text-dark" id="kpi-mod-total">0</h4>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-success-subtle rounded-3 border border-success-subtle text-center shadow-2xs">
                                            <span class="text-success fs-11 fw-bold text-uppercase d-block mb-1">Ligeros ($18/km)</span>
                                            <h4 class="fs-22 fw-bold mb-0 text-success" id="kpi-mod-ligeros">0</h4>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-danger-subtle rounded-3 border border-danger-subtle text-center shadow-2xs">
                                            <span class="text-danger fs-11 fw-bold text-uppercase d-block mb-1">Pesados ($25/km)</span>
                                            <h4 class="fs-22 fw-bold mb-0 text-danger" id="kpi-mod-pesados">0</h4>
                                        </div>
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <div class="p-3 bg-primary-subtle rounded-3 border border-primary-subtle text-center shadow-2xs">
                                            <span class="text-primary fs-11 fw-bold text-uppercase d-block mb-1">Medianos ($20/km)</span>
                                            <h4 class="fs-22 fw-bold mb-0 text-primary" id="kpi-mod-medianos">0</h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Filtros y Búsqueda -->
                                <div class="row g-3 align-items-center mb-3">
                                    <div class="col-md-5">
                                        <div class="search-pill-modern d-flex align-items-center w-100">
                                            <i class="ri-search-line text-muted me-2"></i>
                                            <input type="text" class="w-100" id="inputBuscarModelo" placeholder="Buscar modelo o prefijo VIN..." onkeyup="filtrarTablaModelos();">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select form-select-sm shadow-xs rounded-pill px-3" id="selectFiltroSegmento" onchange="filtrarTablaModelos();">
                                            <option value="">-- Todos los Segmentos --</option>
                                            <option value="1">LIGEROS (LDT)</option>
                                            <option value="2">MEDIANO (MDT)</option>
                                            <option value="3">PESADO (HDT)</option>
                                            <option value="4">BUSES</option>
                                            <option value="5">LOWBOY</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 text-md-end text-muted fs-12">
                                        <span id="lblContadorModelos">Mostrando 0 modelos</span>
                                    </div>
                                </div>

                                <!-- Tabla de Modelos -->
                                <div class="table-responsive">
                                    <table class="table table-hover table-bordered align-middle mb-0" id="tablaModelosSegmentos">
                                        <thead class="table-light">
                                            <tr class="fs-11 text-uppercase text-muted">
                                                <th style="width: 60px;" class="text-center">#</th>
                                                <th>Modelo Vehicular</th>
                                                <th style="width: 180px;">Prefijo / VIN Base</th>
                                                <th style="width: 220px;" class="text-center">Segmento Actual</th>
                                                <th style="width: 180px;" class="text-center">Tarifa Rodando</th>
                                                <th style="width: 170px;" class="text-center">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyModelosSegmentos">
                                            <tr>
                                                <td colspan="6" class="text-center py-4 text-muted">
                                                    <div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando catálogo de modelos...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </section>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: REPLICAR TARIFA BASE GENERAL A PROVEEDORES              -->
<!-- ============================================================== -->
<div class="modal fade" id="modalReplicarTarifaBase" tabindex="-1" aria-labelledby="modalReplicarTarifaBaseLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <div>
                    <h5 class="modal-title fw-bold text-white fs-16" id="modalReplicarTarifaBaseLabel">
                        <i class="ri-git-branch-line me-1"></i> Guardar y Aplicar Tarifa Base General
                    </h5>
                    <p class="fs-12 text-white-50 mb-0">Seleccione los proveedores a los cuales desea actualizarles sus tarifas con esta nueva base general.</p>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-light-subtle">
                <!-- Opciones de propagación -->
                <div class="card border border-primary-subtle bg-white mb-3 shadow-xs">
                    <div class="card-body p-3">
                        <label class="form-label fw-bold text-dark fs-13 mb-2">
                            <i class="ri-settings-4-line text-primary me-1"></i> Modo de Propagación:
                        </label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-light-subtle rounded border h-100">
                                    <input class="form-check-input ms-1 me-2" type="radio" name="modo_propagacion" id="modoPropagarRespetar" value="respetar" checked onchange="onCambioModoPropagacion(this.value);">
                                    <label class="form-check-label fw-semibold text-dark fs-12 cursor-pointer" for="modoPropagarRespetar">
                                        <span class="d-block text-success fw-bold"><i class="ri-shield-check-line me-1"></i> Mantener personalizadas (Recomendado)</span>
                                        <small class="text-muted fs-11">Solo actualiza la base y proveedores con Tarifa General. No altera transportistas configurados manualmente.</small>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check p-2 bg-light-subtle rounded border h-100">
                                    <input class="form-check-input ms-1 me-2" type="radio" name="modo_propagacion" id="modoPropagarForzar" value="forzar" onchange="onCambioModoPropagacion(this.value);">
                                    <label class="form-check-label fw-semibold text-dark fs-12 cursor-pointer" for="modoPropagarForzar">
                                        <span class="d-block text-danger fw-bold"><i class="ri-error-warning-line me-1"></i> Forzar actualización a todos</span>
                                        <small class="text-muted fs-11">Sobreescribe TODOS los seleccionados, reemplazando incluso tarifas manuales previas.</small>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Barra de herramientas y filtros rápidos -->
                <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 mb-3 bg-white p-2 rounded-2 border">
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-medium" onclick="filtrarSeleccionReplicar('todos');">
                            <i class="ri-checkbox-line me-1"></i> Seleccionar Todos
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success fw-medium" onclick="filtrarSeleccionReplicar('solo_base');" title="Selecciona solo los que no tienen tarifas personalizadas">
                            <i class="ri-filter-line me-1"></i> Solo los de Tarifa General
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary fw-medium" onclick="filtrarSeleccionReplicar('ninguno');">
                            <i class="ri-close-circle-line me-1"></i> Limpiar
                        </button>
                    </div>
                    <div>
                        <span class="badge bg-primary fs-12 px-3 py-2 rounded-pill shadow-xs" id="lblContadorReplicar">
                            0 de 0 seleccionados
                        </span>
                    </div>
                </div>

                <!-- Lista de Proveedores con Checkboxes -->
                <div class="card border mb-0 shadow-none">
                    <div class="card-body p-0" style="max-height: 320px; overflow-y: auto;">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0">
                                <thead class="table-light sticky-top">
                                    <tr class="fs-11 text-uppercase text-muted">
                                        <th style="width: 50px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="chkReplicarMaster" onchange="toggleReplicarMaster(this);">
                                        </th>
                                        <th>Proveedor / Transportista</th>
                                        <th style="width: 190px;" class="text-center">Estado Actual</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyReplicarProveedores">
                                    <tr>
                                        <td colspan="3" class="text-center py-4 text-muted">
                                            <div class="spinner-border spinner-border-sm text-primary me-2"></div> Cargando transportistas...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div id="alertaPersonalizadosSeleccionados" class="alert alert-warning border-0 mt-3 mb-0 p-2 fs-12 d-none rounded-2">
                    <i class="ri-alert-line me-1 fw-bold text-warning"></i> 
                    <span id="txtAlertaPersonalizados">Ha seleccionado proveedores con tarifas personalizadas previas; sus tarifas anteriores serán reemplazadas por la nueva base general.</span>
                </div>
            </div>
            <div class="modal-footer bg-white border-top d-flex justify-content-between p-3">
                <button type="button" class="btn btn-soft-secondary fw-semibold" data-bs-dismiss="modal">
                    Cancelar
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary fw-semibold shadow-xs" onclick="ejecutarGuardadoConReplicacion(true);">
                        <i class="ri-save-line me-1"></i> Guardar solo Base (Sin replicar)
                    </button>
                    <button type="button" class="btn btn-success fw-bold shadow-sm" id="btnConfirmarReplicar" onclick="ejecutarGuardadoConReplicacion(false);">
                        <i class="ri-check-double-line me-1"></i> Guardar y Replicar a Seleccionados
                    </button>
                </div>
            </div>
        </div>
    </div>
<!-- ============================================================== -->
<!-- MODAL: ASIGNAR / CAMBIAR SEGMENTO A MODELO                     -->
<!-- ============================================================== -->
<div class="modal fade" id="modalAsignarSegmento" tabindex="-1" aria-labelledby="modalAsignarSegmentoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title fw-bold text-white fs-16" id="modalAsignarSegmentoLabel">
                    <i class="ri-link me-1"></i> Asignar Segmento Vehicular
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <input type="hidden" id="asig_id_modelo_vin" value="">
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Modelo</label>
                    <input type="text" class="form-control fw-bold bg-light" id="asig_nombre_modelo" readonly>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Seleccionar Segmento</label>
                    <select class="form-select" id="asig_select_segmento" required>
                        <option value="1">1 - LIGEROS (Tarifa Chofer: $18.00 / km)</option>
                        <option value="2">2 - MEDIANO (Tarifa Chofer: $20.00 / km)</option>
                        <option value="3">3 - PESADO (Tarifa Chofer: $25.00 / km)</option>
                        <option value="4">4 - BUSES (Tarifa Chofer: $25.00 / km)</option>
                        <option value="5">5 - LOWBOY (Tarifa Chofer: $25.00 / km)</option>
                    </select>
                </div>
                <div class="alert alert-info border-0 p-3 fs-12 rounded mb-0">
                    <i class="ri-information-line me-1 fw-bold"></i> Al asignar este segmento, todos los VINs y traslados por Chofer (Rodando) de este modelo calcularán sus costos automáticamente con la tarifa establecida para dicho segmento.
                </div>
            </div>
            <div class="modal-footer bg-light border-top p-3">
                <button type="button" class="btn btn-soft-secondary fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary fw-bold shadow-sm" onclick="guardarSegmentoModelo();">
                    <i class="ri-save-line me-1"></i> Guardar Asignación
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL: REGISTRAR NUEVO MODELO VEHICULAR                        -->
<!-- ============================================================== -->
<div class="modal fade" id="modalNuevoModelo" tabindex="-1" aria-labelledby="modalNuevoModeloLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold text-white fs-16" id="modalNuevoModeloLabel">
                    <i class="ri-add-circle-line me-1"></i> Registrar Nuevo Modelo
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4 bg-white">
                <form id="formNuevoModelo" onsubmit="guardarNuevoModelo(event);">
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Nombre del Modelo <span class="text-danger">*</span></label>
                        <input type="text" class="form-control fw-bold" id="nuevo_modelo_nombre" placeholder="Ej. Tunland G7 4K22-DC" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Prefijo / VIN Base (Opcional)</label>
                        <input type="text" class="form-control" id="nuevo_modelo_vin_base" placeholder="Ej. 3LDC2A2F">
                        <small class="text-muted fs-11">Si se especifica, cualquier unidad con este prefijo de VIN se vinculará automáticamente a este modelo.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Segmento Vehicular <span class="text-danger">*</span></label>
                        <select class="form-select" id="nuevo_modelo_segmento" required>
                            <option value="1">1 - LIGEROS (Tarifa Chofer: $18.00 / km)</option>
                            <option value="2">2 - MEDIANO (Tarifa Chofer: $20.00 / km)</option>
                            <option value="3">3 - PESADO (Tarifa Chofer: $25.00 / km)</option>
                            <option value="4">4 - BUSES (Tarifa Chofer: $25.00 / km)</option>
                            <option value="5">5 - LOWBOY (Tarifa Chofer: $25.00 / km)</option>
                        </select>
                    </div>
                    <div class="modal-footer bg-light border-top p-3 px-0 pb-0">
                        <button type="button" class="btn btn-soft-secondary fw-semibold" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success fw-bold shadow-sm">
                            <i class="ri-save-line me-1"></i> Registrar y Catalogar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- DATA PASSTHROUGH PARA JAVASCRIPT -->
<script>
    const sysSegmentos = <?= json_encode($data['catalogs']['segmentos']) ?>;
</script>

<?php footerAdmin($data); ?>
