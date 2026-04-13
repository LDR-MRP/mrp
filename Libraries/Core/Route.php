<?php
namespace Libraries\Core;

class Route {
    public static array $routes = [];

    public static function get(string $uri, array $action): self { return self::add('GET', $uri, $action); }
    public static function post(string $uri, array $action): self { return self::add('POST', $uri, $action); }
    public static function put(string $uri, array $action): self { return self::add('PUT', $uri, $action); }
    public static function delete(string $uri, array $action): self { return self::add('DELETE', $uri, $action); }

    private static function add(string $method, string $uri, array $action): self {
        self::$routes[] = [
            'method'      => $method,
            'uri'         => trim($uri, '/'),
            'action'      => $action,
            'middlewares' => []
        ];
        return new self();
    }

    public function middleware(array|string $middlewares): self {
        $lastIndex = array_key_last(self::$routes);
        self::$routes[$lastIndex]['middlewares'] = is_array($middlewares) ? $middlewares : [$middlewares];
        return $this;
    }
}
?>