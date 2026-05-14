<?php headerAdmin($data); ?>
<style>
    /* Estilo para las miniaturas en el manual */
    .img-doc-wrapper {
        position: relative;
        cursor: zoom-in;
        margin: 20px 0;
        display: inline-block;
        width: 100%;
    }

    .img-doc {
        width: 100%;
        max-width: 400px; /* Tamaño de la miniatura */
        border-radius: 8px;
        border: 1px solid #e9ebec;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .img-doc:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }

    /* El Overlay del Zoom (Full Screen) */
    #doc-zoom-overlay {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.9); /* Azul muy oscuro profundo */
        display: none;
        z-index: 9999;
        cursor: zoom-out;
        align-items: center;
        justify-content: center;
        padding: 40px;
    }

    #doc-zoom-overlay img {
        max-width: 90%;
        max-height: 90%;
        border-radius: 8px;
        box-shadow: 0 0 30px rgba(0,0,0,0.5);
        animation: zoomAnim 0.3s ease-out;
    }

    @keyframes zoomAnim {
        from { transform: scale(0.8); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }
</style>
<div class="main-content">
    <div class="page-content">
        <div class="container-fluid">
            <div class="row">
                <!-- NAVEGACIÓN IZQUIERDA (Estática) -->
                <div class="col-lg-3">
                    <div class="card help-card sticky-top" style="top: 90px;">
                        <div class="card-body p-4">
                            <h6 class="text-uppercase fw-bold text-muted fs-11 ls-1 mb-3">Módulos del Sistema</h6>
                            <nav class="nav flex-column nav-docs">
                                <a class="nav-link active" href="#purchases">Compras</a>
                            </nav>
                        </div>
                    </div>
                </div>

                <!-- CONTENIDO DINÁMICO (Inyectando Partials) -->
                <div class="col-lg-9">
                    <div class="card help-card">
                        <div class="card-body p-5 doc-content">
                            
                            <?php 
                                // Inyectamos cada módulo por separado
                                require_once 'Modules/purchases.php';
                            ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal de Zoom Global para el Manual -->
    <div id="doc-zoom-overlay">
        <img src="" alt="Zoomed Image">
    </div>
</div>
<?php footerAdmin($data); ?>