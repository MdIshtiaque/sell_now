<?php

declare(strict_types=1);

namespace SellNow\Validation\Rules;

/**
 * Custom Validation Rule Interface
 * Implement this to create reusable custom validation rules
 */
interface RuleInterface
{
    /**
     * Determine if the validation rule passes
     */
    public function passes(mixed $value, array $params = []): bool;

    /**
     * Get the validation error message
     */
    public function getMessage(): string;
}
