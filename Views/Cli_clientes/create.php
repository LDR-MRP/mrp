<?php headerAdmin($data); ?>

<style>
    .client-form-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(15, 34, 58, 0.08);
    }

    .client-tabs {
        border-bottom: 1px solid #e9ebec;
        gap: 4px;
        flex-wrap: wrap;
    }

    .client-tabs .nav-link {
        border: 0;
        color: #64748b;
        font-weight: 600;
        border-radius: 10px 10px 0 0;
        padding: 12px 16px;
    }

    .client-tabs .nav-link:hover {
        color: var(--vz-primary);
        background: rgba(64, 81, 137, 0.06);
    }

    .client-tabs .nav-link.active {
        color: var(--vz-primary);
        background: rgba(64, 81, 137, 0.10);
        border-bottom: 2px solid var(--vz-primary);
    }

    .section-title {
        font-size: 15px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 4px;
    }

    .section-description {
        color: #94a3b8;
        font-size: 13px;
        margin-bottom: 18px;
    }

    .input-group-text {
        min-width: 44px;
        justify-content: center;
        background: #f8fafc;
        border-color: #dfe3e8;
    }

    .form-label {
        font-weight: 600;
        color: #475569;
        margin-bottom: 7px;
    }

    .required::after {
        content: " *";
        color: #ef4444;
    }

    .dynamic-client-section {
        display: none;
        padding: 16px;
        border: 1px dashed #d8dee5;
        border-radius: 12px;
        background: #fafbfc;
        margin-top: 16px;
    }

    .tab-counter {
        font-size: 11px;
        margin-left: 5px;
    }

    .form-actions {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
        border-top: 1px solid #e9ebec;
        padding: 14px 0 4px;
        z-index: 5;
    }

    .table-form thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 13px;
        font-weight: 700;
    }

    .document-card {
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 18px;
        text-align: center;
        background: #f8fafc;
    }

    .rfc-status {
        font-size: 12px;
        margin-top: 5px;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <div class="row mb-3">
                <div class="col-12">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <h4 class="mb-1">Registro de clientes y distribuidores</h4>
                            <p class="text-muted mb-0">
                                Registra la información general, fiscal, comercial y bancaria del cliente.
                            </p>
                        </div>

                        <a href="<?= base_url(); ?>/cli_clientes" class="btn btn-light">
                            <i class="ri-arrow-left-line align-bottom me-1"></i>
                            Regresar
                        </a>
                    </div>
                </div>
            </div>

            <form id="formCliente" autocomplete="off" novalidate>
                <input type="hidden" id="idcliente" name="idcliente" value="<?= intval($data['idcliente'] ?? 0); ?>">

                <div class="card client-form-card">
                    <div class="card-body">

                        <ul class="nav nav-tabs client-tabs mb-4" id="clientTabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-general"
                                    type="button">
                                    <i class="ri-user-line me-1"></i> General
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fiscal"
                                    type="button">
                                    <i class="ri-file-list-3-line me-1"></i> Fiscal
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-contactos"
                                    type="button">
                                    <i class="ri-contacts-book-line me-1"></i> Contactos
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sucursales"
                                    type="button">
                                    <i class="ri-building-2-line me-1"></i> Sucursales
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-direcciones"
                                    type="button">
                                    <i class="ri-map-pin-line me-1"></i> Direcciones
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-comercial"
                                    type="button">
                                    <i class="ri-briefcase-4-line me-1"></i> Comercial
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-bancos"
                                    type="button">
                                    <i class="ri-bank-line me-1"></i> Bancos
                                </button>
                            </li>

                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-documentos"
                                    type="button">
                                    <i class="ri-folder-upload-line me-1"></i> Documentos
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content">

                            <!-- GENERAL -->
                            <div class="tab-pane fade show active" id="tab-general">
                                <div class="section-title">Información general</div>
                                <div class="section-description">
                                    Selecciona el tipo de cliente y registra sus datos principales.
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <label for="idtipo_cliente" class="form-label required">Tipo de cliente</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-group-line"></i>
                                            </span>
                                            <select class="form-select" id="idtipo_cliente" name="idtipo_cliente"
                                                required>
                                                <option value="">Selecciona un tipo</option>
                                                <!-- <option value="CLIENTE">Cliente</option> -->
                                                <option value="1">Distribuidor</option>
                                                <option value="2">Cliente interno</option>
                                                <option value="3">Cliente externo</option>
                                                <option value="4                            ">Cliente gubernamental
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="tipo_persona" class="form-label required">Tipo de persona</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-user-settings-line"></i>
                                            </span>
                                            <select class="form-select" id="tipo_persona" name="tipo_persona" required>
                                                <option value="">Selecciona una opción</option>
                                                <option value="FISICA">Persona física</option>
                                                <option value="MORAL">Persona moral</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="codigo_cliente" class="form-label">Código del cliente</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-barcode-line"></i>
                                            </span>
                                            <input type="text" class="form-control" id="codigo_cliente"
                                                name="codigo_cliente" placeholder="Ej. CLI-000001" maxlength="30"
                                                readonly>
                                        </div>
                                        <small class="text-muted">Código generado automáticamente.</small>
                                    </div>

                                    <div class="col-lg-6 col-md-6">
                                        <label for="razon_social" class="form-label required">Razón social</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-building-line"></i>
                                            </span>
                                            <input type="text" class="form-control" id="razon_social"
                                                name="razon_social"
                                                placeholder="Ej. Comercializadora del Centro, S.A. de C.V."
                                                maxlength="200" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-6 col-md-6">
                                        <label for="nombre_comercial" class="form-label required">Nombre
                                            comercial</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-store-2-line"></i>
                                            </span>
                                            <input type="text" class="form-control" id="nombre_comercial"
                                                name="nombre_comercial" placeholder="Ej. Autos del Centro"
                                                maxlength="150" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="telefono" class="form-label">Teléfono principal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-phone-line"></i>
                                            </span>
                                            <input type="tel" class="form-control" id="telefono" name="telefono"
                                                placeholder="Ej. 7221234567" maxlength="15">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="celular" class="form-label">Teléfono celular</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-smartphone-line"></i>
                                            </span>
                                            <input type="tel" class="form-control" id="celular" name="celular"
                                                placeholder="Ej. 7229876543" maxlength="15">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="correo" class="form-label required">Correo principal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-mail-line"></i>
                                            </span>
                                            <input type="email" class="form-control" id="correo" name="correo"
                                                placeholder="Ej. contacto@empresa.com" maxlength="150" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="sitio_web" class="form-label">Sitio web</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-global-line"></i>
                                            </span>
                                            <input type="url" class="form-control" id="sitio_web" name="sitio_web"
                                                placeholder="https://www.empresa.com">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="fecha_alta" class="form-label">Fecha de alta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-calendar-line"></i>
                                            </span>
                                            <input type="date" class="form-control" id="fecha_alta" name="fecha_alta"
                                                value="<?= date('Y-m-d'); ?>">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="estado_cliente" class="form-label required">Estado</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-toggle-line"></i>
                                            </span>
                                            <select class="form-select" id="estado_cliente" name="estado" required>
                                                <option value="1">Activo</option>
                                                <option value="2">Inactivo</option>
                                                <option value="3">Suspendido</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- DISTRIBUIDOR -->
                                <div class="dynamic-client-section" id="sectionDistribuidor">
                                    <div class="section-title">
                                        <i class="ri-truck-line me-1"></i>
                                        Información del distribuidor
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label required">Clave de distribuidor</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-key-2-line"></i>
                                                </span>
                                                <input type="text" class="form-control dynamic-required"
                                                    name="clave_distribuidor" placeholder="Ej. DIST-001">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Zona comercial</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-map-2-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="zona_comercial"
                                                    placeholder="Ej. Centro">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Territorio</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-road-map-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="territorio"
                                                    placeholder="Ej. Estado de México">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Responsable comercial</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-user-star-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="responsable_comercial"
                                                    placeholder="Nombre del ejecutivo">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Acceso al portal</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-login-box-line"></i>
                                                </span>
                                                <select class="form-select" name="requiere_acceso_portal">
                                                    <option value="1">Sí</option>
                                                    <option value="0">No</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Correo de acceso</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-mail-lock-line"></i>
                                                </span>
                                                <input type="email" class="form-control" name="correo_acceso"
                                                    placeholder="usuario@distribuidor.com">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CLIENTE INTERNO -->
                                <div class="dynamic-client-section" id="sectionInterno">
                                    <div class="section-title">
                                        <i class="ri-team-line me-1"></i>
                                        Información del cliente interno
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label required">Número de empleado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-id-card-line"></i>
                                                </span>
                                                <input type="text" class="form-control dynamic-required"
                                                    name="numero_empleado" placeholder="Ej. EMP-1025">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Área o departamento</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-organization-chart"></i>
                                                </span>
                                                <input type="text" class="form-control" name="departamento"
                                                    placeholder="Ej. Administración">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Centro de costos</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-funds-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="centro_costos"
                                                    placeholder="Ej. CC-1001">
                                            </div>
                                        </div>

                                        <div class="col-lg-6 col-md-6">
                                            <label class="form-label">Jefe inmediato</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-user-follow-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="jefe_inmediato"
                                                    placeholder="Nombre del responsable">
                                            </div>
                                        </div>

                                        <div class="col-lg-6 col-md-6">
                                            <label class="form-label">Correo corporativo</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-mail-send-line"></i>
                                                </span>
                                                <input type="email" class="form-control" name="correo_corporativo"
                                                    placeholder="usuario@empresa.com">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- CLIENTE EXTERNO -->
                                <div class="dynamic-client-section" id="sectionExterno">
                                    <div class="section-title">
                                        <i class="ri-external-link-line me-1"></i>
                                        Información del cliente externo
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Origen del cliente</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-share-forward-line"></i>
                                                </span>
                                                <select class="form-select" name="origen_cliente">
                                                    <option value="">Selecciona una opción</option>
                                                    <option value="REFERIDO">Referido</option>
                                                    <option value="WEB">Sitio web</option>
                                                    <option value="REDES">Redes sociales</option>
                                                    <option value="PROSPECCION">Prospección comercial</option>
                                                    <option value="OTRO">Otro</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Ejecutivo asignado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-user-received-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="ejecutivo_asignado"
                                                    placeholder="Nombre del ejecutivo">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Segmento de mercado</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-pie-chart-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="segmento_mercado"
                                                    placeholder="Ej. Automotriz">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- GUBERNAMENTAL -->
                                <div class="dynamic-client-section" id="sectionGubernamental">
                                    <div class="section-title">
                                        <i class="ri-government-line me-1"></i>
                                        Información gubernamental
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-lg-6 col-md-6">
                                            <label class="form-label required">Dependencia</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-government-line"></i>
                                                </span>
                                                <input type="text" class="form-control dynamic-required"
                                                    name="dependencia" placeholder="Nombre de la dependencia">
                                            </div>
                                        </div>

                                        <div class="col-lg-6 col-md-6">
                                            <label class="form-label">Unidad administrativa</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-building-4-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="unidad_administrativa"
                                                    placeholder="Ej. Dirección de Recursos Materiales">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Nivel de gobierno</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-building-3-line"></i>
                                                </span>
                                                <select class="form-select" name="nivel_gobierno">
                                                    <option value="">Selecciona una opción</option>
                                                    <option value="FEDERAL">Federal</option>
                                                    <option value="ESTATAL">Estatal</option>
                                                    <option value="MUNICIPAL">Municipal</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Partida presupuestal</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-file-chart-line"></i>
                                                </span>
                                                <input type="text" class="form-control" name="partida_presupuestal"
                                                    placeholder="Ej. 54101">
                                            </div>
                                        </div>

                                        <div class="col-lg-4 col-md-6">
                                            <label class="form-label">Tipo de contratación</label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    <i class="ri-auction-line"></i>
                                                </span>
                                                <select class="form-select" name="tipo_contratacion">
                                                    <option value="">Selecciona una opción</option>
                                                    <option value="LICITACION">Licitación pública</option>
                                                    <option value="INVITACION">Invitación restringida</option>
                                                    <option value="ADJUDICACION">Adjudicación directa</option>
                                                    <option value="CONVENIO">Convenio</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- FISCAL -->
                            <div class="tab-pane fade" id="tab-fiscal">
                                <div class="section-title">Información fiscal</div>
                                <div class="section-description">
                                    Registra los datos fiscales exactamente como aparecen en la Constancia de Situación
                                    Fiscal.
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <label for="rfc" class="form-label required">RFC</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-file-user-line"></i>
                                            </span>
                                            <input type="text" class="form-control text-uppercase" id="rfc" name="rfc"
                                                placeholder="Ej. ABC123456T12" maxlength="13" required>
                                            <button class="btn btn-outline-secondary" type="button" id="btnValidarRFC">
                                                <i class="ri-shield-check-line"></i>
                                            </button>
                                        </div>
                                        <div id="rfcStatus" class="rfc-status"></div>
                                    </div>

                                    <div class="col-lg-4 col-md-6 persona-fisica-field">
                                        <label for="curp" class="form-label">CURP</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-fingerprint-line"></i>
                                            </span>
                                            <input type="text" class="form-control text-uppercase" id="curp" name="curp"
                                                placeholder="Ej. CUCX900101HMCRRL09" maxlength="18">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="regimen_fiscal" class="form-label required">Régimen fiscal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-file-list-2-line"></i>
                                            </span>
                                            <select class="form-select" id="regimen_fiscal" name="regimen_fiscal"
                                                required>
                                                <option value="">Selecciona un régimen</option>
                                                <option value="601">601 - General de Ley Personas Morales</option>
                                                <option value="603">603 - Personas Morales con Fines no Lucrativos
                                                </option>
                                                <option value="605">605 - Sueldos y Salarios</option>
                                                <option value="606">606 - Arrendamiento</option>
                                                <option value="612">612 - Personas Físicas con Actividades Empresariales
                                                </option>
                                                <option value="616">616 - Sin obligaciones fiscales</option>
                                                <option value="621">621 - Incorporación Fiscal</option>
                                                <option value="626">626 - Régimen Simplificado de Confianza</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="uso_cfdi" class="form-label required">Uso de CFDI</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-file-paper-2-line"></i>
                                            </span>
                                            <select class="form-select" id="uso_cfdi" name="uso_cfdi" required>
                                                <option value="">Selecciona una opción</option>
                                                <option value="G01">G01 - Adquisición de mercancías</option>
                                                <option value="G03">G03 - Gastos en general</option>
                                                <option value="I03">I03 - Equipo de transporte</option>
                                                <option value="S01">S01 - Sin efectos fiscales</option>
                                                <option value="CP01">CP01 - Pagos</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="codigo_postal_fiscal" class="form-label required">Código postal
                                            fiscal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-map-pin-2-line"></i>
                                            </span>
                                            <input type="text" class="form-control" id="codigo_postal_fiscal"
                                                name="codigo_postal_fiscal" placeholder="Ej. 50000" maxlength="5"
                                                required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="correo_facturacion" class="form-label">Correo de facturación</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-mail-check-line"></i>
                                            </span>
                                            <input type="email" class="form-control" id="correo_facturacion"
                                                name="correo_facturacion" placeholder="facturacion@empresa.com">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label for="requiere_factura" class="form-label">Requiere factura</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-file-text-line"></i>
                                            </span>
                                            <select class="form-select" id="requiere_factura" name="requiere_factura">
                                                <option value="1">Sí</option>
                                                <option value="0">No</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CONTACTOS -->
                            <div class="tab-pane fade" id="tab-contactos">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="section-title">Contactos</div>
                                        <div class="section-description mb-0">
                                            Registra uno o varios contactos administrativos, comerciales o de
                                            facturación.
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-soft-primary" id="btnAgregarContacto">
                                        <i class="ri-user-add-line me-1"></i>
                                        Agregar contacto
                                    </button>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle table-nowrap table-form">
                                        <thead>
                                            <tr>
                                                <th>Nombre</th>
                                                <th>Puesto</th>
                                                <th>Correo</th>
                                                <th>Teléfono</th>
                                                <th>Tipo</th>
                                                <th>Notificar</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbodyContactos"></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- SUCURSALES -->
                            <div class="tab-pane fade" id="tab-sucursales">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <div class="section-title">Sucursales</div>
                                        <div class="section-description mb-0">
                                            Registra las sedes, agencias o ubicaciones operativas del cliente.
                                        </div>
                                    </div>

                                    <button type="button" class="btn btn-soft-primary" id="btnAgregarSucursal">
                                        <i class="ri-building-2-line me-1"></i>
                                        Agregar sucursal
                                    </button>
                                </div>

                                <div id="contenedorSucursales"></div>
                            </div>

                            <!-- DIRECCIONES -->
                            <div class="tab-pane fade" id="tab-direcciones">
                                <div class="section-title">Direcciones</div>
                                <div class="section-description">
                                    Registra las direcciones fiscal, de entrega, cobranza o correspondencia.
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label required">Tipo de dirección</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-map-pin-user-line"></i>
                                            </span>
                                            <select class="form-select" name="tipo_direccion" required>
                                                <option value="">Selecciona una opción</option>
                                                <option value="FISCAL">Fiscal</option>
                                                <option value="ENTREGA">Entrega</option>
                                                <option value="COBRANZA">Cobranza</option>
                                                <option value="CORRESPONDENCIA">Correspondencia</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-5 col-md-6">
                                        <label class="form-label required">Calle</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-road-map-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="calle"
                                                placeholder="Nombre de la calle" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label required">Número exterior</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-home-4-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="numero_exterior"
                                                placeholder="Ej. 125" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label">Número interior</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-home-gear-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="numero_interior"
                                                placeholder="Ej. Local 3">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label required">Colonia</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-community-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="colonia"
                                                placeholder="Nombre de la colonia" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6">
                                        <label class="form-label required">Código postal</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-mail-open-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="codigo_postal"
                                                placeholder="Ej. 50000" maxlength="5" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label required">Municipio o alcaldía</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-building-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="municipio"
                                                placeholder="Ej. Toluca" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label required">Estado</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-map-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="estado_republica"
                                                placeholder="Ej. Estado de México" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label required">País</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-earth-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="pais" value="México" required>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <label class="form-label">Referencias</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-map-pin-time-line"></i>
                                            </span>
                                            <textarea class="form-control" name="referencias" rows="3"
                                                placeholder="Entre calles, color del edificio, referencias de acceso, etc."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- COMERCIAL -->
                            <div class="tab-pane fade" id="tab-comercial">
                                <div class="section-title">Condiciones comerciales</div>
                                <div class="section-description">
                                    Define las condiciones de crédito, listas de precio y responsables comerciales.
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Lista de precios</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-price-tag-3-line"></i>
                                            </span>
                                            <select class="form-select" name="lista_precio">
                                                <option value="">Selecciona una lista</option>
                                                <option value="GENERAL">Precio general</option>
                                                <option value="DISTRIBUIDOR">Precio distribuidor</option>
                                                <option value="MAYOREO">Mayoreo</option>
                                                <option value="GOBIERNO">Gobierno</option>
                                                <option value="ESPECIAL">Precio especial</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Moneda</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-money-dollar-circle-line"></i>
                                            </span>
                                            <select class="form-select" name="moneda">
                                                <option value="MXN">MXN - Peso mexicano</option>
                                                <option value="USD">USD - Dólar estadounidense</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Forma de pago predeterminada</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-secure-payment-line"></i>
                                            </span>
                                            <select class="form-select" name="forma_pago">
                                                <option value="">Selecciona una opción</option>
                                                <option value="TRANSFERENCIA">Transferencia</option>
                                                <option value="CREDITO">Crédito</option>
                                                <option value="CONTADO">Contado</option>
                                                <option value="CHEQUE">Cheque</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Límite de crédito</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-wallet-3-line"></i>
                                            </span>
                                            <input type="number" class="form-control" name="limite_credito"
                                                placeholder="0.00" min="0" step="0.01">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Días de crédito</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-calendar-check-line"></i>
                                            </span>
                                            <input type="number" class="form-control" name="dias_credito"
                                                placeholder="Ej. 30" min="0" max="365">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Descuento autorizado</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-percent-line"></i>
                                            </span>
                                            <input type="number" class="form-control" name="descuento_autorizado"
                                                placeholder="Ej. 5" min="0" max="100" step="0.01">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Ejecutivo de cuenta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-user-star-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="ejecutivo_cuenta"
                                                placeholder="Nombre del ejecutivo">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Canal de venta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-shopping-bag-3-line"></i>
                                            </span>
                                            <select class="form-select" name="canal_venta">
                                                <option value="">Selecciona una opción</option>
                                                <option value="DIRECTO">Venta directa</option>
                                                <option value="DISTRIBUIDOR">Distribuidor</option>
                                                <option value="GOBIERNO">Gobierno</option>
                                                <option value="INTERNO">Interno</option>
                                                <option value="ECOMMERCE">Portal/Ecommerce</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Clasificación comercial</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-medal-line"></i>
                                            </span>
                                            <select class="form-select" name="clasificacion_comercial">
                                                <option value="">Selecciona una opción</option>
                                                <option value="A">A - Estratégico</option>
                                                <option value="B">B - Frecuente</option>
                                                <option value="C">C - Ocasional</option>
                                                <option value="NUEVO">Nuevo</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <label class="form-label">Observaciones comerciales</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-sticky-note-line"></i>
                                            </span>
                                            <textarea class="form-control" name="observaciones_comerciales" rows="3"
                                                placeholder="Acuerdos especiales, restricciones, condiciones o comentarios comerciales."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- BANCOS -->
                            <div class="tab-pane fade" id="tab-bancos">
                                <div class="section-title">Información bancaria</div>
                                <div class="section-description">
                                    Registra la información bancaria utilizada para pagos, devoluciones o referencias.
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Banco</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-bank-card-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="banco" placeholder="Ej. BBVA">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Titular de la cuenta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-user-3-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="titular_cuenta"
                                                placeholder="Nombre o razón social">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Número de cuenta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-hashtag"></i>
                                            </span>
                                            <input type="text" class="form-control" name="numero_cuenta"
                                                placeholder="Número de cuenta">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">CLABE interbancaria</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-lock-password-line"></i>
                                            </span>
                                            <input type="text" class="form-control" id="clabe" name="clabe"
                                                placeholder="18 dígitos" maxlength="18">
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Moneda de la cuenta</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-currency-line"></i>
                                            </span>
                                            <select class="form-select" name="moneda_cuenta">
                                                <option value="MXN">MXN</option>
                                                <option value="USD">USD</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 col-md-6">
                                        <label class="form-label">Referencia bancaria</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-file-code-line"></i>
                                            </span>
                                            <input type="text" class="form-control" name="referencia_bancaria"
                                                placeholder="Referencia asignada">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DOCUMENTOS -->
                            <div class="tab-pane fade" id="tab-documentos">
                                <div class="section-title">Documentos del cliente</div>
                                <div class="section-description">
                                    Adjunta documentación fiscal, legal, bancaria o comercial.
                                </div>

                                <div class="row g-3">
                                    <div class="col-lg-4">
                                        <label class="form-label">Tipo de documento</label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-file-list-line"></i>
                                            </span>
                                            <select class="form-select" name="tipo_documento">
                                                <option value="">Selecciona una opción</option>
                                                <option value="CONSTANCIA_FISCAL">Constancia de situación fiscal
                                                </option>
                                                <option value="OPINION_CUMPLIMIENTO">Opinión de cumplimiento</option>
                                                <option value="ACTA_CONSTITUTIVA">Acta constitutiva</option>
                                                <option value="IDENTIFICACION">Identificación oficial</option>
                                                <option value="COMPROBANTE_DOMICILIO">Comprobante de domicilio</option>
                                                <option value="ESTADO_CUENTA">Estado de cuenta</option>
                                                <option value="CONTRATO">Contrato</option>
                                                <option value="ORDEN_COMPRA">Orden de compra</option>
                                                <option value="OTRO">Otro</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-lg-8">
                                        <label class="form-label">Archivo</label>
                                        <div class="document-card">
                                            <i class="ri-upload-cloud-2-line fs-1 text-muted"></i>
                                            <p class="mb-2">Selecciona un archivo PDF, XML, JPG o PNG</p>
                                            <input type="file" class="form-control" name="documento"
                                                accept=".pdf,.xml,.jpg,.jpeg,.png">
                                        </div>
                                    </div>
                                </div>
                            </div>



                            <div class="form-actions mt-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- <button type="reset" class="btn btn-light" id="btnLimpiar">
                                    <i class="ri-eraser-line me-1"></i>
                                    Limpiar
                                </button> -->

                                    <button type="submit" class="btn btn-primary btn-label" id="btnGuardarCliente">
                                        <i class="ri-save-3-line label-icon align-middle fs-16 me-2"></i>
                                        Guardar cliente
                                    </button>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </form>

        </div>
    </div>

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