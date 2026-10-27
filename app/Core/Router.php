<?php
declare(strict_types=1);

namespace App\Core;

final class Router
{
    private array $routes = [];
    private array $groups = [];

    public function get(string $pattern, array|\Closure $handler, array $middleware = []): void
    {
        $this->add(['GET', 'HEAD'], $pattern, $handler, $middleware);
    }

    public function post(string $pattern, array|\Closure $handler, array $middleware = []): void
    {
        $this->add(['POST'], $pattern, $handler, $middleware);
    }

    public function delete(string $pattern, array|\Closure $handler, array $middleware = []): void
    {
        $this->add(['DELETE'], $pattern, $handler, $middleware);
    }

    public function group(array $options, callable $callback): void
    {
        $this->groups[] = $options;
        $callback($this);
        array_pop($this->groups);
    }

    private function add(array $methods, string $pattern, array|\Closure $handler, array $middleware): void
    {
        $prefix = '';
        $groupMiddleware = [];
        foreach ($this->groups as $group) {
            $prefix .= $group['prefix'] ?? '';
            $groupMiddleware = array_merge($groupMiddleware, $group['middleware'] ?? []);
        }
        $full = '/' . trim($prefix . '/' . trim($pattern, '/'), '/');

        $regex = preg_replace_callback(
            '/\{(\w+)(?::([^}]+))?\}/',
            static fn ($m) => '(?P<' . $m[1] . '>' . ($m[2] ?? '[^/]+') . ')',
            $full
        );

        $this->routes[] = [
            'methods' => $methods,
            'regex' => '#^' . $regex . '$#',
            'handler' => $handler,
            'middleware' => array_merge($groupMiddleware, $middleware),
        ];
    }

    /** @return array{0: array|\Closure, 1: array, 2: array}|null handler, params, middleware */
    public function match(string $method, string $path): ?array
    {
        $allowed = false;
        foreach ($this->routes as $route) {
            if (!preg_match($route['regex'], $path, $m)) {
                continue;
            }
            if (!in_array($method, $route['methods'], true)) {
                $allowed = true;
                continue;
            }
            $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
            return [$route['handler'], $params, $route['middleware']];
        }
        if ($allowed) {
            throw new HttpException(405, 'Method not allowed');
        }
        return null;
    }
}
