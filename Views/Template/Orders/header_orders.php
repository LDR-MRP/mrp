<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
      <link rel="shortcut icon" href="<?= media();?>/images/ldr_logo_color.png">
  <title>AutoDistribuidores | Solicitud de Unidades</title>
  <link rel="stylesheet" href="<?= media();?>/Orders/styles.css" />
  <link rel="preconnect" href="https://images.unsplash.com">
</head>
<body>
  <header class="top-header">
    <div class="container header-content">
        <a href="<?= base_url(); ?>/orders/home" class="logo">
            <img
                src="<?= media(); ?>/images/ldr-logo-dark.png"
                alt="LDR"
                class="logo-img">
        </a>

      <nav class="nav-menu" id="navMenu">
        <a href="#catalogo">Catálogo</a>
        <!-- <a href="#modelos">Modelos</a> -->
        <a href="#beneficios">Beneficios</a>
        <a href="carrito.html" class="cart-link">Carrito <span id="cartCount">0</span></a>
      </nav>


      <div class="auth-actions">
        <a href="#" class="btn btn-light">Iniciar sesión</a>
      </div>

      <button class="menu-toggle" id="menuToggle">☰</button>
    </div>
  </header>