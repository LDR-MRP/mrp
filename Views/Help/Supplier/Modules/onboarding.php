<section id="onboarding" class="doc-section">
    <h2><i class="ri-user-follow-line align-middle me-2"></i>Gestión Integral de Proveedores</h2>
    <p>El portal de proveedores es el filtro de seguridad fiscal y comercial de LDR Solutions. El proceso garantiza que cada socio comercial cumpla con los requisitos legales antes de ser habilitado para compras.</p>

    <!-- 1. REGISTRO Y PERFIL FISCAL -->
    <div class="row mt-4">
        <div class="col-md-7">
            <h4>1. Registro y Perfil Fiscal</h4>
            <p>El primer paso consiste en la captura de los <strong>Datos Maestros</strong>. Esta información es la base para la facturación (CFDI 4.0) y la identificación legal del proveedor.</p>
            
            <ul class="fs-13">
                <li><strong>Validación de RFC:</strong> El sistema verifica la estructura del RFC (12 o 13 caracteres) en tiempo real.</li>
                <li><strong>Identidad Multi-tenant:</strong> Cada proveedor debe ser asignado a una empresa del grupo.</li>
                <li><strong>Régimen Fiscal:</strong> Campo condicional basado en el tipo de persona (Física/Moral).</li>
            </ul>

            <div class="alert alert-warning border-0 shadow-sm">
                <h6 class="fw-bold"><i class="ri-error-warning-line me-1"></i> Importante:</h6>
                Asegúrate de seleccionar correctamente si el proveedor es <strong>Nacional</strong> o <strong>Extranjero</strong>, ya que esto determina dinámicamente los documentos que el sistema le exigirá en las siguientes etapas.
            </div>
        </div>
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/supplier_profile.png" 
                     class="img-doc shadow-sm" 
                     alt="Captura de Datos Maestros">
                <p class="text-muted fs-11 mt-2"><i class="ri-zoom-in-line"></i> Haz clic para ampliar</p>
            </div>
        </div>        
    </div>

    <hr class="border-dashed my-5">

    <!-- 2. CICLO DE VIDA (STEPPER) -->
    <div class="row">
        <div class="col-md-7">
            <h4>2. Ciclo de Vida (Stepper de Onboarding)</h4>
            <p>Una vez guardados los datos maestros, se habilita el seguimiento de hitos. El sistema monitorea el avance del registro a través de cuatro etapas:</p>
            <ul class="list-unstyled fs-13">
                <li><i class="ri-checkbox-circle-fill text-success me-2"></i><strong>Registro Inicial:</strong> Perfil fiscal guardado.</li>
                <li><i class="ri-loader-4-line text-warning me-2"></i><strong>Expediente Digital:</strong> Carga de archivos pendiente o en proceso.</li>
                <li><i class="ri-shield-user-line text-info me-2"></i><strong>Validación:</strong> Documentación bajo revisión de Mesa de Control.</li>
                <li><i class="ri-bank-card-line text-muted me-2"></i><strong>Alta en ERP:</strong> Socio activo y listo para recibir Órdenes de Compra.</li>
            </ul>
        </div>
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/onboarding_stepper.png" class="img-doc" alt="Hitos de Onboarding">
            </div>
        </div>
    </div>

    <!-- 3. EXPEDIENTE DIGITAL -->
    <div class="row mt-5">
        <div class="col-md-5 order-2 order-md-1">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/digital_dossier.png" class="img-doc" alt="Workbench de Documentos">
            </div>
        </div>
        <div class="col-md-7 order-1 order-md-2">
            <h4>3. Expediente Digital (Compliance)</h4>
            <p>El sistema exige documentos obligatorios en formato <strong>PDF (Máximo 5MB)</strong>. El progreso de documentación (0-100%) se calcula según el perfil fiscal del proveedor.</p>
            <div class="rule-box">
                <h6 class="fw-bold"><i class="ri-information-line me-1"></i> Mejores Prácticas:</h6>
                <p class="mb-0">Si un documento es rechazado, se habilitará la opción <strong>"Reemplazar"</strong>. El sistema borrará el archivo anterior y notificará a validación que existe una nueva versión para revisar.</p>
            </div>
        </div>
    </div>

    <!-- 4. DATOS BANCARIOS -->
    <div class="row mt-5 mb-4">
        <div class="col-md-7">
            <h4>4. Control Antifraude de Cuentas Bancarias</h4>
            <p>Para garantizar la seguridad en los pagos, toda CLABE (18 dígitos) registrada nace con estatus <strong>PENDIENTE</strong>.</p>
            <ul class="fs-13">
                <li><strong>Cruce de Información:</strong> Tesorería validará la CLABE contra la Carátula Bancaria del expediente.</li>
                <li><strong>Cuentas Internacionales:</strong> Requieren forzosamente códigos <strong>SWIFT/BIC</strong> o <strong>IBAN</strong>.</li>
            </ul>
            <div class="alert alert-danger border-0 shadow-sm">
                <h6 class="fw-bold"><i class="ri-lock-2-line me-1"></i> Bloqueo Preventivo:</h6>
                El sistema de Órdenes de Compra no permitirá seleccionar cuentas que no hayan sido auditadas y aprobadas previamente por el nivel de seguridad correspondiente.
            </div>
        </div>
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/banking_data.png" class="img-doc" alt="Gestión Bancaria">
            </div>
        </div>
    </div>
</section>