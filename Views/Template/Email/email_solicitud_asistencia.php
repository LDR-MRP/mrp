<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">

  <title>
    <?= $data['tipo_accion'] ?? 'Notificación de Producción' ?>
  </title>

  <style>
    html, body {
      margin:0 !important;
      padding:0 !important;
      height:100% !important;
      width:100% !important;
      background:#f4f6f8;
      font-family: Arial, Helvetica, sans-serif;
    }

    * {
      -ms-text-size-adjust:100%;
      -webkit-text-size-adjust:100%;
    }

    table, td {
      mso-table-lspace:0pt !important;
      mso-table-rspace:0pt !important;
      border-collapse:collapse !important;
    }

    img {
      border:0;
      outline:none;
      text-decoration:none;
    }

    a {
      text-decoration:none;
    }

    @media screen and (max-width: 600px) {

      .container {
        width:100% !important;
      }

      .px {
        padding-left:16px !important;
        padding-right:16px !important;
      }

      .btn a {
        display:block !important;
        width:100% !important;
      }

      .stack td {
        display:block !important;
        width:100% !important;
        border-right:0 !important;
        border-bottom:1px solid #e5e7eb !important;
      }

      .stack td:last-child {
        border-bottom:0 !important;
      }
    }
  </style>
</head>

<body>

  <!-- PREHEADER -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    <?= $data['tipo_accion'] ?? 'Notificación de Producción' ?>
    en la unidad <?= $data['unidad'] ?? '' ?>
  </div>

  <table role="presentation" width="100%" style="background:#f4f6f8;">
    <tr>
      <td align="center" style="padding:28px 12px;">

        <!-- CONTENEDOR -->
        <table role="presentation"
               class="container"
               width="600"
               style="width:600px; max-width:600px;">

          <tr>
            <td class="px" style="padding:0 24px;">

              <!-- CARD -->
              <table role="presentation"
                     width="100%"
                     style="background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 18px rgba(17,24,39,.06);">

                <!-- TOP BAR -->
                <tr>
                  <td style="background:#111827; height:6px; line-height:6px; font-size:0;">
                    &nbsp;
                  </td>
                </tr>

                <!-- HEADER -->
                <tr>
                  <td style="padding:18px 22px 10px;">

                    <table role="presentation" width="100%">
                      <tr>

                        <td align="left">

                          <table role="presentation" cellpadding="0" cellspacing="0">
                            <tr>

                              <td style="padding-right:10px; vertical-align:middle;">

                                <img src="https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png"
                                     alt="Logotipo"
                                     width="90"
                                     style="display:block; border:0; outline:none; text-decoration:none;">

                              </td>

                              <td style="vertical-align:middle;">

                                <div style="font-size:13px; color:#6b7280;">
                                  LDR Solutions
                                </div>

                                <div style="font-size:20px; color:#111827; font-weight:800; margin-top:6px;">
                                  <?= $data['tipo_accion'] ?? 'Notificación de Producción' ?>
                                </div>

                              </td>

                            </tr>
                          </table>

                        </td>

                        <td align="right">

                          <div style="font-size:12px; color:#6b7280;">
                            Fecha
                          </div>

                          <div style="font-size:13px; color:#111827; font-weight:700;">
                            <?= $data['fecha'] ?? '' ?>
                          </div>

                        </td>

                      </tr>
                    </table>

                  </td>
                </tr>

                <!-- MENSAJE -->
                <tr>
                  <td style="padding:6px 22px 8px;">

                    <div style="font-size:14px; color:#111827; line-height:1.55;">

                      Hola,
                      <strong><?= $data['nombreSupervisor'] ?? 'Supervisor' ?></strong>

                      <br>

                    </div>

                  </td>
                </tr>

                <!-- RESUMEN -->
                <tr>
                  <td style="padding:8px 22px 10px;">

                    <table role="presentation"
                           width="100%"
                           style="border:1px solid #e5e7eb; border-radius:12px;">

                      <tr class="stack">

                        <td style="padding:12px 14px; border-right:1px solid #e5e7eb;">

                          <div style="font-size:12px; color:#6b7280;">
                            Tipo de solicitud
                          </div>

                          <div style="font-size:14px; color:#111827; font-weight:800; margin-top:4px;">
                            <?= $data['tipo_accion'] ?? '—' ?>
                          </div>

                        </td>

                        <td style="padding:12px 14px;">

                          <div style="font-size:12px; color:#6b7280;">
                            Unidad
                          </div>

                          <div style="font-size:14px; color:#111827; font-weight:800; margin-top:4px;">
                            <?= $data['unidad'] ?? '—' ?>
                          </div>

                        </td>

                      </tr>

                    </table>

                  </td>
                </tr>

                <!-- DETALLE -->
                <tr>
                  <td style="padding:10px 22px 6px;">

                    <div style="font-size:14px; color:#111827; font-weight:800; margin-bottom:10px;">
                      Información de la estación
                    </div>

                    <table role="presentation"
                           width="100%"
                           style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">

                      <tr>

                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; width:42%;">
                          Estación
                        </td>

                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; font-weight:800;">
                          <?= $data['estacion'] ?? '—' ?>
                        </td>

                      </tr>

                      <tr>

                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">
                          Proceso
                        </td>

                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">
                          <?= $data['proceso'] ?? '—' ?>
                        </td>

                      </tr>

                      <tr>

                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">
                          Estándar
                        </td>

                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">
                          <?= $data['estandar'] ?? '—' ?>
                        </td>

                      </tr>

                      <tr>

                        <td style="background:#f9fafb; padding:12px 14px; font-size:12px; color:#6b7280;">
                          Descripción
                        </td>

                        <td style="background:#ffffff; padding:12px 14px; font-size:13px; color:#111827;">
                          <?= !empty($data['descripcion']) 
    ? nl2br($data['descripcion']) 
    : 'Sin descripción' ?>
                        </td>

                      </tr>

                    </table>

                  </td>
                </tr>

                <!-- ALERTA -->
                <tr>
                  <td style="padding:10px 22px 6px;">

                    <table role="presentation"
                           width="100%"
                           style="background:#fff7ed; border:1px solid #fdba74; border-radius:12px;">

                      <tr>

                        <td style="padding:16px 18px;">

                          <div style="font-size:14px; font-weight:800; color:#9a3412; margin-bottom:8px;">
                            Atención requerida
                          </div>

                          <div style="font-size:13px; line-height:1.6; color:#7c2d12;">

                            <?php if (($data['tipo_notificacion'] ?? 0) == 1) { ?>

                              Se requiere asistencia inmediata en la estación indicada para continuar correctamente con el flujo de producción.

                            <?php } ?>

                            <?php if (($data['tipo_notificacion'] ?? 0) == 2) { ?>

                              Se reportó falta de material en la estación indicada. Favor de validar abastecimiento para evitar afectaciones en producción.

                            <?php } ?>

                          </div>

                        </td>

                      </tr>

                    </table>

                  </td>
                </tr>

                <!-- BOTÓN -->
                <tr>
                  <td align="center" style="padding:16px 22px 10px;">

                    <div style="font-size:13px; color:#6b7280; margin-bottom:12px;">
                      Puedes consultar el tablero de producción para dar seguimiento a esta solicitud.
                    </div>

                    <table role="presentation"
                           class="btn"
                           style="margin:0 auto;">

                      <tr>

                        <td align="center"
                            bgcolor="#111827"
                            style="border-radius:12px;">

                          <a href="<?= $data['url_detalle'] ?? '#' ?>"
                             target="_blank"
                             style="font-size:14px; font-weight:800; color:#ffffff; padding:12px 18px; display:inline-block;">

                            Abrir tablero

                          </a>

                        </td>

                      </tr>

                    </table>

                    <div style="font-size:12px; color:#9ca3af; margin-top:12px;">

                      Si el botón no funciona, copia y pega este enlace:<br>

                      <span style="word-break:break-all;">
                        <?= $data['url_detalle'] ?? '#' ?>
                      </span>

                    </div>

                  </td>
                </tr>

                <!-- FOOTER -->
                <tr>
                  <td style="padding:14px 22px 18px;">

                    <hr style="border:none; border-top:1px solid #e5e7eb; margin:6px 0 12px;">

                    <div style="font-size:12px; color:#9ca3af; line-height:1.5; text-align:center;">

                      © 2026 LDR Solutions · Producción MRP<br><br>

                      <strong style="color:#6b7280;">
                        Este correo fue generado automáticamente por el sistema.
                      </strong>

                    </div>

                  </td>
                </tr>

              </table>
              <!-- /CARD -->

            </td>
          </tr>

        </table>
        <!-- /CONTENEDOR -->

      </td>
    </tr>
  </table>

</body>
</html>