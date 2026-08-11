<?php

$portalAutenticado =
    !empty($_SESSION['portal_autenticado'])
    && !empty($_SESSION['portal_idusuario_acceso'])
    && !empty($_SESSION['portal_idcliente']);

$nombrePortal = trim(
    ($_SESSION['portal_nombre'] ?? '')
    . ' '
    . ($_SESSION['portal_apellido'] ?? '')
);

$pageName = $data['page_name'] ?? 'home';
$esVistaPublica = in_array($pageName, ['login', 'restablecer-password'], true);
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.6.0/fonts/remixicon.css">

    <link rel="shortcut icon" href="<?= media(); ?>/images/ldr_logo_color.png">

    <title><?= htmlspecialchars($data['page_tag'] ?? 'Portal de Pedidos - LDR Solutions', ENT_QUOTES, 'UTF-8'); ?></title>

    <link rel="stylesheet" href="<?= media(); ?>/Orders/styles.css">

    <?php if (!empty($data['page_styles']) && is_array($data['page_styles'])): ?>
        <?php foreach ($data['page_styles'] as $style): ?>
            <link rel="stylesheet" href="<?= media(); ?>/<?= $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <link href="<?= media(); ?>/minimal/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css">
</head>

<body class="page-<?= htmlspecialchars($pageName, ENT_QUOTES, 'UTF-8'); ?>">

    <header class="top-header">
        <div class="container header-content">

            <a href="<?= $portalAutenticado ? base_url() . '/orders/home' : base_url() . '/orders/login'; ?>" class="logo">
                <img src="<?= media(); ?>/images/ldr-logo-dark.png" alt="LDR Solutions" class="logo-img">
            </a>

            <?php if (!$esVistaPublica): ?>

                <nav class="nav-menu" id="navMenu">

                    <a href="<?= base_url(); ?>/orders/home#catalogo" class="<?= $pageName === 'home' ? 'active' : ''; ?>">
                        <i class="ri-store-2-line"></i><span> Catálogo</span>
                    </a>

                    <a href="<?= base_url(); ?>/orders/home#beneficios">
                        <i class="ri-award-line"></i><span> Beneficios</span>
                    </a>

                    <a href="<?= base_url(); ?>/orders/micuenta" class="<?= $pageName === 'micuenta' ? 'active' : ''; ?>">
                        <i class="ri-user-settings-line"></i><span> Mi cuenta</span>
                    </a>

                    <a href="<?= base_url(); ?>/orders/carrito" class="cart-link <?= $pageName === 'carrito' ? 'active' : ''; ?>">
                        <i class="ri-shopping-cart-2-line"></i>
                        <span id="cartCount">0</span>
                    </a>

                    <div class="mobile-auth-actions">
                        <?php if (!empty($nombrePortal)): ?>
                            <div class="mobile-user-info">
                                <i class="ri-user-line"></i>
                                <span><?= htmlspecialchars($nombrePortal, ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                </nav>

                <div class="auth-actions">
                    <?php if (!empty($nombrePortal)): ?>
                        <div class="header-user-info">
                            <span class="header-user-icon"><i class="ri-user-3-line"></i></span>
                            <div class="header-user-text">
                                <small>Bienvenido</small>
                                <strong><?= htmlspecialchars($nombrePortal, ENT_QUOTES, 'UTF-8'); ?></strong>
                            </div>
                        </div>
                    <?php endif; ?>

                    <a href="<?= base_url(); ?>/orders/logout" class="btn btn-light">
                        <i class="ri-logout-box-r-line"></i> Cerrar sesión
                    </a>
                </div>

                <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú" aria-expanded="false">
                    <i class="ri-menu-line"></i>
                </button>

            <?php endif ?>
       

        </div>
    </header>