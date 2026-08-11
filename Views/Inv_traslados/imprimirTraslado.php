<?php

$traslado = $data['traslado'];
$detalle = $data['detalle'];
$trasladista = $data['trasladista'];

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte de Traslado</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            color:#222;
            background:#fff;
        }

        .contenedor{
            width:900px;
            margin:auto;
            border:2px solid #444;
            padding:25px;
        }

        .logo{
            text-align:center;
            margin-bottom:10px;
        }

        .logo img{
            width:180px;
        }

        .titulo{
            text-align:center;
            font-size:42px;
            font-weight:bold;
            margin-top:10px;
        }

        .subtitulo{
            text-align:center;
            letter-spacing:4px;
            font-size:16px;
            color:#444;
            margin-bottom:25px;
        }

        .bloque{
            margin-top:20px;
        }

        .encabezado{
            background:#ececec;
            border:1px solid #bbb;
            padding:12px;
            font-weight:bold;
            font-size:24px;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        td{
            border:1px solid #ccc;
            padding:12px;
        }

        .label{
            width:40%;
            font-weight:bold;
            background:#fafafa;
        }

        .valor{
            font-weight:bold;
        }

        .estado{
            font-size:24px;
            font-weight:bold;
            text-align:center;
        }

        .tabla-unidades th{
            background:#ececec;
            border:1px solid #ccc;
            padding:10px;
        }

        .tabla-unidades td{
            text-align:center;
        }

        .qr{
            text-align:center;
            margin-top:20px;
        }

        .qr-box{
            width:180px;
            height:180px;
            border:1px solid #000;
            margin:auto;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .firmas{
            margin-top:40px;
        }

        .firma{
            width:45%;
            display:inline-block;
            text-align:center;
        }

        .linea{
            border-top:1px solid #000;
            margin-top:60px;
            margin-bottom:10px;
        }

        .nota{
            text-align:center;
            font-size:11px;
            color:#666;
            margin-top:25px;
            font-style:italic;
        }

        @media print{
            body{
                margin:0;
            }

            .contenedor{
                border:none;
                width:100%;
            }
        }
    </style>
</head>

<body>

<div class="contenedor">

    <!-- LOGO -->

    <div class="logo">

        <!-- Cambia la ruta -->
        <img src="<?= media(); ?>/minimal/images/logo_ldr_color.png">

    </div>

    <div class="titulo">
        REPORTE DE TRASLADO
    </div>

    <!-- INFORMACION GENERAL -->

    <div class="bloque">

        <div class="encabezado">
            INFORMACIÓN GENERAL DEL TRASLADO
        </div>

        <table>

            <tr>
                <td class="label">FOLIO</td>
                <td class="valor"><?= $traslado['folio']; ?></td>
            </tr>

            <tr>
                <td class="label">ORIGEN</td>
                <td><?= $traslado['almacen_origen']; ?></td>
            </tr>

            <tr>
                <td class="label">DESTINO</td>
                <td><?= $traslado['almacen_destino']; ?></td>
            </tr>

            <tr>
                <td class="label">TIPO DE TRASLADO</td>
                <td><?= strtoupper($traslado['tipo_traslado']); ?></td>
            </tr>

            <tr>
                <td class="label">PROVEEDOR</td>
                <td><?= $traslado['proveedor']; ?></td>
            </tr>

            <tr>
                <td class="label">FECHA PROGRAMADA</td>
                <td><?= $traslado['fecha_programada']; ?></td>
            </tr>

        </table>

    </div>

    <!-- TRASLADISTA -->

    <div class="bloque">

        <div class="encabezado">
            DATOS DEL TRASLADISTA
        </div>

        <table>

            <tr>
                <td class="label">NOMBRE</td>
                <td><?= $trasladista['nombre']; ?></td>
            </tr>

            <tr>
                <td class="label">CONTACTO</td>
                <td><?= $trasladista['contacto']; ?></td>
            </tr>

            <tr>
                <td class="label">LICENCIA</td>
                <td><?= $trasladista['numero_licencia']; ?></td>
            </tr>

            <tr>
                <td class="label">VIGENCIA LICENCIA</td>
                <td><?= $trasladista['vigencia_licencia']; ?></td>
            </tr>

        </table>

    </div>

    <!-- UNIDADES -->

    <div class="bloque">

        <div class="encabezado">
            UNIDADES INCLUIDAS EN EL TRASLADO
        </div>

        <table class="tabla-unidades">

            <thead>
                <tr>
                    <th>#</th>
                    <th>VIN</th>
                    <th>MODELO</th>
                    <th>OBSERVACIONES</th>
                </tr>
            </thead>

            <tbody>

                <?php $i=1; ?>

                <?php foreach($detalle as $row){ ?>

                <tr>

                    <td><?= $i++; ?></td>

                    <td><?= $row['vin']; ?></td>

                    <td><?= $row['modelo']; ?></td>

                    <td></td>

                </tr>

                <?php } ?>

            </tbody>

        </table>

    </div>

    <!-- ESTATUS -->

    <div class="bloque">

        <table>

            <tr>

                <td class="label">
                    ESTADO ACTUAL
                </td>

                <td class="estado">

                    <?php

                    $estatus = [
                        1 => 'SOLICITADO',
                        2 => 'SALIDA',
                        3 => 'EN TRÁNSITO',
                        4 => 'RECIBIDO',
                        5 => 'CANCELADO'
                    ];

                    echo $estatus[$traslado['estado']] ?? 'N/D';

                    ?>

                </td>

            </tr>

        </table>

    </div>

    <!-- QR -->

    <div class="bloque">

        <div class="encabezado">
            QR DE TRAZABILIDAD
        </div>

        <div class="qr">

            <div class="qr-box">

                QR

            </div>

        </div>

    </div>

    <!-- FIRMAS -->

    <div class="firmas">

        <div class="firma">

            <div class="linea"></div>

            RESPONSABLE DE SALIDA

        </div>

        <div class="firma" style="float:right;">

            <div class="linea"></div>

            RESPONSABLE DE RECEPCIÓN

        </div>

    </div>

    <div style="clear:both"></div>

    <div class="nota">

        Este documento fue generado automáticamente por el Sistema MRP y contiene
        la información registrada del traslado de la unidad. Los datos mostrados
        corresponden al estado del traslado al momento de su emisión.

    </div>

</div>

</body>
</html>