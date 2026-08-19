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
                                    <li class="breadcrumb-item active text-primary">Administración de Costos</li>
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
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Administrador de Tarifas Logísticas</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión centralizada de costos por ruta, tipo de traslado y matriz de 15 factores por capacidad de madrina y segmento.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-light border dropdown-toggle rounded-pill px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="ri-download-2-line align-middle fs-16 me-1 text-primary"></i> Descargar Tarifarios
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="border-radius: 14px;">
                                <li>
                                    <a class="dropdown-item py-2" href="<?= base_url(); ?>/Lgs_costos/descargarPlantillaCSV?tipo=2">
                                        <i class="ri-steering-2-line text-warning me-2 fs-16"></i> <b>Tarifario Rodando (Chofer)</b>
                                        <span class="text-muted d-block fs-11 ps-4">1 Unidad Fija por viaje</span>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="<?= base_url(); ?>/Lgs_costos/descargarPlantillaCSV?tipo=1">
                                        <i class="ri-truck-line text-primary me-2 fs-16"></i> <b>Tarifario Madrinas (Factores 1-15)</b>
                                        <span class="text-muted d-block fs-11 ps-4">Desglose de 15 capacidades</span>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2" href="<?= base_url(); ?>/Lgs_costos/descargarPlantillaCSV?tipo=all">
                                        <i class="ri-file-list-3-line text-success me-2 fs-16"></i> <b>Tarifario Consolidado Completo</b>
                                        <span class="text-muted d-block fs-11 ps-4">Todas las rutas y registros de BD</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-soft-primary rounded-pill px-3 py-2 fw-semibold me-2 shadow-xs" onclick="openImportModal();">
                            <i class="ri-upload-cloud-2-line align-middle fs-16 me-1"></i> Importar CSV
                        </button>
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" onclick="openNuevaRutaModal();">
                            <i class="ri-add-line align-middle fs-16 me-1"></i> Nueva Ruta
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Rutas Configuradas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-rutas"><?= $data['catalogs']['kpis']['total_rutas'] ?? '0' ?></span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Puntos de Origen</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-origenes"><?= $data['catalogs']['kpis']['total_origenes'] ?? '0' ?></span>
                                        </h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-building-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Destinos Cubiertos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-0">
                                            <span id="kpi-total-destinos"><?= $data['catalogs']['kpis']['total_destinos'] ?? '0' ?></span>
                                        </h4>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
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

                <!-- 4. TABLA PRINCIPAL DE RUTAS -->
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 rounded-3">
                            <div class="card-header border-0 bg-light-subtle py-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                                <h5 class="card-title mb-0 fw-bold"><i class="ri-route-line align-middle text-primary me-2"></i> Catálogo de Rutas y Matriz de Precios</h5>
                                <div class="btn-group btn-group-sm" role="group" id="filterModalidadButtons">
                                    <button type="button" class="btn btn-outline-primary active" id="btnFilterAll" onclick="filterTableByModalidad('');">
                                        <i class="ri-apps-line me-1"></i> Todas las Rutas (82)
                                    </button>
                                    <button type="button" class="btn btn-outline-primary" id="btnFilterMadrina" onclick="filterTableByModalidad('Madrina');">
                                        <i class="ri-truck-line me-1 text-primary"></i> 🚛 Madrinas (41)
                                    </button>
                                    <button type="button" class="btn btn-outline-warning text-dark" id="btnFilterChofer" onclick="filterTableByModalidad('Chofer');">
                                        <i class="ri-steering-2-line me-1 text-warning"></i> 🚗 Choferes / Rodando (41)
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <div class="table-responsive table-card">
                                    <table id="tableRutas" class="table table-hover align-middle table-nowrap mb-0" style="width:100%">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 140px;">Modalidad</th>
                                                <th>Ruta (Origen ➔ Destino)</th>
                                                <th style="width: 130px;">Distancia</th>
                                                <th>Tarifas por Segmento ($/KM)</th>
                                                <th class="text-center" style="width: 160px;">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Cargado dinámicamente con DataTable -->
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

<!-- =======================================================
     MODAL: SUPER MATRIZ DUAL DE PRECIOS (MADRINA Y CHOFER)
     ======================================================= -->
