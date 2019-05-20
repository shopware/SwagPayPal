<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\ExpressCheckout;

use Shopware\Core\Framework\Struct\Struct;

class ExpressCheckoutData extends Struct
{
    /**
     * @var bool
     */
    private $isExpressCheckout;

    /**
     * @var string
     */
    private $transactionId;

    /**
     * @var string
     */
    private $payerId;

    /**
     * @var string
     */
    private $paymentMethodId;

    public function isExpressCheckout(): bool
    {
        return $this->isExpressCheckout;
    }

    public function setIsExpressCheckout(bool $isExpressCheckout): void
    {
        $this->isExpressCheckout = $isExpressCheckout;
    }

    public function getTransactionId(): string
    {
        return $this->transactionId;
    }

    public function setTransactionId(string $transactionId): void
    {
        $this->transactionId = $transactionId;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }

    public function setPayerId(string $payerId): void
    {
        $this->payerId = $payerId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }

    public function setPaymentMethodId(string $paymentMethodId): void
    {
        $this->paymentMethodId = $paymentMethodId;
    }
}
