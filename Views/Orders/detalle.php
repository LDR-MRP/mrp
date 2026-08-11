<?php

headerOrders($data);

$unidad = $data['unidad'] ?? null;
$imagenes = $data['imagenes'] ?? [];

function construirRutaImagenUnidad(
    ?string $ruta
): string {
    $ruta = trim($ruta ?? '');

    if ($ruta === '') {
        return base_url()
            . '/Assets/images/no-image.png';
    }

    if (
        str_starts_with($ruta, 'http://')
        || str_starts_with($ruta, 'https://')
    ) {
        return $ruta;
    }

    return base_url()
        . '/'
        . ltrim($ruta, '/');
}

?>

<main class="unit-detail-page">

    <div class="container">

        <!-- NAVEGACIÓN -->
        <nav class="unit-breadcrumb" aria-label="Navegación">

            <a href="<?= base_url(); ?>/orders/home">
                Inicio
            </a>

            <span>/</span>

            <a href="<?= base_url(); ?>/orders/home#catalogo">
                Catálogo
            </a>

            <?php if (!empty($unidad)): ?>

                <span>/</span>

                <strong>
                    <?= htmlspecialchars(
                        $unidad['nombre'],
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </strong>

            <?php endif; ?>

        </nav>


        <?php if (empty($unidad)): ?>

            <section class="unit-not-found">

                <div class="unit-not-found-icon">
                    <span>!</span>
                </div>

                <h1>
                    Unidad no disponible
                </h1>

                <p>
                    <?= htmlspecialchars(
                        $data['mensaje_error']
                        ?? 'No fue posible localizar la unidad.',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>
                </p>

                <a href="<?= base_url(); ?>/orders/home#catalogo" class="btn btn-primary">

                    Regresar al catálogo
                </a>

            </section>

        <?php else: ?>

            <section class="unit-detail-layout">

                <!-- ==========================================
                     GALERÍA
                =========================================== -->

                <div class="unit-gallery">

                    <div class="unit-slider" id="unitSlider">

                        <div class="unit-slider-track" id="unitSliderTrack">

                            <?php if (!empty($imagenes)): ?>

                                <?php foreach ($imagenes as $index => $imagen): ?>

                                    <?php
                                    $rutaImagen =
                                        construirRutaImagenUnidad(
                                            $imagen['ruta_archivo']
                                            ?? ''
                                        );
                                    ?>

                                    <article class="unit-slide <?= $index === 0
                                        ? 'active'
                                        : ''; ?>" data-index="<?= $index; ?>">

                                        <img src="<?= htmlspecialchars(
                                            $rutaImagen,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>" data-full-image="<?= htmlspecialchars(
                                             $rutaImagen,
                                             ENT_QUOTES,
                                             'UTF-8'
                                         ); ?>" alt="<?= htmlspecialchars(
                                              $imagen['nombre_original']
                                              ?? $unidad['nombre'],
                                              ENT_QUOTES,
                                              'UTF-8'
                                          ); ?>" class="unit-slide-image" loading="<?= $index === 0
                                               ? 'eager'
                                               : 'lazy'; ?>"
                                            onerror="this.onerror=null;this.src='<?= base_url(); ?>/Assets/uploads/unidades_web/img_slider/no-image.png';">

                                        <?php if (
                                            intval(
                                                $imagen['es_principal']
                                                ?? 0
                                            ) === 1
                                        ): ?>

                                            <span class="main-image-badge">
                                                Imagen principal
                                            </span>

                                        <?php endif; ?>

                                        <button type="button" class="unit-zoom-button" data-open-zoom="<?= $index; ?>"
                                            aria-label="Ampliar imagen">

                                            <span aria-hidden="true">
                                                ⛶
                                            </span>

                                            Ampliar
                                        </button>

                                    </article>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <article class="unit-slide active" data-index="0">

                                    <img src="<?= base_url(); ?>/Assets/images/no-image.png"
                                        data-full-image="<?= base_url(); ?>/Assets/images/no-image.png"
                                        alt="Imagen no disponible" class="unit-slide-image">

                                </article>

                            <?php endif; ?>

                        </div>


                        <?php if (count($imagenes) > 1): ?>

                            <button type="button" class="unit-slider-arrow unit-slider-prev" id="unitSliderPrev"
                                aria-label="Imagen anterior">

                                ‹
                            </button>

                            <button type="button" class="unit-slider-arrow unit-slider-next" id="unitSliderNext"
                                aria-label="Imagen siguiente">

                                ›
                            </button>

                            <div class="unit-slider-counter" id="unitSliderCounter">

                                1 / <?= count($imagenes); ?>
                            </div>

                        <?php endif; ?>

                    </div>


                    <!-- MINIATURAS -->

                    <?php if (count($imagenes) > 1): ?>

                        <div class="unit-thumbnails" id="unitThumbnails">

                            <?php foreach ($imagenes as $index => $imagen): ?>

                                <?php
                                $rutaMiniatura =
                                    construirRutaImagenUnidad(
                                        $imagen['ruta_archivo']
                                        ?? ''
                                    );
                                ?>

                                <button type="button" class="unit-thumbnail <?= $index === 0
                                    ? 'active'
                                    : ''; ?>" data-slide-index="<?= $index; ?>"
                                    aria-label="Mostrar imagen <?= $index + 1; ?>">

                                    <img src="<?= htmlspecialchars(
                                        $rutaMiniatura,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>" alt="Vista <?= $index + 1; ?>" loading="lazy" onerror="
                                            this.onerror=null;
                                            this.src='<?= base_url(); ?>/Assets/images/no-image.png';
                                        ">

                                </button>

                            <?php endforeach; ?>

                        </div>

                    <?php endif; ?>

                </div>


                <!-- ==========================================
                     INFORMACIÓN
                =========================================== -->

                <div class="unit-detail-info">

                    <div class="unit-detail-heading">

                        <div class="unit-detail-tags">

                            <?php if (!empty($unidad['marca'])): ?>

                                <span class="unit-brand-tag">
                                    <?= htmlspecialchars(
                                        $unidad['marca'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ); ?>
                                </span>

                            <?php endif; ?>

                            <?php if (!empty($unidad['anio'])): ?>

                                <span class="unit-year-tag">
                                    Modelo <?= intval(
                                        $unidad['anio']
                                    ); ?>
                                </span>

                            <?php endif; ?>

                        </div>

                        <h1>
                            <?= htmlspecialchars(
                                $unidad['nombre'],
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>
                        </h1>

                        <?php if (!empty($unidad['version'])): ?>

                            <p class="unit-version">
                                <?= htmlspecialchars(
                                    $unidad['version'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </p>

                        <?php endif; ?>

                    </div>


                    <div class="unit-price-block">

                        <span>
                            Precio estimado
                        </span>

                        <strong>
                            $<?= number_format(
                                floatval(
                                    $unidad['precio_estimado']
                                ),
                                2,
                                '.',
                                ','
                            ); ?> MXN
                        </strong>

                        <small>
                            Precio sujeto a disponibilidad,
                            condiciones comerciales e impuestos aplicables.
                        </small>

                    </div>


                    <!-- DISPONIBILIDAD -->

                    <div class="unit-availability <?= intval(
                        $unidad['stock']
                    ) > 0
                        ? 'available'
                        : 'unavailable'; ?>">

                        <div class="availability-icon">
                            <?= intval(
                                $unidad['stock']
                            ) > 0
                                ? '✓'
                                : '!'; ?>
                        </div>

                        <div>

                            <strong>
                                <?= intval(
                                    $unidad['stock']
                                ) > 0
                                    ? 'Unidad disponible'
                                    : 'Sin disponibilidad inmediata'; ?>
                            </strong>

                            <span>
                                <?php if (
                                    intval(
                                        $unidad['stock']
                                    ) > 0
                                ): ?>

                                    <?= intval(
                                        $unidad['stock']
                                    ); ?>
                                    unidades registradas en disponibilidad.

                                <?php else: ?>

                                    Puedes agregarla a tu solicitud para
                                    revisión o posible Back Order.

                                <?php endif; ?>
                            </span>

                        </div>

                    </div>


                    <!-- DATOS PRINCIPALES -->

                    <div class="unit-key-data">

                        <div>

                            <span>
                                Marca
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['marca']
                                    ?: 'No especificada',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>

                        </div>

                        <div>

                            <span>
                                Modelo
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['modelo']
                                    ?: 'No especificado',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>

                        </div>

                        <div>

                            <span>
                                Año
                            </span>

                            <strong>
                                <?= !empty($unidad['anio'])
                                    ? intval($unidad['anio'])
                                    : 'No especificado'; ?>
                            </strong>

                        </div>

                        <div>

                            <span>
                                Motor
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['motor']
                                    ?: 'Consultar',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>

                        </div>

                    </div>


                    <!-- CANTIDAD Y CARRITO -->

                    <div class="unit-purchase-box">

                        <label for="detailQuantity">
                            Cantidad solicitada
                        </label>

                        <div class="unit-purchase-controls">

                            <div class="detail-quantity-control">

                                <button type="button" id="detailQuantityMinus" aria-label="Disminuir cantidad">

                                    <span aria-hidden="true">
                                        −
                                    </span>
                                </button>

                                <input type="number" id="detailQuantity" min="1" step="1" value="1"
                                    aria-label="Cantidad de unidades">

                                <button type="button" id="detailQuantityPlus" aria-label="Aumentar cantidad">

                                    <span aria-hidden="true">
                                        +
                                    </span>
                                </button>

                            </div>

                            <button type="button" class="btn btn-primary unit-add-cart" id="btnAddDetailCart">

                                Agregar al carrito
                            </button>

                        </div>

                        <a href="<?= base_url(); ?>/orders/carrito" class="unit-view-cart">

                            Ver carrito actual
                        </a>

                    </div>

                </div>

            </section>


            <!-- ==============================================
                 DESCRIPCIÓN Y ESPECIFICACIONES
            =============================================== -->

            <section class="unit-information-section">

                <div class="unit-description-card">

                    <span class="unit-section-label">
                        Descripción
                    </span>

                    <h2>
                        Conoce esta unidad
                    </h2>

                    <p>
                        <?= nl2br(
                            htmlspecialchars(
                                $unidad['descripcion']
                                ?: 'No se ha registrado una descripción para esta unidad.',
                                ENT_QUOTES,
                                'UTF-8'
                            )
                        ); ?>
                    </p>

                </div>


                <div class="unit-specifications-card">

                    <div class="unit-specifications-header">

                        <div>

                            <span class="unit-section-label">
                                Información técnica
                            </span>

                            <h2>
                                Especificaciones generales
                            </h2>

                        </div>

                    </div>

                    <div class="unit-specifications-table">

                        <div>
                            <span>Clave del modelo</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['clave_modelo']
                                    ?: 'No especificada',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Marca</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['marca']
                                    ?: 'No especificada',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Modelo</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['modelo']
                                    ?: 'No especificado',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Versión</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['version']
                                    ?: 'No especificada',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Año</span>

                            <strong>
                                <?= !empty($unidad['anio'])
                                    ? intval($unidad['anio'])
                                    : 'No especificado'; ?>
                            </strong>
                        </div>

                        <div>
                            <span>Motor</span>

                            <strong>
                                <?= htmlspecialchars(
                                    $unidad['motor']
                                    ?: 'No especificado',
                                    ENT_QUOTES,
                                    'UTF-8'
                                ); ?>
                            </strong>
                        </div>

                        <div>
                            <span>Disponibilidad registrada</span>

                            <strong>
                                <?= intval(
                                    $unidad['stock']
                                ); ?>
                                unidades
                            </strong>
                        </div>

                        <div>
                            <span>Precio estimado</span>

                            <strong>
                                $<?= number_format(
                                    floatval(
                                        $unidad['precio_estimado']
                                    ),
                                    2,
                                    '.',
                                    ','
                                ); ?> MXN
                            </strong>
                        </div>

                    </div>

                </div>

            </section>

        <?php endif; ?>

    </div>

</main>


<!-- =========================================================
     VISOR DE IMAGEN AMPLIADA
========================================================== -->

<?php if (!empty($unidad)): ?>

    <div class="unit-zoom-modal" id="unitZoomModal" aria-hidden="true">

        <div class="unit-zoom-backdrop" data-close-zoom>
        </div>

        <div class="unit-zoom-dialog" role="dialog" aria-modal="true" aria-label="Vista ampliada de la unidad">

            <button type="button" class="unit-zoom-close" id="unitZoomClose" aria-label="Cerrar imagen ampliada">

                ×
            </button>

            <button type="button" class="unit-zoom-arrow unit-zoom-prev" id="unitZoomPrev" aria-label="Imagen anterior">

                ‹
            </button>

            <div class="unit-zoom-image-container">

                <img src="" alt="<?= htmlspecialchars(
                    $unidad['nombre'],
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>" id="unitZoomImage">

            </div>

            <button type="button" class="unit-zoom-arrow unit-zoom-next" id="unitZoomNext" aria-label="Imagen siguiente">

                ›
            </button>

        </div>

    </div>


    <script>
        window.UNIDAD_DETALLE = <?= json_encode(
            [
                'id' => intval(
                    $unidad['idunidad']
                ),
                'idunidad' => intval(
                    $unidad['idunidad']
                ),
                'modelo' =>
                    $unidad['modelo'],
                'clave_modelo' =>
                    $unidad['clave_modelo'],
                'nombre' =>
                    $unidad['nombre'],
                'version' =>
                    $unidad['version'],
                'marca' =>
                    $unidad['marca'],
                'anio' =>
                    intval(
                        $unidad['anio']
                    ),
                'motor' =>
                    $unidad['motor'],
                'stock' =>
                    intval(
                        $unidad['stock']
                    ),
                'precio' =>
                    floatval(
                        $unidad['precio_estimado']
                    ),
                'precio_estimado' =>
                    floatval(
                        $unidad['precio_estimado']
                    ),
                'img' =>
                    !empty($imagenes)
                    ? construirRutaImagenUnidad(
                        $imagenes[0]['ruta_archivo']
                        ?? ''
                    )
                    : construirRutaImagenUnidad(
                        $unidad['imagen_caratula']
                    ),
                'desc' =>
                    $unidad['descripcion'],
                'imagenes' =>
                    array_map(
                        function ($imagen) {
                    return construirRutaImagenUnidad(
                        $imagen['ruta_archivo']
                        ?? ''
                    );
                },
                        $imagenes
                    )
            ],
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_APOS
            | JSON_HEX_AMP
            | JSON_HEX_QUOT
        ); ?>;
    </script>

<?php endif; ?>

<?php
footerOrders($data);
?>