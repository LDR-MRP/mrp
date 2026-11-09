<div class="tab-pane" id="tabHistorial" role="tabpanel">

                            <h5 class="mb-4">
                                Historial del pedido
                            </h5>

                            <div class="d-flex flex-wrap gap-2 mb-3"><button type="button" class="btn btn-soft-primary btn-sm" id="btnReporteHistorial"><i class="ri-file-download-line me-1"></i>Exportar reporte de eventos (CSV)</button><button type="button" class="btn btn-soft-danger btn-sm" id="btnReporteHistorialPdf"><i class="ri-file-pdf-2-line me-1"></i>Exportar historial PDF</button><span class="text-muted small align-self-center"><?= count($bitacora) ?> eventos registrados</span></div>
                            <div class="card bg-body-secondary border-0 mb-3"><div class="card-body"><h6><i class="ri-user-voice-line me-1"></i>Respuestas del distribuidor</h6>
                            <?php foreach(($data['respuestas_comerciales']??[]) as $resp): ?><div class="border-bottom py-2"><strong>V<?= (int)$resp['numero_revision'] ?> · <?= htmlspecialchars($resp['respuesta'],ENT_QUOTES,'UTF-8') ?></strong><div class="small"><?= nl2br(htmlspecialchars($resp['observaciones']?:'Sin comentarios',ENT_QUOTES,'UTF-8')) ?></div><small class="text-muted"><?= htmlspecialchars($resp['nombre_responsable']?:'Usuario #'.$resp['idusuario_acceso'],ENT_QUOTES,'UTF-8') ?> · <?= htmlspecialchars($resp['fecha_respuesta'],ENT_QUOTES,'UTF-8') ?></small></div><?php endforeach; ?>
                            <?php if(empty($data['respuestas_comerciales'])): ?><p class="text-muted mb-0">Aún no hay respuestas registradas.</p><?php endif; ?></div></div>
                            <script type="application/json" id="pedHistorialJson"><?= json_encode(array_map(static function($e){return ['fecha'=>$e['fecha_creacion']??'','tipo'=>$e['tipo_evento']??'','descripcion'=>$e['descripcion']??'','responsable'=>($e['origen']??0)==2?'Distribuidor #'.($e['usuario_registro']??''):'Comercial #'.($e['usuario_registro']??''),'anterior'=>$e['estatus_anterior']??'','nuevo'=>$e['estatus_nuevo']??''];},$bitacora),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_UNESCAPED_UNICODE) ?></script>
                            <?php if (empty($bitacora)) { ?>

                                <div class="text-center py-5 text-muted">

                                    <i class="ri-history-line fs-1"></i>

                                    <p class="mt-2 mb-0">
                                        No hay movimientos registrados.
                                    </p>

                                </div>

                            <?php } else { ?>

                                <div class="timeline">

                                    <?php foreach ($bitacora as $evento) { ?>

                                        <div class="mb-4">

                                            <div class="fw-medium">

                                                <?= htmlspecialchars(
                                                    $evento['descripcion']
                                                    ?? ''
                                                ); ?>

                                            </div>

                                            <small class="text-muted">

                                                <?= htmlspecialchars(
                                                    $evento['fecha_creacion']
                                                    ?? ''
                                                ); ?>

                                                ·

                                                <?= (int)($evento['origen']??0)===2?'Distribuidor':'Comercial' ?> · Usuario #<?= (int)($evento['usuario_registro']??0) ?> · <?= htmlspecialchars($evento['tipo_evento']??'',ENT_QUOTES,'UTF-8') ?>

                                            </small>

                                        </div>

                                    <?php } ?>

                                </div>

                            <?php } ?>

                        </div>