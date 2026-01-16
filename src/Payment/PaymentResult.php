<?php

declare(strict_types=1);

namespace SellNow\Payment;

/**
 * Payment Result - Represents the outcome of a payment operation
 */
class PaymentResult
{
    public function __construct(
        private bool $success,
        private string $transactionId = '',
        private string $message = '',
        private array $metadata = []
    ) {}

    public static function success(string $transactionId, string $message = 'Payment successful', array $metadata = []): self
    {
        return new self(
            success: true,
            transactionId: $transactionId,
            message: $message,
            metadata: $metadata
        );
    }

    public static function failure(string $message, array $metadata = []): self
    {
        return new self(
            success: false,
            transactionId: '',
            message: $message,
            metadata: $metadata
        );
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'transaction_id' => $this->transactionId,
            'message' => $this->message,
            'metadata' => $this->metadata,
        ];
    }
}
