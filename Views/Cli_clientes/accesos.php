<?php headerAdmin($data); ?>

<style>
    .access-card {
        border: 0;
        border-radius: 16px;
        box-shadow: 0 4px 24px rgba(15, 34, 58, .08);
    }

    [data-bs-theme="dark"] .access-card,
    [data-layout-mode="dark"] .access-card {
        box-shadow: 0 4px 24px rgba(0, 0, 0, .25);
    }

    .access-tabs {
        border-bottom: 1px solid var(--vz-border-color);
        gap: 5px;
    }

    .access-tabs .nav-link {
        border: 0;
        color: var(--vz-secondary-color);
        font-weight: 600;
        border-radius: 10px 10px 0 0;
        padding: 12px 18px;
    }

    .access-tabs .nav-link.active {
        color: var(--vz-primary);
        background: rgba(var(--vz-primary-rgb), .10);
        border-bottom: 2px solid var(--vz-primary);
    }

    .section-title {
        color: var(--vz-heading-color);
        font-size: 16px;
        font-weight: 700;
    }

    .section-description {
        color: var(--vz-secondary-color);
        font-size: 13px;
    }

    .input-group-text {
        min-width: 46px;
        justify-content: center;
        color: var(--vz-secondary-color);
        background: var(--vz-tertiary-bg);
        border-color: var(--vz-border-color);
    }

    .form-control[readonly] {
        background: var(--vz-tertiary-bg);
        color: var(--vz-body-color);
        cursor: not-allowed;
    }

    .password-preview {
        font-family: Consolas, Monaco, monospace;
        letter-spacing: 1px;
    }

    .info-box {
        border: 1px solid rgba(var(--vz-info-rgb), .25);
        background: rgba(var(--vz-info-rgb), .08);
        border-radius: 12px;
        padding: 16px;
    }

    .security-box {
        border: 1px solid var(--vz-border-color);
        border-radius: 12px;
        background: var(--vz-card-bg);
        padding: 17px;
    }

    .status-card {
        border: 1px solid var(--vz-border-color);
        border-radius: 12px;
        padding: 14px;
        height: 100%;
        background: var(--vz-card-bg);
    }

    .device-icon {
        width: 38px;
        height: 38px;
        display: inline-flex;
        justify-content: center;
        align-items: center;
        border-radius: 50%;
        color: var(--vz-primary);
        background: rgba(var(--vz-primary-rgb), .10);
        font-size: 18px;
    }

    .log-detail {
        color: var(--vz-secondary-color);
        font-size: 12px;
    }

    .table-access-logs thead th {
        white-space: nowrap;
        background: var(--vz-tertiary-bg);
        color: var(--vz-heading-color);
        font-size: 12px;
        text-transform: uppercase;
    }

    .table-access-logs td {
        vertical-align: middle;
    }
</style>

