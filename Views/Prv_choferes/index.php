<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- ── SECCIÓN 1: VISTA GRID / BANDEJA ────────────────────────── -->
            <section id="view-index-choferes">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item active text-primary">Choferes</li>
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
                                    <i class="ri-steering-2-line"></i>
                                </span>
                            </div>
                            <div>
                                <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Padrón de Choferes</h3>
                                <p class="text-muted mb-0 fs-14">
                                    Gestión centralizada de conductores, vigencias de licencias y asignación a trasladistas.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                        <button class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md" onclick="fntNewChofer();">
                            <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nuevo Chofer
                        </button>
                    </div>
                </div>

                <!-- 3. BLOQUE DE KPIS CIRCULARES -->
                <div class="row mb-4">
                    <!-- KPI 1: Total Conductores -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Total Choferes</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-total-choferes">0</span></h4>
                                        <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Registrados</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                            <i class="ri-user-2-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 2: Activos -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Conductores Activos</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-activos">0</span></h4>
                                        <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Disponibles</span>
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

                    <!-- KPI 3: Licencias Federales -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Licencias Federal / Carga</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-licencias">0</span></h4>
                                        <span class="badge bg-soft-info text-info fw-medium mb-0 px-2 py-1">Tipo B, C, E</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-3">
                                            <i class="ri-shield-user-line"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KPI 4: Inactivos -->
                    <div class="col-xl-3 col-md-6">
                        <div class="card card-animate border-0 shadow-sm rounded-3">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Inactivos / Bajas</p>
                                        <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-inactivos">0</span></h4>
                                        <span class="badge bg-soft-secondary text-secondary fw-medium mb-0 px-2 py-1">No Operativos</span>
                                    </div>
                                    <div class="avatar-sm flex-shrink-0">
                                        <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fs-3">
                                            <i class="ri-user-unfollow-line"></i>
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
                            <table id="tableChoferes" class="table table-hover table-lg align-middle mb-0" style="width:100% !important;">
                                <thead class="bg-light">
                                    <tr>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ID</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Trasladista</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Nombre Completo</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">No. Licencia</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Tipo Licencia</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Vigencia</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Teléfono</th>
                                        <th scope="col" class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Estatus</th>
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
                                <i class="ri-shield-check-line text-success me-1"></i> Padrón de choferes actualizado en tiempo real
                            </small>
                        </div>
                    </div>
                </div>
            </section>


            <!-- ── SECCIÓN 2: VISTA FORMULARIO SEPARADO ────────────────────── -->
            <section id="view-form-choferes" style="display: none;">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="cancelForm();">Choferes</a></li>
                                    <li class="breadcrumb-item active text-primary" id="breadcrumb-form-chofer">Nuevo Chofer</li>
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
                                    <i class="ri-user-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold ls-05" id="form-chofer-title">Registrar Nuevo Chofer</h4>
                                <p class="text-muted mb-0 fs-13">Complete la información del operador y los detalles de su licencia de conducir.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. FORMULARIO ASIMÉTRICO (2 COLUMNAS) -->
                <form id="formChofer" name="formChofer" autocomplete="off">
                    <input type="hidden" id="id_chofer" name="id_chofer" value="">
                    
                    <div class="row">
                        <!-- COLUMNA PRINCIPAL (70%) -->
                        <div class="col-lg-8">
                            <!-- Card: Datos Personales -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-user-3-line me-1 fs-14 align-middle"></i> Datos Personales y Adscripción</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label for="id_proveedor" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Empresa Trasladista <span class="text-danger">*</span></label>
                                            <select class="form-select form-select-lg bg-light border-0" id="id_proveedor" name="id_proveedor" required>
                                                <option value="">-- Seleccionar Trasladista --</option>
                                                <?php foreach ($data['trasladistas'] as $t) { ?>
                                                    <option value="<?= $t['id_proveedor']; ?>"><?= htmlspecialchars($t['razon_social'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($t['rfc'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label for="nombre" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Nombre(s) <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-lg bg-light border-0" id="nombre" name="nombre" required placeholder="ej. Juan Carlos">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="apellidos" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Apellidos <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control form-control-lg bg-light border-0" id="apellidos" name="apellidos" required placeholder="ej. Pérez Gómez">
                                        </div>

                                        <div class="col-md-12">
                                            <label for="telefono" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Teléfono de Contacto</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-phone-line"></i></span>
                                                <input type="text" class="form-control border-start-0 ps-0" id="telefono" name="telefono" placeholder="ej. 55 1234 5678">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Card: Licencia de Conducir -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-id-card-line me-1 fs-14 align-middle"></i> Documentación y Licencia</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="num_licencia" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Número de Licencia <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control text-uppercase fw-bold" id="num_licencia" name="num_licencia" required placeholder="ej. LIC-884920">
                                        </div>

                                        <div class="col-md-6">
                                            <label for="tipo_licencia" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Licencia</label>
                                            <select class="form-select fw-bold text-primary" id="tipo_licencia" name="tipo_licencia" onchange="updateLicenciaDisplay(this.value);">
                                                <option value="A">Tipo A (Particular)</option>
                                                <option value="B" selected>Tipo B (Carga)</option>
                                                <option value="C">Tipo C (Pesado / Articulado)</option>
                                                <option value="E">Tipo E (Federal Carga)</option>
                                            </select>
                                        </div>

                                        <div class="col-md-12">
                                            <label for="vigencia_licencia" class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Vigencia de Licencia</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-calendar-event-line"></i></span>
                                                <input type="date" class="form-control border-start-0 ps-0" id="vigencia_licencia" name="vigencia_licencia">
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
                                            <i class="ri-save-3-line align-middle me-1"></i> <span id="btnText">Guardar Chofer</span>
                                        </button>
                                        <button type="button" class="btn btn-light btn-label" onclick="cancelForm();">
                                            <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Card Métrica: Tipo Licencia -->
                            <div class="card border-0 shadow-lg mb-4 bg-primary" style="border-radius: 10px; background: linear-gradient(135deg, #405189 0%, #0ab39c 100%);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white text-uppercase fs-11 fw-bold opacity-75 mb-1">
                                                Categoría Conductor
                                            </h6>
                                            <h4 class="text-white mb-0 fw-bold" id="lbl-tipo-licencia-display">
                                                Licencia Tipo B
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-steering-fill text-white fs-24 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-10 mt-1">Conductor habilitado para traslado</div>
                                </div>
                            </div>

                            <!-- Card: Registro y Auditoría -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body">
                                    <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Padrón de Conductores</h6>
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
                                        <span class="text-muted fs-12 fw-medium"><i class="ri-time-line me-1"></i> Estado Operativo:</span>
                                        <span class="badge bg-success fw-bold fs-11">DISPONIBLE</span>
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

<?php footerAdmin($data); ?>
