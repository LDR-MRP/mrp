<div class="tab-pane" id="tabBackorder" role="tabpanel">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div><h5 class="mb-1"><i class="ri-stack-line text-primary me-1"></i>Backorders vinculados</h5><p class="text-muted mb-0">Consulta de compromisos aceptados. El surtido se administra en el módulo independiente.</p></div>
    <a class="btn btn-primary" href="<?= base_url() ?>/ped_backorders?pedido=<?= rawurlencode((string)($pedido['folio_pedido']??'')) ?>"><i class="ri-external-link-line me-1"></i>Ir a Backorders</a>
  </div>
                            <div class="card border shadow-none"><div class="card-body"><h6><i class="ri-stack-line me-1 text-primary"></i>Backorder del pedido</h6><div class="alert alert-info border-0"><i class="ri-information-line me-1"></i><strong>Seguimiento de surtido.</strong> Las unidades aceptadas comienzan pendientes; en <strong>Asignación</strong> se vincularán unidades físicas o VIN. El backorder no representa inventario disponible ni una unidad ya reservada.</div><p class="text-muted small">Las cantidades se crean únicamente cuando el distribuidor acepta. Las asignaciones físicas se realizarán en su propia pestaña. La zona y dirección se muestran por renglón según la propuesta aceptada.</p>
                            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Modelo</th><th>Color</th><th>Zona / dirección de entrega</th><th>Comprometidas</th><th>Asignadas</th><th>Pendientes</th><th>Estado</th></tr></thead><tbody>
                            <?php foreach(($data['backorders_comerciales']??[]) as $b): ?><tr><td><?= $escRev($b['modelo']) ?></td><td><?= $escRev($b['nombre_color']??'Sin color') ?></td><td><div class="fw-semibold"><?= $escRev($b['sucursal_entrega'] ?: ($b['tipo_entrega'] ?: 'Por confirmar')) ?></div><small class="text-muted"><?= $escRev($b['direccion_entrega'] ?: 'Dirección no especificada') ?></small><div class="mt-1"><button type="button" class="btn btn-sm btn-soft-primary" data-bs-toggle="modal" data-bs-target="#modalDireccionBackorder" data-zona="<?= htmlspecialchars((string)($b['sucursal_entrega'] ?: $b['tipo_entrega'] ?: 'Por confirmar'), ENT_QUOTES, 'UTF-8') ?>" data-direccion="<?= htmlspecialchars((string)($b['direccion_entrega'] ?: 'Dirección no especificada'), ENT_QUOTES, 'UTF-8') ?>"><i class="ri-map-pin-line me-1"></i>Ver dirección</button></div></td><td><?= (int)$b['cantidad_comprometida'] ?></td><td><?= (int)$b['cantidad_asignada'] ?></td><td class="fw-bold"><?= (int)$b['cantidad_pendiente'] ?></td><td><span class="badge bg-warning-subtle text-warning"><?= $escRev($b['estatus']) ?></span></td></tr><?php endforeach; ?>
                            <?php if(empty($data['backorders_comerciales'])): ?><tr><td colspan="7" class="text-center text-muted py-4">Todavía no hay unidades confirmadas en backorder.</td></tr><?php endif; ?>
                            </tbody></table></div></div></div>
</div>

<div class="modal fade" id="modalDireccionBackorder" tabindex="-1" aria-labelledby="tituloDireccionBackorder" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title" id="tituloDireccionBackorder"><i class="ri-map-pin-2-line text-primary me-2"></i>Destino de entrega</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button></div>
    <div class="modal-body"><div class="bg-light rounded-3 p-3 mb-3"><div class="text-muted small mb-1">Zona o sucursal</div><div class="fw-semibold" id="boZonaEntrega">Por confirmar</div></div><div class="border rounded-3 p-3"><div class="text-muted small mb-1">Dirección de entrega</div><div id="boDireccionEntrega" class="text-break">No especificada</div></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cerrar</button></div>
  </div></div>
</div>
<script>
(function(){
 const modal=document.getElementById('modalDireccionBackorder');
 if(!modal)return;
 modal.addEventListener('show.bs.modal',function(event){
  const boton=event.relatedTarget;
  modal.querySelector('#boZonaEntrega').textContent=boton?.dataset.zona||'Por confirmar';
  modal.querySelector('#boDireccionEntrega').textContent=boton?.dataset.direccion||'Dirección no especificada';
 });
})();
</script>
