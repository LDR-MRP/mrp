<?php headerAdmin($data); ?>
<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Breadcrumb -->
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title']; ?></h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">Logística</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag']; ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /Breadcrumb -->

            <!-- Filtros rápidos -->
            <div class="row g-2 mb-3">
                <div class="col-md-3">
                    <select id="filtroEstado" class="form-select form-select-sm" onchange="recargarBandeja()">
                        <option value="">Estado: Todos</option>
                        <option value="1">Pendiente</option>
                        <option value="2">En Tránsito</option>
                        <option value="3">Entregado</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtroDestino" class="form-select form-select-sm" onchange="recargarBandeja()">
                        <option value="">Destino: Todos</option>
                        <?php foreach ($data['destinos'] as $dest): ?>
                            <option value="<?= intval($dest['id_destino']); ?>"><?= htmlspecialchars($dest['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filtroMotivo" class="form-select form-select-sm" onchange="recargarBandeja()">
                        <option value="">Motivo: Todos</option>
                        <?php foreach ($data['motivos'] as $mot): ?>
                            <option value="<?= intval($mot['id_motivo']); ?>"><?= htmlspecialchars($mot['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" id="filtroBusqueda" class="form-control form-control-sm" placeholder="Buscar VIN o N/S..." oninput="recargarBandeja()">
                </div>
            </div>

            <!-- Tabla principal -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="ri-truck-line me-2"></i>Unidades en Logística</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered dt-responsive nowrap table-striped align-middle" id="tableBandeja" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>VIN</th>
                                    <th>N/S</th>
                                    <th>Modelo</th>
                                    <th>Motivo Envío</th>
                                    <th>Tipo Destino</th>
                                    <th>Destino</th>
                                    <th>Estado</th>
                                    <th>Salida</th>
                                    <th>Llegada</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div><!-- /container-fluid -->
    </div><!-- /page-content -->
</div><!-- /main-content -->

<!-- ======================================================
     MODAL: Asignar Destino y Motivo (Flujo Global)
     ====================================================== -->
<div class="modal fade" id="modalAsignarDestino" tabindex="-1" aria-labelledby="labelModalDestino" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="labelModalDestino"><i class="ri-map-pin-2-line me-2"></i>Asignar Destino y Motivo de Envío</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="asig_id_lgs_unidad">
                <div class="mb-3">
                    <label class="form-label">VIN <small class="text-muted" id="asig_vin_label"></small></label>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Motivo de Envío <span class="text-danger">*</span></label>
                    <select id="asig_id_motivo" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($data['motivos'] as $mot): ?>
                            <option value="<?= intval($mot['id_motivo']); ?>"><?= htmlspecialchars($mot['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tipo de Destino <span class="text-danger">*</span></label>
                    <select id="asig_id_destino" class="form-select">
                        <option value="">-- Seleccione --</option>
                        <?php foreach ($data['destinos'] as $dest): ?>
                            <option value="<?= intval($dest['id_destino']); ?>"><?= htmlspecialchars($dest['descripcion'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Nombre del Destino (libre)</label>
                    <input type="text" id="asig_destino_descripcion" class="form-control" placeholder="Ej: Distribuidora Norte S.A., Carrocería XYZ...">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnGuardarDestino" onclick="fntGuardarDestino()">
                    <i class="ri-save-line me-1"></i>Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     MODAL: Registrar Fechas de Salida / Llegada
     ====================================================== -->
<div class="modal fade" id="modalFechas" tabindex="-1" aria-labelledby="labelModalFechas" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="labelModalFechas"><i class="ri-calendar-check-line me-2"></i>Registrar Fechas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="fec_id_lgs_unidad">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Fecha de Salida</label>
                    <input type="datetime-local" id="fec_fecha_salida" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Fecha de Llegada</label>
                    <input type="datetime-local" id="fec_fecha_llegada" class="form-control">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning" onclick="fntGuardarFechas()">
                    <i class="ri-save-line me-1"></i>Guardar Fechas
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ======================================================
     MODAL: Detalle de Unidad
     ====================================================== -->
<div class="modal fade" id="modalDetalleUnidad" tabindex="-1" aria-labelledby="labelModalDetalle" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="labelModalDetalle"><i class="ri-eye-line me-2"></i>Detalle de Unidad Logística</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detalleUnidadBody">
                <div class="text-center py-4"><div class="spinner-border text-info" role="status"></div></div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>
