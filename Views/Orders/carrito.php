<?php
headerOrders($data);

$idclientePortal = (int) (
    $_SESSION['portal_idcliente']
    ?? 0
);

$idusuarioAccesoPortal = (int) (
    $_SESSION['portal_idusuario_acceso']
    ?? 0
);
?>

<main class="cart-page">
    <div class="container">

        <!-- ENCABEZADO -->
        <div class="section-title left">
            <span>Solicitud de pedido</span>

            <h2>
                Unidades agregadas al carrito
            </h2>

            <p>
                Revisa las unidades seleccionadas, ajusta cantidades
                y define el destino de entrega para cada modelo.
            </p>
        </div>

        <!-- BARRA SUPERIOR -->
        <div class="cart-toolbar">
            <div>
                <strong id="cartToolbarModels">
                    0 modelos
                </strong>

                <span id="cartToolbarUnits">
                    0 unidades seleccionadas
                </span>
            </div>

            <a
                href="<?= base_url(); ?>/orders/home#catalogo"
                class="btn btn-outline"
            >
                <i class="ri-add-line"></i>
                Agregar más unidades
            </a>
        </div>

        <div class="cart-layout">

            <!-- LISTADO -->
            <section
                class="cart-items"
                id="cartItems"
            >
                <div class="cart-loading-state">
                    <span class="spinner-border"></span>

                    <p>
                        Cargando carrito...
                    </p>
                </div>
            </section>

            <!-- RESUMEN -->
            <aside class="cart-summary">

                <div class="summary-header">
                    <div>
                        <span>Resumen</span>

                        <h3>
                            Solicitud de pedido
                        </h3>
                    </div>

                    <i class="ri-file-list-3-line"></i>
                </div>

                <div class="summary-block">

                    <div class="summary-row">
                        <span>Total modelos</span>

                        <strong id="totalModels">
                            0
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>Total unidades</span>

                        <strong id="totalUnits">
                            0
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal estimado</span>

                        <strong id="subtotalEstimated">
                            $0.00
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>IVA estimado</span>

                        <strong id="ivaEstimated">
                            $0.00
                        </strong>
                    </div>

                    <div class="summary-row summary-row-total">
                        <span>Total estimado</span>

                        <strong id="totalEstimated">
                            $0.00
                        </strong>
                    </div>

                </div>

                <hr>

                <!-- FECHA REQUERIDA -->
                <div class="form-group">
                    <label for="orderRequiredDate">
                        Fecha requerida
                    </label>

                    <input
                        type="date"
                        id="orderRequiredDate"
                        class="form-control"
                    >

                    <small class="form-help">
                        Fecha aproximada en la que necesitas las unidades.
                    </small>
                </div>

                <!-- MES DESEADO -->
                <div class="form-group">
                    <label for="orderMonth">
                        Mes deseado de facturación
                    </label>

                    <input
                        type="month"
                        id="orderMonth"
                        class="form-control"
                    >
                </div>

                <!-- PRIORIDAD -->
                <div class="form-group">
                    <label for="orderPriority">
                        Prioridad de la solicitud
                    </label>

                    <select
                        id="orderPriority"
                        class="form-control"
                    >
                        <option value="NORMAL">
                            Normal
                        </option>

                        <option value="ALTA">
                            Alta
                        </option>

                        <option value="URGENTE">
                            Urgente
                        </option>
                    </select>
                </div>

                <!-- NOTAS -->
                <div class="form-group">
                    <label for="orderNotes">
                        Notas del pedido
                    </label>

                    <textarea
                        id="orderNotes"
                        class="form-control"
                        rows="5"
                        maxlength="1000"
                        placeholder="Ejemplo: Prioridad alta, colores preferentes, indicaciones generales..."
                    ></textarea>

                    <small class="form-help">
                        Máximo 1,000 caracteres.
                    </small>
                </div>

                <div class="summary-alert">
                    <i class="ri-information-line"></i>

                    <div>
                        <strong>
                            Importante
                        </strong>

                        <p>
                            Debes seleccionar una sucursal o escribir una
                            dirección de entrega para cada modelo agregado.
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    class="btn btn-primary full"
                    id="btnGenerateRequest"
                >
                    <i class="ri-send-plane-fill"></i>
                    Generar solicitud de pedido
                </button>

                <a
                    href="<?= base_url(); ?>/orders/home#catalogo"
                    class="btn btn-outline full"
                >
                    <i class="ri-add-circle-line"></i>
                    Agregar más unidades
                </a>

            </aside>

        </div>
    </div>
</main>

<script>
    window.ordersPortal = {
        idcliente: <?= $idclientePortal; ?>,
        idusuarioAcceso: <?= $idusuarioAccesoPortal; ?>,
        baseUrl: <?= json_encode(
            base_url(),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
        ); ?>
    };
</script>

<?php
footerOrders($data);
?>