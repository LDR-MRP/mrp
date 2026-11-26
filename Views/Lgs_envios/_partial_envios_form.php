<?php
$esModoPreEnvio    = !empty($esModoPreEnvio);
$breadCrumbPadre   = $esModoPreEnvio ? 'Pre-envíos (Bandeja PT)' : 'Envíos';
$breadCrumbNuevo   = $esModoPreEnvio ? 'Nuevo Pre-envío' : 'Nuevo Envío';
$formTituloInicial = $esModoPreEnvio ? 'Crear Pre-envío / Posible Envío (PE-)' : 'Crear Solicitud de Traslado';
$btnGuardarTxt     = $esModoPreEnvio ? 'Guardar Pre-envío' : 'Guardar Envío';
$accionesTitulo    = $esModoPreEnvio ? 'Acciones del Pre-envío' : 'Acciones del Envío';
$estatusTitulo     = $esModoPreEnvio ? 'Estatus del Pre-envío' : 'Estatus del Envío';
$accentColor       = $esModoPreEnvio ? '#0284C7' : 'var(--brand-primary)';
?>
            <!-- ── SECCIÓN 2: VISTA FORMULARIO SEPARADO ────────────────────── -->
            <section id="view-form-envios" style="display: none;">
                <!-- 1. BREADCRUMB -->
                <div class="row align-items-center mb-4">
                    <div class="col-12">
                        <div class="page-title-box d-sm-flex align-items-center justify-content-between shadow-xs rounded px-3 py-2 bg-transparent">
                            <div class="page-title-right">
                                <ol class="breadcrumb m-0 fs-13">
                                    <li class="breadcrumb-item"><a href="<?= base_url(); ?>/dashboard">Logística</a></li>
                                    <li class="breadcrumb-item"><a href="javascript:void(0);" onclick="cancelFormEnvio();"><?= $breadCrumbPadre; ?></a></li>
                                    <li class="breadcrumb-item active text-primary fw-semibold" id="breadcrumb-form-envio"><?= $breadCrumbNuevo; ?></li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. HEADER FORMULARIO -->
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="d-flex align-items-center">
                            <div class="avatar-md me-3">
                                <span class="avatar-title bg-primary text-white rounded-circle fs-3 shadow-sm" style="background-color: <?= $accentColor; ?> !important;">
                                    <i class="ri-file-add-line"></i>
                                </span>
                            </div>
                            <div>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <h4 class="mb-1 fw-bold ls-05 text-dark" id="form-envio-title"><?= $formTituloInicial; ?></h4>
                                    <?php if ($esModoPreEnvio): ?>
                                        <span class="badge bg-info text-white fs-11 px-2 py-1 mb-1" id="badgeDistintivoPreEnvioForm">PRE-ENVÍO (PE-)</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted mb-0 fs-13">Configure el itinerario de paradas, modalidad de cálculo y transportista responsable.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <form id="formEnvio" name="formEnvio" autocomplete="off" onsubmit="event.preventDefault(); saveEnvio();">
                    <input type="hidden" id="id_envio" name="id_envio" value="">
                    <input type="hidden" id="es_preenvio" name="es_preenvio" value="<?= $esModoPreEnvio ? 1 : 0; ?>">

                    <div class="row g-4">
                        <!-- COLUMNA PRINCIPAL (8 de 12) -->
                        <div class="col-lg-8">
                            <!-- 1. PARÁMETROS OPERATIVOS DEL ENVÍO -->
                            <div class="envios-surface-card p-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 fw-bold text-dark fs-14">
                                        <i class="ri-settings-4-line text-primary me-2 align-middle"></i> Parámetros de Operación
                                    </h6>
                                    <span class="badge bg-light text-muted border fs-11">Datos Generales</span>
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-7">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Empresa Trasladista <span class="text-danger">*</span></label>
                                        <select class="form-select border rounded-3" id="id_proveedor" name="id_proveedor" required onchange="handleProveedorChange()">
                                            <option value="">Seleccione Trasladista...</option>
                                            <?php foreach ($data['catalogos']['proveedores'] ?? [] as $p): ?>
                                                <option value="<?= $p['id']; ?>" data-nombre="<?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?>" data-modalidad="<?= htmlspecialchars($p['modalidad_envio_default'] ?? 'DIRECTO', ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars($p['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-5">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Traslado <span class="text-danger">*</span></label>
                                        <select class="form-select border rounded-3" id="id_tipo_traslado" name="id_tipo_traslado" required onchange="handleTipoTrasladoEnvio()">
                                            <option value="">Seleccione Tipo...</option>
                                            <?php foreach ($data['catalogos']['tipos_traslado'] ?? [] as $t): ?>
                                                <option value="<?= $t['id']; ?>"><?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <!-- Check Lowboy (Oculto por defecto, visible solo si es Plataforma) -->
                                        <div id="divCheckLowboy" class="mt-2 form-check form-switch" style="display: none;">
                                            <input class="form-check-input border-dark bg-secondary" type="checkbox" role="switch" id="is_lowboy" name="is_lowboy" value="1">
                                            <label class="form-check-label fw-bold text-dark fs-12" for="is_lowboy">
                                                Check Lowboy <span class="badge bg-dark ms-1">Tarifa $80</span>
                                            </label>
                                        </div>
                                        <!-- Check Telemetría (Visible si es Madrina o Plataforma) -->
                                        <div id="divCheckTelemetria" class="mt-2 form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="madrina_con_telemetria" name="madrina_con_telemetria" value="1">
                                            <label class="form-check-label fw-bold text-dark fs-12" for="madrina_con_telemetria">
                                                <i class="ri-radar-line text-primary me-1"></i> Unidad de transporte con Telemetría GPS
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Motivo del Traslado <span class="text-danger">*</span></label>
                                        <select class="form-select border rounded-3" id="id_motivo" name="id_motivo" required>
                                            <option value="">Seleccione Motivo...</option>
                                            <?php foreach ($data['catalogos']['motivos'] ?? [] as $m): ?>
                                                <option value="<?= $m['id']; ?>"><?= htmlspecialchars($m['nombre'], ENT_QUOTES, 'UTF-8'); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Tipo de Servicio</label>
                                        <select class="form-select border rounded-3" id="tipo_servicio" name="tipo_servicio" onchange="actualizarDeteccionAutomaticaRutaUI(); verificarYCalcularRuta()">
                                            <option value="AUTOMATICO" selected>Automático / Mixto (Detecta SLC 0-40, SLL 41-80 y Foráneo >80 km por parada)</option>
                                            <option value="FORANEO">Foráneo (Costo por KM)</option>
                                            <option value="SLC">Local Corto SLC (0-40 KM)</option>
                                            <option value="SLL">Local Largo SLL (41-80 KM)</option>
                                        </select>
                                        <div id="badge-deteccion-servicio-form" class="mt-1 d-flex align-items-center flex-wrap gap-1"></div>
                                    </div>

                                    <!-- Alerta dinámica de detección automática (reglas locales / campechaneado) -->
                                    <div id="alert-regla-local-envio" class="col-md-12 mt-2 d-none"></div>

                                    <!-- Pre-confirmación de Cita / Recepción con Cliente para evitar envíos fallidos -->
                                    <div class="col-md-12 mt-2">
                                        <div class="p-2 border rounded-3 bg-light d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="form-check form-switch mb-0">
                                                <input class="form-check-input" type="checkbox" role="switch" id="confirmacion_cita_cliente" name="confirmacion_cita_cliente" value="1" onchange="document.getElementById('divContactoConfirmacion').style.display = this.checked ? 'block' : 'none';">
                                                <label class="form-check-label fw-bold text-dark fs-12" for="confirmacion_cita_cliente">
                                                    <i class="ri-checkbox-circle-line text-success me-1"></i> Cita de entrega pre-confirmada con Distribuidor / Cliente (Evita gastos y KM extra)
                                                </label>
                                            </div>
                                            <div class="flex-grow-1" id="divContactoConfirmacion" style="display: none; max-width: 320px;">
                                                <input type="text" class="form-control form-control-sm" id="contacto_confirmacion_cliente" name="contacto_confirmacion_cliente" placeholder="Nombre / Teléfono de quien confirmó cita...">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. MODALIDAD DE CÁLCULO (RADIO CARDS INTERACTIVAS) -->
                            <div class="envios-surface-card p-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <h6 class="mb-0 fw-bold text-dark fs-14">
                                        <i class="ri-calculator-line text-primary me-2 align-middle"></i> Modalidad de Cálculo de Flete
                                    </h6>
                                    <span class="badge bg-soft-primary text-primary fs-11 fw-semibold">Prorrateo Tarifario</span>
                                </div>

                                <!-- Input select nativo oculto para compatibilidad 100% con backend y validaciones -->
                                <select class="d-none" id="modalidad_calculo" name="modalidad_calculo" onchange="handleModalidadCalculoChange(true)">
                                    <option value="ESCALONADO">Escalonado (Tradicional)</option>
                                    <option value="DIRECTO">Directo con Escalas por Origen</option>
                                </select>

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="radio-card-option d-flex align-items-start gap-3" id="card-modalidad-directo" onclick="seleccionarModalidadCard('DIRECTO');">
                                            <div class="radio-dot mt-1"></div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <strong class="fs-13 text-dark"><i class="ri-direction-line text-primary me-1"></i>Directo con Escalas</strong>
                                                    <span class="badge bg-success-subtle text-success fs-10 fw-semibold">Recomendado</span>
                                                </div>
                                                <p class="text-muted fs-11 mb-0 mt-1">
                                                    Cálculo individual por unidad desde su origen de carga hasta su destino final.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="radio-card-option active d-flex align-items-start gap-3" id="card-modalidad-escalonado" onclick="seleccionarModalidadCard('ESCALONADO');">
                                            <div class="radio-dot mt-1"></div>
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <strong class="fs-13 text-dark"><i class="ri-route-line text-secondary me-1"></i>Escalonado Tradicional</strong>
                                                    <span class="badge bg-light text-secondary border fs-10 fw-semibold">Por Tramo</span>
                                                </div>
                                                <p class="text-muted fs-11 mb-0 mt-1">
                                                    Prorrateo escalonado tramo por tramo según el recorrido físico de la madrina.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 text-muted fs-11" id="helpModalidadCalculo">
                                    <i class="ri-information-line me-1"></i> Prorrateo escalonado tradicional por tramo.
                                </div>
                            </div>

                            <!-- 2.1 MONTO ABIERTO / ACUERDO COMERCIAL -->
                            <div class="envios-surface-card p-4 mb-4" id="card-monto-abierto-container">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <h6 class="mb-0 fw-bold text-dark fs-14">
                                            <i class="ri-hand-coin-line text-warning me-2 align-middle"></i> Modalidad Comercial: Monto Abierto
                                        </h6>
                                        <span class="badge bg-soft-warning text-warning fs-11 fw-semibold">Acuerdo Especial / MKT</span>
                                    </div>
                                    <div class="form-check form-switch form-switch-md mb-0">
                                        <input class="form-check-input" type="checkbox" id="check_monto_abierto" onchange="toggleMontoAbierto(this.checked);">
                                        <label class="form-check-label fs-12 fw-medium text-dark" for="check_monto_abierto">Habilitar Monto Abierto</label>
                                    </div>
                                </div>

                                <input type="hidden" id="es_monto_abierto" name="es_monto_abierto" value="0">

                                <div id="campos_monto_abierto" style="display: none;">
                                    <div class="alert alert-soft-warning border-0 p-3 mb-3 rounded-3 d-flex align-items-center gap-2">
                                        <i class="ri-information-line text-warning fs-20 flex-shrink-0"></i>
                                        <div class="fs-12 text-dark">
                                            Al activar <strong>Monto Abierto</strong>, el sistema respetará el costo pactado directamente con el proveedor (para estadías de operadores, vuelos, viáticos o condiciones de Marketing), omitiendo el recálculo tarifario estándar por kilómetro.
                                        </div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-5">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Monto Total Pactado ($ MXN sin IVA) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text bg-light border fw-bold text-muted">$</span>
                                                <input type="number" step="0.00001" min="0" class="form-control fw-bold fs-14 text-success" id="monto_pactado" name="monto_pactado" placeholder="0.00" oninput="actualizarCostoEstimadoPorMontoAbierto();">
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Motivo / Justificación del Acuerdo <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="motivo_monto_abierto" name="motivo_monto_abierto" placeholder="Ej. Movimiento MKT: estadías toda la semana y vuelo operador Cancún">
                                        </div>
                                    </div>
                                </div>
                                <div id="help_monto_abierto_inactivo" class="text-muted fs-11">
                                    <i class="ri-checkbox-blank-circle-line me-1 text-muted" style="font-size: 8px;"></i> Tabulador estándar activo por defecto. Active esta opción solo si existe un acuerdo comercial o gasto extraordinario fuera de tabulador.
                                </div>
                            </div>

                            <!-- 3. ITINERARIO Y SECUENCIA DE PARADAS (MULTI-ORIGEN Y MULTI-DESTINO) -->
                            <div class="envios-surface-card p-4 mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2 flex-wrap gap-2">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark fs-14">
                                            <i class="ri-route-line text-primary me-2 align-middle"></i> Itinerario del Recorrido (Multi-Origen y Multi-Destino) <span class="text-danger">*</span>
                                        </h6>
                                        <small class="text-muted fs-11">
                                            Construye la secuencia del viaje: <strong><span class="text-success">Carga Inicial (Verde)</span></strong>, <strong><span style="color: #0d6efd;">Cargas Adicionales (Azul)</span></strong> y <strong><span style="color: #fd7e14;">Bajadas / Descargas (Naranja)</span></strong>.
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm btn-soft-primary rounded-pill px-3 shadow-xs" onclick="verificarYCalcularRuta(null, true)" title="Consultar distancias en memoria">
                                            <i class="ri-refresh-line me-1"></i> ⚡ Distancias
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3 shadow-xs" onclick="agregarNodoRuta(null, true)">
                                            <i class="ri-add-line me-1"></i> Añadir Carga
                                        </button>
                                        <button type="button" class="btn btn-sm text-white rounded-pill px-3 shadow-xs" style="background-color: #fd7e14; border-color: #fd7e14;" onclick="agregarNodoRuta(null, false)">
                                            <i class="ri-add-line me-1"></i> Añadir Descarga
                                        </button>
                                    </div>
                                </div>

                                <!-- Badge Resumen Distancia Total -->
                                <div id="badge-distancia-total-container" class="alert alert-soft-primary d-flex align-items-center justify-content-between flex-wrap gap-2 p-2 mb-3 rounded-3" style="display:none !important;">
                                    <span class="fs-12 fw-medium"><i class="ri-road-map-line text-primary me-1"></i>Distancia Total del Recorrido:</span>
                                    <div class="d-flex align-items-center flex-wrap gap-2">
                                        <span id="badge-km-total-val" class="badge bg-primary fs-13">0 km</span>
                                        <span id="badge-servicio-ruta-val" class="badge fs-12 d-none"></span>
                                    </div>
                                </div>

                                <!-- Contenedor dinámico de nodos del recorrido -->
                                <div id="contenedor-nodos-ruta" class="d-flex flex-column gap-3 mb-3">
                                    <!-- Los nodos se agregan aquí dinámicamente -->
                                </div>

                                <div id="msg-sin-nodos" class="text-center py-4 border border-dashed rounded-3 text-muted bg-light" style="border-style: dashed !important; display:none;">
                                    <i class="ri-map-pin-add-line fs-28 d-block mb-1 text-primary opacity-50"></i>
                                    <span class="fs-12">Sin puntos en el recorrido. Haz clic en <strong>"Añadir Carga"</strong> o <strong>"Añadir Descarga"</strong> para comenzar.</span>
                                </div>

                                <!-- Campos ocultos de sincronización -->
                                <input type="hidden" id="fecha_tentativa_envio" name="fecha_tentativa_envio" value="">
                                <input type="hidden" id="fecha_tentativa_llegada" name="fecha_tentativa_llegada" value="">
                                <input type="hidden" id="nodos_json" name="nodos" value="[]">
                                <input type="hidden" id="paradas_json" name="paradas" value="[]">
                                <input type="hidden" id="id_origen" name="id_origen" value="">
                                <input type="hidden" id="id_destino" name="id_destino" value="">
                                <input type="hidden" id="destino_nombre_libre" name="destino_nombre_libre" value="">

                                <!-- Catálogos completos para el JS -->
                                <script id="catalogoUbicaciones" type="application/json">
                                    <?= json_encode($data['catalogos']['ubicaciones'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>
                                <script id="catalogoPlantasWms" type="application/json">
                                    <?= json_encode($data['catalogos']['plantas_wms'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>
                                <script id="catalogoDestinos" type="application/json">
                                    <?= json_encode($data['catalogos']['destinos'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>
                                <script id="catalogoDistribuidores" type="application/json">
                                    <?= json_encode($data['catalogos']['distribuidores'] ?? [], JSON_UNESCAPED_UNICODE); ?>
                                </script>

                                <div class="mt-3">
                                    <label class="form-label text-uppercase fs-11 fw-bold text-muted mb-1">Observaciones Operativas</label>
                                    <textarea class="form-control border rounded-3" id="observaciones" name="observaciones" rows="2" placeholder="Instrucciones especiales para el viaje, accesos a patios, contactos de entrega..."></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- COLUMNA LATERAL (4 de 12) -->
                        <div class="col-lg-4">
                            <!-- Card: Acciones Principales -->
                            <div class="envios-surface-card p-4 mb-4">
                                <h6 class="fw-bold text-dark mb-3 fs-14 border-bottom pb-2">
                                    <i class="ri-play-list-add-line text-primary me-2 align-middle"></i> <?= $accionesTitulo; ?>
                                </h6>
                                <div class="d-grid gap-2">
                                    <button type="button" id="btnActionForm" class="btn btn-primary rounded-pill py-2 shadow-sm fw-bold" onclick="saveEnvio();" style="background-color: <?= $accentColor; ?>; border-color: <?= $accentColor; ?>;">
                                        <i class="ri-save-3-line align-middle me-1"></i> <span id="btnText"><?= $btnGuardarTxt; ?></span>
                                    </button>
                                    <a href="javascript:void(0);" id="btnIrAcomodoDirecto" class="btn btn-soft-info border border-info-subtle rounded-pill py-2 fw-semibold text-info-emphasis" style="display:none;" onclick="irAcomodoDesdeFormulario();">
                                        <i class="ri-truck-line align-middle fs-16 me-1 text-info"></i> Ir al Acomodo de VINs
                                    </a>
                                    <button type="button" id="btnReabrirEditar" class="btn btn-warning rounded-pill py-2 shadow-sm fw-semibold" onclick="fntReabrirParaEditar()" style="display:none;">
                                        <i class="ri-lock-unlock-line align-middle me-1"></i> Reabrir para Editar
                                    </button>
                                    <button type="button" class="btn btn-light border rounded-pill py-2 fw-semibold text-secondary" onclick="cancelFormEnvio();">
                                        <i class="ri-arrow-go-back-line align-middle fs-16 me-1"></i> Cancelar y Volver
                                    </button>
                                </div>
                            </div>

                            <!-- Card: Estatus del Envío -->
                            <div class="card border-0 shadow-sm mb-4 text-white" style="border-radius: 16px; background: linear-gradient(135deg, #0F172A 0%, #1E293B 100%);">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <span class="text-white-50 text-uppercase fs-11 fw-bold ls-1 d-block mb-1">
                                                <?= $estatusTitulo; ?>
                                            </span>
                                            <h4 class="text-white mb-0 fw-bold" id="lbl-estatus-envio-lateral">
                                                En Borrador
                                            </h4>
                                        </div>
                                        <div class="flex-shrink-0" id="icon-estatus-envio-lateral">
                                            <i class="ri-route-line text-white fs-28 opacity-50"></i>
                                        </div>
                                    </div>
                                    <div class="text-white-50 fs-11 mt-3 pt-2 border-top border-secondary border-opacity-25" id="desc-estatus-envio-lateral">
                                        <i class="ri-information-line me-1"></i> Las subidas y bajadas de VINs por tramo se configuran en la siguiente pantalla.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </section>

            <!-- ── MODAL CAPTURA DISTANCIAS FALTANTES (MEMORIA PROGRESIVA) ── -->
            <div class="modal fade" id="modalDistanciasFaltantes" tabindex="-1" aria-labelledby="modalDistanciasLabel" aria-hidden="true" data-bs-backdrop="static">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content border-0 shadow-lg" style="border-radius: 12px;">
                        <div class="modal-header bg-soft-warning border-bottom border-warning py-3">
                            <h5 class="modal-title fw-bold text-dark fs-16" id="modalDistanciasLabel">
                                <i class="ri-route-line text-warning me-2 fs-18"></i> Distancias de Tramos Desconocidas
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="alert alert-soft-info d-flex align-items-center mb-3 rounded-3">
                                <i class="ri-lightbulb-line fs-22 me-3 text-info flex-shrink-0"></i>
                                <div class="fs-13">
                                    Se detectaron tramos nuevos en la ruta sin distancia en el sistema. 
                                    <strong>Ingresa los kilómetros una sola vez:</strong> el sistema los guardará en su memoria progresiva para utilizarlos en este y futuros envíos.
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr class="fs-12 text-uppercase text-muted">
                                            <th style="width: 50px;">Tramo</th>
                                            <th>Punto de Origen</th>
                                            <th style="width: 30px;" class="text-center text-muted">➔</th>
                                            <th>Punto de Destino</th>
                                            <th style="width: 170px;">Distancia (KM) <span class="text-danger">*</span></th>
                                        </tr>
                                    </thead>
                                    <tbody id="tbodyDistanciasFaltantes">
                                        <!-- Se llena dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer bg-light py-2">
                            <button type="button" class="btn btn-sm btn-light border px-3" data-bs-dismiss="modal">Cerrar</button>
                            <button type="button" class="btn btn-sm btn-primary px-4 fw-semibold" id="btnGuardarDistanciasModal" onclick="guardarDistanciasFaltantesModal()">
                                <i class="ri-save-line me-1"></i> Guardar en Memoria y Continuar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
