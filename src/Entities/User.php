<?php

declare(strict_types=1);

namespace SellNow\Entities;

/**
 * User Entity - Represents a user in the system
 */
class User
{
    public function __construct(
        private ?int $id = null,
        private string $email = '',
        private string $username = '',
        private string $fullName = '',
        private string $password = '',
        private ?\DateTimeImmutable $createdAt = null
    ) {}

    // Factory method to create from database row
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) ($data['id'] ?? 0),
            email: $data['email'] ?? '',
            username: $data['username'] ?? '',
            fullName: $data['Full_Name'] ?? '',
            password: $data['password'] ?? '',
            createdAt: isset($data['created_at']) 
                ? new \DateTimeImmutable($data['created_at']) 
                : null
        );
    }

    // Convert to array for database insertion
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'username' => $this->username,
            'Full_Name' => $this->fullName,
            'password' => $this->password,
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getFullName(): string
    {
        return $this->fullName;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    // Setters (return self for fluent interface)
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    public function setFullName(string $fullName): self
    {
        $this->fullName = $fullName;
        return $this;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    // Business logic: Hash password
    public function hashPassword(): self
    {
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        return $this;
    }

    // Business logic: Verify password
    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->password);
    }
}
