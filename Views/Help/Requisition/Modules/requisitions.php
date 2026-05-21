<section id="requisitions" class="doc-section">
    <h2><i class="ri-file-list-3-line align-middle me-2"></i>Gestión de Requisiciones</h2>
    <p>El módulo de requisiciones es el punto de partida de la cadena de suministro. Permite a cualquier colaborador solicitar materiales o servicios, asegurando que cada petición pase por un proceso de revisión y costeo antes de convertirse en una compra real.</p>

    <!-- 1. MONITORIZACIÓN Y ESTADOS -->
    <div class="row mt-5">
        <div class="col-md-7">
            <h4>1. Dashboard y Trazabilidad de Estados</h4>
            <p>El listado permite filtrar y gestionar el ciclo de vida de cada solicitud mediante indicadores visuales dinámicos:</p>
            <ul class="fs-13">
                <li><span class="badge bg-soft-warning text-warning">Pendiente:</span> Esperando firma de Visto Bueno (L1) o Autorización (L2).</li>
                <li><span class="badge bg-soft-success text-success">Aprobada:</span> Firma completada. Lista para ser procesada por Compras.</li>
                <li><span class="badge bg-soft-info text-info">En Compra:</span> Existe una Orden de Compra (OC) activa vinculada a esta solicitud.</li>
                <li><span class="badge bg-success text-white">Finalizada:</span> Todo el material solicitado ha ingresado físicamente al almacén.</li>
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
            
            <h4>2.1 Modalidad de Compra</h4>
            <p>El sistema ofrece una nueva modalidad de procesamiento:</p>
            
            <div class="card bg-light border-0 shadow-none mb-3">
                <div class="card-body">
                    <h6 class="fw-bold text-primary"><i class="ri-flashlight-fill me-1"></i> Spot Buy (Pago Inmediato)</h6>
                    <p class="small mb-0">Para compras en Amazon, Mercado Libre o servicios liquidados con <b>el Medio de Pago Seleccionado</b>. Al activarse, el sistema automatiza la generación de la OC y la entrada de almacén tras la aprobación final.</p>
                </div>
            </div>
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
            <div class="alert alert-warning border-0 shadow-sm">
                <h6 class="fw-bold"><i class="ri-shield-keyhole-line me-1"></i> Seguridad y Permisos:</h6>
                Solo el dueño de la requisición puede editarla en estado <b>Borrador</b>. Una vez enviada a aprobación, el documento queda bloqueado para garantizar que la firma se aplique sobre datos inalterables.
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