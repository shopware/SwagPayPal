<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item\Extensions\BillingDisputeProperties\CreditNotProcessed;

use Swag\PayPal\RestApi\PayPalApiStruct;

class AgreedRefundDetails extends PayPalApiStruct
{
    /**
     * @var bool
     */
    protected $merchantAgreedRefund;

    /**
     * @var string
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
