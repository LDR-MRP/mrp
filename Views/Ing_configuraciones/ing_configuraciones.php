<?php headerAdmin($data);
?>
<div id="contentAjax"></div>
<div class="main-content">

    <div class="page-content">
        <div class="container-fluid">

            <!-- 1. BREADCRUMB -->
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="javascript: void(0);">Ingeniería</a></li>
                                <li class="breadcrumb-item active text-primary"><?= $data['page_tag'] ?></li>
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
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #405189 !important;">
                                <i class="ri-file-list-3-fill"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Configuraciones</h3>
                            <p class="text-muted mb-0 fs-14">
                                Configuración técnica de vehículos por segmento y modelo: especificaciones, certificaciones y autorización.
                            </p>
                        </div>
                    </div>
                </div>
                <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                <div class="col-md-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0">
                    <button type="button" id="btnNuevaConfiguracion" class="btn btn-primary btn-lg btn-label waves-effect waves-light shadow-md">
                        <i class="ri-add-line label-icon align-middle fs-18 me-2"></i> Nueva configuración
                    </button>
                </div>
                <?php } ?>
            </div>

            <!-- 3. BLOQUE DE KPIS -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Total Configuraciones</p>
                                    <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-total-configuraciones">0</span></h4>
                                    <span class="badge bg-soft-primary text-primary fw-medium mb-0 px-2 py-1">Registradas</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-3">
                                        <i class="ri-file-list-3-line"></i>
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
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Autorizadas</p>
                                    <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-autorizadas">0</span></h4>
                                    <span class="badge bg-soft-success text-success fw-medium mb-0 px-2 py-1">Listas para VIN</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fs-3">
                                        <i class="ri-shield-check-line"></i>
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
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Pendientes</p>
                                    <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-pendientes">0</span></h4>
                                    <span class="badge bg-soft-warning text-warning fw-medium mb-0 px-2 py-1">En captura/revisión</span>
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

                <div class="col-xl-3 col-md-6">
                    <div class="card card-animate border-0 shadow-sm rounded-3">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-grow-1 overflow-hidden">
                                    <p class="text-uppercase fw-bold text-muted text-truncate mb-2 fs-11 ls-1">Bloqueadas</p>
                                    <h4 class="fs-22 fw-bold text-body mb-2"><span class="counter-value" id="kpi-bloqueadas">0</span></h4>
                                    <span class="badge bg-soft-danger text-danger fw-medium mb-0 px-2 py-1">Requieren atención</span>
                                </div>
                                <div class="avatar-sm flex-shrink-0">
                                    <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-3">
                                        <i class="ri-lock-line"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-xl">
                <div class="bg-primary" style="height: 4px;"></div>
                <div class="card-header">
                    <ul class="nav nav-tabs-custom card-header-tabs border-bottom-0" id="nav-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#listConfiguraciones" role="tab"><i class="ri-list-unordered align-bottom me-1"></i>LISTADO</a>
                        </li>
                        <?php if (!empty($_SESSION['permisosMod']['w'])) { ?>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#datosGenerales" role="tab"><i class="ri-file-edit-line align-bottom me-1"></i>DATOS GENERALES</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="tabEspecificaciones" data-bs-toggle="tab" href="#especificaciones" role="tab"><i class="ri-list-check-2 align-bottom me-1"></i>ESPECIFICACIONES</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="tabCertificaciones" data-bs-toggle="tab" href="#certificaciones" role="tab"><i class="ri-shield-check-line align-bottom me-1"></i>CERTIFICACIONES</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="tabHistorial" data-bs-toggle="tab" href="#historial" role="tab"><i class="ri-history-line align-bottom me-1"></i>HISTORIAL</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link disabled" id="tabFichaTecnica" data-bs-toggle="tab" href="#fichaTecnica" role="tab"><i class="ri-file-text-line align-bottom me-1"></i>FICHA TÉCNICA</a>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
                <div class="card-body">

                    <!-- PROGRESO: línea de proceso (datos generales / especificaciones / certificaciones / autorizado) -->
                    <div id="progresoConfiguracionWrapper" class="mb-4 pb-3 border-bottom" style="display:none">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                            <h6 class="text-uppercase text-muted fs-13 mb-0">Progreso de la configuración</h6>
                            <span id="progresoEstadoBadge"></span>
                        </div>
                        <div id="stepperConfiguracion" class="stepper-configuracion"></div>
                    </div>

                    <div class="tab-content">

                        <!-- LISTADO -->
                        <div class="tab-pane active" id="listConfiguraciones" role="tabpanel">
                            <div class="d-flex justify-content-end gap-2 mb-3">
                                <?php if (!empty($_SESSION['permisosMod']['u'])) { ?>
                                    <button type="button" id="btnReevaluarTodas" class="btn btn-outline-primary btn-sm">
                                        <i class="ri-refresh-line align-bottom me-1"></i>Reevaluar todas ahora
                                    </button>
                                <?php } ?>
                            </div>
                            <div class="table-responsive">
                                <table id="tableConfiguraciones" class="table table-hover table-bordered nowrap table-striped align-middle" style="width:100%">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">SEGMENTO</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">MODELO</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ORIGEN</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">NOMBRE UNIDAD</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">NOMBRE COMERCIAL</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">CLAVE VEHICULAR</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">MOTOR</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">TRANSMISIÓN</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ESTADO</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">ACCIÓN</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- DATOS GENERALES -->
                        <div class="tab-pane" id="datosGenerales" role="tabpanel">
                            <form id="formConfiguraciones" autocomplete="off" class="was-validated">
                                <input type="hidden" id="id_configuracion" name="id_configuracion" value="0">
                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="id-segmento-select">SEGMENTO</label>
                                            <select class="form-select" id="id-segmento-select" name="id-segmento-select" required>
                                                <option value="">--Seleccione--</option>
                                            </select>
                                            <div class="invalid-feedback">El segmento es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="id-sublinea-select">MODELO</label>
                                            <select class="form-select" id="id-sublinea-select" name="id-sublinea-select" required disabled>
                                                <option value="">--Seleccione un segmento--</option>
                                            </select>
                                            <div class="invalid-feedback">El modelo es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="tipo-origen-select">TIPO DE ORIGEN</label>
                                            <select class="form-select" id="tipo-origen-select" name="tipo-origen-select">
                                                <option value="NACIONAL" selected>Nacional</option>
                                                <option value="IMPORTADO">Importado</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="estado-select">ESTADO</label>
                                            <select class="form-select" id="estado-select" name="estado-select">
                                                <option value="BORRADOR" selected>Borrador</option>
                                                <option value="EN_REVISION">En revisión</option>
                                                <option value="EN_CORRECCION">En corrección</option>
                                                <option value="OBSOLETO">Obsoleto</option>
                                            </select>
                                            <div class="form-text">Autorizado y Bloqueado los asigna el sistema automáticamente. El motor de reglas solo evalúa configuraciones en “En revisión”, “Autorizado” o “Bloqueado” — mientras esté en Borrador no aparecerá nada en HISTORIAL.</div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-unidad-input">NOMBRE UNIDAD</label>
                                            <input type="text" class="form-control" id="nombre-unidad-input" name="nombre-unidad-input" placeholder="Ej. Aumark TM" required>
                                            <div class="invalid-feedback">El nombre de unidad es obligatorio</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="clave-vehicular-input">CLAVE VEHICULAR</label>
                                            <input type="text" class="form-control" id="clave-vehicular-input" name="clave-vehicular-input" required>
                                            <div class="invalid-feedback">La clave vehicular es obligatoria</div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nombre-comercial-input">NOMBRE COMERCIAL</label>
                                            <input type="text" class="form-control" id="nombre-comercial-input" name="nombre-comercial-input" placeholder="Ej. Aumark TM Comercial">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="codigo-modelo-input">CÓDIGO DE MODELO</label>
                                            <input type="text" class="form-control" id="codigo-modelo-input" name="codigo-modelo-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="id-motor-select">MOTOR</label>
                                            <select class="form-select" id="id-motor-select" name="id-motor-select">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="id-transmision-select">TRANSMISIÓN</label>
                                            <select class="form-select" id="id-transmision-select" name="id-transmision-select">
                                                <option value="">--Seleccione--</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="combustible-input">COMBUSTIBLE</label>
                                            <input type="text" class="form-control" id="combustible-input" name="combustible-input">
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="peso-bruto-input">PESO BRUTO VEHICULAR</label>
                                            <input type="number" step="0.01" class="form-control" id="peso-bruto-input" name="peso-bruto-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="nivel-emisiones-input">NIVEL DE EMISIONES</label>
                                            <input type="text" class="form-control" id="nivel-emisiones-input" name="nivel-emisiones-input" placeholder="Ej. EURO VI">
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-sm-6">
                                        <div class="mb-3">
                                            <label class="form-label" for="version-input">VERSIÓN</label>
                                            <input type="text" class="form-control" id="version-input" name="version-input">
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="submit" id="btnActionForm" class="btn btn-success btn-label right ms-auto">
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i><span id="btnText">REGISTRAR</span>
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- ESPECIFICACIONES -->
                        <div class="tab-pane" id="especificaciones" role="tabpanel">
                            <div id="avisoSinConfiguracionSpecs" class="alert alert-warning">Guarda primero los datos generales de la configuración.</div>
                            <form id="formEspecificaciones" style="display:none">
                                <div id="contenedorEspecificaciones"></div>
                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="submit" class="btn btn-success btn-label right ms-auto">
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i>GUARDAR ESPECIFICACIONES
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- CERTIFICACIONES -->
                        <div class="tab-pane" id="certificaciones" role="tabpanel">
                            <div id="avisoSinConfiguracionCert" class="alert alert-warning">Guarda primero los datos generales de la configuración.</div>
                            <form id="formCertificaciones" style="display:none">
                                <div class="table-responsive">
                                    <table class="table table-bordered align-middle" id="tableCertificacionesForm">
                                        <thead>
                                            <tr>
                                                <th>CERTIFICACIÓN</th>
                                                <th>OBLIGATORIA</th>
                                                <th>ESTADO</th>
                                                <th>NO. CERTIFICADO</th>
                                                <th>EMISIÓN</th>
                                                <th>INICIO</th>
                                                <th>VENCIMIENTO</th>
                                                <th>OBSERVACIONES</th>
                                            </tr>
                                        </thead>
                                        <tbody id="bodyCertificacionesForm"></tbody>
                                    </table>
                                </div>
                                <div class="d-flex align-items-start gap-3 mt-4">
                                    <button type="submit" class="btn btn-success btn-label right ms-auto">
                                        <i class="ri-save-line label-icon align-middle fs-16 ms-2"></i>GUARDAR CERTIFICACIONES
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- HISTORIAL -->
                        <div class="tab-pane" id="historial" role="tabpanel">
                            <div id="avisoSinConfiguracionHistorial" class="alert alert-warning">Guarda primero los datos generales de la configuración.</div>
                            <div class="table-responsive" id="contenedorHistorial" style="display:none">
                                <table class="table table-bordered align-middle">
                                    <thead>
                                        <tr>
                                            <th>FECHA</th>
                                            <th>ORIGEN</th>
                                            <th>CAMBIO DE ESTADO</th>
                                            <th>MOTIVOS</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bodyHistorial"></tbody>
                                </table>
                            </div>
                        </div>

                        <!-- FICHA TÉCNICA -->
                        <div class="tab-pane" id="fichaTecnica" role="tabpanel">
                            <div id="avisoSinConfiguracionFicha" class="alert alert-warning">Guarda primero los datos generales de la configuración.</div>
                            <div id="contenedorFichaTecnica" style="display:none"></div>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
