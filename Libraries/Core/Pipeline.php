<?php
namespace Libraries\Core;

use Closure;

class Pipeline {
    public static function process(mixed $request, array $middlewares, Closure $core): mixed {
        $next = $core;

        foreach (array_reverse($middlewares) as $middlewareClass) {
            $middleware = new $middlewareClass();
            $next = function($request) use ($middleware, $next) {
                return $middleware->handle($request, $next);
            };
        }

        return $next($request);
    }
}
?>