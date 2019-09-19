<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment\Transaction\RelatedResource;

class Refund extends RelatedResource
{
    /**
     * @var string
     */
    private $saleId;

    /**
     * @var string
     */
    private $captureId;

    protected function setSaleId(string $saleId): void
    {
        $this->saleId = $saleId;
    }

    protected function setCaptureId(string $captureId): void
    {
        $this->captureId = $captureId;
    }
}
