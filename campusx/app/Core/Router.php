<?php
declare(strict_types=1);
namespace SdutSwap\Core;

final class Router
{
    private array $routes = [];

    /** @param list<\SdutSwap\Middleware\Middleware> $middleware Run in order before the handler. */
    public function get(string $pattern, callable $handler, array $middleware = []): void { $this->add('GET', $pattern, $handler, $middleware); }
    public function post(string $pattern, callable $handler, array $middleware = []): void { $this->add('POST', $pattern, $handler, $middleware); }

    private function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        // {path} matches the rest of the URI (including slashes); others match one segment.
        $regex = '#^' . preg_replace_callback(
            '#\{(\w+)\}#',
            fn(array $m) => '(?P<' . $m[1] . '>' . ($m[1] === 'path' ? '.+' : '[^/]+') . ')',
            $pattern
        ) . '$#';
        $this->routes[] = ['method' => $method, 'pattern' => $pattern, 'regex' => $regex, 'handler' => $handler, 'middleware' => $middleware];
    }

    /** Registered routes with the class names of their middleware (used by the route-policy tests). */
    public function routeTable(): array
    {
        return array_map(fn(array $r): array => [
            'method' => $r['method'],
            'pattern' => $r['pattern'],
            'middleware' => array_map(fn(object $m): string => $m::class, $r['middleware']),
        ], $this->routes);
    }

    public function dispatch(string $method, string $uri): void
    {
        $uri = '/' . trim($uri, '/');
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            if (preg_match($route['regex'], $uri, $m)) {
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                // Middleware runs first; each one aborts/redirects (and exits) when its check fails.
                foreach ($route['middleware'] as $mw) {
                    $mw->handle();
                }
                ($route['handler'])(...array_values($params));
                return;
            }
        }
        abort(404, 'Page not found');
    }
}
