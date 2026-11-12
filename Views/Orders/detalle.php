<?php

headerOrders($data);
?>
<style>
.orders-color-empty{display:flex;gap:16px;align-items:flex-start;padding:20px 22px;border:1px solid #e8c889;border-left:4px solid #d6a341;border-radius:14px;background:linear-gradient(115deg,#fff9e9,#fffdf7);color:#634c20;box-shadow:0 6px 18px rgba(122,93,33,.05);margin:16px 0}
.orders-color-empty__icon{width:46px;height:46px;flex:none;display:grid;place-items:center;border-radius:12px;background:#fff0c7;color:#a06d0b;font-size:24px}
.orders-color-empty__content strong{display:block;font-size:15px;margin:1px 0 6px}
.orders-color-empty__content p{margin:0 0 10px;line-height:1.55;font-size:13px;color:#765f35}
.orders-color-empty__hint{font-size:12px;font-weight:600;color:#986b1d}
@media(max-width:575px){.orders-color-empty{padding:16px;gap:12px}.orders-color-empty__icon{width:40px;height:40px;font-size:20px}}
</style>
<?php





$modo =$_GET['modo'] ?? '';

$pedidoEdicion =$_GET['pedido'] ?? '';

$urlCatalogo = base_url() . '/orders/home';

if ($modo === 'editar' && !empty($pedidoEdicion)) {

    $urlCatalogo .='?modo=editar&pedido='. rawurlencode($pedidoEdicion);
}

$urlCatalogo .='#catalogo';



$unidad = $data['unidad'] ?? null;
$imagenes = $data['imagenes'] ?? [];
$colores = $data['colores'] ?? [];


// dep($imagenes);

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

            <a href="<?= $urlCatalogo ?>">
                Inicio
            </a>

            <span>/</span>

            <a href="<?= $urlCatalogo ?>">
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
                <?php if (!empty($colores)): ?>
                <div class="unit-color-panel">
                  <label for="unidadColorSelect" class="form-label fw-semibold">Color de la unidad</label>
                  <select id="unidadColorSelect" class="form-select unit-color-select" required>
                    
                    <?php foreach ($colores as $c): ?>
                    <option value="<?= (int)$c['idunidad_color'] ?>" data-name="<?= htmlspecialchars($c['nombre_color'], ENT_QUOTES, 'UTF-8') ?>"
                     <?= (int)($c['es_predeterminado'] ?? 0) === 1 || (!array_filter($colores, static fn($color) => (int)($color['es_predeterminado'] ?? 0) === 1) && $c === reset($colores)) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre_color'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                  </select>
                  <small class="unit-color-help">Las fotografías cambian al seleccionar un color.</small>
                </div>
                <?php else: ?>
                <div class="orders-color-empty" role="status" aria-live="polite">
  <span class="orders-color-empty__icon"><i class="ri-palette-line" aria-hidden="true"></i></span>
  <div class="orders-color-empty__content">
    <strong>Colores temporalmente no disponibles</strong>
    <p>Esta unidad todavía no tiene colores configurados. Para garantizar que tu pedido incluya la versión correcta, no es posible agregarla al carrito por el momento.</p>
    <span class="orders-color-empty__hint"><i class="ri-information-line me-1" aria-hidden="true"></i>Consulta con tu asesor comercial para conocer la disponibilidad.</span>
  </div>
</div>
                <?php endif; ?>


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

                                    <article data-color-id="<?= (int)($imagen['idunidad_color'] ?? 0) ?>" class="unit-slide <?= $index === 0
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

                                <button type="button" data-color-id="<?= (int)($imagen['idunidad_color'] ?? 0) ?>" class="unit-thumbnail <?= $index === 0
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


                    <div class="unit-price-block orders-detail-tax-price">
                        <span class="orders-detail-tax-price__label">Precio de venta</span>
                        <strong class="orders-detail-tax-price__amount">
                            $<?= number_format((float) $unidad['precio_dealer'], 2, '.', ','); ?> MXN
                        </strong>
                        <span class="orders-detail-tax-price__included">
                            <i class="ri-checkbox-circle-line" aria-hidden="true"></i>
                            IVA (16 %) incluido
                        </span>
                        <small class="orders-detail-tax-price__base">
                            Precio antes de IVA:
                            $<?= number_format(round((float) $unidad['precio_dealer'] / 1.16, 2), 2, '.', ','); ?> MXN
                        </small>
                        <small class="orders-detail-tax-price__note">Precio sujeto a disponibilidad y condiciones comerciales.</small>
                    </div>


                    <!-- DISPONIBILIDAD -->

                    <!--

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

                    </div>-->


                    <!-- DATOS PRINCIPALES -->

                    <div class="unit-key-data">

                        <div>

                            <span>
                                Segmento
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

                            <button type="button" class="btn btn-primary unit-add-cart" id="btnAddDetailCart" <?= empty($colores) ? 'disabled title="Sin colores disponibles"' : '' ?>>

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
                            <span>Segmento</span>

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

                        <!-- <div>
                            <span>Disponibilidad registrada</span>

                            <strong>
                                <?= intval(
                                    $unidad['stock']
                                ); ?>
                                unidades
                            </strong>
                        </div> -->

                        <div>
                            <span>Precio</span>

                            <strong>
                                $<?= number_format(
                                    floatval(
                                        $unidad['precio_dealer']
                                    ),
                                    2,
                                    '.',
                                    ','
                                ); ?> MXN
                            </strong>
                            <small class="orders-spec-tax-note">IVA (16 %) incluido</small>
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
                'colores' => $colores,
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
                        $unidad['precio_dealer']
                    ),
                'precio_dealer' =>
                    floatval(
                        $unidad['precio_dealer']
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