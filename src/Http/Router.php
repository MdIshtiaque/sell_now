<?php

declare(strict_types=1);

namespace SellNow\Http;

/**
 * Simple Router - Replaces the giant switch statement
 * Supports static routes and dynamic parameters
 */
class Router
{
    private array $routes = [];
    private array $middlewares = [];
    /** @var callable|array|null */
    private mixed $notFoundHandler = null;

    /**
     * Register a GET route
     */
    public function get(string $path, callable|array $handler): self
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     */
    public function post(string $path, callable|array $handler): self
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     */
    public function put(string $path, callable|array $handler): self
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $path, callable|array $handler): self
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register route for any HTTP method
     */
    public function any(string $path, callable|array $handler): self
    {
        foreach (['GET', 'POST', 'PUT', 'DELETE'] as $method) {
            $this->addRoute($method, $path, $handler);
        }
        return $this;
    }

    /**
     * Register a route with specific method
     */
    public function addRoute(string $method, string $path, callable|array $handler): self
    {
        $this->routes[$method][$path] = [
            'handler' => $handler,
            'pattern' => $this->pathToRegex($path),
        ];
        return $this;
    }

    /**
     * Group routes with common prefix
     */
    public function group(string $prefix, callable $callback): self
    {
        $group = new RouterGroup($this, $prefix);
        $callback($group);
        return $this;
    }

    /**
     * Set 404 handler
     */
    public function setNotFoundHandler(callable|array $handler): self
    {
        $this->notFoundHandler = $handler;
        return $this;
    }

    /**
     * Convert path pattern to regex
     * Supports: /users/{id}, /posts/{slug}
     */
    private function pathToRegex(string $path): string
    {
        // Escape forward slashes
        $pattern = preg_quote($path, '#');
        
        // Convert {param} to named capture group
        $pattern = preg_replace('/\\\{([a-zA-Z_]+)\\\}/', '(?P<$1>[^/]+)', $pattern);
        
        return '#^' . $pattern . '$#';
    }

    /**
     * Dispatch the request to appropriate handler
     */
    public function dispatch(Request $request): mixed
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Check for exact match first
        if (isset($this->routes[$method][$path])) {
            return $this->callHandler($this->routes[$method][$path]['handler'], []);
        }

        // Check for pattern matches
        if (isset($this->routes[$method])) {
            foreach ($this->routes[$method] as $route) {
                if (preg_match($route['pattern'], $path, $matches)) {
                    // Extract named parameters
                    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                    return $this->callHandler($route['handler'], $params);
                }
            }
        }

        // No route found
        return $this->handleNotFound();
    }

    /**
     * Call the route handler
     */
    private function callHandler(callable|array $handler, array $params): mixed
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            
            // If class is string, instantiate it
            if (is_string($class)) {
                $class = new $class();
            }
            
            return call_user_func_array([$class, $method], $params);
        }

        return call_user_func_array($handler, $params);
    }

    /**
     * Handle 404 not found
     */
    private function handleNotFound(): void
    {
        if ($this->notFoundHandler) {
            $this->callHandler($this->notFoundHandler, []);
            return;
        }

        Response::notFound();
    }

    /**
     * Get all registered routes (for debugging)
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}

/**
 * Router Group - For grouping routes with common prefix
 */
class RouterGroup
{
    public function __construct(
        private Router $router,
        private string $prefix
    ) {}

    public function get(string $path, callable|array $handler): self
    {
        $this->router->get($this->prefix . $path, $handler);
        return $this;
    }

    public function post(string $path, callable|array $handler): self
    {
        $this->router->post($this->prefix . $path, $handler);
        return $this;
    }

    public function put(string $path, callable|array $handler): self
    {
        $this->router->put($this->prefix . $path, $handler);
        return $this;
    }

    public function delete(string $path, callable|array $handler): self
    {
        $this->router->delete($this->prefix . $path, $handler);
        return $this;
    }
}
