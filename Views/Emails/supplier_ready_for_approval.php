<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 0; background-color: #ffffff; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        .container { width: 100%; max-width:800px; margin: 0 auto; background-color: #ffffff; }
        .header { padding: 20px 40px; }
        .header-logo { float: left; width: 150px; }
        .header-info { float: right; text-align: right; color: #575756; font-size: 14px; }
        .main-card { 
            background-color: #E3E3E2; 
            margin: 0 20px; 
            padding: 40px; 
            border-radius: 15px 15px 15px 15px; /* Asymmetric rounding like the image */
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
        .megaphone-icon { float: right; width: 100px; opacity: 0.7; }
        .body-text { color: #575756; font-size: 18px; line-height: 1.4; margin-bottom: 20px; }
        .highlight { color: #e87b24; font-weight: bold; } /* Orange highlight from the brand */
        
        /* Specific Supplier Data Table */
        .data-table { width: 100%; max-width: 660px; background: #ffffff; border-radius: 5px; padding: 20px 0px 20px 20px; margin: 20px 0; border-left: 6px solid #405189; }
        .label { font-size: 10px; text-transform: uppercase; font-weight: bold; color: #878a99; }
        .value { font-size: 16px; font-weight: bold; color: #405189; }
        
        .btn-action {
            display: inline-block;
            background-color: #405189;
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
                <strong style="font-size: 18px;">Compras / Finanzas</strong><br>
                <?= date('d / m / Y') ?>
            </div>
            <div class="clearfix"></div>
        </div>

        <!-- MAIN CONTENT CARD -->
        <div class="main-card">
            <img src="<?= base_url(); ?>/Assets/images/emails/icon-megaphone.png" class="megaphone-icon" alt="Aviso">
            <div class="title">ESTIMADO<br>COLABORADOR:</div>
            
            <div class="body-text">
                Les informamos que el proveedor <span class="highlight">{{razon_social}}</span> ha completado satisfactoriamente la carga de su <strong>expediente digital</strong>.
            </div>

            <div class="body-text">
                Es fundamental proceder con la validación técnica y legal para asegurar que la identidad visual y fiscal del socio se centra en los estándares de <strong>LDR Solutions</strong>.
            </div>

            <!-- SUPPLIER DETAILS -->
            <div class="data-table">
                <table width="100%" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="50%" style="padding-bottom: 10px;">
                            <span class="label">RFC / Tax ID</span><br>
                            <span class="value">{{rfc}}</span>
                        </td>
                        <td width="50%" style="padding-bottom: 10px;">
                            <span class="label">Origen</span><br>
                            <span class="value">{{origen}} ({{tipo}})</span>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <span class="label">Fecha de Registro</span><br>
                            <span class="value">{{created_at}}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style="text-align: center;">
                <a href="{{link_expediente}}" class="btn-action">Iniciar Validación de Expediente</a>
            </div>
            
            <div class="body-text" style="margin-top: 30px;">
                Agradecemos su disposición para ser parte de este proyecto de modernización operativa.
            </div>

            <div class="body-text">Saludos.</div>
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