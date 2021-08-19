<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments;

use OpenApi\Annotations as OA;
use Swag\PayPal\RestApi\V2\Api\Order\PurchaseUnit\Payments\Authorization\SellerProtection;

/**
 * @OA\Schema(schema="swag_paypal_v2_order_authorization")
 */
class Authorization extends Payment
{
    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var SellerProtection
     * @OA\Property(ref="#/components/schemas/swag_paypal_v2_order_authorization_seller_protection")
     */
    protected $sellerProtection;

    /**
     * @deprecated tag:v4.0.0 - will be strongly typed
     *
     * @var string
     * @OA\Property(type="string")
     */
    protected $expirationTime;

    public function getSellerProtection(): SellerProtection
    {
        return $this->sellerProtection;
    }

    public function setSellerProtection(SellerProtection $sellerProtection): void
    {
        $this->sellerProtection = $sellerProtection;
    }

    public function getExpirationTime(): string
    {
        return $this->expirationTime;
    }

    public function setExpirationTime(string $expirationTime): void
    {
        $this->expirationTime = $expirationTime;
    }
}
