<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\RefundDetails\AllowedRefundAmount;

/**
 * @OA\Schema(schema="swag_paypal_v1_disputes_refund_details")
 */
class RefundDetails extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var AllowedRefundAmount
     * @OA\Property(ref="#/components/schemas/swag_paypal_v1_common_money")
     */
    protected $allowedRefundAmount;

    public function getAllowedRefundAmount(): AllowedRefundAmount
    {
        return $this->allowedRefundAmount;
    }

    public function setAllowedRefundAmount(AllowedRefundAmount $allowedRefundAmount): void
    {
        $this->allowedRefundAmount = $allowedRefundAmount;
    }
}
