<?php

headerOrders($data);

$pedido =
    $data['pedido']
    ?? null;

$detalles =
    $data['detalles']
    ?? [];

$sucursales =
    $data['sucursales']
    ?? []; 

function rutaImagenPedido(
    ?string $ruta
): string {

    $ruta =
        trim(
            $ruta ?? ''
        );

    if ($ruta === '') {

        return base_url()
            . '/Assets/images/no-image.png';
    }

    if (
        str_starts_with(
            $ruta,
            'http://'
        )
        || str_starts_with(
            $ruta,
            'https://'
        )
    ) {

        return $ruta;
    }

    return base_url()
        . '/'
        . ltrim(
            $ruta,
            '/'
        );
}

?>

<main class="edit-order-page">

    <div class="container">


        <?php if (
            empty(
                $pedido
            )
        ): ?>

            <section class="edit-order-error">

                <h1>
                    Pedido no editable
                </h1>

                <p>

                    <?= htmlspecialchars(
                        $data[
                            'mensaje_error'
                        ]
                        ?? 'No es posible editar este pedido.',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>

                </p>

                <a
                    href="<?= base_url(); ?>/orders/micuenta"
                    class="btn btn-primary"
                >

                    Regresar a mis pedidos

                </a>

            </section>


        <?php else: ?>


            <!-- ENCABEZADO -->

            <div class="edit-order-heading">

                <div>

                    <a
                        href="<?= base_url(); ?>/orders/micuenta"
                        class="edit-order-back"
                    >

                        ← Mis pedidos

                    </a>


                    <span>
                        Editar solicitud
                    </span>


                    <h1>

                        <?= htmlspecialchars(
                            $pedido[
                                'folio_pedido'
                            ],
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>

                    </h1>


                    <p>

                        Puedes realizar cambios mientras
                        el pedido permanezca en estado
                        <strong>Pendiente</strong>.

                    </p>

                </div>


                <div class="edit-order-status">

                    <span>
                        Estatus
                    </span>

                    <strong>
                        Pendiente
                    </strong>

                    <small>

                        Versión
                        <?= intval(
                            $pedido[
                                'version'
                            ]
                            ?? 1
                        ); ?>

                    </small>

                </div>

            </div>


            <div class="edit-order-layout">


                <!-- UNIDADES -->

                <section class="edit-order-products">

                    <div class="edit-products-header">

                        <div>

                            <span>
                                Unidades
                            </span>

                            <h2>
                                Modelos incluidos
                            </h2>

                        </div>

<a
    href="<?= base_url(); ?>/orders/home?modo=editar&pedido=<?= rawurlencode(
        $pedido['clave']
    ); ?>#catalogo"
    class="btn btn-outline"
>
    + Agregar más unidades
</a>

                    </div>


                    <div id="editOrderItems">

                        <?php foreach (
                            $detalles
                            as $detalle
                        ): ?>

                            <?php
                            $imagen =
                                rutaImagenPedido(
                                    $detalle[
                                        'imagen_caratula'
                                    ]
                                    ?? ''
                                );
                            ?>
<article
    class="edit-order-item"
    data-detail-id="<?= intval(
        $detalle[
            'idpedido_detalle'
        ]
    ); ?>"
    data-unit-id="<?= intval(
        $detalle[
            'idunidad'
        ]
    ); ?>"
    data-price="<?= floatval(
        $detalle[
            'precio_unitario'
        ]
        ?? $detalle[
            'precio_estimado'
        ]
        ?? 0
    ); ?>"
