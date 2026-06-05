<?php require_once("Views/Template/Srm/header_srm.php"); ?>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Breadcrumb -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between rounded px-3 py-2 bg-transparent">
                        <h4 class="mb-sm-0 fw-bold text-dark fs-15">Órdenes de Compra</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/srm/dashboard">Resumen</a></li>
                                <li class="breadcrumb-item active text-primary">Órdenes de Compra</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header Informativo -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-dark">
                        <div class="card-body p-4 position-relative z-1">
                            <h4 class="text-white fw-bold mb-2 fs-18">Historial de Órdenes de Compra</h4>
                            <p class="text-white mb-0 fs-13">
                                Consulte el estado de sus requerimientos, descargue los formatos oficiales en PDF y suba sus facturas asociadas a cada orden autorizada.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Órdenes de Compra -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0 table-hover" id="tbl-purchase-orders">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">OC #</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">Fecha de Emisión</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">Planta / Destino</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold text-end">Monto Total</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">Estatus</th>
                                            <th class="pe-4 text-uppercase text-muted fs-11 fw-bold text-end">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl-body-orders">
                                        <!-- Loader Inicial -->
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando órdenes...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL DE DETALLE DE ORDEN DE COMPRA (NATIVO VELZON) -->
<div class="modal fade" id="modalVerOC" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
            <div class="modal-header bg-soft-primary p-3">
                <h5 class="modal-title text-primary fw-bold fs-15"><i class="ri-file-list-3-line me-2"></i>Detalle de Orden de Compra #<span id="lbl-modal-oc">...</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Metadatos de la OC -->
                <div class="row g-3 mb-4 bg-light p-3 rounded">
                    <div class="col-sm-6">
                        <span class="text-muted text-uppercase fs-11 d-block mb-1">Planta de Cargo</span>
                        <h6 class="fw-bold mb-0" id="lbl-modal-planta">...</h6>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <span class="text-muted text-uppercase fs-11 d-block mb-1">Monto de la Orden</span>
                        <h5 class="fw-bold text-success mb-0" id="lbl-modal-total">$0.00</h5>
                    </div>
                </div>

                <!-- Tabla de Partidas -->
                <h6 class="fw-bold text-dark mb-3 text-uppercase fs-12">Partidas Solicitadas</h6>
                <div class="table-responsive">
                    <table class="table table-nowrap align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="text-uppercase text-muted fs-11 fw-bold">Artículo / Descripción</th>
                                <th class="text-uppercase text-muted fs-11 fw-bold text-center">Cantidad</th>
                                <th class="text-uppercase text-muted fs-11 fw-bold text-end">P. Unitario</th>
                                <th class="text-uppercase text-muted fs-11 fw-bold text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="tbl-body-partidas-oc">
                            <!-- JS inyectará las partidas aquí -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer bg-light-subtle d-flex justify-content-between">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" id="btn-pdf-descargar" class="btn btn-primary fw-bold"><i class="ri-file-pdf-line align-middle me-1"></i> Descargar Orden (PDF)</button>
            </div>
        </div>
    </div>
</div>

<?php require_once("Views/Template/Srm/footer_srm.php"); ?>