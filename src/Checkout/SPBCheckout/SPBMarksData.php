<?php declare(strict_types=1);

namespace Swag\PayPal\Checkout\SPBCheckout;

use Shopware\Core\Framework\Struct\Struct;

class SPBMarksData extends Struct
{
    /**
     * @var string
     */
    protected $clientId;

    /**
     * @var string
     */
    protected $paymentMethodId;

    public function __construct(string $clientId, string $paymentMethodId)
    {
        $this->clientId = $clientId;
        $this->paymentMethodId = $paymentMethodId;
    }

    public function getClientId(): string
    {
        return $this->clientId;
    }

    public function getPaymentMethodId(): string
    {
        return $this->paymentMethodId;
    }
}
