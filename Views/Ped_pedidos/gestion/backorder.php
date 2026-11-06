<div class="tab-pane" id="tabBackorder" role="tabpanel">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div><h5 class="mb-1"><i class="ri-stack-line text-primary me-1"></i>Backorders vinculados</h5><p class="text-muted mb-0">Consulta de compromisos aceptados. El surtido se administra en el módulo independiente.</p></div>
    <a class="btn btn-primary" href="<?= base_url() ?>/ped_backorders?pedido=<?= rawurlencode((string)($pedido['folio_pedido']??'')) ?>"><i class="ri-external-link-line me-1"></i>Ir a Backorders</a>
  </div>
                            <div class="card border shadow-none"><div class="card-body"><h6><i class="ri-stack-line me-1 text-primary"></i>Backorder del pedido</h6><div class="alert alert-info border-0"><i class="ri-information-line me-1"></i><strong>Seguimiento de surtido.</strong> Las unidades aceptadas comienzan pendientes; en <strong>Asignación</strong> se vincularán unidades físicas o VIN. El backorder no representa inventario disponible ni una unidad ya reservada.</div><p class="text-muted small">Las cantidades se crean únicamente cuando el distribuidor acepta. Las asignaciones físicas se realizarán en su propia pestaña.</p>
                            <div class="table-responsive"><table class="table align-middle"><thead><tr><th>Modelo</th><th>Color</th><th>Comprometidas</th><th>Asignadas</th><th>Pendientes</th><th>Estado</th></tr></thead><tbody>
                            <?php foreach(($data['backorders_comerciales']??[]) as $b): ?><tr><td><?= $escRev($b['modelo']) ?></td><td><?= $escRev($b['nombre_color']??'Sin color') ?></td><td><?= (int)$b['cantidad_comprometida'] ?></td><td><?= (int)$b['cantidad_asignada'] ?></td><td class="fw-bold"><?= (int)$b['cantidad_pendiente'] ?></td><td><span class="badge bg-warning-subtle text-warning"><?= $escRev($b['estatus']) ?></span></td></tr><?php endforeach; ?>
                            <?php if(empty($data['backorders_comerciales'])): ?><tr><td colspan="6" class="text-center text-muted py-4">Todavía no hay unidades confirmadas en backorder.</td></tr><?php endif; ?>
                            </tbody></table></div></div></div>
</div>
