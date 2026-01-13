<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="x-apple-disable-message-reformatting">
  <title>Orden de Trabajo asignada</title>

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
      .stack td { display:block !important; width:100% !important; }
    }
  </style>
</head>

<body>

  <!-- Preheader -->
  <div style="display:none; font-size:1px; line-height:1px; max-height:0; max-width:0; opacity:0; overflow:hidden;">
    Se te asignó una nueva Orden de Trabajo: OT-2026-00087
  </div>

  <table role="presentation" width="100%" style="background:#f4f6f8;">
    <tr>
      <td align="center" style="padding: 28px 12px;">

        <!-- CONTENEDOR -->
        <table role="presentation" class="container" width="600" style="width:600px; max-width:600px;">

          <!-- CARD PRINCIPAL (TODO DENTRO) -->
          <tr>
            <td class="px" style="padding: 0 24px;">

              <table role="presentation" width="100%" style="background:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 6px 18px rgba(17,24,39,.06);">

                <!-- BARRA SUPERIOR (ACENTO) -->
                <tr>
                  <td style="background:#111827; height:6px; line-height:6px; font-size:0;">
                    &nbsp;
                  </td>
                </tr>

                <!-- HEADER INTERNO -->
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
                    
                                <div style="font-size:20px; color:#111827; font-weight:800; margin-top:6px;">
                                  Orden de Trabajo asignada
                                </div>
                              </td>
                            </tr>
                          </table>
                        </td>
                        <td align="right">
                          <div style="font-size:12px; color:#6b7280;">
                            Notificación
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
                      Hola, <strong><?= $data['nombre'] ?? '' ?></strong>
                      <br><br>
                      Se te ha asignado una nueva <strong>Orden de Trabajo</strong>. A continuación te compartimos el detalle para que puedas dar seguimiento.
                    </div>
                  </td>
                </tr>

                <!-- RESUMEN (PRIORIDAD / SUPERVISOR) -->
                <tr>
                  <td style="padding: 8px 22px 10px;">
                    <table role="presentation" width="100%" style="border:1px solid #e5e7eb; border-radius:12px;">
                      <tr class="stack">
                        <td style="padding: 12px 14px; border-right:1px solid #e5e7eb;">
                          <div style="font-size:12px; color:#6b7280;">Prioridad</div>
                          <div style="font-size:14px; color:#111827; font-weight:800; margin-top:4px;"><?= $data['prioridad'] ?? '' ?></div>
                        </td>
                        <td style="padding: 12px 14px;">
                          <div style="font-size:12px; color:#6b7280;">Supervisor</div>
                          <div style="font-size:14px; color:#111827; font-weight:800; margin-top:4px;"><strong><?= $data['supervisor'] ?? '' ?></div>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>


                                <!-- DETALLE DE RESPONSABILIDAD -->
                <!-- <tr>
                  <td style="padding: 10px 22px 6px;">
                    <div style="font-size:14px; color:#111827; font-weight:800; margin-bottom:10px;">
                      Estación asignada (Responsabilidad)
                    </div>

                    <table role="presentation" width="100%" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                      <tr>
                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; width:42%;">
                          Estación
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; font-weight:800;">
                      
                        </td>
                      </tr>

                        <tr>
                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; width:42%;">
                          Proceso
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; font-weight:800;">
                   
                        </td>
                      </tr>

                    </table>
                  </td>
                </tr> -->

                <!-- DETALLE -->
                <tr>
                  <td style="padding: 10px 22px 6px;">
                    <div style="font-size:14px; color:#111827; font-weight:800; margin-bottom:10px;">
                      Detalle de la orden
                    </div>

                    <table role="presentation" width="100%" style="border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                      <tr>
                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280; width:42%;">
                          Número de orden
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827; font-weight:800;">
                          <?= $data['num_orden'] ?>
                        </td>
                      </tr>

                      <tr>
                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">
                          Producto
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">
                          <strong><?= $data['cve_producto'] ?? '' ?></strong> — <?= $data['descripcion'] ?? '' ?>
                        </td>
                      </tr>

                      <tr>
                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">
                          Cantidad a producir
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">
                          <?= $data['cantidad'] .' piezas' ?> 
                        </td>
                      </tr>

                      <tr>
                        <td style="background:#f9fafb; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:12px; color:#6b7280;">
                          Fecha inicio de producción
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; border-bottom:1px solid #e5e7eb; font-size:13px; color:#111827;">
                          <?= $data['fecha_inicio_txt'] ?? '' ?>
                        </td>
                      </tr>

                      <tr>
                        <td style="background:#f9fafb; padding:12px 14px; font-size:12px; color:#6b7280;">
                          Fecha requerida
                        </td>
                        <td style="background:#ffffff; padding:12px 14px; font-size:13px; color:#111827;">
                           <?= $data['fecha_requerida_txt'] ?? '' ?>
                        </td>
                      </tr>
                    </table>
                  </td>
                </tr>

                <!-- BOTÓN -->
                <tr>
                  <td align="center" style="padding: 16px 22px 10px;">
                    <div style="font-size:13px; color:#6b7280; margin-bottom:12px;">
                      Para consultar el detalle completo de la orden, da click en el botón:
                    </div>

                    <table role="presentation" class="btn" style="margin:0 auto;">
                      <tr>
                        <td align="center" bgcolor="#111827" style="border-radius:12px;">
                          <a href="https://mrp.ldrsolutions.com/ordenes/OT-2026-00087"
                             target="_blank"
                             style="font-size:14px; font-weight:800; color:#ffffff; padding:12px 18px; display:inline-block;">
                            Consultar detalle de la orden
                          </a>
                        </td>
                      </tr>
                    </table>

                    <div style="font-size:12px; color:#9ca3af; margin-top:12px;">
                      Si el botón no funciona, copia y pega este enlace en tu navegador:<br>
                      <span style="word-break:break-all;">https://mrp.ldrsolutions.com/ordenes/OT-2026-00087</span>
                    </div>
                  </td>
                </tr>

                <!-- FOOTER DENTRO DEL CARD -->
                <tr>
                  <td style="padding: 14px 22px 18px;">
                    <hr style="border:none; border-top:1px solid #e5e7eb; margin: 6px 0 12px;">
                    <div style="font-size:12px; color:#9ca3af; line-height:1.5; text-align:center;">
                      © 2026 LDR Solutions · Uso interno<br>
                      <br>
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
