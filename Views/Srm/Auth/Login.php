<!DOCTYPE html>
<html lang="es-MX" data-layout="vertical" data-topbar="light" data-sidebar="dark" data-sidebar-size="lg" data-sidebar-image="none" data-preloader="disable">
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
<body class="bg-light">

    <!-- Wrapper Principal (Flexbox para empujar el footer al fondo) -->
    <div class="d-flex flex-column min-vh-100">
        
        <!-- Área Principal de Login -->
        <main class="flex-grow-1 d-flex align-items-center justify-content-center p-3 p-lg-5">
            <div class="container-fluid max-w-100" style="max-width: 1200px;">
                
                <!-- Tarjeta Premium Split-Screen -->
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden mb-0">
                    <div class="row g-0">
                        
                        <!-- Lado Izquierdo: Formulario (Soberbio y Limpio) -->
                        <div class="col-lg-5 col-xl-4 bg-white p-4 p-lg-5 d-flex flex-column justify-content-center position-relative">
                            
                            <!-- Cabecera del Formulario -->
                            <div class="mb-5 text-center text-lg-start">
                                <img src="<?= media(); ?>/images/ldr_logo_color.png" alt="LDR Solutions" height="40" class="mb-4">
                                <h3 class="text-dark fw-bold mb-2">Iniciar Sesión</h3>
                                <p class="text-muted fs-14 mb-0">
                                    ¿No tienes una cuenta? <a href="<?= base_url(); ?>/srm_registro" class="text-primary fw-semibold text-decoration-none">Regístrate como proveedor.</a>
                                </p>
                            </div>

                            <!-- CONTENEDOR LOGIN -->
                            <div id="login-container">
                                <form name="formSrmLogin" id="formSrmLogin" action="">
                                    
                                    <div class="mb-4">
                                        <label for="txtEmail" class="form-label fw-medium text-dark">Correo Electrónico</label>
                                        <input id="txtEmail" name="txtEmail" type="email" class="form-control form-control-lg bg-light border-0" placeholder="ejemplo@tuempresa.com" required>
                                    </div>

                                    <div class="mb-4">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-medium text-dark mb-0" for="txtPassword">Contraseña</label>
                                            <a href="javascript:void(0);" onclick="SrmLogin.toggleForms()" class="text-muted fs-13 text-decoration-none">¿Olvidaste tu clave?</a>
                                        </div>
                                        <div class="position-relative auth-pass-inputgroup">
                                            <input type="password" class="form-control form-control-lg bg-light border-0 pe-5 password-input" placeholder="Ingresa tu contraseña" id="txtPassword" required>
                                            <button class="btn btn-link position-absolute end-0 top-0 text-decoration-none text-muted password-addon h-100" type="button" id="password-addon"><i class="ri-eye-fill align-middle fs-18"></i></button>
                                        </div>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input class="form-check-input" type="checkbox" value="" id="recordar">
                                        <label class="form-check-label text-muted fs-13" for="recordar">Mantener sesión iniciada</label>
                                    </div>

                                    <div class="mt-4">
                                        <button class="btn btn-lg w-100 fw-bold shadow-none" type="submit" id="btnLogin" style="background-color: #e97e2e; color:#ffffff;">Ingresar al Portal</button>
                                    </div>
                                </form>
                            </div>
                            <!-- FIN CONTENEDOR LOGIN -->

                            <!-- CONTENEDOR RESET PASSWORD -->
                            <div id="reset-container" class="d-none">
                                <div class="alert alert-borderless alert-info text-center mb-4 fs-14" role="alert">
                                    Ingresa tu correo para recibir las instrucciones de recuperación.
                                </div>
                                <form id="formRecetPass" name="formRecetPass" action="">
                                    <div class="mb-4">
                                        <label for="txtEmailReset" class="form-label fw-medium text-dark">Correo electrónico</label>
                                        <input id="txtEmailReset" name="txtEmailReset" type="email" class="form-control form-control-lg bg-light border-0" placeholder="ejemplo@tuempresa.com" required>
                                    </div>

                                    <div class="mt-4 vstack gap-2">
                                        <button class="btn btn-primary btn-lg w-100 fw-bold shadow-none" type="submit">Enviar Instrucciones</button>
                                        <button type="button" class="btn btn-ghost-dark btn-lg w-100 fw-medium" onclick="SrmLogin.toggleForms()">Volver al inicio</button>
                                    </div>
                                </form>
                            </div>
                            <!-- FIN CONTENEDOR RESET PASSWORD -->

                        </div>

                        <!-- Lado Derecho: Imagen Corporativa y Tarjeta Flotante (Oculto en móviles) -->
                        <div class="col-lg-7 col-xl-8 d-none d-lg-block position-relative bg-primary" style="background-image: url('<?= media(); ?>/images/auth-one-bg.jpg'); background-size: cover; background-position: center;">
                            
                            <!-- Overlay oscuro para dar contraste -->
                            <div class="bg-overlay" style="opacity: 0.7; background-color: #e97e2e;"></div>
                            
                            <!-- Tarjeta flotante estilo WPForms (Premium) -->
                            <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center p-5">
                                <div class="card bg-white border-0 shadow-lg w-75 p-5 rounded-4" style="max-width: 500px;">
                                    <h3 class="text-dark fw-bold mb-3 fs-24">Gestión Inteligente de Suministros (SRM)</h3>
                                    <p class="text-muted fs-15 mb-4 lh-lg">
                                        Bienvenido a la plataforma de LDR Solutions. Optimice sus tiempos de respuesta, cargue sus facturas XML/PDF y consulte el estado de sus pagos en tiempo real.
                                    </p>
                                    <div>
                                        <a href="<?= base_url(); ?>/nosotros" class="btn btn-soft-secondary fw-bold px-4">Conocer más sobre LDR <i class="ri-arrow-right-line align-middle ms-1"></i></a>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
                
            </div>
        </main>

        <!-- Footer Corporativo (Traducido de Tailwind a Bootstrap 5 / Velzon) -->
        <footer class="bg-white border-top mt-auto">
            <div class="container-fluid py-5" style="max-width: 1400px;">
                <div class="row g-4 justify-content-between">
                    
                    <!-- Columna 1: Logo y Redes -->
                    <div class="col-lg-4 col-md-12 mb-4 mb-lg-0 vstack gap-3">
                        <div>
                            <a href="<?= base_url(); ?>">
                                <img src="<?= media(); ?>/images/ldr_negro.png" alt="Logo LDR Solutions" height="40">
                            </a>
                        </div>
                        <p class="text-muted fw-medium fs-13 pe-lg-4 mb-0">
                            Establecidos desde 2017 en Guadalajara y desde ese momento contamos con un entorno completo de soluciones de transporte para el mercado mexicano.
                        </p>
                        <div class="d-flex gap-3">
                            <a href="https://www.facebook.com/share/1Aff7dCGnA/?mibextid=wwXIfr" target="_blank" class="text-muted text-decoration-none fs-24 transition"><i class="ri-facebook-circle-fill"></i></a>
                            <a href="https://www.instagram.com/ldr_mx?igsh=bWtqb2k2ZGpqd3Fk" target="_blank" class="text-muted text-decoration-none fs-24 transition"><i class="ri-instagram-line"></i></a>
                            <a href="https://www.linkedin.com/company/ldrsolutions/" target="_blank" class="text-muted text-decoration-none fs-24 transition"><i class="ri-linkedin-box-fill"></i></a>
                        </div>
                    </div>

                    <!-- Columna 2: Libre -->
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-4 mb-md-0">
                        
                    </div>

                    <!-- Columna 3: Libre -->
                    <div class="col-lg-2 col-md-4 col-sm-6 mb-4 mb-md-0">

                    </div>

                    <!-- Columna 4: Privacidad -->
                    <div class="col-lg-2 col-md-4 col-sm-6">
                        <h5 class="text-dark fw-bold mb-3 fs-15">Avisos de Privacidad</h5>
                        <ul class="list-unstyled vstack gap-2 mb-0">
                            <li><a target="_blank" href="https://www.ldrsolutions.mx/privacidad/proveedores" class="text-muted text-decoration-none fs-14 text-capitalize">proveedores</a></li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Copyright Base -->
            <div class="border-top py-3">
                <div class="container-fluid text-center" style="max-width: 1400px;">
                    <p class="mb-0 text-muted fs-13 fst-italic">
                        Powered by <span class="text-decoration-underline">LDR Solutions</span> © <?= date('Y') ?>
                    </p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Variables Globales -->
    <script>
        const base_url = "<?= base_url(); ?>";
    </script>

    <!-- Scripts Base -->
    <script src="<?= media(); ?>/js/jquery-3.3.1.min.js"></script>
    <script src="<?= media(); ?>/minimal/libs/sweetalert2/sweetalert2.min.js"></script>
    <script src="<?= media(); ?>/js/sys_core.js?v=1.0.4"></script>
    
    <!-- Script Controlador del Módulo inyectado dinámicamente -->
    <script src="<?= media(); ?>/js/<?= $data['page_functions_js']; ?>"></script>

</body>
</html>