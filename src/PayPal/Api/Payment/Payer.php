<?php declare(strict_types=1);

namespace Swag\PayPal\PayPal\Api\Payment;

use Swag\PayPal\PayPal\Api\Common\PayPalStruct;
use Swag\PayPal\PayPal\Api\Payment\Payer\PayerInfo;

class Payer extends PayPalStruct
{
    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var PayerInfo
     */
    protected $payerInfo;

    /**
     * @var string
     */
    protected $externalSelectedFundingInstrumentType;

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getPayerInfo(): PayerInfo
    {
        return $this->payerInfo;
    }

    public function setExternalSelectedFundingInstrumentType(string $externalSelectedFundingInstrumentType): void
    {
        $this->externalSelectedFundingInstrumentType = $externalSelectedFundingInstrumentType;
    }

    public function setPayerInfo(PayerInfo $payerInfo): void
    {
        $this->payerInfo = $payerInfo;
    }

    protected function setStatus(string $status): void
    {
        $this->status = $status;
    }
}
