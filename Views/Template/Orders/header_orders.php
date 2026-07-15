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

?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">

  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="shortcut icon" href="<?= media(); ?>/images/ldr_logo_color.png">

  <title>
    <?= $data['page_tag'] ?? 'Portal de Pedidos - LDR Solutions'; ?>
  </title>

  <link rel="stylesheet" href="<?= media(); ?>/Orders/styles.css">

  <link href="<?= media(); ?>/minimal/libs/sweetalert2/sweetalert2.min.css" rel="stylesheet" type="text/css">
</head>

<body class="page-<?= $data['page_name'] ?? 'home'; ?>">

  <header class="top-header">
    <div class="container header-content">

      <!-- Logo -->
      <a href="<?= base_url(); ?>/orders/home" class="logo">

        <img src="<?= media(); ?>/images/ldr-logo-dark.png" alt="LDR Solutions" class="logo-img">
      </a>

      <!-- Navegación -->
      <nav class="nav-menu" id="navMenu">

        <a href="<?= base_url(); ?>/orders/home#catalogo">

          Catálogo
        </a>

        <a href="<?= base_url(); ?>/orders/home#beneficios">

          Beneficios
        </a>

        <?php if ($portalAutenticado): ?>

          <a href="<?= base_url(); ?>/orders/micuenta" class="<?= ($data['page_name'] ?? '') === 'micuenta'
              ? 'active'
              : ''; ?>">

            Mi cuenta
          </a>

        <?php endif; ?>

        <a href="<?= base_url(); ?>/orders/carrito" class="cart-link <?= ($data['page_name'] ?? '') === 'carrito'
            ? 'active'
            : ''; ?>">

          Carrito

          <span id="cartCount">
            0
          </span>
        </a>

        <!-- Acceso visible dentro del menú móvil -->
        <div class="mobile-auth-actions">

          <?php if ($portalAutenticado): ?>

            <?php if (!empty($nombrePortal)): ?>

              <div class="mobile-user-info">
                <i class="ri-user-line"></i>

                <span>
                  <?= htmlspecialchars(
                    $nombrePortal,
                    ENT_QUOTES,
                    'UTF-8'
                  ); ?>
                </span>
              </div>

            <?php endif; ?>

       

       



          <?php endif; ?>

        </div>
      </nav>

      <!-- Acciones de escritorio -->
      <div class="auth-actions">

        <?php if ($portalAutenticado): ?>

          <?php if (!empty($nombrePortal)): ?>

            <div class="header-user-info">
              <span class="header-user-icon">
                <i class="ri-user-3-line"></i>
              </span>

              <div class="header-user-text">
                <small>
                  Bienvenido
                </small>

                <strong>
                  <?= htmlspecialchars(
                    $nombrePortal,
                    ENT_QUOTES,
                    'UTF-8'
                  ); ?>
                </strong>
              </div>
            </div>

          <?php endif; ?>

            <a
        href="<?= base_url(); ?>/orders/logout"
        class="btn btn-light">

        <i class="ri-logout-box-r-line"></i>
        Cerrar sesión
    </a>

        <?php else: ?>

          <a href="<?= base_url(); ?>/orders/login" class="btn btn-light">

            <i class="ri-login-box-line"></i>
            Iniciar sesión
          </a>

        <?php endif; ?>

      </div>

      <!-- Menú responsivo -->
      <button type="button" class="menu-toggle" id="menuToggle" aria-label="Abrir menú" aria-expanded="false">

        <i class="ri-menu-line"></i>
      </button>

    </div>
  </header>