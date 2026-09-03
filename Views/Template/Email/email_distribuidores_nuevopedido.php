<!doctype html>
<html lang="es">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="x-apple-disable-message-reformatting"
    >

    <title>
        Nuevo pedido generado
    </title>


    <style>

        html,
        body {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #f4f6f8;
            font-family: Arial, Helvetica, sans-serif;
        }


        * {
            -ms-text-size-adjust: 100%;
            -webkit-text-size-adjust: 100%;
        }


        table,
        td {
            mso-table-lspace: 0pt !important;
            mso-table-rspace: 0pt !important;
            border-collapse: collapse !important;
        }


        table {
            border-spacing: 0 !important;
        }


        img {
            border: 0;
            outline: none;
            text-decoration: none;
            display: block;
        }


        a {
            text-decoration: none;
        }


        @media screen and (max-width: 640px) {

            .container {
                width: 100% !important;
            }


            .px {
                padding-left: 14px !important;
                padding-right: 14px !important;
            }


            .header-column {
                display: block !important;
                width: 100% !important;
                text-align: left !important;
            }


            .header-date {
                padding-top: 14px !important;
                text-align: left !important;
            }


            .summary-cell {
                display: block !important;
                width: 100% !important;
                border-right: 0 !important;
                border-bottom: 1px solid #e5e7eb !important;
                box-sizing: border-box !important;
            }


            .summary-cell:last-child {
                border-bottom: 0 !important;
            }


            .detail-label,
            .detail-value {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }


            .detail-label {
                border-bottom: 0 !important;
                padding-bottom: 4px !important;
            }


            .detail-value {
                padding-top: 4px !important;
            }


            .product-table-head {
                display: none !important;
            }


            .product-row,
            .product-row td {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }


            .product-row {
                border-bottom: 1px solid #e5e7eb !important;
            }


            .product-row td {
                border-bottom: 0 !important;
                padding: 7px 12px !important;
            }


            .product-label-mobile {
                display: inline !important;
            }


            .btn a {
                display: block !important;
                width: 100% !important;
                box-sizing: border-box !important;
            }

        }

    </style>

</head>


<body>


<?php

/*
 * ============================================================
 * VARIABLES GENERALES
 * ============================================================
 */

$nombreUsuario = htmlspecialchars(
    $data['nombre_usuario']
    ?? 'Usuario',
    ENT_QUOTES,
    'UTF-8'
);


