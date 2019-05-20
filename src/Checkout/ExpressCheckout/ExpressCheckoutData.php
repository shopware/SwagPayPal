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
    private $paymentId;

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

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function setPaymentId(string $paymentId): void
    {
        $this->paymentId = $paymentId;
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
