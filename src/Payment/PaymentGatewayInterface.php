<?php

declare(strict_types=1);

namespace SellNow\Payment;

use SellNow\Entities\Order;

/**
 * Payment Gateway Interface
 * All payment providers must implement this interface
 * This allows swapping payment providers without changing business logic
 */
interface PaymentGatewayInterface
{
    /**
     * Get the unique identifier for this gateway
     */
    public function getName(): string;

    /**
     * Get human-readable display name
     */
    public function getDisplayName(): string;

    /**
     * Check if this gateway is properly configured and available
     */
    public function isAvailable(): bool;

    /**
     * Process a payment for the given order
     */
    public function charge(Order $order, array $paymentDetails): PaymentResult;

    /**
     * Refund a previously processed payment
     */
    public function refund(string $transactionId, float $amount): PaymentResult;

    /**
     * Verify a payment/webhook from the provider
     */
    public function verifyPayment(array $payload): PaymentResult;

    /**
     * Get the URL to redirect user for payment (if applicable)
     */
    public function getCheckoutUrl(Order $order): ?string;
}
