<?php

/*
 * ============================================================
 * EMAIL - INICIO DE GESTIÓN DE PEDIDO
 * ============================================================
 *
 * Plantilla:
 * email_distribuidores_inicio_gestion_pedido
 *
 * Descripción:
 * Notifica al distribuidor que su pedido ha comenzado
 * formalmente el proceso de revisión administrativa.
 * ============================================================
 */


/*
 * ============================================================
 * DATOS GENERALES
 * ============================================================
 */

$nombreSolicitante = trim(
    (string)($data['nombre_solicitante'] ?? $data['nombre'] ?? '')
);

if ($nombreSolicitante === '') {
    $nombreSolicitante = 'Estimado distribuidor';
}


$nombreDistribuidor = trim(
    (string)($data['nombre_distribuidor'] ?? '')
);

if ($nombreDistribuidor === '') {
    $nombreDistribuidor = trim(
        (string)($data['razon_social'] ?? '')
    );
}


$folioPedido = trim(
    (string)($data['folio_pedido'] ?? '')
);


$claveDistribuidor = trim(
    (string)(
        $data['clave_distribuidor']
        ?? $data['codigo_cliente']
        ?? ''
    )
);


$estatus = trim(
    (string)($data['estatus_nuevo'] ?? $data['estatus'] ?? 'EN_REVISION')
);


$prioridad = trim(
    (string)($data['prioridad'] ?? '')
);


$fechaPedido = trim(
    (string)($data['fecha_pedido'] ?? '')
);


$fechaRequerida = trim(
    (string)($data['fecha_requerida'] ?? '')
);


$mesFacturacion = trim(
    (string)($data['mes_facturacion_deseado'] ?? '')
);


$totalModelos = intval(
    $data['total_modelos'] ?? 0
);


$totalUnidades = intval(
    $data['total_unidades'] ?? 0
);


$subtotal = floatval(
    $data['subtotal'] ?? 0
);


$iva = floatval(
    $data['iva'] ?? 0
);


$total = floatval(
    $data['total'] ?? 0
);


$observaciones = trim(
    (string)($data['observaciones'] ?? '')
);


$urlPedido = trim(
    (string)($data['url_pedido'] ?? '')
);


/*
 * ============================================================
 * FORMATEAR FECHAS
 * ============================================================
 */

function formatearFechaCorreoPedido($fecha)
{
    if (
        empty($fecha)
        || $fecha === '0000-00-00'
        || $fecha === '0000-00-00 00:00:00'
    ) {
        return 'No especificada';
    }

    $timestamp = strtotime($fecha);

    if ($timestamp === false) {
        return htmlspecialchars($fecha, ENT_QUOTES, 'UTF-8');
    }

    return date('d/m/Y', $timestamp);
}


$fechaPedidoTexto =
    formatearFechaCorreoPedido($fechaPedido);


$fechaRequeridaTexto =
    formatearFechaCorreoPedido($fechaRequerida);


/*
 * ============================================================
 * MES DE FACTURACIÓN
 * ============================================================
 */

$mesFacturacionTexto = 'No especificado';

if ($mesFacturacion !== '') {

    $timestampMes = strtotime(
        $mesFacturacion . '-01'
    );

    if ($timestampMes !== false) {

        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];

        $numeroMes = intval(
            date('n', $timestampMes)
        );

        $anio = date(
            'Y',
            $timestampMes
        );

        $mesFacturacionTexto =
            ($meses[$numeroMes] ?? '')
            . ' '
            . $anio;
    }
}


/*
 * ============================================================
 * ESCAPAR INFORMACIÓN
 * ============================================================
 */

$nombreSolicitanteHtml =
    htmlspecialchars(
        $nombreSolicitante,
        ENT_QUOTES,
        'UTF-8'
    );


$nombreDistribuidorHtml =
    htmlspecialchars(
        $nombreDistribuidor,
        ENT_QUOTES,
        'UTF-8'
    );


$folioPedidoHtml =
    htmlspecialchars(
        $folioPedido,
        ENT_QUOTES,
        'UTF-8'
    );


$claveDistribuidorHtml =
    htmlspecialchars(
        $claveDistribuidor,
        ENT_QUOTES,
        'UTF-8'
    );


$prioridadHtml =
    htmlspecialchars(
        $prioridad,
        ENT_QUOTES,
        'UTF-8'
    );


$urlPedidoHtml =
    htmlspecialchars(
        $urlPedido,
        ENT_QUOTES,
        'UTF-8'
    );

?>

<!DOCTYPE html>

<html lang="es">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0">

    <title>
        Pedido en revisión
    </title>

</head>


