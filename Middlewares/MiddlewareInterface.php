<?php
namespace Middlewares;

use Closure;

interface MiddlewareInterface {
    /**
     * @param array $request Datos de la petición (Headers, Body, etc.)
     * @param Closure $next Siguiente capa en el pipeline
     */
    public function handle(array $request, Closure $next);
}