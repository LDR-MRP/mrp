<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Recurso No Encontrado</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f3f9;
            color: #405189;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            text-align: center;
            max-width: 500px;
            padding: 20px;
        }
        h1 {
            font-size: 80px;
            margin: 0;
            color: #0ab39c; /* Color secundario SRM */
        }
        h2 {
            font-size: 24px;
            margin-top: 10px;
            color: #405189; /* Color primario ERP */
        }
        p {
            font-size: 16px;
            color: #7c7f90;
            margin-bottom: 30px;
        }
        .btn-back {
            padding: 12px 24px;
            font-size: 15px;
            font-weight: 600;
            background-color: #405189;
            color: #ffffff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s ease-in-out;
        }
        .btn-back:hover {
            background-color: #33416e;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Recurso no encontrado</h2>
        <p>La página o el recurso al que intentas acceder no existe, ha sido movido o no tienes los permisos suficientes.</p>
        
        <!-- Al hacer clic, el navegador regresa a la página inmediatamente anterior -->
        <button onclick="window.history.back();" class="btn-back">Regresar</button>
    </div>
</body>
</html>