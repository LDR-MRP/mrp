<?php

$nombreCliente = htmlspecialchars(
    $data['nombre_cliente'] ?? 'Distribuidor',
    ENT_QUOTES,
    'UTF-8'
);

$codigoCliente = htmlspecialchars(
    $data['codigo_cliente'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$usuarioAcceso = htmlspecialchars(
    $data['usuario_acceso'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$passwordTemporal = htmlspecialchars(
    $data['password_temporal'] ?? '',
    ENT_QUOTES,
    'UTF-8'
);

$ligaAccesoRaw = trim($data['liga_acceso'] ?? '');

$ligaAcceso = htmlspecialchars(
    $ligaAccesoRaw,
    ENT_QUOTES,
    'UTF-8'
);

$fechaNotificacion = htmlspecialchars(
    $data['fecha_notificacion'] ?? date('d/m/Y H:i'),
    ENT_QUOTES,
    'UTF-8'
);

$logoUrl = htmlspecialchars(
    $data['logo_url']
        ?? 'https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png',
    ENT_QUOTES,
    'UTF-8'
);

$dobleAutenticacion = intval(
    $data['doble_autenticacion'] ?? 0
);

$anio = intval(
    $data['anio'] ?? date('Y')
);
?>

<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1">

    <meta
        name="x-apple-disable-message-reformatting">

    <title>
        Accesos al Portal de Pedidos
    </title>

    <style>
        html,
        body {
            width: 100% !important;
            height: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
        }

        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }

        table,
        td {
            border-collapse: collapse !important;
            mso-table-lspace: 0 !important;
            mso-table-rspace: 0 !important;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
        }

        a {
            text-decoration: none;
        }

        .credential-value {
            font-family: Consolas, Monaco, "Courier New", monospace;
        }

        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .px {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .stack td {
                display: block !important;
                width: 100% !important;
                border-right: 0 !important;
            }

            .button-table,
            .button-cell,
            .button-link {
                width: 100% !important;
            }

            .button-link {
                display: block !important;
                box-sizing: border-box !important;
            }

            .header-right {
                display: none !important;
            }
        }
    </style>
</head>

<body>

    <!-- Preheader -->
    <div
        style="
            display:none;
            max-height:0;
            max-width:0;
            overflow:hidden;
            opacity:0;
            color:transparent;
            font-size:1px;
            line-height:1px;
        ">
        Tus accesos al Portal de Pedidos de Distribuidores han sido generados.
    </div>

    <table
        role="presentation"
        width="100%"
        cellpadding="0"
        cellspacing="0"
        style="width:100%; background:#f4f6f8;">

        <tr>
            <td
                align="center"
                style="padding:28px 12px;">

                <table
                    role="presentation"
                    class="container"
                    width="600"
                    cellpadding="0"
                    cellspacing="0"
                    style="
                        width:600px;
                        max-width:600px;
                    ">

                    <tr>
                        <td
                            class="px"
                            style="padding:0 24px;">

                            <table
                                role="presentation"
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                style="
                                    width:100%;
                                    background:#ffffff;
                                    border-radius:14px;
                                    overflow:hidden;
                                    box-shadow:0 6px 18px rgba(17,24,39,.06);
                                ">

                                <!-- Barra superior -->
                                <tr>
                                    <td
                                        style="
                                            height:6px;
                                            line-height:6px;
                                            font-size:0;
                                            background:#e97e2e;
                                        ">
                                        &nbsp;
                                    </td>
                                </tr>

                                <!-- Encabezado -->
                                <tr>
                                    <td
                                        style="padding:18px 22px 10px;">

                                        <table
                                            role="presentation"
                                            width="100%"
                                            cellpadding="0"
                                            cellspacing="0">

                                            <tr>
                                                <td align="left">

                                                    <table
                                                        role="presentation"
                                                        cellpadding="0"
                                                        cellspacing="0">

                                                        <tr>
                                                            <td
                                                                style="
                                                                    padding-right:12px;
                                                                    vertical-align:middle;
                                                                ">

                                                                <img
                                                                    src="<?= $logoUrl ?>"
                                                                    alt="LDR Solutions"
                                                                    width="90"
                                                                    style="
                                                                        display:block;
                                                                        width:90px;
                                                                        max-width:90px;
                                                                        height:auto;
                                                                    ">
                                                            </td>

                                                            <td
                                                                style="vertical-align:middle;">

                                                                <div
                                                                    style="
                                                                        margin-top:4px;
                                                                        color:#111827;
                                                                        font-size:20px;
                                                                        font-weight:800;
                                                                    ">
                                                                    Accesos al Portal de Pedidos
                                                                </div>

                                                                <div
                                                                    style="
                                                                        margin-top:4px;
                                                                        color:#6b7280;
                                                                        font-size:12px;
                                                                    ">
                                                                    Portal de Distribuidores
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>

                                                <td
                                                    align="right"
                                                    class="header-right"
                                                    style="vertical-align:middle;">

                                                    <div
                                                        style="
                                                            color:#6b7280;
                                                            font-size:12px;
                                                        ">
                                                        Generado el
                                                    </div>

                                                    <div
                                                        style="
                                                            margin-top:4px;
                                                            color:#111827;
                                                            font-size:13px;
                                                            font-weight:700;
                                                        ">
                                                        <?= $fechaNotificacion ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Saludo -->
                                <tr>
                                    <td
                                        style="padding:8px 22px 10px;">

                                        <div
                                            style="
                                                color:#111827;
                                                font-size:14px;
                                                line-height:1.6;
                                            ">

                                            Hola,
                                            <strong><?= $nombreCliente ?></strong>:

                                            <br><br>

                                            Se ha generado tu acceso al
                                            <strong>Portal de Pedidos de Distribuidores</strong>.
                                            Desde este portal podrás registrar solicitudes,
                                            consultar el estado de tus pedidos y dar seguimiento
                                            a las unidades solicitadas.
                                        </div>
                                    </td>
                                </tr>

                                <?php if (!empty($codigoCliente)): ?>
                                    <!-- Código del cliente -->
                                    <tr>
                                        <td
                                            style="padding:8px 22px 4px;">

                                            <table
                                                role="presentation"
                                                width="100%"
                                                cellpadding="0"
                                                cellspacing="0"
                                                style="
                                                    width:100%;
                                                    border:1px solid #e5e7eb;
                                                    border-radius:12px;
                                                ">

                                                <tr>
                                                    <td
                                                        style="
                                                            padding:12px 14px;
                                                            background:#f9fafb;
                                                            color:#6b7280;
                                                            font-size:12px;
                                                            width:42%;
                                                        ">
                                                        Código del cliente
                                                    </td>

                                                    <td
                                                        style="
                                                            padding:12px 14px;
                                                            background:#ffffff;
                                                            color:#111827;
                                                            font-size:13px;
                                                            font-weight:800;
                                                        ">
                                                        <?= $codigoCliente ?>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                <?php endif; ?>

                                <!-- Credenciales -->
                                <tr>
                                    <td
                                        style="padding:16px 22px 6px;">

                                        <div
                                            style="
                                                margin-bottom:10px;
                                                color:#111827;
                                                font-size:14px;
                                                font-weight:800;
                                            ">
                                            Credenciales de acceso
                                        </div>

                                        <table
                                            role="presentation"
                                            width="100%"
                                            cellpadding="0"
                                            cellspacing="0"
                                            style="
                                                width:100%;
                                                border:1px solid #e5e7eb;
                                                border-radius:12px;
                                                overflow:hidden;
                                            ">

                                            <tr>
                                                <td
                                                    style="
                                                        width:38%;
                                                        padding:13px 14px;
                                                        border-bottom:1px solid #e5e7eb;
                                                        background:#f9fafb;
                                                        color:#6b7280;
                                                        font-size:12px;
                                                    ">
                                                    Usuario
                                                </td>

                                                <td
                                                    style="
                                                        padding:13px 14px;
                                                        border-bottom:1px solid #e5e7eb;
                                                        background:#ffffff;
                                                        color:#111827;
                                                        font-size:14px;
                                                        font-weight:800;
                                                    ">

                                                    <span class="credential-value">
                                                        <?= $usuarioAcceso ?>
                                                    </span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    style="
                                                        width:38%;
                                                        padding:13px 14px;
                                                        border-bottom:1px solid #e5e7eb;
                                                        background:#f9fafb;
                                                        color:#6b7280;
                                                        font-size:12px;
                                                    ">
                                                    Contraseña temporal
                                                </td>

                                                <td
                                                    style="
                                                        padding:13px 14px;
                                                        border-bottom:1px solid #e5e7eb;
                                                        background:#ffffff;
                                                        color:#111827;
                                                        font-size:15px;
                                                        font-weight:800;
                                                        letter-spacing:1px;
                                                    ">

                                                    <span class="credential-value">
                                                        <?= $passwordTemporal ?>
                                                    </span>
                                                </td>
                                            </tr>

                                            <tr>
                                                <td
                                                    style="
                                                        width:38%;
                                                        padding:13px 14px;
                                                        background:#f9fafb;
                                                        color:#6b7280;
                                                        font-size:12px;
                                                    ">
                                                    Doble autenticación
                                                </td>

                                                <td
                                                    style="
                                                        padding:13px 14px;
                                                        background:#ffffff;
                                                        color:#111827;
                                                        font-size:13px;
                                                        font-weight:800;
                                                    ">

                                                    <?php if ($dobleAutenticacion === 1): ?>
                                                        <span
                                                            style="
                                                                display:inline-block;
                                                                padding:5px 10px;
                                                                border-radius:20px;
                                                                background:#dcfce7;
                                                                color:#15803d;
                                                                font-size:12px;
                                                                font-weight:800;
                                                            ">
                                                            Activada
                                                        </span>
                                                    <?php else: ?>
                                                        <span
                                                            style="
                                                                display:inline-block;
                                                                padding:5px 10px;
                                                                border-radius:20px;
                                                                background:#f3f4f6;
                                                                color:#4b5563;
                                                                font-size:12px;
                                                                font-weight:800;
                                                            ">
                                                            Desactivada
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                           
                          <!-- Aviso de contraseña temporal -->
<tr>
    <td style="padding:14px 22px 6px;">
        <table
            role="presentation"
            width="100%"
            cellpadding="0"
            cellspacing="0"
            style="
                width:100%;
                border:1px solid #fde68a;
                border-radius:12px;
                background:#fffbeb;
            ">

            <tr>
                <td
                    style="
                        width:42px;
                        padding:14px 4px 14px 14px;
                        vertical-align:top;
                        color:#d97706;
                        font-size:22px;
                    ">
                    &#9888;
                </td>

                <td
                    style="
                        padding:14px 14px 14px 6px;
                        color:#92400e;
                        font-size:13px;
                        line-height:1.55;
                    ">

                    <strong>
                        Cambio inicial de contraseña
                    </strong>

                    <br>

                    La contraseña proporcionada es temporal. Durante tu primer
                    inicio de sesión, el sistema te solicitará crear una nueva
                    contraseña personal.

                    
                </td>
            </tr>
        </table>
    </td>
</tr>

                    <?php if ($dobleAutenticacion === 1): ?>
    <!-- Aviso de doble autenticación -->
    <tr>
        <td style="padding:10px 22px 6px;">
            <table
                role="presentation"
                width="100%"
                cellpadding="0"
                cellspacing="0"
                style="
                    width:100%;
                    border:1px solid #bfdbfe;
                    border-radius:12px;
                    background:#eff6ff;
                ">

                <tr>
                    <td
                        style="
                            width:42px;
                            padding:14px 4px 14px 14px;
                            vertical-align:top;
                            color:#2563eb;
                            font-size:22px;
                        ">
                        &#128737;
                    </td>

                    <td
                        style="
                            padding:14px 14px 14px 6px;
                            color:#1e40af;
                            font-size:13px;
                            line-height:1.55;
                        ">

                        <strong>
                            Doble autenticación activada
                        </strong>

                     

                       <br>

                        Deberás capturar ese PIN para completar el inicio de
                        sesión. El código tendrá una vigencia limitada y solo
                        podrá utilizarse una vez.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
<?php endif; ?>

                                <!-- Botón -->
                                <tr>
                                    <td
                                        align="center"
                                        style="padding:20px 22px 10px;">

                                        <div
                                            style="
                                                margin-bottom:13px;
                                                color:#6b7280;
                                                font-size:13px;
                                            ">
                                            Ingresa al portal desde el siguiente botón:
                                        </div>

                                        <table
                                            role="presentation"
                                            class="button-table"
                                            cellpadding="0"
                                            cellspacing="0"
                                            style="margin:0 auto;">

                                            <tr>
                                                <td
                                                    align="center"
                                                    bgcolor="#111827"
                                                    class="button-cell"
                                                    style="border-radius:12px;">

                                                    <a
                                                        href="<?= $ligaAcceso ?>"
                                                        target="_blank"
                                                        class="button-link"
                                                        style="
                                                            display:inline-block;
                                                            padding:13px 24px;
                                                            color:#ffffff;
                                                            font-size:14px;
                                                            font-weight:800;
                                                        ">
                                                        Ingresar al portal
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <div
                                            style="
                                                margin-top:14px;
                                                color:#9ca3af;
                                                font-size:12px;
                                                line-height:1.5;
                                            ">
                                            Si el botón no funciona, copia y pega este enlace
                                            en tu navegador:

                                            <br>

                                            <span
                                                style="
                                                    color:#6b7280;
                                                    word-break:break-all;
                                                ">
                                                <?= $ligaAcceso ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>


                                <!-- Footer -->
                                <tr>
                                    <td
                                        style="padding:14px 22px 18px;">

                                        <hr
                                            style="
                                                margin:6px 0 12px;
                                                border:0;
                                                border-top:1px solid #e5e7eb;
                                            ">

                                        <div
                                            style="
                                                color:#9ca3af;
                                                font-size:12px;
                                                line-height:1.6;
                                                text-align:center;
                                            ">

                                            © <?= $anio ?> LDR Solutions · Portal de Pedidos

                                            <br><br>

                                            <strong style="color:#6b7280;">
                                                Este correo fue generado automáticamente.
                                                Por favor, no respondas a este mensaje.
                                            </strong>
                                        </div>
                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>

            </td>
        </tr>
    </table>

</body>

</html>