<div class="tab-pane active" id="tabResumen" role="tabpanel">

                            <div class="row g-4">

                                <div class="col-lg-6">

                                    <h5 class="mb-3">
                                        Información del pedido
                                    </h5>

                                    <div class="table-responsive">

                                        <table class="table table-borderless mb-0">

                                            <tbody>

                                                <tr>
                                                    <td class="text-muted">
                                                        Fecha pedido
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['fecha_pedido']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-muted">
                                                        Fecha requerida
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['fecha_requerida']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-muted">
                                                        Mes facturación
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['mes_facturacion_deseado']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="text-muted">
                                                        Prioridad
                                                    </td>

                                                    <td class="fw-medium">
                                                        <?= htmlspecialchars(
                                                            $pedido['prioridad']
                                                            ?? ''
                                                        ); ?>
                                                    </td>
                                                </tr>

                                            </tbody>

                                        </table>

                                    </div>

                                </div>


                                <div class="col-lg-6">

                                    <h5 class="mb-3">
                                        Solicitante
                                    </h5>

                                    <p class="mb-1 fw-medium">

                                        <?= htmlspecialchars(
                                            trim(
                                                ($pedido['nombre_usuario'] ?? '')
                                                . ' '
                                                . ($pedido['apellido_usuario'] ?? '')
                                            )
                                        ); ?>

                                    </p>

                                    <p class="text-muted mb-3">

                                        <?= htmlspecialchars(
                                            $pedido['correo_usuario']
                                            ?? ''
                                        ); ?>

                                    </p>


                                    <h6>
                                        Observaciones
                                    </h6>

                                    <p class="text-muted mb-0">

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $pedido['observaciones']
                                                ?: 'Sin observaciones.'
                                            )
                                        ); ?>

                                    </p>

                                </div>

                            </div>

                        </div>


                        <!-- =================================================
                             REVISIÓN
                        ================================================== -->

                        