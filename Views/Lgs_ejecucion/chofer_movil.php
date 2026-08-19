<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Portal Trasladista — Inspección y Carga Móvil</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <!-- Core CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-brand: #C46623;
            --primary-dark: #121B2B;
            --primary-surface: #1E293B;
            --bg-app: #F8FAFC;
            --card-border: rgba(226, 232, 240, 0.8);
            --font-main: 'Plus Jakarta Sans', sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        body {
            background-color: var(--bg-app);
            font-family: var(--font-main);
            color: #1E293B;
            -webkit-font-smoothing: antialiased;
            padding-bottom: 80px;
        }

        /* Hero Header */
        .app-header {
            background: linear-gradient(135deg, #0F172A 0%, #1E293B 50%, #334155 100%);
            color: white;
            padding: 24px 20px 28px;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
            position: relative;
            overflow: hidden;
        }
        .app-header::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 140px;
            height: 140px;
            background: radial-gradient(circle, rgba(196, 102, 35, 0.35) 0%, rgba(255,255,255,0) 70%);
            border-radius: 50%;
        }

        /* Modern Cards */
        .card-custom {
            background: #FFFFFF;
            border: 1px solid var(--card-border);
            border-radius: 18px;
            box-shadow: 0 4px 16px -2px rgba(15, 23, 42, 0.04), 0 2px 6px -1px rgba(15, 23, 42, 0.02);
            transition: transform 0.2s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.2s ease;
            overflow: hidden;
        }
        .card-custom:active {
            transform: scale(0.985);
        }

        /* VIN Pill Monospace */
        .vin-pill {
            font-family: var(--font-mono);
            font-weight: 700;
            font-size: 1.05rem;
            letter-spacing: 1.5px;
            color: #0F172A;
            background: #F1F5F9;
            padding: 8px 14px;
            border-radius: 10px;
            border: 1px dashed #CBD5E1;
            display: inline-block;
        }

        /* Badges */
        .badge-soft-warning { background-color: rgba(245, 158, 11, 0.12); color: #B45309; }
        .badge-soft-success { background-color: rgba(16, 185, 129, 0.12); color: #047857; }
        .badge-soft-primary { background-color: rgba(196, 102, 35, 0.12); color: #C46623; }
        .badge-soft-info { background-color: rgba(14, 165, 233, 0.12); color: #0369A1; }

        /* Camera Visor HUD */
        .camera-container {
            width: 100%;
            max-width: 400px;
            height: 240px;
            background-color: #000;
            border-radius: 18px;
            overflow: hidden;
            position: relative;
            margin: auto;
            border: 2px solid #334155;
            box-shadow: inset 0 0 20px rgba(0,0,0,0.8);
        }
        #reader { width: 100%; height: 100%; }

        /* Photo Upload Dropzone */
        .photo-preview {
            width: 100%;
            height: 110px;
            border-radius: 14px;
            border: 2px dashed #CBD5E1;
            background: #F8FAFC;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .photo-preview:hover, .photo-preview:active {
            border-color: var(--primary-brand);
            background: rgba(196, 102, 35, 0.03);
        }
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-preview .badge-check {
            position: absolute;
            top: 6px;
            right: 6px;
            background: #10B981;
            color: white;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        /* Route Indicator Flow */
        .route-indicator {
            position: relative;
            padding-left: 24px;
        }
        .route-indicator::before {
            content: '';
            position: absolute;
            left: 7px;
            top: 8px;
            bottom: 8px;
            width: 2px;
            background: #CBD5E1;
        }
        .route-step {
            position: relative;
            margin-bottom: 12px;
        }
        .route-step:last-child { margin-bottom: 0; }
        .route-step::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 4px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #fff;
            border: 3px solid #64748B;
        }
        .route-step.origin::before { border-color: #10B981; background: #10B981; }
        .route-step.dest::before { border-color: var(--primary-brand); background: var(--primary-brand); }
    </style>
</head>
<body>

<!-- Header -->
<div class="app-header mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="avatar-sm me-3 bg-white text-dark rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                <i class="ri-truck-line text-primary fs-4" style="color: var(--primary-brand) !important;"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold text-white">Portal Trasladista</h5>
                <small class="text-white-50 fs-12"><i class="ri-user-smile-line me-1"></i>Inspección en Patio</small>
            </div>
        </div>
        <span class="badge badge-soft-warning px-3 py-2 rounded-pill fw-semibold fs-11">
            <i class="ri-radar-line me-1"></i>En Vivo
        </span>
    </div>
</div>

<div class="container pb-5 px-3">
    <!-- Listado de Envíos Pendientes -->
    <div id="section-envios">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <h6 class="fw-bold text-uppercase fs-12 text-muted mb-0 ls-1">
                <i class="ri-calendar-check-line me-1 text-primary"></i>Viajes Asignados
            </h6>
            <button class="btn btn-sm btn-light border rounded-pill px-3 fs-12 fw-semibold" onclick="cargarEnviosChofer();">
                <i class="ri-refresh-line me-1"></i>Actualizar
            </button>
        </div>
        
        <div id="lista-envios">
            <div class="text-center py-5 text-muted">
                <div class="spinner-border text-primary mb-3" role="status" style="color: var(--primary-brand) !important;"></div>
                <div class="fw-semibold">Cargando tus traslados asignados...</div>
            </div>
        </div>
    </div>

    <!-- Detalle e Inspección del Envío -->
    <div id="section-detalle-envio" class="d-none">
        <div class="d-flex align-items-center mb-3">
            <button class="btn btn-light border rounded-circle p-2 me-2 shadow-sm d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;" onclick="mostrarListaEnvios();">
                <i class="ri-arrow-left-s-line fs-4 text-dark"></i>
            </button>
            <div>
                <h6 class="fw-bold mb-0 text-dark">Viaje: <span id="lblFolioViaje" class="text-primary" style="color: var(--primary-brand) !important;"></span></h6>
                <small class="text-muted fs-11">Verificación física y checklist de unidades</small>
            </div>
        </div>

        <!-- Tarjeta Resumen de Ruta -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="route-indicator mb-3">
                <div class="route-step origin">
                    <span class="fs-11 text-uppercase text-muted fw-bold d-block">Punto de Origen</span>
                    <strong class="fs-14 text-dark" id="lblOrigenViaje">Planta</strong>
                    <div class="fs-12 text-muted" id="lblOrigenDir">--</div>
                </div>
                <div class="route-step dest">
                    <span class="fs-11 text-uppercase text-muted fw-bold d-block">Punto de Destino</span>
                    <strong class="fs-14 text-dark" id="lblDestinoViaje">Distribuidor / Cliente</strong>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold fs-13 text-uppercase text-muted mb-0 ls-1">Unidades por Cargar (VINs)</h6>
            <span class="badge bg-light text-dark border px-2 py-1 fs-11" id="lblTotalVinsBadge">0 unidades</span>
        </div>

        <div id="lista-vins-checklist">
            <!-- Tarjetas de VINs inyectadas dinámicamente -->
        </div>
    </div>

    <!-- Modal de Captura y Checklist por VIN -->
    <div class="modal fade" id="modalInspeccionVin" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-md-down">
            <div class="modal-content border-0 shadow-2xl" style="border-radius: 24px;">
                <div class="modal-header bg-dark text-white border-0 pb-3" style="border-top-left-radius: 24px; border-top-right-radius: 24px;">
                    <div class="d-flex align-items-center">
                        <div class="avatar-xs bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="background-color: var(--primary-brand) !important; width: 32px; height: 32px;">
                            <i class="ri-shield-check-line fs-5"></i>
                        </div>
                        <h6 class="modal-title fw-bold text-white mb-0" id="titleModalInspeccion">Inspección de Unidad</h6>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="formInspeccionVin" enctype="multipart/form-data">
                        <input type="hidden" id="chk_id_envio" name="id_envio">
                        <input type="hidden" id="chk_id_unidad" name="id_unidad">
                        <input type="hidden" id="chk_tipo_checklist" name="tipo_checklist" value="entrada_trasladista">
                        
                        <!-- 1. VIN Display -->
                        <div class="mb-4 text-center">
                            <span class="text-muted fs-11 text-uppercase fw-bold d-block mb-1">Código VIN de la Unidad</span>
                            <span class="vin-pill shadow-xs" id="lblVinInspeccion"></span>
                        </div>

                        <!-- 2. Lector de Cámara HUD -->
                        <div class="mb-4 p-3 bg-light rounded-3 border">
                            <label class="form-label fs-12 text-uppercase fw-bold text-muted mb-2"><i class="ri-barcode-box-line me-1 text-primary"></i>Paso 1: Validación Óptica</label>
                            <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm mb-3" onclick="iniciarEscaneo();">
                                <i class="ri-qr-code-line me-1"></i> Abrir Cámara Escáner VIN
                            </button>
                            <div id="escaneo-container" class="camera-container d-none mb-3">
                                <div id="reader"></div>
                            </div>
                            <input type="text" class="form-control form-control-lg fw-bold text-uppercase text-center font-monospace" id="vin_confirmado" name="vin" placeholder="Confirmar VIN leído" required>
                        </div>

                        <!-- 3. Fotografías de Evidencia -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-3 border-top pt-3">
                                <h6 class="fw-bold fs-13 mb-0"><i class="ri-camera-lens-line me-1 text-primary"></i>Paso 2: 5 Fotos Obligatorias</h6>
                                <span class="badge bg-soft-primary text-primary px-2 py-1 fs-11">Evidencia 360°</span>
                            </div>
                            
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">1. Frente</span>
                                    <div class="photo-preview text-muted" onclick="triggerFile('file_frente')">
                                        <img id="img_frente" class="d-none">
                                        <i id="ico_frente" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_frente" name="frente" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, 'frente')">
                                </div>
                                
                                <div class="col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">2. Atrás</span>
                                    <div class="photo-preview text-muted" onclick="triggerFile('file_atras')">
                                        <img id="img_atras" class="d-none">
                                        <i id="ico_atras" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_atras" name="atras" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, 'atras')">
                                </div>
                                
                                <div class="col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">3. Lateral Izquierdo</span>
                                    <div class="photo-preview text-muted" onclick="triggerFile('file_lateral_izq')">
                                        <img id="img_lateral_izq" class="d-none">
                                        <i id="ico_lateral_izq" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_lateral_izq" name="lateral_izq" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, 'lateral_izq')">
                                </div>
                                
                                <div class="col-6">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">4. Lateral Derecho</span>
                                    <div class="photo-preview text-muted" onclick="triggerFile('file_lateral_der')">
                                        <img id="img_lateral_der" class="d-none">
                                        <i id="ico_lateral_der" class="ri-image-add-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_lateral_der" name="lateral_der" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, 'lateral_der')">
                                </div>

                                <div class="col-12 mt-2">
                                    <span class="fs-11 text-muted fw-bold d-block mb-1">5. Kilometraje / Odómetro</span>
                                    <div class="photo-preview text-muted" onclick="triggerFile('file_odometro')" style="height: 100px;">
                                        <img id="img_odometro" class="d-none">
                                        <i id="ico_odometro" class="ri-dashboard-3-line fs-2 text-muted opacity-50"></i>
                                    </div>
                                    <input type="file" id="file_odometro" name="odometro" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, 'odometro')">
                                </div>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fs-12 text-muted fw-bold">Observaciones / Daños Preexistentes</label>
                                <textarea class="form-control fs-13 rounded-3" id="chk_comentarios" name="comentarios" rows="2" placeholder="Describa rayones, faltantes o detalles físicos..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer bg-light border-0 p-3 d-flex flex-column gap-2" style="border-bottom-left-radius: 24px; border-bottom-right-radius: 24px;">
                    <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-bold fs-15 shadow-sm" onclick="guardarInspeccion();">
                        <i class="ri-checkbox-circle-line me-1"></i> Guardar y Confirmar Carga
                    </button>
                    <button type="button" class="btn btn-light border w-100 rounded-pill py-2 fs-13 fw-semibold" data-bs-dismiss="modal">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= base_url(); ?>/Assets/js/modulos/functions_lgs_chofer.js"></script>
</body>
</html>
