<!DOCTYPE html>
<html lang="es-MX" 
      data-layout="vertical" 
      data-topbar="light" 
      data-sidebar="dark" 
      data-sidebar-size="lg" 
      data-sidebar-image="none" 
      data-preloader="disable" 
      data-bs-theme="light"
      data-layout-width="fluid"
      data-layout-style="default"
      data-layout-position="fixed"
      data-theme="modern"
      data-theme-colors="orange">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $data['page_tag']; ?></title>

    <!-- Bootstrap Css -->
    <link href="<?= media(); ?>/minimal/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="<?= media(); ?>/minimal/css/icons.min.css" rel="stylesheet" type="text/css" />
    <!-- App Css (Velzon) -->
    <link href="<?= media(); ?>/minimal/css/app.min.css" rel="stylesheet" type="text/css" />
    <!-- Sweet Alert -->
    <link href="<?= media(); ?>/minimal/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css" />
</head>
<body>

    <!-- Layout Wrapper NATIVO -->
    <div id="layout-wrapper">

        <!-- HEADER / TOPBAR -->
        <header id="page-topbar" class="border-bottom">
            <div class="layout-width">
                <div class="navbar-header">
                    <div class="d-flex">
                        <!-- Botón Hamburger para colapsar Sidebar -->
                        <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                            <span class="hamburger-icon">
                                <span></span>
                                <span></span>
                                <span></span>
                            </span>
                        </button>
                    </div>

                    <!-- Lado Derecho: Notificaciones y Perfil -->
                    <div class="d-flex align-items-center">

                        <!-- Campana de Notificaciones -->
                        <div class="dropdown topbar-head-dropdown ms-1 header-item" id="notificationDropdown">
                            <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" id="page-header-notifications-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class='ri-notification-3-line fs-22'></i>
                                <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger" id="lbl-notif-count">0</span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-end p-0" aria-labelledby="page-header-notifications-dropdown">
                                <div class="dropdown-head bg-primary bg-pattern rounded-top p-3">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="m-0 fs-16 fw-semibold text-white">Notificaciones</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="py-2 px-3 fs-13 text-muted text-center" id="notification-list">
                                    No tienes notificaciones pendientes.
                                </div>
                            </div>
                        </div>

                        <!-- Dropdown de Usuario Proveedor -->
                        <div class="dropdown ms-sm-3 header-item topbar-user">
                            <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="d-flex align-items-center">
                                    <div class="avatar-xs">
                                        <span class="avatar-title rounded-circle bg-success-subtle text-success fw-bold fs-12 text-uppercase" id="lbl-user-avatar">EP</span>
                                    </div>
                                    <span class="text-start ms-xl-2">
                                        <span class="d-none d-xl-inline-block ms-1 fw-semibold user-name-text" id="lbl-user-name">Cargando...</span>
                                        <span class="d-none d-xl-block ms-1 fs-12 text-muted user-name-sub-text">Proveedor</span>
                                    </span>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <h6 class="dropdown-header">¡Bienvenido!</h6>
                                <a class="dropdown-item" href="<?= base_url(); ?>/srm/dossier"><i class="ri-archive-line text-muted fs-16 align-middle me-1"></i> <span class="align-middle">Mi Expediente</span></a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="Sys_Core.Auth.logout('/srm/login')"><i class="ri-shut-down-line text-danger fs-16 align-middle me-1"></i> <span class="align-middle">Cerrar Sesión</span></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Sidebar Navigation -->
        <?php require_once("nav_srm.php"); ?>