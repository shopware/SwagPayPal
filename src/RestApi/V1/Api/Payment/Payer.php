<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Payment;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Payment\Payer\PayerInfo;

#[OA\Schema(schema: 'swag_paypal_v1_payment_payer')]
#[Package('checkout')]
class Payer extends PayPalApiStruct
{
    #[OA\Property(type: 'string')]
    protected string $paymentMethod;

    #[OA\Property(type: 'string')]
    protected string $status;

    #[OA\Property(ref: PayerInfo::class)]
    protected PayerInfo $payerInfo;

    #[OA\Property(type: 'string')]
    protected string $externalSelectedFundingInstrumentType;

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
