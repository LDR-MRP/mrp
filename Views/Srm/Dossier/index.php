<?php require_once("Views/Template/Srm/header_srm.php"); ?>

<!-- CONTENIDO PRINCIPAL -->
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">

            <!-- Breadcrumb (Look Premium) -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between rounded px-3 py-2 bg-transparent">
                        <h4 class="mb-sm-0 fw-bold text-dark fs-15">Gestión de Expediente</h4>
                        <div class="page-title-right">
                            <ol class="breadcrumb m-0 fs-13">
                                <li class="breadcrumb-item"><a href="<?= base_url(); ?>/srm/dashboard">Resumen</a></li>
                                <li class="breadcrumb-item active text-primary">Expediente Digital</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Banner Superior con Progreso (Gradiente Corporativo LDR: Grafito a Naranja) -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm border-0 rounded-3 overflow-hidden bg-dark">
                        <div class="card-body p-4 position-relative z-1">
                            <div class="row align-items-center g-3">
                                <div class="col-md-8">
                                    <h4 class="text-white fw-bold mb-2 fs-18">Mi Expediente Digital</h4>
                                    <p class="text-white mb-0 fs-13">
                                        Para poder operar comercialmente con LDR Solutions, es indispensable cargar y mantener actualizado su expediente de documentación básica.
                                    </p>
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <div class="text-white mb-2 fs-13 fw-medium">Progreso del Expediente: <span id="lbl-progress-text">Cargando...</span></div>
                                    <div class="progress bg-white bg-opacity-25 rounded-pill" style="height: 10px;">
                                        <div class="progress-bar bg-warning progress-bar-striped progress-bar-animated rounded-pill" id="bar-progress" role="progressbar" style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Alerta de Estatus General de Onboarding -->
            <div class="alert alert-warning alert-border-left alert-dismissible fade show shadow-sm d-flex align-items-center mb-4" role="alert" id="alert-status">
                <i class="ri-alert-line me-3 align-middle fs-20 text-warning"></i>
                <div>
                    <strong>Acción Requerida:</strong> Tienes documentos obligatorios pendientes de cargar. Tu expediente se encuentra en estatus de <span class="fw-bold text-uppercase">Onboarding</span>.
                </div>
            </div>

            <!-- CONTENEDOR DINÁMICO (Aquí srm_dossier.js renderiza las tarjetas de los documentos) -->
            <div class="row g-4" id="dossier-grid">
                <!-- Esqueleto / Loader inicial de la UI -->
                <div class="col-12 text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Cargando expediente...</span>
                    </div>
                    <p class="text-muted mt-2 fs-13 animate-pulse">Compilando requisitos fiscales y legales...</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- Incluir CDN de la librería Dropzone para el arrastre de archivos -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css" type="text/css" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>

<?php require_once("Views/Template/Srm/footer_srm.php"); ?>