$emailUsuario = htmlspecialchars(
    $data['email']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$folioPedido = htmlspecialchars(
    $data['folio_pedido']
    ?? $data['folioPedido']
    ?? 'Sin folio',
    ENT_QUOTES,
    'UTF-8'
);


$clavePedido = htmlspecialchars(
    $data['clavePedido']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$estatus = htmlspecialchars(
    $data['estatus']
    ?? 'PENDIENTE',
    ENT_QUOTES,
    'UTF-8'
);


$prioridad = htmlspecialchars(
    $data['prioridad']
    ?? 'NORMAL',
    ENT_QUOTES,
    'UTF-8'
);


$fechaPedido = htmlspecialchars(
    $data['fechaPedido']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$fechaRequerida = htmlspecialchars(
    $data['fechaRequerida']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$mesFacturacion = htmlspecialchars(
    $data['mesFacturacion']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$fechaHora = htmlspecialchars(
    $data['fechaHora']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$distribuidor = htmlspecialchars(
    $data['distribuidor']
    ?? $data['nombre_comercial']
    ?? $data['razon_social']
    ?? 'Distribuidor',
    ENT_QUOTES,
    'UTF-8'
);


$razonSocial = htmlspecialchars(
    $data['razon_social']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$codigoCliente = htmlspecialchars(
    $data['codigo_cliente']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$claveDistribuidor = htmlspecialchars(
    $data['clave_distribuidor']
    ?? '',
    ENT_QUOTES,
    'UTF-8'
);


$totalModelos = intval(
    $data['totalModelos']
    ?? 0
);


$totalUnidades = intval(
    $data['totalUnidades']
    ?? 0
);


$subtotal = floatval(
    $data['subtotal']
    ?? 0
);


$descuento = floatval(
    $data['descuento']
    ?? 0
);


$iva = floatval(
    $data['iva']
    ?? 0
);


$total = floatval(
    $data['total']
    ?? 0
);


$observaciones = trim(
    $data['observaciones']
    ?? ''
);


$urlPedido = htmlspecialchars(
    $data['url_pedido']
    ?? $data['url_recovery']
    ?? '#',
    ENT_QUOTES,
    'UTF-8'
);


$detalles = (
    isset(
        $data['detalles']
    )
    && is_array(
        $data['detalles']
    )
)
    ? $data['detalles']
    : [];


/*
 * ============================================================
 * FORMATEADORES
 * ============================================================
 */

function formatMoneyPedidoEmail(
    $importe
) {

    return '$'
        . number_format(
            floatval(
                $importe
            ),
            2,
            '.',
            ','
        )
        . ' MXN';
}


/*
 * ============================================================
 * COLOR / TEXTO DE PRIORIDAD
 * ============================================================
 */

$prioridadUpper = strtoupper(
    $prioridad
);


$prioridadColor =
    '#475467';


$prioridadBg =
    '#f2f4f7';


if (
    $prioridadUpper
    === 'ALTA'
) {

    $prioridadColor =
        '#b54708';

    $prioridadBg =
        '#fffaeb';

} elseif (
    $prioridadUpper
    === 'URGENTE'
) {

    $prioridadColor =
        '#b42318';

    $prioridadBg =
        '#fef3f2';
}

?>


<!--
    TEXTO PREVIO / PREHEADER
-->
<div
    style="
        display:none;
        font-size:1px;
        line-height:1px;
        max-height:0;
        max-width:0;
        opacity:0;
        overflow:hidden;
    "
>

    Tu solicitud de pedido
    <?= $folioPedido ?>
    fue registrada correctamente.

</div>



<table
    role="presentation"
    width="100%"
    style="
        width:100%;
        background:#f4f6f8;
    "
>

    <tr>

        <td
            align="center"
            style="
                padding:28px 12px;
            "
        >


            <table
                role="presentation"
                class="container"
                width="640"
                style="
                    width:640px;
                    max-width:640px;
                "
            >


                <tr>

                    <td
                        class="px"
                        style="
                            padding:0 20px;
                        "
                    >


                        <table
                            role="presentation"
                            width="100%"
                            style="
                                width:100%;
                                background:#ffffff;
                                border-radius:16px;
                                overflow:hidden;
                                box-shadow:0 8px 24px rgba(16,24,40,.08);
                            "
                        >


                            <!-- ===================================================
                                 BARRA SUPERIOR
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        height:7px;
                                        line-height:7px;
                                        font-size:0;
                                        background:#1f1f1f;
                                    "
                                >
                                    &nbsp;
                                </td>

                            </tr>



                            <!-- ===================================================
                                 HEADER
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:22px 24px 16px;
                                    "
                                >

                                    <table
                                        role="presentation"
                                        width="100%"
                                    >

                                        <tr>


                                            <td
                                                class="header-column"
                                                align="left"
                                                style="
                                                    vertical-align:middle;
                                                "
                                            >


                                                <table
                                                    role="presentation"
                                                >

                                                    <tr>


                                                        <td
                                                            style="
                                                                padding-right:14px;
                                                                vertical-align:middle;
                                                            "
                                                        >

                                                            <!--
                                                                LOGO

                                                                Si ya tienes el logo dentro del mismo
                                                                dominio del portal, reemplaza la URL
                                                                por la definitiva.
                                                            -->

                                                            <img
                                                                src="https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png"
                                                                width="95"
                                                                alt="LDR Solutions"
                                                                style="
                                                                    width:95px;
                                                                    max-width:95px;
                                                                    height:auto;
                                                                "
                                                            >

                                                        </td>


                                                        <td
                                                            style="
                                                                vertical-align:middle;
                                                            "
                                                        >

                                                            <div
                                                                style="
                                                                    font-size:12px;
                                                                    line-height:1.4;
                                                                    color:#667085;
                                                                "
                                                            >
                                                                Portal de Pedidos
                                                            </div>


                                                            <div
                                                                style="
                                                                    margin-top:5px;
                                                                    font-size:20px;
                                                                    line-height:1.25;
                                                                    font-weight:800;
                                                                    color:#101828;
                                                                "
                                                            >
                                                                Pedido registrado
                                                            </div>

                                                        </td>


                                                    </tr>

                                                </table>


                                            </td>


                                            <td
                                                class="header-column header-date"
                                                align="right"
                                                style="
                                                    vertical-align:middle;
                                                    white-space:nowrap;
                                                "
                                            >

                                                <div
                                                    style="
                                                        font-size:11px;
                                                        color:#98a2b3;
                                                    "
                                                >
                                                    Notificación
                                                </div>


                                                <div
                                                    style="
                                                        margin-top:4px;
                                                        font-size:12px;
                                                        font-weight:700;
                                                        color:#344054;
                                                    "
                                                >
                                                    <?= $fechaHora ?>
                                                </div>

                                            </td>


                                        </tr>

                                    </table>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 MENSAJE PRINCIPAL
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:8px 24px 18px;
                                    "
                                >

                                    <div
                                        style="
                                            font-size:14px;
                                            line-height:1.7;
                                            color:#344054;
                                        "
                                    >

                                        Estimado(a)
                                        <strong
                                            style="
                                                color:#101828;
                                            "
                                        >
                                            <?= $nombreUsuario ?>
                                        </strong>,

                                        <br><br>


                                        Tu solicitud de pedido ha sido
                                        <strong
                                            style="
                                                color:#067647;
                                            "
                                        >
                                            registrada correctamente
                                        </strong>
                                        en el Portal de Pedidos.


                                        <br><br>


                                        El pedido fue creado con estatus
                                        <strong>
                                            pendiente
                                        </strong>
                                        y podrá continuar con su proceso de revisión,
                                        autorización y atención correspondiente.


                                    </div>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 RESUMEN PRINCIPAL
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:0 24px 18px;
                                    "
                                >


                                    <table
                                        role="presentation"
                                        width="100%"
                                        style="
                                            border:1px solid #d1fadf;
                                            border-radius:12px;
                                            overflow:hidden;
                                            background:#f6fef9;
                                        "
                                    >

                                        <tr>


                                            <td
                                                class="summary-cell"
                                                width="33.33%"
                                                style="
                                                    width:33.33%;
                                                    padding:15px;
                                                    border-right:1px solid #d1fadf;
                                                    vertical-align:top;
                                                "
                                            >

                                                <div
                                                    style="
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Estado
                                                </div>


                                                <div
                                                    style="
                                                        margin-top:5px;
                                                        font-size:13px;
                                                        font-weight:800;
                                                        color:#067647;
                                                    "
                                                >
                                                    ● <?= $estatus ?>
                                                </div>

                                            </td>


                                            <td
                                                class="summary-cell"
                                                width="33.33%"
                                                style="
                                                    width:33.33%;
                                                    padding:15px;
                                                    border-right:1px solid #d1fadf;
                                                    vertical-align:top;
                                                "
                                            >

                                                <div
                                                    style="
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Folio
                                                </div>


                                                <div
                                                    style="
                                                        margin-top:5px;
                                                        font-size:13px;
                                                        font-weight:800;
                                                        color:#101828;
                                                    "
                                                >
                                                    <?= $folioPedido ?>
                                                </div>

                                            </td>


                                            <td
                                                class="summary-cell"
                                                width="33.33%"
                                                style="
                                                    width:33.33%;
                                                    padding:15px;
                                                    vertical-align:top;
                                                "
                                            >

                                                <div
                                                    style="
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Prioridad
                                                </div>


                                                <div
                                                    style="
                                                        display:inline-block;
                                                        margin-top:5px;
                                                        padding:4px 8px;
                                                        border-radius:999px;
                                                        background:<?= $prioridadBg ?>;
                                                        color:<?= $prioridadColor ?>;
                                                        font-size:11px;
                                                        font-weight:800;
                                                    "
                                                >
                                                    <?= $prioridad ?>
                                                </div>

                                            </td>


                                        </tr>

                                    </table>


                                </td>

                            </tr>



                            <!-- ===================================================
                                 INFORMACIÓN DEL DISTRIBUIDOR
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:2px 24px 10px;
                                    "
                                >

                                    <div
                                        style="
                                            margin-bottom:10px;
                                            font-size:14px;
                                            font-weight:800;
                                            color:#101828;
                                        "
                                    >
                                        Información del distribuidor
                                    </div>


                                    <table
                                        role="presentation"
                                        width="100%"
                                        style="
                                            border:1px solid #e4e7ec;
                                            border-radius:12px;
                                            overflow:hidden;
                                        "
                                    >


                                        <tr>

                                            <td
                                                class="detail-label"
                                                width="38%"
                                                style="
                                                    width:38%;
                                                    padding:11px 14px;
                                                    background:#f9fafb;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:11px;
                                                    color:#667085;
                                                "
                                            >
                                                Distribuidor
                                            </td>


                                            <td
                                                class="detail-value"
                                                style="
                                                    padding:11px 14px;
                                                    background:#ffffff;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:13px;
                                                    font-weight:800;
                                                    color:#101828;
                                                "
                                            >
                                                <?= $distribuidor ?>
                                            </td>

                                        </tr>


                                        <?php if ($razonSocial !== ''): ?>

                                            <tr>

                                                <td
                                                    class="detail-label"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#f9fafb;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Razón social
                                                </td>


                                                <td
                                                    class="detail-value"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#ffffff;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:13px;
                                                        color:#344054;
                                                    "
                                                >
                                                    <?= $razonSocial ?>
                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                        <?php if ($codigoCliente !== ''): ?>

                                            <tr>

                                                <td
                                                    class="detail-label"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#f9fafb;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Código de cliente
                                                </td>


                                                <td
                                                    class="detail-value"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#ffffff;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:13px;
                                                        color:#344054;
                                                    "
                                                >
                                                    <?= $codigoCliente ?>
                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                        <?php if ($claveDistribuidor !== ''): ?>

                                            <tr>

                                                <td
                                                    class="detail-label"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#f9fafb;
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Clave distribuidor
                                                </td>


                                                <td
                                                    class="detail-value"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#ffffff;
                                                        font-size:13px;
                                                        font-weight:700;
                                                        color:#344054;
                                                    "
                                                >
                                                    <?= $claveDistribuidor ?>
                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                    </table>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 INFORMACIÓN DEL PEDIDO
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:12px 24px 10px;
                                    "
                                >

                                    <div
                                        style="
                                            margin-bottom:10px;
                                            font-size:14px;
                                            font-weight:800;
                                            color:#101828;
                                        "
                                    >
                                        Información del pedido
                                    </div>


                                    <table
                                        role="presentation"
                                        width="100%"
                                        style="
                                            border:1px solid #e4e7ec;
                                            border-radius:12px;
                                            overflow:hidden;
                                        "
                                    >


                                        <tr>

                                            <td
                                                class="detail-label"
                                                width="38%"
                                                style="
                                                    width:38%;
                                                    padding:11px 14px;
                                                    background:#f9fafb;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:11px;
                                                    color:#667085;
                                                "
                                            >
                                                Folio
                                            </td>


                                            <td
                                                class="detail-value"
                                                style="
                                                    padding:11px 14px;
                                                    background:#ffffff;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:13px;
                                                    font-weight:800;
                                                    color:#101828;
                                                "
                                            >
                                                <?= $folioPedido ?>
                                            </td>

                                        </tr>


                                        <tr>

                                            <td
                                                class="detail-label"
                                                style="
                                                    padding:11px 14px;
                                                    background:#f9fafb;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:11px;
                                                    color:#667085;
                                                "
                                            >
                                                Solicitante
                                            </td>


                                            <td
                                                class="detail-value"
                                                style="
                                                    padding:11px 14px;
                                                    background:#ffffff;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:13px;
                                                    font-weight:700;
                                                    color:#344054;
                                                "
                                            >
                                                <?= $nombreUsuario ?>
                                            </td>

                                        </tr>


                                        <?php if ($emailUsuario !== ''): ?>

                                            <tr>

                                                <td
                                                    class="detail-label"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#f9fafb;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Correo
                                                </td>


                                                <td
                                                    class="detail-value"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#ffffff;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:13px;
                                                        color:#344054;
                                                    "
                                                >
                                                    <?= $emailUsuario ?>
                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                        <tr>

                                            <td
                                                class="detail-label"
                                                style="
                                                    padding:11px 14px;
                                                    background:#f9fafb;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:11px;
                                                    color:#667085;
                                                "
                                            >
                                                Fecha del pedido
                                            </td>


                                            <td
                                                class="detail-value"
                                                style="
                                                    padding:11px 14px;
                                                    background:#ffffff;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:13px;
                                                    color:#344054;
                                                "
                                            >
                                                <?= $fechaPedido ?>
                                            </td>

                                        </tr>


                                        <tr>

                                            <td
                                                class="detail-label"
                                                style="
                                                    padding:11px 14px;
                                                    background:#f9fafb;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:11px;
                                                    color:#667085;
                                                "
                                            >
                                                Fecha requerida
                                            </td>


                                            <td
                                                class="detail-value"
                                                style="
                                                    padding:11px 14px;
                                                    background:#ffffff;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:13px;
                                                    font-weight:700;
                                                    color:#101828;
                                                "
                                            >
                                                <?= $fechaRequerida ?>
                                            </td>

                                        </tr>


                                        <?php if ($mesFacturacion !== ''): ?>

                                            <tr>

                                                <td
                                                    class="detail-label"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#f9fafb;
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Mes de facturación deseado
                                                </td>


                                                <td
                                                    class="detail-value"
                                                    style="
                                                        padding:11px 14px;
                                                        background:#ffffff;
                                                        font-size:13px;
                                                        color:#344054;
                                                    "
                                                >
                                                    <?= $mesFacturacion ?>
                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                    </table>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 RESUMEN DE CANTIDADES
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:12px 24px 10px;
                                    "
                                >


                                    <table
                                        role="presentation"
                                        width="100%"
                                        style="
                                            border:1px solid #e4e7ec;
                                            border-radius:12px;
                                            overflow:hidden;
                                            background:#fcfcfd;
                                        "
                                    >

                                        <tr>


                                            <td
                                                class="summary-cell"
                                                width="50%"
                                                style="
                                                    width:50%;
                                                    padding:14px;
                                                    border-right:1px solid #e4e7ec;
                                                "
                                            >

                                                <div
                                                    style="
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Modelos solicitados
                                                </div>


                                                <div
                                                    style="
                                                        margin-top:4px;
                                                        font-size:19px;
                                                        font-weight:800;
                                                        color:#101828;
                                                    "
                                                >
                                                    <?= $totalModelos ?>
                                                </div>

                                            </td>


                                            <td
                                                class="summary-cell"
                                                width="50%"
                                                style="
                                                    width:50%;
                                                    padding:14px;
                                                "
                                            >

                                                <div
                                                    style="
                                                        font-size:11px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Total de unidades
                                                </div>


                                                <div
                                                    style="
                                                        margin-top:4px;
                                                        font-size:19px;
                                                        font-weight:800;
                                                        color:#101828;
                                                    "
                                                >
                                                    <?= $totalUnidades ?>
                                                </div>

                                            </td>


                                        </tr>

                                    </table>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 DETALLES DE UNIDADES
                            ==================================================== -->

                            <?php if (!empty($detalles)): ?>

                                <tr>

                                    <td
                                        style="
                                            padding:14px 24px 10px;
                                        "
                                    >

                                        <div
                                            style="
                                                margin-bottom:10px;
                                                font-size:14px;
                                                font-weight:800;
                                                color:#101828;
                                            "
                                        >
                                            Unidades solicitadas
                                        </div>


                                        <table
                                            role="presentation"
                                            width="100%"
                                            style="
                                                border:1px solid #e4e7ec;
                                                border-radius:12px;
                                                overflow:hidden;
                                            "
                                        >


                                            <tr
                                                class="product-table-head"
                                                style="
                                                    background:#f9fafb;
                                                "
                                            >

                                                <td
                                                    style="
                                                        padding:10px 11px;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:10px;
                                                        font-weight:800;
                                                        color:#667085;
                                                    "
                                                >
                                                    MODELO
                                                </td>


                                                <td
                                                    align="center"
                                                    style="
                                                        padding:10px 8px;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:10px;
                                                        font-weight:800;
                                                        color:#667085;
                                                    "
                                                >
                                                    CANT.
                                                </td>


                                                <td
                                                    align="right"
                                                    style="
                                                        padding:10px 8px;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:10px;
                                                        font-weight:800;
                                                        color:#667085;
                                                    "
                                                >
                                                    PRECIO UNIT.
                                                </td>


                                                <td
                                                    align="right"
                                                    style="
                                                        padding:10px 11px;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:10px;
                                                        font-weight:800;
                                                        color:#667085;
                                                    "
                                                >
                                                    IMPORTE
                                                </td>

                                            </tr>


                                            <?php foreach ($detalles as $detalle): ?>


                                                <?php

                                                $nombreDetalle = htmlspecialchars(
                                                    $detalle['nombre']
                                                    ?? 'Unidad',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );


                                                $cantidadDetalle = intval(
                                                    $detalle['cantidad']
                                                    ?? 0
                                                );


                                                $precioUnitarioDetalle = floatval(
                                                    $detalle['precio_unitario']
                                                    ?? 0
                                                );


                                                $subtotalDetalle = floatval(
                                                    $detalle['subtotal']
                                                    ?? 0
                                                );


                                                $tipoEntregaDetalle = htmlspecialchars(
                                                    $detalle['tipo_entrega']
                                                    ?? '',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );


                                                $destinoDetalle = htmlspecialchars(
                                                    $detalle['destino']
                                                    ?? 'No especificado',
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                );

                                                ?>


                                                <tr
                                                    class="product-row"
                                                >


                                                    <td
                                                        style="
                                                            padding:12px 11px;
                                                            border-bottom:1px solid #e4e7ec;
                                                            vertical-align:top;
                                                        "
                                                    >

                                                        <div
                                                            style="
                                                                font-size:12px;
                                                                font-weight:800;
                                                                color:#101828;
                                                                line-height:1.4;
                                                            "
                                                        >
                                                            <?= $nombreDetalle ?>
                                                        </div>


                                                        <div
                                                            style="
                                                                margin-top:5px;
                                                                font-size:10px;
                                                                line-height:1.5;
                                                                color:#667085;
                                                            "
                                                        >

                                                            Entrega:
                                                            <strong>
                                                                <?= $tipoEntregaDetalle ?>
                                                            </strong>

                                                            <br>

                                                            <?= $destinoDetalle ?>

                                                        </div>

                                                    </td>


                                                    <td
                                                        align="center"
                                                        style="
                                                            padding:12px 8px;
                                                            border-bottom:1px solid #e4e7ec;
                                                            vertical-align:top;
                                                            font-size:12px;
                                                            font-weight:800;
                                                            color:#101828;
                                                        "
                                                    >

                                                        <span
                                                            class="product-label-mobile"
                                                            style="display:none;"
                                                        >
                                                            Cantidad:
                                                        </span>

                                                        <?= $cantidadDetalle ?>

                                                    </td>


                                                    <td
                                                        align="right"
                                                        style="
                                                            padding:12px 8px;
                                                            border-bottom:1px solid #e4e7ec;
                                                            vertical-align:top;
                                                            font-size:11px;
                                                            color:#344054;
                                                        "
                                                    >

                                                        <span
                                                            class="product-label-mobile"
                                                            style="display:none;"
                                                        >
                                                            Precio unitario:
                                                        </span>

                                                        <?= formatMoneyPedidoEmail(
                                                            $precioUnitarioDetalle
                                                        ) ?>

                                                    </td>


                                                    <td
                                                        align="right"
                                                        style="
                                                            padding:12px 11px;
                                                            border-bottom:1px solid #e4e7ec;
                                                            vertical-align:top;
                                                            font-size:11px;
                                                            font-weight:800;
                                                            color:#101828;
                                                        "
                                                    >

                                                        <span
                                                            class="product-label-mobile"
                                                            style="display:none;"
                                                        >
                                                            Importe:
                                                        </span>

                                                        <?= formatMoneyPedidoEmail(
                                                            $subtotalDetalle
                                                        ) ?>

                                                    </td>


                                                </tr>


                                            <?php endforeach; ?>


                                        </table>

                                    </td>

                                </tr>

                            <?php endif; ?>



                            <!-- ===================================================
                                 TOTALES
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:14px 24px 10px;
                                    "
                                >

                                    <table
                                        role="presentation"
                                        width="100%"
                                        style="
                                            background:#f9fafb;
                                            border:1px solid #e4e7ec;
                                            border-radius:12px;
                                            overflow:hidden;
                                        "
                                    >


                                        <tr>

                                            <td
                                                style="
                                                    padding:11px 14px;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:12px;
                                                    color:#667085;
                                                "
                                            >
                                                Subtotal
                                            </td>


                                            <td
                                                align="right"
                                                style="
                                                    padding:11px 14px;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:12px;
                                                    font-weight:700;
                                                    color:#344054;
                                                "
                                            >
                                                <?= formatMoneyPedidoEmail(
                                                    $subtotal
                                                ) ?>
                                            </td>

                                        </tr>


                                        <?php if ($descuento > 0): ?>

                                            <tr>

                                                <td
                                                    style="
                                                        padding:11px 14px;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:12px;
                                                        color:#667085;
                                                    "
                                                >
                                                    Descuento
                                                </td>


                                                <td
                                                    align="right"
                                                    style="
                                                        padding:11px 14px;
                                                        border-bottom:1px solid #e4e7ec;
                                                        font-size:12px;
                                                        font-weight:700;
                                                        color:#344054;
                                                    "
                                                >
                                                    -
                                                    <?= formatMoneyPedidoEmail(
                                                        $descuento
                                                    ) ?>
                                                </td>

                                            </tr>

                                        <?php endif; ?>


                                        <tr>

                                            <td
                                                style="
                                                    padding:11px 14px;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:12px;
                                                    color:#667085;
                                                "
                                            >
                                                IVA
                                            </td>


                                            <td
                                                align="right"
                                                style="
                                                    padding:11px 14px;
                                                    border-bottom:1px solid #e4e7ec;
                                                    font-size:12px;
                                                    font-weight:700;
                                                    color:#344054;
                                                "
                                            >
                                                <?= formatMoneyPedidoEmail(
                                                    $iva
                                                ) ?>
                                            </td>

                                        </tr>


                                        <tr>

                                            <td
                                                style="
                                                    padding:14px;
                                                    font-size:14px;
                                                    font-weight:800;
                                                    color:#101828;
                                                "
                                            >
                                                Total estimado
                                            </td>


                                            <td
                                                align="right"
                                                style="
                                                    padding:14px;
                                                    font-size:17px;
                                                    font-weight:900;
                                                    color:#f97316;
                                                "
                                            >
                                                <?= formatMoneyPedidoEmail(
                                                    $total
                                                ) ?>
                                            </td>

                                        </tr>


                                    </table>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 OBSERVACIONES
                            ==================================================== -->

                            <?php if ($observaciones !== ''): ?>

                                <tr>

                                    <td
                                        style="
                                            padding:14px 24px 8px;
                                        "
                                    >

                                        <div
                                            style="
                                                margin-bottom:8px;
                                                font-size:13px;
                                                font-weight:800;
                                                color:#101828;
                                            "
                                        >
                                            Observaciones
                                        </div>


                                        <div
                                            style="
                                                padding:13px 14px;
                                                background:#fffcf5;
                                                border:1px solid #fedf89;
                                                border-radius:10px;
                                                font-size:12px;
                                                line-height:1.65;
                                                color:#475467;
                                            "
                                        >
                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $observaciones,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                )
                                            ) ?>
                                        </div>

                                    </td>

                                </tr>

                            <?php endif; ?>



                            <!-- ===================================================
                                 BOTÓN
                            ==================================================== -->

                            <tr>

                                <td
                                    align="center"
                                    style="
                                        padding:22px 24px 14px;
                                    "
                                >

                                    <div
                                        style="
                                            margin-bottom:13px;
                                            font-size:12px;
                                            line-height:1.6;
                                            color:#667085;
                                        "
                                    >
                                        Puedes consultar el detalle y seguimiento
                                        de tu pedido desde el Portal de Pedidos.
                                    </div>


                                    <table
                                        role="presentation"
                                        class="btn"
                                        style="
                                            margin:0 auto;
                                        "
                                    >

                                        <tr>

                                            <td
                                                align="center"
                                                bgcolor="#111827"
                                                style="
                                                    border-radius:10px;
                                                "
                                            >

                                                <a
                                                    href="<?= $urlPedido ?>"
                                                    target="_blank"
                                                    style="
                                                        display:inline-block;
                                                        padding:13px 24px;
                                                        font-size:13px;
                                                        font-weight:800;
                                                        color:#ffffff;
                                                    "
                                                >
                                                    Consultar pedido
                                                </a>

                                            </td>

                                        </tr>

                                    </table>


                                    <div
                                        style="
                                            margin-top:13px;
                                            font-size:10px;
                                            line-height:1.5;
                                            color:#98a2b3;
                                        "
                                    >

                                        Si el botón no funciona, copia y pega
                                        el siguiente enlace en tu navegador:

                                        <br><br>

                                        <span
                                            style="
                                                word-break:break-all;
                                                color:#667085;
                                            "
                                        >
                                            <?= $urlPedido ?>
                                        </span>

                                    </div>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 MENSAJE INFORMATIVO
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:8px 24px 18px;
                                    "
                                >

                                    <div
                                        style="
                                            padding:12px 14px;
                                            background:#f8fafc;
                                            border-radius:10px;
                                            font-size:11px;
                                            line-height:1.6;
                                            color:#667085;
                                        "
                                    >
                                        Los importes mostrados corresponden al
                                        valor estimado registrado al momento de
                                        generar la solicitud. El pedido podrá
                                        continuar con los procesos de revisión,
                                        autorización y atención correspondientes.
                                    </div>

                                </td>

                            </tr>



                            <!-- ===================================================
                                 FOOTER
                            ==================================================== -->

                            <tr>

                                <td
                                    style="
                                        padding:0 24px 22px;
                                    "
                                >

                                    <hr
                                        style="
                                            border:none;
                                            border-top:1px solid #e4e7ec;
                                            margin:0 0 16px;
                                        "
                                    >


                                    <div
                                        style="
                                            text-align:center;
                                            font-size:10px;
                                            line-height:1.6;
                                            color:#98a2b3;
                                        "
                                    >

                                        <strong
                                            style="
                                                color:#667085;
                                            "
                                        >
                                            Portal de Pedidos
                                        </strong>

                                        <br>

                                        LDR Solutions


                                        <?php if (defined('WEB_EMPRESA')): ?>

                                            <br><br>

                                            <a
                                                href="<?= WEB_EMPRESA ?>"
                                                target="_blank"
                                                style="
                                                    color:#667085;
                                                    font-weight:700;
                                                "
                                            >
                                                <?= WEB_EMPRESA ?>
                                            </a>

                                        <?php endif; ?>


                                        <br><br>


                                        <strong
                                            style="
                                                color:#667085;
                                            "
                                        >
                                            Este es un mensaje automático.
                                            Por favor, no respondas a este correo.
                                        </strong>

                                    </div>

                                </td>

                            </tr>


                        </table>


                    </td>

                </tr>


            </table>


        </td>

    </tr>

</table>


</body>

</html>