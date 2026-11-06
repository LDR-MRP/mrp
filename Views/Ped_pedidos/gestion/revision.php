<div class="tab-pane" id="tabRevision" role="tabpanel">
                            <?php
                            $rev = $data['revision_comercial'] ?? null;
                            $lineasRev = $data['lineas_revision'] ?? [];
                            $escRev = static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
                            $editableRev = $gestionIniciada && (!$rev || $rev['estatus'] === 'BORRADOR');
                            ?>
                            <?php $ultimaRespuesta = ($data['respuestas_comerciales'] ?? [])[0] ?? null; ?>
                            <?php if ($ultimaRespuesta): ?>
                              <?php
                                $tipoRespuesta = strtoupper((string)($ultimaRespuesta['respuesta'] ?? ''));
                                $presentacionRespuesta = match ($tipoRespuesta) {
                                    'ACEPTAR', 'ACEPTADA' => ['alert-success', 'ri-checkbox-circle-line', 'Propuesta aceptada'],
                                    'SOLICITAR_AJUSTE', 'AJUSTE_SOLICITADO' => ['alert-warning', 'ri-edit-circle-line', 'Ajustes solicitados'],
                                    'RECHAZAR', 'RECHAZADA' => ['alert-danger', 'ri-close-circle-line', 'Propuesta rechazada'],
                                    default => ['alert-info', 'ri-information-line', 'Respuesta registrada'],
                                };
                              ?>
                              <div class="alert <?= $presentacionRespuesta[0] ?> border-0 d-flex gap-3 align-items-start commercial-feedback" role="status">
                                <i class="<?= $presentacionRespuesta[1] ?> fs-3" aria-hidden="true"></i>
                                <div><h6 class="mb-1"><?= $escRev($presentacionRespuesta[2]) ?> · Versión <?= (int)$ultimaRespuesta['numero_revision'] ?></h6>
                                <p class="mb-1"><?= nl2br($escRev($ultimaRespuesta['observaciones'] ?: 'Sin observaciones')) ?></p>
                                <small>Respondió: <?= $escRev($ultimaRespuesta['nombre_responsable'] ?: 'Usuario del portal #'.$ultimaRespuesta['idusuario_acceso']) ?> · <?= $escRev($ultimaRespuesta['fecha_respuesta']) ?></small></div>
                              </div>
                            <?php endif; ?>
                            <?php if ($estatusPedido === 'RECHAZADO'): ?><div class="alert alert-danger border-0"><i class="ri-lock-line me-2"></i>Pedido rechazado y cerrado. La información se conserva únicamente para consulta y auditoría.</div><?php endif; ?>
                            <div class="card border shadow-none commercial-panel">
                              <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
                                  <div><div class="text-uppercase text-primary small fw-semibold mb-1"><i class="ri-shield-check-line me-1"></i> Negociación auditable</div>
                                  <h5 class="mb-1">Revisión comercial</h5>
                                  <p class="text-muted mb-0">Ajusta colores, cantidades y montos. Cada versión enviada se conserva para auditoría.</p></div>
                                  <span class="badge bg-info-subtle text-info fs-12"><?= $escRev($rev['estatus'] ?? 'SIN_BORRADOR') ?> · V<?= (int)($rev['numero_revision'] ?? 0) ?></span>
                                </div>
                                <div class="alert alert-info border-0"><i class="ri-information-line me-1"></i> La cantidad solicitada es histórica. Comercial propone la cantidad a autorizar; el distribuidor debe aceptar antes de generar backorder. Puedes aplicar descuentos en pesos o porcentaje por cada fila. El importe y los totales se recalculan antes de guardar.</div>
                                <?php if ($estatusPedido === 'EN_REVISION' && (!$rev || $rev['estatus'] === 'AJUSTE_SOLICITADO')): ?>
                                  <button type="button" id="btnCrearRevisionComercial" class="btn btn-primary"><i class="ri-file-add-line me-1"></i><?= $rev ? 'Crear nueva versión de ajustes' : 'Crear borrador de revisión' ?></button>
                                <?php endif; ?>
                                <?php if ($lineasRev): ?>
                                <div class="table-responsive mt-3"><table class="table table-hover align-middle commercial-table-admin">
                                  <thead><tr><th>Modelo y condiciones</th><th>Solicitadas</th><th>Color propuesto</th><th>Autorizadas</th><th>Precio unitario</th><th>Descuento y cálculo</th><th>Motivo del ajuste</th><th>Importe</th></tr></thead>
                                  <tbody>
                                  <?php foreach($lineasRev as $l): ?>
                                    <tr class="revision-linea" data-id="<?= (int)$l['idrevision_detalle'] ?>">
                                      <td><strong><?= $escRev($l['nombre'] ?: $l['modelo']) ?></strong><div class="small text-muted"><?= $escRev($l['version']) ?></div><div class="small text-muted">Original: <?= $escRev($l['nombre_color_original'] ?: 'Sin color') ?></div></td>
                                      <td class="fw-semibold text-center"><?= (int)$l['cantidad_solicitada_original'] ?></td>
                                      <td><select class="form-select form-select-sm rc-color" <?= !$editableRev?'disabled':'' ?>><option value="" <?= $l['idunidad_color_original']!==null?'disabled':'' ?>><?= $l['idunidad_color_original']===null ? 'Seleccionar color (sin color original)' : 'Sin color' ?></option>
                                        <?php foreach(($data['colores_revision'][(int)$l['idunidad']]??[]) as $c): ?><option value="<?= (int)$c['idunidad_color'] ?>" <?= (int)$c['idunidad_color']===(int)$l['idunidad_color']?'selected':'' ?>><?= $escRev($c['nombre']) ?></option><?php endforeach; ?>
                                      </select><?php if($l['idunidad_color_original']===null): ?><small class="text-muted d-block mt-1"><i class="ri-palette-line me-1"></i>El pedido llegó sin color. Selecciona el color que se ofrecerá al distribuidor.</small><?php endif; ?></td>
                                      <td><input type="number" min="0" max="100000" class="form-control form-control-sm rc-cantidad" value="<?= (int)$l['cantidad_propuesta'] ?>" <?= !$editableRev?'disabled':'' ?>></td>
                                      <td><input type="text" inputmode="decimal" class="form-control form-control-sm rc-precio rc-moneda" value="<?= $escRev($l['precio_unitario']) ?>" <?= !$editableRev?'disabled':'' ?>></td>
                                      <td class="rc-descuento-celda"><div class="input-group input-group-sm mb-1"><select class="form-select rc-descuento-tipo" aria-label="Tipo de descuento" <?= !$editableRev?'disabled':'' ?>><option value="MONTO" <?= ($l['descuento_tipo']??'MONTO')==='MONTO'?'selected':'' ?>>$ MXN</option><option value="PORCENTAJE" <?= ($l['descuento_tipo']??'MONTO')==='PORCENTAJE'?'selected':'' ?>>% Porcentaje</option></select><input type="text" inputmode="decimal" class="form-control rc-descuento" value="<?= $escRev($l['descuento_valor']??$l['descuento']) ?>" aria-label="Valor del descuento" <?= !$editableRev?'disabled':'' ?>></div><small class="text-muted d-block rc-descuento-aplicado">Descuento aplicado: $<?= number_format((float)$l['descuento'],2) ?></small></td>
                                      <td><textarea rows="2" class="form-control form-control-sm rc-motivo" placeholder="Explica los cambios..." <?= !$editableRev?'disabled':'' ?>><?= $escRev($l['motivo_ajuste']??'') ?></textarea></td>
                                      <td class="rc-importe"><strong class="d-block rc-total">$<?= number_format((float)$l['total'],2) ?></strong><small class="d-block text-muted rc-operacion">Subtotal = cantidad × precio</small><small class="d-block text-muted rc-subtotal">Subtotal: $<?= number_format((float)$l['subtotal'],2) ?></small><small class="d-block text-muted rc-base">Base: $<?= number_format(max(0,(float)$l['subtotal']-(float)$l['descuento']),2) ?></small><small class="d-block text-muted rc-iva">IVA: $<?= number_format((float)$l['iva'],2) ?></small></td>
                                    </tr>
                                  <?php endforeach; ?>
                                  </tbody></table></div>
                                  <div class="rc-totales rounded bg-body-secondary p-3 ms-auto"><div><span>Subtotal</span><strong id="rcSubtotal">$<?= number_format((float)$rev['subtotal'],2) ?></strong></div><div><span>Descuento</span><strong id="rcDescuento">-$<?= number_format((float)$rev['descuento'],2) ?></strong></div><div><span>IVA</span><strong id="rcIva">$<?= number_format((float)$rev['iva'],2) ?></strong></div><div class="rc-total-general"><span>Total de la propuesta</span><strong id="rcTotal">$<?= number_format((float)$rev['total'],2) ?></strong></div><small class="text-muted d-block mt-2"><i class="ri-calculator-line me-1"></i>Importe = (cantidad × precio − descuento) + IVA. Los importes se validan nuevamente al guardar.</small></div>
                                  <div class="mt-3 d-flex gap-2 flex-wrap"><button type="button" class="btn btn-outline-secondary" data-propuesta-pdf><i class="ri-file-pdf-2-line me-1"></i>PDF de propuesta</button></div><div class="mt-3"><label class="form-label">Observaciones para el distribuidor</label><textarea id="rcObservaciones" rows="3" class="form-control" placeholder="Información que verá el distribuidor" <?= !$editableRev?'disabled':'' ?>><?= $escRev($rev['observaciones_comercial']??'') ?></textarea></div>
                                  <?php if($editableRev): ?><div class="d-flex justify-content-end flex-wrap gap-2 mt-3"><button class="btn btn-soft-primary" id="btnGuardarRevisionComercial"><i class="ri-save-line me-1"></i>Guardar borrador</button><button class="btn btn-success" id="btnEnviarRevisionComercial"><i class="ri-send-plane-line me-1"></i>Enviar para aceptación</button></div><?php endif; ?>
                                <?php elseif($rev): ?><div class="alert alert-warning mt-3">No se encontraron renglones de la revisión.</div><?php endif; ?>
                              </div>
                            </div>
                            <div class="card border shadow-none"><div class="card-body"><h6><i class="ri-git-branch-line me-1"></i>Versiones comerciales</h6><div class="table-responsive"><table class="table"><thead><tr><th>Versión</th><th>Estado</th><th>Creación</th><th>Envío</th><th>Total</th></tr></thead><tbody>
                            <?php foreach(($data['versiones_comerciales']??[]) as $v): ?><tr><td>V<?= (int)$v['numero_revision'] ?></td><td><?= $escRev($v['estatus']) ?></td><td><?= $escRev($v['fecha_creacion']) ?></td><td><?= $escRev($v['fecha_envio']??'—') ?></td><td>$<?= number_format((float)$v['total'],2) ?></td></tr><?php endforeach; ?>
                            </tbody></table></div></div></div>
                        </div>

                        <!-- =================================================
                             DISPONIBILIDAD
                        ================================================== -->

                        <!-- =================================================
                             ÓRDENES DE VENTA
                        ================================================== -->

                        <!-- =================================================
                             ASIGNACIÓN
                        ================================================== -->

                        