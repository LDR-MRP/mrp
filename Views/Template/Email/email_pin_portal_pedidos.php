<?php

$nombre = htmlspecialchars(
    trim($data['nombre'] ?? 'Usuario'),
    ENT_QUOTES,
    'UTF-8'
);

$pin = htmlspecialchars(
    trim($data['pin'] ?? ''),
    ENT_QUOTES,
    'UTF-8'
);

$vigenciaMinutos = intval(
    $data['vigencia_minutos'] ?? 3
);

$fechaNotificacion = htmlspecialchars(
    $data['fecha_notificacion'] ?? date('d/m/Y H:i'),
    ENT_QUOTES,
    'UTF-8'
);

$ligaAccesoRaw = trim(
    $data['liga_acceso']
    ?? 'https://mrp.ldrsolutions.com/orders/login'
);

$ligaAcceso = htmlspecialchars(
    $ligaAccesoRaw,
    ENT_QUOTES,
    'UTF-8'
);

$logoUrl = htmlspecialchars(
    $data['logo_url']
    ?? 'https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png',
    ENT_QUOTES,
    'UTF-8'
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
        Código de seguridad - Portal de Pedidos
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

        @media screen and (max-width: 600px) {
            .container {
                width: 100% !important;
            }

            .px {
                padding-left: 16px !important;
                padding-right: 16px !important;
            }

            .header-right {
                display: none !important;
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

            .pin-code {
                font-size: 30px !important;
                letter-spacing: 8px !important;
            }
        }
    </style>
</head>

<body>

    <!-- Preheader oculto -->
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

        Tu código de seguridad para ingresar al Portal de Pedidos es
        <?= $pin ?>. Tiene una vigencia de <?= $vigenciaMinutos ?> minutos.
    </div>

    <table
        role="presentation"
        width="100%"
        cellpadding="0"
        cellspacing="0"
        style="
            width:100%;
            background:#f4f6f8;
        ">

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
                                                                        color:#111827;
                                                                        font-size:20px;
                                                                        font-weight:800;
                                                                    ">

                                                                    Código de seguridad
                                                                </div>

                                                                <div
                                                                    style="
                                                                        margin-top:4px;
                                                                        color:#6b7280;
                                                                        font-size:12px;
                                                                    ">

                                                                    Portal de Pedidos de Distribuidores
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

                                                        Solicitud generada
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

                                <!-- Mensaje principal -->
                                <tr>
                                    <td
                                        style="padding:8px 22px 12px;">

                                        <div
                                            style="
                                                color:#111827;
                                                font-size:14px;
                                                line-height:1.6;
                                            ">

                                            Hola,
                                            <strong><?= $nombre ?></strong>:

                                            <br><br>

                                            Detectamos un intento de inicio de sesión
                                            en tu cuenta del
                                            <strong>Portal de Pedidos</strong>.

                                            Para completar el acceso, ingresa el siguiente
                                            código de seguridad:
                                        </div>
                                    </td>
                                </tr>

                                <!-- PIN -->
                                <tr>
                                    <td
                                        align="center"
                                        style="padding:12px 22px;">

                                        <table
                                            role="presentation"
                                            width="100%"
                                            cellpadding="0"
                                            cellspacing="0"
                                            style="
                                                width:100%;
                                                border:1px solid #fed7aa;
                                                border-radius:14px;
                                                background:#fff7ed;
                                            ">

                                            <tr>
                                                <td
                                                    align="center"
                                                    style="padding:22px 16px;">

                                                    <div
                                                        style="
                                                            color:#9a3412;
                                                            font-size:12px;
                                                            font-weight:700;
                                                            text-transform:uppercase;
                                                            letter-spacing:.08em;
                                                        ">

                                                        Tu PIN de acceso
                                                    </div>

                                                    <div
                                                        class="pin-code"
                                                        style="
                                                            margin-top:12px;
                                                            color:#111827;
                                                            font-family:Consolas, Monaco, 'Courier New', monospace;
                                                            font-size:38px;
                                                            font-weight:900;
                                                            letter-spacing:12px;
                                                            line-height:1.2;
                                                        ">

                                                        <?= $pin ?>
                                                    </div>

                                                    <div
                                                        style="
                                                            margin-top:12px;
                                                            color:#9a3412;
                                                            font-size:13px;
                                                            line-height:1.5;
                                                        ">

                                                        Este código tiene una vigencia de
                                                        <strong>
                                                            <?= $vigenciaMinutos ?> minutos
                                                        </strong>.
                                                    </div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Información de vigencia -->
                                <tr>
                                    <td
                                        style="padding:8px 22px 6px;">

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
                                                        Verificación en dos pasos
                                                    </strong>

                                                    <br>

                                                    Por seguridad, cada vez que inicies una nueva
                                                    sesión recibirás un PIN diferente en tu correo.

                                                    <br><br>

                                                    El código solo puede utilizarse una vez y dejará
                                                    de funcionar después de
                                                    <?= $vigenciaMinutos ?> minutos.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Advertencia -->
                                <tr>
                                    <td
                                        style="padding:10px 22px 6px;">

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
                                                        No compartas este código
                                                    </strong>

                                                    <br>

                                                    El personal de LDR Solutions nunca te solicitará
                                                    este PIN por teléfono, mensaje o correo.

                                                    Si tú no intentaste iniciar sesión, ignora este
                                                    mensaje y considera cambiar tu contraseña.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

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

                                            Regresa al portal para capturar el PIN:
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

                                                        Volver al Portal de Pedidos
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