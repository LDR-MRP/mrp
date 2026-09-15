import re

with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php', 'r') as f:
    content = f.read()

old_str = """                    <?php 
                        $estadoEnvio = intval($data['envio']['id_estado'] ?? 1);
                        if ($estadoEnvio === 1): 
                    ?>
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="guardarAcomodo(true);">
                            <i class="ri-check-double-line me-1"></i> Finalizar y Volver
                        </button>
                    <?php elseif ($estadoEnvio === 8): ?>
                        <a href="<?= base_url(); ?>/Lgs_envios" class="btn btn-soft-secondary rounded-pill px-4 shadow-sm me-2">
                            <i class="ri-arrow-go-back-line me-1"></i> Volver
                        </a>
                        <button class="btn btn-warning rounded-pill px-4 shadow-sm" onclick="guardarAcomodo(false);">
                            <i class="ri-edit-line me-1"></i> Editar (Regresa a Borrador)
                        </button>
                    <?php else: ?>
                        <a href="<?= base_url(); ?>/Lgs_envios" class="btn btn-soft-secondary rounded-pill px-4 shadow-sm">
                            <i class="ri-arrow-go-back-line me-1"></i> Volver (Solo Lectura)
                        </a>
                    <?php endif; ?>"""

new_str = """                    <?php 
                        $estadoEnvio = intval($data['envio']['id_estado'] ?? 1);
                        $estadoPlan = intval($data['estado_planeacion'] ?? 0);
                        
                        $enPlaneacionAbierta = ($estadoEnvio === 2 && $estadoPlan < 3);
                        $puedeEditar = ($estadoEnvio === 8 || $enPlaneacionAbierta);
                        
                        if ($estadoEnvio === 1): 
                    ?>
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="guardarAcomodo(true);">
                            <i class="ri-check-double-line me-1"></i> Finalizar y Volver
                        </button>
                    <?php elseif ($puedeEditar): ?>
                        <a href="<?= base_url(); ?>/Lgs_envios" class="btn btn-soft-secondary rounded-pill px-4 shadow-sm me-2">
                            <i class="ri-arrow-go-back-line me-1"></i> Volver
                        </a>
                        <button class="btn btn-warning rounded-pill px-4 shadow-sm" onclick="guardarAcomodo(false);">
                            <i class="ri-edit-line me-1"></i> <?= $enPlaneacionAbierta ? 'Editar (Regresa a Borrador)' : 'Editar (Regresa a Borrador)' ?>
                        </button>
                    <?php else: ?>
                        <a href="<?= base_url(); ?>/Lgs_envios" class="btn btn-soft-secondary rounded-pill px-4 shadow-sm">
                            <i class="ri-arrow-go-back-line me-1"></i> Volver (Solo Lectura)
                        </a>
                    <?php endif; ?>"""

if old_str in content:
    content = content.replace(old_str, new_str)
    with open('/home/christianguarneros/proyectos/mrp/Views/Lgs_envios/detalle.php', 'w') as f:
        f.write(content)
    print("detalle.php updated successfully!")
else:
    print("Could not find block in detalle.php")
