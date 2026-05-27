<?php require_once("Views/Template/Srm/header_srm.php"); ?>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Breadcrumb -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between rounded px-3 py-2 bg-transparent">
                        <h4 class="mb-sm-0 fw-bold text-dark fs-15">Buzón de Recepción de Facturas</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/srm/dashboard">Resumen</a></li>
                                <li class="breadcrumb-item active text-primary">Carga de Facturas</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fila Principal (Formulario y Reglas de Negocio) -->
            <div class="row mb-4">
                
                <!-- COLUMNA IZQUIERDA: Formulario de Carga -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold text-dark mb-1 fs-16"><i class="ri-upload-cloud-line me-2 text-primary"></i>Cargar Nueva Factura</h5>
                            <p class="text-muted fs-13 mb-0">Asocie sus comprobantes fiscales (XML y PDF) a una Orden de Compra autorizada.</p>
                        </div>
                        <div class="card-body p-4">
                            <form id="formCargaFactura" enctype="multipart/form-data">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold fs-13 text-uppercase">Asociar Orden de Compra <span class="text-danger">*</span></label>
                                    <select name="id_orden_compra" id="sel-orden-compra" class="form-select form-select-lg bg-light border-0" required>
                                        <option value="">Cargando órdenes autorizadas...</option>
                                    </select>
                                </div>

                                <div class="row g-3 mb-4">
                                    <!-- Carga XML -->
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold fs-13 text-uppercase">Archivo XML <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0 text-success"><i class="ri-code-s-slash-line fs-18"></i></span>
                                            <input type="file" name="factura_xml" class="form-control bg-light border-0" accept=".xml" required>
                                        </div>
                                    </div>
                                    <!-- Carga PDF -->
                                    <div class="col-sm-6">
                                        <label class="form-label fw-semibold fs-13 text-uppercase">Archivo PDF Representación Física <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-0 text-danger"><i class="ri-file-pdf-line fs-18"></i></span>
                                            <input type="file" name="factura_pdf" class="form-control bg-light border-0" accept=".pdf" required>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" id="btnSubirFactura" class="btn btn-primary btn-lg w-100 fw-bold shadow-none">
                                    <i class="ri-checkbox-circle-line align-middle me-1"></i> Validar y Cargar Factura
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Checklist de validación (Compliance / CxP) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: #2E3230 !important;">
                        <div class="card-body p-4 p-lg-5">
                            <h5 class="text-white fw-bold mb-3 fs-16"><i class="ri-shield-check-line me-2 text-warning"></i>Reglas de Validación del SAT & LDR</h5>
                            <p class="text-white-50 fs-13 mb-4lh-lg">
                                Nuestro buzón de validación automatizado ejecutará el validador del SAT de forma síncrona. Asegúrese de cumplir con los siguientes puntos para evitar el rechazo del pago:
                            </p>
                            
                            <ul class="list-unstyled vstack gap-3 text-white fs-13 mb-0">
                                <li class="d-flex align-items-center">
                                    <i class="ri-checkbox-circle-fill text-success fs-18 me-3"></i> El RFC del emisor dentro del XML debe coincidir con su RFC registrado.
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="ri-checkbox-circle-fill text-success fs-18 me-3"></i> El XML debe estar vigente en el repositorio del SAT.
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="ri-checkbox-circle-fill text-success fs-18 me-3"></i> El monto total de la factura no debe exceder el saldo pendiente de la Orden de Compra asociada.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Historial de Facturas Cargadas -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold text-dark mb-0">Facturas Cargadas en el Ecosistema</h5>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0 table-hover" id="tbl-invoices">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">Serie / Folio</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">Fecha de Carga</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">OC Asociada</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold text-end">Monto Facturado</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">Estatus Validación</th>
                                            <th class="pe-4 text-uppercase text-muted fs-11 fw-bold text-end">Evidencia</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl-body-invoices">
                                        <!-- Loader Inicial -->
                                        <tr>
                                            <td colspan="6" class="text-center py-5">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Cargando historial de facturas...</span>
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

<?php require_once("Views/Template/Srm/footer_srm.php"); ?>