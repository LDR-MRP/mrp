<!DOCTYPE html>
<html lang="es">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Acceso al Sistema EMR</title>

<style>
    body {
        font-family: Arial, sans-serif;
        color: #444444;
        margin: 0;
        padding: 0;
        background-color: #f7f7f7;
    }
    table {
        max-width: 600px;
        margin: auto;
        background-color: #ffffff;
        border-collapse: collapse;
    }
    p {
        font-size: 15px;
        line-height: 1.6;
        margin: 10px 20px;
        text-align: justify; /* JUSTIFICAR TEXTO */
    }
    .info-table {
        width: 100%;
        border-top: 1px solid #ddd;
        border-bottom: 1px solid #ddd;
        margin: 20px 0;
    }
    .info-table td {
        padding: 8px 20px;
        font-size: 14px;
    }
    .info-label {
        font-weight: bold;
        color: #D46227;
    }
    .footer {
        font-size: 12px;
        color: #888888;
        text-align: center;
        padding: 15px;
    }
</style>
</head>

<body>

<table>
  <tr>
    <td align="center" style="padding: 20px;">
      <img src="https://viaticos.ldrhumanresources.com/viaticos/Assets/images/Logotipo_Naranja.png"
           alt="Logotipo" width="140" style="display: block; margin: auto;">
    </td>
  </tr>

  <tr>
    <td>

      <p>Hola <?= $data['nombreUsuario']; ?>,</p>

      <p>
        Su cuenta en el sistema <strong>EMR/MRP</strong> ha sido creada correctamente.
      </p>

      <p>
        A continuación, se le proporcionan sus <strong>credenciales de acceso temporales</strong>.
        Por favor, guarde esta información en un lugar seguro y utilícela para ingresar al sistema por primera vez.
        Una vez dentro, le recomendamos actualizar su contraseña para mantener la seguridad de su cuenta.
      </p>

      <table class="info-table">
        <tr>
          <td class="info-label">Usuario:</td>
          <td><strong><?= $data['email']; ?></strong></td>
        </tr>
        <tr>
          <td class="info-label">Contraseña temporal:</td>
          <td><strong><?= $data['password']; ?></strong></td>
        </tr>
      </table>

      <p style="text-align: center;">
        <a href="<?= BASE_URL; ?>" target="_blank" 
           style="background-color: #021749; color: #fff; text-decoration: none;
                  padding: 10px 25px; border-radius: 6px; font-weight: bold; 
                  display: inline-block;">
          INGRESAR AL SISTEMA
        </a>
      </p>

      <div class="footer">
        <p>Este es un mensaje automático. Por favor, no responda a este correo.</p>
      </div>

    </td>
  </tr>
</table>

</body>
</html>
