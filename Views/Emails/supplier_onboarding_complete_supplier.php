<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <!-- 
        Variables inyectadas dinámicamente por NotificationService::renderTemplate():
        $razon_social, $rfc, $origen, $tipo, $created_at, $link_expediente, $destinatario
    -->
    <style>
        body { margin: 0; padding: 0; background-color: #ffffff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .container { width: 100%; max-width:800px; margin: 0 auto; background-color: #ffffff; }
        .header { padding: 20px 40px; }
        .header-logo { float: left; width: 150px; transform: rotate(0deg); }
        .header-info { float: right; text-align: right; color: #575756; font-size: 14px; }
        .main-card { 
            background-color: #E3E3E2; 
            margin: 0 20px; 
            padding: 40px; 
            border-radius: 15px 15px 15px 15px;
            position: relative;
            text-align: justify;
        }
        .title { 
            font-size: 48px; 
            font-weight: 900; 
            color: #575756; 
            line-height: 1; 
            margin-bottom: 30px;
            text-transform: uppercase;
        }
        .check-icon { float: right; width: 100px; opacity: 0.7; transform: rotate(0deg); }
        .body-text { color: #575756; font-size: 18px; line-height: 1.4; margin-bottom: 20px; }
        .highlight { color: #28a745; font-weight: bold; } /* Green highlight for success */
        
        .data-table { width: 100%; max-width: 660px; background: #ffffff; border-radius: 5px; padding: 20px 0px 20px 20px; margin: 20px 0; border-left: 6px solid #28a745; }
        .label { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #878a99; }
        .value { font-size: 16px; font-weight: bold; color: #405189; }
        
        .btn-action {
            display: inline-block;
            background-color: #28a745;
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
            <img src="<?= base_url(); ?>/Assets/images/emails/icon-megaphone.png" class="check-icon" alt="Éxito">
            <div class="title">¡FELICIDADES<br><?= $destinatario ?>!</div>
            
            <div class="body-text">
                Nos complace informarle que su expediente digital ha sido <span class="highlight">aprobado completamente</span> por el equipo de validación de <strong>LDR Solutions</strong>.
            </div>

            <div class="body-text">
                Su cuenta ya se encuentra activa en el sistema y puede comenzar a operar con nosotros.
            </div>

            <!-- SUPPLIER DETAILS -->
            <div class="data-table">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" style="padding-bottom: 10px;">
                            <span class="label">RFC / Tax ID</span><br>
                            <span class="value"><?= $rfc ?></span>
                        </td>
                        <td width="50%" style="padding-bottom: 10px;">
                            <span class="label">Origen</span><br>
                            <span class="value"><?= $origen ?> (<?= $tipo ?>)</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="label">Fecha de Registro</span><br>
                            <span class="value"><?= $created_at ?></span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="<?= $link_expediente ?>" class="btn-action">Ir a mi Portal de Proveedores</a>
            </div>
            
            <div class="body-text" style="margin-top: 30px;">
                Agradecemos su confianza en ser parte de nuestro proveedor de servicios.
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
