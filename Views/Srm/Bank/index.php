<?php require_once("Views/Template/Srm/header_srm.php"); ?>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- 1. BREADCRUMBS SECUNDARIOS (NATIVO FLUIDO) -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between rounded px-3 py-2 bg-transparent">
                        <h4 class="mb-sm-0 fw-bold text-dark fs-15">Cuentas Bancarias</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/srm/dashboard">Resumen</a></li>
                                <li class="breadcrumb-item active text-primary">Mis Cuentas Bancarias</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. FILA PRINCIPAL: 50/50 SPLIT (FORMULARIO IZQUIERDA & REGLAS GRAFITO DERECHA) -->
            <div class="row mb-4">
                
                <!-- COLUMNA IZQUIERDA: Formulario de Registro (Estilo Buzón) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100">
                        <div class="card-header bg-transparent border-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold text-dark mb-1 fs-16"><i class="ri-upload-cloud-line me-2 text-primary"></i>Nueva Cuenta Bancaria</h5>
                            <p class="text-muted fs-13 mb-0">Asocie sus cuentas bancarias.</p>
                        </div>
                        <div class="card-body p-4">
                            <form id="formCargaBanco" enctype="multipart/form-data">
                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted text-uppercase fs-11">Institución Bancaria <span class="text-danger">*</span></label>
                                        <select name="id_banco" id="id_banco" class="form-select border-0 bg-light-subtle" required>
                                            <option value="">Cargando bancos...</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted text-uppercase fs-11">Moneda <span class="text-danger">*</span></label>
                                        <select id="id_moneda_banco" name="id_moneda_banco" class="form-select border-0 bg-light-subtle" required>
                                            <option value="MXN">MXN - Peso Mexicano</option>
                                            <option value="USD">USD - Dólar Americano</option>
                                            <option value="EUR">EUR - Euro</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold text-muted text-uppercase fs-11">CLABE Interbancaria (Nacional) <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-transparent text-muted border-end-0"><i class="ri-bank-card-line"></i></span>
                                        <input type="text" name="clabe" class="form-control bg-light-subtle border-start-0 ps-0" placeholder="18 dígitos" maxlength="18">
                                    </div>
                                </div>

                                <div class="row g-3 mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted text-uppercase fs-11">Número de Cuenta <span class="text-danger">*</span></label>
                                        <input type="text" name="cuenta" class="form-control bg-light-subtle border-0" placeholder="Ej. 0123456789">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted text-uppercase fs-11">¿Es Cuenta Principal?</label>
                                        <select name="banco_es_principal" class="form-select border-0 bg-light-subtle">
                                            <option value="0">No</option>
                                            <option value="1">Sí, marcar como principal</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="text-center text-muted mb-3 fs-11 text-uppercase fw-bold">---------------- Transferencias Internacionales ----------------</div>

                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted text-uppercase fs-11">SWIFT / BIC</label>
                                        <input type="text" name="swift_bic" class="form-control bg-light-subtle border-0" placeholder="Código SWIFT">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-muted text-uppercase fs-11">IBAN</label>
                                        <input type="text" name="iban" class="form-control bg-light-subtle border-0" placeholder="Código IBAN">
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold text-muted text-uppercase fs-11">Carátula Bancaria (PDF) <span class="text-danger">*</span></label>
                                    <input type="file" name="caratula_pdf" class="form-control bg-light-subtle border-0" accept=".pdf" required>
                                    <small class="text-muted fs-11 mt-1 d-block">Obligatorio para validación antifraude (debe verse la CLABE y Razón Social).</small>
                                </div>

                                <!-- CORRECCIÓN: Botón de acción integrado al fondo del formulario -->
                                <button type="submit" id="btnGuardarBanco" class="btn btn-primary btn-lg w-100 fw-bold shadow-none">
                                    <i class="ri-checkbox-circle-line align-middle me-1"></i> Registrar Cuenta Bancaria
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA: Reglas de Validación (Sabor Grafito LDR) -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-3 h-100" style="background-color: #2E3230 !important;">
                        <div class="card-body p-4 p-lg-5">
                            <h5 class="text-white fw-bold mb-3 fs-16"><i class="ri-shield-user-line me-2 text-warning"></i>Proceso de Validación Antifraude</h5>
                            <p class="text-white-50 fs-13 mb-4 lh-lg">
                                Por seguridad contra fraudes, toda nueva cuenta bancaria pasará a estatus PENDIENTE de forma automática. Finanzas (Nivel 2) cruzará esta información con el PDF de su Carátula Bancaria antes de autorizar pagos:
                            </p>
                            
                            <ul class="list-unstyled vstack gap-3 text-white fs-13 mb-0">
                                <li class="d-flex align-items-center">
                                    <i class="ri-checkbox-circle-fill text-success fs-18 me-3"></i> Para cuentas nacionales, la CLABE interbancaria (18 dígitos) es estrictamente indispensable.
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="ri-checkbox-circle-fill text-success fs-18 me-3"></i> El nombre del titular de la cuenta debe coincidir exactamente con la Razón Social registrada.
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="ri-checkbox-circle-fill text-success fs-18 me-3"></i> Para proveedores extranjeros, se requiere el código SWIFT/BIC o IBAN según el país de destino.
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

            <!-- 4. HISTORIAL A LO ANCHO EN LA BASE (Sabor Tabla con Acento de Marca) -->
            <div class="row">
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-3">
                        <div class="card-header bg-transparent border-bottom-0 pt-4 pb-0">
                            <h5 class="card-title fw-bold text-body mb-0">Mis Cuentas Registradas</h5>
                        </div>
                        <div class="card-body p-0 mt-3">
                            <div class="table-responsive">
                                <table class="table table-nowrap align-middle mb-0 table-hover" id="tbl-bancos-proveedor" style="width: 100% !important;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="ps-4 text-uppercase text-muted fs-11 fw-bold">Banco</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">CLABE / Cuenta</th>
                                            <th class="text-uppercase text-muted fs-11 fw-bold">Moneda</th>
                                            <th class="pe-4 text-uppercase text-muted fs-11 fw-bold">Estatus</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbl-body-bancos-srm" class="border-top-0">
                                        <!-- JS inyectará el historial de forma asíncrona -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <div class="card-footer border-top-0 py-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted fw-medium">
                                    <i class="ri-shield-check-line text-success me-1"></i> Cuentas auditadas bajo los estándares de control interno de LDR Solutions
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once("Views/Template/Srm/footer_srm.php"); ?>