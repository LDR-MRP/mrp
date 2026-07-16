<?php

$nombre = htmlspecialchars(
    trim($data['nombre'] ?? 'Usuario'),
    ENT_QUOTES,
    'UTF-8'
);

$ligaRecuperacionRaw = trim(
    $data['liga_recuperacion'] ?? ''
);

$ligaRecuperacion = htmlspecialchars(
    $ligaRecuperacionRaw,
    ENT_QUOTES,
    'UTF-8'
);

$vigenciaMinutos = intval(
    $data['vigencia_minutos'] ?? 30
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
        Recuperación de acceso - Portal de Pedidos
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

        Recibimos una solicitud para restablecer tu contraseña del Portal de Pedidos.
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

                                <!-- Header -->
                                <tr>
                                    <td style="padding:18px 22px 10px;">

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

                                                            <td style="vertical-align:middle;">

                                                                <div
                                                                    style="
                                                                        color:#111827;
                                                                        font-size:20px;
                                                                        font-weight:800;
                                                                    ">

                                                                    Recuperación de acceso
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

                                                        Solicitud recibida
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

                                <!-- Mensaje -->
                                <tr>
                                    <td style="padding:8px 22px 12px;">

                                        <div
                                            style="
                                                color:#111827;
                                                font-size:14px;
                                                line-height:1.6;
                                            ">

                                            Hola,
                                            <strong><?= $nombre ?></strong>:

                                            <br><br>

                                            Recibimos una solicitud para restablecer la contraseña
                                            de tu cuenta en el
                                            <strong>Portal de Pedidos</strong>.

                                            <br><br>

                                            Para continuar, utiliza el siguiente botón. La liga es
                                            personal y solo podrá utilizarse durante el periodo de
                                            vigencia indicado.
                                        </div>
                                    </td>
                                </tr>

                                <!-- Información -->
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

                                                    &#128274;
                                                </td>

                                                <td
                                                    style="
                                                        padding:14px 14px 14px 6px;
                                                        color:#1e40af;
                                                        font-size:13px;
                                                        line-height:1.55;
                                                    ">

                                                    <strong>
                                                        Liga de recuperación temporal
                                                    </strong>

                                                    <br>

                                                    Esta liga tendrá una vigencia de
                                                    <strong>
                                                        <?= $vigenciaMinutos ?> minutos
                                                    </strong>
                                                    y dejará de funcionar después de utilizarse
                                                    correctamente.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Botón -->
                                <tr>
                                    <td
                                        align="center"
                                        style="padding:22px 22px 10px;">

                                        <div
                                            style="
                                                margin-bottom:13px;
                                                color:#6b7280;
                                                font-size:13px;
                                            ">

                                            Presiona el botón para crear una nueva contraseña:
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
                                                        href="<?= $ligaRecuperacion ?>"
                                                        target="_blank"
                                                        class="button-link"
                                                        style="
                                                            display:inline-block;
                                                            padding:13px 24px;
                                                            color:#ffffff;
                                                            font-size:14px;
                                                            font-weight:800;
                                                        ">

                                                        Restablecer contraseña
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

                                                <?= $ligaRecuperacion ?>
                                            </span>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Advertencia -->
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
                                                        ¿No solicitaste este cambio?
                                                    </strong>

                                                    <br>

                                                    Si no reconoces esta solicitud, puedes ignorar
                                                    este correo. Tu contraseña actual continuará
                                                    funcionando y no será modificada.

                                                    <br><br>

                                                    No compartas esta liga con otras personas.
                                                    El personal de LDR Solutions nunca te solicitará
                                                    tu contraseña ni esta liga.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                       

                                <!-- Requisitos -->
                                <tr>
                                    <td style="padding:10px 22px 8px;">

                                        <table
                                            role="presentation"
                                            width="100%"
                                            cellpadding="0"
                                            cellspacing="0"
                                            style="
                                                width:100%;
                                                border:1px solid #e5e7eb;
                                                border-radius:12px;
                                                background:#f9fafb;
                                            ">

                                            <tr>
                                                <td
                                                    style="
                                                        padding:14px;
                                                        color:#4b5563;
                                                        font-size:13px;
                                                        line-height:1.6;
                                                    ">

                                                    <strong style="color:#111827;">
                                                        Requisitos para tu nueva contraseña
                                                    </strong>

                                                    <br><br>

                                                    • Mínimo 10 caracteres.<br>
                                                    • Al menos una letra mayúscula.<br>
                                                    • Al menos una letra minúscula.<br>
                                                    • Al menos un número.<br>
                                                    • Al menos un símbolo especial.
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>

                                <!-- Footer -->
                                <tr>
                                    <td style="padding:14px 22px 18px;">

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
```