<div class="modal fade" id="modalRutaMatriz" tabindex="-1" aria-labelledby="modalRutaMatrizLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title bg-white text-primary rounded-circle fs-3">
                            <i class="ri-money-dollar-box-line"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title text-white mb-0" id="modalRutaMatrizLabel">Gestión de Tarifas del Trayecto</h5>
                        <small class="text-white-50" id="modalRutaSubtitulo">Configure y consulte las tarifas de Madrina (Factores 1-15) y Chofer (1 Unidad) para esta ruta</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formRutaMatriz" onsubmit="saveRutaMatrizDual(event);">
                <input type="hidden" id="matriz_id_origen" name="id_origen" value="">
                <input type="hidden" id="matriz_id_destino" name="id_destino" value="">

                <div class="modal-body p-4">
                    <!-- CABECERA RESUMEN DE LA RUTA -->
                    <div class="card border border-light-subtle shadow-none bg-light-subtle rounded-3 mb-3">
                        <div class="card-body py-3">
                            <div class="row align-items-center">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <span class="text-muted fs-12 text-uppercase fw-semibold d-block">Trayecto Logístico</span>
                                    <div class="d-flex align-items-center">
                                        <span class="fw-bold text-dark fs-16" id="label_origen_nombre">Lagos de Moreno</span>
                                        <i class="ri-arrow-right-line text-primary mx-3 fs-20"></i>
                                        <span class="fw-bold text-primary fs-16" id="label_destino_nombre">Aguascalientes</span>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex align-items-center justify-content-md-end">
                                    <div class="me-3 text-end">
                                        <label for="matriz_km" class="form-label fw-bold text-uppercase fs-12 text-muted mb-0">Distancia (KM) *</label>
                                    </div>
                                    <div class="input-group input-group-sm" style="width: 160px;">
                                        <span class="input-group-text bg-white"><i class="ri-dashboard-3-line"></i></span>
                                        <input type="number" step="0.01" class="form-control fw-bold fs-14 text-dark text-end" id="matriz_km" name="km" value="0.00" oninput="recalcularTotalesDual();" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- NAVEGACIÓN DUAL: MADRINA VS CHOFER -->
                    <ul class="nav nav-pills nav-justified mb-3 p-1 bg-light rounded-3 shadow-xs border" id="pillsModalTarifas" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active fw-bold py-2 fs-14" id="pill-madrina-tab" data-bs-toggle="pill" data-bs-target="#tab_pane_madrina" type="button" role="tab" aria-controls="tab_pane_madrina" aria-selected="true">
                                <i class="ri-truck-line me-1 fs-16 text-primary"></i> 🚛 Tarifas Madrina (Factores 1 al 15)
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link fw-bold py-2 fs-14" id="pill-chofer-tab" data-bs-toggle="pill" data-bs-target="#tab_pane_chofer" type="button" role="tab" aria-controls="tab_pane_chofer" aria-selected="false">
                                <i class="ri-steering-2-line me-1 fs-16 text-warning"></i> 🚗 Tarifas Chofer / Rodando (1 Unidad Fija)
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content" id="tabContentTarifasModal">
                        <!-- PANE 1: MADRINA -->
                        <div class="tab-pane fade show active" id="tab_pane_madrina" role="tabpanel" aria-labelledby="pill-madrina-tab">
                            <!-- ACCIONES RÁPIDAS GLOBALES DE FACTORES -->
                            <div class="d-flex justify-content-between align-items-center mb-2 px-1">
                                <span class="fw-bold text-uppercase fs-12 text-muted"><i class="ri-list-settings-line me-1"></i> Segmentos y Factores de Madrina (1 a 15 Unidades)</span>
                                <div class="d-flex gap-2">
                                    <button type="button" class="btn btn-sm btn-soft-secondary" onclick="expandirTodosFactoresMadrina(true);"><i class="ri-arrow-down-s-line"></i> Expandir Factores</button>
                                    <button type="button" class="btn btn-sm btn-soft-secondary" onclick="expandirTodosFactoresMadrina(false);"><i class="ri-arrow-up-s-line"></i> Contraer Factores</button>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-dark">
                                        <tr class="text-uppercase fs-12">
                                            <th style="width: 240px;">Segmento / Categoría</th>
                                            <th style="width: 150px;">Costo Base / KM ($)</th>
                                            <th style="width: 150px;">Costo Base Estimado</th>
                                            <th style="width: 130px; display: none;">Costo Fijo ($)</th>
                                            <th>Desglose de Precios por Factor (Factor 1 a 15)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyMatrizMadrina">
                                        <!-- Generado dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- PANE 2: CHOFER / RODANDO -->
                        <div class="tab-pane fade" id="tab_pane_chofer" role="tabpanel" aria-labelledby="pill-chofer-tab">
                            <div class="alert alert-warning border-0 bg-warning-subtle d-flex align-items-center mb-3 p-3 rounded-3">
                                <i class="ri-information-line fs-22 text-warning me-3"></i>
                                <div>
                                    <h6 class="mb-1 text-warning fw-bold">Tarifas de Traslado por Chofer (1 Sola Unidad)</h6>
                                    <small class="text-muted">El chofer conduce 1 solo vehículo por viaje. Ingrese el costo por kilómetro.</small>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-bordered align-middle mb-0">
                                    <thead class="table-warning text-dark">
                                        <tr class="text-uppercase fs-12">
                                            <th style="width: 250px;">Segmento / Categoría</th>
                                            <th style="width: 180px;">Costo por KM ($)</th>
                                            <th style="width: 180px; display: none;">Costo Fijo / Tramo ($)</th>
                                            <th style="width: 200px;" class="text-end">Costo Total por VIN ($)</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyMatrizChofer">
                                        <!-- Generado dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-label waves-effect waves-light shadow-md">
                        <i class="ri-save-3-line label-icon align-middle fs-16 me-2"></i> Guardar Tarifas del Trayecto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- =======================================================
     MODAL: CREAR NUEVA RUTA CON SEGMENTOS EN BLANCO
     ======================================================= -->