>

                                <div class="edit-item-image">

                                    <img
                                        src="<?= htmlspecialchars(
                                            $imagen,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                        alt="<?= htmlspecialchars(
                                            $detalle[
                                                'nombre'
                                            ],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>"
                                    >

                                </div>


                                <div class="edit-item-content">

                                    <div class="edit-item-heading">

                                        <div>

                                            <span>

                                                <?= htmlspecialchars(
                                                    $detalle[
                                                        'marca'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </span>

                                            <h3>

                                                <?= htmlspecialchars(
                                                    $detalle[
                                                        'nombre'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </h3>

                                            <p>

                                                <?= htmlspecialchars(
                                                    $detalle[
                                                        'version_unidad'
                                                    ],
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ); ?>

                                            </p>

                                        </div>


                                        <button
                                            type="button"
                                            class="edit-item-remove"
                                            data-remove-detail 
                                        >

                                            Eliminar

                                        </button>

                                    </div>


                                    <div class="edit-item-grid">


                                        <!-- CANTIDAD -->

                                        <div>

                                            <label>
                                                Cantidad
                                            </label>

                                            <div class="edit-quantity-control">

                                                <button
                                                    type="button"
                                                    data-qty-minus
                                                >
                                                    −
                                                </button>


                                                <input
                                                    type="number"
                                                    min="1"
                                                    step="1"
                                                    value="<?= intval(
                                                        $detalle[
                                                            'cantidad_solicitada'
                                                        ]
                                                    ); ?>"
                                                    data-qty
                                                >


                                                <button
                                                    type="button"
                                                    data-qty-plus
                                                >
                                                    +
                                                </button>

                                            </div>

                                        </div>


                                        <!-- ENTREGA -->

                                        <div>

                                            <label>
                                                Tipo de entrega
                                            </label>

                                            <select
                                                data-delivery-type
                                            >

                                                <option
                                                    value="SUCURSAL"
                                                    <?= strtoupper(
                                                        $detalle[
                                                            'tipo_entrega'
                                                        ]
                                                    )
                                                    === 'SUCURSAL'
                                                        ? 'selected'
                                                        : ''; ?>
                                                >

                                                    Sucursal

                                                </option>

                                                <option
                                                    value="OTRA_DIRECCION"
                                                    <?= strtoupper(
                                                        $detalle[
                                                            'tipo_entrega'
                                                        ]
                                                    )
                                                    === 'OTRA_DIRECCION'
                                                        ? 'selected'
                                                        : ''; ?>
                                                >

                                                    Otra dirección

                                                </option>

                                            </select>

                                        </div>


                                        <!-- SUCURSAL -->

                                        <div
                                            data-sucursal-wrapper
                                        >

                                            <label>
                                                Sucursal
                                            </label>

                                            <select
                                                data-sucursal
                                            >

                                                <option value="">
                                                    Selecciona
                                                </option>

                                                <?php foreach (
                                                    $sucursales
                                                    as $sucursal
                                                ): ?>

                                                    <option
                                                        value="<?= intval(
                                                            $sucursal[
                                                                'idsucursal'
                                                            ]
                                                        ); ?>"
                                                        <?= intval(
                                                            $detalle[
                                                                'idsucursal_entrega'
                                                            ]
                                                            ?? 0
                                                        )
                                                        === intval(
                                                            $sucursal[
                                                                'idsucursal'
                                                            ]
                                                        )
                                                            ? 'selected'
                                                            : ''; ?>
                                                    >

                                                        <?= htmlspecialchars(
                                                            $sucursal[
                                                                'nombre_sucursal'
                                                            ],
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ); ?>

                                                    </option>

                                                <?php endforeach; ?>

                                            </select>

                                        </div>


                                        <!-- OTRA DIRECCIÓN -->

                                        <div
                                            data-address-wrapper
                                        >

                                            <label>
                                                Dirección
                                            </label>

                                            <textarea
                                                rows="3"
                                                data-address
                                                placeholder="Indica la dirección completa"
                                            ><?= htmlspecialchars(
                                                $detalle[
                                                    'direccion_entrega'
                                                ]
                                                ?? '',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ); ?></textarea>

                                        </div>

                                    </div>


                               <?php

$precioUnitario =
    floatval(
        $detalle[
            'precio_unitario'
        ]
        ?? $detalle[
            'precio_estimado'
        ]
        ?? 0
    );

$cantidadSolicitada =
    intval(
        $detalle[
            'cantidad_solicitada'
        ]
        ?? 1
    );

$importeEstimado =
    $precioUnitario
    * $cantidadSolicitada;

?>

<div class="edit-item-financial">

    <div class="edit-item-financial-value">

        <span>
            Precio unitario
        </span>

        <strong>

            $<?= number_format( $precioUnitario,2,'.',',' ); ?>

        </strong>

    </div>


    <div class="edit-item-financial-value">

        <span>
            Importe estimado
        </span>

        <strong data-line-total>

            $<?= number_format($importeEstimado,2,'.',','); ?>

        </strong>

    </div>

</div>

                                </div>

                            </article>

                        <?php endforeach; ?>

                    </div>

                </section>


                <!-- RESUMEN -->

                <aside class="edit-order-summary">

                    <h2>
                        Información del pedido
                    </h2>


                    <div class="edit-form-group">

                        <label for="editRequiredDate">
                            Fecha requerida
                        </label>

                        <input
                            type="date"
                            id="editRequiredDate"
                            value="<?= htmlspecialchars(
                                substr(
                                    $pedido[
                                        'fecha_requerida'
                                    ] ?? '',
                                    0,
                                    10
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >

                    </div>


                    <div class="edit-form-group">

                        <label for="editBillingMonth">
                            Mes deseado de facturación
                        </label>

                        <input
                            type="month"
                            id="editBillingMonth"
                            value="<?= htmlspecialchars(
                                substr(
                                    $pedido[
                                        'mes_facturacion_deseado'
                                    ] ?? '',
                                    0,
                                    7
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ); ?>"
                        >

                    </div>


                    <div class="edit-form-group">

                        <label for="editPriority">
                            Prioridad
                        </label>

                        <select id="editPriority">

                            <option
                                value="NORMAL"
                                <?= strtoupper(
                                    $pedido[
                                        'prioridad'
                                    ]
                                )
                                === 'NORMAL'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Normal
                            </option>

                            <option
                                value="ALTA"
                                <?= strtoupper(
                                    $pedido[
                                        'prioridad'
                                    ]
                                )
                                === 'ALTA'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Alta
                            </option>

                            <option
                                value="URGENTE"
                                <?= strtoupper(
                                    $pedido[
                                        'prioridad'
                                    ]
                                )
                                === 'URGENTE'
                                    ? 'selected'
                                    : ''; ?>
                            >
                                Urgente
                            </option>

                        </select>

                    </div>


                    <div class="edit-form-group">

                        <label for="editObservations">
                            Observaciones
                        </label>

                        <textarea
                            id="editObservations"
                            rows="5"
                            placeholder="Comentarios adicionales..."
                        ><?= htmlspecialchars(
                            $pedido[
                                'observaciones'
                            ]
                            ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?></textarea>

                    </div>


                    <div class="edit-summary-values">

                        <div>
                            <span>Modelos</span>
                            <strong id="editTotalModels">0</strong>
                        </div>

                        <div>
                            <span>Unidades</span>
                            <strong id="editTotalUnits">0</strong>
                        </div>

                        <div>
                            <span>Subtotal</span>
                            <strong id="editSubtotal">$0.00</strong>
                        </div>

                        <div>
                            <span>IVA</span>
                            <strong id="editIva">$0.00</strong>
                        </div>

                        <div class="edit-summary-total">
                            <span>Total estimado</span>
                            <strong id="editTotal">$0.00</strong>
                        </div>

                    </div>


                    <div class="edit-order-warning">

                        Una vez que Administración inicie
                        la revisión del pedido, ya no
                        será posible modificarlo.

                    </div>


                    <button
                        type="button"
                        class="btn btn-primary btn-full"
                        id="btnSaveOrderChanges"
                    >

                        Guardar cambios

                    </button>


                    <a
                        href="<?= base_url(); ?>/orders/micuenta"
                        class="btn btn-outline btn-full"
                    >

                        Cancelar edición

                    </a>

                </aside>

            </div>

        <?php endif; ?>

    </div>

</main>


<?php if (
    !empty(
        $pedido
    )
): ?>

<script>

window.EDIT_ORDER = <?= json_encode(
    [
        'clave' =>
            $pedido[
                'clave'
            ],

        'folio' =>
            $pedido[
                'folio_pedido'
            ],

        'sucursales' =>
            array_map(
                function ($sucursal) {

                    return [
                        'idsucursal' =>
                            intval(
                                $sucursal[
                                    'idsucursal'
                                ]
                            ),

                        'nombre' =>
                            $sucursal[
                                'nombre_sucursal'
                            ]
                    ];
                },
                $sucursales
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