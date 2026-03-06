<?php
    headerAdmin($data);
    $isEdit = !empty($data['supplier']);
    $p = $isEdit ? $data['supplier'] : [];
?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <style>
                body {
                    background-color: #f4f7f6;
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                /* Estilo para resaltar los asteriscos de campos obligatorios */
                label.form-label:contains('*'), 
                label.form-label {
                    position: relative;
                }
                .text-danger-asterisk {
                    color: #ef4444; /* Rojo vibrante */
                    font-weight: bold;
                    margin-left: 2px;
                }
            </style>
            <section id="view-create-proveedor">

                <div class="row align-items-center mb-4 text-start">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/prv_proveedores">Proveedores</a></li>
                                    <li class="breadcrumb-item active">Gestión de Registro</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3 text-start">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title <?= $isEdit ? 'bg-warning' : 'bg-primary' ?> text-white rounded-circle fs-3 shadow-lg">
                                    <i class="<?= $data['page_icon'] ?>"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 text-dark fw-bold ls-05"><?= $data['page_action'] ?></h4>
                                <p class="text-muted mb-0 fs-13"><?= $data['page_description'] ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formProveedor" autocomplete="off">
                    <div class="row">
                        <div class="col-lg-8">
                            
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px; border-top: 4px solid #405189;">
                                <div class="card-body p-4 text-start">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-id-card-line text-primary me-1 fs-14 align-middle"></i> Datos Maestros
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">ID Empresa <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ri-building-line"></i></span>
                                                <input type="text" name="id_empresa" class="form-control bg-light" value="<?= $isEdit ? $p['id_empresa'] : '1' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">RFC <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ri-barcode-box-line"></i></span>
                                                <input type="text" name="rfc" class="form-control" maxlength="13" placeholder="ABCD123456EFG" style="text-transform: uppercase;" value="<?= $isEdit ? $p['rfc'] : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Origen <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ri-earth-line"></i></span>
                                                <select name="origen" class="form-select">
                                                    <option value="Nacional" <?= ($isEdit && $p['origen'] == 'Nacional') ? 'selected' : '' ?>>Nacional</option>
                                                    <option value="Extranjero" <?= ($isEdit && $p['origen'] == 'Extranjero') ? 'selected' : '' ?>>Extranjero</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Razón Social <span class="text-danger-asterisk">*</span></label>
                                            <input type="text" name="razon_social" class="form-control form-control-lg bg-light border-0 fw-bold" value="<?= $isEdit ? $p['razon_social'] : '' ?>">
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Nombre Comercial <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white"><i class="ri-store-2-line"></i></span>
                                                <input type="text" name="nombre_comercial" class="form-control" value="<?= $isEdit ? $p['nombre_comercial'] : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Tipo de Persona <span class="text-danger-asterisk">*</span></label>
                                            <select name="id_tipo_persona" class="form-select">
                                                <option>Seleccione una opción...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Régimen Fiscal <span class="text-danger-asterisk">*</span></label>
                                            <select name="id_regimen_fiscal" id="id_regimen_fiscal" class="form-select">
                                                <option>Seleccione una opción...</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body p-4 text-start">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-map-pin-2-line text-info me-1 fs-14 align-middle"></i> Ubicación y Domicilio
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Tipo Dirección <span class="text-danger-asterisk">*</span></label>
                                            <select name="tipo" class="form-select">
                                                <option value="Fiscal">Fiscal</option>
                                                <option value="Bodega">Bodega</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Código Postal <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group border border-info rounded">
                                                <span class="input-group-text bg-white border-0"><i class="ri-map-pin-user-line text-info"></i></span>
                                                <input type="text" name="cp" id="cp" class="form-control border-0 fw-bold" maxlength="5" value="<?= $isEdit ? $p['cp'] : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Principal?</label>
                                            <select name="es_principal" class="form-select">
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Calle <span class="text-danger-asterisk">*</span></label>
                                            <input type="text" name="calle" class="form-control" value="<?= $isEdit ? $p['calle'] : '' ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Ext <span class="text-danger-asterisk">*</span></label>
                                            <input type="text" name="num_ext" class="form-control" value="<?= $isEdit ? $p['num_ext'] : '' ?>">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Int</label>
                                            <input type="text" name="num_int" class="form-control" placeholder="N/A" value="<?= $isEdit ? $p['num_int'] : 'N/A' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Colonia <span class="text-danger-asterisk">*</span></label>
                                            <select name="colonia" id="colonia" class="form-select">
                                                <option>Seleccione una opción...</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Municipio</label>
                                            <input type="text" name="municipio" id="municipio" class="form-control bg-light" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Estado</label>
                                            <input type="text" name="estado" id="estado" class="form-control bg-light" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Ciudad</label>
                                            <input type="text" name="ciudad" id="ciudad" class="form-control bg-light" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body p-4 text-start">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-contacts-line text-warning me-1 fs-14 align-middle"></i> Contacto de Enlace
                                    </h5>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Nombre Completo <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ri-user-3-line"></i></span>
                                                <input type="text" name="nombre" class="form-control" value="<?= $isEdit ? $p['nombre'] : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Puesto <span class="text-danger-asterisk">*</span></label>
                                            <input type="text" name="puesto" class="form-control" value="<?= $isEdit ? $p['puesto'] : '' ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Email <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ri-mail-line"></i></span>
                                                <input type="email" name="email" class="form-control" placeholder="ejemplo@correo.com" value="<?= $isEdit ? $p['email'] : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Teléfono <span class="text-danger-asterisk">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light"><i class="ri-phone-line"></i></span>
                                                <input type="text" name="telefono" id="telefono" class="form-control" placeholder="(00) 0000-0000" value="<?= $isEdit ? $p['telefono'] : '' ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end justify-content-center pb-2">
                                            <div class="form-check form-switch form-switch-md">
                                                <input class="form-check-input" type="checkbox" name="notificar_compras" value="1" checked>
                                                <label class="form-check-label fs-11 fw-bold text-muted text-uppercase">OC</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            
                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body p-4 text-start">
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                                            <i class="ri-save-3-line align-middle me-1"></i> Guardar Registro
                                        </button>
                                        <a href="<?= base_url(); ?>/prv_proveedores" class="btn btn-soft-dark">Volver</a>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px; border-top: 4px solid #0ab39c;">
                                <div class="card-body p-4 text-start">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-bank-card-2-line text-success me-1 fs-14 align-middle"></i> Finanzas
                                    </h5>
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted">Cuenta Contable <span class="text-danger-asterisk">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light"><i class="ri-git-repository-line"></i></span>
                                            <select name="id_cuenta_contable" class="form-select">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted">Límite de Crédito <span class="text-danger-asterisk">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold">$</span>
                                            <input type="number" step="0.01" name="limite_credito" class="form-control text-end fw-bold" value="<?= $isEdit ? $p['limite_credito'] : '0.00' ?>">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted">Condiciones de pago <span class="text-danger-asterisk">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light fw-bold">$</span>
                                            <select name="id_condicion_pago" class="form-select">
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">Moneda</label>
                                            <select name="id_moneda_defecto" class="form-select">
                                                <option value="MXN">MXN</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted">IVA %</label>
                                            <input type="number" name="tasa_iva_default" class="form-control" value="<?= $isEdit ? $p['tasa_iva_default'] : '16.00' ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card bg-primary border-0 shadow-lg" style="border-radius: 10px;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1 text-start">
                                            <h5 class="card-title text-white fs-14 fw-bold mb-1">¿Necesitas ayuda?</h5>
                                            <p class="text-white-50 fs-12 mb-0">Contacta a soporte si el CP no devuelve colonias.</p>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <i class="ri-customer-service-2-line text-white-50 fs-24"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </form>
            </section>
        </div>
    </div>
</div>
<?php footerAdmin($data); ?>