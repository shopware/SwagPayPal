<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V1\Api\Disputes\Item;

use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V1\Api\Disputes\Item\RefundDetails\AllowedRefundAmount;

class RefundDetails extends PayPalApiStruct
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var AllowedRefundAmount
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
