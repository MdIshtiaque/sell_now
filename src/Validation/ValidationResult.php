<?php

declare(strict_types=1);

namespace SellNow\Validation;

/**
 * Validation Result - Holds the outcome of validation
 */
class ValidationResult
{
    private array $errors = [];
    private array $validated = [];

    public function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }

    public function setValidated(array $data): self
    {
        $this->validated = $data;
        return $this;
    }

    public function isValid(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->isValid();
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get all errors as flat array of messages
     */
    public function getAllErrors(): array
    {
        $messages = [];
        foreach ($this->errors as $field => $fieldErrors) {
            foreach ($fieldErrors as $error) {
                $messages[] = $error;
            }
        }
        return $messages;
    }

    /**
     * Get first error for a field
     */
    public function getFirstError(string $field): ?string
    {
        return $this->errors[$field][0] ?? null;
    }

    /**
     * Get first error message overall
     */
    public function getFirstErrorMessage(): ?string
    {
        foreach ($this->errors as $fieldErrors) {
            return $fieldErrors[0] ?? null;
        }
        return null;
    }

    /**
     * Get validated data
     */
    public function getValidated(): array
    {
        return $this->validated;
    }

    /**
     * Get single validated value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->validated[$key] ?? $default;
    }

    /**
     * Check if field has error
     */
    public function hasError(string $field): bool
    {
        return isset($this->errors[$field]);
    }
}
