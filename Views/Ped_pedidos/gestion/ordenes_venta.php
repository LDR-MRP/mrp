<div class="tab-pane" id="tabOrdenesVenta" role="tabpanel">

                            <div class="d-flex justify-content-between align-items-center mb-3">

                                <div>

                                    <h5 class="mb-1">
                                        Órdenes de Venta
                                    </h5>

                                    <p class="text-muted mb-0">
                                        Órdenes generadas a partir del pedido.
                                    </p>

                                </div>


                                <button type="button"
                                    class="btn btn-primary accion-gestion-pedido <?= !$gestionIniciada ? 'd-none' : ''; ?>"
                                    id="btnNuevaOrdenVenta">

                                    <i class="ri-add-line me-1"></i>
                                    Generar Orden de Venta

                                </button>

                            </div>


                            <div id="contenidoOrdenesVenta"></div>

                        </div>


                        <!-- =================================================
                             HISTORIAL
                        ================================================== -->

                        