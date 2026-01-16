<?php

declare(strict_types=1);

namespace SellNow\Repositories;

use PDO;
use SellNow\Entities\User;

/**
 * User Repository - Handles all database operations for users
 */
class UserRepository implements RepositoryInterface
{
    public function __construct(
        private PDO $db
    ) {}

    public function find(int $id): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? User::fromArray($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? User::fromArray($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row ? User::fromArray($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return array_map(fn(array $row) => User::fromArray($row), $rows);
    }

    public function create(object $entity): int
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Expected User entity');
        }

        $sql = "INSERT INTO users (email, username, Full_Name, password) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $entity->getEmail(),
            $entity->getUsername(),
            $entity->getFullName(),
            $entity->getPassword(),
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function update(object $entity): bool
    {
        if (!$entity instanceof User) {
            throw new \InvalidArgumentException('Expected User entity');
        }

        $sql = "UPDATE users SET email = ?, username = ?, Full_Name = ?, password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $entity->getEmail(),
            $entity->getUsername(),
            $entity->getFullName(),
            $entity->getPassword(),
            $entity->getId(),
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE email = ?";
        $params = [$email];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn() > 0;
    }

    public function usernameExists(string $username, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM users WHERE username = ?";
        $params = [$username];
        
        if ($excludeId !== null) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int) $stmt->fetchColumn() > 0;
    }
}
