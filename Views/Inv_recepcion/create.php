<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            
            <section id="view-recepcion-mercantil">
                <!-- 1. Barra de Niveles -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="breadcrumb-box d-sm-flex align-items-center justify-content-between">
                            <h4 class="mb-sm-0 fs-14 fw-bold text-uppercase ls-05">Gestión de Almacenes (WMS)</h4>
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/inv_recepcion">Recepciones</a></li>
                                    <li class="breadcrumb-item active">Nueva Entrada</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Título Principal e Identidad de la OC -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <div class="avatar-title-ldr me-3">
                                    <i class="ri-inbox-archive-fill"></i>
                                </div>
                                <div>
                                    <h3 class="mb-1 fw-bold ls-05">Recepcionar Mercancía</h3>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-soft-primary text-primary fs-12 px-3 py-1">Cotejo Físico</span>
                                        <span class="text-muted fs-13">| Procesando Orden de Compra <b id="lbl-oc-id" class="text-primary">#...</b></span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end">
                                <span class="text-uppercase fs-11 fw-bold text-muted d-block mb-1">Proveedor</span>
                                <span class="badge bg-light fs-13 px-3 py-2 shadow-sm border" id="lbl-proveedor-nombre">Cargando...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formRecepcion" autocomplete="off">
                    <div class="row">
                        <!-- COLUMNA IZQUIERDA: Tabla de Cotejo -->
                        <div class="col-lg-8">
                            <div class="card">
                                <div class="card-header d-flex align-items-center bg-light-subtle">
                                    <i class="ri-list-check-2 text-primary me-2 fs-18"></i>
                                    <h6 class="card-title mb-0 flex-grow-1 fw-bold text-uppercase">Cotejo de Partidas Pendientes</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table id="tblCotejo" class="table table-nowrap align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th class="ps-4">Descripción del Bien / SKU</th>
                                                    <th width="120" class="text-center">Comprado</th>
                                                    <th width="120" class="text-center bg-soft-info text-info">Saldo Pendiente</th>
                                                    <th width="150" class="text-center">Recibir Ahora</th>
                                                    <th width="100" class="text-center">Unidad</th>
                                                </tr>
                                            </thead>
                                            <tbody id="tbl-items-recepcion">
                                                <tr>
                                                    <td colspan="5" class="text-center py-5">
                                                        <div class="spinner-border text-primary avatar-sm" role="status"></div>
                                                        <div class="mt-2 text-muted">Calculando saldos contra órdenes de compra...</div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer bg-light-subtle border-top-0 py-3">
                                    <p class="mb-0 fs-12 text-muted fst-italic">
                                        <i class="ri-information-line me-1 align-middle text-primary fs-14"></i> 
                                        <b>Instrucción:</b> Ingrese únicamente las cantidades que está recibiendo físicamente. Las diferencias generarán un estatus "Parcial" automáticamente.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA DERECHA: Datos del Documento y Acciones -->
                        <div class="col-lg-4">
                            
                            <!-- 1. Card Acciones -->
                            <div class="card">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Acciones de Almacén</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <button type="button" id="btn-registrar-entrada" class="btn btn-success btn-lg shadow-sm fw-bold">
                                            <i class="ri-check-double-line align-middle me-1"></i> Registrar Entrada
                                        </button>
                                        <button type="button" class="btn btn-soft-secondary" data-redirect="com_orden">
                                            <i class="ri-arrow-left-line align-middle me-1"></i> Cancelar
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Card Datos del Documento (Referencia) -->
                            <div class="card">
                                <div class="card-header border-bottom border-light">
                                    <h6 class="card-title mb-0 fw-bold">Información del Documento</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-10 fw-bold text-muted mb-1">Número de Remisión / Factura <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-light-subtle text-muted"><i class="ri-file-paper-2-line"></i></span>
                                            <input type="text" id="txt-remision" name="num_remision" class="form-control" placeholder="Ej. REM-98230">
                                        </div>
                                    </div>
                                    <div class="mb-0">
                                        <label class="form-label text-uppercase fs-10 fw-bold text-muted mb-1">Observaciones de Recepción</label>
                                        <textarea id="txt-observaciones" name="observaciones" class="form-control bg-light-subtle" rows="4" placeholder="Indique si hay daños, diferencias o notas de empaque..."></textarea>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Card Trazabilidad (IDOR / Auditoría) -->
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Auditoría de Origen</h6>
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title bg-soft-info text-info rounded-circle fs-20">
                                                <i class="ri-user-received-line"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <p class="text-muted mb-0 fs-11 text-uppercase fw-bold">Comprador</p>
                                            <h6 id="lbl-comprador-nombre" class="mb-0 fw-bold">...</h6>
                                        </div>
                                    </div>
                                    <hr class="border-dashed my-3">
                                    <div class="d-flex justify-content-between align-items-center fs-12">
                                        <span class="text-muted">Fecha OC:</span>
                                        <span id="lbl-oc-fecha" class="fw-bold">...</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>

    <!-- Footer Unificado -->
    <footer class="footer">
        <div class="container-fluid text-center">
            <span class="text-muted fs-12"><script>document.write(new Date().getFullYear())</script> © LDR Solutions · Módulo de Control de Inventarios</span>
        </div>
    </footer>
</div>

<?php footerAdmin($data); ?>