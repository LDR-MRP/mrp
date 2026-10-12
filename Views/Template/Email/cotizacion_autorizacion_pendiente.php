<?php
$h = static fn($valor) => htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
$url = (string)($data['url_revision'] ?? '');
$logoUrl = (string)($data['logo_url'] ?? (rtrim(base_url(), '/') . '/Assets/images/logo-cotizaciones.png'));
$campos = [
    'Propuesta' => 'folio_propuesta',
    'Cotización' => 'folio_cotizacion',
    'Distribuidor' => 'distribuidor',
    'Versión' => 'version',
    'Fecha de solicitud' => 'fechaHora',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Solicitud de autorización comercial | LDR Solutions</title>
</head>
<body style="margin:0;padding:0;background-color:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#171717;">
    <div style="display:none;font-size:1px;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">
        Tiene una cotización comercial pendiente de revisión y autorización.
    </div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#f3f4f6;">
        <tr>
            <td align="center" style="padding:32px 12px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%;max-width:600px;background-color:#ffffff;border:1px solid #e5e7eb;">
                    <tr>
                        <td align="center" style="padding:30px 24px 22px;border-top:5px solid #171717;">
                            <img src="https://viaticos.ldrhumanresources.com/viaticos/Assets/images/logo-cotizaciones.png" alt="LDR Solutions" width="150" style="display:block;width:150px;max-width:100%;height:auto;border:0;outline:none;text-decoration:none;">
                            <div style="margin-top:20px;font-size:11px;letter-spacing:2px;font-weight:bold;color:#737373;text-transform:uppercase;">Portal de Propuestas Comerciales</div>
                            <h1 style="margin:12px 0 0;font-size:23px;line-height:1.35;font-weight:700;color:#171717;">Autorización comercial pendiente</h1>
                            <p style="margin:10px 0 0;font-size:13px;line-height:1.6;color:#737373;">Notificación de solicitud de revisión</p>
                        </td>
                    </tr>
                    <tr><td style="padding:0 30px;"><div style="border-top:1px solid #e5e7eb;"></div></td></tr>
                    <tr>
                        <td style="padding:26px 30px 12px;">
                            <p style="margin:0 0 14px;font-size:15px;line-height:1.75;color:#262626;">Estimado(a) responsable:</p>
                            <p style="margin:0;font-size:14px;line-height:1.8;color:#404040;">
                                Se ha registrado una cotización comercial que requiere la revisión y autorización de
                                <strong style="color:#171717;"><?= $h($data['direccion'] ?? 'la dirección correspondiente') ?></strong>.
                                A continuación se presenta el resumen de la solicitud.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 30px 20px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="border:1px solid #e5e7eb;font-size:13px;">
                                <tr><td colspan="2" style="padding:13px 16px;background-color:#171717;color:#ffffff;font-size:13px;font-weight:bold;">Detalle de la cotización</td></tr>
                                <?php foreach ($campos as $etiqueta => $campo): ?>
                                <tr>
                                    <td width="40%" style="padding:12px 14px;background-color:#f9fafb;color:#666666;border-bottom:1px solid #e5e7eb;vertical-align:top;"><?= $h($etiqueta) ?></td>
                                    <td width="60%" style="padding:12px 14px;color:#171717;border-bottom:1px solid #e5e7eb;vertical-align:top;"><strong><?= $h($data[$campo] ?? '—') ?></strong></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr>
                                    <td style="padding:13px 14px;background-color:#f9fafb;color:#666666;">Importe total</td>
                                    <td style="padding:13px 14px;color:#171717;font-size:15px;"><strong>$<?= number_format((float)($data['total'] ?? 0), 2) ?> MXN</strong></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:10px 30px 16px;">
                            <p style="margin:0 0 18px;font-size:13px;line-height:1.7;color:#525252;">Para consultar los detalles y registrar su decisión, acceda al portal mediante el siguiente botón:</p>
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr><td align="center" bgcolor="#171717" style="background-color:#171717;border-radius:6px;">
                                <a href="<?= $h($url) ?>" target="_blank" style="display:inline-block;padding:15px 30px;color:#ffffff;text-decoration:none;font-size:14px;font-weight:bold;">Revisar cotización</a>
                            </td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:12px 30px 25px;">
                            <div style="padding:17px 14px;background-color:#f9fafb;border:1px solid #e5e7eb;text-align:center;">
                                <p style="margin:0 0 9px;font-size:12px;line-height:1.6;color:#525252;"><strong>¿El botón no funciona?</strong><br>Copie y pegue el siguiente enlace en la barra de direcciones de su navegador:</p>
                                <div style="font-size:12px;line-height:1.7;overflow-wrap:anywhere;word-break:break-all;">
                                    <a href="<?= $h($url) ?>" target="_blank" style="color:#171717;text-decoration:underline;"><?= $h($url) ?></a>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 30px 26px;">
                            <p style="margin:0;font-size:13px;line-height:1.75;color:#525252;">Agradecemos revisar esta solicitud a la brevedad. La aprobación, devolución o rechazo deberá registrarse directamente en el sistema.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:20px 28px;background-color:#f5f5f5;border-top:1px solid #e5e7eb;">
                            <p style="margin:0 0 6px;font-size:12px;font-weight:bold;color:#262626;">LDR Solutions · Propuestas Comerciales</p>
                            <p style="margin:0;font-size:11px;line-height:1.6;color:#737373;">Este es un mensaje generado automáticamente. Por favor, no responda a este correo.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
