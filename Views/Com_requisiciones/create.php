<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-create-requisicion">
                <!-- Breadcrumb -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/com_requisicion">Requisiciones</a></li>
                                    <li class="breadcrumb-item active">Nueva Solicitud</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title bg-warning text-white rounded-circle fs-3 shadow-lg">
                                    <i class="ri-file-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold ls-05">Crear Solicitud de Compra</h4>
                                <p class="text-muted mb-0 fs-13">Complete los detalles y justificación para iniciar el flujo de aprobación.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <form id="formRequisicion" autocomplete="off">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-article-line me-1 fs-14 align-middle"></i> Datos Generales</h6>
                                </div>
                                <div class="card-body p-4">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Título de referencia <span class="text-danger">*</span></label>
                                            <input type="text" name="titulo" class="form-control form-control-lg bg-light border-0"
                                                placeholder="Ej. Renovación de equipos de cómputo Diseño">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Departamento de Cargo</label>
                                            <select name="departamentoid" class="form-select">
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Fecha Requerida <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-calendar-event-line"></i></span>
                                                <input type="date" name="fecha_requerida" class="form-control border-start-0 ps-0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-warning border-bottom border-light d-flex justify-content-between align-items-center">
                                    <h6 class="card-title mb-0 fw-bold"><i class="ri-shopping-basket-line me-1 fs-14 align-middle"></i> Partidas / Artículos</h6>
                                </div>

                                <div class="card-body p-4 bg-soft-light">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">SKU / Clave</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted"><i class="ri-barcode-line"></i></span>
                                                <input id="sku" name="sku" type="text" class="form-control border-start-0 ps-0" placeholder="Escanear o escribir...">
                                            </div>
                                            <div id="sku-feedback" class="small text-danger mt-1 fw-bold"></div>
                                        </div>

                                        <div class="col-md-8">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Descripción del Artículo</label>
                                            <select id="producto" class="form-select form-control">
                                                <option value="">— Buscar en el catálogo —</option>
                                            </select>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Cantidad</label>
                                            <input id="cantidad" type="number" step="1" class="form-control text-center fw-bold" value="1" min="1">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Unidad</label>
                                            <input id="unidad_salida" type="text" class="form-control bg-light text-muted" disabled readonly placeholder="PZA">
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Precio Est. (MXN)</label>
                                            <div class="input-group">
                                                <span class="input-group-text border-end-0 text-muted">$</span>
                                                <input id="ultimo_costo" type="text" class="form-control border-start-0 ps-0 text-end fw-bold" value="0.000000">
                                            </div>
                                        </div>

                                        <div class="col-md-2 d-flex align-items-end">
                                            <button id="btn-agregar" type="button" class="btn btn-sm btn-warning w-100 shadow-sm">
                                                <i class="ri-add-line align-middle"></i> Agregar Artículo
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-header border-bottom border-light d-flex justify-content-between align-items-center container-acciones-edicion" style="display: none !important;">
                                    <h5 class="card-title mb-0 text-muted fs-12 ls-1"><i class="ri-shopping-basket-line me-1"></i> Artículos agregados</h5>
                                    <button type="button" id="btn-modal-mover" class="btn btn-sm btn-outline-primary" disabled>
                                        <i class="ri-arrow-left-right-line align-middle"></i> Mover Partidas Seleccionadas (<span id="count-mover">0</span>)
                                    </button>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="tblPartidas" class="table table-nowrap align-middle mb-0 table-hover">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">Descripción</th>
                                                    <th width="100" class="text-center text-uppercase text-muted fs-11 fw-bold">Cant.</th>
                                                    <th width="120" class="text-end text-uppercase text-muted fs-11 fw-bold">P. Unit</th>
                                                    <th width="120" class="text-end text-uppercase text-muted fs-11 fw-bold">Subtotal</th>
                                                    <th width="150" class="text-uppercase text-muted fs-11 fw-bold">Notas</th>
                                                    <th width="50"></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="empty-state-row" style="display:none;">
                                                    <td colspan="6" class="text-center py-5">
                                                        <div class="text-muted">
                                                            <i class="ri-shopping-basket-2-line fs-1 opacity-25"></i>
                                                            <p class="mt-2 fs-13">La lista está vacía. Agregue productos arriba.</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer border-top-0 py-3">
                                    <small class="text-muted fst-italic"><i class="ri-information-line me-1"></i> Los precios son estimados y pueden variar en la Orden de Compra.</small>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-3 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-chat-1-line text-secondary me-1 fs-14 align-middle"></i> Justificación del Gasto
                                    </h5>
                                    <textarea name="justificacion" class="form-control bg-light border-0" rows="3"
                                        placeholder="Explique brevemente por qué es necesaria esta compra..."></textarea>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-4">

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header border-bottom border-light">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <h6 class="card-title mb-0 fw-bold">Acciones Disponibles</h6>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Nivel de Prioridad</label>
                                        <select name="prioridad" class="form-select form-select-lg fw-bold text-warning border-warning bg-soft-warning">
                                            <option value="1">Media - Estándar</option>
                                            <option value="2">Alta - Urgente</option>
                                            <option value="3">Baja - Planificado</option>
                                        </select>
                                    </div>

                                    <hr class="border-dashed my-3">

                                    <div class="d-grid gap-2">
                                        <button type="button" class="btn btn-success btn-lg shadow-md btn-guardar" data-estatus="pendiente">
                                            <i class="ri-send-plane-fill align-middle me-1"></i> Enviar a Aprobación
                                        </button>
                                        <button type="button" class="btn btn-light btn-guardar" data-estatus="borrador">
                                            <i class="ri-save-3-line align-middle me-1"></i> Guardar Borrador
                                        </button>
                                        <button type="button" class="btn btn-light btn-label" data-redirect="com_requisicion">
                                            <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4 bg-primary" style="border-radius: 10px; background: linear-gradient(135deg, #405189 0%, #0ab39c 100%);">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h6 class="text-white text-uppercase fs-11 fw-bold opacity-75 mb-1">
                                                Monto Estimado
                                            </h6>

                                            <h4 class="text-white mb-0 d-flex align-items-center">
                                                <input type="text"
                                                    readonly
                                                    name="monto_estimado"
                                                    id="monto_estimado"
                                                    class="form-control-plaintext form-control-lg fw-bold text-white p-0 m-0"
                                                    style="width: 200px; font-size: 1.5rem; line-height: 1;"
                                                    value="0.00">
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-wallet-3-line text-white fs-24 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-10">MXN - Pesos Mexicanos</div>
                                </div>
                            </div>

                            <!-- Tarjeta: Solicitante y Creación -->
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body">
                                    <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Solicitante</h6>
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="me-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title bg-soft-info text-info rounded-circle fs-4 shadow-sm">
                                                    <i class="ri-user-line"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fs-14 fw-bold" id="lbl-solicitante">...</h6>
                                            <p class="text-muted fs-11 mb-0">Usuario del Sistema</p>
                                        </div>
                                    </div>
                                    
                                    <hr class="border-dashed mb-3 mt-0">
                                    
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted fs-12 fw-medium"><i class="ri-time-line me-1"></i> Creado el:</span>
                                        <span class="fw-bold fs-12" id="lbl-fecha-creacion">...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

        </div>
        <!-- container-fluid -->
    </div>
    <!-- End Page-content -->

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear())
                    </script> © LDR.
                </div>
                <div class="col-sm-6">
                    <div class="text-sm-end d-none d-sm-block">
                        LDR Solutions · MRP
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <div class="modal fade" id="modalMoverPartidas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 10px;">
                <div class="modal-header bg-soft-primary border-bottom-0">
                    <h5 class="modal-title text-primary fw-bold"><i class="ri-arrow-left-right-line me-2"></i>Mover Partidas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted mb-4">Selecciona el destino para las <b><span id="lbl-modal-count">0</span></b> partidas seleccionadas. Serán removidas de la solicitud actual.</p>
                    
                    <div class="form-check mb-3 custom-radio">
                        <input class="form-check-input" type="radio" name="optDestinoMover" id="optDestinoNuevo" value="nuevo" checked>
                        <label class="form-check-label fw-bold" for="optDestinoNuevo">
                            Crear como una Nueva Solicitud (Borrador)
                        </label>
                    </div>
                    
                    <div class="form-check custom-radio mb-2">
                        <input class="form-check-input" type="radio" name="optDestinoMover" id="optDestinoExistente" value="existente">
                        <label class="form-check-label fw-bold" for="optDestinoExistente">
                            Mover a un Borrador Existente
                        </label>
                    </div>
                    
                    <!-- Select que aparece solo si elige "Existente" -->
                    <div class="collapse mt-2" id="collapseDraftsExistentes">
                        <select id="selectDraftDestino" class="form-select bg-light border-0">
                            <option value="">Cargando borradores...</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-top-0 bg-light">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" id="btn-confirmar-mover" class="btn btn-primary px-4 shadow-sm">Confirmar y Mover</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>