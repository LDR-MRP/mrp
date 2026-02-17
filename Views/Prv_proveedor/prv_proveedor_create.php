<?php headerAdmin($data); ?>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <section id="view-create-proveedor">

                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-sm rounded px-3 py-2 bg-white">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Dashboard</a></li>
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/prv_proveedores">Proveedores</a></li>
                                    <li class="breadcrumb-item active">Alta de Registro</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title bg-primary text-white rounded-circle fs-3 shadow-lg">
                                    <i class="ri-user-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <h4 class="mb-1 text-dark fw-bold ls-05">Nuevo Proveedor</h4>
                                <p class="text-muted mb-0 fs-13">Complete la información fiscal y comercial para dar de alta al socio.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formProveedor" autocomplete="off">
                    <div class="row">
                        <div class="col-lg-8">

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px; border-top: 4px solid #405189;">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-government-line text-primary me-1 fs-14 align-middle"></i> Información Fiscal
                                    </h5>

                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Razón Social / Nombre Legal <span class="text-danger">*</span></label>
                                            <input type="text" name="razon_social" class="form-control form-control-lg bg-light border-0"
                                                placeholder="Ej. Distribuidora de Insumos del Norte S.A. de C.V.">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">RFC <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-barcode-line"></i></span>
                                                <input type="text" name="rfc" class="form-control border-start-0 ps-0" placeholder="XAXX010101000" maxlength="13" style="text-transform: uppercase;">
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Clave Interna <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-key-2-line"></i></span>
                                                <input type="text" name="clv_proveedor" class="form-control border-start-0 ps-0" placeholder="Auto o Manual (Ej: PRV-001)">
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Nombre Comercial (Marca)</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-store-2-line"></i></span>
                                                <input type="text" name="nombre_comercial" class="form-control border-start-0 ps-0" placeholder="¿Cómo se conoce en el mercado?">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-contacts-book-line text-info me-1 fs-14 align-middle"></i> Contacto y Ubicación
                                    </h5>

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Persona de Contacto</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-user-star-line"></i></span>
                                                <input type="text" name="contacto" class="form-control border-start-0 ps-0" placeholder="Nombre del representante">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Teléfono</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-phone-line"></i></span>
                                                <input type="text" name="telefono" class="form-control border-start-0 ps-0" placeholder="(000) 000-0000">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Correo Electrónico</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-white border-end-0 text-muted"><i class="ri-mail-send-line"></i></span>
                                                <input type="email" name="correo_electronico" class="form-control border-start-0 ps-0" placeholder="facturacion@empresa.com">
                                            </div>
                                        </div>
                                        <div class="col-md-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Dirección Fiscal Completa</label>
                                            <textarea name="direccion_fiscal" class="form-control" rows="3" placeholder="Calle, Número, Colonia, Ciudad, Estado, CP" style="resize: none;"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px; border-top: 4px solid #0ab39c;">
                                <div class="card-body p-4">
                                    <h5 class="card-title mb-4 text-uppercase fw-bold text-muted fs-12 ls-1">
                                        <i class="ri-bank-card-2-line text-success me-1 fs-14 align-middle"></i> Perfil Financiero
                                    </h5>

                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Moneda Default</label>
                                            <select name="idmoneda_predeterminada" class="form-select mb-3">
                                                <option value="1">MXN - Peso Mexicano</option>
                                                <option value="2">USD - Dólar Americano</option>
                                                <option value="3">EUR - Euro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Límite de Crédito</label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light fw-bold">$</span>
                                                <input type="number" step="0.01" name="limite_credito" class="form-control text-end fw-bold" value="0.00">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Días Crédito</label>
                                            <div class="input-group">
                                                <input type="number" name="dias_credito" class="form-control text-center" value="0">
                                                <span class="input-group-text bg-light fs-12">Días</span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Método de Pago Preferido</label>
                                            <input type="text" name="metodo_pago_predeterminado" class="form-control" placeholder="Ej: Transferencia Electrónica (PPD)">
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <div class="col-lg-4">

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-header bg-soft-primary border-bottom border-light">
                                    <h6 class="card-title mb-0 text-primary fw-bold"><i class="ri-settings-4-line me-1"></i> Acciones</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch form-switch-lg mb-3" dir="ltr">
                                        <input type="checkbox" class="form-check-input" id="customSwitchsizelg" checked="">
                                        <label class="form-check-label fw-medium" for="customSwitchsizelg">Proveedor Activo</label>
                                        <p class="text-muted fs-11 mb-0">Desactivar para bloquear compras.</p>
                                    </div>

                                    <hr class="border-dashed my-3">

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary btn-lg shadow-sm waves-effect waves-light">
                                            <i class="ri-save-line align-middle me-1"></i> Guardar Cambios
                                        </button>
                                        <button type="button" class="btn btn-light btn-label waves-effect waves-light" data-redirect="prv_proveedores">
                                            <i class="ri-arrow-go-back-line label-icon align-middle fs-16 me-2"></i> Cancelar y Volver
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card border-0 shadow-lg mb-4" style="border-radius: 10px;">
                                <div class="card-body text-center p-4">
                                    <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Logotipo de Empresa</h6>
                                    <div class="profile-user position-relative d-inline-block mx-auto mb-3">
                                        <div class="avatar-lg p-1 bg-light rounded-circle shadow-sm">
                                            <img src="<?= base_url(); ?>/assets/images/users/multi-user.jpg" class="img-fluid rounded-circle" alt="user-profile-image">
                                        </div>
                                        <div class="avatar-xs p-0 rounded-circle profile-photo-edit position-absolute end-0 bottom-0">
                                            <input id="profile-img-file-input" type="file" class="profile-img-file-input">
                                            <label for="profile-img-file-input" class="profile-photo-edit avatar-xs">
                                                <span class="avatar-title rounded-circle bg-light text-body shadow-sm">
                                                    <i class="ri-camera-fill"></i>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <p class="fs-12 text-muted">Sube el logo para identificarlo en las órdenes de compra.</p>
                                </div>
                            </div>

                            <div class="card bg-primary border-0 shadow-lg" style="border-radius: 10px;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <h5 class="card-title text-white fs-14 fw-bold mb-1">¿Necesitas ayuda?</h5>
                                            <p class="text-white-50 fs-12 mb-0">Contacta a soporte si el RFC no valida correctamente.</p>
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
            <div class="row">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-sm-0 text-uppercase"><i class="ri-user-add-line align-bottom"></i> Nuevo Proveedor
                        </h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/prv_proveedores">Proveedores</a>
                                </li>
                                <li class="breadcrumb-item active">Nuevo</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <form id="formProveedor" autocomplete="off">
                        <div class="card shadow-sm border-0">
                            <div class="card-header border-bottom-dashed">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h5 class="card-title mb-0 text-primary">
                                            <i class="ri- Government-line me-1"></i> Información Fiscal y General
                                        </h5>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Clave Proveedor <span
                                                class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-key-2-line"></i></span>
                                            <input type="text" name="clv_proveedor" class="form-control"
                                                placeholder="Ej: PROV-001">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">RFC <span class="text-danger">*</span></label>
                                        <input type="text" name="rfc" class="form-control"
                                            placeholder="RFC de 12 o 13 dígitos" maxlength="13">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Razón Social <span
                                                class="text-danger">*</span></label>
                                        <input type="text" name="razon_social" class="form-control"
                                            placeholder="Nombre legal completo">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Nombre Comercial</label>
                                        <input type="text" name="nombre_comercial" class="form-control"
                                            placeholder="Nombre de marca">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Contacto (Atención a)</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-user-star-line"></i></span>
                                            <input type="text" name="contacto" class="form-control"
                                                placeholder="Nombre de la persona">
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Correo Electrónico</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-mail-line"></i></span>
                                            <input type="email" name="correo_electronico" class="form-control"
                                                placeholder="ejemplo@proveedor.com">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Teléfono</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="ri-phone-line"></i></span>
                                            <input type="text" name="telefono" class="form-control"
                                                placeholder="Número de contacto">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold text-muted">Dirección Fiscal</label>
                                        <textarea name="direccion_fiscal" class="form-control" rows="2"
                                            placeholder="Calle, número, colonia, CP..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 mt-3">
                            <div class="card-header border-bottom-dashed">
                                <h5 class="card-title mb-0 text-success">
                                    <i class="ri-bank-card-line me-1"></i> Condiciones Comerciales
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Límite de Crédito</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" step="0.01" name="limite_credito" class="form-control"
                                                value="0.00">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-bold">Días de Crédito</label>
                                        <input type="number" name="dias_credito" class="form-control" value="0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label fw-bold">Moneda Predeterminada</label>
                                        <select name="idmoneda_predeterminada" class="form-select">
                                            <option value="1">MXN - Peso Mexicano</option>
                                            <option value="2">USD - Dólar Americano</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Método de Pago</label>
                                        <input type="text" name="metodo_pago_predeterminado" class="form-control"
                                            placeholder="Transferencia, PPD, etc.">
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-light d-flex justify-content-end p-3">
                                <button type="button" class="btn btn-light border me-2" data-redirect="prv_proveedores">
                                    <i class="ri-close-line"></i> Cancelar
                                </button>
                                <button type="submit" class="btn btn-primary px-4">
                                    <i class="ri-save-3-line"></i> Guardar Proveedor
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
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
</div>

<?php footerAdmin($data); ?>