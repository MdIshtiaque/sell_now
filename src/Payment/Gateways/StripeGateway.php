<?php

declare(strict_types=1);

namespace SellNow\Payment\Gateways;

use SellNow\Entities\Order;
use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\PaymentResult;

/**
 * Stripe Payment Gateway Implementation
 * This is a mock implementation - replace with actual Stripe SDK calls in production
 */
class StripeGateway implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $secretKey;
    private bool $testMode;

    public function __construct(
        string $apiKey = '',
        string $secretKey = '',
        bool $testMode = true
    ) {
        $this->apiKey = $apiKey ?: ($_ENV['STRIPE_API_KEY'] ?? '');
        $this->secretKey = $secretKey ?: ($_ENV['STRIPE_SECRET_KEY'] ?? '');
        $this->testMode = $testMode;
    }

    public function getName(): string
    {
        return 'stripe';
    }

    public function getDisplayName(): string
    {
        return 'Stripe';
    }

    public function isAvailable(): bool
    {
        // In production, check if API keys are configured
        return !empty($this->apiKey) || $this->testMode;
    }

    public function charge(Order $order, array $paymentDetails): PaymentResult
    {
        // Validate required payment details
        if (empty($paymentDetails['token']) && !$this->testMode) {
            return PaymentResult::failure('Payment token is required');
        }

        try {
            // In production, this would call Stripe API:
            // \Stripe\Stripe::setApiKey($this->secretKey);
            // $charge = \Stripe\Charge::create([...]);

            // Mock implementation for demonstration
            if ($this->testMode) {
                $transactionId = 'stripe_' . bin2hex(random_bytes(16));
                
                return PaymentResult::success(
                    transactionId: $transactionId,
                    message: 'Payment processed successfully via Stripe',
                    metadata: [
                        'provider' => 'stripe',
                        'amount' => $order->getTotalAmount(),
                        'currency' => 'USD',
                        'test_mode' => true,
                    ]
                );
            }

            // Production Stripe API call would go here
            return PaymentResult::failure('Stripe API not configured');

        } catch (\Exception $e) {
            return PaymentResult::failure('Stripe error: ' . $e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount): PaymentResult
    {
        try {
            // In production: \Stripe\Refund::create([...]);
            
            if ($this->testMode) {
                $refundId = 'refund_' . bin2hex(random_bytes(8));
                return PaymentResult::success(
                    transactionId: $refundId,
                    message: "Refunded ${$amount} successfully",
                    metadata: ['original_transaction' => $transactionId]
                );
            }

            return PaymentResult::failure('Stripe API not configured for refunds');

        } catch (\Exception $e) {
            return PaymentResult::failure('Refund failed: ' . $e->getMessage());
        }
    }

    public function verifyPayment(array $payload): PaymentResult
    {
        // Verify Stripe webhook signature in production
        $transactionId = $payload['payment_intent'] ?? $payload['id'] ?? '';
        
        if (empty($transactionId)) {
            return PaymentResult::failure('Invalid webhook payload');
        }

        return PaymentResult::success(
            transactionId: $transactionId,
            message: 'Payment verified'
        );
    }

    public function getCheckoutUrl(Order $order): ?string
    {
        // In production, create Stripe Checkout Session and return URL
        // return $session->url;
        return null; // Stripe uses embedded checkout
    }
}
