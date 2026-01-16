<?php

declare(strict_types=1);

namespace SellNow\Payment;

use SellNow\Entities\Order;
use SellNow\Payment\Gateways\StripeGateway;
use SellNow\Payment\Gateways\PayPalGateway;
use SellNow\Payment\Gateways\RazorpayGateway;

/**
 * Payment Service - Factory and manager for payment gateways
 * Allows easy swapping and addition of payment providers
 */
class PaymentService
{
    /** @var PaymentGatewayInterface[] */
    private array $gateways = [];

    private ?string $defaultGateway = null;

    public function __construct()
    {
        // Register default gateways
        $this->registerDefaultGateways();
    }

    /**
     * Register the built-in payment gateways
     */
    private function registerDefaultGateways(): void
    {
        $this->register(new StripeGateway());
        $this->register(new PayPalGateway());
        $this->register(new RazorpayGateway());
    }

    /**
     * Register a payment gateway
     */
    public function register(PaymentGatewayInterface $gateway): self
    {
        $this->gateways[$gateway->getName()] = $gateway;
        
        // Set first registered as default
        if ($this->defaultGateway === null) {
            $this->defaultGateway = $gateway->getName();
        }
        
        return $this;
    }

    /**
     * Get a specific gateway by name
     */
    public function gateway(string $name): PaymentGatewayInterface
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Payment gateway '{$name}' not found");
        }
        
        return $this->gateways[$name];
    }

    /**
     * Get the default gateway
     */
    public function getDefault(): PaymentGatewayInterface
    {
        if ($this->defaultGateway === null) {
            throw new \RuntimeException('No payment gateways registered');
        }
        
        return $this->gateways[$this->defaultGateway];
    }

    /**
     * Set the default gateway
     */
    public function setDefault(string $name): self
    {
        if (!isset($this->gateways[$name])) {
            throw new \InvalidArgumentException("Payment gateway '{$name}' not found");
        }
        
        $this->defaultGateway = $name;
        return $this;
    }

    /**
     * Get all available gateways
     * @return PaymentGatewayInterface[]
     */
    public function getAvailable(): array
    {
        return array_filter(
            $this->gateways,
            fn(PaymentGatewayInterface $gateway) => $gateway->isAvailable()
        );
    }

    /**
     * Get list of available gateway names for display
     */
    public function getAvailableNames(): array
    {
        $available = [];
        foreach ($this->getAvailable() as $gateway) {
            $available[$gateway->getName()] = $gateway->getDisplayName();
        }
        return $available;
    }

    /**
     * Process payment using specified or default gateway
     */
    public function processPayment(
        Order $order,
        array $paymentDetails,
        ?string $gatewayName = null
    ): PaymentResult {
        $gateway = $gatewayName 
            ? $this->gateway($gatewayName) 
            : $this->getDefault();
            
        if (!$gateway->isAvailable()) {
            return PaymentResult::failure(
                "Payment gateway '{$gateway->getDisplayName()}' is not available"
            );
        }
        
        return $gateway->charge($order, $paymentDetails);
    }

    /**
     * Process refund using specified gateway
     */
    public function processRefund(
        string $transactionId,
        float $amount,
        string $gatewayName
    ): PaymentResult {
        return $this->gateway($gatewayName)->refund($transactionId, $amount);
    }

    /**
     * Check if a gateway exists
     */
    public function has(string $name): bool
    {
        return isset($this->gateways[$name]);
    }

    /**
     * Remove a gateway
     */
    public function remove(string $name): self
    {
        unset($this->gateways[$name]);
        
        if ($this->defaultGateway === $name) {
            $this->defaultGateway = array_key_first($this->gateways);
        }
        
        return $this;
    }
}
