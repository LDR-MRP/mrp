<section id="requisitions" class="doc-section">
    <h2><i class="ri-file-list-3-line align-middle me-2"></i>Gestión de Requisiciones</h2>
    <p>El módulo de requisiciones es el punto de partida de la cadena de suministro. Permite a cualquier colaborador solicitar materiales o servicios, asegurando que cada petición pase por un proceso de revisión y costeo antes de convertirse en una compra real.</p>

    <!-- 1. MONITORIZACIÓN Y ESTADOS -->
    <div class="row mt-5">
        <div class="col-md-7">
            <h4>1. Panel de Control (Dashboard)</h4>
            <p>Desde el listado principal, los usuarios pueden monitorear el estado global de sus solicitudes mediante indicadores de desempeño (KPIs):</p>
            <ul class="fs-13">
                <li><span class="badge bg-soft-warning text-warning">Pendientes:</span> Solicitudes esperando firma de Jefatura.</li>
                <li><span class="badge bg-soft-success text-success">Listas para Compra:</span> Requisiciones aprobadas que ya pueden ser procesadas por el área de Adquisiciones.</li>
                <li><span class="badge bg-soft-info text-info">En Proceso:</span> Artículos que ya cuentan con una Orden de Compra vinculada.</li>
            </ul>
        </div>
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/req_index.png" class="img-doc" alt="Listado de Requisiciones">
                <p class="text-muted fs-11 mt-2"><i class="ri-zoom-in-line"></i> Vista de monitorización y KPIs</p>
            </div>
        </div>
    </div>

    <hr class="border-dashed my-5">

    <!-- 2. CREACIÓN Y CAPTURA -->
    <div class="row">
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/req_create.png" class="img-doc" alt="Formulario de Creación">
            </div>
        </div>
        <div class="col-md-7">
            <h4>2. Elaboración de la Solicitud</h4>
            <p>Al crear una nueva solicitud, es fundamental completar los campos marcados con un asterisco rojo (<span class="text-danger-asterisk">*</span>).</p>
            <div class="rule-box">
                <h6 class="fw-bold"><i class="ri-shopping-basket-2-line me-1"></i> Selección de Artículos:</h6>
                <p class="mb-0 fs-13">El sistema permite buscar productos directamente en el <strong>Catálogo Maestro</strong> por SKU o descripción. Si el artículo es nuevo o no está catalogado, debe utilizarse la función de <strong>Sourcing Especial</strong>.</p>
            </div>
            <p class="fs-13">Una vez finalizada la carga, el usuario tiene dos opciones principales en el panel de acciones:</p>
            <ul class="fs-13">
                <li><strong>Guardar Borrador:</strong> Permite pausar la captura para continuar después. La solicitud no es visible para los jefes.</li>
                <li><strong>Enviar a Aprobación:</strong> Bloquea la edición y notifica al jefe de departamento para su revisión legal y técnica.</li>
            </ul>
        </div>
    </div>

    <hr class="border-dashed my-5">

    <!-- 3. EXPEDIENTE Y SURTIDO -->
    <div class="row mb-4">
        <div class="col-md-7">
            <h4>3. Expediente Digital y Seguimiento de Surtido</h4>
            <p>La vista de lectura (Dossier) ofrece una visión 360° de la solicitud. El componente más importante es el <strong>Indicador de Surtido Real</strong>.</p>
            <ul class="fs-13">
                <li><strong>Barra de Progreso:</strong> Muestra visualmente qué porcentaje de la cantidad solicitada ha llegado físicamente al almacén.</li>
                <li><strong>Trazabilidad:</strong> En la barra lateral se listan todas las Órdenes de Compra generadas a partir de esta requisición, permitiendo navegar entre documentos relacionados.</li>
            </ul>
            <div class="alert alert-info border-0 shadow-sm">
                <h6 class="fw-bold"><i class="ri-information-line me-1"></i> Nota de Auditoría:</h6>
                Todas las acciones (aprobaciones, rechazos o ediciones) quedan registradas con fecha, hora y usuario responsable en el log de auditoría del sistema.
            </div>
        </div>
        <div class="col-md-5">
            <div class="text-center">
                <img src="<?= media(); ?>/images/help/req_read.png" class="img-doc" alt="Expediente de Requisición">
                <p class="text-muted fs-11 mt-2"><i class="ri-zoom-in-line"></i> Detalle de partidas y progreso</p>
            </div>
        </div>
    </div>
</section>