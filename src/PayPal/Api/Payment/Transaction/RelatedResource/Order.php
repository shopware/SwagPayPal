<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

class Order extends RelatedResource
{
    /**
     * @var string
     */
    private $reasonCode;

    protected function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }
}
