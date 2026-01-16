<?php

declare(strict_types=1);

namespace SellNow\Security;

/**
 * Input Sanitizer
 * Provides methods to sanitize user input
 */
class Sanitizer
{
    /**
     * Sanitize string - trim and remove tags
     */
    public static function string(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return trim(strip_tags($value));
    }

    /**
     * Sanitize email
     */
    public static function email(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return filter_var(trim($value), FILTER_SANITIZE_EMAIL) ?: '';
    }

    /**
     * Sanitize integer
     */
    public static function int(?string $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Sanitize float
     */
    public static function float(?string $value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * Sanitize URL
     */
    public static function url(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return filter_var(trim($value), FILTER_SANITIZE_URL) ?: '';
    }

    /**
     * Escape HTML entities (for output)
     */
    public static function html(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Sanitize filename
     */
    public static function filename(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        // Remove any path components
        $filename = basename($value);
        // Remove special characters
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $filename);
    }

    /**
     * Sanitize slug (URL-friendly string)
     */
    public static function slug(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Sanitize alphanumeric (letters and numbers only)
     */
    public static function alphanumeric(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return preg_replace('/[^a-zA-Z0-9]/', '', $value);
    }

    /**
     * Sanitize array of values
     */
    public static function array(array $data, array $rules): array
    {
        $result = [];
        
        foreach ($rules as $key => $type) {
            if (!isset($data[$key])) {
                continue;
            }
            
            $result[$key] = match ($type) {
                'string' => self::string($data[$key]),
                'email' => self::email($data[$key]),
                'int', 'integer' => self::int($data[$key]),
                'float', 'decimal' => self::float($data[$key]),
                'url' => self::url($data[$key]),
                'html' => self::html($data[$key]),
                'filename' => self::filename($data[$key]),
                'slug' => self::slug($data[$key]),
                'alphanumeric' => self::alphanumeric($data[$key]),
                default => self::string($data[$key]),
            };
        }
        
        return $result;
    }
}
