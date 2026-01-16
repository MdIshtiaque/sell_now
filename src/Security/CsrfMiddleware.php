<?php

declare(strict_types=1);

namespace SellNow\Security;

use SellNow\Http\Request;
use SellNow\Http\Response;

/**
 * CSRF Middleware
 * Validates CSRF tokens on POST/PUT/DELETE requests
 */
class CsrfMiddleware
{
    private array $excludedPaths = [];

    public function __construct(array $excludedPaths = [])
    {
        $this->excludedPaths = $excludedPaths;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, callable $next): mixed
    {
        // Skip for GET, HEAD, OPTIONS requests
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Skip for excluded paths (webhooks, APIs with other auth)
        if ($this->isExcluded($request->getPath())) {
            return $next($request);
        }

        // Validate CSRF token
        if (!Csrf::validateRequest()) {
            Response::forbidden('Invalid CSRF token. Please refresh the page and try again.');
            return null;
        }

        return $next($request);
    }

    /**
     * Check if path is excluded from CSRF protection
     */
    private function isExcluded(string $path): bool
    {
        foreach ($this->excludedPaths as $excluded) {
            if ($excluded === $path) {
                return true;
            }
            
            // Support wildcard patterns
            if (str_ends_with($excluded, '*')) {
                $prefix = rtrim($excluded, '*');
                if (str_starts_with($path, $prefix)) {
                    return true;
                }
            }
        }
        
        return false;
    }

    /**
     * Add path to exclusion list
     */
    public function exclude(string $path): self
    {
        $this->excludedPaths[] = $path;
        return $this;
    }
}
