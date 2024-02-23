<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Attributes as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Common\Money;

#[OA\Schema(schema: 'swag_paypal_v1_disputes_item_refund_details')]
#[Package('checkout')]
class RefundDetails extends PayPalApiStruct
{
    #[OA\Property(ref: Money::class)]
    protected Money $allowedRefundAmount;

    public function getAllowedRefundAmount(): Money
    {
        return $this->allowedRefundAmount;
    }

    public function setAllowedRefundAmount(Money $allowedRefundAmount): void
    {
        $this->allowedRefundAmount = $allowedRefundAmount;
    }
}
