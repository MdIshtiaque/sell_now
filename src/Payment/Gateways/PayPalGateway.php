<?php

declare(strict_types=1);

namespace SellNow\Payment\Gateways;

use SellNow\Entities\Order;
use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\PaymentResult;

/**
 * PayPal Payment Gateway Implementation
 * This is a mock implementation - replace with actual PayPal SDK calls in production
 */
class PayPalGateway implements PaymentGatewayInterface
{
    private string $clientId;
    private string $clientSecret;
    private bool $sandbox;

    public function __construct(
        string $clientId = '',
        string $clientSecret = '',
        bool $sandbox = true
    ) {
        $this->clientId = $clientId ?: ($_ENV['PAYPAL_CLIENT_ID'] ?? '');
        $this->clientSecret = $clientSecret ?: ($_ENV['PAYPAL_CLIENT_SECRET'] ?? '');
        $this->sandbox = $sandbox;
    }

    public function getName(): string
    {
        return 'paypal';
    }

    public function getDisplayName(): string
    {
        return 'PayPal';
    }

    public function isAvailable(): bool
    {
        return !empty($this->clientId) || $this->sandbox;
    }

    public function charge(Order $order, array $paymentDetails): PaymentResult
    {
        try {
            // In production, this would use PayPal REST API:
            // Create order -> Capture payment

            if ($this->sandbox) {
                $transactionId = 'paypal_' . strtoupper(bin2hex(random_bytes(10)));
                
                return PaymentResult::success(
                    transactionId: $transactionId,
                    message: 'Payment processed successfully via PayPal',
                    metadata: [
                        'provider' => 'paypal',
                        'amount' => $order->getTotalAmount(),
                        'currency' => 'USD',
                        'sandbox' => true,
                        'payer_email' => $paymentDetails['payer_email'] ?? 'test@example.com',
                    ]
                );
            }

            return PaymentResult::failure('PayPal API not configured');

        } catch (\Exception $e) {
            return PaymentResult::failure('PayPal error: ' . $e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount): PaymentResult
    {
        try {
            if ($this->sandbox) {
                $refundId = 'paypal_refund_' . bin2hex(random_bytes(8));
                return PaymentResult::success(
                    transactionId: $refundId,
                    message: "Refunded ${$amount} successfully via PayPal",
                    metadata: ['original_transaction' => $transactionId]
                );
            }

            return PaymentResult::failure('PayPal API not configured for refunds');

        } catch (\Exception $e) {
            return PaymentResult::failure('Refund failed: ' . $e->getMessage());
        }
    }

    public function verifyPayment(array $payload): PaymentResult
    {
        // Verify PayPal IPN/Webhook in production
        $transactionId = $payload['txn_id'] ?? $payload['id'] ?? '';
        
        if (empty($transactionId)) {
            return PaymentResult::failure('Invalid PayPal webhook payload');
        }

        return PaymentResult::success(
            transactionId: $transactionId,
            message: 'PayPal payment verified'
        );
    }

    public function getCheckoutUrl(Order $order): ?string
    {
        // In production, create PayPal order and return approval URL
        if ($this->sandbox) {
            return "https://www.sandbox.paypal.com/checkoutnow?token=mock_" . $order->getId();
        }
        return null;
    }
}
