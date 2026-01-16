<?php

declare(strict_types=1);

namespace SellNow\Validation\Rules;

use PDO;

/**
 * Unique Rule - Check if value is unique in database
 * 
 * Usage in validator:
 *   $rule = new UniqueRule($db, 'users', 'email', $excludeId);
 */
class UniqueRule implements RuleInterface
{
    public function __construct(
        private PDO $db,
        private string $table,
        private string $column,
        private ?int $excludeId = null
    ) {}

    public function passes(mixed $value, array $params = []): bool
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$this->column} = ?";
        $bindings = [$value];

        if ($this->excludeId !== null) {
            $sql .= " AND id != ?";
            $bindings[] = $this->excludeId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);

        return (int) $stmt->fetchColumn() === 0;
    }

    public function getMessage(): string
    {
        return "The :field has already been taken.";
    }
}
