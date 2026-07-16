<?php

class Errors extends Controllers {
    public function __construct()
    {
        parent::__construct();
    }

    public function notFound()
    {
        http_response_code(404);

        if (file_exists("Views/Errors/error404.php")) {
            require_once("Views/Errors/error404.php");
        } else {
            // Respaldo simple con botón de regresar
            echo '<div style="text-align: center; margin-top: 50px; font-family: sans-serif;">';
            echo '<h1 style="color: #405189;">404 - Página no encontrada</h1>';
            echo '<p style="color: #7c7f90;">El recurso solicitado no existe o no está disponible.</p>';
            // Ejecuta history.back() al hacer clic
            echo '<button onclick="window.history.back();" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background-color: #405189; color: white; border: none; border-radius: 4px;">Regresar</button>';
            echo '</div>';
        }

        exit;
    }
}

$notFound = new Errors();
$notFound->notFound();
?>