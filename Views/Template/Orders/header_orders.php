<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="shortcut icon" href="<?= media(); ?>/images/ldr_logo_color.png">
  <title><?= $data['page_tag'] ?? 'Portal de Pedidos - LDR Solutions'; ?></title>
  <link rel="stylesheet" href="<?= media(); ?>/Orders/styles.css" />
</head>

<body class="page-<?= $data['page_name'] ?? 'home'; ?>">

<header class="top-header">
  <div class="container header-content">

    <a href="<?= base_url(); ?>/orders/home" class="logo">
      <img src="<?= media(); ?>/images/ldr-logo-dark.png" alt="LDR" class="logo-img">
    </a>

    <nav class="nav-menu" id="navMenu">
      <a href="<?= base_url(); ?>/orders/home#catalogo">Catálogo</a>
      <a href="<?= base_url(); ?>/orders/home#beneficios">Beneficios</a>
      <a href="<?= base_url(); ?>/orders/micuenta">Mi cuenta</a>
      <a href="<?= base_url(); ?>/orders/carrito" class="cart-link">
        Carrito <span id="cartCount">0</span>
      </a>
    </nav>

    <div class="auth-actions">
      <a href="<?= base_url(); ?>/orders/login" class="btn btn-light">Iniciar sesión</a>
    </div>

    <button class="menu-toggle" id="menuToggle">☰</button>

  </div>
</header>