<div class="modal fade" id="modalNuevaRuta" tabindex="-1" aria-labelledby="modalNuevaRutaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 95%;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white p-3">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title bg-white text-primary rounded-circle fs-3">
                            <i class="ri-add-circle-line"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="modal-title text-white mb-0" id="modalNuevaRutaLabel">Nueva Ruta y Matriz de Precios</h5>
                        <small class="text-white-50">Seleccione el origen, destino y complete los precios por segmento y sus factores</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNuevaRuta" onsubmit="saveNuevaRuta(event);">
                <div class="modal-body p-4">
                    <!-- SELECTORES DE RUTA -->
                    <div class="row g-3 mb-4 bg-light-subtle p-3 rounded-3 border border-light-subtle">
                        <div class="col-md-3">
                            <label for="new_id_tipo_traslado" class="form-label fw-bold">Tipo de Traslado *</label>
                            <select class="form-select" id="new_id_tipo_traslado" name="id_tipo_traslado" required>
                                <option value="">--Seleccione--</option>
                                <?php foreach ($data['catalogs']['tipos_traslado'] as $t): ?>
                                    <option value="<?= $t['id_tipo_traslado'] ?>" <?= $t['id_tipo_traslado'] == 1 ? 'selected' : '' ?>><?= $t['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="new_id_origen" class="form-label fw-bold">Punto Origen *</label>
                            <select class="form-select" id="new_id_origen" name="id_origen" required>
                                <option value="">--Seleccione--</option>
                                <?php foreach ($data['catalogs']['origenes'] as $o): ?>
                                    <option value="<?= $o['id_origen'] ?>"><?= $o['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="new_id_destino" class="form-label fw-bold">Punto Destino *</label>
                            <select class="form-select" id="new_id_destino" name="id_destino" required>
                                <option value="">--Seleccione--</option>
                                <?php foreach ($data['catalogs']['destinos'] as $d): ?>
                                    <option value="<?= $d['id_destino'] ?>"><?= $d['nombre'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="new_km" class="form-label fw-bold">Distancia (KM) *</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="ri-dashboard-3-line"></i></span>
                                <input type="number" step="0.01" class="form-control fw-bold" id="new_km" name="km" value="0.00" oninput="recalcularTotalesNuevaRuta();" required>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA MATRIZ DE SEGMENTOS -->
                    <h6 class="fw-bold text-uppercase fs-13 text-muted mb-2"><i class="ri-grid-fill me-1"></i> Precios por Segmento</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-dark">
                                <tr class="text-uppercase fs-12">
                                    <th style="width: 250px;">Segmento</th>
                                    <th style="width: 170px;">Costo / KM ($)</th>
                                    <th style="width: 170px;">Costo Estimado (1 VIN)</th>
                                    <th style="width: 150px; display: none;">Costo Fijo ($)</th>
                                    <th>Factor Base</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['catalogs']['segmentos'] as $idx => $seg): ?>
                                    <tr>
                                        <td>
                                            <input type="hidden" name="segmentos[<?= $idx ?>][id_segmento]" value="<?= $seg['id_segmento'] ?>">
                                            <input type="hidden" name="segmentos[<?= $idx ?>][num_vins_min]" value="1">
                                            <input type="hidden" name="segmentos[<?= $idx ?>][num_vins_max]" value="15">
                                            <span class="fw-bold text-dark fs-14 d-block"><?= htmlspecialchars($seg['nombre']) ?></span>
                                            <small class="text-muted fs-11"><?= htmlspecialchars($seg['descripcion']) ?></small>
                                        </td>
                                        <td>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" class="form-control text-end fw-bold new-costo-km" name="segmentos[<?= $idx ?>][costo_por_km]" value="0.00" oninput="recalcularTotalesNuevaRuta();" required>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-success-subtle text-success fs-13 fw-bold p-2 d-block text-end new-costo-total">$ 0.00</span>
                                        </td>
                                        <td style="display: none;">
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" class="form-control text-end" name="segmentos[<?= $idx ?>][precio_plano]" value="0.00">
                                            </div>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" class="form-control form-control-sm text-center fw-bold" name="segmentos[<?= $idx ?>][factor]" value="1.00">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light-subtle p-3">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-label waves-effect waves-light shadow-md">
                        <i class="ri-save-3-line label-icon align-middle fs-16 me-2"></i> Crear Ruta y Tarifas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==========================================
     MODAL: IMPORTAR CSV
     ========================================== -->
<div class="modal fade" id="modalImportCSV" tabindex="-1" aria-labelledby="modalImportCSVLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-secondary text-white p-3">
                <h5 class="modal-title text-white" id="modalImportCSVLabel">Importar Tarifas desde CSV</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formImportCSV" onsubmit="submitImportCSV(event);">
                <div class="modal-body p-4 text-center">
                    <div class="mb-4">
                        <i class="ri-upload-cloud-2-line text-muted display-4"></i>
                        <p class="mt-2 text-muted fs-14">
                            Selecciona el archivo CSV del Tarifario. El sistema creará los destinos de forma automática y cargará la matriz de tarifas según la modalidad elegida.
                        </p>
                        <div class="d-flex justify-content-center gap-2 mt-2">
                            <a href="<?= base_url(); ?>/Lgs_costos/descargarPlantillaCSV?tipo=2" class="btn btn-sm btn-soft-warning">
                                <i class="ri-steering-2-line me-1"></i> Plantilla Rodando
                            </a>
                            <a href="<?= base_url(); ?>/Lgs_costos/descargarPlantillaCSV?tipo=1" class="btn btn-sm btn-soft-primary">
                                <i class="ri-truck-line me-1"></i> Plantilla Madrina
                            </a>
                        </div>
                    </div>
                    <div class="mb-3 text-start">
                        <label for="import_id_tipo_traslado" class="form-label fw-bold">Modalidad del Tarifario *</label>
                        <select class="form-select" id="import_id_tipo_traslado" name="id_tipo_traslado">
                            <option value="">-- Detectar automáticamente del archivo CSV --</option>
                            <option value="2">🚗 Chofer (Rodando) - 1 Unidad Fija</option>
                            <option value="1">🚛 Madrina - Factores de Volumen (1 a 15)</option>
                        </select>
                    </div>
                    <div class="mb-3 text-start">
                        <label for="csv_file" class="form-label fw-bold">Archivo CSV *</label>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">Comenzar Importación</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
