<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>Recuperar acceso a tu cuenta</title>

  <style>
    html, body {
      margin:0 !important;
      padding:0 !important;
      height:100% !important;
      width:100% !important;
      background:#f4f6f8;
      font-family: Arial, Helvetica, sans-serif;
    }
    * { -ms-text-size-adjust:100%; -webkit-text-size-adjust:100%; }
    table, td { mso-table-lspace:0pt !important; mso-table-rspace:0pt !important; border-collapse:collapse !important; }
    img { border:0; outline:none; text-decoration:none; }
    a { text-decoration:none; }

    @media screen and (max-width: 600px) {
      .container { width: 100% !important; }
      .px { padding-left: 16px !important; padding-right: 16px !important; }
      .btn a { display:block !important; width:100% !important; }
    }
  </style>
</head>

<body>

  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Recupera el acceso a tu cuenta de manera segura.
  </div>

  <table role="presentation" width="100%" style="background:#f4f6f8;">
    <tr>
      <td align="center" style="padding: 28px 12px;">

        <!-- CONTENEDOR -->
        <table role="presentation" class="container" width="600" style="width:600px; max-width:600px;">

          <!-- CARD PRINCIPAL -->
          <tr>
            <td class="px" style="padding: 0 24px;">

              <table role="presentation" width="100%" style="background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 18px rgba(17,24,39,.06);">

                <!-- BARRA SUPERIOR -->
                <tr>
                  <td style="background:#111827; height:6px; line-height:6px; font-size:0;">
                    &nbsp;
                  </td>
                </tr>

                <!-- HEADER -->
                <tr>
                  <td style="padding: 18px 22px 10px;">
                    <table role="presentation" width="100%">
                      <tr>
                        <td align="left">
                          <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>
                              <td style="padding-right:10px; vertical-align:middle;">
                                <img src="https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png"
                                     alt="Logotipo LDR Solutions"
                                     width="90"
                                     style="display:block; border:0; outline:none; text-decoration:none;">
                              </td>
                              <td style="vertical-align:middle;">
                                <div style="font-size:13px; color:#6b7280;">
                                  LDR Solutions
                                </div>
                                <div style="font-size:20px; color:#111827; font-weight:800; margin-top:6px;">
                                  Recuperar acceso a tu cuenta
                                </div>
                              </td>
                            </tr>
                          </table>
                        </td>

                        <td align="right">
                          <div style="font-size:12px; color:#6b7280;">
                            Seguridad
                          </div>
                          <div style="font-size:13px; color:#111827; font-weight:700;">
                            <?= $data['fecha_notificacion'] ?? '' ?>
                          </div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- MENSAJE -->
                <tr>
                  <td style="padding: 6px 22px 8px;">
                    <div style="font-size:14px; color:#111827; line-height:1.55;">
                      Hola, <strong><?= $data['nombreUsuario'] ?? '' ?></strong>,
                      <br><br>
                      Recibimos una solicitud para recuperar el acceso a tu cuenta asociada al correo:
                      <strong style="color:#e97e2e;"><?= $data['email'] ?? '' ?></strong>.
                      <br><br>
                      Para continuar, confirma tus datos de acceso dando clic en el botón:
                    </div>
                  </td>
                </tr>

                <!-- BLOQUE INFO (TIPO RESUMEN) -->
                <tr>
                  <td style="padding: 8px 22px 10px;">
                    <table role="presentation" width="100%" style="border:1px solid #e5e7eb; border-radius:12px;">
                      <tr>
                        <td style="padding: 12px 14px;">
                          <div style="font-size:12px; color:#6b7280;">Acción</div>
                          <div style="font-size:14px; color:#111827; font-weight:800; margin-top:4px;">
                            Confirmar datos de acceso
                          </div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- BOTÓN -->
                <tr>
                  <td align="center" style="padding: 16px 22px 10px;">

                    <table role="presentation" class="btn" style="margin:0 auto;">
                      <tr>
                        <td align="center" bgcolor="#111827" style="border-radius:12px;">
                          <a href="<?= $data['url_recovery'] ?? '#' ?>"
                             target="_blank"
                             style="font-size:14px; font-weight:800; color:#ffffff; padding:12px 18px; display:inline-block;">
                            Confirmar acceso
                          </a>
                        </td>
                      </tr>
                    </table>

                    <div style="font-size:12px; color:#9ca3af; margin-top:12px; line-height:1.5;">
                      Si el botón no funciona, copia y pega este enlace en tu navegador:
                      <br>
                      <!-- <span style="word-break:break-all; color:#111827;">
                        <?= $data['url_recovery'] ?? '' ?>
                      </span> -->
                    </div>

                    <!-- Caja tipo "url-box" (más segura visualmente) -->
                    <div style="margin-top:10px; text-align:left;">
                      <div style="font-size:12px; color:#6b7280; margin-bottom:6px;">Enlace directo</div>
                      <div style="word-break:break-all; font-size:12px; color:#111827; background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:10px 12px;">
                        <?= $data['url_recovery'] ?? '' ?>
                      </div>
                    </div>

                  </td>
                </tr>

                <!-- AVISO -->
                <tr>
                  <td style="padding: 0 22px 8px;">
                    <div style="font-size:13px; color:#6b7280; line-height:1.55;">
                      Si tú no solicitaste esta recuperación, puedes ignorar este correo. Tu cuenta permanecerá sin cambios.
                    </div>
                  </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                  <td style="padding: 14px 22px 18px;">
                    <hr style="border:none; border-top:1px solid #e5e7eb; margin: 10px 0 12px;">

                    <div style="text-align:center; margin-bottom:10px;">
                      <a href="<?= BASE_URL ?? '#' ?>" target="_blank" style="display:inline-block; padding:8px 12px; border:1px solid #e5e7eb; border-radius:12px; font-size:13px; color:#111827; font-weight:800;">
                        <?= WEB_EMPRESA ?? 'Ir a la plataforma' ?>
                      </a>
                    </div>

                    <div style="font-size:12px; color:#9ca3af; line-height:1.5; text-align:center;">
                      © <?= date('Y'); ?> LDR Solutions · Uso interno
                      <br><br>
                      <strong style="color:#6b7280;">Este correo es automático, por favor no respondas a este email.</strong>
                    </div>
                  </td>
                </tr>

              </table>

            </td>
          </tr>

        </table>
        <!-- /CONTENEDOR -->

      </td>
    </tr>
  </table>

</body>
</html>
