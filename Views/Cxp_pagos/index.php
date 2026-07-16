<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- 1. BREADCRUMBS (Estilo EMR) -->
            <div class="row align-items-center mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-transparent">
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                <li class="breadcrumb-item active text-primary">Dispersión de Pagos</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. HEADER CON DESCRIPCIÓN Y ACCIÓN MULTIBANCO (Soberbio & Moderno) -->
            <div class="row align-items-center mb-4">
                <div class="col-md-6 col-lg-7">
                    <div class="d-flex align-items-center">
                        <div class="avatar-md me-4">
                            <span class="avatar-title text-white rounded-circle fs-2 shadow-lg border border-light" style="background-color: #C46623 !important; color: #ffffff !important;">
                                    <i class="ri-refund-2-line"></i>
                            </span>
                        </div>
                        <div>
                            <h3 class="mb-1 fw-bold text-uppercase ls-1 text-body">Programación de Pagos</h3>
                            <p class="text-muted mb-0 fs-14">
                                Seleccione las facturas autorizadas para generar el archivo de transferencia masiva (SPEI).
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Dropdown de banco emisor y botón de generación alineados -->
                <div class="col-md-6 col-lg-5 d-flex justify-content-md-end justify-content-start mt-4 mt-md-0 gap-3">
                    <div style="width: 200px;">
                        <select id="sel-banco-origen" class="form-select form-select-lg border-0 shadow-sm bg-white" style="height: 50px;">
                            <option value="BBVA">Dispersar desde: BBVA</option>
                            <option value="BANORTE">Dispersar desde: BANORTE</option>
                        </select>
                    </div>
                    <button id="btn-generar-layout" class="btn btn-success btn-lg btn-label waves-effect waves-light shadow-md" style="height: 50px;" disabled>
                        <i class="ri-file-text-line label-icon align-middle fs-18 me-2"></i> Generar Layout (<span id="lbl-count-selected">0</span>)
                    </button>
                </div>
            </div>

            <!-- 3. TABLA CARD DE DISPERSIÓN -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-xl overflow-hidden" style="border-radius: 12px;">
                        <div class="bg-primary" style="height: 4px;"></div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0 table-hover" id="tbl-cxp-payments" style="width: 100% !important;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th width="40" class="ps-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="check-all-payments">
                                                </div>
                                            </th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Factura</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Proveedor</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Fecha Vencimiento</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Banco Destino</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold ls-1 py-3">CLABE Interbancaria</th>
                                            <th class="pe-4 text-end text-uppercase text-muted fs-11 fw-bold ls-1 py-3">Monto de Pago</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl-body-cxp-payments" class="border-top-0">
                                        <!-- Loader Inicial -->
                                        <tr>
                                            <td colspan="7" class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status"></div>
                                                <div class="mt-2 text-muted fs-13">Buscando facturas programables para pago...</div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="card-footer border-top-0 py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-medium">
                                    <i class="ri-shield-check-line text-success me-1"></i> Auditoría integrada con la base de datos.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php footerAdmin($data); ?>