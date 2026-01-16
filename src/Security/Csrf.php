<?php

declare(strict_types=1);

namespace SellNow\Security;

/**
 * CSRF Protection
 * Generates and validates CSRF tokens for form submissions
 */
class Csrf
{
    private const TOKEN_NAME = '_csrf_token';
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY = 'csrf_tokens';

    /**
     * Generate a new CSRF token
     */
    public static function generate(): string
    {
        self::ensureSessionStarted();
        
        $token = bin2hex(random_bytes(self::TOKEN_LENGTH));
        
        // Store token in session with timestamp
        $_SESSION[self::SESSION_KEY][$token] = time();
        
        // Clean up old tokens (older than 1 hour)
        self::cleanup();
        
        return $token;
    }

    /**
     * Validate a CSRF token
     */
    public static function validate(?string $token): bool
    {
        self::ensureSessionStarted();
        
        if (empty($token)) {
            return false;
        }
        
        if (!isset($_SESSION[self::SESSION_KEY][$token])) {
            return false;
        }
        
        // Check if token is expired (1 hour)
        $createdAt = $_SESSION[self::SESSION_KEY][$token];
        if (time() - $createdAt > 3600) {
            unset($_SESSION[self::SESSION_KEY][$token]);
            return false;
        }
        
        // Remove used token (single use)
        unset($_SESSION[self::SESSION_KEY][$token]);
        
        return true;
    }

    /**
     * Validate token from POST request
     */
    public static function validateRequest(): bool
    {
        $token = $_POST[self::TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        return self::validate($token);
    }

    /**
     * Get the token field name
     */
    public static function getTokenName(): string
    {
        return self::TOKEN_NAME;
    }

    /**
     * Generate hidden input field HTML
     */
    public static function field(): string
    {
        $token = self::generate();
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            self::TOKEN_NAME,
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Generate meta tag for AJAX requests
     */
    public static function metaTag(): string
    {
        $token = self::generate();
        return sprintf(
            '<meta name="csrf-token" content="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Clean up expired tokens
     */
    private static function cleanup(): void
    {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return;
        }
        
        $now = time();
        foreach ($_SESSION[self::SESSION_KEY] as $token => $createdAt) {
            if ($now - $createdAt > 3600) {
                unset($_SESSION[self::SESSION_KEY][$token]);
            }
        }
        
        // Limit total tokens to prevent session bloat
        if (count($_SESSION[self::SESSION_KEY]) > 100) {
            // Keep only the 50 most recent tokens
            arsort($_SESSION[self::SESSION_KEY]);
            $_SESSION[self::SESSION_KEY] = array_slice(
                $_SESSION[self::SESSION_KEY],
                0,
                50,
                true
            );
        }
    }

    /**
     * Ensure session is started
     */
    private static function ensureSessionStarted(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
