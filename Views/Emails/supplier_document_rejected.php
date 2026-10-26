<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- 
        Variables inyectadas dinámicamente por NotificationService::renderTemplate():
        $razon_social, $rfc, $motivo_rechazo, $link_expediente, $destinatario
    -->
    <style>
        body { margin: 0; padding: 0; background-color: #ffffff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .container { width: 100%; max-width:800px; margin: 0 auto; background-color: #ffffff; }
        .header { padding: 20px 40px; }
        .header-logo { float: left; width: 150px; transform: rotate(0deg); }
        .header-info { float: right; text-align: right; color: #575756; font-size: 14px; }
        .main-card { 
            background-color: #fff3cd; 
            margin: 0 20px; 
            padding: 40px; 
            border-radius: 15px 15px 15px 15px;
            position: relative;
            text-align: justify;
        }
        .title { 
            font-size: 48px; 
            font-weight: 900; 
            color: #dc3545; 
            line-height: 1; 
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .warning-icon { float: right; width: 100px; opacity: 0.7; transform: rotate(0deg); }
        .body-text { color: #575756; font-size: 18px; line-height: 1.4; margin-bottom: 20px; }
        .highlight { color: #dc3545; font-weight: bold; }
        
        .motivo-box { 
            background: #ffffff; 
            border-left: 4px solid #dc3545; 
            padding: 20px; 
            margin: 20px 0; 
            border-radius: 5px;
        }
        .motivo-label { font-size: 12px; text-transform: uppercase; font-weight: bold; color: #dc3545; margin-bottom: 10px; }
        .motivo-text { font-size: 16px; color: #575756; }
        
        .btn-action {
            display: inline-block;
            background-color: #dc3545;
            color: #ffffff !important;
            padding: 15px 35px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 20px;
        }
        .footer-bar { 
            text-align: center; 
            margin-top: 40px;
        }
        .partner-logos { width: 100%; max-width: 760px; }
        .clearfix { clear: both; }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="header">
            <div class="header-logo">
                <img src="<?= base_url(); ?>/Assets/images/emails/logo-ldr-dark.png" width="120" alt="LDR Solutions">
            </div>
            <div class="header-info">
                <strong style="font-size: 18px;">Sistema de Proveedores</strong><br>
                <?= date('d / m / Y') ?>
            </div>
            <div class="clearfix"></div>
        </div>

        <!-- MAIN CONTENT CARD -->
        <div class="main-card">
            <img src="<?= base_url(); ?>/Assets/images/emails/icon-megaphone.png" class="warning-icon" alt="Advertencia">
            <div class="title">ATENCIÓN<br><?= $destinatario ?>!</div>
            
            <div class="body-text">
                Le informamos que un documento de su expediente digital ha sido <span class="highlight">rechazado</span> por el equipo de validación de <strong>LDR Solutions</strong>.
            </div>

            <div class="body-text">
                Por favor, revise el documento, realice las correcciones necesarias y cárguelo nuevamente para continuar con el proceso de validación.
            </div>

            <!-- MOTIVO DE RECHAZO -->
            <div class="motivo-box">
                <div class="motivo-label">MOTIVO DEL RECHAZO:</div>
                <div class="motivo-text"><?= $motivo_rechazo ?></div>
            </div>

            <div style="text-align: center;">
                <a href="<?= $link_expediente ?>" class="btn-action">Ir a mi Expediente Digital</a>
            </div>
            
            <div class="body-text" style="margin-top: 30px;">
                Estamos a su disposición para cualquier duda o aclaración.
            </div>

            <div class="body-text">Atentamente.</div>
        </div>
    </div>
    <!-- FOOTER PARTNERS BAR -->
    <div class="footer-bar">
        <img src="<?= base_url(); ?>/Assets/images/emails/footer-partners-bar.png" class="partner-logos" alt="LDR Partners">
    </div>
    <div width="100%" style="width: 100%; text-align: center; padding: 20px; font-size: 10px; color: #999;">
        LDR Solutions · MRP System v1.0.2 · Notificación Automática
    </div>
</body>
</html>
