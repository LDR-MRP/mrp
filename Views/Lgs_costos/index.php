<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-index-costos">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-3">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Distancias y Costos</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON ACCIONES -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-money-dollar-circle-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Distancias y Tarifas de Logística</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión separada de distancias (KMs) geográficas y de tarifas cobradas por proveedores.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Distancias Registradas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-distancias"><?= $data['catalogs']['kpis']['total_distancias'] ?? '0' ?></span>
                                        </h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-road-map-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Ubicaciones Cubiertas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-nodos"><?= $data['catalogs']['kpis']['total_nodos'] ?? '0' ?></span>
                                        </h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-map-pin-2-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Proveedores c/ Tarifa</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-proveedores"><?= $data['catalogs']['kpis']['total_proveedores_config'] ?? '0' ?></span>
                                        </h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Segmentos Activos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-segmentos"><?= $data['catalogs']['kpis']['total_segmentos'] ?? '0' ?></span>
                                        </h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-caravan-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. PESTAÑAS (TABS) PRINCIPALES -->
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-header bg-white border-bottom-0 pb-0 pt-4 px-4">
                        <ul class="nav nav-tabs-custom rounded card-header-tabs border-bottom-0" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active fw-bold fs-15 text-uppercase px-4" data-bs-toggle="tab" href="#tab-distancias" role="tab">
                                    <i class="ri-map-pin-line me-1 align-bottom"></i> 1. Matriz de Distancias (KMs)
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link fw-bold fs-15 text-uppercase px-4" data-bs-toggle="tab" href="#tab-tarifas" role="tab">
                                    <i class="ri-truck-line me-1 align-bottom"></i> 2. Tarifas por Proveedor
                                </a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="card-body p-4 bg-light-subtle">
                        <div class="tab-content">
                            <!-- ============================================== -->
                            <!-- TAB 1: DISTANCIAS -->
                            <!-- ============================================== -->
                            <div class="tab-pane active" id="tab-distancias" role="tabpanel">
                                <!-- Formulario Alta de Distancia -->
                                <div class="card border border-light-subtle shadow-none mb-4">
                                    <div class="card-body p-3 bg-white rounded">
                                        <form id="formNuevaDistancia" onsubmit="saveDistancia(event);">
                                            <div class="row g-3 align-items-end">
                                                <div class="col-md-3">
                                                    <label for="dist_ubicacion_a" class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Ubicación Origen</label>
                                                    <select class="form-select select2-ubicaciones" id="dist_ubicacion_a" name="id_ubicacion_a" required>
                                                        <option value="">--Seleccione Ubicación--</option>
                                                        <?php foreach ($data['catalogs']['ubicaciones'] as $u): ?>
                                                            <option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="dist_ubicacion_b" class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Ubicación Destino</label>
                                                    <select class="form-select select2-ubicaciones" id="dist_ubicacion_b" name="id_ubicacion_b" required>
                                                        <option value="">--Seleccione Ubicación--</option>
                                                        <?php foreach ($data['catalogs']['ubicaciones'] as $u): ?>
                                                            <option value="<?= $u['id_ubicacion'] ?>"><?= htmlspecialchars($u['nombre']) ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-3">
                                                    <label for="dist_km" class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Distancia en KM</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text bg-light border-end-0"><i class="ri-dashboard-3-line text-primary"></i></span>
                                                        <input type="number" step="0.01" class="form-control fw-bold border-start-0" id="dist_km" name="km" placeholder="Ej. 150.50" required>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                                                        <i class="ri-save-3-line me-1"></i> Guardar Distancia
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Tabla de Distancias -->
                                <div class="table-responsive table-card">
                                    <table id="tableDistancias" class="table table-hover align-middle table-nowrap mb-0 w-100 bg-white">
                                        <thead class="table-light text-muted">
                                            <tr>
                                                <th>Trayecto (Bidireccional)</th>
                                                <th style="width: 150px;">Kilómetros (KM)</th>
                                                <th class="text-center" style="width: 120px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- DataTables -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ============================================== -->
                            <!-- TAB 2: TARIFAS POR PROVEEDOR -->
                            <!-- ============================================== -->
                            <div class="tab-pane" id="tab-tarifas" role="tabpanel">
                                <!-- Selector de Proveedor y Botón Guardar -->
                                <div class="card border border-light-subtle shadow-none mb-3">
                                    <div class="card-body p-3 bg-white rounded d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                        <div class="d-flex align-items-center" style="min-width: 320px;">
                                            <div class="avatar-xs me-3 flex-shrink-0">
                                                <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                                                    <i class="ri-truck-line"></i>
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <label for="select_tarifa_proveedor" class="form-label fw-bold text-muted fs-12 text-uppercase mb-1">Configurando Tarifas para:</label>
                                                <select class="form-select form-select-sm fw-bold border-primary text-primary" id="select_tarifa_proveedor" onchange="loadTarifasProveedor();">
                                                    <option value="0">🌐 Tarifa Base General (Aplica a todos por defecto)</option>
                                                    <?php if (!empty($data['catalogs']['proveedores'])): ?>
                                                        <optgroup label="── Tarifas Específicas ──">
                                                            <?php foreach ($data['catalogs']['proveedores'] as $prv): ?>
                                                                <option value="<?= $prv['id_proveedor'] ?>">🚚 <?= htmlspecialchars($prv['razon_social']) ?></option>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endif; ?>
                                                </select>
                                                <div id="tarifa_status_badge" class="mt-2">
                                                    <!-- Estado dinámico inyectado por JS -->
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <button type="button" class="btn btn-sm btn-outline-danger fw-medium d-none shadow-xs" id="btnRestablecerGlobal" onclick="resetTarifasProveedor();" title="Elimina las tarifas personalizadas de este proveedor para volver a heredar la Tarifa Base General">
                                                <i class="ri-history-line me-1"></i> Restablecer a Base General
                                            </button>
                                            <button type="button" class="btn btn-success fw-bold shadow-sm" id="btnGuardarTarifas" onclick="saveTarifasProveedor();">
                                                <i class="ri-save-3-fill me-1"></i> <span id="btnGuardarTarifasTexto">Guardar Tarifa Base General...</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acordeón de Configuración de Costos -->
                                <form id="formTarifasProveedor">
                                    <div class="accordion custom-accordionwithicon accordion-border-box" id="accordionTarifas">
                                        <!-- MADRINA -->
                                        <div class="accordion-item shadow-sm border-0 mb-3 rounded-3">
                                            <h2 class="accordion-header" id="headingMadrina">
                                                <button class="accordion-button fw-bold fs-15 bg-primary-subtle text-primary rounded-top" type="button" data-bs-toggle="collapse" data-bs-target="#collapseMadrina" aria-expanded="true" aria-controls="collapseMadrina">
                                                    <i class="ri-truck-line me-2 fs-18"></i> 1. Tarifas de Madrina (Con factores de Volumen)
                                                </button>
                                            </h2>
                                            <div id="collapseMadrina" class="accordion-collapse collapse show" aria-labelledby="headingMadrina" data-bs-parent="#accordionTarifas">
                                                <div class="accordion-body bg-white">
                                                    <div class="d-flex justify-content-end mb-2">
                                                        <button type="button" class="btn btn-sm btn-soft-secondary me-2" onclick="toggleFactoresMadrina(true);"><i class="ri-arrow-down-s-line"></i> Expandir Factores</button>
                                                        <button type="button" class="btn btn-sm btn-soft-secondary" onclick="toggleFactoresMadrina(false);"><i class="ri-arrow-up-s-line"></i> Contraer Factores</button>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered align-middle mb-0">
                                                            <thead class="table-light">
                                                                <tr class="text-uppercase fs-12 text-muted">
                                                                    <th style="width: 250px;">Segmento</th>
                                                                    <th style="width: 150px;">Costo Base / KM ($)</th>
                                                                    <th style="display: none;">Precio Plano Fijo</th>
                                                                    <th>Desglose de Factores (1 a 15 Unidades)</th>
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
                                        <div class="accordion-item shadow-sm border-0 rounded-3">
                                            <h2 class="accordion-header" id="headingChofer">
                                                <button class="accordion-button collapsed fw-bold fs-15 bg-warning-subtle text-warning-emphasis rounded-top" type="button" data-bs-toggle="collapse" data-bs-target="#collapseChofer" aria-expanded="false" aria-controls="collapseChofer">
                                                    <i class="ri-steering-2-line me-2 fs-18"></i> 2. Tarifas Chofer / Rodando (Tarifa Fija 1 Unidad)
                                                </button>
                                            </h2>
                                            <div id="collapseChofer" class="accordion-collapse collapse" aria-labelledby="headingChofer" data-bs-parent="#accordionTarifas">
                                                <div class="accordion-body bg-white">
                                                    <div class="alert alert-warning border-0 d-flex align-items-center mb-3 p-3 rounded">
                                                        <i class="ri-information-line fs-20 text-warning me-3"></i>
                                                        <div>Para traslados rodando (1 chofer manejando 1 vehículo), ingrese el costo estándar por KM.</div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered align-middle mb-0">
                                                            <thead class="table-light">
                                                                <tr class="text-uppercase fs-12 text-muted">
                                                                    <th style="width: 250px;">Segmento</th>
                                                                    <th style="width: 200px;">Costo / KM ($)</th>
                                                                    <th style="display: none;">Precio Plano Fijo</th>
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
                                    </div>
                                </form>
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
                <!-- Alerta informativa -->
                <div class="alert alert-info border-0 shadow-xs d-flex align-items-center mb-3 p-3 rounded-3">
                    <i class="ri-information-fill fs-22 text-info me-3 flex-shrink-0"></i>
                    <div class="fs-12">
                        Los proveedores que <b>no seleccione</b> conservarán intactas sus tarifas personalizadas actuales sin sufrir ninguna modificación.
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
</div>

<!-- DATA PASSTHROUGH PARA JAVASCRIPT -->
<script>
    const sysSegmentos = <?= json_encode($data['catalogs']['segmentos']) ?>;
</script>

<?php footerAdmin($data); ?>
