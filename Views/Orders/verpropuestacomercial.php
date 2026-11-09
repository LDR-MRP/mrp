<?php
headerOrders($data);
$propuesta = $data['propuesta'] ?? null;
$detalles = $data['detalles_propuesta'] ?? [];
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
$money = static fn($v) => '$'.number_format((float)$v,2).' MXN';
?>
<main class="commercial-review-page"><div class="commercial-review-container">
 <a class="commercial-back" href="<?= base_url(); ?>/orders/solicitudescomerciales"><i class="ri-arrow-left-line"></i> Solicitudes comerciales</a>
 <?php if (!$propuesta): ?>
  <div class="commercial-empty"><i class="ri-error-warning-line"></i><h1>Propuesta no disponible</h1><p>La solicitud no existe, ya fue atendida o no pertenece a tu cuenta.</p></div>
 <?php else: ?>
  <div class="commercial-header"><div class="commercial-heading-icon"><i class="ri-file-shield-2-line"></i></div><div><div class="commercial-eyebrow">REVISIÓN DE CONDICIONES COMERCIALES</div><h1>Pedido <?= $esc($propuesta['folio_pedido']); ?></h1><p>Versión <?= (int)$propuesta['numero_revision']; ?> · Consulta las condiciones propuestas antes de tomar una decisión.</p></div></div>
  <div class="commercial-trace"><div class="commercial-trace-heading"><span><i class="ri-route-line"></i> Seguimiento de tu pedido</span><span>45% · Pendiente de aprobación</span></div><div class="commercial-trace-track"><div class="commercial-trace-fill" style="width:45%"></div></div><small>Solicitud → Revisión comercial → Tu aceptación → Backorder → Asignación → Entrega. Porcentaje orientativo por etapas.</small></div>
  <div class="commercial-notice"><i class="ri-information-line"></i><span>Esta propuesta está pendiente de tu respuesta. Los cambios de cantidades, colores y precios se muestran por renglón. Al aceptar, todas las unidades de esta versión pasarán a backorder como pendientes. Si necesitas cambios, describe el ajuste para que Comercial prepare una nueva versión.</span></div>
  <div class="commercial-table-wrap"><table class="commercial-table"><thead><tr><th>Unidad / modelo</th><th>Color solicitado</th><th>Color propuesto</th><th>Cantidad original</th><th>Cantidad propuesta</th><th>Precio unitario</th><th>Subtotal</th><th>Descuento</th><th>IVA</th><th>Total</th></tr></thead><tbody>
   <?php foreach ($detalles as $detalle): ?>
    <tr><td><strong><?= $esc($detalle['nombre_unidad'] ?: $detalle['modelo']); ?></strong><small><?= $esc($detalle['version'] ?? ''); ?></small><?php if (!empty($detalle['motivo_ajuste'])): ?><small><?= $esc($detalle['motivo_ajuste']); ?></small><?php endif; ?></td><td><?= $esc($detalle['color_original'] ?: 'No especificado'); ?></td><td><strong><?= $esc($detalle['color_propuesto'] ?: 'No especificado'); ?></strong></td><td><?= (int)$detalle['cantidad_solicitada_original']; ?></td><td><strong><?= (int)$detalle['cantidad_propuesta']; ?></strong></td><td><?= $money($detalle['precio_unitario']); ?></td><td><?= $money($detalle['subtotal']); ?></td><td><strong><?= $money($detalle['descuento']); ?></strong><small><?= ($detalle['descuento_tipo']??'MONTO')==='PORCENTAJE' ? number_format((float)$detalle['descuento_valor'],2).'%' : 'Descuento en MXN' ?></small></td><td><?= $money($detalle['iva']); ?></td><td><strong><?= $money($detalle['total']); ?></strong></td></tr>
   <?php endforeach; ?>
   <?php if (!$detalles): ?><tr><td colspan="10">La propuesta aún no contiene renglones para mostrar.</td></tr><?php endif; ?>
  </tbody></table></div>
  <div class="commercial-pdf-action"><button type="button" class="commercial-action-secondary" data-propuesta-pdf><i class="ri-file-pdf-2-line"></i> Descargar propuesta PDF</button></div><div class="commercial-totals"><div><span>Subtotal</span><strong><?= $money($propuesta['subtotal']); ?></strong></div><div><span>Descuento</span><strong><?= $money($propuesta['descuento']); ?></strong></div><div><span>IVA</span><strong><?= $money($propuesta['iva']); ?></strong></div><div class="commercial-grand-total"><span>Total propuesto</span><strong><?= $money($propuesta['total']); ?></strong></div></div>
  <section class="commercial-observations commercial-response"><h2><i class="ri-shield-check-line"></i> Tu decisión sobre esta propuesta</h2><p>La respuesta quedará registrada con usuario, fecha, versión y huella de auditoría. Una aceptación no puede deshacerse desde esta pantalla.</p>
  <label for="comentariosRespuesta">Comentarios o ajustes solicitados</label><textarea id="comentariosRespuesta" rows="3" class="form-control" placeholder="Si solicitas cambios, explica cuáles y por qué..."></textarea>
  <div class="commercial-response-actions"><button id="btnSolicitarAjustes" type="button" class="commercial-action-secondary"><i class="ri-edit-2-line"></i> Solicitar ajustes</button><button id="btnRechazarPropuesta" type="button" class="commercial-action-danger"><i class="ri-close-circle-line"></i> Rechazar propuesta</button><button id="btnAceptarPropuesta" type="button" class="commercial-action-primary"><i class="ri-checkbox-circle-line"></i> Aceptar propuesta</button></div>
  </section>
  <?php $pdfLineas=[]; foreach($detalles as $pl){$pdfLineas[]=['modelo'=>$pl['nombre_unidad']?:$pl['modelo'],'color'=>$pl['color_propuesto']??'','cantidad'=>(int)$pl['cantidad_propuesta'],'precio'=>(float)$pl['precio_unitario'],'subtotal'=>(float)$pl['subtotal'],'descuento'=>(float)$pl['descuento'],'iva'=>(float)$pl['iva'],'total'=>(float)$pl['total']];} $pdfInfo=['folio'=>$propuesta['folio_pedido'],'version'=>(int)$propuesta['numero_revision'],'estado'=>$propuesta['estatus'],'subtotal'=>(float)$propuesta['subtotal'],'descuento'=>(float)$propuesta['descuento'],'iva'=>(float)$propuesta['iva'],'total'=>(float)$propuesta['total'],'lineas'=>$pdfLineas]; ?><script type="application/json" id="propuestaPdfData"><?= json_encode($pdfInfo,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
  <script>window.PED_PORTAL_REVISION={clave:<?= json_encode($propuesta['clave']) ?>,csrf:<?= json_encode($_SESSION['ped_revision_portal_csrf']??'') ?>};</script>
  <?php if (!empty($propuesta['observaciones_comercial'])): ?><section class="commercial-observations"><h2><i class="ri-chat-3-line"></i> Comentarios de Comercial</h2><p><?= nl2br($esc($propuesta['observaciones_comercial'])); ?></p></section><?php endif; ?>
 <?php endif; ?>
</div></main>
<?php footerOrders($data); ?>
