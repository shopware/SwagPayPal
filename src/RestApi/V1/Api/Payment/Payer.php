<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\PayerInfo;

class Payer extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $paymentMethod;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $status;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var PayerInfo
     */
    protected $payerInfo;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     */
    protected $externalSelectedFundingInstrumentType;

    public function getPaymentMethod(): string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getPayerInfo(): PayerInfo
    {
        return $this->payerInfo;
    }

    public function setPayerInfo(PayerInfo $payerInfo): void
    {
        $this->payerInfo = $payerInfo;
    }

    public function getExternalSelectedFundingInstrumentType(): string
    {
        return $this->externalSelectedFundingInstrumentType;
    }

    public function setExternalSelectedFundingInstrumentType(string $externalSelectedFundingInstrumentType): void
    {
        $this->externalSelectedFundingInstrumentType = $externalSelectedFundingInstrumentType;
    }
}
