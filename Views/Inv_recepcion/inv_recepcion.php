<?php headerAdmin($data); ?>

<div id="contentAjax"></div>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0"><?= $data['page_title'] ?></h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="javascript:void(0);">MRP</a></li>
                                <li class="breadcrumb-item active"><?= $data['page_tag'] ?></li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-1 fw-bold">1. Ordenes de compra - Recepciones</h5>
                        </div>

                        <div class="card-body">
                            <input type="text" id="buscarRecepcion" class="form-control mb-3"
                                placeholder="Buscar OC o proveedor...">

                            <ul class="nav nav-tabs mb-3" id="tabsRecepcion" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#abiertas" type="button">
                                        Abiertas
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#parciales" type="button">
                                        Parciales
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link" data-bs-toggle="tab" data-bs-target="#cerradas" type="button">
                                        Cerradas
                                    </button>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="abiertas">
                                    <div class="recepcion-scroll">
                                        <ul class="list-group" id="listaRecepcionAbierta"></ul>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="parciales">
                                    <div class="recepcion-scroll">
                                        <ul class="list-group" id="listaRecepcionParcial"></ul>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="cerradas">
                                    <div class="recepcion-scroll">
                                        <ul class="list-group" id="listaRecepcionCerrada"></ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">

                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div>
                            <h4 class="mb-1 fw-bold">2. Recepción de materiales</h4>
                            <p class="text-muted mb-0">Visualiza información de la orden de compra y el almacén destino.</p>
                        </div>
                    </div>

                    <!-- Header resumen -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="card resumen-card h-100">
                                <div class="card-body">
                                    <span class="resumen-label">Compra origen</span>
                                    <div id="headerOrigen" class="resumen-value empty-state-mini">
                                        Selecciona una orden de compra
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card resumen-card h-100">
                                <div class="card-body">
                                    <span class="resumen-label">Destino</span>
                                    <div id="headerDestino" class="resumen-value empty-state-mini">
                                        Información pendiente
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Scanner -->
                    <div class="card scanner-card mb-3">
                        <div class="card-body">
                            <div class="scanner-header">
                                <div>
                                    <h5 class="mb-1 fw-bold">3. Escaneo de producto</h5>
                                    <p class="text-muted mb-0">Escanea o captura manualmente un código de barras</p>
                                </div>
                                <div class="scanner-icon">
                                    <i class="ri-qr-scan-2-line"></i>
                                </div>
                            </div>

                            <div class="scanner-input-wrap mt-3">
                                <i class="ri-barcode-line scanner-input-icon"></i>
                                <input type="text" id="scannerInput" class="form-control scanner-input"
                                    placeholder="Escanea código de barras..." autocomplete="off">
                            </div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="card table-card mb-3">
                        <div class="card-header bg-white border-0 pb-0">
                            <h5 class="mb-1 fw-bold">4. Validación de materiales</h5>
                            <p class="text-muted mb-0">Revisa cantidades recibidas antes de registrar</p>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive tabla-recepcion-wrap">
                                <table class="table table-hover align-middle mb-0 tabla-recepcion">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>Código</th>
                                            <th>Descripción</th>
                                            <th>Lote</th>
                                            <th class="text-center">Solicitado</th>
                                            <th class="text-center">Recibido</th>
                                            <th class="text-center">Pendiente</th>
                                            <th class="text-center">Unidad</th>
                                            <th>Obs.</th>
                                        </tr>
                                    </thead>
                                    <tbody id="detalleRecepcion">
                                        <tr>
                                            <td colspan="9">
                                                <div class="empty-state">
                                                    <i class="ri-list-unordered"></i>
                                                    <h6>Selecciona una recepción para comenzar</h6>
                                                    <p>Aquí podrás escanear productos y registrar entradas.</p>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div class="card mb-5">
                        <div class="card-header bg-white border-0">
                            <strong>Observaciones generales</strong>
                        </div>
                        <div class="card-body">
                            <textarea id="observacionesRecepcion" class="form-control observaciones-box" rows="4"
                                placeholder="Agrega comentarios u observaciones de la recepción..."></textarea>
                        </div>
                    </div>

                    <!-- Sticky footer -->
                    <div class="save-bar">
                        <div>
                            <small class="text-muted d-block">Último paso</small>
                            <strong>Confirma y registra la recepción</strong>
                        </div>
                        <button class="btn btn-success btn-save" onclick="guardarRecepcion()">
                            <i class="ri-check-line me-1"></i> Registrar recepción
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php footerAdmin($data); ?>