<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

class Authorization extends RelatedResource
{
    /**
     * @var string
     */
    private $reasonCode;

    /**
     * @var string
     */
    private $validUntil;

    protected function setReasonCode(string $reasonCode): void
    {
        $this->reasonCode = $reasonCode;
    }

    protected function setValidUntil(string $validUntil): void
    {
        $this->validUntil = $validUntil;
    }
}
