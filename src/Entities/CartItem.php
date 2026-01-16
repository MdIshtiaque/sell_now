<?php

declare(strict_types=1);

namespace SellNow\Entities;

/**
 * CartItem Entity - Represents an item in the shopping cart
 */
class CartItem
{
    public function __construct(
        private ?int $id = null,
        private int $productId = 0,
        private string $title = '',
        private float $price = 0.0,
        private int $quantity = 1
    ) {}

    // Factory method to create from database row or session data
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            productId: (int) ($data['product_id'] ?? 0),
            title: $data['title'] ?? '',
            price: (float) ($data['price'] ?? 0),
            quantity: (int) ($data['quantity'] ?? 1)
        );
    }

    // Factory method to create from Product entity
    public static function fromProduct(Product $product, int $quantity = 1): self
    {
        return new self(
            productId: $product->getId() ?? 0,
            title: $product->getTitle(),
            price: $product->getPrice(),
            quantity: $quantity
        );
    }

    // Convert to array for session storage
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'title' => $this->title,
            'price' => $this->price,
            'quantity' => $this->quantity,
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getPrice(): float
    {
        return $this->price;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    // Setters
    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(1, $quantity); // Minimum 1
        return $this;
    }

    public function incrementQuantity(int $amount = 1): self
    {
        $this->quantity += $amount;
        return $this;
    }

    // Business logic: Calculate subtotal
    public function getSubtotal(): float
    {
        return $this->price * $this->quantity;
    }

    // Business logic: Format subtotal for display
    public function getFormattedSubtotal(): string
    {
        return '$' . number_format($this->getSubtotal(), 2);
    }

    // Business logic: Format price for display
    public function getFormattedPrice(): string
    {
        return '$' . number_format($this->price, 2);
    }
}
