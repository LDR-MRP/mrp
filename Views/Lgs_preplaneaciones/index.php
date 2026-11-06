<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <!-- BREADCRUMBS -->
            <div class="row align-items-center mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/Lgs_planeaciones">Logística</a></li>
                                <li class="breadcrumb-item active text-primary">Preplaneaciones</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- HEADER Y ACCIÓN PRINCIPAL -->
            <div class="row align-items-center mb-4">
                <div class="col-md-8">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-3">
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-sm" style="background-color: #0284C7 !important;">
                                <i class="ri-calendar-check-line"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-uppercase ls-1 text-dark">Preplaneaciones para Planta</h3>
                            <p class="text-muted mb-0 fs-13">
                                Aviso preventivo a Planta / WMS para confirmar disponibilidad física de unidades y evitar viajes fallidos.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex justify-content-md-end justify-content-start mt-3 mt-md-0 gap-2">
                    <a href="<?= base_url(); ?>/Lgs_planeaciones" class="btn btn-outline-secondary rounded-pill px-3 py-2 fw-medium fs-13 shadow-xs">
                        <i class="ri-arrow-left-line me-1"></i> Ir a Mis Planeaciones
                    </a>
                    <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold fs-13 shadow-sm" onclick="abrirModalNuevaPreplaneacion();" style="background-color: #0284C7; border-color: #0284C7;">
                        <i class="ri-add-line align-middle fs-16 me-1"></i> Nueva Preplaneación
                    </button>
                </div>
            </div>

            <!-- KPIS INFORMATIVOS -->
            <!-- KPIS SUPERIORES HOMOLOGADOS -->
            <div class="row g-3 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="cursor: pointer;" onclick="filtrarPreplanPorCard('', 'chip-preplan-todos');" title="Ver todos los avisos">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-soft-info text-info rounded-circle d-flex align-items-center justify-content-center me-3 fs-20">
                                <i class="ri-file-list-3-line"></i>
                            </div>
                            <div>
                                <span class="text-muted fs-11 text-uppercase fw-bold">Total Avisos</span>
                                <h4 class="mb-0 fw-bold text-dark" id="kpiTotalPreplan">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="cursor: pointer;" onclick="filtrarPreplanPorCard('En Espera', 'chip-preplan-espera');" title="Filtrar avisos en espera de planta">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-soft-warning text-warning rounded-circle d-flex align-items-center justify-content-center me-3 fs-20">
                                <i class="ri-time-line"></i>
                            </div>
                            <div>
                                <span class="text-muted fs-11 text-uppercase fw-bold">En Espera de Planta</span>
                                <h4 class="mb-0 fw-bold text-warning" id="kpiEnEspera">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="cursor: pointer;" onclick="filtrarPreplanPorCard('Confirmada', 'chip-preplan-confirmadas');" title="Filtrar avisos confirmados por planta">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-soft-success text-success rounded-circle d-flex align-items-center justify-content-center me-3 fs-20">
                                <i class="ri-checkbox-circle-line"></i>
                            </div>
                            <div>
                                <span class="text-muted fs-11 text-uppercase fw-bold">Confirmadas</span>
                                <h4 class="mb-0 fw-bold text-success" id="kpiConfirmadas">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 bg-white h-100" style="cursor: pointer;" onclick="filtrarPreplanPorCard('Convertida a Plan', 'chip-preplan-convertidas');" title="Filtrar preplaneaciones ya convertidas a plan">
                        <div class="d-flex align-items-center">
                            <div class="avatar-sm bg-soft-primary text-primary rounded-circle d-flex align-items-center justify-content-center me-3 fs-20" style="background-color: rgba(196, 102, 35, 0.12) !important; color: #C46623 !important;">
                                <i class="ri-roadster-line"></i>
                            </div>
                            <div>
                                <span class="text-muted fs-11 text-uppercase fw-bold">Convertidas a Plan</span>
                                <h4 class="mb-0 fw-bold" style="color: #C46623;" id="kpiConvertidas">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLA PRINCIPAL DE PREPLANEACIONES CON FILTROS CHIPS -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white mb-4">
                <div class="card-header bg-white py-3 px-4 border-bottom border-light-subtle">
                    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
                        <!-- Buscador -->
                        <div class="d-flex align-items-center w-100" style="max-width: 360px;">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-light border-end-0"><i class="ri-search-line text-muted"></i></span>
                                <input type="text" id="buscarPreplanInput" class="form-control bg-light border-start-0" placeholder="Buscar folio, planta..." oninput="filtrarTablaPreplan(this.value);">
                                <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="document.getElementById('buscarPreplanInput').value=''; filtrarTablaPreplan('');" title="Limpiar"><i class="ri-close-line"></i></button>
                            </div>
                        </div>

                        <!-- Chips de Filtro Rápido -->
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <span class="fs-11 text-muted text-uppercase fw-bold me-1 d-none d-xl-inline"><i class="ri-filter-3-line me-1"></i>Filtro:</span>
                            <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fs-12 active chip-preplan-btn" id="chip-preplan-todos" onclick="filtrarPreplanPorChip('', this);">
                                📌 Todos
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3 py-1 fs-12 chip-preplan-btn" id="chip-preplan-espera" onclick="filtrarPreplanPorChip('En Espera', this);">
                                ⏳ En Espera
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fs-12 chip-preplan-btn" id="chip-preplan-confirmadas" onclick="filtrarPreplanPorChip('Confirmada', this);">
                                ✅ Confirmadas
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fs-12 chip-preplan-btn" id="chip-preplan-convertidas" onclick="filtrarPreplanPorChip('Convertida a Plan', this);">
                                🚀 Convertidas a Plan
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4">
                    <div class="table-responsive">
                        <table id="tablePreplaneaciones" class="table table-hover align-middle table-nowrap w-100">
                            <thead class="table-light fs-12 text-uppercase text-muted">
                                <tr>
                                    <th>Folio</th>
                                    <th>Planta de Origen</th>
                                    <th>Fecha Tentativa Recolección</th>
                                    <th>Unidades Solicitadas</th>
                                    <th>Disponibilidad Confirmada</th>
                                    <th>Estado</th>
                                    <th>Planeación Generada</th>
                                    <th class="text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="fs-13">
                                <!-- Llenado por AJAX DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 1: NUEVA PREPLANEACIÓN (AVISO A PLANTA)                 -->