<body
    style="
        margin:0;
        padding:0;
        background-color:#f3f4f6;
        font-family:Arial, Helvetica, sans-serif;
        color:#1f2937;
    ">


    <!-- ========================================================
         CONTENEDOR GENERAL
    ========================================================= -->

    <table
        width="100%"
        cellpadding="0"
        cellspacing="0"
        border="0"
        role="presentation"
        style="
            width:100%;
            background-color:#f3f4f6;
            padding:30px 15px;
        ">

        <tr>

            <td align="center">


                <!-- =================================================
                     CONTENEDOR DEL CORREO
                ================================================== -->

                <table
                    width="650"
                    cellpadding="0"
                    cellspacing="0"
                    border="0"
                    role="presentation"
                    style="
                        width:100%;
                        max-width:650px;
                        background-color:#ffffff;
                        border-radius:10px;
                        overflow:hidden;
                        box-shadow:0 2px 8px rgba(0,0,0,0.06);
                    ">


                    <!-- =============================================
                         ENCABEZADO
                    ============================================== -->

                    <tr>

                        <td
                            align="center"
                            style="
                                background-color:#111827;
                                padding:28px 30px;
                            ">

                            <div
                                style="
                                    font-size:22px;
                                    line-height:28px;
                                    font-weight:700;
                                    color:#ffffff;
                                    margin-bottom:6px;
                                ">

                                Portal de Pedidos

                            </div>


                            <div
                                style="
                                    font-size:13px;
                                    line-height:20px;
                                    color:#d1d5db;
                                ">

                                LDR Solutions

                            </div>

                        </td>

                    </tr>


                    <!-- =============================================
                         ESTATUS
                    ============================================== -->

                    <tr>

                        <td
                            align="center"
                            style="
                                padding:32px 35px 12px 35px;
                            ">

                            <div
                                style="
                                    display:inline-block;
                                    background-color:#fff7ed;
                                    color:#c2410c;
                                    border:1px solid #fed7aa;
                                    border-radius:20px;
                                    padding:7px 16px;
                                    font-size:12px;
                                    font-weight:700;
                                    letter-spacing:0.3px;
                                ">

                                PEDIDO EN REVISIÓN

                            </div>

                        </td>

                    </tr>


                    <!-- =============================================
                         TÍTULO
                    ============================================== -->

                    <tr>

                        <td
                            align="center"
                            style="
                                padding:10px 40px 5px 40px;
                            ">

                            <div
                                style="
                                    font-size:24px;
                                    line-height:32px;
                                    font-weight:700;
                                    color:#111827;
                                ">

                                La gestión de tu pedido ha iniciado

                            </div>

                        </td>

                    </tr>


                    <!-- =============================================
                         FOLIO
                    ============================================== -->

                    <tr>

                        <td
                            align="center"
                            style="
                                padding:4px 40px 25px 40px;
                            ">

                            <div
                                style="
                                    font-size:15px;
                                    color:#6b7280;
                                ">

                                Pedido

                                <strong
                                    style="
                                        color:#ea580c;
                                    ">

                                    <?= $folioPedidoHtml; ?>

                                </strong>

                            </div>

                        </td>

                    </tr>


                    <!-- =============================================
                         CONTENIDO
                    ============================================== -->

                    <tr>

                        <td
                            style="
                                padding:0 40px 30px 40px;
                            ">


                            <!-- SALUDO -->

                            <p
                                style="
                                    margin:0 0 18px 0;
                                    font-size:15px;
                                    line-height:24px;
                                    color:#374151;
                                ">

                                Hola
                                <strong>
                                    <?= $nombreSolicitanteHtml; ?>
                                </strong>,

                            </p>


                            <!-- MENSAJE -->

                            <p
                                style="
                                    margin:0 0 18px 0;
                                    font-size:15px;
                                    line-height:24px;
                                    color:#374151;
                                ">

                                Te informamos que el pedido

                                <strong>
                                    <?= $folioPedidoHtml; ?>
                                </strong>

                                <?php if ($nombreDistribuidorHtml !== '') { ?>

                                    correspondiente a

                                    <strong>
                                        <?= $nombreDistribuidorHtml; ?>
                                    </strong>

                                <?php } ?>

                                ha comenzado formalmente su proceso de
                                revisión por parte de nuestro equipo.

                            </p>


                            <p
                                style="
                                    margin:0 0 25px 0;
                                    font-size:15px;
                                    line-height:24px;
                                    color:#374151;
                                ">

                                A partir de este momento, el pedido se
                                encuentra en estatus

                                <strong
                                    style="
                                        color:#c2410c;
                                    ">

                                    EN REVISIÓN

                                </strong>

                                y nuestro equipo continuará con la validación
                                de la solicitud, disponibilidad de unidades y
                                demás información necesaria para continuar
                                con el proceso.

                            </p>


                            <!-- =====================================
                                 AVISO IMPORTANTE
                            ====================================== -->

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                role="presentation"
                                style="
                                    margin-bottom:28px;
                                    background-color:#fff7ed;
                                    border:1px solid #fed7aa;
                                    border-radius:8px;
                                ">

                                <tr>

                                    <td
                                        style="
                                            padding:16px 18px;
                                        ">

                                        <div
                                            style="
                                                font-size:14px;
                                                font-weight:700;
                                                color:#9a3412;
                                                margin-bottom:6px;
                                            ">

                                            Información importante

                                        </div>

                                        <div
                                            style="
                                                font-size:13px;
                                                line-height:21px;
                                                color:#7c2d12;
                                            ">

                                            Debido a que la gestión del pedido
                                            ya ha comenzado, ya no será posible
                                            realizar modificaciones o
                                            cancelaciones desde el Portal de
                                            Pedidos.

                                        </div>

                                    </td>

                                </tr>

                            </table>


                            <!-- =====================================
                                 INFORMACIÓN DEL PEDIDO
                            ====================================== -->

                            <div
                                style="
                                    font-size:16px;
                                    font-weight:700;
                                    color:#111827;
                                    margin-bottom:14px;
                                ">

                                Información del pedido

                            </div>


                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                role="presentation"
                                style="
                                    width:100%;
                                    border:1px solid #e5e7eb;
                                    border-radius:8px;
                                    border-collapse:separate;
                                    border-spacing:0;
                                    overflow:hidden;
                                ">


                                <!-- FOLIO -->

                                <tr>

                                    <td
                                        width="45%"
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Folio del pedido

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:700;
                                            color:#111827;
                                        ">

                                        <?= $folioPedidoHtml; ?>

                                    </td>

                                </tr>


                                <!-- DISTRIBUIDOR -->

                                <?php if ($nombreDistribuidorHtml !== '') { ?>

                                    <tr>

                                        <td
                                            style="
                                                padding:12px 15px;
                                                background-color:#f9fafb;
                                                border-bottom:1px solid #e5e7eb;
                                                font-size:13px;
                                                color:#6b7280;
                                            ">

                                            Distribuidor

                                        </td>

                                        <td
                                            style="
                                                padding:12px 15px;
                                                border-bottom:1px solid #e5e7eb;
                                                font-size:13px;
                                                font-weight:600;
                                                color:#111827;
                                            ">

                                            <?= $nombreDistribuidorHtml; ?>

                                        </td>

                                    </tr>

                                <?php } ?>


                                <!-- CLAVE DISTRIBUIDOR -->

                                <?php if ($claveDistribuidorHtml !== '') { ?>

                                    <tr>

                                        <td
                                            style="
                                                padding:12px 15px;
                                                background-color:#f9fafb;
                                                border-bottom:1px solid #e5e7eb;
                                                font-size:13px;
                                                color:#6b7280;
                                            ">

                                            Clave de distribuidor

                                        </td>

                                        <td
                                            style="
                                                padding:12px 15px;
                                                border-bottom:1px solid #e5e7eb;
                                                font-size:13px;
                                                font-weight:600;
                                                color:#111827;
                                            ">

                                            <?= $claveDistribuidorHtml; ?>

                                        </td>

                                    </tr>

                                <?php } ?>


                                <!-- FECHA PEDIDO -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Fecha del pedido

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:600;
                                            color:#111827;
                                        ">

                                        <?= $fechaPedidoTexto; ?>

                                    </td>

                                </tr>


                                <!-- FECHA REQUERIDA -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Fecha requerida

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:600;
                                            color:#111827;
                                        ">

                                        <?= $fechaRequeridaTexto; ?>

                                    </td>

                                </tr>


                                <!-- MES FACTURACIÓN -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Mes de facturación deseado

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:600;
                                            color:#111827;
                                        ">

                                        <?= htmlspecialchars(
                                            $mesFacturacionTexto,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ); ?>

                                    </td>

                                </tr>


                                <!-- PRIORIDAD -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Prioridad

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:600;
                                            color:#111827;
                                        ">

                                        <?= $prioridadHtml !== ''
                                            ? $prioridadHtml
                                            : 'Normal'; ?>

                                    </td>

                                </tr>


                                <!-- MODELOS -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Modelos solicitados

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:600;
                                            color:#111827;
                                        ">

                                        <?= number_format(
                                            $totalModelos
                                        ); ?>

                                    </td>

                                </tr>


                                <!-- UNIDADES -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Unidades solicitadas

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            border-bottom:1px solid #e5e7eb;
                                            font-size:13px;
                                            font-weight:600;
                                            color:#111827;
                                        ">

                                        <?= number_format(
                                            $totalUnidades
                                        ); ?>

                                    </td>

                                </tr>


                                <!-- ESTATUS -->

                                <tr>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            background-color:#f9fafb;
                                            font-size:13px;
                                            color:#6b7280;
                                        ">

                                        Estatus actual

                                    </td>

                                    <td
                                        style="
                                            padding:12px 15px;
                                            font-size:13px;
                                            font-weight:700;
                                            color:#c2410c;
                                        ">

                                        EN REVISIÓN

                                    </td>

                                </tr>

                            </table>


                            <!-- =====================================
                                 TOTAL
                            ====================================== -->

                            <table
                                width="100%"
                                cellpadding="0"
                                cellspacing="0"
                                border="0"
                                role="presentation"
                                style="
                                    margin-top:20px;
                                    background-color:#f9fafb;
                                    border-radius:8px;
                                ">

                                <tr>

                                    <td
                                        style="
                                            padding:16px 18px;
                                            font-size:14px;
                                            font-weight:600;
                                            color:#374151;
                                        ">

                                        Importe total del pedido

                                    </td>


                                    <td
                                        align="right"
                                        style="
                                            padding:16px 18px;
                                            font-size:18px;
                                            font-weight:700;
                                            color:#111827;
                                        ">

                                        $<?= number_format(
                                            $total,
                                            2
                                        ); ?>

                                        <span
                                            style="
                                                font-size:11px;
                                                color:#6b7280;
                                            ">

                                            MXN

                                        </span>

                                    </td>

                                </tr>

                            </table>


                            <!-- =====================================
                                 OBSERVACIONES
                            ====================================== -->

                            <?php if ($observaciones !== '') { ?>

                                <div
                                    style="
                                        margin-top:25px;
                                    ">

                                    <div
                                        style="
                                            font-size:14px;
                                            font-weight:700;
                                            color:#111827;
                                            margin-bottom:7px;
                                        ">

                                        Observaciones del pedido

                                    </div>

                                    <div
                                        style="
                                            padding:14px 16px;
                                            background-color:#f9fafb;
                                            border-radius:8px;
                                            font-size:13px;
                                            line-height:21px;
                                            color:#4b5563;
                                        ">

                                        <?= nl2br(
                                            htmlspecialchars(
                                                $observaciones,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            )
                                        ); ?>

                                    </div>

                                </div>

                            <?php } ?>


                            <!-- =====================================
                                 SIGUIENTE PASO
                            ====================================== -->

                            <div
                                style="
                                    margin-top:28px;
                                    text-align:center;
                                ">

                                <p
                                    style="
                                        margin:0 0 18px 0;
                                        font-size:14px;
                                        line-height:22px;
                                        color:#4b5563;
                                    ">

                                    Puedes consultar el estado y seguimiento
                                    de tu pedido desde el Portal de Pedidos.

                                </p>


                                <?php if ($urlPedido !== '') { ?>

                                    <table
                                        cellpadding="0"
                                        cellspacing="0"
                                        border="0"
                                        role="presentation"
                                        align="center">

                                        <tr>

                                            <td
                                                align="center"
                                                bgcolor="#ea580c"
                                                style="
                                                    border-radius:6px;
                                                ">

                                                <a
                                                    href="<?= $urlPedidoHtml; ?>"
                                                    target="_blank"
                                                    style="
                                                        display:inline-block;
                                                        padding:13px 26px;
                                                        font-size:14px;
                                                        font-weight:700;
                                                        color:#ffffff;
                                                        text-decoration:none;
                                                    ">

                                                    Consultar pedido

                                                </a>

                                            </td>

                                        </tr>

                                    </table>

                                <?php } ?>

                            </div>


                            <!-- =====================================
                                 MENSAJE FINAL
                            ====================================== -->

                            <p
                                style="
                                    margin:30px 0 0 0;
                                    font-size:13px;
                                    line-height:21px;
                                    color:#6b7280;
                                    text-align:center;
                                ">

                                Te notificaremos cuando existan avances
                                importantes relacionados con la gestión
                                de tu pedido.

                            </p>

                        </td>

                    </tr>


                    <!-- =============================================
                         FOOTER
                    ============================================== -->

                    <tr>

                        <td
                            align="center"
                            style="
                                background-color:#f9fafb;
                                border-top:1px solid #e5e7eb;
                                padding:22px 30px;
                            ">

                            <div
                                style="
                                    font-size:12px;
                                    line-height:19px;
                                    color:#6b7280;
                                ">

                                Este correo fue generado automáticamente
                                por el Portal de Pedidos.

                            </div>


                            <div
                                style="
                                    margin-top:5px;
                                    font-size:12px;
                                    font-weight:600;
                                    color:#374151;
                                ">

                                LDR Solutions

                            </div>

                        </td>

                    </tr>


                </table>

            </td>

        </tr>

    </table>

</body>

</html>