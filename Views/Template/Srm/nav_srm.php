<!-- SIDEBAR MENÚ -->
<div class="app-menu navbar-menu border-end bg-dark">
    <!-- LOGO Area -->
    <div class="navbar-brand-box py-4">
        <a href="<?= base_url(); ?>/srm/dashboard" class="logo logo-dark">
            <span class="logo-sm">
                <img src="https://ldrsolutions.mx/v2/img/logos/logoLDR.svg" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="https://ldrsolutions.mx/v2/img/logos/logoLDR.svg" alt="" height="30">
            </span>
        </a>
    </div>

    <!-- Menú de Navegación -->
    <div id="scrollbar">
        <div class="container-fluid">
            <div id="two-column-menu"></div>
            <ul class="navbar-nav" id="navbar-nav">
                
                <li class="menu-title"><span>Menú Principal</span></li>

                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="<?= base_url(); ?>/srm/dashboard">
                        <i class="ri-dashboard-2-line"></i> <span>Resumen</span>
                    </a>
                </li>

                <!-- Expediente Digital -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="<?= base_url(); ?>/srm/dossier">
                        <i class="ri-archive-line"></i> <span>Expediente Digital</span>
                    </a>
                </li>

                <li class="menu-title"><span>Operaciones</span></li>

                <!-- Órdenes de Compra -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="<?= base_url(); ?>/srm/purchaseOrders">
                        <i class="ri-shopping-bag-3-line"></i> <span>Órdenes de Compra</span>
                    </a>
                </li>

                <!-- Buzón de Facturas -->
                <li class="nav-item">
                    <a class="nav-link menu-link" href="<?= base_url(); ?>/srm/invoices">
                        <i class="ri-file-text-line"></i> <span>Buzón de Facturas</span>
                    </a>
                </li>

            </ul>
        </div>
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Sidebar Backlay para móviles -->
<div class="vertical-overlay" id="vertical-overlay"></div>