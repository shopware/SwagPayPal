<?php

declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\Log\Package;
use Swag\PayPal\RestApi\PayPalApiStruct;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\AuthorizationCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\CaptureCollection;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\RefundCollection;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_payments")
 */
#[Package('checkout')]
class Payments extends PayPalApiStruct
{
    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_order_authorization"}, nullable=true)
     */
    protected ?AuthorizationCollection $authorizations = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_order_capture"}, nullable=true)
     */
    protected ?CaptureCollection $captures = null;

    /**
     * @OA\Property(type="array", items={"$ref": "#/components/schemas/swag_paypal_v2_order_refund"}, nullable=true)
     */
    protected ?RefundCollection $refunds = null;

    public function getAuthorizations(): ?AuthorizationCollection
    {
        return $this->authorizations;
    }

    public function setAuthorizations(?AuthorizationCollection $authorizations): void
    {
        $this->authorizations = $authorizations;
    }

    public function getCaptures(): ?CaptureCollection
    {
        return $this->captures;
    }

    public function setCaptures(?CaptureCollection $captures): void
    {
        $this->captures = $captures;
    }

    public function getRefunds(): ?RefundCollection
    {
        return $this->refunds;
    }

    public function setRefunds(?RefundCollection $refunds): void
    {
        $this->refunds = $refunds;
    }
}
