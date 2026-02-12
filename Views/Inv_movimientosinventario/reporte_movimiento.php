<?php
$logoPath = realpath(__DIR__ . '/../../Assets/images/ldr_negro.png');
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">

<style>
body {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 12px;
  color: #333;
}

.header {
  width: 100%;
  background: #f57c00;
  color: #fff;
  padding: 12px;
}

.header-table {
  width: 100%;
}

.logo img {
  height: 45px;
}

.header-right {
  text-align: right;
  font-size: 12px;
}

.title {
  text-align: center;
  margin: 15px 0;
  font-size: 16px;
  font-weight: bold;
  color: #f57c00;
}

.data-box {
  width: 100%;
  margin-bottom: 15px;
  font-size: 12px;
}

.data-box td {
  padding: 6px;
}

.table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.table th{
background: #ffe0b2;
  border: 1px solid #f57c00;
  padding: 8px;
  white-space: normal !important;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

.table td {
  border: 1px solid #ddd;
  padding: 8px;
  white-space: normal !important;
  word-wrap: break-word;
  overflow-wrap: break-word;
}

table { 
  width:100%; 
}

td, th {
  overflow: hidden;
}


.producto-wrap {
  width: 100%;
  max-width: 100%;
  white-space: normal;
  word-wrap: break-word;
  overflow-wrap: break-word;
  line-height: 1.3;
}

.t-right {
  text-align: right;
}

.footer {
  margin-top: 20px;
  text-align: center;
  font-size: 10px;
  color: #777;
}
</style>
</head>

<body>

<div class="header">
  <table class="header-table">
    <tr>
      <td class="logo">
        <img src="<?= $logoPath ?>" style="height:45px;">
      </td>
      <td class="header-right">
        <b>Movimientos</b><br>
        <?= $data['movimiento']['fecha_movimiento'] ?>
      </td>
    </tr>
  </table>
</div>

<div class="title">
  REPORTE DE MOVIMIENTO DE INVENTARIO
</div>

<table class="data-box">
<tr>
  <td width="15%"><b>Almacén:</b></td>
  <td width="35%"><?= $data['movimiento']['almacen'] ?></td>

  <td width="15%"><b>Concepto:</b></td>
  <td width="35%"><?= $data['movimiento']['concepto'] ?></td>
</tr>
<tr>
  <td><b>Referencia:</b></td>
  <td><?= $data['movimiento']['referencia'] ?></td>

</tr>
</table>

<table class="table">
<colgroup>
  <col style="width:45%;">
  <col style="width:15%;">
  <col style="width:20%;">
  <col style="width:20%;">
</colgroup>
<thead>
<tr>
  <th>Producto</th>
  <th style="text-align:right;">Cantidad</th>
  <th style="text-align:right;">Costo</th>
  <th style="text-align:right;">Importe</th>
</tr>
</thead>

<tbody>

<?php 
$totalGeneral = 0;
foreach ($data['detalle'] as $d) { 
  $totalGeneral += $d['total'];
?>
<tr>
  <td>
  <div style="word-wrap:break-word; white-space:normal;">
    <?= $d['descripcion'] ?>
  </div>
</td>
  <td class="t-right"><?= number_format($d['cantidad'] * $d['signo']) ?></td>
  <td class="t-right"><?= number_format($d['costo_cantidad'],2) ?></td>
  <td class="t-right"><?= number_format($d['total'],2) ?></td>
</tr>
<?php } ?>

<tr>
  <td colspan="3" class="t-right" style="background:#fff3e0;"><b>TOTAL</b></td>
  <td class="t-right" style="background:#fff3e0;"><b><?= number_format($totalGeneral,2) ?></b></td>
</tr>


</tbody>
</table>

<div class="footer">
  Documento generado por el sistema WMS
</div>

</body>
</html>
