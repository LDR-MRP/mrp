<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- ── SECCIÓN 1: VISTA GRID / BANDEJA ────────────────────────── -->
            <section id="view-index-madrinas">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Madrinas</li>
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
                                    <i class="ri-truck-fill"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Flota de Madrinas</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión centralizada de unidades de traslado, asignación de operadores y capacidad de carga.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <button class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md" onclick="fntNewMadrina();">
                            <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nueva Madrina
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <!-- KPI 1: Total Madrinas -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Total Unidades</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-total-madrinas">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Registradas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-truck-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Con Chofer Asignado -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Con Conductor</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-asignadas">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Operativas</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                            <i class="ri-user-follow-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 3: Sin Chofer Asignado -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Sin Conductor</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-sin-chofer">0</span></h4>
                                        <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">Disponibles</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-3">
                                            <i class="ri-steering-2-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Capacidad Total -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Capacidad Flota</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-capacidad-total">0</span> <small class="fs-13 text-muted">vehs.</small></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Capacidad Máxima</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-dashboard-3-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. TABLA CARD ESTILIZADA -->
                <div class="card border-0 shadow-xl">
                    <div class="bg-primary" style="height: 4px;"></div>

                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="tableMadrinas" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">No. Económico</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Placas</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Marca / Modelo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Chofer Asignado</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Capacidad</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Flota sincronizada en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>


            <!-- ── SECCIÓN 2: VISTA FORMULARIO SEPARADO ────────────────────── -->
            <section id="view-form-madrinas" style="display: none;">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="cancelForm();">Madrinas</a></li>
                                    <li class="breadcrumb-item active text-primary" id="breadcrumb-form-madrina">Nueva Madrina</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER DE FORMULARIO -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title bg-warning text-white rounded-circle fs-3 shadow-lg">
                                    <i class="ri-truck-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold ls-05" id="form-madrina-title">Registrar Nueva Madrina</h4>
                                <p class="text-muted mb-0 fs-13">Complete los detalles técnicos y operativos de la unidad de traslado.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FORMULARIO ASIMÉTRICO (2 COLUMNAS) -->
                <form id="formMadrina" name="formMadrina" autocomplete="off">
                    <input type="hidden" id="id_madrina" name="id_madrina" value="">
                    
                    <div class="row">
                        <!-- COLUMNA PRINCIPAL (70%) -->
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-article-line me-1 fs-14 align-middle"></i> Datos Generales de la Unidad</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-lg-8 col-md-12">
                                            <label for="id_proveedor" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Empresa Trasladista <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-lg bg-light border-0" id="id_proveedor" name="id_proveedor" required>
                                                <option value="">-- Seleccionar Trasladista --</option>
                                                <?php foreach ($data['trasladistas'] as $t) { ?>
                                                    <option value="<?= $t['id_proveedor']; ?>"><?= htmlspecialchars($t['razon_social'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($t['rfc'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label for="numero_economico" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Número Económico <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-lg bg-light border-0 fw-bold" id="numero_economico" name="numero_economico" required placeholder="ej. M-101">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="placas" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Placas Tracto <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-shield-keyhole-line"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0 text-uppercase fw-bold" id="placas" name="placas" required placeholder="ej. 64-AA-1B">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="placa_caja" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Placas Caja / Remolque</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-truck-line"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0 text-uppercase" id="placa_caja" name="placa_caja" placeholder="ej. 98-TY-2C">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="num_serie_vin" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Número de Serie / VIN</label>
                                            <input type="text" class="form-control text-uppercase" id="num_serie_vin" name="num_serie_vin" placeholder="ej. 3AKJHGLD82910">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="marca" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Marca</label>
                                            <input type="text" class="form-control" id="marca" name="marca" placeholder="ej. Freightliner">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="modelo" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Modelo</label>
                                            <input type="text" class="form-control" id="modelo" name="modelo" placeholder="ej. Cascadia">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="anio" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Año</label>
                                            <input type="number" class="form-control text-center" id="anio" name="anio" placeholder="2024" min="1990" max="2030">
                                        </div>

                                        <div class="col-md-4">
                                            <label for="color" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Color</label>
                                            <input type="text" class="form-control" id="color" name="color" placeholder="ej. Blanco">
                                        </div>

                                        <div class="col-12">
                                            <label for="capacidad_vehiculos" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Capacidad Máxima de Vehículos</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-roadster-line"></i></span>
                                                <input type="number" class="form-control border-start-0 ps-0 fw-bold fs-15 text-primary" id="capacidad_vehiculos" name="capacidad_vehiculos" value="8" min="1" max="20" onchange="updateCapacidadDisplay(this.value);" onkeyup="updateCapacidadDisplay(this.value);">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA LATERAL (30%) -->
                        <div class="col-lg-4">
                            <!-- Card: Acciones Disponibles -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header border-bottom border-light">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title mb-0 fw-bold">Acciones Disponibles</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-success btn-lg shadow-md" id="btnActionForm">
                                            <i class="ri-save-3-line align-middle me-1"></i> <span id="btnText">Guardar Madrina</span>
                                        </button>
                                        <button type="button" class="btn btn-light btn-label" onclick="cancelForm();">
                                            <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Métrica: Capacidad de Carga -->
                            <div class="card border-0 shadow-lg mb-4 bg-primary" style="border-radius: 10px; background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white text-uppercase fs-11 fw-bold opacity-75 mb-1">
                                                Capacidad de Carga
                                            </h6>
                                            <h3 class="text-white mb-0 fw-bold">
                                                <span id="lbl-capacidad-display">8</span> <span class="fs-14 opacity-75 fw-normal">Vehículos</span>
                                            </h3>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-truck-line text-white fs-24 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-10 mt-1">Capacidad estándar para traslados</div>
                                </div>
                            </div>

                            <!-- Card: Usuario y Registro -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body">
                                    <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Registro de Flota</h6>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-soft-info text-info rounded-circle fs-4 shadow-sm">
                                                    <i class="ri-user-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fs-14 fw-bold"><?= htmlspecialchars($_SESSION['userData']['nombre'] ?? 'Administrador', ENT_QUOTES, 'UTF-8'); ?></h6>
                                            <p class="text-muted fs-11 mb-0">Gestión de Logística</p>
                                        </div>
                                    </div>
                                    <hr class="border-dashed mb-3 mt-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fs-12 fw-medium"><i class="ri-time-line me-1"></i> Estado:</span>
                                        <span class="badge bg-success fw-bold fs-11">ACTIVO</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

        </div>
    </div>
</div>

<!-- Modal Historial y Asignación de Chofer -->
<div class="modal fade" id="modalHistorialMadrina" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <div>
          <h5 class="modal-title mb-0" id="titleModalHistorial">Historial de Operadores de la Madrina</h5>
          <small class="text-muted" id="subTitleMadrina">Unidad</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        
        <!-- Formulario Asignar Chofer -->
        <div class="card border mb-4">
          <div class="card-header bg-soft-primary">
            <h6 class="card-title mb-0"><i class="ri-user-add-line me-1"></i> Asignar / Cambiar Chofer Activo</h6>
          </div>
          <div class="card-body">
            <form id="formAsignarChofer">
              <input type="hidden" id="historial_id_madrina" name="id_madrina" value="">
              <input type="hidden" id="historial_id_proveedor" value="">
              
              <div class="row align-items-end">
                <div class="col-md-6 mb-3 mb-md-0">
                  <label for="selectChoferAsignar" class="form-label">Seleccionar Chofer <span class="text-danger">*</span></label>
                  <select class="form-select" id="selectChoferAsignar" name="id_chofer" required>
                    <option value="">-- Cargar Choferes --</option>
                  </select>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                  <label for="observacionesAsignar" class="form-label">Observaciones / Motivo</label>
                  <input type="text" class="form-control" id="observacionesAsignar" name="observaciones" placeholder="ej. Asignación por turno">
                </div>
                <div class="col-md-2 text-end">
                  <button type="submit" class="btn btn-success w-100"><i class="ri-check-line me-1"></i> Asignar</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- Tabla Historial -->
        <h6 class="mb-3"><i class="ri-history-line me-1"></i> Registro Histórico de Conductores</h6>
        <div class="table-responsive">
          <table class="table table-bordered table-striped align-middle" id="tableHistorialChoferes">
            <thead>
              <tr>
                <th>Chofer</th>
                <th>No. Licencia</th>
                <th>Teléfono</th>
                <th>Fecha Inicio</th>
                <th>Fecha Fin</th>
                <th>Estado</th>
                <th>Observaciones</th>
              </tr>
            </thead>
            <tbody id="tbodyHistorialChoferes">
            </tbody>
          </table>
        </div>

      </div>
    </div>
  </div>
</div>

<?php footerAdmin($data); ?>
