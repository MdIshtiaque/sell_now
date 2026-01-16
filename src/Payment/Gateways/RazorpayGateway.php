<?php

declare(strict_types=1);

namespace SellNow\Payment\Gateways;

use SellNow\Entities\Order;
use SellNow\Payment\PaymentGatewayInterface;
use SellNow\Payment\PaymentResult;

/**
 * Razorpay Payment Gateway Implementation
 * This is a mock implementation - replace with actual Razorpay SDK calls in production
 */
class RazorpayGateway implements PaymentGatewayInterface
{
    private string $keyId;
    private string $keySecret;
    private bool $testMode;

    public function __construct(
        string $keyId = '',
        string $keySecret = '',
        bool $testMode = true
    ) {
        $this->keyId = $keyId ?: ($_ENV['RAZORPAY_KEY_ID'] ?? '');
        $this->keySecret = $keySecret ?: ($_ENV['RAZORPAY_KEY_SECRET'] ?? '');
        $this->testMode = $testMode;
    }

    public function getName(): string
    {
        return 'razorpay';
    }

    public function getDisplayName(): string
    {
        return 'Razorpay';
    }

    public function isAvailable(): bool
    {
        return !empty($this->keyId) || $this->testMode;
    }

    public function charge(Order $order, array $paymentDetails): PaymentResult
    {
        // Validate Razorpay payment signature
        $razorpayPaymentId = $paymentDetails['razorpay_payment_id'] ?? '';
        $razorpaySignature = $paymentDetails['razorpay_signature'] ?? '';

        try {
            // In production:
            // $api = new \Razorpay\Api\Api($this->keyId, $this->keySecret);
            // $api->payment->fetch($razorpayPaymentId)->capture(['amount' => $amount]);

            if ($this->testMode) {
                $transactionId = 'rzp_' . bin2hex(random_bytes(12));
                
                return PaymentResult::success(
                    transactionId: $transactionId,
                    message: 'Payment processed successfully via Razorpay',
                    metadata: [
                        'provider' => 'razorpay',
                        'amount' => $order->getTotalAmount(),
                        'currency' => 'INR',
                        'test_mode' => true,
                    ]
                );
            }

            return PaymentResult::failure('Razorpay API not configured');

        } catch (\Exception $e) {
            return PaymentResult::failure('Razorpay error: ' . $e->getMessage());
        }
    }

    public function refund(string $transactionId, float $amount): PaymentResult
    {
        try {
            // In production:
            // $api->refund->create(['payment_id' => $transactionId, 'amount' => $amount * 100]);
            
            if ($this->testMode) {
                $refundId = 'rzp_refund_' . bin2hex(random_bytes(8));
                return PaymentResult::success(
                    transactionId: $refundId,
                    message: "Refunded ${$amount} successfully via Razorpay",
                    metadata: ['original_transaction' => $transactionId]
                );
            }

            return PaymentResult::failure('Razorpay API not configured for refunds');

        } catch (\Exception $e) {
            return PaymentResult::failure('Refund failed: ' . $e->getMessage());
        }
    }

    public function verifyPayment(array $payload): PaymentResult
    {
        $paymentId = $payload['razorpay_payment_id'] ?? '';
        $orderId = $payload['razorpay_order_id'] ?? '';
        $signature = $payload['razorpay_signature'] ?? '';

        if (empty($paymentId)) {
            return PaymentResult::failure('Invalid Razorpay payload');
        }

        // In production, verify signature:
        // $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        // if ($signature !== $expectedSignature) return failure;

        return PaymentResult::success(
            transactionId: $paymentId,
            message: 'Razorpay payment verified'
        );
    }

    public function getCheckoutUrl(Order $order): ?string
    {
        // Razorpay uses embedded checkout, no redirect URL
        return null;
    }

    /**
     * Create Razorpay order (required before checkout)
     */
    public function createOrder(float $amount, string $currency = 'INR'): array
    {
        // In production:
        // return $api->order->create([...]);
        
        return [
            'id' => 'order_' . bin2hex(random_bytes(8)),
            'amount' => (int) ($amount * 100), // Razorpay uses paise
            'currency' => $currency,
        ];
    }
}