<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <input
                type="hidden"
                id="idclientePagina"
                value="<?= intval($data['idcliente'] ?? 0); ?>">

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-3">
                <div>
                    <h4 class="mb-1">Accesos del cliente</h4>
                    <p class="text-muted mb-0">
                        Administra las credenciales del portal y consulta su historial de accesos.
                    </p>
                </div>

                <a
                    href="<?= base_url(); ?>/cli_clientes"
                    class="btn btn-light">

                    <i class="ri-arrow-left-line me-1"></i>
                    Regresar
                </a>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-xl-3 col-md-6">
                    <div class="status-card">
                        <small class="text-muted">Cliente</small>
                        <h6 class="mb-0 mt-1" id="lblCliente">
                            Cargando...
                        </h6>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="status-card">
                        <small class="text-muted">Estado del acceso</small>
                        <h6 class="mb-0 mt-1" id="lblEstadoAcceso">
                            Sin configurar
                        </h6>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="status-card">
                        <small class="text-muted">Contraseña temporal</small>
                        <h6 class="mb-0 mt-1" id="lblEstadoPassword">
                            Sin información
                        </h6>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6">
                    <div class="status-card">
                        <small class="text-muted">Último acceso</small>
                        <h6 class="mb-0 mt-1" id="lblUltimoAcceso">
                            Sin accesos
                        </h6>
                    </div>
                </div>
            </div>

            <div class="card access-card">
                <div class="card-body">

                    <ul class="nav nav-tabs access-tabs mb-4">
                        <li class="nav-item">
                            <button
                                class="nav-link active"
                                data-bs-toggle="tab"
                                data-bs-target="#tabCredenciales"
                                type="button">

                                <i class="ri-key-2-line me-1"></i>
                                Credenciales
                            </button>
                        </li>

                        <li class="nav-item">
                            <button
                                class="nav-link"
                                id="btnTabHistorico"
                                data-bs-toggle="tab"
                                data-bs-target="#tabHistorico"
                                type="button">

                                <i class="ri-history-line me-1"></i>
                                Histórico de accesos
                            </button>
                        </li>
                    </ul>

                    <div class="tab-content">

                        <div
                            class="tab-pane fade show active"
                            id="tabCredenciales">

                            <div
                                id="loaderCliente"
                                class="alert alert-light border">

                                <span class="spinner-border spinner-border-sm me-2"></span>
                                Consultando información del cliente...
                            </div>

                            <form
                                id="formAccesoCliente"
                                class="d-none"
                                autocomplete="off"
                                novalidate>

                                <input
                                    type="hidden"
                                    id="idcliente"
                                    name="idcliente">

                                <input
                                    type="hidden"
                                    id="idusuario_acceso"
                                    name="idusuario_acceso">
 
                                <div class="section-title">
                                    Credenciales del portal
                                </div>

                                <p class="section-description">
                                    El usuario y correo se obtienen de la información registrada del cliente.
                                </p>

                                <div class="row g-3">

                                    <div class="col-lg-6">
                                        <label class="form-label">
                                            Usuario
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-user-line"></i>
                                            </span>

                                            <input
                                                type="text"
                                                class="form-control"
                                                id="usuario_acceso"
                                                name="usuario_acceso"
                                                readonly
                                                required>
                                        </div>
                                    </div>

                                    <div class="col-lg-6">
                                        <label class="form-label">
                                            Correo de acceso
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-mail-line"></i>
                                            </span>

                                            <input
                                                type="email"
                                                class="form-control"
                                                id="correo_acceso"
                                                name="correo_acceso"
                                                readonly
                                                required>
                                        </div>
                                    </div>

                                    <div class="col-lg-8">
                                        <label class="form-label">
                                            Contraseña temporal
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-lock-password-line"></i>
                                            </span>

                                            <input
                                                type="password"
                                                class="form-control password-preview"
                                                id="password_temporal"
                                                name="password_temporal"
                                                minlength="15"
                                                maxlength="15"
                                                placeholder="Genera una contraseña segura"
                                                autocomplete="new-password"
                                                required>

                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                id="btnMostrarPassword">

                                                <i class="ri-eye-line"></i>
                                            </button>

                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                id="btnCopiarPassword">

                                                <i class="ri-file-copy-line"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-lg-4 d-flex align-items-end">
                                        <button
                                            type="button"
                                            class="btn btn-soft-primary w-100"
                                            id="btnGenerarPassword">

                                            <i class="ri-magic-line me-1"></i>
                                            Generar contraseña
                                        </button>
                                    </div>

                                    <div class="col-12">
                                        <label class="form-label">
                                            Liga de acceso
                                        </label>

                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="ri-links-line"></i>
                                            </span>

                                            <input
                                                type="url"
                                                class="form-control"
                                                id="liga_acceso"
                                                name="liga_acceso"
                                                readonly
                                                required>

                                            <button
                                                type="button"
                                                class="btn btn-outline-secondary"
                                                id="btnAbrirPortal">

                                                <i class="ri-external-link-line"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <div class="security-box">
                                            <div class="d-flex justify-content-between align-items-start gap-3">
                                                <div>
                                                    <h6 class="mb-1">
                                                        Doble autenticación
                                                    </h6>

                                                    <p class="text-muted mb-0">
                                                        Después de ingresar su contraseña, el cliente recibirá un PIN de seis dígitos en su correo.
                                                    </p>
                                                </div>

                                                <div class="form-check form-switch form-switch-lg">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input"
                                                        id="doble_autenticacion"
                                                        name="doble_autenticacion"
                                                        value="1">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="info-box mt-4">
                                    <div class="d-flex gap-3">
                                        <i class="ri-information-line fs-3 text-info"></i>

                                        <div>
                                            <h6 class="mb-1">
                                                Guardar y enviar accesos
                                            </h6>

                                            <p class="text-muted mb-0">
                                                Al ejecutar esta función se enviarán al correo del cliente el usuario, la contraseña temporal y la liga de acceso. El cliente deberá cambiar su contraseña al ingresar.
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button
                                        type="submit"
                                        class="btn btn-success btn-label">

                                        <i class="ri-mail-send-line label-icon align-middle fs-16 me-2"></i>
                                        Guardar y enviar accesos
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div
                            class="tab-pane fade"
                            id="tabHistorico">

                            <div class="d-flex justify-content-between flex-wrap gap-3 mb-3">
                                <div>
                                    <div class="section-title">
                                        Histórico de accesos
                                    </div>

                                    <p class="section-description mb-0">
                                        Consulta accesos correctos, intentos fallidos, PIN y cambios de contraseña.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="btn btn-soft-secondary"
                                    id="btnActualizarLogs">

                                    <i class="ri-refresh-line me-1"></i>
                                    Actualizar
                                </button>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-lg-3">
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroFechaInicio">
                                </div>

                                <div class="col-lg-3">
                                    <input
                                        type="date"
                                        class="form-control"
                                        id="filtroFechaFin">
                                </div>

                                <div class="col-lg-3">
                                    <select
                                        class="form-select"
                                        id="filtroResultado">

                                        <option value="">Todos</option>
                                        <option value="EXITOSO">Exitoso</option>
                                        <option value="FALLIDO">Fallido</option>
                                        <option value="BLOQUEADO">Bloqueado</option>
                                        <option value="INFORMATIVO">Informativo</option>
                                    </select>
                                </div>

                                <div class="col-lg-3">
                                    <input
                                        type="search"
                                        class="form-control"
                                        id="filtroBusqueda"
                                        placeholder="Dispositivo, evento o IP">
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-access-logs">
                                    <thead>
                                        <tr>
                                            <th>Fecha y hora</th>
                                            <th>Evento</th>
                                            <th>Resultado</th>
                                            <th>Dispositivo</th>
                                            <th>Navegador</th>
                                            <th>Sistema</th>
                                            <th>IP</th>
                                            <th>Ubicación</th>
                                            <th>Detalle</th>
                                        </tr>
                                    </thead>

                                    <tbody id="tbodyLogs">
                                        <tr>
                                            <td colspan="9" class="text-center py-5 text-muted">
                                                Ingresa a esta pestaña para consultar el histórico.
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

    <footer class="footer">
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-6">
                    <script>
                        document.write(new Date().getFullYear());
                    </script>
                    © LDR.
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