<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- ── SECCIÓN 1: VISTA GRID / BANDEJA ────────────────────────── -->
            <section id="view-index-envios">
                <!-- 1. BREADCRUMBS -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="#">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Envíos</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN PRINCIPAL -->
                <div class="row align-items-center mb-4">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-4">
                                <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important;">
                                    <i class="ri-route-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Bandeja de Envíos</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión centralizada de traslados físicos y asignación de unidades (Madrinas y Choferes).
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold shadow-sm" onclick="openModal();">
                            <i class="ri-add-line align-middle fs-16 me-1"></i> Nuevo Envío
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-3 mb-xl-0">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Total Envíos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardTotalEnvios">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Registrados</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-route-line"></i>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">En Planeación</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEnviosCreados">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Sin consolidar</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-draft-line"></i>
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
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEnviosTransito">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">En Ruta</span>
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
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Entregados</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="cardEnviosEntregados">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Finalizados</span>
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

                <!-- 4. DATATABLE CARD -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>
                    <div class="card-body">

                        <!-- Buscador rápido de VIN -->
                        <div class="row align-items-center mb-3">
                            <div class="col-md-5">
                                <div class="input-group input-group-lg shadow-sm">
                                    <span class="input-group-text bg-light border-end-0 text-primary">
                                        <i class="ri-search-line fs-18"></i>
                                    </span>
                                    <input type="text" id="buscarVinBandeja" class="form-control border-start-0 bg-light ps-0"
                                           placeholder="Buscar por VIN, folio o destino..."
                                           oninput="filtrarTablaPorVin(this.value)">
                                    <button class="btn btn-outline-secondary" type="button" onclick="filtrarTablaPorVin(''); document.getElementById('buscarVinBandeja').value='';" title="Limpiar búsqueda">
                                        <i class="ri-close-line"></i>
                                    </button>
                                </div>
                                <small class="text-muted fs-11 mt-1 d-block"><i class="ri-information-line me-1"></i>Filtra los envíos de la tabla en tiempo real por VIN, folio u origen/destino</small>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table id="tableEnvios" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Folio</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Tipo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Motivo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Origen</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Destino</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Distancia (KM)</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">VINs Asignados</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Costo Est.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha Prog.</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estado</th>
                                        <th scope="col" class="text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3 pe-4">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer border-top-0 py-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted fw-medium">
                                <i class="ri-shield-check-line text-success me-1"></i> Envíos sincronizados en tiempo real
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
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="cancelFormEnvio();">Envíos</a></li>
                                    <li class="breadcrumb-item active text-primary" id="breadcrumb-form-envio">Nuevo Envío</li>
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
                                <span class="avatar-title bg-warning text-white rounded-circle fs-3 shadow-lg">
                                    <i class="ri-file-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold ls-05" id="form-envio-title">Crear Solicitud de Traslado</h4>
                                <p class="text-muted mb-0 fs-13">Complete la información de origen, traslado y empresa responsable.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FORMULARIO ASIMÉTRICO (2 COLUMNAS) -->
                <form id="formEnvio" name="formEnvio" autocomplete="off">
                    <input type="hidden" id="id_envio" name="id_envio" value="">

                    <div class="row">
                        <!-- COLUMNA PRINCIPAL (70%) -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-article-line me-1 fs-14 align-middle"></i> Configuración del Envío</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Traslado <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-lg bg-light border-0" id="id_tipo_traslado" name="id_tipo_traslado" required>
                                                <option value="">Seleccione Tipo...</option>
                                                <?php foreach ($data['catalogos']['tipos_traslado'] ?? [] as $t): ?>
                                                    <option value="<?= $t['id']; ?>"><?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Motivo <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-lg bg-light border-0" id="id_motivo" name="id_motivo" required>
                                                <option value="">Seleccione Motivo...</option>
                                                <?php foreach ($data['catalogos']['motivos'] ?? [] as $m): ?>
                                                    <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Empresa Trasladista <span class="text-danger">*</span></label>
                                            <select class="form-select" id="id_proveedor" name="id_proveedor" required>
                                                <option value="">Seleccione Trasladista...</option>
                                                <?php foreach ($data['catalogos']['proveedores'] ?? [] as $p): ?>
                                                    <option value="<?= $p['id']; ?>"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Origen <span class="text-danger">*</span></label>
                                            <select class="form-select" id="id_origen" name="id_origen" onchange="recalcularRutaGoogleMaps()" required>
                                                <option value="">Seleccione Origen...</option>
                                                <?php foreach ($data['catalogos']['origenes'] ?? [] as $o): ?>
                                                    <option value="<?= $o['id']; ?>" data-direccion="<?= htmlspecialchars($o['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                                                        <?= htmlspecialchars($o['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                                                        <?= !empty($o['direccion']) ? ' ('.htmlspecialchars($o['direccion'], ENT_QUOTES, 'UTF-8').')' : ''; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1"><i class="ri-calendar-event-line me-1 text-primary"></i>Fecha/Hora Programada de Salida <span class="text-danger">*</span></label>
                                            <input type="datetime-local" class="form-control" id="fecha_tentativa_envio" name="fecha_tentativa_envio" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1"><i class="ri-calendar-check-line me-1 text-muted"></i>Fecha Estimada de Llegada</label>
                                            <input type="datetime-local" class="form-control" id="fecha_tentativa_llegada" name="fecha_tentativa_llegada">
                                        </div>

                                        <!-- ── SECCIÓN MULTI-DESTINO / PARADAS ── -->
                                        <div class="col-12">
                                            <hr class="my-3">
                                            <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
                                                <div>
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-0">
                                                        <i class="ri-route-line me-1 text-primary"></i>
                                                        Paradas de la Ruta <span class="text-danger">*</span>
                                                    </label>
                                                    <small class="text-muted d-block fs-11">El camión recorre las paradas en el orden indicado. Las distancias se cargan automáticamente desde el <strong>Tarifario de Rutas</strong>.</small>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-soft-primary shadow-sm" onclick="recalcularRutaGoogleMaps()" title="Consultar distancias en Tarifario">
                                                        <i class="ri-money-dollar-circle-line me-1"></i> ⚡ Cargar desde Tarifario
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-primary shadow-sm" onclick="agregarParadaForm()">
                                                        <i class="ri-add-line me-1"></i> Agregar Parada
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Badge Resumen Distancia Total -->
                                            <div id="badge-distancia-total-container" class="alert alert-soft-primary d-flex align-items-center justify-content-between p-2 mb-3 rounded-3" style="display:none !important;">
                                                <span class="fs-12 fw-medium"><i class="ri-road-map-line text-primary me-1"></i>Distancia Total (Tarifario):</span>
                                                <span id="badge-km-total-val" class="badge bg-primary fs-13">0 km</span>
                                            </div>

                                            <!-- Contenedor dinámico de paradas -->
                                            <div id="contenedor-paradas" class="d-flex flex-column gap-2">
                                                <!-- Las paradas se agregan aquí dinámicamente -->
                                            </div>

                                            <div id="msg-sin-paradas" class="text-center py-3 border border-dashed rounded-3 text-muted" style="border-style: dashed !important;">
                                                <i class="ri-map-pin-add-line fs-24 d-block mb-1 text-primary opacity-50"></i>
                                                <span class="fs-12">Sin paradas. Haz clic en <strong>"Agregar Parada"</strong> para definir la ruta.</span>
                                            </div>

                                            <!-- Campo oculto donde se guarda el JSON de paradas -->
                                            <input type="hidden" id="paradas_json" name="paradas" value="[]">
                                        </div>

                                        <!-- Destinos disponibles como JSON para el JS -->
                                        <script id="catalogoDestinos" type="application/json">
                                            <?php
                                            $destinosJson = [];
                                            foreach ($data['catalogos']['destinos'] ?? [] as $d) {
                                                $destinosJson[] = [
                                                    'id'        => (int)$d['id'],
                                                    'nombre'    => htmlspecialchars($d['nombre'], ENT_QUOTES, 'UTF-8'),
                                                    'direccion' => htmlspecialchars($d['direccion'] ?? '', ENT_QUOTES, 'UTF-8'),
                                                    'lat'       => floatval($d['lat'] ?? 0),
                                                    'lng'       => floatval($d['lng'] ?? 0)
                                                ];
                                            }
                                            echo json_encode($destinosJson, JSON_UNESCAPED_UNICODE);
                                            ?>
                                        </script>

                                        <div class="col-md-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Fecha Tentativa Salida</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-calendar-event-line"></i></span>
                                                <input type="date" class="form-control border-start-0 ps-0" id="fecha_tentativa_envio" name="fecha_tentativa_envio">
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Observaciones</label>
                                            <textarea class="form-control bg-light border-0" id="observaciones" name="observaciones" rows="3" placeholder="Instrucciones adicionales para el traslado..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA LATERAL (30%) -->
                        <div class="col-lg-4">
                            <!-- Card: Acciones -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Acciones Disponibles</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="btnActionForm" class="btn btn-success rounded-pill py-2 shadow-sm fw-semibold" onclick="saveEnvio();">
                                            <i class="ri-save-3-line align-middle me-1"></i> <span id="btnText">Guardar Envío</span>
                                        </button>
                                        <button type="button" class="btn btn-light border rounded-pill py-2 fw-semibold" onclick="cancelFormEnvio();">
                                            <i class="ri-arrow-go-back-line align-middle fs-16 me-1"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card: Resumen de Traslado -->
                            <div class="card border-0 shadow-lg mb-4 text-white" style="border-radius: 14px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white text-uppercase fs-11 fw-bold opacity-75 mb-1">
                                                Estatus del Envío
                                            </h6>
                                            <h4 class="text-white mb-0 fw-bold">
                                                En Borrador
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-route-line text-white fs-24 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-10 mt-1">Podrás asignar VINs en la siguiente etapa</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
