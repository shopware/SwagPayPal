<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_extensions_aggred_refund_details")
 */
class AgreedRefundDetails extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var bool
     * @OA\Property(type="boolean")
     */
    protected $merchantAgreedRefund;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $merchantAgreedRefundTime;

    public function isMerchantAgreedRefund(): bool
    {
        return $this->merchantAgreedRefund;
    }

    public function setMerchantAgreedRefund(bool $merchantAgreedRefund): void
    {
        $this->merchantAgreedRefund = $merchantAgreedRefund;
    }

    public function getMerchantAgreedRefundTime(): string
    {
        return $this->merchantAgreedRefundTime;
    }

    public function setMerchantAgreedRefundTime(string $merchantAgreedRefundTime): void
    {
        $this->merchantAgreedRefundTime = $merchantAgreedRefundTime;
    }
}
