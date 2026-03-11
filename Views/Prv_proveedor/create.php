<?php
    headerAdmin($data);
    $isEdit = !empty($data['supplier']);
    $p = $isEdit ? $data['supplier']['data'] : [];
    $d = $data['supplier']['required_documents'];
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
                .custom-timeline-container {
                    position: relative;
                    width: 100%;
                    margin: 0 auto;
                }

                .custom-timeline-list {
                    display: flex;
                    justify-content: space-between;
                    list-style: none;
                    padding: 0;
                    position: relative;
                }

                /* La línea gris de fondo */
                .custom-timeline-list::before {
                    content: "";
                    position: absolute;
                    top: 20px; /* Centrado con el icono */
                    left: 10%;
                    right: 10%;
                    height: 4px;
                    background: #eff2f7;
                    z-index: 0;
                }

                .timeline-item {
                    position: relative;
                    z-index: 1;
                    width: 25%;
                    text-align: center;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                }

                .timeline-icon {
                    width: 40px;
                    height: 40px;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin-bottom: 15px;
                    border: 4px solid #fff; /* Esto hace que la línea no atraviese el icono */
                    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
                }

                /* La card amarilla que tienes en tu diseño */
                .active-card {
                    background: #fff9e6;
                    border: 1px solid #ffeeba;
                    padding: 10px;
                    border-radius: 8px;
                    min-width: 150px;
                }

                /* Color de la línea para pasos completados */
                .timeline-item.completed + .timeline-item::after {
                    content: "";
                    position: absolute;
                    top: 20px;
                    left: -50%;
                    width: 100%;
                    height: 4px;
                    background: #0ab39c; /* Verde éxito de Velzon */
                    z-index: -1;
                }
            </style>
            <section id="view-create-proveedor">
                <!-- Main Banner -->
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
                <!-- End Main Banner -->

                <!-- Tabs Section -->
                <ul class="nav nav-tabs-custom nav-success mb-4" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#tab-datos-generales" role="tab">
                            <i class="ri-building-line me-1"></i> Perfil del Proveedor
                        </a>
                    </li>
                    <?php if($isEdit): ?>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-expediente" role="tab">
                            <i class="ri-folder-open-line me-1"></i> Expediente Digital
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#tab-onboarding" role="tab">
                            <i class="ri-shield-check-line me-1"></i> Onboarding & Auditoría
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
                <!-- End Tabs Section -->

                <!-- Tabs Content Section -->
                <div class="tab-content">    
                    <!-- Master Data -->
                    <section class="tab-pane active" id="tab-datos-generales" role="tabpanel">
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
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-building-line"></i></span>
                                                        <input type="text" name="id_empresa" class="form-control border-0" value="<?= $isEdit ? $p['id_empresa'] : '1' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">RFC <span class="text-danger-asterisk">*</span></label>
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-barcode-box-line"></i></span>
                                                        <input type="text" name="rfc" class="form-control border-0" maxlength="13" placeholder="ABCD123456EFG" style="text-transform: uppercase;" value="<?= $isEdit ? $p['rfc'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Origen <span class="text-danger-asterisk">*</span></label>
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-earth-line"></i></span>
                                                        <select name="origen" class="form-select border-0">
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
                                                    <div class="input-group rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-store-2-line"></i></span>
                                                        <input type="text" name="nombre_comercial" class="form-control border-top-0 border-start-0 border-end-0 rounded-0 ps-0" value="<?= $isEdit ? $p['nombre_comercial'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Tipo de Persona <span class="text-danger-asterisk">*</span></label>
                                                    <select name="id_tipo_persona" class="form-select border-dashed">
                                                        <option>Selecciona una opción...</option>
                                                    </select>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Régimen Fiscal <span class="text-danger-asterisk">*</span></label>
                                                    <select name="id_regimen_fiscal" id="id_regimen_fiscal" class="form-select border-dashed">
                                                        <option>Selecciona una opción...</option>
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
                                                        <input type="text" name="cp" id="cp" class="form-control border-0 fw-bold fs-15 text-info" placeholder="00000" maxlength="5" value="<?= $isEdit ? $p['cp'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label text-uppercase fs-11 fw-semibold text-muted d-block">¿Es Principal?</label>
                                                    <div class="btn-group w-100" role="group">
                                                        <input type="radio" class="btn-check" name="es_principal" id="principal_si" value="1" <?= (!$isEdit || $p['es_principal'] == 1) ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-light-subtle border fs-12 py-2" for="principal_si text-dark">SÍ</label>
                                                        <input type="radio" class="btn-check" name="es_principal" id="principal_no" value="0" <?= ($isEdit && $p['es_principal'] == 0) ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-light-subtle border fs-12 py-2" for="principal_no text-dark">NO</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-8">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Calle <span class="text-danger-asterisk">*</span></label>
                                                    <input type="text" name="calle" class="form-control" placeholder="Nombre de la vialidad" value="<?= $isEdit ? $p['calle'] : '' ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Ext <span class="text-danger-asterisk">*</span></label>
                                                    <input type="text" name="num_ext" class="form-control" placeholder="SN" value="<?= $isEdit ? $p['num_ext'] : '' ?>">
                                                </div>
                                                <div class="col-md-2">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Int</label>
                                                    <input type="text" name="num_int" class="form-control" placeholder="N/A" value="<?= $isEdit ? $p['num_int'] : 'N/A' ?>">
                                                </div>

                                                <div class="col-md-12">
                                                    <div class="p-3 bg-light-subtle rounded-3 border border-light">
                                                        <div class="row g-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label text-uppercase fs-11 fw-bold text-muted">Colonia <span class="text-danger-asterisk">*</span></label>
                                                                <select name="colonia" id="colonia" class="form-select border-dashed">
                                                                    <option>Selecciona una opción...</option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-uppercase fs-11 fw-bold text-muted">Municipio</label>
                                                                <input type="text" name="municipio" id="municipio" class="form-control form-control-plaintext" readonly>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-uppercase fs-11 fw-bold text-muted">Estado</label>
                                                                <input type="text" name="estado" id="estado" class="form-control form-control-plaintext" readonly>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label text-uppercase fs-11 fw-bold text-muted">Ciudad</label>
                                                                <input type="text" name="ciudad" id="ciudad" class="form-control form-control-plaintext" readonly>
                                                            </div>
                                                        </div>
                                                    </div>
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
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-user-3-line"></i></span>
                                                        <input type="text" name="nombre" class="form-control border-0 fs-16" value="<?= $isEdit ? $p['nombre'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted fw-bold">Puesto <span class="text-danger-asterisk">*</span></label>
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <input type="text" name="puesto" class="form-control border-0 fs-16" value="<?= $isEdit ? $p['puesto'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Email <span class="text-danger-asterisk">*</span></label>
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-mail-line"></i></span>
                                                        <input type="email" name="email" class="form-control border-0 fs-16" placeholder="ejemplo@correo.com" value="<?= $isEdit ? $p['email'] : '' ?>">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted">Teléfono <span class="text-danger-asterisk">*</span></label>
                                                    <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                        <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-phone-line"></i></span>
                                                        <input type="text" name="telefono" id="telefono" class="form-control border-0 fs-16" placeholder="(00) 0000-0000" value="<?= $isEdit ? $p['telefono'] : '' ?>">
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
                                                <a href="<?= base_url(); ?>/prv_proveedor" class="btn btn-soft-dark">Volver</a>
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
                                                <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                    <span class="input-group-text bg-white border-0 text-muted fw-bold"><i class="ri-git-repository-line"></i></span>
                                                    <select name="id_cuenta_contable" class="form-select border-0 fs-16">
                                                    </select>
                                                </div>
                                            </div>                                            

                                            <div class="mb-3">
                                                <label class="form-label text-uppercase fs-11 fw-semibold text-muted">IVA</label>
                                                <div class="p-3 bg-light rounded-3 position-relative">
                                                    <div class="d-flex justify-content-between mb-2">
                                                        <span class="badge bg-white text-dark border shadow-sm px-3 py-2 fs-14" id="iva-label">
                                                            <?= $isEdit ? $p['tasa_iva_default'] : '16.00' ?>%
                                                        </span>
                                                        <span class="text-muted fs-11 align-self-center">Desliza para ajustar</span>
                                                    </div>
                                                    <input type="range" name="tasa_iva_default" class="form-range custom-range" 
                                                        min="0" max="16" step="1" 
                                                        value="<?= $isEdit ? $p['tasa_iva_default'] : '16' ?>" 
                                                        oninput="document.getElementById('iva-label').innerText = this.value + '.00%'">
                                                    <div class="d-flex justify-content-between mt-1 px-1">
                                                        <span class="fs-10 text-muted">0%</span>
                                                        <span class="fs-10 text-muted">8%</span>
                                                        <span class="fs-10 text-muted">16%</span>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label text-uppercase fs-11 fw-semibold text-muted">Crédito Autorizado</label>
                                                <div class="input-group border rounded-3 overflow-hidden shadow-none">
                                                    <span class="input-group-text bg-white border-0 text-muted fw-bold">$</span>
                                                    <input type="number" step="0.01" name="limite_credito" class="form-control border-0 text-end fw-bold fs-16" value="<?= $isEdit ? $p['limite_credito'] : '0.00' ?>">
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label text-uppercase fs-11 fw-semibold text-muted">Moneda</label>
                                                <div class="d-flex gap-2">
                                                    <div class="flex-grow-1">
                                                        <input type="radio" class="btn-check" name="id_moneda_defecto" id="mxn" value="MXN" <?= (!$isEdit || $p['id_moneda_defecto'] == 'MXN') ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-light w-100 border text-dark fs-12" for="mxn">MXN</label>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <input type="radio" class="btn-check" name="id_moneda_defecto" id="usd" value="USD" <?= ($isEdit && $p['id_moneda_defecto'] == 'USD') ? 'checked' : '' ?>>
                                                        <label class="btn btn-outline-light w-100 border text-dark fs-12" for="usd">USD</label>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="mb-0">
                                                <label class="form-label text-uppercase fs-11 fw-semibold text-muted">Condiciones de pago</label>
                                                <select name="id_condicion_pago" class="form-select border-light-subtle bg-light-subtle">
                                                </select>
                                            </div>

                                                





                                        </div>
                                    </div>

                                    <div class="card bg-primary border-0 shadow-lg rounded-3">
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

                                    <div class="card border-0 shadow-lg" style="background-color: #e1ebfd;">
                                        <div class="card-body p-3">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0">
                                                    <i class="ri-information-fill text-primary fs-20"></i>
                                                </div>
                                                <div class="flex-grow-1 ms-2">
                                                    <p class="mb-0 fs-12 text-primary-emphasis fw-medium">
                                                        Los campos marcados con <span class="text-danger">*</span> son obligatorios para timbrado CFDI 4.0.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </form>
                    </section>
                    <!-- End Master Data -->

                    <!-- File Section -->
                    <?php if($isEdit): ?>
                    <section class="tab-pane mt-4" id="tab-expediente" role="tabpanel">
                        <div class="row">
                            <div class="col-12 mb-4">
                                <div class="card bg-light-subtle shadow-none border">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="flex-grow-1">
                                                <h5 class="fs-14 mb-1">Progreso de Documentación</h5>
                                                <p class="text-muted mb-0">Completa los  documentos obligatorios para avanzar al siguiente nivel.</p>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <h4 class="text-success mb-0">20%</h4>
                                            </div>
                                        </div>
                                        <div class="progress animated-progress progress-sm mt-3">
                                            <div class="progress-bar bg-success" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php foreach($d AS $doc):?>
                            
                            <div class="col-xl-4 col-md-6">
                                <div class="card border shadow-none mb-3">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar-sm flex-shrink-0">
                                                <div class="avatar-title bg-primary-subtle text-primary rounded fs-22">
                                                    <i class="ri-file-text-line"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="fs-14 mb-0 fw-bold"><?php echo $doc['name']; ?></h6>
                                                <small class="text-muted"><?php echo $doc['required'] ? 'Obligatorio' : 'Opcional'; ?> - <?php echo strtoupper($doc['ext']); ?></small>
                                            </div>
                                        </div>

                                        <div class="dropzone-premium border-dashed rounded-3 p-4 text-center bg-light" 
                                            style="cursor: pointer; border: 2px dashed #d1d5db;">
                                            <i class="ri-upload-2-line fs-24 text-primary mb-2 d-block"></i>
                                            <p class="fs-13 fw-medium mb-1">Arrastra tu archivo aquí</p>
                                            <p class="text-muted fs-11">O haz clic para explorar en tu equipo</p>
                                        </div>
                                        
                                        <div class="mt-2 d-flex align-items-center text-success fs-12 d-none" id="success-csf">
                                            <i class="ri-checkbox-circle-fill me-1"></i> Archivo listo para revisión
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>

                        </div>
                    </section>
                    <!-- End File Section -->

                    <!-- Onboarding Section -->
                    <section class="tab-pane mt-4" id="tab-onboarding" role="tabpanel">
                        <div class="row">
                            <div class="col-12">
                                <div class="card shadow-none border mb-0">
                                    <div class="card-body p-5">
                                        
                                        <div class="text-center mb-5">
                                            <div class="avatar-md mx-auto mb-3">
                                                <div class="avatar-title bg-warning-subtle text-warning display-5 rounded-circle shadow">
                                                    <i class="ri-loader-4-line ri-spin"></i>
                                                </div>
                                            </div>
                                            <h4 class="fw-bold">Estatus: En Proceso de Documentación</h4>
                                            <p class="text-muted mb-0 max-w-600 mx-auto">
                                                Por favor, asegúrate de subir todos los documentos obligatorios en la pestaña 
                                                <strong>Expediente Digital</strong> para avanzar a la fase de validación.
                                            </p>
                                        </div>

                                        <div class="custom-timeline-container py-4">
    <ul class="custom-timeline-list">
        <li class="timeline-item completed">
            <div class="timeline-icon bg-success">
                <i class="ri-check-line text-white"></i>
            </div>
            <div class="timeline-content">
                <h6 class="fs-14 fw-bold mb-1">Registro Inicial</h6>
                <p class="text-muted fs-12 mb-0">06 Mar, 2026</p>
            </div>
        </li>

        <li class="timeline-item active">
            <div class="timeline-icon bg-warning">
                <i class="ri-folder-open-line text-white"></i>
            </div>
            <div class="timeline-content active-card">
                <h6 class="fs-14 fw-bold mb-1 text-warning">Expediente Digital</h6>
                <span class="badge bg-warning text-white">Esperando Archivos</span>
            </div>
        </li>

        <li class="timeline-item">
            <div class="timeline-icon bg-light">
                <i class="ri-shield-check-line text-muted"></i>
            </div>
            <div class="timeline-content">
                <h6 class="fs-14 fw-bold mb-1 text-muted">Validación</h6>
            </div>
        </li>

        <li class="timeline-item">
            <div class="timeline-icon bg-light">
                <i class="ri-bank-card-2-line text-muted"></i>
            </div>
            <div class="timeline-content">
                <h6 class="fs-14 fw-bold mb-1 text-muted">Alta en ERP</h6>
            </div>
        </li>
    </ul>
</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                    <?php endif; ?>
                    <!-- End Onboarding Section -->
                </div>
                <!-- End Tabs Content Section -->
            </section>
        </div>
    </div>
</div>
<?php footerAdmin($data); ?>