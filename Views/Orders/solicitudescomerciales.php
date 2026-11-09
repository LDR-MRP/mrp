<?php
headerOrders($data);
$solicitudes = $data['solicitudes'] ?? [];
$esc = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
?>
<main class="commercial-review-page">
 <div class="commercial-review-container">
  <a class="commercial-back" href="<?= base_url(); ?>/orders/micuenta"><i class="ri-arrow-left-line"></i> Mi cuenta</a>
  <div class="commercial-header">
   <div class="commercial-heading-icon"><i class="ri-shield-check-line"></i></div>
   <div><div class="commercial-eyebrow">PORTAL DE DISTRIBUIDORES</div><h1>Solicitudes comerciales por aprobar</h1><p>Revisa las propuestas que Comercial ha enviado para tus pedidos. Cada versión conserva sus condiciones para auditoría.</p></div>
  </div>
  <div class="commercial-summary"><i class="ri-notification-3-line"></i><div><strong><?= count($solicitudes); ?> <?= count($solicitudes) === 1 ? 'solicitud pendiente' : 'solicitudes pendientes'; ?></strong><span>Solo se muestran propuestas enviadas y pendientes de tu respuesta.</span></div></div>
  <?php if (!$solicitudes): ?>
   <div class="commercial-empty"><i class="ri-checkbox-circle-line"></i><h2>Todo está al día</h2><p>Por ahora no tienes propuestas comerciales pendientes de revisión.</p><a href="<?= base_url(); ?>/orders/micuenta">Volver a mis pedidos</a></div>
  <?php else: ?>
   <div class="commercial-list">
    <?php foreach ($solicitudes as $solicitud): ?>
     <article class="commercial-item">
      <div class="commercial-item-main"><span class="commercial-pill"><i class="ri-time-line"></i> Pendiente de revisión</span><h2>Pedido <?= $esc($solicitud['folio_pedido'] ?? ''); ?></h2><p>Propuesta comercial · Versión <?= (int)($solicitud['numero_revision'] ?? 0); ?></p><small>Enviada: <?= $esc(!empty($solicitud['fecha_envio']) ? date('d/m/Y H:i', strtotime($solicitud['fecha_envio'])) : 'Sin fecha'); ?></small></div>
      <div class="commercial-item-actions"><strong>$<?= number_format((float)($solicitud['total'] ?? 0),2); ?> MXN</strong><a href="<?= base_url(); ?>/orders/verpropuestacomercial/<?= rawurlencode((string)$solicitud['clave']); ?>">Ver propuesta <i class="ri-arrow-right-line"></i></a></div>
     </article>
    <?php endforeach; ?>
   </div>
  <?php endif; ?>
 </div>
</main>
<?php footerOrders($data); ?>
