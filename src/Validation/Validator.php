<?php

declare(strict_types=1);

namespace SellNow\Validation;

/**
 * Validator - Validates input data against rules
 * 
 * Usage:
 *   $validator = new Validator($data, [
 *       'email' => 'required|email',
 *       'password' => 'required|min:8',
 *       'age' => 'numeric|between:18,100',
 *   ]);
 *   
 *   if ($validator->fails()) {
 *       $errors = $validator->errors();
 *   }
 */
class Validator
{
    private array $data;
    private array $rules;
    private ValidationResult $result;
    private array $customMessages = [];

    private const DEFAULT_MESSAGES = [
        'required' => 'The :field field is required.',
        'email' => 'The :field must be a valid email address.',
        'min' => 'The :field must be at least :param characters.',
        'max' => 'The :field must not exceed :param characters.',
        'numeric' => 'The :field must be a number.',
        'integer' => 'The :field must be an integer.',
        'alpha' => 'The :field must contain only letters.',
        'alphanumeric' => 'The :field must contain only letters and numbers.',
        'between' => 'The :field must be between :param1 and :param2.',
        'in' => 'The :field must be one of: :param.',
        'confirmed' => 'The :field confirmation does not match.',
        'url' => 'The :field must be a valid URL.',
        'regex' => 'The :field format is invalid.',
        'file' => 'The :field must be a valid file.',
        'image' => 'The :field must be an image.',
        'max_size' => 'The :field must not exceed :param KB.',
    ];

    public function __construct(array $data, array $rules, array $customMessages = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->customMessages = $customMessages;
        $this->result = new ValidationResult();
        
        $this->validate();
    }

    /**
     * Static factory method
     */
    public static function make(array $data, array $rules, array $messages = []): self
    {
        return new self($data, $rules, $messages);
    }

    /**
     * Run validation
     */
    private function validate(): void
    {
        $validated = [];

        foreach ($this->rules as $field => $ruleString) {
            $rules = is_array($ruleString) ? $ruleString : explode('|', $ruleString);
            $value = $this->getValue($field);
            
            foreach ($rules as $rule) {
                $this->applyRule($field, $value, $rule);
            }
            
            // Store validated value
            if (!$this->result->hasError($field)) {
                $validated[$field] = $value;
            }
        }

        $this->result->setValidated($validated);
    }

    /**
     * Get value from data (supports dot notation)
     */
    private function getValue(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    /**
     * Apply a single rule
     */
    private function applyRule(string $field, mixed $value, string $rule): void
    {
        // Parse rule and parameters
        $parts = explode(':', $rule, 2);
        $ruleName = $parts[0];
        $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

        // Skip validation if empty and not required
        if ($ruleName !== 'required' && $this->isEmpty($value)) {
            return;
        }

        $method = 'validate' . ucfirst($ruleName);
        
        if (method_exists($this, $method)) {
            if (!$this->$method($value, $params)) {
                $this->addError($field, $ruleName, $params);
            }
        }
    }

    /**
     * Check if value is empty
     */
    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    /**
     * Add validation error
     */
    private function addError(string $field, string $rule, array $params = []): void
    {
        $message = $this->customMessages["{$field}.{$rule}"]
            ?? $this->customMessages[$rule]
            ?? self::DEFAULT_MESSAGES[$rule]
            ?? "The {$field} is invalid.";

        // Replace placeholders
        $message = str_replace(':field', str_replace('_', ' ', $field), $message);
        $message = str_replace(':param', implode(', ', $params), $message);
        
        foreach ($params as $i => $param) {
            $message = str_replace(':param' . ($i + 1), $param, $message);
        }

        $this->result->addError($field, $message);
    }

    // ==================== Validation Rules ====================

    protected function validateRequired(mixed $value, array $params): bool
    {
        return !$this->isEmpty($value);
    }

    protected function validateEmail(mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    protected function validateMin(mixed $value, array $params): bool
    {
        $min = (int) ($params[0] ?? 0);
        return strlen((string) $value) >= $min;
    }

    protected function validateMax(mixed $value, array $params): bool
    {
        $max = (int) ($params[0] ?? PHP_INT_MAX);
        return strlen((string) $value) <= $max;
    }

    protected function validateNumeric(mixed $value, array $params): bool
    {
        return is_numeric($value);
    }

    protected function validateInteger(mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    protected function validateAlpha(mixed $value, array $params): bool
    {
        return ctype_alpha((string) $value);
    }

    protected function validateAlphanumeric(mixed $value, array $params): bool
    {
        return ctype_alnum((string) $value);
    }

    protected function validateBetween(mixed $value, array $params): bool
    {
        $min = (float) ($params[0] ?? 0);
        $max = (float) ($params[1] ?? PHP_INT_MAX);
        $val = is_numeric($value) ? (float) $value : strlen((string) $value);
        return $val >= $min && $val <= $max;
    }

    protected function validateIn(mixed $value, array $params): bool
    {
        return in_array($value, $params, true);
    }

    protected function validateConfirmed(mixed $value, array $params): bool
    {
        $confirmField = $params[0] ?? '';
        return $value === ($this->data[$confirmField] ?? null);
    }

    protected function validateUrl(mixed $value, array $params): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    protected function validateRegex(mixed $value, array $params): bool
    {
        $pattern = $params[0] ?? '';
        return preg_match($pattern, (string) $value) === 1;
    }

    // ==================== Public Methods ====================

    public function fails(): bool
    {
        return $this->result->fails();
    }

    public function passes(): bool
    {
        return $this->result->isValid();
    }

    public function errors(): array
    {
        return $this->result->getErrors();
    }

    public function getResult(): ValidationResult
    {
        return $this->result;
    }

    public function validated(): array
    {
        return $this->result->getValidated();
    }
}
