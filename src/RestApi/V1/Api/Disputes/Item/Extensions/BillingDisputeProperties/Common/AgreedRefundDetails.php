<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\Common;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_extensions_billing_dispute_properties_common_agreed_refund_details')]
#[Package('checkout')]
class AgreedRefundDetails extends PayPalApiStruct
{
    #[OA\Property(type: 'boolean')]
    protected bool $merchantAgreedRefund;

    #[OA\Property(type: 'string')]
    protected string $merchantAgreedRefundTime;

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
