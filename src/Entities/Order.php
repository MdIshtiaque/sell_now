<?php

declare(strict_types=1);

namespace SellNow\Entities;

/**
 * Order Entity - Represents a completed order
 */
class Order
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REFUNDED = 'refunded';

    public function __construct(
        private ?int $id = null,
        private ?int $userId = null,
        private float $totalAmount = 0.0,
        private string $paymentProvider = '',
        private string $paymentStatus = self::STATUS_PENDING,
        private string $transactionId = '',
        private ?\DateTimeImmutable $orderDate = null,
        /** @var CartItem[] */
        private array $items = []
    ) {}

    // Factory method to create from database row
    public static function fromArray(array $data): self
    {
        return new self(
            id: isset($data['id']) ? (int) $data['id'] : null,
            userId: isset($data['user_id']) ? (int) $data['user_id'] : null,
            totalAmount: (float) ($data['total_amount'] ?? 0),
            paymentProvider: $data['payment_provider'] ?? '',
            paymentStatus: $data['payment_status'] ?? self::STATUS_PENDING,
            transactionId: $data['transaction_id'] ?? '',
            orderDate: isset($data['order_date']) 
                ? new \DateTimeImmutable($data['order_date']) 
                : null
        );
    }

    // Convert to array for database insertion
    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'total_amount' => $this->totalAmount,
            'payment_provider' => $this->paymentProvider,
            'payment_status' => $this->paymentStatus,
            'transaction_id' => $this->transactionId,
            'order_date' => $this->orderDate?->format('Y-m-d H:i:s') 
                ?? (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getTotalAmount(): float
    {
        return $this->totalAmount;
    }

    public function getPaymentProvider(): string
    {
        return $this->paymentProvider;
    }

    public function getPaymentStatus(): string
    {
        return $this->paymentStatus;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getOrderDate(): ?\DateTimeImmutable
    {
        return $this->orderDate;
    }

    /**
     * @return CartItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    // Setters
    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function setTotalAmount(float $totalAmount): self
    {
        $this->totalAmount = $totalAmount;
        return $this;
    }

    public function setPaymentProvider(string $paymentProvider): self
    {
        $this->paymentProvider = $paymentProvider;
        return $this;
    }

    public function setPaymentStatus(string $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;
        return $this;
    }

    public function setTransactionId(string $transactionId): self
    {
        $this->transactionId = $transactionId;
        return $this;
    }

    public function setOrderDate(\DateTimeImmutable $orderDate): self
    {
        $this->orderDate = $orderDate;
        return $this;
    }

    public function addItem(CartItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    // Business logic: Calculate total from items
    public function calculateTotal(): self
    {
        $this->totalAmount = array_reduce(
            $this->items,
            fn(float $sum, CartItem $item) => $sum + $item->getSubtotal(),
            0.0
        );
        return $this;
    }

    // Business logic: Check if order is paid
    public function isPaid(): bool
    {
        return $this->paymentStatus === self::STATUS_PAID;
    }

    // Business logic: Mark as paid
    public function markAsPaid(string $transactionId): self
    {
        $this->paymentStatus = self::STATUS_PAID;
        $this->transactionId = $transactionId;
        return $this;
    }

    // Business logic: Format total for display
    public function getFormattedTotal(): string
    {
        return '$' . number_format($this->totalAmount, 2);
    }

    // Business logic: Get formatted date
    public function getFormattedDate(): string
    {
        return $this->orderDate?->format('M d, Y h:i A') ?? 'N/A';
    }
}
