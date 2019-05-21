<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Struct\Struct;

class SPBCheckoutData extends Struct
{
    /**
     * @var string
     */
    private $payerId;

    /**
     * @var string
     */
    private $paymentId;

    public function __construct(string $paymentId, string $payerId)
    {
        $this->paymentId = $paymentId;
        $this->payerId = $payerId;
    }

    public function getPaymentId(): string
    {
        return $this->paymentId;
    }

    public function getPayerId(): string
    {
        return $this->payerId;
    }
}
