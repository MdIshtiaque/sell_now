<?php

declare(strict_types=1);

namespace SellNow\Entities;

/**
 * Product Entity - Represents a digital product
 */
class Product
{
    public function __construct(
        private ?int $id = null,
        private int $userId = 0,
        private string $title = '',
        private string $slug = '',
        private string $description = '',
        private float $price = 0.0,
        private string $imagePath = '',
        private string $filePath = '',
        private bool $isActive = true
    ) {}

    // Factory method to create from database row
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['product_id']) ? (int) $data['product_id'] : null,
            userId: (int) ($data['user_id'] ?? 0),
            title: $data['title'] ?? '',
            slug: $data['slug'] ?? '',
            description: $data['description'] ?? '',
            price: (float) ($data['price'] ?? 0),
            imagePath: $data['image_path'] ?? '',
            filePath: $data['file_path'] ?? '',
            isActive: (bool) ($data['is_active'] ?? true)
        );
    }

    // Convert to array for database insertion
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'image_path' => $this->imagePath,
            'file_path' => $this->filePath,
            'is_active' => $this->isActive ? 1 : 0,
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getImagePath(): string
    {
        return $this->imagePath;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    // Setters
    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function setImagePath(string $imagePath): self
    {
        $this->imagePath = $imagePath;
        return $this;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function setActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    // Business logic: Generate slug from title
    public function generateSlug(): self
    {
        $slug = strtolower(trim($this->title));
        $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
        $slug = preg_replace('/-+/', '-', $slug);
        $this->slug = $slug . '-' . rand(1000, 9999);
        return $this;
    }

    // Business logic: Format price for display
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }
}
