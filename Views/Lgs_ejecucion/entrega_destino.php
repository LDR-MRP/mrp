<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Confirmación de Entrega en Destino (QR) — MRP</title>
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
            --success-brand: #10B981;
            --primary-dark: #0F172A;
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
            background: linear-gradient(135deg, #064E3B 0%, #065F46 50%, #047857 100%);
            color: white;
            padding: 24px 20px 28px;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
            box-shadow: 0 10px 25px -5px rgba(6, 78, 59, 0.25);
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
            background: radial-gradient(circle, rgba(16, 185, 129, 0.4) 0%, rgba(255,255,255,0) 70%);
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
            border: 2px solid #065F46;
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
            border-color: var(--success-brand);
            background: rgba(16, 185, 129, 0.04);
        }
        .photo-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Step Circle Badge */
        .step-num {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background: var(--success-brand);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
            margin-right: 8px;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="app-header mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center">
            <div class="avatar-sm me-3 bg-white text-success rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 42px; height: 42px;">
                <i class="ri-checkbox-circle-line fs-4 text-success"></i>
            </div>
            <div>
                <h5 class="mb-0 fw-bold text-white">Recepción en Destino</h5>
                <small class="text-white-50 fs-12"><i class="ri-qr-scan-2-line me-1"></i>Validación y Cierre de Viaje</small>
            </div>
        </div>
        <span class="badge bg-white text-success px-3 py-2 rounded-pill fw-semibold fs-11 shadow-xs">
            <i class="ri-shield-check-line me-1"></i>QR Seguro
        </span>
    </div>
</div>

<div class="container pb-5 px-3">
    <div class="d-flex align-items-center mb-3">
        <a class="btn btn-light border rounded-circle p-2 me-2 shadow-sm d-flex align-items-center justify-content-center text-dark text-decoration-none" style="width: 38px; height: 38px;" href="<?= base_url(); ?>/Lgs_ejecucion/chofer_movil">
            <i class="ri-arrow-left-s-line fs-4"></i>
        </a>
        <div>
            <h6 class="fw-bold mb-0 text-dark">Confirmación de Entrega</h6>
            <small class="text-muted fs-11">Punto final de descarga de unidades</small>
        </div>
    </div>

    <!-- Selección de Envío para Entrega -->
    <div class="card-custom p-3 mb-4 bg-white">
        <label class="form-label fw-bold fs-11 text-muted text-uppercase ls-1">Seleccione el Viaje que se Entrega</label>
        <select class="form-select form-select-lg fw-bold text-dark font-monospace rounded-3" id="select-viaje-entrega" onchange="cargarDetalleEntrega(this.value)">
            <option value="">Buscar folio de viaje...</option>
            <!-- Inyectado dinámicamente -->
        </select>
    </div>

    <div id="detalle-entrega-container" class="d-none">
        <!-- 1. Tarjeta de Destino -->
        <div class="card-custom p-3 mb-3 bg-white">
            <div class="d-flex align-items-center mb-2">
                <span class="step-num">1</span>
                <h6 class="fw-bold fs-13 mb-0 text-dark">Punto de Entrega</h6>
            </div>
            <div class="p-3 bg-light rounded-3 border">
                <div class="fs-14 fw-bold text-dark mb-1" id="lblDestinoEntrega">--</div>
                <div class="fs-12 text-muted"><i class="ri-map-pin-line me-1 text-danger"></i><span id="lblDireccionEntrega">--</span></div>
            </div>
        </div>

        <!-- 2. Escaneo de QR del Cliente -->
        <div class="card-custom p-3 mb-3 bg-white text-center">
            <div class="d-flex align-items-center mb-3 text-start">
                <span class="step-num">2</span>
                <h6 class="fw-bold fs-13 mb-0 text-dark">Validar Identidad del Concesionario (QR)</h6>
            </div>
            
            <button type="button" class="btn btn-primary w-100 rounded-pill py-2 fw-semibold shadow-sm mb-2" onclick="iniciarEscaneoQR();">
                <i class="ri-camera-lens-line me-1"></i> Abrir Cámara para Leer QR del Cliente
            </button>
            <div id="qr-escaneo-container" class="camera-container d-none mb-2">
                <div id="reader"></div>
            </div>
            <input type="hidden" id="qr_cliente_validado" value="0">
            <div id="lblStatusQR" class="text-danger fw-bold fs-13 mt-2">
                <i class="ri-close-circle-line me-1"></i>QR de Cliente Pendiente de Validación
            </div>
        </div>

        <!-- 3. Escaneo e inspección de VINs en Destino -->
        <div class="mb-3">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <div class="d-flex align-items-center">
                    <span class="step-num">3</span>
                    <h6 class="fw-bold fs-13 mb-0 text-dark">Unidades Recibidas por el Cliente</h6>
                </div>
            </div>
            <div id="lista-vins-entrega" class="mb-3">
                <!-- Inyectado dinámicamente -->
            </div>
        </div>

        <!-- 4. Evidencias Generales de Entrega -->
        <div class="card-custom p-3 mb-4 bg-white">
            <div class="d-flex align-items-center mb-3">
                <span class="step-num">4</span>
                <h6 class="fw-bold fs-13 mb-0 text-dark">Remisión Firmada y Cierre</h6>
            </div>
            
            <form id="formEntregaFinal" enctype="multipart/form-data">
                <input type="hidden" id="ent_id_envio" name="id_envio">
                
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <span class="fs-11 text-muted fw-bold d-block mb-1">Foto Remisión Sellada</span>
                        <div class="photo-preview text-muted" onclick="triggerFile('file_remision')">
                            <img id="img_remision" class="d-none">
                            <i id="ico_remision" class="ri-file-paper-2-line fs-2 text-muted opacity-50"></i>
                        </div>
                        <input type="file" id="file_remision" name="evidencia_remision" accept="image/*" capture="environment" class="d-none" onchange="previewImage(this, 'remision')">
                    </div>

                    <div class="col-6">
                        <span class="fs-11 text-muted fw-bold d-block mb-1">Firma Digital / Foto Receptor</span>
                        <div class="photo-preview text-muted" onclick="triggerFile('file_firma')">
                            <img id="img_firma" class="d-none">
                            <i id="ico_firma" class="ri-edit-2-line fs-2 text-muted opacity-50"></i>
                        </div>
                        <input type="file" id="file_firma" name="evidencia_firma" accept="image/*" class="d-none" onchange="previewImage(this, 'firma')">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fs-12 text-muted fw-bold">Nombre Completo de quien Recibe <span class="text-danger">*</span></label>
                    <input type="text" class="form-control rounded-3" id="nombre_recibe" name="nombre_recibe" placeholder="Ej. Lic. Carlos Gómez" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fs-12 text-muted fw-bold">Comentarios u Observaciones de Entrega</label>
                    <textarea class="form-control rounded-3 fs-13" name="comentarios" rows="2" placeholder="Notas sobre la recepción final física..."></textarea>
                </div>
            </form>

            <button type="button" class="btn btn-primary w-100 rounded-pill py-3 fw-bold fs-15 shadow-sm" onclick="guardarEntregaDestino()">
                <i class="ri-checkbox-circle-line me-1"></i> FINALIZAR ENTREGA Y VIAJE
            </button>
        </div>
    </div>
</div>

<script>
    const base_url = "<?= base_url(); ?>";
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode"></script>
<script src="<?= base_url(); ?>/Assets/js/modulos/functions_lgs_entrega.js"></script>

</body>
</html>
