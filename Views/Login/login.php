<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login EMR</title>

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="<?= media();?>/css/main.css">


  <style>
    body {
      margin: 0;
      font-family: Arial, Helvetica, sans-serif;
      background: linear-gradient(135deg, #ffffff, #ffe3cc);
    }

    /* Contenedor general centrado */
.page-wrapper {
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: flex-start;   
  padding-top: 30px;        
  padding-bottom: 40px;
}

    .card-wrapper {
      width: 100%;
      max-width: 400px;
      perspective: 1000px;
    }

    .flip-card {
      width: 100%;
      transition: transform 0.6s;
      transform-style: preserve-3d;
      position: relative;
    }

    .flip-card.flipped {
      transform: rotateY(180deg);
    }

    .form-side {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      backface-visibility: hidden;
      background: #fff;
      padding: 28px 26px 24px;
      border-radius: 14px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.12);
    }

    .back {
      transform: rotateY(180deg);
    }

    .btn-primary-custom {
      background-color: #e97e2e;
      border-color: #e97e2e;
    }

    .btn-primary-custom:hover {
      background-color: #c46623;
      border-color: #c46623;
    }

    .link-custom {
      color: #e97e2e;
      cursor: pointer;
      font-size: 0.9rem;
    }

    .link-custom:hover {
      text-decoration: underline;
    }

    .logo-emr {
      max-width: 140px;
    }

    .emr-text {
      letter-spacing: 2px;
      font-weight: bold;
      color: #e97e2e;
      font-size: 1.1rem;
    }

    .subtitle-text {
      font-size: 0.85rem;
      color: #777;
    }

    /* Estilo inputs al enfocar */
    .form-control:focus {
      border-color: #e97e2e;
      box-shadow: 0 0 0 0.2rem rgba(233, 126, 46, 0.25);
    }

    .small-footer {
      font-size: 0.75rem;
      color: #999;
      margin-top: 10px;
      text-align: center;
    }

    @media (max-width: 576px) {
      .form-side {
        padding: 22px 18px 18px;
      }
    }

    /* #divLoading{
	position: fixed;
	top: 0;
	width: 100%;
	height: 100%;
	display: flex;
	justify-content: center;
	align-items: center;
	background: rgba(254,254,255, .65);
	z-index: 9999;
	display: none;
}
#divLoading img{
	width: 50px;
	height: 50px;
} */

#divLoading {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(255, 255, 255, 0.85); /* Fondo opcional */
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 9999;
  display: none;
}

#divLoading img {
  width: 70px;  /* Ajusta tamaño */
}

  </style>
    <title><?= $data['page_tag']; ?></title>
</head>
<body>

  <div class="page-wrapper">
    <div class="card-wrapper">
              <div id="divLoading" >
          <div>
            <img src="<?= media(); ?>/images/loading.svg" alt="Loading">
          </div>
        </div>

      
      <div class="text-center mb-3">
        <img src="<?=  media().'/images/Logotipo LDR_solutions.png' ?>" alt="LDR Solutions" class="img-fluid logo-emr mb-2">
        <div class="emr-text">MRP</div>
        <div class="subtitle-text">Módulo de gestión interna</div>
      </div>

      <div class="flip-card" id="flipCard">

        
        <div class="form-side front">
       <form  name="formLogin" id="formLogin" action="">
          <h4 class="text-center mb-2">Iniciar sesión</h4>
          <p class="text-muted text-center mb-4" style="font-size:0.9rem;">
            Accede con tu usuario corporativo.
          </p>

          <div class="mb-3">
            <label for="txtEmail" class="form-label">Usuario</label>
            <input id="txtEmail" name="txtEmail" type="text" class="form-control" placeholder="Ingresa tu usuario">
          </div>

          <div class="mb-3">
            <label for="txtPassword" class="form-label">Contraseña</label>
            <input id="txtPassword" name="txtPassword" class="form-control" placeholder="Ingresa tu contraseña">
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="recordar">
              <label class="form-check-label" for="recordar" style="font-size:0.85rem;">
                Recordarme
              </label>
            </div>
            <span class="link-custom" onclick="flipCard()">¿Olvidaste tu contraseña?</span>
          </div>

          <button class="btn btn-primary-custom w-100 mb-1" type="submit">Iniciar sesión</button>

          <div class="small-footer">
             © <?= date('Y') ?> LDR Solutions · MRP
          </div>
  </form>
        </div>
      

        <!-- BACK LDR -->
        <div class="form-side back">
          <h4 class="text-center mb-2">Recuperar contraseña</h4>
          <p class="text-muted small mb-4 text-center">
            Ingresa tu correo para enviarte las instrucciones de recuperación.
          </p>
          <form id="formRecetPass" name="formRecetPass" class="forget-form" action="">
          <div class="mb-3">
            <label for="txtEmailReset" class="form-label">Correo electrónico</label>
            <input id="txtEmailReset" name="txtEmailReset" type="email" class="form-control" placeholder="tucorreo@empresa.com">
          </div>

          <button class="btn btn-primary-custom w-100 mb-3" type="submit">
            Solicitar
          </button>

            </form>

          <button class="btn btn-outline-secondary w-100" onclick="flipCard()">
            Volver a iniciar sesión
          </button> 
        
          <div class="small-footer">
            Si tienes problemas, contacta a Soporte TI.
          </div>
        </div>

      </div>
    </div>
  </div>

      <script>
        const base_url = "<?= base_url(); ?>";
    </script>

  <!-- Bootstrap JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

   <script src="<?= media(); ?>/js/jquery-3.3.1.min.js"></script>
  <script type="text/javascript" src="<?= media();?>/js/plugins/sweetalert.min.js"></script>
  <script src="<?= media(); ?>/js/modulos/<?= $data['page_functions_js']; ?>"></script>

  <script>
    function flipCard() {
      document.getElementById('flipCard').classList.toggle('flipped');
    }
  </script>

</body>
</html>