<!-- ============================================================== -->
<div class="modal fade" id="modalNuevaPreplan" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white p-3 rounded-top-4">
                <div class="d-flex align-items-center">
                    <i class="ri-calendar-event-line fs-20 text-info me-2"></i>
                    <h5 class="modal-title fw-bold text-white mb-0">Nueva Preplaneación — Aviso Preventivo a Planta</h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formNuevaPreplan" onsubmit="event.preventDefault(); guardarNuevaPreplaneacion();">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-bold text-muted text-uppercase mb-1">Planta de Ensamble / Patio <span class="text-danger">*</span></label>
                            <select class="form-select border rounded-3" id="nueva_id_planta" name="id_planta" required onchange="cargarVinsDisponiblesParaPreplan();">
                                <option value="">Seleccione Planta...</option>
                                <?php foreach ($data['plantas'] ?? [] as $pl): ?>
                                    <option value="<?= $pl['idplanta']; ?>"><?= htmlspecialchars($pl['nombre_planta'], ENT_QUOTES, 'UTF-8'); ?> (<?= htmlspecialchars($pl['cve_planta'], ENT_QUOTES, 'UTF-8'); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-bold text-muted text-uppercase mb-1">Fecha y Hora Tentativa de Recolección <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control border rounded-3" id="nueva_fecha_tentativa" name="fecha_tentativa_recoleccion" required>
                        </div>
                    </div>

                    <!-- SECCIÓN DE SELECCIÓN DE VINS -->
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fs-12 fw-bold text-dark text-uppercase"><i class="ri-car-line text-primary me-1"></i>Seleccionar Unidades a Notificar a Planta</span>
                            <span class="badge bg-primary fs-11" id="lblCountVinsSeleccionados">0 seleccionados</span>
                        </div>
                        <p class="text-muted fs-11 mb-2">
                            Marque los VINs que planea incluir en el viaje. Planta verificará que no tengan faltantes ni bloqueos de PDI.
                        </p>
                        
                        <div class="input-group input-group-sm mb-2">
                            <span class="input-group-text bg-white border"><i class="ri-search-line"></i></span>
                            <input type="text" class="form-control" id="buscarVinPreplan" placeholder="Filtrar por VIN, número de serie o modelo..." onkeyup="filtrarTablaVinsPreplan();">
                        </div>

                        <div class="table-responsive bg-white rounded border" style="max-height: 240px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 fs-12" id="tablaVinsSeleccionPreplan">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40px;" class="text-center">
                                            <input type="checkbox" class="form-check-input" id="checkTodosVinsPreplan" onchange="toggleSeleccionarTodosVins(this.checked);">
                                        </th>
                                        <th>VIN</th>
                                        <th>Modelo</th>
                                        <th>Serie / Consecutivo</th>
                                        <th>Destino Sugerido</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyVinsSeleccionPreplan">
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">
                                            Seleccione una Planta arriba para cargar las unidades disponibles.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm" style="background-color: #0284C7; border-color: #0284C7;">
                            <i class="ri-send-plane-fill me-1"></i> Emitir Aviso a Planta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 2: CAPTURA DE CONFIRMACIÓN DE PLANTA                    -->
<!-- ============================================================== -->
<div class="modal fade" id="modalConfirmarPlanta" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-success text-white p-3 rounded-top-4">
                <div class="d-flex align-items-center">
                    <i class="ri-phone-check-line fs-20 text-white me-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="titleModalConfirmar">Registrar Confirmación de Planta</h5>
                        <small class="text-white opacity-75 fs-11" id="subFolioPreplan"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <form id="formConfirmarPlanta" onsubmit="event.preventDefault(); guardarConfirmacionPlanta();">
                    <input type="hidden" id="conf_id_preplaneacion" name="id_preplaneacion">

                    <div class="alert alert-soft-success border-0 p-3 mb-3 rounded-3 fs-12">
                        <i class="ri-information-line text-success fs-16 me-1 align-middle"></i>
                        Registre la retroalimentación obtenida con el encargado de patio en Planta. Solo las unidades confirmadas como <strong>Disponibles</strong> podrán convertirse a Planeación Definitiva.
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-bold text-muted text-uppercase mb-1">Contacto en Planta (Con quién se acordó) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control border rounded-3" id="conf_contacto_planta" name="contacto_planta" required placeholder="Ej. Ing. Juan Martínez (Encargado Patio)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fs-12 fw-bold text-muted text-uppercase mb-1">Notas de Comunicación / Acuerdos</label>
                            <input type="text" class="form-control border rounded-3" id="conf_notas_comunicacion" name="notas_comunicacion" placeholder="Ej. Confirmado vía llamada; unidades listas en andén norte">
                        </div>
                    </div>

                    <!-- LISTADO DE VINS PARA MARCAR DISPONIBILIDAD -->
                    <div class="border rounded-3 p-3 bg-light mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fs-12 fw-bold text-dark text-uppercase"><i class="ri-check-double-line text-success me-1"></i>Validación Física por Unidad (Planta)</span>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-xs btn-outline-success rounded-pill px-2" onclick="marcarTodosDisponibles(true);">Marcar Todos Sí</button>
                                <button type="button" class="btn btn-xs btn-outline-danger rounded-pill px-2" onclick="marcarTodosDisponibles(false);">Marcar Todos No</button>
                            </div>
                        </div>

                        <div class="table-responsive bg-white rounded border" style="max-height: 280px; overflow-y: auto;">
                            <table class="table table-sm table-hover align-middle mb-0 fs-12">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th>VIN / Unidad</th>
                                        <th>Modelo</th>
                                        <th style="width: 140px;" class="text-center">¿Disponible?</th>
                                        <th style="width: 220px;">Motivo si NO llega</th>
                                    </tr>
                                </thead>
                                <tbody id="tbodyVinsConfirmacion">
                                    <!-- Inyectado dinámicamente -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                            <i class="ri-save-line me-1"></i> Guardar Confirmación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================== -->
<!-- MODAL 3: DETALLE DE PREPLANEACIÓN                             -->
<!-- ============================================================== -->
<div class="modal fade" id="modalDetallePreplan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header bg-dark text-white p-3 rounded-top-4">
                <div class="d-flex align-items-center">
                    <i class="ri-eye-line fs-20 text-warning me-2"></i>
                    <div>
                        <h5 class="modal-title fw-bold text-white mb-0" id="detFolioPreplan">Detalle de Preplaneación</h5>
                        <small class="text-white opacity-75 fs-11" id="detPlantaFecha"></small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div id="contentDetallePreplan">
                    <!-- Inyectado dinámicamente -->
                </div>
            </div>
            <div class="modal-footer bg-light p-3 d-flex justify-content-between align-items-center">
                <div id="btnDetalleAccionConvertir">
                    <!-- Botón de convertir a planeación -->
                </div>
                <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>

